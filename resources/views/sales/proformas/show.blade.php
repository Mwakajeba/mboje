@extends('layouts.main')

@section('title', 'Proforma Details - ' . $proforma->proforma_number)

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Proformas', 'url' => route('sales.proformas.index'), 'icon' => 'bx bx-file-blank'],
            ['label' => $proforma->proforma_number, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <h6 class="mb-0 text-uppercase">PROFORMA DETAILS</h6>
        <hr />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Proforma Header -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h4 class="mb-1">Proforma #{{ $proforma->proforma_number }}</h4>
                                <p class="text-muted mb-0">Created on {{ $proforma->created_at->format('M d, Y \a\t h:i A') }}</p>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-{{ $proforma->status === 'draft' ? 'secondary' : ($proforma->status === 'sent' ? 'info' : ($proforma->status === 'accepted' ? 'success' : ($proforma->status === 'rejected' ? 'danger' : 'warning'))) }} fs-6">
                                    {{ strtoupper($proforma->status) }}
                                </span>
                            </div>
                        </div>

                        <!-- Customer Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">Customer Information</h6>
                                <p><strong>Name:</strong> {{ $proforma->customer->name }}</p>
                                <p><strong>Email:</strong> {{ $proforma->customer->email ?? 'N/A' }}</p>
                                <p><strong>Phone:</strong> {{ $proforma->customer->phone ?? 'N/A' }}</p>
                                <p><strong>Address:</strong> {{ $proforma->customer->address ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">Proforma Information</h6>
                                <p><strong>Date:</strong> {{ $proforma->proforma_date->format('M d, Y') }}</p>
                                <p><strong>Valid Until:</strong> {{ $proforma->valid_until->format('M d, Y') }}</p>
                                <p><strong>Created By:</strong> {{ $proforma->createdBy->name ?? 'N/A' }}</p>
                                <p><strong>Branch:</strong> {{ $proforma->branch->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-primary mb-3">Actions</h6>
                        <div class="d-grid gap-2">
                            @if($proforma->status === 'draft')
                                @can('edit sales proforma')
                                <a href="{{ route('sales.proformas.edit', Hashids::encode($proforma->id)) }}" class="btn btn-warning">
                                    <i class="bx bx-edit me-1"></i> Edit Proforma
                                </a>
                                @endcan
                                <button type="button" class="btn btn-info" onclick="convertProforma()">
                                    <i class="bx bx-transfer me-1"></i> Convert Proforma
                                </button>
                                @can('delete sales proforma')
                                <button type="button" class="btn btn-danger" onclick="deleteProforma()">
                                    <i class="bx bx-trash me-1"></i> Delete Proforma
                                </button>
                                @endcan
                            @endif
                            
                            <a href="{{ route('sales.proformas.export-pdf', \Vinkla\Hashids\Facades\Hashids::encode($proforma->id)) }}" class="btn btn-danger">
                                <i class="bx bxs-file-pdf me-1"></i> Export PDF
                            </a>

                            @if($proforma->status === 'sent')
                                <button type="button" class="btn btn-success" onclick="updateStatus('accepted')">
                                    <i class="bx bx-check-circle me-1"></i> Mark as Accepted
                                </button>
                                <button type="button" class="btn btn-danger" onclick="updateStatus('rejected')">
                                    <i class="bx bx-x-circle me-1"></i> Mark as Rejected
                                </button>
                            @endif

                            @if($proforma->status === 'accepted')
                                <div class="alert alert-success">
                                    <i class="bx bx-check-circle me-2"></i>
                                    <strong>Proforma Accepted</strong><br>
                                    <small class="text-muted">This proforma has been converted to a sales order.</small>
                                </div>
                            @endif

                            <a href="{{ route('sales.proformas.index') }}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Proforma Items -->
        <div class="card">
            <div class="card-body">
                @php
                    $currencyCode = $proforma->currency ?? 'TZS';
                @endphp
                <h6 class="text-primary mb-3">Proforma Items</h6>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Item</th>
                                <th>Code</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">VAT Rate</th>
                                <th class="text-end">VAT Amount</th>
                                <th class="text-end">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($proforma->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <strong>{{ $item->item_name }}</strong>
                                        @if($item->inventoryItem)
                                            <br><small class="text-muted">{{ $item->inventoryItem->description }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $item->item_code }}</td>
                                    <td class="text-end">{{ number_format($item->quantity, 2) }}</td>
                                    <td class="text-end">{{ $currencyCode }} {{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-end">{{ $item->vat_rate > 0 ? $item->vat_rate . '%' : 'N/A' }}</td>
                                    <td class="text-end">{{ $currencyCode }} {{ number_format($item->vat_amount, 2) }}</td>
                                   
                                    <td class="text-end"><strong>{{ $currencyCode }} {{ number_format($item->line_total, 2) }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Proforma Summary -->
        <div class="row">
            <div class="col-lg-8">
                @if($proforma->notes || $proforma->terms_conditions || $proforma->attachment)
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-note me-2"></i>Notes & Terms
                                @if($proforma->attachment)
                                    <a href="{{ asset('storage/' . $proforma->attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary float-end">
                                        <i class="bx bx-paperclip me-1"></i>View Attachment
                                    </a>
                                @endif
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($proforma->notes)
                            <div class="mb-3">
                                <h6>Notes:</h6>
                                <p class="mb-0">{{ $proforma->notes }}</p>
                            </div>
                            @endif
                            @if($proforma->terms_conditions)
                            <div>
                                <h6>Terms & Conditions:</h6>
                                <p class="mb-0">{{ $proforma->terms_conditions }}</p>
                            </div>
                            @endif
                            @if(!$proforma->notes && !$proforma->terms_conditions && $proforma->attachment)
                            <p class="mb-0 text-muted">An attachment has been uploaded for this proforma.</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-primary mb-3">Summary</h6>
                        @php
                            // Calculate VAT from items
                            $calculatedVatAmount = 0;
                            $itemVatRates = [];
                            
                            foreach($proforma->items as $item) {
                                $calculatedVatAmount += $item->vat_amount;
                                
                                if($item->vat_rate > 0) {
                                    $itemVatRates[] = $item->vat_rate;
                                }
                            }
                            
                            // Determine VAT rate display
                            $uniqueVatRates = array_unique($itemVatRates);
                            $vatRateDisplay = count($uniqueVatRates) > 1 ? 'Mixed' : (count($uniqueVatRates) == 1 ? $uniqueVatRates[0] . '%' : '0%');
                        @endphp
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal (without VAT):</span>
                            <span>{{ $currencyCode }} {{ number_format($proforma->subtotal - $calculatedVatAmount, 2) }}</span>
                        </div>
                        @if($proforma->discount_amount > 0)
                            <div class="d-flex justify-content-between mb-2">
                                <span>Discount:</span>
                                <span class="text-danger">-{{ $currencyCode }} {{ number_format($proforma->discount_amount, 2) }}</span>
                            </div>
                        @endif
                        @if($calculatedVatAmount > 0)
                            <div class="d-flex justify-content-between mb-2">
                                <span>VAT ({{ $vatRateDisplay }}):</span>
                                <span>{{ $currencyCode }} {{ number_format($calculatedVatAmount, 2) }}</span>
                            </div>
                        @endif
                        @if($proforma->tax_amount > 0)
                            <div class="d-flex justify-content-between mb-2">
                                <span>Additional Tax:</span>
                                <span>{{ $currencyCode }} {{ number_format($proforma->tax_amount, 2) }}</span>
                            </div>
                        @endif
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total Amount:</strong>
                            <strong class="text-primary fs-5">{{ $currencyCode }} {{ number_format($proforma->total_amount, 2) }}</strong>
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
function updateStatus(status) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'Mark proforma as ' + status.toUpperCase() + '?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, proceed'
    }).then((result)=>{
        if(!result.isConfirmed) return;
        $.ajax({
            url: '{{ route("sales.proformas.update-status", \Vinkla\Hashids\Facades\Hashids::encode($proforma->id)) }}',
            type: 'PATCH',
            data: { status: status, _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    Swal.fire('Updated','Status updated successfully','success').then(()=>location.reload());
                } else {
                    Swal.fire('Error', response.message || 'Failed to update status', 'error');
                }
            },
            error: function() { Swal.fire('Error', 'Error updating status. Please try again.', 'error'); }
        });
    });
}

function convertProforma() {
    Swal.fire({
        title: 'Convert Proforma',
        text: 'Choose what to convert {{ $proforma->proforma_number }} to:',
        icon: 'question',
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
        preConfirm: () => {
            const selectedType = document.querySelector('input[name="document_type"]:checked').value;
            return selectedType;
        }
    }).then((result)=>{
        if(!result.isConfirmed) return;
        
        const documentType = result.value;
        const bankAccountId = (documentType === 'cash_sale') ? document.getElementById('bank_account_id')?.value : null;
        $.ajax({
            url: '{{ route("sales.proformas.convert", \Vinkla\Hashids\Facades\Hashids::encode($proforma->id)) }}',
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
                            location.reload();
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

function deleteProforma() {
    Swal.fire({
        title: 'Delete Proforma?',
        text: 'This will permanently delete {{ $proforma->proforma_number }}. This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete permanently'
    }).then((result)=>{
        if(!result.isConfirmed) return;
        $.ajax({
            url: '{{ route("sales.proformas.destroy", \Vinkla\Hashids\Facades\Hashids::encode($proforma->id)) }}',
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    Swal.fire('Deleted','Proforma deleted successfully','success').then(()=>{ window.location.href = '{{ route("sales.proformas.index") }}'; });
                } else {
                    Swal.fire('Error', response.message || 'Failed to delete', 'error');
                }
            },
            error: function() { Swal.fire('Error', 'Error deleting proforma. Please try again.', 'error'); }
        });
    });
}
</script>
<script nonce="{{ $cspNonce ?? '' }}">
// Show/hide bank selector when choosing cash sale
document.addEventListener('change', function(e){
    if (e.target && e.target.name === 'document_type') {
        const isCashSale = e.target.value === 'cash_sale';
        const section = document.getElementById('cash-sale-bank-section');
        if (section) section.style.display = isCashSale ? '' : 'none';
    }
});
</script>
@endpush 