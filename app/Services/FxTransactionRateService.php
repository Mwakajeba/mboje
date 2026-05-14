<?php

namespace App\Services;

use App\Models\FxRate;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FxTransactionRateService
{
    protected $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    /**
     * Get exchange rate for invoice/voucher creation
     * 
     * Priority:
     * 1. Rate from fx_rates table for the specific date
     * 2. User-provided rate (if entered, will be saved to fx_rates table)
     * 3. Previous day's rate from fx_rates table
     * 
     * @param string $fromCurrency
     * @param string $toCurrency
     * @param string $transactionDate
     * @param int $companyId
     * @param float|null $userProvidedRate If provided, will be saved to fx_rates table
     * @return array ['rate' => float, 'source' => string, 'saved' => bool]
     */
    public function getTransactionRate($fromCurrency, $toCurrency, $transactionDate, $companyId, $userProvidedRate = null)
    {
        // If currencies are the same, return 1.0
        if ($fromCurrency === $toCurrency) {
            return [
                'rate' => 1.000000,
                'source' => 'same_currency',
                'saved' => false,
            ];
        }

        // Normalize transaction date to ensure proper date comparison
        $normalizedDate = Carbon::parse($transactionDate)->toDateString();
        
        // Priority 1: Check if rate exists in fx_rates table for the transaction date
        $rateOnDate = FxRate::getSpotRate($fromCurrency, $toCurrency, $normalizedDate, $companyId);
        
        if ($rateOnDate !== null) {
            Log::info("FX Rate found in fx_rates table", [
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'date' => $normalizedDate,
                'rate' => $rateOnDate,
                'company_id' => $companyId,
            ]);
            
            return [
                'rate' => (float) $rateOnDate,
                'source' => 'fx_rates_table',
                'saved' => false,
            ];
        }
        
        Log::info("FX Rate NOT found in fx_rates table", [
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
            'date' => $normalizedDate,
            'company_id' => $companyId,
        ]);

        // Priority 2: If user provided a rate, save it to fx_rates table and use it
        if ($userProvidedRate !== null && $userProvidedRate > 0) {
            try {
                FxRate::storeRate(
                    $fromCurrency,
                    $toCurrency,
                    $transactionDate,
                    $userProvidedRate,
                    $companyId,
                    null, // month_end_rate
                    null, // average_rate
                    'manual', // source
                    auth()->id()
                );

                Log::info("FX Rate saved to fx_rates table", [
                    'from_currency' => $fromCurrency,
                    'to_currency' => $toCurrency,
                    'date' => $transactionDate,
                    'rate' => $userProvidedRate,
                    'company_id' => $companyId,
                ]);

                return [
                    'rate' => (float) $userProvidedRate,
                    'source' => 'user_provided_saved',
                    'saved' => true,
                ];
            } catch (\Exception $e) {
                Log::error("Failed to save FX rate to fx_rates table: " . $e->getMessage());
                // Continue to fallback
            }
        }

        // Priority 3: Get previous day's rate from fx_rates table (or most recent rate before transaction date)
        $previousDayRate = FxRate::getLatestRateBefore($fromCurrency, $toCurrency, $transactionDate, $companyId);
        
        if ($previousDayRate !== null) {
            Log::info("Using previous day's FX rate", [
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'transaction_date' => $transactionDate,
                'rate' => $previousDayRate,
            ]);

            return [
                'rate' => (float) $previousDayRate,
                'source' => 'previous_day_rate',
                'saved' => false,
            ];
        }

        // Final fallback: Use ExchangeRateService (API or fallback)
        $fallbackRate = $this->exchangeRateService->getSpotRate(
            $fromCurrency,
            $toCurrency,
            $transactionDate,
            $companyId
        ) ?? 1.000000;

        Log::warning("Using fallback FX rate (API/fallback)", [
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
            'transaction_date' => $transactionDate,
            'rate' => $fallbackRate,
        ]);

        return [
            'rate' => (float) $fallbackRate,
            'source' => 'api_fallback',
            'saved' => false,
        ];
    }

    /**
     * Get exchange rate for year-end unrealized FX calculation
     * Uses the rate from fx_rates table at the year-end date
     * 
     * @param string $fromCurrency
     * @param string $toCurrency
     * @param string $yearEndDate
     * @param int $companyId
     * @return float|null
     */
    public function getYearEndRate($fromCurrency, $toCurrency, $yearEndDate, $companyId)
    {
        // Get rate from fx_rates table for year-end date
        $rate = FxRate::getSpotRate($fromCurrency, $toCurrency, $yearEndDate, $companyId);
        
        if ($rate !== null) {
            return (float) $rate;
        }

        // If not found, try to get month-end rate for December
        $year = Carbon::parse($yearEndDate)->year;
        $monthEndRate = FxRate::getMonthEndRate($fromCurrency, $toCurrency, $year, 12, $companyId);
        
        if ($monthEndRate !== null) {
            return (float) $monthEndRate;
        }

        // Final fallback
        return $this->exchangeRateService->getSpotRate(
            $fromCurrency,
            $toCurrency,
            $yearEndDate,
            $companyId
        ) ?? null;
    }

    /**
     * Get closing exchange rate for month-end revaluation
     * Uses month_end_rate if available, otherwise spot_rate for last day of month
     * 
     * @param string $fromCurrency
     * @param string $toCurrency
     * @param int $year
     * @param int $month
     * @param int $companyId
     * @return float|null
     */
    public function getMonthEndClosingRate($fromCurrency, $toCurrency, $year, $month, $companyId)
    {
        // First try to get month_end_rate
        $monthEndRate = FxRate::getMonthEndRate($fromCurrency, $toCurrency, $year, $month, $companyId);
        
        if ($monthEndRate !== null) {
            return (float) $monthEndRate;
        }

        // If month_end_rate not available, use spot_rate for last day of month
        $lastDay = Carbon::create($year, $month)->endOfMonth()->toDateString();
        $spotRate = FxRate::getSpotRate($fromCurrency, $toCurrency, $lastDay, $companyId);
        
        if ($spotRate !== null) {
            return (float) $spotRate;
        }

        // Final fallback
        return $this->exchangeRateService->getMonthEndRate(
            $fromCurrency,
            $toCurrency,
            $year,
            $month,
            $companyId
        ) ?? null;
    }
}

