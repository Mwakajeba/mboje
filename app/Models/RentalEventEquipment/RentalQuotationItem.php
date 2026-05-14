<?php

namespace App\Models\RentalEventEquipment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalQuotationItem extends Model
{
    protected $table = 'rental_quotation_items';

    protected $fillable = [
        'quotation_id',
        'equipment_id',
        'quantity',
        'rental_rate',
        'rental_days',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'rental_rate' => 'decimal:2',
        'rental_days' => 'integer',
        'total_amount' => 'decimal:2',
    ];

    // Relationships
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(RentalQuotation::class, 'quotation_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
