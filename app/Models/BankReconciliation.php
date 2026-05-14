<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class BankReconciliation extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'bank_account_id',
        'user_id',
        'branch_id',
        'reconciliation_date',
        'start_date',
        'end_date',
        'bank_statement_balance',
        'book_balance',
        'adjusted_bank_balance',
        'adjusted_book_balance',
        'difference',
        'status',
        'notes',
        'bank_statement_document',
        'current_approval_level',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'adjustments_posted_at',
        'adjustments_posted_by',
    ];

    // Use Hashids for route model binding like other models
    public function getRouteKey()
    {
        return \App\Helpers\HashIdHelper::encode($this->id);
    }

    /**
     * Resolve the model from the route parameter.
     *
     * @param string $value
     * @param string|null $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // Try to decode the hash ID first
        $decoded = \App\Helpers\HashIdHelper::decode($value);
        
        if ($decoded !== null) {
            return static::where('id', $decoded)->first();
        }
        
        // Fallback to regular ID lookup
        return static::where('id', $value)->first();
    }

    protected $casts = [
        'reconciliation_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'bank_statement_balance' => 'decimal:2',
        'book_balance' => 'decimal:2',
        'adjusted_bank_balance' => 'decimal:2',
        'adjusted_book_balance' => 'decimal:2',
        'difference' => 'decimal:2',
        'current_approval_level' => 'integer',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'adjustments_posted_at' => 'datetime',
    ];

    // Relationships
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the company ID for this reconciliation
     * BankReconciliation doesn't have direct company_id, gets it through bankAccount->chartAccount->accountClassGroup
     */
    public function getCompanyIdAttribute()
    {
        return $this->bankAccount?->chartAccount?->accountClassGroup?->company_id;
    }

    /**
     * Get the company for this reconciliation
     */
    public function company()
    {
        $companyId = $this->company_id;
        return $companyId ? Company::find($companyId) : null;
    }

    public function reconciliationItems(): HasMany
    {
        return $this->hasMany(BankReconciliationItem::class);
    }

    /**
     * Get the approval history for this bank reconciliation.
     */
    public function approvalHistories(): MorphMany
    {
        return $this->morphMany(ApprovalHistory::class, 'approvable');
    }

    /**
     * Get the user who submitted this reconciliation for approval.
     */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Get the user who approved this reconciliation.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who rejected this reconciliation.
     */
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Get the user who posted adjustments for this reconciliation.
     */
    public function adjustmentsPostedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjustments_posted_by');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByBankAccount($query, $bankAccountId)
    {
        return $query->where('bank_account_id', $bankAccountId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('reconciliation_date', [$startDate, $endDate]);
    }

    // Accessors
    public function getFormattedReconciliationDateAttribute()
    {
        return $this->reconciliation_date ? $this->reconciliation_date->format('M d, Y') : 'N/A';
    }

    public function getFormattedStartDateAttribute()
    {
        return $this->start_date ? $this->start_date->format('M d, Y') : 'N/A';
    }

    public function getFormattedEndDateAttribute()
    {
        return $this->end_date ? $this->end_date->format('M d, Y') : 'N/A';
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'draft' => '<span class="badge bg-secondary">Draft</span>',
            'pending_approval' => '<span class="badge bg-warning">Pending Approval</span>',
            'approved' => '<span class="badge bg-info">Approved</span>',
            'rejected' => '<span class="badge bg-danger">Rejected</span>',
            'in_progress' => '<span class="badge bg-warning">In Progress</span>',
            'completed' => '<span class="badge bg-success">Completed</span>',
            'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
        ];

        return $badges[$this->status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    public function getFormattedBankStatementBalanceAttribute()
    {
        return number_format($this->bank_statement_balance, 2);
    }

    public function getFormattedBookBalanceAttribute()
    {
        return number_format($this->book_balance, 2);
    }

    public function getFormattedDifferenceAttribute()
    {
        return number_format($this->difference, 2);
    }

    public function getFormattedAdjustedBankBalanceAttribute()
    {
        return number_format($this->adjusted_bank_balance, 2);
    }

    public function getFormattedAdjustedBookBalanceAttribute()
    {
        return number_format($this->adjusted_book_balance, 2);
    }

    // Methods
    public function calculateBookBalance()
    {
        // Match cash book report logic exactly:
        // 1. Get opening balance (all transactions before start date)
        // 2. Get period transactions (transactions from start_date to end_date inclusive)
        // 3. Final balance = opening balance + (period debits - period credits)
        
        // IMPORTANT: The cash book report uses DB::table() queries with the same date/time format
        // We need to match this exactly to ensure the same results
        
        $startDateWithTime = $this->start_date . ' 00:00:00';
        $endDateWithTime = $this->end_date . ' 23:59:59';
        
        // Use DB::table() to match cash book query structure exactly
        $openingBalanceQuery = \Illuminate\Support\Facades\DB::table('gl_transactions')
            ->where('chart_account_id', $this->bankAccount->chart_account_id)
            ->where('date', '<', $startDateWithTime);
        
        // Apply branch filter - match cash book logic exactly
        // Cash book: if branchId is set, filter by that branch; if null, include all branches
        // For bank reconciliation, if branch_id is set, filter by that branch
        // If branch_id is null, include all branches (no filter) - matching cash book when branchId is null
        if ($this->branch_id) {
            $openingBalanceQuery->where('branch_id', $this->branch_id);
        }
        // If branch_id is null, don't add any branch filter (include all branches)
        
        // Calculate opening balance using same SQL as cash book
        $openingBalance = $openingBalanceQuery->selectRaw('
            SUM(CASE WHEN nature = "debit" THEN amount ELSE 0 END) -
            SUM(CASE WHEN nature = "credit" THEN amount ELSE 0 END) as opening_balance
        ')->value('opening_balance') ?? 0;

        // Get period transactions using DB::table() to match cash book
        $periodTransactionsQuery = \Illuminate\Support\Facades\DB::table('gl_transactions')
            ->where('chart_account_id', $this->bankAccount->chart_account_id)
            ->where('date', '>=', $startDateWithTime)
            ->where('date', '<=', $endDateWithTime);
        
        // Apply branch filter - match cash book logic exactly
        if ($this->branch_id) {
            $periodTransactionsQuery->where('branch_id', $this->branch_id);
        }
        // If branch_id is null, don't add any branch filter (include all branches)
        
        // Calculate period totals using same SQL as cash book
        $periodTotals = $periodTransactionsQuery->selectRaw('
            SUM(CASE WHEN nature = "debit" THEN amount ELSE 0 END) as total_debits,
            SUM(CASE WHEN nature = "credit" THEN amount ELSE 0 END) as total_credits
        ')->first();
        
        $periodDebits = $periodTotals->total_debits ?? 0;
        $periodCredits = $periodTotals->total_credits ?? 0;
        $periodNetChange = $periodDebits - $periodCredits;

        // Book balance = Opening balance + Period net change (closing balance)
        // This matches the cash book report calculation exactly:
        // Final Balance = Opening Balance + (Total Receipts - Total Payments)
        $this->book_balance = $openingBalance + $periodNetChange;
        $this->save();

        return $this->book_balance;
    }

    public function calculateDifference()
    {
        $this->difference = $this->adjusted_bank_balance - $this->adjusted_book_balance;
        $this->save();

        return $this->difference;
    }

    public function isBalanced()
    {
        return abs($this->difference) < 0.01; // Allow for small rounding differences
    }

    public function getUnreconciledItems()
    {
        return $this->reconciliationItems()->where('is_reconciled', false)->get();
    }

    public function getReconciledItems()
    {
        return $this->reconciliationItems()->where('is_reconciled', true)->get();
    }

    /**
     * Get summary of unreconciled items
     */
    public function getUnreconciledSummary()
    {
        $unreconciledBankItems = $this->reconciliationItems()
            ->where('is_bank_statement_item', true)
            ->where('is_reconciled', false)
            ->get();

        $unreconciledBookItems = $this->reconciliationItems()
            ->where('is_book_entry', true)
            ->where('is_reconciled', false)
            ->get();

        return [
            'bank_items_count' => $unreconciledBankItems->count(),
            'bank_items_total' => $unreconciledBankItems->sum('amount'),
            'book_items_count' => $unreconciledBookItems->count(),
            'book_items_total' => $unreconciledBookItems->sum('amount'),
            'total_unreconciled' => $unreconciledBankItems->count() + $unreconciledBookItems->count(),
        ];
    }

    /**
     * Recalculate adjusted balances based on reconciliation items
     */
    public function recalculateAdjustedBalances()
    {
        // Calculate adjusted bank balance
        // Formula: Bank statement closing + Deposits in transit (DNC) - Outstanding checks (UPC)
        $dncItems = $this->reconciliationItems()
            ->where('item_type', 'DNC')
            ->where('uncleared_status', 'UNCLEARED')
            ->get();
        
        $upcItems = $this->reconciliationItems()
            ->where('item_type', 'UPC')
            ->where('uncleared_status', 'UNCLEARED')
            ->get();
        
        $totalDNC = $dncItems->sum('amount');
        $totalUPC = $upcItems->sum('amount');
        
        $adjustedBankBalance = $this->bank_statement_balance + $totalDNC - $totalUPC;

        // Calculate adjusted book balance
        // Note: book_balance already includes ALL GL transactions in the period
        // Adjusted book balance should account for items that appear in bank statement but not yet in books
        // (like bank fees, interest, etc. that are in bank items but not in book entries)
        
        // Get bank statement items that don't have matching book entries (items in bank but not in books)
        // These are items that appear on bank statement but haven't been recorded in GL yet
        $bankItemsWithoutBookMatch = $this->reconciliationItems()
            ->where('is_bank_statement_item', true)
            ->where('is_reconciled', false)
            ->where(function($query) {
                $query->whereNull('matched_with_item_id')
                      ->orWhereDoesntHave('matchedWithItem', function($q) {
                          $q->where('is_book_entry', true);
                      });
            })
            ->get();

        $bankOnlyDebits = $bankItemsWithoutBookMatch->where('nature', 'debit')->sum('amount');
        $bankOnlyCredits = $bankItemsWithoutBookMatch->where('nature', 'credit')->sum('amount');
        
        // Adjusted book balance = book balance + items that appear in bank but not in books
        // For bank accounts: debits increase balance, credits decrease balance
        $adjustedBookBalance = $this->book_balance + $bankOnlyDebits - $bankOnlyCredits;

        $this->update([
            'adjusted_bank_balance' => $adjustedBankBalance,
            'adjusted_book_balance' => $adjustedBookBalance,
        ]);

        $this->calculateDifference();
    }
}
