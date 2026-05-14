@extends('layouts.main')

@section('title', 'Accounting Periods')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Settings', 'url' => route('settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Period-End Closing', 'url' => route('settings.period-closing.index'), 'icon' => 'bx bx-calendar-check'],
            ['label' => 'Periods', 'url' => '#', 'icon' => 'bx bx-calendar']
        ]" />
        <h6 class="mb-0 text-uppercase">ACCOUNTING PERIODS</h6>
        <hr/>

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
                    <div class="card-header bg-transparent border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="bx bx-calendar me-2"></i>Accounting Periods</h6>
                            <a href="{{ route('settings.period-closing.index') }}" class="btn btn-secondary btn-sm">
                                <i class="bx bx-arrow-back me-1"></i> Back
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="periodsTable" class="table table-striped table-bordered" style="width:100%">
                                <thead class="table-light">
                                    <tr>
                                        <th>Period</th>
                                        <th>Fiscal Year</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Locked By</th>
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

<!-- Reopen Period Modal (Dynamic) -->
@can('manage system settings')
<div class="modal fade" id="reopenPeriodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="POST" id="reopenPeriodForm">
                @csrf
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">Reopen Period</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bx bx-error-circle me-2"></i>
                        <strong>Warning:</strong> Reopening a locked/closed period will allow new transactions to be posted to this period. This action should only be performed with proper authorization.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Period</label>
                        <p class="form-control-plaintext" id="modalPeriodInfo">
                            <strong id="modalPeriodLabel"></strong> 
                            (<span id="modalPeriodDates"></span>)
                        </p>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for Reopening <span class="text-danger">*</span></label>
                        <textarea class="form-control" 
                                  id="reason" 
                                  name="reason" 
                                  rows="3" 
                                  placeholder="Enter the reason for reopening this period (required for audit trail)"
                                  required></textarea>
                        <small class="form-text text-muted">This reason will be logged in the audit trail.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="confirmReopenBtn">
                        <i class="bx bx-lock-open me-1"></i> Reopen Period
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Get fiscal year ID from URL if present
    const urlParams = new URLSearchParams(window.location.search);
    const fyId = urlParams.get('fy_id');

    // Initialize DataTable
    const table = $('#periodsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("settings.period-closing.periods") }}',
            type: 'GET',
            data: function(d) {
                if (fyId) {
                    d.fy_id = fyId;
                }
            }
        },
        columns: [
            { data: 'period_label_formatted', name: 'period_label' },
            { data: 'fiscal_year', name: 'fiscalYear.fy_label' },
            { data: 'start_date_formatted', name: 'start_date' },
            { data: 'end_date_formatted', name: 'end_date' },
            { data: 'period_type_badge', name: 'period_type', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status', orderable: false, searchable: false },
            { data: 'locked_by_info', name: 'lockedBy.name', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[2, 'desc']], // Sort by start date descending
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        responsive: true,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: '<div class="text-center p-4"><i class="bx bx-calendar font-48 text-muted mb-3"></i><h6 class="text-muted">No Periods Found</h6><p class="text-muted mb-0">Create a fiscal year to generate periods</p></div>'
        },
        drawCallback: function(settings) {
            // Re-initialize reopen button handlers after table redraw
            $('.reopen-btn').on('click', function() {
                const periodId = $(this).data('period-id');
                const periodLabel = $(this).data('period-label');
                const startDate = $(this).data('start-date');
                const endDate = $(this).data('end-date');
                
                // Update modal with period information
                $('#modalPeriodLabel').text(periodLabel);
                $('#modalPeriodDates').text(startDate + ' - ' + endDate);
                // Use period_id for route model binding
                const reopenUrl = '{{ route("settings.period-closing.periods.reopen", ":id") }}'.replace(':id', periodId);
                $('#reopenPeriodForm').attr('action', reopenUrl);
                
                // Show modal
                $('#reopenPeriodModal').modal('show');
            });
        }
    });

    // Handle confirm reopen button
    $('#confirmReopenBtn').on('click', function() {
        const form = $('#reopenPeriodForm')[0];
        
        // Check if form is valid (HTML5 validation)
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Get reason value
        const reason = $('#reason').val().trim();
        
        if (!reason) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please provide a reason for reopening this period.'
            });
            return;
        }

        Swal.fire({
            title: 'Reopen Period?',
            html: '<p>Are you sure you want to reopen this period?</p><p class="text-muted"><small>This will allow new transactions to be posted to this period.</small></p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, reopen it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // Reset modal when closed
    $('#reopenPeriodModal').on('hidden.bs.modal', function() {
        $('#reopenPeriodForm')[0].reset();
        $('#reopenPeriodForm').attr('action', '');
    });
});
</script>
@endpush

