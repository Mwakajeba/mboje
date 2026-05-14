<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Retirement extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'retirement_number',
        'imprest_request_id',
        'company_id',
        'branch_id',
        'total_amount_used',
        'retirement_notes',
        'supporting_document',
        'status',
        'submitted_by',
        'submitted_at',
        'checked_by',
        'checked_at',
        'check_comments',
        'approved_by',
        'approved_at',
        'approval_comments',
        'rejected_by',
        'journal_id',
        'closed_by',
        'closed_at',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'total_amount_used' => 'decimal:2',
        'submitted_at' => 'datetime',
        'checked_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    // Relationships
    public function imprestRequest(): BelongsTo
    {
        return $this->belongsTo(ImprestRequest::class, 'imprest_request_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Journal::class);
    }

    public function retirementItems(): HasMany
    {
        return $this->hasMany(RetirementItem::class);
    }

    // Convenient relationships for approval views
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by'); // The employee who submitted the retirement
    }

    // Convenient accessor for department through imprest request
    public function getDepartmentAttribute()
    {
        return $this->imprestRequest?->department;
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Status check methods (similar to imprest request)
    public function canBeChecked(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'checked';
    }

    public function canBeRejected(): bool
    {
        return in_array($this->status, ['pending', 'checked']);
    }

    // Permission methods (using modern multi-level approval system)
    public function canUserCheck(User $user): bool
    {
        if (!$this->canBeChecked()) {
            return false;
        }

        // Get retirement approval settings
        $settings = $this->getApprovalSettings();
        if (!$settings || !$settings->approval_required) {
            return true; // No approval required, anyone can check
        }

        // Check if user is in any of the approval levels for this retirement amount
        $requiredLevels = $this->getRequiredApprovalLevels();
        foreach ($requiredLevels as $levelData) {
            if (in_array($user->id, $levelData['approvers'])) {
                return true;
            }
        }

        return false;
    }

    public function canUserApprove(User $user): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        // Get retirement approval settings
        $settings = $this->getApprovalSettings();
        if (!$settings || !$settings->approval_required) {
            return true; // No approval required, anyone can approve
        }

        // Check if user is in any of the approval levels for this retirement amount
        $requiredLevels = $this->getRequiredApprovalLevels();
        foreach ($requiredLevels as $levelData) {
            if (in_array($user->id, $levelData['approvers'])) {
                return true;
            }
        }

        return false;
    }

    // Multi-level approval methods
    public function approvals(): HasMany
    {
        return $this->hasMany(RetirementApproval::class);
    }

    public function getApprovalSettings()
    {
        return RetirementApprovalSettings::where('company_id', $this->company_id)
            ->where('branch_id', $this->branch_id)
            ->first();
    }

    public function requiresApproval(): bool
    {
        $settings = $this->getApprovalSettings();
        return $settings && $settings->approval_required;
    }

    public function getRequiredApprovalLevels(): array
    {
        $settings = $this->getApprovalSettings();
        if (!$settings || !$settings->approval_required) {
            return [];
        }

        return $settings->getRequiredApprovalsForAmount($this->total_amount_used);
    }

    public function getPendingApprovals()
    {
        return $this->approvals()->pending()->get();
    }

    public function getCompletedApprovals()
    {
        return $this->approvals()->where('status', '!=', RetirementApproval::STATUS_PENDING)->get();
    }

    public function isFullyApproved(): bool
    {
        $requiredLevels = $this->getRequiredApprovalLevels();
        
        if (empty($requiredLevels)) {
            return true; // No approvals required
        }

        foreach ($requiredLevels as $level) {
            $approvalExists = $this->approvals()
                ->where('approval_level', $level['level'])
                ->where('status', RetirementApproval::STATUS_APPROVED)
                ->exists();
                
            if (!$approvalExists) {
                return false;
            }
        }

        return true;
    }

    public function isRejected(): bool
    {
        return $this->approvals()->rejected()->exists();
    }

    public function canBeDisbursed(): bool
    {
        return $this->isFullyApproved() && !$this->isRejected();
    }

    public function canUserApproveLevel($user, $level): bool
    {
        $settings = $this->getApprovalSettings();
        if (!$settings) {
            return false;
        }

        $approvers = $settings->getApproversForLevel($level);
        return in_array($user->id, $approvers);
    }

    public function createApprovalRequests()
    {
        $requiredLevels = $this->getRequiredApprovalLevels();
        
        foreach ($requiredLevels as $levelData) {
            $level = $levelData['level'];
            $approvers = $levelData['approvers'];
            
            // Create approval request for each approver at this level
            foreach ($approvers as $approverId) {
                RetirementApproval::create([
                    'retirement_id' => $this->id,
                    'approval_level' => $level,
                    'approver_id' => $approverId,
                    'status' => RetirementApproval::STATUS_PENDING
                ]);
            }
        }
    }

    // Status display methods
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'pending' => 'badge bg-warning',
            'checked' => 'badge bg-info',
            'approved' => 'badge bg-success',
            'closed' => 'badge bg-dark',
            'rejected' => 'badge bg-danger',
            default => 'badge bg-secondary'
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'Pending Check',
            'checked' => 'Checked - Awaiting Approval',
            'approved' => 'Approved - Awaiting Journal',
            'closed' => 'Closed',
            'rejected' => 'Rejected',
            default => 'Unknown'
        };
    }

    // Generate retirement number
    public static function generateRetirementNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $count = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;

        return 'RET-' . $year . $month . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    // Calculate variance between requested and actual amounts
    public function getTotalVarianceAttribute(): float
    {
        $requestedTotal = $this->retirementItems->sum('requested_amount');
        $actualTotal = $this->retirementItems->sum('actual_amount');
        return $actualTotal - $requestedTotal;
    }

    public function getRemainingBalanceAttribute(): float
    {
        $disbursedAmount = $this->imprestRequest->disbursed_amount ?? 0;
        return $disbursedAmount - $this->total_amount_used;
    }
}
