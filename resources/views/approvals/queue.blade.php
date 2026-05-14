@extends('layouts.main')

@section('title', 'Approval Queue')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Approval Queue', 'url' => '#', 'icon' => 'bx bx-check-shield']
        ]" />
        <h6 class="mb-0 text-uppercase">APPROVAL QUEUE</h6>
        <hr />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bx bx-check-shield me-2"></i>Pending Approvals
                            </h5>
                            <span class="badge bg-light text-dark">{{ $allApprovals->count() }} items</span>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($allApprovals->isEmpty())
                            <div class="text-center py-5">
                                <i class="bx bx-check-circle font-48 text-success mb-3"></i>
                                <h5 class="text-muted">No Pending Approvals</h5>
                                <p class="text-muted">You're all caught up! There are no items waiting for your approval.</p>
                            </div>
                        @else
                            <form id="bulkApprovalForm" method="POST" action="{{ route('approvals.bulk-approve') }}">
                                @csrf
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="50">
                                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                                </th>
                                                <th>Type</th>
                                                <th>Reference</th>
                                                <th>Description</th>
                                                <th>Amount</th>
                                                <th>Date</th>
                                                <th>Created By</th>
                                                <th>Branch</th>
                                                <th>Level</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($allApprovals as $approval)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" 
                                                               name="approvals[]" 
                                                               value="{{ $approval['type'] }}_{{ $approval['id'] }}" 
                                                               class="form-check-input approval-checkbox">
                                                    </td>
                                                    <td>
                                                        @if($approval['type'] === 'journal')
                                                            <span class="badge bg-info">
                                                                <i class="bx bx-book me-1"></i>Journal Entry
                                                            </span>
                                                        @else
                                                            <span class="badge bg-warning">
                                                                <i class="bx bx-money me-1"></i>Payment Voucher
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <strong>{{ $approval['reference'] }}</strong>
                                                    </td>
                                                    <td>
                                                        {{ Str::limit($approval['description'] ?? 'N/A', 50) }}
                                                    </td>
                                                    <td class="text-end">
                                                        <strong>TZS {{ number_format($approval['amount'], 2) }}</strong>
                                                    </td>
                                                    <td>
                                                        {{ $approval['date'] ? \Carbon\Carbon::parse($approval['date'])->format('M d, Y') : 'N/A' }}
                                                    </td>
                                                    <td>{{ $approval['created_by'] }}</td>
                                                    <td>{{ $approval['branch'] }}</td>
                                                    <td>
                                                        <span class="badge bg-secondary">Level {{ $approval['approval_level'] }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                                                            @if($approval['type'] === 'journal')
                                                                <a href="{{ route('accounting.journals.show', $approval['journal_id']) }}" 
                                                                   class="btn btn-outline-primary" 
                                                                   title="View">
                                                                    <i class="bx bx-show"></i>
                                                                </a>
                                                                <a href="{{ route('accounting.journals.show', $approval['journal_id']) }}" 
                                                                   class="btn btn-outline-success" 
                                                                   title="Approve">
                                                                    <i class="bx bx-check"></i>
                                                                </a>
                                                            @else
                                                                <a href="{{ route('accounting.payment-vouchers.show', \App\Helpers\HashIdHelper::encode($approval['payment_id'])) }}" 
                                                                   class="btn btn-outline-primary" 
                                                                   title="View">
                                                                    <i class="bx bx-show"></i>
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="mt-3 d-flex gap-2">
                                    <button type="button" class="btn btn-success" id="bulkApproveBtn" disabled>
                                        <i class="bx bx-check me-1"></i>Approve Selected
                                    </button>
                                    <button type="button" class="btn btn-danger" id="bulkRejectBtn" disabled>
                                        <i class="bx bx-x me-1"></i>Reject Selected
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Reject Modal -->
<div class="modal fade" id="bulkRejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="bulkRejectForm" method="POST" action="{{ route('approvals.bulk-reject') }}">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Reject Selected Items</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bx bx-error-circle me-2"></i>
                        <strong>Warning:</strong> You are about to reject the selected items. Please provide a reason.
                    </div>
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="notes" id="rejection_reason" class="form-control" rows="4" required placeholder="Please provide a detailed reason for rejection..."></textarea>
                        <div id="selectedItemsList" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Selected</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.approval-checkbox');
    const bulkApproveBtn = document.getElementById('bulkApproveBtn');
    const bulkRejectBtn = document.getElementById('bulkRejectBtn');
    const bulkApprovalForm = document.getElementById('bulkApprovalForm');
    const bulkRejectForm = document.getElementById('bulkRejectForm');

    // Select all functionality
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkButtons();
        });
    }

    // Individual checkbox change
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkButtons();
            if (selectAll) {
                selectAll.checked = Array.from(checkboxes).every(cb => cb.checked);
            }
        });
    });

    function updateBulkButtons() {
        const checked = Array.from(checkboxes).filter(cb => cb.checked);
        if (bulkApproveBtn) bulkApproveBtn.disabled = checked.length === 0;
        if (bulkRejectBtn) bulkRejectBtn.disabled = checked.length === 0;
    }

    // Bulk approve
    if (bulkApproveBtn) {
        bulkApproveBtn.addEventListener('click', function() {
            const checked = Array.from(checkboxes).filter(cb => cb.checked);
            if (checked.length === 0) {
                alert('Please select at least one item to approve.');
                return;
            }
            
            if (confirm(`Are you sure you want to approve ${checked.length} item(s)?`)) {
                bulkApprovalForm.submit();
            }
        });
    }

    // Bulk reject
    if (bulkRejectBtn) {
        bulkRejectBtn.addEventListener('click', function() {
            const checked = Array.from(checkboxes).filter(cb => cb.checked);
            if (checked.length === 0) {
                alert('Please select at least one item to reject.');
                return;
            }
            
            // Show selected items in modal
            const selectedItemsList = document.getElementById('selectedItemsList');
            if (selectedItemsList) {
                const items = checked.map(cb => {
                    const row = cb.closest('tr');
                    const reference = row.querySelector('td:nth-child(3) strong').textContent;
                    const type = row.querySelector('td:nth-child(2) .badge').textContent.trim();
                    return `${type}: ${reference}`;
                });
                selectedItemsList.innerHTML = '<strong>Selected Items:</strong><ul class="mb-0 mt-2">' + 
                    items.map(item => `<li>${item}</li>`).join('') + '</ul>';
            }
            
            // Clear existing hidden inputs and add selected ones
            const rejectForm = document.getElementById('bulkRejectForm');
            rejectForm.querySelectorAll('input[name="approvals[]"]').forEach(input => {
                if (input.type === 'hidden') input.remove();
            });
            
            const selectedInputs = Array.from(bulkApprovalForm.querySelectorAll('input[name="approvals[]"]:checked'));
            selectedInputs.forEach(input => {
                const newInput = document.createElement('input');
                newInput.type = 'hidden';
                newInput.name = 'approvals[]';
                newInput.value = input.value;
                rejectForm.appendChild(newInput);
            });
            
            new bootstrap.Modal(document.getElementById('bulkRejectModal')).show();
        });
    }
    
    // Clear reject form when modal is hidden
    document.getElementById('bulkRejectModal')?.addEventListener('hidden.bs.modal', function() {
        const rejectForm = document.getElementById('bulkRejectForm');
        if (rejectForm) {
            rejectForm.querySelectorAll('input[name="approvals[]"]').forEach(input => {
                if (input.type === 'hidden') input.remove();
            });
            rejectForm.querySelector('textarea[name="notes"]').value = '';
        }
    });
});
</script>
@endpush

