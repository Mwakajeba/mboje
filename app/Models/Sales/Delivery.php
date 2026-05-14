<?php

namespace App\Models\Sales;

use App\Helpers\AmountInWords;
use App\Models\Customer;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\DeliveryItem;
use App\Models\User;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Inventory\Item as InventoryItem;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Delivery extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'delivery_number',
        'sales_order_id',
        'customer_id',
        'delivery_date',
        'delivery_time',
        'status',
        'delivery_type',
        'delivery_address',
        'contact_person',
        'contact_phone',
        'vehicle_number',
        'driver_name',
        'driver_phone',
        'delivery_instructions',
        'notes',
        'total_quantity',
        'total_weight',
        'weight_unit',
        'has_transport_cost',
        'transport_cost',
        'stock_updated',
        'stock_updated_at',
        'picked_by',
        'picked_at',
        'packed_by',
        'packed_at',
        'delivered_by',
        'delivered_at',
        'received_by',
        'received_by_name',
        'received_at',
        'delivery_notes',
        'return_reason',
        'branch_id',
        'company_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'delivery_time' => 'datetime',
        'stock_updated' => 'boolean',
        'stock_updated_at' => 'datetime',
        'picked_at' => 'datetime',
        'packed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'received_at' => 'datetime',
        'total_quantity' => 'decimal:2',
        'total_weight' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($delivery) {
            if (empty($delivery->delivery_number)) {
                $delivery->delivery_number = self::generateDeliveryNumber();
            }
        });

        static::updating(function ($delivery) {
            // Update stock when delivery is completed
            if ($delivery->isDirty('status') && $delivery->status === 'delivered' && !$delivery->stock_updated) {
                $delivery->updateStock($delivery->received_by);
            }
        });
    }

    public static function generateDeliveryNumber()
    {
        $prefix = 'DEL';
        $year = date('Y');
        $month = date('m');
        
        $lastDelivery = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastDelivery ? intval(substr($lastDelivery->delivery_number, -4)) + 1 : 1;
        
        return $prefix . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(DeliveryItem::class);
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

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('delivery_date', [$startDate, $endDate]);
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'draft' => '<span class="badge bg-secondary">Draft</span>',
            'picking' => '<span class="badge bg-info">Picking</span>',
            'packed' => '<span class="badge bg-warning">Packed</span>',
            'in_transit' => '<span class="badge bg-primary">In Transit</span>',
            'delivered' => '<span class="badge bg-success">Delivered</span>',
            'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
            'returned' => '<span class="badge bg-dark">Returned</span>',
        ];

        return $badges[$this->status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    public function getDeliveryTypeTextAttribute()
    {
        $types = [
            'pickup' => 'Customer Pickup',
            'delivery' => 'Delivery',
            'shipping' => 'Shipping',
        ];

        return $types[$this->delivery_type] ?? 'Unknown';
    }

    public function getFormattedDeliveryDateTimeAttribute()
    {
        $date = $this->delivery_date->format('M d, Y');
        $time = $this->delivery_time ? Carbon::parse($this->delivery_time)->format('h:i A') : '';
        
        return $time ? "$date at $time" : $date;
    }

    public function getProgressPercentageAttribute()
    {
        $progress = [
            'draft' => 0,
            'picking' => 25,
            'packed' => 50,
            'in_transit' => 75,
            'delivered' => 100,
            'cancelled' => 0,
            'returned' => 0,
        ];

        return $progress[$this->status] ?? 0;
    }

    // Business Logic Methods
    public function canStartPicking()
    {
        return $this->status === 'draft' && $this->items()->count() > 0;
    }

    public function canCompletePicking()
    {
        // Check if status is picking
        if ($this->status !== 'picking') {
            return false;
        }
        
        // Check if all items are picked
        $unpickedItems = $this->items()->where('picked', false)->count();
        return $unpickedItems === 0;
    }

    public function canStartPacking()
    {
        return $this->status === 'picking' && $this->items()->where('picked', true)->count() > 0;
    }

    public function canCompletePacking()
    {
        return $this->status === 'packed' && $this->items()->where('packed', false)->count() === 0;
    }

    public function canStartDelivery()
    {
        return $this->status === 'packed' && $this->items()->where('packed', true)->count() > 0;
    }

    public function canCompleteDelivery()
    {
        return in_array($this->status, ['in_transit', 'packed']);
    }

    public function startPicking($userId)
    {
        if (!$this->canStartPicking()) {
            return false;
        }

        $this->update([
            'status' => 'picking',
            'picked_by' => $userId,
            'picked_at' => now(),
        ]);

        return true;
    }

    public function completePicking($userId)
    {
        if (!$this->canCompletePicking()) {
            return false;
        }

        $this->update([
            'status' => 'packed',
            'packed_by' => $userId,
            'packed_at' => now(),
        ]);

        return true;
    }

    public function startDelivery($userId)
    {
        if (!$this->canStartDelivery()) {
            return false;
        }

        $this->update([
            'status' => 'in_transit',
            'delivered_by' => $userId,
        ]);

        return true;
    }

    public function completeDelivery($userId, $receivedByName = null)
    {
        if (!$this->canCompleteDelivery()) {
            return false;
        }

        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
            'received_by' => $userId,
            'received_by_name' => $receivedByName,
            'received_at' => now(),
        ]);

        // Mark all items as delivered
        $this->items()->update(['delivered' => true]);

        return true;
    }

    public function updateStock($userId = null)
    {
        if ($this->stock_updated) {
            return false;
        }

        $stockService = new \App\Services\InventoryStockService();
        $loginLocationId = session('location_id') ?? 1; // Default to location 1 if no session location
        $userId = $userId ?? auth()->id() ?? 1; // Fallback to user 1 if no user ID provided

        foreach ($this->items as $item) {
            $inventoryItem = InventoryItem::find($item->inventory_item_id);
            if ($inventoryItem && $inventoryItem->track_stock) {
                // Get current stock using the stock service
                $balanceBefore = $loginLocationId 
                    ? $stockService->getItemStockAtLocation($inventoryItem->id, $loginLocationId)
                    : $stockService->getItemTotalStock($inventoryItem->id);
                
                $balanceAfter = $balanceBefore - $item->delivered_quantity;
                
                // Create inventory movement for stock out
                \App\Models\Inventory\Movement::create([
                    'item_id' => $inventoryItem->id,
                    'user_id' => $userId,
                    'branch_id' => $this->branch_id,
                    'location_id' => $loginLocationId,
                    'movement_type' => 'sold',
                    'quantity' => $item->delivered_quantity,
                    'unit_price' => $inventoryItem->getCostPriceForBranchOrLocation($this->branch_id, $loginLocationId),
                    'unit_cost' => $inventoryItem->getCostPriceForBranchOrLocation($this->branch_id, $loginLocationId),
                    'total_cost' => $item->delivered_quantity * $inventoryItem->getCostPriceForBranchOrLocation($this->branch_id, $loginLocationId),
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'reference' => 'Delivery: ' . $this->delivery_number,
                    'reference_type' => 'delivery',
                    'reference_id' => $this->id,
                    'notes' => 'Stock delivered to customer',
                    'movement_date' => $this->delivered_at ?? now(),
                ]);
            }
        }

        $this->update([
            'stock_updated' => true,
            'stock_updated_at' => now(),
        ]);

        return true;
    }

    public function generateDeliveryNote()
    {
        $note = "DELIVERY NOTE\n";
        $note .= "Delivery Number: {$this->delivery_number}\n";
        $note .= "Customer: {$this->customer->name}\n";
        $note .= "Delivery Date: {$this->formatted_delivery_date_time}\n";
        $note .= "Delivery Type: {$this->delivery_type_text}\n";
        
        if ($this->delivery_address) {
            $note .= "Delivery Address: {$this->delivery_address}\n";
        }
        
        if ($this->contact_person) {
            $note .= "Contact Person: {$this->contact_person}\n";
        }
        
        if ($this->contact_phone) {
            $note .= "Contact Phone: {$this->contact_phone}\n";
        }
        
        $note .= "\nITEMS:\n";
        $note .= str_repeat("-", 50) . "\n";
        
        foreach ($this->items as $item) {
            $note .= "Item: {$item->item_name} ({$item->item_code})\n";
            $note .= "Quantity: {$item->delivered_quantity} {$item->unit_of_measure}\n";
            $note .= "Weight: {$item->total_weight} {$this->weight_unit}\n";
            $note .= str_repeat("-", 30) . "\n";
        }
        
        $note .= "\nTOTALS:\n";
        $note .= "Total Quantity: {$this->total_quantity}\n";
        $note .= "Total Weight: {$this->total_weight} {$this->weight_unit}\n";
        
        if ($this->delivery_instructions) {
            $note .= "\nDELIVERY INSTRUCTIONS:\n{$this->delivery_instructions}\n";
        }
        
        if ($this->notes) {
            $note .= "\nNOTES:\n{$this->notes}\n";
        }
        
        return $note;
    }

    /**
     * Get the encoded ID for the delivery.
     */
    public function getEncodedIdAttribute(): string
    {
        return \Vinkla\Hashids\Facades\Hashids::encode($this->id);
    }

    /**
     * Get total amount (items total + transport cost)
     */
    public function getTotalAmountAttribute()
    {
        $itemsTotal = $this->items->sum('line_total');
        $transportCost = ($this->has_transport_cost && $this->transport_cost) ? $this->transport_cost : 0;
        return $itemsTotal + $transportCost;
    }

    /**
     * Convert total amount to words using shared helper.
     */
    public function getAmountInWords()
    {
        return AmountInWords::convert($this->total_amount);
    }
}
