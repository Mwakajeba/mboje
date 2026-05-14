<?php

namespace App\Models\RentalEventEquipment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DecorationEquipmentReturnItem extends Model
{
    protected $table = 'decoration_equipment_return_items';

    protected $fillable = [
        'return_id',
        'issue_item_id',
        'equipment_id',
        'quantity_returned',
        'condition',
        'condition_notes',
    ];

    public function return(): BelongsTo
    {
        return $this->belongsTo(DecorationEquipmentReturn::class, 'return_id');
    }

    public function issueItem(): BelongsTo
    {
        return $this->belongsTo(DecorationEquipmentIssueItem::class, 'issue_item_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\RentalEventEquipment\Equipment::class, 'equipment_id');
    }
}

