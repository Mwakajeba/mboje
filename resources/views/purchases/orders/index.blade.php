@extends('layouts.main')

@section('title', 'Purchase Orders')

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
            ['label' => 'Purchase Orders', 'url' => '#', 'icon' => 'bx bx-cart']
        ]" />
        
        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-cart me-2"></i>Purchase Orders</h5>
                                <p class="mb-0 text-muted">Manage and track all purchase orders</p>
                            </div>
                            <div>
                                @can('create purchase orders')
                                <a href="{{ route('purchases.orders.create-from-stock') }}" class="btn btn-outline-warning me-2">
                                    <i class="bx bx-error me-1"></i>Create from Under Stock
                                </a>
                                <a href="{{ route('purchases.orders.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>Create Order
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
                                <p class="mb-0 text-secondary">Total Orders</p>
                                <h4 class="my-1 text-primary" id="total-orders">{{ $stats['total'] }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-cart align-middle"></i> All orders</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-cart"></i>
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
                                <p class="mb-0 text-secondary">Draft</p>
                                <h4 class="my-1 text-secondary" id="draft-count">{{ $stats['draft'] }}</h4>
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
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Pending Approval</p>
                                <h4 class="my-1 text-warning" id="pending-approval-count">{{ $stats['pending_approval'] }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-warning"><i class="bx bx-time align-middle"></i> Awaiting</span>
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
                <div class="card radius-10 border-start border-0 border-3 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Approved</p>
                                <h4 class="my-1 text-success" id="approved-count">{{ $stats['approved'] }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-check-circle align-middle"></i> Confirmed</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                <i class="bx bx-check-circle"></i>
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
                <div class="d-lg-flex align-items-center mb-4 gap-3">
                            <div class="position-relative flex-grow-1">
                        <input type="text" class="form-control ps-5 radius-30" placeholder="Search Orders..." id="search-input">
                        <span class="position-absolute top-50 product-show translate-middle-y"><i class="bx bx-search"></i></span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0" id="orders-table">
                        <thead class="table-light">
                            <tr>
                                <th>Order #</th>
                                <th>Supplier</th>
                                <th>Order Date</th>
                                <th>Expected Delivery</th>
                                <th>Status</th>
                                <th>Total Amount</th>
                                        <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                    </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Order Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1" aria-labelledby="viewOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewOrderModalLabel">View Purchase Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="order-details">
                <!-- Order details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    const table = $('#orders-table').DataTable({
        processing: true,
        serverSide: false, // We'll use client-side processing for now
        ajax: {
            url: '{{ route("purchases.orders.index") }}',
            type: 'GET',
            dataSrc: function(json) {
                return json.orders || [];
            }
        },
        columns: [
            { 
                data: 'order_number',
                render: function(data, type, row) {
                    return '<a href="' + '{{ route("purchases.orders.show", ":id") }}'.replace(':id', row.encoded_id) + '" class="text-primary fw-bold">' + data + '</a>';
                }
            },
            { data: 'supplier_name' },
            { data: 'formatted_date' },
            { data: 'formatted_delivery_date' },
            { data: 'status_badge' },
            { 
                data: 'formatted_total',
                className: 'text-end'
            },
            { 
                data: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        order: [[2, 'desc']], // Sort by date descending
        pageLength: 10,
        responsive: true,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            search: "Search Orders:",
            lengthMenu: "Show _MENU_ orders per page",
            info: "Showing _START_ to _END_ of _TOTAL_ orders",
            infoEmpty: "Showing 0 to 0 of 0 orders",
            infoFiltered: "(filtered from _MAX_ total orders)",
            emptyTable: `
                <div class="text-center text-muted py-4">
                    <i class="bx bx-cart fs-1 d-block mb-2"></i>
                    <h6>No Purchase Orders Found</h6>
                    <p class="mb-0">Get started by creating your first purchase order</p>
                    <a href="{{ route('purchases.orders.create') }}" class="btn btn-primary btn-sm mt-2">
                        <i class="bx bx-plus me-1"></i> Create Order
                    </a>
                </div>
            `
        }
    });

    // Search functionality
    $('#search-input').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Refresh table when needed
    function refreshTable() {
        table.ajax.reload(null, false);
    }

    // Global function to be called from other scripts
    window.refreshOrdersTable = refreshTable;
    
    function deleteOrder(id, name) {
        Swal.fire({
            title: 'Delete Order?',
            text: `Are you sure you want to permanently delete order "${name}"? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete permanently!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;
            
            $.ajax({
                url: '{{ route("purchases.orders.destroy", ":id") }}'.replace(':id', id),
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        });
                        refreshTable();
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred while deleting the order.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    Swal.fire('Error!', errorMessage, 'error');
                }
            });
        });
    }

    // Make deleteOrder globally available
    window.deleteOrder = deleteOrder;
});
</script>
@endpush 