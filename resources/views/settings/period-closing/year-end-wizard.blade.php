@extends('layouts.main')

@section('title', 'Year-End Closing Wizard')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Settings', 'url' => route('settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Period-End Closing', 'url' => route('settings.period-closing.index'), 'icon' => 'bx bx-calendar-check'],
            ['label' => 'Fiscal Years', 'url' => route('settings.period-closing.fiscal-years'), 'icon' => 'bx bx-calendar'],
            ['label' => 'Year-End Wizard: ' . $fiscalYear->fy_label, 'url' => '#', 'icon' => 'bx bx-wizard']
        ]" />
        <h6 class="mb-0 text-uppercase">YEAR-END CLOSING WIZARD</h6>
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

        <!-- Fiscal Year Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="mb-1">{{ $fiscalYear->fy_label }}</h5>
                                <p class="text-muted mb-0">
                                    <strong>Period:</strong> {{ $fiscalYear->start_date->format('M d, Y') }} - {{ $fiscalYear->end_date->format('M d, Y') }}
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge bg-{{ $fiscalYear->status === 'OPEN' ? 'success' : 'secondary' }} fs-6 px-3 py-2">
                                    {{ $fiscalYear->status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Card -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-trending-up me-2"></i>Closing Progress</h6>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="progress" style="height: 30px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                         role="progressbar" 
                                         style="width: {{ $progress }}%"
                                         aria-valuenow="{{ $progress }}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        {{ $progress }}%
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <h4 class="mb-0">{{ $closedCount }} / {{ $totalPeriods }} Periods Closed</h4>
                                <small class="text-muted">{{ $totalPeriods - $closedCount }} remaining</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Open Periods List -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-transparent border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="bx bx-calendar me-2"></i>Periods to Close</h6>
                            <button type="button" class="btn btn-sm btn-primary" onclick="refreshStatus()">
                                <i class="bx bx-refresh me-1"></i> Refresh Status
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(count($openPeriods) > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Period</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Status</th>
                                            <th>Can Close?</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="periodsTableBody">
                                        @foreach($openPeriods as $item)
                                            <tr id="period-row-{{ $item['period']->period_id }}" 
                                                class="{{ !$item['can_close'] ? 'table-warning' : '' }}">
                                                <td>
                                                    <strong>{{ $item['period']->period_label }}</strong>
                                                </td>
                                                <td>{{ $item['period']->start_date->format('M d, Y') }}</td>
                                                <td>{{ $item['period']->end_date->format('M d, Y') }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $item['period']->status === 'OPEN' ? 'success' : 'secondary' }}">
                                                        {{ $item['period']->status }}
                                                    </span>
                                                    @if($item['has_close_batch'])
                                                        <span class="badge bg-info ms-1">Draft Exists</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($item['can_close'])
                                                        <span class="badge bg-success">
                                                            <i class="bx bx-check-circle me-1"></i> Ready
                                                        </span>
                                                    @else
                                                        <span class="badge bg-warning">
                                                            <i class="bx bx-error-circle me-1"></i> Blocked
                                                        </span>
                                                        <br><small class="text-muted mt-1 d-block">
                                                            Close these first: {{ implode(', ', array_column($item['unclosed_periods'], 'period_label')) }}
                                                        </small>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if($item['can_close'])
                                                        <a href="{{ route('settings.period-closing.close-batch.create', $item['period']->period_id) }}" 
                                                           class="btn btn-sm btn-primary">
                                                            <i class="bx bx-file me-1"></i> Create Close Batch
                                                        </a>
                                                    @else
                                                        <button type="button" class="btn btn-sm btn-secondary" disabled>
                                                            <i class="bx bx-lock me-1"></i> Blocked
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="bx bx-check-circle font-48 text-success mb-3"></i>
                                <h5 class="text-success">All Periods Closed!</h5>
                                <p class="text-muted">All periods for this fiscal year have been closed.</p>
                                @php
                                    $lastPeriod = $fiscalYear->periods()->orderBy('end_date', 'desc')->first();
                                    $lastCloseBatch = $lastPeriod ? $lastPeriod->closeBatches()->where('status', 'LOCKED')->first() : null;
                                @endphp
                                @if($lastCloseBatch && app(\App\Services\PeriodClosing\PeriodCloseService::class)->isLastPeriodOfFiscalYear($lastPeriod))
                                    <a href="{{ route('settings.period-closing.close-batch.show', $lastCloseBatch->close_id) }}" 
                                       class="btn btn-warning mt-3">
                                        <i class="bx bx-transfer me-1"></i> Roll to Retained Earnings
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Step-by-Step Process -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bx bx-list-check me-2"></i>Step-by-Step Year-End Closing Process</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3"><i class="bx bx-play-circle me-2"></i>How to Close a Financial Year</h6>
                                <div class="timeline">
                                    <div class="timeline-item mb-4">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <div class="timeline-marker bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <strong>1</strong>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1">Start with the First Period</h6>
                                                <p class="text-muted mb-0">Look for the period marked as <span class="badge bg-success">Ready</span>. This will be the earliest open period (usually January). It's ready because there are no previous periods blocking it.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="timeline-item mb-4">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <div class="timeline-marker bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <strong>2</strong>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1">Click "Create Close Batch"</h6>
                                                <p class="text-muted mb-0">Click the <span class="badge bg-primary">Create Close Batch</span> button for the ready period. This opens the close batch creation page.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="timeline-item mb-4">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <div class="timeline-marker bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <strong>3</strong>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1">Review Pre-Close Checklist</h6>
                                                <p class="text-muted mb-0">Review all pre-close checks (unposted journals, bank reconciliation, etc.). Add any necessary adjustments.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="timeline-item mb-4">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <div class="timeline-marker bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <strong>4</strong>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1">Submit for Review</h6>
                                                <p class="text-muted mb-0">Click "Submit for Review" to send the close batch for approval.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="timeline-item mb-4">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <div class="timeline-marker bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <strong>5</strong>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1">Approve & Lock Period</h6>
                                                <p class="text-muted mb-0">An approver reviews and approves the batch. Once approved, the period is locked.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="timeline-item mb-4">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <div class="timeline-marker bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <strong>6</strong>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1">Next Period Becomes Ready</h6>
                                                <p class="text-muted mb-0">After closing the first period, the next period (February) automatically becomes <span class="badge bg-success">Ready</span>. Repeat steps 2-5 for each period.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="timeline-item">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <div class="timeline-marker bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                    <strong>7</strong>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1">Roll to Retained Earnings</h6>
                                                <p class="text-muted mb-0">After closing the last period (December), click "Roll to Retained Earnings" to close all P&L accounts and complete the year-end.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-warning mb-3"><i class="bx bx-error-circle me-2"></i>Understanding Blocked Periods</h6>
                                <div class="alert alert-warning">
                                    <h6 class="alert-heading"><i class="bx bx-info-circle me-2"></i>Why are periods blocked?</h6>
                                    <p class="mb-2">Periods are marked as <span class="badge bg-warning">Blocked</span> when previous periods haven't been closed yet.</p>
                                    <p class="mb-2"><strong>Example:</strong></p>
                                    <ul class="mb-0">
                                        <li>If <strong>January</strong> is still open, then <strong>February, March, April...</strong> will all be blocked</li>
                                        <li>Once you close <strong>January</strong>, then <strong>February</strong> becomes ready</li>
                                        <li>Once you close <strong>February</strong>, then <strong>March</strong> becomes ready</li>
                                        <li>And so on...</li>
                                    </ul>
                                </div>
                                <div class="alert alert-info">
                                    <h6 class="alert-heading"><i class="bx bx-lightbulb me-2"></i>Quick Tips</h6>
                                    <ul class="mb-0">
                                        <li>Always start with the earliest open period</li>
                                        <li>The wizard shows which periods must be closed first</li>
                                        <li>Refresh the page to see updated status after closing a period</li>
                                        <li>You cannot skip periods - they must be closed in order</li>
                                        <li>The progress bar shows your overall completion</li>
                                    </ul>
                                </div>
                                <div class="card border-success">
                                    <div class="card-body">
                                        <h6 class="text-success mb-3"><i class="bx bx-check-circle me-2"></i>Current Status</h6>
                                        @if(count($openPeriods) > 0)
                                            @php
                                                $readyPeriods = collect($openPeriods)->where('can_close', true);
                                                $blockedPeriods = collect($openPeriods)->where('can_close', false);
                                            @endphp
                                            @if($readyPeriods->count() > 0)
                                                <p class="mb-2"><strong>Ready to Close:</strong> {{ $readyPeriods->count() }} period(s)</p>
                                                <p class="mb-2 text-success">
                                                    <i class="bx bx-arrow-right me-1"></i>
                                                    <strong>Next:</strong> {{ $readyPeriods->first()['period']->period_label }}
                                                </p>
                                            @else
                                                <p class="mb-2 text-warning">
                                                    <i class="bx bx-error-circle me-1"></i>
                                                    <strong>No periods ready.</strong> You need to close earlier periods first.
                                                </p>
                                            @endif
                                            @if($blockedPeriods->count() > 0)
                                                <p class="mb-0"><strong>Blocked:</strong> {{ $blockedPeriods->count() }} period(s) waiting for previous periods to close</p>
                                            @endif
                                        @else
                                            <p class="mb-0 text-success">
                                                <i class="bx bx-check-circle me-1"></i>
                                                <strong>All periods closed!</strong> You can now roll to retained earnings.
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="row mt-4">
            <div class="col-12">
                <a href="{{ route('settings.period-closing.fiscal-years') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to Fiscal Years
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    function refreshStatus() {
        Swal.fire({
            title: 'Refreshing...',
            text: 'Checking period closing status',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '{{ route("settings.period-closing.fiscal-years.period-status", $fiscalYear->fy_id) }}',
            type: 'GET',
            success: function(response) {
                Swal.close();
                
                // Update progress bar
                const progressBar = document.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = response.progress + '%';
                    progressBar.setAttribute('aria-valuenow', response.progress);
                    progressBar.textContent = response.progress + '%';
                }

                // Update closed count
                const countElement = document.querySelector('.col-md-4.text-end h4');
                if (countElement) {
                    countElement.textContent = response.closed_count + ' / ' + response.total_periods + ' Periods Closed';
                }

                // Update table
                const tbody = document.getElementById('periodsTableBody');
                if (tbody && response.open_periods.length > 0) {
                    tbody.innerHTML = '';
                    response.open_periods.forEach(function(item) {
                        const row = createPeriodRow(item);
                        tbody.appendChild(row);
                    });
                } else if (tbody && response.open_periods.length === 0) {
                    // All periods closed - reload page to show success message
                    location.reload();
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Status Updated',
                    text: 'Period closing status has been refreshed.',
                    timer: 1500,
                    showConfirmButton: false
                });
            },
            error: function(xhr) {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to refresh status. Please try again.'
                });
            }
        });
    }

    function createPeriodRow(item) {
        const tr = document.createElement('tr');
        tr.id = 'period-row-' + item.period_id;
        if (!item.can_close) {
            tr.className = 'table-warning';
        }

        const statusBadge = item.can_close 
            ? '<span class="badge bg-success"><i class="bx bx-check-circle me-1"></i> Ready</span>'
            : '<span class="badge bg-warning"><i class="bx bx-error-circle me-1"></i> Blocked</span>';

        const unclosedText = item.unclosed_periods.length > 0
            ? '<br><small class="text-muted mt-1 d-block">Close these first: ' + item.unclosed_periods.map(p => p.period_label).join(', ') + '</small>'
            : '';

        const actionButton = item.can_close
            ? '<a href="/period-closing/close-batch/create/' + item.period_id + '" class="btn btn-sm btn-primary"><i class="bx bx-file me-1"></i> Create Close Batch</a>'
            : '<button type="button" class="btn btn-sm btn-secondary" disabled><i class="bx bx-lock me-1"></i> Blocked</button>';

        tr.innerHTML = `
            <td><strong>${item.period_label}</strong></td>
            <td>${item.start_date}</td>
            <td>${item.end_date}</td>
            <td><span class="badge bg-success">OPEN</span></td>
            <td>${statusBadge}${unclosedText}</td>
            <td class="text-center">${actionButton}</td>
        `;

        return tr;
    }

    // Auto-refresh every 30 seconds if there are open periods
    @if(count($openPeriods) > 0)
    setInterval(function() {
        refreshStatus();
    }, 30000); // 30 seconds
    @endif
</script>
@endpush

