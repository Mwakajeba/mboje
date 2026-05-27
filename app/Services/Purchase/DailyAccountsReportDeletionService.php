<?php

namespace App\Services\Purchase;

use App\Models\Purchase\DailyManunuziLine;
use App\Models\Purchase\DailyManunuziRecord;
use App\Models\Purchase\DailyMatumiziLine;
use App\Models\Purchase\DailyMatumiziRecord;
use App\Models\Purchase\DailyMauzoLine;
use App\Models\Purchase\DailyMauzoRecord;
use App\Models\Purchase\DailyStooLine;
use App\Models\Purchase\DailyStooRecord;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DailyAccountsReportDeletionService
{
    /** @var array<string, array{line: class-string, record: class-string, line_fk: string}> */
    private const TYPES = [
        'mauzo' => [
            'line' => DailyMauzoLine::class,
            'record' => DailyMauzoRecord::class,
            'line_fk' => 'daily_mauzo_record_id',
        ],
        'matumizi' => [
            'line' => DailyMatumiziLine::class,
            'record' => DailyMatumiziRecord::class,
            'line_fk' => 'daily_matumizi_record_id',
        ],
        'manunuzi' => [
            'line' => DailyManunuziLine::class,
            'record' => DailyManunuziRecord::class,
            'line_fk' => 'daily_manunuzi_record_id',
        ],
        'stoo' => [
            'line' => DailyStooLine::class,
            'record' => DailyStooRecord::class,
            'line_fk' => 'daily_stoo_record_id',
        ],
    ];

    /**
     * @return array{employee_id: int, entry_date: string, kiasi: float|null}
     */
    public function deleteLine(string $type, int $lineId, int $companyId, ?int $branchId): array
    {
        $config = $this->config($type);
        $context = ['employee_id' => 0, 'entry_date' => '', 'kiasi' => null];

        DB::transaction(function () use ($config, $type, $lineId, $companyId, $branchId, &$context) {
            $lineModel = $config['line'];
            /** @var Model $line */
            $line = $lineModel::query()->findOrFail($lineId);
            $record = $this->authorizedRecord(
                $config['record'],
                (int) $line->{$config['line_fk']},
                $companyId,
                $branchId
            );

            $context = [
                'employee_id' => (int) $record->employee_id,
                'entry_date' => $record->entry_date->format('Y-m-d'),
                'kiasi' => $this->lineKiasiForType($type, $line),
            ];

            $line->delete();
            $this->deleteRecordIfEmpty($config['record'], $config['line'], $config['line_fk'], $record->id);
        });

        return $context;
    }

    /**
     * @return float|null Jumla ya kiasi kilichofutwa (mauzo/matumizi/manunuzi tu)
     */
    public function deleteSectionForDate(
        string $type,
        int $companyId,
        ?int $branchId,
        int $employeeId,
        string $entryDate
    ): ?float {
        $config = $this->config($type);
        $date = Carbon::parse($entryDate)->toDateString();
        $deletedTotal = null;

        DB::transaction(function () use ($config, $type, $companyId, $branchId, $employeeId, $date, &$deletedTotal) {
            $records = $this->recordsQuery($config['record'], $companyId, $branchId, $employeeId, $date)->get();
            $deletedTotal = $this->sumLinesKiasiForType($type, $records, $config);

            foreach ($records as $record) {
                $config['line']::query()->where($config['line_fk'], $record->id)->delete();
                $record->delete();
            }
        });

        return $deletedTotal;
    }

    public function deleteAllForDate(int $companyId, ?int $branchId, int $employeeId, string $entryDate): void
    {
        foreach (array_keys(self::TYPES) as $type) {
            $this->deleteSectionForDate($type, $companyId, $branchId, $employeeId, $entryDate);
        }
    }

    private function config(string $type): array
    {
        if (! isset(self::TYPES[$type])) {
            throw new InvalidArgumentException('Aina ya rekodi si sahihi.');
        }

        return self::TYPES[$type];
    }

    /**
     * @param  class-string  $recordModel
     */
    private function authorizedRecord(
        string $recordModel,
        int $recordId,
        int $companyId,
        ?int $branchId
    ): Model {
        $query = $recordModel::query()
            ->where('company_id', $companyId)
            ->whereKey($recordId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->firstOrFail();
    }

    /**
     * @param  class-string  $recordModel
     * @param  class-string  $lineModel
     */
    private function deleteRecordIfEmpty(
        string $recordModel,
        string $lineModel,
        string $lineFk,
        int $recordId
    ): void {
        $hasLines = $lineModel::query()->where($lineFk, $recordId)->exists();

        if (! $hasLines) {
            $recordModel::query()->whereKey($recordId)->delete();
        }
    }

    /**
     * @param  class-string  $recordModel
     */
    private function recordsQuery(
        string $recordModel,
        int $companyId,
        ?int $branchId,
        int $employeeId,
        string $date
    ) {
        $query = $recordModel::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->whereDate('entry_date', $date);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }

    private function lineKiasiForType(string $type, Model $line): ?float
    {
        if ($type === 'stoo') {
            return null;
        }

        return (float) $line->kiasi;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Model>  $records
     */
    private function sumLinesKiasiForType(string $type, $records, array $config): ?float
    {
        if ($type === 'stoo') {
            return null;
        }

        $total = 0.0;
        foreach ($records as $record) {
            $total += (float) $config['line']::query()
                ->where($config['line_fk'], $record->id)
                ->sum('kiasi');
        }

        return $total;
    }
}
