@extends('layouts.main')

@section('title', 'Edit Bill Payment')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        @php
            $bill = null;
            if ($payment->reference_type === 'Bill' && $payment->reference_number) {
                $bill = \App\Models\Bill::where('reference', $payment->reference_number)->first();
            }
        @endphp
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchases', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Bill Purchases', 'url' => route('accounting.bill-purchases'), 'icon' => 'bx bx-receipt'],
            ['label' => $bill ? 'Bill #' . $bill->reference : 'Bill Purchases', 'url' => $bill ? route('accounting.bill-purchases.show', $bill) : route('accounting.bill-purchases'), 'icon' => 'bx bx-show'],
            ['label' => 'Payment Details', 'url' => route('accounting.bill-purchases.payment.show', $payment->hash_id), 'icon' => 'bx bx-money'],
            ['label' => 'Edit Payment', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">EDIT PAYMENT</h6>
        <hr />

        <!-- Page Header -->
        <div class="row">
            <div class="col-12">
                <div class="card border-top border-0 border-4 border-warning">
                    <div class="card-body p-5">
                        <div class="card-title d-flex align-items-center">
                            <div><i class="bx bx-edit me-1 font-22 text-warning"></i></div>
                            <h5 class="mb-0 text-warning">Edit Bill Payment</h5>
                        </div>
                        <hr>
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

        <!-- Payment Form -->
        <form action="{{ route('accounting.bill-purchases.payment.update', $payment->hash_id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
                <!-- Payment Details -->
                <div class="col-12 col-lg-8">
                    <div class="card">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Payment Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Payment Date <span class="text-danger">*</span></label>
                                    <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" 
                                           value="{{ old('date', $payment->date->format('Y-m-d')) }}" required>
                                    @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Payment Amount <span class="text-danger">*</span></label>
                                    <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror" 
                                           step="0.01" min="0.01" value="{{ old('amount', $payment->amount) }}" required>
                                    @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Bank Account <span class="text-danger">*</span></label>
                                    <select name="bank_account_id" class="form-select @error('bank_account_id') is-invalid @enderror" required>
                                        <option value="">-- Select Bank Account --</option>
                                        @foreach($bankAccounts as $bankAccount)
                                            <option value="{{ $bankAccount->id }}" 
                                                {{ old('bank_account_id', $payment->bank_account_id) == $bankAccount->id ? 'selected' : '' }}>
                                                {{ $bankAccount->name }} ({{ $bankAccount->account_number }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <!-- <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Supplier</label>
                                    <select name="supplier_id" class="form-select select2-single @error('supplier_id') is-invalid @enderror">
                                        <option value="">-- Select Supplier --</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" 
                                                {{ old('supplier_id', $payment->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                                {{ $supplier->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div> -->
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-bold">Description</label>
                                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" 
                                              rows="3" placeholder="Enter payment description...">{{ old('description', $payment->description) }}</textarea>
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
                                                        <option value="EXCLUSIVE" {{ old('wht_treatment', $payment->wht_treatment ?? 'EXCLUSIVE') == 'EXCLUSIVE' ? 'selected' : '' }}>Exclusive</option>
                                                        <option value="INCLUSIVE" {{ old('wht_treatment', $payment->wht_treatment ?? 'EXCLUSIVE') == 'INCLUSIVE' ? 'selected' : '' }}>Inclusive</option>
                                                        <option value="GROSS_UP" {{ old('wht_treatment', $payment->wht_treatment ?? 'EXCLUSIVE') == 'GROSS_UP' ? 'selected' : '' }}>Gross-Up</option>
                                                        <option value="NONE" {{ old('wht_treatment', $payment->wht_treatment ?? 'EXCLUSIVE') == 'NONE' ? 'selected' : '' }}>None</option>
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
                                                        id="wht_rate" name="wht_rate" value="{{ old('wht_rate', $payment->wht_rate ?? 0) }}"
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
                                                            $paymentVatMode = $payment->vat_mode ?? ($bill ? $bill->vat_mode : 'NONE');
                                                            $selectedVatMode = old('vat_mode', $paymentVatMode);
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
                                                        $paymentVatRate = $payment->vat_rate ?? ($bill ? $bill->vat_rate : get_default_vat_rate());
                                                        $selectedVatRate = old('vat_rate', $paymentVatRate);
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
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="col-12 col-lg-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bx bx-cog me-2"></i>Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-warning">
                                    <i class="bx bx-save me-1"></i> Update Payment
                                </button>
                                <a href="{{ route('accounting.bill-purchases.payment.show', $payment->hash_id) }}" class="btn btn-outline-secondary">
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
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // WHT Calculation
    function calculateWHT() {
        const totalAmount = parseFloat($('input[name="amount"]').val()) || 0;
        const treatment = $('#wht_treatment').val() || 'EXCLUSIVE';
        const whtRate = parseFloat($('#wht_rate').val()) || 0;
        const vatMode = $('#vat_mode').val() || 'NONE';
        const vatRate = parseFloat($('#vat_rate').val()) || {{ $paymentVatRate ?? get_default_vat_rate() }};

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

    $('input[name="amount"], #wht_treatment, #wht_rate, #vat_mode, #vat_rate').on('change input', calculateWHT);
    calculateWHT();
});
</script>
@endpush 