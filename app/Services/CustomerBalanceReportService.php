<?php

namespace App\Services;

use App\Models\CashCollateral;
use App\Models\Customer;
use App\Models\Inventory\CustomerStorageSale;
use App\Models\Journal;
use App\Models\Payment;
use App\Models\Receipt;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CustomerBalanceReportService
{
    public function __construct(
        private readonly CustomerAccountSummaryService $summaryService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(Customer $customer): array
    {
        $companyId = (int) Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $mauzoLines = $this->buildMauzoLines($customer, $companyId, $branchId);
        $mikopoLines = $this->buildMikopoLines($customer);
        $malipoLines = $this->buildMalipoLines($customer);

        $totalMauzo = (float) $mauzoLines->sum('total');
        $totalMikopoGiven = (float) $mikopoLines->sum('amount');
        $totalMalipo = (float) $malipoLines->sum('amount');
        $salioMikopo = $this->summaryService->calculateMikopoTotal($customer);
        $salioMteja = $totalMauzo - $salioMikopo;

        $summary = $this->summaryService->build($customer);

        return [
            'generated_at' => now()->format('d/m/Y H:i'),
            'mauzo_lines' => $mauzoLines->values()->all(),
            'mikopo_lines' => $mikopoLines->values()->all(),
            'malipo_lines' => $malipoLines->values()->all(),
            'total_mauzo' => $totalMauzo,
            'total_mikopo_given' => $totalMikopoGiven,
            'total_malipo' => $totalMalipo,
            'salio_mikopo' => $salioMikopo,
            'salio_mteja' => $salioMteja,
            'storage_balances' => $summary['storageBalances'] ?? [],
            'crop_sales_dashboard' => $summary['cropSalesDashboard'] ?? [],
        ];
    }

    private function buildMauzoLines(Customer $customer, int $companyId, $branchId): Collection
    {
        if (! Schema::hasTable('customer_storage_sales')) {
            return collect();
        }

        return CustomerStorageSale::query()
            ->with(['item', 'withdrawal'])
            ->where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('id')
            ->get()
            ->map(function (CustomerStorageSale $sale) {
                $date = $sale->withdrawal?->withdrawn_date ?? $sale->created_at;
                $item = $sale->item;
                $unit = $item?->unit_of_measure ?? '';

                return [
                    'date' => Carbon::parse($date)->format('d/m/Y'),
                    'item_name' => $item->name ?? 'Zao',
                    'item_code' => $item->code ?? '—',
                    'quantity' => (float) $sale->quantity,
                    'quantity_display' => $this->formatQty((float) $sale->quantity).($unit ? ' '.$unit : ''),
                    'price' => (float) $sale->price,
                    'total' => (float) $sale->total,
                ];
            });
    }

    private function buildMikopoLines(Customer $customer): Collection
    {
        $collateralIds = CashCollateral::query()
            ->where('customer_id', $customer->id)
            ->pluck('id');

        if ($collateralIds->isEmpty()) {
            return collect();
        }

        $loanTypeOptions = CashCollateral::loanTypeOptions();

        return Receipt::query()
            ->with('user')
            ->whereIn('reference', $collateralIds)
            ->where('reference_type', 'Deposit')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->map(function (Receipt $receipt) use ($loanTypeOptions) {
                $typeLabel = $loanTypeOptions[$receipt->loan_type] ?? ($receipt->loan_type ?: 'Mkopo');

                return [
                    'date' => Carbon::parse($receipt->date)->format('d/m/Y'),
                    'loan_type' => $typeLabel,
                    'description' => trim((string) ($receipt->description ?? '')) ?: '—',
                    'amount' => (float) $receipt->amount,
                    'entered_by' => $receipt->user->name ?? '—',
                ];
            });
    }

    private function buildMalipoLines(Customer $customer): Collection
    {
        $lines = collect();
        $collateralIds = CashCollateral::query()
            ->where('customer_id', $customer->id)
            ->pluck('id');

        if ($collateralIds->isNotEmpty()) {
            $payments = Payment::query()
                ->with('user')
                ->whereIn('reference', $collateralIds)
                ->where('reference_type', 'Withdrawal')
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->get();

            foreach ($payments as $payment) {
                $lines->push([
                    'sort_ts' => Carbon::parse($payment->date)->timestamp,
                    'date' => Carbon::parse($payment->date)->format('d/m/Y'),
                    'description' => trim((string) ($payment->description ?? '')) ?: 'Malipo ya mkopo',
                    'amount' => (float) $payment->amount,
                    'entered_by' => $payment->user->name ?? '—',
                ]);
            }
        }

        $journals = Journal::query()
            ->where('customer_id', $customer->id)
            ->whereIn('reference_type', ['sales_invoice_payment', 'cash_sale_payment'])
            ->join('journal_items', 'journals.id', '=', 'journal_items.journal_id')
            ->where('journal_items.chart_account_id', 28)
            ->where('journal_items.nature', 'debit')
            ->selectRaw('journals.id, journals.date, journals.description, SUM(journal_items.amount) as debit_amount')
            ->groupBy('journals.id', 'journals.date', 'journals.description')
            ->orderByDesc('journals.date')
            ->orderByDesc('journals.id')
            ->get();

        foreach ($journals as $journal) {
            $amount = (float) $journal->debit_amount;

            if ($amount <= 0) {
                continue;
            }

            $lines->push([
                'sort_ts' => Carbon::parse($journal->date)->timestamp,
                'date' => Carbon::parse($journal->date)->format('d/m/Y'),
                'description' => trim((string) ($journal->description ?? '')) ?: 'Malipo kupitia ankara',
                'amount' => $amount,
                'entered_by' => '—',
            ]);
        }

        return $lines->sortByDesc('sort_ts')->map(function ($row) {
            unset($row['sort_ts']);

            return $row;
        })->values();
    }

    private function formatQty(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ','), '0'), '.');
    }
}
