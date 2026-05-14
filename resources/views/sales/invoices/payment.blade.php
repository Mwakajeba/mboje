@extends('layouts.main')

@section('title', 'Record Payment - Invoice #' . $invoice->invoice_number)

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-store'],
            ['label' => 'Sales Invoices', 'url' => route('sales.invoices.index'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Invoice #' . $invoice->invoice_number, 'url' => route('sales.invoices.show', $invoice->encoded_id), 'icon' => 'bx bx-file'],
            ['label' => 'Record Payment', 'url' => '#', 'icon' => 'bx bx-money']
        ]" />
        
        <h6 class="mb-0 text-uppercase">RECORD PAYMENT</h6>
        <hr />

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bx bx-money me-2"></i>Payment Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('sales.invoices.record-payment', $invoice->encoded_id) }}">
                            @csrf
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="amount" class="form-label">Payment Amount <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">{{ $invoice->currency ?? 'TZS' }}</span>
                                            <input type="number" class="form-control" id="amount" name="amount" 
                                                   value="{{ old('amount', $invoice->balance_due) }}" 
                                                   min="0.01" max="{{ $invoice->balance_due }}" step="0.01" required>
                                        </div>
                                        <small class="text-muted">Maximum amount: {{ number_format($invoice->balance_due, 2) }} {{ $invoice->currency ?? 'TZS' }}</small>
                                        @if($invoice->early_payment_discount_enabled && $invoice->isEarlyPaymentDiscountValid() && $invoice->calculateEarlyPaymentDiscount() > 0)
                                        <div class="alert alert-success py-2 mt-2">
                                            <small><i class="bx bx-check-circle me-1"></i>Suggested payment with early discount: <strong>{{ number_format($invoice->getAmountDueWithEarlyDiscount(), 2) }}</strong></small>
                                        </div>
                                        @endif
                                        @if($invoice->late_payment_fees_enabled && $invoice->isOverdue() && $invoice->calculateLatePaymentFees() > 0)
                                        <div class="alert alert-warning py-2 mt-2">
                                            <small><i class="bx bx-exclamation-triangle me-1"></i>Amount with late fees: <strong>{{ number_format($invoice->getAmountDueWithLateFees(), 2) }}</strong></small>
                                        </div>
                                        @endif
                                        @error('amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="payment_date" name="payment_date" 
                                               value="{{ old('payment_date', now()->toDateString()) }}" required>
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
                                            <option value="bank" {{ old('payment_method') == 'bank' ? 'selected' : '' }}>Bank or Cash Payment</option>
                                            <option value="cash_deposit" {{ old('payment_method') == 'cash_deposit' ? 'selected' : '' }}>Customer Deposits</option>
                                        </select>
                                        @error('payment_method') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3" id="bank_account_section" style="display: none;">
                                        <label for="chart_account_id" class="form-label">Bank or Cash Account <span class="text-danger">*</span></label>
                                        <select class="form-select select2-single" id="chart_account_id" name="chart_account_id">
                                            <option value="">Select Bank or Cash Account</option>
                                            @foreach($bankAccounts as $account)
                                                <option value="{{ $account->chart_account_id }}">{{ $account->name }} ({{ $account->account_number }})</option>
                                            @endforeach
                                        </select>
                                        @error('chart_account_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                    
                                    <div class="mb-3" id="cash_deposit_section" style="display: none;">
                                        <label for="cash_deposit_id" class="form-label">Customer Cash Deposit Account</label>
                                        <select class="form-select select2-single" id="cash_deposit_id" name="cash_deposit_id">
                                            <option value="">Select Customer Account</option>
                                        </select>
                                        <small class="text-muted">Customer: {{ $invoice->customer->name ?? 'N/A' }} - Cash deposit balance will be shown below</small>
                                        <div id="no_account_message" class="text-danger mt-2" style="display: none;">
                                            <i class="bx bx-info-circle"></i> No account available for this customer
                                        </div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Currency and Exchange Rate Section -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    @php
                                        $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', auth()->user()->company->functional_currency ?? 'TZS');
                                        $invoiceCurrency = $invoice->currency ?? $functionalCurrency;
                                    @endphp
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
                                                   value="{{ old('payment_exchange_rate', number_format($invoice->exchange_rate ?? 1, 6, '.', '')) }}" 
                                                   step="0.000001" min="0.000001" placeholder="1.000000">
                                            <button type="button" class="btn btn-outline-secondary" id="fetch-payment-rate-btn">
                                                <i class="bx bx-refresh"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">
                                            Invoice Rate: {{ number_format($invoice->exchange_rate ?? 1, 6) }} | 
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
                            @if($invoice->currency && $invoice->currency !== 'TZS')
                            <div class="row mb-3">
                                <div class="col-md-12">
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

                            <!-- WHT Section (for Bank Payments only) -->
                            <div class="row mb-4" id="wht_section" style="display: none;">
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
                                                        <option value="NONE" {{ old('wht_treatment', 'NONE') == 'NONE' ? 'selected' : '' }}>None</option>
                                                        <option value="EXCLUSIVE" {{ old('wht_treatment') == 'EXCLUSIVE' ? 'selected' : '' }}>Exclusive</option>
                                                        <option value="INCLUSIVE" {{ old('wht_treatment') == 'INCLUSIVE' ? 'selected' : '' }}>Inclusive</option>
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
                                                  placeholder="Optional payment description">{{ old('description') }}</textarea>
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
                                    <button type="submit" class="btn btn-primary" data-processing-text="Recording...">
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

                        <!-- Early Payment Discount Information -->
                        @if($invoice->early_payment_discount_enabled && $invoice->isEarlyPaymentDiscountValid() && $invoice->calculateEarlyPaymentDiscount() > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span>Early Payment Discount:</span>
                            <span class="text-success fw-bold">-{{ number_format($invoice->calculateEarlyPaymentDiscount(), 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Amount with Early Discount:</span>
                            <span class="text-success fw-bold">{{ number_format($invoice->getAmountDueWithEarlyDiscount(), 2) }}</span>
                        </div>
                        <div class="alert alert-success py-2 mb-2">
                            <small><i class="bx bx-time me-1"></i>Early payment valid until: {{ $invoice->getEarlyPaymentDiscountExpiryDate()->format('M d, Y') }}</small>
                        </div>
                        @endif

                        <!-- Late Payment Fees Information -->
                        @if($invoice->late_payment_fees_enabled && $invoice->isOverdue() && $invoice->calculateLatePaymentFees() > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span>Late Payment Fees:</span>
                            <span class="text-danger fw-bold">+{{ number_format($invoice->calculateLatePaymentFees(), 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Amount with Late Fees:</span>
                            <span class="text-danger fw-bold">{{ number_format($invoice->getAmountDueWithLateFees(), 2) }}</span>
                        </div>
                        <div class="alert alert-danger py-2 mb-2">
                            <small><i class="bx bx-error me-1"></i>Overdue by {{ $invoice->getOverdueDays() }} days</small>
                        </div>
                        @endif

                        <hr>
                        <div class="d-flex justify-content-between">
                            <span>Status:</span>
                            <span>{!! $invoice->status_badge !!}</span>
                        </div>
                        @if($invoice->isOverdue())
                        <div class="mt-2">
                            <span class="badge bg-danger">
                                Overdue by {{ $invoice->getOverdueDays() }} days
                            </span>
                        </div>
                        @endif
                    </div>
                </div>

                @if($invoice->receipts()->count() > 0)
                <div class="card mt-3">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bx bx-history me-2"></i>Payment History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->receipts()->orderBy('date', 'desc')->limit(5)->get() as $receipt)
                                    <tr>
                                        <td>{{ $receipt->date->format('M d, Y') }}</td>
                                        <td class="text-success">{{ number_format($receipt->amount, 2) }}</td>
                                        <td>
                                            @if($receipt->bank_account_id)
                                                <span class="badge bg-info">Bank</span>
                                            @else
                                                <span class="badge bg-secondary">Cash</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
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
    // Function to fetch payment exchange rate from FX RATES MANAGEMENT
    function fetchPaymentExchangeRate(paymentCurrency = null, paymentDate = null) {
        const functionalCurrency = '{{ \App\Models\SystemSetting::getValue("functional_currency", auth()->user()->company->functional_currency ?? "TZS") }}';
        paymentCurrency = paymentCurrency || $('#payment_currency').val() || invoiceCurrency;
        paymentDate = paymentDate || $('#payment_date').val() || new Date().toISOString().split('T')[0];
        
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
                date: paymentDate, // Use payment date instead of today
                rate_type: 'spot'
            },
            success: function(response) {
                if (response.success && response.rate) {
                    const rate = parseFloat(response.rate);
                    rateInput.val(rate.toFixed(6));
                    rateInput.prop('disabled', false);
                    const source = response.source || 'FX RATES MANAGEMENT';
                    $('#payment-rate-source').text(`Rate from ${source} for ${paymentDate}: 1 ${paymentCurrency} = ${rate.toFixed(6)} ${functionalCurrency}`);
                    $('#payment-rate-info').show();
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
                        title: `Rate updated: 1 ${paymentCurrency} = ${rate.toFixed(6)} ${functionalCurrency}`
                    });
                } else {
                    console.warn('Exchange rate API returned unexpected format:', response);
                    rateInput.prop('disabled', false);
                    fetchPaymentExchangeRateFallback(paymentCurrency, paymentDate);
                }
            },
            error: function(xhr) {
                console.error('Failed to fetch exchange rate:', xhr);
                fetchPaymentExchangeRateFallback(paymentCurrency, paymentDate);
            },
            complete: function() {
                btn.html(originalHtml);
                btn.prop('disabled', false);
                rateInput.prop('disabled', false);
            }
        });
    }
    
    // Fallback function to fetch rate from API if FX RATES MANAGEMENT doesn't have it
    function fetchPaymentExchangeRateFallback(paymentCurrency, paymentDate) {
        const rateInput = $('#payment_exchange_rate');
        const functionalCurrency = '{{ \App\Models\SystemSetting::getValue("functional_currency", auth()->user()->company->functional_currency ?? "TZS") }}';
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
        const functionalCurrency = '{{ \App\Models\SystemSetting::getValue("functional_currency", auth()->user()->company->functional_currency ?? "TZS") }}';
        const paymentCurrency = $('#payment_currency').val() || invoiceCurrency;
        const paymentDate = $('#payment_date').val();
        fetchPaymentExchangeRate(paymentCurrency, paymentDate);
    });
    
    // Auto-fetch exchange rate when payment currency or date changes
    $('#payment_currency, #payment_date').on('change', function() {
        const functionalCurrency = '{{ \App\Models\SystemSetting::getValue("functional_currency", auth()->user()->company->functional_currency ?? "TZS") }}';
        const paymentCurrency = $('#payment_currency').val() || invoiceCurrency;
        const paymentDate = $('#payment_date').val();
        if (paymentCurrency && paymentCurrency !== functionalCurrency && paymentDate) {
            fetchPaymentExchangeRate(paymentCurrency, paymentDate);
        }
    });
    @endif

    const customerCashDepositBalance = {{ $invoice->customer ? json_encode(round((float) $invoice->customer->cash_deposit_balance, 2)) : 0 }};
    const invoiceCurrencyCode = @json($invoice->currency ?? 'TZS');

    function alertCashDepositIfUsingBank() {
        if (customerCashDepositBalance > 0) {
            Swal.fire({
                icon: 'info',
                title: 'Customer has cash deposit balance',
                html: 'This customer has <strong>' + customerCashDepositBalance.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + invoiceCurrencyCode + '</strong> available in cash deposits. You can choose <strong>Cash Deposit</strong> as the payment method to apply those funds to this invoice, or continue with bank payment.',
                confirmButtonText: 'OK'
            });
        }
    }

$(document).ready(function() {
    // Initialize Select2
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Validate amount doesn't exceed balance due
    $('#amount').on('change', function() {
        let amount = parseFloat($(this).val()) || 0;
        let balanceDue = parseFloat('{{ $invoice->balance_due }}');
        
        if (amount > balanceDue) {
            alert('Payment amount cannot exceed the balance due amount.');
            $(this).val(balanceDue);
        }
    });

    // Handle payment method changes
    $('#payment_method').change(function() {
        const selectedMethod = $(this).val();
        
        // Hide all sections first
        $('#bank_account_section, #cash_deposit_section, #wht_section').hide();
        
        // Show relevant section based on selection
        if (selectedMethod === 'bank') {
            $('#bank_account_section').show();
            $('#wht_section').show(); // Show WHT section for bank payments
            alertCashDepositIfUsingBank();
        } else if (selectedMethod === 'cash_deposit') {
            $('#cash_deposit_section').show();
            
            // Load customer account since we already have the customer
            const customerId = '{{ $invoice->customer->encoded_id ?? '' }}';
            console.log('Customer ID from invoice:', customerId);
            console.log('Invoice customer ID (raw):', {{ $invoice->customer ? $invoice->customer->id : 'null' }});
            console.log('Invoice customer name:', '{{ $invoice->customer->name ?? "N/A" }}');
            
            if (customerId && customerId !== '' && customerId !== 'null') {
                console.log('Loading cash deposits for customer ID:', customerId);
                loadCustomerCashDeposits(customerId);
            } else {
                // If no customer, show error message
                const select = $('#cash_deposit_id');
                select.empty();
                select.append('<option value="">Error: No customer found for this invoice</option>');
                console.error('No customer ID available for invoice. Customer ID value:', customerId);
            }
        }
    });

    // Load customer cash deposits
    function loadCustomerCashDeposits(customerId) {
        console.log('Loading cash deposits for customer:', customerId);
        
        const select = $('#cash_deposit_id');
        
        // Show loading state
        select.prop('disabled', true);
        select.empty();
        select.append('<option value="">Loading customer accounts...</option>');
        
        // Trigger Select2 update to show loading state
        if (select.hasClass('select2-hidden-accessible')) {
            select.trigger('change.select2');
        }
        
        $.ajax({
            url: `/sales/invoices/customer/${customerId}/cash-deposits`,
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                console.log('Cash deposits response (full):', JSON.stringify(response, null, 2));
                console.log('Response data:', response.data);
                console.log('Response data length:', response.data ? response.data.length : 'null/undefined');
                
                select.empty();
                select.prop('disabled', false);
                
                // Check for error in response
                if (response.error) {
                    select.append('<option value="">Error: ' + response.error + '</option>');
                    console.error('API returned error:', response.error);
                } else if (response.data && Array.isArray(response.data) && response.data.length > 0) {
                    select.append('<option value="">Select Customer Account</option>');
                    response.data.forEach(function(deposit) {
                        // For customer balance, send a special value to indicate customer balance
                        const depositId = String(deposit.id);
                        const value = depositId.startsWith('customer_balance') ? 'customer_balance' : deposit.id;
                        select.append(`<option value="${value}" data-balance-id="${deposit.id}">${deposit.balance_text}</option>`);
                    });
                    $('#no_account_message').hide();
                    console.log('Successfully added', response.data.length, 'balance options');
                } else {
                    select.append('<option value="">No account available for this customer</option>');
                    select.prop('disabled', true);
                    $('#no_account_message').show();
                    console.warn('No customer accounts available - response:', response);
                    console.warn('Response has data?', !!response.data);
                    console.warn('Data is array?', Array.isArray(response.data));
                    if (response.data) {
                        console.warn('Data length:', response.data.length);
                    }
                }
                
                // Refresh Select2 to show new options
                if (select.hasClass('select2-hidden-accessible')) {
                    select.trigger('change.select2');
                }
            },
            error: function(xhr) {
                console.error('Error loading cash deposits:', xhr);
                console.error('Response text:', xhr.responseText);
                select.prop('disabled', false);
                select.empty();
                select.append('<option value="">Error loading accounts</option>');
                // Show more detailed error if available
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    select.append(`<option value="" disabled>${xhr.responseJSON.error}</option>`);
                } else {
                    select.append('<option value="" disabled>Please check console for details</option>');
                }
                
                // Refresh Select2 to show error
                if (select.hasClass('select2-hidden-accessible')) {
                    select.trigger('change.select2');
                }
            }
        });
    }

    // Trigger change event on page load if payment method is already selected
    if ($('#payment_method').val()) {
        $('#payment_method').trigger('change');
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

    $('#amount, #wht_treatment, #wht_rate').on('change input', calculateWHT);
    calculateWHT();
});
</script>
@endpush 