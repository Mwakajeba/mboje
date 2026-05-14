@php
    // Component attributes
    $id = $id ?? 'fx-amount-calculator';
    $fromCurrency = $fromCurrency ?? 'USD';
    $toCurrency = $toCurrency ?? 'TZS';
    $fcyAmountName = $fcyAmountName ?? 'fcy_amount';
    $lcyAmountName = $lcyAmountName ?? 'lcy_amount';
    $exchangeRateName = $exchangeRateName ?? 'exchange_rate';
    $fcyValue = $fcyValue ?? old($fcyAmountName);
    $lcyValue = $lcyValue ?? old($lcyAmountName);
    $rateValue = $rateValue ?? old($exchangeRateName);
    $fcyLabel = $fcyLabel ?? 'Foreign Currency Amount';
    $lcyLabel = $lcyLabel ?? 'Local Currency Amount';
    $rateLabel = $rateLabel ?? 'Exchange Rate';
    $fcyRequired = $fcyRequired ?? false;
    $rateRequired = $rateRequired ?? false;
    $readOnlyRate = $readOnlyRate ?? false;
@endphp

<div id="{{ $id }}" class="fx-amount-calculator card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h6 class="mb-3">
            <i class="bx bx-calculator me-2 text-primary"></i>
            Currency Conversion Calculator
        </h6>
        
        <div class="row g-3">
            <!-- Foreign Currency Amount -->
            <div class="col-md-4">
                <label class="form-label">
                    {{ $fcyLabel }}
                    @if($fcyRequired)
                        <span class="text-danger">*</span>
                    @endif
                </label>
                <div class="input-group">
                    <input type="number" 
                           id="{{ $id }}-fcy" 
                           name="{{ $fcyAmountName }}" 
                           class="form-control fcy-amount-input" 
                           step="0.01" 
                           min="0"
                           value="{{ $fcyValue }}"
                           @if($fcyRequired) required @endif
                           placeholder="0.00">
                    <span class="input-group-text currency-badge" id="{{ $id }}-fcy-badge">{{ $fromCurrency }}</span>
                </div>
            </div>
            
            <!-- Exchange Rate -->
            <div class="col-md-4">
                <label class="form-label">
                    {{ $rateLabel }}
                    @if($rateRequired)
                        <span class="text-danger">*</span>
                    @endif
                </label>
                <div class="input-group">
                    <input type="number" 
                           id="{{ $id }}-rate" 
                           name="{{ $exchangeRateName }}" 
                           class="form-control exchange-rate-input" 
                           step="0.000001" 
                           min="0"
                           value="{{ $rateValue }}"
                           @if($readOnlyRate) readonly @endif
                           @if($rateRequired) required @endif
                           placeholder="0.000000">
                    <span class="input-group-text">
                        <small>{{ $toCurrency }}/{{ $fromCurrency }}</small>
                    </span>
                </div>
                <small class="text-muted">
                    <i class="bx bx-info-circle"></i> 
                    Rate: 1 {{ $fromCurrency }} = <span id="{{ $id }}-rate-display">0.00</span> {{ $toCurrency }}
                </small>
            </div>
            
            <!-- Local Currency Amount (Calculated) -->
            <div class="col-md-4">
                <label class="form-label">
                    {{ $lcyLabel }}
                    <span class="badge bg-info">Calculated</span>
                </label>
                <div class="input-group">
                    <input type="number" 
                           id="{{ $id }}-lcy" 
                           name="{{ $lcyAmountName }}" 
                           class="form-control lcy-amount-input" 
                           step="0.01" 
                           readonly
                           value="{{ $lcyValue }}"
                           placeholder="0.00">
                    <span class="input-group-text currency-badge" id="{{ $id }}-lcy-badge">{{ $toCurrency }}</span>
                </div>
                <small class="text-muted">
                    <i class="bx bx-check-circle text-success"></i> 
                    Auto-calculated
                </small>
            </div>
        </div>
        
        <!-- Calculation Formula Display -->
        <div class="mt-3 p-2 bg-light rounded">
            <small class="text-muted">
                <strong>Formula:</strong> 
                <span id="{{ $id }}-formula">LCY = FCY × Exchange Rate</span>
            </small>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    const calculator = document.getElementById('{{ $id }}');
    if (!calculator) return;
    
    const fcyInput = document.getElementById('{{ $id }}-fcy');
    const rateInput = document.getElementById('{{ $id }}-rate');
    const lcyInput = document.getElementById('{{ $id }}-lcy');
    const rateDisplay = document.getElementById('{{ $id }}-rate-display');
    const formulaDisplay = document.getElementById('{{ $id }}-formula');
    
    // Listen for currency change events
    document.addEventListener('currencyChanged', function(event) {
        const { fromCurrency, toCurrency } = event.detail;
        
        // Update currency badges
        document.getElementById('{{ $id }}-fcy-badge').textContent = fromCurrency;
        document.getElementById('{{ $id }}-lcy-badge').textContent = toCurrency;
        rateInput.nextElementSibling.querySelector('small').textContent = `${toCurrency}/${fromCurrency}`;
        
        // Recalculate if values exist
        calculateLCY();
    });
    
    // Listen for exchange rate updates
    document.addEventListener('exchangeRateUpdated', function(event) {
        const { rate } = event.detail;
        rateInput.value = parseFloat(rate).toFixed(6);
        rateDisplay.textContent = parseFloat(rate).toFixed(6);
        calculateLCY();
    });
    
    // Calculate LCY amount
    function calculateLCY() {
        const fcy = parseFloat(fcyInput.value) || 0;
        const rate = parseFloat(rateInput.value) || 0;
        const lcy = fcy * rate;
        
        lcyInput.value = lcy.toFixed(2);
        rateDisplay.textContent = rate.toFixed(6);
        
        // Update formula display
        formulaDisplay.textContent = `${lcy.toFixed(2)} ${document.getElementById('{{ $id }}-lcy-badge').textContent} = ${fcy.toFixed(2)} ${document.getElementById('{{ $id }}-fcy-badge').textContent} × ${rate.toFixed(6)}`;
    }
    
    // Real-time calculation on input change
    fcyInput.addEventListener('input', calculateLCY);
    rateInput.addEventListener('input', calculateLCY);
    
    // Initialize calculation if values exist
    @if($fcyValue || $rateValue)
        calculateLCY();
    @endif
    
    // Format numbers on blur
    [fcyInput, rateInput, lcyInput].forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value) {
                const num = parseFloat(this.value);
                if (this === rateInput) {
                    this.value = num.toFixed(6);
                } else {
                    this.value = num.toFixed(2);
                }
            }
        });
    });
});
</script>
@endpush

