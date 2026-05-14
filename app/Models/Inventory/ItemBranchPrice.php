<?php

namespace App\Models\Inventory;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemBranchPrice extends Model
{
    protected $table = 'inventory_item_prices';

    protected $fillable = [
        'item_id',
        'branch_id',
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
