@extends('layouts.main')

@section('title', 'Petty Cash Transaction Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Petty Cash Units', 'url' => route('accounting.petty-cash.units.index'), 'icon' => 'bx bx-wallet'],
            ['label' => $transaction->pettyCashUnit->name, 'url' => route('accounting.petty-cash.units.show', $transaction->pettyCashUnit->encoded_id), 'icon' => 'bx bx-show'],
            ['label' => 'Transaction #' . $transaction->transaction_number, 'url' => '#', 'icon' => 'bx bx-receipt']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h6 class="mb-0 text-uppercase">PETTY CASH TRANSACTION DETAILS</h6>
                <p class="text-muted mb-0">View transaction information</p>
            </div>
            <div>
                <a href="{{ route('accounting.petty-cash.units.show', $transaction->pettyCashUnit->encoded_id) }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-2"></i>Back to Unit
                </a>
                @if($transaction->canBeEdited())
                    <a href="{{ route('accounting.petty-cash.transactions.edit', $transaction->encoded_id) }}" class="btn btn-primary">
                        <i class="bx bx-edit me-2"></i>Edit
                    </a>
                @endif
            </div>
        </div>
        <hr />

        <!-- Status Badge -->
        @php
            $statusColors = [
                'draft' => 'secondary',
                'submitted' => 'info',
                'approved' => 'success',
                'pending_receipt' => 'warning',
                'posted' => 'primary',
                'rejected' => 'danger'
            ];
            $statusColor = $statusColors[$transaction->status] ?? 'secondary';
            
            $receiptStatusColors = [
                'pending' => 'warning',
                'uploaded' => 'info',
                'verified' => 'success',
                'rejected' => 'danger'
            ];
            $receiptStatusColor = $transaction->receipt_status ? ($receiptStatusColors[$transaction->receipt_status] ?? 'secondary') : null;
        @endphp

        <!-- Prominent Header Card -->
        <div class="card radius-10 bg-primary text-white mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-lg bg-white text-danger rounded-circle me-3 d-flex align-items-center justify-content-center">
                        <i class="bx bx-receipt font-size-32"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h3 class="mb-1">Transaction #{{ $transaction->transaction_number }}</h3>
                        <p class="mb-0 opacity-75">{{ $transaction->description ?: 'No description provided' }}</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge bg-{{ $statusColor }}">
                            {{ ucfirst(str_replace('_', ' ', $transaction->status)) }}
                        </span>
                        @if($transaction->receipt_status)
                        <span class="badge bg-{{ $receiptStatusColor }}">
                            <i class="bx bx-receipt me-1"></i>
                            Receipt: {{ ucfirst($transaction->receipt_status) }}
                        </span>
                        @endif
                        <span class="badge bg-light text-dark">
                            <i class="bx bx-calendar me-1"></i>
                            {{ $transaction->transaction_date->format('M d, Y') }}
                        </span>
                        <span class="badge bg-light text-dark">
                            <i class="bx bx-dollar me-1"></i>
                            TZS {{ number_format($transaction->amount, 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column - Main Information -->
            <div class="col-lg-8">
                <!-- Basic Information -->
                <div class="card radius-10 mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Transaction Number</label>
                                <p class="form-control-plaintext">{{ $transaction->transaction_number }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Transaction Date</label>
                                <p class="form-control-plaintext">{{ $transaction->transaction_date->format('M d, Y') }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Petty Cash Unit</label>
                                <p class="form-control-plaintext">
                                    <a href="{{ route('accounting.petty-cash.units.show', $transaction->pettyCashUnit->encoded_id) }}" class="text-primary">
                                        {{ $transaction->pettyCashUnit->name }}
                                    </a>
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Amount</label>
                                <p class="form-control-plaintext">
                                    <span class="fw-bold text-danger">TZS {{ number_format($transaction->amount, 2) }}</span>
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Payee Type</label>
                                <p class="form-control-plaintext">
                                    @if($transaction->payee_type)
                                        <span class="badge bg-{{ $transaction->payee_type == 'customer' ? 'primary' : ($transaction->payee_type == 'supplier' ? 'success' : ($transaction->payee_type == 'employee' ? 'info' : 'warning')) }}">
                                            {{ ucfirst($transaction->payee_type) }}
                                        </span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Payee</label>
                                <p class="form-control-plaintext">
                                    @if($transaction->payee_type == 'customer' && $transaction->customer)
                                        {{ $transaction->customer->name ?? 'N/A' }}
                                        @if($transaction->customer->customerNo)
                                            ({{ $transaction->customer->customerNo }})
                                        @endif
                                    @elseif($transaction->payee_type == 'supplier' && $transaction->supplier)
                                        {{ $transaction->supplier->name ?? 'N/A' }}
                                    @elseif($transaction->payee_type == 'employee' && $transaction->employee)
                                        {{ $transaction->employee->full_name }}@if($transaction->employee->employee_number) ({{ $transaction->employee->employee_number }})@endif
                                    @elseif($transaction->payee_type == 'other' && $transaction->payee)
                                        {{ $transaction->payee }}
                                    @elseif($transaction->payee)
                                        {{ $transaction->payee }}
                                    @else
                                        <span class="text-muted">No payee specified</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Expense Category</label>
                                <p class="form-control-plaintext">
                                    {{ $transaction->expenseCategory->name ?? 'N/A' }}
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Balance After</label>
                                <p class="form-control-plaintext">
                                    @if($transaction->balance_after !== null)
                                        <span class="fw-bold">TZS {{ number_format($transaction->balance_after, 2) }}</span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold">Description</label>
                                <p class="form-control-plaintext">
                                    {{ $transaction->description ?: 'No description provided' }}
                                </p>
                            </div>
                            @if($transaction->notes)
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold">Notes</label>
                                <p class="form-control-plaintext">
                                    {{ $transaction->notes }}
                                </p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Line Items -->
                @php
                    // Use payment items if transaction is posted to GL, otherwise use transaction items
                    $lineItems = null;
                    if ($transaction->payment_id && $transaction->payment && $transaction->payment->paymentItems) {
                        $lineItems = $transaction->payment->paymentItems;
                    } elseif ($transaction->items && $transaction->items->count() > 0) {
                        $lineItems = $transaction->items;
                    }
                @endphp
                
                @if($lineItems && $lineItems->count() > 0)
                <div class="card radius-10 mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="bx bx-list-ul me-2"></i>Expense Line Items
                            @if($transaction->payment_id)
                                <small class="opacity-75">(from Payment #{{ $transaction->payment->reference }})</small>
                            @endif
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Account Code</th>
                                        <th>Account Name</th>
                                        <th>Description</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($lineItems as $item)
                                    <tr>
                                        <td>{{ $item->chartAccount->account_code ?? 'N/A' }}</td>
                                        <td>{{ $item->chartAccount->account_name ?? 'N/A' }}</td>
                                        <td>{{ $item->description ?? '-' }}</td>
                                        <td class="text-end fw-bold">TZS {{ number_format($item->amount, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3" class="text-end">Total:</th>
                                        <th class="text-end">TZS {{ number_format($transaction->amount, 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Receipt Section -->
                <div class="card radius-10 mb-4">
                    <div class="card-header bg-{{ $transaction->receipt_status == 'verified' ? 'success' : ($transaction->receipt_status == 'rejected' ? 'danger' : 'info') }} text-white">
                        <h5 class="mb-0"><i class="bx bx-receipt me-2"></i>Receipt Information</h5>
                    </div>
                    <div class="card-body">
                        @if($transaction->receipt_status)
                            <div class="mb-3">
                                <label class="form-label fw-bold">Receipt Status</label>
                                <p class="form-control-plaintext">
                                    <span class="badge bg-{{ $receiptStatusColor }}">
                                        {{ ucfirst($transaction->receipt_status) }}
                                    </span>
                                </p>
                            </div>
                        @endif
                        
                        @if($transaction->receipt_attachment)
                            <div class="mb-3">
                                <label class="form-label fw-bold">Receipt Attachment</label>
                                <p class="form-control-plaintext">
                                    <a href="{{ Storage::url($transaction->receipt_attachment) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="bx bx-file me-2"></i>View Receipt
                                    </a>
                                </p>
                            </div>
                        @endif
                        
                        @if($transaction->receipt_verified_by)
                            <div class="mb-3">
                                <label class="form-label fw-bold">Verified By</label>
                                <p class="form-control-plaintext">{{ $transaction->receiptVerifiedBy->name ?? 'N/A' }}</p>
                            </div>
                        @endif
                        
                        @if($transaction->receipt_verified_at)
                            <div class="mb-3">
                                <label class="form-label fw-bold">Verified At</label>
                                <p class="form-control-plaintext">{{ $transaction->receipt_verified_at->format('M d, Y H:i') }}</p>
                            </div>
                        @endif
                        
                        @if($transaction->receipt_verification_notes)
                            <div class="mb-3">
                                <label class="form-label fw-bold">Verification Notes</label>
                                <p class="form-control-plaintext">{{ $transaction->receipt_verification_notes }}</p>
                            </div>
                        @endif
                        
                        @if($transaction->disbursed_by)
                            <div class="mb-3">
                                <label class="form-label fw-bold">Disbursed By</label>
                                <p class="form-control-plaintext">{{ $transaction->disbursedBy->name ?? 'N/A' }}</p>
                            </div>
                        @endif
                        
                        @if($transaction->disbursed_at)
                            <div class="mb-3">
                                <label class="form-label fw-bold">Disbursed At</label>
                                <p class="form-control-plaintext">{{ $transaction->disbursed_at->format('M d, Y H:i') }}</p>
                            </div>
                        @endif
                        
                        @if($transaction->status === 'pending_receipt' && !$transaction->receipt_attachment)
                            <!-- Upload Receipt Form -->
                            <form id="uploadReceiptForm" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label for="receipt_attachment" class="form-label fw-bold">Upload Receipt</label>
                                    <input type="file" class="form-control" id="receipt_attachment" name="receipt_attachment" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <small class="text-muted">Accepted formats: PDF, JPG, PNG (Max: 5MB)</small>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-upload me-2"></i>Upload Receipt
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column - Status & Actions -->
            <div class="col-lg-4">
                <!-- Status Information -->
                <div class="card radius-10 mb-4">
                    <div class="card-header bg-{{ $statusColor }} text-white">
                        <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Status Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Current Status</label>
                            <p class="form-control-plaintext">
                                <span class="badge bg-{{ $statusColor }}">{{ ucfirst($transaction->status) }}</span>
                            </p>
                        </div>
                        @if($transaction->createdBy)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Created By</label>
                            <p class="form-control-plaintext">{{ $transaction->createdBy->name ?? 'N/A' }}</p>
                        </div>
                        @endif
                        @if($transaction->approvedBy)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Approved By</label>
                            <p class="form-control-plaintext">{{ $transaction->approvedBy->name ?? 'N/A' }}</p>
                        </div>
                        @endif
                        @if($transaction->approved_at)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Approved At</label>
                            <p class="form-control-plaintext">{{ $transaction->approved_at->format('M d, Y H:i') }}</p>
                        </div>
                        @endif
                        @if($transaction->payment_id)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Posted to GL</label>
                            <p class="form-control-plaintext">
                                <span class="badge bg-success">Yes</span>
                                @if($transaction->payment)
                                    <br><small class="text-muted">Payment #{{ $transaction->payment->reference }}</small>
                                @endif
                            </p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Imprest Request (Sub-Imprest Mode) -->
                @if($imprestRequest)
                <div class="card radius-10 mb-4 border-info">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bx bx-link me-2"></i>Linked Imprest Request</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-0">
                            <div class="d-flex align-items-center">
                                <i class="bx bx-info-circle fs-4 me-3"></i>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">This transaction is linked to an Imprest Request</h6>
                                    <p class="mb-2">
                                        <strong>Request Number:</strong> 
                                        <a href="{{ route('imprest.requests.show', $imprestRequest->id) }}" class="text-primary fw-bold">
                                            {{ $imprestRequest->request_number }}
                                        </a>
                                    </p>
                                    <p class="mb-0">
                                        <strong>Status:</strong> 
                                        <span class="badge bg-{{ $imprestRequest->status == 'approved' ? 'success' : ($imprestRequest->status == 'disbursed' ? 'primary' : 'warning') }}">
                                            {{ ucfirst($imprestRequest->status) }}
                                        </span>
                                    </p>
                                    <p class="mb-0 mt-2">
                                        <strong>Purpose:</strong> {{ $imprestRequest->purpose }}
                                    </p>
                                    <p class="mb-0 mt-2">
                                        <a href="{{ route('imprest.requests.show', $imprestRequest->id) }}" class="btn btn-sm btn-outline-light">
                                            <i class="bx bx-show me-1"></i>View Imprest Request
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Actions -->
                <div class="card radius-10 mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bx bx-cog me-2"></i>Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            @if($transaction->canBeEdited())
                                <a href="{{ route('accounting.petty-cash.transactions.edit', $transaction->encoded_id) }}" class="btn btn-primary">
                                    <i class="bx bx-edit me-2"></i>Edit Transaction
                                </a>
                            @endif
                            
                            @if($transaction->canBeApproved())
                                <button type="button" class="btn btn-success w-100 mb-2" onclick="approveTransaction('{{ $transaction->encoded_id }}')">
                                    <i class="bx bx-check me-2"></i>Approve Transaction
                                </button>
                            @endif
                            @if($transaction->canBeRejected())
                                <button type="button" class="btn btn-danger w-100" onclick="rejectTransaction('{{ $transaction->encoded_id }}')">
                                    <i class="bx bx-x me-2"></i>Reject Transaction
                                </button>
                            @endif
                            
                            {{-- Sub-Imprest Mode: Disburse Cash (Custodian) --}}
                            @if($settings->isSubImprestMode() && $transaction->status === 'approved' && !$transaction->disbursed_by)
                                <button type="button" class="btn btn-warning w-100" onclick="disburseTransaction('{{ $transaction->encoded_id }}')">
                                    <i class="bx bx-money me-2"></i>Disburse Cash
                                </button>
                            @endif
                            
                            {{-- Sub-Imprest Mode: Verify Receipt (Custodian/Accountant) --}}
                            @if($settings->isSubImprestMode() && $transaction->receipt_status === 'uploaded')
                                <button type="button" class="btn btn-success w-100" onclick="verifyReceipt('{{ $transaction->encoded_id }}', true)">
                                    <i class="bx bx-check-circle me-2"></i>Verify Receipt
                                </button>
                                <button type="button" class="btn btn-danger w-100" onclick="verifyReceipt('{{ $transaction->encoded_id }}', false)">
                                    <i class="bx bx-x-circle me-2"></i>Reject Receipt
                                </button>
                            @endif
                            
                            {{-- Standalone Mode: Post to GL --}}
                            @if(!$settings->isSubImprestMode() && $transaction->canBePosted())
                                <button type="button" class="btn btn-success w-100" onclick="postTransactionToGL('{{ $transaction->encoded_id }}')">
                                    <i class="bx bx-check-circle me-2"></i>Post to GL
                                </button>
                            @endif
                            
                            @if($transaction->canBeDeleted())
                                <form action="{{ route('accounting.petty-cash.transactions.destroy', $transaction->encoded_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this transaction? This action cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="bx bx-trash me-2"></i>Delete Transaction
                                    </button>
                                </form>
                            @endif
                            
                            <a href="{{ route('accounting.petty-cash.units.show', $transaction->pettyCashUnit->encoded_id) }}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-2"></i>Back to Unit
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    // Approve transaction function
    function approveTransaction(encodedId) {
        Swal.fire({
            title: 'Approve Transaction?',
            text: "Are you sure you want to approve this transaction? This will approve the transaction and post it to GL.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, approve it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while we approve the transaction.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

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
                            text: response.message || 'Transaction has been approved and posted to GL successfully.',
                            confirmButtonColor: '#198754'
                        }).then(() => {
                            // Reload the page to show updated status
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        let message = 'Failed to approve transaction.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.error) {
                            message = xhr.responseJSON.error;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            message = Object.values(xhr.responseJSON.errors).flat().join(', ');
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: message,
                            confirmButtonColor: '#dc3545'
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
                return $.ajax({
                    url: '{{ url("accounting/petty-cash/transactions") }}/' + encodedId + '/reject',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    data: JSON.stringify({
                        rejection_reason: rejectionReason
                    }),
                    contentType: 'application/json',
                    dataType: 'json'
                })
                .then(response => {
                    if (!response.success) {
                        throw new Error(response.message || 'Failed to reject transaction.');
                    }
                    return response;
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
                    title: 'Rejected!',
                    text: result.value.message || 'Transaction has been rejected successfully.',
                    confirmButtonColor: '#dc3545'
                }).then(() => {
                    // Reload the page to show updated status
                    window.location.reload();
                });
            }
        });
    }

    // Post transaction to GL function
    function postTransactionToGL(encodedId) {
        Swal.fire({
            title: 'Post to GL?',
            text: "Are you sure you want to post this transaction to General Ledger?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, post it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while we post the transaction to GL.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

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
                            text: response.message || 'Transaction has been posted to GL successfully.',
                            confirmButtonColor: '#198754'
                        }).then(() => {
                            // Reload the page to show updated status
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        let message = 'Failed to post transaction to GL.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.error) {
                            message = xhr.responseJSON.error;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            message = Object.values(xhr.responseJSON.errors).flat().join(', ');
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: message,
                            confirmButtonColor: '#dc3545'
                        });
                    }
                });
            }
        });
    }

    // Disburse transaction function (Sub-Imprest Mode)
    function disburseTransaction(encodedId) {
        Swal.fire({
            title: 'Disburse Cash?',
            text: "Are you sure you want to disburse cash for this transaction? This will reduce the petty cash balance and mark the transaction as pending receipt.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, disburse!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while we process the disbursement.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: '{{ url("accounting/petty-cash/transactions") }}/' + encodedId + '/disburse',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Disbursed!',
                            text: response.message || 'Cash has been disbursed successfully. Waiting for receipt upload.',
                            confirmButtonColor: '#ffc107'
                        }).then(() => {
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        let message = 'Failed to disburse cash.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: message,
                            confirmButtonColor: '#dc3545'
                        });
                    }
                });
            }
        });
    }

    // Upload receipt function
    $('#uploadReceiptForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const encodedId = '{{ $transaction->encoded_id }}';
        
        Swal.fire({
            title: 'Uploading...',
            text: 'Please wait while we upload the receipt.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '{{ url("accounting/petty-cash/transactions") }}/' + encodedId + '/upload-receipt',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Uploaded!',
                    text: response.message || 'Receipt has been uploaded successfully. Waiting for verification.',
                    confirmButtonColor: '#198754'
                }).then(() => {
                    window.location.reload();
                });
            },
            error: function(xhr) {
                let message = 'Failed to upload receipt.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    message = Object.values(xhr.responseJSON.errors).flat().join(', ');
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: message,
                    confirmButtonColor: '#dc3545'
                });
            }
        });
    });

    // Verify receipt function
    function verifyReceipt(encodedId, verify) {
        const action = verify ? 'verify' : 'reject';
        const title = verify ? 'Verify Receipt?' : 'Reject Receipt?';
        const text = verify 
            ? 'Are you sure you want to verify this receipt? This will post the expense to GL.'
            : 'Are you sure you want to reject this receipt? Please provide a reason.';
        
        Swal.fire({
            title: title,
            html: verify ? text : `
                <div class="text-start">
                    <p class="mb-3">${text}</p>
                    <textarea id="swal-verification_notes" class="form-control" rows="4" placeholder="Enter rejection reason..." style="min-height: 100px;"></textarea>
                </div>
            `,
            icon: verify ? 'question' : 'warning',
            showCancelButton: true,
            confirmButtonColor: verify ? '#198754' : '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: verify ? 'Yes, verify!' : 'Yes, reject!',
            cancelButtonText: 'Cancel',
            didOpen: () => {
                if (!verify) {
                    const textarea = document.getElementById('swal-verification_notes');
                    if (textarea) textarea.focus();
                }
            },
            preConfirm: () => {
                if (!verify) {
                    const notes = document.getElementById('swal-verification_notes').value;
                    if (!notes || notes.trim().length < 5) {
                        Swal.showValidationMessage('Please provide a rejection reason (minimum 5 characters)');
                        return false;
                    }
                    return notes.trim();
                }
                return '';
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while we process the verification.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: '{{ url("accounting/petty-cash/transactions") }}/' + encodedId + '/verify-receipt',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    data: {
                        verify: verify,
                        receipt_verification_notes: result.value || ''
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: verify ? 'Verified!' : 'Rejected!',
                            text: response.message || (verify ? 'Receipt verified and expense posted to GL.' : 'Receipt rejected.'),
                            confirmButtonColor: verify ? '#198754' : '#dc3545'
                        }).then(() => {
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        let message = verify ? 'Failed to verify receipt.' : 'Failed to reject receipt.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            message = Object.values(xhr.responseJSON.errors).flat().join(', ');
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: message,
                            confirmButtonColor: '#dc3545'
                        });
                    }
                });
            }
        });
    }
</script>
@endpush

