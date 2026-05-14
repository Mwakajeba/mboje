@extends('layouts.main')

@section('title', 'Decoration Equipment Issue Details')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
                ['label' => 'Decoration Equipment Issues', 'url' => route('rental-event-equipment.decoration-equipment-issues.index'), 'icon' => 'bx bx-user-check'],
                ['label' => $issue->issue_number, 'url' => '#', 'icon' => 'bx bx-show'],
            ]" />

            <h6 class="mb-0 text-uppercase">DECORATION EQUIPMENT ISSUE DETAILS</h6>
            <hr />

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bx bx-error-circle me-1"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="card-title d-flex align-items-center">
                                    <div><i class="bx bx-user-check me-1 font-22 text-warning"></i></div>
                                    <h5 class="mb-0 text-warning">Issue #{{ $issue->issue_number }}</h5>
                                </div>
                                <div>
                                    @php
                                        $badgeClass = match ($issue->status) {
                                            'draft' => 'secondary',
                                            'issued' => 'primary',
                                            'returned' => 'success',
                                            'cancelled' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badgeClass }} me-2">
                                        {{ ucfirst($issue->status) }}
                                    </span>

                                    @if($issue->status === 'draft')
                                        <form method="POST"
                                              action="{{ route('rental-event-equipment.decoration-equipment-issues.confirm', $issue) }}"
                                              class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning"
                                                    onclick="return confirm('Confirm this issue and move equipment to In Event Use?')">
                                                <i class="bx bx-check-circle me-1"></i>Confirm Issue
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('rental-event-equipment.decoration-equipment-issues.index') }}" class="btn btn-sm btn-secondary">
                                        <i class="bx bx-arrow-back me-1"></i>Back
                                    </a>
                                </div>
                            </div>
                            <hr />

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <label class="form-label fw-bold small">Decoration Job</label>
                                        <p class="mb-0">
                                            @if($issue->job)
                                                <a href="{{ route('rental-event-equipment.decoration-jobs.show', $issue->job) }}"
                                                   class="fw-semibold">
                                                    {{ $issue->job->job_number }}
                                                </a>
                                                <br>
                                                <small class="text-muted">
                                                    {{ optional($issue->job->customer)->name ?? 'N/A' }}
                                                </small>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-2">
                                        <label class="form-label fw-bold small">Issue Date</label>
                                        <p class="mb-0">
                                            {{ optional($issue->issue_date)->format('M d, Y') ?? 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-2">
                                        <label class="form-label fw-bold small">Decorator / Team Lead</label>
                                        <p class="mb-0">
                                            {{ optional($issue->decorator)->name ?? '-' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-2">
                                <div class="col-md-3">
                                    <div class="mb-2">
                                        <label class="form-label fw-bold small">Company</label>
                                        <p class="mb-0">{{ $issue->company->name ?? '-' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-2">
                                        <label class="form-label fw-bold small">Branch</label>
                                        <p class="mb-0">{{ $issue->branch->name ?? '-' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-2">
                                        <label class="form-label fw-bold small">Created By</label>
                                        <p class="mb-0">{{ $issue->creator->name ?? 'System' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-2">
                                        <label class="form-label fw-bold small">Created At</label>
                                        <p class="mb-0">{{ $issue->created_at?->format('M d, Y H:i') }}</p>
                                    </div>
                                </div>
                            </div>

                            @if($issue->notes)
                                <hr />
                                <div class="mb-0">
                                    <label class="form-label fw-bold small">Notes</label>
                                    <p class="mb-0">{{ $issue->notes }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Equipment Items</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle">
                                    <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%;">#</th>
                                        <th style="width: 35%;">Equipment</th>
                                        <th style="width: 15%;">Code</th>
                                        <th style="width: 15%;" class="text-end">Quantity Issued</th>
                                        <th style="width: 30%;">Remarks</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($issue->items as $idx => $item)
                                        <tr>
                                            <td>{{ $idx + 1 }}</td>
                                            <td>{{ optional($item->equipment)->name ?? 'N/A' }}</td>
                                            <td class="text-muted small">{{ optional($item->equipment)->equipment_code ?? '-' }}</td>
                                            <td class="text-end">{{ number_format($item->quantity_issued, 0) }}</td>
                                            <td>{{ $item->remarks }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No equipment items on this issue.</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

