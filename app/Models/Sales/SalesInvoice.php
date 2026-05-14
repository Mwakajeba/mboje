<?php

namespace App\Models\Sales;

use App\Models\Customer;
use App\Models\Inventory\Item as InventoryItem;
use App\Models\Inventory\Movement as InventoryMovement;
use App\Models\User;
use App\Models\Branch;
use App\Models\CashDeposit;
use App\Models\Company;
use App\Models\GlTransaction;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\SystemSetting;
use App\Models\Payment;
use App\Helpers\AmountInWords;
use App\Models\Sales\CreditNote;
use App\Models\Sales\Delivery;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Vinkla\Hashids\Facades\Hashids;

class SalesInvoice extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'invoice_number',
        'sales_order_id',
        'delivery_id',
        'customer_id',
        'invoice_date',
        'due_date',
        'status',
        'payment_terms',
        'payment_days',
        'reference_no',
        'subtotal',
        'vat_amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'balance_due',
        'currency',
        'exchange_rate',
        'withholding_tax_amount',
        'withholding_tax_rate',
        'withholding_tax_type',
        'early_payment_discount_enabled',
        'early_payment_discount_type',
        'early_payment_discount_rate',
        'early_payment_days',
        'late_payment_fees_enabled',
        'late_payment_fees_type',
        'late_payment_fees_rate',
        'notes',
        'terms_conditions',
        'attachment',
        'branch_id',
        'company_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'currency' => 'string',
        'exchange_rate' => 'decimal:6',
        'withholding_tax_amount' => 'decimal:2',
        'withholding_tax_rate' => 'decimal:2',
        'withholding_tax_type' => 'string',
        'payment_days' => 'integer',
        'early_payment_discount_enabled' => 'boolean',
        'early_payment_discount_type' => 'string',
        'early_payment_discount_rate' => 'decimal:2',
        'early_payment_days' => 'integer',
        'late_payment_fees_enabled' => 'boolean',
        'late_payment_fees_type' => 'string',
        'late_payment_fees_rate' => 'decimal:2',
    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($salesInvoice) {
            if (empty($salesInvoice->invoice_number)) {
                $salesInvoice->invoice_number = self::generateInvoiceNumber();
            }
        });
    }

    /**
     * Generate unique invoice number
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = date('Y');
        $month = date('m');
        
        $lastInvoice = self::where('invoice_number', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function glTransactions(): HasMany
    {
        return $this->hasMany(GlTransaction::class, 'transaction_id')
            ->where('transaction_type', 'sales_invoice');
    }

    /**
     * Helper attribute: whether this sales invoice has been posted to GL
     */
    public function getGlPostedAttribute(): bool
    {
        return $this->glTransactions()->exists();
    }

    /**
     * Scopes
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now()->toDateString())
            ->where('status', '!=', 'paid');
    }

    /**
     * Accessors
     */
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'draft' => 'bg-secondary',
            'sent' => 'bg-info',
            'paid' => 'bg-success',
            'overdue' => 'bg-danger',
            'cancelled' => 'bg-dark',
        ];

        $color = $badges[$this->status] ?? 'bg-secondary';
        return '<span class="badge ' . $color . '">' . ucfirst($this->status) . '</span>';
    }

    public function getPaymentTermsTextAttribute()
    {
        $terms = [
            'immediate' => 'Immediate',
            'net_15' => 'Net 15',
            'net_30' => 'Net 30',
            'net_45' => 'Net 45',
            'net_60' => 'Net 60',
            'custom' => 'Custom',
        ];

        return $terms[$this->payment_terms] ?? 'Net 30';
    }

    public function getIsOverdueAttribute()
    {
        return $this->due_date < now()->toDateString() && $this->status !== 'paid';
    }

    public function getIsFullyPaidAttribute()
    {
        return $this->paid_amount >= $this->total_amount;
    }

    public function getEncodedIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    /**
     * Double Entry Accounting Methods
     */
    public function createDoubleEntryTransactions()
    {
        // Check if period is locked
        $companyId = $this->company_id ?? ($this->branch->company_id ?? null);
        if ($companyId) {
            $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
            try {
                $periodLockService->validateTransactionDate($this->invoice_date, $companyId, 'sales invoice');
            } catch (\Exception $e) {
                \Log::warning('SalesInvoice - Cannot post: Period is locked', [
                    'invoice_id' => $this->id,
                    'invoice_number' => $this->invoice_number,
                    'invoice_date' => $this->invoice_date,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        $user = auth()->user();
        $userId = $user ? $user->id : 1; // Default to user ID 1 if no authenticated user
        
        // Delete existing transactions for this invoice
        $this->glTransactions()->delete();

        // Get functional currency and check if conversion is needed
        $functionalCurrency = SystemSetting::getValue('functional_currency', $this->company->functional_currency ?? 'TZS');
        $invoiceCurrency = $this->currency ?? $functionalCurrency;
        $exchangeRate = $this->exchange_rate ?? 1.000000;
        $needsConversion = ($invoiceCurrency !== $functionalCurrency && $exchangeRate != 1.000000);
        
        // Helper function to convert FCY to LCY if needed
        $convertToLCY = function($fcyAmount) use ($needsConversion, $exchangeRate) {
            return $needsConversion ? round($fcyAmount * $exchangeRate, 2) : $fcyAmount;
        };
        
        // Helper function to add currency info to description
        $addCurrencyInfo = function($description) use ($needsConversion, $invoiceCurrency, $functionalCurrency, $exchangeRate) {
            if ($needsConversion) {
                return $description . " [FCY: {$invoiceCurrency}, Rate: {$exchangeRate}, Converted to {$functionalCurrency}]";
            }
            return $description;
        };

        $transactions = [];

        // 1. Debit Accounts Receivable (Customer) - net amount after discount
        // Convert to functional currency if invoice is in foreign currency
        $netReceivableAmount = $convertToLCY($this->total_amount);
        $transactions[] = [
            'chart_account_id' => $this->getReceivableAccountId(),
            'customer_id' => $this->customer_id,
            'amount' => $netReceivableAmount,
            'nature' => 'debit',
            'transaction_id' => $this->id,
            'transaction_type' => 'sales_invoice',
            'date' => $this->invoice_date,
            'description' => $addCurrencyInfo("Invoice #{$this->invoice_number} - {$this->customer->name}"),
            'branch_id' => $this->branch_id,
            'user_id' => $userId,
        ];

        // 2. Credit Sales Revenue (exclude VAT from revenue)
        // Group items by their sales revenue account (item-specific or default)
        // Separate product/service revenue from transport revenue, both net of VAT
        $salesRevenueByAccount = [];
        $transportRevenueAmount = 0;
        $defaultSalesAccountId = \App\Models\SystemSetting::where('key', 'inventory_default_sales_account')->value('value') ?? 53;

        foreach ($this->items as $item) {
            $netLine = ($item->line_total ?? 0) - ($item->vat_amount ?? 0);
            if ($item->item_code === 'TRANSPORT') {
                $transportRevenueAmount += $netLine;
            } else {
                // Get sales revenue account for this item
                $salesAccountId = $defaultSalesAccountId;
                if ($item->inventoryItem 
                    && $item->inventoryItem->has_different_sales_revenue_account 
                    && $item->inventoryItem->sales_revenue_account_id) {
                    $salesAccountId = $item->inventoryItem->sales_revenue_account_id;
                }
                
                // Group by account ID
                if (!isset($salesRevenueByAccount[$salesAccountId])) {
                    $salesRevenueByAccount[$salesAccountId] = 0;
                }
                $salesRevenueByAccount[$salesAccountId] += $netLine;
            }
        }
        
        // Credit Sales Revenue for each account group
        foreach ($salesRevenueByAccount as $accountId => $amount) {
            if ($amount > 0) {
                $amountLCY = $convertToLCY($amount);
                $transactions[] = [
                    'chart_account_id' => $accountId,
                    'customer_id' => $this->customer_id,
                    'amount' => $amountLCY,
                    'nature' => 'credit',
                    'transaction_id' => $this->id,
                    'transaction_type' => 'sales_invoice',
                    'date' => $this->invoice_date,
                    'description' => $addCurrencyInfo("Sales Revenue - Invoice #{$this->invoice_number}"),
                    'branch_id' => $this->branch_id,
                    'user_id' => $userId,
                ];
            }
        }
        
        // Credit Transport Revenue for transport services
        if ($transportRevenueAmount > 0) {
            $transportRevenueAmountLCY = $convertToLCY($transportRevenueAmount);
            $transportRevenueAccountId = \App\Models\SystemSetting::where('key', 'inventory_default_transport_revenue_account')->value('value');
            if ($transportRevenueAccountId) {
                $transactions[] = [
                    'chart_account_id' => $transportRevenueAccountId,
                    'customer_id' => $this->customer_id,
                    'amount' => $transportRevenueAmountLCY,
                    'nature' => 'credit',
                    'transaction_id' => $this->id,
                    'transaction_type' => 'sales_invoice',
                    'date' => $this->invoice_date,
                    'description' => $addCurrencyInfo("Transport Revenue (net of VAT) - Invoice #{$this->invoice_number}"),
                    'branch_id' => $this->branch_id,
                    'user_id' => $userId,
                ];
            }
        }

        // 3. Credit VAT Payable (if VAT amount > 0)
        if ($this->vat_amount > 0) {
            $vatAmountLCY = $convertToLCY($this->vat_amount);
            $transactions[] = [
                'chart_account_id' => $this->getVatAccountId(),
                'customer_id' => $this->customer_id,
                'amount' => $vatAmountLCY,
                'nature' => 'credit',
                'transaction_id' => $this->id,
                'transaction_type' => 'sales_invoice',
                'date' => $this->invoice_date,
                'description' => $addCurrencyInfo("VAT Payable - Invoice #{$this->invoice_number}"),
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];
        }

        // 4. Debit Sales Discount (if discount amount > 0)
        if ($this->discount_amount > 0) {
            $discountAmountLCY = $convertToLCY($this->discount_amount);
            $transactions[] = [
                'chart_account_id' => $this->getDiscountAccountId(),
                'customer_id' => $this->customer_id,
                'amount' => $discountAmountLCY,
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'sales_invoice',
                'date' => $this->invoice_date,
                'description' => $addCurrencyInfo("Sales Discount - Invoice #{$this->invoice_number}"),
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];
        }

        // 5. Handle Withholding Tax (if withholding tax > 0)
        if ($this->withholding_tax_amount > 0) {
            $whtAmountLCY = $convertToLCY($this->withholding_tax_amount);
            $isWithholdingReceivable = $this->getWithholdingTaxNature();
            $description = $isWithholdingReceivable ? "Withholding Tax Receivable from Invoice" : "Withholding Tax Payable";
            
            $transactions[] = [
                'chart_account_id' => $this->getWithholdingTaxAccountId(),
                'customer_id' => $this->customer_id,
                'amount' => $whtAmountLCY,
                // 'nature' => $isWithholdingReceivable ? 'debit' : 'credit', // Receivable = debit, Payable = credit
                'nature' => 'debit', // Always debit for sales invoice
                'transaction_id' => $this->id,
                'transaction_type' => 'sales_invoice',
                'date' => $this->invoice_date,
                'description' => $addCurrencyInfo("{$description} - Invoice #{$this->invoice_number}"),
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];
        }

        // 6. Debit Cost of Goods Sold
        $cogsAmount = $this->calculateCostOfGoodsSold();
     
        
        if ($cogsAmount > 0) {
            $costAccountId = $this->getCostAccountId();
            $inventoryAccountId = $this->getInventoryAccountId();
            
            // Validate account IDs exist in database
            $costAccountExists = \App\Models\ChartAccount::find($costAccountId);
            $inventoryAccountExists = \App\Models\ChartAccount::find($inventoryAccountId);
            
            if (!$costAccountId || !$inventoryAccountId || !$costAccountExists || !$inventoryAccountExists) {
                \Log::error('SalesInvoice::createDoubleEntryTransactions - Missing or invalid account IDs', [
                    'invoice_id' => $this->id,
                    'invoice_number' => $this->invoice_number,
                    'cost_account_id' => $costAccountId,
                    'inventory_account_id' => $inventoryAccountId,
                    'cost_account_exists' => $costAccountExists ? true : false,
                    'inventory_account_exists' => $inventoryAccountExists ? true : false,
                ]);
                
                // Use fallback account IDs if missing
                if (!$costAccountId || !$costAccountExists) {
                    $costAccountId = 173; // Default COGS account
                    \Log::warning('SalesInvoice::createDoubleEntryTransactions - Using fallback COGS account', [
                        'invoice_id' => $this->id,
                        'fallback_account_id' => $costAccountId,
                    ]);
                }
                
                if (!$inventoryAccountId || !$inventoryAccountExists) {
                    $inventoryAccountId = \App\Models\SystemSetting::where('key', 'inventory_default_inventory_account')->value('value') ?? 185; // Default Inventory account
                    \Log::warning('SalesInvoice::createDoubleEntryTransactions - Using fallback Inventory account', [
                        'invoice_id' => $this->id,
                        'fallback_account_id' => $inventoryAccountId,
                    ]);
                }
            }
            
            // IMPORTANT: COGS is always in functional currency (TZS) - inventory costs are stored in TZS
            // We should NOT convert COGS when invoice is in foreign currency
            // The cost from inventory movements is always in TZS
            $cogsAmountLCY = round($cogsAmount, 2);
            
            $transactions[] = [
                'chart_account_id' => $costAccountId,
                'customer_id' => $this->customer_id,
                'amount' => $cogsAmountLCY,
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'sales_invoice',
                'date' => $this->invoice_date,
                'description' => "Cost of Goods Sold - Invoice #{$this->invoice_number} [Amount in {$functionalCurrency}]",
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];

            // 7. Credit Inventory
            $transactions[] = [
                'chart_account_id' => $inventoryAccountId,
                'customer_id' => $this->customer_id,
                'amount' => $cogsAmountLCY,
                'nature' => 'credit',
                'transaction_id' => $this->id,
                'transaction_type' => 'sales_invoice',
                'date' => $this->invoice_date,
                'description' => "Inventory Reduction - Invoice #{$this->invoice_number} [Amount in {$functionalCurrency}]",
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];
        } else {
            \Log::info('SalesInvoice::createDoubleEntryTransactions - COGS is 0, skipping COGS/Inventory transactions', [
                'invoice_id' => $this->id,
                'invoice_number' => $this->invoice_number,
            ]);
        }

        // Create all transactions
        $createdCount = 0;
        foreach ($transactions as $transaction) {
            try {
                GlTransaction::create($transaction);
                $createdCount++;
            } catch (\Exception $e) {
                \Log::error('SalesInvoice::createDoubleEntryTransactions - Failed to create GL transaction', [
                    'invoice_id' => $this->id,
                    'invoice_number' => $this->invoice_number,
                    'transaction' => $transaction,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e; // Re-throw to ensure transaction rollback
            }
        }
        
        
        // Log activity for posting to GL
        $customerName = $this->customer ? $this->customer->name : 'N/A';
        $currencyInfo = $needsConversion ? " (FCY: {$invoiceCurrency} {$this->total_amount}, Rate: {$exchangeRate}, LCY: {$functionalCurrency} " . number_format($netReceivableAmount, 2) . ")" : "";
        $this->logActivity('post', "Posted Sales Invoice {$this->invoice_number} to General Ledger for Customer: {$customerName}{$currencyInfo}", [
            'Invoice Number' => $this->invoice_number,
            'Customer' => $customerName,
            'Invoice Date' => $this->invoice_date ? $this->invoice_date->format('Y-m-d') : 'N/A',
            'Due Date' => $this->due_date ? $this->due_date->format('Y-m-d') : 'N/A',
            'Currency' => $invoiceCurrency,
            'Functional Currency' => $functionalCurrency,
            'Exchange Rate' => $exchangeRate,
            'Total Amount (FCY)' => number_format($this->total_amount, 2) . ' ' . $invoiceCurrency,
            'Total Amount (LCY)' => $needsConversion ? number_format($netReceivableAmount, 2) . ' ' . $functionalCurrency : number_format($this->total_amount, 2) . ' ' . $functionalCurrency,
            'Subtotal' => number_format($this->subtotal, 2),
            'VAT Amount' => number_format($this->vat_amount, 2),
            'Balance Due' => number_format($this->balance_due, 2),
            'GL Transactions Created' => $createdCount,
            'Posted By' => auth()->user()->name ?? 'System',
            'Posted At' => now()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Create GL transactions for early payment discount when payment is received
     */
    public function createEarlyPaymentDiscountTransactions($paymentAmount, $paymentDate)
    {
        if (!$this->early_payment_discount_enabled || !$this->isEarlyPaymentDiscountValid()) {
            return;
        }

        $earlyDiscount = $this->calculateEarlyPaymentDiscount();
        if ($earlyDiscount <= 0) {
            return;
        }

        // Only apply discount if payment is made within early payment period
        $earlyPaymentDate = $this->getEarlyPaymentDiscountExpiryDate();
        if ($paymentDate > $earlyPaymentDate) {
            return;
        }

        $user = auth()->user();
        $userId = $user ? $user->id : 1; // Default to user ID 1 if no authenticated user
        $transactions = [];

        // 1. Debit Early Payment Discount Expense
        $transactions[] = [
            'chart_account_id' => $this->getEarlyPaymentDiscountAccountId(),
            'customer_id' => $this->customer_id,
            'amount' => $earlyDiscount,
            'nature' => 'debit',
            'transaction_id' => $this->id,
            'transaction_type' => 'early_payment_discount',
            'date' => $paymentDate,
            'description' => "Early Payment Discount - Invoice #{$this->invoice_number}",
            'branch_id' => $this->branch_id,
            'user_id' => $userId,
        ];

        // 2. Credit Accounts Receivable (reduce the receivable)
        $transactions[] = [
            'chart_account_id' => $this->getReceivableAccountId(),
            'customer_id' => $this->customer_id,
            'amount' => $earlyDiscount,
            'nature' => 'credit',
            'transaction_id' => $this->id,
            'transaction_type' => 'early_payment_discount',
            'date' => $paymentDate,
            'description' => "Early Payment Discount Applied - Invoice #{$this->invoice_number}",
            'branch_id' => $this->branch_id,
            'user_id' => $userId,
        ];

        // Create all transactions
        foreach ($transactions as $transaction) {
            GlTransaction::create($transaction);
        }
    }

    /**
     * Create GL transactions for late payment fees
     */
    public function createLatePaymentFeesTransactions($feeAmount, $feeDate)
    {
        if (!$this->late_payment_fees_enabled || !$this->isOverdue()) {
            return;
        }

        if ($feeAmount <= 0) {
            return;
        }

        $user = auth()->user();
        $userId = $user ? $user->id : 1; // Default to user ID 1 if no authenticated user
        
        // Ensure feeDate is a Carbon instance
        if (!$feeDate instanceof \Carbon\Carbon) {
            $feeDate = \Carbon\Carbon::parse($feeDate);
        }
        
        $transactions = [];

        // 1. Debit Accounts Receivable (increase the receivable)
        $transactions[] = [
            'chart_account_id' => $this->getReceivableAccountId(),
            'customer_id' => $this->customer_id,
            'amount' => $feeAmount,
            'nature' => 'debit',
            'transaction_id' => $this->id,
            'transaction_type' => 'late_payment_fees',
            'date' => $feeDate->toDateString(),
            'description' => "Late Payment Fees - Invoice #{$this->invoice_number}",
            'branch_id' => $this->branch_id,
            'user_id' => $userId,
        ];

        // 2. Credit Late Payment Fees Income
        $transactions[] = [
            'chart_account_id' => $this->getLatePaymentFeesAccountId(),
            'customer_id' => $this->customer_id,
            'amount' => $feeAmount,
            'nature' => 'credit',
            'transaction_id' => $this->id,
            'transaction_type' => 'late_payment_fees',
            'date' => $feeDate->toDateString(),
            'description' => "Late Payment Fees Income - Invoice #{$this->invoice_number}",
            'branch_id' => $this->branch_id,
            'user_id' => $userId,
        ];

        // Create all transactions
        foreach ($transactions as $transaction) {
            try {
                GlTransaction::create($transaction);
            } catch (\Exception $e) {
                \Log::error('Failed to create late payment fees GL transaction', [
                    'invoice_number' => $this->invoice_number,
                    'transaction' => $transaction,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    }

    /**
     * Apply late payment fees to overdue invoice
     * This method should be called periodically or when processing overdue invoices
     */
    public function applyLatePaymentFees()
    {
        if (!$this->late_payment_fees_enabled || !$this->isOverdue()) {
            return false;
        }

        $feeAmount = $this->calculateLatePaymentFees();
        if ($feeAmount <= 0) {
            return false;
        }

        // Check if late payment fees have already been applied
        // For monthly fees: check if applied this month
        // For one-time fees: check if ever applied
        // Query directly since glTransactions() relationship filters by transaction_type = 'sales_invoice'
        $query = GlTransaction::where('transaction_id', $this->id)
            ->where('transaction_type', 'late_payment_fees');
        
        if ($this->late_payment_fees_type === 'monthly') {
            // For monthly fees, only check current month
            $query->where('date', '>=', now()->startOfMonth());
        }
        // For one-time fees, check all time (no date filter)
        
        $existingFees = $query->sum('amount');

        if ($existingFees > 0) {
            return false; // Fees already applied
        }

        // Create late payment fees GL transactions
        $this->createLatePaymentFeesTransactions($feeAmount, now());

        // Update invoice balance due
        $this->balance_due = $this->balance_due + $feeAmount;
        $this->save();

        return true;
    }

    /**
     * Get account IDs from inventory items or default settings
     */
    public function getSalesAccountId()
    {
        // Try to get from first item, otherwise use default
        $firstItem = $this->items()->with('inventoryItem')->first();
        if ($firstItem && $firstItem->inventoryItem && $firstItem->inventoryItem->sales_account_id) {
            return $firstItem->inventoryItem->sales_account_id;
        }

        // Use default from settings
        return \App\Models\SystemSetting::where('key', 'inventory_default_sales_account')->value('value') ?? 53; // Sales Revenue
    }

    // Get sales receivable account ID
     public function getReceivableAccountId()
    {
        // Try to get from customer, otherwise use default
        if ($this->customer && $this->customer->receivable_account_id) {
            return $this->customer->receivable_account_id;
        }

        // Use default from settings, fallback to ID 18 (Accounts Receivable) or ID 2 for existing databases
        $settingValue = \App\Models\SystemSetting::where('key', 'inventory_default_receivable_account')->value('value');
        if ($settingValue) {
            return (int) $settingValue;
        }
        
        // Fallback: Try ID 18 first (new installations), then ID 2 (existing databases)
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

    public function getVatAccountId()
    {
        $firstItem = $this->items()->with('inventoryItem')->first();
        if ($firstItem && $firstItem->inventoryItem && $firstItem->inventoryItem->vat_account_id) {
            return $firstItem->inventoryItem->vat_account_id;
        }

        return \App\Models\SystemSetting::where('key', 'inventory_default_vat_account')->value('value') ?? 36; // VAT Payable
    }

    /**
     * Get VAT mode from invoice items
     * Returns 'EXCLUSIVE', 'INCLUSIVE', or 'NONE'
     */
    public function getVatMode()
    {
        $items = $this->items;
        if ($items->isEmpty()) {
            return 'NONE';
        }

        // Check if all items have the same VAT type
        $vatTypes = $items->pluck('vat_type')->unique()->values();
        
        if ($vatTypes->count() === 1) {
            $vatType = $vatTypes->first();
            if ($vatType === 'exclusive') {
                return 'EXCLUSIVE';
            } elseif ($vatType === 'inclusive') {
                return 'INCLUSIVE';
            } else {
                return 'NONE';
            }
        }

        // If mixed types, determine based on invoice structure
        // If invoice has VAT amount and total = subtotal + VAT, it's exclusive
        // If invoice has VAT amount and total includes VAT, it's inclusive
        if ($this->vat_amount > 0) {
            // Check if total = subtotal + vat_amount (exclusive) or total includes VAT (inclusive)
            $expectedExclusiveTotal = $this->subtotal + $this->vat_amount - $this->discount_amount;
            if (abs($this->total_amount - $expectedExclusiveTotal) < 0.01) {
                return 'EXCLUSIVE';
            } else {
                return 'INCLUSIVE';
            }
        }

        return 'NONE';
    }

    /**
     * Get VAT rate from invoice items
     * Returns the most common VAT rate, or calculated rate from invoice totals
     */
    public function getVatRate()
    {
        $items = $this->items;
        if ($items->isEmpty()) {
            return 0;
        }

        // Get the most common VAT rate from items
        $vatRates = $items->where('vat_rate', '>', 0)->pluck('vat_rate')->unique()->values();
        
        if ($vatRates->count() > 0) {
            // Return the most common rate (first one if all are the same, or the highest if different)
            return $vatRates->first();
        }

        // If no items have VAT rate, calculate from invoice totals
        if ($this->vat_amount > 0 && $this->subtotal > 0) {
            // Calculate rate: VAT / Subtotal * 100
            return round(($this->vat_amount / $this->subtotal) * 100, 2);
        }

        return 0;
    }

    public function getWithholdingTaxAccountId()
    {
        $firstItem = $this->items()->with('inventoryItem')->first();
        if ($firstItem && $firstItem->inventoryItem && $firstItem->inventoryItem->withholding_tax_account_id) {
            return $firstItem->inventoryItem->withholding_tax_account_id;
        }

        return \App\Models\SystemSetting::where('key', 'inventory_default_withholding_tax_account')->value('value') ?? 37; // Withholding Tax Payable
    }

    public function getDiscountAccountId()
    {
        return \App\Models\SystemSetting::where('key', 'inventory_default_discount_account')->value('value') ?? 172;
    }

    public function getDiscountIncomeAccountId()
    {
        $firstItem = $this->items()->with('inventoryItem')->first();
        if ($firstItem && $firstItem->inventoryItem && $firstItem->inventoryItem->discount_income_account_id) {
            return $firstItem->inventoryItem->discount_income_account_id;
        }

        return \App\Models\SystemSetting::where('key', 'inventory_default_discount_income_account')->value('value') ?? 52;
    }

    public function getWithholdingTaxNature()
    {
        $firstItem = $this->items()->with('inventoryItem')->first();
        if ($firstItem && $firstItem->inventoryItem && isset($firstItem->inventoryItem->is_withholding_receivable)) {
            return $firstItem->inventoryItem->is_withholding_receivable;
        }

        return \App\Models\SystemSetting::where('key', 'inventory_default_is_withholding_receivable')->value('value') ?? false; // Default to payable (false)
    }

    public function getCostAccountId()
    {
        $firstItem = $this->items()->with('inventoryItem')->first();
        if ($firstItem && $firstItem->inventoryItem && $firstItem->inventoryItem->cost_account_id) {
            return $firstItem->inventoryItem->cost_account_id;
        }

        return \App\Models\SystemSetting::where('key', 'inventory_default_cost_account')->value('value') ?? 173; // Cost of Goods Sold
    }

    public function getInventoryAccountId()
    {
        $firstItem = $this->items()->with('inventoryItem')->first();
        if ($firstItem && $firstItem->inventoryItem && $firstItem->inventoryItem->inventory_account_id) {
            return $firstItem->inventoryItem->inventory_account_id;
        }

        return \App\Models\SystemSetting::where('key', 'inventory_default_inventory_account')->value('value') ?? 185; // Merchandise Inventory
    }

    public function getEarlyPaymentDiscountAccountId()
    {
        $firstItem = $this->items()->with('inventoryItem')->first();
        if ($firstItem && $firstItem->inventoryItem && $firstItem->inventoryItem->early_payment_discount_account_id) {
            return $firstItem->inventoryItem->early_payment_discount_account_id;
        }

        return \App\Models\SystemSetting::where('key', 'inventory_default_early_payment_discount_account')->value('value') ?? 172;
    }

    public function getLatePaymentFeesAccountId()
    {
        $firstItem = $this->items()->with('inventoryItem')->first();
        if ($firstItem && $firstItem->inventoryItem && $firstItem->inventoryItem->late_payment_fees_account_id) {
            return $firstItem->inventoryItem->late_payment_fees_account_id;
        }

        return \App\Models\SystemSetting::where('key', 'inventory_default_late_payment_fees_account')->value('value') ?? 199;
    }

    /**
     * Get FX Gain Account ID (for realized FX gains from payments)
     */
    public function getFxGainAccountId()
    {
        // Get from Foreign Exchange Settings - fx_realized_gain_account_id
        $accountId = \App\Models\SystemSetting::getValue('fx_realized_gain_account_id');
        
        if ($accountId) {
            return (int) $accountId;
        }
        
        // Fallback: Try to find "Foreign Exchange Gain - Realized" account by name
        $fxGainAccount = \App\Models\ChartAccount::where('account_name', 'like', '%Foreign Exchange Gain%Realized%')
            ->orWhere('account_name', 'like', '%FX Gain%Realized%')
            ->first();
            
        if ($fxGainAccount) {
            return $fxGainAccount->id;
        }
        
        // Last resort: return null and let the calling code handle it
        return null;
    }

    /**
     * Get FX Loss Account ID (for realized FX losses from payments)
     */
    public function getFxLossAccountId()
    {
        // Get from Foreign Exchange Settings - fx_realized_loss_account_id
        $accountId = \App\Models\SystemSetting::getValue('fx_realized_loss_account_id');
        
        if ($accountId) {
            return (int) $accountId;
        }
        
        // Fallback: Try to find "Foreign Exchange Loss - Realized" account by name
        $fxLossAccount = \App\Models\ChartAccount::where('account_name', 'like', '%Foreign Exchange Loss%Realized%')
            ->orWhere('account_name', 'like', '%FX Loss%Realized%')
            ->first();
            
        if ($fxLossAccount) {
            return $fxLossAccount->id;
        }
        
        // Last resort: return null and let the calling code handle it
        return null;
    }

    /**
     * Calculate total cost of goods sold
     */
    private function calculateCostOfGoodsSold()
    {
        // Get actual cost from inventory movements for this invoice
        // Use reference_type and reference_id for more reliable matching
        $movements = InventoryMovement::where('reference_type', 'sales_invoice')
            ->where('reference_id', $this->id)
            ->where('movement_type', 'sold')
            ->get();
        
        $totalCost = $movements->sum('total_cost');
        
        // Log for debugging if no movements found but invoice has inventory items that track stock
        $inventoryItems = $this->items()
            ->whereNotNull('inventory_item_id')
            ->with('inventoryItem')
            ->get();
        
        $itemsThatShouldHaveMovements = $inventoryItems->filter(function ($item) {
            return $item->inventoryItem && 
                   $item->inventoryItem->track_stock && 
                   $item->inventoryItem->item_type === 'product';
        });
        
        if ($totalCost == 0 && $itemsThatShouldHaveMovements->count() > 0) {
            \Log::warning('SalesInvoice::calculateCostOfGoodsSold - No movements found but invoice has inventory items', [
                'invoice_id' => $this->id,
                'invoice_number' => $this->invoice_number,
                'movements_count' => $movements->count(),
                'inventory_items_count' => $inventoryItems->count(),
                'items_that_should_have_movements' => $itemsThatShouldHaveMovements->count(),
                'movement_details' => $movements->map(function ($m) {
                    return [
                        'id' => $m->id,
                        'item_id' => $m->item_id,
                        'quantity' => $m->quantity,
                        'total_cost' => $m->total_cost,
                        'reference' => $m->reference,
                        'reference_type' => $m->reference_type,
                        'reference_id' => $m->reference_id,
                    ];
                })->toArray()
            ]);
        }
        
        return $totalCost;
    }

    /**
     * Update invoice totals
     */
    public function updateTotals()
    {
        // Calculate VAT amount from items
        $this->vat_amount = $this->items()->sum('vat_amount');
        
        // Calculate subtotal - always exclude VAT from subtotal
        $lineTotalSum = $this->items()->sum('line_total');
        $this->subtotal = $lineTotalSum - $this->vat_amount;
        
        // Note: discount_amount is now set at invoice level, not summed from items
        
        // WHT is NOT calculated at invoice creation - it's only applied at payment/receipt time
        // Set WHT fields to 0/null during invoice creation
        $this->withholding_tax_amount = 0;
        $this->withholding_tax_rate = 0;
        $this->withholding_tax_type = 'percentage';
        
        // Apply invoice-level discount to total calculation
        // WHT is NOT deducted from invoice total - it's handled at payment time
        $this->total_amount = $this->subtotal + $this->vat_amount - $this->discount_amount;
        
        // Calculate balance_due including late payment fees if already applied
        $baseBalanceDue = $this->total_amount - $this->paid_amount;
        
        // Add late payment fees if they have been applied (check GL transactions)
        $lateFeesApplied = 0;
        if ($this->hasLatePaymentFeesApplied()) {
            $query = GlTransaction::where('transaction_id', $this->id)
                ->where('transaction_type', 'late_payment_fees')
                ->where('nature', 'debit'); // Only count debit (the fee amount)
            
            if ($this->late_payment_fees_type === 'monthly') {
                // For monthly fees, only count current month
                $query->where('date', '>=', now()->startOfMonth());
            }
            
            $lateFeesApplied = $query->sum('amount');
        }
        
        $this->balance_due = $baseBalanceDue + $lateFeesApplied;
        $this->save();
    }

    /**
     * Record payment for this invoice
     */
    public function recordPayment($amount, $paymentDate = null, $bankAccountId = null, $description = null, $cashDepositId = null, $paymentExchangeRate = null, $whtTreatment = null, $whtRate = 0, $vatMode = 'EXCLUSIVE', $vatRate = 18)
    {
        $user = auth()->user();
        $paymentDate = $paymentDate ?? now();
        $description = $description ?? "Receipt for Invoice #{$this->invoice_number}";

        // Get payment exchange rate using FxTransactionRateService if not provided
        if ($paymentExchangeRate === null) {
            $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', $this->company->functional_currency ?? 'TZS');
            $invoiceCurrency = $this->currency ?? $functionalCurrency;
            
            if ($invoiceCurrency !== $functionalCurrency) {
                $fxTransactionRateService = app(\App\Services\FxTransactionRateService::class);
                $rateResult = $fxTransactionRateService->getTransactionRate(
                    $invoiceCurrency,
                    $functionalCurrency,
                    $paymentDate,
                    $this->company_id,
                    null // No user-provided rate, use system rate
                );
                $paymentRate = $rateResult['rate'];
            } else {
                $paymentRate = 1.000000;
            }
        } else {
            $paymentRate = $paymentExchangeRate;
        }

        if ($cashDepositId) {
            // Cash Deposit Payment - Create Journal Entry (WHT not typically applicable for cash deposits)
            return $this->recordCashDepositPayment($amount, $paymentDate, $description, $cashDepositId, $paymentRate);
        } else {
            // Bank Payment - Create Receipt Entry (with WHT support)
            return $this->recordBankPayment($amount, $paymentDate, $bankAccountId, $description, $whtTreatment, $whtRate, $vatMode, $vatRate, $paymentRate);
        }
    }

    /**
     * Handle foreign exchange gain/loss on payment
     */
    private function handleForeignExchangeGainLoss($paymentAmount, $paymentExchangeRate, $paymentDate)
    {
        $user = auth()->user();
        $userId = $user ? $user->id : ($this->created_by ?? 1);

        // Calculate the difference between invoice rate and payment rate
        $invoiceAmountInTZS = $paymentAmount * $this->exchange_rate;
        $paymentAmountInTZS = $paymentAmount * $paymentExchangeRate;
        $fxDifference = $paymentAmountInTZS - $invoiceAmountInTZS;

        if (abs($fxDifference) > 0.01) { // Only create GL entry if difference is significant
            $transactions = [];
            $receivableAccountId = $this->getReceivableAccountId();

            if ($fxDifference > 0) {
                // FX Gain - Credit FX Gain Account, Debit Accounts Receivable (reduce receivable)
                $transactions[] = [
                    'chart_account_id' => $this->getFxGainAccountId(),
                    'customer_id' => $this->customer_id,
                    'amount' => $fxDifference,
                    'nature' => 'credit',
                    'transaction_id' => $this->id,
                    'transaction_type' => 'sales_invoice_fx_gain',
                    'date' => $paymentDate,
                    'description' => "FX Gain - Invoice #{$this->invoice_number} - Rate difference: {$this->exchange_rate} to {$paymentExchangeRate}",
                    'branch_id' => $this->branch_id,
                    'user_id' => $userId,
                ];
                // Debit Accounts Receivable to reduce it by the gain amount
                $transactions[] = [
                    'chart_account_id' => $receivableAccountId,
                    'customer_id' => $this->customer_id,
                    'amount' => $fxDifference,
                    'nature' => 'debit',
                    'transaction_id' => $this->id,
                    'transaction_type' => 'sales_invoice_fx_gain',
                    'date' => $paymentDate,
                    'description' => "FX Gain Adjustment - Invoice #{$this->invoice_number} - Reduce receivable by gain",
                    'branch_id' => $this->branch_id,
                    'user_id' => $userId,
                ];
            } else {
                // FX Loss - Debit FX Loss Account, Credit Accounts Receivable (increase receivable)
                $transactions[] = [
                    'chart_account_id' => $this->getFxLossAccountId(),
                    'customer_id' => $this->customer_id,
                    'amount' => abs($fxDifference),
                    'nature' => 'debit',
                    'transaction_id' => $this->id,
                    'transaction_type' => 'sales_invoice_fx_loss',
                    'date' => $paymentDate,
                    'description' => "FX Loss - Invoice #{$this->invoice_number} - Rate difference: {$this->exchange_rate} to {$paymentExchangeRate}",
                    'branch_id' => $this->branch_id,
                    'user_id' => $userId,
                ];
                // Credit Accounts Receivable to increase it by the loss amount
                $transactions[] = [
                    'chart_account_id' => $receivableAccountId,
                    'customer_id' => $this->customer_id,
                    'amount' => abs($fxDifference),
                    'nature' => 'credit',
                    'transaction_id' => $this->id,
                    'transaction_type' => 'sales_invoice_fx_loss',
                    'date' => $paymentDate,
                    'description' => "FX Loss Adjustment - Invoice #{$this->invoice_number} - Increase receivable by loss",
                    'branch_id' => $this->branch_id,
                    'user_id' => $userId,
                ];
            }

            // Create GL transactions
            foreach ($transactions as $transaction) {
                \App\Models\GlTransaction::create($transaction);
            }
        }
    }

    /**
     * Record bank payment (Receipt + GL)
     */
    public function recordBankPayment($amount, $paymentDate, $bankAccountId, $description, $whtTreatment = null, $whtRate = 0, $vatMode = 'EXCLUSIVE', $vatRate = 18, $paymentExchangeRate = null)
    {
        $user = auth()->user();
        $userId = $user ? $user->id : ($this->created_by ?? 1); // Fallback to created_by or default user ID

        // Calculate WHT if provided (for AR, only Exclusive/Inclusive, no Gross-Up)
        $whtService = new \App\Services\WithholdingTaxService();
        $totalAmount = (float) $amount;
        $whtTreatment = $whtTreatment ?? 'EXCLUSIVE';
        
        // Validate AR treatment (no Gross-Up for receipts)
        if (!$whtService->isValidARTreatment($whtTreatment)) {
            $whtTreatment = 'EXCLUSIVE';
        }
        
        $whtRate = (float) $whtRate;
        $receiptWHT = 0;
        $receiptNetReceivable = $totalAmount;
        $receiptBaseAmount = $totalAmount;
        $receiptVatAmount = 0;
        
        if ($whtRate > 0 && $whtTreatment !== 'NONE') {
            $whtCalc = $whtService->calculateWHTForAR($totalAmount, $whtRate, $whtTreatment, $vatMode, $vatRate);
            $receiptWHT = $whtCalc['wht_amount'];
            $receiptNetReceivable = $whtCalc['net_receivable'];
            $receiptBaseAmount = $whtCalc['base_amount'];
            $receiptVatAmount = $whtCalc['vat_amount'];
        } elseif ($vatMode !== 'NONE' && $vatRate > 0) {
            // Calculate VAT even if no WHT
            if ($vatMode === 'INCLUSIVE') {
                $receiptBaseAmount = round($totalAmount / (1 + ($vatRate / 100)), 2);
                $receiptVatAmount = round($totalAmount - $receiptBaseAmount, 2);
            } else {
                // EXCLUSIVE
                $receiptBaseAmount = round($totalAmount / (1 + ($vatRate / 100)), 2);
                $receiptVatAmount = round($totalAmount - $receiptBaseAmount, 2);
            }
        }

        // Get functional currency and check if conversion is needed
        $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', $this->company->functional_currency ?? 'TZS');
        $invoiceCurrency = $this->currency ?? $functionalCurrency;
        $invoiceExchangeRate = $this->exchange_rate ?? 1.000000;
        $paymentRate = $paymentExchangeRate ?? $invoiceExchangeRate; // Use payment rate if provided, otherwise invoice rate
        $needsConversion = ($invoiceCurrency !== $functionalCurrency && $invoiceExchangeRate != 1.000000);
        
        // Convert amounts to local currency for GL transactions
        // Use invoice rate for receivable (what was originally recorded)
        $convertToLCYInvoiceRate = function($fcyAmount) use ($needsConversion, $invoiceExchangeRate) {
            return $needsConversion ? round($fcyAmount * $invoiceExchangeRate, 2) : $fcyAmount;
        };
        // Use payment rate for bank (actual amount received)
        $convertToLCYPaymentRate = function($fcyAmount) use ($needsConversion, $paymentRate) {
            return $needsConversion ? round($fcyAmount * $paymentRate, 2) : $fcyAmount;
        };

        // Create receipt record
        $receipt = Receipt::create([
            'reference' => $this->id,
            'reference_type' => 'sales_invoice',
            'reference_number' => $this->invoice_number,
            'amount' => $totalAmount, // Total amount in invoice currency (may include VAT)
            'currency' => $invoiceCurrency, // Store invoice currency
            'exchange_rate' => $invoiceExchangeRate, // Store invoice exchange rate
            'amount_fcy' => $needsConversion ? $totalAmount : null, // Foreign currency amount
            'amount_lcy' => $needsConversion ? $convertToLCYInvoiceRate($totalAmount) : $totalAmount, // Local currency amount at invoice rate
            'wht_treatment' => $whtTreatment,
            'wht_rate' => $whtRate,
            'wht_amount' => $receiptWHT,
            'net_receivable' => $receiptNetReceivable,
            'vat_mode' => $vatMode,
            'vat_amount' => $receiptVatAmount,
            'base_amount' => $receiptBaseAmount,
            'date' => $paymentDate,
            'description' => $description,
            'user_id' => $userId,
            'bank_account_id' => $bankAccountId,
            'payee_type' => 'customer',
            'payee_id' => $this->customer_id,
            'payee_name' => $this->customer->name,
            'branch_id' => $this->branch_id,
            'approved' => true,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        // Create receipt item
        \App\Models\ReceiptItem::create([
            'receipt_id' => $receipt->id,
            'chart_account_id' => $this->getReceivableAccountId() ? $this->getReceivableAccountId() : 18, // Uses proper fallback logic 
            'amount' => $totalAmount,
            'wht_treatment' => $whtTreatment,
            'wht_rate' => $whtRate,
            'wht_amount' => $receiptWHT,
            'base_amount' => $receiptBaseAmount,
            'net_receivable' => $receiptNetReceivable,
            'vat_mode' => $vatMode,
            'vat_amount' => $receiptVatAmount,
            'description' => $description,
        ]);

        // Create GL transactions for bank payment (with WHT handling and FX gain/loss)
        // Pass the full amount (before WHT) so GL function can properly calculate bank debit and receivable credit
        // The function will debit bank with (amount - WHT) and credit receivables with full amount
        $this->createBankPaymentGlTransactions($receipt, $totalAmount, $paymentDate, $description, $receiptWHT, $whtTreatment, $paymentRate);

        // Create early payment discount GL transactions if applicable
        $this->createEarlyPaymentDiscountTransactions($amount, $paymentDate);

        // Update invoice paid amount
        $this->increment('paid_amount', $amount);
        $this->balance_due = $this->total_amount - $this->paid_amount;
        
        // Update status if fully paid
        if ($this->paid_amount >= $this->total_amount) {
            $this->status = 'paid';
        } else {
            $this->status = 'sent';
        }
        
        $this->save();

        // Sync linked Opening Balance amounts if applicable
        $this->syncLinkedOpeningBalance();

        return $receipt;
    }

    /**
     * Record cash deposit payment (Payment + GL)
     */
    public function recordCashDepositPayment($amount, $paymentDate, $description, $cashDepositId)
    {
        $user = auth()->user();
        $userId = $user ? $user->id : ($this->created_by ?? 1); // Fallback to created_by or default user ID
        
        // Create journal entry for cash deposit payment
        $journal = \App\Models\Journal::create([
            'date' => $paymentDate,
            'reference' => $this->invoice_number,
            'reference_type' => 'sales_invoice_payment',
            'customer_id' => $this->customer_id,
            'description' => $description ?: "Cash deposit payment for Invoice #{$this->invoice_number}",
            'branch_id' => $this->branch_id,
            'user_id' => $userId,
        ]);
        
        // Create journal items for double-entry accounting
        $this->createCashDepositJournalItems($journal, $amount, $paymentDate, $description);

        // Create early payment discount GL transactions if applicable
        $this->createEarlyPaymentDiscountTransactions($amount, $paymentDate);

        // Update invoice paid amount
        $this->increment('paid_amount', $amount);
        $this->balance_due = $this->total_amount - $this->paid_amount;
        
        // Update status if fully paid
        if ($this->paid_amount >= $this->total_amount) {
            $this->status = 'paid';
        } else {
            $this->status = 'sent';
        }
        
        $this->save();

        // Sync linked Opening Balance amounts if applicable
        $this->syncLinkedOpeningBalance();

        return $journal;
    }

    /**
     * Create GL transactions for bank payment (with WHT support and FX gain/loss)
     */
    public function createBankPaymentGlTransactions($receipt, $amount, $paymentDate, $description, $whtAmount = 0, $whtTreatment = 'EXCLUSIVE', $paymentExchangeRate = null)
    {
        $user = auth()->user();
        $userId = $user ? $user->id : ($this->created_by ?? 1); // Fallback to created_by or default user ID
        $bankAccount = $receipt->bankAccount;

        // Check if this bank account is in a completed reconciliation period - prevent posting
        if ($bankAccount) {
            $isInCompletedReconciliation = \App\Services\BankReconciliationService::isChartAccountInCompletedReconciliation(
                $bankAccount->chart_account_id,
                $paymentDate
            );
            
            if ($isInCompletedReconciliation) {
                \Log::warning('SalesInvoice::createBankPaymentGlTransactions - Cannot post: Bank account is in a completed reconciliation period', [
                    'invoice_id' => $this->id,
                    'invoice_number' => $this->invoice_number,
                    'receipt_id' => $receipt->id,
                    'bank_account_id' => $bankAccount->id,
                    'chart_account_id' => $bankAccount->chart_account_id,
                    'payment_date' => $paymentDate
                ]);
                throw new \Exception("Cannot post payment: Bank account is in a completed reconciliation period for date {$paymentDate}.");
            }
        }

        // Get functional currency and check if conversion is needed
        $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', $this->company->functional_currency ?? 'TZS');
        $invoiceCurrency = $this->currency ?? $functionalCurrency;
        $invoiceExchangeRate = $this->exchange_rate ?? 1.000000;
        $paymentRate = $paymentExchangeRate ?? $invoiceExchangeRate; // Use payment rate if provided, otherwise invoice rate
        $needsConversion = ($invoiceCurrency !== $functionalCurrency && $invoiceExchangeRate != 1.000000);
        
        // Convert amounts using invoice rate (for receivable - what was originally recorded)
        $convertToLCYInvoiceRate = function($fcyAmount) use ($needsConversion, $invoiceExchangeRate) {
            return $needsConversion ? round($fcyAmount * $invoiceExchangeRate, 2) : $fcyAmount;
        };
        // Convert amounts using payment rate (for bank - actual amount received)
        $convertToLCYPaymentRate = function($fcyAmount) use ($needsConversion, $paymentRate) {
            return $needsConversion ? round($fcyAmount * $paymentRate, 2) : $fcyAmount;
        };

        $transactions = [];
        
        // Get WHT Receivable account from system settings
        $whtReceivableAccountId = (int) (\App\Models\SystemSetting::where('key', 'wht_receivable_account_id')->value('value') ?? 0);
        if (!$whtReceivableAccountId) {
            // Fallback: try to find WHT Receivable account by name
            $whtAccount = \App\Models\ChartAccount::where('account_name', 'like', '%WHT%Receivable%')
                ->orWhere('account_name', 'like', '%Withholding%Tax%Receivable%')
                ->first();
            $whtReceivableAccountId = $whtAccount ? $whtAccount->id : 0;
        }

        // The $amount parameter is the net_receivable (full receipt amount that should be credited to Trade Receivables)
        // Convert to local currency using invoice rate (what was originally recorded)
        $amountLCYInvoiceRate = $convertToLCYInvoiceRate($amount);
        // Convert to local currency using payment rate (actual amount received)
        $amountLCYPaymentRate = $convertToLCYPaymentRate($amount);
        
        // Calculate WHT amounts
        $whtAmountLCYInvoiceRate = $convertToLCYInvoiceRate($whtAmount);
        $whtAmountLCYPaymentRate = $convertToLCYPaymentRate($whtAmount);
        
        // Calculate net amount received in bank (after WHT deduction) using payment rate
        $netReceivableLCYPaymentRate = $amountLCYPaymentRate - $whtAmountLCYPaymentRate;
        
        // Calculate FX gain/loss
        $fxDifference = $amountLCYPaymentRate - $amountLCYInvoiceRate;
        $fxWHTDifference = $whtAmountLCYPaymentRate - $whtAmountLCYInvoiceRate;
        $totalFxDifference = $fxDifference + $fxWHTDifference; // Total FX difference including WHT

        // Add currency info to description if conversion was done
        $addCurrencyInfo = function($desc) use ($needsConversion, $invoiceCurrency, $functionalCurrency, $invoiceExchangeRate, $paymentRate) {
            if ($needsConversion) {
                if ($invoiceExchangeRate != $paymentRate) {
                    return $desc . " [FCY: {$invoiceCurrency}, Invoice Rate: {$invoiceExchangeRate}, Payment Rate: {$paymentRate}, Converted to {$functionalCurrency}]";
                }
                return $desc . " [FCY: {$invoiceCurrency}, Rate: {$invoiceExchangeRate}, Converted to {$functionalCurrency}]";
            }
            return $desc;
        };

        // 1. Debit Bank Account (or Cash if no bank account) - with net amount received at PAYMENT RATE
        if ($bankAccount) {
            $transactions[] = [
                'chart_account_id' => $bankAccount->chart_account_id,
                'customer_id' => $this->customer_id,
                'amount' => $netReceivableLCYPaymentRate, // Net amount received at PAYMENT RATE (actual amount received)
                'nature' => 'debit',
                'transaction_id' => $receipt->id,
                'transaction_type' => 'receipt',
                'date' => $paymentDate,
                'description' => $addCurrencyInfo($description),
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];
        } else {
            // Cash payment - use default cash account
            $cashAccountId = \App\Models\SystemSetting::where('key', 'inventory_default_cash_account')->value('value') ?? 1;
            $transactions[] = [
                'chart_account_id' => $cashAccountId,
                'customer_id' => $this->customer_id,
                'amount' => $netReceivableLCYPaymentRate, // Net amount received at PAYMENT RATE
                'nature' => 'debit',
                'transaction_id' => $receipt->id,
                'transaction_type' => 'receipt',
                'date' => $paymentDate,
                'description' => $addCurrencyInfo($description),
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];
        }

        // 2. Debit WHT Receivable (if WHT exists) - at PAYMENT RATE
        if ($whtAmountLCYPaymentRate > 0 && $whtReceivableAccountId > 0) {
            $transactions[] = [
                'chart_account_id' => $whtReceivableAccountId,
                'customer_id' => $this->customer_id,
                'amount' => $whtAmountLCYPaymentRate, // WHT amount at PAYMENT RATE
                'nature' => 'debit',
                'transaction_id' => $receipt->id,
                'transaction_type' => 'receipt',
                'date' => $paymentDate,
                'description' => $addCurrencyInfo("WHT Receivable - {$description}"),
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];
        }

        // 3. Credit Accounts Receivable - at INVOICE RATE (what was originally recorded)
        $account_receivable = $this->getReceivableAccountId();
        $transactions[] = [
            'chart_account_id' => $account_receivable ?? 18, // Uses proper fallback logic
            'customer_id' => $this->customer_id,
            'amount' => $amountLCYInvoiceRate, // Full receipt amount at INVOICE RATE (what was originally recorded)
            'nature' => 'credit',
            'transaction_id' => $receipt->id,
            'transaction_type' => 'receipt',
            'date' => $paymentDate,
            'description' => $addCurrencyInfo($description),
            'branch_id' => $this->branch_id,
            'user_id' => $userId,
        ];
        
        // 4. FX Gain/Loss entry (if payment rate differs from invoice rate)
        if (abs($totalFxDifference) > 0.01) {
            $fxGainAccountId = $this->getFxGainAccountId();
            $fxLossAccountId = $this->getFxLossAccountId();
            
            if ($totalFxDifference > 0) {
                // FX Gain - Credit FX Gain Account
                if ($fxGainAccountId) {
                    $transactions[] = [
                        'chart_account_id' => $fxGainAccountId,
                        'customer_id' => $this->customer_id,
                        'amount' => $totalFxDifference,
                        'nature' => 'credit',
                        'transaction_id' => $receipt->id,
                        'transaction_type' => 'receipt',
                        'date' => $paymentDate,
                        'description' => "FX Gain - Invoice #{$this->invoice_number} - Rate difference: {$invoiceExchangeRate} to {$paymentRate}",
                        'branch_id' => $this->branch_id,
                        'user_id' => $userId,
                    ];
                } else {
                    \Log::warning('FX Gain account not configured in Foreign Exchange Settings. FX gain not posted.', [
                        'invoice_id' => $this->id,
                        'invoice_number' => $this->invoice_number,
                        'fx_gain_amount' => $totalFxDifference
                    ]);
                }
            } else {
                // FX Loss - Debit FX Loss Account
                if ($fxLossAccountId) {
                    $transactions[] = [
                        'chart_account_id' => $fxLossAccountId,
                        'customer_id' => $this->customer_id,
                        'amount' => abs($totalFxDifference),
                        'nature' => 'debit',
                        'transaction_id' => $receipt->id,
                        'transaction_type' => 'receipt',
                        'date' => $paymentDate,
                        'description' => "FX Loss - Invoice #{$this->invoice_number} - Rate difference: {$invoiceExchangeRate} to {$paymentRate}",
                        'branch_id' => $this->branch_id,
                        'user_id' => $userId,
                    ];
                } else {
                    \Log::warning('FX Loss account not configured in Foreign Exchange Settings. FX loss not posted.', [
                        'invoice_id' => $this->id,
                        'invoice_number' => $this->invoice_number,
                        'fx_loss_amount' => abs($totalFxDifference)
                    ]);
                }
            }
        }

        // Create all transactions
        foreach ($transactions as $transaction) {
            GlTransaction::create($transaction);
        }
    }

    /**
     * Create GL transactions for bank payment (for payments)
     */
    private function createBankPaymentGlTransactionsForPayment($payment, $amount, $paymentDate, $description)
    {
        $user = auth()->user();
        $userId = $user ? $user->id : ($this->created_by ?? 1); // Fallback to created_by or default user ID
        $bankAccount = $payment->bankAccount;

        $transactions = [];

        // 1. Debit Bank Account (or Cash if no bank account)
        if ($bankAccount) {
            $transactions[] = [
                'chart_account_id' => $bankAccount->chart_account_id,
                'customer_id' => $this->customer_id,
                'amount' => $amount,
                'nature' => 'debit',
                'transaction_id' => $payment->id,
                'transaction_type' => 'payment',
                'date' => $paymentDate,
                'description' => $description,
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];
        } else {
            // Cash payment - use default cash account
            $cashAccountId = \App\Models\SystemSetting::where('key', 'inventory_default_cash_account')->value('value') ?? 1;
            $transactions[] = [
                'chart_account_id' => $cashAccountId,
                'customer_id' => $this->customer_id,
                'amount' => $amount,
                'nature' => 'debit',
                'transaction_id' => $payment->id,
                'transaction_type' => 'payment',
                'date' => $paymentDate,
                'description' => $description,
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];
        }

        // 2. Credit Accounts Receivable
        $account_receivable = $this->getReceivableAccountId();
        $transactions[] = [
            'chart_account_id' => $account_receivable ?? 18, // Uses proper fallback logic
            'customer_id' => $this->customer_id,
            'amount' => $amount,
            'nature' => 'credit',
            'transaction_id' => $payment->id,
            'transaction_type' => 'payment',
            'date' => $paymentDate,
            'description' => $description,
            'branch_id' => $this->branch_id,
            'user_id' => $userId,
        ];

        // Create all transactions
        foreach ($transactions as $transaction) {
            GlTransaction::create($transaction);
        }
    }

    /**
     * Create journal items for cash deposit payment (proper double-entry)
     */
    public function createCashDepositJournalItems($journal, $amount, $paymentDate, $description)
    {
        $journalItems = [];

        // 1. Debit Cash Deposit Account (ID 28)
        $cashDepositAccountId = 28; // Cash Deposits account
        
        $journalItems[] = [
            'journal_id' => $journal->id,
            'chart_account_id' => $cashDepositAccountId,
            'amount' => $amount,
            'nature' => 'debit',
            'description' => $description ?: "Debit cash deposit - {$journal->description}",
        ];

        // 2. Credit Accounts Receivable
        $account_receivable = $this->getReceivableAccountId();
        
        $journalItems[] = [
            'journal_id' => $journal->id,
            'chart_account_id' => $account_receivable ?? 18, // Uses proper fallback logic
            'amount' => $amount,
            'nature' => 'credit',
            'description' => $description ?: "Credit accounts receivable - {$journal->description}",
        ];

        // Create all journal items
        foreach ($journalItems as $itemData) {
            \App\Models\JournalItem::create($itemData);
        }

        // Also create corresponding GL transactions for reporting
        $this->createCashDepositGlTransactions($journal, $amount, $paymentDate, $description);
    }

    /**
     * Create GL transactions for cash deposit payment (for reporting compatibility)
     */
    public function createCashDepositGlTransactions($journal, $amount, $paymentDate, $description)
    {
        $user = auth()->user();
        $userId = $user ? $user->id : ($this->created_by ?? 1);

        // Check if cash deposit account is a bank account in a completed reconciliation period
        $cashDepositAccountId = 28; // Cash Deposits account
        $isInCompletedReconciliation = \App\Services\BankReconciliationService::isChartAccountInCompletedReconciliation(
            $cashDepositAccountId,
            $paymentDate
        );
        
        if ($isInCompletedReconciliation) {
            \Log::warning('SalesInvoice::createCashDepositGlTransactions - Cannot post: Cash deposit account is in a completed reconciliation period', [
                'invoice_id' => $this->id,
                'invoice_number' => $this->invoice_number,
                'journal_id' => $journal->id,
                'chart_account_id' => $cashDepositAccountId,
                'payment_date' => $paymentDate
            ]);
            throw new \Exception("Cannot post cash deposit payment: Account is in a completed reconciliation period for date {$paymentDate}.");
        }

        $transactions = [];

        // 1. Debit Cash Deposit Account (ID 28)

        $transactions[] = [
            'chart_account_id' => $cashDepositAccountId,
            'customer_id' => $this->customer_id,
            'amount' => $amount,
            'nature' => 'debit',
            'transaction_id' => $journal->id,
            'transaction_type' => 'journal',
            'date' => $paymentDate,
            'description' => $description,
            'branch_id' => $this->branch_id,
            'user_id' => $userId,
        ];

        // 2. Credit Accounts Receivable
        $account_receivable = $this->getReceivableAccountId();
        $transactions[] = [
            'chart_account_id' => $account_receivable ?? 18, // Uses proper fallback logic
            'customer_id' => $this->customer_id,
            'amount' => $amount,
            'nature' => 'credit',
            'transaction_id' => $journal->id,
            'transaction_type' => 'journal',
            'date' => $paymentDate,
            'description' => $description,
            'branch_id' => $this->branch_id,
            'user_id' => $userId,
        ];

        // Create all GL transactions
        foreach ($transactions as $transaction) {
            \App\Models\GlTransaction::create($transaction);
        }
    }

    /**
     * Get all receipts for this invoice
     * Matches by reference_number = invoice_number (works for both direct and voucher payments)
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class, 'reference_number', 'invoice_number')
            ->where('reference_type', 'sales_invoice');
    }

    /**
     * Get all payments for this invoice
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'reference', 'invoice_number')
            ->where('reference_type', 'sales_invoice');
    }

    /**
     * Cash deposit invoice payments post as journal entries (not receipts / payment vouchers).
     */
    public function cashDepositPaymentJournals(): HasMany
    {
        return $this->hasMany(Journal::class, 'reference', 'invoice_number')
            ->where('reference_type', 'sales_invoice_payment');
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    /**
     * Reverse a payment by permanently deleting the receipt and its GL entries
     */
    public function reversePayment($receiptId, $reason = null)
    {
        $receipt = Receipt::findOrFail($receiptId);
        
        // Verify the receipt belongs to this invoice (match by ID or invoice number)
        if ($receipt->reference_type != 'sales_invoice' || ($receipt->reference != $this->id && $receipt->reference_number != $this->invoice_number)) {
            throw new \Exception('Receipt does not belong to this invoice');
        }

        // Permanently remove original GL transactions for this receipt
        \App\Models\GlTransaction::where('transaction_id', $receipt->id)
            ->where('transaction_type', 'receipt')
            ->delete();

        // Update invoice paid amount
        $this->decrement('paid_amount', $receipt->amount);
        $this->balance_due = $this->total_amount - $this->paid_amount;
        
        // Update status
        if ($this->paid_amount <= 0) {
            $this->status = 'sent';
        } else {
            $this->status = 'sent'; // Partial payment
        }
        
        $this->save();

        // Sync linked Opening Balance amounts if applicable
        $this->syncLinkedOpeningBalance();

        // Delete receipt items, then the receipt itself
        \App\Models\ReceiptItem::where('receipt_id', $receipt->id)->delete();
        $receipt->delete();

        return $receipt;
    }

    /**
     * Sync amounts to linked Opening Balance (if this invoice was generated from OB)
     */
    public function syncLinkedOpeningBalance(): void
    {
        $openingBalance = \App\Models\Sales\OpeningBalance::where('sales_invoice_id', $this->id)->first();
        if (!$openingBalance) {
            return;
        }
        $openingBalance->paid_amount = min((float)$this->paid_amount, (float)$openingBalance->amount);
        $openingBalance->balance_due = max((float)$openingBalance->amount - (float)$openingBalance->paid_amount, 0);
        $openingBalance->status = $openingBalance->balance_due <= 0.0 ? 'closed' : 'posted';
        $openingBalance->save();
    }

    /**
     * Create reversal GL transactions
     */
    private function createReversalGlTransactions($receipt, $description)
    {
        $user = auth()->user();
        $userId = $user ? $user->id : ($this->created_by ?? 1);
        $bankAccount = $receipt->bankAccount;

        $transactions = [];

        // 1. Credit Bank Account (or Cash if no bank account) - Reverse the debit
        if ($bankAccount) {
            $transactions[] = [
                'chart_account_id' => $bankAccount->chart_account_id,
                'customer_id' => $this->customer_id,
                'amount' => $receipt->amount,
                'nature' => 'credit',
                'transaction_id' => $receipt->id,
                'transaction_type' => 'receipt_reversal',
                'date' => now(),
                'description' => $description,
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];
        } else {
            // Use cash account if no bank account specified
            $cashAccountId = SystemSetting::where('key', 'inventory_default_cash_account')->value('value') ?? 1;
            $transactions[] = [
                'chart_account_id' => $cashAccountId,
                'customer_id' => $this->customer_id,
                'amount' => $receipt->amount,
                'nature' => 'credit',
                'transaction_id' => $receipt->id,
                'transaction_type' => 'receipt_reversal',
                'date' => now(),
                'description' => $description,
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];
        }

        // 2. Debit Accounts Receivable - Reverse the credit
        $transactions[] = [
            'chart_account_id' => $this->getReceivableAccountId() ?? 18, // Uses proper fallback logic
            'customer_id' => $this->customer_id,
            'amount' => $receipt->amount,
            'nature' => 'debit',
            'transaction_id' => $receipt->id,
            'transaction_type' => 'receipt_reversal',
            'date' => now(),
            'description' => $description,
            'branch_id' => $this->branch_id,
            'user_id' => $userId,
        ];

        // 3. Handle Cash Deposit (if applicable) - Reverse the credit
        if ($receipt->cash_deposit_id) {
            $cashDeposit = CashDeposit::find($receipt->cash_deposit_id);
            if ($cashDeposit) {
                $transactions[] = [
                    'chart_account_id' => $cashDeposit->chart_account_id,
                    'customer_id' => $this->customer_id,
                    'amount' => $receipt->amount,
                    'nature' => 'debit',
                    'transaction_id' => $receipt->id,
                    'transaction_type' => 'receipt_reversal',
                    'date' => now(),
                    'description' => $description,
                    'branch_id' => $this->branch_id,
                    'user_id' => $userId,
                ];
            }
        }

        // Create all transactions
        foreach ($transactions as $transaction) {
            GlTransaction::create($transaction);
        }
    }

    /**
     * Roll back paid_amount / status when a cash-deposit journal (sales_invoice_payment) is removed.
     * Caller must delete journal GL rows and journal items after this (or before — amount is read from $journal first).
     */
    public function reverseCashDepositJournalPayment(Journal $journal): void
    {
        if ($journal->reference_type !== 'sales_invoice_payment' || (string) $journal->reference !== (string) $this->invoice_number) {
            throw new \InvalidArgumentException('Journal is not a cash deposit payment for this invoice.');
        }

        $journal->loadMissing('items');
        $cashDepositChartId = 28;
        $amount = (float) $journal->items->where('nature', 'debit')->where('chart_account_id', $cashDepositChartId)->sum('amount');
        if ($amount <= 0) {
            $amount = (float) $journal->items->where('nature', 'debit')->sum('amount');
        }
        if ($amount <= 0) {
            return;
        }

        $this->refresh();
        $newPaid = max(0, round((float) $this->paid_amount - $amount, 2));
        $this->paid_amount = $newPaid;
        $this->balance_due = max(0, round((float) $this->total_amount - $newPaid, 2));

        if ($this->paid_amount <= 0) {
            $this->status = 'sent';
        } elseif ($this->paid_amount >= ((float) $this->total_amount - 0.0001)) {
            $this->status = 'paid';
        } else {
            $this->status = 'sent';
        }

        $this->save();
        $this->syncLinkedOpeningBalance();
    }

    /**
     * Get payment history for this invoice
     */
    public function getPaymentHistory()
    {
        return $this->receipts()
            ->with(['user', 'bankAccount'])
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue()
    {
        return $this->due_date < now()->toDateString() && $this->status !== 'paid';
    }

    /**
     * Get overdue days
     */
    public function getOverdueDays()
    {
        if (!$this->isOverdue()) {
            return 0;
        }
        
        return (int) round($this->due_date->diffInDays(now()));
    }

    /**
     * Calculate early payment discount amount
     */
    public function calculateEarlyPaymentDiscount()
    {
        if (!$this->early_payment_discount_enabled || $this->balance_due <= 0) {
            return 0;
        }

        $earlyPaymentDate = $this->invoice_date->addDays($this->early_payment_days);
        
        // Check if current date is within early payment period
        if (now()->toDateString() <= $earlyPaymentDate->toDateString()) {
            if ($this->early_payment_discount_type === 'percentage') {
                return $this->balance_due * ($this->early_payment_discount_rate / 100);
            } else {
                return $this->early_payment_discount_rate;
            }
        }

        return 0;
    }

    /**
     * Calculate late payment fees
     */
    public function calculateLatePaymentFees()
    {
        if (!$this->late_payment_fees_enabled || $this->balance_due <= 0) {
            return 0;
        }

        if (!$this->isOverdue()) {
            return 0;
        }

        $overdueDays = $this->getOverdueDays();

        if ($this->late_payment_fees_type === 'monthly') {
            // Calculate monthly fees (30 days per month)
            $months = ceil($overdueDays / 30);
            return $this->balance_due * ($this->late_payment_fees_rate / 100) * $months;
        } else {
            // One-time charge
            return $this->balance_due * ($this->late_payment_fees_rate / 100);
        }
    }

    /**
     * Get the amount due with early payment discount applied
     */
    public function getAmountDueWithEarlyDiscount()
    {
        $earlyDiscount = $this->calculateEarlyPaymentDiscount();
        return max(0, $this->balance_due - $earlyDiscount);
    }

    /**
     * Get the amount due with late payment fees applied
     */
    public function getAmountDueWithLateFees()
    {
        $lateFees = $this->calculateLatePaymentFees();
        return $this->balance_due + $lateFees;
    }

    /**
     * Check if early payment discount is still valid
     */
    public function isEarlyPaymentDiscountValid()
    {
        if (!$this->early_payment_discount_enabled) {
            return false;
        }

        $earlyPaymentDate = $this->invoice_date->addDays($this->early_payment_days);
        return now()->toDateString() <= $earlyPaymentDate->toDateString();
    }

    /**
     * Get early payment discount expiry date
     */
    public function getEarlyPaymentDiscountExpiryDate()
    {
        if (!$this->early_payment_discount_enabled) {
            return null;
        }

        return $this->invoice_date->addDays($this->early_payment_days);
    }

    /**
     * Get early payment discount text
     */
    public function getEarlyPaymentDiscountText()
    {
        if (!$this->early_payment_discount_enabled) {
            return 'No early payment discount';
        }

        $type = $this->early_payment_discount_type === 'percentage' ? '%' : 'TSh';
        $rate = $this->early_payment_discount_rate;
        $days = $this->early_payment_days;

        return "{$rate}{$type} discount if paid within {$days} days";
    }

    /**
     * Check if late payment fees have already been applied
     * For monthly fees: checks if applied this month
     * For one-time fees: checks if ever applied
     */
    public function hasLatePaymentFeesApplied(): bool
    {
        if (!$this->late_payment_fees_enabled) {
            return false;
        }

        // Query directly since glTransactions() relationship filters by transaction_type = 'sales_invoice'
        $query = GlTransaction::where('transaction_id', $this->id)
            ->where('transaction_type', 'late_payment_fees');
        
        if ($this->late_payment_fees_type === 'monthly') {
            // For monthly fees, only check current month
            $query->where('date', '>=', now()->startOfMonth());
        }
        // For one-time fees, check all time (no date filter)
        
        return $query->exists();
    }

    /**
     * Get late payment fees text
     */
    public function getLatePaymentFeesText()
    {
        if (!$this->late_payment_fees_enabled) {
            return 'No late payment fees';
        }

        $type = $this->late_payment_fees_type === 'monthly' ? 'monthly' : 'one-time';
        $rate = $this->late_payment_fees_rate;

        return "{$rate}% {$type} fee for late payment";
    }

    /**
     * Convert total_amount to words using shared helper.
     */
    public function getAmountInWords()
    {
        return AmountInWords::convert($this->total_amount);
    }
}
