@extends('layouts.main')

@section('title', 'Profitability by Product Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-bar-chart-alt-2'],
                ['label' => 'Sales Reports', 'url' => route('sales.reports.index'), 'icon' => 'bx bx-trending-up'],
                ['label' => 'Profitability by Product', 'url' => '#', 'icon' => 'bx bx-trending-up']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-trending-up me-2"></i>Profitability by Product Report
                            </h4>
                            <div class="btn-group">
                                <a href="{{ route('sales.reports.profitability-by-product.export.pdf', request()->query()) }}" 
                                   class="btn btn-danger">
                                    <i class="bx bxs-file-pdf me-1"></i>Export PDF
                                </a>
                                <a href="{{ route('sales.reports.profitability-by-product.export.excel', request()->query()) }}" 
                                   class="btn btn-success">
                                    <i class="bx bxs-file-excel me-1"></i>Export Excel
                                </a>
                            </div>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" name="date_from" value="{{ $dateFrom instanceof \Carbon\Carbon ? $dateFrom->format('Y-m-d') : $dateFrom }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" name="date_to" value="{{ $dateTo instanceof \Carbon\Carbon ? $dateTo->format('Y-m-d') : $dateTo }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Branch</label>
                                <select class="form-select" name="branch_id">
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ ($branchId == $branch->id || ($branchId == 'all' && $branch->id == 'all')) ? 'selected' : '' }}>
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
                                        <h5 class="card-title text-primary">Total Revenue</h5>
                                        <h3 class="mb-0">{{ number_format($totalRevenue, 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Total COGS</h5>
                                        <h3 class="mb-0">{{ number_format($totalCogs, 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Gross Profit</h5>
                                        <h3 class="mb-0">{{ number_format($totalGrossProfit, 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Avg Margin %</h5>
                                        <h3 class="mb-0">{{ number_format($averageMarginPercentage, 2) }}%</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Profitability Analysis -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">High Margin Products</h5>
                                        <h3 class="mb-0">{{ number_format($highMarginProducts) }}</h3>
                                        <small class="text-muted">Margin > 30%</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Medium Margin Products</h5>
                                        <h3 class="mb-0">{{ number_format($mediumMarginProducts) }}</h3>
                                        <small class="text-muted">Margin 15-30%</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border border-danger">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-danger">Low Margin Products</h5>
                                        <h3 class="mb-0">{{ number_format($lowMarginProducts) }}</h3>
                                        <small class="text-muted">Margin < 15%</small>
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
                                        <th class="text-end">Avg Selling Price (TZS)</th>
                                        <th class="text-end">Avg Cost Price (TZS)</th>
                                        <th class="text-end">Total Revenue (TZS)</th>
                                        <th class="text-end">Total COGS (TZS)</th>
                                        <th class="text-end">Gross Profit (TZS)</th>
                                        <th class="text-end">Margin %</th>
                                        <th>Performance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($profitabilityData as $index => $product)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <div class="fw-bold">{{ $product->inventoryItem->name ?? 'Unknown Product' }}</div>
                                                <small class="text-muted">{{ $product->inventoryItem->item_code ?? 'N/A' }}</small>
                                            </td>
                                            <td class="text-end">{{ number_format($product->total_quantity) }}</td>
                                            <td class="text-end">{{ number_format($product->avg_selling_price, 2) }}</td>
                                            <td class="text-end">{{ number_format($product->avg_cost_price, 2) }}</td>
                                            <td class="text-end">{{ number_format($product->total_revenue, 2) }}</td>
                                            <td class="text-end">{{ number_format($product->total_cogs, 2) }}</td>
                                            <td class="text-end">
                                                <span class="fw-bold {{ $product->gross_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ number_format($product->gross_profit, 2) }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-{{ $product->margin_percentage >= 30 ? 'success' : ($product->margin_percentage >= 15 ? 'warning' : 'danger') }}">
                                                    {{ number_format($product->margin_percentage, 1) }}%
                                                </span>
                                            </td>
                                            <td>
                                                @if($product->margin_percentage >= 30)
                                                    <span class="badge bg-success">High Margin</span>
                                                @elseif($product->margin_percentage >= 15)
                                                    <span class="badge bg-warning">Medium Margin</span>
                                                @else
                                                    <span class="badge bg-danger">Low Margin</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center text-muted">No profitability data available for the selected period</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="5">Total</th>
                                        <th class="text-end">{{ number_format($totalRevenue, 2) }}</th>
                                        <th class="text-end">{{ number_format($totalCogs, 2) }}</th>
                                        <th class="text-end">{{ number_format($totalGrossProfit, 2) }}</th>
                                        <th class="text-end">{{ number_format($averageMarginPercentage, 2) }}%</th>
                                        <th>-</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Profitability Insights -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card border border-info">
                                    <div class="card-body">
                                        <h5 class="card-title text-info">
                                            <i class="bx bx-lightbulb me-2"></i>Profitability Insights
                                        </h5>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="text-center">
                                                    <h6 class="text-muted">Top Performing Product</h6>
                                                    <h4 class="text-success">
                                                        {{ $topProduct->inventoryItem->name ?? 'N/A' }}
                                                    </h4>
                                                    <small class="text-muted">
                                                        {{ number_format($topProduct->margin_percentage ?? 0, 1) }}% margin
                                                        ({{ number_format($topProduct->gross_profit ?? 0, 2) }} TZS)
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-center">
                                                    <h6 class="text-muted">Revenue Leader</h6>
                                                    <h4 class="text-primary">
                                                        {{ $revenueLeader->inventoryItem->name ?? 'N/A' }}
                                                    </h4>
                                                    <small class="text-muted">
                                                        {{ number_format($revenueLeader->total_revenue ?? 0, 2) }} TZS revenue
                                                        ({{ number_format($revenueLeader->margin_percentage ?? 0, 1) }}% margin)
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-center">
                                                    <h6 class="text-muted">Recommendation</h6>
                                                    @if($averageMarginPercentage >= 25)
                                                        <span class="badge bg-success">Excellent margins</span>
                                                    @elseif($averageMarginPercentage >= 15)
                                                        <span class="badge bg-warning">Review pricing strategy</span>
                                                    @else
                                                        <span class="badge bg-danger">Urgent margin improvement needed</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
