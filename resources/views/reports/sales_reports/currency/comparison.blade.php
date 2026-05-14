@extends('layouts.main')

@section('title', 'Currency Comparison Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => route('reports.currency.index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Currency Comparison', 'url' => '#', 'icon' => 'bx bx-line-chart']
        ]" />
        <h6 class="mb-0 text-uppercase">CURRENCY COMPARISON REPORT</h6>
        <hr />

        <!-- Filters -->
        <form method="GET" action="{{ route('reports.currency.comparison') }}" class="mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date', now()->subDays(30)->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date', now()->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Currencies (pick ≥ 2)</label>
                    @php $allCurrencies = app(\App\Services\ExchangeRateService::class)->getSupportedCurrencies(); @endphp
                    <select name="currencies[]" class="form-select" multiple size="4">
                        @foreach($allCurrencies as $code => $label)
                            <option value="{{ $code }}" {{ collect(request('currencies', ['TZS','USD','KES']))->contains($code) ? 'selected' : '' }}>{{ $code }} - {{ $label }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Hold Ctrl/Command to select multiple</small>
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
                                    <th class="text-end">Date</th>
                                    <th class="text-end">Transactions</th>
                                    <th class="text-end">Total Amount</th>
                                    <th class="text-end">Avg Exchange Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data as $currency => $rows)
                                    @foreach($rows['invoices'] as $row)
                                        <tr>
                                            <td class="fw-bold">{{ $currency }} (Invoices)</td>
                                            <td class="text-end">{{ $row->date }}</td>
                                            <td class="text-end">{{ number_format($row->transactions) }}</td>
                                            <td class="text-end">{{ number_format($row->total_amount, 2) }}</td>
                                            <td class="text-end">{{ $row->avg_rate ? number_format($row->avg_rate, 6) : '-' }}</td>
                                        </tr>
                                    @endforeach
                                    @foreach($rows['cash_sales'] as $row)
                                        <tr>
                                            <td class="fw-bold">{{ $currency }} (Cash)</td>
                                            <td class="text-end">{{ $row->date }}</td>
                                            <td class="text-end">{{ number_format($row->transactions) }}</td>
                                            <td class="text-end">{{ number_format($row->total_amount, 2) }}</td>
                                            <td class="text-end">{{ $row->avg_rate ? number_format($row->avg_rate, 6) : '-' }}</td>
                                        </tr>
                                    @endforeach
                                    @foreach($rows['pos_sales'] as $row)
                                        <tr>
                                            <td class="fw-bold">{{ $currency }} (POS)</td>
                                            <td class="text-end">{{ $row->date }}</td>
                                            <td class="text-end">{{ number_format($row->transactions) }}</td>
                                            <td class="text-end">{{ number_format($row->total_amount, 2) }}</td>
                                            <td class="text-end">{{ $row->avg_rate ? number_format($row->avg_rate, 6) : '-' }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection