@extends('layouts.main')

@section('title', 'Stock on Hand Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Stock on Hand', 'url' => '#', 'icon' => 'bx bx-package']
        ]" />
        
        <h6 class="mb-0 text-uppercase">STOCK ON HAND REPORT</h6>
        <hr />

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('inventory.reports.stock-on-hand') }}">
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
                                <label for="date" class="form-label">As of Date</label>
                                <input type="date" class="form-control" id="date" name="date" value="{{ request('date', date('Y-m-d')) }}" disabled>
                                <small class="text-muted">Stock on Hand shows current quantities</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-search me-1"></i> Filter
                                    </button>
                                    <a href="{{ route('inventory.reports.stock-on-hand') }}" class="btn btn-secondary">
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
                        <a href="{{ route('inventory.reports.stock-on-hand.export.excel', request()->query()) }}" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export to Excel
                        </a>
                        <a href="{{ route('inventory.reports.stock-on-hand.export.pdf', request()->query()) }}" class="btn btn-danger">
                            <i class="bx bx-file-pdf me-1"></i> Export to PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h5 class="card-title text-primary">Total Quantity</h5>
                    <h3 class="text-primary">{{ number_format($totalQuantity, 2) }}</h3>
                    <p class="text-muted mb-0">Items in Stock</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h5 class="card-title text-success">Total Value</h5>
                    <h3 class="text-success">{{ number_format($totalValue, 2) }} TZS</h3>
                    <p class="text-muted mb-0">Inventory Value</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Stock on Hand Report</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Item Code</th>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>UOM</th>
                                    <th class="text-end">Unit Cost</th>
                                    <th class="text-end">Total Stock</th>
                                    <th class="text-end">Total Value</th>
                                    <th>Locations</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($itemsWithStock as $itemData)
                                    <tr>
                                        <td>{{ $itemData['item']->code }}</td>
                                        <td>{{ $itemData['item']->name }}</td>
                                        <td>{{ $itemData['item']->category->name ?? 'N/A' }}</td>
                                        <td>{{ $itemData['item']->unit_of_measure }}</td>
                                        <td class="text-end">{{ number_format($itemData['unit_cost'], 2) }} TZS</td>
                                        <td class="text-end">{{ number_format($itemData['total_stock'], 2) }}</td>
                                        <td class="text-end">{{ number_format($itemData['total_value'], 2) }} TZS</td>
                                        <td>
                                            @if(count($itemData['locations']) > 0)
                                                <div class="location-breakdown">
                                                    @foreach($itemData['locations'] as $locationData)
                                                        <div class="d-flex justify-content-between small">
                                                            <span>{{ $locationData['location']->name }}:</span>
                                                            <span>{{ number_format($locationData['stock'], 2) }} ({{ number_format($locationData['value'], 0) }} TZS)</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted">No stock</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No items with stock found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="5" class="text-end">TOTAL:</th>
                                    <th class="text-end">{{ number_format($totalQuantity, 2) }}</th>
                                    <th class="text-end">{{ number_format($totalValue, 2) }} TZS</th>
                                    <th></th>
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
