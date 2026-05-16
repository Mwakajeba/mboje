<?php

namespace App\Services;

use App\Models\Inventory\Item;
use App\Models\Inventory\Movement;
use App\Models\InventoryLocation;
use App\Models\SystemSetting;
use Illuminate\Support\Collection;

class InventoryValueService
{
    private const STOCK_SUM_SQL = '
        SUM(CASE 
            WHEN movement_type IN ("opening_balance", "transfer_in", "purchased", "adjustment_in") 
            THEN quantity 
            WHEN movement_type IN ("transfer_out", "sold", "adjustment_out", "write_off") 
            THEN -quantity 
            ELSE 0 
        END) as current_stock';

    /**
     * Active locations for the company, optionally scoped to a branch.
     */
    public function locationsForCompany(int $companyId, ?int $branchId = null): Collection
    {
        $query = InventoryLocation::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->with('branch:id,name')
            ->orderBy('name');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get();
    }

    /**
     * Per-item stock quantities at a location (only items with stock > 0).
     *
     * @return Collection<int, float> item_id => quantity
     */
    public function stockQuantitiesAtLocation(int $locationId, int $companyId): Collection
    {
        return Movement::query()
            ->where('location_id', $locationId)
            ->whereHas('item', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->where('is_active', true)
                    ->where('track_stock', true);
            })
            ->selectRaw('item_id, '.self::STOCK_SUM_SQL)
            ->groupBy('item_id')
            ->having('current_stock', '>', 0)
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->item_id => (float) $row->current_stock]);
    }

    /**
     * Item lines with cost and selling totals for one location.
     */
    public function buildLocationDetail(int $locationId, int $companyId): array
    {
        $location = InventoryLocation::query()
            ->where('company_id', $companyId)
            ->with('branch:id,name')
            ->findOrFail($locationId);

        $stockByItem = $this->stockQuantitiesAtLocation($locationId, $companyId);
        if ($stockByItem->isEmpty()) {
            return [
                'location' => $location,
                'rows' => [],
                'totals' => [
                    'items_count' => 0,
                    'total_quantity' => 0.0,
                    'total_cost' => 0.0,
                    'total_selling' => 0.0,
                ],
            ];
        }

        $costService = new InventoryCostService();
        $items = Item::query()
            ->with('category:id,name')
            ->where('company_id', $companyId)
            ->whereIn('id', $stockByItem->keys())
            ->orderBy('name')
            ->get();

        $rows = [];
        $totalQuantity = 0.0;
        $totalCost = 0.0;
        $totalSelling = 0.0;

        foreach ($items as $item) {
            $quantity = (float) $stockByItem->get($item->id, 0);
            if ($quantity <= 0) {
                continue;
            }

            $unitCost = $this->resolveUnitCost($item, $costService, $locationId);
            $unitSelling = (float) $item->getUnitPriceForLocation($locationId);
            $lineTotalCost = round($quantity * $unitCost, 2);
            $lineTotalSelling = round($quantity * $unitSelling, 2);

            $rows[] = [
                'item' => $item,
                'quantity' => $quantity,
                'unit_of_measure' => $item->unit_of_measure ?? 'piece',
                'unit_cost' => $unitCost,
                'total_cost' => $lineTotalCost,
                'unit_selling_price' => $unitSelling,
                'total_selling_price' => $lineTotalSelling,
            ];

            $totalQuantity += $quantity;
            $totalCost += $lineTotalCost;
            $totalSelling += $lineTotalSelling;
        }

        return [
            'location' => $location,
            'rows' => $rows,
            'totals' => [
                'items_count' => count($rows),
                'total_quantity' => round($totalQuantity, 2),
                'total_cost' => round($totalCost, 2),
                'total_selling' => round($totalSelling, 2),
            ],
        ];
    }

    /**
     * Summary totals per location (for the location picker index).
     */
    public function buildLocationSummaries(Collection $locations, int $companyId): Collection
    {
        return $locations->map(function (InventoryLocation $location) use ($companyId) {
            $detail = $this->buildLocationDetail($location->id, $companyId);

            return [
                'location' => $location,
                'items_count' => $detail['totals']['items_count'],
                'total_quantity' => $detail['totals']['total_quantity'],
                'total_cost' => $detail['totals']['total_cost'],
                'total_selling' => $detail['totals']['total_selling'],
            ];
        });
    }

    /**
     * Total inventory cost value for one location.
     */
    public function totalCostValueAtLocation(int $locationId, int $companyId): float
    {
        return $this->buildLocationDetail($locationId, $companyId)['totals']['total_cost'];
    }

    private function resolveUnitCost(Item $item, InventoryCostService $costService, int $locationId): float
    {
        $inventoryValue = $costService->getInventoryValue($item->id);
        $averageCost = (float) ($inventoryValue['average_cost'] ?? 0);
        if ($averageCost > 0) {
            return $averageCost;
        }

        return (float) $item->getCostPriceForLocation($locationId);
    }

    public function functionalCurrency(): string
    {
        return SystemSetting::getValue(
            'functional_currency',
            auth()->user()?->company?->functional_currency ?? 'TZS'
        );
    }
}
