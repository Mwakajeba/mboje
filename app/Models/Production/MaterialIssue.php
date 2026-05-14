<?php

namespace App\Models\Production;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialIssue extends Model
{
    use LogsActivity;
    protected $fillable = [
        'work_order_id',
        'issue_voucher_number',
        'material_item_id',
        'lot_number',
        'quantity_issued',
        'unit_of_measure',
        'issued_by',
        'received_by',
        'bin_location',
        'line_location',
        'issued_at',
        'notes',
    ];

    protected $casts = [
        'quantity_issued' => 'decimal:3',
        'issued_at' => 'datetime',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function materialItem(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Inventory\Item::class, 'material_item_id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'issued_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'received_by');
    }

    public function getFormattedQuantityAttribute()
    {
        return number_format($this->quantity_issued, 3) . ' ' . $this->unit_of_measure;
    }
}