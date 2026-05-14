<?php

namespace App\Models\RentalEventEquipment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DecorationEquipmentLossItem extends Model
{
    protected $table = 'decoration_equipment_loss_items';

    protected $fillable = [
        'loss_id',
        'equipment_id',
        'quantity_lost',
        'notes',
    ];

    protected $casts = [
        'quantity_lost' => 'integer',
    ];

    public function loss(): BelongsTo
    {
        return $this->belongsTo(DecorationEquipmentLoss::class, 'loss_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }
}

