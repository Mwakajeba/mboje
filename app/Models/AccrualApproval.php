<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccrualApproval extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'accrual_approvals';

    protected $fillable = [
        'accrual_schedule_id',
        'approval_level',
        'approver_id',
        'approval_round',
        'status', // pending, approved, rejected
        'comments',
        'rejection_reason',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'approval_round' => 'integer',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(AccrualSchedule::class, 'accrual_schedule_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeForLevel($query, $level)
    {
        return $query->where('approval_level', $level);
    }
}
