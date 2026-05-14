<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetirementItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'retirement_id',
        'chart_account_id',
        'company_id',
        'branch_id',
        'requested_amount',
        'actual_amount',
        'description',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
    ];

    // Relationships
    public function retirement(): BelongsTo
    {
        return $this->belongsTo(Retirement::class);
    }

    public function chartAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Accessors
    public function getVarianceAttribute(): float
    {
        return $this->actual_amount - $this->requested_amount;
    }

    public function getVariancePercentageAttribute(): float
    {
        if ($this->requested_amount == 0) {
            return 0;
        }
        return ($this->variance / $this->requested_amount) * 100;
    }

    public function getFormattedRequestedAmountAttribute(): string
    {
        return number_format($this->requested_amount, 2);
    }

    public function getFormattedActualAmountAttribute(): string
    {
        return number_format($this->actual_amount, 2);
    }

    public function getFormattedVarianceAttribute(): string
    {
        return number_format($this->variance, 2);
    }
}
