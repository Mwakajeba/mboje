@extends('layouts.main')

@section('title', 'Create Close Batch')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Settings', 'url' => route('settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Period-End Closing', 'url' => route('settings.period-closing.index'), 'icon' => 'bx bx-calendar-check'],
            ['label' => 'Create Close Batch', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">CREATE CLOSE BATCH</h6>
        <hr/>

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Period Information -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Period Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Period</label>
                                <p class="form-control-plaintext">{{ $period->period_label }}</p>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Start Date</label>
                                <p class="form-control-plaintext">{{ $period->start_date->format('M d, Y') }}</p>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">End Date</label>
                                <p class="form-control-plaintext">{{ $period->end_date->format('M d, Y') }}</p>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Status</label>
                                <p class="form-control-plaintext">
                                    <span class="badge bg-{{ $period->status === 'OPEN' ? 'success' : ($period->status === 'LOCKED' ? 'danger' : 'secondary') }}">
                                        {{ $period->status }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pre-Close Checklist -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card {{ $preCloseChecks['all_passed'] ? 'border-success' : 'border-warning' }}">
                    <div class="card-header bg-{{ $preCloseChecks['all_passed'] ? 'success' : 'warning' }} text-white">
                        <h6 class="mb-0">
                            <i class="bx bx-{{ $preCloseChecks['all_passed'] ? 'check-circle' : 'error-circle' }} me-2"></i>
                            Pre-Close Checklist
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($preCloseChecks['checks'] as $checkName => $check)
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-center">
                                        <i class="bx bx-{{ $check['passed'] ? 'check-circle text-success' : 'x-circle text-danger' }} me-2 fs-5"></i>
                                        <div class="flex-grow-1">
                                            <strong>{{ ucwords(str_replace('_', ' ', $checkName)) }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $check['message'] }}</small>
                                        </div>
                                    </div>
                                    
                                    @if($checkName === 'unposted_journals' && !$check['passed'] && isset($check['journals']) && count($check['journals']) > 0)
                                        <div class="mt-2 ms-4">
                                            <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="collapse" 
                                                    data-bs-target="#unpostedJournalsList" aria-expanded="false" aria-controls="unpostedJournalsList">
                                                <i class="bx bx-list-ul me-1"></i> View Unposted Journals ({{ count($check['journals']) }})
                                            </button>
                                            <div class="collapse mt-2" id="unpostedJournalsList">
                                                <div class="card card-body bg-light">
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-bordered mb-0">
                                                            <thead>
                                                                <tr>
                                                                    <th>Reference</th>
                                                                    <th>Date</th>
                                                                    <th>Description</th>
                                                                    <th>Amount</th>
                                                                    <th>Created By</th>
                                                                    <th>Created At</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($check['journals'] as $journal)
                                                                <tr>
                                                                    <td>
                                                                        <a href="{{ route('accounting.journals.show', $journal['id']) }}" target="_blank" class="text-decoration-none fw-bold">
                                                                            {{ $journal['reference'] }}
                                                                            <i class="bx bx-link-external ms-1"></i>
                                                                        </a>
                                                                    </td>
                                                                    <td>{{ $journal['date'] }}</td>
                                                                    <td>{{ Str::limit($journal['description'], 50) }}</td>
                                                                    <td class="text-end">{{ number_format($journal['amount'], 2) }}</td>
                                                                    <td>{{ $journal['created_by'] }}</td>
                                                                    <td>{{ $journal['created_at'] }}</td>
                                                                </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @if(!$preCloseChecks['all_passed'])
                            <div class="alert alert-danger mt-3">
                                <i class="bx bx-error-circle me-2"></i>
                                <strong>Error:</strong> Cannot proceed with closing. Please resolve all issues above before creating a close batch.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Close Batch Form -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-file me-2"></i>Close Batch Details</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('settings.period-closing.close-batch.store', $period) }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="batch_label" class="form-label">Batch Label <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="batch_label" name="batch_label" 
                                           value="{{ old('batch_label', 'Close Batch - ' . $period->period_label) }}" required>
                                    <small class="form-text text-muted">A descriptive label for this close batch</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" 
                                              placeholder="Optional notes about this close batch...">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('settings.period-closing.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary" {{ !$preCloseChecks['all_passed'] ? 'disabled' : '' }}>
                                    <i class="bx bx-save me-1"></i> Create Close Batch
                                </button>
                            </div>
                            @if(!$preCloseChecks['all_passed'])
                                <div class="alert alert-warning mt-3 mb-0">
                                    <i class="bx bx-info-circle me-2"></i>
                                    <small>The "Create Close Batch" button is disabled until all pre-close checks pass.</small>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

