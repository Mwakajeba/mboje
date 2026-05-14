@php
    // Component attributes
    $id = $id ?? 'exchange-rate-display';
    $fromCurrency = $fromCurrency ?? 'USD';
    $toCurrency = $toCurrency ?? 'TZS';
    $rateDate = $rateDate ?? now()->toDateString();
    $allowOverride = $allowOverride ?? true;
    $showHistory = $showHistory ?? true;
    $rateValue = $rateValue ?? null;
    
    // Get rate override threshold
    $user = auth()->user();
    $threshold = \App\Models\SystemSetting::getValue('fx_rate_override_threshold', 5);
@endphp

<div id="{{ $id }}" class="exchange-rate-display card border-0 shadow-sm mb-3" style="display: none;">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">
                <i class="bx bx-dollar-circle me-2 text-primary"></i>
                Exchange Rate
            </h6>
            @if($showHistory)
                <a href="{{ route('accounting.fx-rates.index', ['from_currency' => $fromCurrency, 'to_currency' => $toCurrency]) }}" 
                   class="btn btn-sm btn-outline-info" 
                   target="_blank"
                   title="View Rate History">
                    <i class="bx bx-history"></i> History
                </a>
            @endif
        </div>
        
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small text-muted">Currency Pair</label>
                <div class="input-group">
                    <span class="input-group-text bg-light">{{ $fromCurrency }}</span>
                    <span class="input-group-text">/</span>
                    <span class="input-group-text bg-light">{{ $toCurrency }}</span>
                </div>
            </div>
            
            <div class="col-md-6">
                <label class="form-label small text-muted">Rate Date</label>
                <input type="date" 
                       id="{{ $id }}-date" 
                       class="form-control form-control-sm" 
                       value="{{ $rateDate }}"
                       readonly>
            </div>
            
            <div class="col-md-12">
                <label class="form-label small text-muted">Current Rate</label>
                <div class="input-group">
                    <input type="number" 
                           id="{{ $id }}-rate" 
                           name="exchange_rate" 
                           class="form-control exchange-rate-input" 
                           step="0.000001" 
                           min="0"
                           value="{{ $rateValue }}"
                           @if(!$allowOverride) readonly @endif
                           data-threshold="{{ $threshold }}">
                    <span class="input-group-text">{{ $toCurrency }}</span>
                </div>
                <small class="text-muted">
                    <i class="bx bx-info-circle"></i> 
                    1 {{ $fromCurrency }} = <span id="{{ $id }}-rate-display">0.00</span> {{ $toCurrency }}
                </small>
            </div>
            
            @if($allowOverride)
                <div class="col-md-12">
                    <div class="alert alert-warning alert-dismissible fade show" id="{{ $id }}-override-warning" style="display: none;" role="alert">
                        <i class="bx bx-error-circle me-2"></i>
                        <strong>Rate Override Detected!</strong>
                        <span id="{{ $id }}-override-message"></span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    
                    <button type="button" 
                            class="btn btn-sm btn-outline-primary" 
                            id="{{ $id }}-override-btn"
                            onclick="requestRateOverride('{{ $id }}')">
                        <i class="bx bx-edit"></i> Override Rate
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    const display = document.getElementById('{{ $id }}');
    if (!display) return;
    
    const rateInput = document.getElementById('{{ $id }}-rate');
    const rateDisplay = document.getElementById('{{ $id }}-rate-display');
    const threshold = parseFloat(rateInput.dataset.threshold) || 5;
    let originalRate = null;
    
    // Listen for currency change events
    document.addEventListener('currencyChanged', function(event) {
        const { fromCurrency, toCurrency } = event.detail;
        
        // Update display
        display.querySelector('.input-group-text:first-child').textContent = fromCurrency;
        display.querySelector('.input-group-text:last-child').textContent = toCurrency;
        display.style.display = 'block';
        
        // Fetch exchange rate
        fetchExchangeRate(fromCurrency, toCurrency);
    });
    
    // Listen for currency reset events
    document.addEventListener('currencyReset', function() {
        display.style.display = 'none';
    });
    
    // Fetch exchange rate from API
    function fetchExchangeRate(fromCurrency, toCurrency) {
        const rateDate = document.getElementById('{{ $id }}-date').value;
        
        fetch(`/api/fx-rates/get-rate?from=${fromCurrency}&to=${toCurrency}&date=${rateDate}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.rate) {
                    originalRate = parseFloat(data.rate);
                    rateInput.value = originalRate.toFixed(6);
                    rateDisplay.textContent = originalRate.toFixed(6);
                    
                    // Trigger rate change event
                    rateInput.dispatchEvent(new Event('change'));
                } else {
                    console.error('Failed to fetch exchange rate:', data.message);
                }
            })
            .catch(error => {
                console.error('Error fetching exchange rate:', error);
            });
    }
    
    // Check for rate override on input change
    rateInput.addEventListener('change', function() {
        const currentRate = parseFloat(this.value) || 0;
        rateDisplay.textContent = currentRate.toFixed(6);
        
        if (originalRate && currentRate !== originalRate) {
            const difference = Math.abs((currentRate - originalRate) / originalRate * 100);
            
            if (difference > threshold) {
                // Show override warning
                const warning = document.getElementById('{{ $id }}-override-warning');
                const message = document.getElementById('{{ $id }}-override-message');
                message.textContent = `Rate differs by ${difference.toFixed(2)}% from current rate. Approval may be required.`;
                warning.style.display = 'block';
            } else {
                // Hide warning
                document.getElementById('{{ $id }}-override-warning').style.display = 'none';
            }
        }
    });
    
    // Initialize if rate value is provided
    @if($rateValue)
        originalRate = parseFloat('{{ $rateValue }}');
        rateDisplay.textContent = originalRate.toFixed(6);
        display.style.display = 'block';
    @endif
});

// Request rate override approval
function requestRateOverride(displayId) {
    const rateInput = document.getElementById(displayId + '-rate');
    const newRate = parseFloat(rateInput.value);
    const originalRate = parseFloat(rateInput.dataset.originalRate || rateInput.value);
    const fromCurrency = document.getElementById(displayId).querySelector('.input-group-text:first-child').textContent;
    const toCurrency = document.getElementById(displayId).querySelector('.input-group-text:last-child').textContent;
    const rateDate = document.getElementById(displayId + '-date').value;
    
    // This will be handled by the Rate Override Controller
    Swal.fire({
        title: 'Override Exchange Rate?',
        html: `
            <p>You are about to override the exchange rate:</p>
            <p><strong>${fromCurrency}/${toCurrency}</strong></p>
            <p>Original Rate: <strong>${originalRate.toFixed(6)}</strong></p>
            <p>New Rate: <strong>${newRate.toFixed(6)}</strong></p>
            <p>Difference: <strong>${Math.abs((newRate - originalRate) / originalRate * 100).toFixed(2)}%</strong></p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Request Approval',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit override request
            fetch('/accounting/fx-rates/override', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    from_currency: fromCurrency,
                    to_currency: toCurrency,
                    rate_date: rateDate,
                    original_rate: originalRate,
                    new_rate: newRate
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Success', 'Rate override request submitted for approval.', 'success');
                } else {
                    Swal.fire('Error', data.message || 'Failed to submit override request.', 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'An error occurred while submitting the request.', 'error');
            });
        }
    });
}
</script>
@endpush

