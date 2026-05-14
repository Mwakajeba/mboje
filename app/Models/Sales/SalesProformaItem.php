<?php

namespace App\Models\Sales;

use App\Models\Inventory\Item as InventoryItem;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesProformaItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'sales_proforma_id',
        'inventory_item_id',
        'item_name',
        'item_code',
        'quantity',
        'unit_price',
        'vat_type',
        'vat_rate',
        'vat_amount',
        'discount_type',
        'discount_rate',
        'discount_amount',
        'subtotal',
        'line_total'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    protected $attributes = [
        'vat_type' => 'no_vat',
        'discount_type' => 'percentage'
    ];

    // Relationships
    public function salesProforma()
    {
        return $this->belongsTo(SalesProforma::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    // Auto-calculate line total when saving
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $subtotal = ($item->quantity ?? 0) * ($item->unit_price ?? 0);
            $item->subtotal = $subtotal;

            // Normalize types
            $item->vat_type = match ($item->vat_type) {
                'vat_inclusive', 'inclusive' => 'inclusive',
                'vat_exclusive', 'exclusive' => 'exclusive',
                default => 'no_vat',
            };

            // Apply discount
            if ($item->discount_type === 'percentage') {
                $item->discount_amount = $subtotal * (($item->discount_rate ?? 0) / 100);
            } else {
                // Treat any non-percentage as fixed amount
                $item->discount_type = 'fixed';
                $item->discount_amount = $item->discount_rate ?? 0;
            }

            $afterDiscount = $subtotal - $item->discount_amount;

            // Apply VAT
            if ($item->vat_type === 'inclusive' && ($item->vat_rate ?? 0) > 0) {
                $item->vat_amount = $afterDiscount - ($afterDiscount / (1 + (($item->vat_rate ?? 0) / 100)));
                $item->line_total = $afterDiscount;
            } elseif ($item->vat_type === 'exclusive' && ($item->vat_rate ?? 0) > 0) {
                $item->vat_amount = $afterDiscount * (($item->vat_rate ?? 0) / 100);
                $item->line_total = $afterDiscount + $item->vat_amount;
            } else {
                $item->vat_amount = 0;
                $item->line_total = $afterDiscount;
            }

            // Ensure legacy 'total' column is populated for DB schema compatibility
            $item->total = $item->line_total;
        });
    }
}
