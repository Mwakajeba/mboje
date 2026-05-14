@extends('layouts.main')

@section('title', 'Edit Rental Invoice')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Rental Invoices', 'url' => route('rental-event-equipment.rental-invoices.index'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Edit', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />
        <h6 class="mb-0 text-uppercase">EDIT RENTAL INVOICE</h6>
        <hr />

        <div class="card">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0"><i class="bx bx-edit me-2"></i>Edit Invoice #{{ $invoice->invoice_number }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('rental-event-equipment.rental-invoices.update', $invoice->getRouteKey()) }}" id="invoice-form">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="contract_id" class="form-label">Contract</label>
                                <input type="text" class="form-control" value="{{ $invoice->contract->contract_number ?? 'N/A' }}" readonly>
                                <input type="hidden" name="contract_id" value="{{ $invoice->contract_id }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="invoice_date" class="form-label">Invoice Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="invoice_date" name="invoice_date" value="{{ $invoice->invoice_date->format('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" value="{{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '' }}">
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="mb-3"><i class="bx bx-list-ul me-2"></i>Invoice Items</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">Select</th>
                                            <th width="25%">Item Description</th>
                                            <th width="10%">Type</th>
                                            <th width="10%">Quantity</th>
                                            <th width="15%">Unit Price <span class="text-danger">*</span></th>
                                            <th width="15%">Line Total</th>
                                            <th width="20%">Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody id="invoice-items-tbody">
                                        @foreach($invoice->items as $index => $item)
                                        <tr class="invoice-item-row" data-item-type="{{ $item->item_type }}">
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input item-checkbox" type="checkbox" 
                                                        id="item_{{ $index }}" 
                                                        data-item-index="{{ $index }}"
                                                        checked>
                                                </div>
                                                <input type="hidden" name="items[{{ $index }}][equipment_id]" value="{{ $item->equipment_id ?? '' }}">
                                                <input type="hidden" name="items[{{ $index }}][item_type]" value="{{ $item->item_type }}">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control description-input" 
                                                    name="items[{{ $index }}][description]" 
                                                    value="{{ $item->description }}" 
                                                    required>
                                            </td>
                                            <td>
                                                @php
                                                    $typeLabel = $item->item_type === 'equipment' ? 'Equipment' : 
                                                                 ($item->item_type === 'damage_charge' ? 'Damage Charge' : 
                                                                 ($item->item_type === 'loss_charge' ? 'Loss Charge' : 'Service'));
                                                    $typeBadge = $item->item_type === 'equipment' ? 'primary' : 
                                                                 ($item->item_type === 'damage_charge' ? 'warning' : 'danger');
                                                @endphp
                                                <span class="badge bg-{{ $typeBadge }}">{{ $typeLabel }}</span>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control quantity-input" 
                                                    name="items[{{ $index }}][quantity]" 
                                                    value="{{ $item->quantity }}" 
                                                    min="1" 
                                                    data-item-index="{{ $index }}"
                                                    required>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control unit-price-input" 
                                                    name="items[{{ $index }}][unit_price]" 
                                                    value="{{ $item->unit_price }}" 
                                                    min="0" 
                                                    step="0.01"
                                                    data-item-index="{{ $index }}"
                                                    required>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control line-total-display" 
                                                    value="{{ number_format($item->line_total, 2) }}" 
                                                    readonly>
                                                <input type="hidden" name="items[{{ $index }}][line_total]" class="line-total-hidden" 
                                                    value="{{ $item->line_total }}">
                                            </td>
                                            <td>
                                                <textarea class="form-control" 
                                                    name="items[{{ $index }}][notes]" 
                                                    rows="1" 
                                                    placeholder="Notes...">{{ $item->notes }}</textarea>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Deposit Applied Section -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="deposit_applied" class="form-label">Deposit Applied</label>
                                <input type="number" class="form-control" id="deposit_applied" name="deposit_applied" 
                                    value="{{ $invoice->deposit_applied ?? 0 }}" min="0" step="0.01">
                                <small class="text-muted">Available: {{ number_format($invoice->contract ? \App\Models\RentalEventEquipment\CustomerDeposit::where('contract_id', $invoice->contract_id)->where('status', 'confirmed')->sum('amount') : 0, 2) }} TZS</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <th>Subtotal:</th>
                                            <td class="text-end"><span id="invoice-subtotal">{{ number_format($invoice->subtotal, 2) }}</span> TZS</td>
                                        </tr>
                                        <tr>
                                            <th>Deposit Applied:</th>
                                            <td class="text-end"><span id="invoice-deposit">{{ number_format($invoice->deposit_applied ?? 0, 2) }}</span> TZS</td>
                                        </tr>
                                        <tr class="border-top">
                                            <th><strong>Total Amount:</strong></th>
                                            <td class="text-end"><strong><span id="invoice-total" class="text-success">{{ number_format($invoice->total_amount, 2) }}</span> TZS</strong></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes...">{{ $invoice->notes }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="{{ route('rental-event-equipment.rental-invoices.show', $invoice->getRouteKey()) }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bx bx-check me-1"></i>Update Invoice
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize calculations - trigger calculation on page load
    setTimeout(function() {
        initializeInvoiceCalculations();
        calculateInvoiceTotals(); // Force initial calculation
    }, 100);

    function initializeInvoiceCalculations() {
        // Handle quantity and unit price changes
        $(document).off('input', '.quantity-input, .unit-price-input').on('input', '.quantity-input, .unit-price-input', function() {
            const row = $(this).closest('tr');
            const itemIndex = $(this).data('item-index');
            const quantity = parseFloat(row.find('.quantity-input').val()) || 0;
            const unitPrice = parseFloat(row.find('.unit-price-input').val()) || 0;
            const lineTotal = quantity * unitPrice;
            
            row.find('.line-total-display').val(lineTotal.toFixed(2));
            row.find('.line-total-hidden').val(lineTotal.toFixed(2));
            calculateInvoiceTotals();
        });

        // Handle checkbox changes
        $(document).off('change', '.item-checkbox').on('change', '.item-checkbox', function() {
            const row = $(this).closest('tr');
            const inputs = row.find('input:not(.item-checkbox), select, textarea');
            
            if ($(this).is(':checked')) {
                inputs.prop('disabled', false);
            } else {
                inputs.prop('disabled', true);
            }
            calculateInvoiceTotals();
        });

        // Handle deposit applied change
        $('#deposit_applied').off('input').on('input', function() {
            calculateInvoiceTotals();
        });

        // Calculate total on page load
        calculateInvoiceTotals();
    }

    function calculateInvoiceTotals() {
        let subtotal = 0;
        $('.invoice-item-row').each(function() {
            if ($(this).find('.item-checkbox').is(':checked')) {
                // Always calculate from quantity and unit price to ensure accuracy
                const quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
                const unitPrice = parseFloat($(this).find('.unit-price-input').val()) || 0;
                const lineTotal = quantity * unitPrice;
                
                // Update the display and hidden field
                $(this).find('.line-total-display').val(lineTotal.toFixed(2));
                $(this).find('.line-total-hidden').val(lineTotal.toFixed(2));
                
                subtotal += lineTotal;
            }
        });

        const depositApplied = parseFloat($('#deposit_applied').val()) || 0;
        const total = subtotal - depositApplied;

        $('#invoice-subtotal').text(subtotal.toFixed(2));
        $('#invoice-deposit').text(depositApplied.toFixed(2));
        $('#invoice-total').text(total.toFixed(2));
    }
});
</script>
@endpush
