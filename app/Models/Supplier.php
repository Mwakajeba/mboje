<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        // Basic Information
        'name',
        'email',
        'phone',
        'address',
        'region',
        'status',
        // Business & Legal Information
        'company_registration_name',
        'tin_number',
        'vat_number',
        'products_or_services',
        // Banking Information
        'bank_name',
        'bank_account_number',
        'account_name',
        // System fields
        'company_id',
        'branch_id',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'status' => 'string'
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_BLACKLISTED = 'blacklisted';


    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function supplierAdvances()
    {
        return $this->hasMany(\App\Models\Purchase\SupplierAdvance::class);
    }

    public function supplierAdvanceDeductions()
    {
        return $this->hasMany(\App\Models\Purchase\SupplierAdvanceDeduction::class);
    }

    public function supplierAdvanceStockRecords()
    {
        return $this->hasMany(\App\Models\Purchase\SupplierAdvanceStockRecord::class);
    }

    public function supplierAdvanceManunuziEntries()
    {
        return $this->hasMany(\App\Models\Purchase\SupplierAdvanceManunuziEntry::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    // Removed scopePreferred and scopeForCategory - fields no longer exist in database

    /**
     * Scope for filtering by company
     */
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope for filtering by branch
     */
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Branch on record, or the logged-in user's branch (session, then profile).
     */
    public static function resolveBranchIdForUser(?int $existingBranchId = null): ?int
    {
        if ($existingBranchId) {
            return (int) $existingBranchId;
        }

        $user = auth()->user();
        if (! $user) {
            return null;
        }

        $branchId = session('branch_id') ?: $user->branch_id;

        return $branchId ? (int) $branchId : null;
    }

    /**
     * Persist login user's branch when supplier has none.
     */
    public function ensureBranchFromLogin(): void
    {
        if ($this->branch_id) {
            return;
        }

        $branchId = self::resolveBranchIdForUser(null);
        if (! $branchId) {
            return;
        }

        $this->forceFill([
            'branch_id' => $branchId,
            'updated_by' => auth()->id(),
        ])->save();

        $this->load('branch');
    }

    /**
     * Suppliers assigned to the branch or with no branch (e.g. mobile / shared).
     */
    public function scopeVisibleInBranch($query, ?int $branchId)
    {
        if (! $branchId) {
            return $query;
        }

        return $query->where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)
                ->orWhereNull('branch_id');
        });
    }

    /**
     * Check if supplier can handle the requested items
     */
    public function canSupplyItems($itemIds)
    {
        // TODO: Implement supplier-item capability mapping
        // This would check if supplier can provide the requested items
        return true; // Placeholder
    }

    /**
     * Get status options for forms
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_BLACKLISTED => 'Blacklisted',
        ];
    }

    // Removed hasSufficientCredit method - credit_limit field no longer exists in database

    /**
     * Get supplier performance rating
     */
    public function getPerformanceRating()
    {
        // TODO: Calculate performance based on delivery time, quality, etc.
        return 4.5; // Placeholder
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute()
    {
        $badgeClasses = [
            'active' => 'bg-success',
            'inactive' => 'bg-warning',
            'blacklisted' => 'bg-danger'
        ];

        $badgeClass = $badgeClasses[$this->status] ?? 'bg-secondary';
        $statusText = ucfirst($this->status);

        return "<span class='badge {$badgeClass}'>{$statusText}</span>";
    }

    /**
     * Get full address combining address and region
     */
    public function getFullAddressAttribute()
    {
        $parts = array_filter([$this->address, $this->region]);
        return implode(', ', $parts);
    }
}
