<?php

namespace App\Services\Inventory;

use App\Helpers\SmsHelper;
use App\Models\Company;
use App\Models\Inventory\CustomerStorageWithdrawal;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CustomerStorageNotificationService
{
    public function sendReceiptStored(
        int $companyId,
        string $customerName,
        string $itemName,
        float $quantity,
        string $receivedDate,
        float $newBalance,
        ?string $notes = null,
        ?string $actorName = null
    ): void {
        $date = Carbon::parse($receivedDate)->format('d/m/Y');
        $notesPart = $this->notesPart($notes);
        $by = $this->actorSuffix($actorName);

        $message = sprintf(
            'Stoo: Mteja %s ameleta %s idadi %s tarehe %s. Salio %s.%s%s',
            $customerName,
            $itemName,
            $this->fmtQty($quantity),
            $date,
            $this->fmtQty($newBalance),
            $notesPart,
            $by
        );

        $this->sendToCompany($companyId, $message, 'receipt_stored');
    }

    public function sendWithdrawal(
        int $companyId,
        string $customerName,
        string $itemName,
        float $quantity,
        string $reasonKey,
        ?float $price,
        float $newBalance,
        ?string $notes = null,
        ?string $actorName = null
    ): void {
        $reasonLabel = CustomerStorageWithdrawal::reasonOptions()[$reasonKey] ?? $reasonKey;
        $notesPart = $this->notesPart($notes);
        $by = $this->actorSuffix($actorName);
        $date = now()->format('d/m/Y');

        $pricePart = '';
        if ($reasonKey === 'kuuza' && $price !== null && $price > 0) {
            $total = round($quantity * $price, 2);
            $pricePart = sprintf(' Bei %s, Jumla %s.', $this->fmtMoney($price), $this->fmtMoney($total));
        }

        $message = sprintf(
            'Stoo: Mteja %s ametoa %s idadi %s. Sababu: %s.%s Tarehe %s. Salio %s.%s%s',
            $customerName,
            $itemName,
            $this->fmtQty($quantity),
            $reasonLabel,
            $pricePart,
            $date,
            $this->fmtQty($newBalance),
            $notesPart,
            $by
        );

        $this->sendToCompany($companyId, $message, 'withdrawal');
    }

    private function sendToCompany(int $companyId, string $message, string $logType): void
    {
        try {
            $phone = $this->companyPhone($companyId);

            if ($phone === null) {
                Log::warning('Customer storage SMS skipped: company phone not set.', [
                    'company_id' => $companyId,
                    'type' => $logType,
                ]);

                return;
            }

            if (! SmsHelper::isConfigured()) {
                Log::warning('Customer storage SMS skipped: SMS gateway not configured.', [
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
                Log::warning('Customer storage SMS failed.', [
                    'company_id' => $companyId,
                    'type' => $logType,
                    'error' => $result['error'] ?? 'unknown',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Customer storage SMS exception: '.$e->getMessage(), [
                'company_id' => $companyId,
                'type' => $logType,
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

    private function notesPart(?string $notes): string
    {
        $text = trim((string) $notes);

        if ($text === '') {
            return '';
        }

        if (mb_strlen($text) > 50) {
            $text = mb_substr($text, 0, 47).'...';
        }

        return ' Maelezo: '.$text.'.';
    }

    private function fmtQty(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ','), '0'), '.');
    }

    private function fmtMoney(float $amount): string
    {
        return number_format($amount, 2, '.', ',');
    }
}
