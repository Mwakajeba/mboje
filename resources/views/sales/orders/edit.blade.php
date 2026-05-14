@extends('layouts.main')

@section('title', 'Edit Sales Order')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Orders', 'url' => route('sales.orders.index'), 'icon' => 'bx bx-shopping-cart'],
			['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
		]" />

		<div class="card">
			<div class="card-header bg-primary text-white">
				<h5 class="mb-0"><i class="bx bx-shopping-cart me-2"></i>Edit Sales Order: {{ $order->order_number }}</h5>
			</div>
			<div class="card-body">
				<form id="order-form" action="{{ route('sales.orders.update', \Vinkla\Hashids\Facades\Hashids::encode($order->id)) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row">
						<!-- Customer Information -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
								<select class="form-select select2-single" id="customer_id" name="customer_id" required>
                                            <option value="">Select Customer</option>
                                            @foreach($customers as $customer)
										<option value="{{ $customer->id }}" {{ $order->customer_id == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
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

                    <!-- Payment Information -->
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
											<th width="20%">Item</th>
											<th width="10%">Quantity</th>
											<th width="15%">Unit Price</th>
											<th width="15%">VAT Type</th>
											<th width="10%">VAT Rate</th>
											<th width="10%">VAT Amount</th>
											<th width="15%">Total</th>
											<th width="10%">Action</th>
										</tr>
									</thead>
									<tbody id="items-tbody">
										@php($row = 0)
										@foreach($order->items as $pi)
											<tr data-item-id="{{ $pi->inventory_item_id }}" data-row-id="{{ $row }}" data-row="{{ $row }}">
												<td>
													<strong>{{ $pi->item_name }}</strong><br>
													<small class="text-muted">{{ $pi->item_code }}</small>
													<input type="hidden" name="items[{{ $row }}][inventory_item_id]" value="{{ $pi->inventory_item_id }}">
													<input type="hidden" name="items[{{ $row }}][vat_type]" value="{{ $pi->vat_type }}">
													<input type="hidden" name="items[{{ $row }}][vat_rate]" value="{{ $pi->vat_rate }}">
													<input type="hidden" name="items[{{ $row }}][vat_amount]" value="{{ $pi->vat_amount }}">
													<input type="hidden" name="items[{{ $row }}][discount_amount]" value="{{ $pi->discount_amount ?? 0 }}">
													<input type="hidden" name="items[{{ $row }}][discount_rate]" value="{{ $pi->discount_rate ?? 0 }}">
													<input type="hidden" name="items[{{ $row }}][line_total]" value="{{ number_format($pi->line_total, 2, '.', '') }}">
                                                </td>
                                                <td>
													<input type="number" class="form-control item-quantity" name="items[{{ $row }}][quantity]" value="{{ number_format($pi->quantity, 2, '.', '') }}" step="0.01" min="0.01" data-row="{{ $row }}">
                                                </td>
                                                <td>
													<input type="number" class="form-control item-price" name="items[{{ $row }}][unit_price]" value="{{ number_format($pi->unit_price, 2, '.', '') }}" step="0.01" min="0" data-row="{{ $row }}">
                                                </td>
                                                <td>
													<select class="form-select item-vat-type" name="items[{{ $row }}][vat_type]" data-row="{{ $row }}">
														<option value="no_vat" {{ $pi->vat_type == 'no_vat' ? 'selected' : '' }}>No VAT</option>
														<option value="vat_inclusive" {{ $pi->vat_type == 'vat_inclusive' ? 'selected' : '' }}>Inclusive</option>
														<option value="vat_exclusive" {{ $pi->vat_type == 'vat_exclusive' ? 'selected' : '' }}>Exclusive</option>
													</select>
                                                </td>
                                                <td>
													<input type="number" class="form-control item-vat-rate" name="items[{{ $row }}][vat_rate]" value="{{ $pi->vat_rate }}" step="0.01" min="0" data-row="{{ $row }}">
                                                </td>
                                                <td>
													<span class="vat-amount">{{ number_format($pi->vat_amount, 2) }}</span>
												</td>
                                                <td>
													<span class="item-total">{{ number_format($pi->line_total, 2, '.', '') }}</span>
                                                </td>
                                                <td>
													<button type="button" class="btn btn-outline-danger btn-sm remove-item"><i class="bx bx-trash"></i></button>
                                                </td>
                                            </tr>
										@php($row++)
                                        @endforeach
                                    </tbody>
									<tfoot>
										<tr>
											<td colspan="6" class="text-end"><strong>Subtotal:</strong></td>
											<td><strong id="subtotal">0.00</strong></td>
											<td></td>
										</tr>
										<tr id="vat-row" style="display: none;">
											<td colspan="6" class="text-end"><strong>VAT (<span id="vat-rate-display">0</span>%):</strong></td>
											<td><strong id="vat-amount">0.00</strong></td>
											<td></td>
										</tr>
										<tr>
											<td colspan="6" class="text-end"><strong>Additional Tax:</strong></td>
											<td>
												<input type="number" class="form-control" id="tax_amount" name="tax_amount" value="{{ number_format($order->tax_amount ?? 0, 2, '.', '') }}" step="0.01" min="0">
											</td>
											<td></td>
										</tr>
										<tr>
											<td colspan="6" class="text-end"><strong>Discount:</strong></td>
											<td>
												<input type="number" class="form-control" id="discount_amount" name="discount_amount" value="{{ number_format($order->discount_amount, 2, '.', '') }}" step="0.01" min="0">
											</td>
											<td></td>
										</tr>
										<tr class="table-info">
											<td colspan="6" class="text-end"><strong>Total Amount:</strong></td>
											<td><strong id="total-amount">0.00</strong></td>
											<td></td>
										</tr>
									</tfoot>
                                </table>
								<input type="hidden" name="subtotal" id="subtotal-input" value="0.00">
								<input type="hidden" name="vat_amount" id="vat-amount-input" value="0.00">
								<input type="hidden" name="tax_amount" id="tax-amount-input" value="0.00">
								<input type="hidden" name="discount_amount" id="discount-amount-input" value="0.00">
								<input type="hidden" name="total_amount" id="total-amount-input" value="0.00">
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
						<a href="{{ route('sales.proformas.index') }}" class="btn btn-outline-secondary">
							<i class="bx bx-x me-1"></i>Cancel
						</a>
						<button type="submit" class="btn btn-primary" id="submit-btn">
							<i class="bx bx-check me-1" data-processing-text="Updating..."></i>Update Proforma
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
							<option value="{{ $item->id }}" data-name="{{ $item->name }}" data-code="{{ $item->code }}" data-price="{{ $item->resolved_unit_price ?? $item->unit_price }}" data-stock="{{ $item->current_stock }}">
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
							<label for="modal_unit_price" class="form-label">Unit Price</label>
							<input type="number" class="form-control" id="modal_unit_price" step="0.01" min="0" value="0">
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
                            <div class="mb-3">
							<label for="modal_item_vat_type" class="form-label">Item VAT Type</label>
							<select class="form-select" id="modal_item_vat_type">
								<option value="no_vat" {{ get_default_vat_type() == 'no_vat' ? 'selected' : '' }}>No VAT</option>
								<option value="vat_inclusive" {{ get_default_vat_type() == 'vat_inclusive' ? 'selected' : '' }}>VAT Inclusive</option>
								<option value="vat_exclusive" {{ get_default_vat_type() == 'exclusive' ? 'selected' : '' }}>VAT Exclusive</option>
							</select>
						</div>
                            </div>
					<div class="col-md-6">
                            <div class="mb-3">
							<label for="modal_item_vat_rate" class="form-label">Item VAT Rate (%)</label>
							<input type="number" class="form-control" id="modal_item_vat_rate" value="{{ get_default_vat_rate() }}" step="0.01" min="0" max="100">
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
					<div class="col-md-6">
						<div class="mb-3">
							<label class="form-label">Discount Amount</label>
							<div class="form-control-plaintext border rounded p-2 bg-light">
								<strong id="modal_discount_preview">0.00</strong>
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

	// Open modal
	$('#add-item').click(function() { $('#itemModal').modal('show'); resetItemModal(); });

	// Modal interactions
	$('#modal_item_id').change(function() {
		const price = $(this).find(':selected').data('price');
		$('#modal_unit_price').val(price || 0);
		calculateModalItemTotal();
	});
	$('#modal_quantity, #modal_unit_price, #modal_discount_value, #modal_item_vat_rate').on('input', calculateModalItemTotal);
	$('#modal_item_vat_type, #modal_discount_type').on('change', function() { updateDiscountInputLabel(); calculateModalItemTotal(); });

	// Add item to table
	$('#add-item-to-table').click(function() {
		const itemId = $('#modal_item_id').val();
		const itemName = $('#modal_item_id option:selected').data('name');
		const itemCode = $('#modal_item_id option:selected').data('code');
		const quantity = parseFloat($('#modal_quantity').val()) || 0;
		const unitPrice = parseFloat($('#modal_unit_price').val()) || 0;
		const discountType = $('#modal_discount_type').val();
		const discountValue = parseFloat($('#modal_discount_value').val()) || 0;
		const itemVatType = $('#modal_item_vat_type').val();
		const itemVatRate = parseFloat($('#modal_item_vat_rate').val()) || 0;

		if (!itemId || quantity <= 0) { Swal.fire('Error', 'Please select an item and enter a valid quantity', 'error'); return; }
		if ($(`tr[data-item-id="${itemId}"]`).length > 0) { Swal.fire('Error', 'This item is already added. Please edit the existing entry.', 'error'); return; }

		const baseAmount = quantity * unitPrice;
		let discountAmount = 0; let discountPercentage = 0;
		if (discountType === 'percent') { discountPercentage = discountValue; discountAmount = baseAmount * (discountValue / 100); }
		else { discountAmount = discountValue; discountPercentage = baseAmount > 0 ? (discountValue / baseAmount) * 100 : 0; }
		// Clamp discount to base amount to avoid negative line totals
		discountAmount = Math.min(discountAmount, baseAmount);

		let lineTotal = baseAmount - discountAmount; let itemVatAmount = 0;
		if (itemVatType === 'vat_inclusive' && itemVatRate > 0) { const f = itemVatRate/100; itemVatAmount = lineTotal * f / (1 + f); }
		else if (itemVatType === 'vat_exclusive' && itemVatRate > 0) { itemVatAmount = lineTotal * (itemVatRate/100); lineTotal += itemVatAmount; }

		itemCounter++;
		const row = `
			<tr data-item-id="${itemId}" data-row-id="${itemCounter}">
				<td>
					<strong>${itemName}</strong><br>
					<small class="text-muted">${itemCode}</small>
					<input type="hidden" name="items[${itemCounter}][inventory_item_id]" value="${itemId}">
					<input type="hidden" name="items[${itemCounter}][vat_type]" value="${itemVatType}">
					<input type="hidden" name="items[${itemCounter}][vat_rate]" value="${itemVatRate}">
					<input type="hidden" name="items[${itemCounter}][vat_amount]" value="${itemVatAmount}">
					<input type="hidden" name="items[${itemCounter}][line_total]" value="${lineTotal}">
				</td>
				<td><input type="number" class="form-control item-quantity" name="items[${itemCounter}][quantity]" value="${quantity}" step="0.01" min="0.01" data-row="${itemCounter}"></td>
				<td><input type="number" class="form-control item-price" name="items[${itemCounter}][unit_price]" value="${unitPrice}" step="0.01" min="0" data-row="${itemCounter}"></td>
				<td>
					<select class="form-select item-vat-type" name="items[${itemCounter}][vat_type]" data-row="${itemCounter}">
						<option value="no_vat" ${itemVatType === 'no_vat' ? 'selected' : ''}>No VAT</option>
						<option value="vat_inclusive" ${itemVatType === 'vat_inclusive' ? 'selected' : ''}>Inclusive</option>
						<option value="vat_exclusive" ${itemVatType === 'vat_exclusive' ? 'selected' : ''}>Exclusive</option>
					</select>
				</td>
				<td><input type="number" class="form-control item-vat-rate" name="items[${itemCounter}][vat_rate]" value="${itemVatRate}" step="0.01" min="0" data-row="${itemCounter}"></td>
				<td><span class="vat-amount">${itemVatAmount.toFixed(2)}</span></td>
				<td><span class="item-total">${lineTotal.toFixed(2)}</span></td>
				<td><button type="button" class="btn btn-outline-danger btn-sm remove-item"><i class="bx bx-trash"></i></button></td>
			</tr>`;
		$('#items-tbody').append(row);
		$('#itemModal').modal('hide');
		calculateTotals();
	});

	// Row events
	$(document).on('click', '.remove-item', function() { $(this).closest('tr').remove(); calculateTotals(); });
	$(document).on('input', '.item-quantity, .item-price, .item-discount, #tax_amount, #discount_amount', function() {
		const row = $(this).data('row'); if (row) updateRowTotal(row); calculateTotals();
	});

	// Submit
	$('#proforma-form').submit(function(e) {
		e.preventDefault();
		if ($('#items-tbody tr').length === 0) { Swal.fire('Error', 'Please add at least one item to the proforma', 'error'); return; }
		const formData = new FormData(this);
		const submitBtn = $('#submit-btn');
		submitBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i>Updating...');
		$.ajax({
			url: '{{ route("sales.orders.update", \Vinkla\Hashids\Facades\Hashids::encode($order->id)) }}',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response) {
				if (response.success) {
					Swal.fire({ title: 'Updated!', text: response.message, icon: 'success', confirmButtonText: 'OK' }).then(()=>{ window.location.href = response.redirect_url; });
				} else {
					Swal.fire('Error', response.message, 'error');
				}
			},
			error: function(xhr) {
				if (xhr.status === 422) { Swal.fire('Validation Error', 'Please check the form for errors', 'error'); }
				else { Swal.fire('Error', 'Something went wrong. Please try again.', 'error'); }
			},
			complete: function() { submitBtn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Update Proforma'); }
		});
	});

	function updateRowTotal(row) {
		console.log(`updateRowTotal called for row ${row}`);
		const qty = parseFloat($(`input[name="items[${row}][quantity]"]`).val()) || 0;
		const price = parseFloat($(`input[name="items[${row}][unit_price]"]`).val()) || 0;
		const discAmt = parseFloat($(`input[name="items[${row}][discount_amount]"]`).val()) || 0;
		const vatType = $(`input[name="items[${row}][vat_type]"]`).val();
		const vatRate = parseFloat($(`input[name="items[${row}][vat_rate]"]`).val()) || 0;
		
		console.log(`Row ${row}: qty=${qty}, price=${price}, discAmt=${discAmt}, vatType=${vatType}, vatRate=${vatRate}`);
		
		const base = qty * price;
		// Clamp discount to base to avoid negative numbers
		const finalDiscAmt = Math.min(discAmt, base);
		let lineTotal = base - finalDiscAmt; 
		let vatAmt = 0;
		
		if (vatType === 'vat_inclusive' && vatRate > 0) { 
			const f = vatRate/100; 
			vatAmt = lineTotal * f / (1+f); 
		}
		else if (vatType === 'vat_exclusive' && vatRate > 0) { 
			vatAmt = lineTotal * (vatRate/100); 
			lineTotal += vatAmt; 
		}
		
		const discRate = base > 0 ? (finalDiscAmt/base*100) : 0;
		
		console.log(`Row ${row} calculated: base=${base}, lineTotal=${lineTotal}, vatAmt=${vatAmt}`);
		
		$(`input[name="items[${row}][discount_rate]"]`).val(discRate.toFixed(2));
		$(`input[name="items[${row}][vat_amount]"]`).val(vatAmt.toFixed(2));
		$(`input[name="items[${row}][line_total]"]`).val(lineTotal.toFixed(2));
		$(`tr[data-row-id="${row}"] .item-total`).text(`${lineTotal.toFixed(2)}`);
		$(`tr[data-row-id="${row}"] .vat-amount`).text(`${vatAmt.toFixed(2)}`);
	}

function calculateTotals() {
		console.log('calculateTotals() called'); // Debug log
		let subtotal = 0; let totalVatFromItems = 0;
		
		// Calculate from existing items
		$('.item-total').each(function() {
			const rowId = $(this).closest('tr').data('row-id');
			const itemVatType = $(`input[name="items[${rowId}][vat_type]"]`).val();
			const itemVatRate = parseFloat($(`input[name="items[${rowId}][vat_rate]"]`).val()) || 0;
			const lineTotal = parseFloat($(this).text()) || 0;
			
			console.log(`Row ${rowId}: vatType=${itemVatType}, vatRate=${itemVatRate}, lineTotal=${lineTotal}`); // Debug log
			
			let rowVatAmount = 0;
			let rowNetAmount = 0;
			
			if (itemVatType === 'vat_inclusive' && itemVatRate > 0) {
				// VAT inclusive: extract VAT to get net amount
				rowVatAmount = lineTotal * (itemVatRate / (100 + itemVatRate));
				rowNetAmount = lineTotal - rowVatAmount;
			} else if (itemVatType === 'vat_exclusive' && itemVatRate > 0) {
				// VAT exclusive: line total includes VAT, extract net amount
				rowNetAmount = lineTotal / (1 + itemVatRate / 100);
				rowVatAmount = lineTotal - rowNetAmount;
			} else {
				// No VAT
				rowVatAmount = 0;
				rowNetAmount = lineTotal;
			}
			
			subtotal += rowNetAmount;
			totalVatFromItems += rowVatAmount;
		});
		
		const tax = parseFloat($('#tax_amount').val()) || 0;
		const discount = parseFloat($('#discount_amount').val()) || 0;
		
		// Prevent negative subtotal
		subtotal = Math.max(0, subtotal);
		const total = Math.max(0, subtotal + tax + totalVatFromItems - discount);
		
		console.log(`Calculated: subtotal=${subtotal}, vat=${totalVatFromItems}, tax=${tax}, discount=${discount}, total=${total}`); // Debug log
		
		// Update display elements
		$('#subtotal').text(`${subtotal.toFixed(2)}`);
		$('#vat-amount').text(`${totalVatFromItems.toFixed(2)}`);
		$('#vat-rate-display').text('Mixed');
		$('#total-amount').text(`${total.toFixed(2)}`);
		
		// Update hidden inputs
		$('#subtotal-input').val(subtotal.toFixed(2));
		$('#vat-amount-input').val(totalVatFromItems.toFixed(2));
		$('#tax-amount-input').val(tax.toFixed(2));
		$('#discount-amount-input').val(discount.toFixed(2));
		$('#total-amount-input').val(total.toFixed(2));
		
		console.log(`Hidden inputs set: subtotal=${$('#subtotal-input').val()}, vat=${$('#vat-amount-input').val()}, total=${$('#total-amount-input').val()}`); // Debug log
	}

	function resetItemModal() {
		$('#modal_item_id').val('').trigger('change');
		$('#modal_quantity').val(1);
		$('#modal_unit_price').val(0);
		$('#modal_item_vat_type').val('{{ get_default_vat_type() }}');
		$('#modal_item_vat_rate').val('{{ get_default_vat_rate() }}');
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

	// Form submit handler
	$('#order-form').on('submit', function(e) {
		e.preventDefault();
		
		console.log('Form submit triggered'); // Debug log
		
		// Ensure totals are calculated before submission
		calculateTotals();
		
		// Force set all required fields with fallback values
		$('#subtotal-input').val($('#subtotal-input').val() || '0.00');
		$('#vat-amount-input').val($('#vat-amount-input').val() || '0.00');
		$('#tax-amount-input').val($('#tax-amount-input').val() || '0.00');
		$('#discount-amount-input').val($('#discount-amount-input').val() || '0.00');
		$('#total-amount-input').val($('#total-amount-input').val() || '0.00');
		
		console.log('Final check - subtotal:', $('#subtotal-input').val());
		console.log('Final check - vat_amount:', $('#vat-amount-input').val());
		console.log('Final check - tax_amount:', $('#tax-amount-input').val());
		console.log('Final check - discount_amount:', $('#discount-amount-input').val());
		console.log('Final check - total_amount:', $('#total-amount-input').val());
		
		// Get form data
		const formData = new FormData(this);
		
		// Debug: Log form data
		console.log('Form data:');
		for (let [key, value] of formData.entries()) {
			console.log(`${key}: ${value}`);
		}
		
		// Show loading
		$('#submit-btn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-2"></i>Updating...');
		
		// Submit via AJAX
		$.ajax({
			url: $(this).attr('action'),
			method: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response) {
				if (response.success) {
					Swal.fire('Success', response.message, 'success').then(() => {
						window.location.href = response.redirect_url;
					});
				} else {
					Swal.fire('Error', response.message, 'error');
				}
			},
			error: function(xhr) {
				const response = xhr.responseJSON;
				console.log('AJAX Error:', response);
				Swal.fire('Error', response?.message || 'An error occurred', 'error');
			},
			complete: function() {
				$('#submit-btn').prop('disabled', false).html('<i class="bx bx-save me-2"></i>Update Order');
			}
		});
	});

	// Initial totals calculation
	// First, calculate all existing items
	$('.item-total').each(function() {
		const rowId = $(this).closest('tr').data('row-id');
		if (rowId !== undefined) {
			updateRowTotal(rowId);
		}
	});
	
	// Then calculate totals
	calculateTotals();
	
	// Backup: Ensure all required fields are always set on page load
	setTimeout(function() {
		console.log('Backup: Checking all required fields');
		$('#subtotal-input').val($('#subtotal-input').val() || '0.00');
		$('#vat-amount-input').val($('#vat-amount-input').val() || '0.00');
		$('#tax-amount-input').val($('#tax-amount-input').val() || '0.00');
		$('#discount-amount-input').val($('#discount-amount-input').val() || '0.00');
		$('#total-amount-input').val($('#total-amount-input').val() || '0.00');
		console.log('Backup: All fields set');
	}, 1000);
	
	// Recalculate on any input change
	$(document).on('input change', '.item-quantity, .item-price, .item-vat-type, .item-vat-rate, #tax_amount, #discount_amount', function() {
		const rowId = $(this).closest('tr').data('row-id');
		if (rowId !== undefined) {
			updateRowTotal(rowId);
		}
		setTimeout(calculateTotals, 100);
	});
});
</script>
@endpush