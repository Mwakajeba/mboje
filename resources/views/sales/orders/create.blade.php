@extends('layouts.main')

@section('title', 'Create Sales Order')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Orders', 'url' => route('sales.orders.index'), 'icon' => 'bx bx-shopping-cart'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">CREATE SALES ORDER</h6>
        <hr />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-shopping-cart me-2"></i>New Sales Order</h5>
            </div>
            <div class="card-body">
                <form id="order-form" enctype="multipart/form-data">
                    @if(isset($proforma))
                        <input type="hidden" name="proforma_id" value="{{ $proforma->id }}">
                    @endif
                    @csrf
                    <div class="row">
                        <!-- Customer Information -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                <select class="form-select select2-single" id="customer_id" name="customer_id" required>
                                    <option value="">Select Customer</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="order_date" class="form-label">Order Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="order_date" name="order_date" 
                                       value="{{ date('Y-m-d') }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="expected_delivery_date" class="form-label">Expected Delivery Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="expected_delivery_date" name="expected_delivery_date" 
                                       value="{{ date('Y-m-d', strtotime('+7 days')) }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="payment_terms" class="form-label">Payment Terms</label>
                                <select class="form-select" id="payment_terms" name="payment_terms">
                                    <option value="immediate">Immediate Payment</option>
                                    <option value="net_15">Net 15 Days</option>
                                    <option value="net_30" selected>Net 30 Days</option>
                                    <option value="net_45">Net 45 Days</option>
                                    <option value="net_60">Net 60 Days</option>
                                    <option value="custom">Custom</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="payment_days" class="form-label">Payment Days</label>
                                <input type="number" class="form-control" id="payment_days" name="payment_days" 
                                       placeholder="Number of days for payment" min="0">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Section -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Items</h6>
                                <button type="button" class="btn btn-primary btn-sm" id="add-item">
                                    <i class="bx bx-plus me-1"></i>Add Item
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table" id="items-table">
                                    <thead>
                                        <tr>
                                            <th width="25%">Item</th>
                                            <th width="15%">Quantity</th>
                                            <th width="15%">Unit Price</th>
                                            <th width="15%">VAT</th>
                                            <th width="15%">Total</th>
                                            <th width="10%">Action</th>
                                            <th width="5%"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="items-tbody">
                                        <!-- Items will be added here dynamically -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                            <td><strong id="subtotal">0.00</strong></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="subtotal" id="subtotal-input" value="0">
                                        <tr id="vat-row" style="display: none;">
                                            <td colspan="4" class="text-end"><strong>VAT (<span id="vat-rate-display">0</span>%):</strong></td>
                                            <td><strong id="vat-amount">0.00</strong></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="vat_amount" id="vat-amount-input" value="0">
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Additional Tax:</strong></td>
                                            <td>
                                                <input type="number" class="form-control" id="tax_amount" name="tax_amount" 
                                                       value="0" step="0.01" min="0">
                                            </td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Discount:</strong></td>
                                            <td>
                                                <input type="number" class="form-control" id="discount_amount" name="discount_amount" 
                                                       value="0" step="0.01" min="0">
                                            </td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr class="table-info">
                                            <td colspan="4" class="text-end"><strong>Total Amount:</strong></td>
                                            <td><strong id="total-amount">0.00</strong></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="total_amount" id="total-amount-input" value="0">
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Notes, Terms and Attachment -->
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="4" 
                                          placeholder="Additional notes for this order..."></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="terms_conditions" class="form-label">Terms & Conditions</label>
                                <textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="4" 
                                          placeholder="Terms and conditions..."></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="attachment" class="form-label">Attachment (optional)</label>
                                <input type="file" class="form-control @error('attachment') is-invalid @enderror"
                                       id="attachment" name="attachment"
                                       accept=".pdf,.jpg,.jpeg,.png">
                                @error('attachment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Upload a file (PDF or image, max 5MB).</small>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('sales.orders.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="bx bx-check me-1" data-processing-text="Creating..."></i>Create Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Item Selection Modal -->
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
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" 
                                    data-name="{{ $item->name }}" 
                                    data-code="{{ $item->code }}"
                                    data-price="{{ $item->resolved_unit_price ?? $item->unit_price }}"
                                    data-stock="{{ $item->current_stock }}"
                                    data-unit="{{ $item->unit_of_measure }}">
                                {{ $item->name }} ({{ $item->code }}) - Stock: {{ $item->current_stock }}
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
                            <label for="modal_unit_price" class="form-label">Unit Price</label>
                            <input type="number" class="form-control" id="modal_unit_price" step="0.01" min="0">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_item_vat_type" class="form-label">Item VAT Type</label>
                            <select class="form-select" id="modal_item_vat_type">
                                <option value="no_vat" {{ get_default_vat_type() == 'no_vat' ? 'selected' : '' }}>No VAT</option>
                                <option value="vat_inclusive" {{ get_default_vat_type() == 'inclusive' ? 'selected' : '' }}>VAT Inclusive</option>
                                <option value="vat_exclusive" {{ get_default_vat_type() == 'exclusive' ? 'selected' : '' }}>VAT Exclusive</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_item_vat_rate" class="form-label">Item VAT Rate (%)</label>
                            <input type="number" class="form-control" id="modal_item_vat_rate" value="{{ get_default_vat_rate() }}" step="0.01" min="0" max="100">
                            <small class="text-muted">Individual VAT rate for this item</small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Item Total Preview</label>
                            <div class="form-control-plaintext border rounded p-2 bg-light">
                                <strong id="modal_item_total_preview">0.00</strong>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="mb-3">
                    <label for="modal_description" class="form-label">Description</label>
                    <textarea class="form-control" id="modal_description" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="add-item-to-table">Add Item</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
var copyFromInvoice = @json($copyFromInvoice ?? null);
$(document).ready(function() {
    let itemCounter = 0;

    function addItemFromCopyInvoice(item) {
        itemCounter++;
        const vatType = item.vat_type || 'vat_inclusive';
        const itemVatRate = parseFloat(item.vat_rate) || 0;
        const vatDisplay = vatType === 'no_vat' ? 'No VAT' : (itemVatRate + '% (' + vatType.replace('vat_', '') + ')');
        const lineTotal = parseFloat(item.line_total) || 0;
        const row = `
            <tr data-item-id="${item.inventory_item_id}" data-row-id="${itemCounter}">
                <td>
                    <strong>${(item.item_name || '').replace(/</g, '&lt;')}</strong><br>
                    <small class="text-muted">${(item.item_code || '').replace(/</g, '&lt;')}</small>
                    <input type="hidden" name="items[${itemCounter}][inventory_item_id]" value="${item.inventory_item_id}">
                    <input type="hidden" name="items[${itemCounter}][description]" value="">
                    <input type="hidden" name="items[${itemCounter}][vat_type]" value="${vatType}">
                    <input type="hidden" name="items[${itemCounter}][vat_rate]" value="${itemVatRate}">
                    <input type="hidden" name="items[${itemCounter}][vat_amount]" value="${parseFloat(item.vat_amount || 0)}">
                    <input type="hidden" name="items[${itemCounter}][line_total]" value="${lineTotal.toFixed(2)}">
                </td>
                <td>
                    <input type="number" class="form-control item-quantity" name="items[${itemCounter}][quantity]" value="${item.quantity}" step="0.01" min="0.01" data-row="${itemCounter}" data-available-stock="0">
                </td>
                <td>
                    <input type="number" class="form-control item-price" name="items[${itemCounter}][unit_price]" value="${item.unit_price}" step="0.01" min="0" data-row="${itemCounter}">
                </td>
                <td>
                    <span class="form-control-plaintext">${vatDisplay}</span>
                </td>
                <td>
                    <small class="text-muted">${vatDisplay}</small>
                </td>
                <td>
                    <span class="item-total">${lineTotal.toFixed(2)}</span>
                </td>
                <td>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-item"><i class="bx bx-trash"></i></button>
                </td>
                <td></td>
            </tr>
        `;
        $('#items-tbody').append(row);
    }

    function populateFormFromCopyInvoice(data) {
        if (!data || !data.items || !data.items.length) return;
        $('#customer_id').val(data.customer.id).trigger('change');
        $('#order_date').val(data.order_date || '');
        $('#expected_delivery_date').val(data.expected_delivery_date || '');
        $('#payment_terms').val(data.payment_terms || 'net_30');
        $('#payment_days').val(data.payment_days ?? 30);
        $('#notes').val(data.notes || '');
        $('#terms_conditions').val(data.terms_conditions || '');
        $('#items-tbody').empty();
        itemCounter = 0;
        data.items.forEach(function(it) { addItemFromCopyInvoice(it); });
        calculateTotals();
    }

    // Initialize Select2
    $('.select2-single').select2({
        placeholder: 'Select',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap-5'
    });

    // Initialize Select2 for modal with search
    $('.select2-modal').select2({
        placeholder: 'Search for an item...',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap-5',
        dropdownParent: $('#itemModal'),
        minimumInputLength: 0,
        templateResult: formatItemOption,
        templateSelection: formatItemSelection
    });

    // Add Item Button
    $('#add-item').click(function() {
        $('#itemModal').modal('show');
        resetItemModal();
    });

    // Item selection change
    $('#modal_item_id').change(function() {
        const selectedOption = $(this).find(':selected');
        const price = selectedOption.data('price');
        $('#modal_unit_price').val(price || 0);
        calculateModalItemTotal();
    });

    // Modal item calculations
    $('#modal_quantity, #modal_unit_price, #modal_item_vat_rate').on('input', function() {
        calculateModalItemTotal();
    });

    $('#modal_item_vat_type').on('change', function() {
        calculateModalItemTotal();
    });

    // Add item to table
    $('#add-item-to-table').click(function() {
        const itemId = $('#modal_item_id').val();
        const itemName = $('#modal_item_id option:selected').data('name');
        const itemCode = $('#modal_item_id option:selected').data('code');
        const quantity = parseFloat($('#modal_quantity').val()) || 0;
        const unitPrice = parseFloat($('#modal_unit_price').val()) || 0;
        const description = $('#modal_description').val();
        const itemVatType = $('#modal_item_vat_type').val();
        const itemVatRate = parseFloat($('#modal_item_vat_rate').val()) || 0;

        if (!itemId || quantity <= 0) {
            Swal.fire('Error', 'Please select an item and enter a valid quantity', 'error');
            return;
        }

        // Check if item already exists
        if ($(`tr[data-item-id="${itemId}"]`).length > 0) {
            Swal.fire('Error', 'This item is already added. Please edit the existing entry.', 'error');
            return;
        }

        // Calculate item total with VAT
        const baseAmount = quantity * unitPrice;
        let lineTotal = baseAmount;
        let itemVatAmount = 0;
        
        if (itemVatType === 'vat_inclusive' && itemVatRate > 0) {
            const vatFactor = itemVatRate / 100;
            itemVatAmount = lineTotal * vatFactor / (1 + vatFactor);
        } else if (itemVatType === 'vat_exclusive' && itemVatRate > 0) {
            itemVatAmount = lineTotal * (itemVatRate / 100);
            lineTotal += itemVatAmount;
        }

        itemCounter++;

        const vatDisplay = itemVatType === 'no_vat' ? 'No VAT' : `${itemVatRate}% (${itemVatType.replace('_', ' ')})`;

        const availableStock = $('#modal_item_id option:selected').data('stock') || 0;
        const row = `
            <tr data-item-id="${itemId}" data-row-id="${itemCounter}">
                <td>
                    <strong>${itemName}</strong><br>
                    <small class="text-muted">${itemCode}</small>
                    <input type="hidden" name="items[${itemCounter}][inventory_item_id]" value="${itemId}">
                    <input type="hidden" name="items[${itemCounter}][description]" value="${description}">
                    <input type="hidden" name="items[${itemCounter}][vat_type]" value="${itemVatType}">
                    <input type="hidden" name="items[${itemCounter}][vat_rate]" value="${itemVatRate}">
                    <input type="hidden" name="items[${itemCounter}][vat_amount]" value="${itemVatAmount}">
                    <input type="hidden" name="items[${itemCounter}][line_total]" value="${lineTotal}">
                </td>
                <td>
                    <input type="number" class="form-control item-quantity" 
                           name="items[${itemCounter}][quantity]" value="${quantity}" 
                           step="0.01" min="0.01" data-row="${itemCounter}" data-available-stock="${availableStock}">
                </td>
                <td>
                    <input type="number" class="form-control item-price" 
                           name="items[${itemCounter}][unit_price]" value="${unitPrice}" 
                           step="0.01" min="0" data-row="${itemCounter}">
                </td>
                <td>
                    <span class="form-control-plaintext">${vatDisplay}</span>
                </td>
                <td>
                    <small class="text-muted">${vatDisplay}</small>
                </td>
                <td>
                    <span class="item-total">${lineTotal.toFixed(2)}</span>
                </td>
                <td>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-item">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
                <td></td>
            </tr>
        `;

        $('#items-tbody').append(row);
        $('#itemModal').modal('hide');
        calculateTotals();
    });

    // Remove item
    $(document).on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        calculateTotals();
    });

    // Recalculate on input change
    $(document).on('input', '.item-quantity, .item-price, #tax_amount, #discount_amount', function() {
        const row = $(this).data('row');
        if (row) {
            updateRowTotal(row);
        }
        calculateTotals();
    });

    // Form submission
    $('#order-form').submit(function(e) {
        e.preventDefault();
        
        if ($('#items-tbody tr').length === 0) {
            Swal.fire('Error', 'Please add at least one item to the order', 'error');
            return;
        }

        const formData = new FormData(this);
        const submitBtn = $('#submit-btn');
        
        submitBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i>Creating...');

        $.ajax({
            url: '{{ route("sales.orders.store") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = '{{ route("sales.orders.index") }}';
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    displayValidationErrors(errors);
                    Swal.fire('Validation Error', 'Please check the form for errors', 'error');
                } else {
                    Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Create Order');
            }
        });
    });

    function updateRowTotal(row) {
        const quantity = parseFloat($(`input[name="items[${row}][quantity]"]`).val()) || 0;
        const unitPrice = parseFloat($(`input[name="items[${row}][unit_price]"]`).val()) || 0;
        const itemVatType = $(`input[name="items[${row}][vat_type]"]`).val();
        const itemVatRate = parseFloat($(`input[name="items[${row}][vat_rate]"]`).val()) || 0;
        
        let lineTotal = quantity * unitPrice;
        let itemVatAmount = 0;
        
        // Apply individual item VAT
        if (itemVatType === 'vat_inclusive' && itemVatRate > 0) {
            // VAT is already included in the line total
            const vatFactor = itemVatRate / 100;
            itemVatAmount = lineTotal * vatFactor / (1 + vatFactor);
        } else if (itemVatType === 'vat_exclusive' && itemVatRate > 0) {
            itemVatAmount = lineTotal * (itemVatRate / 100);
            lineTotal += itemVatAmount;
        }
        
        // Update hidden fields
        $(`input[name="items[${row}][vat_amount]"]`).val(itemVatAmount.toFixed(2));
        $(`input[name="items[${row}][line_total]"]`).val(lineTotal.toFixed(2));
        
        $(`tr[data-row-id="${row}"] .item-total`).text(`${lineTotal.toFixed(2)}`);
    }

    function calculateTotals() {
        let subtotal = 0;
        let totalVatFromItems = 0;
        
        // Calculate subtotal and individual VAT amounts
        $('.item-total').each(function(index) {
            const rowId = $(this).closest('tr').data('row-id');
            const itemVatType = $(`input[name="items[${rowId}][vat_type]"]`).val();
            const itemVatRate = parseFloat($(`input[name="items[${rowId}][vat_rate]"]`).val()) || 0;
            const lineTotal = parseFloat($(this).text()) || 0;
            
            let rowVatAmount = 0;
            let rowNetAmount = 0;
            
            if (itemVatType === 'vat_inclusive' && itemVatRate > 0) {
                // VAT inclusive: extract VAT to get net amount
                rowVatAmount = lineTotal * (itemVatRate / (100 + itemVatRate));
                rowNetAmount = lineTotal - rowVatAmount;
            } else if (itemVatType === 'vat_exclusive' && itemVatRate > 0) {
                // VAT exclusive: line total includes VAT, extract net amount
                rowNetAmount = lineTotal / (1 + itemVatRate / 100);
                rowVatAmount = lineTotal - rowNetAmount;
            } else {
                // No VAT
                rowVatAmount = 0;
                rowNetAmount = lineTotal;
            }
            
            subtotal += rowNetAmount;
            totalVatFromItems += rowVatAmount;
        });

        const tax = parseFloat($('#tax_amount').val()) || 0;
        const discount = parseFloat($('#discount_amount').val()) || 0;
        
        const total = subtotal + tax + totalVatFromItems - discount;

        $('#subtotal').text(`${subtotal.toFixed(2)}`);
        $('#subtotal-input').val(subtotal.toFixed(2));
        $('#vat-amount').text(`${totalVatFromItems.toFixed(2)}`);
        $('#vat-amount-input').val(totalVatFromItems.toFixed(2));
        $('#vat-rate-display').text('Mixed');
        $('#total-amount').text(`${total.toFixed(2)}`);
        $('#total-amount-input').val(total.toFixed(2));
    }

    function resetItemModal() {
        $('#modal_item_id').val('').trigger('change');
        $('#modal_quantity').val(1);
        $('#modal_unit_price').val(0);
        $('#modal_description').val('');
        $('#modal_item_vat_type').val('no_vat');
        $('#modal_item_vat_rate').val(18);
        $('#modal_item_total_preview').text('0.00');
    }



    function calculateModalItemTotal() {
        const quantity = parseFloat($('#modal_quantity').val()) || 0;
        const unitPrice = parseFloat($('#modal_unit_price').val()) || 0;
        const vatType = $('#modal_item_vat_type').val();
        const vatRate = parseFloat($('#modal_item_vat_rate').val()) || 0;
        
        const baseAmount = quantity * unitPrice;
        let lineTotal = baseAmount;
        
        if (vatType === 'vat_inclusive' && vatRate > 0) {
            // VAT is included, no additional calculation needed for display
        } else if (vatType === 'vat_exclusive' && vatRate > 0) {
            lineTotal += lineTotal * (vatRate / 100);
        }
        
        $('#modal_item_total_preview').text(lineTotal.toFixed(2));
    }

    function formatItemOption(item) {
        if (!item.id) {
            return item.text;
        }
        
        const $item = $(item.element);
        const stock = $item.data('stock');
        const code = $item.data('code');
        const price = $item.data('price');
        
        return $(`
            <div>
                <strong>${item.text.split(' (')[0]}</strong>
                <br>
                <small class="text-muted">Code: ${code} | Price: ${price} | Stock: ${stock}</small>
            </div>
        `);
    }

    function formatItemSelection(item) {
        return item.text;
    }

    function displayValidationErrors(errors) {
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').empty();

        // Display new errors
        $.each(errors, function(field, messages) {
            const input = $(`[name="${field}"]`);
            input.addClass('is-invalid');
            input.siblings('.invalid-feedback').text(messages[0]);
        });
    }

    // Modal: warn on typing quantity beyond available stock
    $('#modal_quantity').on('input', function() {
        const entered = parseFloat($(this).val()) || 0;
        const available = parseFloat($('#modal_item_id option:selected').data('stock')) || 0;
        if (available > 0 && entered > available) {
            Swal.fire({
                icon: 'warning',
                title: 'Quantity exceeds available stock',
                html: `Available: <b>${available}</b>. You typed: <b>${entered}</b>.<br>Do you want to continue?`,
                showCancelButton: true,
                confirmButtonText: 'Continue',
                cancelButtonText: 'Adjust',
            }).then((result) => {
                if (!result.isConfirmed) {
                    $(this).val(available > 0 ? available : 0).trigger('input');
                }
            });
        }
    });

    // Confirm when entered quantity exceeds available stock (on type)
    $(document).on('input', '.item-quantity', function() {
        const $input = $(this);
        const entered = parseFloat($input.val()) || 0;
        const available = parseFloat($input.data('available-stock')) || 0;
        if (available > 0 && entered > available) {
            Swal.fire({
                icon: 'warning',
                title: 'Quantity exceeds available stock',
                html: `Available: <b>${available}</b>. You entered: <b>${entered}</b>.<br>Do you want to continue?`,
                showCancelButton: true,
                confirmButtonText: 'Continue',
                cancelButtonText: 'Adjust',
            }).then((result) => {
                if (!result.isConfirmed) {
                    $input.val(available > 0 ? available : 0).trigger('input');
                }
            });
        }
    });

    if (typeof copyFromInvoice !== 'undefined' && copyFromInvoice && copyFromInvoice.items && copyFromInvoice.items.length) {
        setTimeout(function() { populateFormFromCopyInvoice(copyFromInvoice); }, 100);
    }
});
</script>
@endpush
