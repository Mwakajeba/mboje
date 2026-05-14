<?php

namespace App\Models\Inventory;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory\Item;
use App\Models\InventoryLocation;

class OpeningBalance extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'inventory_opening_balances';

    protected $fillable = [
        'company_id',
        'branch_id',
        'inventory_location_id',
        'item_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'reference',
        'notes',
        'opened_at',
        'user_id',
    ];

    protected $casts = [
        'opened_at' => 'date',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function location()
    {
        return $this->belongsTo(InventoryLocation::class, 'inventory_location_id');
    }
}


