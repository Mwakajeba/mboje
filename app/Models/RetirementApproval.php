<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetirementApproval extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'retirement_id',
        'approval_level',
        'approver_id',
        'status',
        'comments',
        'action_date'
    ];

    protected $casts = [
        'action_date' => 'datetime'
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    // Relationships
    public function retirement()
    {
        return $this->belongsTo(Retirement::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeForLevel($query, $level)
    {
        return $query->where('approval_level', $level);
    }

    // Helper methods
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function approve($comments = null)
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'comments' => $comments,
            'action_date' => now()
        ]);
    }

    public function reject($comments = null)
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'comments' => $comments,
            'action_date' => now()
        ]);
    }
}
