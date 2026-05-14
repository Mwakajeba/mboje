<?php

namespace App\Models\Production;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderBom extends Model
{
    use LogsActivity;
    protected $table = 'work_order_bom';

    protected $fillable = [
        'work_order_id',
        'material_item_id',
        'material_type',
        'required_quantity',
        'unit_of_measure',
        'variance_allowed',
    ];

    protected $casts = [
        'required_quantity' => 'decimal:3',
        'variance_allowed' => 'decimal:2',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function materialItem(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Inventory\Item::class, 'material_item_id');
    }

    public function getVarianceMinAttribute()
    {
        return $this->required_quantity * (1 - ($this->variance_allowed / 100));
    }

    public function getVarianceMaxAttribute()
    {
        return $this->required_quantity * (1 + ($this->variance_allowed / 100));
    }

    public function isWithinVariance($actualQuantity)
    {
        return $actualQuantity >= $this->variance_min && $actualQuantity <= $this->variance_max;
    }
}