<?php

namespace App\Models\Purchase;

use App\Models\Supplier;
use App\Models\Company;
use App\Models\Branch;
use App\Models\User;
use App\Models\GlTransaction;
use App\Models\SystemSetting;
use App\Models\Payment;
use App\Helpers\AmountInWords;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vinkla\Hashids\Facades\Hashids;

class PurchaseInvoice extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'supplier_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'subtotal',
        'vat_amount',
        'discount_amount',
        'total_amount',
        'status',
        'currency',
        'exchange_rate',
        'notes',
        'attachment',
        'company_id',
        'branch_id',
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
        'exchange_rate' => 'decimal:6',
    ];

    public function getEncodedIdAttribute(): string
    {
        return Hashids::encode($this->id ?? 0);
    }

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function items(): HasMany { return $this->hasMany(PurchaseInvoiceItem::class); }
    public function glTransactions(): HasMany
    {
        return $this->hasMany(GlTransaction::class, 'transaction_id')
            ->where('transaction_type', 'purchase_invoice')
            ->orderBy('date');
    }

    /**
     * Helper attribute: whether this purchase invoice has been posted to GL
     */
    public function getGlPostedAttribute(): bool
    {
        return $this->glTransactions()->exists();
    }

    // Payments relationship via payments table
    public function payments(): HasMany
    {
        // Link by supplier and reference_number = invoice_number, reference_type = 'purchase_invoice'
        return $this->hasMany(Payment::class, 'supplier_id', 'supplier_id')
            ->where('reference_type', 'purchase_invoice')
            ->where('reference_number', $this->invoice_number);
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) ($this->payments()->sum('amount') ?? 0);
    }

    public function getOutstandingAmountAttribute(): float
    {
        return max(0, (float) $this->total_amount - (float) $this->total_paid);
    }

    public function postGlTransactions(): void
    {
        // Check if period is locked
        $companyId = $this->company_id ?? ($this->branch->company_id ?? null);
        if ($companyId) {
            $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
            try {
                $periodLockService->validateTransactionDate($this->invoice_date, $companyId, 'purchase invoice');
            } catch (\Exception $e) {
                \Log::warning('PurchaseInvoice - Cannot post: Period is locked', [
                    'invoice_id' => $this->id,
                    'invoice_number' => $this->invoice_number,
                    'invoice_date' => $this->invoice_date,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        // Ensure items are loaded
        if (!$this->relationLoaded('items')) {
            $this->load('items');
        }
        
        // Remove existing entries if reposting
        GlTransaction::where('transaction_type', 'purchase_invoice')->where('transaction_id', $this->id)->delete();

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

        $userId = auth()->id() ?? $this->created_by ?? 1;

        // Accounts from settings
        $apAccountId = (int) (SystemSetting::where('key', 'inventory_default_purchase_payable_account')->value('value') ?? 30); // Trade Payables
        $vatAccountId = (int) (SystemSetting::where('key', 'inventory_default_vat_account')->value('value') ?? 36); // VAT Payable (VAT Control Account)
        $inventoryAccountId = (int) (SystemSetting::where('key', 'inventory_default_inventory_account')->value('value') ?? 185); // Inventory
        $expenseFallbackAccountId = (int) (SystemSetting::where('key', 'inventory_default_cost_account')->value('value') ?? 173); // COGS as expense fallback
        $discountIncomeAccountId = (int) (SystemSetting::where('key', 'inventory_default_discount_income_account')->value('value') ?? 52);

        // Aggregate debits by account to avoid many rows for same account
        // IMPORTANT:
        // - We now use the STORED amounts on each line (line_total and vat_amount)
        //   instead of recalculating from unit_cost.
        // - This ensures that:
        //   Invoice totals (subtotal + VAT - discounts) == GL debits + credits exactly,
        //   eliminating small rounding differences like 11,200.00 vs 11,200.05.
        $debitTotalsByAccount = [];
        $totalVat = 0;
        foreach ($this->items as $line) {
            // Net (exclusive of VAT) = line_total - vat_amount for both exclusive & inclusive VAT types
            $lineTotal = (float) $line->line_total;
            $vatAmount = (float) $line->vat_amount;
            $net = round($lineTotal - $vatAmount, 2);

            // Decide debit account based on item type
            if ($line->isInventory()) {
                // Inventory purchase - use inventory account
                $debitAccount = $line->inventory_item_id ? $inventoryAccountId : $expenseFallbackAccountId;
            } else {
                // Default to expense
                $debitAccount = $expenseFallbackAccountId;
            }

            if ($net > 0) {
                if (!isset($debitTotalsByAccount[$debitAccount])) {
                    $debitTotalsByAccount[$debitAccount] = 0;
                }
                // Convert to LCY before aggregating
                $debitTotalsByAccount[$debitAccount] += $convertToLCY($net);
            }

            if ($vatAmount > 0) {
                // Convert VAT to LCY before aggregating
                $totalVat += $convertToLCY($vatAmount);
            }
        }

        // Post aggregated debits by account
        foreach ($debitTotalsByAccount as $accountId => $amount) {
            if ($amount <= 0) { continue; }
            
            $description = 'Purchase Invoice ' . $this->invoice_number . ' - Goods/Services';
            
            GlTransaction::create([
                'chart_account_id' => (int) $accountId,
                'supplier_id' => $this->supplier_id,
                'amount' => $amount, // Already converted to LCY
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'purchase_invoice',
                'date' => $this->invoice_date,
                'description' => $addCurrencyInfo($description),
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ]);
        }

        // Calculate total debits to ensure double entry balances
        $totalDebits = array_sum($debitTotalsByAccount);
        
        // Post single VAT debit if any (already converted to LCY)
        if ($totalVat > 0) {
            GlTransaction::create([
                'chart_account_id' => $vatAccountId,
                'supplier_id' => $this->supplier_id,
                'amount' => $totalVat, // Already converted to LCY
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'purchase_invoice',
                'date' => $this->invoice_date,
                'description' => $addCurrencyInfo('VAT on Purchase Invoice ' . $this->invoice_number),
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ]);
            $totalDebits += $totalVat;
        }

        // Handle discount: credit discount income if configured and discount > 0
        if (($this->discount_amount ?? 0) > 0 && $discountIncomeAccountId) {
            $discountAmountLCY = $convertToLCY($this->discount_amount);
            GlTransaction::create([
                'chart_account_id' => $discountIncomeAccountId,
                'supplier_id' => $this->supplier_id,
                'amount' => $discountAmountLCY,
                'nature' => 'credit',
                'transaction_id' => $this->id,
                'transaction_type' => 'purchase_invoice',
                'date' => $this->invoice_date,
                'description' => $addCurrencyInfo('Purchase Discount - Purchase Invoice ' . $this->invoice_number),
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
            ]);
            // Subtract discount from total debits (since it's a credit)
            $totalDebits -= $discountAmountLCY;
        }

        // Credit Accounts Payable by sum of debits to ensure double entry balances
        // This ensures the entry always balances, accounting for rounding differences
        $apAmountLCY = round($totalDebits, 2); // Already converted to LCY
        GlTransaction::create([
            'chart_account_id' => $apAccountId,
            'supplier_id' => $this->supplier_id,
            'amount' => $apAmountLCY,
            'nature' => 'credit',
            'transaction_id' => $this->id,
            'transaction_type' => 'purchase_invoice',
            'date' => $this->invoice_date,
            'description' => $addCurrencyInfo('Accounts Payable - Purchase Invoice ' . $this->invoice_number),
            'branch_id' => $this->branch_id,
            'user_id' => $userId,
        ]);
        
        // Log activity for posting to GL
        $supplierName = $this->supplier ? $this->supplier->name : 'N/A';
        $currencyInfo = $needsConversion ? " (FCY: {$invoiceCurrency} {$this->total_amount}, Rate: {$exchangeRate}, LCY: {$functionalCurrency} " . number_format($apAmountLCY, 2) . ")" : "";
        $this->logActivity('post', "Posted Purchase Invoice {$this->invoice_number} to General Ledger for Supplier: {$supplierName}{$currencyInfo}", [
            'Invoice Number' => $this->invoice_number,
            'Supplier' => $supplierName,
            'Invoice Date' => $this->invoice_date ? $this->invoice_date->format('Y-m-d') : 'N/A',
            'Due Date' => $this->due_date ? $this->due_date->format('Y-m-d') : 'N/A',
            'Currency' => $invoiceCurrency,
            'Functional Currency' => $functionalCurrency,
            'Exchange Rate' => $exchangeRate,
            'Total Amount (FCY)' => number_format($this->total_amount, 2) . ' ' . $invoiceCurrency,
            'Total Amount (LCY)' => $needsConversion ? number_format($apAmountLCY, 2) . ' ' . $functionalCurrency : number_format($this->total_amount, 2) . ' ' . $functionalCurrency,
            'Subtotal' => number_format($this->subtotal, 2),
            'VAT Amount' => number_format($this->vat_amount, 2),
            'Discount Amount' => number_format($this->discount_amount, 2),
            'Posted By' => auth()->user()->name ?? 'System',
            'Posted At' => now()->format('Y-m-d H:i:s')
        ]);
    }

    public function postInventoryMovements(): void
    {
        // Ensure items are loaded
        if (!$this->relationLoaded('items')) {
            $this->load('items');
        }
        
        \Log::info('Purchase Invoice postInventoryMovements: Starting', [
            'invoice_id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'items_count' => $this->items->count()
        ]);

        // Remove any previous movements for this invoice
        $oldMovements = \App\Models\Inventory\Movement::where('reference_type', 'purchase_invoice')
            ->where('reference_id', $this->id)
            ->get();
        
        // Remove old cost layers created from this purchase invoice before deleting movements
        // This prevents duplicate layers when a purchase invoice is updated
        $reference = 'Purchase Invoice ' . $this->invoice_number;
        
        foreach ($oldMovements as $oldMovement) {
            // Find and remove cost layers created from this purchase
            $oldLayers = \App\Models\InventoryCostLayer::where('item_id', $oldMovement->item_id)
                ->where('reference', $reference)
                ->where('transaction_type', 'purchase')
                ->where('quantity', '>', 0) // Only purchase layers (not sale layers)
                ->get();
            
            foreach ($oldLayers as $oldLayer) {
                // If layer has remaining quantity, we need to reduce stock
                // But if it's been consumed (sold), we should keep it for historical accuracy
                // For now, we'll delete layers that match this purchase reference
                // The new purchase will create new layers with correct costs
                if ($oldLayer->remaining_quantity > 0) {
                    // Create a negative adjustment to reverse the remaining stock
                    \App\Models\InventoryCostLayer::create([
                        'item_id' => $oldMovement->item_id,
                        'reference' => $reference . ' (Reversed)',
                        'transaction_type' => 'adjustment_out',
                        'quantity' => -$oldLayer->remaining_quantity,
                        'remaining_quantity' => 0,
                        'unit_cost' => $oldLayer->unit_cost,
                        'total_cost' => -($oldLayer->remaining_quantity * $oldLayer->unit_cost),
                        'transaction_date' => $this->invoice_date,
                        'is_consumed' => true,
                    ]);
                }
                
                // Delete the old layer (consumed portions are already reflected in sale layers)
                $oldLayer->delete();
            }
        }
        
        // Update weighted average cost for affected items after removing layers
        $costService = new \App\Services\InventoryCostService();
        $affectedItemIds = $oldMovements->pluck('item_id')->unique();
        foreach ($affectedItemIds as $itemId) {
            // Force recalculation of weighted average cost after removing old layers
            // This ensures cost_price is updated correctly
            $costService->updateWeightedAverageCost($itemId);
        }
        
        // Now delete the old movements
        \App\Models\Inventory\Movement::where('reference_type', 'purchase_invoice')
            ->where('reference_id', $this->id)
            ->delete();

        \Log::info('Purchase Invoice postInventoryMovements: Previous movements and cost layers removed');

        foreach ($this->items as $line) {
            // Skip non-inventory items
            if (!$line->inventory_item_id || !$line->isInventory()) {
                \Log::info('Purchase Invoice postInventoryMovements: Skipping non-stock line', [
                    'line_id' => $line->id,
                    'description' => $line->description
                ]);
                continue; // non-stock line
            }

            $item = \App\Models\Inventory\Item::find($line->inventory_item_id);
            if (!$item) {
                \Log::warning('Purchase Invoice postInventoryMovements: Item not found', [
                    'inventory_item_id' => $line->inventory_item_id
                ]);
                continue;
            }

            \Log::info('Purchase Invoice postInventoryMovements: Processing item', [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'quantity' => $line->quantity
            ]);

            $quantity = (float) $line->quantity;
            if ($quantity <= 0) {
                continue;
            }

            // Normalize unit cost to VAT-exclusive for inventory valuation
            // This ensures inventory valuation always uses VAT-exclusive costs
            $costService = new \App\Services\InventoryCostService();
            $netUnitCost = $costService->normalizeCostToVatExclusive(
                (float) $line->unit_cost,
                $line->vat_type ?? 'no_vat',
                (float) ($line->vat_rate ?? 0)
            );
            // Round unit_cost to 2 decimals first (matching database precision)
            // Then multiply by quantity to ensure exact match with GL transactions
            $netUnitCostRounded = round($netUnitCost, 2);
            $netTotalCost = round($quantity * $netUnitCostRounded, 2);

            // Get current stock using InventoryStockService
            $stockService = new \App\Services\InventoryStockService();
            // Use the first available location for this branch if session location is not available
            $locationId = session('location_id') ?? \App\Models\InventoryLocation::where('branch_id', $this->branch_id)->first()?->id ?? 1;
            \Log::info('Purchase Invoice postInventoryMovements: Using location', [
                'location_id' => $locationId,
                'session_location_id' => session('location_id'),
                'branch_id' => $this->branch_id
            ]);
            
            // For backdated invoices, calculate stock as of the invoice date (before this transaction)
            // This ensures balance_before is correct even when invoice is created later
            // Use the invoice's created_at timestamp to exclude same-day transactions that happened after this purchase
            $asOfTimestamp = $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s');
            $balanceBefore = $stockService->getItemStockAtLocationAsOfDate($item->id, $locationId, $this->invoice_date, null, $asOfTimestamp);
            $balanceAfter = $balanceBefore + $quantity;

            \Log::info('Purchase Invoice postInventoryMovements: Stock calculation', [
                'item_id' => $item->id,
                'balance_before' => $balanceBefore,
                'quantity' => $quantity,
                'balance_after' => $balanceAfter
            ]);

            // Add to cost layers for FIFO/Weighted Average valuation
            $costService->addInventory(
                $item->id,
                $quantity,
                $netUnitCost,
                'purchase', // transaction_type must match ENUM: 'purchase', 'sale', etc.
                'Purchase Invoice ' . $this->invoice_number,
                $this->invoice_date,
                $line->vat_type ?? 'no_vat',
                (float) ($line->vat_rate ?? 0)
            );

            // Create movement
            \App\Models\Inventory\Movement::create([
                'branch_id' => $this->branch_id,
                'location_id' => $locationId,
                'item_id' => $item->id,
                'user_id' => $this->created_by ?? (auth()->id() ?? 1),
                'movement_type' => 'purchased',
                'quantity' => $quantity,
                'unit_cost' => $netUnitCostRounded, // Use rounded unit_cost to match GL
                'total_cost' => $netTotalCost,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reason' => 'Purchase Invoice',
                'reference_number' => $this->invoice_number,
                'reference_type' => 'purchase_invoice',
                'reference_id' => $this->id,
                'reference' => 'Purchase Invoice ' . $this->invoice_number,
                'notes' => $line->description,
                'movement_date' => $this->invoice_date,
            ]);

            // Add expiry tracking if item tracks expiry
            if ($item->track_expiry && $line->expiry_date) {
                $expiryService = new \App\Services\ExpiryStockService();
                $expiryService->addStock(
                    $item->id,
                    $locationId,
                    $quantity,
                    $netUnitCost,
                    $line->expiry_date,
                    'purchase_invoice',
                    $this->id,
                    $line->batch_number,
                    $this->invoice_number
                );
                
                \Log::info('Purchase Invoice: Added expiry tracking', [
                    'item_id' => $item->id,
                    'expiry_date' => $line->expiry_date,
                    'batch_number' => $line->batch_number,
                    'quantity' => $quantity
                ]);
            }

            \Log::info('Purchase Invoice postInventoryMovements: Movement created', [
                'movement_id' => \App\Models\Inventory\Movement::latest()->first()->id,
                'item_id' => $item->id,
                'quantity' => $quantity
            ]);

            // Stock is now tracked via movements, no need to update item directly
            // Cost price updates are handled by InventoryCostService when needed
        }

        \Log::info('Purchase Invoice postInventoryMovements: Completed', [
            'invoice_id' => $this->id,
            'processed_items' => $this->items->where('inventory_item_id', '!=', null)->count()
        ]);
    }

    public function syncLinkedOpeningBalance(): void
    {
        $openingBalance = \App\Models\Purchase\OpeningBalance::where('purchase_invoice_id', $this->id)->first();
        if (!$openingBalance) return;
        
        $openingBalance->paid_amount = min((float)$this->total_paid, (float)$openingBalance->amount);
        $openingBalance->balance_due = max((float)$openingBalance->amount - (float)$openingBalance->paid_amount, 0);
        $openingBalance->status = $openingBalance->balance_due <= 0.0 ? 'closed' : 'posted';
        $openingBalance->save();
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

    /**
     * Convert total_amount to words using shared helper.
     */
    public function getAmountInWords()
    {
        return AmountInWords::convert($this->total_amount);
    }
}
