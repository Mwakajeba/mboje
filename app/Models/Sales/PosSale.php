<?php

namespace App\Models\Sales;

use App\Models\Customer;
use App\Models\Inventory\Item as InventoryItem;
use App\Models\Inventory\Movement as InventoryMovement;
use App\Models\User;
use App\Models\Branch;
use App\Models\Company;
use App\Models\GlTransaction;
use App\Models\SystemSetting;
use App\Models\BankAccount;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Vinkla\Hashids\Facades\Hashids;

class PosSale extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'pos_number',
        'customer_id',
        'customer_name',
        'sale_date',
        'terminal_id',
        'operator_id',
        'payment_method',
        'cash_amount',
        'bank_amount',
        'bank_account_id',
        'subtotal',
        'vat_amount',
        'discount_amount',
        'discount_type',
        'discount_rate',
        'total_amount',
        'currency',
        'exchange_rate',
        'vat_rate',
        'vat_type',
        'withholding_tax_amount',
        'withholding_tax_rate',
        'withholding_tax_type',
        'receipt_printed',
        'notes',
        'branch_id',
        'company_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'cash_amount' => 'decimal:2',
        'card_amount' => 'decimal:2',
        'mobile_money_amount' => 'decimal:2',
        'bank_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'currency' => 'string',
        'exchange_rate' => 'decimal:6',
        'vat_rate' => 'decimal:2',
        'withholding_tax_amount' => 'decimal:2',
        'withholding_tax_rate' => 'decimal:2',
        'receipt_printed' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($posSale) {
            if (empty($posSale->pos_number)) {
                $posSale->pos_number = self::generatePosNumber();
            }
        });
    }

    /**
     * Generate unique POS number
     */
    public static function generatePosNumber(): string
    {
        $prefix = 'POS';
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        
        $lastSale = self::withTrashed()
            ->where('pos_number', 'like', "{$prefix}{$year}{$month}{$day}%")
            ->orderBy('pos_number', 'desc')
            ->first();

        if ($lastSale) {
            $lastNumber = (int) substr($lastSale->pos_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . $day . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
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
        return $this->hasMany(PosSaleItem::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
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
            ->where('transaction_type', 'pos_sale');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * Scopes
     */
    public function scopeForBranch(Builder $query, $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForCompany(Builder $query, $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForTerminal(Builder $query, $terminalId): Builder
    {
        return $query->where('terminal_id', $terminalId);
    }

    public function scopeForOperator(Builder $query, $operatorId): Builder
    {
        return $query->where('operator_id', $operatorId);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('sale_date', today());
    }

    /**
     * Accessors
     */
    public function getEncodedIdAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    public function getTotalPaymentAttribute(): float
    {
        return $this->cash_amount + $this->card_amount + $this->mobile_money_amount;
    }

    public function getChangeAmountAttribute(): float
    {
        return $this->total_payment - $this->total_amount;
    }

    public function getPaymentMethodTextAttribute(): string
    {
        $methods = [];
        if ($this->cash_amount > 0) $methods[] = 'Cash';
        if ($this->card_amount > 0) $methods[] = 'Card';
        if ($this->mobile_money_amount > 0) $methods[] = 'Mobile Money';
        
        return implode(', ', $methods);
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
     * Update totals based on items
     */
    public function updateTotals()
    {
        $subtotal = 0; // Net amount (excluding VAT)
        $vatAmount = 0;
        $totalLineTotals = 0; // Sum of all line totals (for inclusive VAT, this already includes VAT)
        
        foreach ($this->items as $item) {
            $item->calculateLineTotal();
            
            $baseAmount = $item->quantity * $item->unit_price;
            $itemVatAmount = $item->vat_amount;
            $itemLineTotal = $item->line_total;
            
            // Calculate net amount (subtotal) based on VAT type
            if ($item->vat_type === 'inclusive') {
                // For inclusive VAT, net amount = baseAmount - VAT
                $netAmount = $baseAmount - $itemVatAmount;
            } else {
                // For exclusive or no VAT, net amount = baseAmount
                $netAmount = $baseAmount;
            }
            
            $subtotal += $netAmount;
            $vatAmount += $itemVatAmount;
            $totalLineTotals += $itemLineTotal;
        }
        
        $this->subtotal = $subtotal;
        $this->vat_amount = $vatAmount;
        
        // Calculate cart-level discount
        if ($this->discount_type === 'percentage') {
            $this->discount_amount = $subtotal * ($this->discount_rate / 100);
        } elseif ($this->discount_type === 'fixed') {
            $this->discount_amount = $this->discount_rate;
        } else {
            $this->discount_amount = 0;
        }
        
        // Calculate withholding tax
        if ($this->withholding_tax_type === 'percentage') {
            $this->withholding_tax_amount = $subtotal * ($this->withholding_tax_rate / 100);
        } elseif ($this->withholding_tax_type === 'fixed') {
            $this->withholding_tax_amount = $this->withholding_tax_rate;
        } else {
            $this->withholding_tax_amount = 0;
        }
        
        // For VAT inclusive items, total should be sum of line totals minus discount and withholding tax
        // For VAT exclusive items, total should be subtotal + VAT minus discount and withholding tax
        // Since we have mixed items, use the sum of line totals (which already accounts for VAT type)
        $this->total_amount = $totalLineTotals - $this->discount_amount - $this->withholding_tax_amount;
        
        $this->save();
    }

    /**
     * Create double entry transactions for POS sale
     */
    public function createDoubleEntryTransactions()
    {
        // Check if period is locked
        $companyId = $this->company_id ?? ($this->branch->company_id ?? null);
        if ($companyId) {
            $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
            try {
                $periodLockService->validateTransactionDate($this->sale_date, $companyId, 'POS sale');
            } catch (\Exception $e) {
                \Log::warning('PosSale - Cannot post: Period is locked', [
                    'sale_id' => $this->id,
                    'pos_number' => $this->pos_number,
                    'sale_date' => $this->sale_date,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        $user = auth()->user();
        $userId = $user ? $user->id : ($this->created_by ?? 1); // Fallback to created_by or default user ID
        $this->glTransactions()->delete(); // Delete existing transactions
        
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
        if ($this->payment_method === 'bank') {
            // Use the chart account linked to the selected bank account
            $bank = BankAccount::find($this->bank_account_id);
            $debitAccountId = $bank ? $bank->chart_account_id : null;
        } elseif ($this->payment_method === 'card') {
            $debitAccountId = SystemSetting::where('key', 'inventory_default_bank_account')->value('value') ?? 3;
        } elseif ($this->payment_method === 'mobile_money') {
            $debitAccountId = SystemSetting::where('key', 'inventory_default_mobile_money_account')->value('value') ?? 1;
        } else {
            $debitAccountId = SystemSetting::where('key', 'inventory_default_cash_account')->value('value') ?? 1;
        }

        // Check if this account is in a completed reconciliation period - prevent posting
        if ($debitAccountId) {
            $isInCompletedReconciliation = \App\Services\BankReconciliationService::isChartAccountInCompletedReconciliation(
                $debitAccountId,
                $this->sale_date
            );
            
            if ($isInCompletedReconciliation) {
                \Log::warning('PosSale::createDoubleEntryTransactions - Cannot post: Account is in a completed reconciliation period', [
                    'pos_sale_id' => $this->id,
                    'pos_number' => $this->pos_number,
                    'chart_account_id' => $debitAccountId,
                    'payment_method' => $this->payment_method,
                    'sale_date' => $this->sale_date
                ]);
                throw new \Exception("Cannot post POS sale: Account is in a completed reconciliation period for date {$this->sale_date}.");
            }
        }

        // 1. Debit Cash/Bank/Mobile Money Account (amount actually received = total_amount)
        $totalAmountLCY = $convertToLCY($this->total_amount);
        $transactions[] = [
            'chart_account_id' => $debitAccountId,
            'customer_id' => $this->customer_id, // This can be null for walk-in customers
            'amount' => $totalAmountLCY,
            'nature' => 'debit',
            'transaction_id' => $this->id,
            'transaction_type' => 'pos_sale',
            'date' => $this->sale_date,
            'description' => $addCurrencyInfo("POS sale #{$this->pos_number} - " . ($this->customer_name ?? 'Walk-in Customer')),
            'branch_id' => $this->branch_id,
            'user_id' => $userId,
        ];

        // 3. Debit Sales Discount (if discount exists) - This reduces sales revenue
        $discountAmountLCY = ($this->discount_amount > 0) ? $convertToLCY($this->discount_amount) : 0;
        if ($this->discount_amount > 0) {
            $transactions[] = [
                'chart_account_id' => $this->getDiscountAccountId(),
                'customer_id' => $this->customer_id, // This can be null for walk-in customers
                'amount' => $discountAmountLCY,
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'pos_sale',
                'date' => $this->sale_date,
                'description' => $addCurrencyInfo("Sales Discount - POS sale #{$this->pos_number}"),
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];
        }

        // 4. Credit VAT Payable (if VAT exists)
        $vatAmountLCY = 0;
        if ($this->vat_amount > 0) {
            $vatAmountLCY = $convertToLCY($this->vat_amount);
            $transactions[] = [
                'chart_account_id' => $this->getVatAccountId(),
                'customer_id' => $this->customer_id, // This can be null for walk-in customers
                'amount' => $vatAmountLCY,
                'nature' => 'credit',
                'transaction_id' => $this->id,
                'transaction_type' => 'pos_sale',
                'date' => $this->sale_date,
                'description' => $addCurrencyInfo("VAT Payable - POS sale #{$this->pos_number}"),
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];
        }

        // 5. Credit Withholding Tax Payable (if withholding tax > 0)
        $whtAmountLCY = 0;
        if ($this->withholding_tax_amount > 0) {
            $whtAmountLCY = $convertToLCY($this->withholding_tax_amount);
            $transactions[] = [
                'chart_account_id' => $this->getWithholdingTaxAccountId(),
                'customer_id' => $this->customer_id, // This can be null for walk-in customers
                'amount' => $whtAmountLCY,
                'nature' => 'credit',
                'transaction_id' => $this->id,
                'transaction_type' => 'pos_sale',
                'date' => $this->sale_date,
                'description' => $addCurrencyInfo("Withholding Tax Payable - POS sale #{$this->pos_number}"),
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];
        }

        // 2. Credit Sales Revenue - Group items by their sales revenue account
        // Group items by their sales revenue account (item-specific or default)
        $salesRevenueByAccount = [];
        $defaultSalesAccountId = SystemSetting::where('key', 'inventory_default_sales_account')->value('value') ?? 25;
        
        // Ensure items are loaded with inventoryItem relationship
        $items = $this->items()->with('inventoryItem')->get();
        
        // Calculate total revenue from items (net of VAT) and group by account
        $totalRevenueFromItems = 0;
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
            $totalRevenueFromItems += $netLine;
        }
        
        // Calculate the difference to ensure exact balance
        // Sales Revenue must be credited GROSS (before discount) so that Debit Discount has a matching credit.
        // Gross sales = cash received + discount - VAT - WHT → credits 2000, debits 1800 (cash) + 200 (discount) = balanced
        $expectedRevenueLCY = round($totalAmountLCY + $discountAmountLCY - $vatAmountLCY - $whtAmountLCY, 2);
        
        // Convert each account's amount to LCY and calculate total
        $salesRevenueByAccountLCY = [];
        $calculatedTotalLCY = 0;
        foreach ($salesRevenueByAccount as $accountId => $amount) {
            $amountLCY = $convertToLCY($amount);
            $salesRevenueByAccountLCY[$accountId] = $amountLCY;
            $calculatedTotalLCY += $amountLCY;
        }
        
        // Calculate difference in LCY
        $differenceLCY = $expectedRevenueLCY - $calculatedTotalLCY;
        
        // Distribute the difference proportionally to accounts in LCY
        if (abs($differenceLCY) > 0.01 && count($salesRevenueByAccountLCY) > 0 && $calculatedTotalLCY > 0) {
            // Distribute difference proportionally based on each account's share
            foreach ($salesRevenueByAccountLCY as $accountId => $amountLCY) {
                $proportion = $amountLCY / $calculatedTotalLCY;
                $salesRevenueByAccountLCY[$accountId] += ($differenceLCY * $proportion);
            }
        } elseif (abs($differenceLCY) > 0.01 && count($salesRevenueByAccountLCY) > 0) {
            // If no revenue from items, add difference to first account
            $firstAccountId = array_key_first($salesRevenueByAccountLCY);
            $salesRevenueByAccountLCY[$firstAccountId] += $differenceLCY;
        }
        
        // Credit Sales Revenue for each account group
        foreach ($salesRevenueByAccountLCY as $accountId => $amountLCY) {
            if ($amountLCY > 0) {
                $transactions[] = [
                    'chart_account_id' => $accountId,
                    'customer_id' => $this->customer_id,
                    'amount' => round($amountLCY, 2),
                    'nature' => 'credit',
                    'transaction_id' => $this->id,
                    'transaction_type' => 'pos_sale',
                    'date' => $this->sale_date,
                    'description' => $addCurrencyInfo("Sales Revenue - POS sale #{$this->pos_number}"),
                    'branch_id' => $this->branch_id,
                    'user_id' => $userId,
                ];
            }
        }

        // 6. Debit Cost of Goods Sold and Credit Inventory
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
                'transaction_type' => 'pos_sale',
                'date' => $this->sale_date,
                'description' => "Cost of Goods Sold - POS sale #{$this->pos_number} [Amount in {$functionalCurrency}]",
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
                'transaction_type' => 'pos_sale',
                'date' => $this->sale_date,
                'description' => "Inventory Reduction - POS sale #{$this->pos_number} [Amount in {$functionalCurrency}]",
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];
        }

        $createdCount = 0;
        foreach ($transactions as $transaction) {
            GlTransaction::create($transaction);
            $createdCount++;
        }

        // Log activity for posting to GL
        $customerName = $this->customer ? $this->customer->name : ($this->customer_name ?? 'Walk-in Customer');
        $currencyInfo = $needsConversion ? " (FCY: {$saleCurrency} {$this->total_amount}, Rate: {$exchangeRate}, LCY: {$functionalCurrency} " . number_format($totalAmountLCY, 2) . ")" : "";
        $this->logActivity('post', "Posted POS Sale {$this->pos_number} to General Ledger for Customer: {$customerName}{$currencyInfo}", [
            'POS Number' => $this->pos_number,
            'Customer' => $customerName,
            'Sale Date' => $this->sale_date ? $this->sale_date->format('Y-m-d') : 'N/A',
            'Payment Method' => ucfirst(str_replace('_', ' ', $this->payment_method ?? 'cash')),
            'Currency' => $saleCurrency,
            'Functional Currency' => $functionalCurrency,
            'Exchange Rate' => $exchangeRate,
            'Total Amount (FCY)' => number_format($this->total_amount, 2) . ' ' . $saleCurrency,
            'Total Amount (LCY)' => $needsConversion ? number_format($totalAmountLCY, 2) . ' ' . $functionalCurrency : number_format($this->total_amount, 2) . ' ' . $functionalCurrency,
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
        // Restore cost layers before deleting movements (reverse the cost consumption)
        $costService = new \App\Services\InventoryCostService();
        $oldMovements = InventoryMovement::where('reference_type', 'pos_sale')
            ->where('reference_id', $this->id)
            ->get();
        
        foreach ($oldMovements as $oldMovement) {
            // Restore the cost layers that were consumed for this movement
            $costService->restoreInventoryCostLayers(
                $oldMovement->item_id,
                $oldMovement->quantity,
                $oldMovement->unit_cost,
                'POS Sale: ' . $this->pos_number
            );
        }

        // Remove any existing movements for this POS sale (for idempotency during updates)
        $existingMovements = InventoryMovement::where('reference_type', 'pos_sale')
            ->where('reference_id', $this->id)
            ->get();
        
        // Get location_id from existing movements or session, or use branch default
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
        InventoryMovement::where('reference_type', 'pos_sale')
            ->where('reference_id', $this->id)
            ->delete();

        foreach ($this->items as $item) {
            $inventoryItem = $item->inventoryItem;
            
            // Skip inventory movements for service items
            if ($inventoryItem->item_type === 'service' || !$inventoryItem->track_stock) {
                continue;
            }
            
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
                'POS Sale: ' . $this->pos_number,
                $this->sale_date,
                $this->branch_id,
                $locationId
            );
            
            // Create inventory movement
            InventoryMovement::create([
                'branch_id' => $this->branch_id,
                'location_id' => $locationId,
                'item_id' => $inventoryItem->id,
                'user_id' => auth()->id() ?? $this->created_by ?? 1,
                'movement_type' => 'sold',
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'unit_cost' => $costInfo['average_unit_cost'],
                'total_cost' => $costInfo['total_cost'],
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference' => $this->pos_number,
                'reference_type' => 'pos_sale',
                'reference_id' => $this->id,
                'reason' => 'POS sale',
                'notes' => "POS sale #{$this->pos_number}",
                'movement_date' => $this->sale_date,
            ]);

            // Consume stock using FEFO if item tracks expiry
            if ($inventoryItem->track_expiry) {
                $expiryService = new \App\Services\ExpiryStockService();
                $consumedLayers = $expiryService->consumeStock(
                    $inventoryItem->id,
                    $locationId,
                    $item->quantity,
                    'FEFO'
                );
                
                // Update PosSaleItem with expiry information from consumed layers
                if (!empty($consumedLayers)) {
                    // Get earliest expiry date and batch numbers
                    $earliestExpiryDate = collect($consumedLayers)->min('expiry_date');
                    $batchNumbers = collect($consumedLayers)->pluck('batch_number')->filter()->unique()->implode(', ');
                    
                    // Ensure expiry_date is a proper date format
                    if ($earliestExpiryDate) {
                        // Convert to Carbon if it's a string
                        if (is_string($earliestExpiryDate)) {
                            $earliestExpiryDate = \Carbon\Carbon::parse($earliestExpiryDate);
                        }
                        
                        // Update the item with expiry information
                        $item->update([
                            'expiry_date' => $earliestExpiryDate,
                            'batch_number' => !empty($batchNumbers) ? $batchNumbers : null,
                        ]);
                    }
                }
                
                // Log consumed layers for audit trail
                foreach ($consumedLayers as $layer) {
                    \Log::info('POS Sale: Consumed stock with expiry', [
                        'item_id' => $inventoryItem->id,
                        'batch_number' => $layer['batch_number'],
                        'expiry_date' => $layer['expiry_date'],
                        'quantity' => $layer['quantity'],
                        'unit_cost' => $layer['unit_cost']
                    ]);
                }
            }

            // Stock is now tracked via movements, no need to update item directly
        }
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
        return 172; // Discount Expense
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
        // Get actual cost from inventory movements for this POS sale
        // Use reference_type and reference_id for reliable matching
        $movements = InventoryMovement::where('reference_type', 'pos_sale')
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
     * Process payment and mark as completed
     */
    public function processPayment()
    {
        // For POS sales, payment is always immediate
        // The payment amounts are already recorded in cash_amount, card_amount, mobile_money_amount
        // Mark as completed
        $this->receipt_printed = true;
        $this->save();
    }

    /**
     * Get all receipts for this POS sale
     */
    public function receipts()
    {
        return $this->hasMany(\App\Models\Receipt::class, 'reference')
            ->where('reference_type', 'pos_sale');
    }

    /**
     * Get all journals for this POS sale
     */
    public function journals()
    {
        return $this->hasMany(\App\Models\Journal::class, 'reference')
            ->where('reference_type', 'pos_sale');
    }

    /**
     * Get the customer name for display
     */
    public function getCustomerNameAttribute()
    {
        if ($this->customer_id) {
            return $this->customer->name ?? 'Unknown Customer';
        }
        return $this->customer_name ?? 'Walk-in Customer';
    }

    /**
     * Get the customer relationship
     */
} 