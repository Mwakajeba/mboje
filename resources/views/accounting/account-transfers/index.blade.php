@extends('layouts.main')

@section('title', 'Inter-Account Transfers')

@push('styles')
<style>
    .widgets-icons-2 {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #ededed;
        font-size: 27px;
    }
    
    .bg-gradient-primary {
        background: linear-gradient(45deg, #0d6efd, #0a58ca) !important;
    }
    
    .bg-gradient-success {
        background: linear-gradient(45deg, #198754, #146c43) !important;
    }
    
    .bg-gradient-info {
        background: linear-gradient(45deg, #0dcaf0, #0aa2c0) !important;
    }
    
    .bg-gradient-warning {
        background: linear-gradient(45deg, #ffc107, #ffb300) !important;
    }
    
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .table-light {
        background-color: #f8f9fa;
    }
    
    .radius-10 {
        border-radius: 10px;
    }
    
    .border-start {
        border-left-width: 3px !important;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Management', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Inter-Account Transfers', 'url' => '#', 'icon' => 'bx bx-transfer']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-transfer me-2"></i>Inter-Account Transfers</h5>
                                <p class="mb-0 text-muted">Manage fund transfers between bank, cash, and petty cash accounts</p>
                            </div>
                            <div>
                                <a href="{{ route('accounting.account-transfers.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>New Transfer
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Stats -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Transfers</p>
                                <h4 class="my-1 text-primary" id="total-transfers">{{ number_format($totalTransfers) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-transfer align-middle"></i> All transfers</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-transfer"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Pending Approval</p>
                                <h4 class="my-1 text-info" id="pending-transfers">{{ number_format($pendingTransfers) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-info"><i class="bx bx-time align-middle"></i> Awaiting approval</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                <i class="bx bx-time"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Approved</p>
                                <h4 class="my-1 text-success" id="approved-transfers">{{ number_format($approvedTransfers) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-check-circle align-middle"></i> Approved transfers</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                <i class="bx bx-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Amount</p>
                                <h4 class="my-1 text-warning" id="total-amount">TZS {{ number_format($totalAmount, 2) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-warning"><i class="bx bx-dollar align-middle"></i> Total transferred</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                <i class="bx bx-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-body">
                        <!-- Filters -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="filter-branch" class="form-label">Branch</label>
                                <select id="filter-branch" class="form-select">
                                    <option value="">All Branches</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filter-status" class="form-label">Status</label>
                                <select id="filter-status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="draft">Draft</option>
                                    <option value="submitted">Submitted</option>
                                    <option value="approved">Approved</option>
                                    <option value="posted">Posted</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-secondary" onclick="resetFilters()">
                                    <i class="bx bx-refresh me-1"></i>Reset
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="transfers-table" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Transfer Number</th>
                                        <th>Date</th>
                                        <th>From Account</th>
                                        <th>To Account</th>
                                        <th>Amount</th>
                                        <th>Branch</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Transfers DataTable
    var transfersTable = $('#transfers-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("accounting.account-transfers.index") }}',
            data: function(d) {
                d.branch_id = $('#filter-branch').val();
                d.status = $('#filter-status').val();
            }
        },
        columns: [
            { data: 'transfer_number', name: 'transfer_number' },
            { data: 'transfer_date_formatted', name: 'transfer_date' },
            { data: 'from_account_name', name: 'from_account_name' },
            { data: 'to_account_name', name: 'to_account_name' },
            { data: 'amount_formatted', name: 'amount', className: 'text-end' },
            { data: 'branch_name', name: 'branch_name' },
            { data: 'status_badge', name: 'status', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[1, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        dom: 'Bfrtip',
        buttons: [
            'excel', 'pdf', 'print'
        ],
        language: {
            processing: '<i class="bx bx-loader bx-spin font-size-18 align-middle me-2"></i> Loading...'
        }
    });

    // Apply filters on change
    $('#filter-branch, #filter-status').on('change', function() {
        transfersTable.ajax.reload();
    });

    // Reset filters
    window.resetFilters = function() {
        $('#filter-branch').val('');
        $('#filter-status').val('');
        transfersTable.ajax.reload();
    };

    // Approve transfer
    window.approveTransfer = function(encodedId) {
        if (!confirm('Are you sure you want to approve this transfer?')) {
            return;
        }

        $.ajax({
            url: '{{ route("accounting.account-transfers.approve", ":id") }}'.replace(':id', encodedId),
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                approval_notes: ''
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Transfer approved successfully');
                    transfersTable.ajax.reload();
                } else {
                    toastr.error(response.message || 'Failed to approve transfer');
                }
            },
            error: function(xhr) {
                let message = 'Failed to approve transfer';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                toastr.error(message);
            }
        });
    };

    // Reject transfer
    window.rejectTransfer = function(encodedId) {
        const rejectionReason = prompt('Please provide a reason for rejecting this transfer (minimum 10 characters):');
        
        if (!rejectionReason || rejectionReason.length < 10) {
            if (rejectionReason !== null) {
                alert('Rejection reason must be at least 10 characters long.');
            }
            return;
        }

        if (!confirm('Are you sure you want to reject this transfer?')) {
            return;
        }

        $.ajax({
            url: '{{ route("accounting.account-transfers.reject", ":id") }}'.replace(':id', encodedId),
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                rejection_reason: rejectionReason
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Transfer rejected successfully');
                    transfersTable.ajax.reload();
                } else {
                    toastr.error(response.message || 'Failed to reject transfer');
                }
            },
            error: function(xhr) {
                let message = 'Failed to reject transfer';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                toastr.error(message);
            }
        });
    };

    // Post transfer to GL
    window.postTransferToGL = function(encodedId) {
        if (!confirm('Are you sure you want to post this transfer to GL?')) {
            return;
        }

        $.ajax({
            url: '{{ route("accounting.account-transfers.post-to-gl", ":id") }}'.replace(':id', encodedId),
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Transfer posted to GL successfully');
                    transfersTable.ajax.reload();
                } else {
                    toastr.error(response.message || 'Failed to post transfer to GL');
                }
            },
            error: function(xhr) {
                let message = 'Failed to post transfer to GL';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                toastr.error(message);
            }
        });
    };

    // Delete transfer
    window.deleteTransfer = function(encodedId) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'This action cannot be undone. The transfer will be permanently deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("accounting.account-transfers.destroy", ":id") }}'.replace(':id', encodedId),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message || 'Transfer deleted successfully');
                            transfersTable.ajax.reload();
                        } else {
                            toastr.error(response.message || 'Failed to delete transfer');
                        }
                    },
                    error: function(xhr) {
                        let message = 'Failed to delete transfer';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        toastr.error(message);
                    }
                });
            }
        });
    };
});
</script>
@endpush
@endsection
