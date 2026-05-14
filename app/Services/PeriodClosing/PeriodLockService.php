<?php

namespace App\Services\PeriodClosing;

use App\Models\AccountingPeriod;
use Illuminate\Support\Facades\Log;

class PeriodLockService
{
    /**
     * Check if a date falls within a locked period
     */
    public function isDateInLockedPeriod($date, $companyId): bool
    {
        $date = is_string($date) ? \Carbon\Carbon::parse($date) : $date;
        
        $lockedPeriod = AccountingPeriod::whereHas('fiscalYear', function($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })
        ->whereIn('status', ['LOCKED', 'CLOSED'])
        ->where('start_date', '<=', $date)
        ->where('end_date', '>=', $date)
        ->first();

        return $lockedPeriod !== null;
    }

    /**
     * Get locked period for a given date
     */
    public function getLockedPeriodForDate($date, $companyId): ?AccountingPeriod
    {
        $date = is_string($date) ? \Carbon\Carbon::parse($date) : $date;
        
        return AccountingPeriod::whereHas('fiscalYear', function($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })
        ->whereIn('status', ['LOCKED', 'CLOSED'])
        ->where('start_date', '<=', $date)
        ->where('end_date', '>=', $date)
        ->first();
    }

    /**
     * Validate that a transaction date is not in a locked period
     * Throws exception if locked
     */
    public function validateTransactionDate($date, $companyId, $transactionType = 'transaction'): void
    {
        if ($this->isDateInLockedPeriod($date, $companyId)) {
            $lockedPeriod = $this->getLockedPeriodForDate($date, $companyId);
            
            Log::warning('Transaction blocked: Period is locked', [
                'date' => $date,
                'company_id' => $companyId,
                'transaction_type' => $transactionType,
                'locked_period' => $lockedPeriod->period_label ?? 'N/A',
            ]);

            throw new \Exception(
                "Cannot post {$transactionType}: The period {$lockedPeriod->period_label} is locked. " .
                "Transactions dated between {$lockedPeriod->start_date->format('M d, Y')} and " .
                "{$lockedPeriod->end_date->format('M d, Y')} are not allowed."
            );
        }
    }

    /**
     * Check if any date in a date range falls within a locked period
     */
    public function isDateRangeInLockedPeriod($startDate, $endDate, $companyId): bool
    {
        $startDate = is_string($startDate) ? \Carbon\Carbon::parse($startDate) : $startDate;
        $endDate = is_string($endDate) ? \Carbon\Carbon::parse($endDate) : $endDate;
        
        $lockedPeriod = AccountingPeriod::whereHas('fiscalYear', function($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })
        ->whereIn('status', ['LOCKED', 'CLOSED'])
        ->where(function($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        })
        ->first();

        return $lockedPeriod !== null;
    }
}

