@extends('layouts.main')

@section('title', 'Goods Receipt Notes (GRN)')

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
    
    .bg-gradient-secondary {
        background: linear-gradient(45deg, #6c757d, #5c636a) !important;
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
            ['label' => 'Purchases', 'url' => route('purchases.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'GRN', 'url' => '#', 'icon' => 'bx bx-package']
        ]" />
        
        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-package me-2"></i>Goods Receipt Notes (GRN)</h5>
                                <p class="mb-0 text-muted">Manage and track all goods receipt notes</p>
                            </div>
                    <div>
                        @can('create purchase orders')
                        <a href="{{ route('purchases.grn.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>Create New GRN
                        </a>
                        @endcan
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
                                <p class="mb-0 text-secondary">Total GRNs</p>
                                <h4 class="my-1 text-primary">{{ $stats['total'] }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-package align-middle"></i> All receipts</span>
                                </p>
                                </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-package"></i>
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
                                <h4 class="my-1 text-success">{{ $stats['completed'] }}</h4>
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
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Pending</p>
                                <h4 class="my-1 text-warning">{{ $stats['pending'] }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-warning"><i class="bx bx-time align-middle"></i> In progress</span>
                                </p>
                                </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                <i class="bx bx-time"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-secondary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Drafts</p>
                                <h4 class="my-1 text-secondary">{{ $stats['draft'] }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-secondary"><i class="bx bx-edit align-middle"></i> In progress</span>
                                </p>
                                </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-secondary text-white ms-auto">
                                <i class="bx bx-edit"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-body">
                    <div class="table-responsive">
                            <table class="table table-hover" id="grns-table">
                                <thead class="table-light">
                                <tr>
                                    <th>GRN Number</th>
                                    <th>Purchase Order</th>
                                    <th>Supplier</th>
                                    <th>Receipt Date</th>
                                    <th>Received By</th>
                                    <th>Items</th>
                                    <th>Total Amount</th>
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
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#grns-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("purchases.grn.index") }}',
            type: 'GET'
        },
        columns: [
            {data: 'grn_number', name: 'grn_number'},
            {data: 'purchase_order', name: 'purchase_order'},
            {data: 'supplier_name', name: 'supplier_name'},
            {data: 'receipt_date_formatted', name: 'receipt_date'},
            {data: 'received_by_name', name: 'received_by_name'},
            {data: 'items_count', name: 'items_count'},
            {data: 'total_amount_formatted', name: 'total_amount'},
            {data: 'status_badge', name: 'status'},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        order: [[3, 'desc']], // Sort by receipt date descending
        pageLength: 25,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            search: "Search:",
            lengthMenu: "Show _MENU_ entries per page",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            emptyTable: "No goods receipt notes found",
            zeroRecords: "No matching goods receipt notes found"
        }
    });

    // Handle delete GRN
    $(document).on('click', '.delete-grn-btn', function() {
        const grnId = $(this).data('grn-id');
        const grnNumber = $(this).data('grn-number') || 'this GRN';
        
        if ($(this).prop('disabled')) {
                return; // Disabled state, do nothing
            }
        
            Swal.fire({
                title: 'Delete ' + grnNumber + '?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("purchases.grn.destroy", ":id") }}'.replace(':id', grnId),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message || 'GRN deleted successfully.', 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error!', response.message || 'Failed to delete GRN.', 'error');
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while deleting the GRN.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error!', errorMessage, 'error');
                }
            });
            }
        });
    });
});
</script>
@endpush
