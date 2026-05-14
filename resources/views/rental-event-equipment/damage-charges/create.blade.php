@extends('layouts.main')

@section('title', 'Create Damage & Loss Charges')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Damage & Loss Charges', 'url' => route('rental-event-equipment.damage-charges.index'), 'icon' => 'bx bx-error-circle'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">CREATE DAMAGE & LOSS CHARGES</h6>
        <hr />

        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bx bx-error-circle me-2"></i>New Damage & Loss Charges</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('rental-event-equipment.damage-charges.store') }}" id="charge-form">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="return_id" class="form-label">Return <span class="text-danger">*</span></label>
                                <select class="form-select select2-single" id="return_id" name="return_id" required>
                                    <option value="">Select Return</option>
                                    @foreach($returns as $return)
                                        <option value="{{ $return['id'] }}" 
                                            data-encoded-id="{{ $return['encoded_id'] }}"
                                            {{ $selectedReturn && $selectedReturn->id == $return['id'] ? 'selected' : '' }}>
                                            {{ $return['return_number'] }} - {{ $return['customer_name'] }} 
                                            ({{ $return['return_date'] }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Select the return to charge for damages/losses</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="charge_date" class="form-label">Charge Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="charge_date" name="charge_date" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="mb-3"><i class="bx bx-list-ul me-2"></i>Charge Items</h6>
                            <div id="items-container">
                                <p class="text-muted">Please select a return to load items.</p>
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
                        <a href="{{ route('rental-event-equipment.damage-charges.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="bx bx-check me-1"></i>Create Charges
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

    $('#return_id').change(function() {
        const returnId = $(this).val();
        const selectedOption = $(this).find('option:selected');
        const encodedId = selectedOption.data('encoded-id') || returnId;
        loadReturnItems(encodedId);
    });

    function loadReturnItems(returnId) {
        const itemsContainer = $('#items-container');
        
        if (!returnId) {
            itemsContainer.html('<p class="text-muted">Please select a return to load items.</p>');
            return;
        }

        const selectedOption = $('#return_id option:selected');
        const encodedId = selectedOption.data('encoded-id') || returnId;

        itemsContainer.html('<p class="text-muted">Loading items...</p>');

        $.ajax({
            url: '/rental-event-equipment/damage-charges/returns/' + encodedId + '/items',
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(response) {
                if (response.success && response.items && response.items.length > 0) {
                    let itemIndex = 0;
                    let html = `
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">Select</th>
                                        <th width="25%">Equipment</th>
                                        <th width="10%">Quantity</th>
                                        <th width="15%">Condition</th>
                                        <th width="15%">Charge Type <span class="text-danger">*</span></th>
                                        <th width="15%">Unit Charge <span class="text-danger">*</span></th>
                                        <th width="15%">Total Charge</th>
                                    </tr>
                                </thead>
                                <tbody id="charge-items-tbody">
                    `;

                    response.items.forEach(function(item) {
                        const chargeType = item.condition === 'lost' ? 'loss' : 'damage';
                        html += `
                            <tr class="charge-item-row">
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input item-checkbox" type="checkbox" 
                                            id="item_${itemIndex}" 
                                            data-item-index="${itemIndex}"
                                            checked>
                                    </div>
                                    <input type="hidden" name="items[${itemIndex}][return_item_id]" value="${item.return_item_id}">
                                    <input type="hidden" name="items[${itemIndex}][equipment_id]" value="${item.equipment_id}">
                                </td>
                                <td>
                                    <strong>${item.equipment_name}</strong><br>
                                    <small class="text-muted">${item.equipment_code}</small>
                                    ${item.condition_notes ? '<br><small class="text-info">' + item.condition_notes + '</small>' : ''}
                                </td>
                                <td>
                                    <input type="number" class="form-control quantity-input" 
                                        name="items[${itemIndex}][quantity]" 
                                        value="${item.quantity}" 
                                        min="1" 
                                        max="${item.quantity}"
                                        data-max="${item.quantity}"
                                        data-item-index="${itemIndex}"
                                        required>
                                </td>
                                <td>
                                    <span class="badge bg-${item.condition === 'damaged' ? 'warning' : 'danger'}">${item.condition.charAt(0).toUpperCase() + item.condition.slice(1)}</span>
                                </td>
                                <td>
                                    <select class="form-select charge-type-select" name="items[${itemIndex}][charge_type]" required>
                                        <option value="damage" ${chargeType === 'damage' ? 'selected' : ''}>Damage</option>
                                        <option value="loss" ${chargeType === 'loss' ? 'selected' : ''}>Loss</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" class="form-control unit-charge-input" 
                                        name="items[${itemIndex}][unit_charge]" 
                                        value="0" 
                                        min="0" 
                                        step="0.01"
                                        data-item-index="${itemIndex}"
                                        required>
                                </td>
                                <td>
                                    <input type="text" class="form-control total-charge-display" 
                                        value="0.00" 
                                        readonly>
                                    <input type="hidden" name="items[${itemIndex}][description]" value="${item.condition_notes || ''}">
                                </td>
                            </tr>
                        `;
                        itemIndex++;
                    });

                    html += `
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <strong>Total Charges: <span id="total-charges">0.00</span> TZS</strong>
                        </div>
                    `;

                    itemsContainer.html(html);
                    initializeChargeCalculations();
                } else {
                    itemsContainer.html(`
                        <div class="alert alert-warning">
                            <i class="bx bx-info-circle me-2"></i>
                            No damaged or lost items found in this return. All items were returned in good condition.
                        </div>
                    `);
                }
            },
            error: function(xhr) {
                console.error('Error loading return items:', xhr);
                let errorMsg = 'Failed to load return items.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMsg = xhr.responseJSON.error;
                }
                itemsContainer.html(`<div class="alert alert-danger">${errorMsg}</div>`);
            }
        });
    }

    function initializeChargeCalculations() {
        // Handle quantity and unit charge changes
        $(document).off('input', '.quantity-input, .unit-charge-input').on('input', '.quantity-input, .unit-charge-input', function() {
            const row = $(this).closest('tr');
            const itemIndex = $(this).data('item-index');
            const quantity = parseFloat(row.find('.quantity-input').val()) || 0;
            const unitCharge = parseFloat(row.find('.unit-charge-input').val()) || 0;
            const totalCharge = quantity * unitCharge;
            
            row.find('.total-charge-display').val(totalCharge.toFixed(2));
            calculateTotalCharges();
        });

        // Handle checkbox changes
        $(document).off('change', '.item-checkbox').on('change', '.item-checkbox', function() {
            const row = $(this).closest('tr');
            const inputs = row.find('input, select');
            
            if ($(this).is(':checked')) {
                inputs.prop('disabled', false);
            } else {
                inputs.prop('disabled', true);
                row.find('.quantity-input').val(0);
                row.find('.unit-charge-input').val(0);
                row.find('.total-charge-display').val('0.00');
            }
            calculateTotalCharges();
        });

        // Calculate total on page load
        calculateTotalCharges();
    }

    function calculateTotalCharges() {
        let total = 0;
        $('.charge-item-row').each(function() {
            if ($(this).find('.item-checkbox').is(':checked')) {
                const totalCharge = parseFloat($(this).find('.total-charge-display').val()) || 0;
                total += totalCharge;
            }
        });
        $('#total-charges').text(total.toFixed(2));
    }

    @if($selectedReturn)
        $('#return_id').trigger('change');
    @endif
});
</script>
@endpush
