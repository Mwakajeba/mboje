<?php

namespace App\Models\Sales;

use App\Models\Customer;
use App\Models\Inventory\Item as InventoryItem;
use App\Models\User;
use App\Models\Branch;
use App\Models\Company;
use App\Traits\LogsActivity;
use App\Helpers\AmountInWords;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'order_number',
        'proforma_id',
        'customer_id',
        'order_date',
        'expected_delivery_date',
        'status',
        'payment_terms',
        'payment_days',
        'subtotal',
        'vat_type',
        'vat_rate',
        'vat_amount',
        'tax_amount',
        'discount_type',
        'discount_rate',
        'discount_amount',
        'total_amount',
        'customer_credit_limit',
        'customer_current_balance',
        'available_credit',
        'credit_check_passed',
        'inventory_check_passed',
        'credit_check_notes',
        'inventory_check_notes',
        'notes',
        'terms_conditions',
        'attachment',
        'approved_by',
        'approved_at',
        'branch_id',
        'company_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'customer_credit_limit' => 'decimal:2',
        'customer_current_balance' => 'decimal:2',
        'available_credit' => 'decimal:2',
        'credit_check_passed' => 'boolean',
        'inventory_check_passed' => 'boolean',
        'payment_days' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($salesOrder) {
            if (empty($salesOrder->order_number)) {
                $salesOrder->order_number = self::generateOrderNumber();
            }
        });
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'SO';
        $year = date('Y');
        $month = date('m');
        
        // Include soft-deleted records so we never reuse existing unique values.
        $lastOrder = self::withTrashed()
            ->where('order_number', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('order_number', 'desc')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        do {
            $orderNumber = $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
            $newNumber++;
        } while (self::withTrashed()->where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Relationships
     */
    public function proforma(): BelongsTo
    {
        return $this->belongsTo(SalesProforma::class, 'proforma_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    public function invoice(): HasOne
    {
        return $this->hasOne(\App\Models\Sales\SalesInvoice::class, 'sales_order_id');
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(\App\Models\Sales\Delivery::class, 'sales_order_id');
    }

    /**
     * Check if order has been converted to invoice
     */
    public function hasInvoice(): bool
    {
        return $this->invoice()->exists();
    }

    /**
     * Check if order has been converted to delivery
     */
    public function hasDelivery(): bool
    {
        return $this->delivery()->exists();
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

    public function scopeByStatus(Builder $query, $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeInProduction(Builder $query): Builder
    {
        return $query->where('status', 'in_production');
    }

    public function scopeReadyForDelivery(Builder $query): Builder
    {
        return $query->where('status', 'ready_for_delivery');
    }

    /**
     * Accessors
     */
    public function getStatusBadgeAttribute(): string
    {
        $colors = [
            'draft' => 'secondary',
            'pending_approval' => 'warning',
            'approved' => 'success',
            'in_production' => 'info',
            'ready_for_delivery' => 'primary',
            'delivered' => 'success',
            'cancelled' => 'danger',
            'on_hold' => 'warning',
            'converted_to_invoice' => 'info'
        ];

        $color = $colors[$this->status] ?? 'secondary';
        return '<span class="badge bg-' . $color . '">' . strtoupper(str_replace('_', ' ', $this->status)) . '</span>';
    }

    public function getPaymentTermsTextAttribute(): string
    {
        $terms = [
            'immediate' => 'Immediate',
            'net_15' => 'Net 15',
            'net_30' => 'Net 30',
            'net_45' => 'Net 45',
            'net_60' => 'Net 60',
            'custom' => 'Custom'
        ];

        return $terms[$this->payment_terms] ?? $this->payment_terms;
    }

    /**
     * Methods for credit and inventory checks
     */
    public function performCreditCheck(): bool
    {
        // Get customer's current balance and credit limit
        $this->customer_credit_limit = $this->customer->credit_limit ?? 0;
        $this->customer_current_balance = $this->customer->current_balance ?? 0;
        $this->available_credit = $this->customer_credit_limit - $this->customer_current_balance;

        // Check if order amount is within available credit
        $this->credit_check_passed = $this->total_amount <= $this->available_credit;

        if (!$this->credit_check_passed) {
            $this->credit_check_notes = "Order amount ({$this->total_amount}) exceeds available credit ({$this->available_credit})";
        } else {
            $this->credit_check_notes = "Credit check passed. Available credit: {$this->available_credit}";
        }

        return $this->credit_check_passed;
    }

    public function performInventoryCheck(): bool
    {
        $allItemsAvailable = true;
        $checkNotes = [];

        foreach ($this->items as $item) {
            $inventoryItem = $item->inventoryItem;
            $availableStock = $inventoryItem->current_stock ?? 0;
            $reservedStock = $inventoryItem->reserved_stock ?? 0;
            $actualAvailable = $availableStock - $reservedStock;

            $item->available_stock = $actualAvailable;
            $item->reserved_stock = $reservedStock;
            $item->stock_available = $actualAvailable >= $item->quantity;
            $item->save();

            if (!$item->stock_available) {
                $allItemsAvailable = false;
                $checkNotes[] = "Item {$item->item_name}: Required {$item->quantity}, Available {$actualAvailable}";
            }
        }

        $this->inventory_check_passed = $allItemsAvailable;
        $this->inventory_check_notes = $allItemsAvailable 
            ? "All items are available in sufficient quantities" 
            : "Insufficient stock for: " . implode(', ', $checkNotes);

        return $this->inventory_check_passed;
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'pending_approval' && 
               $this->credit_check_passed && 
               $this->inventory_check_passed;
    }

    public function approve($approvedBy): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        return true;
    }

    public function reserveInventory(): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }

        foreach ($this->items as $item) {
            $inventoryItem = $item->inventoryItem;
            $inventoryItem->increment('reserved_stock', $item->quantity);
        }

        return true;
    }

    public function releaseInventory(): bool
    {
        foreach ($this->items as $item) {
            $inventoryItem = $item->inventoryItem;
            $inventoryItem->decrement('reserved_stock', $item->quantity);
        }

        return true;
    }

    /**
     * Convert total_amount to words using shared helper.
     */
    public function getAmountInWords()
    {
        return AmountInWords::convert($this->total_amount);
    }
}
