@extends('layouts.main')

@section('title', 'Cycle Count Performance Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Cycle Count Performance', 'url' => '#', 'icon' => 'bx bx-trending-up']
        ]" />
        
        <h6 class="mb-0 text-uppercase">CYCLE COUNT PERFORMANCE REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('inventory.reports.cycle-count-performance') }}">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="date_from" class="form-label">Date From</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                                </div>
                                <div class="col-md-3">
                                    <label for="date_to" class="form-label">Date To</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bx bx-search me-1"></i> Filter
                                        </button>
                                        <a href="{{ route('inventory.reports.cycle-count-performance') }}" class="btn btn-secondary">
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

        <!-- Report Data -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Cycle Count Performance Metrics</h5>
                    </div>
                    <div class="card-body">
                        @if($performance->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Period Name</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Total Entries</th>
                                            <th>Counted Entries</th>
                                            <th>Completion Rate (%)</th>
                                            <th>Variance Count</th>
                                            <th>Zero Variance</th>
                                            <th>Accuracy Rate (%)</th>
                                            <th>Total Variance Value (TZS)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($performance as $perf)
                                            <tr>
                                                <td>{{ $perf['period']->period_name }}</td>
                                                <td>{{ $perf['period']->count_start_date->format('M d, Y') }}</td>
                                                <td>{{ $perf['period']->count_end_date->format('M d, Y') }}</td>
                                                <td>{{ number_format($perf['total_entries']) }}</td>
                                                <td>{{ number_format($perf['counted_entries']) }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $perf['completion_rate'] >= 90 ? 'success' : ($perf['completion_rate'] >= 70 ? 'warning' : 'danger') }}">
                                                        {{ number_format($perf['completion_rate'], 2) }}%
                                                    </span>
                                                </td>
                                                <td>{{ number_format($perf['variance_count']) }}</td>
                                                <td>{{ number_format($perf['zero_variance_count']) }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $perf['accuracy_rate'] >= 95 ? 'success' : ($perf['accuracy_rate'] >= 90 ? 'warning' : 'danger') }}">
                                                        {{ number_format($perf['accuracy_rate'], 2) }}%
                                                    </span>
                                                </td>
                                                <td>{{ number_format($perf['total_variance_value'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="bx bx-info-circle me-2"></i>No cycle count periods found matching the criteria.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

