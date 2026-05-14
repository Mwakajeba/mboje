@extends('layouts.main')

@section('title', 'Fiscal Years Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Settings', 'url' => route('settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Period-End Closing', 'url' => route('settings.period-closing.index'), 'icon' => 'bx bx-calendar-check'],
            ['label' => 'Fiscal Years', 'url' => '#', 'icon' => 'bx bx-calendar']
        ]" />
        <h6 class="mb-0 text-uppercase">FISCAL YEARS MANAGEMENT</h6>
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
                            <h6 class="mb-0"><i class="bx bx-calendar me-2"></i>Fiscal Years</h6>
                            <div>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createFiscalYearModal">
                                    <i class="bx bx-plus me-1"></i> Create Fiscal Year
                                </button>
                                <a href="{{ route('settings.period-closing.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="bx bx-arrow-back me-1"></i> Back
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="fiscalYearsTable" class="table table-hover table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Fiscal Year</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Duration</th>
                                        <th>Periods</th>
                                        <th>Status</th>
                                        <th>Created By</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- DataTables will populate this -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Fiscal Year Modal -->
<div class="modal fade" id="createFiscalYearModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('settings.period-closing.fiscal-years.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Create New Fiscal Year</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="fy_label" class="form-label">Fiscal Year Label <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="fy_label" name="fy_label" 
                               value="{{ old('fy_label') }}" placeholder="e.g., FY2026" required>
                        <small class="form-text text-muted">A short label to identify this fiscal year</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="{{ old('start_date') }}" required>
                            <small class="form-text text-muted">First day of the fiscal year</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="{{ old('end_date') }}" required>
                            <small class="form-text text-muted">Last day of the fiscal year</small>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>
                        <strong>Note:</strong> Monthly periods will be automatically generated for this fiscal year.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Create Fiscal Year
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Periods Modal -->
<div class="modal fade" id="viewPeriodsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bx bx-list-ul me-2"></i>Periods for <span id="modalFiscalYearLabel"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="periodsLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading periods...</p>
                </div>
                <div id="periodsContent" style="display: none;">
                    <div class="mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <small class="text-muted">Fiscal Year Period</small>
                                <p class="mb-0"><strong id="modalFiscalYearDates"></strong></p>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Total Periods</small>
                                <p class="mb-0"><strong id="modalTotalPeriods"></strong></p>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Status</small>
                                <p class="mb-0" id="modalFiscalYearStatus"></p>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm" id="periodsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Period</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Locked By</th>
                                    <th>Close Batches</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="periodsTableBody">
                                <!-- Will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="periodsError" style="display: none;" class="alert alert-danger">
                    <i class="bx bx-error-circle me-2"></i>
                    <span id="periodsErrorMessage"></span>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        const table = $('#fiscalYearsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("settings.period-closing.fiscal-years.data") }}',
                type: 'GET',
                error: function(xhr, error, thrown) {
                    console.error('Fiscal Years DataTable AJAX error:', error);
                    console.error('Response:', xhr.responseText);
                }
            },
            columns: [
                { data: 'index', name: 'index', orderable: false, searchable: false, className: 'text-center' },
                { data: 'fy_label', name: 'fy_label' },
                { data: 'start_date', name: 'start_date' },
                { data: 'end_date', name: 'end_date' },
                { data: 'duration', name: 'duration', orderable: false, searchable: false },
                { data: 'periods', name: 'periods', orderable: false, searchable: false, className: 'text-center' },
                { data: 'status', name: 'status', orderable: true, searchable: false, className: 'text-center' },
                { data: 'created_by', name: 'created_by' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
            ],
            order: [[2, 'desc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            responsive: true,
            language: {
                processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
                emptyTable: '<div class="text-center p-4"><i class="bx bx-calendar font-24 text-muted"></i><p class="text-muted mt-2">No Fiscal Years Found.</p><p class="text-muted">Create your first fiscal year to get started.</p></div>',
                search: "",
                searchPlaceholder: "Search fiscal years...",
                lengthMenu: "Show _MENU_ entries per page",
                info: "Showing _START_ to _END_ of _TOTAL_ fiscal years",
                infoEmpty: "Showing 0 to 0 of 0 fiscal years",
                infoFiltered: "(filtered from _MAX_ total fiscal years)",
                zeroRecords: "No matching fiscal years found",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });
    });

    function viewPeriods(fyId) {
        const modal = new bootstrap.Modal(document.getElementById('viewPeriodsModal'));
        modal.show();

        // Reset modal state
        $('#periodsLoading').show();
        $('#periodsContent').hide();
        $('#periodsError').hide();
        $('#periodsTableBody').empty();

        // Fetch periods for this fiscal year
        $.ajax({
            url: '{{ route("settings.period-closing.fiscal-years.periods", ":id") }}'.replace(':id', fyId),
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    // Update modal header
                    $('#modalFiscalYearLabel').text(response.fiscal_year.fy_label);
                    $('#modalFiscalYearDates').text(response.fiscal_year.start_date + ' - ' + response.fiscal_year.end_date);
                    $('#modalTotalPeriods').text(response.total_periods + ' periods');

                    // Populate periods table
                    const tbody = $('#periodsTableBody');
                    tbody.empty();

                    if (response.periods.length === 0) {
                        tbody.append('<tr><td colspan="8" class="text-center py-4 text-muted">No periods found for this fiscal year.</td></tr>');
                    } else {
                        response.periods.forEach(function(period) {
                            const row = `
                                <tr>
                                    <td><strong>${period.period_label}</strong></td>
                                    <td>${period.start_date}</td>
                                    <td>${period.end_date}</td>
                                    <td><span class="badge bg-info">${period.period_type}</span></td>
                                    <td>${period.status_badge}</td>
                                    <td>
                                        ${period.locked_by}
                                        ${period.locked_at !== '-' ? '<br><small class="text-muted">' + period.locked_at + '</small>' : ''}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">${period.close_batches}</span>
                                    </td>
                                    <td class="text-center">${period.actions}</td>
                                </tr>
                            `;
                            tbody.append(row);
                        });
                    }

                    $('#periodsLoading').hide();
                    $('#periodsContent').show();
                } else {
                    showPeriodsError('Failed to load periods. Please try again.');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to load periods. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                }
                showPeriodsError(errorMessage);
            }
        });
    }

    function showPeriodsError(message) {
        $('#periodsLoading').hide();
        $('#periodsContent').hide();
        $('#periodsError').show();
        $('#periodsErrorMessage').text(message);
    }
</script>
@endpush

