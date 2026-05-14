@extends('layouts.main')

@section('title', 'Branch Profitability Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-bar-chart-alt-2'],
                ['label' => 'Sales Reports', 'url' => route('sales.reports.index'), 'icon' => 'bx bx-trending-up'],
                ['label' => 'Branch Profitability', 'url' => '#', 'icon' => 'bx bx-trending-up']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-trending-up me-2"></i>Branch Profitability Report
                            </h4>
                            <div class="btn-group">
                                <a href="{{ route('sales.reports.branch-profitability.export.pdf', request()->query()) }}" 
                                   class="btn btn-danger">
                                    <i class="bx bxs-file-pdf me-1"></i>Export PDF
                                </a>
                                <a href="{{ route('sales.reports.branch-profitability.export.excel', request()->query()) }}" 
                                   class="btn btn-success">
                                    <i class="bx bxs-file-excel me-1"></i>Export Excel
                                </a>
                            </div>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" name="date_from" value="{{ $dateFrom }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" name="date_to" value="{{ $dateTo }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="bx bx-search me-1"></i>Filter
                                </button>
                            </div>
                        </form>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Branch</th>
                                        <th class="text-end">Total Revenue (TZS)</th>
                                        <th class="text-end">Cost of Sales (TZS)</th>
                                        <th class="text-end">Gross Profit (TZS)</th>
                                        <th class="text-end">Operating Expenses (TZS)</th>
                                        <th class="text-end">Net Profit (TZS)</th>
                                        <th class="text-end">Profit Margin %</th>
                                        <th class="text-end">Staff Count</th>
                                        <th class="text-end">Profit per Staff (TZS)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($branchData as $branch)
                                        <tr>
                                            <td>
                                                <div class="fw-bold">{{ $branch->branch->name ?? 'Unknown Branch' }}</div>
                                                <small class="text-muted">{{ $branch->branch->address ?? 'N/A' }}</small>
                                            </td>
                                            <td class="text-end">{{ number_format($branch->total_revenue, 2) }}</td>
                                            <td class="text-end">{{ number_format($branch->cost_of_sales, 2) }}</td>
                                            <td class="text-end">
                                                <span class="fw-bold {{ $branch->gross_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ number_format($branch->gross_profit, 2) }}
                                                </span>
                                            </td>
                                            <td class="text-end">{{ number_format($branch->operating_expenses, 2) }}</td>
                                            <td class="text-end">
                                                <span class="fw-bold {{ $branch->net_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ number_format($branch->net_profit, 2) }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-{{ $branch->profit_margin_percentage >= 20 ? 'success' : ($branch->profit_margin_percentage >= 10 ? 'warning' : 'danger') }}">
                                                    {{ number_format($branch->profit_margin_percentage, 0) }}%
                                                </span>
                                            </td>
                                            <td class="text-end">{{ number_format($branch->staff_count) }}</td>
                                            <td class="text-end">{{ number_format($branch->profit_per_staff, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">No profitability data available for the selected period</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Note removed; using actual GL expenses -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
