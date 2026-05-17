<?php

namespace App\Models\Purchase;

use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\GlTransaction;
use App\Models\Journal;
use App\Models\Supplier;
use App\Models\User;
use App\Services\BankReconciliationService;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierAdvance extends Model
{
    use LogsActivity;

    protected $fillable = [
        'company_id',
        'branch_id',
        'supplier_id',
        'advance_date',
        'reference',
        'debit_chart_account_id',
        'bank_account_id',
        'journal_id',
        'amount',
        'description',
        'attachment_path',
        'user_id',
    ];

    protected $casts = [
        'advance_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function debitChartAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'debit_chart_account_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function isOpeningJournalAdvance(): bool
    {
        return $this->journal_id !== null && $this->bank_account_id === null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function glTransactions(): HasMany
    {
        return $this->hasMany(GlTransaction::class, 'transaction_id')
            ->where('transaction_type', 'supplier_advance');
    }

    public function advanceDeductions(): HasMany
    {
        return $this->hasMany(SupplierAdvanceDeduction::class, 'supplier_advance_id');
    }

    public function advanceDeductionsForCashPurchases(): HasMany
    {
        return $this->advanceDeductions()->where('source_type', 'cash_purchase');
    }

    public function totalDeductedAmount(): float
    {
        return (float) $this->advanceDeductions()->sum('amount');
    }

    public function hasCashPurchaseDeductions(): bool
    {
        return $this->advanceDeductionsForCashPurchases()->exists();
    }

    /**
     * Remove posted GL lines for this advance (e.g. before update or delete).
     */
    public function removeGlTransactions(): void
    {
        app(\App\Services\Purchase\SupplierAdvanceJournalService::class)->removeJournalAndGl($this);
    }

    /**
     * Post double entry: Dr advance (asset) account, Cr bank/cash chart account.
     */
    public function postGlTransactions(): void
    {
        if ($this->journal_id) {
            return;
        }

        if (GlTransaction::where('transaction_type', 'supplier_advance')->where('transaction_id', $this->id)->exists()) {
            return;
        }

        $this->loadMissing(['bankAccount.chartAccount', 'supplier']);
        $bankChartId = $this->bankAccount?->chart_account_id;
        if (! $bankChartId) {
            throw new \RuntimeException('Bank account has no linked chart account.');
        }

        $companyId = (int) $this->company_id;
        $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
        $periodLockService->validateTransactionDate($this->advance_date, $companyId, 'transaction');

        $isInCompletedReconciliation = BankReconciliationService::isChartAccountInCompletedReconciliation(
            $bankChartId,
            $this->advance_date
        );
        if ($isInCompletedReconciliation) {
            throw new \RuntimeException('Cannot post: bank account is in a completed reconciliation period for this date.');
        }

        $branchId = $this->branch_id ?? $this->supplier?->branch_id ?? session('branch_id') ?? auth()->user()?->branch_id;
        if (! $branchId) {
            throw new \RuntimeException('Branch is required for GL posting.');
        }

        $userId = $this->user_id ?? auth()->id();
        if (! $userId) {
            throw new \RuntimeException('User is required for GL posting.');
        }

        $desc = $this->description ?: ('Supplier advance — '.($this->supplier->name ?? ''));
        $ref = $this->reference ?: 'SADV-'.$this->id;

        GlTransaction::create([
            'chart_account_id' => $this->debit_chart_account_id,
            'supplier_id' => $this->supplier_id,
            'amount' => $this->amount,
            'nature' => 'debit',
            'transaction_id' => $this->id,
            'transaction_type' => 'supplier_advance',
            'date' => $this->advance_date,
            'description' => $desc.' ('.$ref.')',
            'branch_id' => $branchId,
            'user_id' => $userId,
        ]);

        GlTransaction::create([
            'chart_account_id' => $bankChartId,
            'supplier_id' => $this->supplier_id,
            'amount' => $this->amount,
            'nature' => 'credit',
            'transaction_id' => $this->id,
            'transaction_type' => 'supplier_advance',
            'date' => $this->advance_date,
            'description' => $desc.' — Bank/cash ('.$ref.')',
            'branch_id' => $branchId,
            'user_id' => $userId,
        ]);
    }
}
