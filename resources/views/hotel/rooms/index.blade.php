@extends('layouts.main')

@section('title', 'Room Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel & Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-building-house'],
            ['label' => 'Room Management', 'url' => '#', 'icon' => 'bx bx-bed']
        ]" />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Summary Cards (moved above table) -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded bg-primary bg-gradient">
                                    <div class="avatar-title text-white">
                                        <i class="bx bx-bed font-size-24"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-uppercase fw-medium text-muted mb-0">Total Rooms</p>
                                <h4 class="mb-0">{{ $totalRooms }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded bg-success bg-gradient">
                                    <div class="avatar-title text-white">
                                        <i class="bx bx-check-circle font-size-24"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-uppercase fw-medium text-muted mb-0">Available</p>
                                <h4 class="mb-0">{{ $availableRooms }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded bg-warning bg-gradient">
                                    <div class="avatar-title text-white">
                                        <i class="bx bx-time font-size-24"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-uppercase fw-medium text-muted mb-0">Occupied</p>
                                <h4 class="mb-0">{{ $occupiedRooms }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded bg-danger bg-gradient">
                                    <div class="avatar-title text-white">
                                        <i class="bx bx-wrench font-size-24"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-uppercase fw-medium text-muted mb-0">Maintenance</p>
                                <h4 class="mb-0">{{ $maintenanceRooms }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title">Room Management</h4>
                                <p class="card-subtitle text-muted">Manage hotel rooms, availability, and pricing</p>
                            </div>
                            <div>
                                <a href="{{ route('rooms.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i> Add New Room
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Check-in</label>
                                <input type="date" id="filter_check_in" class="form-control" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Check-out</label>
                                <input type="date" id="filter_check_out" class="form-control" />
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button id="apply_filters" class="btn btn-outline-primary w-100"><i class="bx bx-filter me-1"></i>Apply</button>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button id="clear_filters" class="btn btn-outline-secondary w-100"><i class="bx bx-x me-1"></i>Clear</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="rooms-table" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Room Number</th>
                                        <th>Room Type</th>
                                        <th>Status</th>
                                        <th>Rate (TSh)</th>
                                        <th>Capacity</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- DataTables will populate this -->
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

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    function toISODate(d) {
        const dd = String(d.getDate()).padStart(2, '0');
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const yyyy = d.getFullYear();
        return `${yyyy}-${mm}-${dd}`;
    }

    // Set defaults: today and tomorrow in ISO for native date inputs
    const today = new Date();
    const tomorrow = new Date(today.getFullYear(), today.getMonth(), today.getDate() + 1);
    $('#filter_check_in').val(toISODate(today));
    $('#filter_check_out').val(toISODate(tomorrow));

    function getFilters() {
        return {
            check_in: $('#filter_check_in').val(),
            check_out: $('#filter_check_out').val()
        };
    }

    function updateSummaryFromAjax(json) {
        if (json && json.summary) {
            if (typeof json.summary.total !== 'undefined') {
                $('p:contains("Total Rooms")').closest('.card').find('h4').text(json.summary.total);
            }
            if (typeof json.summary.available !== 'undefined' && json.summary.available !== null) {
                $('p:contains("Available")').closest('.card').find('h4').text(json.summary.available);
            }
            if (typeof json.summary.occupied !== 'undefined' && json.summary.occupied !== null) {
                $('p:contains("Occupied")').closest('.card').find('h4').text(json.summary.occupied);
            }
            if (typeof json.summary.maintenance !== 'undefined') {
                $('p:contains("Maintenance")').closest('.card').find('h4').text(json.summary.maintenance);
            }
        }
    }

    // Initialize DataTables
    const table = $('#rooms-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('rooms.index') }}",
            type: 'GET',
            data: function(d) {
                const f = getFilters();
                if (f.check_in && f.check_out) {
                    d.check_in = f.check_in;
                    d.check_out = f.check_out;
                }
            }
        },
        columns: [
            { data: 'room_info', name: 'room_number' },
            { data: 'room_type_badge', name: 'room_type' },
            { data: 'status_info', name: 'status' },
            { data: 'rate_formatted', name: 'rate_per_night' },
            { data: 'capacity_info', name: 'capacity' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'asc']],
        pageLength: 25,
        responsive: true,
        language: {
            processing: "Loading rooms...",
            emptyTable: "No rooms found",
            zeroRecords: "No rooms match your search criteria"
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        drawCallback: function(settings) {
            // Re-initialize tooltips after table redraw
            $('[data-bs-toggle="tooltip"]').tooltip();
        },
        initComplete: function(settings, json) {
            updateSummaryFromAjax(json);
        }
    });

    $('#apply_filters').on('click', function(e) {
        e.preventDefault();
        table.ajax.reload(function(json) {
            updateSummaryFromAjax(json);
        });
    });

    $('#clear_filters').on('click', function(e) {
        e.preventDefault();
        $('#filter_check_in').val('');
        $('#filter_check_out').val('');
        table.ajax.reload(function(json) {
            updateSummaryFromAjax(json);
        });
    });

    // Auto-apply once on load with default dates
    $('#apply_filters').trigger('click');
});

// Delete room function
function deleteRoom(roomId, roomNumber) {
    Swal.fire({
        title: 'Delete Room?',
        text: `Are you sure you want to delete room ${roomNumber}? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Create and submit delete form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/rooms/${roomId}`;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';
            form.appendChild(methodField);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
@endpush
