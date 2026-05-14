<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\ExchangeRateHistory;
use App\Models\FxRate;
use Carbon\Carbon;

class ExchangeRateService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.exchangerate-api.com/v4/latest/';
    protected $fallbackUrl = 'https://api.frankfurter.app/latest';

    public function __construct()
    {
        $this->apiKey = config('services.exchange_rate.api_key');
    }

    /**
     * Get exchange rate for a currency pair
     */
    public function getExchangeRate($fromCurrency, $toCurrency = 'TZS')
    {
        $cacheKey = "exchange_rate_{$fromCurrency}_{$toCurrency}";
        
        // Try to get from cache first
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Try to get from database history
        $historyRate = ExchangeRateHistory::getLatestRate($fromCurrency, $toCurrency);
        if ($historyRate) {
            Cache::put($cacheKey, $historyRate->rate, now()->addHour());
            return $historyRate->rate;
        }

        try {
            $rate = $this->fetchFromAPI($fromCurrency, $toCurrency);
            
            if ($rate) {
                // Store in history
                ExchangeRateHistory::storeRate($fromCurrency, $toCurrency, $rate, now()->toDateString(), 'api');
                
                // Cache for 1 hour
                Cache::put($cacheKey, $rate, now()->addHour());
                return $rate;
            }
        } catch (\Exception $e) {
            Log::error('Exchange rate API error: ' . $e->getMessage());
        }

        // Return fallback rate and store it
        $fallbackRate = $this->getFallbackRate($fromCurrency, $toCurrency);
        ExchangeRateHistory::storeRate($fromCurrency, $toCurrency, $fallbackRate, now()->toDateString(), 'fallback');
        
        return $fallbackRate;
    }

    /**
     * Fetch exchange rate from API
     */
    protected function fetchFromAPI($fromCurrency, $toCurrency)
    {
        $url = $this->baseUrl . $fromCurrency;
        
        $response = Http::timeout(10)->get($url);
        
        if ($response->successful()) {
            $data = $response->json();
            
            if (isset($data['rates'][$toCurrency])) {
                return $data['rates'][$toCurrency];
            }
        }

        // Try fallback API
        return $this->fetchFromFallbackAPI($fromCurrency, $toCurrency);
    }

    /**
     * Fetch from fallback API
     */
    protected function fetchFromFallbackAPI($fromCurrency, $toCurrency)
    {
        $url = $this->fallbackUrl . "?from={$fromCurrency}&to={$toCurrency}";
        
        $response = Http::timeout(10)->get($url);
        
        if ($response->successful()) {
            $data = $response->json();
            
            if (isset($data['rates'][$toCurrency])) {
                return $data['rates'][$toCurrency];
            }
        }

        return null;
    }

    /**
     * Get fallback exchange rates (hardcoded for reliability)
     */
    protected function getFallbackRate($fromCurrency, $toCurrency)
    {
        $rates = [
            'USD' => 2500.00,  // 1 USD = 2500 TZS
            'EUR' => 2700.00,  // 1 EUR = 2700 TZS
            'GBP' => 3150.00,  // 1 GBP = 3150 TZS
            'KES' => 18.50,    // 1 KES = 18.50 TZS
            'UGX' => 0.65,     // 1 UGX = 0.65 TZS
            'RWF' => 2.10,     // 1 RWF = 2.10 TZS
            'BIF' => 0.85,     // 1 BIF = 0.85 TZS
            'CDF' => 0.95,     // 1 CDF = 0.95 TZS
            'ZAR' => 135.00,   // 1 ZAR = 135 TZS
            'CNY' => 345.00,   // 1 CNY = 345 TZS
            'JPY' => 16.50,    // 1 JPY = 16.50 TZS
        ];

        if ($fromCurrency === 'TZS') {
            return 1.0;
        }

        if ($toCurrency === 'TZS' && isset($rates[$fromCurrency])) {
            return $rates[$fromCurrency];
        }

        // For other currency pairs, calculate cross rate
        if ($fromCurrency !== 'TZS' && $toCurrency !== 'TZS') {
            $fromRate = $rates[$fromCurrency] ?? 1;
            $toRate = $rates[$toCurrency] ?? 1;
            return $toRate / $fromRate;
        }

        return 1.0;
    }

    /**
     * Get all supported currencies from API
     */
    public function getSupportedCurrencies($useApi = true)
    {
        $cacheKey = 'supported_currencies_list';
        
        // Try to get from cache first
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Try to fetch from API if enabled
        if ($useApi) {
            try {
                $currencies = $this->fetchCurrenciesFromAPI();
                if ($currencies && count($currencies) > 0) {
                    // Cache for 24 hours
                    Cache::put($cacheKey, $currencies, now()->addHours(24));
                    return $currencies;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch currencies from API: ' . $e->getMessage());
            }
        }

        // Fallback to hardcoded list
        $fallbackCurrencies = [
            'TZS' => 'Tanzanian Shilling',
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'KES' => 'Kenyan Shilling',
            'UGX' => 'Ugandan Shilling',
            'RWF' => 'Rwandan Franc',
            'BIF' => 'Burundian Franc',
            'CDF' => 'Congolese Franc',
            'ZAR' => 'South African Rand',
            'CNY' => 'Chinese Yuan',
            'JPY' => 'Japanese Yen',
        ];

        // Cache fallback for 1 hour
        Cache::put($cacheKey, $fallbackCurrencies, now()->addHour());
        return $fallbackCurrencies;
    }

    /**
     * Fetch currencies from exchange rate API
     */
    protected function fetchCurrenciesFromAPI()
    {
        // Fetch from exchangerate-api.com - using USD as base to get all currencies
        $url = $this->baseUrl . 'USD';
        
        $response = Http::timeout(10)->get($url);
        
        if ($response->successful()) {
            $data = $response->json();
            
            if (isset($data['rates']) && is_array($data['rates'])) {
                $currencies = [];
                
                // Currency name mapping (common currencies)
                $currencyNames = [
                    'TZS' => 'Tanzanian Shilling',
                    'USD' => 'US Dollar',
                    'EUR' => 'Euro',
                    'GBP' => 'British Pound',
                    'KES' => 'Kenyan Shilling',
                    'UGX' => 'Ugandan Shilling',
                    'RWF' => 'Rwandan Franc',
                    'BIF' => 'Burundian Franc',
                    'CDF' => 'Congolese Franc',
                    'ZAR' => 'South African Rand',
                    'CNY' => 'Chinese Yuan',
                    'JPY' => 'Japanese Yen',
                    'AUD' => 'Australian Dollar',
                    'CAD' => 'Canadian Dollar',
                    'CHF' => 'Swiss Franc',
                    'INR' => 'Indian Rupee',
                    'SGD' => 'Singapore Dollar',
                    'AED' => 'UAE Dirham',
                    'SAR' => 'Saudi Riyal',
                    'NGN' => 'Nigerian Naira',
                    'EGP' => 'Egyptian Pound',
                    'MAD' => 'Moroccan Dirham',
                    'ETB' => 'Ethiopian Birr',
                    'GHS' => 'Ghanaian Cedi',
                    'XOF' => 'West African CFA Franc',
                    'XAF' => 'Central African CFA Franc',
                ];
                
                // Add USD first (base currency)
                $currencies['USD'] = $currencyNames['USD'] ?? 'US Dollar';
                
                // Add all currencies from API response
                foreach ($data['rates'] as $code => $rate) {
                    if (!isset($currencies[$code])) {
                        $currencies[$code] = $currencyNames[$code] ?? $this->getCurrencyNameFromCode($code);
                    }
                }
                
                // Ensure TZS is included (might not be in API response)
                if (!isset($currencies['TZS'])) {
                    $currencies['TZS'] = 'Tanzanian Shilling';
                }
                
                // Sort by currency code
                ksort($currencies);
                
                return $currencies;
            }
        }

        // Try fallback API
        return $this->fetchCurrenciesFromFallbackAPI();
    }

    /**
     * Fetch currencies from fallback API
     */
    protected function fetchCurrenciesFromFallbackAPI()
    {
        $url = 'https://api.frankfurter.app/latest';
        
        $response = Http::timeout(10)->get($url);
        
        if ($response->successful()) {
            $data = $response->json();
            
            if (isset($data['rates']) && is_array($data['rates'])) {
                $currencies = [];
                
                // Add base currency (EUR for Frankfurter)
                $currencies['EUR'] = 'Euro';
                
                // Add all currencies from API response
                foreach ($data['rates'] as $code => $rate) {
                    $currencies[$code] = $this->getCurrencyNameFromCode($code);
                }
                
                // Ensure TZS and USD are included
                if (!isset($currencies['TZS'])) {
                    $currencies['TZS'] = 'Tanzanian Shilling';
                }
                if (!isset($currencies['USD'])) {
                    $currencies['USD'] = 'US Dollar';
                }
                
                ksort($currencies);
                
                return $currencies;
            }
        }

        return null;
    }

    /**
     * Get currency name from currency code (fallback)
     */
    protected function getCurrencyNameFromCode($code)
    {
        // Common currency names
        $names = [
            'USD' => 'US Dollar', 'EUR' => 'Euro', 'GBP' => 'British Pound',
            'JPY' => 'Japanese Yen', 'AUD' => 'Australian Dollar', 'CAD' => 'Canadian Dollar',
            'CHF' => 'Swiss Franc', 'CNY' => 'Chinese Yuan', 'INR' => 'Indian Rupee',
            'BRL' => 'Brazilian Real', 'ZAR' => 'South African Rand', 'MXN' => 'Mexican Peso',
            'SGD' => 'Singapore Dollar', 'HKD' => 'Hong Kong Dollar', 'NZD' => 'New Zealand Dollar',
            'KRW' => 'South Korean Won', 'TRY' => 'Turkish Lira', 'RUB' => 'Russian Ruble',
            'TZS' => 'Tanzanian Shilling', 'KES' => 'Kenyan Shilling', 'UGX' => 'Ugandan Shilling',
            'RWF' => 'Rwandan Franc', 'BIF' => 'Burundian Franc', 'CDF' => 'Congolese Franc',
        ];

        return $names[$code] ?? $code;
    }

    /**
     * Convert amount between currencies
     */
    public function convertAmount($amount, $fromCurrency, $toCurrency)
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $rate = $this->getExchangeRate($fromCurrency, $toCurrency);
        return $amount * $rate;
    }

    /**
     * Get exchange rate history (for tracking)
     */
    public function getExchangeRateHistory($fromCurrency, $toCurrency, $days = 30)
    {
        $cacheKey = "exchange_rate_history_{$fromCurrency}_{$toCurrency}_{$days}";
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $startDate = now()->subDays($days)->toDateString();
        $endDate = now()->toDateString();

        // Get from database history
        $historyRecords = ExchangeRateHistory::getHistory($fromCurrency, $toCurrency, $startDate, $endDate);
        
        $history = [];
        foreach ($historyRecords as $record) {
            $history[$record->rate_date->format('Y-m-d')] = $record->rate;
        }

        // Fill missing dates with current rate
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            if (!isset($history[$date])) {
                $history[$date] = $this->getExchangeRate($fromCurrency, $toCurrency);
            }
        }

        Cache::put($cacheKey, $history, now()->addHours(6));
        return $history;
    }

    /**
     * Clear exchange rate cache
     */
    public function clearCache()
    {
        $currencies = array_keys($this->getSupportedCurrencies());
        
        foreach ($currencies as $from) {
            foreach ($currencies as $to) {
                if ($from !== $to) {
                    Cache::forget("exchange_rate_{$from}_{$to}");
                    Cache::forget("exchange_rate_history_{$from}_{$to}_30");
                }
            }
        }
    }

    /**
     * Get spot rate for a specific date and currency pair (from fx_rates table)
     */
    public function getSpotRate($fromCurrency, $toCurrency, $date = null, $companyId = null)
    {
        $date = $date ?? now()->toDateString();
        $companyId = $companyId ?? auth()->user()?->company_id;

        if (!$companyId) {
            // Fallback to old method if no company ID
            return $this->getExchangeRate($fromCurrency, $toCurrency);
        }

        $rate = FxRate::getSpotRate($fromCurrency, $toCurrency, $date, $companyId);
        
        if ($rate) {
            return $rate;
        }

        // Fallback to ExchangeRateHistory or API
        return $this->getExchangeRate($fromCurrency, $toCurrency);
    }

    /**
     * Get month-end closing rate for a specific month and currency pair
     */
    public function getMonthEndRate($fromCurrency, $toCurrency, $year, $month, $companyId = null)
    {
        $companyId = $companyId ?? auth()->user()?->company_id;

        if (!$companyId) {
            throw new \Exception('Company ID is required to get month-end rate.');
        }

        $rate = FxRate::getMonthEndRate($fromCurrency, $toCurrency, $year, $month, $companyId);
        
        if ($rate) {
            return $rate;
        }

        // If month-end rate not found, try to get spot rate for last day of month
        $lastDay = Carbon::create($year, $month)->endOfMonth()->format('Y-m-d');
        return $this->getSpotRate($fromCurrency, $toCurrency, $lastDay, $companyId);
    }

    /**
     * Get average rate for a period
     */
    public function getAverageRate($fromCurrency, $toCurrency, $startDate, $endDate, $companyId = null)
    {
        $companyId = $companyId ?? auth()->user()?->company_id;

        if (!$companyId) {
            throw new \Exception('Company ID is required to get average rate.');
        }

        $rate = FxRate::getAverageRate($fromCurrency, $toCurrency, $startDate, $endDate, $companyId);
        
        if ($rate) {
            return $rate;
        }

        // Fallback: calculate average from spot rates
        $rates = FxRate::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->whereBetween('rate_date', [$startDate, $endDate])
            ->where('company_id', $companyId)
            ->pluck('spot_rate')
            ->filter();

        if ($rates->count() > 0) {
            return $rates->avg();
        }

        // Final fallback
        return ExchangeRateHistory::getAverageRate($fromCurrency, $toCurrency, $startDate, $endDate);
    }

    /**
     * Store FX rate in fx_rates table
     */
    public function storeFxRate($fromCurrency, $toCurrency, $rateDate, $spotRate, $monthEndRate = null, $averageRate = null, $source = 'manual', $companyId = null, $userId = null)
    {
        $companyId = $companyId ?? auth()->user()?->company_id;
        $userId = $userId ?? auth()->id();

        if (!$companyId) {
            throw new \Exception('Company ID is required to store FX rate.');
        }

        return FxRate::storeRate(
            $fromCurrency,
            $toCurrency,
            $rateDate,
            $spotRate,
            $companyId,
            $monthEndRate,
            $averageRate,
            $source,
            $userId
        );
    }

    /**
     * Lock FX rate (prevent retrospective changes)
     */
    public function lockFxRate($rateId)
    {
        $rate = FxRate::findOrFail($rateId);
        return $rate->lock();
    }

    /**
     * Get rate for transaction date (spot rate) - alias for getSpotRate
     */
    public function getRateForTransaction($fromCurrency, $toCurrency, $transactionDate, $companyId = null)
    {
        return $this->getSpotRate($fromCurrency, $toCurrency, $transactionDate, $companyId);
    }
}
