@extends('layouts.main')
@section('title', 'New Provision (IAS 37)')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
                ['label' => 'Provisions (IAS 37)', 'url' => route('accounting.provisions.index'), 'icon' => 'bx bx-shield-quarter'],
                ['label' => 'New Provision', 'url' => '#', 'icon' => 'bx bx-plus']
            ]" />
            <a href="{{ route('accounting.provisions.index') }}" class="btn btn-secondary">
                Back to List
            </a>
        </div>

        <h6 class="mb-0 text-uppercase">NEW PROVISION (IAS 37)</h6>
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
        <form method="POST" action="{{ route('accounting.provisions.store') }}" id="provisionForm">
            @csrf
            <div class="card-body">
                <h5 class="mb-3">Provision Template</h5>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Template</label>
                        <select id="provision_template" class="form-select select2-single" data-placeholder="Choose template">
                            <option value="">-- Select Template --</option>
                            @foreach($provisionTemplates as $key => $tpl)
                                <option value="{{ $key }}" data-visibility='@json($tpl['field_visibility'] ?? [])'>{{ $tpl['label'] }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Select a template to pre-orient the provision according to IAS 37 type and typical double entry.</small>
                    </div>
                    <div class="col-md-8">
                        <div id="template-details" class="alert alert-secondary mb-0" style="display:none;">
                            <h6 class="fw-bold mb-1" id="tpl-label"></h6>
                            <p class="mb-1" id="tpl-description"></p>
                            <p class="mb-1"><strong>Typical double entry:</strong> <span id="tpl-gl-pattern"></span></p>
                            <p class="mb-0"><strong>Notes:</strong> <span id="tpl-notes"></span></p>
                        </div>
                    </div>
                </div>

                <h5 class="mb-3">Obligation & Probability (IAS 37 Gatekeeper)</h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Provision Type</label>
                        <select name="provision_type" id="provision_type" class="form-select select2-single" required>
                            <option value="">-- Select Type --</option>
                            <option value="legal_claim">Legal Claim</option>
                            <option value="warranty">Warranty</option>
                            <option value="onerous_contract">Onerous Contract</option>
                            <option value="environmental">Environmental Restoration</option>
                            <option value="restructuring">Restructuring</option>
                            <option value="employee_benefit">Employee Benefit</option>
                            <option value="other">Other</option>
                        </select>
                        <small class="text-muted">Classify the obligation (e.g. lawsuit, warranty, onerous contract).</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Present Obligation Type</label>
                        <select name="present_obligation_type" class="form-select select2-single">
                            <option value="">-- Select --</option>
                            <option value="legal">Legal</option>
                            <option value="constructive">Constructive</option>
                        </select>
                        <small class="text-muted">Legal: from law/contract. Constructive: from past practice or public commitments.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Present Obligation Exists?</label>
                        <select name="has_present_obligation" class="form-select select2-single" required>
                            <option value="1">Yes</option>
                            <option value="0">No (Contingent only)</option>
                        </select>
                        <small class="text-muted">Select "Yes" only if a present obligation exists at reporting date.</small>
                    </div>
                </div>

                <div class="row mb-3" id="probability-fields-group">
                    <div class="col-md-4">
                        <label class="form-label">Probability of Outflow</label>
                        <select name="probability" class="form-select select2-single" required>
                            <option value="remote">Remote</option>
                            <option value="possible">Possible</option>
                            <option value="probable" selected>Probable (> 50%)</option>
                            <option value="virtually_certain">Virtually Certain</option>
                        </select>
                        <small class="text-muted">Provision is recognised only if outflow is at least "Probable".</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Probability (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="probability_percent" class="form-control">
                        <small class="text-muted">Optional quantitative estimate of likelihood (for disclosures).</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estimate Method</label>
                        <select name="estimate_method" class="form-select select2-single" required>
                            <option value="best_estimate" selected>Best Estimate</option>
                            <option value="expected_value">Expected Value (many outcomes)</option>
                            <option value="most_likely_outcome">Most Likely Outcome (single obligation)</option>
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
                            <i class="bx bx-calculate"></i> Calculate Provision Amount
                        </button>
                        <span id="calculation-result" class="ms-3"></span>
                    </div>
                </div>

                <h5 class="mb-3">Measurement & Discounting</h5>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required>
                        <small class="text-muted">Short, meaningful name (e.g. "Environmental restoration – Site A").</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Branch</label>
                        <select name="branch_id" class="form-select select2-single">
                            <option value="">Use current branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">If blank, the current user/ session branch will be used.</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description / Nature of Obligation</label>
                    <textarea name="description" class="form-control" rows="3" required></textarea>
                    <small class="text-muted">Describe what created the obligation, key assumptions, and main risks.</small>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Amount (Foreign)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="provision_amount" class="form-control">
                        <small class="text-muted">Best estimate of the obligation in the original currency. Can be calculated using the computation panel above.</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Currency</label>
                        <input type="text" name="currency_code" value="TZS" maxlength="3" class="form-control text-uppercase" required>
                        <small class="text-muted">Three‑letter ISO code (e.g. TZS, USD, EUR).</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">FX Rate at Recognition</label>
                        <input type="number" step="0.000001" min="0.000001" name="fx_rate_at_creation" value="1" class="form-control">
                        <small class="text-muted">Spot/appropriate rate on the recognition date (home currency per 1 unit).</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Movement Date</label>
                        <input type="date" name="movement_date" value="{{ now()->toDateString() }}" class="form-control">
                        <small class="text-muted">Date on which the provision is first recognised in the books.</small>
                    </div>
                </div>

                <!-- Discounting Fields (Conditional) -->
                <div class="row mb-3" id="discounting-fields-group">
                    <div class="col-md-4">
                        <label class="form-label">Discounting Required?</label>
                        <select name="is_discounted" id="is_discounted" class="form-select select2-single">
                            <option value="0">No (immaterial)</option>
                            <option value="1">Yes (material time value)</option>
                        </select>
                        <small class="text-muted">Select "Yes" if the time value of money is material (long‑term obligations).</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Discount Rate (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="discount_rate" id="discount_rate" class="form-control">
                        <small class="text-muted">Pre‑tax rate reflecting current market assessment of the risk specific to the obligation.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Expected Settlement Date</label>
                        <input type="date" name="expected_settlement_date" class="form-control">
                        <small class="text-muted">Best estimate of when the obligation will be settled or expire.</small>
                    </div>
                </div>

                <hr>

                <h5 class="mb-3">Accounting Mapping (Double Entry)</h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Expense Account (Dr)</label>
                        <select name="expense_account_id" class="form-select select2-single" required>
                            @foreach($expenseAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Dr Expense on initial recognition and increases. For Environmental provisions, this may be an Asset account.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Provision Account (Cr)</label>
                        <select name="provision_account_id" class="form-select select2-single" required>
                            @foreach($provisionAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Cr Liability for the provision.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Unwinding (Finance Cost) Account</label>
                        <select name="unwinding_account_id" class="form-select select2-single">
                            <option value="">-- Optional --</option>
                            @foreach($financeCostAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Dr Finance Cost, Cr Provision for discount unwinding.</small>
                    </div>
                </div>

                <!-- Hidden field for computation assumptions -->
                <input type="hidden" name="computation_assumptions" id="computation_assumptions" value="">
                <input type="hidden" name="undiscounted_amount" id="undiscounted_amount" value="">
                <input type="hidden" name="discount_rate_id" id="discount_rate_id" value="">
            </div>

            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bx bx-save"></i> Save Provision
                </button>
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
        const provisionType = $(this).val();
        if (!provisionType || !templates[provisionType]) return;

        const template = templates[provisionType];
        const visibility = template.field_visibility || {};

        // Show/hide discounting fields
        if (visibility.discounting_fields === true) {
            $('#discounting-fields-group').show();
        } else {
            $('#discounting-fields-group').hide();
            $('#is_discounted').val('0').trigger('change');
        }

        // Show/hide probability fields
        if (visibility.probability_fields === true) {
            $('#probability-fields-group').show();
        } else {
            $('#probability-fields-group').hide();
        }

        // Show/hide computation panel
        if (visibility.computation_panel === true && computationServices[provisionType]?.enabled) {
            renderComputationPanel(provisionType);
            $('#computation-panel').show();
        } else {
            $('#computation-panel').hide();
        }

        // Auto-populate discount rate if discounted and rates available
        if (visibility.discounting_fields === true && activeDiscountRates.length > 0) {
            const rate = activeDiscountRates[0];
            $('#discount_rate').val(rate.rate_percent);
            $('#discount_rate_id').val(rate.id);
        }
    });

    // Render computation panel based on provision type
    function renderComputationPanel(provisionType) {
        const service = computationServices[provisionType];
        if (!service || !service.enabled) return;

        const inputs = service.input_fields || [];
        let html = '<div class="row">';

        inputs.forEach(function(field) {
            const fieldClass = field.class || '';
            const readonly = field.readonly ? 'readonly' : '';
            html += `<div class="col-md-6 mb-3">`;
            html += `<label class="form-label ${fieldClass}">${field.label}</label>`;
            
            if (field.type === 'select') {
                html += `<select name="computation_${field.name}" class="form-control computation-input" ${field.required ? 'required' : ''}>`;
                if (field.options) {
                    Object.entries(field.options).forEach(([value, label]) => {
                        html += `<option value="${value}">${label}</option>`;
                    });
                }
                html += `</select>`;
            } else {
                html += `<input type="${field.type}" 
                    name="computation_${field.name}" 
                    class="form-control computation-input ${fieldClass}" 
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

        // For legal claims, handle method selection
        if (provisionType === 'legal_claim') {
            inputs.method = inputs.method || 'expected_value';
            if (inputs.method === 'expected_value') {
                // Collect outcomes array
                inputs.outcomes = []; // Would need dynamic UI for multiple outcomes
            }
        }

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
                    $('#provision_amount').val(response.amount);
                    $('#computation_assumptions').val(JSON.stringify(response.assumptions));
                    if (response.undiscounted_amount) {
                        $('#undiscounted_amount').val(response.undiscounted_amount);
                    }
                    $('#calculation-result').html(
                        '<span class="text-success fw-bold">Calculated: ' + 
                        parseFloat(response.amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + 
                        '</span>'
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
            $('#expected_settlement_date').closest('.col-md-4').show();
        } else {
            $('#discount_rate').closest('.col-md-4').hide();
            $('#expected_settlement_date').closest('.col-md-4').hide();
        }
    });
});
</script>
@endpush
