@extends('layouts.main')

@section('title', 'Petty Cash Register')

@push('styles')
<style>
    .info-card {
        border-left: 3px solid;
        transition: transform 0.2s;
    }
    
    .info-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .info-card.border-primary { border-left-color: #0d6efd; }
    .info-card.border-success { border-left-color: #198754; }
    .info-card.border-warning { border-left-color: #ffc107; }
    .info-card.border-danger { border-left-color: #dc3545; }
    .info-card.border-info { border-left-color: #0dcaf0; }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Management', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Petty Cash Units', 'url' => route('accounting.petty-cash.units.index'), 'icon' => 'bx bx-wallet'],
            ['label' => $unit->name, 'url' => route('accounting.petty-cash.units.show', $unit->encoded_id), 'icon' => 'bx bx-show'],
            ['label' => 'Register', 'url' => '#', 'icon' => 'bx bx-list-ul']
        ]" />

        <!-- Header Section -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-0">Petty Cash Register</h4>
                        <p class="text-muted mb-0">{{ $unit->name }} ({{ $unit->code }})</p>
                    </div>
                    <div class="page-title-right d-flex gap-2">
                        <a href="{{ route('accounting.petty-cash.reconciliation.index') }}?as_of_date={{ $asOfDate ?? now()->toDateString() }}" class="btn btn-warning">
                            <i class="bx bx-table me-1"></i>View All Reconciliations
                        </a>
                        <a href="{{ route('accounting.petty-cash.register.reconciliation', $unit->encoded_id) }}" class="btn btn-info">
                            <i class="bx bx-check-square me-1"></i>Reconciliation
                        </a>
                        <a href="{{ route('accounting.petty-cash.units.show', $unit->encoded_id) }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back to Unit
                        </a>
                    </div>
                </div>
                <hr>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card info-card border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Opening Balance</h6>
                                <h4 class="mb-0">TZS {{ number_format($reconciliation['opening_balance'] ?? 0, 2) }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="bx bx-wallet fs-1 text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card info-card border-danger">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Total Disbursed</h6>
                                <h4 class="mb-0">TZS {{ number_format($reconciliation['total_disbursed'] ?? 0, 2) }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="bx bx-down-arrow-circle fs-1 text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card info-card border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Total Replenished</h6>
                                <h4 class="mb-0">TZS {{ number_format($reconciliation['total_replenished'] ?? 0, 2) }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="bx bx-up-arrow-circle fs-1 text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card info-card border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Closing Cash</h6>
                                <h4 class="mb-0">TZS {{ number_format($reconciliation['closing_cash'] ?? 0, 2) }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="bx bx-calculator fs-1 text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Register Table -->
        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Petty Cash Register</h6>
                        <div class="d-flex gap-2">
                            <a href="{{ route('accounting.petty-cash.register.export.pdf', array_merge([$unit->encoded_id], request()->query())) }}" 
                               class="btn btn-sm btn-danger" target="_blank">
                                <i class="bx bxs-file-pdf me-1"></i>Export PDF
                            </a>
                            <a href="{{ route('accounting.petty-cash.register.export.excel', array_merge([$unit->encoded_id], request()->query())) }}" 
                               class="btn btn-sm btn-success" target="_blank">
                                <i class="bx bxs-file-excel me-1"></i>Export Excel
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" id="date_from" value="{{ request('date_from', now()->startOfMonth()->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" id="date_to" value="{{ request('date_to', now()->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="status">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="posted">Posted</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Entry Type</label>
                                <select class="form-select" id="entry_type">
                                    <option value="">All Types</option>
                                    <option value="disbursement">Disbursement</option>
                                    <option value="replenishment">Replenishment</option>
                                    <option value="opening_balance">Opening Balance</option>
                                    <option value="adjustment">Adjustment</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-primary d-block w-100" onclick="applyFilters()">
                                    <i class="bx bx-filter me-1"></i>Filter
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="register-table" class="table table-hover table-striped" style="width:100%">
                                <thead class="table-light">
                                    <tr>
                                        <th>PCV Number</th>
                                        <th>Date</th>
                                        <th>Entry Type</th>
                                        <th>Description</th>
                                        <th class="text-end">Amount</th>
                                        <th>Nature</th>
                                        <th>GL Account</th>
                                        <th>Requested By</th>
                                        <th>Approved By</th>
                                        <th>Status</th>
                                        <th class="text-end">Balance After</th>
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
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Register DataTable
    var registerTable = $('#register-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("accounting.petty-cash.register.index", $unit->encoded_id) }}',
            type: 'GET',
            data: function(d) {
                d.date_from = $('#date_from').val();
                d.date_to = $('#date_to').val();
                d.status = $('#status').val();
                d.entry_type = $('#entry_type').val();
            }
        },
        columns: [
            {data: 'pcv_number_link', name: 'pcv_number', orderable: true, searchable: true},
            {data: 'formatted_date', name: 'register_date', orderable: true, searchable: false},
            {data: 'entry_type_badge', name: 'entry_type', orderable: true, searchable: true},
            {data: 'description', name: 'description', orderable: true, searchable: true},
            {data: 'formatted_amount', name: 'amount', orderable: true, searchable: false, className: 'text-end'},
            {data: 'nature_badge', name: 'nature', orderable: true, searchable: true},
            {data: 'gl_account_name', name: 'glAccount.account_name', orderable: false, searchable: false},
            {data: 'requested_by_name', name: 'requestedBy.name', orderable: false, searchable: false},
            {data: 'approved_by_name', name: 'approvedBy.name', orderable: false, searchable: false},
            {data: 'status_badge', name: 'status', orderable: true, searchable: true},
            {data: 'formatted_balance_after', name: 'balance_after', orderable: true, searchable: false, className: 'text-end'}
        ],
        order: [[1, 'desc']], // Order by date descending
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            processing: '<i class="bx bx-loader-alt bx-spin"></i> Loading...',
            emptyTable: 'No register entries found',
            zeroRecords: 'No matching register entries found'
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        drawCallback: function(settings) {
            // Any additional callbacks after table draw
        }
    });

    // Apply filters function
    window.applyFilters = function() {
        registerTable.ajax.reload();
    };

});
</script>
@endpush

