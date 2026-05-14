@extends('layouts.main')

@section('title', 'Hotel & Property Settings')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel & Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-building-house'],
            ['label' => 'Settings', 'url' => route('hotel.property.settings'), 'icon' => 'bx bx-cog']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Hotel & Property Management Settings</h4>
                        <p class="card-subtitle text-muted">Configure chart of accounts for hotel and property operations</p>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('hotel.property.settings.update') }}">
            @csrf
            
            <!-- Hotel Chart Accounts -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-hotel me-2"></i>Hotel Chart Accounts
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Room Revenue Account</label>
                                        <select name="hotel_room_revenue_account_id" class="form-select select2-single">
                                            <option value="">Select Account</option>
                                            @foreach($revenueAccounts as $account)
                                                <option value="{{ $account->id }}" 
                                                    {{ $currentSettings['hotel_room_revenue_account_id'] == $account->id ? 'selected' : '' }}>
                                                    {{ $account->account_code }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Service Revenue Account</label>
                                        <select name="hotel_service_revenue_account_id" class="form-select select2-single">
                                            <option value="">Select Account</option>
                                            @foreach($revenueAccounts as $account)
                                                <option value="{{ $account->id }}"
                                                    {{ $currentSettings['hotel_service_revenue_account_id'] == $account->id ? 'selected' : '' }}>
                                                    {{ $account->account_code }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Food & Beverage Revenue Account</label>
                                        <select name="hotel_food_beverage_account_id" class="form-select select2-single">
                                            <option value="">Select Account</option>
                                            @foreach($revenueAccounts as $account)
                                                <option value="{{ $account->id }}"
                                                    {{ $currentSettings['hotel_food_beverage_account_id'] == $account->id ? 'selected' : '' }}>
                                                    {{ $account->account_code }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Expense Account</label>
                                        <select name="hotel_operating_expense_account_id" class="form-select select2-single">
                                            <option value="">Select Account</option>
                                            @foreach($expenseAccounts as $account)
                                                <option value="{{ $account->id }}"
                                                    {{ $currentSettings['hotel_operating_expense_account_id'] == $account->id ? 'selected' : '' }}>
                                                    {{ $account->account_code }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Maintenance Expense Account</label>
                                        <select name="hotel_maintenance_expense_account_id" class="form-select select2-single">
                                            <option value="">Select Account</option>
                                            @foreach($expenseAccounts as $account)
                                                <option value="{{ $account->id }}"
                                                    {{ $currentSettings['hotel_maintenance_expense_account_id'] == $account->id ? 'selected' : '' }}>
                                                    {{ $account->account_code }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Marketing Expense Account</label>
                                        <select name="hotel_marketing_expense_account_id" class="form-select select2-single">
                                            <option value="">Select Account</option>
                                            @foreach($expenseAccounts as $account)
                                                <option value="{{ $account->id }}"
                                                    {{ $currentSettings['hotel_marketing_expense_account_id'] == $account->id ? 'selected' : '' }}>
                                                    {{ $account->account_code }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Discount Expense Account</label>
                                        <select name="hotel_discount_expense_account_id" class="form-select select2-single">
                                            <option value="">Select Account</option>
                                            @foreach($expenseAccounts as $account)
                                                <option value="{{ $account->id }}"
                                                    {{ ($currentSettings['hotel_discount_expense_account_id'] ?? null) == $account->id ? 'selected' : '' }}>
                                                    {{ $account->account_code }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Property Chart Accounts -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-building me-2"></i>Property Chart Accounts
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Rental Income Account</label>
                                        <select name="property_rental_income_account_id" class="form-select select2-single">
                                            <option value="">Select Account</option>
                                            @foreach($revenueAccounts as $account)
                                                <option value="{{ $account->id }}"
                                                    {{ $currentSettings['property_rental_income_account_id'] == $account->id ? 'selected' : '' }}>
                                                    {{ $account->account_code }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Service Charge Account</label>
                                        <select name="property_service_charge_account_id" class="form-select select2-single">
                                            <option value="">Select Account</option>
                                            @foreach($revenueAccounts as $account)
                                                <option value="{{ $account->id }}"
                                                    {{ $currentSettings['property_service_charge_account_id'] == $account->id ? 'selected' : '' }}>
                                                    {{ $account->account_code }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Late Fee Account</label>
                                        <select name="property_late_fee_account_id" class="form-select select2-single">
                                            <option value="">Select Account</option>
                                            @foreach($revenueAccounts as $account)
                                                <option value="{{ $account->id }}"
                                                    {{ $currentSettings['property_late_fee_account_id'] == $account->id ? 'selected' : '' }}>
                                                    {{ $account->account_code }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Management Fee Account</label>
                                        <select name="property_management_fee_account_id" class="form-select select2-single">
                                            <option value="">Select Account</option>
                                            @foreach($revenueAccounts as $account)
                                                <option value="{{ $account->id }}"
                                                    {{ $currentSettings['property_management_fee_account_id'] == $account->id ? 'selected' : '' }}>
                                                    {{ $account->account_code }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Operating Expense Account</label>
                                        <select name="property_operating_expense_account_id" class="form-select select2-single">
                                            <option value="">Select Account</option>
                                            @foreach($expenseAccounts as $account)
                                                <option value="{{ $account->id }}"
                                                    {{ $currentSettings['property_operating_expense_account_id'] == $account->id ? 'selected' : '' }}>
                                                    {{ $account->account_code }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Maintenance Expense Account</label>
                                        <select name="property_maintenance_expense_account_id" class="form-select select2-single">
                                            <option value="">Select Account</option>
                                            @foreach($expenseAccounts as $account)
                                                <option value="{{ $account->id }}"
                                                    {{ $currentSettings['property_maintenance_expense_account_id'] == $account->id ? 'selected' : '' }}>
                                                    {{ $account->account_code }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Utilities Expense Account</label>
                                        <select name="property_utilities_expense_account_id" class="form-select select2-single">
                                            <option value="">Select Account</option>
                                            @foreach($expenseAccounts as $account)
                                                <option value="{{ $account->id }}"
                                                    {{ $currentSettings['property_utilities_expense_account_id'] == $account->id ? 'selected' : '' }}>
                                                    {{ $account->account_code }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Asset & Liability Accounts -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-wallet me-2"></i>Asset & Liability Accounts
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Property Asset Account</label>
                                        <select name="property_asset_account_id" class="form-select select2-single">
                                            <option value="">Select Account</option>
                                            @foreach($assetAccounts as $account)
                                                <option value="{{ $account->id }}"
                                                    {{ $currentSettings['property_asset_account_id'] == $account->id ? 'selected' : '' }}>
                                                    {{ $account->account_code }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Furniture & Fixtures Account</label>
                                        <select name="furniture_fixtures_account_id" class="form-select select2-single">
                                            <option value="">Select Account</option>
                                            @foreach($assetAccounts as $account)
                                                <option value="{{ $account->id }}"
                                                    {{ $currentSettings['furniture_fixtures_account_id'] == $account->id ? 'selected' : '' }}>
                                                    {{ $account->account_code }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Accumulated Depreciation Account</label>
                                        <select name="accumulated_depreciation_account_id" class="form-select select2-single">
                                            <option value="">Select Account</option>
                                            @foreach($assetAccounts as $account)
                                                <option value="{{ $account->id }}"
                                                    {{ $currentSettings['accumulated_depreciation_account_id'] == $account->id ? 'selected' : '' }}>
                                                    {{ $account->account_code }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Security Deposit Liability Account</label>
                                        <select name="security_deposit_liability_account_id" class="form-select select2-single">
                                            <option value="">Select Account</option>
                                            @foreach($liabilityAccounts as $account)
                                                <option value="{{ $account->id }}"
                                                    {{ $currentSettings['security_deposit_liability_account_id'] == $account->id ? 'selected' : '' }}>
                                                    {{ $account->account_code }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bx bx-save me-2"></i>Save Settings
                            </button>
                            <a href="{{ route('hotel.management.index') }}" class="btn btn-secondary btn-lg ms-2">
                                <i class="bx bx-arrow-back me-2"></i>Back to Hotel Management
                            </a>
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
    $(document).ready(function () {
        $('.select2-single').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select Account',
            allowClear: true
        });
    });
</script>
@endpush
