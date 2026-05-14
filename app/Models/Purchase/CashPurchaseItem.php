<?php

namespace App\Models\Purchase;

use App\Traits\LogsActivity;
use App\Models\Inventory\Item as InventoryItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashPurchaseItem extends Model
{
    use LogsActivity;
    
    protected $table = 'cash_purchase_items';

    protected $fillable = [
        'cash_purchase_id',
        'inventory_item_id',
        'description',
        'unit_of_measure',
        'quantity',
        'unit_cost',
        'vat_type', // no_vat, inclusive, exclusive
        'vat_rate',
        'vat_amount',
        'net_amount',
        'line_total',
        'expiry_date',
        'batch_number',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(CashPurchase::class, 'cash_purchase_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /**
     * Check if this item is inventory
     */
    public function isInventory(): bool
    {
        return $this->inventory_item_id !== null;
    }

    public function calculateLine(): void
    {
        $base = (float) $this->quantity * (float) $this->unit_cost;
        $vat = 0;
        if ($this->vat_type === 'exclusive' && (float) $this->vat_rate > 0) {
            $vat = $base * ((float) $this->vat_rate / 100);
        } elseif ($this->vat_type === 'inclusive' && (float) $this->vat_rate > 0) {
            $vat = $base * ((float) $this->vat_rate / (100 + (float) $this->vat_rate));
        }
        $this->vat_amount = $vat;
        $this->net_amount = ($this->vat_type === 'inclusive') ? ($base - $vat) : $base;
        $this->line_total = ($this->vat_type === 'exclusive') ? ($base + $vat) : $base;
    }
}
