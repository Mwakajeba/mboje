@extends('layouts.main')

@section('title', 'Sales Orders')

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
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Sales Orders', 'url' => '#', 'icon' => 'bx bx-cart']
        ]" />
        
        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-cart me-2"></i>Sales Orders</h5>
                                <p class="mb-0 text-muted">Manage and track all sales orders</p>
                            </div>
                            <div>
                                @can('create sales order')
                                <a href="{{ route('sales.orders.create') }}" class="btn btn-primary">
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
                                        <th>Customer</th>
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
                <h5 class="modal-title" id="viewOrderModalLabel">View Order</h5>
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
        serverSide: true,
        ajax: {
            url: '{{ route("sales.orders.index") }}',
            type: 'GET'
        },
        columns: [
            { 
                data: 'order_number',
                render: function(data, type, row) {
                    return '<a href="' + '{{ route("sales.orders.show", ":id") }}'.replace(':id', row.id) + '" class="text-primary fw-bold">' + data + '</a>';
                }
            },
            { data: 'customer_name' },
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
                    <h6>No Sales Orders Found</h6>
                    <p class="mb-0">Get started by creating your first sales order</p>
                    @can('create sales order')
                    <a href="{{ route('sales.orders.create') }}" class="btn btn-primary btn-sm mt-2">
                        <i class="bx bx-plus me-1"></i> Create Order
                    </a>
                    @endcan
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
            title: 'Move to Trash?',
            text: `Are you sure you want to move order "${name}" to trash? You can restore it later.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, move to trash!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;
            
            $.ajax({
                url: '{{ route("sales.orders.destroy", ":id") }}'.replace(':id', id),
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Moved to Trash!',
                            text: 'Order has been moved to trash successfully.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            table.ajax.reload(); // Reload DataTable
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to move order to trash', 'error');
                    }
                },
                error: function(xhr) {
                    console.error('AJAX Error:', xhr);
                    Swal.fire('Error', 'Error moving order to trash. Please try again.', 'error');
                }
            });
        });
    }

    function approveOrder(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'Are you sure you want to approve this order?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, approve it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;
            
            $.ajax({
                url: '{{ route("sales.orders.update-status", ":id") }}'.replace(':id', id),
                type: 'PATCH',
                data: {
                    _token: '{{ csrf_token() }}',
                    status: 'approved'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Approved!',
                            text: 'Order has been approved successfully.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            table.ajax.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to approve order', 'error');
                    }
                },
                error: function(xhr) {
                    console.error('AJAX Error:', xhr);
                    Swal.fire('Error', 'Error approving order. Please try again.', 'error');
                }
            });
        });
    }

    function convertToInvoice(id) {
        Swal.fire({
            title: 'Convert to Invoice',
            text: 'Are you sure you want to convert this order to an invoice?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, convert it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;
            
            $.ajax({
                url: '{{ route("sales.orders.convert-to-invoice", ":id") }}'.replace(':id', id),
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Converted Successfully!',
                            text: 'Order has been converted to invoice successfully.',
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonText: 'View Invoice',
                            cancelButtonText: 'Stay Here'
                        }).then((result) => {
                            if (result.isConfirmed && response.redirect_url) {
                                window.location.href = response.redirect_url;
                            }
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to convert order to invoice', 'error');
                    }
                },
                error: function(xhr) {
                    console.error('AJAX Error:', xhr);
                    Swal.fire('Error', 'Error converting order to invoice. Please try again.', 'error');
                }
            });
        });
    }

    function convertToDelivery(id) {
        Swal.fire({
            title: 'Convert to Delivery',
            text: 'Are you sure you want to convert this order to a delivery note?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, convert it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;
            
            $.ajax({
                url: '{{ route("sales.orders.convert-to-delivery", ":id") }}'.replace(':id', id),
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Converted Successfully!',
                            text: 'Order has been converted to delivery successfully.',
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonText: 'View Delivery',
                            cancelButtonText: 'Stay Here'
                        }).then((result) => {
                            if (result.isConfirmed && response.redirect_url) {
                                window.location.href = response.redirect_url;
                            }
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to convert order to delivery', 'error');
                    }
                },
                error: function(xhr) {
                    console.error('AJAX Error:', xhr);
                    Swal.fire('Error', 'Error converting order to delivery. Please try again.', 'error');
                }
            });
        });
    }

    // Make functions globally available
    window.deleteOrder = deleteOrder;
    window.approveOrder = approveOrder;
    window.convertToInvoice = convertToInvoice;
    window.convertToDelivery = convertToDelivery;
});
</script>
@endpush 