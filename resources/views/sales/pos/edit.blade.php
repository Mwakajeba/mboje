@extends('layouts.main')

@section('title', 'Edit POS Sale')

@section('content')
<div class="page-wrapper">
	<div class="page-content">
		<x-breadcrumbs-with-icons :links="[
			['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
			['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-store'],
			['label' => 'Sales POS List', 'url' => route('sales.pos.list'), 'icon' => 'bx bx-receipt'],
			['label' => 'Edit POS Sale', 'url' => '#', 'icon' => 'bx bx-edit']
		]" />

		<div class="row">
			<div class="col-12">
				<div class="card">
					<div class="card-header d-flex justify-content-between align-items-center">
						<h5 class="mb-0"><i class="bx bx-edit me-2"></i>Edit POS Sale</h5>
						<div>
							<a href="{{ route('sales.pos.show', $posSale->encoded_id) }}" class="btn btn-secondary"><i class="bx bx-show me-1"></i>View</a>
							<a href="{{ route('sales.pos.list') }}" class="btn btn-outline-primary"><i class="bx bx-list-ul me-1"></i>Back to List</a>
						</div>
					</div>
					<div class="card-body">
						<form action="{{ route('sales.pos.update', $posSale->encoded_id) }}" method="POST" id="posEditForm">
							@csrf
							@method('PUT')

							<div class="row g-3">
								<div class="col-md-4">
									<div class="border rounded p-3 h-100">
										<h6 class="text-primary mb-3">Sale Information</h6>
										<div class="mb-3">
											<label class="form-label">POS Number</label>
											<input type="text" class="form-control" value="{{ $posSale->pos_number }}" disabled>
										</div>
										<div class="mb-3">
											<label class="form-label">Date & Time</label>
											<input type="datetime-local" name="sale_date" class="form-control" value="{{ optional($posSale->sale_date)->format('Y-m-d\TH:i') }}" required>
										</div>
										@php
											$currencies = \App\Models\Currency::where('company_id', auth()->user()->company_id)
												->where('is_active', true)
												->orderBy('currency_code')
												->get();
											
											// Fallback to API currencies if database is empty
											if ($currencies->isEmpty()) {
												$supportedCurrencies = app(\App\Services\ExchangeRateService::class)->getSupportedCurrencies();
												$currencies = collect($supportedCurrencies)->map(function($name, $code) {
													return (object)['currency_code' => $code, 'currency_name' => $name];
												});
											}
										@endphp
										<div class="mb-3">
											<label class="form-label">Currency</label>
											<select name="currency" class="form-select select2-single">
												@foreach($currencies as $currency)
													<option value="{{ $currency->currency_code }}" 
															{{ old('currency', $posSale->currency ?? 'TZS') == $currency->currency_code ? 'selected' : '' }}>
														{{ $currency->currency_name ?? $currency->currency_code }} ({{ $currency->currency_code }})
													</option>
												@endforeach
											</select>
										</div>
										<div class="mb-3">
											<label class="form-label">Exchange Rate</label>
											<div class="input-group">
												<input type="number" name="exchange_rate" id="exchange_rate" class="form-control" value="{{ old('exchange_rate', number_format($posSale->exchange_rate ?? 1, 6, '.', '')) }}" step="0.000001" min="0.000001">
												<button type="button" class="btn btn-outline-secondary" id="fetch-rate-btn">
													<i class="bx bx-refresh"></i>
												</button>
											</div>
											<small class="text-muted">Rate relative to functional currency</small>
											<div id="rate-info" class="mt-1" style="display: none;">
												<small class="text-info">
													<i class="bx bx-info-circle"></i>
													<span id="rate-source">Rate fetched from API</span>
												</small>
											</div>
										</div>
										<div class="mb-3">
											<label class="form-label">Customer</label>
											@if($posSale->customer_id)
												<input type="hidden" name="customer_id" value="{{ $posSale->customer_id }}">
												<input type="text" class="form-control" value="{{ optional($posSale->customer)->name }}" disabled>
											@else
												<input type="hidden" name="customer_id" value="">
												<input type="text" name="customer_name" class="form-control" value="{{ $posSale->customer_name ?? 'Walk-in Customer' }}" placeholder="Customer name (optional)">
											@endif
										</div>
										<div class="mb-3">
											<label class="form-label">Notes</label>
											<textarea name="notes" class="form-control" rows="3" placeholder="Notes...">{{ $posSale->notes }}</textarea>
										</div>
									</div>
								</div>
								<div class="col-md-8">
									<div class="border rounded p-3 h-100">
										<h6 class="text-primary mb-3">Items</h6>
										<div class="table-responsive">
											<table class="table table-bordered align-middle" id="itemsTable">
												<thead>
													<tr>
														<th style="width:35%">Item</th>
														<th class="text-end" style="width:12%">Qty</th>
														<th class="text-end" style="width:18%">Unit Price</th>
														<th class="text-end" style="width:12%">VAT %</th>
														<th style="width:15%">VAT Type</th>
														<th style="width:8%"></th>
													</tr>
												</thead>
												<tbody>
													@foreach($posSale->items as $index => $item)
													<tr>
														<td>
															<select name="items[{{ $index }}][item_id]" class="form-select item-select" required>
																<option value="">Select item</option>
																													@foreach($inventoryItems as $inv)
														@php
															$stockService = new \App\Services\InventoryStockService();
															$currentStock = 0;
															if ($inv->item_type !== 'service' && $inv->track_stock) {
																$currentStock = $stockService->getItemStockAtLocation($inv->id, session('location_id'));
															}
														@endphp
														<option value="{{ $inv->id }}" 
															data-price="{{ $inv->resolved_unit_price ?? $inv->unit_price }}" 
															data-vat-rate="{{ $inv->vat_rate ?? 18 }}" 
															data-vat-type="{{ $inv->vat_type ?? 'inclusive' }}"
															data-item-type="{{ $inv->item_type ?? 'product' }}"
															data-track-stock="{{ $inv->track_stock ? 'true' : 'false' }}"
															data-stock="{{ $currentStock }}"
															{{ $inv->id == $item->inventory_item_id ? 'selected' : '' }}>
															{{ $inv->name }} ({{ $inv->code }}) - TZS {{ number_format($inv->resolved_unit_price ?? $inv->unit_price ?? 0, 2) }}
														</option>
													@endforeach
															</select>
														</td>
														<td>
															<input type="number" name="items[{{ $index }}][quantity]" class="form-control text-end" step="0.01" min="0.01" value="{{ number_format($item->quantity, 2, '.', '') }}" required>
														</td>
														<td>
															<input type="number" name="items[{{ $index }}][unit_price]" class="form-control text-end" step="0.01" min="0" value="{{ number_format($item->unit_price, 2, '.', '') }}" required>
														</td>
														<td>
															<input type="number" name="items[{{ $index }}][vat_rate]" class="form-control text-end" step="0.01" min="0" value="{{ number_format($item->vat_rate ?? 18, 2, '.', '') }}">
														</td>
														<td>
															<select name="items[{{ $index }}][vat_type]" class="form-select">
																<option value="inclusive" {{ ($item->vat_type ?? 'inclusive') == 'inclusive' ? 'selected' : '' }}>Inclusive</option>
																<option value="exclusive" {{ ($item->vat_type ?? '') == 'exclusive' ? 'selected' : '' }}>Exclusive</option>
																<option value="no_vat" {{ ($item->vat_type ?? '') == 'no_vat' ? 'selected' : '' }}>No VAT</option>
															</select>
														</td>
														<td class="text-center">
															<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItemRow(this)"><i class="bx bx-trash"></i></button>
														</td>
													</tr>
													@endforeach
												</tbody>
											</table>
										</div>
										<div class="d-flex justify-content-between">
											<button type="button" class="btn btn-outline-secondary" onclick="addItemRow()"><i class="bx bx-plus me-1"></i>Add Item</button>
										</div>

										<hr>
										<h6 class="text-primary mb-3">Payment & Totals</h6>
										<div class="row g-3">
											<div class="col-md-6">
												<label class="form-label">Paid From (Bank Account)</label>
												<select name="bank_account_id" class="form-select" required>
													<option value="">Select bank account</option>
													@foreach($bankAccounts as $bank)
														<option value="{{ $bank->id }}" {{ $posSale->bank_account_id == $bank->id ? 'selected' : '' }}>{{ $bank->name }}</option>
													@endforeach
												</select>
											</div>
											<div class="col-md-6">
												<label class="form-label">Amount Paid (TZS) <span class="text-danger">*</span></label>
												<input type="number" name="bank_amount" id="bank_amount" class="form-control text-end" step="0.01" min="0" value="{{ number_format($posSale->bank_amount ?? 0, 2, '.', '') }}" required>
											</div>
											<div class="col-md-6">
												<label class="form-label">Discount Type</label>
												<select name="discount_type" id="discount_type" class="form-select" required>
													<option value="none" {{ ($posSale->discount_type ?? 'none') == 'none' ? 'selected' : '' }}>No Discount</option>
													<option value="percentage" {{ ($posSale->discount_type ?? '') == 'percentage' ? 'selected' : '' }}>Percentage</option>
													<option value="fixed" {{ ($posSale->discount_type ?? '') == 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
												</select>
											</div>
											<div class="col-md-6">
												<label class="form-label">Discount Rate</label>
												<input type="number" name="discount_rate" id="discount_rate" class="form-control text-end" step="0.01" min="0" value="{{ number_format($posSale->discount_rate ?? 0, 2, '.', '') }}" placeholder="Enter discount rate" style="{{ ($posSale->discount_type ?? 'none') == 'none' ? 'display: none;' : '' }}">
												<small class="text-muted" id="discount_hint"></small>
											</div>
										</div>

										<!-- Summary Section -->
										<div class="mt-4 border rounded p-3 bg-light">
											<h6 class="text-primary mb-3">Summary</h6>
											<div class="row">
												<div class="col-md-3">
													<div class="d-flex justify-content-between mb-2">
														<span>Subtotal:</span>
														<span id="subtotal">TZS 0.00</span>
													</div>
												</div>
												<div class="col-md-3">
													<div class="d-flex justify-content-between mb-2">
														<span>VAT:</span>
														<span id="vat-total">TZS 0.00</span>
													</div>
												</div>
												<div class="col-md-3">
													<div class="d-flex justify-content-between mb-2">
														<span>Discount:</span>
														<span id="discount-total">TZS 0.00</span>
													</div>
												</div>
												<div class="col-md-3">
													<div class="d-flex justify-content-between mb-2 fw-bold text-primary">
														<span>Total:</span>
														<span id="grand-total">TZS 0.00</span>
													</div>
												</div>
											</div>
										</div>

										<div class="mt-4 d-flex justify-content-end gap-2">
											<button type="submit" class="btn btn-primary"><i class="bx bx-save me-1" data-processing-text="Saving..."></i>Save Changes</button>
											<a href="{{ route('sales.pos.show', $posSale->encoded_id) }}" class="btn btn-light">Cancel</a>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
	// Original quantities by item_id from the existing sale
	// This is used to add back original quantities when validating stock availability
	// Declared outside document.ready so it's accessible to all functions
	const originalQuantities = @json($originalQuantities ?? []);
	
	$(document).ready(function() {
		// Get functional currency for exchange rate calculations
		const functionalCurrency = '{{ \App\Models\SystemSetting::getValue("functional_currency", auth()->user()->company->functional_currency ?? "TZS") }}';
		
		// Handle currency change - Use Select2 event for proper handling
		$('select[name="currency"]').on('select2:select', function(e) {
			const selectedCurrency = $(this).val();
			handleCurrencyChange(selectedCurrency);
		}).on('change', function() {
			const selectedCurrency = $(this).val();
			handleCurrencyChange(selectedCurrency);
		});
		
		function handleCurrencyChange(selectedCurrency) {
			if (selectedCurrency && selectedCurrency !== functionalCurrency) {
				$('#exchange_rate').prop('required', true);
				fetchExchangeRate(selectedCurrency);
			} else {
				$('#exchange_rate').prop('required', false);
				$('#exchange_rate').val('1.000000');
				$('#rate-info').hide();
			}
		}
		
		// Fetch exchange rate button
		$('#fetch-rate-btn').on('click', function() {
			const currency = $('select[name="currency"]').val();
			fetchExchangeRate(currency);
		});
		
		// Function to fetch exchange rate from API
		function fetchExchangeRate(currency = null) {
			currency = currency || $('select[name="currency"]').val();
			if (!currency || currency === functionalCurrency) {
				$('#exchange_rate').val('1.000000');
				return;
			}

			const btn = $('#fetch-rate-btn');
			const originalHtml = btn.html();
			const rateInput = $('#exchange_rate');
			
			btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i>');
			rateInput.prop('disabled', true);
			
			$.ajax({
				url: '{{ route("accounting.fx-rates.get-rate") }}',
				method: 'GET',
				data: {
					from_currency: currency,
					to_currency: functionalCurrency,
					date: new Date().toISOString().split('T')[0],
					rate_type: 'spot'
				},
				success: function(response) {
					if (response.success && response.rate) {
						const rate = parseFloat(response.rate);
						rateInput.val(rate.toFixed(6));
						$('#rate-source').text(`Rate fetched: 1 ${currency} = ${rate.toFixed(6)} ${functionalCurrency}`);
						$('#rate-info').show();
						
						const Toast = Swal.mixin({
							toast: true,
							position: 'top-end',
							showConfirmButton: false,
							timer: 2000,
							timerProgressBar: true
						});
						Toast.fire({
							icon: 'success',
							title: `Rate updated: 1 ${currency} = ${rate.toFixed(6)} ${functionalCurrency}`
						});
					}
				},
				error: function(xhr) {
					console.error('Failed to fetch exchange rate:', xhr);
					$.get('{{ route("api.exchange-rates.rate") }}', {
						from: currency,
						to: functionalCurrency
					})
					.done(function(response) {
						if (response.success && response.data && response.data.rate) {
							const rate = parseFloat(response.data.rate);
							rateInput.val(rate.toFixed(6));
							$('#rate-source').text(`Rate fetched (fallback): 1 ${currency} = ${rate.toFixed(6)} ${functionalCurrency}`);
							$('#rate-info').show();
						}
					})
					.fail(function() {
						Swal.fire({
							icon: 'warning',
							title: 'Rate Fetch Failed',
							text: 'Please manually enter the exchange rate.',
							timer: 3000,
							showConfirmButton: false
						});
					});
				},
				complete: function() {
					btn.prop('disabled', false).html(originalHtml);
					rateInput.prop('disabled', false);
				}
			});
		}
		// Initialize Select2 for existing item dropdowns
		initializeSelect2();
		
		// Initialize Select2 for any new rows
		$(document).on('select2:open', function() {
			initializeSelect2();
		});

		// Add event listeners for quantity and price changes
		$(document).on('input', 'input[name*="[quantity]"], input[name*="[unit_price]"]', function() {
			validateQuantityForRow($(this).closest('tr'));
			updateTotalAmount();
		});

		// Add event listeners for VAT changes
		$(document).on('input', 'input[name*="[vat_rate]"]', function() {
			updateTotalAmount();
		});

		$(document).on('change', 'select[name*="[vat_type]"]', function() {
			updateTotalAmount();
		});

		// Add event listener for discount changes
		$(document).on('input', 'input[name="discount_rate"]', function() {
			updateTotalAmount();
		});

		// Add event listener for discount type changes
		$(document).on('change', 'select[name="discount_type"]', function() {
			updateDiscountHint();
			updateTotalAmount();
		});
		
		// Initialize discount hint on page load
		updateDiscountHint();

		// Initial calculation
		updateTotalAmount();

		// Handle form submission
		$('#posEditForm').on('submit', function(e) {
			e.preventDefault();
			
			// Validate all rows before submission
			let hasErrors = false;
			$('#itemsTable tbody tr').each(function() {
				validateQuantityForRow($(this));
				if ($(this).find('input[name*="[quantity]"]').hasClass('is-invalid')) {
					hasErrors = true;
				}
			});

			if (hasErrors) {
				Swal.fire({
					icon: 'error',
					title: 'Validation Error',
					text: 'Please fix stock validation errors before submitting.'
				});
				return;
			}
			
			// Show loading state
			const submitBtn = $(this).find('button[type="submit"]');
			const originalText = submitBtn.html();
			submitBtn.html('<i class="bx bx-loader bx-spin me-1"></i>Saving...').prop('disabled', true);

			$.ajax({
				url: $(this).attr('action'),
				method: 'POST',
				data: $(this).serialize(),
				success: function(response) {
					if (response.success) {
						Swal.fire({
							icon: 'success',
							title: 'Success!',
							text: response.message,
							showConfirmButton: true,
							confirmButtonText: 'OK'
						}).then((result) => {
							if (result.isConfirmed) {
								window.location.href = response.redirect_url;
							}
						});
					} else {
						Swal.fire({
							icon: 'error',
							title: 'Error!',
							text: response.message || 'Failed to update POS sale'
						});
					}
				},
				error: function(xhr) {
					let errorMessage = 'An error occurred while updating the POS sale';
					if (xhr.responseJSON && xhr.responseJSON.message) {
						errorMessage = xhr.responseJSON.message;
					}
					
					Swal.fire({
						icon: 'error',
						title: 'Error!',
						text: errorMessage
					});
				},
				complete: function() {
					// Restore button state
					submitBtn.html(originalText).prop('disabled', false);
				}
			});
		});
	});

	function initializeSelect2() {
		$('select[name*="[item_id]"]').each(function() {
			if (!$(this).hasClass('select2-hidden-accessible')) {
				$(this).select2({
					placeholder: 'Select item',
					allowClear: true,
					width: '100%'
				}).on('select2:select', function(e) {
					const itemId = e.params.data.id;
					const row = $(this).closest('tr');
					loadItemDetails(itemId, row);
					// Validate quantity after item selection
					setTimeout(function() {
						validateQuantityForRow(row);
					}, 100);
				}).on('change', function() {
					// Also validate on change event
					const row = $(this).closest('tr');
					validateQuantityForRow(row);
				});
			}
		});
	}

	function loadItemDetails(itemId, row) {
		$.ajax({
			url: `{{ route('sales.pos.item-details') }}`,
			method: 'POST',
			data: {
				item_id: itemId,
				_token: '{{ csrf_token() }}'
			},
			success: function(response) {
				// Update unit price
				row.find('input[name*="[unit_price]"]').val(response.unit_price);
				
				// Update VAT rate if not already set
				const vatRateInput = row.find('input[name*="[vat_rate]"]');
				if (!vatRateInput.val() || vatRateInput.val() == '0.00') {
					vatRateInput.val(response.vat_rate);
				}
				
				// Update VAT type if not already set
				const vatTypeSelect = row.find('select[name*="[vat_type]"]');
				if (!vatTypeSelect.val()) {
					vatTypeSelect.val(response.vat_type);
				}

				// Validate quantity after loading item details
				validateQuantityForRow(row);

				// Update totals after loading item details
				setTimeout(function() {
					updateTotalAmount();
				}, 100);
			},
			error: function() {
				Swal.fire({
					icon: 'error',
					title: 'Error',
					text: 'Failed to load item details'
				});
			}
		});
	}

	function validateQuantityForRow(row) {
		const itemSelect = row.find('select[name*="[item_id]"]');
		const quantityInput = row.find('input[name*="[quantity]"]');
		
		if (!itemSelect.val() || !quantityInput.length) {
			return;
		}

		const selectedOption = itemSelect.find('option:selected');
		const itemType = selectedOption.data('item-type') || 'product';
		const trackStock = selectedOption.data('track-stock') === 'true' || selectedOption.data('track-stock') === true;
		const availableStock = parseFloat(selectedOption.data('stock')) || 0;
		const enteredQuantity = parseFloat(quantityInput.val()) || 0;
		const itemId = parseInt(itemSelect.val());

		// Skip stock validation for service items or items that don't track stock
		if (itemType === 'service' || !trackStock) {
			quantityInput.removeClass('is-invalid');
			quantityInput.next('.invalid-feedback').remove();
			return;
		}

		// Add back the original quantity for this item if it existed in the old sale
		// This accounts for the fact that the original sale already deducted stock
		const originalQuantity = originalQuantities[itemId] || 0;
		const adjustedAvailableStock = availableStock + originalQuantity;

		// Validate stock for products that track stock
		if (enteredQuantity > adjustedAvailableStock) {
			quantityInput.addClass('is-invalid');
			if (quantityInput.next('.invalid-feedback').length === 0) {
				quantityInput.after(`<div class="invalid-feedback">Insufficient stock. Available: ${adjustedAvailableStock}</div>`);
			} else {
				quantityInput.next('.invalid-feedback').text(`Insufficient stock. Available: ${adjustedAvailableStock}`);
			}
		} else {
			quantityInput.removeClass('is-invalid');
			quantityInput.next('.invalid-feedback').remove();
		}
	}

	function addItemRow() {
		const tableBody = document.querySelector('#itemsTable tbody');
		const index = tableBody.children.length;
		const tr = document.createElement('tr');
		tr.innerHTML = `
			<td>
				<select name="items[${index}][item_id]" class="form-select item-select" required>
					<option value="">Select item</option>
					@foreach($inventoryItems as $inv)
						@php
							$stockService = new \App\Services\InventoryStockService();
							$currentStock = 0;
							if ($inv->item_type !== 'service' && $inv->track_stock) {
								$currentStock = $stockService->getItemStockAtLocation($inv->id, session('location_id'));
							}
						@endphp
						<option value="{{ $inv->id }}" 
							data-price="{{ $inv->resolved_unit_price ?? $inv->unit_price }}" 
							data-vat-rate="{{ $inv->vat_rate ?? 18 }}" 
							data-vat-type="{{ $inv->vat_type ?? 'inclusive' }}"
							data-item-type="{{ $inv->item_type ?? 'product' }}"
							data-track-stock="{{ $inv->track_stock ? 'true' : 'false' }}"
							data-stock="{{ $currentStock }}">{{ $inv->name }} ({{ $inv->code }}) - TZS {{ number_format($inv->resolved_unit_price ?? $inv->unit_price ?? 0, 2) }}</option>
					@endforeach
				</select>
			</td>
			<td><input type="number" name="items[${index}][quantity]" class="form-control text-end" step="0.01" min="0.01" value="1.00" required></td>
			<td><input type="number" name="items[${index}][unit_price]" class="form-control text-end" step="0.01" min="0" value="0.00" required></td>
			<td><input type="number" name="items[${index}][vat_rate]" class="form-control text-end" step="0.01" min="0" value="{{ get_default_vat_rate() }}"></td>
			<td>
				<select name="items[${index}][vat_type]" class="form-select">
					<option value="inclusive" {{ get_default_vat_type() == 'inclusive' ? 'selected' : '' }}>Inclusive</option>
					<option value="exclusive" {{ get_default_vat_type() == 'exclusive' ? 'selected' : '' }}>Exclusive</option>
					<option value="no_vat" {{ get_default_vat_type() == 'no_vat' ? 'selected' : '' }}>No VAT</option>
				</select>
			</td>
			<td class="text-center">
				<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItemRow(this)"><i class="bx bx-trash"></i></button>
			</td>
		`;
		tableBody.appendChild(tr);
		
		// Initialize Select2 for the new row
		$(tr).find('.item-select').select2({
			placeholder: 'Select item',
			allowClear: true,
			width: '100%'
		}).on('select2:select', function(e) {
			const itemId = e.params.data.id;
			const row = $(this).closest('tr');
			loadItemDetails(itemId, row);
			// Validate quantity after item selection
			setTimeout(function() {
				validateQuantityForRow(row);
			}, 100);
		}).on('change', function() {
			// Also validate on change event
			const row = $(this).closest('tr');
			validateQuantityForRow(row);
		});

		// Update totals after adding new row
		updateTotalAmount();
	}

	function removeItemRow(btn) {
		const tr = $(btn).closest('tr');
		// Destroy Select2 before removing (with error handling)
		tr.find('select').each(function() {
			if ($(this).hasClass('select2-hidden-accessible')) {
				try {
					$(this).select2('destroy');
				} catch (e) {
					console.log('Select2 destroy failed:', e);
				}
			}
		});
		tr.remove();
		// Update totals after removing row
		updateTotalAmount();
	}

	function updateDiscountHint() {
		const discountType = $('#discount_type').val();
		const hintElement = $('#discount_hint');
		const rateInput = $('#discount_rate');
		
		if (discountType === 'none') {
			hintElement.text('');
			rateInput.hide();
		} else if (discountType === 'percentage') {
			hintElement.text('Enter percentage (e.g., 10 for 10%)');
			rateInput.show();
		} else if (discountType === 'fixed') {
			hintElement.text('Enter fixed amount in TZS');
			rateInput.show();
		}
	}

	function updateTotalAmount() {
		let subtotal = 0;
		let vatTotal = 0;
		let totalLineTotals = 0; // Sum of all line totals (for VAT inclusive, this already includes VAT)
		const discountRate = parseFloat($('input[name="discount_rate"]').val()) || 0;
		const discountType = $('select[name="discount_type"]').val();

		// Calculate subtotal and VAT for each item
		$('#itemsTable tbody tr').each(function() {
			const quantity = parseFloat($(this).find('input[name*="[quantity]"]').val()) || 0;
			const unitPrice = parseFloat($(this).find('input[name*="[unit_price]"]').val()) || 0;
			const vatRate = parseFloat($(this).find('input[name*="[vat_rate]"]').val()) || 0;
			const vatType = String($(this).find('select[name*="[vat_type]"]').val()).toLowerCase();

			const baseAmount = quantity * unitPrice;
			let itemVat = 0;
			let netAmount = 0;
			let lineTotal = 0;

			// Calculate VAT and net amount based on type
			if (vatType === 'inclusive') {
				itemVat = baseAmount * (vatRate / (100 + vatRate));
				netAmount = baseAmount - itemVat; // Net amount = gross - VAT
				lineTotal = baseAmount; // For inclusive, line total is baseAmount (includes VAT)
			} else if (vatType === 'exclusive') {
				itemVat = baseAmount * (vatRate / 100);
				netAmount = baseAmount; // For exclusive, unit price is already net
				lineTotal = baseAmount + itemVat; // For exclusive, line total = base + VAT
			} else {
				// No VAT
				itemVat = 0;
				netAmount = baseAmount;
				lineTotal = baseAmount; // No VAT, line total = base amount
			}

			subtotal += netAmount; // Add net amount to subtotal
			vatTotal += itemVat;
			totalLineTotals += lineTotal; // Sum of all line totals
		});

		// Calculate discount
		let discountTotal = 0;
		if (discountType === 'percentage') {
			discountTotal = subtotal * (discountRate / 100);
		} else if (discountType === 'fixed') {
			discountTotal = discountRate;
		} else {
			discountTotal = 0;
		}

		// Calculate grand total
		// For VAT inclusive items, total should be sum of line totals minus discount
		// For VAT exclusive items, total should be subtotal + VAT minus discount
		// Since we have mixed items, use the sum of line totals (which already accounts for VAT type)
		const grandTotal = totalLineTotals - discountTotal;

		// Update summary display
		$('#subtotal').text('TZS ' + subtotal.toFixed(2));
		$('#vat-total').text('TZS ' + vatTotal.toFixed(2));
		$('#discount-total').text('TZS ' + discountTotal.toFixed(2));
		$('#grand-total').text('TZS ' + grandTotal.toFixed(2));

		// Update amount paid field - always update to match grand total
		const amountPaidField = $('#bank_amount');
		if (amountPaidField.length) {
			const currentAmount = parseFloat(amountPaidField.val()) || 0;
			// Always update to match grand total (especially when items are removed)
			amountPaidField.val(grandTotal.toFixed(2));
			// Trigger change event to ensure any listeners are notified
			amountPaidField.trigger('change');
			console.log('Amount paid updated from', currentAmount, 'to', grandTotal);
		} else {
			// Fallback to name selector
			const fallbackField = $('input[name="bank_amount"]');
			if (fallbackField.length) {
				fallbackField.val(grandTotal.toFixed(2)).trigger('change');
				console.log('Amount paid updated (fallback) to', grandTotal);
			}
		}
	}
</script>
@endpush 