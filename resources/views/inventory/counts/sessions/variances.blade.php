@extends('layouts.main')

@section('title', 'Variance Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Management', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Inventory Count', 'url' => route('inventory.counts.index'), 'icon' => 'bx bx-clipboard-check'],
            ['label' => 'Variance Report', 'url' => '#', 'icon' => 'bx bx-bar-chart']
        ]" />
        
        <h6 class="mb-0 text-uppercase">VARIANCE REPORT</h6>
        <hr />

        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Session: {{ $session->session_number }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Total Variances:</strong> {{ $variances->count() }}
                            </div>
                            <div class="col-md-3">
                                <strong>Positive (Surplus):</strong> 
                                <span class="badge bg-success">{{ $variances->where('variance_type', 'positive')->count() }}</span>
                            </div>
                            <div class="col-md-3">
                                <strong>Negative (Shortage):</strong> 
                                <span class="badge bg-danger">{{ $variances->where('variance_type', 'negative')->count() }}</span>
                            </div>
                            <div class="col-md-3">
                                <strong>High Value:</strong> 
                                <span class="badge bg-warning">{{ $variances->where('is_high_value', true)->count() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Variance Details</h5>
                        @if($session->isApproved())
                        <div>
                            <button type="button" class="btn btn-sm btn-primary" id="selectAllBtn">
                                <i class="bx bx-check-square me-1"></i> Select All
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" id="deselectAllBtn">
                                <i class="bx bx-square me-1"></i> Deselect All
                            </button>
                            <button type="button" class="btn btn-sm btn-success" id="bulkCreateBtn" disabled>
                                <i class="bx bx-plus me-1"></i> Bulk Create Adjustments (<span id="selectedCount">0</span>)
                            </button>
                        </div>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        @if($session->isApproved())
                                        <th width="50">
                                            <input type="checkbox" id="selectAllCheckbox" title="Select All">
                                        </th>
                                        @endif
                                        <th>Item Code</th>
                                        <th>Item Name</th>
                                        <th>System Qty</th>
                                        <th>Physical Qty</th>
                                        <th>Variance Qty</th>
                                        <th>Variance %</th>
                                        <th>Variance Value</th>
                                        <th>Type</th>
                                        <th>High Value</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($variances as $variance)
                                    <tr>
                                        @if($session->isApproved())
                                        <td>
                                            @if(!$variance->adjustment)
                                            <input type="checkbox" class="variance-checkbox" name="variance_ids[]" value="{{ $variance->id }}" data-variance-id="{{ $variance->id }}">
                                            @endif
                                        </td>
                                        @endif
                                        <td>{{ $variance->item->code ?? 'N/A' }}</td>
                                        <td>{{ $variance->item->name ?? 'N/A' }}</td>
                                        <td>{{ number_format($variance->system_quantity, 2) }}</td>
                                        <td>{{ number_format($variance->physical_quantity, 2) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $variance->variance_type === 'positive' ? 'success' : ($variance->variance_type === 'negative' ? 'danger' : 'secondary') }}">
                                                {{ number_format($variance->variance_quantity, 2) }}
                                            </span>
                                        </td>
                                        <td>{{ number_format($variance->variance_percentage, 2) }}%</td>
                                        <td>TZS {{ number_format($variance->variance_value, 2) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $variance->variance_type === 'positive' ? 'success' : ($variance->variance_type === 'negative' ? 'danger' : 'secondary') }}">
                                                {{ ucfirst($variance->variance_type) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($variance->is_high_value)
                                                <span class="badge bg-danger">Yes</span>
                                            @else
                                                <span class="badge bg-success">No</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $variance->status === 'resolved' ? 'success' : ($variance->status === 'investigating' ? 'warning' : 'secondary') }}">
                                                {{ ucfirst($variance->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if(!$variance->adjustment)
                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createAdjustmentModal" data-variance-id="{{ $variance->id }}">
                                                    <i class="bx bx-plus"></i> Create Adjustment
                                                </button>
                                            @else
                                                <a href="{{ route('inventory.counts.adjustments.show', $variance->adjustment->encoded_id) }}" class="btn btn-sm btn-info">
                                                    <i class="bx bx-show"></i> View Adjustment
                                                </a>
                                            @endif
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
    </div>
</div>

@if($session->isApproved())
<!-- Bulk Create Adjustment Modal -->
<div class="modal fade" id="bulkCreateAdjustmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Create Adjustments</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('inventory.counts.adjustments.bulk-create', $session->encoded_id) }}" method="POST" id="bulkCreateForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>
                        <span id="bulkSelectedCount">0</span> variance(s) selected for adjustment creation.
                    </div>
                    <input type="hidden" name="variance_ids" id="bulkVarianceIds">
                    
                    <div class="mb-3">
                        <label class="form-label">Reason Code <span class="text-danger">*</span></label>
                        <select name="reason_code" class="form-select" required>
                            <option value="">Select Reason Code</option>
                            <option value="wrong_posting">Wrong Posting</option>
                            <option value="theft">Theft</option>
                            <option value="damage">Damage</option>
                            <option value="expired">Expired</option>
                            <option value="unrecorded_issue">Unrecorded Issue</option>
                            <option value="unrecorded_receipt">Unrecorded Receipt</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason Description <span class="text-danger">*</span></label>
                        <textarea name="reason_description" class="form-control" rows="3" required placeholder="Describe the reason for these adjustments..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Supervisor Comments</label>
                        <textarea name="supervisor_comments" class="form-control" rows="2" placeholder="Optional supervisor comments..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Finance Comments</label>
                        <textarea name="finance_comments" class="form-control" rows="2" placeholder="Optional finance comments..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i> Create Adjustments
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Select/Deselect All
    $('#selectAllCheckbox, #selectAllBtn').on('click', function() {
        $('.variance-checkbox:not(:disabled)').prop('checked', true);
        updateSelectedCount();
    });
    
    $('#deselectAllBtn').on('click', function() {
        $('.variance-checkbox').prop('checked', false);
        updateSelectedCount();
    });
    
    // Individual checkbox change
    $(document).on('change', '.variance-checkbox', function() {
        updateSelectedCount();
    });
    
    function updateSelectedCount() {
        const selected = $('.variance-checkbox:checked').length;
        $('#selectedCount').text(selected);
        $('#bulkSelectedCount').text(selected);
        $('#bulkCreateBtn').prop('disabled', selected === 0);
    }
    
    // Bulk Create Button
    $('#bulkCreateBtn').on('click', function() {
        const selectedIds = $('.variance-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (selectedIds.length === 0) {
            alert('Please select at least one variance.');
            return;
        }
        
        $('#bulkVarianceIds').val(JSON.stringify(selectedIds));
        $('#bulkCreateAdjustmentModal').modal('show');
    });
    
    // Update form to send array
    $('#bulkCreateForm').on('submit', function(e) {
        const varianceIds = JSON.parse($('#bulkVarianceIds').val());
        $(this).find('input[name="variance_ids[]"]').remove();
        varianceIds.forEach(function(id) {
            $(this).append('<input type="hidden" name="variance_ids[]" value="' + id + '">');
        }.bind(this));
    });
});
</script>
@endpush
@endif
@endsection

