@extends('layouts.main')

@section('title', 'Currency Summary Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => route('reports.currency.index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Currency Summary', 'url' => '#', 'icon' => 'bx bx-bar-chart-alt-2']
        ]" />
        <h6 class="mb-0 text-uppercase">CURRENCY SUMMARY REPORT</h6>
        <hr />

        <!-- Filters -->
        <form method="GET" action="{{ route('reports.currency.summary') }}" class="mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date', now()->subDays(30)->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date', now()->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Currency (optional)</label>
                    <select name="currency" class="form-select">
                        <option value="">All</option>
                        @php $allCurrencies = app(\App\Services\ExchangeRateService::class)->getSupportedCurrencies(); @endphp
                        @foreach($allCurrencies as $code => $label)
                            <option value="{{ $code }}" {{ request('currency')===$code ? 'selected' : '' }}>{{ $code }} - {{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Report Scope</label>
                    <select name="report_type" class="form-select">
                        <option value="all" {{ request('report_type','all')==='all' ? 'selected' : '' }}>All (Invoices + Cash + POS)</option>
                        <option value="invoices" {{ request('report_type')==='invoices' ? 'selected' : '' }}>Invoices Only</option>
                        <option value="cash_sales" {{ request('report_type')==='cash_sales' ? 'selected' : '' }}>Cash Sales Only</option>
                        <option value="pos_sales" {{ request('report_type')==='pos_sales' ? 'selected' : '' }}>POS Sales Only</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100"><i class="bx bx-refresh me-1"></i>Run</button>
                </div>
            </div>
        </form>

        <!-- Results -->
        <div class="card">
            <div class="card-body">
                @if(empty($data))
                    <div class="alert alert-warning mb-0">No data found for the selected filters.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Currency</th>
                                    <th class="text-end">Invoices - Txns</th>
                                    <th class="text-end">Invoices - Amount</th>
                                    <th class="text-end">Cash Sales - Txns</th>
                                    <th class="text-end">Cash Sales - Amount</th>
                                    <th class="text-end">POS Sales - Txns</th>
                                    <th class="text-end">POS Sales - Amount</th>
                                    <th class="text-end">Total Amount</th>
                                    <th class="text-end">Avg Exch. Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $grandTotal = 0; @endphp
                                @foreach($data as $currency => $blocks)
                                    @php
                                        $invTx = (int)($blocks['invoices']->total_transactions ?? 0);
                                        $invAmt = (float)($blocks['invoices']->total_amount ?? 0);
                                        $cashTx = (int)($blocks['cash_sales']->total_transactions ?? 0);
                                        $cashAmt = (float)($blocks['cash_sales']->total_amount ?? 0);
                                        $posTx = (int)($blocks['pos_sales']->total_transactions ?? 0);
                                        $posAmt = (float)($blocks['pos_sales']->total_amount ?? 0);
                                        $totalAmt = $invAmt + $cashAmt + $posAmt;
                                        $grandTotal += $totalAmt;
                                        $avgRate = collect([
                                            $blocks['invoices']->avg_exchange_rate ?? null,
                                            $blocks['cash_sales']->avg_exchange_rate ?? null,
                                            $blocks['pos_sales']->avg_exchange_rate ?? null
                                        ])->filter(function($v){ return $v > 0; })->avg();
                                    @endphp
                                    <tr>
                                        <td class="fw-bold">{{ $currency }}</td>
                                        <td class="text-end">{{ number_format($invTx) }}</td>
                                        <td class="text-end">{{ number_format($invAmt, 2) }}</td>
                                        <td class="text-end">{{ number_format($cashTx) }}</td>
                                        <td class="text-end">{{ number_format($cashAmt, 2) }}</td>
                                        <td class="text-end">{{ number_format($posTx) }}</td>
                                        <td class="text-end">{{ number_format($posAmt, 2) }}</td>
                                        <td class="text-end fw-bold">{{ number_format($totalAmt, 2) }}</td>
                                        <td class="text-end">{{ $avgRate ? number_format($avgRate, 6) : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="table-info">
                                    <th colspan="7" class="text-end">Grand Total</th>
                                    <th class="text-end">{{ number_format($grandTotal, 2) }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection 