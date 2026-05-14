@extends('layouts.main')

@section('title', 'Edit Credit Note')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Credit Notes', 'url' => route('sales.credit-notes.index'), 'icon' => 'bx bx-undo'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bx bx-edit me-2"></i>Edit Credit Note #{{ $creditNote->credit_note_number }}
                            </h5>
                            <div class="d-flex gap-2">
                                <span class="badge bg-light text-dark">{{ ucfirst($creditNote->status) }}</span>
                                <span class="badge bg-info">{{ $creditNote->type }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('sales.credit-notes.update', $creditNote->encoded_id) }}" id="edit-credit-note-form" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            
                            <!-- Header Information -->
                            <div class="row mb-4">
                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="customer_id" class="form-label fw-bold">Customer <span class="text-danger">*</span></label>
                                                <select class="form-select select2-single @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id" required>
                                                    <option value="">Select Customer</option>
                                                    @foreach($customers as $customer)
                                                        <option value="{{ $customer->id }}" {{ $creditNote->customer_id == $customer->id ? 'selected' : '' }}>
                                                            {{ $customer->name }} - {{ $customer->phone ?? 'No Phone' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('customer_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="credit_note_date" class="form-label fw-bold">Credit Note Date <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control @error('credit_note_date') is-invalid @enderror" 
                                                       id="credit_note_date" name="credit_note_date" 
                                                       value="{{ $creditNote->credit_note_date->format('Y-m-d') }}" required>
                                                @error('credit_note_date')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="mb-2">Current Total</h6>
                                            <h4 class="text-primary mb-0">TZS {{ number_format($creditNote->total_amount, 2) }}</h4>
                                            <small class="text-muted">Original amount</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Credit Note Details -->
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="type" class="form-label fw-bold">Credit Note Type <span class="text-danger">*</span></label>
                                        <select class="form-select select2-single @error('type') is-invalid @enderror" id="type" name="type" required>
                                            <option value="">Select Type</option>
                                            <option value="return" {{ $creditNote->type == 'return' ? 'selected' : '' }}>Return</option>
                                            <option value="discount" {{ $creditNote->type == 'discount' ? 'selected' : '' }}>Discount</option>
                                            <option value="correction" {{ $creditNote->type == 'correction' ? 'selected' : '' }}>Correction</option>
                                            <option value="other" {{ $creditNote->type == 'other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                        @error('type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Status</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" value="{{ ucfirst($creditNote->status) }}" readonly>
                                            <span class="input-group-text">
                                                @if($creditNote->status == 'draft')
                                                    <i class="bx bx-edit text-warning"></i>
                                                @elseif($creditNote->status == 'approved')
                                                    <i class="bx bx-check text-success"></i>
                                                @elseif($creditNote->status == 'cancelled')
                                                    <i class="bx bx-x text-danger"></i>
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Reason and Notes -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="reason" class="form-label fw-bold">Reason for Credit Note <span class="text-danger">*</span></label>
                                        <textarea class="form-control @error('reason') is-invalid @enderror" 
                                                  id="reason" name="reason" rows="4" 
                                                  placeholder="Explain the reason for this credit note..." required>{{ old('reason', $creditNote->reason) }}</textarea>
                                        @error('reason')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="notes" class="form-label fw-bold">Additional Notes</label>
                                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                                  id="notes" name="notes" rows="4" 
                                                  placeholder="Any additional notes or comments...">{{ old('notes', $creditNote->notes) }}</textarea>
                                        @error('notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Exchange Options -->
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="form-check mb-3">
                                        <input type="hidden" name="refund_now" value="0">
                                        <input class="form-check-input" type="checkbox" value="1" id="refund_now" name="refund_now" {{ old('refund_now', $creditNote->refund_now) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="refund_now">Refund customer now</label>
                                    </div>
                                    <div id="bank_account_group" style="display: {{ old('refund_now', $creditNote->refund_now) ? 'block' : 'none' }};">
                                        <label for="bank_account_id" class="form-label">Bank Account for Refund</label>
                                        <select class="form-select" id="bank_account_id" name="bank_account_id">
                                            <option value="">Select Bank Account</option>
                                            @foreach($bankAccounts as $bankAccount)
                                                <option value="{{ $bankAccount->id }}" {{ old('bank_account_id') == $bankAccount->id ? 'selected' : '' }}>{{ $bankAccount->name }} - {{ $bankAccount->account_number }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Select the bank account to process the refund from</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mb-3">
                                        <input type="hidden" name="return_to_stock" value="0">
                                        <input class="form-check-input" type="checkbox" value="1" id="return_to_stock" name="return_to_stock" {{ old('return_to_stock', $creditNote->return_to_stock) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="return_to_stock">Return items to stock</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mb-3">
                                        <input type="hidden" name="is_exchange" value="0">
                                        <input class="form-check-input" type="checkbox" value="1" id="is_exchange" name="is_exchange" {{ old('is_exchange', $creditNote->type === 'exchange') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_exchange">This is an item exchange</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Replacement Items Section (for exchanges) -->
                            <div class="card mb-4" id="replacement_items_section" style="display: {{ old('is_exchange', $creditNote->type === 'exchange') ? 'block' : 'none' }};">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Replacement Items</h6>
                                        <small class="text-muted">Items to give to customer as replacement</small>
                                    </div>
                                    <button type="button" class="btn btn-success btn-sm" id="add-replacement-item-btn">
                                        <i class="bx bx-plus me-1"></i>Add Replacement Item
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table" id="replacement-items-table">
                                            <thead>
                                                <tr>
                                                    <th width="30%">Item</th>
                                                    <th width="15%">Quantity</th>
                                                    <th width="15%">Unit Price</th>
                                                    <th width="15%">VAT</th>
                                                    <th width="15%">Line Total</th>
                                                    <th width="10%">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="replacement-items-tbody">
                                                @if($creditNote->type === 'exchange' && $creditNote->items->where('is_replacement', true)->count() > 0)
                                                    @foreach($creditNote->items->where('is_replacement', true) as $index => $item)
                                                        <tr>
                                                            <td>
                                                                <input type="hidden" name="replacement_items[{{ $index }}][inventory_item_id]" value="{{ $item->inventory_item_id }}">
                                                                <input type="hidden" name="replacement_items[{{ $index }}][item_name]" value="{{ $item->item_name }}">
                                                                <input type="hidden" name="replacement_items[{{ $index }}][item_code]" value="{{ $item->item_code }}">
                                                                <input type="hidden" name="replacement_items[{{ $index }}][unit_of_measure]" value="{{ $item->unit_of_measure }}">
                                                                <input type="hidden" name="replacement_items[{{ $index }}][vat_type]" value="{{ $item->vat_type }}">
                                                                <input type="hidden" name="replacement_items[{{ $index }}][vat_rate]" value="{{ $item->vat_rate }}">
                                                                <input type="hidden" name="replacement_items[{{ $index }}][notes]" value="{{ $item->notes }}">
                                                                <div class="fw-bold">{{ $item->item_name }}</div>
                                                                <small class="text-muted">{{ $item->item_code }}</small>
                                                            </td>
                                                            <td>
                                                                <input type="number" class="form-control replacement-item-quantity" name="replacement_items[{{ $index }}][quantity]" value="{{ $item->quantity }}" step="0.01" min="0.01" data-row="{{ $index }}">
                                                            </td>
                                                            <td>
                                                                <input type="number" class="form-control replacement-item-price" name="replacement_items[{{ $index }}][unit_price]" value="{{ $item->unit_price }}" step="0.01" min="0" data-row="{{ $index }}">
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">{{ $item->vat_type === 'no_vat' ? 'No VAT' : ($item->vat_rate . '%') }}</small>
                                                            </td>
                                                            <td>
                                                                <span class="replacement-item-total">{{ number_format($item->line_total, 2) }}</span>
                                                            </td>
                                                            <td>
                                                                <button type="button" class="btn btn-outline-danger btn-sm remove-replacement-item"><i class="bx bx-trash"></i></button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="5" class="text-end"><strong>Replacement Subtotal:</strong></td>
                                                    <td><strong id="replacement-subtotal">0.00</strong></td>
                                                    <td></td>
                                                </tr>
                                                <input type="hidden" name="replacement_subtotal" id="replacement-subtotal-input" value="0">
                                                <tr id="replacement-vat-row" style="display: none;">
                                                    <td colspan="5" class="text-end"><strong>Replacement VAT Amount:</strong></td>
                                                    <td><strong id="replacement-vat-amount">0.00</strong></td>
                                                    <td></td>
                                                </tr>
                                                <input type="hidden" name="replacement_vat_amount" id="replacement-vat-amount-input" value="0">
                                                <tr>
                                                    <td colspan="5" class="text-end"><strong>Replacement Total:</strong></td>
                                                    <td><strong id="replacement-total">0.00</strong></td>
                                                    <td></td>
                                                </tr>
                                                <input type="hidden" name="replacement_total" id="replacement-total-input" value="0">
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Existing Items Summary -->
                            @if($creditNote->items && $creditNote->items->count() > 0)
                            <div class="card mb-4">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">
                                        <i class="bx bx-package me-2"></i>Credit Note Items ({{ $creditNote->items->count() }} items)
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Item Name</th>
                                                    <th class="text-center">Quantity</th>
                                                    <th class="text-end">Unit Price</th>
                                                    <th class="text-end">VAT Amount</th>
                                                    <th class="text-end">Line Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($creditNote->items as $item)
                                                <tr>
                                                    <td>{{ $item->item_name }}</td>
                                                    <td class="text-center">{{ $item->quantity }}</td>
                                                    <td class="text-end">TZS {{ number_format($item->unit_price, 2) }}</td>
                                                    <td class="text-end">TZS {{ number_format($item->vat_amount ?? 0, 2) }}</td>
                                                    <td class="text-end fw-bold">TZS {{ number_format($item->line_total, 2) }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                                                                <div class="alert alert-info">
                                        <strong>Note:</strong> Only basic information can be edited. Individual item details cannot be modified after creation. 
                                        If you need to change items, please create a new credit note.
                                    </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="text-end">
                                                <h6>Summary:</h6>
                                                <p class="mb-1">Subtotal: TZS {{ number_format($creditNote->subtotal, 2) }}</p>
                                                <p class="mb-1">VAT Amount: TZS {{ number_format($creditNote->vat_amount, 2) }}</p>
                                                <p class="mb-1">Discount: TZS {{ number_format($creditNote->discount_amount ?? 0, 2) }}</p>
                                                <hr class="my-2">
                                                <h5 class="text-primary">Total: TZS {{ number_format($creditNote->total_amount, 2) }}</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Related Documents -->
                            @if($creditNote->sales_invoice)
                            <div class="card mb-4">
                                <div class="card-header bg-secondary text-white">
                                    <h6 class="mb-0">
                                        <i class="bx bx-link me-2"></i>Related Documents
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center p-3 border rounded">
                                                <i class="bx bx-receipt fs-2 text-primary me-3"></i>
                                                <div>
                                                    <h6 class="mb-1">Original Invoice</h6>
                                                    <p class="mb-1 text-muted">{{ $creditNote->sales_invoice->invoice_number }}</p>
                                                    <small class="text-muted">Date: {{ $creditNote->sales_invoice->invoice_date->format('M d, Y') }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center p-3 border rounded">
                                                <i class="bx bx-user fs-2 text-success me-3"></i>
                                                <div>
                                                    <h6 class="mb-1">Customer</h6>
                                                    <p class="mb-1 text-muted">{{ $creditNote->customer->name }}</p>
                                                    <small class="text-muted">Phone: {{ $creditNote->customer->phone ?? 'N/A' }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Form Actions -->
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <a href="{{ route('sales.credit-notes.show', $creditNote->encoded_id) }}" class="btn btn-outline-secondary">
                                                <i class="bx bx-arrow-back"></i> Back to Details
                                            </a>
                                            <a href="{{ route('sales.credit-notes.index') }}" class="btn btn-outline-info ms-2">
                                                <i class="bx bx-list-ul"></i> All Credit Notes
                                            </a>
                                        </div>
                                        <div class="d-flex gap-2">
                                            @if($creditNote->status == 'draft')
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bx bx-save"></i> Update Credit Note
                                            </button>
                                            @else
                                            <button type="button" class="btn btn-secondary" disabled>
                                                <i class="bx bx-lock"></i> Cannot Edit ({{ ucfirst($creditNote->status) }})
                                            </button>
                                            @endif
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
                        <option value="">Select an item</option>
                        @foreach($inventoryItems as $item)
                            <option value="{{ $item->id }}" 
                                    data-name="{{ $item->name }}" 
                                    data-code="{{ $item->code }}" 
                                    data-price="{{ $item->resolved_unit_price ?? $item->unit_price }}"
                                    data-stock="{{ $item->current_stock ?? 0 }}"
                                    data-unit="{{ $item->unit_of_measure }}">
                                {{ $item->name }} ({{ $item->code }}) - Stock: {{ $item->current_stock ?? 0 }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="modal_quantity" step="0.01" min="0.01" value="1">
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
                            <label for="modal_vat_type" class="form-label">VAT Type</label>
                            <select class="form-select" id="modal_vat_type">
                                <option value="no_vat" {{ get_default_vat_type() == 'no_vat' ? 'selected' : '' }}>No VAT</option>
                                <option value="inclusive" {{ get_default_vat_type() == 'inclusive' ? 'selected' : '' }}>VAT Inclusive</option>
                                <option value="exclusive" {{ get_default_vat_type() == 'exclusive' ? 'selected' : '' }}>VAT Exclusive</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="modal_vat_rate" class="form-label">VAT Rate (%)</label>
                            <input type="number" class="form-control" id="modal_vat_rate" step="0.01" min="0" max="100" value="{{ get_default_vat_rate() }}">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="modal_notes" class="form-label">Notes</label>
                    <input type="text" class="form-control" id="modal_notes" placeholder="Optional notes for this item">
                </div>
                <div class="mb-3">
                    <label class="form-label">Line Total</label>
                    <div class="input-group">
                        <span class="input-group-text">TZS</span>
                        <input type="text" class="form-control" id="modal-line-total" readonly value="0.00">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="modal-add-item">Add Item</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select an option'
    });

    // Form validation
    $('#edit-credit-note-form').submit(function(e) {
        const requiredFields = ['customer_id', 'credit_note_date', 'type', 'reason'];
        let isValid = true;
        
        requiredFields.forEach(function(field) {
            const value = $(`#${field}`).val().trim();
            if (!value) {
                $(`#${field}`).addClass('is-invalid');
                isValid = false;
            } else {
                $(`#${field}`).removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            Swal.fire('Error', 'Please fill in all required fields', 'error');
            return false;
        }
        
        // Submit form normally
        return true;
    });



    // Character counter for textareas
    $('#reason, #notes').on('input', function() {
        const maxLength = 1000;
        const currentLength = $(this).val().length;
        const remaining = maxLength - currentLength;
        
        if (!$(this).next('.char-counter').length) {
            $(this).after(`<small class="char-counter text-muted"></small>`);
        }
        
        $(this).next('.char-counter').text(`${currentLength}/${maxLength} characters`);
        
        if (remaining < 0) {
            $(this).next('.char-counter').addClass('text-danger');
        } else {
            $(this).next('.char-counter').removeClass('text-danger');
        }
    });

    // Exchange functionality
    let replacementItemCounter = {{ $creditNote->type === 'exchange' ? $creditNote->items->where('is_replacement', true)->count() : 0 }};

    // Show/hide bank account selection when refund_now is checked
    $('#refund_now').on('change', function() {
        if ($(this).is(':checked')) {
            $('#bank_account_group').show();
            $('#bank_account_id').prop('required', true);
        } else {
            $('#bank_account_group').hide();
            $('#bank_account_id').prop('required', false).val('');
        }
    });

    // Show/hide replacement items section when exchange is checked
    $('#is_exchange').on('change', function() {
        if ($(this).is(':checked')) {
            $('#replacement_items_section').show();
            // Auto-check return to stock for exchanges
            $('#return_to_stock').prop('checked', true);
        } else {
            $('#replacement_items_section').hide();
            // Clear replacement items
            $('#replacement-items-tbody').empty();
            calculateReplacementTotals();
        }
    });

    // Add replacement item functionality
    $('#add-replacement-item-btn').click(function() {
        $('#itemModal').modal('show');
        resetModalForm();
        // Mark this as replacement item
        $('#itemModal').data('is-replacement', true);
    });

    // Event handlers for replacement items
    $(document).on('input', '.replacement-item-quantity, .replacement-item-price', function() {
        calculateReplacementTotals();
    });

    $(document).on('click', '.remove-replacement-item', function() {
        $(this).closest('tr').remove();
        calculateReplacementTotals();
    });

    function calculateReplacementTotals() {
        let subtotal = 0;
        let vatAmount = 0;

        $('#replacement-items-tbody tr').each(function(index) {
            const quantity = parseFloat($(this).find('.replacement-item-quantity').val()) || 0;
            const unitPrice = parseFloat($(this).find('.replacement-item-price').val()) || 0;
            const vatType = $(this).find('input[name*="[vat_type]"]').val();
            const vatRate = parseFloat($(this).find('input[name*="[vat_rate]"]').val()) || 0;

            let rowSubtotal = quantity * unitPrice;

            let rowVatAmount = 0;
            if (vatType === 'exclusive') rowVatAmount = rowSubtotal * (vatRate / 100);
            if (vatType === 'inclusive') rowVatAmount = rowSubtotal * (vatRate / (100 + vatRate));

            subtotal += rowSubtotal;
            vatAmount += rowVatAmount;
        });

        const totalAmount = subtotal + vatAmount;

        $('#replacement-subtotal').text(subtotal.toFixed(2));
        $('#replacement-subtotal-input').val(subtotal);
        $('#replacement-vat-amount').text(vatAmount.toFixed(2));
        $('#replacement-vat-amount-input').val(vatAmount);
        $('#replacement-total').text(totalAmount.toFixed(2));
        $('#replacement-total-input').val(totalAmount);

        if (vatAmount > 0) {
            $('#replacement-vat-row').show();
        } else {
            $('#replacement-vat-row').hide();
        }
    }

    // Initialize replacement totals on page load
    calculateReplacementTotals();

    // Modal functionality
    function resetModalForm() {
        $('#modal_item_id').val('').trigger('change');
        $('#modal_quantity').val(1);
        $('#modal_unit_price').val(0);
        $('#modal_vat_type').val('no_vat');
        $('#modal_vat_rate').val(18);
        $('#modal_notes').val('');
        $('#modal-line-total').val('0.00');
    }

    function calculateModalLineTotal() {
        const quantity = parseFloat($('#modal_quantity').val()) || 0;
        const unitPrice = parseFloat($('#modal_unit_price').val()) || 0;
        const vatType = $('#modal_vat_type').val();
        const vatRate = parseFloat($('#modal_vat_rate').val()) || 0;

        let subtotal = quantity * unitPrice;
        let vatAmount = 0;

        if (vatType === 'exclusive') vatAmount = subtotal * (vatRate / 100);
        if (vatType === 'inclusive') vatAmount = subtotal * (vatRate / (100 + vatRate));

        const total = subtotal + vatAmount;
        $('#modal-line-total').val(total.toFixed(2));
    }

    // Modal event handlers
    $('#modal_item_id').on('change', function() {
        const selected = $(this).find('option:selected');
        if (selected.val()) {
            $('#modal_unit_price').val(selected.data('price') || 0);
            calculateModalLineTotal();
        }
    });

    $('#modal_quantity, #modal_unit_price, #modal_vat_type, #modal_vat_rate').on('input change', function() {
        calculateModalLineTotal();
    });

    $('#modal-add-item').on('click', function() {
        const itemId = $('#modal_item_id').val();
        const selected = $('#modal_item_id').find('option:selected');
        
        if (!itemId) {
            alert('Please select an item');
            return;
        }

        const itemName = selected.data('name');
        const itemCode = selected.data('code');
        const quantity = parseFloat($('#modal_quantity').val()) || 0;
        const unitPrice = parseFloat($('#modal_unit_price').val()) || 0;
        const vatType = $('#modal_vat_type').val();
        const vatRate = parseFloat($('#modal_vat_rate').val()) || 0;
        const notes = $('#modal_notes').val();

        if (quantity <= 0) {
            alert('Quantity must be greater than 0');
            return;
        }

        const isReplacement = $('#itemModal').data('is-replacement');
        
        if (isReplacement) {
            // Add replacement item
            const replacementRow = `
                <tr>
                    <td>
                        <input type="hidden" name="replacement_items[${replacementItemCounter}][inventory_item_id]" value="${itemId}">
                        <input type="hidden" name="replacement_items[${replacementItemCounter}][item_name]" value="${itemName}">
                        <input type="hidden" name="replacement_items[${replacementItemCounter}][item_code]" value="${itemCode}">
                        <input type="hidden" name="replacement_items[${replacementItemCounter}][unit_of_measure]" value="${selected.data('unit') || ''}">
                        <input type="hidden" name="replacement_items[${replacementItemCounter}][vat_type]" value="${vatType}">
                        <input type="hidden" name="replacement_items[${replacementItemCounter}][vat_rate]" value="${vatRate}">
                        <input type="hidden" name="replacement_items[${replacementItemCounter}][notes]" value="${notes}">
                        <div class="fw-bold">${itemName}</div>
                        <small class="text-muted">${itemCode || ''}</small>
                    </td>
                    <td>
                        <input type="number" class="form-control replacement-item-quantity" name="replacement_items[${replacementItemCounter}][quantity]" value="${quantity}" step="0.01" min="0.01" data-row="${replacementItemCounter}">
                    </td>
                    <td>
                        <input type="number" class="form-control replacement-item-price" name="replacement_items[${replacementItemCounter}][unit_price]" value="${unitPrice}" step="0.01" min="0" data-row="${replacementItemCounter}">
                    </td>
                    <td>
                        <small class="text-muted">${vatType === 'no_vat' ? 'No VAT' : (vatRate + '%')}</small>
                    </td>
                    <td>
                        <span class="replacement-item-total">${(quantity * unitPrice).toFixed(2)}</span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-outline-danger btn-sm remove-replacement-item"><i class="bx bx-trash"></i></button>
                    </td>
                </tr>`;
            
            $('#replacement-items-tbody').append(replacementRow);
            replacementItemCounter++;
            calculateReplacementTotals();
        }

        $('#itemModal').modal('hide');
        resetModalForm();
    });
});
</script>
@endpush 