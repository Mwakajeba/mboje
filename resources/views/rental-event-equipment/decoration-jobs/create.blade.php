@extends('layouts.main')

@section('title', 'Create Decoration Job')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Decoration Jobs', 'url' => route('rental-event-equipment.decoration-jobs.index'), 'icon' => 'bx bx-calendar-event'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <h6 class="mb-0 text-uppercase">CREATE DECORATION JOB</h6>
        <hr />

        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bx bx-calendar-event me-2"></i>New Decoration Job</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('rental-event-equipment.decoration-jobs.store') }}">
                    @csrf

                    <div class="row">
                        <!-- Customer -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                <select class="form-select select2-single @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id" required>
                                    <option value="">Select Customer</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }} - {{ $customer->phone }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Event Date -->
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="event_date" class="form-label">Event Date</label>
                                <input type="date" class="form-control @error('event_date') is-invalid @enderror"
                                       id="event_date" name="event_date" value="{{ old('event_date') }}">
                                @error('event_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Event Location -->
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="event_location" class="form-label">Event Location</label>
                                <input type="text" class="form-control @error('event_location') is-invalid @enderror"
                                       id="event_location" name="event_location" value="{{ old('event_location') }}"
                                       placeholder="Venue / Address">
                                @error('event_location')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Event Theme -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="event_theme" class="form-label">Event Theme</label>
                                <input type="text" class="form-control @error('event_theme') is-invalid @enderror"
                                       id="event_theme" name="event_theme" value="{{ old('event_theme') }}"
                                       placeholder="e.g., White & Gold Wedding">
                                @error('event_theme')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Package Name -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="package_name" class="form-label">Service Package</label>
                                <input type="text" class="form-control @error('package_name') is-invalid @enderror"
                                       id="package_name" name="package_name" value="{{ old('package_name') }}"
                                       placeholder="e.g., Premium Wedding Package">
                                @error('package_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Agreed Price -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="agreed_price" class="form-label">Agreed Service Price (TZS) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0"
                                       class="form-control @error('agreed_price') is-invalid @enderror"
                                       id="agreed_price" name="agreed_price" value="{{ old('agreed_price') }}"
                                       placeholder="0.00" required>
                                @error('agreed_price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Status -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="status" class="form-label">Job Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                    @php
                                        $statusOptions = [
                                            'draft' => 'Draft',
                                            'planned' => 'Planned',
                                            'confirmed' => 'Confirmed',
                                            'in_progress' => 'In Progress',
                                            'completed' => 'Completed',
                                            'cancelled' => 'Cancelled',
                                        ];
                                        $currentStatus = old('status', 'draft');
                                    @endphp
                                    @foreach($statusOptions as $value => $label)
                                        <option value="{{ $value }}" {{ $currentStatus === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Service Description -->
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="service_description" class="form-label">Service Description</label>
                                <textarea class="form-control @error('service_description') is-invalid @enderror"
                                          id="service_description" name="service_description" rows="3"
                                          placeholder="Describe the decoration service scope, key deliverables, and any special notes...">{{ old('service_description') }}</textarea>
                                @error('service_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-3">
                        <label for="notes" class="form-label">Internal Notes</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror"
                                  id="notes" name="notes" rows="3"
                                  placeholder="Internal notes for coordination between sales, decorators, and storekeepers...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="alert alert-info mb-4">
                        <h6 class="alert-heading"><i class="bx bx-info-circle me-2"></i>Decoration Service Jobs</h6>
                        <p class="mb-1">
                            Customers are billed for the <strong>service package</strong>, not for individual equipment items.
                        </p>
                        <p class="mb-0">
                            Equipment planning, issues, and returns for this job will be managed in separate screens, ensuring clear tracking of internal equipment usage.
                        </p>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('rental-event-equipment.decoration-jobs.index') }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-info btn-lg">
                            <i class="bx bx-save me-1"></i>Save Decoration Job
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    if ($.fn.select2) {
        $('.select2-single').select2({
            placeholder: 'Select',
            allowClear: true,
            width: '100%',
            theme: 'bootstrap-5'
        });
    }
});
</script>
@endpush

