@extends('layouts.main')

@section('title', 'Customer Opening Balances')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales', 'url' => route('sales.invoices.index'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Opening Balances', 'url' => '#', 'icon' => 'bx bx-book']
        ]" />
        <h6 class="mb-0 text-uppercase">CUSTOMER OPENING BALANCES</h6>
        <hr />

        <div class="d-flex justify-content-between mb-3">
            <div></div>
            <div class="btn-group">
                <a href="{{ route('sales.opening-balances.import') }}" class="btn btn-outline-success">
                    <i class="bx bx-upload me-1"></i>Import
                </a>
                <a href="{{ route('sales.opening-balances.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>Add Opening Balance
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                @if(session('error'))
                    <div class="alert alert-danger">
                        <i class="bx bx-error"></i>
                        {{ session('error') }}
                    </div>
                @endif

                @if(session('success'))
                    <div class="alert alert-success">
                        <i class="bx bx-check"></i>
                        {{ session('success') }}
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-striped" id="opening-balances-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables will populate this via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    $('#opening-balances-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('sales.opening-balances.index') }}",
            type: 'GET'
        },
        columns: [
            { data: 'customer_name', name: 'customer_name' },
            { data: 'opening_date', name: 'opening_date' },
            { data: 'amount_formatted', name: 'amount' },
            { data: 'paid_amount_formatted', name: 'paid_amount' },
            { data: 'balance_due_formatted', name: 'balance_due' },
            { data: 'status_badge', name: 'status' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'desc']], // Sort by date descending
        pageLength: 25,
        responsive: true,
        language: {
            processing: "Loading opening balances...",
            emptyTable: "No opening balances found",
            zeroRecords: "No matching opening balances found"
        }
    });

    // Handle delete button clicks
    $(document).on('click', '.delete-opening-balance', function() {
        const url = $(this).data('url');
        const customer = $(this).data('customer');
        const amount = $(this).data('amount');
        
        Swal.fire({
            title: 'Delete Opening Balance?',
            html: `<strong>Customer:</strong> ${customer}<br><strong>Amount:</strong> ${amount}`,
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire(
                            'Deleted!',
                            'Opening balance has been deleted.',
                            'success'
                        ).then(() => {
                            $('#opening-balances-table').DataTable().ajax.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire(
                            'Error!',
                            'Failed to delete opening balance.',
                            'error'
                        );
                    }
                });
            }
        });
    });
});
</script>
@endpush
