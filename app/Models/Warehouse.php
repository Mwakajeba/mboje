<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name', 'code', 'description', 'location_address', 'location_type',
        'manager_name', 'manager_phone', 'manager_email', 'capacity',
        'capacity_unit', 'is_active', 'is_default', 'company_id', 'branch_id'
    ];

    protected $casts = [
        'capacity' => 'decimal:2',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    // Constants
    const LOCATION_TYPES = ['warehouse', 'store', 'distribution_center', 'showroom', 'office'];
    const CAPACITY_UNITS = ['sq_meters', 'cubic_meters', 'pallets', 'units'];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function goodsReceipts()
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function stockLevels()
    {
        return $this->hasMany(StockLevel::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('location_type', $type);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // Helper Methods
    public function getLocationTypeLabel()
    {
        return ucwords(str_replace('_', ' ', $this->location_type));
    }

    public function getCapacityUnitLabel()
    {
        return ucwords(str_replace('_', ' ', $this->capacity_unit));
    }

    public function getFormattedCapacity()
    {
        if (!$this->capacity) {
            return 'Unlimited';
        }
        return number_format($this->capacity, 2) . ' ' . $this->getCapacityUnitLabel();
    }

    public function isDefault()
    {
        return $this->is_default;
    }

    public function isActive()
    {
        return $this->is_active;
    }

    public function getCurrentStockValue()
    {
        return $this->stockLevels()
            ->join('inventory_items', 'stock_levels.inventory_item_id', '=', 'inventory_items.id')
            ->selectRaw('SUM(stock_levels.quantity * inventory_items.unit_price) as total_value')
            ->value('total_value') ?? 0;
    }

    public function getTotalItems()
    {
        return $this->stockLevels()->count();
    }

    public function getLowStockItems($threshold = 10)
    {
        return $this->stockLevels()
            ->where('quantity', '<=', $threshold)
            ->with('inventoryItem')
            ->get();
    }

    public function getOutOfStockItems()
    {
        return $this->stockLevels()
            ->where('quantity', '<=', 0)
            ->with('inventoryItem')
            ->get();
    }

    public function getStatusBadgeClass()
    {
        if (!$this->is_active) {
            return 'bg-danger';
        }
        
        if ($this->is_default) {
            return 'bg-success';
        }
        
        return 'bg-primary';
    }

    public function getStatusLabel()
    {
        if (!$this->is_active) {
            return 'Inactive';
        }
        
        if ($this->is_default) {
            return 'Default';
        }
        
        return 'Active';
    }

    // Static Methods
    public static function getDefaultForBranch($branchId)
    {
        return self::where('branch_id', $branchId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    public static function getActiveForBranch($branchId)
    {
        return self::where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
    }
} 