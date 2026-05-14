<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImprestSettings extends Model
{
    use LogsActivity;
    protected $fillable = [
        'retirement_enabled',
        'imprest_receivables_account',
        'retirement_period_days',
        'check_budget',
        'notes',
        'company_id',
        'branch_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'retirement_enabled' => 'boolean',
        'check_budget' => 'boolean',
    ];

    /**
     * Get the company that owns the settings.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the branch that owns the settings.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who created the settings.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the settings.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the chart account for imprest receivables.
     */
    public function receivablesAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'imprest_receivables_account');
    }

    /**
     * Check if retirement is properly configured (retirement enabled + receivables account set)
     */
    public function isRetirementConfigured()
    {
        return $this->retirement_enabled && $this->imprest_receivables_account;
    }

    /**
     * Get the settings for a specific company and branch.
     */
    public static function getSettings($companyId, $branchId)
    {
        return static::where('company_id', $companyId)
                    ->where('branch_id', $branchId)
                    ->first();
    }

    /**
     * Create or update settings for a company and branch.
     */
    public static function updateSettings($companyId, $branchId, array $data)
    {
        return static::updateOrCreate(
            ['company_id' => $companyId, 'branch_id' => $branchId],
            $data
        );
    }
}
