@extends('layouts.main')

@section('title', 'Accrual Schedule Details')

@section('content')
@php
    $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', auth()->user()->company->functional_currency ?? 'TZS');
    $scheduleCurrency = $schedule->currency_code ?? 'TZS';
@endphp
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Management', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Accruals & Prepayments', 'url' => route('accounting.accruals-prepayments.index'), 'icon' => 'bx bx-time-five'],
            ['label' => 'Schedule Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Accrual Schedule Details</h4>
                    <div class="page-title-right">
                @if($schedule->canBeEdited())
                        <a href="{{ route('accounting.accruals-prepayments.edit', $schedule->encoded_id) }}" class="btn btn-primary me-1">
                            <i class="bx bx-edit me-1"></i>Edit Schedule
                    </a>
                @endif
                @if($schedule->status === 'draft')
                        <form action="{{ route('accounting.accruals-prepayments.submit', $schedule->encoded_id) }}" method="POST" class="d-inline me-1" id="submit-for-approval-form">
                        @csrf
                            <button type="button" class="btn btn-info" id="submit-for-approval-btn">
                            <i class="bx bx-send me-1"></i>Submit for Approval
                        </button>
                    </form>
                @endif
                @if($schedule->canBeApproved() && ($approvalLevelsConfigured ? $canApprove : true))
                        <form action="{{ route('accounting.accruals-prepayments.approve', $schedule->encoded_id) }}" method="POST" class="d-inline me-1" id="approve-form">
                        @csrf
                            <input type="hidden" name="comments" id="approve-comments" value="">
                            <button type="button" class="btn btn-success" id="approve-btn">
                            <i class="bx bx-check me-1"></i>Approve
                        </button>
                    </form>
                        <form action="{{ route('accounting.accruals-prepayments.reject', $schedule->encoded_id) }}" method="POST" class="d-inline me-1" id="reject-form">
                        @csrf
                            <input type="hidden" name="reason" id="reject-reason" value="">
                            <button type="button" class="btn btn-danger" id="reject-btn">
                            <i class="bx bx-x me-1"></i>Reject
                        </button>
                    </form>
                @endif
                @if($schedule->status === 'active')
                        <form action="{{ route('accounting.accruals-prepayments.post-all-pending', $schedule->encoded_id) }}" method="POST" class="d-inline me-1" id="post-all-pending-form">
                        @csrf
                            <button type="button" class="btn btn-warning" id="post-all-pending-btn">
                            <i class="bx bx-check-double me-1"></i>Post All Pending
                        </button>
                    </form>
                @endif
                        <a href="{{ route('accounting.accruals-prepayments.export-pdf', $schedule->encoded_id) }}" class="btn btn-info me-1" target="_blank">
                            <i class="bx bx-download me-1"></i>Export PDF
                        </a>
                        <a href="{{ route('accounting.accruals-prepayments.export-excel', $schedule->encoded_id) }}" class="btn btn-info me-1">
                            <i class="bx bx-spreadsheet me-1"></i>Export Excel
                        </a>
                        <a href="{{ route('accounting.accruals-prepayments.index') }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back to Schedules
                        </a>
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

        @if(isset($approvalLevelsConfigured) && $approvalLevelsConfigured && $schedule->status === 'submitted' && !$canApprove)
            <div class="alert alert-info border-0">
                <i class="bx bx-info-circle me-2"></i>
                <strong>Pending approval</strong>
                @if(isset($currentApprovalLevel) && $currentApprovalLevel)
                    at <strong>Level {{ $currentApprovalLevel->level }}</strong> ({{ $currentApprovalLevel->level_name }}).
                @endif
                @if(isset($pendingApprovers) && $pendingApprovers->count() > 0)
                    <div class="mt-1 text-muted small">
                        Approvers: {{ $pendingApprovers->implode(', ') }}
                    </div>
                @endif
            </div>
        @endif

        <!-- Schedule Header -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                                <h5 class="text-primary mb-3">Schedule Information</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="150"><strong>Schedule Number:</strong></td>
                                        <td>{{ $schedule->schedule_number }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>
                                            {!! $schedule->status_badge !!}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Schedule Type:</strong></td>
                                        <td>
                                            <span class="badge bg-info">{{ ucfirst($schedule->schedule_type) }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Nature:</strong></td>
                                        <td>
                                            <span class="badge bg-secondary">{{ ucfirst($schedule->nature) }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Category:</strong></td>
                                        <td>{{ $schedule->category_name }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Start Date:</strong></td>
                                        <td>{{ $schedule->start_date ? $schedule->start_date->format('d M Y') : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>End Date:</strong></td>
                                        <td>{{ $schedule->end_date ? $schedule->end_date->format('d M Y') : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Frequency:</strong></td>
                                        <td>{{ ucfirst($schedule->frequency) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Currency:</strong></td>
                                        <td><span class="badge bg-info">{{ $scheduleCurrency }}</span></td>
                                    </tr>
                                    @if($schedule->fx_rate_at_creation && $schedule->fx_rate_at_creation != 1)
                                    <tr>
                                        <td><strong>Exchange Rate:</strong></td>
                                        <td>1 {{ $scheduleCurrency }} = {{ number_format($schedule->fx_rate_at_creation, 6) }} {{ $functionalCurrency }}</td>
                                    </tr>
                                    @endif
                                    @if($schedule->schedule_type === 'prepayment' && $schedule->payment_method)
                                    <tr>
                                        <td><strong>Payment Method:</strong></td>
                                        <td>
                                            <span class="badge bg-info">{{ ucfirst($schedule->payment_method) }}</span>
                                            @if($schedule->payment_method === 'bank' && $schedule->bankAccount)
                                                <br><small class="text-muted">{{ $schedule->bankAccount->name }} - {{ $schedule->bankAccount->account_number }}</small>
                                            @endif
                                        </td>
                                    </tr>
                                    @if($schedule->payment_date)
                                    <tr>
                                        <td><strong>Payment Date:</strong></td>
                                        <td>{{ $schedule->payment_date->format('d M Y') }}</td>
                                    </tr>
                                    @endif
                                    @if($schedule->initialJournal)
                                    <tr>
                                        <td><strong>Initial Journal:</strong></td>
                                        <td>
                                            <a href="{{ route('accounting.journals.show', $schedule->initialJournal->id) }}" class="text-primary fw-bold">
                                                {{ $schedule->initialJournal->journal_number }}
                                            </a>
                                            <br><small class="text-muted">{{ $schedule->initialJournal->description }}</small>
                                        </td>
                                    </tr>
                                    @endif
                                    @endif
                                </table>
                            </div>
                    <div class="col-md-6">
                                <h5 class="text-primary mb-3">Account & Party Information</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="150"><strong>P&L Account:</strong></td>
                                        <td>
                                            <strong>{{ $schedule->expenseIncomeAccount->account_code }}</strong><br>
                                            <small class="text-muted">{{ $schedule->expenseIncomeAccount->account_name }}</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Balance Sheet Account:</strong></td>
                                        <td>
                                            <strong>{{ $schedule->balanceSheetAccount->account_code }}</strong><br>
                                            <small class="text-muted">{{ $schedule->balanceSheetAccount->account_name }}</small>
                                        </td>
                                    </tr>
                                    @if($schedule->vendor)
                                    <tr>
                                        <td><strong>Vendor:</strong></td>
                                        <td>{{ $schedule->vendor->name }}</td>
                                    </tr>
                                    @endif
                                    @if($schedule->customer)
                                    <tr>
                                        <td><strong>Customer:</strong></td>
                                        <td>{{ $schedule->customer->name }}</td>
                                    </tr>
                                    @endif
                                    @if($schedule->branch)
                                    <tr>
                                        <td><strong>Branch:</strong></td>
                                        <td>{{ $schedule->branch->name }}</td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td><strong>Prepared By:</strong></td>
                                        <td>{{ $schedule->preparedBy->name ?? 'N/A' }}</td>
                                    </tr>
                                    @if($schedule->approvedBy)
                                    <tr>
                                        <td><strong>Approved By:</strong></td>
                                        <td>{{ $schedule->approvedBy->name }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Approved At:</strong></td>
                                        <td>{{ $schedule->approved_at ? $schedule->approved_at->format('d M Y H:i') : 'N/A' }}</td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs for Amortisation Schedule and Journal Entries -->
        <div class="row">
            <div class="col-12">
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="amortisation-tab" data-bs-toggle="tab" data-bs-target="#amortisation" type="button" role="tab">
                            <i class="bx bx-calendar me-1"></i>Amortisation Schedule
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="journals-tab" data-bs-toggle="tab" data-bs-target="#journals" type="button" role="tab">
                            <i class="bx bx-book me-1"></i>Journal Entries
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="approvals-tab" data-bs-toggle="tab" data-bs-target="#approvals" type="button" role="tab">
                            <i class="bx bx-check-shield me-1"></i>Approvals
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
            <!-- Amortisation Schedule Tab -->
                    <div class="tab-pane fade show active" id="amortisation" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bx bx-calendar me-2"></i>Amortisation Schedule
                                </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                    <tr>
                                        <th>Period</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th class="text-end">Days</th>
                                                <th class="text-end">Amount ({{ $scheduleCurrency }})</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($amortisationSchedule as $period)
                                    <tr>
                                                <td><strong>{{ $period['period'] }}</strong></td>
                                                <td>{{ $period['period_start_date']->format('d M Y') }}</td>
                                                <td>{{ $period['period_end_date']->format('d M Y') }}</td>
                                        <td class="text-end">{{ $period['days_in_period'] }}</td>
                                                <td class="text-end"><strong>{{ number_format($period['amortisation_amount'], 2) }}</strong></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                        <tfoot>
                                            <tr class="table-info">
                                                <th colspan="4" class="text-end">Total:</th>
                                                <th class="text-end">{{ number_format($schedule->total_amount, 2) }} {{ $scheduleCurrency }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

                    <!-- Journal Entries Tab -->
            <div class="tab-pane fade" id="journals" role="tabpanel">
                        @if($schedule->journals->count() > 0)
                <div class="card">
                    <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bx bx-book me-2"></i>Journal Entries
                                </h5>
                                <small class="text-muted">All amounts are displayed in schedule currency ({{ $scheduleCurrency }})</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                    <tr>
                                        <th>Period</th>
                                        <th>Narration</th>
                                                <th class="text-end">Amount ({{ $scheduleCurrency }})</th>
                                        <th>FX Rate</th>
                                                <th class="text-end">Home Currency ({{ $functionalCurrency }})</th>
                                        <th>Journal #</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                            @foreach($schedule->journals as $journal)
                                    <tr>
                                        <td>{{ $journal->period }}</td>
                                        <td>{{ $journal->narration }}</td>
                                                <td class="text-end">{{ number_format($journal->amortisation_amount, 2) }}</td>
                                        <td>{{ number_format($journal->fx_rate, 6) }}</td>
                                        <td class="text-end">{{ number_format($journal->home_currency_amount, 2) }}</td>
                                        <td>
                                            @if($journal->journal)
                                                        <a href="{{ route('accounting.journals.show', $journal->journal->id) }}" class="text-primary fw-bold">
                                                            {{ $journal->journal->reference }}
                                                </a>
                                            @else
                                                <span class="text-muted">Not posted</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge 
                                                @if($journal->status == 'posted') bg-success
                                                @elseif($journal->status == 'reversed') bg-warning
                                                @elseif($journal->status == 'cancelled') bg-danger
                                                @else bg-secondary
                                                @endif">
                                                {{ ucfirst($journal->status) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($journal->status === 'pending' && $schedule->status === 'active')
                                                        <form action="{{ route('accounting.accruals-prepayments.post-journal', [$schedule->encoded_id, $journal->id]) }}" method="POST" class="d-inline post-journal-form" data-journal-id="{{ $journal->id }}" data-period="{{ $journal->period }}" data-amount="{{ number_format($journal->home_currency_amount, 2) }}">
                                                    @csrf
                                                            <button type="button" class="btn btn-sm btn-success post-journal-btn">
                                                                <i class="bx bx-check me-1"></i>Post
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-info">
                                                <th colspan="2">Total Journals:</th>
                                                <th class="text-end">{{ $scheduleCurrency }} {{ number_format($schedule->journals->sum('amortisation_amount'), 2) }}</th>
                                                <th></th>
                                                <th class="text-end">{{ $functionalCurrency }} {{ number_format($schedule->journals->sum('home_currency_amount'), 2) }}</th>
                                                <th colspan="3"></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="card">
                            <div class="card-body text-center text-muted py-5">
                                <i class="bx bx-book-open fs-1 mb-3"></i>
                                <p class="mb-0">No journal entries generated yet</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes & Summary -->
        <div class="row">
            <div class="col-md-8">
                @if($schedule->description || $schedule->notes)
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-note me-2"></i>Description & Notes
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($schedule->description)
                        <div class="mb-3">
                            <h6>Description:</h6>
                            <p class="mb-0">{{ $schedule->description }}</p>
                        </div>
                        @endif
                        @if($schedule->notes)
                        <div>
                            <h6>Notes:</h6>
                            <p class="mb-0">{{ $schedule->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-calculator me-2"></i>Schedule Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Amount:</span>
                            <span><strong>{{ $scheduleCurrency }} {{ number_format($schedule->total_amount, 2) }}</strong></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Amortised Amount:</span>
                            <span class="text-success">{{ $scheduleCurrency }} {{ number_format($schedule->amortised_amount, 2) }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold text-primary">
                            <span>Remaining Amount:</span>
                            <span>{{ $scheduleCurrency }} {{ number_format($schedule->remaining_amount, 2) }}</span>
                        </div>

                        @if($schedule->fx_rate_at_creation && $schedule->fx_rate_at_creation != 1)
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Home Currency Total:</span>
                            <span class="text-muted small"><strong>{{ $functionalCurrency }} {{ number_format($schedule->home_currency_amount ?? ($schedule->total_amount * $schedule->fx_rate_at_creation), 2) }}</strong></span>
                        </div>
                        @endif

                        <!-- Amortisation Progress Bar -->
                        @php
                            $amortisationPercentage = $schedule->total_amount > 0 ? ($schedule->amortised_amount / $schedule->total_amount) * 100 : 0;
                            $amortisationPercentage = round($amortisationPercentage, 1);
                        @endphp
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small">Amortisation Progress</span>
                                <span class="text-muted small fw-bold">{{ $amortisationPercentage }}%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar
                                    @if($amortisationPercentage >= 100) bg-success
                                    @elseif($amortisationPercentage >= 75) bg-info
                                    @elseif($amortisationPercentage >= 50) bg-warning
                                    @else bg-danger
                                    @endif"
                                    role="progressbar"
                                    style="width: {{ $amortisationPercentage }}%"
                                    aria-valuenow="{{ $amortisationPercentage }}"
                                    aria-valuemin="0"
                                    aria-valuemax="100">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">{{ $scheduleCurrency }} {{ number_format($schedule->amortised_amount, 2) }}</small>
                                <small class="text-muted">{{ $scheduleCurrency }} {{ number_format($schedule->total_amount, 2) }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Audit Information -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-history me-2"></i>Audit Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Action</th>
                                        <th>User</th>
                                        <th>Date & Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Created</td>
                                        <td>{{ $schedule->createdBy->name ?? 'N/A' }}</td>
                                        <td>{{ $schedule->created_at->format('d M Y H:i:s') }}</td>
                                    </tr>
                                    @if($schedule->preparedBy)
                                    <tr>
                                        <td>Prepared</td>
                                        <td>{{ $schedule->preparedBy->name }}</td>
                                        <td>{{ $schedule->created_at->format('d M Y H:i:s') }}</td>
                                    </tr>
                                    @endif
                                    @if($schedule->approvedBy)
                                    <tr>
                                        <td>Approved</td>
                                        <td>{{ $schedule->approvedBy->name }}</td>
                                        <td>{{ $schedule->approved_at ? $schedule->approved_at->format('d M Y H:i:s') : 'N/A' }}</td>
                                    </tr>
                                    @endif
                                    @if($schedule->updated_at != $schedule->created_at)
                                    <tr>
                                        <td>Last Updated</td>
                                        <td>{{ $schedule->updatedBy->name ?? 'N/A' }}</td>
                                        <td>{{ $schedule->updated_at->format('d M Y H:i:s') }}</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schedule Actions -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-cog me-2"></i>Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex gap-2">
                            @if($schedule->canBeCancelled())
                            <button type="button" class="btn btn-danger" onclick="deleteSchedule()">
                                <i class="bx bx-trash me-1"></i>Delete Schedule
                            </button>
                            @endif
                        </div>
                    </div>
                </div>

                    <!-- Approvals Tab -->
                    <div class="tab-pane fade" id="approvals" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bx bx-check-shield me-2"></i>Approval History
                                </h5>
                                <small class="text-muted">
                                    Current round: <strong>{{ $schedule->approval_round ?? 1 }}</strong>
                                </small>
                            </div>
                            <div class="card-body">
                                @if(isset($approvalLevelsConfigured) && $approvalLevelsConfigured && $schedule->status === 'submitted')
                                    <div class="alert alert-info border-0">
                                        <i class="bx bx-info-circle me-2"></i>
                                        @if(isset($currentApprovalLevel) && $currentApprovalLevel)
                                            Pending at <strong>Level {{ $currentApprovalLevel->level }}</strong> ({{ $currentApprovalLevel->level_name }}).
                                        @else
                                            Pending approvals are complete for all configured levels.
                                        @endif
                                    </div>
                                @endif

                                @if(!isset($approvalHistory) || $approvalHistory->count() === 0)
                                    <div class="text-muted">
                                        No approval history yet.
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th width="90">Round</th>
                                                    <th width="90">Level</th>
                                                    <th>Approver</th>
                                                    <th width="120">Status</th>
                                                    <th width="180">Date</th>
                                                    <th>Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($approvalHistory as $row)
                                                    <tr class="{{ ($row->approval_round ?? 1) == ($schedule->approval_round ?? 1) ? '' : 'table-secondary' }}">
                                                        <td>
                                                            <span class="badge bg-secondary">{{ $row->approval_round ?? 1 }}</span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-primary">L{{ $row->approval_level }}</span>
                                                        </td>
                                                        <td>{{ $row->approver->name ?? ('User #' . $row->approver_id) }}</td>
                                                        <td>
                                                            @if($row->status === 'approved')
                                                                <span class="badge bg-success">Approved</span>
                                                            @elseif($row->status === 'rejected')
                                                                <span class="badge bg-danger">Rejected</span>
                                                            @else
                                                                <span class="badge bg-warning">Pending</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @php
                                                                $dt = $row->approved_at ?? $row->rejected_at ?? $row->created_at;
                                                            @endphp
                                                            {{ $dt ? \Carbon\Carbon::parse($dt)->format('d M Y H:i') : 'N/A' }}
                                                        </td>
                                                        <td>
                                                            @if($row->status === 'rejected')
                                                                {{ $row->rejection_reason ?? $row->comments ?? 'Rejected' }}
                                                            @else
                                                                {{ $row->comments ?? '-' }}
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        <small class="text-muted d-block mt-2">
                                            Rows shaded in gray are from older rounds (previous submissions).
                                        </small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Submit for Approval Confirmation
    $('#submit-for-approval-btn').on('click', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Submit for Approval?',
            text: 'Are you sure you want to submit this schedule for approval? Once submitted, it will require approval before it can be activated.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0dcaf0',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Submit for Approval',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $('#submit-for-approval-form').submit();
            }
        });
    });

    // Approve Confirmation
    $('#approve-btn').on('click', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Approve Schedule?',
            input: 'textarea',
            inputLabel: 'Comment (optional)',
            inputPlaceholder: 'Add a short comment (optional)...',
            text: 'Once approved, it will be activated and journals can be posted.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Approve',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $('#approve-comments').val(result.value || '');
                $('#approve-form').submit();
            }
        });
    });

    // Reject Confirmation
    $('#reject-btn').on('click', function(e) {
        e.preventDefault();

        Swal.fire({
            title: 'Reject Schedule?',
            input: 'textarea',
            inputLabel: 'Reason (optional)',
            inputPlaceholder: 'Add a short reason for rejection (optional)...',
            text: 'This schedule will be returned to draft status.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Reject',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $('#reject-reason').val(result.value || '');
                $('#reject-form').submit();
            }
        });
    });

    // Post All Pending Journals Confirmation
    $('#post-all-pending-btn').on('click', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Post All Pending Journals?',
            text: 'This will post all pending journals to the General Ledger. This action cannot be undone.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Post All',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $('#post-all-pending-form').submit();
            }
        });
    });

    // Post Individual Journal Confirmation
    $(document).on('click', '.post-journal-btn', function(e) {
        e.preventDefault();
        
        const form = $(this).closest('.post-journal-form');
        const period = form.data('period');
        const amount = form.data('amount');
        const functionalCurrency = '{{ $functionalCurrency }}';
        
        Swal.fire({
            title: 'Post Journal to GL?',
            html: `Are you sure you want to post this journal to the General Ledger?<br><br>
                   <strong>Period:</strong> ${period}<br>
                   <strong>Amount:</strong> ${functionalCurrency} ${amount}<br><br>
                   This will create GL transactions and cannot be undone.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Post to GL',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});

function deleteSchedule() {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this! This will permanently delete the schedule and all associated data.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("accounting.accruals-prepayments.destroy", $schedule->encoded_id) }}',
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
                        window.location.href = '{{ route("accounting.accruals-prepayments.index") }}';
                    });
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred while deleting the schedule.';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
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
}
</script>
@endpush
@endsection
