@extends('layouts.main')

@section('title', 'Debit Notes')

@push('styles')
<style>
    .widgets-icons-2 {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #ededed;
        font-size: 27px;
    }
    
    .bg-gradient-primary {
        background: linear-gradient(45deg, #0d6efd, #0a58ca) !important;
    }
    
    .bg-gradient-success {
        background: linear-gradient(45deg, #198754, #146c43) !important;
    }
    
    .bg-gradient-info {
        background: linear-gradient(45deg, #0dcaf0, #0aa2c0) !important;
    }
    
    .bg-gradient-warning {
        background: linear-gradient(45deg, #ffc107, #ffb300) !important;
    }
    
    .bg-gradient-secondary {
        background: linear-gradient(45deg, #6c757d, #5c636a) !important;
    }
    
    .bg-gradient-danger {
        background: linear-gradient(45deg, #dc3545, #bb2d3b) !important;
    }
    
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .table-light {
        background-color: #f8f9fa;
    }
    
    .radius-10 {
        border-radius: 10px;
    }
    
    .border-start {
        border-left-width: 3px !important;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchases', 'url' => route('purchases.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Debit Notes', 'url' => '#', 'icon' => 'bx bx-minus-circle']
        ]" />
        
        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-minus-circle me-2"></i>Debit Notes</h5>
                                <p class="mb-0 text-muted">Manage and track all debit notes</p>
                            </div>
                            <div>
                                @can('create debit notes')
                                <a href="{{ route('purchases.debit-notes.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>Create Debit Note
                                </a>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Stats -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Debit Notes</p>
                                <h4 class="my-1 text-primary" id="total-debit-notes">{{ $stats['total_debit_notes'] }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-minus-circle align-middle"></i> All debit notes</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-minus-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-secondary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Draft</p>
                                <h4 class="my-1 text-secondary" id="draft-count">{{ $stats['draft'] }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-secondary"><i class="bx bx-edit align-middle"></i> In progress</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-secondary text-white ms-auto">
                                <i class="bx bx-edit"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Issued</p>
                                <h4 class="my-1 text-info" id="issued-count">{{ $stats['issued'] }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-info"><i class="bx bx-check-circle align-middle"></i> Approved</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                <i class="bx bx-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Applied</p>
                                <h4 class="my-1 text-success" id="applied-count">{{ $stats['applied'] }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-credit-card align-middle"></i> Used</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                <i class="bx bx-credit-card"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="debit-notes-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Debit Note #</th>
                                        <th>Supplier</th>
                                        <th>Reference Invoice</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Type</th>
                                        <th>Total Amount</th>
                                        <th>Applied Amount</th>
                                        <th>Remaining</th>
                                        <th class="text-center">Actions</th>
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
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize DataTable
    $('#debit-notes-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('purchases.debit-notes.index') }}",
            type: 'GET'
        },
        columns: [
            { data: 'debit_note_number', name: 'debit_note_number' },
            { data: 'supplier_name', name: 'supplier_name' },
            { data: 'reference_invoice', name: 'reference_invoice' },
            { data: 'debit_note_date_formatted', name: 'debit_note_date' },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'type_badge', name: 'type', orderable: false },
            { data: 'total_amount_formatted', name: 'total_amount' },
            { data: 'applied_amount_formatted', name: 'applied_amount' },
            { data: 'remaining_amount_formatted', name: 'remaining_amount' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            processing: "Loading debit notes...",
            emptyTable: "No debit notes found",
            zeroRecords: "No matching debit notes found"
        }
    });
});

// Approve Debit Note
function approveDebitNote(id) {
    Swal.fire({
        title: 'Approve Debit Note?',
        text: "This will finalize the debit note, post inventory/GL, and mark it approved.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, approve it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/purchases/debit-notes/${id}/approve`,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Approved!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        });
                        $('#debit-notes-table').DataTable().ajax.reload();
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred while approving the debit note.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    Swal.fire('Error!', errorMessage, 'error');
                }
            });
        }
    });
}

// Apply Debit Note
function applyDebitNote(id) {
    // Redirect to application form or show modal
    // Tip: Apply allocates the approved debit note value - to invoice (offset), refund (money back), or debit balance.
    window.location.href = `/purchases/debit-notes/${id}/apply`;
}

// Cancel Debit Note
function cancelDebitNote(id) {
    Swal.fire({
        title: 'Cancel Debit Note?',
        text: "This will void the debit note and revert any effects that are allowed.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, cancel it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/purchases/debit-notes/${id}/cancel`,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Cancelled!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        });
                        $('#debit-notes-table').DataTable().ajax.reload();
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred while cancelling the debit note.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    Swal.fire('Error!', errorMessage, 'error');
                }
            });
        }
    });
}

// Delete Debit Note
function deleteDebitNote(id, name) {
    Swal.fire({
        title: 'Delete Debit Note?',
        text: `This permanently removes debit note "${name}". Use cancel instead if you want to keep an audit trail.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete permanently!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (!result.isConfirmed) return;
        
        $.ajax({
            url: `/purchases/debit-notes/${id}`,
            type: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    });
                    $('#debit-notes-table').DataTable().ajax.reload();
                } else {
                    Swal.fire('Error!', response.message, 'error');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while deleting the debit note.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                Swal.fire('Error!', errorMessage, 'error');
            }
        });
    });
}
</script>
@endpush
