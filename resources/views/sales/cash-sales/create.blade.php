@extends('layouts.main')

@section('title', 'Create Cash Sale')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-store'],
            ['label' => 'Cash Sales', 'url' => route('sales.cash-sales.index'), 'icon' => 'bx bx-dollar-circle'],
            ['label' => 'Create Cash Sale', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">CREATE CASH SALE</h6>
        <hr />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-dollar-circle me-2"></i>New Cash Sale</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('sales.cash-sales.store') }}" id="cash-sale-form" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                <select class="form-select select2-single" id="customer_id" name="customer_id" required>
                                    <option value="">Select Customer</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" data-encoded-id="{{ $customer->encoded_id }}">
                                            {{ $customer->name }} - {{ $customer->phone }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="sale_date" class="form-label">Sale Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="sale_date" name="sale_date" 
                                       value="{{ date('Y-m-d') }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="">Select Payment Method</option>
                                    <option value="bank">Bank</option>
                                    <option value="cash_deposit">Cash Deposit</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
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
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="exchange_rate" class="form-label">Exchange Rate</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="exchange_rate" name="exchange_rate" 
                                           value="1.000000" step="0.000001" min="0.000001" placeholder="1.000000">
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
                        <div class="col-md-4">
                            <div class="mb-3" id="bank_account_section" style="display: none;">
                                <label for="bank_account_id" class="form-label">Bank Account</label>
                                <select class="form-select select2-single" id="bank_account_id" name="bank_account_id">
                                    <option value="">Select Bank Account</option>
                                    @foreach($bankAccounts as $bankAccount)
                                        <option value="{{ $bankAccount->id }}">
                                            {{ $bankAccount->name }} ({{ $bankAccount->account_number }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3" id="cash_deposit_section" style="display: none;">
                                <label for="cash_deposit_id" class="form-label">Customer Account</label>
                                <select class="form-select select2-single" id="cash_deposit_id" name="cash_deposit_id">
                                    <option value="">Select Customer Account</option>
                                </select>
                                <small class="text-muted">Select a customer first to see their account and balance</small>
                                <div id="no_account_message" class="text-danger mt-2" style="display: none;">
                                    <i class="bx bx-info-circle"></i> No account available for this customer
                                </div>
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
                                            <td colspan="4" class="text-end"><strong>VAT Amount:</strong></td>
                                            <td><strong id="vat-amount">0.00</strong></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="vat_amount" id="vat-amount-input" value="0">
                                        <tr id="withholding-tax-row" style="display: none;">
                                            <td colspan="4" class="text-end"><strong>Withholding Tax (<span id="withholding-tax-rate-display">0</span>%):</strong></td>
                                            <td><strong id="withholding-tax-amount">0.00</strong></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="withholding_tax_amount" id="withholding-tax-amount-input" value="0">
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Discount:</strong></td>
                                            <td>
                                                <input type="number" class="form-control" id="discount_amount" name="discount_amount" 
                                                       value="0" step="0.01" min="0" placeholder="0.00">
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

                    <!-- Withholding Tax Settings -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Withholding Tax Settings</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="hidden" name="withholding_tax_enabled" value="0">
                                            <input class="form-check-input" type="checkbox" id="withholding_tax_enabled" name="withholding_tax_enabled" value="1">
                                            <label class="form-check-label" for="withholding_tax_enabled">
                                                Apply Withholding Tax
                                            </label>
                                        </div>
                                    </div>
                                    <div id="withholding_tax_fields" style="display: none;">
                                        <div class="mb-3">
                                            <label for="withholding_tax_rate" class="form-label">Withholding Tax Rate (%)</label>
                                            <input type="number" class="form-control" id="withholding_tax_rate" name="withholding_tax_rate" 
                                                   value="5" min="0" max="100" step="0.01">
                                        </div>
                                        <div class="mb-3">
                                            <label for="withholding_tax_type" class="form-label">Withholding Tax Type</label>
                                            <select class="form-select" id="withholding_tax_type" name="withholding_tax_type">
                                                <option value="percentage">Percentage of Subtotal</option>
                                                <option value="fixed">Fixed Amount</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes, Terms and Attachment -->
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="4" 
                                          placeholder="Additional notes for this cash sale..."></textarea>
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
                        <a href="{{ route('sales.cash-sales.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="bx bx-check me-1"></i>Create Cash Sale
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
                                @php
                                    $stockService = new \App\Services\InventoryStockService();
                                    $currentStock = 0;
                                    if ($item->item_type !== 'service' && $item->track_stock) {
                                        $currentStock = $stockService->getItemStockAtLocation($item->id, session('location_id'));
                                    }
                                @endphp
                        <option value="{{ $item->id }}" 
                                data-name="{{ $item->name }}"
                                data-code="{{ $item->code }}"
                                data-price="{{ $item->resolved_unit_price ?? $item->unit_price }}"
                                data-wholesale-price="{{ $item->has_wholesale ? ($item->resolved_wholesale_unit_price ?? $item->wholesale_unit_price ?? 0) : '' }}"
                                data-has-wholesale="{{ $item->has_wholesale ? '1' : '0' }}"
                                data-stock="{{ $currentStock }}"
                                data-item-type="{{ $item->item_type ?? 'product' }}"
                                data-track-stock="{{ $item->track_stock ? 'true' : 'false' }}"
                                data-minimum-stock="{{ $item->minimum_stock ?? 0 }}"
                                data-unit="{{ $item->unit_of_measure }}"
                                data-vat-rate="{{ $item->vat_rate ?? get_default_vat_rate() }}"
                                data-vat-type="{{ $item->vat_type ?? get_default_vat_type() }}">
                            {{ $item->name }} ({{ $item->code }}) - Price: {{ number_format($item->resolved_unit_price ?? $item->unit_price, 2) }}
                            @if($item->item_type !== 'service' && $item->track_stock)
                                - Stock: {{ $currentStock }}
                            @else
                                - Service Item
                            @endif
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
                <div class="row" id="modal_price_tier_row" style="display: none;">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="modal_price_tier" class="form-label">Price type</label>
                            <select class="form-select" id="modal_price_tier">
                                <option value="retail">Retail</option>
                                <option value="wholesale">Wholesale</option>
                            </select>
                            <small class="text-muted">Default is retail when both are available.</small>
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
        $('#itemModal').modal('show');
        resetModalForm();
    });

    function cashModalItemHasWholesale(opt) {
        return opt.attr('data-has-wholesale') === '1';
    }

    function toggleCashModalPriceTierRow() {
        const opt = $('#modal_item_id option:selected');
        if (!opt.val()) {
            $('#modal_price_tier_row').hide();
            return;
        }
        if (cashModalItemHasWholesale(opt)) {
            $('#modal_price_tier_row').show();
            $('#modal_price_tier').val('retail');
        } else {
            $('#modal_price_tier_row').hide();
            $('#modal_price_tier').val('retail');
        }
    }

    function applyModalPriceForCashTier() {
        const opt = $('#modal_item_id option:selected');
        if (!opt.val()) return;
        const saleCurrency = getCurrentSaleCurrency();
        const exchangeRate = getCurrentExchangeRate();
        const retailBase = parseFloat(opt.data('price')) || 0;
        const wholesaleBase = parseFloat(opt.attr('data-wholesale-price')) || 0;
        const hasWs = cashModalItemHasWholesale(opt);
        const tier = hasWs ? ($('#modal_price_tier').val() || 'retail') : 'retail';
        const basePrice = (hasWs && tier === 'wholesale') ? wholesaleBase : retailBase;
        const convertedPrice = convertItemPrice(basePrice, saleCurrency, exchangeRate);
        $('#modal_unit_price').data('original-price', basePrice);
        $('#modal_unit_price').data('original-currency', functionalCurrency);
        $('#modal_unit_price').val(convertedPrice.toFixed(2));
        if (saleCurrency !== functionalCurrency && exchangeRate !== 1) {
            $('#modal_unit_price').attr('title', `Converted from ${basePrice.toFixed(2)} ${functionalCurrency} at rate ${exchangeRate}`);
        } else {
            $('#modal_unit_price').removeAttr('title');
        }
        calculateModalLineTotal();
    }

    $('#modal_price_tier').on('change', function() {
        applyModalPriceForCashTier();
    });

    // Item selection in modal
    $('#modal_item_id').change(function() {
        const selectedOption = $(this).find('option:selected');
        // Reset override flag when new item is selected
        $('#modal_quantity').removeData('allow-override');
        if (selectedOption.val()) {
            toggleCashModalPriceTierRow();
            applyModalPriceForCashTier();
            
            const vatRate = selectedOption.data('vat-rate');
            const vatType = selectedOption.data('vat-type');
            const availableStock = selectedOption.data('stock');
            const itemName = selectedOption.data('name');
            const itemType = selectedOption.data('item-type') || 'product';
            const trackStock = selectedOption.data('track-stock') === 'true' || selectedOption.data('track-stock') === true;
            
            $('#modal_vat_rate').val(vatRate);
            $('#modal_vat_type').val(vatType);
            
            // Only check stock for products that track stock (skip for service items, same as sales invoice)
            if (itemType !== 'service' && trackStock) {
                // Set max quantity to available stock (but allow override for low stock items)
                $('#modal_quantity').attr('max', availableStock);
                
                // Show stock information
                const minimumStock = selectedOption.data('minimum-stock') || 0;
                if (availableStock <= 0) {
                    Swal.fire({
                        title: 'Out of Stock!',
                        text: `${itemName} is currently out of stock.`,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                } else if (minimumStock > 0 && availableStock <= minimumStock) {
                    Swal.fire({
                        title: 'Low Stock Warning',
                        html: `<strong>${itemName}</strong> has low stock:<br>
                               Available: <strong>${availableStock}</strong><br>
                               Minimum Required: <strong>${minimumStock}</strong><br><br>
                               Do you want to continue with this item?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Continue',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33'
                    }).then((result) => {
                        if (!result.isConfirmed) {
                            // User cancelled, clear the selection
                            $('#modal_item_id').val('').trigger('change');
                            resetModalForm();
                        } else {
                            // User confirmed to continue, allow quantity override
                            $('#modal_quantity').data('allow-override', true);
                            $('#modal_quantity').removeAttr('max');
                        }
                    });
                }
            } else {
                // For service items or non-tracked items, do not check stock
                $('#modal_quantity').removeAttr('max');
            }
        } else {
            $('#modal_price_tier_row').hide();
            $('#modal_price_tier').val('retail');
        }
    });

    // Quantity validation
    $('#modal_quantity').on('input', function() {
        const selectedOption = $('#modal_item_id option:selected');
        if (!selectedOption.val()) {
            calculateModalLineTotal();
            return;
        }
        
        const itemType = selectedOption.data('item-type') || 'product';
        const trackStock = selectedOption.data('track-stock') === 'true' || selectedOption.data('track-stock') === true;
        const availableStock = parseInt(selectedOption.data('stock')) || 0;
        const enteredQuantity = parseFloat($(this).val()) || 0;
        const allowOverride = $(this).data('allow-override') || false;
        
        // Skip stock validation for service items or items that don't track stock
        if (itemType === 'service' || !trackStock) {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
            calculateModalLineTotal();
            return;
        }
        
        if (enteredQuantity > availableStock && !allowOverride) {
            $(this).addClass('is-invalid');
            if ($(this).next('.invalid-feedback').length === 0) {
                $(this).after(`<div class="invalid-feedback">Quantity cannot exceed available stock (${availableStock})</div>`);
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
        calculateModalLineTotal();
    });

    // Calculate modal line total on input change
    $('#modal_quantity, #modal_unit_price, #modal_vat_rate').on('input', function() {
        calculateModalLineTotal();
        
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
    $('#modal_discount_type').change(function() {
        if ($(this).val() === '') {
            $('#modal_discount_rate').val(0);
        }
        calculateModalLineTotal();
    });

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
    $(document).on('input', '.item-quantity, .item-price, .item-discount, #discount_amount, #withholding_tax_rate', function() {
        const row = $(this).data('row');
        if (row) {
            updateRowTotal(row);
        }
        calculateTotals();
    });

    function updateRowTotal(row) {
        const quantity = parseFloat($(`input[name="items[${row}][quantity]"]`).val()) || 0;
        const unitPrice = parseFloat($(`input[name="items[${row}][unit_price]"]`).val()) || 0;
        const itemVatType = $(`input[name="items[${row}][vat_type]"]`).val();
        const itemVatRate = parseFloat($(`input[name="items[${row}][vat_rate]"]`).val()) || 0;

        const rowSubtotal = quantity * unitPrice;
        let rowVatAmount = 0;

        if (itemVatType === 'no_vat') {
            rowVatAmount = 0;
        } else if (itemVatType === 'exclusive') {
            rowVatAmount = rowSubtotal * (itemVatRate / 100);
        } else {
            rowVatAmount = rowSubtotal * (itemVatRate / (100 + itemVatRate));
        }

        const lineTotal = rowSubtotal + rowVatAmount;
        $(`.item-total`).eq(row).text(lineTotal.toFixed(2));
    }

    function calculateTotals() {
        let subtotal = 0;
        let vatAmount = 0;

        $('#items-tbody tr').each(function() {
            const quantity = parseFloat($(this).find('.item-quantity').val()) || 0;
            const unitPrice = parseFloat($(this).find('.item-price').val()) || 0;
            const itemVatType = $(this).find('input[name*="[vat_type]"]').val();
            const itemVatRate = parseFloat($(this).find('input[name*="[vat_rate]"]').val()) || 0;

            const rowSubtotal = quantity * unitPrice;
            let rowVatAmount = 0;
            let rowNetAmount = 0;

            if (itemVatType === 'no_vat') {
                rowVatAmount = 0;
                rowNetAmount = rowSubtotal;
            } else if (itemVatType === 'exclusive') {
                rowVatAmount = rowSubtotal * (itemVatRate / 100);
                rowNetAmount = rowSubtotal; // For exclusive, unit price is already net
            } else {
                // VAT inclusive
                rowVatAmount = rowSubtotal * (itemVatRate / (100 + itemVatRate));
                rowNetAmount = rowSubtotal - rowVatAmount; // Net amount = gross - VAT
            }

            subtotal += rowNetAmount; // Add net amount to subtotal
            vatAmount += rowVatAmount;
        });

        // Get invoice-level discount
        const invoiceDiscount = parseFloat($('#discount_amount').val()) || 0;

        // Calculate withholding tax
        const withholdingTaxEnabled = $('#withholding_tax_enabled').is(':checked');
        const withholdingTaxRate = parseFloat($('#withholding_tax_rate').val()) || 0;
        const withholdingTaxType = $('#withholding_tax_type').val();
        let withholdingTaxAmount = 0;

        if (withholdingTaxEnabled && withholdingTaxRate > 0) {
            if (withholdingTaxType === 'percentage') {
                withholdingTaxAmount = subtotal * (withholdingTaxRate / 100);
            } else if (withholdingTaxType === 'fixed') {
                withholdingTaxAmount = withholdingTaxRate;
            }
        }
        
        // Calculate final total
        const totalAmount = subtotal + vatAmount - invoiceDiscount - withholdingTaxAmount;

        // Update displays
        $('#subtotal').text(subtotal.toFixed(2));
        $('#subtotal-input').val(subtotal.toFixed(2));
        
        if (vatAmount > 0) {
            $('#vat-row').show();
            $('#vat-amount').text(vatAmount.toFixed(2));
            $('#vat-amount-input').val(vatAmount.toFixed(2));
        } else {
            $('#vat-row').hide();
        }

        if (withholdingTaxEnabled && withholdingTaxAmount > 0) {
            $('#withholding-tax-row').show();
            $('#withholding-tax-amount').text(withholdingTaxAmount.toFixed(2));
            $('#withholding-tax-amount-input').val(withholdingTaxAmount.toFixed(2));
            $('#withholding-tax-rate-display').text(withholdingTaxRate);
        } else {
            $('#withholding-tax-row').hide();
        }

        $('#total-amount').text(totalAmount.toFixed(2));
        $('#total-amount-input').val(totalAmount.toFixed(2));
    }

    // Handle payment method changes
    $('#payment_method').change(function() {
        const selectedMethod = $(this).val();
        
        // Hide all sections first
        $('#bank_account_section, #cash_deposit_section').hide();
        
        // Show relevant section based on selection
        if (selectedMethod === 'bank') {
            $('#bank_account_section').show();
        } else if (selectedMethod === 'cash_deposit') {
            $('#cash_deposit_section').show();
            
            // Load customer account if a customer is already selected
            const selectedCustomer = $('#customer_id').find('option:selected');
            const customerId = selectedCustomer.attr('data-encoded-id');
            if (customerId) {
                loadCustomerCashDeposits(customerId);
            }
        }
    });

    // Handle customer selection for cash deposits
    $('#customer_id').change(function() {
        const selectedOption = $(this).find('option:selected');
        const customerId = selectedOption.attr('data-encoded-id');
        
        if (customerId && $('#payment_method').val() === 'cash_deposit') {
            loadCustomerCashDeposits(customerId);
        }
    });

    // Load customer cash deposits
    function loadCustomerCashDeposits(customerId) {
        console.log('Loading cash deposits for customer:', customerId);
        
        $.ajax({
            url: `/sales/cash-sales/customer/${customerId}/cash-deposits`,
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                console.log('Cash deposits response:', response);
                const select = $('#cash_deposit_id');
                select.empty();
                select.append('<option value="">Select Customer Account</option>');
                
                if (response.data && response.data.length > 0) {
                    response.data.forEach(function(deposit) {
                        // For customer balance, send a special value to indicate customer balance
                        const value = deposit.id.startsWith('customer_balance_') ? 'customer_balance' : deposit.id;
                        select.append(`<option value="${value}" data-balance-id="${deposit.id}">${deposit.balance_text}</option>`);
                    });
                    $('#no_account_message').hide();
                    select.prop('disabled', false);
                    console.log('Added', response.data.length, 'balance options');
                } else {
                    select.append('<option value="">No account available for this customer</option>');
                    select.prop('disabled', true);
                    $('#no_account_message').show();
                    console.log('No customer accounts available');
                }
            },
            error: function(xhr) {
                console.error('Error loading cash deposits:', xhr);
                console.error('Response text:', xhr.responseText);
                const select = $('#cash_deposit_id');
                select.empty();
                select.append('<option value="">Error loading accounts</option>');
            }
        });
    }

    // Handle discount amount changes
    $(document).on('input', '#discount_amount', function() {
        calculateTotals();
    });

    // Handle withholding tax changes
    $('#withholding_tax_enabled').change(function() {
        if ($(this).is(':checked')) {
            $('#withholding_tax_fields').show();
        } else {
            $('#withholding_tax_fields').hide();
        }
        calculateTotals();
    });

    // Get functional currency for exchange rate calculations
    const functionalCurrency = '{{ \App\Models\SystemSetting::getValue("functional_currency", auth()->user()->company->functional_currency ?? "TZS") }}';
    
    // Function to convert item price from functional currency to sale currency
    function convertItemPrice(basePrice, saleCurrency, exchangeRate) {
        if (!basePrice || !saleCurrency || !exchangeRate) {
            return basePrice;
        }
        
        // If sale currency is functional currency, no conversion needed
        if (saleCurrency === functionalCurrency) {
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
    
    // Function to get current sale currency
    function getCurrentSaleCurrency() {
        return $('#currency').val() || functionalCurrency;
    }
    
    // Handle currency change - Use Select2 event for proper handling
    $('#currency').on('select2:select', function(e) {
        const selectedCurrency = $(this).val();
        handleCurrencyChange(selectedCurrency);
    }).on('change', function() {
        // Fallback for non-Select2 scenarios
        const selectedCurrency = $(this).val();
        handleCurrencyChange(selectedCurrency);
    });
    
    function handleCurrencyChange(selectedCurrency) {
        if (selectedCurrency && selectedCurrency !== functionalCurrency) {
            $('#exchange_rate').prop('required', true);
            // Auto-fetch exchange rate when currency changes (use sale date if available)
            const saleDate = $('#sale_date').val();
            fetchExchangeRate(selectedCurrency, saleDate);
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
        const saleCurrency = getCurrentSaleCurrency();
        const exchangeRate = getCurrentExchangeRate();
        
        // Convert prices in existing rows
        $('input.item-price').each(function() {
            const $priceInput = $(this);
            const originalPrice = $priceInput.data('original-price');
            
            // If original price is stored, use it; otherwise use current value as base
            const basePrice = originalPrice || parseFloat($priceInput.val()) || 0;
            
            if (basePrice > 0) {
                const convertedPrice = convertItemPrice(basePrice, saleCurrency, exchangeRate);
                $priceInput.val(convertedPrice.toFixed(2));
                
                // Store original price if not already stored
                if (!originalPrice) {
                    $priceInput.data('original-price', basePrice);
                    $priceInput.data('original-currency', functionalCurrency);
                }
                
                // Update tooltip
                if (saleCurrency !== functionalCurrency && exchangeRate !== 1) {
                    $priceInput.attr('title', `Converted from ${basePrice.toFixed(2)} ${functionalCurrency} at rate ${exchangeRate}`);
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
        
        if ($('#modal_item_id').val()) {
            applyModalPriceForCashTier();
        }
        
        // Recalculate totals
        calculateTotals();
    }
    
    // Convert prices when exchange rate changes
    $('#exchange_rate').on('input change', function() {
        // Only convert if currency is not functional currency
        const saleCurrency = getCurrentSaleCurrency();
        if (saleCurrency !== functionalCurrency) {
            convertAllItemPrices();
        }
    });
    
    // Fetch exchange rate button
    $('#fetch-rate-btn').on('click', function() {
        const currency = $('#currency').val();
        fetchExchangeRate(currency);
    });
    
    // Function to fetch exchange rate from FX RATES MANAGEMENT
    function fetchExchangeRate(currency = null, saleDate = null) {
        currency = currency || $('#currency').val();
        saleDate = saleDate || $('#sale_date').val() || new Date().toISOString().split('T')[0];
        
        if (!currency || currency === functionalCurrency) {
            $('#exchange_rate').val('1.000000');
            $('#rate-info').hide();
            return;
        }

        const btn = $('#fetch-rate-btn');
        const originalHtml = btn.html();
        const rateInput = $('#exchange_rate');
        
        // Show loading state
        btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i>');
        rateInput.prop('disabled', true);
        
        // Use the FX rates API endpoint
        $.ajax({
            url: '{{ route("accounting.fx-rates.get-rate") }}',
            method: 'GET',
            data: {
                from_currency: currency,
                to_currency: functionalCurrency,
                date: saleDate, // Use sale date instead of today
                rate_type: 'spot'
            },
            success: function(response) {
                if (response.success && response.rate) {
                    const rate = parseFloat(response.rate);
                    rateInput.val(rate.toFixed(6));
                    const source = response.source || 'FX RATES MANAGEMENT';
                    $('#rate-source').text(`Rate from ${source} for ${saleDate}: 1 ${currency} = ${rate.toFixed(6)} ${functionalCurrency}`);
                    $('#rate-info').show();
                    
                    // Show success notification
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                    Toast.fire({
                        icon: 'success',
                        title: `Rate updated: 1 ${currency} = ${rate.toFixed(6)} ${functionalCurrency}`
                    });
                } else {
                    console.warn('Exchange rate API returned unexpected format:', response);
                    // Try fallback
                    fetchExchangeRateFallback(currency, saleDate);
                }
            },
            error: function(xhr) {
                console.error('Failed to fetch exchange rate:', xhr);
                fetchExchangeRateFallback(currency, saleDate);
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
                rateInput.prop('disabled', false);
            }
        });
    }

    // Sync withholding tax settings
    $('#withholding_tax_rate, #withholding_tax_type').on('input change', function() {
        calculateTotals();
    });

    // Form submission
    function displayValidationErrors(errors) {
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        if (!errors || typeof errors !== 'object') {
            return;
        }
        Object.keys(errors).forEach(field => {
            let input = $(`[name="${field}"]`);
            if (!input.length && field.includes('.')) {
                const bracketName = field.replace(/\.([0-9]+)\./g, '[$1][') + ']';
                input = $(`[name="${bracketName}"]`);
            }
            if (input.length) {
                input.addClass('is-invalid');
                const feedback = input.siblings('.invalid-feedback');
                if (feedback.length) {
                    feedback.text(Array.isArray(errors[field]) ? errors[field][0] : errors[field]);
                }
            }
        });
    }

    $('#cash-sale-form').submit(function(e) {
        e.preventDefault();
        
        if ($('#items-tbody tr').length === 0) {
            Swal.fire('Error', 'Please add at least one item to the cash sale', 'error');
            return;
        }

        const formData = new FormData(this);
        const submitBtn = $('#submit-btn');
        
        submitBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i>Creating...');

        $.ajax({
            url: '{{ route("sales.cash-sales.store") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                Swal.fire({
                    title: 'Success!',
                    text: 'Cash sale created successfully',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    window.location.href = '{{ route("sales.cash-sales.index") }}';
                });
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Create Cash Sale');
                
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON && xhr.responseJSON.errors;
                    displayValidationErrors(errors || {});
                    Swal.fire('Error', 'Please check the form for errors', 'error');
                } else {
                    Swal.fire('Error', 'An error occurred while creating the cash sale', 'error');
                }
            }
        });
    });

    // Initialize item counter
    let itemCounter = 0;

    function resetModalForm() {
        $('#modal_item_id').val('').trigger('change');
        $('#modal_quantity').val(1);
        $('#modal_unit_price').val('');
        $('#modal_price_tier').val('retail');
        $('#modal_price_tier_row').hide();
        $('#modal_vat_type').val('{{ get_default_vat_type() }}');
        $('#modal_vat_rate').val('{{ get_default_vat_rate() }}');
        $('#modal_notes').val('');
        $('#modal-line-total').text('0.00');
    }

    function calculateModalLineTotal() {
        const quantity = parseFloat($('#modal_quantity').val()) || 0;
        const unitPrice = parseFloat($('#modal_unit_price').val()) || 0;
        const vatRate = parseFloat($('#modal_vat_rate').val()) || 0;
        const vatType = $('#modal_vat_type').val();

        const subtotal = quantity * unitPrice;
        let vatAmount = 0;
        let lineTotal = 0;

        if (vatType === 'no_vat') {
            vatAmount = 0;
            lineTotal = subtotal;
        } else if (vatType === 'exclusive') {
            vatAmount = subtotal * (vatRate / 100);
            lineTotal = subtotal + vatAmount;
        } else {
            vatAmount = subtotal * (vatRate / (100 + vatRate));
            lineTotal = subtotal;
        }

        // Update modal display
        $('#modal-line-total').text(lineTotal.toFixed(2));
    }

    function addItemToTable() {
        const itemId = $('#modal_item_id').val();
        const selectedOption = $('#modal_item_id option:selected');
        const itemName = selectedOption.data('name');
        const quantity = parseFloat($('#modal_quantity').val()) || 0;
        const unitPrice = parseFloat($('#modal_unit_price').val()) || 0;
        const vatRate = parseFloat($('#modal_vat_rate').val()) || 0;
        const vatType = $('#modal_vat_type').val();
        const notes = $('#modal_notes').val();
        const priceTier = ($('#modal_price_tier_row').is(':visible'))
            ? ($('#modal_price_tier').val() || 'retail')
            : 'retail';
        const lineKey = `${itemId}_${priceTier}`;

        const originalPrice = $('#modal_unit_price').data('original-price') || parseFloat(selectedOption.data('price')) || unitPrice;

        if (!itemId || quantity <= 0 || unitPrice <= 0) {
            Swal.fire('Error', 'Please fill in all required fields correctly', 'error');
            return;
        }

        const existingRow = $(`tr[data-line-key="${lineKey}"]`);
        if (existingRow.length > 0) {
            const currentQuantity = parseFloat(existingRow.find('.item-quantity').val()) || 0;
            const newQuantity = currentQuantity + quantity;

            const itemTypeMerge = selectedOption.data('item-type') || 'product';
            const trackStockMerge = selectedOption.data('track-stock') === 'true' || selectedOption.data('track-stock') === true;
            const availableStockMerge = parseFloat(selectedOption.data('stock')) || 0;

            if (itemTypeMerge !== 'service' && trackStockMerge && newQuantity > availableStockMerge) {
                Swal.fire({
                    icon: 'error',
                    title: 'Insufficient Stock',
                    text: `Available: ${availableStockMerge}, Total requested: ${newQuantity}`,
                    confirmButtonColor: '#d33'
                });
                return;
            }

            existingRow.find('.item-quantity').val(newQuantity);

            const $priceInput = existingRow.find('.item-price');
            if (!$priceInput.data('original-price')) {
                const rowItemId = existingRow.data('item-id');
                if (rowItemId) {
                    const itemOption = $('#modal_item_id').find(`option[value="${rowItemId}"]`);
                    if (itemOption.length) {
                        const rowTier = existingRow.find('input[name*="[price_tier]"]').val() || 'retail';
                        const basePrice = rowTier === 'wholesale'
                            ? (parseFloat(itemOption.attr('data-wholesale-price')) || parseFloat($priceInput.val()) || 0)
                            : (parseFloat(itemOption.data('price')) || parseFloat($priceInput.val()) || 0);
                        $priceInput.data('original-price', basePrice);
                    }
                }
            }

            const rowIdx = existingRow.find('.item-quantity').data('row');
            if (rowIdx !== undefined) {
                updateRowTotal(rowIdx);
            }
            calculateTotals();
            $('#itemModal').modal('hide');
            resetModalForm();
            return;
        }

        const itemType = selectedOption.data('item-type') || 'product';
        const trackStock = selectedOption.data('track-stock') === 'true' || selectedOption.data('track-stock') === true;
        const availableStock = parseFloat(selectedOption.data('stock')) || 0;

        if (itemType !== 'service' && trackStock && quantity > availableStock) {
            Swal.fire({
                icon: 'error',
                title: 'Insufficient Stock',
                text: `Available stock: ${availableStock} units`,
                confirmButtonColor: '#d33'
            });
            return;
        }
        
        const subtotal = quantity * unitPrice;
        let vatAmount = 0;
        let lineTotal = 0;

        if (vatType === 'no_vat') {
            vatAmount = 0;
            lineTotal = subtotal;
        } else if (vatType === 'exclusive') {
            vatAmount = subtotal * (vatRate / 100);
            lineTotal = subtotal + vatAmount;
        } else {
            vatAmount = subtotal * (vatRate / (100 + vatRate));
            lineTotal = subtotal;
        }
    
        const vatDisplay = vatType === 'no_vat' ? 'No VAT' : `${vatRate}%`;
        const tierLabel = priceTier === 'wholesale' ? ' <span class="badge bg-secondary">Wholesale</span>' : '';

        const row = `
            <tr data-item-id="${itemId}" data-line-key="${lineKey}">
                <td>
                    <input type="hidden" name="items[${itemCounter}][inventory_item_id]" value="${itemId}">
                    <input type="hidden" name="items[${itemCounter}][price_tier]" value="${priceTier}">
                    <input type="hidden" name="items[${itemCounter}][item_name]" value="${itemName}">
                    <input type="hidden" name="items[${itemCounter}][vat_type]" value="${vatType}">
                    <input type="hidden" name="items[${itemCounter}][vat_rate]" value="${vatRate}">
                    <input type="hidden" name="items[${itemCounter}][notes]" value="${notes}">
                    <div class="fw-bold">${itemName}${tierLabel}</div>
                    <small class="text-muted">${notes || ''}</small>
                </td>
                <td>
                    <input type="number" class="form-control item-quantity" 
                           name="items[${itemCounter}][quantity]" value="${quantity}" 
                           step="0.01" min="0.01" data-row="${itemCounter}">
                </td>
                <td>
                    <input type="number" class="form-control item-price" 
                           name="items[${itemCounter}][unit_price]" value="${unitPrice.toFixed(2)}" 
                           step="0.01" min="0" data-row="${itemCounter}"
                           data-original-price="${originalPrice}"
                           data-original-currency="${functionalCurrency}"
                           ${getCurrentSaleCurrency() !== functionalCurrency ? `title="Converted from ${originalPrice.toFixed(2)} ${functionalCurrency}"` : ''}>
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
        itemCounter++;
        calculateTotals();
    }
});
</script>
@endpush
@endsection