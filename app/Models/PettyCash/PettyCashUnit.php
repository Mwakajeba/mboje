<?php

namespace App\Models\PettyCash;

use App\Models\Branch;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\User;
use App\Models\BankAccount;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class PettyCashUnit extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id',
        'branch_id',
        'name',
        'code',
        'custodian_id',
        'supervisor_id',
        'float_amount',
        'current_balance',
        'maximum_limit',
        'approval_threshold',
        'bank_account_id',
        'petty_cash_account_id',
        'suspense_account_id',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'float_amount' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'maximum_limit' => 'decimal:2',
        'approval_threshold' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'custodian_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function pettyCashAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'petty_cash_account_id');
    }

    public function suspenseAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'suspense_account_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PettyCashTransaction::class);
    }

    public function replenishments(): HasMany
    {
        return $this->hasMany(PettyCashReplenishment::class);
    }

    public function registerEntries(): HasMany
    {
        return $this->hasMany(PettyCashRegister::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(PettyCashApproval::class);
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessors
    public function getEncodedIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    // Helper methods
    public function canSpend($amount): bool
    {
        if ($this->current_balance < $amount) {
            return false;
        }
        return true;
    }

    public function requiresApproval($amount): bool
    {
        if ($this->approval_threshold && $amount > $this->approval_threshold) {
            return true;
        }
        return false;
    }

    /**
     * Initialize approval workflow for petty cash unit creation
     */
    public function initializeApprovalWorkflow()
    {
        $settings = PettyCashSettings::getForCompany($this->company_id);
        
        if (!$settings->approval_required) {
            // No approval required - mark as approved
            $this->update([
                'is_active' => true,
            ]);
            return;
        }

        $requiredLevel = $settings->getRequiredApprovalLevel($this->float_amount);
        
        if ($requiredLevel === 0) {
            // Auto-approved - activate unit
            $this->update([
                'is_active' => true,
            ]);
            return;
        }

        // Create approval records for each level
        for ($level = 1; $level <= $requiredLevel; $level++) {
            $approvalType = $settings->{"level{$level}_approval_type"};
            $approvers = $settings->{"level{$level}_approvers"} ?? [];

            if ($approvalType === 'role') {
                foreach ($approvers as $roleName) {
                    $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
                    if ($role) {
                        PettyCashApproval::create([
                            'petty_cash_unit_id' => $this->id,
                            'approval_level' => $level,
                            'approver_type' => 'role',
                            'approver_name' => $role->name,
                            'status' => 'pending',
                        ]);
                    }
                }
            } elseif ($approvalType === 'user') {
                foreach ($approvers as $userId) {
                    $userId = (int) $userId;
                    $user = User::find($userId);
                    if ($user) {
                        PettyCashApproval::create([
                            'petty_cash_unit_id' => $this->id,
                            'approval_level' => $level,
                            'approver_type' => 'user',
                            'approver_id' => $user->id,
                            'approver_name' => $user->name,
                            'status' => 'pending',
                        ]);
                    }
                }
            }
        }

        // Set unit as inactive until approved
        $this->update([
            'is_active' => false,
        ]);
    }

    /**
     * Check if unit is fully approved
     */
    public function isFullyApproved(): bool
    {
        $settings = PettyCashSettings::getForCompany($this->company_id);
        
        if (!$settings->approval_required) {
            return true;
        }

        $requiredLevel = $settings->getRequiredApprovalLevel($this->float_amount);
        
        if ($requiredLevel === 0) {
            return true;
        }

        // Check if the required approval level is approved
        $requiredLevelApproved = $this->approvals()
            ->where('approval_level', $requiredLevel)
            ->where('status', 'approved')
            ->exists();
            
        return $requiredLevelApproved;
    }

    /**
     * Check if unit is rejected
     */
    public function isRejected(): bool
    {
        return $this->approvals()->where('status', 'rejected')->exists();
    }

    /**
     * Get current pending approval
     */
    public function currentApproval()
    {
        return $this->approvals()
            ->where('status', 'pending')
            ->orderBy('approval_level')
            ->first();
    }
}

