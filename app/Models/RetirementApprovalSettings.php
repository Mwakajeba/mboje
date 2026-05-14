<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetirementApprovalSettings extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'retirement_multilevel_approval_settings';

    protected $fillable = [
        'company_id',
        'branch_id',
        'approval_required',
        'approval_levels',
        'level1_amount_threshold',
        'level1_approvers',
        'level2_amount_threshold',
        'level2_approvers',
        'level3_amount_threshold',
        'level3_approvers',
        'level4_amount_threshold',
        'level4_approvers',
        'level5_amount_threshold',
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
        'level1_amount_threshold' => 'decimal:2',
        'level2_amount_threshold' => 'decimal:2',
        'level3_amount_threshold' => 'decimal:2',
        'level4_amount_threshold' => 'decimal:2',
        'level5_amount_threshold' => 'decimal:2',
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

    public function getAmountThresholdForLevel($level)
    {
        $property = "level{$level}_amount_threshold";
        return $this->$property;
    }

    public function getRequiredApprovalsForAmount($amount)
    {
        $requiredApprovals = [];
        
        for ($level = 1; $level <= $this->approval_levels; $level++) {
            $threshold = $this->getAmountThresholdForLevel($level);
            $approvers = $this->getApproversForLevel($level);
            
            // Include this level if:
            // 1. It's Level 1 (always required if approvers exist)
            // 2. Amount is >= threshold for this level
            if (!empty($approvers) && ($level == 1 || ($threshold !== null && $amount >= $threshold))) {
                $requiredApprovals[] = [
                    'level' => $level,
                    'approvers' => $approvers,
                    'threshold' => $threshold
                ];
            }
        }
        
        return $requiredApprovals;
    }
}
