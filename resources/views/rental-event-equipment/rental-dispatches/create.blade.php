@extends('layouts.main')

@section('title', 'Create Rental Dispatch')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Rental Dispatches', 'url' => route('rental-event-equipment.rental-dispatches.index'), 'icon' => 'bx bx-send'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">CREATE RENTAL DISPATCH</h6>
        <hr />

        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bx bx-send me-2"></i>New Rental Dispatch</h5>
            </div>
            <div class="card-body">
                <form id="dispatch-form" method="POST" action="{{ route('rental-event-equipment.rental-dispatches.store') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="contract_id" class="form-label">Contract <span class="text-danger">*</span></label>
                                <select class="form-select select2-single" id="contract_id" name="contract_id" required>
                                    <option value="">Select Contract</option>
                                    @foreach($contracts as $contract)
                                        <option value="{{ $contract->id }}"
                                                {{ isset($selectedContractId) && $selectedContractId == $contract->id ? 'selected' : '' }}
                                                data-customer="{{ $contract->customer->name }}"
                                                data-event-date="{{ $contract->event_date ? $contract->event_date->format('Y-m-d') : '' }}"
                                                data-event-location="{{ $contract->event_location ?? '' }}"
                                                data-items="{{ json_encode($contract->items->map(function($item) {
                                                    return [
                                                        'equipment_id' => $item->equipment_id,
                                                        'equipment_name' => $item->equipment->name ?? 'N/A',
                                                        'category' => $item->equipment->category->name ?? 'N/A',
                                                        'quantity' => $item->quantity,
                                                        'available' => $item->equipment->quantity_available ?? 0
                                                    ];
                                                })) }}">
                                            {{ $contract->contract_number }} - {{ $contract->customer->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="dispatch_date" class="form-label">Dispatch Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="dispatch_date" name="dispatch_date" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="expected_return_date" class="form-label">Expected Return Date</label>
                                <input type="date" class="form-control" id="expected_return_date" name="expected_return_date">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="event_date" class="form-label">Event Date</label>
                                <input type="date" class="form-control" id="event_date" name="event_date"
                                       value="{{ isset($selectedContract) && $selectedContract->event_date ? $selectedContract->event_date->format('Y-m-d') : '' }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="event_location" class="form-label">Event Location</label>
                                <input type="text" class="form-control" id="event_location" name="event_location"
                                       placeholder="Event location"
                                       value="{{ isset($selectedContract) && $selectedContract->event_location ? $selectedContract->event_location : '' }}">
                            </div>
                        </div>
                    </div>

                    <!-- Equipment Items Section -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="mb-0">Equipment Items</h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="bx bx-info-circle me-2"></i>
                                Select a contract to load equipment items. You can adjust quantities for dispatch.
                            </div>
                            <div class="table-responsive">
                                <table class="table" id="equipment-table">
                                    <thead>
                                        <tr>
                                            <th width="5%">Select</th>
                                            <th width="35%">Equipment</th>
                                            <th width="15%">Contract Qty</th>
                                            <th width="15%">Available</th>
                                            <th width="15%">Dispatch Qty</th>
                                            <th width="15%">Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody id="equipment-tbody">
                                        <!-- Items will be loaded dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="{{ route('rental-event-equipment.rental-dispatches.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-info">
                            <i class="bx bx-check me-1"></i>Create Dispatch
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
        placeholder: 'Select Contract',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap-5'
    });

    // Auto-load equipment and populate event fields if contract is pre-selected
    @if(isset($selectedContractId) && $selectedContractId && isset($selectedContract))
    $(document).ready(function() {
        // Populate event date and location from selected contract
        @if($selectedContract->event_date)
        $('#event_date').val('{{ $selectedContract->event_date->format('Y-m-d') }}');
        @endif
        @if($selectedContract->event_location)
        $('#event_location').val('{{ $selectedContract->event_location }}');
        @endif

        // Trigger change event after Select2 is initialized to load equipment items
        setTimeout(function() {
            $('#contract_id').trigger('change');
        }, 100);
    });
    @endif

    $('#contract_id').on('change', function() {
        const selectedOption = $(this).find(':selected');
        const items = selectedOption.data('items') || [];
        const eventDate = selectedOption.data('event-date') || '';
        const eventLocation = selectedOption.data('event-location') || '';
        const tbody = $('#equipment-tbody');
        tbody.empty();

        // Auto-populate event date and location from contract
        if (eventDate) {
            $('#event_date').val(eventDate);
        }
        if (eventLocation) {
            $('#event_location').val(eventLocation);
        }

        if (items.length === 0) {
            tbody.append('<tr><td colspan="6" class="text-center text-muted">No equipment items found for this contract.</td></tr>');
            return;
        }

        items.forEach(function(item, index) {
            // Dispatch quantity is limited by the contract quantity
            const maxContractQty = item.quantity;
            const defaultQty = maxContractQty > 0 ? maxContractQty : 0;

            const row = `
                <tr>
                    <td>
                        <input type="checkbox" class="form-check-input item-checkbox"
                               data-equipment-id="${item.equipment_id}"
                               data-max-qty="${item.quantity}">
                    </td>
                    <td>
                        <strong>${item.equipment_name}</strong><br>
                        <small class="text-muted">${item.category}</small>
                    </td>
                    <td>${item.quantity}</td>
                    <td>
                        ${item.available}
                        ${item.available < item.quantity ? '<br><small class="text-warning">(' + (item.quantity - item.available) + ' not available)</small>' : ''}
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm dispatch-qty"
                               name="items[${index}][quantity]"
                               data-equipment-id="${item.equipment_id}"
                               min="1" max="${maxContractQty}"
                               value="${defaultQty}"
                               disabled>
                        <input type="hidden" name="items[${index}][equipment_id]" value="${item.equipment_id}" disabled>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm"
                               name="items[${index}][notes]" placeholder="Notes" disabled>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    });

    $(document).on('change', '.item-checkbox', function() {
        const row = $(this).closest('tr');
        const qtyInput = row.find('.dispatch-qty');
        const hiddenInput = row.find('input[name*="[equipment_id]"]');
        const notesInput = row.find('input[name*="[notes]"]');
        const maxContractQty = parseInt($(this).data('max-qty')) || 0;

        if ($(this).is(':checked')) {
            if (maxContractQty > 0) {
                qtyInput.prop('disabled', false).prop('required', true);
                hiddenInput.prop('disabled', false);
                notesInput.prop('disabled', false);
            } else {
                $(this).prop('checked', false);
                Swal.fire('Warning', 'This item has no quantity on the contract to dispatch.', 'warning');
            }
        } else {
            qtyInput.prop('disabled', true).prop('required', false).val('');
            hiddenInput.prop('disabled', true);
            notesInput.prop('disabled', true).val('');
        }
    });

    // Validate dispatch quantity doesn't exceed contract quantity when user changes it
    $(document).on('change', '.dispatch-qty', function() {
        const checkbox = $(this).closest('tr').find('.item-checkbox');
        const maxContractQty = parseInt(checkbox.data('max-qty')) || 0;
        let enteredQty = parseInt($(this).val()) || 0;

        if (enteredQty <= 0) {
            enteredQty = 1;
            $(this).val(enteredQty);
        }

        if (enteredQty > maxContractQty) {
            $(this).val(maxContractQty);
            Swal.fire(
                'Warning',
                'Dispatch quantity cannot exceed the contract quantity (' + maxContractQty + ').',
                'warning'
            );
        }
    });

    $('#dispatch-form').on('submit', function(e) {
        e.preventDefault(); // Prevent default submission to handle it manually

        const checkedItems = $('.item-checkbox:checked');
        if (checkedItems.length === 0) {
            Swal.fire('Error', 'Please select at least one equipment item to dispatch.', 'error');
            return false;
        }

        // Validate contract is selected
        const contractId = $('#contract_id').val();
        if (!contractId) {
            Swal.fire('Error', 'Please select a contract.', 'error');
            return false;
        }

        // Build items array from checked items only
        const items = [];
        checkedItems.each(function() {
            const $row = $(this).closest('tr');
            const equipmentId = $row.find('input[name*="[equipment_id]"]').val();
            const quantity = $row.find('input[name*="[quantity]"]').val();
            const notes = $row.find('input[name*="[notes]"]').val() || '';

            if (equipmentId && quantity) {
                items.push({
                    equipment_id: equipmentId,
                    quantity: quantity,
                    notes: notes
                });
            }
        });

        if (items.length === 0) {
            Swal.fire('Error', 'Please select at least one equipment item to dispatch.', 'error');
            return false;
        }

        // Create a temporary form to submit with proper data structure
        const form = $(this);
        const formData = new FormData(form[0]);

        // Clear existing items from form data
        const keysToRemove = [];
        for (let key of formData.keys()) {
            if (key.startsWith('items[')) {
                keysToRemove.push(key);
            }
        }
        keysToRemove.forEach(key => formData.delete(key));

        // Add properly indexed items
        items.forEach((item, index) => {
            formData.append(`items[${index}][equipment_id]`, item.equipment_id);
            formData.append(`items[${index}][quantity]`, item.quantity);
            if (item.notes) {
                formData.append(`items[${index}][notes]`, item.notes);
            }
        });

        // Show loading state
        const submitBtn = form.find('button[type="submit"]');
        const originalHtml = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Creating...');

        // Submit via AJAX to handle errors properly
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                // If response is a redirect, follow it
                if (response.redirect) {
                    window.location.href = response.redirect;
                } else {
                    window.location.href = '{{ route("rental-event-equipment.rental-dispatches.index") }}';
                }
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false).html(originalHtml);

                let errorMessage = 'Failed to create dispatch.';
                let errorDetails = '';

                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    if (xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        const errorList = [];
                        Object.keys(errors).forEach(key => {
                            const fieldErrors = Array.isArray(errors[key]) ? errors[key] : [errors[key]];
                            fieldErrors.forEach(err => errorList.push(err));
                        });
                        if (errorList.length > 0) {
                            errorDetails = errorList.join('<br>');
                        }
                    }
                } else if (xhr.responseText) {
                    // Try to extract error from HTML response
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(xhr.responseText, 'text/html');
                    const errorElement = doc.querySelector('.alert-danger, .error');
                    if (errorElement) {
                        errorMessage = errorElement.textContent.trim();
                    }
                }

                if (errorDetails) {
                    Swal.fire({
                        icon: 'error',
                        title: errorMessage,
                        html: errorDetails
                    });
                } else {
                    Swal.fire('Error', errorMessage, 'error');
                }
            }
        });

        return false;
    });
});
</script>
@endpush
