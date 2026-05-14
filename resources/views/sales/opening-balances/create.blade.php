@extends('layouts.main')

@section('title', 'Add Opening Balance')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales', 'url' => route('sales.invoices.index'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Opening Balances', 'url' => route('sales.opening-balances.index'), 'icon' => 'bx bx-book'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-plus me-2"></i>New Opening Balance</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('sales.opening-balances.store') }}">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Customer <span class="text-danger">*</span></label>
                            <select name="customer_id" class="form-select select2-single" required>
                                <option value="">Select customer</option>
                                @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Choose the customer for this opening balance.</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Opening Date <span class="text-danger">*</span></label>
                            <input type="date" name="opening_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            <small class="text-muted">The effective date for the opening balance.</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
                            <small class="text-muted">Total receivable to bring forward.</small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Currency</label>
                            @php
                                $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', auth()->user()->company->functional_currency ?? 'TZS');
                                $currencies = \App\Models\Currency::where('company_id', auth()->user()->company_id)
                                    ->where('is_active', true)
                                    ->orderBy('currency_code')
                                    ->get();
                                
                                // Fallback to API currencies if database is empty
                                if ($currencies->isEmpty()) {
                                    $supportedCurrencies = app(\App\Services\ExchangeRateService::class)->getSupportedCurrencies();
                                    $currencies = collect($supportedCurrencies)->map(function($name, $code) {
                                        return (object)['currency_code' => $code, 'currency_name' => $name];
                                    });
                                }
                            @endphp
                            <select name="currency" class="form-select select2-single">
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->currency_code }}" 
                                            {{ old('currency', $functionalCurrency) == $currency->currency_code ? 'selected' : '' }}>
                                        {{ $currency->currency_name ?? $currency->currency_code }} ({{ $currency->currency_code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Exchange Rate</label>
                            <input type="number" name="exchange_rate" class="form-control" step="0.000001" min="0.000001" value="1.000000">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Reference</label>
                            <input type="text" name="reference" class="form-control" maxlength="100" placeholder="Optional ref">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Notes</label>
                            <input type="text" name="notes" class="form-control" placeholder="Optional notes">
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('sales.opening-balances.index') }}" class="btn btn-outline-secondary"><i class="bx bx-x me-1"></i>Cancel</a>
                        <button type="submit" class="btn btn-primary" data-processing-text="Creating..."><i class="bx bx-check me-1"></i>Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection


