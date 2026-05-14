<?php

namespace App\Services;

use App\Models\Purchase\DebitNote;
use App\Models\Purchase\DebitNoteItem;
use App\Models\Purchase\DebitNoteApplication;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\PurchaseInvoiceItem;
use App\Models\Inventory\Item as InventoryItem;
use App\Models\Inventory\Movement as InventoryMovement;
use App\Models\Supplier;
use App\Models\GlTransaction;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class DebitNoteService
{
    /**
     * Create a debit note with comprehensive scenario handling
     */
    public function createDebitNote(array $data)
    {
        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            
            // Validate scenario-specific requirements
            $this->validateScenarioRequirements($data);
            
            // Create debit note
            $debitNoteData = $this->prepareDebitNoteData($data, $user);
            $debitNote = DebitNote::create($debitNoteData);
            
            // Create debit note items
            $this->createDebitNoteItems($debitNote, $data['items'], $user);
            
            // Calculate and update totals
            $this->calculateTotals($debitNote);
            
            // Calculate restocking fees if applicable
            if ($debitNote->restocking_fee_percentage > 0) {
                $this->calculateRestockingFees($debitNote);
            }
            
            // Process FX adjustments if applicable
            if ($debitNote->currency !== 'TZS') {
                $this->processFxAdjustments($debitNote);
            }
            
            // Create GL transactions if auto-approve is enabled
            if ($this->shouldAutoApprove($debitNote)) {
                $debitNote->approve();
            }
            
            DB::commit();
            
            return $debitNote;
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing debit note
     */
    public function updateDebitNote(DebitNote $debitNote, array $data)
    {
        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            
            // Update debit note
            $debitNoteData = $this->prepareDebitNoteData($data, $user);
            $debitNote->update($debitNoteData);
            
            // Delete existing items
            $debitNote->items()->delete();
            
            // Create new items
            $this->createDebitNoteItems($debitNote, $data['items'], $user);
            
            // Recalculate totals
            $this->calculateTotals($debitNote);
            
            // Recalculate restocking fees if applicable
            if ($debitNote->restocking_fee_percentage > 0) {
                $this->calculateRestockingFees($debitNote);
            }
            
            DB::commit();
            
            return $debitNote;
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a debit note
     */
    public function deleteDebitNote(DebitNote $debitNote)
    {
        DB::beginTransaction();
        
        try {
            // Delete inventory movements for this debit note (this handles stock reversal)
            InventoryMovement::where('reference_type', 'debit_note')
                ->where('reference_id', $debitNote->id)
                ->delete();
            
            // Delete related records
            $debitNote->items()->delete();
            $debitNote->applications()->delete();
            
            // Delete GL transactions
            GlTransaction::where('transaction_type', 'debit_note')
                ->where('transaction_id', $debitNote->id)
                ->delete();
            
            // Delete the debit note
            $debitNote->delete();
            
            DB::commit();
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Approve a debit note
     */
    public function approveDebitNote(DebitNote $debitNote)
    {
        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            
            // Update status
            $debitNote->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
            
            // Create GL transactions
            $this->createGlTransactions($debitNote);
            
            // Update inventory if return to stock
            if ($debitNote->return_to_stock) {
                $this->updateInventory($debitNote);
            }
            
            DB::commit();
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Apply a debit note
     */
    public function applyDebitNote(DebitNote $debitNote, array $data)
    {
        DB::beginTransaction();
        
        try {
            $user = Auth::user();
            
            // Create application
            $application = DebitNoteApplication::create([
                'debit_note_id' => $debitNote->id,
                'purchase_invoice_id' => $data['purchase_invoice_id'] ?? null,
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'amount_applied' => $data['amount_applied'],
                'application_type' => $data['application_type'],
                'application_date' => $data['application_date'],
                'description' => $data['description'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
                'branch_id' => $user->branch_id,
                'company_id' => $user->company_id,
            ]);
            
            // Create GL transactions for application
            $application->createGlTransactions();
            
            // Update debit note applied amount
            $debitNote->increment('applied_amount', $data['amount_applied']);
            $debitNote->decrement('remaining_amount', $data['amount_applied']);
            
            // Update status if fully applied
            if ($debitNote->remaining_amount <= 0) {
                $debitNote->update(['status' => 'applied']);
            }
            
            DB::commit();
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancel a debit note
     */
    public function cancelDebitNote(DebitNote $debitNote)
    {
        DB::beginTransaction();
        
        try {
            // Update status
            $debitNote->update(['status' => 'cancelled']);
            
            // Delete GL transactions
            GlTransaction::where('transaction_type', 'debit_note')
                ->where('transaction_id', $debitNote->id)
                ->delete();
            
            // Revert inventory changes if any
            if ($debitNote->return_to_stock && $debitNote->status === 'approved') {
                $this->revertInventoryChanges($debitNote);
            }
            
            DB::commit();
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate scenario-specific requirements
     */
    private function validateScenarioRequirements(array $data)
    {
        // Add validation logic based on debit note type
        switch ($data['type']) {
            case 'return':
                // Validate that items exist in inventory
                break;
            case 'discount':
                // Validate discount amount
                break;
            case 'correction':
                // Validate reference invoice
                break;
        }
    }

    /**
     * Prepare debit note data for creation/update
     */
    private function prepareDebitNoteData(array $data, $user)
    {
        return [
            'purchase_invoice_id' => $data['purchase_invoice_id'] ?? null,
            'reference_invoice_id' => $data['reference_invoice_id'] ?? null,
            'supplier_id' => $data['supplier_id'],
            'debit_note_date' => $data['debit_note_date'],
            'type' => $data['type'],
            'reason_code' => $data['reason_code'] ?? null,
            'reason' => $data['reason'],
            'notes' => $data['notes'] ?? null,
            'terms_conditions' => $data['terms_conditions'] ?? null,
            'attachment' => $data['attachment'] ?? null,
            'refund_now' => $data['refund_now'] ?? false,
            'return_to_stock' => $data['return_to_stock'] ?? true,
            'restocking_fee_percentage' => $data['restocking_fee_percentage'] ?? 0,
            'currency' => $data['currency'] ?? 'TZS',
            'exchange_rate' => $data['exchange_rate'] ?? 1.0,
            'reference_document' => $data['reference_document'] ?? null,
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'status' => 'draft',
            'branch_id' => $user->branch_id,
            'company_id' => $user->company_id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ];
    }

    /**
     * Create debit note items
     */
    private function createDebitNoteItems(DebitNote $debitNote, array $items, $user)
    {
        foreach ($items as $itemData) {
            $item = DebitNoteItem::create([
                'debit_note_id' => $debitNote->id,
                'purchase_invoice_item_id' => $itemData['purchase_invoice_item_id'] ?? null,
                'linked_invoice_line_id' => $itemData['linked_invoice_line_id'] ?? null,
                'inventory_item_id' => $itemData['inventory_item_id'] ?? null,
                'warehouse_id' => $itemData['warehouse_id'] ?? null,
                'item_name' => $itemData['item_name'],
                'item_code' => $itemData['item_code'] ?? null,
                'description' => $itemData['description'] ?? null,
                'unit_of_measure' => $itemData['unit_of_measure'] ?? null,
                'quantity' => $itemData['quantity'],
                'unit_cost' => $itemData['unit_cost'],
                'vat_type' => $itemData['vat_type'] ?? 'inclusive',
                'vat_rate' => $itemData['vat_rate'] ?? 18,
                'discount_type' => $itemData['discount_type'] ?? 'none',
                'discount_rate' => $itemData['discount_rate'] ?? 0,
                'return_to_stock' => $itemData['return_to_stock'] ?? true,
                'return_condition' => $itemData['return_condition'] ?? 'resellable',
                'notes' => $itemData['notes'] ?? null,
            ]);
            
            // Calculate line total
            $item->calculateLineTotal();
            $item->save();
        }
    }

    /**
     * Calculate totals for debit note
     */
    private function calculateTotals(DebitNote $debitNote)
    {
        $subtotal = $debitNote->items()->sum('line_total');
        $vatAmount = $debitNote->items()->sum('vat_amount');
        $discountAmount = $debitNote->items()->sum('discount_amount');
        
        $totalAmount = $subtotal + $vatAmount - $discountAmount;
        
        $debitNote->update([
            'subtotal' => $subtotal,
            'vat_amount' => $vatAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'remaining_amount' => $totalAmount,
        ]);
    }

    /**
     * Calculate restocking fees
     */
    private function calculateRestockingFees(DebitNote $debitNote)
    {
        $restockingFeeAmount = $debitNote->subtotal * ($debitNote->restocking_fee_percentage / 100);
        $restockingFeeVat = $restockingFeeAmount * 0.18; // Assuming 18% VAT
        
        $debitNote->update([
            'restocking_fee_amount' => $restockingFeeAmount,
            'restocking_fee_vat' => $restockingFeeVat,
            'total_amount' => $debitNote->total_amount + $restockingFeeAmount + $restockingFeeVat,
        ]);
    }

    /**
     * Process FX adjustments
     */
    private function processFxAdjustments(DebitNote $debitNote)
    {
        // Implement FX adjustment logic
        $fxGainLoss = 0; // Calculate based on exchange rate changes
        
        $debitNote->update([
            'fx_gain_loss' => $fxGainLoss,
            'total_amount' => $debitNote->total_amount + $fxGainLoss,
        ]);
    }

    /**
     * Check if debit note should be auto-approved
     */
    private function shouldAutoApprove(DebitNote $debitNote)
    {
        // Check system settings for auto-approval
        $autoApprove = SystemSetting::where('key', 'auto_approve_debit_notes')->value('value');
            
        return $autoApprove === '1' || $autoApprove === 'true';
    }

    /**
     * Create GL transactions for approved debit note
     */
    private function createGlTransactions(DebitNote $debitNote)
    {
        // Delete existing transactions
        GlTransaction::where('transaction_type', 'debit_note')
            ->where('transaction_id', $debitNote->id)
            ->delete();
        
        $user = Auth::user();
        
        // Get account IDs from system settings
        $supplierPayableAccountId = $this->getSupplierPayableAccountId($debitNote->company_id);
        $inventoryAccountId = $this->getInventoryAccountId($debitNote->company_id);
        $vatInputAccountId = $this->getVatInputAccountId($debitNote->company_id);
        $purchaseDiscountAccountId = $this->getPurchaseDiscountAccountId($debitNote->company_id);
        
        $transactions = [];
        
        // Debit: Supplier Payable (increase liability)
        $transactions[] = [
            'chart_account_id' => $supplierPayableAccountId,
            'supplier_id' => $debitNote->supplier_id,
            'amount' => $debitNote->total_amount,
            'nature' => 'debit',
            'transaction_id' => $debitNote->id,
            'transaction_type' => 'debit_note',
            'date' => $debitNote->debit_note_date,
            'description' => "Debit Note #{$debitNote->debit_note_number} - {$debitNote->reason}",
            'branch_id' => $debitNote->branch_id,
            'user_id' => $user->id,
        ];
        
        // Credit: Inventory (decrease asset)
        $transactions[] = [
            'chart_account_id' => $inventoryAccountId,
            'amount' => $debitNote->subtotal,
            'nature' => 'credit',
            'transaction_id' => $debitNote->id,
            'transaction_type' => 'debit_note',
            'date' => $debitNote->debit_note_date,
            'description' => "Debit Note #{$debitNote->debit_note_number} - Inventory Return",
            'branch_id' => $debitNote->branch_id,
            'user_id' => $user->id,
        ];
        
        // Credit: VAT Input (decrease asset)
        if ($debitNote->vat_amount > 0) {
            $transactions[] = [
                'chart_account_id' => $vatInputAccountId,
                'amount' => $debitNote->vat_amount,
                'nature' => 'credit',
                'transaction_id' => $debitNote->id,
                'transaction_type' => 'debit_note',
                'date' => $debitNote->debit_note_date,
                'description' => "Debit Note #{$debitNote->debit_note_number} - VAT Input",
                'branch_id' => $debitNote->branch_id,
                'user_id' => $user->id,
            ];
        }

        // Credit: Purchase Discount (reduces expense/cost) if invoice-level discount exists
        if ($debitNote->discount_amount > 0) {
            $transactions[] = [
                'chart_account_id' => $purchaseDiscountAccountId,
                'amount' => $debitNote->discount_amount,
                'nature' => 'credit',
                'transaction_id' => $debitNote->id,
                'transaction_type' => 'debit_note',
                'date' => $debitNote->debit_note_date,
                'description' => "Debit Note #{$debitNote->debit_note_number} - Purchase Discount",
                'branch_id' => $debitNote->branch_id,
                'user_id' => $user->id,
            ];
        }
        
        // Create all transactions
        foreach ($transactions as $transaction) {
            GlTransaction::create($transaction);
        }
    }

    /**
     * Update inventory for returned items
     */
    private function updateInventory(DebitNote $debitNote)
    {
        foreach ($debitNote->items as $item) {
            if ($item->return_to_stock && $item->inventory_item_id) {
                // Decrease inventory
                $inventoryItem = InventoryItem::find($item->inventory_item_id);
                if ($inventoryItem) {
                    $inventoryItem->decrement('current_stock', $item->quantity);
                    
                    // Create inventory movement
                    InventoryMovement::create([
                        'item_id' => $item->inventory_item_id,
                        'movement_type' => 'adjustment_out',
                        'quantity' => -$item->quantity, // Negative for return (stock decreases on debit note)
                        'unit_cost' => $item->unit_cost,
                        'total_cost' => $item->quantity * $item->unit_cost,
                        'reference_type' => 'debit_note',
                        'reference_id' => $debitNote->id,
                        'notes' => "Return from Debit Note #{$debitNote->debit_note_number}",
                        'branch_id' => $debitNote->branch_id,
                        'user_id' => Auth::id(),
                    ]);
                }
            }
        }
    }

    /**
     * Revert inventory changes
     */
    private function revertInventoryChanges(DebitNote $debitNote)
    {
        foreach ($debitNote->items as $item) {
            if ($item->return_to_stock && $item->inventory_item_id) {
                // Increase inventory back
                $inventoryItem = InventoryItem::find($item->inventory_item_id);
                if ($inventoryItem) {
                    $inventoryItem->increment('current_stock', $item->quantity);
                }
            }
        }
    }

    /**
     * Get supplier payable account ID
     */
    private function getSupplierPayableAccountId($companyId)
    {
        // Reuse existing system setting key used by purchase invoices
        $setting = SystemSetting::where('key', 'inventory_default_purchase_payable_account')->value('value');
        return (int)($setting ?: 30); // default Trade Payables
    }

    /**
     * Get inventory account ID
     */
    private function getInventoryAccountId($companyId)
    {
        $setting = SystemSetting::where('key', 'inventory_default_inventory_account')->value('value');
        return (int)($setting ?: 185); // default Inventory
    }

    /**
     * Get VAT input account ID
     */
    private function getVatInputAccountId($companyId)
    {
        $setting = SystemSetting::where('key', 'inventory_default_vat_account')->value('value');
        return (int)($setting ?: 60); // default VAT Payable/Input
    }

    /**
     * Get purchase discount account ID (income/contra-expense)
     */
    private function getPurchaseDiscountAccountId($companyId)
    {
        $setting = SystemSetting::where('key', 'inventory_default_discount_income_account')->value('value');
        return (int)($setting ?: 0); // 0 means skip if not configured
    }
}
