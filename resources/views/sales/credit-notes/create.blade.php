@extends('layouts.main')

@section('title', 'Create Credit Note')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-store'],
            ['label' => 'Credit Notes', 'url' => route('sales.credit-notes.index'), 'icon' => 'bx bx-minus-circle'],
            ['label' => 'Create Credit Note', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">CREATE CREDIT NOTE</h6>
        <hr />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-minus-circle me-2"></i>New Credit Note</h5>
            </div>
            <div class="card-body">
                <form id="credit-note-form" method="POST" action="{{ route('sales.credit-notes.store') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="sales_invoice_id" name="sales_invoice_id" value="">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                <select class="form-select select2-single" id="customer_id" name="customer_id" required>
                                    <option value="">Select Customer</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }} - {{ $customer->phone }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="credit_note_date" class="form-label">Credit Note Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="credit_note_date" name="credit_note_date" value="{{ date('Y-m-d') }}" required>
                                <div class="invalid-feedback"></div>
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
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="reference_invoice_id" class="form-label">Reference Invoice</label>
                                <select class="form-select select2-single" id="reference_invoice_id" name="reference_invoice_id">
                                    <option value="">Select Invoice (Optional)</option>
                                    @foreach($invoices as $invoice)
                                        <option value="{{ $invoice->id }}">{{ $invoice->invoice_number ?? ('INV-' . $invoice->id) }} - {{ $invoice->customer->name ?? '' }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">If this credit note references a specific sales invoice</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="type" class="form-label">Credit Note Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="type" name="type" required>
                                    @foreach($creditNoteTypes as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
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
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason Details <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="Describe the reason for this credit note (e.g., 'Customer returned expired product', 'Quality issue with specific items')" required></textarea>
                                <small class="text-muted">For partial returns, explain which items and why (e.g., "Item A expired, Item B in good condition")</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="warehouse_id" class="form-label">Warehouse</label>
                                <select class="form-select select2-single" id="warehouse_id" name="warehouse_id">
                                    <option value="">Select Warehouse</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Required when returning to stock</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-check mb-3">
                                <input type="hidden" name="refund_now" value="0">
                                <input class="form-check-input" type="checkbox" value="1" id="refund_now" name="refund_now">
                                <label class="form-check-label" for="refund_now">Refund customer now</label>
                            </div>
                            <div id="bank_account_group" style="display: none;">
                                <label for="bank_account_id" class="form-label">Bank Account for Refund</label>
                                <select class="form-select" id="bank_account_id" name="bank_account_id">
                                    <option value="">Select Bank Account</option>
                                    @foreach($bankAccounts as $bankAccount)
                                        <option value="{{ $bankAccount->id }}">{{ $bankAccount->name }} - {{ $bankAccount->account_number }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Select the bank account to process the refund from</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mb-3">
                                <input type="hidden" name="return_to_stock" value="0">
                                <input class="form-check-input" type="checkbox" value="1" id="return_to_stock" name="return_to_stock">
                                <label class="form-check-label" for="return_to_stock">Return items to stock</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mb-3">
                                <input type="hidden" name="is_exchange" value="0">
                                <input class="form-check-input" type="checkbox" value="1" id="is_exchange" name="is_exchange">
                                <label class="form-check-label" for="is_exchange">This is an item exchange</label>
                            </div>
                        </div>
                        <div class="col-md-4" id="exchange_rate_group" style="display:none;">
                            <label for="exchange_rate" class="form-label">Exchange Rate</label>
                            <div class="input-group">
                                <input type="number" step="0.000001" min="0.000001" class="form-control" id="exchange_rate" name="exchange_rate" placeholder="Enter exchange rate">
                                <button type="button" class="btn btn-outline-secondary" id="fetch-rate-btn">
                                    <i class="bx bx-refresh"></i>
                                </button>
                            </div>
                            <div id="rate-info" class="mt-1" style="display: none;">
                                <small class="text-info">
                                    <i class="bx bx-info-circle"></i>
                                    <span id="rate-source">Rate fetched from API</span>
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Replacement Items Section (for exchanges) -->
                    <div class="card mt-3" id="replacement_items_section" style="display: none;">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Replacement Items</h6>
                                <small class="text-muted">Items to give to customer as replacement</small>
                            </div>
                            <button type="button" class="btn btn-success btn-sm" id="add-replacement-item-btn">
                                <i class="bx bx-plus me-1"></i>Add Replacement Item
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table" id="replacement-items-table">
                                    <thead>
                                        <tr>
                                            <th width="30%">Item</th>
                                            <th width="15%">Quantity</th>
                                            <th width="15%">Unit Price</th>
                                            <th width="15%">VAT</th>
                                            <th width="15%">Line Total</th>
                                            <th width="10%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="replacement-items-tbody"></tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="5" class="text-end"><strong>Replacement Subtotal:</strong></td>
                                            <td><strong id="replacement-subtotal">0.00</strong></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="replacement_subtotal" id="replacement-subtotal-input" value="0">
                                        <tr id="replacement-vat-row" style="display: none;">
                                            <td colspan="5" class="text-end"><strong>Replacement VAT Amount:</strong></td>
                                            <td><strong id="replacement-vat-amount">0.00</strong></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="replacement_vat_amount" id="replacement-vat-amount-input" value="0">
                                        <tr>
                                            <td colspan="5" class="text-end"><strong>Replacement Total:</strong></td>
                                            <td><strong id="replacement-total">0.00</strong></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="replacement_total" id="replacement-total-input" value="0">
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Returned Items</h6>
                                <small class="text-muted">Items being returned by customer</small>
                            </div>
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
                                            <th width="15%">Unit Price</th>
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
                                <textarea class="form-control" id="notes" name="notes" rows="4" placeholder="Additional notes for this credit note..."></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="terms_conditions" class="form-label">Terms & Conditions</label>
                                <textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="4" placeholder="Terms and conditions..."></textarea>
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
                        <a href="{{ route('sales.credit-notes.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="bx bx-check me-1"></i>Create Credit Note
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
                                    data-unit="{{ $item->unit_of_measure }}"
                                    data-vat-rate="18"
                                    data-vat-type="inclusive">
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
                                <option value="expired">Expired</option>
                                <option value="scrap">Scrap</option>
                                <option value="refurbish">Refurbish</option>
                            </select>
                        </div>
                    </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="modal_notes" class="form-label">Line Notes</label>
                                <input type="text" class="form-control" id="modal_notes" placeholder="e.g., 'Expired on 2025-01-15' or 'Customer complaint'">
                                <small class="text-muted">Add specific details about why this item is being returned</small>
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

    // Get functional currency for exchange rate calculations
    const functionalCurrency = '{{ \App\Models\SystemSetting::getValue("functional_currency", auth()->user()->company->functional_currency ?? "TZS") }}';
    
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
            $('#exchange_rate_group').show();
            $('#exchange_rate').prop('required', true);
            fetchExchangeRate(selectedCurrency);
        } else {
            $('#exchange_rate_group').hide();
            $('#exchange_rate').prop('required', false);
            $('#exchange_rate').val('1.000000');
            $('#rate-info').hide();
        }
    }
    
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
        const rateInput = $('#exchange_rate');
        const originalBtnHtml = btn.html();
        
        btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i>');
        rateInput.prop('disabled', true);
        
        $.ajax({
            url: '{{ route("accounting.fx-rates.get-rate") }}',
            method: 'GET',
            data: {
                from_currency: currency,
                to_currency: functionalCurrency,
                date: new Date().toISOString().split('T')[0],
                rate_type: 'spot'
            },
            success: function(response) {
                if (response.success && response.rate) {
                    const rate = parseFloat(response.rate);
                    rateInput.val(rate.toFixed(6));
                    $('#rate-source').text(`Rate fetched: 1 ${currency} = ${rate.toFixed(6)} ${functionalCurrency}`);
                    $('#rate-info').show();
                }
            },
            error: function(xhr) {
                console.error('Failed to fetch exchange rate:', xhr);
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
                });
            },
            complete: function() {
                btn.prop('disabled', false).html(originalBtnHtml);
                rateInput.prop('disabled', false);
            }
        });
    }
    
    // Original currency change handler (for backward compatibility)
    $('#currency').on('change', function() {
        if ($(this).val() !== functionalCurrency) {
            $('#exchange_rate_group').show();
        } else {
            $('#exchange_rate_group').hide();
            $('#exchange_rate').val('');
        }
    });

    // Show/hide bank account selection when refund_now is checked
    $('#refund_now').on('change', function() {
        if ($(this).is(':checked')) {
            $('#bank_account_group').show();
            $('#bank_account_id').prop('required', true);
        } else {
            $('#bank_account_group').hide();
            $('#bank_account_id').prop('required', false).val('');
        }
    });

    // Show/hide replacement items section when exchange is checked
    $('#is_exchange').on('change', function() {
        if ($(this).is(':checked')) {
            $('#replacement_items_section').show();
            // Auto-check return to stock for exchanges
            $('#return_to_stock').prop('checked', true);
        } else {
            $('#replacement_items_section').hide();
            // Clear replacement items
            $('#replacement-items-tbody').empty();
            calculateReplacementTotals();
        }
    });

    // When customer changes, we can optionally filter invoices (progressive enhancement)
    $('#customer_id').on('change', function() {
        const customerId = $(this).val();
        const $refSelect = $('#reference_invoice_id');
        $('#sales_invoice_id').val('');
        $refSelect.prop('disabled', true);
        $refSelect.html('<option value="">Select Invoice (Optional)</option>').trigger('change');
        if (!customerId) {
            $refSelect.html('<option value="">Select Invoice (Optional)</option>').prop('disabled', false).trigger('change');
            return;
        }
        const url = `{{ route('sales.credit-notes.customer-invoices', ':customerId') }}`.replace(':customerId', customerId);
        $.get(url)
            .done(function(invoices) {
                let options = '<option value="">Select Invoice (Optional)</option>';
                invoices.forEach(inv => {
                    const label = `${inv.invoice_number ?? ('INV-' + inv.id)} - ${inv.status} - ${new Date(inv.invoice_date).toLocaleDateString()}`;
                    options += `<option value="${inv.id}">${label}</option>`;
                });
                $refSelect.html(options).prop('disabled', false).trigger('change');
            })
            .fail(function() {
                $refSelect.html('<option value="">Failed to load invoices</option>').prop('disabled', false).trigger('change');
            });
    });

    // Auto-fetch invoices if a customer is already selected (e.g., when returning to the form)
    if ($('#customer_id').val()) {
        $('#customer_id').trigger('change');
    }

    // Prefill from Copy To (source invoice)
    @if(isset($sourceInvoice) && $sourceInvoice)
        $('#customer_id').val('{{ $sourceInvoice->customer_id }}').trigger('change');
        setTimeout(function() {
            if ($('#reference_invoice_id option[value="{{ $sourceInvoice->id }}"]').length) {
                $('#reference_invoice_id').val('{{ $sourceInvoice->id }}').trigger('change');
            }
        }, 500);
    @endif

    // Sync selected reference invoice to hidden sales_invoice_id for scenarios that require it
    $('#reference_invoice_id').on('change', function() {
        const invoiceId = $(this).val() || '';
        $('#sales_invoice_id').val(invoiceId);
        if (invoiceId) {
            loadInvoiceItems(invoiceId);
            loadInvoiceDetails(invoiceId);
        } else {
            // Clear items if no invoice selected
            $('#items-tbody').empty();
            itemCounter = 0;
            calculateTotals();
        }
    });

    function loadInvoiceItems(invoiceId) {
        const url = `{{ route('sales.credit-notes.invoice-items', ':invoiceId') }}`.replace(':invoiceId', invoiceId);
        // Disable UI while loading
        $('#reference_invoice_id').prop('disabled', true);
        $('#add-item').prop('disabled', true);
        $('#items-tbody').html(`<tr><td colspan="6"><span class="text-info"><i class="bx bx-loader bx-spin me-1"></i>Loading invoice items...</span></td></tr>`);

        $.get(url)
            .done(function(items) {
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

    function loadInvoiceDetails(invoiceId) {
        const url = `{{ route('sales.credit-notes.invoice-details', ':invoiceId') }}`.replace(':invoiceId', invoiceId);
        
        $.get(url)
            .done(function(response) {
                if (response.default_warehouse) {
                    // Set the warehouse dropdown to the invoice's default warehouse
                    $('#warehouse_id').val(response.default_warehouse.id).trigger('change');
                }
                
                // Set discount amount if invoice has discount
                if (response.discount_amount && response.discount_amount > 0) {
                    $('#discount_amount').val(response.discount_amount);
                }
                
                // Update totals display with invoice data
                if (response.subtotal) {
                    $('#subtotal').text(parseFloat(response.subtotal).toFixed(2));
                    $('#subtotal-input').val(response.subtotal);
                }
                
                if (response.vat_amount && response.vat_amount > 0) {
                    $('#vat-row').show();
                    $('#vat-amount').text(parseFloat(response.vat_amount).toFixed(2));
                    $('#vat-amount-input').val(response.vat_amount);
                }
                
                if (response.total_amount) {
                    $('#total-amount').text(parseFloat(response.total_amount).toFixed(2));
                    $('#total-amount-input').val(response.total_amount);
                }
                
                // Set default VAT type and rate from the selected invoice
                if (response.vat_type) {
                    $('#modal_vat_type').val(response.vat_type);
                }
                if (response.vat_rate) {
                    $('#modal_vat_rate').val(response.vat_rate);
                }
            })
            .fail(function(xhr, status, error) {
                console.log('Failed to load invoice details:', error);
            });
    }

    function appendInvoiceItemRow(item) {
        // Compute line total according to current VAT settings
        const quantity = parseFloat(item.quantity) || 0;
        const unitPrice = parseFloat(item.unit_price) || 0;
        const vatType = item.vat_type || 'no_vat';
        const vatRate = parseFloat(item.vat_rate) || 0;

        let subtotal = quantity * unitPrice;
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
                    <input type="number" class="form-control item-price" name="items[${itemCounter}][unit_price]" value="${unitPrice}" step="0.01" min="0" data-row="${itemCounter}">
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
        itemCounter++;
    }

    $('#add-item').click(function() {
        $('#itemModal').modal('show');
        resetModalForm();
    });

    // Add replacement item functionality
    $('#add-replacement-item-btn').click(function() {
        $('#itemModal').modal('show');
        resetModalForm();
        // Mark this as replacement item
        $('#itemModal').data('is-replacement', true);
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

    // Event handlers for replacement items
    $(document).on('input', '.replacement-item-quantity, .replacement-item-price', function() {
        calculateReplacementTotals();
    });

    $(document).on('click', '.remove-replacement-item', function() {
        $(this).closest('tr').remove();
        calculateReplacementTotals();
    });

    $('#discount_amount').on('input', function() { calculateTotals(); });

    $('#credit-note-form').submit(function(e) {
        e.preventDefault();
        if ($('#items-tbody tr').length === 0) {
            Swal.fire('Error', 'Please add at least one item', 'error');
            return;
        }
        
        // Validate bank account selection if refund_now is checked
        if ($('#refund_now').is(':checked') && !$('#bank_account_id').val()) {
            Swal.fire('Error', 'Please select a bank account for the refund', 'error');
            return;
        }

        // Validate exchange requirements
        if ($('#is_exchange').is(':checked')) {
            if ($('#items-tbody tr').length === 0) {
                Swal.fire('Error', 'Please add at least one returned item for the exchange', 'error');
                return;
            }
            if ($('#replacement-items-tbody tr').length === 0) {
                Swal.fire('Error', 'Please add at least one replacement item for the exchange', 'error');
                return;
            }
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
                    Swal.fire({ title: 'Success', text: response.message || 'Credit note created successfully!', icon: 'success' }).then(() => {
                        if (response.redirect_url) { window.location.href = response.redirect_url; }
                        else { window.location.href = '{{ route('sales.credit-notes.index') }}'; }
                    });
                } else {
                    Swal.fire('Error', response.message || 'Failed to create credit note', 'error');
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
                submitBtn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Create Credit Note');
            }
        });
    });

    function resetModalForm() {
        $('#modal_item_id').val('').trigger('change');
        $('#modal_quantity').val(1);
        $('#modal_unit_price').val('');
        $('#modal_vat_rate').val(18);
        $('#modal_vat_type').val('inclusive');
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
        const unitPrice = parseFloat($('#modal_unit_price').val()) || 0;
        const vatRate = parseFloat($('#modal_vat_rate').val()) || 0;
        const vatType = $('#modal_vat_type').val();
        const notes = $('#modal_notes').val();
        const returnCondition = $('#modal_return_condition').val();

        if (!itemId || quantity <= 0 || unitPrice < 0) {
            Swal.fire('Error', 'Please fill in all required fields correctly', 'error');
            return;
        }

        const availableStock = selected.data('stock');
        if ($('#return_to_stock').is(':checked') && quantity > availableStock) {
            Swal.fire('Error', `Insufficient stock to return for ${itemName}. Available: ${availableStock}, Requested: ${quantity}`, 'error');
            return;
        }

        // Compute total similar to modal
        let subtotal = quantity * unitPrice;
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
                    <input type="number" class="form-control item-price" name="items[${itemCounter}][unit_price]" value="${unitPrice}" step="0.01" min="0" data-row="${itemCounter}">
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

        const isReplacement = $('#itemModal').data('is-replacement') || false;
        
        if (isReplacement) {
            // Create replacement row without return_condition field
            const replacementRow = `
                <tr>
                    <td>
                        <input type="hidden" name="replacement_items[${replacementItemCounter}][inventory_item_id]" value="${itemId}">
                        <input type="hidden" name="replacement_items[${replacementItemCounter}][item_name]" value="${itemName}">
                        <input type="hidden" name="replacement_items[${replacementItemCounter}][item_code]" value="${itemCode}">
                        <input type="hidden" name="replacement_items[${replacementItemCounter}][unit_of_measure]" value="${selected.data('unit') || ''}">
                        <input type="hidden" name="replacement_items[${replacementItemCounter}][vat_type]" value="${vatType}">
                        <input type="hidden" name="replacement_items[${replacementItemCounter}][vat_rate]" value="${vatRate}">
                        <input type="hidden" name="replacement_items[${replacementItemCounter}][notes]" value="${notes}">
                        <div class="fw-bold">${itemName}</div>
                        <small class="text-muted">${itemCode || ''}</small>
                    </td>
                    <td>
                        <input type="number" class="form-control replacement-item-quantity" name="replacement_items[${replacementItemCounter}][quantity]" value="${quantity}" step="0.01" min="0.01" data-row="${replacementItemCounter}">
                    </td>
                    <td>
                        <input type="number" class="form-control replacement-item-price" name="replacement_items[${replacementItemCounter}][unit_price]" value="${unitPrice}" step="0.01" min="0" data-row="${replacementItemCounter}">
                    </td>
                    <td>
                        <small class="text-muted">${vatType === 'no_vat' ? 'No VAT' : (vatRate + '%')}</small>
                    </td>
                    <td>
                        <span class="replacement-item-total">${lineTotal.toFixed(2)}</span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-outline-danger btn-sm remove-replacement-item"><i class="bx bx-trash"></i></button>
                    </td>
                </tr>`;
            
            $('#replacement-items-tbody').append(replacementRow);
            replacementItemCounter++;
            calculateReplacementTotals();
        } else {
            $('#items-tbody').append(row);
            itemCounter++;
            calculateTotals();
        }
        
        $('#itemModal').modal('hide');
        $('#itemModal').data('is-replacement', false);
    }

    function updateRowTotal(row) {
        const quantity = parseFloat($(`input[name="items[${row}][quantity]"]`).val()) || 0;
        const unitPrice = parseFloat($(`input[name="items[${row}][unit_price]"]`).val()) || 0;
        const vatType = $(`input[name="items[${row}][vat_type]"]`).val();
        const vatRate = parseFloat($(`input[name="items[${row}][vat_rate]"]`).val()) || 0;

        const lineTotal = quantity * unitPrice;
        let vatAmount = 0;
        let netAmount = 0;

        // Calculate VAT and net amount based on type
        if (vatType === 'inclusive') {
            vatAmount = lineTotal * (vatRate / (100 + vatRate));
            netAmount = lineTotal - vatAmount; // Net amount = gross - VAT
        } else if (vatType === 'exclusive') {
            vatAmount = lineTotal * (vatRate / 100);
            netAmount = lineTotal; // For exclusive, unit price is already net
        } else {
            // No VAT
            vatAmount = 0;
            netAmount = lineTotal;
        }

        // Update the line total display (this should show the total including VAT)
        let displayTotal = 0;
        if (vatType === 'exclusive') {
            displayTotal = netAmount + vatAmount;
        } else {
            displayTotal = lineTotal; // For inclusive, lineTotal already includes VAT
        }

        $(`.item-total`).eq(row).text(displayTotal.toFixed(2));
    }

    function calculateTotals() {
        let subtotal = 0;
        let vatAmount = 0;

        $('#items-tbody tr').each(function(index) {
            const quantity = parseFloat($(this).find('.item-quantity').val()) || 0;
            const unitPrice = parseFloat($(this).find('.item-price').val()) || 0;
            const vatType = $(this).find('input[name*="[vat_type]"]').val();
            const vatRate = parseFloat($(this).find('input[name*="[vat_rate]"]').val()) || 0;

            const lineTotal = quantity * unitPrice;
            let rowVatAmount = 0;
            let rowNetAmount = 0;

            // Calculate VAT and net amount based on type
            if (vatType === 'inclusive') {
                rowVatAmount = lineTotal * (vatRate / (100 + vatRate));
                rowNetAmount = lineTotal - rowVatAmount; // Net amount = gross - VAT
            } else if (vatType === 'exclusive') {
                rowVatAmount = lineTotal * (vatRate / 100);
                rowNetAmount = lineTotal; // For exclusive, unit price is already net
            } else {
                // No VAT
                rowVatAmount = 0;
                rowNetAmount = lineTotal;
            }

            subtotal += rowNetAmount; // Add net amount to subtotal
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

    function calculateReplacementTotals() {
        let subtotal = 0;
        let vatAmount = 0;

        $('#replacement-items-tbody tr').each(function(index) {
            const quantity = parseFloat($(this).find('.replacement-item-quantity').val()) || 0;
            const unitPrice = parseFloat($(this).find('.replacement-item-price').val()) || 0;
            const vatType = $(this).find('input[name*="[vat_type]"]').val();
            const vatRate = parseFloat($(this).find('input[name*="[vat_rate]"]').val()) || 0;

            const lineTotal = quantity * unitPrice;
            let rowVatAmount = 0;
            let rowNetAmount = 0;

            // Calculate VAT and net amount based on type
            if (vatType === 'inclusive') {
                rowVatAmount = lineTotal * (vatRate / (100 + vatRate));
                rowNetAmount = lineTotal - rowVatAmount; // Net amount = gross - VAT
            } else if (vatType === 'exclusive') {
                rowVatAmount = lineTotal * (vatRate / 100);
                rowNetAmount = lineTotal; // For exclusive, unit price is already net
            } else {
                // No VAT
                rowVatAmount = 0;
                rowNetAmount = lineTotal;
            }

            subtotal += rowNetAmount; // Add net amount to subtotal
            vatAmount += rowVatAmount;
        });

        const totalAmount = subtotal + vatAmount;

        $('#replacement-subtotal').text(subtotal.toFixed(2));
        $('#replacement-subtotal-input').val(subtotal);
        $('#replacement-vat-amount').text(vatAmount.toFixed(2));
        $('#replacement-vat-amount-input').val(vatAmount);
        $('#replacement-total').text(totalAmount.toFixed(2));
        $('#replacement-total-input').val(totalAmount);

        if (vatAmount > 0) {
            $('#replacement-vat-row').show();
        } else {
            $('#replacement-vat-row').hide();
        }
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
    let replacementItemCounter = 0;
});
</script>
@endpush
@endsection
