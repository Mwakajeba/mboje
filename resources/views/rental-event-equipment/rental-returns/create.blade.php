@extends('layouts.main')

@section('title', 'Record Rental Return')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Rental Returns', 'url' => route('rental-event-equipment.rental-returns.index'), 'icon' => 'bx bx-undo'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">RECORD RENTAL RETURN</h6>
        <hr />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-undo me-2"></i>New Rental Return</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('rental-event-equipment.rental-returns.store') }}" id="return-form">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="dispatch_id" class="form-label">Dispatch <span class="text-danger">*</span></label>
                                <select class="form-select select2-single" id="dispatch_id" name="dispatch_id" required>
                                    <option value="">Select Dispatch</option>
                                    @foreach($dispatches as $dispatch)
                                        <option value="{{ $dispatch['id'] }}" 
                                            data-encoded-id="{{ $dispatch['encoded_id'] }}">
                                            {{ $dispatch['dispatch_number'] }} - {{ $dispatch['customer_name'] }} 
                                            ({{ $dispatch['dispatch_date'] }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Select the dispatch to return items from</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="return_date" class="form-label">Return Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="return_date" name="return_date" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0"><i class="bx bx-package me-2"></i>Return Items</h6>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-success" id="add-row-btn">
                                        <i class="bx bx-plus me-1"></i>Add Row
                                    </button>
                                </div>
                            </div>
                            <div class="alert alert-info">
                                <i class="bx bx-info-circle me-2"></i>
                                <strong>Bulk Return Instructions:</strong> 
                                <ul class="mb-0 mt-2">
                                    <li>For each equipment item, you can return partial quantities with different conditions.</li>
                                    <li><strong>Example:</strong> If 30 items were dispatched:
                                        <ul>
                                            <li>Return 27 items as "Good" (quantity: 27)</li>
                                            <li>Return 2 items as "Damaged" (quantity: 2) - click "Add Row" to add another entry for the same item</li>
                                            <li>Return 1 item as "Lost" (quantity: 1) - click "Add Row" again</li>
                                        </ul>
                                    </li>
                                    <li>Total returned quantity per item cannot exceed the dispatched quantity.</li>
                                </ul>
                            </div>
                            <div id="items-container">
                                <p class="text-muted">Please select a dispatch to load items.</p>
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
                        <a href="{{ route('rental-event-equipment.rental-returns.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-check me-1"></i>Record Return
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

    let dispatchItems = {};

    $('#dispatch_id').change(function() {
        const dispatchId = $(this).val();
        const itemsContainer = $('#items-container');
        
        if (!dispatchId) {
            itemsContainer.html('<p class="text-muted">Please select a dispatch to load items.</p>');
            return;
        }

        // Load dispatch items
        loadDispatchItems(dispatchId);
    });

    function loadDispatchItems(dispatchId) {
        const itemsContainer = $('#items-container');
        const selectedOption = $('#dispatch_id option:selected');
        const encodedId = selectedOption.data('encoded-id') || dispatchId;
        
        itemsContainer.html(`
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th width="30%">Equipment</th>
                            <th width="15%">Dispatched Qty</th>
                            <th width="15%">Returned Qty <span class="text-danger">*</span></th>
                            <th width="15%">Condition <span class="text-danger">*</span></th>
                            <th width="20%">Condition Notes</th>
                            <th width="5%">Action</th>
                        </tr>
                    </thead>
                    <tbody id="items-tbody">
                        <tr>
                            <td colspan="5" class="text-center text-muted">Loading items...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        `);

        $.ajax({
            url: '/rental-event-equipment/rental-dispatches/' + dispatchId + '/items',
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(response) {
                const tbody = $('#items-tbody');
                tbody.empty();
                
                if (response.items && response.items.length > 0) {
                    // Store dispatch items globally for adding rows
                    window.dispatchItemsData = response.items;
                    let itemIndex = 0;
                    
                    response.items.forEach(function(item) {
                        const row = createReturnRow(item, itemIndex, response.items, false);
                        tbody.append(row);
                        itemIndex++;
                    });
                    
                    // Initialize bulk operations
                    initializeBulkOperations();
                    
                    // Initialize remaining quantities for all equipment
                    response.items.forEach(function(item) {
                        const equipmentId = item.equipment_id;
                        const originalMaxQty = item.quantity;
                        let totalReturned = 0;
                        $(`.return-item-row[data-equipment-id="${equipmentId}"]`).each(function() {
                            totalReturned += parseInt($(this).find('.quantity-input').val()) || 0;
                        });
                        updateRemainingQuantities(equipmentId, originalMaxQty, totalReturned, null);
                    });
                } else {
                    tbody.html('<tr><td colspan="6" class="text-center text-muted">No items found for this dispatch.</td></tr>');
                }
            },
            error: function(xhr) {
                console.error('Error loading dispatch items:', xhr);
                let errorMsg = 'Failed to load dispatch items.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMsg = xhr.responseJSON.error;
                }
                $('#items-tbody').html(`<tr><td colspan="6" class="text-center text-danger">${errorMsg}</td></tr>`);
            }
        });
    }
    
    function createReturnRow(item, itemIndex, allItems, isAdditionalRow = false) {
        const maxQty = item.quantity;
        // For additional rows, start with 0 quantity
        const initialQty = isAdditionalRow ? 0 : maxQty;
        
        return `
            <tr class="return-item-row" data-equipment-id="${item.equipment_id}" data-dispatch-item-id="${item.id}" data-original-max="${maxQty}">
                <td>
                    <select class="form-select equipment-select" name="items[${itemIndex}][equipment_id]" required>
                        <option value="">Select Equipment</option>
                        ${allItems.map(i => `<option value="${i.equipment_id}" ${i.equipment_id == item.equipment_id ? 'selected' : ''} data-dispatch-item-id="${i.id}" data-max-qty="${i.quantity}">${i.equipment_name} (${i.equipment_code})</option>`).join('')}
                    </select>
                    <input type="hidden" name="items[${itemIndex}][dispatch_item_id]" class="dispatch-item-id" value="${item.id}">
                </td>
                <td>
                    <span class="badge bg-secondary dispatched-qty" data-original="${maxQty}">${isAdditionalRow ? maxQty + ' remaining' : maxQty + ' dispatched'}</span>
                </td>
                <td>
                    <input type="number" class="form-control quantity-input" 
                        name="items[${itemIndex}][quantity_returned]" 
                        value="${initialQty}" 
                        min="0" 
                        max="${isAdditionalRow ? maxQty : maxQty}" 
                        data-max="${isAdditionalRow ? maxQty : maxQty}"
                        data-original-max="${maxQty}"
                        required>
                </td>
                <td>
                    <select class="form-select condition-select" name="items[${itemIndex}][condition]" required>
                        <option value="good" selected>Good</option>
                        <option value="damaged">Damaged</option>
                        <option value="lost">Lost</option>
                    </select>
                </td>
                <td>
                    <textarea class="form-control" 
                        name="items[${itemIndex}][condition_notes]" 
                        rows="2" 
                        placeholder="Condition notes..."></textarea>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-row-btn" title="Remove row">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }
    
    function initializeBulkOperations() {
        // Handle equipment selection change - update dispatched quantity and max
        $(document).on('change', '.equipment-select', function() {
            const row = $(this).closest('tr');
            const selectedOption = $(this).find('option:selected');
            const maxQty = parseInt(selectedOption.data('max-qty')) || 0;
            const dispatchItemId = selectedOption.data('dispatch-item-id');
            const equipmentId = selectedOption.val();
            
            row.data('equipment-id', equipmentId);
            row.data('dispatch-item-id', dispatchItemId);
            row.data('original-max', maxQty);
            row.find('.dispatch-item-id').val(dispatchItemId);
            row.find('.quantity-input').data('original-max', maxQty);
            
            // Check if this is the first row for this equipment
            const sameEquipmentRows = $(`.return-item-row[data-equipment-id="${equipmentId}"]`);
            const isFirstRow = sameEquipmentRows.index(row) === 0 || sameEquipmentRows.length === 1;
            
            if (isFirstRow) {
                row.find('.dispatched-qty').text(maxQty + ' dispatched');
                row.find('.quantity-input').attr('max', maxQty).data('max', maxQty).prop('disabled', false);
            } else {
                // Calculate remaining for additional rows
                let totalReturned = 0;
                sameEquipmentRows.not(row).each(function() {
                    totalReturned += parseInt($(this).find('.quantity-input').val()) || 0;
                });
                const remaining = maxQty - totalReturned;
                row.find('.dispatched-qty').text(remaining + ' remaining');
                row.find('.quantity-input').attr('max', remaining).data('max', remaining);
                if (remaining <= 0) {
                    row.find('.quantity-input').prop('disabled', true).val(0);
                }
            }
            
            // Update all rows for this equipment
            updateRemainingQuantities(equipmentId, maxQty, 0, null);
        });
        
        // Handle quantity changes - validate total doesn't exceed dispatched per equipment and update remaining
        let isUpdating = false; // Flag to prevent recursive updates
        
        $(document).on('input', '.quantity-input', function() {
            if (isUpdating) return; // Prevent recursive calls
            
            const row = $(this).closest('tr');
            const equipmentId = row.data('equipment-id');
            if (!equipmentId) return;
            
            const originalMaxQty = parseInt(row.data('original-max')) || parseInt($(this).data('original-max')) || 0;
            if (!originalMaxQty) return;
            
            const currentInput = $(this);
            const currentQty = parseInt(currentInput.val()) || 0;
            const rows = $(`.return-item-row[data-equipment-id="${equipmentId}"]`);
            const isFirstRow = rows.index(row) === 0;
            
            // Calculate total returned for this equipment across all rows
            let totalReturned = 0;
            rows.each(function() {
                const qty = parseInt($(this).find('.quantity-input').val()) || 0;
                totalReturned += qty;
            });
            
            // Validate total - only if it exceeds the max
            if (totalReturned > originalMaxQty) {
                // Calculate what other rows have returned (excluding current input)
                let otherRowsTotal = 0;
                rows.each(function() {
                    const input = $(this).find('.quantity-input');
                    if (input[0] !== currentInput[0]) {
                        otherRowsTotal += parseInt(input.val()) || 0;
                    }
                });
                const allowedQty = Math.max(0, originalMaxQty - otherRowsTotal);
                currentInput.val(allowedQty);
                alert(`Total returned quantity for this equipment cannot exceed ${originalMaxQty} (dispatched quantity). Maximum allowed: ${allowedQty}`);
                totalReturned = originalMaxQty; // Recalculate after correction
            }
            
            // Update remaining quantities for other rows of the same equipment
            // For remaining calculation, we use first row quantity only
            isUpdating = true;
            updateRemainingQuantities(equipmentId, originalMaxQty, totalReturned, currentInput);
            isUpdating = false;
        });
        
        // Handle blur event to finalize validation
        $(document).on('blur', '.quantity-input', function() {
            const row = $(this).closest('tr');
            const equipmentId = row.data('equipment-id');
            if (!equipmentId) return;
            
            const originalMaxQty = parseInt(row.data('original-max')) || parseInt($(this).data('original-max')) || 0;
            if (!originalMaxQty) return;
            
            const currentInput = $(this);
            const currentQty = parseInt(currentInput.val()) || 0;
            
            // Calculate total returned
            let totalReturned = 0;
            $(`.return-item-row[data-equipment-id="${equipmentId}"]`).each(function() {
                totalReturned += parseInt($(this).find('.quantity-input').val()) || 0;
            });
            
            // Final validation
            if (totalReturned > originalMaxQty) {
                let otherRowsTotal = 0;
                $(`.return-item-row[data-equipment-id="${equipmentId}"]`).each(function() {
                    const input = $(this).find('.quantity-input');
                    if (input[0] !== currentInput[0]) {
                        otherRowsTotal += parseInt(input.val()) || 0;
                    }
                });
                const allowedQty = Math.max(0, originalMaxQty - otherRowsTotal);
                currentInput.val(allowedQty);
                totalReturned = originalMaxQty;
            }
            
            isUpdating = true;
            updateRemainingQuantities(equipmentId, originalMaxQty, totalReturned, currentInput);
            isUpdating = false;
        });
        
        function updateRemainingQuantities(equipmentId, originalMaxQty, totalReturned, currentInputElement) {
            if (!equipmentId || !originalMaxQty) return;
            
            const rows = $(`.return-item-row[data-equipment-id="${equipmentId}"]`);
            
            // Calculate remaining based on what's NOT in the first row
            let firstRowQty = 0;
            if (rows.length > 0) {
                const firstRowInput = rows.first().find('.quantity-input');
                if (currentInputElement && firstRowInput[0] === currentInputElement[0]) {
                    // If user is typing in first row, use the value they're typing
                    firstRowQty = parseInt(currentInputElement.val()) || 0;
                } else {
                    firstRowQty = parseInt(firstRowInput.val()) || 0;
                }
            }
            
            const remaining = Math.max(0, originalMaxQty - firstRowQty);
            
            rows.each(function(index) {
                const row = $(this);
                const quantityInput = row.find('.quantity-input');
                const dispatchedBadge = row.find('.dispatched-qty');
                
                // Don't modify the input that triggered the update
                const isCurrentInput = currentInputElement && quantityInput.length > 0 && currentInputElement.length > 0 && quantityInput[0] === currentInputElement[0];
                const currentQty = parseInt(quantityInput.val()) || 0;
                
                if (index === 0) {
                    // First row shows original dispatched quantity
                    dispatchedBadge.text(originalMaxQty + ' dispatched');
                    quantityInput.attr('max', originalMaxQty).data('max', originalMaxQty);
                    if (!isCurrentInput) {
                        quantityInput.prop('disabled', false);
                    }
                } else {
                    // Additional rows show remaining quantity (based on first row only)
                    dispatchedBadge.text(remaining + ' remaining');
                    quantityInput.attr('max', remaining).data('max', remaining);
                    
                    // Enable/disable based on remaining
                    if (remaining > 0) {
                        // Always enable, but don't change the value if user is typing
                        if (!isCurrentInput) {
                            quantityInput.prop('disabled', false);
                            // Only adjust value if it exceeds remaining and user is not currently typing
                            if (currentQty > remaining) {
                                quantityInput.val(remaining);
                            }
                        } else {
                            // For current input, just update max but don't change value
                            quantityInput.prop('disabled', false);
                        }
                    } else {
                        // Disable only if not the current input
                        if (!isCurrentInput) {
                            quantityInput.prop('disabled', true).val(0);
                        } else {
                            // If it's the current input and remaining is 0, allow user to clear it
                            quantityInput.prop('disabled', false);
                        }
                    }
                }
            });
        }
        
        // Add row button
        $('#add-row-btn').off('click').on('click', function() {
            if (!window.dispatchItemsData || window.dispatchItemsData.length === 0) {
                alert('Please select a dispatch first.');
                return;
            }
            
            const tbody = $('#items-tbody');
            const currentRowCount = tbody.find('tr').length;
            
            // Use the first row's equipment, or the first item from dispatch
            const firstRow = tbody.find('tr').first();
            const firstEquipmentId = firstRow.data('equipment-id');
            const firstItem = window.dispatchItemsData.find(i => i.equipment_id == firstEquipmentId) || window.dispatchItemsData[0];
            
            if (!firstItem) {
                alert('No items available to add.');
                return;
            }
            
            const newRow = createReturnRow(firstItem, currentRowCount, window.dispatchItemsData, true);
            tbody.append(newRow);
            
            // Update remaining quantities after adding new row
            if (firstEquipmentId) {
                const originalMax = parseInt(firstRow.data('original-max')) || parseInt(firstRow.find('.quantity-input').data('original-max')) || firstItem.quantity;
                
                // Set original-max on new row
                const newRowElement = tbody.find('tr').last();
                newRowElement.data('equipment-id', firstEquipmentId);
                newRowElement.data('original-max', originalMax);
                newRowElement.find('.quantity-input').data('original-max', originalMax);
                newRowElement.find('.equipment-select').val(firstEquipmentId);
                
                // Calculate current total returned (excluding the new row which starts at 0)
                let totalReturned = 0;
                $(`.return-item-row[data-equipment-id="${firstEquipmentId}"]`).not(newRowElement).each(function() {
                    totalReturned += parseInt($(this).find('.quantity-input').val()) || 0;
                });
                
                // Update remaining quantities (new row will show remaining)
                updateRemainingQuantities(firstEquipmentId, originalMax, totalReturned, null);
            }
        });
        
        // Remove row button
        $(document).on('click', '.remove-row-btn', function() {
            const row = $(this).closest('tr');
            const tbody = $('#items-tbody');
            const equipmentId = row.data('equipment-id');
            const originalMax = parseInt(row.data('original-max'));
            
            // Don't allow removing if it's the only row
            if (tbody.find('tr').length <= 1) {
                alert('At least one item row is required.');
                return;
            }
            
            row.remove();
            
            // Update remaining quantities after removing row
            if (equipmentId && originalMax) {
                let totalReturned = 0;
                $(`.return-item-row[data-equipment-id="${equipmentId}"]`).each(function() {
                    totalReturned += parseInt($(this).find('.quantity-input').val()) || 0;
                });
                updateRemainingQuantities(equipmentId, originalMax, totalReturned, null);
            }
        });
    }
});
</script>
@endpush
