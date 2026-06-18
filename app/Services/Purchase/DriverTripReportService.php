<?php

namespace App\Services\Purchase;

use App\Models\Purchase\DriverTrip;
use App\Models\Purchase\DriverTripMapatoLine;
use App\Models\Purchase\DriverTripMatumiziLine;

class DriverTripReportService
{
    /**
     * @return array{
     *     trip: DriverTrip,
     *     trip_date_formatted: string,
     *     mapato_lines: array<int, array{id: int, maelezo: string, amount: float, entry_date: string}>,
     *     matumizi_lines: array<int, array{id: int, maelezo: string, amount: float, entry_date: string}>,
     *     mapato_total: float,
     *     matumizi_total: float,
     *     faida: float
     * }
     */
    public function build(DriverTrip $trip): array
    {
        $mapatoLines = DriverTripMapatoLine::query()
            ->whereHas('record', fn ($q) => $q->where('driver_trip_id', $trip->id))
            ->with('record')
            ->orderBy('id')
            ->get()
            ->map(fn ($line) => [
                'id' => $line->id,
                'maelezo' => $line->maelezo,
                'amount' => (float) $line->kiasi,
                'entry_date' => $line->record->entry_date?->format('Y-m-d') ?? '',
            ])
            ->values()
            ->all();

        $matumiziLines = DriverTripMatumiziLine::query()
            ->whereHas('record', fn ($q) => $q->where('driver_trip_id', $trip->id))
            ->with('record')
            ->orderBy('id')
            ->get()
            ->map(fn ($line) => [
                'id' => $line->id,
                'maelezo' => $line->maelezo,
                'amount' => (float) $line->kiasi,
                'entry_date' => $line->record->entry_date?->format('Y-m-d') ?? '',
            ])
            ->values()
            ->all();

        $mapatoTotal = (float) collect($mapatoLines)->sum('amount');
        $matumiziTotal = (float) collect($matumiziLines)->sum('amount');

        return [
            'trip' => $trip,
            'trip_date_formatted' => $trip->trip_date?->format('d/m/Y') ?? '—',
            'mapato_lines' => $mapatoLines,
            'matumizi_lines' => $matumiziLines,
            'mapato_total' => $mapatoTotal,
            'matumizi_total' => $matumiziTotal,
            'faida' => $mapatoTotal - $matumiziTotal,
        ];
    }
}
