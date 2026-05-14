@extends('layouts.main')

@section('title', 'Create Sales Proforma')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Proformas', 'url' => route('sales.proformas.index'), 'icon' => 'bx bx-file-blank'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">CREATE SALES PROFORMA</h6>
        <hr />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-file-blank me-2"></i>New Sales Proforma</h5>
            </div>
            <div class="card-body">
                <form id="proforma-form" enctype="multipart/form-data">
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
                                <label for="proforma_date" class="form-label">Proforma Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="proforma_date" name="proforma_date" 
                                       value="{{ date('Y-m-d') }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="valid_until" class="form-label">Valid Until <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="valid_until" name="valid_until" 
                                       value="{{ date('Y-m-d', strtotime('+30 days')) }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    @php
                        $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', auth()->user()->company->functional_currency ?? 'TZS');
                    @endphp
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="currency" class="form-label">Currency</label>
                                <select class="form-select select2-single" id="currency" name="currency">
                                    @if(isset($currencies) && $currencies->isNotEmpty())
                                        @foreach($currencies as $currency)
                                            <option value="{{ $currency->currency_code }}"
                                                    {{ old('currency', $functionalCurrency) == $currency->currency_code ? 'selected' : '' }}>
                                                {{ $currency->currency_name ?? $currency->currency_code }} ({{ $currency->currency_code }})
                                            </option>
                                        @endforeach
                                    @else
                                        <option value="{{ $functionalCurrency }}" selected>{{ $functionalCurrency }}</option>
                                    @endif
                                </select>
                                <small class="text-muted">Currencies from FX RATES MANAGEMENT</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="exchange_rate" class="form-label">Exchange Rate</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="exchange_rate" name="exchange_rate"
                                           value="{{ old('exchange_rate', '1.000000') }}" step="0.000001" min="0.000001" placeholder="1.000000">
                                    <button type="button" class="btn btn-outline-secondary" id="fetch-rate-btn">
                                        <i class="bx bx-refresh"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Rate relative to functional currency</small>
                                <div id="rate-info" class="mt-1" style="display: none;">
                                    <small class="text-info">
                                        <i class="bx bx-info-circle"></i>
                                        <span id="rate-source">Rate fetched from API</span>
                                    </small>
                                </div>
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
                                            <th width="24%">Item</th>
                                            <th width="14%">Quantity</th>
                                            <th width="14%">Unit Price</th>
                                            <th width="12%">VAT</th>
                                            <th width="14%">Total</th>
                                            <th width="14%">Action</th>
                                            <th width="8%"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="items-tbody">
                                        <!-- Items will be added here dynamically -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Subtotal (Without VAT):</strong></td>
                                            <td><strong id="subtotal">0.00</strong></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="subtotal" id="subtotal-input" value="0">
                                        <tr id="vat-row" style="display: none;">
                                            <td colspan="4" class="text-end"><strong>VAT Amount:</strong></td>
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
                                          placeholder="Additional notes for this proforma..."></textarea>
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
                        <a href="{{ route('sales.proformas.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="bx bx-check me-1"></i>Create Proforma
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
                                {{ $item->name }} ({{ $item->code }}) - Price: {{ number_format($item->resolved_unit_price ?? $item->unit_price, 2) }} - Stock: {{ $item->current_stock }}
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
    const functionalCurrency = @json($functionalCurrency ?? 'TZS');
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
                    <input type="hidden" name="items[${itemCounter}][discount_type]" value="fixed">
                    <input type="hidden" name="items[${itemCounter}][discount_rate]" value="0">
                    <input type="hidden" name="items[${itemCounter}][discount_amount]" value="0">
                    <input type="hidden" name="items[${itemCounter}][line_total]" value="${lineTotal.toFixed(2)}">
                </td>
                <td>
                    <input type="number" class="form-control item-quantity" name="items[${itemCounter}][quantity]" value="${item.quantity}" step="0.01" min="0.01" data-row="${itemCounter}">
                </td>
                <td>
                    <input type="number" class="form-control item-price" name="items[${itemCounter}][unit_price]" value="${item.unit_price}" step="0.01" min="0" data-row="${itemCounter}">
                </td>
                <td class="text-muted">N/A</td>
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
        $('#proforma_date').val(data.proforma_date || '');
        $('#valid_until').val(data.valid_until || '');
        $('#currency').val(data.currency || functionalCurrency).trigger('change');
        $('#exchange_rate').val((parseFloat(data.exchange_rate) || 1).toFixed(6));
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

    function fetchExchangeRate(currency = null, proformaDate = null) {
        currency = currency || $('#currency').val();
        proformaDate = proformaDate || $('#proforma_date').val() || new Date().toISOString().split('T')[0];

        if (!currency || currency === functionalCurrency) {
            $('#exchange_rate').val('1.000000');
            $('#rate-info').hide();
            return;
        }

        const btn = $('#fetch-rate-btn');
        const originalHtml = btn.html();
        const rateInput = $('#exchange_rate');

        btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i>');
        rateInput.prop('disabled', true);

        $.ajax({
            url: '{{ route("accounting.fx-rates.get-rate") }}',
            method: 'GET',
            data: {
                from_currency: currency,
                to_currency: functionalCurrency,
                date: proformaDate,
                rate_type: 'spot'
            },
            success: function(response) {
                if (response.success && response.rate) {
                    const rate = parseFloat(response.rate);
                    rateInput.val(rate.toFixed(6));
                    const source = response.source || 'FX RATES MANAGEMENT';
                    $('#rate-source').text(`Rate from ${source} for ${proformaDate}: 1 ${currency} = ${rate.toFixed(6)} ${functionalCurrency}`);
                    $('#rate-info').show();
                } else {
                    $('#rate-info').hide();
                }
            },
            error: function() {
                $('#rate-info').hide();
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
                rateInput.prop('disabled', false);
            }
        });
    }

    function handleCurrencyChange(selectedCurrency) {
        if (selectedCurrency && selectedCurrency !== functionalCurrency) {
            $('#exchange_rate').prop('required', true);
            fetchExchangeRate(selectedCurrency, $('#proforma_date').val());
        } else {
            $('#exchange_rate').prop('required', false);
            $('#exchange_rate').val('1.000000');
            $('#rate-info').hide();
        }
    }

    $('#currency').on('select2:select change', function() {
        handleCurrencyChange($(this).val());
    });

    $('#fetch-rate-btn').on('click', function() {
        fetchExchangeRate($('#currency').val(), $('#proforma_date').val());
    });

    $('#proforma_date').on('change', function() {
        const selectedCurrency = $('#currency').val();
        if (selectedCurrency && selectedCurrency !== functionalCurrency) {
            fetchExchangeRate(selectedCurrency, $(this).val());
        }
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
        const discountType = 'fixed';
        const discountValue = 0;
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

        // Calculate discount amount
        const baseAmount = quantity * unitPrice;
        let discountAmount = 0;
        let discountPercentage = 0;
        
        if (discountType === 'percent') {
            discountPercentage = discountValue;
            discountAmount = baseAmount * (discountValue / 100);
        } else {
            discountAmount = discountValue;
            discountPercentage = baseAmount > 0 ? (discountValue / baseAmount) * 100 : 0;
        }

        // Calculate item total with VAT
        let lineTotal = baseAmount - discountAmount;
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
        const discountDisplay = '0.00';

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
                    <input type="hidden" name="items[${itemCounter}][discount_type]" value="fixed">
                    <input type="hidden" name="items[${itemCounter}][discount_rate]" value="0">
                    <input type="hidden" name="items[${itemCounter}][discount_amount]" value="0">
                    <input type="hidden" name="items[${itemCounter}][line_total]" value="${lineTotal}">
                </td>
                <td>
                    <input type="number" class="form-control item-quantity" 
                           name="items[${itemCounter}][quantity]" value="${quantity}" 
                           step="0.01" min="0.01" data-row="${itemCounter}">
                </td>
                <td>
                    <input type="number" class="form-control item-price" 
                           name="items[${itemCounter}][unit_price]" value="${unitPrice}" 
                           step="0.01" min="0" data-row="${itemCounter}">
                </td>
                <td class="text-muted">N/A</td>
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
    $(document).on('input', '.item-quantity, .item-price, .item-discount, #tax_amount, #discount_amount', function() {
        const row = $(this).data('row');
        if (row) {
            updateRowTotal(row);
        }
        calculateTotals();
    });

    // Form submission
    $('#proforma-form').submit(function(e) {
        e.preventDefault();
        
        if ($('#items-tbody tr').length === 0) {
            Swal.fire('Error', 'Please add at least one item to the proforma', 'error');
            return;
        }

        const formData = new FormData(this);
        const submitBtn = $('#submit-btn');
        
        submitBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i>Creating...');

        $.ajax({
            url: '{{ route("sales.proformas.store") }}',
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
                        window.location.href = '{{ route("sales.proformas.index") }}';
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
                submitBtn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Create Proforma');
            }
        });
    });

    function updateRowTotal(row) {
        const quantity = parseFloat($(`input[name="items[${row}][quantity]"]`).val()) || 0;
        const unitPrice = parseFloat($(`input[name="items[${row}][unit_price]"]`).val()) || 0;
        const discountAmount = parseFloat($(`input[name="items[${row}][discount_amount]"]`).val()) || 0;
        const itemVatType = $(`input[name="items[${row}][vat_type]"]`).val();
        const itemVatRate = parseFloat($(`input[name="items[${row}][vat_rate]"]`).val()) || 0;
        
        let lineTotal = (quantity * unitPrice) - discountAmount;
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

            // Toggle VAT row visibility similar to Sales Invoice create
            if (totalVatFromItems > 0.000001) {
                $('#vat-row').show();
            } else {
                $('#vat-row').hide();
            }
    }

    // Set default VAT values
    const defaultVatType = '{{ get_default_vat_type() == 'inclusive' ? 'vat_inclusive' : (get_default_vat_type() == 'exclusive' ? 'vat_exclusive' : 'no_vat') }}';
    const defaultVatRate = {{ get_default_vat_rate() }};

    function resetItemModal() {
        $('#modal_item_id').val('').trigger('change');
        $('#modal_quantity').val(1);
        $('#modal_unit_price').val(0);
        $('#modal_discount_type').val('fixed');
        $('#modal_discount_value').val(0);
        $('#modal_description').val('');
        $('#modal_item_vat_type').val(defaultVatType);
        $('#modal_item_vat_rate').val(defaultVatRate);
        $('#modal_item_total_preview').text('0.00');
        $('#modal_discount_preview').text('0.00');
        updateDiscountInputLabel();
    }

    function updateDiscountInputLabel() {
        const discountType = $('#modal_discount_type').val();
        const unit = discountType === 'percent' ? '%' : 'Amount';
        $('#discount-unit').text(unit);
        const maxAttr = discountType === 'percent' ? 100 : null;
        $('#modal_discount_value').attr('max', maxAttr);
    }

    function calculateModalItemTotal() {
        const quantity = parseFloat($('#modal_quantity').val()) || 0;
        const unitPrice = parseFloat($('#modal_unit_price').val()) || 0;
        const discountType = $('#modal_discount_type').val();
        const discountValue = parseFloat($('#modal_discount_value').val()) || 0;
        const vatType = $('#modal_item_vat_type').val();
        const vatRate = parseFloat($('#modal_item_vat_rate').val()) || 0;
        
        const baseAmount = quantity * unitPrice;
        let discountAmount = 0;
        
        if (discountType === 'percent') {
            discountAmount = baseAmount * (discountValue / 100);
        } else {
            discountAmount = discountValue;
        }
        
        let lineTotal = baseAmount - discountAmount;
        
        if (vatType === 'vat_inclusive' && vatRate > 0) {
            // VAT is included, no additional calculation needed for display
        } else if (vatType === 'vat_exclusive' && vatRate > 0) {
            lineTotal += lineTotal * (vatRate / 100);
        }
        
        $('#modal_item_total_preview').text(lineTotal.toFixed(2));
        $('#modal_discount_preview').text(discountAmount.toFixed(2));
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

    if (typeof copyFromInvoice !== 'undefined' && copyFromInvoice && copyFromInvoice.items && copyFromInvoice.items.length) {
        setTimeout(function() { populateFormFromCopyInvoice(copyFromInvoice); }, 100);
    } else {
        handleCurrencyChange($('#currency').val());
    }
});
</script>
@endpush
