<?php

namespace App\Services\Inventory;

use App\Models\Inventory\CustomerStorageGharama;
use App\Models\Inventory\CustomerStorageMapato;
use App\Models\Inventory\CustomerStorageReceipt;
use App\Models\Inventory\CustomerStorageSale;
use App\Models\Inventory\Item;
use App\Models\Inventory\PermanentStorageGharama;
use App\Models\Inventory\PermanentStorageMapato;
use App\Models\Inventory\PermanentStorageReceipt;
use App\Models\Inventory\PermanentStorageSale;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class StorageDailyTransactionsService
{
    /**
     * @return array{
     *     mapato_mauzo: Collection<int, array<string, mixed>>,
     *     gharama: Collection<int, array<string, mixed>>,
     *     stoo_ingizo: Collection<int, array<string, mixed>>,
     *     totals: array<string, float|int>
     * }
     */
    public function build(int $companyId, ?int $branchId, string $date): array
    {
        $mapatoMauzo = $this->mapatoMauzoLines($companyId, $branchId, $date);
        $gharama = $this->gharamaLines($companyId, $branchId, $date);
        $stooIngizo = $this->stooIngizoLines($companyId, $branchId, $date);

        return [
            'mapato_mauzo' => $mapatoMauzo,
            'gharama' => $gharama,
            'stoo_ingizo' => $stooIngizo,
            'totals' => [
                'mapato_mauzo' => (float) $mapatoMauzo->sum('amount'),
                'gharama' => (float) $gharama->sum('amount'),
                'stoo_ingizo_count' => $stooIngizo->count(),
                'stoo_ingizo_quantity' => (float) $stooIngizo->sum('quantity'),
            ],
        ];
    }

    private function mapatoMauzoLines(int $companyId, ?int $branchId, string $date): Collection
    {
        $lines = collect();

        if (Schema::hasTable('permanent_storage_mapato')) {
            PermanentStorageMapato::query()
                ->with(['customer', 'item'])
                ->where('company_id', $companyId)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereDate('entry_date', $date)
                ->orderBy('id')
                ->get()
                ->each(function ($row) use ($lines) {
                    $lines->push($this->moneyLine(
                        stooLabel: 'Stoo ya Kudumu',
                        subtype: 'Mapato',
                        customerName: $row->customer?->name,
                        item: $row->item,
                        description: $row->sababu ?: 'Mapato',
                        amount: (float) $row->kiasi,
                        sort: 'A-perm-mapato-'.$row->id,
                    ));
                });
        }

        if (Schema::hasTable('customer_storage_mapato')) {
            CustomerStorageMapato::query()
                ->with(['customer', 'item'])
                ->where('company_id', $companyId)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereDate('entry_date', $date)
                ->orderBy('id')
                ->get()
                ->each(function ($row) use ($lines) {
                    $desc = $row->sababu ?: 'Mapato';
                    if ($row->mazunguko) {
                        $desc .= ' (Mz. '.$row->mazunguko.')';
                    }

                    $lines->push($this->moneyLine(
                        stooLabel: 'Stoo ya Muda Mfupi (Wateja)',
                        subtype: 'Mapato',
                        customerName: $row->customer?->name,
                        item: $row->item,
                        description: $desc,
                        amount: (float) $row->kiasi,
                        sort: 'A-cust-mapato-'.$row->id,
                    ));
                });
        }

        if (Schema::hasTable('permanent_storage_sales')) {
            PermanentStorageSale::query()
                ->with(['customer', 'item'])
                ->where('company_id', $companyId)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereDate('created_at', $date)
                ->orderBy('id')
                ->get()
                ->each(function ($row) use ($lines) {
                    $lines->push($this->moneyLine(
                        stooLabel: 'Stoo ya Kudumu',
                        subtype: 'Mauzo',
                        customerName: $row->customer?->name,
                        item: $row->item,
                        description: 'Mauzo ya zao (idadi '.$this->formatNumber((float) $row->quantity).')',
                        amount: (float) $row->total,
                        sort: 'B-perm-sale-'.$row->id,
                    ));
                });
        }

        if (Schema::hasTable('customer_storage_sales')) {
            CustomerStorageSale::query()
                ->with(['customer', 'item'])
                ->where('company_id', $companyId)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereDate('created_at', $date)
                ->orderBy('id')
                ->get()
                ->each(function ($row) use ($lines) {
                    $desc = 'Mauzo ya zao (idadi '.$this->formatNumber((float) $row->quantity).')';
                    if ($row->mazunguko) {
                        $desc .= ', Mz. '.$row->mazunguko;
                    }

                    $lines->push($this->moneyLine(
                        stooLabel: 'Stoo ya Muda Mfupi (Wateja)',
                        subtype: 'Mauzo',
                        customerName: $row->customer?->name,
                        item: $row->item,
                        description: $desc,
                        amount: (float) $row->total,
                        sort: 'B-cust-sale-'.$row->id,
                    ));
                });
        }

        return $lines->sortBy('sort')->values();
    }

    private function gharamaLines(int $companyId, ?int $branchId, string $date): Collection
    {
        $lines = collect();

        if (Schema::hasTable('permanent_storage_gharama')) {
            PermanentStorageGharama::query()
                ->with(['customer', 'item'])
                ->where('company_id', $companyId)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereDate('entry_date', $date)
                ->orderBy('id')
                ->get()
                ->each(function ($row) use ($lines) {
                    $lines->push($this->moneyLine(
                        stooLabel: 'Stoo ya Kudumu',
                        subtype: 'Gharama',
                        customerName: $row->customer?->name,
                        item: $row->item,
                        description: $row->sababu ?: 'Gharama',
                        amount: (float) $row->kiasi,
                        sort: 'C-perm-gharama-'.$row->id,
                    ));
                });
        }

        if (Schema::hasTable('customer_storage_gharama')) {
            CustomerStorageGharama::query()
                ->with(['customer', 'item'])
                ->where('company_id', $companyId)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereDate('entry_date', $date)
                ->orderBy('id')
                ->get()
                ->each(function ($row) use ($lines) {
                    $desc = $row->sababu ?: 'Gharama';
                    if ($row->mazunguko) {
                        $desc .= ' (Mz. '.$row->mazunguko.')';
                    }

                    $lines->push($this->moneyLine(
                        stooLabel: 'Stoo ya Muda Mfupi (Wateja)',
                        subtype: 'Gharama',
                        customerName: $row->customer?->name,
                        item: $row->item,
                        description: $desc,
                        amount: (float) $row->kiasi,
                        sort: 'C-cust-gharama-'.$row->id,
                    ));
                });
        }

        return $lines->sortBy('sort')->values();
    }

    private function stooIngizoLines(int $companyId, ?int $branchId, string $date): Collection
    {
        $lines = collect();

        if (Schema::hasTable('permanent_storage_receipts')) {
            PermanentStorageReceipt::query()
                ->with(['customer', 'item'])
                ->where('company_id', $companyId)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereDate('received_date', $date)
                ->orderBy('id')
                ->get()
                ->each(function ($row) use ($lines) {
                    $lines->push([
                        'stoo_label' => 'Stoo ya Kudumu',
                        'customer_name' => $row->customer?->name ?? '—',
                        'item_name' => $this->itemLabel($row->item),
                        'description' => $row->notes ?: 'Pokea zao',
                        'quantity' => (float) $row->quantity,
                        'quantity_display' => $this->formatQuantity((float) $row->quantity, $row->item),
                        'sort' => 'D-perm-receipt-'.$row->id,
                    ]);
                });
        }

        if (Schema::hasTable('customer_storage_receipts')) {
            CustomerStorageReceipt::query()
                ->with(['customer', 'item'])
                ->where('company_id', $companyId)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereDate('received_date', $date)
                ->orderBy('id')
                ->get()
                ->each(function ($row) use ($lines) {
                    $desc = $row->notes ?: 'Pokea zao';
                    if ($row->mazunguko) {
                        $desc .= ' (Mz. '.$row->mazunguko.')';
                    }

                    $lines->push([
                        'stoo_label' => 'Stoo ya Muda Mfupi (Wateja)',
                        'customer_name' => $row->customer?->name ?? '—',
                        'item_name' => $this->itemLabel($row->item),
                        'description' => $desc,
                        'quantity' => (float) $row->quantity,
                        'quantity_display' => $this->formatQuantity((float) $row->quantity, $row->item),
                        'sort' => 'D-cust-receipt-'.$row->id,
                    ]);
                });
        }

        return $lines->sortBy('sort')->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function moneyLine(
        string $stooLabel,
        string $subtype,
        ?string $customerName,
        ?Item $item,
        string $description,
        float $amount,
        string $sort,
    ): array {
        return [
            'stoo_label' => $stooLabel,
            'subtype' => $subtype,
            'customer_name' => $customerName ?? '—',
            'item_name' => $this->itemLabel($item),
            'description' => $description,
            'amount' => $amount,
            'sort' => $sort,
        ];
    }

    private function itemLabel(?Item $item): string
    {
        if (! $item) {
            return '—';
        }

        $code = trim((string) ($item->code ?? ''));

        return $code !== '' ? $item->name.' ('.$code.')' : $item->name;
    }

    public function formatQuantity(float $quantity, ?Item $item): string
    {
        $formatted = $this->formatNumber($quantity);
        $unit = trim((string) ($item->unit_of_measure ?? ''));

        return $unit !== '' ? $formatted.' '.$unit : $formatted;
    }

    public function formatNumber(float $value): string
    {
        if (fmod($value, 1.0) === 0.0) {
            return number_format($value, 0, '.', ',');
        }

        return rtrim(rtrim(number_format($value, 2, '.', ','), '0'), '.');
    }

    public function parseDate(?string $date): string
    {
        try {
            return Carbon::parse($date ?? now())->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }
}
