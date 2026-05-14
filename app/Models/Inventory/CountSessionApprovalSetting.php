<?php

namespace App\Models\Inventory;

use App\Models\Company;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountSessionApprovalSetting extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'count_session_approval_settings';

    protected $fillable = [
        'company_id',
        'approval_levels',
        'require_approval_for_all',
        'escalation_time',
        'level1_approval_type',
        'level1_approvers',
        'level1_name',
        'level2_approval_type',
        'level2_approvers',
        'level2_name',
        'level3_approval_type',
        'level3_approvers',
        'level3_name',
        'level4_approval_type',
        'level4_approvers',
        'level4_name',
        'level5_approval_type',
        'level5_approvers',
        'level5_name',
    ];

    protected $casts = [
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
     * Get approvers for a specific level
     */
    public function getApproversForLevel($level)
    {
        $property = "level{$level}_approvers";
        return $this->$property ?? [];
    }

    /**
     * Get approval type for a specific level
     */
    public function getApprovalTypeForLevel($level)
    {
        $property = "level{$level}_approval_type";
        return $this->$property ?? 'role';
    }

    /**
     * Get level name for a specific level
     */
    public function getLevelName($level)
    {
        $property = "level{$level}_name";
        return $this->$property ?? "Level {$level}";
    }

    /**
     * Check if a user can approve at a specific level
     */
    public function canUserApproveAtLevel(User $user, int $level): bool
    {
        $approvalType = $this->getApprovalTypeForLevel($level);
        $approvers = $this->getApproversForLevel($level);

        if (empty($approvers)) {
            return false;
        }

        if ($approvalType === 'role') {
            return $user->hasAnyRole($approvers);
        } elseif ($approvalType === 'user') {
            $approverIds = array_map('intval', $approvers);
            return in_array($user->id, $approverIds, true);
        }

        return false;
    }

    /**
     * Get or create settings for a company
     */
    public static function getForCompany($companyId)
    {
        return static::firstOrCreate(
            ['company_id' => $companyId],
            [
                'approval_levels' => 1,
                'require_approval_for_all' => true,
                'escalation_time' => 24,
                'level1_approval_type' => 'role',
                'level1_name' => 'Supervisor',
            ]
        );
    }
}
