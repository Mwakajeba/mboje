<?php

namespace App\Models\Shares;

use App\Models\Company;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class ShareHolding extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'share_holdings';

    protected $fillable = [
        'company_id',
        'shareholder_id',
        'share_class_id',
        'share_issue_id',
        'lot_number',
        'acquisition_date',
        'shares_issued',
        'shares_outstanding',
        'shares_forfeited',
        'shares_converted',
        'shares_redeemed',
        'paid_up_amount',
        'unpaid_amount',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'shares_issued' => 'integer',
        'shares_outstanding' => 'integer',
        'shares_forfeited' => 'integer',
        'shares_converted' => 'integer',
        'shares_redeemed' => 'integer',
        'paid_up_amount' => 'decimal:2',
        'unpaid_amount' => 'decimal:2',
    ];

    /**
     * Get the encoded ID attribute.
     */
    public function getEncodedIdAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    /**
     * Get the company that owns this holding.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the shareholder who owns this holding.
     */
    public function shareholder(): BelongsTo
    {
        return $this->belongsTo(Shareholder::class, 'shareholder_id', 'shareholder_id');
    }

    /**
     * Get the share class for this holding.
     */
    public function shareClass(): BelongsTo
    {
        return $this->belongsTo(ShareClass::class);
    }

    /**
     * Get the share issue that created this holding.
     */
    public function shareIssue(): BelongsTo
    {
        return $this->belongsTo(ShareIssue::class);
    }

    /**
     * Get the user who created this holding.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this holding.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if this holding is fully paid.
     */
    public function getIsFullyPaidAttribute(): bool
    {
        return $this->unpaid_amount == 0;
    }

    /**
     * Scope: Active holdings only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

