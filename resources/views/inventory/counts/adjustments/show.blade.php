@extends('layouts.main')

@section('title', 'Adjustment Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Management', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Inventory Count', 'url' => route('inventory.counts.index'), 'icon' => 'bx bx-clipboard-check'],
            ['label' => 'Adjustment Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        
        <h6 class="mb-0 text-uppercase">ADJUSTMENT DETAILS</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Adjustment: {{ $adjustment->adjustment_number }}</h5>
                        <div>
                            @if($adjustment->status === 'pending_approval' && $currentApprovalLevel)
                                <span class="badge bg-warning me-2">
                                    <i class="bx bx-time me-1"></i>Awaiting Level {{ $currentApprovalLevel }} Approval
                                </span>
                            @endif
                            @if($adjustment->status === 'approved')
                                <form action="{{ route('inventory.counts.adjustments.post-to-gl', $adjustment->encoded_id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to post this adjustment to GL?')">
                                        <i class="bx bx-upload me-1"></i> Post to GL
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>Adjustment Information</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th>Adjustment Number:</th>
                                        <td>{{ $adjustment->adjustment_number }}</td>
                                    </tr>
                                    <tr>
                                        <th>Item:</th>
                                        <td>{{ $adjustment->item->name }} ({{ $adjustment->item->code }})</td>
                                    </tr>
                                    <tr>
                                        <th>Location:</th>
                                        <td>{{ $adjustment->location->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Adjustment Type:</th>
                                        <td>
                                            <span class="badge bg-{{ $adjustment->adjustment_type === 'surplus' ? 'success' : 'danger' }}">
                                                {{ ucfirst($adjustment->adjustment_type) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Adjustment Quantity:</th>
                                        <td>{{ number_format($adjustment->adjustment_quantity, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Adjustment Value:</th>
                                        <td><strong>TZS {{ number_format($adjustment->adjustment_value, 2) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            <span class="badge bg-{{ $adjustment->status === 'posted' ? 'success' : ($adjustment->status === 'approved' ? 'info' : ($adjustment->status === 'pending_approval' ? 'warning' : 'secondary')) }}">
                                                {{ ucfirst(str_replace('_', ' ', $adjustment->status)) }}
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Reason & Comments</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th>Reason Code:</th>
                                        <td>{{ ucfirst(str_replace('_', ' ', $adjustment->reason_code)) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Reason Description:</th>
                                        <td>{{ $adjustment->reason_description }}</td>
                                    </tr>
                                    @if($adjustment->supervisor_comments)
                                    <tr>
                                        <th>Supervisor Comments:</th>
                                        <td>{{ $adjustment->supervisor_comments }}</td>
                                    </tr>
                                    @endif
                                    @if($adjustment->finance_comments)
                                    <tr>
                                        <th>Finance Comments:</th>
                                        <td>{{ $adjustment->finance_comments }}</td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                        </div>

                        <!-- Approval Workflow Section -->
                        @if($adjustment->status === 'pending_approval')
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary bg-opacity-10">
                                        <h6 class="mb-0">
                                            <i class="bx bx-check-circle me-2"></i>Multi-Level Approval Workflow
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            @foreach($adjustment->approvals->sortBy('approval_level') as $approval)
                                            <div class="col-md-6 mb-3">
                                                <div class="card {{ $approval->status === 'approved' ? 'border-success' : ($approval->status === 'rejected' ? 'border-danger' : ($approval->approval_level == $currentApprovalLevel ? 'border-primary border-2' : 'border-secondary')) }}">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <div>
                                                                <h6 class="mb-1">
                                                                    Level {{ $approval->approval_level }}: {{ $approval->level_name }}
                                                                </h6>
                                                                @if($approval->approval_level == $currentApprovalLevel && $approval->status === 'pending')
                                                                <span class="badge bg-primary">
                                                                    <i class="bx bx-time me-1"></i>Current Approval Required
                                                                </span>
                                                                @elseif($approval->status === 'approved')
                                                                <span class="badge bg-success">
                                                                    <i class="bx bx-check me-1"></i>Approved
                                                                </span>
                                                                @elseif($approval->status === 'rejected')
                                                                <span class="badge bg-danger">
                                                                    <i class="bx bx-x me-1"></i>Rejected
                                                                </span>
                                                                @else
                                                                <span class="badge bg-secondary">
                                                                    <i class="bx bx-hourglass me-1"></i>Pending
                                                                </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        
                                                        @if($approval->status === 'approved' && $approval->approver)
                                                        <p class="mb-1 text-success small">
                                                            <i class="bx bx-user me-1"></i>Approved by: <strong>{{ $approval->approver->name ?? 'N/A' }}</strong>
                                                        </p>
                                                        <p class="mb-0 text-muted small">
                                                            <i class="bx bx-time me-1"></i>{{ $approval->approved_at ? $approval->approved_at->format('M d, Y H:i') : 'N/A' }}
                                                        </p>
                                                        @if($approval->comments)
                                                        <p class="mb-0 mt-2 small">
                                                            <strong>Comments:</strong> {{ $approval->comments }}
                                                        </p>
                                                        @endif
                                                        @elseif($approval->status === 'rejected')
                                                        <p class="mb-1 text-danger small">
                                                            <i class="bx bx-x-circle me-1"></i>Rejected
                                                        </p>
                                                        @if($approval->rejection_reason)
                                                        <p class="mb-0 text-danger small">
                                                            <strong>Reason:</strong> {{ $approval->rejection_reason }}
                                                        </p>
                                                        @endif
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>

                                        <!-- Submit for Approval Section -->
                                        @if($currentApprovalLevel && $currentApproval && $currentApproval->status === 'pending')
                                        <div class="alert alert-info mt-3 mb-0">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">
                                                        <i class="bx bx-info-circle me-2"></i>Submit for Approval
                                                    </h6>
                                                    <p class="mb-0">
                                                        This adjustment requires <strong>Level {{ $currentApprovalLevel }}: {{ $currentApproval->level_name }}</strong> approval.
                                                        Click the button below to approve or reject this adjustment.
                                                    </p>
                                                </div>
                                                <div class="ms-3">
                                                    <form action="{{ route('inventory.counts.adjustments.approve', $adjustment->encoded_id) }}" method="POST" class="d-inline me-2" id="approveForm">
                                                        @csrf
                                                        <div class="mb-2">
                                                            <textarea name="comments" class="form-control form-control-sm" rows="2" placeholder="Add approval comments (optional)"></textarea>
                                                        </div>
                                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to approve this adjustment at Level {{ $currentApprovalLevel }}?')">
                                                            <i class="bx bx-check me-1"></i> Approve Level {{ $currentApprovalLevel }}
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                                        <i class="bx bx-x me-1"></i> Reject
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="row mb-4">
                            <div class="col-12">
                                <h6>Audit Trail</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th>Created By:</th>
                                        <td>{{ $adjustment->createdBy->name ?? 'N/A' }} on {{ $adjustment->created_at->format('M d, Y H:i') }}</td>
                                    </tr>
                                    @if($adjustment->approved_by)
                                    <tr>
                                        <th>Approved By:</th>
                                        <td>{{ $adjustment->approvedBy->name ?? 'N/A' }} on {{ $adjustment->approved_at ? $adjustment->approved_at->format('M d, Y H:i') : 'N/A' }}</td>
                                    </tr>
                                    @endif
                                    @if($adjustment->posted_by)
                                    <tr>
                                        <th>Posted By:</th>
                                        <td>{{ $adjustment->postedBy->name ?? 'N/A' }} on {{ $adjustment->posted_at ? $adjustment->posted_at->format('M d, Y H:i') : 'N/A' }}</td>
                                    </tr>
                                    @endif
                                    @if($adjustment->journal)
                                    <tr>
                                        <th>Journal Entry:</th>
                                        <td>
                                            <a href="{{ route('accounting.journals.show', $adjustment->journal->id) }}" target="_blank">
                                                {{ $adjustment->journal->reference }}
                                            </a>
                                        </td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                        </div>

        <div class="d-flex justify-content-end">
            <a href="{{ route('inventory.counts.sessions.show', $adjustment->session->encoded_id) }}" class="btn btn-secondary">
                <i class="bx bx-arrow-back me-1"></i> Back to Session
            </a>
        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
@if($currentApprovalLevel && $currentApproval && $currentApproval->status === 'pending')
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Adjustment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('inventory.counts.adjustments.reject', $adjustment->encoded_id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="4" required placeholder="Please provide a reason for rejecting this adjustment..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bx bx-x me-1"></i> Reject Adjustment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

