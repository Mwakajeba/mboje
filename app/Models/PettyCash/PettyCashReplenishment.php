<?php

namespace App\Models\PettyCash;

use App\Models\BankAccount;
use App\Models\Journal;
use App\Models\Payment;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class PettyCashReplenishment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'petty_cash_unit_id',
        'replenishment_number',
        'request_date',
        'requested_amount',
        'approved_amount',
        'reason',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'approval_notes',
        'rejection_reason',
        'payment_voucher_id',
        'source_account_id',
        'journal_id',
        'imprest_request_id',
    ];

    protected $casts = [
        'request_date' => 'date',
        'requested_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function pettyCashUnit(): BelongsTo
    {
        return $this->belongsTo(PettyCashUnit::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paymentVoucher(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_voucher_id');
    }

    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'source_account_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function imprestRequest(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ImprestRequest::class);
    }

    // Accessors
    public function getEncodedIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    // Scopes
    public function scopePendingApproval($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Helper methods
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'submitted';
    }
}

