@extends('layouts.main')

@section('title', 'Create Count Session')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Management', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Inventory Count', 'url' => route('inventory.counts.index'), 'icon' => 'bx bx-clipboard-check'],
            ['label' => 'Create Count Session', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        
        <h6 class="mb-0 text-uppercase">CREATE COUNT SESSION</h6>
        <hr />

        <div class="row">
            <div class="col-12 col-lg-8">
                <div class="card shadow-sm mb-4 radius-10">
                    <div class="card-header card-header-gradient">
                        <h5 class="card-title mb-0 text-white">
                            <i class="bx bx-calendar me-2"></i>Count Period: {{ $period->period_name }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('inventory.counts.sessions.store', $period->encoded_id) }}" method="POST">
                            @csrf

                            <!-- Location & Assignment Section -->
                            <div class="form-section mb-4">
                                <h6 class="form-section-title">
                                    <i class="bx bx-map"></i>Location & Assignment
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <label for="inventory_location_id" class="form-label fw-bold">
                                                <i class="bx bx-building text-primary me-1"></i>Location <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select select2-single @error('inventory_location_id') is-invalid @enderror" 
                                                    id="inventory_location_id" 
                                                    name="inventory_location_id" 
                                                    required>
                                                <option value="">Select Location</option>
                                                @foreach($locations as $location)
                                                    <option value="{{ $location->id }}" {{ old('inventory_location_id') == $location->id ? 'selected' : '' }}>
                                                        {{ $location->name }}{{ $location->branch ? ' (' . $location->branch->name . ')' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('inventory_location_id')
                                                <div class="invalid-feedback">
                                                    <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                                </div>
                                            @enderror
                                            <small class="text-muted d-block mt-1">
                                                <i class="bx bx-info-circle me-1"></i>Select the location for this count session
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-info border-0 shadow-sm mt-3">
                                    <i class="bx bx-info-circle me-2"></i>
                                    <strong>Note:</strong> You can assign supervisors, counters, and verifiers after creating the session using the "Assign Team" button.
                                </div>
                            </div>

                            <!-- Count Settings Section -->
                            <div class="form-section mb-4">
                                <h6 class="form-section-title">
                                    <i class="bx bx-cog"></i>Count Settings
                                </h6>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_blind_count" id="is_blind_count" value="1" {{ old('is_blind_count') ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold" for="is_blind_count">
                                                    <i class="bx bx-hide text-warning me-1"></i>Blind Count
                                                </label>
                                            </div>
                                            <small class="text-muted d-block mt-1 ms-4">
                                                <i class="bx bx-info-circle me-1"></i>Hide system quantities from counters to ensure unbiased counting
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes Section -->
                            <div class="form-section mb-4">
                                <h6 class="form-section-title">
                                    <i class="bx bx-note"></i>Additional Notes
                                </h6>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="notes" class="form-label fw-bold">
                                                <i class="bx bx-edit text-info me-1"></i>Notes
                                            </label>
                                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                                      id="notes" 
                                                      name="notes" 
                                                      rows="4" 
                                                      placeholder="Enter any additional notes or instructions for this count session...">{{ old('notes') }}</textarea>
                                            @error('notes')
                                                <div class="invalid-feedback">
                                                    <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info border-0 shadow-sm">
                                <i class="bx bx-info-circle me-2"></i>
                                <strong>Note:</strong> After creating the session, counting sheets will be automatically generated for all items at the selected location.
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="{{ route('inventory.counts.index') }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-x me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-check-circle me-1"></i>Create Session
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar Information -->
            <div class="col-12 col-lg-4">
                <div class="card shadow-sm mb-4 border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="card-title mb-0">
                            <i class="bx bx-info-circle me-2"></i>Period Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <p class="mb-1 text-muted small">Period Name</p>
                            <p class="mb-0 fw-bold">{{ $period->period_name }}</p>
                        </div>
                        <div class="mb-3">
                            <p class="mb-1 text-muted small">Count Type</p>
                            <p class="mb-0 fw-bold">{{ ucfirst(str_replace('_', ' ', $period->count_type)) }}</p>
                        </div>
                        <div class="mb-3">
                            <p class="mb-1 text-muted small">Start Date</p>
                            <p class="mb-0 fw-bold">{{ $period->count_start_date ? $period->count_start_date->format('M d, Y') : 'N/A' }}</p>
                        </div>
                        <div class="mb-3">
                            <p class="mb-1 text-muted small">End Date</p>
                            <p class="mb-0 fw-bold">{{ $period->count_end_date ? $period->count_end_date->format('M d, Y') : 'N/A' }}</p>
                        </div>
                        @if($period->location)
                            <div class="mb-3">
                                <p class="mb-1 text-muted small">Location</p>
                                <p class="mb-0 fw-bold">
                                    {{ $period->location->name }}
                                    @if($period->location->branch)
                                        <span class="text-muted">({{ $period->location->branch->name }})</span>
                                    @endif
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h6 class="card-title mb-0">
                            <i class="bx bx-bulb me-2"></i>Quick Tips
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="bx bx-check-circle text-success me-2"></i>
                                <small>Select the location where counting will take place</small>
                            </li>
                            <li class="mb-2">
                                <i class="bx bx-check-circle text-success me-2"></i>
                                <small>Assign team members (supervisors, counters, verifiers) after creation</small>
                            </li>
                            <li class="mb-2">
                                <i class="bx bx-check-circle text-success me-2"></i>
                                <small>Use blind count for unbiased results</small>
                            </li>
                            <li>
                                <i class="bx bx-check-circle text-success me-2"></i>
                                <small>Counting sheets are auto-generated after creation</small>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card-header-gradient {
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%) !important;
        color: white;
    }
    
    .form-section {
        background: #f8f9fa;
        border-left: 4px solid #0d6efd;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    
    .form-section:hover {
        background: #f0f4f8;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }
    
    .form-section-title {
        font-weight: 600;
        color: #212529;
        font-size: 1rem;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        align-items: center;
    }
    
    .form-section-title i {
        margin-right: 0.5rem;
        color: #0d6efd;
        font-size: 1.1rem;
    }
    
    .radius-10 {
        border-radius: 10px;
    }
    
    .select2-container--bootstrap-5 .select2-selection {
        border-radius: 0.5rem;
        border: 2px solid #e9ecef;
        min-height: 48px;
    }

    .select2-container--bootstrap-5 .select2-selection:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
    
    .select2-container--bootstrap-5.select2-container--focus .select2-selection {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2 for all select2-single elements
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: function() {
            return $(this).data('placeholder') || 'Please select...';
        },
        allowClear: true
    });
});
</script>
@endpush

