<?php

namespace App\Services\Purchase;

use App\Models\GlTransaction;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\Purchase\SupplierAdvance;
use App\Models\SystemSetting;

class SupplierAdvanceJournalService
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
     * Opening supplier advance: Dr advance (1100), Cr retained earnings via journal → GL.
     */
    public function postOpeningAdvance(SupplierAdvance $advance, int $userId): Journal
    {
        $advance->loadMissing('supplier');
        $companyId = (int) $advance->company_id;
        $branchId = (int) $advance->branch_id;
        $amount = round((float) $advance->amount, 2);

        if ($amount <= 0) {
            throw new \RuntimeException('Advance amount must be greater than zero.');
        }

        $retainedEarningsId = $this->resolveRetainedEarningsAccountId($companyId);
        if (! $retainedEarningsId) {
            throw new \RuntimeException(
                'Retained earnings account is not configured. Set retained_earnings_account_id in Settings.'
            );
        }

        $debitId = (int) $advance->debit_chart_account_id;
        if ($debitId === $retainedEarningsId) {
            throw new \RuntimeException('Advance account cannot be the same as the retained earnings account.');
        }

        $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
        $periodLockService->validateTransactionDate($advance->advance_date, $companyId, 'supplier advance');

        $this->removeJournalAndGl($advance);

        $supplierName = $advance->supplier->name ?? 'Supplier';
        $ref = $advance->reference ?: ('SADV-'.$advance->id);
        $desc = $advance->description ?: ('Opening supplier advance — '.$supplierName);

        $journal = Journal::create([
            'date' => $advance->advance_date,
            'reference' => (string) $advance->id,
            'reference_type' => 'supplier_advance_opening',
            'supplier_id' => $advance->supplier_id,
            'description' => $desc.' ('.$ref.')',
            'branch_id' => $branchId,
            'user_id' => $userId,
            'approved' => true,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $debitId,
            'amount' => $amount,
            'nature' => 'debit',
            'description' => 'Supplier advance — '.$ref,
        ]);

        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => $retainedEarningsId,
            'amount' => $amount,
            'nature' => 'credit',
            'description' => 'Retained earnings — '.$ref,
        ]);

        $journal->load('items');
        $journal->createGlTransactions();

        $advance->update(['journal_id' => $journal->id]);

        return $journal;
    }

    public function removeJournalAndGl(SupplierAdvance $advance): void
    {
        if (! $advance->journal_id) {
            GlTransaction::where('transaction_type', 'supplier_advance')
                ->where('transaction_id', $advance->id)
                ->delete();

            return;
        }

        $journalId = (int) $advance->journal_id;
        GlTransaction::where('transaction_type', 'journal')->where('transaction_id', $journalId)->delete();
        JournalItem::where('journal_id', $journalId)->delete();
        Journal::where('id', $journalId)->delete();
        $advance->journal_id = null;
        $advance->saveQuietly();
    }
}
