@extends('layouts.main')

@section('title', 'Create Delivery')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Deliveries', 'url' => route('sales.deliveries.index'), 'icon' => 'bx bx-truck'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">CREATE DELIVERY</h6>
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
                                <div class="input-group">
                                    <select class="form-select select2-single" id="customer_id" name="customer_id" required>
                                        <option value="">Select Customer</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }} - {{ $customer->phone }}</option>
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
                                <label for="delivery_date" class="form-label">Delivery Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="delivery_date" name="delivery_date" 
                                       value="{{ date('Y-m-d') }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="delivery_time" class="form-label">Delivery Time</label>
                                <input type="time" class="form-control" id="delivery_time" name="delivery_time">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="delivery_type" class="form-label">Delivery Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="delivery_type" name="delivery_type" required>
                                    <option value="delivery">Delivery</option>
                                    <option value="pickup">Customer Pickup</option>
                                    <option value="shipping">Shipping</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="contact_person" class="form-label">Contact Person</label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person" 
                                       placeholder="Name of contact person">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="contact_phone" class="form-label">Contact Phone</label>
                                <input type="text" class="form-control" id="contact_phone" name="contact_phone" 
                                       placeholder="Contact phone number">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="delivery_address" class="form-label">Delivery Address</label>
                                <textarea class="form-control" id="delivery_address" name="delivery_address" rows="3" 
                                          placeholder="Full delivery address..."></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="delivery_instructions" class="form-label">Delivery Instructions</label>
                                <textarea class="form-control" id="delivery_instructions" name="delivery_instructions" rows="3" 
                                          placeholder="Special delivery instructions..."></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Transport Cost Section -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="has_transport_cost" name="has_transport_cost" value="1">
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
                                       step="0.01" min="0" placeholder="0.00">
                                <small class="form-text text-muted">Amount customer will pay for transport/delivery</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="vehicle_number" class="form-label">Vehicle Number</label>
                                <input type="text" class="form-control" id="vehicle_number" name="vehicle_number" 
                                       placeholder="Vehicle registration number">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="driver_name" class="form-label">Driver Name</label>
                                <input type="text" class="form-control" id="driver_name" name="driver_name" 
                                       placeholder="Driver's name">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="driver_phone" class="form-label">Driver Phone</label>
                                <input type="text" class="form-control" id="driver_phone" name="driver_phone" 
                                       placeholder="Driver's phone number">
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
                                        <!-- Items will be added here dynamically -->
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
                                                    <input type="text" class="form-control" id="transport-cost-display-value" value="0.00" readonly>
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
                                          placeholder="Additional notes for this delivery..."></textarea>
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

<!-- Customer Creation Modal -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="add-customer-errors" class="alert alert-danger d-none"></div>
                <form id="customer-form">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_name" class="form-label">Customer Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="customer_name" name="name" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="customer_phone" name="phone" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="customer_email" name="email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_status" class="form-label">Status</label>
                                <select class="form-select" id="customer_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_tin_number" class="form-label">TIN Number</label>
                                <input type="text" class="form-control" id="customer_tin_number" name="tin_number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_vat_number" class="form-label">VAT Number</label>
                                <input type="text" class="form-control" id="customer_vat_number" name="vat_number">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="customer_description" class="form-label">Description</label>
                        <textarea class="form-control" id="customer_description" name="description" rows="3" placeholder="Optional description about the customer..."></textarea>
                    </div>
                </form>
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

@push('styles')
<style>
#add-customer-btn {
    min-width: 120px;
    font-size: 0.875rem;
    font-weight: 500;
}

#add-customer-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.input-group .btn {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

.input-group .select2-container {
    flex: 1 1 auto;
    width: 1% !important;
}

.input-group .select2-container .select2-selection {
    border-bottom-right-radius: 0;
    border-right: 0;
}

.modal-backdrop {
    z-index: 1040;
}

.modal {
    z-index: 1050;
}

#customerModal .modal-content {
    background-color: white;
    color: #333;
}

#customerModal .modal-body {
    padding: 1rem;
}

#customerModal .form-control {
    background-color: white;
    color: #333;
}
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    let itemCounter = 0;
    const selectedItems = {}; // To keep track of items already added

    // Initialize Select2 for customer and item selection (excluding customer dropdown which is in input group)
    $('.select2-single').not('#customer_id').select2({
        theme: 'bootstrap-5',
        width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
        placeholder: $(this).data('placeholder'),
    });

    // Initialize customer dropdown with Select2 but compatible with input group
    $('#customer_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        dropdownParent: $('body')
    });

    $('.select2-modal').select2({
        dropdownParent: $('#itemModal'),
        theme: 'bootstrap-5',
        width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
        placeholder: $(this).data('placeholder'),
    });

    // Customer modal functionality
    $('#add-customer-btn').click(function() {
        resetCustomerForm();
        $('#add-customer-errors').addClass('d-none').empty();
        $('#customerModal').modal('show');
    });

    // Save customer functionality
    $('#save-customer-btn').click(function() {
        saveCustomer();
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
    @elseif(isset($invoice) && $invoice)
        {{-- Copy To: from Sales Invoice --}}
        $('#customer_id').val('{{ $invoice->customer_id }}').trigger('change');
        $('#delivery_date').val('{{ date('Y-m-d') }}');
        $('#delivery_address').val('{{ $invoice->customer->address ?? '' }}');
        $('#contact_person').val('{{ $invoice->customer->contact_person ?? '' }}');
        $('#contact_phone').val('{{ $invoice->customer->phone ?? '' }}');
        $('#notes').val('Copied from Invoice: {{ $invoice->invoice_number }}');
        @foreach($invoice->items as $item)
            addItemToTable({
                id: '{{ $item->inventory_item_id }}',
                name: '{{ addslashes($item->item_name) }}',
                code: '{{ $item->item_code ?? '' }}',
                quantity: '{{ $item->quantity }}',
                unit_price: '{{ $item->unit_price }}',
                vat_type: '{{ $item->vat_type ?? 'no_vat' }}',
                vat_rate: '{{ $item->vat_rate ?? 0 }}',
                notes: ''
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

    // Update row totals when values change
    function updateRowTotals(rowId) {
        const row = $(`#${rowId}`);
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

    // Customer functions
    function resetCustomerForm() {
        $('#customer-form')[0].reset();
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    }

    function saveCustomer() {
        // Clear errors
        $('#add-customer-errors').addClass('d-none').empty();
        $('.invalid-feedback').text('');
        $('.form-control').removeClass('is-invalid');

        // Collect and normalize
        const name = $('#customer_name').val().trim();
        const rawPhone = $('#customer_phone').val().trim();
        const email = $('#customer_email').val().trim();
        const status = $('#customer_status').val() || 'active';
        
        function normalizePhoneClient(phone){
            let p = (phone || '').replace(/[^0-9+]/g, '');
            if (p.startsWith('+255')) { p = '255' + p.slice(4); }
            else if (p.startsWith('0')) { p = '255' + p.slice(1); }
            else if (/^\d{9}$/.test(p)) { p = '255' + p; }
            return p;
        }
        const phone = normalizePhoneClient(rawPhone);
        
        if (!name) { 
            $('#customer_name').addClass('is-invalid').siblings('.invalid-feedback').text('Customer name is required'); 
            return; 
        }
        if (!phone) { 
            $('#customer_phone').addClass('is-invalid').siblings('.invalid-feedback').text('Phone number is required'); 
            return; 
        }

        const payload = {
            name: name,
            phone: phone,
            email: email,
            status: status,
            tin_number: $('#customer_tin_number').val(),
            vat_number: $('#customer_vat_number').val(),
            description: $('#customer_description').val(),
            _token: $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val()
        };

        const btn = $('#save-customer-btn');
        btn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i>Saving...');

        $.ajax({
            url: '{{ route('customers.store') }}',
            method: 'POST',
            data: payload,
            headers: { 'Accept': 'application/json' },
        }).done(function(res){
            const id = res?.customer?.id;
            const name = res?.customer?.name;
            const phone = res?.customer?.phone;
            
            if (id && name) {
                // Add new option to customer dropdown
                const newOption = new Option(`${name} - ${phone}`, id, true, true);
                $('#customer_id').append(newOption).trigger('change');
                
                // Close modal and show success
                $('#customerModal').modal('hide');
                Swal.fire('Success', 'Customer added successfully!', 'success');
            } else {
                $('#add-customer-errors').removeClass('d-none').text('Failed to create customer');
            }
        }).fail(function(xhr) {
            let errorMessage = 'Failed to create customer';
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                const errors = xhr.responseJSON.errors;
                let errorList = '<ul>';
                $.each(errors, function(key, value) {
                    errorList += `<li>${value}</li>`;
                    // Highlight invalid fields
                    $(`#customer_${key}`).addClass('is-invalid');
                });
                errorList += '</ul>';
                errorMessage += errorList;
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            $('#add-customer-errors').removeClass('d-none').html(errorMessage);
        }).always(function() {
            btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Customer');
        });
    }
});
</script>
@endpush
@endsection 