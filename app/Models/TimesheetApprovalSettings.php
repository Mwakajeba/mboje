<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class TimesheetApprovalSettings extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'approval_required',
        'approvers',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'approvers' => 'array',
        'approval_required' => 'boolean',
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
    public function getApprovers(): array
    {
        return $this->approvers ?? [];
    }

    public function canUserApprove($userId): bool
    {
        $approvers = $this->getApprovers();
        return in_array($userId, $approvers);
    }

    /**
     * Get timesheet approval settings for a company/branch
     */
    public static function getSettingsForCompany($companyId, $branchId = null): ?self
    {
        try {
            // Check if company_id column exists in the table
            $columns = Schema::getColumnListing('timesheet_approval_settings');
            if (!in_array('company_id', $columns)) {
                // If company_id doesn't exist, return the first record (legacy support)
                \Log::warning('timesheet_approval_settings table missing company_id column');
                return static::first();
            }
            
            return static::where('company_id', $companyId)
                ->where(function ($query) use ($branchId) {
                    if ($branchId) {
                        $query->where('branch_id', $branchId);
                    } else {
                        $query->whereNull('branch_id');
                    }
                })
                ->first();
        } catch (\Exception $e) {
            \Log::error('Error fetching timesheet approval settings: ' . $e->getMessage());
            return null;
        }
    }
}
