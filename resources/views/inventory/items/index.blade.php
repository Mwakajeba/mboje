@extends('layouts.main')

@section('title', 'Inventory Items')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Management', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Inventory Items', 'url' => route('inventory.items.index'), 'icon' => 'bx bx-package']
        ]" />

        <div class="row">
            @if(isset($totalItems))
            <div class="col-md-3 mb-3">
                <div class="card border-primary h-100">
                    <div class="card-body text-center">
                        <div class="mb-2"><i class="bx bx-box fs-1 text-primary"></i></div>
                        <h6 class="text-muted mb-1">Total Items {{ isset($loginLocationName) && $loginLocationName ? '(' . $loginLocationName . ')' : '(Login Location)' }}</h6>
                        <h3 class="mb-0">{{ $totalItems }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-success h-100">
                    <div class="card-body text-center">
                        <div class="mb-2"><i class="bx bx-check-circle fs-1 text-success"></i></div>
                        <h6 class="text-muted mb-1">In Stock</h6>
                        <h3 class="mb-0">{{ $inStock }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-warning h-100">
                    <div class="card-body text-center">
                        <div class="mb-2"><i class="bx bx-error fs-1 text-warning"></i></div>
                        <h6 class="text-muted mb-1">Low Stock</h6>
                        <h3 class="mb-0">{{ $lowStock }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-danger h-100">
                    <div class="card-body text-center">
                        <div class="mb-2"><i class="bx bx-x-circle fs-1 text-danger"></i></div>
                        <h6 class="text-muted mb-1">Out of Stock</h6>
                        <h3 class="mb-0">{{ $outOfStock }}</h3>
                    </div>
                </div>
            </div>
            @endif
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">Inventory Items</h4>
                            <div>
                                @can('viewAny', \App\Models\Inventory\Item::class)
                                <a href="{{ route('inventory.items.export') }}" class="btn btn-success me-2">
                                    <i class="bx bx-export me-1"></i> Export Items
                                </a>
                                @endcan
                                @can('manage inventory items')
                                <button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#importModal">
                                    <i class="bx bx-import me-1"></i> Import Items
                                </button>
                                <a href="{{ route('inventory.items.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i> Add New Item
                                </a>
                                @endcan
                            </div>
                        </div>

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

                        <div class="table-responsive">
                            <table id="itemsTable" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Code</th>
                                        <th>Category</th>
                                        <th>Branch visibility</th>
                                        <th>Cost Price</th>
                                        <th>Selling Price</th>
                                        <th>Current Stock</th>
                                        <th>Expiry Tracking</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">
                    <i class="bx bx-import me-2"></i>Import Inventory Items
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="importForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        

                        <!-- Category Selection -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="category_id" class="form-control" required>
                                <option value="">Select Category</option>
                                @foreach($categories ?? [] as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- Product Type Selection -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Product Type <span class="text-danger">*</span></label>
                            <select name="item_type" id="item_type" class="form-control" required>
                                <option value="">Select Product Type</option>
                                <option value="product">Product</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- CSV File Upload -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">CSV File <span class="text-danger">*</span></label>
                            <input type="file" name="csv_file" id="csv_file" class="form-control" accept=".csv" required>
                            <div class="invalid-feedback"></div>
                            <small class="text-muted">Upload a CSV file with item details.</small>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>
                        <strong>CSV format:</strong> Required columns: <code>name</code>, <code>code</code>, <code>unit_price</code>. Optional: <code>description</code>, <code>unit_of_measure</code>, <code>cost_price</code>, <code>minimum_stock</code>, <code>maximum_stock</code>, <code>reorder_level</code>, <code>track_expiry</code>.
                        <strong>Wholesale (optional):</strong> add <code>has_wholesale</code> and <code>wholesale_unit_price</code> — use the &quot;With wholesale columns&quot; template below.
                        <br>
                        <small>
                            <strong>track_expiry:</strong> <code>Yes</code>, <code>True</code>, or <code>1</code> for perishables; otherwise <code>No</code> / <code>0</code>.<br>
                            <strong>has_wholesale:</strong> <code>Yes</code> / <code>True</code> / <code>1</code> only if you provide a positive <code>wholesale_unit_price</code>.<br>
                            Default accounts come from inventory settings.
                        </small>
                    </div>

                    <div class="mt-3">
                        <div class="dropdown d-inline-block">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="inventoryTemplateDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bx bx-download me-1"></i> Download CSV template
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="inventoryTemplateDropdown">
                                <li>
                                    <a class="dropdown-item" href="{{ route('inventory.items.download-template', ['variant' => 'basic']) }}">
                                        <i class="bx bx-file me-1"></i> Standard (retail only)
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('inventory.items.download-template', ['variant' => 'wholesale']) }}">
                                        <i class="bx bx-layer me-1"></i> With wholesale columns
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-upload me-1"></i> Import Items
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        // Initialize DataTable with simple AJAX
        $('#itemsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('inventory.items.index') }}",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            },
            columns: [
                {data: 'name', name: 'name'},
                {data: 'code', name: 'code'},
                {data: 'category_name', name: 'category.name'},
                {data: 'branches_scope', name: 'branches_scope', orderable: false, searchable: false},
                {data: 'cost_price', name: 'cost_price'},
                {data: 'unit_price', name: 'unit_price'},
                {data: 'current_stock', name: 'current_stock', orderable: false, searchable: false},
                {data: 'expiry_tracking_badge', name: 'track_expiry', orderable: false, searchable: false},
                {data: 'status_badge', name: 'is_active', orderable: false, searchable: false},
                {data: 'actions', name: 'actions', orderable: false, searchable: false}
            ],
            order: [[0, 'asc']],
            pageLength: 25,
            responsive: true,
            language: {
                processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                emptyTable: '<div class="text-center p-4"><i class="bx bx-package font-24 text-muted"></i><p class="text-muted mt-2">No items found.</p></div>'
            }
        });

        // Handle delete button clicks
        $(document).on('click', '.delete-btn', function() {
            const itemId = $(this).data('id');
            const deleteUrl = $(this).data('url') || `/inventory/items/${itemId}`;

            if (!itemId) {
                console.error('Delete clicked but no itemId found on button.');
                Swal.fire('Error!', 'Missing item identifier. Please reload the page and try again.', 'error');
                return;
            }

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: deleteUrl,
                        type: 'POST',
                        data: { _method: 'DELETE' },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Deleted!', response.message, 'success');
                                $('#itemsTable').DataTable().ajax.reload();
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            console.error('Delete error:', {
                                status: xhr.status,
                                statusText: xhr.statusText,
                                responseText: xhr.responseText
                            });
                            let json = null;
                            try { json = xhr.responseJSON || JSON.parse(xhr.responseText); } catch(_) {}
                            const message = json?.message || 'Something went wrong!';
                            Swal.fire('Error!', message, 'error');
                        }
                    });
                }
            });
        });

        // Handle import form submission
        $('#importForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();
            
            // Reset previous validation states
            $('.form-control').removeClass('is-invalid');
            $('.invalid-feedback').text('');
            
            // Show loading state
            submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Importing...');
            
            $.ajax({
                url: "{{ route('inventory.items.import') }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Import Successful!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 2000
                        });
                        $('#importModal').modal('hide');
                        $('#importForm')[0].reset();
                        $('#itemsTable').DataTable().ajax.reload();
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    try {
                        console.error('Import error:', {
                            status: xhr.status,
                            statusText: xhr.statusText,
                            responseText: xhr.responseText,
                            responseJSON: xhr.responseJSON
                        });
                    } catch (e) {
                        // ignore console errors
                    }

                    // Try to safely parse JSON if not provided
                    let json = xhr.responseJSON;
                    if (!json && xhr.responseText) {
                        try { json = JSON.parse(xhr.responseText); } catch (_) {}
                    }

                    if (xhr.status === 422) {
                        const errors = (json && json.errors) ? json.errors : null;
                        if (errors && typeof errors === 'object') {
                            Object.keys(errors).forEach(function(key) {
                                const input = $(`[name="${key}"]`);
                                input.addClass('is-invalid');
                                input.next('.invalid-feedback').text(errors[key][0]);
                            });
                        } else {
                            const message = (json && json.message) ? json.message : 'Validation failed.';
                            Swal.fire('Error!', message, 'error');
                        }
                    } else {
                        const message = (json && json.message) ? json.message : 'An error occurred during import.';
                        Swal.fire('Error!', message, 'error');
                    }
                },
                complete: function() {
                    // Reset button state
                    submitBtn.prop('disabled', false).html(originalText);
                }
            });
        });
    });
</script>
@endpush
