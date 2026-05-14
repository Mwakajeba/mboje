@extends('layouts.main')

@section('title', 'Equipment Categories Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Equipment Categories', 'url' => '#', 'icon' => 'bx bx-category']
        ]" />
        <h6 class="mb-0 text-uppercase">EQUIPMENT CATEGORIES MANAGEMENT</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="card-title d-flex align-items-center">
                                <div><i class="bx bx-category me-1 font-22 text-primary"></i></div>
                                <h5 class="mb-0 text-primary">Equipment Categories</h5>
                            </div>
                            <a href="{{ route('rental-event-equipment.categories.create') }}" class="btn btn-primary">
                                <i class="bx bx-plus me-1"></i> Add New Category
                            </a>
                        </div>
                        <hr />

                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bx bx-error-circle me-1"></i> {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table id="categories-table" class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Category Name</th>
                                        <th>Equipment Count</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded via AJAX -->
                                </tbody>
                            </table>
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
    .table th {
        font-weight: 600;
        font-size: 0.875rem;
    }

    .table td {
        vertical-align: middle;
    }

    .btn-group .btn {
        margin-right: 2px;
    }

    .btn-group .btn:last-child {
        margin-right: 0;
    }

    .fs-1 {
        font-size: 3rem !important;
    }

    .card-title {
        font-size: 1rem;
        font-weight: 600;
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#categories-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("rental-event-equipment.categories.data") }}',
                type: 'GET'
            },
            columns: [
                {
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'equipment_count',
                    name: 'equipment_count',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'created_at_formatted',
                    name: 'created_at'
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ],
            pageLength: 25,
            responsive: true,
            language: {
                processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
            },
            drawCallback: function() {
                // Attach event listeners to delete buttons after table draw
                $('.delete-category-btn').off('click').on('click', function(e) {
                    e.preventDefault();
                    var categoryId = $(this).data('category-id');
                    var categoryName = $(this).data('category-name');
                    var equipmentCount = $(this).data('equipment-count');
                    var deleteUrl = '{{ url("rental-event-equipment/categories") }}/' + categoryId;

                    // Build warning message
                    var warningHtml = '<div class="text-start">' +
                        '<p class="mb-2">Are you sure you want to delete the category <strong>"' + categoryName + '"</strong>?</p>';

                    if (equipmentCount > 0) {
                        warningHtml += '<div class="alert alert-warning mt-2 mb-2">' +
                            '<i class="bx bx-error-circle me-1"></i>' +
                            '<strong>Warning:</strong> This category has <strong>' + equipmentCount + '</strong> equipment item(s) assigned to it. ' +
                            'Deleting this category will require removing all equipment assignments first.' +
                            '</div>';
                    }

                    warningHtml += '<p class="text-danger mb-0"><strong>This action cannot be undone!</strong></p>' +
                        '</div>';

                    Swal.fire({
                        title: 'Delete Equipment Category?',
                        html: warningHtml,
                        icon: equipmentCount > 0 ? 'error' : 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="bx bx-trash me-1"></i> Yes, Delete',
                        cancelButtonText: '<i class="bx bx-x me-1"></i> Cancel',
                        reverseButtons: true,
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Create and submit delete form
                            var form = $('<form>', {
                                'method': 'POST',
                                'action': deleteUrl
                            });
                            form.append($('<input>', {
                                'type': 'hidden',
                                'name': '_token',
                                'value': '{{ csrf_token() }}'
                            }));
                            form.append($('<input>', {
                                'type': 'hidden',
                                'name': '_method',
                                'value': 'DELETE'
                            }));
                            $('body').append(form);
                            form.submit();
                        }
                    });
                });
            },
            initComplete: function() {
                // Add export buttons if needed
                this.api().buttons().container().appendTo('#categories-table_wrapper .col-md-6:eq(0)');
            }
        });

        console.log('Equipment Categories DataTable loaded');
    });
</script>
@endpush
