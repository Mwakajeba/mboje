@extends('layouts.main')

@section('title', 'Share Issue Details')

@push('styles')
<style>
    .info-card {
        border-left: 4px solid;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .info-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .info-card.border-primary {
        border-left-color: #0d6efd;
    }
    
    .info-card.border-success {
        border-left-color: #198754;
    }
    
    .info-card.border-warning {
        border-left-color: #ffc107;
    }
    
    .info-card.border-info {
        border-left-color: #0dcaf0;
    }
    
    .widgets-icons-2 {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 27px;
    }
    
    .bg-light-primary {
        background-color: rgba(13, 110, 253, 0.1);
    }
    
    .bg-light-warning {
        background-color: rgba(255, 193, 7, 0.1);
    }
    
    .bg-light-success {
        background-color: rgba(25, 135, 84, 0.1);
    }
    
    .bg-light-info {
        background-color: rgba(13, 202, 240, 0.1);
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Share Capital', 'url' => route('accounting.share-capital.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Share Issues', 'url' => route('accounting.share-capital.share-issues.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => $shareIssue->reference_number ?? 'Issue #' . $shareIssue->id, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">SHARE ISSUE DETAILS</h6>
            <div>
                @if($shareIssue->status === 'draft')
                    <a href="{{ route('accounting.share-capital.share-issues.edit', $shareIssue->encoded_id) }}" class="btn btn-primary me-2">
                        <i class="bx bx-edit"></i> Edit
                    </a>
                @endif
                <a href="{{ route('accounting.share-capital.share-issues.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back"></i> Back
                </a>
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

        <!-- Statistics Cards -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 mb-4">
            <div class="col">
                <div class="card info-card border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary me-3">
                                <i class="bx bx-trending-up"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-muted">Total Shares</h6>
                                <h4 class="mb-0">{{ number_format($shareIssue->total_shares) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card info-card border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="widgets-icons-2 rounded-circle bg-light-success text-success me-3">
                                <i class="bx bx-money"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-muted">Total Amount</h6>
                                <h4 class="mb-0">{{ number_format($shareIssue->total_amount, 2) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card info-card border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="widgets-icons-2 rounded-circle bg-light-info text-info me-3">
                                <i class="bx bx-group"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-muted">Shareholders</h6>
                                <h4 class="mb-0">{{ $shareIssue->shareHoldings->count() }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card info-card border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="widgets-icons-2 rounded-circle bg-light-warning text-warning me-3">
                                <i class="bx bx-check-circle"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-muted">Status</h6>
                                @php
                                    $badgeClass = match($shareIssue->status) {
                                        'draft' => 'badge bg-secondary',
                                        'approved' => 'badge bg-primary',
                                        'posted' => 'badge bg-success',
                                        'cancelled' => 'badge bg-danger',
                                        default => 'badge bg-secondary',
                                    };
                                @endphp
                                <h4 class="mb-0"><span class="{{ $badgeClass }}">{{ strtoupper($shareIssue->status) }}</span></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Issue Information -->
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Issue Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Share Class</label>
                                    <div>
                                        <strong>{{ $shareIssue->shareClass->name ?? 'N/A' }}</strong>
                                        <span class="text-muted">({{ $shareIssue->shareClass->code ?? 'N/A' }})</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Issue Type</label>
                                    <div>
                                        <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $shareIssue->issue_type)) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Reference Number</label>
                                    <div><strong>{{ $shareIssue->reference_number ?? 'ISSUE-' . $shareIssue->id }}</strong></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Issue Date</label>
                                    <div><strong>{{ $shareIssue->issue_date->format('M d, Y') }}</strong></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Record Date</label>
                                    <div><strong>{{ $shareIssue->record_date ? $shareIssue->record_date->format('M d, Y') : 'N/A' }}</strong></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Settlement Date</label>
                                    <div><strong>{{ $shareIssue->settlement_date ? $shareIssue->settlement_date->format('M d, Y') : 'N/A' }}</strong></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Price Per Share</label>
                                    <div><strong>{{ number_format($shareIssue->price_per_share, 6) }}</strong></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Par Value</label>
                                    <div><strong>{{ number_format($shareIssue->par_value ?? 0, 6) }}</strong></div>
                                </div>
                            </div>
                            @if($shareIssue->description)
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="text-muted small mb-1">Description</label>
                                    <div>{{ $shareIssue->description }}</div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Shareholders Allocation -->
                @if($shareIssue->shareHoldings->count() > 0)
                <div class="card mb-3">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bx bx-group me-2"></i>Shareholders Allocation</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Shareholder</th>
                                        <th class="text-end">Shares</th>
                                        <th class="text-end">Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($shareIssue->shareHoldings as $holding)
                                    <tr>
                                        <td>
                                            @if($holding->shareholder && $holding->shareholder->encoded_id)
                                                <a href="{{ route('accounting.share-capital.shareholders.show', $holding->shareholder->encoded_id) }}" class="text-decoration-none">
                                                    {{ $holding->shareholder->name ?? 'N/A' }}
                                                </a>
                                            @else
                                                {{ $holding->shareholder->name ?? 'N/A' }}
                                            @endif
                                        </td>
                                        <td class="text-end">{{ number_format($holding->shares_outstanding) }}</td>
                                        <td class="text-end"><strong>{{ number_format($holding->paid_up_amount, 2) }}</strong></td>
                                        <td><span class="badge bg-success">{{ ucfirst($holding->status) }}</span></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>Total</th>
                                        <th class="text-end">{{ number_format($shareIssue->shareHoldings->sum('shares_outstanding')) }}</th>
                                        <th class="text-end">{{ number_format($shareIssue->shareHoldings->sum('paid_up_amount'), 2) }}</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                <!-- GL Entries Section -->
                @if($journal && $journal->items->count() > 0)
                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-book me-2"></i>General Ledger Entries</h6>
                    </div>
                    <div class="card-body">
                        <!-- Journal Information -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <small class="text-muted">Journal Reference:</small>
                                <div><strong>{{ $journal->reference }}</strong></div>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">Journal Date:</small>
                                <div><strong>{{ $journal->date->format('M d, Y') }}</strong></div>
                            </div>
                            @if($journal->approved)
                            <div class="col-md-6 mt-2">
                                <small class="text-muted">Approved:</small>
                                <div>
                                    <span class="badge bg-success">Yes</span>
                                    @if($journal->approved_at)
                                        <small class="text-muted">({{ $journal->approved_at->format('M d, Y H:i') }})</small>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Journal Items Table -->
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Account Code</th>
                                        <th>Account Name</th>
                                        <th>Description</th>
                                        <th class="text-end">Debit</th>
                                        <th class="text-end">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalDebit = 0;
                                        $totalCredit = 0;
                                    @endphp
                                    @foreach($journal->items as $item)
                                    @php
                                        if ($item->nature == 'debit') {
                                            $totalDebit += $item->amount;
                                        } else {
                                            $totalCredit += $item->amount;
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <small class="text-muted">{{ $item->chartAccount->account_code ?? 'N/A' }}</small>
                                        </td>
                                        <td>
                                            <strong>{{ $item->chartAccount->account_name ?? 'N/A' }}</strong>
                                        </td>
                                        <td>
                                            <small>{{ $item->description ?? $journal->description }}</small>
                                        </td>
                                        <td class="text-end">
                                            @if($item->nature == 'debit')
                                                <span class="text-danger fw-semibold">{{ number_format($item->amount, 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($item->nature == 'credit')
                                                <span class="text-success fw-semibold">{{ number_format($item->amount, 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3" class="text-end">Total:</th>
                                        <th class="text-end text-danger">{{ number_format($totalDebit, 2) }}</th>
                                        <th class="text-end text-success">{{ number_format($totalCredit, 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- GL Transactions (if posted) -->
                        @if($glTransactions->count() > 0)
                        <div class="mt-4">
                            <h6 class="mb-3"><i class="bx bx-list-ul me-2"></i>Posted GL Transactions</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Account</th>
                                            <th>Description</th>
                                            <th class="text-end">Debit</th>
                                            <th class="text-end">Credit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($glTransactions as $transaction)
                                        <tr>
                                            <td>{{ $transaction->date->format('M d, Y') }}</td>
                                            <td>
                                                <small class="text-muted">{{ $transaction->chartAccount->account_code ?? 'N/A' }}</small><br>
                                                <strong>{{ $transaction->chartAccount->account_name ?? 'N/A' }}</strong>
                                            </td>
                                            <td>
                                                <small>{{ $transaction->description }}</small>
                                            </td>
                                            <td class="text-end">
                                                @if($transaction->nature == 'debit')
                                                    <span class="text-danger fw-semibold">{{ number_format($transaction->amount, 2) }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if($transaction->nature == 'credit')
                                                    <span class="text-success fw-semibold">{{ number_format($transaction->amount, 2) }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @elseif($shareIssue->status === 'posted')
                <div class="card mb-3">
                    <div class="card-body text-center text-muted">
                        <i class="bx bx-info-circle fs-1 mb-2"></i>
                        <p class="mb-0">No GL entries found for this share issue.</p>
                    </div>
                </div>
                @endif
            </div>

            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="card mb-3">
                    <div class="card-header bg-warning text-white">
                        <h6 class="mb-0"><i class="bx bx-cog me-2"></i>Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        @if($shareIssue->status === 'draft')
                            <form id="approveIssueForm" action="{{ route('accounting.share-capital.share-issues.approve', $shareIssue->encoded_id) }}" method="POST" class="mb-2">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bx bx-check"></i> Approve Issue
                                </button>
                            </form>
                        @endif
                        
                        @if($shareIssue->status === 'approved')
                            <button type="button" class="btn btn-success w-100 mb-2" data-bs-toggle="modal" data-bs-target="#postToGlModal">
                                <i class="bx bx-check"></i> Post to GL
                            </button>
                        @endif
                        
                        @if($shareIssue->status === 'posted')
                            <div class="alert alert-success mb-0">
                                <i class="bx bx-check-circle"></i> Posted to GL
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Audit Information -->
                <div class="card mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bx bx-time me-2"></i>Audit Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Created</small>
                            <div>{{ $shareIssue->created_at->format('M d, Y H:i') }}</div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Created By</small>
                            <div><strong>{{ $shareIssue->creator->name ?? 'N/A' }}</strong></div>
                        </div>
                        @if($shareIssue->approver)
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Approved</small>
                            <div>{{ $shareIssue->updated_at->format('M d, Y H:i') }}</div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Approved By</small>
                            <div><strong>{{ $shareIssue->approver->name ?? 'N/A' }}</strong></div>
                        </div>
                        @endif
                        @if($shareIssue->poster)
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Posted</small>
                            <div>{{ $shareIssue->updated_at->format('M d, Y H:i') }}</div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Posted By</small>
                            <div><strong>{{ $shareIssue->poster->name ?? 'N/A' }}</strong></div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Post to GL Modal -->
<div class="modal fade" id="postToGlModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="postToGlForm" action="{{ route('accounting.share-capital.share-issues.post-to-gl', $shareIssue->encoded_id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Post Share Issue to GL</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Select the GL accounts to post this share issue.</p>
                    <div class="mb-3">
                        <label class="form-label">Bank Account <span class="text-danger">*</span></label>
                        <select name="bank_account_id" class="form-select" required>
                            <option value="">Select Bank Account</option>
                            <!-- Options should be loaded from backend -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Share Capital Account <span class="text-danger">*</span></label>
                        <select name="share_capital_account_id" class="form-select" required>
                            <option value="">Select Account</option>
                            <!-- Options should be loaded from backend -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Share Premium Account</label>
                        <select name="share_premium_account_id" class="form-select">
                            <option value="">Select Account</option>
                            <!-- Options should be loaded from backend -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Issue Costs</label>
                        <input type="number" step="0.01" name="issue_costs" class="form-control" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Post to GL</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    document.addEventListener('DOMContentLoaded', function () {
        const approveIssueForm = document.getElementById('approveIssueForm');
        if (approveIssueForm) {
            approveIssueForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (typeof Swal === 'undefined') {
                    return approveIssueForm.submit();
                }
                Swal.fire({
                    title: 'Approve this share issue?',
                    text: 'This will mark the issue as approved and enable posting to GL.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, approve',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        approveIssueForm.submit();
                    }
                });
            });
        }

        const postToGlForm = document.getElementById('postToGlForm');
        if (postToGlForm) {
            postToGlForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (typeof Swal === 'undefined') {
                    return postToGlForm.submit();
                }
                Swal.fire({
                    title: 'Post share issue to GL?',
                    text: 'A journal will be created and GL entries posted. This action cannot be undone easily.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, post to GL',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        postToGlForm.submit();
                    }
                });
            });
        }
    });
</script>
@endpush
