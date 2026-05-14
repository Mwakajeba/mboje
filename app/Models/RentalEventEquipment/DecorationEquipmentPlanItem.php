<?php

namespace App\Models\RentalEventEquipment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DecorationEquipmentPlanItem extends Model
{
    protected $table = 'decoration_equipment_plan_items';

    protected $fillable = [
        'plan_id',
        'equipment_id',
        'quantity_planned',
        'notes',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(DecorationEquipmentPlan::class, 'plan_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }
}

