<?php

namespace App\Services;

use App\Models\CashCollateral;
use App\Models\Customer;
use App\Models\Inventory\CustomerStorageBalance;
use App\Models\Inventory\CustomerStorageSale;
use App\Models\Inventory\Item;
use App\Models\Journal;
use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CustomerAccountSummaryService
{
    /**
     * @return array{
     *     totalCropSales: float,
     *     mikopoTotal: float,
     *     customerNetBalance: float,
     *     cropSalesDashboard: array<int, array<string, mixed>>,
     *     storageBalances: array<int, array<string, mixed>>,
     *     customerCashCollateral: ?CashCollateral
     * }
     */
    public function build(Customer $customer): array
    {
        $totalCropSales = $this->calculateTotalCropSales($customer);
        $mikopoTotal = $this->calculateMikopoTotal($customer);

        return [
            'totalCropSales' => $totalCropSales,
            'mikopoTotal' => $mikopoTotal,
            'customerNetBalance' => $totalCropSales - $mikopoTotal,
            'cropSalesDashboard' => $this->buildCropSalesDashboard($customer),
            'storageBalances' => $this->buildStorageBalances($customer),
            'customerCashCollateral' => $customer->cashCollaterals()->first(),
        ];
    }

    public function calculateTotalCropSales(Customer $customer): float
    {
        if (! Schema::hasTable('customer_storage_sales')) {
            return 0.0;
        }

        $companyId = (int) Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        return (float) CustomerStorageSale::query()
            ->where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('total');
    }

    public function calculateMikopoTotal(Customer $customer): float
    {
        $total = 0.0;
        $cashCollaterals = CashCollateral::where('customer_id', $customer->id)->get();

        foreach ($cashCollaterals as $collateral) {
            $deposits = Receipt::where('reference', $collateral->id)
                ->where('reference_type', 'Deposit')
                ->sum('amount');

            $withdrawals = Payment::where('reference', $collateral->id)
                ->where('reference_type', 'Withdrawal')
                ->sum('amount');

            $journalWithdrawals = Journal::where('customer_id', $customer->id)
                ->whereIn('reference_type', ['sales_invoice_payment', 'cash_sale_payment'])
                ->join('journal_items', 'journals.id', '=', 'journal_items.journal_id')
                ->where('journal_items.chart_account_id', 28)
                ->where('journal_items.nature', 'debit')
                ->sum('journal_items.amount');

            $total += (float) $deposits - ((float) $withdrawals + (float) $journalWithdrawals);
        }

        return $total;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildCropSalesDashboard(Customer $customer): array
    {
        if (! Schema::hasTable('customer_storage_sales')) {
            return [];
        }

        $companyId = (int) Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $storedItemIds = CustomerStorageBalance::query()
            ->where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->pluck('inventory_item_id');

        $soldItemIds = CustomerStorageSale::query()
            ->where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->pluck('inventory_item_id');

        $itemIds = $storedItemIds->merge($soldItemIds)->unique()->values();

        if ($itemIds->isEmpty()) {
            return [];
        }

        $salesTotals = CustomerStorageSale::query()
            ->where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereIn('inventory_item_id', $itemIds)
            ->selectRaw('inventory_item_id, SUM(quantity) as total_quantity, SUM(total) as total_sales')
            ->groupBy('inventory_item_id')
            ->get()
            ->keyBy('inventory_item_id');

        return Item::query()
            ->whereIn('id', $itemIds)
            ->orderBy('name')
            ->get()
            ->map(function ($item) use ($salesTotals) {
                $row = $salesTotals->get($item->id);

                return [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'item_code' => $item->code,
                    'unit' => $item->unit_of_measure,
                    'total_quantity_sold' => (float) ($row->total_quantity ?? 0),
                    'total_sales' => (float) ($row->total_sales ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildStorageBalances(Customer $customer): array
    {
        if (! Schema::hasTable('customer_storage_balances')) {
            return [];
        }

        $companyId = (int) Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        return CustomerStorageBalance::query()
            ->with('item')
            ->where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('quantity_on_hand', '>', 0)
            ->orderBy('inventory_item_id')
            ->get()
            ->map(function ($row) {
                $item = $row->item;
                $quantity = (float) $row->quantity_on_hand;

                return [
                    'item_name' => $item->name ?? '—',
                    'item_code' => $item->code ?? '—',
                    'quantity_display' => $this->formatStorageQuantity($quantity, $item),
                    'package_display' => $this->formatStoragePackage($quantity, $item),
                ];
            })
            ->values()
            ->all();
    }

    private function formatStorageQuantity(float $quantity, ?Item $item): string
    {
        $unit = $item?->unit_of_measure;
        $formattedQty = $this->formatStorageNumber($quantity);

        return $unit ? $formattedQty.' '.$unit : $formattedQty;
    }

    private function formatStoragePackage(float $quantity, ?Item $item): string
    {
        if (! $item) {
            return '—';
        }

        $packageQuantity = (float) ($item->package_quantity ?? 0);
        $packageName = trim((string) ($item->package_name ?? ''));

        if ($packageQuantity <= 0 || $packageName === '') {
            return '—';
        }

        $count = $quantity / $packageQuantity;

        return $this->formatStorageNumber($count).' '.$packageName;
    }

    private function formatStorageNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ','), '0'), '.');
    }
}
