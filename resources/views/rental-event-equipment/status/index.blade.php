@extends('layouts.main')

@section('title', 'Equipment Status Tracking')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Equipment Status', 'url' => '#', 'icon' => 'bx bx-check-circle']
        ]" />
        <h6 class="mb-0 text-uppercase">EQUIPMENT STATUS TRACKING</h6>
        <hr />

        <!-- Status Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h4 class="text-success">{{ $statusCounts['available']->count ?? 0 }}</h4>
                        <small class="text-muted">Available</small>
                        <div class="mt-2">
                            <small class="text-muted">Qty: {{ $statusCounts['available']->total_available ?? 0 }}</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h4 class="text-warning">{{ $statusCounts['reserved']->count ?? 0 }}</h4>
                        <small class="text-muted">Reserved</small>
                        <div class="mt-2">
                            <small class="text-muted">Qty: {{ $statusCounts['reserved']->total_available ?? 0 }}</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h4 class="text-info">{{ $statusCounts['on_rent']->count ?? 0 }}</h4>
                        <small class="text-muted">On Rent</small>
                        <div class="mt-2">
                            <small class="text-muted">Qty: {{ $statusCounts['on_rent']->total_available ?? 0 }}</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <h4 class="text-primary">{{ $statusCounts['in_event_use']->count ?? 0 }}</h4>
                        <small class="text-muted">In Event Use</small>
                        <div class="mt-2">
                            <small class="text-muted">Qty: {{ $statusCounts['in_event_use']->total_available ?? 0 }}</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <h4 class="text-danger">{{ $statusCounts['under_repair']->count ?? 0 }}</h4>
                        <small class="text-muted">Under Repair</small>
                        <div class="mt-2">
                            <small class="text-muted">Qty: {{ $statusCounts['under_repair']->total_available ?? 0 }}</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-dark">
                    <div class="card-body text-center">
                        <h4 class="text-dark">{{ $statusCounts['lost']->count ?? 0 }}</h4>
                        <small class="text-muted">Lost</small>
                        <div class="mt-2">
                            <small class="text-muted">Qty: {{ $statusCounts['lost']->total_available ?? 0 }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="card-title d-flex align-items-center">
                                <div><i class="bx bx-check-circle me-1 font-22 text-info"></i></div>
                                <h5 class="mb-0 text-info">Equipment by Status</h5>
                            </div>
                        </div>
                        <hr />

                        <!-- Filter Section -->
                        <div class="card border-info mb-4">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">
                                    <i class="bx bx-filter me-2"></i> Filter by Status
                                </h6>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="{{ route('rental-event-equipment.status.index') }}" id="filterForm">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label for="status" class="form-label fw-bold">Status</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="">All Statuses</option>
                                                <option value="available" {{ $selectedStatus == 'available' ? 'selected' : '' }}>Available</option>
                                                <option value="reserved" {{ $selectedStatus == 'reserved' ? 'selected' : '' }}>Reserved</option>
                                                <option value="on_rent" {{ $selectedStatus == 'on_rent' ? 'selected' : '' }}>On Rent</option>
                                                <option value="in_event_use" {{ $selectedStatus == 'in_event_use' ? 'selected' : '' }}>In Event Use</option>
                                                <option value="under_repair" {{ $selectedStatus == 'under_repair' ? 'selected' : '' }}>Under Repair</option>
                                                <option value="lost" {{ $selectedStatus == 'lost' ? 'selected' : '' }}>Lost</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary" id="filterBtn">
                                                    <i class="bx bx-search me-1"></i> Filter
                                                </button>
                                                <a href="{{ route('rental-event-equipment.status.index') }}" class="btn btn-outline-secondary">
                                                    <i class="bx bx-refresh me-1"></i> Clear
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="statusTable" class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Equipment Code</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Status Breakdown (Quantities)</th>
                                        <th>Actions</th>
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
@endsection

@push('styles')
<style>
    .table th {
        font-weight: 600;
        font-size: 0.875rem;
    }

    .table td {
        vertical-align: middle;
    }

    .card-title {
        font-size: 1rem;
        font-weight: 600;
    }

    .badge {
        font-size: 0.75rem;
        padding: 0.5em 0.75em;
    }

    .status-breakdown {
        min-width: 200px;
    }

    .status-breakdown .badge {
        display: inline-block;
        margin: 2px 0;
        font-size: 0.7rem;
        padding: 0.4em 0.6em;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    window.statusTable = $('#statusTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("rental-event-equipment.status.data") }}',
            type: 'GET',
            data: function(d) {
                d.status = $('#status').val();
            },
            error: function(xhr, status, error) {
                console.error('DataTables error:', error);
                console.error('Response:', xhr.responseText);
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'equipment_code', name: 'equipment_code' },
            { data: 'name', name: 'name' },
            { data: 'category_name', name: 'category_name' },
            { data: 'status_breakdown', name: 'status_breakdown', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[1, 'asc']],
        responsive: true,
        language: {
            processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>'
        }
    });

    // Reload DataTable with new filter parameters
    function reloadDataTable() {
        if (window.statusTable) {
            window.statusTable.ajax.reload(function() {
                $('#filterBtn').prop('disabled', false).html('<i class="bx bx-search me-1"></i> Filter');
            }, false);
        }
    }

    // Filter button click handler
    $('#filterBtn').on('click', function() {
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Filtering...');
        reloadDataTable();
    });
});
</script>
@endpush
