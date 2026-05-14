@extends('layouts.main')

@section('title', 'Create Transfer Request')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Management', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Transfer Requests', 'url' => route('inventory.transfer-requests.index'), 'icon' => 'bx bx-message-square-dots'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h6 class="mb-0 text-uppercase">CREATE TRANSFER REQUEST</h6>
                <p class="mb-0 text-muted">Transfer requests items between branches or between locations within your branch</p>
            </div>
            <a href="{{ route('inventory.transfer-requests.index') }}" class="btn btn-secondary">
                <i class="bx bx-arrow-back me-1"></i>Back to Transfers Requests
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
                <form action="{{ route('inventory.transfer-requests.store') }}" method="POST" id="transferRequestForm">
                    @csrf
                    <!-- Transfer Details -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="destination_branch_id" class="form-label">Destination Branch <span class="text-danger">*</span></label>
                                <select class="form-select select2-single" id="destination_branch_id" name="destination_branch_id" required>
                                    <option value="">Select Destination Branch</option>
                                    @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('destination_branch_id') == $branch->id ? 'selected' : '' }}>
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
                                <label for="to_location_id" class="form-label">Destination Location <span class="text-danger">*</span></label>
                                <select class="form-select select2-single" id="to_location_id" name="to_location_id" required disabled>
                                    <option value="">Select Location</option>
                                </select>
                                @error('to_location_id')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">From Location</label>
                                <input type="text" class="form-control" value="{{ \App\Models\InventoryLocation::find($loginLocationId)->name ?? 'No location selected' }}" readonly>
                                <small class="text-muted">Current login location</small>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                                <input type="text" name="reason" id="reason" class="form-control @error('reason') is-invalid @enderror" 
                                       placeholder="e.g., Stock redistribution, Emergency transfer" required>
                                @error('reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"
                                    placeholder="Additional notes about this transfer request">{{ old('notes') }}</textarea>
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
                        <a href="{{ route('inventory.transfer-requests.index') }}" class="btn btn-secondary px-5">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-5" id="submit-btn" disabled>
                            <i class="bx bx-message-square-dots me-1"></i>Create Transfer Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Hidden items template for modal options -->
<select id="modal_items_source" class="d-none">
    @foreach($items as $item)
    <option value="{{ $item->id }}"
            data-name="{{ $item->name }}"
            data-code="{{ $item->code ?? $item->item_code }}"
            data-unit="{{ $item->unit_of_measure }}"
            data-stock="{{ $locationStocks[$item->id] ?? 0 }}">
        {{ $item->name }} ({{ $item->code ?? $item->item_code }}) - Stock: {{ $locationStocks[$item->id] ?? 0 }} {{ $item->unit_of_measure }}
    </option>
    @endforeach
    
</select>


@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Enhance selects (init explicitly to avoid cross-binding)
    if (typeof $ !== 'undefined' && $.fn.select2) {
        const $branch = $('#destination_branch_id');
        const $location = $('#to_location_id');
        if ($branch.length) {
            $branch.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select Destination Branch',
                allowClear: true
            });
        }
        if ($location.length) {
            $location.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select Location',
                allowClear: true
            });
        }
    }

    // Load locations by selected branch
    $('#destination_branch_id').on('change', function() {
        const branchId = $(this).val();
        const $loc = $('#to_location_id');
        $loc.html('<option value="">Select Location</option>').prop('disabled', true);

        if (!branchId) return;

        $.ajax({
            url: '/api/branches/' + branchId + '/locations',
            method: 'GET',
            success: function(data) {
                data.forEach(function(location) {
                    $loc.append($('<option></option>').attr('value', location.id).text(location.name));
                });
                $loc.prop('disabled', false);
                if ($loc.data('select2')) {
                    $loc.trigger('change.select2');
                }
            },
            error: function() {
                alert('Failed to load locations.');
            }
        });
    });

    // Add Item popup
    $('#add-item').on('click', function() {
        const modalHtml = `
        <div class="modal fade" id="trqItemModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Item to Transfer Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select Item</label>
                            <select class="form-select select2-single" id="trq_modal_item">
                                <option value="">Select an item...</option>
                                ${$('#modal_items_source').html()}
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transfer Quantity</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="trq_modal_qty" value="1" step="0.01" min="0.01">
                                <span class="input-group-text" id="trq_modal_unit">Unit</span>
                            </div>
                            <small class="text-muted">Available: <span id="trq_available_stock">0</span> <span id="trq_available_unit">units</span></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="trq_add_item_confirm">Add Item</button>
                    </div>
                </div>
            </div>
        </div>`;

        // Append once
        if (!document.getElementById('trqItemModal')) {
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }
        const $modal = $('#trqItemModal');

        // Initialize select2 when modal is shown
        $modal.off('shown.bs.modal').on('shown.bs.modal', function() {
            if ($.fn.select2) {
                const $sel = $('#trq_modal_item');
                if ($sel.data('select2')) {
                    $sel.select2('destroy');
                }
                $sel.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $modal
                });
            }
        });

        // Clean up on hide
        $modal.off('hidden.bs.modal').on('hidden.bs.modal', function() {
            const $sel = $('#trq_modal_item');
            if ($sel.data('select2')) {
                $sel.select2('destroy');
            }
        });

        // Change handler to update stock/unit
        $modal.off('change', '#trq_modal_item').on('change', '#trq_modal_item', function() {
            const $opt = $(this).find(':selected');
            const stock = parseFloat($opt.data('stock')) || 0;
            const unit = $opt.data('unit') || 'units';
            $('#trq_available_stock').text(stock);
            $('#trq_available_unit').text(unit);
            $('#trq_modal_unit').text(unit);
            $('#trq_modal_qty').attr('max', stock);
        });

        // Confirm add
        $modal.off('click', '#trq_add_item_confirm').on('click', '#trq_add_item_confirm', function() {
            const $sel = $('#trq_modal_item');
            const itemId = $sel.val();
            const $opt = $sel.find(':selected');
            const itemName = $opt.data('name');
            const itemCode = $opt.data('code');
            const unit = $opt.data('unit') || 'units';
            const stock = parseFloat($opt.data('stock')) || 0;
            const qty = parseFloat($('#trq_modal_qty').val());

            if (!itemId) return alert('Please select an item.');
            if (!qty || qty <= 0) return alert('Enter a valid quantity.');
            if (qty > stock) return alert('Transfer quantity cannot exceed available stock.');
            if ($(`#itemsTable tbody tr[data-item-id='${itemId}']`).length) return alert('Item already added.');

            const row = `
            <tr data-item-id="${itemId}">
                <td>
                    <input type="hidden" name="item_id" value="${itemId}">
                    <strong>${itemName}</strong><br>
                    <small class="text-muted">${itemCode || ''}</small>
                </td>
                <td>${stock} ${unit}</td>
                <td>
                    <input type="number" step="0.01" name="quantity" class="form-control form-control-sm" value="${qty}" min="0.01" max="${stock}" required>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-item"><i class="bx bx-trash"></i></button>
                </td>
            </tr>`;

            $('#itemsTable tbody').append(row);
            $('#no-items-message').hide();
            $('#submit-btn').prop('disabled', false);
            $modal.modal('hide');
        });

        $modal.modal('show');
    });

    // Remove item row
    $(document).on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        if (!$('#itemsTable tbody tr').length) {
            $('#no-items-message').show();
            $('#submit-btn').prop('disabled', true);
        }
    });
});
</script>
@endpush
@endsection