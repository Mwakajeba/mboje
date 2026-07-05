<?php

namespace App\Services\Inventory;

use App\Models\Inventory\CustomerStorageBalance;
use App\Models\Inventory\Item;
use App\Services\CustomerAccountSummaryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class CustomerStorageReportService
{
    public function __construct(
        private readonly CustomerAccountSummaryService $customerAccountSummary
    ) {}

    /**
     * @return array{
     *     itemDashboard: array<int, array<string, mixed>>,
     *     grandTotalQuantity: float,
     *     grandTotalMikopo: float,
     *     customerCount: int,
     *     hasData: bool
     * }
     */
    public function build(int $companyId, ?int $branchId): array
    {
        if (! Schema::hasTable('customer_storage_balances')) {
            return $this->emptyReport();
        }

        $balances = $this->balancesQuery($companyId, $branchId)->get();

        if ($balances->isEmpty()) {
            return $this->emptyReport();
        }

        $itemDashboard = $this->buildItemDashboard($balances);

        $customerIds = $balances->pluck('customer_id')->unique();
        $grandTotalMikopo = 0.0;
        foreach ($customerIds as $customerId) {
            $customer = $balances->firstWhere('customer_id', $customerId)?->customer;
            if ($customer) {
                $grandTotalMikopo += $this->customerAccountSummary->calculateMikopoTotal($customer);
            }
        }

        return [
            'itemDashboard' => $itemDashboard,
            'grandTotalQuantity' => (float) $balances->sum('quantity_on_hand'),
            'grandTotalMikopo' => $grandTotalMikopo,
            'customerCount' => $customerIds->count(),
            'hasData' => true,
        ];
    }

    public function balancesQuery(int $companyId, ?int $branchId)
    {
        if (! Schema::hasTable('customer_storage_balances')) {
            return CustomerStorageBalance::query()->whereRaw('1 = 0');
        }

        return CustomerStorageBalance::query()
            ->with(['customer', 'item'])
            ->where('company_id', $companyId)
            ->where('quantity_on_hand', '>', 0)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
    }

    public function formatQuantity(float $quantity, ?Item $item): string
    {
        $formatted = rtrim(rtrim(number_format($quantity, 2, '.', ','), '0'), '.');
        $unit = trim((string) ($item->unit_of_measure ?? ''));

        return $unit !== '' ? $formatted.' '.$unit : $formatted;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildItemDashboard(Collection $balances): array
    {
        return $balances
            ->groupBy('inventory_item_id')
            ->map(function (Collection $rows) {
                /** @var CustomerStorageBalance $first */
                $first = $rows->first();
                $item = $first->item;

                return [
                    'item_id' => (int) $first->inventory_item_id,
                    'item_name' => $item->name ?? '—',
                    'item_code' => $item->code ?? '—',
                    'unit' => $item->unit_of_measure ?? '',
                    'total_quantity' => (float) $rows->sum('quantity_on_hand'),
                    'customer_count' => $rows->pluck('customer_id')->unique()->count(),
                ];
            })
            ->sortBy('item_name')
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     itemDashboard: array<int, array<string, mixed>>,
     *     grandTotalQuantity: float,
     *     grandTotalMikopo: float,
     *     customerCount: int,
     *     hasData: bool
     * }
     */
    private function emptyReport(): array
    {
        return [
            'itemDashboard' => [],
            'grandTotalQuantity' => 0.0,
            'grandTotalMikopo' => 0.0,
            'customerCount' => 0,
            'hasData' => false,
        ];
    }
}
