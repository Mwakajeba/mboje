<?php

namespace App\Services;

use App\Models\Inventory\Item;
use App\Models\Inventory\Movement;
use App\Models\Inventory\Location;
use Illuminate\Support\Facades\DB;

class InventoryStockService
{
    /**
     * Get current stock quantity for a specific item at a specific location
     */
    public function getItemStockAtLocation($itemId, $locationId)
    {
        $stock = Movement::where('item_id', $itemId)
            ->where('location_id', $locationId)
            ->selectRaw('
                SUM(CASE 
                    WHEN movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in") 
                    THEN quantity 
                    WHEN movement_type IN ("transfer_out", "sold", "adjustment_out", "write_off") 
                    THEN -quantity 
                    ELSE 0 
                END) as current_stock
            ')
            ->value('current_stock');
        
        return (float) ($stock ?? 0);
    }

    /**
     * Get stock quantity for a specific item at a specific location as of a specific date
     * This is used for backdated transactions to calculate correct balance_before
     * 
     * When creating a new movement, it calculates stock from all movements with
     * movement_date < asOfDate OR (movement_date = asOfDate AND created_at < asOfTimestamp).
     * This ensures same-day transactions are ordered correctly.
     * 
     * @param int $itemId
     * @param int $locationId
     * @param string $asOfDate Date in Y-m-d format
     * @param int|null $excludeMovementId Optional: exclude a specific movement ID (for updates)
     * @param string|null $asOfTimestamp Optional: timestamp (Y-m-d H:i:s) to exclude movements created after this time on the same date
     * @return float
     */
    public function getItemStockAtLocationAsOfDate($itemId, $locationId, $asOfDate, $excludeMovementId = null, $asOfTimestamp = null)
    {
        $query = Movement::where('item_id', $itemId)
            ->where('location_id', $locationId)
            ->where(function($q) use ($asOfDate, $asOfTimestamp) {
                // Include movements before the date
                $q->whereDate('movement_date', '<', $asOfDate);
                
                // For same-day movements, only include if created before the timestamp (if provided)
                if ($asOfTimestamp) {
                    $q->orWhere(function($q2) use ($asOfDate, $asOfTimestamp) {
                        $q2->whereDate('movement_date', '=', $asOfDate)
                           ->where('created_at', '<', $asOfTimestamp);
                    });
                } else {
                    // If no timestamp provided, include all movements on the date
                    // (This handles the case where we're calculating for a new transaction)
                    $q->orWhereDate('movement_date', '=', $asOfDate);
                }
            });
        
        // Exclude a specific movement if provided (useful when updating existing movements)
        if ($excludeMovementId) {
            $query->where('id', '!=', $excludeMovementId);
        }
        
        // Get movements ordered chronologically to handle same-day transactions correctly
        $movements = $query->orderBy('movement_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();
        
        // Calculate stock by processing movements in chronological order
        $stock = 0;
        foreach ($movements as $movement) {
            if (in_array($movement->movement_type, ['opening_balance', 'transfer_in', 'purchased', 'adjustment_in'])) {
                $stock += $movement->quantity;
            } elseif (in_array($movement->movement_type, ['transfer_out', 'sold', 'adjustment_out', 'write_off'])) {
                $stock -= $movement->quantity;
            }
        }
        
        return (float) $stock;
    }

    /**
     * Get stock quantities for a specific item across all locations
     */
    public function getItemStockByLocation($itemId)
    {
        return Movement::where('item_id', $itemId)
            ->selectRaw('
                location_id,
                SUM(CASE 
                    WHEN movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in") 
                    THEN quantity 
                    WHEN movement_type IN ("transfer_out", "sold", "adjustment_out", "write_off") 
                    THEN -quantity 
                    ELSE 0 
                END) as current_stock
            ')
            ->groupBy('location_id')
            ->having('current_stock', '>', 0)
            ->with('location:id,name')
            ->get()
            ->map(function ($movement) {
                return [
                    'location_id' => $movement->location_id,
                    'location_name' => $movement->location->name ?? 'Unknown Location',
                    'quantity' => (float) $movement->current_stock
                ];
            });
    }

    /**
     * Get total stock quantity for a specific item across all locations
     */
    public function getItemTotalStock($itemId)
    {
        $totalStock = Movement::where('item_id', $itemId)
            ->selectRaw('
                SUM(CASE 
                    WHEN movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in") 
                    THEN quantity 
                    WHEN movement_type IN ("transfer_out", "sold", "adjustment_out", "write_off") 
                    THEN -quantity 
                    ELSE 0 
                END) as total_stock
            ')
            ->value('total_stock');
        
        return (float) ($totalStock ?? 0);
    }

    /**
     * Get stock summary for all items at a specific location
     */
    public function getLocationStockSummary($locationId)
    {
        return Movement::where('location_id', $locationId)
            ->selectRaw('
                item_id,
                SUM(CASE 
                    WHEN movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in") 
                    THEN quantity 
                    WHEN movement_type IN ("transfer_out", "sold", "adjustment_out", "write_off") 
                    THEN -quantity 
                    ELSE 0 
                END) as current_stock
            ')
            ->groupBy('item_id')
            ->having('current_stock', '>', 0)
            ->with('item:id,name,code,unit_of_measure')
            ->get()
            ->map(function ($movement) {
                return [
                    'item_id' => $movement->item_id,
                    'item_name' => $movement->item->name ?? 'Unknown Item',
                    'item_code' => $movement->item->code ?? 'N/A',
                    'unit_of_measure' => $movement->item->unit_of_measure ?? 'piece',
                    'quantity' => (float) $movement->current_stock
                ];
            });
    }

    /**
     * Get comprehensive stock report for all items across all locations
     */
    public function getComprehensiveStockReport($companyId = null)
    {
        $query = Movement::query();
        
        if ($companyId) {
            $query->whereHas('item', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        return $query->selectRaw('
                item_id,
                location_id,
                SUM(CASE 
                    WHEN movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in") 
                    THEN quantity 
                    WHEN movement_type IN ("transfer_out", "sold", "adjustment_out", "write_off") 
                    THEN -quantity 
                    ELSE 0 
                END) as current_stock
            ')
            ->groupBy('item_id', 'location_id')
            ->having('current_stock', '>', 0)
            ->with(['item:id,name,code,unit_of_measure,category_id', 'location:id,name'])
            ->get()
            ->groupBy('item_id')
            ->map(function ($itemMovements) {
                $item = $itemMovements->first()->item;
                $totalStock = $itemMovements->sum('current_stock');
                
                return [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'item_code' => $item->code,
                    'unit_of_measure' => $item->unit_of_measure,
                    'category_name' => $item->category->name ?? 'No Category',
                    'total_stock' => (float) $totalStock,
                    'locations' => $itemMovements->map(function ($movement) {
                        return [
                            'location_id' => $movement->location_id,
                            'location_name' => $movement->location->name ?? 'Unknown Location',
                            'quantity' => (float) $movement->current_stock
                        ];
                    })->values()
                ];
            })
            ->values();
    }

    /**
     * Get stock movement history for an item at a specific location
     */
    public function getItemLocationMovementHistory($itemId, $locationId, $limit = 50)
    {
        return Movement::where('item_id', $itemId)
            ->where('location_id', $locationId)
            ->with(['user:id,name'])
            ->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($movement) {
                return [
                    'id' => $movement->id,
                    'movement_type' => $movement->movement_type,
                    'movement_type_text' => $movement->movement_type_text,
                    'quantity' => $movement->quantity,
                    'unit_cost' => $movement->unit_cost,
                    'total_cost' => $movement->total_cost,
                    'balance_before' => $movement->balance_before,
                    'balance_after' => $movement->balance_after,
                    'reason' => $movement->reason,
                    'reference_number' => $movement->reference_number,
                    'movement_date' => $movement->movement_date,
                    'user_name' => $movement->user->name ?? 'Unknown User',
                    'created_at' => $movement->created_at,
                ];
            });
    }

    /**
     * Get low stock items (below reorder level) for a specific location
     */
    public function getLowStockItemsAtLocation($locationId)
    {
        $companyId = auth()->user()->company_id;

        // Subquery to calculate current stock for each item at the location
        $stockSubquery = Movement::select('item_id')
            ->selectRaw('
                SUM(CASE 
                    WHEN movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in") 
                    THEN quantity 
                    WHEN movement_type IN ("transfer_out", "sold", "adjustment_out", "write_off") 
                    THEN -quantity 
                    ELSE 0 
                END) as current_stock
            ')
            ->where('location_id', $locationId)
            ->groupBy('item_id');

        return Item::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('track_stock', true)
            ->leftJoinSub($stockSubquery, 'stock', function ($join) {
                $join->on('inventory_items.id', '=', 'stock.item_id');
            })
            ->select('inventory_items.*', DB::raw('COALESCE(stock.current_stock, 0) as location_current_stock'))
            ->when($locationId, function ($q) use ($locationId) {
                $branchId = \App\Models\InventoryLocation::where('id', $locationId)->value('branch_id');
                $q->visibleInSessionBranch($branchId ? (int) $branchId : null);
            }, function ($q) {
                $bid = session('branch_id') ? (int) session('branch_id') : null;
                $q->visibleInSessionBranch($bid);
            })
            ->where(function ($query) {
                $query->whereRaw('COALESCE(stock.current_stock, 0) <= inventory_items.reorder_level')
                    ->orWhereRaw('COALESCE(stock.current_stock, 0) <= inventory_items.minimum_stock');
            })
            ->get()
            ->map(function ($item) use ($locationId) {
                $currentStock = (float) $item->location_current_stock;
                $reorderLevel = (float) ($item->reorder_level ?? 0);
                $minimumStock = (float) ($item->minimum_stock ?? 0);
                
                // Get branch from location for price resolution
                $location = \App\Models\InventoryLocation::find($locationId);
                $branchId = $location ? $location->branch_id : session('branch_id');

                return [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'item_code' => $item->code,
                    'current_stock' => $currentStock,
                    'reorder_level' => $reorderLevel,
                    'minimum_stock' => $minimumStock,
                    'cost_price' => (float) $item->getCostPriceForBranchOrLocation($branchId, $locationId),
                    'unit_of_measure' => $item->unit_of_measure,
                    'status' => $currentStock <= $minimumStock ? 'out_of_stock' : 'low_stock'
                ];
            })
            ->values();
    }

    /**
     * Get out of stock items for a specific location
     */
    public function getOutOfStockItemsAtLocation($locationId)
    {
        return Movement::where('location_id', $locationId)
            ->selectRaw('
                item_id,
                SUM(CASE 
                    WHEN movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in") 
                    THEN quantity 
                    WHEN movement_type IN ("transfer_out", "sold", "adjustment_out", "write_off") 
                    THEN -quantity 
                    ELSE 0 
                END) as current_stock
            ')
            ->groupBy('item_id')
            ->having('current_stock', '<=', 0)
            ->with(['item:id,name,code,unit_of_measure'])
            ->get()
            ->map(function ($movement) {
                $item = $movement->item;
                
                return [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'item_code' => $item->code,
                    'current_stock' => (float) $movement->current_stock,
                    'unit_of_measure' => $item->unit_of_measure,
                ];
            });
    }

    /**
     * Get items with stock > 0 at a specific location
     */
    public function getItemsWithStockAtLocation($companyId, $locationId)
    {
        $branchId = \App\Models\InventoryLocation::where('id', $locationId)->value('branch_id');
        $branchId = $branchId ? (int) $branchId : (session('branch_id') ? (int) session('branch_id') : null);

        return Item::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('track_stock', true)
            ->visibleInSessionBranch($branchId)
            ->whereHas('movements', function ($query) use ($locationId) {
                $query->where('location_id', $locationId);
            })
            ->get()
            ->filter(function ($item) use ($locationId) {
                $stock = $this->getItemStockAtLocation($item->id, $locationId);
                return $stock > 0;
            })
            ->sortBy('name')
            ->values();
    }

    /**
     * Get items with stock > 0 at a specific location (for services, include all active items)
     */
    public function getAvailableItemsForSales($companyId, $locationId)
    {
        $branchId = session('branch_id') ? (int) session('branch_id') : null;

        return Item::where('company_id', $companyId)
            ->where('is_active', true)
            ->visibleInSessionBranch($branchId)
            ->get()
            ->filter(function ($item) use ($locationId) {
                // For services or non-stock-tracked items, always include them
                if ($item->item_type === 'service' || !$item->track_stock) {
                    return true;
                }
                
                // For products, include even when stock is 0 so they can be shown
                // in the UI but disabled/faded when out of stock.
                $stock = $this->getItemStockAtLocation($item->id, $locationId);
                return $stock >= 0;
            })
            ->sortBy('name')
            ->values();
    }
}
