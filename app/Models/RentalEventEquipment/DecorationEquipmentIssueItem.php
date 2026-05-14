<?php

namespace App\Models\RentalEventEquipment;

use App\Models\Inventory\Item as Equipment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DecorationEquipmentIssueItem extends Model
{
    protected $table = 'decoration_equipment_issue_items';

    protected $fillable = [
        'issue_id',
        'equipment_id',
        'quantity_issued',
        'remarks',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(DecorationEquipmentIssue::class, 'issue_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\RentalEventEquipment\Equipment::class, 'equipment_id');
    }
}

