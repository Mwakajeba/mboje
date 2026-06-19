<?php

namespace App\Services\Purchase;

use App\Models\CashCollateral;
use App\Models\Customer;
use App\Models\Inventory\CustomerStorageReceipt;
use App\Models\Inventory\CustomerStorageSale;
use App\Models\Inventory\CustomerStorageWithdrawal;
use App\Models\Inventory\Item;
use App\Models\Journal;
use App\Models\Payment;
use App\Models\Receipt;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class CustomerAccountsReportService
{
    public function build(
        Customer $customer,
        int $companyId,
        ?int $branchId,
        string $entryDate
    ): array {
        $date = Carbon::parse($entryDate)->toDateString();
        $previousDate = Carbon::parse($date)->subDay();

        $openingMauzo = $this->sumMauzoBeforeDate($customer, $companyId, $branchId, $date);
        $mauzoLines = $this->mauzoLinesOnDate($customer, $companyId, $branchId, $date);
        $mauzoTotal = (float) collect($mauzoLines)->sum('amount');
        $jumlaMauzo = $openingMauzo + $mauzoTotal;

        $openingMikopo = $this->mikopoBalanceBeforeDate($customer, $date);
        $mikopoLines = $this->mikopoLinesOnDate($customer, $date);
        $mikopoTotal = (float) collect($mikopoLines)->sum('amount');
        $malipoLines = $this->malipoLinesOnDate($customer, $date);
        $malipoTotal = (float) collect($malipoLines)->sum('amount');
        $salioMikopo = $openingMikopo + $mikopoTotal - $malipoTotal;

        $openingSalioMteja = $openingMauzo - $openingMikopo;
        $salioMteja = $jumlaMauzo - $salioMikopo;

        $stooBalances = $this->buildStooBalancesAsOfDate($customer, $companyId, $branchId, $date);
        $stooMovements = $this->buildStooMovementsOnDate($customer, $companyId, $branchId, $date);

        return [
            'entry_date' => $date,
            'entry_date_formatted' => Carbon::parse($date)->format('d/m/Y'),
            'previous_date_formatted' => $previousDate->format('d/m/Y'),
            'opening_mauzo' => $openingMauzo,
            'mauzo_lines' => $mauzoLines,
            'mauzo_total' => $mauzoTotal,
            'jumla_mauzo' => $jumlaMauzo,
            'opening_mikopo' => $openingMikopo,
            'mikopo_lines' => $mikopoLines,
            'mikopo_total' => $mikopoTotal,
            'malipo_lines' => $malipoLines,
            'malipo_total' => $malipoTotal,
            'salio_mikopo' => $salioMikopo,
            'opening_salio_mteja' => $openingSalioMteja,
            'salio_mteja' => $salioMteja,
            'stoo_balances' => $stooBalances,
            'stoo_movements' => $stooMovements,
            'customer_cash_collateral' => $customer->cashCollaterals()->first(),
        ];
    }

    public function sumMauzoBeforeDate(
        Customer $customer,
        int $companyId,
        ?int $branchId,
        string $beforeDate
    ): float {
        if (! Schema::hasTable('customer_storage_sales')) {
            return 0.0;
        }

        $query = CustomerStorageSale::query()
            ->where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $this->applySaleDateScope($query, $beforeDate, '<');

        return (float) $query->sum('total');
    }

    public function mikopoBalanceBeforeDate(Customer $customer, string $beforeDate): float
    {
        $collateralIds = $this->collateralIds($customer);

        if ($collateralIds->isEmpty()) {
            return 0.0;
        }

        $deposits = (float) Receipt::query()
            ->whereIn('reference', $collateralIds)
            ->where('reference_type', 'Deposit')
            ->whereDate('date', '<', $beforeDate)
            ->sum('amount');

        $withdrawals = (float) Payment::query()
            ->whereIn('reference', $collateralIds)
            ->where('reference_type', 'Withdrawal')
            ->whereDate('date', '<', $beforeDate)
            ->sum('amount');

        $journalDebits = $this->sumJournalLoanDebits($customer, beforeDate: $beforeDate);

        return $deposits - $withdrawals - $journalDebits;
    }

    /**
     * @return list<array{id: int, maelezo: string, amount: float}>
     */
    private function mauzoLinesOnDate(
        Customer $customer,
        int $companyId,
        ?int $branchId,
        string $date
    ): array {
        if (! Schema::hasTable('customer_storage_sales')) {
            return [];
        }

        $sales = CustomerStorageSale::query()
            ->with(['item'])
            ->where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $this->applySaleDateScope($sales, $date, '=');

        return $sales->orderBy('id')->get()->map(function (CustomerStorageSale $sale) {
            $itemName = $sale->item->name ?? 'Zao';
            $qty = $this->formatQty((float) $sale->quantity);
            $unit = $sale->item->unit_of_measure ?? '';

            return [
                'id' => $sale->id,
                'maelezo' => trim($itemName.' — idadi '.$qty.($unit ? ' '.$unit : '')),
                'amount' => (float) $sale->total,
            ];
        })->values()->all();
    }

    /**
     * @return list<array{id: int, maelezo: string, amount: float}>
     */
    private function mikopoLinesOnDate(Customer $customer, string $date): array
    {
        $collateralIds = $this->collateralIds($customer);
        $loanTypeOptions = CashCollateral::loanTypeOptions();

        if ($collateralIds->isEmpty()) {
            return [];
        }

        return Receipt::query()
            ->with('user')
            ->whereIn('reference', $collateralIds)
            ->where('reference_type', 'Deposit')
            ->whereDate('date', $date)
            ->orderBy('id')
            ->get()
            ->map(function (Receipt $receipt) use ($loanTypeOptions) {
                $typeLabel = $loanTypeOptions[$receipt->loan_type] ?? ($receipt->loan_type ?: 'Mkopo');
                $notes = trim((string) ($receipt->description ?? ''));
                $maelezo = $notes !== '' ? $typeLabel.' — '.$notes : $typeLabel;

                return [
                    'id' => $receipt->id,
                    'maelezo' => $maelezo,
                    'amount' => (float) $receipt->amount,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, maelezo: string, amount: float}>
     */
    private function malipoLinesOnDate(Customer $customer, string $date): array
    {
        $collateralIds = $this->collateralIds($customer);
        $lines = [];

        if ($collateralIds->isNotEmpty()) {
            $payments = Payment::query()
                ->whereIn('reference', $collateralIds)
                ->where('reference_type', 'Withdrawal')
                ->whereDate('date', $date)
                ->orderBy('id')
                ->get();

            foreach ($payments as $payment) {
                $notes = trim((string) ($payment->description ?? ''));
                $lines[] = [
                    'id' => $payment->id,
                    'maelezo' => $notes !== '' ? 'Malipo — '.$notes : 'Malipo ya mkopo',
                    'amount' => (float) $payment->amount,
                ];
            }
        }

        $journals = Journal::query()
            ->where('customer_id', $customer->id)
            ->whereIn('reference_type', ['sales_invoice_payment', 'cash_sale_payment'])
            ->whereDate('journals.date', $date)
            ->join('journal_items', 'journals.id', '=', 'journal_items.journal_id')
            ->where('journal_items.chart_account_id', 28)
            ->where('journal_items.nature', 'debit')
            ->selectRaw('journals.id, journals.description, SUM(journal_items.amount) as debit_amount')
            ->groupBy('journals.id', 'journals.description')
            ->orderBy('journals.id')
            ->get();

        foreach ($journals as $journal) {
            $debitAmount = (float) $journal->debit_amount;

            if ($debitAmount <= 0) {
                continue;
            }

            $notes = trim((string) ($journal->description ?? ''));
            $lines[] = [
                'id' => (int) $journal->id,
                'maelezo' => $notes !== '' ? 'Malipo (ankara) — '.$notes : 'Malipo ya mkopo (ankara)',
                'amount' => $debitAmount,
            ];
        }

        return $lines;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildStooBalancesAsOfDate(
        Customer $customer,
        int $companyId,
        ?int $branchId,
        string $asOfDate
    ): array {
        if (! Schema::hasTable('customer_storage_receipts') || ! Schema::hasTable('customer_storage_withdrawals')) {
            return [];
        }

        $receiptItemIds = CustomerStorageReceipt::query()
            ->where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('received_date', '<=', $asOfDate)
            ->pluck('inventory_item_id');

        $withdrawalItemIds = CustomerStorageWithdrawal::query()
            ->where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('withdrawn_date', '<=', $asOfDate)
            ->pluck('inventory_item_id');

        $itemIds = $receiptItemIds->merge($withdrawalItemIds)->unique()->values();

        if ($itemIds->isEmpty()) {
            return [];
        }

        $receiptTotals = CustomerStorageReceipt::query()
            ->where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('received_date', '<=', $asOfDate)
            ->whereIn('inventory_item_id', $itemIds)
            ->selectRaw('inventory_item_id, SUM(quantity) as total_qty')
            ->groupBy('inventory_item_id')
            ->get()
            ->keyBy('inventory_item_id');

        $withdrawalTotals = CustomerStorageWithdrawal::query()
            ->where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('withdrawn_date', '<=', $asOfDate)
            ->whereIn('inventory_item_id', $itemIds)
            ->selectRaw('inventory_item_id, SUM(quantity) as total_qty')
            ->groupBy('inventory_item_id')
            ->get()
            ->keyBy('inventory_item_id');

        return Item::query()
            ->whereIn('id', $itemIds)
            ->orderBy('name')
            ->get()
            ->map(function (Item $item) use ($receiptTotals, $withdrawalTotals) {
                $received = (float) ($receiptTotals->get($item->id)->total_qty ?? 0);
                $withdrawn = (float) ($withdrawalTotals->get($item->id)->total_qty ?? 0);
                $quantity = $received - $withdrawn;

                if ($quantity <= 0) {
                    return null;
                }

                return [
                    'item_name' => $item->name,
                    'item_code' => $item->code ?? '—',
                    'quantity_display' => $this->formatStorageQuantity($quantity, $item),
                    'package_display' => $this->formatStoragePackage($quantity, $item),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildStooMovementsOnDate(
        Customer $customer,
        int $companyId,
        ?int $branchId,
        string $date
    ): array {
        if (! Schema::hasTable('customer_storage_receipts') || ! Schema::hasTable('customer_storage_withdrawals')) {
            return [];
        }

        $reasonOptions = CustomerStorageWithdrawal::reasonOptions();
        $movements = [];

        $receipts = CustomerStorageReceipt::query()
            ->with('item')
            ->where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('received_date', $date)
            ->orderBy('id')
            ->get();

        foreach ($receipts as $receipt) {
            $item = $receipt->item;
            $qty = $this->formatStorageQuantity((float) $receipt->quantity, $item);
            $movements[] = [
                'type' => 'in',
                'item_name' => $item->name ?? 'Zao',
                'maelezo' => 'Uleti — '.$qty,
            ];
        }

        $withdrawals = CustomerStorageWithdrawal::query()
            ->with('item')
            ->where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('withdrawn_date', $date)
            ->orderBy('id')
            ->get();

        foreach ($withdrawals as $withdrawal) {
            $item = $withdrawal->item;
            $qty = $this->formatStorageQuantity((float) $withdrawal->quantity, $item);
            $reason = $reasonOptions[$withdrawal->reason] ?? $withdrawal->reason;
            $movements[] = [
                'type' => 'out',
                'item_name' => $item->name ?? 'Zao',
                'maelezo' => 'Utoaji ('.$reason.') — '.$qty,
            ];
        }

        return $movements;
    }

    private function sumJournalLoanDebits(
        Customer $customer,
        ?string $beforeDate = null,
        ?string $onDate = null
    ): float {
        $query = Journal::query()
            ->where('customer_id', $customer->id)
            ->whereIn('reference_type', ['sales_invoice_payment', 'cash_sale_payment'])
            ->join('journal_items', 'journals.id', '=', 'journal_items.journal_id')
            ->where('journal_items.chart_account_id', 28)
            ->where('journal_items.nature', 'debit');

        if ($beforeDate !== null) {
            $query->whereDate('journals.date', '<', $beforeDate);
        }

        if ($onDate !== null) {
            $query->whereDate('journals.date', $onDate);
        }

        return (float) $query->sum('journal_items.amount');
    }

    private function applySaleDateScope(Builder $query, string $date, string $operator): void
    {
        $query->where(function ($q) use ($date, $operator) {
            $q->whereHas('withdrawal', function ($w) use ($date, $operator) {
                if ($operator === '=') {
                    $w->whereDate('withdrawn_date', $date);
                } elseif ($operator === '<') {
                    $w->whereDate('withdrawn_date', '<', $date);
                } else {
                    $w->whereDate('withdrawn_date', '<=', $date);
                }
            })->orWhere(function ($q2) use ($date, $operator) {
                $q2->whereNull('withdrawal_id');

                if ($operator === '=') {
                    $q2->whereDate('created_at', $date);
                } elseif ($operator === '<') {
                    $q2->whereDate('created_at', '<', $date);
                } else {
                    $q2->whereDate('created_at', '<=', $date);
                }
            });
        });
    }

    private function collateralIds(Customer $customer)
    {
        return CashCollateral::query()
            ->where('customer_id', $customer->id)
            ->pluck('id');
    }

    private function formatQty(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ','), '0'), '.');
    }

    private function formatStorageQuantity(float $quantity, ?Item $item): string
    {
        $unit = $item?->unit_of_measure;
        $formattedQty = $this->formatQty($quantity);

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

        return $this->formatQty($count).' '.$packageName;
    }
}
