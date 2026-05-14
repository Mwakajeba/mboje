<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImprestApprovalSettings extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'imprest_multilevel_approval_settings';

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

        // Ensure amount is numeric
        $amount = (float) $amount;

        info('getRequiredApprovalsForAmount called', [
            'amount' => $amount,
            'approval_levels' => $this->approval_levels
        ]);

        for ($level = 1; $level <= $this->approval_levels; $level++) {
            $threshold = $this->getAmountThresholdForLevel($level);
            $approvers = $this->getApproversForLevel($level);

            info("Level $level check", [
                'threshold' => $threshold,
                'threshold_float' => (float) $threshold,
                'approvers' => $approvers,
                'approvers_count' => count($approvers ?? []),
                'amount' => $amount,
                'comparison' => $amount >= (float) $threshold
            ]);

            // Skip if no approvers are configured for this level
            if (empty($approvers)) {
                info("Level $level: No approvers, skipping");
                continue;
            }

            // If threshold is null or amount meets/exceeds threshold, approval is required
            if ($threshold === null || $amount >= (float) $threshold) {
                info("Level $level: Required!");
                $requiredApprovals[] = [
                    'level' => $level,
                    'approvers' => $approvers,
                    'threshold' => $threshold
                ];
            } else {
                info("Level $level: Not required (amount below threshold)");
            }
        }

        info('getRequiredApprovalsForAmount result', ['count' => count($requiredApprovals), 'approvals' => $requiredApprovals]);

        return $requiredApprovals;
    }

    /**
     * Get approval settings for a company/branch
     */
    public static function getSettingsForCompany($companyId, $branchId = null)
    {
        return static::where('company_id', $companyId)
            ->where(function ($query) use ($branchId) {
                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }
            })
            ->orderBy('branch_id', 'desc') // Branch-specific settings take precedence
            ->first();
    }
}
