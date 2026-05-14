<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vinkla\Hashids\Facades\Hashids;

class FeeGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_code',
        'name',
        'receivable_account_id',
        'income_account_id',
        'transport_income_account_id',
        'discount_account_id',
        'opening_balance_account_id',
        'description',
        'is_active',
        'company_id',
        'branch_id',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the receivable account for this fee group.
     */
    public function receivableAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'receivable_account_id');
    }

    /**
     * Get the income account for this fee group.
     */
    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'income_account_id');
    }

    /**
     * Get the transport income account for this fee group.
     */
    public function transportIncomeAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'transport_income_account_id');
    }

    /**
     * Get the discount account for this fee group.
     */
    public function discountAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'discount_account_id');
    }

    /**
     * Get the opening balance account for this fee group.
     */
    public function openingBalanceAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'opening_balance_account_id');
    }

    /**
     * Get the company that owns the fee group.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the branch that owns the fee group.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who created the fee group.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to filter by company.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to filter by branch.
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where(function ($query) use ($branchId) {
            $query->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
        });
    }

    /**
     * Scope to filter by user (company and branch).
     */
    public function scopeForUser($query)
    {
        $user = auth()->user();
        return $query->where('company_id', $user->company_id)
                    ->where(function ($query) use ($user) {
                        $query->where('branch_id', $user->branch_id)
                              ->orWhereNull('branch_id');
                    });
    }

    /**
     * Scope to filter active fee groups.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKey()
    {
        return Hashids::encode($this->getKey());
    }

    /**
     * Get the route key name for route model binding.
     */
    public function getRouteKeyName()
    {
        return 'hashid';
    }

    /**
     * Retrieve the model for a bound value.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $decoded = Hashids::decode($value);
        $id = $decoded[0] ?? null;

        return $this->where('id', $id)->firstOrFail();
    }

    /**
     * Get the hashid attribute.
     */
    public function getHashidAttribute()
    {
        return Hashids::encode($this->getKey());
    }

    /**
     * Find by hashid.
     */
    public static function findByHashid($hashid)
    {
        $decoded = Hashids::decode($hashid);
        return self::where('id', $decoded[0] ?? null)->first();
    }
}
