@extends('layouts.main')
@section('title', 'Journal Entries')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <div class="row">
            <div class="col-12">
                <div class="page-breadcrumb d-flex align-items-center">
                    <div class="me-auto">
                        <x-breadcrumbs-with-icons :links="[
                            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
                            ['label' => 'Journal Entries', 'url' => '#', 'icon' => 'bx bx-book-open']
                        ]" />
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('accounting.journals.create') }}" class="btn btn-primary">
                            <i class="bx bx-plus"></i> New Journal Entry
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <h6 class="mb-0 text-uppercase">JOURNAL ENTRIES</h6>
        <hr />

        <!-- Statistics Cards -->
        <div class="row row-cols-1 row-cols-lg-4 g-3 mb-4">
            <div class="col">
                <div class="card radius-10 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 text-muted">Total Entries</h6>
                                <h4 class="mb-0 text-primary" id="stat-total-entries">{{ $journals->count() }}</h4>
                            </div>
                            <div class="widgets-icons bg-gradient-primary text-white">
                                <i class='bx bx-book'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 text-muted">Total Debit</h6>
                                <h4 class="mb-0 text-success" id="stat-total-debit">TZS {{ number_format($journals->sum('debit_total'), 2) }}</h4>
                            </div>
                            <div class="widgets-icons bg-gradient-success text-white">
                                <i class='bx bx-plus'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 text-muted">Total Credit</h6>
                                <h4 class="mb-0 text-danger" id="stat-total-credit">TZS {{ number_format($journals->sum('credit_total'), 2) }}</h4>
                            </div>
                            <div class="widgets-icons bg-gradient-danger text-white">
                                <i class='bx bx-minus'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 text-muted">Balance</h6>
                                <h4 class="mb-0 {{ $journals->sum('balance') == 0 ? 'text-success' : 'text-warning' }}" id="stat-balance">
                                    TZS {{ number_format($journals->sum('balance'), 2) }}
                                </h4>
                            </div>
                            <div class="widgets-icons bg-gradient-{{ $journals->sum('balance') == 0 ? 'success' : 'warning' }} text-white" id="stat-balance-icon">
                                <i class='bx bx-check-circle'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Journal Entries Table -->
        <div class="card radius-10 border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Journal Entries</h6>
                    <div class="d-flex align-items-center gap-2">
                        <label for="journal-branch-filter" class="mb-0 me-1 small text-muted">Branch:</label>
                        <select id="journal-branch-filter" class="form-select form-select-sm" style="min-width: 180px;">
                            <option value="default">Current Branch</option>
                            <option value="all">All Branches</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="journalsTable">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Reference</th>
                                <th>Date</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Credit</th>
                                <th class="text-center">Balance</th>
                                <th class="text-center">Status</th>
                                <th>Created By</th>
                                <th class="border-0 text-center">
                                    <i class="bx bx-cog me-1"></i>Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables will populate this body via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

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
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Ensure DataTable controls are visible */
    .dataTables_wrapper .dataTables_filter {
        display: block !important;
        margin-bottom: 10px;
    }
    
    .dataTables_wrapper .dataTables_length {
        display: block !important;
        margin-bottom: 10px;
    }
    
    .dataTables_wrapper .dataTables_info {
        display: block !important;
        margin-top: 10px;
    }
    
    .dataTables_wrapper .dataTables_paginate {
        display: block !important;
        margin-top: 10px;
    }
    
    /* Improve search box styling */
    .dataTables_filter input {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 6px 12px;
        margin-left: 8px;
    }
    
    /* Improve length filter styling */
    .dataTables_length select {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 4px 8px;
        margin: 0 4px;
    }
    
    /* Ensure action column is visible and properly styled */
    #journalsTable th:last-child,
    #journalsTable td:last-child {
        min-width: 120px;
        text-align: center;
    }
    
    /* Style action buttons */
    #journalsTable .btn-group {
        display: flex;
        gap: 2px;
        justify-content: center;
    }
    
    #journalsTable .btn-group .btn {
        padding: 4px 8px;
        font-size: 12px;
    }
    
    /* Ensure table is responsive */
    .table-responsive {
        overflow-x: auto;
    }
    
    /* Fix DataTable responsive issues */
    .dataTables_wrapper .dataTables_scroll {
        overflow-x: auto;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    // Fix perfect-scrollbar passive event listener warning
    if (typeof PerfectScrollbar !== 'undefined') {
        const originalBind = PerfectScrollbar.prototype.bind;
        PerfectScrollbar.prototype.bind = function() {
            try {
                return originalBind.apply(this, arguments);
            } catch (e) {
                console.warn('PerfectScrollbar binding error:', e);
            }
        };
    }

    // Fix Highcharts error #13
    if (typeof Highcharts !== 'undefined') {
        Highcharts.error = function(code, stop) {
            if (code === 13) {
                console.warn('Highcharts error #13: Container not found, skipping chart rendering');
                return;
            }
            console.error('Highcharts error #' + code);
        };
    }

    $(document).ready(function() {
        const table = $('#journalsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("accounting.journals.data") }}',
                type: 'GET',
                data: function (d) {
                    const branchFilter = $('#journal-branch-filter').val();
                    if (branchFilter && branchFilter !== 'default') {
                        d.branch_id = branchFilter;
                    }
                },
                error: function(xhr, error, thrown) {
                    console.error('Journals DataTable AJAX error:', error);
                    console.error('Response:', xhr.responseText);
                }
            },
            columns: [
                { data: 'index', name: 'index', orderable: false, searchable: false },
                { data: 'reference', name: 'reference' },
                { data: 'date', name: 'date' },
                { data: 'debit', name: 'debit', orderable: false, searchable: false, className: 'text-end' },
                { data: 'credit', name: 'credit', orderable: false, searchable: false, className: 'text-end' },
                { data: 'balance_badge', name: 'balance_badge', orderable: false, searchable: false, className: 'text-center' },
                { data: 'status', name: 'status', orderable: false, searchable: false, className: 'text-center' },
                { data: 'created_by', name: 'created_by' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
            ],
            order: [[2, 'desc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            responsive: true,
            language: {
                processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
                emptyTable: '<div class="text-center p-4"><i class="bx bx-book-open font-24 text-muted"></i><p class="text-muted mt-2">No Journal Entries Found.</p></div>',
                search: "",
                searchPlaceholder: "Search journal entries...",
                lengthMenu: "Show _MENU_ entries per page",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                zeroRecords: "No matching journal entries found",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });

        // Function to load and update statistics cards
        function loadStatistics() {
            const branchFilter = $('#journal-branch-filter').val();
            const params = {};
            if (branchFilter && branchFilter !== 'default') {
                params.branch_id = branchFilter;
            }

            $.ajax({
                url: '{{ route("accounting.journals.statistics") }}',
                type: 'GET',
                data: params,
                success: function(response) {
                    // Update Total Entries
                    $('#stat-total-entries').text(response.total_entries);
                    
                    // Update Total Debit
                    $('#stat-total-debit').text('TZS ' + parseFloat(response.total_debit).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }));
                    
                    // Update Total Credit
                    $('#stat-total-credit').text('TZS ' + parseFloat(response.total_credit).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }));
                    
                    // Update Balance
                    const balance = parseFloat(response.balance);
                    const balanceFormatted = 'TZS ' + balance.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    const $balanceEl = $('#stat-balance');
                    const $balanceIcon = $('#stat-balance-icon');
                    
                    $balanceEl.text(balanceFormatted);
                    if (response.is_balanced) {
                        $balanceEl.removeClass('text-warning').addClass('text-success');
                        $balanceIcon.removeClass('bg-gradient-warning').addClass('bg-gradient-success');
                    } else {
                        $balanceEl.removeClass('text-success').addClass('text-warning');
                        $balanceIcon.removeClass('bg-gradient-success').addClass('bg-gradient-warning');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Failed to load statistics:', error);
                }
            });
        }

        // Reload table and statistics when branch filter changes
        $('#journal-branch-filter').on('change', function () {
            table.ajax.reload();
            loadStatistics();
        });
    });

    function confirmDelete(url) {
        document.getElementById('deleteForm').action = url;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
</script>
@endpush
