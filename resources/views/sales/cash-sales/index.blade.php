@extends('layouts.main')

@section('title', 'Cash Sales')

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
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-store'],
            ['label' => 'Cash Sales', 'url' => '#', 'icon' => 'bx bx-dollar-circle']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-dollar-circle me-2"></i>Cash Sales</h5>
                                <p class="mb-0 text-muted">Manage and track all cash sales transactions</p>
                            </div>
                            <div>
                                @can('create cash sales')
                                <a href="{{ route('sales.cash-sales.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>Create Cash Sale
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
                                <p class="mb-0 text-secondary">Total Cash Sales</p>
                                <h4 class="my-1 text-primary" id="total-cash-sales">{{ number_format(\App\Models\Sales\CashSale::forBranch(auth()->user()->branch_id)->count()) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-trending-up align-middle"></i> All time</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-dollar-circle"></i>
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
                                <h4 class="my-1 text-success" id="total-amount">TZS {{ number_format(\App\Models\Sales\CashSale::forBranch(auth()->user()->branch_id)->sum('total_amount'), 2) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-money align-middle"></i> Revenue</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                <i class="bx bx-money"></i>
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
                                <h4 class="my-1 text-info" id="today-sales">{{ number_format(\App\Models\Sales\CashSale::forBranch(auth()->user()->branch_id)->whereDate('sale_date', today())->count()) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-info"><i class="bx bx-calendar-check align-middle"></i> {{ \Carbon\Carbon::today()->format('M d, Y') }}</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                <i class="bx bx-calendar-check"></i>
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
                                <h4 class="my-1 text-warning" id="month-sales">{{ number_format(\App\Models\Sales\CashSale::forBranch(auth()->user()->branch_id)->whereMonth('sale_date', now()->month)->whereYear('sale_date', now()->year)->count()) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-warning"><i class="bx bx-calendar align-middle"></i> {{ \Carbon\Carbon::now()->format('F Y') }}</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                <i class="bx bx-calendar"></i>
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
                            <table id="cash-sales-table" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sale #</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Payment Method</th>
                                        <th>Total Amount</th>
                                        <th>Created By</th>
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this cash sale? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#cash-sales-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("sales.cash-sales.index") }}',
            type: 'GET'
        },
        columns: [
            {data: 'sale_number', name: 'sale_number'},
            {data: 'customer_name', name: 'customer_name'},
            {data: 'sale_date_formatted', name: 'sale_date'},
            {data: 'payment_method_text', name: 'payment_method'},
            {data: 'total_amount_formatted', name: 'total_amount'},
            {data: 'created_by_name', name: 'created_by'},
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
            emptyTable: "No cash sales found",
            zeroRecords: "No matching cash sales found"
        }
    });

    // Handle delete cash sale
    window.deleteCashSale = function(encodedId) {
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
                    url: '/sales/cash-sales/' + encodedId,
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
                            'An error occurred while deleting the cash sale.',
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