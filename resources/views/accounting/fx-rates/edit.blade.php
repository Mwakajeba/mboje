@extends('layouts.main')
@section('title', 'Edit FX Rate')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'FX Rates', 'url' => route('accounting.fx-rates.index'), 'icon' => 'bx bx-dollar'],
            ['label' => 'Edit Rate', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">EDIT FX RATE</h6>
        <hr />

        @if($fxRate->is_locked)
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bx bx-lock me-2"></i>
            <strong>This rate is locked!</strong> Locked rates cannot be modified. Please unlock it first if you need to make changes.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <!-- Page Header -->
        <div class="row">
            <div class="col-12">
                <div class="card border-top border-0 border-4 border-primary">
                    <div class="card-body p-5">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="card-title d-flex align-items-center">
                                    <div><i class="bx bx-edit me-1 font-22 text-primary"></i></div>
                                    <h5 class="mb-0 text-primary">Edit FX Rate</h5>
                                </div>
                                <p class="mb-0 text-muted">Update exchange rate information</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="{{ route('accounting.fx-rates.index') }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to Rates
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FX Rate Form -->
        <div class="row">
            <div class="col-12">
                <div class="card radius-10 border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-edit me-2"></i>FX Rate Details</h6>
                    </div>
                    <div class="card-body">
                        @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bx bx-error-circle me-2"></i>
                            <strong>Please fix the following errors:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        <form action="{{ route('accounting.fx-rates.update', $fxRate->id) }}" method="POST" id="fxRateForm">
                            @csrf
                            @method('PUT')

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Rate Date <span class="text-danger">*</span></label>
                                    <input type="date" name="rate_date" class="form-control @error('rate_date') is-invalid @enderror" 
                                           value="{{ old('rate_date', $fxRate->rate_date->format('Y-m-d')) }}" 
                                           {{ $fxRate->is_locked ? 'readonly' : 'required' }}>
                                    @error('rate_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">From Currency <span class="text-danger">*</span></label>
                                    <select name="from_currency" id="from_currency" class="form-select select2-single @error('from_currency') is-invalid @enderror" 
                                            {{ $fxRate->is_locked ? 'disabled' : 'required' }}>
                                        <option value="">Loading currencies...</option>
                                    </select>
                                    @if($fxRate->is_locked)
                                        <input type="hidden" name="from_currency" value="{{ $fxRate->from_currency }}">
                                    @endif
                                    @error('from_currency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">To Currency <span class="text-danger">*</span></label>
                                    <select name="to_currency" id="to_currency" class="form-select select2-single @error('to_currency') is-invalid @enderror" 
                                            {{ $fxRate->is_locked ? 'disabled' : 'required' }}>
                                        <option value="">Loading currencies...</option>
                                    </select>
                                    @if($fxRate->is_locked)
                                        <input type="hidden" name="to_currency" value="{{ $fxRate->to_currency }}">
                                    @endif
                                    @error('to_currency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Spot Rate <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="spot_rate" id="spot_rate" step="0.000001" min="0.000001" 
                                               class="form-control @error('spot_rate') is-invalid @enderror" 
                                               value="{{ old('spot_rate', $fxRate->spot_rate) }}" 
                                               placeholder="0.000000" {{ $fxRate->is_locked ? 'readonly' : 'required' }}>
                                        @if(!$fxRate->is_locked)
                                            <button type="button" class="btn btn-outline-secondary" id="fetch-rate-btn" title="Fetch rate from API">
                                                <i class="bx bx-refresh"></i>
                                            </button>
                                        @endif
                                    </div>
                                    <small class="text-muted">Exchange rate for the transaction date</small>
                                    <div id="rate-info" class="mt-1" style="display: none;">
                                        <small class="text-info">
                                            <i class="bx bx-info-circle"></i>
                                            <span id="rate-source">Rate fetched from API</span>
                                        </small>
                                    </div>
                                    @error('spot_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">
                                        Month-End Rate
                                        <i class="bx bx-info-circle text-info ms-1" data-bs-toggle="tooltip" 
                                           data-bs-placement="top" 
                                           title="Closing exchange rate at the end of the month. Used for month-end revaluation of monetary items (AR, AP, Bank, Loans) per IAS 21. If not provided, the spot rate will be used."></i>
                                    </label>
                                    <input type="number" name="month_end_rate" step="0.000001" min="0.000001" 
                                           class="form-control @error('month_end_rate') is-invalid @enderror" 
                                           value="{{ old('month_end_rate', $fxRate->month_end_rate) }}" 
                                           placeholder="0.000000" {{ $fxRate->is_locked ? 'readonly' : '' }}>
                                    <small class="text-muted d-block mt-1">
                                        <i class="bx bx-info-circle me-1"></i>
                                        <strong>Purpose:</strong> Used for month-end revaluation of foreign currency monetary items (Accounts Receivable, Accounts Payable, Bank Accounts, Loans) according to IAS 21 standards. This rate is applied at the end of each month to calculate unrealized exchange gains/losses.
                                    </small>
                                    <small class="text-muted d-block mt-1">
                                        <strong>Note:</strong> If left blank, the spot rate will be used for month-end revaluation.
                                    </small>
                                    @error('month_end_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">
                                        Average Rate
                                        <i class="bx bx-info-circle text-info ms-1" data-bs-toggle="tooltip" 
                                           data-bs-placement="top" 
                                           title="Average exchange rate calculated over a specific period. Used for income statement items and non-monetary transactions per IAS 21. If not provided, the system will calculate it from daily spot rates."></i>
                                    </label>
                                    <input type="number" name="average_rate" step="0.000001" min="0.000001" 
                                           class="form-control @error('average_rate') is-invalid @enderror" 
                                           value="{{ old('average_rate', $fxRate->average_rate) }}" 
                                           placeholder="0.000000" {{ $fxRate->is_locked ? 'readonly' : '' }}>
                                    <small class="text-muted d-block mt-1">
                                        <i class="bx bx-info-circle me-1"></i>
                                        <strong>Purpose:</strong> Used for translating income statement items (revenue, expenses) and non-monetary transactions during the period. This is the average of exchange rates over the specified period, typically calculated as the mean of daily spot rates.
                                    </small>
                                    <small class="text-muted d-block mt-1">
                                        <strong>Note:</strong> If left blank, the system will automatically calculate the average from daily spot rates for the period.
                                    </small>
                                    @error('average_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Source <span class="text-danger">*</span></label>
                                    <select name="source" class="form-select select2-single @error('source') is-invalid @enderror" 
                                            {{ $fxRate->is_locked ? 'disabled' : 'required' }}>
                                        <option value="manual" {{ old('source', $fxRate->source) == 'manual' ? 'selected' : '' }}>Manual Entry</option>
                                        <option value="api" {{ old('source', $fxRate->source) == 'api' ? 'selected' : '' }}>API Import</option>
                                        <option value="import" {{ old('source', $fxRate->source) == 'import' ? 'selected' : '' }}>Bulk Import</option>
                                    </select>
                                    @if($fxRate->is_locked)
                                        <input type="hidden" name="source" value="{{ $fxRate->source }}">
                                    @endif
                                    @error('source')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <hr>
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('accounting.fx-rates.index') }}" class="btn btn-outline-secondary">
                                            <i class="bx bx-x me-1"></i> Cancel
                                        </a>
                                        @if(!$fxRate->is_locked)
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bx bx-save me-1"></i> Update FX Rate
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-primary" disabled>
                                                <i class="bx bx-lock me-1"></i> Rate is Locked
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </form>
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
        // Fetch currencies from API
        function loadCurrencies() {
            $.get('{{ route("api.exchange-rates.currencies") }}')
                .done(function(response) {
                    if (response.success && response.data.currencies) {
                        const currencies = response.data.currencies;
                        const currentFromCurrency = '{{ old("from_currency", $fxRate->from_currency) }}';
                        const currentToCurrency = '{{ old("to_currency", $fxRate->to_currency) }}';
                        
                        // Populate From Currency dropdown
                        let fromCurrencyOptions = '<option value="">Select Currency</option>';
                        for (const [code, name] of Object.entries(currencies)) {
                            const selected = currentFromCurrency === code ? 'selected' : '';
                            fromCurrencyOptions += `<option value="${code}" ${selected}>${name} (${code})</option>`;
                        }
                        $('#from_currency').html(fromCurrencyOptions);
                        
                        // Populate To Currency dropdown
                        let toCurrencyOptions = '<option value="">Select Currency</option>';
                        for (const [code, name] of Object.entries(currencies)) {
                            const selected = currentToCurrency === code ? 'selected' : '';
                            toCurrencyOptions += `<option value="${code}" ${selected}>${name} (${code})</option>`;
                        }
                        $('#to_currency').html(toCurrencyOptions);
                        
                        // Initialize Select2 after populating options
                        initializeSelect2();
                    }
                })
                .fail(function() {
                    // Fallback: Use database currencies or hardcoded list
                    console.warn('Failed to load currencies from API, using fallback');
                    initializeSelect2();
                });
        }
        
        // Initialize Select2 for currency and source dropdowns
        function initializeSelect2() {
            // Destroy existing Select2 instances first to avoid conflicts
            $('.select2-single').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });
            
            $('.select2-single').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select an option',
                allowClear: true
            });
        }
        
        // Load currencies on page load
        loadCurrencies();

        // Initialize Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Validate that from and to currencies are different
        $('#fxRateForm').on('submit', function(e) {
            const fromCurrency = $('select[name="from_currency"]').val() || $('select[name="from_currency"]:not(:disabled)').val();
            const toCurrency = $('select[name="to_currency"]').val() || $('select[name="to_currency"]:not(:disabled)').val();
            
            if (fromCurrency === toCurrency) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'From currency and To currency must be different.',
                });
                return false;
            }
        });

        @if(!$fxRate->is_locked)
        // Handle currency change - auto-fetch rate (only if not locked)
        $('select[name="from_currency"], select[name="to_currency"]').on('change', function() {
            const fromCurrency = $('select[name="from_currency"]').val();
            const toCurrency = $('select[name="to_currency"]').val();
            
            if (fromCurrency && toCurrency && fromCurrency !== toCurrency) {
                // Auto-fetch exchange rate when currency changes
                fetchExchangeRate();
            } else if (fromCurrency === toCurrency && fromCurrency && toCurrency) {
                $('#spot_rate').val('1.000000');
                $('#rate-info').hide();
            }
        });

        // Handle date change - auto-fetch rate if currencies are selected
        $('input[name="rate_date"]').on('change', function() {
            const fromCurrency = $('select[name="from_currency"]').val();
            const toCurrency = $('select[name="to_currency"]').val();
            
            if (fromCurrency && toCurrency && fromCurrency !== toCurrency) {
                fetchExchangeRate();
            }
        });

        // Fetch exchange rate button
        $('#fetch-rate-btn').on('click', function() {
            fetchExchangeRate();
        });

        // Function to fetch exchange rate from API
        function fetchExchangeRate() {
            const fromCurrency = $('select[name="from_currency"]').val();
            const toCurrency = $('select[name="to_currency"]').val();
            
            if (!fromCurrency || !toCurrency || fromCurrency === toCurrency) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Selection',
                    text: 'Please select different currencies before fetching rate.',
                    timer: 2000,
                    showConfirmButton: false
                });
                return;
            }

            const btn = $('#fetch-rate-btn');
            const originalHtml = btn.html();
            
            btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i>');
            
            $.get('{{ route("api.exchange-rates.rate") }}', {
                from: fromCurrency,
                to: toCurrency
            })
            .done(function(response) {
                if (response.success) {
                    $('#spot_rate').val(response.data.rate.toFixed(6));
                    $('#rate-source').text(`Rate: ${response.data.rate.toFixed(6)} (${new Date(response.data.timestamp).toLocaleString()})`);
                    $('#rate-info').show();
                    
                    // Update source to 'api' if fetched successfully
                    $('select[name="source"]').val('api').trigger('change');
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Exchange Rate Updated',
                        text: `Current rate: 1 ${fromCurrency} = ${response.data.rate.toFixed(6)} ${toCurrency}`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            })
            .fail(function() {
                Swal.fire({
                    icon: 'warning',
                    title: 'Rate Fetch Failed',
                    text: 'Using fallback rate. You can manually enter the exchange rate.',
                    timer: 3000,
                    showConfirmButton: false
                });
            })
            .always(function() {
                btn.prop('disabled', false).html(originalHtml);
            });
        }
        @endif
    });
</script>
@endpush

