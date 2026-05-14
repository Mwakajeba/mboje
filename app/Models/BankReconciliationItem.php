<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliationItem extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'bank_reconciliation_id',
        'gl_transaction_id',
        'transaction_type',
        'item_type', // DNC, UPC, BANK_ERROR
        'reference',
        'description',
        'transaction_date',
        'amount',
        'nature',
        'is_reconciled',
        'is_bank_statement_item',
        'is_book_entry',
        'matched_with_item_id',
        'reconciled_at',
        'reconciled_by',
        'notes',
        // Uncleared items tracking
        'origin_date',
        'origin_month',
        'origin_reconciliation_id',
        'age_days',
        'age_months',
        'uncleared_status', // UNCLEARED, CLEARED, MANUALLY_CLOSED
        'clearing_date',
        'clearing_month',
        'cleared_by',
        'clearing_reference',
        'manual_close_reason',
        'manual_closed_by',
        'manual_closed_at',
        'is_brought_forward',
        'brought_forward_from_item_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'origin_date' => 'date',
        'origin_month' => 'date',
        'clearing_date' => 'date',
        'clearing_month' => 'date',
        'amount' => 'decimal:2',
        'age_months' => 'decimal:2',
        'is_reconciled' => 'boolean',
        'is_bank_statement_item' => 'boolean',
        'is_book_entry' => 'boolean',
        'is_brought_forward' => 'boolean',
        'reconciled_at' => 'datetime',
        'manual_closed_at' => 'datetime',
    ];

    // Relationships
    public function bankReconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class);
    }

    public function glTransaction(): BelongsTo
    {
        return $this->belongsTo(GlTransaction::class);
    }

    public function matchedWithItem(): BelongsTo
    {
        return $this->belongsTo(BankReconciliationItem::class, 'matched_with_item_id');
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function originReconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'origin_reconciliation_id');
    }

    public function clearedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cleared_by');
    }

    public function manualClosedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manual_closed_by');
    }

    public function broughtForwardFromItem(): BelongsTo
    {
        return $this->belongsTo(BankReconciliationItem::class, 'brought_forward_from_item_id');
    }

    // Scopes
    public function scopeReconciled($query)
    {
        return $query->where('is_reconciled', true);
    }

    public function scopeUnreconciled($query)
    {
        return $query->where('is_reconciled', false);
    }

    public function scopeBankStatementItems($query)
    {
        return $query->where('is_bank_statement_item', true);
    }

    public function scopeBookEntries($query)
    {
        return $query->where('is_book_entry', true);
    }

    public function scopeByTransactionType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeUncleared($query)
    {
        return $query->where('uncleared_status', 'UNCLEARED');
    }

    public function scopeCleared($query)
    {
        return $query->where('uncleared_status', 'CLEARED');
    }

    public function scopeManuallyClosed($query)
    {
        return $query->where('uncleared_status', 'MANUALLY_CLOSED');
    }

    public function scopeByItemType($query, $type)
    {
        return $query->where('item_type', $type);
    }

    public function scopeBroughtForward($query)
    {
        return $query->where('is_brought_forward', true);
    }

    public function scopeByOriginMonth($query, $month)
    {
        return $query->where('origin_month', $month);
    }

    // Accessors
    public function getFormattedTransactionDateAttribute()
    {
        return $this->transaction_date ? $this->transaction_date->format('M d, Y') : 'N/A';
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2);
    }

    public function getNatureBadgeAttribute()
    {
        $badges = [
            'debit' => '<span class="badge bg-danger">Debit</span>',
            'credit' => '<span class="badge bg-success">Credit</span>',
        ];

        return $badges[$this->nature] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    public function getReconciliationStatusBadgeAttribute()
    {
        if ($this->is_reconciled) {
            return '<span class="badge bg-success">Reconciled</span>';
        }
        return '<span class="badge bg-warning">Unreconciled</span>';
    }

    public function getTransactionTypeBadgeAttribute()
    {
        $badges = [
            'bank_statement' => '<span class="badge bg-info">Bank Statement</span>',
            'book_entry' => '<span class="badge bg-primary">Book Entry</span>',
            'adjustment' => '<span class="badge bg-warning">Adjustment</span>',
        ];

        return $badges[$this->transaction_type] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    // Methods
    public function markAsReconciled($userId = null, $reconciledDate = null, $bankReference = null)
    {
        $this->update([
            'is_reconciled' => true,
            'reconciled_at' => $reconciledDate ?? now(),
            'reconciled_by' => $userId ?? auth()->id(),
            'clearing_reference' => $bankReference ?? $this->clearing_reference,
            'clearing_date' => $reconciledDate ?? $this->clearing_date ?? now(),
        ]);
        
        // If this is an uncleared item, mark it as cleared
        if ($this->uncleared_status === 'UNCLEARED') {
            $this->markAsCleared($reconciledDate ?? now(), $bankReference, $userId ?? auth()->id());
        }
    }

    public function markAsUnreconciled()
    {
        $this->update([
            'is_reconciled' => false,
            'matched_with_item_id' => null,
            'reconciled_at' => null,
            'reconciled_by' => null,
        ]);
    }

    public function matchWith($itemId)
    {
        $this->update([
            'matched_with_item_id' => $itemId,
            'is_reconciled' => true,
            'reconciled_at' => now(),
            'reconciled_by' => auth()->id(),
        ]);
    }

    public function getMatchedItem()
    {
        return $this->matchedWithItem;
    }

    public function isMatched()
    {
        return !is_null($this->matched_with_item_id);
    }

    /**
     * Calculate and update aging fields
     */
    public function calculateAging()
    {
        // Use transaction_date (the date shown on the left) for age calculation
        // Fall back to origin_date if transaction_date is not available
        $transactionDate = $this->transaction_date ?? $this->origin_date;
        
        if (!$transactionDate) {
            return;
        }

        $now = now();
        
        // Calculate age: today's date minus transaction date (days since transaction)
        // Use startOfDay to ensure accurate day calculation
        $transDate = \Carbon\Carbon::parse($transactionDate)->copy()->startOfDay();
        $today = $now->copy()->startOfDay();
        
        // Calculate days: today - transaction_date (positive if transaction is in the past)
        if ($transDate->isFuture()) {
            $ageDays = 0;
        } else {
            // Calculate: today - transaction_date
            // diffInDays($date, false) returns difference from $this to $date
            // So $today->diffInDays($transDate, false) gives: today - transaction_date
            $ageDays = $today->diffInDays($transDate, false);
            // Ensure positive value (if transaction is in the past, age should be positive)
            if ($ageDays < 0) {
                $ageDays = abs($ageDays); // Take absolute value if negative
            }
        }
        
        // Round to whole number (no decimals)
        $ageDays = (int) round($ageDays);
        
        // Calculate months: use diffInMonths for whole months, then add fractional part
        if ($transDate->isFuture()) {
            $ageMonths = 0;
        } else {
            // Calculate: today - transaction_date in months
            $ageMonths = $today->diffInMonths($transDate, false);
            if ($ageMonths < 0) {
                $ageMonths = abs($ageMonths); // Take absolute value if negative
            }
            
                // Calculate remaining days after whole months
            $dateAfterMonths = $transDate->copy()->addMonths((int)$ageMonths);
                $remainingDays = $today->diffInDays($dateAfterMonths, false);
                if ($remainingDays > 0) {
                    $ageMonths = $ageMonths + ($remainingDays / 30);
            }
        }
        
        // Round months to 2 decimal places
        $ageMonths = round($ageMonths, 2);

        $this->update([
            'age_days' => $ageDays,
            'age_months' => $ageMonths,
        ]);

        return [
            'days' => $ageDays,
            'months' => $ageMonths,
        ];
    }

    /**
     * Get aging flag color based on days outstanding
     */
    public function getAgingFlagColor()
    {
        $days = $this->age_days ?? 0;
        
        if ($days >= 180) {
            return 'danger'; // Critical Alert
        } elseif ($days >= 90) {
            return 'danger'; // Red Flag
        } elseif ($days >= 60) {
            return 'warning'; // Orange
        } elseif ($days >= 30) {
            return 'warning'; // Yellow warning
        }
        
        return 'info'; // Normal
    }

    /**
     * Get aging flag badge HTML
     */
    public function getAgingFlagBadgeAttribute()
    {
        $color = $this->getAgingFlagColor();
        $days = $this->age_days ?? 0;
        $months = $this->age_months ?? 0;
        
        $label = $days . ' days';
        if ($months >= 1) {
            $label .= ' (' . number_format($months, 1) . ' months)';
        }
        
        return '<span class="badge bg-' . $color . '">' . $label . '</span>';
    }

    /**
     * Mark item as cleared
     */
    public function markAsCleared($clearingDate = null, $clearingReference = null, $userId = null)
    {
        $this->update([
            'uncleared_status' => 'CLEARED',
            'clearing_date' => $clearingDate ?? now(),
            'clearing_month' => now()->startOfMonth(),
            'cleared_by' => $userId ?? auth()->id(),
            'clearing_reference' => $clearingReference,
            'is_reconciled' => true,
            'reconciled_at' => now(),
            'reconciled_by' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Manually close uncleared item
     */
    public function manuallyClose($reason, $userId = null)
    {
        $this->update([
            'uncleared_status' => 'MANUALLY_CLOSED',
            'manual_close_reason' => $reason,
            'manual_closed_by' => $userId ?? auth()->id(),
            'manual_closed_at' => now(),
        ]);
    }

    /**
     * Determine item type based on nature and book entry status
     */
    public function determineItemType()
    {
        // DNC (Deposits Not Credited) = Receipts = Debit in books but not in bank (money coming in)
        // UPC (Unpresented Cheques) = Payments = Credit in books but not in bank (money going out)
        if ($this->is_book_entry && !$this->is_bank_statement_item) {
            if ($this->nature === 'debit') {
                return 'DNC'; // Receipts (money coming in)
            } elseif ($this->nature === 'credit') {
                return 'UPC'; // Payments (money going out)
            }
        }
        
        return null;
    }
}
