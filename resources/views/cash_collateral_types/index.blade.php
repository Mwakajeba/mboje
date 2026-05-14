@extends('layouts.main')

@section('title', 'Cash Collateral Types')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Cash Deposits', 'url' => '#', 'icon' => 'bx bx-credit-card']
        ]" />

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 text-primary">Cash Deposits Management</h5>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="bx bx-plus me-1"></i>Add New Deposit Type
            </button>
        </div>

        @if(session('success'))
        <div class="alert alert-success d-flex align-items-start" role="alert">
            <i class="bx bx-check-circle me-2 fs-4"></i>
            <div>
                <strong>Success!</strong>
                {{ session('success') }}
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger d-flex align-items-start" role="alert">
            <i class="bx bx-error me-2 fs-4"></i>
            <div>
                <strong>Error!</strong>
                {{ session('error') }}
            </div>
        </div>
        @endif

        <!-- Stats Cards -->
        <div class="row row-cols-1 row-cols-lg-3 mb-4">
            <div class="col">
                <div class="card radius-10 border-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">Total Types</p>
                                <h4 class="mb-0 text-primary">{{ $totalTypes }}</h4>
                            </div>
                            <div class="widgets-icons bg-gradient-cosmic text-white">
                                <i class='bx bx-list-ul'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 border-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">Active Types</p>
                                <h4 class="mb-0 text-success">{{ $activeTypes }}</h4>
                            </div>
                            <div class="widgets-icons bg-gradient-burning text-white">
                                <i class='bx bx-check-circle'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 border-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">Inactive Types</p>
                                <h4 class="mb-0 text-secondary">{{ $inactiveTypes }}</h4>
                            </div>
                            <div class="widgets-icons bg-gradient-moonlit text-white">
                                <i class='bx bx-x-circle'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="cashCollateralTypesTable" class="table table-striped table-bordered" style="width:100%">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%">#</th>
                                <th width="20%">Name</th>
                                <th width="25%">Chart Account</th>
                                <th width="15%">Account Code</th>
                                <th width="25%">Description</th>
                                <th width="10%">Status</th>
                                <th width="15%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated via DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createModalLabel">
                    <i class="bx bx-plus-circle me-2"></i>Add New Cash Deposit Type
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createForm" method="POST" action="{{ route('cash_collateral_types.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">
                                <i class="bx bx-tag me-1"></i>Deposit Type Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   name="name" 
                                   placeholder="Enter deposit type name"
                                   required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="chart_account_id" class="form-label">
                                <i class="bx bx-book me-1"></i>Chart Account <span class="text-danger">*</span>
                            </label>
                            <select class="form-select select2-single" 
                                    id="chart_account_id" 
                                    name="chart_account_id" 
                                    required>
                                <option value="">-- Select Chart Account --</option>
                                @foreach($chartAccounts as $account)
                                <option value="{{ $account->id }}">
                                    {{ $account->account_code }} - {{ $account->account_name }}
                                </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label for="description" class="form-label">
                                <i class="bx bx-text me-1"></i>Description
                            </label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="3" 
                                      placeholder="Enter description (optional)"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="is_active" 
                                       name="is_active" 
                                       value="1" 
                                       checked>
                                <label class="form-check-label" for="is_active">
                                    <i class="bx bx-check-circle me-1"></i>Active
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span class="btn-text">
                            <i class="bx bx-check me-1"></i>Create Deposit Type
                        </span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="editModalLabel">
                    <i class="bx bx-edit me-2"></i>Edit Cash Deposit Type
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_name" class="form-label">
                                <i class="bx bx-tag me-1"></i>Deposit Type Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="edit_name" 
                                   name="name" 
                                   placeholder="Enter deposit type name"
                                   required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_chart_account_id" class="form-label">
                                <i class="bx bx-book me-1"></i>Chart Account <span class="text-danger">*</span>
                            </label>
                            <select class="form-select select2-single" 
                                    id="edit_chart_account_id" 
                                    name="chart_account_id" 
                                    required>
                                <option value="">-- Select Chart Account --</option>
                                @foreach($chartAccounts as $account)
                                <option value="{{ $account->id }}">
                                    {{ $account->account_code }} - {{ $account->account_name }}
                                </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label for="edit_description" class="form-label">
                                <i class="bx bx-text me-1"></i>Description
                            </label>
                            <textarea class="form-control" 
                                      id="edit_description" 
                                      name="description" 
                                      rows="3" 
                                      placeholder="Enter description (optional)"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="edit_is_active" 
                                       name="is_active" 
                                       value="1">
                                <label class="form-check-label" for="edit_is_active">
                                    <i class="bx bx-check-circle me-1"></i>Active
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-warning" id="editSubmitBtn">
                        <span class="btn-text">
                            <i class="bx bx-check me-1"></i>Update Deposit Type
                        </span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
<style>
    .widgets-icons {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        font-size: 1.5rem;
    }
    
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid rgba(0, 0, 0, 0.125);
    }
    
    .table-dark th {
        background-color: #6c757d;
        border-color: #6c757d;
    }
    
    .modal-header.bg-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    }
    
    .modal-header.bg-warning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
    }
    
    .btn-group .btn {
        margin-right: 2px;
    }
    
    .btn-group .btn:last-child {
        margin-right: 0;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>

<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#cashCollateralTypesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("cash_collateral_types.index") }}',
            type: 'GET'
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'chart_account_name', name: 'chartAccount.account_name' },
            { data: 'chart_account_code', name: 'chartAccount.account_code' },
            { 
                data: 'description', 
                name: 'description', 
                render: function(data, type, row) {
                    if (data && data.length > 50) {
                        return data.substring(0, 50) + '...';
                    }
                    return data || '<span class="text-muted">No description</span>';
                }
            },
            { data: 'status', name: 'is_active', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        responsive: true,
        language: {
            processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
            emptyTable: 'No cash collateral types found',
            zeroRecords: 'No matching records found'
        },
        drawCallback: function(settings) {
            // Initialize tooltips for action buttons
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });

    // Initialize Select2 for chart accounts
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%',
        dropdownParent: $('#createModal')
    });

    // Initialize Select2 for edit modal
    $('#createModal').on('shown.bs.modal', function() {
        $('#chart_account_id').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $('#createModal')
        });
    });

    $('#editModal').on('shown.bs.modal', function() {
        $('#edit_chart_account_id').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $('#editModal')
        });
    });

    // Handle create form submission
    $('#createForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = $('#submitBtn');
        const btnText = submitBtn.find('.btn-text');
        const spinner = submitBtn.find('.spinner-border');
        
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').empty();
        
        // Show loading state
        submitBtn.prop('disabled', true);
        btnText.addClass('d-none');
        spinner.removeClass('d-none');
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    $('#createModal').modal('hide');
                    form[0].reset();
                    $('.select2-single').val(null).trigger('change');
                    table.ajax.reload();
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    // Validation errors
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        const input = $(`[name="${field}"]`);
                        input.addClass('is-invalid');
                        input.next('.invalid-feedback').text(messages[0]);
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred while creating the cash deposit type.'
                    });
                }
            },
            complete: function() {
                // Reset button state
                submitBtn.prop('disabled', false);
                btnText.removeClass('d-none');
                spinner.addClass('d-none');
            }
        });
    });

    // Handle edit button click
    $(document).on('click', '.edit-btn', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const chartAccountId = $(this).data('chart-account-id');
        const description = $(this).data('description');
        const isActive = $(this).data('active');
        
        $('#edit_name').val(name);
        $('#edit_chart_account_id').val(chartAccountId).trigger('change');
        $('#edit_description').val(description);
        $('#edit_is_active').prop('checked', isActive == 1);
        $('#editForm').attr('action', `{{ route('cash_collateral_types.index') }}/${id}`);
        $('#editModal').modal('show');
    });

    // Handle edit form submission
    $('#editForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = $('#editSubmitBtn');
        const btnText = submitBtn.find('.btn-text');
        const spinner = submitBtn.find('.spinner-border');
        
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').empty();
        
        // Show loading state
        submitBtn.prop('disabled', true);
        btnText.addClass('d-none');
        spinner.removeClass('d-none');
        
        $.ajax({
            url: form.attr('action'),
            type: 'PUT',
            data: form.serialize(),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    $('#editModal').modal('hide');
                    table.ajax.reload();
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    // Validation errors
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        const input = $(`[name="${field}"]`);
                        input.addClass('is-invalid');
                        input.next('.invalid-feedback').text(messages[0]);
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred while updating the cash deposit type.'
                    });
                }
            },
            complete: function() {
                // Reset button state
                submitBtn.prop('disabled', false);
                btnText.removeClass('d-none');
                spinner.addClass('d-none');
            }
        });
    });

    // Handle delete action
    $(document).on('click', '.delete-btn', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const name = $(this).data('name');

        Swal.fire({
            title: 'Are you sure?',
            text: `Do you want to delete "${name}"? This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ route("cash_collateral_types.index") }}/${id}`,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', 'An error occurred while deleting the cash deposit type.', 'error');
                    }
                });
            }
        });
    });

    // Reset modal when hidden
    $('.modal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        $(this).find('.is-invalid').removeClass('is-invalid');
        $(this).find('.invalid-feedback').empty();
        $('.select2-single').val(null).trigger('change');
    });
});
</script>
@endpush
