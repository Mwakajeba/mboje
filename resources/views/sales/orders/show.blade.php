@extends('layouts.main')

@section('title', 'Order Details - ' . $order->order_number)

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Orders', 'url' => route('sales.orders.index'), 'icon' => 'bx bx-shopping-cart'],
            ['label' => $order->order_number, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <h6 class="mb-0 text-uppercase">ORDER DETAILS</h6>
        <hr />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Order Header -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h4 class="mb-1">Order #{{ $order->order_number }}</h4>
                                @if($order->proforma)
                                    <div class="text-muted small">
                                        From Proforma: 
                                        <a href="{{ route('sales.proformas.show', $order->proforma->encoded_id ?? \Vinkla\Hashids\Facades\Hashids::encode($order->proforma->id)) }}" class="text-primary fw-semibold">
                                            {{ $order->proforma->proforma_number }}
                                        </a>
                                    </div>
                                @endif
                                <p class="text-muted mb-0">Created on {{ $order->created_at->format('M d, Y \a\t h:i A') }}</p>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-{{ $order->status === 'draft' ? 'secondary' : ($order->status === 'pending_approval' ? 'warning' : ($order->status === 'approved' ? 'success' : ($order->status === 'in_production' ? 'info' : ($order->status === 'ready_for_delivery' ? 'primary' : 'danger')))) }} fs-6">
                                    {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                                </span>
                            </div>
                        </div>

                        <!-- Customer Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">Customer Information</h6>
                                <p><strong>Name:</strong> {{ $order->customer->name }}</p>
                                <p><strong>Email:</strong> {{ $order->customer->email ?? 'N/A' }}</p>
                                <p><strong>Phone:</strong> {{ $order->customer->phone ?? 'N/A' }}</p>
                                <p><strong>Address:</strong> {{ $order->customer->address ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">Order Information</h6>
                                <p><strong>Order Date:</strong> {{ $order->order_date->format('M d, Y') }}</p>
                                <p><strong>Expected Delivery:</strong> {{ $order->expected_delivery_date->format('M d, Y') }}</p>
                                <p><strong>Payment Terms:</strong> {{ $order->payment_terms ?? 'N/A' }}</p>
                                <p><strong>Created By:</strong> {{ $order->createdBy->name ?? 'N/A' }}</p>
                                <p><strong>Branch:</strong> {{ $order->branch->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-primary mb-3">Actions</h6>
                        <div class="d-grid gap-2">
                            @if($order->status === 'draft')
                                @can('edit sales order')
                                <a href="{{ route('sales.orders.edit', Hashids::encode($order->id)) }}" class="btn btn-warning">
                                    <i class="bx bx-edit me-1"></i> Edit Order
                                </a>
                                @endcan
                                @can('approve sales order')
                                <button type="button" class="btn btn-info" onclick="updateStatus('pending_approval')">
                                    <i class="bx bx-send me-1"></i> Submit for Approval
                                </button>
                                @endcan
                                @can('delete sales order')
                                <button type="button" class="btn btn-danger" onclick="deleteOrder()">
                                    <i class="bx bx-trash me-1"></i> Delete Order
                                </button>
                                @endcan
                            @endif

                            @if($order->status === 'pending_approval')
                                @can('approve sales order')
                                <button type="button" class="btn btn-success" onclick="updateStatus('approved')">
                                    <i class="bx bx-check-circle me-1"></i> Approve Order
                                </button>
                                <button type="button" class="btn btn-danger" onclick="updateStatus('cancelled')">
                                    <i class="bx bx-x-circle me-1"></i> Cancel Order
                                </button>
                                @endcan
                            @endif

                            @if($order->status === 'approved')
                                @php
                                    $existingInvoice = \App\Models\Sales\SalesInvoice::where('sales_order_id', $order->id)->first();
                                @endphp
                                @if(!$existingInvoice)
                                    <button type="button" class="btn btn-primary" onclick="convertToInvoice()">
                                        <i class="bx bx-receipt me-1"></i> Convert to Invoice
                                    </button>
                                @else
                                    <a href="{{ route('sales.invoices.show', $existingInvoice->encoded_id) }}" class="btn btn-primary">
                                        <i class="bx bx-receipt me-1"></i> View Invoice
                                    </a>
                                @endif
                            @elseif($order->status === 'converted_to_invoice')
                                @php
                                    $existingInvoice = \App\Models\Sales\SalesInvoice::where('sales_order_id', $order->id)->first();
                                    $existingDelivery = \App\Models\Sales\Delivery::where('sales_order_id', $order->id)->first();
                                    $existingCashSale = \App\Models\Sales\CashSale::where('notes', 'like', '%Converted from Sales Order: ' . $order->order_number . '%')->first();
                                @endphp
                                @if($existingInvoice)
                                    <a href="{{ route('sales.invoices.show', $existingInvoice->encoded_id) }}" class="btn btn-primary">
                                        <i class="bx bx-receipt me-1"></i> View Invoice
                                    </a>
                                @endif
                                @if($existingDelivery)
                                    <a href="{{ route('sales.deliveries.show', $existingDelivery->encoded_id) }}" class="btn btn-info">
                                        <i class="bx bx-package me-1"></i> View Delivery
                                    </a>
                                @endif
                                @if($existingCashSale)
                                    <a href="{{ route('sales.cash-sales.show', $existingCashSale->encoded_id) }}" class="btn btn-success">
                                        <i class="bx bx-money me-1"></i> View Cash Sale
                                    </a>
                                @endif
                            @endif

                            <a href="{{ route('sales.orders.export-pdf', Hashids::encode($order->id)) }}" class="btn btn-danger" target="_blank">
                                <i class="bx bx-file me-1"></i> Export PDF
                            </a>

                            <button type="button" class="btn btn-info" onclick="printOrder()">
                                <i class="bx bx-printer me-1"></i> Print Order
                            </button>

                            <a href="{{ route('sales.orders.index') }}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="card">
            <div class="card-body">
                <h6 class="text-primary mb-3">Order Items</h6>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Item</th>
                                <th>Code</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">VAT Rate</th>
                                <th class="text-end">VAT Amount</th>
                                <th class="text-end">Discount</th>
                                <th class="text-end">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <strong>{{ $item->item_name }}</strong>
                                        @if($item->inventoryItem)
                                            <br><small class="text-muted">{{ $item->inventoryItem->description }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $item->item_code }}</td>
                                    <td class="text-end">
                                        @php
                                            $currentStock = $item->inventoryItem->current_stock ?? null;
                                            $isOver = $currentStock !== null && $item->quantity > $currentStock;
                                        @endphp
                                        <span class="{{ $isOver ? 'text-danger fw-bold' : '' }}" title="{{ $currentStock !== null ? 'Stock: ' . $currentStock : '' }}">
                                            {{ number_format($item->quantity, 2) }}
                                        </span>
                                    </td>
                                    <td class="text-end">TZS {{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-end">{{ $item->vat_rate > 0 ? $item->vat_rate . '%' : 'N/A' }}</td>
                                    <td class="text-end">TZS {{ number_format($item->vat_amount, 2) }}</td>
                                    <td class="text-end">
                                        @if($item->discount_amount > 0)
                                            TZS {{ number_format($item->discount_amount, 2) }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="text-end"><strong>TZS {{ number_format($item->total, 2) }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="row">
            <div class="col-lg-8">
                @if($order->notes || $order->terms_conditions || $order->attachment)
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-note me-2"></i>Notes & Terms
                                @if($order->attachment)
                                    <a href="{{ asset('storage/' . $order->attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary float-end">
                                        <i class="bx bx-paperclip me-1"></i>View Attachment
                                    </a>
                                @endif
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($order->notes)
                            <div class="mb-3">
                                <h6>Notes:</h6>
                                <p class="mb-0">{{ $order->notes }}</p>
                            </div>
                            @endif
                            @if($order->terms_conditions)
                            <div>
                                <h6>Terms & Conditions:</h6>
                                <p class="mb-0">{{ $order->terms_conditions }}</p>
                            </div>
                            @endif
                            @if(!$order->notes && !$order->terms_conditions && $order->attachment)
                            <p class="mb-0 text-muted">An attachment has been uploaded for this order.</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-primary mb-3">Summary</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>TZS {{ number_format($order->subtotal, 2) }}</span>
                        </div>
                        @if($order->discount_amount > 0)
                            <div class="d-flex justify-content-between mb-2">
                                <span>Discount:</span>
                                <span class="text-danger">-TZS {{ number_format($order->discount_amount, 2) }}</span>
                            </div>
                        @endif
                        @if($order->vat_amount > 0)
                            <div class="d-flex justify-content-between mb-2">
                                <span>VAT ({{ $order->vat_rate }}%):</span>
                                <span>TZS {{ number_format($order->vat_amount, 2) }}</span>
                            </div>
                        @endif
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total Amount:</strong>
                            <strong class="text-primary fs-5">TZS {{ number_format($order->total_amount, 2) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function updateStatus(status) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'Mark order as ' + status.toUpperCase() + '?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, proceed'
    }).then((result)=>{
        if(!result.isConfirmed) return;
        $.ajax({
            url: '{{ route("sales.orders.update-status", Hashids::encode($order->id)) }}',
            type: 'PATCH',
            data: { status: status, _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    Swal.fire('Updated','Status updated successfully','success').then(()=>location.reload());
                } else {
                    Swal.fire('Error', response.message || 'Failed to update status', 'error');
                }
            },
            error: function() { Swal.fire('Error', 'Error updating status. Please try again.', 'error'); }
        });
    });
}

function deleteOrder() {
    Swal.fire({
        title: 'Delete Order?',
        text: 'This will soft-delete {{ $order->order_number }}. You can restore later if needed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete'
    }).then((result)=>{
        if(!result.isConfirmed) return;
        $.ajax({
            url: '{{ route("sales.orders.destroy", Hashids::encode($order->id)) }}',
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    Swal.fire('Deleted','Order deleted successfully','success').then(()=>{ window.location.href = '{{ route("sales.orders.index") }}'; });
                } else {
                    Swal.fire('Error', response.message || 'Failed to delete', 'error');
                }
            },
            error: function() { Swal.fire('Error', 'Error deleting order. Please try again.', 'error'); }
        });
    });
}

function convertToInvoice() {
    Swal.fire({
        title: 'Convert to Invoice',
        text: 'Are you sure you want to convert this order to an invoice?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, convert it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (!result.isConfirmed) return;
        
        $.ajax({
            url: '{{ route("sales.orders.convert-to-invoice", Hashids::encode($order->id)) }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Converted Successfully!',
                        text: 'Order has been converted to invoice successfully.',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'View Invoice',
                        cancelButtonText: 'Stay Here'
                    }).then((result) => {
                        if (result.isConfirmed && response.redirect_url) {
                            window.location.href = response.redirect_url;
                        }
                    });
                } else {
                    Swal.fire('Error', response.message || 'Failed to convert order to invoice', 'error');
                }
            },
            error: function(xhr) {
                console.error('AJAX Error:', xhr);
                Swal.fire('Error', 'Error converting order to invoice. Please try again.', 'error');
            }
        });
    });
}

function convertToDelivery() {
    Swal.fire({
        title: 'Convert to Delivery',
        text: 'Are you sure you want to convert this order to a delivery note?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, convert it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (!result.isConfirmed) return;
        
        $.ajax({
            url: '{{ route("sales.orders.convert-to-delivery", Hashids::encode($order->id)) }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Converted Successfully!',
                        text: 'Order has been converted to delivery successfully.',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'View Delivery',
                        cancelButtonText: 'Stay Here'
                    }).then((result) => {
                        if (result.isConfirmed && response.redirect_url) {
                            window.location.href = response.redirect_url;
                        }
                    });
                } else {
                    Swal.fire('Error', response.message || 'Failed to convert order to delivery', 'error');
                }
            },
            error: function(xhr) {
                console.error('AJAX Error:', xhr);
                Swal.fire('Error', 'Error converting order to delivery. Please try again.', 'error');
            }
        });
    });
}

function convertToCash() {
    Swal.fire({
        title: 'Convert to Cash Sale',
        text: 'Are you sure you want to convert this order to a cash sale?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, convert it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (!result.isConfirmed) return;
        
        $.ajax({
            url: '{{ route("sales.orders.convert-to-cash", Hashids::encode($order->id)) }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Converted Successfully!',
                        text: 'Order has been converted to cash sale successfully.',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'View Transaction',
                        cancelButtonText: 'Stay Here'
                    }).then((result) => {
                        if (result.isConfirmed && response.transaction_url) {
                            window.open(response.transaction_url, '_blank');
                        }
                    });
                } else {
                    Swal.fire('Error', response.message || 'Failed to convert order to cash sale', 'error');
                }
            },
            error: function(xhr) {
                console.error('AJAX Error:', xhr);
                Swal.fire('Error', 'Error converting order to cash sale. Please try again.', 'error');
            }
        });
    });
}

function printOrder() {
    // Create a new window for printing
    const printWindow = window.open('', '_blank', 'width=800,height=600');
    
    // Get the order data
    const orderData = {
        orderNumber: '{{ $order->order_number }}',
        orderDate: '{{ $order->order_date->format('M d, Y') }}',
        expectedDelivery: '{{ $order->expected_delivery_date->format('M d, Y') }}',
        customerName: '{{ $order->customer->name }}',
        customerEmail: '{{ $order->customer->email ?? 'N/A' }}',
        customerPhone: '{{ $order->customer->phone ?? 'N/A' }}',
        customerAddress: '{{ $order->customer->address ?? 'N/A' }}',
        createdBy: '{{ $order->createdBy->name ?? 'N/A' }}',
        branch: '{{ $order->branch->name ?? 'N/A' }}',
        status: '{{ strtoupper(str_replace('_', ' ', $order->status)) }}',
        notes: '{{ $order->notes ?? 'N/A' }}',
        termsConditions: '{{ $order->terms_conditions ?? 'N/A' }}',
        subtotal: '{{ number_format($order->subtotal, 2) }}',
        discountAmount: '{{ number_format($order->discount_amount, 2) }}',
        vatAmount: '{{ number_format($order->vat_amount, 2) }}',
        vatRate: '{{ $order->vat_rate }}',
        totalAmount: '{{ number_format($order->total_amount, 2) }}',
        items: [
            @foreach($order->items as $item)
            {
                itemName: '{{ $item->item_name }}',
                itemCode: '{{ $item->item_code }}',
                quantity: '{{ number_format($item->quantity, 2) }}',
                unitPrice: '{{ number_format($item->unit_price, 2) }}',
                vatRate: '{{ $item->vat_rate > 0 ? $item->vat_rate . '%' : 'N/A' }}',
                vatAmount: '{{ number_format($item->vat_amount, 2) }}',
                discountAmount: '{{ $item->discount_amount > 0 ? number_format($item->discount_amount, 2) : 'N/A' }}',
                total: '{{ number_format($item->total, 2) }}'
            }{{ $loop->last ? '' : ',' }}
            @endforeach
        ]
    };

    // Create the print content
    const printContent = `
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Sales Order - ${orderData.orderNumber}</title>
            <style>
                @page {
                    size: A5;
                    margin: 0.5in 0.5in 0.5in 0.5in;
                }

                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: 'Arial', sans-serif;
                    font-size: 10px;
                    color: #000;
                    margin: 0;
                    padding: 0;
                    width: 100%;
                    min-height: 100vh;
                }

                .print-container {
                    width: 100%;
                    max-width: 100%;
                    margin: 0;
                    padding: 0;
                    position: relative;
                    top: 0;
                    left: 0;
                }

                @media print {
                    @page {
                        size: A4;
                        margin: 0.5in 0.5in 0.5in 0.5in;
                    }
                    
                    body {
                        margin: 0 !important;
                        padding: 0 !important;
                    }
                    
                    .print-container {
                        margin: 0 !important;
                        padding: 0 !important;
                        position: relative !important;
                        top: 0 !important;
                        left: 0 !important;
                    }
                }

                /* === HEADER === */
                .header {
                    text-align: center;
                    border-bottom: 1px solid #000;
                    margin-bottom: 5px;
                    padding-bottom: 5px;
                    margin-top: 0;
                    padding-top: 0;
                }

                .company-name {
                    color: #b22222;
                    font-size: 15px;
                    font-weight: bold;
                    margin: 0;
                    letter-spacing: 0.5px;
                }

                .company-details {
                    font-size: 8px;
                    line-height: 1.3;
                    margin-top: 2px;
                }

                /* === ORDER TITLE === */
                .order-title {
                    text-align: center;
                    font-weight: bold;
                    font-size: 11px;
                    margin: 8px 0;
                    text-transform: uppercase;
                }

                /* === INFO SECTION === */
                .order-details {
                    display: flex;
                    justify-content: space-between;
                    font-size: 9px;
                    margin-bottom: 8px;
                }

                .bill-to {
                    flex: 1;
                }

                .order-info {
                    flex: 1;
                    text-align: right;
                }

                .order-info table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 8px;
                }

                .order-info td {
                    border: 1px solid #000;
                    padding: 2px;
                }

                .order-info td:first-child {
                    font-weight: bold;
                    background-color: #f9f9f9;
                }

                /* === ITEMS TABLE === */
                .items-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 8px;
                }

                .items-table th,
                .items-table td {
                    border: 1px solid #000;
                    padding: 3px;
                    font-size: 8px;
                }

                .items-table th {
                    background-color: #f2f2f2;
                    font-weight: bold;
                    text-align: center;
                }

                .text-center {
                    text-align: center;
                }

                .text-right {
                    text-align: right;
                }

                /* === TOTALS === */
                .summary {
                    margin-top: 5px;
                    font-size: 9px;
                }

                .summary-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 2px;
                }

                .summary-row.total {
                    border-top: 1px solid #000;
                    font-weight: bold;
                    padding-top: 3px;
                }

                /* === TERMS & FOOTER === */
                .payment-terms {
                    font-size: 8px;
                    margin-top: 8px;
                }

                .payment-terms h6 {
                    margin: 0 0 2px 0;
                    font-weight: bold;
                    text-transform: uppercase;
                }

                .footer {
                    font-size: 8px;
                    margin-top: 10px;
                }

                .signature-line {
                    margin-top: 8px;
                }

                .page-info {
                    text-align: center;
                    font-size: 8px;
                    margin-top: 8px;
                }
            </style>
        </head>
        <body>
            <div class="print-container">
                <div class="header">
                    <div class="company-info">
                        <h1 class="company-name">SMARTACCOUNTING</h1>
                        <div class="company-details">
                            <div><strong>P.O. Box:</strong> P.O.BOX 00000, City, Country</div>
                            <div><strong>Phone:</strong> +255 000 000 000</div>
                            <div><strong>Email:</strong> company@email.com</div>
                        </div>
                    </div>
                </div>

                <div class="order-title">Sales Order</div>

                <div class="order-details">
                    <div class="bill-to">
                        <div class="field-label">Bill To:</div>
                        <div class="field-value">${orderData.customerName}</div>
                        <br>
                        <div class="field-value">${orderData.customerAddress}</div>
                        <div class="field-label">User name:</div>
                        <div class="field-value">${orderData.createdBy}</div>
                    </div>
                    <div class="order-info">
                        <table style="width: 100%; border-collapse: collapse; font-size: 8px;">
                            <tr>
                                <td style="padding: 2px; border: 1px solid #000; font-weight: bold; width: 20%;">Order No:</td>
                                <td style="padding: 2px; border: 1px solid #000; width: 30%;">${orderData.orderNumber}</td>
                                <td style="padding: 2px; border: 1px solid #000; font-weight: bold; width: 20%;">Date:</td>
                                <td style="padding: 2px; border: 1px solid #000; width: 30%;">${orderData.orderDate}</td>
                            </tr>
                            @if($order->proforma)
                            <tr>
                                <td style=\"padding: 2px; border: 1px solid #000; font-weight: bold;\">History:</td>
                                <td style=\"padding: 2px; border: 1px solid #000;\" colspan=\"3\">
                                    From Proforma:
                                    <a href=\"{{ route('sales.proformas.show', \Vinkla\Hashids\Facades\Hashids::encode($order->proforma->id)) }}\" style=\"color: #0d6efd; text-decoration: none;\">
                                        {{ $order->proforma->proforma_number }}
                                    </a>
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">Currency:</td>
                                <td style="padding: 2px; border: 1px solid #000;">TZS</td>
                                <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">Ex Rate:</td>
                                <td style="padding: 2px; border: 1px solid #000;">1.00</td>
                            </tr>
                            <tr>
                                <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">TIN:</td>
                                <td style="padding: 2px; border: 1px solid #000;">N/A</td>
                                <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">VRN:</td>
                                <td style="padding: 2px; border: 1px solid #000;">N/A</td>
                            </tr>
                            <tr>
                                <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">Time:</td>
                                <td style="padding: 2px; border: 1px solid #000;" colspan="3">${new Date().toLocaleTimeString()}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 8%;">Qty</th>
                            <th style="width: 45%;">Description</th>
                            <th style="width: 12%;">VAT Rate</th>
                            <th style="width: 15%;">Unit price</th>
                            <th style="width: 10%;">VAT Amt</th>
                            <th style="width: 10%;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${orderData.items.map((item) => `
                            <tr>
                                <td class="text-center">${item.quantity}</td>
                                <td>${item.itemName}</td>
                                <td class="text-center">${item.vatRate}</td>
                                <td class="text-right">${item.unitPrice}</td>
                                <td class="text-right">${item.vatAmount}</td>
                                <td class="text-right">${item.total}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>

                <div class="summary">
                    <div class="summary-row">
                        <span>Amount without tax:</span>
                        <span>${orderData.subtotal}</span>
                    </div>
                    <div class="summary-row">
                        <span>Total Tax:</span>
                        <span>${orderData.vatAmount}</span>
                    </div>
                    <div class="summary-row">
                        <span>Total Discount:</span>
                        <span>${orderData.discountAmount}</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total Amount:</span>
                        <span>${orderData.totalAmount}</span>
                    </div>
                </div>

                ${orderData.notes !== 'N/A' ? `
                <div class="payment-terms">
                    <h6>Notes</h6>
                    <div>${orderData.notes}</div>
                </div>
                ` : ''}

                ${orderData.termsConditions !== 'N/A' ? `
                <div class="payment-terms">
                    <h6>Terms & Conditions</h6>
                    <div>${orderData.termsConditions}</div>
                </div>
                ` : ''}

                <div class="footer">
                    <div class="signature-line">
                        <strong>Signature................................................</strong>
                    </div>
                    <div class="page-info">
                        Generated on ${new Date().toLocaleString()}
                    </div>
                </div>
            </div>
        </body>
        </html>
    `;

    // Write content to the new window
    printWindow.document.write(printContent);
    printWindow.document.close();

    // Wait for content to load, then print
    printWindow.onload = function() {
        printWindow.print();
        printWindow.close();
    };
}
</script>
@endpush 