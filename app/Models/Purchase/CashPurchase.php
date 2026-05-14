<?php

namespace App\Models\Purchase;

use App\Helpers\AmountInWords;
use App\Models\Inventory\Item;
use App\Models\Supplier;
use App\Models\BankAccount;
use App\Models\GlTransaction;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Inventory\Movement as InventoryMovement;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;

class CashPurchase extends Model
{
    use LogsActivity;
    
    protected $table = 'cash_purchases';

    protected $fillable = [
        'supplier_id',
        'purchase_date',
        'payment_method', // cash, bank
        'bank_account_id',
        'currency',
        'exchange_rate',
        'discount_amount',
        'notes',
        'terms_conditions',
        'subtotal',
        'vat_amount',
        'total_amount',
        'attachment',
        'branch_id',
        'company_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'exchange_rate' => 'decimal:6',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    // Accessors
    public function getEncodedIdAttribute(): string
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

    // Relationships
    public function items(): HasMany
    {
        return $this->hasMany(CashPurchaseItem::class, 'cash_purchase_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function glTransactions(): HasMany
    {
        return $this->hasMany(GlTransaction::class, 'transaction_id')
            ->where('transaction_type', 'cash_purchase');
    }

    // Helpers
    public function updateTotals(): void
    {
        $subtotal = (float) $this->items()->sum('net_amount');
        $vatAmount = (float) $this->items()->sum('vat_amount');
        $discount = (float) ($this->discount_amount ?? 0);
        $this->subtotal = $subtotal;
        $this->vat_amount = $vatAmount;
        $this->total_amount = max(0, $subtotal + $vatAmount - $discount);
        $this->save();
    }

    public function updateInventory(): void
    {
        // Remove any existing movements for this cash purchase (for idempotency during updates)
        InventoryMovement::where('reference_type', 'cash_purchase')
            ->where('reference_id', $this->id)
            ->delete();

        $this->loadMissing('items.inventoryItem');
        foreach ($this->items as $line) {
            if (!$line->inventoryItem) {
                continue;
            }

            $inventoryItem = $line->inventoryItem;

            // Normalize unit cost to VAT-exclusive for inventory valuation
            $costService = new \App\Services\InventoryCostService();
            $netUnitCost = $costService->normalizeCostToVatExclusive(
                (float) $line->unit_cost,
                $line->vat_type ?? 'no_vat',
                (float) ($line->vat_rate ?? 0)
            );
            $netTotalCost = (float) $line->quantity * $netUnitCost;

            // Get current stock using InventoryStockService
            $stockService = new \App\Services\InventoryStockService();
            $balanceBefore = $stockService->getItemStockAtLocation($inventoryItem->id, session('location_id'));
            $balanceAfter = $balanceBefore + (float) $line->quantity;

            // Add to cost layers for FIFO/Weighted Average valuation
            $costService->addInventory(
                $inventoryItem->id,
                (float) $line->quantity,
                $netUnitCost,
                'purchase', // transaction_type must match ENUM: 'purchase', 'sale', etc.
                'Cash Purchase ' . $this->id,
                $this->purchase_date,
                $line->vat_type ?? 'no_vat',
                (float) ($line->vat_rate ?? 0)
            );

            // Create movement record
            InventoryMovement::create([
                'item_id' => $inventoryItem->id,
                'user_id' => auth()->id() ?? ($this->created_by ?? 1),
                'branch_id' => $this->branch_id,
                'location_id' => session('location_id'),
                'movement_type' => 'purchased',
                'quantity' => (float) $line->quantity,
                'unit_cost' => $netUnitCost,
                'unit_price' => $netUnitCost,
                'total_cost' => $netTotalCost,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference' => (string) $this->id,
                'reference_type' => 'cash_purchase',
                'reference_id' => $this->id,
                'reason' => 'Cash purchase',
                'notes' => 'Cash purchase',
                'movement_date' => $this->purchase_date,
            ]);

            // Add expiry tracking if item tracks expiry
            if ($inventoryItem->track_expiry && $line->expiry_date) {
                $expiryService = new \App\Services\ExpiryStockService();
                $expiryService->addStock(
                    $inventoryItem->id,
                    session('location_id'),
                    (float) $line->quantity,
                    $netUnitCost, // Use VAT-exclusive cost for expiry tracking
                    $line->expiry_date,
                    'cash_purchase',
                    $this->id,
                    $line->batch_number,
                    (string) $this->id
                );
            }

            // Stock is now tracked via movements, no need to update item directly
        }
    }

    public function createDoubleEntryTransactions(): void
    {
        // Check if period is locked
        $companyId = $this->company_id ?? ($this->branch->company_id ?? null);
        if ($companyId) {
            $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
            try {
                $periodLockService->validateTransactionDate($this->purchase_date, $companyId, 'cash purchase');
            } catch (\Exception $e) {
                \Log::warning('CashPurchase - Cannot post: Period is locked', [
                    'purchase_id' => $this->id,
                    'purchase_date' => $this->purchase_date,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        // Remove any existing GL rows for idempotency
        $this->glTransactions()->delete();

        // Get functional currency and check if conversion is needed
        $functionalCurrency = SystemSetting::getValue('functional_currency', $this->company->functional_currency ?? 'TZS');
        $purchaseCurrency = $this->currency ?? $functionalCurrency;
        $exchangeRate = $this->exchange_rate ?? 1.000000;
        $needsConversion = ($purchaseCurrency !== $functionalCurrency && $exchangeRate != 1.000000);
        
        // Helper function to convert FCY to LCY if needed
        $convertToLCY = function($fcyAmount) use ($needsConversion, $exchangeRate) {
            return $needsConversion ? round($fcyAmount * $exchangeRate, 2) : $fcyAmount;
        };
        
        // Helper function to add currency info to description
        $addCurrencyInfo = function($description) use ($needsConversion, $purchaseCurrency, $functionalCurrency, $exchangeRate) {
            if ($needsConversion) {
                return $description . " [FCY: {$purchaseCurrency}, Rate: {$exchangeRate}, Converted to {$functionalCurrency}]";
            }
            return $description;
        };

        $transactions = [];

        // Accounts
        $inventoryAccountId = (int) (SystemSetting::where('key', 'inventory_default_inventory_account')->value('value') ?? 185);
        $vatAccountId = (int) (SystemSetting::where('key', 'inventory_default_vat_account')->value('value') ?? 36);
        $discountIncomeAccountId = (int) (SystemSetting::where('key', 'inventory_default_discount_income_account')->value('value') ?? 0);

        // Determine credit account (cash/bank)
        $creditAccountId = null;
        if ($this->payment_method === 'bank' && $this->bank_account_id && $this->bankAccount) {
            $creditAccountId = (int) $this->bankAccount->chart_account_id;
        }
        if (!$creditAccountId) {
            $creditAccountId = (int) (SystemSetting::where('key', 'inventory_default_cash_account')->value('value') ?? 1);
        }

        // Check if this account is in a completed reconciliation period - prevent posting
        if ($creditAccountId) {
            $isInCompletedReconciliation = \App\Services\BankReconciliationService::isChartAccountInCompletedReconciliation(
                $creditAccountId,
                $this->purchase_date
            );
            
            if ($isInCompletedReconciliation) {
                \Log::warning('CashPurchase::postGlTransactions - Cannot post: Account is in a completed reconciliation period', [
                    'cash_purchase_id' => $this->id,
                    'purchase_number' => $this->purchase_number ?? 'N/A',
                    'chart_account_id' => $creditAccountId,
                    'payment_method' => $this->payment_method,
                    'purchase_date' => $this->purchase_date
                ]);
                throw new \Exception("Cannot post cash purchase: Account is in a completed reconciliation period for date {$this->purchase_date}.");
            }
        }

        $userId = auth()->id() ?? ($this->created_by ?? 1);

        $this->loadMissing('items.inventoryItem');
        
        $inventoryNet = 0.0;
        $costService = new \App\Services\InventoryCostService();
        
        foreach ($this->items as $line) {
            // Use the same VAT-exclusive calculation method as purchase invoices
            $netUnitCost = $costService->normalizeCostToVatExclusive(
                (float) $line->unit_cost,
                $line->vat_type ?? 'no_vat',
                (float) ($line->vat_rate ?? 0)
            );
            $netUnitCostRounded = round($netUnitCost, 2);
            $net = round((float) $line->quantity * $netUnitCostRounded, 2);
            
            if ($line->isInventory()) {
                $inventoryNet += $net;
            } else {
                // Non-inventory: treat as expense (inventory account or expense fallback)
                $inventoryNet += $net;
            }
        }

        if ($inventoryNet > 0) {
            $inventoryNetLCY = $convertToLCY($inventoryNet);
            $transactions[] = [
                'chart_account_id' => $inventoryAccountId,
                'supplier_id' => $this->supplier_id,
                'amount' => $inventoryNetLCY,
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'cash_purchase',
                'date' => $this->purchase_date,
                'description' => $addCurrencyInfo('Inventory Purchase - Cash purchase'),
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];
        }

        // 2) Debit VAT Input if applicable
        if (($this->vat_amount ?? 0) > 0) {
            $vatAmountLCY = $convertToLCY($this->vat_amount);
            $transactions[] = [
                'chart_account_id' => $vatAccountId,
                'supplier_id' => $this->supplier_id,
                'amount' => $vatAmountLCY,
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'cash_purchase',
                'date' => $this->purchase_date,
                'description' => $addCurrencyInfo('VAT Input - Cash purchase'),
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];
        }

        // 3) Credit Cash/Bank (sum of all debits to ensure balance)
        // Calculate total debits: inventory + VAT
        // Note: Discount is handled as a separate credit entry below
        $totalDebits = $inventoryNet;
        $totalDebits += ($this->vat_amount ?? 0);
        
        // Subtract discount from cash credit (discount reduces what we pay)
        $totalDebits -= ($this->discount_amount ?? 0);
        
        if ($totalDebits > 0) {
            $totalDebitsLCY = $convertToLCY($totalDebits);
            $transactions[] = [
                'chart_account_id' => $creditAccountId,
                'supplier_id' => $this->supplier_id,
                'amount' => $totalDebitsLCY,
                'nature' => 'credit',
                'transaction_id' => $this->id,
                'transaction_type' => 'cash_purchase',
                'date' => $this->purchase_date,
                'description' => $addCurrencyInfo('Cash/Bank Payment - Cash purchase'),
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];
        }

        // 4) If discount provided, credit Discount Income (Purchase discount)
        if (($this->discount_amount ?? 0) > 0 && $discountIncomeAccountId) {
            $discountAmountLCY = $convertToLCY($this->discount_amount);
            $transactions[] = [
                'chart_account_id' => $discountIncomeAccountId,
                'supplier_id' => $this->supplier_id,
                'amount' => $discountAmountLCY,
                'nature' => 'credit',
                'transaction_id' => $this->id,
                'transaction_type' => 'cash_purchase',
                'date' => $this->purchase_date,
                'description' => $addCurrencyInfo('Purchase Discount'),
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ];
        }

        $createdCount = 0;
        foreach ($transactions as $t) {
            GlTransaction::create($t);
            $createdCount++;
        }
        
        // Log activity for posting to GL
        $supplierName = $this->supplier ? $this->supplier->name : 'N/A';
        $totalAmountLCY = isset($totalAmountLCY) ? $totalAmountLCY : $convertToLCY($this->total_amount ?? 0);
        $currencyInfo = $needsConversion ? " (FCY: {$purchaseCurrency} " . number_format($this->total_amount ?? 0, 2) . ", Rate: {$exchangeRate}, LCY: {$functionalCurrency} " . number_format($totalAmountLCY, 2) . ")" : "";
        $this->logActivity('post', "Posted Cash Purchase to General Ledger for Supplier: {$supplierName}{$currencyInfo}", [
            'Supplier' => $supplierName,
            'Purchase Date' => $this->purchase_date ? $this->purchase_date->format('Y-m-d') : 'N/A',
            'Payment Method' => ucfirst(str_replace('_', ' ', $this->payment_method ?? 'cash')),
            'Currency' => $purchaseCurrency,
            'Functional Currency' => $functionalCurrency,
            'Exchange Rate' => $exchangeRate,
            'Total Amount (FCY)' => number_format($this->total_amount ?? 0, 2) . ' ' . $purchaseCurrency,
            'Total Amount (LCY)' => $needsConversion ? number_format($totalAmountLCY, 2) . ' ' . $functionalCurrency : number_format($this->total_amount ?? 0, 2) . ' ' . $functionalCurrency,
            'Subtotal' => number_format($this->subtotal ?? 0, 2),
            'VAT Amount' => number_format($this->vat_amount ?? 0, 2),
            'Discount Amount' => number_format($this->discount_amount ?? 0, 2),
            'GL Transactions Created' => $createdCount,
            'Posted By' => auth()->user()->name ?? 'System',
            'Posted At' => now()->format('Y-m-d H:i:s')
        ]);
    }

}
