@extends('layouts.main')

@section('title', 'Edit Transfer')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Transfers', 'url' => route('inventory.transfers.index'), 'icon' => 'bx bx-transfer'],
            ['label' => 'Edit Transfer', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h6 class="mb-0 text-uppercase">Edit Transfer</h6>
                <p class="mb-0 text-muted">Update transfer details and items</p>
            </div>
            <a href="{{ route('inventory.transfers.index') }}" class="btn btn-secondary">
                <i class="bx bx-arrow-back me-1"></i>Back to Transfers
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(isset($errors) && $errors->any())
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

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('inventory.transfers.update', $transfer->hash_id) }}" id="transferForm">
                    @csrf
                    @method('PUT')
                    
                    <!-- Transfer Details -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="destination_branch_id" class="form-label">Destination Branch <span class="text-danger">*</span></label>
                                <select class="form-select" id="destination_branch_id" name="destination_branch_id" required>
                                    <option value="">Select Destination Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('destination_branch_id', $destinationBranch->id ?? '') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}{{ $branch->id == Auth::user()->branch_id ? ' (Your Branch)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">You can transfer to other branches or between locations within your own branch</small>
                                @error('destination_branch_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="destination_location_id" class="form-label">Destination Location <span class="text-danger">*</span></label>
                                <select class="form-select" id="destination_location_id" name="destination_location_id" required>
                                    <option value="">Select Location</option>
                                    @if($destinationLocation)
                                        <option value="{{ $destinationLocation->id }}" selected>{{ $destinationLocation->name }}</option>
                                    @endif
                                </select>
                                @error('destination_location_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="transfer_date" class="form-label">Transfer Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="transfer_date" name="transfer_date" 
                                       value="{{ old('transfer_date', $transfer->movement_date->format('Y-m-d')) }}" required>
                                @error('transfer_date')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="reference" class="form-label">Reference</label>
                                <input type="text" class="form-control" id="reference" name="reference" 
                                       value="{{ old('reference', $transfer->reference) }}" 
                                       placeholder="Transfer reference">
                                @error('reference')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" 
                                          placeholder="Additional notes about this transfer">{{ old('notes', $transfer->notes) }}</textarea>
                                @error('notes')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Items Section -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">
                                <i class="bx bx-package me-2"></i>Items to Transfer
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="itemsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Item</th>
                                            <th>Available Stock</th>
                                            <th>Transfer Quantity</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Items will be added here dynamically -->
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-center text-muted" id="no-items-message">
                                <i class="bx bx-package fs-1"></i>
                                <p class="mt-2">No items selected. Click "Add Item" to start.</p>
                            </div>

                            <div class="mt-3">
                                <button type="button" class="btn btn-primary" id="add-item">
                                    <i class="bx bx-plus me-1"></i>Add Item
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('inventory.transfers.index') }}" class="btn btn-secondary px-5">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-5" id="submit-btn" disabled>
                            <i class="bx bx-save me-1"></i>Update Transfer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="itemModalLabel">Add Item to Transfer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="modal_item_id" class="form-label">Select Item</label>
                    <select class="form-select" id="modal_item_id">
                        <option value="">Select an item...</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" 
                                    data-name="{{ $item->name }}"
                                    data-code="{{ $item->code }}"
                                    data-stock="{{ $item->current_stock }}"
                                    data-unit="{{ $item->unit_of_measure }}">
                                {{ $item->name }} ({{ $item->code }}) - Stock: {{ $item->current_stock }} {{ $item->unit_of_measure }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="modal_quantity" class="form-label">Transfer Quantity</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="modal_quantity" value="1" step="0.01" min="0.01">
                        <span class="input-group-text" id="modal_unit_display">Unit</span>
                    </div>
                    <small class="text-muted">Available: <span id="available_stock">0</span> <span id="available_unit">units</span></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="add-item-to-table">Add Item</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
        padding-left: 12px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    let itemCounter = 0;
    
    // Initialize Select2 for the item dropdown
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('#modal_item_id').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select an item...',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#itemModal')
        });
    }
    
    // Pre-populate existing transfer items
    @if(isset($transferItems) && $transferItems->count() > 0)
        @foreach($transferItems as $index => $transferItem)
            const existingRow = `
                <tr data-item-id="{{ $transferItem->item->id }}">
                    <td>
                        <input type="hidden" name="items[${itemCounter}][item_id]" value="{{ $transferItem->item->id }}">
                        <strong>{{ $transferItem->item->name }}</strong><br>
                        <small class="text-muted">{{ $transferItem->item->code }}</small>
                    </td>
                    <td>{{ $transferItem->item->current_stock + $transferItem->quantity }} {{ $transferItem->item->unit_of_measure }}</td>
                    <td>
                        <input type="number" step="0.01" name="items[${itemCounter}][quantity]" class="form-control form-control-sm" 
                               value="{{ $transferItem->quantity }}" min="0.01" max="{{ $transferItem->item->current_stock + $transferItem->quantity }}" required>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger remove-item">
                            <i class="bx bx-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            $('#itemsTable tbody').append(existingRow);
            itemCounter++;
        @endforeach
        
        $('#no-items-message').hide();
        $('#submit-btn').prop('disabled', false);
    @endif
    
    // Load locations for pre-selected branch
    @if($destinationBranch)
        const preSelectedBranchId = {{ $destinationBranch->id }};
        const preSelectedLocationId = {{ $destinationLocation->id ?? 'null' }};
        
        if (preSelectedBranchId) {
            $.ajax({
                url: '/api/branches/' + preSelectedBranchId + '/locations',
                method: 'GET',
                success: function(data) {
                    const locationSelect = $('#destination_location_id');
                    locationSelect.prop('disabled', false);
                    locationSelect.html('<option value="">Select Location</option>');
                    
                    $.each(data, function(index, location) {
                        const selected = location.id == preSelectedLocationId ? 'selected' : '';
                        locationSelect.append(
                            $('<option></option>')
                                .attr('value', location.id)
                                .attr('selected', selected)
                                .text(location.name)
                        );
                    });
                }
            });
        }
    @endif

    // Handle branch selection change to load locations
    $('#destination_branch_id').change(function() {
        const branchId = $(this).val();
        const locationSelect = $('#destination_location_id');
        
        // Reset and disable location dropdown
        locationSelect.html('<option value="">Select Location</option>').prop('disabled', true);
        
        if (branchId) {
            // Load locations for selected branch
            $.ajax({
                url: '/api/branches/' + branchId + '/locations',
                method: 'GET',
                success: function(data) {
                    locationSelect.prop('disabled', false);
                    $.each(data, function(index, location) {
                        locationSelect.append(
                            $('<option></option>')
                                .attr('value', location.id)
                                .text(location.name)
                        );
                    });
                },
                error: function() {
                    alert('Error loading locations. Please try again.');
                }
            });
        }
    });

    // Add Item Button
    $('#add-item').click(function() {
        $('#itemModal').modal('show');
        resetItemModal();
    });
    
    // Ensure modal select2 shows correctly each time modal opens
    $('#itemModal').on('shown.bs.modal', function() {
        if ($.fn.select2) {
            const $sel = $('#modal_item_id');
            if ($sel.data('select2')) { $sel.select2('destroy'); }
            $sel.select2({ 
                theme: 'bootstrap-5', 
                width: '100%', 
                dropdownParent: $('#itemModal'),
                placeholder: 'Select an item...',
                allowClear: true
            });
        }
    });

    // Item selection change in modal
    $('#modal_item_id').on('select2:select', function(e) {
        const data = e.params.data;
        const selectedOption = $(this).find('option[value="' + data.id + '"]');
        const stock = selectedOption.data('stock');
        const unit = selectedOption.data('unit');
        
        $('#available_stock').text(stock);
        $('#available_unit').text(unit);
        $('#modal_unit_display').text(unit);
        $('#modal_quantity').attr('max', stock);
    });
    
    // Handle clear selection
    $('#modal_item_id').on('select2:clear', function() {
        $('#available_stock').text('0');
        $('#available_unit').text('units');
        $('#modal_unit_display').text('Unit');
        $('#modal_quantity').removeAttr('max');
    });

    // Add item to table
    $('#add-item-to-table').click(function() {
        const itemId = $('#modal_item_id').val();
        const selectedOption = $('#modal_item_id option:selected');
        const quantity = parseFloat($('#modal_quantity').val());
        
        if (!itemId) {
            alert('Please select an item.');
            return;
        }
        
        if (!quantity || quantity <= 0) {
            alert('Please enter a valid quantity.');
            return;
        }

        const maxStock = parseFloat($('#modal_item_id option:selected').data('stock'));
        if (quantity > maxStock) {
            alert('Transfer quantity cannot exceed available stock.');
            return;
        }

        // Check if item already exists in table
        if ($(`input[name="items[${itemId}][item_id]"]`).length > 0) {
            alert('This item is already in the transfer list.');
            return;
        }

        const itemName = selectedOption.data('name');
        const itemCode = selectedOption.data('code');
        const unit = selectedOption.data('unit');

        const newRow = `
            <tr data-item-id="${itemId}">
                <td>
                    <input type="hidden" name="items[${itemCounter}][item_id]" value="${itemId}">
                    <strong>${itemName}</strong><br>
                    <small class="text-muted">${itemCode}</small>
                </td>
                <td>${maxStock} ${unit}</td>
                <td>
                    <input type="number" step="0.01" name="items[${itemCounter}][quantity]" class="form-control form-control-sm" 
                           value="${quantity}" min="0.01" max="${maxStock}" required>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-item">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#itemsTable tbody').append(newRow);
        $('#no-items-message').hide();
        $('#submit-btn').prop('disabled', false);
        itemCounter++;
        
        $('#itemModal').modal('hide');
    });

    // Remove item
    $(document).on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        
        if ($('#itemsTable tbody tr').length === 0) {
            $('#no-items-message').show();
            $('#submit-btn').prop('disabled', true);
        }
    });

    function resetItemModal() {
        $('#modal_item_id').val('').trigger('change');
        $('#modal_quantity').val(1);
        $('#available_stock').text('0');
        $('#available_unit').text('units');
        $('#modal_unit_display').text('Unit');
    }
});
</script>
@endpush
@endsection 