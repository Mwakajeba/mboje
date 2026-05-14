@extends('layouts.main')

@section('title', 'Cashflow Forecast Details')

@push('styles')
<style>
    .summary-card {
        border-left: 4px solid;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .summary-card.border-primary {
        border-left-color: #0d6efd;
    }
    
    .summary-card.border-info {
        border-left-color: #0dcaf0;
    }
    
    .summary-card.border-success {
        border-left-color: #198754;
    }
    
    .summary-card.border-danger {
        border-left-color: #dc3545;
    }
    
    .summary-card.border-warning {
        border-left-color: #ffc107;
    }
    
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .badge-scenario {
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Management', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Cashflow Forecasting', 'url' => route('accounting.cashflow-forecasts.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => 'Forecast Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-trending-up me-2"></i>{{ $forecast->forecast_name }}</h5>
                                <small class="text-white-50">
                                    Period: {{ $forecast->start_date->format('d M Y') }} - {{ $forecast->end_date->format('d M Y') }}
                                    @if($forecast->branch)
                                        | Branch: {{ $forecast->branch->name }}
                                    @endif
                                </small>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('accounting.cashflow-forecasts.export.pdf', $forecast->encoded_id) }}" 
                                   class="btn btn-danger btn-sm">
                                    <i class="bx bx-file-pdf me-1"></i>Export PDF
                                </a>
                                <a href="{{ route('accounting.cashflow-forecasts.export.excel', $forecast->encoded_id) }}" 
                                   class="btn btn-success btn-sm">
                                    <i class="bx bx-file me-1"></i>Export Excel
                                </a>
                                <form action="{{ route('accounting.cashflow-forecasts.regenerate', $forecast->encoded_id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="button" class="btn btn-warning btn-sm regenerate-btn">
                                        <i class="bx bx-refresh me-1"></i>Regenerate
                                    </button>
                                </form>
                                <a href="{{ route('accounting.cashflow-forecasts.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="bx bx-arrow-back me-1"></i>Back
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <!-- Summary Cards -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card summary-card border-primary">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h6 class="text-muted mb-1">Scenario</h6>
                                                <h5 class="mb-0">
                                                    @php
                                                        $scenarios = [
                                                            'best_case' => ['label' => 'Best Case', 'class' => 'success'],
                                                            'base_case' => ['label' => 'Base Case', 'class' => 'info'],
                                                            'worst_case' => ['label' => 'Worst Case', 'class' => 'warning']
                                                        ];
                                                        $scenario = $scenarios[$forecast->scenario] ?? ['label' => ucfirst(str_replace('_', ' ', $forecast->scenario)), 'class' => 'secondary'];
                                                    @endphp
                                                    <span class="badge bg-{{ $scenario['class'] }} badge-scenario">{{ $scenario['label'] }}</span>
                                                </h5>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <i class="bx bx-bar-chart-alt-2 fs-1 text-primary"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card summary-card border-info">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h6 class="text-muted mb-1">Timeline</h6>
                                                <h5 class="mb-0">
                                                    <span class="badge bg-info badge-scenario">{{ ucfirst($forecast->timeline) }}</span>
                                                </h5>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <i class="bx bx-calendar fs-1 text-info"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card summary-card border-success">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h6 class="text-muted mb-1">Total Inflows</h6>
                                                <h4 class="mb-0 text-success">
                                                    <i class="bx bx-up-arrow-alt"></i>
                                                    {{ number_format($forecast->getTotalInflows(), 2) }} TZS
                                                </h4>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <i class="bx bx-trending-up fs-1 text-success"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card summary-card border-danger">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h6 class="text-muted mb-1">Total Outflows</h6>
                                                <h4 class="mb-0 text-danger">
                                                    <i class="bx bx-down-arrow-alt"></i>
                                                    {{ number_format($forecast->getTotalOutflows(), 2) }} TZS
                                                </h4>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <i class="bx bx-trending-down fs-1 text-danger"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Net Cashflow and Balance Cards -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="card summary-card border-{{ $forecast->getNetCashflow() >= 0 ? 'success' : 'danger' }}">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h6 class="text-muted mb-1">Net Cashflow</h6>
                                                <h3 class="mb-0 text-{{ $forecast->getNetCashflow() >= 0 ? 'success' : 'danger' }}">
                                                    <i class="bx {{ $forecast->getNetCashflow() >= 0 ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt' }}"></i>
                                                    {{ number_format($forecast->getNetCashflow(), 2) }} TZS
                                                </h3>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <i class="bx bx-line-chart fs-1 text-{{ $forecast->getNetCashflow() >= 0 ? 'success' : 'danger' }}"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card summary-card border-warning">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h6 class="text-muted mb-1">Starting Balance</h6>
                                                <h3 class="mb-0 text-warning">
                                                    <i class="bx bx-wallet"></i>
                                                    {{ number_format($forecast->starting_cash_balance, 2) }} TZS
                                                </h3>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <i class="bx bx-dollar-circle fs-1 text-warning"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- AI Insights Section -->
                        @if(isset($insights) && count($insights) > 0)
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-gradient-primary text-white">
                                <h6 class="mb-0"><i class="bx bx-brain me-2"></i>AI Insights & Recommendations</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    @foreach($insights as $insight)
                                    <div class="col-md-6">
                                        <div class="alert alert-{{ $insight['type'] }} alert-dismissible fade show mb-0" role="alert">
                                            <i class="bx {{ $insight['icon'] }} me-2"></i>
                                            <strong>{{ $insight['title'] }}:</strong> {{ $insight['message'] }}
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Charts Section -->
                        @if(isset($chartData))
                        <div class="row g-3 mb-4">
                            <!-- 90-Day Cash Balance Graph -->
                            <div class="col-12">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bx bx-line-chart me-2"></i>90-Day Cash Balance Forecast</h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="cashBalanceChart" style="height: 350px;"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Daily Inflow/Outflow Bars -->
                            <div class="col-12">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bx bx-bar-chart me-2"></i>Daily Inflow vs Outflow</h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="inflowOutflowChart" style="height: 300px;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Widgets Row -->
                        <div class="row g-3 mb-4">
                            <!-- Upcoming Large Payments -->
                            @if(isset($upcomingPayments) && $upcomingPayments->count() > 0)
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-warning text-dark">
                                        <h6 class="mb-0"><i class="bx bx-calendar-exclamation me-2"></i>Upcoming Large Payments</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Description</th>
                                                        <th class="text-end">Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($upcomingPayments as $payment)
                                                    <tr>
                                                        <td>{{ $payment->forecast_date->format('d M Y') }}</td>
                                                        <td>
                                                            <small>{{ Str::limit($payment->description, 40) }}</small>
                                                        </td>
                                                        <td class="text-end">
                                                            <span class="text-danger fw-bold">
                                                                {{ number_format($payment->amount, 2) }} TZS
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Overdue Receivables -->
                            @if(isset($overdueReceivables) && $overdueReceivables->count() > 0)
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0"><i class="bx bx-time-five me-2"></i>Overdue Receivables</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Due Date</th>
                                                        <th>Reference</th>
                                                        <th class="text-end">Amount</th>
                                                        <th class="text-end">Probability</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($overdueReceivables as $receivable)
                                                    <tr>
                                                        <td>{{ $receivable->forecast_date->format('d M Y') }}</td>
                                                        <td>
                                                            <small>{{ Str::limit($receivable->source_reference, 30) }}</small>
                                                        </td>
                                                        <td class="text-end">
                                                            <span class="text-success fw-bold">
                                                                {{ number_format($receivable->amount, 2) }} TZS
                                                            </span>
                                                        </td>
                                                        <td class="text-end">
                                                            <span class="badge bg-{{ $receivable->probability >= 70 ? 'success' : ($receivable->probability >= 50 ? 'warning' : 'danger') }}">
                                                                {{ number_format($receivable->probability, 0) }}%
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Risk Scoring Heatmap -->
                        @if(isset($summary) && count($summary) > 0)
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bx bx-map me-2"></i>Cash Risk Heatmap</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    @foreach($summary as $date => $data)
                                        @php
                                            $riskLevel = 'low';
                                            $riskColor = 'success';
                                            if ($data['closing_balance'] < 0) {
                                                $riskLevel = 'critical';
                                                $riskColor = 'danger';
                                            } elseif ($data['closing_balance'] < ($forecast->starting_cash_balance * 0.2)) {
                                                $riskLevel = 'high';
                                                $riskColor = 'warning';
                                            } elseif ($data['closing_balance'] < ($forecast->starting_cash_balance * 0.5)) {
                                                $riskLevel = 'medium';
                                                $riskColor = 'info';
                                            }
                                        @endphp
                                        <div class="col-auto">
                                            <div class="card border-{{ $riskColor }} mb-2" style="width: 80px; height: 60px; cursor: pointer;" 
                                                 title="{{ \Carbon\Carbon::parse($date)->format('d M Y') }}: TZS {{ number_format($data['closing_balance'], 0) }}">
                                                <div class="card-body p-2 text-center">
                                                    <small class="d-block text-muted">{{ \Carbon\Carbon::parse($date)->format('d') }}</small>
                                                    <small class="d-block fw-bold text-{{ $riskColor }}">{{ strtoupper(substr($riskLevel, 0, 1)) }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <span class="badge bg-success me-2">L</span> Low Risk
                                        <span class="badge bg-info me-2">M</span> Medium Risk
                                        <span class="badge bg-warning me-2">H</span> High Risk
                                        <span class="badge bg-danger me-2">C</span> Critical (Negative Balance)
                                    </small>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Forecast Timeline Table -->
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bx bx-calendar me-2"></i>Forecast Timeline</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped mb-0" id="forecast-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th class="text-end">Opening Balance</th>
                                                <th class="text-end">Expected Inflows</th>
                                                <th class="text-end">Expected Outflows</th>
                                                <th class="text-end">Net Change</th>
                                                <th class="text-end">Closing Balance</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($summary as $date => $data)
                                                <tr>
                                                    <td>
                                                        <strong>{{ \Carbon\Carbon::parse($date)->format('Y-m-d') }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ \Carbon\Carbon::parse($date)->format('l, d M') }}</small>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="fw-bold">
                                                            {{ number_format($data['opening_balance'] ?? $forecast->starting_cash_balance, 2) }} TZS
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="text-success fw-bold">
                                                            <i class="bx bx-up-arrow-alt"></i>
                                                            {{ number_format($data['inflows'], 2) }} TZS
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="text-danger fw-bold">
                                                            <i class="bx bx-down-arrow-alt"></i>
                                                            {{ number_format($data['outflows'], 2) }} TZS
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="fw-bold {{ $data['net'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                            {{ $data['net'] >= 0 ? '+' : '' }}{{ number_format($data['net'], 2) }} TZS
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="fw-bold {{ $data['closing_balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                            {{ number_format($data['closing_balance'], 2) }} TZS
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">{{ $data['notes'] ?? 'No transactions' }}</small>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted py-4">
                                                        <i class="bx bx-info-circle fs-1 d-block mb-2"></i>
                                                        No forecast data available
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th>Totals</th>
                                                <th class="text-end">
                                                    <small class="text-muted">Starting: {{ number_format($forecast->starting_cash_balance, 2) }} TZS</small>
                                                </th>
                                                <th class="text-end text-success">
                                                    {{ number_format($forecast->getTotalInflows(), 2) }} TZS
                                                </th>
                                                <th class="text-end text-danger">
                                                    {{ number_format($forecast->getTotalOutflows(), 2) }} TZS
                                                </th>
                                                <th class="text-end {{ $forecast->getNetCashflow() >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ $forecast->getNetCashflow() >= 0 ? '+' : '' }}{{ number_format($forecast->getNetCashflow(), 2) }} TZS
                                                </th>
                                                <th class="text-end">
                                                    @php
                                                        $endingBalance = $forecast->starting_cash_balance + $forecast->getNetCashflow();
                                                    @endphp
                                                    <span class="fw-bold {{ $endingBalance >= 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ number_format($endingBalance, 2) }} TZS
                                                    </span>
                                                </th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        @if($forecast->notes)
                            <div class="card border-info mt-4">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="bx bx-note me-2"></i>Notes</h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0">{{ $forecast->notes }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Handle regenerate with SweetAlert
    $('.regenerate-btn').on('click', function(e) {
        e.preventDefault();
        var form = $(this).closest('form');
        
        Swal.fire({
            title: 'Regenerate Forecast?',
            text: "This will delete all existing forecast items and regenerate them from source data.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, regenerate!'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // Cash Balance Chart (90-Day Forecast)
    @if(isset($chartData))
    var cashBalanceOptions = {
        series: [{
            name: 'Cash Balance',
            data: @json($chartData['balances'])
        }],
        chart: {
            type: 'line',
            height: 350,
            toolbar: {
                show: true
            },
            zoom: {
                enabled: true
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        colors: ['#0d6efd'],
        xaxis: {
            categories: @json($chartData['dates']),
            labels: {
                rotate: -45,
                rotateAlways: true
            }
        },
        yaxis: {
            labels: {
                formatter: function (val) {
                    return new Intl.NumberFormat('en-TZ', {
                        style: 'currency',
                        currency: 'TZS',
                        minimumFractionDigits: 0
                    }).format(val);
                }
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return new Intl.NumberFormat('en-TZ', {
                        style: 'currency',
                        currency: 'TZS',
                        minimumFractionDigits: 2
                    }).format(val);
                }
            }
        },
        grid: {
            borderColor: '#e7e7e7',
            row: {
                colors: ['#f3f3f3', 'transparent'],
                opacity: 0.5
            }
        },
        markers: {
            size: 4,
            hover: {
                size: 6
            }
        }
    };

    var cashBalanceChart = new ApexCharts(document.querySelector("#cashBalanceChart"), cashBalanceOptions);
    cashBalanceChart.render();

    // Inflow/Outflow Bar Chart
    var inflowOutflowOptions = {
        series: [{
            name: 'Inflows',
            data: @json($chartData['inflows'])
        }, {
            name: 'Outflows',
            data: @json($chartData['outflows'])
        }],
        chart: {
            type: 'bar',
            height: 300,
            toolbar: {
                show: true
            }
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                endingShape: 'rounded'
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            show: true,
            width: 2,
            colors: ['transparent']
        },
        colors: ['#198754', '#dc3545'],
        xaxis: {
            categories: @json($chartData['dates']),
            labels: {
                rotate: -45,
                rotateAlways: true
            }
        },
        yaxis: {
            labels: {
                formatter: function (val) {
                    return new Intl.NumberFormat('en-TZ', {
                        style: 'currency',
                        currency: 'TZS',
                        minimumFractionDigits: 0
                    }).format(val);
                }
            }
        },
        fill: {
            opacity: 1
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return new Intl.NumberFormat('en-TZ', {
                        style: 'currency',
                        currency: 'TZS',
                        minimumFractionDigits: 2
                    }).format(val);
                }
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right'
        }
    };

    var inflowOutflowChart = new ApexCharts(document.querySelector("#inflowOutflowChart"), inflowOutflowOptions);
    inflowOutflowChart.render();
    @endif
});
</script>
@endpush
@endsection
