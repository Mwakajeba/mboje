<?php

namespace App\Services;

use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\GlTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BankReconciliationService
{
    /**
     * Update bank reconciliations when a new GL transaction is created
     */
    public function updateReconciliationsForTransaction(GlTransaction $glTransaction)
    {
        try {
            DB::beginTransaction();

            // Get the chart account for this transaction
            $chartAccount = $glTransaction->chartAccount;
            if (!$chartAccount) {
                return;
            }

            // Find bank accounts that use this chart account
            $bankAccounts = $glTransaction->chartAccount->bankAccounts;

            foreach ($bankAccounts as $bankAccount) {
                // Find active reconciliations for this bank account
                $activeReconciliations = BankReconciliation::where('bank_account_id', $bankAccount->id)
                    ->whereIn('status', ['draft', 'in_progress'])
                    ->where('start_date', '<=', $glTransaction->date)
                    ->where('end_date', '>=', $glTransaction->date)
                    ->get();

                foreach ($activeReconciliations as $reconciliation) {
                    $this->updateReconciliation($reconciliation, $glTransaction);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update bank reconciliations for transaction: ' . $e->getMessage(), [
                'transaction_id' => $glTransaction->id,
                'chart_account_id' => $glTransaction->chart_account_id,
            ]);
        }
    }

    /**
     * Update a specific reconciliation with a new transaction
     */
    private function updateReconciliation(BankReconciliation $reconciliation, GlTransaction $glTransaction)
    {
        // Store old book balance for notification
        $oldBookBalance = $reconciliation->book_balance;
        
        // Update book balance
        $reconciliation->calculateBookBalance();
        
        // Recalculate adjusted balances
        $reconciliation->recalculateAdjustedBalances();
        
        // Add new transaction as reconciliation item if it doesn't exist
        $this->addTransactionToReconciliation($reconciliation, $glTransaction);
        
        // Send notification if book balance changed
        if ($oldBookBalance != $reconciliation->book_balance) {
            $this->sendUpdateNotification($reconciliation, $glTransaction, $oldBookBalance, $reconciliation->book_balance);
        }
    }

    /**
     * Send notification about reconciliation update
     */
    private function sendUpdateNotification(BankReconciliation $reconciliation, GlTransaction $glTransaction, $oldBookBalance, $newBookBalance)
    {
        try {
            $reconciliation->user->notify(new \App\Notifications\BankReconciliationUpdated(
                $reconciliation,
                $glTransaction,
                $oldBookBalance,
                $newBookBalance
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send bank reconciliation update notification: ' . $e->getMessage());
        }
    }

    /**
     * Add a GL transaction to reconciliation items if it doesn't exist
     */
    private function addTransactionToReconciliation(BankReconciliation $reconciliation, GlTransaction $glTransaction)
    {
        // Check if this transaction is already in the reconciliation items
        $existingItem = BankReconciliationItem::where('bank_reconciliation_id', $reconciliation->id)
            ->where('gl_transaction_id', $glTransaction->id)
            ->first();

        if (!$existingItem) {
            BankReconciliationItem::create([
                'bank_reconciliation_id' => $reconciliation->id,
                'gl_transaction_id' => $glTransaction->id,
                'transaction_type' => 'book_entry',
                'reference' => $glTransaction->transaction_id,
                'description' => $glTransaction->description,
                'transaction_date' => $glTransaction->date,
                'amount' => $glTransaction->amount,
                'nature' => $glTransaction->nature,
                'is_bank_statement_item' => false,
                'is_book_entry' => true,
                'is_reconciled' => false, // Always start as unreconciled
                'notes' => 'Auto-imported from GL transaction',
            ]);
        }
    }

    /**
     * Import all missing GL transactions to reconciliation items
     */
    public function importMissingTransactions(BankReconciliation $reconciliation)
    {
        // Get all GL transactions for this bank account in the reconciliation period
        $glTransactions = GlTransaction::where('chart_account_id', $reconciliation->bankAccount->chart_account_id)
            ->whereBetween('date', [$reconciliation->start_date, $reconciliation->end_date])
            ->get();

        // Get existing reconciliation items (check both gl_transaction_id and reference/date/amount to catch duplicates)
        $existingGlTransactionIds = $reconciliation->reconciliationItems()
            ->whereNotNull('gl_transaction_id')
            ->pluck('gl_transaction_id')
            ->toArray();

        // Also check for items without gl_transaction_id but with matching reference, date, and amount
        $existingItems = $reconciliation->reconciliationItems()
            ->where('is_book_entry', true)
            ->get();

        // Add only missing transactions
        foreach ($glTransactions as $glTransaction) {
            // Skip if already exists by gl_transaction_id
            if (in_array($glTransaction->id, $existingGlTransactionIds)) {
                continue;
            }

            // Also check if an item exists with same reference, date, and amount (for items without gl_transaction_id)
            $duplicateExists = $existingItems->contains(function ($item) use ($glTransaction) {
                return $item->reference == $glTransaction->transaction_id
                    && $item->transaction_date->format('Y-m-d') == $glTransaction->date->format('Y-m-d')
                    && abs($item->amount - $glTransaction->amount) < 0.01
                    && $item->nature == $glTransaction->nature;
            });

            if (!$duplicateExists) {
                $this->addTransactionToReconciliation($reconciliation, $glTransaction);
            }
        }
    }

    /**
     * Get all active reconciliations that might be affected by a transaction
     */
    public function getActiveReconciliationsForTransaction(GlTransaction $glTransaction)
    {
        $chartAccount = $glTransaction->chartAccount;
        if (!$chartAccount) {
            return collect();
        }

        return BankReconciliation::whereHas('bankAccount', function ($query) use ($chartAccount) {
                $query->where('chart_account_id', $chartAccount->id);
            })
            ->whereIn('status', ['draft', 'in_progress'])
            ->where('start_date', '<=', $glTransaction->date)
            ->where('end_date', '>=', $glTransaction->date)
            ->get();
    }

    /**
     * Check if a reconciliation needs to be updated
     */
    public function reconciliationNeedsUpdate(BankReconciliation $reconciliation)
    {
        // Check if there are any new GL transactions that haven't been imported
        $latestImportedTransaction = $reconciliation->reconciliationItems()
            ->whereNotNull('gl_transaction_id')
            ->orderBy('created_at', 'desc')
            ->first();

        $latestGlTransaction = GlTransaction::where('chart_account_id', $reconciliation->bankAccount->chart_account_id)
            ->whereBetween('date', [$reconciliation->start_date, $reconciliation->end_date])
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$latestImportedTransaction || !$latestGlTransaction) {
            return true;
        }

        return $latestGlTransaction->created_at->gt($latestImportedTransaction->created_at);
    }

    /**
     * Post reconciliation adjustments to GL when reconciliation is approved/completed
     * This posts receipts, payments, and journals created during the reconciliation period to GL
     */
    public function postAdjustmentsToGL(BankReconciliation $reconciliation, $userId = null): array
    {
        try {
            DB::beginTransaction();

            // Check if adjustments have already been posted
            if ($reconciliation->adjustments_posted_at) {
                Log::info('Bank reconciliation adjustments already posted', [
                    'reconciliation_id' => $reconciliation->id,
                    'posted_at' => $reconciliation->adjustments_posted_at
                ]);
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Adjustments have already been posted to GL.',
                    'posted_count' => 0
                ];
            }

            // Load relationships
            $reconciliation->load('bankAccount.chartAccount', 'branch', 'user');
            $bankAccount = $reconciliation->bankAccount;
            $bankChartAccountId = $bankAccount->chart_account_id;
            $userId = $userId ?? $reconciliation->user_id ?? auth()->id();

            $postedCount = 0;
            $errors = [];
            $postedReceipts = 0;
            $postedPayments = 0;
            $postedJournals = 0;

            // Find receipts for this bank account within the reconciliation period
            $receipts = \App\Models\Receipt::where('bank_account_id', $bankAccount->id)
                ->whereBetween('date', [$reconciliation->start_date, $reconciliation->end_date])
                ->where('approved', true)
                ->get();

            Log::info('Bank reconciliation: Finding receipts to post', [
                'reconciliation_id' => $reconciliation->id,
                'bank_account_id' => $bankAccount->id,
                'start_date' => $reconciliation->start_date,
                'end_date' => $reconciliation->end_date,
                'receipts_count' => $receipts->count()
            ]);

            foreach ($receipts as $receipt) {
                try {
                    // Check if this receipt is already part of a completed reconciliation for this period
                    $isInCompletedReconciliation = $this->isTransactionInCompletedReconciliation(
                        $bankAccount->id,
                        $receipt->date,
                        $reconciliation->id
                    );
                    
                    if ($isInCompletedReconciliation) {
                        Log::info('Bank reconciliation: Receipt is already in a completed reconciliation, skipping GL posting', [
                            'reconciliation_id' => $reconciliation->id,
                            'receipt_id' => $receipt->id,
                            'receipt_reference' => $receipt->reference,
                            'receipt_date' => $receipt->date
                        ]);
                        continue;
                    }
                    
                    // Check if GL transactions already exist for this receipt
                    $existingGL = GlTransaction::where('transaction_type', 'receipt')
                        ->where('transaction_id', $receipt->id)
                        ->exists();

                    if (!$existingGL) {
                        // Load relationships needed for GL posting
                        $receipt->loadMissing(['bankAccount', 'receiptItems']);
                        
                        // Check if receipt has items - if not, skip with warning
                        if (!$receipt->receiptItems || $receipt->receiptItems->isEmpty()) {
                            Log::warning('Bank reconciliation: Receipt has no items, skipping GL posting', [
                                'reconciliation_id' => $reconciliation->id,
                                'receipt_id' => $receipt->id,
                                'receipt_reference' => $receipt->reference
                            ]);
                            $errors[] = "Receipt {$receipt->reference} has no items to post.";
                            continue;
                        }
                        
                        // Check if receipt has bank account or can use cash account
                        if (!$receipt->bankAccount) {
                            // Check if cash account is available
                            $cashAccountId = (int) (\App\Models\SystemSetting::where('key', 'inventory_default_cash_account')->value('value') ?? 0);
                            if (!$cashAccountId) {
                                $cashAccount = \App\Models\ChartAccount::where('account_name', 'like', '%Cash%Hand%')
                                    ->orWhere('account_name', 'like', '%Cash on Hand%')
                                    ->first();
                                $cashAccountId = $cashAccount ? $cashAccount->id : 0;
                            }
                            
                            if (!$cashAccountId) {
                                Log::warning('Bank reconciliation: Receipt has no bank or cash account, skipping GL posting', [
                                    'reconciliation_id' => $reconciliation->id,
                                    'receipt_id' => $receipt->id,
                                    'receipt_reference' => $receipt->reference
                                ]);
                                $errors[] = "Receipt {$receipt->reference} has no bank or cash account configured.";
                                continue;
                            }
                        }
                        
                        // Create GL transactions for the receipt
                        $receipt->createGlTransactions();
                        
                        // Verify GL transactions were created
                        $glCreated = GlTransaction::where('transaction_type', 'receipt')
                            ->where('transaction_id', $receipt->id)
                            ->exists();
                        
                        if ($glCreated) {
                            $postedReceipts++;
                            $postedCount++;
                            Log::info('Bank reconciliation: Posted receipt to GL', [
                                'reconciliation_id' => $reconciliation->id,
                                'receipt_id' => $receipt->id,
                                'receipt_reference' => $receipt->reference
                            ]);
                        } else {
                            Log::warning('Bank reconciliation: Receipt createGlTransactions returned but no GL transactions created', [
                                'reconciliation_id' => $reconciliation->id,
                                'receipt_id' => $receipt->id,
                                'receipt_reference' => $receipt->reference
                            ]);
                            $errors[] = "Receipt {$receipt->reference} GL posting returned but no transactions were created.";
                        }
                    } else {
                        Log::info('Bank reconciliation: Receipt already has GL transactions', [
                            'reconciliation_id' => $reconciliation->id,
                            'receipt_id' => $receipt->id,
                            'receipt_reference' => $receipt->reference
                        ]);
                    }
                } catch (\Exception $e) {
                    $errors[] = "Failed to post receipt {$receipt->reference}: " . $e->getMessage();
                    Log::error('Failed to post receipt to GL during reconciliation', [
                        'receipt_id' => $receipt->id,
                        'reconciliation_id' => $reconciliation->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            // Find payments for this bank account within the reconciliation period
            $payments = \App\Models\Payment::where('bank_account_id', $bankAccount->id)
                ->whereBetween('date', [$reconciliation->start_date, $reconciliation->end_date])
                ->where('approved', true)
                ->get();

            Log::info('Bank reconciliation: Finding payments to post', [
                'reconciliation_id' => $reconciliation->id,
                'bank_account_id' => $bankAccount->id,
                'payments_count' => $payments->count()
            ]);

            foreach ($payments as $payment) {
                try {
                    // Check if this payment is already part of a completed reconciliation for this period
                    $isInCompletedReconciliation = $this->isTransactionInCompletedReconciliation(
                        $bankAccount->id,
                        $payment->date,
                        $reconciliation->id
                    );
                    
                    if ($isInCompletedReconciliation) {
                        Log::info('Bank reconciliation: Payment is already in a completed reconciliation, skipping GL posting', [
                            'reconciliation_id' => $reconciliation->id,
                            'payment_id' => $payment->id,
                            'payment_reference' => $payment->reference,
                            'payment_date' => $payment->date
                        ]);
                        continue;
                    }
                    
                    // Check if GL transactions already exist for this payment
                    $existingGL = GlTransaction::where('transaction_type', 'payment')
                        ->where('transaction_id', $payment->id)
                        ->exists();

                    if (!$existingGL) {
                        // Load relationships needed for GL posting
                        $payment->loadMissing(['bankAccount', 'paymentItems']);
                        
                        // Check if payment has items - if not, skip with warning
                        if (!$payment->paymentItems || $payment->paymentItems->isEmpty()) {
                            Log::warning('Bank reconciliation: Payment has no items, skipping GL posting', [
                                'reconciliation_id' => $reconciliation->id,
                                'payment_id' => $payment->id,
                                'payment_reference' => $payment->reference
                            ]);
                            $errors[] = "Payment {$payment->reference} has no items to post.";
                            continue;
                        }
                        
                        // Check if payment has bank account or can use cash account
                        if (!$payment->bankAccount) {
                            // Check if cash account is available
                            $cashAccountId = (int) (\App\Models\SystemSetting::where('key', 'inventory_default_cash_account')->value('value') ?? 0);
                            if (!$cashAccountId) {
                                $cashAccount = \App\Models\ChartAccount::where('account_name', 'like', '%Cash%Hand%')
                                    ->orWhere('account_name', 'like', '%Cash on Hand%')
                                    ->first();
                                $cashAccountId = $cashAccount ? $cashAccount->id : 0;
                            }
                            
                            if (!$cashAccountId) {
                                Log::warning('Bank reconciliation: Payment has no bank or cash account, skipping GL posting', [
                                    'reconciliation_id' => $reconciliation->id,
                                    'payment_id' => $payment->id,
                                    'payment_reference' => $payment->reference
                                ]);
                                $errors[] = "Payment {$payment->reference} has no bank or cash account configured.";
                                continue;
                            }
                        }
                        
                        // Create GL transactions for the payment
                        $payment->createGlTransactions();
                        
                        // Verify GL transactions were created
                        $glCreated = GlTransaction::where('transaction_type', 'payment')
                            ->where('transaction_id', $payment->id)
                            ->exists();
                        
                        if ($glCreated) {
                            $postedPayments++;
                            $postedCount++;
                            Log::info('Bank reconciliation: Posted payment to GL', [
                                'reconciliation_id' => $reconciliation->id,
                                'payment_id' => $payment->id,
                                'payment_reference' => $payment->reference
                            ]);
                        } else {
                            Log::warning('Bank reconciliation: Payment createGlTransactions returned but no GL transactions created', [
                                'reconciliation_id' => $reconciliation->id,
                                'payment_id' => $payment->id,
                                'payment_reference' => $payment->reference
                            ]);
                            $errors[] = "Payment {$payment->reference} GL posting returned but no transactions were created.";
                        }
                    } else {
                        Log::info('Bank reconciliation: Payment already has GL transactions', [
                        'reconciliation_id' => $reconciliation->id,
                        'payment_id' => $payment->id,
                        'payment_reference' => $payment->reference
                    ]);
                    }
                } catch (\Exception $e) {
                    $errors[] = "Failed to post payment {$payment->reference}: " . $e->getMessage();
                    Log::error('Failed to post payment to GL during reconciliation', [
                        'payment_id' => $payment->id,
                        'reconciliation_id' => $reconciliation->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            // Find journals that affect this bank account within the reconciliation period
            // Journals affect bank account through their journal items
            $journals = \App\Models\Journal::where('date', '>=', $reconciliation->start_date)
                ->where('date', '<=', $reconciliation->end_date)
                ->where('branch_id', $reconciliation->branch_id)
                ->whereHas('items', function($query) use ($bankChartAccountId) {
                    $query->where('chart_account_id', $bankChartAccountId);
                })
                ->get();

            Log::info('Bank reconciliation: Finding journals to post', [
                'reconciliation_id' => $reconciliation->id,
                'bank_chart_account_id' => $bankChartAccountId,
                'journals_count' => $journals->count()
            ]);

            foreach ($journals as $journal) {
                try {
                    // Check if this journal is already part of a completed reconciliation for this period
                    $isInCompletedReconciliation = $this->isTransactionInCompletedReconciliation(
                        $bankAccount->id,
                        $journal->date,
                        $reconciliation->id
                    );
                    
                    if ($isInCompletedReconciliation) {
                        Log::info('Bank reconciliation: Journal is already in a completed reconciliation, skipping GL posting', [
                            'reconciliation_id' => $reconciliation->id,
                            'journal_id' => $journal->id,
                            'journal_reference' => $journal->reference,
                            'journal_date' => $journal->date
                        ]);
                        continue;
                    }
                    
                    // Check if GL transactions already exist for this journal
                    $existingGL = GlTransaction::where('transaction_type', 'journal')
                        ->where('transaction_id', $journal->id)
                        ->exists();

                    if (!$existingGL) {
                        // Create GL transactions from journal items
                        $this->createGLTransactionsFromJournal($journal);
                        $postedJournals++;
                        $postedCount++;
                        Log::info('Bank reconciliation: Posted journal to GL', [
                            'reconciliation_id' => $reconciliation->id,
                            'journal_id' => $journal->id,
                            'journal_reference' => $journal->reference
                        ]);
                    } else {
                        Log::info('Bank reconciliation: Journal already has GL transactions', [
                            'reconciliation_id' => $reconciliation->id,
                            'journal_id' => $journal->id,
                            'journal_reference' => $journal->reference
                        ]);
                    }
                } catch (\Exception $e) {
                    $errors[] = "Failed to post journal {$journal->reference}: " . $e->getMessage();
                    Log::error('Failed to post journal to GL during reconciliation', [
                        'journal_id' => $journal->id,
                        'reconciliation_id' => $reconciliation->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Mark reconciliation as having adjustments posted
            if ($postedCount > 0) {
                $reconciliation->update([
                    'adjustments_posted_at' => now(),
                    'adjustments_posted_by' => $userId
                ]);

                // Log activity
                if (method_exists($reconciliation, 'logActivity')) {
                    $details = [];
                    if ($postedReceipts > 0) $details['Receipts'] = $postedReceipts;
                    if ($postedPayments > 0) $details['Payments'] = $postedPayments;
                    if ($postedJournals > 0) $details['Journals'] = $postedJournals;
                    
                    $reconciliation->logActivity('post', "Posted {$postedCount} reconciliation adjustments to GL", array_merge([
                        'Total Posted' => $postedCount,
                        'Posted By' => \App\Models\User::find($userId)->name ?? 'System',
                        'Posted At' => now()->format('Y-m-d H:i:s')
                    ], $details));
                }
            }

            DB::commit();

            $messageParts = [];
            if ($postedReceipts > 0) $messageParts[] = "{$postedReceipts} receipt(s)";
            if ($postedPayments > 0) $messageParts[] = "{$postedPayments} payment(s)";
            if ($postedJournals > 0) $messageParts[] = "{$postedJournals} journal(s)";
            
            $message = "Successfully posted " . implode(', ', $messageParts) . " to GL.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode('; ', $errors);
            }

            return [
                'success' => true,
                'message' => $message,
                'posted_count' => $postedCount,
                'posted_receipts' => $postedReceipts,
                'posted_payments' => $postedPayments,
                'posted_journals' => $postedJournals,
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to post bank reconciliation adjustments to GL', [
                'reconciliation_id' => $reconciliation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to post adjustments: ' . $e->getMessage(),
                'posted_count' => 0,
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Create GL transactions from journal items
     */
    private function createGLTransactionsFromJournal(\App\Models\Journal $journal): void
    {
        // Load items if not already loaded
        if (!$journal->relationLoaded('items')) {
            $journal->load('items');
        }
        
        foreach ($journal->items as $item) {
            GlTransaction::create([
                'chart_account_id' => $item->chart_account_id,
                'amount' => $item->amount,
                'nature' => $item->nature,
                'transaction_id' => $journal->id,
                'transaction_type' => 'journal',
                'date' => $journal->date,
                'description' => $item->description ?? $journal->description,
                'branch_id' => $journal->branch_id,
                'user_id' => $journal->user_id ?? auth()->id(),
            ]);
        }
    }

    /**
     * Check if a transaction (receipt/payment/journal) is already part of a completed reconciliation
     * for the same bank account and period. This prevents posting GL transactions for items
     * that are already in a completed reconciliation.
     */
    private function isTransactionInCompletedReconciliation(
        int $bankAccountId,
        $transactionDate,
        ?int $excludeReconciliationId = null
    ): bool {
        // Find completed reconciliations for this bank account that include this transaction date
        $query = BankReconciliation::where('bank_account_id', $bankAccountId)
            ->where('status', 'completed')
            ->where('start_date', '<=', $transactionDate)
            ->where('end_date', '>=', $transactionDate);
        
        // Exclude the current reconciliation if provided (for the reconciliation being approved)
        if ($excludeReconciliationId) {
            $query->where('id', '!=', $excludeReconciliationId);
        }
        
        return $query->exists();
    }

    /**
     * Static helper method to check if a chart account (bank account) and date is in a completed reconciliation.
     * This can be used from anywhere in the application to prevent GL posting.
     * 
     * @param int|null $chartAccountId The chart account ID (bank account's chart_account_id)
     * @param mixed $transactionDate The transaction date
     * @return bool True if the account/date is in a completed reconciliation
     */
    public static function isChartAccountInCompletedReconciliation(?int $chartAccountId, $transactionDate): bool
    {
        if (!$chartAccountId) {
            return false;
        }

        // Find bank accounts that use this chart account
        $bankAccounts = \App\Models\BankAccount::where('chart_account_id', $chartAccountId)->pluck('id');
        
        if ($bankAccounts->isEmpty()) {
            return false;
        }

        // Check if any of these bank accounts have a completed reconciliation for this date
        return BankReconciliation::whereIn('bank_account_id', $bankAccounts)
            ->where('status', 'completed')
            ->where('start_date', '<=', $transactionDate)
            ->where('end_date', '>=', $transactionDate)
            ->exists();
    }

} 