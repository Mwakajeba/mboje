@extends('layouts.main')

@section('title', 'View Customer Deposit')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Customer Deposits', 'url' => route('rental-event-equipment.customer-deposits.index'), 'icon' => 'bx bx-money'],
            ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <h6 class="mb-0 text-uppercase">CUSTOMER DEPOSIT DETAILS</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="card-title d-flex align-items-center">
                                <div><i class="bx bx-money me-1 font-22 text-warning"></i></div>
                                <h5 class="mb-0 text-warning">Deposit #{{ $deposit->deposit_number }}</h5>
                            </div>
                            <div>
                                <a href="{{ route('rental-event-equipment.customer-deposits.export-pdf', $deposit) }}" class="btn btn-danger btn-sm">
                                    <i class="bx bx-file me-1"></i> Export PDF
                                </a>
                                @if(!in_array($deposit->status, ['applied', 'refunded']))
                                <a href="{{ route('rental-event-equipment.customer-deposits.edit', $deposit) }}" class="btn btn-warning btn-sm">
                                    <i class="bx bx-edit me-1"></i> Edit
                                </a>
                                @endif
                                <a href="{{ route('rental-event-equipment.customer-deposits.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="bx bx-arrow-back me-1"></i> Back
                                </a>
                            </div>
                        </div>
                        <hr />

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Customer</label>
                                    <p class="form-control-plaintext">{{ $deposit->customer->name ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Contract</label>
                                    <p class="form-control-plaintext">
                                        @if($deposit->contract)
                                            <a href="{{ route('rental-event-equipment.contracts.show', $deposit->contract) }}">
                                                {{ $deposit->contract->contract_number }}
                                            </a>
                                        @else
                                            N/A
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Deposit Date</label>
                                    <p class="form-control-plaintext">{{ $deposit->deposit_date->format('M d, Y') }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Amount</label>
                                    <p class="form-control-plaintext">TZS {{ number_format($deposit->amount, 2) }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Payment Method</label>
                                    <p class="form-control-plaintext">{{ $deposit->payment_method === 'bank_transfer' ? 'Bank' : ucfirst(str_replace('_', ' ', $deposit->payment_method)) }}</p>
                                </div>
                            </div>
                            @if($deposit->bankAccount)
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Bank Account</label>
                                    <p class="form-control-plaintext">{{ $deposit->bankAccount->name }} @if($deposit->bankAccount->account_number)({{ $deposit->bankAccount->account_number }})@endif</p>
                                </div>
                            </div>
                            @endif
                            @if($deposit->attachment)
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Attachment</label>
                                    <p class="form-control-plaintext">
                                        <a href="{{ Storage::url($deposit->attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bx bx-download me-1"></i>View Attachment
                                        </a>
                                    </p>
                                </div>
                            </div>
                            @endif
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Status</label>
                                    <p class="form-control-plaintext">
                                        @php
                                            $statusBadge = match($deposit->status) {
                                                'draft' => 'secondary',
                                                'pending' => 'warning',
                                                'confirmed' => 'success',
                                                'refunded' => 'info',
                                                'applied' => 'primary',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $statusBadge }}">{{ ucfirst($deposit->status) }}</span>
                                    </p>
                                </div>
                            </div>
                            @if($deposit->reference_number)
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Reference Number</label>
                                    <p class="form-control-plaintext">{{ $deposit->reference_number }}</p>
                                </div>
                            </div>
                            @endif
                            @if($deposit->notes)
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Notes</label>
                                    <p class="form-control-plaintext">{{ $deposit->notes }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Approval Section -->
                @if($deposit->status === 'pending_approval' || $deposit->status === 'draft')
                    @if(isset($canApprove) && $canApprove)
                    <div class="card border-warning mb-3">
                        <div class="card-header bg-warning text-white">
                            <h6 class="mb-0"><i class="bx bx-check-circle me-2"></i> Approval Required @if(isset($userApprovalLevel))(Level {{ $userApprovalLevel }})@endif</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">This deposit requires your approval@if(isset($userApprovalLevel)) at Level {{ $userApprovalLevel }}@endif.</p>
                            <form id="approval-form" method="POST" action="{{ route('rental-event-equipment.approvals.approve', ['type' => 'deposit', 'encodedId' => Hashids::encode($deposit->id)]) }}">
                                @csrf
                                <input type="hidden" name="level" value="{{ $userApprovalLevel ?? 1 }}">
                                <div class="mb-3">
                                    <label for="approval_comments" class="form-label">Comments (Optional)</label>
                                    <textarea class="form-control" id="approval_comments" name="comments" rows="3" placeholder="Add any comments..."></textarea>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bx bx-check me-1"></i> Approve
                                    </button>
                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                        <i class="bx bx-x me-1"></i> Reject
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @elseif($deposit->status === 'pending_approval')
                    <div class="card border-info mb-3">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="bx bx-time me-2"></i> Pending Approval</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">This deposit is awaiting approval from authorized approvers.</p>
                        </div>
                    </div>
                    @endif
                @endif

                <!-- Approval History -->
                @if(isset($allApprovals) && $allApprovals->count() > 0)
                <div class="card border-secondary mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bx bx-history me-2"></i> Approval History</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Level</th>
                                        <th>Approver</th>
                                        <th>Status</th>
                                        <th>Comments</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($allApprovals as $approval)
                                    <tr>
                                        <td>Level {{ $approval->approval_level }}</td>
                                        <td>{{ $approval->approver->name ?? 'N/A' }}</td>
                                        <td>
                                            @if($approval->status === 'approved')
                                                <span class="badge bg-success">Approved</span>
                                            @elseif($approval->status === 'rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                            @else
                                                <span class="badge bg-warning">Pending</span>
                                            @endif
                                        </td>
                                        <td>{{ $approval->comments ?? ($approval->rejection_reason ?? 'N/A') }}</td>
                                        <td>
                                            @if($approval->approved_at)
                                                {{ $approval->approved_at->format('M d, Y h:i A') }}
                                            @elseif($approval->rejected_at)
                                                {{ $approval->rejected_at->format('M d, Y h:i A') }}
                                            @else
                                                {{ $approval->created_at->format('M d, Y h:i A') }}
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Deposit Movement Table -->
                @if(isset($depositMovements) && $depositMovements->count() > 0)
                <div class="card border-info mb-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-transfer me-2"></i> Deposit Movement</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Invoice Number</th>
                                        <th>Customer</th>
                                        <th>Deposit Applied</th>
                                        <th>Invoice Total</th>
                                        <th>Status</th>
                                        <th>Created By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($depositMovements as $invoice)
                                    <tr>
                                        <td>{{ $invoice->invoice_date->format('M d, Y') }}</td>
                                        <td>
                                            <a href="{{ route('rental-event-equipment.rental-invoices.show', $invoice) }}" target="_blank">
                                                {{ $invoice->invoice_number }}
                                            </a>
                                        </td>
                                        <td>{{ $invoice->customer->name ?? 'N/A' }}</td>
                                        <td class="text-end"><strong>TZS {{ number_format($invoice->deposit_applied, 2) }}</strong></td>
                                        <td class="text-end">TZS {{ number_format($invoice->total_amount, 2) }}</td>
                                        <td>
                                            @php
                                                $statusBadge = match($invoice->status) {
                                                    'draft' => 'secondary',
                                                    'sent' => 'primary',
                                                    'paid' => 'success',
                                                    'overdue' => 'danger',
                                                    'cancelled' => 'dark',
                                                    default => 'secondary'
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $statusBadge }}">{{ ucfirst($invoice->status) }}</span>
                                        </td>
                                        <td>{{ $invoice->creator->name ?? 'N/A' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3" class="text-end">Total Deposits Applied:</th>
                                        <th class="text-end">TZS {{ number_format($depositMovements->sum('deposit_applied'), 2) }}</th>
                                        <th colspan="3"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @elseif($deposit->contract_id)
                <div class="card border-secondary mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bx bx-transfer me-2"></i> Deposit Movement</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-0">No deposits have been applied to invoices yet.</p>
                    </div>
                </div>
                @endif

                <!-- Reject Modal -->
                @if(isset($canApprove) && $canApprove)
                <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title" id="rejectModalLabel">Reject Deposit</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST" action="{{ route('rental-event-equipment.approvals.reject', ['type' => 'deposit', 'encodedId' => Hashids::encode($deposit->id)]) }}">
                                @csrf
                                <input type="hidden" name="level" value="{{ $userApprovalLevel ?? 1 }}">
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required placeholder="Please provide a reason for rejection..."></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-danger">Reject Deposit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@php
    use Vinkla\Hashids\Facades\Hashids;
@endphp
@endpush
