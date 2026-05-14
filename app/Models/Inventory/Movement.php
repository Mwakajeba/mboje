<?php

namespace App\Models\Inventory;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

use Vinkla\Hashids\Facades\Hashids;

class Movement extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'inventory_movements';

    protected $fillable = [
        'branch_id',
        'location_id',
        'item_id',
        'user_id',
        'movement_type',
        'writeoff_type',
        'quantity',
        'unit_price',
        'unit_cost',
        'total_cost',
        'balance_before',
        'balance_after',
        'reason',
        'reference_number',
        'reference_type',
        'reference_id',
        'reference',
        'notes',
        'movement_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'movement_date' => 'date',
    ];

    // Relationships
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(\App\Models\InventoryLocation::class);
    }

    // Scopes
    public function scopeInventoryIn($query)
    {
        return $query->where('movement_type', 'opening_balance');
    }

    public function scopeTransferIn($query)
    {
        return $query->where('movement_type', 'transfer_in');
    }

    public function scopeTransferOut($query)
    {
        return $query->where('movement_type', 'transfer_out');
    }

    public function scopeSold($query)
    {
        return $query->where('movement_type', 'sold');
    }

    public function scopePurchased($query)
    {
        return $query->where('movement_type', 'purchased');
    }

    public function scopeAdjustmentIn($query)
    {
        return $query->where('movement_type', 'adjustment_in');
    }

    public function scopeAdjustmentOut($query)
    {
        return $query->where('movement_type', 'adjustment_out');
    }

    public function scopeAdjustment($query)
    {
        return $query->whereIn('movement_type', ['adjustment_in', 'adjustment_out']);
    }

    public function scopeInbound($query)
    {
        return $query->whereIn('movement_type', ['opening_balance', 'transfer_in', 'purchased', 'adjustment_in']);
    }

    public function scopeOutbound($query)
    {
        return $query->whereIn('movement_type', ['transfer_out', 'sold', 'adjustment_out']);
    }

    // Accessors
    public function getMovementTypeTextAttribute()
    {
        return match($this->movement_type) {
            'opening_balance' => 'Opening Balance',
            'transfer_in' => 'Transfer In',
            'transfer_out' => 'Transfer Out',
            'sold' => 'Sold',
            'purchased' => 'Purchased',
            'adjustment_in' => 'Adjustment In',
            'adjustment_out' => 'Adjustment Out',
            default => ucfirst($this->movement_type),
        };
    }

    public function getMovementTypeBadgeClassAttribute()
    {
        return match($this->movement_type) {
            'opening_balance' => 'primary',
            'transfer_in' => 'success',
            'transfer_out' => 'info',
            'sold' => 'danger',
            'purchased' => 'success',
            'adjustment_in' => 'warning',
            'adjustment_out' => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'hash_id';
    }

    /**
     * Resolve the model instance for the given hash ID.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // If field is hash_id, decode the hash ID
        if ($field === 'hash_id' || $field === null) {
            $decoded = Hashids::decode($value);
            if (!empty($decoded)) {
                return $this->findOrFail($decoded[0]);
            }
        }
        
        // If not a hash ID, try as regular ID
        return $this->findOrFail($value);
    }

    /**
     * Get the hash ID for this model.
     */
    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    /**
     * Get the hash ID for routing.
     */
    public function getRouteKey()
    {
        return Hashids::encode($this->id);
    }
}
