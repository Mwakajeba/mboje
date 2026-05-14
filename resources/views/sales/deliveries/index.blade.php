@extends('layouts.main')

@section('title', 'Deliveries')

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
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Deliveries', 'url' => '#', 'icon' => 'bx bx-package']
        ]" />
        
        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-package me-2"></i>Deliveries</h5>
                                <p class="mb-0 text-muted">Manage and track all deliveries</p>
                            </div>
                            <div>
                                @can('create delivery')
                                <a href="{{ route('sales.deliveries.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>Create Delivery
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
                                <p class="mb-0 text-secondary">Total Deliveries</p>
                                <h4 class="my-1 text-primary">{{ $stats['total'] }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-package align-middle"></i> All deliveries</span>
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
                <div class="card radius-10 border-start border-0 border-3 border-secondary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Draft</p>
                                <h4 class="my-1 text-secondary">{{ $stats['draft'] }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-secondary"><i class="bx bx-file-blank align-middle"></i> Not started</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-secondary text-white ms-auto">
                                <i class="bx bx-file-blank"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">In Progress</p>
                                <h4 class="my-1 text-info">{{ $stats['picking'] + $stats['packed'] }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-info"><i class="bx bx-package align-middle"></i> Processing</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
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
                                <p class="mb-0 text-secondary">Delivered</p>
                                <h4 class="my-1 text-success">{{ $stats['delivered'] }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-check-circle align-middle"></i> Completed</span>
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

        <!-- Deliveries Table -->
        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="deliveries-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Delivery #</th>
                                        <th>Customer</th>
                                        <th>Order #</th>
                                        <th>Delivery Date</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Progress</th>
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

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    var table = $('#deliveries-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("sales.deliveries.index") }}',
        columns: [
            {
                data: 'delivery_number',
                name: 'delivery_number',
                render: function(data, type, row) {
                    return '<a href="' + '{{ route("sales.deliveries.show", ":id") }}'.replace(':id', row.id) + '" class="text-primary fw-bold">' + data + '</a>';
                }
            },
            { data: 'customer_name', name: 'customer_name' },
            { data: 'order_number', name: 'order_number' },
            { data: 'formatted_date', name: 'delivery_date' },
            { data: 'delivery_type_text', name: 'delivery_type' },
            { 
                data: 'status_badge', 
                name: 'status',
                orderable: false,
                searchable: false
            },
            { 
                data: 'progress', 
                name: 'progress',
                orderable: false,
                searchable: false
            },
            { 
                data: 'actions', 
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        order: [[3, 'desc']],
        pageLength: 25,
        responsive: true
    });

    // Start Picking
    window.startPicking = function(deliveryId) {
        Swal.fire({
            title: 'Start Picking?',
            text: 'This will start the picking process for this delivery.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, start picking!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("sales.deliveries.start-picking", ":id") }}'.replace(':id', deliveryId),
                    type: 'PATCH',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Success', response.message, 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'An error occurred while starting picking.', 'error');
                    }
                });
            }
        });
    };

    // Complete Picking
    window.completePicking = function(deliveryId) {
        Swal.fire({
            title: 'Complete Picking?',
            text: 'This will mark all items as picked and move to packing.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, complete picking!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("sales.deliveries.complete-picking", ":id") }}'.replace(':id', deliveryId),
                    type: 'PATCH',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Success', response.message, 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        // Mark as handled to prevent global error handler
                        xhr.errorHandled = true;
                        Swal.fire('Error', 'An error occurred while completing picking.', 'error');
                    }
                });
            }
        });
    };

    // Start Delivery
    window.startDelivery = function(deliveryId) {
        Swal.fire({
            title: 'Start Delivery?',
            text: 'This will start the delivery process.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, start delivery!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("sales.deliveries.start-delivery", ":id") }}'.replace(':id', deliveryId),
                    type: 'PATCH',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Success', response.message, 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'An error occurred while starting delivery.', 'error');
                    }
                });
            }
        });
    };

    // Complete Delivery
    window.completeDelivery = function(deliveryId) {
        Swal.fire({
            title: 'Complete Delivery?',
            text: 'This will mark the delivery as completed and update stock.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, complete delivery!'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Received By',
                    input: 'text',
                    inputLabel: 'Name of person who received the delivery',
                    inputPlaceholder: 'Enter recipient name',
                    showCancelButton: true,
                    confirmButtonText: 'Complete Delivery',
                    showLoaderOnConfirm: true,
                    preConfirm: (receivedByName) => {
                        return $.ajax({
                            url: '{{ route("sales.deliveries.complete-delivery", ":id") }}'.replace(':id', deliveryId),
                            type: 'PATCH',
                            data: {
                                _token: '{{ csrf_token() }}',
                                received_by_name: receivedByName
                            }
                        });
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (result.value.success) {
                            Swal.fire('Success', result.value.message, 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error', result.value.message, 'error');
                        }
                    }
                });
            }
        });
    };

    // Delete Delivery
    window.deleteDelivery = function(deliveryId, deliveryNumber) {
        Swal.fire({
            title: 'Delete Delivery?',
            text: `Are you sure you want to delete delivery ${deliveryNumber}? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("sales.deliveries.destroy", ":id") }}'.replace(':id', deliveryId),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'An error occurred while deleting the delivery.', 'error');
                    }
                });
            }
        });
    };
});
</script>
@endpush
@endsection 