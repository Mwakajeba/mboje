@extends('layouts.main')

@section('title', 'Cash Deposit Transaction History')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Customers', 'url' => route('customers.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Customer', 'url' => route('customers.show', Hashids::encode($cashCollateral->customer_id)), 'icon' => 'bx bx-user'],
            ['label' => 'Transaction History', 'url' => '#', 'icon' => 'bx bx-history']
        ]" />

        <!-- Header Section -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="text-primary mb-2">{{ $cashCollateral->type->name }} - Transaction History</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Customer:</strong> {{ $cashCollateral->customer->name }}</p>
                                        <p class="mb-1"><strong>Account Type:</strong> {{ $cashCollateral->type->name }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Current Balance:</strong>
                                            <span class="badge bg-success fs-6">TSHS {{ number_format($calculatedBalance ?? 0, 2) }}</span>
                                        </p>
                                        <p class="mb-1"><strong>Branch:</strong> {{ $cashCollateral->branch->name ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="btn-group" role="group">
                                    @can('deposit cash deposit')
                                    <a href="{{ route('cash_collaterals.deposit', Hashids::encode($cashCollateral->id)) }}"
                                        class="btn btn-success btn-sm">
                                        <i class="bx bx-plus me-1"></i> Deposit
                                    </a>
                                    @endcan

                                    @can('withdraw cash deposit')
                                    <a href="{{ route('cash_collaterals.withdraw', Hashids::encode($cashCollateral->id)) }}"
                                        class="btn btn-warning btn-sm">
                                        <i class="bx bx-minus me-1"></i> Withdraw
                                    </a>
                                    @endcan

                                    <a href="{{ route('cash_collaterals.statement-pdf', Hashids::encode($cashCollateral->id)) }}"
                                        class="btn btn-info btn-sm" target="_blank">
                                        <i class="bx bx-printer me-1"></i> Print Statement
                                    </a>

                                    <a href="{{ route('customers.show', Hashids::encode($cashCollateral->customer_id)) }}"
                                        class="btn btn-secondary btn-sm">
                                        <i class="bx bx-arrow-back me-1"></i> Back
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction History Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">Transaction History</h5>
                            <a href="{{ route('cash_collaterals.statement-pdf', Hashids::encode($cashCollateral->id)) }}"
                               class="btn btn-primary btn-sm" target="_blank">
                                <i class="bx bx-download me-1"></i> Download Statement
                            </a>
                        </div>
                        
                        @if($transactions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="8%">Delete</th>
                                        <th width="12%">Date</th>
                                        <th width="25%">Narration</th>
                                        <th width="14%">Created By</th>
                                        <th width="12%">Cr (Deposit)</th>
                                        <th width="12%">Dr (Withdraw)</th>
                                        <th width="12%">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transactions as $transaction)
                                    <tr class="{{ !($transaction['deletable'] ?? false) ? 'text-muted opacity-50' : '' }}">
                                        <td class="text-center">
                                            <span>{{ $transaction['row_number'] }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($transaction['deletable'] ?? false)
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger delete-transaction-btn" 
                                                        data-id="{{ $transaction['delete_id'] }}"
                                                        data-type="{{ $transaction['delete_type'] }}"
                                                        data-amount="{{ $transaction['credit'] > 0 ? $transaction['credit'] : $transaction['debit'] }}"
                                                        data-narration="{{ $transaction['narration'] }}"
                                                        title="Delete Transaction">
                                                    <i class="bx bx-trash" style="font-size: 12px;"></i>
                                                </button>
                                            @else
                                                <span class="text-muted small">Protected</span>
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($transaction['date'])->format('d/m/Y') }}</td>
                                        <td>{{ $transaction['narration'] }}</td>
                                        <td>{{ $transaction['created_by'] }}</td>
                                        <td class="text-end">
                                            @if($transaction['credit'] > 0)
                                                <span class="text-success fw-bold">
                                                    {{ number_format($transaction['credit'], 2) }}
                                                </span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($transaction['debit'] > 0)
                                                <span class="text-danger fw-bold">
                                                    {{ number_format($transaction['debit'], 2) }}
                                                </span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold">{{ number_format($transaction['balance'], 2) }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="5" class="text-end">Totals:</th>
                                        <th class="text-end">
                                            <span class="text-success fw-bold">
                                                {{ number_format($transactions->sum('credit'), 2) }}
                                            </span>
                                        </th>
                                        <th class="text-end">
                                            <span class="text-danger fw-bold">
                                                {{ number_format($transactions->sum('debit'), 2) }}
                                            </span>
                                        </th>
                                        <th class="text-end">
                                            <span class="badge bg-primary fs-6">
                                                {{ number_format($calculatedBalance ?? 0, 2) }}
                                            </span>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Quick Stats -->
                        <div class="row mt-4">
                            <div class="col-md-3">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <h6 class="text-success">Total Deposits</h6>
                                        <h5 class="text-success mb-0">TSHS {{ number_format($transactions->sum('credit'), 2) }}</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-danger">
                                    <div class="card-body text-center">
                                        <h6 class="text-danger">Total Withdrawals</h6>
                                        <h5 class="text-danger mb-0">TSHS {{ number_format($transactions->sum('debit'), 2) }}</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-info">
                                    <div class="card-body text-center">
                                        <h6 class="text-info">Total Transactions</h6>
                                        <h5 class="text-info mb-0">{{ $transactions->count() }}</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <h6 class="text-primary">Current Balance</h6>
                                        <h5 class="text-primary mb-0">TSHS {{ number_format($calculatedBalance ?? 0, 2) }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="bx bx-history" style="font-size: 4rem; color: #6c757d;"></i>
                            </div>
                            <h5 class="text-muted">No Transaction History</h5>
                            <p class="text-muted">No deposits or withdrawals have been made for this account yet.</p>
                            @can('deposit cash deposit')
                            <a href="{{ route('cash_collaterals.deposit', Hashids::encode($cashCollateral->id)) }}"
                                class="btn btn-success">
                                <i class="bx bx-plus me-1"></i> Make First Deposit
                            </a>
                            @endcan
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Handle delete transaction button click
    $('.delete-transaction-btn').on('click', function() {
        const transactionId = $(this).data('id');
        const transactionType = $(this).data('type');
        const amount = $(this).data('amount');
        const narration = $(this).data('narration');
        const row = $(this).closest('tr');
        
        // Confirm deletion
        Swal.fire({
            title: 'Delete Transaction?',
            html: `
                <div class="text-start">
                    <p><strong>Type:</strong> ${transactionType === 'receipt' ? 'Deposit' : 'Withdrawal'}</p>
                    <p><strong>Amount:</strong> TSHS ${parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
                    <p><strong>Description:</strong> ${narration}</p>
                    <p class="text-warning mt-3"><i class="bx bx-warning"></i> This action cannot be undone!</p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, Delete It',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Determine the correct route based on transaction type
                let deleteUrl;
                if (transactionType === 'receipt') {
                    deleteUrl = `{{ url('cash_collaterals/delete-deposit') }}/${transactionId}`;
                } else if (transactionType === 'payment') {
                    deleteUrl = `{{ url('cash_collaterals/delete-withdrawal') }}/${transactionId}`;
                }
                
                // Show loading
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while we delete the transaction.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Send delete request
                $.ajax({
                    url: deleteUrl,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                // Reload the page to show updated balances
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error!', response.message || 'Failed to delete transaction.', 'error');
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to delete transaction.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error!', errorMessage, 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush