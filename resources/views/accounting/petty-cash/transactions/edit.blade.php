@extends('layouts.main')

@section('title', 'Edit Petty Cash Transaction')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Petty Cash Units', 'url' => route('accounting.petty-cash.units.index'), 'icon' => 'bx bx-wallet'],
            ['label' => $transaction->pettyCashUnit->name, 'url' => route('accounting.petty-cash.units.show', $transaction->pettyCashUnit->encoded_id), 'icon' => 'bx bx-show'],
            ['label' => 'Edit Transaction', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        
        <h6 class="mb-0 text-uppercase">EDIT PETTY CASH TRANSACTION</h6>
        <hr />
        
        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header bg-warning text-dark">
                        <div class="d-flex align-items-center">
                            <div>
                                <h5 class="mb-0 text-dark">
                                    <i class="bx bx-edit me-2"></i>Edit Transaction #{{ $transaction->transaction_number }}
                                </h5>
                                <p class="mb-0 opacity-75">Update petty cash transaction details</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="editTransactionForm" action="{{ route('accounting.petty-cash.transactions.update', $transaction->encoded_id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            
                            <!-- Basic Information -->
                            <div class="row mb-4">
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label for="petty_cash_unit_id" class="form-label fw-bold">
                                            <i class="bx bx-wallet me-1"></i>Petty Cash Unit <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select form-select-lg @error('petty_cash_unit_id') is-invalid @enderror" 
                                                id="petty_cash_unit_id" name="petty_cash_unit_id" required>
                                            <option value="">-- Select Unit --</option>
                                            @foreach($units as $unit)
                                                <option value="{{ $unit->id }}" 
                                                    {{ old('petty_cash_unit_id', $transaction->petty_cash_unit_id) == $unit->id ? 'selected' : '' }}>
                                                    {{ $unit->name }} (Balance: TZS {{ number_format($unit->current_balance, 2) }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('petty_cash_unit_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label for="transaction_date" class="form-label fw-bold">
                                            <i class="bx bx-calendar me-1"></i>Transaction Date <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" 
                                               class="form-control form-control-lg @error('transaction_date') is-invalid @enderror" 
                                               id="transaction_date" 
                                               name="transaction_date" 
                                               value="{{ old('transaction_date', $transaction->transaction_date->format('Y-m-d')) }}" 
                                               required>
                                        @error('transaction_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Payee Information -->
                            <div class="row mb-4">
                                <div class="col-lg-12">
                                    <div class="mb-3">
                                        <label for="payee_type" class="form-label fw-bold">
                                            <i class="bx bx-user me-1"></i>Payee Type <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select form-select-lg @error('payee_type') is-invalid @enderror" 
                                                id="payee_type" name="payee_type" required>
                                            <option value="">-- Select Payee Type --</option>
                                            <option value="customer" {{ old('payee_type', $transaction->payee_type) == 'customer' ? 'selected' : '' }}>Customer</option>
                                            <option value="supplier" {{ old('payee_type', $transaction->payee_type) == 'supplier' ? 'selected' : '' }}>Supplier</option>
                                            <option value="employee" {{ old('payee_type', $transaction->payee_type) == 'employee' ? 'selected' : '' }}>Employee</option>
                                            <option value="other" {{ old('payee_type', $transaction->payee_type) == 'other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                        @error('payee_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Customer Section -->
                            <div class="row mb-4" id="customerSection" style="display: {{ old('payee_type', $transaction->payee_type) == 'customer' ? 'block' : 'none' }};">
                                <div class="col-lg-12">
                                    <div class="mb-3">
                                        <label for="customer_id" class="form-label fw-bold">
                                            <i class="bx bx-user me-1"></i>Customer <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select form-select-lg select2-single @error('customer_id') is-invalid @enderror" 
                                                id="customer_id" name="customer_id">
                                            <option value="">-- Select Customer --</option>
                                        </select>
                                        @error('customer_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Supplier Section -->
                            <div class="row mb-4" id="supplierSection" style="display: {{ old('payee_type', $transaction->payee_type) == 'supplier' ? 'block' : 'none' }};">
                                <div class="col-lg-12">
                                    <div class="mb-3">
                                        <label for="supplier_id" class="form-label fw-bold">
                                            <i class="bx bx-user me-1"></i>Supplier <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select form-select-lg select2-single @error('supplier_id') is-invalid @enderror" 
                                                id="supplier_id" name="supplier_id">
                                            <option value="">-- Select Supplier --</option>
                                        </select>
                                        @error('supplier_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Employee Section -->
                            <div class="row mb-4" id="employeeSection" style="display: {{ old('payee_type', $transaction->payee_type) == 'employee' ? 'block' : 'none' }};">
                                <div class="col-lg-12">
                                    <div class="mb-3">
                                        <label for="employee_id" class="form-label fw-bold">
                                            <i class="bx bx-user me-1"></i>Employee <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select form-select-lg select2-single @error('employee_id') is-invalid @enderror" 
                                                id="employee_id" name="employee_id">
                                            <option value="">-- Select Employee --</option>
                                            @foreach($employees ?? [] as $employee)
                                                <option value="{{ $employee->id }}" {{ old('employee_id', $transaction->employee_id) == $employee->id ? 'selected' : '' }}>
                                                    {{ $employee->full_name }}@if($employee->employee_number) ({{ $employee->employee_number }})@endif
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('employee_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Other Payee Section -->
                            <div class="row mb-4" id="otherPayeeSection" style="display: {{ old('payee_type', $transaction->payee_type) == 'other' ? 'block' : 'none' }};">
                                <div class="col-lg-12">
                                    <div class="mb-3">
                                        <label for="payee_name" class="form-label fw-bold">
                                            <i class="bx bx-user me-1"></i>Payee Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               class="form-control form-control-lg @error('payee_name') is-invalid @enderror" 
                                               id="payee_name" 
                                               name="payee_name" 
                                               value="{{ old('payee_name', $transaction->payee) }}" 
                                               placeholder="Enter payee name">
                                        @error('payee_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Description -->
                            <div class="row mb-4">
                                <div class="col-lg-12">
                                    <div class="mb-3">
                                        <label for="description" class="form-label fw-bold">
                                            <i class="bx bx-file me-1"></i>Description <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                                  id="description" 
                                                  name="description" 
                                                  rows="3" 
                                                  required>{{ old('description', $transaction->description) }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Line Items -->
                            <div class="row mb-4">
                                <div class="col-lg-12">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <label class="form-label fw-bold mb-0">
                                            <i class="bx bx-list-ul me-1"></i>Expense Line Items <span class="text-danger">*</span>
                                        </label>
                                        <button type="button" class="btn btn-sm btn-success" id="addLineItemBtn">
                                            <i class="bx bx-plus me-1"></i>Add Line Item
                                        </button>
                                    </div>
                                    <div id="lineItemsContainer">
                                        @if($transaction->items && $transaction->items->count() > 0)
                                            @foreach($transaction->items as $index => $item)
                                                <div class="line-item-row" data-line-index="{{ $index }}">
                                                    <div class="row">
                                                        <div class="col-md-5 mb-2">
                                                            <label class="form-label fw-bold">Select Account <span class="text-danger">*</span></label>
                                                            <select class="form-select chart-account-select select2-single" 
                                                                    name="line_items[{{ $index }}][chart_account_id]" required>
                                                                <option value="">Select Account</option>
                                                                @foreach($expenseAccounts as $account)
                                                                    <option value="{{ $account->id }}" 
                                                                        {{ $item->chart_account_id == $account->id ? 'selected' : '' }}>
                                                                        {{ $account->account_code }} - {{ $account->account_name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                        <div class="col-md-4 mb-2">
                                                            <label class="form-label fw-bold">Description</label>
                                                            <input type="text" 
                                                                   class="form-control description-input" 
                                                                   name="line_items[{{ $index }}][description]" 
                                                                   value="{{ $item->description }}" 
                                                                   placeholder="Enter description">
                                                        </div>
                                                        <div class="col-md-2 mb-2">
                                                            <label class="form-label fw-bold">Amount <span class="text-danger">*</span></label>
                                                            <input type="number" 
                                                                   class="form-control amount-input" 
                                                                   name="line_items[{{ $index }}][amount]" 
                                                                   value="{{ $item->amount }}" 
                                                                   step="0.01" 
                                                                   min="0.01" 
                                                                   placeholder="0.00" 
                                                                   required>
                                                            <div class="invalid-feedback"></div>
                                                        </div>
                                                        <div class="col-md-1 mb-2 d-flex align-items-end">
                                                            <button type="button" class="btn btn-outline-danger btn-sm remove-line-btn" title="Remove Line">
                                                                <i class="bx bx-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Notes and Attachment -->
                            <div class="row mb-4">
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label for="notes" class="form-label fw-bold">
                                            <i class="bx bx-note me-1"></i>Notes
                                        </label>
                                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                                  id="notes" 
                                                  name="notes" 
                                                  rows="3">{{ old('notes', $transaction->notes) }}</textarea>
                                        @error('notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-lg-6">
                                    <div class="mb-3">
                                        <label for="receipt_attachment" class="form-label fw-bold">
                                            <i class="bx bx-paperclip me-1"></i>Receipt Attachment
                                        </label>
                                        <input type="file" 
                                               class="form-control @error('receipt_attachment') is-invalid @enderror" 
                                               id="receipt_attachment" 
                                               name="receipt_attachment" 
                                               accept=".pdf,.jpg,.jpeg,.png">
                                        @error('receipt_attachment')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        @if($transaction->receipt_attachment)
                                            <small class="text-muted mt-1 d-block">
                                                Current: <a href="{{ Storage::url($transaction->receipt_attachment) }}" target="_blank">View Receipt</a>
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Submit Buttons -->
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('accounting.petty-cash.units.show', $transaction->pettyCashUnit->encoded_id) }}" 
                                           class="btn btn-secondary">
                                            <i class="bx bx-x me-1"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-success" id="submitBtn">
                                            <i class="bx bx-save me-1"></i>Update Transaction
                                        </button>
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

@push('styles')
<style>
    .line-item-row {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        border: 1px solid #dee2e6;
    }
    .line-item-row:hover {
        background: #e9ecef;
        border-color: #adb5bd;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    let lineItemCount = {{ $transaction->items ? $transaction->items->count() : 0 }};
    
    // Handle payee type selection
    $('#payee_type').on('change', function() {
        const payeeType = $(this).val();
        
        // Hide all sections first
        $('#customerSection, #supplierSection, #employeeSection, #otherPayeeSection').hide();
        
        // Reset required attributes and disable all fields
        $('#customer_id, #supplier_id, #employee_id, #payee_name').prop('required', false).prop('disabled', true);
        
        // Show relevant section based on selection
        if (payeeType === 'customer') {
            $('#customerSection').show();
            $('#customer_id').prop('required', true).prop('disabled', false);
        } else if (payeeType === 'supplier') {
            $('#supplierSection').show();
            $('#supplier_id').prop('required', true).prop('disabled', false);
        } else if (payeeType === 'employee') {
            $('#employeeSection').show();
            $('#employee_id').prop('required', true).prop('disabled', false);
        } else if (payeeType === 'other') {
            $('#otherPayeeSection').show();
            $('#payee_name').prop('required', true).prop('disabled', false);
        }
    });
    
    // Load customers
    function loadCustomers() {
        $.ajax({
            url: '{{ url("api/customers") }}',
            type: 'GET',
            success: function(customers) {
                const select = $('#customer_id');
                select.empty().append('<option value="">Select Customer</option>');
                
                customers.forEach(function(customer) {
                    select.append($('<option>', {
                        value: customer.id,
                        text: customer.name + (customer.customer_no ? ' (' + customer.customer_no + ')' : '')
                    }));
                });
                
                @if($transaction->customer_id)
                    select.val({{ $transaction->customer_id }}).trigger('change');
                @endif
                
                if (typeof $().select2 !== 'undefined') {
                    select.select2({
                        theme: 'bootstrap-5',
                        width: '100%'
                    });
                }
            }
        });
    }
    
    // Load suppliers
    function loadSuppliers() {
        $.ajax({
            url: '{{ url("api/suppliers") }}',
            type: 'GET',
            success: function(suppliers) {
                const select = $('#supplier_id');
                select.empty().append('<option value="">Select Supplier</option>');
                
                suppliers.forEach(function(supplier) {
                    select.append($('<option>', {
                        value: supplier.id,
                        text: supplier.name
                    }));
                });
                
                @if($transaction->supplier_id)
                    select.val({{ $transaction->supplier_id }}).trigger('change');
                @endif
                
                if (typeof $().select2 !== 'undefined') {
                    select.select2({
                        theme: 'bootstrap-5',
                        width: '100%'
                    });
                }
            }
        });
    }
    
    // Initialize Select2 for chart accounts
    $('.chart-account-select').each(function() {
        if (typeof $().select2 !== 'undefined') {
            $(this).select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        }
    });
    
    // Add line item
    $('#addLineItemBtn').on('click', function() {
        lineItemCount++;
        const lineItemHtml = `
            <div class="line-item-row" data-line-index="${lineItemCount}">
                <div class="row">
                    <div class="col-md-5 mb-2">
                        <label class="form-label fw-bold">Select Account <span class="text-danger">*</span></label>
                        <select class="form-select chart-account-select select2-single" 
                                name="line_items[${lineItemCount}][chart_account_id]" required>
                            <option value="">Select Account</option>
                            @foreach($expenseAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->account_code }} - {{ $account->account_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label fw-bold">Description</label>
                        <input type="text" class="form-control description-input" 
                               name="line_items[${lineItemCount}][description]" 
                               placeholder="Enter description">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label fw-bold">Amount <span class="text-danger">*</span></label>
                        <input type="number" class="form-control amount-input" 
                               name="line_items[${lineItemCount}][amount]" 
                               step="0.01" min="0.01" placeholder="0.00" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-1 mb-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-line-btn" title="Remove Line">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        $('#lineItemsContainer').append(lineItemHtml);
        
        // Initialize Select2 for new select
        $('#lineItemsContainer .line-item-row:last .chart-account-select').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    });
    
    // Remove line item
    $(document).on('click', '.remove-line-btn', function() {
        $(this).closest('.line-item-row').remove();
    });
    
    // Load customers and suppliers on page load
    loadCustomers();
    loadSuppliers();
    
    // Trigger payee type change to show correct section
    $('#payee_type').trigger('change');
});
</script>
@endpush
@endsection

