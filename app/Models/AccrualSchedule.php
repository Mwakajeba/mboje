<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class AccrualSchedule extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'accrual_schedules';

    protected $fillable = [
        'schedule_number',
        'schedule_type', // prepayment, accrual
        'nature', // expense, income
        'start_date',
        'end_date',
        'total_amount',
        'amortised_amount',
        'remaining_amount',
        'expense_income_account_id',
        'balance_sheet_account_id',
        'frequency', // monthly, quarterly, custom
        'custom_periods',
        'vendor_id',
        'customer_id',
        'currency_code',
        'payment_method',
        'bank_account_id',
        'payment_date',
        'initial_journal_id',
        'fx_rate_at_creation',
        'home_currency_amount',
        'description',
        'notes',
        'prepared_by',
        'approved_by',
        'approved_at',
        'status', // draft, submitted, approved, active, completed, cancelled
        'approval_round',
        'company_id',
        'branch_id',
        'created_by',
        'updated_by',
        'last_posted_period',
        'is_locked',
        'attachment_path',
        'matched_invoice_number',
        'matched_invoice_date',
        'matched_invoice_amount',
        'settlement_status',
        'settled_at',
        'settled_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'payment_date' => 'date',
        'approved_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'amortised_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'fx_rate_at_creation' => 'decimal:6',
        'home_currency_amount' => 'decimal:2',
        'is_locked' => 'boolean',
        'last_posted_period' => 'date',
        'matched_invoice_date' => 'date',
        'matched_invoice_amount' => 'decimal:2',
        'approval_round' => 'integer',
        'settled_at' => 'datetime',
    ];

    /**
     * Get the encoded ID for URLs
     */
    public function getEncodedIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function expenseIncomeAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'expense_income_account_id');
    }

    public function balanceSheetAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'balance_sheet_account_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'vendor_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function settledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function initialJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'initial_journal_id');
    }

    public function journals(): HasMany
    {
        return $this->hasMany(AccrualJournal::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(AccrualApproval::class);
    }

    /**
     * Scopes
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePrepayment($query)
    {
        return $query->where('schedule_type', 'prepayment');
    }

    public function scopeAccrual($query)
    {
        return $query->where('schedule_type', 'accrual');
    }

    public function scopeExpense($query)
    {
        return $query->where('nature', 'expense');
    }

    public function scopeIncome($query)
    {
        return $query->where('nature', 'income');
    }

    /**
     * Check if schedule can be edited
     */
    public function canBeEdited()
    {
        return in_array($this->status, ['draft', 'submitted']) && !$this->is_locked;
    }

    /**
     * Check if schedule can be approved
     */
    public function canBeApproved()
    {
        return $this->status === 'submitted';
    }

    /**
     * Check if schedule can be cancelled
     */
    public function canBeCancelled()
    {
        // Only allow cancellation/deletion for draft and submitted schedules
        // Approved and active schedules cannot be deleted
        return in_array($this->status, ['draft', 'submitted']);
    }

    /**
     * Get the category name for display
     */
    public function getCategoryNameAttribute()
    {
        if ($this->schedule_type === 'prepayment' && $this->nature === 'expense') {
            return 'Prepaid Expense';
        } elseif ($this->schedule_type === 'accrual' && $this->nature === 'expense') {
            return 'Accrued Expense';
        } elseif ($this->schedule_type === 'prepayment' && $this->nature === 'income') {
            return 'Deferred Income';
        } elseif ($this->schedule_type === 'accrual' && $this->nature === 'income') {
            return 'Accrued Income';
        }
        return 'Unknown';
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute()
    {
        $badgeClass = match($this->status) {
            'draft' => 'bg-secondary',
            'submitted' => 'bg-info',
            'approved' => 'bg-primary',
            'active' => 'bg-success',
            'completed' => 'bg-dark',
            'cancelled' => 'bg-danger',
            default => 'bg-secondary',
        };
        return '<span class="badge ' . $badgeClass . '">' . ucfirst($this->status) . '</span>';
    }

    /**
     * Match an invoice to this accrual schedule
     * 
     * @param string $invoiceNumber Invoice number
     * @param \Carbon\Carbon $invoiceDate Invoice date
     * @param float $invoiceAmount Invoice amount
     * @param int|null $userId User ID who is matching (defaults to current user)
     * @return void
     */
    public function matchInvoice($invoiceNumber, $invoiceDate, $invoiceAmount, $userId = null)
    {
        if (!$userId) {
            $userId = \Illuminate\Support\Facades\Auth::id();
        }

        $this->matched_invoice_number = $invoiceNumber;
        $this->matched_invoice_date = $invoiceDate;
        $this->matched_invoice_amount = $invoiceAmount;
        $this->settled_at = now();
        $this->settled_by = $userId;

        // Determine settlement status based on amount comparison
        $tolerance = 0.01; // Allow small rounding differences
        $difference = abs($this->home_currency_amount - $invoiceAmount);

        if ($difference <= $tolerance) {
            $this->settlement_status = 'fully_settled';
        } else {
            $this->settlement_status = 'partially_settled';
        }

        $this->save();
    }

    /**
     * Check if this accrual is settled
     */
    public function isSettled(): bool
    {
        return $this->settlement_status === 'fully_settled';
    }

    /**
     * Check if this accrual is partially settled
     */
    public function isPartiallySettled(): bool
    {
        return $this->settlement_status === 'partially_settled';
    }
}
