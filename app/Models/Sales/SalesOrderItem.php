<?php

namespace App\Models\Sales;

use App\Models\Inventory\Item as InventoryItem;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrderItem extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'sales_order_id',
        'inventory_item_id',
        'item_name',
        'item_code',
        'quantity',
        'unit_price',
        'unit_of_measure',
        'available_stock',
        'reserved_stock',
        'stock_available',
        'vat_type',
        'vat_rate',
        'vat_amount',
        'discount_type',
        'discount_rate',
        'discount_amount',
        'line_total',
        'total',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'available_stock' => 'decimal:2',
        'reserved_stock' => 'decimal:2',
        'stock_available' => 'boolean',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->calculateLineTotal();
        });
    }

    /**
     * Relationships
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Calculate line total based on quantity, price, VAT, and discount
     */
    public function calculateLineTotal(): void
    {
        $subtotal = $this->quantity * $this->unit_price;

        // Calculate VAT
        if ($this->vat_type === 'vat_exclusive' && $this->vat_rate > 0) {
            $this->vat_amount = $subtotal * ($this->vat_rate / 100);
        } elseif ($this->vat_type === 'vat_inclusive' && $this->vat_rate > 0) {
            $this->vat_amount = $subtotal - ($subtotal / (1 + $this->vat_rate / 100));
        } else {
            $this->vat_amount = 0;
        }

        // Calculate discount
        if ($this->discount_type === 'percentage' && $this->discount_rate > 0) {
            $this->discount_amount = $subtotal * ($this->discount_rate / 100);
        } elseif ($this->discount_type === 'fixed') {
            $this->discount_amount = $this->discount_rate;
        } else {
            $this->discount_amount = 0;
        }

        // Calculate final line total
        $this->line_total = $subtotal + $this->vat_amount - $this->discount_amount;
        $this->total = $this->line_total;
    }

    /**
     * Check if item has sufficient stock
     */
    public function checkStockAvailability(): bool
    {
        $inventoryItem = $this->inventoryItem;
        $availableStock = $inventoryItem->current_stock ?? 0;
        $reservedStock = $inventoryItem->reserved_stock ?? 0;
        $actualAvailable = $availableStock - $reservedStock;

        $this->available_stock = $actualAvailable;
        $this->reserved_stock = $reservedStock;
        $this->stock_available = $actualAvailable >= $this->quantity;

        return $this->stock_available;
    }

    /**
     * Get stock status text
     */
    public function getStockStatusTextAttribute(): string
    {
        if ($this->stock_available) {
            return "Available ({$this->available_stock})";
        } else {
            return "Insufficient ({$this->available_stock} available, {$this->quantity} required)";
        }
    }

    /**
     * Get stock status badge
     */
    public function getStockStatusBadgeAttribute(): string
    {
        if ($this->stock_available) {
            return '<span class="badge bg-success">In Stock</span>';
        } else {
            return '<span class="badge bg-danger">Out of Stock</span>';
        }
    }
}
