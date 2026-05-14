@extends('layouts.main')

@section('title', 'Create Transfer')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Transfers', 'url' => route('inventory.transfers.index'), 'icon' => 'bx bx-transfer'],
            ['label' => 'Create Transfer', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h6 class="mb-0 text-uppercase">
                    Create Transfer
                    @if(isset($currentBranch) || isset($currentLocation))
                        (<span class="text-primary">
                            @if(isset($currentBranch)){{ $currentBranch->name }}@endif
                            @if(isset($currentBranch) && isset($currentLocation)) - @endif
                            @if(isset($currentLocation)){{ $currentLocation->name }}@endif
                        </span>)
                    @endif
                </h6>
                <p class="mb-0 text-muted">Transfer items between branches or between locations within your branch</p>
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

        {{-- @if(isset($errors) && $errors->any())
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
        @endif --}}

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('inventory.transfers.store') }}" id="transferForm">
                    @csrf
                    
                    <!-- Transfer Details -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="destination_branch_id" class="form-label">Destination Branch <span class="text-danger">*</span></label>
                                <select class="form-select select2-single" id="destination_branch_id" name="destination_branch_id" required>
                                    <option value="">Select Destination Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('destination_branch_id', isset($currentBranch) ? $currentBranch->id : null) == $branch->id ?  : '' }}>
                                            {{ $branch->name }}{{ isset($currentBranch) && $branch->id == $currentBranch->id ? ' (Your Branch)' : '' }}
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
                                <select class="form-select select2-single" id="destination_location_id" name="destination_location_id" required disabled>
                                    <option value="">Select Location</option>
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
                                       value="{{ old('transfer_date', date('Y-m-d')) }}" required>
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
                                       value="{{ old('reference', 'TRF-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT)) }}" 
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
                                          placeholder="Additional notes about this transfer">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Items Section -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="bx bx-package me-2"></i>Items to Transfer
                                </h6>
                                <button type="button" class="btn btn-outline-light" id="add-item">
                                    <i class="bx bx-plus me-1"></i>Add Item
                                </button>
                            </div>
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
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('inventory.transfers.index') }}" class="btn btn-secondary px-5">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-5" id="submit-btn" disabled>
                            <i class="bx bx-transfer me-1"></i>Create Transfer
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
                    <select class="form-select select2-modal" id="modal_item_id">
                        <option value="">Select an item...</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" 
                                    data-name="{{ $item->name }}"
                                    data-code="{{ $item->code }}"
                                    data-stock="{{ $locationStocks[$item->id] ?? 0 }}"
                                    data-unit="{{ $item->unit_of_measure }}">
                                {{ $item->name }} ({{ $item->code }}) - Stock: {{ $locationStocks[$item->id] ?? 0 }} {{ $item->unit_of_measure }}
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

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('.select2-single').select2({ theme: 'bootstrap-5', width: '100%' });
        $('.select2-modal').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#itemModal') });
    }

    // Re-init after loading locations
    $('#destination_branch_id').on('change', function() {
        // allow existing handler to run; after AJAX success, re-init select2
        $(document).one('ajaxSuccess', function() {
            if ($.fn.select2) {
                const $loc = $('#destination_location_id');
                if ($loc.data('select2')) { $loc.select2('destroy'); }
                $loc.select2({ theme: 'bootstrap-5', width: '100%' });
            }
        });
    });

    // Ensure modal select2 shows correctly each time modal opens
    $('#itemModal').on('shown.bs.modal', function() {
        if ($.fn.select2) {
            const $sel = $('#modal_item_id');
            if ($sel.data('select2')) { $sel.select2('destroy'); }
            $sel.select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#itemModal') });
        }
    });

    let itemCounter = 0;

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

    // Item selection change in modal
    $('#modal_item_id').change(function() {
        const selectedOption = $(this).find(':selected');
        const stock = selectedOption.data('stock');
        const unit = selectedOption.data('unit');
        
        $('#available_stock').text(stock);
        $('#available_unit').text(unit);
        $('#modal_unit_display').text(unit);
        $('#modal_quantity').attr('max', stock);
    });

    // Add item to table
    $('#add-item-to-table').click(function() {
        const selectedOption = $('#modal_item_id option:selected');
        const itemId = $('#modal_item_id').val();
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
        $('#modal_item_id').val('');
        $('#modal_quantity').val(1);
        $('#available_stock').text('0');
        $('#available_unit').text('units');
        $('#modal_unit_display').text('Unit');
    }
});
</script>
@if(session('error'))
<script nonce="{{ $cspNonce ?? '' }}">
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Action not allowed',
                text: @json(session('error')),
                confirmButtonText: 'OK'
            });
        }
    });
</script>
@endif
@endpush
@endsection
