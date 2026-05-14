<?php

namespace App\Models\Sales;

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

class CreditNoteApplication extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'credit_note_id',
        'sales_invoice_id',
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
    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
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
            ->where('transaction_type', 'credit_note_application');
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
            'credit_balance' => '<span class="badge bg-info">Credit Balance</span>',
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
                // Apply credit note to invoice
                $transactions[] = [
                    'chart_account_id' => $this->getCustomerReceivableAccountId(),
                    'customer_id' => $this->creditNote->customer_id,
                    'amount' => $this->amount_applied,
                    'nature' => 'credit',
                    'transaction_id' => $this->id,
                    'transaction_type' => 'credit_note_application',
                    'date' => $this->application_date,
                    'description' => "Credit Note #{$this->creditNote->credit_note_number} applied to Invoice #{$this->salesInvoice->invoice_number}",
                    'branch_id' => $this->branch_id,
                    'user_id' => $user->id,
                ];
                break;

            case 'refund':
                // Refund to customer
                $transactions[] = [
                    'chart_account_id' => $this->bank_account_id,
                    'customer_id' => $this->creditNote->customer_id,
                    'amount' => $this->amount_applied,
                    'nature' => 'credit',
                    'transaction_id' => $this->id,
                    'transaction_type' => 'credit_note_application',
                    'date' => $this->application_date,
                    'description' => "Refund for Credit Note #{$this->creditNote->credit_note_number}",
                    'branch_id' => $this->branch_id,
                    'user_id' => $user->id,
                ];

                $transactions[] = [
                    'chart_account_id' => $this->getCustomerReceivableAccountId(),
                    'customer_id' => $this->creditNote->customer_id,
                    'amount' => $this->amount_applied,
                    'nature' => 'debit',
                    'transaction_id' => $this->id,
                    'transaction_type' => 'credit_note_application',
                    'date' => $this->application_date,
                    'description' => "Refund for Credit Note #{$this->creditNote->credit_note_number}",
                    'branch_id' => $this->branch_id,
                    'user_id' => $user->id,
                ];
                break;

            case 'credit_balance':
                // Keep as credit balance (no GL transaction needed)
                break;
        }

        // Create all transactions
        foreach ($transactions as $transaction) {
            GlTransaction::create($transaction);
        }
    }

    /**
     * Get customer receivable account ID
     */
    private function getCustomerReceivableAccountId()
    {
        // Get from system settings or default
        $setting = \App\Models\SystemSetting::where('key', 'inventory_default_receivable_account')
            ->first();

        if ($setting && $setting->value) {
            return (int) $setting->value;
        }
        
        // Check if customer has specific receivable account
        if ($this->creditNote->customer && $this->creditNote->customer->receivable_account_id) {
            return $this->creditNote->customer->receivable_account_id;
        }
        
        // Fallback: Try ID 18 first (Accounts Receivable for new installations), then ID 2 (existing databases)
        $account18 = \App\Models\ChartAccount::find(18);
        if ($account18 && $account18->account_name === 'Accounts Receivable') {
            return 18;
        }
        
        $account2 = \App\Models\ChartAccount::find(2);
        if ($account2 && $account2->account_name === 'Accounts Receivable') {
            return 2;
        }
        
        // Last resort: find by name
        $account = \App\Models\ChartAccount::where('account_name', 'like', '%Accounts Receivable%')->first();
        return $account ? $account->id : 18; // Default to 18 if nothing found
    }
} 