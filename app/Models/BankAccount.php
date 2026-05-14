<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    use HasFactory,LogsActivity;

    protected $table = 'bank_accounts';
    protected $fillable = [
        'chart_account_id',
        'name',
        'account_number',
        'company_id',
        'branch_id',
        'is_all_branches',
        'currency',
        'revaluation_required',
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_all_branches' => 'boolean',
        'revaluation_required' => 'boolean',
    ];

    /**
     * Get the chart account that owns the bank account.
     */
    public function chartAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'chart_account_id');
    }

    /**
     * Get the company that owns the bank account.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the branch this bank account is scoped to (if any).
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the GL transactions for this bank account.
     */
    public function glTransactions(): HasMany
    {
        return $this->hasMany(GlTransaction::class, 'chart_account_id', 'chart_account_id');
    }

    public function repaymente(){
        return $this->hasMany(Repayment::class,'bank_account_id');
    }

    public function bankReconciliations()
    {
        return $this->hasMany(BankReconciliation::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Calculate the current balance of the bank account.
     */
    public function getBalanceAttribute()
    {
        if (!$this->chart_account_id) {
            return 0;
        }

        $debits = $this->glTransactions()
            ->where('nature', 'debit')
            ->sum('amount');

        $credits = $this->glTransactions()
            ->where('nature', 'credit')
            ->sum('amount');

        // For bank accounts, debits increase balance, credits decrease balance
        // This is because bank accounts are asset accounts (debit to increase, credit to decrease)
        return $debits - $credits;
    }

    /**
     * Get formatted balance.
     */
    public function getFormattedBalanceAttribute()
    {
        return number_format($this->balance, 2);
    }
}