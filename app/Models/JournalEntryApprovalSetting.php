<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntryApprovalSetting extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'company_id',
        'approval_levels',
        'auto_approval_limit',
        'approval_threshold_1',
        'approval_threshold_2',
        'approval_threshold_3',
        'approval_threshold_4',
        'approval_threshold_5',
        'escalation_time',
        'require_approval_for_all',
        'level1_approval_type',
        'level1_approvers',
        'level2_approval_type',
        'level2_approvers',
        'level3_approval_type',
        'level3_approvers',
        'level4_approval_type',
        'level4_approvers',
        'level5_approval_type',
        'level5_approvers',
    ];

    protected $casts = [
        'auto_approval_limit' => 'decimal:2',
        'approval_threshold_1' => 'decimal:2',
        'approval_threshold_2' => 'decimal:2',
        'approval_threshold_3' => 'decimal:2',
        'approval_threshold_4' => 'decimal:2',
        'approval_threshold_5' => 'decimal:2',
        'require_approval_for_all' => 'boolean',
        'level1_approvers' => 'array',
        'level2_approvers' => 'array',
        'level3_approvers' => 'array',
        'level4_approvers' => 'array',
        'level5_approvers' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the required approval level for a given amount.
     */
    public function getRequiredApprovalLevel($amount): int
    {
        if ($this->require_approval_for_all) {
            return (int) $this->approval_levels;
        }

        return 0;
    }

    /**
     * Check if a user can approve at a specific level.
     */
    public function canUserApproveAtLevel(User $user, int $level): bool
    {
        $approvalType = $this->{"level{$level}_approval_type"};
        $approvers = $this->{"level{$level}_approvers"} ?? [];

        if ($approvalType === 'role') {
            return $user->hasAnyRole($approvers);
        } elseif ($approvalType === 'user') {
            $approverIds = array_map('intval', $approvers);
            return in_array($user->id, $approverIds, true);
        }

        return false;
    }
}

