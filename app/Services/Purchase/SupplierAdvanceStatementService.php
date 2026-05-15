<?php

namespace App\Services\Purchase;

use App\Models\Purchase\SupplierAdvance;
use App\Models\Purchase\SupplierAdvanceDeduction;
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
            $lines->push([
                'date' => $advance->advance_date,
                'sort' => $advance->advance_date->format('Y-m-d').'-A-'.str_pad((string) $advance->id, 8, '0', STR_PAD_LEFT),
                'type' => 'advance',
                'reference' => $advance->reference ?: ('SADV-'.$advance->id),
                'description' => $advance->description ?: 'Advance payment',
                'paid' => (float) $advance->amount,
                'deducted' => 0.0,
                'performed_by' => $advance->user?->name ?? '—',
                'user_id' => $advance->user_id,
            ]);
        }

        foreach ($deductions as $deduction) {
            $lines->push([
                'date' => $deduction->deduction_date,
                'sort' => $deduction->deduction_date->format('Y-m-d').'-D-'.str_pad((string) $deduction->id, 8, '0', STR_PAD_LEFT),
                'type' => 'deduction',
                'reference' => $this->formatDeductionReference($deduction),
                'description' => $deduction->description ?: $this->defaultDeductionDescription($deduction),
                'paid' => 0.0,
                'deducted' => (float) $deduction->amount,
                'performed_by' => $deduction->user?->name ?? '—',
                'user_id' => $deduction->user_id,
            ]);
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
            default => 'Advance applied',
        };
    }
}
