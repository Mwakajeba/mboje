<?php

namespace App\Services;

use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\GlTransaction;
use App\Models\Cheque;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnclearedItemsService
{
    /**
     * Identify uncleared items for a reconciliation period
     * 
     * @param BankReconciliation $reconciliation
     * @return array
     */
    public function identifyUnclearedItems(BankReconciliation $reconciliation)
    {
        $bankAccountId = $reconciliation->bankAccount->chart_account_id;
        $startDate = $reconciliation->start_date;
        $endDate = $reconciliation->end_date;

        // Get all GL transactions for this bank account in the period
        $glTransactions = GlTransaction::where('chart_account_id', $bankAccountId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        // Get all bank statement items for this reconciliation
        $bankStatementItems = $reconciliation->reconciliationItems()
            ->where('is_bank_statement_item', true)
            ->get();

        // Get existing reconciliation items to avoid duplicates
        $existingGlTransactionIds = $reconciliation->reconciliationItems()
            ->whereNotNull('gl_transaction_id')
            ->pluck('gl_transaction_id')
            ->toArray();

        $unclearedItems = [];

        foreach ($glTransactions as $glTransaction) {
            // Skip if already exists as a reconciliation item (avoid duplicates)
            if (in_array($glTransaction->id, $existingGlTransactionIds)) {
                continue;
            }

            // Check if this GL transaction has a matching bank statement item
            $matched = false;
            
            foreach ($bankStatementItems as $bankItem) {
                if ($this->matchItems($glTransaction, $bankItem)) {
                    $matched = true;
                    break;
                }
            }

            // If not matched, it's an uncleared item
            // DNC = Deposits Not Credited = Receipts = Debit (money coming in)
            // UPC = Unpresented Cheques = Payments = Credit (money going out)
            if (!$matched) {
                $itemType = $glTransaction->nature === 'debit' ? 'DNC' : 'UPC';
                
                $unclearedItems[] = [
                    'gl_transaction_id' => $glTransaction->id,
                    'item_type' => $itemType,
                    'reference' => $glTransaction->description,
                    'description' => $glTransaction->description,
                    'transaction_date' => $glTransaction->date,
                    'amount' => $glTransaction->amount,
                    'nature' => $glTransaction->nature,
                    'is_book_entry' => true,
                    'is_bank_statement_item' => false,
                    'is_reconciled' => false,
                    'uncleared_status' => 'UNCLEARED',
                    'origin_date' => $glTransaction->date,
                    'origin_month' => $glTransaction->date->copy()->startOfMonth(),
                    'origin_reconciliation_id' => $reconciliation->id,
                ];
            }
        }

        // Add cheques in transit (received but not yet deposited) as DNC items
        // These are cheques that were received but haven't been deposited to the bank yet
        $chequesInTransit = \App\Models\Receipt::where('bank_account_id', $reconciliation->bank_account_id)
            ->where('payment_method', 'cheque')
            ->where('cheque_deposited', false)
            ->where('date', '<=', $endDate) // Cheques received on or before end date
            ->where('approved', true) // Only approved receipts
            ->get();

        // Get existing receipt references to avoid duplicates
        $existingReceiptReferences = $reconciliation->reconciliationItems()
            ->whereNotNull('reference')
            ->where('item_type', 'DNC')
            ->pluck('reference')
            ->toArray();

        foreach ($chequesInTransit as $receipt) {
            // Skip if this cheque is already in reconciliation items
            $chequeReference = "Cheque in Transit: {$receipt->reference}";
            if (in_array($chequeReference, $existingReceiptReferences)) {
                continue;
            }

            // Check if this cheque has been matched with a bank statement item
            $matched = false;
            foreach ($bankStatementItems as $bankItem) {
                // Match by amount and date (receipt date within 7 days of bank statement date)
                $amountMatch = abs($receipt->amount - $bankItem->amount) < 0.01;
                $dateDiff = abs($receipt->date->diffInDays($bankItem->transaction_date));
                $dateMatch = $dateDiff <= 7;
                
                // Cheques in transit are receipts (debits), so bank statement should also be debit
                $natureMatch = $bankItem->nature === 'debit';
                
                if ($amountMatch && $dateMatch && $natureMatch) {
                    $matched = true;
                    break;
                }
            }

            // If not matched, add as DNC (Deposit Not Credited)
            if (!$matched) {
                $unclearedItems[] = [
                    'gl_transaction_id' => null, // No GL transaction for cheques in transit (they're in Cheques in Transit Account)
                    'item_type' => 'DNC',
                    'reference' => $chequeReference,
                    'description' => "Cheque in transit: {$receipt->reference} - {$receipt->description}",
                    'transaction_date' => $receipt->date,
                    'amount' => $receipt->amount,
                    'nature' => 'debit', // Cheques in transit are receipts (debits)
                    'is_book_entry' => true, // Technically a book entry (recorded in books via Cheques in Transit Account)
                    'is_bank_statement_item' => false,
                    'is_reconciled' => false,
                    'uncleared_status' => 'UNCLEARED',
                    'origin_date' => $receipt->date,
                    'origin_month' => $receipt->date->copy()->startOfMonth(),
                    'origin_reconciliation_id' => $reconciliation->id,
                ];
            }
        }

        // Add outstanding cheques (issued but not cleared) as UPC items
        // These are cheques that were issued but haven't cleared the bank yet
        $outstandingCheques = Cheque::where('bank_account_id', $reconciliation->bank_account_id)
            ->where('status', 'issued')
            ->where('cheque_date', '<=', $endDate) // Cheques issued on or before end date
            ->get();

        // Get existing cheque references to avoid duplicates
        $existingChequeReferences = $reconciliation->reconciliationItems()
            ->whereNotNull('reference')
            ->where('item_type', 'UPC')
            ->pluck('reference')
            ->toArray();

        foreach ($outstandingCheques as $cheque) {
            // Skip if this cheque is already in reconciliation items
            $chequeReference = "Cheque: {$cheque->cheque_number}";
            if (in_array($chequeReference, $existingChequeReferences)) {
                continue;
            }

            // Check if this cheque has been matched with a bank statement item
            $matched = false;
            foreach ($bankStatementItems as $bankItem) {
                // Match by amount and date (cheque date within 7 days of bank statement date)
                $amountMatch = abs($cheque->amount - $bankItem->amount) < 0.01;
                $dateDiff = abs($cheque->cheque_date->diffInDays($bankItem->transaction_date));
                $dateMatch = $dateDiff <= 7;
                
                // Cheques are payments (credits), so bank statement should also be credit
                $natureMatch = $bankItem->nature === 'credit';
                
                if ($amountMatch && $dateMatch && $natureMatch) {
                    $matched = true;
                    break;
                }
            }

            // If not matched, add as UPC (Unpresented Cheque)
            if (!$matched) {
                $unclearedItems[] = [
                    'gl_transaction_id' => null, // No GL transaction for outstanding cheques (they're in Cheque Issued Account)
                    'item_type' => 'UPC',
                    'reference' => $chequeReference,
                    'description' => "Outstanding cheque: {$cheque->cheque_number} - {$cheque->payee_name}",
                    'transaction_date' => $cheque->cheque_date,
                    'amount' => $cheque->amount,
                    'nature' => 'credit', // Cheques are payments (credits)
                    'is_book_entry' => true, // Technically a book entry (recorded in books via Cheque Issued Account)
                    'is_bank_statement_item' => false,
                    'is_reconciled' => false,
                    'uncleared_status' => 'UNCLEARED',
                    'origin_date' => $cheque->cheque_date,
                    'origin_month' => $cheque->cheque_date->copy()->startOfMonth(),
                    'origin_reconciliation_id' => $reconciliation->id,
                ];
            }
        }

        return $unclearedItems;
    }

    /**
     * Match GL transaction with bank statement item
     * 
     * @param GlTransaction $glTransaction
     * @param BankReconciliationItem $bankItem
     * @return bool
     */
    public function matchItems($glTransaction, BankReconciliationItem $bankItem)
    {
        // Match by amount (must be exact or within tolerance)
        $amountMatch = abs($glTransaction->amount - $bankItem->amount) < 0.01;
        
        // Match by date (within 7 days tolerance)
        $dateDiff = abs($glTransaction->date->diffInDays($bankItem->transaction_date));
        $dateMatch = $dateDiff <= 7;
        
        // Match by nature (debit/credit)
        $natureMatch = $glTransaction->nature === $bankItem->nature;

        return $amountMatch && $dateMatch && $natureMatch;
    }

    /**
     * Carry forward uncleared items from previous reconciliation
     * 
     * @param BankReconciliation $currentReconciliation
     * @param BankReconciliation|null $previousReconciliation
     * @return int Number of items carried forward
     */
    public function carryForwardUnclearedItems(BankReconciliation $currentReconciliation, $previousReconciliation = null)
    {
        if (!$previousReconciliation) {
            // Find the most recent reconciliation for this bank account (not necessarily completed)
            // This ensures we carry forward items even if previous reconciliation is still in progress
            $previousReconciliation = BankReconciliation::where('bank_account_id', $currentReconciliation->bank_account_id)
                ->where('id', '!=', $currentReconciliation->id)  // Exclude current reconciliation
                ->where('end_date', '<', $currentReconciliation->start_date)
                ->orderBy('end_date', 'desc')
                ->first();
        }

        if (!$previousReconciliation) {
            \Log::info('No previous reconciliation found for carry forward', [
                'current_reconciliation_id' => $currentReconciliation->id,
                'bank_account_id' => $currentReconciliation->bank_account_id,
                'start_date' => $currentReconciliation->start_date
            ]);
            return 0;
        }

        \Log::info('Carrying forward items from previous reconciliation', [
            'current_reconciliation_id' => $currentReconciliation->id,
            'previous_reconciliation_id' => $previousReconciliation->id,
            'previous_status' => $previousReconciliation->status,
            'previous_end_date' => $previousReconciliation->end_date
        ]);

        // Get ONLY unreconciled DNC and UPC items from previous reconciliation
        // These are the items that remain uncleared and need to be carried forward
        // IMPORTANT: Only carry forward items that are:
        // - NOT reconciled (is_reconciled = false)
        // - Still UNCLEARED (uncleared_status = 'UNCLEARED')
        // - DNC or UPC type (item_type IN ['DNC', 'UPC'])
        // - Book entries (is_book_entry = true)
        $unclearedItems = BankReconciliationItem::where('bank_reconciliation_id', $previousReconciliation->id)
            ->where('uncleared_status', 'UNCLEARED')  // Still uncleared
            ->where('is_reconciled', false)            // NOT reconciled
            ->where('is_book_entry', true)            // Book entries only
            ->whereIn('item_type', ['DNC', 'UPC'])     // Only DNC and UPC types
            ->get();

        $carriedForwardCount = 0;

        foreach ($unclearedItems as $item) {
            // Skip if this item was already brought forward (avoid duplicates)
            // Check if there's already a brought forward item from this item
            $existingBroughtForward = BankReconciliationItem::where('bank_reconciliation_id', $currentReconciliation->id)
                ->where('brought_forward_from_item_id', $item->id)
                ->first();
            
            if ($existingBroughtForward) {
                continue; // Skip duplicates
            }
            
            // Create a new item in current reconciliation
            $newItem = BankReconciliationItem::create([
                'bank_reconciliation_id' => $currentReconciliation->id,
                'gl_transaction_id' => $item->gl_transaction_id,
                'transaction_type' => $item->transaction_type,
                'item_type' => $item->item_type,
                'reference' => $item->reference,
                'description' => $item->description,
                'transaction_date' => $item->transaction_date,
                'amount' => $item->amount,
                'nature' => $item->nature,
                'is_reconciled' => false,
                'is_bank_statement_item' => false,
                'is_book_entry' => true,
                'uncleared_status' => 'UNCLEARED',
                'origin_date' => $item->origin_date ?? $item->transaction_date,
                'origin_month' => $item->origin_month ?? $item->transaction_date->copy()->startOfMonth(),
                'origin_reconciliation_id' => $item->origin_reconciliation_id ?? $previousReconciliation->id,
                'is_brought_forward' => true,
                'brought_forward_from_item_id' => $item->id,
            ]);

            // Calculate aging
            $newItem->calculateAging();
            $carriedForwardCount++;
            
            \Log::info('Carried forward item', [
                'original_item_id' => $item->id,
                'new_item_id' => $newItem->id,
                'item_type' => $item->item_type,
                'amount' => $item->amount
            ]);
        }

        \Log::info('Carry forward completed', [
            'current_reconciliation_id' => $currentReconciliation->id,
            'carried_forward_count' => $carriedForwardCount
        ]);

        return $carriedForwardCount;
    }

    /**
     * Auto-match uncleared items with bank statement items
     * 
     * @param BankReconciliation $reconciliation
     * @return array Matched items count
     */
    public function autoMatchUnclearedItems(BankReconciliation $reconciliation)
    {
        $unclearedItems = $reconciliation->reconciliationItems()
            ->where('uncleared_status', 'UNCLEARED')
            ->where('is_book_entry', true)
            ->get();

        $bankStatementItems = $reconciliation->reconciliationItems()
            ->where('is_bank_statement_item', true)
            ->where('is_reconciled', false)
            ->get();

        $matchedCount = 0;

        foreach ($unclearedItems as $unclearedItem) {
            foreach ($bankStatementItems as $bankItem) {
                if ($this->matchReconciliationItems($unclearedItem, $bankItem)) {
                    // Match found
                    $unclearedItem->matchWith($bankItem->id);
                    $bankItem->matchWith($unclearedItem->id);
                    
                    // Mark as cleared
                    $unclearedItem->markAsCleared(
                        $bankItem->transaction_date,
                        $bankItem->reference,
                        auth()->id()
                    );
                    
                    $matchedCount++;
                    break;
                }
            }
        }

        return [
            'matched_count' => $matchedCount,
            'remaining_uncleared' => $unclearedItems->count() - $matchedCount,
        ];
    }

    /**
     * Match two reconciliation items
     * 
     * @param BankReconciliationItem $item1
     * @param BankReconciliationItem $item2
     * @return bool
     */
    public function matchReconciliationItems(BankReconciliationItem $item1, BankReconciliationItem $item2)
    {
        // Must be opposite types (one book entry, one bank statement)
        if ($item1->is_book_entry === $item2->is_book_entry) {
            return false;
        }

        // Match by amount
        $amountMatch = abs($item1->amount - $item2->amount) < 0.01;
        
        // Match by date (within 7 days tolerance)
        $dateDiff = abs($item1->transaction_date->diffInDays($item2->transaction_date));
        $dateMatch = $dateDiff <= 7;
        
        // Match by nature (must be opposite for book vs bank)
        // Book entry credit (DNC) should match bank statement credit
        // Book entry debit (UPC) should match bank statement debit
        $natureMatch = $item1->nature === $item2->nature;

        return $amountMatch && $dateMatch && $natureMatch;
    }

    /**
     * Update aging for all uncleared items
     * 
     * @param BankReconciliation $reconciliation
     * @return void
     */
    public function updateAgingForReconciliation(BankReconciliation $reconciliation)
    {
        $unclearedItems = $reconciliation->reconciliationItems()
            ->where('uncleared_status', 'UNCLEARED')
            ->get();

        foreach ($unclearedItems as $item) {
            $item->calculateAging();
        }
    }

    /**
     * Get uncleared items summary for reconciliation
     * 
     * @param BankReconciliation $reconciliation
     * @return array
     */
    public function getUnclearedItemsSummary(BankReconciliation $reconciliation)
    {
        $dncItems = $reconciliation->reconciliationItems()
            ->where('item_type', 'DNC')
            ->where('uncleared_status', 'UNCLEARED')
            ->get();

        $upcItems = $reconciliation->reconciliationItems()
            ->where('item_type', 'UPC')
            ->where('uncleared_status', 'UNCLEARED')
            ->get();

        $broughtForwardItems = $reconciliation->reconciliationItems()
            ->where('is_brought_forward', true)
            ->where('uncleared_status', 'UNCLEARED')
            ->get();

        $totalDNC = $dncItems->sum('amount');
        $totalUPC = $upcItems->sum('amount');
        $totalBroughtForward = $broughtForwardItems->sum('amount');
        
        // Net uncleared amount = DNC (adds to balance) - UPC (subtracts from balance)
        // DNC are receipts (debits) that increase bank balance
        // UPC are payments (credits) that decrease bank balance
        $netUnclearedAmount = $totalDNC - $totalUPC;
        
        return [
            'dnc' => [
                'count' => $dncItems->count(),
                'total_amount' => $totalDNC,
                'items' => $dncItems,
            ],
            'upc' => [
                'count' => $upcItems->count(),
                'total_amount' => $totalUPC,
                'items' => $upcItems,
            ],
            'brought_forward' => [
                'count' => $broughtForwardItems->count(),
                'total_amount' => $totalBroughtForward,
                'items' => $broughtForwardItems,
            ],
            'total_uncleared' => [
                'count' => $dncItems->count() + $upcItems->count(),
                'total_amount' => $netUnclearedAmount, // Net effect: DNC - UPC
            ],
        ];
    }

    /**
     * Process reconciliation items and identify uncleared items
     * 
     * @param BankReconciliation $reconciliation
     * @return void
     */
    public function processReconciliationItems(BankReconciliation $reconciliation)
    {
        \Log::info('Processing reconciliation items', [
            'reconciliation_id' => $reconciliation->id,
            'start_date' => $reconciliation->start_date,
            'end_date' => $reconciliation->end_date
        ]);

        // First, carry forward uncleared items from previous month
        $carriedForwardCount = $this->carryForwardUnclearedItems($reconciliation);
        
        \Log::info('Carried forward items count', [
            'reconciliation_id' => $reconciliation->id,
            'carried_forward_count' => $carriedForwardCount
        ]);

        // Identify new uncleared items for this period
        $unclearedItems = $this->identifyUnclearedItems($reconciliation);
        
        \Log::info('Identified new uncleared items', [
            'reconciliation_id' => $reconciliation->id,
            'new_uncleared_items_count' => count($unclearedItems)
        ]);

        // Create reconciliation items for uncleared items
        foreach ($unclearedItems as $itemData) {
            $itemData['bank_reconciliation_id'] = $reconciliation->id;
            BankReconciliationItem::create($itemData);
        }

        // Update aging for all uncleared items
        $this->updateAgingForReconciliation($reconciliation);

        // Verify items are present
        $totalUnreconciledItems = $reconciliation->reconciliationItems()
            ->where('is_book_entry', true)
            ->where('is_reconciled', false)
            ->count();
        
        $broughtForwardItems = $reconciliation->reconciliationItems()
            ->where('is_brought_forward', true)
            ->where('is_reconciled', false)
            ->count();
        
        \Log::info('Reconciliation items processing completed', [
            'reconciliation_id' => $reconciliation->id,
            'total_unreconciled_items' => $totalUnreconciledItems,
            'brought_forward_items' => $broughtForwardItems,
            'carried_forward_count' => $carriedForwardCount,
            'new_items_count' => count($unclearedItems)
        ]);

        // Try auto-matching
        $this->autoMatchUnclearedItems($reconciliation);
    }
}

