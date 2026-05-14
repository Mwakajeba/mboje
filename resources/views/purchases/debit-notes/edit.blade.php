@extends('layouts.main')

@section('title', 'Edit Debit Note')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchases', 'url' => route('purchases.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Debit Notes', 'url' => route('purchases.debit-notes.index'), 'icon' => 'bx bx-minus-circle'],
            ['label' => $debitNote->debit_note_number, 'url' => route('purchases.debit-notes.show', $debitNote->encoded_id), 'icon' => 'bx bx-show'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">EDIT DEBIT NOTE</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form id="debit-note-form" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            
                            <!-- Header Information -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="supplier_id" class="form-label">Supplier <span class="text-danger">*</span></label>
                                    <select class="form-select select2-single" id="supplier_id" name="supplier_id" required>
                                        <option value="">Select Supplier</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" {{ $debitNote->supplier_id == $supplier->id ? 'selected' : '' }}>
                                                {{ $supplier->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="debit_note_date" class="form-label">Debit Note Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="debit_note_date" name="debit_note_date" 
                                           value="{{ $debitNote->debit_note_date ? $debitNote->debit_note_date->format('Y-m-d') : '' }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="currency" class="form-label">Currency</label>
                                    @php
                                        $currencies = \App\Models\Currency::where('company_id', auth()->user()->company_id)
                                            ->where('is_active', true)
                                            ->orderBy('currency_code')
                                            ->get();
                                        
                                        // Fallback to API currencies if database is empty
                                        if ($currencies->isEmpty()) {
                                            $supportedCurrencies = app(\App\Services\ExchangeRateService::class)->getSupportedCurrencies();
                                            $currencies = collect($supportedCurrencies)->map(function($name, $code) {
                                                return (object)['currency_code' => $code, 'currency_name' => $name];
                                            });
                                        }
                                    @endphp
                                    <select class="form-select select2-single" id="currency" name="currency">
                                        @foreach($currencies as $currency)
                                            <option value="{{ $currency->currency_code }}" 
                                                    {{ old('currency', $debitNote->currency ?? 'TZS') == $currency->currency_code ? 'selected' : '' }}>
                                                {{ $currency->currency_name ?? $currency->currency_code }} ({{ $currency->currency_code }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <label for="warehouse_id" class="form-label">Warehouse</label>
                                    <select class="form-select select2-single" id="warehouse_id" name="warehouse_id">
                                        <option value="">Select Warehouse</option>
                                        @foreach($warehouses as $wh)
                                            <option value="{{ $wh->id }}" {{ $debitNote->warehouse_id == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Required when returning items to stock</small>
                                </div>
                                <div class="col-md-4" id="exchange_rate_group" style="display: {{ ($debitNote->currency ?? 'TZS') !== 'TZS' ? 'block' : 'none' }};">
                                    <label for="exchange_rate" class="form-label">Exchange Rate</label>
                                    <input type="number" step="0.0001" min="0" class="form-control" id="exchange_rate" name="exchange_rate" value="{{ $debitNote->exchange_rate ?? '' }}" placeholder="Enter exchange rate">
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="">Select Type</option>
                                        <option value="return" {{ $debitNote->type == 'return' ? 'selected' : '' }}>Return</option>
                                        <option value="discount" {{ $debitNote->type == 'discount' ? 'selected' : '' }}>Discount</option>
                                        <option value="correction" {{ $debitNote->type == 'correction' ? 'selected' : '' }}>Correction</option>
                                        <option value="overbilling" {{ $debitNote->type == 'overbilling' ? 'selected' : '' }}>Overbilling</option>
                                        <option value="service_adjustment" {{ $debitNote->type == 'service_adjustment' ? 'selected' : '' }}>Service Adjustment</option>
                                        <option value="post_invoice_discount" {{ $debitNote->type == 'post_invoice_discount' ? 'selected' : '' }}>Post Invoice Discount</option>
                                        <option value="refund" {{ $debitNote->type == 'refund' ? 'selected' : '' }}>Refund</option>
                                        <option value="restocking_fee" {{ $debitNote->type == 'restocking_fee' ? 'selected' : '' }}>Restocking Fee</option>
                                        <option value="scrap_writeoff" {{ $debitNote->type == 'scrap_writeoff' ? 'selected' : '' }}>Scrap Write-off</option>
                                        <option value="advance_refund" {{ $debitNote->type == 'advance_refund' ? 'selected' : '' }}>Advance Refund</option>
                                        <option value="fx_adjustment" {{ $debitNote->type == 'fx_adjustment' ? 'selected' : '' }}>FX Adjustment</option>
                                        <option value="other" {{ $debitNote->type == 'other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="reason_code" class="form-label">Reason Code</label>
                                    <input type="text" class="form-control" id="reason_code" name="reason_code" 
                                           value="{{ $debitNote->reason_code }}" placeholder="Enter reason code">
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-12">
                                    <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="reason" name="reason" rows="3" 
                                              placeholder="Enter reason for debit note" required>{{ $debitNote->reason }}</textarea>
                                </div>
                            </div>

                            <!-- Items Section -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">Items</h5>
                                        <button type="button" class="btn btn-sm btn-primary" id="add-item">
                                            <i class="bx bx-plus"></i> Add Item
                                        </button>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="items-table">
                                            <thead>
                                                <tr>
                                                    <th>Item Name</th>
                                                    <th>Description</th>
                                                    <th>Quantity</th>
                                                    <th>Unit Cost</th>
                                                    <th>VAT Type</th>
                                                    <th>VAT Rate</th>
                                                    <th>Return to Stock</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="items-tbody">
                                                @foreach($debitNote->items as $index => $item)
                                                <tr>
                                                <td>
                                                    <select class="form-select item-select select2-single" name="items[{{ $index }}][inventory_item_id]" data-row-index="{{ $index }}">
                                                        <option value="">Select Item</option>
                                                        @foreach($inventoryItems as $inv)
                                                            <option value="{{ $inv->id }}" 
                                                                data-name="{{ $inv->name }}"
                                                                data-code="{{ $inv->code }}"
                                                                data-unit="{{ $inv->unit_of_measure }}"
                                                                data-cost="{{ $inv->cost_price }}"
                                                                {{ $item->inventory_item_id == $inv->id ? 'selected' : '' }}>
                                                                {{ $inv->name }} ({{ $inv->code }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <input type="hidden" name="items[{{ $index }}][item_name]" value="{{ $item->item_name }}">
                                                    <input type="hidden" name="items[{{ $index }}][item_code]" value="{{ $item->item_code }}">
                                                    <input type="hidden" name="items[{{ $index }}][unit_of_measure]" value="{{ $item->unit_of_measure }}">
                                                </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="items[{{ $index }}][description]" 
                                                               value="{{ $item->description }}">
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control" name="items[{{ $index }}][quantity]" 
                                                               step="0.01" min="0.01" value="{{ $item->quantity }}" required>
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control" name="items[{{ $index }}][unit_cost]" 
                                                               step="0.01" min="0" value="{{ $item->unit_cost }}" required>
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="items[{{ $index }}][vat_type]" required>
                                                            <option value="inclusive" {{ $item->vat_type == 'inclusive' ? 'selected' : '' }}>Inclusive</option>
                                                            <option value="exclusive" {{ $item->vat_type == 'exclusive' ? 'selected' : '' }}>Exclusive</option>
                                                            <option value="no_vat" {{ $item->vat_type == 'no_vat' ? 'selected' : '' }}>No VAT</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control" name="items[{{ $index }}][vat_rate]" 
                                                               step="0.01" min="0" max="100" value="{{ $item->vat_rate }}">
                                                    </td>
                                                    <td class="text-center">
                                                        <input type="hidden" name="items[{{ $index }}][return_to_stock]" value="0">
                                                        <div class="form-check d-inline-block">
                                                            <input class="form-check-input" type="checkbox" 
                                                                   name="items[{{ $index }}][return_to_stock]" value="1"
                                                                   {{ $item->return_to_stock ? 'checked' : '' }}>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)" title="Remove line">
                                                            <i class="bx bx-trash me-1"></i><span class="d-none d-md-inline">Remove</span>
                                                        </button>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Options -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="return_to_stock" name="return_to_stock" 
                                               {{ $debitNote->return_to_stock ? 'checked' : '' }}>
                                        <label class="form-check-label" for="return_to_stock">
                                            Return to Stock
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="refund_now" name="refund_now" 
                                               {{ $debitNote->refund_now ? 'checked' : '' }}>
                                        <label class="form-check-label" for="refund_now">
                                            Refund Now
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes, Terms & Attachment -->
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" 
                                              placeholder="Enter any additional notes">{{ $debitNote->notes }}</textarea>
                                </div>
                                <div class="col-md-4">
                                    <label for="terms_conditions" class="form-label">Terms & Conditions</label>
                                    <textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="3" 
                                              placeholder="Terms and conditions...">{{ $debitNote->terms_conditions }}</textarea>
                                </div>
                                <div class="col-md-4">
                                    <label for="attachment" class="form-label">Attachment (optional)</label>
                                    <input type="file" class="form-control @error('attachment') is-invalid @enderror"
                                           id="attachment" name="attachment"
                                           accept=".pdf,.jpg,.jpeg,.png">
                                    @error('attachment')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if(!empty($debitNote->attachment))
                                        <div class="mt-2">
                                            <a href="{{ asset('storage/' . $debitNote->attachment) }}" target="_blank">
                                                <i class="bx bx-link-external me-1"></i>View current attachment
                                            </a>
                                        </div>
                                    @endif
                                    <small class="text-muted">Upload a new file to replace the existing attachment (PDF or image, max 5MB).</small>
                                </div>
                            </div>

                            <!-- Discount -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="discount_amount" class="form-label">Discount Amount</label>
                                    <input type="number" class="form-control" id="discount_amount" name="discount_amount" step="0.01" min="0" value="{{ $debitNote->discount_amount ?? 0 }}">
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('purchases.debit-notes.show', $debitNote->encoded_id) }}" class="btn btn-secondary">
                                            <i class="bx bx-arrow-back me-1"></i> Back
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bx bx-save me-1"></i> Update Debit Note
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
// Initialize Select2 like create form
$(document).ready(function() {
    $('.select2-single').not('.item-select').select2({ theme: 'bootstrap-5', width: '100%' });
    $('.item-select').select2({ theme: 'bootstrap-5', width: '100%' });
    $('.select2-modal').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#itemModal') });

    $('#currency').on('change', function() {
        if ($(this).val() !== 'TZS') {
            $('#exchange_rate_group').show();
        } else {
            $('#exchange_rate_group').hide();
            $('#exchange_rate').val('');
        }
    });
});

// When item is changed, fill hidden fields and default unit cost if empty
$(document).on('change', '.item-select', function() {
    const $sel = $(this);
    const row = $sel.data('row-index');
    const selected = $sel.find('option:selected');
    const name = selected.data('name') || '';
    const code = selected.data('code') || '';
    const unit = selected.data('unit') || '';
    const cost = parseFloat(selected.data('cost') || 0);

    $(`input[name="items[${row}][item_name]"]`).val(name);
    $(`input[name="items[${row}][item_code]"]`).val(code);
    $(`input[name="items[${row}][unit_of_measure]"]`).val(unit);

    const $costInput = $(`input[name="items[${row}][unit_cost]"]`);
    if ($costInput.val() === '' || parseFloat($costInput.val()) === 0) {
        $costInput.val(cost);
    }
});
let itemIndex = {{ $debitNote->items->count() }};

// Open modal
$('#add-item').click(function() {
    $('#itemModal').modal('show');
    resetModalForm();
});

function removeItem(button) {
    button.closest('tr').remove();
}

// Form submission
document.getElementById('debit-note-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    $.ajax({
        url: "{{ route('purchases.debit-notes.update', $debitNote->encoded_id) }}",
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.href = response.redirect;
                });
            } else {
                Swal.fire('Error!', response.message, 'error');
            }
        },
        error: function(xhr) {
            let errorMessage = 'An error occurred while updating the debit note.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            Swal.fire('Error!', errorMessage, 'error');
        }
    });
});

// Modal helpers (mirrors create form behavior)
function resetModalForm() {
    $('#modal_item_id').val('');
    $('#modal_quantity').val(1);
    $('#modal_unit_cost').val('');
    $('#modal_vat_rate').val(18);
    $('#modal_vat_type').val('inclusive');
    $('#modal_return_condition').val('');
    $('#modal_notes').val('');
    $('#modal-line-total').text('0.00');
}

function calculateModalLineTotal() {
    const quantity = parseFloat($('#modal_quantity').val()) || 0;
    const unitCost = parseFloat($('#modal_unit_cost').val()) || 0;
    const vatRate = parseFloat($('#modal_vat_rate').val()) || 0;
    const vatType = $('#modal_vat_type').val();

    let subtotal = quantity * unitCost;
    let vatAmount = 0;
    let lineTotal = 0;
    if (vatType === 'no_vat') { vatAmount = 0; lineTotal = subtotal; }
    else if (vatType === 'exclusive') { vatAmount = subtotal * (vatRate / 100); lineTotal = subtotal + vatAmount; }
    else { vatAmount = subtotal * (vatRate / (100 + vatRate)); lineTotal = subtotal; }

    $('#modal-line-total').text(lineTotal.toFixed(2));
}

$('#modal_quantity, #modal_unit_cost, #modal_vat_rate, #modal_vat_type').on('input change', function() {
    calculateModalLineTotal();
});

$('#modal_item_id').change(function() {
    const selected = $(this).find('option:selected');
    if (selected.val()) {
        $('#modal_unit_cost').val(selected.data('price'));
        $('#modal_vat_rate').val(selected.data('vat-rate'));
        $('#modal_vat_type').val(selected.data('vat-type'));
        const stock = selected.data('stock');
        $('#modal_quantity').attr('max', stock);
        calculateModalLineTotal();
    }
});

$('#add-item-btn').click(function() {
    const selected = $('#modal_item_id option:selected');
    const itemId = selected.val();
    const itemName = selected.data('name');
    const itemCode = selected.data('code');
    const unit = selected.data('unit') || '';
    const quantity = parseFloat($('#modal_quantity').val()) || 0;
    const unitCost = parseFloat($('#modal_unit_cost').val()) || 0;
    const vatType = $('#modal_vat_type').val();
    const vatRate = parseFloat($('#modal_vat_rate').val()) || 0;
    const notes = $('#modal_notes').val();
    const returnCondition = $('#modal_return_condition').val();

    if (!itemId || quantity <= 0 || unitCost < 0) {
        Swal.fire('Error', 'Please fill in all required fields correctly', 'error');
        return;
    }

    let subtotal = quantity * unitCost;
    let vatAmount = 0; let lineTotal = 0;
    if (vatType === 'no_vat') { vatAmount = 0; lineTotal = subtotal; }
    else if (vatType === 'exclusive') { vatAmount = subtotal * (vatRate / 100); lineTotal = subtotal + vatAmount; }
    else { vatAmount = subtotal * (vatRate / (100 + vatRate)); lineTotal = subtotal; }

    const row = `
        <tr>
            <td>
                <input type="hidden" name="items[${itemIndex}][inventory_item_id]" value="${itemId}">
                <input type="hidden" name="items[${itemIndex}][item_name]" value="${itemName}">
                <input type="hidden" name="items[${itemIndex}][item_code]" value="${itemCode}">
                <input type="hidden" name="items[${itemIndex}][unit_of_measure]" value="${unit}">
                <input type="hidden" name="items[${itemIndex}][vat_type]" value="${vatType}">
                <input type="hidden" name="items[${itemIndex}][vat_rate]" value="${vatRate}">
                <input type="hidden" name="items[${itemIndex}][notes]" value="${notes}">
                <input type="hidden" name="items[${itemIndex}][return_condition]" value="${returnCondition}">
                <div class="fw-bold">${itemName}</div>
                <small class="text-muted">${itemCode || ''}</small>
            </td>
            <td>
                <input type="text" class="form-control" name="items[${itemIndex}][description]">
            </td>
            <td>
                <input type="number" class="form-control item-quantity" name="items[${itemIndex}][quantity]" value="${quantity}" step="0.01" min="0.01" data-row="${itemIndex}">
            </td>
            <td>
                <input type="number" class="form-control item-cost" name="items[${itemIndex}][unit_cost]" value="${unitCost}" step="0.01" min="0" data-row="${itemIndex}">
            </td>
            <td>
                <small class="text-muted">${vatType === 'no_vat' ? 'No VAT' : (vatRate + '%')}</small>
            </td>
            <td>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="items[${itemIndex}][return_to_stock]" checked>
                </div>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(this)"><i class="bx bx-trash"></i></button>
            </td>
        </tr>`;

    $('#items-tbody').append(row);
    $('#itemModal').modal('hide');
    itemIndex++;
});
</script>
@endpush

<!-- Item Selection Modal (same UX as create) -->
<div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="modal_item_id" class="form-label">Select Item</label>
                    <select class="form-select select2-modal" id="modal_item_id">
                        <option value="">Choose an item...</option>
                        @foreach($inventoryItems as $item)
                            <option value="{{ $item->id }}"
                                    data-name="{{ $item->name }}"
                                    data-code="{{ $item->code }}"
                                    data-price="{{ $item->resolved_cost_price ?? $item->cost_price }}"
                                    data-stock="{{ $item->current_stock }}"
                                    data-unit="{{ $item->unit_of_measure }}"
                                    data-vat-rate="18"
                                    data-vat-type="inclusive">
                                {{ $item->name }} ({{ $item->code }}) - Cost: {{ number_format($item->cost_price, 2) }} - Stock: {{ $item->current_stock }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="modal_quantity" value="1" step="0.01" min="0.01">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_unit_cost" class="form-label">Unit Cost</label>
                            <input type="number" class="form-control" id="modal_unit_cost" step="0.01" min="0">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_vat_type" class="form-label">VAT Type</label>
                            <select class="form-select" id="modal_vat_type">
                                <option value="no_vat" {{ get_default_vat_type() == 'no_vat' ? 'selected' : '' }}>No VAT</option>
                                <option value="inclusive" {{ get_default_vat_type() == 'inclusive' ? 'selected' : '' }}>Inclusive</option>
                                <option value="exclusive" {{ get_default_vat_type() == 'exclusive' ? 'selected' : '' }}>Exclusive</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_vat_rate" class="form-label">VAT Rate (%)</label>
                            <input type="number" class="form-control" id="modal_vat_rate" value="{{ get_default_vat_rate() }}" step="0.01" min="0">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_return_condition" class="form-label">Return Condition</label>
                            <select class="form-select" id="modal_return_condition">
                                <option value="">Select (optional)</option>
                                <option value="resellable">Resellable</option>
                                <option value="damaged">Damaged</option>
                                <option value="scrap">Scrap</option>
                                <option value="refurbish">Refurbish</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_notes" class="form-label">Line Notes</label>
                            <input type="text" class="form-control" id="modal_notes" placeholder="Optional">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Line Total</label>
                    <div class="border rounded p-2 bg-light">
                        <span class="fw-bold" id="modal-line-total">0.00</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="add-item-btn">Add Item</button>
            </div>
        </div>
    </div>
</div>
