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

class DailyAccountsReportLineService
{
    /** @var array<string, array{line: class-string, record: class-string, line_fk: string, amount: string}> */
    public const TYPES = [
        'mauzo' => [
            'line' => DailyMauzoLine::class,
            'record' => DailyMauzoRecord::class,
            'line_fk' => 'daily_mauzo_record_id',
            'amount' => 'kiasi',
        ],
        'matumizi' => [
            'line' => DailyMatumiziLine::class,
            'record' => DailyMatumiziRecord::class,
            'line_fk' => 'daily_matumizi_record_id',
            'amount' => 'kiasi',
        ],
        'manunuzi' => [
            'line' => DailyManunuziLine::class,
            'record' => DailyManunuziRecord::class,
            'line_fk' => 'daily_manunuzi_record_id',
            'amount' => 'kiasi',
        ],
        'stoo' => [
            'line' => DailyStooLine::class,
            'record' => DailyStooRecord::class,
            'line_fk' => 'daily_stoo_record_id',
            'amount' => 'thamani',
        ],
    ];

    public function amountField(string $type): string
    {
        return $this->config($type)['amount'];
    }

    public function updateLine(string $type, int $lineId, int $companyId, ?int $branchId, array $data): void
    {
        $config = $this->config($type);
        $amountField = $config['amount'];
        $lineFk = $config['line_fk'];
        $recordModel = $config['record'];
        $lineModel = $config['line'];

        $targetEmployeeId = (int) $data['employee_id'];
        $targetEntryDate = Carbon::parse($data['entry_date'])->toDateString();
        $targetBidhaa = $type === 'stoo' ? ($data['bidhaa'] ?? null) : null;

        DB::transaction(function () use (
            $config,
            $type,
            $lineId,
            $companyId,
            $branchId,
            $data,
            $amountField,
            $lineFk,
            $recordModel,
            $lineModel,
            $targetEmployeeId,
            $targetEntryDate,
            $targetBidhaa
        ) {
            /** @var Model $line */
            $line = $config['line']::query()->findOrFail($lineId);
            $sourceRecord = $this->authorizedRecord(
                $recordModel,
                (int) $line->{$lineFk},
                $companyId,
                $branchId
            );

            $line->update([
                'maelezo' => $data['maelezo'],
                $amountField => $data[$amountField],
            ]);

            $targetRecord = $this->findOrCreateRecord(
                $recordModel,
                $sourceRecord,
                $companyId,
                $branchId,
                $targetEmployeeId,
                $targetEntryDate,
                $targetBidhaa
            );

            if ((int) $line->{$lineFk} !== (int) $targetRecord->id) {
                $oldRecordId = (int) $line->{$lineFk};
                $line->update([$lineFk => $targetRecord->id]);
                $this->deleteRecordIfEmpty($recordModel, $lineModel, $lineFk, $oldRecordId);
            }
        });
    }

    /**
     * @param  class-string  $recordModel
     */
    private function findOrCreateRecord(
        string $recordModel,
        Model $sourceRecord,
        int $companyId,
        ?int $branchId,
        int $employeeId,
        string $entryDate,
        ?string $bidhaa = null
    ): Model {
        $recordBranchId = $sourceRecord->branch_id;

        $query = $recordModel::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->whereDate('entry_date', $entryDate);

        if ($recordBranchId) {
            $query->where('branch_id', $recordBranchId);
        } else {
            $query->whereNull('branch_id');
        }

        if ($bidhaa !== null) {
            $query->where('bidhaa', $bidhaa);
        }

        $existing = $query->first();
        if ($existing) {
            return $existing;
        }

        $recordData = [
            'company_id' => $companyId,
            'branch_id' => $recordBranchId,
            'employee_id' => $employeeId,
            'entry_date' => $entryDate,
            'user_id' => $sourceRecord->user_id,
        ];

        if ($bidhaa !== null) {
            $recordData['bidhaa'] = $bidhaa;
        }

        return $recordModel::create($recordData);
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
    public function authorizedRecord(
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

    public function config(string $type): array
    {
        if (! isset(self::TYPES[$type])) {
            throw new InvalidArgumentException('Aina ya rekodi si sahihi.');
        }

        return self::TYPES[$type];
    }
}
