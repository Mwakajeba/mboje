<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionBatch extends Model
{
    protected $fillable = [
        'batch_number',
        'item_id',
        'quantity_planned',
        'quantity_produced',
        'quantity_defective',
        'start_date',
        'end_date',
        'status',
    ];

    public function item()
    {
        return $this->belongsTo(\App\Models\Inventory\Item::class, 'item_id');
    }

    public function orders()
    {
    return $this->belongsToMany(\App\Models\Sales\SalesOrder::class, 'batch_orders', 'batch_id', 'order_id')
            ->withPivot('assigned_quantity')
            ->withTimestamps();
    }
    public function itemBatches()
    {
        return $this->hasMany(\App\Models\Production\ItemBatch::class, 'production_batch_id');
    }
}
