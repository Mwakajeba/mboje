<?php

namespace App\Services;

use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\GLTransaction;
use Illuminate\Support\Facades\DB;
use Exception;

class GLTransactionService
{
    /**
     * Create GL transaction entries
     *
     * @param array $transactionData
     * @return array
     * @throws Exception
     */
    public function createTransaction(array $transactionData)
    {
        try {
            DB::beginTransaction();

            // Create journal entry
            $journal = Journal::create([
                'company_id' => $transactionData['company_id'],
                'branch_id' => $transactionData['branch_id'] ?? null,
                'journal_date' => $transactionData['transaction_date'],
                'reference_number' => $transactionData['reference_number'],
                'description' => $transactionData['description'],
                'total_amount' => $transactionData['total_amount'],
                'created_by' => auth()->id(),
                'status' => 'posted'
            ]);

            // Create journal items (debit and credit entries)
            if (isset($transactionData['entries']) && is_array($transactionData['entries'])) {
                foreach ($transactionData['entries'] as $entry) {
                    JournalItem::create([
                        'journal_id' => $journal->id,
                        'chart_account_id' => $entry['chart_account_id'],
                        'debit_amount' => $entry['debit_amount'] ?? 0,
                        'credit_amount' => $entry['credit_amount'] ?? 0,
                        'description' => $entry['description'] ?? $transactionData['description'],
                    ]);
                }
            }

            // Create GL transaction records if GLTransaction model exists
            if (class_exists('App\Models\GLTransaction')) {
                if (isset($transactionData['entries']) && is_array($transactionData['entries'])) {
                    foreach ($transactionData['entries'] as $entry) {
                        GLTransaction::create([
                            'company_id' => $transactionData['company_id'],
                            'branch_id' => $transactionData['branch_id'] ?? null,
                            'chart_account_id' => $entry['chart_account_id'],
                            'transaction_date' => $transactionData['transaction_date'],
                            'reference_number' => $transactionData['reference_number'],
                            'description' => $entry['description'] ?? $transactionData['description'],
                            'debit_amount' => $entry['debit_amount'] ?? 0,
                            'credit_amount' => $entry['credit_amount'] ?? 0,
                            'journal_id' => $journal->id,
                            'created_by' => auth()->id(),
                        ]);
                    }
                }
            }

            DB::commit();

            return [
                'success' => true,
                'journal_id' => $journal->id,
                'message' => 'GL transaction created successfully'
            ];

        } catch (Exception $e) {
            DB::rollback();
            
            return [
                'success' => false,
                'message' => 'Failed to create GL transaction: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create retirement GL transactions
     *
     * @param array $retirementData
     * @return array
     */
    public function createRetirementGLTransactions(array $retirementData)
    {
        $entries = [];

        // Create debit entries for each retirement item (expense accounts)
        if (isset($retirementData['retirement_items']) && is_array($retirementData['retirement_items'])) {
            foreach ($retirementData['retirement_items'] as $item) {
                $entries[] = [
                    'chart_account_id' => $item['chart_account_id'],
                    'debit_amount' => $item['actual_amount'],
                    'credit_amount' => 0,
                    'description' => $item['description'] ?? 'Retirement expense'
                ];
            }
        }

        // Create credit entry for the selected credit account
        if (isset($retirementData['credit_account_id']) && isset($retirementData['total_amount'])) {
            $entries[] = [
                'chart_account_id' => $retirementData['credit_account_id'],
                'debit_amount' => 0,
                'credit_amount' => $retirementData['total_amount'],
                'description' => 'Retirement settlement'
            ];
        }

        return $this->createTransaction([
            'company_id' => $retirementData['company_id'],
            'branch_id' => $retirementData['branch_id'] ?? null,
            'transaction_date' => $retirementData['transaction_date'] ?? now()->toDateString(),
            'reference_number' => $retirementData['reference_number'],
            'description' => $retirementData['description'] ?? 'Retirement GL Transaction',
            'total_amount' => $retirementData['total_amount'],
            'entries' => $entries
        ]);
    }

    /**
     * Reverse GL transaction
     *
     * @param int $journalId
     * @return array
     */
    public function reverseTransaction(int $journalId)
    {
        try {
            DB::beginTransaction();

            $journal = Journal::findOrFail($journalId);
            
            // Create reversal journal
            $reversalJournal = Journal::create([
                'company_id' => $journal->company_id,
                'branch_id' => $journal->branch_id,
                'journal_date' => now()->toDateString(),
                'reference_number' => 'REV-' . $journal->reference_number,
                'description' => 'Reversal of: ' . $journal->description,
                'total_amount' => $journal->total_amount,
                'created_by' => auth()->id(),
                'status' => 'posted'
            ]);

            // Create reversal entries (swap debit/credit)
            foreach ($journal->journalItems as $item) {
                JournalItem::create([
                    'journal_id' => $reversalJournal->id,
                    'chart_account_id' => $item->chart_account_id,
                    'debit_amount' => $item->credit_amount, // Reverse
                    'credit_amount' => $item->debit_amount, // Reverse
                    'description' => 'Reversal: ' . $item->description,
                ]);

                // Create GL transaction if model exists
                if (class_exists('App\Models\GLTransaction')) {
                    GLTransaction::create([
                        'company_id' => $journal->company_id,
                        'branch_id' => $journal->branch_id,
                        'chart_account_id' => $item->chart_account_id,
                        'transaction_date' => now()->toDateString(),
                        'reference_number' => 'REV-' . $journal->reference_number,
                        'description' => 'Reversal: ' . $item->description,
                        'debit_amount' => $item->credit_amount, // Reverse
                        'credit_amount' => $item->debit_amount, // Reverse
                        'journal_id' => $reversalJournal->id,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            DB::commit();

            return [
                'success' => true,
                'reversal_journal_id' => $reversalJournal->id,
                'message' => 'Transaction reversed successfully'
            ];

        } catch (Exception $e) {
            DB::rollback();
            
            return [
                'success' => false,
                'message' => 'Failed to reverse transaction: ' . $e->getMessage()
            ];
        }
    }
}