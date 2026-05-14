@extends('layouts.main')

@section('title', 'Edit Receipt - Invoice #' . $invoice->invoice_number)

@section('content')
@php
    // Get invoice currency - check if it's stored in the database
    $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', auth()->user()->company->functional_currency ?? 'TZS');
    
    // Get invoice currency - prioritize stored currency, but ensure it's valid
    $invoiceCurrency = $invoice->currency;
    
    // If currency is null, empty, or not set, use functional currency
    if (empty($invoiceCurrency) || !$invoiceCurrency) {
        $invoiceCurrency = $functionalCurrency;
    }
    
    // Ensure currency is uppercase for consistency
    $invoiceCurrency = strtoupper(trim($invoiceCurrency));
    
    // Use receipt currency if available, otherwise use invoice currency
    $displayCurrency = $receipt->currency ?? $invoiceCurrency;
    if (empty($displayCurrency)) {
        $displayCurrency = $invoiceCurrency;
    }
    $displayCurrency = strtoupper(trim($displayCurrency));
@endphp
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-store'],
            ['label' => 'Sales Invoices', 'url' => route('sales.invoices.index'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Invoice #' . $invoice->invoice_number, 'url' => route('sales.invoices.show', $invoice->encoded_id), 'icon' => 'bx bx-file'],
            ['label' => 'Edit Receipt', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        
        <h6 class="mb-0 text-uppercase">EDIT RECEIPT</h6>
        <hr />

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Receipt Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('sales.invoices.receipt.update', $receipt->encoded_id) }}">
                            @csrf
                            @method('PUT')
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="amount" class="form-label">Payment Amount <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">{{ $displayCurrency }}</span>
                                            <input type="number" class="form-control" id="amount" name="amount" 
                                                   value="{{ old('amount', $receipt->amount) }}" 
                                                   min="0.01" max="{{ $invoice->balance_due + $receipt->amount }}" step="0.01" required>
                                        </div>
                                        <small class="text-muted">
                                            Maximum allowed: {{ number_format($invoice->balance_due + $receipt->amount, 2) }} {{ $displayCurrency }}
                                            (Balance due: {{ number_format($invoice->balance_due, 2) }} + Current receipt: {{ number_format($receipt->amount, 2) }})
                                        </small>
                                        @error('amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="payment_date" name="payment_date" 
                                               value="{{ old('payment_date', $receipt->date->toDateString()) }}" required>
                                        @error('payment_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                        <select class="form-select" id="payment_method" name="payment_method" required>
                                            <option value="">Select Payment Method</option>
                                            <option value="bank" {{ old('payment_method', $receipt->bank_account_id ? 'bank' : 'cash_deposit') == 'bank' ? 'selected' : '' }}>Bank Payment</option>
                                            <option value="cash_deposit" {{ old('payment_method', $receipt->bank_account_id ? 'bank' : 'cash_deposit') == 'cash_deposit' ? 'selected' : '' }}>Cash Deposit</option>
                                        </select>
                                        @error('payment_method') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3" id="bank_account_section" style="display:none;">
                                        <label for="bank_account_id" class="form-label">Bank Account <span class="text-danger">*</span></label>
                                        <select class="form-select select2-single" id="bank_account_id" name="bank_account_id">
                                            <option value="">Select Bank Account</option>
                                            @foreach($bankAccounts as $account)
                                                <option value="{{ $account->id }}" {{ old('bank_account_id', $receipt->bank_account_id) == $account->id ? 'selected' : '' }}>
                                                    {{ $account->name }} ({{ $account->account_number }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('bank_account_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="mb-3" id="cash_deposit_section" style="display:none;">
                                        <label for="cash_deposit_id" class="form-label">Customer Account</label>
                                        <select class="form-select select2-single" id="cash_deposit_id" name="cash_deposit_id">
                                            <option value="">Select Customer Account</option>
                                        </select>
                                        <small class="text-muted">Customer cash deposit accounts and balance</small>
                                        <div id="no_account_message" class="text-danger mt-2" style="display: none;">
                                            <i class="bx bx-info-circle"></i> No account available for this customer
                                        </div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            @if($invoice->currency && $invoice->currency !== 'TZS')
                            <!-- Payment Exchange Rate Section (for Foreign Currency Invoices) -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_exchange_rate" class="form-label">
                                            <i class="bx bx-transfer me-1"></i>Payment Exchange Rate
                                        </label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="payment_exchange_rate" name="payment_exchange_rate" 
                                                   value="{{ old('payment_exchange_rate', number_format($receipt->exchange_rate ?? $invoice->exchange_rate ?? 1, 6, '.', '')) }}" 
                                                   step="0.000001" min="0.000001" placeholder="1.000000">
                                            <button type="button" class="btn btn-outline-secondary" id="fetch-payment-rate-btn">
                                                <i class="bx bx-refresh"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">
                                            Invoice Rate: {{ number_format($invoice->exchange_rate ?? 1, 6) }} | 
                                            If different, FX gain/loss will be recalculated
                                        </small>
                                        @error('payment_exchange_rate') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-info py-2 mt-4">
                                        <small>
                                            <i class="bx bx-info-circle me-1"></i>
                                            <strong>Invoice Currency:</strong> {{ $invoice->currency }}<br>
                                            <strong>Invoice Exchange Rate:</strong> {{ number_format($invoice->exchange_rate ?? 1, 6) }}<br>
                                            <strong>Payment Amount in TZS:</strong> <span id="payment_amount_tzs">-</span><br>
                                            <strong>FX Gain/Loss:</strong> <span id="fx_gain_loss">-</span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Withholding Tax Section (only for bank payments) -->
                            <div class="row" id="wht_section" style="display: none;">
                                <div class="col-md-12">
                                    <div class="card border-info">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0 fw-bold">
                                                <i class="bx bx-calculator me-2"></i>Withholding Tax (WHT)
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-3">
                                                <div class="col-md-12">
                                                    <div class="alert alert-info py-2 mb-3">
                                                        <small>
                                                            <i class="bx bx-info-circle me-1"></i>
                                                            <strong>VAT Mode:</strong> {{ $invoice->getVatMode() }} | 
                                                            <strong>VAT Rate:</strong> {{ number_format($invoice->getVatRate(), 2) }}%
                                                            <br>
                                                            <span class="text-muted">Using VAT settings from invoice creation</span>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="wht_treatment" class="form-label fw-bold">
                                                        WHT Treatment
                                                    </label>
                                                    <select class="form-select @error('wht_treatment') is-invalid @enderror"
                                                        id="wht_treatment" name="wht_treatment">
                                                        <option value="EXCLUSIVE" {{ old('wht_treatment', $receipt->wht_treatment ?? 'EXCLUSIVE') == 'EXCLUSIVE' ? 'selected' : '' }}>Exclusive</option>
                                                        <option value="INCLUSIVE" {{ old('wht_treatment', $receipt->wht_treatment ?? '') == 'INCLUSIVE' ? 'selected' : '' }}>Inclusive</option>
                                                        <option value="NONE" {{ old('wht_treatment', $receipt->wht_treatment ?? '') == 'NONE' ? 'selected' : '' }}>None</option>
                                                    </select>
                                                    @error('wht_treatment')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                    <small class="form-text text-muted">
                                                        <strong>Exclusive:</strong> WHT deducted from base<br>
                                                        <strong>Inclusive:</strong> WHT included in total<br>
                                                        <strong>Note:</strong> Gross-Up not applicable for receipts
                                                    </small>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="wht_rate" class="form-label fw-bold">
                                                        WHT Rate (%)
                                                    </label>
                                                    <input type="number" class="form-control @error('wht_rate') is-invalid @enderror"
                                                        id="wht_rate" name="wht_rate" value="{{ old('wht_rate', $receipt->wht_rate ?? 0) }}"
                                                        step="0.01" min="0" max="100" placeholder="0.00">
                                                    @error('wht_rate')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12 mb-3">
                                                    <div class="card border-primary">
                                                        <div class="card-header bg-primary text-white py-2">
                                                            <h6 class="mb-0 fw-bold">
                                                                <i class="bx bx-calculator me-2"></i>Calculation Preview
                                                            </h6>
                                                        </div>
                                                        <div class="card-body p-3">
                                                            <div class="row g-3 align-items-center">
                                                                <div class="col-md-2 col-sm-4 col-6">
                                                                    <div class="text-center p-2 bg-light rounded">
                                                                        <small class="text-muted d-block mb-1">Total Amount</small>
                                                                        <div class="fw-bold fs-6" id="wht_total_amount">0.00</div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2 col-sm-4 col-6">
                                                                    <div class="text-center p-2 bg-light rounded">
                                                                        <small class="text-muted d-block mb-1">Base Amount</small>
                                                                        <div class="fw-bold fs-6" id="wht_base_amount">0.00</div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2 col-sm-4 col-6">
                                                                    <div class="text-center p-2 bg-light rounded">
                                                                        <small class="text-muted d-block mb-1">VAT Amount</small>
                                                                        <div class="fw-bold fs-6 text-info" id="wht_vat_amount">0.00</div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2 col-sm-4 col-6">
                                                                    <div class="text-center p-2 bg-light rounded">
                                                                        <small class="text-muted d-block mb-1">WHT Amount</small>
                                                                        <div class="fw-bold fs-6 text-danger" id="wht_amount_preview">0.00</div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2 col-sm-4 col-6">
                                                                    <div class="text-center p-3 bg-success rounded border border-success border-2">
                                                                        <small class="text-white d-block mb-2 fw-semibold">Net Receivable</small>
                                                                        <div class="fw-bold fs-5 text-white" id="wht_net_receivable" style="word-break: break-word; line-height: 1.2;">0.00</div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3" 
                                                  placeholder="Optional payment description">{{ old('description', $receipt->description) }}</textarea>
                                        @error('description') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <a href="{{ route('sales.invoices.show', $invoice->encoded_id) }}" class="btn btn-secondary">
                                        <i class="bx bx-arrow-back me-1"></i>Cancel
                                    </a>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bx bx-save me-1" data-processing-text="Updating..."></i>Update Receipt
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Invoice Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Invoice Number:</span>
                            <span class="fw-bold">{{ $invoice->invoice_number }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Customer:</span>
                            <span>{{ $invoice->customer->name }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Invoice Date:</span>
                            <span>{{ $invoice->invoice_date->format('M d, Y') }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Due Date:</span>
                            <span class="{{ $invoice->isOverdue() ? 'text-danger' : '' }}">
                                {{ $invoice->due_date->format('M d, Y') }}
                            </span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Amount:</span>
                            <span class="fw-bold">{{ number_format($invoice->total_amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Paid Amount:</span>
                            <span class="text-success">{{ number_format($invoice->paid_amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Balance Due:</span>
                            <span class="text-danger fw-bold">{{ number_format($invoice->balance_due, 2) }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span>Status:</span>
                            <span>{!! $invoice->status_badge !!}</span>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Current Receipt Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Original Amount:</span>
                            <span class="fw-bold">{{ number_format($receipt->amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Payment Date:</span>
                            <span>{{ $receipt->date->format('M d, Y') }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Payment Source:</span>
                            <span class="badge bg-primary">
                                @if($receipt->bank_account_id)
                                    {{ $receipt->bankAccount->name }}
                                @else
                                    Customer Cash Deposit
                                @endif
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Recorded By:</span>
                            <span>{{ $receipt->user->name }}</span>
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
    // FX Gain/Loss Calculation (for foreign currency invoices)
    @if($invoice->currency && $invoice->currency !== 'TZS')
    const invoiceCurrency = '{{ $invoice->currency }}';
    const invoiceExchangeRate = {{ $invoice->exchange_rate ?? 1 }};
    
    function calculateFXGainLoss() {
        const paymentAmount = parseFloat($('#amount').val()) || 0;
        const paymentExchangeRate = parseFloat($('#payment_exchange_rate').val()) || invoiceExchangeRate;
        
        if (paymentAmount > 0 && paymentExchangeRate > 0) {
            // Calculate amounts in TZS
            const invoiceAmountInTZS = paymentAmount * invoiceExchangeRate;
            const paymentAmountInTZS = paymentAmount * paymentExchangeRate;
            const fxDifference = paymentAmountInTZS - invoiceAmountInTZS;
            
            // Display payment amount in TZS
            $('#payment_amount_tzs').text(paymentAmountInTZS.toLocaleString('en-US', { 
                minimumFractionDigits: 2, 
                maximumFractionDigits: 2 
            }) + ' TZS');
            
            // Display FX gain/loss
            if (Math.abs(fxDifference) > 0.01) {
                if (fxDifference > 0) {
                    $('#fx_gain_loss').html('<span class="text-success">Gain: ' + 
                        fxDifference.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + 
                        ' TZS</span>');
                } else {
                    $('#fx_gain_loss').html('<span class="text-danger">Loss: ' + 
                        Math.abs(fxDifference).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + 
                        ' TZS</span>');
                }
            } else {
                $('#fx_gain_loss').text('No gain/loss (rates are the same)');
            }
        } else {
            $('#payment_amount_tzs').text('-');
            $('#fx_gain_loss').text('-');
        }
    }
    
    // Calculate FX gain/loss when amount or exchange rate changes
    $('#amount, #payment_exchange_rate').on('input', function() {
        calculateFXGainLoss();
    });
    
    // Initial calculation
    calculateFXGainLoss();
    
    // Fetch current exchange rate from API
    $('#fetch-payment-rate-btn').on('click', function() {
        const functionalCurrency = '{{ \App\Models\SystemSetting::getValue("functional_currency", auth()->user()->company->functional_currency ?? "TZS") }}';
        const currency = invoiceCurrency;
        
        if (!currency || currency === functionalCurrency) {
            $('#payment_exchange_rate').val('1.000000');
            calculateFXGainLoss();
            return;
        }

        const btn = $('#fetch-payment-rate-btn');
        const originalHtml = btn.html();
        const rateInput = $('#payment_exchange_rate');
        
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
                    rateInput.prop('disabled', false);
                    calculateFXGainLoss(); // Recalculate FX gain/loss with new rate
                    
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
                    rateInput.prop('disabled', false);
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
                        rateInput.prop('disabled', false);
                        calculateFXGainLoss();
                        
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: 'success',
                            title: `Rate fetched: 1 ${currency} = ${rate.toFixed(6)} ${functionalCurrency}`
                        });
                    } else {
                        rateInput.prop('disabled', false);
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
                    rateInput.prop('disabled', false);
                });
            },
            complete: function() {
                btn.html(originalHtml);
                btn.prop('disabled', false);
            }
        });
    });
    @endif
$(document).ready(function() {
    // Initialize Select2
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    function togglePaymentMethod() {
        var method = $('#payment_method').val();
        // Hide all sections first
        $('#bank_account_section, #cash_deposit_section, #wht_section').hide();
        $('#bank_account_id').removeAttr('required');

        if (method === 'bank') {
            $('#bank_account_section').show();
            $('#bank_account_id').attr('required', true);
            $('#wht_section').show(); // Show WHT section for bank payments
            // Use setTimeout to ensure DOM is ready before calculating
            setTimeout(function() {
                calculateWHT(); // Calculate WHT when showing section
            }, 100);
        } else if (method === 'cash_deposit') {
            $('#cash_deposit_section').show();
            // Load customer cash deposits
            loadCustomerCashDeposits('{{ $invoice->customer->encoded_id }}');
        }
    }

    function loadCustomerCashDeposits(customerId) {
        if (!customerId) return;
        $.ajax({
            url: `/sales/invoices/customer/${customerId}/cash-deposits`,
            type: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(response) {
                const select = $('#cash_deposit_id');
                select.empty();
                select.append('<option value="">Select Customer Account</option>');
                const defaultValue = "{{ old('cash_deposit_id') }}";
                if (response.data && response.data.length > 0) {
                    response.data.forEach(function(deposit) {
                        const value = String(deposit.id).startsWith('customer_balance') ? 'customer_balance' : deposit.id;
                        select.append(`<option value="${value}" data-balance-id="${deposit.id}">${deposit.balance_text}</option>`);
                    });
                    $('#no_account_message').hide();
                    select.prop('disabled', false);
                    if (defaultValue) {
                        select.val(defaultValue).trigger('change');
                    }
                } else {
                    select.append('<option value="">No account available for this customer</option>');
                    select.prop('disabled', true);
                    $('#no_account_message').show();
                }
            },
            error: function() {
                const select = $('#cash_deposit_id');
                select.empty();
                select.append('<option value="">Error loading accounts</option>');
            }
        });
    }

    // Get VAT mode and rate from invoice (set when invoice was created)
    const invoiceVatMode = '{{ $invoice->getVatMode() }}';
    const invoiceVatRate = parseFloat('{{ $invoice->getVatRate() }}') || 0;

    // WHT Calculation (for AR - Exclusive/Inclusive only)
    function calculateWHT() {
        const baseAmount = parseFloat($('#amount').val()) || 0;
        const treatment = $('#wht_treatment').val() || 'EXCLUSIVE';
        const rate = parseFloat($('#wht_rate').val()) || 0;
        const vatMode = invoiceVatMode;
        const vatRate = invoiceVatRate;

        // Calculate base amount based on VAT mode
        let baseAmountForWHT = baseAmount;
        let vatAmount = 0;

        if (vatMode === 'INCLUSIVE' && vatRate > 0) {
            // VAT is included in total, extract base
            baseAmountForWHT = baseAmount / (1 + (vatRate / 100));
            vatAmount = baseAmount - baseAmountForWHT;
        } else if (vatMode === 'EXCLUSIVE' && vatRate > 0) {
            // VAT is separate, base is the amount (assuming payment amount is net of VAT)
            baseAmountForWHT = baseAmount;
            vatAmount = baseAmount * (vatRate / 100);
        } else {
            // No VAT
            baseAmountForWHT = baseAmount;
            vatAmount = 0;
        }

        $('#wht_total_amount').text(baseAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#wht_base_amount').text(baseAmountForWHT.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#wht_vat_amount').text(vatAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

        if (rate <= 0 || treatment === 'NONE') {
            $('#wht_amount_preview').text('0.00');
            $('#wht_net_receivable').text(baseAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            return;
        }

        // Calculate WHT on base amount (before VAT)
        let wht = 0;
        let net = baseAmount;

        const rateDecimal = rate / 100;
        
        if (treatment === 'EXCLUSIVE') {
            wht = baseAmountForWHT * rateDecimal;
            net = baseAmount - wht;
        } else if (treatment === 'INCLUSIVE') {
            wht = baseAmountForWHT * (rateDecimal / (1 + rateDecimal));
            net = baseAmount - wht;
        }

        $('#wht_amount_preview').text(wht.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#wht_net_receivable').text(net.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    }

    $('#payment_method').on('change', togglePaymentMethod);
    
    // Initialize payment method display
    togglePaymentMethod();

    // Calculate WHT when amount, treatment, or rate changes
    $('#amount, #wht_treatment, #wht_rate').on('change input', function() {
        if ($('#payment_method').val() === 'bank') {
            calculateWHT();
        }
    });

    // Initial calculation after page load - ensure it runs after DOM is ready
    setTimeout(function() {
        if ($('#payment_method').val() === 'bank') {
            calculateWHT();
        }
    }, 200);
});
</script>
@endpush 