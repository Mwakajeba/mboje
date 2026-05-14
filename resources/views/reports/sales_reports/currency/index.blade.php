@extends('layouts.main')

@section('title', 'Currency Reports')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => '#', 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Currency Reports', 'url' => '#', 'icon' => 'bx bx-dollar-circle']
        ]" />
        
        <h6 class="mb-0 text-uppercase">CURRENCY REPORTS</h6>
        <hr />

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bx bx-bar-chart-alt-2 fs-1 text-primary mb-3"></i>
                        <h5>Currency Summary Report</h5>
                        <p class="text-muted">View sales summary by currency with exchange rate analysis</p>
                        <a href="{{ route('reports.currency.summary', ['start_date' => now()->subDays(30)->format('Y-m-d'), 'end_date' => now()->format('Y-m-d'), 'report_type' => 'all']) }}" class="btn btn-primary">
                            <i class="bx bx-right-arrow-alt"></i> Generate Report
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bx bx-line-chart fs-1 text-success mb-3"></i>
                        <h5>Currency Comparison Report</h5>
                        <p class="text-muted">Compare sales performance across different currencies</p>
                        <a href="{{ route('reports.currency.comparison', ['start_date' => now()->subDays(30)->format('Y-m-d'), 'end_date' => now()->format('Y-m-d'), 'currencies' => ['TZS','USD','KES']]) }}" class="btn btn-success">
                            <i class="bx bx-right-arrow-alt"></i> Generate Report
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="bx bx-trending-up fs-1 text-info mb-3"></i>
                        <h5>Exchange Rate Analysis</h5>
                        <p class="text-muted">Analyze exchange rate trends and their impact on sales</p>
                        <a href="{{ route('reports.currency.exchange-rate-analysis', ['start_date' => now()->subDays(30)->format('Y-m-d'), 'end_date' => now()->format('Y-m-d'), 'from_currency' => 'USD', 'to_currency' => 'TZS']) }}" class="btn btn-info">
                            <i class="bx bx-right-arrow-alt"></i> Generate Report
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Supported Currencies</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($currencies as $code => $name)
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center p-2 border rounded">
                                    <div class="me-3">
                                        <i class="bx bx-dollar-circle fs-4 text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $code }}</h6>
                                        <small class="text-muted">{{ $name }}</small>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 