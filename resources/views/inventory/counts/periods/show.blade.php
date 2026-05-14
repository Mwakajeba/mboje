@extends('layouts.main')

@section('title', 'Count Period Details')

@push('styles')
<style>
    .info-card {
        border-left: 4px solid;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .info-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .info-card.border-primary {
        border-left-color: #0d6efd;
    }
    
    .info-card.border-success {
        border-left-color: #198754;
    }
    
    .info-card.border-warning {
        border-left-color: #ffc107;
    }
    
    .info-card.border-info {
        border-left-color: #0dcaf0;
    }
    
    .info-item {
        padding: 0.75rem 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 600;
        color: #6c757d;
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }
    
    .info-value {
        color: #212529;
        font-size: 1rem;
    }
    
    .radius-10 {
        border-radius: 10px;
    }
    
    .widgets-icons-2 {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 27px;
    }
    
    .bg-light-primary {
        background-color: rgba(13, 110, 253, 0.1);
    }
    
    .bg-light-warning {
        background-color: rgba(255, 193, 7, 0.1);
    }
    
    .bg-light-success {
        background-color: rgba(25, 135, 84, 0.1);
    }
    
    .bg-light-info {
        background-color: rgba(13, 202, 240, 0.1);
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Management', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Inventory Count', 'url' => route('inventory.counts.index'), 'icon' => 'bx bx-clipboard-check'],
            ['label' => 'Period Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <!-- Header Section -->
        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h4 class="mb-2">
                                    <i class="bx bx-calendar-check me-2 text-primary"></i>{{ $period->period_name }}
                                </h4>
                                <p class="text-muted mb-0">
                                    <i class="bx bx-time me-1"></i>
                                    Period: {{ $period->count_start_date->format('M d, Y') }} - {{ $period->count_end_date->format('M d, Y') }}
                                </p>
                            </div>
                            <div class="d-flex gap-2">
                                @php
                                    $statusColors = [
                                        'draft' => 'secondary',
                                        'in_progress' => 'warning',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                    ];
                                    $statusColor = $statusColors[$period->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $statusColor }} fs-6 px-3 py-2">
                                    {{ ucfirst(str_replace('_', ' ', $period->status)) }}
                                </span>
                                @if($period->status === 'draft')
                                    <a href="{{ route('inventory.counts.sessions.create', $period->encoded_id) }}" class="btn btn-primary">
                                        <i class="bx bx-plus me-1"></i> Create Session
                                    </a>
                                @endif
                                <a href="{{ route('inventory.counts.index') }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="row mt-3">
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 info-card border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Sessions</p>
                                <h4 class="my-1 text-primary">{{ $period->sessions->count() }}</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary ms-auto">
                                <i class="bx bx-clipboard-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 info-card border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Active Sessions</p>
                                <h4 class="my-1 text-warning">{{ $period->sessions->whereIn('status', ['frozen', 'counting'])->count() }}</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-light-warning text-warning ms-auto">
                                <i class="bx bx-time-five"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 info-card border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Completed</p>
                                <h4 class="my-1 text-success">{{ $period->sessions->where('status', 'completed')->count() }}</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-light-success text-success ms-auto">
                                <i class="bx bx-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 info-card border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Days Remaining</p>
                                <h4 class="my-1 text-info">
                                    @php
                                        $daysRemaining = max(0, (int)round(now()->diffInDays($period->count_end_date, false)));
                                    @endphp
                                    {{ number_format($daysRemaining) }}
                                </h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-light-info text-info ms-auto">
                                <i class="bx bx-calendar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Period Information -->
        <div class="row mt-3">
            <div class="col-lg-8">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0 bg-transparent">
                        <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Period Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Count Type</div>
                                    <div class="info-value">
                                        <span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $period->count_type)) }}</span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Frequency</div>
                                    <div class="info-value">{{ $period->frequency ? ucfirst($period->frequency) : 'N/A' }}</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Start Date</div>
                                    <div class="info-value">
                                        <i class="bx bx-calendar me-1"></i>{{ $period->count_start_date->format('M d, Y') }}
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">End Date</div>
                                    <div class="info-value">
                                        <i class="bx bx-calendar me-1"></i>{{ $period->count_end_date->format('M d, Y') }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <div class="info-label">Location</div>
                                    <div class="info-value">
                                        <i class="bx bx-map me-1"></i>{{ $period->location ? $period->location->name : 'All Locations' }}
                                        @if($period->location && $period->location->branch)
                                            <small class="text-muted">({{ $period->location->branch->name }})</small>
                                        @endif
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Responsible Staff</div>
                                    <div class="info-value">
                                        <i class="bx bx-user me-1"></i>{{ $period->responsibleStaff->name ?? 'N/A' }}
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Branch</div>
                                    <div class="info-value">
                                        <i class="bx bx-building me-1"></i>{{ $period->branch->name ?? 'N/A' }}
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Created</div>
                                    <div class="info-value">
                                        <i class="bx bx-time me-1"></i>{{ $period->created_at->format('M d, Y H:i') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($period->notes)
                        <div class="mt-3">
                            <div class="alert alert-info mb-0">
                                <strong><i class="bx bx-note me-1"></i>Notes:</strong>
                                <p class="mb-0 mt-2">{{ $period->notes }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0 bg-transparent">
                        <h5 class="mb-0"><i class="bx bx-bar-chart me-2"></i>Progress Overview</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $totalSessions = $period->sessions->count();
                            $completedSessions = $period->sessions->where('status', 'completed')->count();
                            $progressPercentage = $totalSessions > 0 ? ($completedSessions / $totalSessions) * 100 : 0;
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Completion Rate</span>
                                <span class="fw-bold">{{ number_format($progressPercentage, 1) }}%</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progressPercentage }}%" aria-valuenow="{{ $progressPercentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span><i class="bx bx-clipboard-check me-2 text-primary"></i>Total Sessions</span>
                                <span class="badge bg-primary rounded-pill">{{ $totalSessions }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span><i class="bx bx-check-circle me-2 text-success"></i>Completed</span>
                                <span class="badge bg-success rounded-pill">{{ $completedSessions }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span><i class="bx bx-time-five me-2 text-warning"></i>In Progress</span>
                                <span class="badge bg-warning rounded-pill">{{ $period->sessions->whereIn('status', ['frozen', 'counting'])->count() }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span><i class="bx bx-file-blank me-2 text-secondary"></i>Draft</span>
                                <span class="badge bg-secondary rounded-pill">{{ $period->sessions->where('status', 'draft')->count() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Count Sessions Table -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0 bg-transparent">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bx bx-clipboard-check me-2"></i>Count Sessions</h5>
                            @if($period->status === 'draft')
                                <a href="{{ route('inventory.counts.sessions.create', $period->encoded_id) }}" class="btn btn-sm btn-primary">
                                    <i class="bx bx-plus me-1"></i> New Session
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="sessions-table" class="table table-striped table-bordered dt-responsive nowrap" style="width: 100%;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Session Number</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Snapshot Date</th>
                                        <th>Created By</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Sessions DataTable
    var sessionsTable = $('#sessions-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("inventory.counts.index") }}',
            type: 'GET',
            data: function(d) {
                d.period_id = {{ $period->id }};
            }
        },
        columns: [
            {data: 'session_number_link', name: 'session_number'},
            {data: 'location_name', name: 'location.name'},
            {data: 'status_badge', name: 'status'},
            {data: 'snapshot_date_formatted', name: 'snapshot_date'},
            {data: 'created_by_name', name: 'createdBy.name', defaultContent: 'N/A'},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        order: [[3, 'desc']], // Sort by snapshot date descending
        pageLength: 25,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            search: "Search:",
            lengthMenu: "Show _MENU_ entries per page",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            emptyTable: "No count sessions found",
            zeroRecords: "No matching count sessions found"
        }
    });
});
</script>
@endpush
@endsection
