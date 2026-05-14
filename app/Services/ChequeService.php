<?php

namespace App\Services;

use App\Models\Cheque;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\ChartAccount;
use App\Models\BankAccount;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ChequeService
{
    /**
     * Issue a cheque (create cheque record and journal entry)
     * 
     * @param array $data Cheque data
     * @return Cheque
     */
    public function issueCheque(array $data)
    {
        DB::beginTransaction();
        try {
            // Validate cheque number uniqueness
            if (!Cheque::isChequeNumberUnique($data['cheque_number'], $data['bank_account_id'])) {
                throw new \Exception('Cheque number already exists for this bank account.');
            }

            // Create cheque record
            $cheque = Cheque::create([
                'cheque_number' => $data['cheque_number'],
                'cheque_date' => $data['cheque_date'],
                'bank_account_id' => $data['bank_account_id'],
                'payee_name' => $data['payee_name'],
                'amount' => $data['amount'],
                'status' => 'issued',
                'payment_reference_type' => $data['payment_reference_type'] ?? null,
                'payment_reference_id' => $data['payment_reference_id'] ?? null,
                'payment_reference_number' => $data['payment_reference_number'] ?? null,
                'module_origin' => $data['module_origin'] ?? null,
                'payment_type' => $data['payment_type'] ?? null,
                'description' => $data['description'] ?? null,
                'company_id' => $data['company_id'],
                'branch_id' => $data['branch_id'] ?? null,
                'issued_by' => $data['issued_by'] ?? auth()->id(),
            ]);

            // Create journal entry: Dr Expense/Payable, Cr Bank Cheque Issued (Pending)
            $journal = $this->createIssueJournal($cheque, $data);

            $cheque->issue_journal_id = $journal->id;
            $cheque->save();

            DB::commit();

            return $cheque;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cheque issue error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create journal entry when cheque is issued
     */
    protected function createIssueJournal(Cheque $cheque, array $data)
    {
        $bankAccount = BankAccount::findOrFail($cheque->bank_account_id);
        
        // Get cheque issued account (contra account for bank)
        $chequeIssuedAccountId = SystemSetting::getValue('cheque_issued_account_id');
        if (!$chequeIssuedAccountId) {
            // Fallback: try to find by name
            $chequeIssuedAccount = ChartAccount::where('account_name', 'LIKE', '%cheque issued%')
                ->orWhere('account_name', 'LIKE', '%outstanding cheque%')
                ->first();
            
            if (!$chequeIssuedAccount) {
                throw new \Exception('Cheque Issued account not configured. Please set cheque_issued_account_id in system settings.');
            }
            $chequeIssuedAccountId = $chequeIssuedAccount->id;
        }

        // Get expense/payable account from data
        $expenseAccountId = $data['expense_account_id'] ?? $data['payable_account_id'] ?? null;
        if (!$expenseAccountId) {
            throw new \Exception('Expense or Payable account is required for cheque issuance.');
        }

        // Create journal
        $journal = Journal::create([
            'date' => $cheque->cheque_date,
            'reference' => 'CHQ-' . $cheque->cheque_number,
            'reference_type' => 'Cheque Issued',
            'description' => "Cheque issued: {$cheque->cheque_number} - {$cheque->payee_name}",
            'branch_id' => $cheque->branch_id ?? auth()->user()->branch_id,
            'user_id' => $cheque->issued_by ?? auth()->id(),
            'approved' => true,
            'approved_by' => $cheque->issued_by ?? auth()->id(),
            'approved_at' => now(),
        ]);

        // Dr. Expense/Payable
        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $expenseAccountId,
            'amount' => $cheque->amount,
            'nature' => 'debit',
            'description' => "Cheque payment: {$cheque->cheque_number}",
        ]);

        // Cr. Cheque Issued (Pending Clearance) - This is a contra account
        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $chequeIssuedAccountId,
            'amount' => $cheque->amount,
            'nature' => 'credit',
            'description' => "Cheque issued pending clearance: {$cheque->cheque_number}",
        ]);

        // Initialize approval workflow and create GL transactions
        $journal->initializeApprovalWorkflow();
        $journal->createGlTransactions();

        return $journal;
    }

    /**
     * Clear a cheque (when bank confirms it's cleared)
     */
public function clearCheque(Cheque $cheque, $clearedBy = null)
    {
        if (!$cheque->canBeCleared()) {
            throw new \Exception('Cheque cannot be cleared in current status.');
        }

        // Check if cheque is already cleared (prevent duplicate clearing)
        if ($cheque->status === 'cleared' && $cheque->clear_journal_id) {
            throw new \Exception('This cheque has already been cleared. Clear journal ID: ' . $cheque->clear_journal_id);
        }

        DB::beginTransaction();
        try {
            // Double-check status inside transaction to prevent race conditions
            $cheque->refresh();
            if ($cheque->status === 'cleared' && $cheque->clear_journal_id) {
                DB::rollBack();
                throw new \Exception('This cheque has already been cleared. Please refresh the page.');
            }

            // Create journal entry: Dr Cheque Issued Clearing, Cr Bank Account
            // This creates journal and items inside the transaction
            $journal = $this->createClearJournal($cheque);

            $cheque->status = 'cleared';
            $cheque->cleared_date = now();
            $cheque->cleared_by = $clearedBy ?? auth()->id();
            $cheque->clear_journal_id = $journal->id;
            $cheque->save();

            // Commit transaction BEFORE creating GL transactions
            // This ensures the cheque is saved even if GL transactions fail
            DB::commit();

            // Create GL transactions AFTER transaction commits
            // This is safe because journal and items are already created
            // If GL transactions fail, the cheque is still cleared and can be fixed later
            try {
                // Reload journal with all necessary relationships
                $journal->refresh();
                $journal->load(['user', 'items']);
                
                // Check if journal has items
                if ($journal->items->isEmpty()) {
                    Log::warning('Cheque clear journal has no items - cannot create GL transactions', [
                        'journal_id' => $journal->id,
                        'cheque_id' => $cheque->id,
                        'journal_reference' => $journal->reference,
                    ]);
                } else {
                    // Check if GL transactions already exist to prevent duplicates
                    $existingGlTransactions = \App\Models\GlTransaction::where('transaction_id', $journal->id)
                        ->where('transaction_type', 'journal')
                        ->count();

                    if ($existingGlTransactions > 0) {
                        Log::info('GL transactions already exist for cheque clear journal - skipping duplicate creation', [
                            'journal_id' => $journal->id,
                            'cheque_id' => $cheque->id,
                            'journal_reference' => $journal->reference,
                            'existing_count' => $existingGlTransactions,
                        ]);
                    } else {
                        // Ensure user is loaded (needed for createGlTransactions)
                        if (!$journal->user) {
                            Log::error('Journal user not found - cannot create GL transactions', [
                                'journal_id' => $journal->id,
                                'user_id' => $journal->user_id,
                            ]);
                        } else {
                            // Only create GL transactions if they don't exist
                            $journal->createGlTransactions();
                            
                            Log::info('GL transactions created successfully for cheque clear journal', [
                                'journal_id' => $journal->id,
                                'cheque_id' => $cheque->id,
                                'journal_reference' => $journal->reference,
                            ]);
                        }
                    }
                }
            } catch (\Exception $glException) {
                // Log the error but don't fail the cheque clearing
                // The cheque is already cleared and saved, GL transactions can be fixed later
                Log::error('Failed to create GL transactions for cheque clear journal (cheque still cleared)', [
                    'journal_id' => $journal->id,
                    'cheque_id' => $cheque->id,
                    'journal_reference' => $journal->reference,
                    'error' => $glException->getMessage(),
                    'trace' => $glException->getTraceAsString(),
                ]);
                // Don't throw - cheque is already cleared, GL transactions can be fixed later
            }

            // Refresh cheque to ensure we have the latest status before returning
            $cheque->refresh();
            
            // Log success - cheque should be cleared at this point
            Log::info('Cheque cleared successfully', [
                'cheque_id' => $cheque->id,
                'cheque_number' => $cheque->cheque_number,
                'status' => $cheque->status,
                'clear_journal_id' => $cheque->clear_journal_id,
                'journal_reference' => $journal->reference,
            ]);

            return $cheque;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cheque clear error: ' . $e->getMessage(), [
                'cheque_id' => $cheque->id,
                'cheque_number' => $cheque->cheque_number,
                'current_status' => $cheque->status,
                'clear_journal_id' => $cheque->clear_journal_id,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Create journal entry when cheque is cleared
     */
    protected function createClearJournal(Cheque $cheque)
    {
        // Ensure bank account is loaded
        if (!$cheque->relationLoaded('bankAccount')) {
            $cheque->load('bankAccount');
        }
        
        $bankAccount = $cheque->bankAccount;
        if (!$bankAccount) {
            throw new \Exception('Bank account not found for this cheque. Please ensure the cheque is linked to a valid bank account.');
        }
        
        if (!$bankAccount->chart_account_id) {
            throw new \Exception('Bank account does not have a chart account configured. Please set the chart account for the bank account: ' . $bankAccount->name);
        }
        
        // Get cheque issued account
        $chequeIssuedAccountId = SystemSetting::getValue('cheque_issued_account_id');
        if (!$chequeIssuedAccountId) {
            // Try to find by name - filter by company if possible
            $companyId = auth()->user()->company_id ?? null;
            $query = ChartAccount::where(function($q) {
                $q->where('account_name', 'LIKE', '%cheque issued%')
                  ->orWhere('account_name', 'LIKE', '%outstanding cheque%');
            });
            
            // Filter by company through account class group if company_id is available
            if ($companyId) {
                $query->whereHas('accountClassGroup', function($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                });
            }
            
            $chequeIssuedAccount = $query->first();
            
            if (!$chequeIssuedAccount) {
                throw new \Exception('Cheque Issued account not configured. Please set cheque_issued_account_id in system settings or create a chart account with "cheque issued" or "outstanding cheque" in the name.');
            }
            $chequeIssuedAccountId = $chequeIssuedAccount->id;
        }

        $user = auth()->user();
        if (!$user) {
            throw new \Exception('User not authenticated.');
        }

        // Check if a clear journal already exists for this cheque (prevent duplicates)
        $journalReference = 'CHQ-CLEAR-' . $cheque->cheque_number;
        $existingJournal = Journal::where('reference', $journalReference)
            ->where('reference_type', 'Cheque Cleared')
            ->first();

        if ($existingJournal) {
            throw new \Exception('A clear journal entry already exists for this cheque. Journal ID: ' . $existingJournal->id);
        }

        // Double-check if cheque already has a clear_journal_id
        if ($cheque->clear_journal_id) {
            throw new \Exception('This cheque already has a clear journal assigned. Clear journal ID: ' . $cheque->clear_journal_id);
        }

        // Create journal
        $journal = Journal::create([
            'date' => now(),
            'reference' => $journalReference,
            'reference_type' => 'Cheque Cleared',
            'description' => "Cheque cleared: {$cheque->cheque_number} - {$cheque->payee_name}",
            'branch_id' => $cheque->branch_id ?? $user->branch_id,
            'user_id' => $user->id,
            'approved' => true,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        // Dr. Cheque Issued Clearing (reduce the contra account)
        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $chequeIssuedAccountId,
            'amount' => $cheque->amount,
            'nature' => 'debit',
            'description' => "Cheque cleared: {$cheque->cheque_number}",
        ]);

        // Cr. Bank Account (reduce bank balance)
        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $bankAccount->chart_account_id,
            'amount' => $cheque->amount,
            'nature' => 'credit',
            'description' => "Cheque cleared: {$cheque->cheque_number}",
        ]);

        // Note: We don't create GL transactions here anymore
        // GL transactions are created AFTER the transaction commits in clearCheque()
        // This ensures the cheque is saved even if GL transactions fail
        // Just create the journal and items, return the journal
        
        return $journal;
    }

    /**
     * Fix duplicate GL transactions for a cleared cheque's clear journal
     * This keeps only one pair of GL transactions (one debit + one credit) and deletes duplicates
     */
    public function fixDuplicateGlTransactions(Cheque $cheque)
    {
        if (!$cheque->clear_journal_id) {
            throw new \Exception('This cheque does not have a clear journal. It may not have been cleared yet.');
        }

        $clearJournal = Journal::find($cheque->clear_journal_id);
        if (!$clearJournal) {
            throw new \Exception('Clear journal not found for this cheque. Journal ID: ' . $cheque->clear_journal_id);
        }

        // Load journal items to know what GL transactions should exist
        $clearJournal->load('items');
        
        if ($clearJournal->items->isEmpty()) {
            throw new \Exception('Clear journal has no items. Journal ID: ' . $clearJournal->id);
        }

        DB::beginTransaction();
        try {
            // Get all GL transactions for this clear journal
            $glTransactions = \App\Models\GlTransaction::where('transaction_id', $clearJournal->id)
                ->where('transaction_type', 'journal')
                ->orderBy('id')
                ->get();

            if ($glTransactions->isEmpty()) {
                DB::rollBack();
                throw new \Exception('No GL transactions found for this clear journal. Journal ID: ' . $clearJournal->id);
            }

            Log::info('Fixing duplicate GL transactions for cheque clear journal', [
                'cheque_id' => $cheque->id,
                'cheque_number' => $cheque->cheque_number,
                'journal_id' => $clearJournal->id,
                'journal_reference' => $clearJournal->reference,
                'total_gl_transactions' => $glTransactions->count(),
                'expected_count' => $clearJournal->items->count(),
            ]);

            $deletedCount = 0;
            $keptGlTransactionIds = [];

            // Group GL transactions by chart_account_id, amount, and nature (to identify duplicates)
            $glTransactionsByKey = [];
            foreach ($glTransactions as $glTrans) {
                $key = $glTrans->chart_account_id . '_' . $glTrans->amount . '_' . $glTrans->nature;
                if (!isset($glTransactionsByKey[$key])) {
                    $glTransactionsByKey[$key] = [];
                }
                $glTransactionsByKey[$key][] = $glTrans;
            }

            // For each group, keep the first one and delete the rest
            foreach ($glTransactionsByKey as $key => $groupedGlTransactions) {
                if (count($groupedGlTransactions) > 1) {
                    // Keep the first one (oldest by ID)
                    $firstGlTransaction = $groupedGlTransactions[0];
                    $keptGlTransactionIds[] = $firstGlTransaction->id;
                    
                    // Delete the rest (duplicates)
                    $duplicates = array_slice($groupedGlTransactions, 1);
                    $duplicateIds = array_map(function($glTrans) {
                        return $glTrans->id;
                    }, $duplicates);
                    
                    \App\Models\GlTransaction::whereIn('id', $duplicateIds)->delete();
                    $deletedCount += count($duplicates);
                    
                    Log::info('Fixed duplicate GL transactions for cheque clear journal', [
                        'cheque_id' => $cheque->id,
                        'journal_id' => $clearJournal->id,
                        'chart_account_id' => $firstGlTransaction->chart_account_id,
                        'amount' => $firstGlTransaction->amount,
                        'nature' => $firstGlTransaction->nature,
                        'kept_gl_transaction_id' => $firstGlTransaction->id,
                        'deleted_gl_transaction_ids' => $duplicateIds,
                        'deleted_count' => count($duplicates),
                    ]);
                } else {
                    // Only one GL transaction in this group, keep it
                    $keptGlTransactionIds[] = $groupedGlTransactions[0]->id;
                }
            }

            // Verify we have the correct number of GL transactions (should match journal items)
            $remainingGlTransactions = \App\Models\GlTransaction::where('transaction_id', $clearJournal->id)
                ->where('transaction_type', 'journal')
                ->count();

            if ($remainingGlTransactions !== $clearJournal->items->count()) {
                Log::warning('GL transaction count mismatch after fixing duplicates', [
                    'cheque_id' => $cheque->id,
                    'journal_id' => $clearJournal->id,
                    'expected_count' => $clearJournal->items->count(),
                    'actual_count' => $remainingGlTransactions,
                ]);
            }

            DB::commit();

            Log::info('Successfully fixed duplicate GL transactions for cheque clear journal', [
                'cheque_id' => $cheque->id,
                'cheque_number' => $cheque->cheque_number,
                'journal_id' => $clearJournal->id,
                'deleted_duplicates' => $deletedCount,
                'remaining_gl_transactions' => $remainingGlTransactions,
            ]);

            return [
                'success' => true,
                'deleted_count' => $deletedCount,
                'remaining_count' => $remainingGlTransactions,
                'expected_count' => $clearJournal->items->count(),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error fixing duplicate GL transactions for cheque clear journal', [
                'cheque_id' => $cheque->id,
                'cheque_number' => $cheque->cheque_number,
                'journal_id' => $clearJournal->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Bounce a cheque (reverse the payment)
     */
    public function bounceCheque(Cheque $cheque, $reason, $bouncedBy = null)
    {
        if ($cheque->status !== 'issued') {
            throw new \Exception('Only issued cheques can be bounced.');
        }

        DB::beginTransaction();
        try {
            // Create journal entry to reverse the original payment
            $journal = $this->createBounceJournal($cheque, $reason);

            $cheque->status = 'bounced';
            $cheque->bounced_date = now();
            $cheque->bounce_reason = $reason;
            $cheque->bounce_journal_id = $journal->id;
            $cheque->save();

            DB::commit();

            return $cheque;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cheque bounce error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create journal entry when cheque bounces
     */
    protected function createBounceJournal(Cheque $cheque, $reason)
    {
        // Get accounts
        $companyId = auth()->user()->company_id ?? null;
        $chequeIssuedAccountId = SystemSetting::getValue('cheque_issued_account_id');
        if (!$chequeIssuedAccountId) {
            $query = ChartAccount::where(function($q) {
                $q->where('account_name', 'LIKE', '%cheque issued%')
                  ->orWhere('account_name', 'LIKE', '%outstanding cheque%');
            });
            
            // Filter by company through account class group if company_id is available
            if ($companyId) {
                $query->whereHas('accountClassGroup', function($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                });
            }
            
            $chequeIssuedAccount = $query->first();
            if (!$chequeIssuedAccount) {
                throw new \Exception('Cheque Issued account not configured. Please set cheque_issued_account_id in system settings or create a chart account with "cheque issued" or "outstanding cheque" in the name.');
            }
            $chequeIssuedAccountId = $chequeIssuedAccount->id;
        }

        // Get original expense/payable account from issue journal
        if (!$cheque->relationLoaded('issueJournal')) {
            $cheque->load('issueJournal');
        }
        
        $issueJournal = $cheque->issueJournal;
        if (!$issueJournal) {
            throw new \Exception('Issue journal not found for this cheque. The cheque must have been issued first.');
        }

        // Ensure issue journal items are loaded
        if (!$issueJournal->relationLoaded('items')) {
            $issueJournal->load('items');
        }

        $expenseItem = $issueJournal->items()->where('nature', 'debit')->where('chart_account_id', '!=', $chequeIssuedAccountId)->first();
        if (!$expenseItem) {
            throw new \Exception('Expense or Payable account not found in issue journal.');
        }

        $user = auth()->user();
        if (!$user) {
            throw new \Exception('User not authenticated.');
        }

        // Create journal
        $journal = Journal::create([
            'date' => now(),
            'reference' => 'CHQ-BOUNCE-' . $cheque->cheque_number,
            'reference_type' => 'Cheque Bounced',
            'description' => "Cheque bounced: {$cheque->cheque_number} - {$cheque->payee_name}. Reason: {$reason}",
            'branch_id' => $cheque->branch_id ?? $user->branch_id,
            'user_id' => $user->id,
            'approved' => true,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        // Dr. Cheque Issued (reverse the credit)
        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $chequeIssuedAccountId,
            'amount' => $cheque->amount,
            'nature' => 'debit',
            'description' => "Cheque bounced: {$cheque->cheque_number}",
        ]);

        // Cr. Expense/Payable (reverse the original debit)
        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $expenseItem->chart_account_id,
            'amount' => $cheque->amount,
            'nature' => 'credit',
            'description' => "Cheque bounced reversal: {$cheque->cheque_number}",
        ]);

        // Reload journal with user relationship for approval workflow
        $journal->load('user');

        // Initialize approval workflow and create GL transactions
        $journal->initializeApprovalWorkflow();
        $journal->createGlTransactions();

        return $journal;
    }

    /**
     * Cancel a cheque
     */
    public function cancelCheque(Cheque $cheque, $reason, $cancelledBy = null)
    {
        if (!$cheque->canBeCancelled()) {
            throw new \Exception('Cheque cannot be cancelled in current status.');
        }

        DB::beginTransaction();
        try {
            // Reverse the original journal entry
            $this->reverseIssueJournal($cheque, $reason);

            $cheque->status = 'cancelled';
            $cheque->cancelled_date = now();
            $cheque->cancellation_reason = $reason;
            $cheque->cancelled_by = $cancelledBy ?? auth()->id();
            $cheque->save();

            DB::commit();

            return $cheque;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cheque cancel error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Reverse the issue journal when cheque is cancelled
     */
    protected function reverseIssueJournal(Cheque $cheque, $reason)
    {
        // Ensure issue journal is loaded
        if (!$cheque->relationLoaded('issueJournal')) {
            $cheque->load('issueJournal');
        }
        
        $issueJournal = $cheque->issueJournal;
        if (!$issueJournal) {
            throw new \Exception('Issue journal not found for this cheque. The cheque must have been issued first.');
        }

        // Ensure issue journal items are loaded
        if (!$issueJournal->relationLoaded('items')) {
            $issueJournal->load('items');
        }

        if ($issueJournal->items->isEmpty()) {
            throw new \Exception('Issue journal has no items. Cannot reverse journal entry.');
        }

        $user = auth()->user();
        if (!$user) {
            throw new \Exception('User not authenticated.');
        }

        // Create reversing journal entry
        $journal = Journal::create([
            'date' => now(),
            'reference' => 'CHQ-CANCEL-' . $cheque->cheque_number,
            'reference_type' => 'Cheque Cancelled',
            'description' => "Cheque cancelled: {$cheque->cheque_number} - {$cheque->payee_name}. Reason: {$reason}",
            'branch_id' => $cheque->branch_id ?? $user->branch_id,
            'user_id' => $user->id,
            'approved' => true,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        // Reverse all items from issue journal
        foreach ($issueJournal->items as $item) {
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $item->chart_account_id,
                'amount' => $item->amount,
                'nature' => $item->nature === 'debit' ? 'credit' : 'debit', // Reverse nature
                'description' => "Cheque cancellation reversal: {$cheque->cheque_number}",
            ]);
        }

        // Reload journal with user relationship for approval workflow
        $journal->load('user');

        // Initialize approval workflow and create GL transactions
        $journal->initializeApprovalWorkflow();
        $journal->createGlTransactions();

        return $journal;
    }

    /**
     * Mark cheque as stale
     */
    public function markStale(Cheque $cheque, $days = 180)
    {
        if ($cheque->status !== 'issued') {
            return false;
        }

        if ($cheque->isStale($days)) {
            $cheque->status = 'stale';
            $cheque->save();
            return true;
        }

        return false;
    }

    /**
     * Get outstanding cheques for bank reconciliation
     */
    public function getOutstandingCheques($bankAccountId = null, $companyId = null)
    {
        $query = Cheque::where('status', 'issued');

        if ($bankAccountId) {
            $query->where('bank_account_id', $bankAccountId);
        }

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->orderBy('cheque_date')->get();
    }
}

