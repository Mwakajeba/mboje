@extends('layouts.main')

@section('title', 'Reorder Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Reorder Report', 'url' => '#', 'icon' => 'bx bx-refresh']
        ]" />
        
        <h6 class="mb-0 text-uppercase">REORDER REPORT</h6>
        <hr />

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('inventory.reports.reorder') }}">
                        <div class="row g-3">
                            <div class="col-md-4">
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
                            <div class="col-md-4">
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
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-search me-1"></i> Filter
                                    </button>
                                    <a href="{{ route('inventory.reports.reorder') }}" class="btn btn-secondary">
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
                        <a href="{{ route('inventory.reports.reorder.export.excel', request()->query()) }}" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </a>
                        <a href="{{ route('inventory.reports.reorder.export.pdf', request()->query()) }}" class="btn btn-danger">
                            <i class="bx bx-file-pdf me-1"></i> Export PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-warning">
                <h5 class="alert-heading">
                    <i class="bx bx-exclamation-triangle me-2"></i>
                    Items Requiring Reorder
                </h5>
                <p class="mb-0">The following items are below their reorder levels and need to be restocked.</p>
            </div>
        </div>
    </div>

    <!-- Report Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Reorder Report</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Item Code</th>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    {{-- <th>Location</th> --}}
                                    {{-- <th>Supplier</th> --}}
                                    <th class="text-end">On Hand</th>
                                    <th class="text-end">Reserved</th>
                                    <th class="text-end">Available</th>
                                    <th class="text-end">Min Level</th>
                                    <th class="text-end">Reorder Level</th>
                                    <th class="text-end">Suggested Qty</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reorderItems as $reorderItem)
                                    @php
                                        $item = $reorderItem['item'];
                                        $available = $reorderItem['available'];
                                        $suggestedQty = $reorderItem['suggested_qty'];
                                        $status = $available <= 0 ? 'critical' : 'warning';
                                    @endphp
                                    <tr class="{{ $status == 'critical' ? 'table-danger' : 'table-warning' }}">
                                        <td>{{ $item->code }}</td>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->category->name ?? 'N/A' }}</td>
                                        {{-- <td>{{ $item->location->name ?? 'N/A' }}</td>
                                        <td>N/A</td> --}}
                                        <td class="text-end">{{ number_format($item->current_stock, 2) }}</td>
                                        <td class="text-end">0.00</td>
                                        <td class="text-end">{{ number_format($item->current_stock, 2) }}</td>
                                        <td class="text-end">{{ number_format($item->minimum_stock, 2) }}</td>
                                        <td class="text-end">{{ number_format($item->reorder_level, 2) }}</td>
                                        <td class="text-end">
                                            <strong>{{ number_format($suggestedQty, 2) }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $status == 'critical' ? 'danger' : 'warning' }}">
                                                {{ $status == 'critical' ? 'Critical' : 'Low Stock' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center">
                                            <div class="text-success">
                                                <i class="bx bx-check-circle fs-1"></i>
                                                <p class="mt-2">All items are adequately stocked!</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
@endsection
