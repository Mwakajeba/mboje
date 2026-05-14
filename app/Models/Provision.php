<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class Provision extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'provisions';

    protected $fillable = [
        'provision_number',
        'provision_type',
        'title',
        'description',
        'present_obligation_type',
        'has_present_obligation',
        'probability',
        'probability_percent',
        'is_recognised',
        'estimate_method',
        'computation_assumptions', // JSON field for storing calculation inputs
        'currency_code',
        'fx_rate_at_creation',
        'original_estimate',
        'undiscounted_amount', // Future undiscounted amount for disclosure
        'current_balance',
        'utilised_amount',
        'reversed_amount',
        'is_discounted',
        'discount_rate',
        'discount_rate_id', // Link to central discount rate table
        'expected_settlement_date',
        'expense_account_id',
        'provision_account_id',
        'unwinding_account_id',
        'related_asset_id', // For Environmental provisions
        'asset_category',
        'is_capitalised', // Whether provision is capitalised into PPE
        'depreciation_start_date',
        'status',
        'current_approval_level',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'company_id',
        'branch_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'has_present_obligation' => 'boolean',
        'is_recognised' => 'boolean',
        'original_estimate' => 'decimal:2',
        'undiscounted_amount' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'utilised_amount' => 'decimal:2',
        'reversed_amount' => 'decimal:2',
        'discount_rate' => 'decimal:4',
        'fx_rate_at_creation' => 'decimal:6',
        'is_discounted' => 'boolean',
        'is_capitalised' => 'boolean',
        'expected_settlement_date' => 'date',
        'depreciation_start_date' => 'date',
        'computation_assumptions' => 'array', // JSON to array
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Encoded ID accessor for URLs
     */
    public function getEncodedIdAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'expense_account_id');
    }

    public function provisionAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'provision_account_id');
    }

    public function unwindingAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'unwinding_account_id');
    }

    public function discountRate(): BelongsTo
    {
        return $this->belongsTo(DiscountRate::class, 'discount_rate_id');
    }

    public function relatedAsset(): BelongsTo
    {
        // Note: This assumes an 'assets' table exists. Adjust foreign key if different.
        if (class_exists(\App\Models\Asset::class)) {
            return $this->belongsTo(\App\Models\Asset::class, 'related_asset_id');
        }
        // Return a dummy relationship if assets table doesn't exist yet
        return $this->belongsTo(ChartAccount::class, 'related_asset_id');
    }

    public function submittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(ProvisionMovement::class);
    }

    public function contingencies(): HasMany
    {
        return $this->hasMany(Contingency::class);
    }

    /**
     * Scopes
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Helpers
     */
    public function isRecognised(): bool
    {
        return (bool) $this->is_recognised;
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'pending_approval', 'rejected'], true);
    }

    public function canBeSubmitted(): bool
    {
        return in_array($this->status, ['draft', 'rejected'], true);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'pending_approval'], true);
    }

    public function isSettled(): bool
    {
        return $this->status === 'settled';
    }
}


