@extends('layouts.main')

@section('title', 'Aging Stock Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Aging Stock', 'url' => '#', 'icon' => 'bx bx-time']
        ]" />
        
        <h6 class="mb-0 text-uppercase">AGING STOCK REPORT</h6>
        <hr />

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('inventory.reports.aging-stock') }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="threshold_days" class="form-label">Threshold Days</label>
                                <select class="form-select" id="threshold_days" name="threshold_days">
                                    <option value="30" {{ $thresholdDays == 30 ? 'selected' : '' }}>30 Days</option>
                                    <option value="60" {{ $thresholdDays == 60 ? 'selected' : '' }}>60 Days</option>
                                    <option value="90" {{ $thresholdDays == 90 ? 'selected' : '' }}>90 Days</option>
                                    <option value="180" {{ $thresholdDays == 180 ? 'selected' : '' }}>180 Days</option>
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
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-search me-1"></i> Filter
                                    </button>
                                    <a href="{{ route('inventory.reports.aging-stock') }}" class="btn btn-secondary">
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

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h5 class="card-title text-success">Active Items</h5>
                    <h3 class="text-success">{{ $itemsWithLastMovement->where('status', 'active')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h5 class="card-title text-warning">Slow Moving</h5>
                    <h3 class="text-warning">{{ $itemsWithLastMovement->where('status', 'slow')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <h5 class="card-title text-danger">Obsolete</h5>
                    <h3 class="text-danger">{{ $itemsWithLastMovement->where('status', 'obsolete')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h5 class="card-title text-info">Total Value</h5>
                    <h3 class="text-info">{{ number_format($itemsWithLastMovement->sum('value'), 2) }} TZS</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Aging Stock Report</h5>
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
                                    <th class="text-end">On Hand</th>
                                    <th class="text-end">Unit Cost</th>
                                    <th class="text-end">Value</th>
                                    <th>Last Movement</th>
                                    <th class="text-end">Days Inactive</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($itemsWithLastMovement->sortByDesc('days_inactive') as $itemData)
                                    @php
                                        $item = $itemData['item'];
                                        $status = $itemData['status'];
                                        $statusClass = $status == 'active' ? 'success' : ($status == 'slow' ? 'warning' : 'danger');
                                    @endphp
                                    <tr class="{{ $status == 'obsolete' ? 'table-danger' : ($status == 'slow' ? 'table-warning' : '') }}">
                                        <td>{{ $item->code }}</td>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->category->name ?? 'N/A' }}</td>
                                        <td>{{ $item->location->name ?? 'N/A' }}</td>
                                        <td class="text-end">{{ number_format($item->current_stock, 2) }}</td>
                                        <td class="text-end">{{ number_format($item->resolved_cost_price ?? $item->cost_price, 2) }} TZS</td>
                                        <td class="text-end">{{ number_format($itemData['value'], 2) }} TZS</td>
                                        <td>{{ $itemData['last_movement_date'] ? $itemData['last_movement_date']->format('Y-m-d') : 'Never' }}</td>
                                        <td class="text-end">
                                            <strong>{{ $itemData['days_inactive'] }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $statusClass }}">
                                                {{ ucfirst($status) }}
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
