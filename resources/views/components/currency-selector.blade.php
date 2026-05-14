@php
    // Get functional currency from system settings or company
    $user = auth()->user();
    $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', $user->company->functional_currency ?? 'TZS');
    
    // Get active currencies from database
    $currencies = \App\Models\Currency::where('company_id', $user->company_id)
        ->where('is_active', true)
        ->orderBy('currency_code')
        ->get();
    
    // If no currencies in DB, use supported currencies from API
    if ($currencies->isEmpty()) {
        $exchangeRateService = app(\App\Services\ExchangeRateService::class);
        $supportedCurrencies = $exchangeRateService->getSupportedCurrencies();
        $currencies = collect($supportedCurrencies)->map(function($name, $code) {
            return (object)['currency_code' => $code, 'currency_name' => $name];
        });
    }
    
    // Get selected currency (from old input or default)
    $selectedCurrency = old($name ?? 'currency', $value ?? $functionalCurrency);
    
    // Component attributes
    $id = $id ?? ($name ?? 'currency');
    $name = $name ?? 'currency';
    $class = $class ?? 'form-select';
    $required = $required ?? false;
    $onChange = $onChange ?? null;
@endphp

<div class="mb-3">
    <label for="{{ $id }}" class="form-label">
        Currency
        @if($required)
            <span class="text-danger">*</span>
        @endif
    </label>
    <select 
        id="{{ $id }}" 
        name="{{ $name }}" 
        class="{{ $class }} currency-selector" 
        data-functional-currency="{{ $functionalCurrency }}"
        @if($required) required @endif
        @if($onChange) onchange="{{ $onChange }}" @endif
    >
        @foreach($currencies as $currency)
            <option 
                value="{{ $currency->currency_code }}" 
                data-name="{{ $currency->currency_name ?? $currency->currency_code }}"
                {{ $selectedCurrency == $currency->currency_code ? 'selected' : '' }}
            >
                {{ $currency->currency_name ?? $currency->currency_code }} ({{ $currency->currency_code }})
            </option>
        @endforeach
    </select>
    <small class="text-muted">
        <i class="bx bx-info-circle"></i> 
        Functional Currency: <strong>{{ $functionalCurrency }}</strong>
    </small>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    const selector = document.getElementById('{{ $id }}');
    if (!selector) return;
    
    // Initialize Select2 if class contains 'select2-single'
    if (selector.classList.contains('select2-single')) {
        $(selector).select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }
    
    // Auto-fetch exchange rate on currency change
    selector.addEventListener('change', function() {
        const selectedCurrency = this.value;
        const functionalCurrency = this.dataset.functionalCurrency;
        
        // Only fetch rate if currency is different from functional currency
        if (selectedCurrency !== functionalCurrency) {
            // Trigger custom event for exchange rate fetching
            const event = new CustomEvent('currencyChanged', {
                detail: {
                    fromCurrency: selectedCurrency,
                    toCurrency: functionalCurrency,
                    currencyName: this.options[this.selectedIndex].dataset.name
                }
            });
            document.dispatchEvent(event);
        } else {
            // Reset exchange rate if functional currency selected
            const resetEvent = new CustomEvent('currencyReset');
            document.dispatchEvent(resetEvent);
        }
    });
});
</script>
@endpush

