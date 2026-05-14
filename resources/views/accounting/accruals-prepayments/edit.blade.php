@extends('layouts.main')

@section('title', 'Edit Accrual Schedule')

@push('styles')
<style>
    .form-section {
        border-left: 3px solid #6f42c1;
        padding-left: 1rem;
        margin-bottom: 2rem;
    }
    
    .form-section-title {
        font-weight: 600;
        color: #6f42c1;
        margin-bottom: 1rem;
    }
    
    .info-box {
        background-color: #f8f9fa;
        border-left: 3px solid #ffc107;
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
            ['label' => 'Accruals & Prepayments', 'url' => route('accounting.accruals-prepayments.index'), 'icon' => 'bx bx-time-five'],
            ['label' => $schedule->schedule_number, 'url' => route('accounting.accruals-prepayments.show', $schedule->encoded_id), 'icon' => 'bx bx-show'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        
        <h6 class="mb-0 text-uppercase">EDIT ACCRUAL SCHEDULE</h6>
        <hr />

        <div class="card radius-10">
            <div class="card-header" style="background: linear-gradient(45deg, #6f42c1, #5a32a3); color: white;">
                <h5 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Schedule: {{ $schedule->schedule_number }}</h5>
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
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="info-box">
                    <strong><i class="bx bx-info-circle me-1"></i>Note:</strong> Editing this schedule will cancel all future (unposted) journals and regenerate them based on the new parameters. Posted journals will remain unchanged.
                </div>

                <form id="schedule-form" action="{{ route('accounting.accruals-prepayments.update', $schedule->encoded_id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Schedule Information (Read-only) -->
                    <div class="form-section">
                        <h6 class="form-section-title">
                            <i class="bx bx-info-circle me-2"></i>Schedule Information (Cannot be changed)
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Schedule Number</label>
                                    <input type="text" class="form-control" value="{{ $schedule->schedule_number }}" disabled>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <input type="text" class="form-control" value="{{ $schedule->category_name }}" disabled>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- General Details -->
                    <div class="form-section">
                        <h6 class="form-section-title">
                            <i class="bx bx-info-circle me-2"></i>General Details
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('start_date') is-invalid @enderror" 
                                           id="start_date" name="start_date" 
                                           value="{{ old('start_date', $schedule->start_date->format('Y-m-d')) }}" required>
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('end_date') is-invalid @enderror" 
                                           id="end_date" name="end_date" 
                                           value="{{ old('end_date', $schedule->end_date->format('Y-m-d')) }}" required>
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="total_amount" class="form-label">Total Amount <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0.01" 
                                           class="form-control @error('total_amount') is-invalid @enderror" 
                                           id="total_amount" name="total_amount" 
                                           value="{{ old('total_amount', $schedule->total_amount) }}" required>
                                    @error('total_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="3" required>{{ old('description', $schedule->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Accounts -->
                    <div class="form-section">
                        <h6 class="form-section-title">
                            <i class="bx bx-book me-2"></i>Chart of Accounts
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="expense_income_account_id" class="form-label">P&L Account <span class="text-danger">*</span></label>
                                    <select class="form-select @error('expense_income_account_id') is-invalid @enderror" 
                                            id="expense_income_account_id" name="expense_income_account_id" required>
                                        <option value="">Select Account</option>
                                        @foreach($expenseIncomeAccounts as $account)
                                        <option value="{{ $account->id }}" 
                                                {{ old('expense_income_account_id', $schedule->expense_income_account_id) == $account->id ? 'selected' : '' }}>
                                            {{ $account->account_code }} - {{ $account->account_name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('expense_income_account_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="balance_sheet_account_id" class="form-label">Balance Sheet Account <span class="text-danger">*</span></label>
                                    <select class="form-select @error('balance_sheet_account_id') is-invalid @enderror" 
                                            id="balance_sheet_account_id" name="balance_sheet_account_id" required>
                                        <option value="">Select Account</option>
                                        @foreach($balanceSheetAccounts as $account)
                                        <option value="{{ $account->id }}" 
                                                {{ old('balance_sheet_account_id', $schedule->balance_sheet_account_id) == $account->id ? 'selected' : '' }}>
                                            {{ $account->account_code }} - {{ $account->account_name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('balance_sheet_account_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Frequency & Optional Fields -->
                    <div class="form-section">
                        <h6 class="form-section-title">
                            <i class="bx bx-calendar me-2"></i>Frequency & Optional Information
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="frequency" class="form-label">Frequency <span class="text-danger">*</span></label>
                                    <select class="form-select @error('frequency') is-invalid @enderror" 
                                            id="frequency" name="frequency" required>
                                        <option value="monthly" {{ old('frequency', $schedule->frequency) == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                        <option value="quarterly" {{ old('frequency', $schedule->frequency) == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                        <option value="custom" {{ old('frequency', $schedule->frequency) == 'custom' ? 'selected' : '' }}>Custom</option>
                                    </select>
                                    @error('frequency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6" id="custom-periods-container" style="display: {{ old('frequency', $schedule->frequency) == 'custom' ? 'block' : 'none' }};">
                                <div class="mb-3">
                                    <label for="custom_periods" class="form-label">Custom Periods (Months)</label>
                                    <input type="number" min="1" class="form-control" 
                                           id="custom_periods" name="custom_periods" 
                                           value="{{ old('custom_periods', $schedule->custom_periods) }}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="branch_id" class="form-label">Branch</label>
                                    <select class="form-select" id="branch_id" name="branch_id">
                                        <option value="">All Branches</option>
                                        @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" 
                                                {{ old('branch_id', $schedule->branch_id) == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="vendor_id" class="form-label">Vendor (Optional)</label>
                                    <select class="form-select" id="vendor_id" name="vendor_id">
                                        <option value="">Select Vendor</option>
                                        @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" 
                                                {{ old('vendor_id', $schedule->vendor_id) == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_id" class="form-label">Customer (Optional)</label>
                                    <select class="form-select" id="customer_id" name="customer_id">
                                        <option value="">Select Customer</option>
                                        @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" 
                                                {{ old('customer_id', $schedule->customer_id) == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="2">{{ old('notes', $schedule->notes) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('accounting.accruals-prepayments.show', $schedule->encoded_id) }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-purple">
                            <i class="bx bx-save me-1"></i>Update Schedule
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
// Frequency change handler
document.getElementById('frequency').addEventListener('change', function() {
    const customContainer = document.getElementById('custom-periods-container');
    if (this.value === 'custom') {
        customContainer.style.display = 'block';
    } else {
        customContainer.style.display = 'none';
    }
});
</script>
@endpush

