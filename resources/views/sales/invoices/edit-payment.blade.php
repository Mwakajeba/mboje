@extends('layouts.main')

@section('title', 'Edit Payment - Invoice #' . $invoice->invoice_number)

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-store'],
            ['label' => 'Sales Invoices', 'url' => route('sales.invoices.index'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Invoice #' . $invoice->invoice_number, 'url' => route('sales.invoices.show', $invoice->encoded_id), 'icon' => 'bx bx-file'],
            ['label' => 'Edit Payment', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        
        <h6 class="mb-0 text-uppercase">EDIT PAYMENT</h6>
        <hr />

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Payment Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('sales.invoices.payment.update', $payment->hash_id) }}">
                            @csrf
                            @method('PUT')
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="amount" class="form-label">Payment Amount <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">TSHS</span>
                                            <input type="number" class="form-control" id="amount" name="amount" 
                                                   value="{{ old('amount', $payment->amount) }}" 
                                                   min="0.01" max="{{ $invoice->balance_due + $payment->amount }}" step="0.01" required>
                                        </div>
                                        <small class="text-muted">
                                            Maximum allowed: {{ number_format($invoice->balance_due + $payment->amount, 2) }} 
                                            (Balance due: {{ number_format($invoice->balance_due, 2) }} + Current payment: {{ number_format($payment->amount, 2) }})
                                        </small>
                                        @error('amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="payment_date" name="payment_date" 
                                               value="{{ old('payment_date', $payment->date->toDateString()) }}" required>
                                        @error('payment_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                        <select class="form-select" id="payment_method" name="payment_method" required>
                                            <option value="">Select Payment Method</option>
                                            <option value="bank" {{ old('payment_method', $payment->bank_account_id ? 'bank' : 'cash_deposit') == 'bank' ? 'selected' : '' }}>Bank Payment</option>
                                            <option value="cash_deposit" {{ old('payment_method', $payment->bank_account_id ? 'bank' : 'cash_deposit') == 'cash_deposit' ? 'selected' : '' }}>Cash Deposit</option>
                                        </select>
                                        @error('payment_method') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3" id="bank_account_section" style="display: {{ $payment->bank_account_id ? 'block' : 'none' }};">
                                        <label for="chart_account_id" class="form-label">Bank Account <span class="text-danger">*</span></label>
                                        <select class="form-select select2-single" id="chart_account_id" name="chart_account_id">
                                            <option value="">Select Bank Account</option>
                                            @foreach($bankAccounts as $account)
                                                <option value="{{ $account->chart_account_id }}" {{ old('chart_account_id', optional($payment->bankAccount)->chart_account_id) == $account->chart_account_id ? 'selected' : '' }}>
                                                    {{ $account->name }} ({{ $account->account_number }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('chart_account_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                    
                                    <div class="mb-3" id="cash_deposit_section" style="display: {{ $payment->bank_account_id ? 'none' : 'block' }};">
                                        <label for="cash_deposit_id" class="form-label">Customer Account</label>
                                        <select class="form-select select2-single" id="cash_deposit_id" name="cash_deposit_id">
                                            <option value="">Select Customer Account</option>
                                        </select>
                                        <small class="text-muted">Select a customer first to see their account and balance</small>
                                        <div id="no_account_message" class="text-danger mt-2" style="display: none;">
                                            <i class="bx bx-info-circle"></i> No account available for this customer
                                        </div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3" 
                                                  placeholder="Optional payment description">{{ old('description', $payment->description) }}</textarea>
                                        @error('description') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <a href="{{ route('sales.invoices.show', $invoice->encoded_id) }}" class="btn btn-secondary">
                                        <i class="bx bx-arrow-back me-1"></i>Cancel
                                    </a>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bx bx-save me-1" data-processing-text="Updating..."></i>Update Payment
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Invoice Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Invoice Number:</span>
                            <span class="fw-bold">{{ $invoice->invoice_number }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Customer:</span>
                            <span>{{ $invoice->customer->name }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Invoice Date:</span>
                            <span>{{ $invoice->invoice_date->format('M d, Y') }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Due Date:</span>
                            <span class="{{ $invoice->isOverdue() ? 'text-danger' : '' }}">
                                {{ $invoice->due_date->format('M d, Y') }}
                            </span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Amount:</span>
                            <span class="fw-bold">{{ number_format($invoice->total_amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Paid Amount:</span>
                            <span class="text-success">{{ number_format($invoice->paid_amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Balance Due:</span>
                            <span class="text-danger fw-bold">{{ number_format($invoice->balance_due, 2) }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span>Status:</span>
                            <span>{!! $invoice->status_badge !!}</span>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Current Payment Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Original Amount:</span>
                            <span class="fw-bold">{{ number_format($payment->amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Payment Date:</span>
                            <span>{{ $payment->date->format('M d, Y') }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Payment Method:</span>
                            <span>
                                @if($payment->cash_deposit_id)
                                    <span class="badge bg-info">Cash Deposit</span>
                                @elseif($payment->bank_account_id)
                                    <span class="badge bg-primary">{{ $payment->bankAccount->name ?? 'Bank' }}</span>
                                @else
                                    <span class="badge bg-success">Cash</span>
                                @endif
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Recorded By:</span>
                            <span>{{ $payment->user->name }}</span>
                        </div>
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
    // Initialize Select2
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Handle payment method changes
    $('#payment_method').change(function() {
        const selectedMethod = $(this).val();
        
        // Hide all sections first
        $('#bank_account_section, #cash_deposit_section').hide();
        
        // Show relevant section based on selection
        if (selectedMethod === 'bank') {
            $('#bank_account_section').show();
        } else if (selectedMethod === 'cash_deposit') {
            $('#cash_deposit_section').show();
            
            // Load customer account since we already have the customer
                            const customerId = '{{ $invoice->customer->encoded_id }}';
            if (customerId) {
                loadCustomerCashDeposits(customerId);
            }
        }
    });

    // Load customer cash deposits
    function loadCustomerCashDeposits(customerId) {
        console.log('Loading cash deposits for customer:', customerId);
        
        $.ajax({
            url: `/sales/invoices/customer/${customerId}/cash-deposits`,
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                console.log('Cash deposits response:', response);
                const select = $('#cash_deposit_id');
                select.empty();
                select.append('<option value="">Select Customer Account</option>');
                
                if (response.data && response.data.length > 0) {
                    response.data.forEach(function(deposit) {
                        // For customer balance, send a special value to indicate customer balance
                        const depositId = String(deposit.id);
                        const value = depositId.startsWith('customer_balance') ? 'customer_balance' : deposit.id;
                        const isSelected = '{{ $payment->cash_deposit_id }}' === value || 
                                         ('{{ $payment->cash_deposit_id }}' === 'null' && value === 'customer_balance');
                        
                        select.append(`<option value="${value}" data-balance-id="${deposit.id}" ${isSelected ? 'selected' : ''}>${deposit.balance_text}</option>`);
                    });
                    $('#no_account_message').hide();
                    select.prop('disabled', false);
                    console.log('Added', response.data.length, 'balance options');
                } else {
                    select.append('<option value="">No account available for this customer</option>');
                    select.prop('disabled', true);
                    $('#no_account_message').show();
                    console.log('No customer accounts available');
                }
            },
            error: function(xhr) {
                console.error('Error loading cash deposits:', xhr);
                console.error('Response text:', xhr.responseText);
                const select = $('#cash_deposit_id');
                select.empty();
                select.append('<option value="">Error loading accounts</option>');
            }
        });
    }

    // Trigger change event on page load if payment method is already selected
    if ($('#payment_method').val()) {
        $('#payment_method').trigger('change');
    }
});
</script>
@endpush 