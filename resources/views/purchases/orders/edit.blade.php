@extends('layouts.main')

@section('title', 'Edit Purchase Order')

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
            ['label' => 'Purchases', 'url' => route('purchases.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Orders', 'url' => route('purchases.orders.index'), 'icon' => 'bx bx-shopping-cart'],
			['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
		]" />

		<div class="card">
			<div class="card-header bg-primary text-white">
				<h5 class="mb-0"><i class="bx bx-shopping-cart me-2"></i>Edit Purchase Order: {{ $order->order_number }}</h5>
			</div>
			<div class="card-body">
				<form id="order-form" action="{{ route('purchases.orders.update', $order->encoded_id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row">
						<!-- Supplier Information -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="supplier_id" class="form-label">Supplier <span class="text-danger">*</span></label>
								<select class="form-select select2-single" id="supplier_id" name="supplier_id" required>
                                            <option value="">Select Supplier</option>
                                            @foreach($suppliers as $supplier)
										<option value="{{ $supplier->id }}" {{ $order->supplier_id == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                                            @endforeach
                                        </select>
								<div class="invalid-feedback"></div>
                                    </div>
                                </div>

						<div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="order_date" class="form-label">Order Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="order_date" name="order_date" value="{{ $order->order_date->format('Y-m-d') }}" required>
								<div class="invalid-feedback"></div>
                                    </div>
                                </div>

						<div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="expected_delivery_date" class="form-label">Expected Delivery Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="expected_delivery_date" name="expected_delivery_date" value="{{ $order->expected_delivery_date->format('Y-m-d') }}" required>
								<div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden order-level fields to satisfy validation -->
                    <input type="hidden" name="vat_type" value="{{ $order->vat_type ?? 'no_vat' }}">
                    <input type="hidden" name="vat_rate" value="{{ number_format($order->vat_rate ?? 0, 2, '.', '') }}">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="payment_terms" class="form-label">Payment Terms</label>
                                <select class="form-select" id="payment_terms" name="payment_terms">
                                    <option value="immediate" {{ $order->payment_terms == 'immediate' ? 'selected' : '' }}>Immediate Payment</option>
                                    <option value="net_15" {{ $order->payment_terms == 'net_15' ? 'selected' : '' }}>Net 15 Days</option>
                                    <option value="net_30" {{ $order->payment_terms == 'net_30' ? 'selected' : '' }}>Net 30 Days</option>
                                    <option value="net_45" {{ $order->payment_terms == 'net_45' ? 'selected' : '' }}>Net 45 Days</option>
                                    <option value="net_60" {{ $order->payment_terms == 'net_60' ? 'selected' : '' }}>Net 60 Days</option>
                                    <option value="custom" {{ $order->payment_terms == 'custom' ? 'selected' : '' }}>Custom</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="payment_days" class="form-label">Payment Days</label>
                                <input type="number" class="form-control" id="payment_days" name="payment_days" 
                                       value="{{ $order->payment_days }}" placeholder="Number of days for payment" min="0">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Hide Cost Price Toggle -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="hide_cost_price" name="hide_cost_price" value="1" {{ $order->hide_cost_price ? 'checked' : '' }}>
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
										@foreach($order->items as $index => $item)
                                            @php
                                                $isHidden = $order->hide_cost_price;
                                                $hiddenClass = $isHidden ? 'hidden' : '';
                                            @endphp
											<tr data-item-id="{{ $item->item_id }}" data-row-id="{{ $index }}">
												<td>
													<strong>{{ $item->item->name }}</strong><br>
													<small class="text-muted">{{ $item->item->code }}</small>
													<input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $item->item_id }}">
                                                    <input type="hidden" name="items[{{ $index }}][vat_type]" value="{{ $item->vat_type === 'inclusive' ? 'vat_inclusive' : ($item->vat_type === 'exclusive' ? 'vat_exclusive' : 'no_vat') }}">
													<input type="hidden" name="items[{{ $index }}][vat_rate]" value="{{ $item->vat_rate }}">
													<input type="hidden" name="items[{{ $index }}][vat_amount]" value="{{ $item->vat_amount }}">
													<input type="hidden" name="items[{{ $index }}][total_amount]" value="{{ number_format($item->total_amount, 2, '.', '') }}">
                                                </td>
                                                <td>
												<input type="number" class="form-control item-quantity" name="items[{{ $index }}][quantity]" value="{{ number_format($item->quantity, 2, '.', '') }}" step="0.01" min="0.01" data-row="{{ $index }}">
                                                </td>
                                                <td class="cost-price-col {{ $hiddenClass }}">
												<input type="number" class="form-control item-price" name="items[{{ $index }}][cost_price]" value="{{ number_format($item->cost_price, 2, '.', '') }}" step="0.01" min="0" data-row="{{ $index }}">
                                                </td>
                                            <td class="vat-col {{ $hiddenClass }}">
                                                <span class="form-control-plaintext">{{ $item->vat_type == 'no_vat' ? 'No VAT' : $item->vat_rate . '% (' . str_replace('_', ' ', $item->vat_type) . ')' }}</span>
                                            </td>
                                            <td class="vat-amount-col {{ $hiddenClass }}">
                                                <span class="item-vat-amount">{{ number_format($item->vat_amount, 2, '.', '') }}</span>
                                            </td>
                                            <td class="line-total-col {{ $hiddenClass }}">
                                                <span class="item-total">{{ number_format($item->total_amount, 2, '.', '') }}</span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-outline-danger btn-sm remove-item"><i class="bx bx-trash"></i></button>
                                            </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
									<tfoot>
                                        <tr class="cost-summary-row">
                                            <td colspan="5" class="text-end"><strong>Subtotal:</strong></td>
                                            <td><strong id="subtotal">0.00</strong></td>
                                            <td></td>
                                        </tr>
                                        <tr id="vat-row" class="cost-summary-row" style="display: none;">
                                            <td colspan="5" class="text-end"><strong>VAT Total:</strong></td>
                                            <td><strong id="vat-amount">0.00</strong></td>
                                            <td></td>
                                        </tr>
                                        <tr class="cost-summary-row">
                                            <td colspan="5" class="text-end"><strong>Additional Tax:</strong></td>
                                            <td>
                                                <input type="number" class="form-control" id="tax_amount" name="tax_amount" value="{{ number_format($order->tax_amount ?? 0, 2, '.', '') }}" step="0.01" min="0">
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr class="cost-summary-row">
                                            <td colspan="2" class="text-end"><strong>Discount:</strong></td>
                                            <td colspan="2">
                                                <div class="row g-2">
                                                    <div class="col-md-5">
                                                        <select class="form-select" id="discount_type" name="discount_type">
                                                            <option value="percentage" {{ $order->discount_type === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                                            <option value="fixed" {{ $order->discount_type === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-7">
                                                        <div id="discount_rate_wrapper" style="display: {{ $order->discount_type === 'percentage' ? 'block' : 'none' }};">
                                                            <input type="number" class="form-control" id="discount_rate" name="discount_rate" value="{{ number_format($order->discount_rate ?? 0, 2, '.', '') }}" step="0.01" min="0" max="100" placeholder="Rate %">
                                                        </div>
                                                        <div id="discount_amount_wrapper" style="display: {{ $order->discount_type === 'fixed' ? 'block' : 'none' }};">
                                                            <input type="number" class="form-control" id="discount_amount" name="discount_amount" value="{{ number_format($order->discount_amount ?? 0, 2, '.', '') }}" step="0.01" min="0" placeholder="Amount">
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr class="table-info cost-summary-row">
                                            <td colspan="5" class="text-end"><strong>Total Amount:</strong></td>
                                            <td><strong id="total-amount">0.00</strong></td>
                                            <td></td>
                                        </tr>
									</tfoot>
                                </table>
								<input type="hidden" name="subtotal" id="subtotal-input" value="0">
								<input type="hidden" name="vat_amount" id="vat-amount-input" value="0">
								<input type="hidden" name="total_amount" id="total-amount-input" value="0">
                            </div>
                        </div>
                    </div>

                    					<!-- Notes, Terms and Attachment -->
					<div class="row mt-4">
						<div class="col-md-4">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
								<textarea class="form-control" id="notes" name="notes" rows="4">{{ $order->notes }}</textarea>
							</div>
                            </div>
						<div class="col-md-4">
                            <div class="mb-3">
                                <label for="terms_conditions" class="form-label">Terms & Conditions</label>
								<textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="4">{{ $order->terms_conditions }}</textarea>
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
                                @if(!empty($order->attachment))
                                    <div class="mt-2">
                                        <a href="{{ asset('storage/' . $order->attachment) }}" target="_blank">
                                            <i class="bx bx-link-external me-1"></i>View current attachment
                                        </a>
                                    </div>
                                @endif
                                <small class="text-muted">Upload a new file to replace the existing attachment (PDF or image, max 5MB).</small>
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
					<div class="col-md-6 price-fields">
						<div class="mb-3">
							<label for="modal_unit_price" class="form-label">Cost Price</label>
							<input type="number" class="form-control" id="modal_unit_price" step="0.01" min="0" value="0">
						</div>
					</div>
				</div>

				<div class="row price-fields">
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
	let itemCounter = {{ count($order->items) }};

	// Select2
	$('.select2-single').select2({ placeholder: 'Select', allowClear: true, width: '100%', theme: 'bootstrap-5' });
	$('.select2-modal').select2({ placeholder: 'Search for an item...', allowClear: true, width: '100%', theme: 'bootstrap-5', dropdownParent: $('#itemModal') });

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
    // Initial state
    $('#hide_cost_price').trigger('change');

	// Open modal
	$('#add-item').click(function() { $('#itemModal').modal('show'); resetItemModal(); });

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

		if (!itemId || quantity <= 0) { Swal.fire('Error', 'Please select an item and enter a valid quantity', 'error'); return; }
		if ($(`tr[data-item-id="${itemId}"]`).length > 0) { Swal.fire('Error', 'This item is already added. Please edit the existing entry.', 'error'); return; }

		const baseAmount = quantity * unitPrice;
		let lineTotal = baseAmount; let itemVatAmount = 0;
		if (itemVatType === 'vat_inclusive' && itemVatRate > 0) { const f = itemVatRate/100; itemVatAmount = lineTotal * f / (1 + f); }
		else if (itemVatType === 'vat_exclusive' && itemVatRate > 0) { itemVatAmount = lineTotal * (itemVatRate/100); lineTotal += itemVatAmount; }

		const isHidden = $('#hide_cost_price').is(':checked');
		const hiddenClass = isHidden ? 'hidden' : '';

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
				<td class="cost-price-col ${hiddenClass}"><input type="number" class="form-control item-price" name="items[${itemCounter}][cost_price]" value="${unitPrice}" step="0.01" min="0" data-row="${itemCounter}"></td>
				<td class="vat-col ${hiddenClass}"><span class="form-control-plaintext">${itemVatType==='no_vat' ? 'No VAT' : (itemVatRate+'% '+itemVatType.replace('_',' '))}</span></td>
				<td class="vat-amount-col ${hiddenClass}"><span class="item-vat-amount">${itemVatAmount.toFixed(2)}</span></td>
				<td class="line-total-col ${hiddenClass}"><span class="item-total">${lineTotal.toFixed(2)}</span></td>
				<td><button type="button" class="btn btn-outline-danger btn-sm remove-item"><i class="bx bx-trash"></i></button></td>
			</tr>`;
		$('#items-tbody').append(row);
		$('#itemModal').modal('hide');
		calculateTotals();
	});

	// Row events
	$(document).on('click', '.remove-item', function() { $(this).closest('tr').remove(); calculateTotals(); });
    $(document).on('input', '.item-quantity, .item-price, #tax_amount, #discount_amount, #discount_rate', function() {
		const row = $(this).data('row'); if (row) updateRowTotal(row); calculateTotals();
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

	// Submit
	$('#order-form').submit(function(e) {
		e.preventDefault();
		if ($('#items-tbody tr').length === 0) { Swal.fire('Error', 'Please add at least one item to the order', 'error'); return; }
		const formData = new FormData(this);
		const submitBtn = $('#submit-btn');
		submitBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i>Updating...');
		$.ajax({
			url: '{{ route("purchases.orders.update", $order->encoded_id) }}',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response) {
				if (response.success) {
					Swal.fire({ title: 'Updated!', text: response.message, icon: 'success', confirmButtonText: 'OK' }).then(()=>{ window.location.href = response.redirect; });
				} else {
					Swal.fire('Error', response.message, 'error');
				}
			},
			error: function(xhr) {
				if (xhr.status === 422) { Swal.fire('Validation Error', 'Please check the form for errors', 'error'); }
				else { Swal.fire('Error', 'Something went wrong. Please try again.', 'error'); }
			},
			complete: function() { submitBtn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Update Order'); }
		});
	});

	function updateRowTotal(row) {
		const qty = parseFloat($(`input[name="items[${row}][quantity]"]`).val()) || 0;
		const price = parseFloat($(`input[name="items[${row}][cost_price]"]`).val()) || 0;
		const vatType = $(`input[name="items[${row}][vat_type]"]`).val();
		const vatRate = parseFloat($(`input[name="items[${row}][vat_rate]"]`).val()) || 0;
		const base = qty * price;
		let totalAmount = base; let vatAmt = 0;
		if (vatType === 'vat_inclusive' && vatRate > 0) { const f = vatRate/100; vatAmt = totalAmount * f / (1+f); }
		else if (vatType === 'vat_exclusive' && vatRate > 0) { vatAmt = totalAmount * (vatRate/100); totalAmount += vatAmt; }
		$(`input[name="items[${row}][vat_amount]"]`).val(vatAmt.toFixed(2));
		$(`input[name="items[${row}][total_amount]"]`).val(totalAmount.toFixed(2));
		$(`tr[data-row-id="${row}"] .item-total`).text(`${totalAmount.toFixed(2)}`);
		$(`tr[data-row-id="${row}"] .item-vat-amount`).text(`${vatAmt.toFixed(2)}`);
}

function calculateTotals() {
		let subtotal = 0; let totalVatFromItems = 0;
		$('.item-total').each(function() {
			const rowId = $(this).closest('tr').data('row-id');
			const itemVatType = $(`input[name="items[${rowId}][vat_type]"]`).val();
			const itemVatRate = parseFloat($(`input[name="items[${rowId}][vat_rate]"]`).val()) || 0;
			const totalAmount = parseFloat($(this).text()) || 0;
			if (itemVatType === 'vat_inclusive' && itemVatRate > 0) {
				const vatFactor = itemVatRate / 100; const vatAmount = totalAmount * vatFactor / (1 + vatFactor);
				totalVatFromItems += vatAmount; subtotal += totalAmount - vatAmount;
			} else if (itemVatType === 'vat_exclusive' && itemVatRate > 0) {
				const netAmount = totalAmount / (1 + itemVatRate / 100); const vatAmount = totalAmount - netAmount;
				totalVatFromItems += vatAmount; subtotal += netAmount;
			} else { subtotal += totalAmount; }
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
		// Prevent negative subtotal
		subtotal = Math.max(0, subtotal);
		const total = Math.max(0, subtotal + tax + totalVatFromItems - discount);
		$('#subtotal').text(`${subtotal.toFixed(2)}`);
		$('#subtotal-input').val(subtotal.toFixed(2));
		if (totalVatFromItems > 0) { $('#vat-row').show(); } else { $('#vat-row').hide(); }
		$('#vat-amount').text(`${totalVatFromItems.toFixed(2)}`);
		$('#vat-amount-input').val(totalVatFromItems.toFixed(2));
		$('#total-amount').text(`${total.toFixed(2)}`);
		$('#total-amount-input').val(total.toFixed(2));
		}

	function resetItemModal() {
		$('#modal_item_id').val('').trigger('change');
		$('#modal_quantity').val(1);
		$('#modal_unit_price').val(0);
		$('#modal_item_vat_type').val('no_vat');
		$('#modal_item_vat_rate').val(18);
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