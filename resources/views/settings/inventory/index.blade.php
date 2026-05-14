@extends('layouts.main')

@section('title', 'Inventory Settings')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Settings', 'url' => route('settings.index'), 'icon' => 'bx bx-cog'],
            ['label' => 'Inventory', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Inventory Settings', 'url' => '#', 'icon' => 'bx bx-package']
        ]" />
        
        <h6 class="mb-0 text-uppercase">INVENTORY SETTINGS</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Configure Inventory Management Settings</h4>

                        @php
                            $costMethod = \App\Models\SystemSetting::where('key', 'inventory_cost_method')->value('value');
                        @endphp

                        @if(!$costMethod)
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="bx bx-exclamation-triangle me-2"></i>
                            <strong>Important:</strong> You must set your preferred cost method (FIFO or Weighted Average Cost) before you can perform any inventory operations. This setting cannot be changed once set.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bx bx-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        @if(isset($errors) && $errors->any())
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

                        <form method="POST" action="{{ route('settings.inventory.update') }}">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <!-- Stock Management Settings -->
                                <div class="col-lg-6">
                                    <div class="card border-primary">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="mb-0">
                                                <i class="bx bx-box me-2"></i>Stock Management
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="low_stock_threshold" class="form-label">Low Stock Threshold</label>
                                                <input type="number" class="form-control" id="low_stock_threshold" 
                                                       name="low_stock_threshold" value="{{ old('low_stock_threshold', $currentSettings['low_stock_threshold']) }}" 
                                                       min="0" step="1" required>
                                                <small class="text-muted">Alert when stock quantity falls below this number</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="auto_reorder_point" class="form-label">Auto Reorder Point</label>
                                                <input type="number" class="form-control" id="auto_reorder_point" 
                                                       name="auto_reorder_point" value="{{ old('auto_reorder_point', $currentSettings['auto_reorder_point']) }}" 
                                                       min="0" step="1" required>
                                                <small class="text-muted">Automatically suggest reordering when stock reaches this level</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="default_unit" class="form-label">Default Unit of Measurement</label>
                                                <select class="form-select" id="default_unit" name="default_unit" required>
                                                    <option value="pieces" {{ old('default_unit', $currentSettings['default_unit']) == 'pieces' ? 'selected' : '' }}>Pieces</option>
                                                    <option value="kg" {{ old('default_unit', $currentSettings['default_unit']) == 'kg' ? 'selected' : '' }}>Kilograms</option>
                                                    <option value="g" {{ old('default_unit', $currentSettings['default_unit']) == 'g' ? 'selected' : '' }}>Grams</option>
                                                    <option value="liters" {{ old('default_unit', $currentSettings['default_unit']) == 'liters' ? 'selected' : '' }}>Liters</option>
                                                    <option value="ml" {{ old('default_unit', $currentSettings['default_unit']) == 'ml' ? 'selected' : '' }}>Milliliters</option>
                                                    <option value="meters" {{ old('default_unit', $currentSettings['default_unit']) == 'meters' ? 'selected' : '' }}>Meters</option>
                                                    <option value="cm" {{ old('default_unit', $currentSettings['default_unit']) == 'cm' ? 'selected' : '' }}>Centimeters</option>
                                                    <option value="boxes" {{ old('default_unit', $currentSettings['default_unit']) == 'boxes' ? 'selected' : '' }}>Boxes</option>
                                                    <option value="sets" {{ old('default_unit', $currentSettings['default_unit']) == 'sets' ? 'selected' : '' }}>Sets</option>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="default_location" class="form-label">Default Storage Location</label>
                                                <select class="form-select" id="default_location" name="default_location">
                                                    <option value="">Select default storage location</option>
                                                    @foreach($inventoryLocations as $loc)
                                                    <option value="{{ $loc->id }}" {{ (int) old('default_location', $currentSettings['default_location'] ?? 1) === (int) $loc->id ? 'selected' : '' }}>
                                                        {{ $loc->name }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted">Default location for new inventory items. Manage locations in Inventory → Locations.</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="cost_method" class="form-label">Cost Method</label>
                                                <select class="form-select" id="cost_method" name="cost_method" required>
                                                    <option value="fifo" {{ old('cost_method', $currentSettings['cost_method']) == 'fifo' ? 'selected' : '' }}>FIFO (First In, First Out)</option>
                                                    <option value="weighted_average" {{ old('cost_method', $currentSettings['cost_method']) == 'weighted_average' ? 'selected' : '' }}>Weighted Average Cost</option>
                                                </select>
                                                <small class="text-muted">Method for calculating inventory cost. <strong>Note:</strong> This setting cannot be changed once set.</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="default_invoice_due_days" class="form-label">Default Invoice Due Days</label>
                                                <input
                                                    type="number"
                                                    class="form-control"
                                                    id="default_invoice_due_days"
                                                    name="default_invoice_due_days"
                                                    value="{{ old('default_invoice_due_days', $currentSettings['default_invoice_due_days'] ?? 30) }}"
                                                    min="0"
                                                >
                                                <small class="text-muted">
                                                    Number of days after invoice date that sales and purchase invoices are due by default. Use 0 for due on invoice date.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tracking Settings -->
                                <div class="col-lg-6">
                                    <div class="card border-success">
                                        <div class="card-header bg-success text-white">
                                            <h5 class="mb-0">
                                                <i class="bx bx-search-alt me-2"></i>Tracking Settings
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="barcode_prefix" class="form-label">Barcode Prefix</label>
                                                <input type="text" class="form-control" id="barcode_prefix" 
                                                       name="barcode_prefix" value="{{ old('barcode_prefix', $currentSettings['barcode_prefix']) }}" 
                                                       maxlength="10">
                                                <small class="text-muted">Prefix for auto-generated barcodes (e.g., INV001, INV002)</small>
                                            </div>

                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="enable_batch_tracking" 
                                                           name="enable_batch_tracking" value="1" 
                                                           {{ old('enable_batch_tracking', $currentSettings['enable_batch_tracking']) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="enable_batch_tracking">
                                                        Enable Batch Tracking
                                                    </label>
                                                </div>
                                                <small class="text-muted">Track inventory items by production batches</small>
                                            </div>

                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="enable_expiry_tracking" 
                                                           name="enable_expiry_tracking" value="1" 
                                                           {{ old('enable_expiry_tracking', $currentSettings['enable_expiry_tracking']) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="enable_expiry_tracking">
                                                        Enable Expiry Date Tracking
                                                    </label>
                                                </div>
                                                <small class="text-muted">Track expiry dates for perishable items</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="global_expiry_warning_days" class="form-label">Global Expiry Warning Days</label>
                                                <input type="number" class="form-control" id="global_expiry_warning_days" 
                                                       name="global_expiry_warning_days" 
                                                       value="{{ old('global_expiry_warning_days', $currentSettings['global_expiry_warning_days'] ?? 30) }}" 
                                                       min="1" max="365">
                                                <small class="text-muted">Number of days before expiry to show warnings for all items</small>
                                            </div>

                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="enable_serial_tracking" 
                                                           name="enable_serial_tracking" value="1" 
                                                           {{ old('enable_serial_tracking', $currentSettings['enable_serial_tracking']) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="enable_serial_tracking">
                                                        Enable Serial Number Tracking
                                                    </label>
                                                </div>
                                                <small class="text-muted">Track individual items by serial numbers</small>
                                            </div>
                                                <div class="mb-3">
                                                <label for="default_vat_type" class="form-label">Default VAT Type</label>
                                                <select class="form-select" id="default_vat_type" name="default_vat_type">
                                                    <option value="inclusive" {{ old('default_vat_type', $currentSettings['default_vat_type'] ?? 'inclusive') == 'inclusive' ? 'selected' : '' }}>
                                                        VAT Inclusive
                                                    </option>
                                                    <option value="exclusive" {{ old('default_vat_type', $currentSettings['default_vat_type'] ?? 'inclusive') == 'exclusive' ? 'selected' : '' }}>
                                                        VAT Exclusive
                                                    </option>
                                                    <option value="no_vat" {{ old('default_vat_type', $currentSettings['default_vat_type'] ?? 'inclusive') == 'no_vat' ? 'selected' : '' }}>
                                                        No VAT
                                                    </option>
                                                </select>
                                                <small class="text-muted">Default VAT type for new inventory items and transactions</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="default_vat_rate" class="form-label">Default VAT Rate (%)</label>
                                                <input type="number" class="form-control" id="default_vat_rate" 
                                                       name="default_vat_rate" value="{{ old('default_vat_rate', $currentSettings['default_vat_rate'] ?? '18.00') }}" 
                                                       min="0" max="100" step="0.01">
                                                <small class="text-muted">Default VAT rate percentage for new items</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Accounting Settings -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card border-warning">
                                        <div class="card-header bg-warning text-white">
                                            <h5 class="mb-0">
                                                <i class="bx bx-calculator me-2"></i>Default Accounting Settings
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="default_inventory_account" class="form-label">Default Inventory Account</label>
                                                        <select class="form-select select2-single" id="default_inventory_account" name="default_inventory_account" required>
                                                            <option value="">Select Inventory Account (Assets)</option>
                                                            @foreach($inventoryAccounts as $account)
                                                            <option value="{{ $account->id }}" {{ old('default_inventory_account', $currentSettings['default_inventory_account']) == $account->id ? 'selected' : '' }}>
                                                                {{ $account->account_code }} - {{ $account->account_name }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted">Account used for inventory asset valuation</small>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="default_sales_account" class="form-label">Default Sales Account</label>
                                                        <select class="form-select select2-single" id="default_sales_account" name="default_sales_account" required>
                                                            <option value="">Select Sales Account (Revenue)</option>
                                                            @foreach($salesAccounts as $account)
                                                            <option value="{{ $account->id }}" {{ old('default_sales_account', $currentSettings['default_sales_account']) == $account->id ? 'selected' : '' }}>
                                                                {{ $account->account_code }} - {{ $account->account_name }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted">Account used for recording sales transactions</small>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="default_cost_account" class="form-label">Default Cost Account</label>
                                                        <select class="form-select select2-single" id="default_cost_account" name="default_cost_account" required>
                                                            <option value="">Select Cost Account (Expenses)</option>
                                                            @foreach($costAccounts as $account)
                                                            <option value="{{ $account->id }}" {{ old('default_cost_account', $currentSettings['default_cost_account']) == $account->id ? 'selected' : '' }}>
                                                                {{ $account->account_code }} - {{ $account->account_name }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted">Account used for recording cost of goods sold</small>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="default_opening_balance_account" class="form-label">Default Opening Balance Account</label>
                                                        <select class="form-select select2-single" id="default_opening_balance_account" name="default_opening_balance_account" required>
                                                            <option value="">Select Opening Balance Account (Equity)</option>
                                                            @foreach($openingBalanceAccounts as $account)
                                                            <option value="{{ $account->id }}" {{ old('default_opening_balance_account', $currentSettings['default_opening_balance_account']) == $account->id ? 'selected' : '' }}>
                                                                {{ $account->account_code }} - {{ $account->account_name }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted">Account used for recording opening inventory balances</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Inventory Count Adjustment Accounts -->
                                            <div class="row mt-3">
                                                <div class="col-12">
                                                    <h6 class="text-info mb-3">
                                                        <i class="bx bx-bar-chart me-2"></i>Inventory Count Adjustment Accounts
                                                    </h6>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="inventory_loss_expense_account" class="form-label">Inventory Loss Expense Account</label>
                                                        <select class="form-select select2-single" id="inventory_loss_expense_account" name="inventory_loss_expense_account">
                                                            <option value="">Select Loss Expense Account (Expenses)</option>
                                                            @foreach($expenseAccounts as $account)
                                                            <option value="{{ $account->id }}" {{ old('inventory_loss_expense_account', $currentSettings['inventory_loss_expense_account'] ?? '') == $account->id ? 'selected' : '' }}>
                                                                {{ $account->account_code }} - {{ $account->account_name }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted">Account used for recording inventory shortages/losses from count adjustments</small>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="inventory_gain_income_account" class="form-label">Inventory Gain Income Account</label>
                                                        <select class="form-select select2-single" id="inventory_gain_income_account" name="inventory_gain_income_account">
                                                            <option value="">Select Gain Income Account (Revenue)</option>
                                                            @foreach($incomeAccounts as $account)
                                                            <option value="{{ $account->id }}" {{ old('inventory_gain_income_account', $currentSettings['inventory_gain_income_account'] ?? '') == $account->id ? 'selected' : '' }}>
                                                                {{ $account->account_code }} - {{ $account->account_name }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted">Account used for recording inventory surpluses/gains from count adjustments</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Withholding Tax Settings -->
                                            <div class="row mt-3">
                                                <div class="col-12">
                                                    <h6 class="text-primary mb-3">
                                                        <i class="bx bx-receipt me-2"></i>Withholding Tax Settings
                                                    </h6>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="default_vat_account" class="form-label">Default VAT Account</label>
                                                        <select class="form-select select2-single" id="default_vat_account" name="default_vat_account">
                                                            <option value="">Select VAT Account (Liabilities)</option>
                                                            @foreach($vatAccounts as $account)
                                                            <option value="{{ $account->id }}" {{ old('default_vat_account', $currentSettings['default_vat_account'] ?? '') == $account->id ? 'selected' : '' }}>
                                                                {{ $account->account_code }} - {{ $account->account_name }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted">Account used for VAT liability tracking</small>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="default_withholding_tax_account" class="form-label">Default Withholding Tax Account</label>
                                                        <select class="form-select select2-single" id="default_withholding_tax_account" name="default_withholding_tax_account">
                                                            <option value="">Select Withholding Tax Account (Liabilities)</option>
                                                            @foreach($withholdingTaxAccounts as $account)
                                                            <option value="{{ $account->id }}" {{ old('default_withholding_tax_account', $currentSettings['default_withholding_tax_account'] ?? '') == $account->id ? 'selected' : '' }}>
                                                                {{ $account->account_code }} - {{ $account->account_name }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted">Account used for withholding tax liability tracking</small>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="default_withholding_tax_expense_account" class="form-label">Default Withholding Tax Expense Account</label>
                                                        <select class="form-select select2-single" id="default_withholding_tax_expense_account" name="default_withholding_tax_expense_account">
                                                            <option value="">Select Withholding Tax Expense Account (Expenses)</option>
                                                            @foreach($withholdingTaxExpenseAccounts as $account)
                                                            <option value="{{ $account->id }}" {{ old('default_withholding_tax_expense_account', $currentSettings['default_withholding_tax_expense_account'] ?? '') == $account->id ? 'selected' : '' }}>
                                                                {{ $account->account_code }} - {{ $account->account_name }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted">Account used for withholding tax expense tracking</small>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="default_purchase_payable_account" class="form-label">Default Purchase Payable Account</label>
                                                        <select class="form-select select2-single" id="default_purchase_payable_account" name="default_purchase_payable_account">
                                                            <option value="">Select Purchase Payable Account (Liabilities)</option>
                                                            @foreach($purchasePayableAccounts as $account)
                                                            <option value="{{ $account->id }}" {{ old('default_purchase_payable_account', $currentSettings['default_purchase_payable_account'] ?? '') == $account->id ? 'selected' : '' }}>
                                                                {{ $account->account_code }} - {{ $account->account_name }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted">Account used for purchase payable tracking</small>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="default_discount_account" class="form-label">Default Discount Account</label>
                                                        <select class="form-select select2-single" id="default_discount_account" name="default_discount_account">
                                                            <option value="">Select Discount Account (Expenses)</option>
                                                            @foreach($discountAccounts as $account)
                                                            <option value="{{ $account->id }}" {{ old('default_discount_account', $currentSettings['default_discount_account'] ?? '') == $account->id ? 'selected' : '' }}>
                                                                {{ $account->account_code }} - {{ $account->account_name }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted">Account used for discount expense tracking</small>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="default_discount_income_account" class="form-label">Default Discount Income Account</label>
                                                        <select class="form-select select2-single" id="default_discount_income_account" name="default_discount_income_account">
                                                            <option value="">Select Discount Income Account (Revenue)</option>
                                                            @foreach($discountIncomeAccounts as $account)
                                                            <option value="{{ $account->id }}" {{ old('default_discount_income_account', $currentSettings['default_discount_income_account'] ?? '') == $account->id ? 'selected' : '' }}>
                                                                {{ $account->account_code }} - {{ $account->account_name }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted">Account used for discount income tracking</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Default Withholding Tax Type</label>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="default_is_withholding_receivable" id="default_withholding_payable" value="0" {{ old('default_is_withholding_receivable', $currentSettings['default_is_withholding_receivable'] ?? false) == false ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="default_withholding_payable">
                                                                Payable (We pay withholding tax)
                                                            </label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="default_is_withholding_receivable" id="default_withholding_receivable" value="1" {{ old('default_is_withholding_receivable', $currentSettings['default_is_withholding_receivable'] ?? false) == true ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="default_withholding_receivable">
                                                                Receivable (We receive withholding tax)
                                                            </label>
                                                        </div>
                                                        <small class="text-muted">Default withholding tax type for new inventory items</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Payment Terms Account Settings -->
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="default_early_payment_discount_account" class="form-label">Default Early Payment Discount Account</label>
                                                        <select class="form-select select2-single" id="default_early_payment_discount_account" name="default_early_payment_discount_account">
                                                            <option value="">Select Early Payment Discount Account (Expense)</option>
                                                            @foreach($earlyPaymentDiscountAccounts as $account)
                                                            <option value="{{ $account->id }}" {{ old('default_early_payment_discount_account', $currentSettings['default_early_payment_discount_account'] ?? '') == $account->id ? 'selected' : '' }}>
                                                                {{ $account->account_code }} - {{ $account->account_name }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted">Account used for early payment discount expenses</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="default_late_payment_fees_account" class="form-label">Default Late Payment Fees Account</label>
                                                        <select class="form-select select2-single" id="default_late_payment_fees_account" name="default_late_payment_fees_account">
                                                            <option value="">Select Late Payment Fees Account (Revenue)</option>
                                                            @foreach($latePaymentFeesAccounts as $account)
                                                            <option value="{{ $account->id }}" {{ old('default_late_payment_fees_account', $currentSettings['default_late_payment_fees_account'] ?? '') == $account->id ? 'selected' : '' }}>
                                                                {{ $account->account_code }} - {{ $account->account_name }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted">Account used for late payment fees income</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="default_receivable_account" class="form-label">Default Accounts Receivable Account</label>
                                                        <select class="form-select select2-single" id="default_receivable_account" name="default_receivable_account">
                                                            <option value="">Select Accounts Receivable Account (Asset)</option>
                                                            @foreach($receivableAccounts as $account)
                                                            <option value="{{ $account->id }}" {{ old('default_receivable_account', $currentSettings['default_receivable_account'] ?? '') == $account->id ? 'selected' : '' }}>
                                                                {{ $account->account_code }} - {{ $account->account_name }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted">Account used for accounts receivable tracking</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="default_cash_account" class="form-label">Default Cash Account</label>
                                                        <select class="form-select select2-single" id="default_cash_account" name="default_cash_account">
                                                            <option value="">Select Cash Account (Cash on Hand)</option>
                                                            @foreach($cashAccounts as $account)
                                                            <option value="{{ $account->id }}" {{ (int)($currentSettings['default_cash_account'] ?? 0) == (int)$account->id ? 'selected' : '' }}>
                                                                {{ $account->account_code }} - {{ $account->account_name }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted">Account used for cash transactions (cash sales, cash purchases, receipts, payments)</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="default_transport_revenue_account" class="form-label">Default Transport Revenue Account</label>
                                                        <select class="form-select select2-single" id="default_transport_revenue_account" name="default_transport_revenue_account">
                                                            <option value="">Select Transport Revenue Account (Revenue)</option>
                                                            @foreach($transportRevenueAccounts as $account)
                                                            <option value="{{ $account->id }}" {{ old('default_transport_revenue_account', $currentSettings['default_transport_revenue_account'] ?? '') == $account->id ? 'selected' : '' }}>
                                                                {{ $account->account_code }} - {{ $account->account_name }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted">Account used for transport/delivery service revenue</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Cheque Issued Account -->
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="cheque_issued_account_id" class="form-label">Cheque Issued Account</label>
                                                        <select class="form-select select2-single" id="cheque_issued_account_id" name="cheque_issued_account_id">
                                                            <option value="">Select Cheque Issued Account (Liability)</option>
                                                            @foreach($chequeIssuedAccounts as $account)
                                                            <option value="{{ $account->id }}" {{ old('cheque_issued_account_id', $currentSettings['cheque_issued_account_id'] ?? '') == $account->id ? 'selected' : '' }}>
                                                                {{ $account->account_code }} - {{ $account->account_name }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted">Contra account for tracking outstanding cheques issued but not yet cleared</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="cheques_in_transit_account_id" class="form-label">Cheques in Transit Account</label>
                                                        <select class="form-select select2-single" id="cheques_in_transit_account_id" name="cheques_in_transit_account_id">
                                                            <option value="">Select Cheques in Transit Account (Asset)</option>
                                                            @foreach($chequesInTransitAccounts as $account)
                                                            <option value="{{ $account->id }}" {{ (int)($currentSettings['cheques_in_transit_account_id'] ?? 0) == (int)$account->id ? 'selected' : '' }}>
                                                                {{ $account->account_code }} - {{ $account->account_name }}
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted">Asset account for tracking cheques received but not yet deposited to bank</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Inventory Count Variance Settings -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card border-success">
                                        <div class="card-header bg-success text-white">
                                            <h5 class="mb-0">
                                                <i class="bx bx-bar-chart-alt-2 me-2"></i>Inventory Count Variance Settings
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="alert alert-info">
                                                <i class="bx bx-info-circle me-2"></i>
                                                <strong>Note:</strong> Configure thresholds for variance detection during inventory counts. 
                                                Variances exceeding these thresholds will be flagged as high-value and require investigation.
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="variance_value_threshold" class="form-label">Variance Value Threshold (TZS)</label>
                                                        <input type="number" class="form-control" id="variance_value_threshold" 
                                                               name="variance_value_threshold" 
                                                               value="{{ old('variance_value_threshold', $currentSettings['variance_value_threshold'] ?? 50000) }}" 
                                                               min="0" step="0.01" required>
                                                        <small class="text-muted">Variances with value ≥ this amount will be flagged as high-value (default: 50,000 TZS)</small>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="variance_percentage_threshold" class="form-label">Variance Percentage Threshold (%)</label>
                                                        <input type="number" class="form-control" id="variance_percentage_threshold" 
                                                               name="variance_percentage_threshold" 
                                                               value="{{ old('variance_percentage_threshold', $currentSettings['variance_percentage_threshold'] ?? 5) }}" 
                                                               min="0" max="100" step="0.01" required>
                                                        <small class="text-muted">Variances with percentage ≥ this value will be flagged as high-value (default: 5%)</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="alert alert-warning">
                                                <i class="bx bx-exclamation-triangle me-2"></i>
                                                <strong>How it works:</strong> A variance is flagged as high-value if <strong>either</strong> the variance value 
                                                exceeds the value threshold <strong>OR</strong> the variance percentage exceeds the percentage threshold. 
                                                High-value variances are automatically set to "pending" status and require investigation/recount.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Count Session Approval Settings -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card border-info">
                                        <div class="card-header bg-info text-white">
                                            <h5 class="mb-0">
                                                <i class="bx bx-check-circle me-2"></i>Count Session Approval Settings
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="alert alert-info">
                                                <i class="bx bx-info-circle me-2"></i>
                                                <strong>Note:</strong> Configure who can approve completed count sessions. 
                                                These settings determine the approval workflow for inventory count sessions.
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Number of Approval Levels</label>
                                                <select class="form-select" id="approval_levels" name="approval_levels" onchange="toggleApprovalLevels()">
                                                    <option value="1" {{ ($approvalSettings->approval_levels ?? 1) == 1 ? 'selected' : '' }}>1 Level</option>
                                                    <option value="2" {{ ($approvalSettings->approval_levels ?? 1) == 2 ? 'selected' : '' }}>2 Levels</option>
                                                    <option value="3" {{ ($approvalSettings->approval_levels ?? 1) == 3 ? 'selected' : '' }}>3 Levels</option>
                                                    <option value="4" {{ ($approvalSettings->approval_levels ?? 1) == 4 ? 'selected' : '' }}>4 Levels</option>
                                                    <option value="5" {{ ($approvalSettings->approval_levels ?? 1) == 5 ? 'selected' : '' }}>5 Levels</option>
                                                </select>
                                                <small class="text-muted">Number of approval levels required for count sessions</small>
                                            </div>

                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="require_approval_for_all" name="require_approval_for_all" 
                                                           {{ ($approvalSettings->require_approval_for_all ?? true) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="require_approval_for_all">
                                                        Require Approval for All Count Sessions
                                                    </label>
                                                </div>
                                                <small class="text-muted">If disabled, some sessions may not require approval</small>
                                            </div>

                                            @php
                                                $levels = $approvalSettings->approval_levels ?? 1;
                                                $roles = \Spatie\Permission\Models\Role::where('guard_name', 'web')->orderBy('name')->get();
                                                $users = \App\Models\User::where('company_id', auth()->user()->company_id)->where('status', 'active')->orderBy('name')->get();
                                            @endphp

                                            <div id="approval-levels-container">
                                                @for($i = 1; $i <= 5; $i++)
                                                <div class="approval-level-section mb-4 {{ $i > $levels ? 'd-none' : '' }}" data-level="{{ $i }}">
                                                    <div class="card border-secondary">
                                                        <div class="card-header bg-secondary bg-opacity-10">
                                                            <h6 class="mb-0">Level {{ $i }} Approval</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div class="col-md-4">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Level Name</label>
                                                                        <input type="text" class="form-control" name="level{{ $i }}_name" 
                                                                               value="{{ $approvalSettings->{"level{$i}_name"} ?? ($i == 1 ? 'Supervisor' : "Level {$i}") }}" 
                                                                               placeholder="e.g., Supervisor, Manager">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Approval Type</label>
                                                                        <select class="form-select" name="level{{ $i }}_approval_type" onchange="toggleApproversType({{ $i }})">
                                                                            <option value="role" {{ ($approvalSettings->{"level{$i}_approval_type"} ?? 'role') == 'role' ? 'selected' : '' }}>By Role</option>
                                                                            <option value="user" {{ ($approvalSettings->{"level{$i}_approval_type"} ?? 'role') == 'user' ? 'selected' : '' }}>By User</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Approvers</label>
                                                                        <select class="form-select select2-multiple" name="level{{ $i }}_approvers[]" multiple 
                                                                                id="level{{ $i }}_approvers" 
                                                                                data-approval-type="{{ $approvalSettings->{"level{$i}_approval_type"} ?? 'role' }}"
                                                                                data-level="{{ $i }}">
                                                                            @php
                                                                                $currentType = $approvalSettings->{"level{$i}_approval_type"} ?? 'role';
                                                                                $currentApprovers = $approvalSettings->{"level{$i}_approvers"} ?? [];
                                                                            @endphp
                                                                            @if($currentType == 'role')
                                                                                @foreach($roles as $role)
                                                                                <option value="{{ $role->name }}" 
                                                                                        {{ in_array($role->name, $currentApprovers) ? 'selected' : '' }}
                                                                                        data-type="role">
                                                                                    {{ $role->name }}
                                                                                </option>
                                                                                @endforeach
                                                                                @foreach($users as $user)
                                                                                <option value="{{ $user->id }}" 
                                                                                        {{ in_array($user->id, $currentApprovers) ? 'selected' : '' }}
                                                                                        data-type="user" style="display:none;">
                                                                                    {{ $user->name }} ({{ $user->email }})
                                                                                </option>
                                                                                @endforeach
                                                                            @else
                                                                                @foreach($roles as $role)
                                                                                <option value="{{ $role->name }}" 
                                                                                        {{ in_array($role->name, $currentApprovers) ? 'selected' : '' }}
                                                                                        data-type="role" style="display:none;">
                                                                                    {{ $role->name }}
                                                                                </option>
                                                                                @endforeach
                                                                                @foreach($users as $user)
                                                                                <option value="{{ $user->id }}" 
                                                                                        {{ in_array($user->id, $currentApprovers) ? 'selected' : '' }}
                                                                                        data-type="user">
                                                                                    {{ $user->name }} ({{ $user->email }})
                                                                                </option>
                                                                                @endforeach
                                                                            @endif
                                                                        </select>
                                                                        <small class="text-muted">Select who can approve at this level</small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endfor
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Settings -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card border-info">
                                        <div class="card-header bg-info text-white">
                                            <h5 class="mb-0">
                                                <i class="bx bx-cog me-2"></i>Quick Configuration Options
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <button type="button" class="btn btn-outline-primary w-100 mb-2" 
                                                            onclick="setDefaults('retail')">
                                                        <i class="bx bx-store me-1"></i>
                                                        Retail Store Setup
                                                    </button>
                                                    <small class="text-muted">Basic settings for retail inventory</small>
                                                </div>
                                                <div class="col-md-4">
                                                    <button type="button" class="btn btn-outline-success w-100 mb-2" 
                                                            onclick="setDefaults('warehouse')">
                                                        <i class="bx bx-box me-1"></i>
                                                        Warehouse Setup
                                                    </button>
                                                    <small class="text-muted">Advanced tracking for large inventory</small>
                                                </div>
                                                <div class="col-md-4">
                                                    <button type="button" class="btn btn-outline-warning w-100 mb-2" 
                                                            onclick="setDefaults('manufacturing')">
                                                        <i class="bx bx-wrench me-1"></i>
                                                        Manufacturing Setup
                                                    </button>
                                                    <small class="text-muted">Full tracking with batch and serial numbers</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between">
                                        <a href="{{ route('settings.index') }}" class="btn btn-secondary">
                                            <i class="bx bx-arrow-back me-1"></i>Back to Settings
                                        </a>
                                        <div>
                                            <button type="button" class="btn btn-outline-primary me-2" onclick="resetForm()">
                                                <i class="bx bx-refresh me-1"></i>Reset
                                            </button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bx bx-check me-1"></i>Save Inventory Settings
                                            </button>
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
function setDefaults(type) {
    switch(type) {
        case 'retail':
            document.getElementById('low_stock_threshold').value = 5;
            document.getElementById('auto_reorder_point').value = 2;
            document.getElementById('default_unit').value = 'pieces';
            document.getElementById('cost_method').value = 'fifo';
            document.getElementById('barcode_prefix').value = 'RTL';
            document.getElementById('enable_batch_tracking').checked = false;
            document.getElementById('enable_expiry_tracking').checked = true;
            document.getElementById('enable_serial_tracking').checked = false;
            document.getElementById('default_location').value = 'Store Floor';
            document.getElementById('default_invoice_due_days').value = 30;
            
            // Set default accounts for retail - Complete double-entry flow
            setAccountValue('default_inventory_account', 'Inventory'); // Asset - Inventory
            setAccountValue('default_sales_account', 'Sales Revenue'); // Revenue - Sales
            setAccountValue('default_cost_account', 'Cost of Goods Sold'); // Expense - COGS
            setAccountValue('default_opening_balance_account', 'Retained Earnings'); // Equity - Opening Balance
            setAccountValue('default_vat_account', 'VAT Payable'); // Liability - VAT
            setAccountValue('default_withholding_tax_account', 'Withholding Tax Payable'); // Liability - WHT
            setAccountValue('default_withholding_tax_expense_account', 'Withholding Tax Expense'); // Expense - WHT
            setAccountValue('default_purchase_payable_account', 'Accounts Payable'); // Liability - Payables
            setAccountValue('default_discount_account', 'Discount Expense'); // Expense - Discounts
            setAccountValue('default_discount_income_account', 'Discount Income'); // Revenue - Discounts
            setAccountValue('default_early_payment_discount_account', 'Early Payment Discount Expense'); // Expense - Early Payment Discounts
            setAccountValue('default_late_payment_fees_account', 'Late Payment Fees Income'); // Revenue - Late Payment Fees
            setAccountValue('default_receivable_account', 'Accounts Receivable'); // Asset - Receivables
            break;
            
        case 'warehouse':
            document.getElementById('low_stock_threshold').value = 20;
            document.getElementById('auto_reorder_point').value = 10;
            document.getElementById('default_unit').value = 'boxes';
            document.getElementById('cost_method').value = 'fifo';
            document.getElementById('barcode_prefix').value = 'WHS';
            document.getElementById('enable_batch_tracking').checked = true;
            document.getElementById('enable_expiry_tracking').checked = true;
            document.getElementById('enable_serial_tracking').checked = false;
            document.getElementById('default_location').value = 'Main Warehouse';
            document.getElementById('default_invoice_due_days').value = 30;
            
            // Set default accounts for warehouse - Complete double-entry flow
            setAccountValue('default_inventory_account', 'Inventory'); // Asset - Inventory
            setAccountValue('default_sales_account', 'Sales Revenue'); // Revenue - Sales
            setAccountValue('default_cost_account', 'Cost of Goods Sold'); // Expense - COGS
            setAccountValue('default_opening_balance_account', 'Retained Earnings'); // Equity - Opening Balance
            setAccountValue('default_vat_account', 'VAT Payable'); // Liability - VAT
            setAccountValue('default_withholding_tax_account', 'Withholding Tax Payable'); // Liability - WHT
            setAccountValue('default_withholding_tax_expense_account', 'Withholding Tax Expense'); // Expense - WHT
            setAccountValue('default_purchase_payable_account', 'Accounts Payable'); // Liability - Payables
            setAccountValue('default_discount_account', 'Discount Expense'); // Expense - Discounts
            setAccountValue('default_discount_income_account', 'Discount Income'); // Revenue - Discounts
            setAccountValue('default_early_payment_discount_account', 'Early Payment Discount Expense'); // Expense - Early Payment Discounts
            setAccountValue('default_late_payment_fees_account', 'Late Payment Fees Income'); // Revenue - Late Payment Fees
            setAccountValue('default_receivable_account', 'Accounts Receivable'); // Asset - Receivables
            break;
            
        case 'manufacturing':
            document.getElementById('low_stock_threshold').value = 50;
            document.getElementById('auto_reorder_point').value = 25;
            document.getElementById('default_unit').value = 'pieces';
            document.getElementById('cost_method').value = 'weighted_average';
            document.getElementById('barcode_prefix').value = 'MFG';
            document.getElementById('enable_batch_tracking').checked = true;
            document.getElementById('enable_expiry_tracking').checked = true;
            document.getElementById('enable_serial_tracking').checked = true;
            document.getElementById('default_location').value = 'Production Floor';
            document.getElementById('default_invoice_due_days').value = 30;
            
            // Set default accounts for manufacturing - Complete double-entry flow
            setAccountValue('default_inventory_account', 'Raw Materials'); // Asset - Raw Materials
            setAccountValue('default_sales_account', 'Sales Revenue'); // Revenue - Sales
            setAccountValue('default_cost_account', 'Cost of Goods Sold'); // Expense - COGS
            setAccountValue('default_opening_balance_account', 'Retained Earnings'); // Equity - Opening Balance
            setAccountValue('default_vat_account', 'VAT Payable'); // Liability - VAT
            setAccountValue('default_withholding_tax_account', 'Withholding Tax Payable'); // Liability - WHT
            setAccountValue('default_withholding_tax_expense_account', 'Withholding Tax Expense'); // Expense - WHT
            setAccountValue('default_purchase_payable_account', 'Accounts Payable'); // Liability - Payables
            setAccountValue('default_discount_account', 'Discount Expense'); // Expense - Discounts
            setAccountValue('default_discount_income_account', 'Discount Income'); // Revenue - Discounts
            setAccountValue('default_early_payment_discount_account', 'Early Payment Discount Expense'); // Expense - Early Payment Discounts
            setAccountValue('default_late_payment_fees_account', 'Late Payment Fees Income'); // Revenue - Late Payment Fees
            setAccountValue('default_receivable_account', 'Accounts Receivable'); // Asset - Receivables
            break;
    }
    
    // Show success message
    Swal.fire({
        icon: 'success',
        title: 'Settings Applied!',
        text: `${type.charAt(0).toUpperCase() + type.slice(1)} defaults have been applied with complete double-entry accounting flow.`,
        timer: 3000,
        showConfirmButton: false
    });
}

// Helper function to set account values by account name
function setAccountValue(selectId, accountName) {
    const select = document.getElementById(selectId);
    if (select) {
        // Find the option with the matching account name
        for (let option of select.options) {
            if (option.text.includes(accountName)) {
                select.value = option.value;
                break;
            }
        }
    }
}

// Toggle approval levels visibility
function toggleApprovalLevels() {
    const selectedLevels = parseInt(document.getElementById('approval_levels').value);
    for (let i = 1; i <= 5; i++) {
        const section = document.querySelector(`.approval-level-section[data-level="${i}"]`);
        if (section) {
            if (i <= selectedLevels) {
                section.classList.remove('d-none');
            } else {
                section.classList.add('d-none');
            }
        }
    }
}

// Toggle approvers type (role vs user)
function toggleApproversType(level) {
    const approversSelect = document.getElementById(`level${level}_approvers`);
    const typeSelect = document.querySelector(`select[name="level${level}_approval_type"]`);
    
    if (!approversSelect || !typeSelect) return;
    
    const selectedType = typeSelect.value;
    
    // Get currently selected values that match the new type
    const currentValues = [];
    approversSelect.querySelectorAll('option:checked').forEach(option => {
        if (option.getAttribute('data-type') === selectedType) {
            currentValues.push(option.value);
        }
    });
    
    // Deselect options that don't match the new type
    approversSelect.querySelectorAll('option').forEach(option => {
        if (!option.value) return;
        const optionType = option.getAttribute('data-type');
        if (optionType !== selectedType && option.selected) {
            option.selected = false;
        }
    });
    
    // Update data attribute
    approversSelect.setAttribute('data-approval-type', selectedType);
    
    // Destroy and reinitialize Select2 with matcher to filter options
    if ($(approversSelect).hasClass('select2-hidden-accessible')) {
        $(approversSelect).select2('destroy');
    }
    
    $(approversSelect).select2({
        theme: 'bootstrap-5',
        placeholder: 'Select approvers',
        allowClear: true,
        // Filter options based on data-type attribute
        matcher: function(params, data) {
            // If there's no search term, show all matching type
            if ($.trim(params.term) === '') {
                const option = approversSelect.querySelector(`option[value="${data.id}"]`);
                if (option && option.getAttribute('data-type') === selectedType) {
                    return data;
                }
                return null;
            }
            
            // If searching, only show matching type
            const option = approversSelect.querySelector(`option[value="${data.id}"]`);
            if (option && option.getAttribute('data-type') === selectedType) {
                // Normal Select2 search
                if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
                    return data;
                }
            }
            return null;
        }
    });
    
    // Restore selected values
    currentValues.forEach(value => {
        const option = approversSelect.querySelector(`option[value="${value}"]`);
        if (option && option.getAttribute('data-type') === selectedType) {
            option.selected = true;
        }
    });
    
    // Trigger change to update display
    $(approversSelect).trigger('change');
}

// Initialize Select2 for approval approvers on page load
$(document).ready(function() {
    $('.select2-multiple').each(function() {
        const select = this;
        const currentType = $(select).attr('data-approval-type') || 'role';
        
        $(select).select2({
            theme: 'bootstrap-5',
            placeholder: 'Select approvers',
            allowClear: true,
            // Filter options based on data-type attribute
            matcher: function(params, data) {
                // If there's no search term, show all matching type
                if ($.trim(params.term) === '') {
                    const option = select.querySelector(`option[value="${data.id}"]`);
                    if (option && option.getAttribute('data-type') === currentType) {
                        return data;
                    }
                    return null;
                }
                
                // If searching, only show matching type
                const option = select.querySelector(`option[value="${data.id}"]`);
                if (option && option.getAttribute('data-type') === currentType) {
                    // Normal Select2 search
                    if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
                        return data;
                    }
                }
                return null;
            }
        });
    });
});

function resetForm() {
    Swal.fire({
        title: 'Reset Form?',
        text: 'This will reset all fields to their default values.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, reset it!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('low_stock_threshold').value = 10;
            document.getElementById('auto_reorder_point').value = 5;
            document.getElementById('default_unit').value = 'pieces';
            document.getElementById('cost_method').value = 'fifo';
            document.getElementById('barcode_prefix').value = 'INV';
            document.getElementById('enable_batch_tracking').checked = true;
            document.getElementById('enable_expiry_tracking').checked = true;
            document.getElementById('enable_serial_tracking').checked = false;
            document.getElementById('default_location').value = 'Main Warehouse';
            document.getElementById('default_invoice_due_days').value = 30;
            
            // Reset to default accounts - Complete double-entry flow
            setAccountValue('default_inventory_account', 'Inventory');
            setAccountValue('default_sales_account', 'Sales Revenue');
            setAccountValue('default_cost_account', 'Cost of Goods Sold');
            setAccountValue('default_opening_balance_account', 'Retained Earnings');
            setAccountValue('default_vat_account', 'VAT Payable');
            setAccountValue('default_withholding_tax_account', 'Withholding Tax Payable');
            setAccountValue('default_withholding_tax_expense_account', 'Withholding Tax Expense');
            setAccountValue('default_purchase_payable_account', 'Accounts Payable');
            setAccountValue('default_discount_account', 'Discount Expense');
            setAccountValue('default_discount_income_account', 'Discount Income');
            setAccountValue('default_early_payment_discount_account', 'Early Payment Discount Expense');
            setAccountValue('default_late_payment_fees_account', 'Late Payment Fees Income');
            setAccountValue('default_receivable_account', 'Accounts Receivable');
            
            Swal.fire('Reset!', 'Form has been reset to default values with complete double-entry accounting flow.', 'success');
        }
    });
}

// Disable cost method field if it's already been set
document.addEventListener('DOMContentLoaded', function() {
    const costMethodField = document.getElementById('cost_method');
    const currentCostMethod = '{{ $currentSettings['cost_method'] }}';
    
    // Check if cost method has been set (not the default 'fifo')
    if (currentCostMethod && currentCostMethod !== 'fifo') {
        costMethodField.disabled = true;
        costMethodField.style.backgroundColor = '#f8f9fa';
        costMethodField.style.cursor = 'not-allowed';
        
        // Add a note below the field
        const note = document.createElement('small');
        note.className = 'text-danger d-block mt-1';
        note.innerHTML = '<i class="bx bx-info-circle me-1"></i>Cost method cannot be changed once set. Current method: <strong>' + currentCostMethod.toUpperCase() + '</strong>';
        costMethodField.parentNode.appendChild(note);
        
        // Add a hidden input to preserve the value
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'cost_method';
        hiddenInput.value = currentCostMethod;
        costMethodField.parentNode.appendChild(hiddenInput);
    }
});
</script>
@endpush

@push('styles')
<style>
.card-header {
    font-weight: 600;
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.btn-outline-primary:hover,
.btn-outline-success:hover,
.btn-outline-warning:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.card {
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.alert {
    border-radius: 10px;
}

.form-control:focus,
.form-select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>
@endpush
