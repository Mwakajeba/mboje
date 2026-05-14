<?php

namespace App\Models\Sales;

use App\Models\Customer;
use App\Models\Inventory\Item as InventoryItem;
use App\Models\Inventory\Movement as InventoryMovement;
use App\Models\User;
use App\Models\Branch;
use App\Models\Company;
use App\Models\GlTransaction;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\SystemSetting;
use App\Models\BankAccount;
use App\Models\CashDeposit;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Vinkla\Hashids\Facades\Hashids;

class CashSale extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'sale_number',
        'customer_id',
        'sale_date',
        'payment_method',
        'bank_account_id',
        'cash_deposit_id',
        'subtotal',
        'vat_amount',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'currency',
        'exchange_rate',
        'vat_rate',
        'vat_type',
        'withholding_tax_amount',
        'withholding_tax_rate',
        'withholding_tax_type',
        'notes',
        'terms_conditions',
        'attachment',
        'branch_id',
        'company_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'currency' => 'string',
        'exchange_rate' => 'decimal:6',
        'vat_rate' => 'decimal:2',
        'withholding_tax_amount' => 'decimal:2',
        'withholding_tax_rate' => 'decimal:2',
    ];

    // protected $dates = ['deleted_at'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cashSale) {
            if (empty($cashSale->sale_number)) {
                $cashSale->sale_number = self::generateSaleNumber();
            }
        });
    }

    /**
     * Generate unique sale number
     */
    public static function generateSaleNumber(): string
    {
        $prefix = 'CS';
        $year = date('Y');
        $month = date('m');
        
        $lastSale = self::where('sale_number', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('sale_number', 'desc')
            ->first();

        if ($lastSale) {
            $lastNumber = (int) substr($lastSale->sale_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CashSaleItem::class);
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

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function cashDeposit(): BelongsTo
    {
        return $this->belongsTo(CashDeposit::class);
    }

    public function glTransactions(): HasMany
    {
        return $this->hasMany(GlTransaction::class, 'transaction_id')
            ->where('transaction_type', 'cash_sale');
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

    public function scopeByPaymentMethod($query, $paymentMethod)
    {
        return $query->where('payment_method', $paymentMethod);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('sale_date', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('sale_date', now()->month)
            ->whereYear('sale_date', now()->year);
    }

    /**
     * Accessors
     */
    public function getEncodedIdAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    public function getPaymentMethodTextAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->payment_method));
    }

    public function getVatTypeTextAttribute(): string
    {
        return ucfirst($this->vat_type);
    }

    public function getWithholdingTaxTypeTextAttribute(): string
    {
        return ucfirst($this->withholding_tax_type);
    }

    /**
     * Create double entry transactions for cash sale
     */
    public function createDoubleEntryTransactions()
    {
        // Check if period is locked
        $companyId = $this->company_id ?? ($this->branch->company_id ?? null);
        if ($companyId) {
            $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
            try {
                $periodLockService->validateTransactionDate($this->sale_date, $companyId, 'cash sale');
            } catch (\Exception $e) {
                \Log::warning('CashSale - Cannot post: Period is locked', [
                    'sale_id' => $this->id,
                    'sale_number' => $this->sale_number,
                    'sale_date' => $this->sale_date,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        $user = auth()->user();
        $userId = $user ? $user->id : ($this->created_by ?? 1); // Fallback to created_by or default user ID
        
        // Delete existing transactions for this sale
        $this->glTransactions()->delete();

        // Get functional currency and check if conversion is needed
        $functionalCurrency = SystemSetting::getValue('functional_currency', $this->company->functional_currency ?? 'TZS');
        $saleCurrency = $this->currency ?? $functionalCurrency;
        $exchangeRate = $this->exchange_rate ?? 1.000000;
        $needsConversion = ($saleCurrency !== $functionalCurrency && $exchangeRate != 1.000000);
        
        // Helper function to convert FCY to LCY if needed
        $convertToLCY = function($fcyAmount) use ($needsConversion, $exchangeRate) {
            return $needsConversion ? round($fcyAmount * $exchangeRate, 2) : $fcyAmount;
        };
        
        // Helper function to add currency info to description
        $addCurrencyInfo = function($description) use ($needsConversion, $saleCurrency, $functionalCurrency, $exchangeRate) {
            if ($needsConversion) {
                return $description . " [FCY: {$saleCurrency}, Rate: {$exchangeRate}, Converted to {$functionalCurrency}]";
            }
            return $description;
        };

        $transactions = [];

        // Determine the debit account based on payment method
        $debitAccountId = null;
        if ($this->payment_method === 'bank' && $this->bank_account_id) {
            $debitAccountId = $this->bankAccount->chart_account_id;
        } elseif ($this->payment_method === 'cash_deposit') {
            if ($this->cash_deposit_id) {
                // For specific cash deposits
                $debitAccountId = $this->cashDeposit->type->chart_account_id;
            } else {
                // For customer balance (cash_deposit_id is null)
                // Find the first available cash deposit account for this customer
                $firstDeposit = $this->customer->cashDeposits()
                    ->where('amount', '>', 0)
                    ->with('type')
                    ->first();
                
                if ($firstDeposit) {
                    $debitAccountId = $firstDeposit->type->chart_account_id;
                } else {
                    // Fallback to default cash account if no deposits found
                    $debitAccountId = SystemSetting::where('key', 'inventory_default_cash_account')->value('value') ?? 7; // Cash on Hand (1001)
                }
            }
        } else {
            // Cash payment - use default cash account
            $debitAccountId = SystemSetting::where('key', 'inventory_default_cash_account')->value('value') ?? 7; // Cash on Hand (1001)
        }

        // Check if this account is in a completed reconciliation period - prevent posting
        if ($debitAccountId) {
            $isInCompletedReconciliation = \App\Services\BankReconciliationService::isChartAccountInCompletedReconciliation(
                $debitAccountId,
                $this->sale_date
            );
            
            if ($isInCompletedReconciliation) {
                \Log::warning('CashSale::createDoubleEntryTransactions - Cannot post: Account is in a completed reconciliation period', [
                    'cash_sale_id' => $this->id,
                    'sale_number' => $this->sale_number,
                    'chart_account_id' => $debitAccountId,
                    'payment_method' => $this->payment_method,
                    'sale_date' => $this->sale_date
                ]);
                throw new \Exception("Cannot post cash sale: Account is in a completed reconciliation period for date {$this->sale_date}.");
            }
        }

        // Determine if VAT is inclusive or exclusive
        $hasInclusiveItems = $this->items()->where('vat_type', 'inclusive')->exists();
        $hasExclusiveItems = $this->items()->where('vat_type', 'exclusive')->exists();
        
        // Calculate net sales revenue (excluding VAT)
        $netSalesRevenue = $this->subtotal;
        if ($hasInclusiveItems && !$hasExclusiveItems) {
            // All items are inclusive - subtotal includes VAT, so net = subtotal - VAT
            $netSalesRevenue = $this->subtotal - $this->vat_amount;
        }
        // For exclusive VAT, subtotal is already net (excluding VAT)
        
        // 1. Debit Cash/Bank/Cash Deposit Account (amount received net of withholding)
        // For inclusive VAT: total_amount already includes VAT, so use total_amount
        // For exclusive VAT: total_amount = subtotal + VAT - discount - WHT
        $amountReceived = $convertToLCY($this->total_amount);
        $transactions[] = [
            'chart_account_id' => $debitAccountId,
            'customer_id' => $this->customer_id,
            'amount' => $amountReceived,
            'nature' => 'debit',
            'transaction_id' => $this->id,
            'transaction_type' => 'cash_sale',
            'date' => $this->sale_date,
            'description' => $addCurrencyInfo("Cash sale #{$this->sale_number} - {$this->customer->name}"),
            'branch_id' => $this->branch_id,
            'user_id' => $userId,
        ];

        // 2. Credit Sales Revenue (net amount excluding VAT)
        // Group items by their sales revenue account (item-specific or default)
        $salesRevenueByAccount = [];
        $defaultSalesAccountId = SystemSetting::where('key', 'inventory_default_sales_account')->value('value') ?? 25;

        // Ensure items are loaded with inventoryItem relationship
        $items = $this->items()->with('inventoryItem')->get();

        foreach ($items as $item) {
            // Calculate net line amount (excluding VAT)
            $netLine = $item->line_total ?? 0;
            if ($item->vat_type === 'inclusive' && $item->vat_amount) {
                $netLine = $netLine - $item->vat_amount;
            }
            
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
                    'transaction_type' => 'cash_sale',
                    'date' => $this->sale_date,
                    'description' => $addCurrencyInfo("Sales Revenue - Cash sale #{$this->sale_number}"),
                    'branch_id' => $this->branch_id,
                    'user_id' => $userId,
                ];
            }
        }

        // 3. Debit Sales Discount (if discount exists)
        if ($this->discount_amount > 0) {
            $discountAmountLCY = $convertToLCY($this->discount_amount);
            $transactions[] = [
                'chart_account_id' => $this->getDiscountAccountId(),
                'customer_id' => $this->customer_id,
                'amount' => $discountAmountLCY,
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'cash_sale',
                'date' => $this->sale_date,
                'description' => $addCurrencyInfo("Sales Discount - Cash sale #{$this->sale_number}"),
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];
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
                'transaction_type' => 'cash_sale',
                'date' => $this->sale_date,
                'description' => $addCurrencyInfo("VAT Payable - Cash sale #{$this->sale_number}"),
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];
        }

        // 4. Debit Withholding Tax (sales withholding is typically a receivable from the tax authority)
        if ($this->withholding_tax_amount > 0) {
            $whtAmountLCY = $convertToLCY($this->withholding_tax_amount);
            $transactions[] = [
                'chart_account_id' => $this->getWithholdingTaxAccountId(),
                'customer_id' => $this->customer_id,
                'amount' => $whtAmountLCY,
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'cash_sale',
                'date' => $this->sale_date,
                'description' => $addCurrencyInfo("Withholding Tax Receivable - Cash sale #{$this->sale_number}"),
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];
        }

        // 5. Debit Cost of Goods Sold and Credit Inventory
        // IMPORTANT: Update inventory FIRST so cost layers are consumed and movements are created
        // Then we can get the actual cost from inventory movements
        $this->updateInventory();
        
        // Get actual cost from inventory movements (same method as Sales Invoice)
        // IMPORTANT: COGS is always in functional currency (TZS) - inventory costs are stored in TZS
        // We should NOT convert COGS when sale is in foreign currency
        $totalCostOfGoodsSold = $this->calculateCostOfGoodsSold();

        if ($totalCostOfGoodsSold > 0) {
            // COGS is already in functional currency (TZS), do NOT convert
            // The cost from inventory movements is always in TZS
            $cogsAmountLCY = round($totalCostOfGoodsSold, 2);
            
            // Debit Cost of Goods Sold
            $transactions[] = [
                'chart_account_id' => $this->getCostOfGoodsSoldAccountId(),
                'customer_id' => $this->customer_id,
                'amount' => $cogsAmountLCY,
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'cash_sale',
                'date' => $this->sale_date,
                'description' => "Cost of Goods Sold - Cash sale #{$this->sale_number} [Amount in {$functionalCurrency}]",
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];

            // Credit Inventory
            $transactions[] = [
                'chart_account_id' => $this->getInventoryAccountId(),
                'customer_id' => $this->customer_id,
                'amount' => $cogsAmountLCY,
                'nature' => 'credit',
                'transaction_id' => $this->id,
                'transaction_type' => 'cash_sale',
                'date' => $this->sale_date,
                'description' => "Inventory Reduction - Cash sale #{$this->sale_number} [Amount in {$functionalCurrency}]",
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];
        }

        // Create all transactions
        $createdCount = 0;
        foreach ($transactions as $transaction) {
            GlTransaction::create($transaction);
            $createdCount++;
        }

        // Log activity for posting to GL
        $customerName = $this->customer ? $this->customer->name : 'N/A';
        $currencyInfo = $needsConversion ? " (FCY: {$saleCurrency} {$this->total_amount}, Rate: {$exchangeRate}, LCY: {$functionalCurrency} " . number_format($amountReceived, 2) . ")" : "";
        $this->logActivity('post', "Posted Cash Sale {$this->sale_number} to General Ledger for Customer: {$customerName}{$currencyInfo}", [
            'Sale Number' => $this->sale_number,
            'Customer' => $customerName,
            'Sale Date' => $this->sale_date ? $this->sale_date->format('Y-m-d') : 'N/A',
            'Payment Method' => ucfirst(str_replace('_', ' ', $this->payment_method ?? 'cash')),
            'Currency' => $saleCurrency,
            'Functional Currency' => $functionalCurrency,
            'Exchange Rate' => $exchangeRate,
            'Total Amount (FCY)' => number_format($this->total_amount, 2) . ' ' . $saleCurrency,
            'Total Amount (LCY)' => $needsConversion ? number_format($amountReceived, 2) . ' ' . $functionalCurrency : number_format($this->total_amount, 2) . ' ' . $functionalCurrency,
            'Subtotal' => number_format($this->subtotal, 2),
            'VAT Amount' => number_format($this->vat_amount, 2),
            'Discount Amount' => number_format($this->discount_amount, 2),
            'GL Transactions Created' => $createdCount,
            'Posted By' => auth()->user()->name ?? 'System',
            'Posted At' => now()->format('Y-m-d H:i:s')
        ]);
        // Note: updateInventory() was already called before COGS calculation
    }

    /**
     * Update inventory levels for all items
     */
public function updateInventory()
    {
        // Restore cost layers before updating movements (reverse the cost consumption)
        $costService = new \App\Services\InventoryCostService();
        $oldMovements = InventoryMovement::where('reference_type', 'cash_sale')
            ->where('reference_id', $this->id)
            ->get();
        
        foreach ($oldMovements as $oldMovement) {
            // Restore the cost layers that were consumed for this movement
            $costService->restoreInventoryCostLayers(
                $oldMovement->item_id,
                $oldMovement->quantity,
                $oldMovement->unit_cost,
                'Cash Sale: ' . $this->sale_number
            );
        }

        // Delete existing movements for this sale (idempotency on edit)
        // Get location_id from existing movements or session, or use branch default
        $existingMovements = InventoryMovement::where('reference_type', 'cash_sale')
            ->where('reference_id', $this->id)
            ->get();
        
        $locationId = null;
        if ($existingMovements->isNotEmpty()) {
            // Use location from existing movement (most reliable)
            $locationId = $existingMovements->first()->location_id;
        } elseif (session('location_id')) {
            // Use session location if available
            $locationId = session('location_id');
        } else {
            // Fallback: get first location for this branch
            $locationId = \App\Models\InventoryLocation::where('branch_id', $this->branch_id)->first()?->id ?? 1;
        }
        
        // Delete old movements
        InventoryMovement::where('reference_type', 'cash_sale')
            ->where('reference_id', $this->id)
            ->delete();

        foreach ($this->items as $item) {
            $inventoryItem = $item->inventoryItem;
            
            // Get stock as of sale date (for backdated sales, this ensures correct balance calculation)
            // Use the sale's created_at timestamp to exclude same-day transactions that happened after this sale
            $stockService = new \App\Services\InventoryStockService();
            $asOfTimestamp = $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s');
            $balanceBefore = $stockService->getItemStockAtLocationAsOfDate(
                $inventoryItem->id, 
                $locationId, 
                $this->sale_date,
                null,
                $asOfTimestamp
            );
            $balanceAfter = $balanceBefore - $item->quantity;
            
            // Get actual cost using FIFO/Weighted Average
            // Pass branch/location for fallback cost resolution (location → branch → default)
            $costInfo = $costService->removeInventory(
                $inventoryItem->id,
                $item->quantity,
                'sale',
                'Cash Sale: ' . $this->sale_number,
                $this->sale_date,
                $this->branch_id,
                $locationId
            );
            
            // Create new inventory movement
            InventoryMovement::create([
                'item_id' => $inventoryItem->id,
                'user_id' => auth()->id() ?? $this->created_by ?? 1,
                'branch_id' => $this->branch_id,
                'location_id' => $locationId,
                'movement_type' => 'sold',
                'quantity' => $item->quantity,
                'unit_cost' => $costInfo['average_unit_cost'],
                'unit_price' => $costInfo['average_unit_cost'],
                'total_cost' => $costInfo['total_cost'],
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference' => $this->sale_number,
                'reference_type' => 'cash_sale',
                'reference_id' => $this->id,
                'reason' => 'Cash sale',
                'notes' => "Cash sale #{$this->sale_number}",
                'movement_date' => $this->sale_date,
            ]);

            // Consume stock using FEFO if item tracks expiry
            if ($inventoryItem->track_expiry) {
                $expiryService = new \App\Services\ExpiryStockService();
                $consumedLayers = $expiryService->consumeStock(
                    $inventoryItem->id,
                    session('location_id'),
                    $item->quantity,
                    'FEFO'
                );
                
                // Log consumed layers for audit trail
                foreach ($consumedLayers as $layer) {
                    \Log::info('Cash Sale: Consumed stock with expiry', [
                        'item_id' => $inventoryItem->id,
                        'batch_number' => $layer['batch_number'],
                        'expiry_date' => $layer['expiry_date'],
                        'quantity' => $layer['quantity'],
                        'unit_cost' => $layer['unit_cost']
                    ]);
                }
            }

            // Stock is tracked via movements; no direct column updates
        }
    }

    /**
     * Update existing inventory movements for this cash sale
     * @deprecated Use updateInventory() instead - this method is kept for backward compatibility
     */
    public function updateInventoryMovements($oldMovements = null)
    {
        // Delegate to updateInventory() which handles cost layer restoration properly
        $this->updateInventory();
    }

    /**
     * Get sales account ID from system settings
     */
    private function getSalesAccountId(): int
    {
        $systemSetting = SystemSetting::where('key', 'inventory_default_sales_account')->value('value');
        
        if ($systemSetting) {
            return $systemSetting;
        }
        
        // Fallback: use the sales account from the first inventory item in this sale
        $firstItem = $this->items->first();
        if ($firstItem && $firstItem->inventoryItem && $firstItem->inventoryItem->sales_account_id) {
            return $firstItem->inventoryItem->sales_account_id;
        }
        
        // Final fallback to default sales account
        return 25; // Sales Revenue
    }

    /**
     * Get VAT account ID from system settings
     */
    private function getVatAccountId(): int
    {
        $systemSetting = SystemSetting::where('key', 'inventory_default_vat_account')->value('value');
        
        if ($systemSetting) {
            return $systemSetting;
        }
        
        // Fallback: use the VAT account from the first inventory item in this sale
        $firstItem = $this->items->first();
        if ($firstItem && $firstItem->inventoryItem && $firstItem->inventoryItem->vat_account_id) {
            return $firstItem->inventoryItem->vat_account_id;
        }
        
        // Final fallback to default VAT account
        return 60; // VAT Payable
    }

    /**
     * Get withholding tax account ID from system settings
     */
    private function getWithholdingTaxAccountId(): int
    {
        $systemSetting = SystemSetting::where('key', 'inventory_default_withholding_tax_account')->value('value');
        
        if ($systemSetting) {
            return $systemSetting;
        }
        
        // Fallback: use the withholding tax account from the first inventory item in this sale
        $firstItem = $this->items->first();
        if ($firstItem && $firstItem->inventoryItem && $firstItem->inventoryItem->withholding_tax_account_id) {
            return $firstItem->inventoryItem->withholding_tax_account_id;
        }
        
        // Final fallback to default withholding tax account
        return 61; // Withholding Tax Payable
    }

    /**
     * Get discount account ID from system settings
     */
    private function getDiscountAccountId(): int
    {
        $systemSetting = SystemSetting::where('key', 'inventory_default_discount_account')->value('value');
        
        if ($systemSetting) {
            return $systemSetting;
        }
        
        // Fallback: use the discount account from the first inventory item in this sale
        $firstItem = $this->items->first();
        if ($firstItem && $firstItem->inventoryItem && $firstItem->inventoryItem->discount_account_id) {
            return $firstItem->inventoryItem->discount_account_id;
        }
        
        // Final fallback to default discount account
        return 157; // Discount Expense (5405)
    }

    /**
     * Get cost of goods sold account ID from system settings
     */
    private function getCostOfGoodsSoldAccountId(): int
    {
        $systemSetting = SystemSetting::where('key', 'inventory_default_cogs_account')->value('value');
        
        if ($systemSetting) {
            return $systemSetting;
        }
        
        // Fallback: use the cost account from the first inventory item in this sale
        $firstItem = $this->items->first();
        if ($firstItem && $firstItem->inventoryItem && $firstItem->inventoryItem->cost_account_id) {
            return $firstItem->inventoryItem->cost_account_id;
        }
        
        // Final fallback to default COGS account
        return 173; // Cost of Goods Sold
    }

    /**
     * Calculate total cost of goods sold from inventory movements
     * Uses the same method as Sales Invoice for consistency
     */
    private function calculateCostOfGoodsSold(): float
    {
        // Get actual cost from inventory movements for this cash sale
        // Use reference_type and reference_id for reliable matching
        $movements = InventoryMovement::where('reference_type', 'cash_sale')
            ->where('reference_id', $this->id)
            ->where('movement_type', 'sold')
            ->get();
        
        $totalCost = $movements->sum('total_cost');
        
        // Fallback: if no movements found, use resolved cost price (location → branch → default)
        if ($totalCost == 0) {
            $locationId = session('location_id');
            foreach ($this->items as $item) {
                $inventoryItem = $item->inventoryItem;
                // Use resolved cost price hierarchy
                $unitCost = $inventoryItem ? $inventoryItem->getCostPriceForBranchOrLocation($this->branch_id, $locationId) : 0;
                $totalCost += $item->quantity * $unitCost;
            }
        }
        
        return $totalCost;
    }

    /**
     * Get inventory account ID from system settings
     */
    private function getInventoryAccountId(): int
    {
        $systemSetting = SystemSetting::where('key', 'inventory_default_inventory_account')->value('value');
        
        if ($systemSetting) {
            return $systemSetting;
        }
        
        // Fallback: use the inventory account from the first inventory item in this sale
        $firstItem = $this->items->first();
        if ($firstItem && $firstItem->inventoryItem && $firstItem->inventoryItem->inventory_account_id) {
            return $firstItem->inventoryItem->inventory_account_id;
        }
        
        // Final fallback to default inventory account
        return 185; // Merchandise Inventory
    }

    /**
     * Update totals based on items
     */
    public function updateTotals()
    {
        // Calculate subtotal and VAT from items
        $subtotalNet = 0; // Net amount excluding VAT
        $vatAmount = 0;
        $subtotalGross = 0; // Gross amount (for inclusive VAT, this includes VAT)
        
        foreach ($this->items as $item) {
            $item->calculateLineTotal();
            
            if ($item->vat_type === 'inclusive') {
                // For inclusive VAT: line_total includes VAT, so gross = line_total
                $subtotalGross += $item->line_total;
                $vatAmount += $item->vat_amount;
                // Net amount = gross - VAT
                $subtotalNet += ($item->line_total - $item->vat_amount);
            } elseif ($item->vat_type === 'exclusive') {
                // For exclusive VAT: line_total = net + VAT
                $subtotalNet += ($item->line_total - $item->vat_amount);
                $vatAmount += $item->vat_amount;
                $subtotalGross += $item->line_total;
            } else {
                // No VAT
                $subtotalNet += $item->line_total;
                $subtotalGross += $item->line_total;
            }
        }
        
        // Set subtotal based on VAT type
        // If all items are inclusive, subtotal should be gross (includes VAT)
        // If all items are exclusive, subtotal should be net (excludes VAT)
        // For mixed, we'll use gross as subtotal
        $hasInclusiveItems = $this->items()->where('vat_type', 'inclusive')->exists();
        $hasExclusiveItems = $this->items()->where('vat_type', 'exclusive')->exists();
        
        if ($hasInclusiveItems && !$hasExclusiveItems) {
            // All items are inclusive - subtotal is gross (includes VAT)
            $this->subtotal = $subtotalGross;
        } elseif ($hasExclusiveItems && !$hasInclusiveItems) {
            // All items are exclusive - subtotal is net (excludes VAT)
            $this->subtotal = $subtotalNet;
        } else {
            // Mixed - use gross as subtotal
            $this->subtotal = $subtotalGross;
        }
        
        $this->vat_amount = $vatAmount;
        
        // Note: discount_amount is set manually by user, not calculated from items
        
        // Calculate withholding tax amount (based on net subtotal)
        if ($this->withholding_tax_type === 'fixed') {
            $this->withholding_tax_amount = $this->withholding_tax_rate;
        } else {
            // WHT is calculated on net amount (subtotal excluding VAT)
            $this->withholding_tax_amount = $subtotalNet * ($this->withholding_tax_rate / 100);
        }
        
        // Calculate total amount
        // For inclusive VAT: total = subtotal (gross) - discount - WHT (no need to add VAT)
        // For exclusive VAT: total = subtotal (net) + VAT - discount - WHT
        if ($hasInclusiveItems && !$hasExclusiveItems) {
            // All inclusive: subtotal already includes VAT
            $this->total_amount = $this->subtotal - $this->discount_amount - $this->withholding_tax_amount;
        } elseif ($hasExclusiveItems && !$hasInclusiveItems) {
            // All exclusive: need to add VAT
            $this->total_amount = $this->subtotal + $this->vat_amount - $this->discount_amount - $this->withholding_tax_amount;
        } else {
            // Mixed: subtotal is gross, so no need to add VAT
            $this->total_amount = $this->subtotal - $this->discount_amount - $this->withholding_tax_amount;
        }
        
        $this->paid_amount = $this->total_amount; // Cash sales are always fully paid
        $this->save();
    }

    /**
     * Process payment based on payment method
     */
    public function processPayment()
    {
        if ($this->payment_method === 'cash_deposit') {
            if ($this->cash_deposit_id) {
                // Reduce specific cash deposit balance
                $this->cashDeposit->decrement('amount', $this->total_amount);
                
                // Create payment record using cash deposit's chart account
                \App\Models\Payment::create([
                    'customer_id' => $this->customer_id,
                    'amount' => $this->total_amount,
                    'date' => $this->sale_date,
                    'reference' => $this->sale_number,
                    'reference_type' => 'cash_sale',
                    'description' => "Cash sale payment - {$this->sale_number}",
                    'branch_id' => $this->branch_id,
                    'user_id' => auth()->id(),
                    'bank_account_id' => $this->cashDeposit->type->chart_account_id,
                    'cash_deposit_id' => $this->cash_deposit_id,
                ]);
            } else {
                // For customer_balance case, use Journal system like invoice payments
                $this->createCashDepositJournal();
            }
        } elseif ($this->payment_method === 'bank' && $this->bank_account_id) {
            // For bank payments, create a payment record using the selected bank account
            \App\Models\Payment::create([
                'customer_id' => $this->customer_id,
                'amount' => $this->total_amount,
                'date' => $this->sale_date,
                'reference' => $this->sale_number,
                'reference_type' => 'cash_sale',
                'description' => "Cash sale payment - {$this->sale_number}",
                'branch_id' => $this->branch_id,
                'user_id' => auth()->id(),
                'bank_account_id' => $this->bank_account_id,
            ]);
        }
        
        // Mark as paid
        $this->paid_amount = $this->total_amount;
        $this->save();
    }

    /**
     * Get all receipts for this cash sale
     */
    public function receipts()
    {
        return $this->hasMany(Receipt::class, 'reference')
            ->where('reference_type', 'cash_sale');
    }

    /**
     * Get all journals for this cash sale
     */
    public function journals()
    {
        return $this->hasMany(Journal::class, 'reference')
            ->where('reference_type', 'cash_sale');
    }

    /**
     * Create journal entries for cash deposit payments
     */
    protected function createCashDepositJournal()
    {
        // Create Journal for cash deposit payment
        $journal = Journal::create([
            'journal_number' => 'CASH-SALE-' . $this->sale_number,
            'date' => $this->sale_date,
            'reference' => $this->sale_number,
            'reference_type' => 'cash_sale_payment',
            'description' => "Cash sale payment via cash deposits - {$this->sale_number}",
            'customer_id' => $this->customer_id,
            'branch_id' => $this->branch_id,
            'company_id' => $this->company_id,
            'user_id' => auth()->id(),
        ]);

        // Create Journal Items for double-entry accounting
        $this->createCashDepositJournalItems($journal);

        return $journal;
    }

    /**
     * Create journal items for cash deposit payment
     */
    protected function createCashDepositJournalItems($journal)
    {
        $description = "Cash sale payment - {$this->sale_number}";
        
        // Debit Cash Deposits account (Account 28) - reducing customer's cash deposit balance
        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => 28, // Cash Deposits account
            'nature' => 'debit',
            'amount' => $this->total_amount,
            'description' => $description,
            'branch_id' => $this->branch_id,
            'company_id' => $this->company_id,
        ]);

        // Credit Sales Revenue account (based on revenue account configuration)
        // Using account 5 for now, but this should ideally be configurable
        JournalItem::create([
            'journal_id' => $journal->id,
            'chart_account_id' => 5, // Sales Revenue account
            'nature' => 'credit',
            'amount' => $this->total_amount,
            'description' => $description,
            'branch_id' => $this->branch_id,
            'company_id' => $this->company_id,
        ]);

        // Create GL transactions for each journal item
        $this->createGlTransactionsFromJournal($journal);
    }

    /**
     * Create GL transactions from journal items
     */
    protected function createGlTransactionsFromJournal($journal)
    {
        foreach ($journal->items as $item) {
            \App\Models\GlTransaction::create([
                'chart_account_id' => $item->chart_account_id,
                'amount' => $item->amount,
                'nature' => $item->nature,
                'transaction_id' => $journal->id,
                'transaction_type' => 'journal',
                'description' => $item->description,
                'date' => $this->sale_date,
                'branch_id' => $this->branch_id,
                'user_id' => auth()->id(),
            ]);
        }
    }
} 