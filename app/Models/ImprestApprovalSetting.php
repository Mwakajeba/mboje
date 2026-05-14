<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImprestApprovalSetting extends Model
{
    protected $fillable = [
        'user_id',
        'approval_role',
        'is_active',
        'amount_limit',
        'department_ids',
        'company_id',
        'branch_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'amount_limit' => 'decimal:2',
        'department_ids' => 'array',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('approval_role', $role);
    }

    /**
     * Helper methods
     */
    public function getRoleLabelAttribute(): string
    {
        return match($this->approval_role) {
            'checker' => 'Checker/Reviewer',
            'approver' => 'Approver',
            'provider' => 'Provider',
            default => ucfirst($this->approval_role)
        };
    }

    public function getRoleBadgeClassAttribute(): string
    {
        return match($this->approval_role) {
            'checker' => 'badge bg-info',
            'approver' => 'badge bg-success',
            'provider' => 'badge bg-primary',
            default => 'badge bg-secondary'
        };
    }

    public function canApproveAmount($amount): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->amount_limit === null) {
            return true; // No limit set
        }

        return $amount <= $this->amount_limit;
    }

    public function canHandleDepartment($departmentId): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (empty($this->department_ids)) {
            return true; // Can handle all departments
        }

        return in_array($departmentId, $this->department_ids);
    }

    public function getDepartmentNamesAttribute(): string
    {
        if (empty($this->department_ids)) {
            return 'All Departments';
        }

        $departments = Department::whereIn('id', $this->department_ids)->pluck('name')->toArray();
        return implode(', ', $departments);
    }
}
