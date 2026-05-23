<?php

namespace App\Services\Purchase;

use App\Models\Purchase\DailyManunuziLine;
use App\Models\Purchase\DailyManunuziRecord;
use App\Models\Purchase\DailyMatumiziLine;
use App\Models\Purchase\DailyMatumiziRecord;
use App\Models\Purchase\DailyMauzoLine;
use App\Models\Purchase\DailyMauzoRecord;
use App\Models\Purchase\DailyStooRecord;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class DailyAccountsReportService
{
    public function build(int $companyId, ?int $branchId, int $employeeId, string $entryDate): array
    {
        $date = Carbon::parse($entryDate)->toDateString();
        $previousDate = Carbon::parse($date)->subDay();

        $mauzoLines = $this->flattenAmountLines(
            $this->recordsForDate(DailyMauzoRecord::class, $companyId, $branchId, $employeeId, $date),
            'kiasi'
        );
        $matumiziLines = $this->flattenAmountLines(
            $this->recordsForDate(DailyMatumiziRecord::class, $companyId, $branchId, $employeeId, $date),
            'kiasi'
        );
        $manunuziLines = $this->flattenAmountLines(
            $this->recordsForDate(DailyManunuziRecord::class, $companyId, $branchId, $employeeId, $date),
            'kiasi'
        );

        $mauzoTotal = (float) collect($mauzoLines)->sum('amount');
        $matumiziTotal = (float) collect($matumiziLines)->sum('amount');
        $manunuziTotal = (float) collect($manunuziLines)->sum('amount');
        $matumiziManunuziTotal = $matumiziTotal + $manunuziTotal;

        $openingBalance = $this->openingBalanceBeforeDate($companyId, $branchId, $employeeId, $date);
        $bakiNaMauzo = $openingBalance + $mauzoTotal;
        $bakiMpya = $openingBalance + $mauzoTotal - $matumiziManunuziTotal;

        $stooRecords = $this->recordsForDate(DailyStooRecord::class, $companyId, $branchId, $employeeId, $date)
            ->sortBy('bidhaa')
            ->values();

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
            'previous_date_formatted' => $previousDate->format('d/m/Y'),
            'opening_balance' => $openingBalance,
            'mauzo_lines' => $mauzoLines,
            'mauzo_total' => $mauzoTotal,
            'matumizi_lines' => $matumiziLines,
            'matumizi_total' => $matumiziTotal,
            'manunuzi_lines' => $manunuziLines,
            'manunuzi_total' => $manunuziTotal,
            'matumizi_manunuzi_total' => $matumiziManunuziTotal,
            'baki_na_mauzo' => $bakiNaMauzo,
            'baki_mpya' => $bakiMpya,
            'baki' => $bakiMpya,
            'stoo_groups' => $stooGroups,
        ];
    }

    public function openingBalanceBeforeDate(int $companyId, ?int $branchId, int $employeeId, string $beforeDate): float
    {
        $date = Carbon::parse($beforeDate)->toDateString();

        $mauzo = $this->sumKiasiBeforeDate(DailyMauzoLine::class, $companyId, $branchId, $employeeId, $date);
        $matumizi = $this->sumKiasiBeforeDate(DailyMatumiziLine::class, $companyId, $branchId, $employeeId, $date);
        $manunuzi = $this->sumKiasiBeforeDate(DailyManunuziLine::class, $companyId, $branchId, $employeeId, $date);

        return $mauzo - $matumizi - $manunuzi;
    }

    public function buildNotificationMessage(string $employeeName, array $report): string
    {
        $fmt = fn (float $n) => number_format($n, 2, '.', ',');

        return sprintf(
            'Hesabu ya %s ya tarehe %s imeingizwa tayari. Baki ya tarehe %s ni %s. Mauzo ya tarehe %s ni %s. Jumla ya baki na mauzo ni %s. Jumla ya matumizi na manunuzi ni %s. Baki mpya ni %s.',
            $employeeName,
            $report['entry_date_formatted'],
            $report['previous_date_formatted'],
            $fmt($report['opening_balance']),
            $report['entry_date_formatted'],
            $fmt($report['mauzo_total']),
            $fmt($report['baki_na_mauzo']),
            $fmt($report['matumizi_manunuzi_total']),
            $fmt($report['baki_mpya'])
        );
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $recordModel
     */
    private function recordsForDate(string $recordModel, int $companyId, ?int $branchId, int $employeeId, string $date)
    {
        $query = $recordModel::query()->with('lines');

        $this->applyRecordScope($query, $companyId, $branchId, $employeeId, $date);

        return $query->orderBy('id')->get();
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $lineModel
     */
    private function sumKiasiBeforeDate(
        string $lineModel,
        int $companyId,
        ?int $branchId,
        int $employeeId,
        string $beforeDate
    ): float {
        return (float) $lineModel::query()
            ->whereHas('record', function (Builder $q) use ($companyId, $branchId, $employeeId, $beforeDate) {
                $q->where('company_id', $companyId)
                    ->where('employee_id', $employeeId)
                    ->whereDate('entry_date', '<', $beforeDate);

                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->sum('kiasi');
    }

    private function applyRecordScope(
        Builder $query,
        int $companyId,
        ?int $branchId,
        int $employeeId,
        string $date
    ): void {
        $query->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->whereDate('entry_date', $date);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
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
