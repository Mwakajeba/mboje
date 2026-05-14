<?php

namespace App\Models\Inventory;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use App\Models\InventoryLocation;

class ExpiryTracking extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'inventory_expiry_tracking';

    protected $fillable = [
        'item_id',
        'location_id',
        'batch_number',
        'expiry_date',
        'quantity',
        'unit_cost',
        'total_cost',
        'reference_type',
        'reference_id',
        'reference_number',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    // Relationships
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    // Scopes
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now()->toDateString());
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereBetween('expiry_date', [
            now()->toDateString(),
            now()->addDays($days)->toDateString()
        ]);
    }

    public function scopeForItem($query, $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    public function scopeForLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeAvailable($query)
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeOrderByExpiry($query, $direction = 'asc')
    {
        return $query->orderBy('expiry_date', $direction);
    }

    public function scopeOrderByFifo($query, $direction = 'asc')
    {
        return $query->orderBy('created_at', $direction);
    }

    // Accessors
    public function getDaysUntilExpiryAttribute()
    {
        return now()->diffInDays($this->expiry_date, false);
    }

    public function getIsExpiredAttribute()
    {
        return $this->expiry_date < now()->toDateString();
    }

    public function getIsExpiringSoonAttribute()
    {
        $warningDays = \App\Models\SystemSetting::where('key', 'inventory_global_expiry_warning_days')->value('value') ?? 30;
        return $this->days_until_expiry <= $warningDays && $this->days_until_expiry >= 0;
    }

    public function getExpiryStatusAttribute()
    {
        if ($this->is_expired) {
            return 'expired';
        } elseif ($this->is_expiring_soon) {
            return 'expiring_soon';
        } else {
            return 'good';
        }
    }

    public function getExpiryStatusBadgeClassAttribute()
    {
        return match($this->expiry_status) {
            'expired' => 'danger',
            'expiring_soon' => 'warning',
            'good' => 'success',
            default => 'secondary',
        };
    }

    public function getExpiryStatusTextAttribute()
    {
        return match($this->expiry_status) {
            'expired' => 'Expired',
            'expiring_soon' => 'Expiring Soon',
            'good' => 'Good',
            default => 'Unknown',
        };
    }

    // Methods
    public function consume($quantity)
    {
        $consumed = min($quantity, $this->quantity);
        $this->quantity -= $consumed;
        $this->total_cost = $this->quantity * $this->unit_cost;
        $this->save();
        
        return $consumed;
    }

    public function add($quantity, $unitCost = null)
    {
        if ($unitCost !== null) {
            // Recalculate average cost
            $totalValue = ($this->quantity * $this->unit_cost) + ($quantity * $unitCost);
            $totalQuantity = $this->quantity + $quantity;
            $this->unit_cost = $totalQuantity > 0 ? $totalValue / $totalQuantity : $this->unit_cost;
        }
        
        $this->quantity += $quantity;
        $this->total_cost = $this->quantity * $this->unit_cost;
        $this->save();
        
        return $this;
    }

    public function getRemainingValue()
    {
        return $this->quantity * $this->unit_cost;
    }
}
