@extends('layouts.main')

@section('title', 'Add Equipment')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Add Equipment', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="bx bx-plus me-2"></i>Equipment Master - Add New Equipment</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('rental-event-equipment.store') }}" id="equipmentForm">
                    @csrf

                    <!-- Basic Information Section -->
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0 text-primary"><i class="bx bx-info-circle me-2"></i>Basic Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">
                                        Item Name <span class="text-danger">*</span>
                                        <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Name of the equipment item (e.g., Plastic Chair, Wedding Tent, Sound System)"></i>
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('item_name') is-invalid @enderror" 
                                           name="item_name" 
                                           value="{{ old('item_name') }}" 
                                           placeholder="e.g., Plastic Chair"
                                           required>
                                    <small class="text-muted">Enter the name of the equipment item</small>
                                    @error('item_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">
                                        Category <span class="text-danger">*</span>
                                        <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Select whether this is rental equipment (customer pays per item) or decoration equipment (used internally for service delivery)"></i>
                                    </label>
                                    <select name="category" 
                                            class="form-select @error('category') is-invalid @enderror" 
                                            id="category"
                                            required>
                                        <option value="">Select category</option>
                                        <option value="rental_equipment" {{ old('category') == 'rental_equipment' ? 'selected' : '' }}>Rental Equipment</option>
                                        <option value="decoration_equipment" {{ old('category') == 'decoration_equipment' ? 'selected' : '' }}>Decoration Equipment</option>
                                    </select>
                                    <small class="text-muted">Rental: Customer pays per item | Decoration: Used internally for service delivery</small>
                                    @error('category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">
                                        Description
                                        <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Additional details about the equipment item"></i>
                                    </label>
                                    <textarea name="description" 
                                              class="form-control @error('description') is-invalid @enderror" 
                                              rows="3" 
                                              placeholder="Enter any additional details about this equipment item...">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quantity & Cost Information Section -->
                    <div class="card border-info mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0 text-info"><i class="bx bx-calculator me-2"></i>Quantity & Cost Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">
                                        Quantity Owned <span class="text-danger">*</span>
                                        <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Total number of this equipment item you own"></i>
                                    </label>
                                    <input type="number" 
                                           class="form-control @error('quantity_owned') is-invalid @enderror" 
                                           name="quantity_owned" 
                                           value="{{ old('quantity_owned') }}" 
                                           min="1"
                                           placeholder="e.g., 1000"
                                           required>
                                    <small class="text-muted">Total quantity of this item in your inventory</small>
                                    @error('quantity_owned')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">
                                        Replacement Cost <span class="text-danger">*</span>
                                        <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Cost to replace one unit of this equipment if lost or damaged"></i>
                                    </label>
                                    <input type="number" 
                                           step="0.01"
                                           class="form-control @error('replacement_cost') is-invalid @enderror" 
                                           name="replacement_cost" 
                                           value="{{ old('replacement_cost') }}" 
                                           min="0"
                                           placeholder="0.00"
                                           required>
                                    <small class="text-muted">Cost per unit to replace this item</small>
                                    @error('replacement_cost')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4" id="rental_rate_field">
                                    <label class="form-label">
                                        Rental Rate (per unit)
                                        <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Rate charged per unit when rented to customers (only for rental equipment)"></i>
                                    </label>
                                    <input type="number" 
                                           step="0.01"
                                           class="form-control @error('rental_rate') is-invalid @enderror" 
                                           name="rental_rate" 
                                           value="{{ old('rental_rate') }}" 
                                           min="0"
                                           placeholder="0.00"
                                           id="rental_rate_input">
                                    <small class="text-muted">Rate per unit for rental equipment</small>
                                    @error('rental_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Equipment Status Section -->
                    <div class="card border-success mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0 text-success"><i class="bx bx-check-circle me-2"></i>Equipment Status</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">
                                        Initial Status <span class="text-danger">*</span>
                                        <i class="bx bx-info-circle text-muted ms-1" data-bs-toggle="tooltip" title="Current status of the equipment when adding to system"></i>
                                    </label>
                                    <select name="status" 
                                            class="form-select @error('status') is-invalid @enderror" 
                                            required>
                                        <option value="">Select status</option>
                                        <option value="available" {{ old('status', 'available') == 'available' ? 'selected' : '' }}>Available - In store ready for use</option>
                                        <option value="reserved" {{ old('status') == 'reserved' ? 'selected' : '' }}>Reserved - Booked for upcoming event</option>
                                        <option value="on_rent" {{ old('status') == 'on_rent' ? 'selected' : '' }}>On Rent - With rental customer</option>
                                        <option value="in_event_use" {{ old('status') == 'in_event_use' ? 'selected' : '' }}>In Event Use - With decoration team</option>
                                        <option value="under_repair" {{ old('status') == 'under_repair' ? 'selected' : '' }}>Under Repair - Damaged</option>
                                        <option value="lost" {{ old('status') == 'lost' ? 'selected' : '' }}>Lost - Missing permanently</option>
                                    </select>
                                    <small class="text-muted">Select the current status of this equipment</small>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Information Note -->
                    <div class="alert alert-info mb-4">
                        <h6 class="alert-heading"><i class="bx bx-info-circle me-2"></i>About Equipment Tracking</h6>
                        <p class="mb-0">
                            <strong>Rental Equipment:</strong> Items that customers rent and pay for per unit (e.g., chairs, tables, tents). 
                            These items appear on customer invoices and generate rental income.
                        </p>
                        <p class="mb-0 mt-2">
                            <strong>Decoration Equipment:</strong> Items used internally by your decoration team to deliver services. 
                            Customers pay for the service package, not individual items. These items are tracked for internal management only.
                        </p>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('rental-event-equipment.index') }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bx bx-save me-1"></i>Save Equipment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(function(){
        // Initialize Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Show/hide rental rate field based on category
        $('#category').on('change', function() {
            if ($(this).val() === 'rental_equipment') {
                $('#rental_rate_field').slideDown();
                $('#rental_rate_input').prop('required', true);
            } else {
                $('#rental_rate_field').slideUp();
                $('#rental_rate_input').prop('required', false);
                $('#rental_rate_input').val('');
            }
        });

        // Trigger on page load
        $('#category').trigger('change');
    });
</script>
@endpush
