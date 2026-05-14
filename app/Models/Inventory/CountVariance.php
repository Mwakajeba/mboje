<?php

namespace App\Models\Inventory;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountVariance extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'inventory_count_variances';

    protected $fillable = [
        'count_entry_id',
        'item_id',
        'system_quantity',
        'physical_quantity',
        'variance_quantity',
        'variance_percentage',
        'unit_cost',
        'variance_value',
        'variance_type',
        'is_high_value',
        'requires_recount',
        'recount_tolerance_percentage',
        'recount_tolerance_value',
        'investigation_notes',
        'status',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:2',
        'physical_quantity' => 'decimal:2',
        'variance_quantity' => 'decimal:2',
        'variance_percentage' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'variance_value' => 'decimal:2',
        'is_high_value' => 'boolean',
        'requires_recount' => 'boolean',
        'recount_tolerance_percentage' => 'decimal:2',
        'recount_tolerance_value' => 'decimal:2',
    ];

    public function entry()
    {
        return $this->belongsTo(CountEntry::class, 'count_entry_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function adjustment()
    {
        return $this->hasOne(CountAdjustment::class, 'variance_id');
    }
}
