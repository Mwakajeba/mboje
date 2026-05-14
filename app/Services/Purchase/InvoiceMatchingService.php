<?php

namespace App\Services\Purchase;

use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseInvoiceItem;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\GoodsReceipt;
use App\Models\Purchase\GoodsReceiptItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceMatchingService
{
    /**
     * Perform 3-way matching: PO vs GRN vs Invoice
     * Returns array with match results and any exceptions
     */
    public function performThreeWayMatch(PurchaseInvoice $invoice): array
    {
        $results = [
            'matched' => true,
            'exceptions' => [],
            'details' => [],
        ];

        // Get related PO and GRN
        $po = $this->getRelatedPO($invoice);
        $grn = $this->getRelatedGRN($invoice);

        if (!$po) {
            $results['matched'] = false;
            $results['exceptions'][] = 'No Purchase Order found for this invoice';
            return $results;
        }

        // Match invoice items against PO and GRN
        foreach ($invoice->items as $invoiceItem) {
            $itemMatch = $this->matchInvoiceItem($invoiceItem, $po, $grn);
            
            if (!$itemMatch['matched']) {
                $results['matched'] = false;
                $results['exceptions'] = array_merge($results['exceptions'], $itemMatch['exceptions']);
            }
            
            $results['details'][] = $itemMatch;
        }

        // Match totals
        $totalsMatch = $this->matchTotals($invoice, $po, $grn);
        if (!$totalsMatch['matched']) {
            $results['matched'] = false;
            $results['exceptions'] = array_merge($results['exceptions'], $totalsMatch['exceptions']);
        }
        $results['totals'] = $totalsMatch;

        return $results;
    }

    /**
     * Get related Purchase Order for invoice
     */
    protected function getRelatedPO(PurchaseInvoice $invoice): ?PurchaseOrder
    {
        // Try to get PO from invoice reference or supplier
        // This assumes invoice has a reference to PO or we match by supplier and items
        if ($invoice->purchase_order_id ?? null) {
            return PurchaseOrder::find($invoice->purchase_order_id);
        }

        // Try to find PO by matching supplier and items
        $po = PurchaseOrder::where('supplier_id', $invoice->supplier_id)
            ->where('status', 'approved')
            ->whereHas('items', function($q) use ($invoice) {
                $q->whereIn('item_id', $invoice->items->pluck('inventory_item_id')->filter());
            })
            ->first();

        return $po;
    }

    /**
     * Get related Goods Receipt Note for invoice
     */
    protected function getRelatedGRN(PurchaseInvoice $invoice): ?GoodsReceipt
    {
        // Try to get GRN from invoice reference
        if ($invoice->grn_id ?? null) {
            return GoodsReceipt::find($invoice->grn_id);
        }

        // Try to find GRN by matching PO
        $po = $this->getRelatedPO($invoice);
        if ($po) {
            return GoodsReceipt::where('purchase_order_id', $po->id)
                ->where('status', 'approved')
                ->first();
        }

        return null;
    }

    /**
     * Match individual invoice item against PO and GRN
     */
    protected function matchInvoiceItem(PurchaseInvoiceItem $invoiceItem, PurchaseOrder $po, ?GoodsReceipt $grn): array
    {
        $result = [
            'item_id' => $invoiceItem->inventory_item_id ?? $invoiceItem->asset_id,
            'description' => $invoiceItem->description,
            'matched' => true,
            'exceptions' => [],
            'po_match' => null,
            'grn_match' => null,
        ];

        // Find matching PO item
        $poItem = $po->items->first(function($item) use ($invoiceItem) {
            return ($item->item_id == $invoiceItem->inventory_item_id) ||
                   ($item->asset_id == $invoiceItem->asset_id) ||
                   (strtolower($item->description) == strtolower($invoiceItem->description));
        });

        if (!$poItem) {
            $result['matched'] = false;
            $result['exceptions'][] = 'Item not found in Purchase Order';
            return $result;
        }

        $result['po_match'] = [
            'quantity' => $poItem->quantity,
            'unit_price' => $poItem->cost_price,
            'total' => $poItem->total_amount,
        ];

        // Match quantity
        $quantityVariance = abs($invoiceItem->quantity - $poItem->quantity);
        if ($quantityVariance > 0.01) {
            $result['matched'] = false;
            $result['exceptions'][] = "Quantity mismatch: Invoice ({$invoiceItem->quantity}) vs PO ({$poItem->quantity})";
        }

        // Match unit price (allow small variance for rounding)
        $priceVariance = abs($invoiceItem->unit_cost - $poItem->cost_price);
        if ($priceVariance > 0.01) {
            $result['matched'] = false;
            $result['exceptions'][] = "Unit price mismatch: Invoice (" . number_format($invoiceItem->unit_cost, 2) . ") vs PO (" . number_format($poItem->cost_price, 2) . ")";
        }

        // Match against GRN if available
        if ($grn) {
            $grnItem = $grn->items->first(function($item) use ($invoiceItem) {
                return ($item->inventory_item_id == $invoiceItem->inventory_item_id) ||
                       ($item->purchase_order_item_id == $poItem->id);
            });

            if ($grnItem) {
                $result['grn_match'] = [
                    'quantity_ordered' => $grnItem->quantity_ordered,
                    'quantity_received' => $grnItem->quantity_received,
                    'unit_cost' => $grnItem->unit_cost,
                ];

                // Check quantity received matches invoice
                $grnQuantityVariance = abs($invoiceItem->quantity - $grnItem->quantity_received);
                if ($grnQuantityVariance > 0.01) {
                    $result['matched'] = false;
                    $result['exceptions'][] = "Quantity mismatch: Invoice ({$invoiceItem->quantity}) vs GRN received ({$grnItem->quantity_received})";
                }
            } else {
                $result['exceptions'][] = 'Item not found in Goods Receipt Note';
            }
        }

        return $result;
    }

    /**
     * Match invoice totals against PO and GRN
     */
    protected function matchTotals(PurchaseInvoice $invoice, PurchaseOrder $po, ?GoodsReceipt $grn): array
    {
        $result = [
            'matched' => true,
            'exceptions' => [],
            'po_totals' => [
                'subtotal' => $po->subtotal,
                'vat_amount' => $po->vat_amount,
                'total' => $po->total_amount,
            ],
            'invoice_totals' => [
                'subtotal' => $invoice->subtotal,
                'vat_amount' => $invoice->vat_amount,
                'total' => $invoice->total_amount,
            ],
            'grn_totals' => null,
        ];

        // Match subtotal (allow small variance)
        $subtotalVariance = abs($invoice->subtotal - $po->subtotal);
        if ($subtotalVariance > 0.01) {
            $result['matched'] = false;
            $result['exceptions'][] = "Subtotal mismatch: Invoice (" . number_format($invoice->subtotal, 2) . ") vs PO (" . number_format($po->subtotal, 2) . ")";
        }

        // Match VAT amount (allow small variance)
        $vatVariance = abs($invoice->vat_amount - $po->vat_amount);
        if ($vatVariance > 0.01) {
            $result['matched'] = false;
            $result['exceptions'][] = "VAT amount mismatch: Invoice (" . number_format($invoice->vat_amount, 2) . ") vs PO (" . number_format($po->vat_amount, 2) . ")";
        }

        // Match total amount (allow small variance)
        $totalVariance = abs($invoice->total_amount - $po->total_amount);
        if ($totalVariance > 0.01) {
            $result['matched'] = false;
            $result['exceptions'][] = "Total amount mismatch: Invoice (" . number_format($invoice->total_amount, 2) . ") vs PO (" . number_format($po->total_amount, 2) . ")";
        }

        // Match GRN totals if available
        if ($grn) {
            $result['grn_totals'] = [
                'total_quantity' => $grn->total_quantity,
                'total_amount' => $grn->total_amount,
            ];

            $grnTotalVariance = abs($invoice->subtotal - $grn->total_amount);
            if ($grnTotalVariance > 0.01) {
                $result['matched'] = false;
                $result['exceptions'][] = "Total mismatch: Invoice subtotal (" . number_format($invoice->subtotal, 2) . ") vs GRN (" . number_format($grn->total_amount, 2) . ")";
            }
        }

        return $result;
    }

    /**
     * Validate invoice and route to approval if exceptions exist
     */
    public function validateAndRoute(PurchaseInvoice $invoice): array
    {
        $matchResult = $this->performThreeWayMatch($invoice);

        if ($matchResult['matched']) {
            // Perfect match - can post directly to Accounts Payable
            return [
                'status' => 'matched',
                'can_post' => true,
                'message' => 'Invoice matches PO and GRN perfectly',
                'match_result' => $matchResult,
            ];
        } else {
            // Exceptions found - route to approval
            return [
                'status' => 'exceptions',
                'can_post' => false,
                'message' => 'Invoice has exceptions and requires approval',
                'exceptions' => $matchResult['exceptions'],
                'match_result' => $matchResult,
            ];
        }
    }

    /**
     * Check if invoice can be auto-posted (perfect match)
     */
    public function canAutoPost(PurchaseInvoice $invoice): bool
    {
        $validation = $this->validateAndRoute($invoice);
        return $validation['can_post'] && $validation['status'] === 'matched';
    }
}

