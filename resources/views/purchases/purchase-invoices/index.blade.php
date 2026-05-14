@extends('layouts.main')

@section('title', 'Purchase Invoices')

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
            ['label' => 'Purchase Management', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Purchase Invoices', 'url' => '#', 'icon' => 'bx bx-receipt']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-receipt me-2"></i>Purchase Invoices</h5>
                                <p class="mb-0 text-muted">Manage and track all purchase invoices</p>
                            </div>
                            <div>
                                @can('create purchase invoices')
                                <a href="{{ route('purchases.purchase-invoices.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>Create Invoice
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
                                <p class="mb-0 text-secondary">Total Invoices</p>
                                <h4 class="my-1 text-primary" id="total-invoices">{{ number_format($totalInvoices) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-receipt align-middle"></i> All invoices</span>
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
                                <h4 class="my-1 text-success" id="total-amount">TZS {{ number_format($totalAmount, 2) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-dollar align-middle"></i> Total purchases</span>
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
                                <p class="mb-0 text-secondary">Total Paid</p>
                                <h4 class="my-1 text-info" id="total-paid">TZS {{ number_format($totalPaid, 2) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-info"><i class="bx bx-check-circle align-middle"></i> Settled</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
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
                                <p class="mb-0 text-secondary">Outstanding</p>
                                <h4 class="my-1 text-warning" id="total-outstanding">TZS {{ number_format($totalOutstanding, 2) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-warning"><i class="bx bx-time align-middle"></i> Pending</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                <i class="bx bx-time"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row">
            <div class="col-12">
                <div class="card radius-10 mt-3 mb-3">
                    <!-- Filters can be added here if needed -->
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="purchase-invoices-table" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Supplier</th>
                                        <th>Date</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Total Amount</th>
                                        <th>Paid</th>
                                        <th>Outstanding</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
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
    // Initialize DataTable
    var table = $('#purchase-invoices-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("purchases.purchase-invoices.index") }}',
            type: 'GET',
            data: function(d){
                d.supplier = $('#filter-supplier').val();
                d.status = $('#filter-status').val();
                d.date_from = $('#filter-from').val();
                d.date_to = $('#filter-to').val();
            }
        },
        columns: [
            {data: 'invoice_number', name: 'invoice_number'},
            {data: 'supplier_name', name: 'supplier_name'},
            {data: 'formatted_date', name: 'invoice_date'},
            {data: 'formatted_due_date', name: 'due_date'},
            {data: 'status_badge', name: 'status'},
            {data: 'formatted_total', name: 'total_amount'},
            {data: 'formatted_paid', name: 'formatted_paid'},
            {data: 'formatted_outstanding', name: 'formatted_outstanding'},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        order: [[2, 'desc']],
        pageLength: 25,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            search: 'Search:',
            lengthMenu: 'Show _MENU_ entries per page',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty: 'Showing 0 to 0 of 0 entries',
            infoFiltered: '(filtered from _MAX_ total entries)',
            emptyTable: 'No invoices found',
            zeroRecords: 'No matching invoices found'
        },
        drawCallback: function(settings) {
            updateDashboardStats();
        }
    });

    // Filters
    $('#filter-supplier, #filter-status, #filter-from, #filter-to').on('change keyup', function(){
        table.ajax.reload();
    });
    $('#filter-reset').on('click', function(e){
        e.preventDefault();
        $('#filter-supplier').val('');
        $('#filter-status').val('');
        $('#filter-from').val('');
        $('#filter-to').val('');
        table.ajax.reload();
    });

    function updateDashboardStats() {
        $.get('{{ route("purchases.purchase-invoices.index") }}', { stats_only: true }, function(resp){
            if (resp.stats) {
                $('#total-invoices').text(Number(resp.stats.total_invoices).toLocaleString());
                $('#total-amount').text(Number(resp.stats.total_amount).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}));
                $('#total-paid').text(Number(resp.stats.total_paid).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}));
                $('#total-outstanding').text(Number(resp.stats.total_outstanding).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}));
            }
        });
    }

    // Delete function (hashed id)
    window.deleteInvoice = function(encodedId) {
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
                    url: '{{ route("purchases.purchase-invoices.index") }}/' + encodedId,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'An error occurred while deleting the invoice.';
                        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        Swal.fire('Error!', msg, 'error');
                    }
                });
            }
        });
    };
});
</script>
@endpush
@endsection 