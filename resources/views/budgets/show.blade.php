@extends('layouts.main')

@section('title', __('app.budget_details'))

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => __('app.budgets'), 'url' => route('accounting.budgets.index'), 'icon' => 'bx bx-chart'],
            ['label' => $budget->name, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <h6 class="mb-0 text-uppercase">{{ __('app.budget_details') }}</h6>
        <hr />

        <!-- Budget Information -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">{{ __('app.budget') }} {{ __('app.info') }}</h5>
                            <div class="btn-group">
                                <a href="{{ route('accounting.budgets.reallocate', $budget) }}" class="btn btn-info btn-sm">
                                    <i class="bx bx-transfer"></i> Reallocate
                                </a>
                                <a href="{{ route('accounting.budgets.export-excel', $budget) }}" class="btn btn-success btn-sm">
                                    <i class="bx bx-export"></i> Excel
                                </a>
                                <a href="{{ route('accounting.budgets.export-pdf', $budget) }}" class="btn btn-danger btn-sm">
                                    <i class="bx bx-file-pdf"></i> PDF
                                </a>
                                <a href="{{ route('accounting.budgets.edit', $budget) }}" class="btn btn-warning btn-sm">
                                    <i class="bx bx-edit"></i> {{ __('app.edit') }}
                                </a>
                                <a href="{{ route('accounting.budgets.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="bx bx-arrow-back"></i> {{ __('app.back') }}
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- Approval Status Badge -->
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Approval Status:</strong>
                                @if($budget->status === 'pending_approval')
                                    <span class="badge bg-warning">
                                        Pending Approval - Level {{ $budget->current_approval_level }}
                                    </span>
                                @elseif($budget->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($budget->status === 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @else
                                    <span class="badge bg-secondary">Draft</span>
                                @endif
                            </div>
                            @if($budget->status === 'pending_approval' && $currentLevel)
                                <div>
                                    <small class="text-muted">
                                        <i class="bx bx-user me-1"></i>
                                        Current Approvers: {{ $currentApprovers->pluck('name')->join(', ') }}
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="fw-bold" width="150">{{ __('app.budget_name') }}:</td>
                                        <td>{{ $budget->name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">{{ __('app.budget_year') }}:</td>
                                        <td><span class="badge bg-info">{{ $budget->year }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">{{ __('app.budget_branch') }}:</td>
                                        <td>
                                            @if($budget->branch_id === null)
                                                <span class="badge bg-info">All Branches</span>
                                            @else
                                                {{ $budget->branch->name ?? 'N/A' }}
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td class="fw-bold" width="150">{{ __('app.budget_created_by') }}:</td>
                                        <td>{{ $budget->user->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">{{ __('app.budget_created_date') }}:</td>
                                        <td>{{ $budget->created_at->format('M d, Y H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">{{ __('app.budget_total_amount') }}:</td>
                                        <td><span class="fw-bold text-success fs-5">TZS {{ number_format($budget->total_amount, 2) }}</span></td>
                                    </tr>
                                    @if($budget->status === 'pending_approval' && $currentLevel)
                                    <tr>
                                        <td class="fw-bold">Current Approval Level:</td>
                                        <td>
                                            <span class="badge bg-info">{{ $currentLevel->level_name }}</span>
                                        </td>
                                    </tr>
                                    @endif
                                    @if($budget->submitted_by)
                                    <tr>
                                        <td class="fw-bold">Submitted By:</td>
                                        <td>{{ $budget->submittedBy->name ?? 'N/A' }} 
                                            <small class="text-muted">({{ $budget->submitted_at ? $budget->submitted_at->format('M d, Y H:i') : 'N/A' }})</small>
                                        </td>
                                    </tr>
                                    @endif
                                    @if($budget->approved_by)
                                    <tr>
                                        <td class="fw-bold">Approved By:</td>
                                        <td>{{ $budget->approvedBy->name ?? 'N/A' }} 
                                            <small class="text-muted">({{ $budget->approved_at ? $budget->approved_at->format('M d, Y H:i') : 'N/A' }})</small>
                                        </td>
                                    </tr>
                                    @endif
                                    @if($budget->rejected_by)
                                    <tr>
                                        <td class="fw-bold">Rejected By:</td>
                                        <td>
                                            {{ $budget->rejectedBy->name ?? 'N/A' }} 
                                            <small class="text-muted">({{ $budget->rejected_at ? $budget->rejected_at->format('M d, Y H:i') : 'N/A' }})</small>
                                            @if($budget->rejection_reason)
                                                <br><small class="text-danger"><strong>Reason:</strong> {{ $budget->rejection_reason }}</small>
                                            @endif
                                        </td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approval Actions -->
        @if(in_array($budget->status, ['draft', 'rejected']) && $canSubmit && auth()->user()->can('submit budget for approval'))
        <div class="row">
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-send me-2"></i>Submit for Approval
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">Ready to submit this budget for approval? Click the button below to start the approval process.</p>
                        <form action="{{ route('accounting.budgets.submit-for-approval', $budget) }}" method="POST" id="submitApprovalForm">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-send me-2"></i>Submit for Approval
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($budget->status === 'pending_approval' && $canApprove && $currentLevel && (auth()->user()->can('approve budget') || auth()->user()->can('reject budget')))
        <div class="row">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-check-circle me-2"></i>Approval Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">You are authorized to approve or reject this budget at the current level (<strong>{{ $currentLevel->level_name }}</strong>).</p>
                        
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

        <!-- Budget Lines -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('app.budget_lines') }}</h5>
                    </div>
                    <div class="card-body">
                        @if($budget->budgetLines->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('app.account') }} {{ __('app.code') }}</th>
                                        <th>{{ __('app.account') }} {{ __('app.name') }}</th>
                                        <th>{{ __('app.amount') }}</th>
                                        <th>{{ __('app.description') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($budget->budgetLines as $index => $line)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $line->account->account_code ?? 'N/A' }}</span>
                                        </td>
                                        <td>{{ $line->account->account_name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="fw-bold text-success">
                                                TZS {{ number_format($line->amount, 2) }}
                                            </span>
                                        </td>
                                        <td>{{ $line->description ?? '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-dark">
                                                                                  <td colspan="3" class="fw-bold">{{ __('app.total') }}</td>
                                        <td class="fw-bold text-success">
                                            TZS {{ number_format($budget->total_amount, 2) }}
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-4">
                            <i class="bx bx-error-circle bx-lg text-warning mb-3"></i>
                            <h5 class="text-muted">{{ __('app.no_budget_lines_found') }}</h5>
                            <p class="text-muted">{{ __('app.no_budget_lines_message') }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Budget Summary -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('app.budget_summary') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <div class="text-center">
                                    <h3 class="text-primary">{{ $budget->budgetLines->count() }}</h3>
                                    <p class="text-muted mb-0">{{ __('app.budget_lines') }}</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <h3 class="text-success">TZS {{ number_format($budget->total_amount, 2) }}</h3>
                                    <p class="text-muted mb-0">{{ __('app.total_budget') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('app.quick_actions') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('accounting.budgets.reallocate', $budget) }}" class="btn btn-info">
                                <i class="bx bx-transfer"></i> Reallocate Amount
                            </a>
                            <a href="{{ route('accounting.budgets.edit', $budget) }}" class="btn btn-warning">
                                <i class="bx bx-edit"></i> {{ __('app.edit_budget') }}
                            </a>
                            <a href="{{ route('accounting.budgets.export-excel', $budget) }}" class="btn btn-success">
                                <i class="bx bx-export"></i> Export to Excel
                            </a>
                            <a href="{{ route('accounting.budgets.export-pdf', $budget) }}" class="btn btn-danger">
                                <i class="bx bx-file-pdf"></i> Export to PDF
                            </a>
                            <a href="{{ route('accounting.budgets.index') }}" class="btn btn-secondary">
                                <i class="bx bx-list-ul"></i> {{ __('app.view_all_budgets') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reallocation History -->
        @php
            $reallocations = $budget->reallocations()->with(['fromAccount', 'toAccount', 'user'])->orderBy('created_at', 'desc')->get();
        @endphp
        @if($reallocations->count() > 0)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-history me-2"></i>
                            Reallocation History
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>From Account</th>
                                        <th>To Account</th>
                                        <th>Amount</th>
                                        <th>Reason</th>
                                        <th>Reallocated By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reallocations as $index => $reallocation)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $reallocation->created_at->format('M d, Y H:i') }}</td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $reallocation->fromAccount->account_code ?? 'N/A' }}</span>
                                            <br>
                                            <small>{{ $reallocation->fromAccount->account_name ?? 'N/A' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">{{ $reallocation->toAccount->account_code ?? 'N/A' }}</span>
                                            <br>
                                            <small>{{ $reallocation->toAccount->account_name ?? 'N/A' }}</small>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-primary">
                                                TZS {{ number_format($reallocation->amount, 2) }}
                                            </span>
                                        </td>
                                        <td>{{ $reallocation->reason ?? '-' }}</td>
                                        <td>{{ $reallocation->user->name ?? 'N/A' }}</td>
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

        <!-- Approval History -->
        @if($budget->approvalHistories->count() > 0 && auth()->user()->can('view budget approval history'))
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-history me-2"></i>Approval History
                            </h5>
                            @if(auth()->user()->can('view budget approval history'))
                            <a href="{{ route('accounting.budgets.approval-history', $budget) }}" class="btn btn-sm btn-info">
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
                                    @foreach($budget->approvalHistories->take(5) as $history)
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
@if($budget->status === 'pending_approval' && $canApprove && $currentLevel)
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Budget</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('accounting.budgets.approve', $budget) }}" method="POST">
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
                <h5 class="modal-title">Reject Budget</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('accounting.budgets.reject', $budget) }}" method="POST">
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
@endsection