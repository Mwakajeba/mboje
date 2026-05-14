<?php

namespace App\Services;

use App\Models\Inventory\ExpiryTracking;
use App\Models\Inventory\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpiryStockService
{
    /**
     * Add stock with expiry tracking
     */
    public function addStock(
        $itemId,
        $locationId,
        $quantity,
        $unitCost,
        $expiryDate,
        $referenceType,
        $referenceId,
        $batchNumber = null,
        $referenceNumber = null
    ): ExpiryTracking {
        $batchNumber = $batchNumber ?: $this->generateBatchNumber($referenceType, $referenceId);
        
        return ExpiryTracking::create([
            'item_id' => $itemId,
            'location_id' => $locationId,
            'batch_number' => $batchNumber,
            'expiry_date' => $expiryDate,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $quantity * $unitCost,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reference_number' => $referenceNumber,
        ]);
    }

    /**
     * Consume stock using FEFO (First Expiry First Out) or FIFO (First In First Out)
     */
    public function consumeStock($itemId, $locationId, $quantity, $method = 'FEFO'): array
    {
        $availableLayers = ExpiryTracking::forItem($itemId)
            ->forLocation($locationId)
            ->available()
            ->when($method === 'FEFO', fn($q) => $q->orderByExpiry())
            ->when($method === 'FIFO', fn($q) => $q->orderByFifo())
            ->get();

        $remainingToConsume = $quantity;
        $consumedLayers = [];

        Log::info('ExpiryStockService: Starting stock consumption', [
            'item_id' => $itemId,
            'location_id' => $locationId,
            'quantity' => $quantity,
            'method' => $method,
            'available_layers' => $availableLayers->count()
        ]);

        foreach ($availableLayers as $layer) {
            if ($remainingToConsume <= 0) break;

            $consumeFromLayer = min($remainingToConsume, $layer->quantity);
            
            Log::info('ExpiryStockService: Consuming from layer', [
                'layer_id' => $layer->id,
                'batch_number' => $layer->batch_number,
                'expiry_date' => $layer->expiry_date,
                'consume_quantity' => $consumeFromLayer,
                'remaining_quantity' => $layer->quantity
            ]);

            // Update layer
            $layer->consume($consumeFromLayer);

            $consumedLayers[] = [
                'layer_id' => $layer->id,
                'quantity' => $consumeFromLayer,
                'unit_cost' => $layer->unit_cost,
                'total_cost' => $consumeFromLayer * $layer->unit_cost,
                'expiry_date' => $layer->expiry_date,
                'batch_number' => $layer->batch_number,
            ];

            $remainingToConsume -= $consumeFromLayer;
        }

        if ($remainingToConsume > 0) {
            Log::warning('ExpiryStockService: Insufficient stock for consumption', [
                'item_id' => $itemId,
                'location_id' => $locationId,
                'requested' => $quantity,
                'consumed' => $quantity - $remainingToConsume,
                'remaining' => $remainingToConsume
            ]);
        }

        return $consumedLayers;
    }

    /**
     * Get available stock for an item at a location with expiry details
     */
    public function getAvailableStock($itemId, $locationId): array
    {
        $layers = ExpiryTracking::forItem($itemId)
            ->forLocation($locationId)
            ->available()
            ->orderByExpiry()
            ->get();

        $totalQuantity = $layers->sum('quantity');
        $totalValue = $layers->sum('total_cost');
        $averageCost = $totalQuantity > 0 ? $totalValue / $totalQuantity : 0;

        return [
            'total_quantity' => $totalQuantity,
            'total_value' => $totalValue,
            'average_cost' => $averageCost,
            'layers' => $layers->map(function ($layer) {
                return [
                    'id' => $layer->id,
                    'batch_number' => $layer->batch_number,
                    'expiry_date' => $layer->expiry_date,
                    'quantity' => $layer->quantity,
                    'unit_cost' => $layer->unit_cost,
                    'total_cost' => $layer->total_cost,
                    'days_until_expiry' => $layer->days_until_expiry,
                    'expiry_status' => $layer->expiry_status,
                ];
            })->toArray()
        ];
    }

    /**
     * Get current cost using FEFO
     */
    public function getCurrentCost($itemId, $locationId): float
    {
        $oldestLayer = ExpiryTracking::forItem($itemId)
            ->forLocation($locationId)
            ->available()
            ->orderByExpiry()
            ->first();

        return $oldestLayer ? (float) $oldestLayer->unit_cost : 0;
    }

    /**
     * Get expiring stock report
     */
    public function getExpiringStock($days = null, $locationId = null): array
    {
        // Use global setting if days not specified
        if ($days === null) {
            $days = \App\Models\SystemSetting::where('key', 'inventory_global_expiry_warning_days')->value('value') ?? 30;
        }
        
        $query = ExpiryTracking::with(['item', 'location'])
            ->available()
            ->expiringSoon($days);

        if ($locationId) {
            $query->forLocation($locationId);
        }

        return $query->get()->groupBy('item_id')->map(function ($layers) {
            $item = $layers->first()->item;
            return [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'item_code' => $item->code,
                'total_quantity' => $layers->sum('quantity'),
                'total_value' => $layers->sum('total_cost'),
                'earliest_expiry' => $layers->min('expiry_date'),
                'layers' => $layers->map(function ($layer) {
                    return [
                        'batch_number' => $layer->batch_number,
                        'expiry_date' => $layer->expiry_date,
                        'quantity' => $layer->quantity,
                        'unit_cost' => $layer->unit_cost,
                        'days_until_expiry' => $layer->days_until_expiry,
                    ];
                })->toArray()
            ];
        })->toArray();
    }

    /**
     * Get expired stock report
     */
    public function getExpiredStock($locationId = null): array
    {
        $query = ExpiryTracking::with(['item', 'location'])
            ->available()
            ->expired();

        if ($locationId) {
            $query->forLocation($locationId);
        }

        return $query->get()->groupBy('item_id')->map(function ($layers) {
            $item = $layers->first()->item;
            return [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'item_code' => $item->code,
                'total_quantity' => $layers->sum('quantity'),
                'total_value' => $layers->sum('total_cost'),
                'layers' => $layers->map(function ($layer) {
                    return [
                        'batch_number' => $layer->batch_number,
                        'expiry_date' => $layer->expiry_date,
                        'quantity' => $layer->quantity,
                        'unit_cost' => $layer->unit_cost,
                        'days_expired' => abs($layer->days_until_expiry),
                    ];
                })->toArray()
            ];
        })->toArray();
    }

    /**
     * Write off expired stock
     */
    public function writeOffExpiredStock($itemId, $locationId, $batchNumber = null): array
    {
        $query = ExpiryTracking::forItem($itemId)
            ->forLocation($locationId)
            ->available()
            ->expired();

        if ($batchNumber) {
            $query->where('batch_number', $batchNumber);
        }

        $expiredLayers = $query->get();
        $writeOffDetails = [];

        foreach ($expiredLayers as $layer) {
            $writeOffDetails[] = [
                'layer_id' => $layer->id,
                'batch_number' => $layer->batch_number,
                'expiry_date' => $layer->expiry_date,
                'quantity' => $layer->quantity,
                'unit_cost' => $layer->unit_cost,
                'total_cost' => $layer->total_cost,
            ];

            // Set quantity to 0 (effectively writing off)
            $layer->update(['quantity' => 0, 'total_cost' => 0]);
        }

        return $writeOffDetails;
    }

    /**
     * Generate batch number
     */
    private function generateBatchNumber($referenceType, $referenceId): string
    {
        $prefix = match($referenceType) {
            'purchase_invoice' => 'PINV',
            'cash_purchase' => 'CASH',
            'opening_balance' => 'OB',
            'pos_sale' => 'POS',
            default => 'BATCH'
        };

        return $prefix . '-' . $referenceId . '-' . now()->format('YmdHis');
    }

    /**
     * Consolidate layers with same expiry date and batch
     */
    public function consolidateLayers($itemId, $locationId): int
    {
        $layers = ExpiryTracking::forItem($itemId)
            ->forLocation($locationId)
            ->available()
            ->get()
            ->groupBy(function ($layer) {
                return $layer->expiry_date->format('Y-m-d') . '|' . $layer->batch_number;
            });

        $consolidated = 0;

        foreach ($layers as $groupKey => $groupLayers) {
            if ($groupLayers->count() > 1) {
                $firstLayer = $groupLayers->first();
                $totalQuantity = $groupLayers->sum('quantity');
                $totalValue = $groupLayers->sum('total_cost');
                $averageCost = $totalQuantity > 0 ? $totalValue / $totalQuantity : $firstLayer->unit_cost;

                // Update first layer with consolidated values
                $firstLayer->update([
                    'quantity' => $totalQuantity,
                    'unit_cost' => $averageCost,
                    'total_cost' => $totalValue,
                ]);

                // Delete other layers
                $groupLayers->skip(1)->each(function ($layer) {
                    $layer->delete();
                });

                $consolidated += $groupLayers->count() - 1;
            }
        }

        return $consolidated;
    }
}
