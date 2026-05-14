<?php

namespace App\Models\Purchase;

use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\Company;
use App\Models\GlTransaction;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebitNoteApplication extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'debit_note_id',
        'purchase_invoice_id',
        'bank_account_id',
        'amount_applied',
        'application_type',
        'application_date',
        'description',
        'currency',
        'exchange_rate',
        'fx_gain_loss',
        'reference_number',
        'payment_method',
        'notes',
        'created_by',
        'branch_id',
        'company_id',
    ];

    protected $casts = [
        'amount_applied' => 'decimal:2',
        'application_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'fx_gain_loss' => 'decimal:2',
    ];

    protected $dates = ['deleted_at'];

    /**
     * Relationships
     */
    public function debitNote(): BelongsTo
    {
        return $this->belongsTo(DebitNote::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function glTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GlTransaction::class, 'transaction_id')
            ->where('transaction_type', 'debit_note_application');
    }

    /**
     * Accessors
     */
    public function getApplicationTypeTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->application_type));
    }

    public function getApplicationTypeBadgeAttribute()
    {
        $badges = [
            'invoice' => '<span class="badge bg-primary">Invoice</span>',
            'refund' => '<span class="badge bg-success">Refund</span>',
            'debit_balance' => '<span class="badge bg-info">Debit Balance</span>',
        ];

        return $badges[$this->application_type] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    /**
     * Create GL transactions for this application
     */
    public function createGlTransactions()
    {
        $user = auth()->user() ?? User::find($this->created_by);
        
        // Delete existing transactions for this application
        $this->glTransactions()->delete();

        $transactions = [];

        switch ($this->application_type) {
            case 'invoice':
                // No GL here; allocation only (GL already handled on approval)
                break;

            case 'refund':
                // Refund from supplier
                $bankAccount = $this->bankAccount; // eager/relationship
                if (!$bankAccount || !$bankAccount->chart_account_id) {
                    throw new \Exception('Invalid bank account selected for refund. Please configure the bank account\'s chart account.');
                }
                $bankChartAccountId = (int) $bankAccount->chart_account_id;
                $transactions[] = [
                    'chart_account_id' => $bankChartAccountId,
                    'supplier_id' => $this->debitNote->supplier_id,
                    'amount' => $this->amount_applied,
                    'nature' => 'debit',
                    'transaction_id' => $this->id,
                    'transaction_type' => 'debit_note_application',
                    'date' => $this->application_date,
                    'description' => "Refund for Debit Note #{$this->debitNote->debit_note_number}",
                    'branch_id' => $this->branch_id,
                    'user_id' => $user->id,
                ];

                $transactions[] = [
                    'chart_account_id' => $this->getSupplierPayableAccountId(),
                    'supplier_id' => $this->debitNote->supplier_id,
                    'amount' => $this->amount_applied,
                    'nature' => 'credit',
                    'transaction_id' => $this->id,
                    'transaction_type' => 'debit_note_application',
                    'date' => $this->application_date,
                    'description' => "Refund for Debit Note #{$this->debitNote->debit_note_number}",
                    'branch_id' => $this->branch_id,
                    'user_id' => $user->id,
                ];
                break;

            case 'debit_balance':
                // Keep as debit balance (no GL transaction needed)
                break;
        }

        // Create all transactions
        foreach ($transactions as $transaction) {
            GlTransaction::create($transaction);
        }
    }

    /**
     * Get supplier payable account ID
     */
    private function getSupplierPayableAccountId()
    {
        // Get from system settings or default
        // Reuse existing key used elsewhere for AP
        $value = \App\Models\SystemSetting::where('key', 'inventory_default_purchase_payable_account')->value('value');
        return (int)($value ?: 30);
    }
}
