<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ExchangeRateHistory extends Model
{
    use HasFactory;

    protected $table = 'exchange_rate_history';

    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'rate_date',
        'source',
        'notes',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'rate_date' => 'date',
    ];

    /**
     * Get exchange rate for a specific date and currency pair
     */
    public static function getRate($fromCurrency, $toCurrency, $date = null)
    {
        $date = $date ?? now()->toDateString();
        
        return self::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->where('rate_date', $date)
            ->first();
    }

    /**
     * Store exchange rate
     */
    public static function storeRate($fromCurrency, $toCurrency, $rate, $date = null, $source = 'api', $notes = null)
    {
        $date = $date ?? now()->toDateString();
        
        return self::updateOrCreate(
            [
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'rate_date' => $date,
            ],
            [
                'rate' => $rate,
                'source' => $source,
                'notes' => $notes,
            ]
        );
    }

    /**
     * Get rate history for a currency pair
     */
    public static function getHistory($fromCurrency, $toCurrency, $startDate = null, $endDate = null)
    {
        $query = self::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->orderBy('rate_date');

        if ($startDate) {
            $query->where('rate_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('rate_date', '<=', $endDate);
        }

        return $query->get();
    }

    /**
     * Get latest rate for a currency pair
     */
    public static function getLatestRate($fromCurrency, $toCurrency)
    {
        return self::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->orderBy('rate_date', 'desc')
            ->first();
    }

    /**
     * Get average rate for a period
     */
    public static function getAverageRate($fromCurrency, $toCurrency, $startDate, $endDate)
    {
        return self::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->whereBetween('rate_date', [$startDate, $endDate])
            ->avg('rate');
    }

    /**
     * Get rate statistics for a period
     */
    public static function getRateStatistics($fromCurrency, $toCurrency, $startDate, $endDate)
    {
        return self::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->whereBetween('rate_date', [$startDate, $endDate])
            ->selectRaw('
                AVG(rate) as average_rate,
                MIN(rate) as min_rate,
                MAX(rate) as max_rate,
                STDDEV(rate) as rate_volatility,
                COUNT(*) as total_records
            ')
            ->first();
    }

    /**
     * Scope for currency pair
     */
    public function scopeCurrencyPair($query, $fromCurrency, $toCurrency)
    {
        return $query->where('from_currency', $fromCurrency)
                    ->where('to_currency', $toCurrency);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('rate_date', [$startDate, $endDate]);
    }

    /**
     * Scope for source
     */
    public function scopeSource($query, $source)
    {
        return $query->where('source', $source);
    }
}
