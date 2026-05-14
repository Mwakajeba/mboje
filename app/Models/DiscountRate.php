<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscountRate extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'discount_rates';

    protected $fillable = [
        'ifrs_standard',
        'usage_context',
        'currency_code',
        'rate_type',
        'risk_category',
        'rate_percent',
        'basis',
        'effective_from',
        'effective_to',
        'approval_status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'company_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'rate_percent' => 'decimal:4',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForContext($query, string $context)
    {
        return $query->where('usage_context', $context);
    }

    public function scopeActive($query)
    {
        return $query->where('approval_status', 'approved')
            ->where(function ($q) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', now()->toDateString());
            })
            ->where('effective_from', '<=', now()->toDateString());
    }

    public function scopeForCurrency($query, string $currencyCode)
    {
        return $query->where('currency_code', $currencyCode);
    }

    /**
     * Get the currently active discount rate for a given context and currency
     */
    public static function getActiveRate(int $companyId, string $context = 'provision', string $currencyCode = 'TZS'): ?self
    {
        return static::forCompany($companyId)
            ->forContext($context)
            ->forCurrency($currencyCode)
            ->active()
            ->orderBy('effective_from', 'desc')
            ->first();
    }
}

