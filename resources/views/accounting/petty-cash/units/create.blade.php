@extends('layouts.main')

@section('title', 'Create Petty Cash Unit')

@push('styles')
<style>
    .card-header.bg-primary {
        background: linear-gradient(45deg, #0d6efd, #0a58ca) !important;
    }
    
    .form-section {
        border-left: 3px solid #0d6efd;
        padding-left: 1rem;
        margin-bottom: 2rem;
    }
    
    .form-section-title {
        font-weight: 600;
        color: #0d6efd;
        margin-bottom: 1rem;
    }
    
    .info-box {
        background-color: #f8f9fa;
        border-left: 3px solid #0dcaf0;
        padding: 0.75rem;
        margin-bottom: 1rem;
        border-radius: 4px;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Management', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Petty Cash Units', 'url' => route('accounting.petty-cash.units.index'), 'icon' => 'bx bx-wallet'],
            ['label' => 'Create Petty Cash Unit', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        
        <h6 class="mb-0 text-uppercase">CREATE PETTY CASH UNIT</h6>
        <hr />

        <div class="card radius-10">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-wallet me-2"></i>New Petty Cash Unit</h5>
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bx bx-error-circle me-2"></i>
                        <strong>Please fix the following errors:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(isset($settings) && $settings)
                <div class="alert alert-info border-0 border-start border-4 border-info mb-4">
                    <div class="d-flex align-items-start">
                        <i class="bx bx-info-circle fs-4 me-3 mt-1"></i>
                        <div>
                            <h6 class="alert-heading mb-2">System Settings Applied</h6>
                            <p class="mb-1"><strong>Operation Mode:</strong> {{ $settings->operation_mode === 'sub_imprest' ? 'Sub-Imprest Mode (Linked to Imprest Module)' : 'Standalone Mode' }}</p>
                            @if($settings->default_float_amount)
                                <p class="mb-1"><strong>Default Float Amount:</strong> {{ number_format($settings->default_float_amount, 2) }} TZS</p>
                            @endif
                            @if($settings->max_transaction_amount)
                                <p class="mb-1"><strong>Maximum Transaction Amount:</strong> {{ number_format($settings->max_transaction_amount, 2) }} TZS</p>
                            @endif
                            @if($settings->maximum_limit)
                                <p class="mb-1"><strong>Maximum Limit (Unit Balance):</strong> {{ number_format($settings->maximum_limit, 2) }} TZS</p>
                            @endif
                            @if($settings->minimum_balance_trigger)
                                <p class="mb-0"><strong>Minimum Balance Trigger:</strong> {{ number_format($settings->minimum_balance_trigger, 2) }} TZS (replenishment recommended below this)</p>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <form id="petty-cash-unit-form" action="{{ route('accounting.petty-cash.units.store') }}" method="POST">
                    @csrf

                    <!-- Basic Information Section -->
                    <div class="form-section">
                        <h6 class="form-section-title">
                            <i class="bx bx-info-circle me-2"></i>Basic Information
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Unit Name <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name') }}" 
                                           placeholder="e.g., HQ Petty Cash, Branch A Petty Cash"
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">A descriptive name for this petty cash unit</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="code" class="form-label">Unit Code <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('code') is-invalid @enderror" 
                                           id="code" 
                                           name="code" 
                                           value="{{ old('code') }}" 
                                           placeholder="e.g., PC-HQ, PC-BR-A"
                                           required>
                                    @error('code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Unique code identifier for this unit</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="branch_id" class="form-label">Branch</label>
                                    <select class="form-select select2-single @error('branch_id') is-invalid @enderror" 
                                            id="branch_id" 
                                            name="branch_id">
                                        <option value="">All Branches (Company-wide)</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('branch_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Select branch if this unit is branch-specific</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Personnel Assignment Section -->
                    <div class="form-section">
                        <h6 class="form-section-title">
                            <i class="bx bx-user me-2"></i>Personnel Assignment
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="custodian_id" class="form-label">Custodian <span class="text-danger">*</span></label>
                                    <select class="form-select select2-single @error('custodian_id') is-invalid @enderror" 
                                            id="custodian_id" 
                                            name="custodian_id" 
                                            required>
                                        <option value="">Select Custodian</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('custodian_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }} @if($user->email) - {{ $user->email }} @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('custodian_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">The person responsible for managing this petty cash unit</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="supervisor_id" class="form-label">Supervisor</label>
                                    <select class="form-select select2-single @error('supervisor_id') is-invalid @enderror" 
                                            id="supervisor_id" 
                                            name="supervisor_id">
                                        <option value="">Select Supervisor (Optional)</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('supervisor_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }} @if($user->email) - {{ $user->email }} @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('supervisor_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Supervisor who approves replenishments and large expenses</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Configuration Section -->
                    <div class="form-section">
                        <h6 class="form-section-title">
                            <i class="bx bx-dollar me-2"></i>Financial Configuration
                        </h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="float_amount" class="form-label">Float Amount <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">TZS</span>
                                    <input type="number" 
                                           class="form-control @error('float_amount') is-invalid @enderror" 
                                           id="float_amount" 
                                           name="float_amount" 
                                           value="{{ old('float_amount', $settings->default_float_amount ?? '') }}" 
                                           step="0.01" 
                                           min="0" 
                                           required>
                                        @error('float_amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <small class="text-muted">
                                        Initial cash amount for this petty cash unit
                                        @if($settings->default_float_amount)
                                            <br><span class="text-info"><i class="bx bx-info-circle"></i> Default: {{ number_format($settings->default_float_amount, 2) }} TZS (from system settings)</span>
                                        @endif
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="maximum_limit" class="form-label">Maximum Limit <span class="text-muted">(Unit Balance)</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">TZS</span>
                                        <input type="number" 
                                               class="form-control @error('maximum_limit') is-invalid @enderror" 
                                               id="maximum_limit" 
                                               name="maximum_limit" 
                                               value="{{ old('maximum_limit', $settings->maximum_limit ?? '') }}" 
                                               step="0.01" 
                                               min="0">
                                        @error('maximum_limit')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <small class="text-muted">
                                        Maximum allowed balance for this unit (optional)
                                        @if($settings->maximum_limit)
                                            <br><span class="text-info"><i class="bx bx-info-circle"></i> Default: {{ number_format($settings->maximum_limit, 2) }} TZS (from system settings)</span>
                                        @endif
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="approval_threshold" class="form-label">Approval Threshold</label>
                                    <div class="input-group">
                                        <span class="input-group-text">TZS</span>
                                        <input type="number" 
                                               class="form-control @error('approval_threshold') is-invalid @enderror" 
                                               id="approval_threshold" 
                                               name="approval_threshold" 
                                               value="{{ old('approval_threshold', $settings->max_transaction_amount ?? '') }}" 
                                               step="0.01" 
                                               min="0">
                                        @error('approval_threshold')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <small class="text-muted">
                                        Expenses above this amount require supervisor approval
                                        @if(isset($settings) && $settings->max_transaction_amount)
                                            <br><span class="text-info"><i class="bx bx-info-circle"></i> Default: {{ number_format($settings->max_transaction_amount, 2) }} TZS (from system settings)</span>
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="info-box">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Note:</strong> The current balance will be automatically set to the float amount when the unit is created.
                        </div>
                    </div>

                    <!-- Chart of Accounts Section -->
                    <div class="form-section">
                        <h6 class="form-section-title">
                            <i class="bx bx-book me-2"></i>Chart of Accounts
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="petty_cash_account_id" class="form-label">Petty Cash Account <span class="text-danger">*</span></label>
                                    <select class="form-select select2-single @error('petty_cash_account_id') is-invalid @enderror" 
                                            id="petty_cash_account_id" 
                                            name="petty_cash_account_id" 
                                            required>
                                        <option value="">Select Petty Cash Account</option>
                                        @foreach($pettyCashAccounts as $account)
                                            <option value="{{ $account->id }}" {{ old('petty_cash_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('petty_cash_account_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">GL account for petty cash asset</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="bank_account_id" class="form-label">Bank Account <span class="text-danger">*</span></label>
                                    <select class="form-select select2-single @error('bank_account_id') is-invalid @enderror" 
                                            id="bank_account_id" 
                                            name="bank_account_id" 
                                            required>
                                        <option value="">Select Bank Account</option>
                                        @foreach($bankAccounts as $bankAccount)
                                            <option value="{{ $bankAccount->id }}" {{ old('bank_account_id') == $bankAccount->id ? 'selected' : '' }}>
                                                {{ $bankAccount->name }} @if($bankAccount->account_number) ({{ $bankAccount->account_number }}) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('bank_account_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Bank account from which the petty cash float will be debited</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Notes Section -->
                    <div class="form-section">
                        <h6 class="form-section-title">
                            <i class="bx bx-note me-2"></i>Additional Information
                        </h6>
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                                              id="notes" 
                                              name="notes" 
                                              rows="4" 
                                              placeholder="Enter any additional notes or instructions for this petty cash unit...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Optional notes or special instructions</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="{{ route('accounting.petty-cash.units.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i>Create Petty Cash Unit
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2 for dropdowns
    if (typeof $().select2 !== 'undefined') {
        $('.select2-single').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }

    // Auto-generate code from name
    $('#name').on('blur', function() {
        if (!$('#code').val()) {
            let name = $(this).val();
            let code = name.toUpperCase()
                .replace(/[^A-Z0-9]/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '')
                .substring(0, 20);
            $('#code').val('PC-' + code);
        }
    });

    // Form validation
    $('#petty-cash-unit-form').on('submit', function(e) {
        let isValid = true;
        
        // Check required fields
        $(this).find('[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        if (!isValid) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please fill in all required fields.'
            });
        }
    });

    // Remove invalid class on input
    $('input, select, textarea').on('input change', function() {
        if ($(this).val()) {
            $(this).removeClass('is-invalid');
        }
    });
});
</script>
@endpush
@endsection
