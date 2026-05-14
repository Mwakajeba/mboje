@extends('layouts.main')

@section('title', 'Over/Understock Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Over/Understock Report', 'url' => '#', 'icon' => 'bx bx-error']
        ]" />
        

        <hr />

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('inventory.reports.over-understock') }}">
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
                                <label for="status" class="form-label">Stock Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="ok" {{ request('status') == 'ok' ? 'selected' : '' }}>OK</option>
                                    <option value="understock" {{ request('status') == 'understock' ? 'selected' : '' }}>Understock</option>
                                    <option value="overstock" {{ request('status') == 'overstock' ? 'selected' : '' }}>Overstock</option>
                                    <option value="out_of_stock" {{ request('status') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-search me-1"></i> Filter
                                    </button>
                                    <a href="{{ route('inventory.reports.over-understock') }}" class="btn btn-secondary">
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
                        <a href="{{ route('inventory.reports.over-understock.export.excel', request()->query()) }}" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </a>
                        <a href="{{ route('inventory.reports.over-understock.export.pdf', request()->query()) }}" class="btn btn-danger">
                            <i class="bx bx-file-pdf me-1"></i> Export PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h5 class="card-title text-success">Normal Stock</h5>
                    <h3 class="text-success">{{ $stockAnalysis->where('status', 'ok')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h5 class="card-title text-warning">Understock</h5>
                    <h3 class="text-warning">{{ $stockAnalysis->where('status', 'understock')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <h5 class="card-title text-danger">Overstock</h5>
                    <h3 class="text-danger">{{ $stockAnalysis->where('status', 'overstock')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-dark">
                <div class="card-body text-center">
                    <h5 class="card-title text-dark">Out of Stock</h5>
                    <h3 class="text-dark">{{ $stockAnalysis->where('status', 'out_of_stock')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h5 class="card-title text-info">Total Value</h5>
                    <h3 class="text-info">{{ number_format($stockAnalysis->sum('value'), 2) }} TZS</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Over/Understock Report</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Item Code</th>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th class="text-end">On Hand</th>
                                    <th class="text-end">Min Level</th>
                                    <th class="text-end">Max Level</th>
                                    <th class="text-end">Variance</th>
                                    <th class="text-end">Value</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stockAnalysis->sortBy('status') as $analysis)
                                    @php
                                        $item = $analysis['item'];
                                        $status = $analysis['status'];
                                        $statusClass = match($status) {
                                            'ok'           => 'success',
                                            'understock'   => 'warning',
                                            'overstock'    => 'danger',
                                            'out_of_stock' => 'dark',
                                            default        => 'secondary',
                                        };
                                        $rowClass = match($status) {
                                            'overstock'    => 'table-danger',
                                            'understock'   => 'table-warning',
                                            'out_of_stock' => 'table-dark',
                                            default        => '',
                                        };
                                        $statusLabel = match($status) {
                                            'out_of_stock' => 'Out of Stock',
                                            default        => ucfirst($status),
                                        };
                                    @endphp
                                    <tr class="{{ $rowClass }}">
                                        <td>{{ $item->code }}</td>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->category->name ?? 'N/A' }}</td>
                                        <td class="text-end">{{ number_format($item->current_stock, 2) }}</td>
                                        <td class="text-end">{{ number_format($item->minimum_stock, 2) }}</td>
                                        <td class="text-end">{{ number_format($item->maximum_stock, 2) }}</td>
                                        <td class="text-end">
                                            <span class="{{ $analysis['variance'] < 0 ? 'text-danger' : 'text-success' }}">
                                                {{ number_format($analysis['variance'], 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">{{ number_format($analysis['value'], 2) }} TZS</td>
                                        <td>
                                            <span class="badge bg-{{ $statusClass }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center">No items found</td>
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
