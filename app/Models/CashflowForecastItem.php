<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashflowForecastItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'cashflow_forecast_id',
        'forecast_date',
        'type',
        'source_type',
        'source_reference',
        'source_id',
        'amount',
        'probability',
        'description',
        'notes',
        'is_manual_adjustment',
        'adjusted_by',
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'amount' => 'decimal:2',
        'probability' => 'decimal:2',
        'is_manual_adjustment' => 'boolean',
    ];

    // Relationships
    public function cashflowForecast(): BelongsTo
    {
        return $this->belongsTo(CashflowForecast::class);
    }

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    // Scopes
    public function scopeInflows($query)
    {
        return $query->where('type', 'inflow');
    }

    public function scopeOutflows($query)
    {
        return $query->where('type', 'outflow');
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('forecast_date', $date);
    }
}

