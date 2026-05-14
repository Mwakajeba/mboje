@extends('layouts.main')

@section('title', 'Rental Invoices')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Rental Invoices', 'url' => '#', 'icon' => 'bx bx-receipt']
        ]" />
        <h6 class="mb-0 text-uppercase">RENTAL INVOICES MANAGEMENT</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="card-title d-flex align-items-center">
                                <div><i class="bx bx-receipt me-1 font-22 text-success"></i></div>
                                <h5 class="mb-0 text-success">Rental Invoices</h5>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('rental-event-equipment.rental-invoices.create') }}" class="btn btn-success">
                                    <i class="bx bx-plus me-1"></i> Create Invoice
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

                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bx bx-error-circle me-1"></i> {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table id="invoicesTable" class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Invoice Number</th>
                                        <th>Customer</th>
                                        <th>Contract</th>
                                        <th>Invoice Date</th>
                                        <th>Total Amount</th>
                                        <th>Paid Amount</th>
                                        <th>Balance Due</th>
                                        <th>Status</th>
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

@push('scripts')
<script>
$(document).ready(function() {
    $('#invoicesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("rental-event-equipment.rental-invoices.data") }}',
            type: 'GET'
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'invoice_number', name: 'invoice_number' },
            { data: 'customer_name', name: 'customer_name' },
            { data: 'contract_number', name: 'contract_number' },
            { data: 'invoice_date_formatted', name: 'invoice_date' },
            { data: 'total_amount_formatted', name: 'total_amount' },
            { data: 'paid_amount_formatted', name: 'paid_amount', orderable: false },
            { data: 'balance_due_formatted', name: 'balance_due', orderable: false },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[4, 'desc']],
        responsive: true
    });

    // Handle delete button click
    $(document).on('click', '.delete-invoice-btn', function() {
        const invoiceId = $(this).data('invoice-id');
        const invoiceNumber = $(this).data('invoice-number');
        
        Swal.fire({
            title: 'Delete Invoice?',
            html: `Are you sure you want to delete invoice <strong>${invoiceNumber}</strong>?<br><br>This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while we delete the invoice.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                const form = $('<form>', {
                    'method': 'POST',
                    'action': '{{ url("rental-event-equipment/rental-invoices") }}/' + invoiceId
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
    });
});
</script>
@endpush
