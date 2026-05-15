@extends('layouts.main')

@section('title', 'Edit Cash Purchase')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchase Management', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Cash Purchases', 'url' => route('purchases.cash-purchases.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Edit Cash Purchase', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">EDIT CASH PURCHASE</h6>
        <hr />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-money me-2"></i>Edit Cash Purchase</h5>
            </div>
            <div class="card-body">
                @can('edit cash purchases')
                <form method="POST" action="{{ route('purchases.cash-purchases.update', $purchase->encoded_id) }}" id="cash-purchase-form" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="supplier_id" class="form-label">Supplier <span class="text-danger">*</span></label>
                                <select class="form-select select2-single" id="supplier_id" name="supplier_id" required>
                                    <option value="">Select Supplier</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ $purchase->supplier_id == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }} - {{ $supplier->phone }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="purchase_date" class="form-label">Purchase Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="purchase_date" name="purchase_date" value="{{ optional($purchase->purchase_date)->format('Y-m-d') }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="bank" selected>Bank</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
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
                                                {{ old('currency', $purchase->currency ?? $functionalCurrency) == $currency->currency_code ? 'selected' : '' }}>
                                            {{ $currency->currency_name ?? $currency->currency_code }} ({{ $currency->currency_code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="exchange_rate" class="form-label">Exchange Rate</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="exchange_rate" name="exchange_rate" value="{{ old('exchange_rate', number_format($purchase->exchange_rate ?? 1, 6, '.', '')) }}" step="0.000001" min="0.000001" placeholder="1.000000">
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
                            <div class="mb-3" id="bank_account_section">
                                <label for="bank_account_id" class="form-label">Bank Account</label>
                                <select class="form-select select2-single" id="bank_account_id" name="bank_account_id" required>
                                    <option value="">Select Bank Account</option>
                                    @foreach($bankAccounts as $bankAccount)
                                        <option value="{{ $bankAccount->id }}" {{ (int)$purchase->bank_account_id === (int)$bankAccount->id ? 'selected' : '' }}>{{ $bankAccount->name }} ({{ $bankAccount->account_number }})</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="supplier_advance_applied_amount" class="form-label">Apply from supplier advance <span class="text-muted">(optional)</span></label>
                                <input type="number" step="0.01" min="0" class="form-control" id="supplier_advance_applied_amount" name="supplier_advance_applied_amount" value="{{ old('supplier_advance_applied_amount', $purchase->supplier_advance_applied_amount ?? 0) }}">
                                <small class="text-muted">In <strong>purchase currency</strong>. Reduces the bank payment. Available advance for the selected supplier (including this document’s current application): <strong><span id="supplier-advance-available">0.00</span> {{ $functionalCurrency }}</strong>.</small>
                                @error('supplier_advance_applied_amount')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="mb-3 w-100 border rounded p-2 bg-light small">
                                <div class="fw-semibold mb-1">Settlement preview</div>
                                <div>Bank pays: <span id="bank-pay-preview">0.00</span></div>
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
                                            <th width="30%">Item</th>
                                            <th width="15%">Quantity</th>
                                            <th width="15%">Unit Cost</th>
                                            <th width="15%">VAT</th>
                                            <th width="15%">Total</th>
                                            <th width="10%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="items-tbody">
                                        @php $idx = 0; @endphp
                                        @foreach($purchase->items as $line)
                                        @php
                                            $item = $line->inventoryItem;
                                            $base = (float)$line->quantity * (float)$line->unit_cost;
                                            $vat = 0;
                                            if ($line->vat_type === 'exclusive' && (float)$line->vat_rate > 0) {
                                                $vat = $base * ((float)$line->vat_rate / 100);
                                            } elseif ($line->vat_type === 'inclusive' && (float)$line->vat_rate > 0) {
                                                $vat = $base * ((float)$line->vat_rate / (100 + (float)$line->vat_rate));
                                            }
                                            $total = $line->vat_type === 'exclusive' ? ($base + $vat) : $base;
                                        @endphp
                                        <tr>
                                            <td>
                                                <input type="hidden" name="items[{{ $idx }}][inventory_item_id]" value="{{ $line->inventory_item_id }}">
                                                <input type="hidden" name="items[{{ $idx }}][vat_type]" value="{{ $line->vat_type }}">
                                                <input type="hidden" name="items[{{ $idx }}][vat_rate]" value="{{ (float)$line->vat_rate }}">
                                                <input type="hidden" name="items[{{ $idx }}][description]" value="{{ $line->description }}">
                                                <div class="fw-bold">{{ $item->name ?? 'Item' }}</div>
                                                <small class="text-muted">{{ $line->description }}</small>
                                            </td>
                                            <td><input type="number" class="form-control item-quantity" name="items[{{ $idx }}][quantity]" value="{{ (float)$line->quantity }}" step="0.01" min="0.01"></td>
                                            <td>
                                                @php
                                                    // Get original price: if purchase was in foreign currency, reverse-convert to get TZS price
                                                    $originalPrice = $line->unit_cost;
                                                    if ($purchase->currency && $purchase->currency != $functionalCurrency && $purchase->exchange_rate && $purchase->exchange_rate != 1) {
                                                        $originalPrice = $line->unit_cost * $purchase->exchange_rate;
                                                    } else {
                                                        // Try to get from item's base price
                                                        $originalPrice = optional($item)->cost_price ?? $line->unit_cost;
                                                    }
                                                @endphp
                                                <input type="number" class="form-control item-cost" name="items[{{ $idx }}][unit_cost]" value="{{ (float)$line->unit_cost }}" step="0.01" min="0"
                                                       data-original-price="{{ $originalPrice }}"
                                                       data-original-currency="{{ $functionalCurrency }}">
                                            </td>
                                            <td><small class="text-muted">@if($line->vat_type==='no_vat') No VAT @else {{ (float)$line->vat_rate }}% {{ $line->vat_type }} @endif</small></td>
                                            <td><span class="item-total">{{ number_format($total, 2) }}</span></td>
                                            <td><button type="button" class="btn btn-outline-danger btn-sm remove-item"><i class="bx bx-trash"></i></button></td>
                                        </tr>
                                        @php $idx++; @endphp
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                            <td><strong id="subtotal">0.00</strong></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="subtotal" id="subtotal-input" value="0">
                                        <tr id="vat-row" style="display: none;">
                                            <td colspan="4" class="text-end"><strong>VAT Amount:</strong></td>
                                            <td><strong id="vat-amount">0.00</strong></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="vat_amount" id="vat-amount-input" value="0">
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Discount:</strong></td>
                                            <td>
                                                <input type="number" class="form-control" id="discount_amount" name="discount_amount" value="{{ (float)($purchase->discount_amount ?? 0) }}" step="0.01" min="0" placeholder="0.00">
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr class="table-info">
                                            <td colspan="4" class="text-end"><strong>Total Amount:</strong></td>
                                            <td><strong id="total-amount">0.00</strong></td>
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
                                <textarea class="form-control" id="notes" name="notes" rows="4" placeholder="Additional notes for this cash purchase...">{{ $purchase->notes }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="terms_conditions" class="form-label">Terms & Conditions</label>
                                <textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="4" placeholder="Terms and conditions...">{{ $purchase->terms_conditions }}</textarea>
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
                                @if(!empty($purchase->attachment))
                                    <div class="mt-2">
                                        <a href="{{ asset('storage/' . $purchase->attachment) }}" target="_blank">
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
                        <a href="{{ route('purchases.cash-purchases.show', $purchase->encoded_id) }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="bx bx-check me-1"></i>Update Cash Purchase
                        </button>
                    </div>
                </form>
                @else
                <div class="alert alert-warning">You do not have permission to edit cash purchases.</div>
                @endcan
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
                                data-cost="{{ $item->resolved_cost_price ?? $item->cost_price ?? 0 }}"
                                data-unit="{{ $item->unit_of_measure }}">
                            {{ $item->name }} ({{ $item->code }}) - Cost: {{ number_format($item->resolved_cost_price ?? $item->cost_price ?? 0, 2) }}
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
    $('.select2-single').select2({ theme: 'bootstrap-5', width: '100%' });
    $('.select2-modal').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#itemModal') });

    // Toggle bank section based on current value
    function toggleBank(){ $('#bank_account_section').toggle($('#payment_method').val()==='bank'); }
    toggleBank();
    $('#payment_method').on('change', toggleBank);

    // Get functional currency for exchange rate calculations
    const functionalCurrency = '{{ \App\Models\SystemSetting::getValue("functional_currency", auth()->user()->company->functional_currency ?? "TZS") }}';
    const supplierAdvanceBalances = @json($supplierAdvanceBalances ?? []);

    function updateAdvanceUi() {
        const sid = $('#supplier_id').val();
        let bal = 0;
        if (sid && supplierAdvanceBalances[sid] !== undefined && supplierAdvanceBalances[sid] !== null) {
            bal = parseFloat(supplierAdvanceBalances[sid]);
        }
        $('#supplier-advance-available').text(isNaN(bal) ? '0.00' : bal.toFixed(2));
        const total = parseFloat($('#total-amount-input').val()) || 0;
        const adv = Math.max(0, parseFloat($('#supplier_advance_applied_amount').val()) || 0);
        $('#bank-pay-preview').text(Math.max(0, total - adv).toFixed(2));
    }
    $('#supplier_id').on('change select2:select', updateAdvanceUi);
    $('#supplier_advance_applied_amount').on('input change', updateAdvanceUi);

    // Function to convert item price from functional currency to purchase currency
    function convertItemPrice(basePrice, purchaseCurrency, exchangeRate) {
        if (!basePrice || !purchaseCurrency || !exchangeRate) {
            return basePrice;
        }
        
        // If purchase currency is functional currency, no conversion needed
        if (purchaseCurrency === functionalCurrency) {
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
    
    // Function to get current purchase currency
    function getCurrentPurchaseCurrency() {
        return $('#currency').val() || functionalCurrency;
    }
    
    $('#add-item').click(function() { $('#itemModal').modal('show'); resetModalForm(); });

    $('#modal_item_id').change(function() {
        const opt = $(this).find('option:selected');
        if (opt.val()) {
            const basePrice = parseFloat(opt.data('cost')) || 0;
            const purchaseCurrency = getCurrentPurchaseCurrency();
            const exchangeRate = getCurrentExchangeRate();
            
            // Convert price if currency is different from functional currency
            const convertedPrice = convertItemPrice(basePrice, purchaseCurrency, exchangeRate);
            
            // Store original price for reference
            $('#modal_unit_cost').data('original-price', basePrice);
            $('#modal_unit_cost').data('original-currency', functionalCurrency);
            
            // Set converted price
            $('#modal_unit_cost').val(convertedPrice.toFixed(2));
            
            // Show price info if converted
            if (purchaseCurrency !== functionalCurrency && exchangeRate !== 1) {
                $('#modal_unit_cost').attr('title', `Converted from ${basePrice.toFixed(2)} ${functionalCurrency} at rate ${exchangeRate}`);
            } else {
                $('#modal_unit_cost').removeAttr('title');
            }
            
            calculateModalLineTotal();
        }
    });

    $('#modal_quantity, #modal_unit_cost, #modal_vat_rate, #modal_vat_type').on('input change', function() {
        calculateModalLineTotal();
        
        // Store original price if manually edited (for price conversion)
        if ($(this).attr('id') === 'modal_unit_cost') {
            const selectedOption = $('#modal_item_id').find('option:selected');
            if (selectedOption.val() && !$('#modal_unit_cost').data('original-price')) {
                const basePrice = parseFloat(selectedOption.data('cost')) || 0;
                if (basePrice > 0) {
                    $('#modal_unit_cost').data('original-price', basePrice);
                }
            }
        }
    });
    $('#add-item-btn').click(function() { addItemToTable(); });
    $(document).on('click', '.remove-item', function() { $(this).closest('tr').remove(); calculateTotals(); });
    $(document).on('input change', '.item-quantity, .item-cost, #discount_amount', function() { calculateTotals(); });
    
    // Function to convert all item prices in the table when currency/exchange rate changes
    function convertAllItemPrices() {
        const purchaseCurrency = getCurrentPurchaseCurrency();
        const exchangeRate = getCurrentExchangeRate();
        
        // Convert prices in existing rows
        $('input.item-cost').each(function() {
            const $priceInput = $(this);
            const originalPrice = $priceInput.data('original-price');
            
            // If original price is stored, use it; otherwise use current value as base
            const basePrice = originalPrice || parseFloat($priceInput.val()) || 0;
            
            if (basePrice > 0) {
                const convertedPrice = convertItemPrice(basePrice, purchaseCurrency, exchangeRate);
                $priceInput.val(convertedPrice.toFixed(2));
                
                // Store original price if not already stored
                if (!originalPrice) {
                    $priceInput.data('original-price', basePrice);
                    $priceInput.data('original-currency', functionalCurrency);
                }
                
                // Update tooltip
                if (purchaseCurrency !== functionalCurrency && exchangeRate !== 1) {
                    $priceInput.attr('title', `Converted from ${basePrice.toFixed(2)} ${functionalCurrency} at rate ${exchangeRate}`);
                } else {
                    $priceInput.removeAttr('title');
                }
            }
        });
        
        // Convert price in modal if item is selected
        if ($('#modal_item_id').val()) {
            const selectedOption = $('#modal_item_id').find('option:selected');
            if (selectedOption.val()) {
                const basePrice = parseFloat(selectedOption.data('cost')) || 0;
                if (basePrice > 0) {
                    const convertedPrice = convertItemPrice(basePrice, purchaseCurrency, exchangeRate);
                    $('#modal_unit_cost').val(convertedPrice.toFixed(2));
                    $('#modal_unit_cost').data('original-price', basePrice);
                    
                    if (purchaseCurrency !== functionalCurrency && exchangeRate !== 1) {
                        $('#modal_unit_cost').attr('title', `Converted from ${basePrice.toFixed(2)} ${functionalCurrency} at rate ${exchangeRate}`);
                    } else {
                        $('#modal_unit_cost').removeAttr('title');
                    }
                }
            }
        }
        
        // Recalculate totals
        calculateTotals();
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
            // Auto-fetch exchange rate when currency changes
            fetchExchangeRate(selectedCurrency);
        } else {
            $('#exchange_rate').prop('required', false);
            $('#exchange_rate').val('1.000000');
        }
        
        // Convert all existing item prices when currency changes
        convertAllItemPrices();
    }
    
    // Convert prices when exchange rate changes
    $('#exchange_rate').on('input change', function() {
        // Only convert if currency is not functional currency
        const purchaseCurrency = getCurrentPurchaseCurrency();
        if (purchaseCurrency !== functionalCurrency) {
            convertAllItemPrices();
        }
    });
    
    // Fetch exchange rate button
    $('#fetch-rate-btn')?.on('click', function() {
        const currency = $('#currency').val();
        fetchExchangeRate(currency);
    });
    
    // Function to fetch exchange rate from API
    function fetchExchangeRate(currency = null) {
        currency = currency || $('#currency').val();
        if (!currency || currency === functionalCurrency) {
            $('#exchange_rate').val('1.000000');
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
                date: new Date().toISOString().split('T')[0], // Today's date
                rate_type: 'spot'
            },
            success: function(response) {
                if (response.success && response.rate) {
                    const rate = parseFloat(response.rate);
                    rateInput.val(rate.toFixed(6));
                    $('#rate-source').text(`Rate fetched: 1 ${currency} = ${rate.toFixed(6)} ${functionalCurrency}`);
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
                }
            },
            error: function(xhr) {
                console.error('Failed to fetch exchange rate:', xhr);
                // Try fallback API
                $.get('{{ route("api.exchange-rates.rate") }}', {
                    from: currency,
                    to: functionalCurrency
                })
                .done(function(response) {
                    if (response.success && response.data && response.data.rate) {
                        const rate = parseFloat(response.data.rate);
                        rateInput.val(rate.toFixed(6));
                        $('#rate-source').text(`Rate fetched (fallback): 1 ${currency} = ${rate.toFixed(6)} ${functionalCurrency}`);
                        $('#rate-info').show();
                    }
                })
                .fail(function() {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Rate Fetch Failed',
                        text: 'Please manually enter the exchange rate.',
                        timer: 3000,
                        showConfirmButton: false
                    });
                });
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
                rateInput.prop('disabled', false);
            }
        });
    }
    
    // Convert existing items on page load if currency has changed
    setTimeout(function() {
        const currentCurrency = getCurrentPurchaseCurrency();
        const purchaseCurrency = '{{ $purchase->currency ?? "TZS" }}';
        if (currentCurrency !== purchaseCurrency) {
            convertAllItemPrices();
        }
    }, 100);

    let itemCounter = $('#items-tbody tr').length;

    // Initial totals
    calculateTotals();
    updateAdvanceUi();

    function resetModalForm(){
        $('#modal_item_id').val('').trigger('change');
        $('#modal_quantity').val(1);
        $('#modal_unit_cost').val('');
        $('#modal_vat_type').val('{{ get_default_vat_type() }}');
        $('#modal_vat_rate').val('{{ get_default_vat_rate() }}');
        $('#modal_notes').val('');
        $('#modal-line-total').text('0.00');
    }

    function calculateModalLineTotal(){
        const qty = parseFloat($('#modal_quantity').val()) || 0;
        const cost = parseFloat($('#modal_unit_cost').val()) || 0;
        const rate = parseFloat($('#modal_vat_rate').val()) || 0;
        const type = $('#modal_vat_type').val();
        const base = qty * cost;
        let vat = 0, total = 0;
        if (type === 'exclusive') { vat = base * (rate/100); total = base + vat; }
        else if (type === 'inclusive' && rate > 0) { vat = base * (rate/(100+rate)); total = base; }
        else { total = base; }
        $('#modal-line-total').text(total.toFixed(2));
    }

    function addItemToTable(){
        const itemId = $('#modal_item_id').val();
        const opt = $('#modal_item_id option:selected');
        const itemName = opt.data('name');
        const qty = parseFloat($('#modal_quantity').val()) || 0;
        const cost = parseFloat($('#modal_unit_cost').val()) || 0;
        const rate = parseFloat($('#modal_vat_rate').val()) || 0;
        const type = $('#modal_vat_type').val();
        const notes = $('#modal_notes').val();
        
        // Get original price from modal or item data
        let originalPrice = parseFloat($('#modal_unit_cost').data('original-price')) || 0;
        if (!originalPrice) {
            originalPrice = parseFloat(opt.data('cost')) || cost;
        }
        if (!originalPrice) {
            originalPrice = cost;
        }
        
        if (!itemId || qty <= 0 || cost < 0) { Swal.fire('Error','Fill item, quantity, and unit cost','error'); return; }
        const base = qty * cost;
        let vat = 0, total = 0;
        if (type === 'exclusive') { vat = base * (rate/100); total = base + vat; }
        else if (type === 'inclusive' && rate > 0) { vat = base * (rate/(100+rate)); total = base; }
        else { total = base; }
        const vatDisplay = (type === 'no_vat') ? 'No VAT' : (rate + '% ' + type);
        const row = `
            <tr>
                <td>
                    <input type="hidden" name="items[${itemCounter}][inventory_item_id]" value="${itemId}">
                    <input type="hidden" name="items[${itemCounter}][vat_type]" value="${type}">
                    <input type="hidden" name="items[${itemCounter}][vat_rate]" value="${rate}">
                    <input type="hidden" name="items[${itemCounter}][description]" value="${notes}">
                    <div class="fw-bold">${itemName}</div>
                    <small class="text-muted">${notes || ''}</small>
                </td>
                <td><input type="number" class="form-control item-quantity" name="items[${itemCounter}][quantity]" value="${qty}" step="0.01" min="0.01"></td>
                <td><input type="number" class="form-control item-cost" name="items[${itemCounter}][unit_cost]" value="${cost.toFixed(2)}" step="0.01" min="0"
                           data-original-price="${originalPrice}"
                           data-original-currency="${functionalCurrency}"
                           ${getCurrentPurchaseCurrency() !== functionalCurrency ? `title="Converted from ${originalPrice.toFixed(2)} ${functionalCurrency}"` : ''}></td>
                <td><small class="text-muted">${vatDisplay}</small></td>
                <td><span class="item-total">${total.toFixed(2)}</span></td>
                <td><button type="button" class="btn btn-outline-danger btn-sm remove-item"><i class="bx bx-trash"></i></button></td>
            </tr>`;
        $('#items-tbody').append(row);
        $('#itemModal').modal('hide');
        itemCounter++;
        calculateTotals();
    }

    function calculateTotals(){
        let subtotal = 0, vatAmount = 0;
        $('#items-tbody tr').each(function(){
            const qty = parseFloat($(this).find('.item-quantity').val()) || 0;
            const cost = parseFloat($(this).find('.item-cost').val()) || 0;
            const type = $(this).find('input[name*="[vat_type]"]').val();
            const rate = parseFloat($(this).find('input[name*="[vat_rate]"]').val()) || 0;
            const base = qty * cost;
            let vat = 0;
            if (type === 'exclusive') vat = base * (rate/100);
            else if (type === 'inclusive' && rate > 0) vat = base * (rate/(100+rate));
            subtotal += (type === 'inclusive') ? (base - vat) : base;
            vatAmount += vat;
        });
        const discount = parseFloat($('#discount_amount').val()) || 0;
        const total = Math.max(0, subtotal + vatAmount - discount);
        $('#subtotal').text(subtotal.toFixed(2));
        $('#subtotal-input').val(subtotal.toFixed(2));
        if (vatAmount > 0) { $('#vat-row').show(); $('#vat-amount').text(vatAmount.toFixed(2)); $('#vat-amount-input').val(vatAmount.toFixed(2)); }
        else { $('#vat-row').hide(); $('#vat-amount-input').val('0'); }
        $('#total-amount').text(total.toFixed(2));
        $('#total-amount-input').val(total.toFixed(2));
        updateAdvanceUi();
    }

    $('#cash-purchase-form').submit(function(e){
        if ($('#items-tbody tr').length === 0) {
            e.preventDefault();
            Swal.fire('Error','Please add at least one item','error');
        }
    });
});
</script>
@endpush
@endsection


