@extends('layouts.main')

@section('title', 'Stock Take Variance Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Stock Take Variance', 'url' => '#', 'icon' => 'bx bx-check-square']
        ]" />
        
        <h6 class="mb-0 text-uppercase">STOCK TAKE VARIANCE REPORT</h6>
        <hr />

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('inventory.reports.stock-take-variance') }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="count_batch" class="form-label">Count Batch</label>
                                <input type="text" class="form-control" id="count_batch" name="count_batch" value="{{ request('count_batch') }}" placeholder="Enter batch number">
                            </div>
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
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-search me-1"></i> Filter
                                    </button>
                                    <a href="{{ route('inventory.reports.stock-take-variance') }}" class="btn btn-secondary">
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

    <!-- Report Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Stock Take Variance Report</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Count Batch</th>
                                    <th>Date</th>
                                    <th>Location</th>
                                    <th>User</th>
                                    <th>Status</th>
                                    <th>Items Counted</th>
                                    <th>Total Variance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stockTakes as $stockTake)
                                    <tr>
                                        <td>{{ $stockTake->count_batch }}</td>
                                        <td>{{ $stockTake->created_at->format('Y-m-d H:i') }}</td>
                                        <td>{{ $stockTake->location->name ?? 'N/A' }}</td>
                                        <td>{{ $stockTake->user->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $stockTake->status == 'completed' ? 'success' : 'warning' }}">
                                                {{ ucfirst($stockTake->status) }}
                                            </span>
                                        </td>
                                        <td class="text-end">{{ $stockTake->items->count() }}</td>
                                        <td class="text-end">
                                            @php
                                                $totalVariance = $stockTake->items->sum(function($item) {
                                                    return abs($item->counted_quantity - $item->system_quantity);
                                                });
                                            @endphp
                                            {{ number_format($totalVariance, 2) }}
                                        </td>
                                        <td>
                                            <a href="#" class="btn btn-sm btn-primary">
                                                <i class="bx bx-show me-1"></i> View Details
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No stock takes found</td>
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
