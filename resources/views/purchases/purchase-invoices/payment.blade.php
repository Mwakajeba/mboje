@extends('layouts.main')

@section('title', 'Record Payment - Invoice #' . $invoice->invoice_number)

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchase Management', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Purchase Invoices', 'url' => route('purchases.purchase-invoices.index'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Invoice #' . $invoice->invoice_number, 'url' => route('purchases.purchase-invoices.show', $invoice->encoded_id), 'icon' => 'bx bx-file'],
            ['label' => 'Record Payment', 'url' => '#', 'icon' => 'bx bx-money']
        ]" />
        
        <h6 class="mb-0 text-uppercase">RECORD PAYMENT</h6>
        <hr />

        @php
            $paid = (float) ($invoice->payments()->sum('amount'));
            $balanceDue = max(0, (float) $invoice->total_amount - $paid);
            
            // Get functional currency and invoice currency
            $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', auth()->user()->company->functional_currency ?? 'TZS');
            $invoiceCurrency = $invoice->currency ?? $functionalCurrency;
            $invoiceExchangeRate = $invoice->exchange_rate ?? 1.000000;
            $isForeignCurrency = ($invoiceCurrency !== $functionalCurrency && $invoiceExchangeRate != 1.000000);
        @endphp

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bx bx-money me-2"></i>Payment Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('purchases.purchase-invoices.record-payment', $invoice->encoded_id) }}">
                            @csrf
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="amount" class="form-label">Payment Amount <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">{{ $invoiceCurrency }}</span>
                                            <input type="number" class="form-control" id="amount" name="amount" 
                                                   value="{{ old('amount', $balanceDue) }}" 
                                                   min="0.01" max="{{ $balanceDue }}" step="0.01" required>
                                        </div>
                                        <small class="text-muted">Maximum amount: {{ $invoiceCurrency }} {{ number_format($balanceDue, 2) }}</small>
                                        @error('amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="date" name="date" 
                                               value="{{ old('date', now()->toDateString()) }}" required>
                                        @error('date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                        <select class="form-select" id="method" name="method" required>
                                            <option value="">Select Payment Method</option>
                                            <option value="bank" {{ old('method') == 'bank' ? 'selected' : '' }}>Bank Payment</option>
                                        </select>
                                        @error('method') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3" id="bank_account_section" style="display: none;">
                                        <label for="bank_account_id" class="form-label">Bank Account</label>
                                        <select class="form-select select2-single" id="bank_account_id" name="bank_account_id">
                                            <option value="">Select Bank Account</option>
                                            @foreach($bankAccounts as $account)
                                                <option value="{{ $account->id }}" {{ old('bank_account_id') == $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('bank_account_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Currency and Exchange Rate Section -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_currency" class="form-label">
                                            <i class="bx bx-money me-1"></i>Payment Currency
                                        </label>
                                        <select class="form-select select2-single" id="payment_currency" name="payment_currency">
                                            @if(isset($currencies) && $currencies->isNotEmpty())
                                                @foreach($currencies as $currency)
                                                    <option value="{{ $currency->currency_code }}" 
                                                            {{ old('payment_currency', $invoiceCurrency) == $currency->currency_code ? 'selected' : '' }}>
                                                        {{ $currency->currency_name ?? $currency->currency_code }} ({{ $currency->currency_code }})
                                                    </option>
                                                @endforeach
                                            @else
                                                <option value="{{ $invoiceCurrency }}" selected>{{ $invoiceCurrency }}</option>
                                            @endif
                                        </select>
                                        <small class="text-muted">Currencies from FX RATES MANAGEMENT</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_exchange_rate" class="form-label">
                                            <i class="bx bx-transfer me-1"></i>Payment Exchange Rate
                                        </label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="payment_exchange_rate" name="payment_exchange_rate" 
                                                   value="{{ old('payment_exchange_rate', number_format($invoiceExchangeRate, 6, '.', '')) }}" 
                                                   step="0.000001" min="0.000001" placeholder="1.000000">
                                            <button type="button" class="btn btn-outline-secondary" id="fetch-payment-rate-btn" title="Fetch Current Rate">
                                                <i class="bx bx-refresh"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">
                                            Invoice Rate: {{ number_format($invoiceExchangeRate, 6) }} | 
                                            If different, FX gain/loss will be calculated
                                        </small>
                                        <div id="payment-rate-info" class="mt-1" style="display: none;">
                                            <small class="text-info">
                                                <i class="bx bx-info-circle"></i>
                                                <span id="payment-rate-source">Rate fetched from FX RATES MANAGEMENT</span>
                                            </small>
                                        </div>
                                        @error('payment_exchange_rate') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>
                            @if(!empty($invoice->currency) || ($invoiceExchangeRate != 1.000000))
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <div class="alert alert-info py-2 mt-4">
                                        <small>
                                            <i class="bx bx-info-circle me-1"></i>
                                            <strong>Invoice Currency:</strong> {{ $invoiceCurrency }}<br>
                                            <strong>Invoice Exchange Rate:</strong> {{ number_format($invoiceExchangeRate, 6) }}<br>
                                            <strong>Payment Amount in {{ $functionalCurrency }}:</strong> <span id="payment_amount_tzs">-</span><br>
                                            <strong>FX Gain/Loss:</strong> <span id="fx_gain_loss">-</span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Withholding Tax Section (only for bank payments) -->
                            <div class="row" id="wht_section">
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
                                                        <option value="EXCLUSIVE" {{ old('wht_treatment', 'EXCLUSIVE') == 'EXCLUSIVE' ? 'selected' : '' }}>Exclusive</option>
                                                        <option value="INCLUSIVE" {{ old('wht_treatment') == 'INCLUSIVE' ? 'selected' : '' }}>Inclusive</option>
                                                        <option value="GROSS_UP" {{ old('wht_treatment') == 'GROSS_UP' ? 'selected' : '' }}>Gross-Up</option>
                                                        <option value="NONE" {{ old('wht_treatment') == 'NONE' ? 'selected' : '' }}>None</option>
                                                    </select>
                                                    @error('wht_treatment')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                    <small class="form-text text-muted">
                                                        <strong>Exclusive:</strong> WHT deducted from base<br>
                                                        <strong>Inclusive:</strong> WHT included in total<br>
                                                        <strong>Gross-Up:</strong> WHT added on top (supplier receives full amount)
                                                    </small>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="wht_rate" class="form-label fw-bold">
                                                        WHT Rate (%)
                                                    </label>
                                                    <input type="number" class="form-control @error('wht_rate') is-invalid @enderror"
                                                        id="wht_rate" name="wht_rate" value="{{ old('wht_rate', 0) }}"
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
                                                                        <small class="text-white d-block mb-2 fw-semibold">Net Payable</small>
                                                                        <div class="fw-bold fs-5 text-white" id="wht_net_payable" style="word-break: break-word; line-height: 1.2;">0.00</div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2 col-sm-4 col-6" id="wht_total_cost_container" style="display: none;">
                                                                    <div class="text-center p-3 bg-primary rounded border border-primary border-2">
                                                                        <small class="text-white d-block mb-2 fw-semibold">Total Cost</small>
                                                                        <div class="fw-bold fs-5 text-white" id="wht_total_cost" style="word-break: break-word; line-height: 1.2;">0.00</div>
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
                                                  placeholder="Optional payment description">{{ old('description') }}</textarea>
                                        @error('description') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <a href="{{ route('purchases.purchase-invoices.show', $invoice->encoded_id) }}" class="btn btn-secondary">
                                        <i class="bx bx-arrow-back me-1"></i>Cancel
                                    </a>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i>Record Payment
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
                            <span>Supplier:</span>
                            <span>{{ optional($invoice->supplier)->name ?? 'N/A' }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Invoice Date:</span>
                            <span>{{ optional($invoice->invoice_date)->format('M d, Y') }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Due Date:</span>
                            <span>{{ optional($invoice->due_date)->format('M d, Y') }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Amount:</span>
                            <span class="fw-bold">{{ number_format($invoice->total_amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Paid Amount:</span>
                            <span class="text-success">{{ number_format($paid, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Balance Due:</span>
                            <span class="text-danger fw-bold">{{ number_format($balanceDue, 2) }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span>Status:</span>
                            <span>{{ ucfirst($invoice->status) }}</span>
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
$(document).ready(function() {
    $('.select2-single').select2({ theme: 'bootstrap-5', width: '100%' });

    // Validate amount doesn't exceed balance due
    $('#amount').on('change', function() {
        let amount = parseFloat($(this).val()) || 0;
        let balanceDue = parseFloat('{{ $balanceDue }}');
        if (amount > balanceDue) {
            alert('Payment amount cannot exceed the balance due amount.');
            $(this).val(balanceDue);
        }
    });

    function toggleBank() {
        const selectedMethod = $('#method').val();
        if (selectedMethod === 'bank') {
            $('#bank_account_section').show();
        } else {
            $('#bank_account_section').hide();
            $('#bank_account_id').val('');
        }
    }

    $('#method').on('change', toggleBank);
    toggleBank();

    // Get VAT mode and rate from invoice (set when invoice was created)
    const invoiceVatMode = '{{ $invoice->getVatMode() }}';
    const invoiceVatRate = parseFloat('{{ $invoice->getVatRate() }}') || 0;

    // WHT Calculation (for AP - Exclusive/Inclusive/Gross-Up)
    function calculateWHT() {
        const totalAmount = parseFloat($('#amount').val()) || 0;
        const treatment = $('#wht_treatment').val() || 'EXCLUSIVE';
        const rate = parseFloat($('#wht_rate').val()) || 0;
        const vatMode = invoiceVatMode;
        const vatRate = invoiceVatRate;

        // Calculate base amount based on VAT mode
        let baseAmountForWHT = totalAmount;
        let vatAmount = 0;

        if (vatMode === 'INCLUSIVE' && vatRate > 0) {
            // VAT is included in total, extract base
            baseAmountForWHT = totalAmount / (1 + (vatRate / 100));
            vatAmount = totalAmount - baseAmountForWHT;
        } else if (vatMode === 'EXCLUSIVE' && vatRate > 0) {
            // VAT is separate, base is the amount (assuming payment amount is net of VAT)
            baseAmountForWHT = totalAmount;
            vatAmount = totalAmount * (vatRate / 100);
        } else {
            // No VAT
            baseAmountForWHT = totalAmount;
            vatAmount = 0;
        }

        $('#wht_total_amount').text(totalAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#wht_base_amount').text(baseAmountForWHT.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#wht_vat_amount').text(vatAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

        if (rate <= 0 || treatment === 'NONE') {
            $('#wht_amount_preview').text('0.00');
            $('#wht_net_payable').text(totalAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#wht_total_cost_container').hide();
            return;
        }

        // Calculate WHT on base amount (before VAT)
        let wht = 0;
        let net = baseAmountForWHT;
        let totalCost = baseAmountForWHT;

        const rateDecimal = rate / 100;
        
        if (treatment === 'EXCLUSIVE') {
            wht = baseAmountForWHT * rateDecimal;
            net = baseAmountForWHT - wht;
            totalCost = baseAmountForWHT;
        } else if (treatment === 'INCLUSIVE') {
            wht = baseAmountForWHT * (rateDecimal / (1 + rateDecimal));
            net = baseAmountForWHT - wht;
            totalCost = baseAmountForWHT;
        } else if (treatment === 'GROSS_UP') {
            wht = baseAmountForWHT * (rateDecimal / (1 - rateDecimal));
            net = baseAmountForWHT;
            totalCost = baseAmountForWHT + wht;
            $('#wht_total_cost_container').show();
        }

        // For net payable, add VAT back if VAT is exclusive
        let netPayable = net;
        if (vatMode === 'EXCLUSIVE' && vatAmount > 0) {
            netPayable = net + vatAmount;
        } else if (vatMode === 'INCLUSIVE') {
            // For inclusive, net payable is already net of WHT from the total
            netPayable = totalAmount - wht;
        }

        $('#wht_amount_preview').text(wht.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#wht_net_payable').text(netPayable.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#wht_total_cost').text(totalCost.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

        if (treatment !== 'GROSS_UP') {
            $('#wht_total_cost_container').hide();
        }
    }

    // Calculate WHT when amount, treatment, or rate changes
    $('#amount, #wht_treatment, #wht_rate').on('change input', calculateWHT);
    
    // Initial calculation
    calculateWHT();
    
    @if(!empty($invoice->currency) || ($invoiceExchangeRate != 1.000000))
    // FX Gain/Loss calculation for purchase invoices
    function calculateFXGainLoss() {
        const amount = parseFloat($('#amount').val()) || 0;
        const invoiceRate = parseFloat('{{ $invoiceExchangeRate }}');
        const paymentRate = parseFloat($('#payment_exchange_rate').val()) || invoiceRate;
        const functionalCurrency = '{{ $functionalCurrency }}';
        const invoiceCurrency = '{{ $invoiceCurrency }}';
        
        if (amount > 0 && paymentRate > 0) {
            // Calculate payment amount in functional currency (TZS)
            const paymentAmountInLCY = amount * paymentRate;
            $('#payment_amount_tzs').text(functionalCurrency + ' ' + paymentAmountInLCY.toFixed(2));
            
            if (invoiceRate > 0 && invoiceRate != paymentRate) {
                // Calculate FX difference
                // For purchase invoices: if payment rate > invoice rate, we pay more in LCY = loss
                // If payment rate < invoice rate, we pay less in LCY = gain
                const invoiceAmountInLCY = amount * invoiceRate;
                const fxDifference = paymentAmountInLCY - invoiceAmountInLCY;
                
                if (Math.abs(fxDifference) > 0.01) {
                    if (fxDifference > 0) {
                        // Loss (we pay more in LCY)
                        $('#fx_gain_loss').removeClass('text-success').addClass('text-danger')
                            .text(functionalCurrency + ' ' + fxDifference.toFixed(2) + ' (Loss)');
                    } else {
                        // Gain (we pay less in LCY)
                        $('#fx_gain_loss').removeClass('text-danger').addClass('text-success')
                            .text(functionalCurrency + ' ' + Math.abs(fxDifference).toFixed(2) + ' (Gain)');
                    }
                } else {
                    $('#fx_gain_loss').removeClass('text-success text-danger').text('-');
                }
            } else {
                $('#fx_gain_loss').removeClass('text-success text-danger').text('-');
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
    // Function to fetch payment exchange rate from FX RATES MANAGEMENT
    function fetchPaymentExchangeRate(paymentCurrency = null, paymentDate = null) {
        const functionalCurrency = '{{ $functionalCurrency }}';
        paymentCurrency = paymentCurrency || $('#payment_currency').val() || '{{ $invoiceCurrency }}';
        paymentDate = paymentDate || $('#date').val() || new Date().toISOString().split('T')[0];
        
        if (!paymentCurrency || paymentCurrency === functionalCurrency) {
            $('#payment_exchange_rate').val('1.000000');
            $('#payment-rate-info').hide();
            calculateFXGainLoss();
            return;
        }

        const btn = $('#fetch-payment-rate-btn');
        const originalHtml = btn.html();
        const rateInput = $('#payment_exchange_rate');
        
        // Show loading state
        btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i>');
        rateInput.prop('disabled', true);
        
        // Use the FX rates API endpoint with payment date
        $.ajax({
            url: '{{ route("accounting.fx-rates.get-rate") }}',
            method: 'GET',
            data: {
                from_currency: paymentCurrency,
                to_currency: functionalCurrency,
                date: paymentDate,
                rate_type: 'spot'
            },
            success: function(response) {
                if (response.success && response.rate) {
                    const rate = parseFloat(response.rate);
                    rateInput.val(rate.toFixed(6));
                    const source = response.source || 'FX RATES MANAGEMENT';
                    $('#payment-rate-source').text(`Rate from ${source} for ${paymentDate}: 1 ${paymentCurrency} = ${rate.toFixed(6)} ${functionalCurrency}`);
                    $('#payment-rate-info').show();
                    calculateFXGainLoss();
                } else {
                    fetchPaymentExchangeRateFallback(paymentCurrency, paymentDate);
                }
            },
            error: function() {
                fetchPaymentExchangeRateFallback(paymentCurrency, paymentDate);
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
                rateInput.prop('disabled', false);
            }
        });
    }
    
    // Fallback function to fetch rate from API if FX RATES MANAGEMENT doesn't have it
    function fetchPaymentExchangeRateFallback(paymentCurrency, paymentDate) {
        const rateInput = $('#payment_exchange_rate');
        const functionalCurrency = '{{ $functionalCurrency }}';
        $.get('{{ route("api.exchange-rates.rate") }}', {
            from: paymentCurrency,
            to: functionalCurrency
        })
        .done(function(response) {
            if (response.success && response.data && response.data.rate) {
                const rate = parseFloat(response.data.rate);
                rateInput.val(rate.toFixed(6));
                $('#payment-rate-source').text(`Rate fetched (fallback API) for ${paymentDate}: 1 ${paymentCurrency} = ${rate.toFixed(6)} ${functionalCurrency}`);
                $('#payment-rate-info').show();
                calculateFXGainLoss();
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
    
    // Button click handler
    $('#fetch-payment-rate-btn').on('click', function() {
        const functionalCurrency = '{{ $functionalCurrency }}';
        const paymentCurrency = $('#payment_currency').val() || '{{ $invoiceCurrency }}';
        const paymentDate = $('#date').val();
        fetchPaymentExchangeRate(paymentCurrency, paymentDate);
    });
    
    // Auto-fetch exchange rate when payment currency or date changes
    $('#payment_currency, #date').on('change', function() {
        const functionalCurrency = '{{ $functionalCurrency }}';
        const paymentCurrency = $('#payment_currency').val() || '{{ $invoiceCurrency }}';
        const paymentDate = $('#date').val();
        if (paymentCurrency && paymentCurrency !== functionalCurrency && paymentDate) {
            fetchPaymentExchangeRate(paymentCurrency, paymentDate);
        }
    });
    @endif
});
</script>
@endpush
