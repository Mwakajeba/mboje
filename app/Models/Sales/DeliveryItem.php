<?php

namespace App\Models\Sales;

use App\Models\Sales\Delivery;
use App\Models\Sales\SalesOrderItem;
use App\Models\Inventory\Item as InventoryItem;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DeliveryItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'delivery_id',
        'inventory_item_id',
        'sales_order_item_id',
        'item_name',
        'item_code',
        'quantity',
        'picked_quantity',
        'packed_quantity',
        'delivered_quantity',
        'unit_of_measure',
        'unit_price',
        'vat_type',
        'vat_rate',
        'vat_amount',
        'line_total',
        'unit_weight',
        'total_weight',
        'location',
        'batch_number',
        'expiry_date',
        'picked',
        'picked_at',
        'picked_by',
        'packed',
        'packed_at',
        'packed_by',
        'delivered',
        'delivered_at',
        'delivered_by',
        'picking_notes',
        'packing_notes',
        'delivery_notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'picked_quantity' => 'decimal:2',
        'packed_quantity' => 'decimal:2',
        'delivered_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'unit_weight' => 'decimal:2',
        'total_weight' => 'decimal:2',
        'expiry_date' => 'date',
        'picked' => 'boolean',
        'picked_at' => 'datetime',
        'packed' => 'boolean',
        'packed_at' => 'datetime',
        'delivered' => 'boolean',
        'delivered_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (empty($item->delivered_quantity)) {
                $item->delivered_quantity = $item->quantity;
            }
            if (empty($item->picked_quantity)) {
                $item->picked_quantity = $item->quantity;
            }
            if (empty($item->packed_quantity)) {
                $item->packed_quantity = $item->quantity;
            }
        });

        static::saving(function ($item) {
            // Calculate total weight
            $item->total_weight = $item->quantity * $item->unit_weight;
        });
    }

    // Relationships
    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function salesOrderItem()
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function pickedBy()
    {
        return $this->belongsTo(User::class, 'picked_by');
    }

    public function packedBy()
    {
        return $this->belongsTo(User::class, 'packed_by');
    }

    public function deliveredBy()
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    // Accessors
    public function getPickingStatusTextAttribute()
    {
        if ($this->picked) {
            return 'Picked';
        } elseif ($this->delivery->status === 'picking') {
            return 'Pending';
        } else {
            return 'Not Started';
        }
    }

    public function getPackingStatusTextAttribute()
    {
        if ($this->packed) {
            return 'Packed';
        } elseif ($this->delivery->status === 'packed') {
            return 'Pending';
        } else {
            return 'Not Started';
        }
    }

    public function getDeliveryStatusTextAttribute()
    {
        if ($this->delivered) {
            return 'Delivered';
        } elseif (in_array($this->delivery->status, ['in_transit', 'packed'])) {
            return 'Pending';
        } else {
            return 'Not Started';
        }
    }

    public function getPickingStatusBadgeAttribute()
    {
        if ($this->picked) {
            return '<span class="badge bg-success">Picked</span>';
        } elseif ($this->delivery->status === 'picking') {
            return '<span class="badge bg-warning">Pending</span>';
        } else {
            return '<span class="badge bg-secondary">Not Started</span>';
        }
    }

    public function getPackingStatusBadgeAttribute()
    {
        if ($this->packed) {
            return '<span class="badge bg-success">Packed</span>';
        } elseif ($this->delivery->status === 'packed') {
            return '<span class="badge bg-warning">Pending</span>';
        } else {
            return '<span class="badge bg-secondary">Not Started</span>';
        }
    }

    public function getDeliveryStatusBadgeAttribute()
    {
        if ($this->delivered) {
            return '<span class="badge bg-success">Delivered</span>';
        } elseif (in_array($this->delivery->status, ['in_transit', 'packed'])) {
            return '<span class="badge bg-warning">Pending</span>';
        } else {
            return '<span class="badge bg-secondary">Not Started</span>';
        }
    }

    public function getFormattedWeightAttribute()
    {
        return number_format($this->total_weight, 2) . ' ' . ($this->delivery->weight_unit ?? 'kg');
    }

    public function getFormattedExpiryDateAttribute()
    {
        return $this->expiry_date ? $this->expiry_date->format('M d, Y') : 'N/A';
    }

    // Business Logic Methods
    public function canPick()
    {
        return !$this->picked && $this->delivery->status === 'picking';
    }

    public function canPack()
    {
        return $this->picked && !$this->packed && $this->delivery->status === 'packed';
    }

    public function canDeliver()
    {
        return $this->packed && !$this->delivered && in_array($this->delivery->status, ['in_transit', 'packed']);
    }

    public function markAsPicked($userId, $pickedQuantity = null, $notes = null)
    {
        if (!$this->canPick()) {
            return false;
        }

        $this->update([
            'picked' => true,
            'picked_at' => now(),
            'picked_by' => $userId,
            'picked_quantity' => $pickedQuantity ?? $this->quantity,
            'picking_notes' => $notes,
        ]);

        return true;
    }

    public function markAsPacked($userId, $packedQuantity = null, $notes = null)
    {
        if (!$this->canPack()) {
            return false;
        }

        $this->update([
            'packed' => true,
            'packed_at' => now(),
            'packed_by' => $userId,
            'packed_quantity' => $packedQuantity ?? $this->picked_quantity,
            'packing_notes' => $notes,
        ]);

        return true;
    }

    public function markAsDelivered($userId, $deliveredQuantity = null, $notes = null)
    {
        if (!$this->canDeliver()) {
            return false;
        }

        $this->update([
            'delivered' => true,
            'delivered_at' => now(),
            'delivered_by' => $userId,
            'delivered_quantity' => $deliveredQuantity ?? $this->packed_quantity,
            'delivery_notes' => $notes,
        ]);

        return true;
    }

    public function checkStockAvailability()
    {
        if (!$this->inventoryItem) {
            return false;
        }

        $availableStock = $this->inventoryItem->current_stock - $this->inventoryItem->reserved_stock;
        return $availableStock >= $this->quantity;
    }

    public function getStockStatusText()
    {
        if ($this->checkStockAvailability()) {
            return 'Available';
        } else {
            return 'Insufficient Stock';
        }
    }

    public function getStockStatusBadge()
    {
        if ($this->checkStockAvailability()) {
            return '<span class="badge bg-success">Available</span>';
        } else {
            return '<span class="badge bg-danger">Insufficient</span>';
        }
    }
}
