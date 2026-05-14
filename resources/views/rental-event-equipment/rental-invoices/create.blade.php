@extends('layouts.main')

@section('title', 'Create Rental Invoice')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Rental Invoices', 'url' => route('rental-event-equipment.rental-invoices.index'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">CREATE RENTAL INVOICE</h6>
        <hr />

        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bx bx-receipt me-2"></i>New Rental Invoice</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('rental-event-equipment.rental-invoices.store') }}" id="invoice-form" onsubmit="return validateDepositAmount()">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="contract_id" class="form-label">Contract <span class="text-danger">*</span></label>
                                <select class="form-select select2-single" id="contract_id" name="contract_id" required>
                                    <option value="">Select Contract</option>
                                    @foreach($contracts as $contract)
                                        <option value="{{ $contract['id'] }}" 
                                            data-encoded-id="{{ $contract['encoded_id'] }}"
                                            {{ $selectedContract && $selectedContract->id == $contract['id'] ? 'selected' : '' }}>
                                            {{ $contract['contract_number'] }} - {{ $contract['customer_name'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Select the contract to invoice</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="invoice_date" class="form-label">Invoice Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="invoice_date" name="invoice_date" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" class="form-control" id="due_date" name="due_date">
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="mb-3"><i class="bx bx-list-ul me-2"></i>Invoice Items</h6>
                            <div id="items-container">
                                <p class="text-muted">Please select a contract to load items.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Deposit Applied Section - Always Visible -->
                    <div class="row mt-3" id="deposit-section" style="display: none;">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="deposit_applied" class="form-label">Deposit Applied <span id="deposit-required-star" class="text-danger" style="display: none;">*</span></label>
                                <input type="number" class="form-control" id="deposit_applied" name="deposit_applied" 
                                    value="0" min="0" step="0.01">
                                <small class="text-muted" id="deposit-available-text">Available: 0.00 TZS</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <th>Subtotal:</th>
                                            <td class="text-end"><span id="invoice-subtotal">0.00</span> TZS</td>
                                        </tr>
                                        <tr>
                                            <th>Deposit Applied:</th>
                                            <td class="text-end"><span id="invoice-deposit">0.00</span> TZS</td>
                                        </tr>
                                        <tr class="border-top">
                                            <th><strong>Total Amount:</strong></th>
                                            <td class="text-end"><strong><span id="invoice-total" class="text-success">0.00</span> TZS</strong></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="{{ route('rental-event-equipment.rental-invoices.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bx bx-check me-1"></i>Create Invoice
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.select2-single').select2({
        placeholder: 'Select',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap-5'
    });

    $('#contract_id').change(function() {
        const contractId = $(this).val();
        const selectedOption = $(this).find('option:selected');
        const encodedId = selectedOption.data('encoded-id') || contractId;
        loadContractItems(encodedId);
    });

    function loadContractItems(contractId) {
        const itemsContainer = $('#items-container');
        
        if (!contractId) {
            itemsContainer.html('<p class="text-muted">Please select a contract to load items.</p>');
            $('#deposit-section').hide();
            $('#deposit_applied').val(0);
            calculateInvoiceTotals();
            return;
        }

        const selectedOption = $('#contract_id option:selected');
        const encodedId = selectedOption.data('encoded-id') || contractId;

        itemsContainer.html('<p class="text-muted">Loading items...</p>');

        $.ajax({
            url: '/rental-event-equipment/rental-invoices/contracts/' + encodedId + '/invoice-data',
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(response) {
                if (response.success) {
                    let itemIndex = 0;
                    let html = `
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6 class="mb-0">Contract: ${response.contract.contract_number}</h6>
                                <small class="text-muted">Customer: ${response.contract.customer_name}</small>
                            </div>
                            <div class="col-md-6 text-end">
                                <strong>Total Deposits Available: <span id="total-deposits-available">${parseFloat(response.total_deposits).toFixed(2)}</span> TZS</strong>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">Select</th>
                                        <th width="25%">Item Description</th>
                                        <th width="10%">Type</th>
                                        <th width="10%">Quantity</th>
                                        <th width="15%">Unit Price <span class="text-danger">*</span></th>
                                        <th width="15%">Line Total</th>
                                        <th width="20%">Notes</th>
                                    </tr>
                                </thead>
                                <tbody id="invoice-items-tbody">
                    `;

                    // Add rental items
                    if (response.rental_items && response.rental_items.length > 0) {
                        response.rental_items.forEach(function(item) {
                            html += createInvoiceItemRow(item, itemIndex, 'equipment');
                            itemIndex++;
                        });
                    }

                    // Add damage/loss charges
                    if (response.damage_charges && response.damage_charges.length > 0) {
                        response.damage_charges.forEach(function(item) {
                            html += createInvoiceItemRow(item, itemIndex, item.item_type);
                            itemIndex++;
                        });
                    }

                    html += `
                                </tbody>
                            </table>
                        </div>
                    `;

                    itemsContainer.html(html);
                    
                    // Update deposit section - always show it
                    const totalDeposits = parseFloat(response.total_deposits || 0);
                    $('#deposit_applied').attr('max', totalDeposits);
                    $('#deposit-available-text').text(`Available: ${totalDeposits.toFixed(2)} TZS`);
                    
                    // Auto-fill deposit if available amount exists
                    if (totalDeposits > 0) {
                        // Calculate subtotal first
                        let subtotal = 0;
                        if (response.rental_items && response.rental_items.length > 0) {
                            response.rental_items.forEach(function(item) {
                                subtotal += parseFloat(item.line_total || 0);
                            });
                        }
                        if (response.damage_charges && response.damage_charges.length > 0) {
                            response.damage_charges.forEach(function(item) {
                                subtotal += parseFloat(item.line_total || 0);
                            });
                        }
                        
                        // Auto-fill with minimum of available deposits and subtotal
                        const autoFillAmount = Math.min(totalDeposits, subtotal);
                        $('#deposit_applied').val(autoFillAmount.toFixed(2));
                        
                        // Make field required if deposits are available
                        $('#deposit_applied').attr('required', 'required');
                        $('#deposit-required-star').show();
                    } else {
                        $('#deposit_applied').val(0);
                        $('#deposit_applied').removeAttr('required');
                        $('#deposit-required-star').hide();
                    }
                    
                    $('#deposit-section').show();
                    
                    // Initialize calculations
                    initializeInvoiceCalculations();
                } else {
                    itemsContainer.html('<div class="alert alert-warning">No data found for this contract.</div>');
                }
            },
            error: function(xhr) {
                console.error('Error loading contract data:', xhr);
                let errorMsg = 'Failed to load contract data.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMsg = xhr.responseJSON.error;
                }
                itemsContainer.html(`<div class="alert alert-danger">${errorMsg}</div>`);
            }
        });
    }

    function createInvoiceItemRow(item, itemIndex, itemType) {
        const typeLabel = itemType === 'equipment' ? 'Equipment' : 
                         itemType === 'damage_charge' ? 'Damage Charge' : 
                         itemType === 'loss_charge' ? 'Loss Charge' : 'Service';
        
        return `
            <tr class="invoice-item-row" data-item-type="${itemType}">
                <td>
                    <div class="form-check">
                        <input class="form-check-input item-checkbox" type="checkbox" 
                            id="item_${itemIndex}" 
                            data-item-index="${itemIndex}"
                            checked>
                    </div>
                    <input type="hidden" name="items[${itemIndex}][equipment_id]" value="${item.equipment_id || ''}">
                    <input type="hidden" name="items[${itemIndex}][item_type]" value="${itemType}">
                </td>
                <td>
                    <input type="text" class="form-control description-input" 
                        name="items[${itemIndex}][description]" 
                        value="${item.description || item.equipment_name || ''}" 
                        required>
                </td>
                <td>
                    <span class="badge bg-${itemType === 'equipment' ? 'primary' : itemType === 'damage_charge' ? 'warning' : 'danger'}">${typeLabel}</span>
                </td>
                <td>
                    <input type="number" class="form-control quantity-input" 
                        name="items[${itemIndex}][quantity]" 
                        value="${item.quantity}" 
                        min="1" 
                        data-item-index="${itemIndex}"
                        required>
                </td>
                <td>
                    <input type="number" class="form-control unit-price-input" 
                        name="items[${itemIndex}][unit_price]" 
                        value="${item.unit_price}" 
                        min="0" 
                        step="0.01"
                        data-item-index="${itemIndex}"
                        required>
                </td>
                <td>
                    <input type="text" class="form-control line-total-display" 
                        value="${(parseFloat(item.quantity || 0) * parseFloat(item.unit_price || 0)).toFixed(2)}" 
                        readonly>
                    <input type="hidden" name="items[${itemIndex}][line_total]" class="line-total-hidden" 
                        value="${(parseFloat(item.quantity || 0) * parseFloat(item.unit_price || 0)).toFixed(2)}">
                </td>
                <td>
                    <textarea class="form-control" 
                        name="items[${itemIndex}][notes]" 
                        rows="1" 
                        placeholder="Notes..."></textarea>
                </td>
            </tr>
        `;
    }

    function initializeInvoiceCalculations() {
        // Handle quantity and unit price changes
        $(document).off('input', '.quantity-input, .unit-price-input').on('input', '.quantity-input, .unit-price-input', function() {
            const row = $(this).closest('tr');
            const itemIndex = $(this).data('item-index');
            const quantity = parseFloat(row.find('.quantity-input').val()) || 0;
            const unitPrice = parseFloat(row.find('.unit-price-input').val()) || 0;
            const lineTotal = quantity * unitPrice;
            
            row.find('.line-total-display').val(lineTotal.toFixed(2));
            row.find('.line-total-hidden').val(lineTotal.toFixed(2));
            calculateInvoiceTotals();
        });

        // Handle checkbox changes
        $(document).off('change', '.item-checkbox').on('change', '.item-checkbox', function() {
            const row = $(this).closest('tr');
            const inputs = row.find('input:not(.item-checkbox), select, textarea');
            
            if ($(this).is(':checked')) {
                inputs.prop('disabled', false);
            } else {
                inputs.prop('disabled', true);
            }
            calculateInvoiceTotals();
        });

        // Handle deposit applied change
        $('#deposit_applied').off('input').on('input', function() {
            const enteredAmount = parseFloat($(this).val()) || 0;
            const maxAmount = parseFloat($(this).attr('max')) || 0;
            
            // Validate: cannot exceed available amount
            if (enteredAmount > maxAmount) {
                $(this).val(maxAmount.toFixed(2));
                alert(`Deposit amount cannot exceed available amount of ${maxAmount.toFixed(2)} TZS`);
            }
            
            calculateInvoiceTotals();
        });

        // Calculate total on page load
        calculateInvoiceTotals();
    }

    function calculateInvoiceTotals() {
        let subtotal = 0;
        $('.invoice-item-row').each(function() {
            if ($(this).find('.item-checkbox').is(':checked')) {
                const lineTotal = parseFloat($(this).find('.line-total-display').val()) || 0;
                subtotal += lineTotal;
            }
        });

        const depositApplied = parseFloat($('#deposit_applied').val()) || 0;
        const total = subtotal - depositApplied;

        $('#invoice-subtotal').text(subtotal.toFixed(2));
        $('#invoice-deposit').text(depositApplied.toFixed(2));
        $('#invoice-total').text(total.toFixed(2));
    }

    @if($selectedContract)
        $('#contract_id').trigger('change');
    @endif

    // Form validation before submit
    function validateDepositAmount() {
        const depositApplied = parseFloat($('#deposit_applied').val()) || 0;
        const maxAmount = parseFloat($('#deposit_applied').attr('max')) || 0;
        const isRequired = $('#deposit_applied').attr('required');
        
        // Check if required and empty
        if (isRequired && depositApplied <= 0) {
            alert('Deposit Applied is required when deposits are available. Please enter the deposit amount.');
            $('#deposit_applied').focus();
            return false;
        }
        
        // Check if exceeds available
        if (depositApplied > maxAmount) {
            alert(`Deposit amount cannot exceed available amount of ${maxAmount.toFixed(2)} TZS`);
            $('#deposit_applied').focus();
            return false;
        }
        
        return true;
    }
});
</script>
@endpush
