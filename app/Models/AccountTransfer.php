<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class AccountTransfer extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id',
        'branch_id',
        'transfer_number',
        'transfer_date',
        'from_account_type',
        'from_account_id',
        'to_account_type',
        'to_account_id',
        'amount',
        'currency_id',
        'exchange_rate',
        'amount_fcy',
        'charges',
        'charges_account_id',
        'description',
        'reference_number',
        'attachment',
        'status',
        'current_approval_level',
        'created_by',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejection_reason',
        'journal_id',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'amount' => 'decimal:2',
        'amount_fcy' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'charges' => 'decimal:2',
        'approved_at' => 'datetime',
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

    /**
     * Get the from account (polymorphic)
     */
    public function getFromAccountAttribute()
    {
        switch ($this->from_account_type) {
            case 'bank':
                return \App\Models\BankAccount::find($this->from_account_id);
            case 'cash':
                return \App\Models\CashDepositAccount::find($this->from_account_id);
            case 'petty_cash':
                return \App\Models\PettyCash\PettyCashUnit::find($this->from_account_id);
            default:
                return null;
        }
    }

    /**
     * Get the to account (polymorphic)
     */
    public function getToAccountAttribute()
    {
        switch ($this->to_account_type) {
            case 'bank':
                return \App\Models\BankAccount::find($this->to_account_id);
            case 'cash':
                return \App\Models\CashDepositAccount::find($this->to_account_id);
            case 'petty_cash':
                return \App\Models\PettyCash\PettyCashUnit::find($this->to_account_id);
            default:
                return null;
        }
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function chargesAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'charges_account_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(AccountTransferApproval::class);
    }

    // Accessors
    public function getEncodedIdAttribute()
    {
        return Hashids::encode($this->id);
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

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'submitted');
    }

    // Helper methods
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    public function canBeApproved(): bool
    {
        // Can be approved if status is 'submitted' or 'draft' (approvers can approve drafts directly)
        if (!in_array($this->status, ['submitted', 'draft'])) {
            return false;
        }
        
        // Check if approval settings exist and user has permission
        $settings = \App\Models\AccountTransferApprovalSetting::where('company_id', $this->company_id)->first();
        
        if ($settings && $settings->require_approval_for_all) {
            // Get current approval level (or level 1 if not set)
            $currentLevel = $this->current_approval_level ?? 1;
            
            // Check if current user can approve at the current level
            $user = auth()->user();
            if ($user && $settings->canUserApproveAtLevel($user, $currentLevel)) {
                // Check if there's a pending approval at this level for this user
                $pendingApproval = $this->approvals()
                    ->where('approval_level', $currentLevel)
                    ->where('status', 'pending')
                    ->where(function($query) use ($user, $settings, $currentLevel) {
                        $approvalType = $settings->{"level{$currentLevel}_approval_type"};
                        $approvers = $settings->{"level{$currentLevel}_approvers"} ?? [];
                        
                        if ($approvalType === 'user') {
                            $userIds = array_map('intval', $approvers);
                            $query->where('approver_id', $user->id)
                                  ->whereIn('approver_id', $userIds);
                        } elseif ($approvalType === 'role') {
                            $userRoles = $user->roles->pluck('name')->toArray();
                            $roleNames = array_filter($approvers, function($a) { 
                                return is_string($a) && !str_starts_with($a, 'role_'); 
                            });
                            if (empty($roleNames)) {
                                // Try extracting from role_ format
                                $roleNames = array_map(function($a) {
                                    return str_replace('role_', '', $a);
                                }, array_filter($approvers, function($a) { 
                                    return is_string($a) && str_starts_with($a, 'role_'); 
                                }));
                            }
                            $query->whereIn('approver_name', $roleNames)
                                  ->where(function($q) use ($userRoles) {
                                      foreach ($userRoles as $role) {
                                          $q->orWhere('approver_name', $role);
                                      }
                                  });
                        }
                    })
                    ->first();
                
                return $pendingApproval !== null;
            }
            return false;
        }
        
        // If no approval settings or approval not required, allow approval for submitted status
        return $this->status === 'submitted';
    }

    public function canBePosted(): bool
    {
        // Can only be posted if fully approved and not already posted
        return $this->isFullyApproved() && !$this->journal_id;
    }

    /**
     * Initialize approval workflow when transfer is submitted
     */
    public function initializeApprovalWorkflow()
    {
        // Check if approvals already exist to prevent duplicates
        if ($this->approvals()->count() > 0) {
            return;
        }
        
        $settings = \App\Models\AccountTransferApprovalSetting::where('company_id', $this->company_id)->first();
        
        if (!$settings || !$settings->require_approval_for_all) {
            // No approval required - auto-approve
            $this->update([
                'status' => 'approved',
                'approved_by' => $this->created_by,
                'approved_at' => now(),
            ]);
            return;
        }

        $requiredLevel = $settings->approval_levels;
        
        // Create approval records for each level
        for ($level = 1; $level <= $requiredLevel; $level++) {
            $approvalType = $settings->{"level{$level}_approval_type"};
            $approvers = $settings->{"level{$level}_approvers"} ?? [];

            if ($approvalType === 'role') {
                foreach ($approvers as $roleName) {
                    // Handle both formats: "admin" or "role_admin"
                    if (str_starts_with($roleName, 'role_')) {
                        $roleName = str_replace('role_', '', $roleName);
                    }
                    $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
                    if ($role) {
                        AccountTransferApproval::create([
                            'account_transfer_id' => $this->id,
                            'approval_level' => $level,
                            'approver_type' => 'role',
                            'approver_name' => $role->name,
                            'status' => 'pending',
                        ]);
                    }
                }
            } elseif ($approvalType === 'user') {
                foreach ($approvers as $userId) {
                    // Handle both formats: 123 (integer) or "user_123"
                    if (is_string($userId) && str_starts_with($userId, 'user_')) {
                        $userId = (int) str_replace('user_', '', $userId);
                    }
                    $userId = (int) $userId;
                    $user = User::find($userId);
                    if ($user) {
                        AccountTransferApproval::create([
                            'account_transfer_id' => $this->id,
                            'approval_level' => $level,
                            'approver_id' => $user->id,
                            'approver_type' => 'user',
                            'approver_name' => $user->name,
                            'status' => 'pending',
                        ]);
                    }
                }
            }
        }

        // Update transfer status
        $this->update([
            'status' => 'submitted',
            'current_approval_level' => 1,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    /**
     * Check if transfer is fully approved
     */
    public function isFullyApproved(): bool
    {
        $settings = \App\Models\AccountTransferApprovalSetting::where('company_id', $this->company_id)->first();
        
        if (!$settings || !$settings->require_approval_for_all) {
            // If no approval required, check if status is approved
            return $this->status === 'approved';
        }

        $requiredLevel = $settings->approval_levels;
        
        // Check if all levels have been approved
        for ($level = 1; $level <= $requiredLevel; $level++) {
            $levelApprovals = $this->approvals()->where('approval_level', $level)->get();
            
            if ($levelApprovals->isEmpty()) {
                // No approvals created for this level - not fully approved
                return false;
            }
            
            // Check if all approvals at this level are approved
            $allApproved = $levelApprovals->every(function($approval) {
                return $approval->status === 'approved';
            });
            
            if (!$allApproved) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get current pending approval for the user
     */
    public function getCurrentApproval()
    {
        $currentLevel = $this->current_approval_level ?? 1;
        $user = auth()->user();
        
        if (!$user) {
            return null;
        }
        
        $settings = \App\Models\AccountTransferApprovalSetting::where('company_id', $this->company_id)->first();
        if (!$settings) {
            return null;
        }
        
        $approvalType = $settings->{"level{$currentLevel}_approval_type"};
        
        if ($approvalType === 'user') {
            return $this->approvals()
                ->where('approval_level', $currentLevel)
                ->where('approver_id', $user->id)
                ->where('status', 'pending')
                ->first();
        } elseif ($approvalType === 'role') {
            $userRoles = $user->roles->pluck('name')->toArray();
            return $this->approvals()
                ->where('approval_level', $currentLevel)
                ->whereIn('approver_name', $userRoles)
                ->where('status', 'pending')
                ->first();
        }
        
        return null;
    }

    public function canBeDeleted(): bool
    {
        // Can delete if not posted to GL (no journal_id) and status is draft, rejected, or submitted
        return !$this->journal_id && in_array($this->status, ['draft', 'rejected', 'submitted']);
    }

    public function canBeRejected(): bool
    {
        // Can only be rejected if status is 'submitted' (not draft)
        if ($this->status !== 'submitted') {
            return false;
        }
        
        // Check if approval settings exist and user has permission
        $settings = \App\Models\AccountTransferApprovalSetting::where('company_id', $this->company_id)->first();
        
        if ($settings && $settings->require_approval_for_all) {
            // Get current approval level
            $currentLevel = $this->current_approval_level ?? 1;
            
            // Check if current user can approve at the current level
            $user = auth()->user();
            if ($user && $settings->canUserApproveAtLevel($user, $currentLevel)) {
                return true;
            }
            return false;
        }
        
        // If no approval settings or approval not required, allow rejection for submitted status
        return $this->status === 'submitted';
    }

    public function canBeSubmitted(): bool
    {
        // Can be submitted if status is 'draft' and user is the creator
        if ($this->status !== 'draft') {
            return false;
        }
        
        $user = auth()->user();
        return $user && $this->created_by === $user->id;
    }
}

