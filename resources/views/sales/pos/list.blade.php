@extends('layouts.main')

@section('title', 'POS Sales List')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales Management', 'url' => route('sales.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'POS Sales List', 'url' => '#', 'icon' => 'bx bx-list-ul']
        ]" />

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Sales</p>
                                <h4 class="my-1 text-primary">{{ number_format($totalSales) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-receipt align-middle"></i> All POS sales</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-receipt"></i>
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
                                <p class="mb-0 text-secondary">Total Amount</p>
                                <h4 class="my-1 text-success">TZS {{ number_format($totalAmount, 2) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-dollar align-middle"></i> Total revenue</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                <i class="bx bx-dollar"></i>
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
                                <p class="mb-0 text-secondary">Today's Sales</p>
                                <h4 class="my-1 text-info">{{ number_format($todaySales) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-info"><i class="bx bx-calendar align-middle"></i> TZS {{ number_format($todayAmount, 2) }}</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                <i class="bx bx-calendar"></i>
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
                                <p class="mb-0 text-secondary">This Month</p>
                                <h4 class="my-1 text-warning">{{ number_format($thisMonthSales) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-warning"><i class="bx bx-trending-up align-middle"></i> TZS {{ number_format($thisMonthAmount, 2) }}</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                <i class="bx bx-trending-up"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">POS Sales List</h4>
                            <a href="{{ route('sales.pos.index') }}" class="btn btn-primary">
                                <i class="bx bx-plus me-1"></i> New POS Sale
                            </a>
                        </div>

                  
                        <!-- POS Sales Table -->
                        <div class="table-responsive">
                            <table id="pos-sales-table" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Sale #</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Total Amount</th>
                                        <th>Payment Method</th>
                                        <th>Operator</th>
                                        <th>Expiry Date</th>
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
<link rel="stylesheet" href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}">
<style>
    .badge {
        font-size: 0.75rem;
    }
    .table th {
        font-weight: 600;
        background-color: #f8f9fa;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.js') }}"></script>

<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#pos-sales-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("sales.pos.list") }}',
            type: 'GET',
            data: function(d) {
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
                d.payment_method = $('#payment_method').val();
            }
        },
        columns: [
            {data: 'sale_number', name: 'sale_number'},
            {data: 'customer_name', name: 'customer_name'},
            {data: 'sale_date_formatted', name: 'sale_date'},
            {data: 'items_count', name: 'items_count'},
            {data: 'total_amount_formatted', name: 'total_amount'},
            {data: 'payment_method_text', name: 'payment_method'},
            {data: 'operator_name', name: 'operator_name'},
            {data: 'expiry_date', name: 'expiry_date', orderable: false, searchable: false},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        order: [[2, 'desc']], // Sort by date descending
        pageLength: 25,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            search: "Search:",
            lengthMenu: "Show _MENU_ entries per page",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            emptyTable: "No POS sales found",
            zeroRecords: "No matching POS sales found"
        }
    });

    // Apply filters
    $('#apply_filters').click(function() {
        table.ajax.reload();
    });

    // Handle delete POS sale
    window.deletePosSale = function(encodedId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/sales/pos/' + encodedId + '/void',
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire(
                                'Deleted!',
                                response.message,
                                'success'
                            );
                            table.ajax.reload();
                        } else {
                            Swal.fire(
                                'Error!',
                                response.message,
                                'error'
                            );
                        }
                    },
                    error: function(xhr) {
                        Swal.fire(
                            'Error!',
                            'An error occurred while deleting the POS sale.',
                            'error'
                        );
                    }
                });
            }
        });
    };
});
</script>
@endpush 