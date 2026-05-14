<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalHistory extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'approval_level_id',
        'action',
        'approver_id',
        'comments',
        'reassigned_to_user_id',
    ];

    protected $casts = [
        'approvable_id' => 'integer',
        'approval_level_id' => 'integer',
        'approver_id' => 'integer',
        'reassigned_to_user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the parent approvable model (Budget or BankReconciliation).
     */
    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the approval level for this history entry.
     */
    public function approvalLevel(): BelongsTo
    {
        return $this->belongsTo(ApprovalLevel::class);
    }

    /**
     * Get the user who took this action.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Get the user this was reassigned to (if reassigned).
     */
    public function reassignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reassigned_to_user_id');
    }

    /**
     * Scope to filter by action type.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by approval level.
     */
    public function scopeByLevel($query, int $levelId)
    {
        return $query->where('approval_level_id', $levelId);
    }

    /**
     * Scope to filter by approver.
     */
    public function scopeByApprover($query, int $userId)
    {
        return $query->where('approver_id', $userId);
    }

    /**
     * Scope to get latest first.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope to get oldest first.
     */
    public function scopeOldest($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    /**
     * Scope to filter by company ID, handling different approvable types correctly.
     * BankReconciliation doesn't have a direct company_id column, so it's handled specially.
     */
    public function scopeForCompany($query, int $companyId)
    {
        $bankReconciliationClass = \App\Models\BankReconciliation::class;
        
        return $query->where(function($q) use ($companyId, $bankReconciliationClass) {
            // Handle models with direct company_id column (most models)
            $q->where(function($subQ) use ($companyId, $bankReconciliationClass) {
                $subQ->where('approvable_type', '!=', $bankReconciliationClass)
                     ->whereHas('approvable', function($approvableQ) use ($companyId) {
                         $approvableQ->where('company_id', $companyId);
                     });
            })
            // Handle BankReconciliation specially - check company_id through bankAccount relationship
            ->orWhere(function($subQ) use ($companyId, $bankReconciliationClass) {
                $subQ->where('approvable_type', $bankReconciliationClass)
                     ->whereHas('approvable', function($approvableQ) use ($companyId) {
                         $approvableQ->whereHas('bankAccount', function($bankAccountQ) use ($companyId) {
                             $bankAccountQ->where('company_id', $companyId);
                         });
                     });
            });
        });
    }

    /**
     * Check if this is a submission action.
     */
    public function isSubmission(): bool
    {
        return $this->action === 'submitted';
    }

    /**
     * Check if this is an approval action.
     */
    public function isApproval(): bool
    {
        return $this->action === 'approved';
    }

    /**
     * Check if this is a rejection action.
     */
    public function isRejection(): bool
    {
        return $this->action === 'rejected';
    }

    /**
     * Check if this is a reassignment action.
     */
    public function isReassignment(): bool
    {
        return $this->action === 'reassigned';
    }

    /**
     * Get formatted action badge HTML.
     */
    public function getActionBadgeAttribute(): string
    {
        $badges = [
            'submitted' => '<span class="badge bg-info">Submitted</span>',
            'approved' => '<span class="badge bg-success">Approved</span>',
            'rejected' => '<span class="badge bg-danger">Rejected</span>',
            'reassigned' => '<span class="badge bg-warning">Reassigned</span>',
        ];

        return $badges[$this->action] ?? '<span class="badge bg-secondary">Unknown</span>';
    }
}
