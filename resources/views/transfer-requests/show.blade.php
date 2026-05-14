@extends('layouts.main')

@section('title', 'Transfer Request Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Management', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Transfer Requests', 'url' => route('inventory.transfer-requests.index'), 'icon' => 'bx bx-message-square-dots'],
            ['label' => $transferRequest->reference, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <h6 class="mb-0 text-uppercase">TRANSFER REQUEST DETAILS</h6>
        <hr />

        <div class="row">
            <div class="col-12">

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Transfer Request: {{ $transferRequest->reference }}</h5>
                        <div>
                            {!! $transferRequest->status_badge !!}
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Request Information</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Reference:</strong></td>
                                    <td>{{ $transferRequest->reference }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Item:</strong></td>
                                    <td>{{ $transferRequest->item->name }} ({{ $transferRequest->item->item_code }})</td>
                                </tr>
                                <tr>
                                    <td><strong>Quantity:</strong></td>
                                    <td>{{ number_format($transferRequest->quantity, 2) }} {{ $transferRequest->item->unit_of_measure ?? 'units' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Unit Cost:</strong></td>
                                    <td>{{ number_format($transferRequest->unit_cost, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Total Cost:</strong></td>
                                    <td>{{ number_format($transferRequest->total_cost, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Reason:</strong></td>
                                    <td>{{ $transferRequest->reason }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Location Information</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>From Location:</strong></td>
                                    <td>{{ $transferRequest->fromLocation->name }} ({{ $transferRequest->fromLocation->branch->name }})</td>
                                </tr>
                                <tr>
                                    <td><strong>To Location:</strong></td>
                                    <td>{{ $transferRequest->toLocation->name }} ({{ $transferRequest->toLocation->branch->name }})</td>
                                </tr>
                                <tr>
                                    <td><strong>Branch:</strong></td>
                                    <td>{{ $transferRequest->branch->name }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($transferRequest->notes)
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-muted mb-3">Notes</h6>
                            <div class="alert alert-light">
                                {{ $transferRequest->notes }}
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Request Details</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Requested By:</strong></td>
                                    <td>{{ $transferRequest->requestedBy->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Requested At:</strong></td>
                                    <td>{{ $transferRequest->created_at->format('M d, Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>

                        @if($transferRequest->status !== 'pending')
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Approval Details</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>{{ $transferRequest->status === 'approved' ? 'Approved' : 'Rejected' }} By:</strong></td>
                                    <td>{{ $transferRequest->approvedBy->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ $transferRequest->status === 'approved' ? 'Approved' : 'Rejected' }} At:</strong></td>
                                    <td>{{ $transferRequest->approved_at->format('M d, Y H:i') }}</td>
                                </tr>
                                @if($transferRequest->approval_notes)
                                <tr>
                                    <td><strong>Notes:</strong></td>
                                    <td>{{ $transferRequest->approval_notes }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                        @endif
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('inventory.transfer-requests.index') }}" class="btn btn-secondary">Back to List</a>
                        
                        @if($transferRequest->status === 'pending')
                            @if($transferRequest->requested_by === Auth::id())
                                <a href="{{ route('inventory.transfer-requests.edit', $transferRequest->id) }}" class="btn btn-primary">Edit Request</a>
                            @endif
                            
                            @can('approve transfer requests')
                                <button type="button" class="btn btn-success approve-btn" data-id="{{ $transferRequest->id }}">
                                    <i class="bx bx-check"></i> Approve
                                </button>
                                <button type="button" class="btn btn-danger reject-btn" data-id="{{ $transferRequest->id }}">
                                    <i class="bx bx-x"></i> Reject
                                </button>
                            @endcan
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approvalModalLabel">Approve Transfer Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="approvalForm">
                <div class="modal-body">
                    <input type="hidden" id="approvalRequestId" name="request_id">
                    <div class="mb-3">
                        <label for="approvalNotes" class="form-label">Approval Notes (Optional)</label>
                        <textarea class="form-control" id="approvalNotes" name="approval_notes" rows="3" placeholder="Add any notes about this approval..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectionModal" tabindex="-1" aria-labelledby="rejectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectionModalLabel">Reject Transfer Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rejectionForm">
                <div class="modal-body">
                    <input type="hidden" id="rejectionRequestId" name="request_id">
                    <div class="mb-3">
                        <label for="rejectionNotes" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejectionNotes" name="approval_notes" rows="3" placeholder="Please provide a reason for rejection..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Handle approve button click
    $('.approve-btn').on('click', function() {
        var requestId = $(this).data('id');
        $('#approvalRequestId').val(requestId);
        $('#approvalModal').modal('show');
    });

    // Handle reject button click
    $('.reject-btn').on('click', function() {
        var requestId = $(this).data('id');
        $('#rejectionRequestId').val(requestId);
        $('#rejectionModal').modal('show');
    });

    // Handle approval form submission
    $('#approvalForm').on('submit', function(e) {
        e.preventDefault();
        
        var requestId = $('#approvalRequestId').val();
        var formData = $(this).serialize();
        
        $.ajax({
            url: '/inventory/transfer-requests/' + requestId + '/approve',
            type: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#approvalModal').modal('hide');
                location.reload();
            },
            error: function(xhr) {
                var error = xhr.responseJSON?.error || 'An error occurred';
                toastr.error(error);
            }
        });
    });

    // Handle rejection form submission
    $('#rejectionForm').on('submit', function(e) {
        e.preventDefault();
        
        var requestId = $('#rejectionRequestId').val();
        var formData = $(this).serialize();
        
        $.ajax({
            url: '/inventory/transfer-requests/' + requestId + '/reject',
            type: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#rejectionModal').modal('hide');
                location.reload();
            },
            error: function(xhr) {
                var error = xhr.responseJSON?.error || 'An error occurred';
                toastr.error(error);
            }
        });
    });
});
</script>
@endpush
