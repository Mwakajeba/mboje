@extends('layouts.main')

@section('title', 'Create Bill Purchase')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchases', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Bill Purchases', 'url' => route('accounting.bill-purchases'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Create Bill', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">CREATE BILL PURCHASE</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header bg-secondary text-white">
                        <div class="d-flex align-items-center">
                            <div>
                                <h5 class="mb-0 text-white">
                                    <i class="bx bx-receipt me-2"></i>New Bill Purchase
                                </h5>
                                <p class="mb-0 opacity-75">Create a new bill purchase entry</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bx bx-error-circle me-2"></i>
                                <strong>Please fix the following errors:</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <form action="{{ route('accounting.bill-purchases.store') }}" method="POST" id="billForm">
                            @csrf

                            <!-- Basic Information Section -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="mb-3 fw-bold text-primary">
                                        <i class="bx bx-info-circle me-2"></i>Basic Information
                                    </h6>
                                </div>
                                
                                <div class="col-lg-6 mb-3">
                                    <label for="date" class="form-label fw-bold">
                                        <i class="bx bx-calendar me-1"></i>Bill Date <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" name="date" id="date" 
                                           class="form-control @error('date') is-invalid @enderror" 
                                           value="{{ old('date', date('Y-m-d')) }}" required>
                                    @error('date') 
                                        <div class="invalid-feedback">{{ $message }}</div> 
                                    @enderror
                                </div>

                                <div class="col-lg-6 mb-3">
                                    <label for="due_date" class="form-label fw-bold">
                                        <i class="bx bx-calendar-check me-1"></i>Due Date
                                    </label>
                                    <input type="date" name="due_date" id="due_date" 
                                           class="form-control @error('due_date') is-invalid @enderror" 
                                           value="{{ old('due_date') }}">
                                    @error('due_date') 
                                        <div class="invalid-feedback">{{ $message }}</div> 
                                    @enderror
                                </div>

                                <div class="col-lg-6 mb-3">
                                    <label for="supplier_id" class="form-label fw-bold">
                                        <i class="bx bx-user me-1"></i>Supplier <span class="text-danger">*</span>
                                    </label>
                                    <select name="supplier_id" id="supplier_id" 
                                            class="form-select select2-single @error('supplier_id') is-invalid @enderror" required>
                                        <option value="">-- Select Supplier --</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                                {{ $supplier->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('supplier_id') 
                                        <div class="invalid-feedback">{{ $message }}</div> 
                                    @enderror
                                </div>

                                <div class="col-lg-6 mb-3">
                                    <label for="credit_account" class="form-label fw-bold">
                                        <i class="bx bx-credit-card me-1"></i>Credit Account (Accounts Payable) <span class="text-danger">*</span>
                                    </label>
                                    <select name="credit_account" id="credit_account" 
                                            class="form-select select2-single @error('credit_account') is-invalid @enderror" required>
                                        <option value="">-- Select Credit Account --</option>
                                        @foreach($chartAccounts as $account)
                                            <option value="{{ $account->id }}" {{ old('credit_account') == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('credit_account') 
                                        <div class="invalid-feedback">{{ $message }}</div> 
                                    @enderror
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="note" class="form-label fw-bold">
                                        <i class="bx bx-note me-1"></i>Notes
                                    </label>
                                    <textarea name="note" id="note" class="form-control @error('note') is-invalid @enderror" 
                                              rows="3" placeholder="Enter any additional notes or description for this bill...">{{ old('note') }}</textarea>
                                    @error('note') 
                                        <div class="invalid-feedback">{{ $message }}</div> 
                                    @enderror
                                </div>
                            </div>

                            <!-- VAT Information Section -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="mb-3 fw-bold text-primary">
                                        <i class="bx bx-calculator me-2"></i>VAT Information
                                    </h6>
                                </div>

                                <div class="col-lg-6 mb-3">
                                    <label for="vat_mode" class="form-label fw-bold">
                                        <i class="bx bx-purchase-tag me-1"></i>VAT Mode
                                    </label>
                                    <select name="vat_mode" id="vat_mode" 
                                            class="form-select @error('vat_mode') is-invalid @enderror">
                                        @php
                                            $defaultVatType = strtoupper(get_default_vat_type());
                                            $selectedVatMode = old('vat_mode', $defaultVatType == 'NONE' ? 'NONE' : ($defaultVatType == 'EXCLUSIVE' ? 'EXCLUSIVE' : ($defaultVatType == 'INCLUSIVE' ? 'INCLUSIVE' : 'NONE')));
                                        @endphp
                                        <option value="NONE" {{ $selectedVatMode == 'NONE' ? 'selected' : '' }}>None</option>
                                        <option value="EXCLUSIVE" {{ $selectedVatMode == 'EXCLUSIVE' ? 'selected' : '' }}>Exclusive</option>
                                        <option value="INCLUSIVE" {{ $selectedVatMode == 'INCLUSIVE' ? 'selected' : '' }}>Inclusive</option>
                                    </select>
                                    @error('vat_mode') 
                                        <div class="invalid-feedback">{{ $message }}</div> 
                                    @enderror
                                    <small class="form-text text-muted">
                                        <strong>Exclusive:</strong> VAT separate from base<br>
                                        <strong>Inclusive:</strong> VAT included in total<br>
                                        <strong>None:</strong> No VAT
                                    </small>
                                </div>

                                <div class="col-lg-6 mb-3">
                                    <label for="vat_rate" class="form-label fw-bold">
                                        <i class="bx bx-percent me-1"></i>VAT Rate (%) <span id="vatRateRequired" style="display: none;" class="text-danger">*</span>
                                    </label>
                                    <input type="number" name="vat_rate" id="vat_rate" 
                                           class="form-control @error('vat_rate') is-invalid @enderror" 
                                           value="{{ old('vat_rate', get_default_vat_rate()) }}" 
                                           step="0.01" min="0" max="100" placeholder="0.00">
                                    @error('vat_rate') 
                                        <div class="invalid-feedback">{{ $message }}</div> 
                                    @enderror
                                </div>
                            </div>

                            <!-- Line Items Section -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0 fw-bold text-primary">
                                            <i class="bx bx-list-ul me-2"></i>Line Items
                                        </h6>
                                        <button type="button" class="btn btn-success btn-sm" id="addLineItem">
                                            <i class="bx bx-plus me-1"></i> Add Line Item
                                        </button>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th width="40%">Debit Account <span class="text-danger">*</span></th>
                                                    <th width="25%">Amount <span class="text-danger">*</span></th>
                                                    <th width="30%">Description</th>
                                                    <th width="5%" class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="lineItemsContainer">
                                                <tr class="line-item" data-index="0">
                                                    <td>
                                                        <select name="line_items[0][debit_account]" 
                                                                class="form-select debit-account select2-single" required>
                                                            <option value="">-- Select Account --</option>
                                                            @foreach($chartAccounts as $account)
                                                                <option value="{{ $account->id }}">
                                                                    {{ $account->account_code }} - {{ $account->account_name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <div class="input-group">
                                                            <span class="input-group-text">TZS</span>
                                                            <input type="number" name="line_items[0][amount]" 
                                                                   class="form-control amount-input" 
                                                                   step="0.01" min="0.01" required placeholder="0.00">
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="line_items[0][description]" 
                                                               class="form-control" placeholder="Enter description">
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-danger btn-sm remove-line-item" style="display: none;">
                                                            <i class="bx bx-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary Section -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-lg-6 mb-3 mb-lg-0">
                                                    <label class="form-label fw-bold mb-2">
                                                        <i class="bx bx-calculator me-1"></i>Total Amount
                                                    </label>
                                                    <div class="input-group input-group-lg">
                                                        <span class="input-group-text bg-success text-white fw-bold">TZS</span>
                                                        <input type="text" id="totalAmount" 
                                                               class="form-control fw-bold text-success fs-4" 
                                                               value="0.00" readonly>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                                        <button type="submit" class="btn btn-primary btn-lg js-submit-once">
                                                            <i class="bx bx-save me-1"></i> Create Bill
                                                        </button>
                                                        <a href="{{ route('accounting.bill-purchases') }}" class="btn btn-outline-secondary btn-lg">
                                                            <i class="bx bx-arrow-back me-1"></i> Cancel
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
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
$(document).ready(function() {
    let lineItemIndex = 1;

    // Initialize Select2 for existing selects
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Add line item
    $('#addLineItem').click(function() {
        const newRow = `
            <tr class="line-item" data-index="${lineItemIndex}">
                <td>
                    <select name="line_items[${lineItemIndex}][debit_account]" 
                            class="form-select debit-account select2-single" required>
                        <option value="">-- Select Account --</option>
                        @foreach($chartAccounts as $account)
                            <option value="{{ $account->id }}">
                                {{ $account->account_code }} - {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <div class="input-group">
                        <span class="input-group-text">TZS</span>
                        <input type="number" name="line_items[${lineItemIndex}][amount]" 
                               class="form-control amount-input" 
                               step="0.01" min="0.01" required placeholder="0.00">
                    </div>
                </td>
                <td>
                    <input type="text" name="line_items[${lineItemIndex}][description]" 
                           class="form-control" placeholder="Enter description">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm remove-line-item">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#lineItemsContainer').append(newRow);
        
        // Initialize Select2 for the new select
        $('#lineItemsContainer tr:last-child .select2-single').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
        
        lineItemIndex++;
        updateRemoveButtons();
    });

    // Remove line item
    $(document).on('click', '.remove-line-item', function() {
        $(this).closest('tr').fadeOut(300, function() {
            $(this).remove();
            updateRemoveButtons();
            calculateTotal();
        });
    });

    // Calculate total when amount changes
    $(document).on('input', '.amount-input', function() {
        calculateTotal();
    });

    function updateRemoveButtons() {
        const lineItems = $('.line-item');
        if (lineItems.length > 1) {
            $('.remove-line-item').show();
        } else {
            $('.remove-line-item').hide();
        }
    }

    function calculateTotal() {
        let total = 0;
        $('.amount-input').each(function() {
            const amount = parseFloat($(this).val()) || 0;
            total += amount;
        });
        $('#totalAmount').val(total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    }

    // Show/hide VAT rate required indicator based on VAT mode
    $('#vat_mode').on('change', function() {
        const vatMode = $(this).val();
        if (vatMode === 'NONE') {
            $('#vatRateRequired').hide();
            $('#vat_rate').prop('required', false);
        } else {
            $('#vatRateRequired').show();
            $('#vat_rate').prop('required', true);
        }
    });

    // Initialize VAT rate required indicator
    $('#vat_mode').trigger('change');

    // Prevent double submit: disable and fade submit button
    $('#billForm').on('submit', function(e) {
        const btn = $(this).find('.js-submit-once');
        btn.prop('disabled', true).addClass('disabled').css({ opacity: 0.6, cursor: 'not-allowed' });
        btn.html('<i class="bx bx-loader-alt bx-spin me-1"></i> Creating...');
        
        const total = parseFloat($('#totalAmount').val().replace(/,/g, '')) || 0;
        if (total <= 0) {
            e.preventDefault();
            alert('Please add at least one line item with a valid amount.');
            btn.prop('disabled', false).removeClass('disabled').css({ opacity: 1, cursor: 'pointer' });
            btn.html('<i class="bx bx-save me-1"></i> Create Bill');
            return false;
        }
    });

    // Initialize
    updateRemoveButtons();
    calculateTotal();
});
</script>
@endpush
