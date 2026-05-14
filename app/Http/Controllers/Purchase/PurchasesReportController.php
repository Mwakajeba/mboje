<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderItem;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseInvoiceItem;
use App\Models\Purchase\DebitNote;
use App\Models\Purchase\DebitNoteItem;
use App\Models\Purchase\GoodsReceipt;
use App\Models\Purchase\GoodsReceiptItem;
use App\Models\Purchase\PurchaseRequisition;
use App\Models\Purchase\PurchaseRequisitionLine;
use App\Models\Supplier;
use App\Models\Branch;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class PurchasesReportController extends Controller
{
    public function index()
    {
        $this->authorize('view purchases');
        return view('purchases.reports.index');
    }

    /**
     * Purchase Requisition Report
     * Item-level report showing requisition details with estimated values
     */
    public function purchaseRequisitionReport(Request $request)
    {
        $this->authorize('view purchases');

        $branchId = Auth::user()->branch_id;
        $query = PurchaseRequisition::with(['requestor', 'department', 'approvedBy', 'lines.inventoryItem'])
            ->where('company_id', Auth::user()->company_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('requestor_id')) {
            $query->where('requestor_id', $request->requestor_id);
        }

        $requisitions = $query->orderByDesc('created_at')->get();
        
        // Build item-level report data
        $reportData = collect();
        foreach ($requisitions as $req) {
            foreach ($req->lines as $line) {
                $estimatedValue = ($line->quantity ?? 0) * ($line->unit_price_estimate ?? 0);
                $approvalStatus = 'Pending';
                if ($req->status === 'approved' || $req->status === 'po_created') {
                    $approvalStatus = 'Approved';
                } elseif ($req->status === 'rejected') {
                    $approvalStatus = 'Rejected';
                } elseif ($req->status === 'pending_approval' || $req->status === 'in_review') {
                    $approvalStatus = 'Pending Approval';
                }

                $reportData->push([
                    'requisition_id' => $req->pr_no,
                    'req_date' => $req->created_at ? $req->created_at->format('d-M-Y') : '-',
                    'requester' => $req->requestor->name ?? 'N/A',
                    'department' => $req->department->name ?? 'N/A',
                    'item_code' => $line->inventoryItem->item_code ?? ($line->inventoryItem->code ?? 'N/A'),
                    'item_description' => $line->description ?? ($line->inventoryItem->name ?? 'N/A'),
                    'qty_requested' => (float)($line->quantity ?? 0),
                    'estimated_unit_cost' => (float)($line->unit_price_estimate ?? 0),
                    'estimated_value' => (float)$estimatedValue,
                    'approval_status' => $approvalStatus,
                    'approved_by' => $req->approvedBy ? $req->approvedBy->name : '-',
                ]);
            }
        }

        // Calculate summary
        $totalRequisitions = $requisitions->count();
        $totalRequisitionValue = $reportData->groupBy('requisition_id')->map(function($items) {
            return $items->sum('estimated_value');
        })->sum();

        // Get departments and requestors for filters
        $departments = \App\Models\Hr\Department::where('company_id', Auth::user()->company_id)
            ->orderBy('name')->get(['id', 'name']);
        
        $requestors = \App\Models\User::where('company_id', Auth::user()->company_id)
            ->join('purchase_requisitions', 'users.id', '=', 'purchase_requisitions.requestor_id')
            ->select('users.id', 'users.name')
            ->distinct()
            ->orderBy('users.name')
            ->get();

        return view('purchases.reports.purchase-requisition', compact(
            'reportData', 'departments', 'requestors', 'totalRequisitions', 'totalRequisitionValue'
        ));
    }

    public function purchaseOrderRegister(Request $request)
    {
        $this->authorize('view purchases');

        $branchId = Auth::user()->branch_id;
        $query = PurchaseOrder::with(['supplier', 'items.item'])
            ->where('company_id', Auth::user()->company_id)
            ->when($branchId, fn($q)=>$q->where('branch_id', $branchId));

        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderByDesc('order_date')->get();
        
        // Build item-level report data
        $reportData = collect();
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $poValue = ($item->quantity ?? 0) * ($item->cost_price ?? 0);
                $reportData->push([
                    'po_number' => $order->order_number ?? ('PO-' . $order->id),
                    'po_date' => $order->order_date ? $order->order_date->format('d-M-Y') : '-',
                    'supplier' => $order->supplier->name ?? 'Unknown',
                    'item_code' => $item->item->item_code ?? ($item->item->code ?? 'N/A'),
                    'description' => $item->description ?? ($item->item->name ?? 'N/A'),
                    'ordered_qty' => (float)($item->quantity ?? 0),
                    'unit_cost' => (float)($item->cost_price ?? 0),
                    'po_value' => (float)$poValue,
                    'status' => ucfirst($order->status ?? 'draft'),
                    'expected_delivery' => $order->expected_delivery_date ? $order->expected_delivery_date->format('d-M-Y') : '-',
                ]);
            }
        }

        // Calculate summary
        $totalPos = $orders->count();
        $totalValue = $reportData->sum('po_value');

        $suppliers = Supplier::where('company_id', Auth::user()->company_id)
            ->when($branchId, fn($q)=>$q->where('branch_id', $branchId))
            ->orderBy('name')->get(['id','name']);

        return view('purchases.reports.purchase-order-register', compact('reportData','suppliers','totalPos','totalValue'));
    }

    /**
     * Export Purchase Order Register to PDF
     */
    public function exportPurchaseOrderRegisterPdf(Request $request)
    {
        $this->authorize('view purchases');

        $branchId = Auth::user()->branch_id;
        $query = PurchaseOrder::with(['supplier', 'branch'])
            ->when($branchId, fn($q)=>$q->where('branch_id', $branchId));

        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderByDesc('order_date')->get();
        
        // Aggregates
        $totalPos = $orders->count();
        $totalValue = $orders->sum('total_amount');
        
        $supplier = $request->filled('supplier_id') ? Supplier::find($request->supplier_id) : null;
        $company = current_company();
        $branch = $branchId ? Branch::find($branchId) : null;
        
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : null;
        $status = $request->filled('status') ? $request->status : null;

        $pdf = Pdf::loadView('purchases.reports.exports.purchase-order-register-pdf', compact(
            'orders', 'totalPos', 'totalValue', 'supplier', 'company', 'branch', 'dateFrom', 'dateTo', 'status'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('purchase-order-register-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export Purchase Order Register to Excel
     */
    public function exportPurchaseOrderRegisterExcel(Request $request)
    {
        $this->authorize('view purchases');

        $branchId = Auth::user()->branch_id;
        $query = PurchaseOrder::with(['supplier', 'branch'])
            ->when($branchId, fn($q)=>$q->where('branch_id', $branchId));

        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderByDesc('order_date')->get();
        
        $supplier = $request->filled('supplier_id') ? Supplier::find($request->supplier_id) : null;
        $company = current_company();
        $branch = $branchId ? Branch::find($branchId) : null;
        
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : null;
        $status = $request->filled('status') ? $request->status : null;

        return Excel::download(new \App\Exports\PurchaseOrderRegisterExport($orders, $dateFrom, $dateTo, $branch, $supplier, $status, $company), 
            'purchase-order-register-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * PO vs GRN (Fulfillment) Report
     * Compares ordered quantities vs received quantities
     */
    public function poVsGrn(Request $request)
    {
        $this->authorize('view purchases');

        $branchId = Auth::user()->branch_id;
        $query = PurchaseOrder::with(['supplier', 'branch', 'items.item'])
            ->when($branchId, fn($q)=>$q->where('branch_id', $branchId));

        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('fulfillment_status')) {
            $fulfillmentStatus = $request->fulfillment_status;
            // We'll filter this after processing the data
        }

        $orders = $query->orderByDesc('order_date')->get();
        $reportData = $this->processPoVsGrnData($orders, $request);
        
        // Calculate summary statistics
        $totalItems = $reportData->count();
        $fullyReceived = $reportData->where('fulfillment_status', 'fully_received')->count();
        $partiallyReceived = $reportData->where('fulfillment_status', 'partially_received')->count();
        $notReceived = $reportData->where('fulfillment_status', 'not_received')->count();
        $totalOrderedQty = $reportData->sum('ordered_quantity');
        $totalReceivedQty = $reportData->sum('received_quantity');
        $totalVariance = $reportData->sum('variance');
        $totalOrderedAmount = $reportData->sum('ordered_amount');
        $totalReceivedAmount = $reportData->sum('received_amount');
        
        $suppliers = Supplier::where('company_id', Auth::user()->company_id)
            ->when($branchId, fn($q)=>$q->where('branch_id', $branchId))
            ->orderBy('name')->get(['id','name']);

        return view('purchases.reports.po-vs-grn', compact(
            'reportData', 'suppliers', 'totalItems', 'fullyReceived', 'partiallyReceived', 'notReceived',
            'totalOrderedQty', 'totalReceivedQty', 'totalVariance', 'totalOrderedAmount', 'totalReceivedAmount'
        ));
    }

    /**
     * Helper method to process PO vs GRN data
     */
    private function processPoVsGrnData($orders, $request)
    {
        $reportData = collect();
        
        foreach ($orders as $order) {
            foreach ($order->items as $poItem) {
                // Get GRN items for this PO item
                $grnItems = GoodsReceiptItem::with('goodsReceipt')
                    ->where('purchase_order_item_id', $poItem->id)
                    ->get();
                
                $receivedQuantity = $grnItems->sum('quantity_received') ?? 0;
                $orderedQuantity = $poItem->quantity ?? 0;
                $pendingQty = $orderedQuantity - $receivedQuantity;
                $fulfillmentPercentage = $orderedQuantity > 0 ? ($receivedQuantity / $orderedQuantity) * 100 : 0;
                
                // Get GRN numbers (comma-separated if multiple)
                $grnNumbers = $grnItems->map(function($item) {
                    return $item->goodsReceipt->grn_number ?? null;
                })->filter()->unique()->values()->implode(', ');
                
                $fulfillmentStatus = 'not_received';
                if ($receivedQuantity >= $orderedQuantity) {
                    $fulfillmentStatus = 'fully_received';
                } elseif ($receivedQuantity > 0) {
                    $fulfillmentStatus = 'partially_received';
                }
                
                if ($request->filled('fulfillment_status') && $request->fulfillment_status !== 'all') {
                    if ($fulfillmentStatus !== $request->fulfillment_status) {
                        continue;
                    }
                }
                
                $reportData->push([
                    'po_id' => $order->id,
                    'po_number' => $order->order_number ?? $order->reference ?? ('PO-' . $order->id),
                    'po_date' => $order->order_date,
                    'supplier_name' => $order->supplier->name ?? 'Unknown',
                    'branch_name' => $order->branch->name ?? 'N/A',
                    'item_id' => $poItem->item_id,
                    'item_name' => $poItem->item->name ?? 'Unknown Item',
                    'item_code' => $poItem->item->item_code ?? ($poItem->item->code ?? 'N/A'),
                    'ordered_quantity' => $orderedQuantity,
                    'received_quantity' => $receivedQuantity,
                    'pending_qty' => $pendingQty,
                    'variance' => $pendingQty, // For backward compatibility
                    'grn_number' => $grnNumbers ?: '-',
                    'fulfillment_percentage' => $fulfillmentPercentage,
                    'fulfillment_status' => $fulfillmentStatus,
                    'unit_price' => $poItem->cost_price ?? 0,
                    'ordered_amount' => $orderedQuantity * ($poItem->cost_price ?? 0),
                    'received_amount' => $receivedQuantity * ($poItem->cost_price ?? 0),
                ]);
            }
        }
        
        return $reportData;
    }

    /**
     * Export PO vs GRN to PDF
     */
    public function exportPoVsGrnPdf(Request $request)
    {
        $this->authorize('view purchases');

        $branchId = Auth::user()->branch_id;
        $query = PurchaseOrder::with(['supplier', 'branch', 'items.item'])
            ->when($branchId, fn($q)=>$q->where('branch_id', $branchId));

        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderByDesc('order_date')->get();
        $reportData = $this->processPoVsGrnData($orders, $request);
        
        // Calculate summary
        $totalItems = $reportData->count();
        $fullyReceived = $reportData->where('fulfillment_status', 'fully_received')->count();
        $partiallyReceived = $reportData->where('fulfillment_status', 'partially_received')->count();
        $notReceived = $reportData->where('fulfillment_status', 'not_received')->count();
        $totalOrderedQty = $reportData->sum('ordered_quantity');
        $totalReceivedQty = $reportData->sum('received_quantity');
        $totalVariance = $reportData->sum('variance');
        
        $supplier = $request->filled('supplier_id') ? Supplier::find($request->supplier_id) : null;
        $company = current_company();
        $branch = $branchId ? Branch::find($branchId) : null;
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : null;
        $status = $request->filled('status') ? $request->status : null;
        $fulfillmentStatus = $request->filled('fulfillment_status') ? $request->fulfillment_status : null;

        $pdf = Pdf::loadView('purchases.reports.exports.po-vs-grn-pdf', compact(
            'reportData', 'totalItems', 'fullyReceived', 'partiallyReceived', 'notReceived',
            'totalOrderedQty', 'totalReceivedQty', 'totalVariance', 'supplier', 'company', 'branch',
            'dateFrom', 'dateTo', 'status', 'fulfillmentStatus'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('po-vs-grn-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export PO vs GRN to Excel
     */
    public function exportPoVsGrnExcel(Request $request)
    {
        $this->authorize('view purchases');

        $branchId = Auth::user()->branch_id;
        $query = PurchaseOrder::with(['supplier', 'branch', 'items.item'])
            ->when($branchId, fn($q)=>$q->where('branch_id', $branchId));

        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderByDesc('order_date')->get();
        $reportData = $this->processPoVsGrnData($orders, $request);
        
        $supplier = $request->filled('supplier_id') ? Supplier::find($request->supplier_id) : null;
        $company = current_company();
        $branch = $branchId ? Branch::find($branchId) : null;
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : null;
        $status = $request->filled('status') ? $request->status : null;
        $fulfillmentStatus = $request->filled('fulfillment_status') ? $request->fulfillment_status : null;

        return Excel::download(new \App\Exports\PoVsGrnExport($reportData, $dateFrom, $dateTo, $branch, $supplier, $status, $fulfillmentStatus, $company), 
            'po-vs-grn-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * GRN vs Invoice Variance Report
     * Compares received quantities vs invoiced quantities
     */
    public function grnVariance(Request $request)
    {
        $this->authorize('view purchases');

        $branchId = Auth::user()->branch_id;
        $query = GoodsReceipt::with(['branch', 'items.inventoryItem', 'items.purchaseOrderItem.item', 'purchaseOrder.supplier'])
            ->when($branchId, fn($q)=>$q->where('branch_id', $branchId));

        if ($request->filled('date_from')) {
            $query->whereDate('receipt_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('receipt_date', '<=', $request->date_to);
        }
        if ($request->filled('supplier_id')) {
            // Get supplier from related PO
            $query->whereHas('purchaseOrder', function($q) use ($request) {
                $q->where('supplier_id', $request->supplier_id);
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('variance_status')) {
            $varianceStatus = $request->variance_status;
            // We'll filter this after processing the data
        }

        $grns = $query->orderByDesc('receipt_date')->get();
        
        // Process each GRN and its items to compare received vs invoiced
        $reportData = collect();
        
        foreach ($grns as $grn) {
            $supplierName = $grn->purchaseOrder->supplier->name ?? 'Unknown';
            
            foreach ($grn->items as $grnItem) {
                // Get invoiced quantity for this GRN item
                $invoiceItems = PurchaseInvoiceItem::with('invoice')
                    ->where('grn_item_id', $grnItem->id)
                    ->get();
                
                $invoicedQuantity = $invoiceItems->sum('quantity') ?? 0;
                $receivedQuantity = $grnItem->quantity_received ?? 0;
                $varianceQty = $invoicedQuantity - $receivedQuantity;
                $unitCost = $grnItem->unit_cost ?? 0;
                $varianceValue = $varianceQty * $unitCost;
                
                // Get invoice numbers (comma-separated if multiple)
                $invoiceNumbers = $invoiceItems->map(function($item) {
                    return $item->invoice->invoice_number ?? null;
                })->filter()->unique()->values()->implode(', ');
                
                // Determine variance status
                $varianceStatus = 'matched';
                if ($invoicedQuantity == 0) {
                    $varianceStatus = 'not_invoiced';
                } elseif ($invoicedQuantity < $receivedQuantity) {
                    $varianceStatus = 'under_invoiced';
                } elseif ($invoicedQuantity > $receivedQuantity) {
                    $varianceStatus = 'over_invoiced';
                }
                
                // Apply variance status filter if specified
                if ($request->filled('variance_status') && $request->variance_status !== 'all') {
                    if ($varianceStatus !== $request->variance_status) {
                        continue;
                    }
                }
                
                $reportData->push([
                    'supplier_name' => $supplierName,
                    'grn_number' => $grn->grn_number ?? ('GRN-' . $grn->id),
                    'invoice_no' => $invoiceNumbers ?: '-',
                    'received_qty' => $receivedQuantity,
                    'invoiced_qty' => $invoicedQuantity,
                    'unit_cost' => $unitCost,
                    'variance_qty' => $varianceQty,
                    'variance_value' => $varianceValue,
                    // Keep additional fields for backward compatibility
                    'grn_id' => $grn->id,
                    'grn_date' => $grn->receipt_date,
                    'variance_status' => $varianceStatus,
                ]);
            }
        }
        
        // Calculate summary statistics
        $totalItems = $reportData->count();
        $matched = $reportData->where('variance_status', 'matched')->count();
        $notInvoiced = $reportData->where('variance_status', 'not_invoiced')->count();
        $underInvoiced = $reportData->where('variance_status', 'under_invoiced')->count();
        $overInvoiced = $reportData->where('variance_status', 'over_invoiced')->count();
        $totalReceivedQty = $reportData->sum('received_qty');
        $totalInvoicedQty = $reportData->sum('invoiced_qty');
        $totalVarianceQty = $reportData->sum('variance_qty');
        $totalVarianceValue = $reportData->sum('variance_value');
        
        $suppliers = Supplier::where('company_id', Auth::user()->company_id)
            ->when($branchId, fn($q)=>$q->where('branch_id', $branchId))
            ->orderBy('name')->get(['id','name']);

        return view('purchases.reports.grn-variance', compact(
            'reportData', 'suppliers', 'totalItems', 'matched', 'notInvoiced', 'underInvoiced', 'overInvoiced',
            'totalReceivedQty', 'totalInvoicedQty', 'totalVarianceQty', 'totalVarianceValue'
        ));
    }

    /**
     * Helper method to process GRN vs Invoice data
     */
    private function processGrnVarianceData($grns, $request)
    {
        $reportData = collect();
        
        foreach ($grns as $grn) {
            $supplierName = $grn->purchaseOrder->supplier->name ?? 'Unknown';
            
            foreach ($grn->items as $grnItem) {
                $invoiceItems = PurchaseInvoiceItem::where('grn_item_id', $grnItem->id)->get();
                $invoicedQuantity = $invoiceItems->sum('quantity') ?? 0;
                
                $receivedQuantity = $grnItem->quantity_received ?? 0;
                $variance = $receivedQuantity - $invoicedQuantity;
                $variancePercentage = $receivedQuantity > 0 ? ($variance / $receivedQuantity) * 100 : 0;
                
                $varianceStatus = 'matched';
                if ($invoicedQuantity == 0) {
                    $varianceStatus = 'not_invoiced';
                } elseif ($invoicedQuantity < $receivedQuantity) {
                    $varianceStatus = 'under_invoiced';
                } elseif ($invoicedQuantity > $receivedQuantity) {
                    $varianceStatus = 'over_invoiced';
                }
                
                if ($request->filled('variance_status') && $request->variance_status !== 'all') {
                    if ($varianceStatus !== $request->variance_status) {
                        continue;
                    }
                }
                
                $item = $grnItem->inventoryItem ?? $grnItem->purchaseOrderItem->item ?? null;
                
                $reportData->push([
                    'grn_id' => $grn->id,
                    'grn_number' => $grn->grn_number ?? ('GRN-' . $grn->id),
                    'grn_date' => $grn->receipt_date,
                    'po_number' => $grn->purchaseOrder->reference ?? $grn->purchaseOrder->order_number ?? ('PO-' . $grn->purchase_order_id),
                    'supplier_name' => $supplierName,
                    'branch_name' => $grn->branch->name ?? 'N/A',
                    'item_id' => $grnItem->inventory_item_id ?? $grnItem->purchaseOrderItem->item_id ?? null,
                    'item_name' => $item->name ?? 'Unknown Item',
                    'item_code' => $item->item_code ?? 'N/A',
                    'received_quantity' => $receivedQuantity,
                    'invoiced_quantity' => $invoicedQuantity,
                    'variance' => $variance,
                    'variance_percentage' => $variancePercentage,
                    'variance_status' => $varianceStatus,
                    'unit_cost' => $grnItem->unit_cost ?? 0,
                    'received_amount' => $receivedQuantity * ($grnItem->unit_cost ?? 0),
                    'invoiced_amount' => $invoicedQuantity * ($grnItem->unit_cost ?? 0),
                    'variance_amount' => $variance * ($grnItem->unit_cost ?? 0),
                ]);
            }
        }
        
        return $reportData;
    }

    /**
     * Export GRN vs Invoice Variance to PDF
     */
    public function exportGrnVariancePdf(Request $request)
    {
        $this->authorize('view purchases');

        $branchId = Auth::user()->branch_id;
        $query = GoodsReceipt::with(['branch', 'items.inventoryItem', 'items.purchaseOrderItem.item', 'purchaseOrder.supplier'])
            ->when($branchId, fn($q)=>$q->where('branch_id', $branchId));

        if ($request->filled('date_from')) {
            $query->whereDate('receipt_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('receipt_date', '<=', $request->date_to);
        }
        if ($request->filled('supplier_id')) {
            $query->whereHas('purchaseOrder', function($q) use ($request) {
                $q->where('supplier_id', $request->supplier_id);
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $grns = $query->orderByDesc('receipt_date')->get();
        $reportData = $this->processGrnVarianceData($grns, $request);
        
        // Calculate summary
        $totalItems = $reportData->count();
        $matched = $reportData->where('variance_status', 'matched')->count();
        $notInvoiced = $reportData->where('variance_status', 'not_invoiced')->count();
        $underInvoiced = $reportData->where('variance_status', 'under_invoiced')->count();
        $overInvoiced = $reportData->where('variance_status', 'over_invoiced')->count();
        $totalReceivedQty = $reportData->sum('received_quantity');
        $totalInvoicedQty = $reportData->sum('invoiced_quantity');
        $totalVariance = $reportData->sum('variance');
        $totalReceivedAmount = $reportData->sum('received_amount');
        $totalInvoicedAmount = $reportData->sum('invoiced_amount');
        $totalVarianceAmount = $reportData->sum('variance_amount');
        
        $supplier = $request->filled('supplier_id') ? Supplier::find($request->supplier_id) : null;
        $company = current_company();
        $branch = $branchId ? Branch::find($branchId) : null;
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : null;
        $status = $request->filled('status') ? $request->status : null;
        $varianceStatus = $request->filled('variance_status') ? $request->variance_status : null;

        $pdf = Pdf::loadView('purchases.reports.exports.grn-variance-pdf', compact(
            'reportData', 'totalItems', 'matched', 'notInvoiced', 'underInvoiced', 'overInvoiced',
            'totalReceivedQty', 'totalInvoicedQty', 'totalVariance',
            'totalReceivedAmount', 'totalInvoicedAmount', 'totalVarianceAmount',
            'supplier', 'company', 'branch', 'dateFrom', 'dateTo', 'status', 'varianceStatus'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('grn-variance-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export GRN vs Invoice Variance to Excel
     */
    public function exportGrnVarianceExcel(Request $request)
    {
        $this->authorize('view purchases');

        $branchId = Auth::user()->branch_id;
        $query = GoodsReceipt::with(['branch', 'items.inventoryItem', 'items.purchaseOrderItem.item', 'purchaseOrder.supplier'])
            ->when($branchId, fn($q)=>$q->where('branch_id', $branchId));

        if ($request->filled('date_from')) {
            $query->whereDate('receipt_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('receipt_date', '<=', $request->date_to);
        }
        if ($request->filled('supplier_id')) {
            $query->whereHas('purchaseOrder', function($q) use ($request) {
                $q->where('supplier_id', $request->supplier_id);
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $grns = $query->orderByDesc('receipt_date')->get();
        $reportData = $this->processGrnVarianceData($grns, $request);
        
        $supplier = $request->filled('supplier_id') ? Supplier::find($request->supplier_id) : null;
        $company = current_company();
        $branch = $branchId ? Branch::find($branchId) : null;
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : null;
        $status = $request->filled('status') ? $request->status : null;
        $varianceStatus = $request->filled('variance_status') ? $request->variance_status : null;

        return Excel::download(new \App\Exports\GrnVarianceExport($reportData, $dateFrom, $dateTo, $branch, $supplier, $status, $varianceStatus, $company), 
            'grn-variance-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function invoiceRegister(Request $request)
    {
        $this->authorize('view purchases');
        
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from')) : Carbon::now()->startOfMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to')) : Carbon::now()->endOfMonth();
        
        // Use session branch as default, fallback to user's branch
        $sessionBranchId = session('branch_id');
        $userBranchId = Auth::user()->branch_id;
        
        // Get all user's accessible branches
        $userBranches = Branch::where('company_id', Auth::user()->company_id)->get();
        
        // If user has more than 1 branch, default to 'all', otherwise use assigned branch
        if ($userBranches->count() > 1) {
            $defaultBranchId = 'all';
        } else {
            $defaultBranchId = $sessionBranchId ?? $userBranchId;
        }
        
        $branchId = $request->get('branch_id', $defaultBranchId);
        $status = $request->get('status');
        $supplierId = $request->get('supplier_id');

        $query = PurchaseInvoice::with(['supplier', 'branch', 'items.grnItem.purchaseOrderItem.purchaseOrder'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo]);

        // Apply branch filtering
        $this->applyBranchFilter($query, $branchId);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->get();

        // Process invoices to get PO numbers
        $invoices = $invoices->map(function($invoice) {
            // Get PO numbers from invoice items
            $poNumbers = $invoice->items->map(function($item) {
                if ($item->grnItem && $item->grnItem->purchaseOrderItem) {
                    $po = $item->grnItem->purchaseOrderItem->purchaseOrder;
                    return $po->order_number ?? ('PO-' . $po->id);
                }
                return null;
            })->filter()->unique()->values()->implode(', ');
            
            $invoice->po_number = $poNumbers ?: '-';
            return $invoice;
        });

        $summary = [
            'total_invoices' => $invoices->count(),
            'total_value' => $invoices->sum('total_amount'),
            'total_subtotal' => $invoices->sum('subtotal'),
            'total_vat' => $invoices->sum('vat_amount'),
            'total_discount' => $invoices->sum('discount_amount'),
            'total_paid' => $invoices->sum(function($invoice) {
                return $invoice->total_paid;
            }),
            'total_outstanding' => $invoices->sum(function($invoice) {
                return $invoice->outstanding_amount;
            })
        ];

        // Prepare branches for filter dropdown
        if ($userBranches->count() > 1) {
            $allBranchesOption = (object) [
                'id' => 'all',
                'name' => 'All Branches',
                'company_id' => Auth::user()->company_id
            ];
            $branches = $userBranches->prepend($allBranchesOption);
        } else {
            $branches = $userBranches;
        }
        
        // Get suppliers for filter
        $suppliers = Supplier::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('purchases.reports.invoice-register', compact(
            'invoices', 'summary', 'branches', 'suppliers', 'dateFrom', 'dateTo', 'branchId', 'status', 'supplierId'
        ));
    }
    
    /**
     * Apply branch filtering to a query
     */
    private function applyBranchFilter($query, $branchId)
    {
        // Handle "all" branches selection
        if ($branchId === 'all') {
            $user = Auth::user();
            $companyBranches = Branch::where('company_id', $user->company_id)->pluck('id')->toArray();
            $query->whereIn('branch_id', $companyBranches);
        } else {
            $query->where('branch_id', $branchId);
        }
        
        return $query;
    }
    
    /**
     * Export Supplier Invoice Register to PDF
     */
    public function exportInvoiceRegisterPdf(Request $request)
    {
        $this->authorize('view purchases');
        
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from')) : Carbon::now()->startOfMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to')) : Carbon::now()->endOfMonth();
        
        // Use session branch as default, fallback to user's branch
        $sessionBranchId = session('branch_id');
        $userBranchId = Auth::user()->branch_id;
        
        // Get all user's accessible branches
        $userBranches = Branch::where('company_id', Auth::user()->company_id)->get();
        
        // If user has more than 1 branch, default to 'all', otherwise use assigned branch
        if ($userBranches->count() > 1) {
            $defaultBranchId = 'all';
        } else {
            $defaultBranchId = $sessionBranchId ?? $userBranchId;
        }
        
        $branchId = $request->get('branch_id', $defaultBranchId);
        $status = $request->get('status');
        $supplierId = $request->get('supplier_id');

        $query = PurchaseInvoice::with(['supplier', 'branch', 'creator'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo]);

        $this->applyBranchFilter($query, $branchId);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->get();
        
        $summary = [
            'total_invoices' => $invoices->count(),
            'total_value' => $invoices->sum('total_amount'),
            'total_subtotal' => $invoices->sum('subtotal'),
            'total_vat' => $invoices->sum('vat_amount'),
            'total_discount' => $invoices->sum('discount_amount'),
            'total_paid' => $invoices->sum(function($invoice) {
                return $invoice->total_paid;
            }),
            'total_outstanding' => $invoices->sum(function($invoice) {
                return $invoice->outstanding_amount;
            })
        ];

        $branch = $branchId !== 'all' ? Branch::find($branchId) : null;
        $supplier = $supplierId ? Supplier::find($supplierId) : null;
        $company = current_company();

        $pdf = Pdf::loadView('purchases.reports.exports.invoice-register-pdf', compact(
            'invoices', 'summary', 'dateFrom', 'dateTo', 'branch', 'supplier', 'status', 'company'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('supplier-invoice-register-report-' . now()->format('Y-m-d') . '.pdf');
    }
    
    /**
     * Export Supplier Invoice Register to Excel
     */
    public function exportInvoiceRegisterExcel(Request $request)
    {
        $this->authorize('view purchases');
        
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from')) : Carbon::now()->startOfMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to')) : Carbon::now()->endOfMonth();
        
        // Use session branch as default, fallback to user's branch
        $sessionBranchId = session('branch_id');
        $userBranchId = Auth::user()->branch_id;
        
        // Get all user's accessible branches
        $userBranches = Branch::where('company_id', Auth::user()->company_id)->get();
        
        // If user has more than 1 branch, default to 'all', otherwise use assigned branch
        if ($userBranches->count() > 1) {
            $defaultBranchId = 'all';
        } else {
            $defaultBranchId = $sessionBranchId ?? $userBranchId;
        }
        
        $branchId = $request->get('branch_id', $defaultBranchId);
        $status = $request->get('status');
        $supplierId = $request->get('supplier_id');

        $query = PurchaseInvoice::with(['supplier', 'branch', 'creator'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo]);

        $this->applyBranchFilter($query, $branchId);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->get();

        $branch = $branchId !== 'all' ? Branch::find($branchId) : null;
        $supplier = $supplierId ? Supplier::find($supplierId) : null;
        $company = current_company();

        return Excel::download(new \App\Exports\SupplierInvoiceRegisterExport($invoices, $dateFrom, $dateTo, $branch, $supplier, $status, $company), 
            'supplier-invoice-register-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Payables Aging Report
     * Groups supplier invoice balances into aging buckets
     */
    public function payablesAging(Request $request)
    {
        $this->authorize('view purchases');
        
        $branchId = $request->get('branch_id', Auth::user()->branch_id);
        $supplierId = $request->get('supplier_id');
        $asOfDate = $request->get('as_of_date', Carbon::now()->format('Y-m-d'));
        $viewType = $request->get('view_type', 'summary'); // summary | detailed | trend
        $bucket = $request->get('bucket', '0-30'); // 0-30 | 31-60 | 61-90 | 90+
        if (!in_array($bucket, ['0-30','31-60','61-90','90+'])) {
            $bucket = '0-30';
        }

        $query = PurchaseInvoice::with(['supplier'])
            ->where('status', '!=', 'cancelled')
            ->where('invoice_date', '<=', $asOfDate);
        
        $this->applyBranchFilter($query, $branchId);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $outstandingInvoices = $query->get()->map(function ($invoice) use ($asOfDate) {
            $asOf = Carbon::parse($asOfDate);
            $invoiceDate = Carbon::parse($invoice->invoice_date);
            // Determine due date: prefer stored due_date, else payment_days, else +30
            $dueDate = !empty($invoice->due_date)
                ? Carbon::parse($invoice->due_date)
                : $invoiceDate->copy()->addDays($invoice->payment_days ?? 30);
            // Days overdue (0 if not yet due)
            $daysOverdue = $dueDate->lt($asOf) ? $dueDate->diffInDays($asOf) : 0;
            // Outstanding amount
            $outstandingAmount = $invoice->outstanding_amount ?? 0;
            $invoice->due_date = $dueDate;
            $invoice->days_overdue = $daysOverdue;
            $invoice->aging_bucket = $this->getAgingBucket($daysOverdue);
            $invoice->outstanding_amount = (float)$outstandingAmount;
            return $invoice;
        })->filter(function ($inv) {
            return (($inv->outstanding_amount ?? 0) > 0);
        });

        // Executive summary buckets
        $buckets = ['0-30','31-60','61-90','90+'];
        $agingSummary = collect($buckets)->mapWithKeys(function($b) use ($outstandingInvoices) {
            // Include ONLY overdue items (days_overdue > 0)
            $filtered = $outstandingInvoices
                ->where('aging_bucket', $b)
                ->filter(function($inv){ return ($inv->days_overdue ?? 0) > 0; });
            return [$b => [
                'count' => $filtered->count(),
                'total_amount' => (float)$filtered->sum('outstanding_amount'),
                'invoices' => $filtered,
            ]];
        });
        // Totals that reflect the current filtered summary (overdue-only)
        $summaryTotalAmount = (float)collect($agingSummary)->sum('total_amount');
        $summaryTotalCount = (int)collect($agingSummary)->sum('count');
        // Overall outstanding (including current, not just overdue)
        $totalOutstanding = (float)$outstandingInvoices->sum('outstanding_amount');

        // Detailed: build all buckets grouped by supplier, ONLY overdue (days_overdue > 0)
        $bucketLabels = ['0-30' => '0 – 30 Days', '31-60' => '31 – 60 Days', '61-90' => '61 – 90 Days', '90+' => 'Over 90 Days'];
        $detailedAllBuckets = collect($bucketLabels)->map(function($label, $key) use ($outstandingInvoices) {
            $bucketInvoices = $outstandingInvoices->where('aging_bucket', $key)
                ->filter(function($inv){ return ($inv->days_overdue ?? 0) > 0; });
            $groups = $bucketInvoices
                ->groupBy(function($inv){ return $inv->supplier->name ?? 'Unknown'; })
                ->map(function($group, $supplierName) {
                    $subtotal = (float)$group->sum('outstanding_amount');
                    return [
                        'supplier_name' => $supplierName,
                        'invoices' => $group->map(function($inv){
                            return [
                                'supplier_name' => $inv->supplier->name ?? 'Unknown',
                                'invoice_number' => $inv->invoice_number,
                                'invoice_date' => $inv->invoice_date,
                                'due_date' => $inv->due_date,
                                'outstanding_amount' => (float)$inv->outstanding_amount,
                                'days_overdue' => $inv->days_overdue,
                                'status' => $inv->status,
                            ];
                        }),
                        'subtotal' => $subtotal,
                    ];
                })->values();
            $bucketTotal = (float)$bucketInvoices->sum('outstanding_amount');
            return [
                'label' => $label,
                'key' => $key,
                'groups' => $groups,
                'bucket_total' => $bucketTotal,
            ];
        })->values();

        // Trend comparison: current month vs previous month by bucket
        $prevMonthEnd = Carbon::parse($asOfDate)->subMonth()->endOfMonth();
        $prevMonthStart = $prevMonthEnd->copy()->startOfMonth();

        $prevInvoices = PurchaseInvoice::with(['supplier'])
            ->where('status', '!=', 'cancelled')
            ->where('invoice_date', '<=', $prevMonthEnd);
        $this->applyBranchFilter($prevInvoices, $branchId);
        if ($supplierId) { $prevInvoices->where('supplier_id', $supplierId); }
        $prevAging = $prevInvoices->get()->map(function ($inv) use ($prevMonthEnd) {
            $asOf = Carbon::parse($prevMonthEnd);
            $invoiceDate = Carbon::parse($inv->invoice_date);
            $dueDate = !empty($inv->due_date)
                ? Carbon::parse($inv->due_date)
                : $invoiceDate->copy()->addDays($inv->payment_days ?? 30);
            $daysOverdue = $dueDate->lt($asOf) ? $dueDate->diffInDays($asOf) : 0;
            $outstandingAmount = $inv->outstanding_amount ?? 0;
            return [
                'aging_bucket' => $this->getAgingBucket($daysOverdue),
                'outstanding_amount' => $outstandingAmount,
                'days_overdue' => $daysOverdue,
            ];
        })->filter(function ($i) { return (($i['outstanding_amount'] ?? 0) > 0) && (($i['days_overdue'] ?? 0) > 0); });

        // Use overdue-only for current buckets
        $overdueOnly = $outstandingInvoices->filter(function($inv){ return ($inv->days_overdue ?? 0) > 0; });
        $currentByBucket = collect($buckets)->mapWithKeys(function($b) use ($overdueOnly) {
            return [$b => (float)$overdueOnly->where('aging_bucket', $b)->sum('outstanding_amount')];
        });
        $prevByBucket = collect($buckets)->mapWithKeys(function($b) use ($prevAging) {
            return [$b => (float)$prevAging->where('aging_bucket', $b)->sum('outstanding_amount')];
        });
        $trend = collect($buckets)->map(function($b) use ($currentByBucket, $prevByBucket) {
            $current = $currentByBucket[$b] ?? 0;
            $prev = $prevByBucket[$b] ?? 0;
            $change = $current - $prev;
            $pctChange = $prev > 0 ? ($change / $prev) * 100 : ($current > 0 ? 100 : 0);
            return [
                'current' => $current,
                'previous' => $prev,
                'change' => $change,
                'pct_change' => $pctChange,
            ];
        });

        // Get branches and suppliers for filters
        $userBranches = Branch::where('company_id', Auth::user()->company_id)->get();
        if ($userBranches->count() > 1) {
            $allBranchesOption = (object) [
                'id' => 'all',
                'name' => 'All Branches',
                'company_id' => Auth::user()->company_id
            ];
            $branches = $userBranches->prepend($allBranchesOption);
        } else {
            $branches = $userBranches;
        }
        
        $suppliers = Supplier::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('purchases.reports.payables-aging', compact(
            'agingSummary', 'summaryTotalAmount', 'summaryTotalCount', 'totalOutstanding',
            'detailedAllBuckets', 'trend', 'asOfDate', 'branchId', 'supplierId', 'viewType', 'bucket',
            'branches', 'suppliers'
        ));
    }

    /**
     * Helper method to determine aging bucket
     */
    private function getAgingBucket($daysOverdue)
    {
        if ($daysOverdue <= 30) return '0-30';
        if ($daysOverdue <= 60) return '31-60';
        if ($daysOverdue <= 90) return '61-90';
        return '90+';
    }

    /**
     * Export Payables Aging to PDF
     */
    public function exportPayablesAgingPdf(Request $request)
    {
        $this->authorize('view purchases');
        
        $branchId = $request->get('branch_id', Auth::user()->branch_id);
        $supplierId = $request->get('supplier_id');
        $asOfDate = $request->get('as_of_date', Carbon::now()->format('Y-m-d'));
        $viewType = $request->get('view_type', 'summary');

        $query = PurchaseInvoice::with(['supplier'])
            ->where('status', '!=', 'cancelled')
            ->where('invoice_date', '<=', $asOfDate);
        
        $this->applyBranchFilter($query, $branchId);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $outstandingInvoices = $query->get()->map(function ($invoice) use ($asOfDate) {
            $asOf = Carbon::parse($asOfDate);
            $invoiceDate = Carbon::parse($invoice->invoice_date);
            $dueDate = !empty($invoice->due_date)
                ? Carbon::parse($invoice->due_date)
                : $invoiceDate->copy()->addDays($invoice->payment_days ?? 30);
            $daysOverdue = $dueDate->lt($asOf) ? $dueDate->diffInDays($asOf) : 0;
            $outstandingAmount = $invoice->outstanding_amount ?? 0;
            
            return [
                'supplier_name' => $invoice->supplier->name ?? 'Unknown',
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date,
                'due_date' => $dueDate,
                'total_amount' => (float)$invoice->total_amount,
                'outstanding_amount' => (float)$outstandingAmount,
                'days_overdue' => $daysOverdue,
                'aging_bucket' => $this->getAgingBucket($daysOverdue),
                'status' => $invoice->status,
            ];
        })->filter(function ($item) {
            return $item['outstanding_amount'] > 0;
        });

        $branch = $branchId !== 'all' ? Branch::find($branchId) : null;
        $supplier = $supplierId ? Supplier::find($supplierId) : null;
        $company = current_company();

        // Build executive summary buckets (overdue-only)
        $buckets = ['0-30','31-60','61-90','90+'];
        $overdueOnly = collect($outstandingInvoices)->filter(function ($i) { return ($i['days_overdue'] ?? 0) > 0; });
        $summary = collect($buckets)->mapWithKeys(function($b) use ($overdueOnly) {
            $filtered = $overdueOnly->where('aging_bucket', $b);
            return [$b => [
                'count' => $filtered->count(),
                'amount' => (float)$filtered->sum('outstanding_amount'),
            ]];
        });
        $totalOutstanding = (float)$overdueOnly->sum('outstanding_amount');
        $summary = $summary->map(function($row) use ($totalOutstanding) {
            $row['pct'] = $totalOutstanding > 0 ? ($row['amount'] / $totalOutstanding) * 100 : 0;
            return $row;
        });

        // Detailed ALL buckets grouped by supplier (overdue only)
        $bucketLabels = ['0-30' => '0 – 30 Days', '31-60' => '31 – 60 Days', '61-90' => '61 – 90 Days', '90+' => 'Over 90 Days'];
        $detailedAllBuckets = collect($bucketLabels)->map(function($label, $key) use ($overdueOnly) {
            $bucketInvoices = $overdueOnly->where('aging_bucket', $key);
            $groups = $bucketInvoices
                ->groupBy('supplier_name')
                ->map(function($invoices, $supplierName) {
                    $subtotal = (float)collect($invoices)->sum('outstanding_amount');
                    return [
                        'supplier_name' => $supplierName,
                        'invoices' => $invoices,
                        'subtotal' => $subtotal,
                    ];
                })->values();
            $bucketTotal = (float)$bucketInvoices->sum('outstanding_amount');
            return [
                'label' => $label,
                'key' => $key,
                'groups' => $groups,
                'bucket_total' => $bucketTotal,
            ];
        })->values();

        // Trend comparison: current month vs previous month by bucket
        $prevMonthEnd = Carbon::parse($asOfDate)->subMonth()->endOfMonth();
        $prevInvoices = PurchaseInvoice::with(['supplier'])
            ->where('status', '!=', 'cancelled')
            ->where('invoice_date', '<=', $prevMonthEnd);
        $this->applyBranchFilter($prevInvoices, $branchId);
        if ($supplierId) { $prevInvoices->where('supplier_id', $supplierId); }
        $prevAging = $prevInvoices->get()->map(function ($inv) use ($prevMonthEnd) {
            $asOf = Carbon::parse($prevMonthEnd);
            $invoiceDate = Carbon::parse($inv->invoice_date);
            $dueDate = !empty($inv->due_date)
                ? Carbon::parse($inv->due_date)
                : $invoiceDate->copy()->addDays($inv->payment_days ?? 30);
            $daysOverdue = $dueDate->lt($asOf) ? $dueDate->diffInDays($asOf) : 0;
            $outstandingAmount = $inv->outstanding_amount ?? 0;
            return [
                'aging_bucket' => $this->getAgingBucket($daysOverdue),
                'outstanding_amount' => $outstandingAmount,
                'days_overdue' => $daysOverdue,
            ];
        })->filter(function ($i) { return (($i['outstanding_amount'] ?? 0) > 0) && (($i['days_overdue'] ?? 0) > 0); });

        $currentByBucket = collect($buckets)->mapWithKeys(function($b) use ($overdueOnly) {
            return [$b => (float)$overdueOnly->where('aging_bucket', $b)->sum('outstanding_amount')];
        });
        $prevByBucket = collect($buckets)->mapWithKeys(function($b) use ($prevAging) {
            return [$b => (float)$prevAging->where('aging_bucket', $b)->sum('outstanding_amount')];
        });
        $trend = collect($buckets)->map(function($b) use ($currentByBucket, $prevByBucket) {
            $current = $currentByBucket[$b] ?? 0;
            $prev = $prevByBucket[$b] ?? 0;
            $change = $current - $prev;
            $pctChange = $prev > 0 ? ($change / $prev) * 100 : ($current > 0 ? 100 : 0);
            return [
                'current' => $current,
                'previous' => $prev,
                'change' => $change,
                'pct_change' => $pctChange,
            ];
        });

        $pdf = Pdf::loadView('purchases.reports.exports.payables-aging-pdf', compact(
            'summary', 'totalOutstanding', 'detailedAllBuckets', 'trend', 'asOfDate', 'branch', 'supplier', 'company', 'viewType'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('payables-aging-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export Payables Aging to Excel
     */
    public function exportPayablesAgingExcel(Request $request)
    {
        $this->authorize('view purchases');
        
        $branchId = $request->get('branch_id', Auth::user()->branch_id);
        $supplierId = $request->get('supplier_id');
        $asOfDate = $request->get('as_of_date', Carbon::now()->format('Y-m-d'));
        $viewType = $request->get('view_type', 'summary');

        $query = PurchaseInvoice::with(['supplier'])
            ->where('status', '!=', 'cancelled')
            ->where('invoice_date', '<=', $asOfDate);
        
        $this->applyBranchFilter($query, $branchId);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $outstandingInvoices = $query->get()->map(function ($invoice) use ($asOfDate) {
            $asOf = Carbon::parse($asOfDate);
            $invoiceDate = Carbon::parse($invoice->invoice_date);
            $dueDate = !empty($invoice->due_date)
                ? Carbon::parse($invoice->due_date)
                : $invoiceDate->copy()->addDays($invoice->payment_days ?? 30);
            $daysOverdue = $dueDate->lt($asOf) ? $dueDate->diffInDays($asOf) : 0;
            $outstandingAmount = $invoice->outstanding_amount ?? 0;
            
            return [
                'supplier_name' => $invoice->supplier->name ?? 'Unknown',
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date,
                'due_date' => $dueDate,
                'outstanding_amount' => (float)$outstandingAmount,
                'days_overdue' => $daysOverdue,
                'aging_bucket' => $this->getAgingBucket($daysOverdue),
            ];
        })->filter(function ($item) {
            return $item['outstanding_amount'] > 0;
        });

        $branch = $branchId !== 'all' ? Branch::find($branchId) : null;
        $supplier = $supplierId ? Supplier::find($supplierId) : null;
        $company = current_company();

        return Excel::download(new \App\Exports\PayablesAgingExport($outstandingInvoices, $asOfDate, $branch, $supplier, $company), 
            'payables-aging-report-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Outstanding Supplier Invoices Report
     * Shows unpaid and partially paid invoices with outstanding balances
     */
    public function outstandingInvoices(Request $request)
    {
        $this->authorize('view purchases');
        
        $branchId = $request->get('branch_id', Auth::user()->branch_id);
        $supplierId = $request->get('supplier_id');
        $status = $request->get('status', 'all');
        
        $query = PurchaseInvoice::with(['supplier'])
            ->where('status', '!=', 'cancelled');
        
        $this->applyBranchFilter($query, $branchId);
        
        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }
        
        $invoices = $query->get()->map(function ($invoice) {
            $paidAmount = (float) $invoice->total_paid;
            $invoiceAmount = (float) $invoice->total_amount;
            $creditNotes = (float) 0; // TODO: Get credit notes applied to this invoice
            $outstandingBalance = max(0, $invoiceAmount - $paidAmount - $creditNotes);
            
            return [
                'supplier_name' => $invoice->supplier->name ?? 'Unknown',
                'invoice_no' => $invoice->invoice_number,
                'invoice_amount' => $invoiceAmount,
                'paid_amount' => $paidAmount,
                'credit_notes' => $creditNotes,
                'outstanding_balance' => $outstandingBalance,
                'due_date' => $invoice->due_date ?? $invoice->invoice_date->addDays(30),
                'invoice_date' => $invoice->invoice_date,
            ];
        })->filter(function ($inv) {
            return $inv['outstanding_balance'] > 0;
        })->sortByDesc('due_date');
        
        $summary = [
            'total_invoices' => $invoices->count(),
            'total_invoice_amount' => $invoices->sum('invoice_amount'),
            'total_paid_amount' => $invoices->sum('paid_amount'),
            'total_credit_notes' => $invoices->sum('credit_notes'),
            'total_outstanding' => $invoices->sum('outstanding_balance'),
        ];
        
        $suppliers = Supplier::where('company_id', Auth::user()->company_id)
            ->orderBy('name')->get(['id', 'name']);
        
        $userBranches = Branch::where('company_id', Auth::user()->company_id)->get();
        if ($userBranches->count() > 1) {
            $allBranchesOption = (object) ['id' => 'all', 'name' => 'All Branches', 'company_id' => Auth::user()->company_id];
            $branches = $userBranches->prepend($allBranchesOption);
        } else {
            $branches = $userBranches;
        }
        
        return view('purchases.reports.outstanding-invoices', compact(
            'invoices', 'summary', 'suppliers', 'branches', 'branchId', 'supplierId', 'status'
        ));
    }

    /**
     * Paid Supplier Invoice Report
     * Shows fully paid invoices
     */
    public function paidInvoices(Request $request)
    {
        $this->authorize('view purchases');
        
        $branchId = $request->get('branch_id', Auth::user()->branch_id);
        $supplierId = $request->get('supplier_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        
        $query = PurchaseInvoice::with(['supplier'])
            ->where('status', '!=', 'cancelled');
        
        $this->applyBranchFilter($query, $branchId);
        
        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }
        
        if ($dateFrom) {
            $query->whereDate('invoice_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('invoice_date', '<=', $dateTo);
        }
        
        $invoices = $query->get()->filter(function ($invoice) {
            $paidAmount = (float) $invoice->total_paid;
            $invoiceAmount = (float) $invoice->total_amount;
            return $paidAmount >= $invoiceAmount && $invoiceAmount > 0;
        })->map(function ($invoice) {
            $payments = Payment::where('supplier_id', $invoice->supplier_id)
                ->where('reference_type', 'purchase_invoice')
                ->where('reference_number', $invoice->invoice_number)
                ->orderBy('date')
                ->get();
            
            $latestPayment = $payments->last();
            
            return [
                'supplier_name' => $invoice->supplier->name ?? 'Unknown',
                'invoice_no' => $invoice->invoice_number,
                'invoice_amount' => (float) $invoice->total_amount,
                'payment_date' => $latestPayment ? $latestPayment->date : $invoice->invoice_date,
                'payment_method' => $latestPayment && $latestPayment->bankAccount 
                    ? $latestPayment->bankAccount->name 
                    : 'Cash',
                'paid_amount' => (float) $invoice->total_paid,
            ];
        })->sortByDesc('payment_date');
        
        $summary = [
            'total_invoices' => $invoices->count(),
            'total_paid_value' => $invoices->sum('paid_amount'),
        ];
        
        $suppliers = Supplier::where('company_id', Auth::user()->company_id)
            ->orderBy('name')->get(['id', 'name']);
        
        $userBranches = Branch::where('company_id', Auth::user()->company_id)->get();
        if ($userBranches->count() > 1) {
            $allBranchesOption = (object) ['id' => 'all', 'name' => 'All Branches', 'company_id' => Auth::user()->company_id];
            $branches = $userBranches->prepend($allBranchesOption);
        } else {
            $branches = $userBranches;
        }
        
        return view('purchases.reports.paid-invoices', compact(
            'invoices', 'summary', 'suppliers', 'branches', 'branchId', 'supplierId', 'dateFrom', 'dateTo'
        ));
    }

    public function supplierCreditNotes(Request $request)
    {
        $this->authorize('view purchases');
        return view('purchases.reports.supplier-credit-notes');
    }

    /**
     * Supplier Invoice Variance Report (PO vs Invoice)
     * Compares Purchase Order quantities and prices with Invoice quantities and prices
     */
    public function poInvoiceVariance(Request $request)
    {
        $this->authorize('view purchases');

        $branchId = $request->get('branch_id', Auth::user()->branch_id);
        $supplierId = $request->get('supplier_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = PurchaseInvoice::with(['supplier', 'items.grnItem.purchaseOrderItem.purchaseOrder', 'items.inventoryItem'])
            ->where('status', '!=', 'cancelled');

        $this->applyBranchFilter($query, $branchId);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }
        if ($dateFrom) {
            $query->whereDate('invoice_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('invoice_date', '<=', $dateTo);
        }

        $invoices = $query->orderByDesc('invoice_date')->get();

        $reportData = collect();
        
        foreach ($invoices as $invoice) {
            $supplierName = $invoice->supplier->name ?? 'Unknown';
            
            foreach ($invoice->items as $invoiceItem) {
                // Try to get PO item through GRN item
                $poItem = null;
                $poNumber = '-';
                
                if ($invoiceItem->grnItem && $invoiceItem->grnItem->purchaseOrderItem) {
                    $poItem = $invoiceItem->grnItem->purchaseOrderItem;
                    $po = $poItem->purchaseOrder;
                    $poNumber = $po->order_number ?? ('PO-' . $po->id);
                }
                
                if (!$poItem) {
                    continue; // Skip if no PO item found
                }
                
                $poQty = $poItem->quantity ?? 0;
                $invoiceQty = $invoiceItem->quantity ?? 0;
                $qtyVariance = $invoiceQty - $poQty;
                
                $poUnitCost = $poItem->cost_price ?? 0;
                $invoiceUnitCost = $invoiceItem->unit_cost ?? 0;
                $priceVariance = $invoiceUnitCost - $poUnitCost;
                $valueVariance = ($invoiceQty * $invoiceUnitCost) - ($poQty * $poUnitCost);
                
                $reportData->push([
                    'supplier_name' => $supplierName,
                    'po_number' => $poNumber,
                    'invoice_no' => $invoice->invoice_number,
                    'po_qty' => $poQty,
                    'invoice_qty' => $invoiceQty,
                    'po_unit_cost' => $poUnitCost,
                    'invoice_unit_cost' => $invoiceUnitCost,
                    'qty_variance' => $qtyVariance,
                    'price_variance' => $priceVariance,
                    'value_variance' => $valueVariance,
                ]);
            }
        }

        $summary = [
            'total_items' => $reportData->count(),
            'total_qty_variance' => $reportData->sum('qty_variance'),
            'total_value_variance' => $reportData->sum('value_variance'),
        ];

        $suppliers = Supplier::where('company_id', Auth::user()->company_id)
            ->when($branchId && $branchId !== 'all', fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')->get(['id', 'name']);

        $userBranches = Branch::where('company_id', Auth::user()->company_id)->get();
        if ($userBranches->count() > 1) {
            $allBranchesOption = (object) [
                'id' => 'all',
                'name' => 'All Branches',
                'company_id' => Auth::user()->company_id
            ];
            $branches = $userBranches->prepend($allBranchesOption);
        } else {
            $branches = $userBranches;
        }

        return view('purchases.reports.po-invoice-variance', compact(
            'reportData', 'summary', 'suppliers', 'branches', 'branchId', 'supplierId', 'dateFrom', 'dateTo'
        ));
    }

    /**
     * Purchase Analysis by Supplier
     * Shows total value, PO count, avg order value, contribution %
     */
    public function purchaseBySupplier(Request $request)
    {
        $this->authorize('view purchases');
        
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from')) : Carbon::now()->startOfMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to')) : Carbon::now()->endOfMonth();
        
        $branchId = $request->get('branch_id', Auth::user()->branch_id);

        $query = PurchaseOrder::with(['supplier'])
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');

        $this->applyBranchFilter($query, $branchId);

        $orders = $query->get();

        // Group by supplier
        $supplierData = $orders->groupBy('supplier_id')->map(function($supplierOrders, $supplierId) use ($orders) {
            $supplier = $supplierOrders->first()->supplier ?? null;
            if (!$supplier) return null;
            
            $totalValue = $supplierOrders->sum('total_amount');
            $poCount = $supplierOrders->count();
            $avgOrderValue = $poCount > 0 ? $totalValue / $poCount : 0;
            $totalAllOrders = $orders->sum('total_amount');
            $contributionPercent = $totalAllOrders > 0 ? ($totalValue / $totalAllOrders) * 100 : 0;
            
            return [
                'supplier_name' => $supplier->name,
                'total_value' => $totalValue,
                'po_count' => $poCount,
                'avg_order_value' => $avgOrderValue,
                'contribution_percent' => $contributionPercent,
            ];
        })->filter()->sortByDesc('total_value')->values();

        $summary = [
            'total_suppliers' => $supplierData->count(),
            'total_orders' => $orders->count(),
            'total_value' => $orders->sum('total_amount'),
        ];

        $userBranches = Branch::where('company_id', Auth::user()->company_id)->get();
        if ($userBranches->count() > 1) {
            $allBranchesOption = (object) [
                'id' => 'all',
                'name' => 'All Branches',
                'company_id' => Auth::user()->company_id
            ];
            $branches = $userBranches->prepend($allBranchesOption);
        } else {
            $branches = $userBranches;
        }

        return view('purchases.reports.purchase-by-supplier', compact(
            'supplierData', 'summary', 'branches', 'dateFrom', 'dateTo', 'branchId'
        ));
    }

    /**
     * Purchase Returns Report
     * Shows returned items with quantities and reasons
     */
    public function purchaseReturnsReport(Request $request)
    {
        $this->authorize('view purchases');
        
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from')) : Carbon::now()->startOfMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to')) : Carbon::now()->endOfMonth();
        
        $branchId = $request->get('branch_id', Auth::user()->branch_id);
        $supplierId = $request->get('supplier_id');

        $query = DebitNote::with(['supplier', 'items.inventoryItem', 'purchaseInvoice'])
            ->where('type', 'return')
            ->whereBetween('debit_note_date', [$dateFrom, $dateTo]);

        $this->applyBranchFilter($query, $branchId);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $debitNotes = $query->orderByDesc('debit_note_date')->get();

        // Build item-level report data
        $reportData = collect();
        foreach ($debitNotes as $debitNote) {
            foreach ($debitNote->items as $item) {
                $reportData->push([
                    'return_date' => $debitNote->debit_note_date,
                    'return_no' => $debitNote->debit_note_number,
                    'supplier_name' => $debitNote->supplier->name ?? 'N/A',
                    'invoice_no' => $debitNote->purchaseInvoice->invoice_number ?? '-',
                    'item_code' => $item->item_code ?? ($item->inventoryItem->item_code ?? 'N/A'),
                    'item_description' => $item->item_name ?? ($item->description ?? 'N/A'),
                    'quantity' => $item->quantity ?? 0,
                    'unit_cost' => $item->unit_cost ?? 0,
                    'return_value' => $item->line_total ?? 0,
                    'reason' => $debitNote->reason ?? '-',
                    'return_condition' => $item->return_condition ?? 'resellable',
                ]);
            }
        }

        $summary = [
            'total_returns' => $debitNotes->count(),
            'total_items' => $reportData->count(),
            'total_quantity' => $reportData->sum('quantity'),
            'total_value' => $reportData->sum('return_value'),
        ];

        $suppliers = Supplier::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $userBranches = Branch::where('company_id', Auth::user()->company_id)->get();
        if ($userBranches->count() > 1) {
            $allBranchesOption = (object) [
                'id' => 'all',
                'name' => 'All Branches',
                'company_id' => Auth::user()->company_id
            ];
            $branches = $userBranches->prepend($allBranchesOption);
        } else {
            $branches = $userBranches;
        }

        return view('purchases.reports.purchase-returns', compact(
            'reportData', 'summary', 'suppliers', 'branches', 'dateFrom', 'dateTo', 'branchId', 'supplierId'
        ));
    }

    /**
     * Purchase Analysis by Item/Category
     * Shows total qty, total value, avg unit cost by category
     */
    public function purchaseByItem(Request $request)
    {
        $this->authorize('view purchases');
        
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from')) : Carbon::now()->startOfMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to')) : Carbon::now()->endOfMonth();
        
        $branchId = $request->get('branch_id', Auth::user()->branch_id);
        $categoryId = $request->get('category_id');

        $query = PurchaseInvoiceItem::with(['inventoryItem.category', 'invoice'])
            ->whereHas('invoice', function($q) use ($dateFrom, $dateTo, $branchId) {
                $q->whereBetween('invoice_date', [$dateFrom, $dateTo])
                  ->where('status', '!=', 'cancelled');
                $this->applyBranchFilter($q, $branchId);
            })
            ->whereNotNull('inventory_item_id');

        if ($categoryId) {
            $query->whereHas('inventoryItem', function($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $items = $query->get();

        // Group by category
        $categoryData = $items->groupBy(function($item) {
            return $item->inventoryItem->category->name ?? 'Uncategorized';
        })->map(function($categoryItems, $categoryName) {
            $totalQty = $categoryItems->sum('quantity');
            $totalValue = $categoryItems->sum('line_total');
            $avgUnitCost = $totalQty > 0 ? $totalValue / $totalQty : 0;
            
            return [
                'category_name' => $categoryName,
                'total_qty' => $totalQty,
                'total_value' => $totalValue,
                'avg_unit_cost' => $avgUnitCost,
            ];
        })->sortByDesc('total_value')->values();

        $summary = [
            'total_categories' => $categoryData->count(),
            'total_qty' => $categoryData->sum('total_qty'),
            'total_value' => $categoryData->sum('total_value'),
        ];

        // Get categories for filter
        $categories = \App\Models\Inventory\Category::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $userBranches = Branch::where('company_id', Auth::user()->company_id)->get();
        if ($userBranches->count() > 1) {
            $allBranchesOption = (object) [
                'id' => 'all',
                'name' => 'All Branches',
                'company_id' => Auth::user()->company_id
            ];
            $branches = $userBranches->prepend($allBranchesOption);
        } else {
            $branches = $userBranches;
        }

        return view('purchases.reports.purchase-by-item', compact(
            'categoryData', 'summary', 'categories', 'branches', 'dateFrom', 'dateTo', 'branchId', 'categoryId'
        ));
    }

    /**
     * Purchase Forecast Report
     * Forecasts future purchases based on historical usage
     */
    public function purchaseForecast(Request $request)
    {
        $this->authorize('view purchases');
        
        $months = $request->get('months', 6); // Forecast period in months
        $branchId = $request->get('branch_id', Auth::user()->branch_id);
        $categoryId = $request->get('category_id');

        // Get historical purchase data (last 12 months)
        $historicalStart = Carbon::now()->subMonths(12);
        $historicalEnd = Carbon::now();

        $query = PurchaseInvoiceItem::with(['inventoryItem.category', 'invoice'])
            ->whereHas('invoice', function($q) use ($historicalStart, $historicalEnd, $branchId) {
                $q->whereBetween('invoice_date', [$historicalStart, $historicalEnd])
                  ->where('status', '!=', 'cancelled');
                if ($branchId === 'all') {
                    $companyBranches = Branch::where('company_id', Auth::user()->company_id)->pluck('id')->toArray();
                    $q->whereIn('branch_id', $companyBranches);
                } else {
                    $q->where('branch_id', $branchId);
                }
            })
            ->whereNotNull('inventory_item_id');

        if ($categoryId) {
            $query->whereHas('inventoryItem', function($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $items = $query->get();

        // Group by item and calculate monthly average
        $itemData = $items->groupBy('inventory_item_id')->map(function($itemPurchases, $itemId) use ($months) {
            $item = $itemPurchases->first()->inventoryItem ?? null;
            if (!$item) return null;
            
            // Group by month to calculate average monthly usage
            $monthlyUsage = $itemPurchases->groupBy(function($purchase) {
                return Carbon::parse($purchase->invoice->invoice_date)->format('Y-m');
            })->map(function($monthPurchases) {
                return $monthPurchases->sum('quantity');
            });
            
            $monthlyAvg = $monthlyUsage->count() > 0 ? $monthlyUsage->avg() : 0;
            $forecastQty = $monthlyAvg * $months;
            
            // Get current stock if available
            $currentStock = 0; // Could be enhanced to get actual stock
            
            // Calculate suggested purchase (forecast - current stock)
            $suggestedPurchase = max(0, $forecastQty - $currentStock);
            
            return [
                'item_code' => $item->item_code ?? 'N/A',
                'item_name' => $item->name ?? 'N/A',
                'category' => $item->category->name ?? 'Uncategorized',
                'monthly_avg_usage' => $monthlyAvg,
                'current_stock' => $currentStock,
                'forecast_qty' => $forecastQty,
                'suggested_purchase' => $suggestedPurchase,
            ];
        })->filter()->sortByDesc('forecast_qty')->values();

        $summary = [
            'total_items' => $itemData->count(),
            'total_forecast_qty' => $itemData->sum('forecast_qty'),
            'total_suggested_purchase' => $itemData->sum('suggested_purchase'),
        ];

        // Get categories for filter
        $categories = \App\Models\Inventory\Category::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $userBranches = Branch::where('company_id', Auth::user()->company_id)->get();
        if ($userBranches->count() > 1) {
            $allBranchesOption = (object) [
                'id' => 'all',
                'name' => 'All Branches',
                'company_id' => Auth::user()->company_id
            ];
            $branches = $userBranches->prepend($allBranchesOption);
        } else {
            $branches = $userBranches;
        }

        return view('purchases.reports.purchase-forecast', compact(
            'itemData', 'summary', 'categories', 'branches', 'months', 'branchId', 'categoryId'
        ));
    }

    /**
     * Supplier Invoice Tax Report
     * Shows tax breakdown by invoice
     */
    public function supplierTax(Request $request)
    {
        $this->authorize('view purchases');
        
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from')) : Carbon::now()->startOfMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to')) : Carbon::now()->endOfMonth();
        
        $branchId = $request->get('branch_id', Auth::user()->branch_id);
        $supplierId = $request->get('supplier_id');

        $query = PurchaseInvoice::with(['supplier'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled')
            ->where('vat_amount', '>', 0);

        $this->applyBranchFilter($query, $branchId);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $invoices = $query->orderByDesc('invoice_date')->get();

        $reportData = $invoices->map(function($invoice) {
            return [
                'invoice_no' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date,
                'supplier_name' => $invoice->supplier->name ?? 'N/A',
                'net_amount' => $invoice->subtotal,
                'vat_rate' => 18, // Default VAT rate, could be calculated from items
                'vat_amount' => $invoice->vat_amount,
                'gross_amount' => $invoice->total_amount,
            ];
        });

        $summary = [
            'total_invoices' => $invoices->count(),
            'total_net_amount' => $invoices->sum('subtotal'),
            'total_vat_amount' => $invoices->sum('vat_amount'),
            'total_gross_amount' => $invoices->sum('total_amount'),
        ];

        $suppliers = Supplier::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $userBranches = Branch::where('company_id', Auth::user()->company_id)->get();
        if ($userBranches->count() > 1) {
            $allBranchesOption = (object) [
                'id' => 'all',
                'name' => 'All Branches',
                'company_id' => Auth::user()->company_id
            ];
            $branches = $userBranches->prepend($allBranchesOption);
        } else {
            $branches = $userBranches;
        }

        return view('purchases.reports.supplier-tax', compact(
            'reportData', 'summary', 'suppliers', 'branches', 'dateFrom', 'dateTo', 'branchId', 'supplierId'
        ));
    }

    /**
     * Supplier Payment Schedule Report
     * Shows scheduled payments by due date
     */
    public function paymentSchedule(Request $request)
    {
        $this->authorize('view purchases');
        
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from')) : Carbon::now();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to')) : Carbon::now()->addMonths(3);
        
        $branchId = $request->get('branch_id', Auth::user()->branch_id);
        $supplierId = $request->get('supplier_id');

        $query = PurchaseInvoice::with(['supplier'])
            ->where('status', '!=', 'cancelled')
            ->whereBetween('due_date', [$dateFrom, $dateTo]);

        $this->applyBranchFilter($query, $branchId);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $invoices = $query->orderBy('due_date')->get()->filter(function($invoice) {
            return $invoice->outstanding_amount > 0;
        });

        $reportData = $invoices->map(function($invoice) {
            return [
                'invoice_no' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date,
                'due_date' => $invoice->due_date,
                'supplier_name' => $invoice->supplier->name ?? 'N/A',
                'invoice_amount' => $invoice->total_amount,
                'paid_amount' => $invoice->total_paid,
                'outstanding_amount' => $invoice->outstanding_amount,
                'days_until_due' => Carbon::now()->diffInDays($invoice->due_date, false),
            ];
        })->values();

        $summary = [
            'total_invoices' => $invoices->count(),
            'total_outstanding' => $invoices->sum('outstanding_amount'),
            'total_paid' => $invoices->sum('total_paid'),
        ];

        $suppliers = Supplier::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $userBranches = Branch::where('company_id', Auth::user()->company_id)->get();
        if ($userBranches->count() > 1) {
            $allBranchesOption = (object) [
                'id' => 'all',
                'name' => 'All Branches',
                'company_id' => Auth::user()->company_id
            ];
            $branches = $userBranches->prepend($allBranchesOption);
        } else {
            $branches = $userBranches;
        }

        return view('purchases.reports.payment-schedule', compact(
            'reportData', 'summary', 'suppliers', 'branches', 'dateFrom', 'dateTo', 'branchId', 'supplierId'
        ));
    }

    /**
     * Three-Way Matching Exception Report
     * Shows invoices with mismatches between PO, GRN, and Invoice
     */
    public function threeWayMatchingException(Request $request)
    {
        $this->authorize('view purchases');
        
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from')) : Carbon::now()->startOfMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to')) : Carbon::now()->endOfMonth();
        
        $branchId = $request->get('branch_id', Auth::user()->branch_id);
        $supplierId = $request->get('supplier_id');

        $query = PurchaseInvoice::with(['supplier', 'items.inventoryItem'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');

        $this->applyBranchFilter($query, $branchId);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $invoices = $query->orderByDesc('invoice_date')->get();

        $matchingService = new \App\Services\Purchase\InvoiceMatchingService();
        
        $reportData = collect();
        foreach ($invoices as $invoice) {
            $matchResult = $matchingService->performThreeWayMatch($invoice);
            
            if (!$matchResult['matched']) {
                $reportData->push([
                    'invoice_no' => $invoice->invoice_number,
                    'invoice_date' => $invoice->invoice_date,
                    'supplier_name' => $invoice->supplier->name ?? 'N/A',
                    'invoice_amount' => $invoice->total_amount,
                    'exceptions' => $matchResult['exceptions'],
                    'exception_count' => count($matchResult['exceptions']),
                    'exception_summary' => implode('; ', array_slice($matchResult['exceptions'], 0, 3)),
                ]);
            }
        }

        $summary = [
            'total_invoices' => $invoices->count(),
            'total_exceptions' => $reportData->count(),
            'total_exception_items' => $reportData->sum('exception_count'),
        ];

        $suppliers = Supplier::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $userBranches = Branch::where('company_id', Auth::user()->company_id)->get();
        if ($userBranches->count() > 1) {
            $allBranchesOption = (object) [
                'id' => 'all',
                'name' => 'All Branches',
                'company_id' => Auth::user()->company_id
            ];
            $branches = $userBranches->prepend($allBranchesOption);
        } else {
            $branches = $userBranches;
        }

        return view('purchases.reports.three-way-matching-exception', compact(
            'reportData', 'summary', 'suppliers', 'branches', 'dateFrom', 'dateTo', 'branchId', 'supplierId'
        ));
    }

    /**
     * Supplier Performance Report
     * Shows supplier performance metrics: on-time delivery, quality rating, average delivery time
     */
    public function supplierPerformance(Request $request)
    {
        $this->authorize('view purchases');
        
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from')) : Carbon::now()->subMonths(6);
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to')) : Carbon::now();
        
        $branchId = $request->get('branch_id', Auth::user()->branch_id);
        $supplierId = $request->get('supplier_id');

        // Get Purchase Orders
        $poQuery = PurchaseOrder::with(['supplier', 'items'])
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');

        $this->applyBranchFilter($poQuery, $branchId);

        if ($supplierId) {
            $poQuery->where('supplier_id', $supplierId);
        }

        $orders = $poQuery->get();

        // Get GRNs for delivery analysis
        $grnQuery = GoodsReceipt::with(['purchaseOrder'])
            ->whereHas('purchaseOrder', function($q) use ($dateFrom, $dateTo, $branchId, $supplierId) {
                $q->whereBetween('order_date', [$dateFrom, $dateTo])
                  ->where('status', '!=', 'cancelled');
                $this->applyBranchFilter($q, $branchId);
                if ($supplierId) {
                    $q->where('supplier_id', $supplierId);
                }
            });

        $grns = $grnQuery->get();

        // Group by supplier and calculate metrics
        $supplierData = $orders->groupBy('supplier_id')->map(function($supplierOrders, $supplierId) use ($grns, $orders) {
            $supplier = $supplierOrders->first()->supplier ?? null;
            if (!$supplier) return null;
            
            $totalOrders = $supplierOrders->count();
            $totalValue = $supplierOrders->sum('total_amount');
            $avgOrderValue = $totalOrders > 0 ? $totalValue / $totalOrders : 0;
            
            // Get GRNs for this supplier's orders
            $supplierOrderIds = $supplierOrders->pluck('id');
            $supplierGrns = $grns->filter(function($grn) use ($supplierOrderIds) {
                return $supplierOrderIds->contains($grn->purchase_order_id);
            });
            
            // Calculate on-time delivery
            $onTimeDeliveries = $supplierOrders->filter(function($order) use ($supplierGrns) {
                $grn = $supplierGrns->firstWhere('purchase_order_id', $order->id);
                if (!$grn || !$order->expected_delivery_date) return false;
                return $grn->receipt_date <= $order->expected_delivery_date;
            })->count();
            
            $onTimeRate = $totalOrders > 0 ? ($onTimeDeliveries / $totalOrders) * 100 : 0;
            
            // Calculate average delivery time
            $deliveryTimes = $supplierOrders->map(function($order) use ($supplierGrns) {
                $grn = $supplierGrns->firstWhere('purchase_order_id', $order->id);
                if (!$grn || !$order->expected_delivery_date) return null;
                return $order->order_date->diffInDays($grn->receipt_date);
            })->filter();
            
            $avgDeliveryTime = $deliveryTimes->count() > 0 ? $deliveryTimes->avg() : 0;
            
            return [
                'supplier_name' => $supplier->name,
                'total_orders' => $totalOrders,
                'total_value' => $totalValue,
                'avg_order_value' => $avgOrderValue,
                'on_time_rate' => $onTimeRate,
                'avg_delivery_time' => $avgDeliveryTime,
            ];
        })->filter()->sortByDesc('total_value')->values();

        $summary = [
            'total_suppliers' => $supplierData->count(),
            'total_orders' => $orders->count(),
            'total_value' => $orders->sum('total_amount'),
        ];

        $suppliers = Supplier::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $userBranches = Branch::where('company_id', Auth::user()->company_id)->get();
        if ($userBranches->count() > 1) {
            $allBranchesOption = (object) [
                'id' => 'all',
                'name' => 'All Branches',
                'company_id' => Auth::user()->company_id
            ];
            $branches = $userBranches->prepend($allBranchesOption);
        } else {
            $branches = $userBranches;
        }

        return view('purchases.reports.supplier-performance', compact(
            'supplierData', 'summary', 'suppliers', 'branches', 'dateFrom', 'dateTo', 'branchId', 'supplierId'
        ));
    }

    /**
     * Purchase Price Variance (PPV) Report
     * Shows price variance between PO and Invoice
     */
    public function purchasePriceVariance(Request $request)
    {
        $this->authorize('view purchases');
        
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from')) : Carbon::now()->startOfMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to')) : Carbon::now()->endOfMonth();
        
        $branchId = $request->get('branch_id', Auth::user()->branch_id);
        $supplierId = $request->get('supplier_id');

        $query = PurchaseInvoice::with(['supplier', 'items.grnItem.purchaseOrderItem.purchaseOrder'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');

        $this->applyBranchFilter($query, $branchId);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $invoices = $query->orderByDesc('invoice_date')->get();

        $reportData = collect();
        foreach ($invoices as $invoice) {
            foreach ($invoice->items as $invoiceItem) {
                // Try to get PO item through GRN item
                $poItem = null;
                if ($invoiceItem->grnItem && $invoiceItem->grnItem->purchaseOrderItem) {
                    $poItem = $invoiceItem->grnItem->purchaseOrderItem;
                }
                
                if (!$poItem) continue;
                
                $poPrice = $poItem->cost_price ?? 0;
                $invoicePrice = $invoiceItem->unit_cost ?? 0;
                $priceVariance = $invoicePrice - $poPrice;
                $variancePercent = $poPrice > 0 ? ($priceVariance / $poPrice) * 100 : 0;
                $quantity = $invoiceItem->quantity ?? 0;
                $totalVariance = $priceVariance * $quantity;
                
                $reportData->push([
                    'supplier_name' => $invoice->supplier->name ?? 'N/A',
                    'invoice_no' => $invoice->invoice_number,
                    'po_no' => $poItem->purchaseOrder->order_number ?? '-',
                    'item_code' => $invoiceItem->inventoryItem->item_code ?? '-',
                    'item_description' => $invoiceItem->description ?? '-',
                    'quantity' => $quantity,
                    'po_unit_price' => $poPrice,
                    'invoice_unit_price' => $invoicePrice,
                    'price_variance' => $priceVariance,
                    'variance_percent' => $variancePercent,
                    'total_variance' => $totalVariance,
                ]);
            }
        }

        $summary = [
            'total_items' => $reportData->count(),
            'total_favorable_variance' => $reportData->filter(fn($r) => $r['price_variance'] < 0)->sum('total_variance'),
            'total_unfavorable_variance' => $reportData->filter(fn($r) => $r['price_variance'] > 0)->sum('total_variance'),
            'net_variance' => $reportData->sum('total_variance'),
        ];

        $suppliers = Supplier::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $userBranches = Branch::where('company_id', Auth::user()->company_id)->get();
        if ($userBranches->count() > 1) {
            $allBranchesOption = (object) [
                'id' => 'all',
                'name' => 'All Branches',
                'company_id' => Auth::user()->company_id
            ];
            $branches = $userBranches->prepend($allBranchesOption);
        } else {
            $branches = $userBranches;
        }

        return view('purchases.reports.purchase-price-variance', compact(
            'reportData', 'summary', 'suppliers', 'branches', 'dateFrom', 'dateTo', 'branchId', 'supplierId'
        ));
    }
    
    /**
     * Supplier Statement Report
     */
    public function supplierStatement(Request $request)
    {
        $this->authorize('view purchases');
        
        // Default dates to current month if not provided
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $supplierId = $request->get('supplier_id');
        
        // Use session branch as default, fallback to user's branch
        $sessionBranchId = session('branch_id');
        $userBranchId = Auth::user()->branch_id;
        
        // Get all user's accessible branches
        $userBranches = Branch::where('company_id', Auth::user()->company_id)->get();
        
        // If user has more than 1 branch, default to 'all', otherwise use assigned branch
        if ($userBranches->count() > 1) {
            $defaultBranchId = 'all';
        } else {
            $defaultBranchId = $sessionBranchId ?? $userBranchId;
        }
        
        $branchId = $request->get('branch_id', $defaultBranchId);

        // Initialize variables
        $supplier = null;
        $openingBalance = 0;
        $closingBalance = 0;
        $totalInvoices = 0;
        $totalPayments = 0;
        $totalDebitNotes = 0;
        $transactions = collect();
        $errorMessage = null;
        
        // Get branches for filter
        if ($userBranches->count() > 1) {
            $assignedBranch = null;
            if ($sessionBranchId) {
                $assignedBranch = $userBranches->where('id', $sessionBranchId)->first();
            }
            
            $allBranchesOption = (object) [
                'id' => 'all',
                'name' => 'All Branches',
                'company_id' => Auth::user()->company_id
            ];
            
            if ($assignedBranch) {
                $branches = collect([$assignedBranch])->prepend($allBranchesOption);
            } else {
                $branches = $userBranches->prepend($allBranchesOption);
            }
        } else {
            $branches = $userBranches;
        }
        
        // Get suppliers for the selected branch
        $suppliers = Supplier::where('company_id', Auth::user()->company_id)
            ->when($branchId && $branchId !== 'all', function($q) use ($branchId) { 
                $q->where('branch_id', $branchId); 
            })
            ->orderBy('name')->get();

        // Only process report data if supplier is selected and dates are valid
        if ($supplierId && $dateFrom && $dateTo) {
            try {
                $dateFromCarbon = Carbon::parse($dateFrom)->startOfDay();
                $dateToCarbon = Carbon::parse($dateTo)->endOfDay();
                
                if ($dateFromCarbon->gt($dateToCarbon)) {
                    $errorMessage = 'Start date cannot be later than end date. Please select a valid date range.';
                } else {
                    $supplier = Supplier::find($supplierId);
                    
                    if ($supplier) {
                        // Get opening balance (invoices before date range)
                        $openingBalanceQuery = PurchaseInvoice::where('supplier_id', $supplierId)
                            ->where('invoice_date', '<', $dateFromCarbon)
                            ->where('status', '!=', 'cancelled');
                        $this->applyBranchFilter($openingBalanceQuery, $branchId);
                        $openingBalance = $openingBalanceQuery->sum('total_amount');
                        
                        // Subtract payments made before date range
                        $openingPaymentsQuery = Payment::where('supplier_id', $supplierId)
                            ->where('reference_type', 'purchase_invoice')
                            ->where('date', '<', $dateFromCarbon);
                        $this->applyBranchFilter($openingPaymentsQuery, $branchId);
                        $openingPayments = $openingPaymentsQuery->sum('amount');
                        
                        // Subtract debit notes before date range
                        $openingDebitNotesQuery = DebitNote::where('supplier_id', $supplierId)
                            ->where('debit_note_date', '<', $dateFromCarbon)
                            ->where('status', '!=', 'cancelled');
                        $this->applyBranchFilter($openingDebitNotesQuery, $branchId);
                        $openingDebitNotes = $openingDebitNotesQuery->sum('total_amount');
                        
                        $openingBalance = $openingBalance - $openingPayments - $openingDebitNotes;

                        // Get transactions in period
                        $invoicesQuery = PurchaseInvoice::where('supplier_id', $supplierId)
                            ->whereBetween('invoice_date', [$dateFromCarbon, $dateToCarbon])
                            ->where('status', '!=', 'cancelled');
                        $this->applyBranchFilter($invoicesQuery, $branchId);
                        $invoices = $invoicesQuery->orderBy('invoice_date')->get();

                        $debitNotesQuery = DebitNote::where('supplier_id', $supplierId)
                            ->whereBetween('debit_note_date', [$dateFromCarbon, $dateToCarbon])
                            ->where('status', '!=', 'cancelled');
                        $this->applyBranchFilter($debitNotesQuery, $branchId);
                        $debitNotes = $debitNotesQuery->orderBy('debit_note_date')->get();

                        // Get payments in period
                        $paymentsQuery = Payment::where('supplier_id', $supplierId)
                            ->where('reference_type', 'purchase_invoice')
                            ->whereBetween('date', [$dateFromCarbon, $dateToCarbon]);
                        $this->applyBranchFilter($paymentsQuery, $branchId);
                        $payments = $paymentsQuery->orderBy('date')->get();

                        // Combine all transactions
                        $transactions = collect();
                        
                        // Add invoices
                        foreach ($invoices as $invoice) {
                            $transactions->push((object) [
                                'date' => $invoice->invoice_date,
                                'document_type' => 'Invoice',
                                'reference_no' => $invoice->invoice_number,
                                'debit' => $invoice->total_amount,
                                'credit' => 0,
                                'running_balance' => 0, // Will be calculated
                                'reference_id' => $invoice->id,
                                'type' => 'invoice',
                                'invoice_id' => $invoice->id
                            ]);
                        }
                        
                        // Add payments
                        foreach ($payments as $payment) {
                            $transactions->push((object) [
                                'date' => $payment->date,
                                'document_type' => 'Payment',
                                'reference_no' => $payment->reference_number ?? 'PAY-' . $payment->id,
                                'debit' => 0,
                                'credit' => $payment->amount,
                                'running_balance' => 0, // Will be calculated
                                'reference_id' => $payment->id,
                                'type' => 'payment',
                                'payment_method' => $payment->bankAccount ? $payment->bankAccount->account_name : 'Cash'
                            ]);
                        }
                        
                        // Add debit notes
                        foreach ($debitNotes as $debitNote) {
                            $transactions->push((object) [
                                'date' => $debitNote->debit_note_date,
                                'document_type' => 'Credit Note',
                                'reference_no' => $debitNote->debit_note_number,
                                'debit' => 0,
                                'credit' => $debitNote->total_amount,
                                'running_balance' => 0, // Will be calculated
                                'reference_id' => $debitNote->id,
                                'type' => 'debit_note'
                            ]);
                        }
                        
                        // Sort transactions by date
                        $transactions = $transactions->sortBy('date');
                        
                        // Calculate running balance
                        $runningBalance = $openingBalance;
                        $transactions = $transactions->map(function ($transaction) use (&$runningBalance) {
                            $runningBalance += $transaction->debit - $transaction->credit;
                            $transaction->running_balance = $runningBalance;
                            return $transaction;
                        });

                        // Calculate summary statistics
                        $totalInvoices = $invoices->sum('total_amount');
                        $totalPayments = $transactions->where('type', 'payment')->sum('amount');
                        $totalDebitNotes = $debitNotes->sum('total_amount');
                        $closingBalance = $openingBalance + $totalInvoices - $totalDebitNotes - $totalPayments;
                    } else {
                        $errorMessage = 'Supplier not found.';
                    }
                }
            } catch (\Exception $e) {
                $errorMessage = 'Invalid date format. Please select valid dates.';
            }
        }

        $totalDebitNotes = isset($totalDebitNotes) ? $totalDebitNotes : 0;
        
        return view('purchases.reports.supplier-statement', compact(
            'supplier', 'openingBalance', 'closingBalance', 'totalInvoices', 'totalPayments', 'totalDebitNotes',
            'transactions', 'suppliers', 'branches', 'dateFrom', 'dateTo', 'branchId', 'supplierId', 'errorMessage'
        ));
    }
    
    /**
     * Export Supplier Statement to PDF
     */
    public function exportSupplierStatementPdf(Request $request)
    {
        $this->authorize('view purchases');
        
        $supplierId = $request->get('supplier_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if (!$supplierId || !$dateFrom || !$dateTo) {
            abort(400, 'Supplier, date from, and date to are required for export');
        }

        $supplier = Supplier::findOrFail($supplierId);
        $dateFrom = Carbon::parse($dateFrom);
        $dateTo = Carbon::parse($dateTo);
        
        $branchId = $request->get('branch_id', 'all');

        // Get opening balance (invoices before date range)
        $openingBalanceQuery = PurchaseInvoice::where('supplier_id', $supplierId)
            ->where('invoice_date', '<', $dateFrom)
            ->where('status', '!=', 'cancelled');
        $this->applyBranchFilter($openingBalanceQuery, $branchId);
        $openingBalance = $openingBalanceQuery->sum('total_amount');
        
        // Subtract payments made before date range
        $openingPaymentsQuery = Payment::where('supplier_id', $supplierId)
            ->where('reference_type', 'purchase_invoice')
            ->where('date', '<', $dateFrom);
        $this->applyBranchFilter($openingPaymentsQuery, $branchId);
        $openingPayments = $openingPaymentsQuery->sum('amount');
        
        // Subtract debit notes before date range
        $openingDebitNotesQuery = DebitNote::where('supplier_id', $supplierId)
            ->where('debit_note_date', '<', $dateFrom)
            ->where('status', '!=', 'cancelled');
        $this->applyBranchFilter($openingDebitNotesQuery, $branchId);
        $openingDebitNotes = $openingDebitNotesQuery->sum('total_amount');
        
        $openingBalance = $openingBalance - $openingPayments - $openingDebitNotes;

        // Get transactions in period
        $invoicesQuery = PurchaseInvoice::where('supplier_id', $supplierId)
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');
        $this->applyBranchFilter($invoicesQuery, $branchId);
        $invoices = $invoicesQuery->orderBy('invoice_date')->get();

        $debitNotesQuery = DebitNote::where('supplier_id', $supplierId)
            ->whereBetween('debit_note_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');
        $this->applyBranchFilter($debitNotesQuery, $branchId);
        $debitNotes = $debitNotesQuery->orderBy('debit_note_date')->get();

        // Get payments in period
        $paymentsQuery = Payment::where('supplier_id', $supplierId)
            ->where('reference_type', 'purchase_invoice')
            ->whereBetween('date', [$dateFrom, $dateTo]);
        $this->applyBranchFilter($paymentsQuery, $branchId);
        $payments = $paymentsQuery->orderBy('date')->get();

        // Combine all transactions
        $transactions = collect();
        
        // Add invoices
        foreach ($invoices as $invoice) {
            $transactions->push((object) [
                'date' => $invoice->invoice_date,
                'reference' => $invoice->invoice_number,
                'reference_id' => $invoice->id,
                'description' => 'Invoice#'. $invoice->id . ' --- ' . 'Invoice - ' . $invoice->invoice_number,
                'amount' => $invoice->total_amount,
                'paid_amount' => $invoice->total_paid,
                'balance_due' => $invoice->outstanding_amount,
                'status' => $invoice->status,
                'type' => 'invoice',
                'invoice_id' => $invoice->id
            ]);
        }
        
        // Add payments
        foreach ($payments as $payment) {
            $transactions->push((object) [
                'date' => $payment->date,
                'reference' => $payment->reference_number ?? 'PAY-' . $payment->id,
                'reference_id' => $payment->id,
                'description' => 'Payment#'. $payment->id . ' --- ' . ($payment->description ?? 'Payment for Invoice'),
                'amount' => $payment->amount,
                'type' => 'payment',
                'payment_method' => $payment->bankAccount ? $payment->bankAccount->account_name : 'Cash'
            ]);
        }
        
        // Add debit notes
        foreach ($debitNotes as $debitNote) {
            $transactions->push((object) [
                'date' => $debitNote->debit_note_date,
                'reference' => $debitNote->debit_note_number,
                'reference_id' => $debitNote->id,
                'description' => 'Debit Note#'. $debitNote->id . ' --- ' . 'Debit Note - ' . $debitNote->debit_note_number,
                'amount' => $debitNote->total_amount,
                'type' => 'debit_note'
            ]);
        }
        
        // Sort transactions by date
        $transactions = $transactions->sortBy('date');

        // Calculate running balance
        $runningBalance = $openingBalance;
        $transactions = $transactions->map(function ($transaction) use (&$runningBalance) {
            if ($transaction->type == 'invoice') {
                $runningBalance += $transaction->amount;
            } elseif ($transaction->type == 'payment') {
                $runningBalance -= $transaction->amount;
            } elseif ($transaction->type == 'debit_note') {
                $runningBalance -= $transaction->amount;
            }
            $transaction->balance = $runningBalance;
            return $transaction;
        });

        // Calculate summary statistics
        $totalInvoices = $invoices->sum('total_amount');
        $totalPayments = $transactions->where('type', 'payment')->sum('amount');
        $totalDebitNotes = $debitNotes->sum('total_amount');
        $closingBalance = $openingBalance + $totalInvoices - $totalDebitNotes - $totalPayments;

        $company = current_company();
        $branch = $branchId !== 'all' ? Branch::find($branchId) : null;

        $pdf = Pdf::loadView('purchases.reports.exports.supplier-statement-pdf', compact(
            'supplier', 'transactions', 'dateFrom', 'dateTo', 'company', 'openingBalance', 'totalInvoices', 'totalPayments', 'totalDebitNotes', 'closingBalance', 'branch'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('supplier-statement-' . $supplier->name . '-' . now()->format('Y-m-d') . '.pdf');
    }
    
    /**
     * Export Supplier Statement to Excel
     */
    public function exportSupplierStatementExcel(Request $request)
    {
        $this->authorize('view purchases');
        
        $supplierId = $request->get('supplier_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if (!$supplierId || !$dateFrom || !$dateTo) {
            abort(400, 'Supplier, date from, and date to are required for export');
        }

        $supplier = Supplier::findOrFail($supplierId);
        $dateFrom = Carbon::parse($dateFrom);
        $dateTo = Carbon::parse($dateTo);
        
        $branchId = $request->get('branch_id', 'all');

        // Get opening balance (invoices before date range)
        $openingBalanceQuery = PurchaseInvoice::where('supplier_id', $supplierId)
            ->where('invoice_date', '<', $dateFrom)
            ->where('status', '!=', 'cancelled');
        $this->applyBranchFilter($openingBalanceQuery, $branchId);
        $openingBalance = $openingBalanceQuery->sum('total_amount');
        
        // Subtract payments made before date range
        $openingPaymentsQuery = Payment::where('supplier_id', $supplierId)
            ->where('reference_type', 'purchase_invoice')
            ->where('date', '<', $dateFrom);
        $this->applyBranchFilter($openingPaymentsQuery, $branchId);
        $openingPayments = $openingPaymentsQuery->sum('amount');
        
        // Subtract debit notes before date range
        $openingDebitNotesQuery = DebitNote::where('supplier_id', $supplierId)
            ->where('debit_note_date', '<', $dateFrom)
            ->where('status', '!=', 'cancelled');
        $this->applyBranchFilter($openingDebitNotesQuery, $branchId);
        $openingDebitNotes = $openingDebitNotesQuery->sum('total_amount');
        
        $openingBalance = $openingBalance - $openingPayments - $openingDebitNotes;

        // Get transactions in period
        $invoicesQuery = PurchaseInvoice::where('supplier_id', $supplierId)
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');
        $this->applyBranchFilter($invoicesQuery, $branchId);
        $invoices = $invoicesQuery->orderBy('invoice_date')->get();

        $debitNotesQuery = DebitNote::where('supplier_id', $supplierId)
            ->whereBetween('debit_note_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled');
        $this->applyBranchFilter($debitNotesQuery, $branchId);
        $debitNotes = $debitNotesQuery->orderBy('debit_note_date')->get();

        // Get payments in period
        $paymentsQuery = Payment::where('supplier_id', $supplierId)
            ->where('reference_type', 'purchase_invoice')
            ->whereBetween('date', [$dateFrom, $dateTo]);
        $this->applyBranchFilter($paymentsQuery, $branchId);
        $payments = $paymentsQuery->orderBy('date')->get();

        // Combine all transactions
        $transactions = collect();
        
        // Add invoices
        foreach ($invoices as $invoice) {
            $transactions->push((object) [
                'date' => $invoice->invoice_date,
                'reference' => $invoice->invoice_number,
                'reference_id' => $invoice->id,
                'description' => 'Invoice#'. $invoice->id . ' --- ' . 'Invoice - ' . $invoice->invoice_number,
                'amount' => $invoice->total_amount,
                'paid_amount' => $invoice->total_paid,
                'balance_due' => $invoice->outstanding_amount,
                'status' => $invoice->status,
                'type' => 'invoice',
                'invoice_id' => $invoice->id
            ]);
        }
        
        // Add payments
        foreach ($payments as $payment) {
            $transactions->push((object) [
                'date' => $payment->date,
                'reference' => $payment->reference_number ?? 'PAY-' . $payment->id,
                'reference_id' => $payment->id,
                'description' => 'Payment#'. $payment->id . ' --- ' . ($payment->description ?? 'Payment for Invoice'),
                'amount' => $payment->amount,
                'type' => 'payment',
                'payment_method' => $payment->bankAccount ? $payment->bankAccount->account_name : 'Cash'
            ]);
        }
        
        // Add debit notes
        foreach ($debitNotes as $debitNote) {
            $transactions->push((object) [
                'date' => $debitNote->debit_note_date,
                'reference' => $debitNote->debit_note_number,
                'reference_id' => $debitNote->id,
                'description' => 'Debit Note#'. $debitNote->id . ' --- ' . 'Debit Note - ' . $debitNote->debit_note_number,
                'amount' => $debitNote->total_amount,
                'type' => 'debit_note'
            ]);
        }
        
        // Sort transactions by date
        $transactions = $transactions->sortBy('date');

        // Calculate running balance
        $runningBalance = $openingBalance;
        $transactions = $transactions->map(function ($transaction) use (&$runningBalance) {
            if ($transaction->type == 'invoice') {
                $runningBalance += $transaction->amount;
            } elseif ($transaction->type == 'payment') {
                $runningBalance -= $transaction->amount;
            } elseif ($transaction->type == 'debit_note') {
                $runningBalance -= $transaction->amount;
            }
            $transaction->balance = $runningBalance;
            return $transaction;
        });

        // Calculate summary statistics
        $totalInvoices = $invoices->sum('total_amount');
        $totalPayments = $transactions->where('type', 'payment')->sum('amount');
        $totalDebitNotes = $debitNotes->sum('total_amount');
        $closingBalance = $openingBalance + $totalInvoices - $totalDebitNotes - $totalPayments;

        $branch = $branchId !== 'all' ? Branch::find($branchId) : null;
        $company = current_company();

        return Excel::download(new \App\Exports\SupplierStatementExport($supplier, $transactions, $dateFrom, $dateTo, $branch, $openingBalance, $totalInvoices, $totalPayments, $totalDebitNotes, $closingBalance, $company), 
            'supplier-statement-' . $supplier->name . '-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Supplier Credit Note Report (Debit Note Report)
     * Lists all credit notes (debit notes) with details
     */
    public function supplierCreditNoteReport(Request $request)
    {
        $this->authorize('view purchases');
        
        $dateFrom = $request->get('date_from') ? Carbon::parse($request->get('date_from')) : Carbon::now()->startOfMonth();
        $dateTo = $request->get('date_to') ? Carbon::parse($request->get('date_to')) : Carbon::now()->endOfMonth();
        
        $branchId = $request->get('branch_id', Auth::user()->branch_id);
        $supplierId = $request->get('supplier_id');
        $status = $request->get('status');

        $query = DebitNote::with(['supplier', 'purchaseInvoice'])
            ->whereBetween('debit_note_date', [$dateFrom, $dateTo]);

        $this->applyBranchFilter($query, $branchId);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }
        
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $debitNotes = $query->orderBy('debit_note_date', 'desc')->get();

        $summary = [
            'total_notes' => $debitNotes->count(),
            'total_net_amount' => $debitNotes->sum('subtotal'),
            'total_tax_amount' => $debitNotes->sum('vat_amount'),
            'total_gross_amount' => $debitNotes->sum('total_amount'),
        ];

        // Get suppliers for filter
        $suppliers = Supplier::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Get branches for filter
        $userBranches = Branch::where('company_id', Auth::user()->company_id)->get();
        if ($userBranches->count() > 1) {
            $allBranchesOption = (object) [
                'id' => 'all',
                'name' => 'All Branches',
                'company_id' => Auth::user()->company_id
            ];
            $branches = $userBranches->prepend($allBranchesOption);
        } else {
            $branches = $userBranches;
        }

        return view('purchases.reports.supplier-credit-note', compact(
            'debitNotes', 'summary', 'suppliers', 'branches', 'dateFrom', 'dateTo', 'branchId', 'supplierId', 'status'
        ));
    }
}


