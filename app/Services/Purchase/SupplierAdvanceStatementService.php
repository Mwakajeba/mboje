<?php

namespace App\Services\Purchase;

use App\Models\Purchase\SupplierAdvance;
use App\Models\Purchase\SupplierAdvanceDeduction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SupplierAdvanceStatementService
{
    /**
     * Build advance in/out lines with running balance for a supplier.
     *
     * @return array{lines: Collection<int, array<string, mixed>>, totals: array{paid: float, deducted: float, balance: float}}
     */
    public function buildForSupplier(int $supplierId, int $companyId, ?int $branchId): array
    {
        $advances = SupplierAdvance::query()
            ->where('supplier_id', $supplierId)
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->with(['user'])
            ->orderBy('advance_date')
            ->orderBy('id')
            ->get();

        $deductions = SupplierAdvanceDeduction::query()
            ->where('supplier_id', $supplierId)
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->with(['user'])
            ->orderBy('deduction_date')
            ->orderBy('id')
            ->get();

        $lines = collect();

        foreach ($advances as $advance) {
            $lines->push($this->mapAdvanceLine($advance));
        }

        foreach ($deductions as $deduction) {
            $lines->push($this->mapDeductionLine($deduction));
        }

        $lines = $lines->sortBy('sort')->values();

        $running = 0.0;
        $lines = $lines->map(function (array $line) use (&$running) {
            $running += $line['paid'] - $line['deducted'];
            $line['balance'] = round($running, 2);

            return $line;
        })->values();

        $totalPaid = (float) $advances->sum('amount');
        $totalDeducted = (float) $deductions->sum('amount');

        return [
            'lines' => $lines,
            'totals' => [
                'paid' => round($totalPaid, 2),
                'deducted' => round($totalDeducted, 2),
                'balance' => round($totalPaid - $totalDeducted, 2),
                // Aliases for existing statement view
                'advances' => round($totalPaid, 2),
                'applied' => round($totalDeducted, 2),
            ],
            'opening_balance' => null,
            'period' => null,
        ];
    }

    /**
     * Statement for a date range: opening balance, movements in period, closing balance.
     *
     * @return array{lines: Collection, totals: array, opening_balance: float, period: array{from: string, to: string}}
     */
    public function buildForSupplierPeriod(
        int $supplierId,
        int $companyId,
        ?int $branchId,
        string $fromDate,
        string $toDate
    ): array {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();
        if ($from->gt($to)) {
            throw new \InvalidArgumentException('From date cannot be after to date.');
        }

        $fromStr = $from->toDateString();
        $toStr = $to->toDateString();

        $advanceBase = SupplierAdvance::query()
            ->where('supplier_id', $supplierId)
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $deductionBase = SupplierAdvanceDeduction::query()
            ->where('supplier_id', $supplierId)
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $openingBalance = round(
            (float) (clone $advanceBase)->where('advance_date', '<', $fromStr)->sum('amount')
            - (float) (clone $deductionBase)->where('deduction_date', '<', $fromStr)->sum('amount'),
            2
        );

        $advances = (clone $advanceBase)
            ->whereBetween('advance_date', [$fromStr, $toStr])
            ->with(['user'])
            ->orderBy('advance_date')
            ->orderBy('id')
            ->get();

        $deductions = (clone $deductionBase)
            ->whereBetween('deduction_date', [$fromStr, $toStr])
            ->with(['user'])
            ->orderBy('deduction_date')
            ->orderBy('id')
            ->get();

        $lines = collect();

        $lines->push([
            'date' => $from->copy(),
            'sort' => $fromStr.'-O-00000000',
            'type' => 'opening',
            'reference' => '—',
            'description' => 'Opening balance',
            'paid' => 0.0,
            'deducted' => 0.0,
            'performed_by' => '—',
            'user_id' => null,
            'is_opening' => true,
        ]);

        foreach ($advances as $advance) {
            $lines->push($this->mapAdvanceLine($advance));
        }

        foreach ($deductions as $deduction) {
            $lines->push($this->mapDeductionLine($deduction));
        }

        $lines = $lines->sortBy('sort')->values();

        $running = $openingBalance;
        $lines = $lines->map(function (array $line) use (&$running) {
            if (! empty($line['is_opening'])) {
                $line['balance'] = round($running, 2);

                return $line;
            }
            $running += $line['paid'] - $line['deducted'];
            $line['balance'] = round($running, 2);

            return $line;
        })->values();

        $periodPaid = (float) $advances->sum('amount');
        $periodDeducted = (float) $deductions->sum('amount');
        $closingBalance = round($openingBalance + $periodPaid - $periodDeducted, 2);

        return [
            'lines' => $lines,
            'totals' => [
                'paid' => round($periodPaid, 2),
                'deducted' => round($periodDeducted, 2),
                'balance' => $closingBalance,
                'advances' => round($periodPaid, 2),
                'applied' => round($periodDeducted, 2),
                'opening_balance' => $openingBalance,
                'closing_balance' => $closingBalance,
            ],
            'opening_balance' => $openingBalance,
            'period' => [
                'from' => $fromStr,
                'to' => $toStr,
            ],
        ];
    }

    private function mapAdvanceLine(SupplierAdvance $advance): array
    {
        return [
            'date' => $advance->advance_date,
            'sort' => $advance->advance_date->format('Y-m-d').'-A-'.str_pad((string) $advance->id, 8, '0', STR_PAD_LEFT),
            'type' => 'advance',
            'reference' => $advance->reference ?: ('SADV-'.$advance->id),
            'description' => $advance->description ?: 'Advance payment',
            'paid' => (float) $advance->amount,
            'deducted' => 0.0,
            'performed_by' => $advance->user?->name ?? '—',
            'user_id' => $advance->user_id,
        ];
    }

    private function mapDeductionLine(SupplierAdvanceDeduction $deduction): array
    {
        return [
            'date' => $deduction->deduction_date,
            'sort' => $deduction->deduction_date->format('Y-m-d').'-D-'.str_pad((string) $deduction->id, 8, '0', STR_PAD_LEFT),
            'type' => 'deduction',
            'reference' => $this->formatDeductionReference($deduction),
            'description' => $deduction->description ?: $this->defaultDeductionDescription($deduction),
            'paid' => 0.0,
            'deducted' => (float) $deduction->amount,
            'performed_by' => $deduction->user?->name ?? '—',
            'user_id' => $deduction->user_id,
        ];
    }

    private function formatDeductionReference(SupplierAdvanceDeduction $deduction): string
    {
        if ($deduction->source_type && $deduction->source_id) {
            return $deduction->source_type.' #'.$deduction->source_id;
        }

        return $deduction->source_type ?: 'deduction';
    }

    private function defaultDeductionDescription(SupplierAdvanceDeduction $deduction): string
    {
        return match ($deduction->source_type) {
            'cash_purchase' => 'Applied to cash purchase #'.($deduction->source_id ?? ''),
            'supplier_advance_refund' => 'Cash returned by supplier (receipt #'.($deduction->source_id ?? '').')',
            'supplier_advance_expense' => 'Applied to expense (journal #'.($deduction->source_id ?? '').')',
            default => 'Advance applied',
        };
    }
}
