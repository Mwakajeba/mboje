@extends('layouts.main')

@section('title', 'Petty Cash Unit Details')

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
    
    .info-group {
        padding: 0.5rem 0;
    }
    
    .info-group label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.25rem;
    }
    
    .balance-indicator {
        font-size: 2rem;
        font-weight: bold;
    }
    
    .balance-low {
        color: #dc3545;
    }
    
    .balance-normal {
        color: #198754;
    }
    
    .balance-high {
        color: #0d6efd;
    }
    
    .progress {
        background-color: #e9ecef;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
    }
    
    .progress-bar {
        transition: width 0.6s ease;
    }
    
    .progress-bar-striped {
        background-image: linear-gradient(45deg, rgba(255,255,255,0.15) 25%, transparent 25%, transparent 50%, rgba(255,255,255,0.15) 50%, rgba(255,255,255,0.15) 75%, transparent 75%, transparent);
        background-size: 1rem 1rem;
    }
    
    .order-actions {
        justify-content: center;
    }
    
    .order-actions .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Management', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Petty Cash Units', 'url' => route('accounting.petty-cash.units.index'), 'icon' => 'bx bx-wallet'],
            ['label' => 'Unit Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <!-- Alert for Low Balance -->
        @php
            $isBelowMinimum = $minimumBalanceTrigger && $unit->current_balance < $minimumBalanceTrigger;
        @endphp
        @if($isBelowMinimum)
        <div class="row">
            <div class="col-12">
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="bx bx-error-circle me-2" style="font-size: 1.5rem;"></i>
                    <div class="flex-grow-1">
                        <strong>Low Balance Alert!</strong> The current balance (TZS {{ number_format($unit->current_balance, 2) }}) is below the minimum balance trigger (TZS {{ number_format($minimumBalanceTrigger, 2) }}). 
                        Please request a replenishment to restore the balance.
                    </div>
                    <button type="button" class="btn btn-sm btn-danger ms-2" data-bs-toggle="modal" data-bs-target="#requestReplenishmentModal">
                        <i class="bx bx-plus me-1"></i>Request Replenishment
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
        @endif

        <!-- Header Section -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-0">Petty Cash Unit Details</h4>
                        <p class="text-muted mb-0">{{ $unit->name }} ({{ $unit->code }})</p>
                    </div>
                    <div class="page-title-right d-flex gap-2 flex-wrap">
                        <a href="{{ route('accounting.petty-cash.units.export-pdf', $unit->encoded_id) }}" class="btn btn-danger" target="_blank">
                            <i class="bx bx-file-blank me-1"></i>Export PDF
                        </a>
                        <a href="{{ route('accounting.petty-cash.register.index', $unit->encoded_id) }}" class="btn btn-info">
                            <i class="bx bx-list-ul me-1"></i>Register
                        </a>
                        <a href="{{ route('accounting.petty-cash.register.reconciliation', $unit->encoded_id) }}" class="btn btn-warning">
                            <i class="bx bx-check-square me-1"></i>Reconciliation
                        </a>
                        <a href="{{ route('accounting.petty-cash.units.edit', $unit->encoded_id) }}" class="btn btn-primary">
                            <i class="bx bx-edit me-1"></i>Edit
                        </a>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newTransactionModal">
                            <i class="bx bx-plus me-1"></i>New Transaction
                        </button>
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#newReplenishmentModal">
                            <i class="bx bx-refresh me-1"></i>Request Replenishment
                        </button>
                        <a href="{{ route('accounting.petty-cash.units.index') }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back
                        </a>
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

        <!-- Summary Cards -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card info-card border-primary radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary small">Float Amount</p>
                                <h4 class="my-1 text-primary">TZS {{ number_format($unit->float_amount, 2) }}</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                @php
                    $isBelowMinimum = $minimumBalanceTrigger && $unit->current_balance < $minimumBalanceTrigger;
                    $isLowBalance = $isBelowMinimum || (!$minimumBalanceTrigger && $unit->current_balance < ($unit->float_amount * 0.2));
                @endphp
                <div class="card info-card border-{{ $isLowBalance ? 'danger' : 'success' }} radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-grow-1">
                                @php
                                    // Get the latest replenishment date to reset "used" calculation
                                    // Check for both 'approved' and 'posted' statuses
                                    $latestReplenishment = $unit->replenishments()
                                        ->whereIn('status', ['approved', 'posted'])
                                        ->whereNotNull('approved_at')
                                        ->orderBy('approved_at', 'desc')
                                        ->orderBy('id', 'desc')
                                        ->first();
                                    
                                    // Calculate used amount from transactions AFTER the latest replenishment
                                    // If no replenishment exists, calculate from all transactions
                                    if ($latestReplenishment && $latestReplenishment->approved_at) {
                                        // Only count transactions that were posted to GL (payment created) after the latest replenishment was approved
                                        // We use the payment's created_at date because that's when the transaction actually affected the balance
                                        $replenishmentDate = $latestReplenishment->approved_at;
                                        $usedAmount = $unit->transactions()
                                            ->where('status', '!=', 'rejected')
                                            ->whereNotNull('payment_id') // Only posted transactions
                                            ->whereHas('payment', function($query) use ($replenishmentDate) {
                                                $query->where('created_at', '>', $replenishmentDate);
                                            })
                                            ->sum('amount');
                                    } else {
                                        // No replenishment yet, count all transactions
                                        $usedAmount = $unit->transactions()
                                            ->where('status', '!=', 'rejected')
                                            ->whereNotNull('payment_id') // Only posted transactions
                                            ->sum('amount');
                                    }
                                    
                                    // Calculate total replenished amount (approved and posted)
                                    $replenishedAmount = $unit->replenishments()
                                        ->where('status', 'approved')
                                        ->sum('approved_amount');
                                    
                                    // Calculate net balance: float + replenishments - used (since last replenishment)
                                    // After replenishment, balance resets to float, so used should be from that point
                                    $calculatedBalance = $unit->float_amount - $usedAmount;
                                    
                                    // Calculate percentages based on float amount
                                    // Used percentage = (used since last replenishment / float) * 100
                                    // Remaining percentage = (current / float) * 100
                                    if ($unit->float_amount > 0) {
                                        $usedPercentage = min(100, ($usedAmount / $unit->float_amount) * 100);
                                        
                                        // Calculate remaining as: (current_balance / float_amount) * 100
                                        // After replenishment, current_balance should be close to float_amount
                                        $remainingPercentage = ($unit->current_balance / $unit->float_amount) * 100;
                                        
                                        // Cap at 100% for display purposes if it exceeds
                                        $displayRemainingPercentage = min(100, $remainingPercentage);
                                    } else {
                                        $displayRemainingPercentage = 0;
                                        $usedPercentage = 0;
                                    }
                                    
                                    // Determine progress bar color based on remaining percentage
                                    if ($displayRemainingPercentage < 20) {
                                        $progressColor = 'danger';
                                    } elseif ($displayRemainingPercentage < 50) {
                                        $progressColor = 'warning';
                                    } else {
                                        $progressColor = 'success';
                                    }
                                    
                                    // Clamp percentages to 0-100
                                    $displayRemainingPercentage = max(0, min(100, $displayRemainingPercentage));
                                    $usedPercentage = max(0, min(100, $usedPercentage));
                                @endphp
                                <p class="mb-0 text-secondary small">Current Balance</p>
                                <h4 class="my-1 balance-indicator {{ $isLowBalance ? 'balance-low' : 'balance-normal' }}">
                                    TZS {{ number_format($unit->current_balance, 2) }}
                                </h4>
                                @if($isBelowMinimum)
                                <small class="text-danger d-block">
                                    <i class="bx bx-error-circle"></i> Below minimum (TZS {{ number_format($minimumBalanceTrigger, 2) }})
                                </small>
                                @endif
                                <small class="text-muted d-block">
                                    {{ number_format($displayRemainingPercentage, 1) }}% of float
                                    @if($replenishedAmount > 0)
                                        <span class="text-info">(+{{ number_format(($replenishedAmount / $unit->float_amount) * 100, 1) }}% replenished)</span>
                                    @endif
                                </small>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-{{ $isLowBalance ? 'danger' : 'success' }} text-white ms-auto">
                                <i class="bx bx-wallet"></i>
                            </div>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted fw-bold">Balance Usage</small>
                                <small class="text-muted">
                                    @php
                                        if ($unit->float_amount > 0) {
                                            $usedPercentageForBar = max(0, min(100, ($usedAmount / $unit->float_amount) * 100));
                                            $availableFromFloat = max(0, min(100, (($unit->float_amount - $usedAmount) / $unit->float_amount) * 100));
                                        } else {
                                            $usedPercentageForBar = 0;
                                            $availableFromFloat = 0;
                                        }
                                    @endphp
                                    <span class="text-{{ $progressColor }}">{{ number_format($usedPercentageForBar, 1) }}%</span> used
                                    @if($availableFromFloat > 0)
                                        | <span class="text-success">{{ number_format($availableFromFloat, 1) }}%</span> available
                                    @endif
                                </small>
                            </div>
                            <div class="progress" style="height: 16px; border-radius: 8px; position: relative; background-color: #e9ecef;">
                                @php
                                    // Progress bar shows "used" percentage, not "available"
                                    // When used = 0, bar is empty (0%)
                                    // As used increases, bar fills up to show usage
                                    // After replenishment, used resets to 0, so bar shows 0%
                                    if ($unit->float_amount > 0) {
                                        $usedPercentageForBar = max(0, min(100, ($usedAmount / $unit->float_amount) * 100));
                                    } else {
                                        $usedPercentageForBar = 0;
                                    }
                                @endphp
                                <!-- Used portion - shows how much has been used since last replenishment -->
                                <div class="progress-bar bg-{{ $progressColor }} progress-bar-striped progress-bar-animated" 
                                     role="progressbar" 
                                     style="width: {{ $usedPercentageForBar }}%"
                                     aria-valuenow="{{ $usedPercentageForBar }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100"
                                     title="Used: {{ number_format($usedPercentageForBar, 1) }}% (TZS {{ number_format($usedAmount, 2) }} since last replenishment)">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <small class="text-muted">
                                    <i class="bx bx-down-arrow-alt text-{{ $progressColor }}"></i> 
                                    Used: <strong>TZS {{ number_format($usedAmount, 2) }}</strong>
                                </small>
                                <small class="text-muted">
                                    <i class="bx bx-up-arrow-alt text-success"></i> 
                                    Available: <strong>TZS {{ number_format($unit->current_balance, 2) }}</strong>
                                </small>
                            </div>
                            @if($replenishedAmount > 0)
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <small class="text-muted">
                                    <i class="bx bx-refresh text-info"></i> 
                                    Replenished: <strong>TZS {{ number_format($replenishedAmount, 2) }}</strong>
                                </small>
                                <small class="text-muted">
                                    <i class="bx bx-calculator text-secondary"></i> 
                                    Float: <strong>TZS {{ number_format($unit->float_amount, 2) }}</strong>
                                </small>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card info-card border-info radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary small">Total Transactions</p>
                                <h4 class="my-1 text-info">{{ $unit->transactions()->count() }}</h4>
                                <small class="text-muted">All time</small>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                <i class="bx bx-list-ul"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card info-card border-warning radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary small">Status</p>
                                <h4 class="my-1">
                                    <span class="badge bg-{{ $unit->is_active ? 'success' : 'secondary' }} fs-6">
                                        {{ $unit->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                <i class="bx bx-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <!-- Left Column - Unit Information -->
            <div class="col-lg-8">
                <!-- Unit Information Card -->
                <div class="card radius-10">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bx bx-wallet me-2"></i>Unit Information</h5>
                        <span class="badge bg-light text-primary fs-6">{{ $unit->code }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-group mb-3">
                                    <label class="form-label text-muted small">Unit Name</label>
                                    <div class="fw-bold">{{ $unit->name }}</div>
                                </div>
                                <div class="info-group mb-3">
                                    <label class="form-label text-muted small">Unit Code</label>
                                    <div class="fw-bold">{{ $unit->code }}</div>
                                </div>
                                <div class="info-group mb-3">
                                    <label class="form-label text-muted small">Branch</label>
                                    <div class="fw-bold">{{ $unit->branch->name ?? 'All Branches' }}</div>
                                </div>
                                <div class="info-group mb-3">
                                    <label class="form-label text-muted small">Custodian</label>
                                    <div class="fw-bold">{{ $unit->custodian->name ?? 'N/A' }}</div>
                                    @if($unit->custodian && $unit->custodian->email)
                                        <small class="text-muted">{{ $unit->custodian->email }}</small>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-group mb-3">
                                    <label class="form-label text-muted small">Supervisor</label>
                                    <div class="fw-bold">{{ $unit->supervisor->name ?? 'Not Assigned' }}</div>
                                    @if($unit->supervisor && $unit->supervisor->email)
                                        <small class="text-muted">{{ $unit->supervisor->email }}</small>
                                    @endif
                                </div>
                                <div class="info-group mb-3">
                                    <label class="form-label text-muted small">Maximum Limit</label>
                                    <div class="fw-bold">
                                        {{ $unit->maximum_limit ? 'TZS ' . number_format($unit->maximum_limit, 2) : 'Not Set' }}
                                    </div>
                                </div>
                                <div class="info-group mb-3">
                                    <label class="form-label text-muted small">Approval Threshold</label>
                                    <div class="fw-bold">
                                        {{ $unit->approval_threshold ? 'TZS ' . number_format($unit->approval_threshold, 2) : 'Not Set' }}
                                    </div>
                                    <small class="text-muted">Expenses above this amount require approval</small>
                                </div>
                                <div class="info-group mb-3">
                                    <label class="form-label text-muted small">Created</label>
                                    <div class="fw-bold">{{ $unit->created_at->format('F d, Y') }}</div>
                                    <small class="text-muted">{{ $unit->created_at->format('h:i A') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart of Accounts Card -->
                <div class="card radius-10 mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bx bx-book me-2"></i>Chart of Accounts</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-group mb-3">
                                    <label class="form-label text-muted small">Petty Cash Account</label>
                                    <div class="fw-bold">
                                        {{ $unit->pettyCashAccount->account_name ?? 'N/A' }}
                                    </div>
                                    @if($unit->pettyCashAccount)
                                        <small class="text-muted">{{ $unit->pettyCashAccount->account_code }}</small>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-group mb-3">
                                    <label class="form-label text-muted small">Suspense Account</label>
                                    <div class="fw-bold">
                                        {{ $unit->suspenseAccount->account_name ?? 'Not Set' }}
                                    </div>
                                    @if($unit->suspenseAccount)
                                        <small class="text-muted">{{ $unit->suspenseAccount->account_code }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($unit->notes)
                <!-- Notes Card -->
                <div class="card radius-10 mt-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-note me-2"></i>Notes</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">{{ $unit->notes }}</p>
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column - Quick Stats & Actions -->
            <div class="col-lg-4">
                <!-- Balance Summary Card -->
                <div class="card radius-10 border-{{ $isLowBalance ? 'danger' : 'success' }}">
                    <div class="card-header bg-{{ $isLowBalance ? 'danger' : 'success' }} text-white">
                        <h6 class="mb-0"><i class="bx bx-wallet me-2"></i>Balance Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <h2 class="balance-indicator {{ $isLowBalance ? 'balance-low' : 'balance-normal' }}">
                                TZS {{ number_format($unit->current_balance, 2) }}
                            </h2>
                            <p class="text-muted mb-0">Current Balance</p>
                            @if($isBelowMinimum && $minimumBalanceTrigger)
                            <small class="text-danger d-block mt-1">
                                <i class="bx bx-error-circle"></i> Below minimum: TZS {{ number_format($minimumBalanceTrigger, 2) }}
                            </small>
                            @endif
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Float Amount:</span>
                            <span class="fw-bold">TZS {{ number_format($unit->float_amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">
                                <i class="bx bx-down-arrow-alt text-danger"></i> Total Used:
                            </span>
                            <span class="fw-bold text-danger">
                                TZS {{ number_format($usedAmount, 2) }}
                            </span>
                        </div>
                        @php
                            $totalReplenishments = $unit->replenishments()
                                ->where('status', 'posted')
                                ->sum('approved_amount');
                        @endphp
                        @if($totalReplenishments > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">
                                <i class="bx bx-up-arrow-alt text-success"></i> Total Replenished:
                            </span>
                            <span class="fw-bold text-success">
                                TZS {{ number_format($totalReplenishments, 2) }}
                            </span>
                        </div>
                        @endif
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Available:</span>
                            <span class="fw-bold text-{{ $unit->current_balance < ($unit->float_amount * 0.2) ? 'danger' : 'success' }}">
                                TZS {{ number_format($unit->current_balance, 2) }}
                            </span>
                        </div>
                        @if($unit->maximum_limit)
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Max Limit:</span>
                            <span class="fw-bold">TZS {{ number_format($unit->maximum_limit, 2) }}</span>
                        </div>
                        @endif
                        @if($minimumBalanceTrigger)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Minimum Balance Trigger:</span>
                            <span class="fw-bold">TZS {{ number_format($minimumBalanceTrigger, 2) }}</span>
                        </div>
                        @endif
                        @if($isBelowMinimum)
                            <div class="alert alert-danger mt-3 mb-0">
                                <i class="bx bx-error-circle me-2"></i>
                                <strong>Low Balance Alert!</strong> Current balance is below the minimum balance trigger. Please request a replenishment.
                            </div>
                        @elseif(!$minimumBalanceTrigger && $unit->current_balance < ($unit->float_amount * 0.2))
                            <div class="alert alert-warning mt-3 mb-0">
                                <i class="bx bx-error-circle me-2"></i>
                                <strong>Low Balance!</strong> Consider requesting a replenishment.
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="card radius-10 mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bx bx-bolt me-2"></i>Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newTransactionModal">
                            <i class="bx bx-plus me-1"></i>New Expense
                        </button>
                            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#newReplenishmentModal">
                                <i class="bx bx-refresh me-1"></i>Request Replenishment
                            </button>
                            <a href="{{ route('accounting.petty-cash.transactions.index', ['petty_cash_unit_id' => $unit->id]) }}" class="btn btn-primary">
                                <i class="bx bx-list-ul me-1"></i>View All Transactions
                            </a>
                            <a href="{{ route('accounting.petty-cash.replenishments.index', ['petty_cash_unit_id' => $unit->id]) }}" class="btn btn-warning">
                                <i class="bx bx-history me-1"></i>View Replenishments
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Recent Transactions</h6>
                        <a href="{{ route('accounting.petty-cash.transactions.index', ['petty_cash_unit_id' => $unit->id]) }}" class="btn btn-sm btn-primary">
                            View All
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="transactions-table" class="table table-hover table-striped" style="width:100%">
                                <thead class="table-light">
                                    <tr>
                                        <th>Transaction #</th>
                                        <th>Date</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th class="text-end">Amount</th>
                                        <th>Status</th>
                                        <th class="text-end">Balance After</th>
                                        <th class="text-center">Actions</th>
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

        <!-- Recent Replenishments -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bx bx-history me-2"></i>Recent Replenishments</h6>
                        <a href="{{ route('accounting.petty-cash.replenishments.index', ['petty_cash_unit_id' => $unit->id]) }}" class="btn btn-sm btn-primary">
                            View All
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="replenishments-table" class="table table-hover table-striped" style="width:100%">
                                <thead class="table-light">
                                    <tr>
                                        <th>Replenishment #</th>
                                        <th>Request Date</th>
                                        <th class="text-end">Requested Amount</th>
                                        <th class="text-end">Approved Amount</th>
                                        <th>Status</th>
                                        <th>Requested By</th>
                                        <th class="text-center">Actions</th>
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

<!-- New Transaction Modal -->
<div class="modal fade" id="newTransactionModal" tabindex="-1" aria-labelledby="newTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="newTransactionModalLabel">
                    <i class="bx bx-plus me-2"></i>New Petty Cash Transaction
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="newTransactionForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="petty_cash_unit_id" value="{{ $unit->id }}">
                <div class="modal-body" style="max-height: 80vh; overflow-y: auto;">
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>
                        <strong>Current Balance:</strong> TZS {{ number_format($unit->current_balance, 2) }}
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="transaction_date" class="form-label">Transaction Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="transaction_date" name="transaction_date" 
                                   value="{{ date('Y-m-d') }}" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="payee_type" class="form-label">Payee Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="payee_type" name="payee_type" required>
                                <option value="">Select Payee Type</option>
                                <option value="customer">Customer</option>
                                <option value="supplier">Supplier</option>
                                <option value="employee">Employee</option>
                                <option value="other">Other</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Customer Selection -->
                        <div class="col-md-6" id="customerSection" style="display: none;">
                            <label for="customer_id" class="form-label">Select Customer <span class="text-danger">*</span></label>
                            <select class="form-select select2-single" id="customer_id" name="customer_id">
                                <option value="">Select Customer</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Supplier Selection -->
                        <div class="col-md-6" id="supplierSection" style="display: none;">
                            <label for="supplier_id" class="form-label">Select Supplier <span class="text-danger">*</span></label>
                            <select class="form-select select2-single" id="supplier_id" name="supplier_id">
                                <option value="">Select Supplier</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Employee Selection -->
                        <div class="col-md-6" id="employeeSection" style="display: none;">
                            <label for="employee_id" class="form-label">Select Employee <span class="text-danger">*</span></label>
                            <select class="form-select select2-single" id="employee_id" name="employee_id">
                                <option value="">Select Employee</option>
                                @foreach($employees ?? [] as $employee)
                                    <option value="{{ $employee->id }}">
                                        {{ $employee->full_name }}@if($employee->employee_number) ({{ $employee->employee_number }})@endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Other Payee Name -->
                        <div class="col-md-6" id="otherPayeeSection" style="display: none;">
                            <label for="payee_name" class="form-label">Payee Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="payee_name" name="payee_name" 
                                   placeholder="Enter payee name">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <!-- Line Items Section -->
                    <div class="card border-primary mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0 fw-bold">
                                <i class="bx bx-list-ul me-2"></i>Expense Accounts
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="lineItemsContainer">
                                <!-- Line items will be added here dynamically -->
                            </div>
                            <div class="text-left mt-3">
                                <button type="button" class="btn btn-success btn-sm" id="addLineBtn">
                                    <i class="bx bx-plus me-1"></i>Add Line
                                </button>
                            </div>
                            <div class="mt-3 text-end">
                                <h5 class="mb-0">
                                    <strong>Total Amount: <span class="text-danger" id="totalAmount">0.00</span> TZS</strong>
                                </h5>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      placeholder="Describe the expense..." required></textarea>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2" 
                                      placeholder="Additional notes (optional)"></textarea>
                        </div>

                        <div class="col-12">
                            <label for="receipt_attachment" class="form-label">Receipt Attachment</label>
                            <input type="file" class="form-control" id="receipt_attachment" name="receipt_attachment" 
                                   accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">Max file size: 5MB. Allowed: PDF, JPG, JPEG, PNG</small>
                        </div>

                        @if($unit->approval_threshold)
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <i class="bx bx-info-circle me-2"></i>
                                <strong>Approval Required:</strong> Expenses above TZS {{ number_format($unit->approval_threshold, 2) }} require supervisor approval.
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success" id="submitTransactionBtn">
                        <span class="btn-text">
                            <i class="bx bx-save me-1"></i>Create Transaction
                        </span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- New Replenishment Modal -->
<div class="modal fade" id="newReplenishmentModal" tabindex="-1" aria-labelledby="newReplenishmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="newReplenishmentModalLabel">
                    <i class="bx bx-refresh me-2"></i>Request Petty Cash Replenishment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="newReplenishmentForm">
                @csrf
                <input type="hidden" name="petty_cash_unit_id" value="{{ $unit->id }}">
                <div class="modal-body">
                    <div class="alert alert-info" id="replenishmentInfo">
                        <i class="bx bx-info-circle me-2"></i>
                        <strong>Current Balance:</strong> <span id="modalCurrentBalance">TZS {{ number_format($unit->current_balance, 2) }}</span> | 
                        <strong>Float Amount:</strong> TZS {{ number_format($unit->float_amount, 2) }}
                        @if($unit->maximum_limit)
                        | <strong>Maximum Limit:</strong> TZS {{ number_format($unit->maximum_limit, 2) }}
                        @endif
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="request_date" class="form-label">Request Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="request_date" name="request_date" 
                                   value="{{ date('Y-m-d') }}" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="requested_amount" class="form-label">Requested Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="requested_amount" name="requested_amount" 
                                   step="0.01" min="0.01" placeholder="0.00" required>
                            <div class="invalid-feedback"></div>
                            <small class="text-muted" id="requestedAmountHelp">Enter the amount you need to replenish</small>
                        </div>

                        <div class="col-md-12">
                            <label for="source_account_id" class="form-label">Source Bank Account</label>
                            <select class="form-select select2-single" id="source_account_id" name="source_account_id">
                                <option value="">Select Bank Account</option>
                            </select>
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">Select the bank account to transfer funds from (optional)</small>
                        </div>

                        <div class="col-md-12">
                            <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="reason" name="reason" rows="4" 
                                      placeholder="Explain why you need this replenishment..." required></textarea>
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">Provide a clear reason for the replenishment request</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-info" id="submitReplenishmentBtn">
                        <span class="btn-text">
                            <i class="bx bx-save me-1"></i>Submit Request
                        </span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Replenishment Modal -->
<div class="modal fade" id="editReplenishmentModal" tabindex="-1" aria-labelledby="editReplenishmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="editReplenishmentModalLabel">
                    <i class="bx bx-edit me-2"></i>Edit Petty Cash Replenishment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editReplenishmentForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="petty_cash_unit_id" value="{{ $unit->id }}">
                <input type="hidden" id="edit_replenishment_id" name="replenishment_id">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>
                        <strong>Current Balance:</strong> TZS {{ number_format($unit->current_balance, 2) }} | 
                        <strong>Float Amount:</strong> TZS {{ number_format($unit->float_amount, 2) }}
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_request_date" class="form-label">Request Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="edit_request_date" name="request_date" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_requested_amount" class="form-label">Requested Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_requested_amount" name="requested_amount" 
                                   step="0.01" min="0.01" placeholder="0.00" required>
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">Enter the amount you need to replenish</small>
                        </div>

                        <div class="col-md-12">
                            <label for="edit_source_account_id" class="form-label">Source Bank Account</label>
                            <select class="form-select select2-single" id="edit_source_account_id" name="source_account_id">
                                <option value="">Select Bank Account</option>
                            </select>
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">Select the bank account to transfer funds from (optional)</small>
                        </div>

                        <div class="col-md-12">
                            <label for="edit_reason" class="form-label">Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="edit_reason" name="reason" rows="4" 
                                      placeholder="Explain why you need this replenishment..." required></textarea>
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">Provide a clear reason for the replenishment request</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-warning" id="updateReplenishmentBtn">
                        <span class="btn-text">
                            <i class="bx bx-save me-1"></i>Update Request
                        </span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
    .line-item-row {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        border: 1px solid #dee2e6;
    }
    .line-item-row:hover {
        background: #e9ecef;
        border-color: #adb5bd;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    const unitId = {{ $unit->id }};
    const currentBalance = {{ $unit->current_balance }};
    const approvalThreshold = {{ $unit->approval_threshold ?? 0 }};
    const minimumBalanceTrigger = {{ $minimumBalanceTrigger ?? 'null' }};
    let lineItemCount = 0;

    // Show notification if balance is below minimum threshold
    if (minimumBalanceTrigger !== null && currentBalance < minimumBalanceTrigger) {
        Swal.fire({
            icon: 'warning',
            title: 'Low Balance Alert!',
            html: `
                <p>The current balance (<strong>TZS ${currentBalance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>) is below the minimum balance trigger (<strong>TZS ${minimumBalanceTrigger.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>).</p>
                <p class="mb-0">Please request a replenishment to restore the balance.</p>
            `,
            confirmButtonText: 'Request Replenishment',
            confirmButtonColor: '#d33',
            showCancelButton: true,
            cancelButtonText: 'Close',
            footer: '<a href="javascript:void(0);" id="swalReplenishmentLink" style="color: #d33; text-decoration: underline; cursor: pointer;">Click here to request replenishment</a>',
            didOpen: () => {
                // Add click handler for footer link after SweetAlert opens
                const link = document.getElementById('swalReplenishmentLink');
                if (link) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        Swal.close().then(() => {
                            // Wait a bit for SweetAlert to fully close before opening modal
                            setTimeout(() => {
                                $('#requestReplenishmentModal').modal('show');
                            }, 300);
                        });
                    });
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Wait a bit for SweetAlert to fully close before opening modal
                setTimeout(() => {
                    $('#requestReplenishmentModal').modal('show');
                }, 300);
            }
        });
    }

    // Load data when modal opens
    $('#newTransactionModal').on('show.bs.modal', function() {
        // Load accounts first, then add line item after accounts are loaded
        loadChartAccounts(function() {
            addLineItem(); // Add first line item after accounts are loaded
        });
        loadCustomers();
        loadSuppliers();
    });

    // Handle payee type selection
    $('#payee_type').on('change', function() {
        const payeeType = $(this).val();
        
        // Hide all sections first
        $('#customerSection, #supplierSection, #employeeSection, #otherPayeeSection').hide();
        
        // Reset required attributes and disable all fields
        $('#customer_id, #supplier_id, #employee_id, #payee_name').prop('required', false).prop('disabled', true);
        
        // Show relevant section based on selection
        if (payeeType === 'customer') {
            $('#customerSection').show();
            $('#customer_id').prop('required', true).prop('disabled', false);
        } else if (payeeType === 'supplier') {
            $('#supplierSection').show();
            $('#supplier_id').prop('required', true).prop('disabled', false);
        } else if (payeeType === 'employee') {
            $('#employeeSection').show();
            $('#employee_id').prop('required', true).prop('disabled', false);
        } else if (payeeType === 'other') {
            $('#otherPayeeSection').show();
            $('#payee_name').prop('required', true).prop('disabled', false);
        }
    });

    // Load chart accounts (expense accounts)
    function loadChartAccounts(callback) {
        $.ajax({
            url: '{{ route("accounting.petty-cash.transactions.expense-accounts") }}',
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(accounts) {
                console.log('Loaded expense accounts:', accounts);
                window.chartAccounts = accounts;
                
                // Populate all existing chart account selects
                $('.chart-account-select').each(function() {
                    const select = $(this);
                    if (select.find('option').length <= 1) { // Only has "Select Account" option
                        accounts.forEach(function(account) {
                            select.append($('<option>', {
                                value: account.id,
                                text: (account.display || account.account_code + ' - ' + account.account_name)
                            }));
                        });
                    }
                });
                
                if (callback) callback();
            },
            error: function(xhr, status, error) {
                console.error('Error loading chart accounts:', error, xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load expense accounts. Please refresh the page and try again.'
                });
            }
        });
    }

    // Load customers
    function loadCustomers() {
        $.ajax({
            url: '{{ url("api/customers") }}',
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(customers) {
                const select = $('#customer_id');
                select.empty().append('<option value="">Select Customer</option>');
                
                customers.forEach(function(customer) {
                    select.append($('<option>', {
                        value: customer.id,
                        text: customer.name + (customer.customer_no ? ' (' + customer.customer_no + ')' : '')
                    }));
                });

                if (typeof $().select2 !== 'undefined') {
                    select.select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        dropdownParent: $('#newTransactionModal')
                    });
                }
            },
            error: function() {
                console.error('Error loading customers');
            }
        });
    }

    // Load suppliers
    function loadSuppliers() {
        $.ajax({
            url: '{{ url("api/suppliers") }}',
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(suppliers) {
                const select = $('#supplier_id');
                select.empty().append('<option value="">Select Supplier</option>');
                
                suppliers.forEach(function(supplier) {
                    select.append($('<option>', {
                        value: supplier.id,
                        text: supplier.name
                    }));
                });

                if (typeof $().select2 !== 'undefined') {
                    select.select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        dropdownParent: $('#newTransactionModal')
                    });
                }
            },
            error: function() {
                console.error('Error loading suppliers');
            }
        });
    }

    // Add line item
    function addLineItem() {
        lineItemCount++;
        const lineItemHtml = `
            <div class="line-item-row" data-line-index="${lineItemCount}">
                <div class="row">
                    <div class="col-md-5 mb-2">
                        <label class="form-label fw-bold">Select Account <span class="text-danger">*</span></label>
                        <select class="form-select chart-account-select select2-single" 
                                name="line_items[${lineItemCount}][chart_account_id]" required>
                            <option value="">Select Account</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label fw-bold">Description</label>
                        <input type="text" class="form-control description-input" 
                               name="line_items[${lineItemCount}][description]" 
                               placeholder="Enter description">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label fw-bold">Amount <span class="text-danger">*</span></label>
                        <input type="number" class="form-control amount-input" 
                               name="line_items[${lineItemCount}][amount]" 
                               step="0.01" min="0.01" placeholder="0.00" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-1 mb-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-line-btn" title="Remove Line">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

        $('#lineItemsContainer').append(lineItemHtml);
        
        // Populate chart accounts for this select
        const newSelect = $('#lineItemsContainer .chart-account-select').last();
        if (window.chartAccounts && window.chartAccounts.length > 0) {
            window.chartAccounts.forEach(function(account) {
                newSelect.append($('<option>', {
                    value: account.id,
                    text: account.display || (account.account_code + ' - ' + account.account_name)
                }));
            });
        } else {
            // If accounts not loaded yet, load them
            loadChartAccounts(function() {
                if (window.chartAccounts && window.chartAccounts.length > 0) {
                    window.chartAccounts.forEach(function(account) {
                        newSelect.append($('<option>', {
                            value: account.id,
                            text: account.display || (account.account_code + ' - ' + account.account_name)
                        }));
                    });
                    // Re-initialize Select2 after populating
                    if (typeof $().select2 !== 'undefined') {
                        newSelect.select2({
                            theme: 'bootstrap-5',
                            width: '100%',
                            dropdownParent: $('#newTransactionModal')
                        });
                    }
                }
            });
        }

        // Initialize Select2 for the new select
        if (typeof $().select2 !== 'undefined') {
            newSelect.select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $('#newTransactionModal')
            });
        }
    }

    // Add line item button
    $('#addLineBtn').on('click', function() {
        // Ensure accounts are loaded before adding line item
        if (!window.chartAccounts || window.chartAccounts.length === 0) {
            loadChartAccounts(function() {
                addLineItem();
            });
        } else {
            addLineItem();
        }
    });

    // Remove line item
    $(document).on('click', '.remove-line-btn', function() {
        if ($('.line-item-row').length > 1) {
            $(this).closest('.line-item-row').remove();
            calculateTotal();
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Cannot Remove',
                text: 'At least one line item is required.'
            });
        }
    });

    // Calculate total when amounts change
    $(document).on('input', '.amount-input', function() {
        calculateTotal();
    });

    // Calculate total amount
    function calculateTotal() {
        let total = 0;
        $('.amount-input').each(function() {
            const amount = parseFloat($(this).val()) || 0;
            total += amount;
        });
        $('#totalAmount').text(total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ","));
    }

    // Handle form submission - use off() first to prevent duplicate bindings
    $('#newTransactionForm').off('submit').on('submit', function(e) {
        e.preventDefault();
        
        // Prevent double submission
        const form = $(this);
        if (form.data('submitting')) {
            return false;
        }
        form.data('submitting', true);
        
        const submitBtn = $('#submitTransactionBtn');
        const btnText = submitBtn.find('.btn-text');
        const spinner = submitBtn.find('.spinner-border');
        
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').empty();
        
        // Validate line items
        const lineItems = [];
        let totalAmount = 0;
        let hasErrors = false;

        $('.line-item-row').each(function() {
            const accountId = $(this).find('.chart-account-select').val();
            const amount = parseFloat($(this).find('.amount-input').val()) || 0;
            const description = $(this).find('.description-input').val();

            if (!accountId) {
                $(this).find('.chart-account-select').addClass('is-invalid');
                $(this).find('.chart-account-select').next('.invalid-feedback').text('Please select an account.');
                hasErrors = true;
            }

            if (amount <= 0) {
                $(this).find('.amount-input').addClass('is-invalid');
                $(this).find('.amount-input').next('.invalid-feedback').text('Amount must be greater than 0.');
                hasErrors = true;
            }

            if (accountId && amount > 0) {
                lineItems.push({
                    chart_account_id: accountId,
                    amount: amount,
                    description: description || ''
                });
                totalAmount += amount;
            }
        });

        if (lineItems.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please add at least one expense account line item.'
            });
            return;
        }

        if (hasErrors) {
            return;
        }

        // Validate total amount
        if (totalAmount > currentBalance) {
            Swal.fire({
                icon: 'error',
                title: 'Insufficient Balance',
                text: 'Total amount exceeds available balance of TZS ' + currentBalance.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")
            });
            return;
        }
        
        // Disable submit button
        submitBtn.prop('disabled', true);
        btnText.addClass('d-none');
        spinner.removeClass('d-none');
        
        // Prepare form data
        // First, get all form data
        const originalFormData = new FormData(this);
        
        // Create new FormData and copy only non-line-item fields
        // This prevents duplicate line items (form fields + manual append)
        const formData = new FormData();
        for (const [key, value] of originalFormData.entries()) {
            // Skip line_items entries - we'll add them manually below
            if (!key.startsWith('line_items[')) {
                formData.append(key, value);
            }
        }
        
        // Generate unique request ID to prevent duplicate submissions
        const requestId = 'pct_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        formData.append('_request_id', requestId);
        
        // Add line items to form data (manually to ensure no duplicates)
        lineItems.forEach(function(item, index) {
            formData.append(`line_items[${index}][chart_account_id]`, item.chart_account_id);
            formData.append(`line_items[${index}][amount]`, item.amount);
            if (item.description) {
                formData.append(`line_items[${index}][description]`, item.description);
            }
        });
        
        $.ajax({
            url: '{{ route("accounting.petty-cash.transactions.store") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Request-ID': requestId
            },
            success: function(response) {
                if (response.success) {
                    $('#newTransactionModal').modal('hide');
                    form[0].reset();
                    $('#lineItemsContainer').empty();
                    lineItemCount = 0;
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 3000,
                        showConfirmButton: false
                    }).then(function() {
                        // Reload page to show updated balance and new transaction
                        window.location.reload();
                    });
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    // Validation errors
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        if (field.includes('line_items')) {
                            // Handle line item errors
                            const match = field.match(/line_items\.(\d+)\.(\w+)/);
                            if (match) {
                                const lineIndex = match[1];
                                const fieldName = match[2];
                                const lineRow = $(`.line-item-row[data-line-index="${lineIndex}"]`);
                                if (fieldName === 'chart_account_id') {
                                    lineRow.find('.chart-account-select').addClass('is-invalid');
                                    lineRow.find('.chart-account-select').next('.invalid-feedback').text(messages[0]);
                                } else if (fieldName === 'amount') {
                                    lineRow.find('.amount-input').addClass('is-invalid');
                                    lineRow.find('.amount-input').next('.invalid-feedback').text(messages[0]);
                                }
                            }
                        } else {
                            const input = $(`[name="${field}"]`);
                            input.addClass('is-invalid');
                            const feedback = input.next('.invalid-feedback');
                            if (feedback.length === 0) {
                                input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                            } else {
                                feedback.text(messages[0]);
                            }
                        }
                    });
                } else {
                    let message = 'An error occurred while creating the transaction.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: message
                    });
                }
            },
            complete: function() {
                // Reset submitting flag
                form.data('submitting', false);
                submitBtn.prop('disabled', false);
                btnText.removeClass('d-none');
                spinner.removeClass('d-none');
            }
        });
    });

    // Reset modal on hide
    $('#newTransactionModal').on('hidden.bs.modal', function() {
        $('#newTransactionForm')[0].reset();
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').empty();
        $('#lineItemsContainer').empty();
        lineItemCount = 0;
        $('#customerSection, #supplierSection, #employeeSection, #otherPayeeSection').hide();
        $('#customer_id, #supplier_id, #employee_id, #payee_name').prop('required', false).prop('disabled', true);
        $('#transaction_date').val('{{ date('Y-m-d') }}');
        $('#totalAmount').text('0.00');
    });

    // Initialize Transactions DataTable
    var transactionsTable = $('#transactions-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("accounting.petty-cash.units.transactions", $unit->encoded_id) }}',
            type: 'GET'
        },
        columns: [
            {data: 'transaction_number_link', name: 'transaction_number', orderable: true, searchable: true},
            {data: 'formatted_date', name: 'transaction_date', orderable: true, searchable: false},
            {data: 'category_name', name: 'expenseCategory.name', orderable: false, searchable: false},
            {data: 'description_with_payee', name: 'description', orderable: true, searchable: true},
            {data: 'formatted_amount', name: 'amount', orderable: true, searchable: false},
            {data: 'status_badge', name: 'status', orderable: true, searchable: true},
            {data: 'formatted_balance_after', name: 'balance_after', orderable: true, searchable: false},
            {data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center'}
        ],
        order: [[1, 'desc']], // Order by date descending
        pageLength: 10,
        language: {
            processing: '<i class="bx bx-loader-alt bx-spin"></i> Loading...',
            emptyTable: 'No transactions found',
            zeroRecords: 'No matching transactions found'
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        drawCallback: function(settings) {
            // Any additional callbacks after table draw
        }
    });

    // Post transaction to GL function
    function postTransactionToGL(encodedId) {
        Swal.fire({
            title: 'Post to GL?',
            text: "This will post the transaction to General Ledger. Continue?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, post it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("accounting/petty-cash/transactions") }}/' + encodedId + '/post-to-gl',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Posted!',
                            text: 'Transaction posted to GL successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(function() {
                            transactionsTable.ajax.reload(null, false);
                            // Reload page to update balance
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        let message = 'Failed to post transaction to GL.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.error) {
                            message = xhr.responseJSON.error;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: message
                        });
                    }
                });
            }
        });
    }

    // Make postTransactionToGL available globally
    window.postTransactionToGL = postTransactionToGL;

    // Delete transaction function
    function deleteTransaction(encodedId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("accounting/petty-cash/transactions") }}/' + encodedId,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(function() {
                                transactionsTable.ajax.reload(null, false);
                                // Reload page to update balance
                                window.location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        let message = 'Failed to delete transaction.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: message
                        });
                    }
                });
            }
        });
    }

    // Make deleteTransaction available globally
    window.deleteTransaction = deleteTransaction;

    // Approve transaction function
    function approveTransaction(encodedId) {
        Swal.fire({
            title: 'Approve Transaction?',
            text: "This will approve the transaction and post it to GL. Continue?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, approve it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("accounting/petty-cash/transactions") }}/' + encodedId + '/approve',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Approved!',
                            text: 'Transaction approved and posted to GL successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(function() {
                            transactionsTable.ajax.reload(null, false);
                            // Reload page to update balance
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        let message = 'Failed to approve transaction.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.error) {
                            message = xhr.responseJSON.error;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            message = Object.values(xhr.responseJSON.errors).flat().join(', ');
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: message
                        });
                    }
                });
            }
        });
    }

    // Reject transaction function
    function rejectTransaction(encodedId) {
        Swal.fire({
            title: 'Reject Transaction?',
            text: "Please provide a reason for rejecting this transaction.",
            icon: 'warning',
            input: 'textarea',
            inputLabel: 'Rejection Reason',
            inputPlaceholder: 'Enter the reason for rejection (minimum 10 characters)...',
            inputAttributes: {
                'aria-label': 'Enter the reason for rejection'
            },
            inputValidator: (value) => {
                if (!value || value.length < 10) {
                    return 'Please provide a rejection reason (minimum 10 characters)';
                }
            },
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Reject',
            cancelButtonText: 'Cancel',
            showLoaderOnConfirm: true,
            preConfirm: (rejectionReason) => {
                return fetch('{{ url("accounting/petty-cash/transactions") }}/' + encodedId + '/reject', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        rejection_reason: rejectionReason
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to reject transaction.');
                    }
                    return data;
                })
                .catch(error => {
                    Swal.showValidationMessage('Request failed: ' + error.message);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: result.value.message || 'Transaction rejected successfully.',
                    timer: 3000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            }
        });
    }

    // Make functions available globally
    window.approveTransaction = approveTransaction;
    window.rejectTransaction = rejectTransaction;

    // Initialize Replenishments DataTable
    var replenishmentsTable = $('#replenishments-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("accounting.petty-cash.units.replenishments", $unit->encoded_id) }}',
            type: 'GET'
        },
        columns: [
            {data: 'replenishment_number_link', name: 'replenishment_number', orderable: true, searchable: true},
            {data: 'formatted_request_date', name: 'request_date', orderable: true, searchable: false},
            {data: 'formatted_requested_amount', name: 'requested_amount', orderable: true, searchable: false},
            {data: 'formatted_approved_amount', name: 'approved_amount', orderable: true, searchable: false},
            {data: 'status_badge', name: 'status', orderable: true, searchable: true},
            {data: 'requested_by_name', name: 'requestedBy.name', orderable: false, searchable: false},
            {data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center'}
        ],
        order: [[1, 'desc']], // Order by request date descending
        pageLength: 10,
        language: {
            processing: '<i class="bx bx-loader-alt bx-spin"></i> Loading...',
            emptyTable: 'No replenishments found',
            zeroRecords: 'No matching replenishments found'
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        drawCallback: function(settings) {
            // Any additional callbacks after table draw
        }
    });
});

// Replenishment Modal Scripts
$(document).ready(function() {
    const unitId = {{ $unit->id }};
    const currentBalance = {{ $unit->current_balance }};
    const floatAmount = {{ $unit->float_amount }};

    // Load bank accounts when modal opens
    $('#newReplenishmentModal').on('show.bs.modal', function() {
        loadBankAccounts();
        
        // Use the server-side current balance value directly (avoid parsing from DOM which can have precision issues)
        const currentBalance = {{ $unit->current_balance }};
        const floatAmount = {{ $unit->float_amount }};
        const unitMaximumLimit = {{ $unit->maximum_limit ?? 0 }};
        const systemMaximumLimit = {{ $systemMaximumLimit ?? 0 }};
        // Use unit's maximum_limit if set, otherwise fall back to system's maximum_limit
        const maximumLimit = unitMaximumLimit > 0 ? unitMaximumLimit : systemMaximumLimit;
        
        // Calculate default requested amount: difference between float and current balance
        // This will reset the balance back to the float amount
        let defaultAmount = floatAmount - currentBalance;
        
        // Ensure it's not negative
        if (defaultAmount < 0) {
            defaultAmount = 0;
        }
        
        // If maximum_limit is set, ensure requested amount doesn't exceed it
        // The maximum allowed is: maximum_limit - current_balance
        if (maximumLimit > 0) {
            const maxAllowed = maximumLimit - currentBalance;
            if (maxAllowed < defaultAmount) {
                defaultAmount = maxAllowed > 0 ? maxAllowed : 0;
            }
        }
        
        // Reset form
        $('#newReplenishmentForm')[0].reset();
        $('#newReplenishmentForm .invalid-feedback').text('');
        $('#newReplenishmentForm .is-invalid').removeClass('is-invalid');
        
        // Set default requested amount
        $('#requested_amount').val(defaultAmount.toFixed(2));
        
        // Update help text
        let helpText = 'Enter the amount you need to replenish';
        if (defaultAmount > 0) {
            helpText += ' (Default: ' + defaultAmount.toFixed(2) + ' to reset balance to float amount)';
        }
        if (maximumLimit > 0) {
            const maxAllowed = maximumLimit - currentBalance;
            if (maxAllowed > 0) {
                helpText += ' (Max: ' + maxAllowed.toFixed(2) + ')';
            } else {
                helpText += ' (Maximum limit reached)';
            }
        }
        $('#requestedAmountHelp').text(helpText);
        
        // Update modal current balance display using the exact server-side value
        $('#modalCurrentBalance').text('TZS ' + currentBalance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    });

    // Load bank accounts
    function loadBankAccounts() {
        $.ajax({
            url: '{{ route("accounting.petty-cash.replenishments.bank-accounts") }}',
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                const select = $('#source_account_id');
                select.empty();
                select.append('<option value="">Select Bank Account</option>');
                
                response.forEach(function(account) {
                    select.append(new Option(account.display, account.id));
                });
                
                // Initialize Select2 if not already initialized
                if (!select.hasClass('select2-hidden-accessible')) {
                    select.select2({
                        dropdownParent: $('#newReplenishmentModal'),
                        placeholder: 'Select Bank Account',
                        allowClear: true
                    });
                } else {
                    select.trigger('change');
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load bank accounts.'
                });
            }
        });
    }

    // Handle form submission
    $('#newReplenishmentForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = $('#submitReplenishmentBtn');
        const btnText = submitBtn.find('.btn-text');
        const spinner = submitBtn.find('.spinner-border');
        
        // Get current values for validation (use server-side value to avoid precision issues)
        const currentBalance = {{ $unit->current_balance }};
        const floatAmount = {{ $unit->float_amount }};
        const unitMaximumLimit = {{ $unit->maximum_limit ?? 0 }};
        const systemMaximumLimit = {{ $systemMaximumLimit ?? 0 }};
        // Use unit's maximum_limit if set, otherwise fall back to system's maximum_limit
        const maximumLimit = unitMaximumLimit > 0 ? unitMaximumLimit : systemMaximumLimit;
        const requestedAmount = parseFloat($('#requested_amount').val()) || 0;
        
        // Validate requested amount
        if (requestedAmount <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Requested amount must be greater than 0.'
            });
            $('#requested_amount').addClass('is-invalid');
            $('#requested_amount').next('.invalid-feedback').text('Requested amount must be greater than 0.');
            return;
        }
        
        // Validate against maximum limit if set
        if (maximumLimit > 0) {
            const newBalance = currentBalance + requestedAmount;
            if (newBalance > maximumLimit) {
                const maxAllowed = maximumLimit - currentBalance;
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Requested amount exceeds maximum limit. Maximum allowed: ' + maxAllowed.toFixed(2) + ' (Current balance: ' + currentBalance.toFixed(2) + ', Maximum limit: ' + maximumLimit.toFixed(2) + ')'
                });
                $('#requested_amount').addClass('is-invalid');
                $('#requested_amount').next('.invalid-feedback').text('Requested amount cannot exceed maximum limit of ' + maximumLimit.toFixed(2) + '. Maximum allowed: ' + maxAllowed.toFixed(2));
                return;
            }
        }
        
        // Validate form
        if (!form[0].checkValidity()) {
            form[0].reportValidity();
            return;
        }
        
        // Disable submit button
        submitBtn.prop('disabled', true);
        btnText.hide();
        spinner.removeClass('d-none');
        
        // Create FormData
        const formData = new FormData(form[0]);
        
        $.ajax({
            url: '{{ route("accounting.petty-cash.replenishments.store") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message || 'Replenishment request created successfully.',
                    confirmButtonColor: '#198754'
                }).then(() => {
                    // Close modal
                    $('#newReplenishmentModal').modal('hide');
                    // Reload replenishments table
                    if (typeof replenishmentsTable !== 'undefined') {
                        replenishmentsTable.ajax.reload();
                    }
                    // Reload page to update balance
                    window.location.reload();
                });
            },
            error: function(xhr) {
                let message = 'Failed to create replenishment request.';
                
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.errors) {
                        const errors = Object.values(xhr.responseJSON.errors).flat();
                        message = errors.join('<br>');
                    }
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    html: message,
                    confirmButtonColor: '#dc3545'
                });
                
                // Show validation errors
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    Object.keys(xhr.responseJSON.errors).forEach(function(key) {
                        const field = form.find('[name="' + key + '"]');
                        field.addClass('is-invalid');
                        field.siblings('.invalid-feedback').text(xhr.responseJSON.errors[key][0]);
                    });
                }
            },
            complete: function() {
                // Re-enable submit button
                submitBtn.prop('disabled', false);
                btnText.show();
                spinner.addClass('d-none');
            }
        });
    });

    // Replenishment Action Functions
    function approveReplenishment(encodedId) {
        Swal.fire({
            title: 'Approve Replenishment?',
            text: "This will approve the replenishment request and post it to GL. Continue?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, approve it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("accounting/petty-cash/replenishments") }}/' + encodedId + '/approve',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Approved!',
                            text: response.message || 'Replenishment approved and posted to GL successfully.',
                            confirmButtonColor: '#198754'
                        }).then(() => {
                            // Reload the page to show updated status and view button
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        let message = 'Failed to approve replenishment.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: message
                        });
                    }
                });
            }
        });
    }

    function rejectReplenishment(encodedId) {
        Swal.fire({
            title: 'Reject Replenishment',
            html: `
                <div class="text-start">
                    <p class="mb-3">Please provide a reason for rejecting this replenishment request:</p>
                    <textarea id="swal-rejection_reason" class="form-control" rows="4" placeholder="Enter rejection reason (minimum 10 characters)..." style="min-height: 100px;"></textarea>
                    <small class="text-muted mt-2 d-block">Minimum 10 characters required</small>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Reject',
            cancelButtonText: 'Cancel',
            didOpen: () => {
                const textarea = document.getElementById('swal-rejection_reason');
                textarea.focus();
            },
            preConfirm: () => {
                const reason = document.getElementById('swal-rejection_reason').value;
                if (!reason || reason.trim().length < 10) {
                    Swal.showValidationMessage('Please provide a rejection reason (minimum 10 characters)');
                    return false;
                }
                return reason.trim();
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                $.ajax({
                    url: '{{ url("accounting/petty-cash/replenishments") }}/' + encodedId + '/reject',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    data: {
                        rejection_reason: result.value
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Rejected!',
                            text: response.message || 'Replenishment rejected successfully.',
                            confirmButtonColor: '#dc3545'
                        }).then(() => {
                            // Reload the page to show updated status
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        let message = 'Failed to reject replenishment.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            message = Object.values(errors).flat().join(', ');
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: message
                        });
                    }
                });
            }
        });
    }

    function editReplenishment(encodedId) {
        // Store encoded ID for form submission
        $('#editReplenishmentForm').data('encoded-id', encodedId);
        $('#edit_replenishment_id').data('encoded-id', encodedId);
        
        // Show loading
        Swal.fire({
            title: 'Loading...',
            text: 'Please wait while we load the replenishment details.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Load replenishment data
        $.ajax({
            url: '{{ url("accounting/petty-cash/replenishments") }}/' + encodedId + '/edit',
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                Swal.close();
                
                if (response.success && response.replenishment) {
                    const r = response.replenishment;
                    
                    // Populate form fields
                    $('#edit_replenishment_id').val(r.id);
                    $('#edit_request_date').val(r.request_date);
                    $('#edit_requested_amount').val(r.requested_amount);
                    $('#edit_reason').val(r.reason);
                    
                    // Load bank accounts and set selected value
                    loadBankAccountsForEdit(function() {
                        if (r.source_account_id) {
                            $('#edit_source_account_id').val(r.source_account_id).trigger('change');
                        }
                    });
                    
                    // Open edit modal
                    $('#editReplenishmentModal').modal('show');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to load replenishment details.'
                    });
                }
            },
            error: function(xhr) {
                Swal.close();
                let message = 'Failed to load replenishment details.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: message
                });
            }
        });
    }

    // Load bank accounts for edit modal
    function loadBankAccountsForEdit(callback) {
        $.ajax({
            url: '{{ route("accounting.petty-cash.replenishments.bank-accounts") }}',
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                const select = $('#edit_source_account_id');
                select.empty();
                select.append('<option value="">Select Bank Account</option>');
                
                response.forEach(function(account) {
                    select.append(new Option(account.display, account.id));
                });
                
                // Initialize Select2 if not already initialized
                if (!select.hasClass('select2-hidden-accessible')) {
                    select.select2({
                        dropdownParent: $('#editReplenishmentModal'),
                        placeholder: 'Select Bank Account',
                        allowClear: true
                    });
                } else {
                    select.trigger('change');
                }
                
                if (callback) callback();
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load bank accounts.'
                });
            }
        });
    }

    // Handle edit form submission
    $('#editReplenishmentForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = $('#updateReplenishmentBtn');
        const btnText = submitBtn.find('.btn-text');
        const spinner = submitBtn.find('.spinner-border');
        const encodedId = form.data('encoded-id');
        
        if (!encodedId) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Replenishment ID not found.'
            });
            return;
        }
        
        // Validate form
        if (!form[0].checkValidity()) {
            form[0].reportValidity();
            return;
        }
        
        // Disable submit button
        submitBtn.prop('disabled', true);
        btnText.hide();
        spinner.removeClass('d-none');
        
        // Create FormData
        const formData = new FormData(form[0]);
        
        $.ajax({
            url: '{{ url("accounting/petty-cash/replenishments") }}/' + encodedId,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-HTTP-Method-Override': 'PUT',
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    text: response.message || 'Replenishment updated successfully.',
                    confirmButtonColor: '#198754'
                }).then(() => {
                    // Close modal
                    $('#editReplenishmentModal').modal('hide');
                    // Reload replenishments table
                    if (typeof replenishmentsTable !== 'undefined') {
                        replenishmentsTable.ajax.reload();
                    }
                });
            },
            error: function(xhr) {
                let message = 'Failed to update replenishment request.';
                
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.errors) {
                        const errors = Object.values(xhr.responseJSON.errors).flat();
                        message = errors.join('<br>');
                    }
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    html: message,
                    confirmButtonColor: '#dc3545'
                });
                
                // Show validation errors
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    Object.keys(xhr.responseJSON.errors).forEach(function(key) {
                        const field = form.find('[name="' + key + '"]');
                        field.addClass('is-invalid');
                        field.siblings('.invalid-feedback').text(xhr.responseJSON.errors[key][0]);
                    });
                }
            },
            complete: function() {
                // Re-enable submit button
                submitBtn.prop('disabled', false);
                btnText.show();
                spinner.addClass('d-none');
            }
        });
    });

    // Reset edit form when modal is closed
    $('#editReplenishmentModal').on('hidden.bs.modal', function() {
        $('#editReplenishmentForm')[0].reset();
        $('#editReplenishmentForm .invalid-feedback').text('');
        $('#editReplenishmentForm .is-invalid').removeClass('is-invalid');
        $('#edit_source_account_id').val(null).trigger('change');
        $('#editReplenishmentForm').removeData('encoded-id');
    });

    function deleteReplenishment(encodedId) {
        Swal.fire({
            title: 'Delete Replenishment?',
            text: "Are you sure you want to delete this replenishment request? This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("accounting/petty-cash/replenishments") }}/' + encodedId,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Replenishment has been deleted.',
                            confirmButtonColor: '#198754'
                        }).then(() => {
                            replenishmentsTable.ajax.reload();
                        });
                    },
                    error: function(xhr) {
                        let message = 'Failed to delete replenishment.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: message
                        });
                    }
                });
            }
        });
    }

    // Make functions available globally
    window.approveReplenishment = approveReplenishment;
    window.rejectReplenishment = rejectReplenishment;
    window.editReplenishment = editReplenishment;
    window.deleteReplenishment = deleteReplenishment;
});
</script>
@endpush
@endsection

