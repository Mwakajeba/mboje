@extends('layouts.main')

@section('title', 'Equipment Master Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Equipment Master', 'url' => '#', 'icon' => 'bx bx-package']
        ]" />
        <h6 class="mb-0 text-uppercase">EQUIPMENT MASTER MANAGEMENT</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="card-title d-flex align-items-center">
                                <div><i class="bx bx-package me-1 font-22 text-primary"></i></div>
                                <h5 class="mb-0 text-primary">Equipment Records</h5>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('rental-event-equipment.equipment.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i> Add New Equipment
                                </a>
                            </div>
                        </div>
                        <hr />

                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <!-- Filter Section -->
                        <div class="card border-info mb-4">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">
                                    <i class="bx bx-filter me-2"></i> Filter Equipment
                                </h6>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="{{ route('rental-event-equipment.equipment.index') }}" id="filterForm">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label for="category_id" class="form-label fw-bold">Category</label>
                                            <select class="form-select" id="category_id" name="category_id">
                                                <option value="">All Categories</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}" {{ $selectedCategory == $category->id ? 'selected' : '' }}>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
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
                                                <a href="{{ route('rental-event-equipment.equipment.index') }}" class="btn btn-outline-secondary">
                                                    <i class="bx bx-refresh me-1"></i> Clear
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="equipmentTable" class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Equipment Code</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Status Breakdown (Quantities)</th>
                                        <th>Cost Info</th>
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

    .btn-group .btn {
        margin-right: 2px;
    }

    .btn-group .btn:last-child {
        margin-right: 0;
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
    window.equipmentTable = $('#equipmentTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("rental-event-equipment.equipment.data") }}',
            type: 'GET',
            data: function(d) {
                d.category_id = $('#category_id').val();
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
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'status_breakdown', name: 'status_breakdown', orderable: false, searchable: false },
            { data: 'cost_info', name: 'cost_info', orderable: false, searchable: false },
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
        if (window.equipmentTable) {
            window.equipmentTable.ajax.reload(function() {
                $('#filterBtn').prop('disabled', false).html('<i class="bx bx-search me-1"></i> Filter');
            }, false);
        }
    }

    // Filter button click handler
    $('#filterBtn').on('click', function() {
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Filtering...');
        reloadDataTable();
    });

    // Delete confirmation using SweetAlert
    window.confirmDelete = function(equipmentName, deleteUrl) {
        Swal.fire({
            title: 'Delete Equipment?',
            html: '<div class="text-start">' +
                '<p class="mb-2">Are you sure you want to delete the equipment <strong>"' + equipmentName + '"</strong>?</p>' +
                '<div class="alert alert-warning mt-2 mb-2">' +
                '<i class="bx bx-info-circle me-1"></i>' +
                '<strong>Warning:</strong> This action cannot be undone. The equipment record and all associated data will be permanently deleted.' +
                '</div>' +
                '<p class="text-danger mb-0"><strong>This action cannot be undone!</strong></p>' +
                '</div>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bx bx-trash me-1"></i> Yes, Delete Equipment',
            cancelButtonText: '<i class="bx bx-x me-1"></i> Cancel',
            reverseButtons: true,
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Create and submit delete form
                var form = $('<form>', {
                    'method': 'POST',
                    'action': deleteUrl
                });
                form.append($('<input>', {
                    'type': 'hidden',
                    'name': '_token',
                    'value': '{{ csrf_token() }}'
                }));
                form.append($('<input>', {
                    'type': 'hidden',
                    'name': '_method',
                    'value': 'DELETE'
                }));
                $('body').append(form);
                form.submit();
            }
        });
    };
});
</script>
@endpush
