@extends('layouts.main')
@section('title', 'FX Revaluation History')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'FX Revaluation', 'url' => '#', 'icon' => 'bx bx-refresh']
        ]" />
        <h6 class="mb-0 text-uppercase">FX REVALUATION HISTORY</h6>
        <hr />

        <!-- Page Header -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card border-top border-0 border-4 border-primary">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="card-title d-flex align-items-center">
                                    <div><i class="bx bx-refresh me-1 font-22 text-primary"></i></div>
                                    <h5 class="mb-0 text-primary">FX Revaluation History</h5>
                                </div>
                                <p class="mb-0 text-muted">View and manage foreign currency revaluation history</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="{{ route('accounting.fx-revaluation.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i> New Revaluation
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <!-- Filters -->
        <div class="card radius-10 border-0 shadow-sm mb-4">
            <div class="card-body">
                <form id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" id="filter_branch_id" class="form-select select2-single">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Item Type</label>
                            <select name="item_type" id="filter_item_type" class="form-select select2-single">
                                <option value="">All Types</option>
                                <option value="AR">Accounts Receivable</option>
                                <option value="AP">Accounts Payable</option>
                                <option value="BANK">Bank Accounts</option>
                                <option value="LOAN">Loans</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" id="filter_date_from" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" id="filter_date_to" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="is_reversed" id="filter_is_reversed" class="form-select select2-single">
                                <option value="">All</option>
                                <option value="0">Not Reversed</option>
                                <option value="1">Reversed</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <button type="button" id="applyFilters" class="btn btn-primary">
                                <i class="bx bx-search me-1"></i> Apply Filters
                            </button>
                            <button type="button" id="resetFilters" class="btn btn-outline-secondary">
                                <i class="bx bx-refresh me-1"></i> Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Revaluation History Table -->
        <div class="card radius-10 border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="revaluationTable">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Item Type</th>
                                <th>Reference</th>
                                <th>Currency</th>
                                <th class="text-end">FCY Amount</th>
                                <th class="text-end">Original Rate</th>
                                <th class="text-end">Closing Rate</th>
                                <th class="text-end">Gain/Loss</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables will populate this via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        // Initialize Select2
        $('.select2-single').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select an option',
            allowClear: true
        });

        // Initialize DataTable
        var table = $('#revaluationTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("accounting.fx-revaluation.data") }}',
                data: function(d) {
                    d.branch_id = $('#filter_branch_id').val();
                    d.item_type = $('#filter_item_type').val();
                    d.date_from = $('#filter_date_from').val();
                    d.date_to = $('#filter_date_to').val();
                    d.is_reversed = $('#filter_is_reversed').val();
                }
            },
            columns: [
                { data: 'formatted_date', name: 'revaluation_date' },
                { data: 'item_type_badge', name: 'item_type', orderable: true, searchable: true },
                { data: 'item_ref', name: 'item_ref' },
                { data: 'currency', name: 'currency', orderable: false, searchable: false },
                { data: 'formatted_fcy_amount', name: 'fcy_amount', className: 'text-end' },
                { data: 'formatted_original_rate', name: 'original_rate', className: 'text-end' },
                { data: 'formatted_closing_rate', name: 'closing_rate', className: 'text-end' },
                { data: 'formatted_gain_loss', name: 'gain_loss', className: 'text-end' },
                { data: 'status_badge', name: 'is_reversed', orderable: true, searchable: false },
                { data: 'creator_name', name: 'creator.name', orderable: false },
                { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
            ],
            order: [[0, 'desc']], // Sort by date descending
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            language: {
                processing: '<i class="bx bx-loader bx-spin"></i> Loading...',
                emptyTable: '<div class="text-center py-4"><i class="bx bx-refresh font-48 text-muted mb-3"></i><h6 class="text-muted">No Revaluation History Found</h6></div>',
                zeroRecords: '<div class="text-center py-4"><i class="bx bx-refresh font-48 text-muted mb-3"></i><h6 class="text-muted">No matching records found</h6></div>'
            },
            drawCallback: function(settings) {
                // Re-initialize Select2 and event handlers after table redraw
                $('.select2-single').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: 'Select an option',
                    allowClear: true
                });
            }
        });

        // Apply filters
        $('#applyFilters').on('click', function() {
            table.ajax.reload();
        });

        // Reset filters
        $('#resetFilters').on('click', function() {
            $('#filterForm')[0].reset();
            $('.select2-single').val(null).trigger('change');
            table.ajax.reload();
        });

        // Handle reverse button click with SweetAlert
        $(document).on('click', '.reverse-btn', function(e) {
            e.preventDefault();
            const form = $(this).closest('form');
            const itemRef = form.data('item-ref');
            
            Swal.fire({
                title: 'Reverse Revaluation?',
                html: `
                    <div class="text-start">
                        <p class="mb-2">Are you sure you want to reverse this revaluation?</p>
                        <div class="alert alert-warning mb-0">
                            <strong>Reference:</strong> ${itemRef}<br>
                            <strong>Warning:</strong> This will create a reversal journal entry. This action cannot be undone.
                        </div>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bx bx-undo me-1"></i> Yes, Reverse It',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Reversing...',
                        text: 'Please wait while we reverse the revaluation',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    form.submit();
                }
            });
        });
    });
</script>
@endpush

