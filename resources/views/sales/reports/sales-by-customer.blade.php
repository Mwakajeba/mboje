@extends('layouts.main')

@section('title', 'Sales by Customer Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-bar-chart-alt-2'],
                ['label' => 'Sales Reports', 'url' => route('sales.reports.index'), 'icon' => 'bx bx-trending-up'],
                ['label' => 'Sales by Customer', 'url' => '#', 'icon' => 'bx bx-user']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-user me-2"></i>Sales by Customer Report
                            </h4>
                            <div class="btn-group">
                                <a href="{{ route('sales.reports.sales-by-customer.export.pdf', request()->query()) }}" 
                                   class="btn btn-danger">
                                    <i class="bx bxs-file-pdf me-1"></i>Export PDF
                                </a>
                                <a href="{{ route('sales.reports.sales-by-customer.export.excel', request()->query()) }}" 
                                   class="btn btn-success">
                                    <i class="bx bxs-file-excel me-1"></i>Export Excel
                                </a>
                            </div>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" name="date_from" value="{{ \Carbon\Carbon::parse($dateFrom)->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" name="date_to" value="{{ \Carbon\Carbon::parse($dateTo)->format('Y-m-d') }}">
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
                                        <h3 class="mb-0">{{ number_format($totalSales, 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Total Customers</h5>
                                        <h3 class="mb-0">{{ number_format($customerSales->count()) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Total Invoices</h5>
                                        <h3 class="mb-0">{{ number_format($customerSales->sum('invoice_count')) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Avg Invoice Value</h5>
                                        <h3 class="mb-0">{{ number_format($customerSales->avg('avg_invoice_value'), 2) }} TZS</h3>
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
                                        <th>Customer Name</th>
                                        <th>Phone</th>
                                        <th class="text-end">Total Sales (TZS)</th>
                                        <th class="text-end">Total Cost (TZS)</th>
                                        <th class="text-end">Gross Profit (TZS)</th>
                                        <th class="text-end">Invoice Count</th>
                                        <th class="text-end">Avg Invoice Value (TZS)</th>
                                        <th class="text-end">Outstanding Balance (TZS)</th>
                                        <th class="text-end">Contribution %</th>
                                        <th>First Invoice</th>
                                        <th>Last Invoice</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($customerSales as $index => $customer)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <div class="fw-bold">{{ $customer->customer->name ?? 'Unknown Customer' }}</div>
                                            </td>
                                            <td>{{ $customer->customer_phone ?? 'N/A' }}</td>
                                            <td class="text-end">{{ number_format($customer->total_sales, 2) }}</td>
                                            <td class="text-end">{{ number_format($customer->total_cost ?? 0, 2) }}</td>
                                            <td class="text-end">{{ number_format($customer->gross_profit ?? 0, 2) }}</td>
                                            <td class="text-end">{{ number_format($customer->invoice_count) }}</td>
                                            <td class="text-end">{{ number_format($customer->avg_invoice_value, 2) }}</td>
                                            <td class="text-end">
                                                <span class="{{ ($customer->outstanding_balance ?? 0) > 0 ? 'text-danger fw-bold' : 'text-success' }}">
                                                    {{ number_format($customer->outstanding_balance ?? 0, 2) }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-{{ $customer->contribution_percentage >= 10 ? 'success' : ($customer->contribution_percentage >= 5 ? 'warning' : 'info') }}">
                                                    {{ number_format($customer->contribution_percentage, 1) }}%
                                                </span>
                                            </td>
                                            <td>{{ $customer->first_invoice_date ? \Carbon\Carbon::parse($customer->first_invoice_date)->format('M d, Y') : 'N/A' }}</td>
                                            <td>{{ $customer->last_invoice_date ? \Carbon\Carbon::parse($customer->last_invoice_date)->format('M d, Y') : 'N/A' }}</td>
                                            <td>
                                                @if($customer->contribution_percentage >= 10)
                                                    <span class="badge bg-success">High Value</span>
                                                @elseif($customer->contribution_percentage >= 5)
                                                    <span class="badge bg-warning">Medium Value</span>
                                                @else
                                                    <span class="badge bg-info">Low Value</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="13" class="text-center text-muted">No sales data available for the selected period</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3">Total</th>
                                        <th class="text-end">{{ number_format($totalSales, 2) }}</th>
                                        <th class="text-end">{{ number_format($customerSales->sum('total_cost'), 2) }}</th>
                                        <th class="text-end">{{ number_format($customerSales->sum('gross_profit'), 2) }}</th>
                                        <th class="text-end">{{ number_format($customerSales->sum('invoice_count')) }}</th>
                                        <th class="text-end">{{ number_format($customerSales->avg('avg_invoice_value'), 2) }}</th>
                                        <th class="text-end">{{ number_format($customerSales->sum('outstanding_balance'), 2) }}</th>
                                        <th class="text-end">100.0%</th>
                                        <th colspan="3">-</th>
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
