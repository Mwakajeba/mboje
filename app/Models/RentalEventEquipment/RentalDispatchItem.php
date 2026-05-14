<?php

namespace App\Models\RentalEventEquipment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalDispatchItem extends Model
{
    protected $table = 'rental_dispatch_items';

    protected $fillable = [
        'dispatch_id',
        'equipment_id',
        'quantity',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(RentalDispatch::class, 'dispatch_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }
}
