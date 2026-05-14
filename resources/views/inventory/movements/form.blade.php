@php
// Ensure $movement is defined to avoid undefined variable notices
$movement = $movement ?? null;
$isEdit = isset($movement);
@endphp

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bx bx-error-circle me-2"></i>
    Please fix the following errors:
    <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<form action="{{ $isEdit ? route('inventory.movements.update', optional($movement)->hash_id) : route('inventory.movements.store') }}"
      method="POST" id="movementForm">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <!-- Movement Information -->
    <div class="row">
        <div class="col-12">
            <h6 class="text-uppercase">Movement Information</h6>
            <hr>
        </div>
    </div>

    <div class="row">
        @if(isset($isOpening) && $isOpening)
        <div class="col-12 mb-3">
            <div class="alert alert-warning">
                <div class="d-flex align-items-center">
                    <i class="bx bx-info-circle me-2"></i>
                    <div>
                        <strong>Opening Balance Mode:</strong> This will initialize starting stock for the current login location. Use this only once per item per location. Subsequent stock changes should be done via normal adjustments, purchases, or transfers.
                    </div>
                </div>
            </div>
        </div>
        @endif
        <!-- Reference -->
        <div class="col-md-6 mb-3">
            <label class="form-label">Reference</label>
            <input type="text" name="reference" class="form-control @error('reference') is-invalid @enderror"
                value="{{ old('reference', optional($movement)->reference ?? ('MV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT))) }}" 
                placeholder="Enter reference (optional)">
            @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- Movement Date -->
        <div class="col-md-6 mb-3">
            <label class="form-label">Movement Date <span class="text-danger">*</span></label>
            <input type="date" name="movement_date" class="form-control @error('movement_date') is-invalid @enderror"
                value="{{ old('movement_date', ($isEdit && optional($movement)->movement_date) ? optional(optional($movement)->movement_date)->format('Y-m-d') : date('Y-m-d')) }}" required>
            @error('movement_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        @if(isset($isOpening) && $isOpening)
        <input type="hidden" name="opening" value="1" />
        @endif

        <!-- Movement Type -->
        <div class="col-md-6 mb-3">
            <label class="form-label">Movement Type <span class="text-danger">*</span></label>
            <select name="movement_type" class="form-control @error('movement_type') is-invalid @enderror" required id="movementTypeSelect">
                <option value="">Select Type</option>
                <option value="adjustment_in" {{ old('movement_type', optional($movement)->movement_type ?? ($defaultMovementType ?? '')) == 'adjustment_in' ? 'selected' : '' }}>
                    Adjustment In
                </option>
                <option value="adjustment_out" {{ old('movement_type', optional($movement)->movement_type ?? ($defaultMovementType ?? '')) == 'adjustment_out' ? 'selected' : '' }}>
                    Adjustment Out
                </option>
            </select>
            @error('movement_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <small class="form-text text-muted">
                <strong>Adjustment In:</strong> Add stock for corrections and found items<br>
                <strong>Adjustment Out:</strong> Remove stock for corrections and missing items
            </small>
        </div>
    </div>

    <!-- Items Section -->
    <div class="row">
        <div class="col-12">
            <h6 class="text-uppercase">Items</h6>
            <hr>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <button type="button" class="btn btn-primary" id="add-item">
                <i class="bx bx-plus me-1"></i>Add Item
            </button>
        </div>
        </div>

    <!-- Items Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover" id="itemsTable">
            <thead class="table-light">
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Unit Cost</th>
                    <th>Total Cost</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @if($isEdit && isset($movement))
                <tr data-item-id="{{ optional($movement)->item_id }}">
                    <td>
                        <input type="hidden" name="items[0][item_id]" value="{{ optional($movement)->item_id }}">
                        <input type="hidden" name="items[0][item_name]" value="{{ optional(optional($movement)->item)->name }}">
                        <input type="hidden" name="items[0][item_code]" value="{{ optional(optional($movement)->item)->code }}">
                        <strong>{{ optional(optional($movement)->item)->name }}</strong><br>
                        <small class="text-muted">{{ optional(optional($movement)->item)->code }}</small>
                    </td>
                    <td>
                        <input type="number" step="0.01" name="items[0][quantity]" class="form-control form-control-sm quantity-input" 
                               value="{{ optional($movement)->quantity }}" required>
                    </td>
                    <td>
                        <input type="number" step="0.01" name="items[0][unit_cost]" class="form-control form-control-sm unit-cost-input" 
                               value="{{ optional($movement)->unit_cost }}" required>
                    </td>
                    <td>
                        <span class="total-cost-display">{{ number_format(optional($movement)->total_cost ?? 0, 2) }}</span>
                        <input type="hidden" name="items[0][total_cost]" value="{{ optional($movement)->total_cost }}">
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger remove-item">
                            <i class="bx bx-trash"></i>
                        </button>
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
        </div>

    <!-- Summary -->
    <div class="row">
        <div class="col-md-6 offset-md-6">
            <table class="table table-borderless">
                <tr>
                    <td><strong>Total Items:</strong></td>
                    <td class="text-end"><span id="total-items">0</span></td>
                </tr>
                <tr>
                    <td><strong>Total Value:</strong></td>
                    <td class="text-end"><span id="total-value">0.00</span></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Additional Information -->
    <div class="row">
        <div class="col-12">
            <h6 class="text-uppercase">Additional Information</h6>
            <hr>
        </div>
    </div>

    <div class="row">
        <!-- Reason -->
        <div class="col-md-12 mb-3">
            <label class="form-label">Reason <span class="text-danger">*</span></label>
            <textarea name="reason" class="form-control @error('reason') is-invalid @enderror"
                      rows="3" placeholder="Enter reason for this adjustment" required>{{ old('reason', optional($movement)->reason ?? '') }}</textarea>
            @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <!-- Notes -->
        <div class="col-md-12 mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror"
                      rows="2" placeholder="Additional notes (optional)">{{ old('notes', optional($movement)->notes ?? '') }}</textarea>
            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <button type="submit" class="btn btn-primary px-5">
                <i class="bx bx-save me-1"></i>{{ $isEdit ? 'Update' : 'Create' }} Movement
            </button>
            <a href="{{ route('inventory.movements.index') }}" class="btn btn-secondary px-5 ms-2">
                <i class="bx bx-x me-1"></i>Cancel
            </a>
        </div>
    </div>
</form>

<!-- Add Item Modal -->
<div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="modal_item_id" class="form-label">Select Item</label>
                    <select class="form-select select2-modal" id="modal_item_id">
                        <option value="">Choose an item...</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" 
                                    data-name="{{ $item->name }}" 
                                    data-code="{{ $item->code }}"
                                    data-cost="{{ $item->resolved_cost_price ?? $item->cost_price }}"
                                    data-stock="{{ $locationStocks[$item->id] ?? 0 }}"
                                    data-unit="{{ $item->unit_of_measure }}">
                                {{ $item->name }} ({{ $item->code }}) - Stock: {{ $locationStocks[$item->id] ?? 0 }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_quantity" class="form-label">Quantity</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="modal_quantity" value="1" step="0.01" min="0.01">
                                <span class="input-group-text" id="modal_unit_display">Unit</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_unit_cost" class="form-label">Unit Cost</label>
                            <input type="number" class="form-control" id="modal_unit_cost" step="0.01" min="0">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label class="form-label">Total Cost Preview</label>
                            <div class="form-control-plaintext border rounded p-2 bg-light">
                                <strong id="modal_total_preview">0.00</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="add-item-to-table">Add Item</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    let itemCounter = {{ $isEdit ? 1 : 0 }};

    // Initialize Select2 for modal
    $('.select2-modal').select2({
        placeholder: 'Search for an item...',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap-5',
        dropdownParent: $('#itemModal'),
        minimumInputLength: 0,
        templateResult: formatItemOption,
        templateSelection: formatItemSelection
    });

    // Format item options in dropdown
    function formatItemOption(item) {
        if (!item.id) {
            return item.text;
        }
        
        const $item = $(item.element);
        const stock = $item.data('stock');
        const code = $item.data('code');
        const cost = $item.data('cost');
        
        return $(`
            <div>
                <strong>${item.text.split(' (')[0]}</strong>
                <br>
                <small class="text-muted">Code: ${code} | Cost: ${cost} | Stock: ${stock}</small>
            </div>
        `);
    }

    // Format selected item
    function formatItemSelection(item) {
        return item.text;
    }

    // Add Item Button
    $('#add-item').click(function() {
        $('#itemModal').modal('show');
        resetItemModal();
    });

    // Item selection change in modal
    $('#modal_item_id').change(function() {
        const selectedOption = $(this).find(':selected');
        const cost = selectedOption.data('cost');
        const unit = selectedOption.data('unit');
        
        $('#modal_unit_cost').val(cost || 0);
        $('#modal_unit_display').text(unit || 'Unit');
        calculateModalTotal();
    });

    // Modal calculations
    $('#modal_quantity, #modal_unit_cost').on('input', function() {
        calculateModalTotal();
    });

    function calculateModalTotal() {
        const quantity = parseFloat($('#modal_quantity').val()) || 0;
        const unitCost = parseFloat($('#modal_unit_cost').val()) || 0;
        const total = quantity * unitCost;
        
        $('#modal_total_preview').text(total.toFixed(2));
    }

    function resetItemModal() {
        $('#modal_item_id').val('').trigger('change');
        $('#modal_quantity').val(1);
        $('#modal_unit_cost').val(0);
        $('#modal_unit_display').text('Unit');
        $('#modal_total_preview').text('0.00');
    }

    // Add item to table
    $('#add-item-to-table').click(function() {
        const itemId = $('#modal_item_id').val();
        const itemName = $('#modal_item_id option:selected').data('name');
        const itemCode = $('#modal_item_id option:selected').data('code');
        const quantity = parseFloat($('#modal_quantity').val()) || 0;
        const unitCost = parseFloat($('#modal_unit_cost').val()) || 0;

        if (!itemId || quantity <= 0) {
            Swal.fire('Error', 'Please select an item and enter a valid quantity', 'error');
            return;
        }

        // Check if item already exists
        if ($(`tr[data-item-id="${itemId}"]`).length > 0) {
            Swal.fire('Error', 'This item is already added. Please edit the existing entry.', 'error');
            return;
        }

        const totalCost = quantity * unitCost;

        const newRow = `
            <tr data-item-id="${itemId}">
                <td>
                    <input type="hidden" name="items[${itemCounter}][item_id]" value="${itemId}">
                    <input type="hidden" name="items[${itemCounter}][item_name]" value="${itemName}">
                    <input type="hidden" name="items[${itemCounter}][item_code]" value="${itemCode}">
                    <strong>${itemName}</strong><br>
                    <small class="text-muted">${itemCode}</small>
                </td>
                <td>
                    <input type="number" step="0.01" name="items[${itemCounter}][quantity]" class="form-control form-control-sm quantity-input" 
                           value="${quantity}" required>
                </td>
                <td>
                    <input type="number" step="0.01" name="items[${itemCounter}][unit_cost]" class="form-control form-control-sm unit-cost-input" 
                           value="${unitCost}" required>
                </td>
                <td>
                    <span class="total-cost-display">${totalCost.toFixed(2)}</span>
                    <input type="hidden" name="items[${itemCounter}][total_cost]" value="${totalCost}">
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-item">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#itemsTable tbody').append(newRow);
        itemCounter++;
        
        updateSummary();
        $('#itemModal').modal('hide');
    });

    // Remove item
    $(document).on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        updateSummary();
    });

    // Update totals when quantity or unit cost changes
    $(document).on('input', '.quantity-input, .unit-cost-input', function() {
        const row = $(this).closest('tr');
        const quantity = parseFloat(row.find('.quantity-input').val()) || 0;
        const unitCost = parseFloat(row.find('.unit-cost-input').val()) || 0;
        const totalCost = quantity * unitCost;
        
        row.find('.total-cost-display').text(totalCost.toFixed(2));
        row.find('input[name*="[total_cost]"]').val(totalCost);
        
        updateSummary();
    });

    function updateSummary() {
        const totalItems = $('#itemsTable tbody tr').length;
        let totalValue = 0;
        
        $('#itemsTable tbody tr').each(function() {
            const totalCost = parseFloat($(this).find('input[name*="[total_cost]"]').val()) || 0;
            totalValue += totalCost;
        });
        
        $('#total-items').text(totalItems);
        $('#total-value').text(totalValue.toFixed(2));
    }

    // Form validation
    $('#movementForm').on('submit', function(e) {
        const totalItems = $('#itemsTable tbody tr').length;
        if (totalItems === 0) {
            e.preventDefault();
            Swal.fire('Error', 'Please add at least one item to the movement.', 'error');
            return false;
        }
    });

    // Initialize summary
    updateSummary();
});
</script>
@endpush