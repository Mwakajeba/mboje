@php
    use Vinkla\Hashids\Facades\Hashids;
@endphp
@extends('layouts.main')

@section('title', 'View Rental Quotation')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Rental Quotations', 'url' => route('rental-event-equipment.quotations.index'), 'icon' => 'bx bx-file-blank'],
            ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <h6 class="mb-0 text-uppercase">RENTAL QUOTATION DETAILS</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="card-title d-flex align-items-center">
                                <div><i class="bx bx-file-blank me-1 font-22 text-success"></i></div>
                                <h5 class="mb-0 text-success">Quotation #{{ $quotation->quotation_number }}</h5>
                            </div>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <a href="{{ route('rental-event-equipment.quotations.export-pdf', $quotation) }}" class="btn btn-danger btn-sm" target="_blank">
                                    <i class="bx bxs-file-pdf me-1"></i> Export PDF
                                </a>
                                {{-- Change status / Actions dropdown --}}
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bx bx-cog me-1"></i> Change status / Actions
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @if($quotation->status === 'draft')
                                            <li>
                                                <a class="dropdown-item" href="{{ route('rental-event-equipment.quotations.edit', $quotation) }}">
                                                    <i class="bx bx-edit me-2"></i> Edit
                                                </a>
                                            </li>
                                            <li>
                                                <form action="{{ route('rental-event-equipment.quotations.submit-for-approval', $quotation) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-warning">
                                                        <i class="bx bx-send me-2"></i> Submit for approval
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                        @if($quotation->status === 'rejected')
                                            <li>
                                                <a class="dropdown-item" href="{{ route('rental-event-equipment.quotations.edit', $quotation) }}">
                                                    <i class="bx bx-refresh me-2"></i> Reapply (Edit & resubmit)
                                                </a>
                                            </li>
                                        @endif
                                        @if(in_array($quotation->status, ['approved', 'sent']))
                                            <li>
                                                <a class="dropdown-item" href="{{ route('rental-event-equipment.contracts.create', ['quotation_id' => Hashids::encode($quotation->id)]) }}">
                                                    <i class="bx bx-file me-2"></i> Convert to Contract
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                                @if(in_array($quotation->status, ['approved', 'sent']) && $quotation->status !== 'converted')
                                <a href="{{ route('rental-event-equipment.contracts.create', ['quotation_id' => Hashids::encode($quotation->id)]) }}" class="btn btn-success btn-sm">
                                    <i class="bx bx-file me-1"></i> Convert to Contract
                                </a>
                                @endif
                                <a href="{{ route('rental-event-equipment.quotations.index') }}" class="btn btn-secondary btn-sm">
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
                                            <p class="form-control-plaintext">{{ $quotation->customer->name ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Phone</label>
                                            <p class="form-control-plaintext">{{ $quotation->customer->phone ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quotation Details -->
                        <div class="card border-info mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i> Quotation Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Quotation Date</label>
                                            <p class="form-control-plaintext">{{ $quotation->quotation_date->format('M d, Y') }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Valid Until</label>
                                            <p class="form-control-plaintext">{{ $quotation->valid_until->format('M d, Y') }}</p>
                                        </div>
                                    </div>
                                    @if($quotation->event_date)
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Event Date</label>
                                            <p class="form-control-plaintext">{{ $quotation->event_date->format('M d, Y') }}</p>
                                        </div>
                                    </div>
                                    @endif
                                    @if($quotation->event_location)
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Event Location</label>
                                            <p class="form-control-plaintext">{{ $quotation->event_location }}</p>
                                        </div>
                                    </div>
                                    @endif
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Status</label>
                                            <p class="form-control-plaintext">
                                                @php
                                                    $statusBadge = match($quotation->status) {
                                                        'draft' => 'secondary',
                                                        'pending_approval' => 'warning',
                                                        'sent' => 'info',
                                                        'approved' => 'success',
                                                        'rejected' => 'danger',
                                                        'expired' => 'warning',
                                                        'converted' => 'primary',
                                                        default => 'secondary'
                                                    };
                                                    $statusLabel = match($quotation->status) {
                                                        'pending_approval' => 'Pending Approval',
                                                        default => ucfirst($quotation->status)
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
                                            @foreach($quotation->items as $item)
                                            <tr>
                                                <td>{{ $item->equipment->name ?? 'N/A' }}</td>
                                                <td>{{ $item->equipment->category->name ?? 'N/A' }}</td>
                                                <td class="text-end">{{ $item->quantity }}</td>
                                                <td class="text-end">TZS {{ number_format($item->rental_rate, 2) }}</td>
                                                <td class="text-end">{{ $item->rental_days }}</td>
                                                <td class="text-end">TZS {{ number_format($item->total_amount, 2) }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <td colspan="5" class="text-end"><strong>Subtotal:</strong></td>
                                                <td class="text-end"><strong>TZS {{ number_format($quotation->subtotal, 2) }}</strong></td>
                                            </tr>
                                            @if($quotation->discount_amount > 0)
                                            <tr>
                                                <td colspan="5" class="text-end"><strong>Discount:</strong></td>
                                                <td class="text-end"><strong>TZS {{ number_format($quotation->discount_amount, 2) }}</strong></td>
                                            </tr>
                                            @endif
                                            <tr class="table-success">
                                                <td colspan="5" class="text-end"><strong>Total Amount:</strong></td>
                                                <td class="text-end"><strong>TZS {{ number_format($quotation->total_amount, 2) }}</strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        @if($quotation->notes || $quotation->terms_conditions)
                        <div class="card border-secondary mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bx bx-note me-2"></i> Additional Information</h6>
                            </div>
                            <div class="card-body">
                                @if($quotation->notes)
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Notes</label>
                                    <p class="form-control-plaintext">{{ $quotation->notes }}</p>
                                </div>
                                @endif
                                @if($quotation->terms_conditions)
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Terms & Conditions</label>
                                    <p class="form-control-plaintext">{{ $quotation->terms_conditions }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- Approval Section -->
                        @if(($quotation->status === 'pending_approval' || $quotation->status === 'draft') && !empty($canApprove))
                        <div class="card border-warning mb-3">
                            <div class="card-header bg-warning text-white">
                                <h6 class="mb-0"><i class="bx bx-check-circle me-2"></i> Approval Required {{ isset($userApprovalLevel) ? '(Level '.$userApprovalLevel.')' : '' }}</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-3">This quotation requires your approval{{ isset($userApprovalLevel) ? ' at Level '.$userApprovalLevel : '' }}.</p>
                                <form id="approval-form" method="POST" action="{{ route('rental-event-equipment.approvals.approve', ['type' => 'quotation', 'encodedId' => Hashids::encode($quotation->id)]) }}">
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
                        @elseif($quotation->status === 'pending_approval')
                        <div class="card border-info mb-3">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="bx bx-time me-2"></i> Pending Approval</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">This quotation is awaiting approval from authorized approvers.</p>
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
                                                <td>{{ $approval->comments ?? ($approval->rejection_reason ?? 'N/A') }}</td>
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
                <h5 class="modal-title">Reject Quotation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('rental-event-equipment.approvals.reject', ['type' => 'quotation', 'encodedId' => Hashids::encode($quotation->id)]) }}">
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
                    <button type="submit" class="btn btn-danger">
                        <i class="bx bx-x me-1"></i> Reject Quotation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
