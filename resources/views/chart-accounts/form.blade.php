@php
use Vinkla\Hashids\Facades\Hashids;
// Build a JS object of class_id => {from, to} for all account classes
$classRanges = [];
foreach ($accountClasses as $class) {
    $classRanges[$class->id] = [
        'from' => $class->range_from,
        'to' => $class->range_to,
    ];
}
@endphp

<form
    action="{{ isset($chartAccount) ? route('accounting.chart-accounts.update', Hashids::encode($chartAccount->id)) : route('accounting.chart-accounts.store') }}"
    method="POST">
    @csrf
    @if(isset($chartAccount))
        @method('PUT')
    @endif

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Select Account Class Group</label>
            <select class="form-select select2" name="account_class_group_id" required>
                <option value="">-- Choose Account Class Group --</option>
                @foreach($accountClassGroups as $group)
                    <option value="{{ $group->id }}" {{ (old('account_class_group_id') == $group->id || (isset($chartAccount) && $chartAccount->account_class_group_id == $group->id)) ? 'selected' : '' }}>
                        {{ $group->accountClass->name ?? 'N/A' }} - {{ $group->name }}
                    </option>
                @endforeach
            </select>
            @error('account_class_group_id')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label">Account Code (<span style="color:red" id="range_hint"></span>)</label>
            <div class="input-group">
                <input type="text" class="form-control" name="account_code"
                    id="account_code_input"
                    value="{{ $chartAccount->account_code ?? old('account_code') }}" required
                    placeholder="Choose from above range ...">
                <span class="input-group-text" id="range_hint_display"></span>
            </div>
            @error('account_code')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Account Name</label>
        <input type="text" class="form-control" name="account_name"
            value="{{ $chartAccount->account_name ?? old('account_name') }}" required
            placeholder="e.g., Cash, Accounts Receivable, etc.">
        @error('account_name')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    <!-- Account Type Selection -->
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Account Type <span class="text-danger">*</span></label>
            <select class="form-select" name="account_type" id="account_type" required>
                <option value="">-- Choose Account Type --</option>
                <option value="parent" {{ (old('account_type') == 'parent' || (isset($chartAccount) && $chartAccount->account_type == 'parent')) ? 'selected' : '' }}>
                    Parent Account
                </option>
                <option value="child" {{ (old('account_type') == 'child' || (isset($chartAccount) && $chartAccount->account_type == 'child')) ? 'selected' : '' }}>
                    Child Account
                </option>
            </select>
            @error('account_type')
                <div class="text-danger">{{ $message }}</div>
            @enderror
            <small class="text-muted">Parent accounts can have child accounts under them</small>
        </div>

        <!-- Parent Account Selection (shown only when account_type is 'child') -->
        <div class="col-md-6" id="parent_account_div" style="display: {{ (old('account_type') == 'child' || (isset($chartAccount) && $chartAccount->account_type == 'child')) ? 'block' : 'none' }};">
            <label class="form-label">Parent Account <span class="text-danger">*</span></label>
            <select class="form-select select2" name="parent_id" id="parent_id">
                <option value="">-- Choose Parent Account --</option>
                @foreach($parentAccounts as $parent)
                    <option value="{{ $parent->id }}" {{ (old('parent_id') == $parent->id || (isset($chartAccount) && $chartAccount->parent_id == $parent->id)) ? 'selected' : '' }}>
                        [{{ $parent->account_code }}] {{ $parent->account_name }} ({{ $parent->accountClassGroup->name ?? 'N/A' }})
                    </option>
                @endforeach
            </select>
            @error('parent_id')
                <div class="text-danger">{{ $message }}</div>
            @enderror
            <small class="text-muted">Select the parent account for this child account</small>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="has_cash_flow" value="1" id="has_cash_flow" 
                    {{ (old('has_cash_flow') || (isset($chartAccount) && $chartAccount->has_cash_flow)) ? 'checked' : '' }}>
                <label class="form-check-label" for="has_cash_flow">
                    Has Cash Flow Impact
                </label>
            </div>
            @error('has_cash_flow')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="has_equity" value="1" id="has_equity" 
                    {{ (old('has_equity') || (isset($chartAccount) && $chartAccount->has_equity)) ? 'checked' : '' }}>
                <label class="form-check-label" for="has_equity">
                    Has Equity Impact
                </label>
            </div>
            @error('has_equity')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Cash Flow Category Dropdown (shown when has_cash_flow is checked) -->
    <div class="mb-3" id="cash_flow_category_div" style="display: {{ (old('has_cash_flow') || (isset($chartAccount) && $chartAccount->has_cash_flow)) ? 'block' : 'none' }};">
        <label class="form-label">Cash Flow Category</label>
        <select class="form-select select2" name="cash_flow_category_id">
            <option value="">-- Choose Cash Flow Category --</option>
            @foreach($cashFlowCategories as $category)
                <option value="{{ $category->id }}" {{ (old('cash_flow_category_id') == $category->id || (isset($chartAccount) && $chartAccount->cash_flow_category_id == $category->id)) ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        @error('cash_flow_category_id')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    <!-- Equity Category Dropdown (shown when has_equity is checked) -->
    <div class="mb-3" id="equity_category_div" style="display: {{ (old('has_equity') || (isset($chartAccount) && $chartAccount->has_equity)) ? 'block' : 'none' }};">
        <label class="form-label">Equity Category</label>
        <select class="form-select select2" name="equity_category_id">
            <option value="">-- Choose Equity Category --</option>
            @foreach($equityCategories as $category)
                <option value="{{ $category->id }}" {{ (old('equity_category_id') == $category->id || (isset($chartAccount) && $chartAccount->equity_category_id == $category->id)) ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        @error('equity_category_id')
            <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-flex justify-content-end">
        <a href="{{ route('accounting.chart-accounts.index') }}" class="btn btn-secondary me-2">Cancel</a>
        <button type="submit" class="btn btn-{{ isset($chartAccount) ? 'primary' : 'success' }}">
            {{ isset($chartAccount) ? 'Update Account' : 'Create Account' }}
        </button>
    </div>
</form>

