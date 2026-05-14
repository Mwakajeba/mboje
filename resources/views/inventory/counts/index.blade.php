@extends('layouts.main')

@section('title', 'Inventory Count Management')

@push('styles')
<style>
    .widgets-icons-2 {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #ededed;
        font-size: 27px;
    }
    
    .bg-gradient-primary {
        background: linear-gradient(45deg, #0d6efd, #0a58ca) !important;
    }
    
    .bg-gradient-success {
        background: linear-gradient(45deg, #198754, #146c43) !important;
    }
    
    .bg-gradient-info {
        background: linear-gradient(45deg, #0dcaf0, #0aa2c0) !important;
    }
    
    .bg-gradient-warning {
        background: linear-gradient(45deg, #ffc107, #ffb300) !important;
    }
    
    .bg-gradient-danger {
        background: linear-gradient(45deg, #dc3545, #bb2d3b) !important;
    }
    
    .bg-gradient-purple {
        background: linear-gradient(45deg, #6f42c1, #5a32a3) !important;
    }
    
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .table-light {
        background-color: #f8f9fa;
    }
    
    .radius-10 {
        border-radius: 10px;
    }
    
    .border-start {
        border-left-width: 3px !important;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Management', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Inventory Count', 'url' => '#', 'icon' => 'bx bx-clipboard-check']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-clipboard-check me-2"></i>Inventory Count Management</h5>
                                <p class="mb-0 text-muted">Manage stock counting, variance analysis, and adjustments</p>
                            </div>
                            <div>
                                <a href="{{ route('inventory.counts.periods.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>New Count Period
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Stats -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Sessions</p>
                                <h4 class="my-1 text-primary" id="total-sessions">{{ number_format($totalSessions) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-clipboard-check align-middle"></i> All sessions</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-clipboard-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Active Sessions</p>
                                <h4 class="my-1 text-warning" id="active-sessions">{{ number_format($activeSessions) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-warning"><i class="bx bx-time-five align-middle"></i> In progress</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                <i class="bx bx-time-five"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Completed</p>
                                <h4 class="my-1 text-success" id="completed-sessions">{{ number_format($completedSessions) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-check-circle align-middle"></i> Finished</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                <i class="bx bx-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-danger">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Pending Adjustments</p>
                                <h4 class="my-1 text-danger" id="pending-adjustments">{{ number_format($pendingAdjustments) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-danger"><i class="bx bx-error-circle align-middle"></i> Awaiting approval</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-danger text-white ms-auto">
                                <i class="bx bx-error-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Count Periods Section -->
        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <h5 class="mb-0"><i class="bx bx-calendar me-2"></i>Count Periods</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="count-periods-table" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Period Name</th>
                                        <th>Type</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Location</th>
                                        <th>Sessions</th>
                                        <th>Status</th>
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

        <!-- Count Sessions Section -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <h5 class="mb-0"><i class="bx bx-clipboard-check me-2"></i>Count Sessions</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="count-sessions-table" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Session Number</th>
                                        <th>Period</th>
                                        <th>Location</th>
                                        <th>Snapshot Date</th>
                                        <th>Status</th>
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
    // Initialize Count Periods DataTable
    var periodsTable = $('#count-periods-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("inventory.counts.index") }}',
            type: 'GET',
            data: function(d) {
                d.type = 'periods';
            }
        },
        columns: [
            {data: 'period_name_link', name: 'period_name'},
            {data: 'count_type_formatted', name: 'count_type'},
            {data: 'count_start_date_formatted', name: 'count_start_date'},
            {data: 'count_end_date_formatted', name: 'count_end_date'},
            {data: 'location_name', name: 'location.name'},
            {data: 'sessions_count', name: 'sessions_count', orderable: false, searchable: false},
            {data: 'status_badge', name: 'status'},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        order: [[2, 'desc']], // Sort by start date descending
        pageLength: 25,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            search: "Search:",
            lengthMenu: "Show _MENU_ entries per page",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            emptyTable: "No count periods found",
            zeroRecords: "No matching count periods found"
        }
    });

    // Initialize Count Sessions DataTable
    var sessionsTable = $('#count-sessions-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("inventory.counts.index") }}',
            type: 'GET'
        },
        columns: [
            {data: 'session_number_link', name: 'session_number'},
            {data: 'period_name', name: 'period.period_name'},
            {data: 'location_name', name: 'location.name'},
            {data: 'snapshot_date_formatted', name: 'snapshot_date'},
            {data: 'status_badge', name: 'status'},
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
