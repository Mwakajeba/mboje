<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Payment;

class Bill extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'date',
        'due_date',
        'supplier_id',
        'note',
        'credit_account',
        'paid',
        'user_id',
        'branch_id',
        'company_id',
        'reference',
        'total_amount',
        'vat_mode',
        'vat_rate',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'paid' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'credit_account');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function billItems(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    public function payments(): HasMany
    {
        // Link by supplier and reference_number = bill reference, reference_type = 'Bill'
        // Note: We need to use a closure to access $this->reference for eager loading compatibility
        return $this->hasMany(Payment::class, 'supplier_id', 'supplier_id')
            ->where('reference_type', 'Bill')
            ->where(function($query) {
                $query->where('reference_number', $this->reference);
            });
    }

    public function glTransactions(): HasMany
    {
        return $this->hasMany(GlTransaction::class, 'transaction_id')
            ->where('transaction_type', 'bill');
    }

    // Scopes
    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereRaw('paid < total_amount OR paid IS NULL');
    }

    public function scopePaid($query)
    {
        return $query->whereRaw('paid >= total_amount');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now()->toDateString())
            ->whereRaw('paid < total_amount OR paid IS NULL');
    }

    // Accessors
    public function getFormattedDateAttribute()
    {
        return $this->date ? $this->date->format('Y-m-d') : null;
    }

    public function getFormattedDueDateAttribute()
    {
        return $this->due_date ? $this->due_date->format('Y-m-d') : null;
    }

    public function getFormattedTotalAmountAttribute()
    {
        return number_format($this->total_amount, 2);
    }

    public function getFormattedPaidAttribute()
    {
        return number_format($this->paid ?? 0, 2);
    }

    public function getBalanceAttribute()
    {
        return $this->total_amount - ($this->paid ?? 0);
    }

    public function getFormattedBalanceAttribute()
    {
        return number_format($this->balance, 2);
    }

    public function getStatusAttribute()
    {
        if ($this->balance <= 0) {
            return 'paid';
        } elseif ($this->due_date && $this->due_date < now()->toDateString()) {
            return 'overdue';
        } else {
            return 'pending';
        }
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'paid' => '<span class="badge bg-success">Paid</span>',
            'overdue' => '<span class="badge bg-danger">Overdue</span>',
            'pending' => '<span class="badge bg-warning">Pending</span>',
        ];

        return $badges[$this->status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    // Methods
    public function isPaid()
    {
        return $this->balance <= 0;
    }

    public function isOverdue()
    {
        return $this->due_date && $this->due_date < now()->toDateString() && $this->balance > 0;
    }

    public function isPending()
    {
        return !$this->isPaid() && !$this->isOverdue();
    }

    public function updatePaidAmount()
    {
        // Query payments directly to ensure fresh data (after deletion, this will be 0 or sum of remaining payments)
        $totalPaid = Payment::where('supplier_id', $this->supplier_id)
            ->where('reference_type', 'Bill')
            ->where('reference_number', $this->reference)
            ->sum('amount');
        
        // Ensure we have a numeric value (null becomes 0)
        $totalPaid = $totalPaid ? (float) $totalPaid : 0;
        
        // Update the paid field directly
        $this->paid = $totalPaid;
        $this->save();
        
        // Refresh the model to ensure relationships are updated
        $this->refresh();
        
        return $this->paid;
    }
}
