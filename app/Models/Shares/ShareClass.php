<?php

namespace App\Models\Shares;

use App\Models\Company;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class ShareClass extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'share_classes';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'has_par_value',
        'par_value',
        'currency_code',
        'share_type',
        'voting_rights',
        'dividend_policy',
        'redeemable',
        'convertible',
        'cumulative',
        'participating',
        'classification',
        'authorized_shares',
        'authorized_value',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'has_par_value' => 'boolean',
        'par_value' => 'decimal:6',
        'redeemable' => 'boolean',
        'convertible' => 'boolean',
        'cumulative' => 'boolean',
        'participating' => 'boolean',
        'is_active' => 'boolean',
        'authorized_shares' => 'integer',
        'authorized_value' => 'decimal:2',
    ];

    /**
     * Get the encoded ID attribute.
     */
    public function getEncodedIdAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    /**
     * Get the company that owns this share class.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created this share class.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this share class.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get all share issues for this class.
     */
    public function shareIssues(): HasMany
    {
        return $this->hasMany(ShareIssue::class);
    }

    /**
     * Get all share holdings for this class.
     */
    public function shareHoldings(): HasMany
    {
        return $this->hasMany(ShareHolding::class);
    }

    /**
     * Get all corporate actions for this class.
     */
    public function corporateActions(): HasMany
    {
        return $this->hasMany(ShareCorporateAction::class);
    }

    /**
     * Get all dividends for this class.
     */
    public function dividends(): HasMany
    {
        return $this->hasMany(ShareDividend::class);
    }

    /**
     * Calculate total issued shares for this class.
     */
    public function getTotalIssuedSharesAttribute(): int
    {
        return $this->shareHoldings()
            ->where('status', 'active')
            ->sum('shares_outstanding') ?? 0;
    }

    /**
     * Calculate total outstanding shares (excluding treasury).
     */
    public function getTotalOutstandingSharesAttribute(): int
    {
        return $this->shareHoldings()
            ->where('status', 'active')
            ->sum('shares_outstanding') ?? 0;
    }

    /**
     * Scope: Active share classes only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

