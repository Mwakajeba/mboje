@extends('layouts.main')

@section('title', 'Inter-Account Transfer Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Management', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Inter-Account Transfers', 'url' => route('accounting.account-transfers.index'), 'icon' => 'bx bx-transfer'],
            ['label' => 'Transfer #' . $transfer->transfer_number, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h6 class="mb-0 text-uppercase">INTER-ACCOUNT TRANSFER DETAILS</h6>
                <p class="text-muted mb-0">View transfer information</p>
            </div>
            <div>
                <a href="{{ route('accounting.account-transfers.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-2"></i>Back to Transfers
                </a>
                <a href="{{ route('accounting.account-transfers.export-pdf', $transfer->encoded_id) }}" class="btn btn-info" target="_blank">
                    <i class="bx bx-file me-1"></i>Export PDF
                </a>
                @if($transfer->canBeEdited())
                    <a href="{{ route('accounting.account-transfers.edit', $transfer->encoded_id) }}" class="btn btn-warning">
                        <i class="bx bx-edit me-1"></i>Edit
                    </a>
                @endif
                @if($transfer->canBeDeleted())
                    <button type="button" class="btn btn-danger" onclick="deleteTransfer('{{ $transfer->encoded_id }}')">
                        <i class="bx bx-trash me-1"></i>Delete
                    </button>
                @endif
            </div>
        </div>
        <hr />

        <!-- Status Notice -->
        @if($transfer->status === 'draft')
        <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bx bx-info-circle font-size-24 me-3"></i>
                <div>
                    <h6 class="alert-heading mb-1">Draft Transfer</h6>
                    <p class="mb-0">This transfer is saved as a draft and has not been submitted for approval yet. 
                    @if($transfer->canBeEdited())
                        <a href="{{ route('accounting.account-transfers.edit', $transfer->encoded_id) }}" class="alert-link">Edit and submit</a> the transfer to send it for approval.
                    @endif
                    </p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @elseif($transfer->status === 'submitted')
        <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bx bx-time font-size-24 me-3"></i>
                <div>
                    <h6 class="alert-heading mb-1">Pending Approval</h6>
                    <p class="mb-0">This transfer is awaiting approval before it can be posted to GL.</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @elseif($transfer->status === 'approved')
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bx bx-check-circle font-size-24 me-3"></i>
                <div>
                    <h6 class="alert-heading mb-1">Approved</h6>
                    <p class="mb-0">This transfer has been approved and is ready to be posted to GL.</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @elseif($transfer->status === 'posted')
        <div class="alert alert-primary alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bx bx-check-double font-size-24 me-3"></i>
                <div>
                    <h6 class="alert-heading mb-1">Posted to GL</h6>
                    <p class="mb-0">This transfer has been posted to the General Ledger and cannot be modified.</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @elseif($transfer->status === 'rejected')
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bx bx-x-circle font-size-24 me-3"></i>
                <div>
                    <h6 class="alert-heading mb-1">Rejected</h6>
                    <p class="mb-0">This transfer has been rejected. @if($transfer->rejection_reason) Reason: {{ $transfer->rejection_reason }} @endif</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <!-- Prominent Header Card -->
        <div class="card radius-10 bg-primary text-white mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-lg bg-white text-primary rounded-circle me-3 d-flex align-items-center justify-content-center">
                        <i class="bx bx-transfer font-size-32"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h3 class="mb-1">Transfer #{{ $transfer->transfer_number }}</h3>
                        <p class="mb-0 opacity-75">{{ $transfer->description ?: 'No description provided' }}</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        @php
                            $statusColors = [
                                'draft' => 'secondary',
                                'submitted' => 'info',
                                'approved' => 'success',
                                'posted' => 'primary',
                                'rejected' => 'danger'
                            ];
                            $color = $statusColors[$transfer->status] ?? 'secondary';
                        @endphp
                        <span class="badge bg-{{ $color }}">{{ ucfirst($transfer->status) }}</span>
                        <span class="badge bg-light text-dark">
                            <i class="bx bx-calendar me-1"></i>
                            {{ $transfer->transfer_date->format('d M Y') }}
                        </span>
                        <span class="badge bg-light text-dark">
                            <i class="bx bx-dollar me-1"></i>
                            TZS {{ number_format($transfer->amount, 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column - Main Information -->
            <div class="col-lg-8">
                <!-- Transfer Details -->
                <div class="card radius-10 mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Transfer Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Transfer Number</label>
                                <p class="form-control-plaintext">{{ $transfer->transfer_number }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Transfer Date</label>
                                <p class="form-control-plaintext">{{ $transfer->transfer_date->format('d M Y') }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Reference Number</label>
                                <p class="form-control-plaintext">{{ $transfer->reference_number ?: 'N/A' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Branch</label>
                                <p class="form-control-plaintext">{{ $transfer->branch->name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold">Description</label>
                                <p class="form-control-plaintext">{{ $transfer->description ?: 'No description provided' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Information -->
                <div class="card radius-10 mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bx bx-wallet me-2"></i>Account Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-primary">From Account</label>
                                @php
                                    $fromAccount = null;
                                    $fromType = ucfirst(str_replace('_', ' ', $transfer->from_account_type));
                                    switch ($transfer->from_account_type) {
                                        case 'bank':
                                            $fromAccount = \App\Models\BankAccount::find($transfer->from_account_id);
                                            break;
                                        case 'cash':
                                            $fromAccount = \App\Models\CashDepositAccount::find($transfer->from_account_id);
                                            break;
                                        case 'petty_cash':
                                            $fromAccount = \App\Models\PettyCash\PettyCashUnit::find($transfer->from_account_id);
                                            break;
                                    }
                                @endphp
                                <p class="form-control-plaintext">
                                    <span class="badge bg-primary me-2">{{ $fromType }}</span>
                                    {{ $fromAccount->name ?? 'N/A' }}
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-success">To Account</label>
                                @php
                                    $toAccount = null;
                                    $toType = ucfirst(str_replace('_', ' ', $transfer->to_account_type));
                                    switch ($transfer->to_account_type) {
                                        case 'bank':
                                            $toAccount = \App\Models\BankAccount::find($transfer->to_account_id);
                                            break;
                                        case 'cash':
                                            $toAccount = \App\Models\CashDepositAccount::find($transfer->to_account_id);
                                            break;
                                        case 'petty_cash':
                                            $toAccount = \App\Models\PettyCash\PettyCashUnit::find($transfer->to_account_id);
                                            break;
                                    }
                                @endphp
                                <p class="form-control-plaintext">
                                    <span class="badge bg-success me-2">{{ $toType }}</span>
                                    {{ $toAccount->name ?? 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Amount & Charges -->
                <div class="card radius-10 mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bx bx-dollar me-2"></i>Amount & Charges</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Transfer Amount</label>
                                <p class="form-control-plaintext h5 text-primary">
                                    TZS {{ number_format($transfer->amount, 2) }}
                                </p>
                            </div>
                            @if($transfer->charges && $transfer->charges > 0)
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Bank Charges</label>
                                <p class="form-control-plaintext">
                                    TZS {{ number_format($transfer->charges, 2) }}
                                    @if($transfer->chargesAccount)
                                        <br><small class="text-muted">Account: {{ $transfer->chargesAccount->account_code }} - {{ $transfer->chargesAccount->account_name }}</small>
                                    @endif
                                </p>
                            </div>
                            @endif
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Total Amount</label>
                                <p class="form-control-plaintext h5 text-success">
                                    TZS {{ number_format($transfer->amount + ($transfer->charges ?? 0), 2) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attachment -->
                @if($transfer->attachment)
                <div class="card radius-10 mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bx bx-paperclip me-2"></i>Attachment</h5>
                    </div>
                    <div class="card-body">
                        <a href="{{ asset('storage/' . $transfer->attachment) }}" target="_blank" class="btn btn-outline-primary">
                            <i class="bx bx-file me-1"></i>View Attachment
                        </a>
                    </div>
                </div>
                @endif

                <!-- Journal Entries (Double Entry) -->
                @if($transfer->journal_id && $transfer->journal && $transfer->journal->items)
                <div class="card radius-10 mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="bx bx-book me-2"></i>General Ledger Entries
                            <small class="opacity-75">(Journal #{{ $transfer->journal->reference }})</small>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="35%">Account</th>
                                        <th width="15%">Nature</th>
                                        <th width="25%" class="text-end">Amount</th>
                                        <th width="20%">Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalDebit = 0;
                                        $totalCredit = 0;
                                    @endphp
                                    @foreach($transfer->journal->items as $index => $item)
                                        @php
                                            if ($item->nature === 'debit') {
                                                $totalDebit += $item->amount;
                                            } else {
                                                $totalCredit += $item->amount;
                                            }
                                        @endphp
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <strong>{{ $item->chartAccount->account_code ?? 'N/A' }}</strong><br>
                                                <small class="text-muted">{{ $item->chartAccount->account_name ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                @if($item->nature === 'debit')
                                                    <span class="badge bg-success">Debit</span>
                                                @else
                                                    <span class="badge bg-danger">Credit</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <strong class="{{ $item->nature === 'debit' ? 'text-success' : 'text-danger' }}">
                                                    TZS {{ number_format($item->amount, 2) }}
                                                </strong>
                                            </td>
                                            <td>
                                                <small>{{ $item->description ?: 'No description' }}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="2">Total</th>
                                        <th class="text-center">
                                            <span class="badge bg-success">Debit: TZS {{ number_format($totalDebit, 2) }}</span><br>
                                            <span class="badge bg-danger">Credit: TZS {{ number_format($totalCredit, 2) }}</span>
                                        </th>
                                        <th class="text-end">
                                            @if(abs($totalDebit - $totalCredit) < 0.01)
                                                <span class="badge bg-success">Balanced</span>
                                            @else
                                                <span class="badge bg-danger">Unbalanced</span>
                                            @endif
                                        </th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column - Status & Actions -->
            <div class="col-lg-4">
                <!-- Status Information -->
                <div class="card radius-10 mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Status Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Current Status</label>
                            <div class="mt-2">
                                @php
                                    $statusColors = [
                                        'draft' => 'secondary',
                                        'submitted' => 'info',
                                        'approved' => 'success',
                                        'posted' => 'primary',
                                        'rejected' => 'danger'
                                    ];
                                    $color = $statusColors[$transfer->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $color }} fs-6">{{ ucfirst($transfer->status) }}</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Created By</label>
                            <p class="form-control-plaintext">{{ $transfer->createdBy->name ?? 'N/A' }}</p>
                            <small class="text-muted">{{ $transfer->created_at->format('d M Y, h:i A') }}</small>
                        </div>
                        @if($transfer->approved_by)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Approved By</label>
                            <p class="form-control-plaintext">{{ $transfer->approvedBy->name ?? 'N/A' }}</p>
                            <small class="text-muted">{{ $transfer->approved_at ? $transfer->approved_at->format('d M Y, h:i A') : 'N/A' }}</small>
                        </div>
                        @endif
                        @if($transfer->approval_notes)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Approval Notes</label>
                            <p class="form-control-plaintext">{{ $transfer->approval_notes }}</p>
                        </div>
                        @endif
                        @if($transfer->rejection_reason)
                        <div class="mb-3">
                            <label class="form-label fw-bold text-danger">Rejection Reason</label>
                            <p class="form-control-plaintext">{{ $transfer->rejection_reason }}</p>
                        </div>
                        @endif
                        @if($transfer->journal_id)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Journal Entry</label>
                            <p class="form-control-plaintext">
                                <span class="badge bg-success">Posted to GL</span>
                            </p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Approval Information -->
                @if($approvalSettings && $approvalSettings->require_approval_for_all)
                <div class="card radius-10 mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bx bx-user-check me-2"></i>Who Can Approve</h5>
                    </div>
                    <div class="card-body">
                        @if($transfer->status === 'draft' && $transfer->canBeApproved())
                            <div class="alert alert-info mb-3">
                                <i class="bx bx-info-circle me-2"></i>
                                <strong>Note:</strong> As an approver, you can approve this draft transfer directly.
                            </div>
                        @elseif($transfer->status === 'draft')
                            <div class="alert alert-warning mb-3">
                                <i class="bx bx-info-circle me-2"></i>
                                <strong>Note:</strong> This transfer must be submitted before it can be approved.
                            </div>
                        @endif
                        
                        @if(count($approvers) > 0)
                            @foreach($approvers as $level => $approverInfo)
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Level {{ $level }} Approvers</label>
                                    @if($approverInfo['type'] === 'role')
                                        <div class="mb-2">
                                            <small class="text-muted d-block mb-1">Approval by Role:</small>
                                            @foreach($approverInfo['roles'] as $role)
                                                <span class="badge bg-primary me-1">{{ ucfirst($role->name) }}</span>
                                            @endforeach
                                        </div>
                                        @if($approverInfo['users']->count() > 0)
                                            <div>
                                                <small class="text-muted d-block mb-1">Users with these roles:</small>
                                                <ul class="list-unstyled mb-0">
                                                    @foreach($approverInfo['users'] as $user)
                                                        <li class="mb-1">
                                                            <i class="bx bx-user me-1"></i>
                                                            {{ $user->name }}
                                                            <small class="text-muted">({{ $user->email }})</small>
                                                            @if(auth()->id() == $user->id)
                                                                <span class="badge bg-success ms-1">You</span>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @else
                                            <p class="text-muted mb-0"><small>No users assigned to these roles</small></p>
                                        @endif
                                    @elseif($approverInfo['type'] === 'user')
                                        <ul class="list-unstyled mb-0">
                                            @foreach($approverInfo['users'] as $user)
                                                <li class="mb-1">
                                                    <i class="bx bx-user me-1"></i>
                                                    {{ $user->name }}
                                                    <small class="text-muted">({{ $user->email }})</small>
                                                    @if(auth()->id() == $user->id)
                                                        <span class="badge bg-success ms-1">You</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                                @if(!$loop->last)
                                    <hr>
                                @endif
                            @endforeach
                        @else
                            <p class="text-muted mb-0">No approvers configured</p>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Actions -->
                <div class="card radius-10 mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bx bx-cog me-2"></i>Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            @if($transfer->canBeEdited())
                                <a href="{{ route('accounting.account-transfers.edit', $transfer->encoded_id) }}" class="btn btn-warning">
                                    <i class="bx bx-edit me-1"></i>Edit Transfer
                                </a>
                            @endif
                            
                            @if($transfer->canBeSubmitted())
                                <button type="button" class="btn btn-info" onclick="submitTransfer('{{ $transfer->encoded_id }}')">
                                    <i class="bx bx-paper-plane me-1"></i>Submit for Approval
                                </button>
                            @endif
                            
                            @if($transfer->canBeApproved())
                                <button type="button" class="btn btn-success" onclick="approveTransfer('{{ $transfer->encoded_id }}')">
                                    <i class="bx bx-check me-1"></i>Approve Transfer
                                </button>
                            @endif
                            
                            @if($transfer->canBeRejected())
                                <button type="button" class="btn btn-danger" onclick="rejectTransfer('{{ $transfer->encoded_id }}')">
                                    <i class="bx bx-x me-1"></i>Reject Transfer
                                </button>
                            @endif
                            
                            @if($transfer->canBePosted())
                                <button type="button" class="btn btn-primary" onclick="postTransferToGL('{{ $transfer->encoded_id }}')">
                                    <i class="bx bx-book me-1"></i>Post to GL
                                </button>
                            @endif
                            
                            @if($transfer->canBeDeleted())
                                <button type="button" class="btn btn-danger" onclick="deleteTransfer('{{ $transfer->encoded_id }}')">
                                    <i class="bx bx-trash me-1"></i>Delete Transfer
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    // Submit transfer for approval
    function submitTransfer(encodedId) {
        Swal.fire({
            title: 'Submit for Approval?',
            text: 'Are you sure you want to submit this transfer for approval?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0dcaf0',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, submit it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("accounting.account-transfers.update", ":id") }}'.replace(':id', encodedId),
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'PUT',
                        action: 'submit',
                        transfer_date: '{{ $transfer->transfer_date->format("Y-m-d") }}',
                        from_account_type: '{{ $transfer->from_account_type }}',
                        from_account_id: '{{ $transfer->from_account_id }}',
                        to_account_type: '{{ $transfer->to_account_type }}',
                        to_account_id: '{{ $transfer->to_account_id }}',
                        amount: '{{ $transfer->amount }}',
                        charges: '{{ $transfer->charges ?? 0 }}',
                        description: '{{ addslashes($transfer->description) }}',
                        reference_number: '{{ $transfer->reference_number ?? "" }}',
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Submitted!',
                            text: 'Transfer submitted for approval successfully',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        let message = 'Failed to submit transfer';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = Object.values(xhr.responseJSON.errors).flat();
                            message = errors.join(', ');
                        }
                        Swal.fire('Error', message, 'error');
                    }
                });
            }
        });
    }

    // Approve transfer
    function approveTransfer(encodedId) {
        Swal.fire({
            title: 'Approve Transfer?',
            text: 'Are you sure you want to approve this transfer?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, approve it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("accounting.account-transfers.approve", ":id") }}'.replace(':id', encodedId),
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        approval_notes: ''
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Approved!',
                                text: response.message || 'Transfer approved successfully',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message || 'Failed to approve transfer', 'error');
                        }
                    },
                    error: function(xhr) {
                        let message = 'Failed to approve transfer';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', message, 'error');
                    }
                });
            }
        });
    }

    // Reject transfer
    function rejectTransfer(encodedId) {
        Swal.fire({
            title: 'Reject Transfer?',
            text: 'Please provide a reason for rejecting this transfer.',
            icon: 'warning',
            input: 'textarea',
            inputLabel: 'Rejection Reason',
            inputPlaceholder: 'Enter the reason for rejection (minimum 10 characters)...',
            inputAttributes: {
                'aria-label': 'Enter the reason for rejection'
            },
            inputValidator: (value) => {
                if (!value || value.length < 10) {
                    return 'Rejection reason must be at least 10 characters long';
                }
            },
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, reject it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("accounting.account-transfers.reject", ":id") }}'.replace(':id', encodedId),
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        rejection_reason: result.value
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Rejected!',
                                text: response.message || 'Transfer rejected successfully',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message || 'Failed to reject transfer', 'error');
                        }
                    },
                    error: function(xhr) {
                        let message = 'Failed to reject transfer';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', message, 'error');
                    }
                });
            }
        });
    }

    // Post transfer to GL
    function postTransferToGL(encodedId) {
        Swal.fire({
            title: 'Post to GL?',
            text: 'Are you sure you want to post this transfer to the General Ledger?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, post it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("accounting.account-transfers.post-to-gl", ":id") }}'.replace(':id', encodedId),
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Posted!',
                                text: response.message || 'Transfer posted to GL successfully',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message || 'Failed to post transfer to GL', 'error');
                        }
                    },
                    error: function(xhr) {
                        let message = 'Failed to post transfer to GL';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', message, 'error');
                    }
                });
            }
        });
    }

    // Delete transfer
    function deleteTransfer(encodedId) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'This action cannot be undone. The transfer will be permanently deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("accounting.account-transfers.destroy", ":id") }}'.replace(':id', encodedId),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.message || 'Transfer deleted successfully',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = '{{ route("accounting.account-transfers.index") }}';
                            });
                        } else {
                            Swal.fire('Error', response.message || 'Failed to delete transfer', 'error');
                        }
                    },
                    error: function(xhr) {
                        let message = 'Failed to delete transfer';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', message, 'error');
                    }
                });
            }
        });
    }
</script>
@endpush
@endsection

