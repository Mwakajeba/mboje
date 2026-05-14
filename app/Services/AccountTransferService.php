<?php

namespace App\Services;

use App\Models\AccountTransfer;
use App\Models\Journal;
use App\Models\JournalItem;
use Illuminate\Support\Facades\DB;

class AccountTransferService
{
    /**
     * Post account transfer to GL
     */
    public function postTransferToGL(AccountTransfer $transfer)
    {
        if ($transfer->journal_id) {
            return; // Already posted
        }
        
        try {
            DB::beginTransaction();
            
            // Get account chart account IDs
            $fromAccountId = $this->getChartAccountId($transfer->from_account_type, $transfer->from_account_id);
            $toAccountId = $this->getChartAccountId($transfer->to_account_type, $transfer->to_account_id);
            
            if (!$fromAccountId || !$toAccountId) {
                throw new \Exception('Invalid account configuration');
            }
            
            // Create journal
            $journal = Journal::create([
                'reference' => $transfer->transfer_number,
                'reference_type' => 'Account Transfer',
                'description' => $transfer->description,
                'date' => $transfer->transfer_date,
                'branch_id' => $transfer->branch_id,
                'user_id' => $transfer->created_by,
                'approved' => true,
                'approved_by' => $transfer->approved_by,
                'approved_at' => $transfer->approved_at,
            ]);
            
            // Create journal items
            // Dr To Account
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $toAccountId,
                'description' => $transfer->description,
                'amount' => $transfer->amount,
                'nature' => 'debit',
            ]);
            
            // Cr From Account
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $fromAccountId,
                'description' => $transfer->description,
                'amount' => $transfer->amount,
                'nature' => 'credit',
            ]);
            
            // If charges exist
            if ($transfer->charges > 0 && $transfer->charges_account_id) {
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $transfer->charges_account_id,
                    'description' => 'Transfer Charges',
                    'amount' => $transfer->charges,
                    'nature' => 'debit',
                ]);
                
                // Additional credit for charges
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $fromAccountId,
                    'description' => 'Transfer Charges',
                    'amount' => $transfer->charges,
                    'nature' => 'credit',
                ]);
            }
            
            // Post journal
            $journal->createGlTransactions();
            
            // Update transfer
            $transfer->update([
                'journal_id' => $journal->id,
                'status' => 'posted',
            ]);
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get chart account ID for different account types
     */
    private function getChartAccountId($accountType, $accountId)
    {
        switch ($accountType) {
            case 'bank':
                $bankAccount = \App\Models\BankAccount::find($accountId);
                return $bankAccount?->chart_account_id;
                
            case 'cash':
                $cashAccount = \App\Models\CashDepositAccount::find($accountId);
                return $cashAccount?->chart_account_id;
                
            case 'petty_cash':
                $pettyCashUnit = \App\Models\PettyCash\PettyCashUnit::find($accountId);
                return $pettyCashUnit?->petty_cash_account_id;
                
            default:
                return null;
        }
    }
}

