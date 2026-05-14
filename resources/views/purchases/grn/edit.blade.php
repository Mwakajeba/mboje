@extends('layouts.main')

@section('title', 'Edit GRN')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchases', 'url' => route('purchases.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'GRN', 'url' => route('purchases.grn.index'), 'icon' => 'bx bx-package'],
			['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
		]" />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-package me-2"></i>Edit GRN: GRN-{{ str_pad($grn->id, 6, '0', STR_PAD_LEFT) }}</h5>
            </div>
            <div class="card-body">
                <form id="grn-form" action="{{ route('purchases.grn.update', $grn->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <!-- Supplier / Receipt info -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Supplier</label>
                                <input type="text" class="form-control" value="{{ optional(optional($grn->purchaseOrder)->supplier)->name ?? 'Standalone GRN' }}" disabled>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="receipt_date" class="form-label">Receipt Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="receipt_date" name="receipt_date" value="{{ $grn->receipt_date?->format('Y-m-d') }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        @if(isset($warehouses) && $warehouses->count())
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Warehouse</label>
                                <select class="form-select" name="warehouse_id">
                                    <option value="">Select Warehouse</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ $grn->warehouse_id == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- (Order-specific hidden/payment fields removed for GRN) -->

                    <!-- Items Section -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">GRN Items</h6>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table" id="items-table">
                                    <thead>
                                        <tr>
                                            <th width="30%">Item</th>
                                            <th width="12%">Qty Ordered</th>
                                            <th width="12%">Qty Received</th>
                                            @if(($grn->quality_check_status ?? 'pending') === 'partial')
                                            <th width="12%">Accepted Qty</th>
                                            <th width="12%">Item QC</th>
                                            @endif
                                            <th width="12%">Unit Cost</th>
                                            <th width="12%">Total</th>
                                            <th width="8%"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="items-tbody">
                                        @foreach($grn->items as $item)
                                        <tr>
                                            <td>
                                                <strong>{{ optional($item->inventoryItem)->name ?? '-' }}</strong><br>
                                                <small class="text-muted">{{ optional($item->inventoryItem)->code ?? '-' }}</small>
                                            </td>
                                            <td>
                                                {{ number_format($item->quantity_ordered, 2, '.', '') }}
                                            </td>
                                            <td>
                                                {{ number_format($item->quantity_received, 2, '.', '') }}
                                            </td>
                                            @if(($grn->quality_check_status ?? 'pending') === 'partial')
                                            <td>
                                                <input type="number" name="items[{{ $item->id }}][accepted_quantity]" class="form-control form-control-sm" step="0.01" min="0" max="{{ number_format($item->quantity_received, 2, '.', '') }}" value="{{ number_format(old('items.' . $item->id . '.accepted_quantity', ($item->accepted_quantity > 0 ? $item->accepted_quantity : $item->quantity_received)), 2, '.', '') }}">
                                            </td>
                                            <td>
                                                <select name="items[{{ $item->id }}][item_qc_status]" class="form-select form-select-sm">
                                                    <option value="pending" {{ ($item->item_qc_status ?? 'pending') === 'pending' ? 'selected' : '' }}>Pending</option>
                                                    <option value="passed" {{ ($item->item_qc_status ?? '') === 'passed' ? 'selected' : '' }}>Passed</option>
                                                    <option value="failed" {{ ($item->item_qc_status ?? '') === 'failed' ? 'selected' : '' }}>Failed</option>
                                                </select>
                                            </td>
                                            @endif
                                            <td>
                                                {{ number_format($item->unit_cost, 2, '.', '') }}
                                            </td>
                                            <td>
                                                <strong>{{ number_format($item->total_cost, 2, '.', '') }}</strong>
                                            </td>
                                            <td></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                            <td><strong id="subtotal">0.00</strong></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr id="vat-row" style="display: none;">
                                            <td colspan="4" class="text-end"><strong>VAT (<span id="vat-rate-display">0</span>%):</strong></td>
                                            <td><strong id="vat-amount">0.00</strong></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr class="table-info">
                                            <td colspan="4" class="text-end"><strong>Total Amount:</strong></td>
                                            <td><strong id="total-amount">{{ number_format($grn->total_amount, 2, '.', '') }}</strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                                <input type="hidden" name="subtotal" id="subtotal-input" value="{{ number_format($grn->items->sum('total_cost'), 2, '.', '') }}">
                                <input type="hidden" name="total_amount" id="total-amount-input" value="{{ number_format($grn->total_amount, 2, '.', '') }}">
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="4">{{ $grn->notes }}</textarea>
                            </div>
                        </div>
                        
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('purchases.orders.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="bx bx-check me-1"></i>Update Order
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
                        @foreach($inventoryItems as $item)
                        <option value="{{ $item->id }}" data-name="{{ $item->name }}" data-code="{{ $item->code }}" data-price="{{ $item->resolved_cost_price ?? $item->cost_price }}" data-stock="{{ $item->current_stock }}">
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
                            <input type="number" class="form-control" id="modal_unit_price" step="0.01" min="0" value="0">
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
            let itemCounter = $('#items-tbody tr').length || 0;

            // Select2
            $('.select2-single').select2({
                placeholder: 'Select'
                , allowClear: true
                , width: '100%'
                , theme: 'bootstrap-5'
            });
            $('.select2-modal').select2({
                placeholder: 'Search for an item...'
                , allowClear: true
                , width: '100%'
                , theme: 'bootstrap-5'
                , dropdownParent: $('#itemModal')
            });

            // Open modal
            $('#add-item').click(function() {
                $('#itemModal').modal('show');
                resetItemModal();
            });

            // Modal interactions
            $('#modal_item_id').change(function() {
                const price = $(this).find(':selected').data('price');
                $('#modal_unit_price').val(price || 0);
                calculateModalItemTotal();
            });
            $('#modal_quantity, #modal_unit_price, #modal_item_vat_rate').on('input', calculateModalItemTotal);
            $('#modal_item_vat_type').on('change', calculateModalItemTotal);

            // Add item to table
            $('#add-item-to-table').click(function() {
                const itemId = $('#modal_item_id').val();
                const itemName = $('#modal_item_id option:selected').data('name');
                const itemCode = $('#modal_item_id option:selected').data('code');
                const quantity = parseFloat($('#modal_quantity').val()) || 0;
                const unitPrice = parseFloat($('#modal_unit_price').val()) || 0;
                const itemVatType = $('#modal_item_vat_type').val();
                const itemVatRate = parseFloat($('#modal_item_vat_rate').val()) || 0;

                if (!itemId || quantity <= 0) {
                    Swal.fire('Error', 'Please select an item and enter a valid quantity', 'error');
                    return;
                }
                if ($(`tr[data-item-id="${itemId}"]`).length > 0) {
                    Swal.fire('Error', 'This item is already added. Please edit the existing entry.', 'error');
                    return;
                }

                const baseAmount = quantity * unitPrice;
                let lineTotal = baseAmount;
                let itemVatAmount = 0;
                if (itemVatType === 'vat_inclusive' && itemVatRate > 0) {
                    const f = itemVatRate / 100;
                    itemVatAmount = lineTotal * f / (1 + f);
                } else if (itemVatType === 'vat_exclusive' && itemVatRate > 0) {
                    itemVatAmount = lineTotal * (itemVatRate / 100);
                    lineTotal += itemVatAmount;
                }

                itemCounter++;
                const row = `
			<tr data-item-id="${itemId}" data-row-id="${itemCounter}">
				<td>
					<strong>${itemName}</strong><br>
					<small class="text-muted">${itemCode}</small>
					<input type="hidden" name="items[${itemCounter}][item_id]" value="${itemId}">
					<input type="hidden" name="items[${itemCounter}][vat_type]" value="${itemVatType}">
					<input type="hidden" name="items[${itemCounter}][vat_rate]" value="${itemVatRate}">
					<input type="hidden" name="items[${itemCounter}][vat_amount]" value="${itemVatAmount}">
					<input type="hidden" name="items[${itemCounter}][total_amount]" value="${lineTotal}">
				</td>
				<td><input type="number" class="form-control item-quantity" name="items[${itemCounter}][quantity]" value="${quantity}" step="0.01" min="0.01" data-row="${itemCounter}"></td>
				<td><input type="number" class="form-control item-price" name="items[${itemCounter}][cost_price]" value="${unitPrice}" step="0.01" min="0" data-row="${itemCounter}"></td>
				<td><span class="form-control-plaintext">${itemVatType==='no_vat' ? 'No VAT' : (itemVatRate+'% '+itemVatType.replace('_',' '))}</span></td>
				<td><span class="item-total">${lineTotal.toFixed(2)}</span></td>
				<td><button type="button" class="btn btn-outline-danger btn-sm remove-item"><i class="bx bx-trash"></i></button></td>
				<td></td>
			</tr>`;
                $('#items-tbody').append(row);
                $('#itemModal').modal('hide');
                calculateTotals();
            });

            // Row events
            $(document).on('click', '.remove-item', function() {
                $(this).closest('tr').remove();
                calculateTotals();
            });
            $(document).on('input', '.item-quantity, .item-price, #tax_amount, #discount_amount', function() {
                const row = $(this).data('row');
                if (row) updateRowTotal(row);
                calculateTotals();
            });

            // Form is standard POST to GRN update route; no AJAX override needed

            function updateRowTotal(row) {
                const qty = parseFloat($(`input[name="items[${row}][quantity]"]`).val()) || 0;
                const price = parseFloat($(`input[name="items[${row}][cost_price]"]`).val()) || 0;
                const vatType = $(`input[name="items[${row}][vat_type]"]`).val();
                const vatRate = parseFloat($(`input[name="items[${row}][vat_rate]"]`).val()) || 0;
                const base = qty * price;
                let totalAmount = base;
                let vatAmt = 0;
                if (vatType === 'vat_inclusive' && vatRate > 0) {
                    const f = vatRate / 100;
                    vatAmt = totalAmount * f / (1 + f);
                } else if (vatType === 'vat_exclusive' && vatRate > 0) {
                    vatAmt = totalAmount * (vatRate / 100);
                    totalAmount += vatAmt;
                }
                $(`input[name="items[${row}][vat_amount]"]`).val(vatAmt.toFixed(2));
                $(`input[name="items[${row}][total_amount]"]`).val(totalAmount.toFixed(2));
                $(`tr[data-row-id="${row}"] .item-total`).text(`${totalAmount.toFixed(2)}`);
            }

            function calculateTotals() {
                let subtotal = 0;
                let totalVatFromItems = 0;
                $('.item-total').each(function() {
                    const rowId = $(this).closest('tr').data('row-id');
                    const itemVatType = $(`input[name="items[${rowId}][vat_type]"]`).val();
                    const itemVatRate = parseFloat($(`input[name="items[${rowId}][vat_rate]"]`).val()) || 0;
                    const totalAmount = parseFloat($(this).text()) || 0;
                    if (itemVatType === 'vat_inclusive' && itemVatRate > 0) {
                        const vatFactor = itemVatRate / 100;
                        const vatAmount = totalAmount * vatFactor / (1 + vatFactor);
                        totalVatFromItems += vatAmount;
                        subtotal += totalAmount - vatAmount;
                    } else if (itemVatType === 'vat_exclusive' && itemVatRate > 0) {
                        const netAmount = totalAmount / (1 + itemVatRate / 100);
                        const vatAmount = totalAmount - netAmount;
                        totalVatFromItems += vatAmount;
                        subtotal += netAmount;
                    } else {
                        subtotal += totalAmount;
                    }
                });
                const tax = parseFloat($('#tax_amount').val()) || 0;
                const discount = parseFloat($('#discount_amount').val()) || 0;
                // Prevent negative subtotal
                subtotal = Math.max(0, subtotal);
                const total = Math.max(0, subtotal + tax + totalVatFromItems - discount);
                $('#subtotal').text(`${subtotal.toFixed(2)}`);
                $('#subtotal-input').val(subtotal.toFixed(2));
                $('#vat-amount').text(`${totalVatFromItems.toFixed(2)}`);
                $('#vat-amount-input').val(totalVatFromItems.toFixed(2));
                $('#vat-rate-display').text('Mixed');
                $('#total-amount').text(`${total.toFixed(2)}`);
                $('#total-amount-input').val(total.toFixed(2));
            }

            // Default VAT config from system settings
            const DEFAULT_VAT_TYPE = (function(){
                const t = '{{ get_default_vat_type() }}';
                if (t === 'inclusive') return 'vat_inclusive';
                if (t === 'exclusive') return 'vat_exclusive';
                return 'no_vat';
            })();
            const DEFAULT_VAT_RATE = {{ (float) (get_default_vat_rate() ?? 0) }};

            function resetItemModal() {
                $('#modal_item_id').val('').trigger('change');
                $('#modal_quantity').val(1);
                $('#modal_unit_price').val(0);
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
                if (vatType === 'vat_exclusive' && vatRate > 0) {
                    lineTotal += lineTotal * (vatRate / 100);
                }
                $('#modal_item_total_preview').text(lineTotal.toFixed(2));
            }

            // initial totals
            calculateTotals();
        });

    </script>
    @endpush
