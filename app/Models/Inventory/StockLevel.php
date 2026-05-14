<?php

namespace App\Models\Inventory;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockLevel extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'inventory_stock_levels';

    protected $fillable = [
        'item_id',
        'inventory_location_id',
        'quantity',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function location()
    {
        return $this->belongsTo(\App\Models\InventoryLocation::class, 'inventory_location_id');
    }
}



