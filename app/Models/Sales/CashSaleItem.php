<?php

namespace App\Models\Sales;

use App\Models\Inventory\Item as InventoryItem;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashSaleItem extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'cash_sale_id',
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
    ];

    protected $dates = ['deleted_at'];

    /**
     * Relationships
     */
    public function cashSale(): BelongsTo
    {
        return $this->belongsTo(CashSale::class);
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
            return 'In Stock';
        } else {
            return 'Out of Stock';
        }
    }

    /**
     * Calculate line total including VAT and discounts
     */
    public function calculateLineTotal()
    {
        $baseAmount = $this->quantity * $this->unit_price;
        
        // Calculate discount
        if ($this->discount_type === 'percentage') {
            $this->discount_amount = $baseAmount * ($this->discount_rate / 100);
        } elseif ($this->discount_type === 'fixed') {
            $this->discount_amount = $this->discount_rate;
        } else {
            $this->discount_amount = 0;
        }
        
        $amountAfterDiscount = $baseAmount - $this->discount_amount;
        
        // Calculate VAT
        if ($this->vat_type === 'inclusive') {
            // VAT is already included in unit price
            $this->vat_amount = $amountAfterDiscount * ($this->vat_rate / (100 + $this->vat_rate));
            $this->line_total = $amountAfterDiscount;
        } elseif ($this->vat_type === 'exclusive') {
            // VAT is added on top
            $this->vat_amount = $amountAfterDiscount * ($this->vat_rate / 100);
            $this->line_total = $amountAfterDiscount + $this->vat_amount;
        } else {
            // No VAT
            $this->vat_amount = 0;
            $this->line_total = $amountAfterDiscount;
        }
        
        return $this->line_total;
    }
} 