@extends('layouts.main')

@section('title', 'Reallocate Budget')
@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => __('app.budgets'), 'url' => route('accounting.budgets.index'), 'icon' => 'bx bx-chart'],
            ['label' => $budget->name, 'url' => route('accounting.budgets.show', $budget), 'icon' => 'bx bx-show'],
            ['label' => 'Reallocate', 'url' => '#', 'icon' => 'bx bx-transfer']
        ]" />
        <h6 class="mb-0 text-uppercase">Reallocate Budget Amount</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="bx bx-transfer me-2"></i>
                            Reallocate Budget Amount
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Budget Info -->
                        <div class="alert alert-info mb-4">
                            <h6 class="alert-heading"><i class="bx bx-info-circle me-2"></i>Budget Information</h6>
                            <hr>
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Budget Name:</strong> {{ $budget->name }}
                                </div>
                                <div class="col-md-4">
                                    <strong>Year:</strong> {{ $budget->year }}
                                </div>
                                <div class="col-md-4">
                                    <strong>Total Budget:</strong> TZS {{ number_format($budget->total_amount, 2) }}
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <strong>Status:</strong> 
                                    @if($budget->status === 'pending_approval')
                                        <span class="badge bg-warning">Pending Approval - Level {{ $budget->current_approval_level }}</span>
                                    @elseif($budget->status === 'approved')
                                        <span class="badge bg-success">Approved</span>
                                    @elseif($budget->status === 'active')
                                        <span class="badge bg-primary">Active</span>
                                    @elseif($budget->status === 'rejected')
                                        <span class="badge bg-danger">Rejected</span>
                                    @else
                                        <span class="badge bg-secondary">Draft</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if(in_array($budget->status, ['approved', 'active']))
                            <div class="alert alert-warning mb-4">
                                <h6 class="alert-heading"><i class="bx bx-error-circle me-2"></i>Important Notice</h6>
                                <hr>
                                <p class="mb-0">
                                    <strong>This budget is currently {{ $budget->status === 'approved' ? 'approved' : 'active' }}.</strong> 
                                    Reallocating amounts will automatically resubmit the budget for approval. 
                                    The budget status will be reset to <strong>Pending Approval</strong> and will need to go through the approval workflow again.
                                </p>
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bx bx-error-circle me-2"></i>
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <form id="reallocateForm" action="{{ route('accounting.budgets.reallocate.store', $budget) }}" method="POST">
                            @csrf
                            
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary fw-bold mb-3">
                                        <i class="bx bx-transfer me-2"></i>
                                        Reallocation Details
                                    </h6>
                                </div>
                                
                                <!-- From Account -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label fw-bold">
                                            <i class="bx bx-arrow-from-left me-1"></i>
                                            From Account <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select select2-single @error('from_account_id') is-invalid @enderror" 
                                                id="from_account_id" name="from_account_id" required>
                                            <option value="">Select Source Account</option>
                                            @foreach($budgetAccounts as $account)
                                                @php
                                                    $budgetLine = $budget->budgetLines->where('account_id', $account->id)->first();
                                                    $availableAmount = $budgetLine ? $budgetLine->amount : 0;
                                                @endphp
                                                @if($availableAmount > 0)
                                                    <option value="{{ $account->id }}" 
                                                            data-amount="{{ $availableAmount }}"
                                                            {{ old('from_account_id') == $account->id ? 'selected' : '' }}>
                                                        {{ $account->account_code }} - {{ $account->account_name }} 
                                                        (Available: TZS {{ number_format($availableAmount, 2) }})
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Select the account to transfer amount from</small>
                                        @error('from_account_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- To Account -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label fw-bold">
                                            <i class="bx bx-arrow-to-right me-1"></i>
                                            To Account <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select select2-single @error('to_account_id') is-invalid @enderror" 
                                                id="to_account_id" name="to_account_id" required>
                                            <option value="">Select Destination Account</option>
                                            @foreach($allAccounts as $account)
                                                <option value="{{ $account->id }}" 
                                                        {{ old('to_account_id') == $account->id ? 'selected' : '' }}>
                                                    {{ $account->account_code }} - {{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Select the account to transfer amount to</small>
                                        @error('to_account_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Amount -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label fw-bold">
                                            <i class="bx bx-money me-1"></i>
                                            Amount <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">TZS</span>
                                            <input type="number" 
                                                   class="form-control @error('amount') is-invalid @enderror" 
                                                   id="amount" 
                                                   name="amount" 
                                                   value="{{ old('amount') }}" 
                                                   step="0.01" 
                                                   min="0.01"
                                                   placeholder="0.00" 
                                                   required>
                                        </div>
                                        <small class="text-muted" id="available-amount-text"></small>
                                        @error('amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Reason -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label fw-bold">
                                            <i class="bx bx-message-square-detail me-1"></i>
                                            Reason (Optional)
                                        </label>
                                        <textarea class="form-control @error('reason') is-invalid @enderror" 
                                                  name="reason" 
                                                  rows="3" 
                                                  placeholder="Enter reason for reallocation">{{ old('reason') }}</textarea>
                                        @error('reason')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Summary Card -->
                            <div class="card bg-light mb-4">
                                <div class="card-body">
                                    <h6 class="card-title"><i class="bx bx-info-circle me-2"></i>Reallocation Summary</h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <strong>From:</strong> <span id="summary-from">-</span>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>To:</strong> <span id="summary-to">-</span>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Amount:</strong> <span id="summary-amount" class="text-success fw-bold">TZS 0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('accounting.budgets.show', $budget) }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-check me-1"></i>Reallocate Amount
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
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

    // Update summary and available amount when from account changes
    $('#from_account_id').on('change', function() {
        updateSummary();
        updateAvailableAmount();
    });

    // Update summary when to account changes
    $('#to_account_id').on('change', function() {
        updateSummary();
    });

    // Update summary when amount changes
    $('#amount').on('input', function() {
        updateSummary();
        validateAmount();
    });

    function updateAvailableAmount() {
        const fromAccountId = $('#from_account_id').val();
        const selectedOption = $('#from_account_id option:selected');
        const availableAmount = selectedOption.data('amount') || 0;
        
        if (fromAccountId && availableAmount > 0) {
            $('#available-amount-text').text('Available: TZS ' + parseFloat(availableAmount).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
            $('#amount').attr('max', availableAmount);
        } else {
            $('#available-amount-text').text('');
            $('#amount').removeAttr('max');
        }
    }

    function updateSummary() {
        const fromAccountId = $('#from_account_id').val();
        const toAccountId = $('#to_account_id').val();
        const amount = parseFloat($('#amount').val()) || 0;

        if (fromAccountId) {
            const fromText = $('#from_account_id option:selected').text().split(' (')[0];
            $('#summary-from').text(fromText);
        } else {
            $('#summary-from').text('-');
        }

        if (toAccountId) {
            const toText = $('#to_account_id option:selected').text();
            $('#summary-to').text(toText);
        } else {
            $('#summary-to').text('-');
        }

        $('#summary-amount').text('TZS ' + amount.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));
    }

    function validateAmount() {
        const amount = parseFloat($('#amount').val()) || 0;
        const maxAmount = parseFloat($('#amount').attr('max')) || 0;
        
        if (maxAmount > 0 && amount > maxAmount) {
            $('#amount').addClass('is-invalid');
            $('#available-amount-text').html('<span class="text-danger">Amount exceeds available balance!</span>');
        } else {
            $('#amount').removeClass('is-invalid');
            updateAvailableAmount();
        }
    }

    // Form submission validation
    $('#reallocateForm').on('submit', function(e) {
        const fromAccountId = $('#from_account_id').val();
        const toAccountId = $('#to_account_id').val();
        const amount = parseFloat($('#amount').val()) || 0;
        const maxAmount = parseFloat($('#amount').attr('max')) || 0;

        if (!fromAccountId || !toAccountId) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please select both source and destination accounts.'
            });
            return false;
        }

        if (fromAccountId === toAccountId) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Source and destination accounts must be different.'
            });
            return false;
        }

        if (amount <= 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please enter a valid amount greater than zero.'
            });
            return false;
        }

        if (maxAmount > 0 && amount > maxAmount) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Insufficient Balance',
                text: 'Amount exceeds available balance in source account.'
            });
            return false;
        }
    });

    // Initialize on page load
    updateSummary();
    updateAvailableAmount();
});
</script>
@endpush
@endsection

