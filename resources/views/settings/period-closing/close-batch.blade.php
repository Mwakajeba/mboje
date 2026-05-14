@extends('layouts.main')

@section('title', 'Close Batch Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Settings', 'url' => route('settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Period-End Closing', 'url' => route('settings.period-closing.index'), 'icon' => 'bx bx-calendar-check'],
            ['label' => 'Close Batch: ' . $closeBatch->batch_label, 'url' => '#', 'icon' => 'bx bx-file']
        ]" />
        <h6 class="mb-0 text-uppercase">CLOSE BATCH DETAILS</h6>
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

        <!-- Batch Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="mb-1">{{ $closeBatch->batch_label }}</h5>
                                <p class="text-muted mb-0">
                                    Period: <strong>{{ $closeBatch->period->period_label }}</strong> | 
                                    Fiscal Year: <strong>{{ $closeBatch->period->fiscalYear->fy_label }}</strong>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge bg-{{ $closeBatch->status === 'DRAFT' ? 'warning' : ($closeBatch->status === 'REVIEW' ? 'info' : ($closeBatch->status === 'LOCKED' ? 'success' : 'secondary')) }} fs-6 px-3 py-2">
                                    {{ $closeBatch->status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Main Content -->
            <div class="col-12 col-lg-8">
                <!-- Adjustments Section -->
                <div class="card mb-4">
                    <div class="card-header bg-transparent border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Adjustments</h6>
                            @if($closeBatch->isDraft())
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAdjustmentModal">
                                    <i class="bx bx-plus me-1"></i> Add Adjustment
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        @if($closeBatch->adjustments->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Debit Account</th>
                                            <th>Credit Account</th>
                                            <th class="text-end">Amount</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($closeBatch->adjustments as $adj)
                                            <tr>
                                                <td>{{ $adj->adj_date->format('M d, Y') }}</td>
                                                <td>
                                                    <strong>{{ $adj->debitAccount->account_code ?? 'N/A' }}</strong><br>
                                                    <small class="text-muted">{{ $adj->debitAccount->account_name ?? 'N/A' }}</small>
                                                </td>
                                                <td>
                                                    <strong>{{ $adj->creditAccount->account_code ?? 'N/A' }}</strong><br>
                                                    <small class="text-muted">{{ $adj->creditAccount->account_name ?? 'N/A' }}</small>
                                                </td>
                                                <td class="text-end">
                                                    <strong>TZS {{ number_format($adj->amount, 2) }}</strong>
                                                </td>
                                                <td>{{ $adj->description }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        @if($adj->posted_journal_id)
                                                            <span class="badge bg-success">Posted</span>
                                                        @else
                                                            <span class="badge bg-warning">Pending</span>
                                                        @endif
                                                        @if($closeBatch->isDraft() && !$adj->posted_journal_id)
                                                            <form action="{{ route('settings.period-closing.close-batch.adjustments.destroy', [$closeBatch, $adj]) }}" 
                                                                  method="POST" 
                                                                  class="d-inline delete-adjustment-form"
                                                                  data-adjustment-id="{{ $adj->id }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="button" class="btn btn-sm btn-outline-danger delete-adjustment-btn" title="Delete Adjustment">
                                                                    <i class="bx bx-trash"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="bx bx-edit font-48 text-muted mb-3"></i>
                                <h6 class="text-muted">No Adjustments Added</h6>
                                <p class="text-muted mb-0">Add adjusting entries for accruals, prepayments, etc.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Period Snapshots -->
                @if($closeBatch->snapshots->count() > 0)
                <div class="card mb-4">
                    <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bx bx-camera me-2"></i>Period Snapshots</h6>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="hideZeroBalance" checked>
                            <label class="form-check-label" for="hideZeroBalance">
                                <small>Hide Zero-Balance Accounts</small>
                            </label>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Immutable Snapshot:</strong> These balances were captured at the time of closing and cannot be modified. All accounts are included in the snapshot for complete audit trail.
                        </div>
                        <div class="table-responsive">
                            <table id="snapshotsTable" class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Account</th>
                                        <th class="text-end">Opening Balance</th>
                                        <th class="text-end">Debits</th>
                                        <th class="text-end">Credits</th>
                                        <th class="text-end">Closing Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- DataTables will populate this -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-12 col-lg-4">
                <!-- Batch Information -->
                <div class="card mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Batch Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Status</label>
                            <p class="form-control-plaintext">
                                <span class="badge bg-{{ $closeBatch->status === 'DRAFT' ? 'warning' : ($closeBatch->status === 'REVIEW' ? 'info' : ($closeBatch->status === 'LOCKED' ? 'success' : 'secondary')) }}">
                                    {{ $closeBatch->status }}
                                </span>
                            </p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Prepared By</label>
                            <p class="form-control-plaintext">{{ $closeBatch->preparedBy->name ?? 'N/A' }}</p>
                            @if($closeBatch->prepared_at)
                                <small class="text-muted">{{ $closeBatch->prepared_at->format('M d, Y g:i A') }}</small>
                            @endif
                        </div>
                        @if($closeBatch->reviewedBy)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Reviewed By</label>
                            <p class="form-control-plaintext">{{ $closeBatch->reviewedBy->name }}</p>
                            @if($closeBatch->reviewed_at)
                                <small class="text-muted">{{ $closeBatch->reviewed_at->format('M d, Y g:i A') }}</small>
                            @endif
                        </div>
                        @endif
                        @if($closeBatch->approvedBy)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Approved By</label>
                            <p class="form-control-plaintext">{{ $closeBatch->approvedBy->name }}</p>
                            @if($closeBatch->approved_at)
                                <small class="text-muted">{{ $closeBatch->approved_at->format('M d, Y g:i A') }}</small>
                            @endif
                        </div>
                        @endif
                        @if($closeBatch->notes)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Notes</label>
                            <p class="form-control-plaintext">{{ $closeBatch->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Actions -->
                <div class="card mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-cog me-2"></i>Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            @if($closeBatch->isDraft())
                                <form action="{{ route('settings.period-closing.close-batch.submit-review', $closeBatch) }}" method="POST" class="d-inline" id="submitReviewForm">
                                    @csrf
                                    <button type="button" class="btn btn-info w-100" onclick="submitForReview()">
                                        <i class="bx bx-send me-1"></i> Submit for Review
                                    </button>
                                </form>
                            @endif
                            @if($closeBatch->isInReview())
                                @can('manage system settings')
                                <form action="{{ route('settings.period-closing.close-batch.approve', $closeBatch) }}" method="POST" class="d-inline" id="approveForm">
                                    @csrf
                                    <button type="button" class="btn btn-success w-100" onclick="approveCloseBatch()">
                                        <i class="bx bx-check-circle me-1"></i> Approve & Lock Period
                                    </button>
                                </form>
                                @endcan
                            @endif
                            @if($closeBatch->isLocked())
                                @php
                                    $isLastPeriod = app(\App\Services\PeriodClosing\PeriodCloseService::class)->isLastPeriodOfFiscalYear($closeBatch->period);
                                @endphp
                                @if($isLastPeriod)
                                    @can('manage system settings')
                                    <form action="{{ route('settings.period-closing.close-batch.roll-retained-earnings', $closeBatch) }}" method="POST" class="d-inline" id="rollRetainedEarningsForm">
                                        @csrf
                                        <button type="button" class="btn btn-warning w-100" onclick="rollRetainedEarnings()">
                                            <i class="bx bx-transfer me-1"></i> Roll to Retained Earnings
                                        </button>
                                    </form>
                                    @endcan
                                @endif
                            @endif
                            <a href="{{ route('settings.period-closing.index') }}" class="btn btn-secondary w-100">
                                <i class="bx bx-arrow-back me-1"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="card">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-calculator me-2"></i>Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong>Adjustments:</strong> {{ $closeBatch->adjustments->count() }}
                        </div>
                        <div class="mb-2">
                            <strong>Total Adjustment Amount:</strong> 
                            <span class="text-primary">TZS {{ number_format($closeBatch->adjustments->sum('amount'), 2) }}</span>
                        </div>
                        <div>
                            <strong>Snapshots:</strong> {{ $closeBatch->snapshots->count() }} accounts
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Adjustment Modal -->
@if($closeBatch->isDraft())
<div class="modal fade" id="addAdjustmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('settings.period-closing.close-batch.adjustments.add', $closeBatch) }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Add Adjustment Entry</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="adj_date" class="form-label">Adjustment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="adj_date" name="adj_date" 
                                   value="{{ old('adj_date', $closeBatch->period->end_date->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount" 
                                   value="{{ old('amount') }}" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="gl_debit_account" class="form-label">Debit Account <span class="text-danger">*</span></label>
                            <select class="form-select select2-single" id="gl_debit_account" name="gl_debit_account" required>
                                <option value="">-- Select Account --</option>
                                @if(isset($chartAccounts))
                                    @foreach($chartAccounts as $account)
                                        <option value="{{ $account->id }}">
                                            {{ $account->account_name }} ({{ $account->account_code }})
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="gl_credit_account" class="form-label">Credit Account <span class="text-danger">*</span></label>
                            <select class="form-select select2-single" id="gl_credit_account" name="gl_credit_account" required>
                                <option value="">-- Select Account --</option>
                                @if(isset($chartAccounts))
                                    @foreach($chartAccounts as $account)
                                        <option value="{{ $account->id }}">
                                            {{ $account->account_name }} ({{ $account->account_code }})
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="3" required
                                  placeholder="e.g., Accrual for utilities expense">{{ old('description') }}</textarea>
                        <small class="form-text text-muted">Provide a clear description of this adjustment</small>
                    </div>
                    <div class="mb-3">
                        <label for="source_document" class="form-label">Source Document</label>
                        <input type="text" class="form-control" id="source_document" name="source_document" 
                               value="{{ old('source_document') }}" placeholder="e.g., Invoice #12345">
                        <small class="form-text text-muted">Reference to supporting document</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Add Adjustment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        $('.select2-single').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });

        // Initialize DataTables for snapshots if table exists
        @if($closeBatch->snapshots->count() > 0)
        let snapshotsTable = $('#snapshotsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("settings.period-closing.close-batch.snapshots.data", $closeBatch) }}',
                type: 'GET',
                data: function(d) {
                    d.hide_zero_balance = $('#hideZeroBalance').is(':checked') ? 'true' : 'false';
                },
                error: function(xhr, error, thrown) {
                    console.error('Snapshots DataTable AJAX error:', error);
                    console.error('Response:', xhr.responseText);
                }
            },
            columns: [
                { data: 'account', name: 'account', orderable: false, searchable: true },
                { data: 'opening_balance', name: 'opening_balance', orderable: true, searchable: false, className: 'text-end' },
                { data: 'period_debits', name: 'period_debits', orderable: true, searchable: false, className: 'text-end' },
                { data: 'period_credits', name: 'period_credits', orderable: true, searchable: false, className: 'text-end' },
                { data: 'closing_balance', name: 'closing_balance', orderable: true, searchable: false, className: 'text-end' }
            ],
            order: [[0, 'asc']], // Sort by account code by default
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            responsive: true,
            language: {
                processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
                emptyTable: '<div class="text-center p-4"><i class="bx bx-camera font-24 text-muted mb-3"></i><p class="text-muted mt-2">No Snapshots Found.</p></div>',
                search: "",
                searchPlaceholder: "Search by account code or name...",
                lengthMenu: "Show _MENU_ entries per page",
                info: "Showing _START_ to _END_ of _TOTAL_ accounts",
                infoEmpty: "Showing 0 to 0 of 0 accounts",
                infoFiltered: "(filtered from _MAX_ total accounts)",
                zeroRecords: "No matching accounts found",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });

        // Toggle zero-balance filter
        $('#hideZeroBalance').on('change', function() {
            snapshotsTable.ajax.reload();
        });
        @endif

        // Delete adjustment confirmation
        $(document).on('click', '.delete-adjustment-btn', function() {
            const form = $(this).closest('form');
            const adjustmentId = form.data('adjustment-id');
            
            Swal.fire({
                title: 'Delete Adjustment?',
                text: 'Are you sure you want to delete this adjustment? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });

    function submitForReview() {
        Swal.fire({
            title: 'Submit for Review?',
            text: 'This will submit the close batch for review. You will not be able to make further changes.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0dcaf0',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, submit it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('submitReviewForm').submit();
            }
        });
    }

    function approveCloseBatch() {
        Swal.fire({
            title: 'Approve & Lock Period?',
            text: 'This will approve the close batch and lock the period. This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, approve & lock!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('approveForm').submit();
            }
        });
    }

    function rollRetainedEarnings() {
        Swal.fire({
            title: 'Roll to Retained Earnings?',
            text: 'This will create a journal entry closing all revenue and expense accounts to retained earnings. This action cannot be undone.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, roll it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('rollRetainedEarningsForm').submit();
            }
        });
    }
</script>
@endpush

