@extends('layouts.main')

@section('title', 'Cash Deposit Withdrawal')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
    ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
    ['label' => 'Customers', 'url' => route('customers.index'), 'icon' => 'bx bx-group'],
    ['label' => 'Customer', 'url' => route('customers.show', Hashids::encode($customer->id)), 'icon' => 'bx bx-user'],
    ['label' => 'Withdrawal', 'url' => '#', 'icon' => 'bx bx-user']
]" />
        
        <h5 class="mb-0 text-primary">Cash Deposit Withdrawal</h5>

        <hr>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('cash_deposits.withdrawStore') }}" method="POST">
                    @csrf
                    @if(isset($deposit))
                        <input type="hidden" name="deposit_id" value="{{ Hashids::encode($deposit->id) }}" />
                        <input type="hidden" name="withdrawal_type" value="single_deposit" />
                    @else
                        <input type="hidden" name="customer_id" value="{{ Hashids::encode($customer->id) }}" />
                        <input type="hidden" name="withdrawal_type" value="total_balance" />
                    @endif

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="bank_account_id" class="form-label">Paid From (Bank Account)</label>
                            <select name="bank_account_id" id="bank_account_id" class="form-select" required>
                                <option value="">-- Select Bank Account --</option>
                                @foreach($bankAccounts as $bankAccount)
                                <option value="{{ $bankAccount->id }}"
                                    {{ old('bank_account_id') == $bankAccount->id ? 'selected' : '' }}>
                                    {{ $bankAccount->name }} - {{ $bankAccount->account_number }}
                                </option>
                                @endforeach
                            </select>
                            @error('bank_account_id')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="withdrawal_date" class="form-label">Withdrawal Date</label>
                            <input type="date"
                                class="form-control"
                                id="withdrawal_date"
                                name="withdrawal_date"
                                value="{{ old('withdrawal_date', date('Y-m-d')) }}"
                                required>
                            @error('withdrawal_date')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="amount" class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">TSHS</span>
                                <input type="number"
                                    class="form-control"
                                    id="amount"
                                    name="amount"
                                    value="{{ old('amount') }}"
                                    step="0.01"
                                    min="0.01"
                                    max="{{ isset($deposit) ? $deposit->amount : $totalBalance }}"
                                    placeholder="0"
                                    required>
                            </div>
                            <small class="form-text text-muted">Available deposit: <span class="text-primary cursor-pointer" id="select-full-amount" style="cursor: pointer; text-decoration: underline;">TSHS {{ number_format(isset($deposit) ? $deposit->amount : $totalBalance, 2) }}</span></small>
                            @if(isset($totalBalance) && $cashDeposits->count() > 1)
                                <small class="form-text text-info">Total balance across {{ $cashDeposits->count() }} deposit account(s)</small>
                            @endif
                            @error('amount')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="notes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control"
                                id="notes"
                                name="notes"
                                rows="3"
                                placeholder="Enter any additional notes about this withdrawal">{{ old('notes') }}</textarea>
                            @error('notes')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <a href="{{ route('customers.show', $customer->id)}}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Back
                            </a>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="submit" class="btn btn-warning">
                                <i class="bx bx-money me-1"></i> Process Withdrawal
                            </button>
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
        // Auto-select today's date if not already set
        if (!$('#withdrawal_date').val()) {
            $('#withdrawal_date').val(new Date().toISOString().split('T')[0]);
        }

        // Format amount input
        $('#amount').on('input', function() {
            let value = $(this).val();
            if (value && !isNaN(value)) {
                $(this).val(parseFloat(value).toFixed(2));
            }
        });

                    // Validate amount doesn't exceed available deposit
        $('#amount').on('change', function() {
            let amount = parseFloat($(this).val()) || 0;
            let available = parseFloat('{{ isset($deposit) ? $deposit->amount : ($totalBalance ?? 0) }}');
            
            if (amount > available) {
                alert('Withdrawal amount cannot exceed available deposit amount.');
                $(this).val(available.toFixed(2));
            }
        });

        // Auto-select full amount when clicking on available deposit text
        $('#select-full-amount').on('click', function() {
            let available = parseFloat('{{ isset($deposit) ? $deposit->amount : ($totalBalance ?? 0) }}');
            $('#amount').val(available.toFixed(2));
            
            // Show a brief feedback
            $(this).addClass('text-success').removeClass('text-primary');
            setTimeout(() => {
                $(this).removeClass('text-success').addClass('text-primary');
            }, 500);
        });
    });
</script>
@endpush