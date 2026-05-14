<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingPeriod extends Model
{
    use HasFactory, LogsActivity;

    protected $primaryKey = 'period_id';
    protected $table = 'accounting_periods';

    protected $fillable = [
        'fy_id',
        'period_label',
        'start_date',
        'end_date',
        'period_type',
        'status',
        'locked_by',
        'locked_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'locked_at' => 'datetime',
    ];

    // Relationships
    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class, 'fy_id');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function closeBatches(): HasMany
    {
        return $this->hasMany(CloseBatch::class, 'period_id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(PeriodSnapshot::class, 'period_id');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'OPEN');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'CLOSED');
    }

    public function scopeLocked($query)
    {
        return $query->where('status', 'LOCKED');
    }

    public function scopeForFiscalYear($query, $fyId)
    {
        return $query->where('fy_id', $fyId);
    }

    // Helper methods
    public function isLocked(): bool
    {
        return $this->status === 'LOCKED';
    }

    public function isClosed(): bool
    {
        return $this->status === 'CLOSED';
    }

    public function isOpen(): bool
    {
        return $this->status === 'OPEN';
    }
}
