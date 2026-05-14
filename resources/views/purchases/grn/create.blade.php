@extends('layouts.main')

@section('title', 'Create GRN')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchases', 'url' => route('purchases.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'GRN', 'url' => route('purchases.grn.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Create GRN', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">CREATE GRN</h6>
        <hr />

        <!-- Original Order Information -->
        @if(isset($order))
        <div class="alert alert-primary d-flex align-items-start" role="alert">
            <div class="me-3">
                <i class="bx bx-shopping-cart fs-3"></i>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <strong>Creating GRN from Purchase Order:</strong>
                        <a href="{{ route('purchases.orders.show', $order->encoded_id) }}" class="text-decoration-underline">
                            {{ $order->reference ?? ('PO-' . str_pad($order->id, 6, '0', STR_PAD_LEFT)) }}
                        </a>
                        <span class="badge bg-primary ms-2">Purchase Order</span>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">Order Date: {{ $order->order_date?->format('M j, Y') }} | Expected Delivery: {{ $order->expected_delivery_date?->format('M j, Y') }}</small>
                        <div>
                            @php
                                $statusClasses = [
                                    'draft' => 'bg-secondary',
                                    'pending' => 'bg-warning',
                                    'approved' => 'bg-success',
                                    'rejected' => 'bg-danger',
                                    'cancelled' => 'bg-dark',
                                ];
                                $statusClass = $statusClasses[$order->status] ?? 'bg-secondary';
                            @endphp
                            <span class="badge {{ $statusClass }}">{{ ucfirst($order->status) }}</span>
                        </div>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="row">
                        <div class="col-md-6">
                            <small><strong>Supplier:</strong> {{ $order->supplier->name ?? 'N/A' }}</small>
                        </div>
                        <div class="col-md-6">
                            <small><strong>Order Total:</strong> TZS {{ number_format($order->total_amount, 2) }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(isset($quotation))
        <div class="alert alert-info d-flex align-items-start" role="alert">
            <div class="me-3">
                <i class="bx bx-file fs-3"></i>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <strong>From Quotation:</strong>
                        <a href="{{ route('purchases.quotations.show', $quotation->hash_id) }}" class="text-decoration-underline">
                            {{ $quotation->reference ?? ('QTN-' . str_pad($quotation->id, 6, '0', STR_PAD_LEFT)) }}
                        </a>
                        @if($quotation->is_request_for_quotation)
                            <span class="badge bg-warning text-dark ms-2">RFQ</span>
                        @else
                            <span class="badge bg-info ms-2">Quotation</span>
                        @endif
                    </div>
                    <div class="text-end">
                        <small class="text-muted">Start: {{ $quotation->start_date?->format('M j, Y') }} | Due: {{ $quotation->due_date?->format('M j, Y') }}</small>
                        <div>
                            @php
                                $statusClasses = [
                                    'draft' => 'bg-secondary',
                                    'sent' => 'bg-info',
                                    'approved' => 'bg-success',
                                    'rejected' => 'bg-danger',
                                    'expired' => 'bg-warning',
                                ];
                                $statusClass = $statusClasses[$quotation->status] ?? 'bg-secondary';
                            @endphp
                            <span class="badge {{ $statusClass }}">{{ ucfirst($quotation->status) }}</span>
                            @if(session('info'))
                                <div><small class="text-warning">{{ session('info') }}</small></div>
                            @endif
                        </div>
                    </div>
                </div>
                @if(!$quotation->is_request_for_quotation)
                    <div class="mt-1">
                        <small>Quoted Total: <strong>TZS {{ number_format($quotation->total_amount, 2) }}</strong></small>
                    </div>
                @endif
            </div>
        </div>
        @endif

        @if(isset($invoice) && !isset($order))
        <div class="alert alert-info d-flex align-items-start" role="alert">
            <div class="me-3">
                <i class="bx bx-receipt fs-3"></i>
            </div>
            <div class="flex-grow-1">
                <strong>Copy To: from Purchase Invoice</strong>
                <div class="mt-1">
                    <small>Invoice: <strong>{{ $invoice->invoice_number }}</strong> | Supplier: {{ $invoice->supplier->name ?? 'N/A' }} | Total: TZS {{ number_format($invoice->total_amount, 2) }}</small>
                </div>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-package me-2"></i>New GRN</h5>
            </div>
            <div class="card-body">
                <form id="grn-form">
                    @csrf
                    @if(isset($order))
                        <input type="hidden" name="purchase_order_id" value="{{ $order->id }}">
                    @endif
                    @if(isset($quotation))
                        <input type="hidden" name="quotation_id" value="{{ $quotation->id }}">
                    @endif
                    <div class="row">
                        <!-- Supplier Information -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="supplier_id" class="form-label">Supplier <span class="text-danger">*</span></label>
                                <select class="form-select select2-single" id="supplier_id" name="supplier_id" required {{ isset($order) ? 'disabled' : '' }}>
                                    <option value="">Select Supplier</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ (isset($order) && $order->supplier_id == $supplier->id) || (isset($invoice) && $invoice->supplier_id == $supplier->id) ? 'selected' : '' }}>{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                                @if(isset($order))
                                    <input type="hidden" name="supplier_id" value="{{ $order->supplier_id }}">
                                @endif
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="receipt_date" class="form-label">Receipt Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="receipt_date" name="receipt_date" 
                                       value="{{ date('Y-m-d') }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="reference_number" class="form-label">Reference Number</label>
                                <input type="text" class="form-control" id="reference_number" name="reference_number" 
                                       value="{{ isset($order) ? ($order->reference ?? 'PO-' . str_pad($order->id, 6, '0', STR_PAD_LEFT)) : '' }}"
                                       placeholder="Enter reference number">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="payment_terms" class="form-label">Payment Terms</label>
                                <select class="form-select" id="payment_terms" name="payment_terms">
                                    <option value="immediate">Immediate Payment</option>
                                    <option value="net_15">Net 15 Days</option>
                                    <option value="net_30" selected>Net 30 Days</option>
                                    <option value="net_45">Net 45 Days</option>
                                    <option value="net_60">Net 60 Days</option>
                                    <option value="custom">Custom</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="payment_days" class="form-label">Payment Days</label>
                                <input type="number" class="form-control" id="payment_days" name="payment_days" 
                                       placeholder="Number of days for payment" min="0">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Section -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">GRN Items</h6>
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
                                            <th width="25%">Item</th>
                                            <th width="15%">Quantity Received</th>
                                            <th width="15%">Cost Price</th>
                                            <th width="15%">VAT</th>
                                            <th width="15%">Total</th>
                                            <th width="10%">Action</th>
                                            <th width="5%"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="items-tbody">
                                        <!-- Items will be added here dynamically -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Subtotal (Without VAT):</strong></td>
                                            <td><strong id="subtotal">0.00</strong></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="subtotal" id="subtotal-input" value="0">
                                        <tr id="vat-row" style="display: none;">
                                            <td colspan="4" class="text-end"><strong>VAT (<span id="vat-rate-display">0</span>%):</strong></td>
                                            <td><strong id="vat-amount">0.00</strong></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="vat_amount" id="vat-amount-input" value="0">
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Additional Tax:</strong></td>
                                            <td>
                                                <input type="number" class="form-control" id="tax_amount" name="tax_amount" 
                                                       value="0" step="0.01" min="0">
                                            </td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Discount:</strong></td>
                                            <td>
                                                <input type="number" class="form-control" id="discount_amount" name="discount_amount" 
                                                       value="0" step="0.01" min="0">
                                            </td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr class="table-info">
                                            <td colspan="4" class="text-end"><strong>Total Amount:</strong></td>
                                            <td><strong id="total-amount">0.00</strong></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="total_amount" id="total-amount-input" value="0">
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
                                          placeholder="Additional notes for this GRN..."></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="terms_conditions" class="form-label">Terms & Conditions</label>
                                <textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="4" 
                                          placeholder="Terms and conditions..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2 justify-content-end">
                                             <a href="{{ isset($order) ? route('purchases.orders.show', $order->encoded_id) : route('purchases.grn.index') }}" class="btn btn-outline-secondary">
                         <i class="bx bx-x me-1"></i>Cancel
                     </a>
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="bx bx-check me-1" data-processing-text="Creating..."></i>Create GRN
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
                        @foreach(($inventoryItems ?? []) as $item)
                            <option value="{{ $item->id }}" 
                                    data-name="{{ $item->name }}" 
                                    data-code="{{ $item->code }}"
                                    data-price="{{ $item->resolved_cost_price ?? $item->cost_price }}"
                                    data-stock="{{ $item->current_stock }}"
                                    data-unit="{{ $item->unit_of_measure }}">
                                {{ $item->name }} ({{ $item->code }}) - Stock: {{ $item->current_stock }}
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
                            <label for="modal_unit_price" class="form-label">Cost Price</label>
                            <input type="number" class="form-control" id="modal_unit_price" step="0.01" min="0">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_item_vat_type" class="form-label">Item VAT Type</label>
                            <select class="form-select" id="modal_item_vat_type">
                                <option value="no_vat">No VAT</option>
                                <option value="vat_inclusive">VAT Inclusive</option>
                                <option value="vat_exclusive">VAT Exclusive</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_item_vat_rate" class="form-label">Item VAT Rate (%)</label>
                            <input type="number" class="form-control" id="modal_item_vat_rate" value="18" step="0.01" min="0" max="100">
                            <small class="text-muted">Individual VAT rate for this item</small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Item Total Preview</label>
                            <div class="form-control-plaintext border rounded p-2 bg-light">
                                <strong id="modal_item_total_preview">0.00</strong>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="mb-3">
                    <label for="modal_description" class="form-label">Description</label>
                    <textarea class="form-control" id="modal_description" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="add-item-to-table">Add Item</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
// Default VAT config from system settings
const DEFAULT_VAT_TYPE = (function(){
    const t = '{{ get_default_vat_type() }}';
    if (t === 'inclusive') return 'vat_inclusive';
    if (t === 'exclusive') return 'vat_exclusive';
    return 'no_vat';
})();
const DEFAULT_VAT_RATE = {{ (float) (get_default_vat_rate() ?? 0) }};
$(document).ready(function() {
    let itemCounter = 0;

    // Initialize Select2
    $('.select2-single').select2({
        placeholder: 'Select',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap-5'
    });

    // Initialize Select2 for modal with search
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

    // Add Item Button
    $('#add-item').click(function() {
        $('#itemModal').modal('show');
        resetItemModal();
    });

    // Item selection change
    $('#modal_item_id').change(function() {
        const selectedOption = $(this).find(':selected');
        const price = selectedOption.data('price');
        $('#modal_unit_price').val(price || 0);
        calculateModalItemTotal();
    });

    // Modal item calculations
    $('#modal_quantity, #modal_unit_price, #modal_item_vat_rate').on('input', function() {
        calculateModalItemTotal();
    });

    $('#modal_item_vat_type').on('change', function() {
        calculateModalItemTotal();
    });

    // Add item to table
    $('#add-item-to-table').click(function() {
        const itemId = $('#modal_item_id').val();
        const itemName = $('#modal_item_id option:selected').data('name');
        const itemCode = $('#modal_item_id option:selected').data('code');
        const quantity = parseFloat($('#modal_quantity').val()) || 0;
        const unitPrice = parseFloat($('#modal_unit_price').val()) || 0;
        const description = $('#modal_description').val();
        const itemVatType = $('#modal_item_vat_type').val();
        const itemVatRate = parseFloat($('#modal_item_vat_rate').val()) || 0;

        if (!itemId || quantity <= 0) {
            Swal.fire('Error', 'Please select an item and enter a valid quantity', 'error');
            return;
        }

        // Check if item already exists
        if ($(`tr[data-item-id="${itemId}"]`).length > 0) {
            Swal.fire('Error', 'This item is already added. Please edit the existing entry.', 'error');
            return;
        }

        // Calculate item total with VAT
        const baseAmount = quantity * unitPrice;
        let lineTotal = baseAmount;
        let itemVatAmount = 0;
        
        if (itemVatType === 'vat_inclusive' && itemVatRate > 0) {
            const vatFactor = itemVatRate / 100;
            itemVatAmount = lineTotal * vatFactor / (1 + vatFactor);
        } else if (itemVatType === 'vat_exclusive' && itemVatRate > 0) {
            itemVatAmount = lineTotal * (itemVatRate / 100);
            lineTotal += itemVatAmount;
        }

        itemCounter++;

        const vatDisplay = itemVatType === 'no_vat' ? 'No VAT' : `${itemVatRate}% (${itemVatType.replace('_', ' ')})`;

        const row = `
            <tr data-item-id="${itemId}" data-row-id="${itemCounter}">
                <td>
                    <strong>${itemName}</strong><br>
                    <small class="text-muted">${itemCode}</small>
                                         <input type="hidden" name="items[${itemCounter}][inventory_item_id]" value="${itemId}">
                    <input type="hidden" name="items[${itemCounter}][quantity_ordered]" value="${quantity}">
                    <input type="hidden" name="items[${itemCounter}][description]" value="${description}">
                    <input type="hidden" name="items[${itemCounter}][vat_type]" value="${itemVatType}">
                    <input type="hidden" name="items[${itemCounter}][vat_rate]" value="${itemVatRate}">
                    <input type="hidden" name="items[${itemCounter}][vat_amount]" value="${itemVatAmount}">
                    <input type="hidden" name="items[${itemCounter}][line_total]" value="${lineTotal}">
                </td>
                <td>
                    <input type="number" class="form-control item-quantity" 
                           name="items[${itemCounter}][quantity_received]" value="${quantity}" 
                           step="0.01" min="0.01" data-row="${itemCounter}">
                </td>
                <td>
                    <input type="number" class="form-control item-price" 
                           name="items[${itemCounter}][unit_cost]" value="${unitPrice}" 
                           step="0.01" min="0" data-row="${itemCounter}">
                </td>
                <td>
                    <span class="form-control-plaintext">${vatDisplay}</span>
                </td>
                <td>
                    <small class="text-muted">${vatDisplay}</small>
                </td>
                <td>
                    <span class="item-total">${lineTotal.toFixed(2)}</span>
                </td>
                <td>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-item">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
                <td></td>
            </tr>
        `;

        $('#items-tbody').append(row);
        $('#itemModal').modal('hide');
        calculateTotals();
    });

    // Prefill from order when available
    @if(isset($order))
        // Set supplier from order
        if ({{ $order->supplier_id }}) {
            $('#supplier_id').val({{ $order->supplier_id }}).trigger('change');
        }

        // Prefill items from order
        const orderItems = @json($order->items ?? []);
        orderItems.forEach(function(orderItem) {
            if (orderItem.item) {
                const itemId = orderItem.item.id;
                const itemName = orderItem.item.name || '';
                const itemCode = orderItem.item.code || '';
                const quantityOrdered = parseFloat(orderItem.quantity) || 0;
                const unitPrice = parseFloat(orderItem.cost_price) || 0;
                const vatType = orderItem.vat_type || 'no_vat';
                const vatRate = parseFloat(orderItem.vat_rate) || 0;

                if (!itemId || quantityOrdered <= 0) return;
                if ($(`tr[data-item-id="${itemId}"]`).length > 0) return;

                // Calculate item total with VAT
                const baseAmount = quantityOrdered * unitPrice;
                let lineTotal = baseAmount;
                let itemVatAmount = 0;
                
                if (vatType === 'vat_inclusive' && vatRate > 0) {
                    const vatFactor = vatRate / 100;
                    itemVatAmount = lineTotal * vatFactor / (1 + vatFactor);
                } else if (vatType === 'vat_exclusive' && vatRate > 0) {
                    itemVatAmount = lineTotal * (vatRate / 100);
                    lineTotal += itemVatAmount;
                }

                itemCounter++;
                const vatDisplay = vatType === 'no_vat' ? 'No VAT' : `${vatRate}% (${vatType.replace('_', ' ')})`;

                const row = `
                    <tr data-item-id="${itemId}" data-row-id="${itemCounter}">
                        <td>
                            <strong>${itemName}</strong><br>
                            <small class="text-muted">${itemCode}</small>
                            <input type="hidden" name="items[${itemCounter}][inventory_item_id]" value="${itemId}">
                            <input type="hidden" name="items[${itemCounter}][purchase_order_item_id]" value="${orderItem.id}">
                            <input type="hidden" name="items[${itemCounter}][quantity_ordered]" value="${quantityOrdered}">
                            <input type="hidden" name="items[${itemCounter}][description]" value="">
                            <input type="hidden" name="items[${itemCounter}][vat_type]" value="${vatType}">
                            <input type="hidden" name="items[${itemCounter}][vat_rate]" value="${vatRate}">
                            <input type="hidden" name="items[${itemCounter}][vat_amount]" value="${itemVatAmount}">
                            <input type="hidden" name="items[${itemCounter}][line_total]" value="${lineTotal}">
                        </td>
                        <td>
                            <div class="mb-1">
                                <small class="text-muted">Ordered: ${quantityOrdered}</small>
                            </div>
                            <input type="number" class="form-control item-quantity" 
                                   name="items[${itemCounter}][quantity_received]" value="${quantityOrdered}" 
                                   step="0.01" min="0.01" data-row="${itemCounter}" 
                                   placeholder="Received quantity">
                        </td>
                        <td>
                            <input type="number" class="form-control item-price" 
                                   name="items[${itemCounter}][unit_cost]" value="${unitPrice}" 
                                   step="0.01" min="0" data-row="${itemCounter}" readonly>
                        </td>
                        <td>
                            <span class="form-control-plaintext">${vatDisplay}</span>
                        </td>
                        <td>
                            <span class="item-total">${lineTotal.toFixed(2)}</span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-outline-danger btn-sm remove-item">
                                <i class="bx bx-trash"></i>
                            </button>
                        </td>
                        <td></td>
                    </tr>
                `;

                $('#items-tbody').append(row);
                calculateTotals();
            }
        });
    @else
        // For standalone GRN or from invoice - see below
    @endif

    // Prefill from purchase invoice (Copy To) when available
    @if(isset($invoice) && isset($prefillItems) && count($prefillItems) > 0)
        $('#supplier_id').val({{ (int) $invoice->supplier_id }}).trigger('change');
        (function() {
            const prefillItems = @json($prefillItems);
            const mapVatType = (t) => {
                if (!t) return 'no_vat';
                if (t === 'inclusive') return 'vat_inclusive';
                if (t === 'exclusive') return 'vat_exclusive';
                return 'no_vat';
            };
            (prefillItems || []).forEach(function(it){
                const itemId = it.inventory_item_id;
                const itemName = it.item_name || '';
                const itemCode = it.item_code || '';
                const quantityOrdered = parseFloat(it.quantity_ordered) || parseFloat(it.quantity_received) || 0;
                const unitPrice = parseFloat(it.unit_cost) || 0;
                const itemVatType = mapVatType(it.vat_type);
                const itemVatRate = parseFloat(it.vat_rate) || 0;
                if (!itemId || quantityOrdered <= 0) return;
                if ($(`tr[data-item-id="${itemId}"]`).length > 0) return;
                const baseAmount = quantityOrdered * unitPrice;
                let lineTotal = baseAmount;
                let itemVatAmount = 0;
                if (itemVatType === 'vat_inclusive' && itemVatRate > 0) {
                    const vatFactor = itemVatRate / 100;
                    itemVatAmount = lineTotal * vatFactor / (1 + vatFactor);
                } else if (itemVatType === 'vat_exclusive' && itemVatRate > 0) {
                    itemVatAmount = lineTotal * (itemVatRate / 100);
                    lineTotal += itemVatAmount;
                }
                itemCounter++;
                const vatDisplay = itemVatType === 'no_vat' ? 'No VAT' : (itemVatRate + '%');
                const row = '<tr data-item-id="' + itemId + '" data-row-id="' + itemCounter + '">' +
                    '<td><strong>' + (itemName.replace(/</g, '&lt;')) + '</strong><br><small class="text-muted">' + (itemCode.replace(/</g, '&lt;')) + '</small>' +
                    '<input type="hidden" name="items[' + itemCounter + '][inventory_item_id]" value="' + itemId + '">' +
                    '<input type="hidden" name="items[' + itemCounter + '][purchase_order_item_id]" value="">' +
                    '<input type="hidden" name="items[' + itemCounter + '][quantity_ordered]" value="' + quantityOrdered + '">' +
                    '<input type="hidden" name="items[' + itemCounter + '][vat_type]" value="' + itemVatType + '">' +
                    '<input type="hidden" name="items[' + itemCounter + '][vat_rate]" value="' + itemVatRate + '">' +
                    '<input type="hidden" name="items[' + itemCounter + '][vat_amount]" value="' + itemVatAmount + '">' +
                    '<input type="hidden" name="items[' + itemCounter + '][line_total]" value="' + lineTotal + '">' +
                    '</td><td><div class="mb-1"><small class="text-muted">Ordered: ' + quantityOrdered + '</small></div>' +
                    '<input type="number" class="form-control item-quantity" name="items[' + itemCounter + '][quantity_received]" value="' + quantityOrdered + '" step="0.01" min="0.01" data-row="' + itemCounter + '"></td>' +
                    '<td><input type="number" class="form-control item-price" name="items[' + itemCounter + '][unit_cost]" value="' + unitPrice + '" step="0.01" min="0" data-row="' + itemCounter + '"></td>' +
                    '<td><span class="form-control-plaintext">' + vatDisplay + '</span></td>' +
                    '<td><span class="item-total">' + lineTotal.toFixed(2) + '</span></td>' +
                    '<td><button type="button" class="btn btn-outline-danger btn-sm remove-item"><i class="bx bx-trash"></i></button></td><td></td></tr>';
                $('#items-tbody').append(row);
            });
            calculateTotals();
        })();
    @endif

    // Prefill from quotation when available
    @if(isset($quotation))
        const prefill = {
            supplier_id: {{ (int) $quotation->supplier_id }},
            is_rfq: {{ $quotation->is_request_for_quotation ? 'true' : 'false' }},
            items: @json($prefillItems)
        };

        // Set supplier
        if (prefill.supplier_id) {
            $('#supplier_id').val(prefill.supplier_id).trigger('change');
        }

        // Helper to map VAT type from quotation to order form
        const mapVatType = (t) => {
            if (!t) return 'no_vat';
            if (t === 'inclusive') return 'vat_inclusive';
            if (t === 'exclusive') return 'vat_exclusive';
            return 'no_vat';
        };

        // Add each item row
        (prefill.items || []).forEach(function(qItem){
            const itemId = qItem.item_id;
            const itemName = qItem.item_name || '';
            const itemCode = qItem.item_code || '';
            const quantity = qItem.quantity || 0;
            const unitPrice = prefill.is_rfq ? 0 : (qItem.unit_price || 0);
            const itemVatType = mapVatType(qItem.vat_type);
            const itemVatRate = qItem.vat_rate || 0;

            if (!itemId || quantity <= 0) return;
            if ($(`tr[data-item-id="${itemId}"]`).length > 0) return;

            const baseAmount = quantity * unitPrice;
            let lineTotal = baseAmount;
            let itemVatAmount = 0;

            if (itemVatType === 'vat_inclusive' && itemVatRate > 0) {
                const vatFactor = itemVatRate / 100;
                itemVatAmount = lineTotal * vatFactor / (1 + vatFactor);
            } else if (itemVatType === 'vat_exclusive' && itemVatRate > 0) {
                itemVatAmount = lineTotal * (itemVatRate / 100);
                lineTotal += itemVatAmount;
            }

            itemCounter++;
            const vatDisplay = itemVatType === 'no_vat' ? 'No VAT' : `${itemVatRate}% (${itemVatType.replace('_', ' ')})`;

            const row = `
                <tr data-item-id="${itemId}" data-row-id="${itemCounter}">
                    <td>
                        <strong>${itemName}</strong><br>
                        <small class="text-muted">${itemCode}</small>
                        <input type="hidden" name="items[${itemCounter}][inventory_item_id]" value="${itemId}">
                        <input type="hidden" name="items[${itemCounter}][quantity_ordered]" value="${quantity}">
                        <input type="hidden" name="items[${itemCounter}][description]" value="">
                        <input type="hidden" name="items[${itemCounter}][vat_type]" value="${itemVatType}">
                        <input type="hidden" name="items[${itemCounter}][vat_rate]" value="${itemVatRate}">
                        <input type="hidden" name="items[${itemCounter}][vat_amount]" value="${itemVatAmount}">
                        <input type="hidden" name="items[${itemCounter}][line_total]" value="${lineTotal}">
                    </td>
                    <td>
                        <input type="number" class="form-control item-quantity" 
                               name="items[${itemCounter}][quantity_received]" value="${quantity}" 
                               step="0.01" min="0.01" data-row="${itemCounter}">
                    </td>
                    <td>
                        <input type="number" class="form-control item-price" 
                               name="items[${itemCounter}][unit_cost]" value="${unitPrice}" 
                               step="0.01" min="0" data-row="${itemCounter}">
                    </td>
                    <td>
                        <span class="form-control-plaintext">${vatDisplay}</span>
                    </td>
                    <td>
                        <small class="text-muted">${vatDisplay}</small>
                    </td>
                    <td>
                        <span class="item-total">${lineTotal.toFixed(2)}</span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-outline-danger btn-sm remove-item">
                            <i class="bx bx-trash"></i>
                        </button>
                    </td>
                    <td></td>
                </tr>
            `;

            $('#items-tbody').append(row);
            calculateTotals();
        });
    @endif

    // Remove item
    $(document).on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        calculateTotals();
    });

    // Recalculate on input change
    $(document).on('input', '.item-quantity, .item-price, #tax_amount, #discount_amount', function() {
        const row = $(this).data('row');
        if (row) {
            updateRowTotal(row);
        }
        calculateTotals();
    });

    // Form submission
    $('#grn-form').submit(function(e) {
        e.preventDefault();
        
                 if ($('#items-tbody tr').length === 0) {
             Swal.fire('Error', 'Please add at least one item to the GRN', 'error');
             return;
         }

         if (!$('#supplier_id').val()) {
             Swal.fire('Error', 'Please select a supplier', 'error');
             return;
         }

        const formData = new FormData(this);
        const submitBtn = $('#submit-btn');
        
        submitBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i>Creating...');

                 $.ajax({
             url: '{{ isset($order) ? route("purchases.orders.grn.store", $order->encoded_id) : route("purchases.grn.store-standalone") }}',
             type: 'POST',
             data: formData,
             processData: false,
             contentType: false,
             success: function(response) {
                 if (response.success) {
                     Swal.fire({
                         title: 'Success!',
                         text: response.message,
                         icon: 'success',
                         confirmButtonText: 'OK'
                     }).then(() => {
                         window.location.href = '{{ route("purchases.grn.index") }}';
                     });
                 } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    displayValidationErrors(errors);
                    Swal.fire('Validation Error', 'Please check the form for errors', 'error');
                } else {
                    Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Create GRN');
            }
        });
    });

    function updateRowTotal(row) {
        const quantity = parseFloat($(`input[name="items[${row}][quantity_received]"]`).val()) || 0;
        const unitPrice = parseFloat($(`input[name="items[${row}][unit_cost]"]`).val()) || 0;
        const itemVatType = $(`input[name="items[${row}][vat_type]"]`).val();
        const itemVatRate = parseFloat($(`input[name="items[${row}][vat_rate]"]`).val()) || 0;
        
        let lineTotal = quantity * unitPrice;
        let itemVatAmount = 0;
        
        // Apply individual item VAT
        if (itemVatType === 'vat_inclusive' && itemVatRate > 0) {
            // VAT is already included in the line total
            const vatFactor = itemVatRate / 100;
            itemVatAmount = lineTotal * vatFactor / (1 + vatFactor);
        } else if (itemVatType === 'vat_exclusive' && itemVatRate > 0) {
            itemVatAmount = lineTotal * (itemVatRate / 100);
            lineTotal += itemVatAmount;
        }
        
        // Update hidden fields
        $(`input[name="items[${row}][vat_amount]"]`).val(itemVatAmount.toFixed(2));
        $(`input[name="items[${row}][line_total]"]`).val(lineTotal.toFixed(2));
        
        $(`tr[data-row-id="${row}"] .item-total`).text(`${lineTotal.toFixed(2)}`);
    }

    function calculateTotals() {
        let subtotal = 0;
        let totalVatFromItems = 0;
        
        // Calculate subtotal and individual VAT amounts
        $('.item-total').each(function(index) {
            const rowId = $(this).closest('tr').data('row-id');
            const itemVatType = $(`input[name="items[${rowId}][vat_type]"]`).val();
            const itemVatRate = parseFloat($(`input[name="items[${rowId}][vat_rate]"]`).val()) || 0;
            const lineTotal = parseFloat($(this).text()) || 0;
            
            if (itemVatType === 'inclusive' && itemVatRate > 0) {
                // Extract VAT from inclusive amount
                const vatAmount = lineTotal * (itemVatRate / (100 + itemVatRate));
                totalVatFromItems += vatAmount;
                subtotal += lineTotal - vatAmount; // Add net amount
            } else if (itemVatType === 'exclusive' && itemVatRate > 0) {
                // VAT is added on top of net amount
                const netAmount = lineTotal / (1 + itemVatRate / 100);
                const vatAmount = lineTotal - netAmount;
                totalVatFromItems += vatAmount;
                subtotal += netAmount;
            } else {
                // No VAT
                subtotal += lineTotal;
            }
        });

        const tax = parseFloat($('#tax_amount').val()) || 0;
        const discount = parseFloat($('#discount_amount').val()) || 0;
        
        const total = subtotal + tax + totalVatFromItems - discount;

        $('#subtotal').text(`${subtotal.toFixed(2)}`);
        $('#subtotal-input').val(subtotal.toFixed(2));
        if (totalVatFromItems > 0) {
            $('#vat-row').show();
        } else {
            $('#vat-row').hide();
        }
        $('#vat-amount').text(`${totalVatFromItems.toFixed(2)}`);
        $('#vat-amount-input').val(totalVatFromItems.toFixed(2));
        $('#vat-rate-display').text('Mixed');
        $('#total-amount').text(`${total.toFixed(2)}`);
        $('#total-amount-input').val(total.toFixed(2));
    }

    function resetItemModal() {
        $('#modal_item_id').val('').trigger('change');
        $('#modal_quantity').val(1);
        $('#modal_unit_price').val(0);
        $('#modal_description').val('');
        $('#modal_item_vat_type').val(DEFAULT_VAT_TYPE);
        $('#modal_item_vat_rate').val(DEFAULT_VAT_RATE);
        $('#modal_item_total_preview').text('0.00');
    }



    function calculateModalItemTotal() {
        const quantity = parseFloat($('#modal_quantity').val()) || 0;
        const unitPrice = parseFloat($('#modal_unit_price').val()) || 0;
        const vatType = $('#modal_item_vat_type').val();
        const vatRate = parseFloat($('#modal_item_vat_rate').val()) || 0;
        
        const baseAmount = quantity * unitPrice;
        let lineTotal = baseAmount;
        
        if (vatType === 'vat_inclusive' && vatRate > 0) {
            // VAT is included, no additional calculation needed for display
        } else if (vatType === 'vat_exclusive' && vatRate > 0) {
            lineTotal += lineTotal * (vatRate / 100);
        }
        
        $('#modal_item_total_preview').text(lineTotal.toFixed(2));
    }

    function formatItemOption(item) {
        if (!item.id) {
            return item.text;
        }
        
        const $item = $(item.element);
        const stock = $item.data('stock');
        const code = $item.data('code');
        const price = $item.data('price');
        
        return $(`
            <div>
                <strong>${item.text.split(' (')[0]}</strong>
                <br>
                <small class="text-muted">Code: ${code} | Price: ${price} | Stock: ${stock}</small>
            </div>
        `);
    }

    function formatItemSelection(item) {
        return item.text;
    }

    function displayValidationErrors(errors) {
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').empty();

        // Display new errors
        $.each(errors, function(field, messages) {
            const input = $(`[name="${field}"]`);
            input.addClass('is-invalid');
            input.siblings('.invalid-feedback').text(messages[0]);
        });
    }
});
</script>
@endpush
