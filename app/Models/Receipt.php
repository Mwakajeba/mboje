<?php

namespace App\Models;

use App\Helpers\AmountInWords;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class Receipt extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'reference',
        'reference_type',
        'reference_number',
        'amount',
        'currency',
        'exchange_rate',
        'amount_fcy',
        'amount_lcy',
        'fx_gain_loss',
        'fx_rate_used',
        'receipt_currency',
        'wht_treatment',
        'wht_rate',
        'wht_amount',
        'net_receivable',
        'vat_mode',
        'vat_amount',
        'base_amount',
        'date',
        'description',
        'user_id',
        'attachment',
        'bank_account_id',
        'payee_type',
        'payee_id',
        'payee_name',
        'customer_id',
        'employee_id',
        'branch_id',
        'approved',
        'approved_by',
        'approved_at',
        'payment_method',
        'cheque_id',
        'cheque_deposited',
        'cheque_deposited_at',
        'cheque_deposited_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'wht_rate' => 'decimal:2',
        'wht_amount' => 'decimal:2',
        'net_receivable' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'date' => 'datetime',
        'approved' => 'boolean',
        'approved_at' => 'datetime',
        'cheque_deposited' => 'boolean',
        'cheque_deposited_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'payee_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'payee_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function receiptItems()
    {
        return $this->hasMany(ReceiptItem::class);
    }

    public function cheque()
    {
        return $this->belongsTo(Cheque::class);
    }

    public function chequeDepositedBy()
    {
        return $this->belongsTo(User::class, 'cheque_deposited_by');
    }

    public function glTransactions()
    {
        return $this->hasMany(GlTransaction::class, 'transaction_id')
            ->where('transaction_type', 'receipt');
    }

    /**
     * Helper attribute: whether this receipt has been posted to GL
     */
    public function getGlPostedAttribute(): bool
    {
        return $this->glTransactions()->exists();
    }

    public function salesInvoice()
    {
        // For sales_invoice receipts, reference_number contains the invoice_number
        return $this->belongsTo(\App\Models\Sales\SalesInvoice::class, 'reference_number', 'invoice_number');
    }
    
    /**
     * Get the sales invoice by checking both reference_number and reference field
     */
    public function getSalesInvoiceAttribute()
    {
        if ($this->reference_type != 'sales_invoice') {
            return null;
        }
        
        // Try by reference_number first (invoice_number)
        if ($this->reference_number) {
            $invoice = \App\Models\Sales\SalesInvoice::where('invoice_number', $this->reference_number)->first();
            if ($invoice) {
                return $invoice;
            }
        }
        
        // Fallback: try by reference field if it's numeric (invoice ID)
        if (is_numeric($this->reference)) {
            return \App\Models\Sales\SalesInvoice::find($this->reference);
        }
        
        return null;
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('approved', true);
    }

    public function scopePending($query)
    {
        return $query->where('approved', false);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('payee_type', 'customer')->where('payee_id', $customerId);
    }

    public function scopeByBankAccount($query, $bankAccountId)
    {
        return $query->where('bank_account_id', $bankAccountId);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2);
    }

    public function getFormattedDateAttribute()
    {
        return $this->date->format('M d, Y');
    }

    public function getStatusBadgeAttribute()
    {
        if ($this->approved) {
            return '<span class="badge bg-success">Approved</span>';
        }
        return '<span class="badge bg-warning">Pending</span>';
    }

    public function getTotalAmountAttribute()
    {
        return $this->receiptItems->sum('amount');
    }

    public function getPayeeNameAttribute($value)
    {
        if ($this->payee_type === 'customer' && $this->customer) {
            return $this->customer->name;
        }
        return $value;
    }

    /**
     * Get the payment method display name
     */
    public function getPaymentMethodAttribute()
    {
        if ($this->bank_account_id && $this->bankAccount) {
            return $this->bankAccount->name;
        }
        return 'Cash';
    }

    /**
     * Get the encoded ID attribute.
     */
    public function getEncodedIdAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    /**
     * Get the encoded ID (alias for getEncodedIdAttribute).
     */
    public function getEncodedId(): string
    {
        return $this->getEncodedIdAttribute();
    }

    /**
     * Convert total_amount to words using shared helper.
     */
    public function getAmountInWords()
    {
        return AmountInWords::convert($this->amount);
    }

    /**
     * Create GL transactions for this receipt voucher.
     * Handles WHT for Exclusive and Inclusive treatments (AR side - no Gross-Up).
     * Integrates with VAT handling per TRA regulations.
     */
    public function createGlTransactions()
    {
        // Check if GL transactions already exist to avoid duplicates
        if ($this->glTransactions()->exists()) {
            return;
        }

        if ($this->reference_type === 'supplier_advance_refund') {
            $this->createSupplierAdvanceRefundGlTransactions();

            return;
        }

        // Check if period is locked
        $companyId = $this->company_id ?? ($this->branch->company_id ?? null);
        if ($companyId) {
            $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
            try {
                $periodLockService->validateTransactionDate($this->date, $companyId, 'receipt');
            } catch (\Exception $e) {
                \Log::warning('Receipt - Cannot post: Period is locked', [
                    'receipt_id' => $this->id,
                    'receipt_reference' => $this->reference,
                    'receipt_date' => $this->date,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        // Check if this receipt is in a completed reconciliation period - prevent posting
        if ($this->bank_account_id) {
            $isInCompletedReconciliation = \App\Models\BankReconciliation::where('bank_account_id', $this->bank_account_id)
                ->where('status', 'completed')
                ->where('start_date', '<=', $this->date)
                ->where('end_date', '>=', $this->date)
                ->exists();
            
            if ($isInCompletedReconciliation) {
                \Log::warning('Receipt::createGlTransactions - Cannot post: Receipt is in a completed reconciliation period', [
                    'receipt_id' => $this->id,
                    'receipt_reference' => $this->reference,
                    'receipt_date' => $this->date,
                    'bank_account_id' => $this->bank_account_id
                ]);
                throw new \Exception("Cannot post: Receipt is in a completed reconciliation period for date {$this->date}.");
            }
        }

        $this->loadMissing(['bankAccount', 'receiptItems']);

        if (!$this->receiptItems->count()) {
            return;
        }

        $bankAccount = $this->bankAccount;
        $date = $this->date;
        $description = $this->description ?: "Receipt voucher {$this->reference}";
        $branchId = $this->branch_id;
        $userId = $this->user_id;

        // Get WHT Receivable account from system settings
        $whtReceivableAccountId = (int) (\App\Models\SystemSetting::where('key', 'inventory_default_withholding_tax_account')->value('value') ?? 37);
        if (!$whtReceivableAccountId) {
            // Fallback: try to find WHT Receivable account by name
            $whtAccount = \App\Models\ChartAccount::where('account_name', 'like', '%WHT%Receivable%')
                ->orWhere('account_name', 'like', '%Withholding%Tax%Receivable%')
                ->first();
            $whtReceivableAccountId = $whtAccount ? $whtAccount->id : 0;
        }

        // Get VAT Output account from system settings
        $vatOutputAccountId = (int) (\App\Models\SystemSetting::where('key', 'inventory_default_vat_account')->value('value') ?? 36);
        if (!$vatOutputAccountId) {
            // Fallback: try to find VAT Output account by name
            $vatAccount = \App\Models\ChartAccount::where('account_name', 'like', '%VAT%Output%')
                ->orWhere('account_name', 'like', '%VAT Output%')
                ->first();
            $vatOutputAccountId = $vatAccount ? $vatAccount->id : 0;
        }

        // Get receipt-level VAT mode and amounts
        $receiptVatMode = $this->vat_mode ?? 'EXCLUSIVE';
        $receiptVatAmount = $this->vat_amount ?? 0;
        $receiptBaseAmount = $this->base_amount ?? $this->amount;

        // Calculate totals for WHT
        $totalWHT = $this->wht_amount ?? 0;
        $totalNetReceivable = $this->net_receivable ?? $this->amount;

        // Calculate total VAT amount and total base (prioritize receipt-level, then sum from items)
        $totalVAT = $receiptVatAmount;
        $totalBase = $receiptBaseAmount;
        
        // If no receipt-level VAT/base, sum from items
        if ($totalVAT == 0 && $totalBase == $this->amount) {
            $totalVAT = 0;
            $totalBase = 0;
            foreach ($this->receiptItems as $item) {
                $itemVatAmount = $item->vat_amount ?? 0;
                $itemBase = $item->base_amount ?? $item->amount;
                $totalVAT += $itemVatAmount;
                $totalBase += $itemBase;
            }
        }

        // Determine debit account based on payment method
        $debitAccountId = null;
        
        // If payment is by cheque and not yet deposited, use Cheques in Transit account
        if ($this->payment_method === 'cheque' && !$this->cheque_deposited) {
            // Get Cheques in Transit account
            $chequesInTransitAccountId = (int) (\App\Models\SystemSetting::where('key', 'cheques_in_transit_account_id')->value('value') ?? 0);
            if (!$chequesInTransitAccountId) {
                // Fallback: try to find by name
                $chequesInTransitAccount = \App\Models\ChartAccount::where('account_name', 'LIKE', '%cheque%transit%')
                    ->orWhere('account_name', 'LIKE', '%cheques in transit%')
                    ->first();
                if (!$chequesInTransitAccount) {
                    throw new \Exception('Cheques in Transit account not configured. Please set cheques_in_transit_account_id in system settings.');
                }
                $chequesInTransitAccountId = $chequesInTransitAccount->id;
            }
            $debitAccountId = $chequesInTransitAccountId;
        } elseif ($bankAccount) {
            // Bank transfer or cheque already deposited - use bank account
            $debitAccountId = $bankAccount->chart_account_id;
        } else {
            // Cash receipt - use default cash account
            $cashAccountId = (int) (\App\Models\SystemSetting::where('key', 'inventory_default_cash_account')->value('value') ?? 1);
            if (!$cashAccountId) {
                $cashAccount = \App\Models\ChartAccount::where('account_name', 'like', '%Cash%Hand%')
                    ->orWhere('account_name', 'like', '%Cash on Hand%')
                    ->first();
                $cashAccountId = $cashAccount ? $cashAccount->id : 0;
            }
            $debitAccountId = $cashAccountId;
        }

        if (!$debitAccountId) {
            \Log::warning('Receipt::createGlTransactions - No debit account found', [
                'receipt_id' => $this->id,
                'reference' => $this->reference,
                'payment_method' => $this->payment_method,
                'cheque_deposited' => $this->cheque_deposited ?? false
            ]);
            return;
        }

        // Calculate debit amount
        // When VAT is EXCLUSIVE: receives base + VAT - WHT
        // When VAT is INCLUSIVE: receives total amount - WHT (total includes VAT)
        // When no VAT: receives total - WHT (or just total if no WHT)
        if ($receiptVatMode === 'EXCLUSIVE' && $totalVAT > 0) {
            // VAT is exclusive: receives base + VAT - WHT
            $debitAmount = ($totalBase + $totalVAT) - $totalWHT;
        } elseif ($receiptVatMode === 'INCLUSIVE' && $totalVAT > 0) {
            // VAT is inclusive: total amount includes VAT, receives total - WHT
            $debitAmount = $this->amount - $totalWHT;
        } else {
            // No VAT: receives total - WHT (or just total if no WHT)
            $debitAmount = $totalWHT > 0 ? $totalNetReceivable : $this->amount;
        }

        // Determine description based on payment method
        $transactionDescription = $description;
        if ($this->payment_method === 'cheque' && !$this->cheque_deposited) {
            $transactionDescription = "Cheque received in transit - {$description}";
        }

        // Debit account (Cheques in Transit, Bank, or Cash)
        GlTransaction::create([
            'chart_account_id' => $debitAccountId,
            'customer_id' => $this->payee_type === 'customer' ? $this->payee_id : null,
            'amount' => $debitAmount,
            'nature' => 'debit',
            'transaction_id' => $this->id,
            'transaction_type' => 'receipt',
            'date' => $date,
            'description' => $transactionDescription,
            'branch_id' => $branchId,
            'user_id' => $userId,
        ]);

        // Debit WHT Receivable (if WHT exists)
        if ($whtReceivableAccountId > 0 && $totalWHT > 0) {
            GlTransaction::create([
                'chart_account_id' => $whtReceivableAccountId,
                'customer_id' => $this->payee_type === 'customer' ? $this->payee_id : null,
                'amount' => $totalWHT,
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'receipt',
                'date' => $date,
                'description' => "WHT Receivable - {$description}",
                'branch_id' => $branchId,
                'user_id' => $userId,
            ]);
        }

        // Credit VAT Output (if VAT exists)
        if ($vatOutputAccountId > 0 && $totalVAT > 0 && $receiptVatMode !== 'NONE') {
            GlTransaction::create([
                'chart_account_id' => $vatOutputAccountId,
                'customer_id' => $this->payee_type === 'customer' ? $this->payee_id : null,
                'amount' => $totalVAT,
                'nature' => 'credit',
                'transaction_id' => $this->id,
                'transaction_type' => 'receipt',
                'date' => $date,
                'description' => "VAT Output - {$description}",
                'branch_id' => $branchId,
                'user_id' => $userId,
            ]);
        }

        // Credit each revenue/income line (base amount only)
        foreach ($this->receiptItems as $item) {
            GlTransaction::create([
                'chart_account_id' => $item->chart_account_id,
                'customer_id' => $this->payee_type === 'customer' ? $this->payee_id : null,
                'amount' => $item->base_amount ?? $item->amount, // Base amount (excluding VAT)
                'nature' => 'credit',
                'transaction_id' => $this->id,
                'transaction_type' => 'receipt',
                'date' => $date,
                'description' => $item->description ?: $description,
                'branch_id' => $branchId,
                'user_id' => $userId,
            ]);
        }
        
        // Log activity for posting to GL
        $payerName = $this->payer_name ?? ($this->customer ? $this->customer->name : ($this->supplier ? $this->supplier->name : 'N/A'));
        $this->logActivity('post', "Posted Receipt {$this->reference} to General Ledger from {$payerName}", [
            'Receipt Reference' => $this->reference,
            'Payer' => $payerName,
            'Payer Type' => ucfirst($this->payer_type ?? 'N/A'),
            'Receipt Date' => $this->date ? $this->date->format('Y-m-d') : 'N/A',
            'Amount' => number_format($this->amount, 2),
            'WHT Amount' => number_format($this->wht_amount ?? 0, 2),
            'VAT Amount' => number_format($this->vat_amount ?? 0, 2),
            'Receipt Items Count' => count($this->receiptItems),
            'Payment Method' => $this->bankAccount ? 'Bank' : ($this->cashDeposit ? 'Cash Deposit' : 'Cash'),
            'Posted By' => auth()->user()->name ?? 'System',
            'Posted At' => now()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * GL for supplier advance cash refund: Dr bank, Cr supplier advance (from receipt items).
     */
    public function createSupplierAdvanceRefundGlTransactions(): void
    {
        $this->loadMissing(['bankAccount', 'receiptItems', 'supplier']);

        if (! $this->bankAccount?->chart_account_id) {
            throw new \Exception('Bank account is required for supplier advance refund receipt.');
        }

        if ($this->receiptItems->isEmpty()) {
            throw new \Exception('Receipt has no items for supplier advance refund.');
        }

        $companyId = $this->branch?->company_id ?? $this->user?->company_id;
        if ($companyId) {
            $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
            $periodLockService->validateTransactionDate($this->date, $companyId, 'supplier advance refund');
        }

        $bankChartId = (int) $this->bankAccount->chart_account_id;
        $supplierId = $this->payee_type === 'supplier' ? $this->payee_id : null;
        $date = $this->date;
        $branchId = $this->branch_id;
        $userId = $this->user_id;
        $desc = $this->description ?: "Supplier advance refund {$this->reference}";
        $totalCredit = round((float) $this->receiptItems->sum('amount'), 2);

        GlTransaction::create([
            'chart_account_id' => $bankChartId,
            'supplier_id' => $supplierId,
            'amount' => $totalCredit,
            'nature' => 'debit',
            'transaction_id' => $this->id,
            'transaction_type' => 'receipt',
            'date' => $date,
            'description' => $desc.' — Bank',
            'branch_id' => $branchId,
            'user_id' => $userId,
        ]);

        foreach ($this->receiptItems as $item) {
            GlTransaction::create([
                'chart_account_id' => $item->chart_account_id,
                'supplier_id' => $supplierId,
                'amount' => $item->amount,
                'nature' => 'credit',
                'transaction_id' => $this->id,
                'transaction_type' => 'receipt',
                'date' => $date,
                'description' => $item->description ?: ($desc.' — Advance'),
                'branch_id' => $branchId,
                'user_id' => $userId,
            ]);
        }

        $supplierName = $this->supplier?->name ?? $this->payee_name ?? 'Supplier';
        $this->logActivity('post', "Posted supplier advance refund receipt {$this->reference} from {$supplierName}", [
            'Receipt Reference' => $this->reference,
            'Supplier' => $supplierName,
            'Amount' => number_format($this->amount, 2),
            'Bank' => $this->bankAccount->name ?? 'N/A',
        ]);
    }

    /**
     * Deposit a cheque (move from Cheques in Transit to Bank Account)
     * 
     * @param int|null $userId User ID who is depositing the cheque
     * @param \Carbon\Carbon|null $depositDate Date of deposit (defaults to now)
     * @return void
     * @throws \Exception
     */
    public function depositCheque($userId = null, $depositDate = null)
    {
        if ($this->payment_method !== 'cheque') {
            throw new \Exception('This receipt is not a cheque payment.');
        }

        if ($this->cheque_deposited) {
            throw new \Exception('This cheque has already been deposited.');
        }

        if (!$this->bank_account_id) {
            throw new \Exception('Bank account is required to deposit cheque.');
        }

        $bankAccount = $this->bankAccount;
        if (!$bankAccount) {
            throw new \Exception('Bank account not found.');
        }

        $userId = $userId ?? auth()->id();
        $depositDate = $depositDate ?? now();

        \DB::beginTransaction();
        try {
            // Get Cheques in Transit account
            $chequesInTransitAccountId = (int) (\App\Models\SystemSetting::where('key', 'cheques_in_transit_account_id')->value('value') ?? 0);
            if (!$chequesInTransitAccountId) {
                $chequesInTransitAccount = \App\Models\ChartAccount::where('account_name', 'LIKE', '%cheque%transit%')
                    ->orWhere('account_name', 'LIKE', '%cheques in transit%')
                    ->first();
                if (!$chequesInTransitAccount) {
                    throw new \Exception('Cheques in Transit account not configured.');
                }
                $chequesInTransitAccountId = $chequesInTransitAccount->id;
            }

            // Find the original GL transaction that debited Cheques in Transit
            $originalTransaction = GlTransaction::where('transaction_id', $this->id)
                ->where('transaction_type', 'receipt')
                ->where('chart_account_id', $chequesInTransitAccountId)
                ->where('nature', 'debit')
                ->first();

            if (!$originalTransaction) {
                throw new \Exception('Original cheque transaction not found.');
            }

            // Calculate amounts (same as original transaction)
            $totalWHT = $this->wht_amount ?? 0;
            $receiptVatMode = $this->vat_mode ?? 'EXCLUSIVE';
            $totalVAT = $this->vat_amount ?? 0;
            $totalBase = $this->base_amount ?? $this->amount;
            $totalNetReceivable = $this->net_receivable ?? $this->amount;

            // Calculate debit amount (same logic as createGlTransactions)
            if ($receiptVatMode === 'EXCLUSIVE' && $totalVAT > 0) {
                $debitAmount = ($totalBase + $totalVAT) - $totalWHT;
            } elseif ($receiptVatMode === 'INCLUSIVE' && $totalVAT > 0) {
                $debitAmount = $this->amount - $totalWHT;
            } else {
                $debitAmount = $totalWHT > 0 ? $totalNetReceivable : $this->amount;
            }

            // Credit Cheques in Transit (reverse the original debit)
            GlTransaction::create([
                'chart_account_id' => $chequesInTransitAccountId,
                'customer_id' => $this->payee_type === 'customer' ? $this->payee_id : null,
                'amount' => $debitAmount,
                'nature' => 'credit',
                'transaction_id' => $this->id,
                'transaction_type' => 'receipt_deposit',
                'date' => $depositDate,
                'description' => "Cheque deposited to bank - {$this->description}",
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ]);

            // Debit Bank Account
            GlTransaction::create([
                'chart_account_id' => $bankAccount->chart_account_id,
                'customer_id' => $this->payee_type === 'customer' ? $this->payee_id : null,
                'amount' => $debitAmount,
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'receipt_deposit',
                'date' => $depositDate,
                'description' => "Cheque deposited: {$this->reference} - {$this->description}",
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ]);

            // Update receipt
            $this->update([
                'cheque_deposited' => true,
                'cheque_deposited_at' => $depositDate,
                'cheque_deposited_by' => $userId,
            ]);

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }
}
