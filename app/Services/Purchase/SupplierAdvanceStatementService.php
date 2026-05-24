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

        $advanceLines = $advances->map(fn ($advance) => $this->mapAdvanceLine($advance));
        $deductionLines = $deductions->map(fn ($deduction) => $this->mapDeductionLine($deduction));
        $closingBalance = round((float) $advanceLines->sum('paid') - (float) $deductionLines->sum('deducted'), 2);

        $sections = $this->buildStructuredSections(
            $advanceLines,
            $deductionLines,
            openingBalance: 0.0,
            closingBalance: $closingBalance,
            includeOpening: false,
            includeClosing: true,
            closingDate: Carbon::today(),
        );

        $totalPaid = (float) $advances->sum('amount');
        $totalDeducted = (float) $deductions->sum('amount');

        return array_merge($sections, [
            'lines' => $this->flattenSectionsToLines($sections),
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
        ]);
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
            throw new \InvalidArgumentException('Tarehe ya kuanzia haiwezi kuwa baada ya tarehe ya mwisho.');
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

        $periodPaid = (float) $advances->sum('amount');
        $periodDeducted = (float) $deductions->sum('amount');
        $closingBalance = round($openingBalance + $periodPaid - $periodDeducted, 2);

        $advanceLines = $advances->map(fn ($advance) => $this->mapAdvanceLine($advance));
        $deductionLines = $deductions->map(fn ($deduction) => $this->mapDeductionLine($deduction));

        $sections = $this->buildStructuredSections(
            $advanceLines,
            $deductionLines,
            openingBalance: $openingBalance,
            closingBalance: $closingBalance,
            includeOpening: true,
            includeClosing: true,
            openingDate: $from,
            closingDate: $to,
        );

        return array_merge($sections, [
            'lines' => $this->flattenSectionsToLines($sections),
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
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $advanceLines
     * @param  Collection<int, array<string, mixed>>  $deductionLines
     * @return array{opening_row: ?array, malipo_lines: Collection, matumizi_lines: Collection, closing_row: ?array}
     */
    private function buildStructuredSections(
        Collection $advanceLines,
        Collection $deductionLines,
        float $openingBalance,
        ?float $closingBalance,
        bool $includeOpening,
        bool $includeClosing,
        ?Carbon $openingDate = null,
        ?Carbon $closingDate = null,
    ): array {
        $openingRow = null;
        if ($includeOpening) {
            $openDate = $openingDate ?? Carbon::today();
            $openingRow = [
                'date' => $openDate->copy(),
                'description' => 'Salio la kufungua',
                'balance' => round($openingBalance, 2),
                'is_opening' => true,
            ];
        }

        $malipoSorted = $advanceLines->sortBy('sort')->values();
        $matumiziSorted = $deductionLines->sortBy('sort')->values();

        $malipoLines = $this->applyRunningBalance($malipoSorted, $openingBalance);
        $balanceAfterMalipo = round(
            $openingBalance + (float) $malipoSorted->sum('paid'),
            2
        );
        $matumiziLines = $this->applyRunningBalance($matumiziSorted, $balanceAfterMalipo);

        $closingRow = null;
        if ($includeClosing) {
            $closeDate = $closingDate ?? Carbon::today();
            $finalBalance = $closingBalance ?? round(
                $balanceAfterMalipo - (float) $matumiziSorted->sum('deducted'),
                2
            );
            $closingRow = [
                'date' => $closeDate->copy(),
                'description' => 'Salio la kufunga/Baki',
                'balance' => round($finalBalance, 2),
                'is_closing' => true,
            ];
        }

        return [
            'opening_row' => $openingRow,
            'malipo_lines' => $malipoLines,
            'matumizi_lines' => $matumiziLines,
            'closing_row' => $closingRow,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $lines
     * @return Collection<int, array<string, mixed>>
     */
    private function applyRunningBalance(Collection $lines, float $startBalance): Collection
    {
        $running = $startBalance;

        return $lines->map(function (array $line) use (&$running) {
            $running += (float) ($line['paid'] ?? 0) - (float) ($line['deducted'] ?? 0);
            $line['balance'] = round($running, 2);

            return $line;
        })->values();
    }

    /**
     * @param  array{opening_row: ?array, malipo_lines: Collection, matumizi_lines: Collection, closing_row: ?array}  $sections
     */
    private function flattenSectionsToLines(array $sections): Collection
    {
        $lines = collect();
        if (! empty($sections['opening_row'])) {
            $row = $sections['opening_row'];
            $row['paid'] = 0.0;
            $row['deducted'] = 0.0;
            $row['performed_by'] = '—';
            $lines->push($row);
        }
        foreach ($sections['malipo_lines'] as $line) {
            $lines->push($line);
        }
        foreach ($sections['matumizi_lines'] as $line) {
            $lines->push($line);
        }
        if (! empty($sections['closing_row'])) {
            $row = $sections['closing_row'];
            $row['paid'] = 0.0;
            $row['deducted'] = 0.0;
            $row['performed_by'] = '—';
            $lines->push($row);
        }

        return $lines->values();
    }

    private function mapAdvanceLine(SupplierAdvance $advance): array
    {
        return [
            'date' => $advance->advance_date,
            'sort' => $advance->advance_date->format('Y-m-d').'-A-'.str_pad((string) $advance->id, 8, '0', STR_PAD_LEFT),
            'type' => 'advance',
            'reference' => $advance->reference ?: ('SADV-'.$advance->id),
            'description' => $advance->description ?: 'Malipo ya awali',
            'paid' => (float) $advance->amount,
            'deducted' => 0.0,
            'performed_by' => $advance->user?->name ?? '—',
            'user_id' => $advance->user_id,
        ];
    }

    private function mapDeductionLine(SupplierAdvanceDeduction $deduction): array
    {
        $sourceType = (string) ($deduction->source_type ?? '');

        return [
            'date' => $deduction->deduction_date,
            'sort' => $deduction->deduction_date->format('Y-m-d').'-D-'.str_pad((string) $deduction->id, 8, '0', STR_PAD_LEFT),
            'type' => 'deduction',
            'deduction_id' => $deduction->id,
            'source_type' => $sourceType,
            'source_id' => $deduction->source_id,
            'reference' => $this->formatDeductionReference($deduction),
            'description' => $deduction->description ?: $this->defaultDeductionDescription($deduction),
            'paid' => 0.0,
            'deducted' => (float) $deduction->amount,
            'performed_by' => $deduction->user?->name ?? '—',
            'user_id' => $deduction->user_id,
            'can_delete' => $sourceType === 'supplier_advance_expense',
            'is_manunuzi' => $sourceType === 'cash_purchase',
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
            'cash_purchase' => 'Matumizi kwa ununuzi wa cash #'.($deduction->source_id ?? ''),
            'supplier_advance_refund' => 'Fedha zilirudishwa na msambazaji (risiti #'.($deduction->source_id ?? '').')',
            'supplier_advance_expense' => 'Matumizi (jarida #'.($deduction->source_id ?? '').')',
            'supplier_advance_manunuzi' => 'Manunuzi',
            default => 'Malipo ya awali yametumika',
        };
    }
}
