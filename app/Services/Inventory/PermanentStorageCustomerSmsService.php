<?php

namespace App\Services\Inventory;

use App\Helpers\SmsHelper;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PermanentStorageCustomerSmsService
{
    public function sendGharama(Customer $customer, string $sababu, float $kiasi, string $entryDate): void
    {
        $this->send(
            $customer,
            sprintf(
                'Gharama: %s Tsh %s tarehe %s. Asante.',
                $sababu,
                $this->fmtMoney($kiasi),
                Carbon::parse($entryDate)->format('d/m/Y')
            ),
            'gharama'
        );
    }

    public function sendMalipo(Customer $customer, string $sababu, float $kiasi, string $entryDate): void
    {
        $this->send(
            $customer,
            sprintf(
                'Malipo: %s Tsh %s tarehe %s. Asante.',
                $sababu,
                $this->fmtMoney($kiasi),
                Carbon::parse($entryDate)->format('d/m/Y')
            ),
            'malipo'
        );
    }

    private function send(Customer $customer, string $message, string $logType): void
    {
        try {
            $phone = trim((string) ($customer->phone ?? ''));

            if ($phone === '') {
                Log::warning('Permanent storage SMS skipped: customer phone not set.', [
                    'customer_id' => $customer->id,
                    'type' => $logType,
                ]);

                return;
            }

            if (! SmsHelper::isConfigured()) {
                Log::warning('Permanent storage SMS skipped: SMS gateway not configured.', [
                    'customer_id' => $customer->id,
                    'type' => $logType,
                ]);

                return;
            }

            $normalized = function_exists('normalize_phone_number')
                ? normalize_phone_number($phone)
                : $phone;

            $result = SmsHelper::send($normalized ?: $phone, $message);

            if (! ($result['success'] ?? false)) {
                Log::warning('Permanent storage SMS failed.', [
                    'customer_id' => $customer->id,
                    'type' => $logType,
                    'error' => $result['error'] ?? 'unknown',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Permanent storage SMS exception: '.$e->getMessage(), [
                'customer_id' => $customer->id,
                'type' => $logType,
            ]);
        }
    }

    private function fmtMoney(float $amount): string
    {
        return number_format($amount, 2, '.', ',');
    }
}
