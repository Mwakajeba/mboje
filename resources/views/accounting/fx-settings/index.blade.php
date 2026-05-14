@extends('layouts.main')
@section('title', 'FX Settings')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'FX Settings', 'url' => '#', 'icon' => 'bx bx-cog']
        ]" />
        <h6 class="mb-0 text-uppercase">FX SETTINGS</h6>
        <hr />

        <!-- Page Header -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card border-top border-0 border-4 border-primary">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="card-title d-flex align-items-center">
                                    <div><i class="bx bx-cog me-1 font-22 text-primary"></i></div>
                                    <h5 class="mb-0 text-primary">Foreign Exchange Settings</h5>
                                </div>
                                <p class="mb-0 text-muted">Configure FX accounts and revaluation settings for IAS 21 compliance</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <!-- Settings Form -->
        <div class="row">
            <div class="col-12">
                <form action="{{ route('accounting.fx-settings.update') }}" method="POST" id="fxSettingsForm">
                    @csrf
                    @method('PUT')

                    <!-- Chart Accounts Section -->
                    <div class="card radius-10 border-0 shadow-sm mb-4">
                        <div class="card-header bg-transparent border-0">
                            <h6 class="mb-0"><i class="bx bx-book me-2"></i>Chart of Accounts Configuration</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">
                                        FX Realized Gain Account
                                        <i class="bx bx-info-circle text-info ms-1" data-bs-toggle="tooltip" 
                                           data-bs-placement="top" 
                                           title="Account for recording realized FX gains from settled transactions"></i>
                                    </label>
                                    <select name="fx_realized_gain_account_id" id="fx_realized_gain_account_id" 
                                            class="form-select select2-single">
                                        <option value="">-- Select Revenue Account --</option>
                                        @foreach($revenueAccounts as $account)
                                            <option value="{{ $account->id }}" 
                                                    {{ old('fx_realized_gain_account_id', $settings['fx_realized_gain_account_id']) == $account->id ? 'selected' : '' }}>
                                                [{{ $account->account_code }}] {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Used for realized FX gains from completed transactions</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        FX Realized Loss Account
                                        <i class="bx bx-info-circle text-info ms-1" data-bs-toggle="tooltip" 
                                           data-bs-placement="top" 
                                           title="Account for recording realized FX losses from settled transactions"></i>
                                    </label>
                                    <select name="fx_realized_loss_account_id" id="fx_realized_loss_account_id" 
                                            class="form-select select2-single">
                                        <option value="">-- Select Expense Account --</option>
                                        @foreach($expenseAccounts as $account)
                                            <option value="{{ $account->id }}" 
                                                    {{ old('fx_realized_loss_account_id', $settings['fx_realized_loss_account_id']) == $account->id ? 'selected' : '' }}>
                                                [{{ $account->account_code }}] {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Used for realized FX losses from completed transactions</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        FX Unrealized Gain Account
                                        <i class="bx bx-info-circle text-info ms-1" data-bs-toggle="tooltip" 
                                           data-bs-placement="top" 
                                           title="Account for recording unrealized FX gains from month-end revaluation"></i>
                                    </label>
                                    <select name="fx_unrealized_gain_account_id" id="fx_unrealized_gain_account_id" 
                                            class="form-select select2-single">
                                        <option value="">-- Select Revenue Account --</option>
                                        @foreach($revenueAccounts as $account)
                                            <option value="{{ $account->id }}" 
                                                    {{ old('fx_unrealized_gain_account_id', $settings['fx_unrealized_gain_account_id']) == $account->id ? 'selected' : '' }}>
                                                [{{ $account->account_code }}] {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Used for unrealized FX gains from month-end revaluation (IAS 21)</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        FX Unrealized Loss Account
                                        <i class="bx bx-info-circle text-info ms-1" data-bs-toggle="tooltip" 
                                           data-bs-placement="top" 
                                           title="Account for recording unrealized FX losses from month-end revaluation"></i>
                                    </label>
                                    <select name="fx_unrealized_loss_account_id" id="fx_unrealized_loss_account_id" 
                                            class="form-select select2-single">
                                        <option value="">-- Select Expense Account --</option>
                                        @foreach($expenseAccounts as $account)
                                            <option value="{{ $account->id }}" 
                                                    {{ old('fx_unrealized_loss_account_id', $settings['fx_unrealized_loss_account_id']) == $account->id ? 'selected' : '' }}>
                                                [{{ $account->account_code }}] {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Used for unrealized FX losses from month-end revaluation (IAS 21)</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- General Settings Section -->
                    <div class="card radius-10 border-0 shadow-sm mb-4">
                        <div class="card-header bg-transparent border-0">
                            <h6 class="mb-0"><i class="bx bx-cog me-2"></i>General Settings</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">
                                        Functional Currency <span class="text-danger">*</span>
                                        <i class="bx bx-info-circle text-info ms-1" data-bs-toggle="tooltip" 
                                           data-bs-placement="top" 
                                           title="The primary currency in which the company reports its financial results"></i>
                                    </label>
                                    <input type="text" name="functional_currency" id="functional_currency" 
                                           class="form-control" 
                                           value="{{ old('functional_currency', $settings['functional_currency']) }}" 
                                           maxlength="3" required>
                                    <small class="text-muted">Base currency for financial reporting (e.g., TZS, USD, EUR)</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        FX Rate Override Threshold (%)
                                        <i class="bx bx-info-circle text-info ms-1" data-bs-toggle="tooltip" 
                                           data-bs-placement="top" 
                                           title="If a rate override exceeds this percentage from the market rate, approval is required"></i>
                                    </label>
                                    <input type="number" name="fx_rate_override_threshold" id="fx_rate_override_threshold" 
                                           class="form-control" 
                                           value="{{ old('fx_rate_override_threshold', $settings['fx_rate_override_threshold']) }}" 
                                           min="0" max="100" step="0.01">
                                    <small class="text-muted">Default: 5%. Rate overrides exceeding this threshold require approval.</small>
                                </div>

                                <div class="col-md-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="fx_revaluation_approval_required" 
                                               id="fx_revaluation_approval_required" 
                                               {{ old('fx_revaluation_approval_required', $settings['fx_revaluation_approval_required']) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="fx_revaluation_approval_required">
                                            <strong>Require Approval for FX Revaluation</strong>
                                            <i class="bx bx-info-circle text-info ms-1" data-bs-toggle="tooltip" 
                                               data-bs-placement="top" 
                                               title="When enabled, FX revaluation journal entries require approval before posting"></i>
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        When enabled, all FX revaluation journal entries will require approval before being posted to the general ledger.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="card radius-10 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('accounting.index') }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i> Save Settings
                                </button>
                            </div>
                        </div>
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
        // Initialize Select2 for chart account dropdowns
        $('.select2-single').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select an account',
            allowClear: true
        });

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endpush

