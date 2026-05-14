<?php

namespace App\Models\Production;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionBatch extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'production_batches'; // Adjust if your table name is different

    protected $fillable = [
        'batch_number',
        'item_id',
        'quantity_planned',
        'quantity_produced',
        'quantity_defective',
        'start_date',
        'end_date',
        'status',
        // Add other fields as needed
    ];

    // Example relationship to ItemBatch
    public function itemBatches()
    {
        return $this->hasMany(ItemBatch::class, 'production_batch_id');
    }

    // Example relationship to Item
    public function item()
    {
        return $this->belongsTo(\App\Models\Inventory\Item::class);
    }
}
