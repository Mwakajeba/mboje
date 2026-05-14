<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\GlTransaction;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\PettyCash\PettyCashTransaction;
use App\Models\PettyCash\PettyCashReplenishment;
use App\Services\PettyCashModeService;
use Illuminate\Support\Facades\DB;

class PettyCashService
{
    /**
     * Post petty cash transaction to GL as a Payment
     */
    public function postTransactionToGL(PettyCashTransaction $transaction)
    {
        // Reload transaction to get latest state
        $transaction->refresh();
        
        if ($transaction->payment_id) {
            // Check if payment exists and has items
            $payment = Payment::with('paymentItems')->find($transaction->payment_id);
            if ($payment && $payment->paymentItems->count() > 0) {
                \Log::info('Transaction already posted to GL with payment items', [
                    'transaction_id' => $transaction->id,
                    'payment_id' => $payment->id,
                    'payment_items_count' => $payment->paymentItems->count()
                ]);
                return; // Already posted with items
            }
            // If payment exists but has no items, we might need to create them
            // But this shouldn't happen - log it for investigation
            if ($payment && $payment->paymentItems->count() == 0) {
                \Log::warning('Payment exists but has no items - this should not happen', [
                    'transaction_id' => $transaction->id,
                    'payment_id' => $payment->id
                ]);
            }
        }
        
        try {
            DB::beginTransaction();
            
            // Reload transaction with items and unit to ensure they're available
            $transaction->load(['items', 'pettyCashUnit.branch']);
            
            $unit = $transaction->pettyCashUnit;
            $user = $transaction->createdBy;
            
            // Ensure branch_id is available
            $branchId = $unit->branch_id;
            if (!$branchId) {
                // Fallback: get branch_id from user or session
                $branchId = $user->branch_id ?? session('branch_id') ?? null;
                if (!$branchId) {
                    throw new \Exception('Branch ID is required but not available. Please ensure the petty cash unit has a branch assigned.');
                }
                \Log::warning('Petty cash unit missing branch_id, using fallback', [
                    'unit_id' => $unit->id,
                    'unit_name' => $unit->name,
                    'fallback_branch_id' => $branchId
                ]);
            }
            
            $totalAmount = 0;
            
            // Calculate total from line items
            if ($transaction->items && $transaction->items->count() > 0) {
                foreach ($transaction->items as $item) {
                    $totalAmount += $item->amount;
                }
            } else {
                // Legacy: Use transaction amount
                $totalAmount = $transaction->amount;
            }
            
            // Determine payee information
            $payeeType = null;
            $payeeId = null;
            $payeeName = null;
            $customerId = null;
            $supplierId = null;
            
            if ($transaction->payee_type === 'customer') {
                $payeeType = 'customer';
                $payeeId = $transaction->customer_id;
                $customerId = $transaction->customer_id;
            } elseif ($transaction->payee_type === 'supplier') {
                $payeeType = 'supplier';
                $payeeId = $transaction->supplier_id;
                $supplierId = $transaction->supplier_id;
            } elseif ($transaction->payee) {
                $payeeType = 'other';
                $payeeName = $transaction->payee;
            }
            
            // Get functional currency
            $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', $unit->branch->company->functional_currency ?? 'TZS');
            
            // Create Payment record
            try {
                $payment = Payment::create([
                    'reference' => $transaction->transaction_number,
                    'reference_type' => 'petty_cash',
                    'reference_number' => $transaction->transaction_number,
                    'amount' => $totalAmount,
                    'currency' => $functionalCurrency,
                    'exchange_rate' => 1.000000,
                    'amount_fcy' => $totalAmount,
                    'amount_lcy' => $totalAmount,
                    'date' => $transaction->transaction_date instanceof \Carbon\Carbon 
                        ? $transaction->transaction_date 
                        : \Carbon\Carbon::parse($transaction->transaction_date),
                    'description' => 'Petty Cash Expense: ' . $transaction->description,
                    'branch_id' => $branchId,
                    'user_id' => $transaction->created_by,
                    'payee_type' => $payeeType,
                    'payee_id' => $payeeId,
                    'payee_name' => $payeeName,
                    'customer_id' => $customerId,
                    'supplier_id' => $supplierId,
                    'approved' => true,
                    'approved_by' => $transaction->approved_by,
                    'approved_at' => $transaction->approved_at ? 
                        ($transaction->approved_at instanceof \Carbon\Carbon 
                            ? $transaction->approved_at 
                            : \Carbon\Carbon::parse($transaction->approved_at)) 
                        : now(),
                    'wht_treatment' => 'NONE',
                    'wht_rate' => 0,
                    'wht_amount' => 0,
                    'vat_mode' => 'NONE',
                    'vat_amount' => 0,
                    'base_amount' => $totalAmount,
                    'net_payable' => $totalAmount,
                    'total_cost' => $totalAmount,
                    'bank_account_id' => null, // Explicitly set to null for petty cash payments
                ]);
                
                \Log::info('Payment created successfully for petty cash transaction', [
                    'payment_id' => $payment->id,
                    'transaction_id' => $transaction->id,
                    'amount' => $totalAmount
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to create Payment record', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'data' => [
                        'reference' => $transaction->transaction_number,
                        'amount' => $totalAmount,
                        'branch_id' => $unit->branch_id,
                        'user_id' => $transaction->created_by,
                    ]
                ]);
                throw $e;
            }
            
            // Check if payment items already exist to prevent duplicates
            if ($payment->paymentItems()->count() > 0) {
                \Log::warning('Payment items already exist for petty cash payment, checking for duplicates', [
                    'payment_id' => $payment->id,
                    'transaction_id' => $transaction->id,
                    'existing_items_count' => $payment->paymentItems()->count()
                ]);
                
                // Check for and remove duplicates
                $this->removeDuplicatePaymentItems($payment);
                
                // Reload payment with items
                $payment->refresh();
                $payment->load('paymentItems');
            } else {
                // Create PaymentItems for each expense account (debit side)
                // First, check if any payment items already exist for this payment to prevent duplicates
                $existingItems = PaymentItem::where('payment_id', $payment->id)->get();
                if ($existingItems->count() > 0) {
                    \Log::warning('Payment items already exist for payment, skipping creation', [
                        'payment_id' => $payment->id,
                        'transaction_id' => $transaction->id,
                        'existing_count' => $existingItems->count()
                    ]);
                    $payment->refresh();
                    $payment->load('paymentItems');
                } else {
                    $paymentItems = [];
                    if ($transaction->items && $transaction->items->count() > 0) {
                        // Group items by chart_account_id and description to prevent exact duplicates
                        $uniqueItems = [];
                        foreach ($transaction->items as $item) {
                            $key = $item->chart_account_id . '|' . ($item->description ?? '');
                            if (!isset($uniqueItems[$key])) {
                                $uniqueItems[$key] = [
                                    'chart_account_id' => $item->chart_account_id,
                                    'amount' => $item->amount,
                                    'description' => $item->description ?? $transaction->description,
                                ];
                            } else {
                                // If duplicate found, sum the amounts
                                $uniqueItems[$key]['amount'] += $item->amount;
                                \Log::warning('Duplicate transaction item found, combining amounts', [
                                    'transaction_id' => $transaction->id,
                                    'chart_account_id' => $item->chart_account_id,
                                    'description' => $item->description
                                ]);
                            }
                        }
                        
                        // Create payment items from unique items
                        foreach ($uniqueItems as $uniqueItem) {
                            $paymentItems[] = PaymentItem::create([
                                'payment_id' => $payment->id,
                                'chart_account_id' => $uniqueItem['chart_account_id'],
                                'amount' => $uniqueItem['amount'],
                                'base_amount' => $uniqueItem['amount'],
                                'net_payable' => $uniqueItem['amount'],
                                'total_cost' => $uniqueItem['amount'],
                                'wht_treatment' => 'NONE',
                                'wht_rate' => 0,
                                'wht_amount' => 0,
                                'vat_mode' => 'NONE',
                                'vat_amount' => 0,
                                'description' => $uniqueItem['description'],
                            ]);
                        }
                    } else {
                // Legacy: Use expense category
                $category = $transaction->expenseCategory;
                if ($category && $category->expense_account_id) {
                    $paymentItems[] = PaymentItem::create([
                        'payment_id' => $payment->id,
                        'chart_account_id' => $category->expense_account_id,
                        'amount' => $transaction->amount,
                        'base_amount' => $transaction->amount,
                        'net_payable' => $transaction->amount,
                        'total_cost' => $transaction->amount,
                        'wht_treatment' => 'NONE',
                        'wht_rate' => 0,
                        'wht_amount' => 0,
                        'vat_mode' => 'NONE',
                        'vat_amount' => 0,
                        'description' => $transaction->description,
                    ]);
                } else {
                    \Log::warning('Petty cash transaction has no items and no expense category for GL posting', [
                        'transaction_id' => $transaction->id,
                        'transaction_number' => $transaction->transaction_number
                    ]);
                    // If no items and no category, create a generic payment item to balance
                    $paymentItems[] = PaymentItem::create([
                        'payment_id' => $payment->id,
                        'chart_account_id' => $unit->suspense_account_id ?? $unit->petty_cash_account_id, // Use suspense or petty cash account as fallback
                        'amount' => $transaction->amount,
                        'base_amount' => $transaction->amount,
                        'net_payable' => $transaction->amount,
                        'total_cost' => $transaction->amount,
                        'wht_treatment' => 'NONE',
                        'wht_rate' => 0,
                        'wht_amount' => 0,
                        'vat_mode' => 'NONE',
                        'vat_amount' => 0,
                        'description' => 'Uncategorized Petty Cash Expense',
                    ]);
                }
                    }
                    
                    // Reload payment with items to ensure they're available
                    $payment->refresh();
                    $payment->load('paymentItems');
                }
            }
            
            // Check if GL transactions already exist to avoid duplicates
            if ($payment->glTransactions()->exists()) {
                \Log::warning('GL transactions already exist for petty cash payment, skipping creation', [
                    'payment_id' => $payment->id,
                    'transaction_id' => $transaction->id
                ]);
                return;
            }
            
            // Create GL transactions manually (since Payment's createGlTransactions expects bank/cash account)
            // Dr Expense Accounts (from payment items)
            foreach ($payment->paymentItems as $item) {
                GlTransaction::create([
                    'chart_account_id' => $item->chart_account_id,
                    'amount' => $item->amount,
                    'nature' => 'debit', // Dr Expense Account
                    'transaction_id' => $payment->id,
                    'transaction_type' => 'payment',
                    'date' => $payment->date,
                    'description' => $item->description ?? $payment->description,
                    'branch_id' => $branchId,
                    'user_id' => $payment->user_id,
                ]);
            }
            
            // Cr Petty Cash Account
            GlTransaction::create([
                'chart_account_id' => $unit->petty_cash_account_id,
                'amount' => $totalAmount,
                'nature' => 'credit', // Cr Petty Cash Account
                'transaction_id' => $payment->id,
                'transaction_type' => 'payment',
                'date' => $payment->date,
                'description' => 'Petty Cash Payment: ' . $transaction->description,
                'branch_id' => $branchId,
                'user_id' => $payment->user_id,
            ]);
            
            // Update transaction
            $transaction->update([
                'payment_id' => $payment->id,
                'status' => 'posted',
                'balance_after' => $unit->current_balance,
            ]);
            
            // Create register entry
            try {
                PettyCashModeService::createRegisterEntry($transaction->fresh());
            } catch (\Exception $e) {
                \Log::warning('Failed to create register entry for transaction', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ]);
                // Don't fail the entire operation if register entry creation fails
            }
            
            DB::commit();
            
            \Log::info('Petty cash transaction posted to GL as Payment', [
                'transaction_id' => $transaction->id,
                'payment_id' => $payment->id,
                'total_amount' => $totalAmount
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to post petty cash transaction to GL', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Post replenishment to GL and create payment voucher
     */
    public function postReplenishmentToGL(PettyCashReplenishment $replenishment)
    {
        if ($replenishment->journal_id) {
            return; // Already posted
        }
        
        try {
            DB::beginTransaction();
            
            // Reload replenishment with relationships
            $replenishment->load(['pettyCashUnit.branch', 'requestedBy']);
            
            $unit = $replenishment->pettyCashUnit;
            $amount = $replenishment->approved_amount ?? $replenishment->requested_amount;
            $user = $replenishment->requestedBy;
            
            // Ensure branch_id is available
            $branchId = $unit->branch_id;
            if (!$branchId) {
                // Fallback: get branch_id from user or session
                $branchId = $user->branch_id ?? session('branch_id') ?? null;
                if (!$branchId) {
                    throw new \Exception('Branch ID is required but not available. Please ensure the petty cash unit has a branch assigned.');
                }
                \Log::warning('Petty cash unit missing branch_id for replenishment, using fallback', [
                    'unit_id' => $unit->id,
                    'unit_name' => $unit->name,
                    'replenishment_id' => $replenishment->id,
                    'fallback_branch_id' => $branchId
                ]);
            }
            
            // Create journal
            $journal = Journal::create([
                'reference' => $replenishment->replenishment_number,
                'reference_type' => 'Petty Cash Replenishment',
                'description' => 'Petty Cash Replenishment: ' . $replenishment->reason,
                'date' => $replenishment->request_date instanceof \Carbon\Carbon 
                    ? $replenishment->request_date 
                    : \Carbon\Carbon::parse($replenishment->request_date),
                'branch_id' => $branchId,
                'user_id' => $replenishment->requested_by,
                'approved' => true,
                'approved_by' => $replenishment->approved_by,
                'approved_at' => $replenishment->approved_at instanceof \Carbon\Carbon 
                    ? $replenishment->approved_at 
                    : \Carbon\Carbon::parse($replenishment->approved_at),
            ]);
            
            // Create journal items
            // Dr Petty Cash Account
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $unit->petty_cash_account_id,
                'description' => 'Petty Cash Replenishment',
                'amount' => $amount,
                'nature' => 'debit', // Dr Petty Cash Account
            ]);
            
            // Cr Source Bank Account
            if ($replenishment->sourceAccount) {
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $replenishment->sourceAccount->chart_account_id,
                    'description' => 'Petty Cash Replenishment',
                    'amount' => $amount,
                    'nature' => 'credit', // Cr Bank Account
                ]);
            }
            
            // Post journal
            $journal->createGlTransactions();
            
            // Update unit balance
            $unit->increment('current_balance', $amount);
            
            // Update replenishment
            $replenishment->update([
                'journal_id' => $journal->id,
                'status' => 'posted',
            ]);
            
            // Create register entry
            try {
                PettyCashModeService::createReplenishmentRegisterEntry($replenishment->fresh());
            } catch (\Exception $e) {
                \Log::warning('Failed to create register entry for replenishment', [
                    'replenishment_id' => $replenishment->id,
                    'error' => $e->getMessage()
                ]);
                // Don't fail the entire operation if register entry creation fails
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Remove duplicate payment items for a payment
     * Groups items by chart_account_id and description, keeps first, combines amounts, deletes duplicates
     */
    private function removeDuplicatePaymentItems(Payment $payment)
    {
        $items = $payment->paymentItems()->get();
        
        if ($items->count() <= 1) {
            return; // No duplicates possible
        }
        
        // Group items by chart_account_id and description
        $grouped = [];
        foreach ($items as $item) {
            $key = $item->chart_account_id . '|' . ($item->description ?? '');
            if (!isset($grouped[$key])) {
                $grouped[$key] = [];
            }
            $grouped[$key][] = $item;
        }
        
        $removedCount = 0;
        
        foreach ($grouped as $key => $groupItems) {
            if (count($groupItems) > 1) {
                // Keep the first item, combine amounts
                $firstItem = $groupItems[0];
                $totalAmount = array_sum(array_column($groupItems, 'amount'));
                
                // Update first item with combined amount
                $firstItem->update([
                    'amount' => $totalAmount,
                    'base_amount' => $totalAmount,
                    'net_payable' => $totalAmount,
                    'total_cost' => $totalAmount,
                ]);
                
                // Delete duplicate items (skip first)
                $duplicateIds = array_slice(array_column($groupItems, 'id'), 1);
                PaymentItem::whereIn('id', $duplicateIds)->delete();
                
                $removedCount += count($duplicateIds);
                
                \Log::info('Removed duplicate payment items', [
                    'payment_id' => $payment->id,
                    'chart_account_id' => $firstItem->chart_account_id,
                    'description' => $firstItem->description,
                    'duplicates_removed' => count($duplicateIds),
                    'combined_amount' => $totalAmount
                ]);
            }
        }
        
        if ($removedCount > 0) {
            \Log::info('Removed duplicate payment items for payment', [
                'payment_id' => $payment->id,
                'total_removed' => $removedCount
            ]);
        }
    }
}

