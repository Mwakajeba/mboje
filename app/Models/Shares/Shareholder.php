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

class Shareholder extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'shareholders';

    // Primary key may be 'id' or 'shareholder_id' depending on database schema
    protected $primaryKey = 'shareholder_id';

    public $incrementing = true;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'type',
        'email',
        'phone',
        'country',
        'tax_id',
        'address',
        'is_related_party',
        'related_party_notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_related_party' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the encoded ID attribute.
     */
    public function getEncodedIdAttribute(): string
    {
        // Always encode the actual primary key value
        return Hashids::encode($this->getKey());
    }

    /**
     * Get the company that owns this shareholder record.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created this shareholder.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this shareholder.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get all share holdings for this shareholder.
     */
    public function shareHoldings(): HasMany
    {
        return $this->hasMany(ShareHolding::class, 'shareholder_id', 'shareholder_id');
    }

    /**
     * Get all dividend payments for this shareholder.
     */
    public function dividendPayments(): HasMany
    {
        return $this->hasMany(ShareDividendPayment::class, 'shareholder_id', 'shareholder_id');
    }

    /**
     * Calculate total shares held across all classes.
     */
    public function getTotalSharesHeldAttribute(): int
    {
        return $this->shareHoldings()
            ->where('status', 'active')
            ->sum('shares_outstanding') ?? 0;
    }

    /**
     * Get shareholding percentage for a specific share class.
     */
    public function getShareholdingPercentage(ShareClass $shareClass): float
    {
        $totalOutstanding = $shareClass->total_outstanding_shares;
        if ($totalOutstanding == 0) {
            return 0;
        }

        $shareholderHolding = $this->shareHoldings()
            ->where('share_class_id', $shareClass->id)
            ->where('status', 'active')
            ->sum('shares_outstanding') ?? 0;

        return ($shareholderHolding / $totalOutstanding) * 100;
    }

    /**
     * Scope: Active shareholders only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

