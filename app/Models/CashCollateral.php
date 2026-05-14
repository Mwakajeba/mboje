<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashCollateral extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'branch_id',
        'company_id',
        'customer_id',
        'type_id',
        'amount',
    ];

    // Relationships

    // A CashCollateral belongs to a Branch
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // A CashCollateral belongs to a Company
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // A CashCollateral belongs to a Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // A CashCollateral belongs to a CashCollateralType
    public function type()
    {
        return $this->belongsTo(CashCollateralType::class, 'type_id');
    }
    
    public static function getCashCollateralBalance(int $customerId): float
    {
        $record = self::where('customer_id', $customerId)->first();
        return round($record?->amount ?? 0, 2);
    }

    /**
     * Get the current balance of this cash collateral
     * Calculates: Deposits - Withdrawals - Journal withdrawals
     */
    public function getCurrentBalanceAttribute()
    {
        // Get deposit transactions (receipts)
        $deposits = \App\Models\Receipt::where('reference', $this->id)
            ->where('reference_type', 'Deposit')
            ->sum('amount');
        
        // Get withdrawal transactions (payments)
        $withdrawals = \App\Models\Payment::where('reference', $this->id)
            ->where('reference_type', 'Withdrawal')
            ->sum('amount');
        
        // Get journal-based cash deposit payments (new system)
        // This includes payments made using cash deposits for invoices or cash sales
        $journalWithdrawals = \App\Models\Journal::where('customer_id', $this->customer_id)
            ->whereIn('reference_type', ['sales_invoice_payment', 'cash_sale_payment'])
            ->join('journal_items', 'journals.id', '=', 'journal_items.journal_id')
            ->where('journal_items.chart_account_id', 28) // Cash Deposits account
            ->where('journal_items.nature', 'debit')
            ->sum('journal_items.amount');
        
        // Calculate current balance
        $currentBalance = floatval($deposits) - (floatval($withdrawals) + floatval($journalWithdrawals));
        
        return max(0, round($currentBalance, 2)); // Ensure non-negative
    }
}
