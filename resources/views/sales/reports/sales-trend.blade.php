@extends('layouts.main')

@section('title', 'Sales Trend Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-bar-chart-alt-2'],
                ['label' => 'Sales Reports', 'url' => route('sales.reports.index'), 'icon' => 'bx bx-trending-up'],
                ['label' => 'Sales Trend', 'url' => '#', 'icon' => 'bx bx-line-chart']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-line-chart me-2"></i>Sales Trend Report
                            </h4>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" name="date_from" value="{{ $dateFrom }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" name="date_to" value="{{ $dateTo }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Branch</label>
                                <select class="form-select" name="branch_id">
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Period</label>
                                <select class="form-select" name="period">
                                    <option value="day" {{ $period == 'day' ? 'selected' : '' }}>Daily</option>
                                    <option value="week" {{ $period == 'week' ? 'selected' : '' }}>Weekly</option>
                                    <option value="month" {{ $period == 'month' ? 'selected' : '' }}>Monthly</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="bx bx-search me-1"></i>Filter
                                </button>
                            </div>
                        </form>

                        <!-- Summary Cards -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">Total Sales</h5>
                                        <h3 class="mb-0">{{ number_format($trendData->sum('sales_amount'), 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Total Invoices</h5>
                                        <h3 class="mb-0">{{ number_format($trendData->sum('invoice_count')) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Avg Daily Sales</h5>
                                        <h3 class="mb-0">{{ number_format($trendData->avg('sales_amount'), 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Moving Average</h5>
                                        <h3 class="mb-0">{{ number_format($trendData->avg('moving_average'), 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Period</th>
                                        <th class="text-end">Sales Amount (TZS)</th>
                                        <th class="text-end">Invoice Count</th>
                                        <th class="text-end">Moving Average (TZS)</th>
                                        <th>Trend</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($trendData as $data)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($data->period)->format('d/m/Y') }}</td>
                                            <td class="text-end">{{ number_format($data->sales_amount, 2) }}</td>
                                            <td class="text-end">{{ number_format($data->invoice_count) }}</td>
                                            <td class="text-end">{{ number_format($data->moving_average, 2) }}</td>
                                            <td>
                                                @if($data->sales_amount > $data->moving_average)
                                                    <span class="badge bg-success">
                                                        <i class="bx bx-trending-up me-1"></i>Up
                                                    </span>
                                                @elseif($data->sales_amount < $data->moving_average)
                                                    <span class="badge bg-danger">
                                                        <i class="bx bx-trending-down me-1"></i>Down
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">
                                                        <i class="bx bx-minus me-1"></i>Stable
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No trend data available for the selected period</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
