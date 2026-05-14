<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class FxRate extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'fx_rates';

    protected $fillable = [
        'rate_date',
        'from_currency',
        'to_currency',
        'spot_rate',
        'month_end_rate',
        'average_rate',
        'source',
        'is_locked',
        'company_id',
        'created_by',
    ];

    protected $casts = [
        'rate_date' => 'date',
        'spot_rate' => 'decimal:6',
        'month_end_rate' => 'decimal:6',
        'average_rate' => 'decimal:6',
        'is_locked' => 'boolean',
    ];

    /**
     * Get the company that owns the FX rate.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created the FX rate.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get spot rate for a specific date and currency pair.
     */
    public static function getSpotRate($fromCurrency, $toCurrency, $date, $companyId)
    {
        // Normalize date to Y-m-d format to ensure proper comparison
        $normalizedDate = Carbon::parse($date)->toDateString();
        
        $rate = self::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->whereDate('rate_date', $normalizedDate)
            ->where('company_id', $companyId)
            ->first();

        return $rate ? $rate->spot_rate : null;
    }

    /**
     * Get the most recent rate before or on a specific date (for fallback to previous day)
     */
    public static function getLatestRateBefore($fromCurrency, $toCurrency, $date, $companyId)
    {
        // Normalize date to Y-m-d format to ensure proper comparison
        $normalizedDate = Carbon::parse($date)->toDateString();
        
        $rate = self::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->whereDate('rate_date', '<=', $normalizedDate)
            ->where('company_id', $companyId)
            ->orderBy('rate_date', 'desc')
            ->first();

        return $rate ? $rate->spot_rate : null;
    }

    /**
     * Get month-end rate for a specific month and currency pair.
     */
    public static function getMonthEndRate($fromCurrency, $toCurrency, $year, $month, $companyId)
    {
        // Get the last day of the month
        $lastDay = Carbon::create($year, $month)->endOfMonth()->format('Y-m-d');

        $rate = self::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->where('rate_date', $lastDay)
            ->where('company_id', $companyId)
            ->whereNotNull('month_end_rate')
            ->first();

        return $rate ? $rate->month_end_rate : null;
    }

    /**
     * Get average rate for a period.
     */
    public static function getAverageRate($fromCurrency, $toCurrency, $startDate, $endDate, $companyId)
    {
        $rate = self::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->whereBetween('rate_date', [$startDate, $endDate])
            ->where('company_id', $companyId)
            ->whereNotNull('average_rate')
            ->avg('average_rate');

        return $rate ?: null;
    }

    /**
     * Store or update FX rate.
     */
    public static function storeRate($fromCurrency, $toCurrency, $rateDate, $spotRate, $companyId, $monthEndRate = null, $averageRate = null, $source = 'manual', $userId = null)
    {
        // Normalize date to Y-m-d format to ensure proper comparison
        $normalizedDate = Carbon::parse($rateDate)->toDateString();
        
        // Check if rate is locked
        $existing = self::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->whereDate('rate_date', $normalizedDate)
            ->where('company_id', $companyId)
            ->first();

        if ($existing && $existing->is_locked) {
            throw new \Exception('Cannot modify locked FX rate.');
        }

        return self::updateOrCreate(
            [
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'rate_date' => $normalizedDate,
                'company_id' => $companyId,
            ],
            [
                'spot_rate' => $spotRate,
                'month_end_rate' => $monthEndRate,
                'average_rate' => $averageRate,
                'source' => $source,
                'created_by' => $userId ?? auth()->id(),
            ]
        );
    }

    /**
     * Lock the FX rate (prevent future modifications).
     */
    public function lock()
    {
        $this->is_locked = true;
        $this->save();
        return $this;
    }

    /**
     * Unlock the FX rate (allow modifications).
     */
    public function unlock()
    {
        $this->is_locked = false;
        $this->save();
        return $this;
    }

    /**
     * Scope to filter by currency pair.
     */
    public function scopeCurrencyPair($query, $fromCurrency, $toCurrency)
    {
        return $query->where('from_currency', $fromCurrency)
                    ->where('to_currency', $toCurrency);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('rate_date', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by company.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to get only locked rates.
     */
    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    /**
     * Scope to get only unlocked rates.
     */
    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }
}

