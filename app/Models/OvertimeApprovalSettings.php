<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeApprovalSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'approval_required',
        'approval_levels',
        'level1_hours_threshold',
        'level1_approvers',
        'level2_hours_threshold',
        'level2_approvers',
        'level3_hours_threshold',
        'level3_approvers',
        'level4_hours_threshold',
        'level4_approvers',
        'level5_hours_threshold',
        'level5_approvers',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'level1_approvers' => 'array',
        'level2_approvers' => 'array',
        'level3_approvers' => 'array',
        'level4_approvers' => 'array',
        'level5_approvers' => 'array',
        'approval_required' => 'boolean',
        'level1_hours_threshold' => 'decimal:2',
        'level2_hours_threshold' => 'decimal:2',
        'level3_hours_threshold' => 'decimal:2',
        'level4_hours_threshold' => 'decimal:2',
        'level5_hours_threshold' => 'decimal:2',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Helper methods
    public function getApproversForLevel($level)
    {
        $property = "level{$level}_approvers";
        return $this->$property ?? [];
    }

    public function getHoursThresholdForLevel($level)
    {
        $property = "level{$level}_hours_threshold";
        return $this->$property;
    }

    public function getRequiredApprovalsForHours($hours)
    {
        $requiredApprovals = [];
        
        for ($level = 1; $level <= $this->approval_levels; $level++) {
            $threshold = $this->getHoursThresholdForLevel($level);
            
            if ($threshold === null || $hours >= $threshold) {
                $approvers = $this->getApproversForLevel($level);
                if (!empty($approvers)) {
                    $requiredApprovals[] = [
                        'level' => $level,
                        'approvers' => $approvers,
                        'threshold' => $threshold
                    ];
                }
            }
        }
        
        return $requiredApprovals;
    }

    public function canUserApproveAtLevel($userId, $level)
    {
        $approvers = $this->getApproversForLevel($level);
        return in_array($userId, $approvers);
    }

    public function getMaxApprovalLevel()
    {
        return $this->approval_levels;
    }

    /**
     * Get overtime approval settings for a company/branch
     */
    public static function getSettingsForCompany($companyId, $branchId = null)
    {
        return static::where('company_id', $companyId)
            ->where(function ($query) use ($branchId) {
                if ($branchId) {
                    $query->where('branch_id', $branchId)
                          ->orWhereNull('branch_id');
                } else {
                    $query->whereNull('branch_id');
                }
            })
            ->orderBy('branch_id', 'desc') // Branch-specific settings take precedence
            ->first();
    }

    /**
     * Get all assigned approvers with their details grouped by level
     *
     * @return array
     */
    public function getAllAssignedApprovers()
    {
        $allApprovers = [];
        $uniqueUserIds = [];

        // Collect all approvers from all levels
        for ($level = 1; $level <= $this->approval_levels; $level++) {
            $approverIds = $this->getApproversForLevel($level);
            $threshold = $this->getHoursThresholdForLevel($level);

            if (!empty($approverIds)) {
                $approvers = \App\Models\User::whereIn('id', $approverIds)
                    ->where('company_id', $this->company_id)
                    ->select('id', 'name', 'email')
                    ->with('branches:id,name')
                    ->get();

                foreach ($approvers as $approver) {
                    // Track unique users
                    if (!in_array($approver->id, $uniqueUserIds)) {
                        $uniqueUserIds[] = $approver->id;
                    }

                    $allApprovers[] = [
                        'user' => $approver,
                        'level' => $level,
                        'threshold' => $threshold,
                    ];
                }
            }
        }

        // Group by user
        $groupedByUser = [];
        foreach ($allApprovers as $item) {
            $userId = $item['user']->id;
            if (!isset($groupedByUser[$userId])) {
                $groupedByUser[$userId] = [
                    'user' => $item['user'],
                    'levels' => [],
                ];
            }
            $groupedByUser[$userId]['levels'][] = [
                'level' => $item['level'],
                'threshold' => $item['threshold'],
            ];
        }

        return [
            'by_level' => $allApprovers,
            'by_user' => array_values($groupedByUser),
            'unique_count' => count($uniqueUserIds),
        ];
    }

    /**
     * Get all unique approver user IDs across all levels
     *
     * @return array
     */
    public function getAllApproverIds()
    {
        $allIds = [];
        for ($level = 1; $level <= $this->approval_levels; $level++) {
            $approverIds = $this->getApproversForLevel($level);
            if (!empty($approverIds)) {
                $allIds = array_merge($allIds, $approverIds);
            }
        }
        return array_unique($allIds);
    }
}
