<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ImprestApprovalSettings;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Hr\Department as HrDepartment;

class ImprestRequest extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'request_number',
        'employee_id',
        'department_id',
        'company_id',
        'branch_id',
        'purpose',
        'amount_requested',
        'date_required',
        'status',
        'description',
        'created_by',
        'checked_by',
        'checked_at',
        'check_comments',
        'approved_by',
        'approved_at',
        'approval_comments',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'payment_id',
        'disbursed_by',
        'disbursed_at',
        'disbursed_amount',
    ];

    protected $casts = [
        'amount_requested' => 'decimal:2',
        'disbursed_amount' => 'decimal:2',
        'date_required' => 'date',
        'checked_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'disbursed_at' => 'datetime',
    ];

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(HrDepartment::class);
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

    public function disburser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Payment::class, 'payment_id');
    }

    public function disbursement(): HasOne
    {
        return $this->hasOne(ImprestDisbursement::class);
    }

    public function liquidation(): HasOne
    {
        return $this->hasOne(ImprestLiquidation::class);
    }

    public function retirement(): HasOne
    {
        return $this->hasOne(Retirement::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(ImprestJournalEntry::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ImprestDocument::class);
    }

    public function imprestItems(): HasMany
    {
        return $this->hasMany(ImprestItem::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ImprestApproval::class);
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    // Methods
    public function canBeChecked(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'checked';
    }

    public function canBeDisbursed(): bool
    {
        return $this->status === 'approved' && !$this->payment_id && !$this->disbursement;
    }

    public function canBeLiquidated(): bool
    {
        return $this->status === 'disbursed' && ($this->payment_id || $this->disbursement) && !$this->liquidation;
    }

    public function canBeRetired(): bool
    {
        return $this->status === 'disbursed' && ($this->payment_id || $this->disbursement) && !$this->retirement;
    }

    public function canBeClosed(): bool
    {
        return $this->status === 'liquidated' &&
            $this->retirement &&
            $this->retirement->status === 'approved';
    }

    /**
     * Check if a user can check this imprest request based on approval settings
     * DEPRECATED: Use multi-level approval system instead
     */
    public function canUserCheck(User $user): bool
    {
        // For multi-level approval, use the new system
        if ($this->requiresApproval()) {
            return $this->canUserApproveAtLevel($user, 1); // First level is typically checker
        }

        // Legacy fallback - allow checking if no multi-level approval required
        return $this->canBeChecked();
    }

    /**
     * Check if a user can approve this imprest request based on approval settings
     * DEPRECATED: Use multi-level approval system instead
     */
    public function canUserApprove(User $user): bool
    {
        // For multi-level approval, check if user can approve at current level
        if ($this->requiresApproval()) {
            $currentLevel = $this->getCurrentApprovalLevel();
            return $currentLevel ? $this->canUserApproveAtLevel($user, $currentLevel) : false;
        }

        // Legacy fallback - allow approval if no multi-level approval required
        return $this->canBeApproved();
    }

    /**
     * Check if a user can disburse this imprest request based on approval settings
     * DEPRECATED: Use multi-level approval system instead
     */
    public function canUserDisburse(User $user): bool
    {
        // For multi-level approval, check if all approvals are complete
        if ($this->requiresApproval()) {
            return $this->isFullyApproved() && $this->canBeDisbursed();
        }

        // Legacy fallback - allow disbursement if no multi-level approval required
        return $this->canBeDisbursed();
    }

    public function getRemainingBalance(): float
    {
        if (!$this->disbursement) {
            return 0;
        }

        if (!$this->retirement) {
            return (float) ($this->disbursed_amount ?? 0);
        }

        return (float) $this->retirement->remaining_balance;
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'pending' => 'badge bg-warning',
            'checked' => 'badge bg-info',
            'approved' => 'badge bg-primary',
            'disbursed' => 'badge bg-secondary',
            'liquidated' => 'badge bg-success',
            'closed' => 'badge bg-dark',
            'rejected' => 'badge bg-danger',
            default => 'badge bg-light text-dark'
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending Review',
            'checked' => 'Checked by Manager',
            'approved' => 'Approved by Finance',
            'disbursed' => 'Funds Disbursed',
            'liquidated' => 'Retirement Submitted',
            'closed' => 'Closed',
            'rejected' => 'Rejected',
            default => ucfirst($this->status)
        };
    }

    // Generate unique request number
    public static function generateRequestNumber(): string
    {
        $year = date('Y');
        $month = date('m');

        $lastRequest = static::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastRequest ? (int)substr($lastRequest->request_number, -4) + 1 : 1;

        return 'IMP-' . $year . $month . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    // Multi-level approval methods
    public function getApprovalSettings()
    {
        // Get approval settings directly by company_id and branch_id
        return ImprestApprovalSettings::where('company_id', $this->company_id)
            ->where('branch_id', $this->branch_id)
            ->first();
    }

    public function requiresApproval(): bool
    {
        $settings = $this->getApprovalSettings();
        return $settings && $settings->approval_required;
    }

    /**
     * Get the required approval levels (just the level numbers) for this imprest request
     * @return array Array of level numbers, e.g., [1, 2, 3]
     */
    public function getRequiredApprovalLevels(): array
    {
        $levelDetails = $this->getRequiredApprovalLevelDetails();

        // Extract just the level numbers
        return array_column($levelDetails, 'level');
    }

    /**
     * Get the full required approval level details including approvers and thresholds
     * @return array Array of level details with 'level', 'approvers', and 'threshold' keys
     */
    public function getRequiredApprovalLevelDetails(): array
    {
        $settings = $this->getApprovalSettings();
        info('Settings foundddd', ['settings' => $settings]);

        if (!$settings) {
            info('No settings found for company_id: ' . $this->company_id . ' branch_id: ' . $this->branch_id);
            return [];
        }

        info('Settings found', [
            'approval_required' => $settings->approval_required,
            'approval_levels' => $settings->approval_levels,
            'type' => gettype($settings->approval_required)
        ]);

        if (!$settings->approval_required) {
            info('Approval not required');
            return [];
        }

        $amount = $this->amount_requested;
        info('Getting required approvals for amount: ' . $amount);

        $result = [];

        // Get required levels based on approval_levels setting
        $maxLevels = $settings->approval_levels;

        // Check each level up to the configured approval_levels
        for ($level = 1; $level <= $maxLevels; $level++) {
            $thresholdField = "level{$level}_amount_threshold";
            $approversField = "level{$level}_approvers";

            $threshold = $settings->{$thresholdField};
            $approvers = $settings->{$approversField};

            // Skip if approvers not set (threshold can be null for sequential levels)
            if (is_null($approvers) || empty($approvers)) {
                continue;
            }

            // Add this level to required approvals
            $result[] = [
                'level' => $level,
                'threshold' => $threshold,
                'approvers' => is_array($approvers) ? $approvers : json_decode($approvers, true) ?? []
            ];
        }

        info('Required approvals result', ['result' => $result]);

        return $result;
    }

    public function getPendingApprovals()
    {
        return $this->approvals()->pending()->get();
    }

    public function getCompletedApprovals()
    {
        return $this->approvals()->where('status', '!=', ImprestApproval::STATUS_PENDING)->get();
    }

    public function isFullyApproved(): bool
    {
        $settings = $this->getApprovalSettings();

        // If no approval settings or approval not required, consider it approved
        if (!$settings || !$settings->approval_required) {
            info('isFullyApproved: No settings or approval not required', [
                'has_settings' => !is_null($settings),
                'approval_required' => $settings ? $settings->approval_required : null
            ]);
            return true;
        }

        $requiredLevels = $this->getRequiredApprovalLevels();
        info('isFullyApproved: Required levels', ['count' => count($requiredLevels), 'levels' => $requiredLevels]);

       info('requiredLevels', [$requiredLevels]);

        if (empty($requiredLevels)) {
            info('isFullyApproved: No required levels for this amount');
            return true;
        }

        // Check if each required level has been approved
        foreach ($requiredLevels as $level) {
            $approvalExists = $this->approvals()
                ->where('approval_level', $level)
                ->where('status', ImprestApproval::STATUS_APPROVED)
                ->exists();

            info('Checking level ' . $level, ['exists' => $approvalExists]);

            if (!$approvalExists) {
                info('isFullyApproved: Level ' . $level . ' not approved yet');
                return false; // This level hasn't been approved yet
            }
        }

        info('isFullyApproved: All levels approved');
        return true; // All required levels have been approved
    }

    public function hasRejectedApprovals(): bool
    {
        return $this->approvals()->rejected()->exists();
    }

    public function getCurrentApprovalLevel(): ?int
    {
        $requiredLevels = $this->getRequiredApprovalLevels();

        foreach ($requiredLevels as $level) {
            $approved = $this->approvals()
                ->where('approval_level', $level)
                ->where('status', ImprestApproval::STATUS_APPROVED)
                ->exists();

            if (!$approved) {
                return $level;
            }
        }

        return null; // All levels approved
    }

    public function canUserApproveAtLevel(User $user, int $level): bool
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
        $requiredLevelDetails = $this->getRequiredApprovalLevelDetails();

        foreach ($requiredLevelDetails as $levelData) {
            $level = $levelData['level'];
            $approvers = $levelData['approvers'];

            // Create approval request for each approver at this level
            foreach ($approvers as $approverId) {
                ImprestApproval::create([
                    'imprest_request_id' => $this->id,
                    'approval_level' => $level,
                    'approver_id' => $approverId,
                    'status' => ImprestApproval::STATUS_PENDING
                ]);
            }
        }
    }
}
