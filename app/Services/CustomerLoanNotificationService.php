<?php

namespace App\Services;

use App\Helpers\SmsHelper;
use App\Models\CashCollateral;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CustomerLoanNotificationService
{
    public function sendLoanGranted(
        CashCollateral $collateral,
        float $amount,
        string $loanTypeLabel,
        string $notes,
        string $depositDate,
        ?string $actorName = null
    ): void {
        $customerName = $collateral->customer?->name ?? 'mteja';
        $date = Carbon::parse($depositDate)->format('d/m/Y');
        $reason = $this->truncate($notes);
        $by = $this->actorSuffix($actorName);

        $message = sprintf(
            'Mkopo: Mteja %s amepata Tsh %s. Aina: %s. Sababu: %s. Tarehe %s.%s',
            $customerName,
            $this->fmtMoney($amount),
            $loanTypeLabel,
            $reason,
            $date,
            $by
        );

        $this->sendToCompany($this->resolveCompanyId($collateral), $message, 'loan_granted');
    }

    public function sendLoanRepayment(
        CashCollateral $collateral,
        float $amount,
        string $notes,
        string $withdrawalDate,
        float $remainingBalance,
        ?string $actorName = null
    ): void {
        $customerName = $collateral->customer?->name ?? 'mteja';
        $date = Carbon::parse($withdrawalDate)->format('d/m/Y');
        $reason = $this->truncate($notes);
        $by = $this->actorSuffix($actorName);

        $message = sprintf(
            'Mkopo: Mteja %s amelipa Tsh %s. Sababu: %s. Tarehe %s. Salio %s.%s',
            $customerName,
            $this->fmtMoney($amount),
            $reason,
            $date,
            $this->fmtMoney($remainingBalance),
            $by
        );

        $this->sendToCompany($this->resolveCompanyId($collateral), $message, 'loan_repayment');
    }

    public function sendLoanDeleted(
        int $companyId,
        string $customerName,
        float $amount,
        ?string $loanTypeLabel,
        string $transactionDate,
        ?string $actorName = null,
        ?string $notes = null
    ): void {
        $date = Carbon::parse($transactionDate)->format('d/m/Y');
        $typePart = $loanTypeLabel ? ' ('.$loanTypeLabel.')' : '';
        $notesPart = $this->notesPart($notes);
        $by = $this->actorSuffix($actorName);

        $message = sprintf(
            'Mkopo: Muamala wa mkopo wa %s Tsh %s%s tarehe %s umefutwa.%s%s',
            $customerName,
            $this->fmtMoney($amount),
            $typePart,
            $date,
            $notesPart,
            $by
        );

        $this->sendToCompany($companyId, $message, 'loan_deleted');
    }

    public function sendRepaymentDeleted(
        int $companyId,
        string $customerName,
        float $amount,
        string $transactionDate,
        ?string $actorName = null,
        ?string $notes = null
    ): void {
        $date = Carbon::parse($transactionDate)->format('d/m/Y');
        $notesPart = $this->notesPart($notes);
        $by = $this->actorSuffix($actorName);

        $message = sprintf(
            'Mkopo: Malipo ya %s Tsh %s tarehe %s yamefutwa.%s%s',
            $customerName,
            $this->fmtMoney($amount),
            $date,
            $notesPart,
            $by
        );

        $this->sendToCompany($companyId, $message, 'repayment_deleted');
    }

    public function sendCollateralAccountDeleted(
        CashCollateral $collateral,
        ?string $actorName = null
    ): void {
        $customerName = $collateral->customer?->name ?? 'mteja';
        $typeName = $collateral->type?->name ?? 'Mkopo';
        $by = $this->actorSuffix($actorName);

        $message = sprintf(
            'Mkopo: Akaunti ya mkopo ya %s (%s) imefutwa.%s',
            $customerName,
            $typeName,
            $by
        );

        $this->sendToCompany($this->resolveCompanyId($collateral), $message, 'collateral_deleted');
    }

    public function resolveCompanyId(CashCollateral $collateral): int
    {
        if (! empty($collateral->company_id)) {
            return (int) $collateral->company_id;
        }

        if ($collateral->relationLoaded('customer') && $collateral->customer?->company_id) {
            return (int) $collateral->customer->company_id;
        }

        $customerCompanyId = $collateral->customer()->value('company_id');

        if ($customerCompanyId) {
            return (int) $customerCompanyId;
        }

        return (int) (auth()->user()?->company_id ?? 0);
    }

    private function sendToCompany(int $companyId, string $message, string $logType): void
    {
        try {
            $phone = $this->companyPhone($companyId);

            if ($phone === null) {
                Log::warning('Customer loan SMS skipped: company phone not set.', [
                    'company_id' => $companyId,
                    'type' => $logType,
                ]);

                return;
            }

            if (! SmsHelper::isConfigured()) {
                Log::warning('Customer loan SMS skipped: SMS gateway not configured.', [
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
                Log::warning('Customer loan SMS failed.', [
                    'company_id' => $companyId,
                    'type' => $logType,
                    'error' => $result['error'] ?? 'unknown',
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Customer loan SMS exception: '.$e->getMessage(), [
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

        return ' Sababu: '.$text.'.';
    }

    private function truncate(string $text, int $max = 60): string
    {
        $text = trim($text);

        if ($text === '') {
            return '—';
        }

        if (mb_strlen($text) > $max) {
            return mb_substr($text, 0, $max - 3).'...';
        }

        return $text;
    }

    private function fmtMoney(float $amount): string
    {
        return number_format($amount, 2, '.', ',');
    }
}
