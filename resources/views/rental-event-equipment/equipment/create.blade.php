@extends('layouts.main')

@section('title', 'Create New Equipment')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Equipment Master', 'url' => route('rental-event-equipment.equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">CREATE NEW EQUIPMENT</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-center">
                            <div><i class="bx bx-plus me-1 font-22 text-primary"></i></div>
                            <h5 class="mb-0 text-primary">Add New Equipment Item</h5>
                        </div>
                        <hr />

                        <form action="{{ route('rental-event-equipment.equipment.store') }}" method="POST">
                            @csrf

                            <!-- Basic Information -->
                            <div class="card border-primary mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i> Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="name" class="form-label fw-bold">Equipment Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                                       id="name" name="name" value="{{ old('name') }}"
                                                       placeholder="e.g., Plastic Chair, Wedding Tent" required>
                                                @error('name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="category_id" class="form-label fw-bold">Category <span class="text-danger">*</span></label>
                                                <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                                                    <option value="">Select Category</option>
                                                    @foreach($categories as $category)
                                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                            {{ $category->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('category_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="equipment_code" class="form-label fw-bold">Equipment Code</label>
                                                <input type="text" class="form-control @error('equipment_code') is-invalid @enderror"
                                                       id="equipment_code" name="equipment_code" value="{{ old('equipment_code') }}"
                                                       placeholder="Auto-generated if left empty">
                                                @error('equipment_code')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="form-text">Leave empty to auto-generate</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="status" class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                                                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                                    <option value="available" {{ old('status') == 'available' ? 'selected' : '' }}>Available</option>
                                                    <option value="reserved" {{ old('status') == 'reserved' ? 'selected' : '' }}>Reserved</option>
                                                    <option value="on_rent" {{ old('status') == 'on_rent' ? 'selected' : '' }}>On Rent</option>
                                                    <option value="in_event_use" {{ old('status') == 'in_event_use' ? 'selected' : '' }}>In Event Use</option>
                                                    <option value="under_repair" {{ old('status') == 'under_repair' ? 'selected' : '' }}>Under Repair</option>
                                                    <option value="lost" {{ old('status') == 'lost' ? 'selected' : '' }}>Lost</option>
                                                </select>
                                                @error('status')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="mb-3">
                                                <label for="description" class="form-label fw-bold">Description</label>
                                                <textarea class="form-control @error('description') is-invalid @enderror"
                                                          id="description" name="description" rows="3"
                                                          placeholder="Enter equipment description...">{{ old('description') }}</textarea>
                                                @error('description')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quantity & Cost Information -->
                            <div class="card border-info mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="bx bx-calculator me-2"></i> Quantity & Cost Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="quantity_owned" class="form-label fw-bold">Quantity Owned <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control @error('quantity_owned') is-invalid @enderror"
                                                       id="quantity_owned" name="quantity_owned" value="{{ old('quantity_owned', 0) }}"
                                                       min="0" required>
                                                @error('quantity_owned')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="replacement_cost" class="form-label fw-bold">Replacement Cost <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" class="form-control @error('replacement_cost') is-invalid @enderror"
                                                       id="replacement_cost" name="replacement_cost" value="{{ old('replacement_cost', 0) }}"
                                                       min="0" required>
                                                @error('replacement_cost')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="rental_rate" class="form-label fw-bold">Rental Rate</label>
                                                <input type="number" step="0.01" class="form-control @error('rental_rate') is-invalid @enderror"
                                                       id="rental_rate" name="rental_rate" value="{{ old('rental_rate') }}"
                                                       min="0">
                                                @error('rental_rate')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Information -->
                            <div class="card border-secondary mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="bx bx-detail me-2"></i> Additional Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="location" class="form-label fw-bold">Location</label>
                                                <input type="text" class="form-control @error('location') is-invalid @enderror"
                                                       id="location" name="location" value="{{ old('location') }}"
                                                       placeholder="e.g., Warehouse A, Storage Room">
                                                @error('location')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="serial_number" class="form-label fw-bold">Serial Number</label>
                                                <input type="text" class="form-control @error('serial_number') is-invalid @enderror"
                                                       id="serial_number" name="serial_number" value="{{ old('serial_number') }}">
                                                @error('serial_number')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="purchase_date" class="form-label fw-bold">Purchase Date</label>
                                                <input type="date" class="form-control @error('purchase_date') is-invalid @enderror"
                                                       id="purchase_date" name="purchase_date" value="{{ old('purchase_date') }}">
                                                @error('purchase_date')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="manufacturer" class="form-label fw-bold">Manufacturer</label>
                                                <input type="text" class="form-control @error('manufacturer') is-invalid @enderror"
                                                       id="manufacturer" name="manufacturer" value="{{ old('manufacturer') }}">
                                                @error('manufacturer')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="model" class="form-label fw-bold">Model</label>
                                                <input type="text" class="form-control @error('model') is-invalid @enderror"
                                                       id="model" name="model" value="{{ old('model') }}">
                                                @error('model')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="mb-3">
                                                <label for="notes" class="form-label fw-bold">Notes</label>
                                                <textarea class="form-control @error('notes') is-invalid @enderror"
                                                          id="notes" name="notes" rows="3"
                                                          placeholder="Additional notes...">{{ old('notes') }}</textarea>
                                                @error('notes')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <a href="{{ route('rental-event-equipment.equipment.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to Equipment
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i> Create Equipment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
