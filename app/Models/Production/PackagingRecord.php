<?php

namespace App\Models\Production;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackagingRecord extends Model
{
    use LogsActivity;
    protected $fillable = [
        'work_order_id',
        'packed_quantities',
        'carton_numbers',
        'barcode_data',
        'packed_by',
        'packed_at',
        'notes',
    ];

    protected $casts = [
        'packed_quantities' => 'array',
        'carton_numbers' => 'array',
        'barcode_data' => 'array',
        'packed_at' => 'datetime',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function packedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'packed_by');
    }

    public function getTotalPackedAttribute()
    {
        return collect($this->packed_quantities)->sum();
    }

    public function getCartonNumbersStringAttribute()
    {
        return $this->carton_numbers ? implode(', ', $this->carton_numbers) : 'N/A';
    }

    public function getPackedQuantitiesStringAttribute()
    {
        if (!$this->packed_quantities) {
            return 'N/A';
        }

        $quantities = [];
        foreach ($this->packed_quantities as $size => $quantity) {
            $quantities[] = "$size: $quantity";
        }

        return implode(', ', $quantities);
    }
}