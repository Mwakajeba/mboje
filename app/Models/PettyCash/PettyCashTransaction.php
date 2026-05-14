<?php

namespace App\Models\PettyCash;

use App\Models\Journal;
use App\Models\Payment;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class PettyCashTransaction extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'petty_cash_unit_id',
        'transaction_number',
        'transaction_date',
        'expense_category_id',
        'amount',
        'payee',
        'payee_type',
        'customer_id',
        'supplier_id',
        'employee_id',
        'description',
        'notes',
        'receipt_attachment',
        'receipt_status',
        'receipt_verified_by',
        'receipt_verified_at',
        'receipt_verification_notes',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'disbursed_by',
        'disbursed_at',
        'rejection_reason',
        'journal_id',
        'payment_id',
        'balance_after',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'approved_at' => 'datetime',
        'disbursed_at' => 'datetime',
        'receipt_verified_at' => 'datetime',
    ];

    // Relationships
    public function pettyCashUnit(): BelongsTo
    {
        return $this->belongsTo(PettyCashUnit::class);
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(PettyCashExpenseCategory::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PettyCashTransactionItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Supplier::class);
    }

    public function receiptVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receipt_verified_by');
    }

    public function disbursedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by');
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

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    // Helper methods
    public function canBeEdited(): bool
    {
        // Can edit if:
        // 1. Status is draft or rejected, OR
        // 2. Transaction hasn't been posted to GL yet (no payment_id)
        return in_array($this->status, ['draft', 'rejected']) || !$this->payment_id;
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'submitted';
    }

    public function canBeRejected(): bool
    {
        return $this->status === 'submitted';
    }

    public function canBePosted(): bool
    {
        return $this->status === 'approved' && !$this->payment_id;
    }

    public function canBeDeleted(): bool
    {
        // Can delete if not in posted status (posted status means it's finalized)
        // Even if payment_id exists, we can delete it (will delete GL transactions too)
        return $this->status !== 'posted';
    }
}

