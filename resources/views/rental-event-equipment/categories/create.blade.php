@extends('layouts.main')

@section('title', 'Create New Equipment Category')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Equipment Categories', 'url' => route('rental-event-equipment.categories.index'), 'icon' => 'bx bx-category'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">CREATE NEW EQUIPMENT CATEGORY</h6>
        <hr />

        <div class="row">
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-center justify-content-between">
                            <div><i class="bx bx-plus me-1 font-22 text-primary"></i></div>
                            <h5 class="mb-0 text-primary">Add New Equipment Categories</h5>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addCategoryBtn">
                                <i class="bx bx-plus me-1"></i> Add Line
                            </button>
                        </div>
                        <hr />

                        <form action="{{ route('rental-event-equipment.categories.store') }}" method="POST">
                            @csrf

                            <div id="categoriesContainer">
                                <div class="category-row border rounded p-3 mb-3" data-row="0">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <label class="form-label">Category Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control category-name @error('categories.0.name') is-invalid @enderror"
                                                   name="categories[0][name]" value="{{ old('categories.0.name') }}"
                                                   placeholder="e.g., Furniture, Tents, Audio Equipment" required>
                                            <div class="category-warning text-warning small mt-1" style="display: none;">
                                                <i class="bx bx-error-circle me-1"></i>
                                                This category name already exists!
                                            </div>
                                            @error('categories.0.name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Description</label>
                                            <input type="text" class="form-control category-description"
                                                   name="categories[0][description]" value="{{ old('categories.0.description') }}"
                                                   placeholder="Optional description">
                                        </div>
                                        <div class="col-md-1 d-flex align-items-end">
                                            <button type="button" class="btn btn-outline-danger btn-sm remove-category-btn" disabled>
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-text mb-3">
                                Enter the names and descriptions of the equipment categories. Click "Add Line" to add more categories at once.
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <a href="{{ route('rental-event-equipment.categories.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to Categories
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i> Create Categories
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card-title {
        font-size: 1rem;
        font-weight: 600;
    }

    .form-text {
        font-size: 0.875rem;
    }

    .category-row {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6 !important;
        transition: all 0.2s ease;
    }

    .category-row:hover {
        background-color: #e9ecef;
        border-color: #adb5bd !important;
    }

    .category-row .form-label {
        font-size: 0.875rem;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .remove-category-btn:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    #addCategoryBtn {
        border: 1px solid #0d6efd;
        color: #0d6efd;
    }

    #addCategoryBtn:hover {
        background-color: #0d6efd;
        color: white;
    }

    /* Warning styles for existing category names */
    .is-warning {
        border-color: #ffc107 !important;
        box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25) !important;
    }

    .category-warning {
        font-weight: 500;
        color: #856404 !important;
    }

    .duplicate-warning {
        background-color: #f8d7da;
        border-color: #f5c6cb;
        color: #721c24;
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        let rowCount = 1;

        // Add new category row
        $('#addCategoryBtn').on('click', function() {
            addCategoryRow();
        });

        // Remove category row
        $(document).on('click', '.remove-category-btn', function() {
            $(this).closest('.category-row').remove();
            updateRemoveButtons();
            updateRowIndices();
        });

        // Auto-capitalize first letter for category names
        $(document).on('input', '.category-name', function() {
            let value = $(this).val();
            if (value.length > 0) {
                $(this).val(value.charAt(0).toUpperCase() + value.slice(1));
            }
            checkCategoryName(this);
        });

        // Check for duplicate names within the form
        $(document).on('input', '.category-name', function() {
            checkForDuplicates();
        });

        function addCategoryRow() {
            const rowHtml = `
                <div class="category-row border rounded p-3 mb-3" data-row="${rowCount}">
                    <div class="row">
                        <div class="col-md-5">
                            <label class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control category-name"
                                   name="categories[${rowCount}][name]"
                                   placeholder="e.g., Furniture, Tents, Audio Equipment" required>
                            <div class="category-warning text-warning small mt-1" style="display: none;">
                                <i class="bx bx-error-circle me-1"></i>
                                This category name already exists!
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Description</label>
                            <input type="text" class="form-control category-description"
                                   name="categories[${rowCount}][description]"
                                   placeholder="Optional description">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger btn-sm remove-category-btn">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            $('#categoriesContainer').append(rowHtml);
            rowCount++;
            updateRemoveButtons();
        }

        function checkCategoryName(inputElement) {
            const $input = $(inputElement);
            const $row = $input.closest('.category-row');
            const $warning = $row.find('.category-warning');
            const name = $input.val().trim();

            if (name.length === 0) {
                $warning.hide();
                $input.removeClass('is-warning');
                return;
            }

            // Check against database
            $.ajax({
                url: '{{ route("rental-event-equipment.categories.check-name") }}',
                method: 'GET',
                data: { name: name },
                success: function(response) {
                    if (response.exists) {
                        $warning.show();
                        $input.addClass('is-warning');
                    } else {
                        $warning.hide();
                        $input.removeClass('is-warning');
                    }
                },
                error: function() {
                    console.error('Error checking category name');
                }
            });
        }

        function checkForDuplicates() {
            const names = [];
            let hasDuplicates = false;

            $('.category-name').each(function() {
                const name = $(this).val().trim().toLowerCase();
                if (name.length > 0) {
                    if (names.includes(name)) {
                        hasDuplicates = true;
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                        names.push(name);
                    }
                }
            });

            // Update submit button state
            const $submitBtn = $('button[type="submit"]');
            if (hasDuplicates) {
                $submitBtn.prop('disabled', true);
                if (!$('.duplicate-warning').length) {
                    $('#categoriesContainer').after(`
                        <div class="duplicate-warning alert alert-danger mt-3">
                            <i class="bx bx-error-circle me-1"></i>
                            Duplicate category names detected within the form. Please use unique names.
                        </div>
                    `);
                }
            } else {
                $submitBtn.prop('disabled', false);
                $('.duplicate-warning').remove();
            }
        }

        function updateRemoveButtons() {
            const rows = $('.category-row');
            if (rows.length === 1) {
                rows.find('.remove-category-btn').prop('disabled', true);
            } else {
                rows.find('.remove-category-btn').prop('disabled', false);
            }
        }

        function updateRowIndices() {
            $('.category-row').each(function(index) {
                $(this).attr('data-row', index);
                $(this).find('input[name*="categories["]').each(function() {
                    const name = $(this).attr('name');
                    const newName = name.replace(/categories\[\d+\]/, `categories[${index}]`);
                    $(this).attr('name', newName);
                });
            });
            rowCount = $('.category-row').length;
        }

        // Initialize remove buttons state
        updateRemoveButtons();

        console.log('Create categories form loaded');
    });
</script>
@endpush
