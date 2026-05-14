@extends('layouts.main')

@section('title', 'Exchange Rate Analysis')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => route('reports.currency.index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Exchange Rate Analysis', 'url' => '#', 'icon' => 'bx bx-trending-up']
        ]" />
        <h6 class="mb-0 text-uppercase">EXCHANGE RATE ANALYSIS</h6>
        <hr />

        <!-- Filters -->
        <form method="GET" action="{{ route('reports.currency.exchange-rate-analysis') }}" class="mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date', now()->subDays(30)->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date', now()->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From</label>
                    @php $allCurrencies = app(\App\Services\ExchangeRateService::class)->getSupportedCurrencies(); @endphp
                    <select name="from_currency" class="form-select">
                        @foreach($allCurrencies as $code => $label)
                            <option value="{{ $code }}" {{ request('from_currency','USD')===$code ? 'selected' : '' }}>{{ $code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <select name="to_currency" class="form-select">
                        @foreach($allCurrencies as $code => $label)
                            <option value="{{ $code }}" {{ request('to_currency','TZS')===$code ? 'selected' : '' }}>{{ $code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bx bx-refresh me-1"></i>Run</button>
                </div>
            </div>
        </form>

        <!-- Results -->
        <div class="row">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">Rate History ({{ $fromCurrency ?? request('from_currency','USD') }} → {{ $toCurrency ?? request('to_currency','TZS') }})</div>
                    <div class="card-body">
                        @if(empty($data['history'] ?? []))
                            <div class="alert alert-warning mb-0">No history available.</div>
                        @else
                            <div class="table-responsive" style="max-height: 420px; overflow:auto;">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th class="text-end">Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($data['history'] as $date => $rate)
                                            <tr>
                                                <td>{{ $date }}</td>
                                                <td class="text-end">{{ number_format($rate, 6) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">Sales Impact (Invoices in {{ $fromCurrency ?? request('from_currency','USD') }})</div>
                    <div class="card-body">
                        @if(empty($data['sales_data'] ?? []))
                            <div class="alert alert-warning mb-0">No sales data in selected currency.</div>
                        @else
                            <div class="table-responsive" style="max-height: 420px; overflow:auto;">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th class="text-end">Amount ({{ $fromCurrency ?? request('from_currency','USD') }})</th>
                                            <th class="text-end">Rate</th>
                                            <th class="text-end">TZS Equivalent</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($data['sales_data'] as $row)
                                            <tr>
                                                <td>{{ optional($row->invoice_date)->format('Y-m-d') ?? $row->invoice_date }}</td>
                                                <td class="text-end">{{ number_format($row->total_amount, 2) }}</td>
                                                <td class="text-end">{{ number_format($row->exchange_rate, 6) }}</td>
                                                <td class="text-end">{{ number_format($row->tzs_equivalent, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection