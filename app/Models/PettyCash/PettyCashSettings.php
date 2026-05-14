<?php

namespace App\Models\PettyCash;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PettyCashSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'operation_mode',
        'default_float_amount',
        'max_transaction_amount',
        'maximum_limit',
        'allowed_expense_categories',
        'require_receipt',
        'minimum_balance_trigger',
        'auto_approve_below_threshold',
        'approval_required',
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
        'notes',
    ];

    protected $casts = [
        'default_float_amount' => 'decimal:2',
        'max_transaction_amount' => 'decimal:2',
        'maximum_limit' => 'decimal:2',
        'allowed_expense_categories' => 'array',
        'require_receipt' => 'boolean',
        'minimum_balance_trigger' => 'decimal:2',
        'auto_approve_below_threshold' => 'boolean',
        'approval_required' => 'boolean',
        'approval_levels' => 'integer',
        'auto_approval_limit' => 'decimal:2',
        'approval_threshold_1' => 'decimal:2',
        'approval_threshold_2' => 'decimal:2',
        'approval_threshold_3' => 'decimal:2',
        'approval_threshold_4' => 'decimal:2',
        'approval_threshold_5' => 'decimal:2',
        'escalation_time' => 'integer',
        'require_approval_for_all' => 'boolean',
        'level1_approvers' => 'array',
        'level2_approvers' => 'array',
        'level3_approvers' => 'array',
        'level4_approvers' => 'array',
        'level5_approvers' => 'array',
    ];

    /**
     * Get the company that owns the settings
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Check if sub-imprest mode is enabled
     */
    public function isSubImprestMode(): bool
    {
        return $this->operation_mode === 'sub_imprest';
    }

    /**
     * Check if standalone mode is enabled
     */
    public function isStandaloneMode(): bool
    {
        return $this->operation_mode === 'standalone';
    }

    /**
     * Get settings for a company (create default if not exists)
     */
    public static function getForCompany($companyId): self
    {
        return static::firstOrCreate(
            ['company_id' => $companyId],
            [
                'operation_mode' => 'standalone',
                'require_receipt' => true,
                'auto_approve_below_threshold' => true,
                'approval_required' => false,
                'approval_levels' => 2,
                'auto_approval_limit' => 100000,
            ]
        );
    }

    /**
     * Get the required approval level for a given float amount.
     */
    public function getRequiredApprovalLevel($floatAmount): int
    {
        if (!$this->approval_required) {
            return 0;
        }

        if ($this->auto_approval_limit && $floatAmount <= $this->auto_approval_limit) {
            return 0; // Auto-approved
        }

        if ($this->require_approval_for_all) {
            return (int) $this->approval_levels;
        }

        // Determine level based on thresholds
        if ($this->approval_threshold_5 && $floatAmount >= $this->approval_threshold_5) {
            return 5;
        }
        if ($this->approval_threshold_4 && $floatAmount >= $this->approval_threshold_4) {
            return 4;
        }
        if ($this->approval_threshold_3 && $floatAmount >= $this->approval_threshold_3) {
            return 3;
        }
        if ($this->approval_threshold_2 && $floatAmount >= $this->approval_threshold_2) {
            return 2;
        }
        if ($this->approval_threshold_1 && $floatAmount >= $this->approval_threshold_1) {
            return 1;
        }

        return 0; // Below all thresholds, auto-approved
    }

    /**
     * Check if a user can approve at a specific level.
     */
    public function canUserApproveAtLevel(\App\Models\User $user, int $level): bool
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


