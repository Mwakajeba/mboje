@php
    use Vinkla\Hashids\Facades\Hashids;
@endphp
@extends('layouts.main')

@section('title', 'View Rental Contract')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Rental Contracts', 'url' => route('rental-event-equipment.contracts.index'), 'icon' => 'bx bx-file'],
            ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
            <h6 class="mb-0 text-uppercase">RENTAL CONTRACT DETAILS</h6>
            <hr />

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="card-title d-flex align-items-center">
                                    <div><i class="bx bx-file me-1 font-22 text-success"></i></div>
                                    <h5 class="mb-0 text-success">Contract #{{ $contract->contract_number }}</h5>
                                </div>
                                <div>
                                    @if(isset($canConvertToDispatch) && $canConvertToDispatch)
                                        <a href="{{ route('rental-event-equipment.rental-dispatches.create', ['contract_id' => $contract->id]) }}"
                                            class="btn btn-info btn-sm">
                                            <i class="bx bx-send me-1"></i> Convert to Dispatch
                                        </a>
                                    @endif
                                    <a href="{{ route('rental-event-equipment.contracts.export-pdf', $contract) }}"
                                        class="btn btn-danger btn-sm" target="_blank">
                                        <i class="bx bxs-file-pdf me-1"></i> Export PDF
                                    </a>
                                    @if($contract->status === 'draft')
                                        <a href="{{ route('rental-event-equipment.contracts.edit', $contract) }}"
                                            class="btn btn-warning btn-sm">
                                            <i class="bx bx-edit me-1"></i> Edit
                                        </a>
                                    @endif
                                    <a href="{{ route('rental-event-equipment.contracts.index') }}"
                                        class="btn btn-secondary btn-sm">
                                        <i class="bx bx-arrow-back me-1"></i> Back
                                    </a>
                                </div>
                            </div>
                            <hr />

                            <!-- Customer Information -->
                            <div class="card border-primary mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bx bx-user me-2"></i> Customer Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Customer Name</label>
                                                <p class="form-control-plaintext">{{ $contract->customer->name ?? 'N/A' }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Phone</label>
                                                <p class="form-control-plaintext">{{ $contract->customer->phone ?? 'N/A' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contract Details -->
                            <div class="card border-info mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i> Contract Details</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Contract Date</label>
                                                <p class="form-control-plaintext">
                                                    {{ $contract->contract_date->format('M d, Y') }}
                                                </p>
                                            </div>
                                        </div>
                                        @if($contract->quotation)
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Quotation Number</label>
                                                    <p class="form-control-plaintext">
                                                        <a
                                                            href="{{ route('rental-event-equipment.quotations.show', $contract->quotation) }}">
                                                            {{ $contract->quotation->quotation_number }}
                                                        </a>
                                                    </p>
                                                </div>
                                            </div>
                                        @endif
                                        @if($contract->event_date)
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Event Date</label>
                                                    <p class="form-control-plaintext">
                                                        {{ $contract->event_date->format('M d, Y') }}
                                                    </p>
                                                </div>
                                            </div>
                                        @endif
                                        @if($contract->rental_start_date)
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Rental Start Date</label>
                                                    <p class="form-control-plaintext">
                                                        {{ $contract->rental_start_date->format('M d, Y') }}
                                                    </p>
                                                </div>
                                            </div>
                                        @endif
                                        @if($contract->rental_end_date)
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Rental End Date</label>
                                                    <p class="form-control-plaintext">
                                                        {{ $contract->rental_end_date->format('M d, Y') }}
                                                    </p>
                                                </div>
                                            </div>
                                        @endif
                                        @if($contract->event_location)
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Event Location</label>
                                                    <p class="form-control-plaintext">{{ $contract->event_location }}</p>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Status</label>
                                                <p class="form-control-plaintext">
                                                    @php
                                                        $statusBadge = match ($contract->status) {
                                                            'draft' => 'secondary',
                                                            'pending_approval' => 'warning',
                                                            'active' => 'success',
                                                            'approved' => 'success',
                                                            'completed' => 'info',
                                                            'cancelled' => 'danger',
                                                            'rejected' => 'danger',
                                                            default => 'secondary'
                                                        };
                                                        $statusLabel = match ($contract->status) {
                                                            'pending_approval' => 'Pending Approval',
                                                            default => ucfirst($contract->status)
                                                        };
                                                    @endphp
                                                    <span class="badge bg-{{ $statusBadge }}">{{ $statusLabel }}</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Equipment Items -->
                            <div class="card border-success mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bx bx-package me-2"></i> Equipment Items</h6>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="bx bx-info-circle me-2"></i>
                                        <strong>Note:</strong> Equipment status has been changed from Available to Reserved.
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Equipment</th>
                                                    <th>Category</th>
                                                    <th class="text-end">Quantity</th>
                                                    <th class="text-end">Rate/Day</th>
                                                    <th class="text-end">Days</th>
                                                    <th class="text-end">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($contract->items as $item)
                                                    <tr>
                                                        <td>{{ $item->equipment->name ?? 'N/A' }}</td>
                                                        <td>{{ $item->equipment->category->name ?? 'N/A' }}</td>
                                                        <td class="text-end">{{ $item->quantity }}</td>
                                                        <td class="text-end">TZS {{ number_format($item->rental_rate, 2) }}</td>
                                                        <td class="text-end">{{ $item->rental_days }}</td>
                                                        <td class="text-end">TZS {{ number_format($item->total_amount, 2) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="table-light">
                                                <tr>
                                                    <td colspan="5" class="text-end"><strong>Subtotal:</strong></td>
                                                    <td class="text-end"><strong>TZS
                                                            {{ number_format($contract->subtotal, 2) }}</strong></td>
                                                </tr>
                                                @if($contract->discount_amount > 0)
                                                    <tr>
                                                        <td colspan="5" class="text-end"><strong>Discount:</strong></td>
                                                        <td class="text-end"><strong>TZS
                                                                {{ number_format($contract->discount_amount, 2) }}</strong></td>
                                                    </tr>
                                                @endif
                                                <tr class="table-success">
                                                    <td colspan="5" class="text-end"><strong>Total Amount:</strong></td>
                                                    <td class="text-end"><strong>TZS
                                                            {{ number_format($contract->total_amount, 2) }}</strong></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            @if($contract->notes || $contract->terms_conditions)
                                <div class="card border-secondary mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bx bx-note me-2"></i> Additional Information</h6>
                                    </div>
                                    <div class="card-body">
                                        @if($contract->notes)
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Notes</label>
                                                <p class="form-control-plaintext">{{ $contract->notes }}</p>
                                            </div>
                                        @endif
                                        @if($contract->terms_conditions)
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Terms & Conditions</label>
                                                <p class="form-control-plaintext">{{ $contract->terms_conditions }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Approval Section -->
                            @if(($contract->status === 'pending_approval' || $contract->status === 'draft') && $canApprove)
                                <div class="card border-warning mb-3">
                                    <div class="card-header bg-warning text-white">
                                        <h6 class="mb-0">
                                            <i class="bx bx-check-circle me-2"></i>
                                            Approval Required
                                            @if(isset($userApprovalLevel))
                                                (Level {{ $userApprovalLevel }})
                                            @endif
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-3">
                                            This contract requires your approval
                                            @if(isset($userApprovalLevel))
                                                at Level {{ $userApprovalLevel }}
                                            @endif.
                                        </p>
                                        <form id="approval-form" method="POST"
                                            action="{{ route('rental-event-equipment.approvals.approve', ['type' => 'contract', 'encodedId' => Hashids::encode($contract->id)]) }}">
                                            @csrf
                                            <input type="hidden" name="level" value="{{ $userApprovalLevel ?? 1 }}">
                                            <div class="mb-3">
                                                <label for="approval_comments" class="form-label">Comments (Optional)</label>
                                                <textarea class="form-control" id="approval_comments" name="comments" rows="3"
                                                    placeholder="Add any comments..."></textarea>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-success">
                                                    <i class="bx bx-check me-1"></i> Approve
                                                </button>
                                                <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                                                    data-bs-target="#rejectModal">
                                                    <i class="bx bx-x me-1"></i> Reject
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @elseif($contract->status === 'pending_approval')
                                <div class="card border-info mb-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="bx bx-time me-2"></i> Pending Approval</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-0">This contract is awaiting approval from authorized approvers.</p>
                                    </div>
                                </div>
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
                                                            <td>{{ $approval->comments ?? ($approval->rejection_reason ?? 'N/A') }}
                                                            </td>
                                                            <td>
                                                                @if($approval->approved_at)
                                                                    {{ $approval->approved_at->format('M d, Y H:i') }}
                                                                @elseif($approval->rejected_at)
                                                                    {{ $approval->rejected_at->format('M d, Y H:i') }}
                                                                @else
                                                                    Pending
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Contract</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST"
                    action="{{ route('rental-event-equipment.approvals.reject', ['type' => 'contract', 'encodedId' => Hashids::encode($contract->id)]) }}">
                    @csrf
                    <input type="hidden" name="level" value="{{ $userApprovalLevel ?? 1 }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="rejection_reason" class="form-label">Rejection Reason <span
                                    class="text-danger">*</span></label>
                            <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" required
                                placeholder="Please provide a reason for rejection..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bx bx-x me-1"></i> Reject Contract
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection