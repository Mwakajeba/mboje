@extends('layouts.main')

@section('title', 'Edit Payment - Invoice #' . $invoice->invoice_number)

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchase Management', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Purchase Invoices', 'url' => route('purchases.purchase-invoices.index'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Invoice #' . $invoice->invoice_number, 'url' => route('purchases.purchase-invoices.show', $invoice->encoded_id), 'icon' => 'bx bx-file'],
            ['label' => 'Edit Payment', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        
        <h6 class="mb-0 text-uppercase">EDIT PAYMENT</h6>
        <hr />

        @php
            $paid = (float) ($invoice->payments()->sum('amount'));
            $balanceDue = max(0, (float) $invoice->total_amount - $paid);
            
            // Get functional currency and invoice currency
            $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', auth()->user()->company->functional_currency ?? 'TZS');
            $invoiceCurrency = $invoice->currency ?? $functionalCurrency;
            $invoiceExchangeRate = $invoice->exchange_rate ?? 1.000000;
            $paymentExchangeRate = $p->exchange_rate ?? $invoiceExchangeRate;
            $isForeignCurrency = ($invoiceCurrency !== $functionalCurrency && $invoiceExchangeRate != 1.000000);
        @endphp

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bx bx-edit me-2"></i>Payment Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('purchases.purchase-invoices.payment.update', [$invoice->encoded_id, $p->hash_id]) }}">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">{{ $invoiceCurrency }}</span>
                                            <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01" value="{{ old('amount', $p->amount) }}" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="date" value="{{ old('date', optional($p->date)->toDateString()) }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                                        <select class="form-select" id="method" name="method" required>
                                            <option value="bank" {{ old('method', $p->bank_account_id ? 'bank':'cash') == 'bank' ? 'selected':'' }}>Bank Payment</option>
                                            <option value="cash" {{ old('method', $p->bank_account_id ? 'bank':'cash') == 'cash' ? 'selected':'' }}>Cash</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3" id="bank_account_section" style="display:none;">
                                        <label class="form-label">Bank Account</label>
                                        <select class="form-select select2-single" id="bank_account_id" name="bank_account_id">
                                            <option value="">Select Bank Account</option>
                                            @foreach($bankAccounts as $account)
                                                <option value="{{ $account->id }}" {{ old('bank_account_id', $p->bank_account_id) == $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

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
                                                        <option value="EXCLUSIVE" {{ old('wht_treatment', $p->wht_treatment ?? 'EXCLUSIVE') == 'EXCLUSIVE' ? 'selected' : '' }}>Exclusive</option>
                                                        <option value="INCLUSIVE" {{ old('wht_treatment', $p->wht_treatment ?? '') == 'INCLUSIVE' ? 'selected' : '' }}>Inclusive</option>
                                                        <option value="GROSS_UP" {{ old('wht_treatment', $p->wht_treatment ?? '') == 'GROSS_UP' ? 'selected' : '' }}>Gross-Up</option>
                                                        <option value="NONE" {{ old('wht_treatment', $p->wht_treatment ?? '') == 'NONE' ? 'selected' : '' }}>None</option>
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
                                                        id="wht_rate" name="wht_rate" value="{{ old('wht_rate', $p->wht_rate ?? 0) }}"
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

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3">{{ old('description', $p->description) }}</textarea>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('purchases.purchase-invoices.show', $invoice->encoded_id) }}" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary"><i class="bx bx-check me-1"></i> Update Payment</button>
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
                        @if($isForeignCurrency)
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Currency:</span>
                            <span class="badge bg-info">{{ $invoiceCurrency }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Invoice Exchange Rate:</span>
                            <span>1 {{ $invoiceCurrency }} = {{ number_format($invoiceExchangeRate, 6) }} {{ $functionalCurrency }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Payment Exchange Rate:</span>
                            <span>1 {{ $invoiceCurrency }} = {{ number_format($paymentExchangeRate, 6) }} {{ $functionalCurrency }}</span>
                        </div>
                        @endif
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Amount:</span>
                            <span class="fw-bold">{{ $invoiceCurrency }} {{ number_format($invoice->total_amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Paid Amount:</span>
                            <span class="text-success">{{ $invoiceCurrency }} {{ number_format($paid, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Balance Due:</span>
                            <span class="text-danger fw-bold">{{ $invoiceCurrency }} {{ number_format($balanceDue, 2) }}</span>
                        </div>
                        @if($isForeignCurrency)
                        <div class="alert alert-info py-2 px-3 mb-2">
                            <small>
                                <strong>Total Amount in {{ $functionalCurrency }}:</strong> {{ $functionalCurrency }} {{ number_format($invoice->total_amount * $invoiceExchangeRate, 2) }}<br>
                                <strong>Balance Due in {{ $functionalCurrency }}:</strong> {{ $functionalCurrency }} {{ number_format($balanceDue * $invoiceExchangeRate, 2) }}
                            </small>
                        </div>
                        @endif
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

    function toggleBank() {
        const method = $('#method').val();
        if (method === 'bank') {
            $('#bank_account_section').show();
            $('#wht_section').show();
            setTimeout(function() {
                calculateWHT();
            }, 100);
        } else {
            $('#bank_account_section').hide();
            $('#wht_section').hide();
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
    $('#amount, #wht_treatment, #wht_rate').on('change input', function() {
        if ($('#method').val() === 'bank') {
            calculateWHT();
        }
    });

    // Initial calculation if payment method is bank
    setTimeout(function() {
        if ($('#method').val() === 'bank') {
            calculateWHT();
        }
    }, 200);
    
    @if($isForeignCurrency)
    // FX Gain/Loss calculation for purchase invoices
    function calculateFXGainLoss() {
        const amount = parseFloat($('#amount').val()) || 0;
        const invoiceRate = parseFloat('{{ $invoiceExchangeRate }}');
        const paymentRate = parseFloat($('#payment_exchange_rate').val()) || invoiceRate;
        const functionalCurrency = '{{ $functionalCurrency }}';
        const invoiceCurrency = '{{ $invoiceCurrency }}';
        
        if (amount > 0 && invoiceRate > 0 && paymentRate > 0) {
            // Calculate FX difference
            // For purchase invoices: if payment rate > invoice rate, we pay more in LCY = loss
            // If payment rate < invoice rate, we pay less in LCY = gain
            const invoiceAmountInLCY = amount * invoiceRate;
            const paymentAmountInLCY = amount * paymentRate;
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
        const functionalCurrency = '{{ $functionalCurrency }}';
        const currency = '{{ $invoiceCurrency }}';
        
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
                date: $('input[name="date"]').val() || new Date().toISOString().split('T')[0]
            },
            success: function(response) {
                if (response.success && response.rate) {
                    rateInput.val(parseFloat(response.rate).toFixed(6));
                    calculateFXGainLoss();
                } else {
                    // Fallback to alternative API endpoint
                    $.ajax({
                        url: '{{ route("api.exchange-rates.rate") }}',
                        method: 'GET',
                        data: {
                            from: currency,
                            to: functionalCurrency
                        },
                        success: function(altResponse) {
                            if (altResponse.rate) {
                                rateInput.val(parseFloat(altResponse.rate).toFixed(6));
                                calculateFXGainLoss();
                            }
                        },
                        error: function() {
                            alert('Failed to fetch exchange rate. Please enter manually.');
                        },
                        complete: function() {
                            btn.prop('disabled', false).html(originalHtml);
                            rateInput.prop('disabled', false);
                        }
                    });
                }
            },
            error: function() {
                // Fallback to alternative API endpoint
                $.ajax({
                    url: '{{ route("api.exchange-rates.rate") }}',
                    method: 'GET',
                    data: {
                        from: currency,
                        to: functionalCurrency
                    },
                    success: function(altResponse) {
                        if (altResponse.rate) {
                            rateInput.val(parseFloat(altResponse.rate).toFixed(6));
                            calculateFXGainLoss();
                        }
                    },
                    error: function() {
                        alert('Failed to fetch exchange rate. Please enter manually.');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(originalHtml);
                        rateInput.prop('disabled', false);
                    }
                });
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
                rateInput.prop('disabled', false);
            }
        });
    });
    @endif
});
</script>
@endpush
