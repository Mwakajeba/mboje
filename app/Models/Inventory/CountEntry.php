<?php

namespace App\Models\Inventory;

use App\Traits\LogsActivity;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountEntry extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'inventory_count_entries';

    protected $fillable = [
        'count_session_id',
        'item_id',
        'inventory_location_id',
        'bin_location',
        'system_quantity',
        'physical_quantity',
        'recount_quantity',
        'condition',
        'lot_number',
        'batch_number',
        'expiry_date',
        'remarks',
        'counted_by',
        'counted_at',
        'recounted_by',
        'recounted_at',
        'verified_by',
        'verified_at',
        'status',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:2',
        'physical_quantity' => 'decimal:2',
        'recount_quantity' => 'decimal:2',
        'expiry_date' => 'date',
        'counted_at' => 'datetime',
        'recounted_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(CountSession::class, 'count_session_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function location()
    {
        return $this->belongsTo(\App\Models\InventoryLocation::class, 'inventory_location_id');
    }

    public function countedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'counted_by');
    }

    public function recountedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'recounted_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'verified_by');
    }

    public function variance()
    {
        return $this->hasOne(CountVariance::class, 'count_entry_id');
    }

    public function getEncodedIdAttribute()
    {
        return Hashids::encode($this->id);
    }
}
