@extends('layouts.main')

@section('title', 'Create Inter-Account Transfer')

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
    
    .account-type-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 0.25rem;
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .badge-bank {
        background-color: #0d6efd;
        color: white;
    }
    
    .badge-cash {
        background-color: #198754;
        color: white;
    }
    
    .badge-petty-cash {
        background-color: #ffc107;
        color: #000;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Management', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Inter-Account Transfers', 'url' => route('accounting.account-transfers.index'), 'icon' => 'bx bx-transfer'],
            ['label' => 'Create Transfer', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        
        <h6 class="mb-0 text-uppercase">CREATE INTER-ACCOUNT TRANSFER</h6>
        <hr />

        <div class="card radius-10">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-transfer me-2"></i>New Inter-Account Transfer</h5>
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

                <form id="transfer-form" action="{{ route('accounting.account-transfers.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- Transfer Details Section -->
                    <div class="form-section">
                        <h6 class="form-section-title">
                            <i class="bx bx-info-circle me-2"></i>Transfer Details
                        </h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="transfer_date" class="form-label">Transfer Date <span class="text-danger">*</span></label>
                                    <input type="date" 
                                           class="form-control @error('transfer_date') is-invalid @enderror" 
                                           id="transfer_date" 
                                           name="transfer_date" 
                                           value="{{ old('transfer_date', date('Y-m-d')) }}" 
                                           required>
                                    @error('transfer_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="reference_number" class="form-label">Reference Number</label>
                                    <input type="text" 
                                           class="form-control @error('reference_number') is-invalid @enderror" 
                                           id="reference_number" 
                                           name="reference_number" 
                                           value="{{ old('reference_number') }}" 
                                           placeholder="e.g., EFT-001, CHQ-123">
                                    @error('reference_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Bank reference, cheque number, or transaction ID</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="attachment" class="form-label">Attachment</label>
                                    <input type="file" 
                                           class="form-control @error('attachment') is-invalid @enderror" 
                                           id="attachment" 
                                           name="attachment" 
                                           accept=".pdf,.jpg,.jpeg,.png">
                                    @error('attachment')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Upload EFT slip, cheque copy, or receipt (PDF, JPG, PNG)</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" 
                                              name="description" 
                                              rows="3" 
                                              placeholder="Enter transfer description or purpose"
                                              required>{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Provide a clear description of the transfer purpose</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Selection Section -->
                    <div class="form-section">
                        <h6 class="form-section-title">
                            <i class="bx bx-wallet me-2"></i>Account Selection
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-primary mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bx bx-arrow-from-left me-2"></i>From Account</h6>
                                    </div>
                                    <div class="card-body">
                                        <input type="hidden" name="from_account_type" value="bank">
                                        <div class="mb-3">
                                            <label for="from_account_id" class="form-label">Select Bank Account <span class="text-danger">*</span></label>
                                            <select class="form-select select2-single @error('from_account_id') is-invalid @enderror" 
                                                    id="from_account_id" 
                                                    name="from_account_id" 
                                                    required>
                                                <option value="">Select Bank Account</option>
                                                @foreach($bankAccounts as $account)
                                                    <option value="{{ $account->id }}" {{ old('from_account_id') == $account->id ? 'selected' : '' }}>
                                                        {{ $account->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('from_account_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div id="from-account-balance" class="mt-2" style="display: none;">
                                                <small class="text-muted">
                                                    <i class="bx bx-info-circle"></i> 
                                                    Current Balance: <strong id="from-balance-amount" class="text-primary">TZS 0.00</strong>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card border-success mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="bx bx-arrow-to-right me-2"></i>To Account</h6>
                                    </div>
                                    <div class="card-body">
                                        <input type="hidden" name="to_account_type" value="bank">
                                        <div class="mb-3">
                                            <label for="to_account_id" class="form-label">Select Bank Account <span class="text-danger">*</span></label>
                                            <select class="form-select select2-single @error('to_account_id') is-invalid @enderror" 
                                                    id="to_account_id" 
                                                    name="to_account_id" 
                                                    required>
                                                <option value="">Select Bank Account</option>
                                                @foreach($bankAccounts as $account)
                                                    <option value="{{ $account->id }}" {{ old('to_account_id') == $account->id ? 'selected' : '' }}>
                                                        {{ $account->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('to_account_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Amount Section -->
                    <div class="form-section">
                        <h6 class="form-section-title">
                            <i class="bx bx-dollar me-2"></i>Transfer Amount
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Transfer Amount <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">TZS</span>
                                        <input type="number" 
                                               class="form-control @error('amount') is-invalid @enderror" 
                                               id="amount" 
                                               name="amount" 
                                               value="{{ old('amount') }}" 
                                               step="0.01" 
                                               min="0.01" 
                                               required>
                                        @error('amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <small class="text-muted">Amount to transfer between bank accounts</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charges Section -->
                    <div class="form-section">
                        <h6 class="form-section-title">
                            <i class="bx bx-receipt me-2"></i>Bank Charges (Optional)
                        </h6>
                        <div class="info-box">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Note:</strong> If bank charges apply to this transfer, specify the amount and the expense account to post the charges.
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="charges" class="form-label">Charges Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">TZS</span>
                                        <input type="number" 
                                               class="form-control @error('charges') is-invalid @enderror" 
                                               id="charges" 
                                               name="charges" 
                                               value="{{ old('charges', 0) }}" 
                                               step="0.01" 
                                               min="0">
                                        @error('charges')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <small class="text-muted">Bank charges, transfer fees, etc.</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="charges_account_id" class="form-label">Charges Account</label>
                                    <select class="form-select select2-single @error('charges_account_id') is-invalid @enderror" 
                                            id="charges_account_id" 
                                            name="charges_account_id">
                                        <option value="">Select Expense Account</option>
                                        @foreach($chargesAccounts as $account)
                                            <option value="{{ $account->id }}" {{ old('charges_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('charges_account_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Expense account to post bank charges</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('accounting.account-transfers.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-x me-1"></i>Cancel
                                </a>
                                <button type="submit" name="action" value="draft" class="btn btn-secondary">
                                    <i class="bx bx-save me-1"></i>Save as Draft
                                </button>
                                <button type="submit" name="action" value="submit" class="btn btn-primary">
                                    <i class="bx bx-send me-1"></i>Submit for Approval
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
    // Initialize Select2
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });


    // Load bank account balance when "From Account" is selected
    $('#from_account_id').on('change', function() {
        const accountId = $(this).val();
        const balanceDiv = $('#from-account-balance');
        const balanceAmount = $('#from-balance-amount');
        
        if (accountId) {
            // Fetch balance via AJAX
            $.ajax({
                url: '{{ route("accounting.api.bank-accounts.balance", ":id") }}'.replace(':id', accountId),
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success) {
                        const balance = parseFloat(response.balance) || 0;
                        balanceAmount.text('TZS ' + balance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        balanceDiv.show();
                        
                        // Check if current amount exceeds balance
                        checkBalance();
                    } else {
                        balanceDiv.hide();
                    }
                },
                error: function() {
                    balanceDiv.hide();
                }
            });
        } else {
            balanceDiv.hide();
        }
    });

    // Check if transfer amount exceeds balance
    function checkBalance() {
        const accountId = $('#from_account_id').val();
        const amount = parseFloat($('#amount').val()) || 0;
        
        if (accountId && amount > 0) {
            $.ajax({
                url: '{{ route("accounting.api.bank-accounts.balance", ":id") }}'.replace(':id', accountId),
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success) {
                        const balance = parseFloat(response.balance) || 0;
                        
                        if (amount > balance) {
                            $('#amount').addClass('is-invalid');
                            const errorDiv = $('#amount').siblings('.balance-error');
                            if (errorDiv.length === 0) {
                                $('#amount').after('<div class="balance-error text-danger small mt-1"><i class="bx bx-error-circle"></i> Transfer amount exceeds available balance (TZS ' + balance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ')</div>');
                            } else {
                                errorDiv.html('<i class="bx bx-error-circle"></i> Transfer amount exceeds available balance (TZS ' + balance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ')');
                            }
                        } else {
                            $('#amount').removeClass('is-invalid');
                            $('.balance-error').remove();
                        }
                    }
                }
            });
        } else {
            $('#amount').removeClass('is-invalid');
            $('.balance-error').remove();
        }
    }

    // Check balance when amount changes
    $('#amount').on('input', function() {
        checkBalance();
    });

    // Real-time validation: prevent selecting the same account
    $('#to_account_id').on('change', function() {
        const fromAccountId = $('#from_account_id').val();
        const toAccountId = $(this).val();
        
        if (fromAccountId && toAccountId && fromAccountId === toAccountId) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Selection',
                text: 'From account and To account cannot be the same. Please select different bank accounts.',
                confirmButtonText: 'OK'
            });
            
            // Reset the changed field
            $('#to_account_id').val('').trigger('change');
        }
    });

    // Form validation
    $('#transfer-form').on('submit', function(e) {
        const fromAccountId = $('#from_account_id').val();
        const toAccountId = $('#to_account_id').val();

        // Check if from and to accounts are the same
        if (fromAccountId === toAccountId) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Invalid Selection',
                text: 'From account and To account cannot be the same. Please select different bank accounts.'
            });
            return false;
        }

        // Check if amount exceeds balance
        const amount = parseFloat($('#amount').val()) || 0;
        if (fromAccountId && amount > 0) {
            const balanceText = $('#from-balance-amount').text().replace(/[^\d.-]/g, '');
            const balance = parseFloat(balanceText) || 0;
            
            if (amount > balance) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Insufficient Balance',
                    text: 'Transfer amount (TZS ' + amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ') exceeds available balance (TZS ' + balance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ').'
                });
                return false;
            }
        }

        // Check if charges are specified but no account selected
        const charges = parseFloat($('#charges').val()) || 0;
        const chargesAccountId = $('#charges_account_id').val();
        if (charges > 0 && !chargesAccountId) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Charges Account Required',
                text: 'Please select an expense account for the charges.'
            });
            return false;
        }
    });
});
</script>
@endpush
@endsection
