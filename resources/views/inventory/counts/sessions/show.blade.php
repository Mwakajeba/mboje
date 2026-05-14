@extends('layouts.main')

@section('title', 'Count Session Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Management', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Inventory Count', 'url' => route('inventory.counts.index'), 'icon' => 'bx bx-clipboard-check'],
            ['label' => 'Session Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        
        <h6 class="mb-0 text-uppercase">COUNT SESSION DETAILS</h6>
        <hr />

        <!-- Session Info Card -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Session: {{ $session->session_number }}</h5>
                        <div class="btn-group">
                            @if($session->status === 'draft')
                                <form action="{{ route('inventory.counts.sessions.freeze', $session->encoded_id) }}" method="POST" class="d-inline" id="freeze-session-form">
                                    @csrf
                                    <button type="button" class="btn btn-info" id="freeze-session-btn">
                                        <i class="bx bx-lock me-1"></i> Freeze Session
                                    </button>
                                </form>
                            @endif
                            @if($session->status === 'frozen')
                                <form action="{{ route('inventory.counts.sessions.start-counting', $session->encoded_id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bx bx-play me-1"></i> Start Counting
                                    </button>
                                </form>
                            @endif
                            @if($session->status === 'counting')
                                <form action="{{ route('inventory.counts.sessions.complete-counting', $session->encoded_id) }}" method="POST" class="d-inline" id="complete-counting-form">
                                    @csrf
                                    <button type="button" class="btn btn-success" id="complete-counting-btn">
                                        <i class="bx bx-check me-1"></i> Complete Counting
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('inventory.counts.sessions.export-counting-sheets-pdf', $session->encoded_id) }}" class="btn btn-danger">
                                <i class="bx bx-file me-1"></i> Export PDF
                            </a>
                            <a href="{{ route('inventory.counts.sessions.export-counting-sheets-excel', $session->encoded_id) }}" class="btn btn-success">
                                <i class="bx bx-file me-1"></i> Export Excel
                            </a>
                            @if(in_array($session->status, ['draft', 'frozen']))
                                <a href="{{ route('inventory.counts.sessions.assign-team', $session->encoded_id) }}" class="btn btn-primary">
                                    <i class="bx bx-group me-1"></i> Assign Team
                                </a>
                            @endif
                            @if(in_array($session->status, ['frozen', 'counting']))
                                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#uploadPhysicalQtyModal">
                                    <i class="bx bx-upload me-1"></i> Upload Physical Qty
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Period:</strong><br>
                                {{ $session->period->period_name ?? 'N/A' }}
                            </div>
                            <div class="col-md-3">
                                <strong>Location:</strong><br>
                                {{ $session->location->name ?? 'N/A' }}
                            </div>
                            <div class="col-md-3">
                                <strong>Status:</strong><br>
                                <span class="badge bg-{{ $session->status === 'completed' ? 'success' : ($session->status === 'counting' ? 'warning' : ($session->status === 'frozen' ? 'info' : 'secondary')) }}">
                                    {{ ucfirst($session->status) }}
                                </span>
                            </div>
                            <div class="col-md-3">
                                <strong>Snapshot Date:</strong><br>
                                {{ $session->snapshot_date ? $session->snapshot_date->format('M d, Y H:i') : 'N/A' }}
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <strong>Blind Count:</strong><br>
                                {{ $session->is_blind_count ? 'Yes' : 'No' }}
                            </div>
                            <div class="col-md-3">
                                <strong>Created By:</strong><br>
                                {{ $session->createdBy->name ?? 'N/A' }}
                            </div>
                            <div class="col-md-3">
                                <strong>Supervisor(s):</strong><br>
                                @php
                                    $supervisors = $session->teams->where('role', 'supervisor');
                                @endphp
                                @if($supervisors->count() > 0)
                                    @foreach($supervisors as $supervisor)
                                        <span class="badge bg-success me-1">{{ $supervisor->user->name ?? 'N/A' }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">Not assigned</span>
                                @endif
                            </div>
                            <div class="col-md-3">
                                <strong>Total Entries:</strong><br>
                                {{ $totalEntries }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Members Section -->
        @if($session->teams->count() > 0)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-group me-2"></i>Assigned Team Members
                        </h5>
                        @if(in_array($session->status, ['draft', 'frozen']))
                            <a href="{{ route('inventory.counts.sessions.assign-team', $session->encoded_id) }}" class="btn btn-sm btn-primary">
                                <i class="bx bx-edit me-1"></i> Edit Team
                            </a>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @php
                                $counters = $session->teams->where('role', 'counter');
                                $supervisors = $session->teams->where('role', 'supervisor');
                                $verifiers = $session->teams->where('role', 'verifier');
                            @endphp
                            
                            @if($counters->count() > 0)
                            <div class="col-md-4 mb-3">
                                <h6 class="text-primary mb-2">
                                    <i class="bx bx-user me-1"></i>Counters ({{ $counters->count() }})
                                </h6>
                                <ul class="list-unstyled mb-0">
                                    @foreach($counters as $counter)
                                        <li class="mb-1">
                                            <i class="bx bx-user me-1 text-primary"></i>
                                            <strong>{{ $counter->user->name ?? 'N/A' }}</strong>
                                            @if($counter->assigned_area)
                                                <br><small class="text-muted ms-4">Area: {{ $counter->assigned_area }}</small>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif

                            @if($supervisors->count() > 0)
                            <div class="col-md-4 mb-3">
                                <h6 class="text-success mb-2">
                                    <i class="bx bx-user-check me-1"></i>Supervisors ({{ $supervisors->count() }})
                                </h6>
                                <ul class="list-unstyled mb-0">
                                    @foreach($supervisors as $supervisor)
                                        <li class="mb-1">
                                            <i class="bx bx-user me-1 text-success"></i>
                                            <strong>{{ $supervisor->user->name ?? 'N/A' }}</strong>
                                            @if($supervisor->assigned_area)
                                                <br><small class="text-muted ms-4">Area: {{ $supervisor->assigned_area }}</small>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif

                            @if($verifiers->count() > 0)
                            <div class="col-md-4 mb-3">
                                <h6 class="text-info mb-2">
                                    <i class="bx bx-check-circle me-1"></i>Verifiers ({{ $verifiers->count() }})
                                </h6>
                                <ul class="list-unstyled mb-0">
                                    @foreach($verifiers as $verifier)
                                        <li class="mb-1">
                                            <i class="bx bx-user me-1 text-info"></i>
                                            <strong>{{ $verifier->user->name ?? 'N/A' }}</strong>
                                            @if($verifier->assigned_area)
                                                <br><small class="text-muted ms-4">Area: {{ $verifier->assigned_area }}</small>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif

                            @if($session->teams->count() == 0)
                            <div class="col-12">
                                <div class="alert alert-info mb-0">
                                    <i class="bx bx-info-circle me-2"></i>
                                    No team members assigned yet. 
                                    @if(in_array($session->status, ['draft', 'frozen']))
                                        <a href="{{ route('inventory.counts.sessions.assign-team', $session->encoded_id) }}" class="alert-link">Assign team members</a>.
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Adjustments Summary Section -->
        @if($adjustments->count() > 0)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning bg-opacity-10 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="bx bx-clipboard me-2"></i>Stock Adjustments
                        </h6>
                        <div class="d-flex gap-2">
                            <a href="{{ route('inventory.counts.sessions.adjustments', $session->encoded_id) }}" class="btn btn-sm btn-primary">
                                <i class="bx bx-clipboard me-1"></i> View All Adjustments ({{ $adjustments->count() }})
                            </a>
                            <a href="{{ route('inventory.counts.sessions.variances', $session->encoded_id) }}" class="btn btn-sm btn-info">
                                <i class="bx bx-bar-chart me-1"></i> View Variances
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm rounded-circle bg-light-warning text-warning d-flex align-items-center justify-content-center">
                                            <i class="bx bx-time fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="mb-0 text-muted small">Pending Approval</p>
                                        <h5 class="mb-0 text-warning">{{ $adjustments->where('status', 'pending_approval')->count() }}</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm rounded-circle bg-light-info text-info d-flex align-items-center justify-content-center">
                                            <i class="bx bx-check-circle fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="mb-0 text-muted small">Approved</p>
                                        <h5 class="mb-0 text-info">{{ $adjustments->where('status', 'approved')->count() }}</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm rounded-circle bg-light-success text-success d-flex align-items-center justify-content-center">
                                            <i class="bx bx-check fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="mb-0 text-muted small">Posted to GL</p>
                                        <h5 class="mb-0 text-success">{{ $adjustments->where('status', 'posted')->count() }}</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm rounded-circle bg-light-primary text-primary d-flex align-items-center justify-content-center">
                                            <i class="bx bx-money fs-4"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <p class="mb-0 text-muted small">Total Value</p>
                                        <h5 class="mb-0 text-primary">TZS {{ number_format($adjustments->sum('adjustment_value'), 2) }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Approval Section -->
        @if($session->status === 'completed')
        <div class="row mb-4">
            <div class="col-12">
                <div class="card {{ $session->isPendingApproval() ? 'border-warning' : ($session->isApproved() ? 'border-success' : ($session->isRejected() ? 'border-danger' : '')) }}">
                    <div class="card-header {{ $session->isPendingApproval() ? 'bg-warning bg-opacity-10' : ($session->isApproved() ? 'bg-success bg-opacity-10' : ($session->isRejected() ? 'bg-danger bg-opacity-10' : '')) }}">
                        <h6 class="mb-0">
                            <i class="bx bx-check-circle me-2"></i>Count Session Approval
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($session->isPendingApproval())
                            <div class="alert alert-warning mb-3">
                                <h6 class="alert-heading">
                                    <i class="bx bx-time me-2"></i>Awaiting Approval
                                </h6>
                                <p class="mb-0">
                                    This count session has been completed and is awaiting approval. 
                                    Once approved, you can proceed to create adjustments for variances.
                                </p>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveSessionModal">
                                    <i class="bx bx-check me-1"></i> Approve Count Session
                                </button>
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectSessionModal">
                                    <i class="bx bx-x me-1"></i> Reject Count Session
                                </button>
                            </div>
                        @elseif($session->isApproved())
                            <div class="alert alert-success mb-3">
                                <h6 class="alert-heading">
                                    <i class="bx bx-check-circle me-2"></i>Count Session Approved
                                </h6>
                                <p class="mb-2">
                                    This count session has been approved. You can now proceed to create adjustments for variances.
                                </p>
                                @if($totalVariances > 0)
                                <div class="d-flex gap-2 align-items-center">
                                    <a href="{{ route('inventory.counts.sessions.variances', $session->encoded_id) }}" class="btn btn-primary">
                                        <i class="bx bx-bar-chart me-1"></i> View Variances & Create Adjustments
                                    </a>
                                    <span class="text-muted">
                                        <i class="bx bx-info-circle me-1"></i>{{ $totalVariances }} variance(s) found
                                    </span>
                                </div>
                                @else
                                <p class="mb-0 text-muted">
                                    <i class="bx bx-info-circle me-1"></i>No variances found. All items match system quantities.
                                </p>
                                @endif
                            </div>
                            <div class="alert alert-info mb-0">
                                <h6 class="alert-heading">
                                    <i class="bx bx-info-circle me-2"></i>Next Steps
                                </h6>
                                <ol class="mb-0 ps-3">
                                    <li>Click <strong>"View Variances & Create Adjustments"</strong> above</li>
                                    <li>Review each variance and click <strong>"Create Adjustment"</strong> for items that need adjustment</li>
                                    <li>Fill in the reason code and description for each adjustment</li>
                                    <li>Submit adjustments for multi-level approval</li>
                                    <li>Once all approvals are complete, click <strong>"Post to GL"</strong> to update inventory</li>
                                </ol>
                            </div>
                            <div class="mt-3">
                                <p class="mb-2">
                                    <strong>Approved by:</strong> {{ $session->approval->approver->name ?? 'N/A' }}
                                    on {{ $session->approval->approved_at ? $session->approval->approved_at->format('M d, Y H:i') : 'N/A' }}
                                </p>
                                @if($session->approval->comments)
                                <p class="mb-0">
                                    <strong>Comments:</strong> {{ $session->approval->comments }}
                                </p>
                                @endif
                            </div>
                        @elseif($session->isRejected())
                            <div class="alert alert-danger mb-0">
                                <h6 class="alert-heading">
                                    <i class="bx bx-x-circle me-2"></i>Rejected
                                </h6>
                                <p class="mb-2">
                                    This count session has been approved by 
                                    <strong>{{ $session->approval->approver->name ?? 'N/A' }}</strong>
                                    on {{ $session->approval->approved_at ? $session->approval->approved_at->format('M d, Y H:i') : 'N/A' }}.
                                </p>
                                @if($session->approval->comments)
                                <p class="mb-0">
                                    <strong>Comments:</strong> {{ $session->approval->comments }}
                                </p>
                                @endif
                            </div>
                        @elseif($session->isRejected())
                            <div class="alert alert-danger mb-0">
                                <h6 class="alert-heading">
                                    <i class="bx bx-x-circle me-2"></i>Rejected
                                </h6>
                                <p class="mb-2">
                                    This count session has been rejected by 
                                    <strong>{{ $session->approval->approver->name ?? 'N/A' }}</strong>
                                    on {{ $session->approval->rejected_at ? $session->approval->rejected_at->format('M d, Y H:i') : 'N/A' }}.
                                </p>
                                @if($session->approval->rejection_reason)
                                <p class="mb-0">
                                    <strong>Reason:</strong> {{ $session->approval->rejection_reason }}
                                </p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card radius-10 border-start border-0 border-3 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Entries</p>
                                <h4 class="my-1 text-primary">{{ number_format($totalEntries) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-list-ul align-middle"></i> All items</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-list-ul"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card radius-10 border-start border-0 border-3 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Counted</p>
                                <h4 class="my-1 text-success">{{ number_format($countedEntries) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-check-circle align-middle"></i> {{ $totalEntries > 0 ? number_format(($countedEntries / $totalEntries) * 100, 1) : 0 }}% complete</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                <i class="bx bx-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Variances</p>
                                <h4 class="my-1 text-warning">{{ number_format($totalVariances) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-warning"><i class="bx bx-bar-chart align-middle"></i> {{ $positiveVariances }} surplus, {{ $negativeVariances }} shortage</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                <i class="bx bx-bar-chart"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card radius-10 border-start border-0 border-3 border-danger">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Variance Value</p>
                                <h4 class="my-1 text-danger">TZS {{ number_format($totalVarianceValue, 2) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-danger"><i class="bx bx-dollar align-middle"></i> Total impact</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-danger text-white ms-auto">
                                <i class="bx bx-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Variance Cards -->
        @if($totalVariances > 0)
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card radius-10 border-start border-0 border-3 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Surplus (Positive)</p>
                                <h4 class="my-1 text-info">{{ number_format($positiveVariances) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-info"><i class="bx bx-trending-up align-middle"></i> TZS {{ number_format($totalPositiveValue, 2) }}</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                <i class="bx bx-trending-up"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card radius-10 border-start border-0 border-3 border-danger">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Shortage (Negative)</p>
                                <h4 class="my-1 text-danger">{{ number_format($negativeVariances) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-danger"><i class="bx bx-trending-down align-middle"></i> TZS {{ number_format($totalNegativeValue, 2) }}</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-danger text-white ms-auto">
                                <i class="bx bx-trending-down"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card radius-10 border-start border-0 border-3 border-secondary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Zero Variance</p>
                                <h4 class="my-1 text-secondary">{{ number_format($zeroVariances) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-secondary"><i class="bx bx-check align-middle"></i> Perfect match</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-secondary text-white ms-auto">
                                <i class="bx bx-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">High Value</p>
                                <h4 class="my-1 text-warning">{{ number_format($highValueVariances) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-warning"><i class="bx bx-error-circle align-middle"></i> Requires review</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                <i class="bx bx-error-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Count Entries Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Counting Entries</h5>
                        @if($session->status === 'counting' || $session->status === 'frozen')
                        <div class="alert alert-info mb-0 py-2 px-3">
                            <i class="bx bx-info-circle me-1"></i>
                            <small><strong>Tip:</strong> Enter physical quantities in the "Physical Qty" column below</small>
                        </div>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="countEntriesTable">
                                <thead>
                                    <tr>
                                        <th>Item Code</th>
                                        <th>Item Name</th>
                                        <th>UOM</th>
                                        @if(!$session->is_blind_count)
                                        <th>System Qty</th>
                                        @endif
                                        <th class="bg-warning bg-opacity-25">
                                            <i class="bx bx-edit me-1"></i>Physical Qty
                                            @if($session->status === 'counting' || $session->status === 'frozen')
                                            <br><small class="text-muted">Enter count here</small>
                                            @endif
                                        </th>
                                        <th>Variance</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($session->entries as $entry)
                                    <tr>
                                        <td>{{ $entry->item->code ?? 'N/A' }}</td>
                                        <td>{{ $entry->item->name ?? 'N/A' }}</td>
                                        <td>{{ $entry->item->unit_of_measure ?? 'N/A' }}</td>
                                        @if(!$session->is_blind_count)
                                        <td class="system-qty-cell" data-system-qty="{{ $entry->system_quantity }}">
                                            {{ number_format($entry->system_quantity, 2) }}
                                        </td>
                                        @endif
                                        <td>
                                            @if($session->status === 'counting' || $session->status === 'frozen')
                                                <input type="number" 
                                                       step="0.01" 
                                                       min="0"
                                                       class="form-control form-control-sm physical-qty-input" 
                                                       data-entry-id="{{ $entry->encoded_id }}" 
                                                       data-system-qty="{{ $entry->system_quantity }}"
                                                       value="{{ $entry->physical_quantity ?? '' }}" 
                                                       placeholder="Enter qty"
                                                       style="width: 120px;"
                                                       title="Enter the physical count quantity"
                                                       autocomplete="off">
                                            @else
                                                {{ $entry->physical_quantity ? number_format($entry->physical_quantity, 2) : '-' }}
                                            @endif
                                        </td>
                                        <td class="variance-cell" data-entry-id="{{ $entry->encoded_id }}">
                                            @if($session->status === 'counting' || $session->status === 'frozen')
                                                <span class="variance-badge" id="variance-{{ $entry->encoded_id }}">
                                                    @if($entry->physical_quantity)
                                                        @php
                                                            $variance = $entry->physical_quantity - $entry->system_quantity;
                                                            $varianceType = $variance > 0 ? 'success' : ($variance < 0 ? 'danger' : 'secondary');
                                                        @endphp
                                                        <span class="badge bg-{{ $varianceType }}">
                                                            {{ number_format($variance, 2) }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary">-</span>
                                                    @endif
                                                </span>
                                            @else
                                            @if($entry->variance)
                                                <span class="badge bg-{{ $entry->variance->variance_type === 'positive' ? 'success' : ($entry->variance->variance_type === 'negative' ? 'danger' : 'secondary') }}">
                                                    {{ number_format($entry->variance->variance_quantity, 2) }}
                                                </span>
                                            @else
                                                -
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $entry->status === 'verified' ? 'success' : ($entry->status === 'counted' ? 'info' : 'secondary') }}">
                                                {{ ucfirst($entry->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info view-entry-btn" data-entry-id="{{ $entry->encoded_id }}">
                                                <i class="bx bx-show"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Entry Detail Modal -->
<div class="modal fade" id="entryDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Count Entry Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="entryDetailContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Upload Physical Qty Modal -->
<div class="modal fade" id="uploadPhysicalQtyModal" tabindex="-1" aria-labelledby="uploadPhysicalQtyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadPhysicalQtyModalLabel">
                    <i class="bx bx-upload me-2"></i>Upload Physical Quantities
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uploadPhysicalQtyForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>
                        <strong>Upload Excel File:</strong> Upload an Excel file with physical quantities counted offline.
                        <br>
                        <small>
                            The file should contain columns: <code>Item Code, Item Name, Physical Quantity, Condition, Lot Number, Batch Number, Expiry Date, Remarks</code>
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="excel_file" class="form-label">Excel File <span class="text-danger">*</span></label>
                        <input type="file" name="excel_file" id="excel_file" class="form-control" accept=".xlsx,.xls" required>
                        <div class="invalid-feedback"></div>
                        <small class="text-muted">Upload an Excel file (.xlsx or .xls) with physical quantities.</small>
                    </div>

                    <div class="alert alert-warning">
                        <i class="bx bx-error-circle me-2"></i>
                        <strong>Important:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Make sure the Item Code matches exactly with the items in this count session</li>
                            <li>Physical Quantity must be a valid number</li>
                            <li>Condition should be: good, damaged, expired, obsolete, or missing</li>
                            <li>Expiry Date format: YYYY-MM-DD (e.g., 2025-12-31)</li>
                        </ul>
                    </div>

                    <div class="mt-3">
                        <a href="{{ route('inventory.counts.sessions.download-counting-template', $session->encoded_id) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bx bx-download me-1"></i> Download Excel Template
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="uploadPhysicalQtyBtn">
                        <i class="bx bx-upload me-1"></i> Upload Physical Quantities
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Approve Session Modal -->
@if($session->status === 'completed' && $session->isPendingApproval())
<div class="modal fade" id="approveSessionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bx bx-check-circle me-2"></i>Approve Count Session
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('inventory.counts.sessions.approve', $session->encoded_id) }}" method="POST" id="approve-session-form">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>
                        <strong>Note:</strong> Once approved, you can proceed to create adjustments for variances.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Approval Comments <span class="text-muted">(Optional)</span></label>
                        <textarea name="comments" class="form-control" rows="4" placeholder="Add any comments about this count session (e.g., variances reviewed, quality of count, etc.)..."></textarea>
                        <small class="text-muted">Optional: Add any notes or comments about this approval.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bx bx-check me-1"></i> Approve Count Session
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Session Modal -->
<div class="modal fade" id="rejectSessionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bx bx-x-circle me-2"></i>Reject Count Session
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('inventory.counts.sessions.reject', $session->encoded_id) }}" method="POST" id="reject-session-form">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bx bx-error-circle me-2"></i>
                        <strong>Warning:</strong> Rejecting this count session will require recounting. 
                        Please provide a clear reason for rejection.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="4" required minlength="10" placeholder="Please provide a detailed reason for rejecting this count session (e.g., discrepancies found, incomplete count, data quality issues, etc.)..."></textarea>
                        <small class="text-muted">Minimum 10 characters required.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bx bx-x me-1"></i> Reject Count Session
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif


    </div>
</div>
@endsection

@push('styles')
<style>
    .widgets-icons-2 {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #ededed;
        font-size: 27px;
    }
    
    .bg-gradient-primary {
        background: linear-gradient(45deg, #0d6efd, #0a58ca) !important;
    }
    
    .bg-gradient-success {
        background: linear-gradient(45deg, #198754, #146c43) !important;
    }
    
    .bg-gradient-info {
        background: linear-gradient(45deg, #0dcaf0, #0aa2c0) !important;
    }
    
    .bg-gradient-warning {
        background: linear-gradient(45deg, #ffc107, #ffb300) !important;
    }
    
    .bg-gradient-danger {
        background: linear-gradient(45deg, #dc3545, #bb2d3b) !important;
    }
    
    .bg-gradient-secondary {
        background: linear-gradient(45deg, #6c757d, #5c636a) !important;
    }
    
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .radius-10 {
        border-radius: 10px;
    }
    
    .border-start {
        border-left-width: 3px !important;
    }

    .physical-qty-input {
        pointer-events: auto !important;
        user-select: text !important;
        -webkit-user-select: text !important;
        -moz-user-select: text !important;
        -ms-user-select: text !important;
    }
    
    .physical-qty-input:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        outline: none;
    }
    
    .physical-qty-input:disabled {
        background-color: #e9ecef;
        opacity: 0.6;
        cursor: not-allowed;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        // Freeze Session confirmation with SweetAlert
        $('#freeze-session-btn').on('click', function() {
            Swal.fire({
                title: 'Freeze Session?',
                html: `
                    <div class="text-start">
                        <p class="mb-2">Are you sure you want to freeze this count session?</p>
                        <p class="mb-0 text-muted small">
                            <i class="bx bx-info-circle me-1"></i>
                            Freezing will lock the session and prevent further modifications until counting begins.
                        </p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0dcaf0',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bx bx-lock me-1"></i>Yes, Freeze Session',
                cancelButtonText: '<i class="bx bx-x me-1"></i>Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#freeze-session-form').submit();
                }
            });
        });

        // Complete Counting confirmation with SweetAlert
        $('#complete-counting-btn').on('click', function() {
            Swal.fire({
                title: 'Complete Counting?',
                html: `
                    <div class="text-start">
                        <p class="mb-2">Are you sure you want to complete counting for this session?</p>
                        <p class="mb-2"><strong>This action will:</strong></p>
                        <ul class="text-start mb-2">
                            <li>Calculate variances between system and physical quantities</li>
                            <li>Lock all entries from further modifications</li>
                            <li>Generate variance reports</li>
                        </ul>
                        <p class="mb-0 text-warning small">
                            <i class="bx bx-error-circle me-1"></i>
                            This action cannot be undone easily.
                        </p>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bx bx-check me-1"></i>Yes, Complete Counting',
                cancelButtonText: '<i class="bx bx-x me-1"></i>Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#complete-counting-form').submit();
                }
            });
        });

        // Ensure all physical quantity inputs are enabled and editable on page load
        $('.physical-qty-input').each(function() {
            const $input = $(this);
            $input.prop('disabled', false)
                   .removeAttr('readonly')
                   .removeClass('disabled')
                   .css({
                       'pointer-events': 'auto',
                       'user-select': 'text',
                       '-webkit-user-select': 'text'
                   })
                   .data('original-value', $input.val() || '');
            
            // Initialize variance display if physical quantity exists
            if ($input.val() && $input.val() !== '') {
                const physicalQty = parseFloat($input.val()) || 0;
                const systemQty = parseFloat($input.data('system-qty')) || 0;
                const variance = physicalQty - systemQty;
                
                let varianceColor = 'secondary';
                if (variance > 0) {
                    varianceColor = 'success';
                } else if (variance < 0) {
                    varianceColor = 'danger';
                }
                
                const $row = $input.closest('tr');
                const $varianceCell = $row.find('.variance-cell');
                $varianceCell.html('<span class="badge bg-' + varianceColor + '">' + variance.toFixed(2) + '</span>');
            }
        });

        // Also ensure inputs are enabled when clicked/focused
        $(document).on('focus click', '.physical-qty-input', function() {
            $(this).prop('disabled', false)
                   .removeAttr('readonly')
                   .css({
                       'pointer-events': 'auto',
                       'user-select': 'text'
                   });
        });

        // Real-time variance calculation as user types
        $(document).on('input keyup change', '.physical-qty-input', function() {
            const $input = $(this);
            const physicalQty = parseFloat($input.val()) || 0;
            const systemQty = parseFloat($input.data('system-qty')) || 0;
            const entryId = $input.data('entry-id');
            
            // Calculate variance (Physical - System)
            const variance = physicalQty - systemQty;
            
            // Find the variance cell in the same row
            const $row = $input.closest('tr');
            const $varianceCell = $row.find('.variance-cell');
            
            // Determine variance type and color
            let varianceColor = 'secondary';
            if (variance > 0) {
                varianceColor = 'success'; // Positive variance (more than system)
            } else if (variance < 0) {
                varianceColor = 'danger'; // Negative variance (less than system)
            } else {
                varianceColor = 'secondary'; // Zero variance
            }
            
            // Update variance display
            if ($input.val() !== '' && $input.val() !== null) {
                const varianceFormatted = variance.toFixed(2);
                $varianceCell.html('<span class="badge bg-' + varianceColor + '">' + varianceFormatted + '</span>');
            } else {
                $varianceCell.html('<span class="badge bg-secondary">-</span>');
            }
        });

        // Save physical quantity on blur (only if value changed)
        $(document).on('blur', '.physical-qty-input', function() {
            const entryId = $(this).data('entry-id');
            const physicalQty = $(this).val();
            const originalValue = $(this).data('original-value') || '';
            
            // Only save if value has changed and is not empty
            if (physicalQty === '' || physicalQty === null || physicalQty === originalValue) {
                return;
            }
            
            // Show loading indicator
            const $input = $(this);
            const oldValue = $input.val();
            $input.prop('disabled', true);
            
            $.ajax({
                url: '{{ route("inventory.counts.entries.update-physical-qty", ":id") }}'.replace(':id', entryId),
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    physical_quantity: physicalQty
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Physical quantity updated successfully');
                        // Update original value
                        $input.data('original-value', physicalQty);
                        // Reload after a short delay to show the update
                        setTimeout(function() {
                        location.reload();
                        }, 500);
                    } else {
                        toastr.error(response.message || 'Failed to update physical quantity');
                        $input.prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Failed to update physical quantity';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                    $input.prop('disabled', false);
                    // Restore old value on error
                    $input.val(oldValue);
                }
            });
        });

        // View entry details
        $('.view-entry-btn').on('click', function() {
            const entryId = $(this).data('entry-id');
            $('#entryDetailModal').modal('show');
            $('#entryDetailContent').html('<div class="text-center"><i class="bx bx-loader bx-spin"></i> Loading...</div>');
            
            $.ajax({
                url: '{{ route("inventory.counts.entries.show", ":id") }}'.replace(':id', entryId),
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                },
                success: function(response) {
                    $('#entryDetailContent').html(response);
                },
                error: function(xhr, status, error) {
                    console.error('Error loading entry details:', error, xhr.responseText);
                    $('#entryDetailContent').html('<div class="alert alert-danger"><i class="bx bx-error-circle me-2"></i>Failed to load entry details. Please try again.</div>');
                }
            });
        });

        // Upload Physical Qty Form Handler
        $('#uploadPhysicalQtyForm').on('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const formData = new FormData(form);
            const submitBtn = $('#uploadPhysicalQtyBtn');
            const originalBtnText = submitBtn.html();
            
            // Disable submit button
            submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Uploading...');
            
            // Remove previous validation classes
            $(form).find('.is-invalid').removeClass('is-invalid');
            $(form).find('.invalid-feedback').text('');
            
            $.ajax({
                url: '{{ route('inventory.counts.sessions.upload-counting-excel', $session->encoded_id) }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            showConfirmButton: true
                        }).then(() => {
                            // Close modal
                            $('#uploadPhysicalQtyModal').modal('hide');
                            // Reset form
                            form.reset();
                            // Reload page to show updated quantities
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Upload Failed',
                            text: response.message || 'Failed to upload physical quantities',
                            showConfirmButton: true
                        });
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Failed to upload physical quantities';
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        const firstError = Object.values(errors)[0];
                        errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Upload Failed',
                        text: errorMessage,
                        showConfirmButton: true
                    });
                },
                complete: function() {
                    // Re-enable submit button
                    submitBtn.prop('disabled', false).html(originalBtnText);
                }
            });
        });

        // Approve Session Form Handler with SweetAlert confirmation
        $('#approve-session-form').on('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const comments = $(form).find('textarea[name="comments"]').val();
            
            Swal.fire({
                title: 'Approve Count Session?',
                html: comments 
                    ? `<p>Are you sure you want to approve this count session?</p><p class="text-muted small">Comments: ${comments}</p>`
                    : '<p>Are you sure you want to approve this count session? Once approved, adjustments can be created.</p>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Approve',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        // Reject Session Form Handler with SweetAlert confirmation
        $('#reject-session-form').on('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const rejectionReason = $(form).find('textarea[name="rejection_reason"]').val();
            
            if (!rejectionReason || rejectionReason.trim().length < 10) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please provide a rejection reason with at least 10 characters.',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }
            
            Swal.fire({
                title: 'Reject Count Session?',
                html: `<p>Are you sure you want to reject this count session?</p>
                       <p class="text-danger small"><strong>Reason:</strong> ${rejectionReason}</p>
                       <p class="text-muted small">This will require recounting.</p>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Reject',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endpush
