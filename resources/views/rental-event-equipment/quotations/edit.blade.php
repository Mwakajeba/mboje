@extends('layouts.main')

@section('title', 'Edit Rental Quotation')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Rental Quotations', 'url' => route('rental-event-equipment.quotations.index'), 'icon' => 'bx bx-file-blank'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">EDIT RENTAL QUOTATION</h6>
        <hr />

        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bx bx-file-blank me-2"></i>Edit Rental Quotation: {{ $quotation->quotation_number }}</h5>
            </div>
            <div class="card-body">
                <form id="quotation-form" method="POST" action="{{ route('rental-event-equipment.quotations.update', $quotation) }}">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <!-- Customer Information -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select class="form-select select2-single" id="customer_id" name="customer_id" required>
                                        <option value="">Select Customer</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ $quotation->customer_id == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }} - {{ $customer->phone }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-primary" id="add-customer-btn" title="Add New Customer">
                                        <i class="bx bx-plus me-1"></i>Add Customer
                                    </button>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="quotation_date" class="form-label">Quotation Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="quotation_date" name="quotation_date" 
                                       value="{{ $quotation->quotation_date->format('Y-m-d') }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="valid_until" class="form-label">Valid Until <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="valid_until" name="valid_until" 
                                       value="{{ $quotation->valid_until->format('Y-m-d') }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="event_date" class="form-label">Event Date</label>
                                <input type="date" class="form-control" id="event_date" name="event_date" 
                                       value="{{ $quotation->event_date ? $quotation->event_date->format('Y-m-d') : '' }}">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="event_location" class="form-label">Event Location</label>
                                <input type="text" class="form-control" id="event_location" name="event_location" 
                                       value="{{ $quotation->event_location }}" placeholder="Event location">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Equipment Items Section -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Equipment Items</h6>
                                <button type="button" class="btn btn-primary btn-sm" id="add-equipment-btn">
                                    <i class="bx bx-plus me-1"></i>Add Equipment
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table" id="equipment-table">
                                    <thead>
                                        <tr>
                                            <th width="30%">Equipment</th>
                                            <th width="15%">Quantity</th>
                                            <th width="15%">Rental Rate/Day</th>
                                            <th width="15%">Days</th>
                                            <th width="15%">Total</th>
                                            <th width="10%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="equipment-tbody">
                                        @foreach($quotation->items as $index => $item)
                                        <tr data-equipment-id="{{ $item->equipment_id }}" data-item-id="item_{{ $index }}">
                                            <td>
                                                <strong>{{ $item->equipment->name ?? 'N/A' }}</strong><br>
                                                @if($item->equipment->equipment_code)
                                                <small class="text-muted">{{ $item->equipment->equipment_code }}</small><br>
                                                @endif
                                                <small class="text-muted">{{ $item->equipment->category->name ?? 'N/A' }}</small><br>
                                                <small class="text-info">Available: {{ $item->equipment->quantity_available ?? 0 }}</small>
                                                <input type="hidden" name="items[{{ $index }}][equipment_id]" value="{{ $item->equipment_id }}">
                                                <input type="hidden" name="items[{{ $index }}][notes]" value="{{ $item->notes ?? '' }}">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm quantity-input" 
                                                       name="items[{{ $index }}][quantity]" value="{{ $item->quantity }}" min="1" 
                                                       data-item-id="item_{{ $index }}" required>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm rate-input" 
                                                       name="items[{{ $index }}][rental_rate]" value="{{ $item->rental_rate }}" step="0.01" min="0" 
                                                       data-item-id="item_{{ $index }}" required>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm days-input" 
                                                       name="items[{{ $index }}][rental_days]" value="{{ $item->rental_days }}" min="1" 
                                                       data-item-id="item_{{ $index }}" required>
                                            </td>
                                            <td class="item-total">{{ number_format($item->total_amount, 2) }}</td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-danger remove-item">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                            <td><strong id="subtotal">{{ number_format($quotation->subtotal, 2) }}</strong></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" class="text-end">
                                                <strong>Discount:</strong>
                                                <select class="form-select form-select-sm d-inline-block ms-2" style="width: 120px;" id="discount_type" name="discount_type">
                                                    <option value="">None</option>
                                                    <option value="percentage" {{ $quotation->discount_type == 'percentage' ? 'selected' : '' }}>Percentage</option>
                                                    <option value="fixed" {{ $quotation->discount_type == 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                                </select>
                                                <input type="number" class="form-control form-control-sm d-inline-block ms-2" style="width: 120px;" 
                                                       id="discount_amount" name="discount_amount" value="{{ $quotation->discount_amount }}" step="0.01" min="0" placeholder="0.00">
                                            </td>
                                            <td><strong id="discount-display">{{ number_format($quotation->discount_amount, 2) }}</strong></td>
                                            <td></td>
                                        </tr>
                                        <tr class="table-success">
                                            <td colspan="4" class="text-end"><strong>Total Amount:</strong></td>
                                            <td><strong id="total-amount">{{ number_format($quotation->total_amount, 2) }}</strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Notes and Terms -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="4" 
                                          placeholder="Additional notes...">{{ $quotation->notes }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="terms_conditions" class="form-label">Terms & Conditions</label>
                                <textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="4" 
                                          placeholder="Terms and conditions...">{{ $quotation->terms_conditions }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="{{ route('rental-event-equipment.quotations.show', $quotation) }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-success" id="submit-btn">
                            <i class="bx bx-check me-1"></i>Update Quotation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Customer Modal (same as create) -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="add-customer-errors" class="alert alert-danger d-none"></div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="customer_name" class="form-label">Customer Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="customer_name" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="customer_phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="customer_phone" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="customer_email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="customer_email">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="customer_status" class="form-label">Status</label>
                            <select class="form-select" id="customer_status">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-customer-btn">
                    <i class="bx bx-save me-1"></i>Save Customer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Equipment Selection Modal (same as create) -->
<div class="modal fade" id="equipmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Equipment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="modal_equipment_id" class="form-label">Select Equipment <span class="text-danger">*</span></label>
                    <select class="form-select select2-modal" id="modal_equipment_id">
                        <option value="">Choose an equipment...</option>
                        @foreach($equipment as $item)
                            <option value="{{ $item->id }}"
                                    data-name="{{ $item->name }}"
                                    data-code="{{ $item->equipment_code ?? '' }}"
                                    data-category="{{ $item->category->name ?? 'N/A' }}"
                                    data-available="{{ $item->quantity_available ?? 0 }}"
                                    data-rate="{{ $item->rental_rate ?? 0 }}">
                                {{ $item->name }}@if($item->equipment_code) ({{ $item->equipment_code }})@endif - Rate: {{ number_format($item->rental_rate ?? 0, 2) }}/day - Available: {{ $item->quantity_available ?? 0 }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="modal_quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="modal_quantity" value="1" step="1" min="1">
                            <small class="text-muted" id="modal_available_info">Available: 0</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="modal_rental_rate" class="form-label">Rental Rate/Day <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="modal_rental_rate" step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="modal_rental_days" class="form-label">Rental Days <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="modal_rental_days" value="1" step="1" min="1">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="modal_equipment_notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="modal_equipment_notes" rows="2" placeholder="Optional notes for this equipment..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Line Total</label>
                    <div class="border rounded p-2 bg-light">
                        <span class="fw-bold" id="modal-line-total">0.00</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="add-equipment-to-table">Add Equipment</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let itemCounter = {{ $quotation->items->count() }};

    // Initialize Select2
    $('.select2-single').select2({
        placeholder: 'Select',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap-5'
    });

    // Initialize Select2 for modal with search - only initialize once
    let select2ModalInitialized = false;
    $('#equipmentModal').on('shown.bs.modal', function() {
        if (!select2ModalInitialized && $('#modal_equipment_id').length && !$('#modal_equipment_id').hasClass('select2-hidden-accessible')) {
            $('#modal_equipment_id').select2({
                placeholder: 'Search for equipment...',
                allowClear: true,
                width: '100%',
                theme: 'bootstrap-5',
                dropdownParent: $('#equipmentModal'),
                minimumInputLength: 0
            });
            select2ModalInitialized = true;
        }
    });

    // Add Customer Modal
    $('#add-customer-btn').on('click', function() {
        $('#add-customer-errors').addClass('d-none').empty();
        $('#customer_name, #customer_phone, #customer_email').val('');
        $('#customer_status').val('active');
        $('#customerModal').modal('show');
    });

    // Save Customer (same as create)
    $('#save-customer-btn').on('click', function() {
        function normalizePhone(phone) {
            let p = (phone || '').replace(/[^0-9+]/g, '');
            if (p.startsWith('+255')) p = '255' + p.slice(4);
            else if (p.startsWith('0')) p = '255' + p.slice(1);
            else if (/^\d{9}$/.test(p)) p = '255' + p;
            return p;
        }

        const name = $('#customer_name').val().trim();
        const phone = normalizePhone($('#customer_phone').val().trim());
        const email = $('#customer_email').val().trim();
        const status = $('#customer_status').val();

        if (!name || !phone) {
            $('#add-customer-errors').removeClass('d-none').html('<div>Name and phone are required.</div>');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        $.ajax({
            url: '{{ route("customers.store") }}',
            method: 'POST',
            data: { name, phone, email, status, _token: '{{ csrf_token() }}' },
            success: function(res) {
                if (res.customer && res.customer.id) {
                    const newOption = new Option(`${res.customer.name} - ${res.customer.phone}`, res.customer.id, true, true);
                    $('#customer_id').append(newOption).trigger('change');
                    $('#customerModal').modal('hide');
                    Swal.fire('Success', 'Customer created successfully!', 'success');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorHtml = '';
                    Object.values(errors).forEach(err => {
                        errorHtml += '<div>' + err[0] + '</div>';
                    });
                    $('#add-customer-errors').removeClass('d-none').html(errorHtml);
                } else {
                    $('#add-customer-errors').removeClass('d-none').html('<div>Failed to create customer.</div>');
                }
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Customer');
            }
        });
    });

    // Add Equipment Button
    $('#add-equipment-btn').click(function() {
        $('#equipmentModal').modal('show');
        resetEquipmentModal();
    });

    // Equipment selection change - populate fields
    $('#modal_equipment_id').change(function() {
        const selectedOption = $(this).find(':selected');
        if (!selectedOption.val()) {
            resetEquipmentModal();
            return;
        }

        const rate = parseFloat(selectedOption.data('rate')) || 0;
        const available = parseInt(selectedOption.data('available')) || 0;

        $('#modal_rental_rate').val(rate);
        $('#modal_quantity').attr('max', available);
        $('#modal_available_info').text(`Available: ${available}`);
        
        calculateModalLineTotal();
    });

    // Calculate modal line total
    function calculateModalLineTotal() {
        const quantity = parseFloat($('#modal_quantity').val()) || 0;
        const rate = parseFloat($('#modal_rental_rate').val()) || 0;
        const days = parseFloat($('#modal_rental_days').val()) || 0;
        const total = quantity * rate * days;
        $('#modal-line-total').text(total.toFixed(2));
    }

    // Modal input changes
    $('#modal_quantity, #modal_rental_rate, #modal_rental_days').on('input', function() {
        calculateModalLineTotal();
    });

    // Reset equipment modal
    function resetEquipmentModal() {
        if ($('#modal_equipment_id').hasClass('select2-hidden-accessible')) {
            $('#modal_equipment_id').val(null).trigger('change.select2');
        } else {
            $('#modal_equipment_id').val('');
        }
        $('#modal_quantity').val(1);
        $('#modal_rental_rate').val('');
        $('#modal_rental_days').val(1);
        $('#modal_equipment_notes').val('');
        $('#modal_available_info').text('Available: 0');
        $('#modal-line-total').text('0.00');
    }

    // Add equipment to table
    $('#add-equipment-to-table').click(function() {
        const equipmentId = $('#modal_equipment_id').val();
        if (!equipmentId) {
            Swal.fire('Error', 'Please select an equipment item.', 'error');
            return;
        }

        const selectedOption = $('#modal_equipment_id').find(':selected');
        const name = selectedOption.data('name') || '';
        const code = selectedOption.data('code') || '';
        const category = selectedOption.data('category') || 'N/A';
        const available = parseInt(selectedOption.data('available')) || 0;
        const quantity = parseInt($('#modal_quantity').val()) || 1;
        const rate = parseFloat($('#modal_rental_rate').val()) || 0;
        const days = parseInt($('#modal_rental_days').val()) || 1;
        const notes = $('#modal_equipment_notes').val() || '';

        if (quantity > available) {
            Swal.fire('Error', `Quantity cannot exceed available stock (${available}).`, 'error');
            return;
        }

        if ($(`tr[data-equipment-id="${equipmentId}"]`).length > 0) {
            Swal.fire('Warning', 'This equipment is already added to the quotation.', 'warning');
            return;
        }

        const itemId = 'item_' + itemCounter++;
        const total = quantity * rate * days;
        const rowHtml = `
            <tr data-equipment-id="${equipmentId}" data-item-id="${itemId}">
                <td>
                    <strong>${name}</strong><br>
                    ${code ? `<small class="text-muted">${code}</small><br>` : ''}
                    <small class="text-muted">${category}</small><br>
                    <small class="text-info">Available: ${available}</small>
                    <input type="hidden" name="items[${itemId}][equipment_id]" value="${equipmentId}">
                    <input type="hidden" name="items[${itemId}][notes]" value="${notes}">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm quantity-input" 
                           name="items[${itemId}][quantity]" value="${quantity}" min="1" max="${available}" 
                           data-item-id="${itemId}" required>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm rate-input" 
                           name="items[${itemId}][rental_rate]" value="${rate}" step="0.01" min="0" 
                           data-item-id="${itemId}" required>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm days-input" 
                           name="items[${itemId}][rental_days]" value="${days}" min="1" 
                           data-item-id="${itemId}" required>
                </td>
                <td class="item-total">${total.toFixed(2)}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-item">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#equipment-tbody').append(rowHtml);
        calculateTotals();
        resetEquipmentModal();
        $('#equipmentModal').modal('hide');
    });

    // Remove Item
    $(document).on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        calculateTotals();
    });

    // Calculate totals on input change
    $(document).on('input', '.quantity-input, .rate-input, .days-input, #discount_amount', function() {
        calculateTotals();
    });

    $(document).on('change', '#discount_type', function() {
        calculateTotals();
    });

    function calculateTotals() {
        let subtotal = 0;
        $('#equipment-tbody tr').each(function() {
            const quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
            const rate = parseFloat($(this).find('.rate-input').val()) || 0;
            const days = parseFloat($(this).find('.days-input').val()) || 0;
            const total = quantity * rate * days;
            $(this).find('.item-total').text(total.toFixed(2));
            subtotal += total;
        });

        $('#subtotal').text(subtotal.toFixed(2));

        const discountType = $('#discount_type').val();
        let discountAmount = parseFloat($('#discount_amount').val()) || 0;
        
        if (discountType === 'percentage' && discountAmount > 0) {
            discountAmount = (subtotal * discountAmount) / 100;
        }

        $('#discount-display').text(discountAmount.toFixed(2));
        const total = subtotal - discountAmount;
        $('#total-amount').text(total.toFixed(2));
    }

    // Initialize calculations
    calculateTotals();

    // Form submission
    $('#quotation-form').on('submit', function(e) {
        e.preventDefault();
        
        if ($('#equipment-tbody tr').length === 0) {
            Swal.fire('Error', 'Please add at least one equipment item.', 'error');
            return;
        }

        const formData = new FormData();
        
        $(this).find('input, select, textarea').not('[name*="items["]').each(function() {
            const $field = $(this);
            const name = $field.attr('name');
            const type = $field.attr('type');
            
            if (name) {
                if (type === 'checkbox') {
                    if ($field.is(':checked')) {
                        formData.append(name, $field.val() || '1');
                    }
                } else if (type === 'radio') {
                    if ($field.is(':checked')) {
                        formData.append(name, $field.val());
                    }
                } else {
                    const value = $field.val();
                    if (value !== null && value !== undefined && value !== '') {
                        formData.append(name, value);
                    }
                }
            }
        });

        const items = [];
        $('#equipment-tbody tr').each(function() {
            const equipmentId = $(this).find('input[name*="[equipment_id]"]').val();
            const quantity = $(this).find('input[name*="[quantity]"]').val();
            const rentalRate = $(this).find('input[name*="[rental_rate]"]').val();
            const rentalDays = $(this).find('input[name*="[rental_days]"]').val();
            const notes = $(this).find('input[name*="[notes]"]').val() || '';

            if (equipmentId && quantity && rentalRate && rentalDays) {
                items.push({
                    equipment_id: equipmentId,
                    quantity: quantity,
                    rental_rate: rentalRate,
                    rental_days: rentalDays,
                    notes: notes
                });
            }
        });

        items.forEach((item, index) => {
            formData.append(`items[${index}][equipment_id]`, item.equipment_id);
            formData.append(`items[${index}][quantity]`, item.quantity);
            formData.append(`items[${index}][rental_rate]`, item.rental_rate);
            formData.append(`items[${index}][rental_days]`, item.rental_days);
            if (item.notes) {
                formData.append(`items[${index}][notes]`, item.notes);
            }
        });

        const submitBtn = $('#submit-btn');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Updating...');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                Swal.fire('Success', 'Quotation updated successfully!', 'success').then(() => {
                    window.location.href = '{{ route("rental-event-equipment.quotations.index") }}';
                });
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Update Quotation');
                
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMsg = 'Validation errors:\n';
                    Object.keys(errors).forEach(key => {
                        if (Array.isArray(errors[key])) {
                            errorMsg += errors[key][0] + '\n';
                        } else {
                            errorMsg += errors[key] + '\n';
                        }
                    });
                    Swal.fire('Error', errorMsg, 'error');
                } else {
                    Swal.fire('Error', 'Failed to update quotation. Please try again.', 'error');
                }
            }
        });
    });
});
</script>
@endpush
