@extends('layouts.main')
@section('title', 'Edit Provision (IAS 37)')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
                ['label' => 'Provisions (IAS 37)', 'url' => route('accounting.provisions.index'), 'icon' => 'bx bx-shield-quarter'],
                ['label' => 'Edit: ' . $provision->provision_number, 'url' => '#', 'icon' => 'bx bx-edit']
            ]" />
            <div>
                <a href="{{ route('accounting.provisions.show', $provision->encoded_id) }}" class="btn btn-secondary me-2">
                    <i class="bx bx-arrow-back"></i> Back to Detail
                </a>
                <a href="{{ route('accounting.provisions.index') }}" class="btn btn-outline-secondary">
                    Back to List
                </a>
            </div>
        </div>

        <h6 class="mb-0 text-uppercase">EDIT PROVISION (IAS 37): {{ $provision->provision_number }}</h6>
        <hr />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error') || (isset($errors) && $errors->any()))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                @if(session('error'))
                    {{ session('error') }}
                @else
                    Please fix the following errors:
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card radius-10 border-0 shadow-sm">
        <form method="POST" action="{{ route('accounting.provisions.update', $provision->encoded_id) }}" id="provisionForm">
            @csrf
            @method('PUT')
            <div class="card-body">
                <h5 class="mb-3">Provision Template</h5>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Template</label>
                        <select id="provision_template" class="form-select select2-single" data-placeholder="Choose template">
                            <option value="">-- Select Template --</option>
                            @foreach($provisionTemplates as $key => $tpl)
                                <option value="{{ $key }}" 
                                    {{ $provision->provision_type === $key ? 'selected' : '' }}
                                    data-visibility='@json($tpl['field_visibility'] ?? [])'>{{ $tpl['label'] }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Select a template to pre-orient the provision according to IAS 37 type and typical double entry.</small>
                    </div>
                    <div class="col-md-8">
                        <div id="template-details" class="alert alert-secondary mb-0">
                            @php
                                $currentTemplate = $provisionTemplates[$provision->provision_type] ?? null;
                            @endphp
                            @if($currentTemplate)
                                <h6 class="fw-bold mb-1">{{ $currentTemplate['label'] }}</h6>
                                <p class="mb-1">{{ $currentTemplate['description'] }}</p>
                                <p class="mb-1"><strong>Typical double entry:</strong> {{ $currentTemplate['gl_pattern'] }}</p>
                                <p class="mb-0"><strong>Notes:</strong> {{ $currentTemplate['notes'] }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <h5 class="mb-3">Obligation & Probability (IAS 37 Gatekeeper)</h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Provision Type</label>
                        <select name="provision_type" id="provision_type" class="form-select select2-single" required>
                            <option value="">-- Select Type --</option>
                            <option value="legal_claim" {{ $provision->provision_type === 'legal_claim' ? 'selected' : '' }}>Legal Claim</option>
                            <option value="warranty" {{ $provision->provision_type === 'warranty' ? 'selected' : '' }}>Warranty</option>
                            <option value="onerous_contract" {{ $provision->provision_type === 'onerous_contract' ? 'selected' : '' }}>Onerous Contract</option>
                            <option value="environmental" {{ $provision->provision_type === 'environmental' ? 'selected' : '' }}>Environmental Restoration</option>
                            <option value="restructuring" {{ $provision->provision_type === 'restructuring' ? 'selected' : '' }}>Restructuring</option>
                            <option value="employee_benefit" {{ $provision->provision_type === 'employee_benefit' ? 'selected' : '' }}>Employee Benefit</option>
                            <option value="other" {{ $provision->provision_type === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        <small class="text-muted">Classify the obligation (e.g. lawsuit, warranty, onerous contract).</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Present Obligation Type</label>
                        <select name="present_obligation_type" class="form-select select2-single">
                            <option value="">-- Select --</option>
                            <option value="legal" {{ $provision->present_obligation_type === 'legal' ? 'selected' : '' }}>Legal</option>
                            <option value="constructive" {{ $provision->present_obligation_type === 'constructive' ? 'selected' : '' }}>Constructive</option>
                        </select>
                        <small class="text-muted">Legal: from law/contract. Constructive: from past practice or public commitments.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Present Obligation Exists?</label>
                        <select name="has_present_obligation" class="form-select select2-single" required>
                            <option value="1" {{ $provision->has_present_obligation ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ !$provision->has_present_obligation ? 'selected' : '' }}>No (Contingent only)</option>
                        </select>
                        <small class="text-muted">Select "Yes" only if a present obligation exists at reporting date.</small>
                    </div>
                </div>

                <div class="row mb-3" id="probability-fields-group">
                    <div class="col-md-4">
                        <label class="form-label">Probability of Outflow</label>
                        <select name="probability" class="form-select select2-single" required>
                            <option value="remote" {{ $provision->probability === 'remote' ? 'selected' : '' }}>Remote</option>
                            <option value="possible" {{ $provision->probability === 'possible' ? 'selected' : '' }}>Possible</option>
                            <option value="probable" {{ $provision->probability === 'probable' ? 'selected' : '' }}>Probable (> 50%)</option>
                            <option value="virtually_certain" {{ $provision->probability === 'virtually_certain' ? 'selected' : '' }}>Virtually Certain</option>
                        </select>
                        <small class="text-muted">Provision is recognised only if outflow is at least "Probable".</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Probability (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="probability_percent" class="form-control" value="{{ $provision->probability_percent }}">
                        <small class="text-muted">Optional quantitative estimate of likelihood (for disclosures).</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estimate Method</label>
                        <select name="estimate_method" class="form-select select2-single" required>
                            <option value="best_estimate" {{ $provision->estimate_method === 'best_estimate' ? 'selected' : '' }}>Best Estimate</option>
                            <option value="expected_value" {{ $provision->estimate_method === 'expected_value' ? 'selected' : '' }}>Expected Value (many outcomes)</option>
                            <option value="most_likely_outcome" {{ $provision->estimate_method === 'most_likely_outcome' ? 'selected' : '' }}>Most Likely Outcome (single obligation)</option>
                        </select>
                        <small class="text-muted">Choose how management determined the best estimate per IAS 37.</small>
                    </div>
                </div>

                <hr>

                <!-- Computation Panel (Dynamic based on provision type) -->
                <div id="computation-panel" class="card bg-light mb-3" style="display:none;">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bx bx-calculator me-2"></i>Provision Calculation</h6>
                    </div>
                    <div class="card-body" id="computation-inputs">
                        <!-- Dynamically populated based on provision type -->
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-sm btn-primary" id="calculate-provision">
                            <i class="bx bx-calculate"></i> Recalculate Provision Amount
                        </button>
                        <span id="calculation-result" class="ms-3"></span>
                        @if($provision->computation_assumptions)
                            <small class="text-muted d-block mt-2">
                                <i class="bx bx-info-circle"></i> Current assumptions stored: {{ json_encode($provision->computation_assumptions) }}
                            </small>
                        @endif
                    </div>
                </div>

                <h5 class="mb-3">Measurement & Discounting</h5>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $provision->title) }}" required>
                        <small class="text-muted">Short, meaningful name (e.g. "Environmental restoration – Site A").</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Branch</label>
                        <select name="branch_id" class="form-select select2-single">
                            <option value="">Use current branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $provision->branch_id == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">If blank, the current user/ session branch will be used.</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description / Nature of Obligation</label>
                    <textarea name="description" class="form-control" rows="3" required>{{ old('description', $provision->description) }}</textarea>
                    <small class="text-muted">Describe what created the obligation, key assumptions, and main risks.</small>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Current Balance</label>
                        <input type="text" class="form-control" value="{{ number_format($provision->current_balance, 2) }}" readonly>
                        <small class="text-muted">Current provision balance (read-only). Use remeasurement to change.</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Currency</label>
                        <input type="text" name="currency_code" value="{{ old('currency_code', $provision->currency_code) }}" maxlength="3" class="form-control text-uppercase" required>
                        <small class="text-muted">Three‑letter ISO code (e.g. TZS, USD, EUR).</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">FX Rate at Recognition</label>
                        <input type="number" step="0.000001" min="0.000001" name="fx_rate_at_creation" value="{{ old('fx_rate_at_creation', $provision->fx_rate_at_creation) }}" class="form-control">
                        <small class="text-muted">Spot/appropriate rate on the recognition date (home currency per 1 unit).</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Original Estimate</label>
                        <input type="text" class="form-control" value="{{ number_format($provision->original_estimate, 2) }}" readonly>
                        <small class="text-muted">Original estimate at recognition (read-only).</small>
                    </div>
                </div>

                <!-- Discounting Fields (Conditional) -->
                <div class="row mb-3" id="discounting-fields-group">
                    <div class="col-md-4">
                        <label class="form-label">Discounting Required?</label>
                        <select name="is_discounted" id="is_discounted" class="form-select select2-single">
                            <option value="0" {{ !$provision->is_discounted ? 'selected' : '' }}>No (immaterial)</option>
                            <option value="1" {{ $provision->is_discounted ? 'selected' : '' }}>Yes (material time value)</option>
                        </select>
                        <small class="text-muted">Select "Yes" if the time value of money is material (long‑term obligations).</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Discount Rate (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="discount_rate" id="discount_rate" class="form-control" value="{{ old('discount_rate', $provision->discount_rate) }}">
                        <small class="text-muted">Pre‑tax rate reflecting current market assessment of the risk specific to the obligation.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Expected Settlement Date</label>
                        <input type="date" name="expected_settlement_date" class="form-control" value="{{ old('expected_settlement_date', $provision->expected_settlement_date?->format('Y-m-d')) }}">
                        <small class="text-muted">Best estimate of when the obligation will be settled or expire.</small>
                    </div>
                </div>

                <!-- Asset Linkage Fields (Conditional - Environmental only) -->
                <div class="row mb-3" id="asset-linkage-fields-group" style="display:none;">
                    <div class="col-md-12">
                        <h6 class="mb-2"><i class="bx bx-building me-2"></i>Asset Linkage (Environmental Provisions)</h6>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Related Asset ID</label>
                        <input type="number" name="related_asset_id" class="form-control" value="{{ old('related_asset_id', $provision->related_asset_id) }}" placeholder="Asset ID">
                        <small class="text-muted">Link to the PPE asset that gives rise to the restoration obligation.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Asset Category</label>
                        <input type="text" name="asset_category" class="form-control" value="{{ old('asset_category', $provision->asset_category) }}" placeholder="e.g. Mining Site, Oil Platform">
                        <small class="text-muted">Category or type of asset requiring restoration.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Capitalise into PPE?</label>
                        <select name="is_capitalised" class="form-select select2-single">
                            <option value="0" {{ !$provision->is_capitalised ? 'selected' : '' }}>No (Expense)</option>
                            <option value="1" {{ $provision->is_capitalised ? 'selected' : '' }}>Yes (Capitalise)</option>
                        </select>
                        <small class="text-muted">Environmental provisions are typically capitalised into the related asset (Dr Asset / Cr Provision).</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Depreciation Start Date</label>
                        <input type="date" name="depreciation_start_date" class="form-control" value="{{ old('depreciation_start_date', $provision->depreciation_start_date?->format('Y-m-d')) }}">
                        <small class="text-muted">Reference date for depreciation tracking (read-only reference).</small>
                    </div>
                </div>

                <hr>

                <h5 class="mb-3">Accounting Mapping (Double Entry)</h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Expense Account (Dr)</label>
                        <select name="expense_account_id" class="form-select select2-single" required>
                            @foreach($expenseAccounts as $account)
                                <option value="{{ $account->id }}" {{ $provision->expense_account_id == $account->id ? 'selected' : '' }}>
                                    {{ $account->account_code }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Dr Expense on initial recognition and increases. For Environmental provisions, this may be an Asset account.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Provision Account (Cr)</label>
                        <select name="provision_account_id" class="form-select select2-single" required>
                            @foreach($provisionAccounts as $account)
                                <option value="{{ $account->id }}" {{ $provision->provision_account_id == $account->id ? 'selected' : '' }}>
                                    {{ $account->account_code }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Cr Liability for the provision.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Unwinding (Finance Cost) Account</label>
                        <select name="unwinding_account_id" class="form-select select2-single">
                            <option value="">-- Optional --</option>
                            @foreach($financeCostAccounts as $account)
                                <option value="{{ $account->id }}" {{ $provision->unwinding_account_id == $account->id ? 'selected' : '' }}>
                                    {{ $account->account_code }} - {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Dr Finance Cost, Cr Provision for discount unwinding.</small>
                    </div>
                </div>

                <!-- Hidden field for computation assumptions -->
                <input type="hidden" name="computation_assumptions" id="computation_assumptions" value="{{ json_encode($provision->computation_assumptions ?? []) }}">
                <input type="hidden" name="undiscounted_amount" id="undiscounted_amount" value="{{ $provision->undiscounted_amount ?? '' }}">
                <input type="hidden" name="discount_rate_id" id="discount_rate_id" value="{{ $provision->discount_rate_id ?? '' }}">
            </div>

            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bx bx-save"></i> Update Provision
                </button>
                <a href="{{ route('accounting.provisions.show', $provision->encoded_id) }}" class="btn btn-secondary">
                    <i class="bx bx-x"></i> Cancel
                </a>
            </div>
        </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    const templates = @json($provisionTemplates);
    const computationServices = @json($computationServices ?? []);
    const activeDiscountRates = @json($activeDiscountRates ?? []);
    const provision = @json($provision);
    const existingAssumptions = @json($provision->computation_assumptions ?? []);

    // Initialize field visibility based on current provision type
    function initializeFieldVisibility() {
        const provisionType = $('#provision_type').val();
        if (!provisionType || !templates[provisionType]) return;

        const template = templates[provisionType];
        const visibility = template.field_visibility || {};

        // Show/hide discounting fields
        if (visibility.discounting_fields === true) {
            $('#discounting-fields-group').show();
        } else {
            $('#discounting-fields-group').hide();
        }

        // Show/hide asset linkage fields
        if (provisionType === 'environmental' && visibility.asset_linkage_fields === true) {
            $('#asset-linkage-fields-group').show();
        } else {
            $('#asset-linkage-fields-group').hide();
        }

        // Show/hide probability fields
        if (visibility.probability_fields === true) {
            $('#probability-fields-group').show();
        } else {
            $('#probability-fields-group').hide();
        }

        // Show/hide computation panel
        if (visibility.computation_panel === true && computationServices[provisionType]?.enabled) {
            renderComputationPanel(provisionType, existingAssumptions);
            $('#computation-panel').show();
        } else {
            $('#computation-panel').hide();
        }
    }

    // Template selection handler
    $('#provision_template').on('change', function() {
        const templateKey = $(this).val();
        if (!templateKey || !templates[templateKey]) return;

        const template = templates[templateKey];
        const visibility = template.field_visibility || {};

        // Update template details display
        $('#tpl-label').text(template.label);
        $('#tpl-description').text(template.description);
        $('#tpl-gl-pattern').text(template.gl_pattern);
        $('#tpl-notes').text(template.notes);
        $('#template-details').show();

        // Set provision type
        $('#provision_type').val(templateKey).trigger('change');
    });

    // Provision type change handler
    $('#provision_type').on('change', function() {
        initializeFieldVisibility();
    });

    // Render computation panel
    function renderComputationPanel(provisionType, existingValues = {}) {
        const service = computationServices[provisionType];
        if (!service || !service.enabled) return;

        const inputs = service.input_fields || [];
        let html = '<div class="row">';

        inputs.forEach(function(field) {
            const fieldClass = field.class || '';
            const readonly = field.readonly ? 'readonly' : '';
            const existingValue = existingValues[field.name] || '';
            
            html += `<div class="col-md-6 mb-3">`;
            html += `<label class="form-label ${fieldClass}">${field.label}</label>`;
            
            if (field.type === 'select') {
                html += `<select name="computation_${field.name}" class="form-control computation-input" ${field.required ? 'required' : ''}>`;
                if (field.options) {
                    Object.entries(field.options).forEach(([value, label]) => {
                        const selected = existingValue == value ? 'selected' : '';
                        html += `<option value="${value}" ${selected}>${label}</option>`;
                    });
                }
                html += `</select>`;
            } else {
                html += `<input type="${field.type}" 
                    name="computation_${field.name}" 
                    class="form-control computation-input ${fieldClass}" 
                    value="${existingValue}"
                    ${field.required ? 'required' : ''} 
                    ${field.step ? `step="${field.step}"` : ''}
                    ${field.min !== undefined ? `min="${field.min}"` : ''}
                    ${field.max !== undefined ? `max="${field.max}"` : ''}
                    ${readonly}
                    placeholder="${field.help_text || ''}">`;
            }
            
            if (field.help_text) {
                html += `<small class="text-muted d-block mt-1">${field.help_text}</small>`;
            }
            html += `</div>`;
        });

        html += '</div>';
        $('#computation-inputs').html(html);
    }

    // Calculate provision amount
    $('#calculate-provision').on('click', function() {
        const provisionType = $('#provision_type').val();
        if (!provisionType) {
            alert('Please select a provision type first.');
            return;
        }

        const service = computationServices[provisionType];
        if (!service || !service.enabled) {
            alert('Computation not available for this provision type.');
            return;
        }

        // Collect computation inputs
        const inputs = {};
        $('.computation-input').each(function() {
            const name = $(this).attr('name').replace('computation_', '');
            const value = $(this).val();
            if (value) {
                inputs[name] = value;
            }
        });

        // Call computation API
        $.ajax({
            url: '{{ route("accounting.provisions.compute") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                provision_type: provisionType,
                inputs: inputs
            },
            success: function(response) {
                if (response.errors && response.errors.length > 0) {
                    $('#calculation-result').html('<span class="text-danger">' + response.errors.join(', ') + '</span>');
                } else {
                    $('#computation_assumptions').val(JSON.stringify(response.assumptions));
                    if (response.undiscounted_amount) {
                        $('#undiscounted_amount').val(response.undiscounted_amount);
                    }
                    $('#calculation-result').html(
                        '<span class="text-success fw-bold">Recalculated: ' + 
                        parseFloat(response.amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + 
                        ' (Note: Amount changes require remeasurement, not direct edit)</span>'
                    );
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Calculation failed';
                $('#calculation-result').html('<span class="text-danger">' + message + '</span>');
            }
        });
    });

    // Discount rate change handler
    $('#is_discounted').on('change', function() {
        if ($(this).val() === '1') {
            $('#discount_rate').closest('.col-md-4').show();
            $('input[name="expected_settlement_date"]').closest('.col-md-4').show();
        } else {
            $('#discount_rate').closest('.col-md-4').hide();
            $('input[name="expected_settlement_date"]').closest('.col-md-4').hide();
        }
    });

    // Initialize on page load
    initializeFieldVisibility();
    
    // Set template dropdown to match current provision type
    $('#provision_template').val('{{ $provision->provision_type }}').trigger('change');
});
</script>
@endpush

