<?php

namespace App\Traits;

use App\Models\FxRate;
use App\Models\Currency;
use App\Models\SystemSetting;
use App\Services\ExchangeRateService;

trait GetsCurrenciesFromFxRates
{
    /**
     * Get currencies from FX RATES MANAGEMENT (fx_rates table)
     * 
     * @param int|null $companyId
     * @return \Illuminate\Support\Collection
     */
    protected function getCurrenciesFromFxRates($companyId = null)
    {
        $companyId = $companyId ?? auth()->user()->company_id;
        $functionalCurrency = SystemSetting::getValue('functional_currency', auth()->user()->company->functional_currency ?? 'TZS');
        
        // Get distinct currencies from fx_rates table
        $fxCurrencies = FxRate::where('company_id', $companyId)
            ->where('to_currency', $functionalCurrency) // Only currencies that convert to functional currency
            ->distinct()
            ->pluck('from_currency')
            ->unique()
            ->sort()
            ->values();
        
        // Get currency names from Currency model or ExchangeRateService
        $currencies = collect();
        foreach ($fxCurrencies as $code) {
            $currency = Currency::where('company_id', $companyId)
                ->where('currency_code', $code)
                ->first();
            
            if ($currency) {
                $currencies->push((object)[
                    'currency_code' => $code,
                    'currency_name' => $currency->currency_name ?? $code
                ]);
            } else {
                // Fallback to ExchangeRateService for currency name
                $exchangeRateService = app(ExchangeRateService::class);
                $supportedCurrencies = $exchangeRateService->getSupportedCurrencies();
                $currencies->push((object)[
                    'currency_code' => $code,
                    'currency_name' => $supportedCurrencies[$code] ?? $code
                ]);
            }
        }
        
        // Always include functional currency
        if (!$currencies->contains('currency_code', $functionalCurrency)) {
            $currency = Currency::where('company_id', $companyId)
                ->where('currency_code', $functionalCurrency)
                ->first();
            
            $currencies->prepend((object)[
                'currency_code' => $functionalCurrency,
                'currency_name' => $currency->currency_name ?? $functionalCurrency
            ]);
        }
        
        return $currencies;
    }
}

