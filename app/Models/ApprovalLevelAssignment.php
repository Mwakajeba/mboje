<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalLevelAssignment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'approval_level_id',
        'user_id',
        'role_id',
        'branch_id',
    ];

    protected $casts = [
        'approval_level_id' => 'integer',
        'user_id' => 'integer',
        'role_id' => 'integer',
        'branch_id' => 'integer',
    ];

    /**
     * Get the approval level this assignment belongs to.
     */
    public function approvalLevel(): BelongsTo
    {
        return $this->belongsTo(ApprovalLevel::class);
    }

    /**
     * Get the user assigned to this level (if user-based).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the role assigned to this level (if role-based).
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Get the branch for this assignment (if branch-specific).
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by role.
     */
    public function scopeForRole($query, int $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * Scope to filter by branch.
     */
    public function scopeForBranch($query, ?int $branchId)
    {
        if ($branchId === null) {
            return $query->whereNull('branch_id');
        }
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to filter global assignments (no branch).
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('branch_id');
    }

    /**
     * Check if this is a user-based assignment.
     */
    public function isUserBased(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Check if this is a role-based assignment.
     */
    public function isRoleBased(): bool
    {
        return $this->role_id !== null;
    }

    /**
     * Check if this is a branch-specific assignment.
     */
    public function isBranchSpecific(): bool
    {
        return $this->branch_id !== null;
    }

    /**
     * Get approvers for this assignment.
     * Returns collection of users who can approve at this level.
     */
    public function getApprovers(): \Illuminate\Support\Collection
    {
        $approvers = collect();

        if ($this->isUserBased() && $this->user) {
            $approvers->push($this->user);
        } elseif ($this->isRoleBased() && $this->role) {
            // Get all users with this role
            $roleUsers = User::role($this->role->name)->get();
            $approvers = $approvers->merge($roleUsers);
        }

        return $approvers->unique('id');
    }
}
