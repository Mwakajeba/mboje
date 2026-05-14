<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory\Item;

class InventoryCostLayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'reference',
        'transaction_type',
        'quantity',
        'remaining_quantity',
        'unit_cost',
        'total_cost',
        'transaction_date',
        'is_consumed',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'remaining_quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:2',
        'transaction_date' => 'date',
        'is_consumed' => 'boolean',
    ];

    // Relationships
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
