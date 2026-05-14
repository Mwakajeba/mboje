@extends('layouts.main')

@section('title', 'Sales Proformas')

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
            ['label' => 'Proformas', 'url' => '#', 'icon' => 'bx bx-file-blank']
        ]" />
        
        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-file-blank me-2"></i>Sales Proformas</h5>
                                <p class="mb-0 text-muted">Manage and track all sales proformas</p>
                            </div>
                            <div>
                                @can('create sales proforma')
                                <a href="{{ route('sales.proformas.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>Create Proforma
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
            <div class="col-xl-4 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Proformas</p>
                                <h4 class="my-1 text-primary" id="total-proformas">{{ $stats['total'] }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-file-blank align-middle"></i> All proformas</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-file-blank"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
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
            <div class="col-xl-4 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Accepted</p>
                                <h4 class="my-1 text-success" id="accepted-count">{{ $stats['accepted'] }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-check-circle align-middle"></i> Confirmed</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                <i class="bx bx-check-circle"></i>
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
                            <table class="table mb-0" id="proformas-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Proforma #</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Valid Until</th>
                                        <th>Status</th>
                                        <th>Total Amount</th>
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

<!-- View Proforma Modal -->
<div class="modal fade" id="viewProformaModal" tabindex="-1" aria-labelledby="viewProformaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewProformaModalLabel">View Proforma</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="proforma-details">
                <!-- Proforma details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    const table = $('#proformas-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: '{{ route("sales.proformas.index") }}', type: 'GET' },
        columns: [
            { data: 'proforma_number', render: function(data, type, row){ return '<a href="' + '{{ route("sales.proformas.show", ":id") }}'.replace(':id', row.id) + '" class="text-primary fw-bold">' + data + '</a>'; } },
            { data: 'customer_name' },
            { data: 'formatted_date' },
            { data: 'formatted_valid_until' },
            { data: 'status_badge' },
            { data: 'formatted_total', className: 'text-end' },
            { data: 'actions', orderable: false, searchable: false },
        ],
        order: [[2, 'desc']],
        pageLength: 10,
        responsive: true,
    });

    const destroyUrlTmpl = '{{ route('sales.proformas.destroy', ':id') }}';
    const statusUrlTmpl = '{{ route('sales.proformas.update-status', ':id') }}';

    window.convertProforma = function(encodedId, proformaNumber){
        Swal.fire({
            title: 'Convert Proforma',
            text: `Choose what to convert ${proformaNumber} to:`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Convert',
            showDenyButton: true,
            denyButtonText: 'Cancel',
            html: `
                <div class="text-start">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="document_type" id="order" value="order" checked>
                        <label class="form-check-label" for="order">
                            <i class="bx bx-list-ul text-primary"></i> Sales Order
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="document_type" id="invoice" value="invoice">
                        <label class="form-check-label" for="invoice">
                            <i class="bx bx-receipt text-primary"></i> Sales Invoice
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="document_type" id="cash_sale" value="cash_sale">
                        <label class="form-check-label" for="cash_sale">
                            <i class="bx bx-money text-success"></i> Cash Sale
                        </label>
                    </div>
                    <div id="cash-sale-bank-section" class="mt-3" style="display:none;">
                        <label for="bank_account_id" class="form-label">Select Bank Account</label>
                        <select id="bank_account_id" class="form-select">
                            @foreach(($bankAccounts ?? []) as $bank)
                                <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            `,
            didOpen: () => {
                document.querySelectorAll('input[name="document_type"]').forEach(r => {
                    r.addEventListener('change', function(){
                        const section = document.getElementById('cash-sale-bank-section');
                        if (section) section.style.display = (this.value === 'cash_sale') ? '' : 'none';
                    });
                });
            },
            preConfirm: () => {
                const selectedType = document.querySelector('input[name="document_type"]:checked').value;
                return selectedType;
            }
        }).then((result)=>{
            if(!result.isConfirmed) return;
            
            const documentType = result.value;
            const bankAccountId = (documentType === 'cash_sale') ? document.getElementById('bank_account_id')?.value : null;
            $.ajax({
                url: '{{ route("sales.proformas.convert", ":id") }}'.replace(':id', encodedId),
                type: 'POST',
                data: { 
                    _token: '{{ csrf_token() }}', 
                    document_type: documentType,
                    bank_account_id: bankAccountId
                },
                success: function(resp){
                    if(resp.success){
                        Swal.fire({
                            title: 'Converted Successfully!',
                            text: resp.message,
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonText: 'View Document',
                            cancelButtonText: 'Stay Here'
                        }).then((result) => {
                            if(result.isConfirmed && resp.redirect_url){
                                window.location.href = resp.redirect_url;
                            } else {
                                table.ajax.reload(null, false);
                            }
                        });
                    } else {
                        Swal.fire('Error', resp.message || 'Failed to convert proforma', 'error');
                    }
                },
                error: function(xhr){
                    Swal.fire('Error', xhr.responseJSON?.message || 'Failed to convert proforma', 'error');
                }
            });
        });
    }

    window.deleteProforma = function(encodedId, proformaNumber){
        Swal.fire({
            title: 'Delete Proforma?',
            text: `This will permanently delete ${proformaNumber}. This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete permanently',
        }).then((result)=>{
            if(!result.isConfirmed) return;
            $.ajax({
                url: destroyUrlTmpl.replace(':id', encodedId),
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(resp){
                    if(resp.success){
                        Swal.fire('Deleted', 'Proforma permanently deleted.', 'success');
                        table.ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', resp.message || 'Failed to delete', 'error');
                    }
                },
                error: function(xhr){
                    if(xhr.status === 422){
                        Swal.fire('Not Allowed', 'Only draft proformas can be deleted.', 'info');
                    } else {
                        Swal.fire('Error', 'Failed to delete proforma', 'error');
                    }
                }
            });
        });
    }
});
</script>
@endpush
