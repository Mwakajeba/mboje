<?php

namespace App\Services\Purchase;

use App\Models\Purchase\DriverTripMapatoLine;
use App\Models\Purchase\DriverTripMapatoRecord;
use App\Models\Purchase\DriverTripMatumiziLine;
use App\Models\Purchase\DriverTripMatumiziRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DriverTripReportLineDeletionService
{
    /** @var array<string, array{line: class-string, record: class-string, line_fk: string}> */
    private const TYPES = [
        'mapato' => [
            'line' => DriverTripMapatoLine::class,
            'record' => DriverTripMapatoRecord::class,
            'line_fk' => 'driver_trip_mapato_record_id',
        ],
        'matumizi' => [
            'line' => DriverTripMatumiziLine::class,
            'record' => DriverTripMatumiziRecord::class,
            'line_fk' => 'driver_trip_matumizi_record_id',
        ],
    ];

    public function deleteLine(string $type, int $lineId, int $tripId, int $companyId, ?int $branchId): void
    {
        $config = $this->config($type);

        DB::transaction(function () use ($config, $lineId, $tripId, $companyId, $branchId) {
            $lineModel = $config['line'];
            /** @var Model $line */
            $line = $lineModel::query()->findOrFail($lineId);

            $record = $this->authorizedRecord(
                $config['record'],
                (int) $line->{$config['line_fk']},
                $tripId,
                $companyId,
                $branchId
            );

            $line->delete();
            $this->deleteRecordIfEmpty($config['line'], $config['record'], $config['line_fk'], $record->id);
        });
    }

    /** @return array{line: class-string, record: class-string, line_fk: string} */
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
        int $tripId,
        int $companyId,
        ?int $branchId
    ): Model {
        $query = $recordModel::query()
            ->where('company_id', $companyId)
            ->where('driver_trip_id', $tripId)
            ->whereKey($recordId);

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            });
        }

        return $query->firstOrFail();
    }

    /**
     * @param  class-string  $lineModel
     * @param  class-string  $recordModel
     */
    private function deleteRecordIfEmpty(string $lineModel, string $recordModel, string $lineFk, int $recordId): void
    {
        $remaining = $lineModel::query()->where($lineFk, $recordId)->count();

        if ($remaining === 0) {
            $recordModel::query()->whereKey($recordId)->delete();
        }
    }
}
