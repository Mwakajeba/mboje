@extends('layouts.main')

@section('title', 'Location Bin Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Location Bin', 'url' => '#', 'icon' => 'bx bx-map']
        ]" />
        
        <h6 class="mb-0 text-uppercase">LOCATION BIN REPORT</h6>
        <hr />

    <!-- Location Selection -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('inventory.reports.location-bin') }}">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="location_id" class="form-label">Select Location</label>
                                <select class="form-select" id="location_id" name="location_id" required>
                                    <option value="">Choose Location</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}" {{ request('location_id') == $location->id ? 'selected' : '' }}>
                                            {{ $location->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-search me-1"></i> Generate Report
                                    </button>
                                    <a href="{{ route('inventory.reports.location-bin') }}" class="btn btn-secondary">
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

    @if(isset($location))
    <!-- Location Info -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-body">
                    <h5 class="card-title">Location: {{ $location->name }}</h5>
                    <p class="text-muted">{{ $location->description ?? 'No description available' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h5 class="card-title text-success">Normal Bins</h5>
                    <h3 class="text-success">{{ $binReport->where('status', 'normal')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h5 class="card-title text-warning">Overfull Bins</h5>
                    <h3 class="text-warning">{{ $binReport->where('status', 'overfull')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <h5 class="card-title text-danger">Empty Bins</h5>
                    <h3 class="text-danger">{{ $binReport->where('status', 'empty')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h5 class="card-title text-info">Total Items</h5>
                    <h3 class="text-info">{{ $binReport->count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Location Bin Report - {{ $location->name }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Item Code</th>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Max Stock</th>
                                    <th class="text-end">Utilization %</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($binReport->sortBy('status') as $bin)
                                    @php
                                        $item = $bin['item'];
                                        $status = $bin['status'];
                                        $statusClass = $status == 'normal' ? 'success' : ($status == 'overfull' ? 'warning' : 'danger');
                                    @endphp
                                    <tr class="{{ $status == 'overfull' ? 'table-warning' : ($status == 'empty' ? 'table-danger' : '') }}">
                                        <td>{{ $item->code }}</td>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->category->name ?? 'N/A' }}</td>
                                        <td class="text-end">{{ number_format($item->current_stock, 2) }}</td>
                                        <td class="text-end">{{ number_format($item->maximum_stock, 2) }}</td>
                                        <td class="text-end">
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-{{ $statusClass }}" role="progressbar" 
                                                     style="width: {{ $bin['utilization'] }}%">
                                                    {{ number_format($bin['utilization'], 1) }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $statusClass }}">
                                                {{ ucfirst($status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No items found in this location</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    </div>
</div>
@endsection
