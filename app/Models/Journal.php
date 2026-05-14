<?php

namespace App\Models;

use App\Helpers\AmountInWords;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'date',
        'reference',
        'reference_type',
        'customer_id',
        'description',
        'attachment',
        'branch_id',
        'user_id',
        'approved',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'datetime',
        'approved_at' => 'datetime',
        'approved' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function items()
    {
        return $this->hasMany(JournalItem::class);
    }

    /**
     * Get GL transactions for this journal
     */
    public function glTransactions()
    {
        return $this->hasMany(GlTransaction::class, 'transaction_id')
            ->where('transaction_type', 'journal');
    }

    /**
     * Helper attribute: whether this journal has been posted to GL
     */
    public function getGlPostedAttribute(): bool
    {
        return $this->glTransactions()->exists();
    }

    /**
     * Get approval records for this journal
     */
    public function approvals()
    {
        return $this->hasMany(JournalEntryApproval::class, 'journal_id');
    }

    /**
     * Get the user who approved this journal
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Initialize approval workflow for this journal
     */
    public function initializeApprovalWorkflow()
    {
        $settings = JournalEntryApprovalSetting::where('company_id', $this->user->company_id)->first();
        
        if (!$settings) {
            // No approval settings configured - auto-approve all journals
            $this->update([
                'approved' => true,
                'approved_by' => $this->user_id,
                'approved_at' => now(),
            ]);
            
            // Create GL transactions for auto-approved journals
            $this->createGlTransactions();
            return;
        }

        $requiredLevel = $settings->getRequiredApprovalLevel($this->total);
        
        if ($requiredLevel === 0) {
            // Auto-approve
            $this->update([
                'approved' => true,
                'approved_by' => $this->user_id,
                'approved_at' => now(),
            ]);
            
            // Create GL transactions for auto-approved journals
            $this->createGlTransactions();
            return;
        }

        // Create approval records for each level
        for ($level = 1; $level <= $requiredLevel; $level++) {
            $approvalType = $settings->{"level{$level}_approval_type"};
            $approvers = $settings->{"level{$level}_approvers"} ?? [];

            if ($approvalType === 'role') {
                foreach ($approvers as $roleName) {
                    $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
                    if ($role) {
                        $approval = JournalEntryApproval::create([
                            'journal_id' => $this->id,
                            'approval_level' => $level,
                            'approver_type' => 'role',
                            'approver_name' => $role->name,
                            'status' => 'pending',
                        ]);
                        
                        // Send notification to all users with this role
                        $roleUsers = User::role($roleName)->where('company_id', $this->user->company_id)->get();
                        foreach ($roleUsers as $roleUser) {
                            try {
                                $roleUser->notify(new \App\Notifications\JournalEntryApprovalRequired($this, $level));
                            } catch (\Exception $e) {
                                \Log::error('Failed to send journal approval notification', [
                                    'user_id' => $roleUser->id,
                                    'journal_id' => $this->id,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                    }
                }
            } elseif ($approvalType === 'user') {
                foreach ($approvers as $userId) {
                    $userId = (int) $userId;
                    $user = User::find($userId);
                    if ($user) {
                        $approval = JournalEntryApproval::create([
                            'journal_id' => $this->id,
                            'approval_level' => $level,
                            'approver_id' => $user->id,
                            'approver_type' => 'user',
                            'approver_name' => $user->name,
                            'status' => 'pending',
                        ]);
                        
                        // Send notification to approver
                        try {
                            $user->notify(new \App\Notifications\JournalEntryApprovalRequired($this, $level));
                        } catch (\Exception $e) {
                            \Log::error('Failed to send journal approval notification', [
                                'user_id' => $user->id,
                                'journal_id' => $this->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }
        }

        // Update journal status
        $this->update([
            'approved' => false,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    /**
     * Check if journal is fully approved
     */
    public function isFullyApproved(): bool
    {
        $settings = JournalEntryApprovalSetting::where('company_id', $this->user->company_id)->first();
        
        if (!$settings) {
            return $this->approved;
        }

        $requiredLevel = $settings->getRequiredApprovalLevel($this->total);
        
        if ($requiredLevel === 0) {
            return $this->approved;
        }

        // Check if the required approval level is approved
        $requiredLevelApproved = $this->approvals()
            ->where('approval_level', $requiredLevel)
            ->where('status', 'approved')
            ->exists();
            
        return $requiredLevelApproved;
    }

    /**
     * Check if journal is rejected
     */
    public function isRejected(): bool
    {
        return $this->approvals()->rejected()->exists();
    }

    /**
     * Get current pending approval
     */
    public function currentApproval()
    {
        return $this->approvals()
            ->pending()
            ->orderBy('approval_level')
            ->first();
    }

    /**
     * Create GL transactions for this journal
     */
    public function createGlTransactions()
    {
        // Check if period is locked
        $companyId = $this->user->company_id ?? null;
        if ($companyId) {
            $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
            try {
                $periodLockService->validateTransactionDate($this->date, $companyId, 'journal entry');
            } catch (\Exception $e) {
                \Log::warning('Journal - Cannot post: Period is locked', [
                    'journal_id' => $this->id,
                    'journal_reference' => $this->reference,
                    'transaction_date' => $this->date,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        // Ensure items are loaded
        if (!$this->relationLoaded('items')) {
            $this->load('items');
        }

        // Check if journal has items
        if ($this->items->isEmpty()) {
            \Log::error('Journal - Cannot create GL transactions: No journal items found', [
                'journal_id' => $this->id,
                'journal_reference' => $this->reference
            ]);
            throw new \Exception("Cannot create GL transactions: Journal has no items. Journal ID: {$this->id}, Reference: {$this->reference}");
        }

        \Log::info('Journal - Creating GL transactions', [
            'journal_id' => $this->id,
            'journal_reference' => $this->reference,
            'items_count' => $this->items->count(),
            'branch_id' => $this->branch_id,
            'user_id' => $this->user_id
        ]);

        foreach ($this->items as $item) {
            // Check if GL transaction already exists for this journal item (prevent duplicates)
            $existingGlTransaction = GlTransaction::where('transaction_id', $this->id)
                ->where('transaction_type', 'journal')
                ->where('chart_account_id', $item->chart_account_id)
                ->where('amount', $item->amount)
                ->where('nature', $item->nature)
                ->whereDate('date', $this->date)
                ->first();

            if ($existingGlTransaction) {
                \Log::info('GL Transaction already exists for journal item - skipping duplicate', [
                    'journal_id' => $this->id,
                    'journal_reference' => $this->reference,
                    'journal_item_id' => $item->id,
                    'chart_account_id' => $item->chart_account_id,
                    'existing_gl_transaction_id' => $existingGlTransaction->id,
                    'amount' => $item->amount,
                    'nature' => $item->nature
                ]);
                continue; // Skip this item, GL transaction already exists
            }

            // Check if this chart account is in a completed reconciliation period
            $isInCompletedReconciliation = \App\Services\BankReconciliationService::isChartAccountInCompletedReconciliation(
                $item->chart_account_id,
                $this->date
            );
            
            if ($isInCompletedReconciliation) {
                \Log::warning('Journal - Cannot post: Chart account is in a completed reconciliation period', [
                    'journal_id' => $this->id,
                    'journal_reference' => $this->reference,
                    'chart_account_id' => $item->chart_account_id,
                    'transaction_date' => $this->date
                ]);
                throw new \Exception("Cannot post journal entry: Chart account is in a completed reconciliation period for date {$this->date}.");
            }

            try {
                $glTransaction = GlTransaction::create([
                'chart_account_id' => $item->chart_account_id,
                'customer_id' => null,
                'supplier_id' => null,
                'amount' => $item->amount,
                'nature' => $item->nature,
                'transaction_id' => $this->id,
                'transaction_type' => 'journal',
                'date' => $this->date,
                'description' => $item->description ?? $this->description,
                'branch_id' => $this->branch_id,
                'user_id' => $this->user_id,
            ]);
                
                \Log::info('GL Transaction created successfully', [
                    'gl_transaction_id' => $glTransaction->id,
                    'journal_id' => $this->id,
                    'journal_item_id' => $item->id,
                    'chart_account_id' => $item->chart_account_id,
                    'amount' => $item->amount,
                    'nature' => $item->nature
                ]);
            } catch (\Exception $e) {
                // Check if error is due to duplicate (unique constraint violation)
                if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'UNIQUE constraint')) {
                    \Log::warning('GL Transaction duplicate detected (unique constraint)', [
                        'journal_id' => $this->id,
                        'journal_reference' => $this->reference,
                        'chart_account_id' => $item->chart_account_id,
                        'amount' => $item->amount,
                        'nature' => $item->nature,
                        'error' => $e->getMessage()
                    ]);
                    continue; // Skip this item, duplicate was prevented by database constraint
                }
                
                \Log::error('Failed to create GL transaction', [
                    'journal_id' => $this->id,
                    'journal_reference' => $this->reference,
                    'chart_account_id' => $item->chart_account_id,
                    'amount' => $item->amount,
                    'nature' => $item->nature,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        }
        
        \Log::info('Journal - GL transactions created successfully', [
            'journal_id' => $this->id,
            'journal_reference' => $this->reference,
            'gl_transactions_count' => GlTransaction::where('transaction_id', $this->id)
                ->where('transaction_type', 'journal')
                ->count()
        ]);
    }
    public function getTotalAttribute()
    {
        return $this->items->sum('amount');
    }

    public function getDebitTotalAttribute()
    {
        return $this->items->where('nature', 'debit')->sum('amount');
    }

    public function getCreditTotalAttribute()
    {
        return $this->items->where('nature', 'credit')->sum('amount');
    }

    /**
     * Convert total debit amount to words using shared helper.
     */
    public function getAmountInWords()
    {
        return AmountInWords::convert($this->debit_total);
    }
    public function getBalanceAttribute()
    {
        return $this->debit_total - $this->credit_total;
    }
    public function getFormattedDateAttribute()
    {
        return $this->date ? $this->date->format('Y-m-d') : null;
    }

    public function getFormattedTotalAttribute()
    {
        return number_format($this->total, 2);
    }
    public function getFormattedDebitTotalAttribute()
    {
        return number_format($this->debit_total, 2);
    }
    public function getFormattedCreditTotalAttribute()
    {
        return number_format($this->credit_total, 2);
    }
    public function getFormattedBalanceAttribute()
    {
        return number_format($this->balance, 2);
    }
    
}
