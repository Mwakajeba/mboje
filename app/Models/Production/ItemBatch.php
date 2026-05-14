<?php

namespace App\Models\Production;

use App\Models\Inventory\Item;
use App\Models\Production\ProductionBatch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemBatch extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'item_batch';

    protected $fillable = [
        'item_id',
        'production_batch_id',
        'quantity',
        'cost',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function productionBatch()
    {
        return $this->belongsTo(ProductionBatch::class);
    }
}
