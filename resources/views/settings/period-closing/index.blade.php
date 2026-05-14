@extends('layouts.main')

@section('title', 'Period-End Closing')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Settings', 'url' => route('settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Period-End Closing', 'url' => '#', 'icon' => 'bx bx-calendar-check']
        ]" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">PERIOD-END CLOSING</h6>
           
        </div>
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

        <div class="alert alert-info border-0">
            <div class="d-flex align-items-center">
                <i class="bx bx-info-circle fs-4 me-3"></i>
                <div>
                    <h6 class="mb-1">Period-End Closing System</h6>
                    <p class="mb-1">
                        Manage fiscal years, accounting periods, and period closing workflows. 
                        Create close batches, add adjusting entries, and lock periods to prevent new transactions.
                    </p>
                    <p class="mb-0">
                        <strong>Key Features:</strong> Pre-close checklist validation, immutable period snapshots, 
                        year-end retained earnings roll, and controlled period reopening.
                    </p>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Fiscal Years</h6>
                                <h4 class="mb-0 text-primary">{{ $fiscalYears->count() }}</h4>
                            </div>
                            <div class="widgets-icons bg-gradient-primary text-white">
                                <i class='bx bx-calendar'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Current Period</h6>
                                <h5 class="mb-0 text-warning">
                                    {{ $currentPeriod ? $currentPeriod->period_label : 'N/A' }}
                                </h5>
                            </div>
                            <div class="widgets-icons bg-gradient-warning text-white">
                                <i class='bx bx-time'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="text-muted mb-1">Pending Batches</h6>
                                <h4 class="mb-0 text-info">{{ $pendingBatches->count() }}</h4>
                            </div>
                            <div class="widgets-icons bg-gradient-info text-white">
                                <i class='bx bx-file'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Fiscal Years -->
            <div class="col-12 col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-transparent border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="bx bx-calendar me-2"></i>Fiscal Years</h6>
                            <div class="btn-group">
                                <a href="{{ route('settings.period-closing.periods') }}" class="btn btn-sm btn-info">
                                    <i class="bx bx-list-ul me-1"></i> View Periods
                                </a>
                                <a href="{{ route('settings.period-closing.fiscal-years') }}" class="btn btn-sm btn-primary">
                                    <i class="bx bx-plus me-1"></i> Manage
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @forelse($fiscalYears->take(5) as $fy)
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div>
                                    <h6 class="mb-1">{{ $fy->fy_label }}</h6>
                                    <small class="text-muted">
                                        {{ $fy->start_date->format('M d, Y') }} - {{ $fy->end_date->format('M d, Y') }}
                                    </small>
                                </div>
                                <span class="badge bg-{{ $fy->status === 'OPEN' ? 'success' : 'secondary' }}">
                                    {{ $fy->status }}
                                </span>
                            </div>
                        @empty
                            <p class="text-muted text-center mb-0">No fiscal years configured</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Pending Close Batches -->
            <div class="col-12 col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-file me-2"></i>Pending Close Batches</h6>
                    </div>
                    <div class="card-body">
                        @forelse($pendingBatches as $batch)
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div>
                                    <h6 class="mb-1">{{ $batch->batch_label }}</h6>
                                    <small class="text-muted">
                                        Period: {{ $batch->period->period_label }} | 
                                        Prepared: {{ $batch->preparedBy->name ?? 'N/A' }}
                                    </small>
                                </div>
                                <div>
                                    <span class="badge bg-{{ $batch->status === 'DRAFT' ? 'warning' : 'info' }} me-2">
                                        {{ $batch->status }}
                                    </span>
                                    <a href="{{ route('settings.period-closing.close-batch.show', $batch) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bx bx-show"></i>
                                    </a>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted text-center mb-0">No pending close batches</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-cog me-2"></i>Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <a href="{{ route('settings.period-closing.fiscal-years') }}" class="btn btn-primary w-100">
                                    <i class="bx bx-calendar me-1"></i> Manage Fiscal Years
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="{{ route('settings.period-closing.periods') }}" class="btn btn-info w-100">
                                    <i class="bx bx-list-ul me-1"></i> View All Periods
                                </a>
                            </div>
                            @if($currentPeriod)
                            <div class="col-md-3 mb-3">
                                <a href="{{ route('settings.period-closing.close-batch.create', $currentPeriod) }}" class="btn btn-success w-100">
                                    <i class="bx bx-plus me-1"></i> Create Close Batch
                                </a>
                            </div>
                            @endif
                            <div class="col-md-3 mb-3">
                                <a href="{{ route('settings.period-closing.download-guide') }}" class="btn btn-primary w-100">
                                    <i class="bx bx-download me-1"></i> Download Guide
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="{{ route('settings.index') }}" class="btn btn-secondary w-100">
                                    <i class="bx bx-arrow-back me-1"></i> Back to Settings
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

