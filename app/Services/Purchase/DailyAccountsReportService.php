<?php

namespace App\Services\Purchase;

use App\Models\Purchase\DailyManunuziRecord;
use App\Models\Purchase\DailyMatumiziRecord;
use App\Models\Purchase\DailyMauzoRecord;
use App\Models\Purchase\DailyStooRecord;
use Carbon\Carbon;

class DailyAccountsReportService
{
    public function build(int $companyId, ?int $branchId, int $employeeId, string $entryDate): array
    {
        $date = Carbon::parse($entryDate)->toDateString();

        $scope = function ($query) use ($companyId, $branchId, $employeeId, $date) {
            $query->where('company_id', $companyId)
                ->where('employee_id', $employeeId)
                ->whereDate('entry_date', $date);

            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
        };

        $mauzoLines = $this->flattenAmountLines(
            DailyMauzoRecord::query()->tap($scope)->with('lines')->orderBy('id')->get(),
            'kiasi'
        );
        $matumiziLines = $this->flattenAmountLines(
            DailyMatumiziRecord::query()->tap($scope)->with('lines')->orderBy('id')->get(),
            'kiasi'
        );
        $manunuziLines = $this->flattenAmountLines(
            DailyManunuziRecord::query()->tap($scope)->with('lines')->orderBy('id')->get(),
            'kiasi'
        );

        $mauzoTotal = (float) collect($mauzoLines)->sum('amount');
        $matumiziTotal = (float) collect($matumiziLines)->sum('amount');
        $manunuziTotal = (float) collect($manunuziLines)->sum('amount');
        $matumiziManunuziTotal = $matumiziTotal + $manunuziTotal;

        $stooRecords = DailyStooRecord::query()
            ->tap($scope)
            ->with('lines')
            ->orderBy('bidhaa')
            ->orderBy('id')
            ->get();

        $stooGroups = $stooRecords->map(fn (DailyStooRecord $record) => [
            'bidhaa' => $record->bidhaa,
            'lines' => $record->lines->map(fn ($line) => [
                'maelezo' => $line->maelezo,
                'thamani' => (float) $line->thamani,
            ])->values()->all(),
        ])->values()->all();

        return [
            'entry_date' => $date,
            'entry_date_formatted' => Carbon::parse($date)->format('d/m/Y'),
            'mauzo_lines' => $mauzoLines,
            'mauzo_total' => $mauzoTotal,
            'matumizi_lines' => $matumiziLines,
            'matumizi_total' => $matumiziTotal,
            'manunuzi_lines' => $manunuziLines,
            'manunuzi_total' => $manunuziTotal,
            'matumizi_manunuzi_total' => $matumiziManunuziTotal,
            'baki' => $mauzoTotal - $matumiziManunuziTotal,
            'stoo_groups' => $stooGroups,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>  $records
     * @return list<array{maelezo: string, amount: float}>
     */
    private function flattenAmountLines($records, string $amountColumn): array
    {
        $lines = [];

        foreach ($records as $record) {
            foreach ($record->lines as $line) {
                $lines[] = [
                    'maelezo' => $line->maelezo,
                    'amount' => (float) $line->{$amountColumn},
                ];
            }
        }

        return $lines;
    }
}
