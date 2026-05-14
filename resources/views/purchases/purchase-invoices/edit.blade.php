@extends('layouts.main')

@section('title', 'Edit Purchase Invoice')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchase Management', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Purchase Invoices', 'url' => route('purchases.purchase-invoices.index'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">EDIT PURCHASE INVOICE</h6>
        <hr />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-receipt me-2"></i>Edit Purchase Invoice</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('purchases.purchase-invoices.update', $invoice->encoded_id) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Supplier <span class="text-danger">*</span></label>
                            <select name="supplier_id" class="form-select select2-single" required>
                                <option value="">Select supplier</option>
                                @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" {{ $invoice->supplier_id == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Invoice # <span class="text-danger">*</span></label>
                            <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number', $invoice->invoice_number) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                            <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', optional($invoice->invoice_date)->toDateString()) }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-control" value="{{ old('due_date', optional($invoice->due_date)->toDateString()) }}">
                        </div>
                    </div>


                       <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="grn_id" class="form-label">GRN Selection</label>
                                <select class="form-select select2-single" id="grn_id" name="grn_id">
                                    <option value="">Select GRN (Optional)</option>
                                    {{-- GRN selection removed to avoid undefined $grns. Use prefill via ?grn_id=... --}}
                                </select>
                                <small class="text-muted">Only unconverted GRNs are shown</small>
                                <div id="order-loading" class="mt-2" style="display: none;">
                                    <small class="text-info"><i class="bx bx-loader bx-spin me-1"></i>Loading GRN details...</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            @php
                                $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', auth()->user()->company->functional_currency ?? 'TZS');
                            @endphp
                            <div class="mb-3">
                                <label for="currency" class="form-label">Currency</label>
                                <select class="form-select select2-single" id="currency" name="currency">
                                    @if(isset($currencies) && $currencies->isNotEmpty())
                                        @foreach($currencies as $currency)
                                            <option value="{{ $currency->currency_code }}" 
                                                    {{ old('currency', $invoice->currency ?? $functionalCurrency) == $currency->currency_code ? 'selected' : '' }}>
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
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="exchange_rate" class="form-label">Exchange Rate</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="exchange_rate" name="exchange_rate" 
                                           value="{{ old('exchange_rate', number_format($invoice->exchange_rate ?? 1, 6, '.', '')) }}" step="0.000001" min="0.000001" placeholder="1.000000">
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
                                            <th width="20%">Item</th>
                                            <th width="10%">Quantity</th>
                                            <th width="12%">Unit Price</th>
                                            <th width="8%">VAT</th>
                                            <th width="10%">Expiry Date</th>
                                            <th width="10%">Batch Number</th>
                                            <th width="12%">Total</th>
                                            <th width="8%">Action</th>
                                            <th width="5%"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="items-tbody">
                                        @foreach($invoice->items as $item)
                                        <tr data-row-id="{{ $loop->iteration }}">
                                            <td>
                                                <div class="fw-bold">
                                                    <span class="badge bg-success me-1">Inventory</span>
                                                    {{ optional($item->inventoryItem)->name ?? $item->description ?? 'N/A' }}
                                                </div>
                                                <small class="text-muted">{{ optional($item->inventoryItem)->code ?? 'N/A' }}</small>
                                                <input type="hidden" name="items[{{ $loop->iteration }}][inventory_item_id]" value="{{ $item->inventory_item_id }}">
                                                <input type="hidden" name="items[{{ $loop->iteration }}][vat_type]" value="{{ $item->vat_type }}">
                                                <input type="hidden" name="items[{{ $loop->iteration }}][vat_rate]" value="{{ $item->vat_rate }}">
                                                <input type="hidden" name="items[{{ $loop->iteration }}][vat_amount]" value="{{ $item->vat_amount }}">
                                                <input type="hidden" name="items[{{ $loop->iteration }}][line_total]" value="{{ $item->line_total }}">
                                                <input type="hidden" name="items[{{ $loop->iteration }}][notes]" value="{{ $item->notes ?? '' }}">
                                                <input type="hidden" name="items[{{ $loop->iteration }}][expiry_date]" value="{{ $item->expiry_date ? $item->expiry_date->toDateString() : '' }}">
                                                <input type="hidden" name="items[{{ $loop->iteration }}][batch_number]" value="{{ $item->batch_number ?? '' }}">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control item-quantity" 
                                                       name="items[{{ $loop->iteration }}][quantity]" value="{{ $item->quantity }}" 
                                                       step="0.01" min="0.01" data-row="{{ $loop->iteration }}">
                                            </td>
                                            <td>
                                                @php
                                                    // Get original price: if invoice was in foreign currency, reverse-convert to get TZS price
                                                    $originalPrice = $item->unit_cost;
                                                    if ($invoice->currency && $invoice->currency != $functionalCurrency && $invoice->exchange_rate && $invoice->exchange_rate != 1) {
                                                        $originalPrice = $item->unit_cost * $invoice->exchange_rate;
                                                    } else {
                                                        // Try to get from item's base price
                                                        $originalPrice = optional($item->inventoryItem)->cost_price ?? $item->unit_cost;
                                                    }
                                                @endphp
                                                <input type="number" class="form-control item-price" 
                                                       name="items[{{ $loop->iteration }}][unit_cost]" value="{{ $item->unit_cost }}" 
                                                       step="0.01" min="0" data-row="{{ $loop->iteration }}"
                                                       data-original-price="{{ $originalPrice }}"
                                                       data-original-currency="{{ $functionalCurrency }}">
                                            </td>
                                            <td>
                                                <small class="text-muted vat-display">
                                                    @if($item->vat_type === 'no_vat')
                                                        No VAT
                                                    @else
                                                        {{ ucfirst($item->vat_type) }} ({{ $item->vat_rate }}%) - {{ number_format($item->vat_amount, 2) }}
                                                    @endif
                                                </small>
                                            </td>
                                            <td>
                                                @if($item->expiry_date)
                                                    <span class="badge bg-info">{{ $item->expiry_date->format('d M Y') }}</span>
                                                    @if($item->expiry_date < now())
                                                        <span class="badge bg-danger ms-1">Expired</span>
                                                    @elseif($item->expiry_date < now()->addDays(30))
                                                        <span class="badge bg-warning ms-1">Expiring Soon</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($item->batch_number)
                                                    <span class="badge bg-secondary">{{ $item->batch_number }}</span>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="item-total">{{ number_format($item->line_total, 2) }}</span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-outline-danger btn-sm remove-item">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="6" class="text-end"><strong>Subtotal (Without VAT):</strong></td>
                                            <td><strong id="subtotal">{{ number_format($invoice->subtotal, 2) }}</strong></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="subtotal" id="subtotal-input" value="{{ number_format($invoice->subtotal, 2) }}">
                                        <tr id="vat-row" style="display: none;">
                                            <td colspan="6" class="text-end"><strong>VAT Amount:</strong></td>
                                            <td><strong id="vat-amount">{{ number_format($invoice->vat_amount, 2) }}</strong></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="vat_amount" id="vat-amount-input" value="{{ number_format($invoice->vat_amount, 2) }}">
                                        <input type="hidden" name="withholding_tax_amount" id="withholding-tax-amount-input" value="{{ number_format($invoice->withholding_tax_amount, 2) }}">
                                        <tr>
                                            <td colspan="6" class="text-end"><strong>Discount:</strong></td>
                                            <td>
                                                <input type="number" class="form-control" id="discount_amount" name="discount_amount" 
                                                       value="{{ old('discount_amount', $invoice->discount_amount) }}" step="0.01" min="0" placeholder="0.00">
                                            </td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr class="table-info">
                                            <td colspan="6" class="text-end"><strong>Total Amount:</strong></td>
                                            <td><strong id="total-amount">{{ number_format($invoice->total_amount, 2) }}</strong></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="total_amount" id="total-amount-input" value="{{ number_format($invoice->total_amount, 2) }}">
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Notes, Terms, Attachment -->
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="4" 
                                          placeholder="Additional notes for this invoice...">{{ old('notes', $invoice->notes) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="terms_conditions" class="form-label">Terms & Conditions</label>
                                <textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="4" 
                                          placeholder="Terms and conditions...">{{ old('terms_conditions', $invoice->terms_conditions) }}</textarea>
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
                                @if(!empty($invoice->attachment))
                                    <div class="mt-2">
                                        <a href="{{ asset('storage/' . $invoice->attachment) }}" target="_blank">
                                            <i class="bx bx-link-external me-1"></i>View current attachment
                                        </a>
                                    </div>
                                @endif
                                <small class="text-muted">Upload a new file to replace the existing attachment (PDF or image, max 5MB).</small>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('purchases.purchase-invoices.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <!-- Normal submit button (shown when items <= 30) -->
                        <button type="submit" class="btn btn-primary" id="submit-btn" style="display: none;">
                            <i class="bx bx-check me-1"></i>Update Purchase Invoice
                        </button>
                        <!-- CSV export/import buttons (shown when items > 30) -->
                        <button type="button" class="btn btn-success" id="export-csv-btn" style="display: none;">
                            <i class="bx bx-download me-1"></i>Export CSV
                        </button>
                        <a href="#" class="btn btn-primary" id="import-csv-btn" style="display: none;">
                            <i class="bx bx-upload me-1"></i>Import CSV
                        </a>
                    </div>
                </form>
                            </div>
                            </div>
                            </div>
                            </div>
                                        @error('payment_terms') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
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
                <!-- Inventory Item Selection -->
                <div id="inventory-item-section" class="mb-3">
                    <label for="modal_item_id" class="form-label">Select Inventory Item</label>
                    <select class="form-select select2-modal" id="modal_item_id">
                        <option value="">Choose an item...</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" 
                                    data-name="{{ $item->name }}"
                                    data-code="{{ $item->code }}"
                                    data-price="{{ $item->resolved_cost_price ?? $item->cost_price }}"
                                    data-unit="{{ $item->unit_of_measure }}"
                                    data-vat-rate="{{ get_default_vat_rate() }}"
                                    data-vat-type="{{ get_default_vat_type() }}"
                                    data-track-expiry="{{ $item->track_expiry ? 'true' : 'false' }}">
                                {{ $item->name }} ({{ $item->code }}) - Cost: {{ number_format($item->cost_price, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="modal_quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="modal_quantity" value="1" step="0.01" min="0.01">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="modal_unit_price" class="form-label">Unit Cost</label>
                            <input type="number" class="form-control" id="modal_unit_price" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="modal_purchase_total_cost" class="form-label">Purchase Total Cost</label>
                            <input type="number" class="form-control" id="modal_purchase_total_cost" step="0.01" min="0" placeholder="Optional">
                            <small class="text-muted">If > 0, calculates unit cost</small>
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
                
                <!-- Expiry Date Fields (shown only for items that track expiry) -->
                <div id="expiry-fields" class="row" style="display: none;">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_expiry_date" class="form-label">Expiry Date</label>
                            <input type="date" class="form-control" id="modal_expiry_date">
                            <small class="text-muted">Required for items that track expiry</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_batch_number" class="form-label">Batch Number</label>
                            <input type="text" class="form-control" id="modal_batch_number" placeholder="Optional batch number">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="modal_notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="modal_notes" rows="2" placeholder="Optional notes for this item..."></textarea>
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
$(function(){
    if (window.jQuery) {
        $('#invoice-form').off('submit');
    }
});
$(document).ready(function() {
    // Initialize Select2
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    $('.select2-modal').select2({
        theme: 'bootstrap-5',
        width: '100%',
        dropdownParent: $('#itemModal')
    });

    // Add item button click
    $('#add-item').click(function() {
        resetModalForm();
        $('#itemModal').modal('show');
    });
    
    // Ensure modal is reset when shown (in case it was opened with asset type previously)
    $('#itemModal').on('show.bs.modal', function() {
        resetModalForm();
    });


    // Item selection in modal
    $('#modal_item_id').change(function() {
        const selectedOption = $(this).find('option:selected');
        if (selectedOption.val()) {
            const basePrice = parseFloat(selectedOption.data('price')) || 0;
            const invoiceCurrency = getCurrentInvoiceCurrency();
            const exchangeRate = getCurrentExchangeRate();
            
            // Convert price if currency is different from functional currency
            const convertedPrice = convertItemPrice(basePrice, invoiceCurrency, exchangeRate);
            
            // Store original price for reference
            $('#modal_unit_price').data('original-price', basePrice);
            $('#modal_unit_price').data('original-currency', functionalCurrency);
            
            // Set converted price
            $('#modal_unit_price').val(convertedPrice.toFixed(2));
            
            // Show price info if converted
            if (invoiceCurrency !== functionalCurrency && exchangeRate !== 1) {
                const basePriceFixed = (isNaN(basePrice) ? 0 : basePrice).toFixed(2);
                $('#modal_unit_price').attr('title', 'Converted from ' + basePriceFixed + ' ' + functionalCurrency + ' at rate ' + exchangeRate);
            } else {
                $('#modal_unit_price').removeAttr('title');
            }
            
            $('#modal_vat_rate').val(selectedOption.data('vat-rate'));
            $('#modal_vat_type').val(selectedOption.data('vat-type'));
            
            // Show/hide expiry fields based on item's track_expiry setting
            const trackExpiry = selectedOption.data('track-expiry');
            if (trackExpiry) {
                $('#expiry-fields').show();
                $('#modal_expiry_date').prop('required', true);
            } else {
                $('#expiry-fields').hide();
                $('#modal_expiry_date').prop('required', false);
                $('#modal_expiry_date').val('');
                $('#modal_batch_number').val('');
            }
            
            // For purchase invoices, no stock validation needed
            // Purchases add stock, they don't consume it
            
            calculateModalLineTotal();
        }
    });

    // Note: Quantity change handler is now handled above to support purchase total cost calculation
    // Calculate modal line total on input change (excluding quantity which is handled separately)
    $('#modal_unit_price, #modal_vat_rate, #modal_vat_type').on('input change', function() {
        // Only recalculate if purchase total cost is NOT set (to avoid conflicts)
        const purchaseTotalCost = parseFloat($('#modal_purchase_total_cost').val()) || 0;
        if (purchaseTotalCost <= 0) {
        calculateModalLineTotal();
        }
        
        // Store original price if manually edited (for price conversion)
        if ($(this).attr('id') === 'modal_unit_price') {
            const selectedOption = $('#modal_item_id').find('option:selected');
            if (selectedOption.val() && !$('#modal_unit_price').data('original-price')) {
                const basePrice = parseFloat(selectedOption.data('price')) || 0;
                if (basePrice > 0) {
                    $('#modal_unit_price').data('original-price', basePrice);
                }
            }
        }
    });

    // Handle discount type change
    // Removed discount type change handler

    // Add item button in modal
    $('#add-item-btn').click(function() {
        addItemToTable();
    });

    // Remove item
    $(document).on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        calculateTotals();
    });

    // Recalculate on input change
    $(document).on('input', '.item-quantity, .item-price, #discount_amount', function() {
        const row = $(this).data('row');
        if (row) {
            updateRowTotal(row);
        }
        calculateTotals();
    });

    // Handle payment terms change
    $('#payment_terms').change(function() {
        const terms = $(this).val();
        let days = 30;
        
        switch(terms) {
            case 'immediate':
                days = 0;
                break;
            case 'net_15':
                days = 15;
                break;
            case 'net_30':
                days = 30;
                break;
            case 'net_45':
                days = 45;
                break;
            case 'net_60':
                days = 60;
                break;
            case 'custom':
                // Keep current value for custom
                return;
        }
        
        $('#payment_days').val(days);
        updateDueDate();
    });

    // Update due date when invoice date or payment days change
    $('#invoice_date, #payment_days').change(function() {
        updateDueDate();
    });


    // Form submission
    // Removed custom AJAX submit to allow normal form submit
    // $('#invoice-form').submit(function(e) {
    //         e.preventDefault();
        
    //     if ($('#items-tbody tr').length === 0) {
    //         Swal.fire('Error', 'Please add at least one item to the invoice', 'error');
    //         return;
    //     }

    //     const formData = new FormData(this);
    //     const submitBtn = $('#submit-btn');
        
    //     // Debug: Log form data
    //     console.log('Form data being sent:');
    //     console.log('Form action:', this.action);
    //     console.log('Form method:', this.method);
    //     console.log('Items count:', $('#items-tbody tr').length);
        
    //     for (let [key, value] of formData.entries()) {
    //         console.log(key + ': ' + value);
    //     }
        
    //     submitBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i>Creating...');

    //     $.ajax({
    //         url: '{{ route("purchases.purchase-invoices.store") }}',
    //         type: 'POST',
    //         data: formData,
    //         processData: false,
    //         contentType: false,
    //         success: function(response) {
    //             console.log('Success response:', response);
    //             if (response.success) {
    //                 Swal.fire({
    //                     title: 'Success!',
    //                     text: response.message,
    //                     icon: 'success',
    //                     confirmButtonText: 'OK'
    //                 }).then(() => {
    //                     if (response.redirect_url) {
    //                         window.location.href = response.redirect_url;
    //                     } else {
    //                         window.location.href = '{{ route("purchases.purchase-invoices.index") }}';
    //                     }
    //                 });
    //             } else {
    //                 Swal.fire('Error', response.message, 'error');
    //             }
    //         },
    //         error: function(xhr) {
    //             console.log('Error response:', xhr);
    //             console.log('Status:', xhr.status);
    //             console.log('Response:', xhr.responseText);
                
    //             if (xhr.status === 422) {
    //                 const errors = xhr.responseJSON?.errors;
    //                 console.log('Validation errors:', errors);
    //                 displayValidationErrors(errors);
    //                 Swal.fire('Validation Error', 'Please check the form for errors', 'error');
    //             } else if (xhr.status === 500) {
    //                 console.error('Server error:', xhr.responseText);
    //                 Swal.fire('Server Error', 'An internal server error occurred. Please try again later.', 'error');
    //             } else {
    //                 console.error('Unexpected error:', xhr.status, xhr.responseText);
    //                 Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
    //             }
    //         },
    //         complete: function() {
    //             submitBtn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Create Invoice');
    //         }
    //     });
    // });

function updateDueDate() {
        const invoiceDate = $('#invoice_date').val();
        const paymentDays = parseInt($('#payment_days').val()) || 0;
    
    if (invoiceDate) {
            const dueDate = new Date(invoiceDate);
        dueDate.setDate(dueDate.getDate() + paymentDays);
        $('#due_date').val(dueDate.toISOString().split('T')[0]);
    }
}

    function resetModalForm() {
        $('#inventory-item-section').show();
        $('#expiry-fields').hide();
        $('#modal_item_id').val('').trigger('change');
        $('#modal_quantity').val(1);
        $('#modal_unit_price').val('');
        $('#modal_purchase_total_cost').val('');
        $('#modal_vat_rate').val('{{ get_default_vat_rate() }}');
        $('#modal_vat_type').val('{{ get_default_vat_type() }}');
        $('#modal_notes').val('');
        $('#modal_expiry_date').val('');
        $('#modal_batch_number').val('');
        $('#modal-capitalize-threshold-info').hide();
        $('#modal-capitalize-details').hide();
        $('#modal-line-total').text('0.00');
        $('#modal_item_type').trigger('change');
    }

    function calculateModalLineTotal() {
        const quantity = parseFloat($('#modal_quantity').val()) || 0;
        const unitPrice = parseFloat($('#modal_unit_price').val()) || 0;
        const vatRate = parseFloat($('#modal_vat_rate').val()) || 0;
        const vatType = $('#modal_vat_type').val();

        let subtotal = quantity * unitPrice;
        let vatAmount = 0;
        let lineTotal = 0;

        if (vatType === 'no_vat') {
            vatAmount = 0;
            lineTotal = subtotal;
        } else if (vatType === 'exclusive') {
            vatAmount = subtotal * (vatRate / 100);
            lineTotal = subtotal + vatAmount;
        } else {
            // VAT inclusive
            vatAmount = subtotal * (vatRate / (100 + vatRate));
            lineTotal = subtotal;
        }
    
        // Update modal display
        $('#modal-line-total').text(lineTotal.toFixed(2));
    }
    
    // Function to calculate unit cost from purchase total cost
    function calculateUnitCostFromPurchaseTotal() {
        const purchaseTotalCost = parseFloat($('#modal_purchase_total_cost').val()) || 0;
        const quantity = parseFloat($('#modal_quantity').val()) || 0;
        
        if (purchaseTotalCost > 0 && quantity > 0) {
            // Calculate unit cost from purchase total cost
            const calculatedUnitCost = purchaseTotalCost / quantity;
            $('#modal_unit_price').val(calculatedUnitCost.toFixed(2));
            // Recalculate line total with new unit cost
            calculateModalLineTotal();
            return true;
        }
        return false;
    }
    
    // Handle purchase total cost change - calculate unit cost (with debounce)
    let purchaseTotalCostTimeout;
    $(document).on('input', '#modal_purchase_total_cost', function() {
        // Clear previous timeout
        clearTimeout(purchaseTotalCostTimeout);
        
        // Wait for user to finish typing (500ms delay)
        purchaseTotalCostTimeout = setTimeout(function() {
            calculateUnitCostFromPurchaseTotal();
        }, 500);
    });
    
    // Also calculate on blur (when user leaves the field)
    $(document).on('blur', '#modal_purchase_total_cost', function() {
        clearTimeout(purchaseTotalCostTimeout);
        calculateUnitCostFromPurchaseTotal();
    });
    
    // Recalculate when quantity changes if purchase total cost is set
    $(document).on('input change', '#modal_quantity', function() {
        const purchaseTotalCost = parseFloat($('#modal_purchase_total_cost').val()) || 0;
        if (purchaseTotalCost > 0) {
            // If purchase total cost is set, recalculate unit cost
            calculateUnitCostFromPurchaseTotal();
        } else {
            // Otherwise just recalculate line total normally
            calculateModalLineTotal();
        }
    });

function addItemToTable() {
        const itemType = 'inventory';
        const itemId = $('#modal_item_id').val();

        let itemName = $('#modal_item_id option:selected').text();
        if (!itemId || !itemName) {
            Swal.fire('Error', 'Please select an inventory item', 'error');
            return;
        }
        
        const quantity = parseFloat($('#modal_quantity').val()) || 0;
        let unitPrice = parseFloat($('#modal_unit_price').val()) || 0;
        const purchaseTotalCost = parseFloat($('#modal_purchase_total_cost').val()) || 0;
        const vatRate = parseFloat($('#modal_vat_rate').val()) || 0;
        const vatType = $('#modal_vat_type').val();
        const notes = $('#modal_notes').val() || '';
        const expiryDate = itemType === 'inventory' ? $('#modal_expiry_date').val() : null;
        const batchNumber = itemType === 'inventory' ? $('#modal_batch_number').val() : null;
        
        // If purchase total cost > 0, calculate unit cost from it
        if (purchaseTotalCost > 0 && quantity > 0) {
            unitPrice = purchaseTotalCost / quantity;
        }
        
        // Get original price from modal or item data
        let originalPrice = parseFloat($('#modal_unit_price').data('original-price')) || 0;
        if (!originalPrice) {
            const selectedOption = $('#modal_item_id').find('option:selected');
            originalPrice = parseFloat(selectedOption.data('price')) || unitPrice;
        }
        if (!originalPrice || isNaN(originalPrice)) {
            originalPrice = unitPrice;
        }

        if (quantity <= 0 || unitPrice <= 0 || isNaN(quantity) || isNaN(unitPrice)) {
            Swal.fire('Error', 'Please fill in quantity and unit price correctly', 'error');
            return;
        }

        // For purchase invoices, no stock validation needed
        // Purchases add stock, they don't consume it
    
        let subtotal = quantity * unitPrice;
        let vatAmount = 0;
        let lineTotal = 0;

        if (vatType === 'no_vat') {
            vatAmount = 0;
            lineTotal = subtotal;
        } else if (vatType === 'exclusive') {
            vatAmount = subtotal * (vatRate / 100);
            lineTotal = subtotal + vatAmount;
        } else {
            // VAT inclusive
            vatAmount = subtotal * (vatRate / (100 + vatRate));
            lineTotal = subtotal;
        }
    
        // Ensure all values are numbers before using toFixed
        const unitPriceFixed = (isNaN(unitPrice) ? 0 : unitPrice).toFixed(2);
        const originalPriceFixed = (isNaN(originalPrice) ? 0 : originalPrice).toFixed(2);
        const vatAmountFixed = (isNaN(vatAmount) ? 0 : vatAmount).toFixed(2);
        const lineTotalFixed = (isNaN(lineTotal) ? 0 : lineTotal).toFixed(2);
        const vatDisplay = vatType === 'no_vat' ? 'No VAT' : (vatRate + '%');
        const row = `
            <tr data-row-id="${itemCounter}" ${itemId ? `data-item-id="${itemId}"` : ''}>
                <td>
                    <input type="hidden" name="items[${itemCounter}][inventory_item_id]" value="${itemId || ''}">
                    <input type="hidden" name="items[${itemCounter}][item_name]" value="${(itemName || '').replace(/"/g, '&quot;')}">
                    <input type="hidden" name="items[${itemCounter}][description]" value="${(notes || '').replace(/"/g, '&quot;')}">
                    <input type="hidden" name="items[${itemCounter}][vat_type]" value="${vatType}">
                    <input type="hidden" name="items[${itemCounter}][vat_rate]" value="${vatRate}">
                    <input type="hidden" name="items[${itemCounter}][discount_type]" value="percentage">
                    <input type="hidden" name="items[${itemCounter}][discount_rate]" value="0">
                    <input type="hidden" name="items[${itemCounter}][notes]" value="${(notes || '').replace(/"/g, '&quot;')}">
                    <input type="hidden" name="items[${itemCounter}][expiry_date]" value="${expiryDate || ''}">
                    <input type="hidden" name="items[${itemCounter}][batch_number]" value="${batchNumber || ''}">
                    <input type="hidden" name="items[${itemCounter}][line_total]" value="${lineTotal}">
                    <input type="hidden" name="items[${itemCounter}][vat_amount]" value="${vatAmount}">
                    <input type="hidden" name="items[${itemCounter}][purchase_total_cost]" value="${purchaseTotalCost > 0 ? purchaseTotalCost.toFixed(2) : lineTotal.toFixed(2)}">
                    <div class="fw-bold">
                        <span class="badge bg-success me-1">Inventory</span>
                        ${itemName}
                    </div>
                    <small class="text-muted">${notes || ''}</small>
                </td>
                <td>
                    <input type="number" class="form-control item-quantity" 
                           name="items[${itemCounter}][quantity]" value="${quantity}" 
                           step="0.01" min="0.01" data-row="${itemCounter}">
                </td>
                <td>
                    <input type="number" class="form-control item-price" 
                           name="items[${itemCounter}][unit_cost]" value="${unitPriceFixed}" 
                           step="0.01" min="0" data-row="${itemCounter}"
                           data-original-price="${originalPriceFixed}"
                           data-original-currency="${functionalCurrency}"
                           ${getCurrentInvoiceCurrency() !== functionalCurrency ? 'title="Converted from ' + originalPriceFixed + ' ' + functionalCurrency + '"' : ''}>
                </td>
                <td>
                    <small class="text-muted">${vatDisplay}</small>
                </td>
                <td>
                    ${expiryDate ? '<span class="badge bg-info">' + new Date(expiryDate).toLocaleDateString('en-GB') + '</span>' : '<span class="text-muted">N/A</span>'}
                </td>
                <td>
                    ${batchNumber ? '<span class="badge bg-secondary">' + batchNumber + '</span>' : '<span class="text-muted">N/A</span>'}
                </td>
                <td>
                    <span class="item-total">${lineTotalFixed}</span>
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
        itemCounter++;
        calculateTotals();
    }

    function updateRowTotal(row) {
        const quantity = parseFloat($(`input[name="items[${row}][quantity]"]`).val()) || 0;
        const unitPrice = parseFloat($(`input[name="items[${row}][unit_cost]"]`).val()) || 0;
        const itemVatType = $(`input[name="items[${row}][vat_type]"]`).val();
        const itemVatRate = parseFloat($(`input[name="items[${row}][vat_rate]"]`).val()) || 0;

        let subtotal = quantity * unitPrice;
        let vatAmount = 0;
        let lineTotal = 0;

        if (itemVatType === 'no_vat') {
            vatAmount = 0;
            lineTotal = subtotal;
        } else if (itemVatType === 'exclusive') {
            vatAmount = subtotal * (itemVatRate / 100);
            lineTotal = subtotal + vatAmount;
        } else {
            vatAmount = subtotal * (itemVatRate / (100 + itemVatRate));
            lineTotal = subtotal;
        }

        const lineTotalFixed = (isNaN(lineTotal) ? 0 : lineTotal).toFixed(2);
        $(`.item-total`).eq(row).text(lineTotalFixed);
    }

function calculateTotals() {
        let subtotal = 0;
        let vatAmount = 0;
        let lineTotalSum = 0;

        $('#items-tbody tr').each(function() {
            const quantity = parseFloat($(this).find('.item-quantity').val()) || 0;
            const unitPrice = parseFloat($(this).find('.item-price').val()) || 0;
            const itemVatType = $(this).find('input[name*="[vat_type]"]').val();
            const itemVatRate = parseFloat($(this).find('input[name*="[vat_rate]"]').val()) || 0;

            const rowSubtotal = quantity * unitPrice;
            let rowVatAmount = 0;
            let rowLineTotal = 0;
            let rowNetAmount = 0; // Net amount without VAT

            if (itemVatType === 'no_vat') {
                rowVatAmount = 0;
                rowLineTotal = rowSubtotal;
                rowNetAmount = rowSubtotal;
            } else if (itemVatType === 'exclusive') {
                rowVatAmount = rowSubtotal * (itemVatRate / 100);
                rowLineTotal = rowSubtotal + rowVatAmount;
                rowNetAmount = rowSubtotal; // For exclusive, unit price is already net
            } else {
                // For inclusive VAT, extract VAT to get net amount
                rowVatAmount = rowSubtotal * (itemVatRate / (100 + itemVatRate));
                rowLineTotal = rowSubtotal; // For inclusive, line total is the subtotal (VAT already included)
                rowNetAmount = rowSubtotal - rowVatAmount; // Net amount = gross - VAT
            }

            subtotal += rowNetAmount; // Add net amount to subtotal
            vatAmount += rowVatAmount;
            lineTotalSum += rowLineTotal;
        });

        // Withholding tax calculation removed - no UI elements exist for this
        let withholdingTaxAmount = 0;

        // Calculate total discount (from invoice-level discount)
        const totalDiscount = parseFloat($('#discount_amount').val()) || 0;
        
        // Calculate final total using sum of line totals (handles inclusive/exclusive correctly)
        const totalAmount = lineTotalSum - totalDiscount;

        // Update displays
        // Ensure all values are numbers before using toFixed
        const subtotalFixed = (isNaN(subtotal) ? 0 : subtotal).toFixed(2);
        const vatAmountFixed = (isNaN(vatAmount) ? 0 : vatAmount).toFixed(2);
        const totalAmountFixed = (isNaN(totalAmount) ? 0 : totalAmount).toFixed(2);
        
        $('#subtotal').text(subtotalFixed);
        $('#subtotal-input').val(subtotalFixed);
        
        if (vatAmount > 0) {
            $('#vat-row').show();
            $('#vat-amount').text(vatAmountFixed);
            $('#vat-amount-input').val(vatAmountFixed);
        } else {
            $('#vat-row').hide();
        }
        
        $('#total-amount').text(totalAmountFixed);
        $('#total-amount-input').val(totalAmountFixed);
    }

    function displayValidationErrors(errors) {
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        // Check if errors object exists and has properties
        if (!errors || typeof errors !== 'object') {
            console.log('No validation errors to display or invalid errors object:', errors);
            return;
        }

        // Display new errors
        Object.keys(errors).forEach(field => {
            const input = $(`[name="${field}"]`);
            if (input.length) {
                input.addClass('is-invalid');
                input.siblings('.invalid-feedback').text(errors[field][0]);
            }
        });
    }

    // Get functional currency for exchange rate calculations
    const functionalCurrency = '{{ \App\Models\SystemSetting::getValue("functional_currency", auth()->user()->company->functional_currency ?? "TZS") }}';
    
    // Function to convert item price from functional currency to invoice currency
    function convertItemPrice(basePrice, invoiceCurrency, exchangeRate) {
        if (!basePrice || !invoiceCurrency || !exchangeRate) {
            return basePrice;
        }
        
        // If invoice currency is functional currency, no conversion needed
        if (invoiceCurrency === functionalCurrency) {
            return parseFloat(basePrice);
        }
        
        // Convert: Price in FCY = Price in TZS / Exchange Rate
        // Example: 10,000 TZS / 2,500 = 4 USD
        const convertedPrice = parseFloat(basePrice) / parseFloat(exchangeRate);
        return parseFloat(convertedPrice.toFixed(2));
    }
    
    // Function to get current exchange rate
    function getCurrentExchangeRate() {
        const rate = parseFloat($('#exchange_rate').val()) || 1.000000;
        return rate;
    }
    
    // Function to get current invoice currency
    function getCurrentInvoiceCurrency() {
        return $('#currency').val() || functionalCurrency;
    }
    
    // Handle currency change - Use Select2 event for proper handling
    $('#currency').on('select2:select', function(e) {
        const selectedCurrency = $(this).val();
        handleCurrencyChange(selectedCurrency);
    }).on('change', function() {
        const selectedCurrency = $(this).val();
        handleCurrencyChange(selectedCurrency);
    });
    
    function handleCurrencyChange(selectedCurrency) {
        if (selectedCurrency && selectedCurrency !== functionalCurrency) {
            $('#exchange_rate').prop('required', true);
            // Auto-fetch exchange rate when currency changes (use invoice date if available)
            const invoiceDate = $('#invoice_date').val();
            fetchExchangeRate(selectedCurrency, invoiceDate);
        } else {
            $('#exchange_rate').prop('required', false);
            $('#exchange_rate').val('1.000000');
            $('#rate-info').hide();
        }
        
        // Convert all existing item prices when currency changes
        convertAllItemPrices();
    }
    
    // Function to convert all item prices in the table when currency/exchange rate changes
    function convertAllItemPrices() {
        const invoiceCurrency = getCurrentInvoiceCurrency();
        const exchangeRate = getCurrentExchangeRate();
        
        // Convert prices in existing rows
        $('input.item-price').each(function() {
            const $priceInput = $(this);
            const originalPrice = $priceInput.data('original-price');
            
            // If original price is stored, use it; otherwise use current value as base
            const basePrice = originalPrice || parseFloat($priceInput.val()) || 0;
            
            if (basePrice > 0) {
                const convertedPrice = convertItemPrice(basePrice, invoiceCurrency, exchangeRate);
                $priceInput.val(convertedPrice.toFixed(2));
                
                // Store original price if not already stored
                if (!originalPrice) {
                    $priceInput.data('original-price', basePrice);
                    $priceInput.data('original-currency', functionalCurrency);
                }
                
                // Update tooltip
                if (invoiceCurrency !== functionalCurrency && exchangeRate !== 1) {
                    const basePriceFixed = (isNaN(basePrice) ? 0 : basePrice).toFixed(2);
                    $priceInput.attr('title', 'Converted from ' + basePriceFixed + ' ' + functionalCurrency + ' at rate ' + exchangeRate);
                } else {
                    $priceInput.removeAttr('title');
                }
                
                // Recalculate row total
                const row = $priceInput.data('row');
                if (row !== undefined) {
                    updateRowTotal(row);
                }
            }
        });
        
        // Convert price in modal if item is selected
        if ($('#modal_item_id').val()) {
            const selectedOption = $('#modal_item_id').find('option:selected');
            if (selectedOption.val()) {
                const basePrice = parseFloat(selectedOption.data('price')) || 0;
                if (basePrice > 0) {
                    const convertedPrice = convertItemPrice(basePrice, invoiceCurrency, exchangeRate);
                    $('#modal_unit_price').val(convertedPrice.toFixed(2));
                    $('#modal_unit_price').data('original-price', basePrice);
                    
                    if (invoiceCurrency !== functionalCurrency && exchangeRate !== 1) {
                        const basePriceFixed = (isNaN(basePrice) ? 0 : basePrice).toFixed(2);
                        $('#modal_unit_price').attr('title', 'Converted from ' + basePriceFixed + ' ' + functionalCurrency + ' at rate ' + exchangeRate);
                    } else {
                        $('#modal_unit_price').removeAttr('title');
                    }
                }
            }
        }
        
        // Recalculate totals
        calculateTotals();
    }
    
    // Convert prices when exchange rate changes
    $('#exchange_rate').on('input change', function() {
        // Only convert if currency is not functional currency
        const invoiceCurrency = getCurrentInvoiceCurrency();
        if (invoiceCurrency !== functionalCurrency) {
            convertAllItemPrices();
        }
    });
    
    // Convert existing items on page load if currency has changed
    setTimeout(function() {
        const currentCurrency = getCurrentInvoiceCurrency();
        const invoiceCurrency = '{{ $invoice->currency ?? "TZS" }}';
        if (currentCurrency !== invoiceCurrency) {
            convertAllItemPrices();
        }
    }, 100);

    // Fetch exchange rate button
    $('#fetch-rate-btn').on('click', function() {
        const currency = $('#currency').val();
        fetchExchangeRate(currency);
    });

    // Function to fetch exchange rate from FX RATES MANAGEMENT
    function fetchExchangeRate(currency = null, invoiceDate = null) {
        currency = currency || $('#currency').val();
        invoiceDate = invoiceDate || $('#invoice_date').val() || new Date().toISOString().split('T')[0];
        
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
        
        // Use the FX rates API endpoint
        $.ajax({
            url: '{{ route("accounting.fx-rates.get-rate") }}',
            method: 'GET',
            data: {
                from_currency: currency,
                to_currency: functionalCurrency,
                date: invoiceDate, // Use invoice date instead of today
                rate_type: 'spot'
            },
            success: function(response) {
                if (response.success && response.rate) {
                    const rate = parseFloat(response.rate) || 0;
                    const rateFixed = (isNaN(rate) ? 0 : rate).toFixed(6);
                    rateInput.val(rateFixed);
                    const source = response.source || 'FX RATES MANAGEMENT';
                    $('#rate-source').text('Rate from ' + source + ' for ' + invoiceDate + ': 1 ' + currency + ' = ' + rateFixed + ' ' + functionalCurrency);
                    $('#rate-info').show();
                    
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                    Toast.fire({
                        icon: 'success',
                        title: 'Rate updated: 1 ' + currency + ' = ' + rateFixed + ' ' + functionalCurrency
                    });
                } else {
                    console.warn('Exchange rate API returned unexpected format:', response);
                    // Try fallback
                    fetchExchangeRateFallback(currency, invoiceDate);
                }
            },
            error: function(xhr) {
                console.error('Failed to fetch exchange rate:', xhr);
                fetchExchangeRateFallback(currency, invoiceDate);
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
                rateInput.prop('disabled', false);
            }
        });
    }
    
    // Fallback function to fetch rate from API if FX RATES MANAGEMENT doesn't have it
    function fetchExchangeRateFallback(currency, invoiceDate) {
        const rateInput = $('#exchange_rate');
        $.get('{{ route("api.exchange-rates.rate") }}', {
            from: currency,
            to: functionalCurrency
        })
        .done(function(response) {
            if (response.success && response.data && response.data.rate) {
                const rate = parseFloat(response.data.rate) || 0;
                const rateFixed = (isNaN(rate) ? 0 : rate).toFixed(6);
                rateInput.val(rateFixed);
                $('#rate-source').text('Rate fetched (fallback API) for ' + invoiceDate + ': 1 ' + currency + ' = ' + rateFixed + ' ' + functionalCurrency);
                $('#rate-info').show();
            }
        })
        .fail(function() {
            Swal.fire({
                icon: 'warning',
                title: 'Rate Fetch Failed',
                text: 'Please manually enter the exchange rate or add it to FX RATES MANAGEMENT.',
                timer: 3000,
                showConfirmButton: false
            });
        });
    }
    
    // Auto-fetch exchange rate when invoice date changes
    $('#invoice_date').on('change', function() {
        const currency = $('#currency').val();
        const invoiceDate = $(this).val();
        if (currency && currency !== functionalCurrency && invoiceDate) {
            fetchExchangeRate(currency, invoiceDate);
        }
    });

    // Handle sales order selection
    $('#grn_id').on('change', function() {
        const orderId = $(this).val();
        
        if (!orderId) {
            // Clear form if no order selected
            clearForm();
            return;
        }

        // Show loading state
        $(this).prop('disabled', true);
        $('#order-loading').show();
        
        // Fetch sales order details
        $.ajax({
            url: `{{ route('sales.invoices.sales-order-details', ':orderId') }}`.replace(':orderId', orderId),
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    populateFormFromOrder(response.order);
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function(xhr) {
                Swal.fire('Error', 'Failed to fetch sales order details', 'error');
            },
            complete: function() {
                $('#grn_id').prop('disabled', false);
                $('#order-loading').hide();
            }
        });
    });

    function populateFormFromOrder(order) {
        // Populate supplier
        $('#supplier_id').val(grn.supplier.id).trigger('change');
        
        // Populate payment terms
        $('#payment_terms').val(order.payment_terms);
        $('#payment_days').val(order.payment_days);
        
        // Populate notes and terms
        $('#notes').val(order.notes);
        $('#terms_conditions').val(order.terms_conditions);
        
        // Clear existing items
        $('#items-tbody').empty();
        itemCounter = 0;
        
        // Add items from sales order
        order.items.forEach(function(item) {
            addItemFromOrder(item);
        });
        
        // Update totals
        calculateTotals();
        
        // Show success message
        Swal.fire({
            title: 'Success!',
            text: `Sales order "${order.order_number}" details loaded successfully`,
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    }

    function addItemFromOrder(item) {
        itemCounter++;
        
        const row = `
            <tr data-row-id="${itemCounter}">
                <td>
                    <div class="fw-bold">
                        <span class="badge bg-success me-1">Inventory</span>
                        ${item.item_name}
                    </div>
                    <small class="text-muted">${item.item_code || ''}</small>
                    <input type="hidden" name="items[${itemCounter}][inventory_item_id]" value="${item.inventory_item_id}">
                    <input type="hidden" name="items[${itemCounter}][vat_type]" value="${item.vat_type}">
                    <input type="hidden" name="items[${itemCounter}][vat_rate]" value="${item.vat_rate}">
                    <input type="hidden" name="items[${itemCounter}][vat_amount]" value="${item.vat_amount}">
                    <input type="hidden" name="items[${itemCounter}][discount_type]" value="${item.discount_type}">
                    <input type="hidden" name="items[${itemCounter}][discount_rate]" value="${item.discount_rate}">
                    <input type="hidden" name="items[${itemCounter}][discount_amount]" value="${item.discount_amount}">
                    <input type="hidden" name="items[${itemCounter}][line_total]" value="${item.line_total}">
                    <input type="hidden" name="items[${itemCounter}][notes]" value="${item.notes || ''}">
                </td>
                <td>
                    <input type="number" class="form-control item-quantity" 
                           name="items[${itemCounter}][quantity]" value="${item.quantity}" 
                           step="0.01" min="0.01" data-row="${itemCounter}">
                </td>
                <td>
                    <input type="number" class="form-control item-price" 
                           name="items[${itemCounter}][unit_cost]" value="${item.unit_price}" 
                           step="0.01" min="0" data-row="${itemCounter}">
                </td>
                <td>
                    <span class="form-control-plaintext">${item.vat_type === 'no_vat' ? 'No VAT' : item.vat_rate + '% (' + item.vat_type.replace('_', ' ') + ')'}</span>
                </td>
                <td>
                    <span class="item-total">${(parseFloat(item.line_total) || 0).toFixed(2)}</span>
                </td>
                <td>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-item">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $('#items-tbody').append(row);
    }

    function clearForm() {
        // Clear customer
        $('#customer_id').val('').trigger('change');
        
        // Reset payment terms to defaults
        $('#payment_terms').val('net_30');
        $('#payment_days').val('30');
        
        // Clear notes and terms
        $('#notes').val('');
        $('#terms_conditions').val('');
        
        // Clear items
        $('#items-tbody').empty();
        itemCounter = 0;
        
        // Reset totals
        calculateTotals();
    }

    let itemCounter = 0;

    // Function to update buttons based on item count (same as create form)
    function updateButtonsBasedOnItemCount() {
        const itemCount = $('#items-tbody tr').length;
        if (itemCount > 30) {
            $('#submit-btn').hide();
            $('#export-csv-btn').show();
            $('#import-csv-btn').show();
        } else {
            $('#submit-btn').show();
            $('#export-csv-btn').hide();
            $('#import-csv-btn').hide();
        }
    }

    // Check item count on page load
    updateButtonsBasedOnItemCount();

    // Update buttons when items are added/removed
    $(document).on('DOMNodeInserted DOMNodeRemoved', '#items-tbody', function() {
        updateButtonsBasedOnItemCount();
    });

    // Also update when items are removed via remove button
    $(document).on('click', '.remove-item', function() {
        setTimeout(function() {
            updateButtonsBasedOnItemCount();
        }, 100);
    });

    // Export CSV button handler
    $('#export-csv-btn').click(function() {
        exportItemsToCsv();
    });

    // Import CSV button - opens import page in new tab with current form data
    $('#import-csv-btn').on('click', function(e) {
        e.preventDefault();

        // Collect current form values from the edit page
        const supplierId = $('select[name="supplier_id"]').val();
        const invoiceNumber = $('input[name="invoice_number"]').val();
        const invoiceDate = $('input[name="invoice_date"]').val();
        const dueDate = $('input[name="due_date"]').val();
        const currency = $('#currency').val();
        const exchangeRate = $('#exchange_rate').val();
        const discountAmount = $('#discount_amount').val() || '0';
        const notes = $('#notes').val();
        const termsConditions = $('#terms_conditions').val();
        const invoiceId = '{{ $invoice->encoded_id }}';

        // Build URL with query parameters
        const params = new URLSearchParams();
        params.append('invoice_id', invoiceId); // Add invoice ID for edit mode
        if (supplierId) params.append('supplier_id', supplierId);
        if (invoiceNumber) params.append('invoice_number', invoiceNumber);
        if (invoiceDate) params.append('invoice_date', invoiceDate);
        if (dueDate) params.append('due_date', dueDate);
        if (currency) params.append('currency', currency);
        if (exchangeRate) params.append('exchange_rate', exchangeRate);
        if (discountAmount) params.append('discount_amount', discountAmount);
        if (notes) params.append('notes', encodeURIComponent(notes));
        if (termsConditions) params.append('terms_conditions', encodeURIComponent(termsConditions));

        const importUrl = '{{ route("purchases.purchase-invoices.import") }}' + (params.toString() ? '?' + params.toString() : '');

        // Open in new tab
        window.open(importUrl, '_blank');
    });

    // Export items to CSV function (same as create form)
    function exportItemsToCsv() {
        const items = [];
        $('#items-tbody tr').each(function() {
            const $row = $(this);
            const itemType = $row.find('input[name*="[item_type]"]').val() || 'inventory';
            const itemId = $row.find('input[name*="[inventory_item_id]"]').val();
            // Extract item name - try hidden input first, then strong/fw-bold element
            let itemName = $row.find('input[name*="[item_name]"]').val();
            if (!itemName) {
                const $nameEl = $row.find('td:first strong, td:first .fw-bold');
                if ($nameEl.length) {
                    itemName = $nameEl.first().text().trim();
                    // Remove badge text if present
                    itemName = itemName.replace(/^Inventory\s*/, '').trim();
                }
            }
            itemName = itemName || '';
            const quantity = $row.find('.item-quantity').val();
            const unitCost = $row.find('.item-price').val();
            const vatType = $row.find('input[name*="[vat_type]"]').val();
            const vatRate = $row.find('input[name*="[vat_rate]"]').val();
            const notes = $row.find('input[name*="[notes]"]').val() || '';
            const expiryDate = $row.find('input[name*="[expiry_date]"]').val() || '';
            const batchNumber = $row.find('input[name*="[batch_number]"]').val() || '';
            const description = $row.find('input[name*="[description]"]').val() || '';

            items.push({
                item_type: itemType,
                item_id: itemId,
                item_name: itemName,
                quantity: quantity,
                unit_cost: unitCost,
                vat_type: vatType,
                vat_rate: vatRate,
                notes: notes,
                expiry_date: expiryDate,
                batch_number: batchNumber,
                description: description
            });
        });

        // Create CSV content
        const headers = ['Item Type', 'Item ID', 'Item Name', 'Quantity', 'Unit Cost', 'VAT Type', 'VAT Rate', 'Notes', 'Expiry Date', 'Batch Number', 'Description'];
        let csvContent = headers.join(',') + '\n';

        items.forEach(function(item) {
            const row = [
                item.item_type || '',
                item.item_id || '',
                '"' + (item.item_name || '').replace(/"/g, '""') + '"',
                item.quantity || '0',
                item.unit_cost || '0',
                item.vat_type || 'no_vat',
                item.vat_rate || '0',
                '"' + (item.notes || '').replace(/"/g, '""') + '"',
                item.expiry_date || '',
                '"' + (item.batch_number || '').replace(/"/g, '""') + '"',
                '"' + (item.description || '').replace(/"/g, '""') + '"'
            ];
            csvContent += row.join(',') + '\n';
        });

        // Create download link
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', 'purchase_invoice_items_' + new Date().getTime() + '.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
});
</script>
@endpush
@endsection 