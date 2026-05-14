<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountTransferApproval extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'account_transfer_id',
        'approval_level',
        'approver_id',
        'approver_type',
        'approver_name',
        'status',
        'comments',
        'approved_at',
        'escalated_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'escalated_at' => 'datetime',
    ];

    /**
     * Get the account transfer that owns the approval.
     */
    public function accountTransfer()
    {
        return $this->belongsTo(AccountTransfer::class);
    }

    /**
     * Get the approver user.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Scope for pending approvals.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved approvals.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected approvals.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Check if approval is pending.
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if approval is approved.
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * Approve this approval record.
     */
    public function approve(?string $comments = null)
    {
        $this->update([
            'status' => 'approved',
            'approver_id' => auth()->id(),
            'comments' => $comments,
            'approved_at' => now(),
        ]);
    }
}
