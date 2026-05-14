@extends('layouts.main')

@section('title', 'Category Performance Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Category Performance Report', 'url' => '#', 'icon' => 'bx bx-pie-chart']
        ]" />
        
        <h6 class="mb-0 text-uppercase">CATEGORY PERFORMANCE REPORT</h6>
        <hr />

        <!-- Purpose and Use Info -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info">
                    <h6 class="alert-heading mb-2"><i class="bx bx-info-circle me-2"></i>Purpose</h6>
                    <p class="mb-2">Analyze profitability by product category.</p>
                    <h6 class="alert-heading mb-2"><i class="bx bx-target-lock me-2"></i>Use</h6>
                    <p class="mb-0">Gross Profit = Sales – Cost of Sales | Gross Margin % = (Gross Profit ÷ Sales) × 100</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('inventory.reports.category-performance') }}">
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label for="branch_id" class="form-label">Branch</label>
                                    <select class="form-select select2-single" id="branch_id" name="branch_id">
                                        @if($hasMultipleBranches)
                                            <option value="all_my_branches" {{ request('branch_id', 'all_my_branches') == 'all_my_branches' ? 'selected' : '' }}>
                                                All My Branches
                                            </option>
                                        @else
                                            <option value="">All Branches</option>
                                        @endif
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" {{ request('branch_id', $hasMultipleBranches ? 'all_my_branches' : (session('branch_id') ?? '')) == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="date_from" class="form-label">Date From</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" value="{{ $dateFrom }}">
                                </div>
                                <div class="col-md-2">
                                    <label for="date_to" class="form-label">Date To</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" value="{{ $dateTo }}">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bx bx-search me-1"></i> Generate
                                        </button>
                                        <a href="{{ route('inventory.reports.category-performance') }}" class="btn btn-secondary">
                                            <i class="bx bx-refresh me-1"></i> Reset
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('inventory.reports.category-performance.export.pdf', request()->all()) }}" class="btn btn-danger" target="_blank">
                                            <i class="bx bx-file me-1"></i> Export PDF
                                        </a>
                                        <a href="{{ route('inventory.reports.category-performance.export.excel', request()->all()) }}" class="btn btn-success" target="_blank">
                                            <i class="bx bx-file me-1"></i> Export Excel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if(isset($reportData) && $reportData->count() > 0)
        <!-- Report Data -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bx bx-pie-chart me-2"></i>Category Performance Report</h5>
                        <small>Period: {{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Category / Dept</th>
                                        <th class="text-end">Total Sales (TZS)</th>
                                        <th class="text-end">Cost of Sales (TZS)</th>
                                        <th class="text-end">Gross Profit</th>
                                        <th class="text-end">Gross Margin %</th>
                                        <th class="text-end">Units Sold</th>
                                        <th>Top Selling Item</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalSales = 0;
                                        $totalCost = 0;
                                        $totalProfit = 0;
                                        $totalUnits = 0;
                                    @endphp
                                    @foreach($reportData as $data)
                                        @php
                                            $totalSales += $data['total_sales'];
                                            $totalCost += $data['cost_of_sales'];
                                            $totalProfit += $data['gross_profit'];
                                            $totalUnits += $data['units_sold'];
                                        @endphp
                                        <tr>
                                            <td>{{ $data['category']->name }}</td>
                                            <td class="text-end">{{ number_format($data['total_sales'], 2) }}</td>
                                            <td class="text-end text-danger">{{ number_format($data['cost_of_sales'], 2) }}</td>
                                            <td class="text-end text-success">{{ number_format($data['gross_profit'], 2) }}</td>
                                            <td class="text-end fw-bold">{{ number_format($data['gross_margin'], 2) }}%</td>
                                            <td class="text-end">{{ number_format($data['units_sold'], 2) }}</td>
                                            <td>{{ $data['top_selling_item']->name ?? 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td class="text-end">TOTAL:</td>
                                        <td class="text-end">{{ number_format($totalSales, 2) }}</td>
                                        <td class="text-end text-danger">{{ number_format($totalCost, 2) }}</td>
                                        <td class="text-end text-success">{{ number_format($totalProfit, 2) }}</td>
                                        <td class="text-end">{{ $totalSales > 0 ? number_format(($totalProfit / $totalSales) * 100, 2) : 0 }}%</td>
                                        <td class="text-end">{{ number_format($totalUnits, 2) }}</td>
                                        <td>-</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @elseif(isset($reportData))
        <div class="row">
            <div class="col-12">
                <div class="alert alert-warning">
                    <i class="bx bx-info-circle me-2"></i>No data found for the selected criteria.
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        $('.select2-single').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    });
</script>
@endpush

