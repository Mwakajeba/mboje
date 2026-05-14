@extends('layouts.main')

@section('title', 'Stock Adjustments')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Management', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Inventory Count', 'url' => route('inventory.counts.index'), 'icon' => 'bx bx-clipboard-check'],
            ['label' => 'Count Session', 'url' => route('inventory.counts.sessions.show', $session->encoded_id), 'icon' => 'bx bx-show'],
            ['label' => 'Stock Adjustments', 'url' => '#', 'icon' => 'bx bx-clipboard']
        ]" />
        
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h6 class="mb-0 text-uppercase">Stock Adjustments</h6>
                <p class="mb-0 text-muted">Session: {{ $session->session_number }}</p>
            </div>
            <div>
                <a href="{{ route('inventory.counts.sessions.show', $session->encoded_id) }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to Session
                </a>
                <a href="{{ route('inventory.counts.sessions.variances', $session->encoded_id) }}" class="btn btn-primary">
                    <i class="bx bx-bar-chart me-1"></i> View Variances
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card radius-10 border-start border-3 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Total Adjustments</p>
                                <h4 class="my-1 text-primary">{{ number_format($totalAdjustments) }}</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary ms-auto">
                                <i class="bx bx-clipboard"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card radius-10 border-start border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Pending Approval</p>
                                <h4 class="my-1 text-warning">{{ number_format($pendingApproval) }}</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-light-warning text-warning ms-auto">
                                <i class="bx bx-time"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card radius-10 border-start border-3 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Approved</p>
                                <h4 class="my-1 text-info">{{ number_format($approved) }}</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-light-info text-info ms-auto">
                                <i class="bx bx-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card radius-10 border-start border-3 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Posted to GL</p>
                                <h4 class="my-1 text-success">{{ number_format($posted) }}</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-light-success text-success ms-auto">
                                <i class="bx bx-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Value Summary -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card radius-10 border-start border-3 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Total Value</p>
                                <h4 class="my-1 text-primary">TZS {{ number_format($totalValue, 2) }}</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary ms-auto">
                                <i class="bx bx-money"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card radius-10 border-start border-3 border-danger">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Shortages Value</p>
                                <h4 class="my-1 text-danger">TZS {{ number_format($totalShortageValue, 2) }}</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-light-danger text-danger ms-auto">
                                <i class="bx bx-down-arrow-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card radius-10 border-start border-3 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Surpluses Value</p>
                                <h4 class="my-1 text-success">TZS {{ number_format($totalSurplusValue, 2) }}</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-light-success text-success ms-auto">
                                <i class="bx bx-up-arrow-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Adjustments Table -->
        @if($adjustments->count() > 0)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Adjustments List</h5>
                        <div class="d-flex gap-2">
                            @if($canBulkApprove)
                            <button type="button" class="btn btn-sm btn-warning" id="bulkApproveBtn" disabled>
                                <i class="bx bx-check me-1"></i> Bulk Approve (<span id="bulkApproveSelectedCount">0</span>)
                            </button>
                            @endif
                            @if($adjustments->where('status', 'approved')->whereNull('journal_id')->count() > 0)
                            <button type="button" class="btn btn-sm btn-success" id="bulkPostBtn" disabled>
                                <i class="bx bx-upload me-1"></i> Bulk Post to GL (<span id="bulkPostSelectedCount">0</span>)
                            </button>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        @if($canBulkApprove || $adjustments->where('status', 'approved')->whereNull('journal_id')->count() > 0)
                                        <th width="50">
                                            <input type="checkbox" id="selectAllAdjustmentsCheckbox" title="Select All">
                                        </th>
                                        @endif
                                        <th>Adjustment #</th>
                                        <th>Item</th>
                                        <th>Type</th>
                                        <th>Quantity</th>
                                        <th>Value</th>
                                        <th>Status</th>
                                        <th>Approval Status</th>
                                        <th>Current Level</th>
                                        <th>Created By</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($adjustments as $adjustment)
                                    <tr>
                                        @if($canBulkApprove || $adjustments->where('status', 'approved')->whereNull('journal_id')->count() > 0)
                                        <td>
                                            @php
                                                $currentLevel = $adjustment->getCurrentApprovalLevel();
                                                $canApproveThis = false;
                                                if ($adjustment->status === 'pending_approval' && $currentLevel) {
                                                    $canApproveThis = $approvalSettings->canUserApproveAtLevel(auth()->user(), $currentLevel);
                                                }
                                            @endphp
                                            @if(($adjustment->status === 'pending_approval' && $canApproveThis) || ($adjustment->status === 'approved' && !$adjustment->journal_id))
                                            <input type="checkbox" class="adjustment-checkbox" name="adjustment_ids[]" value="{{ $adjustment->id }}" data-adjustment-id="{{ $adjustment->id }}" data-adjustment-status="{{ $adjustment->status }}" data-can-approve="{{ $canApproveThis ? '1' : '0' }}">
                                            @endif
                                        </td>
                                        @endif
                                        <td>
                                            <a href="{{ route('inventory.counts.adjustments.show', $adjustment->encoded_id) }}" class="text-primary fw-bold">
                                                {{ $adjustment->adjustment_number }}
                                            </a>
                                        </td>
                                        <td>
                                            {{ $adjustment->item->name ?? 'N/A' }}
                                            <br><small class="text-muted">{{ $adjustment->item->code ?? '' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $adjustment->adjustment_type === 'surplus' ? 'success' : 'danger' }}">
                                                {{ ucfirst($adjustment->adjustment_type) }}
                                            </span>
                                        </td>
                                        <td>{{ number_format($adjustment->adjustment_quantity, 2) }}</td>
                                        <td><strong>TZS {{ number_format($adjustment->adjustment_value, 2) }}</strong></td>
                                        <td>
                                            <span class="badge bg-{{ $adjustment->status === 'posted' ? 'success' : ($adjustment->status === 'approved' ? 'info' : ($adjustment->status === 'pending_approval' ? 'warning' : 'secondary')) }}">
                                                {{ ucfirst(str_replace('_', ' ', $adjustment->status)) }}
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                // Get configured approval levels from settings
                                                $configuredLevels = (int) ($approvalSettings->approval_levels ?? 1);
                                                
                                                // Only count approvals up to configured levels
                                                $relevantApprovals = $adjustment->approvals->where('approval_level', '<=', $configuredLevels);
                                                $pendingApprovals = $relevantApprovals->where('status', 'pending')->count();
                                                $approvedApprovals = $relevantApprovals->where('status', 'approved')->count();
                                                $totalApprovals = $relevantApprovals->count();
                                            @endphp
                                            @if($adjustment->status === 'pending_approval')
                                                <span class="badge bg-warning">
                                                    {{ $approvedApprovals }}/{{ $totalApprovals }} Approved
                                                </span>
                                                @if($pendingApprovals > 0)
                                                <br><small class="text-danger">
                                                    <i class="bx bx-time me-1"></i>{{ $pendingApprovals }} Pending
                                                </small>
                                                @endif
                                            @elseif($adjustment->status === 'approved')
                                                <span class="badge bg-success">
                                                    <i class="bx bx-check me-1"></i>All Approved
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $currentLevel = $adjustment->getCurrentApprovalLevel();
                                                $canApprove = false;
                                                if ($currentLevel && $adjustment->status === 'pending_approval') {
                                                    $canApprove = $approvalSettings->canUserApproveAtLevel(auth()->user(), $currentLevel);
                                                    $currentApproval = $adjustment->approvals->where('approval_level', $currentLevel)->first();
                                                    $levelName = $currentApproval ? $currentApproval->level_name : ($approvalSettings->getLevelName($currentLevel) ?? "Level {$currentLevel}");
                                                }
                                            @endphp
                                            @if($adjustment->status === 'pending_approval' && $currentLevel)
                                                <div>
                                                    <span class="badge bg-{{ $canApprove ? 'primary' : 'secondary' }}">
                                                        Level {{ $currentLevel }}: {{ $levelName ?? "Level {$currentLevel}" }}
                                                    </span>
                                                    @if($canApprove)
                                                    <br><small class="text-success">
                                                        <i class="bx bx-check-circle me-1"></i>You can approve
                                                    </small>
                                                    @else
                                                    <br><small class="text-muted">
                                                        <i class="bx bx-lock me-1"></i>Awaiting approval
                                                    </small>
                                                    @endif
                                                </div>
                                            @elseif($adjustment->status === 'approved')
                                                <span class="badge bg-success">
                                                    <i class="bx bx-check me-1"></i>All Levels Complete
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $adjustment->createdBy->name ?? 'N/A' }}
                                        </td>
                                        <td>
                                            {{ $adjustment->created_at->format('M d, Y H:i') }}
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('inventory.counts.adjustments.show', $adjustment->encoded_id) }}" class="btn btn-sm btn-info" title="View Details">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                @if($adjustment->status === 'approved' && !$adjustment->journal_id)
                                                <form action="{{ route('inventory.counts.adjustments.post-to-gl', $adjustment->encoded_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to post this adjustment to GL?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" title="Post to GL">
                                                        <i class="bx bx-upload"></i>
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bx bx-clipboard fs-1 text-muted mb-3"></i>
                        <h5 class="text-muted">No Adjustments Found</h5>
                        <p class="text-muted">No adjustments have been created for this count session yet.</p>
                        <a href="{{ route('inventory.counts.sessions.variances', $session->encoded_id) }}" class="btn btn-primary">
                            <i class="bx bx-bar-chart me-1"></i> View Variances & Create Adjustments
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@if($canBulkApprove || $adjustments->where('status', 'approved')->whereNull('journal_id')->count() > 0)
@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Select/Deselect All Adjustments - Simple approach like variances page
    $('#selectAllAdjustmentsCheckbox').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.adjustment-checkbox').prop('checked', isChecked);
        updateBulkCounts();
    });
    
    // Also handle click event for better compatibility
    $('#selectAllAdjustmentsCheckbox').on('click', function() {
        const isChecked = $(this).prop('checked');
        $('.adjustment-checkbox').prop('checked', isChecked);
        updateBulkCounts();
    });
    
    // Individual checkbox change
    $(document).on('change', '.adjustment-checkbox', function() {
        updateBulkCounts();
        // Update select all checkbox state
        const total = $('.adjustment-checkbox').length;
        const checked = $('.adjustment-checkbox:checked').length;
        $('#selectAllAdjustmentsCheckbox').prop('checked', total > 0 && total === checked);
    });
    
    function updateBulkCounts() {
        const selected = $('.adjustment-checkbox:checked');
        const selectedCount = selected.length;
        
        // Count pending approval adjustments
        const pendingApprovalCount = selected.filter(function() {
            return $(this).data('adjustment-status') === 'pending_approval';
        }).length;
        
        // Count approved adjustments (not posted)
        const approvedCount = selected.filter(function() {
            return $(this).data('adjustment-status') === 'approved';
        }).length;
        
        // Update bulk approve button
        if ($('#bulkApproveBtn').length) {
            $('#bulkApproveSelectedCount').text(pendingApprovalCount);
            $('#bulkApproveSelectedCountModal').text(pendingApprovalCount);
            $('#bulkApproveBtn').prop('disabled', pendingApprovalCount === 0);
        }
        
        // Update bulk post button
        if ($('#bulkPostBtn').length) {
            $('#bulkPostSelectedCount').text(approvedCount);
            $('#bulkPostSelectedCountModal').text(approvedCount);
            $('#bulkPostBtn').prop('disabled', approvedCount === 0);
        }
    }
    
    // Bulk Approve Button
    $('#bulkApproveBtn').on('click', function() {
        const selectedIds = $('.adjustment-checkbox:checked').filter(function() {
            return $(this).data('adjustment-status') === 'pending_approval';
        }).map(function() {
            return $(this).val();
        }).get();
        
        if (selectedIds.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Selection',
                text: 'Please select at least one pending adjustment.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        $('#bulkApproveAdjustmentIds').val(JSON.stringify(selectedIds));
        $('#bulkApproveModal').modal('show');
    });
    
    // Bulk Post Button
    $('#bulkPostBtn').on('click', function() {
        const selectedIds = $('.adjustment-checkbox:checked').filter(function() {
            return $(this).data('adjustment-status') === 'approved';
        }).map(function() {
            return $(this).val();
        }).get();
        
        if (selectedIds.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Selection',
                text: 'Please select at least one approved adjustment.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        $('#bulkAdjustmentIds').val(JSON.stringify(selectedIds));
        $('#bulkPostModal').modal('show');
    });
    
    // Initialize count on page load
    updateBulkCounts();
});
</script>
@endpush
@endif

@if($canBulkApprove)
<!-- Bulk Approve Modal -->
<div class="modal fade" id="bulkApproveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Approve Adjustments</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('inventory.counts.adjustments.bulk-approve', $session->encoded_id) }}" method="POST" id="bulkApproveForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>
                        <span id="bulkApproveSelectedCountModal">0</span> pending adjustment(s) selected for approval.
                    </div>
                    <p class="mb-2">This will approve the current pending level for each selected adjustment.</p>
                    <div class="mb-3">
                        <label class="form-label">Approval Comments (Optional)</label>
                        <textarea name="comments" class="form-control" rows="3" placeholder="Add any comments about this approval..."></textarea>
                    </div>
                    <input type="hidden" name="adjustment_ids" id="bulkApproveAdjustmentIds">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bx bx-check me-1"></i> Approve Adjustments
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Update form to send array
    $('#bulkApproveForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = this;
        const adjustmentIdsJson = $('#bulkApproveAdjustmentIds').val();
        
        if (!adjustmentIdsJson) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No adjustments selected.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        let adjustmentIds;
        try {
            adjustmentIds = JSON.parse(adjustmentIdsJson);
        } catch (e) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Invalid adjustment IDs format.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        if (!Array.isArray(adjustmentIds) || adjustmentIds.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No adjustments selected.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        Swal.fire({
            title: 'Approve Adjustments?',
            html: `<p>Are you sure you want to approve <strong>${adjustmentIds.length}</strong> adjustment(s)?</p><p class="text-muted small">This will approve the current pending level for each adjustment.</p>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Approve',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Remove the old hidden input with JSON string
                $('#bulkApproveAdjustmentIds').remove();
                
                // Add individual hidden inputs for each ID
                adjustmentIds.forEach(function(id) {
                    $(form).append('<input type="hidden" name="adjustment_ids[]" value="' + id + '">');
                });
                
                // Submit form
                form.submit();
            }
        });
    });
});
</script>
@endpush
@endif

@if($adjustments->where('status', 'approved')->whereNull('journal_id')->count() > 0)
<!-- Bulk Post to GL Modal -->
<div class="modal fade" id="bulkPostModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Post Adjustments to GL</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('inventory.counts.adjustments.bulk-post', $session->encoded_id) }}" method="POST" id="bulkPostForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bx bx-info-circle me-2"></i>
                        <span id="bulkPostSelectedCountModal">0</span> approved adjustment(s) selected for posting to GL.
                    </div>
                    <p class="mb-0">This will:</p>
                    <ul class="mb-0">
                        <li>Create consolidated journal entries in GL</li>
                        <li>Update inventory quantities and cost layers</li>
                        <li>Create movement records</li>
                        <li>Mark adjustments as posted</li>
                    </ul>
                    <input type="hidden" name="adjustment_ids" id="bulkAdjustmentIds">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bx bx-upload me-1"></i> Post to GL
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Update form to send array
    $('#bulkPostForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = this;
        const adjustmentIdsJson = $('#bulkAdjustmentIds').val();
        
        if (!adjustmentIdsJson) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No adjustments selected.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        let adjustmentIds;
        try {
            adjustmentIds = JSON.parse(adjustmentIdsJson);
        } catch (e) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Invalid adjustment IDs format.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        if (!Array.isArray(adjustmentIds) || adjustmentIds.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No adjustments selected.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        Swal.fire({
            title: 'Post Adjustments to GL?',
            html: `<p>Are you sure you want to post <strong>${adjustmentIds.length}</strong> adjustment(s) to GL?</p><p class="text-muted small">This will create journal entries and update inventory quantities.</p>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Post to GL',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Remove the old hidden input with JSON string
                $('#bulkAdjustmentIds').remove();
                
                // Add individual hidden inputs for each ID
                adjustmentIds.forEach(function(id) {
                    $(form).append('<input type="hidden" name="adjustment_ids[]" value="' + id + '">');
                });
                
                // Submit form
                form.submit();
            }
        });
    });
});
</script>
@endpush
@endif
@endsection

