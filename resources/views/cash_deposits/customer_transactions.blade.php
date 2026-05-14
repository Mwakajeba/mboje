@extends('layouts.main')

@section('title', 'Customer Cash Deposit Transactions')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Cash Deposits', 'url' => route('cash_deposits.index'), 'icon' => 'bx bx-credit-card'],
            ['label' => $customer->name, 'url' => '#', 'icon' => 'bx bx-user']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0 text-primary">Cash Deposit Transactions</h5>
                <p class="text-muted mb-0">{{ $customer->name }}</p>
            </div>
            <div class="btn-group">
                @can('deposit cash deposit')
                <a href="{{ route('cash_deposits.create') }}?customer_id={{ Hashids::encode($customer->id) }}{{ isset($mostUsedTypeId) && $mostUsedTypeId ? '&type_id=' . Hashids::encode($mostUsedTypeId) : '' }}" class="btn btn-primary">
                    <i class="bx bx-plus"></i> New Deposit
                </a>
                @endcan
                
                @can('withdraw cash deposit')
                @if($cashDeposits->count() > 0)
                <a href="{{ route('cash_deposits.withdraw', Hashids::encode($customer->id)) }}" class="btn btn-success">
                    <i class="bx bx-minus"></i> Withdraw
                </a>
                @endif
                @endcan
            </div>
        </div>

        <hr>

        <!-- Customer Info Card -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Customer Information</h6>
                                <p class="mb-1"><strong>Name:</strong> {{ $customer->name }}</p>
                                @if($customer->phone)
                                <p class="mb-1"><strong>Phone:</strong> {{ $customer->phone }}</p>
                                @endif
                                @if($customer->email)
                                <p class="mb-0"><strong>Email:</strong> {{ $customer->email }}</p>
                                @endif
                            </div>
                            <div class="ms-3">
                                <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bx bx-user font-size-24"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Total Balance</h6>
                                <h3 class="mb-0 text-success">TZS {{ number_format($totalBalance, 2) }}</h3>
                                <small class="text-muted">{{ $cashDeposits->count() }} deposit account(s)</small>
                            </div>
                            <div class="ms-3">
                                <div class="avatar-sm bg-success text-white rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bx bx-wallet font-size-24"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <!-- Transactions Table -->
        <div class="card radius-10">
            <div class="card-header">
                <h6 class="mb-0">Transaction History</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered dt-responsive nowrap" id="transactions-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Account Type</th>
                                <th>Payment Source</th>
                                <th class="text-end">Amount</th>
                                <th>User</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
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
    try {
        const table = $('#transactions-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("cash_deposits.customer_transactions.datatable", Hashids::encode($customer->id)) }}',
                type: 'GET'
            },
            columns: [
                { data: 'formatted_date', name: 'date' },
                { data: 'type_badge', name: 'type', orderable: false },
                { data: 'description', name: 'description' },
                { data: 'deposit_type', name: 'deposit_type' },
                { data: 'bank_account', name: 'bank_account' },
                { data: 'formatted_amount', name: 'amount', className: 'text-end', orderable: false },
                { data: 'user_name', name: 'user_name' },
                { 
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                }
            ],
            order: [[0, 'desc']], // Sort by date descending
            pageLength: 25,
            responsive: true,
            language: {
                processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                search: "Search Transactions:",
                lengthMenu: "Show _MENU_ transactions per page",
                info: "Showing _START_ to _END_ of _TOTAL_ transactions",
                infoEmpty: "Showing 0 to 0 of 0 transactions",
                infoFiltered: "(filtered from _MAX_ total transactions)",
                emptyTable: `
                    <div class="text-center text-muted py-4">
                        <i class="bx bx-wallet fs-1 d-block mb-2"></i>
                        <h6>No Transactions Found</h6>
                        <p class="mb-0">No transactions have been recorded for this customer yet.</p>
                    </div>
                `
            }
        });

        // Handle delete form submissions
        $(document).on('submit', '.delete-form', function(e) {
            e.preventDefault();
            const form = $(this);
            const transactionName = form.find('button').data('name');
            
            Swal.fire({
                title: 'Are you sure?',
                text: `Do you want to delete this ${transactionName}? This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait while we delete the transaction.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: form.attr('action'),
                        type: 'POST',
                        data: form.serialize(),
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: 'Transaction has been deleted successfully.',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    // Reload the DataTable
                                    table.ajax.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: response.message || 'Failed to delete transaction.',
                                    icon: 'error'
                                });
                            }
                        },
                        error: function(xhr) {
                            console.error('Delete error:', xhr);
                            let errorMessage = 'Failed to delete transaction. Please try again.';
                            
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            } else if (xhr.responseText) {
                                errorMessage = xhr.responseText;
                            }
                            
                            Swal.fire({
                                title: 'Error!',
                                text: errorMessage,
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });

    } catch (error) {
        console.error('DataTable initialization error:', error);
        alert('Error initializing table. Please refresh the page.');
    }
});
</script>
@endpush 