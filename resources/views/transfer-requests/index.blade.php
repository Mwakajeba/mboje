@extends('layouts.main')

@section('title', 'Transfer Requests')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Management', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Transfer Requests', 'url' => '#', 'icon' => 'bx bx-message-square-dots']
        ]" />
        <h6 class="mb-0 text-uppercase">TRANSFER REQUESTS</h6>
        <hr />

        <div class="row">
            <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Transfer Requests</h5>
                        <div>
                            @can('create transfer requests')
                            <a href="{{ route('inventory.transfer-requests.create') }}" class="btn btn-primary">
                                <i class="bx bx-plus"></i> New Transfer Request
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="transferRequestsTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Item</th>
                                    <th>From Location</th>
                                    <th>To Location</th>
                                    <th>Quantity</th>
                                    <th>Total Cost</th>
                                    <th>Status</th>
                                    <th>Requested By</th>
                                    <th>Requested At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
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
    var table = $('#transferRequestsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('inventory.transfer-requests.index') }}",
        columns: [
            { data: 'reference', name: 'reference' },
            { data: 'item.name', name: 'item.name' },
            { data: 'from_location.name', name: 'fromLocation.name' },
            { data: 'to_location.name', name: 'toLocation.name' },
            { data: 'quantity', name: 'quantity' },
            { data: 'total_cost', name: 'total_cost' },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'requested_by.name', name: 'requestedBy.name' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[8, 'desc']], // Order by created_at desc
        pageLength: 25,
        responsive: true
    });

    // Handle approve button click
    $(document).on('click', '.approve-btn', function() {
        var requestId = $(this).data('id');
        $('#approvalRequestId').val(requestId);
        $('#approvalModal').modal('show');
    });

    // Handle reject button click
    $(document).on('click', '.reject-btn', function() {
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
                table.ajax.reload();
                toastr.success(response.success);
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
                table.ajax.reload();
                toastr.success(response.success);
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
