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

    public function deleteLine(string $type, int $lineId, int $companyId, ?int $branchId): void
    {
        $config = $this->config($type);

        DB::transaction(function () use ($config, $lineId, $companyId, $branchId) {
            $lineModel = $config['line'];
            /** @var Model $line */
            $line = $lineModel::query()->findOrFail($lineId);
            $record = $this->authorizedRecord(
                $config['record'],
                (int) $line->{$config['line_fk']},
                $companyId,
                $branchId
            );

            $line->delete();
            $this->deleteRecordIfEmpty($config['record'], $config['line'], $config['line_fk'], $record->id);
        });
    }

    public function deleteSectionForDate(
        string $type,
        int $companyId,
        ?int $branchId,
        int $employeeId,
        string $entryDate
    ): void {
        $config = $this->config($type);
        $date = Carbon::parse($entryDate)->toDateString();

        DB::transaction(function () use ($config, $companyId, $branchId, $employeeId, $date) {
            $records = $this->recordsQuery($config['record'], $companyId, $branchId, $employeeId, $date)->get();

            foreach ($records as $record) {
                $config['line']::query()->where($config['line_fk'], $record->id)->delete();
                $record->delete();
            }
        });
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
}
