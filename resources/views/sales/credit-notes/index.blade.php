@extends('layouts.main')

@section('title', 'Credit Notes')

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
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Credit Notes', 'url' => '#', 'icon' => 'bx bx-undo']
        ]" />
        
        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-undo me-2"></i>Credit Notes</h5>
                                <p class="mb-0 text-muted">Manage and track all credit notes</p>
                            </div>
                            <div>
                                @can('create credit notes')
                                <a href="{{ route('sales.credit-notes.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>Create Credit Note
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
                                <p class="mb-0 text-secondary">Total Credit Notes</p>
                                <h4 class="my-1 text-primary" id="total-credit-notes">{{ $stats['total_credit_notes'] }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-undo align-middle"></i> All credit notes</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-undo"></i>
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
                                    <span class="text-success"><i class="bx bx-check-double align-middle"></i> Used</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                <i class="bx bx-check-double"></i>
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
                        <div class="d-lg-flex align-items-center mb-4 gap-3">
                            <div class="position-relative flex-grow-1">
                                <input type="text" class="form-control ps-5 radius-30" placeholder="Search Credit Notes..." id="search-input">
                                <span class="position-absolute top-50 product-show translate-middle-y"><i class="bx bx-search"></i></span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table mb-0" id="credit-notes-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Credit Note #</th>
                                        <th>Customer</th>
                                        <th>Invoice #</th>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Total Amount</th>
                                        <th>Applied Amount</th>
                                        <th>Remaining Amount</th>
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

<!-- View Credit Note Modal -->
<div class="modal fade" id="viewCreditNoteModal" tabindex="-1" aria-labelledby="viewCreditNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewCreditNoteModalLabel">View Credit Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="credit-note-details">
                <!-- Credit note details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Apply Credit Note Modal -->
<div class="modal fade" id="applyCreditNoteModal" tabindex="-1" aria-labelledby="applyCreditNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="applyCreditNoteModalLabel">Apply Credit Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="apply-credit-note-form">
                    <div class="mb-3">
                        <label for="apply-amount" class="form-label">Amount to Apply</label>
                        <input type="number" class="form-control" id="apply-amount" name="amount" step="0.01" required>
                        <div class="form-text">Maximum amount available: <span id="max-amount">0.00</span></div>
                    </div>
                    <div class="mb-3">
                        <label for="apply-description" class="form-label">Description</label>
                        <textarea class="form-control" id="apply-description" name="description" rows="3" placeholder="Enter description for this application..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitApplyCreditNote()">Apply Credit Note</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    const table = $('#credit-notes-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: '{{ route("sales.credit-notes.index") }}', type: 'GET' },
        columns: [
            { data: 'credit_note_number', render: function(data, type, row){ return '<a href="' + '{{ route("sales.credit-notes.show", ":id") }}'.replace(':id', row.encoded_id) + '" class="text-primary fw-bold">' + data + '</a>'; } },
            { data: 'customer_name' },
            { data: 'invoice_number' },
            { data: 'formatted_date' },
            { data: 'type_badge' },
            { data: 'status_badge' },
            { data: 'formatted_total', className: 'text-end' },
            { data: 'formatted_applied', className: 'text-end' },
            { data: 'formatted_remaining', className: 'text-end' },
            { data: 'actions', orderable: false, searchable: false },
        ],
        order: [[3, 'desc']],
        pageLength: 10,
        responsive: true,
    });

    $('#search-input').on('keyup', function(){ table.search(this.value).draw(); });

    const destroyUrlTmpl = '{{ route('sales.credit-notes.destroy', ':id') }}';
    const approveUrlTmpl = '{{ route('sales.credit-notes.approve', ':id') }}';
    const cancelUrlTmpl = '{{ route('sales.credit-notes.cancel', ':id') }}';
    const applyUrlTmpl = '{{ route('sales.credit-notes.apply', ':id') }}';

    window.approveCreditNote = function(encodedId, creditNoteNumber){
        Swal.fire({
            title: 'Approve Credit Note?',
            text: `This will approve ${creditNoteNumber} and create GL transactions.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, approve',
            cancelButtonText: 'Cancel'
        }).then((result)=>{
            if(!result.isConfirmed) return;
            
            $.ajax({
                url: approveUrlTmpl.replace(':id', encodedId),
                type: 'POST',
                data: { 
                    _token: '{{ csrf_token() }}'
                },
                success: function(resp){
                    if(resp.success){
                        Swal.fire({
                            title: 'Approved Successfully!',
                            text: resp.message,
                            icon: 'success'
                        });
                        table.ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', resp.message || 'Failed to approve credit note', 'error');
                    }
                },
                error: function(xhr){
                    Swal.fire('Error', xhr.responseJSON?.message || 'Failed to approve credit note', 'error');
                }
            });
        });
    }

    window.applyCreditNote = function(encodedId, creditNoteNumber, remainingAmount){
        $('#apply-amount').val(remainingAmount);
        $('#max-amount').text(remainingAmount);
        $('#apply-credit-note-form').data('encoded-id', encodedId);
        $('#applyCreditNoteModal').modal('show');
    }

    window.submitApplyCreditNote = function(){
        const encodedId = $('#apply-credit-note-form').data('encoded-id');
        const amount = $('#apply-amount').val();
        const description = $('#apply-description').val();

        if(!amount || amount <= 0){
            Swal.fire('Error', 'Please enter a valid amount', 'error');
            return;
        }

        $.ajax({
            url: applyUrlTmpl.replace(':id', encodedId),
            type: 'POST',
            data: { 
                _token: '{{ csrf_token() }}',
                amount: amount,
                description: description
            },
            success: function(resp){
                if(resp.success){
                    $('#applyCreditNoteModal').modal('hide');
                    Swal.fire({
                        title: 'Applied Successfully!',
                        text: resp.message,
                        icon: 'success'
                    });
                    table.ajax.reload(null, false);
                } else {
                    Swal.fire('Error', resp.message || 'Failed to apply credit note', 'error');
                }
            },
            error: function(xhr){
                Swal.fire('Error', xhr.responseJSON?.message || 'Failed to apply credit note', 'error');
            }
        });
    }

    window.cancelCreditNote = function(encodedId, creditNoteNumber){
        Swal.fire({
            title: 'Cancel Credit Note?',
            text: `This will cancel ${creditNoteNumber}. Please provide a reason:`,
            icon: 'warning',
            input: 'text',
            inputPlaceholder: 'Enter cancellation reason...',
            showCancelButton: true,
            confirmButtonText: 'Yes, cancel',
            cancelButtonText: 'No, keep it',
            inputValidator: (value) => {
                if (!value) {
                    return 'You need to provide a reason!'
                }
            }
        }).then((result)=>{
            if(!result.isConfirmed) return;
            
            $.ajax({
                url: cancelUrlTmpl.replace(':id', encodedId),
                type: 'POST',
                data: { 
                    _token: '{{ csrf_token() }}',
                    reason: result.value
                },
                success: function(resp){
                    if(resp.success){
                        Swal.fire({
                            title: 'Cancelled Successfully!',
                            text: resp.message,
                            icon: 'success'
                        });
                        table.ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', resp.message || 'Failed to cancel credit note', 'error');
                    }
                },
                error: function(xhr){
                    Swal.fire('Error', xhr.responseJSON?.message || 'Failed to cancel credit note', 'error');
                }
            });
        });
    }

    window.deleteCreditNote = function(encodedId, creditNoteNumber){
        Swal.fire({
            title: 'Delete Credit Note?',
            text: `This will delete ${creditNoteNumber}. This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
        }).then((result)=>{
            if(!result.isConfirmed) return;
            
            $.ajax({
                url: destroyUrlTmpl.replace(':id', encodedId),
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(resp){
                    if(resp.success){
                        Swal.fire('Deleted', 'Credit note deleted successfully.', 'success');
                        table.ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', resp.message || 'Failed to delete', 'error');
                    }
                },
                error: function(xhr){
                    if(xhr.status === 422){
                        Swal.fire('Not Allowed', 'Only draft credit notes can be deleted.', 'info');
                    } else {
                        Swal.fire('Error', 'Failed to delete credit note', 'error');
                    }
                }
            });
        });
    }
});
</script>
@endpush 