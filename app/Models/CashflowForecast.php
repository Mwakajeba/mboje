<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class CashflowForecast extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id',
        'branch_id',
        'forecast_name',
        'scenario',
        'timeline',
        'start_date',
        'end_date',
        'starting_cash_balance',
        'notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'starting_cash_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CashflowForecastItem::class);
    }

    // Accessors
    public function getEncodedIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper methods
    public function getTotalInflows()
    {
        return $this->items()->where('type', 'inflow')->sum('amount');
    }

    public function getTotalOutflows()
    {
        return $this->items()->where('type', 'outflow')->sum('amount');
    }

    public function getNetCashflow()
    {
        return $this->getTotalInflows() - $this->getTotalOutflows();
    }
}

