@extends('layouts.main')

@section('title', 'Sales Summary Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-bar-chart-alt-2'],
                ['label' => 'Sales Reports', 'url' => route('sales.reports.index'), 'icon' => 'bx bx-trending-up'],
                ['label' => 'Sales Summary', 'url' => '#', 'icon' => 'bx bx-bar-chart']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-bar-chart me-2"></i>Sales Summary Report
                            </h4>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-2">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" name="date_from" value="{{ \Carbon\Carbon::parse($dateFrom)->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" name="date_to" value="{{ \Carbon\Carbon::parse($dateTo)->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Branch</label>
                                <select class="form-select" name="branch_id">
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ ($branchId == $branch->id || ($branchId == 'all' && $branch->id == 'all')) ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Customer</label>
                                <select class="form-select" name="customer_id">
                                    <option value="">All Customers</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ $customerId == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Group By</label>
                                <select class="form-select" name="group_by">
                                    <option value="day" {{ $groupBy == 'day' ? 'selected' : '' }}>Day</option>
                                    <option value="week" {{ $groupBy == 'week' ? 'selected' : '' }}>Week</option>
                                    <option value="month" {{ $groupBy == 'month' ? 'selected' : '' }}>Month</option>
                                    <option value="year" {{ $groupBy == 'year' ? 'selected' : '' }}>Year</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="bx bx-search me-1"></i>Filter
                                </button>
                            </div>
                        </form>

                        <!-- Export Options -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <a href="{{ route('sales.reports.sales-summary.export.pdf', request()->query()) }}" 
                                       class="btn btn-danger">
                                        <i class="bx bx-file-pdf me-1"></i>Export PDF
                                    </a>
                                    <a href="{{ route('sales.reports.sales-summary.export.excel', request()->query()) }}" 
                                       class="btn btn-success">
                                        <i class="bx bx-file me-1"></i>Export Excel
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Summary Cards -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">Total Sales</h5>
                                        <h3 class="mb-0">{{ number_format($summaryData->sum('total_sales'), 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Total Quantity</h5>
                                        <h3 class="mb-0">{{ number_format($summaryData->sum('total_quantity')) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Total Invoices</h5>
                                        <h3 class="mb-0">{{ number_format($summaryData->sum('invoice_count')) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Net Sales</h5>
                                        <h3 class="mb-0">{{ number_format($summaryData->sum('net_sales'), 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card border border-secondary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-secondary">Total Discounts</h5>
                                        <h3 class="mb-0">{{ number_format($summaryData->sum('total_discounts'), 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Avg Daily Sales</h5>
                                        <h3 class="mb-0">{{ number_format($summaryData->avg('average_daily_sales'), 2) }} TZS</h3>
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
                                        <th class="text-end">No. of Invoices</th>
                                        <th class="text-end">Quantity Sold</th>
                                        <th class="text-end">Total Sales (TZS)</th>
                                        <th class="text-end">Total Discounts</th>
                                        <th class="text-end">Net Sales</th>
                                        <th class="text-end">% Growth vs. Prev. Period</th>
                                        <th class="text-end">Average Daily Sales (TZS)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($summaryData as $data)
                                        <tr>
                                            <td>{{ $data['period_label'] }}</td>
                                            <td class="text-end">{{ number_format($data['invoice_count']) }}</td>
                                            <td class="text-end">{{ number_format($data['total_quantity']) }}</td>
                                            <td class="text-end">{{ number_format($data['total_sales'], 2) }}</td>
                                            <td class="text-end">{{ number_format($data['total_discounts'], 2) }}</td>
                                            <td class="text-end">{{ number_format($data['net_sales'], 2) }}</td>
                                            <td class="text-end">
                                                @if(!is_null($data['growth_vs_prev']))
                                                    {{ ($data['growth_vs_prev'] >= 0 ? '+' : '') . number_format($data['growth_vs_prev'], 1) }}%
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="text-end">{{ number_format($data['average_daily_sales'], 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">No data available for the selected period</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>Total</th>
                                        <th class="text-end">{{ number_format($summaryData->sum('invoice_count')) }}</th>
                                        <th class="text-end">{{ number_format($summaryData->sum('total_quantity')) }}</th>
                                        <th class="text-end">{{ number_format($summaryData->sum('total_sales'), 2) }}</th>
                                        <th class="text-end">{{ number_format($summaryData->sum('total_discounts'), 2) }}</th>
                                        <th class="text-end">{{ number_format($summaryData->sum('net_sales'), 2) }}</th>
                                        <th class="text-end">—</th>
                                        <th class="text-end">{{ number_format($summaryData->avg('average_daily_sales'), 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
