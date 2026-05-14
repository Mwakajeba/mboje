<?php

namespace App\Models\Sales;

use App\Models\Inventory\Item as InventoryItem;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSaleItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'pos_sale_id',
        'inventory_item_id',
        'item_name',
        'quantity',
        'unit_price',
        'price_tier',
        'vat_type',
        'vat_rate',
        'vat_amount',
        'discount_type',
        'discount_rate',
        'discount_amount',
        'line_total',
        'expiry_date',
        'batch_number',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    /**
     * Relationships
     */
    public function posSale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Calculate line total
     */
    public function calculateLineTotal()
    {
        $baseAmount = $this->quantity * $this->unit_price;
        
        // Calculate VAT
        if ($this->vat_type === 'inclusive') {
            // VAT is already included in unit price
            $this->vat_amount = $baseAmount * ($this->vat_rate / (100 + $this->vat_rate));
        } elseif ($this->vat_type === 'exclusive') {
            // VAT is added on top
            $this->vat_amount = $baseAmount * ($this->vat_rate / 100);
        } else {
            // No VAT
            $this->vat_amount = 0;
        }
        
        // Calculate discount
        if ($this->discount_type === 'percentage') {
            $this->discount_amount = $baseAmount * ($this->discount_rate / 100);
        } elseif ($this->discount_type === 'fixed') {
            $this->discount_amount = $this->discount_rate;
        } else {
            $this->discount_amount = 0;
        }

        // Calculate line total
        if ($this->vat_type === 'inclusive') {
            // VAT is included, so line total is base amount minus discount
            $this->line_total = $baseAmount - $this->discount_amount;
        } else {
            // VAT is exclusive or no VAT, so line total is base amount plus VAT minus discount
            $this->line_total = $baseAmount + $this->vat_amount - $this->discount_amount;
        }
    }
} 