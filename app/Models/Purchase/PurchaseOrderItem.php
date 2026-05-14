<?php

namespace App\Models\Purchase;

use App\Models\Inventory\Item;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderItem extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'purchase_order_id',
        'item_id',
        'quantity',
        'cost_price',
        'tax_calculation_type',
        'vat_type',
        'vat_rate',
        'vat_amount',
        'tax_amount',
        'subtotal',
        'total_amount',
        'description',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Accessors
     */
    public function getFormattedQuantityAttribute()
    {
        return number_format($this->quantity, 2);
    }

    public function getFormattedCostPriceAttribute()
    {
        return number_format($this->cost_price, 2);
    }

    public function getFormattedSubtotalAttribute()
    {
        return number_format($this->subtotal, 2);
    }

    public function getFormattedVatAmountAttribute()
    {
        return number_format($this->vat_amount, 2);
    }

    public function getFormattedTotalAmountAttribute()
    {
        return number_format($this->total_amount, 2);
    }

    public function getVatTypeLabelAttribute()
    {
        $labels = [
            'no_vat' => 'No VAT',
            'inclusive' => 'VAT Inclusive',
            'exclusive' => 'VAT Exclusive',
        ];

        return $labels[$this->vat_type] ?? 'Unknown';
    }

    /**
     * Calculate item totals
     */
    public function calculateTotals()
    {
        $gross = $this->quantity * $this->cost_price;

        if ($this->vat_type === 'no_vat') {
            $this->vat_amount = 0;
            $this->subtotal = $gross; // net equals gross
            $this->total_amount = $gross;
        } elseif ($this->vat_type === 'inclusive') {
            // Extract VAT from gross to get net subtotal
            $this->vat_amount = $this->vat_rate > 0 ? ($gross * ($this->vat_rate / (100 + $this->vat_rate))) : 0;
            $this->subtotal = $gross - $this->vat_amount; // net (excl. VAT)
            $this->total_amount = $gross; // gross includes VAT
        } else { // exclusive
            $this->vat_amount = $this->vat_rate > 0 ? ($gross * ($this->vat_rate / 100)) : 0;
            $this->subtotal = $gross; // net (excl. VAT)
            $this->total_amount = $gross + $this->vat_amount; // add VAT on top
        }

        $this->tax_amount = $this->vat_amount;
    }
}
