@extends('layouts.main')

@section('title', 'Add Payment - ' . $billPurchase->reference)

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchases', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Bill Purchases', 'url' => route('accounting.bill-purchases'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Bill #' . $billPurchase->reference, 'url' => route('accounting.bill-purchases.show', $billPurchase), 'icon' => 'bx bx-show'],
            ['label' => 'Add Payment', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">ADD PAYMENT</h6>
        <hr />

        <!-- Page Header -->
        <div class="row">
            <div class="col-12">
                <div class="card border-top border-0 border-4 border-success">
                    <div class="card-body p-5">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="card-title d-flex align-items-center">
                                    <div><i class="bx bx-money me-1 font-22 text-success"></i></div>
                                    <h5 class="mb-0 text-success">Add Payment for Bill: {{ $billPurchase->reference }}</h5>
                                </div>
                                <p class="mb-0 text-muted">Process payment to reduce the outstanding balance</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="{{ route('accounting.bill-purchases.show', $billPurchase) }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to Bill
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                Please fix the following errors:
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <!-- Bill Summary -->
            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Bill Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold">Total Amount</label>
                                <p class="h6 text-primary">TZS {{ $billPurchase->formatted_total_amount }}</p>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">Paid Amount</label>
                                <p class="h6 text-success">TZS {{ $billPurchase->formatted_paid }}</p>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label fw-bold">Outstanding Balance</label>
                                <p class="h5 text-danger">TZS {{ $billPurchase->formatted_balance }}</p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label fw-bold">Supplier</label>
                                <p class="form-control-plaintext">{{ $billPurchase->supplier->name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">Due Date</label>
                                <p class="form-control-plaintext">{{ $billPurchase->formatted_due_date ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bx bx-money me-2"></i>Payment Details</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('accounting.bill-purchases.process-payment', $billPurchase) }}" method="POST" id="paymentForm">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                                    <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" 
                                           value="{{ old('date', date('Y-m-d')) }}" required>
                                    @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Payment Amount <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">TZS</span>
                                        <input type="number" name="amount" id="paymentAmount" class="form-control @error('amount') is-invalid @enderror" 
                                               step="0.01" min="0.01" max="{{ $billPurchase->balance }}" 
                                               value="{{ old('amount', $billPurchase->balance) }}" required>
                                    </div>
                                    <small class="text-muted">Maximum: TZS {{ number_format($billPurchase->balance, 2) }}</small>
                                    @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Bank Account <span class="text-danger">*</span></label>
                                    <select name="bank_account_id" class="form-select @error('bank_account_id') is-invalid @enderror" required>
                                        <option value="">-- Select Bank Account --</option>
                                        @foreach($bankAccounts as $bankAccount)
                                            <option value="{{ $bankAccount->id }}" {{ old('bank_account_id') == $bankAccount->id ? 'selected' : '' }}>
                                                {{ $bankAccount->name }} ({{ $bankAccount->account_number }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" 
                                              rows="3" placeholder="Enter payment description...">{{ old('description') }}</textarea>
                                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <!-- WHT Section -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="card border-info">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0 fw-bold">
                                                <i class="bx bx-calculator me-2"></i>Withholding Tax (WHT)
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-3 mb-3">
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
                                                            <strong>Gross-Up:</strong> WHT added on top
                                                        </small>
                                                    </div>
                                                    <div class="col-md-3 mb-3">
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
                                                    <div class="col-md-3 mb-3">
                                                        <label for="vat_mode" class="form-label fw-bold">
                                                            VAT Mode
                                                        </label>
                                                        <select class="form-select @error('vat_mode') is-invalid @enderror"
                                                            id="vat_mode" name="vat_mode">
                                                            @php
                                                                $billVatMode = $billPurchase->vat_mode ?? 'NONE';
                                                                $selectedVatMode = old('vat_mode', $billVatMode);
                                                            @endphp
                                                            <option value="NONE" {{ $selectedVatMode == 'NONE' ? 'selected' : '' }}>None</option>
                                                            <option value="EXCLUSIVE" {{ $selectedVatMode == 'EXCLUSIVE' ? 'selected' : '' }}>Exclusive</option>
                                                            <option value="INCLUSIVE" {{ $selectedVatMode == 'INCLUSIVE' ? 'selected' : '' }}>Inclusive</option>
                                                        </select>
                                                        @error('vat_mode')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                        <small class="form-text text-muted">
                                                            <strong>Exclusive:</strong> VAT separate from base<br>
                                                            <strong>Inclusive:</strong> VAT included in total<br>
                                                            <strong>None:</strong> No VAT
                                                        </small>
                                                    </div>
                                                    <div class="col-md-3 mb-3">
                                                        <label for="vat_rate" class="form-label fw-bold">
                                                            VAT Rate (%)
                                                        </label>
                                                        @php
                                                            $billVatRate = $billPurchase->vat_rate ?? get_default_vat_rate();
                                                            $selectedVatRate = old('vat_rate', $billVatRate);
                                                        @endphp
                                                        <input type="number" class="form-control @error('vat_rate') is-invalid @enderror"
                                                            id="vat_rate" name="vat_rate" value="{{ $selectedVatRate }}"
                                                            step="0.01" min="0" max="100" placeholder="0.00">
                                                        @error('vat_rate')
                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12 mb-3">
                                                        <div class="card bg-light">
                                                            <div class="card-body">
                                                                <h6 class="mb-2 fw-bold">Calculation Preview</h6>
                                                                <div class="row">
                                                                    <div class="col-md-3 mb-2">
                                                                        <small class="text-muted">Total Amount:</small>
                                                                        <div class="fw-bold" id="wht_total_amount">0.00</div>
                                                                    </div>
                                                                    <div class="col-md-3 mb-2">
                                                                        <small class="text-muted">Base Amount:</small>
                                                                        <div class="fw-bold" id="wht_base_amount">0.00</div>
                                                                    </div>
                                                                    <div class="col-md-3 mb-2">
                                                                        <small class="text-muted">VAT Amount:</small>
                                                                        <div class="fw-bold text-info" id="wht_vat_amount">0.00</div>
                                                                    </div>
                                                                    <div class="col-md-3 mb-2">
                                                                        <small class="text-muted">WHT Amount:</small>
                                                                        <div class="fw-bold text-danger" id="wht_amount_preview">0.00</div>
                                                                    </div>
                                                                    <div class="col-md-3 mb-2">
                                                                        <small class="text-muted">Net Payable:</small>
                                                                        <div class="fw-bold text-success" id="wht_net_payable">0.00</div>
                                                                    </div>
                                                                    <div class="col-md-3 mb-2" id="wht_total_cost_container" style="display: none;">
                                                                        <small class="text-muted">Total Cost:</small>
                                                                        <div class="fw-bold text-primary" id="wht_total_cost">0.00</div>
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



                            <!-- Summary -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <label class="form-label fw-bold">Payment Amount</label>
                                                    <p class="h5 text-success">TZS <span id="displayPaymentAmount">0.00</span></p>
                                                </div>
                                            </div>
                                            <div class="d-grid gap-2 mt-3">
                                                <button type="submit" id="submitBtn" class="btn btn-success">
                                                    <i class="bx bx-money me-1"></i> Process Payment
                                                </button>
                                                <a href="{{ route('accounting.bill-purchases.show', $billPurchase) }}" class="btn btn-outline-secondary">
                                                    <i class="bx bx-arrow-back me-1"></i> Cancel
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
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
    // Update payment amount display
    $('#paymentAmount').on('input', function() {
        $('#displayPaymentAmount').text(parseFloat($(this).val() || 0).toFixed(2));
    });

    // Form validation and prevent double submission
    $('#paymentForm').submit(function(e) {
        const paymentAmount = parseFloat($('#paymentAmount').val()) || 0;
        
        if (paymentAmount <= 0) {
            e.preventDefault();
            alert('Please enter a valid payment amount.');
            return false;
        }
        
        // Disable submit button and make it feint to prevent double submission
        const submitBtn = $('#submitBtn');
        submitBtn.prop('disabled', true);
        submitBtn.addClass('opacity-50');
        submitBtn.html('<i class="bx bx-loader-alt bx-spin me-1"></i> Processing...');
        
        // Re-enable after 5 seconds as fallback (in case of network issues)
        setTimeout(function() {
            submitBtn.prop('disabled', false);
            submitBtn.removeClass('opacity-50');
            submitBtn.html('<i class="bx bx-money me-1"></i> Process Payment');
        }, 5000);
    });

    // Initialize
    $('#displayPaymentAmount').text(parseFloat($('#paymentAmount').val() || 0).toFixed(2));

    // WHT Calculation
    function calculateWHT() {
        const totalAmount = parseFloat($('#paymentAmount').val()) || 0;
        const treatment = $('#wht_treatment').val() || 'EXCLUSIVE';
        const whtRate = parseFloat($('#wht_rate').val()) || 0;
        const vatMode = $('#vat_mode').val() || 'NONE';
        const vatRate = parseFloat($('#vat_rate').val()) || {{ $billPurchase->vat_rate ?? get_default_vat_rate() }};

        // Calculate base amount (excluding VAT) based on VAT mode
        let baseAmount = totalAmount;
        let vatAmount = 0;

        if (vatMode === 'INCLUSIVE' && vatRate > 0) {
            // VAT is included in total, extract base
            baseAmount = totalAmount / (1 + (vatRate / 100));
            vatAmount = totalAmount - baseAmount;
        } else if (vatMode === 'EXCLUSIVE' && vatRate > 0) {
            // VAT is separate, total is base + VAT
            baseAmount = totalAmount / (1 + (vatRate / 100));
            vatAmount = totalAmount - baseAmount;
        }

        // Round to 2 decimal places
        baseAmount = Math.round(baseAmount * 100) / 100;
        vatAmount = Math.round(vatAmount * 100) / 100;

        // Update display
        $('#wht_total_amount').text(totalAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#wht_base_amount').text(baseAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#wht_vat_amount').text(vatAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

        if (whtRate <= 0 || treatment === 'NONE') {
            $('#wht_amount_preview').text('0.00');
            let netPayable = totalAmount;
            if (vatMode === 'EXCLUSIVE' && vatAmount > 0) {
                netPayable = baseAmount + vatAmount;
            }
            $('#wht_net_payable').text(netPayable.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#wht_total_cost_container').hide();
            return;
        }

        // Calculate WHT on base amount (never on VAT)
        let wht = 0;
        let net = baseAmount;
        let totalCost = baseAmount;

        const rateDecimal = whtRate / 100;
        
        if (treatment === 'EXCLUSIVE') {
            wht = baseAmount * rateDecimal;
            net = baseAmount - wht;
            totalCost = baseAmount;
        } else if (treatment === 'INCLUSIVE') {
            wht = baseAmount * (rateDecimal / (1 + rateDecimal));
            net = baseAmount - wht;
            totalCost = baseAmount;
        } else if (treatment === 'GROSS_UP') {
            wht = baseAmount * (rateDecimal / (1 - rateDecimal));
            net = baseAmount;
            totalCost = baseAmount + wht;
            $('#wht_total_cost_container').show();
        }

        // Round WHT calculations
        wht = Math.round(wht * 100) / 100;
        net = Math.round(net * 100) / 100;
        totalCost = Math.round(totalCost * 100) / 100;

        // For net payable, add VAT back if VAT is exclusive
        let netPayable = net;
        if (vatMode === 'EXCLUSIVE' && vatAmount > 0) {
            netPayable = net + vatAmount;
        }

        $('#wht_amount_preview').text(wht.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#wht_net_payable').text(netPayable.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#wht_total_cost').text(totalCost.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

        if (treatment !== 'GROSS_UP') {
            $('#wht_total_cost_container').hide();
        }
    }

    $('#paymentAmount, #wht_treatment, #wht_rate, #vat_mode, #vat_rate').on('change input', calculateWHT);
    calculateWHT();
});
</script>
@endpush 