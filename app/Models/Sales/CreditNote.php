<?php

namespace App\Models\Sales;

use App\Helpers\AmountInWords;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Inventory\Item as InventoryItem;
use App\Models\Inventory\Movement as InventoryMovement;
use App\Models\User;
use App\Models\Branch;
use App\Models\Company;
use App\Models\GlTransaction;
use App\Models\SystemSetting;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Vinkla\Hashids\Facades\Hashids;

class CreditNote extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'credit_note_number',
        'sales_invoice_id',
        'reference_invoice_id',
        'customer_id',
        'credit_note_date',
        'status',
        'type',
        'reason_code',
        'reason',
        'notes',
        'terms_conditions',
        'refund_now',
        'bank_account_id',
        'return_to_stock',
        'restocking_fee_percentage',
        'restocking_fee_amount',
        'restocking_fee_vat',
        'currency',
        'exchange_rate',
        'fx_gain_loss',
        'reference_document',
        'warehouse_id',
        'approval_notes',
        'submitted_at',
        'submitted_by',
        'tax_calculation_details',
        'posting_details',
        'document_series',
        'subtotal',
        'vat_amount',
        'discount_amount',
        'total_amount',
        'original_amount',
        'net_credit_amount',
        'gross_credit_amount',
        'applied_amount',
        'remaining_amount',
        'vat_rate',
        'vat_type',
        'attachment',
        'branch_id',
        'company_id',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'credit_note_date' => 'date',
        'refund_now' => 'boolean',
        'return_to_stock' => 'boolean',
        'restocking_fee_percentage' => 'decimal:2',
        'restocking_fee_amount' => 'decimal:2',
        'restocking_fee_vat' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'fx_gain_loss' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'tax_calculation_details' => 'array',
        'posting_details' => 'array',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'net_credit_amount' => 'decimal:2',
        'gross_credit_amount' => 'decimal:2',
        'applied_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'vat_rate' => 'decimal:2',
    ];

    protected $dates = ['deleted_at'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($creditNote) {
            if (empty($creditNote->credit_note_number)) {
                $creditNote->credit_note_number = self::generateCreditNoteNumber();
            }
        });
    }

    /**
     * Generate unique credit note number
     */
    public static function generateCreditNoteNumber(): string
    {
        $prefix = 'CN';
        $year = date('Y');
        $month = date('m');

        $lastCreditNote = self::where('credit_note_number', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('credit_note_number', 'desc')
            ->first();

        if ($lastCreditNote) {
            $lastNumber = (int) substr($lastCreditNote->credit_note_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */
    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
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

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function glTransactions(): HasMany
    {
        return $this->hasMany(GlTransaction::class, 'transaction_id')
            ->where('transaction_type', 'credit_note');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(CreditNoteApplication::class);
    }

    public function referenceInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'reference_invoice_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\App\Models\InventoryLocation::class, 'warehouse_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Accessors
     */
    public function getEncodedIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    /**
     * Convert total_amount to words using shared helper.
     */
    public function getAmountInWords()
    {
        return AmountInWords::convert($this->total_amount);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'draft' => '<span class="badge bg-secondary">Draft</span>',
            'issued' => '<span class="badge bg-primary">Issued</span>',
            'applied' => '<span class="badge bg-success">Applied</span>',
            'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
        ];

        return $badges[$this->status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    public function getTypeBadgeAttribute()
    {
        $badges = [
            'return' => '<span class="badge bg-warning">Return</span>',
            'discount' => '<span class="badge bg-info">Discount</span>',
            'correction' => '<span class="badge bg-secondary">Correction</span>',
            'other' => '<span class="badge bg-dark">Other</span>',
        ];

        return $badges[$this->type] ?? '<span class="badge bg-secondary">Unknown</span>';
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

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeIssued($query)
    {
        return $query->where('status', 'issued');
    }

    public function scopeApplied($query)
    {
        return $query->where('status', 'applied');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Create double entry GL transactions for credit note
     */
    public function createDoubleEntryTransactions()
    {
        // Check if period is locked
        $companyId = $this->company_id ?? ($this->branch->company_id ?? null);
        if ($companyId) {
            $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
            try {
                $periodLockService->validateTransactionDate($this->credit_note_date, $companyId, 'credit note');
            } catch (\Exception $e) {
                \Log::warning('CreditNote - Cannot post: Period is locked', [
                    'credit_note_id' => $this->id,
                    'credit_note_number' => $this->credit_note_number,
                    'credit_note_date' => $this->credit_note_date,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        $user = auth()->user() ?? User::find($this->created_by);

        // Delete existing transactions for this credit note
        $this->glTransactions()->delete();

        // Get functional currency and check if conversion is needed
        $functionalCurrency = SystemSetting::getValue('functional_currency', $this->company->functional_currency ?? 'TZS');
        $creditNoteCurrency = $this->currency ?? $functionalCurrency;
        $exchangeRate = $this->exchange_rate ?? 1.000000;
        $needsConversion = ($creditNoteCurrency !== $functionalCurrency && $exchangeRate != 1.000000);
        
        // Helper function to convert FCY to LCY if needed
        $convertToLCY = function($fcyAmount) use ($needsConversion, $exchangeRate) {
            return $needsConversion ? round($fcyAmount * $exchangeRate, 2) : $fcyAmount;
        };
        
        // Helper function to add currency info to description
        $addCurrencyInfo = function($description) use ($needsConversion, $creditNoteCurrency, $functionalCurrency, $exchangeRate) {
            if ($needsConversion) {
                return $description . " [FCY: {$creditNoteCurrency}, Rate: {$exchangeRate}, Converted to {$functionalCurrency}]";
            }
            return $description;
        };

        $transactions = [];
        $postingDetails = [];

        // Scenario-based posting logic
        switch ($this->type) {
            case 'return':
                $transactions = $this->createReturnTransactions($user);
                break;
            case 'discount':
            case 'price_adjustment':
                $transactions = $this->createDiscountTransactions($user);
                break;
            case 'overbilling':
            case 'duplicate_billing':
                $transactions = $this->createOverbillingTransactions($user);
                break;
            case 'service_adjustment':
                $transactions = $this->createServiceAdjustmentTransactions($user);
                break;
            case 'post_invoice_discount':
                $transactions = $this->createPostInvoiceDiscountTransactions($user);
                break;
            case 'refund':
                $transactions = $this->createRefundTransactions($user);
                break;
            case 'restocking_fee':
                $transactions = $this->createRestockingFeeTransactions($user);
                break;
            case 'scrap_writeoff':
                $transactions = $this->createScrapWriteoffTransactions($user);
                break;
            case 'advance_refund':
                $transactions = $this->createAdvanceRefundTransactions($user);
                break;
            case 'fx_adjustment':
                $transactions = $this->createFxAdjustmentTransactions($user);
                break;
            default:
                $transactions = $this->createGenericTransactions($user);
        }

        // Add restocking fee if applicable
        if ($this->restocking_fee_amount > 0) {
            $restockingTransactions = $this->createRestockingFeeTransactions($user);
            $transactions = array_merge($transactions, $restockingTransactions);
        }

        // Convert all transaction amounts to LCY and add currency info to descriptions
        foreach ($transactions as &$transaction) {
            if (isset($transaction['amount'])) {
                $transaction['amount'] = $convertToLCY($transaction['amount']);
            }
            if (isset($transaction['description'])) {
                $transaction['description'] = $addCurrencyInfo($transaction['description']);
            }
        }
        unset($transaction); // Break reference

        // Create all transactions
        foreach ($transactions as $transaction) {
            GlTransaction::create($transaction);
        }

        // Store posting details for audit
        $totalAmountLCY = $convertToLCY($this->total_amount);
        $currencyInfo = $needsConversion ? " (FCY: {$creditNoteCurrency} {$this->total_amount}, Rate: {$exchangeRate}, LCY: {$functionalCurrency} " . number_format($totalAmountLCY, 2) . ")" : "";
        $this->update([
            'posting_details' => [
                'scenario' => $this->type,
                'transactions_count' => count($transactions),
                'posting_date' => now()->toDateTimeString(),
                'user_id' => $user->id,
                'currency' => $creditNoteCurrency,
                'functional_currency' => $functionalCurrency,
                'exchange_rate' => $exchangeRate,
                'needs_conversion' => $needsConversion,
            ]
        ]);
        
        // Log activity for posting to GL
        $customerName = $this->customer ? $this->customer->name : 'N/A';
        $this->logActivity('post', "Posted Credit Note {$this->credit_note_number} to General Ledger for Customer: {$customerName}{$currencyInfo}", [
            'Credit Note Number' => $this->credit_note_number,
            'Customer' => $customerName,
            'Credit Note Date' => $this->credit_note_date ? $this->credit_note_date->format('Y-m-d') : 'N/A',
            'Type' => ucfirst(str_replace('_', ' ', $this->type ?? 'generic')),
            'Currency' => $creditNoteCurrency,
            'Functional Currency' => $functionalCurrency,
            'Exchange Rate' => $exchangeRate,
            'Total Amount (FCY)' => number_format($this->total_amount, 2) . ' ' . $creditNoteCurrency,
            'Total Amount (LCY)' => $needsConversion ? number_format($totalAmountLCY, 2) . ' ' . $functionalCurrency : number_format($this->total_amount, 2) . ' ' . $functionalCurrency,
            'Subtotal' => number_format($this->subtotal, 2),
            'VAT Amount' => number_format($this->vat_amount, 2),
            'GL Transactions Created' => count($transactions),
            'Posted By' => $user->name ?? 'System',
            'Posted At' => now()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Create transactions for goods return scenario
     */
    private function createReturnTransactions($user)
    {
        $transactions = [];

        // 1. Credit Trade Receivables (Customer) - Reduce customer debt
        $transactions[] = [
            'chart_account_id' => $this->getCustomerReceivableAccountId(),
            'customer_id' => $this->customer_id,
            'amount' => $this->gross_credit_amount ?: $this->total_amount,
            'nature' => 'credit',
            'transaction_id' => $this->id,
            'transaction_type' => 'credit_note',
            'date' => $this->credit_note_date,
            'description' => "Credit Note #{$this->credit_note_number} - {$this->customer->name}",
            'branch_id' => $this->branch_id,
            'user_id' => $user->id,
        ];

        // 2. Debit Sales Returns/Allowances - Record the return
        $transactions[] = [
            'chart_account_id' => $this->getSalesReturnsAccountId(),
            'customer_id' => $this->customer_id,
            'amount' => $this->net_credit_amount ?: ($this->subtotal - $this->discount_amount),
            'nature' => 'debit',
            'transaction_id' => $this->id,
            'transaction_type' => 'credit_note',
            'date' => $this->credit_note_date,
            'description' => "Sales Returns - Credit Note #{$this->credit_note_number}",
            'branch_id' => $this->branch_id,
            'user_id' => $user->id,
        ];

        // 3. Debit VAT Account - Reduce VAT liability
        if ($this->vat_amount > 0) {
            $transactions[] = [
                'chart_account_id' => $this->getVatOutputAccountId(),
                'customer_id' => $this->customer_id,
                'amount' => $this->vat_amount,
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'credit_note',
                'date' => $this->credit_note_date,
                'description' => "VAT Output - Credit Note #{$this->credit_note_number}",
                'branch_id' => $this->branch_id,
                'user_id' => $user->id,
            ];
        }

        // 4. Inventory and COGS reversal (if return to stock)
        if ($this->return_to_stock) {
            $inventoryReturnAmount = $this->calculateInventoryReturnAmount();
            if ($inventoryReturnAmount > 0) {
                $transactions[] = [
                    'chart_account_id' => $this->getInventoryAccountId(),
                    'customer_id' => $this->customer_id,
                    'amount' => $inventoryReturnAmount,
                    'nature' => 'debit',
                    'transaction_id' => $this->id,
                    'transaction_type' => 'credit_note',
                    'date' => $this->credit_note_date,
                    'description' => "Inventory Return - Credit Note #{$this->credit_note_number}",
                    'branch_id' => $this->branch_id,
                    'user_id' => $user->id,
                ];

                $transactions[] = [
                    'chart_account_id' => $this->getCogsAccountId(),
                    'customer_id' => $this->customer_id,
                    'amount' => $inventoryReturnAmount,
                    'nature' => 'credit',
                    'transaction_id' => $this->id,
                    'transaction_type' => 'credit_note',
                    'date' => $this->credit_note_date,
                    'description' => "COGS Reversal - Credit Note #{$this->credit_note_number}",
                    'branch_id' => $this->branch_id,
                    'user_id' => $user->id,
                ];
            }
        }

        return $transactions;
    }

    /**
     * Create transactions for discount/allowance scenario
     */
    private function createDiscountTransactions($user)
    {
        $transactions = [];

        // 1. Credit Trade Receivables (Customer)
        $transactions[] = [
            'chart_account_id' => $this->getCustomerReceivableAccountId(),
            'customer_id' => $this->customer_id,
            'amount' => $this->gross_credit_amount ?: $this->total_amount,
            'nature' => 'credit',
            'transaction_id' => $this->id,
            'transaction_type' => 'credit_note',
            'date' => $this->credit_note_date,
            'description' => "Credit Note #{$this->credit_note_number} - {$this->customer->name}",
            'branch_id' => $this->branch_id,
            'user_id' => $user->id,
        ];

        // 2. Debit Sales Allowances
        $transactions[] = [
            'chart_account_id' => $this->getSalesAllowancesAccountId(),
            'customer_id' => $this->customer_id,
            'amount' => $this->net_credit_amount ?: ($this->subtotal - $this->discount_amount),
            'nature' => 'debit',
            'transaction_id' => $this->id,
            'transaction_type' => 'credit_note',
            'date' => $this->credit_note_date,
            'description' => "Sales Allowances - Credit Note #{$this->credit_note_number}",
            'branch_id' => $this->branch_id,
            'user_id' => $user->id,
        ];

        // 3. Debit VAT Account
        if ($this->vat_amount > 0) {
            $transactions[] = [
                'chart_account_id' => $this->getVatOutputAccountId(),
                'customer_id' => $this->customer_id,
                'amount' => $this->vat_amount,
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'credit_note',
                'date' => $this->credit_note_date,
                'description' => "VAT Output - Credit Note #{$this->credit_note_number}",
                'branch_id' => $this->branch_id,
                'user_id' => $user->id,
            ];
        }

        return $transactions;
    }

    /**
     * Create transactions for overbilling/duplicate billing scenario
     */
    private function createOverbillingTransactions($user)
    {
        $transactions = [];

        // 1. Credit Trade Receivables (Customer)
        $transactions[] = [
            'chart_account_id' => $this->getCustomerReceivableAccountId(),
            'customer_id' => $this->customer_id,
            'amount' => $this->gross_credit_amount ?: $this->total_amount,
            'nature' => 'credit',
            'transaction_id' => $this->id,
            'transaction_type' => 'credit_note',
            'date' => $this->credit_note_date,
            'description' => "Credit Note #{$this->credit_note_number} - {$this->customer->name}",
            'branch_id' => $this->branch_id,
            'user_id' => $user->id,
        ];

        // 2. Debit Sales Returns (full reversal)
        $transactions[] = [
            'chart_account_id' => $this->getSalesReturnsAccountId(),
            'customer_id' => $this->customer_id,
            'amount' => $this->net_credit_amount ?: ($this->subtotal - $this->discount_amount),
            'nature' => 'debit',
            'transaction_id' => $this->id,
            'transaction_type' => 'credit_note',
            'date' => $this->credit_note_date,
            'description' => "Sales Reversal - Credit Note #{$this->credit_note_number}",
            'branch_id' => $this->branch_id,
            'user_id' => $user->id,
        ];

        // 3. Debit VAT Account
        if ($this->vat_amount > 0) {
            $transactions[] = [
                'chart_account_id' => $this->getVatOutputAccountId(),
                'customer_id' => $this->customer_id,
                'amount' => $this->vat_amount,
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'credit_note',
                'date' => $this->credit_note_date,
                'description' => "VAT Output - Credit Note #{$this->credit_note_number}",
                'branch_id' => $this->branch_id,
                'user_id' => $user->id,
            ];
        }

        // 4. Inventory and COGS reversal (if goods were actually returned)
        if ($this->return_to_stock) {
            $inventoryReturnAmount = $this->calculateInventoryReturnAmount();
            if ($inventoryReturnAmount > 0) {
                $transactions[] = [
                    'chart_account_id' => $this->getInventoryAccountId(),
                    'customer_id' => $this->customer_id,
                    'amount' => $inventoryReturnAmount,
                    'nature' => 'debit',
                    'transaction_id' => $this->id,
                    'transaction_type' => 'credit_note',
                    'date' => $this->credit_note_date,
                    'description' => "Inventory Return - Credit Note #{$this->credit_note_number}",
                    'branch_id' => $this->branch_id,
                    'user_id' => $user->id,
                ];

                $transactions[] = [
                    'chart_account_id' => $this->getCogsAccountId(),
                    'customer_id' => $this->customer_id,
                    'amount' => $inventoryReturnAmount,
                    'nature' => 'credit',
                    'transaction_id' => $this->id,
                    'transaction_type' => 'credit_note',
                    'date' => $this->credit_note_date,
                    'description' => "COGS Reversal - Credit Note #{$this->credit_note_number}",
                    'branch_id' => $this->branch_id,
                    'user_id' => $user->id,
                ];
            }
        }

        return $transactions;
    }

    /**
     * Create transactions for service adjustment scenario
     */
    private function createServiceAdjustmentTransactions($user)
    {
        $transactions = [];

        // 1. Credit Trade Receivables (Customer)
        $transactions[] = [
            'chart_account_id' => $this->getCustomerReceivableAccountId(),
            'customer_id' => $this->customer_id,
            'amount' => $this->gross_credit_amount ?: $this->total_amount,
            'nature' => 'credit',
            'transaction_id' => $this->id,
            'transaction_type' => 'credit_note',
            'date' => $this->credit_note_date,
            'description' => "Credit Note #{$this->credit_note_number} - {$this->customer->name}",
            'branch_id' => $this->branch_id,
            'user_id' => $user->id,
        ];

        // 2. Debit Sales Returns (no inventory movement)
        $transactions[] = [
            'chart_account_id' => $this->getSalesReturnsAccountId(),
            'customer_id' => $this->customer_id,
            'amount' => $this->net_credit_amount ?: ($this->subtotal - $this->discount_amount),
            'nature' => 'debit',
            'transaction_id' => $this->id,
            'transaction_type' => 'credit_note',
            'date' => $this->credit_note_date,
            'description' => "Service Adjustment - Credit Note #{$this->credit_note_number}",
            'branch_id' => $this->branch_id,
            'user_id' => $user->id,
        ];

        // 3. Debit VAT Account
        if ($this->vat_amount > 0) {
            $transactions[] = [
                'chart_account_id' => $this->getVatOutputAccountId(),
                'customer_id' => $this->customer_id,
                'amount' => $this->vat_amount,
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'credit_note',
                'date' => $this->credit_note_date,
                'description' => "VAT Output - Credit Note #{$this->credit_note_number}",
                'branch_id' => $this->branch_id,
                'user_id' => $user->id,
            ];
        }

        return $transactions;
    }

    /**
     * Create transactions for post-invoice discount scenario
     */
    private function createPostInvoiceDiscountTransactions($user)
    {
        return $this->createDiscountTransactions($user);
    }

    /**
     * Create transactions for refund scenario
     */
    private function createRefundTransactions($user)
    {
        $transactions = [];

        // 1. Credit Trade Receivables (Customer)
        $transactions[] = [
            'chart_account_id' => $this->getCustomerReceivableAccountId(),
            'customer_id' => $this->customer_id,
            'amount' => $this->gross_credit_amount ?: $this->total_amount,
            'nature' => 'credit',
            'transaction_id' => $this->id,
            'transaction_type' => 'credit_note',
            'date' => $this->credit_note_date,
            'description' => "Credit Note #{$this->credit_note_number} - {$this->customer->name}",
            'branch_id' => $this->branch_id,
            'user_id' => $user->id,
        ];

        // 2. Debit Sales Returns
        $transactions[] = [
            'chart_account_id' => $this->getSalesReturnsAccountId(),
            'customer_id' => $this->customer_id,
            'amount' => $this->net_credit_amount ?: ($this->subtotal - $this->discount_amount),
            'nature' => 'debit',
            'transaction_id' => $this->id,
            'transaction_type' => 'credit_note',
            'date' => $this->credit_note_date,
            'description' => "Refund - Credit Note #{$this->credit_note_number}",
            'branch_id' => $this->branch_id,
            'user_id' => $user->id,
        ];

        // 3. Debit VAT Account
        if ($this->vat_amount > 0) {
            $transactions[] = [
                'chart_account_id' => $this->getVatOutputAccountId(),
                'customer_id' => $this->customer_id,
                'amount' => $this->vat_amount,
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'credit_note',
                'date' => $this->credit_note_date,
                'description' => "VAT Output - Credit Note #{$this->credit_note_number}",
                'branch_id' => $this->branch_id,
                'user_id' => $user->id,
            ];
        }

        // 4. If refund now, create bank transaction
        if ($this->refund_now) {
            $transactions[] = [
                'chart_account_id' => $this->getDefaultBankAccountId(),
                'customer_id' => $this->customer_id,
                'amount' => $this->gross_credit_amount ?: $this->total_amount,
                'nature' => 'credit',
                'transaction_id' => $this->id,
                'transaction_type' => 'credit_note_refund',
                'date' => $this->credit_note_date,
                'description' => "Refund Payment - Credit Note #{$this->credit_note_number}",
                'branch_id' => $this->branch_id,
                'user_id' => $user->id,
            ];

            $transactions[] = [
                'chart_account_id' => $this->getCustomerReceivableAccountId(),
                'customer_id' => $this->customer_id,
                'amount' => $this->gross_credit_amount ?: $this->total_amount,
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'credit_note_refund',
                'date' => $this->credit_note_date,
                'description' => "Refund Payment - Credit Note #{$this->credit_note_number}",
                'branch_id' => $this->branch_id,
                'user_id' => $user->id,
            ];
        }

        return $transactions;
    }

    /**
     * Create transactions for restocking fee scenario
     */
    private function createRestockingFeeTransactions($user)
    {
        $transactions = [];

        if ($this->restocking_fee_amount > 0) {
            // 1. Debit Trade Receivables (Customer) - Charge the fee
            $transactions[] = [
                'chart_account_id' => $this->getCustomerReceivableAccountId(),
                'customer_id' => $this->customer_id,
                'amount' => $this->restocking_fee_amount + $this->restocking_fee_vat,
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'credit_note',
                'date' => $this->credit_note_date,
                'description' => "Restocking Fee - Credit Note #{$this->credit_note_number}",
                'branch_id' => $this->branch_id,
                'user_id' => $user->id,
            ];

            // 2. Credit Restocking Fee Income
            $transactions[] = [
                'chart_account_id' => $this->getRestockingFeeAccountId(),
                'customer_id' => $this->customer_id,
                'amount' => $this->restocking_fee_amount,
                'nature' => 'credit',
                'transaction_id' => $this->id,
                'transaction_type' => 'credit_note',
                'date' => $this->credit_note_date,
                'description' => "Restocking Fee Income - Credit Note #{$this->credit_note_number}",
                'branch_id' => $this->branch_id,
                'user_id' => $user->id,
            ];

            // 3. Credit VAT Output (if VAT on restocking fee)
            if ($this->restocking_fee_vat > 0) {
                $transactions[] = [
                    'chart_account_id' => $this->getVatOutputAccountId(),
                    'customer_id' => $this->customer_id,
                    'amount' => $this->restocking_fee_vat,
                    'nature' => 'credit',
                    'transaction_id' => $this->id,
                    'transaction_type' => 'credit_note',
                    'date' => $this->credit_note_date,
                    'description' => "VAT Output - Restocking Fee - Credit Note #{$this->credit_note_number}",
                    'branch_id' => $this->branch_id,
                    'user_id' => $user->id,
                ];
            }
        }

        return $transactions;
    }

    /**
     * Create transactions for scrap writeoff scenario
     */
    private function createScrapWriteoffTransactions($user)
    {
        $transactions = [];

        // 1. Credit Trade Receivables (Customer)
        $transactions[] = [
            'chart_account_id' => $this->getCustomerReceivableAccountId(),
            'customer_id' => $this->customer_id,
            'amount' => $this->gross_credit_amount ?: $this->total_amount,
            'nature' => 'credit',
            'transaction_id' => $this->id,
            'transaction_type' => 'credit_note',
            'date' => $this->credit_note_date,
            'description' => "Credit Note #{$this->credit_note_number} - {$this->customer->name}",
            'branch_id' => $this->branch_id,
            'user_id' => $user->id,
        ];

        // 2. Debit Sales Returns
        $transactions[] = [
            'chart_account_id' => $this->getSalesReturnsAccountId(),
            'customer_id' => $this->customer_id,
            'amount' => $this->net_credit_amount ?: ($this->subtotal - $this->discount_amount),
            'nature' => 'debit',
            'transaction_id' => $this->id,
            'transaction_type' => 'credit_note',
            'date' => $this->credit_note_date,
            'description' => "Sales Returns - Credit Note #{$this->credit_note_number}",
            'branch_id' => $this->branch_id,
            'user_id' => $user->id,
        ];

        // 3. Debit VAT Account
        if ($this->vat_amount > 0) {
            $transactions[] = [
                'chart_account_id' => $this->getVatOutputAccountId(),
                'customer_id' => $this->customer_id,
                'amount' => $this->vat_amount,
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'credit_note',
                'date' => $this->credit_note_date,
                'description' => "VAT Output - Credit Note #{$this->credit_note_number}",
                'branch_id' => $this->branch_id,
                'user_id' => $user->id,
            ];
        }

        // 4. Debit Scrap/Obsolescence Expense
        $scrapAmount = $this->calculateInventoryReturnAmount();
        if ($scrapAmount > 0) {
            $transactions[] = [
                'chart_account_id' => $this->getScrapExpenseAccountId(),
                'customer_id' => $this->customer_id,
                'amount' => $scrapAmount,
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'credit_note',
                'date' => $this->credit_note_date,
                'description' => "Scrap Expense - Credit Note #{$this->credit_note_number}",
                'branch_id' => $this->branch_id,
                'user_id' => $user->id,
            ];

            $transactions[] = [
                'chart_account_id' => $this->getCogsAccountId(),
                'customer_id' => $this->customer_id,
                'amount' => $scrapAmount,
                'nature' => 'credit',
                'transaction_id' => $this->id,
                'transaction_type' => 'credit_note',
                'date' => $this->credit_note_date,
                'description' => "COGS Reversal - Credit Note #{$this->credit_note_number}",
                'branch_id' => $this->branch_id,
                'user_id' => $user->id,
            ];
        }

        return $transactions;
    }

    /**
     * Create transactions for advance refund scenario
     */
    private function createAdvanceRefundTransactions($user)
    {
        $transactions = [];

        // 1. Debit Unearned Revenue
        $transactions[] = [
            'chart_account_id' => $this->getUnearnedRevenueAccountId(),
            'customer_id' => $this->customer_id,
            'amount' => $this->gross_credit_amount ?: $this->total_amount,
            'nature' => 'debit',
            'transaction_id' => $this->id,
            'transaction_type' => 'credit_note',
            'date' => $this->credit_note_date,
            'description' => "Advance Refund - Credit Note #{$this->credit_note_number}",
            'branch_id' => $this->branch_id,
            'user_id' => $user->id,
        ];

        // 2. Credit Bank Account
        $transactions[] = [
            'chart_account_id' => $this->getDefaultBankAccountId(),
            'customer_id' => $this->customer_id,
            'amount' => $this->gross_credit_amount ?: $this->total_amount,
            'nature' => 'credit',
            'transaction_id' => $this->id,
            'transaction_type' => 'credit_note',
            'date' => $this->credit_note_date,
            'description' => "Advance Refund - Credit Note #{$this->credit_note_number}",
            'branch_id' => $this->branch_id,
            'user_id' => $user->id,
        ];

        return $transactions;
    }

    /**
     * Create transactions for FX adjustment scenario
     */
    private function createFxAdjustmentTransactions($user)
    {
        $transactions = [];

        // 1. Credit Trade Receivables (Customer)
        $transactions[] = [
            'chart_account_id' => $this->getCustomerReceivableAccountId(),
            'customer_id' => $this->customer_id,
            'amount' => $this->gross_credit_amount ?: $this->total_amount,
            'nature' => 'credit',
            'transaction_id' => $this->id,
            'transaction_type' => 'credit_note',
            'date' => $this->credit_note_date,
            'description' => "FX Adjustment - Credit Note #{$this->credit_note_number}",
            'branch_id' => $this->branch_id,
            'user_id' => $user->id,
        ];

        // 2. Debit Sales Returns
        $transactions[] = [
            'chart_account_id' => $this->getSalesReturnsAccountId(),
            'customer_id' => $this->customer_id,
            'amount' => $this->net_credit_amount ?: ($this->subtotal - $this->discount_amount),
            'nature' => 'debit',
            'transaction_id' => $this->id,
            'transaction_type' => 'credit_note',
            'date' => $this->credit_note_date,
            'description' => "FX Adjustment - Credit Note #{$this->credit_note_number}",
            'branch_id' => $this->branch_id,
            'user_id' => $user->id,
        ];

        // 3. Debit VAT Account
        if ($this->vat_amount > 0) {
            $transactions[] = [
                'chart_account_id' => $this->getVatOutputAccountId(),
                'customer_id' => $this->customer_id,
                'amount' => $this->vat_amount,
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'credit_note',
                'date' => $this->credit_note_date,
                'description' => "VAT Output - FX Adjustment - Credit Note #{$this->credit_note_number}",
                'branch_id' => $this->branch_id,
                'user_id' => $user->id,
            ];
        }

        // 4. FX Gain/Loss
        if ($this->fx_gain_loss != 0) {
            $fxAccountId = $this->fx_gain_loss > 0 ? $this->getFxGainAccountId() : $this->getFxLossAccountId();
            $fxNature = $this->fx_gain_loss > 0 ? 'credit' : 'debit';

            $transactions[] = [
                'chart_account_id' => $fxAccountId,
                'customer_id' => $this->customer_id,
                'amount' => abs($this->fx_gain_loss),
                'nature' => $fxNature,
                'transaction_id' => $this->id,
                'transaction_type' => 'credit_note',
                'date' => $this->credit_note_date,
                'description' => "FX " . ($this->fx_gain_loss > 0 ? 'Gain' : 'Loss') . " - Credit Note #{$this->credit_note_number}",
                'branch_id' => $this->branch_id,
                'user_id' => $user->id,
            ];
        }

        return $transactions;
    }

    /**
     * Create generic transactions for other scenarios
     */
    private function createGenericTransactions($user)
    {
        return $this->createReturnTransactions($user);
    }
    /**
     * Get account IDs with fallback logic: Settings -> Inventory Items -> Defaults
     */
    private function getCustomerReceivableAccountId()
    {
        // 1. Check system settings first
        $accountId = SystemSetting::where('key', 'inventory_default_receivable_account')->value('value');
        if ($accountId) {
            return (int) $accountId;
        }

        // 2. Check if customer has specific receivable account
        if ($this->customer && $this->customer->receivable_account_id) {
            return $this->customer->receivable_account_id;
        }

        // 3. Default to Trade Receivables (6)
        return 6;
    }

    private function getSalesReturnsAccountId()
    {
        // 1. Check system settings first
        $accountId = SystemSetting::where('key', 'inventory_default_sales_account')->value('value');
        if ($accountId) {
            return (int) $accountId;
        }

        // 2. Check inventory items for sales account
        $inventoryItem = $this->items->first();
        if ($inventoryItem && $inventoryItem->inventoryItem && $inventoryItem->inventoryItem->sales_account_id) {
            return $inventoryItem->inventoryItem->sales_account_id;
        }

        // 3. Default to Sales Revenue (25)
        return 25;
    }

    private function getVatAccountId()
    {
        // 1. Check system settings first
        $accountId = SystemSetting::where('key', 'inventory_default_vat_account')->value('value');
        if ($accountId) {
            return (int) $accountId;
        }

        // 2. Check inventory items for VAT account
        $inventoryItem = $this->items->first();
        if ($inventoryItem && $inventoryItem->inventoryItem && $inventoryItem->inventoryItem->vat_account_id) {
            return $inventoryItem->inventoryItem->vat_account_id;
        }

        // 3. Default to VAT Payable (60)
        return 60;
    }

    private function getInventoryAccountId()
    {
        // 1. Check system settings first
        $accountId = SystemSetting::where('key', 'inventory_default_inventory_account')->value('value');
        if ($accountId) {
            return (int) $accountId;
        }

        // 2. Check inventory items for inventory account
        $inventoryItem = $this->items->first();
        if ($inventoryItem && $inventoryItem->inventoryItem && $inventoryItem->inventoryItem->inventory_account_id) {
            return $inventoryItem->inventoryItem->inventory_account_id;
        }

        // 3. Default to Merchandise Inventory (185)
        return 185;
    }

    private function getCostAccountId()
    {
        // 1. Check system settings first
        $accountId = SystemSetting::where('key', 'inventory_default_cost_account')->value('value');
        if ($accountId) {
            return (int) $accountId;
        }

        // 2. Check inventory items for cost account
        $inventoryItem = $this->items->first();
        if ($inventoryItem && $inventoryItem->inventoryItem && $inventoryItem->inventoryItem->cost_account_id) {
            return $inventoryItem->inventoryItem->cost_account_id;
        }

        // 3. Default to Cost of Goods Sold (173)
        return 173;
    }

    private function getWithholdingTaxAccountId()
    {
        // 1. Check system settings first
        $accountId = SystemSetting::where('key', 'inventory_default_withholding_tax_account')->value('value');
        if ($accountId) {
            return (int) $accountId;
        }

        // 2. Check inventory items for withholding tax account
        $inventoryItem = $this->items->first();
        if ($inventoryItem && $inventoryItem->inventoryItem && $inventoryItem->inventoryItem->withholding_tax_account_id) {
            return $inventoryItem->inventoryItem->withholding_tax_account_id;
        }

        // 3. Default to Withholding Tax Payable (37)
        return 37;
    }

    /**
     * Calculate withholding tax amount (if applicable)
     */
    private function calculateWithholdingTaxAmount()
    {
        // For now, return 0 as withholding tax is not implemented in credit note items
        // This can be extended when withholding tax is added to credit note items
        return 0;
    }

    /**
     * Calculate inventory return amount based on returned items
     */
    private function calculateInventoryReturnAmount()
    {
        $totalCost = 0;

        foreach ($this->items as $item) {
            if ($item->inventoryItem) {
                // If line explicitly says not returned to stock, skip it
                if (isset($item->return_to_stock) && $item->return_to_stock === false) {
                    continue;
                }

                // Respect inventory costing method setting (fifo | weighted_average)
                $costMethod = \App\Models\SystemSetting::where('key', 'inventory_cost_method')->value('value') ?? 'fifo';

                if ($costMethod === 'fifo') {
                    // Prefer the exact cost used at sale time; fallback to line avg cost, then item avg
                    $unitCost = $item->cogs_cost_at_sale
                        ?? $item->current_avg_cost
                        ?? ($item->inventoryItem->average_cost ?? 0);
                } else {
                    // Weighted average: use the current weighted average cost on the item
                    // Use resolved cost price (location → branch → default) as fallback
                    $resolvedCost = $item->inventoryItem->getCostPriceForBranchOrLocation($this->branch_id, session('location_id'));
                    $unitCost = $resolvedCost
                        ?? $item->current_avg_cost
                        ?? $item->cogs_cost_at_sale
                        ?? ($item->inventoryItem->average_cost ?? 0);
                }

                $totalCost += $item->quantity * $unitCost;
            }
        }

        return $totalCost;
    }

    /**
     * Get VAT Output account ID (alias for getVatAccountId)
     */
    private function getVatOutputAccountId()
    {
        return $this->getVatAccountId();
    }

    /**
     * Get Sales Allowances account ID
     */
    private function getSalesAllowancesAccountId()
    {
        // Get from system settings or default
        $setting = SystemSetting::where('key', 'sales_allowances_account_id')->first();

        return $setting ? (int) $setting->value : 4100; // Default Sales Allowances account ID
    }

    /**
     * Get Restocking Fee account ID
     */
    private function getRestockingFeeAccountId()
    {
        // Get from system settings or default
        $setting = SystemSetting::where('key', 'restocking_fee_account_id')->first();

        return $setting ? (int) $setting->value : 4200; // Default Restocking Fee account ID
    }

    /**
     * Get Scrap Expense account ID
     */
    private function getScrapExpenseAccountId()
    {
        // Get from system settings or default
        $setting = SystemSetting::where('key', 'scrap_expense_account_id')->first();

        return $setting ? (int) $setting->value : 5200; // Default Scrap Expense account ID
    }

    /**
     * Get Unearned Revenue account ID
     */
    private function getUnearnedRevenueAccountId()
    {
        // Get from system settings or default
        $setting = SystemSetting::where('key', 'unearned_revenue_account_id')->first();

        return $setting ? (int) $setting->value : 2300; // Default Unearned Revenue account ID
    }

    /**
     * Get Default Bank Account ID
     */
    private function getDefaultBankAccountId()
    {
        // Get from system settings or default
        $setting = SystemSetting::where('key', 'default_bank_account_id')->first();

        return $setting ? (int) $setting->value : 1000; // Default Bank account ID
    }

    /**
     * Get FX Gain account ID
     */
    private function getFxGainAccountId()
    {
        // Get from system settings or default
        $setting = SystemSetting::where('key', 'fx_gain_account_id')->first();

        return $setting ? (int) $setting->value : 4300; // Default FX Gain account ID
    }

    /**
     * Get FX Loss account ID
     */
    private function getFxLossAccountId()
    {
        // Get from system settings or default
        $setting = SystemSetting::where('key', 'fx_loss_account_id')->first();

        return $setting ? (int) $setting->value : 5300; // Default FX Loss account ID
    }

    /**
     * Get COGS account ID (alias for getCostAccountId)
     */
    private function getCogsAccountId()
    {
        return $this->getCostAccountId();
    }



    /**
     * Update totals based on items
     */
    public function updateTotals()
    {
        $this->subtotal = $this->items()->sum('line_total');
        $this->vat_amount = $this->items()->sum('vat_amount');
        $this->discount_amount = $this->items()->sum('discount_amount');
        $this->total_amount = $this->subtotal + $this->vat_amount - $this->discount_amount;
        $this->remaining_amount = $this->total_amount - $this->applied_amount;
        $this->save();
    }

    /**
     * Apply credit note to customer account or future invoices
     */
    public function applyCreditNote($amount = null, $description = null)
    {
        $amount = $amount ?? $this->remaining_amount;
        $description = $description ?? "Credit Note #{$this->credit_note_number} applied";

        if ($amount > $this->remaining_amount) {
            throw new \Exception('Cannot apply more than remaining amount');
        }

        $user = auth()->user();

        // Create GL transaction for credit application
        $this->createCreditApplicationGlTransactions($amount, $description);

        // Update credit note
        $this->increment('applied_amount', $amount);
        $this->remaining_amount = $this->total_amount - $this->applied_amount;

        if ($this->remaining_amount <= 0) {
            $this->status = 'applied';
        } else {
            $this->status = 'issued';
        }

        $this->save();

        return $this;
    }

    /**
     * Create GL transactions for credit application
     */
    private function createCreditApplicationGlTransactions($amount, $description)
    {
        $user = auth()->user();

        $transactions = [];

        // 1. Debit Sales Returns/Allowances (or specific credit account)
        $transactions[] = [
            'chart_account_id' => $this->getSalesReturnsAccountId(),
            'customer_id' => $this->customer_id,
            'amount' => $amount,
            'nature' => 'debit',
            'transaction_id' => $this->id,
            'transaction_type' => 'credit_note_application',
            'date' => now(),
            'description' => $description,
            'branch_id' => $this->branch_id,
            'user_id' => $user->id,
        ];

        // 2. Credit Trade Receivables (Customer Account)
        $transactions[] = [
            'chart_account_id' => $this->getCustomerReceivableAccountId(),
            'customer_id' => $this->customer_id,
            'amount' => $amount,
            'nature' => 'credit',
            'transaction_id' => $this->id,
            'transaction_type' => 'credit_note_application',
            'date' => now(),
            'description' => $description,
            'branch_id' => $this->branch_id,
            'user_id' => $user->id,
        ];

        // Create all transactions
        foreach ($transactions as $transaction) {
            GlTransaction::create($transaction);
        }
    }



    /**
     * Process inventory returns for returned items
     */
    public function processInventoryReturns()
    {
        $stockService = new \App\Services\InventoryStockService();

        // Resolve a location for movements if not set on the credit note
        $resolvedWarehouseId = $this->warehouse_id
            ?? session('location_id')
            ?? \App\Models\InventoryLocation::where('branch_id', $this->branch_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->value('id');

        foreach ($this->items as $item) {
            if ($item->inventoryItem && $item->quantity > 0) {
                $movementLocationId = $item->warehouse_id ?: $resolvedWarehouseId;
                // Get current stock using stock service
                $currentStock = $stockService->getItemStockAtLocation($item->inventoryItem->id, $movementLocationId);
                $newStock = $currentStock + $item->quantity;

                // Create inventory movement for return
                InventoryMovement::create([
                    'branch_id' => $this->branch_id,
                    'location_id' => $movementLocationId,
                    'item_id' => $item->inventoryItem->id,
                    'user_id' => auth()->id(),
                    'movement_type' => 'adjustment_in', // Return to inventory
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->inventoryItem->average_cost ?? 0,
                    'total_cost' => $item->quantity * ($item->inventoryItem->average_cost ?? 0),
                    'balance_before' => $currentStock,
                    'balance_after' => $newStock,
                    'reference' => $this->id,
                    'reference_type' => 'credit_note',
                    'notes' => "Return from Credit Note #{$this->credit_note_number}",
                    'movement_date' => $this->credit_note_date,
                ]);

                // Stock is now tracked via movements, no need to update item directly
            }
        }
    }

    /**
     * Approve credit note
     */
    public function approve($approvedBy = null)
    {
        $user = $approvedBy ?? auth()->user();

        $this->update([
            'status' => 'issued',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        // Create GL transactions
        $this->createDoubleEntryTransactions();

        // Process inventory returns if applicable
        if ($this->type === 'return') {
            $this->processInventoryReturns();
        }

        return $this;
    }

    /**
     * Cancel credit note
     */
    public function cancel($reason = null)
    {
        if ($this->status === 'applied') {
            throw new \Exception('Cannot cancel an applied credit note');
        }

        $this->update([
            'status' => 'cancelled',
            'notes' => $this->notes . "\nCancelled: " . ($reason ?? 'No reason provided'),
        ]);

        // Delete GL transactions
        $this->glTransactions()->delete();

        return $this;
    }
}
