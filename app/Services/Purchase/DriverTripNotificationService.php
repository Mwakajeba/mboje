<?php

namespace App\Services\Purchase;

use App\Helpers\SmsHelper;
use App\Models\Company;
use App\Models\Purchase\DriverTrip;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DriverTripNotificationService
{
    /**
     * @param  array<int, array{maelezo: string, kiasi: mixed}>  $lines
     */
    public function sendTripCreated(DriverTrip $trip, ?string $actorName = null): void
    {
        $date = $trip->trip_date?->format('d/m/Y') ?? '—';
        $price = $this->fmtMoney((float) $trip->trip_price);
        $by = $this->actorSuffix($actorName);

        $message = sprintf(
            'Safari mpya: %s. Dereva: %s. Bei Tsh %s. Tarehe %s.%s',
            $trip->trip_name,
            $trip->driver_name,
            $price,
            $date,
            $by
        );

        $this->sendToCompany((int) $trip->company_id, $message, 'trip_created', $trip->id);
    }

    /**
     * @param  array<string, mixed>  $before
     */
    public function sendTripUpdated(DriverTrip $trip, array $before, ?string $actorName = null): void
    {
        $changes = $this->buildTripChangeSummary($before, $trip);

        if ($changes === '') {
            return;
        }

        $by = $this->actorSuffix($actorName);
        $message = sprintf(
            'Safari %s imebadilishwa:%s%s',
            $trip->trip_name,
            $changes,
            $by
        );

        $this->sendToCompany((int) $trip->company_id, $message, 'trip_updated', $trip->id);
    }

    public function sendTripDeleted(DriverTrip $trip, ?string $actorName = null): void
    {
        $date = $trip->trip_date?->format('d/m/Y') ?? '—';
        $price = $this->fmtMoney((float) $trip->trip_price);
        $by = $this->actorSuffix($actorName);

        $message = sprintf(
            'Safari imefutwa: %s. Dereva %s. Bei Tsh %s. Tarehe %s.%s',
            $trip->trip_name,
            $trip->driver_name,
            $price,
            $date,
            $by
        );

        $this->sendToCompany((int) $trip->company_id, $message, 'trip_deleted', $trip->id);
    }

    /**
     * @param  array<int, array{maelezo: string, kiasi: mixed}>  $lines
     */
    public function sendMapatoEntered(
        DriverTrip $trip,
        array $lines,
        float $total,
        string $entryDate,
        ?string $actorName = null
    ): void {
        $this->sendEntrySms($trip, 'Mapato', $lines, $total, $entryDate, $actorName, 'mapato');
    }

    /**
     * @param  array<int, array{maelezo: string, kiasi: mixed}>  $lines
     */
    public function sendMatumiziEntered(
        DriverTrip $trip,
        array $lines,
        float $total,
        string $entryDate,
        ?string $actorName = null
    ): void {
        $this->sendEntrySms($trip, 'Matumizi', $lines, $total, $entryDate, $actorName, 'matumizi');
    }

    /**
     * @param  array<int, array{maelezo: string, kiasi: mixed}>  $lines
     */
    private function sendEntrySms(
        DriverTrip $trip,
        string $typeLabel,
        array $lines,
        float $total,
        string $entryDate,
        ?string $actorName,
        string $logType
    ): void {
        $date = Carbon::parse($entryDate)->format('d/m/Y');
        $summary = $this->linesSummary($lines);
        $by = $this->actorSuffix($actorName);

        $message = sprintf(
            'Safari %s: %s Tsh %s tarehe %s.%s%s',
            $trip->trip_name,
            $typeLabel,
            $this->fmtMoney($total),
            $date,
            $summary,
            $by
        );

        $this->sendToCompany((int) $trip->company_id, $message, $logType, $trip->id);
    }

    private function sendToCompany(int $companyId, string $message, string $logType, ?int $tripId = null): void
    {
        try {
            $phone = $this->companyPhone($companyId);

            if ($phone === null) {
                Log::warning('Driver trip SMS skipped: company phone not set.', [
                    'company_id' => $companyId,
                    'type' => $logType,
                    'trip_id' => $tripId,
                ]);

                return;
            }

            if (! SmsHelper::isConfigured()) {
                Log::warning('Driver trip SMS skipped: SMS gateway not configured.', [
                    'company_id' => $companyId,
                    'type' => $logType,
                ]);

                return;
            }

            $normalized = function_exists('normalize_phone_number')
                ? normalize_phone_number($phone)
                : $phone;

            $result = SmsHelper::send($normalized ?: $phone, $message);

            if (! ($result['success'] ?? false)) {
                Log::warning('Driver trip SMS failed.', [
                    'company_id' => $companyId,
                    'type' => $logType,
                    'trip_id' => $tripId,
                    'error' => $result['error'] ?? 'unknown',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Driver trip SMS exception: '.$e->getMessage(), [
                'company_id' => $companyId,
                'type' => $logType,
                'trip_id' => $tripId,
            ]);
        }
    }

    private function companyPhone(int $companyId): ?string
    {
        $company = Company::query()->find($companyId);
        $phone = trim((string) ($company?->phone ?? ''));

        return $phone !== '' ? $phone : null;
    }

    private function actorSuffix(?string $actorName): string
    {
        $name = trim((string) $actorName);

        return $name !== '' ? ' Na '.$name.'.' : '.';
    }

    private function fmtMoney(float $amount): string
    {
        return number_format($amount, 2, '.', ',');
    }

    /**
     * @param  array<int, array{maelezo: string, kiasi: mixed}>  $lines
     */
    private function linesSummary(array $lines): string
    {
        if ($lines === []) {
            return '';
        }

        $first = trim((string) ($lines[0]['maelezo'] ?? ''));
        $count = count($lines);

        if ($count === 1 && $first !== '') {
            $short = mb_strlen($first) > 40 ? mb_substr($first, 0, 37).'...' : $first;

            return ' '.$short.'.';
        }

        if ($first !== '') {
            $short = mb_strlen($first) > 30 ? mb_substr($first, 0, 27).'...' : $first;

            return sprintf(' Mistari %d (%s...).', $count, $short);
        }

        return sprintf(' Mistari %d.', $count);
    }

    /**
     * @param  array<string, mixed>  $before
     */
    private function buildTripChangeSummary(array $before, DriverTrip $trip): string
    {
        $parts = [];

        $fields = [
            'trip_name' => 'Jina',
            'driver_name' => 'Dereva',
            'vehicle_info' => 'Gari',
            'trip_price' => 'Bei',
            'trip_date' => 'Tarehe',
            'status' => 'Hali',
        ];

        foreach ($fields as $field => $label) {
            $old = $before[$field] ?? null;
            $new = $trip->{$field};

            if ($field === 'trip_date') {
                $old = $old ? Carbon::parse($old)->format('d/m/Y') : null;
                $new = $new ? Carbon::parse($new)->format('d/m/Y') : null;
            } elseif ($field === 'trip_price') {
                $old = $old !== null ? $this->fmtMoney((float) $old) : null;
                $new = $this->fmtMoney((float) $new);
            } elseif ($field === 'status') {
                $old = $old ? (DriverTrip::statusOptions()[$old] ?? $old) : null;
                $new = DriverTrip::statusOptions()[$new] ?? $new;
            } else {
                $old = $old !== null ? trim((string) $old) : '';
                $new = trim((string) ($new ?? ''));
                if ($old === '' && $new === '') {
                    continue;
                }
            }

            if ((string) $old !== (string) $new) {
                $parts[] = sprintf(' %s %s->%s', $label, $old ?: '—', $new ?: '—');
            }
        }

        return $parts === [] ? '' : implode(';', $parts).'.';
    }
}
