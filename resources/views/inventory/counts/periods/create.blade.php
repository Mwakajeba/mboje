@extends('layouts.main')

@section('title', 'Create Count Period')

@push('styles')
<style>
    .form-section {
        background: #ffffff;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: box-shadow 0.2s ease-in-out;
    }
    
    .form-section:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }
    
    .form-section-title {
        font-weight: 600;
        color: #212529;
        font-size: 1.1rem;
        margin-bottom: 1.25rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #e9ecef;
        display: flex;
        align-items: center;
    }
    
    .form-section-title i {
        margin-right: 0.5rem;
        color: #0d6efd;
    }
    
    .info-box {
        background: linear-gradient(135deg, #e7f3ff 0%, #f0f8ff 100%);
        border-left: 4px solid #0dcaf0;
        padding: 1rem;
        margin-top: 1rem;
        border-radius: 6px;
    }
    
    .info-box i {
        color: #0dcaf0;
    }
    
    .radius-10 {
        border-radius: 10px;
    }
    
    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }
    
    .form-label .text-danger {
        color: #dc3545 !important;
    }
    
    .form-control:focus,
    .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
    }
    
    .card-header-gradient {
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        color: white;
        border-radius: 10px 10px 0 0;
    }
    
    .step-indicator {
        display: flex;
        justify-content: space-between;
        margin-bottom: 2rem;
        position: relative;
    }
    
    .step-indicator::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 0;
        right: 0;
        height: 2px;
        background: #e9ecef;
        z-index: 0;
    }
    
    .step {
        flex: 1;
        text-align: center;
        position: relative;
        z-index: 1;
    }
    
    .step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #0d6efd;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.5rem;
        font-weight: 600;
    }
    
    .step-label {
        font-size: 0.875rem;
        color: #6c757d;
        font-weight: 500;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Management', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Inventory Count', 'url' => route('inventory.counts.index'), 'icon' => 'bx bx-clipboard-check'],
            ['label' => 'Create Count Period', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <!-- Header -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1">
                            <i class="bx bx-clipboard-check me-2 text-primary"></i>Create New Count Period
                        </h4>
                        <p class="text-muted mb-0">Set up a new inventory counting period for stock verification</p>
                    </div>
                    <a href="{{ route('inventory.counts.index') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-arrow-back me-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        <!-- Step Indicator -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="step-indicator">
                            <div class="step">
                                <div class="step-number">1</div>
                                <div class="step-label">Basic Info</div>
                            </div>
                            <div class="step">
                                <div class="step-number">2</div>
                                <div class="step-label">Schedule</div>
                            </div>
                            <div class="step">
                                <div class="step-number">3</div>
                                <div class="step-label">Location & Staff</div>
                            </div>
                            <div class="step">
                                <div class="step-number">4</div>
                                <div class="step-label">Review</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form id="count-period-form" action="{{ route('inventory.counts.periods.store') }}" method="POST">
            @csrf

            <div class="card radius-10">
                <div class="card-header card-header-gradient">
                    <h5 class="mb-0 text-white">
                        <i class="bx bx-clipboard-check me-2"></i>Count Period Information
                    </h5>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bx bx-error-circle me-2"></i>
                            <strong>Please fix the following errors:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Basic Information Section -->
                    <div class="form-section">
                        <h6 class="form-section-title">
                            <i class="bx bx-info-circle"></i>Basic Information
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="period_name" class="form-label">
                                        Period Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control form-control-lg @error('period_name') is-invalid @enderror" 
                                           id="period_name" 
                                           name="period_name" 
                                           value="{{ old('period_name') }}" 
                                           placeholder="e.g., Monthly Count - January 2025, Q1 2025 Cycle Count"
                                           required>
                                    @error('period_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">
                                        <i class="bx bx-info-circle me-1"></i>A descriptive name to identify this count period
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="count_type" class="form-label">
                                        Count Type <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select form-select-lg select2-single @error('count_type') is-invalid @enderror" 
                                            id="count_type" 
                                            name="count_type" 
                                            required>
                                        <option value="">Select Count Type</option>
                                        <option value="cycle" {{ old('count_type') == 'cycle' ? 'selected' : '' }}>Cycle Count</option>
                                        <option value="year_end" {{ old('count_type') == 'year_end' ? 'selected' : '' }}>Year-End Count</option>
                                        <option value="ad_hoc" {{ old('count_type') == 'ad_hoc' ? 'selected' : '' }}>Ad-Hoc Count</option>
                                    </select>
                                    @error('count_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">
                                        <i class="bx bx-info-circle me-1"></i>Type of inventory count to be performed
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule Configuration Section -->
                    <div class="form-section">
                        <h6 class="form-section-title">
                            <i class="bx bx-calendar"></i>Schedule Configuration
                        </h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-4">
                                    <label for="frequency" class="form-label">Frequency</label>
                                    <select class="form-select form-select-lg select2-single @error('frequency') is-invalid @enderror" 
                                            id="frequency" 
                                            name="frequency">
                                        <option value="">Select Frequency (Optional)</option>
                                        <option value="daily" {{ old('frequency') == 'daily' ? 'selected' : '' }}>Daily</option>
                                        <option value="weekly" {{ old('frequency') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                        <option value="monthly" {{ old('frequency') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                        <option value="quarterly" {{ old('frequency') == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                        <option value="yearly" {{ old('frequency') == 'yearly' ? 'selected' : '' }}>Yearly</option>
                                        <option value="ad_hoc" {{ old('frequency') == 'ad_hoc' ? 'selected' : '' }}>Ad-Hoc</option>
                                    </select>
                                    @error('frequency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">
                                        <i class="bx bx-info-circle me-1"></i>How often this count should be performed
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-4">
                                    <label for="count_start_date" class="form-label">
                                        Start Date <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" 
                                           class="form-control form-control-lg @error('count_start_date') is-invalid @enderror" 
                                           id="count_start_date" 
                                           name="count_start_date" 
                                           value="{{ old('count_start_date') }}" 
                                           required>
                                    @error('count_start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">
                                        <i class="bx bx-calendar me-1"></i>Date when counting will begin
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-4">
                                    <label for="count_end_date" class="form-label">
                                        End Date <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" 
                                           class="form-control form-control-lg @error('count_end_date') is-invalid @enderror" 
                                           id="count_end_date" 
                                           name="count_end_date" 
                                           value="{{ old('count_end_date') }}" 
                                           required>
                                    @error('count_end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">
                                        <i class="bx bx-calendar me-1"></i>Date when counting should be completed
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="info-box">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Important:</strong> The end date must be on or after the start date. Multiple count sessions can be created within this period to organize the counting process.
                        </div>
                    </div>

                    <!-- Location & Assignment Section -->
                    <div class="form-section">
                        <h6 class="form-section-title">
                            <i class="bx bx-map"></i>Location & Assignment
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="inventory_location_id" class="form-label">Location</label>
                                    <select class="form-select form-select-lg select2-single @error('inventory_location_id') is-invalid @enderror" 
                                            id="inventory_location_id" 
                                            name="inventory_location_id">
                                        <option value="">All Locations</option>
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}" {{ old('inventory_location_id') == $location->id ? 'selected' : '' }}>
                                                {{ $location->name }}@if($location->branch) ({{ $location->branch->name }})@endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('inventory_location_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">
                                        <i class="bx bx-info-circle me-1"></i>Select specific location or leave blank for all locations
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="responsible_staff_id" class="form-label">Responsible Staff</label>
                                    <select class="form-select form-select-lg select2-single @error('responsible_staff_id') is-invalid @enderror" 
                                            id="responsible_staff_id" 
                                            name="responsible_staff_id">
                                        <option value="">Select Staff (Optional)</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('responsible_staff_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}@if($user->email) - {{ $user->email }}@endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('responsible_staff_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">
                                        <i class="bx bx-info-circle me-1"></i>Person responsible for coordinating this count period
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information Section -->
                    <div class="form-section">
                        <h6 class="form-section-title">
                            <i class="bx bx-note"></i>Additional Information
                        </h6>
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-4">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                                              id="notes" 
                                              name="notes" 
                                              rows="5" 
                                              placeholder="Enter any additional notes, instructions, or special requirements for this count period...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-1">
                                        <i class="bx bx-info-circle me-1"></i>Optional notes, instructions, or special requirements for this count period
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                <a href="{{ route('inventory.counts.index') }}" class="btn btn-outline-secondary btn-lg">
                                    <i class="bx bx-x me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bx bx-save me-1"></i>Create Count Period
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2 for dropdowns
    if (typeof $().select2 !== 'undefined') {
        $('.select2-single').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: function() {
                return $(this).data('placeholder') || 'Select an option';
            }
        });
    }

    // Set minimum date for end date based on start date
    $('#count_start_date').on('change', function() {
        const startDate = $(this).val();
        if (startDate) {
            $('#count_end_date').attr('min', startDate);
            // If end date is before start date, update it
            const endDate = $('#count_end_date').val();
            if (endDate && endDate < startDate) {
                $('#count_end_date').val(startDate);
            }
        }
    });

    // Validate end date is after start date
    $('#count_start_date, #count_end_date').on('change', function() {
        const startDate = $('#count_start_date').val();
        const endDate = $('#count_end_date').val();
        
        if (startDate && endDate) {
            if (new Date(endDate) < new Date(startDate)) {
                $('#count_end_date').addClass('is-invalid');
                if ($('#count_end_date').next('.invalid-feedback').length === 0) {
                    $('#count_end_date').after('<div class="invalid-feedback">End date must be on or after start date.</div>');
                }
            } else {
                $('#count_end_date').removeClass('is-invalid');
                $('#count_end_date').next('.invalid-feedback').remove();
            }
        }
    });

    // Form validation
    $('#count-period-form').on('submit', function(e) {
        let isValid = true;
        
        // Check required fields
        $(this).find('[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                if ($(this).next('.invalid-feedback').length === 0) {
                    $(this).after('<div class="invalid-feedback">This field is required.</div>');
                }
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            }
        });

        // Validate date range
        const startDate = $('#count_start_date').val();
        const endDate = $('#count_end_date').val();
        
        if (startDate && endDate && new Date(endDate) < new Date(startDate)) {
            $('#count_end_date').addClass('is-invalid');
            if ($('#count_end_date').next('.invalid-feedback').length === 0) {
                $('#count_end_date').after('<div class="invalid-feedback">End date must be on or after start date.</div>');
            }
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            // Scroll to first error
            $('html, body').animate({
                scrollTop: $('.is-invalid').first().offset().top - 100
            }, 500);
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fix the errors in the form before submitting.',
                    confirmButtonColor: '#0d6efd'
                });
            }
        }
    });

    // Remove invalid class on input
    $('input, select, textarea').on('input change', function() {
        if ($(this).val()) {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
    });
});
</script>
@endpush
@endsection
