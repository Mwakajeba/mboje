<?php

namespace App\Models\Sales;

use App\Models\Inventory\Item as InventoryItem;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceItem extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'sales_invoice_id',
        'inventory_item_id',
        'item_name',
        'item_code',
        'description',
        'unit_of_measure',
        'quantity',
        'unit_price',
        'price_tier',
        'line_total',
        'vat_type',
        'vat_rate',
        'vat_amount',
        'discount_type',
        'discount_rate',
        'discount_amount',
        'available_stock',
        'reserved_stock',
        'stock_available',
        'batch_number',
        'expiry_date',
        'expiry_consumption_details',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'available_stock' => 'decimal:2',
        'reserved_stock' => 'decimal:2',
        'stock_available' => 'boolean',
        'expiry_date' => 'date',
        'expiry_consumption_details' => 'array',
    ];

    protected $dates = ['deleted_at'];

    /**
     * Relationships
     */
    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Accessors
     */
    public function getVatTypeTextAttribute()
    {
        return ucfirst($this->vat_type);
    }

    public function getDiscountTypeTextAttribute()
    {
        return ucfirst($this->discount_type ?? 'none');
    }

    public function getStockStatusAttribute()
    {
        if ($this->stock_available) {
            return '<span class="badge bg-success">In Stock</span>';
        }
        return '<span class="badge bg-danger">Out of Stock</span>';
    }

    /**
     * Calculate line total
     */
    public function calculateLineTotal()
    {
        $subtotal = $this->quantity * $this->unit_price;
        
        // Apply discount
        if ($this->discount_type === 'percentage' && $this->discount_rate > 0) {
            $this->discount_amount = $subtotal * ($this->discount_rate / 100);
        } elseif ($this->discount_type === 'fixed') {
            $this->discount_amount = $this->discount_rate;
        } else {
            $this->discount_amount = 0;
        }

        $discountedSubtotal = $subtotal - $this->discount_amount;

        // Calculate VAT
        if ($this->vat_type === 'no_vat') {
            $this->vat_amount = 0;
            $this->line_total = $discountedSubtotal;
        } elseif ($this->vat_type === 'exclusive') {
            $this->vat_amount = $discountedSubtotal * ($this->vat_rate / 100);
            $this->line_total = $discountedSubtotal + $this->vat_amount;
        } else {
            // VAT inclusive
            $this->vat_amount = $discountedSubtotal * ($this->vat_rate / (100 + $this->vat_rate));
            $this->line_total = $discountedSubtotal;
        }

        return $this->line_total;
    }

    /**
     * Check stock availability
     * Skip stock check for service items or items that don't track stock
     */
    public function checkStockAvailability()
    {
        if ($this->inventoryItem) {
            // Skip stock validation for service items or items that don't track stock
            if ($this->inventoryItem->item_type === 'service' || !$this->inventoryItem->track_stock) {
                $this->available_stock = null;
                $this->reserved_stock = null;
                $this->stock_available = true; // Service items are always available
                return;
            }
            
            $this->available_stock = $this->inventoryItem->available_stock;
            $this->reserved_stock = $this->inventoryItem->reserved_stock;
            $this->stock_available = $this->available_stock >= $this->quantity;
        }
    }

    /**
     * Update stock levels
     */
    public function updateStockLevels()
    {
        if ($this->inventoryItem) {
            $this->inventoryItem->decrement('available_stock', $this->quantity);
            $this->inventoryItem->increment('reserved_stock', $this->quantity);
        }
    }

    /**
     * Get expiry status badge
     */
    public function getExpiryStatusBadgeAttribute()
    {
        if (!$this->expiry_date) {
            return '<span class="badge bg-secondary">No Expiry</span>';
        }

        $daysUntilExpiry = now()->diffInDays($this->expiry_date, false);
        
        if ($daysUntilExpiry < 0) {
            return '<span class="badge bg-danger">Expired</span>';
        } elseif ($daysUntilExpiry <= 30) {
            return '<span class="badge bg-warning">Expiring Soon</span>';
        } else {
            return '<span class="badge bg-success">Good</span>';
        }
    }

    /**
     * Get formatted expiry date
     */
    public function getFormattedExpiryDateAttribute()
    {
        return $this->expiry_date ? $this->expiry_date->format('Y-m-d') : 'N/A';
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiryAttribute()
    {
        if (!$this->expiry_date) {
            return null;
        }

        return now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Check if item has expiry tracking
     */
    public function hasExpiryTracking()
    {
        return $this->inventoryItem && $this->inventoryItem->track_expiry;
    }

    /**
     * Get expiry consumption details summary
     */
    public function getExpiryConsumptionSummaryAttribute()
    {
        if (!$this->expiry_consumption_details) {
            return null;
        }

        $details = $this->expiry_consumption_details;
        $summary = [];
        
        foreach ($details as $layer) {
            $summary[] = "Batch: {$layer['batch_number']}, Expiry: {$layer['expiry_date']}, Qty: {$layer['quantity']}";
        }
        
        return implode('; ', $summary);
    }
}
