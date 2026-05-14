<?php

namespace App\Models;

use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesProforma;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class Customer extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'customerNo',
        'name',
        'description',
        'phone',
        'email',
        'branch_id',
        'company_id',
        'has_cash_deposit',
        'status',
        'credit_limit',
        'company_name',
        'company_registration_number',
        'tin_number',
        'vat_number',
    ];


    protected $casts = [
        'has_cash_deposit' => 'boolean',
    ];




    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function collaterals()
    {
        return $this->hasMany(CashDeposit::class);
    }



    // Mutator for customer number
    public function setCustomerNoAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['customerNo'] = 100000 + (self::max('id') ?? 0) + 1;
        } else {
            $this->attributes['customerNo'] = $value;
        }
    }


    /**
     * Get the customer's available cash deposit balance (only actual cash deposits)
     * This is used for cash deposit payment validation
     * Includes both CashDeposit and CashCollateral balances
     */
    public function getCashDepositBalanceAttribute()
    {
        // Get total from CashDeposit model
        $totalCashDeposits = $this->cashDeposits()->sum('amount');
        
        // Get total from CashCollateral model
        $totalCashCollaterals = $this->cashCollaterals()->sum('amount');
        
        // Combined total deposits
        $totalDeposits = $totalCashDeposits + $totalCashCollaterals;
        
        // Get total amount used from cash deposits via GL transactions (journal system)
        // This checks for debits to the cash deposit account (ID 28) for this customer
        $totalUsedFromJournals = \App\Models\Journal::where('customer_id', $this->id)
            ->whereIn('reference_type', ['sales_invoice_payment', 'cash_sale_payment'])
            ->join('journal_items', 'journals.id', '=', 'journal_items.journal_id')
            ->where('journal_items.chart_account_id', 28) // Cash Deposits account
            ->where('journal_items.nature', 'debit')
            ->sum('journal_items.amount');
        
        // Get total amount used from cash deposits via payments (old system)
        $totalUsedFromDeposits = $this->payments()
            ->whereNotNull('cash_deposit_id')
            ->sum('amount');
        
        // Calculate available balance
        $availableBalance = $totalDeposits - ($totalUsedFromDeposits + $totalUsedFromJournals);
        
        return max(0, $availableBalance); // Ensure non-negative
     }

    /**
     * Get the customer's complete account balance
     * (Available cash deposits + Total receipts - Total payments - Journal cash deposit usage)
     */
    public function getCompleteAccountBalanceAttribute()
    {
        // Get total cash deposits
        $totalDeposits = $this->cashDeposits()->sum('amount');
        
        // Get total amount used from cash deposits via old payment system
        $totalUsedFromDeposits = $this->payments()
            ->whereNotNull('cash_deposit_id')
            ->sum('amount');
        
        // Get total amount used from cash deposits via new journal system
        // This checks for debits to the cash deposit account (ID 28) for this customer
        $totalUsedFromJournals = \App\Models\Journal::where('customer_id', $this->id)
            ->whereIn('reference_type', ['sales_invoice_payment', 'cash_sale_payment'])
            ->join('journal_items', 'journals.id', '=', 'journal_items.journal_id')
            ->where('journal_items.chart_account_id', 28) // Cash Deposits account
            ->where('journal_items.nature', 'debit')
            ->sum('journal_items.amount');
        
        // Available cash deposits = total deposits - used amount from both systems
        $availableDeposits = $totalDeposits - ($totalUsedFromDeposits + $totalUsedFromJournals);
        
        // Get other receipts and payments (bank transactions)
        $totalReceipts = $this->receipts()->sum('amount');
        $totalPayments = $this->payments()
            ->whereNull('cash_deposit_id') // Exclude cash deposit payments
            ->sum('amount');
        
        return $availableDeposits + $totalReceipts - $totalPayments;
    }

    /**
     * Get available cash deposits for the customer (excluding used amounts)
     */
    public function getAvailableCashDeposits()
    {
        $deposits = $this->cashDeposits()->with('type')->get();
        
        // Calculate available amount for each deposit
        foreach ($deposits as $deposit) {
            $usedAmount = $this->payments()
                ->where('cash_deposit_id', $deposit->id)
                ->sum('amount');
            
            $deposit->available_amount = max(0, $deposit->amount - $usedAmount);
        }
        
        // Return only deposits with available amount > 0
        return $deposits->filter(function ($deposit) {
            return $deposit->available_amount > 0;
        });
    }

    // Sales Relationships
    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function salesProformas()
    {
        return $this->hasMany(SalesProforma::class);
    }

    public function salesInvoices()
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function salesDeliveries()
    {
        return $this->hasMany(\App\Models\Sales\Delivery::class);
    }

    // Financial Relationships
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class, 'payee_id')->where('payee_type', 'customer');
    }

    public function journals()
    {
        return $this->hasMany(Journal::class);
    }

    public function glTransactions()
    {
        return $this->hasMany(GlTransaction::class);
    }

    public function cashDeposits()
    {
        return $this->hasMany(CashDeposit::class);
    }

    public function cashCollaterals()
    {
        return $this->hasMany(CashCollateral::class);
    }

    public function rentalCustomerDeposits()
    {
        return $this->hasMany(\App\Models\RentalEventEquipment\CustomerDeposit::class);
    }


    // Computed Attributes
    public function getTotalOrdersAttribute()
    {
        return $this->salesOrders()->count();
    }

    public function getTotalProformasAttribute()
    {
        return $this->salesProformas()->count();
    }

    public function getTotalInvoicesAttribute()
    {
        return $this->salesInvoices()->count();
    }

    public function getTotalDueInvoicesAttribute()
    {
        // Sum balance_due for all invoices that have an outstanding balance
        // Exclude cancelled invoices and only include invoices with balance_due > 0
        return $this->salesInvoices()
            ->where('status', '!=', 'cancelled')
            ->where('balance_due', '>', 0)
            ->sum('balance_due');
    }

    /**
     * Get customer's current outstanding balance (total due invoices)
     */
    public function getCurrentBalance()
    {
        // Calculate directly from sales invoices to avoid attribute accessor issues
        return $this->salesInvoices()
            ->where('status', '!=', 'cancelled')
            ->where('balance_due', '>', 0)
            ->sum('balance_due') ?? 0;
    }

    /**
     * Get available credit (credit limit - current balance)
     * Returns negative value if current balance exceeds credit limit
     */
    public function getAvailableCredit()
    {
        $creditLimit = $this->credit_limit ?? 0;
        if ($creditLimit <= 0) {
            return 0; // No credit limit means no available credit
        }
        $currentBalance = $this->getCurrentBalance();
        return $creditLimit - $currentBalance;
    }

    /**
     * Check if customer has sufficient credit for an amount
     */
    public function hasSufficientCredit($amount)
    {
        $availableCredit = $this->getAvailableCredit();
        return $amount <= $availableCredit;
    }

    public function getTotalPaymentsAttribute()
    {
        return $this->payments()->sum('amount');
    }

    public function getTotalReceiptsAttribute()
    {
        return $this->receipts()->sum('amount');
    }

    public function getAccountBalanceAttribute()
    {
        $debits = $this->glTransactions()->where('nature', 'debit')->sum('amount');
        $credits = $this->glTransactions()->where('nature', 'credit')->sum('amount');
        return $debits - $credits;
    }

    public function getEncodedIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'active' => '<span class="badge bg-success">Active</span>',
            'inactive' => '<span class="badge bg-secondary">Inactive</span>',
            'suspended' => '<span class="badge bg-warning">Suspended</span>',
        ];

        return $badges[$this->status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active';
    }

}
