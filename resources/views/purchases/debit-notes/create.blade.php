@extends('layouts.main')

@section('title', 'Create Debit Note')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchases', 'url' => route('purchases.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Debit Notes', 'url' => route('purchases.debit-notes.index'), 'icon' => 'bx bx-minus-circle'],
            ['label' => 'Create Debit Note', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">CREATE DEBIT NOTE</h6>
        <hr />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-minus-circle me-2"></i>New Debit Note</h5>
            </div>
            <div class="card-body">
                <form id="debit-note-form" method="POST" action="{{ route('purchases.debit-notes.store') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="purchase_invoice_id" name="purchase_invoice_id" value="">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="supplier_id" class="form-label">Supplier <span class="text-danger">*</span></label>
                                <select class="form-select select2-single" id="supplier_id" name="supplier_id" required>
                                    <option value="">Select Supplier</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                                <small class="text-muted">Select the supplier this debit note relates to.</small>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="debit_note_date" class="form-label">Debit Note Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="debit_note_date" name="debit_note_date" value="{{ date('Y-m-d') }}" required>
                                <div class="invalid-feedback"></div>
                                <small class="text-muted">The effective date of the debit note.</small>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="currency" class="form-label">Currency</label>
                                @php
                                    $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', auth()->user()->company->functional_currency ?? 'TZS');
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
                                                {{ old('currency', $functionalCurrency) == $currency->currency_code ? 'selected' : '' }}>
                                            {{ $currency->currency_name ?? $currency->currency_code }} ({{ $currency->currency_code }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Set a currency if different from TZS; provides exchange rate field.</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="reference_invoice_id" class="form-label">Reference Purchase Invoice</label>
                                <select class="form-select select2-single" id="reference_invoice_id" name="reference_invoice_id">
                                    <option value="">Select Invoice (Optional)</option>
                                    @foreach($invoices as $invoice)
                                        <option value="{{ $invoice->id }}">{{ $invoice->invoice_number ?? ('PINV-' . $invoice->id) }} - {{ $invoice->supplier->name ?? '' }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Optional: link to an existing purchase invoice to prefill items.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="type" class="form-label">Debit Note Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="type" name="type" required>
                                    @foreach($debitNoteTypes as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Choose "Return" to send goods back to the supplier; other types adjust values only.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="reason_code" class="form-label">Reason Code</label>
                                <select class="form-select" id="reason_code" name="reason_code">
                                    <option value="">Select Reason (Optional)</option>
                                    @foreach($reasonCodes as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Optional reason category to classify this debit note.</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason Details <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="Describe the reason for this debit note" required></textarea>
                                <small class="text-muted">Describe the discrepancy or reason for the debit note.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="warehouse_id" class="form-label">Warehouse</label>
                                <select class="form-select select2-single" id="warehouse_id" name="warehouse_id">
                                    <option value="">Select Warehouse</option>
                                    @if(isset($warehouses) && count($warehouses))
                                        @foreach($warehouses as $wh)
                                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                        @endforeach
                                    @else
                                        <option value="" disabled>No warehouses found</option>
                                    @endif
                                </select>
                                <small class="text-muted" id="warehouse_help">Required when returning items to stock. If left empty, your current location will be used.</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-check mb-3">
                                <input type="hidden" name="refund_now" value="0">
                                <input class="form-check-input" type="checkbox" value="1" id="refund_now" name="refund_now">
                                <label class="form-check-label" for="refund_now">Refund now</label>
                                <small class="text-muted d-block">Create an immediate refund entry to the supplier.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mb-3">
                                <input type="hidden" name="return_to_stock" value="0">
                                <input class="form-check-input" type="checkbox" value="1" id="return_to_stock" name="return_to_stock">
                                <label class="form-check-label" for="return_to_stock">Return items to stock (stock IN)</label>
                                <small class="text-muted d-block">Checked: goods go into your warehouse. Unchecked: goods go back to supplier (stock OUT).</small>
                            </div>
                        </div>
                        <div class="col-md-4" id="exchange_rate_group" style="display:none;">
                            <label for="exchange_rate" class="form-label">Exchange Rate</label>
                            <input type="number" step="0.0001" min="0" class="form-control" id="exchange_rate" name="exchange_rate" placeholder="Enter exchange rate">
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Items</h6>
                            <button type="button" class="btn btn-primary btn-sm" id="add-item">
                                <i class="bx bx-plus me-1"></i>Add Item
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table" id="items-table">
                                    <thead>
                                        <tr>
                                            <th width="30%">Item</th>
                                            <th width="15%">Quantity</th>
                                            <th width="15%">Unit Cost</th>
                                            <th width="15%">VAT</th>
                                            <th width="15%">Line Total</th>
                                            <th width="10%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="items-tbody"></tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="5" class="text-end"><strong>Subtotal:</strong></td>
                                            <td><strong id="subtotal">0.00</strong></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="subtotal" id="subtotal-input" value="0">
                                        <tr id="vat-row" style="display: none;">
                                            <td colspan="5" class="text-end"><strong>VAT Amount:</strong></td>
                                            <td><strong id="vat-amount">0.00</strong></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="vat_amount" id="vat-amount-input" value="0">
                                        <tr>
                                            <td colspan="5" class="text-end"><strong>Discount:</strong></td>
                                            <td>
                                                <input type="number" class="form-control" id="discount_amount" name="discount_amount" value="0" step="0.01" min="0" placeholder="0.00">
                                                <small class="text-muted">Optional invoice-level discount to subtract from the total.</small>
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr class="table-info">
                                            <td colspan="5" class="text-end"><strong>Total Amount:</strong></td>
                                            <td><strong id="total-amount">0.00</strong></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="total_amount" id="total-amount-input" value="0">
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="4" placeholder="Additional notes for this debit note..."></textarea>
                                <small class="text-muted">Internal notes for reference.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="terms_conditions" class="form-label">Terms & Conditions</label>
                                <textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="4" placeholder="Terms and conditions..."></textarea>
                                <small class="text-muted">Optional terms shown on the document.</small>
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

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('purchases.debit-notes.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="bx bx-check me-1"></i>Create Debit Note
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
                        @foreach($inventoryItems as $item)
                            <option value="{{ $item->id }}"
                                    data-name="{{ $item->name }}"
                                    data-code="{{ $item->code }}"
                                    data-price="{{ $item->resolved_cost_price ?? $item->cost_price }}"
                                    data-stock="{{ $item->current_stock }}"
                                    data-unit="{{ $item->unit_of_measure }}"
                                    data-vat-rate="18"
                                    data-vat-type="no_vat">
                                {{ $item->name }} ({{ $item->code }}) - Cost: {{ number_format($item->resolved_cost_price ?? $item->cost_price, 2) }} - Stock: {{ $item->current_stock }}
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

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    $('.select2-single').select2({ theme: 'bootstrap-5', width: '100%' });
    $('.select2-modal').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#itemModal') });

    $('#currency').on('change', function() {
        if ($(this).val() !== 'TZS') {
            $('#exchange_rate_group').show();
        } else {
            $('#exchange_rate_group').hide();
            $('#exchange_rate').val('');
        }
    });

    // Prefill from Copy To (source invoice)
    @if(isset($sourceInvoice) && $sourceInvoice)
        $('#supplier_id').val('{{ $sourceInvoice->supplier_id }}').trigger('change');
        setTimeout(function() {
            if ($('#reference_invoice_id option[value="{{ $sourceInvoice->id }}"]').length) {
                $('#reference_invoice_id').val('{{ $sourceInvoice->id }}').trigger('change');
            }
        }, 300);
    @endif

    // Sync selected reference invoice to hidden purchase_invoice_id
    $('#reference_invoice_id').on('change', function() {
        const invoiceId = $(this).val() || '';
        $('#purchase_invoice_id').val(invoiceId);
        if (invoiceId) {
            loadInvoiceItems(invoiceId);
        } else {
            // Clear items if no invoice selected
            $('#items-tbody').empty();
            itemCounter = 0;
            calculateTotals();
        }
    });

    function loadInvoiceItems(invoiceId) {
        const url = `{{ route('purchases.debit-notes.invoice-items', ':id') }}`.replace(':id', invoiceId);
        // Disable UI while loading
        $('#reference_invoice_id').prop('disabled', true);
        $('#add-item').prop('disabled', true);
        $('#items-tbody').html(`<tr><td colspan="6"><span class="text-info"><i class="bx bx-loader bx-spin me-1"></i>Loading invoice items...</span></td></tr>`);

        $.get(url)
            .done(function(payload) {
                // Prefill supplier
                if (payload.supplier_id) {
                    $('#supplier_id').val(payload.supplier_id).trigger('change');
                }

                const items = payload.items || [];
                $('#items-tbody').empty();
                itemCounter = 0;
                items.forEach(function(item) {
                    appendInvoiceItemRow(item);
                });
                calculateTotals();
            })
            .fail(function() {
                Swal.fire('Error', 'Failed to load invoice items', 'error');
                $('#items-tbody').empty();
                itemCounter = 0;
            })
            .always(function() {
                $('#reference_invoice_id').prop('disabled', false);
                $('#add-item').prop('disabled', false);
            });
    }

    function appendInvoiceItemRow(item) {
        // Compute line total according to current VAT settings
        const quantity = parseFloat(item.quantity) || 0;
        const unitCost = parseFloat(item.unit_cost) || 0;
        const vatType = item.vat_type || 'no_vat';
        const vatRate = parseFloat(item.vat_rate) || 0;

        let subtotal = quantity * unitCost;
        let vatAmount = 0;
        let lineTotal = 0;
        if (vatType === 'no_vat') { vatAmount = 0; lineTotal = subtotal; }
        else if (vatType === 'exclusive') { vatAmount = subtotal * (vatRate / 100); lineTotal = subtotal + vatAmount; }
        else { vatAmount = subtotal * (vatRate / (100 + vatRate)); lineTotal = subtotal; }

        const row = `
            <tr>
                <td>
                    <input type="hidden" name="items[${itemCounter}][linked_invoice_line_id]" value="${item.id}">
                    <input type="hidden" name="items[${itemCounter}][inventory_item_id]" value="${item.inventory_item_id || ''}">
                    <input type="hidden" name="items[${itemCounter}][item_name]" value="${item.item_name || ''}">
                    <input type="hidden" name="items[${itemCounter}][item_code]" value="${item.item_code || ''}">
                    <input type="hidden" name="items[${itemCounter}][unit_of_measure]" value="${item.unit_of_measure || ''}">
                    <input type="hidden" name="items[${itemCounter}][vat_type]" value="${vatType}">
                    <input type="hidden" name="items[${itemCounter}][vat_rate]" value="${vatRate}">
                    <div class="fw-bold">${item.item_name || ''}</div>
                    <small class="text-muted">${item.item_code || ''}</small>
                </td>
                <td>
                    <input type="number" class="form-control item-quantity" name="items[${itemCounter}][quantity]" value="${quantity}" step="0.01" min="0.01" data-row="${itemCounter}">
                </td>
                <td>
                    <input type="number" class="form-control item-price" name="items[${itemCounter}][unit_cost]" value="${unitCost}" step="0.01" min="0" data-row="${itemCounter}">
                </td>
                <td>
                    <small class="text-muted">${vatType === 'no_vat' ? 'No VAT' : (vatRate + '%')}</small>
                </td>
                <td>
                    <span class="item-total">${lineTotal.toFixed(2)}</span>
                </td>
                <td class="text-center">
                    <input type="hidden" name="items[${itemCounter}][return_to_stock]" value="${ $('#return_to_stock').is(':checked') ? 1 : 0 }">
                    <button type="button" class="btn btn-sm btn-danger remove-item" title="Remove line">
                        <i class="bx bx-trash me-1"></i><span class="d-none d-md-inline">Remove</span>
                    </button>
                </td>
            </tr>`;

        $('#items-tbody').append(row);
        itemCounter++;
    }

    $('#add-item').click(function() {
        $('#itemModal').modal('show');
        resetModalForm();
    });

    $('#modal_item_id').change(function() {
        const selected = $(this).find('option:selected');
        if (selected.val()) {
            $('#modal_unit_price').val(selected.data('price'));
            $('#modal_vat_rate').val(selected.data('vat-rate'));
            $('#modal_vat_type').val(selected.data('vat-type'));
            const stock = selected.data('stock');
            $('#modal_quantity').attr('max', stock);
            if (stock <= 0) {
                alert(`Warning: ${selected.data('name')} is out of stock!`);
            } else if (stock <= 10) {
                alert(`Warning: ${selected.data('name')} has low stock (${stock} available)`);
            }
            calculateModalLineTotal();
        }
    });

    $('#modal_quantity, #modal_unit_price, #modal_vat_rate, #modal_vat_type').on('input change', function() {
        calculateModalLineTotal();
    });

    $('#add-item-btn').click(function() { addItemToTable(); });

    $(document).on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        calculateTotals();
    });

    $(document).on('input', '.item-quantity, .item-price', function() {
        const row = $(this).data('row');
        if (row !== undefined) { updateRowTotal(row); }
        calculateTotals();
    });

    // Toggle Warehouse requirement and helper based on return_to_stock
    $('#return_to_stock').on('change', function(){
        const isReturnToStock = $(this).is(':checked');
        // Count valid warehouse options (those with a value)
        const hasWarehouses = $('#warehouse_id option').filter(function(){ return $(this).val(); }).length > 0;
        if (isReturnToStock && hasWarehouses) {
            $('#warehouse_id').prop('required', true);
            $('#warehouse_help').text('Warehouse is required when returning items to stock (stock IN)');
        } else if (isReturnToStock && !hasWarehouses) {
            // No warehouses available: allow submit and inform we will use current location
            $('#warehouse_id').prop('required', false);
            $('#warehouse_help').text('No warehouses found; your current location will be used for stock IN.');
        } else {
            $('#warehouse_id').prop('required', false);
            $('#warehouse_help').text('Select a warehouse (optional)');
        }
    }).trigger('change');

    $('#discount_amount').on('input', function() { calculateTotals(); });

    $('#debit-note-form').submit(function(e) {
        e.preventDefault();
        if ($('#items-tbody tr').length === 0) {
            Swal.fire('Error', 'Please add at least one item', 'error');
            return;
        }
        const formData = new FormData(this);
        const submitBtn = $('#submit-btn');
        submitBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i>Creating...');

        $.ajax({
            url: this.action,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({ title: 'Success', text: response.message || 'Debit note created successfully!', icon: 'success' }).then(() => {
                        if (response.redirect) { window.location.href = response.redirect; }
                        else { window.location.href = '{{ route('purchases.debit-notes.index') }}'; }
                    });
                } else {
                    Swal.fire('Error', response.message || 'Failed to create debit note', 'error');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors;
                    displayValidationErrors(errors);
                    Swal.fire('Validation Error', 'Please check the form for errors', 'error');
                } else {
                    Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Create Debit Note');
            }
        });
    });

    function resetModalForm() {
        $('#modal_item_id').val('').trigger('change');
        $('#modal_quantity').val(1);
        $('#modal_unit_price').val('');
        $('#modal_vat_rate').val(18);
        $('#modal_vat_type').val('no_vat');
        $('#modal_return_condition').val('');
        $('#modal_notes').val('');
        $('#modal-line-total').text('0.00');
    }

    function calculateModalLineTotal() {
        const quantity = parseFloat($('#modal_quantity').val()) || 0;
        const unitPrice = parseFloat($('#modal_unit_price').val()) || 0;
        const vatRate = parseFloat($('#modal_vat_rate').val()) || 0;
        const vatType = $('#modal_vat_type').val();

        let subtotal = quantity * unitPrice;

        let vatAmount = 0;
        let lineTotal = 0;
        if (vatType === 'no_vat') { vatAmount = 0; lineTotal = subtotal; }
        else if (vatType === 'exclusive') { vatAmount = subtotal * (vatRate / 100); lineTotal = subtotal + vatAmount; }
        else { vatAmount = subtotal * (vatRate / (100 + vatRate)); lineTotal = subtotal; }

        $('#modal-line-total').text(lineTotal.toFixed(2));
    }

    function addItemToTable() {
        const itemId = $('#modal_item_id').val();
        const selected = $('#modal_item_id option:selected');
        const itemName = selected.data('name');
        const itemCode = selected.data('code');
        const quantity = parseFloat($('#modal_quantity').val()) || 0;
        const unitCost = parseFloat($('#modal_unit_price').val()) || 0;
        const vatRate = parseFloat($('#modal_vat_rate').val()) || 0;
        const vatType = $('#modal_vat_type').val();
        const notes = $('#modal_notes').val();
        const returnCondition = $('#modal_return_condition').val();

        if (!itemId || quantity <= 0 || unitCost < 0) {
            Swal.fire('Error', 'Please fill in all required fields correctly', 'error');
            return;
        }

        const availableStock = selected.data('stock');
        // For supplier returns (stock OUT), ensure sufficient stock
        if (!$('#return_to_stock').is(':checked') && quantity > availableStock) {
            Swal.fire('Error', `Insufficient stock for supplier return of ${itemName}. Available: ${availableStock}, Requested: ${quantity}`, 'error');
            return;
        }

        // Compute total similar to modal
        let subtotal = quantity * unitCost;
        let vatAmount = 0; let lineTotal = 0;
        if (vatType === 'no_vat') { vatAmount = 0; lineTotal = subtotal; }
        else if (vatType === 'exclusive') { vatAmount = subtotal * (vatRate / 100); lineTotal = subtotal + vatAmount; }
        else { vatAmount = subtotal * (vatRate / (100 + vatRate)); lineTotal = subtotal; }

        const row = `
            <tr>
                <td>
                    <input type="hidden" name="items[${itemCounter}][inventory_item_id]" value="${itemId}">
                    <input type="hidden" name="items[${itemCounter}][item_name]" value="${itemName}">
                    <input type="hidden" name="items[${itemCounter}][item_code]" value="${itemCode}">
                    <input type="hidden" name="items[${itemCounter}][unit_of_measure]" value="${selected.data('unit') || ''}">
                    <input type="hidden" name="items[${itemCounter}][vat_type]" value="${vatType}">
                    <input type="hidden" name="items[${itemCounter}][vat_rate]" value="${vatRate}">
                    <input type="hidden" name="items[${itemCounter}][notes]" value="${notes}">
                    <input type="hidden" name="items[${itemCounter}][return_condition]" value="${returnCondition}">
                    <div class="fw-bold">${itemName}</div>
                    <small class="text-muted">${itemCode || ''}</small>
                </td>
                <td>
                    <input type="number" class="form-control item-quantity" name="items[${itemCounter}][quantity]" value="${quantity}" step="0.01" min="0.01" data-row="${itemCounter}">
                </td>
                <td>
                    <input type="number" class="form-control item-price" name="items[${itemCounter}][unit_cost]" value="${unitCost}" step="0.01" min="0" data-row="${itemCounter}">
                </td>
                <td>
                    <small class="text-muted">${vatType === 'no_vat' ? 'No VAT' : (vatRate + '%')}</small>
                </td>

                <td>
                    <span class="item-total">${lineTotal.toFixed(2)}</span>
                </td>
                <td>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-item"><i class="bx bx-trash"></i></button>
                </td>
            </tr>`;

        $('#items-tbody').append(row);
        $('#itemModal').modal('hide');
        itemCounter++;
        calculateTotals();
    }

    function updateRowTotal(row) {
        const quantity = parseFloat($(`input[name="items[${row}][quantity]"]`).val()) || 0;
        const unitPrice = parseFloat($(`input[name="items[${row}][unit_price]"]`).val()) || 0;
        const vatType = $(`input[name="items[${row}][vat_type]"]`).val();
        const vatRate = parseFloat($(this).find('input[name*="[vat_rate]"]').val()) || 0;

        let subtotal = quantity * unitPrice;

        let vatAmount = 0; let lineTotal = 0;
        if (vatType === 'no_vat') { vatAmount = 0; lineTotal = subtotal; }
        else if (vatType === 'exclusive') { vatAmount = subtotal * (vatRate / 100); lineTotal = subtotal + vatAmount; }
        else { vatAmount = subtotal * (vatRate / (100 + vatRate)); lineTotal = subtotal; }

        $(`.item-total`).eq(row).text(lineTotal.toFixed(2));
    }

    function calculateTotals() {
        let subtotal = 0;
        let vatAmount = 0;

        $('#items-tbody tr').each(function(index) {
            const quantity = parseFloat($(this).find('.item-quantity').val()) || 0;
            const unitPrice = parseFloat($(this).find('.item-price').val()) || 0;
            const vatType = $(this).find('input[name*="[vat_type]"]').val();
            const vatRate = parseFloat($(this).find('input[name*="[vat_rate]"]').val()) || 0;

            let rowSubtotal = quantity * unitPrice;

            let rowVatAmount = 0;
            if (vatType === 'exclusive') rowVatAmount = rowSubtotal * (vatRate / 100);
            if (vatType === 'inclusive') rowVatAmount = rowSubtotal * (vatRate / (100 + vatRate));

            subtotal += rowSubtotal;
            vatAmount += rowVatAmount;
        });

        const invoiceLevelDiscount = parseFloat($('#discount_amount').val()) || 0;
        const totalAmount = subtotal + vatAmount - invoiceLevelDiscount;

        $('#subtotal').text(subtotal.toFixed(2));
        $('#subtotal-input').val(subtotal.toFixed(2));

        if (vatAmount > 0) {
            $('#vat-row').show();
            $('#vat-amount').text(vatAmount.toFixed(2));
            $('#vat-amount-input').val(vatAmount.toFixed(2));
        } else {
            $('#vat-row').hide();
        }

        $('#total-amount').text(totalAmount.toFixed(2));
        $('#total-amount-input').val(totalAmount.toFixed(2));
    }

    function displayValidationErrors(errors) {
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        if (!errors || typeof errors !== 'object') return;
        Object.keys(errors).forEach(field => {
            const input = $(`[name="${field}"]`);
            if (input.length) {
                input.addClass('is-invalid');
                const message = errors[field][0];
                if (input.siblings('.invalid-feedback').length) {
                    input.siblings('.invalid-feedback').text(message);
                }
            }
        });
    }

    let itemCounter = 0;
});
</script>
@endpush
@endsection
