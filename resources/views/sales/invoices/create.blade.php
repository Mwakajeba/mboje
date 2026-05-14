@extends('layouts.main')

@section('title', 'Create Sales Invoice')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-store'],
            ['label' => 'Sales Invoices', 'url' => route('sales.invoices.index'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Create Invoice', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        @php
            $defaultInvoiceDueDays = \App\Models\SystemSetting::getValue('inventory_default_invoice_due_days', 30);
            $defaultPaymentTerm = match((int) $defaultInvoiceDueDays) {
                0 => 'immediate',
                15 => 'net_15',
                30 => 'net_30',
                45 => 'net_45',
                60 => 'net_60',
                default => 'custom',
            };
            $selectedPaymentTerm = old('payment_terms', $defaultPaymentTerm);
            $selectedPaymentDays = old('payment_days', $defaultInvoiceDueDays);
        @endphp
        <h6 class="mb-0 text-uppercase">CREATE SALES INVOICE</h6>
        <hr />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-receipt me-2"></i>New Sales Invoice</h5>
            </div>
            <div class="card-body">
        <form id="invoice-form" method="POST" action="{{ route('sales.invoices.store') }}" enctype="multipart/form-data">
            @csrf
                    <input type="hidden" name="withholding_tax_rate" id="withholding_tax_rate_input" value="0">
                    <input type="hidden" name="withholding_tax_type" id="withholding_tax_type_input" value="percentage">
            <div class="row">
                        <!-- Customer Information -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <select class="form-select select2-single" id="customer_id" name="customer_id" required>
                                                <option value="">Select Customer</option>
                                                @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ $selectedCustomer && $selectedCustomer->id == $customer->id ? 'selected' : '' }}>
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
                                <label for="invoice_date" class="form-label">Invoice Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control transaction-date" id="invoice_date" name="invoice_date"
                                       value="{{ old('invoice_date', now()->toDateString()) }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="due_date" name="due_date"
                                       value="{{ old('due_date', now()->addDays((int) $defaultInvoiceDueDays)->toDateString()) }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                                    </div>
                                </div>

                        <!-- Customer Credit Information -->
                        <div class="row" id="credit-info-row" style="display: none;">
                            <div class="col-12">
                                <div class="card border-info mb-3" id="credit-info-card">
                                    <div class="card-header bg-info text-white d-flex align-items-center">
                                        <i class="bx bx-credit-card me-2"></i>
                                        <strong>Customer Credit Information</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <p class="mb-1 text-muted small">Credit Limit</p>
                                                    <h5 class="mb-0 fw-bold" id="credit-limit-display">-</h5>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <p class="mb-1 text-muted small">Current Balance</p>
                                                    <h5 class="mb-0 fw-bold" id="current-balance-display">-</h5>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <p class="mb-1 text-muted small">Available Credit</p>
                                                    <h5 class="mb-0 fw-bold" id="available-credit-display">-</h5>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <p class="mb-1 text-muted small">Status</p>
                                                    <span class="badge" id="credit-status-badge">-</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="sales_order_id" class="form-label">Sales Order</label>
                                <select class="form-select select2-single" id="sales_order_id" name="sales_order_id">
                                    <option value="">Select Sales Order (Optional)</option>
                                    @foreach($salesOrders as $order)
                                        <option value="{{ $order->id }}">
                                            {{ $order->order_number }} - {{ $order->customer->name }}
                                            ({{ $order->order_date->format('M d, Y') }} - TZS {{ number_format($order->total_amount, 2) }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Only unconverted sales orders are shown</small>
                                <div id="order-loading" class="mt-2" style="display: none;">
                                    <small class="text-info"><i class="bx bx-loader bx-spin me-1"></i>Loading order details...</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            @php
                                $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', auth()->user()->company->functional_currency ?? 'TZS');
                            @endphp
                            <div class="mb-3">
                                <label for="currency" class="form-label">Currency</label>
                                <select class="form-select select2-single" id="currency" name="currency">
                                    @if(isset($currencies) && $currencies->isNotEmpty())
                                        @foreach($currencies as $currency)
                                            <option value="{{ $currency->currency_code }}"
                                                    {{ old('currency', $functionalCurrency) == $currency->currency_code ? 'selected' : '' }}>
                                                {{ $currency->currency_name ?? $currency->currency_code }} ({{ $currency->currency_code }})
                                            </option>
                                        @endforeach
                                    @else
                                        <option value="{{ $functionalCurrency }}" selected>{{ $functionalCurrency }}</option>
                                    @endif
                                </select>
                                <small class="text-muted">Currencies from FX RATES MANAGEMENT</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="exchange_rate" class="form-label">Exchange Rate</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="exchange_rate" name="exchange_rate"
                                           value="1.000000" step="0.000001" min="0.000001" placeholder="1.000000">
                                    <button type="button" class="btn btn-outline-secondary" id="fetch-rate-btn">
                                        <i class="bx bx-refresh"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Rate relative to TZS</small>
                                <div id="rate-info" class="mt-1" style="display: none;">
                                    <small class="text-info">
                                        <i class="bx bx-info-circle"></i>
                                        <span id="rate-source">Rate fetched from API</span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="reference_no" class="form-label">Reference No.</label>
                                <input type="text" class="form-control" id="reference_no" name="reference_no"
                                       value="{{ old('reference_no') }}" placeholder="e.g. PO-12345">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                        <label for="payment_terms" class="form-label">Payment Terms <span class="text-danger">*</span></label>
                        <select class="form-select" id="payment_terms" name="payment_terms" required>
                            <option value="immediate" {{ $selectedPaymentTerm === 'immediate' ? 'selected' : '' }}>Immediate</option>
                            <option value="net_15" {{ $selectedPaymentTerm === 'net_15' ? 'selected' : '' }}>Net 15</option>
                            <option value="net_30" {{ $selectedPaymentTerm === 'net_30' ? 'selected' : '' }}>Net 30</option>
                            <option value="net_45" {{ $selectedPaymentTerm === 'net_45' ? 'selected' : '' }}>Net 45</option>
                            <option value="net_60" {{ $selectedPaymentTerm === 'net_60' ? 'selected' : '' }}>Net 60</option>
                            <option value="custom" {{ $selectedPaymentTerm === 'custom' ? 'selected' : '' }}>Custom</option>
                        </select>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Days -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                <label for="payment_days" class="form-label">Payment Days <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="payment_days" name="payment_days"
                                       value="{{ $selectedPaymentDays }}" min="0" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Terms Configuration -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Early Payment Discount</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="hidden" name="early_payment_discount_enabled" value="0">
                                            <input class="form-check-input" type="checkbox" id="early_payment_discount_enabled" name="early_payment_discount_enabled" value="1">
                                            <label class="form-check-label" for="early_payment_discount_enabled">
                                                Early payment discount
                                            </label>
                                        </div>
                                    </div>
                                    <div id="early_payment_discount_fields" style="display: none;">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label for="early_payment_discount_type" class="form-label">Type</label>
                                                <select class="form-select" id="early_payment_discount_type" name="early_payment_discount_type">
                                                    <option value="percentage">Percentage</option>
                                                    <option value="fixed">Fixed Amount</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="early_payment_discount_rate" class="form-label">Rate/Amount</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="early_payment_discount_rate" name="early_payment_discount_rate"
                                                           value="0" step="0.01" min="0" placeholder="0">
                                                    <span class="input-group-text" id="early-discount-unit">%</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="early_payment_days" class="form-label">If paid within</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="early_payment_days" name="early_payment_days"
                                                           value="0" min="0" placeholder="0">
                                                    <span class="input-group-text">days</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Late Payment Fees</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="hidden" name="late_payment_fees_enabled" value="0">
                                            <input class="form-check-input" type="checkbox" id="late_payment_fees_enabled" name="late_payment_fees_enabled" value="1">
                                            <label class="form-check-label" for="late_payment_fees_enabled">
                                                Late payment fees
                                            </label>
                                        </div>
                                    </div>
                                    <div id="late_payment_fees_fields" style="display: none;">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="late_payment_fees_type" class="form-label">Type</label>
                                                <select class="form-select" id="late_payment_fees_type" name="late_payment_fees_type">
                                                    <option value="monthly">Charge monthly</option>
                                                    <option value="one_time">One-time charge</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="late_payment_fees_rate" class="form-label">Rate</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="late_payment_fees_rate" name="late_payment_fees_rate"
                                                           value="0" step="0.01" min="0" placeholder="0">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                                            <th width="25%">Item</th>
                                            <th width="15%">Quantity</th>
                                            <th width="15%">Unit Price</th>
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
                                            <td colspan="4" class="text-end"><strong>VAT Amount:</strong></td>
                                            <td><strong id="vat-amount">0.00</strong></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="vat_amount" id="vat-amount-input" value="0">
                                        {{-- WHT row is hidden - WHT is only applied at payment/receipt time, not at invoice creation --}}
                                        <tr id="withholding-tax-row" style="display: none !important;">
                                            <td colspan="4" class="text-end"><strong>Withholding Tax:</strong></td>
                                            <td><strong id="withholding-tax-amount">0.00</strong></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="withholding_tax_amount" id="withholding-tax-amount-input" value="0">
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Discount:</strong></td>
                                            <td>
                                                <input type="number" class="form-control" id="discount_amount" name="discount_amount"
                                                       value="0" step="0.01" min="0" placeholder="0.00">
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

                    <!-- Withholding Tax Settings -->
                    <div class="row mt-4">
                        <div class="col-md-6">

                </div>

                    <!-- Notes and Terms -->
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="4"
                                    placeholder="Additional notes for this invoice..."></textarea>
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
                                <small class="text-muted">Upload customer PO, signed invoice, or related document (PDF or image, max 5MB).</small>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('sales.invoices.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="submit-btn" data-processing-text="Creating...">
                            <i class="bx bx-check me-1"></i>Create Invoice
                        </button>
                        <!-- CSV export/import buttons (shown when items > 30) -->
                        <button type="button" class="btn btn-success" id="export-csv-btn" style="display: none;">
                            <i class="bx bx-download me-1"></i>Export CSV
                        </button>
                        <a href="#" class="btn btn-primary" id="import-csv-btn" style="display: none;">
                            <i class="bx bx-upload me-1"></i>Import CSV
                        </a>
                    </div>
                </form>
                            </div>
                            </div>
                            </div>
                            </div>
                                        @error('payment_terms') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
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
                                    data-price="{{ $item->resolved_unit_price ?? $item->unit_price }}"
                                    data-wholesale-price="{{ $item->has_wholesale ? ($item->resolved_wholesale_unit_price ?? $item->wholesale_unit_price ?? 0) : '' }}"
                                    data-has-wholesale="{{ $item->has_wholesale ? '1' : '0' }}"
                                    data-stock="{{ $item->current_stock }}"
                                    data-minimum-stock="{{ $item->minimum_stock ?? 0 }}"
                                    data-unit="{{ $item->unit_of_measure }}"
                                    data-vat-rate="0"
                                    data-vat-type="no_vat"
                                    data-item-type="{{ $item->item_type }}"
                                    data-track-stock="{{ $item->track_stock ? 'true' : 'false' }}">
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
                <div class="row" id="modal_price_tier_row" style="display: none;">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="modal_price_tier" class="form-label">Price type</label>
                            <select class="form-select" id="modal_price_tier">
                                <option value="retail">Retail</option>
                                <option value="wholesale">Wholesale</option>
                            </select>
                            <small class="text-muted">This item has a wholesale price. Default is retail.</small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_vat_type" class="form-label">VAT Type</label>
                            <select class="form-select" id="modal_vat_type">
                                <option value="no_vat" selected>No VAT</option>
                                <option value="inclusive">Inclusive</option>
                                <option value="exclusive">Exclusive</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_vat_rate" class="form-label">VAT Rate (%)</label>
                            <input type="number" class="form-control" id="modal_vat_rate" value="0" step="0.01" min="0">
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="add-item-btn">Add Item</button>
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
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_status" class="form-label">Status</label>
                                <select class="form-select" id="customer_status" name="status">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_company_name" class="form-label">Company Name</label>
                                <input type="text" class="form-control" id="customer_company_name" name="company_name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_credit_limit" class="form-label">Credit Limit</label>
                                <input type="number" class="form-control" id="customer_credit_limit" name="credit_limit" step="0.01" min="0">
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
    border-top-right-radius: 0;
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
var copyFromInvoice = @json($copyFromInvoice ?? null);
// TEST: This should show immediately when page loads
console.log('=== Sales Invoice Script Loading ===');
console.log('jQuery version:', typeof jQuery !== 'undefined' ? jQuery.fn.jquery : 'jQuery not loaded');
console.log('Select2 available:', typeof $.fn.select2 !== 'undefined');

$(document).ready(function() {
    console.log('=== Document ready - initializing sales invoice form ===');

    // Ensure customer modal is attached to body to avoid stacking/overflow issues
    if ($('#customerModal').length) {
        $('#customerModal').appendTo('body');
    }

    // Initialize Select2 for all select2-single elements (including customer dropdown)
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Get functional currency for exchange rate calculations
    const functionalCurrency = '{{ \App\Models\SystemSetting::getValue("functional_currency", auth()->user()->company->functional_currency ?? "TZS") }}';

    // Get customer select element reference
    console.log('=== Initializing customer dropdown ===');
    const customerSelect = $('#customer_id');
    console.log('Customer select element found:', customerSelect.length > 0);
    console.log('Customer select current value:', customerSelect.val());

    // Test: Make sure console is working
    console.log('TEST: Console is working!');

    // Prevent multiple simultaneous AJAX calls
    let creditInfoAjax = null;
    let lastFetchedCustomerId = null;

    // Function to fetch and display customer credit information (defined before use)
    function fetchCustomerCreditInfo(customerId) {
        console.log('🚀 fetchCustomerCreditInfo called with customerId:', customerId);

        if (!customerId) {
            console.log('❌ No customer ID provided, hiding credit info');
            $('#credit-info-row').hide();
            lastFetchedCustomerId = null;
            return;
        }

        // If we're already fetching for this customer, don't fetch again
        if (lastFetchedCustomerId === customerId && creditInfoAjax && creditInfoAjax.readyState !== 4) {
            console.log('Already fetching credit info for this customer, skipping...');
            return;
        }

        // Cancel previous request if still pending
        if (creditInfoAjax && creditInfoAjax.readyState !== 4) {
            creditInfoAjax.abort();
        }

        lastFetchedCustomerId = customerId;

        // Show loading state (but don't show row yet - wait for response)
        $('#credit-info-card').addClass('opacity-50');
        $('#credit-limit-display').html('<i class="bx bx-loader bx-spin"></i>');
        $('#current-balance-display').html('<i class="bx bx-loader bx-spin"></i>');
        $('#available-credit-display').html('<i class="bx bx-loader bx-spin"></i>');
        $('#credit-status-badge').html('-');

        // Build the URL - use absolute path to avoid route conflicts
        // The route is: /sales/invoices/customer-credit-info
        const baseUrl = window.location.origin;
        const creditInfoUrl = baseUrl + '/sales/invoices/customer-credit-info';
        console.log('=== AJAX Request Details ===');
        console.log('Base URL:', baseUrl);
        console.log('Credit Info URL:', creditInfoUrl);
        console.log('Customer ID:', customerId);
        console.log('Full URL with params:', creditInfoUrl + '?customer_id=' + customerId);

        creditInfoAjax = $.ajax({
            url: creditInfoUrl,
            method: 'GET',
            data: { customer_id: customerId },
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            context: { customerId: customerId }, // Store customerId in context for error handler
            success: function(response) {
                const customerId = this.customerId; // Get customerId from context
                console.log('=== Credit info response received ===');
                console.log('Customer ID:', customerId);
                console.log('Last fetched customer ID:', lastFetchedCustomerId);
                console.log('Response type:', typeof response);
                console.log('Response keys:', Object.keys(response || {}));
                console.log('Full response:', JSON.stringify(response, null, 2));

                // Check if response is DataTables format (wrong endpoint)
                if (response && (response.draw !== undefined || response.data !== undefined && Array.isArray(response.data))) {
                    console.error('❌ ERROR: Received DataTables response instead of credit info!');
                    console.error('This means the wrong endpoint was called. Expected credit info, got DataTables format.');
                    console.error('Response:', response);
                    $('#credit-info-row').hide();
                    $('#credit-info-card').removeClass('opacity-50');
                    return;
                }

                // Only process if this is still the current customer (prevent stale responses)
                if (lastFetchedCustomerId !== customerId) {
                    console.log('⚠️ IGNORING stale response - customer mismatch');
                    return;
                }

                // Check if customer has credit limit
                if (response && response.success === true) {
                    const creditLimit = parseFloat(response.credit_limit) || 0;
                    const hasCreditLimit = response.has_credit_limit === true || creditLimit > 0;

                    console.log('Processing credit info:');
                    console.log('  - creditLimit:', creditLimit);
                    console.log('  - has_credit_limit (from response):', response.has_credit_limit);
                    console.log('  - hasCreditLimit (computed):', hasCreditLimit);

                    // Show credit info ONLY if customer has a credit limit set
                    if (response.has_credit_limit === true && creditLimit > 0) {
                        // Display credit information
                        const currentBalance = parseFloat(response.current_balance) || 0;
                        const availableCredit = parseFloat(response.available_credit) || 0;

                        console.log('Displaying credit info - Limit:', creditLimit, 'Balance:', currentBalance, 'Available:', availableCredit);

                        $('#credit-limit-display').text('TZS ' + creditLimit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('#current-balance-display').text('TZS ' + currentBalance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

                        // Format available credit (can be negative)
                        const availableCreditFormatted = 'TZS ' + availableCredit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        $('#available-credit-display').text(availableCreditFormatted);

                        // Set color based on available credit
                        if (availableCredit < 0) {
                            $('#available-credit-display').removeClass('text-success text-warning').addClass('text-danger');
                            $('#credit-status-badge').removeClass('bg-success bg-warning').addClass('bg-danger').text('Over Limit');
                            $('#credit-info-card').removeClass('border-info border-warning').addClass('border-danger');
                            $('#credit-info-card .card-header').removeClass('bg-info bg-warning').addClass('bg-danger');
                        } else if (availableCredit < (creditLimit * 0.2)) {
                            // Less than 20% of credit limit remaining
                            $('#available-credit-display').removeClass('text-success text-danger').addClass('text-warning');
                            $('#credit-status-badge').removeClass('bg-success bg-danger').addClass('bg-warning').text('Low Credit');
                            $('#credit-info-card').removeClass('border-info border-danger').addClass('border-warning');
                            $('#credit-info-card .card-header').removeClass('bg-info bg-danger').addClass('bg-warning');
                        } else {
                            $('#available-credit-display').removeClass('text-warning text-danger').addClass('text-success');
                            $('#credit-status-badge').removeClass('bg-warning bg-danger').addClass('bg-success').text('Good');
                            $('#credit-info-card').removeClass('border-warning border-danger').addClass('border-info');
                            $('#credit-info-card .card-header').removeClass('bg-warning bg-danger').addClass('bg-info');
                        }

                        // Show the credit info row
                        $('#credit-info-row').show();
                        $('#credit-info-card').removeClass('opacity-50');
                        console.log('✅ Credit info DISPLAYED for customer with credit limit:', customerId);
                    } else {
                        // Hide credit info if customer has no credit limit
                        console.log('❌ Customer has no credit limit, hiding credit info section');
                        $('#credit-info-row').hide();
                        $('#credit-info-card').removeClass('opacity-50');
                    }
                }

                // Remove loading state
                $('#credit-info-card').removeClass('opacity-50');
            },
            error: function(xhr) {
                console.error('Error fetching credit info:', xhr);
                $('#credit-info-row').hide();
                $('#credit-info-card').removeClass('opacity-50');
            }
        });
    }

    // Variables for customer change handling
    let isProcessingCustomerChange = false;
    let customerChangeTimeout = null;

    // Function to handle customer change
    function handleCustomerChange() {
        console.log('🔔 handleCustomerChange called!');

        // Prevent multiple simultaneous calls
        if (isProcessingCustomerChange) {
            console.log('Customer change already processing, skipping...');
            return;
        }

        clearTimeout(customerChangeTimeout);
        customerChangeTimeout = setTimeout(function() {
            isProcessingCustomerChange = true;
            const customerId = customerSelect.val();
            console.log('📋 Customer changed event processed, customerId:', customerId);

            if (customerId && customerId !== '' && customerId !== null) {
                console.log('✅ Valid customer selected, fetching credit info for customer:', customerId);
                fetchCustomerCreditInfo(customerId);
            } else {
                // Only hide if customer is actually cleared (not just during initialization)
                console.log('❌ No customer selected, hiding credit info');
                lastFetchedCustomerId = null;
                $('#credit-info-row').hide();
            }

            // Reset flag after a short delay
            setTimeout(function() {
                isProcessingCustomerChange = false;
            }, 200);
        }, 150); // Small delay to debounce
    }

    // Listen for both regular change and Select2 events
    console.log('Attaching event handlers to customer dropdown...');

        // Regular change event
        customerSelect.on('change', function(e) {
            console.log('🔔 Regular change event fired!', e);
            console.log('Selected value:', $(this).val());
            handleCustomerChange();
        });

        // Select2 specific events
        customerSelect.on('select2:select', function(e) {
            console.log('🔔 Select2 select event fired!', e);
            console.log('Selected value:', $(this).val());
            handleCustomerChange();
        });

        customerSelect.on('select2:selecting', function(e) {
            console.log('🔔 Select2 selecting event fired!', e);
        });

        // Also try listening on the Select2 container
        customerSelect.on('select2:open', function() {
            console.log('🔔 Select2 dropdown opened');
        });

        // Direct value monitoring as fallback
        let lastCustomerValue = customerSelect.val();
        setInterval(function() {
            const currentValue = customerSelect.val();
            if (currentValue !== lastCustomerValue) {
                console.log('🔔 Customer value changed via monitoring:', lastCustomerValue, '->', currentValue);
                lastCustomerValue = currentValue;
                if (currentValue) {
                    handleCustomerChange();
                }
            }
        }, 500);

        console.log('Event handlers attached successfully');

        // Fetch credit info if customer is pre-selected
        if (customerSelect.val()) {
            console.log('Pre-selected customer found, fetching credit info:', customerSelect.val());
            fetchCustomerCreditInfo(customerSelect.val());
        }

    $('.select2-modal').select2({
        theme: 'bootstrap-5',
        width: '100%',
        dropdownParent: $('#itemModal')
    });

    // Add item button click
    $('#add-item').click(function() {
        $('#itemModal').modal('show');
        resetModalForm();
    });

    // Add customer button click
    $('#add-customer-btn').click(function() {
        resetCustomerForm();
        $('#add-customer-errors').addClass('d-none').empty();
        $('#customerModal').modal('show');
    });


    // Function to convert item price from functional currency to invoice currency
    function convertItemPrice(basePrice, invoiceCurrency, exchangeRate) {
        if (!basePrice || !invoiceCurrency || !exchangeRate) {
            return basePrice;
        }

        // If invoice currency is functional currency, no conversion needed
        if (invoiceCurrency === functionalCurrency) {
            return parseFloat(basePrice);
        }

        // Convert: Price in FCY = Price in TZS / Exchange Rate
        // Example: 10,000 TZS / 2,500 = 4 USD
        const convertedPrice = parseFloat(basePrice) / parseFloat(exchangeRate);
        return parseFloat(convertedPrice.toFixed(2));
    }

    // Function to get current exchange rate
    function getCurrentExchangeRate() {
        const rate = parseFloat($('#exchange_rate').val()) || 1.000000;
        return rate;
    }

    // Function to get current invoice currency
    function getCurrentInvoiceCurrency() {
        return $('#currency').val() || functionalCurrency;
    }

    function invoiceModalItemHasWholesale(opt) {
        return opt.attr('data-has-wholesale') === '1';
    }

    function toggleInvoiceModalPriceTierRow() {
        const opt = $('#modal_item_id option:selected');
        if (!opt.val()) {
            $('#modal_price_tier_row').hide();
            return;
        }
        if (invoiceModalItemHasWholesale(opt)) {
            $('#modal_price_tier_row').show();
            $('#modal_price_tier').val('retail');
        } else {
            $('#modal_price_tier_row').hide();
            $('#modal_price_tier').val('retail');
        }
    }

    function applyModalPriceForInvoiceTier() {
        const opt = $('#modal_item_id option:selected');
        if (!opt.val()) return;
        const invoiceCurrency = getCurrentInvoiceCurrency();
        const exchangeRate = getCurrentExchangeRate();
        const retailBase = parseFloat(opt.data('price')) || 0;
        const wholesaleBase = parseFloat(opt.attr('data-wholesale-price')) || 0;
        const hasWs = invoiceModalItemHasWholesale(opt);
        const tier = hasWs ? ($('#modal_price_tier').val() || 'retail') : 'retail';
        const basePrice = (hasWs && tier === 'wholesale') ? wholesaleBase : retailBase;
        const convertedPrice = convertItemPrice(basePrice, invoiceCurrency, exchangeRate);
        $('#modal_unit_price').data('original-price', basePrice);
        $('#modal_unit_price').data('original-currency', functionalCurrency);
        $('#modal_unit_price').val(convertedPrice.toFixed(2));
        if (invoiceCurrency !== functionalCurrency && exchangeRate !== 1) {
            $('#modal_unit_price').attr('title', `Converted from ${basePrice.toFixed(2)} ${functionalCurrency} at rate ${exchangeRate}`);
        } else {
            $('#modal_unit_price').removeAttr('title');
        }
        calculateModalLineTotal();
    }

    $('#modal_price_tier').on('change', function() {
        applyModalPriceForInvoiceTier();
    });

    // Item selection in modal
    $('#modal_item_id').change(function() {
        const selectedOption = $(this).find('option:selected');
        // Reset override flag when new item is selected
        $('#modal_quantity').removeData('allow-override');
        if (selectedOption.val()) {
            toggleInvoiceModalPriceTierRow();
            applyModalPriceForInvoiceTier();

            $('#modal_vat_rate').val(selectedOption.data('vat-rate'));
            $('#modal_vat_type').val(selectedOption.data('vat-type'));

            // Set max quantity to available stock (but allow override for low stock items)
            const availableStock = selectedOption.data('stock');
            const itemType = selectedOption.data('item-type');
            const trackStock = selectedOption.data('track-stock') === 'true';

            // Only apply stock validation for products that track stock
            if (itemType === 'product' && trackStock) {
                $('#modal_quantity').attr('max', availableStock);

                // Show stock information
                const itemName = selectedOption.data('name');
                const minimumStock = selectedOption.data('minimum-stock') || 0;
                if (availableStock <= 0) {
                    Swal.fire({
                        title: 'Out of Stock!',
                        text: `${itemName} is currently out of stock.`,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                } else if (minimumStock > 0 && availableStock <= minimumStock) {
                    Swal.fire({
                        title: 'Low Stock Warning',
                        html: `<strong>${itemName}</strong> has low stock:<br>
                               Available: <strong>${availableStock}</strong><br>
                               Minimum Required: <strong>${minimumStock}</strong><br><br>
                               Do you want to continue with this item?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Continue',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33'
                    }).then((result) => {
                        if (!result.isConfirmed) {
                            // User cancelled, clear the selection
                            $('#modal_item_id').val('').trigger('change');
                            resetModalForm();
                        } else {
                            // User confirmed to continue, allow quantity override
                            $('#modal_quantity').data('allow-override', true);
                            $('#modal_quantity').removeAttr('max');
                        }
                    });
                }
            } else {
                // For services or non-tracked items, remove any max limit
                $('#modal_quantity').removeAttr('max');
            }
        } else {
            $('#modal_price_tier_row').hide();
            $('#modal_price_tier').val('retail');
        }
    });

    // Track last checked quantity to prevent duplicate alerts
    let lastCheckedQuantity = null;

    // Validate quantity against stock using 'input' event for real-time validation
    // 'input' event fires on every value change (typing, paste, etc.) - works with all input methods
    // Note: modal_item_id uses select2-single, but we access it via standard jQuery selector
    $('#modal_quantity').on('input', function() {
        // Get selected item from select2 dropdown (works with select2-single)
        const selectedOption = $('#modal_item_id option:selected');
        if (selectedOption.val()) {
            // Use .attr() for data attributes with select2 to ensure we get the correct value
            const availableStock = parseFloat(selectedOption.attr('data-stock') || selectedOption.data('stock')) || 0;
            const itemType = selectedOption.attr('data-item-type') || selectedOption.data('item-type');
            const trackStock = (selectedOption.attr('data-track-stock') || selectedOption.data('track-stock')) === 'true';
            const enteredQuantity = parseFloat($(this).val()) || 0;
            const allowOverride = $(this).data('allow-override') || false;
            // Get minimum stock - try both attr and data methods for compatibility
            const minimumStock = parseFloat(selectedOption.attr('data-minimum-stock') || selectedOption.data('minimum-stock')) || 0;
            const itemName = selectedOption.attr('data-name') || selectedOption.data('name');

            // Debug logging (remove in production)
            console.log('Stock Check:', {
                itemName: itemName,
                availableStock: availableStock,
                enteredQuantity: enteredQuantity,
                minimumStock: minimumStock,
                remainingStock: availableStock - enteredQuantity,
                itemType: itemType,
                trackStock: trackStock
            });

            // Only validate stock for products that track stock
            if (itemType === 'product' && trackStock && enteredQuantity > availableStock && !allowOverride) {
                $(this).addClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
                $(this).after(`<div class="invalid-feedback">Quantity cannot exceed available stock (${availableStock})</div>`);
            } else {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            }

            // Check minimum stock while typing (instantly when condition is met)
            if (itemType === 'product' && trackStock && minimumStock > 0 && enteredQuantity > 0) {
                const remainingStock = availableStock - enteredQuantity;

                console.log('Minimum Stock Check:', {
                    remainingStock: remainingStock,
                    minimumStock: minimumStock,
                    condition: remainingStock < minimumStock,
                    lastChecked: lastCheckedQuantity,
                    entered: enteredQuantity
                });

                // Check if entered quantity would cause stock to go below minimum
                // Show alert if remaining stock is below minimum (allow showing again if quantity changed)
                if (remainingStock < minimumStock && remainingStock >= 0) {
                    // Only show if this is a different quantity than last checked
                    if (enteredQuantity !== lastCheckedQuantity) {
                        // Show alert instantly
                        lastCheckedQuantity = enteredQuantity;

                        Swal.fire({
                            title: 'Minimum Stock Warning',
                            html: `<strong>${itemName}</strong><br><br>
                                   Entered Quantity: <strong>${enteredQuantity}</strong><br>
                                   Available Stock: <strong>${availableStock}</strong><br>
                                   Remaining Stock After Sale: <strong>${remainingStock}</strong><br>
                                   Minimum Stock Required: <strong>${minimumStock}</strong><br><br>
                                   This will leave stock below the minimum level. Do you want to continue?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, Continue',
                            cancelButtonText: 'Cancel',
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        }).then((result) => {
                            if (!result.isConfirmed) {
                                // User cancelled, reset quantity to safe level
                                const safeQuantity = Math.max(0, availableStock - minimumStock);
                                $('#modal_quantity').val(safeQuantity);
                                lastCheckedQuantity = null;
                                calculateModalLineTotal();
                            } else {
                                // User confirmed, allow this quantity
                                lastCheckedQuantity = enteredQuantity;
                            }
                        });
                    }
                } else if (remainingStock >= minimumStock) {
                    // Reset the flag if quantity is now safe
                    lastCheckedQuantity = null;
                }
            }
        }
        calculateModalLineTotal();
    });

    // Check minimum stock when quantity is entered (on blur to avoid multiple alerts)
    $('#modal_quantity').on('blur', function() {
        const selectedOption = $('#modal_item_id option:selected');
        if (selectedOption.val()) {
            const availableStock = parseFloat(selectedOption.data('stock')) || 0;
            const itemType = selectedOption.data('item-type');
            const trackStock = selectedOption.data('track-stock') === 'true';
            const minimumStock = parseFloat(selectedOption.data('minimum-stock')) || 0;
            const enteredQuantity = parseFloat($(this).val()) || 0;
            const itemName = selectedOption.data('name');
            const allowOverride = $(this).data('allow-override') || false;

            // Only check minimum stock for products that track stock
            if (itemType === 'product' && trackStock && minimumStock > 0 && enteredQuantity > 0) {
                const remainingStock = availableStock - enteredQuantity;

                // Check if entered quantity would cause stock to go below minimum
                if (remainingStock < minimumStock && remainingStock >= 0) {
                    Swal.fire({
                        title: 'Minimum Stock Warning',
                        html: `<strong>${itemName}</strong><br><br>
                               Entered Quantity: <strong>${enteredQuantity}</strong><br>
                               Available Stock: <strong>${availableStock}</strong><br>
                               Remaining Stock After Sale: <strong>${remainingStock}</strong><br>
                               Minimum Stock Required: <strong>${minimumStock}</strong><br><br>
                               This will leave stock below the minimum level. Do you want to continue?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Continue',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33'
                    }).then((result) => {
                        if (!result.isConfirmed) {
                            // User cancelled, reset quantity to safe level
                            const safeQuantity = Math.max(0, availableStock - minimumStock);
                            $(this).val(safeQuantity);
                            calculateModalLineTotal();
                        }
                    });
                }
            }
        }
    });

    // Calculate modal line total on input change
    $('#modal_quantity, #modal_unit_price, #modal_vat_rate').on('input', function() {
        calculateModalLineTotal();
    });

    // Handle discount type change
    // Removed discount type change handler

    // Add item button in modal
    $('#add-item-btn').click(function() {
        addItemToTable();
    });

    // Save customer button in modal
    $('#save-customer-btn').click(function() {
        saveCustomer();
    });

    // Remove item
    $(document).on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        calculateTotals();
    });

    // Recalculate on input change
    $(document).on('input', '.item-quantity, .item-price, #discount_amount', function() {
        const row = $(this).data('row');
        if (row) {
            updateRowTotal(row);
        }
        calculateTotals();
    });

    // Handle payment terms change
    $('#payment_terms').change(function() {
        const terms = $(this).val();
        let days = 30;

        switch(terms) {
            case 'immediate':
                days = 0;
                break;
            case 'net_15':
                days = 15;
                break;
            case 'net_30':
                days = 30;
                break;
            case 'net_45':
                days = 45;
                break;
            case 'net_60':
                days = 60;
                break;
            case 'custom':
                // Keep current value for custom
                return;
        }

        $('#payment_days').val(days);
        updateDueDate();
    });

    // Update due date when invoice date or payment days change
    $('#invoice_date, #payment_days').change(function() {
        updateDueDate();
    });

    // WHT is NOT handled at invoice creation - it's only applied at payment/receipt time
    // All WHT fields are hidden and set to 0
    // WHT handlers removed since WHT fields are no longer present in the form

    // Early Payment Discount functionality
    $('#early_payment_discount_enabled').change(function() {
        if ($(this).is(':checked')) {
            $('#early_payment_discount_fields').show();
        } else {
            $('#early_payment_discount_fields').hide();
        }
    });

    // Early Payment Discount Type change
    $('#early_payment_discount_type').change(function() {
        const type = $(this).val();
        if (type === 'percentage') {
            $('#early-discount-unit').text('%');
        } else {
            $('#early-discount-unit').text('TSh');
        }
    });

    // Late Payment Fees functionality
    $('#late_payment_fees_enabled').change(function() {
        if ($(this).is(':checked')) {
            $('#late_payment_fees_fields').show();
        } else {
            $('#late_payment_fees_fields').hide();
        }
    });

    // Handle discount amount changes
    // Total discount is now calculated automatically from item discounts
    // No need for input event listener since it's readonly

    // Period lock check function
    function checkPeriodLock(date, onResult) {
        if (!date) return;
        $.ajax({
            url: '{{ route('settings.period-closing.check-date') }}',
            method: 'GET',
            data: { date: date },
            success: function(response) {
                if (typeof onResult === 'function') onResult(response);
            },
            error: function() {
                console.error('Failed to check period lock status.');
            }
        });
    }

    // Warn on date change
    $('.transaction-date').on('change', function() {
        const date = $(this).val();
        checkPeriodLock(date, function(response) {
            if (response.locked) {
                Swal.fire({
                    title: 'Locked Period',
                    text: response.message || 'The selected period is locked. Please choose another date.',
                    icon: 'warning'
                });
            }
        });
    });

    // Form submission
    $('#invoice-form').submit(function(e) {
            e.preventDefault();

        if ($('#items-tbody tr').length === 0) {
            Swal.fire('Error', 'Please add at least one item to the invoice', 'error');
            return;
        }

        const form = this;
        const date = $('.transaction-date').val();

        // Check period lock before submitting
        if (date) {
            checkPeriodLock(date, function(response) {
                if (response.locked) {
                    Swal.fire({
                        title: 'Locked Period',
                        text: response.message || 'The selected period is locked. Please choose another date.',
                        icon: 'error'
                    });
                    return;
                }

                // Period is not locked, proceed with form submission
                const formData = new FormData(form);
                const submitBtn = $('#submit-btn');

                // Debug: Log form data
                console.log('Form data being sent:');
                console.log('Form action:', form.action);
                console.log('Form method:', form.method);
                console.log('Items count:', $('#items-tbody tr').length);

                for (let [key, value] of formData.entries()) {
                    console.log(key + ': ' + value);
                }

                submitBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i>Creating...');

                $.ajax({
                    url: '{{ route("sales.invoices.store") }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('Success response:', response);
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                if (response.redirect_url) {
                                    window.location.href = response.redirect_url;
                                } else {
                                    window.location.href = '{{ route("sales.invoices.index") }}';
                                }
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        console.log('Error response:', xhr);
                        console.log('Status:', xhr.status);
                        console.log('Response:', xhr.responseText);

                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON?.errors;
                            console.log('Validation errors:', errors);
                            displayValidationErrors(errors);
                            Swal.fire('Validation Error', 'Please check the form for errors', 'error');
                        } else if (xhr.status === 500) {
                            console.error('Server error:', xhr.responseText);
                            Swal.fire('Server Error', 'An internal server error occurred. Please try again later.', 'error');
                        } else {
                            console.error('Unexpected error:', xhr.status, xhr.responseText);
                            Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
                        }
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Create Invoice');
                    }
                });
            });
        } else {
            // No date selected, proceed with normal submission
            const formData = new FormData(form);
            const submitBtn = $('#submit-btn');

            submitBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i>Creating...');

            $.ajax({
                url: '{{ route("sales.invoices.store") }}',
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
                            if (response.redirect_url) {
                                window.location.href = response.redirect_url;
                            } else {
                                window.location.href = '{{ route("sales.invoices.index") }}';
                            }
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON?.errors;
                        displayValidationErrors(errors);
                        Swal.fire('Validation Error', 'Please check the form for errors', 'error');
                    } else {
                        Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
                    }
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Create Invoice');
                }
            });
        }
});

function updateDueDate() {
        const invoiceDate = $('#invoice_date').val();
        const paymentDays = parseInt($('#payment_days').val()) || 0;

    if (invoiceDate) {
            const dueDate = new Date(invoiceDate);
        dueDate.setDate(dueDate.getDate() + paymentDays);
        $('#due_date').val(dueDate.toISOString().split('T')[0]);
    }
}

    function resetModalForm() {
    $('#modal_item_id').val('').trigger('change');
    $('#modal_quantity').val(1);
    $('#modal_unit_price').val('');
    $('#modal_price_tier').val('retail');
    $('#modal_price_tier_row').hide();
    $('#modal_vat_rate').val(0);
    $('#modal_vat_type').val('no_vat');
    $('#modal_notes').val('');
    }

    // VAT defaults behavior: No VAT => 0, Inclusive/Exclusive => default 18 (if empty/0)
    $('#modal_vat_type').on('change', function () {
        const vatType = $(this).val();
        const currentRate = parseFloat($('#modal_vat_rate').val());
        const hasRate = !Number.isNaN(currentRate) && currentRate > 0;

        if (vatType === 'no_vat') {
            $('#modal_vat_rate').val(0);
        } else if (!hasRate) {
            $('#modal_vat_rate').val(18);
        }

        calculateModalLineTotal();
    });

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
            // VAT inclusive
            vatAmount = subtotal * (vatRate / (100 + vatRate));
            lineTotal = subtotal;
        }

        // Update modal display
        $('#modal-line-total').text(lineTotal.toFixed(2));
    }

function addItemToTable() {
        const itemId = $('#modal_item_id').val();
        const itemName = $('#modal_item_id option:selected').text();
        const quantity = parseFloat($('#modal_quantity').val()) || 0;
        const unitPrice = parseFloat($('#modal_unit_price').val()) || 0;
        const vatRate = parseFloat($('#modal_vat_rate').val()) || 0;
        const vatType = $('#modal_vat_type').val();
        const notes = $('#modal_notes').val();
        const selectedOption = $('#modal_item_id option:selected');
        const priceTier = ($('#modal_price_tier_row').is(':visible'))
            ? ($('#modal_price_tier').val() || 'retail')
            : 'retail';
        const lineKey = itemId + '_' + priceTier;

        if (!itemId || quantity <= 0 || unitPrice <= 0) {
            Swal.fire('Error', 'Please fill in all required fields correctly', 'error');
            return;
        }

        // Check if item already exists in the table (same item + same price tier)
        const existingRow = $(`tr[data-line-key="${lineKey}"]`);
        if (existingRow.length > 0) {
            // Item exists, update quantity instead of adding new row
            const currentQuantity = parseFloat(existingRow.find('.item-quantity').val()) || 0;
            const newQuantity = currentQuantity + quantity;

            // Check stock availability for total quantity
            const availableStock = selectedOption.data('stock');
            const itemType = selectedOption.data('item-type');
            const trackStock = selectedOption.data('track-stock') === 'true';

            // Only check stock for products that track stock
            if (itemType === 'product' && trackStock && newQuantity > availableStock) {
                Swal.fire('Error', `Insufficient stock for ${selectedOption.data('name')}. Available: ${availableStock}, Total Requested: ${newQuantity}`, 'error');
                return;
            }

            // Update the existing row
            existingRow.find('.item-quantity').val(newQuantity);

            // Recalculate totals for the updated row
            let updatedUnitPrice = parseFloat(existingRow.find('.item-price').val()) || 0;

            // If price was manually edited, update the original price reference
            const $priceInput = existingRow.find('.item-price');
            if (!$priceInput.data('original-price')) {
                // Try to get from item data or use current value as base
                const itemId = existingRow.find('input[name*="[inventory_item_id]"]').val();
                if (itemId) {
                    const itemOption = $('#modal_item_id').find(`option[value="${itemId}"]`);
                    if (itemOption.length) {
                        const rowTier = existingRow.find('input[name*="[price_tier]"]').val() || 'retail';
                        const basePrice = rowTier === 'wholesale'
                            ? (parseFloat(itemOption.attr('data-wholesale-price')) || updatedUnitPrice)
                            : (parseFloat(itemOption.data('price')) || updatedUnitPrice);
                        $priceInput.data('original-price', basePrice);
                    }
                }
            }
            const updatedVatRate = parseFloat(existingRow.find('input[name*="[vat_rate]"]').val()) || 0;
            const updatedVatType = existingRow.find('input[name*="[vat_type]"]').val();

            let updatedSubtotal = newQuantity * updatedUnitPrice;
            let updatedVatAmount = 0;
            let updatedLineTotal = 0;

            if (updatedVatType === 'no_vat') {
                updatedVatAmount = 0;
                updatedLineTotal = updatedSubtotal;
            } else if (updatedVatType === 'exclusive') {
                updatedVatAmount = updatedSubtotal * (updatedVatRate / 100);
                updatedLineTotal = updatedSubtotal + updatedVatAmount;
            } else {
                // VAT inclusive
                updatedVatAmount = updatedSubtotal * (updatedVatRate / (100 + updatedVatRate));
                updatedLineTotal = updatedSubtotal;
            }

            // Update the line total display
            existingRow.find('.line-total').text(updatedLineTotal.toFixed(2));
            existingRow.find('input[name*="[line_total]"]').val(updatedLineTotal);
            existingRow.find('input[name*="[vat_amount]"]').val(updatedVatAmount);

            // Update VAT display
            const updatedVatDisplay = updatedVatType === 'no_vat' ? 'No VAT' : ((updatedVatType === 'exclusive' ? 'Exclusive' : 'Inclusive') + ' (' + updatedVatRate + '%) - ' + updatedVatAmount.toFixed(2));
            existingRow.find('small.text-muted').text(updatedVatDisplay);

            // Recalculate invoice totals
            calculateTotals();

            // Clear modal and hide it
            $('#modal_item_id').val('').trigger('change');
            $('#modal_quantity').val('');
            $('#modal_unit_price').val('');
            $('#modal_vat_rate').val(0);
            $('#modal_vat_type').val('no_vat');
            $('#modal_notes').val('');
            $('#itemModal').modal('hide');
            return;
        }

        // Check stock availability for new item (selectedOption already set above)
        const availableStock = selectedOption.data('stock');
        const itemType = selectedOption.data('item-type');
        const trackStock = selectedOption.data('track-stock') === 'true';

        // Only check stock for products that track stock
        if (itemType === 'product' && trackStock && quantity > availableStock) {
            Swal.fire('Error', `Insufficient stock for ${selectedOption.data('name')}. Available: ${availableStock}, Requested: ${quantity}`, 'error');
            return;
        }

        // Get original price from item data or stored value
        const originalPrice = $('#modal_unit_price').data('original-price') || parseFloat(selectedOption.data('price')) || unitPrice;

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
            // VAT inclusive
            vatAmount = subtotal * (vatRate / (100 + vatRate));
            lineTotal = subtotal;
        }

        const vatDisplay = vatType === 'no_vat' ? 'No VAT' : ((vatType === 'exclusive' ? 'Exclusive' : 'Inclusive') + ' (' + vatRate + '%) - ' + vatAmount.toFixed(2));

        const tierLabel = priceTier === 'wholesale' ? ' <span class="badge bg-secondary">Wholesale</span>' : '';
        const row = `
            <tr data-item-id="${itemId}" data-line-key="${lineKey}">
                <td>
                    <input type="hidden" name="items[${itemCounter}][inventory_item_id]" value="${itemId}">
                    <input type="hidden" name="items[${itemCounter}][price_tier]" value="${priceTier}">
                    <input type="hidden" name="items[${itemCounter}][item_name]" value="${itemName}">
                    <input type="hidden" name="items[${itemCounter}][vat_type]" value="${vatType}">
                    <input type="hidden" name="items[${itemCounter}][vat_rate]" value="${vatRate}">
                    <input type="hidden" name="items[${itemCounter}][discount_type]" value="percentage">
                    <input type="hidden" name="items[${itemCounter}][discount_rate]" value="0">
                    <input type="hidden" name="items[${itemCounter}][notes]" value="${notes}">
                    <input type="hidden" name="items[${itemCounter}][line_total]" value="${lineTotal}">
                    <input type="hidden" name="items[${itemCounter}][vat_amount]" value="${vatAmount}">
                    <div class="fw-bold">${itemName}${tierLabel}</div>
                    <small class="text-muted">${notes || ''}</small>
                </td>
                <td>
                    <input type="number" class="form-control item-quantity"
                           name="items[${itemCounter}][quantity]" value="${quantity}"
                           step="0.01" min="0.01" data-row="${itemCounter}">
                </td>
                <td>
                    <input type="number" class="form-control item-price"
                           name="items[${itemCounter}][unit_price]" value="${unitPrice.toFixed(2)}"
                           step="0.01" min="0" data-row="${itemCounter}"
                           data-original-price="${originalPrice}"
                           data-original-currency="${functionalCurrency}"
                           ${getCurrentInvoiceCurrency() !== functionalCurrency ? `title="Converted from ${originalPrice.toFixed(2)} ${functionalCurrency}"` : ''}>
                </td>
                <td>
                    <small class="text-muted">${vatDisplay}</small>
                </td>
                <td>
                    <span class="line-total">${lineTotal.toFixed(2)}</span>
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
        itemCounter++;
        calculateTotals();
    }

    function updateRowTotal(row) {
        const quantity = parseFloat($(`input[name="items[${row}][quantity]"]`).val()) || 0;
        const unitPrice = parseFloat($(`input[name="items[${row}][unit_price]"]`).val()) || 0;
        const itemVatType = $(`input[name="items[${row}][vat_type]"]`).val();
        const itemVatRate = parseFloat($(this).find('input[name*="[vat_rate]"]').val()) || 0;

        let subtotal = quantity * unitPrice;
        let vatAmount = 0;
        let lineTotal = 0;

        if (itemVatType === 'no_vat') {
            vatAmount = 0;
            lineTotal = subtotal;
        } else if (itemVatType === 'exclusive') {
            vatAmount = subtotal * (itemVatRate / 100);
            lineTotal = subtotal + vatAmount;
        } else {
            vatAmount = subtotal * (itemVatRate / (100 + itemVatRate));
            lineTotal = subtotal;
        }

        $(`.item-total`).eq(row).text(lineTotal.toFixed(2));
    }

function calculateTotals() {
        let subtotal = 0;
        let vatAmount = 0;

        $('#items-tbody tr').each(function() {
            const quantity = parseFloat($(this).find('.item-quantity').val()) || 0;
            const unitPrice = parseFloat($(this).find('.item-price').val()) || 0;
            const itemVatType = $(this).find('input[name*="[vat_type]"]').val();
            const itemVatRate = parseFloat($(this).find('input[name*="[vat_rate]"]').val()) || 0;

            const rowSubtotal = quantity * unitPrice;
            let rowVatAmount = 0;
            let rowNetAmount = 0; // Net amount without VAT

            if (itemVatType === 'no_vat') {
                rowVatAmount = 0;
                rowNetAmount = rowSubtotal;
            } else if (itemVatType === 'exclusive') {
                rowVatAmount = rowSubtotal * (itemVatRate / 100);
                rowNetAmount = rowSubtotal; // For exclusive, unit price is already net
            } else {
                // For inclusive VAT, extract VAT to get net amount
                rowVatAmount = rowSubtotal * (itemVatRate / (100 + itemVatRate));
                rowNetAmount = rowSubtotal - rowVatAmount; // Net amount = gross - VAT
            }

            subtotal += rowNetAmount; // Add net amount to subtotal
            vatAmount += rowVatAmount;
        });

        // Calculate withholding tax
        // WHT is NOT calculated at invoice creation - it's only applied at payment/receipt time
        const withholdingTaxEnabled = false;
        const withholdingTaxRate = 0;
        const withholdingTaxType = 'percentage';
        let withholdingTaxAmount = 0;

        // Calculate total discount (from invoice-level discount)
        const totalDiscount = parseFloat($('#discount_amount').val()) || 0;

        // Calculate final total using line totals (handles all VAT types correctly)
        let lineTotalSum = 0;
        $('#items-tbody tr').each(function() {
            const lineTotal = parseFloat($(this).find('.line-total').text()) || 0;
            lineTotalSum += lineTotal;
        });

        // WHT is NOT deducted from invoice total - it's handled at payment time
        const totalAmount = lineTotalSum - totalDiscount;

        // Update displays
        $('#subtotal').text(subtotal.toFixed(2));
        $('#subtotal-input').val(subtotal.toFixed(2));

        if (vatAmount > 0) {
            $('#vat-row').show();
            $('#vat-amount').text(vatAmount.toFixed(2));
            $('#vat-amount-input').val(vatAmount.toFixed(2));
        } else {
            $('#vat-row').hide();
        }

        // WHT row is always hidden at invoice creation - WHT only at payment time
        $('#withholding-tax-row').hide();
        $('#withholding-tax-amount').text('0.00');
        $('#withholding-tax-amount-input').val('0');

        $('#total-amount').text(totalAmount.toFixed(2));
        $('#total-amount-input').val(totalAmount.toFixed(2));

        // Update buttons based on current item count
        updateButtonsBasedOnItemCount();
    }

    // --- CSV Export / Import (large item count handling) ---

    // Function to check item count and show/hide buttons
    function updateButtonsBasedOnItemCount() {
        const itemCount = $('#items-tbody tr').length;
        if (itemCount > 30) {
            $('#submit-btn').hide();
            $('#export-csv-btn').show();
            $('#import-csv-btn').show();
        } else {
            $('#submit-btn').show();
            $('#export-csv-btn').hide();
            $('#import-csv-btn').hide();
        }
    }

    // Check item count on page load
    updateButtonsBasedOnItemCount();

    // Export CSV button handler
    $('#export-csv-btn').click(function() {
        exportItemsToCsv();
    });

    // Import CSV button - opens import page in new tab with current form data
    $('#import-csv-btn').on('click', function(e) {
        e.preventDefault();

        // Collect current form values from the create page
        const customerId = $('select[name="customer_id"]').val();
        const invoiceDate = $('input[name="invoice_date"]').val();
        const dueDate = $('input[name="due_date"]').val();
        const paymentTerms = $('#payment_terms').val();
        const paymentDays = $('#payment_days').val();
        const currency = $('#currency').val();
        const exchangeRate = $('#exchange_rate').val();
        const notes = $('#notes').val();
        const termsConditions = $('#terms_conditions').val();

        // Build URL with query parameters
        const params = new URLSearchParams();
        if (customerId) params.append('customer_id', customerId);
        if (invoiceDate) params.append('invoice_date', invoiceDate);
        if (dueDate) params.append('due_date', dueDate);
        if (paymentTerms) params.append('payment_terms', paymentTerms);
        if (paymentDays) params.append('payment_days', paymentDays);
        if (currency) params.append('currency', currency);
        if (exchangeRate) params.append('exchange_rate', exchangeRate);
        if (notes) params.append('notes', encodeURIComponent(notes));
        if (termsConditions) params.append('terms_conditions', encodeURIComponent(termsConditions));

        const importUrl = '{{ route("sales.invoices.import") }}' + (params.toString() ? '?' + params.toString() : '');

        // Open in new tab
        window.open(importUrl, '_blank');
    });

    // Export current items to CSV compatible with ImportSalesInvoiceItemsJob
    function exportItemsToCsv() {
        const items = [];
        $('#items-tbody tr').each(function() {
            const $row = $(this);
            const inventoryItemId = $row.find('input[name*="[inventory_item_id]"]').val();
            // Extract item name - try hidden input first, then strong/fw-bold element
            let itemName = $row.find('input[name*="[item_name]"]').val();
            if (!itemName) {
                const $nameEl = $row.find('td:first strong, td:first .fw-bold');
                if ($nameEl.length) {
                    itemName = $nameEl.first().text().trim();
                }
            }
            itemName = itemName || '';
            const quantity = $row.find('.item-quantity').val();
            const unitPrice = $row.find('.item-price').val();
            const vatType = $row.find('input[name*="[vat_type]"]').val();
            const vatRate = $row.find('input[name*="[vat_rate]"]').val();
            const discountType = $row.find('input[name*="[discount_type]"]').val() || '';
            const discountRate = $row.find('input[name*="[discount_rate]"]').val() || '';
            const notes = $row.find('input[name*="[notes]"]').val() || '';

            items.push({
                inventory_item_id: inventoryItemId,
                item_name: itemName,
                quantity: quantity,
                unit_price: unitPrice,
                vat_type: vatType,
                vat_rate: vatRate,
                discount_type: discountType,
                discount_rate: discountRate,
                notes: notes
            });
        });

        if (items.length === 0) {
            Swal.fire('Info', 'No items to export.', 'info');
            return;
        }

        // Build CSV headers and rows
        const headers = [
            'Inventory Item ID',
            'Item Name',
            'Quantity',
            'Unit Price',
            'VAT Type',
            'VAT Rate',
            'Discount Type',
            'Discount Rate',
            'Notes'
        ];

        let csvContent = headers.join(',') + '\n';
        items.forEach(item => {
            const row = [
                item.inventory_item_id || '',
                `"${(item.item_name || '').replace(/"/g, '""')}"`,
                item.quantity || '',
                item.unit_price || '',
                item.vat_type || '',
                item.vat_rate || '',
                item.discount_type || '',
                item.discount_rate || '',
                `"${(item.notes || '').replace(/"/g, '""')}"`,
            ];
            csvContent += row.join(',') + '\n';
        });

        // Trigger download
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        const now = new Date();
        const timestamp = now.toISOString().slice(0,19).replace(/[:T]/g,'-');
        link.download = `sales-invoice-items-${timestamp}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    function displayValidationErrors(errors) {
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        // Check if errors object exists and has properties
        if (!errors || typeof errors !== 'object') {
            console.log('No validation errors to display or invalid errors object:', errors);
            return;
        }

        // Display new errors
        Object.keys(errors).forEach(field => {
            const input = $(`[name="${field}"]`);
            if (input.length) {
                input.addClass('is-invalid');
                input.siblings('.invalid-feedback').text(errors[field][0]);
            }
        });
    }

    // Handle currency change - Use Select2 event for proper handling
    $('#currency').on('select2:select', function(e) {
        const selectedCurrency = $(this).val();
        handleCurrencyChange(selectedCurrency);
    }).on('change', function() {
        // Fallback for non-Select2 scenarios
        const selectedCurrency = $(this).val();
        handleCurrencyChange(selectedCurrency);
    });

    function handleCurrencyChange(selectedCurrency) {
        if (selectedCurrency && selectedCurrency !== functionalCurrency) {
            $('#exchange_rate').prop('required', true);
            // Auto-fetch exchange rate when currency changes (use invoice date if available)
            const invoiceDate = $('#invoice_date').val();
            fetchExchangeRate(selectedCurrency, invoiceDate);
        } else {
            $('#exchange_rate').prop('required', false);
            $('#exchange_rate').val('1.000000');
            $('#rate-info').hide();
        }

        // Convert all existing item prices when currency changes
        convertAllItemPrices();
    }

    // Function to convert all item prices in the table when currency/exchange rate changes
    function convertAllItemPrices() {
        const invoiceCurrency = getCurrentInvoiceCurrency();
        const exchangeRate = getCurrentExchangeRate();

        // Convert prices in existing rows
        $('input.item-price').each(function() {
            const $priceInput = $(this);
            const originalPrice = $priceInput.data('original-price');

            // If original price is stored, use it; otherwise use current value as base
            const basePrice = originalPrice || parseFloat($priceInput.val()) || 0;

            if (basePrice > 0) {
                const convertedPrice = convertItemPrice(basePrice, invoiceCurrency, exchangeRate);
                $priceInput.val(convertedPrice.toFixed(2));

                // Store original price if not already stored
                if (!originalPrice) {
                    $priceInput.data('original-price', basePrice);
                    $priceInput.data('original-currency', functionalCurrency);
                }

                // Update tooltip
                if (invoiceCurrency !== functionalCurrency && exchangeRate !== 1) {
                    $priceInput.attr('title', `Converted from ${basePrice.toFixed(2)} ${functionalCurrency} at rate ${exchangeRate}`);
                } else {
                    $priceInput.removeAttr('title');
                }

                // Recalculate row total
                const row = $priceInput.data('row');
                if (row) {
                    updateRowTotal(row);
                }
            }
        });

        // Convert price in modal if item is selected (respect retail vs wholesale)
        if ($('#modal_item_id').val()) {
            applyModalPriceForInvoiceTier();
        }

        // Recalculate invoice totals
        calculateInvoiceTotals();
    }

    // Convert prices when exchange rate changes
    $('#exchange_rate').on('input change', function() {
        // Only convert if currency is not functional currency
        const invoiceCurrency = getCurrentInvoiceCurrency();
        if (invoiceCurrency !== functionalCurrency) {
            convertAllItemPrices();
        }
    });

    // Fetch exchange rate button
    $('#fetch-rate-btn').on('click', function() {
        const currency = $('#currency').val();
        fetchExchangeRate(currency);
    });

    // Function to fetch exchange rate from FX RATES MANAGEMENT
    function fetchExchangeRate(currency = null, invoiceDate = null) {
        currency = currency || $('#currency').val();
        invoiceDate = invoiceDate || $('#invoice_date').val() || new Date().toISOString().split('T')[0];

        if (!currency || currency === functionalCurrency) {
            $('#exchange_rate').val('1.000000');
            $('#rate-info').hide();
            return;
        }

        const btn = $('#fetch-rate-btn');
        const originalHtml = btn.html();
        const rateInput = $('#exchange_rate');

        // Show loading state
        btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i>');
        rateInput.prop('disabled', true);

        // Use the FX rates API endpoint with invoice date
        $.ajax({
            url: '{{ route("accounting.fx-rates.get-rate") }}',
            method: 'GET',
            data: {
                from_currency: currency,
                to_currency: functionalCurrency,
                date: invoiceDate, // Use invoice date instead of today
                rate_type: 'spot'
            },
            success: function(response) {
                if (response.success && response.rate) {
                    const rate = parseFloat(response.rate);
                    rateInput.val(rate.toFixed(6));
                    const source = response.source || 'FX RATES MANAGEMENT';
                    $('#rate-source').text(`Rate from ${source} for ${invoiceDate}: 1 ${currency} = ${rate.toFixed(6)} ${functionalCurrency}`);
                    $('#rate-info').show();

                    // Show success notification (optional, less intrusive)
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
                } else {
                    console.warn('Exchange rate API returned unexpected format:', response);
                    // Try fallback
                    fetchExchangeRateFallback(currency, invoiceDate);
                }
            },
            error: function(xhr) {
                console.error('Failed to fetch exchange rate:', xhr);
                fetchExchangeRateFallback(currency, invoiceDate);
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
                rateInput.prop('disabled', false);
            }
        });
    }

    // Fallback function to fetch rate from API if FX RATES MANAGEMENT doesn't have it
    function fetchExchangeRateFallback(currency, invoiceDate) {
        const rateInput = $('#exchange_rate');
        $.get('{{ route("api.exchange-rates.rate") }}', {
            from: currency,
            to: functionalCurrency
        })
        .done(function(response) {
            if (response.success && response.data && response.data.rate) {
                const rate = parseFloat(response.data.rate);
                rateInput.val(rate.toFixed(6));
                $('#rate-source').text(`Rate fetched (fallback API) for ${invoiceDate}: 1 ${currency} = ${rate.toFixed(6)} ${functionalCurrency}`);
                $('#rate-info').show();
            }
        })
        .fail(function() {
            Swal.fire({
                icon: 'warning',
                title: 'Rate Fetch Failed',
                text: 'Please manually enter the exchange rate or add it to FX RATES MANAGEMENT.',
                timer: 3000,
                showConfirmButton: false
            });
        });
    }

    // Auto-fetch exchange rate when invoice date changes
    $('#invoice_date').on('change', function() {
        const currency = $('#currency').val();
        const invoiceDate = $(this).val();
        if (currency && currency !== functionalCurrency && invoiceDate) {
            fetchExchangeRate(currency, invoiceDate);
        }
    });

    // Handle sales order selection
    $('#sales_order_id').on('change', function() {
        const orderId = $(this).val();

        if (!orderId) {
            // Clear form if no order selected
            clearForm();
            return;
        }

        // Show loading state
        $(this).prop('disabled', true);
        $('#order-loading').show();

        // Fetch sales order details
        $.ajax({
            url: `{{ route('sales.invoices.sales-order-details', ':orderId') }}`.replace(':orderId', orderId),
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    populateFormFromOrder(response.order);
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function(xhr) {
                Swal.fire('Error', 'Failed to fetch sales order details', 'error');
            },
            complete: function() {
                $('#sales_order_id').prop('disabled', false);
                $('#order-loading').hide();
            }
        });
    });

    function populateFormFromOrder(order) {
        // Populate customer
        $('#customer_id').val(order.customer.id).trigger('change');

        // Populate payment terms
        $('#payment_terms').val(order.payment_terms);
        $('#payment_days').val(order.payment_days);

        // Populate notes and terms
        $('#notes').val(order.notes);
        $('#terms_conditions').val(order.terms_conditions);

        // Clear existing items
        $('#items-tbody').empty();
        itemCounter = 0;

        // Add items from sales order
        order.items.forEach(function(item) {
            addItemFromOrder(item);
        });

        // Update totals
        calculateTotals();

        // Show success message
        Swal.fire({
            title: 'Success!',
            text: `Sales order "${order.order_number}" details loaded successfully`,
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    }

    function addItemFromOrder(item) {
        itemCounter++;

        const row = `
            <tr data-row-id="${itemCounter}">
                <td>
                    <strong>${item.item_name}</strong><br>
                    <small class="text-muted">${item.item_code}</small>
                    <input type="hidden" name="items[${itemCounter}][inventory_item_id]" value="${item.inventory_item_id}">
                    <input type="hidden" name="items[${itemCounter}][vat_type]" value="${item.vat_type}">
                    <input type="hidden" name="items[${itemCounter}][vat_rate]" value="${item.vat_rate}">
                    <input type="hidden" name="items[${itemCounter}][vat_amount]" value="${item.vat_amount}">
                    <input type="hidden" name="items[${itemCounter}][discount_type]" value="${item.discount_type}">
                    <input type="hidden" name="items[${itemCounter}][discount_rate]" value="${item.discount_rate}">
                    <input type="hidden" name="items[${itemCounter}][discount_amount]" value="${item.discount_amount}">
                    <input type="hidden" name="items[${itemCounter}][line_total]" value="${item.line_total}">
                    <input type="hidden" name="items[${itemCounter}][notes]" value="${item.notes || ''}">
                </td>
                <td>
                    <input type="number" class="form-control item-quantity"
                           name="items[${itemCounter}][quantity]" value="${item.quantity}"
                           step="0.01" min="0.01" data-row="${itemCounter}">
                </td>
                <td>
                    <input type="number" class="form-control item-price"
                           name="items[${itemCounter}][unit_price]" value="${item.unit_price}"
                           step="0.01" min="0" data-row="${itemCounter}">
                </td>
                <td>
                    <span class="form-control-plaintext">${item.vat_type === 'no_vat' ? 'No VAT' : item.vat_rate + '% (' + item.vat_type.replace('_', ' ') + ')'}</span>
                </td>
                <td>
                    <span class="line-total">${parseFloat(item.line_total).toFixed(2)}</span>
                </td>
                <td>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-item">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#items-tbody').append(row);
    }

    function populateFormFromCopyInvoice(data) {
        if (!data || !data.items || !data.items.length) return;
        $('#customer_id').val(data.customer.id).trigger('change');
        $('#invoice_date').val(data.invoice_date || '');
        $('#due_date').val(data.due_date || '');
        $('#payment_terms').val(data.payment_terms || 'net_30');
        $('#payment_days').val(data.payment_days ?? 30);
        $('#discount_amount').val(data.discount_amount != null ? data.discount_amount : 0);
        $('#notes').val(data.notes || '');
        $('#terms_conditions').val(data.terms_conditions || '');
        $('#items-tbody').empty();
        itemCounter = 0;
        data.items.forEach(function(item) {
            addItemFromOrder(item);
        });
        calculateTotals();
    }

    function clearForm() {
        // Clear customer
        $('#customer_id').val('').trigger('change');

        // Reset payment terms to defaults
        $('#payment_terms').val('net_30');
        $('#payment_days').val('30');

        // Clear notes and terms
        $('#notes').val('');
        $('#terms_conditions').val('');

        // Clear items
        $('#items-tbody').empty();
        itemCounter = 0;

        // Reset totals
        calculateTotals();
    }

    let itemCounter = 0;

    // Customer modal functions
    function resetCustomerForm() {
        try {
            if ($('#customer-form').length > 0) {
                $('#customer-form')[0].reset();
            }
            if ($('#customer_status').length > 0) {
                $('#customer_status').val('active');
            }
            $('.invalid-feedback').text('');
            $('.form-control').removeClass('is-invalid');
        } catch (error) {
            console.error('Error resetting customer form:', error);
        }
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
        if (!name) { $('#customer_name').addClass('is-invalid').siblings('.invalid-feedback').text('Customer name is required'); return; }
        if (!phone) { $('#customer_phone').addClass('is-invalid').siblings('.invalid-feedback').text('Phone number is required'); return; }

        const payload = {
            name: name,
            phone: phone,
            email: email,
            status: status,
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
            const label = (res?.customer?.name || 'Customer') + (res?.customer?.phone ? (' - ' + res.customer.phone) : '');
            if (id) {
                const newOption = new Option(label, id, true, true);
                $('#customer_id').append(newOption).trigger('change');
            }
            $('#customerModal').modal('hide');
            Swal.fire('Success','Customer created','success');
        }).fail(function(xhr){
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                const errors = xhr.responseJSON.errors;
                const list = Object.values(errors).flat().map(e=>`<div>${e}</div>`).join('');
                $('#add-customer-errors').removeClass('d-none').html(list);
                Object.keys(errors).forEach(field => {
                    const input = $(`#customer_${field}`);
                    if (input.length) { input.addClass('is-invalid').siblings('.invalid-feedback').text(errors[field][0]); }
                });
            } else {
                $('#add-customer-errors').removeClass('d-none').text((xhr.responseJSON && xhr.responseJSON.message) || 'Failed to create customer');
            }
        }).always(function(){
            btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Customer');
        });
    }

    function fetchNewCustomer(name, phone) {
        // Search for the newly created customer
        $.ajax({
            url: '{{ route("customers.search") }}',
            type: 'GET',
            data: { term: name },
            success: function(customers) {
                if (customers && customers.length > 0) {
                    // Find the customer that matches the name and phone
                    const newCustomer = customers.find(c => c.name === name && c.phone === phone);
                    if (newCustomer) {
                        // Add the new customer to the dropdown
                        const option = new Option(`${newCustomer.name} - ${newCustomer.phone}`, newCustomer.id, true, true);
                        $('#customer_id').append(option).trigger('change');

                        // Close modal and show success message
                        $('#customerModal').modal('hide');
                        Swal.fire({
                            title: 'Success!',
                            text: 'Customer created and selected successfully',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        // Fallback: refresh the page to get updated customer list
                        location.reload();
                    }
                } else {
                    // Fallback: refresh the page to get updated customer list
                    location.reload();
                }
            },
            error: function() {
                // Fallback: refresh the page to get updated customer list
                location.reload();
            }
        });
    }

    // Prefill form when copying from another invoice
    if (typeof copyFromInvoice !== 'undefined' && copyFromInvoice && copyFromInvoice.items && copyFromInvoice.items.length) {
        setTimeout(function() { populateFormFromCopyInvoice(copyFromInvoice); }, 100);
    }
});
</script>
@endpush
@endsection
