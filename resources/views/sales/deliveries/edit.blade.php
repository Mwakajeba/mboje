@extends('layouts.main')

@section('title', 'Edit Delivery')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Deliveries', 'url' => route('sales.deliveries.index'), 'icon' => 'bx bx-truck'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">EDIT DELIVERY</h6>
        <hr />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-truck me-2"></i>New Delivery</h5>
            </div>
            <div class="card-body">
                <form id="delivery-form">
                    @csrf
                    <div class="row">
                        <!-- Customer Information -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                <select class="form-select select2-single" id="customer_id" name="customer_id" required>
                                    <option value="">Select Customer</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ $customer->id == $delivery->customer_id ? 'selected' : '' }}>{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="delivery_date" class="form-label">Delivery Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="delivery_date" name="delivery_date" 
                                       value="{{ $delivery->delivery_date ? \Carbon\Carbon::parse($delivery->delivery_date)->format('Y-m-d') : date('Y-m-d') }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="delivery_time" class="form-label">Delivery Time</label>
                                <input type="time" class="form-control" id="delivery_time" name="delivery_time" value="{{ $delivery->delivery_time ? \Carbon\Carbon::parse($delivery->delivery_time)->format('H:i') : '' }}">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="delivery_type" class="form-label">Delivery Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="delivery_type" name="delivery_type" required>
                                    <option value="delivery" {{ $delivery->delivery_type == 'delivery' ? 'selected' : '' }}>Delivery</option>
                                    <option value="pickup" {{ $delivery->delivery_type == 'pickup' ? 'selected' : '' }}>Customer Pickup</option>
                                    <option value="shipping" {{ $delivery->delivery_type == 'shipping' ? 'selected' : '' }}>Shipping</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="contact_person" class="form-label">Contact Person</label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person" 
                                       placeholder="Name of contact person" value="{{ $delivery->contact_person }}">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="contact_phone" class="form-label">Contact Phone</label>
                                <input type="text" class="form-control" id="contact_phone" name="contact_phone" 
                                       placeholder="Contact phone number" value="{{ $delivery->contact_phone }}">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="delivery_address" class="form-label">Delivery Address</label>
                                <textarea class="form-control" id="delivery_address" name="delivery_address" rows="3" 
                                          placeholder="Full delivery address...">{{ $delivery->delivery_address }}</textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="delivery_instructions" class="form-label">Delivery Instructions</label>
                                <textarea class="form-control" id="delivery_instructions" name="delivery_instructions" rows="3" 
                                          placeholder="Special delivery instructions...">{{ $delivery->delivery_instructions }}</textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Transport Cost Section -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="has_transport_cost" name="has_transport_cost" value="1" {{ $delivery->has_transport_cost ? 'checked' : '' }}>
                                    <label class="form-check-label" for="has_transport_cost">
                                        <strong>Include Transport Cost</strong>
                                    </label>
                                    <small class="form-text text-muted d-block">Check if customer will pay for transport/delivery</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row" id="transport_cost_fields" style="display: none;">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="transport_cost" class="form-label">Transport Cost (Customer Pays)</label>
                                <input type="number" class="form-control" id="transport_cost" name="transport_cost" 
                                       step="0.01" min="0" placeholder="0.00" value="{{ $delivery->transport_cost ?? 0 }}">
                                <small class="form-text text-muted">Amount customer will pay for transport/delivery</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="vehicle_number" class="form-label">Vehicle Number</label>
                                <input type="text" class="form-control" id="vehicle_number" name="vehicle_number" 
                                       placeholder="Vehicle registration number" value="{{ $delivery->vehicle_number }}">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="driver_name" class="form-label">Driver Name</label>
                                <input type="text" class="form-control" id="driver_name" name="driver_name" 
                                       placeholder="Driver's name" value="{{ $delivery->driver_name }}">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="driver_phone" class="form-label">Driver Phone</label>
                                <input type="text" class="form-control" id="driver_phone" name="driver_phone" 
                                       placeholder="Driver's phone number" value="{{ $delivery->driver_phone }}">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Section -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Items</h6>
                                <button type="button" class="btn btn-primary btn-sm" id="add-item">
                                    <i class="bx bx-plus me-1"></i>Add Item
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table" id="items-table">
                                    <thead>
                                        <tr>
                                            <th width="30%">Item</th>
                                            <th width="12%">Quantity</th>
                                            <th width="12%">Unit Price</th>
                                            <th width="10%">VAT Type</th>
                                            <th width="10%">VAT Rate</th>
                                            <th width="12%">VAT Amount</th>
                                            <th width="14%">Line Total</th>
                                            <th width="10%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="items-tbody">
                                        @foreach($delivery->items as $item)
                                        <tr data-item-id="{{ $item->inventory_item_id }}">
                                            <td>
                                                <strong>{{ $item->item_name }}</strong><br>
                                                <small class="text-muted">{{ $item->item_code }}</small>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control item-quantity" 
                                                       value="{{ $item->quantity }}" step="0.01" min="0">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control item-price" 
                                                       value="{{ $item->unit_price }}" step="0.01" min="0">
                                            </td>
                                            <td>
                                                <select class="form-select item-vat-type">
                                                    <option value="no_vat" {{ $item->vat_type == 'no_vat' ? 'selected' : '' }}>No VAT</option>
                                                    <option value="inclusive" {{ $item->vat_type == 'inclusive' ? 'selected' : '' }}>Inclusive</option>
                                                    <option value="exclusive" {{ $item->vat_type == 'exclusive' ? 'selected' : '' }}>Exclusive</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control item-vat-rate" 
                                                       value="{{ $item->vat_rate }}" step="0.1" min="0" max="100">
                                            </td>
                                            <td class="vat-amount">{{ number_format($item->vat_amount, 2) }}</td>
                                            <td class="line-total">{{ number_format($item->line_total, 2) }}</td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-danger remove-item">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </td>
                                            <!-- Hidden inputs for form submission -->
                                            <input type="hidden" name="items[{{ $loop->index }}][inventory_item_id]" value="{{ $item->inventory_item_id }}">
                                            <input type="hidden" name="items[{{ $loop->index }}][quantity]" value="{{ $item->quantity }}">
                                            <input type="hidden" name="items[{{ $loop->index }}][unit_price]" value="{{ $item->unit_price }}">
                                            <input type="hidden" name="items[{{ $loop->index }}][vat_type]" value="{{ $item->vat_type }}">
                                            <input type="hidden" name="items[{{ $loop->index }}][vat_rate]" value="{{ $item->vat_rate }}">
                                            <input type="hidden" name="items[{{ $loop->index }}][vat_amount]" value="{{ $item->vat_amount }}">
                                            <input type="hidden" name="items[{{ $loop->index }}][line_total]" value="{{ $item->line_total }}">
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="6" class="text-end"><strong>Total Quantity:</strong></td>
                                            <td><strong id="total-quantity">0.00</strong></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="6" class="text-end"><strong>Total VAT:</strong></td>
                                            <td><strong id="total-vat">0.00</strong></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="6" class="text-end"><strong>Grand Total:</strong></td>
                                            <td><strong id="grand-total">0.00</strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Summary -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="bx bx-calculator me-2"></i>Delivery Summary</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Items Total:</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">TZS</span>
                                                    <input type="text" class="form-control" id="items-total" value="0.00" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6" id="transport-cost-display" style="display: none;">
                                            <div class="mb-3">
                                                <label class="form-label">Transport Cost:</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">TZS</span>
                                                    <input type="text" class="form-control" id="transport-cost-display-value" value="{{ number_format($delivery->transport_cost ?? 0, 2) }}" readonly>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Total VAT:</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">TZS</span>
                                                    <input type="text" class="form-control" id="total-vat-display" value="0.00" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label"><strong>Grand Total:</strong></label>
                                                <div class="input-group">
                                                    <span class="input-group-text">TZS</span>
                                                    <input type="text" class="form-control" id="grand-total-display" value="0.00" readonly style="font-weight: bold; font-size: 1.1em;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="4" 
                                          placeholder="Additional notes for this delivery...">{{ $delivery->notes }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('sales.deliveries.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="bx bx-check me-1"></i>Create Delivery
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Item Selection Modal -->
<div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
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
                                    data-stock="{{ $item->current_stock }}"
                                    data-unit="{{ $item->unit_of_measure }}"
                                    data-price="{{ $item->resolved_unit_price ?? $item->unit_price }}">
                                {{ $item->name }} ({{ $item->code }}) - Price: {{ number_format($item->resolved_unit_price ?? $item->unit_price, 2) }} - Stock: {{ $item->current_stock }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="modal_quantity" value="1" step="0.01" min="0.01">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_unit_price" class="form-label">Unit Price</label>
                            <input type="number" class="form-control" id="modal_unit_price" step="0.01" min="0">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_vat_type" class="form-label">VAT Type</label>
                            <select class="form-select" id="modal_vat_type">
                                <option value="no_vat" {{ get_default_vat_type() == 'no_vat' ? 'selected' : '' }}>No VAT</option>
                                <option value="inclusive" {{ get_default_vat_type() == 'inclusive' ? 'selected' : '' }}>Inclusive</option>
                                <option value="exclusive" {{ get_default_vat_type() == 'exclusive' ? 'selected' : '' }}>Exclusive</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_vat_rate" class="form-label">VAT Rate (%)</label>
                            <input type="number" class="form-control" id="modal_vat_rate" value="{{ get_default_vat_rate() }}" step="0.01" min="0">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="modal_notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="modal_notes" rows="2" placeholder="Optional notes for this item..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Line Total</label>
                    <div class="border rounded p-2 bg-light">
                        <span class="fw-bold" id="modal-line-total">0.00</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="add-item-to-table">Add Item</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    let itemCounter = 0;
    const selectedItems = {}; // To keep track of items already added

    // Initialize Select2 for customer and item selection
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
        placeholder: $(this).data('placeholder'),
    });

    $('.select2-modal').select2({
        dropdownParent: $('#itemModal'),
        theme: 'bootstrap-5',
        width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
        placeholder: $(this).data('placeholder'),
    });

    // Populate form if order data is available (for conversion)
    @if(isset($order) && $order)
        $('#customer_id').val('{{ $order->customer_id }}').trigger('change');
        $('#delivery_date').val('{{ date('Y-m-d') }}'); // Set current date for delivery
        $('#delivery_address').val('{{ $order->customer->address ?? '' }}');
        $('#contact_person').val('{{ $order->customer->contact_person ?? '' }}');
        $('#contact_phone').val('{{ $order->customer->phone ?? '' }}');
        $('#notes').val('Delivery for Order: {{ $order->order_number }}');
        // Add order ID to form data
        $('<input>').attr({
            type: 'hidden',
            id: 'sales_order_id',
            name: 'sales_order_id',
            value: '{{ $order->id }}'
        }).appendTo('#delivery-form');

        @foreach($order->items as $item)
            addItemToTable({
                id: '{{ $item->inventory_item_id }}',
                name: '{{ $item->item_name }}',
                code: '{{ $item->item_code }}',
                quantity: '{{ $item->quantity }}',
                unit_of_measure: '{{ $item->inventoryItem->unit_of_measure ?? 'N/A' }}',
                unit_weight: '{{ $item->inventoryItem->unit_weight ?? 0 }}',
                sales_order_item_id: '{{ $item->id }}'
            });
        @endforeach
    @endif

    // Add Item button click
    $('#add-item').click(function() {
        $('#itemModal').modal('show');
        // Reset modal fields
        $('#modal_item_id').val('').trigger('change');
        $('#modal_quantity').val(1);
        $('#modal_unit_price').val('');
        $('#modal_vat_type').val('{{ get_default_vat_type() }}');
        $('#modal_vat_rate').val('{{ get_default_vat_rate() }}');
        $('#modal_notes').val('');
        $('#modal-line-total').text('0.00');
    });

    // When an item is selected in the modal
    $('#modal_item_id').change(function() {
        const selectedOption = $(this).find(':selected');
        const unitPrice = selectedOption.data('price');
        $('#modal_unit_price').val(unitPrice);
        calculateModalLineTotal();
    });

    // Add Item to Table button click
    $('#add-item-to-table').click(function() {
        const itemId = $('#modal_item_id').val();
        const itemName = $('#modal_item_id option:selected').text();
        const quantity = parseFloat($('#modal_quantity').val()) || 0;
        const unitPrice = parseFloat($('#modal_unit_price').val()) || 0;
        const vatRate = parseFloat($('#modal_vat_rate').val()) || 0;
        const vatType = $('#modal_vat_type').val();
        const notes = $('#modal_notes').val();

        if (!itemId || quantity <= 0 || unitPrice <= 0) {
            Swal.fire('Error', 'Please fill in all required fields correctly', 'error');
            return;
        }

        if (selectedItems[itemId]) {
            Swal.fire('Error', 'This item has already been added', 'error');
            return;
        }

        addItemToTable({
            id: itemId,
            name: itemName,
            quantity: quantity,
            unit_price: unitPrice,
            vat_rate: vatRate,
            vat_type: vatType,
            notes: notes
        });

        selectedItems[itemId] = true; // Mark item as added
        $('#itemModal').modal('hide');
    });

    function addItemToTable(item) {
        itemCounter++;
        const rowId = `item-row-${itemCounter}`;
        
        // Calculate line total
        let subtotal = item.quantity * item.unit_price;
        let vatAmount = 0;
        let lineTotal = 0;
        
        if (item.vat_type === 'no_vat') {
            vatAmount = 0;
            lineTotal = subtotal;
        } else if (item.vat_type === 'exclusive') {
            vatAmount = subtotal * (item.vat_rate / 100);
            lineTotal = subtotal + vatAmount;
        } else {
            vatAmount = subtotal * (item.vat_rate / (100 + item.vat_rate));
            lineTotal = subtotal;
        }

        const newRow = `
            <tr id="${rowId}">
                <td>
                    <strong>${item.name}</strong><br>
                    <small class="text-muted">${item.notes || ''}</small>
                    <input type="hidden" name="items[${itemCounter}][inventory_item_id]" value="${item.id}">
                    <input type="hidden" name="items[${itemCounter}][item_name]" value="${item.name}">
                    <input type="hidden" name="items[${itemCounter}][vat_type]" value="${item.vat_type}">
                    <input type="hidden" name="items[${itemCounter}][vat_rate]" value="${item.vat_rate}">
                    <input type="hidden" name="items[${itemCounter}][notes]" value="${item.notes}">
                    <input type="hidden" name="items[${itemCounter}][line_total]" value="${lineTotal}">
                    <input type="hidden" name="items[${itemCounter}][vat_amount]" value="${vatAmount}">
                    ${item.sales_order_item_id ? `<input type="hidden" name="items[${itemCounter}][sales_order_item_id]" value="${item.sales_order_item_id}">` : ''}
                </td>
                <td><input type="number" class="form-control item-quantity" name="items[${itemCounter}][quantity]" value="${item.quantity}" step="0.01" min="0.01" data-row-id="${rowId}"></td>
                <td><input type="number" class="form-control item-price" name="items[${itemCounter}][unit_price]" value="${item.unit_price}" step="0.01" min="0" data-row-id="${rowId}"></td>
                <td>
                    <select class="form-select item-vat-type" name="items[${itemCounter}][vat_type]" data-row-id="${rowId}">
                        <option value="no_vat" ${item.vat_type === 'no_vat' ? 'selected' : ''}>No VAT</option>
                        <option value="inclusive" ${item.vat_type === 'inclusive' ? 'selected' : ''}>Inclusive</option>
                        <option value="exclusive" ${item.vat_type === 'exclusive' ? 'selected' : ''}>Exclusive</option>
                    </select>
                </td>
                <td><input type="number" class="form-control item-vat-rate" name="items[${itemCounter}][vat_rate]" value="${item.vat_rate}" step="0.01" min="0" data-row-id="${rowId}"></td>
                <td><span class="vat-amount">${vatAmount.toFixed(2)}</span></td>
                <td><span class="line-total">${lineTotal.toFixed(2)}</span></td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-item" data-row-id="${rowId}" data-item-id="${item.id}">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#items-tbody').append(newRow);
        calculateTotals();
    }

    // Remove item from table
    $(document).on('click', '.remove-item', function() {
        const rowId = $(this).data('row-id');
        const itemId = $(this).data('item-id');
        $(`#${rowId}`).remove();
        delete selectedItems[itemId]; // Remove from tracking
        calculateTotals();
    });

    // Recalculate totals on input change
    $(document).on('input change', '.item-quantity, .item-price, .item-vat-rate, .item-vat-type', function() {
        const rowId = $(this).data('row-id');
        updateRowTotals(rowId);
        calculateTotals();
    });

    // Calculate modal line total
    function calculateModalLineTotal() {
        const quantity = parseFloat($('#modal_quantity').val()) || 0;
        const unitPrice = parseFloat($('#modal_unit_price').val()) || 0;
        const vatRate = parseFloat($('#modal_vat_rate').val()) || 0;
        const vatType = $('#modal_vat_type').val();
        
        let subtotal = quantity * unitPrice;
        let vatAmount = 0;
        let lineTotal = 0;
        
        if (vatType === 'no_vat') {
            vatAmount = 0;
            lineTotal = subtotal;
        } else if (vatType === 'exclusive') {
            vatAmount = subtotal * (vatRate / 100);
            lineTotal = subtotal + vatAmount;
        } else {
            vatAmount = subtotal * (vatRate / (100 + vatRate));
            lineTotal = subtotal;
        }
        
        $('#modal-line-total').text(lineTotal.toFixed(2));
    }

    // Update modal line total when inputs change
    $('#modal_quantity, #modal_unit_price, #modal_vat_rate, #modal_vat_type').on('input change', function() {
        calculateModalLineTotal();
    });

    // Transport cost checkbox handler
    $('#has_transport_cost').on('change', function() {
        if ($(this).is(':checked')) {
            $('#transport_cost_fields').show();
            $('#transport-cost-display').show();
        } else {
            $('#transport_cost_fields').hide();
            $('#transport-cost-display').hide();
            $('#transport_cost').val('0');
        }
        calculateTotals();
    });

    // Transport cost input handler
    $('#transport_cost').on('input', function() {
        calculateTotals();
    });

    // Check if transport cost should be shown on page load
    if ($('#has_transport_cost').is(':checked')) {
        $('#transport_cost_fields').show();
        $('#transport-cost-display').show();
    }

    // Handle existing items - add event listeners and calculate totals
    $('#items-tbody tr').each(function() {
        const row = $(this);
        
        // Add event listeners for existing items
        row.find('.item-quantity, .item-price, .item-vat-rate, .item-vat-type').on('input change', function() {
            updateRowTotals(row.attr('data-item-id'));
            calculateTotals();
        });
        
        // Add remove button handler
        row.find('.remove-item').on('click', function() {
            row.remove();
            calculateTotals();
        });
    });

    // Calculate totals on page load
    calculateTotals();

    // Update row totals when values change
    function updateRowTotals(rowId) {
        const row = $(`tr[data-item-id="${rowId}"]`);
        const quantity = parseFloat(row.find('.item-quantity').val()) || 0;
        const unitPrice = parseFloat(row.find('.item-price').val()) || 0;
        const vatRate = parseFloat(row.find('.item-vat-rate').val()) || 0;
        const vatType = row.find('.item-vat-type').val();
        
        let subtotal = quantity * unitPrice;
        let vatAmount = 0;
        let lineTotal = 0;
        
        if (vatType === 'no_vat') {
            vatAmount = 0;
            lineTotal = subtotal;
        } else if (vatType === 'exclusive') {
            vatAmount = subtotal * (vatRate / 100);
            lineTotal = subtotal + vatAmount;
        } else {
            vatAmount = subtotal * (vatRate / (100 + vatRate));
            lineTotal = subtotal;
        }
        
        // Update display values
        row.find('.vat-amount').text(vatAmount.toFixed(2));
        row.find('.line-total').text(lineTotal.toFixed(2));
        
        // Update hidden inputs
        row.find('input[name*="[quantity]"]').val(quantity);
        row.find('input[name*="[unit_price]"]').val(unitPrice);
        row.find('input[name*="[vat_type]"]').val(vatType);
        row.find('input[name*="[vat_rate]"]').val(vatRate);
        row.find('input[name*="[vat_amount]"]').val(vatAmount);
        row.find('input[name*="[line_total]"]').val(lineTotal);
    }

    function calculateTotals() {
        let totalQuantity = 0;
        let totalVat = 0;
        let itemsTotal = 0;

        $('#items-tbody tr').each(function() {
            const quantity = parseFloat($(this).find('.item-quantity').val()) || 0;
            const vatAmount = parseFloat($(this).find('.vat-amount').text()) || 0;
            const lineTotal = parseFloat($(this).find('.line-total').text()) || 0;
            
            totalQuantity += quantity;
            totalVat += vatAmount;
            itemsTotal += lineTotal;
        });

        // Get transport cost if checkbox is checked
        const hasTransportCost = $('#has_transport_cost').is(':checked');
        const transportCost = hasTransportCost ? (parseFloat($('#transport_cost').val()) || 0) : 0;
        
        // Calculate grand total (items + transport cost)
        const grandTotal = itemsTotal + transportCost;

        // Update table totals
        $('#total-quantity').text(totalQuantity.toFixed(2));
        $('#total-vat').text(totalVat.toFixed(2));
        $('#grand-total').text(grandTotal.toFixed(2));

        // Update summary display
        $('#items-total').val(itemsTotal.toFixed(2));
        $('#transport-cost-display-value').val(transportCost.toFixed(2));
        $('#total-vat-display').val(totalVat.toFixed(2));
        $('#grand-total-display').val(grandTotal.toFixed(2));
    }

    // Initial calculation
    calculateTotals();

    // Form submission
    $('#delivery-form').submit(function(e) {
        e.preventDefault();
        
        const formData = $(this).serializeArray();

        $.ajax({
            url: '{{ route("sales.deliveries.store") }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    Swal.fire('Success', response.message, 'success').then(() => {
                        window.location.href = response.redirect_url;
                    });
                } else {
                    let errorMessage = response.message || 'An unknown error occurred.';
                    if (response.errors) {
                        errorMessage += '<br><ul>';
                        $.each(response.errors, function(key, value) {
                            errorMessage += `<li>${value}</li>`;
                            // Highlight invalid fields
                            const input = $(`[name="${key}"]`);
                            if (input.length) {
                                input.addClass('is-invalid');
                                input.next('.invalid-feedback').text(value);
                            }
                            // Handle nested item errors
                            if (key.startsWith('items.')) {
                                const parts = key.split('.');
                                const itemIndex = parts[1];
                                const fieldName = parts[2];
                                const itemInput = $(`[name="items[${itemIndex}][${fieldName}]"]`);
                                if (itemInput.length) {
                                    itemInput.addClass('is-invalid');
                                    itemInput.next('.invalid-feedback').text(value);
                                }
                            }
                        });
                        errorMessage += '</ul>';
                    }
                    Swal.fire('Error', errorMessage, 'error');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                Swal.fire('Error', errorMessage, 'error');
            }
        });
    });
});
</script>
@endpush
@endsection 