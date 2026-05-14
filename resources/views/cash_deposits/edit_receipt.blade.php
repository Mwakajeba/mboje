@extends('layouts.main')

@section('title', 'Edit Cash Deposit Deposit')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Customers', 'url' => route('customers.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Customer', 'url' => route('customers.show', Hashids::encode($deposit->customer_id)), 'icon' => 'bx bx-user'],
            ['label' => 'Cash Deposit', 'url' => route('cash_deposits.show', Hashids::encode($deposit->id)), 'icon' => 'bx bx-money'],
            ['label' => 'Edit Deposit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        
        <h5 class="mb-0 text-primary">Edit Cash Deposit Deposit</h5>

        <hr>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('receipts.update', Hashids::encode($receipt->id)) }}" method="POST" id="receipt-form">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="bank_account_id" class="form-label">Bank Account</label>
                            <select name="bank_account_id" id="bank_account_id" class="form-select" required>
                                <option value="">-- Select Bank Account --</option>
                                @foreach($bankAccounts as $bankAccount)
                                <option value="{{ $bankAccount->id }}"
                                    {{ old('bank_account_id', $receipt->bank_account_id) == $bankAccount->id ? 'selected' : '' }}>
                                    {{ $bankAccount->name }} - {{ $bankAccount->account_number }}
                                </option>
                                @endforeach
                            </select>
                            @error('bank_account_id')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="date" class="form-label">Deposit Date</label>
                            <input type="date"
                                class="form-control transaction-date"
                                id="date"
                                name="date"
                                value="{{ old('date', $receipt->date->format('Y-m-d')) }}"
                                required>
                            @error('date')
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
                                    value="{{ old('amount', $receipt->amount) }}"
                                    step="0.01"
                                    min="0.01"
                                    placeholder="0.00"
                                    required>
                            </div>
                            @error('amount')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control"
                                id="description"
                                name="description"
                                rows="3"
                                placeholder="Enter description for this deposit">{{ old('description', $receipt->description) }}</textarea>
                            @error('description')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="row mt-4">
                        @can('view cash deposit')
                        <div class="col-md-6">
                            <a href="{{ route('cash_deposits.show', Hashids::encode($deposit->id)) }}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Back
                            </a>
                        </div>
                        @endcan
                        <div class="col-md-6 text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i> Update Deposit
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
        // Format amount input
        $('#amount').on('input', function() {
            let value = $(this).val();
            if (value && !isNaN(value)) {
                $(this).val(parseFloat(value).toFixed(2));
            }
        });

        function checkPeriodLock(date, onResult) {
            if (!date) {
                return;
            }

            $.ajax({
                url: '{{ route('settings.period-closing.check-date') }}',
                method: 'GET',
                data: { date: date },
                success: function(response) {
                    if (typeof onResult === 'function') {
                        onResult(response);
                    }
                },
                error: function() {
                    console.error('Failed to check period lock status.');
                }
            });
        }

        $('.transaction-date').on('change', function() {
            const date = $(this).val();
            checkPeriodLock(date, function(response) {
                if (response.locked) {
                    Swal.fire({
                        title: 'Locked Period',
                        text: response.message || 'The selected period is locked. Please choose another date.',
                        icon: 'warning'
                    });
                }
            });
        });

        $('#receipt-form').on('submit', function(e) {
            const form = this;
            const date = $('.transaction-date').val();
            if (!date) {
                return;
            }

            e.preventDefault();

            checkPeriodLock(date, function(response) {
                if (response.locked) {
                    Swal.fire({
                        title: 'Locked Period',
                        text: response.message || 'The selected period is locked. Please choose another date.',
                        icon: 'error'
                    });
                } else {
                    form.submit();
                }
            });
        });
    });
</script>
@endpush 