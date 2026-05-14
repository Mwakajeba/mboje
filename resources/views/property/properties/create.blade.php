@extends('layouts.main')

@section('title', 'Add New Property')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel & Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-building-house'],
            ['label' => 'Property Management', 'url' => route('properties.index'), 'icon' => 'bx bx-building'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <h6 class="mb-0 text-uppercase">CREATE PROPERTY</h6>
        <hr />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-building me-2"></i>New Property</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('properties.store') }}">
                    @csrf

                    <!-- Basic Information -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Property Name <span class="text-danger">*</span></label>
                                <input type="text" 
                                       name="name" 
                                       id="name"
                                       class="form-control @error('name') is-invalid @enderror" 
                                       placeholder="e.g., Downtown Office Building" 
                                       value="{{ old('name') }}"
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="type" class="form-label">Property Type <span class="text-danger">*</span></label>
                                <select name="type" 
                                        id="type"
                                        class="form-select @error('type') is-invalid @enderror" 
                                        required>
                                    <option value="">Select Property Type</option>
                                    <option value="hotel" {{ old('type') == 'hotel' ? 'selected' : '' }}>Hotel</option>
                                    <option value="apartment" {{ old('type') == 'apartment' ? 'selected' : '' }}>Apartment</option>
                                    <option value="office" {{ old('type') == 'office' ? 'selected' : '' }}>Office Building</option>
                                    <option value="retail" {{ old('type') == 'retail' ? 'selected' : '' }}>Retail Space</option>
                                    <option value="warehouse" {{ old('type') == 'warehouse' ? 'selected' : '' }}>Warehouse</option>
                                    <option value="residential" {{ old('type') == 'residential' ? 'selected' : '' }}>Residential</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Location Information -->
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" 
                                       name="address" 
                                       id="address"
                                       class="form-control @error('address') is-invalid @enderror" 
                                       placeholder="Street address, building number" 
                                       value="{{ old('address') }}">
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" 
                                       name="city" 
                                       id="city"
                                       class="form-control @error('city') is-invalid @enderror" 
                                       placeholder="City" 
                                       value="{{ old('city') }}">
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="state" class="form-label">State/Region</label>
                                <input type="text" 
                                       name="state" 
                                       id="state"
                                       class="form-control @error('state') is-invalid @enderror" 
                                       placeholder="State/Region" 
                                       value="{{ old('state') }}">
                                @error('state')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" 
                                       name="country" 
                                       id="country"
                                       class="form-control @error('country') is-invalid @enderror" 
                                       placeholder="Country" 
                                       value="{{ old('country', 'Tanzania') }}">
                                @error('country')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Property Details -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" 
                                        id="status"
                                        class="form-select @error('status') is-invalid @enderror">
                                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="maintenance" {{ old('status') == 'maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                                    <option value="sold" {{ old('status') == 'sold' ? 'selected' : '' }}>Sold</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="purchase_date" class="form-label">Purchase Date</label>
                                <input type="date" 
                                       name="purchase_date" 
                                       id="purchase_date"
                                       class="form-control @error('purchase_date') is-invalid @enderror" 
                                       value="{{ old('purchase_date') }}">
                                @error('purchase_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="current_value" class="form-label">Current Value (TSh)</label>
                                <input type="number" 
                                       name="current_value" 
                                       id="current_value"
                                       class="form-control @error('current_value') is-invalid @enderror" 
                                       placeholder="0" 
                                       min="0" 
                                       step="0.01" 
                                       value="{{ old('current_value') }}">
                                @error('current_value')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Current market value of the property</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="purchase_price" class="form-label">Purchase Price (TSh)</label>
                                <input type="number" 
                                       name="purchase_price" 
                                       id="purchase_price"
                                       class="form-control @error('purchase_price') is-invalid @enderror" 
                                       placeholder="0" 
                                       min="0" 
                                       step="0.01" 
                                       value="{{ old('purchase_price') }}">
                                @error('purchase_price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Original purchase price</small>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea name="description" 
                                          id="description"
                                          class="form-control @error('description') is-invalid @enderror" 
                                          rows="4" 
                                          placeholder="Property description, features, amenities, etc.">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('properties.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-check me-1"></i>Create Property
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
