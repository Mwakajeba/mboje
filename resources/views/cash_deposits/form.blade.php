<form
    action="{{ isset($cashDeposit) ? route('cash_deposits.update', $cashDeposit) : route('cash_deposits.store') }}"
    method="POST"
    id="cashDepositForm">
    @csrf
    @if(isset($cashDeposit))
    @method('PUT')
    @endif

    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
    @endif

    @if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="customer_id" class="form-label">Customer</label>

            <select name="customer_id" id="customer_id" class="form-select select2-single" required>
                <option value="">-- Select Customer --</option>
                @foreach($customers as $customer)
                <option value="{{ $customer->id }}"
                    {{ old('customer_id', isset($cashDeposit) ? $cashDeposit->customer_id : (isset($selectedCustomerId) ? $selectedCustomerId : '')) == $customer->id ? 'selected' : '' }}>
                    {{ $customer->name }}
                </option>
                @endforeach
            </select>
            @error('customer_id') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-6 mb-3">
            <label for="type_id" class="form-label">Deposit Type</label>

            <select name="type_id" id="type_id" class="form-select select2-single" required>
                <option value="">-- Select Type --</option>
                @foreach($types as $type)
                <option value="{{ $type->id }}"
                    {{ old('type_id', isset($cashDeposit) ? $cashDeposit->type_id : (isset($selectedTypeId) ? $selectedTypeId : '')) == $type->id ? 'selected' : '' }}>
                    {{ $type->name }}
                </option>
                @endforeach
            </select>
            @error('type_id') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="bank_account_id" class="form-label">Bank Account</label>
            <select name="bank_account_id" id="bank_account_id" class="form-select select2-single" required>
                <option value="">-- Select Bank Account --</option>
                @foreach($bankAccounts as $bankAccount)
                <option value="{{ $bankAccount->id }}"
                    {{ old('bank_account_id') == $bankAccount->id ? 'selected' : '' }}>
                    {{ $bankAccount->name }} ({{ $bankAccount->account_number }})
                </option>
                @endforeach
            </select>
            @error('bank_account_id') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-6 mb-3">
            <label for="deposit_date" class="form-label">Deposit Date</label>
            <input type="date" name="deposit_date" id="deposit_date" class="form-control" 
                value="{{ old('deposit_date', date('Y-m-d')) }}" required>
            @error('deposit_date') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="amount" class="form-label">Amount</label>
            <input type="number" name="amount" id="amount" class="form-control" 
                value="{{ old('amount', isset($cashDeposit) ? $cashDeposit->amount : '') }}" 
                step="0.01" min="0.01" required>
            @error('amount') <span class="text-danger">{{ $message }}</span> @enderror
        </div>

        <div class="col-md-6 mb-3">
            <label for="notes" class="form-label">Notes (Optional)</label>
            <textarea name="notes" id="notes" class="form-control" rows="3" 
                placeholder="Enter any additional notes...">{{ old('notes') }}</textarea>
            @error('notes') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <a href="{{ route('cash_deposits.index') }}" class="btn btn-secondary">Back</a>
        </div>
        <div class="col-md-6 text-end">
            <button type="submit" class="btn btn-primary">
                {{ isset($cashDeposit) ? 'Update' : 'Create' }}
            </button>
        </div>
    </div>
</form>