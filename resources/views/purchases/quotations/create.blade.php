@extends('layouts.main')

@section('title', 'Create Purchase Quotation')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchases', 'url' => route('purchases.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Purchase Quotations', 'url' => route('purchases.quotations.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">CREATE PURCHASE QUOTATION</h6>
        <hr />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-file me-2"></i>New Purchase Quotation</h5>
            </div>
            <div class="card-body">
                <form id="quotation-form" enctype="multipart/form-data">
                    @csrf
                    @if(isset($requisition) && $requisition)
                        <input type="hidden" name="purchase_requisition_id" value="{{ $requisition->id }}">
                    @endif
                    <div class="row">
                        <!-- Supplier / Requisition Information -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                @if(isset($requisition) && $requisition)
                                    <label for="supplier_ids" class="form-label">
                                        Suppliers <span class="text-danger">*</span>
                                        <small class="text-muted d-block">Select one or more suppliers – one RFQ will be created per supplier.</small>
                                    </label>
                                    <select class="form-select select2-single" id="supplier_ids" name="supplier_ids[]" multiple required>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <label for="supplier_id" class="form-label">Supplier <span class="text-danger">*</span></label>
                                    <select class="form-select select2-single" id="supplier_id" name="supplier_id" required>
                                        <option value="">Select Supplier</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        @if(isset($requisition) && $requisition)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">From Requisition</label>
                                <div class="border rounded px-3 py-2 bg-light d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold">{{ $requisition->pr_no }}</div>
                                        <small class="text-muted">
                                            Dept: {{ $requisition->department->name ?? 'N/A' }} &middot;
                                            Total: {{ number_format($requisition->total_amount, 2) }}
                                        </small>
                                    </div>
                                    <a href="{{ route('purchases.requisitions.show', $requisition->hash_id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bx bx-link-external"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="{{ date('Y-m-d') }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="due_date" name="due_date" 
                                       value="{{ date('Y-m-d', strtotime('+30 days')) }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Request for Quotation Checkbox -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_request_for_quotation" name="is_request_for_quotation">
                                    <label class="form-check-label" for="is_request_for_quotation">
                                        <strong>This is a Request for Quotation (RFQ)</strong>
                                        <small class="text-muted d-block">Check this if you want suppliers to provide pricing. Only item and quantity will be shown.</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Section -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Items</h6>
                                @if(!isset($requisition) || !$requisition)
                                    <button type="button" class="btn btn-primary btn-sm" id="add-item">
                                        <i class="bx bx-plus me-1"></i>Add Item
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle" id="items-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="30%">Item</th>
                                            <th width="20%">Quantity</th>
                                            <th width="15%" class="price-column">Cost Price</th>
                                            <th width="15%" class="vat-column">VAT</th>
                                            <th width="15%" class="total-column">Total</th>
                                            <th width="5%" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="items-tbody">
                                        <!-- Items will be added here dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Totals Section -->
                    <div class="card mt-4">
                        <div class="card-body">
                            <div class="row justify-content-end">
                                <div class="col-md-6">
                                    <table class="table table-borderless mb-0">
                                        <tbody>
                                            <tr id="rfq-totals-note-row" class="d-none">
                                                <td colspan="2" class="text-end text-muted">
                                                    <em>Totals will be determined from supplier quotations (RFQ has no prices).</em>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-end"><strong>Total Quantity:</strong></td>
                                                <td class="text-end" id="total-quantity-amount">0</td>
                                            </tr>
                                            <tr id="totals-subtotal-row">
                                                <td class="text-end"><strong>Subtotal:</strong></td>
                                                <td class="text-end" id="subtotal-amount">TZS 0.00</td>
                                            </tr>
                                            <tr id="totals-vat-row">
                                                <td class="text-end"><strong>VAT Total:</strong></td>
                                                <td class="text-end" id="vat-total-amount">TZS 0.00</td>
                                            </tr>
                                            <tr id="totals-total-row" class="border-top">
                                                <td class="text-end"><strong>Total Amount:</strong></td>
                                                <td class="text-end"><strong id="grand-total-amount">TZS 0.00</strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes, Terms and Attachment -->
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="4" 
                                          placeholder="Additional notes for this quotation..."></textarea>
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
                        <button type="button" class="btn btn-outline-secondary" id="cancel-btn">
                            <i class="bx bx-x me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="bx bx-check me-1"></i>Create Quotation
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
                    <select class="form-select select2-single" id="modal_item_id">
                        <option value="">Search and select item...</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" 
                                    data-name="{{ $item->name }}"
                                    data-code="{{ $item->code }}"
                                    data-unit="{{ $item->unit_of_measure }}"
                                    data-price="{{ $item->resolved_cost_price ?? $item->cost_price ?? 0 }}">
                                {{ $item->name }} ({{ $item->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="modal_quantity" step="0.01" min="0.01" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_unit" class="form-label">Unit</label>
                            <input type="text" class="form-control" id="modal_unit" readonly>
                        </div>
                    </div>
                </div>
                <div class="row price-fields">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_unit_price" class="form-label">Cost Price</label>
                            <input type="number" class="form-control" id="modal_unit_price" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_vat_type" class="form-label">VAT Type</label>
                            <select class="form-select" id="modal_vat_type">
                                <option value="no_vat" {{ (function_exists('get_default_vat_type') && get_default_vat_type()=='no_vat') ? 'selected' : '' }}>No VAT</option>
                                <option value="inclusive" {{ (function_exists('get_default_vat_type') && get_default_vat_type()=='inclusive') ? 'selected' : '' }}>VAT Inclusive</option>
                                <option value="exclusive" {{ (function_exists('get_default_vat_type') && get_default_vat_type()=='exclusive') ? 'selected' : '' }}>VAT Exclusive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_vat_rate" class="form-label">VAT Rate (%)</label>
                            <input type="number" class="form-control" id="modal_vat_rate" step="0.01" min="0" value="{{ function_exists('get_default_vat_rate') ? get_default_vat_rate() : 0 }}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="add-item-btn">Add Item</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
/* Ensure table maintains structure when columns are hidden */
.price-column, .vat-column, .total-column {
    transition: all 0.3s ease;
}

.price-column.hidden, .vat-column.hidden, .total-column.hidden {
    display: none !important;
}

/* Ensure consistent column widths */
#items-table th, #items-table td {
    vertical-align: middle;
}

#items-table .price-column,
#items-table .vat-column,
#items-table .total-column {
    text-align: center;
}
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    const defaultVatRate = parseFloat("{{ function_exists('get_default_vat_rate') ? get_default_vat_rate() : 0 }}") || 0;
    let itemCounter = 0;
    let items = [];

    // If this RFQ is created from a requisition, quantities come from PR and should not be edited
    const isFromRequisition = {{ isset($requisition) && $requisition ? 'true' : 'false' }};

    // Preloaded requisition items (inventory only), passed from backend as JSON-safe structure
    const requisitionItems = @json($requisitionItems ?? []);

    // Initialize Select2 (excluding modal item dropdown)
    $('.select2-single').not('#modal_item_id').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Initialize Select2 for modal item dropdown with search enabled
    $('#modal_item_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        dropdownParent: $('#itemModal'),
        placeholder: 'Search and select item...',
        allowClear: true,
        minimumInputLength: 0
    });

    // Handle Request for Quotation checkbox
    $('#is_request_for_quotation').change(function() {
        const isRFQ = $(this).is(':checked');
        
        if (isRFQ) {
            // Hide price-related columns
            $('.price-column, .vat-column, .total-column').addClass('hidden');
            $('.price-fields').hide();
            // Hide numeric totals and show RFQ note
            $('#totals-subtotal-row, #totals-vat-row, #totals-total-row').addClass('d-none');
            $('#rfq-totals-note-row').removeClass('d-none');
        } else {
            // Show all columns
            $('.price-column, .vat-column, .total-column').removeClass('hidden');
            $('.price-fields').show();
            // Show numeric totals and hide RFQ note
            $('#totals-subtotal-row, #totals-vat-row, #totals-total-row').removeClass('d-none');
            $('#rfq-totals-note-row').addClass('d-none');
        }
    });
    // Apply initial toggle state on load
    $('#is_request_for_quotation').trigger('change');

    // If this quotation is created from a requisition, pre-fill items in RFQ mode
    if (Array.isArray(requisitionItems) && requisitionItems.length > 0) {
        // Force RFQ mode (no prices) when coming from PR
        $('#is_request_for_quotation').prop('checked', true).trigger('change');

        requisitionItems.forEach(function (src) {
            const item = {
                id: itemCounter++,
                item_id: src.item_id || '',
                item_type: src.item_type || 'inventory',
                asset_id: src.asset_id || '',
                fixed_asset_category_id: src.fixed_asset_category_id || '',
                intangible_asset_category_id: src.intangible_asset_category_id || '',
                description: src.description || '',
                item_name: src.item_name || src.description || ('Line ' + itemCounter),
                item_code: src.item_code || '',
                quantity: parseFloat(src.quantity) || 0,
                unit: src.unit || 'units',
                unit_price: 0,
                vat_type: 'no_vat',
                vat_rate: 0,
                vat_amount: 0,
                subtotal: 0,
                total_amount: 0,
            };

            if (item.quantity > 0) {
                items.push(item);
                addItemToTable(item, true); // RFQ: no price/VAT
            }
        });
    }

    // Prefill from Copy To (purchase invoice)
    @if(isset($sourceInvoice) && $sourceInvoice && $sourceInvoice->items->isNotEmpty())
        $('#supplier_id').val('{{ $sourceInvoice->supplier_id }}').trigger('change');
        $('#is_request_for_quotation').prop('checked', false).trigger('change');
        @foreach($sourceInvoice->items as $line)
            @php
                $invItem = $line->inventoryItem;
                $unit = $invItem ? ($invItem->unit_of_measure ?? 'units') : 'units';
                $itemName = $invItem ? $invItem->name : ($line->description ?? 'Item');
                $itemCode = $invItem ? $invItem->code : '';
            @endphp
            (function() {
                const item = {
                    id: ++itemCounter,
                    item_id: '{{ $line->inventory_item_id }}',
                    item_type: 'inventory',
                    asset_id: '',
                    fixed_asset_category_id: '',
                    intangible_asset_category_id: '',
                    description: {{ json_encode($line->description ?? '') }},
                    item_name: {{ json_encode($itemName) }},
                    item_code: {{ json_encode($itemCode) }},
                    quantity: {{ (float) $line->quantity }},
                    unit: {{ json_encode($unit) }},
                    unit_price: {{ (float) ($line->unit_cost ?? 0) }},
                    vat_type: {{ json_encode($line->vat_type ?? 'no_vat') }},
                    vat_rate: {{ (float) ($line->vat_rate ?? 0) }},
                    vat_amount: {{ (float) ($line->vat_amount ?? 0) }},
                    subtotal: {{ (float) ($line->line_total ?? 0) }},
                    total_amount: {{ (float) ($line->line_total ?? 0) }},
                };
                items.push(item);
                addItemToTable(item, false);
            })();
        @endforeach
    @endif

    // Add Item button click - initialize VAT defaults on open
    const defaultVatType = "{{ function_exists('get_default_vat_type') ? get_default_vat_type() : 'no_vat' }}";
    $('#add-item').click(function() {
        // Reset modal fields to defaults from settings
        $('#modal_item_id').val('').trigger('change');
        $('#modal_quantity').val('');
        $('#modal_unit').val('');
        $('#modal_unit_price').val('');
        $('#modal_vat_type').val(defaultVatType);
        if (defaultVatType === 'no_vat') {
            $('#modal_vat_rate').prop('disabled', true).val('0');
        } else {
            $('#modal_vat_rate').prop('disabled', false).val(defaultVatRate);
        }
        $('#itemModal').modal('show');
    });

    // Modal item selection change
    $('#modal_item_id').change(function() {
        const selectedOption = $(this).find('option:selected');
        const unit = selectedOption.data('unit') || 'units';
        const price = selectedOption.data('price') || 0;
        
        $('#modal_unit').val(unit);
        $('#modal_unit_price').val(price);
    });

    // VAT type change
    $('#modal_vat_type').change(function() {
        const vatType = $(this).val();
        if (vatType === 'no_vat') {
            $('#modal_vat_rate').prop('disabled', true).val('0');
        } else {
            $('#modal_vat_rate').prop('disabled', false).val(defaultVatRate);
        }
    });
    // Initialize VAT controls according to default
    $('#modal_vat_type').trigger('change');

    // Add Item from modal
    $('#add-item-btn').click(function() {
        const itemId = $('#modal_item_id').val();
        const itemName = $('#modal_item_id option:selected').text();
        const itemCode = $('#modal_item_id option:selected').data('code');
        const quantity = parseFloat($('#modal_quantity').val()) || 0;
        const unit = $('#modal_unit').val();
        const unitPrice = parseFloat($('#modal_unit_price').val()) || 0;
        const isRFQ = $('#is_request_for_quotation').is(':checked');
        
        // For RFQ, set default values
        const vatType = isRFQ ? 'no_vat' : $('#modal_vat_type').val();
        const vatRate = isRFQ ? 0 : (vatType === 'no_vat' ? 0 : (parseFloat($('#modal_vat_rate').val()) || 0));

        if (!itemId || quantity <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Input',
                text: 'Please select an item and enter a valid quantity.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        // Check if item already exists
        if (items.some(item => item.item_id == itemId)) {
            Swal.fire({
                icon: 'warning',
                title: 'Item Already Added',
                text: 'This item is already added to the quotation.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        // Calculate VAT and totals based on VAT type (align with sales invoice: subtotal is net)
        let vatAmount = 0;
        let gross = quantity * unitPrice;
        let net = gross;
        let totalAmount = gross;

        if (!isRFQ) {
            if (vatType === 'inclusive' && vatRate > 0) {
                vatAmount = (gross * vatRate) / (100 + vatRate);
                net = gross - vatAmount;
                totalAmount = gross;
            } else if (vatType === 'exclusive' && vatRate > 0) {
                vatAmount = (gross * vatRate) / 100;
                net = gross;
                totalAmount = net + vatAmount;
            } else {
                net = gross;
                vatAmount = 0;
                totalAmount = gross;
            }
        } else {
            net = gross;
            vatAmount = 0;
            totalAmount = gross;
        }

        const item = {
            id: itemCounter++,
            item_id: itemId,
            item_type: 'inventory',
            asset_id: '',
            fixed_asset_category_id: '',
            intangible_asset_category_id: '',
            description: '',
            item_name: itemName,
            item_code: itemCode,
            quantity: quantity,
            unit: unit,
            unit_price: isRFQ ? 0 : unitPrice,
            vat_type: vatType,
            vat_rate: vatRate,
            vat_amount: vatAmount,
            subtotal: net,
            total_amount: totalAmount
        };

        items.push(item);
        addItemToTable(item, isRFQ);
        
        // Apply RFQ state to the new row
        if (isRFQ) {
            $(`tr[data-item-id="${item.id}"] .price-column, tr[data-item-id="${item.id}"] .vat-column, tr[data-item-id="${item.id}"] .total-column`).addClass('hidden');
        }

        // Reset modal
        $('#modal_item_id').val('').trigger('change');
        $('#modal_quantity').val('');
        $('#modal_unit').val('');
        $('#modal_unit_price').val('');
        // Reset to defaults from settings for next add
        $('#modal_vat_type').val(defaultVatType);
        if (defaultVatType === 'no_vat') {
            $('#modal_vat_rate').val('0').prop('disabled', true);
        } else {
            $('#modal_vat_rate').val(defaultVatRate).prop('disabled', false);
        }
        $('#itemModal').modal('hide');
    });

    // Add item to table
    function addItemToTable(item, isRFQ) {
        const type = item.item_type || 'inventory';
        // Use success color for type badge for better visibility
        const typeBadgeClass = 'bg-success text-white';

        const typeLabel = type === 'fixed_asset'
            ? 'Fixed Asset'
            : (type === 'intangible' ? 'Intangible' : 'Inventory');

        let quantityCellHtml;
        if (isFromRequisition) {
            quantityCellHtml = `
                <td>
                    <div class="d-flex align-items-center">
                        <span class="fw-semibold">${item.quantity}</span>
                        <span class="ms-1 text-muted">${item.unit}</span>
                    </div>
                    <input type="hidden" name="items[${item.id}][quantity]" value="${item.quantity}">
                </td>
            `;
        } else {
            quantityCellHtml = `
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number" class="form-control quantity-input" 
                               value="${item.quantity}" 
                               step="0.01" min="0.01" 
                               data-item-id="${item.id}"
                               style="width: 80px;">
                        <span class="input-group-text">${item.unit}</span>
                    </div>
                    <input type="hidden" name="items[${item.id}][quantity]" value="${item.quantity}">
                </td>
            `;
        }

        let row = `
            <tr data-item-id="${item.id}">
                <td>
                    <div class="d-flex flex-column">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <strong>${item.item_name}</strong>
                            <span class="badge ${typeBadgeClass} rounded-pill">
                                ${typeLabel}
                            </span>
                        </div>
                        <small class="text-muted d-block">${item.item_code}</small>
                        ${item.description ? `<small class="text-muted d-block">${item.description}</small>` : ''}
                    </div>
                    <input type="hidden" name="items[${item.id}][item_id]" value="${item.item_id}">
                    <input type="hidden" name="items[${item.id}][item_type]" value="${item.item_type || ''}">
                    <input type="hidden" name="items[${item.id}][asset_id]" value="${item.asset_id || ''}">
                    <input type="hidden" name="items[${item.id}][fixed_asset_category_id]" value="${item.fixed_asset_category_id || ''}">
                    <input type="hidden" name="items[${item.id}][intangible_asset_category_id]" value="${item.intangible_asset_category_id || ''}">
                    <input type="hidden" name="items[${item.id}][description]" value="${item.description || ''}">
                    <input type="hidden" name="items[${item.id}][unit_of_measure]" value="${item.unit || ''}">
                </td>
                ${quantityCellHtml}
                <td class="price-column">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">TZS</span>
                        <input type="number" class="form-control unit-price-input" 
                               value="${item.unit_price}" 
                               step="0.01" min="0" 
                               data-item-id="${item.id}"
                               style="width: 100px;">
                    </div>
                    <input type="hidden" name="items[${item.id}][unit_price]" value="${item.unit_price}">
                </td>
                <td class="vat-column">
                    ${item.vat_type === 'no_vat' ? 'No VAT' : (item.vat_type === 'inclusive' ? 'Inclusive' : 'Exclusive')} ${item.vat_rate > 0 ? `(${item.vat_rate}%)` : ''}
                    <br><small class="text-muted">${formatCurrency(item.vat_amount)}</small>
                    <input type="hidden" name="items[${item.id}][vat_type]" value="${item.vat_type}">
                    <input type="hidden" name="items[${item.id}][vat_rate]" value="${item.vat_rate}">
                    <input type="hidden" name="items[${item.id}][vat_amount]" value="${item.vat_amount}">
                </td>
                <td class="total-column text-end">
                    <span class="fw-semibold">${formatCurrency(item.total_amount)}</span>
                    <input type="hidden" name="items[${item.id}][total_amount]" value="${item.total_amount}">
                </td>
                
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm remove-item" data-item-id="${item.id}">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            </tr>`;

        $('#items-tbody').append(row);

        // If currently in RFQ mode, hide price/VAT/Total columns for this row as well
        const rfqActive = typeof isRFQ !== 'undefined'
            ? isRFQ
            : $('#is_request_for_quotation').is(':checked');
        if (rfqActive) {
            const selector = `tr[data-item-id="${item.id}"] .price-column, tr[data-item-id="${item.id}"] .vat-column, tr[data-item-id="${item.id}"] .total-column`;
            $(selector).addClass('hidden');
        }
        updateTotals();
    }

    // Remove item
    $(document).on('click', '.remove-item', function() {
        const itemId = $(this).data('item-id');
        const itemName = $(this).closest('tr').find('strong').first().text();
        
        Swal.fire({
            icon: 'question',
            title: 'Remove Item',
            text: `Are you sure you want to remove "${itemName}" from the quotation?`,
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, remove it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                items = items.filter(item => item.id != itemId);
                $(`tr[data-item-id="${itemId}"]`).remove();
                updateTotals();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Removed!',
                    text: 'Item has been removed from the quotation.',
                    showConfirmButton: false,
                    timer: 1000
                });
            }
        });
    });

    // Handle unit price change
    $(document).on('change', '.unit-price-input', function() {
        const itemId = parseInt($(this).data('item-id'));
        const newUnitPrice = parseFloat($(this).val()) || 0;
        
        if (newUnitPrice < 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Price',
                text: 'Unit price cannot be negative.',
                confirmButtonColor: '#3085d6'
            });
            // Reset to original value
            const item = items.find(i => i.id === itemId);
            $(this).val(item.unit_price);
            return;
        }

        // Find and update the item
        const itemIndex = items.findIndex(i => i.id === itemId);
        if (itemIndex !== -1) {
            const item = items[itemIndex];
            const oldUnitPrice = item.unit_price;
            item.unit_price = newUnitPrice;
            
            // Recalculate item totals (subtotal is net)
            const grossAfter = item.quantity * newUnitPrice;
            if (item.vat_type === 'inclusive' && item.vat_rate > 0) {
                item.vat_amount = (grossAfter * item.vat_rate) / (100 + item.vat_rate);
                item.subtotal = grossAfter - item.vat_amount;
                item.total_amount = grossAfter;
            } else if (item.vat_type === 'exclusive' && item.vat_rate > 0) {
                item.subtotal = grossAfter;
                item.vat_amount = (item.subtotal * item.vat_rate) / 100;
                item.total_amount = item.subtotal + item.vat_amount;
            } else {
                item.subtotal = grossAfter;
                item.vat_amount = 0;
                item.total_amount = grossAfter;
            }
            
            // Update hidden inputs
            $(`input[name="items[${itemId}][unit_price]"]`).val(newUnitPrice);
            $(`input[name="items[${itemId}][vat_amount]"]`).val(item.vat_amount);
            $(`input[name="items[${itemId}][total_amount]"]`).val(item.total_amount);
            
            // Update display
            updateItemDisplay(itemId);
            updateTotals();
            
            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'Updated!',
                text: `Unit price updated from ${formatCurrency(oldUnitPrice)} to ${formatCurrency(newUnitPrice)}`,
                showConfirmButton: false,
                timer: 1000
            });
        }
    });

    // Handle quantity change
    $(document).on('change', '.quantity-input', function() {
        const itemId = parseInt($(this).data('item-id'));
        const newQuantity = parseFloat($(this).val()) || 0;
        
        if (newQuantity <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Quantity',
                text: 'Quantity must be greater than 0.',
                confirmButtonColor: '#3085d6'
            });
            // Reset to original value
            const item = items.find(i => i.id === itemId);
            $(this).val(item.quantity);
            return;
        }

        // Find and update the item
        const itemIndex = items.findIndex(i => i.id === itemId);
        if (itemIndex !== -1) {
            const item = items[itemIndex];
            const oldQuantity = item.quantity;
            item.quantity = newQuantity;
            
            // Recalculate item totals (subtotal is net)
            const grossQty = newQuantity * item.unit_price;
            if (item.vat_type === 'inclusive' && item.vat_rate > 0) {
                item.vat_amount = (grossQty * item.vat_rate) / (100 + item.vat_rate);
                item.subtotal = grossQty - item.vat_amount;
                item.total_amount = grossQty;
            } else if (item.vat_type === 'exclusive' && item.vat_rate > 0) {
                item.subtotal = grossQty;
                item.vat_amount = (item.subtotal * item.vat_rate) / 100;
                item.total_amount = item.subtotal + item.vat_amount;
            } else {
                item.subtotal = grossQty;
                item.vat_amount = 0;
                item.total_amount = grossQty;
            }
            
            // Update hidden inputs
            $(`input[name="items[${itemId}][quantity]"]`).val(newQuantity);
            $(`input[name="items[${itemId}][vat_amount]"]`).val(item.vat_amount);
            $(`input[name="items[${itemId}][total_amount]"]`).val(item.total_amount);
            
            // Update display
            updateItemDisplay(itemId);
            updateTotals();
            
            // Show success message
            Swal.fire({
                icon: 'success',
                title: 'Updated!',
                text: `Quantity updated from ${oldQuantity} to ${newQuantity}`,
                showConfirmButton: false,
                timer: 1000
            });
        }
    });

    // Update item display in table
    function updateItemDisplay(itemId) {
        const item = items.find(i => i.id === itemId);
        if (item) {
            const row = $(`tr[data-item-id="${itemId}"]`);
            
            // Update VAT column
            const vatCell = row.find('.vat-column');
            vatCell.html(`
                ${item.vat_type === 'no_vat' ? 'No VAT' : (item.vat_type === 'inclusive' ? 'Inclusive' : 'Exclusive')} ${item.vat_rate > 0 ? `(${item.vat_rate}%)` : ''}
                <br><small class="text-muted">${formatCurrency(item.vat_amount)}</small>
                <input type="hidden" name="items[${itemId}][vat_type]" value="${item.vat_type}">
                <input type="hidden" name="items[${itemId}][vat_rate]" value="${item.vat_rate}">
                <input type="hidden" name="items[${itemId}][vat_amount]" value="${item.vat_amount}">
            `);
            
            // Update total column
            const totalCell = row.find('.total-column');
            totalCell.html(`
                ${formatCurrency(item.total_amount)}
                <input type="hidden" name="items[${itemId}][total_amount]" value="${item.total_amount}">
            `);
        }
    }

    // Update totals
    function updateTotals() {
        let totalQty = 0;
        let subtotal = 0;
        let vatTotal = 0;
        let grandTotal = 0;

        items.forEach(item => {
            totalQty += parseFloat(item.quantity) || 0;
            subtotal += item.subtotal || 0;
            vatTotal += item.vat_amount || 0;
            grandTotal += item.total_amount || 0;
        });

        $('#total-quantity-amount').text(formatQuantity(totalQty));
        $('#subtotal-amount').text('TZS ' + formatCurrency(subtotal));
        $('#vat-total-amount').text('TZS ' + formatCurrency(vatTotal));
        $('#grand-total-amount').text('TZS ' + formatCurrency(grandTotal));
    }

    function formatQuantity(qty) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        }).format(qty);
    }
    // Format currency
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    }

    // Handle cancel button
    $('#cancel-btn').click(function() {
        if (items.length > 0) {
            Swal.fire({
                icon: 'question',
                title: 'Cancel Creation',
                text: 'Are you sure you want to cancel? All entered data will be lost.',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, cancel it!',
                cancelButtonText: 'Continue editing'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '{{ route("purchases.quotations.index") }}';
                }
            });
        } else {
            window.location.href = '{{ route("purchases.quotations.index") }}';
        }
    });

    // Handle form submission
    $('#quotation-form').submit(function(e) {
        e.preventDefault();

        if (items.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Items Added',
                text: 'Please add at least one item to the quotation.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        // Show loading state
        const submitBtn = $('#submit-btn');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Creating...');

        // Prepare form data using FormData to support file uploads
        const formData = new FormData($('#quotation-form')[0]);
        
        // Ensure is_request_for_quotation is properly set
        const isRFQ = $('#is_request_for_quotation').is(':checked');
        formData.append('is_request_for_quotation', isRFQ ? '1' : '0');

        // Add items array to form data (Laravel expects array format)
        items.forEach((item, index) => {
            formData.append(`items[${index}][item_id]`, item.item_id);
            formData.append(`items[${index}][quantity]`, item.quantity);
            formData.append(`items[${index}][unit_price]`, item.unit_price || 0);
            formData.append(`items[${index}][vat_type]`, item.vat_type || 'no_vat');
            formData.append(`items[${index}][vat_rate]`, item.vat_rate || 0);
            formData.append(`items[${index}][vat_amount]`, item.vat_amount || 0);
            formData.append(`items[${index}][total_amount]`, item.total_amount || 0);
        });

        // Submit form
        $.ajax({
            url: '{{ route("purchases.quotations.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = response.redirect_url;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message,
                        confirmButtonColor: '#d33'
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while creating the quotation.';
                
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMessage = 'Please fix the following errors:\n\n';
                    Object.keys(xhr.responseJSON.errors).forEach(key => {
                        errorMessage += '• ' + xhr.responseJSON.errors[key][0] + '\n';
                    });
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: errorMessage.replace(/\n/g, '<br>'),
                    confirmButtonColor: '#d33'
                });
            },
            complete: function() {
                // Reset button state
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
@endpush 