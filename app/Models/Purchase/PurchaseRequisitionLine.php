<?php

namespace App\Models\Purchase;

use App\Models\ChartAccount;
use App\Models\Department;
use App\Models\Inventory\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequisitionLine extends Model
{
    protected $fillable = [
        'purchase_requisition_id',
        'item_type',
        'inventory_item_id',
        'description',
        'quantity',
        'uom',
        'unit_price_estimate',
        'line_total_estimate',
        'tax_group_id',
        'gl_account_id',
        'cost_center_id',
        'project_id',
        'budget_line_id',
        'ordered_quantity',
        'line_status',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price_estimate' => 'decimal:4',
        'line_total_estimate' => 'decimal:2',
        'ordered_quantity' => 'decimal:4',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'inventory_item_id');
    }

    public function taxGroup(): BelongsTo
    {
        // TaxGroup model may not exist - check before using
        if (class_exists(\App\Models\TaxGroup::class)) {
            return $this->belongsTo(\App\Models\TaxGroup::class, 'tax_group_id');
        }
        // If TaxGroup doesn't exist, return a dummy relationship to ChartAccount
        // This prevents errors but won't return actual tax group data
        return $this->belongsTo(\App\Models\ChartAccount::class, 'tax_group_id');
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'gl_account_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'cost_center_id');
    }
}


