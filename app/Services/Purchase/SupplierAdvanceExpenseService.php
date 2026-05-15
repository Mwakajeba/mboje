<?php

namespace App\Services\Purchase;

use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\Supplier;
use App\Services\BankReconciliationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupplierAdvanceExpenseService
{
    public function __construct(
        private readonly SupplierAdvanceAllocationService $allocationService
    ) {}

    /**
     * Apply supplier advance to expenses: Journal + JournalItems + GL (Dr expense, Cr advance) + deductions.
     *
     * @param  array<int, array{chart_account_id: int, amount: float, description?: string|null}>  $lineItems
     */
    public function processExpense(
        Supplier $supplier,
        int $companyId,
        int $branchId,
        array $lineItems,
        string $date,
        int $userId,
        ?string $description = null,
        ?string $reference = null
    ): Journal {
        $total = round(collect($lineItems)->sum(fn ($row) => (float) ($row['amount'] ?? 0)), 2);
        if ($total <= 0) {
            throw new \RuntimeException('Total expense amount must be greater than zero.');
        }

        $balance = $this->allocationService->balanceForSupplier($supplier->id, $companyId, $branchId);
        if ($total > $balance + 0.05) {
            throw new \RuntimeException(
                'Total exceeds supplier advance balance ('.number_format($balance, 2).').'
            );
        }

        $slices = $this->allocationService->allocateFifo($supplier->id, $companyId, $branchId, $total);
        $allocated = round((float) $slices->sum('amount'), 2);
        if ($slices->isEmpty() || abs($allocated - $total) > 0.05) {
            throw new \RuntimeException('Could not allocate expense across supplier advances (insufficient balance).');
        }

        foreach ($lineItems as $row) {
            $chartId = (int) ($row['chart_account_id'] ?? 0);
            if ($chartId <= 0) {
                throw new \RuntimeException('Each line must have an expense account.');
            }
            if (BankReconciliationService::isChartAccountInCompletedReconciliation($chartId, $date)) {
                throw new \RuntimeException('Cannot post: an expense account is in a completed reconciliation for this date.');
            }
        }

        foreach ($slices->pluck('debit_chart_account_id')->unique() as $chartId) {
            if (BankReconciliationService::isChartAccountInCompletedReconciliation((int) $chartId, $date)) {
                throw new \RuntimeException('Cannot post: a supplier advance account is in a completed reconciliation for this date.');
            }
        }

        $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
        $periodLockService->validateTransactionDate($date, $companyId, 'supplier advance expense');

        return DB::transaction(function () use (
            $supplier,
            $companyId,
            $branchId,
            $lineItems,
            $date,
            $userId,
            $description,
            $reference,
            $slices
        ) {
            $ref = $reference ?: $this->generateReference();
            $desc = $description ?: ('Supplier advance expense — '.$supplier->name);

            $journal = Journal::create([
                'date' => $date,
                'reference' => $ref,
                'reference_type' => 'supplier_advance_expense',
                'supplier_id' => $supplier->id,
                'description' => $desc,
                'branch_id' => $branchId,
                'user_id' => $userId,
                'approved' => true,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            foreach ($lineItems as $row) {
                $amt = round((float) $row['amount'], 2);
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => (int) $row['chart_account_id'],
                    'amount' => $amt,
                    'nature' => 'debit',
                    'description' => $row['description'] ?? ('Expense — '.$ref),
                ]);
            }

            foreach ($slices as $slice) {
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => (int) $slice['debit_chart_account_id'],
                    'amount' => $slice['amount'],
                    'nature' => 'credit',
                    'description' => 'Supplier advance applied — '.$ref,
                ]);
            }

            $journal->load('items');
            $journal->createGlTransactions();

            $this->allocationService->recordDeductionsForSource(
                $slices,
                $supplier->id,
                $companyId,
                $branchId,
                $date,
                'supplier_advance_expense',
                $journal->id,
                $userId,
                $desc
            );

            return $journal;
        });
    }

    private function generateReference(): string
    {
        do {
            $ref = 'SAE-'.date('Ymd').'-'.strtoupper(Str::random(6));
        } while (Journal::where('reference', $ref)->where('reference_type', 'supplier_advance_expense')->exists());

        return $ref;
    }
}
