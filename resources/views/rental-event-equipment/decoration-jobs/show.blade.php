@extends('layouts.main')

@section('title', 'Decoration Job Details')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Decoration Jobs', 'url' => route('rental-event-equipment.decoration-jobs.index'), 'icon' => 'bx bx-calendar-event'],
            ['label' => 'Job Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

            <h6 class="mb-0 text-uppercase">DECORATION JOB DETAILS</h6>
            <hr />

            @php
                $encodedId = Vinkla\Hashids\Facades\Hashids::encode($job->id);
            @endphp

            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">
                                    <i class="bx bx-calendar-event me-2 text-info"></i>
                                    Job {{ $job->job_number }}
                                </h5>
                                <small class="text-muted">Created {{ $job->created_at->format('M d, Y H:i') }} by
                                    {{ $job->creator->name ?? 'System' }}</small>
                            </div>
                            <div>
                                @php
                                    $status = $job->status;
                                    $badgeClass = match ($status) {
                                        'draft' => 'bg-secondary',
                                        'planned' => 'bg-info',
                                        'confirmed' => 'bg-primary',
                                        'in_progress' => 'bg-warning',
                                        'completed' => 'bg-success',
                                        'cancelled' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }} px-3 py-2">
                                    {{ ucwords(str_replace('_', ' ', $status)) }}
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Customer</h6>
                                    <p class="mb-0 fw-semibold">
                                        {{ $job->customer->name ?? 'N/A' }}
                                    </p>
                                    @if($job->customer)
                                        <small class="text-muted">
                                            {{ $job->customer->phone }}{{ $job->customer->email ? ' | ' . $job->customer->email : '' }}
                                        </small>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Agreed Service Price</h6>
                                    <p class="mb-0 fw-bold text-success">
                                        TZS {{ number_format($job->agreed_price, 2) }}
                                    </p>
                                </div>
                            </div>

                            <hr>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <h6 class="text-muted mb-1">Event Date</h6>
                                    <p class="mb-0">
                                        {{ $job->event_date ? $job->event_date->format('M d, Y') : '-' }}
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <h6 class="text-muted mb-1">Event Location</h6>
                                    <p class="mb-0">{{ $job->event_location ?: '-' }}</p>
                                </div>
                                <div class="col-md-4">
                                    <h6 class="text-muted mb-1">Event Theme</h6>
                                    <p class="mb-0">{{ $job->event_theme ?: '-' }}</p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Service Package</h6>
                                    <p class="mb-0">{{ $job->package_name ?: '-' }}</p>
                                </div>
                            </div>

                            @if($job->service_description)
                                <div class="mb-3">
                                    <h6 class="text-muted mb-1">Service Description</h6>
                                    <p class="mb-0">{{ $job->service_description }}</p>
                                </div>
                            @endif

                            @if($job->notes)
                                <div class="mb-0">
                                    <h6 class="text-muted mb-1">Internal Notes</h6>
                                    <p class="mb-0">{{ $job->notes }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card shadow-sm mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bx bx-cog me-1"></i>Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('rental-event-equipment.decoration-jobs.edit', $encodedId) }}"
                                    class="btn btn-outline-primary">
                                    <i class="bx bx-edit me-1"></i>Edit Job
                                </a>
                                <a href="{{ route('rental-event-equipment.decoration-equipment-plans.create', ['job_id' => $encodedId]) }}"
                                   class="btn btn-outline-info">
                                    <i class="bx bx-list-check me-1"></i>Create Equipment Plan
                                </a>
                                <a href="{{ route('rental-event-equipment.decoration-invoices.create', ['job_id' => $encodedId]) }}"
                                   class="btn btn-outline-success">
                                    <i class="bx bx-receipt me-1"></i>Create Service Invoice
                                </a>
                                <a href="{{ route('rental-event-equipment.decoration-jobs.index') }}"
                                    class="btn btn-outline-secondary">
                                    <i class="bx bx-arrow-back me-1"></i>Back to List
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bx bx-palette me-1"></i>Decoration Workflow (Preview)</h6>
                        </div>
                        <div class="card-body">
                            <ol class="mb-0 small">
                                <li><strong>Decoration Job</strong> – This screen (customer, event, package, agreed price).
                                </li>
                                <li><strong>Equipment Planning</strong> – Plan internal equipment required for this job.
                                </li>
                                <li><strong>Issue to Decorators</strong> – Issue equipment to decorators (Available → In
                                    Event Use).</li>
                                <li><strong>Decoration Returns</strong> – Record returned equipment (Good / Damaged / Lost).
                                </li>
                                <li><strong>Loss Handling</strong> – Handle any internal losses (business expense / employee
                                    liability).</li>
                                <li><strong>Service Invoice</strong> – Invoice customer for the service package (no
                                    equipment lines).</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection