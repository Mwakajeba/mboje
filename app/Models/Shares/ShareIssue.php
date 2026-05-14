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

class ShareIssue extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'share_issues';

    protected $fillable = [
        'company_id',
        'share_class_id',
        'issue_type',
        'reference_number',
        'issue_date',
        'record_date',
        'settlement_date',
        'price_per_share',
        'par_value',
        'total_shares',
        'total_amount',
        'status',
        'description',
        'created_by',
        'approved_by',
        'posted_by',
        'updated_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'record_date' => 'date',
        'settlement_date' => 'date',
        'price_per_share' => 'decimal:6',
        'par_value' => 'decimal:6',
        'total_shares' => 'integer',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the encoded ID attribute.
     */
    public function getEncodedIdAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    /**
     * Get the company that owns this share issue.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the share class for this issue.
     */
    public function shareClass(): BelongsTo
    {
        return $this->belongsTo(ShareClass::class);
    }

    /**
     * Get the user who created this issue.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved this issue.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who posted this issue.
     */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * Get all share holdings created from this issue.
     */
    public function shareHoldings(): HasMany
    {
        return $this->hasMany(ShareHolding::class);
    }

    /**
     * Check if this issue has been posted to GL.
     */
    public function getIsPostedAttribute(): bool
    {
        return $this->status === 'posted';
    }

    /**
     * Scope: Posted issues only.
     */
    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    /**
     * Scope: Approved issues only.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}

