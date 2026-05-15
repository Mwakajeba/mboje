<?php

namespace App\Services\Purchase;

use App\Models\GlTransaction;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\Purchase\OpeningBalance;
use App\Models\SystemSetting;

class SupplierOpeningBalanceJournalService
{
    public function resolveRetainedEarningsAccountId(int $companyId): int
    {
        $id = (int) (SystemSetting::where('key', 'retained_earnings_account_id')->value('value') ?? 0);
        if ($id) {
            return $id;
        }

        $id = (int) (SystemSetting::where('key', 'inventory_default_opening_balance_account')->value('value') ?? 0);
        if ($id) {
            return $id;
        }

        return (int) (SystemSetting::where('key', 'ap_opening_balance_account_id')->value('value') ?? 0);
    }

    /**
     * Post opening balance via Journal → Journal items → GL (no bank account).
     * Dr Retained earnings, Cr selected payable chart account.
     */
    public function post(OpeningBalance $opening, int $payableChartAccountId, int $userId): Journal
    {
        $opening->loadMissing('supplier');
        $companyId = (int) $opening->company_id;
        $branchId = (int) $opening->branch_id;
        $amount = round((float) $opening->amount, 2);

        if ($amount <= 0) {
            throw new \RuntimeException('Opening balance amount must be greater than zero.');
        }

        $retainedEarningsId = $this->resolveRetainedEarningsAccountId($companyId);
        if (! $retainedEarningsId) {
            throw new \RuntimeException(
                'Retained earnings account is not configured. Set retained_earnings_account_id in Settings.'
            );
        }

        if ($payableChartAccountId === $retainedEarningsId) {
            throw new \RuntimeException('Payable account cannot be the same as the retained earnings account.');
        }

        $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
        $periodLockService->validateTransactionDate($opening->opening_date, $companyId, 'supplier opening balance');

        $this->removeJournalAndGl($opening);

        $supplierName = $opening->supplier->name ?? 'Supplier';
        $ref = $opening->reference ?: ('SOB-'.$opening->id);
        $desc = 'Supplier opening balance — '.$supplierName.' ('.$ref.')';

        $journal = Journal::create([
            'date' => $opening->opening_date,
            'reference' => (string) $opening->id,
            'reference_type' => 'supplier_opening_balance',
            'supplier_id' => $opening->supplier_id,
            'description' => $desc,
            'branch_id' => $branchId,
            'user_id' => $userId,
            'approved' => true,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $retainedEarningsId,
            'amount' => $amount,
            'nature' => 'debit',
            'description' => 'Retained earnings — '.$ref,
        ]);

        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $payableChartAccountId,
            'amount' => $amount,
            'nature' => 'credit',
            'description' => 'Accounts payable opening — '.$ref,
        ]);

        $journal->load('items');
        $journal->createGlTransactions();

        $opening->update([
            'journal_id' => $journal->id,
            'payable_chart_account_id' => $payableChartAccountId,
        ]);

        return $journal;
    }

    public function removeJournalAndGl(OpeningBalance $opening): void
    {
        if (! $opening->journal_id) {
            return;
        }

        $journalId = (int) $opening->journal_id;
        GlTransaction::where('transaction_type', 'journal')->where('transaction_id', $journalId)->delete();
        JournalItem::where('journal_id', $journalId)->delete();
        Journal::where('id', $journalId)->delete();
        $opening->journal_id = null;
        $opening->saveQuietly();
    }
}
