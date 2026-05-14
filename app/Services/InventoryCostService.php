<?php

namespace App\Services;

use App\Models\Inventory\Item;
use App\Models\InventoryCostLayer;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InventoryCostService
{
    /**
     * Get the configured cost method from system settings
     */
    private function getCostMethod(): string
    {
        return SystemSetting::where('key', 'inventory_cost_method')->value('value') ?? 'fifo';
    }

    /**
     * Normalize unit cost to VAT-exclusive
     * 
     * @param float $grossCost The gross unit cost (may include VAT)
     * @param string $vatType 'inclusive', 'exclusive', or 'no_vat'
     * @param float $vatRate VAT rate as percentage (e.g., 18 for 18%)
     * @return float VAT-exclusive unit cost
     */
    public function normalizeCostToVatExclusive(float $grossCost, string $vatType, float $vatRate = 0): float
    {
        if ($vatType === 'inclusive' && $vatRate > 0) {
            // VAT is included in the price, extract it
            return $grossCost / (1 + ($vatRate / 100));
        }
        // For 'exclusive' or 'no_vat', the cost is already VAT-exclusive
        return $grossCost;
    }

    /**
     * Add inventory (purchase, adjustment in, opening balance, etc.)
     * 
     * @param int $itemId
     * @param float $quantity
     * @param float $unitCost Unit cost (should already be VAT-exclusive, or will be normalized if VAT params provided)
     * @param string $transactionType
     * @param string|null $reference
     * @param string|null $transactionDate
     * @param string|null $vatType Optional: 'inclusive', 'exclusive', or 'no_vat' - if provided, will normalize cost
     * @param float $vatRate Optional: VAT rate as percentage (e.g., 18 for 18%)
     */
    public function addInventory(
        int $itemId,
        float $quantity,
        float $unitCost,
        string $transactionType,
        string $reference = null,
        string $transactionDate = null,
        string $vatType = null,
        float $vatRate = 0
    ): void {
        $transactionDate = $transactionDate ?: Carbon::now()->toDateString();
        
        // Normalize cost to VAT-exclusive if VAT information is provided
        $netUnitCost = $unitCost;
        if ($vatType !== null) {
            $netUnitCost = $this->normalizeCostToVatExclusive($unitCost, $vatType, $vatRate);
        }
        
        // Create cost layer with VAT-exclusive cost
        InventoryCostLayer::create([
            'item_id' => $itemId,
            'reference' => $reference,
            'transaction_type' => $transactionType,
            'quantity' => $quantity,
            'remaining_quantity' => $quantity,
            'unit_cost' => $netUnitCost,
            'total_cost' => $quantity * $netUnitCost,
            'transaction_date' => $transactionDate,
            'is_consumed' => false,
        ]);

        // Update item's weighted average cost if using weighted average method
        // This should only happen on purchases, which is correct here
        if ($this->getCostMethod() === 'weighted_average') {
            $this->updateWeightedAverageCost($itemId);
        }
    }

    /**
     * Remove inventory (sale, adjustment out, etc.) and return cost information
     * 
     * @param int $itemId
     * @param float $quantity
     * @param string $transactionType
     * @param string|null $reference
     * @param string|null $transactionDate
     * @param int|null $branchId Optional branch ID for fallback cost resolution (location → branch → default)
     * @param int|null $locationId Optional location ID for fallback cost resolution (location → branch → default)
     */
    public function removeInventory(
        int $itemId,
        float $quantity,
        string $transactionType,
        string $reference = null,
        string $transactionDate = null,
        ?int $branchId = null,
        ?int $locationId = null
    ): array {
        $transactionDate = $transactionDate ?: Carbon::now()->toDateString();
        $costMethod = $this->getCostMethod();
        
        if ($costMethod === 'fifo') {
            return $this->removeInventoryFIFO($itemId, $quantity, $transactionType, $reference, $transactionDate, $branchId, $locationId);
        } else {
            return $this->removeInventoryWeightedAverage($itemId, $quantity, $transactionType, $reference, $transactionDate, $branchId, $locationId);
        }
    }

    /**
     * Remove inventory using FIFO method
     */
    private function removeInventoryFIFO(
        int $itemId,
        float $quantity,
        string $transactionType,
        string $reference,
        string $transactionDate,
        ?int $branchId = null,
        ?int $locationId = null
    ): array {
        $totalCost = 0;
        $remainingQuantity = $quantity;
        $costBreakdown = [];

        // Get available cost layers ordered by date (FIFO)
        $costLayers = InventoryCostLayer::where('item_id', $itemId)
            ->where('remaining_quantity', '>', 0)
            ->where('is_consumed', false)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        foreach ($costLayers as $layer) {
            if ($remainingQuantity <= 0) break;

            $quantityToTake = min($remainingQuantity, $layer->remaining_quantity);
            $layerCost = $quantityToTake * $layer->unit_cost;
            
            $totalCost += $layerCost;
            $costBreakdown[] = [
                'layer_id' => $layer->id,
                'quantity' => $quantityToTake,
                'unit_cost' => $layer->unit_cost,
                'total_cost' => $layerCost,
            ];

            // Update the layer
            $layer->remaining_quantity -= $quantityToTake;
            if ($layer->remaining_quantity <= 0) {
                $layer->is_consumed = true;
            }
            $layer->save();

            $remainingQuantity -= $quantityToTake;
        }

        // If no cost layers were found or total cost is 0, use resolved cost as fallback
        // Fallback hierarchy: location cost → branch cost → item default cost
        if ($totalCost == 0) {
            $item = Item::find($itemId);
            // Use the resolved cost based on location/branch hierarchy
            $fallbackCost = $item->getCostPriceForBranchOrLocation($branchId, $locationId);
            $totalCost = $quantity * $fallbackCost;
            
            $costBreakdown[] = [
                'quantity' => $quantity,
                'unit_cost' => $fallbackCost,
                'total_cost' => $totalCost,
                'note' => 'Using resolved cost (location→branch→default) as fallback - no cost layers available'
            ];
        }

        // Create negative cost layer for the removal
        InventoryCostLayer::create([
            'item_id' => $itemId,
            'reference' => $reference,
            'transaction_type' => $transactionType,
            'quantity' => -$quantity,
            'remaining_quantity' => 0,
            'unit_cost' => $totalCost / $quantity,
            'total_cost' => -$totalCost,
            'transaction_date' => $transactionDate,
            'is_consumed' => true,
        ]);

        return [
            'total_cost' => $totalCost,
            'average_unit_cost' => $quantity > 0 ? $totalCost / $quantity : 0,
            'cost_breakdown' => $costBreakdown,
        ];
    }

    /**
     * Remove inventory using Weighted Average method
     * Calculates weighted average from cost layers, not from item->cost_price
     */
    private function removeInventoryWeightedAverage(
        int $itemId,
        float $quantity,
        string $transactionType,
        string $reference,
        string $transactionDate,
        ?int $branchId = null,
        ?int $locationId = null
    ): array {
        // Calculate weighted average from available cost layers (not from item->cost_price)
        $costLayers = InventoryCostLayer::where('item_id', $itemId)
            ->where('remaining_quantity', '>', 0)
            ->where('is_consumed', false)
            ->get();

        $totalQuantity = $costLayers->sum('remaining_quantity');
        $totalValue = $costLayers->sum(function ($layer) {
            return $layer->remaining_quantity * $layer->unit_cost;
        });

        // Calculate weighted average cost from layers
        $averageCost = ($totalQuantity > 0) ? ($totalValue / $totalQuantity) : 0;
        
        // Fallback to resolved cost if no layers found
        // Fallback hierarchy: location cost → branch cost → item default cost
        if ($averageCost == 0) {
            $item = Item::find($itemId);
            $averageCost = $item->getCostPriceForBranchOrLocation($branchId, $locationId);
        }

        $totalCost = $quantity * $averageCost;

        // Create negative cost layer for the removal
        InventoryCostLayer::create([
            'item_id' => $itemId,
            'reference' => $reference,
            'transaction_type' => $transactionType,
            'quantity' => -$quantity,
            'remaining_quantity' => 0,
            'unit_cost' => $averageCost,
            'total_cost' => -$totalCost,
            'transaction_date' => $transactionDate,
            'is_consumed' => true,
        ]);

        return [
            'total_cost' => $totalCost,
            'average_unit_cost' => $averageCost,
            'cost_breakdown' => [
                [
                    'quantity' => $quantity,
                    'unit_cost' => $averageCost,
                    'total_cost' => $totalCost,
                ]
            ],
        ];
    }

    /**
     * Update item's weighted average cost
     * 
     * Formula: NewAverageCost = ((PrevQty * PrevCost) + (NewQty * NetCost)) / (PrevQty + NewQty)
     * This should only be called on purchases, not on sales.
     * 
     * Made public so it can be called when removing old cost layers during purchase invoice updates.
     */
    public function updateWeightedAverageCost(int $itemId): void
    {
        $item = Item::find($itemId);
        if (!$item) return;

        // Calculate weighted average from all cost layers with remaining quantity
        $layers = InventoryCostLayer::where('item_id', $itemId)
            ->where('remaining_quantity', '>', 0)
            ->where('is_consumed', false)
            ->get();

        $totalQuantity = $layers->sum('remaining_quantity');
        $totalValue = $layers->sum(function ($layer) {
            return $layer->remaining_quantity * $layer->unit_cost;
        });

        if ($totalQuantity > 0) {
            $weightedAverageCost = $totalValue / $totalQuantity;
            $item->update(['cost_price' => $weightedAverageCost]);
        } else {
            // If no layers remain, check actual stock from movements to be sure
            // Only set to 0 if there's truly no stock remaining
            $stockService = new \App\Services\InventoryStockService();
            $actualStock = $stockService->getItemTotalStock($itemId);
            
            if ($actualStock <= 0) {
                // Only set to 0 if there's truly no stock remaining
                $item->update(['cost_price' => 0]);
            } else {
                // If there's stock but no layers, this is an inconsistency - log it but don't change cost_price
                \Log::warning('InventoryCostService::updateWeightedAverageCost - Stock exists but no cost layers', [
                    'item_id' => $itemId,
                    'actual_stock' => $actualStock,
                    'current_cost_price' => $item->cost_price,
                ]);
                // Keep existing cost_price - don't change it
            }
        }
    }

    /**
     * Get current inventory value for an item
     */
    public function getInventoryValue(int $itemId): array
    {
        $costMethod = $this->getCostMethod();
        
        if ($costMethod === 'fifo') {
            return $this->getInventoryValueFIFO($itemId);
        } else {
            return $this->getInventoryValueWeightedAverage($itemId);
        }
    }

    /**
     * Get inventory value using FIFO method
     */
    private function getInventoryValueFIFO(int $itemId): array
    {
        $layers = InventoryCostLayer::where('item_id', $itemId)
            ->where('remaining_quantity', '>', 0)
            ->where('is_consumed', false)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $totalQuantity = $layers->sum('remaining_quantity');
        $totalValue = $layers->sum(function ($layer) {
            return $layer->remaining_quantity * $layer->unit_cost;
        });

        return [
            'total_quantity' => $totalQuantity,
            'total_value' => $totalValue,
            'average_cost' => $totalQuantity > 0 ? $totalValue / $totalQuantity : 0,
            'cost_layers' => $layers->map(function ($layer) {
                return [
                    'date' => $layer->transaction_date,
                    'quantity' => $layer->remaining_quantity,
                    'unit_cost' => $layer->unit_cost,
                    'total_value' => $layer->remaining_quantity * $layer->unit_cost,
                ];
            }),
        ];
    }

    /**
     * Get inventory value using Weighted Average method
     */
    private function getInventoryValueWeightedAverage(int $itemId): array
    {
        $item = Item::find($itemId);
        $currentStock = $item->current_stock ?? 0;
        $averageCost = $item->cost_price ?? 0;
        $totalValue = $currentStock * $averageCost;

        return [
            'total_quantity' => $currentStock,
            'total_value' => $totalValue,
            'average_cost' => $averageCost,
            'cost_layers' => [
                [
                    'date' => Carbon::now()->toDateString(),
                    'quantity' => $currentStock,
                    'unit_cost' => $averageCost,
                    'total_value' => $totalValue,
                ]
            ],
        ];
    }

    /**
     * Recalculate all costs for an item (useful for data corrections)
     */
    public function recalculateItemCosts(int $itemId): void
    {
        $costMethod = $this->getCostMethod();
        
        if ($costMethod === 'weighted_average') {
            $this->updateWeightedAverageCost($itemId);
        }
        // For FIFO, costs are already tracked in layers, no recalculation needed
    }

    /**
     * Restore cost layers for a reversed sale transaction
     * This reverses the effect of removeInventory() when a sale is updated or deleted
     * 
     * @param int $itemId
     * @param float $quantity Quantity that was sold
     * @param float $unitCost The unit cost that was used (from movement record)
     * @param string $reference The original reference (e.g., 'Sales Invoice: INV001')
     */
    public function restoreInventoryCostLayers(int $itemId, float $quantity, float $unitCost, string $reference): void
    {
        $costMethod = $this->getCostMethod();
        
        // Find and delete the negative cost layer created for this sale
        $negativeLayers = InventoryCostLayer::where('item_id', $itemId)
            ->where('reference', $reference)
            ->where('transaction_type', 'sale')
            ->where('quantity', '<', 0)
            ->get();

        foreach ($negativeLayers as $negativeLayer) {
            // For FIFO: We need to restore the consumed layers
            // Restore to the oldest consumed layers first (reverse FIFO order)
            if ($costMethod === 'fifo') {
                $quantityToRestore = abs($negativeLayer->quantity);
                
                // First, restore to fully consumed layers (oldest first - reverse of consumption order)
                $fullyConsumedLayers = InventoryCostLayer::where('item_id', $itemId)
                    ->where('is_consumed', true)
                    ->where('remaining_quantity', 0)
                    ->orderBy('transaction_date', 'asc')  // Oldest first (reverse of FIFO consumption)
                    ->orderBy('id', 'asc')
                    ->get();
                
                foreach ($fullyConsumedLayers as $layer) {
                    if ($quantityToRestore <= 0) break;
                    
                    $restoreAmount = min($quantityToRestore, $layer->quantity);
                    $layer->remaining_quantity += $restoreAmount;
                    if ($layer->remaining_quantity > 0) {
                        $layer->is_consumed = false;
                    }
                    $layer->save();
                    $quantityToRestore -= $restoreAmount;
                }
                
                // Then restore to partially consumed layers (oldest first)
                if ($quantityToRestore > 0) {
                    $partiallyConsumedLayers = InventoryCostLayer::where('item_id', $itemId)
                        ->where('remaining_quantity', '>', 0)
                        ->where('remaining_quantity', '<', DB::raw('quantity'))
                        ->where('is_consumed', false)
                        ->orderBy('transaction_date', 'asc')  // Oldest first
                        ->orderBy('id', 'asc')
                        ->get();

                    foreach ($partiallyConsumedLayers as $layer) {
                        if ($quantityToRestore <= 0) break;
                        
                        $originalQuantity = $layer->quantity;
                        $currentRemaining = $layer->remaining_quantity;
                        $canRestore = $originalQuantity - $currentRemaining;
                        
                        if ($canRestore > 0) {
                            $restoreAmount = min($quantityToRestore, $canRestore);
                            $layer->remaining_quantity += $restoreAmount;
                            if ($layer->remaining_quantity >= $originalQuantity) {
                                $layer->is_consumed = false;
                            }
                            $layer->save();
                            $quantityToRestore -= $restoreAmount;
                        }
                    }
                }
            }
            
            // Delete the negative layer
            $negativeLayer->delete();
        }
        
        // Recalculate weighted average if needed
        if ($costMethod === 'weighted_average') {
            $this->updateWeightedAverageCost($itemId);
        }
    }
} 