<?php

namespace App\Services;

use App\Models\Sales\CreditNote;
use App\Models\Sales\CreditNoteItem;
use App\Models\Sales\CreditNoteApplication;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesInvoiceItem;
use App\Models\Inventory\Item as InventoryItem;
use App\Models\Inventory\Movement as InventoryMovement;
use App\Models\Customer;
use App\Models\GlTransaction;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class CreditNoteService
{
    /**
     * Create a credit note with comprehensive scenario handling
     */
    public function createCreditNote(array $data)
    {
        \Log::info('CreditNoteService: Starting credit note creation', [
            'type' => $data['type'] ?? 'unknown',
            'user_id' => Auth::id(),
            'has_items' => !empty($data['items']),
            'has_replacement_items' => !empty($data['replacement_items']),
            'items_count' => count($data['items'] ?? []),
            'replacement_items_count' => count($data['replacement_items'] ?? [])
        ]);

        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            \Log::info('CreditNoteService: User authenticated', ['user_id' => $user->id, 'branch_id' => $user->branch_id]);
            
            // Validate scenario-specific requirements
            \Log::info('CreditNoteService: Validating scenario requirements');
            $this->validateScenarioRequirements($data);
            \Log::info('CreditNoteService: Scenario validation passed');
            
            // Create credit note
            \Log::info('CreditNoteService: Preparing credit note data');
            $creditNoteData = $this->prepareCreditNoteData($data, $user);
            \Log::info('CreditNoteService: Credit note data prepared', [
                'branch_id' => $creditNoteData['branch_id'],
                'company_id' => $creditNoteData['company_id'],
                'type' => $creditNoteData['type']
            ]);
            
            \Log::info('CreditNoteService: Creating credit note record');
            $creditNote = CreditNote::create($creditNoteData);
            \Log::info('CreditNoteService: Credit note created', ['credit_note_id' => $creditNote->id]);
            
            // Create credit note items
            \Log::info('CreditNoteService: Creating credit note items', ['items_count' => count($data['items'])]);
            $this->createCreditNoteItems($creditNote, $data['items'], $user);
            \Log::info('CreditNoteService: Credit note items created successfully');
            
            // Create replacement items for exchanges
            if ($data['type'] === 'exchange' && !empty($data['replacement_items'])) {
                \Log::info('CreditNoteService: Creating replacement items', ['replacement_items_count' => count($data['replacement_items'])]);
                $this->createReplacementItems($creditNote, $data['replacement_items'], $user);
                \Log::info('CreditNoteService: Replacement items created successfully');
            }
            
            // Calculate and update totals
            \Log::info('CreditNoteService: Calculating totals');
            $this->calculateTotals($creditNote);
            \Log::info('CreditNoteService: Totals calculated successfully');
            
            // Calculate restocking fees if applicable
            if ($creditNote->restocking_fee_percentage > 0) {
                \Log::info('CreditNoteService: Calculating restocking fees');
                $this->calculateRestockingFees($creditNote);
            }
            
            // Process FX adjustments if applicable
            if ($creditNote->currency !== 'TZS') {
                \Log::info('CreditNoteService: Processing FX adjustments');
                $this->processFxAdjustments($creditNote);
            }
            
            // Create GL transactions if auto-approve is enabled
            if ($this->shouldAutoApprove($creditNote)) {
                \Log::info('CreditNoteService: Auto-approving credit note');
                $creditNote->approve();
            }
            
            \Log::info('CreditNoteService: Committing transaction');
            DB::commit();
            \Log::info('CreditNoteService: Credit note creation completed successfully', ['credit_note_id' => $creditNote->id]);
            
            return $creditNote;
            
        } catch (Exception $e) {
            \Log::error('CreditNoteService: Error during credit note creation', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Validate scenario-specific requirements
     */
    private function validateScenarioRequirements(array $data)
    {
        $type = $data['type'] ?? 'return';
        
        switch ($type) {
            case 'return':
                $this->validateReturnScenario($data);
                break;
            case 'overbilling':
            case 'duplicate_billing':
                $this->validateOverbillingScenario($data);
                break;
            case 'refund':
                $this->validateRefundScenario($data);
                break;
            case 'advance_refund':
                $this->validateAdvanceRefundScenario($data);
                break;
            case 'scrap_writeoff':
                $this->validateScrapWriteoffScenario($data);
                break;
        }
    }
    
    /**
     * Validate return scenario requirements
     */
    private function validateReturnScenario(array $data)
    {
        $invoiceId = $data['sales_invoice_id'] ?? $data['reference_invoice_id'] ?? null;
        if (empty($invoiceId)) {
            throw new Exception('Sales invoice is required for return credit notes.');
        }
        
        if (empty($data['items']) || !is_array($data['items'])) {
            throw new Exception('At least one item is required for return credit notes.');
        }
        
        // Validate return quantities
        foreach ($data['items'] as $item) {
            if ($item['quantity'] <= 0) {
                throw new Exception('Return quantity must be greater than zero.');
            }
            
            // Check if return quantity exceeds original quantity
            if (isset($item['linked_invoice_line_id'])) {
                $invoiceItem = SalesInvoiceItem::find($item['linked_invoice_line_id']);
                if ($invoiceItem && $item['quantity'] > $invoiceItem->quantity) {
                    throw new Exception("Return quantity cannot exceed original quantity for item: {$item['item_name']}");
                }
            }
        }
    }
    
    /**
     * Validate overbilling scenario requirements
     */
    private function validateOverbillingScenario(array $data)
    {
        $invoiceId = $data['sales_invoice_id'] ?? $data['reference_invoice_id'] ?? null;
        if (empty($invoiceId)) {
            throw new Exception('Sales invoice is required for overbilling credit notes.');
        }
        
        if (empty($data['reason_code']) || !in_array($data['reason_code'], ['duplicate_billing', 'overbilling'])) {
            throw new Exception('Valid reason code is required for overbilling credit notes.');
        }
    }
    
    /**
     * Validate refund scenario requirements
     */
    private function validateRefundScenario(array $data)
    {
        $invoiceId = $data['sales_invoice_id'] ?? $data['reference_invoice_id'] ?? null;
        if (empty($invoiceId)) {
            throw new Exception('Sales invoice is required for refund credit notes.');
        }
        
        // Check if invoice is paid
        $invoice = SalesInvoice::find($invoiceId);
        if ($invoice && $invoice->status !== 'paid') {
            throw new Exception('Refund credit notes can only be created for paid invoices.');
        }
    }
    
    /**
     * Validate advance refund scenario requirements
     */
    private function validateAdvanceRefundScenario(array $data)
    {
        if (empty($data['reference_document'])) {
            throw new Exception('Reference document is required for advance refund credit notes.');
        }
    }
    
    /**
     * Validate scrap writeoff scenario requirements
     */
    private function validateScrapWriteoffScenario(array $data)
    {
        if (empty($data['items']) || !is_array($data['items'])) {
            throw new Exception('At least one item is required for scrap writeoff credit notes.');
        }
        
        foreach ($data['items'] as $item) {
            if (($item['return_condition'] ?? 'resellable') !== 'scrap') {
                throw new Exception('All items must be marked as scrap for scrap writeoff credit notes.');
            }
        }
    }
    
    /**
     * Prepare credit note data
     */
    private function prepareCreditNoteData(array $data, $user): array
    {
        // Use reference_invoice_id as sales_invoice_id if sales_invoice_id is empty
        $salesInvoiceId = $data['sales_invoice_id'] ?? $data['reference_invoice_id'] ?? null;
        
        // Handle case where user might be null (shouldn't happen in normal web requests)
        if (!$user) {
            throw new \Exception('User must be authenticated to create credit notes');
        }
        
        // Resolve warehouse id: request -> session location -> first active warehouse for branch
        $resolvedWarehouseId = $data['warehouse_id'] ?? (session('location_id') ?: (function () use ($user) {
            try {
                return \App\Models\InventoryLocation::where('branch_id', $user->branch_id)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->value('id');
            } catch (\Throwable $e) {
                return null;
            }
        })());

        return [
            'sales_invoice_id' => $salesInvoiceId,
            'reference_invoice_id' => $data['reference_invoice_id'] ?? null,
            'customer_id' => $data['customer_id'],
            'credit_note_date' => $data['credit_note_date'],
            'type' => $data['type'] ?? 'return',
            'reason_code' => $data['reason_code'] ?? null,
            'reason' => $data['reason'],
            'notes' => $data['notes'] ?? null,
            'terms_conditions' => $data['terms_conditions'] ?? null,
            'attachment' => $data['attachment'] ?? null,
            'refund_now' => $data['refund_now'] ?? false,
            'return_to_stock' => $data['return_to_stock'] ?? true,
            'restocking_fee_percentage' => $data['restocking_fee_percentage'] ?? null,
            'currency' => $data['currency'] ?? 'TZS',
            'exchange_rate' => $data['exchange_rate'] ?? 1.000000,
            'reference_document' => $data['reference_document'] ?? null,
            'warehouse_id' => $resolvedWarehouseId,
            'branch_id' => session('branch_id') ?? $user->branch_id ?? 1, // Use session branch_id, fallback to user branch_id, then default to Main Branch
            'company_id' => $user->company_id,
            'created_by' => $user->id,
            'status' => 'draft',
        ];
    }
    
    /**
     * Create credit note items
     */
    private function createCreditNoteItems(CreditNote $creditNote, array $items, $user)
    {
        \Log::info('CreditNoteService: createCreditNoteItems started', [
            'credit_note_id' => $creditNote->id,
            'items_count' => count($items),
            'items' => $items
        ]);

        foreach ($items as $index => $itemData) {
            \Log::info('CreditNoteService: Processing credit note item', [
                'index' => $index,
                'item_data' => $itemData
            ]);

            $itemData['credit_note_id'] = $creditNote->id;
            
            // Get original invoice item data if linked
            if (!empty($itemData['linked_invoice_line_id'])) {
                $invoiceItem = SalesInvoiceItem::find($itemData['linked_invoice_line_id']);
                if ($invoiceItem) {
                    $itemData['original_quantity'] = $invoiceItem->quantity;
                    $itemData['original_unit_price'] = $invoiceItem->unit_price;
                    $itemData['cogs_cost_at_sale'] = $invoiceItem->cogs_cost_at_sale ?? 0;
                }
            }
            
            // Get current average cost if inventory item exists
            if (!empty($itemData['inventory_item_id'])) {
                $inventoryItem = InventoryItem::find($itemData['inventory_item_id']);
                if ($inventoryItem) {
                    $itemData['current_avg_cost'] = $inventoryItem->average_cost ?? 0;
                    $itemData['available_stock'] = $inventoryItem->current_stock ?? 0;
                    $itemData['stock_available'] = ($inventoryItem->current_stock ?? 0) > 0;
                }
            }
            
            // Calculate line total
            $itemData['line_total'] = $itemData['quantity'] * $itemData['unit_price'];
            
            // Ensure optional discount fields have defaults
            $itemData['discount_type'] = $itemData['discount_type'] ?? 'none';
            $itemData['discount_rate'] = $itemData['discount_rate'] ?? 0;
            
            // Calculate VAT
            $itemData['vat_amount'] = $this->calculateVatAmount($itemData);
            
            // Calculate discount
            $itemData['discount_amount'] = $this->calculateDiscountAmount($itemData);
            
            // Store tax calculation details
            $itemData['tax_calculation_details'] = [
                'vat_type' => $itemData['vat_type'],
                'vat_rate' => $itemData['vat_rate'],
                'vat_amount' => $itemData['vat_amount'],
                'discount_type' => $itemData['discount_type'],
                'discount_rate' => $itemData['discount_rate'],
                'discount_amount' => $itemData['discount_amount'],
            ];
            
            \Log::info('CreditNoteService: Creating CreditNoteItem for returned item', [
                'item_data' => $itemData,
                'has_return_condition' => isset($itemData['return_condition']),
                'return_condition_value' => $itemData['return_condition'] ?? 'not_set'
            ]);

            try {
                $creditNoteItem = CreditNoteItem::create($itemData);
                \Log::info('CreditNoteService: CreditNoteItem for returned item created successfully', ['credit_note_item_id' => $creditNoteItem->id]);
            } catch (Exception $e) {
                \Log::error('CreditNoteService: Error creating CreditNoteItem for returned item', [
                    'error' => $e->getMessage(),
                    'item_data' => $itemData,
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                throw $e;
            }
        }
        
        \Log::info('CreditNoteService: createCreditNoteItems completed successfully');
    }

    /**
     * Create replacement items for exchanges
     */
    private function createReplacementItems(CreditNote $creditNote, array $replacementItems, $user)
    {
        \Log::info('CreditNoteService: createReplacementItems started', [
            'credit_note_id' => $creditNote->id,
            'replacement_items_count' => count($replacementItems),
            'replacement_items' => $replacementItems
        ]);

        foreach ($replacementItems as $index => $itemData) {
            \Log::info('CreditNoteService: Processing replacement item', [
                'index' => $index,
                'item_data' => $itemData
            ]);

            $itemData['credit_note_id'] = $creditNote->id;
            $itemData['is_replacement'] = true; // Mark as replacement item
            
            \Log::info('CreditNoteService: Basic item data set', [
                'credit_note_id' => $itemData['credit_note_id'],
                'is_replacement' => $itemData['is_replacement']
            ]);
            
            // Get current average cost if inventory item exists
            if (!empty($itemData['inventory_item_id'])) {
                \Log::info('CreditNoteService: Looking up inventory item', ['inventory_item_id' => $itemData['inventory_item_id']]);
                $inventoryItem = InventoryItem::find($itemData['inventory_item_id']);
                if ($inventoryItem) {
                    $itemData['current_avg_cost'] = $inventoryItem->average_cost ?? 0;
                    $itemData['available_stock'] = $inventoryItem->current_stock ?? 0;
                    $itemData['stock_available'] = ($inventoryItem->current_stock ?? 0) > 0;
                    \Log::info('CreditNoteService: Inventory item found', [
                        'average_cost' => $itemData['current_avg_cost'],
                        'available_stock' => $itemData['available_stock']
                    ]);
                } else {
                    \Log::warning('CreditNoteService: Inventory item not found', ['inventory_item_id' => $itemData['inventory_item_id']]);
                }
            }
            
            // Calculate line total
            $itemData['line_total'] = $itemData['quantity'] * $itemData['unit_price'];
            \Log::info('CreditNoteService: Line total calculated', ['line_total' => $itemData['line_total']]);
            
            // Ensure optional discount fields have defaults
            $itemData['discount_type'] = $itemData['discount_type'] ?? 'none';
            $itemData['discount_rate'] = $itemData['discount_rate'] ?? 0;
            
            // Calculate VAT
            \Log::info('CreditNoteService: Calculating VAT', ['vat_type' => $itemData['vat_type'], 'vat_rate' => $itemData['vat_rate']]);
            $itemData['vat_amount'] = $this->calculateVatAmount($itemData);
            \Log::info('CreditNoteService: VAT calculated', ['vat_amount' => $itemData['vat_amount']]);
            
            // Calculate discount
            $itemData['discount_amount'] = $this->calculateDiscountAmount($itemData);
            \Log::info('CreditNoteService: Discount calculated', ['discount_amount' => $itemData['discount_amount']]);
            
            // Store tax calculation details
            $itemData['tax_calculation_details'] = [
                'vat_type' => $itemData['vat_type'],
                'vat_rate' => $itemData['vat_rate'],
                'vat_amount' => $itemData['vat_amount'],
                'discount_type' => $itemData['discount_type'],
                'discount_rate' => $itemData['discount_rate'],
                'discount_amount' => $itemData['discount_amount'],
            ];
            
            \Log::info('CreditNoteService: Final item data before creation', [
                'item_data' => $itemData,
                'has_return_condition' => isset($itemData['return_condition']),
                'return_condition_value' => $itemData['return_condition'] ?? 'not_set'
            ]);
            
            \Log::info('CreditNoteService: Creating CreditNoteItem record');
            try {
                $creditNoteItem = CreditNoteItem::create($itemData);
                \Log::info('CreditNoteService: CreditNoteItem created successfully', ['credit_note_item_id' => $creditNoteItem->id]);
            } catch (Exception $e) {
                \Log::error('CreditNoteService: Error creating CreditNoteItem', [
                    'error' => $e->getMessage(),
                    'item_data' => $itemData,
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                throw $e;
            }
        }
        
        \Log::info('CreditNoteService: createReplacementItems completed successfully');
    }
    
    /**
     * Calculate VAT amount for an item
     */
    private function calculateVatAmount(array $itemData): float
    {
        $lineTotal = $itemData['quantity'] * $itemData['unit_price'];
        
        if (($itemData['vat_type'] ?? 'no_vat') === 'no_vat') {
            return 0;
        }
        
        $vatRate = $itemData['vat_rate'] ?? 0;
        
        if (($itemData['vat_type'] ?? 'exclusive') === 'inclusive') {
            // VAT is included in the price
            return $lineTotal - ($lineTotal / (1 + ($vatRate / 100)));
        } else {
            // VAT is added to the price
            return $lineTotal * ($vatRate / 100);
        }
    }
    
    /**
     * Calculate discount amount for an item
     */
    private function calculateDiscountAmount(array $itemData): float
    {
        $lineTotal = $itemData['quantity'] * $itemData['unit_price'];
        $discountType = $itemData['discount_type'] ?? 'none';
        $discountRate = $itemData['discount_rate'] ?? 0;
        
        if ($discountType === 'percentage') {
            return $lineTotal * ($discountRate / 100);
        } elseif ($discountType === 'fixed') {
            return $discountRate;
        }
        
        return 0;
    }
    
    /**
     * Calculate totals for the credit note
     */
    private function calculateTotals(CreditNote $creditNote)
    {
        $subtotal = 0;
        $vatAmount = 0;
        $discountAmount = 0;
        
        if ($creditNote->type === 'exchange') {
            // For exchange credit notes, calculate net difference between returned and replacement items
            $returnedSubtotal = 0;
            $returnedVatAmount = 0;
            $returnedDiscountAmount = 0;
            
            $replacementSubtotal = 0;
            $replacementVatAmount = 0;
            $replacementDiscountAmount = 0;
            
            foreach ($creditNote->items as $item) {
                if ($item->is_replacement) {
                    // Replacement items (positive - what customer receives)
                    $replacementSubtotal += $item->line_total;
                    $replacementVatAmount += $item->vat_amount;
                    $replacementDiscountAmount += $item->discount_amount;
                } else {
                    // Returned items (negative - what customer returns)
                    $returnedSubtotal += $item->line_total;
                    $returnedVatAmount += $item->vat_amount;
                    $returnedDiscountAmount += $item->discount_amount;
                }
            }
            
            // Net amount = returned items - replacement items
            $subtotal = $returnedSubtotal - $replacementSubtotal;
            $vatAmount = $returnedVatAmount - $replacementVatAmount;
            $discountAmount = $returnedDiscountAmount - $replacementDiscountAmount;
            
            \Log::info('CreditNoteService: Exchange totals calculated', [
                'returned_subtotal' => $returnedSubtotal,
                'replacement_subtotal' => $replacementSubtotal,
                'net_subtotal' => $subtotal,
                'returned_vat' => $returnedVatAmount,
                'replacement_vat' => $replacementVatAmount,
                'net_vat' => $vatAmount
            ]);
        } else {
            // For regular credit notes, sum all items
            foreach ($creditNote->items as $item) {
                $subtotal += $item->line_total;
                $vatAmount += $item->vat_amount;
                $discountAmount += $item->discount_amount;
            }
        }
        
        // Apply invoice-level discount if present
        $invoiceLevelDiscount = request()->input('discount_amount', 0);
        $invoiceLevelDiscount = is_numeric($invoiceLevelDiscount) ? (float) $invoiceLevelDiscount : 0.0;
        $discountAmount += $invoiceLevelDiscount;
        
        $totalAmount = $subtotal + $vatAmount - $discountAmount;
        
        \Log::info('CreditNoteService: Final totals', [
            'subtotal' => $subtotal,
            'vat_amount' => $vatAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount
        ]);
        
        $creditNote->update([
            'subtotal' => $subtotal,
            'vat_amount' => $vatAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'net_credit_amount' => $subtotal - $discountAmount,
            'gross_credit_amount' => $totalAmount,
            'remaining_amount' => $totalAmount,
        ]);
    }
    
    /**
     * Calculate restocking fees
     */
    private function calculateRestockingFees(CreditNote $creditNote)
    {
        if ($creditNote->restocking_fee_percentage <= 0) {
            return;
        }
        
        $restockingFeeAmount = $creditNote->net_credit_amount * ($creditNote->restocking_fee_percentage / 100);
        $restockingFeeVat = $restockingFeeAmount * ($creditNote->vat_rate / 100);
        
        $creditNote->update([
            'restocking_fee_amount' => $restockingFeeAmount,
            'restocking_fee_vat' => $restockingFeeVat,
        ]);
        
        // Update item-level restocking fees
        foreach ($creditNote->items as $item) {
            $itemRestockingFee = $item->line_total * ($creditNote->restocking_fee_percentage / 100);
            $itemRestockingFeeVat = $itemRestockingFee * ($item->vat_rate / 100);
            
            $item->update([
                'restocking_fee_amount' => $itemRestockingFee,
                'restocking_fee_vat' => $itemRestockingFeeVat,
            ]);
        }
    }
    
    /**
     * Process FX adjustments
     */
    private function processFxAdjustments(CreditNote $creditNote)
    {
        if ($creditNote->currency === 'TZS') {
            return;
        }
        
        // Calculate FX gain/loss based on exchange rate differences
        // This is a simplified implementation - you may need more complex logic
        $originalAmount = $creditNote->total_amount;
        $convertedAmount = $originalAmount * $creditNote->exchange_rate;
        
        // For now, we'll set FX gain/loss to 0 and let the user adjust manually
        $creditNote->update([
            'fx_gain_loss' => 0,
        ]);
    }
    
    /**
     * Check if credit note should be auto-approved
     */
    private function shouldAutoApprove(CreditNote $creditNote): bool
    {
        // Get auto-approve setting from system settings
        $setting = SystemSetting::where('key', 'credit_note_auto_approve')->first();
        
        // Default to false if setting doesn't exist
        return $setting ? (bool) $setting->value : false;
    }
    
    /**
     * Apply credit note to invoice
     */
    public function applyToInvoice(CreditNote $creditNote, SalesInvoice $invoice, float $amount = null)
    {
        DB::beginTransaction();
        
        try {
            $amount = $amount ?? min($creditNote->remaining_amount, $invoice->balance_due);
            
            if ($amount <= 0) {
                throw new Exception('Invalid application amount.');
            }
            
            if ($amount > $creditNote->remaining_amount) {
                throw new Exception('Cannot apply more than remaining credit note amount.');
            }
            
            if ($amount > $invoice->balance_due) {
                throw new Exception('Cannot apply more than remaining invoice amount.');
            }
            
            // Create application record
            $application = CreditNoteApplication::create([
                'credit_note_id' => $creditNote->id,
                'sales_invoice_id' => $invoice->id,
                'amount_applied' => $amount,
                'application_type' => 'invoice',
                'application_date' => now()->toDateString(),
                'description' => "Credit Note #{$creditNote->credit_note_number} applied to Invoice #{$invoice->invoice_number}",
                'currency' => $creditNote->currency,
                'exchange_rate' => $creditNote->exchange_rate,
                'created_by' => Auth::id(),
                'branch_id' => $creditNote->branch_id,
                'company_id' => $creditNote->company_id,
            ]);
            
            // Only create GL transactions if credit note is not already approved
            // (Approval already creates the necessary GL entries)
            if ($creditNote->status !== 'applied' && $creditNote->status !== 'issued') {
                $application->createGlTransactions();
            }
            
            // Process inventory movements only if credit note is not already approved
            // (Approval already processes inventory returns)
            if ($creditNote->status !== 'applied' && $creditNote->status !== 'issued') {
                if ($creditNote->type === 'exchange') {
                    $this->processExchangeInventoryMovements($creditNote);
                } else {
                    // Process regular inventory returns
                    $this->processInventoryReturns($creditNote);
                }
            }
            
            // Update credit note
            $creditNote->increment('applied_amount', $amount);
            $creditNote->remaining_amount = $creditNote->total_amount - $creditNote->applied_amount;
            
            if ($creditNote->remaining_amount <= 0) {
                $creditNote->status = 'applied';
            } else {
                $creditNote->status = 'issued';
            }
            
            $creditNote->save();
            
            // Update invoice
            $invoice->increment('paid_amount', $amount);
            $invoice->balance_due = $invoice->total_amount - $invoice->paid_amount;
            
            if ($invoice->balance_due <= 0) {
                $invoice->status = 'paid';
            }
            
            $invoice->save();
            
            DB::commit();
            
            return $application;
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Process refund for credit note
     */
    public function processRefund(CreditNote $creditNote, array $refundData)
    {
        DB::beginTransaction();
        
        try {
            $amount = $refundData['amount'] ?? $creditNote->remaining_amount;
            $bankAccountId = $refundData['bank_account_id'] ?? null;
            $paymentMethod = $refundData['payment_method'] ?? 'bank_transfer';
            $referenceNumber = $refundData['reference_number'] ?? null;
            
            if ($amount <= 0) {
                throw new Exception('Invalid refund amount.');
            }
            
            if ($amount > $creditNote->remaining_amount) {
                throw new Exception('Cannot refund more than remaining credit note amount.');
            }
            
            // Create application record
            $application = CreditNoteApplication::create([
                'credit_note_id' => $creditNote->id,
                'bank_account_id' => $bankAccountId,
                'amount_applied' => $amount,
                'application_type' => 'refund',
                'application_date' => now()->toDateString(),
                'description' => "Refund for Credit Note #{$creditNote->credit_note_number}",
                'currency' => $creditNote->currency,
                'exchange_rate' => $creditNote->exchange_rate,
                'reference_number' => $referenceNumber,
                'payment_method' => $paymentMethod,
                'created_by' => Auth::id(),
                'branch_id' => $creditNote->branch_id,
                'company_id' => $creditNote->company_id,
            ]);
            
            // Only create GL transactions if credit note is not already approved
            // (Approval already creates the necessary GL entries)
            if ($creditNote->status !== 'applied' && $creditNote->status !== 'issued') {
                $application->createGlTransactions();
            }
            
            // Update credit note
            $creditNote->increment('applied_amount', $amount);
            $creditNote->remaining_amount = $creditNote->total_amount - $creditNote->applied_amount;
            
            if ($creditNote->remaining_amount <= 0) {
                $creditNote->status = 'refunded';
            } else {
                $creditNote->status = 'issued';
            }
            
            $creditNote->save();
            
            DB::commit();
            
            return $application;
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Process inventory returns
     */
    public function processInventoryReturns(CreditNote $creditNote)
    {
        if (!$creditNote->return_to_stock) {
            return;
        }
        
        \Log::info('CreditNoteService: Processing inventory returns', [
            'credit_note_id' => $creditNote->id,
            'return_to_stock' => $creditNote->return_to_stock,
            'items_count' => $creditNote->items->count()
        ]);
        
        foreach ($creditNote->items as $item) {
            if (!$item->return_to_stock || !$item->inventoryItem) {
                \Log::info('CreditNoteService: Skipping item', [
                    'item_id' => $item->id,
                    'return_to_stock' => $item->return_to_stock,
                    'has_inventory_item' => !!$item->inventoryItem
                ]);
                continue;
            }
            
            $inventoryItem = $item->inventoryItem;
            $quantity = $item->quantity;
            $cost = $item->cogs_cost_at_sale ?: $item->current_avg_cost ?: 0;
            
            // Get location_id from item or credit note
            $locationId = $item->warehouse_id ?: $creditNote->warehouse_id;
            
            // If no location_id, try to get default warehouse for branch
            if (!$locationId) {
                $locationId = \App\Models\InventoryLocation::where('branch_id', $creditNote->branch_id)
                    ->where('is_active', true)
                    ->first()?->id;
            }
            
            if (!$locationId) {
                \Log::error('CreditNoteService: No location_id found for inventory return', [
                    'credit_note_id' => $creditNote->id,
                    'item_id' => $item->id,
                    'branch_id' => $creditNote->branch_id
                ]);
                throw new Exception('No warehouse location found for inventory return');
            }
            
            try {
                // Create inventory movement
                $movement = InventoryMovement::create([
                    'branch_id' => $creditNote->branch_id,
                    'location_id' => $locationId,
                    'item_id' => $inventoryItem->id,
                    'user_id' => Auth::id(),
                    'movement_type' => 'adjustment_in',
                    'quantity' => $quantity,
                    'unit_cost' => $cost,
                    'total_cost' => $quantity * $cost,
                    'balance_before' => $inventoryItem->current_stock,
                    'balance_after' => $inventoryItem->current_stock + $quantity,
                    'reference' => $creditNote->id,
                    'reference_type' => 'credit_note',
                    'notes' => "Return from Credit Note #{$creditNote->credit_note_number}",
                    'movement_date' => $creditNote->credit_note_date,
                ]);
                
                \Log::info('CreditNoteService: Created inventory movement', [
                    'movement_id' => $movement->id,
                    'item_id' => $inventoryItem->id,
                    'quantity' => $quantity,
                    'location_id' => $locationId
                ]);
                
                // Update inventory stock
                $inventoryItem->increment('current_stock', $quantity);
                
                // Update average cost if needed
                if ($cost > 0 && $cost != $inventoryItem->average_cost) {
                    $this->updateAverageCost($inventoryItem, $quantity, $cost);
                }
                
            } catch (Exception $e) {
                \Log::error('CreditNoteService: Failed to create inventory movement', [
                    'error' => $e->getMessage(),
                    'item_id' => $item->id,
                    'inventory_item_id' => $inventoryItem->id,
                    'quantity' => $quantity,
                    'location_id' => $locationId
                ]);
                throw $e;
            }
        }
    }
    
    /**
     * Process inventory movements for exchange credit notes
     */
    public function processExchangeInventoryMovements(CreditNote $creditNote)
    {
        \Log::info('CreditNoteService: Processing exchange inventory movements', [
            'credit_note_id' => $creditNote->id,
            'return_to_stock' => $creditNote->return_to_stock
        ]);

        foreach ($creditNote->items as $item) {
            if (!$item->inventoryItem) {
                \Log::warning('CreditNoteService: Item has no inventory item', ['item_id' => $item->id]);
                continue;
            }

            $inventoryItem = $item->inventoryItem;
            $quantity = $item->quantity;
            $cost = $item->cogs_cost_at_sale ?: $item->current_avg_cost ?: 0;
            
            // Calculate current stock from movements
            $currentStock = $inventoryItem->movements()->sum('quantity');

            if ($item->is_replacement) {
                // Replacement items - reduce stock (customer receives)
                \Log::info('CreditNoteService: Processing replacement item', [
                    'item_name' => $item->item_name,
                    'quantity' => $quantity,
                    'current_stock' => $currentStock
                ]);

                // Create inventory movement for replacement
                InventoryMovement::create([
                    'branch_id' => $creditNote->branch_id,
                    'location_id' => $inventoryItem->default_location_id ?? 1,
                    'item_id' => $inventoryItem->id,
                    'user_id' => Auth::id() ?? 1,
                    'movement_type' => 'adjustment_out',
                    'quantity' => -$quantity, // Negative for outgoing
                    'unit_cost' => $cost,
                    'total_cost' => -($quantity * $cost),
                    'balance_before' => $currentStock,
                    'balance_after' => $currentStock - $quantity,
                    'reference' => $creditNote->id,
                    'reference_type' => 'credit_note',
                    'notes' => "Replacement item from Credit Note #{$creditNote->credit_note_number}",
                    'movement_date' => $creditNote->credit_note_date,
                ]);

                // Stock is tracked through movements, no need to update inventory item

            } else {
                // Returned items - add stock back (customer returns)
                if (!$creditNote->return_to_stock) {
                    \Log::info('CreditNoteService: Skipping returned item - not returning to stock', [
                        'item_name' => $item->item_name
                    ]);
                    continue;
                }

                \Log::info('CreditNoteService: Processing returned item', [
                    'item_name' => $item->item_name,
                    'quantity' => $quantity,
                    'current_stock' => $currentStock
                ]);

                // Create inventory movement for return
                InventoryMovement::create([
                    'branch_id' => $creditNote->branch_id,
                    'location_id' => $inventoryItem->default_location_id ?? 1,
                    'item_id' => $inventoryItem->id,
                    'user_id' => Auth::id() ?? 1,
                    'movement_type' => 'adjustment_in',
                    'quantity' => $quantity, // Positive for incoming
                    'unit_cost' => $cost,
                    'total_cost' => $quantity * $cost,
                    'balance_before' => $currentStock,
                    'balance_after' => $currentStock + $quantity,
                    'reference' => $creditNote->id,
                    'reference_type' => 'credit_note',
                    'notes' => "Return from Credit Note #{$creditNote->credit_note_number}",
                    'movement_date' => $creditNote->credit_note_date,
                ]);

                // Stock is tracked through movements, no need to update inventory item

                // Update average cost if needed
                if ($cost > 0 && $cost != $inventoryItem->average_cost) {
                    $this->updateAverageCost($inventoryItem, $quantity, $cost);
                }
            }
        }

        \Log::info('CreditNoteService: Exchange inventory movements completed');
    }
    
    /**
     * Update average cost for inventory item
     */
    private function updateAverageCost(InventoryItem $inventoryItem, float $quantity, float $cost)
    {
        $currentStock = $inventoryItem->current_stock;
        $currentAvgCost = $inventoryItem->average_cost ?? 0;
        
        if ($currentStock > 0) {
            $newAvgCost = (($currentStock - $quantity) * $currentAvgCost + $quantity * $cost) / $currentStock;
            $inventoryItem->update(['average_cost' => $newAvgCost]);
        } else {
            $inventoryItem->update(['average_cost' => $cost]);
        }
    }
    
    /**
     * Get available invoices for credit note application
     */
    public function getAvailableInvoices(Customer $customer, CreditNote $creditNote = null)
    {
        $query = SalesInvoice::where('customer_id', $customer->id)
            ->where('status', '!=', 'cancelled')
            ->where('remaining_amount', '>', 0);
        
        if ($creditNote) {
            // Exclude invoices that have already been applied to this credit note
            $appliedInvoiceIds = $creditNote->applications()
                ->where('application_type', 'invoice')
                ->pluck('sales_invoice_id');
            
            $query->whereNotIn('id', $appliedInvoiceIds);
        }
        
        return $query->orderBy('invoice_date', 'desc')->get();
    }
    
    /**
     * Get credit note statistics
     */
    public function getStatistics($companyId, $branchId = null)
    {
        $query = CreditNote::where('company_id', $companyId);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        return [
            'total_credit_notes' => $query->count(),
            'draft_credit_notes' => $query->where('status', 'draft')->count(),
            'issued_credit_notes' => $query->where('status', 'issued')->count(),
            'applied_credit_notes' => $query->where('status', 'applied')->count(),
            'refunded_credit_notes' => $query->where('status', 'refunded')->count(),
            'cancelled_credit_notes' => $query->where('status', 'cancelled')->count(),
            'total_amount' => $query->sum('total_amount'),
            'applied_amount' => $query->sum('applied_amount'),
            'remaining_amount' => $query->sum('remaining_amount'),
        ];
    }
} 