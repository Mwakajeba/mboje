@extends('layouts.main')

@section('title', 'Edit Payment Voucher')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <!-- Breadcrumb -->
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
                ['label' => 'Payment Vouchers', 'url' => route('accounting.payment-vouchers.index'), 'icon' => 'bx bx-receipt'],
                ['label' => 'Payment Voucher #' . $paymentVoucher->reference, 'url' => route('accounting.payment-vouchers.show', $paymentVoucher->hash_id), 'icon' => 'bx bx-show'],
                ['label' => 'Edit Payment Voucher', 'url' => '#', 'icon' => 'bx bx-edit']
            ]" />
            <h6 class="mb-0 text-uppercase">EDIT PAYMENT VOUCHER</h6>
            <hr />
            <div class="row">
                <div class="col-12">
                    <div class="card radius-10">
                        <div class="card-header bg-warning text-dark">
                            <div class="d-flex align-items-center">
                                <div>
                                    <h5 class="mb-0 text-dark">
                                        <i class="bx bx-edit me-2"></i>Edit Payment Voucher
                                    </h5>
                                    <p class="mb-0 opacity-75">Update payment voucher entry</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="paymentVoucherForm" action="{{ route('accounting.payment-vouchers.update', $paymentVoucher->hash_id) }}"
                                method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <!-- Header Section -->
                                <div class="row mb-4">
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="date" class="form-label fw-bold">
                                                <i class="bx bx-calendar me-1"></i>Date <span class="text-danger">*</span>
                                            </label>
                                            <input type="date"
                                                class="form-control form-control-lg @error('date') is-invalid @enderror"
                                                id="date" name="date" value="{{ old('date', $paymentVoucher->date->format('Y-m-d')) }}" required>
                                            @error('date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="reference" class="form-label fw-bold">
                                                <i class="bx bx-hash me-1"></i>Reference Number
                                            </label>
                                            <input type="text"
                                                class="form-control form-control-lg @error('reference') is-invalid @enderror"
                                                id="reference" name="reference" value="{{ old('reference', $paymentVoucher->reference) }}"
                                                placeholder="Enter reference number">
                                            @error('reference')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>


                                <!-- Currency Section -->
                                <div class="row mb-4">
                                    <div class="col-lg-6">
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
                                        <div class="mb-3">
                                            <label for="currency" class="form-label fw-bold">
                                                <i class="bx bx-money me-1"></i>Currency
                                            </label>
                                            <select class="form-select form-select-lg select2-single @error('currency') is-invalid @enderror"
                                                id="currency" name="currency">
                                                @foreach($currencies as $currency)
                                                    <option value="{{ $currency->currency_code }}" 
                                                            {{ old('currency', $paymentVoucher->currency ?? $functionalCurrency) == $currency->currency_code ? 'selected' : '' }}>
                                                        {{ $currency->currency_name ?? $currency->currency_code }} ({{ $currency->currency_code }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('currency')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="exchange_rate" class="form-label fw-bold">
                                                <i class="bx bx-transfer me-1"></i>Exchange Rate
                                            </label>
                                            <div class="input-group">
                                                <input type="number" class="form-control form-control-lg @error('exchange_rate') is-invalid @enderror"
                                                    id="exchange_rate" name="exchange_rate" 
                                                    value="{{ old('exchange_rate', number_format($paymentVoucher->exchange_rate ?? 1, 6, '.', '')) }}" 
                                                    step="0.000001" min="0.000001" placeholder="1.000000">
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
                                            @error('exchange_rate')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Payee Section -->
                                <div class="row mb-4">
                                    <div class="col-lg-12">
                                        <div class="card border-warning">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0 fw-bold">
                                                    <i class="bx bx-user me-2"></i>Payee Information
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-lg-4">
                                                        <div class="mb-3">
                                                            <label for="payee_type" class="form-label fw-bold">
                                                                Payee Type <span class="text-danger">*</span>
                                                            </label>
                                                            <select
                                                                class="form-select form-select-lg select2-single @error('payee_type') is-invalid @enderror"
                                                                id="payee_type" name="payee_type" required>
                                                                <option value="">-- Select Payee Type --</option>
                                                                <option value="customer" {{ old('payee_type', $paymentVoucher->payee_type) == 'customer' ? 'selected' : '' }}>Customer</option>
                                                                <option value="supplier" {{ old('payee_type', $paymentVoucher->payee_type) == 'supplier' ? 'selected' : '' }}>Supplier</option>
                                                                <option value="employee" {{ old('payee_type', $paymentVoucher->payee_type) == 'employee' ? 'selected' : '' }}>Employee</option>
                                                                <option value="other" {{ old('payee_type', $paymentVoucher->payee_type) == 'other' ? 'selected' : '' }}>Other</option>
                                                            </select>
                                                            @error('payee_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                                    <!-- Customer Selection (shown when payee_type is customer) -->
                                                    <div class="col-lg-8" id="customerSection" style="display: none;">
                                        <div class="mb-3">
                                            <label for="customer_id" class="form-label fw-bold">
                                                                Select Customer <span class="text-danger">*</span>
                                            </label>
                                            <select
                                                class="form-select form-select-lg select2-single @error('customer_id') is-invalid @enderror"
                                                id="customer_id" name="customer_id">
                                                                <option value="">-- Select Customer --</option>
                                                    @foreach($customers as $customer)
                                                        <option value="{{ $customer->id }}" 
                                                                data-encoded-id="{{ \Vinkla\Hashids\Facades\Hashids::encode($customer->id) }}"
                                                                {{ old('customer_id', $paymentVoucher->customer_id) == $customer->id ? 'selected' : '' }}>
                                                            {{ $customer->name }} ({{ $customer->customerNo }})
                                                        </option>
                                                    @endforeach
                                            </select>
                                            @error('customer_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                                        </div>
                                                    </div>

                                                    <!-- Supplier Selection (shown when payee_type is supplier) -->
                                                    <div class="col-lg-8" id="supplierSection" style="display: none;">
                                                        <div class="mb-3">
                                                            <label for="supplier_id" class="form-label fw-bold">
                                                                Select Supplier <span class="text-danger">*</span>
                                                            </label>
                                                            <select
                                                                class="form-select form-select-lg select2-single @error('supplier_id') is-invalid @enderror"
                                                                id="supplier_id" name="supplier_id">
                                                                <option value="">-- Select Supplier --</option>
                                                                @foreach($suppliers as $supplier)
                                                                    <option value="{{ $supplier->id }}" {{ old('supplier_id', $paymentVoucher->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                                                        {{ $supplier->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            @error('supplier_id')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                    </div>

                                                    <!-- Employee Selection (shown when payee_type is employee) -->
                                                    <div class="col-lg-8" id="employeeSection" style="display: none;">
                                                        <div class="mb-3">
                                                            <label for="employee_id" class="form-label fw-bold">
                                                                Select Employee <span class="text-danger">*</span>
                                                            </label>
                                                            <select
                                                                class="form-select form-select-lg select2-single @error('employee_id') is-invalid @enderror"
                                                                id="employee_id" name="employee_id">
                                                                <option value="">-- Select Employee --</option>
                                                                @foreach($employees ?? [] as $employee)
                                                                    <option value="{{ $employee->id }}" {{ old('employee_id', $paymentVoucher->employee_id) == $employee->id ? 'selected' : '' }}>
                                                                        {{ $employee->full_name }}@if($employee->employee_number) ({{ $employee->employee_number }})@endif
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            @error('employee_id')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                    </div>

                                                    <!-- Other Payee Name (shown when payee_type is other) -->
                                                    <div class="col-lg-8" id="otherPayeeSection" style="display: none;">
                                                        <div class="mb-3">
                                                            <label for="payee_name" class="form-label fw-bold">
                                                                Payee Name <span class="text-danger">*</span>
                                                            </label>
                                                            <input type="text"
                                                                class="form-control form-control-lg @error('payee_name') is-invalid @enderror"
                                                                id="payee_name" name="payee_name"
                                                                value="{{ old('payee_name', $paymentVoucher->payee_name) }}"
                                                                placeholder="Enter payee name"
                                                                required>
                                                            @error('payee_name')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Method Section -->
                                <div class="row mb-4">
                                    <div class="col-lg-12">
                                        <div class="card border-info">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0 fw-bold">
                                                    <i class="bx bx-credit-card me-2"></i>Payment Method
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">
                                                                <i class="bx bx-credit-card me-1"></i>Payment Method <span class="text-danger">*</span>
                                                            </label>
                                                            <select
                                                                class="form-select form-select-lg @error('payment_method') is-invalid @enderror"
                                                                id="payment_method" name="payment_method" required>
                                                                <option value="">-- Select Payment Method --</option>
                                                                <option value="bank_transfer" {{ old('payment_method', $paymentVoucher->payment_method ?? 'bank_transfer') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                                                <option value="cash_deposit" id="cash_deposit_option" {{ old('payment_method', $paymentVoucher->payment_method) == 'cash_deposit' ? 'selected' : '' }} style="display: none;">Cash Deposit</option>
                                                                <option value="cheque" {{ old('payment_method', $paymentVoucher->payment_method) == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                                            </select>
                                                            @error('payment_method')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                    </div>

                                                    <!-- Bank Account Section (for Bank Transfer and Cheque) -->
                                                    <div class="col-lg-6" id="bank_account_section" style="display: none;">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">
                                                                <i class="bx bx-wallet me-1"></i>Bank Account <span class="text-danger">*</span>
                                                            </label>
                                                            <select
                                                                class="form-select form-select-lg select2-single @error('bank_account_id') is-invalid @enderror"
                                                                id="bank_account_id" name="bank_account_id">
                                                                <option value="">-- Select Bank Account --</option>
                                                                @foreach($bankAccounts as $bankAccount)
                                                                    <option value="{{ $bankAccount->id }}" {{ old('bank_account_id', $paymentVoucher->bank_account_id) == $bankAccount->id ? 'selected' : '' }}>
                                                                        {{ $bankAccount->name }} - {{ $bankAccount->account_number }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            @error('bank_account_id')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                        </div>
                                                    </div>

                                                    <!-- Cash Deposit Section (for Cash Deposit) -->
                                                    <div class="col-lg-6" id="cash_deposit_section" style="display: none;">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">
                                                                <i class="bx bx-wallet me-1"></i>Customer Account <span class="text-danger">*</span>
                                                            </label>
                                                            <select
                                                                class="form-select form-select-lg select2-single @error('cash_deposit_id') is-invalid @enderror"
                                                                id="cash_deposit_id" name="cash_deposit_id">
                                                                <option value="">-- Select Customer Account --</option>
                                                                @if($paymentVoucher->payment_method === 'cash_deposit' && $paymentVoucher->cash_deposit_id === 'customer_balance')
                                                                    <option value="customer_balance" selected>Customer Balance</option>
                                                                @endif
                                                            </select>
                                                            @error('cash_deposit_id')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                            <small class="text-muted">Only available when payee type is Customer</small>
                                                        </div>
                                                    </div>

                                                </div>

                                                <!-- Cheque Information Section -->
                                                <div class="row mb-3" id="cheque_section" style="display: none;">
                                                    <div class="col-12">
                                                        <div class="card border-primary">
                                                            <div class="card-header bg-primary bg-opacity-10">
                                                                <h6 class="mb-0 text-primary">
                                                                    <i class="bx bx-credit-card me-2"></i>Cheque Information
                                                                </h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="row g-3">
                                                                    <div class="col-md-4">
                                                                        <label class="form-label">Cheque Number <span class="text-danger">*</span></label>
                                                                        <input type="text" 
                                                                               class="form-control @error('cheque_number') is-invalid @enderror" 
                                                                               id="cheque_number" 
                                                                               name="cheque_number" 
                                                                               value="{{ old('cheque_number', optional($paymentVoucher->cheque)->cheque_number ?? '') }}"
                                                                               placeholder="Enter cheque number">
                                                                        @error('cheque_number')
                                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                                        @enderror
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <label class="form-label">Cheque Date <span class="text-danger">*</span></label>
                                                                        <input type="date" 
                                                                               class="form-control @error('cheque_date') is-invalid @enderror" 
                                                                               id="cheque_date" 
                                                                               name="cheque_date" 
                                                                               value="{{ old('cheque_date', optional($paymentVoucher->cheque)->cheque_date ? optional($paymentVoucher->cheque)->cheque_date->format('Y-m-d') : date('Y-m-d')) }}">
                                                                        @error('cheque_date')
                                                                            <div class="invalid-feedback">{{ $message }}</div>
                                                                        @enderror
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <label class="form-label">Payee Name</label>
                                                                        <input type="text" 
                                                                               class="form-control" 
                                                                               id="cheque_payee_name" 
                                                                               readonly
                                                                               placeholder="Auto-filled from payee selection">
                                                                        <small class="text-muted">Auto-filled from payee selection above</small>
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

                                <!-- WHT Section -->
                                @php
                                    $hasExistingWht = $paymentVoucher->wht_treatment
                                        && $paymentVoucher->wht_treatment !== 'NONE'
                                        && (float) ($paymentVoucher->wht_rate ?? 0) > 0;
                                    // When there is no existing WHT, default the treatment to NONE instead of EXCLUSIVE
                                    $defaultWhtTreatment = $paymentVoucher->wht_treatment
                                        ?? ($hasExistingWht ? 'EXCLUSIVE' : 'NONE');
                                    $selectedWhtTreatment = old('wht_treatment', $defaultWhtTreatment);
                                @endphp
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
                                                            <option value="EXCLUSIVE" {{ $selectedWhtTreatment === 'EXCLUSIVE' ? 'selected' : '' }}>Exclusive</option>
                                                            <option value="INCLUSIVE" {{ $selectedWhtTreatment === 'INCLUSIVE' ? 'selected' : '' }}>Inclusive</option>
                                                            <option value="GROSS_UP" {{ $selectedWhtTreatment === 'GROSS_UP' ? 'selected' : '' }}>Gross-Up</option>
                                                            <option value="NONE" {{ $selectedWhtTreatment === 'NONE' ? 'selected' : '' }}>None</option>
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
                                                            id="wht_rate" name="wht_rate" value="{{ old('wht_rate', $paymentVoucher->wht_rate ?? 0) }}"
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
                                                                $defaultVatType = get_default_vat_type();
                                                                $defaultVatMode = 'EXCLUSIVE'; // Default fallback
                                                                if ($defaultVatType == 'inclusive') {
                                                                    $defaultVatMode = 'INCLUSIVE';
                                                                } elseif ($defaultVatType == 'exclusive') {
                                                                    $defaultVatMode = 'EXCLUSIVE';
                                                                } elseif ($defaultVatType == 'no_vat') {
                                                                    $defaultVatMode = 'NONE';
                                                                }
                                                                $selectedVatMode = old('vat_mode', $paymentVoucher->vat_mode ?? $defaultVatMode);
                                                            @endphp
                                                            <option value="EXCLUSIVE" {{ $selectedVatMode == 'EXCLUSIVE' ? 'selected' : '' }}>Exclusive</option>
                                                            <option value="INCLUSIVE" {{ $selectedVatMode == 'INCLUSIVE' ? 'selected' : '' }}>Inclusive</option>
                                                            <option value="NONE" {{ $selectedVatMode == 'NONE' ? 'selected' : '' }}>None</option>
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
                                                        <input type="number" class="form-control @error('vat_rate') is-invalid @enderror"
                                                            id="vat_rate" name="vat_rate" value="{{ old('vat_rate', $paymentVoucher->vat_rate ?? get_default_vat_rate()) }}"
                                                            step="0.01" min="0" max="100" placeholder="18.00">
                                                        @error('vat_rate')
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

                                <!-- Transaction Description -->
                                <div class="row mb-4">
                                    <div class="col-12 mb-3">
                                        <label for="description" class="form-label fw-bold">
                                            <i class="bx bx-message-square-detail me-1"></i>Description
                                        </label>
                                        <textarea class="form-control form-control-lg @error('description') is-invalid @enderror"
                                            id="description" name="description" rows="3"
                                            placeholder="Enter payment voucher description">{{ old('description', $paymentVoucher->description) }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label for="attachment" class="form-label fw-bold">
                                            <i class="bx bx-paperclip me-1"></i>Attachment (Optional)
                                        </label>
                                        @if($paymentVoucher->attachment)
                                            <div class="alert alert-info mb-3">
                                                <i class="bx bx-info-circle me-2"></i>
                                                <strong>Current Attachment:</strong> {{ $paymentVoucher->attachment_name }}
                                                <a href="{{ route('accounting.payment-vouchers.download-attachment', $paymentVoucher->hash_id) }}" 
                                                   class="btn btn-sm btn-primary ms-2">
                                                    <i class="bx bx-download me-1"></i>Download
                                                </a>
                                            </div>
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" id="remove_attachment" name="remove_attachment" value="1">
                                                <label class="form-check-label text-danger" for="remove_attachment">
                                                    <i class="bx bx-trash me-1"></i>Remove current attachment
                                                </label>
                                            </div>
                                        @endif
                                        <input type="file" class="form-control form-control-lg @error('attachment') is-invalid @enderror"
                                            id="attachment" name="attachment" accept=".pdf">
                                        <div class="form-text">
                                            Supported format: PDF only (Max: 2MB)
                                            @if($paymentVoucher->attachment)
                                                <br><span class="text-warning">Leave empty to keep current attachment</span>
                                            @endif
                                        </div>
                                        @error('attachment')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Line Items Section -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="card border-warning">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0 fw-bold">
                                                    <i class="bx bx-list-ul me-2"></i>Line Items
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <div id="lineItemsContainer">
                                                    <!-- Line items will be added here dynamically -->
                                                    @foreach($paymentVoucher->paymentItems as $index => $lineItem)
                                                        @php
                                                            $lineItemCount = $index + 1;
                                                        @endphp
                                                        <div class="line-item-row">
                                                            <div class="row">
                                                                <div class="col-md-5 mb-2">
                                                                    <label for="line_items_{{ $lineItemCount }}_chart_account_id" class="form-label fw-bold">
                                                                        Account <span class="text-danger">*</span>
                                                                    </label>
                                                                    <select class="form-select chart-account-select select2-single" name="line_items[{{ $lineItemCount }}][chart_account_id]" required>
                                                                        <option value="">--- Select Account ---</option>
                                                                        @foreach($chartAccounts as $chartAccount)
                                                                            <option value="{{ $chartAccount->id }}" {{ $lineItem->chart_account_id == $chartAccount->id ? 'selected' : '' }}>
                                                                                {{ $chartAccount->account_name }} ({{ $chartAccount->account_code }})
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-4 mb-2">
                                                                    <label for="line_items_{{ $lineItemCount }}_description" class="form-label fw-bold">
                                                                        Description
                                                                    </label>
                                                                    <input type="text" class="form-control description-input" 
                                                                           name="line_items[{{ $lineItemCount }}][description]" 
                                                                           placeholder="Enter description" value="{{ $lineItem->description }}">
                                                                </div>
                                                                <div class="col-md-2 mb-2">
                                                                    <label for="line_items_{{ $lineItemCount }}_amount" class="form-label fw-bold">
                                                                        Amount <span class="text-danger">*</span>
                                                                    </label>
                                                                    <input type="number" class="form-control amount-input" 
                                                                           name="line_items[{{ $lineItemCount }}][amount]" 
                                                                           step="0.01" min="0" placeholder="0.00" value="{{ $lineItem->amount }}" required>
                                                                </div>
                                                                <div class="col-md-1 mb-2 d-flex align-items-end">
                                                                    <button type="button" class="btn btn-outline-danger btn-sm remove-line-btn" title="Remove Line">
                                                                        <i class="bx bx-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>

                                                <div class="text-left mt-3">
                                                    <button type="button" class="btn btn-success" id="addLineBtn">
                                                        <i class="bx bx-plus me-2"></i>Add Line
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Total and Actions -->
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="d-flex justify-content-start">
                                            <a href="{{ route('accounting.payment-vouchers.show', $paymentVoucher) }}"
                                                class="btn btn-secondary btn-lg me-2">
                                                <i class="bx bx-arrow-back me-2"></i>Cancel
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="d-flex justify-content-end align-items-center">
                                            <div class="me-4">
                                                <h4 class="mb-0 text-danger fw-bold">
                                                    Total Amount: <span id="totalAmount">0.00</span>
                                                </h4>
                                            </div>
                                            @can('edit payment voucher')
                                            <button type="submit" class="btn btn-warning btn-lg" id="updateBtn">
                                                <i class="bx bx-save me-2"></i>Update
                                            </button>
                                            @endcan
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

@push('styles')
    <style>
        .form-control-lg,
        .form-select-lg {
            font-size: 1.1rem;
            padding: 0.75rem 1rem;
        }

        .btn-lg {
            padding: 0.75rem 1.5rem;
            font-size: 1.1rem;
        }

        .line-item-row {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .line-item-row:hover {
            background: #e9ecef;
            border-color: #adb5bd;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .line-item-row .form-label {
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .line-item-row .form-select,
        .line-item-row .form-control {
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .line-item-row {
                padding: 15px;
            }

            .line-item-row .col-md-4,
            .line-item-row .col-md-3 {
                margin-bottom: 15px;
            }

            .line-item-row .col-md-1 {
                margin-bottom: 15px;
                text-align: center;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function () {
            let lineItemCount = {{ $paymentVoucher->paymentItems->count() }};

            // Handle payee type selection
            $('#payee_type').on('change', function() {
                const payeeType = $(this).val();
                
                // Hide all sections first
                $('#customerSection, #supplierSection, #employeeSection, #otherPayeeSection').hide();
                
                // Reset required attributes and disable all fields
                $('#customer_id, #supplier_id, #employee_id, #payee_name').prop('required', false).prop('disabled', true);
                
                // Destroy Select2 instances for hidden fields to prevent issues
                if ($('#customer_id').hasClass('select2-hidden-accessible')) {
                    $('#customer_id').select2('destroy');
                }
                if ($('#supplier_id').hasClass('select2-hidden-accessible')) {
                    $('#supplier_id').select2('destroy');
                }
                if ($('#employee_id').hasClass('select2-hidden-accessible')) {
                    $('#employee_id').select2('destroy');
                }
                
                // Show relevant section based on selection and set required fields
                if (payeeType === 'customer') {
                    $('#customerSection').show();
                    $('#customer_id').prop('required', true).prop('disabled', false);
                    // Reinitialize Select2 for customer
                    setTimeout(function() {
                        $('#customer_id').select2({
                            placeholder: 'Select Customer',
                            allowClear: true,
                            width: '100%',
                            theme: 'bootstrap-5'
                        });
                    }, 100);
                } else if (payeeType === 'supplier') {
                    $('#supplierSection').show();
                    $('#supplier_id').prop('required', true).prop('disabled', false);
                    // Reinitialize Select2 for supplier
                    setTimeout(function() {
                        $('#supplier_id').select2({
                            placeholder: 'Select Supplier',
                            allowClear: true,
                            width: '100%',
                            theme: 'bootstrap-5'
                        });
                    }, 100);
                } else if (payeeType === 'employee') {
                    $('#employeeSection').show();
                    $('#employee_id').prop('required', true).prop('disabled', false);
                    // Reinitialize Select2 for employee
                    setTimeout(function() {
                        $('#employee_id').select2({
                            placeholder: 'Select Employee',
                            allowClear: true,
                            width: '100%',
                            theme: 'bootstrap-5'
                        });
                    }, 100);
                } else if (payeeType === 'other') {
                    $('#otherPayeeSection').show();
                    $('#payee_name').prop('required', true).prop('disabled', false);
                }
            });

            // Trigger change event on page load if payee_type has a value
            if ($('#payee_type').val()) {
                $('#payee_type').trigger('change');
            }

            // Handle payment method selection
            function togglePaymentMethodSections() {
                const paymentMethod = $('#payment_method').val();
                
                // Hide all sections first
                $('#bank_account_section').hide();
                $('#cash_deposit_section').hide();
                $('#cheque_section').hide();
                
                // Remove required attributes and clear values for hidden fields
                $('#bank_account_id').removeAttr('required').removeClass('is-invalid');
                $('#cash_deposit_id').removeAttr('required').removeClass('is-invalid');
                $('#cheque_number').removeAttr('required').removeClass('is-invalid');
                $('#cheque_date').removeAttr('required').removeClass('is-invalid');
                
                // Clear validation error messages
                $('#bank_account_id').next('.invalid-feedback').remove();
                $('#cash_deposit_id').next('.invalid-feedback').remove();
                $('#cheque_number').next('.invalid-feedback').remove();
                $('#cheque_date').next('.invalid-feedback').remove();
                
                // Show relevant section based on payment method
                if (paymentMethod === 'bank_transfer' || paymentMethod === 'cheque') {
                    $('#bank_account_section').show();
                    $('#bank_account_id').attr('required', 'required');
                    
                    if (paymentMethod === 'cheque') {
                        $('#cheque_section').show();
                        $('#cheque_number').attr('required', 'required');
                        $('#cheque_date').attr('required', 'required');
                        // Set default cheque date if empty
                        if (!$('#cheque_date').val()) {
                            $('#cheque_date').val('{{ date('Y-m-d') }}');
                        }
                        updateChequePayeeName();
                    }
                } else if (paymentMethod === 'cash_deposit') {
                    $('#cash_deposit_section').show();
                    // Only require if payee type is customer
                    if ($('#payee_type').val() === 'customer') {
                        $('#cash_deposit_id').attr('required', 'required');
                        loadCashDeposits();
                    }
                }
            }

            // Update cheque payee name based on selected payee
            function updateChequePayeeName() {
                const payeeType = $('#payee_type').val();
                let payeeName = '';
                
                if (payeeType === 'customer' && $('#customer_id').val()) {
                    payeeName = $('#customer_id option:selected').text();
                } else if (payeeType === 'supplier' && $('#supplier_id').val()) {
                    payeeName = $('#supplier_id option:selected').text();
                } else if (payeeType === 'employee' && $('#employee_id').val()) {
                    payeeName = $('#employee_id option:selected').text();
                } else if (payeeType === 'other' && $('#payee_name').val()) {
                    payeeName = $('#payee_name').val();
                }
                
                $('#cheque_payee_name').val(payeeName);
            }

            // Load cash deposits for selected customer
            function loadCashDeposits() {
                const customerSelect = $('#customer_id');
                const selectedOption = customerSelect.find('option:selected');
                const encodedCustomerId = selectedOption.data('encoded-id');
                const cashDepositSelect = $('#cash_deposit_id');
                
                cashDepositSelect.empty().append('<option value="">-- Select Customer Account --</option>');
                
                if (!encodedCustomerId) {
                    cashDepositSelect.append('<option value="">No customer selected</option>');
                    return;
                }
                
                // Show loading state
                cashDepositSelect.prop('disabled', true);
                cashDepositSelect.empty();
                cashDepositSelect.append('<option value="">Loading customer accounts...</option>');
                
                // Load cash deposits via AJAX
                $.ajax({
                    url: `/accounting/payment-vouchers/customer/${encodedCustomerId}/cash-deposits`,
                    type: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        console.log('Cash deposits response:', response);
                        cashDepositSelect.empty();
                        cashDepositSelect.prop('disabled', false);
                        
                        // Check for error in response
                        if (response.error) {
                            cashDepositSelect.append('<option value="">Error: ' + response.error + '</option>');
                            console.error('API returned error:', response.error);
                        } else if (response.data && Array.isArray(response.data) && response.data.length > 0) {
                            cashDepositSelect.append('<option value="">Select Customer Account</option>');
                            const savedCashDepositId = '{{ $paymentVoucher->cash_deposit_id ?? 'customer_balance' }}';
                            response.data.forEach(function(deposit) {
                                // For customer balance, use 'customer_balance' as value
                                const value = deposit.id === 'customer_balance' ? 'customer_balance' : deposit.id;
                                // Check if this is the saved value
                                const isSelected = (savedCashDepositId === value) || 
                                                 (savedCashDepositId === 'null' && value === 'customer_balance') ||
                                                 (savedCashDepositId === '' && value === 'customer_balance');
                                cashDepositSelect.append(
                                    $('<option></option>')
                                        .attr('value', value)
                                        .prop('selected', isSelected)
                                        .text(deposit.balance_text)
                                );
                            });
                            // Trigger change to update Select2 if it's initialized
                            if (cashDepositSelect.hasClass('select2-hidden-accessible')) {
                                cashDepositSelect.trigger('change');
                            }
                        } else {
                            cashDepositSelect.append('<option value="">No account available for this customer</option>');
                        }
                    },
                    error: function(xhr) {
                        console.error('Failed to load cash deposits:', xhr);
                        cashDepositSelect.empty();
                        cashDepositSelect.prop('disabled', false);
                        cashDepositSelect.append('<option value="">Error loading customer accounts</option>');
                    }
                });
            }

            // Handle payee type change - show/hide cash deposit option
            function toggleCashDepositOption() {
                const payeeType = $('#payee_type').val();
                const cashDepositOption = $('#cash_deposit_option');
                const paymentMethod = $('#payment_method').val();
                
                if (payeeType === 'customer') {
                    // Show cash deposit option
                    cashDepositOption.show();
                } else {
                    // Hide cash deposit option
                    cashDepositOption.hide();
                    
                    // If cash deposit was selected, clear it and reset to default
                    if (paymentMethod === 'cash_deposit') {
                        $('#payment_method').val('bank_transfer').trigger('change');
                        // Hide cash deposit section
                        $('#cash_deposit_section').hide();
                        $('#cash_deposit_id').removeAttr('required').val('').removeClass('is-invalid');
                    }
                }
            }

            // Event listeners for payment method
            $('#payment_method').on('change', togglePaymentMethodSections);
            $('#payee_type').on('change', function() {
                toggleCashDepositOption();
                if ($('#payment_method').val() === 'cheque') {
                    updateChequePayeeName();
                }
                if ($('#payment_method').val() === 'cash_deposit' && $('#payee_type').val() === 'customer') {
                    loadCashDeposits();
                }
            });
            $('#customer_id, #supplier_id, #employee_id, #payee_name').on('change', function() {
                if ($('#payment_method').val() === 'cheque') {
                    updateChequePayeeName();
                }
                if ($('#payment_method').val() === 'cash_deposit' && $('#payee_type').val() === 'customer') {
                    loadCashDeposits();
                }
            });

            // Initialize on page load
            // Check if payee type is already set (from old values)
            const initialPayeeType = $('#payee_type').val();
            if (initialPayeeType !== 'customer') {
                $('#cash_deposit_option').hide();
            }
            toggleCashDepositOption();
            togglePaymentMethodSections();
            
            // If payment method is cash_deposit and customer is selected, load and select cash deposit
            const initialPaymentMethod = $('#payment_method').val();
            const initialCustomerId = $('#customer_id').val();
            if (initialPaymentMethod === 'cash_deposit' && initialPayeeType === 'customer' && initialCustomerId) {
                // Wait a bit for the section to be shown, then load deposits
                setTimeout(function() {
                    loadCashDeposits();
                    // Initialize Select2 for cash deposit select after loading
                    setTimeout(function() {
                        if (!$('#cash_deposit_id').hasClass('select2-hidden-accessible')) {
                            $('#cash_deposit_id').select2({
                                placeholder: 'Select Customer Account',
                                allowClear: true,
                                width: '100%',
                                theme: 'bootstrap-5'
                            });
                        }
                    }, 500);
                }, 300);
            }

            // Initialize Select2 for existing chart account selects
            $('.chart-account-select').select2({
                placeholder: 'Select Account',
                allowClear: true,
                width: '100%',
                theme: 'bootstrap-5'
            });

            // Initialize Select2 for all select elements with select2-single class
            $('.select2-single').select2({
                placeholder: 'Select an option',
                allowClear: true,
                width: '100%',
                theme: 'bootstrap-5'
            });
            
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
                    $('#exchange_rate').prop('required', true);
                    fetchExchangeRate(selectedCurrency);
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

            // Initialize with existing line items (already rendered in HTML)
            // No need to add sample items since they're already in the DOM

            // Add line item button
            $('#addLineBtn').on('click', function () {
                addLineItem();
            });

            // Remove line item
            $(document).on('click', '.remove-line-btn', function () {
                $(this).closest('.line-item-row').remove();
                calculateTotal();
            });

            // Calculate total when amounts change
            $(document).on('input', '.amount-input', function () {
                calculateTotal();
            });

            // Form validation
            $('#paymentVoucherForm').on('submit', function (e) {
                e.preventDefault();

                // Validate required fields
                if (!validateForm()) {
                    return false;
                }

                // Disable submit button and show loading state
                $('#updateBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-2"></i>Updating...');

                // Submit form
                this.submit();
            });

            function addLineItem(accountName = '', amount = '', description = '') {
                lineItemCount++;
                const lineItemHtml = `
                                    <div class="line-item-row">
                                        <div class="row">
                                            <div class="col-md-5 mb-2">
                                                <label for="line_items_${lineItemCount}_chart_account_id" class="form-label fw-bold">
                                                    Account <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select chart-account-select select2-single" name="line_items[${lineItemCount}][chart_account_id]" required>
                                                    <option value="">--- Select Account ---</option>
                                                    @foreach($chartAccounts as $chartAccount)
                                                        <option value="{{ $chartAccount->id }}" ${accountName.includes('{{ $chartAccount->account_name }}') ? 'selected' : ''}>
                                                            {{ $chartAccount->account_name }} ({{ $chartAccount->account_code }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label for="line_items_${lineItemCount}_description" class="form-label fw-bold">
                                                    Description
                                                </label>
                                                <input type="text" class="form-control description-input" 
                                                       name="line_items[${lineItemCount}][description]" 
                                                       placeholder="Enter description" value="${description}">
                                            </div>
                                            <div class="col-md-2 mb-2">
                                                <label for="line_items_${lineItemCount}_amount" class="form-label fw-bold">
                                                    Amount <span class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control amount-input" 
                                                       name="line_items[${lineItemCount}][amount]" 
                                                       step="0.01" min="0" placeholder="0.00" value="${amount}" required>
                                            </div>
                                            <div class="col-md-1 mb-2 d-flex align-items-end">
                                                <button type="button" class="btn btn-outline-danger btn-sm remove-line-btn" title="Remove Line">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                `;

                $('#lineItemsContainer').append(lineItemHtml);
                
                // Initialize Select2 for the new chart account select
                setTimeout(function() {
                    $('#lineItemsContainer .chart-account-select').last().select2({
                        placeholder: 'Select Account',
                        allowClear: true,
                        width: '100%',
                        theme: 'bootstrap-5'
                    });
                }, 100);
            }

            function calculateTotal() {
                let total = 0;
                $('.amount-input').each(function () {
                    const amount = parseFloat($(this).val()) || 0;
                    total += amount;
                });

                $('#totalAmount').text(total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                // Update update button state
                if (total > 0) {
                    $('#updateBtn').prop('disabled', false);
                } else {
                    $('#updateBtn').prop('disabled', true);
                }
            }

            function validateForm() {
                let isValid = true;

                // Validate payee information
                const payeeType = $('#payee_type').val();
                if (!payeeType) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please select a payee type.',
                        confirmButtonColor: '#dc3545'
                    });
                    isValid = false;
                    return isValid;
                }

                if (payeeType === 'customer' && !$('#customer_id').val()) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please select a customer.',
                        confirmButtonColor: '#dc3545'
                    });
                    isValid = false;
                    return isValid;
                }

                if (payeeType === 'supplier' && !$('#supplier_id').val()) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please select a supplier.',
                        confirmButtonColor: '#dc3545'
                    });
                    isValid = false;
                    return isValid;
                }

                if (payeeType === 'employee' && !$('#employee_id').val()) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please select an employee.',
                        confirmButtonColor: '#dc3545'
                    });
                    isValid = false;
                    return isValid;
                }

                if (payeeType === 'other' && !$('#payee_name').val().trim()) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please enter a payee name.',
                        confirmButtonColor: '#dc3545'
                    });
                    isValid = false;
                    return isValid;
                }

                // Check if at least one line item has both account and amount
                let hasValidLineItem = false;
                $('.line-item-row').each(function () {
                    const account = $(this).find('.chart-account-select').val();
                    const amount = parseFloat($(this).find('.amount-input').val()) || 0;

                    if (account && amount > 0) {
                        hasValidLineItem = true;
                    }
                });

                if (!hasValidLineItem) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please add at least one line item with account and amount.',
                        confirmButtonColor: '#dc3545'
                    });
                    isValid = false;
                }

                return isValid;
            }

            // Initialize total calculation
            calculateTotal();

            // WHT Calculation
            function calculateWHT() {
                const totalAmount = parseFloat($('#totalAmount').text().replace(/,/g, '')) || 0;
                const treatment = $('#wht_treatment').val() || 'EXCLUSIVE';
                const whtRate = parseFloat($('#wht_rate').val()) || 0;
                const vatMode = $('#vat_mode').val() || 'EXCLUSIVE';
                const vatRate = parseFloat($('#vat_rate').val()) || {{ get_default_vat_rate() }};

                // Calculate base amount (excluding VAT) based on VAT mode
                let baseAmount = totalAmount;
                let vatAmount = 0;

                if (vatMode === 'INCLUSIVE' && vatRate > 0) {
                    // VAT is included in total, extract base
                    baseAmount = totalAmount / (1 + (vatRate / 100));
                    vatAmount = totalAmount - baseAmount;
                } else if (vatMode === 'EXCLUSIVE' && vatRate > 0) {
                    // VAT is exclusive: total amount IS the base amount (before VAT)
                    // VAT will be added separately
                    baseAmount = totalAmount;
                    vatAmount = baseAmount * (vatRate / 100);
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
                    // When no WHT: net payable = base amount + VAT (if exclusive) or total amount (if inclusive)
                    let netPayable = baseAmount;
                    if (vatMode === 'EXCLUSIVE' && vatRate > 0) {
                        netPayable = baseAmount + vatAmount;
                    } else if (vatMode === 'INCLUSIVE') {
                        netPayable = totalAmount; // VAT already included
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

                // Calculate net payable based on VAT mode
                let netPayable = net;
                if (vatMode === 'EXCLUSIVE' && vatRate > 0) {
                    // VAT is exclusive: net payable = (base - WHT) + VAT
                    netPayable = net + vatAmount;
                } else if (vatMode === 'INCLUSIVE') {
                    // VAT is inclusive: net payable = base - WHT (VAT already included in total)
                    // But we need to show the actual amount to pay, which is totalAmount - WHT
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
            $(document).on('input', '.amount-input', function() {
                calculateTotal();
                calculateWHT();
            });

            $('#wht_treatment, #wht_rate, #vat_mode, #vat_rate').on('change input', calculateWHT);
            
            // Initialize WHT calculation
            calculateWHT();
        });
    </script>
@endpush 