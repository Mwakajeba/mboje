@extends('layouts.main')

@section('title', 'Budget Settings')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Settings', 'url' => route('settings.index'), 'icon' => 'bx bx-cog'],
                ['label' => 'Budget Settings', 'url' => '#', 'icon' => 'bx bx-chart']
            ]" />
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h6 class="mb-0 text-uppercase">BUDGET SETTINGS</h6>
                    <p class="text-muted mb-0">Configure budget checking and over-budget allowances for expenses</p>
                </div>
                <div>
                    <a href="{{ route('settings.index') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i> Back to Settings
                    </a>
                </div>
            </div>
            <hr />

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">
                                <i class="bx bx-chart me-2"></i>Budget Configuration
                            </h4>

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

                            <form action="{{ route('settings.budget.update') }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="row">
                                    <!-- Budget Check Configuration -->
                                    <div class="col-12">
                                        <h5 class="mb-3 text-primary">
                                            <i class="bx bx-shield me-2"></i>Budget Check Configuration
                                        </h5>
                                    </div>

                                    <div class="col-md-12 mb-4">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" 
                                                           type="checkbox" 
                                                           id="budget_check_enabled" 
                                                           name="budget_check_enabled" 
                                                           value="1"
                                                           {{ old('budget_check_enabled', $budgetCheckEnabled) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-bold" for="budget_check_enabled">
                                                        Enable Budget Checking
                                                    </label>
                                                </div>
                                                <p class="text-muted mt-2 mb-0">
                                                    <i class="bx bx-info-circle me-1"></i>
                                                    When enabled, the system will check if expenses exceed budget limits before allowing transactions. 
                                                    This helps prevent overspending and ensures better budget control.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12 mb-4">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" 
                                                           type="checkbox" 
                                                           id="budget_require_allocation" 
                                                           name="budget_require_allocation" 
                                                           value="1"
                                                           {{ old('budget_require_allocation', $budgetRequireAllocation) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-bold" for="budget_require_allocation">
                                                        Require Budget Allocation
                                                    </label>
                                                </div>
                                                <p class="text-muted mt-2 mb-0">
                                                    <i class="bx bx-info-circle me-1"></i>
                                                    When enabled, payment vouchers for accounts not included in the budget will be <strong>blocked</strong>. 
                                                    When disabled, they will be <strong>allowed with a warning</strong>. 
                                                    This setting only applies when budget checking is enabled.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Over Budget Allowance -->
                                    <div class="col-12">
                                        <h5 class="mb-3 text-primary">
                                            <i class="bx bx-percent me-2"></i>Over Budget Allowance
                                        </h5>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="budget_over_budget_percentage" class="form-label fw-bold">
                                            Over Budget Percentage Allowed (%)
                                            <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="number"
                                                class="form-control @error('budget_over_budget_percentage') is-invalid @enderror"
                                                id="budget_over_budget_percentage" 
                                                name="budget_over_budget_percentage"
                                                value="{{ old('budget_over_budget_percentage', $budgetOverBudgetPercentage) }}" 
                                                min="0" 
                                                max="100" 
                                                step="1"
                                                placeholder="Enter percentage">
                                            <span class="input-group-text">%</span>
                                        </div>
                                        <div class="form-text">
                                            <i class="bx bx-info-circle me-1"></i>
                                            Percentage over budget that is allowed before blocking expenses. 
                                            For example, if set to <strong>10%</strong>, expenses can exceed the budget by up to 10% before being blocked.
                                            Set to <strong>0</strong> to strictly enforce budget limits with no over-budget allowance.
                                        </div>
                                        @error('budget_over_budget_percentage')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <div class="card bg-info bg-opacity-10">
                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    <i class="bx bx-bulb me-2"></i>How It Works
                                                </h6>
                                                <ul class="mb-0 small">
                                                    <li>When budget checking is <strong>enabled</strong>, the system validates expenses against budget limits.</li>
                                                    <li>If an expense exceeds the budget by more than the allowed percentage, it will be <strong>blocked</strong>.</li>
                                                    <li>If an expense is within the allowed over-budget percentage, it will be <strong>allowed with a warning</strong>.</li>
                                                    <li>If "Require Budget Allocation" is enabled, accounts not in the budget will be <strong>blocked</strong>.</li>
                                                    <li>If "Require Budget Allocation" is disabled, accounts not in the budget will be <strong>allowed with a warning</strong>.</li>
                                                    <li>When budget checking is <strong>disabled</strong>, expenses are processed without budget validation.</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('settings.index') }}" class="btn btn-secondary">
                                        <i class="bx bx-x me-1"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i> Update Budget Settings
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
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Show/hide over budget percentage field and require allocation based on budget check enabled
    function toggleBudgetFields() {
        const isEnabled = $('#budget_check_enabled').is(':checked');
        $('#budget_over_budget_percentage').closest('.col-md-6').toggle(isEnabled);
        $('#budget_require_allocation').closest('.col-md-12').toggle(isEnabled);
    }
    
    toggleBudgetFields();
    
    $('#budget_check_enabled').on('change', function() {
        toggleBudgetFields();
    });
});
</script>
@endpush

