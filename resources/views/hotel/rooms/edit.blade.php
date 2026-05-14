@extends('layouts.main')

@section('title', 'Edit Room')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel & Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-building-house'],
            ['label' => 'Room Management', 'url' => route('rooms.index'), 'icon' => 'bx bx-bed'],
            ['label' => 'Room Details', 'url' => route('rooms.show', $room), 'icon' => 'bx bx-show'],
            ['label' => 'Edit Room', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title">Edit Room</h4>
                                <p class="card-subtitle text-muted">Update room information and settings</p>
                            </div>
                            <div>
                                <a href="{{ route('rooms.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to Rooms
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('rooms.update', $room) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Property <span class="text-danger">*</span></label>
                                        <select name="property_id" class="form-select select2-single @error('property_id') is-invalid @enderror">
                                            <option value="">Select Property</option>
                                            @foreach($properties as $property)
                                                <option value="{{ $property->id }}" {{ old('property_id', $room->property_id) == $property->id ? 'selected' : '' }}>
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
                                        <input type="text" name="room_number" class="form-control @error('room_number') is-invalid @enderror" value="{{ old('room_number', $room->room_number) }}" placeholder="e.g., 101, A-201">
                                        @error('room_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Room Name</label>
                                        <input type="text" name="room_name" class="form-control @error('room_name') is-invalid @enderror" value="{{ old('room_name', $room->room_name) }}" placeholder="e.g., Presidential Suite">
                                        @error('room_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Room Type <span class="text-danger">*</span></label>
                                        <select name="room_type" class="form-select select2-single @error('room_type') is-invalid @enderror">
                                            <option value="">Select Room Type</option>
                                            <option value="single" {{ old('room_type', $room->room_type) == 'single' ? 'selected' : '' }}>Single Room</option>
                                            <option value="double" {{ old('room_type', $room->room_type) == 'double' ? 'selected' : '' }}>Double Room</option>
                                            <option value="twin" {{ old('room_type', $room->room_type) == 'twin' ? 'selected' : '' }}>Twin Room</option>
                                            <option value="suite" {{ old('room_type', $room->room_type) == 'suite' ? 'selected' : '' }}>Suite</option>
                                            <option value="deluxe" {{ old('room_type', $room->room_type) == 'deluxe' ? 'selected' : '' }}>Deluxe Room</option>
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
                                        <input type="number" name="rate_per_night" class="form-control @error('rate_per_night') is-invalid @enderror" value="{{ old('rate_per_night', $room->rate_per_night) }}" placeholder="0" min="0" step="0.01">
                                        @error('rate_per_night')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Capacity <span class="text-danger">*</span></label>
                                        <input type="number" name="capacity" class="form-control @error('capacity') is-invalid @enderror" value="{{ old('capacity', $room->capacity) }}" placeholder="2" min="1" max="10">
                                        @error('capacity')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Rate per Month (TSh)</label>
                                        <input type="number" name="rate_per_month" class="form-control @error('rate_per_month') is-invalid @enderror" value="{{ old('rate_per_month', $room->rate_per_month) }}" placeholder="0" min="0" step="0.01">
                                        @error('rate_per_month')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Floor</label>
                                        <input type="text" name="floor_number" class="form-control @error('floor_number') is-invalid @enderror" value="{{ old('floor_number', $room->floor_number) }}" placeholder="e.g., 1, 2, Ground">
                                        @error('floor_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Status <span class="text-danger">*</span></label>
                                        <select name="status" class="form-select select2-single @error('status') is-invalid @enderror">
                                            <option value="">Select Status</option>
                                            <option value="available" {{ old('status', $room->status) == 'available' ? 'selected' : '' }}>Available</option>
                                            <option value="occupied" {{ old('status', $room->status) == 'occupied' ? 'selected' : '' }}>Occupied</option>
                                            <option value="maintenance" {{ old('status', $room->status) == 'maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                                            <option value="out_of_order" {{ old('status', $room->status) == 'out_of_order' ? 'selected' : '' }}>Out of Order</option>
                                        </select>
                                        @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">View Type</label>
                                        <select name="view_type" class="form-select select2-single @error('view_type') is-invalid @enderror">
                                            <option value="">Select View Type</option>
                                            <option value="city_view" {{ old('view_type', $room->view_type) == 'city_view' ? 'selected' : '' }}>City View</option>
                                            <option value="ocean_view" {{ old('view_type', $room->view_type) == 'ocean_view' ? 'selected' : '' }}>Ocean View</option>
                                            <option value="garden_view" {{ old('view_type', $room->view_type) == 'garden_view' ? 'selected' : '' }}>Garden View</option>
                                            <option value="street_view" {{ old('view_type', $room->view_type) == 'street_view' ? 'selected' : '' }}>Street View</option>
                                        </select>
                                        @error('view_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Size (sqm)</label>
                                        <input type="number" name="size_sqm" class="form-control @error('size_sqm') is-invalid @enderror" value="{{ old('size_sqm', $room->size_sqm) }}" placeholder="0" min="0" step="0.01">
                                        @error('size_sqm')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Room description, amenities, etc.">{{ old('description', $room->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Amenities</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" value="wifi" id="wifi" {{ in_array('wifi', old('amenities', $room->amenities ?? [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="wifi">WiFi</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" value="ac" id="ac" {{ in_array('ac', old('amenities', $room->amenities ?? [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="ac">Air Conditioning</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" value="tv" id="tv" {{ in_array('tv', old('amenities', $room->amenities ?? [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="tv">TV</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" value="minibar" id="minibar" {{ in_array('minibar', old('amenities', $room->amenities ?? [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="minibar">Minibar</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Additional Features</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" value="balcony" id="balcony" {{ in_array('balcony', old('amenities', $room->amenities ?? [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="balcony">Balcony</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" value="ocean_view" id="ocean_view" {{ in_array('ocean_view', old('amenities', $room->amenities ?? [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="ocean_view">Ocean View</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" value="city_view" id="city_view" {{ in_array('city_view', old('amenities', $room->amenities ?? [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="city_view">City View</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="amenities[]" value="smoking" id="smoking" {{ in_array('smoking', old('amenities', $room->amenities ?? [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="smoking">Smoking Allowed</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Room Images Section -->
                            <div class="mb-4">
                                <label class="form-label fw-bold mb-3">Room Images</label>
                                
                                <!-- Current Images Display -->
                                @if($room->images && count($room->images) > 0)
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Current Images</label>
                                    <div class="row g-2" id="existingImagesContainer">
                                        @foreach($room->images as $index => $image)
                                        <div class="col-md-3 col-6 existing-image-item">
                                            <div class="position-relative">
                                                <img src="{{ $image }}" alt="Room Image {{ $index + 1 }}" class="img-thumbnail w-100" style="height: 150px; object-fit: cover;">
                                                <div class="position-absolute top-0 end-0 m-1">
                                                    <button type="button" class="btn btn-sm btn-danger remove-image-btn" data-image-url="{{ $image }}">
                                                        <i class="bx bx-x"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    <input type="hidden" name="existing_images" id="existing_images" value="{{ json_encode($room->images) }}">
                                    <input type="hidden" name="removed_images" id="removed_images" value="[]">
                                </div>
                                @endif

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

                                <!-- New Images Preview -->
                                <div id="imagePreviewContainer" class="row g-2 mt-3" style="display: none;">
                                    <!-- New images will be previewed here -->
                                </div>

                                <small class="form-text text-muted d-block mt-2">
                                    <i class="bx bx-info-circle"></i> You can upload multiple images (max 10 images total, 5MB each). First image will be used as the main/cover image on the website.
                                </small>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i> Update Room
                                </button>
                                <a href="{{ route('rooms.show', $room) }}" class="btn btn-secondary">
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
    const removedImagesInput = document.getElementById('removed_images');
    let selectedFiles = [];
    let removedImages = [];
    
    if (removedImagesInput) {
        try {
            removedImages = JSON.parse(removedImagesInput.value) || [];
        } catch (e) {
            removedImages = [];
        }
    }

    // Handle existing image removal
    const removeImageBtns = document.querySelectorAll('.remove-image-btn');
    removeImageBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const imageUrl = this.getAttribute('data-image-url');
            if (imageUrl && !removedImages.includes(imageUrl)) {
                removedImages.push(imageUrl);
            }
            
            // Update hidden input
            if (removedImagesInput) {
                removedImagesInput.value = JSON.stringify(removedImages);
            }
            
            // Hide the image container
            this.closest('.existing-image-item').style.display = 'none';
        });
    });

    // Click on upload area to trigger file input
    uploadArea.addEventListener('click', function(e) {
        if (e.target.tagName !== 'SPAN' && e.target.tagName !== 'I') {
            imageInput.click();
        }
    });

    // Handle file selection
    imageInput.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        const existingImagesInput = document.getElementById('existing_images');
        let existingImages = [];
        
        if (existingImagesInput) {
            try {
                existingImages = JSON.parse(existingImagesInput.value) || [];
            } catch (e) {
                existingImages = [];
            }
        }

        const currentImageCount = existingImages.length - removedImages.length;
        
        // Limit to 10 images total
        if (currentImageCount + selectedFiles.length + files.length > 10) {
            alert('You can only upload a maximum of 10 images total.');
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
        const existingImagesInput = document.getElementById('existing_images');
        let existingImages = [];
        
        if (existingImagesInput) {
            try {
                existingImages = JSON.parse(existingImagesInput.value) || [];
            } catch (e) {
                existingImages = [];
            }
        }

        const currentImageCount = existingImages.length - removedImages.length;
        
        if (currentImageCount + selectedFiles.length + files.length > 10) {
            alert('You can only upload a maximum of 10 images total.');
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
