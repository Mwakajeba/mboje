@extends('layouts.main')

@section('title', 'Create Accrual Schedule')

@push('styles')
<style>
    .form-wizard {
        position: relative;
    }
    
    .wizard-steps {
        display: flex;
        justify-content: space-between;
        margin-bottom: 2rem;
        position: relative;
    }
    
    .wizard-step {
        flex: 1;
        text-align: center;
        position: relative;
    }
    
    .wizard-step::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 50%;
        width: 100%;
        height: 2px;
        background: #e9ecef;
        z-index: 0;
    }
    
    .wizard-step:first-child::before {
        display: none;
    }
    
    .wizard-step.active::before {
        background: #6f42c1;
    }
    
    .wizard-step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e9ecef;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.5rem;
        font-weight: 600;
        position: relative;
        z-index: 1;
        transition: all 0.3s ease;
    }
    
    .wizard-step.active .wizard-step-number {
        background: #6f42c1;
        color: white;
        box-shadow: 0 0 0 4px rgba(111, 66, 193, 0.1);
    }
    
    .wizard-step.completed .wizard-step-number {
        background: #198754;
        color: white;
    }
    
    .wizard-step-label {
        font-size: 0.875rem;
        color: #6c757d;
        font-weight: 500;
    }
    
    .wizard-step.active .wizard-step-label {
        color: #6f42c1;
        font-weight: 600;
    }
    
    .form-section {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }
    
    .form-section:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .form-section-title {
        font-weight: 600;
        color: #6f42c1;
        margin-bottom: 1.25rem;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .form-section-title i {
        font-size: 1.25rem;
        margin-right: 0.5rem;
    }
    
    .info-box {
        background: linear-gradient(135deg, #f8f9ff 0%, #e7f1ff 100%);
        border-left: 4px solid #6f42c1;
        padding: 1rem;
        margin-bottom: 1.5rem;
        border-radius: 6px;
    }
    
    .info-box strong {
        color: #6f42c1;
    }
    
    .info-box ul {
        margin-bottom: 0;
        padding-left: 1.25rem;
    }
    
    .info-box li {
        margin-bottom: 0.5rem;
    }
    
    .category-card {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 1.25rem;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: 0.75rem;
        background: #fff;
        position: relative;
        overflow: hidden;
    }
    
    .category-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: transparent;
        transition: all 0.3s ease;
    }
    
    .category-card:hover {
        border-color: #6f42c1;
        background: #f8f9ff;
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(111, 66, 193, 0.15);
    }
    
    .category-card:hover::before {
        background: #6f42c1;
    }
    
    .category-card.selected {
        border-color: #6f42c1;
        background: linear-gradient(135deg, #e7f1ff 0%, #f8f9ff 100%);
        box-shadow: 0 4px 16px rgba(111, 66, 193, 0.2);
    }
    
    .category-card.selected::before {
        background: #6f42c1;
        width: 4px;
    }
    
    .category-card input[type="radio"] {
        margin-right: 0.75rem;
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    
    .category-card label {
        cursor: pointer;
        margin-bottom: 0;
        font-weight: 600;
        color: #333;
    }
    
    .category-card.selected label {
        color: #6f42c1;
    }
    
    .category-card p {
        margin-top: 0.5rem;
        margin-bottom: 0;
        font-size: 0.875rem;
    }
    
    .category-display-card {
        background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
        color: white;
        border-radius: 10px;
        padding: 1.25rem;
        margin-top: 1rem;
        box-shadow: 0 4px 16px rgba(111, 66, 193, 0.3);
    }
    
    .category-display-card strong {
        display: block;
        font-size: 0.875rem;
        opacity: 0.9;
        margin-bottom: 0.5rem;
    }
    
    .category-display-card span {
        font-size: 1.25rem;
        font-weight: 600;
    }
    
    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }
    
    .form-control:focus,
    .form-select:focus {
        border-color: #6f42c1;
        box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);
    }
    
    .btn-purple {
        background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
        border: none;
        color: white;
        font-weight: 600;
        padding: 0.75rem 2rem;
        border-radius: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(111, 66, 193, 0.3);
    }
    
    .btn-purple:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(111, 66, 193, 0.4);
        color: white;
    }
    
    .select2-container--bootstrap-5 .select2-selection {
        border-color: #ced4da;
    }
    
    .select2-container--bootstrap-5.select2-container--focus .select2-selection {
        border-color: #6f42c1;
        box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);
    }
    
    .preview-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Management', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Accruals & Prepayments', 'url' => route('accounting.accruals-prepayments.index'), 'icon' => 'bx bx-time-five'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="mb-0 text-uppercase">CREATE ACCRUAL SCHEDULE</h6>
                <p class="text-muted mb-0">Create a new accrual or prepayment schedule with automated amortisation</p>
            </div>
            <a href="{{ route('accounting.accruals-prepayments.index') }}" class="btn btn-secondary">
                <i class="bx bx-arrow-back me-1"></i>Back to List
            </a>
        </div>
        <hr />

        <div class="card radius-10 border-0 shadow-sm">
            <div class="card-header" style="background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%); color: white; border-radius: 10px 10px 0 0;">
                <div class="d-flex align-items-center">
                    <div class="widgets-icons-2 rounded-circle bg-white bg-opacity-20 me-3">
                        <i class="bx bx-time-five"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">New Accrual/Prepayment Schedule</h5>
                        <p class="mb-0 small opacity-75">IFRS Compliant Automated Amortisation</p>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="bx bx-error-circle fs-4 me-3 mt-1"></i>
                            <div class="flex-grow-1">
                                <strong>Please fix the following errors:</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    </div>
                @endif

                <!-- Wizard Steps -->
                <div class="form-wizard mb-4">
                    <div class="wizard-steps">
                        <div class="wizard-step active" data-step="1">
                            <div class="wizard-step-number">1</div>
                            <div class="wizard-step-label">Type & Nature</div>
                        </div>
                        <div class="wizard-step" data-step="2">
                            <div class="wizard-step-number">2</div>
                            <div class="wizard-step-label">Details</div>
                        </div>
                        <div class="wizard-step" data-step="3">
                            <div class="wizard-step-number">3</div>
                            <div class="wizard-step-label">Accounts</div>
                        </div>
                        <div class="wizard-step" data-step="4">
                            <div class="wizard-step-number">4</div>
                            <div class="wizard-step-label">Frequency</div>
                        </div>
                    </div>
                </div>

                <form id="schedule-form" action="{{ route('accounting.accruals-prepayments.store') }}" method="POST">
                    @csrf

                    <!-- Step 1: Schedule Type and Nature -->
                    <div class="form-section" data-wizard-step="1">
                        <h6 class="form-section-title">
                            <i class="bx bx-category"></i>Step 1: Select Schedule Type & Nature
                        </h6>
                        
                        <div class="info-box">
                            <strong><i class="bx bx-info-circle me-1"></i>IFRS Categories Explained:</strong>
                            <ul class="mt-2">
                                <li><strong>Prepaid Expense</strong> (Asset): Expense paid before benefit period begins</li>
                                <li><strong>Accrued Expense</strong> (Liability): Expense incurred but not yet invoiced/paid</li>
                                <li><strong>Deferred Income</strong> (Liability): Cash received before service delivery</li>
                                <li><strong>Accrued Income</strong> (Asset): Income earned but not yet billed/received</li>
                            </ul>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Schedule Type <span class="text-danger">*</span></label>
                                <div>
                                    <div class="category-card" onclick="selectScheduleType('prepayment', this)">
                                        <input type="radio" name="schedule_type" value="prepayment" id="type_prepayment" 
                                               {{ old('schedule_type') == 'prepayment' ? 'checked' : '' }} required>
                                        <label for="type_prepayment" class="mb-0">
                                            <i class="bx bx-credit-card me-1"></i>Prepayment
                                        </label>
                                        <p class="text-muted mb-0 small">Payment made in advance; cost consumed over time</p>
                                    </div>
                                    <div class="category-card" onclick="selectScheduleType('accrual', this)">
                                        <input type="radio" name="schedule_type" value="accrual" id="type_accrual"
                                               {{ old('schedule_type') == 'accrual' ? 'checked' : '' }} required>
                                        <label for="type_accrual" class="mb-0">
                                            <i class="bx bx-time me-1"></i>Accrual
                                        </label>
                                        <p class="text-muted mb-0 small">Expense/Income incurred before payment/receipt</p>
                                    </div>
                                </div>
                                @error('schedule_type')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nature <span class="text-danger">*</span></label>
                                <div>
                                    <div class="category-card" onclick="selectNature('expense', this)">
                                        <input type="radio" name="nature" value="expense" id="nature_expense"
                                               {{ old('nature') == 'expense' ? 'checked' : '' }} required>
                                        <label for="nature_expense" class="mb-0">
                                            <i class="bx bx-down-arrow-circle me-1 text-danger"></i>Expense
                                        </label>
                                    </div>
                                    <div class="category-card" onclick="selectNature('income', this)">
                                        <input type="radio" name="nature" value="income" id="nature_income"
                                               {{ old('nature') == 'income' ? 'checked' : '' }} required>
                                        <label for="nature_income" class="mb-0">
                                            <i class="bx bx-up-arrow-circle me-1 text-success"></i>Income
                                        </label>
                                    </div>
                                </div>
                                @error('nature')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="category-display-card" id="category-display">
                            <strong>Selected Category:</strong>
                            <span id="category-name">Please select type and nature above</span>
                        </div>
                    </div>

                    <!-- Step 2: General Details -->
                    <div class="form-section" data-wizard-step="2">
                        <h6 class="form-section-title">
                            <i class="bx bx-info-circle"></i>Step 2: General Details
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">
                                        <i class="bx bx-calendar me-1"></i>Start Date <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control @error('start_date') is-invalid @enderror" 
                                           id="start_date" name="start_date" 
                                           value="{{ old('start_date') }}" required>
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Amortisation start date</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">
                                        <i class="bx bx-calendar-check me-1"></i>End Date <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control @error('end_date') is-invalid @enderror" 
                                           id="end_date" name="end_date" 
                                           value="{{ old('end_date') }}" required>
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Final amortisation period</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="total_amount" class="form-label">
                                        <i class="bx bx-dollar me-1"></i>Total Amount <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0.01" 
                                               class="form-control @error('total_amount') is-invalid @enderror" 
                                               id="total_amount" name="total_amount" 
                                               value="{{ old('total_amount') }}" 
                                               placeholder="0.00" required>
                                        <span class="input-group-text" id="currency-display">TZS</span>
                                    </div>
                                    @error('total_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Total amount to be amortised</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="currency_code" class="form-label">
                                        <i class="bx bx-money me-1"></i>Currency <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('currency_code') is-invalid @enderror" 
                                            id="currency_code" name="currency_code" required>
                                        <option value="TZS" {{ old('currency_code', 'TZS') == 'TZS' ? 'selected' : '' }}>TZS - Tanzanian Shilling</option>
                                        <option value="USD" {{ old('currency_code') == 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                        <option value="EUR" {{ old('currency_code') == 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                        <option value="GBP" {{ old('currency_code') == 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                                    </select>
                                    @error('currency_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Payment Method Fields (Only for Prepayments) -->
                            <div id="payment-method-section" style="display: none;">
                                <div class="col-md-12">
                                    <div class="alert alert-info">
                                        <i class="bx bx-info-circle me-2"></i>
                                        <strong>Payment Information Required:</strong> For prepayments, you must specify how the payment/receipt was made. This will create the initial journal entry when the schedule is approved.
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_method" class="form-label">
                                            <i class="bx bx-credit-card me-1"></i>Payment Method
                                        </label>
                                        <select class="form-select @error('payment_method') is-invalid @enderror" 
                                                id="payment_method" name="payment_method">
                                            <option value="">-- Select Payment Method --</option>
                                            <option value="bank" {{ old('payment_method') == 'bank' ? 'selected' : '' }}>Bank Transfer</option>
                                            <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                        </select>
                                        @error('payment_method')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">How was the payment/receipt made?</small>
                                    </div>
                                </div>

                                <div class="col-md-6" id="bank-account-container" style="display: none;">
                                    <div class="mb-3">
                                        <label for="bank_account_id" class="form-label">
                                            <i class="bx bx-building me-1"></i>Bank Account
                                        </label>
                                        <select class="form-select select2-single @error('bank_account_id') is-invalid @enderror" 
                                                id="bank_account_id" name="bank_account_id">
                                            <option value="">-- Select Bank Account --</option>
                                            @foreach($bankAccounts as $bankAccount)
                                                <option value="{{ $bankAccount->id }}" 
                                                        {{ old('bank_account_id') == $bankAccount->id ? 'selected' : '' }}>
                                                    {{ $bankAccount->name }} - {{ $bankAccount->account_number }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('bank_account_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">Select the bank account used for payment/receipt</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_date" class="form-label">
                                            <i class="bx bx-calendar me-1"></i>Payment/Receipt Date
                                        </label>
                                        <input type="date" class="form-control @error('payment_date') is-invalid @enderror" 
                                               id="payment_date" name="payment_date" 
                                               value="{{ old('payment_date') }}">
                                        @error('payment_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">Date when payment was made or receipt was received (defaults to start date if not specified)</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="description" class="form-label">
                                        <i class="bx bx-file-blank me-1"></i>Description <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="3" 
                                              placeholder="Enter a clear description of this accrual/prepayment..." required>{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Purpose of this accrual/prepayment transaction</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Accounts -->
                    <div class="form-section" data-wizard-step="3">
                        <h6 class="form-section-title">
                            <i class="bx bx-book"></i>Step 3: Chart of Accounts
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="expense_income_account_id" class="form-label">
                                        <i class="bx bx-line-chart me-1"></i>P&L Account <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select select2-single @error('expense_income_account_id') is-invalid @enderror" 
                                            id="expense_income_account_id" name="expense_income_account_id" required>
                                        <option value="">-- Select P&L Account --</option>
                                        @foreach($expenseIncomeAccounts as $account)
                                        <option value="{{ $account->id }}" 
                                                {{ old('expense_income_account_id') == $account->id ? 'selected' : '' }}>
                                            {{ $account->account_code }} - {{ $account->account_name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('expense_income_account_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Expense or Income account (Profit & Loss Statement)</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="balance_sheet_account_id" class="form-label">
                                        <i class="bx bx-spreadsheet me-1"></i>Balance Sheet Account <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select select2-single @error('balance_sheet_account_id') is-invalid @enderror" 
                                            id="balance_sheet_account_id" name="balance_sheet_account_id" required>
                                        <option value="">-- Select Balance Sheet Account --</option>
                                        @foreach($balanceSheetAccounts as $account)
                                        <option value="{{ $account->id }}" 
                                                {{ old('balance_sheet_account_id') == $account->id ? 'selected' : '' }}>
                                            {{ $account->account_code }} - {{ $account->account_name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('balance_sheet_account_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Prepaid, Accrued, Deferred, or Accrued Income account</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Frequency & Optional Fields -->
                    <div class="form-section" data-wizard-step="4">
                        <h6 class="form-section-title">
                            <i class="bx bx-calendar"></i>Step 4: Frequency & Optional Information
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="frequency" class="form-label">
                                        <i class="bx bx-refresh me-1"></i>Frequency <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('frequency') is-invalid @enderror" 
                                            id="frequency" name="frequency" required>
                                        <option value="monthly" {{ old('frequency', 'monthly') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                        <option value="quarterly" {{ old('frequency') == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                        <option value="custom" {{ old('frequency') == 'custom' ? 'selected' : '' }}>Custom</option>
                                    </select>
                                    @error('frequency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">How often to amortise this amount</small>
                                </div>
                            </div>

                            <div class="col-md-6" id="custom-periods-container" style="display: none;">
                                <div class="mb-3">
                                    <label for="custom_periods" class="form-label">
                                        <i class="bx bx-time-five me-1"></i>Custom Periods (Months)
                                    </label>
                                    <input type="number" min="1" class="form-control" 
                                           id="custom_periods" name="custom_periods" 
                                           value="{{ old('custom_periods') }}"
                                           placeholder="e.g., 2, 3, 6">
                                    <small class="text-muted">Number of months per amortisation period</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="branch_id" class="form-label">
                                        <i class="bx bx-building me-1"></i>Branch
                                    </label>
                                    <select class="form-select select2-single" id="branch_id" name="branch_id">
                                        <option value="">All Branches</option>
                                        @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" 
                                                {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Leave blank for company-wide</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="vendor_id" class="form-label">
                                        <i class="bx bx-store me-1"></i>Vendor (Optional)
                                    </label>
                                    <select class="form-select select2-single" id="vendor_id" name="vendor_id">
                                        <option value="">-- Select Vendor --</option>
                                        @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" 
                                                {{ old('vendor_id') == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_id" class="form-label">
                                        <i class="bx bx-user me-1"></i>Customer (Optional)
                                    </label>
                                    <select class="form-select select2-single" id="customer_id" name="customer_id">
                                        <option value="">-- Select Customer --</option>
                                        @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" 
                                                {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">
                                        <i class="bx bx-note me-1"></i>Additional Notes
                                    </label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"
                                              placeholder="Any additional notes or comments...">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="{{ route('accounting.accruals-prepayments.index') }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-purple">
                            <i class="bx bx-save me-1"></i>Create Schedule
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
        width: '100%',
        placeholder: function() {
            return $(this).data('placeholder') || 'Select an option';
        }
    });
    
    // Update currency display
    $('#currency_code').on('change', function() {
        $('#currency-display').text($(this).val());
    });
    
    // Initialize currency display
    $('#currency-display').text($('#currency_code').val());
    
    // Show/hide payment method section based on schedule type
    function togglePaymentMethodSection() {
        const scheduleType = $('input[name="schedule_type"]:checked').val();
        if (scheduleType === 'prepayment') {
            $('#payment-method-section').slideDown(300);
            // Make payment method required for prepayments
            $('#payment_method').prop('required', true);
            $('.initial-payment-required-star').show();
            // If bank is already selected, show bank account field
            if ($('#payment_method').val() === 'bank') {
                $('#bank-account-container').slideDown(300);
                $('#bank_account_id').prop('required', true);
            }
        } else {
            $('#payment-method-section').slideUp(300);
            $('#payment_method').prop('required', false);
            $('#bank_account_id').prop('required', false);
            $('#bank-account-container').slideUp(300);
            $('.initial-payment-required-star').hide();
        }
    }
    
    // Show/hide bank account field based on payment method
    $('#payment_method').on('change', function() {
        if ($(this).val() === 'bank') {
            $('#bank-account-container').slideDown(300);
            $('#bank_account_id').prop('required', true);
            // Reinitialize Select2 for bank account dropdown
            if ($('#bank_account_id').hasClass('select2-hidden-accessible')) {
                $('#bank_account_id').select2('destroy');
            }
            $('#bank_account_id').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select Bank Account'
            });
        } else {
            $('#bank-account-container').slideUp(300);
            $('#bank_account_id').prop('required', false);
        }
    });
    
    // Listen for schedule type changes (both radio buttons and card clicks)
    $('input[name="schedule_type"]').on('change', function() {
        togglePaymentMethodSection();
    });
    
    // Also listen for card clicks that trigger selectScheduleType
    $(document).on('click', '.category-card', function() {
        setTimeout(function() {
            togglePaymentMethodSection();
        }, 100);
    });
    
    // Initialize on page load
    togglePaymentMethodSection();
    
    // If payment method is already set, show bank account if needed
    if ($('#payment_method').val() === 'bank') {
        $('#bank-account-container').show();
        $('#bank_account_id').prop('required', true);
        // Initialize Select2 for bank account if not already initialized
        if (!$('#bank_account_id').hasClass('select2-hidden-accessible')) {
            $('#bank_account_id').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select Bank Account'
            });
        }
    }
});

function selectScheduleType(type, element) {
    document.querySelectorAll('.category-card').forEach(card => {
        if (card.querySelector('input[name="schedule_type"]')) {
            card.classList.remove('selected');
        }
    });
    element.classList.add('selected');
    document.getElementById('type_' + type).checked = true;
    updateCategoryDisplay();
    updateWizardSteps();
    // Toggle payment method section
    if (typeof togglePaymentMethodSection === 'function') {
        togglePaymentMethodSection();
    }
}

function selectNature(nature, element) {
    document.querySelectorAll('.category-card').forEach(card => {
        if (card.querySelector('input[name="nature"]')) {
            card.classList.remove('selected');
        }
    });
    element.classList.add('selected');
    document.getElementById('nature_' + nature).checked = true;
    updateCategoryDisplay();
    updateWizardSteps();
}

function updateCategoryDisplay() {
    const scheduleType = document.querySelector('input[name="schedule_type"]:checked')?.value;
    const nature = document.querySelector('input[name="nature"]:checked')?.value;
    
    if (scheduleType && nature) {
        let categoryName = '';
        let icon = '';
        if (scheduleType === 'prepayment' && nature === 'expense') {
            categoryName = 'Prepaid Expense (Asset)';
            icon = '<i class="bx bx-credit-card me-1"></i>';
        } else if (scheduleType === 'accrual' && nature === 'expense') {
            categoryName = 'Accrued Expense (Liability)';
            icon = '<i class="bx bx-time me-1"></i>';
        } else if (scheduleType === 'prepayment' && nature === 'income') {
            categoryName = 'Deferred Income (Liability)';
            icon = '<i class="bx bx-credit-card me-1"></i>';
        } else if (scheduleType === 'accrual' && nature === 'income') {
            categoryName = 'Accrued Income (Asset)';
            icon = '<i class="bx bx-time me-1"></i>';
        }
        document.getElementById('category-name').innerHTML = icon + categoryName;
        document.getElementById('category-display').style.display = 'block';
    } else {
        document.getElementById('category-name').textContent = 'Please select type and nature above';
    }
}

function updateWizardSteps() {
    const scheduleType = document.querySelector('input[name="schedule_type"]:checked')?.value;
    const nature = document.querySelector('input[name="nature"]:checked')?.value;
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const totalAmount = document.getElementById('total_amount').value;
    const expenseAccount = document.getElementById('expense_income_account_id').value;
    const balanceAccount = document.getElementById('balance_sheet_account_id').value;
    
    // Step 1 completed
    if (scheduleType && nature) {
        document.querySelector('[data-step="1"]').classList.add('completed');
        document.querySelector('[data-step="2"]').classList.add('active');
    }
    
    // Step 2 completed
    if (startDate && endDate && totalAmount) {
        document.querySelector('[data-step="2"]').classList.add('completed');
        document.querySelector('[data-step="3"]').classList.add('active');
    }
    
    // Step 3 completed
    if (expenseAccount && balanceAccount) {
        document.querySelector('[data-step="3"]').classList.add('completed');
        document.querySelector('[data-step="4"]').classList.add('active');
    }
}

// Frequency change handler
$('#frequency').on('change', function() {
    const customContainer = $('#custom-periods-container');
    if ($(this).val() === 'custom') {
        customContainer.slideDown();
    } else {
        customContainer.slideUp();
    }
    updateWizardSteps();
});

// Initialize category display and wizard
document.querySelectorAll('input[name="schedule_type"], input[name="nature"]').forEach(input => {
    input.addEventListener('change', function() {
        updateCategoryDisplay();
        updateWizardSteps();
    });
    if (input.checked) {
        const card = input.closest('.category-card');
        if (card) card.classList.add('selected');
    }
});

// Form field change handlers for wizard
$('#start_date, #end_date, #total_amount, #expense_income_account_id, #balance_sheet_account_id').on('change', function() {
    updateWizardSteps();
});

// Initialize frequency display
if ($('#frequency').val() === 'custom') {
    $('#custom-periods-container').show();
}

// Initialize wizard
updateCategoryDisplay();
updateWizardSteps();
</script>
@endpush
