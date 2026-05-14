<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'currency_code',
        'currency_name',
        'decimal_places',
        'is_active',
        'company_id',
    ];

    protected $casts = [
        'decimal_places' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the company that owns the currency.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all FX rates for this currency (as from_currency).
     */
    public function fxRatesFrom()
    {
        return FxRate::where('from_currency', $this->currency_code)
            ->where('company_id', $this->company_id);
    }

    /**
     * Get all FX rates for this currency (as to_currency).
     */
    public function fxRatesTo()
    {
        return FxRate::where('to_currency', $this->currency_code)
            ->where('company_id', $this->company_id);
    }

    /**
     * Scope to get only active currencies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by company.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Get formatted currency code with name.
     */
    public function getFormattedNameAttribute()
    {
        return "{$this->currency_code} - {$this->currency_name}";
    }
}

