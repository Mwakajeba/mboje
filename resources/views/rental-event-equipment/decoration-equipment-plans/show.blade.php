@extends('layouts.main')

@section('title', 'Decoration Equipment Plan Details')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
                ['label' => 'Decoration Equipment Plans', 'url' => route('rental-event-equipment.decoration-equipment-plans.index'), 'icon' => 'bx bx-list-check'],
                ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show'],
            ]" />

            @php
                $status = $plan->status;
                $badgeClass = match ($status) {
                    'draft' => 'bg-secondary',
                    'finalized' => 'bg-success',
                    'cancelled' => 'bg-danger',
                    default => 'bg-secondary',
                };
            @endphp

            <!-- Header & actions (aligned with invoice detail screens) -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">
                            <i class="bx bx-list-check me-2 text-info"></i>
                            Plan for Job {{ $plan->job->job_number }}
                            <span class="badge {{ $badgeClass }} ms-2">
                                {{ ucwords(str_replace('_', ' ', $status)) }}
                            </span>
                        </h4>
                        <div class="page-title-right">
                            <a href="{{ route('rental-event-equipment.decoration-equipment-plans.edit', $plan) }}"
                               class="btn btn-outline-primary btn-sm me-1">
                                <i class="bx bx-edit me-1"></i>Edit Plan
                            </a>
                            <a href="{{ route('rental-event-equipment.decoration-jobs.show', $plan->job) }}"
                               class="btn btn-outline-info btn-sm me-1">
                                <i class="bx bx-calendar-event me-1"></i>View Job
                            </a>
                            <a href="{{ route('rental-event-equipment.decoration-equipment-issues.create', ['job_id' => Vinkla\Hashids\Facades\Hashids::encode($plan->job_id)]) }}"
                               class="btn btn-outline-warning btn-sm me-1">
                                <i class="bx bx-user-check me-1"></i>Issue Equipment for Job
                            </a>
                            <a href="{{ route('rental-event-equipment.decoration-equipment-plans.index') }}"
                               class="btn btn-secondary btn-sm">
                                <i class="bx bx-arrow-back me-1"></i>Back to Plans
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Plan & Job information -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-2">Plan Information</h6>
                                    <table class="table table-borderless table-sm mb-0">
                                        <tr>
                                            <td width="130"><strong>Plan Status:</strong></td>
                                            <td>
                                                <span class="badge {{ $badgeClass }}">
                                                    {{ ucwords(str_replace('_', ' ', $status)) }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Created:</strong></td>
                                            <td>
                                                {{ $plan->created_at?->format('M d, Y H:i') }}
                                                @if($plan->creator)
                                                    <br><small class="text-muted">by {{ $plan->creator->name }}</small>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Company:</strong></td>
                                            <td>{{ $plan->company->name ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Branch:</strong></td>
                                            <td>{{ $plan->branch->name ?? '-' }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-2">Job Information</h6>
                                    <table class="table table-borderless table-sm mb-0">
                                        <tr>
                                            <td width="130"><strong>Job Number:</strong></td>
                                            <td>{{ $plan->job->job_number }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Customer:</strong></td>
                                            <td>{{ $plan->job->customer->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Event Date:</strong></td>
                                            <td>
                                                {{ $plan->job->event_date ? $plan->job->event_date->format('M d, Y') : '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Event Location:</strong></td>
                                            <td>{{ $plan->job->event_location ?: '-' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <hr>

                            <h6 class="text-muted mb-2">Planning Notes</h6>
                            <p class="mb-0">{{ $plan->notes ?: '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow-sm mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bx bx-info-circle me-1"></i>How this plan is used</h6>
                        </div>
                        <div class="card-body small">
                            <p class="mb-1">
                                This plan is an internal guide for what equipment should be prepared for the job.
                            </p>
                            <p class="mb-1">
                                Actual stock movements happen when you create an
                                <strong>Equipment Issue</strong> for this decoration job.
                            </p>
                            <p class="mb-0">
                                Use this plan as a pick list for the store or decorators before issuing items.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Planned equipment items -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Planned Equipment Items</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle">
                                    <thead class="table-light">
                                    <tr>
                                        <th style="width: 40%;">Equipment</th>
                                        <th style="width: 15%;">Code</th>
                                        <th style="width: 15%;" class="text-end">Planned Qty</th>
                                        <th style="width: 30%;">Notes</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($plan->items as $item)
                                        <tr>
                                            <td>{{ $item->equipment->name ?? 'N/A' }}</td>
                                            <td class="text-muted small">{{ $item->equipment->equipment_code ?? '-' }}</td>
                                            <td class="text-end">{{ number_format($item->quantity_planned, 0) }}</td>
                                            <td>{{ $item->notes }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No planned items.</td>
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

