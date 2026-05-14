@extends('layouts.main')
@section('title', 'FX Rates Management')

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
                            ['label' => 'FX Rates', 'url' => '#', 'icon' => 'bx bx-dollar']
                        ]" />
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('accounting.fx-rates.create') }}" class="btn btn-primary me-2">
                            <i class="bx bx-plus"></i> New FX Rate
                        </a>
                        <a href="{{ route('accounting.fx-rates.import') }}" class="btn btn-outline-primary">
                            <i class="bx bx-import"></i> Import Rates
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <h6 class="mb-0 text-uppercase">FX RATES MANAGEMENT</h6>
        <hr />

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if(session('error') || (isset($errors) && $errors->any()))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>
            @if(session('error'))
                {{ session('error') }}
            @else
                Please fix the following errors:
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <!-- Filters -->
        <div class="card radius-10 border-0 shadow-sm mb-4">
            <div class="card-body">
                <form id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Source</label>
                            <select name="source" id="filter_source" class="form-select select2-single">
                                <option value="">All Sources</option>
                                <option value="manual">Manual</option>
                                <option value="api">API</option>
                                <option value="import">Import</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="is_locked" id="filter_is_locked" class="form-select select2-single">
                                <option value="">All</option>
                                <option value="1">Locked</option>
                                <option value="0">Unlocked</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end gap-2">
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

        <!-- FX Rates Table -->
        <div class="card radius-10 border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Exchange Rates</h6>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="fxRatesTable">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>From Currency</th>
                                <th>To Currency</th>
                                <th class="text-end">Spot Rate</th>
                                <th class="text-end">Month-End Rate</th>
                                <th class="text-end">Average Rate</th>
                                <th>Source</th>
                                <th class="text-center">Status</th>
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

@push('styles')
<style>
    .table th {
        font-weight: 600;
        background-color: #f8f9fa;
    }
    .badge {
        font-size: 0.75rem;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        // Initialize Select2 for filter dropdowns
        function initializeSelect2() {
            // Destroy existing Select2 instances first to avoid conflicts
            $('.select2-single').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });
            
            $('.select2-single').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select an option',
                allowClear: true
            });
        }
        
        // Initialize Select2 on page load
        initializeSelect2();

        // Initialize DataTable
        var table = $('#fxRatesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("accounting.fx-rates.data") }}',
                data: function(d) {
                    d.source = $('#filter_source').val();
                    d.is_locked = $('#filter_is_locked').val();
                }
            },
            columns: [
                { data: 'formatted_date', name: 'rate_date' },
                { data: 'from_currency_badge', name: 'from_currency', orderable: true, searchable: true },
                { data: 'to_currency_badge', name: 'to_currency', orderable: true, searchable: true },
                { data: 'formatted_spot_rate', name: 'spot_rate', className: 'text-end' },
                { data: 'formatted_month_end_rate', name: 'month_end_rate', className: 'text-end', orderable: false },
                { data: 'formatted_average_rate', name: 'average_rate', className: 'text-end', orderable: false },
                { data: 'source_badge', name: 'source', orderable: true, searchable: true },
                { data: 'status_badge', name: 'is_locked', orderable: true, searchable: false },
                { data: 'creator_name', name: 'creator.name', orderable: false },
                { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
            ],
            order: [[0, 'desc']], // Sort by date descending
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            language: {
                processing: '<i class="bx bx-loader bx-spin"></i> Loading...',
                emptyTable: '<div class="text-center py-4"><i class="bx bx-dollar font-48 text-muted mb-3"></i><h6 class="text-muted">No FX Rates Found</h6></div>',
                zeroRecords: '<div class="text-center py-4"><i class="bx bx-dollar font-48 text-muted mb-3"></i><h6 class="text-muted">No matching records found</h6></div>'
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

        // Handle lock button click with SweetAlert
        $(document).on('click', '.lock-btn', function(e) {
            e.preventDefault();
            const form = $(this).closest('form');
            const rateId = form.data('rate-id');
            const fromCurrency = form.data('from-currency');
            const toCurrency = form.data('to-currency');
            
            Swal.fire({
                title: 'Lock FX Rate?',
                html: `
                    <div class="text-start">
                        <p class="mb-2">Are you sure you want to lock this rate?</p>
                        <div class="alert alert-warning mb-0">
                            <strong>Currency Pair:</strong> ${fromCurrency} → ${toCurrency}<br>
                            <strong>Warning:</strong> Locked rates cannot be modified. This action will prevent any future edits to this exchange rate.
                        </div>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bx bx-lock me-1"></i> Yes, Lock It',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Locking...',
                        text: 'Please wait while we lock the rate',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Submit the form
                    form.submit();
                }
            });
        });

        // Handle unlock button click with SweetAlert
        $(document).on('click', '.unlock-btn', function(e) {
            e.preventDefault();
            const form = $(this).closest('form');
            const rateId = form.data('rate-id');
            const fromCurrency = form.data('from-currency');
            const toCurrency = form.data('to-currency');
            
            Swal.fire({
                title: 'Unlock FX Rate?',
                html: `
                    <div class="text-start">
                        <p class="mb-2">Are you sure you want to unlock this rate?</p>
                        <div class="alert alert-info mb-0">
                            <strong>Currency Pair:</strong> ${fromCurrency} → ${toCurrency}<br>
                            <strong>Note:</strong> Unlocking will allow you to modify this exchange rate again.
                        </div>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bx bx-lock-open me-1"></i> Yes, Unlock It',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Unlocking...',
                        text: 'Please wait while we unlock the rate',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Submit the form
                    form.submit();
                }
            });
        });
    });
</script>
@endpush

