<?php

namespace App\Models\Purchase;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverTripMapatoLine extends Model
{
    protected $fillable = [
        'driver_trip_mapato_record_id',
        'maelezo',
        'kiasi',
        'sort_order',
    ];

    protected $casts = [
        'kiasi' => 'decimal:2',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(DriverTripMapatoRecord::class, 'driver_trip_mapato_record_id');
    }
}
