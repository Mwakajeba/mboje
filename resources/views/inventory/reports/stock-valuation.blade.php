@extends('layouts.main')

@section('title', 'Stock Valuation Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Stock Valuation', 'url' => '#', 'icon' => 'bx bx-dollar']
        ]" />
        
        <h6 class="mb-0 text-uppercase">STOCK VALUATION REPORT</h6>
        <hr />

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('inventory.reports.stock-valuation') }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="location_id" class="form-label">Location</label>
                                <select class="form-select" id="location_id" name="location_id">
                                    <option value="">All Locations</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}" {{ request('location_id') == $location->id ? 'selected' : '' }}>
                                            {{ $location->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="costing_method" class="form-label">Costing Method</label>
                                <select class="form-select" id="costing_method" name="costing_method">
                                    <option value="">System Default ({{ ucfirst($selectedCostingMethod) }})</option>
                                    <option value="average" {{ request('costing_method') == 'average' ? 'selected' : '' }}>Weighted Average</option>
                                    <option value="fifo" {{ request('costing_method') == 'fifo' ? 'selected' : '' }}>FIFO</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-search me-1"></i> Filter
                                    </button>
                                    <a href="{{ route('inventory.reports.stock-valuation') }}" class="btn btn-secondary">
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

    <!-- Summary -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h5 class="card-title text-info">Total Inventory Value</h5>
                    <h2 class="text-info">{{ number_format($totalValue, 2) }} TZS</h2>
                </div>
            </div>
        </div>
    </div>


    <!-- Report Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Stock Valuation Report</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Item Code</th>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Location</th>
                                    <th>Costing Method</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Unit Cost</th>
                                    <th class="text-end">Total Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($itemsWithValues as $itemData)
                                    <tr>
                                        <td>{{ $itemData['item']->code }}</td>
                                        <td>{{ $itemData['item']->name }}</td>
                                        <td>{{ $itemData['item']->category->name ?? 'N/A' }}</td>
                                        <td>
                                            @if(request('location_id'))
                                                {{ \App\Models\InventoryLocation::find(request('location_id'))->name ?? 'N/A' }}
                                            @else
                                                All Locations
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $itemData['costing_method'] === 'FIFO' ? 'primary' : 'info' }}">
                                                {{ $itemData['costing_method'] }}
                                            </span>
                                        </td>
                                        <td class="text-end">{{ number_format($itemData['stock'], 2) }}</td>
                                        <td class="text-end">{{ number_format($itemData['unit_cost'], 2) }} TZS</td>
                                        <td class="text-end">{{ number_format($itemData['total_value'], 2) }} TZS</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No items found with stock</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="5" class="text-end">TOTAL:</th>
                                    <th class="text-end">{{ number_format($itemsWithValues->sum('stock'), 2) }}</th>
                                    <th class="text-end">-</th>
                                    <th class="text-end">{{ number_format($totalValue, 2) }} TZS</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Totals Summary -->
    @if($categoryTotals->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Valuation by Category ({{ ucfirst($selectedCostingMethod) }})</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Total Quantity</th>
                                    <th class="text-end">Total Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categoryTotals as $categoryName => $totals)
                                    <tr>
                                        <td>{{ $categoryName }}</td>
                                        <td class="text-end">{{ number_format($totals['quantity'], 2) }}</td>
                                        <td class="text-end">{{ number_format($totals['value'], 2) }} TZS</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th>TOTAL:</th>
                                    <th class="text-end">{{ number_format($categoryTotals->sum('quantity'), 2) }}</th>
                                    <th class="text-end">{{ number_format($categoryTotals->sum('value'), 2) }} TZS</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Location Totals Summary -->
    @if($locationTotals->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Valuation by Location ({{ ucfirst($selectedCostingMethod) }})</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Location</th>
                                    <th class="text-end">Total Quantity</th>
                                    <th class="text-end">Total Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($locationTotals as $locationName => $totals)
                                    <tr>
                                        <td>{{ $locationName }}</td>
                                        <td class="text-end">{{ number_format($totals['quantity'], 2) }}</td>
                                        <td class="text-end">{{ number_format($totals['value'], 2) }} TZS</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th>TOTAL:</th>
                                    <th class="text-end">{{ number_format($locationTotals->sum('quantity'), 2) }}</th>
                                    <th class="text-end">{{ number_format($locationTotals->sum('value'), 2) }} TZS</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Costing Method Information -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Costing Method Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>FIFO (First In, First Out)</h6>
                            <p class="text-muted small">
                                Uses the cost of the oldest inventory first. Provides more accurate cost reflection 
                                when prices are changing over time. Better for financial reporting and tax purposes.
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Weighted Average</h6>
                            <p class="text-muted small">
                                Uses the average cost of all inventory purchases. Provides a smoothed cost 
                                that reduces the impact of price fluctuations. Simpler to calculate and understand.
                            </p>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3">
                        <strong>Current Method:</strong> {{ ucfirst($selectedCostingMethod) }} costing is being used for this report.
                        @if($selectedCostingMethod === 'fifo')
                            Values are calculated based on actual cost layers and consumption patterns.
                        @else
                            Values are calculated using the weighted average cost from the item's cost_price field.
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
@endsection
