@extends('layouts.main')

@section('title', 'Import Sales Invoice Items from CSV')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-store'],
            ['label' => 'Sales Invoices', 'url' => route('sales.invoices.index'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Import from CSV', 'url' => '#', 'icon' => 'bx bx-upload']
        ]" />
        
        <h6 class="mb-0 text-uppercase">{{ isset($isEditMode) && $isEditMode ? 'UPDATE SALES INVOICE ITEMS FROM CSV' : 'IMPORT SALES INVOICE ITEMS FROM CSV' }}</h6>
        <hr />
        
        <div class="card">
            <div class="card-body">
                <form id="import-form" method="POST" action="{{ route('sales.invoices.import-from-csv') }}" enctype="multipart/form-data">
                    @csrf
                    @if(isset($prefillData['invoice_id']))
                        <input type="hidden" name="invoice_id" value="{{ $prefillData['invoice_id'] }}">
                    @endif
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Customer <span class="text-danger">*</span></label>
                            <select name="customer_id" class="form-select select2-single" required>
                                <option value="">Select customer</option>
                                @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ old('customer_id', $prefillData['customer_id'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                            <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', $prefillData['invoice_date'] ?? now()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Due Date <span class="text-danger">*</span></label>
                            <input type="date" name="due_date" id="due_date" class="form-control" value="{{ old('due_date', $prefillData['due_date'] ?? now()->addMonth()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Payment Terms <span class="text-danger">*</span></label>
                            <select name="payment_terms" class="form-select" required>
                                <option value="immediate" {{ old('payment_terms', $prefillData['payment_terms'] ?? 'net_30') == 'immediate' ? 'selected' : '' }}>Immediate</option>
                                <option value="net_15" {{ old('payment_terms', $prefillData['payment_terms'] ?? 'net_30') == 'net_15' ? 'selected' : '' }}>Net 15</option>
                                <option value="net_30" {{ old('payment_terms', $prefillData['payment_terms'] ?? 'net_30') == 'net_30' ? 'selected' : '' }}>Net 30</option>
                                <option value="net_45" {{ old('payment_terms', $prefillData['payment_terms'] ?? 'net_30') == 'net_45' ? 'selected' : '' }}>Net 45</option>
                                <option value="net_60" {{ old('payment_terms', $prefillData['payment_terms'] ?? 'net_30') == 'net_60' ? 'selected' : '' }}>Net 60</option>
                                <option value="custom" {{ old('payment_terms', $prefillData['payment_terms'] ?? 'net_30') == 'custom' ? 'selected' : '' }}>Custom</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Payment Days <span class="text-danger">*</span></label>
                            <input type="number" name="payment_days" class="form-control" value="{{ old('payment_days', $prefillData['payment_days'] ?? '30') }}" min="0" required>
                        </div>
                    </div>

                    <div class="row mb-3">
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
                                                    {{ old('currency', $prefillData['currency'] ?? $functionalCurrency) == $currency->currency_code ? 'selected' : '' }}>
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
                                           value="{{ old('exchange_rate', $prefillData['exchange_rate'] ?? '1.000000') }}" step="0.000001" min="0.000001" placeholder="1.000000">
                                    <button type="button" class="btn btn-outline-secondary" id="fetch-rate-btn">
                                        <i class="bx bx-refresh"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Rate relative to TZS</small>
                                <div id="rate-info" class="mt-1" style="display: none;">
                                    <small class="text-info">
                                        <i class="bx bx-info-circle"></i>
                                        <span id="rate-source">Rate fetched from API</span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="4" 
                                          placeholder="Additional notes for this invoice...">{{ old('notes', $prefillData['notes'] ?? '') }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="terms_conditions" class="form-label">Terms & Conditions</label>
                                <textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="4" 
                                          placeholder="Terms and conditions...">{{ old('terms_conditions', $prefillData['terms_conditions'] ?? '') }}</textarea>
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
                                @if(isset($isEditMode) && $isEditMode && isset($invoice) && !empty($invoice->attachment))
                                    <div class="mt-2">
                                        <a href="{{ asset('storage/' . $invoice->attachment) }}" target="_blank">
                                            <i class="bx bx-link-external me-1"></i>View current attachment
                                        </a>
                                    </div>
                                @endif
                                <small class="text-muted">Upload a PDF or image file (max 5MB).</small>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="csv_file" class="form-label">CSV File <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv,.txt" required>
                                <small class="text-muted">Upload the CSV file containing sales invoice items. Maximum file size: 10MB</small>
                            </div>
                        </div>
                    </div>

                    @if(isset($isEditMode) && $isEditMode)
                        <div class="alert alert-warning">
                            <strong><i class="bx bx-info-circle"></i> Note:</strong> This will replace all existing items in the invoice. Existing items will be deleted before importing new ones.
                        </div>
                    @endif
                    <div class="alert alert-info">
                        <h6 class="fw-bold mb-2"><i class="bx bx-info-circle"></i> CSV Format Requirements:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Required Columns:</strong>
                                <ul class="mb-0 small">
                                    <li><code>Item Name</code> - Name of the item</li>
                                    <li><code>Quantity</code> - Quantity (must be > 0)</li>
                                    <li><code>Unit Price</code> - Unit price (must be > 0)</li>
                                    <li><code>VAT Type</code> - no_vat, inclusive, or exclusive</li>
                                    <li><code>VAT Rate</code> - VAT rate percentage (0-100)</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <strong>Optional Columns:</strong>
                                <ul class="mb-0 small">
                                    <li><code>Inventory Item ID</code> - Inventory item ID</li>
                                    <li><code>Discount Type</code> - percentage or fixed</li>
                                    <li><code>Discount Rate</code> - Discount rate</li>
                                    <li><code>Notes</code> - Additional notes</li>
                                </ul>
                            </div>
                        </div>
                        <hr class="my-2">
                        <strong>Note:</strong> Column names are case-insensitive. The invoice will be created and items will be imported immediately.
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2 justify-content-end">
                        @if(isset($isEditMode) && $isEditMode && isset($invoice))
                            <a href="{{ route('sales.invoices.edit', $invoice->encoded_id) }}" class="btn btn-outline-secondary">
                                <i class="bx bx-x me-1"></i>Cancel
                            </a>
                        @else
                            <a href="{{ route('sales.invoices.create') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-x me-1"></i>Cancel
                            </a>
                        @endif
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="bx bx-upload me-1"></i>{{ isset($isEditMode) && $isEditMode ? 'Update Invoice' : 'Import CSV' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Get functional currency for exchange rate calculations
    const functionalCurrency = '{{ \App\Models\SystemSetting::getValue("functional_currency", auth()->user()->company->functional_currency ?? "TZS") }}';
    
    // Function to get current exchange rate
    function getCurrentExchangeRate() {
        const rate = parseFloat($('#exchange_rate').val()) || 1.000000;
        return rate;
    }
    
    // Function to get current invoice currency
    function getCurrentInvoiceCurrency() {
        return $('#currency').val() || functionalCurrency;
    }
    
    // Handle currency change
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
            // Auto-fetch exchange rate when currency changes
            const invoiceDate = $('#invoice_date').val();
            fetchExchangeRate(selectedCurrency, invoiceDate);
        } else {
            $('#exchange_rate').prop('required', false);
            $('#exchange_rate').val('1.000000');
            $('#rate-info').hide();
        }
    }
    
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
        
        // Show loading state
        btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i>');
        rateInput.prop('disabled', true);
        
        // Use the FX rates API endpoint with invoice date
        $.ajax({
            url: '{{ route("accounting.fx-rates.get-rate") }}',
            method: 'GET',
            data: {
                from_currency: currency,
                to_currency: functionalCurrency,
                date: invoiceDate,
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
                        title: 'Rate updated: 1 ' + currency + ' = ' + rateFixed + ' ' + functionalCurrency
                    });
                } else {
                    console.warn('Exchange rate API returned unexpected format:', response);
                }
            },
            error: function(xhr) {
                console.error('Failed to fetch exchange rate:', xhr);
                Swal.fire({
                    icon: 'warning',
                    title: 'Rate Fetch Failed',
                    text: 'Please manually enter the exchange rate or add it to FX RATES MANAGEMENT.',
                    timer: 3000,
                    showConfirmButton: false
                });
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
                rateInput.prop('disabled', false);
            }
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

    // Update due date when invoice date changes
    $('#invoice_date').on('change', function() {
        const invoiceDate = $(this).val();
        if (invoiceDate) {
            const dueDate = new Date(invoiceDate);
            dueDate.setMonth(dueDate.getMonth() + 1);
            $('#due_date').val(dueDate.toISOString().split('T')[0]);
        }
    });

    // Form submission with AJAX to handle JSON response
    $('#import-form').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $('#submit-btn');
        const originalHtml = submitBtn.html();
        const formData = new FormData(this);
        
        submitBtn.prop('disabled', true).html('<i class="bx bx-loader bx-spin me-1"></i>Importing...');
        
        $.ajax({
            url: $(this).attr('action'),
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
                        if (response.redirect_url) {
                            window.location.href = response.redirect_url;
                        } else {
                            window.close(); // Close the tab if no redirect
                        }
                    });
                } else {
                    Swal.fire('Error', response.message || 'Import failed', 'error');
                    submitBtn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function(xhr) {
                const errorMessage = xhr.responseJSON?.message || 'Import failed. Please try again.';
                Swal.fire('Error', errorMessage, 'error');
                submitBtn.prop('disabled', false).html(originalHtml);
            }
        });
    });
});
</script>
@endpush
