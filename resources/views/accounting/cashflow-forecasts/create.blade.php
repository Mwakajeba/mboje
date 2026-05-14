@extends('layouts.main')

@section('title', 'Create Cashflow Forecast')

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
    
    .scenario-card {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: 0.5rem;
    }
    
    .scenario-card:hover {
        border-color: #0d6efd;
        background-color: #f8f9ff;
    }
    
    .scenario-card.selected {
        border-color: #0d6efd;
        background-color: #e7f1ff;
    }
    
    .scenario-card input[type="radio"] {
        margin-right: 0.5rem;
    }
    
    .timeline-card {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: 0.5rem;
        text-align: center;
    }
    
    .timeline-card:hover {
        border-color: #0d6efd;
        background-color: #f8f9ff;
    }
    
    .timeline-card.selected {
        border-color: #0d6efd;
        background-color: #e7f1ff;
    }
    
    .timeline-card input[type="radio"] {
        margin-right: 0.5rem;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Management', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Cashflow Forecasting', 'url' => route('accounting.cashflow-forecasts.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        
        <h6 class="mb-0 text-uppercase">CREATE CASHFLOW FORECAST</h6>
        <hr />

        <div class="card radius-10">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-trending-up me-2"></i>New Cashflow Forecast</h5>
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

                <form id="cashflow-forecast-form" action="{{ route('accounting.cashflow-forecasts.store') }}" method="POST">
                    @csrf

                    <!-- Basic Information Section -->
                    <div class="form-section">
                        <h6 class="form-section-title">
                            <i class="bx bx-info-circle me-2"></i>Basic Information
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="forecast_name" class="form-label">Forecast Name <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('forecast_name') is-invalid @enderror" 
                                           id="forecast_name" 
                                           name="forecast_name" 
                                           value="{{ old('forecast_name') }}" 
                                           placeholder="e.g., Q1 2026 Forecast, Annual Cashflow Projection"
                                           required>
                                    @error('forecast_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">A descriptive name for this cashflow forecast</small>
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
                                    <small class="text-muted">Select branch if this forecast is branch-specific</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Scenario Selection Section -->
                    <div class="form-section">
                        <h6 class="form-section-title">
                            <i class="bx bx-bar-chart-alt-2 me-2"></i>Scenario Selection <span class="text-danger">*</span>
                        </h6>
                        <div class="row">
                            <div class="col-md-4">
                                <label class="scenario-card {{ old('scenario', 'base_case') == 'best_case' ? 'selected' : '' }}" onclick="selectScenario('best_case')">
                                    <input type="radio" name="scenario" value="best_case" {{ old('scenario', 'base_case') == 'best_case' ? 'checked' : '' }} required>
                                    <strong class="d-block text-success">
                                        <i class="bx bx-trending-up me-1"></i>Best Case
                                    </strong>
                                    <small class="text-muted">Optimistic view: Early collections, delayed payments</small>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="scenario-card {{ old('scenario', 'base_case') == 'base_case' ? 'selected' : '' }}" onclick="selectScenario('base_case')">
                                    <input type="radio" name="scenario" value="base_case" {{ old('scenario', 'base_case') == 'base_case' ? 'checked' : '' }} required>
                                    <strong class="d-block text-info">
                                        <i class="bx bx-bar-chart me-1"></i>Base Case
                                    </strong>
                                    <small class="text-muted">Realistic view: Normal business conditions</small>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="scenario-card {{ old('scenario', 'base_case') == 'worst_case' ? 'selected' : '' }}" onclick="selectScenario('worst_case')">
                                    <input type="radio" name="scenario" value="worst_case" {{ old('scenario', 'base_case') == 'worst_case' ? 'checked' : '' }} required>
                                    <strong class="d-block text-warning">
                                        <i class="bx bx-trending-down me-1"></i>Worst Case
                                    </strong>
                                    <small class="text-muted">Pessimistic view: Delayed collections, early payments</small>
                                </label>
                            </div>
                        </div>
                        @error('scenario')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Forecast Period Section -->
                    <!-- Hidden timeline field with default value -->
                    <input type="hidden" name="timeline" value="{{ old('timeline', 'monthly') }}">
                    <div class="form-section">
                        <h6 class="form-section-title">
                            <i class="bx bx-time me-2"></i>Forecast Period <span class="text-danger">*</span>
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                    <input type="date" 
                                           class="form-control @error('start_date') is-invalid @enderror" 
                                           id="start_date" 
                                           name="start_date" 
                                           value="{{ old('start_date', date('Y-m-d')) }}" 
                                           required>
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">The first date of the forecast period</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                    <input type="date" 
                                           class="form-control @error('end_date') is-invalid @enderror" 
                                           id="end_date" 
                                           name="end_date" 
                                           value="{{ old('end_date') }}" 
                                           required>
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">The last date of the forecast period (auto-calculated based on timeline)</small>
                                </div>
                            </div>
                        </div>

                        <div class="info-box">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Note:</strong> The forecast period should be realistic. Longer periods may have less accurate predictions.
                        </div>
                    </div>

                    <!-- Starting Balance Section -->
                    <div class="form-section">
                        <h6 class="form-section-title">
                            <i class="bx bx-dollar me-2"></i>Starting Cash Balance <span class="text-danger">*</span>
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="starting_cash_balance" class="form-label">Starting Balance <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">TZS</span>
                                        <input type="number" 
                                               class="form-control @error('starting_cash_balance') is-invalid @enderror" 
                                               id="starting_cash_balance" 
                                               name="starting_cash_balance" 
                                               value="{{ old('starting_cash_balance', $calculatedBalance ?? 0) }}" 
                                               step="0.01" 
                                               min="0" 
                                               required>
                                        @error('starting_cash_balance')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <button type="button" class="btn btn-outline-primary" id="calculateBalanceBtn" title="Auto-calculate from bank accounts">
                                            <i class="bx bx-calculator"></i> Auto-Calculate
                                        </button>
                                    </div>
                                    <small class="text-muted">
                                        <i class="bx bx-info-circle"></i> 
                                        Current cash balance at the start of the forecast period. Click "Auto-Calculate" to pull from bank accounts.
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Balance Calculation Method</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="balance_method" id="balance_auto" value="auto" checked>
                                        <label class="form-check-label" for="balance_auto">
                                            <strong>Auto-Calculate</strong> - Pull from bank accounts and mobile money (petty cash excluded)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="balance_method" id="balance_manual" value="manual">
                                        <label class="form-check-label" for="balance_manual">
                                            <strong>Manual Entry</strong> - Enter balance manually
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Balance Breakdown -->
                        <div id="balanceBreakdown" class="card border-info mb-3" style="display: none;">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="bx bx-calculator me-2"></i>Calculated Balance Breakdown</h6>
                            </div>
                            <div class="card-body">
                                <div id="breakdownContent">
                                    @if(isset($balanceBreakdown))
                                        @if(count($balanceBreakdown['bank_accounts']) > 0)
                                        <div class="mb-3">
                                            <strong class="text-primary"><i class="bx bx-building me-1"></i>Bank Accounts:</strong>
                                            <ul class="mb-0 mt-2">
                                                @foreach($balanceBreakdown['bank_accounts'] as $bank)
                                                <li>{{ $bank['name'] }} ({{ $bank['account_number'] ?? 'N/A' }}): 
                                                    <span class="fw-bold">{{ number_format($bank['balance'], 2) }} TZS</span>
                                                </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                        @endif
                                        
                                        
                                        <div class="alert alert-success mb-0">
                                            <strong>Total Calculated Balance:</strong> 
                                            <span class="fs-5">{{ number_format($balanceBreakdown['total'], 2) }} TZS</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="info-box">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>How it works:</strong> The system automatically calculates your opening cash balance from:
                            <ul class="mb-0 mt-2">
                                <li><strong>Bank Accounts:</strong> Current balances from all bank accounts (via GL transactions)</li>
                                <li><strong>Mobile Money:</strong> (Coming soon) Mobile money balances</li>
                            </ul>
                            <strong>Note:</strong> Petty cash is excluded from the opening balance calculation. You can override this value manually if needed. The balance is calculated as of the forecast start date.
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
                                              placeholder="Enter any additional notes, assumptions, or special considerations for this forecast...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Optional notes, assumptions, or special considerations</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Sources Section -->
                    <div class="form-section">
                        <h6 class="form-section-title">
                            <i class="bx bx-data me-2"></i>Data Sources Included
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-success mb-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="bx bx-trending-up me-2"></i>Cash Inflows</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="mb-0">
                                            <li><i class="bx bx-check-circle text-success me-2"></i><strong>Accounts Receivable</strong> - Unpaid customer invoices with AI probability scoring</li>
                                            <li><i class="bx bx-check-circle text-success me-2"></i><strong>Sales Orders</strong> - Confirmed orders with expected delivery dates</li>
                                            <li><i class="bx bx-check-circle text-success me-2"></i><strong>Loan Disbursements</strong> - Expected loan funds to be received</li>
                                            <li><i class="bx bx-check-circle text-success me-2"></i><strong>Recurring Incomes</strong> - Subscriptions, contracts, and recurring revenue</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-danger mb-3">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0"><i class="bx bx-trending-down me-2"></i>Cash Outflows</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="mb-0">
                                            <li><i class="bx bx-check-circle text-danger me-2"></i><strong>Accounts Payable</strong> - Unpaid supplier invoices with due dates</li>
                                            <li><i class="bx bx-check-circle text-danger me-2"></i><strong>Purchase Orders</strong> - Approved POs with payment terms</li>
                                            <li><i class="bx bx-check-circle text-danger me-2"></i><strong>Payroll</strong> - Monthly salary payments (estimated from history)</li>
                                            <li><i class="bx bx-check-circle text-danger me-2"></i><strong>Tax Obligations</strong> - VAT (20th), PAYE/SDL/WHT (7th), Corporate Tax (quarterly)</li>
                                            <li><i class="bx bx-check-circle text-danger me-2"></i><strong>Loan Payments</strong> - Principal + interest from loan schedules</li>
                                            <li><i class="bx bx-check-circle text-danger me-2"></i><strong>Recurring Expenses</strong> - Rent, utilities, subscriptions, standing orders</li>
                                            <li><i class="bx bx-check-circle text-danger me-2"></i><strong>Payment Vouchers</strong> - Approved but unpaid payment vouchers</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Forecast Generation Info -->
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>
                        <strong>What happens next?</strong> After clicking "Generate Forecast", the system will automatically:
                        <ul class="mb-0 mt-2">
                            <li><strong>Pull Real Transactional Data</strong> from all configured sources (AR, AP, Orders, Loans, Payroll, Taxes, Recurring Expenses)</li>
                            <li><strong>Apply AI Probability Scoring</strong> based on customer payment history and invoice age</li>
                            <li><strong>Apply Scenario Adjustments</strong> to dates and probabilities (Best Case: early collections, delayed payments | Worst Case: delayed collections, early payments)</li>
                            <li><strong>Generate Forecast Items</strong> for each date in the period based on timeline (Daily/Weekly/Monthly/Quarterly)</li>
                            <li><strong>Calculate Running Cash Balances</strong> day by day</li>
                            <li><strong>Detect Anomalies</strong> and generate AI insights</li>
                        </ul>
                    </div>

                    <!-- Form Actions -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="{{ route('accounting.cashflow-forecasts.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bx bx-trending-up me-1"></i>Generate Forecast
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
// Global flag to prevent validation when auto-updating end date
var isAutoUpdating = false;

// Scenario selection (global function for onclick handlers)
window.selectScenario = function(value) {
    if (typeof jQuery !== 'undefined') {
        jQuery('.scenario-card').removeClass('selected');
        jQuery('.scenario-card:has(input[value="' + value + '"])').addClass('selected');
        jQuery('input[name="scenario"][value="' + value + '"]').prop('checked', true);
    }
};


$(document).ready(function() {
    // Initialize Select2 for dropdowns
    if (typeof $().select2 !== 'undefined') {
        $('.select2-single').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }

    // Ensure scenario cards are properly selected on load
    const selectedScenario = $('input[name="scenario"]:checked').val();
    if (selectedScenario) {
        selectScenario(selectedScenario);
    }

    // Form validation - only check critical issues
    $('#cashflow-forecast-form').on('submit', function(e) {
        let isValid = true;
        let errorMessages = [];

        // Check forecast name
        const forecastName = $('#forecast_name').val() ? $('#forecast_name').val().trim() : '';
        if (!forecastName) {
            $('#forecast_name').addClass('is-invalid');
            isValid = false;
            errorMessages.push('Forecast name is required');
        } else {
            $('#forecast_name').removeClass('is-invalid');
        }

        // Check scenario radio button
        const selectedScenario = $('input[name="scenario"]:checked').val();
        if (!selectedScenario) {
            isValid = false;
            errorMessages.push('Please select a scenario (Best Case, Base Case, or Worst Case)');
        }


        // Check start date
        const startDateVal = $('#start_date').val();
        if (!startDateVal) {
            $('#start_date').addClass('is-invalid');
            isValid = false;
            errorMessages.push('Start date is required');
        } else {
            $('#start_date').removeClass('is-invalid');
        }

        // Check end date
        const endDateVal = $('#end_date').val();
        if (!endDateVal) {
            $('#end_date').addClass('is-invalid');
            isValid = false;
            errorMessages.push('End date is required');
        } else {
            $('#end_date').removeClass('is-invalid');
        }

        // Validate dates relationship only if both dates exist
        if (startDateVal && endDateVal) {
            const startDate = new Date(startDateVal);
            const endDate = new Date(endDateVal);
            
            // Set time to midnight for accurate comparison
            startDate.setHours(0, 0, 0, 0);
            endDate.setHours(0, 0, 0, 0);
            
            if (endDate <= startDate) {
                $('#end_date').addClass('is-invalid');
                isValid = false;
                errorMessages.push('End date must be after start date');
            } else {
                $('#end_date').removeClass('is-invalid');
            }
        }

        // Check starting balance
        const startingBalance = $('#starting_cash_balance').val();
        if (!startingBalance || startingBalance === '' || isNaN(parseFloat(startingBalance)) || parseFloat(startingBalance) < 0) {
            $('#starting_cash_balance').addClass('is-invalid');
            isValid = false;
            errorMessages.push('Starting cash balance is required and must be 0 or greater');
        } else {
            $('#starting_cash_balance').removeClass('is-invalid');
        }

        if (!isValid) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                html: '<div style="text-align: left;"><strong>Please fix the following:</strong><ul style="margin-top: 10px;"><li>' + errorMessages.join('</li><li>') + '</li></ul></div>'
            });
            return false;
        }
    });

    // Remove invalid class on input
    $('input, select, textarea').on('input change', function() {
        if ($(this).val()) {
            $(this).removeClass('is-invalid');
        }
    });

    // Handle start date changes
    $('#start_date').on('change', function() {
        // Recalculate balance if auto-calculate is selected
        if ($('input[name="balance_method"]:checked').val() === 'auto') {
            calculateOpeningBalance();
        }
        
        // Validate end date is after start date
        const startDateVal = $('#start_date').val();
        const endDateVal = $('#end_date').val();
        
        if (startDateVal && endDateVal) {
            const startDate = new Date(startDateVal);
            const endDate = new Date(endDateVal);
            
            if (endDate <= startDate) {
                $('#end_date').addClass('is-invalid');
                if (!$('#end_date').next('.invalid-feedback').length) {
                    $('#end_date').after('<div class="invalid-feedback">End date must be after start date</div>');
                }
            } else {
                $('#end_date').removeClass('is-invalid');
                $('#end_date').next('.invalid-feedback').remove();
            }
        }
    });

    // Balance calculation method toggle
    $('input[name="balance_method"]').on('change', function() {
        if ($(this).val() === 'auto') {
            calculateOpeningBalance();
            $('#starting_cash_balance').prop('readonly', true).addClass('bg-light');
        } else {
            $('#starting_cash_balance').prop('readonly', false).removeClass('bg-light');
            $('#balanceBreakdown').hide();
        }
    });

    // Auto-calculate balance button
    $('#calculateBalanceBtn').on('click', function() {
        calculateOpeningBalance();
    });

    // Calculate opening balance function
    function calculateOpeningBalance() {
        const startDate = $('#start_date').val();
        const branchId = $('#branch_id').val();
        
        if (!startDate) {
            Swal.fire({
                icon: 'warning',
                title: 'Start Date Required',
                text: 'Please select a start date first.'
            });
            return;
        }
        
        // Show loading
        $('#calculateBalanceBtn').prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i> Calculating...');
        
        $.ajax({
            url: '{{ route("accounting.cashflow-forecasts.calculate-balance") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                start_date: startDate,
                branch_id: branchId
            },
            success: function(response) {
                if (response.success) {
                    $('#starting_cash_balance').val(response.balance);
                    
                    // Update breakdown display
                    let breakdownHtml = '';
                    
                    if (response.breakdown.bank_accounts && response.breakdown.bank_accounts.length > 0) {
                        breakdownHtml += '<div class="mb-3"><strong class="text-primary"><i class="bx bx-building me-1"></i>Bank Accounts:</strong><ul class="mb-0 mt-2">';
                        response.breakdown.bank_accounts.forEach(function(bank) {
                            breakdownHtml += '<li>' + bank.name + ' (' + (bank.account_number || 'N/A') + '): <span class="fw-bold">' + 
                                parseFloat(bank.balance).toLocaleString('en-TZ', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' TZS</span></li>';
                        });
                        breakdownHtml += '</ul></div>';
                    }
                    
                    
                    breakdownHtml += '<div class="alert alert-success mb-0"><strong>Total Calculated Balance:</strong> <span class="fs-5">' + 
                        parseFloat(response.balance).toLocaleString('en-TZ', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' TZS</span></div>';
                    
                    $('#breakdownContent').html(breakdownHtml);
                    $('#balanceBreakdown').show();
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Calculation Failed',
                    text: xhr.responseJSON?.message || 'Failed to calculate opening balance. Please try again.'
                });
            },
            complete: function() {
                $('#calculateBalanceBtn').prop('disabled', false).html('<i class="bx bx-calculator"></i> Auto-Calculate');
            }
        });
    }

    // Auto-calculate on page load if balance method is auto
    $(document).ready(function() {
        if ($('input[name="balance_method"]:checked').val() === 'auto') {
            $('#starting_cash_balance').prop('readonly', true).addClass('bg-light');
            @if(isset($balanceBreakdown))
                $('#balanceBreakdown').show();
            @endif
        }
        
        // Auto-calculate when branch changes
        $('#branch_id').on('change', function() {
            if ($('input[name="balance_method"]:checked').val() === 'auto') {
                calculateOpeningBalance();
            }
        });
    });
    
    // Validate end date is after start date (only when manually changed, not auto-calculated)
    $('#end_date').on('change', function() {
        if (isAutoUpdating) {
            isAutoUpdating = false;
            return;
        }
        
        const startDateVal = $('#start_date').val();
        const endDateVal = $('#end_date').val();
        
        if (startDateVal && endDateVal) {
            const startDate = new Date(startDateVal);
            const endDate = new Date(endDateVal);
            
            if (endDate <= startDate) {
                $('#end_date').addClass('is-invalid');
                if (!$('#end_date').next('.invalid-feedback').length) {
                    $('#end_date').after('<div class="invalid-feedback">End date must be after start date</div>');
                }
            } else {
                $('#end_date').removeClass('is-invalid');
                $('#end_date').next('.invalid-feedback').remove();
            }
        }
    });
});
</script>
@endpush
@endsection
