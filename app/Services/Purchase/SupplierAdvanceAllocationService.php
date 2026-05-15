<?php

namespace App\Services\Purchase;

use App\Models\Purchase\SupplierAdvance;
use App\Models\Purchase\SupplierAdvanceDeduction;
use Illuminate\Support\Collection;

class SupplierAdvanceAllocationService
{
    /**
     * Remaining balance on a single advance row (LCY).
     */
    public function remainingOnAdvance(SupplierAdvance $advance): float
    {
        $used = (float) SupplierAdvanceDeduction::where('supplier_advance_id', $advance->id)->sum('amount');

        return max(0, (float) $advance->amount - $used);
    }

    /**
     * Total supplier advance balance (LCY): posted advances minus deductions.
     */
    public function balanceForSupplier(int $supplierId, int $companyId, ?int $branchId): float
    {
        $adv = SupplierAdvance::query()
            ->where('supplier_id', $supplierId)
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        $ded = SupplierAdvanceDeduction::query()
            ->where('supplier_id', $supplierId)
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        return max(0, (float) $adv - (float) $ded);
    }

    /**
     * FIFO allocate apply amount (LCY) across advances. Returns slices for GL / deductions.
     *
     * @return Collection<int, array{supplier_advance_id: int, debit_chart_account_id: int, amount: float}>
     */
    public function allocateFifo(
        int $supplierId,
        int $companyId,
        ?int $branchId,
        float $applyAmountLCY
    ): Collection {
        $applyAmountLCY = round(max(0, $applyAmountLCY), 2);
        if ($applyAmountLCY <= 0) {
            return collect();
        }

        $advances = SupplierAdvance::query()
            ->where('supplier_id', $supplierId)
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('advance_date')
            ->orderBy('id')
            ->get();

        $left = $applyAmountLCY;
        $slices = collect();

        foreach ($advances as $advance) {
            if ($left <= 0) {
                break;
            }
            $rem = $this->remainingOnAdvance($advance);
            if ($rem <= 0) {
                continue;
            }
            $take = round(min($rem, $left), 2);
            if ($take <= 0) {
                continue;
            }
            $slices->push([
                'supplier_advance_id' => $advance->id,
                'debit_chart_account_id' => (int) $advance->debit_chart_account_id,
                'amount' => $take,
            ]);
            $left = round($left - $take, 2);
        }

        return $slices;
    }

    /**
     * Remove deductions created for a cash purchase (before re-posting GL).
     */
    public function deleteDeductionsForCashPurchase(int $cashPurchaseId): void
    {
        SupplierAdvanceDeduction::query()
            ->where('source_type', 'cash_purchase')
            ->where('source_id', $cashPurchaseId)
            ->delete();
    }

    /**
     * Persist deduction rows for a cash purchase allocation.
     */
    public function recordDeductions(
        Collection $slices,
        int $supplierId,
        int $companyId,
        ?int $branchId,
        $deductionDate,
        int $cashPurchaseId,
        ?int $userId,
        ?string $description = null
    ): void {
        $this->recordDeductionsForSource(
            $slices,
            $supplierId,
            $companyId,
            $branchId,
            $deductionDate,
            'cash_purchase',
            $cashPurchaseId,
            $userId,
            $description ?? ('Applied to cash purchase #'.$cashPurchaseId)
        );
    }

    /**
     * @param  Collection<int, array{supplier_advance_id: int, debit_chart_account_id: int, amount: float}>  $slices
     */
    public function recordDeductionsForSource(
        Collection $slices,
        int $supplierId,
        int $companyId,
        ?int $branchId,
        $deductionDate,
        string $sourceType,
        int $sourceId,
        ?int $userId,
        ?string $description = null
    ): void {
        foreach ($slices as $slice) {
            SupplierAdvanceDeduction::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'supplier_id' => $supplierId,
                'supplier_advance_id' => $slice['supplier_advance_id'],
                'amount' => $slice['amount'],
                'deduction_date' => $deductionDate,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description,
                'user_id' => $userId,
            ]);
        }
    }
}
