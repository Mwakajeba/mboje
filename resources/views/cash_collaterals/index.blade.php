@extends('layouts.main')

@section('title', 'Cash Deposits')

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
                <i class="bx bx-plus me-1"></i>Add New Cash Deposit
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
                                <p class="text-muted mb-1">Total Deposits</p>
                                <h4 class="mb-0 text-primary">{{ $totalCollaterals }}</h4>
                            </div>
                            <div class="widgets-icons bg-gradient-cosmic text-white">
                                <i class='bx bx-wallet'></i>
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
                                <p class="text-muted mb-1">Total Amount</p>
                                <h4 class="mb-0 text-success">{{ number_format($totalAmount ?? 0, 2) }}</h4>
                            </div>
                            <div class="widgets-icons bg-gradient-burning text-white">
                                <i class='bx bx-money'></i>
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
                                <p class="text-muted mb-1">Available Types</p>
                                <h4 class="mb-0 text-secondary">{{ $cashCollateralTypes->count() }}</h4>
                            </div>
                            <div class="widgets-icons bg-gradient-moonlit text-white">
                                <i class='bx bx-list-ul'></i>
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
                    <table id="cashCollateralsTable" class="table table-striped table-bordered" style="width:100%">
                        <thead class="table-dark">
                            <tr>
                                <th width="5%">#</th>
                                <th width="20%">Customer</th>
                                <th width="15%">Deposit Type</th>
                                <th width="20%">Chart Account</th>
                                <th width="10%">Amount</th>
                                <th width="10%">Branch</th>
                                <th width="10%">Date</th>
                                <th width="10%">Actions</th>
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
                    <i class="bx bx-plus-circle me-2"></i>Add New Cash Deposit
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createForm" method="POST" action="{{ route('cash_collaterals.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="customer_id" class="form-label">
                                <i class="bx bx-user me-1"></i>Customer <span class="text-danger">*</span>
                            </label>
                            <select class="form-select select2-single"
                                    id="customer_id"
                                    name="customer_id"
                                    required>
                                <option value="">-- Select Customer --</option>
                                @foreach($customers as $customer)
                                @if(empty($occupiedCustomerIds) || !in_array((int) $customer->id, array_map('intval', $occupiedCustomerIds), true))
                                <option value="{{ $customer->id }}">
                                    {{ $customer->name }} - {{ $customer->phone }}
                                </option>
                                @endif
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="type_id" class="form-label">
                                <i class="bx bx-category me-1"></i>Deposit Type <span class="text-danger">*</span>
                            </label>
                            <select class="form-select select2-single"
                                    id="type_id"
                                    name="type_id"
                                    required>
                                <option value="">-- Select Deposit Type --</option>
                                @foreach($cashCollateralTypes as $type)
                                <option value="{{ $type->id }}" data-chart-account="{{ $type->chartAccount->account_code ?? '' }} - {{ $type->chartAccount->account_name ?? '' }}">
                                    {{ $type->name }}
                                </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span class="btn-text">
                            <i class="bx bx-check me-1"></i>Create Cash Deposit
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
                    <i class="bx bx-edit me-2"></i>Edit Cash Deposit
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_customer_id" class="form-label">
                                <i class="bx bx-user me-1"></i>Customer <span class="text-danger">*</span>
                            </label>
                            <select class="form-select select2-single" 
                                    id="edit_customer_id" 
                                    name="customer_id" 
                                    required>
                                <option value="">-- Select Customer --</option>
                                @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">
                                    {{ $customer->name }} - {{ $customer->phone }}
                                </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_type_id" class="form-label">
                                <i class="bx bx-category me-1"></i>Deposit Type <span class="text-danger">*</span>
                            </label>
                            <select class="form-select select2-single" 
                                    id="edit_type_id" 
                                    name="type_id" 
                                    required>
                                <option value="">-- Select Deposit Type --</option>
                                @foreach($cashCollateralTypes as $type)
                                <option value="{{ $type->id }}" data-chart-account="{{ $type->chartAccount->account_code ?? '' }} - {{ $type->chartAccount->account_name ?? '' }}">
                                    {{ $type->name }}
                                </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-warning" id="editSubmitBtn">
                        <span class="btn-text">
                            <i class="bx bx-check me-1"></i>Update Cash Deposit
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
    var table = $('#cashCollateralsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("cash_collaterals.index") }}',
            type: 'GET'
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'customer_name', name: 'customer.name' },
            { data: 'type_name', name: 'type.name' },
            { data: 'chart_account', name: 'type.chartAccount.account_name' },
            { data: 'formatted_amount', name: 'amount', searchable: false },
            { data: 'branch_name', name: 'branch.name' },
            { data: 'formatted_date', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[6, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
            emptyTable: 'No cash deposits found',
            zeroRecords: 'No matching records found'
        },
        drawCallback: function(settings) {
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });

    // Initialize Select2 for all modals
    function initializeSelect2(modalId) {
        $(`#${modalId} .select2-single`).select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $(`#${modalId}`)
        });
    }

    // Initialize Select2 on modal show
    $('#createModal').on('shown.bs.modal', function() {
        initializeSelect2('createModal');
    });

    $('#editModal').on('shown.bs.modal', function() {
        initializeSelect2('editModal');
    });

    // Handle create form submission
    $('#createForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = $('#submitBtn');
        const btnText = submitBtn.find('.btn-text');
        const spinner = submitBtn.find('.spinner-border');
        
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').empty();
        
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
                    const errors = xhr.responseJSON.errors;
                    if (errors) {
                        $.each(errors, function(field, messages) {
                            const input = $(`#createForm [name="${field}"]`);
                            input.addClass('is-invalid');
                            input.next('.invalid-feedback').text(messages[0]);
                        });
                    }
                    const msg = xhr.responseJSON?.message || (errors && errors.customer_id && errors.customer_id[0]);
                    if (msg) {
                        Swal.fire({ icon: 'warning', title: 'Cannot create', text: msg });
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseJSON?.message || 'An error occurred while creating the cash deposit.'
                    });
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                btnText.removeClass('d-none');
                spinner.addClass('d-none');
            }
        });
    });

    // Handle edit button click
    $(document).on('click', '.edit-btn', function() {
        const data = $(this).data();
        
        $('#edit_customer_id').val(data.customerId).trigger('change');
        $('#edit_type_id').val(data.typeId).trigger('change');
        
        $('#editForm').attr('action', `{{ route('cash_collaterals.index') }}/${data.id}`);
        $('#editModal').modal('show');
    });

    // Handle edit form submission
    $('#editForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = $('#editSubmitBtn');
        const btnText = submitBtn.find('.btn-text');
        const spinner = submitBtn.find('.spinner-border');
        
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').empty();
        
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
                    const errors = xhr.responseJSON.errors;
                    if (errors) {
                        $.each(errors, function(field, messages) {
                            const input = $(`#editForm [name="${field}"]`);
                            input.addClass('is-invalid');
                            input.next('.invalid-feedback').text(messages[0]);
                        });
                    }
                    const msg = xhr.responseJSON?.message || (errors && errors.customer_id && errors.customer_id[0]);
                    if (msg) {
                        Swal.fire({ icon: 'warning', title: 'Cannot update', text: msg });
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseJSON?.message || 'An error occurred while updating the cash deposit.'
                    });
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                btnText.removeClass('d-none');
                spinner.addClass('d-none');
            }
        });
    });

    // Handle delete action
    $(document).on('click', '.delete-btn', function(e) {
        e.preventDefault();
        const data = $(this).data();

        Swal.fire({
            title: 'Are you sure?',
            text: `Do you want to delete this cash deposit account? This action cannot be undone!`,
            html: `
                <div class="text-start">
                    <p>Do you want to delete this cash deposit account?</p>
                    <p class="text-warning mb-0"><i class="bx bx-info-circle"></i> <strong>Note:</strong> Accounts with transactions cannot be deleted.</p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while we delete the cash deposit account.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                $.ajax({
                    url: `{{ route("cash_collaterals.index") }}/${data.id}`,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error!', response.message || 'Failed to delete cash deposit account.', 'error');
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while deleting the cash deposit account.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.status === 422) {
                            errorMessage = 'Cannot delete cash deposit account. It has existing transactions.';
                        }
                        Swal.fire('Error!', errorMessage, 'error');
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
