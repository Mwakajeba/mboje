<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExchangeRateController extends Controller
{
    protected $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    /**
     * Get current exchange rate
     */
    public function getRate(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|string|max:3',
            'to' => 'nullable|string|max:3',
        ]);

        $fromCurrency = strtoupper($request->from);
        $toCurrency = strtoupper($request->to ?? 'TZS');

        $rate = $this->exchangeRateService->getExchangeRate($fromCurrency, $toCurrency);

        return response()->json([
            'success' => true,
            'data' => [
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'rate' => $rate,
                'timestamp' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * Convert amount between currencies
     */
    public function convertAmount(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'from' => 'required|string|max:3',
            'to' => 'nullable|string|max:3',
        ]);

        $amount = $request->amount;
        $fromCurrency = strtoupper($request->from);
        $toCurrency = strtoupper($request->to ?? 'TZS');

        $convertedAmount = $this->exchangeRateService->convertAmount($amount, $fromCurrency, $toCurrency);
        $rate = $this->exchangeRateService->getExchangeRate($fromCurrency, $toCurrency);

        return response()->json([
            'success' => true,
            'data' => [
                'original_amount' => $amount,
                'original_currency' => $fromCurrency,
                'converted_amount' => $convertedAmount,
                'target_currency' => $toCurrency,
                'exchange_rate' => $rate,
                'timestamp' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * Get exchange rate history
     */
    public function getHistory(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|string|max:3',
            'to' => 'nullable|string|max:3',
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        $fromCurrency = strtoupper($request->from);
        $toCurrency = strtoupper($request->to ?? 'TZS');
        $days = $request->days ?? 30;

        $history = $this->exchangeRateService->getExchangeRateHistory($fromCurrency, $toCurrency, $days);

        return response()->json([
            'success' => true,
            'data' => [
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'days' => $days,
                'history' => $history,
                'timestamp' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * Get supported currencies
     */
    public function getSupportedCurrencies(): JsonResponse
    {
        $currencies = $this->exchangeRateService->getSupportedCurrencies();

        return response()->json([
            'success' => true,
            'data' => [
                'currencies' => $currencies,
                'count' => count($currencies),
                'timestamp' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * Clear exchange rate cache
     */
    public function clearCache(): JsonResponse
    {
        $this->exchangeRateService->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Exchange rate cache cleared successfully',
            'timestamp' => now()->toISOString(),
        ]);
    }
}
