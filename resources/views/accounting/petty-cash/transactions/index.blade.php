@extends('layouts.main')

@section('title', 'Petty Cash Transactions')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Petty Cash Units', 'url' => route('accounting.petty-cash.units.index'), 'icon' => 'bx bx-wallet'],
            ['label' => 'Transactions', 'url' => '#', 'icon' => 'bx bx-list-ul']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h6 class="mb-0 text-uppercase">PETTY CASH TRANSACTIONS</h6>
                <p class="text-muted mb-0">View and manage all petty cash transactions</p>
            </div>
            <div>
                <a href="{{ route('accounting.petty-cash.units.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-2"></i>Back to Units
                </a>
            </div>
        </div>
        <hr />

        <!-- Filters -->
        <div class="card radius-10 mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="bx bx-filter me-2"></i>Filters</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="petty_cash_unit_id" class="form-label">Petty Cash Unit</label>
                        <select class="form-select" id="petty_cash_unit_id" name="petty_cash_unit_id">
                            <option value="">All Units</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" {{ request('petty_cash_unit_id') == $unit->id ? 'selected' : '' }}>
                                    {{ $unit->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="posted" {{ request('status') == 'posted' ? 'selected' : '' }}>Posted</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary" id="resetFilters">
                            <i class="bx bx-refresh me-1"></i>Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="card radius-10">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Transactions</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="transactions-table" class="table table-hover table-striped" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>Transaction #</th>
                                <th>Date</th>
                                <th>Unit</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                                <th class="text-end">Balance After</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables will populate this -->
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
    // Initialize Transactions DataTable
    var transactionsTable = $('#transactions-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("accounting.petty-cash.transactions.index") }}',
            type: 'GET',
            data: function(d) {
                d.petty_cash_unit_id = $('#petty_cash_unit_id').val();
                d.status = $('#status').val();
            }
        },
        columns: [
            {data: 'transaction_number_link', name: 'transaction_number', orderable: true, searchable: true},
            {data: 'formatted_date', name: 'transaction_date', orderable: true, searchable: false},
            {data: 'unit_name', name: 'pettyCashUnit.name', orderable: true, searchable: true},
            {data: 'category_name', name: 'expenseCategory.name', orderable: false, searchable: false},
            {data: 'description_with_payee', name: 'description', orderable: true, searchable: true},
            {data: 'formatted_amount', name: 'amount', orderable: true, searchable: false, className: 'text-end'},
            {data: 'status_badge', name: 'status', orderable: true, searchable: true},
            {data: 'formatted_balance_after', name: 'balance_after', orderable: true, searchable: false, className: 'text-end'},
            {data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center'}
        ],
        order: [[1, 'desc']], // Order by date descending
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            processing: '<i class="bx bx-loader-alt bx-spin"></i> Loading...',
            emptyTable: 'No transactions found',
            zeroRecords: 'No matching transactions found'
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        drawCallback: function(settings) {
            // Any additional callbacks after table draw
        }
    });

    // Reload table when filters change
    $('#petty_cash_unit_id, #status').on('change', function() {
        transactionsTable.ajax.reload();
    });

    // Reset filters
    $('#resetFilters').on('click', function() {
        $('#petty_cash_unit_id, #status').val('').trigger('change');
        transactionsTable.ajax.reload();
    });

    // Post transaction to GL function
    function postTransactionToGL(encodedId) {
        Swal.fire({
            title: 'Post to GL?',
            text: "This will post the transaction to General Ledger. Continue?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, post it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("accounting/petty-cash/transactions") }}/' + encodedId + '/post-to-gl',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Posted!',
                            text: 'Transaction posted to GL successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(function() {
                            transactionsTable.ajax.reload(null, false);
                        });
                    },
                    error: function(xhr) {
                        let message = 'Failed to post transaction to GL.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: message
                        });
                    }
                });
            }
        });
    }

    // Make postTransactionToGL available globally
    window.postTransactionToGL = postTransactionToGL;

    // Delete transaction function
    function deleteTransaction(encodedId) {
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
                    url: '{{ url("accounting/petty-cash/transactions") }}/' + encodedId,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(function() {
                                transactionsTable.ajax.reload(null, false);
                            });
                        }
                    },
                    error: function(xhr) {
                        let message = 'Failed to delete transaction.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: message
                        });
                    }
                });
            }
        });
    }

    // Make deleteTransaction available globally
    window.deleteTransaction = deleteTransaction;

    // Approve transaction function
    function approveTransaction(encodedId) {
        Swal.fire({
            title: 'Approve Transaction?',
            text: "This will approve the transaction and post it to GL. Continue?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, approve it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("accounting/petty-cash/transactions") }}/' + encodedId + '/approve',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Approved!',
                            text: 'Transaction approved and posted to GL successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(function() {
                            transactionsTable.ajax.reload(null, false);
                        });
                    },
                    error: function(xhr) {
                        let message = 'Failed to approve transaction.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: message
                        });
                    }
                });
            }
        });
    }

    // Reject transaction function
    function rejectTransaction(encodedId) {
        Swal.fire({
            title: 'Reject Transaction?',
            text: "Please provide a reason for rejecting this transaction.",
            icon: 'warning',
            input: 'textarea',
            inputLabel: 'Rejection Reason',
            inputPlaceholder: 'Enter the reason for rejection (minimum 10 characters)...',
            inputAttributes: {
                'aria-label': 'Enter the reason for rejection'
            },
            inputValidator: (value) => {
                if (!value || value.length < 10) {
                    return 'Please provide a rejection reason (minimum 10 characters)';
                }
            },
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Reject',
            cancelButtonText: 'Cancel',
            showLoaderOnConfirm: true,
            preConfirm: (rejectionReason) => {
                return fetch('{{ url("accounting/petty-cash/transactions") }}/' + encodedId + '/reject', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        rejection_reason: rejectionReason
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to reject transaction.');
                    }
                    return data;
                })
                .catch(error => {
                    Swal.showValidationMessage('Request failed: ' + error.message);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: result.value.message || 'Transaction rejected successfully.',
                    timer: 3000,
                    showConfirmButton: false
                }).then(() => {
                    transactionsTable.ajax.reload(null, false);
                });
            }
        });
    }

    // Make functions available globally
    window.approveTransaction = approveTransaction;
    window.rejectTransaction = rejectTransaction;
});
</script>
@endpush

