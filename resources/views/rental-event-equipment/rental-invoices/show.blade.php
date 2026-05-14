@extends('layouts.main')

@section('title', 'View Rental Invoice')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Rental Invoices', 'url' => route('rental-event-equipment.rental-invoices.index'), 'icon' => 'bx bx-receipt'],
            ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <h6 class="mb-0 text-uppercase">RENTAL INVOICE DETAILS</h6>
        <hr />

        @php
            $balanceDue = $invoice->total_amount - ($invoice->deposit_applied ?? 0);
        @endphp

        <!-- Header actions (similar to sales invoice show) -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Rental Invoice Details</h4>
                    <div class="page-title-right">
                        @if($invoice->status === 'draft')
                            <a href="{{ route('rental-event-equipment.rental-invoices.edit', $invoice->getRouteKey()) }}" class="btn btn-warning btn-sm me-1">
                                <i class="bx bx-edit me-1"></i>Edit Invoice
                            </a>
                            <button type="button" class="btn btn-success btn-sm me-1"
                                    onclick="approveInvoice('{{ $invoice->getRouteKey() }}', '{{ $invoice->invoice_number }}')">
                                <i class="bx bx-check me-1"></i>Approve &amp; Send
                            </button>
                            <button type="button" class="btn btn-danger btn-sm me-1"
                                    onclick="deleteInvoice('{{ $invoice->getRouteKey() }}', '{{ $invoice->invoice_number }}')">
                                <i class="bx bx-trash me-1"></i>Delete
                            </button>
                        @elseif($balanceDue > 0 && $invoice->status !== 'cancelled')
                            <a href="{{ route('rental-event-equipment.rental-invoices.payment', $invoice->getRouteKey()) }}"
                               class="btn btn-success btn-sm me-1">
                                <i class="bx bx-money me-1"></i>Add Payment
                            </a>
                        @endif

                        <a href="{{ route('rental-event-equipment.rental-invoices.export-pdf', $invoice->getRouteKey()) }}"
                           class="btn btn-danger btn-sm me-1" target="_blank">
                            <i class="bx bxs-file-pdf me-1"></i>Export Invoice PDF
                        </a>
                        @if($invoice->deposit_applied > 0)
                            <a href="{{ route('rental-event-equipment.rental-invoices.export-receipt-pdf', $invoice->getRouteKey()) }}"
                               class="btn btn-warning btn-sm me-1" target="_blank">
                                <i class="bx bxs-file-pdf me-1"></i>Export Receipt PDF
                            </a>
                        @endif
                        <a href="{{ route('rental-event-equipment.rental-invoices.index') }}" class="btn btn-secondary btn-sm">
                            <i class="bx bx-arrow-back me-1"></i>Back to Invoices
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice & Customer information -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-success mb-3">Invoice Information</h5>
                                <table class="table table-borderless mb-0">
                                    <tr>
                                        <td width="150"><strong>Invoice Number:</strong></td>
                                        <td> {{ $invoice->invoice_number }} </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Contract:</strong></td>
                                        <td>
                                            @if($invoice->contract)
                                                <a href="{{ route('rental-event-equipment.contracts.show', $invoice->contract) }}"
                                                   class="text-primary fw-semibold">
                                                    {{ $invoice->contract->contract_number }}
                                                </a>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Invoice Date:</strong></td>
                                        <td>{{ $invoice->invoice_date?->format('M d, Y') ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Due Date:</strong></td>
                                        <td>
                                            {{ $invoice->due_date?->format('M d, Y') ?? 'N/A' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>
                                            @php
                                                $statusBadge = match($invoice->status) {
                                                    'draft' => 'secondary',
                                                    'sent' => 'info',
                                                    'paid' => 'success',
                                                    'cancelled' => 'danger',
                                                    default => $balanceDue > 0 ? 'warning' : 'success'
                                                };
                                                $statusText = $balanceDue <= 0 && $invoice->status !== 'paid'
                                                    ? 'Paid'
                                                    : ucfirst($invoice->status);
                                            @endphp
                                            <span class="badge bg-{{ $statusBadge }}">{{ $statusText }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Paid Amount:</strong></td>
                                        <td class="text-success">TZS {{ number_format($invoice->deposit_applied ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Balance Due:</strong></td>
                                        <td>
                                            <span class="badge bg-{{ $balanceDue > 0 ? 'warning' : 'success' }}">
                                                TZS {{ number_format($balanceDue, 2) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @if($invoice->creator)
                                        <tr>
                                            <td><strong>Created By:</strong></td>
                                            <td>{{ $invoice->creator->name ?? 'N/A' }}</td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5 class="text-success mb-3">Customer Information</h5>
                                <table class="table table-borderless mb-0">
                                    <tr>
                                        <td width="150"><strong>Customer:</strong></td>
                                        <td>{{ $invoice->customer->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Phone:</strong></td>
                                        <td>{{ $invoice->customer->phone ?? 'N/A' }}</td>
                                    </tr>
                                    @if(!empty($invoice->customer->email))
                                        <tr>
                                            <td><strong>Email:</strong></td>
                                            <td>{{ $invoice->customer->email }}</td>
                                        </tr>
                                    @endif
                                    @if(!empty($invoice->customer->address))
                                        <tr>
                                            <td><strong>Address:</strong></td>
                                            <td>{{ $invoice->customer->address }}</td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Items -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="bx bx-list-ul me-2"></i>Invoice Items
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                <tr>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Line Total</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($invoice->items as $item)
                                    <tr>
                                        <td>
                                            @php
                                                $typeBadge = match($item->item_type) {
                                                    'equipment' => 'primary',
                                                    'damage_charge' => 'danger',
                                                    'loss_charge' => 'warning',
                                                    'service' => 'info',
                                                    default => 'secondary'
                                                };
                                                $typeLabel = match($item->item_type) {
                                                    'equipment' => 'Equipment',
                                                    'damage_charge' => 'Damage',
                                                    'loss_charge' => 'Loss',
                                                    'service' => 'Service',
                                                    default => ucfirst($item->item_type)
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $typeBadge }}">{{ $typeLabel }}</span>
                                        </td>
                                        <td>
                                            {{ $item->description }}
                                            @if($item->equipment)
                                                <br><small class="text-muted">Code: {{ $item->equipment->equipment_code ?? 'N/A' }}</small>
                                            @endif
                                            @if($item->notes)
                                                <br><small class="text-muted"><i>{{ $item->notes }}</i></small>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ number_format($item->quantity, 0) }}</td>
                                        <td class="text-end">TZS {{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-end">TZS {{ number_format($item->line_total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No items found</td>
                                    </tr>
                                @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                @if($invoice->rental_charges > 0)
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Rental Charges:</strong></td>
                                        <td class="text-end"><strong>TZS {{ number_format($invoice->rental_charges, 2) }}</strong></td>
                                    </tr>
                                @endif
                                @if($invoice->damage_charges > 0)
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Damage Charges:</strong></td>
                                        <td class="text-end"><strong>TZS {{ number_format($invoice->damage_charges, 2) }}</strong></td>
                                    </tr>
                                @endif
                                @if($invoice->loss_charges > 0)
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Loss Charges:</strong></td>
                                        <td class="text-end"><strong>TZS {{ number_format($invoice->loss_charges, 2) }}</strong></td>
                                    </tr>
                                @endif
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end"><strong>TZS {{ number_format($invoice->subtotal, 2) }}</strong></td>
                                </tr>
                                @if($invoice->tax_amount > 0)
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Tax:</strong></td>
                                        <td class="text-end"><strong>TZS {{ number_format($invoice->tax_amount, 2) }}</strong></td>
                                    </tr>
                                @endif
                                @if($invoice->deposit_applied > 0)
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Deposit Applied:</strong></td>
                                        <td class="text-end">
                                            <strong class="text-success">-TZS {{ number_format($invoice->deposit_applied, 2) }}</strong>
                                        </td>
                                    </tr>
                                @endif
                                <tr class="table-success">
                                    <td colspan="4" class="text-end"><strong>Total Amount:</strong></td>
                                    <td class="text-end"><strong>TZS {{ number_format($invoice->total_amount, 2) }}</strong></td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes & Summary -->
        <div class="row">
            <div class="col-md-8">
                @if($invoice->notes)
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bx bx-note me-2"></i>Notes</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">{{ $invoice->notes }}</p>
                        </div>
                    </div>
                @endif
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bx bx-calculator me-2"></i>Invoice Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>TZS {{ number_format($invoice->subtotal, 2) }}</span>
                        </div>
                        @if($invoice->tax_amount > 0)
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax:</span>
                                <span>TZS {{ number_format($invoice->tax_amount, 2) }}</span>
                            </div>
                        @endif
                        @if($invoice->rental_charges > 0)
                            <div class="d-flex justify-content-between mb-2">
                                <span>Rental Charges:</span>
                                <span>TZS {{ number_format($invoice->rental_charges, 2) }}</span>
                            </div>
                        @endif
                        @if($invoice->damage_charges > 0)
                            <div class="d-flex justify-content-between mb-2">
                                <span>Damage Charges:</span>
                                <span>TZS {{ number_format($invoice->damage_charges, 2) }}</span>
                            </div>
                        @endif
                        @if($invoice->loss_charges > 0)
                            <div class="d-flex justify-content-between mb-2">
                                <span>Loss Charges:</span>
                                <span>TZS {{ number_format($invoice->loss_charges, 2) }}</span>
                            </div>
                        @endif
                        @if($invoice->deposit_applied > 0)
                            <div class="d-flex justify-content-between mb-2">
                                <span>Deposit Applied:</span>
                                <span class="text-success">-TZS {{ number_format($invoice->deposit_applied, 2) }}</span>
                            </div>
                        @endif
                        <hr>
                        <div class="d-flex justify-content-between fw-bold mb-2">
                            <span>Total Amount:</span>
                            <span>TZS {{ number_format($invoice->total_amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Paid:</span>
                            <span>TZS {{ number_format($invoice->deposit_applied ?? 0, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold text-primary">
                            <span>Balance Due:</span>
                            <span>TZS {{ number_format($balanceDue, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        @if(isset($receipts) && $receipts->count() > 0)
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bx bx-history me-2"></i>Payment History</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Payment Method</th>
                                        <th>Bank Account</th>
                                        <th>Reference Number</th>
                                        <th>Description</th>
                                        <th>Recorded By</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($receipts as $receipt)
                                        <tr>
                                            <td>{{ $receipt->date->format('M d, Y') }}</td>
                                            <td class="text-end"><strong>TZS {{ number_format($receipt->amount, 2) }}</strong></td>
                                            <td>{{ ucfirst(str_replace('_', ' ', $receipt->payment_method ?? 'N/A')) }}</td>
                                            <td>{{ $receipt->bankAccount->name ?? 'N/A' }}</td>
                                            <td>{{ $receipt->reference_number ?? 'N/A' }}</td>
                                            <td>{{ $receipt->description ?? 'N/A' }}</td>
                                            <td>{{ $receipt->user->name ?? 'N/A' }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('rental-event-equipment.rental-invoices.receipt.edit', Hashids::encode($receipt->id)) }}"
                                                       class="btn btn-sm btn-outline-warning" title="Edit">
                                                        <i class="bx bx-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-receipt-btn"
                                                            data-receipt-id="{{ Hashids::encode($receipt->id) }}"
                                                            data-invoice-id="{{ $invoice->getRouteKey() }}"
                                                            title="Delete">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                    <tfoot class="table-light">
                                    <tr>
                                        <th colspan="7" class="text-end">Total Payments:</th>
                                        <th class="text-end">TZS {{ number_format($receipts->sum('amount'), 2) }}</th>
                                    </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
@php
    use Vinkla\Hashids\Facades\Hashids;
@endphp
<script>
$(document).ready(function() {
    // Handle delete receipt button
    $(document).on('click', '.delete-receipt-btn', function() {
        const receiptId = $(this).data('receipt-id');
        const invoiceId = $(this).data('invoice-id');
        
        Swal.fire({
            title: 'Delete Receipt?',
            text: 'Are you sure you want to delete this receipt? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while we delete the receipt.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                const form = $('<form>', {
                    'method': 'POST',
                    'action': '{{ url("rental-event-equipment/rental-invoices/receipt") }}/' + receiptId
                });
                
                form.append($('<input>', {
                    'type': 'hidden',
                    'name': '_token',
                    'value': '{{ csrf_token() }}'
                }));
                
                form.append($('<input>', {
                    'type': 'hidden',
                    'name': '_method',
                    'value': 'DELETE'
                }));
                
                $('body').append(form);
                form.submit();
            }
        });
    });
});

// Approve & Send Invoice
function approveInvoice(invoiceId, invoiceNumber) {
    Swal.fire({
        title: 'Approve & Send Invoice?',
        html: `Are you sure you want to approve and send invoice <strong>${invoiceNumber}</strong>?<br><br>This will change the status from draft to sent.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Approve & Send',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait while we approve the invoice.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            const form = $('<form>', {
                'method': 'POST',
                'action': '{{ url("rental-event-equipment/rental-invoices") }}/' + invoiceId + '/update-status'
            });
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': '_token',
                'value': '{{ csrf_token() }}'
            }));
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': '_method',
                'value': 'PATCH'
            }));
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'status',
                'value': 'sent'
            }));
            
            $('body').append(form);
            form.submit();
        }
    });
}

// Delete Invoice
function deleteInvoice(invoiceId, invoiceNumber) {
    Swal.fire({
        title: 'Delete Invoice?',
        html: `Are you sure you want to delete invoice <strong>${invoiceNumber}</strong>?<br><br>This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait while we delete the invoice.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            const form = $('<form>', {
                'method': 'POST',
                'action': '{{ url("rental-event-equipment/rental-invoices") }}/' + invoiceId
            });
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': '_token',
                'value': '{{ csrf_token() }}'
            }));
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': '_method',
                'value': 'DELETE'
            }));
            
            $('body').append(form);
            form.submit();
        }
    });
}
</script>
@endpush
