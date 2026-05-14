@extends('layouts.main')

@section('title', 'Sales by Product Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-bar-chart-alt-2'],
                ['label' => 'Sales Reports', 'url' => route('sales.reports.index'), 'icon' => 'bx bx-trending-up'],
                ['label' => 'Sales by Product', 'url' => '#', 'icon' => 'bx bx-package']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-package me-2"></i>Sales by Product Report
                            </h4>
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

                        <!-- Export Options -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <a href="{{ route('sales.reports.sales-by-product.export.pdf', request()->query()) }}" 
                                       class="btn btn-danger">
                                        <i class="bx bx-file-pdf me-1"></i>Export PDF
                                    </a>
                                    <a href="{{ route('sales.reports.sales-by-product.export.excel', request()->query()) }}" 
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
                                        <h3 class="mb-0">{{ number_format($productSales->sum('total_revenue'), 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Total Quantity</h5>
                                        <h3 class="mb-0">{{ number_format($productSales->sum('total_quantity')) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Total Cost</h5>
                                        <h3 class="mb-0">{{ number_format($productSales->sum('total_cogs'), 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Total Gross Profit</h5>
                                        <h3 class="mb-0">{{ number_format($productSales->sum('gross_profit'), 2) }} TZS</h3>
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
                                        <th>Product</th>
                                        <th class="text-end">Quantity Sold</th>
                                        <th class="text-end">Sales Value (TZS)</th>
                                        <th class="text-end">Cost (TZS)</th>
                                        <th class="text-end">Gross Profit</th>
                                        <th class="text-end">Profit Margin %</th>
                                        <th class="text-end">% Contribution</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($productSales as $index => $product)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <div class="fw-bold">{{ $product->inventoryItem->name ?? 'Unknown Product' }}</div>
                                                <small class="text-muted">{{ $product->inventoryItem->code ?? 'N/A' }}</small>
                                            </td>
                                            <td class="text-end">{{ number_format($product->total_quantity) }}</td>
                                            <td class="text-end">{{ number_format($product->total_revenue, 2) }}</td>
                                            <td class="text-end">{{ number_format($product->total_cogs, 2) }}</td>
                                            <td class="text-end">{{ number_format($product->gross_profit, 2) }}</td>
                                            <td class="text-end">
                                                <span class="badge bg-{{ $product->profit_margin_percentage >= 20 ? 'success' : ($product->profit_margin_percentage >= 10 ? 'warning' : 'danger') }}">
                                                    {{ number_format($product->profit_margin_percentage, 1) }}%
                                                </span>
                                            </td>
                                            <td class="text-end">{{ number_format($product->contribution_percentage, 1) }}%</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">No sales data available for the selected period</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="2">Total</th>
                                        <th class="text-end">{{ number_format($productSales->sum('total_quantity')) }}</th>
                                        <th class="text-end">{{ number_format($productSales->sum('total_revenue'), 2) }}</th>
                                        <th class="text-end">{{ number_format($productSales->sum('total_cogs'), 2) }}</th>
                                        <th class="text-end">{{ number_format($productSales->sum('gross_profit'), 2) }}</th>
                                        <th class="text-end">{{ number_format($productSales->avg('profit_margin_percentage'), 1) }}%</th>
                                        <th class="text-end">100.0%</th>
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
