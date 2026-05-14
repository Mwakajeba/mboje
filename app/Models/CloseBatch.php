<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CloseBatch extends Model
{
    use HasFactory, LogsActivity;

    protected $primaryKey = 'close_id';
    protected $table = 'close_batches';

    protected $fillable = [
        'company_id',
        'period_id',
        'batch_label',
        'prepared_by',
        'prepared_at',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'prepared_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(CloseAdjustment::class, 'close_id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(PeriodSnapshot::class, 'close_id');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'DRAFT');
    }

    public function scopeReview($query)
    {
        return $query->where('status', 'REVIEW');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    public function scopeLocked($query)
    {
        return $query->where('status', 'LOCKED');
    }

    // Helper methods
    public function isDraft(): bool
    {
        return $this->status === 'DRAFT';
    }

    public function isInReview(): bool
    {
        return $this->status === 'REVIEW';
    }

    public function isApproved(): bool
    {
        return $this->status === 'APPROVED';
    }

    public function isLocked(): bool
    {
        return $this->status === 'LOCKED';
    }

    public function isReopened(): bool
    {
        return $this->status === 'REOPENED';
    }
}
