@extends('layouts.main')

@section('title', 'Edit Sales Invoice')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-store'],
            ['label' => 'Sales Invoices', 'url' => route('sales.invoices.index'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Edit Invoice', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
                                <h6 class="mb-0 text-uppercase">EDIT SALES INVOICE</h6>
        <hr />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-receipt me-2"></i>New Sales Invoice</h5>
                    </div>
                    <div class="card-body">
                <form id="invoice-form" method="POST" action="{{ route('sales.invoices.update', $invoice->encoded_id) }}" enctype="multipart/form-data">
                    @method('PUT')
                            @csrf
                    <input type="hidden" name="withholding_tax_rate" id="withholding_tax_rate_input" value="0">
                    <input type="hidden" name="withholding_tax_type" id="withholding_tax_type_input" value="percentage">
                            <div class="row">
                        <!-- Customer Information -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                <select class="form-select select2-single" id="customer_id" name="customer_id" required>
                                            <option value="">Select Customer</option>
                                            @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ old('customer_id', $invoice->customer_id) == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }} - {{ $customer->phone }}
                                            </option>
                                            @endforeach
                                        </select>
                                <div class="invalid-feedback"></div>
                                    </div>
                                </div>

                        <div class="col-md-3">
                                    <div class="mb-3">
                                <label for="invoice_date" class="form-label">Invoice Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control transaction-date" id="invoice_date" name="invoice_date"
                                       value="{{ old('invoice_date', $invoice->invoice_date->format('Y-m-d')) }}" required>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="due_date" name="due_date"
                                       value="{{ old('due_date', $invoice->due_date->format('Y-m-d')) }}" required>
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
                                                <option value="{{ $order->id }}" {{ old('sales_order_id', $invoice->sales_order_id) == $order->id ? 'selected' : '' }}>{{ $order->order_number }} - {{ $order->customer->name }}</option>
                                            @endforeach
                                        </select>
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
                                                            {{ old('currency', $invoice->currency ?? $functionalCurrency) == $currency->currency_code ? 'selected' : '' }}>
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
                                            <input type="number" class="form-control" id="exchange_rate" name="exchange_rate" value="{{ old('exchange_rate', number_format($invoice->exchange_rate ?? 1, 6, '.', '')) }}" step="0.000001" min="0.000001">
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
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="reference_no" class="form-label">Reference No.</label>
                                        <input type="text" class="form-control" id="reference_no" name="reference_no"
                                               value="{{ old('reference_no', $invoice->reference_no) }}" placeholder="e.g. PO-12345">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                <label for="payment_terms" class="form-label">Payment Terms <span class="text-danger">*</span></label>
                                        <select class="form-select" id="payment_terms" name="payment_terms" required>
                                            <option value="immediate" {{ old('payment_terms', $invoice->payment_terms) == 'immediate' ? 'selected' : '' }}>Immediate</option>
                                            <option value="net_15" {{ old('payment_terms', $invoice->payment_terms) == 'net_15' ? 'selected' : '' }}>Net 15</option>
                                    <option value="net_30" {{ old('payment_terms', $invoice->payment_terms) == 'net_30' ? 'selected' : '' }}>Net 30</option>
                                            <option value="net_45" {{ old('payment_terms', $invoice->payment_terms) == 'net_45' ? 'selected' : '' }}>Net 45</option>
                                            <option value="net_60" {{ old('payment_terms', $invoice->payment_terms) == 'net_60' ? 'selected' : '' }}>Net 60</option>
                                    <option value="custom" {{ old('payment_terms', $invoice->payment_terms) == 'custom' ? 'selected' : '' }}>Custom</option>
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
                                       value="{{ old('payment_days', $invoice->payment_days) }}" min="0" required>
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
                                            <input class="form-check-input" type="checkbox" id="early_payment_discount_enabled" name="early_payment_discount_enabled" value="1" {{ $invoice->early_payment_discount_enabled ? 'checked' : '' }}>
                                            <label class="form-check-label" for="early_payment_discount_enabled">
                                                Early payment discount
                                            </label>
                                        </div>
                                    </div>
                                    <div id="early_payment_discount_fields" style="{{ $invoice->early_payment_discount_enabled ? '' : 'display: none;' }}">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label for="early_payment_discount_type" class="form-label">Type</label>
                                                <select class="form-select" id="early_payment_discount_type" name="early_payment_discount_type">
                                                    <option value="percentage" {{ ($invoice->early_payment_discount_type ?? 'percentage') == 'percentage' ? 'selected' : '' }}>Percentage</option>
                                                    <option value="fixed" {{ ($invoice->early_payment_discount_type ?? 'percentage') == 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="early_payment_discount_rate" class="form-label">Rate/Amount</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="early_payment_discount_rate" name="early_payment_discount_rate"
                                                           value="{{ $invoice->early_payment_discount_rate ?? 0 }}" step="0.01" min="0" placeholder="0">
                                                    <span class="input-group-text" id="early-discount-unit">{{ ($invoice->early_payment_discount_type ?? 'percentage') == 'percentage' ? '%' : 'TSh' }}</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="early_payment_days" class="form-label">If paid within</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="early_payment_days" name="early_payment_days"
                                                           value="{{ $invoice->early_payment_days ?? 0 }}" min="0" placeholder="0">
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
                                            <input class="form-check-input" type="checkbox" id="late_payment_fees_enabled" name="late_payment_fees_enabled" value="1" {{ $invoice->late_payment_fees_enabled ? 'checked' : '' }}>
                                            <label class="form-check-label" for="late_payment_fees_enabled">
                                                Late payment fees
                                            </label>
                                        </div>
                                    </div>
                                    <div id="late_payment_fees_fields" style="{{ $invoice->late_payment_fees_enabled ? '' : 'display: none;' }}">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="late_payment_fees_type" class="form-label">Type</label>
                                                <select class="form-select" id="late_payment_fees_type" name="late_payment_fees_type">
                                                    <option value="monthly" {{ ($invoice->late_payment_fees_type ?? 'monthly') == 'monthly' ? 'selected' : '' }}>Charge monthly</option>
                                                    <option value="one_time" {{ ($invoice->late_payment_fees_type ?? 'monthly') == 'one_time' ? 'selected' : '' }}>One-time charge</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="late_payment_fees_rate" class="form-label">Rate</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="late_payment_fees_rate" name="late_payment_fees_rate"
                                                           value="{{ $invoice->late_payment_fees_rate ?? 0 }}" step="0.01" min="0" placeholder="0">
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
                                            <td><strong id="subtotal">{{ number_format($invoice->subtotal, 2) }}</strong></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="subtotal" id="subtotal-input" value="{{ $invoice->subtotal }}">
                                        <tr id="vat-row" style="display: none;">
                                            <td colspan="4" class="text-end"><strong>VAT Amount:</strong></td>
                                            <td><strong id="vat-amount">{{ number_format($invoice->vat_amount, 2) }}</strong></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="vat_amount" id="vat-amount-input" value="{{ $invoice->vat_amount }}">
                                        <tr id="withholding-tax-row" style="display: none;">
                                            <td colspan="4" class="text-end"><strong>Withholding Tax (<span id="withholding-tax-rate-display">0</span>%):</strong></td>
                                            <td><strong id="withholding-tax-amount">{{ number_format($invoice->withholding_tax_amount, 2) }}</strong></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="withholding_tax_amount" id="withholding-tax-amount-input" value="{{ $invoice->withholding_tax_amount }}">
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Discount:</strong></td>
                                            <td>
                                                <input type="number" class="form-control" id="discount_amount" name="discount_amount"
                                                       value="{{ $invoice->discount_amount ?? 0 }}" step="0.01" min="0" placeholder="0.00">
                                            </td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <tr class="table-info">
                                            <td colspan="4" class="text-end"><strong>Total Amount:</strong></td>
                                            <td><strong id="total-amount">{{ number_format($invoice->total_amount, 2) }}</strong></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <input type="hidden" name="total_amount" id="total-amount-input" value="{{ $invoice->total_amount }}">
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                                    </div>

                    <!-- Withholding Tax Settings -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            {{-- WHT Settings Card - Hidden because WHT is only applied at payment/receipt time --}}
                            {{-- <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Withholding Tax Settings</h6>
                                </div>
                                <div class="card-body">
                                    WHT is NOT handled at invoice creation - it's only applied at payment/receipt time
                                </div>
                            </div> --}}
                            {{-- Hidden fields to maintain form structure but always set to 0 --}}
                            <input type="hidden" name="withholding_tax_enabled" value="0">
                            <input type="hidden" id="withholding_tax_rate" name="withholding_tax_rate" value="0">
                            <input type="hidden" id="withholding_tax_type" name="withholding_tax_type" value="percentage">

                            <!-- close WHT row/col wrapper -->
                        </div>
                    </div>

                    <!-- Notes, Terms & Attachment -->
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="4"
                                    placeholder="Additional notes for this invoice...">{{ old('notes', $invoice->notes) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="terms_conditions" class="form-label">Terms & Conditions</label>
                                <textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="4"
                                    placeholder="Terms and conditions...">{{ old('terms_conditions', $invoice->terms_conditions) }}</textarea>
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
                                @if(!empty($invoice->attachment))
                                    <div class="mt-2">
                                        <a href="{{ asset('storage/' . $invoice->attachment) }}" target="_blank">
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
                        <a href="{{ route('sales.invoices.index') }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-x me-1"></i>Cancel
                                </a>
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="bx bx-check me-1" data-processing-text="Updating..."></i>Update Invoice
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
                                    data-stock="{{ $item->current_stock ?? 0 }}"
                                    data-minimum-stock="{{ $item->minimum_stock ?? 0 }}"
                                    data-unit="{{ $item->unit_of_measure }}"
                                    data-vat-rate="{{ $item->vat_rate ?? get_default_vat_rate() }}"
                                    data-vat-type="{{ $item->vat_type ?? get_default_vat_type() }}"
                                    data-item-type="{{ $item->item_type }}"
                                    data-track-stock="{{ $item->track_stock ? 'true' : 'false' }}">
                                {{ $item->name }} ({{ $item->code }}) - Price: {{ number_format($item->resolved_unit_price ?? $item->unit_price, 2) }} - Stock: {{ $item->current_stock ?? 0 }}
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="add-item-btn">Add Item</button>
            </div>
        </div>
    </div>


@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
	// Original quantities by item_id from the existing invoice
	// This is used to add back original quantities when validating stock availability
	// Declared outside document.ready so it's accessible to all functions
	const originalQuantities = @json($originalQuantities ?? []);
	
$(document).ready(function() {
    // Initialize item counter
    let itemCounter = {{ count($invoice->items) }};

    // Initialize line totals for existing items
    $('#items-tbody tr').each(function(index) {
        updateRowTotal(index);
    });

    // Calculate initial totals
    calculateTotals();

    // Initialize Select2
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Get functional currency for exchange rate calculations
    const functionalCurrency = '{{ \App\Models\SystemSetting::getValue("functional_currency", auth()->user()->company->functional_currency ?? "TZS") }}';

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
                if (row !== undefined) {
                    updateRowTotal(row);
                }
            }
        });

        // Convert price in modal if item is selected (retail vs wholesale)
        if ($('#modal_item_id').val()) {
            applyModalPriceForInvoiceTier();
        }

        // Recalculate invoice totals
        calculateTotals();
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

    // Function to fetch exchange rate from API
    function fetchExchangeRate(currency = null) {
        currency = currency || $('#currency').val();
        if (!currency || currency === functionalCurrency) {
            $('#exchange_rate').val('1.000000');
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

                    // Show success notification
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

    $('.select2-modal').select2({
        theme: 'bootstrap-5',
        width: '100%',
        dropdownParent: $('#itemModal')
    });

    // Handle customer selection change to fetch credit information
    $('#customer_id').on('change', function() {
        const customerId = $(this).val();
        if (customerId) {
            fetchCustomerCreditInfo(customerId);
        } else {
            // Hide credit info when no customer is selected
            $('#credit-info-row').hide();
        }
    });

    // Fetch credit info if customer is pre-selected
    if ($('#customer_id').val()) {
        fetchCustomerCreditInfo($('#customer_id').val());
    }

    // Function to fetch and display customer credit information
    function fetchCustomerCreditInfo(customerId) {
        if (!customerId) {
            $('#credit-info-row').hide();
            return;
        }

        // Show loading state
        $('#credit-info-card').addClass('opacity-50');
        $('#credit-limit-display').html('<i class="bx bx-loader bx-spin"></i>');
        $('#current-balance-display').html('<i class="bx bx-loader bx-spin"></i>');
        $('#available-credit-display').html('<i class="bx bx-loader bx-spin"></i>');
        $('#credit-status-badge').html('-');

        $.ajax({
            url: '{{ route("sales.invoices.customer-credit-info") }}',
            method: 'GET',
            data: { customer_id: customerId },
            success: function(response) {
                if (response.success && response.has_credit_limit) {
                    // Display credit information
                    const creditLimit = parseFloat(response.credit_limit) || 0;
                    const currentBalance = parseFloat(response.current_balance) || 0;
                    const availableCredit = parseFloat(response.available_credit) || 0;

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

                    $('#credit-info-row').fadeIn();
                } else {
                    // Customer has no credit limit, hide the card
                    $('#credit-info-row').hide();
                }
                $('#credit-info-card').removeClass('opacity-50');
            },
            error: function(xhr) {
                console.error('Error fetching credit info:', xhr);
                $('#credit-info-row').hide();
                $('#credit-info-card').removeClass('opacity-50');
            }
        });
    }

    // Add item button click
    $('#add-item').click(function() {
        $('#itemModal').modal('show');
        resetModalForm();
    });

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
            // Skip stock validation for service items or items that don't track stock
            const itemType = selectedOption.data('item-type') || 'product';
            const trackStock = selectedOption.data('track-stock') === 'true' || selectedOption.data('track-stock') === true;
            const availableStock = selectedOption.data('stock');

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

    // Validate quantity against stock - only for products that track stock
    $('#modal_quantity').on('input', function() {
        const selectedOption = $('#modal_item_id option:selected');
        if (selectedOption.val()) {
            const itemType = selectedOption.data('item-type') || 'product';
            const trackStock = selectedOption.data('track-stock') === 'true' || selectedOption.data('track-stock') === true;
            const availableStock = selectedOption.data('stock');
            const enteredQuantity = parseFloat($(this).val()) || 0;
            const allowOverride = $(this).data('allow-override') || false;

            // Only validate stock for products that track stock
            if (itemType === 'product' && trackStock && enteredQuantity > availableStock && !allowOverride) {
                $(this).addClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
                $(this).after(`<div class="invalid-feedback">Quantity cannot exceed available stock (${availableStock})</div>`);
            } else {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            }
        }
        calculateModalLineTotal();
    });

    // Calculate modal line total on input change
    $('#modal_quantity, #modal_unit_price, #modal_vat_rate').on('input', function() {
        calculateModalLineTotal();

        // Store original price if manually edited (for price conversion)
        if ($(this).attr('id') === 'modal_unit_price') {
            const selectedOption = $('#modal_item_id').find('option:selected');
            if (selectedOption.val() && !$('#modal_unit_price').data('original-price')) {
                const basePrice = parseFloat(selectedOption.data('price')) || 0;
                if (basePrice > 0) {
                    $('#modal_unit_price').data('original-price', basePrice);
                }
            }
        }
    });

    // Removed discount type change handler

    // Add item button in modal
    $('#add-item-btn').click(function() {
        addItemToTable();
    });

    // Remove item
    $(document).on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        calculateTotals();
    });

    // Recalculate on input change
    $(document).on('input', '.item-quantity, .item-price, #discount_amount, #withholding_tax_rate', function() {
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

    // Handle withholding tax checkbox
    $('#withholding_tax_enabled').change(function() {
        if ($(this).is(':checked')) {
            $('#withholding_tax_fields').show();
            $('#withholding_tax_rate').val(5); // Set default 5%
            $('#withholding_tax_rate_input').val(5);
            $('#withholding_tax_type_input').val($('#withholding_tax_type').val());
        } else {
            $('#withholding_tax_fields').hide();
            $('#withholding_tax_rate_input').val(0);
        }
        calculateTotals();
    });

    // WHT is NOT handled at invoice creation - it's only applied at payment/receipt time
    // All WHT fields are hidden and set to 0
    // $('#withholding_tax_rate, #withholding_tax_type').on('input change', function() {
    //     // Disabled - WHT only at payment time
    // });

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

    // Form submission
    $('#invoice-form').submit(function(e) {
        e.preventDefault();

        if ($('#items-tbody tr').length === 0) {
            Swal.fire('Error', 'Please add at least one item to the invoice', 'error');
            return;
        }

        const formData = new FormData(this);
        const submitBtn = $('#submit-btn');

        // Debug: Log form data
        console.log('Form data being sent:');
        console.log('Form action:', this.action);
        console.log('Form method:', this.method);
        console.log('Items count:', $('#items-tbody tr').length);

        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }

        submitBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i>Updating...');

        $.ajax({
            url: '{{ route("sales.invoices.update", $invoice->encoded_id) }}',
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
                    const response = xhr.responseJSON;
                    const errors = response?.errors;
                    console.log('Validation errors:', errors);
                    
                    // Get the error message - prefer the detailed message from response, otherwise extract from errors
                    let errorMessage = response?.message || 'Validation Error';
                    
                    // If message is generic, try to get the first detailed error from errors object
                    if (errorMessage === 'Stock validation failed' || errorMessage === 'Validation Error') {
                        if (errors && Object.keys(errors).length > 0) {
                            // Get the first error message from the errors object
                            const firstErrorKey = Object.keys(errors)[0];
                            const firstErrorMessages = errors[firstErrorKey];
                            if (firstErrorMessages && firstErrorMessages.length > 0) {
                                errorMessage = firstErrorMessages[0];
                            }
                        }
                    }
                    
                    displayValidationErrors(errors);
                    Swal.fire({
                        icon: 'error',
                        title: 'Stock Validation Error',
                        text: errorMessage
                    });
                } else if (xhr.status === 500) {
                    console.error('Server error:', xhr.responseText);
                    Swal.fire('Server Error', 'An internal server error occurred. Please try again later.', 'error');
                } else {
                    console.error('Unexpected error:', xhr.status, xhr.responseText);
                    const errorMessage = xhr.responseJSON?.message || 'Something went wrong. Please try again.';
                    Swal.fire('Error', errorMessage, 'error');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Update Invoice');
            }
        });
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
        $('#modal_vat_rate').val('{{ get_default_vat_rate() }}');
        $('#modal_vat_type').val('{{ get_default_vat_type() }}');
        $('#modal_notes').val('');
    }

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

        const existingRow = $(`tr[data-line-key="${lineKey}"]`);
        if (existingRow.length > 0) {
            const currentQuantity = parseFloat(existingRow.find('.item-quantity').val()) || 0;
            const newQuantity = currentQuantity + quantity;

            const itemType = selectedOption.data('item-type') || 'product';
            const trackStock = selectedOption.data('track-stock') === 'true' || selectedOption.data('track-stock') === true;
            const availableStock = parseFloat(selectedOption.data('stock')) || 0;
            const itemIdInt = parseInt(itemId);
            const originalQuantity = originalQuantities[itemIdInt] || 0;
            const adjustedAvailableStock = availableStock + originalQuantity;

            if (itemType === 'product' && trackStock && newQuantity > adjustedAvailableStock) {
                Swal.fire('Error', `Insufficient stock for ${selectedOption.data('name')}. Available: ${adjustedAvailableStock}, Total Requested: ${newQuantity}`, 'error');
                return;
            }

            existingRow.find('.item-quantity').val(newQuantity);

            let updatedUnitPrice = parseFloat(existingRow.find('.item-price').val()) || 0;
            const $priceInput = existingRow.find('.item-price');
            if (!$priceInput.data('original-price')) {
                const rowItemId = existingRow.data('item-id');
                if (rowItemId) {
                    const itemOption = $('#modal_item_id').find(`option[value="${rowItemId}"]`);
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
                updatedVatAmount = updatedSubtotal * (updatedVatRate / (100 + updatedVatRate));
                updatedLineTotal = updatedSubtotal;
            }

            existingRow.find('.line-total').text(updatedLineTotal.toFixed(2));
            existingRow.find('input[name*="[line_total]"]').val(updatedLineTotal);
            existingRow.find('input[name*="[vat_amount]"]').val(updatedVatAmount);

            calculateTotals();

            $('#modal_item_id').val('').trigger('change');
            $('#modal_quantity').val('');
            $('#modal_unit_price').val('');
            $('#modal_vat_rate').val('');
            $('#modal_vat_type').val('no_vat');
            $('#modal_notes').val('');
            $('#itemModal').modal('hide');
            return;
        }

        const itemType = selectedOption.data('item-type') || 'product';
        const trackStock = selectedOption.data('track-stock') === 'true' || selectedOption.data('track-stock') === true;
        const availableStock = parseFloat(selectedOption.data('stock')) || 0;
        const itemIdInt = parseInt(itemId);
        const originalQuantity = originalQuantities[itemIdInt] || 0;
        const adjustedAvailableStock = availableStock + originalQuantity;

        if (itemType === 'product' && trackStock && quantity > adjustedAvailableStock) {
            Swal.fire('Error', `Insufficient stock for ${selectedOption.data('name')}. Available: ${adjustedAvailableStock}, Requested: ${quantity}`, 'error');
            return;
        }

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
            vatAmount = subtotal * (vatRate / (100 + vatRate));
            lineTotal = subtotal;
        }

        const vatDisplay = vatType === 'no_vat' ? 'No VAT' : `${vatRate}%`;
        const invoiceCurrency = getCurrentInvoiceCurrency();
        const priceTooltip = invoiceCurrency !== functionalCurrency ? `title="Converted from ${originalPrice.toFixed(2)} ${functionalCurrency}"` : '';
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
                           ${priceTooltip}>
                </td>
                <td>
                    <small class="text-muted">${(vatType === 'no_vat') ? 'No VAT' : ((vatType === 'exclusive' ? 'Exclusive' : 'Inclusive') + ' (' + vatRate + '%) - ' + vatAmount.toFixed(2))}</small>
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

    // Helper function to calculate line total
    function calculateLineTotal(unitPrice, quantity, vatType, vatRate) {
        const subtotal = quantity * unitPrice;
        let lineTotal = 0;

        if (vatType === 'no_vat') {
            lineTotal = subtotal;
        } else if (vatType === 'exclusive') {
            const vatAmount = subtotal * (vatRate / 100);
            lineTotal = subtotal + vatAmount;
        } else {
            // VAT inclusive - line total is the subtotal (VAT already included)
            lineTotal = subtotal;
        }

        return lineTotal;
    }

    function updateRowTotal(row) {
        const quantity = parseFloat($(`input[name="items[${row}][quantity]"]`).val()) || 0;
        const unitPrice = parseFloat($(`input[name="items[${row}][unit_price]"]`).val()) || 0;
        const itemVatType = $(`input[name="items[${row}][vat_type]"]`).val();
        const itemVatRate = parseFloat($(`input[name="items[${row}][vat_rate]"]`).val()) || 0;

        let subtotal = quantity * unitPrice;
        let vatAmount = 0;
        let lineTotal = calculateLineTotal(unitPrice, quantity, itemVatType, itemVatRate);

        if (itemVatType === 'no_vat') {
            vatAmount = 0;
        } else if (itemVatType === 'exclusive') {
            vatAmount = subtotal * (itemVatRate / 100);
        } else {
            vatAmount = subtotal * (itemVatRate / (100 + itemVatRate));
        }

        // Update the line total display
        $(`input[name="items[${row}][line_total]"]`).val(lineTotal.toFixed(2));
        $(`input[name="items[${row}][vat_amount]"]`).val(vatAmount.toFixed(2));
        $(`.line-total`).eq(row).text(lineTotal.toFixed(2));
    }

    function calculateTotals() {
        let subtotal = 0;
        let vatAmount = 0;
        let lineTotalSum = 0;

        $('#items-tbody tr').each(function() {
            const quantity = parseFloat($(this).find('.item-quantity').val()) || 0;
            const unitPrice = parseFloat($(this).find('.item-price').val()) || 0;
            const itemVatType = $(this).find('input[name*="[vat_type]"]').val();
            const itemVatRate = parseFloat($(this).find('input[name*="[vat_rate]"]').val()) || 0;

            const rowSubtotal = quantity * unitPrice;
            let rowVatAmount = 0;
            let rowLineTotal = 0;
            let rowNetAmount = 0; // Net amount without VAT

            if (itemVatType === 'no_vat') {
                rowVatAmount = 0;
                rowLineTotal = rowSubtotal;
                rowNetAmount = rowSubtotal;
            } else if (itemVatType === 'exclusive') {
                rowVatAmount = rowSubtotal * (itemVatRate / 100);
                rowLineTotal = rowSubtotal + rowVatAmount;
                rowNetAmount = rowSubtotal; // For exclusive, unit price is already net
            } else {
                // inclusive
                rowVatAmount = rowSubtotal * (itemVatRate / (100 + itemVatRate));
                rowLineTotal = rowSubtotal;
                rowNetAmount = rowSubtotal - rowVatAmount; // Net amount = gross - VAT
            }

            subtotal += rowNetAmount; // Add net amount to subtotal
            vatAmount += rowVatAmount;
            lineTotalSum += rowLineTotal;
        });

        // Calculate withholding tax
        // WHT is NOT calculated at invoice creation - it's only applied at payment/receipt time
        const withholdingTaxEnabled = false;
        const withholdingTaxRate = 0;
        const withholdingTaxType = 'percentage';
        let withholdingTaxAmount = 0;

        // Calculate total discount (from invoice-level discount)
        const totalDiscount = parseFloat($('#discount_amount').val()) || 0;

        // Calculate final total using sum of line totals (handles inclusive/exclusive correctly)
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

    // Also update when items are removed via remove button
    $(document).on('click', '.remove-item', function() {
        setTimeout(function() {
            updateButtonsBasedOnItemCount();
        }, 100);
    });

    // Export CSV button handler
    $('#export-csv-btn').click(function() {
        exportItemsToCsv();
    });

    // Import CSV button - opens import page in new tab with current form data
    $('#import-csv-btn').on('click', function(e) {
        e.preventDefault();

        // Collect current form values from the edit page
        const customerId = $('select[name="customer_id"]').val();
        const invoiceNumber = '{{ $invoice->invoice_number }}';
        const invoiceDate = $('input[name="invoice_date"]').val();
        const dueDate = $('input[name="due_date"]').val();
        const paymentTerms = $('#payment_terms').val();
        const paymentDays = $('#payment_days').val();
        const currency = $('#currency').val();
        const exchangeRate = $('#exchange_rate').val();
        const notes = $('#notes').val();
        const termsConditions = $('#terms_conditions').val();
        const invoiceId = '{{ $invoice->encoded_id }}';

        // Build URL with query parameters
        const params = new URLSearchParams();
        params.append('invoice_id', invoiceId); // Add invoice ID for edit mode
        if (customerId) params.append('customer_id', customerId);
        if (invoiceNumber) params.append('invoice_number', invoiceNumber);
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

    // Load existing invoice items
    function loadExistingItems() {
        console.log('Loading existing items...');
        console.log('Total items to load:', {{ count($invoice->items) }});

        @if(count($invoice->items) > 0)
        @foreach($invoice->items as $index => $item)
            console.log('Loading item {{ $index }}:', {
                id: {{ $item->inventory_item_id }},
                name: {!! json_encode($item->item_name) !!},
                quantity: {{ $item->quantity }},
                unitPrice: {{ $item->unit_price }},
                vatType: {!! json_encode($item->vat_type) !!},
                vatRate: {{ $item->vat_rate }},
                vatAmount: {{ $item->vat_amount ?? 0 }},
                discountType: {!! json_encode($item->discount_type ?? 'percentage') !!},
                discountRate: {{ $item->discount_rate ?? 0 }},
                discountAmount: {{ $item->discount_amount ?? 0 }},
                notes: {!! json_encode($item->notes ?? '') !!},
                lineTotal: {{ $item->line_total }},
                priceTier: {!! json_encode($item->price_tier ?? 'retail') !!}
            });

            const item{{ $index }} = {
                id: {{ $item->inventory_item_id }},
                name: {!! json_encode($item->item_name) !!},
                quantity: {{ $item->quantity }},
                unitPrice: {{ $item->unit_price }},
                vatType: {!! json_encode($item->vat_type) !!},
                vatRate: {{ $item->vat_rate }},
                vatAmount: {{ $item->vat_amount ?? 0 }},
                discountType: {!! json_encode($item->discount_type ?? 'percentage') !!},
                discountRate: {{ $item->discount_rate ?? 0 }},
                discountAmount: {{ $item->discount_amount ?? 0 }},
                notes: {!! json_encode($item->notes ?? '') !!},
                lineTotal: {{ $item->line_total }},
                priceTier: {!! json_encode($item->price_tier ?? 'retail') !!}
            };

            addExistingItemToTable(item{{ $index }}, {{ $index }});
        @endforeach
        @else
            console.warn('No items found to load! Invoice has {{ $invoice->id }} items in collection.');
        @endif

        console.log('Finished loading items. Item counter:', itemCounter);
        console.log('Items in table:', $('#items-tbody tr').length);
    }

    function addExistingItemToTable(item, index) {
        try {
            console.log('Adding existing item to table:', item, 'at index:', index);

            // Validate item data
            if (!item || !item.id) {
                console.error('Invalid item data:', item);
                return;
            }

            // Get original price: if invoice was in foreign currency, reverse-convert to get TZS price
            // Otherwise, get from item's base price or use current price as base
            let originalPrice = parseFloat(item.unitPrice) || 0;
            const invoiceCurrency = '{{ $invoice->currency ?? "TZS" }}';
            const invoiceExchangeRate = parseFloat('{{ $invoice->exchange_rate ?? 1 }}') || 1;

            // If invoice was in foreign currency, reverse-convert to get original TZS price
            // Reverse conversion: TZS = FCY × Exchange Rate
            // Example: If item was 4 USD at rate 2500, original TZS = 4 × 2500 = 10,000 TZS
            if (invoiceCurrency !== functionalCurrency && invoiceExchangeRate !== 1) {
                originalPrice = item.unitPrice * invoiceExchangeRate;
            } else {
                const itemOption = $('#modal_item_id').find(`option[value="${item.id}"]`);
                if (itemOption.length) {
                    const tier = item.priceTier || 'retail';
                    originalPrice = tier === 'wholesale'
                        ? (parseFloat(itemOption.attr('data-wholesale-price')) || originalPrice)
                        : (parseFloat(itemOption.data('price')) || originalPrice);
                }
            }

            // Get current currency and exchange rate (may differ from invoice's original currency)
            const currentCurrency = getCurrentInvoiceCurrency();
            const currentExchangeRate = getCurrentExchangeRate();

            // Convert price to current currency using original TZS price
            let displayPrice = parseFloat(item.unitPrice) || 0; // Default to stored price
            if (currentCurrency !== invoiceCurrency) {
                // Currency changed - convert from original TZS to new currency
                displayPrice = convertItemPrice(originalPrice, currentCurrency, currentExchangeRate);
            } else if (currentCurrency !== functionalCurrency && currentExchangeRate !== invoiceExchangeRate) {
                // Same currency but exchange rate changed - re-convert
                displayPrice = convertItemPrice(originalPrice, currentCurrency, currentExchangeRate);
            }

            // Ensure numeric values
            const quantity = parseFloat(item.quantity) || 0;
            const vatRate = parseFloat(item.vatRate) || 0;
            const vatAmount = parseFloat(item.vatAmount) || 0;
            const lineTotal = parseFloat(item.lineTotal) || 0;
            const discountRate = parseFloat(item.discountRate) || 0;
            const discountAmount = parseFloat(item.discountAmount) || 0;

            const discountDisplay = item.discountType === 'percentage' ? `${discountRate}%` : `TSh ${discountAmount.toFixed(2)}`;
            const vatDisplay = item.vatType === 'no_vat' ? 'No VAT' : `${vatRate}%`;
            const priceTooltip = currentCurrency !== functionalCurrency && currentExchangeRate !== 1
                ? `title="Converted from ${originalPrice.toFixed(2)} ${functionalCurrency} at rate ${currentExchangeRate}"`
                : '';

            // Calculate VAT display amount
            let vatAmountDisplay = 0;
            if (item.vatType !== 'no_vat') {
                if (item.vatType === 'exclusive') {
                    vatAmountDisplay = displayPrice * quantity * (vatRate / 100);
                } else {
                    vatAmountDisplay = displayPrice * quantity * (vatRate / (100 + vatRate));
                }
            }

            const calculatedLineTotal = calculateLineTotal(displayPrice, quantity, item.vatType || 'no_vat', vatRate);
            const pt = item.priceTier || 'retail';
            const lineKey = `${item.id}_${pt}`;
            const tierLabel = pt === 'wholesale' ? ' <span class="badge bg-secondary">Wholesale</span>' : '';

            const row = `
                <tr data-item-id="${item.id}" data-line-key="${lineKey}">
                    <td>
                        <input type="hidden" name="items[${index}][inventory_item_id]" value="${item.id}">
                        <input type="hidden" name="items[${index}][price_tier]" value="${pt}">
                        <input type="hidden" name="items[${index}][item_name]" value="${escapeHtml(item.name || '')}">
                        <input type="hidden" name="items[${index}][vat_type]" value="${item.vatType || 'no_vat'}">
                        <input type="hidden" name="items[${index}][vat_rate]" value="${vatRate}">
                        <input type="hidden" name="items[${index}][discount_type]" value="${item.discountType || 'percentage'}">
                        <input type="hidden" name="items[${index}][discount_rate]" value="${discountRate}">
                        <input type="hidden" name="items[${index}][notes]" value="${escapeHtml(item.notes || '')}">
                        <input type="hidden" name="items[${index}][line_total]" value="${lineTotal}">
                        <input type="hidden" name="items[${index}][vat_amount]" value="${vatAmount}">
                        <div class="fw-bold">${escapeHtml(item.name || '')}${tierLabel}</div>
                        <small class="text-muted">${escapeHtml(item.notes || '')}</small>
                    </td>
                    <td>
                        <input type="number" class="form-control item-quantity"
                               name="items[${index}][quantity]" value="${quantity}"
                               step="0.01" min="0.01" data-row="${index}">
                    </td>
                    <td>
                        <input type="number" class="form-control item-price"
                               name="items[${index}][unit_price]" value="${displayPrice.toFixed(2)}"
                               step="0.01" min="0" data-row="${index}"
                               data-original-price="${originalPrice}"
                               data-original-currency="${functionalCurrency}"
                               ${priceTooltip}>
                    </td>
                    <td>
                        <small class="text-muted">${item.vatType === 'no_vat' ? 'No VAT' : ((item.vatType === 'exclusive' ? 'Exclusive' : 'Inclusive') + ' (' + vatRate + '%) - ' + vatAmountDisplay.toFixed(2))}</small>
                    </td>
                    <td>
                        <span class="line-total">${calculatedLineTotal.toFixed(2)}</span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-outline-danger btn-sm remove-item">
                            <i class="bx bx-trash"></i>
                        </button>
                    </td>
                </tr>
            `;

            $('#items-tbody').append(row);
            itemCounter = Math.max(itemCounter, index + 1);
        } catch (error) {
            console.error('Error adding item to table:', error, item);
        }
    }

    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

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

    // Block submit if locked
    $('#invoice-form').on('submit', function(e) {
        const form = this;
        const date = $('.transaction-date').val();
        if (!date) return true;

        if ($(form).data('period-checked') === true) {
            return true;
        }

        e.preventDefault();

        checkPeriodLock(date, function(response) {
            if (response.locked) {
                Swal.fire({
                    title: 'Locked Period',
                    text: response.message || 'The selected period is locked. Please choose another date.',
                    icon: 'error'
                });
            } else {
                $(form).data('period-checked', true);
                form.submit();
            }
        });
    });

    // Load existing items and initialize calculations
    loadExistingItems();
    calculateTotals();
});
</script>
@endpush
@endsection
