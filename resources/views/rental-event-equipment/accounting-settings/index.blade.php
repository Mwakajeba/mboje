@extends('layouts.main')

@section('title', 'Accounting Settings')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Accounting Settings', 'url' => '#', 'icon' => 'bx bx-calculator']
        ]" />
        <h6 class="mb-0 text-uppercase">ACCOUNTING SETTINGS</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bx bx-calculator me-2"></i>Configure GL Accounts</h5>
                    </div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('rental-event-equipment.accounting-settings.store') }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-12">
                                    <h6 class="text-primary mb-3"><i class="bx bx-package me-2"></i>Asset Accounts</h6>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Rental & Event Equipment Account</label>
                                    <select name="rental_equipment_account_id" class="form-select select2-single">
                                        <option value="">Select Account</option>
                                        @foreach($assetAccounts as $account)
                                            <option value="{{ $account->id }}" 
                                                {{ ($settings && $settings->rental_equipment_account_id == $account->id) ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">GL account for rental & event equipment assets</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Equipment Under Repair Account</label>
                                    <select name="equipment_under_repair_account_id" class="form-select select2-single">
                                        <option value="">Select Account</option>
                                        @foreach($assetAccounts as $account)
                                            <option value="{{ $account->id }}" 
                                                {{ ($settings && $settings->equipment_under_repair_account_id == $account->id) ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">GL account for equipment under repair</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Accounts Receivable Account</label>
                                    <select name="accounts_receivable_account_id" class="form-select select2-single">
                                        <option value="">Select Account</option>
                                        @foreach($assetAccounts as $account)
                                            <option value="{{ $account->id }}" 
                                                {{ ($settings && $settings->accounts_receivable_account_id == $account->id) ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">GL account for accounts receivable</div>
                                </div>

                                <div class="col-12 mt-4">
                                    <h6 class="text-success mb-3"><i class="bx bx-money me-2"></i>Income Accounts</h6>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Rental Income Account <span class="text-danger">*</span></label>
                                    <select name="rental_income_account_id" class="form-select select2-single" required>
                                        <option value="">Select Account</option>
                                        @foreach($incomeAccounts as $account)
                                            <option value="{{ $account->id }}" 
                                                {{ ($settings && $settings->rental_income_account_id == $account->id) ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">GL account for rental income from equipment rentals</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Decoration Service Income Account</label>
                                    <select name="service_income_account_id" class="form-select select2-single">
                                        <option value="">Select Account</option>
                                        @foreach($incomeAccounts as $account)
                                            <option value="{{ $account->id }}" 
                                                {{ ($settings && $settings->service_income_account_id == $account->id) ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">GL account for decoration service income</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Damage Recovery Income Account</label>
                                    <select name="damage_recovery_income_account_id" class="form-select select2-single">
                                        <option value="">Select Account</option>
                                        @foreach($incomeAccounts as $account)
                                            <option value="{{ $account->id }}" 
                                                {{ ($settings && $settings->damage_recovery_income_account_id == $account->id) ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">GL account for damage recovery income</div>
                                </div>

                                <div class="col-12 mt-4">
                                    <h6 class="text-warning mb-3"><i class="bx bx-wallet me-2"></i>Liability Accounts</h6>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Customer Deposits Account <span class="text-danger">*</span></label>
                                    <select name="deposits_account_id" class="form-select select2-single" required>
                                        <option value="">Select Account</option>
                                        @foreach($liabilityAccounts as $account)
                                            <option value="{{ $account->id }}" 
                                                {{ ($settings && $settings->deposits_account_id == $account->id) ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">GL account for customer deposits (refundable liabilities)</div>
                                </div>

                                <div class="col-12 mt-4">
                                    <h6 class="text-danger mb-3"><i class="bx bx-error me-2"></i>Expense Accounts</h6>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Repair & Maintenance Expense Account</label>
                                    <select name="repair_maintenance_expense_account_id" class="form-select select2-single">
                                        <option value="">Select Account</option>
                                        @foreach($expenseAccounts as $account)
                                            <option value="{{ $account->id }}" 
                                                {{ ($settings && $settings->repair_maintenance_expense_account_id == $account->id) ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">GL account for repair & maintenance expenses</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Loss on Equipment Account</label>
                                    <select name="loss_on_equipment_account_id" class="form-select select2-single">
                                        <option value="">Select Account</option>
                                        @foreach($expenseAccounts as $account)
                                            <option value="{{ $account->id }}" 
                                                {{ ($settings && $settings->loss_on_equipment_account_id == $account->id) ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">GL account for loss on equipment</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">General Expenses Account</label>
                                    <select name="expenses_account_id" class="form-select select2-single">
                                        <option value="">Select Account</option>
                                        @foreach($expenseAccounts as $account)
                                            <option value="{{ $account->id }}" 
                                                {{ ($settings && $settings->expenses_account_id == $account->id) ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">GL account for general rental and event equipment expenses</div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 justify-content-end mt-4">
                                <a href="{{ route('rental-event-equipment.index') }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-x me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-secondary">
                                    <i class="bx bx-save me-1"></i>Save Settings
                                </button>
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
<script>
$(document).ready(function() {
    $('.select2-single').select2({
        placeholder: 'Select Account',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap-5'
    });
});
</script>
@endpush
