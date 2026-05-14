@extends('layouts.main')

@section('title', 'Create Purchase Requisition')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchase Management', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Requisitions', 'url' => route('purchases.requisitions.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <h6 class="mb-0 text-uppercase">CREATE PURCHASE REQUISITION</h6>
        <hr />

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('purchases.requisitions.store') }}">
                    @csrf

                    {{-- Header --}}
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Department (Cost Center) <span class="text-danger">*</span></label>
                            <select name="department_id" id="department_id" class="form-select select2-single" required>
                                <option value="">Select Department</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" @selected(old('department_id') == $dept->id)>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Required for budget validation</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Budget <span class="text-danger">*</span></label>
                            <select name="budget_id" id="budget_id" class="form-select select2-single" required>
                                <option value="">Select Budget</option>
                                @foreach($budgets ?? [] as $budget)
                                    <option value="{{ $budget->id }}" 
                                            @selected(old('budget_id', $defaultBudget->id ?? null) == $budget->id)>
                                        {{ $budget->name ?? 'Budget ' . $budget->year }} ({{ $budget->year }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Required for budget validation</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Required Date <span class="text-danger">*</span></label>
                            <input type="date"
                                   name="required_date"
                                   class="form-control"
                                   value="{{ old('required_date', now()->toDateString()) }}"
                                   required>
                        </div>
                        <div class="col-md-3">
                            @php
                                $functionalCurrency = \App\Models\SystemSetting::getValue(
                                    'functional_currency',
                                    auth()->user()->company->functional_currency ?? 'TZS'
                                );
                                $currencies = \App\Models\Currency::where('company_id', auth()->user()->company_id)
                                    ->where('is_active', true)
                                    ->orderBy('currency_code')
                                    ->get();

                                if ($currencies->isEmpty()) {
                                    $supportedCurrencies = app(\App\Services\ExchangeRateService::class)->getSupportedCurrencies();
                                    $currencies = collect($supportedCurrencies)->map(function($name, $code) {
                                        return (object)['currency_code' => $code, 'currency_name' => $name];
                                    });
                                }
                            @endphp
                            <label class="form-label">Currency <span class="text-danger">*</span></label>
                            <select class="form-select select2-single" id="currency" name="currency" required>
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->currency_code }}"
                                        {{ old('currency', $functionalCurrency) == $currency->currency_code ? 'selected' : '' }}>
                                        {{ $currency->currency_name ?? $currency->currency_code }} ({{ $currency->currency_code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Exchange Rate</label>
                            <div class="input-group">
                                <input type="number"
                                       class="form-control"
                                       id="exchange_rate"
                                       name="exchange_rate"
                                       value="{{ old('exchange_rate', '1.000000') }}"
                                       step="0.000001"
                                       min="0.000001"
                                       placeholder="1.000000">
                                <button type="button" class="btn btn-outline-secondary" id="fetch-rate-btn">
                                    <i class="bx bx-refresh"></i>
                                </button>
                            </div>
                            <small class="text-muted">Rate relative to {{ $functionalCurrency }}</small>
                            <div id="rate-info" class="mt-1" style="display: none;">
                                <small class="text-info">
                                    <i class="bx bx-info-circle"></i>
                                    <span id="rate-source">Rate fetched from API</span>
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <label class="form-label">Justification</label>
                            <textarea name="justification"
                                      class="form-control"
                                      rows="2"
                                      placeholder="Reason for this requisition...">{{ old('justification') }}</textarea>
                        </div>
                    </div>

                    {{-- Lines --}}
                    <div class="card mt-3">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Requisition Lines</h6>
                                <button type="button" class="btn btn-primary btn-sm" id="add-line">
                                    <i class="bx bx-plus me-1"></i>Add Line
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table" id="lines-table">
                                    <thead>
                                        <tr>
                                            <th width="20%">Item</th>
                                            <th width="12%">Quantity</th>
                                            <th width="12%">Unit Price</th>
                                            <th width="15%">GL Account <span class="text-danger">*</span></th>
                                            <th width="12%">Tax Group</th>
                                            <th width="12%">Total</th>
                                            <th width="10%">Budget Status</th>
                                            <th width="7%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="lines-tbody">
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="5" class="text-end"><strong>Total Amount:</strong></td>
                                            <td><strong id="pr-total-amount">0.00</strong></td>
                                            <td colspan="2"></td>
                                        </tr>
                                        <input type="hidden" name="total_amount" id="pr-total-amount-input" value="0">
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="{{ route('purchases.requisitions.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Save Draft
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Add Line Modal with item selection (inventory / asset) --}}
<div class="modal fade" id="prItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Requisition Line</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Item Type <span class="text-danger">*</span></label>
                        <select id="modal_item_type" class="form-select">
                            <option value="inventory" selected>Inventory Item</option>
                            <option value="service">Service</option>
                        </select>
                    </div>
                </div>

                {{-- Inventory selection --}}
                <div id="inventory-item-section" class="mb-3">
                    <label class="form-label">Select Inventory Item</label>
                    <select class="form-select select2-modal" id="modal_item_id">
                        <option value="">Choose an item...</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}"
                                    data-name="{{ $item->name }}"
                                    data-code="{{ $item->code }}"
                                    data-price="{{ $item->resolved_cost_price ?? $item->cost_price }}"
                                    data-unit="{{ $item->unit_of_measure }}">
                                {{ $item->name }} ({{ $item->code }}) - Cost: {{ number_format($item->resolved_cost_price ?? $item->cost_price, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Common fields --}}
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <input type="text" id="modal_description" class="form-control" placeholder="Item description">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">UOM</label>
                        <input type="text" id="modal_uom" class="form-control" placeholder="PCS, UNIT, etc.">
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-3">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" id="modal_quantity" class="form-control" value="1" min="0.01" step="0.01">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Unit Price</label>
                        <input type="number" id="modal_unit_price" class="form-control" value="0" step="0.01" min="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Line Total</label>
                        <div class="border rounded p-2 bg-light">
                            <span class="fw-bold" id="modal_line_total">0.00</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tax Group</label>
                        <select id="modal_tax_group_id" class="form-select select2-modal">
                            <option value="">No Tax</option>
                            @foreach($taxGroups ?? [] as $taxGroup)
                                <option value="{{ $taxGroup->id }}"
                                        data-vat-type="{{ $taxGroup->vat_type }}"
                                        data-vat-rate="{{ $taxGroup->vat_rate }}">
                                    {{ $taxGroup->name }} ({{ strtoupper($taxGroup->vat_type) }} @if($taxGroup->vat_rate){{ $taxGroup->vat_rate }}% @endif)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-12">
                        <label class="form-label">GL Account (Cost Center) <span class="text-danger">*</span></label>
                        <select id="modal_gl_account_id" class="form-select select2-modal" required>
                            <option value="">Select GL Account</option>
                            @foreach($chartAccounts ?? [] as $account)
                                <option value="{{ $account->id }}"
                                        data-code="{{ $account->account_code }}"
                                        data-name="{{ $account->account_name }}">
                                    [{{ $account->account_code }}] {{ $account->account_name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Required for budget validation and accounting</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="modal_add_line_btn">Add Line</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@php
    // Default GL accounts per requisition line type, re-using existing system settings
    // Inventory: uses inventory_default_inventory_account (UI label: default_inventory_account)
    $prInventoryGl = \App\Models\SystemSetting::getValue('inventory_default_inventory_account');
@endphp
<script nonce="{{ $cspNonce ?? '' }}">
    function recalcTotals() {
        let total = 0;
        $('#lines-table tbody tr').each(function () {
            const val = parseFloat($(this).find('.line-total').data('value') || 0);
            total += val;
        });
        $('#pr-total-amount').text(total.toFixed(2));
        $('#pr-total-amount-input').val(total.toFixed(2));
    }

    function recalcModalTotal() {
        const qty = parseFloat($('#modal_quantity').val()) || 0;
        const price = parseFloat($('#modal_unit_price').val()) || 0;
        $('#modal_line_total').text((qty * price).toFixed(2));
    }

    $(function () {
        const defaultGlByType = {
            inventory: '{{ $prInventoryGl }}',
            service: '',
        };

        function applyDefaultGlAccountForType(type) {
            const defaultId = defaultGlByType[type] || '';
            if (defaultId) {
                const $select = $('#modal_gl_account_id');
                if ($select.find(`option[value="${defaultId}"]`).length) {
                    $select.val(defaultId).trigger('change');
                }
            }
        }
        $('.select2-single').select2({ theme: 'bootstrap-5', width: '100%' });
        $('.select2-modal').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $('#prItemModal')
        });

        $('#add-line').on('click', function () {
            $('#modal_item_type').val('inventory').trigger('change');
            $('#modal_description').val('');
            $('#modal_quantity').val(1);
            $('#modal_uom').val('');
            $('#modal_unit_price').val(0);
            $('#modal_line_total').text('0.00');
            $('#modal_gl_account_id').val('').trigger('change');
            $('#modal_tax_group_id').val('').trigger('change');
            $('#modal_item_id').val('').trigger('change');

            // Apply default GL account for inventory (if configured)
            applyDefaultGlAccountForType('inventory');

            new bootstrap.Modal(document.getElementById('prItemModal')).show();
        });

        $('#modal_item_type').on('change', function () {
            const type = $(this).val();
            if (type === 'inventory') {
                $('#inventory-item-section').show();
                $('#modal_uom').val('');
                $('#modal_item_id').val('').trigger('change');
            } else {
                $('#inventory-item-section').hide();
                $('#modal_item_id').val('').trigger('change');
            }
            applyDefaultGlAccountForType(type);
        }).trigger('change');

        $('#modal_item_id').on('change', function () {
            const opt = $(this).find('option:selected');
            if (!opt.val()) return;
            const name = opt.data('name') || '';
            const code = opt.data('code') || '';
            const price = parseFloat(opt.data('price')) || 0;
            const unit = opt.data('unit') || '';
            $('#modal_description').val(name + (code ? ' (' + code + ')' : ''));
            $('#modal_uom').val(unit);
            $('#modal_unit_price').val(price.toFixed(2));
            recalcModalTotal();
        });

        $('#modal_quantity, #modal_unit_price').on('input', recalcModalTotal);

        $('#modal_add_line_btn').on('click', function () {
            const type = $('#modal_item_type').val();
            const description = ($('#modal_description').val() || '').trim();
            const qty = parseFloat($('#modal_quantity').val()) || 0;
            const uom = ($('#modal_uom').val() || '').trim();
            const unitPrice = parseFloat($('#modal_unit_price').val()) || 0;
            let inventoryId = null;

            if (!description || qty <= 0) {
                alert('Please provide description and a valid quantity.');
                return;
            }

            if (type === 'inventory') {
                inventoryId = $('#modal_item_id').val();
                if (!inventoryId) {
                    alert('Please select an inventory item.');
                    return;
                }
            }

            // Get GL account and tax group
            const glAccountId = $('#modal_gl_account_id').val();
            const glAccountText = $('#modal_gl_account_id option:selected').text();
            const taxGroupId = $('#modal_tax_group_id').val();
            const taxGroupText = $('#modal_tax_group_id option:selected').text() || 'No Tax';

            if (!glAccountId) {
                alert('Please select a GL Account (Cost Center) for this line item.');
                return;
            }

            const lineTotal = qty * unitPrice;
            const index = $('#lines-table tbody tr').length;

            const typeLabel = type === 'service' ? 'Service' : 'Inventory';

            const safeDesc = description.replace(/"/g, '&quot;');

            let refInputs = '';
            if (inventoryId) {
                refInputs += `<input type="hidden" name="lines[${index}][inventory_item_id]" value="${inventoryId}">`;
            }

            const row = `
                <tr data-row-id="${index}">
                    <td>
                        <input type="hidden" name="lines[${index}][item_type]" value="${type}">
                        ${refInputs}
                        <div class="fw-bold">
                            ${typeLabel === 'Service'
                                ? '<span class="badge bg-secondary me-1">Service</span>'
                                : '<span class="badge bg-success me-1">Inventory</span>'}
                            ${safeDesc}
                        </div>
                    </td>
                    <td>
                        <input type="number" class="form-control pr-line-qty"
                               name="lines[${index}][quantity]" value="${qty.toFixed(2)}"
                               step="0.01" min="0.01" data-row="${index}">
                    </td>
                    <td>
                        <input type="number" class="form-control pr-line-price"
                               name="lines[${index}][unit_price_estimate]" value="${unitPrice.toFixed(2)}"
                               step="0.01" min="0" data-row="${index}">
                    </td>
                    <td>
                        <select name="lines[${index}][gl_account_id]" class="form-select pr-line-gl-account" required data-row="${index}">
                            <option value="">Select Account</option>
                            @foreach($chartAccounts ?? [] as $account)
                                <option value="{{ $account->id }}">[{{ $account->account_code }}] {{ $account->account_name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="lines[${index}][tax_group_id]" class="form-select pr-line-tax-group" data-row="${index}">
                            <option value="">No Tax</option>
                            @foreach($taxGroups ?? [] as $taxGroup)
                                <option value="{{ $taxGroup->id }}">{{ $taxGroup->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <span class="pr-line-total">${lineTotal.toFixed(2)}</span>
                        <input type="hidden" class="line-total" data-value="${lineTotal}"
                               name="lines[${index}][line_total_estimate]" value="${lineTotal.toFixed(2)}">
                    </td>
                    <td>
                        <span class="budget-status-badge" data-row="${index}">
                            <small class="text-muted">Check Budget</small>
                        </span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-line">
                            <i class="bx bx-trash"></i>
                        </button>
                    </td>
                </tr>
            `;

            $('#lines-table tbody').append(row);
            
            // Set selected values for GL account and tax group
            const newRow = $(`tr[data-row-id="${index}"]`);
            newRow.find('.pr-line-gl-account').val(glAccountId);
            newRow.find('.pr-line-tax-group').val(taxGroupId || '');
            
            // Initialize Select2 for new row dropdowns
            newRow.find('.pr-line-gl-account, .pr-line-tax-group').select2({ 
                theme: 'bootstrap-5', 
                width: '100%',
                dropdownParent: $('body')
            });
            
            recalcTotals();
            
            // Check budget for this line
            checkBudgetForLine(index, glAccountId, lineTotal);
            
            bootstrap.Modal.getInstance(document.getElementById('prItemModal')).hide();
        });

        $('#lines-table').on('click', '.btn-remove-line', function () {
            $(this).closest('tr').remove();
            recalcTotals();
        });

        // Live recalc when qty or price change (like invoice)
        $('#lines-table').on('input', '.pr-line-qty, .pr-line-price', function () {
            const row = $(this).data('row');
            const tr = $(`tr[data-row-id="${row}"]`);
            const qty = parseFloat(tr.find('.pr-line-qty').val()) || 0;
            const price = parseFloat(tr.find('.pr-line-price').val()) || 0;
            const total = qty * price;
            tr.find('.pr-line-total').text(total.toFixed(2));
            tr.find('.line-total').data('value', total).val(total.toFixed(2));
            recalcTotals();
            
            // Re-check budget when amount changes
            const glAccountId = tr.find('.pr-line-gl-account').val();
            if (glAccountId) {
                checkBudgetForLine(row, glAccountId, total);
            }
        });

        // Check budget when GL account changes (event delegation for dynamically added rows)
        $(document).on('change', '.pr-line-gl-account', function () {
            const row = $(this).data('row');
            const tr = $(`tr[data-row-id="${row}"]`);
            const glAccountId = $(this).val();
            const total = parseFloat(tr.find('.line-total').data('value') || 0);
            if (glAccountId) {
                checkBudgetForLine(row, glAccountId, total);
            } else {
                tr.find('.budget-status-badge').html('<small class="text-muted">Select GL Account</small>');
            }
        });

        // Budget validation function
        function checkBudgetForLine(rowIndex, glAccountId, lineAmount) {
            const budgetId = $('#budget_id').val();
            if (!budgetId || !glAccountId) {
                return;
            }

            const tr = $(`tr[data-row-id="${rowIndex}"]`);
            const badge = tr.find('.budget-status-badge');
            badge.html('<small class="text-info"><i class="bx bx-loader bx-spin"></i> Checking...</small>');

            $.ajax({
                url: '{{ route("purchases.requisitions.check-budget") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    budget_id: budgetId,
                    gl_account_id: glAccountId,
                    amount: lineAmount
                },
                success: function(response) {
                    if (response.success) {
                        if (response.status === 'ok') {
                            badge.html(`<small class="text-success"><i class="bx bx-check-circle"></i> Within Budget</small>`);
                        } else if (response.status === 'over_budget_warning') {
                            badge.html(`<small class="text-warning"><i class="bx bx-error-circle"></i> Over Budget (Tolerance)</small>`);
                        } else {
                            badge.html(`<small class="text-danger"><i class="bx bx-x-circle"></i> ${response.message || 'Over Budget'}</small>`);
                        }
                    } else {
                        badge.html(`<small class="text-muted">${response.message || 'Unable to check'}</small>`);
                    }
                },
                error: function() {
                    badge.html('<small class="text-muted">Check failed</small>');
                }
            });
        }

        // Fetch exchange rate using same endpoint as purchase invoice
        function fetchExchangeRate(currency = null) {
            const functionalCurrency = '{{ $functionalCurrency }}';
            currency = currency || $('#currency').val();

            if (!currency || currency === functionalCurrency) {
                $('#exchange_rate').val('1.000000');
                $('#rate-info').hide();
                return;
            }

            const btn = $('#fetch-rate-btn');
            const originalHtml = btn.html();
            const rateInput = $('#exchange_rate');

            btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i>');
            rateInput.prop('disabled', true);

            $.ajax({
                url: '{{ route("accounting.fx-rates.get-rate") }}',
                method: 'GET',
                data: {
                    from_currency: currency,
                    to_currency: functionalCurrency,
                    date: new Date().toISOString().split('T')[0],
                    rate_type: 'spot'
                },
                success: function (response) {
                    if (response.success && response.rate) {
                        const rate = parseFloat(response.rate);
                        rateInput.val(rate.toFixed(6));
                        $('#rate-source').text(`Rate fetched: 1 ${currency} = ${rate.toFixed(6)} ${functionalCurrency}`);
                        $('#rate-info').show();
                    } else {
                        alert(response.message || 'Failed to fetch exchange rate.');
                    }
                },
                error: function () {
                    alert('An error occurred while fetching the exchange rate.');
                },
                complete: function () {
                    btn.prop('disabled', false).html(originalHtml);
                    rateInput.prop('disabled', false);
                }
            });
        }

        // Button click uses same fetch function as invoices
        $('#fetch-rate-btn').on('click', function () {
            fetchExchangeRate();
        });

        // Live behavior: when currency changes, auto refresh rate
        $('#currency').on('change', function () {
            fetchExchangeRate($(this).val());
        });

        // Re-check all budgets when budget selection changes
        $('#budget_id').on('change', function() {
            $('#lines-table tbody tr').each(function() {
                const row = $(this).data('row-id');
                const glAccountId = $(this).find('.pr-line-gl-account').val();
                const total = parseFloat($(this).find('.line-total').data('value') || 0);
                if (glAccountId && total > 0) {
                    checkBudgetForLine(row, glAccountId, total);
                }
            });
        });

        // Form validation before submit
        $('form').on('submit', function(e) {
            let hasErrors = false;
            const errors = [];

            // Check if budget is selected
            if (!$('#budget_id').val()) {
                errors.push('Please select a budget');
                hasErrors = true;
            }

            // Check if department is selected
            if (!$('#department_id').val()) {
                errors.push('Please select a department (cost center)');
                hasErrors = true;
            }

            // Check each line has GL account
            $('#lines-table tbody tr').each(function() {
                const glAccountId = $(this).find('.pr-line-gl-account').val();
                if (!glAccountId) {
                    errors.push('All line items must have a GL Account selected');
                    hasErrors = true;
                    return false;
                }
            });

            if (hasErrors) {
                e.preventDefault();
                alert('Validation Errors:\\n' + errors.join('\\n'));
                return false;
            }
        });
    });
</script>
@endpush


