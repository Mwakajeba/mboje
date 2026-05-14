@extends('layouts.main')

@section('title', 'Full Inventory Count Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Full Inventory Count', 'url' => '#', 'icon' => 'bx bx-clipboard']
        ]" />
        
        <h6 class="mb-0 text-uppercase">FULL INVENTORY COUNT REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('inventory.reports.full-inventory-count') }}">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="period_id" class="form-label">Count Period</label>
                                    <select class="form-select" id="period_id" name="period_id">
                                        <option value="">All Periods</option>
                                        @foreach($periods as $period)
                                            <option value="{{ $period->id }}" {{ request('period_id') == $period->id ? 'selected' : '' }}>
                                                {{ $period->period_name }}
                                            </option>
                                        @endforeach
                                    </select>
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
                                <div class="col-md-2">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">All Statuses</option>
                                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="frozen" {{ request('status') == 'frozen' ? 'selected' : '' }}>Frozen</option>
                                        <option value="counting" {{ request('status') == 'counting' ? 'selected' : '' }}>Counting</option>
                                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="date_from" class="form-label">Date From</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                                </div>
                                <div class="col-md-2">
                                    <label for="date_to" class="form-label">Date To</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-search me-1"></i> Filter
                                    </button>
                                    <a href="{{ route('inventory.reports.full-inventory-count') }}" class="btn btn-secondary">
                                        <i class="bx bx-refresh me-1"></i> Reset
                                    </a>
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
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Count Sessions</h5>
                        <span class="badge bg-primary">{{ $sessions->count() }} Sessions</span>
                    </div>
                    <div class="card-body">
                        @if($sessions->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Session Number</th>
                                            <th>Period</th>
                                            <th>Location</th>
                                            <th>Status</th>
                                            <th>Snapshot Date</th>
                                            <th>Total Entries</th>
                                            <th>Counted Entries</th>
                                            <th>Created By</th>
                                            <th>Created At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sessions as $session)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('inventory.counts.sessions.show', $session->encoded_id) }}" class="text-primary">
                                                        {{ $session->session_number }}
                                                    </a>
                                                </td>
                                                <td>{{ $session->period->period_name ?? 'N/A' }}</td>
                                                <td>{{ $session->location->name ?? 'N/A' }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $session->status === 'completed' ? 'success' : ($session->status === 'counting' ? 'warning' : ($session->status === 'frozen' ? 'info' : 'secondary')) }}">
                                                        {{ ucfirst($session->status) }}
                                                    </span>
                                                </td>
                                                <td>{{ $session->snapshot_date ? $session->snapshot_date->format('M d, Y H:i') : 'N/A' }}</td>
                                                <td>{{ $session->entries->count() }}</td>
                                                <td>{{ $session->entries->where('status', '!=', 'pending')->count() }}</td>
                                                <td>{{ $session->createdBy->name ?? 'N/A' }}</td>
                                                <td>{{ $session->created_at->format('M d, Y') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="bx bx-info-circle me-2"></i>No count sessions found matching the criteria.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

