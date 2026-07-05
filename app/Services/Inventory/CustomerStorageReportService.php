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
     *     customerLines: array<int, array<string, mixed>>,
     *     grandTotalQuantity: float,
     *     grandTotalMikopo: float,
     *     customerCount: int
     * }
     */
    public function build(int $companyId, ?int $branchId): array
    {
        if (! Schema::hasTable('customer_storage_balances')) {
            return $this->emptyReport();
        }

        $balances = CustomerStorageBalance::query()
            ->with(['customer', 'item'])
            ->where('company_id', $companyId)
            ->where('quantity_on_hand', '>', 0)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get();

        if ($balances->isEmpty()) {
            return $this->emptyReport();
        }

        $itemDashboard = $this->buildItemDashboard($balances);
        $customerLines = $this->buildCustomerLines($balances);

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
            'customerLines' => $customerLines,
            'grandTotalQuantity' => (float) $balances->sum('quantity_on_hand'),
            'grandTotalMikopo' => $grandTotalMikopo,
            'customerCount' => $customerIds->count(),
        ];
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
     * @return array<int, array<string, mixed>>
     */
    private function buildCustomerLines(Collection $balances): array
    {
        $mikopoByCustomer = [];

        return $balances
            ->map(function (CustomerStorageBalance $row) use (&$mikopoByCustomer) {
                $customer = $row->customer;
                $item = $row->item;
                $customerId = (int) $row->customer_id;

                if ($customer && ! array_key_exists($customerId, $mikopoByCustomer)) {
                    $mikopoByCustomer[$customerId] = $this->customerAccountSummary->calculateMikopoTotal($customer);
                }

                $quantity = (float) $row->quantity_on_hand;

                return [
                    'customer_id' => $customerId,
                    'customer_name' => $customer->name ?? '—',
                    'customer_phone' => $customer->phone ?? '',
                    'item_name' => $item->name ?? '—',
                    'item_code' => $item->code ?? '—',
                    'quantity' => $quantity,
                    'unit' => $item->unit_of_measure ?? '',
                    'quantity_display' => $this->formatQuantity($quantity, $item),
                    'mikopo_total' => (float) ($mikopoByCustomer[$customerId] ?? 0),
                ];
            })
            ->sortBy([
                ['customer_name', 'asc'],
                ['item_name', 'asc'],
            ])
            ->values()
            ->all();
    }

    private function formatQuantity(float $quantity, ?Item $item): string
    {
        $formatted = rtrim(rtrim(number_format($quantity, 2, '.', ','), '0'), '.');
        $unit = trim((string) ($item->unit_of_measure ?? ''));

        return $unit !== '' ? $formatted.' '.$unit : $formatted;
    }

    /**
     * @return array{
     *     itemDashboard: array<int, array<string, mixed>>,
     *     customerLines: array<int, array<string, mixed>>,
     *     grandTotalQuantity: float,
     *     grandTotalMikopo: float,
     *     customerCount: int
     * }
     */
    private function emptyReport(): array
    {
        return [
            'itemDashboard' => [],
            'customerLines' => [],
            'grandTotalQuantity' => 0.0,
            'grandTotalMikopo' => 0.0,
            'customerCount' => 0,
        ];
    }
}
