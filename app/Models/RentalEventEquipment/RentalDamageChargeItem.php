<?php

namespace App\Models\RentalEventEquipment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalDamageChargeItem extends Model
{
    protected $table = 'rental_damage_charge_items';

    protected $fillable = [
        'damage_charge_id',
        'return_item_id',
        'equipment_id',
        'charge_type',
        'quantity',
        'unit_charge',
        'total_charge',
        'description',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_charge' => 'decimal:2',
        'total_charge' => 'decimal:2',
    ];

    public function damageCharge(): BelongsTo
    {
        return $this->belongsTo(RentalDamageCharge::class, 'damage_charge_id');
    }

    public function returnItem(): BelongsTo
    {
        return $this->belongsTo(RentalReturnItem::class, 'return_item_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }
}
