@extends('layouts.main')

@section('title', 'Inventory Movements')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Movements', 'url' => route('inventory.movements.index'), 'icon' => 'bx bx-transfer']
        ]" />

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h6 class="mb-0 text-uppercase">Inventory Movements</h6>
                <p class="mb-0 text-muted">Track all stock movements, adjustments, and transactions</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <!-- Location Filter -->
                <div class="d-flex align-items-center">
                    <label class="form-label me-2 mb-0">Location:</label>
                    <select id="locationFilter" class="form-select form-select-sm" style="width: 200px;">
                        <option value="">All Locations</option>
                        @foreach(Auth::user()->locations as $location)
                            <option value="{{ $location->id }}" {{ session('location_id') == $location->id ? 'selected' : '' }}>
                                {{ $location->name }} ({{ $location->branch->name ?? 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                @can('create inventory adjustments')
                <a href="{{ route('inventory.movements.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>Create Adjustment
                </a>
                @endcan
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Movement Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            <i class="bx bx-plus-circle fs-1 text-success"></i>
                        </div>
                        <h4 class="mb-1" id="totalInMovements">-</h4>
                        <p class="text-muted mb-0">Stock In</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            <i class="bx bx-minus-circle fs-1 text-danger"></i>
                        </div>
                        <h4 class="mb-1" id="totalOutMovements">-</h4>
                        <p class="text-muted mb-0">Stock Out</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            <i class="bx bx-transfer fs-1 text-warning"></i>
                        </div>
                        <h4 class="mb-1" id="totalAdjustments">-</h4>
                        <p class="text-muted mb-0">Adjustments</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            <i class="bx bx-list-ul fs-1 text-info"></i>
                        </div>
                        <h4 class="mb-1" id="totalMovements">-</h4>
                        <p class="text-muted mb-0">Total Movements</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">
                            <i class="bx bx-transfer me-2"></i>Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @can('create inventory adjustments')
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('inventory.movements.create', ['defaultMovementType' => 'adjustment_in']) }}" class="btn btn-outline-success w-100">
                                    <i class="bx bx-plus-circle me-1"></i>Stock In
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('inventory.movements.create', ['defaultMovementType' => 'adjustment_out']) }}" class="btn btn-outline-danger w-100">
                                    <i class="bx bx-minus-circle me-1"></i>Stock Out
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('inventory.movements.create') }}" class="btn btn-outline-primary w-100">
                                    <i class="bx bx-transfer me-1"></i>Custom Adjustment
                                </a>
                            </div>
                            @endcan
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary w-100">
                                    <i class="bx bx-package me-1"></i>Back to Inventory
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="movementsTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Item</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Unit Cost</th>
                                <th>Total Value</th>
                                <th>Balance After</th>
                                <th>Location</th>
                                <th>User</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    $('#movementsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('inventory.movements.index') }}",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            error: function(xhr, error, code) {
                console.log('DataTables Ajax Error:', error, code);
                console.log('Response:', xhr.responseText);
            }
        },
        columns: [
            { data: 'movement_date', name: 'movement_date' },
            { data: 'reference', name: 'reference' },
            { data: 'item_name', name: 'item.name' },
            { data: 'movement_type_badge', name: 'movement_type', orderable: false },
            { data: 'quantity_formatted', name: 'quantity' },
            { data: 'unit_cost_formatted', name: 'unit_cost' },
            { data: 'total_cost_formatted', name: 'total_cost' },
            { data: 'balance_after_formatted', name: 'balance_after' },
            { data: 'location_name', name: 'location.name', orderable: false },
            { data: 'user_name', name: 'user.name' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: '<div class="text-center p-4"><i class="bx bx-transfer font-24 text-muted"></i><p class="text-muted mt-2">No movements found.</p></div>'
        },
        columnDefs: [
            {
                targets: [3, 10], // movement_type_badge and actions columns
                className: 'text-center'
            },
            {
                targets: [4, 5, 6, 7], // quantity and cost columns
                className: 'text-end'
            }
        ]
    });

    // Populate statistics
    $('#totalMovements').text('{{ $statistics['total_movements'] }}');
    $('#totalInMovements').text('{{ $statistics['stock_in'] }}');
    $('#totalOutMovements').text('{{ $statistics['stock_out'] }}');
    $('#totalAdjustments').text('{{ $statistics['adjustments'] }}');

    // Handle location filter
    $('#locationFilter').on('change', function() {
        const locationId = $(this).val();
        
        // Set session location via AJAX
        if (locationId) {
            $.ajax({
                url: '/set-location/' + locationId,
                type: 'GET',
                success: function(response) {
                    console.log('Location set to:', response);
                    // Reload the DataTable to show filtered results
                    $('#movementsTable').DataTable().ajax.reload();
                },
                error: function(xhr, status, error) {
                    console.error('Error setting location:', error);
                }
            });
        } else {
            // Clear location filter - reload with all locations
            $('#movementsTable').DataTable().ajax.reload();
        }
    });

    // Handle delete with SweetAlert
    $(document).on('click', '.delete-movement', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        const reference = $(this).data('reference');
        
        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete the movement "${reference}". This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>
@endpush
@endsection
