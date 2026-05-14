@extends('layouts.main')

@section('title', 'Bank Reconciliation Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Bank Reconciliation', 'url' => route('accounting.bank-reconciliation.index'), 'icon' => 'bx bx-credit-card'],
            ['label' => $bankReconciliation->bankAccount->name . ' - ' . $bankReconciliation->formatted_reconciliation_date, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <h6 class="mb-0 text-uppercase">BANK RECONCILIATION DETAILS</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-end mb-3">
                    <a href="{{ route('accounting.bank-reconciliation.statement', $bankReconciliation) }}" class="btn btn-primary me-2" target="_blank">
                        <i class="bx bx-file me-2"></i>View Statement
                    </a>
                    {{-- <a href="{{ route('accounting.bank-reconciliation.export-statement', $bankReconciliation) }}" class="btn btn-danger me-2">
                        <i class="bx bx-download me-2"></i>Export Statement PDF
                    </a> --}}
                    {{-- <a href="{{ route('accounting.reports.bank-reconciliation-report.export', $bankReconciliation) }}" class="btn btn-secondary me-2">
                        <i class="bx bx-download me-2"></i>Export Report PDF
                    </a> --}}
                    @if($bankReconciliation->status === 'draft')
                        <a href="{{ route('accounting.bank-reconciliation.edit', $bankReconciliation) }}" class="btn btn-warning me-2">
                            <i class="bx bx-edit me-2"></i>Edit Reconciliation
                        </a>
                    @endif
                    @if($bankReconciliation->status !== 'completed')
                        <button type="button" class="btn btn-info me-2" onclick="refreshBookBalance()" id="refreshBookBalanceBtn">
                            <i class="bx bx-refresh me-2"></i>Refresh Book Balance
                        </button>
                    @endif
                    @if($bankReconciliation->status === 'approved')
                        <button type="button" class="btn btn-success me-2" onclick="markAsCompleted()" title="Mark this reconciliation as completed after approval">
                            <i class="bx bx-check-circle me-2"></i>Mark as Completed
                        </button>
                        
                        <!-- Hidden form for completion -->
                        <form id="completeForm" action="{{ route('accounting.bank-reconciliation.complete', $bankReconciliation) }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    @endif
                <a href="{{ route('accounting.bank-reconciliation.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-2"></i>Back to Reconciliations
                </a>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row row-cols-1 row-cols-lg-4 mb-4">
            <div class="col">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="">
                                <p class="mb-1 text-secondary">Bank Statement Balance</p>
                                <h4 class="my-1 text-dark">{{ $bankReconciliation->formatted_bank_statement_balance }}</h4>
                            </div>
                            <div class="ms-auto fs-1 text-primary">
                                <i class="bx bx-bank"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="">
                                <p class="mb-1 text-secondary">Book Balance</p>
                                <h4 class="my-1 text-dark">{{ $bankReconciliation->formatted_book_balance }}</h4>
                                @if($bankReconciliation->status !== 'completed')
                                    <small class="text-info">
                                        <i class="bx bx-sync me-1"></i>Auto-updates with new transactions
                                    </small>
                                @endif
                            </div>
                            <div class="ms-auto fs-1 text-info">
                                <i class="bx bx-book"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="">
                                <p class="mb-1 text-secondary">Difference</p>
                                <h4 class="my-1 {{ $bankReconciliation->difference == 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $bankReconciliation->formatted_difference }}
                                </h4>
                            </div>
                            <div class="ms-auto fs-1 {{ $bankReconciliation->difference == 0 ? 'text-success' : 'text-danger' }}">
                                <i class="bx {{ $bankReconciliation->difference == 0 ? 'bx-check-circle' : 'bx-x-circle' }}"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="">
                                <p class="mb-1 text-secondary">Status</p>
                                <h4 class="my-1">{!! $bankReconciliation->status_badge !!}</h4>
                                @if($bankReconciliation->status === 'pending_approval' && $currentLevel)
                                    <small class="text-muted">
                                        <i class="bx bx-user me-1"></i>
                                        Level {{ $bankReconciliation->current_approval_level }} - {{ $currentLevel->level_name }}
                                    </small>
                                @endif
                            </div>
                            <div class="ms-auto fs-1 text-warning">
                                <i class="bx bx-time"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approval Actions -->
        @if(in_array($bankReconciliation->status, ['draft', 'rejected']) && $canSubmit && auth()->user()->can('submit bank reconciliation for approval'))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-send me-2"></i>Submit for Approval
                        </h5>
                    </div>
                    <div class="card-body">
                        @if(!$bankReconciliation->isBalanced())
                            <div class="alert alert-danger mb-3">
                                <i class="bx bx-error-circle me-2"></i>
                                <strong>Warning:</strong> This reconciliation is not balanced (Difference: {{ number_format($bankReconciliation->difference, 2) }}). 
                                You must balance it before submitting for approval.
                            </div>
                        @else
                            <div class="alert alert-success mb-3">
                                <i class="bx bx-check-circle me-2"></i>
                                <strong>Ready for Approval:</strong> This reconciliation is balanced and ready to be submitted for approval.
                            </div>
                        @endif
                        <p class="mb-3">
                            <strong>Workflow:</strong> 
                            1. Complete the reconciliation (ensure it's balanced) → 
                            2. Submit for Approval → 
                            3. After approval, mark as Completed
                        </p>
                        <form action="{{ route('accounting.bank-reconciliation.submit-for-approval', $bankReconciliation) }}" method="POST" id="submitApprovalForm">
                            @csrf
                            <button type="submit" class="btn btn-primary" {{ !$bankReconciliation->isBalanced() ? 'disabled' : '' }}>
                                <i class="bx bx-send me-2"></i>Submit for Approval
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($bankReconciliation->status === 'pending_approval' && $canApprove && $currentLevel && (auth()->user()->can('approve bank reconciliation') || auth()->user()->can('reject bank reconciliation')))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-check-circle me-2"></i>Approval Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">You are authorized to approve or reject this bank reconciliation at the current level (<strong>{{ $currentLevel->level_name }}</strong>).</p>
                        
                        <!-- Approve Form -->
                        <div class="mb-3">
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                                <i class="bx bx-check-circle me-2"></i>Approve
                            </button>
                        </div>
                        
                        <!-- Reject Form -->
                        <div>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="bx bx-x-circle me-2"></i>Reject
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="row">
            <!-- Reconciliation Details -->
            <div class="col-lg-8">
                <div class="card radius-10">
                    <div class="card-header">
                        <h6 class="mb-0">Reconciliation Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="fw-bold">Bank Account:</td>
                                        <td>{{ $bankReconciliation->bankAccount->name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Account Number:</td>
                                        <td>{{ $bankReconciliation->bankAccount->account_number }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Reconciliation Date:</td>
                                        <td>{{ $bankReconciliation->formatted_reconciliation_date }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Period:</td>
                                        <td>{{ $bankReconciliation->formatted_start_date }} - {{ $bankReconciliation->formatted_end_date }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="fw-bold">Created By:</td>
                                        <td>{{ $bankReconciliation->user->name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Created At:</td>
                                        <td>{{ $bankReconciliation->created_at->format('M d, Y H:i') }}</td>
                                    </tr>
                                    @if($bankReconciliation->status === 'pending_approval' && $currentLevel)
                                    <tr>
                                        <td class="fw-bold">Current Approval Level:</td>
                                        <td>
                                            <span class="badge bg-info">{{ $currentLevel->level_name }}</span>
                                        </td>
                                    </tr>
                                    @endif
                                    @if($bankReconciliation->submitted_by)
                                    <tr>
                                        <td class="fw-bold">Submitted By:</td>
                                        <td>{{ $bankReconciliation->submittedBy->name ?? 'N/A' }} 
                                            <small class="text-muted">({{ $bankReconciliation->submitted_at ? $bankReconciliation->submitted_at->format('M d, Y H:i') : 'N/A' }})</small>
                                        </td>
                                    </tr>
                                    @endif
                                    @if($bankReconciliation->approved_by)
                                    <tr>
                                        <td class="fw-bold">Approved By:</td>
                                        <td>{{ $bankReconciliation->approvedBy->name ?? 'N/A' }} 
                                            <small class="text-muted">({{ $bankReconciliation->approved_at ? $bankReconciliation->approved_at->format('M d, Y H:i') : 'N/A' }})</small>
                                        </td>
                                    </tr>
                                    @endif
                                    @if($bankReconciliation->rejected_by)
                                    <tr>
                                        <td class="fw-bold">Rejected By:</td>
                                        <td>
                                            {{ $bankReconciliation->rejectedBy->name ?? 'N/A' }} 
                                            <small class="text-muted">({{ $bankReconciliation->rejected_at ? $bankReconciliation->rejected_at->format('M d, Y H:i') : 'N/A' }})</small>
                                            @if($bankReconciliation->rejection_reason)
                                                <br><small class="text-danger"><strong>Reason:</strong> {{ $bankReconciliation->rejection_reason }}</small>
                                            @endif
                                        </td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                        </div>

                        @if($bankReconciliation->notes)
                        <div class="mt-3">
                            <h6 class="fw-bold">Notes:</h6>
                            <p class="text-muted">{{ $bankReconciliation->notes }}</p>
                        </div>
                        @endif

                        @if($bankReconciliation->bank_statement_notes)
                        <div class="mt-3">
                            <h6 class="fw-bold">Bank Statement Notes:</h6>
                            <p class="text-muted">{{ $bankReconciliation->bank_statement_notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Reconciliation Items -->
                <div class="card radius-10 mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Reconciliation Items</h6>
                        @if($bankReconciliation->status !== 'completed')
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#createReceiptModal">
                                <i class="bx bx-plus me-1"></i>Add Receipt
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#createPaymentModal">
                                <i class="bx bx-minus me-1"></i>Add Payment
                            </button>
                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#createJournalModal">
                                <i class="bx bx-transfer me-1"></i>Add Journal
                            </button>
                        </div>
                        @endif
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="reconciliationTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="unreconciled-tab" data-bs-toggle="tab" data-bs-target="#unreconciled" type="button" role="tab">
                                    Unreconciled Items
                                    <span class="badge bg-warning ms-1">{{ $unreconciledBankItems->count() + $unreconciledBookItems->count() }}</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="reconciled-tab" data-bs-toggle="tab" data-bs-target="#reconciled" type="button" role="tab">
                                    Reconciled Items
                                    <span class="badge bg-success ms-1">{{ $totalReconciledCount ?? $reconciledItems->count() }}</span>
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content mt-3" id="reconciliationTabsContent">
                            <div class="tab-pane fade show active" id="unreconciled" role="tabpanel">
                                <!-- Brought Forward Items Section -->
                                @if($broughtForwardItems->count() > 0)
                                <div class="alert alert-warning mb-3">
                                    <h6 class="fw-bold mb-2">
                                        <i class="bx bx-transfer me-2"></i>Brought Forward Uncleared Items (Prior Month)
                                    </h6>
                                    <p class="mb-2">These items were not cleared in previous months and have been carried forward to this reconciliation.</p>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Type</th>
                                                    <th>Reference</th>
                                                    <th>Description</th>
                                                    <th class="text-end">Amount</th>
                                                    <th>Age</th>
                                                    <th>Origin Month</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($broughtForwardItems as $item)
                                                @php
                                                    $item->calculateAging();
                                                    $agingColor = $item->getAgingFlagColor();
                                                @endphp
                                                <tr style="background-color: #fff3cd;">
                                                    <td>{{ $item->transaction_date->format('d/m/Y') }}</td>
                                                    <td>
                                                        <span class="badge bg-{{ $item->item_type === 'DNC' ? 'success' : 'danger' }}">
                                                            {{ $item->item_type }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $item->reference ?? 'N/A' }}</td>
                                                    <td>{{ $item->description }}</td>
                                                    <td class="text-end">{{ number_format($item->amount, 2) }}</td>
                                                    <td>
                                                        <span class="badge bg-{{ $agingColor }}">
                                                            {{ $item->age_days }} days
                                                            @if($item->age_months >= 1)
                                                                ({{ number_format($item->age_months, 1) }} months)
                                                            @endif
                                                        </span>
                                                    </td>
                                                    <td>{{ $item->origin_month ? $item->origin_month->format('M Y') : 'N/A' }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-warning">
                                                    <td colspan="4" class="text-end fw-bold">Total Brought Forward:</td>
                                                    <td class="text-end fw-bold">{{ number_format($broughtForwardItems->sum('amount'), 2) }}</td>
                                                    <td colspan="2"></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                                @endif

                                <!-- Uncleared Items Summary -->
                                <div class="alert alert-info mb-3">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <strong>DNC Items:</strong> {{ $unclearedItemsSummary['dnc']['count'] ?? 0 }}
                                            @if(($unclearedItemsSummary['dnc']['count'] ?? 0) > 0)
                                                <br><small class="text-muted">Total: {{ number_format($unclearedItemsSummary['dnc']['total_amount'] ?? 0, 2) }}</small>
                                            @endif
                                        </div>
                                        <div class="col-md-3">
                                            <strong>UPC Items:</strong> {{ $unclearedItemsSummary['upc']['count'] ?? 0 }}
                                            @if(($unclearedItemsSummary['upc']['count'] ?? 0) > 0)
                                                <br><small class="text-muted">Total: {{ number_format($unclearedItemsSummary['upc']['total_amount'] ?? 0, 2) }}</small>
                                            @endif
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Brought Forward:</strong> {{ $unclearedItemsSummary['brought_forward']['count'] ?? 0 }}
                                            @if(($unclearedItemsSummary['brought_forward']['count'] ?? 0) > 0)
                                                <br><small class="text-muted">Total: {{ number_format($unclearedItemsSummary['brought_forward']['total_amount'] ?? 0, 2) }}</small>
                                            @endif
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Total Uncleared:</strong> {{ $unclearedItemsSummary['total_uncleared']['count'] ?? 0 }}
                                            @if(($unclearedItemsSummary['total_uncleared']['count'] ?? 0) > 0)
                                                <br><small class="text-muted">Total: {{ number_format($unclearedItemsSummary['total_uncleared']['total_amount'] ?? 0, 2) }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Summary of unreconciled items -->
                                @php
                                    $unreconciledSummary = $bankReconciliation->getUnreconciledSummary();
                                @endphp
                                <div class="alert alert-secondary mb-3">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Bank Statement Items:</strong> {{ $unreconciledSummary['bank_items_count'] }} unreconciled
                                            @if($unreconciledSummary['bank_items_count'] > 0)
                                                <br><small class="text-muted">Total: {{ number_format($unreconciledSummary['bank_items_total'], 2) }}</small>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Book Entry Items:</strong> {{ $unreconciledSummary['book_items_count'] }} unreconciled
                                            @if($unreconciledSummary['book_items_count'] > 0)
                                                <br><small class="text-muted">Total: {{ number_format($unreconciledSummary['book_items_total'], 2) }}</small>
                                            @endif
                                        </div>
                                    </div>
                                    @if($unreconciledSummary['total_unreconciled'] > 0)
                                    <hr class="my-2">
                                    <div class="text-center">
                                        <strong>Total Unreconciled Items: {{ $unreconciledSummary['total_unreconciled'] }}</strong>
                                    </div>
                                    @endif
                                </div>
                                
                                <div class="row">
                                    <!-- Confirmed From Physical Statement (starts empty, filled by confirmations) -->
                                    <div class="col-md-6">
                                        <h6 class="fw-bold text-info">Confirmed From Physical Statement</h6>
                                        <small class="text-muted d-block mb-2">Tick a matching system entry on the right to confirm from your paper statement. Recent confirmations appear here (and also under Reconciled).</small>
                                        <div id="confirmedFromStatement">
                                            @if(($totalReconciledCount ?? 0) > 0)
                                                @foreach($reconciledItems as $item)
                                                <div class="card mb-2 border-success">
                                                    <div class="card-body p-3">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div class="flex-grow-1">
                                                                <h6 class="mb-1">{{ $item->description }}</h6>
                                                                <small class="text-muted">{{ $item->formatted_transaction_date }} - {{ $item->reference }}</small>
                                                                <div class="mt-1">
                                                                    <span class="badge {{ $item->nature === 'debit' ? 'bg-danger' : 'bg-success' }}">{{ strtoupper($item->nature) }}</span>
                                                                    <span class="fw-bold ms-2">{{ $item->formatted_amount }}</span>
                                                                    <span class="badge bg-success ms-2">Reconciled</span>
                                                                </div>
                                                                @if($item->matchedWithItem)
                                                                <small class="text-muted">Matched with: {{ $item->matchedWithItem->description }}</small>
                                                                @endif
                                                            </div>
                                                            <div class="ms-2">
                                                                @if($bankReconciliation->status !== 'completed')
                                                                <form action="{{ route('accounting.bank-reconciliation.unmatch-items', $bankReconciliation) }}" method="POST" class="d-inline unmatch-form">
                                                                    @csrf
                                                                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                                    <button type="button" class="btn btn-sm btn-outline-danger unmatch-swal-btn" data-item-id="{{ $item->id }}">
                                                                        <i class="bx bx-unlink"></i>
                                                                    </button>
                                                                </form>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                            @endif
                                        </div>
                                        @if(($totalReconciledCount ?? 0) === 0)
                                        <div id="noConfirmedPlaceholder" class="text-center py-4">
                                            <i class="bx bx-info-circle font-size-48 text-muted mb-3"></i>
                                            <h6 class="text-muted">No confirmations yet</h6>
                                            <p class="text-muted">Use your physical statement and tick the matching system entry on the right.</p>
                                        </div>
                                        @endif
                                    </div>

                                    <!-- Book Entry Items -->
                                    <div class="col-md-6">
                                        <h6 class="fw-bold text-primary">Book Entry Items <small class="text-muted">(tick to confirm)</small></h6>
                                        <small class="text-muted d-block mb-2">Tick the system entry that matches what you see on your physical statement. It will move left as reconciled. You can reverse it.</small>
                                        @forelse($unreconciledBookItems as $item)
                                        @php
                                            if ($item->uncleared_status === 'UNCLEARED' && $item->origin_date) {
                                                $item->calculateAging();
                                            }
                                            $agingColor = $item->uncleared_status === 'UNCLEARED' && $item->age_days ? $item->getAgingFlagColor() : 'secondary';
                                            $isBroughtForward = $item->is_brought_forward ?? false;
                                        @endphp
                                        <div class="card mb-2 border-primary {{ $isBroughtForward ? 'border-warning' : '' }}" style="{{ $isBroughtForward ? 'background-color: #fff3cd;' : '' }}">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        @if($isBroughtForward)
                                                        <span class="badge bg-warning mb-1">Brought Forward</span>
                                                        @endif
                                                        @if($item->item_type)
                                                        <span class="badge bg-{{ $item->item_type === 'DNC' ? 'success' : 'danger' }} mb-1">{{ $item->item_type }}</span>
                                                        @endif
                                                        <h6 class="mb-1">{{ $item->description }}</h6>
                                                        <small class="text-muted">{{ $item->formatted_transaction_date }} - {{ $item->reference }}</small>
                                                        @if($item->origin_date && $item->origin_date != $item->transaction_date)
                                                        <br><small class="text-muted">Origin: {{ $item->origin_date->format('d/m/Y') }}</small>
                                                        @endif
                                                        <div class="mt-1">
                                                            <span class="badge {{ $item->nature === 'debit' ? 'bg-danger' : 'bg-success' }}">
                                                                {{ strtoupper($item->nature) }}
                                                            </span>
                                                            <span class="fw-bold ms-2">{{ $item->formatted_amount }}</span>
                                                            @if($item->uncleared_status === 'UNCLEARED' && $item->age_days)
                                                            <span class="badge bg-{{ $agingColor }} ms-2">
                                                                {{ $item->age_days }} days
                                                                @if($item->age_months >= 1)
                                                                    ({{ number_format($item->age_months, 1) }}m)
                                                                @endif
                                                            </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="ms-2 form-check">
                                                        <input class="form-check-input book-item-checkbox" type="checkbox" value="{{ $item->id }}" id="book_cb_{{ $item->id }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @empty
                                        <div class="text-center py-4">
                                            <i class="bx bx-check-circle font-size-48 text-success mb-3"></i>
                                            <h6 class="text-success">All book entry items reconciled!</h6>
                                            <p class="text-muted">No unreconciled book entry items found.</p>
                                        </div>
                                        @endforelse
                                    </div>
                                </div>

                                <!-- Match Items Form removed for physical-statement-first workflow -->
                            </div>

                            <div class="tab-pane fade" id="reconciled" role="tabpanel">
                                <!-- Summary of reconciled items -->
                                @if($reconciledItems->count() > 0)
                                <div class="alert alert-success mb-3">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Reconciled Items:</strong> {{ $reconciledItems->count() }} items
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Total Amount:</strong> {{ number_format($reconciledItems->sum('amount'), 2) }}
                                        </div>
                                    </div>
                                </div>
                                @endif
                                
                                @forelse($reconciledItems as $item)
                                @php
                                    $isBroughtForward = $item->is_brought_forward ?? false;
                                    $needsReconciliationInfo = $isBroughtForward && (!$item->clearing_reference || !$item->clearing_date);
                                @endphp
                                <div class="card mb-2 border-success {{ $isBroughtForward ? 'border-warning' : '' }}" style="{{ $isBroughtForward ? 'background-color: #fff3cd;' : '' }}">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                @if($isBroughtForward)
                                                <span class="badge bg-warning mb-1">Brought Forward from Previous Month</span>
                                                @endif
                                                <h6 class="mb-1">{{ $item->description }}</h6>
                                                <small class="text-muted">{{ $item->formatted_transaction_date }} - {{ $item->reference }}</small>
                                                @if($item->origin_date && $item->origin_date != $item->transaction_date)
                                                <br><small class="text-muted">Origin: {{ $item->origin_date->format('d/m/Y') }}</small>
                                                @endif
                                                <div class="mt-1">
                                                    <span class="badge {{ $item->nature === 'debit' ? 'bg-danger' : 'bg-success' }}">
                                                        {{ strtoupper($item->nature) }}
                                                    </span>
                                                    <span class="fw-bold ms-2">{{ $item->formatted_amount }}</span>
                                                    <span class="badge bg-success ms-2">Reconciled</span>
                                                </div>
                                                @if($item->matchedWithItem)
                                                <small class="text-muted">Matched with: {{ $item->matchedWithItem->description }}</small>
                                                @endif
                                                @if($needsReconciliationInfo)
                                                <div class="mt-2">
                                                    <button type="button" class="btn btn-sm btn-warning mark-reconciled-btn" data-item-id="{{ $item->id }}" data-item-description="{{ $item->description }}">
                                                        <i class="bx bx-calendar"></i> Enter Reconciliation Details
                                                    </button>
                                                </div>
                                                @elseif($item->clearing_reference && $item->clearing_date)
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        <i class="bx bx-calendar"></i> Reconciled: {{ $item->clearing_date->format('d/m/Y') }} | 
                                                        <i class="bx bx-receipt"></i> Reference: {{ $item->clearing_reference }}
                                                    </small>
                                                </div>
                                                @endif
                                            </div>
                                            <div class="ms-2">
                                                @if($bankReconciliation->status !== 'completed')
                                                <form action="{{ route('accounting.bank-reconciliation.unmatch-items', $bankReconciliation) }}" method="POST" class="d-inline unmatch-form">
                                                    @csrf
                                                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                    <button type="button" class="btn btn-sm btn-outline-danger unmatch-swal-btn" data-item-id="{{ $item->id }}">
                                                        <i class="bx bx-unlink"></i>
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <div class="text-center py-4">
                                    <i class="bx bx-info-circle font-size-48 text-muted mb-3"></i>
                                    <h6 class="text-muted">No reconciled items yet</h6>
                                    <p class="text-muted mb-0">Start matching unreconciled items to see them here.</p>
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Adjusted Balances -->
            <div class="col-lg-4">
                <div class="card radius-10">
                    <div class="card-header">
                        <h6 class="mb-0">Adjusted Balances</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Adjusted Bank Balance</label>
                            <input type="number" step="0.01" class="form-control" 
                                   value="{{ $bankReconciliation->adjusted_bank_balance }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Adjusted Book Balance</label>
                            <input type="number" step="0.01" class="form-control" 
                                   value="{{ $bankReconciliation->adjusted_book_balance }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Difference</label>
                            <input type="number" step="0.01" class="form-control {{ $bankReconciliation->difference == 0 ? 'border-success' : 'border-danger' }}" 
                                   value="{{ $bankReconciliation->difference }}" readonly>
                        </div>
                        <div class="alert {{ $bankReconciliation->difference == 0 ? 'alert-success' : 'alert-danger' }}">
                            <i class="bx {{ $bankReconciliation->difference == 0 ? 'bx-check-circle' : 'bx-x-circle' }} me-2"></i>
                            {{ $bankReconciliation->difference == 0 ? 'Reconciliation is balanced!' : 'Reconciliation is not balanced.' }}
                        </div>
                        @if($bankReconciliation->bank_statement_document)
                            <div class="mt-3">
                                <a href="{{ asset('storage/' . $bankReconciliation->bank_statement_document) }}" class="btn btn-primary btn-sm w-100" target="_blank">
                                    <i class="bx bx-file-blank me-2"></i>View Bank Statement Document
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Approval History -->
        @if($bankReconciliation->approvalHistories->count() > 0 && auth()->user()->can('view bank reconciliation approval history'))
        <div class="row mt-4">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="bx bx-history me-2"></i>Approval History
                            </h6>
                            @if(auth()->user()->can('view bank reconciliation approval history'))
                            <a href="{{ route('accounting.bank-reconciliation.approval-history', $bankReconciliation) }}" class="btn btn-sm btn-info">
                                <i class="bx bx-show me-1"></i>View Full History
                            </a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Level</th>
                                        <th>Action</th>
                                        <th>Approver</th>
                                        <th>Comments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bankReconciliation->approvalHistories->take(5) as $history)
                                    <tr>
                                        <td>{{ $history->created_at->format('M d, Y H:i') }}</td>
                                        <td>{{ $history->approvalLevel->level_name ?? 'N/A' }}</td>
                                        <td>{!! $history->action_badge !!}</td>
                                        <td>{{ $history->approver->name ?? 'N/A' }}</td>
                                        <td>{{ $history->comments ?? '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Approve Modal -->
@if($bankReconciliation->status === 'pending_approval' && $canApprove && $currentLevel)
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Bank Reconciliation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('accounting.bank-reconciliation.approve', $bankReconciliation) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="approval_level_id" value="{{ $currentLevel->id }}">
                    <div class="mb-3">
                        <label for="approve_comments" class="form-label">Comments (Optional)</label>
                        <textarea class="form-control" id="approve_comments" name="comments" rows="3" placeholder="Add any comments about this approval..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Bank Reconciliation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('accounting.bank-reconciliation.reject', $bankReconciliation) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="approval_level_id" value="{{ $currentLevel->id }}">
                    <div class="mb-3">
                        <label for="reject_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reject_reason" name="reason" rows="3" required placeholder="Please provide a reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Add Bank Statement Item Modal -->
<div class="modal fade" id="addBankStatementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Bank Statement Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('accounting.bank-reconciliation.add-bank-statement-item', $bankReconciliation) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reference" class="form-label">Reference</label>
                        <input type="text" class="form-control" id="reference" name="reference" placeholder="Transaction reference">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <input type="text" class="form-control" id="description" name="description" required placeholder="Transaction description">
                    </div>
                    <div class="mb-3">
                        <label for="transaction_date" class="form-label">Transaction Date</label>
                        <input type="date" class="form-control" id="transaction_date" name="transaction_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount</label>
                        <input type="number" step="0.01" class="form-control" id="amount" name="amount" required placeholder="0.00">
                    </div>
                    <div class="mb-3">
                        <label for="nature" class="form-label">Nature</label>
                        <select class="form-select" id="nature" name="nature" required>
                            <option value="">Select nature</option>
                            <option value="debit">Debit</option>
                            <option value="credit">Credit</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Receipt Voucher Modal -->
<div class="modal fade" id="createReceiptModal" tabindex="-1" aria-labelledby="createReceiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="createReceiptModalLabel">
                    <i class="bx bx-receipt me-2"></i>Create Receipt Voucher
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="receiptVoucherModalForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="bank_account_id" value="{{ $bankReconciliation->bank_account_id }}">
                <input type="hidden" name="reconciliation_id" value="{{ $bankReconciliation->id }}">
                
                <div class="modal-body">
                    <!-- Header Section -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="receipt_date" class="form-label fw-bold">
                                Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="receipt_date" name="date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="receipt_reference" class="form-label fw-bold">Reference Number</label>
                            <input type="text" class="form-control" id="receipt_reference" name="reference" placeholder="Enter reference number">
                        </div>
                    </div>

                    <!-- Bank Account (Pre-filled) -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Bank Account</label>
                            <input type="text" class="form-control" value="{{ $bankReconciliation->bankAccount->name }} - {{ $bankReconciliation->bankAccount->account_number }}" readonly>
                        </div>
                    </div>

                    <!-- Payee Section -->
                    <div class="card border-primary mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0 fw-bold">Payee Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="receipt_payee_type" class="form-label fw-bold">
                                        Payee Type <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select select2-single" id="receipt_payee_type" name="payee_type" required>
                                        <option value="">-- Select Payee Type --</option>
                                        <option value="customer">Customer</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-8" id="receipt_customerSection" style="display: none;">
                                    <label for="receipt_customer_id" class="form-label fw-bold">
                                        Select Customer <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select select2-single" id="receipt_customer_id" name="customer_id">
                                        <option value="">-- Select Customer --</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->customerNo }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-8" id="receipt_otherPayeeSection" style="display: none;">
                                    <label for="receipt_payee_name" class="form-label fw-bold">
                                        Payee Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="receipt_payee_name" name="payee_name" placeholder="Enter payee name">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- WHT & VAT Section -->
                    <div class="card border-info mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0 fw-bold">Withholding Tax & VAT</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="receipt_wht_treatment" class="form-label fw-bold">WHT Treatment</label>
                                    <select class="form-select" id="receipt_wht_treatment" name="wht_treatment">
                                        <option value="EXCLUSIVE" selected>Exclusive</option>
                                        <option value="INCLUSIVE">Inclusive</option>
                                        <option value="NONE">None</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="receipt_wht_rate" class="form-label fw-bold">WHT Rate (%)</label>
                                    <input type="number" class="form-control" id="receipt_wht_rate" name="wht_rate" value="0" step="0.01" min="0" max="100">
                                </div>
                                <div class="col-md-3">
                                    <label for="receipt_vat_mode" class="form-label fw-bold">VAT Mode</label>
                                    <select class="form-select" id="receipt_vat_mode" name="vat_mode">
                                        <option value="EXCLUSIVE" selected>Exclusive</option>
                                        <option value="INCLUSIVE">Inclusive</option>
                                        <option value="NONE">None</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="receipt_vat_rate" class="form-label fw-bold">VAT Rate (%)</label>
                                    <input type="number" class="form-control" id="receipt_vat_rate" name="vat_rate" value="{{ get_default_vat_rate() }}" step="0.01" min="0" max="100">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description and Attachment -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="receipt_description" class="form-label fw-bold">Transaction Description</label>
                            <textarea class="form-control" id="receipt_description" name="description" rows="3" placeholder="Enter transaction description"></textarea>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="receipt_attachment" class="form-label fw-bold">Attachment (Optional)</label>
                            <input type="file" class="form-control" id="receipt_attachment" name="attachment" accept=".pdf">
                            <small class="form-text text-muted">Supported format: PDF only (Max: 2MB)</small>
                        </div>
                    </div>

                    <!-- Line Items Section -->
                    <div class="card border-primary mb-3">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">Line Items</h6>
                            <button type="button" class="btn btn-sm btn-success" id="addReceiptLineBtn">
                                <i class="bx bx-plus me-1"></i>Add Line
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="receiptLineItemsContainer">
                                <!-- Line items will be added here dynamically -->
                            </div>
                            <div class="text-end mt-3">
                                <h5 class="mb-0">
                                    Total Amount: <span class="text-danger" id="receiptTotalAmount">0.00</span>
                                </h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="submitReceiptBtn">
                        <i class="bx bx-save me-2"></i>Create Receipt
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Payment Voucher Modal -->
<div class="modal fade" id="createPaymentModal" tabindex="-1" aria-labelledby="createPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createPaymentModalLabel">
                    <i class="bx bx-receipt me-2"></i>Create Payment Voucher
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="paymentVoucherModalForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="bank_account_id" value="{{ $bankReconciliation->bank_account_id }}">
                <input type="hidden" name="reconciliation_id" value="{{ $bankReconciliation->id }}">
                
                <div class="modal-body">
                    <!-- Header Section -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="payment_date" class="form-label fw-bold">
                                Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="payment_date" name="date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="payment_reference" class="form-label fw-bold">Reference Number</label>
                            <input type="text" class="form-control" id="payment_reference" name="reference" placeholder="Enter reference number">
                        </div>
                    </div>

                    <!-- Bank Account (Pre-filled) -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Bank Account</label>
                            <input type="text" class="form-control" value="{{ $bankReconciliation->bankAccount->name }} - {{ $bankReconciliation->bankAccount->account_number }}" readonly>
                        </div>
                    </div>

                    <!-- Payee Section -->
                    <div class="card border-danger mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0 fw-bold">Payee Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="payment_payee_type" class="form-label fw-bold">
                                        Payee Type <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select select2-single" id="payment_payee_type" name="payee_type" required>
                                        <option value="">-- Select Payee Type --</option>
                                        <option value="customer">Customer</option>
                                        <option value="supplier">Supplier</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-8" id="payment_customerSection" style="display: none;">
                                    <label for="payment_customer_id" class="form-label fw-bold">
                                        Select Customer <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select select2-single" id="payment_customer_id" name="customer_id">
                                        <option value="">-- Select Customer --</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->customerNo }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-8" id="payment_supplierSection" style="display: none;">
                                    <label for="payment_supplier_id" class="form-label fw-bold">
                                        Select Supplier <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select select2-single" id="payment_supplier_id" name="supplier_id">
                                        <option value="">-- Select Supplier --</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-8" id="payment_otherPayeeSection" style="display: none;">
                                    <label for="payment_payee_name" class="form-label fw-bold">
                                        Payee Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="payment_payee_name" name="payee_name" placeholder="Enter payee name">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- WHT & VAT Section -->
                    <div class="card border-info mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0 fw-bold">Withholding Tax & VAT</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="payment_wht_treatment" class="form-label fw-bold">WHT Treatment</label>
                                    <select class="form-select" id="payment_wht_treatment" name="wht_treatment">
                                        <option value="EXCLUSIVE" selected>Exclusive</option>
                                        <option value="INCLUSIVE">Inclusive</option>
                                        <option value="GROSS_UP">Gross-Up</option>
                                        <option value="NONE">None</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="payment_wht_rate" class="form-label fw-bold">WHT Rate (%)</label>
                                    <input type="number" class="form-control" id="payment_wht_rate" name="wht_rate" value="0" step="0.01" min="0" max="100">
                                </div>
                                <div class="col-md-3">
                                    <label for="payment_vat_mode" class="form-label fw-bold">VAT Mode</label>
                                    <select class="form-select" id="payment_vat_mode" name="vat_mode">
                                        <option value="EXCLUSIVE" selected>Exclusive</option>
                                        <option value="INCLUSIVE">Inclusive</option>
                                        <option value="NONE">None</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="payment_vat_rate" class="form-label fw-bold">VAT Rate (%)</label>
                                    <input type="number" class="form-control" id="payment_vat_rate" name="vat_rate" value="{{ get_default_vat_rate() }}" step="0.01" min="0" max="100">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description and Attachment -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="payment_description" class="form-label fw-bold">Transaction Description</label>
                            <textarea class="form-control" id="payment_description" name="description" rows="3" placeholder="Enter transaction description"></textarea>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="payment_attachment" class="form-label fw-bold">Attachment (Optional)</label>
                            <input type="file" class="form-control" id="payment_attachment" name="attachment" accept=".pdf">
                            <small class="form-text text-muted">Supported format: PDF only (Max: 2MB)</small>
                        </div>
                    </div>

                    <!-- Line Items Section -->
                    <div class="card border-danger mb-3">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">Line Items</h6>
                            <button type="button" class="btn btn-sm btn-danger" id="addPaymentLineBtn">
                                <i class="bx bx-plus me-1"></i>Add Line
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="paymentLineItemsContainer">
                                <!-- Line items will be added here dynamically -->
                            </div>
                            <div class="text-end mt-3">
                                <h5 class="mb-0">
                                    Total Amount: <span class="text-danger" id="paymentTotalAmount">0.00</span>
                                </h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="submitPaymentBtn">
                        <i class="bx bx-save me-2"></i>Create Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Journal Entry Modal -->
<div class="modal fade" id="createJournalModal" tabindex="-1" aria-labelledby="createJournalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="createJournalModalLabel">
                    <i class="bx bx-transfer me-2"></i>Create Journal Entry
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="journalModalForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="reconciliation_id" value="{{ $bankReconciliation->id }}">
                
                <div class="modal-body">
                    <!-- Header Section -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="journal_date" class="form-label fw-bold">
                                Entry Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="journal_date" name="date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="journal_attachment" class="form-label fw-bold">Attachment (Optional)</label>
                            <input type="file" class="form-control" id="journal_attachment" name="attachment" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <small class="form-text text-muted">Supported formats: PDF, DOC, DOCX, JPG, JPEG, PNG</small>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="journal_description" class="form-label fw-bold">
                                Description <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="journal_description" name="description" rows="3" placeholder="Enter a detailed description of this journal entry..." required></textarea>
                        </div>
                    </div>

                    <!-- Journal Items Section -->
                    <div class="card border-warning mb-3">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">Journal Entries (Debit / Credit)</h6>
                            <button type="button" class="btn btn-sm btn-warning" id="addJournalEntryBtn">
                                <i class="bx bx-plus me-1"></i>Add Entry
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="journalItemsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 35%;">Account <span class="text-danger">*</span></th>
                                            <th style="width: 15%;">Nature <span class="text-danger">*</span></th>
                                            <th style="width: 20%;">Amount <span class="text-danger">*</span></th>
                                            <th style="width: 25%;">Description</th>
                                            <th style="width: 5%;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="journalItemsContainer">
                                        <!-- Journal entries will be added here dynamically -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="2" class="text-end fw-bold">Total:</td>
                                            <td colspan="3">
                                                <div class="d-flex justify-content-between">
                                                    <span>Debit: <span class="text-success" id="journalDebitTotal">0.00</span></span>
                                                    <span>Credit: <span class="text-danger" id="journalCreditTotal">0.00</span></span>
                                                    <span>Balance: <span class="fw-bold" id="journalBalance">0.00</span></span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" id="submitJournalBtn" style="display: none;">
                        <i class="bx bx-save me-2"></i>Create Journal Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Fix modal scrolling */
    #createReceiptModal .modal-body {
        max-height: calc(100vh - 250px);
        overflow-y: auto;
        padding: 1.5rem;
    }
    
    /* Custom scrollbar for modal */
    #createReceiptModal .modal-body::-webkit-scrollbar {
        width: 8px;
    }
    
    #createReceiptModal .modal-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    #createReceiptModal .modal-body::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }
    
    #createReceiptModal .modal-body::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
    
    /* Ensure modal dialog is properly sized */
    #createReceiptModal .modal-dialog {
        max-height: calc(100vh - 1rem);
        margin: 0.5rem auto;
    }
    
    #createReceiptModal .modal-content {
        max-height: calc(100vh - 1rem);
        display: flex;
        flex-direction: column;
    }
    
    #createReceiptModal .modal-footer {
        flex-shrink: 0;
    }
    
    /* Payment Modal Styles */
    #createPaymentModal .modal-body {
        max-height: calc(100vh - 250px);
        overflow-y: auto;
        padding: 1.5rem;
    }
    
    #createPaymentModal .modal-body::-webkit-scrollbar {
        width: 8px;
    }
    
    #createPaymentModal .modal-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    #createPaymentModal .modal-body::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }
    
    #createPaymentModal .modal-body::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
    
    #createPaymentModal .modal-dialog {
        max-height: calc(100vh - 1rem);
        margin: 0.5rem auto;
    }
    
    #createPaymentModal .modal-content {
        max-height: calc(100vh - 1rem);
        display: flex;
        flex-direction: column;
    }
    
    #createPaymentModal .modal-footer {
        flex-shrink: 0;
    }
    
    /* Journal Modal Styles */
    #createJournalModal .modal-body {
        max-height: calc(100vh - 250px);
        overflow-y: auto;
        padding: 1.5rem;
    }
    
    #createJournalModal .modal-body::-webkit-scrollbar {
        width: 8px;
    }
    
    #createJournalModal .modal-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    #createJournalModal .modal-body::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }
    
    #createJournalModal .modal-body::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
    
    #createJournalModal .modal-dialog {
        max-height: calc(100vh - 1rem);
        margin: 0.5rem auto;
    }
    
    #createJournalModal .modal-content {
        max-height: calc(100vh - 1rem);
        display: flex;
        flex-direction: column;
    }
    
    #createJournalModal .modal-footer {
        flex-shrink: 0;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
let selectedBankItem = null;
let selectedBookItem = null;

function selectForMatching(type, itemId) {
    if (type === 'bank') {
        selectedBankItem = itemId;
        $('#bank_item_id').val(itemId);
    } else {
        selectedBookItem = itemId;
        $('#book_item_id').val(itemId);
    }
}

function refreshBookBalance() {
    const btn = document.getElementById('refreshBookBalanceBtn');
    const originalText = btn.innerHTML;
    
    // Show loading state
    btn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-2"></i>Refreshing...';
    btn.disabled = true;
    
    // Make AJAX request to update book balance
    fetch('{{ route("accounting.bank-reconciliation.update-book-balance", $bankReconciliation) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        // Check if response is ok
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || 'Failed to refresh book balance.');
            }).catch(() => {
                throw new Error(`HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: data.message || 'Book balance updated successfully.',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Error', data.message || 'Failed to refresh book balance.', 'error');
            // Restore button state on error
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', error.message || 'An error occurred while refreshing the book balance.', 'error');
        // Restore button state on error
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}



$(document).ready(function() {
    // Handle click on "Enter Reconciliation Details" button for brought forward items
    $(document).on('click', '.mark-reconciled-btn', function(e){
        e.preventDefault();
        const itemId = $(this).data('item-id');
        const itemDescription = $(this).data('item-description');
        
        Swal.fire({
            title: 'Enter Reconciliation Details',
            html: `
                <form id="reconciliationDetailsForm">
                    <div class="mb-3">
                        <label for="reconciled_date" class="form-label">Reconciliation Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="reconciled_date" name="reconciled_date" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label for="bank_reference" class="form-label">Bank Reference Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="bank_reference" name="bank_reference" required placeholder="Enter bank reference number">
                    </div>
                    <input type="hidden" id="item_id" value="${itemId}">
                </form>
            `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Save',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#28a745',
            didOpen: () => {
                // Focus on first input
                document.getElementById('reconciled_date').focus();
            },
            preConfirm: () => {
                const date = document.getElementById('reconciled_date').value;
                const reference = document.getElementById('bank_reference').value;
                
                if (!date) {
                    Swal.showValidationMessage('Please enter reconciliation date');
                    return false;
                }
                
                if (!reference || !reference.trim()) {
                    Swal.showValidationMessage('Please enter bank reference number');
                    return false;
                }
                
                return {
                    item_id: itemId,
                    reconciled_date: date,
                    bank_reference: reference.trim()
                };
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                // Submit via AJAX
                fetch('{{ route("accounting.bank-reconciliation.mark-previous-month-reconciled", $bankReconciliation) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(result.value)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Reconciliation details saved successfully.',
                            confirmButtonColor: '#28a745'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to save reconciliation details.',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'An error occurred while saving reconciliation details.',
                        confirmButtonColor: '#dc3545'
                    });
                });
            }
        });
    });
    
    // SweetAlert confirm for unmatch in reconciled tab
    $(document).on('click', '.unmatch-swal-btn', function(e){
        e.preventDefault();
        const form = $(this).closest('form.unmatch-form');
        Swal.fire({
            title: 'Unmatch this item?',
            text: 'This will move the items back to Unreconciled.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, unmatch',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                form.trigger('submit');
            }
        });
    });
    // Set default transaction date
    $('#transaction_date').val('{{ date("Y-m-d") }}');

    // When a book item checkbox is ticked, confirm from physical statement via AJAX
    $('.book-item-checkbox').on('change', function(){
        const checkbox = this;
        if (!checkbox.checked) return;

        const bookItemId = $(checkbox).val();
        const $checkbox = $(checkbox);
        
        // Check if this is a brought forward item
        const $itemCard = $checkbox.closest('.card');
        const isBroughtForward = $itemCard.hasClass('border-warning') || $itemCard.find('.badge.bg-warning').length > 0;
        
        if (isBroughtForward) {
            // Show popup form for clearing date and reference
            Swal.fire({
                title: 'Enter Clearing Details',
                html: `
                    <form id="clearingForm">
                        <div class="mb-3">
                            <label for="clearing_date" class="form-label">Clearing Date *</label>
                            <input type="date" class="form-control" id="clearing_date" name="clearing_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="clearing_reference" class="form-label">Bank Statement Reference Number *</label>
                            <input type="text" class="form-control" id="clearing_reference" name="clearing_reference" placeholder="Enter reference from bank statement" required>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: 'Confirm',
                cancelButtonText: 'Cancel',
                didOpen: () => {
                    // Set today's date as default
                    document.getElementById('clearing_date').valueAsDate = new Date();
                },
                preConfirm: () => {
                    const clearingDate = document.getElementById('clearing_date').value;
                    const clearingReference = document.getElementById('clearing_reference').value;
                    
                    if (!clearingDate || !clearingReference) {
                        Swal.showValidationMessage('Please fill in all required fields');
                        return false;
                    }
                    
                    return {
                        clearing_date: clearingDate,
                        clearing_reference: clearingReference
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $checkbox.prop('disabled', true);
                    
                    fetch('{{ route("accounting.bank-reconciliation.confirm-book-item", $bankReconciliation) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            book_item_id: bookItemId,
                            clearing_date: result.value.clearing_date,
                            clearing_reference: result.value.clearing_reference
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (!data.success && !data.already_reconciled) {
                            Swal.fire('Error', data.message || 'Failed to confirm item.', 'error');
                            $checkbox.prop('checked', false).prop('disabled', false);
                            return;
                        }
                        Swal.fire({
                            icon: 'success',
                            title: 'Confirmed',
                            text: 'Item confirmed and marked as cleared.',
                            timer: 1200,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    })
                    .catch(() => {
                        Swal.fire('Network Error', 'Please try again.', 'error');
                        $checkbox.prop('checked', false).prop('disabled', false);
                    });
                } else {
                    // User cancelled, uncheck the checkbox
                    $checkbox.prop('checked', false);
                }
            });
        } else {
            // Regular item confirmation (no popup needed)
            $checkbox.prop('disabled', true);

            fetch('{{ route("accounting.bank-reconciliation.confirm-book-item", $bankReconciliation) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ book_item_id: bookItemId })
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success && !data.already_reconciled) {
                    Swal.fire('Error', data.message || 'Failed to confirm item.', 'error');
                    $checkbox.prop('checked', false).prop('disabled', false);
                    return;
                }
                Swal.fire({
                    icon: 'success',
                    title: 'Confirmed',
                    text: 'Item confirmed from physical statement.',
                    timer: 1200,
                    showConfirmButton: false
                }).then(() => location.reload());
            })
            .catch(() => {
                Swal.fire('Network Error', 'Please try again.', 'error');
                $checkbox.prop('checked', false).prop('disabled', false);
            });
        }
    });
});

// Show success message if exists
@if(session('success'))
    Swal.fire({
        title: 'Success!',
        text: '{{ session('success') }}',
        icon: 'success',
        confirmButtonText: 'OK'
    });
@endif

// Show error message if exists
@if(session('error') || $errors->any())
    Swal.fire({
        title: 'Error!',
        text: '{{ session('error') ?? $errors->first() }}',
        icon: 'error',
        confirmButtonText: 'OK'
    });
@endif

// Function to mark reconciliation as completed with SweetAlert
function markAsCompleted() {
    Swal.fire({
        title: 'Mark as Completed?',
        text: 'Are you sure you want to mark this reconciliation as completed? This action cannot be undone.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Mark as Completed',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Processing...',
                text: 'Marking reconciliation as completed',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Submit the form
            document.getElementById('completeForm').submit();
        }
    });
}

// Receipt Voucher Modal JavaScript
let receiptLineItemCount = 0;

// Handle payee type change
$('#receipt_payee_type').on('change', function() {
    const payeeType = $(this).val();
    if (payeeType === 'customer') {
        $('#receipt_customerSection').show();
        $('#receipt_otherPayeeSection').hide();
        $('#receipt_customer_id').prop('required', true);
        $('#receipt_payee_name').prop('required', false);
    } else if (payeeType === 'other') {
        $('#receipt_customerSection').hide();
        $('#receipt_otherPayeeSection').show();
        $('#receipt_customer_id').prop('required', false);
        $('#receipt_payee_name').prop('required', true);
    } else {
        $('#receipt_customerSection').hide();
        $('#receipt_otherPayeeSection').hide();
        $('#receipt_customer_id').prop('required', false);
        $('#receipt_payee_name').prop('required', false);
    }
});

// Add receipt line item
function addReceiptLineItem() {
    receiptLineItemCount++;
    const lineItemHtml = `
        <div class="receipt-line-item-row border rounded p-3 mb-2" id="receiptLineItem_${receiptLineItemCount}">
            <div class="row">
                <div class="col-md-5 mb-2">
                    <label class="form-label fw-bold">Account <span class="text-danger">*</span></label>
                    <select class="form-select receipt-chart-account-select select2-single" name="line_items[${receiptLineItemCount}][chart_account_id]" required>
                        <option value="">--- Select Account ---</option>
                        @foreach($chartAccounts as $chartAccount)
                            <option value="{{ $chartAccount->id }}">{{ $chartAccount->account_name }} ({{ $chartAccount->account_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <label class="form-label fw-bold">Description</label>
                    <input type="text" class="form-control receipt-description-input" name="line_items[${receiptLineItemCount}][description]" placeholder="Enter description">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label fw-bold">Amount <span class="text-danger">*</span></label>
                    <input type="number" class="form-control receipt-amount-input" name="line_items[${receiptLineItemCount}][amount]" step="0.01" min="0" placeholder="0.00" required>
                </div>
                <div class="col-md-1 mb-2 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-danger btn-sm remove-receipt-line-btn" title="Remove Line">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    $('#receiptLineItemsContainer').append(lineItemHtml);
    
    // Initialize Select2 for the new chart account select
    setTimeout(function() {
        $('#receiptLineItem_' + receiptLineItemCount + ' .receipt-chart-account-select').select2({
            placeholder: 'Select Account',
            allowClear: true,
            width: '100%',
            theme: 'bootstrap-5',
            dropdownParent: $('#createReceiptModal')
        });
    }, 100);
}

// Remove receipt line item
$(document).on('click', '.remove-receipt-line-btn', function() {
    if ($('.receipt-line-item-row').length > 1) {
        $(this).closest('.receipt-line-item-row').remove();
        calculateReceiptTotal();
    } else {
        Swal.fire({
            icon: 'warning',
            title: 'Cannot Remove',
            text: 'At least one line item is required.',
            confirmButtonColor: '#ffc107'
        });
    }
});

// Calculate receipt total
function calculateReceiptTotal() {
    let total = 0;
    $('.receipt-amount-input').each(function() {
        const amount = parseFloat($(this).val()) || 0;
        total += amount;
    });
    $('#receiptTotalAmount').text(total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
}

// Handle amount input changes
$(document).on('input', '.receipt-amount-input', function() {
    calculateReceiptTotal();
});

// Add line item button
$('#addReceiptLineBtn').on('click', function() {
    addReceiptLineItem();
});

// Initialize modal - add first line item when modal opens
$('#createReceiptModal').on('shown.bs.modal', function() {
    // Initialize Select2 for payee type and customer select
    $('#receipt_payee_type').select2({
        placeholder: 'Select Payee Type',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap-5',
        dropdownParent: $('#createReceiptModal')
    });
    
    $('#receipt_customer_id').select2({
        placeholder: 'Select Customer',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap-5',
        dropdownParent: $('#createReceiptModal')
    });
    
    if ($('.receipt-line-item-row').length === 0) {
        addReceiptLineItem();
    }
});

// Reset modal when closed
$('#createReceiptModal').on('hidden.bs.modal', function() {
    // Destroy Select2 instances
    $('#receipt_payee_type').select2('destroy');
    $('#receipt_customer_id').select2('destroy');
    $('.receipt-chart-account-select').select2('destroy');
    
    $('#receiptVoucherModalForm')[0].reset();
    $('#receiptLineItemsContainer').empty();
    receiptLineItemCount = 0;
    $('#receipt_customerSection').hide();
    $('#receipt_otherPayeeSection').hide();
    $('#receiptTotalAmount').text('0.00');
    $('#receipt_date').val('{{ date('Y-m-d') }}');
});

// Handle receipt form submission
$('#receiptVoucherModalForm').on('submit', function(e) {
    e.preventDefault();
    
    // Validate form
    const payeeType = $('#receipt_payee_type').val();
    if (!payeeType) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please select a payee type.',
            confirmButtonColor: '#dc3545'
        });
        return false;
    }
    
    if (payeeType === 'customer' && !$('#receipt_customer_id').val()) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please select a customer.',
            confirmButtonColor: '#dc3545'
        });
        return false;
    }
    
    if (payeeType === 'other' && !$('#receipt_payee_name').val().trim()) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please enter a payee name.',
            confirmButtonColor: '#dc3545'
        });
        return false;
    }
    
    // Check if at least one line item has both account and amount
    let hasValidLineItem = false;
    $('.receipt-line-item-row').each(function() {
        const account = $(this).find('.receipt-chart-account-select').val();
        const amount = parseFloat($(this).find('.receipt-amount-input').val()) || 0;
        if (account && amount > 0) {
            hasValidLineItem = true;
        }
    });
    
    if (!hasValidLineItem) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please add at least one line item with account and amount.',
            confirmButtonColor: '#dc3545'
        });
        return false;
    }
    
    // Disable submit button
    $('#submitReceiptBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-2"></i>Creating...');
    
    // Create FormData for file upload
    const formData = new FormData(this);
    
    // Submit via AJAX
    $.ajax({
        url: '{{ route('accounting.receipt-vouchers.store') }}',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: response.message || 'Receipt voucher created successfully.',
                confirmButtonColor: '#28a745'
            }).then(() => {
                $('#createReceiptModal').modal('hide');
                // Stay on the same page (Bank Reconciliation Details)
                location.reload();
            });
        },
        error: function(xhr) {
            let errorMessage = 'An error occurred while creating the receipt voucher.';
            let icon = 'error';
            
            if (xhr.responseJSON) {
                if (xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMessage = errors.join('<br>');
                }
                
                // Use icon from response if provided
                if (xhr.responseJSON.icon) {
                    icon = xhr.responseJSON.icon;
                }
            }
            
            Swal.fire({
                icon: icon,
                title: icon === 'error' ? 'Error!' : 'Warning!',
                html: errorMessage,
                confirmButtonColor: '#dc3545'
            });
            $('#submitReceiptBtn').prop('disabled', false).html('<i class="bx bx-save me-2"></i>Create Receipt');
        }
    });
});

// ========== PAYMENT VOUCHER MODAL ==========
let paymentLineItemCount = 0;

// Handle payment payee type selection
$('#payment_payee_type').on('change', function() {
    const payeeType = $(this).val();
    $('#payment_customerSection, #payment_supplierSection, #payment_otherPayeeSection').hide();
    $('#payment_customer_id, #payment_supplier_id, #payment_payee_name').prop('required', false).prop('disabled', true);
    
    if (payeeType === 'customer') {
        $('#payment_customerSection').show();
        $('#payment_customer_id').prop('required', true).prop('disabled', false);
    } else if (payeeType === 'supplier') {
        $('#payment_supplierSection').show();
        $('#payment_supplier_id').prop('required', true).prop('disabled', false);
    } else if (payeeType === 'other') {
        $('#payment_otherPayeeSection').show();
        $('#payment_payee_name').prop('required', true).prop('disabled', false);
    }
});

// Initialize payment modal when opened
$('#createPaymentModal').on('shown.bs.modal', function() {
    // Initialize Select2 for payee type, customer, and supplier
    $('#payment_payee_type').select2({
        placeholder: 'Select Payee Type',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap-5',
        dropdownParent: $('#createPaymentModal')
    });
    
    $('#payment_customer_id').select2({
        placeholder: 'Select Customer',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap-5',
        dropdownParent: $('#createPaymentModal')
    });
    
    $('#payment_supplier_id').select2({
        placeholder: 'Select Supplier',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap-5',
        dropdownParent: $('#createPaymentModal')
    });
    
    // Add first line item if none exist
    if ($('.payment-line-item-row').length === 0) {
        addPaymentLineItem();
    }
});

// Add payment line item
function addPaymentLineItem() {
    paymentLineItemCount++;
    const lineItemHtml = `
        <div class="payment-line-item-row mb-3" id="paymentLineItem_${paymentLineItemCount}">
            <div class="row">
                <div class="col-md-5 mb-2">
                    <label class="form-label fw-bold">Account <span class="text-danger">*</span></label>
                    <select class="form-select payment-chart-account-select select2-single" name="line_items[${paymentLineItemCount}][chart_account_id]" required>
                        <option value="">--- Select Account ---</option>
                        @foreach($chartAccounts as $chartAccount)
                            <option value="{{ $chartAccount->id }}">{{ $chartAccount->account_name }} ({{ $chartAccount->account_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <label class="form-label fw-bold">Description</label>
                    <input type="text" class="form-control payment-description-input" name="line_items[${paymentLineItemCount}][description]" placeholder="Enter description">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label fw-bold">Amount <span class="text-danger">*</span></label>
                    <input type="number" class="form-control payment-amount-input" name="line_items[${paymentLineItemCount}][amount]" step="0.01" min="0" placeholder="0.00" required>
                </div>
                <div class="col-md-1 mb-2 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-danger btn-sm remove-payment-line-btn" title="Remove Line">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    $('#paymentLineItemsContainer').append(lineItemHtml);
    
    // Initialize Select2 for the new chart account select
    setTimeout(function() {
        $('#paymentLineItem_' + paymentLineItemCount + ' .payment-chart-account-select').select2({
            placeholder: 'Select Account',
            allowClear: true,
            width: '100%',
            theme: 'bootstrap-5',
            dropdownParent: $('#createPaymentModal')
        });
    }, 100);
}

// Remove payment line item
$(document).on('click', '.remove-payment-line-btn', function() {
    if ($('.payment-line-item-row').length > 1) {
        $(this).closest('.payment-line-item-row').remove();
        calculatePaymentTotal();
    } else {
        Swal.fire({
            icon: 'warning',
            title: 'Cannot Remove',
            text: 'At least one line item is required.',
            confirmButtonColor: '#ffc107'
        });
    }
});

// Calculate payment total
function calculatePaymentTotal() {
    let total = 0;
    $('.payment-amount-input').each(function() {
        const amount = parseFloat($(this).val()) || 0;
        total += amount;
    });
    $('#paymentTotalAmount').text(total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
}

// Update payment total when amount changes
$(document).on('input', '.payment-amount-input', function() {
    calculatePaymentTotal();
});

// Add payment line button
$('#addPaymentLineBtn').on('click', function() {
    addPaymentLineItem();
});

// Reset payment modal when closed
$('#createPaymentModal').on('hidden.bs.modal', function() {
    // Destroy Select2 instances
    $('#payment_payee_type').select2('destroy');
    $('#payment_customer_id').select2('destroy');
    $('#payment_supplier_id').select2('destroy');
    $('.payment-chart-account-select').select2('destroy');
    
    $('#paymentVoucherModalForm')[0].reset();
    $('#paymentLineItemsContainer').empty();
    paymentLineItemCount = 0;
    $('#payment_customerSection').hide();
    $('#payment_supplierSection').hide();
    $('#payment_otherPayeeSection').hide();
    $('#paymentTotalAmount').text('0.00'); // Keep as 0.00 for initial display
    $('#payment_date').val('{{ date('Y-m-d') }}');
});

// Handle payment form submission
$('#paymentVoucherModalForm').on('submit', function(e) {
    e.preventDefault();
    
    // Validate form
    const payeeType = $('#payment_payee_type').val();
    if (!payeeType) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please select a payee type.',
            confirmButtonColor: '#dc3545'
        });
        return false;
    }
    
    if (payeeType === 'customer' && !$('#payment_customer_id').val()) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please select a customer.',
            confirmButtonColor: '#dc3545'
        });
        return false;
    }
    
    if (payeeType === 'supplier' && !$('#payment_supplier_id').val()) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please select a supplier.',
            confirmButtonColor: '#dc3545'
        });
        return false;
    }
    
    if (payeeType === 'other' && !$('#payment_payee_name').val()) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please enter payee name.',
            confirmButtonColor: '#dc3545'
        });
        return false;
    }
    
    // Validate line items
    if ($('.payment-line-item-row').length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please add at least one line item.',
            confirmButtonColor: '#dc3545'
        });
        return false;
    }
    
    // Disable submit button
    $('#submitPaymentBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-2"></i>Creating...');
    
    // Create FormData for file upload
    const formData = new FormData(this);
    
    // Submit via AJAX
    $.ajax({
        url: '{{ route('accounting.payment-vouchers.store') }}',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: response.message || 'Payment voucher created successfully.',
                confirmButtonColor: '#28a745'
            }).then(() => {
                $('#createPaymentModal').modal('hide');
                // Stay on the same page (Bank Reconciliation Details)
                location.reload();
            });
        },
        error: function(xhr) {
            let errorMessage = 'An error occurred while creating the payment voucher.';
            let icon = 'error';
            
            if (xhr.responseJSON) {
                if (xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMessage = errors.join('<br>');
                }
                
                // Use icon from response if provided
                if (xhr.responseJSON.icon) {
                    icon = xhr.responseJSON.icon;
                }
            }
            
            Swal.fire({
                icon: icon,
                title: icon === 'error' ? 'Error!' : 'Warning!',
                html: errorMessage,
                confirmButtonColor: '#dc3545'
            });
            $('#submitPaymentBtn').prop('disabled', false).html('<i class="bx bx-save me-2"></i>Create Payment');
        }
    });
});

// ========== JOURNAL ENTRY MODAL ==========
let journalEntryIndex = 0;

// Add journal entry row
function addJournalEntry() {
    journalEntryIndex++;
    const rowHtml = `
        <tr class="journal-entry-row" id="journalEntry_${journalEntryIndex}">
            <td>
                <select name="items[${journalEntryIndex}][account_id]" class="form-select journal-account-select select2-single" required>
                    <option value="">-- Select Account --</option>
                    @foreach($chartAccounts as $chartAccount)
                        <option value="{{ $chartAccount->id }}">{{ $chartAccount->account_code }} - {{ $chartAccount->account_name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select name="items[${journalEntryIndex}][nature]" class="form-select journal-nature-select" required>
                    <option value="debit">Debit</option>
                    <option value="credit">Credit</option>
                </select>
            </td>
            <td>
                <input type="number" step="0.01" name="items[${journalEntryIndex}][amount]" 
                       class="form-control journal-amount-input" placeholder="0.00" min="0.01" required>
            </td>
            <td>
                <input type="text" name="items[${journalEntryIndex}][description]" 
                       class="form-control journal-description-input" placeholder="Optional description">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-journal-entry-btn" title="Remove Entry">
                    <i class="bx bx-trash"></i>
                </button>
            </td>
        </tr>
    `;
    $('#journalItemsContainer').append(rowHtml);
    
    // Initialize Select2 for the new account select
    setTimeout(function() {
        const $select = $('#journalEntry_' + journalEntryIndex + ' .journal-account-select');
        if ($select.length && !$select.hasClass('select2-hidden-accessible')) {
            $select.select2({
                placeholder: 'Select Account',
                allowClear: true,
                width: '100%',
                theme: 'bootstrap-5',
                dropdownParent: $('#createJournalModal')
            });
        }
    }, 150);
}

// Remove journal entry row
$(document).on('click', '.remove-journal-entry-btn', function() {
    if ($('.journal-entry-row').length > 1) {
        $(this).closest('.journal-entry-row').remove();
        calculateJournalTotals();
    } else {
        Swal.fire({
            icon: 'warning',
            title: 'Cannot Remove',
            text: 'At least one journal entry is required.',
            confirmButtonColor: '#ffc107'
        });
    }
});

// Calculate journal totals
function calculateJournalTotals() {
    let debitTotal = 0;
    let creditTotal = 0;
    
    $('.journal-entry-row').each(function() {
        const nature = $(this).find('.journal-nature-select').val();
        const amount = parseFloat($(this).find('.journal-amount-input').val()) || 0;
        
        if (nature === 'debit') {
            debitTotal += amount;
        } else {
            creditTotal += amount;
        }
    });
    
    const balance = debitTotal - creditTotal;
    
    $('#journalDebitTotal').text(debitTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $('#journalCreditTotal').text(creditTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    
    // Highlight balance if not zero
    const balanceElement = $('#journalBalance');
    balanceElement.text(balance.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    
    // Show/hide submit button based on balance (similar to normal journal form)
    // Use Math.abs() to handle floating point precision issues
    if (Math.abs(balance) < 0.01 && $('.journal-entry-row').length >= 2) {
        balanceElement.removeClass('text-danger text-warning').addClass('text-success');
        $('#submitJournalBtn').show();
        console.log('Journal entry is balanced - showing submit button');
    } else {
        if (balance > 0) {
            balanceElement.removeClass('text-success text-danger').addClass('text-warning');
        } else {
            balanceElement.removeClass('text-success text-warning').addClass('text-danger');
        }
        $('#submitJournalBtn').hide();
        console.log('Journal entry is not balanced - hiding submit button');
    }
}

// Update totals when amount or nature changes
$(document).on('input change', '.journal-amount-input, .journal-nature-select', function() {
    calculateJournalTotals();
});

// Add journal entry button
$('#addJournalEntryBtn').on('click', function() {
    addJournalEntry();
});

// Initialize journal modal when opened
$('#createJournalModal').on('shown.bs.modal', function() {
    // Hide submit button initially
    $('#submitJournalBtn').hide();
    
    // Add first entry if none exist
    if ($('.journal-entry-row').length === 0) {
        addJournalEntry();
        // Wait a bit before adding the second entry to ensure Select2 initializes properly
        setTimeout(function() {
            addJournalEntry();
            // Ensure all Select2 instances are initialized
            setTimeout(function() {
                $('.journal-account-select').each(function() {
                    if (!$(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2({
                            placeholder: 'Select Account',
                            allowClear: true,
                            width: '100%',
                            theme: 'bootstrap-5',
                            dropdownParent: $('#createJournalModal')
                        });
                    }
                });
            }, 100);
        }, 200);
    } else {
        // Re-initialize Select2 for existing entries
        setTimeout(function() {
            $('.journal-account-select').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        placeholder: 'Select Account',
                        allowClear: true,
                        width: '100%',
                        theme: 'bootstrap-5',
                        dropdownParent: $('#createJournalModal')
                    });
                }
            });
        }, 150);
    }
});

// Reset journal modal when closed
$('#createJournalModal').on('hidden.bs.modal', function() {
    // Destroy Select2 instances
    $('.journal-account-select').select2('destroy');
    
    $('#journalModalForm')[0].reset();
    $('#journalItemsContainer').empty();
    journalEntryIndex = 0;
    $('#journalDebitTotal').text('0.00');
    $('#journalCreditTotal').text('0.00');
    $('#journalBalance').text('0.00').removeClass('text-danger text-success text-warning');
    $('#journal_date').val('{{ date('Y-m-d') }}');
    $('#submitJournalBtn').hide(); // Hide submit button when modal is closed
});

// Hide submit button when modal is opened
$('#createJournalModal').on('show.bs.modal', function() {
    $('#submitJournalBtn').hide();
});

// Handle journal form submission
$('#journalModalForm').on('submit', function(e) {
    e.preventDefault();
    
    // Validate form
    if ($('.journal-entry-row').length < 2) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please add at least two journal entries (one debit and one credit).',
            confirmButtonColor: '#dc3545'
        });
        return false;
    }
    
    // Validate that debits equal credits
    let debitTotal = 0;
    let creditTotal = 0;
    
    $('.journal-entry-row').each(function() {
        const nature = $(this).find('.journal-nature-select').val();
        const amount = parseFloat($(this).find('.journal-amount-input').val()) || 0;
        
        if (nature === 'debit') {
            debitTotal += amount;
        } else {
            creditTotal += amount;
        }
    });
    
    if (debitTotal !== creditTotal) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            html: `Debits (${debitTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}) must equal Credits (${creditTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}).<br>The difference is: ${Math.abs(debitTotal - creditTotal).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
            confirmButtonColor: '#dc3545'
        });
        return false;
    }
    
    // Disable submit button
    $('#submitJournalBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-2"></i>Creating...');
    
    // Create FormData for file upload
    const formData = new FormData(this);
    
    // Submit via AJAX
    $.ajax({
        url: '{{ route('accounting.journals.store') }}',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: response.message || 'Journal entry created successfully.',
                confirmButtonColor: '#28a745'
            }).then(() => {
                $('#createJournalModal').modal('hide');
                // Stay on the same page (Bank Reconciliation Details)
                location.reload();
            });
        },
        error: function(xhr) {
            let errorMessage = 'An error occurred while creating the journal entry.';
            let icon = 'error';
            
            if (xhr.responseJSON) {
                if (xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMessage = errors.join('<br>');
                }
                
                // Use icon from response if provided
                if (xhr.responseJSON.icon) {
                    icon = xhr.responseJSON.icon;
                }
            }
            
            Swal.fire({
                icon: icon,
                title: icon === 'error' ? 'Error!' : 'Warning!',
                html: errorMessage,
                confirmButtonColor: '#dc3545'
            });
            $('#submitJournalBtn').prop('disabled', false).html('<i class="bx bx-save me-2"></i>Create Journal Entry');
        }
    });
});
</script>
@endpush 