@extends('layouts.main')
@section('title', 'Journal Entry Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Journal Entries', 'url' => route('accounting.journals.index'), 'icon' => 'bx bx-book-open'],
            ['label' => 'Journal Entry #' . $journal->reference, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <h6 class="mb-0 text-uppercase">JOURNAL ENTRY DETAILS</h6>
        <hr />

        <!-- Page Header -->
        <div class="row">
            <div class="col-12">
                <div class="card border-top border-0 border-4 border-info">
                    <div class="card-body p-5">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="card-title d-flex align-items-center">
                                    <div><i class="bx bx-book-open me-1 font-22 text-info"></i></div>
                                    <h5 class="mb-0 text-info">Journal Entry Details</h5>
                                </div>
                                <p class="mb-0 text-muted">View complete details of this journal entry</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="d-flex gap-2 justify-content-end flex-wrap">
                                    @if($journal->approved)
                                        <span class="badge bg-success align-self-center me-2">
                                            <i class="bx bx-check-circle me-1"></i>Approved
                                        </span>
                                        @if($journal->gl_posted ?? false)
                                            <span class="badge bg-success align-self-center">
                                                GL Posted
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark align-self-center" title="Journal is approved but not yet posted to GL. This may be due to a locked period or configuration issue.">
                                                Not Posted to GL
                                            </span>
                                        @endif
                                    @elseif($journal->isRejected())
                                        <span class="badge bg-danger align-self-center me-2">
                                            <i class="bx bx-x-circle me-1"></i>Rejected
                                        </span>
                                    @elseif($currentApproval && $canApprove)
                                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                                            <i class="bx bx-check me-1"></i>Approve
                                        </button>
                                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                            <i class="bx bx-x me-1"></i>Reject
                                        </button>
                                    @elseif($currentApproval)
                                        <span class="badge bg-warning align-self-center me-2">
                                            <i class="bx bx-time me-1"></i>Pending Approval (Level {{ $currentApproval->approval_level }})
                                        </span>
                                    @elseif(!$journal->approved && !$journal->isRejected())
                                        {{-- Debug info: Show why approval button is not showing --}}
                                        @if(config('app.debug'))
                                            <div class="alert alert-info alert-sm mb-2">
                                                <small>
                                                    <strong>Debug Info:</strong><br>
                                                    Settings: {{ $settings ? 'Yes' : 'No' }}<br>
                                                    Current Approval: {{ $currentApproval ? 'Yes (Level ' . $currentApproval->approval_level . ')' : 'No' }}<br>
                                                    Can Approve: {{ $canApprove ? 'Yes' : 'No' }}<br>
                                                    Approvals Count: {{ $journal->approvals->count() }}<br>
                                                    Pending Approvals: {{ $journal->approvals->where('status', 'pending')->count() }}
                                                </small>
                                            </div>
                                        @endif
                                        <span class="badge bg-secondary align-self-center me-2">
                                            <i class="bx bx-file me-1"></i>Draft
                                        </span>
                                    @endif
                                    <a href="{{ route('accounting.journals.export-pdf', $journal) }}" class="btn btn-danger">
                                        <i class="bx bx-file-pdf me-1"></i> Export PDF
                                    </a>
                                    @if(!$journal->approved && !$journal->isRejected())
                                        <a href="{{ route('accounting.journals.edit', $journal) }}" class="btn btn-warning">
                                            <i class="bx bx-edit me-1"></i> Edit
                                        </a>
                                    @endif
                                    <a href="{{ route('accounting.journals.index') }}" class="btn btn-outline-secondary">
                                        <i class="bx bx-arrow-back me-1"></i> Back
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Journal Information -->
            <div class="col-12 col-lg-8">
                <div class="card radius-10 border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Journal Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Entry Date</label>
                                <p class="form-control-plaintext">
                                    <span class="badge bg-light text-dark">
                                        {{ $journal->date ? $journal->date->format('F d, Y') : 'N/A' }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Reference</label>
                                <p class="form-control-plaintext">
                                    <strong>{{ $journal->reference ?? 'N/A' }}</strong>
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Status</label>
                                <p class="form-control-plaintext">
                                    @if($journal->approved)
                                        <span class="badge bg-success">
                                            <i class="bx bx-check-circle me-1"></i>Approved
                                        </span>
                                        @if($journal->approved_at)
                                            <br><small class="text-muted">Approved on {{ $journal->approved_at->format('M d, Y \a\t g:i A') }}</small>
                                        @endif
                                    @elseif($journal->isRejected())
                                        <span class="badge bg-danger">
                                            <i class="bx bx-x-circle me-1"></i>Rejected
                                        </span>
                                    @elseif($currentApproval)
                                        <span class="badge bg-warning">
                                            <i class="bx bx-time me-1"></i>Pending Approval (Level {{ $currentApproval->approval_level }})
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="bx bx-file me-1"></i>Draft
                                        </span>
                                    @endif
                                </p>
                            </div>
                            @if($journal->approved && $journal->approvedBy)
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Approved By</label>
                                <p class="form-control-plaintext">
                                    {{ $journal->approvedBy->name }}
                                </p>
                            </div>
                            @endif
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Branch</label>
                                <p class="form-control-plaintext">
                                    <span class="badge bg-info">
                                        {{ $journal->branch->name ?? 'N/A' }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Created By</label>
                                <p class="form-control-plaintext">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm me-2">
                                            <img src="{{ asset('assets/images/avatars/avatar-1.png') }}" alt="User" class="rounded-circle" width="24">
                                        </div>
                                        <span>{{ $journal->user->name ?? 'N/A' }}</span>
                                    </div>
                                </p>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold">Description</label>
                                <p class="form-control-plaintext">
                                    {{ $journal->description ?: 'No description provided' }}
                                </p>
                            </div>
                            @if($journal->attachment)
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-bold">Attachment</label>
                                    <p class="form-control-plaintext">
                                        <a href="{{ asset('storage/' . $journal->attachment) }}"
                                           target="_blank"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bx bx-download me-1"></i>View Attachment
                                        </a>
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Journal Items -->
                <div class="card radius-10 border-0 shadow-sm mt-4">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Journal Entries</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Account</th>
                                        <th>Nature</th>
                                        <th class="text-end">Amount</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($journal->items as $index => $item)
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
                                                {{ $item->description ?: 'No description' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <div class="d-flex flex-column align-items-center">
                                                    <i class="bx bx-list-ul font-48 text-muted mb-3"></i>
                                                    <h6 class="text-muted">No Journal Items Found</h6>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Sidebar -->
            <div class="col-12 col-lg-4">
                <!-- Totals Summary -->
                <div class="card radius-10 border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-calculator me-2"></i>Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="p-3 bg-light rounded">
                                    <h6 class="text-muted mb-1">Total Debit</h6>
                                    <h4 class="mb-0 text-success">TZS {{ number_format($journal->debit_total, 2) }}</h4>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="p-3 bg-light rounded">
                                    <h6 class="text-muted mb-1">Total Credit</h6>
                                    <h4 class="mb-0 text-danger">TZS {{ number_format($journal->credit_total, 2) }}</h4>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <div class="p-3 bg-light rounded">
                                <h6 class="text-muted mb-1">Balance</h6>
                                @if($journal->balance == 0)
                                    <h4 class="mb-0 text-success">
                                        <i class="bx bx-check-circle me-1"></i>Balanced
                                    </h4>
                                @else
                                    <h4 class="mb-0 text-warning">
                                        <i class="bx bx-error-circle me-1"></i>TZS {{ number_format(abs($journal->balance), 2) }}
                                    </h4>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card radius-10 border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-cog me-2"></i>Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('accounting.journals.edit', $journal) }}" class="btn btn-warning">
                                <i class="bx bx-edit me-1"></i> Edit Entry
                            </a>
                            <button type="button" class="btn btn-outline-danger" onclick="confirmDelete()">
                                <i class="bx bx-trash me-1"></i> Delete Entry
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Approval History -->
                @if($journal->approvals && $journal->approvals->count() > 0)
                <div class="card radius-10 border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-check-shield me-2"></i>Approval History</h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            @foreach($journal->approvals->sortBy('approval_level') as $approval)
                                <div class="timeline-item mb-3">
                                    <div class="d-flex">
                                        <div class="timeline-marker me-3">
                                            @if($approval->status === 'approved')
                                                <div class="avatar avatar-sm bg-success rounded-circle d-flex align-items-center justify-content-center">
                                                    <i class="bx bx-check text-white"></i>
                                                </div>
                                            @elseif($approval->status === 'rejected')
                                                <div class="avatar avatar-sm bg-danger rounded-circle d-flex align-items-center justify-content-center">
                                                    <i class="bx bx-x text-white"></i>
                                                </div>
                                            @else
                                                <div class="avatar avatar-sm bg-warning rounded-circle d-flex align-items-center justify-content-center">
                                                    <i class="bx bx-time text-white"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1">
                                                        Level {{ $approval->approval_level }} - {{ $approval->approver_name }}
                                                        @if($approval->approver_type === 'role')
                                                            <span class="badge bg-info ms-2">Role</span>
                                                        @else
                                                            <span class="badge bg-primary ms-2">User</span>
                                                        @endif
                                                    </h6>
                                                    <p class="text-muted mb-1 small">
                                                        @if($approval->status === 'approved' && $approval->approved_at)
                                                            Approved on {{ $approval->approved_at->format('M d, Y \a\t g:i A') }}
                                                        @elseif($approval->status === 'rejected' && $approval->rejected_at)
                                                            Rejected on {{ $approval->rejected_at->format('M d, Y \a\t g:i A') }}
                                                        @else
                                                            Pending approval
                                                        @endif
                                                    </p>
                                                    @if($approval->notes)
                                                        <p class="mb-0 small">
                                                            <strong>Notes:</strong> {{ $approval->notes }}
                                                        </p>
                                                    @endif
                                                </div>
                                                <div>
                                                    @if($approval->status === 'approved')
                                                        <span class="badge bg-success">Approved</span>
                                                    @elseif($approval->status === 'rejected')
                                                        <span class="badge bg-danger">Rejected</span>
                                                    @else
                                                        <span class="badge bg-warning">Pending</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Entry Details -->
                <div class="card radius-10 border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-detail me-2"></i>Entry Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold">Created</label>
                                <p class="form-control-plaintext">
                                    {{ $journal->created_at ? $journal->created_at->format('M d, Y \a\t g:i A') : 'N/A' }}
                                </p>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold">Last Updated</label>
                                <p class="form-control-plaintext">
                                    {{ $journal->updated_at ? $journal->updated_at->format('M d, Y \a\t g:i A') : 'N/A' }}
                                </p>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold">Total Items</label>
                                <p class="form-control-plaintext">
                                    <span class="badge bg-primary">{{ $journal->items->count() }} entries</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
@if($currentApproval && $canApprove)
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('accounting.journals.approve.store', $journal) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Approve Journal Entry</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>
                        You are approving this journal entry at <strong>Level {{ $currentApproval->approval_level }}</strong>.
                    </div>
                    <div class="mb-3">
                        <label for="approval_notes" class="form-label">Approval Notes (Optional)</label>
                        <textarea name="notes" id="approval_notes" class="form-control" rows="3" placeholder="Add any notes about this approval..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bx bx-check me-1"></i>Approve
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('accounting.journals.reject', $journal) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Reject Journal Entry</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bx bx-error-circle me-2"></i>
                        <strong>Warning:</strong> This action will reject the journal entry. Please provide a reason for rejection.
                    </div>
                    <div class="mb-3">
                        <label for="rejection_notes" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="notes" id="rejection_notes" class="form-control" rows="4" required placeholder="Please provide a detailed reason for rejecting this journal entry..."></textarea>
                        <div class="form-text">This reason will be recorded and visible to the preparer.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bx bx-x me-1"></i>Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this journal entry? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('accounting.journals.destroy', $journal) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    function confirmDelete() {
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
</script>
@endpush
