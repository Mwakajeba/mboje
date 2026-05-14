@extends('layouts.main')

@section('title', 'Inventory Transfers')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Transfers', 'url' => route('inventory.transfers.index'), 'icon' => 'bx bx-transfer']
        ]" />

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h6 class="mb-0 text-uppercase">Inventory Transfers</h6>
                <p class="mb-0 text-muted">Track inter-branch item transfers</p>
            </div>
            @can('create inventory adjustments')
            <a href="{{ route('inventory.transfers.create') }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i>Create Transfer
            </a>
            @endcan
        </div>

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Transfer Statistics -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            <i class="bx bx-transfer fs-1 text-info"></i>
                        </div>
                        <h4 class="mb-1">{{ $statistics['total_transfers'] }}</h4>
                        <p class="text-muted mb-0">Total Transfers</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            <i class="bx bx-down-arrow-circle fs-1 text-success"></i>
                        </div>
                        <h4 class="mb-1">{{ $statistics['transfers_in'] }}</h4>
                        <p class="text-muted mb-0">Transfers In</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            <i class="bx bx-up-arrow-circle fs-1 text-warning"></i>
                        </div>
                        <h4 class="mb-1">{{ $statistics['transfers_out'] }}</h4>
                        <p class="text-muted mb-0">Transfers Out</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="transfersTable" class="table table-striped table-bordered" style="width:100%">
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

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    $('#transfersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('inventory.transfers.index') }}",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        },
        columns: [
            { data: 'movement_date', name: 'movement_date' },
            { data: 'reference', name: 'reference' },
            { data: 'item_name', name: 'item.name' },
            { data: 'transfer_type_badge', name: 'movement_type', orderable: false },
            { data: 'quantity_formatted', name: 'quantity' },
            { data: 'unit_cost_formatted', name: 'unit_cost' },
            { data: 'total_cost_formatted', name: 'total_cost' },
            { data: 'balance_after_formatted', name: 'balance_after' },
            { data: 'user_name', name: 'user.name' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true
    });

    // Handle delete button click with SweetAlert
    $(document).on('click', '.delete-transfer', function() {
        const deleteUrl = $(this).data('url');
        const reference = $(this).data('reference');
        
        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete transfer "${reference}". This action cannot be undone and will reverse all stock changes.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while we delete the transfer.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Send AJAX request
                $.ajax({
                    url: deleteUrl,
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        _method: 'DELETE'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                // Reload the DataTable
                                $('#transfersTable').DataTable().ajax.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Failed to delete transfer. Please try again.',
                            icon: 'error'
                        });
                    }
                });
            }
        });
    });
});
</script>
@endpush
@endsection 