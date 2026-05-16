<?php

namespace App\Models\Purchase;

use App\Helpers\AmountInWords;
use App\Models\Inventory\Item;
use App\Models\Supplier;
use App\Models\BankAccount;
use App\Models\GlTransaction;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Inventory\Movement as InventoryMovement;
use App\Services\Purchase\SupplierAdvanceAllocationService;
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
        'journal_id',
        'purchase_date',
        'payment_method', // cash, bank
        'bank_account_id',
        'currency',
        'exchange_rate',
        'discount_amount',
        'supplier_advance_applied_amount',
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
        'supplier_advance_applied_amount' => 'decimal:2',
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

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    /** @deprecated Legacy direct GL; new postings use journal. */
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

    /**
     * Post inventory/VAT/advance settlement via Journal → Journal items → GL (no Payment / bank).
     */
    public function createDoubleEntryTransactions(): void
    {
        $companyId = $this->company_id ?? ($this->branch->company_id ?? null);
        if ($companyId) {
            $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
            $periodLockService->validateTransactionDate($this->purchase_date, $companyId, 'cash purchase');
        }

        /** @var SupplierAdvanceAllocationService $advanceAllocation */
        $advanceAllocation = app(SupplierAdvanceAllocationService::class);
        $advanceAllocation->deleteDeductionsForCashPurchase((int) $this->id);

        // Remove legacy direct cash_purchase GL rows
        $this->glTransactions()->delete();

        $functionalCurrency = SystemSetting::getValue('functional_currency', $this->company->functional_currency ?? 'TZS');
        $purchaseCurrency = $this->currency ?? $functionalCurrency;
        $exchangeRate = (float) ($this->exchange_rate ?? 1.000000);
        $needsConversion = ($purchaseCurrency !== $functionalCurrency && $exchangeRate != 1.000000);

        $convertToLCY = function ($fcyAmount) use ($needsConversion, $exchangeRate) {
            return $needsConversion ? round((float) $fcyAmount * $exchangeRate, 2) : round((float) $fcyAmount, 2);
        };

        $addCurrencyInfo = function ($description) use ($needsConversion, $purchaseCurrency, $functionalCurrency, $exchangeRate) {
            if ($needsConversion) {
                return $description." [FCY: {$purchaseCurrency}, Rate: {$exchangeRate}, Converted to {$functionalCurrency}]";
            }

            return $description;
        };

        $inventoryAccountId = (int) (SystemSetting::where('key', 'inventory_default_inventory_account')->value('value') ?? 185);
        $vatAccountId = (int) (SystemSetting::where('key', 'inventory_default_vat_account')->value('value') ?? 36);
        $discountIncomeAccountId = (int) (SystemSetting::where('key', 'inventory_default_discount_income_account')->value('value') ?? 0);

        $userId = auth()->id() ?? ($this->created_by ?? 1);
        $this->loadMissing('items.inventoryItem', 'supplier');

        $inventoryNet = 0.0;
        $costService = new \App\Services\InventoryCostService();
        foreach ($this->items as $line) {
            $netUnitCost = $costService->normalizeCostToVatExclusive(
                (float) $line->unit_cost,
                $line->vat_type ?? 'no_vat',
                (float) ($line->vat_rate ?? 0)
            );
            $inventoryNet += round((float) $line->quantity * round($netUnitCost, 2), 2);
        }

        $totalDebitsFcy = $inventoryNet + (float) ($this->vat_amount ?? 0) - (float) ($this->discount_amount ?? 0);
        $totalDebitsLCY = $convertToLCY($totalDebitsFcy);

        $applyFcy = round((float) ($this->supplier_advance_applied_amount ?? 0), 2);
        $applyLcy = $convertToLCY($applyFcy);

        if ($totalDebitsLCY <= 0) {
            throw new \RuntimeException('Cash purchase total must be greater than zero.');
        }

        if (abs($applyFcy - (float) $this->total_amount) > 0.05) {
            throw new \RuntimeException('Cash purchase must be settled entirely from supplier advance (applied amount must equal purchase total).');
        }

        if (abs($applyLcy - $totalDebitsLCY) > 0.05) {
            throw new \RuntimeException('Supplier advance applied does not match the purchase settlement amount.');
        }

        $advanceSlices = $advanceAllocation->allocateFifo(
            (int) $this->supplier_id,
            (int) $this->company_id,
            $this->branch_id,
            $applyLcy
        );
        $allocatedSum = round((float) $advanceSlices->sum('amount'), 2);
        if ($advanceSlices->isEmpty()) {
            throw new \RuntimeException('No supplier advance balance is available for this supplier.');
        }
        if (abs($allocatedSum - $applyLcy) > 0.05) {
            throw new \RuntimeException('Supplier advance balance is insufficient for this purchase.');
        }

        foreach ($advanceSlices->pluck('debit_chart_account_id')->unique()->filter() as $advChartId) {
            if (\App\Services\BankReconciliationService::isChartAccountInCompletedReconciliation((int) $advChartId, $this->purchase_date)) {
                throw new \Exception("Cannot post cash purchase: a supplier advance account is in a completed bank reconciliation for date {$this->purchase_date}.");
            }
        }

        $journalLines = [];

        if ($inventoryNet > 0) {
            $journalLines[] = [
                'chart_account_id' => $inventoryAccountId,
                'amount' => $convertToLCY($inventoryNet),
                'nature' => 'debit',
                'description' => $addCurrencyInfo('Inventory Purchase - Cash purchase #'.$this->id),
            ];
        }

        if (($this->vat_amount ?? 0) > 0) {
            $journalLines[] = [
                'chart_account_id' => $vatAccountId,
                'amount' => $convertToLCY($this->vat_amount),
                'nature' => 'debit',
                'description' => $addCurrencyInfo('VAT Input - Cash purchase #'.$this->id),
            ];
        }

        foreach ($advanceSlices as $slice) {
            $journalLines[] = [
                'chart_account_id' => (int) $slice['debit_chart_account_id'],
                'amount' => (float) $slice['amount'],
                'nature' => 'credit',
                'description' => $addCurrencyInfo('Supplier advance applied - Cash purchase #'.$this->id),
            ];
        }

        if (($this->discount_amount ?? 0) > 0 && $discountIncomeAccountId) {
            $journalLines[] = [
                'chart_account_id' => $discountIncomeAccountId,
                'amount' => $convertToLCY($this->discount_amount),
                'nature' => 'credit',
                'description' => $addCurrencyInfo('Purchase Discount - Cash purchase #'.$this->id),
            ];
        }

        $debitSum = round(collect($journalLines)->where('nature', 'debit')->sum('amount'), 2);
        $creditSum = round(collect($journalLines)->where('nature', 'credit')->sum('amount'), 2);
        if (abs($debitSum - $creditSum) > 0.05) {
            throw new \RuntimeException("Journal is out of balance (debits {$debitSum}, credits {$creditSum}).");
        }

        $supplierName = $this->supplier->name ?? 'Supplier';
        $journal = $this->journal_id ? Journal::find($this->journal_id) : null;

        if ($journal) {
            GlTransaction::where('transaction_type', 'journal')->where('transaction_id', $journal->id)->delete();
            $journal->items()->delete();
            $journal->update([
                'date' => $this->purchase_date,
                'reference' => (string) $this->id,
                'reference_type' => 'cash_purchase',
                'supplier_id' => $this->supplier_id,
                'description' => 'Cash purchase #'.$this->id.' — '.$supplierName.' (supplier advance)',
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
                'approved' => true,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);
        } else {
            $journal = Journal::create([
                'date' => $this->purchase_date,
                'reference' => (string) $this->id,
                'reference_type' => 'cash_purchase',
                'supplier_id' => $this->supplier_id,
                'description' => 'Cash purchase #'.$this->id.' — '.$supplierName.' (supplier advance)',
                'branch_id' => $this->branch_id,
                'user_id' => $userId,
                'approved' => true,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);
            $this->journal_id = $journal->id;
            $this->saveQuietly();
        }

        foreach ($journalLines as $line) {
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $line['chart_account_id'],
                'amount' => $line['amount'],
                'nature' => $line['nature'],
                'description' => $line['description'],
            ]);
        }

        $journal->load('items');
        $journal->createGlTransactions();

        $advanceAllocation->recordDeductions(
            $advanceSlices,
            (int) $this->supplier_id,
            (int) $this->company_id,
            $this->branch_id,
            $this->purchase_date,
            (int) $this->id,
            $userId,
            $this->supplierAdvanceDeductionDescription()
        );

        $glCount = GlTransaction::where('transaction_type', 'journal')
            ->where('transaction_id', $journal->id)
            ->count();

        $totalAmountLCY = $convertToLCY($this->total_amount ?? 0);
        $currencyInfo = $needsConversion
            ? " (FCY: {$purchaseCurrency} ".number_format($this->total_amount ?? 0, 2).", LCY: {$functionalCurrency} ".number_format($totalAmountLCY, 2).')'
            : '';

        $this->logActivity('post', "Posted Cash Purchase journal for Supplier: {$supplierName}{$currencyInfo}", [
            'Supplier' => $supplierName,
            'Journal ID' => $journal->id,
            'Purchase Date' => $this->purchase_date ? $this->purchase_date->format('Y-m-d') : 'N/A',
            'Settlement' => 'Supplier advance only',
            'Supplier advance applied' => number_format($applyFcy, 2),
            'GL lines (journal)' => $glCount,
            'Posted By' => auth()->user()->name ?? 'System',
            'Posted At' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function removeJournalAndGl(): void
    {
        if ($this->journal_id) {
            $journalId = $this->journal_id;
            GlTransaction::where('transaction_type', 'journal')->where('transaction_id', $journalId)->delete();
            JournalItem::where('journal_id', $journalId)->delete();
            Journal::where('id', $journalId)->delete();
            $this->journal_id = null;
            $this->saveQuietly();
        }
        $this->glTransactions()->delete();
    }

    /**
     * Text stored on supplier advance deduction rows (supplier advance statement).
     */
    public function supplierAdvanceDeductionDescription(): string
    {
        $custom = trim((string) ($this->notes ?? ''));

        return $custom !== ''
            ? $custom
            : 'Cash purchase #'.$this->id;
    }

}
