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

class ShareDividendPayment extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'share_dividend_payments';

    protected $fillable = [
        'company_id',
        'dividend_id',
        'shareholder_id',
        'gross_amount',
        'withholding_tax_amount',
        'net_amount',
        'payment_date',
        'payment_reference',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'withholding_tax_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /**
     * Get the encoded ID attribute.
     */
    public function getEncodedIdAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    /**
     * Get the company that owns this payment.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the dividend for this payment.
     */
    public function dividend(): BelongsTo
    {
        return $this->belongsTo(ShareDividend::class, 'dividend_id');
    }

    /**
     * Get the shareholder receiving this payment.
     */
    public function shareholder(): BelongsTo
    {
        return $this->belongsTo(Shareholder::class, 'shareholder_id', 'shareholder_id');
    }

    /**
     * Get the user who created this payment.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this payment.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if this payment has been paid.
     */
    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Scope: Paid payments only.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope: Pending payments only.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}

