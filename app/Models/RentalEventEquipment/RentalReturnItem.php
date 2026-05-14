<?php

namespace App\Models\RentalEventEquipment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalReturnItem extends Model
{
    protected $table = 'rental_return_items';

    protected $fillable = [
        'return_id',
        'dispatch_item_id',
        'equipment_id',
        'quantity_returned',
        'condition',
        'condition_notes',
    ];

    protected $casts = [
        'quantity_returned' => 'integer',
    ];

    public function return(): BelongsTo
    {
        return $this->belongsTo(RentalReturn::class, 'return_id');
    }

    public function dispatchItem(): BelongsTo
    {
        return $this->belongsTo(RentalDispatchItem::class, 'dispatch_item_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }
}
