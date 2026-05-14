@extends('layouts.main')

@section('title', 'Edit Equipment Category')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Equipment Categories', 'url' => route('rental-event-equipment.categories.index'), 'icon' => 'bx bx-category'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">EDIT EQUIPMENT CATEGORY</h6>
        <hr />

        <div class="row">
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-center">
                            <div><i class="bx bx-edit me-1 font-22 text-primary"></i></div>
                            <h5 class="mb-0 text-primary">Edit Equipment Category</h5>
                        </div>
                        <hr />

                        <form action="{{ route('rental-event-equipment.categories.update', $category) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                               id="name" name="name" value="{{ old('name', $category->name) }}"
                                               placeholder="e.g., Furniture, Tents, Audio Equipment" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control @error('description') is-invalid @enderror"
                                                  id="description" name="description" rows="3"
                                                  placeholder="Optional description">{{ old('description', $category->description) }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <a href="{{ route('rental-event-equipment.categories.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to Categories
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i> Update Category
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="bx bx-info-circle me-1 text-info"></i> Information
                        </h6>
                        <hr />
                        <div class="mb-3">
                            <h6>What are Equipment Categories?</h6>
                            <p class="small text-muted">
                                Equipment categories help organize and classify your rental and event equipment items. 
                                Categories make it easier to manage, search, and filter equipment in your inventory.
                            </p>
                        </div>
                        <div class="mb-3">
                            <h6>Examples:</h6>
                            <ul class="small text-muted">
                                <li>Furniture (Chairs, Tables, Stools)</li>
                                <li>Tents & Canopies</li>
                                <li>Audio Equipment</li>
                                <li>Lighting Equipment</li>
                                <li>Decoration Items</li>
                                <li>Kitchen Equipment</li>
                            </ul>
                        </div>
                        <div class="mb-3">
                            <h6>Category Details</h6>
                            <p class="small text-muted mb-1">
                                <strong>Created:</strong> {{ $category->created_at->format('M d, Y') }}
                            </p>
                            @if($category->updated_at != $category->created_at)
                            <p class="small text-muted mb-1">
                                <strong>Last Updated:</strong> {{ $category->updated_at->format('M d, Y') }}
                            </p>
                            @endif
                            <p class="small text-muted mb-0">
                                <strong>Equipment Items:</strong> {{ $category->equipment()->count() }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
