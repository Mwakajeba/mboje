@extends('layouts.main')

@section('title', 'Sales by Branch Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-bar-chart-alt-2'],
                ['label' => 'Sales Reports', 'url' => route('sales.reports.index'), 'icon' => 'bx bx-trending-up'],
                ['label' => 'Sales by Branch', 'url' => '#', 'icon' => 'bx bx-buildings']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-buildings me-2"></i>Sales by Branch Report
                            </h4>
                            <div class="btn-group">
                                <a href="{{ route('sales.reports.sales-by-branch.export.pdf', request()->query()) }}" 
                                   class="btn btn-danger">
                                    <i class="bx bxs-file-pdf me-1"></i>Export PDF
                                </a>
                                <a href="{{ route('sales.reports.sales-by-branch.export.excel', request()->query()) }}" 
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

                        <!-- Summary Cards -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">Total Sales</h5>
                                        <h3 class="mb-0">{{ number_format($totalSales, 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Total Customers Served</h5>
                                        <h3 class="mb-0">{{ number_format($branchSales->sum('customers_served')) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Total Invoices</h5>
                                        <h3 class="mb-0">{{ number_format($branchSales->sum('invoice_count')) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Avg Invoice Value</h5>
                                        <h3 class="mb-0">{{ number_format($branchSales->avg('avg_invoice_value'), 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Branch</th>
                                        <th class="text-end">No. of Invoices</th>
                                        <th class="text-end">Customers Served</th>
                                        <th class="text-end">Total Sales (TZS)</th>
                                        <th class="text-end">Cost (TZS)</th>
                                        <th class="text-end">Gross Profit (TZS)</th>
                                        <th class="text-end">Profit Margin %</th>
                                        <th class="text-end">% Contribution</th>
                                        <th>Performance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($branchSales as $index => $branch)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <div class="fw-bold">{{ $branch->branch->name ?? 'Unknown Branch' }}</div>
                                                <small class="text-muted">{{ $branch->branch->address ?? 'N/A' }}</small>
                                            </td>
                                            <td class="text-end">{{ number_format($branch->invoice_count) }}</td>
                                            <td class="text-end">{{ number_format($branch->customers_served) }}</td>
                                            <td class="text-end">{{ number_format($branch->total_sales, 2) }}</td>
                                            <td class="text-end">{{ number_format($branch->total_cost ?? 0, 2) }}</td>
                                            <td class="text-end">{{ number_format($branch->gross_profit ?? 0, 2) }}</td>
                                            <td class="text-end">{{ number_format($branch->margin_percentage ?? 0, 1) }}%</td>
                                            <td class="text-end">
                                                <span class="badge bg-{{ $branch->contribution_percentage >= 30 ? 'success' : ($branch->contribution_percentage >= 15 ? 'warning' : 'info') }}">
                                                    {{ number_format($branch->contribution_percentage, 1) }}%
                                                </span>
                                            </td>
                                            <td>
                                                @if($branch->contribution_percentage >= 30)
                                                    <span class="badge bg-success">High Performer</span>
                                                @elseif($branch->contribution_percentage >= 15)
                                                    <span class="badge bg-warning">Medium Performer</span>
                                                @else
                                                    <span class="badge bg-info">Low Performer</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">No sales data available for the selected period</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="2">Total</th>
                                        <th class="text-end">{{ number_format($branchSales->sum('invoice_count')) }}</th>
                                        <th class="text-end">{{ number_format($branchSales->sum('customers_served')) }}</th>
                                        <th class="text-end">{{ number_format($totalSales, 2) }}</th>
                                        <th class="text-end">{{ number_format($branchSales->sum('total_cost'), 2) }}</th>
                                        <th class="text-end">{{ number_format($branchSales->sum('gross_profit'), 2) }}</th>
                                        <th class="text-end">{{ number_format($branchSales->avg('margin_percentage'), 1) }}%</th>
                                        <th class="text-end">100.0%</th>
                                        <th>-</th>
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
