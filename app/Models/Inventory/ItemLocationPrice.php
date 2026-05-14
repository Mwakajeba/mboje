<?php

namespace App\Models\Inventory;

use App\Models\InventoryLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemLocationPrice extends Model
{
    protected $table = 'inventory_item_location_prices';

    protected $fillable = [
        'item_id',
        'location_id',
        'cost_price',
        'unit_price',
        'wholesale_unit_price',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'wholesale_unit_price' => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }
}
