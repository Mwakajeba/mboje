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

class ShareDividend extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'share_dividends';

    protected $fillable = [
        'company_id',
        'share_class_id',
        'corporate_action_id',
        'dividend_type',
        'declaration_date',
        'record_date',
        'ex_date',
        'payment_date',
        'per_share_amount',
        'total_amount',
        'currency_code',
        'status',
        'description',
        'created_by',
        'approved_by',
        'updated_by',
    ];

    protected $casts = [
        'declaration_date' => 'date',
        'record_date' => 'date',
        'ex_date' => 'date',
        'payment_date' => 'date',
        'per_share_amount' => 'decimal:6',
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
     * Get the company that owns this dividend.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the share class for this dividend.
     */
    public function shareClass(): BelongsTo
    {
        return $this->belongsTo(ShareClass::class);
    }

    /**
     * Get the corporate action linked to this dividend.
     */
    public function corporateAction(): BelongsTo
    {
        return $this->belongsTo(ShareCorporateAction::class, 'corporate_action_id');
    }

    /**
     * Get the user who created this dividend.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved this dividend.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get all dividend payments for this dividend.
     */
    public function dividendPayments(): HasMany
    {
        return $this->hasMany(ShareDividendPayment::class, 'dividend_id');
    }

    /**
     * Calculate total paid amount.
     */
    public function getTotalPaidAttribute(): float
    {
        return $this->dividendPayments()
            ->where('status', 'paid')
            ->sum('net_amount') ?? 0;
    }

    /**
     * Calculate total pending amount.
     */
    public function getTotalPendingAttribute(): float
    {
        return $this->dividendPayments()
            ->where('status', 'pending')
            ->sum('net_amount') ?? 0;
    }

    /**
     * Scope: Declared dividends only.
     */
    public function scopeDeclared($query)
    {
        return $query->where('status', 'declared');
    }
}

