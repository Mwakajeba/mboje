@extends('layouts.main')

@php
use Vinkla\Hashids\Facades\Hashids;
@endphp

@section('title', 'Edit Receipt Voucher')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
                ['label' => 'Receipt Vouchers', 'url' => route('accounting.receipt-vouchers.index'), 'icon' => 'bx bx-receipt'],
                ['label' => 'Edit Voucher', 'url' => '#', 'icon' => 'bx bx-edit']
            ]" />
            
            <div class="row">
                <div class="col-12">
                    <div class="card radius-10">
                        <div class="card-header bg-secondary text-dark">
                            <div class="d-flex align-items-center">
                                <div>
                                    <h5 class="mb-0 text-dark">
                                        <i class="bx bx-edit me-2"></i>Edit Receipt Voucher
                                    </h5>
                                    <p class="mb-0 opacity-75">Update receipt voucher entry</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            @if($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <h6 class="alert-heading"><i class="bx bx-error-circle me-2"></i>Validation Errors</h6>
                                    <ul class="mb-0">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            @if(session('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <form id="receiptVoucherForm" action="{{ route('accounting.receipt-vouchers.update', Hashids::encode($receiptVoucher->id)) }}"
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
                                                class="form-control @error('date') is-invalid @enderror"
                                                id="date" name="date" value="{{ old('date', $receiptVoucher->date ? $receiptVoucher->date->format('Y-m-d') : '') }}" required>
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
                                                class="form-control @error('reference') is-invalid @enderror"
                                                id="reference" name="reference" value="{{ old('reference', $receiptVoucher->reference) }}"
                                                placeholder="Enter reference number">
                                            @error('reference')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Method Section -->
                                <div class="row mb-4">
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="bx bx-credit-card me-1"></i>Payment Method <span class="text-danger">*</span>
                                            </label>
                                            <select
                                                class="form-select form-select-lg @error('payment_method') is-invalid @enderror"
                                                id="payment_method" name="payment_method" required>
                                                <option value="">-- Select Payment Method --</option>
                                                @php
                                                    // Get raw payment_method value (not the accessor)
                                                    $rawPaymentMethod = $receiptVoucher->getAttributes()['payment_method'] ?? $receiptVoucher->getOriginal('payment_method') ?? old('payment_method', 'bank_transfer');
                                                @endphp
                                                <option value="bank_transfer" {{ old('payment_method', $rawPaymentMethod) == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                                <option value="cash" {{ old('payment_method', $rawPaymentMethod) == 'cash' ? 'selected' : '' }}>Cash</option>
                                                <option value="cheque" {{ old('payment_method', $rawPaymentMethod) == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                            </select>
                                            @error('payment_method')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Bank Account Section -->
                                <div class="row mb-4" id="bank_account_section">
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="bx bx-wallet me-1"></i>Bank Account <span class="text-danger">*</span>
                                            </label>
                                            <select
                                                class="form-select form-select-lg select2-single mt-2 @error('bank_account_id') is-invalid @enderror"
                                                id="bank_account_id" name="bank_account_id">
                                                <option value="">-- Select Bank Account --</option>
                                                @foreach($bankAccounts as $bankAccount)
                                                    <option value="{{ $bankAccount->id }}" {{ old('bank_account_id', $receiptVoucher->bank_account_id) == $bankAccount->id ? 'selected' : '' }}>
                                                        {{ $bankAccount->name }} - {{ $bankAccount->account_number }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('bank_account_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Cheque Section (hidden by default) -->
                                <div class="row mb-4" id="cheque_section" style="display: none;">
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="bx bx-money me-1"></i>Cheque Number
                                            </label>
                                            <input type="text"
                                                class="form-control @error('cheque_number') is-invalid @enderror"
                                                id="cheque_number" name="cheque_number"
                                                value="{{ old('cheque_number', $receiptVoucher->cheque ? $receiptVoucher->cheque->cheque_number : '') }}"
                                                placeholder="Enter cheque number">
                                            @error('cheque_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="bx bx-calendar me-1"></i>Cheque Date
                                            </label>
                                            <input type="date"
                                                class="form-control @error('cheque_date') is-invalid @enderror"
                                                id="cheque_date" name="cheque_date"
                                                value="{{ old('cheque_date', $receiptVoucher->cheque ? $receiptVoucher->cheque->cheque_date->format('Y-m-d') : date('Y-m-d')) }}">
                                            @error('cheque_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    @if($receiptVoucher->payment_method === 'cheque' && $receiptVoucher->cheque_deposited)
                                    <div class="col-lg-12">
                                        <div class="alert alert-info">
                                            <i class="bx bx-info-circle me-2"></i>
                                            <strong>Cheque Already Deposited:</strong> This cheque was deposited on {{ $receiptVoucher->cheque_deposited_at ? $receiptVoucher->cheque_deposited_at->format('d M Y') : 'N/A' }}
                                            @if($receiptVoucher->chequeDepositedBy)
                                                by {{ $receiptVoucher->chequeDepositedBy->name }}
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                </div>

                                <!-- Currency Section -->
                                <div class="row mb-4">
                                    <div class="col-lg-6">
                                        @php
                                            $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', auth()->user()->company->functional_currency ?? 'TZS');
                                        @endphp
                                        <div class="mb-3">
                                            <label for="currency" class="form-label fw-bold">
                                                <i class="bx bx-money me-1"></i>Currency
                                            </label>
                                            <select class="form-select form-select-lg select2-single @error('currency') is-invalid @enderror"
                                                id="currency" name="currency">
                                                @if(isset($currencies) && $currencies->isNotEmpty())
                                                    @foreach($currencies as $currency)
                                                        <option value="{{ $currency->currency_code }}" 
                                                                {{ old('currency', $receiptVoucher->currency ?? $functionalCurrency) == $currency->currency_code ? 'selected' : '' }}>
                                                            {{ $currency->currency_name ?? $currency->currency_code }} ({{ $currency->currency_code }})
                                                        </option>
                                                    @endforeach
                                                @else
                                                    <option value="{{ $functionalCurrency }}" selected>{{ $functionalCurrency }}</option>
                                                @endif
                                            </select>
                                            <small class="text-muted">Currencies from FX RATES MANAGEMENT</small>
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
                                                <input type="number" class="form-control @error('exchange_rate') is-invalid @enderror"
                                                    id="exchange_rate" name="exchange_rate" 
                                                    value="{{ old('exchange_rate', number_format($receiptVoucher->exchange_rate ?? 1, 6, '.', '')) }}" 
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
                                                                <option value="customer" {{ old('payee_type', $receiptVoucher->payee_type) == 'customer' ? 'selected' : '' }}>Customer</option>
                                                                <option value="employee" {{ old('payee_type', $receiptVoucher->payee_type) == 'employee' ? 'selected' : '' }}>Employee</option>
                                                                <option value="other" {{ old('payee_type', $receiptVoucher->payee_type) == 'other' ? 'selected' : '' }}>Other</option>
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
                                                        @php
                                                            // Use payee_id if payee_type is customer, otherwise check customer_id
                                                            $selectedCustomerId = ($receiptVoucher->payee_type == 'customer' && $receiptVoucher->payee_id) 
                                                                ? $receiptVoucher->payee_id 
                                                                : ($receiptVoucher->customer_id ?? old('customer_id'));
                                                        @endphp
                                                        <option value="{{ $customer->id }}" {{ $selectedCustomerId == $customer->id ? 'selected' : '' }}>
                                                            {{ $customer->name }} ({{ $customer->customerNo }})
                                                        </option>
                                                    @endforeach
                                            </select>
                                            @error('customer_id')
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
                                                                    @php
                                                                        // Use payee_id if payee_type is employee
                                                                        $selectedEmployeeId = ($receiptVoucher->payee_type == 'employee' && $receiptVoucher->payee_id) 
                                                                            ? $receiptVoucher->payee_id 
                                                                            : old('employee_id');
                                                                    @endphp
                                                                    <option value="{{ $employee->id }}" {{ $selectedEmployeeId == $employee->id ? 'selected' : '' }}>
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
                                                                value="{{ old('payee_name', $receiptVoucher->payee_name) }}"
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

                                <!-- Customer Invoices Section (shown when customer is selected) -->
                                <div class="row mb-4" id="customerInvoicesSection" style="display: none;">
                                    <div class="col-12">
                                        <div class="card border-primary">
                                            <div class="card-header bg-primary text-white">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0 fw-bold">
                                                        <i class="bx bx-receipt me-2"></i>Unpaid Customer Invoices
                                                    </h6>
                                                    <div>
                                                        <button type="button" class="btn btn-sm btn-light" id="selectAllInvoices">
                                                            <i class="bx bx-check-square me-1"></i>Select All
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-light" id="deselectAllInvoices">
                                                            <i class="bx bx-square me-1"></i>Deselect All
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-light" id="addSelectedInvoices">
                                                            <i class="bx bx-plus me-1"></i>Add Selected to Line Items
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div id="customerInvoicesContainer">
                                                    <div class="text-center py-4">
                                                        <i class="bx bx-loader-alt bx-spin fs-3 text-primary"></i>
                                                        <p class="mt-2 text-muted">Select a customer to load invoices...</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- WHT Section -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="card border-info">
                                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0 fw-bold">
                                                    <i class="bx bx-calculator me-2"></i>Withholding Tax (WHT)
                                                </h6>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="wht_enabled_switch" name="wht_enabled" value="1" {{ old('wht_enabled', ($receiptVoucher->wht_treatment && $receiptVoucher->wht_treatment != 'NONE' && $receiptVoucher->wht_rate > 0) ? true : false) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="wht_enabled_switch">
                                                        Enable WHT
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="card-body" id="wht_fields_container" style="display: {{ old('wht_enabled', ($receiptVoucher->wht_treatment && $receiptVoucher->wht_treatment != 'NONE' && $receiptVoucher->wht_rate > 0) ? true : false) ? 'block' : 'none' }};">
                                                <div class="row">
                                                    <div class="col-md-3 mb-3">
                                                        <label for="wht_treatment" class="form-label fw-bold">
                                                            WHT Treatment
                                                        </label>
                                                        <select class="form-select @error('wht_treatment') is-invalid @enderror"
                                                            id="wht_treatment" name="wht_treatment">
                                                            <option value="EXCLUSIVE" {{ old('wht_treatment', $receiptVoucher->wht_treatment ?? 'EXCLUSIVE') == 'EXCLUSIVE' ? 'selected' : '' }}>Exclusive</option>
                                                            <option value="INCLUSIVE" {{ old('wht_treatment', $receiptVoucher->wht_treatment) == 'INCLUSIVE' ? 'selected' : '' }}>Inclusive</option>
                                                            <option value="NONE" {{ old('wht_treatment', $receiptVoucher->wht_treatment) == 'NONE' ? 'selected' : '' }}>None</option>
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
                                                    <div class="col-md-3 mb-3">
                                                        <label for="wht_rate" class="form-label fw-bold">
                                                            WHT Rate (%)
                                                        </label>
                                                        <input type="number" class="form-control @error('wht_rate') is-invalid @enderror"
                                                            id="wht_rate" name="wht_rate" value="{{ old('wht_rate', $receiptVoucher->wht_rate ?? 0) }}"
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
                                                                $selectedVatMode = old('vat_mode', $receiptVoucher->vat_mode ?? $defaultVatMode);
                                                            @endphp
                                                            <script nonce="{{ $cspNonce ?? '' }}">
                                                                // Make defaultVatMode available to JavaScript
                                                                window.defaultVatMode = '{{ $defaultVatMode }}';
                                                            </script>
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
                                                            id="vat_rate" name="vat_rate" value="{{ old('vat_rate', $receiptVoucher->vat_rate ?? get_default_vat_rate()) }}"
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

                                <!-- Transaction Description and Attachment -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="description" class="form-label fw-bold">
                                                <i class="bx bx-message-square-detail me-1"></i>Transaction Description
                                            </label>
                                            <textarea class="form-control @error('description') is-invalid @enderror"
                                                id="description" name="description" rows="3"
                                                placeholder="Enter transaction description">{{ old('description', $receiptVoucher->description) }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="attachment" class="form-label fw-bold">
                                                <i class="bx bx-paperclip me-1"></i>Attachment (Optional)
                                            </label>
                                            @if($receiptVoucher->attachment)
                                                <div class="mb-2">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bx bx-file-pdf me-2 text-danger"></i>
                                                        <span class="me-3">{{ basename($receiptVoucher->attachment) }}</span>
                                                        <a href="{{ route('accounting.receipt-vouchers.download-attachment', Hashids::encode($receiptVoucher->id)) }}" 
                                                           class="btn btn-sm btn-outline-primary me-2">
                                                            <i class="bx bx-download"></i> Download
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="removeAttachment()">
                                                            <i class="bx bx-trash"></i> Remove
                                                        </button>
                                                    </div>
                                                    <input type="hidden" name="remove_attachment" id="remove_attachment" value="0">
                                                </div>
                                            @endif
                                            <input type="file"
                                                class="form-control @error('attachment') is-invalid @enderror"
                                                id="attachment" name="attachment" accept=".pdf">
                                            <div class="form-text">Supported format: PDF only (Max: 2MB)</div>
                                            @error('attachment')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
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
                                                    @php
                                                        // Get linked invoice receipts to identify invoice-based line items
                                                        $invoiceReceipts = \App\Models\Receipt::where('reference', 'like', $receiptVoucher->reference . '-INV-%')
                                                            ->orWhere(function($query) use ($receiptVoucher) {
                                                                $query->where('reference_type', 'sales_invoice')
                                                                      ->where('payee_id', $receiptVoucher->payee_id)
                                                                      ->where('date', $receiptVoucher->date)
                                                                      ->where('user_id', $receiptVoucher->user_id);
                                                            })
                                                            ->get()
                                                            ->keyBy('reference_number'); // Key by invoice_number for easy lookup
                                                    @endphp
                                                    @foreach($receiptVoucher->receiptItems as $index => $lineItem)
                                                        @php
                                                            $lineItemCount = $index + 1;
                                                            // Check if this line item is from an invoice
                                                            // Match by description pattern "Receipt for Invoice {number}" or "Payment for Invoice {number}" (backward compatibility)
                                                            $isInvoiceItem = false;
                                                            $invoiceId = null;
                                                            $invoiceNumber = null;
                                                            
                                                            if (preg_match('/(?:Receipt|Payment) for Invoice\s+([A-Z0-9\-]+)/i', $lineItem->description ?? '', $matches)) {
                                                                $invoiceNumber = $matches[1];
                                                                $invoiceReceipt = $invoiceReceipts->get($invoiceNumber);
                                                                if ($invoiceReceipt) {
                                                                    $isInvoiceItem = true;
                                                                    // Try to find the invoice ID from the invoice number
                                                                    $salesInvoice = \App\Models\Sales\SalesInvoice::where('invoice_number', $invoiceNumber)
                                                                        ->where('customer_id', $receiptVoucher->payee_id)
                                                                        ->first();
                                                                    if ($salesInvoice) {
                                                                        $invoiceId = $salesInvoice->id;
                                                                    }
                                                                }
                                                            }
                                                        @endphp
                                                        <div class="line-item-row" @if($isInvoiceItem) data-invoice-item="true" data-invoice-id="{{ $invoiceId }}" @endif>
                                                            <div class="row">
                                                                @if($isInvoiceItem)
                                                                    {{-- Invoice item - hide account field --}}
                                                                    <input type="hidden" name="line_items[{{ $lineItemCount }}][chart_account_id]" value="{{ $lineItem->chart_account_id }}">
                                                                    <input type="hidden" name="line_items[{{ $lineItemCount }}][invoice_id]" value="{{ $invoiceId }}">
                                                                    <input type="hidden" name="line_items[{{ $lineItemCount }}][invoice_number]" value="{{ $invoiceNumber }}">
                                                                    <div class="col-md-7 mb-2">
                                                                        <label for="line_items_{{ $lineItemCount }}_description" class="form-label fw-bold">
                                                                            Description
                                                                        </label>
                                                                        <input type="text" class="form-control description-input" 
                                                                               name="line_items[{{ $lineItemCount }}][description]" 
                                                                               value="{{ $lineItem->description }}" readonly>
                                                                    </div>
                                                                @else
                                                                    {{-- Regular item - show account field --}}
                                                                    <div class="col-md-4 mb-2 account-field">
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
                                                                @endif
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
                                            <a href="{{ route('accounting.receipt-vouchers.show', Hashids::encode($receiptVoucher->id)) }}"
                                                class="btn btn-secondary me-2">
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
                                            @can('edit receipt voucher')
                                            <button type="submit" class="btn btn-primary" id="updateBtn">
                                                <i class="bx bx-edit me-2"></i>Update
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
        .form-control,
        .form-select {
            font-size: 0.9rem;
            padding: 0.5rem 0.75rem;
        }

        .form-label {
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .btn {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
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
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
        }

        .line-item-row .form-select,
        .line-item-row .form-control {
            font-size: 0.85rem;
        }

        .line-item-row .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
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
    <script nonce="{{ $cspNonce ?? '' }}">
        $(document).ready(function () {
            // Get Accounts Receivable account ID using the same robust logic as controller
            @php
                $arAccountId = null;
                $settingValue = \App\Models\SystemSetting::where('key', 'inventory_default_receivable_account')->value('value');
                if ($settingValue) {
                    $arAccountId = (int) $settingValue;
                } else {
                    // Fallback: Try ID 18 first, then ID 2
                    $account18 = \App\Models\ChartAccount::find(18);
                    if ($account18 && stripos($account18->account_name, 'Accounts Receivable') !== false) {
                        $arAccountId = 18;
                    } else {
                        $account2 = \App\Models\ChartAccount::find(2);
                        if ($account2 && stripos($account2->account_name, 'Accounts Receivable') !== false) {
                            $arAccountId = 2;
                        } else {
                            // Last resort: find by name
                            $account = \App\Models\ChartAccount::where('account_name', 'like', '%Accounts Receivable%')->first();
                            $arAccountId = $account ? $account->id : 18; // Default to 18 if nothing found
                        }
                    }
                }
                if (!$arAccountId) {
                    $arAccountId = 18; // Final fallback
                }
            @endphp
            const defaultArAccountId = {{ $arAccountId }};
            
            let lineItemCount = {{ $receiptVoucher->receiptItems->count() }};
            // Track which invoices have been added to line items
            let addedInvoices = {}; // {invoiceId: {invoiceNumber, amount, rowElement}}
            
            // Initialize tracking for existing invoice line items
            $('.line-item-row[data-invoice-item="true"]').each(function() {
                const $row = $(this);
                const invoiceId = $row.data('invoice-id');
                const invoiceNumber = $row.find('input[name*="[invoice_number]"]').val();
                const amount = parseFloat($row.find('.amount-input').val()) || 0;
                
                if (invoiceId && invoiceNumber) {
                    addedInvoices[invoiceId] = {
                        invoice_number: invoiceNumber,
                        amount: amount,
                        rowElement: $row
                    };
                }
            });

            // Initialize Select2 for all select fields
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
                const receiptDate = $('#date').val() || new Date().toISOString().split('T')[0];
                handleCurrencyChange(selectedCurrency, receiptDate);
            }).on('change', function() {
                const selectedCurrency = $(this).val();
                const receiptDate = $('#date').val() || new Date().toISOString().split('T')[0];
                handleCurrencyChange(selectedCurrency, receiptDate);
            });
            
            // Handle date change - fetch rate when date changes
            $('#date').on('change', function() {
                const currency = $('#currency').val();
                const receiptDate = $(this).val() || new Date().toISOString().split('T')[0];
                if (currency && currency !== functionalCurrency) {
                    fetchExchangeRate(currency, receiptDate);
                }
            });
            
            function handleCurrencyChange(selectedCurrency, receiptDate = null) {
                receiptDate = receiptDate || $('#date').val() || new Date().toISOString().split('T')[0];
                if (selectedCurrency && selectedCurrency !== functionalCurrency) {
                    $('#exchange_rate').prop('required', true);
                    fetchExchangeRate(selectedCurrency, receiptDate);
                } else {
                    $('#exchange_rate').prop('required', false);
                    $('#exchange_rate').val('1.000000');
                    $('#rate-info').hide();
                }
            }
            
            // Fetch exchange rate button
            $('#fetch-rate-btn').on('click', function() {
                const currency = $('#currency').val();
                const receiptDate = $('#date').val() || new Date().toISOString().split('T')[0];
                fetchExchangeRate(currency, receiptDate);
            });
            
            // Function to fetch exchange rate from FX RATES MANAGEMENT
            function fetchExchangeRate(currency = null, receiptDate = null) {
                currency = currency || $('#currency').val();
                receiptDate = receiptDate || $('#date').val() || new Date().toISOString().split('T')[0];
                if (!currency || currency === functionalCurrency) {
                    $('#exchange_rate').val('1.000000');
                    $('#rate-info').hide();
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
                        date: receiptDate || new Date().toISOString().split('T')[0], // Use receipt date instead of today
                        rate_type: 'spot'
                    },
                    success: function(response) {
                        if (response.success && response.rate) {
                            const rate = parseFloat(response.rate);
                            const dateUsed = receiptDate || $('#date').val() || new Date().toISOString().split('T')[0];
                            rateInput.val(rate.toFixed(6));
                            $('#rate-source').text(`Rate from ${response.source || 'FX RATES MANAGEMENT'} for ${dateUsed}: 1 ${currency} = ${rate.toFixed(6)} ${functionalCurrency}`);
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
                                const dateUsed = receiptDate || $('#date').val() || new Date().toISOString().split('T')[0];
                                $('#rate-source').text(`Rate fetched (fallback API) for ${dateUsed}: 1 ${currency} = ${rate.toFixed(6)} ${functionalCurrency}`);
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

            // Handle payment method change
            $('#payment_method').on('change', function() {
                const paymentMethod = $(this).val();
                
                // Hide all sections
                $('#bank_account_section, #cheque_section').hide();
                $('#bank_account_id, #cheque_number, #cheque_date').removeAttr('required');
                
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
                    }
                } else if (paymentMethod === 'cash') {
                    // For cash, bank account is optional
                    $('#bank_account_section').show();
                }
            });
            
            // Trigger change on page load if value exists
            if ($('#payment_method').val()) {
                $('#payment_method').trigger('change');
            }

            // Handle payee type change
            $('#payee_type').change(function() {
                const payeeType = $(this).val();
                
                // Hide all sections
                $('#customerSection, #employeeSection, #otherPayeeSection').hide();
                
                // Reset required attributes
                $('#customer_id, #employee_id, #payee_name').prop('required', false);
                
                if (payeeType === 'customer') {
                    $('#customerSection').show();
                    $('#customer_id').prop('required', true);
                    // Reinitialize Select2 for customer
                    setTimeout(function() {
                        // Initialize Select2 with the current value
                        $('#customer_id').select2({
                            placeholder: 'Select Customer',
                            allowClear: true,
                            width: '100%',
                            theme: 'bootstrap-5'
                        });
                        
                        // Ensure the value is set in Select2 (in case it wasn't picked up initially)
                        const currentValue = $('#customer_id').val();
                        if (currentValue) {
                            $('#customer_id').val(currentValue).trigger('change.select2');
                        }
                        
                        // Load invoices when customer is selected
                        $('#customer_id').off('change.invoiceLoader').on('change.invoiceLoader', function() {
                            loadCustomerInvoices($(this).val());
                        });
                        // Load invoices if customer is already selected
                        if (currentValue) {
                            loadCustomerInvoices(currentValue);
                        }
                    }, 100);
                } else if (payeeType === 'employee') {
                    $('#employeeSection').show();
                    $('#employee_id').prop('required', true);
                } else if (payeeType === 'other') {
                    $('#otherPayeeSection').show();
                    $('#payee_name').prop('required', true);
                } else {
                    $('#customerSection').hide();
                    $('#otherPayeeSection').hide();
                    $('#customer_id').prop('required', false);
                    $('#payee_name').prop('required', false);
                }
            });

            // Trigger change event on page load if value exists
            // This will show the appropriate section and initialize Select2
            if ($('#payee_type').val()) {
                $('#payee_type').trigger('change');
            } else {
                // If no payee type is set, check if customer_id has a value and set payee_type to customer
                if ($('#customer_id').val()) {
                    $('#payee_type').val('customer').trigger('change');
                }
            }
            
            // Initialize Select2 for existing chart account selects
            $('.chart-account-select').select2({
                placeholder: 'Select Account',
                allowClear: true,
                width: '100%',
                theme: 'bootstrap-5'
            });

            // Attachment removal function
            window.removeAttachment = function() {
                if (confirm('Are you sure you want to remove the attachment?')) {
                    $('#remove_attachment').val('1');
                    $('.attachment-display').hide();
                }
            };

            // Add line item button
            $('#addLineBtn').on('click', function () {
                addLineItem();
            });

            // Remove line item - handles both invoice and non-invoice items
            $(document).on('click', '.remove-line-btn', function () {
                const $row = $(this).closest('.line-item-row');
                const invoiceId = $row.find('input[name*="[invoice_id]"]').val();
                
                // Remove from tracking if it's an invoice item
                if (invoiceId && addedInvoices[invoiceId]) {
                    delete addedInvoices[invoiceId];
                    // Refresh invoice display
                    refreshInvoiceDisplay();
                }
                
                // Remove the row
                $row.remove();
                
                // Recalculate totals
                calculateTotal();
                calculateWHT();
            });

            // Calculate total when amounts change
            $(document).on('input', '.amount-input', function () {
                calculateTotal();
                calculateWHT();
            });

            // WHT Switch Toggle
            $('#wht_enabled_switch').on('change', function() {
                const isEnabled = $(this).is(':checked');
                if (isEnabled) {
                    $('#wht_fields_container').slideDown(300);
                } else {
                    $('#wht_fields_container').slideUp(300);
                    // Reset WHT values when disabled
                    $('#wht_treatment').val('NONE');
                    $('#wht_rate').val('0');
                    calculateWHT();
                }
            });

            // Calculate WHT when treatment or rate changes (only if enabled)
            $('#wht_treatment, #wht_rate, #vat_mode, #vat_rate').on('change input', function() {
                if ($('#wht_enabled_switch').is(':checked')) {
                    calculateWHT();
                }
            });

            function calculateWHT() {
                // Don't calculate if WHT is disabled
                if (!$('#wht_enabled_switch').is(':checked')) {
                    return;
                }
                
                const totalAmount = parseFloat($('#totalAmount').text().replace(/,/g, '')) || 0;
                const treatment = $('#wht_treatment').val() || 'EXCLUSIVE';
                const whtRate = parseFloat($('#wht_rate').val()) || 0;
                const vatMode = $('#vat_mode').val() || (typeof window.defaultVatMode !== 'undefined' ? window.defaultVatMode : 'EXCLUSIVE');
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
                    // When no WHT: net receivable = base amount + VAT (if exclusive) or total amount (if inclusive)
                    let netReceivable = baseAmount;
                    if (vatMode === 'EXCLUSIVE' && vatRate > 0) {
                        netReceivable = baseAmount + vatAmount;
                    } else if (vatMode === 'INCLUSIVE') {
                        netReceivable = totalAmount; // VAT already included
                    }
                    $('#wht_net_receivable').text(netReceivable.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    return;
                }

                // Calculate WHT on base amount (never on VAT)
                let wht = 0;
                let net = baseAmount;

                // Calculate based on treatment
                const rateDecimal = whtRate / 100;
                
                if (treatment === 'EXCLUSIVE') {
                    wht = baseAmount * rateDecimal;
                    net = baseAmount - wht;
                } else if (treatment === 'INCLUSIVE') {
                    wht = baseAmount * (rateDecimal / (1 + rateDecimal));
                    net = baseAmount - wht;
                }

                // Round WHT calculations
                wht = Math.round(wht * 100) / 100;
                net = Math.round(net * 100) / 100;

                // Calculate net receivable based on VAT mode
                let netReceivable = net;
                if (vatMode === 'EXCLUSIVE' && vatRate > 0) {
                    // VAT is exclusive: net receivable = (base - WHT) + VAT
                    netReceivable = net + vatAmount;
                } else if (vatMode === 'INCLUSIVE') {
                    // VAT is inclusive: net receivable = totalAmount - WHT (VAT already included in total)
                    netReceivable = totalAmount - wht;
                }

                // Update display
                $('#wht_amount_preview').text(wht.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#wht_net_receivable').text(netReceivable.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            }

            // Initialize WHT calculation
            calculateTotal();
            calculateWHT();

            // Auto-fetch rate on page load if currency is not functional currency
            $(document).ready(function() {
                const initialCurrency = $('#currency').val();
                const initialDate = $('#date').val() || new Date().toISOString().split('T')[0];
                if (initialCurrency && initialCurrency !== functionalCurrency) {
                    fetchExchangeRate(initialCurrency, initialDate);
                }
            });

            // Form validation
            $('#receiptVoucherForm').on('submit', function (e) {
                // Validate required fields
                const payeeType = $('#payee_type').val();
                if (!payeeType) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please select a payee type.',
                        confirmButtonColor: '#dc3545'
                    });
                    return false;
                }

                if (payeeType === 'customer' && !$('#customer_id').val()) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please select a customer.',
                        confirmButtonColor: '#dc3545'
                    });
                    return false;
                }

                if (payeeType === 'employee' && !$('#employee_id').val()) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please select an employee.',
                        confirmButtonColor: '#dc3545'
                    });
                    return false;
                }

                if (payeeType === 'other' && !$('#payee_name').val().trim()) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please enter a payee name.',
                        confirmButtonColor: '#dc3545'
                    });
                    return false;
                }

                // Check if at least one line item has both account and amount
                // Handle both regular items (with .chart-account-select) and invoice items (with hidden inputs)
                // Also ignore empty line items (no amount or no account)
                let hasValidLineItem = false;
                $('.line-item-row').each(function () {
                    const $row = $(this);
                    const amount = parseFloat($row.find('.amount-input').val()) || 0;
                    
                    // Skip if amount is 0 or empty
                    if (amount <= 0) {
                        return;
                    }
                    
                    // Check if it's an invoice item (has hidden invoice_id)
                    const invoiceId = $row.find('input[name*="[invoice_id]"]').val();
                    if (invoiceId) {
                        // Invoice item - check if it has hidden chart_account_id
                        const accountId = $row.find('input[name*="[chart_account_id]"]').val();
                        if (accountId && accountId !== '') {
                            hasValidLineItem = true;
                            return false; // Break out of each loop
                        }
                    } else {
                        // Regular item - check if account is selected
                        const account = $row.find('.chart-account-select').val();
                        if (account && account !== '') {
                            hasValidLineItem = true;
                            return false; // Break out of each loop
                        }
                    }
                });

                if (!hasValidLineItem) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please add at least one line item with account and amount greater than 0.',
                        confirmButtonColor: '#dc3545'
                    });
                    return false;
                }

                // Disable submit button and show loading state
                $('#updateBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-2"></i>Updating...');

                // Allow form to submit normally (don't prevent default if validation passes)
            });

            function addLineItem(isInvoiceItem = false, invoiceId = null, invoiceNumber = null, amount = 0, chartAccountId = null, accountName = '', description = '') {
                lineItemCount++;
                const accountFieldHtml = isInvoiceItem ? `
                                                    <input type="hidden" name="line_items[${lineItemCount}][chart_account_id]" value="${chartAccountId || ''}">
                                                    <input type="hidden" name="line_items[${lineItemCount}][invoice_id]" value="${invoiceId || ''}">
                                                    <input type="hidden" name="line_items[${lineItemCount}][invoice_number]" value="${invoiceNumber || ''}">
                                                ` : `
                                                    <div class="col-md-4 mb-2 account-field">
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
                                                `;
                
                const descriptionValue = isInvoiceItem ? `Receipt for Invoice ${invoiceNumber}` : description;
                const descriptionReadonly = isInvoiceItem ? 'readonly' : '';
                const descriptionColClass = isInvoiceItem ? 'col-md-7' : 'col-md-4';
                
                const lineItemHtml = `
                                    <div class="line-item-row" ${isInvoiceItem ? `data-invoice-item="true" data-invoice-id="${invoiceId}"` : ''}>
                                        <div class="row">
                                            ${accountFieldHtml}
                                            <div class="${descriptionColClass} mb-2">
                                                <label for="line_items_${lineItemCount}_description" class="form-label fw-bold">
                                                    Description
                                                </label>
                                                <input type="text" class="form-control description-input"
                                                       name="line_items[${lineItemCount}][description]"
                                                       placeholder="Enter description"
                                                       value="${descriptionValue}"
                                                       ${descriptionReadonly}>
                                            </div>
                                            <div class="col-md-2 mb-2">
                                                <label for="line_items_${lineItemCount}_amount" class="form-label fw-bold">
                                                    Amount <span class="text-danger">*</span>
                                                </label>
                                                <input type="number" class="form-control amount-input"
                                                       name="line_items[${lineItemCount}][amount]"
                                                       step="0.01" min="0" placeholder="0.00" value="${amount || ''}" required>
                                            </div>
                                            <div class="col-md-1 mb-2 d-flex align-items-end">
                                                <button type="button" class="btn btn-outline-danger btn-sm remove-line-btn" title="Remove Line">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                `;

                const $newRow = $(lineItemHtml);
                $('#lineItemsContainer').append($newRow);

                // Initialize Select2 for the new chart account select (only for non-invoice items)
                if (!isInvoiceItem) {
                    setTimeout(function() {
                        $('#lineItemsContainer .chart-account-select').last().select2({
                            placeholder: 'Select Account',
                            allowClear: true,
                            width: '100%',
                            theme: 'bootstrap-5'
                        });
                    }, 100);
                }
                
                // Recalculate total
                calculateTotal();
                
                // Return the row element for tracking (if invoice item)
                if (isInvoiceItem) {
                    return $newRow;
                }
            }
            
            // Load customer invoices
            function loadCustomerInvoices(customerId) {
                if (!customerId) {
                    $('#customerInvoicesSection').hide();
                    return;
                }

                $('#customerInvoicesContainer').html(`
                    <div class="text-center py-4">
                        <i class="bx bx-loader-alt bx-spin fs-3 text-primary"></i>
                        <p class="mt-2 text-muted">Loading invoices...</p>
                    </div>
                `);
                $('#customerInvoicesSection').show();

                $.ajax({
                    url: '{{ route("accounting.receipt-vouchers.customer-invoices", ":id") }}'.replace(':id', customerId),
                    method: 'GET',
                    success: function(response) {
                        if (response.data && response.data.length > 0) {
                            let html = `
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th width="50">
                                                    <input type="checkbox" id="selectAllInvoicesCheckbox">
                                                </th>
                                                <th>Invoice #</th>
                                                <th>Date</th>
                                                <th>Due Date</th>
                                                <th>Total Amount</th>
                                                <th>Paid</th>
                                                <th>Outstanding</th>
                                                <th>Amount to Pay</th>
                                                <th width="100">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                            `;
                            
                            response.data.forEach(function(invoice) {
                                // Check if this invoice is already added to line items
                                const isAdded = addedInvoices[invoice.id] !== undefined;
                                const addedAmount = isAdded ? addedInvoices[invoice.id].amount : null;
                                // Always default to outstanding amount, even if invoice is already added
                                // The user can still edit the amount if needed
                                const displayAmount = isAdded ? (addedAmount || invoice.outstanding_amount) : invoice.outstanding_amount;
                                const rowClass = isAdded ? 'table-info' : '';
                                
                                html += `
                                    <tr class="${rowClass}" data-invoice-row-id="${invoice.id}">
                                        <td>
                                            <input type="checkbox" class="invoice-checkbox" 
                                                   data-invoice-id="${invoice.id}" 
                                                   data-invoice-number="${invoice.invoice_number}"
                                                   data-outstanding="${invoice.outstanding_amount}"
                                                   ${isAdded ? 'checked' : ''}>
                                        </td>
                                        <td>${invoice.invoice_number}</td>
                                        <td>${invoice.invoice_date}</td>
                                        <td>${invoice.due_date || 'N/A'}</td>
                                        <td>${parseFloat(invoice.total_amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} ${invoice.currency}</td>
                                        <td>${parseFloat(invoice.total_paid).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} ${invoice.currency}</td>
                                        <td class="fw-bold text-danger">${parseFloat(invoice.outstanding_amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} ${invoice.currency}</td>
                                        <td>
                                            <input type="number" 
                                                   class="form-control form-control-sm invoice-amount" 
                                                   data-invoice-id="${invoice.id}"
                                                   data-outstanding="${invoice.outstanding_amount}"
                                                   value="${displayAmount}"
                                                   step="0.01" 
                                                   min="0" 
                                                   style="width: 120px;">
                                        </td>
                                        <td>
                                            ${isAdded ? '<span class="badge bg-success"><i class="bx bx-check me-1"></i>Added</span>' : '<span class="badge bg-secondary">Not Added</span>'}
                                        </td>
                                    </tr>
                                `;
                            });
                            
                            html += `
                                        </tbody>
                                    </table>
                                </div>
                            `;
                            
                            $('#customerInvoicesContainer').html(html);
                            
                            // Refresh display to show which invoices are already added
                            refreshInvoiceDisplay();
                        } else {
                            $('#customerInvoicesContainer').html(`
                                <div class="text-center py-4">
                                    <i class="bx bx-info-circle fs-3 text-muted"></i>
                                    <p class="mt-2 text-muted">No unpaid invoices found for this customer.</p>
                                </div>
                            `);
                        }
                    },
                    error: function(xhr) {
                        console.error('Failed to load invoices:', xhr);
                        $('#customerInvoicesContainer').html(`
                            <div class="alert alert-danger">
                                <i class="bx bx-error-circle me-2"></i>Failed to load invoices. Please try again.
                            </div>
                        `);
                    }
                });
            }
            
            // Function to refresh invoice display (update status and amounts)
            function refreshInvoiceDisplay() {
                // Update checkboxes and amounts for already added invoices
                Object.keys(addedInvoices).forEach(function(invoiceId) {
                    const invoiceData = addedInvoices[invoiceId];
                    const checkbox = $(`.invoice-checkbox[data-invoice-id="${invoiceId}"]`);
                    const amountInput = $(`.invoice-amount[data-invoice-id="${invoiceId}"]`);
                    const row = $(`tr[data-invoice-row-id="${invoiceId}"]`);
                    
                    if (checkbox.length) {
                        checkbox.prop('checked', true);
                    }
                    if (amountInput.length) {
                        amountInput.val(invoiceData.amount);
                    }
                    if (row.length) {
                        row.addClass('table-info');
                        const statusCell = row.find('td:last');
                        statusCell.html('<span class="badge bg-success"><i class="bx bx-check me-1"></i>Added</span>');
                    }
                });
            }

            // Select all invoices
            $(document).on('change', '#selectAllInvoicesCheckbox', function() {
                $('.invoice-checkbox').prop('checked', $(this).is(':checked'));
            });

            // Select all button
            $('#selectAllInvoices').on('click', function() {
                $('.invoice-checkbox, #selectAllInvoicesCheckbox').prop('checked', true);
            });

            // Deselect all button
            $('#deselectAllInvoices').on('click', function() {
                $('.invoice-checkbox, #selectAllInvoicesCheckbox').prop('checked', false);
            });

            // Show warning if amount exceeds outstanding (but allow it)
            $(document).on('input', '.invoice-amount', function() {
                const outstanding = parseFloat($(this).data('outstanding')) || 0;
                const value = parseFloat($(this).val()) || 0;
                const $row = $(this).closest('tr');
                
                // Remove any existing warning
                $row.find('.amount-warning').remove();
                
                // Prevent negative values
                if (value < 0) {
                    $(this).val(0);
                    return;
                }
                
                // Show warning if amount exceeds outstanding, but don't prevent it
                if (value > outstanding && outstanding > 0) {
                    $(this).addClass('border-warning');
                    const warningHtml = `<small class="text-warning d-block mt-1 amount-warning">
                        <i class="bx bx-info-circle"></i> Amount exceeds outstanding (${outstanding.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})})
                    </small>`;
                    $(this).after(warningHtml);
                } else {
                    $(this).removeClass('border-warning');
                }
            });

            // Add selected invoices to line items
            $('#addSelectedInvoices').on('click', function() {
                const selectedInvoices = [];
                $('.invoice-checkbox:checked').each(function() {
                    const invoiceId = $(this).data('invoice-id');
                    const invoiceNumber = $(this).data('invoice-number');
                    const outstandingAmount = parseFloat($(this).data('outstanding')) || 0;
                    const amountInput = $(`.invoice-amount[data-invoice-id="${invoiceId}"]`);
                    let amount = parseFloat(amountInput.val()) || 0;
                    
                    // If amount is 0 or empty, use the outstanding amount as default
                    // But allow amounts greater than outstanding (for advance payments, etc.)
                    if (amount <= 0 && outstandingAmount > 0) {
                        amount = outstandingAmount;
                        amountInput.val(amount.toFixed(2));
                    }
                    
                    // Add if we have a valid amount (allow amounts greater than outstanding)
                    if (amount > 0) {
                        selectedInvoices.push({
                            invoice_id: invoiceId,
                            invoice_number: invoiceNumber,
                            amount: amount,
                            outstanding_amount: outstandingAmount
                        });
                    }
                });

                if (selectedInvoices.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Invoices Selected',
                        text: 'Please select at least one invoice with amount > 0',
                        confirmButtonColor: '#dc3545'
                    });
                    return;
                }

                // Use the AR account ID retrieved on page load
                const arAccountId = defaultArAccountId;

                let addedCount = 0;
                let updatedCount = 0;

                // Add or update each selected invoice as a line item
                selectedInvoices.forEach(function(invoice) {
                    if (addedInvoices[invoice.invoice_id]) {
                        // Invoice already added - update the existing line item
                        const existingRow = addedInvoices[invoice.invoice_id].rowElement;
                        if (existingRow && existingRow.length) {
                            // Update amount in the line item
                            existingRow.find('.amount-input').val(invoice.amount);
                            // Update description if needed
                            existingRow.find('.description-input').val(`Receipt for Invoice ${invoice.invoice_number}`);
                            // Update tracking
                            addedInvoices[invoice.invoice_id].amount = invoice.amount;
                            updatedCount++;
                        }
                    } else {
                        // New invoice - add as new line item
                        const $newRow = addLineItem(true, invoice.invoice_id, invoice.invoice_number, invoice.amount, arAccountId);
                        // Track the added invoice
                        addedInvoices[invoice.invoice_id] = {
                            invoice_number: invoice.invoice_number,
                            amount: invoice.amount,
                            rowElement: $newRow
                        };
                        addedCount++;
                    }
                });

                // Refresh invoice display to show updated status
                refreshInvoiceDisplay();

                // Show success message
                let message = '';
                if (addedCount > 0 && updatedCount > 0) {
                    message = `${addedCount} invoice(s) added and ${updatedCount} invoice(s) updated`;
                } else if (addedCount > 0) {
                    message = `${addedCount} invoice(s) added to line items`;
                } else if (updatedCount > 0) {
                    message = `${updatedCount} invoice(s) updated`;
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Invoices Processed',
                    text: message,
                    timer: 2000,
                    showConfirmButton: false
                });
            });

            // Hide invoice section when payee type changes away from customer
            $('#payee_type').on('change', function() {
                if ($(this).val() !== 'customer') {
                    $('#customerInvoicesSection').hide();
                }
            });
            
            // Load invoices if customer is already selected (for edit form)
            // Note: refreshInvoiceDisplay is already called inside loadCustomerInvoices after HTML is loaded
            if ($('#payee_type').val() === 'customer' && $('#customer_id').val()) {
                setTimeout(function() {
                    loadCustomerInvoices($('#customer_id').val());
                }, 500);
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
        });
    </script>
@endpush