@extends('layouts.main')

@section('title', 'Create Purchase Order')

@section('content')
<style>
    .hidden {
        display: none !important;
    }
</style>
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchases', 'url' => route('purchases.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Orders', 'url' => route('purchases.orders.index'), 'icon' => 'bx bx-shopping-cart'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">CREATE PURCHASE ORDER</h6>
        <hr />

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

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-shopping-cart me-2"></i>New Purchase Order</h5>
            </div>
            <div class="card-body">
                <form id="order-form" enctype="multipart/form-data">
                    @csrf
                    @if(isset($quotation))
                        <input type="hidden" name="quotation_id" value="{{ $quotation->id }}">
                    @endif
                    <div class="row">
                        <!-- Supplier Information -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="supplier_id" class="form-label">Supplier <span class="text-danger">*</span></label>
                                <select class="form-select select2-single" id="supplier_id" name="supplier_id" required>
                                    <option value="">Select Supplier</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="order_date" class="form-label">Order Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="order_date" name="order_date" 
                                       value="{{ date('Y-m-d') }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="expected_delivery_date" class="form-label">Expected Delivery Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="expected_delivery_date" name="expected_delivery_date" 
                                       value="{{ date('Y-m-d', strtotime('+7 days')) }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

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

                    <!-- Hide Cost Price Toggle -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="hide_cost_price" name="hide_cost_price" value="1">
                                <label class="form-check-label" for="hide_cost_price">
                                    <strong>Hide Cost Prices</strong>
                                    <small class="text-muted d-block">Toggle this to hide cost and total columns if you don't want to specify pricing at this stage.</small>
                                </label>
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
                                            <th width="22%">Item</th>
                                            <th width="12%">Quantity</th>
                                            <th width="14%" class="cost-price-col">Cost Price</th>
                                            <th width="14%" class="vat-col">VAT</th>
                                            <th width="14%" class="vat-amount-col">VAT Amount</th>
                                            <th width="14%" class="line-total-col">Line Total</th>
                                            <th width="10%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="items-tbody">
                                        <!-- Items will be added here dynamically -->
                                    </tbody>
                                    <tfoot>
                                        <tr class="cost-summary-row">
                                            <td colspan="5" class="text-end"><strong>Subtotal (Without VAT):</strong></td>
                                            <td><strong id="subtotal">0.00</strong></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="subtotal" id="subtotal-input" value="0">
                                        <tr id="vat-row" class="cost-summary-row" style="display: none;">
                                            <td colspan="5" class="text-end"><strong>VAT Total:</strong></td>
                                            <td><strong id="vat-amount">0.00</strong></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="vat_amount" id="vat-amount-input" value="0">
                                        <tr class="cost-summary-row">
                                            <td colspan="5" class="text-end"><strong>Additional Tax:</strong></td>
                                            <td>
                                                <input type="number" class="form-control" id="tax_amount" name="tax_amount" 
                                                       value="0" step="0.01" min="0">
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr class="cost-summary-row">
                                            <td colspan="5" class="text-end"><strong>Discount:</strong></td>
                                            <td>
                                                <div class="row g-2">
                                                    <div class="col-md-5">
                                                        <select class="form-select" id="discount_type" name="discount_type">
                                                            <option value="percentage">Percentage (%)</option>
                                                            <option value="fixed" selected>Fixed Amount</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-7">
                                                        <div id="discount_rate_wrapper" style="display: none;">
                                                            <input type="number" class="form-control" id="discount_rate" name="discount_rate" value="0" step="0.01" min="0" max="100" placeholder="Rate %">
                                                        </div>
                                                        <div id="discount_amount_wrapper" style="display: block;">
                                                            <input type="number" class="form-control" id="discount_amount" name="discount_amount" value="0" step="0.01" min="0" placeholder="Amount">
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr class="table-info cost-summary-row">
                                            <td colspan="5" class="text-end"><strong>Total Amount:</strong></td>
                                            <td><strong id="total-amount">0.00</strong></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="total_amount" id="total-amount-input" value="0">
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Notes, Terms and Attachment -->
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="4" 
                                          placeholder="Additional notes for this order..."></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="terms_conditions" class="form-label">Terms & Conditions</label>
                                <textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="4" 
                                          placeholder="Terms and conditions..."></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="attachment" class="form-label">Attachment (optional)</label>
                                <input type="file" class="form-control @error('attachment') is-invalid @enderror"
                                       id="attachment" name="attachment"
                                       accept=".pdf,.jpg,.jpeg,.png">
                                @error('attachment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Upload a file (PDF or image, max 5MB).</small>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2 justify-content-end">
                                             <a href="{{ route('purchases.orders.index') }}" class="btn btn-outline-secondary">
                         <i class="bx bx-x me-1"></i>Cancel
                     </a>
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="bx bx-check me-1"></i>Create Order
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
                    <div class="col-md-6 price-fields">
                        <div class="mb-3">
                            <label for="modal_unit_price" class="form-label">Cost Price</label>
                            <input type="number" class="form-control" id="modal_unit_price" step="0.01" min="0">
                        </div>
                    </div>
                </div>

                <div class="row price-fields">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_item_vat_type" class="form-label">Item VAT Type</label>
                            <select class="form-select" id="modal_item_vat_type">
                                <option value="no_vat" {{ (function_exists('get_default_vat_type') && get_default_vat_type()=='no_vat') ? 'selected' : '' }}>No VAT</option>
                                <option value="vat_inclusive" {{ (function_exists('get_default_vat_type') && get_default_vat_type()=='inclusive') ? 'selected' : '' }}>VAT Inclusive</option>
                                <option value="vat_exclusive" {{ (function_exists('get_default_vat_type') && get_default_vat_type()=='exclusive') ? 'selected' : '' }}>VAT Exclusive</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_item_vat_rate" class="form-label">Item VAT Rate (%)</label>
                            <input type="number" class="form-control" id="modal_item_vat_rate" value="{{ function_exists('get_default_vat_rate') ? get_default_vat_rate() : 18 }}" step="0.01" min="0" max="100">
                            <small class="text-muted">Individual VAT rate for this item</small>
                        </div>
                    </div>
                </div>
                <div class="row price-fields">
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
$(document).ready(function() {
    let itemCounter = 0;

    // Initialize Select2
    $('.select2-single').select2({
        placeholder: 'Select',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap-5'
    });

    // Handle Hide Cost Price Toggle
    $('#hide_cost_price').change(function() {
        const isHidden = $(this).is(':checked');
        if (isHidden) {
            $('.cost-price-col, .vat-col, .vat-amount-col, .line-total-col').addClass('hidden');
            $('.cost-summary-row').addClass('hidden');
            $('.price-fields').addClass('hidden');
        } else {
            $('.cost-price-col, .vat-col, .vat-amount-col, .line-total-col').removeClass('hidden');
            $('.cost-summary-row').removeClass('hidden');
            $('.price-fields').removeClass('hidden');
        }
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

        const isHidden = $('#hide_cost_price').is(':checked');
        const hiddenClass = isHidden ? 'hidden' : '';

        const row = `
            <tr data-item-id="${itemId}" data-row-id="${itemCounter}">
                <td>
                    <strong>${itemName}</strong><br>
                    <small class="text-muted">${itemCode}</small>
                    <input type="hidden" name="items[${itemCounter}][item_id]" value="${itemId}">
                    <input type="hidden" name="items[${itemCounter}][description]" value="${description}">
                    <input type="hidden" name="items[${itemCounter}][vat_type]" value="${itemVatType}">
                    <input type="hidden" name="items[${itemCounter}][vat_rate]" value="${itemVatRate}">
                    <input type="hidden" name="items[${itemCounter}][vat_amount]" value="${itemVatAmount}">
                    <input type="hidden" name="items[${itemCounter}][line_total]" value="${lineTotal}">
                </td>
                <td>
                    <input type="number" class="form-control item-quantity" 
                           name="items[${itemCounter}][quantity]" value="${quantity}" 
                           step="0.01" min="0.01" data-row="${itemCounter}">
                </td>
                <td class="cost-price-col ${hiddenClass}">
                    <input type="number" class="form-control item-price" 
                           name="items[${itemCounter}][cost_price]" value="${unitPrice}" 
                           step="0.01" min="0" data-row="${itemCounter}">
                </td>
                <td class="vat-col ${hiddenClass}">
                    <span class="form-control-plaintext">${vatDisplay}</span>
                </td>
                <td class="vat-amount-col ${hiddenClass}">
                    <span class="item-vat-amount">${itemVatAmount.toFixed(2)}</span>
                </td>
                <td class="line-total-col ${hiddenClass}">
                    <span class="item-total">${lineTotal.toFixed(2)}</span>
                </td>
                <td>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-item">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#items-tbody').append(row);
        $('#itemModal').modal('hide');
        calculateTotals();
    });

    // Prefill items when available (from Quotation, Invoice Copy To, or Low Stock)
    @if(isset($prefillItems) && count($prefillItems) > 0)
        @php
            $prefillSupplierId = isset($quotation) ? $quotation->supplier_id : (isset($invoice) ? $invoice->supplier_id : null);
        @endphp
        const prefill = {
            supplier_id: {{ $prefillSupplierId !== null ? (int) $prefillSupplierId : 'null' }},
            is_rfq: {{ (isset($quotation) && $quotation->is_request_for_quotation) ? 'true' : 'false' }},
            items: @json($prefillItems)
        };

        // Set supplier if available
        if (prefill.supplier_id) {
            $('#supplier_id').val(prefill.supplier_id).trigger('change');
        }

        // Helper to map VAT type
        const mapVatType = (t) => {
            if (!t) return 'no_vat';
            if (t === 'inclusive' || t === 'vat_inclusive') return 'vat_inclusive';
            if (t === 'exclusive' || t === 'vat_exclusive') return 'vat_exclusive';
            return 'no_vat';
        };

        // Add each item row
        (prefill.items || []).forEach(function(qItem){
            const itemId = qItem.item_id;
            const itemName = qItem.item_name || '';
            const itemCode = qItem.item_code || '';
            const quantity = qItem.quantity || 0;
            const unitPrice = qItem.unit_price || 0;
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

            const isHidden = $('#hide_cost_price').is(':checked');
            const hiddenClass = isHidden ? 'hidden' : '';

            const row = `
                <tr data-item-id="${itemId}" data-row-id="${itemCounter}">
                    <td>
                        <strong>${itemName}</strong><br>
                        <small class="text-muted">${itemCode}</small>
                        <input type="hidden" name="items[${itemCounter}][item_id]" value="${itemId}">
                        <input type="hidden" name="items[${itemCounter}][description]" value="">
                        <input type="hidden" name="items[${itemCounter}][vat_type]" value="${itemVatType}">
                        <input type="hidden" name="items[${itemCounter}][vat_rate]" value="${itemVatRate}">
                        <input type="hidden" name="items[${itemCounter}][vat_amount]" value="${itemVatAmount}">
                        <input type="hidden" name="items[${itemCounter}][line_total]" value="${lineTotal}">
                    </td>
                    <td>
                        <input type="number" class="form-control item-quantity" 
                               name="items[${itemCounter}][quantity]" value="${quantity}" 
                               step="0.01" min="0.01" data-row="${itemCounter}">
                    </td>
                    <td class="cost-price-col ${hiddenClass}">
                        <input type="number" class="form-control item-price" 
                               name="items[${itemCounter}][cost_price]" value="${unitPrice}" 
                               step="0.01" min="0" data-row="${itemCounter}">
                    </td>
                    <td class="vat-col ${hiddenClass}">
                        <span class="form-control-plaintext">${vatDisplay}</span>
                    </td>
                    <td class="vat-amount-col ${hiddenClass}">
                        <span class="item-vat-amount">${itemVatAmount.toFixed(2)}</span>
                    </td>
                    <td class="line-total-col ${hiddenClass}">
                        <span class="item-total">${lineTotal.toFixed(2)}</span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-outline-danger btn-sm remove-item">
                            <i class="bx bx-trash"></i>
                        </button>
                    </td>
                </tr>
            `;

            $('#items-tbody').append(row);
        });
        calculateTotals();
    @endif

    // Remove item
    $(document).on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        calculateTotals();
    });

    // Recalculate on input change
    $(document).on('input', '.item-quantity, .item-price, #tax_amount, #discount_amount, #discount_rate', function() {
        const row = $(this).data('row');
        if (row) {
            updateRowTotal(row);
        }
        calculateTotals();
    });

    $('#discount_type').on('change', function(){
        const type = $(this).val();
        if (type === 'percentage') {
            $('#discount_rate_wrapper').show();
            $('#discount_amount_wrapper').hide();
        } else {
            $('#discount_rate_wrapper').hide();
            $('#discount_amount_wrapper').show();
        }
        calculateTotals();
    });

    // Form submission
    $('#order-form').submit(function(e) {
        e.preventDefault();
        
                 if ($('#items-tbody tr').length === 0) {
             Swal.fire('Error', 'Please add at least one item to the order', 'error');
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
             url: '{{ route("purchases.orders.store") }}',
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
                         window.location.href = '{{ route("purchases.orders.index") }}';
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
                submitBtn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Create Order');
            }
        });
    });

    function updateRowTotal(row) {
        const quantity = parseFloat($(`input[name="items[${row}][quantity]"]`).val()) || 0;
        const unitPrice = parseFloat($(`input[name="items[${row}][cost_price]"]`).val()) || 0;
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
        
        // Update display
        $(`tr[data-row-id="${row}"] .item-total`).text(`${lineTotal.toFixed(2)}`);
        $(`tr[data-row-id="${row}"] .item-vat-amount`).text(`${itemVatAmount.toFixed(2)}`);
    }

    function calculateTotals() {
        let subtotal = 0;
        let totalVatFromItems = 0;
        
        // Calculate subtotal and sum individual VAT amounts
        $('#items-tbody tr').each(function() {
            const rowId = $(this).data('row-id');
            const itemVatType = $(`input[name="items[${rowId}][vat_type]"]`).val();
            const itemVatRate = parseFloat($(`input[name="items[${rowId}][vat_rate]"]`).val()) || 0;
            const quantity = parseFloat($(`input[name="items[${rowId}][quantity]"]`).val()) || 0;
            const unitPrice = parseFloat($(`input[name="items[${rowId}][cost_price]"]`).val()) || 0;
            const lineTotal = parseFloat($(this).find('.item-total').text()) || 0;
            const itemVatAmount = parseFloat($(this).find('.item-vat-amount').text()) || 0;
            
            // Sum VAT amounts directly from items
            totalVatFromItems += itemVatAmount;
            
            // Calculate net subtotal (line total minus VAT for inclusive, or base amount for exclusive)
            if (itemVatType === 'vat_inclusive' && itemVatRate > 0) {
                // VAT is included, so net = lineTotal - VAT
                subtotal += lineTotal - itemVatAmount;
            } else if (itemVatType === 'vat_exclusive' && itemVatRate > 0) {
                // VAT is added on top, so net = base amount
                subtotal += quantity * unitPrice;
            } else {
                // No VAT
                subtotal += lineTotal;
            }
        });

        const tax = parseFloat($('#tax_amount').val()) || 0;
        let discount = 0;
        const discountType = $('#discount_type').val();
        if (discountType === 'percentage') {
            const rate = parseFloat($('#discount_rate').val()) || 0;
            discount = subtotal * (rate / 100);
        } else {
            discount = parseFloat($('#discount_amount').val()) || 0;
        }
        
        const total = subtotal + tax + totalVatFromItems - discount;

        $('#subtotal').text(`${subtotal.toFixed(2)}`);
        $('#subtotal-input').val(subtotal.toFixed(2));
        
        // Show/hide VAT row and update VAT amount
        if (totalVatFromItems > 0) {
            $('#vat-row').show();
            $('#vat-amount').text(`${totalVatFromItems.toFixed(2)}`);
            $('#vat-amount-input').val(totalVatFromItems.toFixed(2));
        } else {
            $('#vat-row').hide();
            $('#vat-amount-input').val('0');
        }
        
        $('#total-amount').text(`${total.toFixed(2)}`);
        $('#total-amount-input').val(total.toFixed(2));
    }

    function resetItemModal() {
        $('#modal_item_id').val('').trigger('change');
        $('#modal_quantity').val(1);
        $('#modal_unit_price').val(0);
        $('#modal_description').val('');
        // Reset to default VAT settings
        const defaultVatType = "{{ (function_exists('get_default_vat_type') && get_default_vat_type()=='inclusive') ? 'vat_inclusive' : ((function_exists('get_default_vat_type') && get_default_vat_type()=='exclusive') ? 'vat_exclusive' : 'no_vat') }}";
        const defaultVatRate = parseFloat("{{ function_exists('get_default_vat_rate') ? get_default_vat_rate() : 18 }}") || 18;
        $('#modal_item_vat_type').val(defaultVatType);
        $('#modal_item_vat_rate').val(defaultVatRate);
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
