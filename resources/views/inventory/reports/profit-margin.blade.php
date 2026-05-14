@extends('layouts.main')

@section('title', 'Profit Margin Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Profit Margin', 'url' => '#', 'icon' => 'bx bx-trending-up']
        ]" />
        
        <h6 class="mb-0 text-uppercase">PROFIT MARGIN REPORT</h6>
        <hr />

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('inventory.reports.profit-margin') }}">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">Date From</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="{{ $dateFrom }}">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">Date To</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="{{ $dateTo }}">
                            </div>
                            <div class="col-md-3">
                                <label for="item_id" class="form-label">Item</label>
                                <select class="form-select select2-single" id="item_id" name="item_id">
                                    <option value="">All Items</option>
                                    @foreach($items as $item)
                                        <option value="{{ $item->id }}" {{ request('item_id') == $item->id ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="customer_id" class="form-label">Customer</label>
                                <select class="form-select select2-single" id="customer_id" name="customer_id">
                                    <option value="">All Customers</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-search me-1"></i> Filter
                                    </button>
                                    <a href="{{ route('inventory.reports.profit-margin') }}" class="btn btn-secondary">
                                        <i class="bx bx-refresh me-1"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Buttons -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <a href="{{ route('inventory.reports.profit-margin.export.excel', request()->query()) }}" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </a>
                        <a href="{{ route('inventory.reports.profit-margin.export.pdf', request()->query()) }}" class="btn btn-danger">
                            <i class="bx bx-file-pdf me-1"></i> Export PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h5 class="card-title text-primary">Total Revenue</h5>
                    <h3 class="text-primary">{{ number_format($profitData->sum('sales_revenue'), 2) }} TZS</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <h5 class="card-title text-danger">Total Cost</h5>
                    <h3 class="text-danger">{{ number_format($profitData->sum('cost_of_goods'), 2) }} TZS</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h5 class="card-title text-success">Gross Profit</h5>
                    <h3 class="text-success">{{ number_format($profitData->sum('gross_margin'), 2) }} TZS</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h5 class="card-title text-info">Avg Margin %</h5>
                    <h3 class="text-info">
                        @php
                            $totalRevenue = $profitData->sum('sales_revenue');
                            $totalMargin = $profitData->sum('gross_margin');
                            $avgMargin = $totalRevenue > 0 ? ($totalMargin / $totalRevenue) * 100 : 0;
                        @endphp
                        {{ number_format($avgMargin, 1) }}%
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Profit Margin Report</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Item Code</th>
                                    <th>Item Name</th>
                                    <th class="text-end">Sold Qty</th>
                                    <th class="text-end">Sales Revenue</th>
                                    <th class="text-end">Cost of Goods</th>
                                    <th class="text-end">Gross Margin</th>
                                    <th class="text-end">Margin %</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($profitData as $data)
                                    @php
                                        $marginPercent = $data['gross_margin_percent'];
                                        $status = $marginPercent >= 30 ? 'excellent' : ($marginPercent >= 20 ? 'good' : ($marginPercent >= 10 ? 'fair' : 'poor'));
                                        $statusClass = $status == 'excellent' ? 'success' : ($status == 'good' ? 'info' : ($status == 'fair' ? 'warning' : 'danger'));
                                    @endphp
                                    <tr>
                                        <td>{{ $data['item']->code }}</td>
                                        <td>{{ $data['item']->name }}</td>
                                        <td class="text-end">{{ number_format($data['sold_qty'], 2) }}</td>
                                        <td class="text-end">{{ number_format($data['sales_revenue'], 2) }} TZS</td>
                                        <td class="text-end">{{ number_format($data['cost_of_goods'], 2) }} TZS</td>
                                        <td class="text-end">
                                            <span class="{{ $data['gross_margin'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($data['gross_margin'], 2) }} TZS
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-{{ $statusClass }}">
                                                {{ number_format($marginPercent, 1) }}%
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $statusClass }}">
                                                {{ ucfirst($status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No sales data found for the selected period</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="2" class="text-end">TOTAL:</th>
                                    <th class="text-end">{{ number_format($profitData->sum('sold_qty'), 2) }}</th>
                                    <th class="text-end">{{ number_format($profitData->sum('sales_revenue'), 2) }} TZS</th>
                                    <th class="text-end">{{ number_format($profitData->sum('cost_of_goods'), 2) }} TZS</th>
                                    <th class="text-end">{{ number_format($profitData->sum('gross_margin'), 2) }} TZS</th>
                                    <th class="text-end">{{ number_format($avgMargin, 1) }}%</th>
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

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2 for item dropdown
    $('#item_id').select2({
        placeholder: 'Select Item',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#item_id').parent()
    });

    // Initialize Select2 for customer dropdown
    $('#customer_id').select2({
        placeholder: 'Select Customer',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#customer_id').parent()
    });
});
</script>
@endpush
