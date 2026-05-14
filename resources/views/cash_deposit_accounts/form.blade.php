@php
    $isEdit = isset($cashDepositAccount);
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ $isEdit ? route('cash_deposit_accounts.update', $cashDepositAccount) : route('cash_deposit_accounts.store') }}" method="POST">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="name" 
                value="{{ old('name', $cashDepositAccount->name ?? '') }}" 
                class="form-control" required>
        </div>

        <div class="col-md-6 mb-3">
            <label for="chart_account_id" class="form-label">Chart Account <span class="text-danger">*</span></label>
            <select name="chart_account_id" id="chart_account_id" class="form-select">
                <option value="">-- Select Chart Account --</option>
                @foreach($chartAccounts as $account)
                    <option value="{{ $account->id }}"
                        {{ old('chart_account_id', $cashDepositAccount->chart_account_id ?? '') == $account->id ? 'selected' : '' }}>
                        {{ $account->account_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $cashDepositAccount->description ?? '') }}</textarea>
        </div>

        <div class="col-md-6 d-flex align-items-center mb-3">
            <div class="form-check mt-3">
            <input type="hidden" name="is_active" value="0"> <!-- default if not checked -->
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                {{ old('is_active', $cashDepositAccount->is_active ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">
                Active
            </label>
        </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        {{ $isEdit ? 'Update' : 'Create' }}
    </button>
    <a href="{{ route('cash_deposit_accounts.index') }}" class="btn btn-secondary">Back</a>
</form>
