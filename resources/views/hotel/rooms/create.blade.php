@extends('layouts.main')

@section('title', 'Add New Room')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel & Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-building-house'],
            ['label' => 'Room Management', 'url' => route('rooms.index'), 'icon' => 'bx bx-bed'],
            ['label' => 'Add New Room', 'url' => '#', 'icon' => 'bx bx-plus']
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

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Add New Room</h4>
                        <p class="card-subtitle text-muted">Create a new room in your hotel</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('rooms.store') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Property <span class="text-danger">*</span></label>
                                        <select name="property_id" class="form-select @error('property_id') is-invalid @enderror">
                                            <option value="">Select Property</option>
                                            @foreach($properties as $property)
                                                <option value="{{ $property->id }}" {{ old('property_id') == $property->id ? 'selected' : '' }}>
                                                    {{ $property->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('property_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Room Number <span class="text-danger">*</span></label>
                                        <input type="text" name="room_number" class="form-control @error('room_number') is-invalid @enderror" placeholder="e.g., 101, A-201" value="{{ old('room_number') }}">
                                        @error('room_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Room Name</label>
                                        <input type="text" name="room_name" class="form-control @error('room_name') is-invalid @enderror" value="{{ old('room_name') }}" placeholder="e.g., Presidential Suite">
                                        @error('room_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Room Type <span class="text-danger">*</span></label>
                                        <select name="room_type" class="form-select @error('room_type') is-invalid @enderror">
                                            <option value="">Select Room Type</option>
                                            <option value="single" {{ old('room_type') == 'single' ? 'selected' : '' }}>Single Room</option>
                                            <option value="double" {{ old('room_type') == 'double' ? 'selected' : '' }}>Double Room</option>
                                            <option value="twin" {{ old('room_type') == 'twin' ? 'selected' : '' }}>Twin Room</option>
                                            <option value="suite" {{ old('room_type') == 'suite' ? 'selected' : '' }}>Suite</option>
                                            <option value="deluxe" {{ old('room_type') == 'deluxe' ? 'selected' : '' }}>Deluxe Room</option>
                                        </select>
                                        @error('room_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Rate per Night (TSh) <span class="text-danger">*</span></label>
                                        <input type="number" name="rate_per_night" class="form-control @error('rate_per_night') is-invalid @enderror" placeholder="0" min="0" value="{{ old('rate_per_night') }}">
                                        @error('rate_per_night')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Rate per Month (TSh)</label>
                                        <input type="number" name="rate_per_month" class="form-control @error('rate_per_month') is-invalid @enderror" placeholder="0" min="0" value="{{ old('rate_per_month') }}">
                                        @error('rate_per_month')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Capacity <span class="text-danger">*</span></label>
                                        <input type="number" name="capacity" class="form-control @error('capacity') is-invalid @enderror" placeholder="2" min="1" max="10" value="{{ old('capacity', 2) }}">
                                        @error('capacity')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Floor</label>
                                        <input type="number" name="floor_number" class="form-control @error('floor_number') is-invalid @enderror" placeholder="e.g., 1, 2, 3" value="{{ old('floor_number') }}" min="0">
                                        @error('floor_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                                            <option value="available" {{ old('status', 'available') == 'available' ? 'selected' : '' }}>Available</option>
                                            <option value="occupied" {{ old('status') == 'occupied' ? 'selected' : '' }}>Occupied</option>
                                            <option value="maintenance" {{ old('status') == 'maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                                            <option value="out_of_order" {{ old('status') == 'out_of_order' ? 'selected' : '' }}>Out of Order</option>
                                        </select>
                                        @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Room description, amenities, etc.">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Amenities</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" value="wifi" id="wifi" {{ in_array('wifi', old('amenities', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="wifi">WiFi</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" value="ac" id="ac" {{ in_array('ac', old('amenities', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="ac">Air Conditioning</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" value="tv" id="tv" {{ in_array('tv', old('amenities', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="tv">TV</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" value="minibar" id="minibar" {{ in_array('minibar', old('amenities', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="minibar">Minibar</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Additional Features</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" value="balcony" id="balcony" {{ in_array('balcony', old('amenities', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="balcony">Balcony</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" value="ocean_view" id="ocean_view" {{ in_array('ocean_view', old('amenities', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="ocean_view">Ocean View</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" value="city_view" id="city_view" {{ in_array('city_view', old('amenities', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="city_view">City View</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" value="smoking" id="smoking" {{ in_array('smoking', old('amenities', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="smoking">Smoking Allowed</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Room Images Section -->
                            <div class="mb-4">
                                <label class="form-label fw-bold mb-3">Room Images</label>
                                
                                <!-- Image Preview Grid -->
                                <div id="imagePreviewContainer" class="row g-2 mb-3" style="display: none;">
                                    <!-- Images will be previewed here -->
                                </div>

                                <!-- Upload Area -->
                                <div class="border-2 border-dashed border-secondary rounded-3 p-5 text-center bg-light position-relative" id="uploadArea" style="cursor: pointer;">
                                    <input type="file" name="images[]" id="imageInput" class="d-none" accept="image/jpeg,image/jpg,image/png,image/webp" multiple>
                                    
                                    <div class="mb-3">
                                        <i class="bx bx-cloud-upload fs-1 text-muted"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-2">Upload New Photos</h5>
                                    <p class="text-muted mb-2">
                                        Drag and drop high-resolution images here, or <span class="text-primary fw-bold" style="cursor: pointer;">browse files</span>
                                    </p>
                                    <p class="text-muted small text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                        Recommended: 1920x1080px (JPG, PNG)
                                    </p>
                                    
                                    @error('images')
                                        <div class="alert alert-danger mt-3">{{ $message }}</div>
                                    @enderror
                                    @error('images.*')
                                        <div class="alert alert-danger mt-3">{{ $message }}</div>
                                    @enderror
                                </div>

                                <small class="form-text text-muted d-block mt-2">
                                    <i class="bx bx-info-circle"></i> You can upload multiple images (max 10 images, 5MB each). First image will be used as the main/cover image on the website.
                                </small>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i> Save Room
                                </button>
                                <a href="{{ route('rooms.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-x me-1"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    const imageInput = document.getElementById('imageInput');
    const uploadArea = document.getElementById('uploadArea');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');
    let selectedFiles = [];

    // Click on upload area to trigger file input
    uploadArea.addEventListener('click', function(e) {
        if (e.target.tagName !== 'SPAN' && e.target.tagName !== 'I') {
            imageInput.click();
        }
    });

    // Handle file selection
    imageInput.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        
        // Limit to 10 images
        if (selectedFiles.length + files.length > 10) {
            alert('You can only upload a maximum of 10 images.');
            return;
        }

        files.forEach(file => {
            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert(`File ${file.name} is too large. Maximum size is 5MB.`);
                return;
            }

            selectedFiles.push(file);
            previewImage(file);
        });

        // Update file input
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => dataTransfer.items.add(file));
        imageInput.files = dataTransfer.files;
    });

    function previewImage(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const col = document.createElement('div');
            col.className = 'col-md-3 col-6';
            col.innerHTML = `
                <div class="position-relative">
                    <img src="${e.target.result}" alt="Preview" class="img-thumbnail w-100" style="height: 150px; object-fit: cover;">
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 remove-preview-btn" data-file-name="${file.name}">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
            `;
            imagePreviewContainer.appendChild(col);
            imagePreviewContainer.style.display = 'flex';

            // Add remove functionality
            col.querySelector('.remove-preview-btn').addEventListener('click', function() {
                selectedFiles = selectedFiles.filter(f => f.name !== file.name);
                col.remove();
                
                // Update file input
                const dataTransfer = new DataTransfer();
                selectedFiles.forEach(f => dataTransfer.items.add(f));
                imageInput.files = dataTransfer.files;

                if (selectedFiles.length === 0) {
                    imagePreviewContainer.style.display = 'none';
                }
            });
        };
        reader.readAsDataURL(file);
    }

    // Drag and drop functionality
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('border-primary', 'bg-primary-light');
    });

    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('border-primary', 'bg-primary-light');
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('border-primary', 'bg-primary-light');
        
        const files = Array.from(e.dataTransfer.files).filter(file => file.type.startsWith('image/'));
        
        if (selectedFiles.length + files.length > 10) {
            alert('You can only upload a maximum of 10 images.');
            return;
        }

        files.forEach(file => {
            if (file.size > 5 * 1024 * 1024) {
                alert(`File ${file.name} is too large. Maximum size is 5MB.`);
                return;
            }
            selectedFiles.push(file);
            previewImage(file);
        });

        // Update file input
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => dataTransfer.items.add(file));
        imageInput.files = dataTransfer.files;
    });
});
</script>
@endpush
@endsection
