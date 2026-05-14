@php
$isEdit = isset($category);
@endphp

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bx bx-error-circle me-2"></i>
    Please fix the following errors:
    <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<form action="{{ $isEdit ? route('inventory.categories.update', $category->encoded_id) : route('inventory.categories.store') }}"
      method="POST">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row">
        <!-- Category Code -->
        <div class="col-md-6 mb-3">
            <label class="form-label">Category Code <span class="text-danger">*</span></label>
            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                value="{{ old('code', $category->code ?? '') }}" placeholder="Enter category code" required>
            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- Category Name -->
        <div class="col-md-6 mb-3">
            <label class="form-label">Category Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $category->name ?? '') }}" placeholder="Enter category name" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- Description -->
        <div class="col-md-12 mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                      rows="3" placeholder="Enter category description">{{ old('description', $category->description ?? '') }}</textarea>
            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- Status -->
        <div class="col-md-12 mb-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" 
                       {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }} 
                       id="is_active">
                <label class="form-check-label" for="is_active">
                    Active Category
                </label>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <button type="submit" class="btn btn-primary px-5">
                <i class="bx bx-save me-1"></i>{{ $isEdit ? 'Update' : 'Create' }} Category
            </button>
            <a href="{{ route('inventory.categories.index') }}" class="btn btn-secondary px-5 ms-2">
                <i class="bx bx-x me-1"></i>Cancel
            </a>
        </div>
    </div>
</form>
