@extends('layouts.main')

@section('title', 'Accruals & Prepayments')

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
    
    .bg-gradient-purple {
        background: linear-gradient(45deg, #6f42c1, #5a32a3) !important;
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
    
    .border-purple {
        border-color: #6f42c1 !important;
    }
    
    .text-purple {
        color: #6f42c1 !important;
    }
    
    .btn-purple {
        background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
        border: none;
        color: white;
        font-weight: 600;
    }
    
    .btn-purple:hover {
        background: linear-gradient(135deg, #5a32a3 0%, #4a2a8a 100%);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(111, 66, 193, 0.3);
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Management', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Accruals & Prepayments', 'url' => '#', 'icon' => 'bx bx-time-five']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-time-five me-2"></i>Accruals & Prepayments</h5>
                                <p class="mb-0 text-muted">Manage prepaid expenses, accrued expenses, deferred income, and accrued income</p>
                            </div>
                            <div>
                                <a href="{{ route('accounting.accruals-prepayments.create') }}" class="btn btn-purple">
                                    <i class="bx bx-plus me-1"></i>New Schedule
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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

        <!-- Dashboard Stats -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-purple">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Schedules</p>
                                <h4 class="my-1 text-purple" id="total-schedules">{{ number_format($totalSchedules) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-purple"><i class="bx bx-time-five align-middle"></i> All schedules</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-purple text-white ms-auto">
                                <i class="bx bx-time-five"></i>
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
                                <p class="mb-0 text-secondary">Active Schedules</p>
                                <h4 class="my-1 text-success" id="active-schedules">{{ number_format($activeSchedules) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-check-circle align-middle"></i> Currently active</span>
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
                <div class="card radius-10 border-start border-0 border-3 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Amount</p>
                                <h4 class="my-1 text-info" id="total-amount">TZS {{ number_format($totalAmount, 2) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-info"><i class="bx bx-dollar align-middle"></i> Total scheduled</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                <i class="bx bx-dollar"></i>
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
                                <p class="mb-0 text-secondary">Remaining Amount</p>
                                <h4 class="my-1 text-warning" id="remaining-amount">TZS {{ number_format($remainingAmount, 2) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-warning"><i class="bx bx-time align-middle"></i> Unamortised</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                <i class="bx bx-time"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="mb-0"><i class="bx bx-filter me-2"></i>Filters</h6>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Branch</label>
                                <select id="branch-filter" class="form-select form-select-sm">
                                    <option value="">All Branches</option>
                                    @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Schedule Type</label>
                                <select id="schedule-type-filter" class="form-select form-select-sm">
                                    <option value="">All Types</option>
                                    <option value="prepayment">Prepayment</option>
                                    <option value="accrual">Accrual</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nature</label>
                                <select id="nature-filter" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    <option value="expense">Expense</option>
                                    <option value="income">Income</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select id="status-filter" class="form-select form-select-sm">
                                    <option value="">All Statuses</option>
                                    <option value="draft">Draft</option>
                                    <option value="submitted">Submitted</option>
                                    <option value="approved">Approved</option>
                                    <option value="active">Active</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
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
                        <div class="table-responsive">
                            <table id="schedulesTable" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Schedule #</th>
                                        <th>Category</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th class="text-end">Total Amount</th>
                                        <th class="text-end">Remaining</th>
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
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#schedulesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("accounting.accruals-prepayments.index") }}',
            type: 'GET',
            data: function(d) {
                d.branch_id = $('#branch-filter').val();
                d.schedule_type = $('#schedule-type-filter').val();
                d.nature = $('#nature-filter').val();
                d.status = $('#status-filter').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'schedule_number_link', name: 'schedule_number' },
            { data: 'category_name', name: 'category_name' },
            { data: 'formatted_start_date', name: 'start_date' },
            { data: 'formatted_end_date', name: 'end_date' },
            { data: 'formatted_total_amount', name: 'total_amount', orderable: false, searchable: false, className: 'text-end' },
            { data: 'formatted_remaining_amount', name: 'remaining_amount', orderable: false, searchable: false, className: 'text-end' },
            { data: 'status_badge', name: 'status', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[3, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            processing: '<div class="spinner-border text-purple" role="status"><span class="visually-hidden">Loading...</span></div>',
            search: "Search:",
            lengthMenu: "Show _MENU_ entries per page",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            emptyTable: "No accrual schedules found",
            zeroRecords: "No matching accrual schedules found"
        }
    });

    // Apply filters on change
    $('#branch-filter, #schedule-type-filter, #nature-filter, #status-filter').on('change', function() {
        table.draw();
    });

    // Delete function
    window.deleteSchedule = function(encodedId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this! This will permanently delete the schedule and all associated data.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6f42c1',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("accounting.accruals-prepayments.index") }}/' + encodedId,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                            Swal.fire(
                                'Deleted!',
                            response.message || 'Schedule has been deleted successfully.',
                                'success'
                            ).then(() => {
                                table.draw();
                            });
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while deleting the schedule.';
                        
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        }
                        
                        Swal.fire(
                            'Error!',
                            errorMessage,
                            'error'
                        );
                    }
                });
            }
        });
    };
});
</script>
@endpush
