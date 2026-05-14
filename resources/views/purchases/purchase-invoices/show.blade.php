@extends('layouts.main')

@section('title', 'Invoice Details')

@section('content')
@php
    // Get functional currency from system settings or company default
    $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', auth()->user()->company->functional_currency ?? 'TZS');
    
    // Get invoice currency directly from database
    // If currency field is null, empty, or doesn't exist, use TZS as fallback
    $invoiceCurrency = $invoice->currency ?? null;
    
    // If currency is not set in database, use TZS
    if (empty($invoiceCurrency) || trim($invoiceCurrency) === '') {
        $invoiceCurrency = 'TZS';
    }
    
    // Ensure currency is uppercase for consistency
    $invoiceCurrency = strtoupper(trim($invoiceCurrency));
    
    $currencyDisplay = $invoiceCurrency; // Currency code to display with amounts
@endphp
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchase Management', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Purchase Invoices', 'url' => route('purchases.purchase-invoices.index'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Invoice Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <!-- Alert for Manual Job Processing -->
        @php
            // Show alert if:
            // 1. Status is 'draft' (processing not started)
            // 2. Status is 'open' but total_amount is 0 and items exist (processing incomplete)
            $hasItems = $invoice->items && $invoice->items->count() > 0;
            $isDraft = $invoice->status === 'draft';
            $incompleteProcessing = $invoice->status === 'open' && $invoice->total_amount == 0 && $hasItems;
            $needsProcessing = $hasItems && ($isDraft || $incompleteProcessing);
        @endphp
        @if($needsProcessing)
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-danger d-flex align-items-center border border-danger border-2" role="alert" id="reprocess-alert" style="box-shadow: 0 0 10px rgba(220, 53, 69, 0.3);">
                    <i class="bx bx-error-circle me-3" style="font-size: 2rem;"></i>
                    <div class="flex-grow-1">
                        <strong class="fs-5">⚠️ Items Processing Required!</strong>
                        <p class="mb-2 mt-2">This invoice has items that need to be processed. If items are not processing automatically on the server, you can manually trigger the process using the keyboard shortcut or button below.</p>
                        <p class="mb-0">
                            <strong>Keyboard Shortcut:</strong>
                            <kbd class="bg-dark text-white px-3 py-2 rounded ms-2 me-1" style="font-size: 0.9rem;">Ctrl</kbd> +
                            <kbd class="bg-dark text-white px-3 py-2 rounded mx-1" style="font-size: 0.9rem;">Shift</kbd> +
                            <kbd class="bg-dark text-white px-3 py-2 rounded mx-1" style="font-size: 0.9rem;">P</kbd>
                            <span class="ms-3">or</span>
                            <button type="button" class="btn btn-danger btn-sm ms-2" id="reprocess-btn" onclick="reprocessInvoiceItems()">
                                <i class="bx bx-refresh me-1"></i>Click to Process Items Now
                            </button>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Invoice Details</h4>
                    <div class="page-title-right">
                        @php
                            $computedBalance = ($balanceDue ?? max(0, ($invoice->total_amount ?? 0) - ($totalPaid ?? 0)));
                            $isClosed = ($invoice->status === 'closed') || ($computedBalance <= 0);
                        @endphp
                        @can('record purchase payment')
                        <a href="{{ $isClosed ? '#' : route('purchases.purchase-invoices.payment-form', $invoice->encoded_id) }}" class="btn btn-success me-1 {{ $isClosed ? 'disabled' : '' }}" {{ $isClosed ? 'aria-disabled=true tabindex=-1' : '' }}><i class="bx bx-money me-1"></i>Record Payment</a>
                        @endcan
                        @can('edit purchase invoices')
                        <a href="{{ $isClosed ? '#' : route('purchases.purchase-invoices.edit', $invoice->encoded_id) }}" class="btn btn-primary me-1 {{ $isClosed ? 'disabled' : '' }}" {{ $isClosed ? 'aria-disabled=true tabindex=-1' : '' }}><i class="bx bx-edit me-1"></i>Edit Invoice</a>
                        @endcan
                        @if(optional($invoice->supplier)->email)
                        <button type="button" class="btn btn-info me-1" onclick="sendInvoiceEmail()">
                            <i class="bx bx-envelope me-1"></i>Send Email
                        </button>
                        @endif
                        <a href="{{ route('purchases.purchase-invoices.export-pdf', $invoice->encoded_id) }}" class="btn btn-info me-1" target="_blank"><i class="bx bx-download me-1"></i>Export PDF</a>
                        <div class="btn-group me-1">
                            <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bx bx-copy me-1"></i>Copy To
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('purchases.quotations.create-from-invoice', $invoice->encoded_id) }}"><i class="bx bx-file me-2"></i>Purchase Quote</a></li>
                                <li><a class="dropdown-item" href="{{ route('purchases.orders.create-from-invoice', $invoice->encoded_id) }}"><i class="bx bx-cart me-2"></i>Purchase Order</a></li>
                                <li><a class="dropdown-item" href="{{ route('purchases.grn.create-from-invoice', $invoice->encoded_id) }}"><i class="bx bx-package me-2"></i>Goods Receipt</a></li>
                                <li><a class="dropdown-item" href="{{ route('purchases.debit-notes.create-from-invoice', $invoice->encoded_id) }}"><i class="bx bx-undo me-2"></i>Debit Note</a></li>
                            </ul>
                        </div>
                        <a href="{{ route('purchases.purchase-invoices.index') }}" class="btn btn-secondary"><i class="bx bx-arrow-back me-1"></i>Back to Invoices</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Header -->
        <div class="row">
            <div class="col-12">
        <div class="card">
            <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">Invoice Information</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="150"><strong>Invoice Number:</strong></td>
                                        <td>{{ $invoice->invoice_number }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>{{ ucfirst($invoice->status ?? 'open') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Invoice Date:</strong></td>
                                        <td>{{ $invoice->invoice_date ? $invoice->invoice_date->format('d M Y') : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Due Date:</strong></td>
                                        <td>
                                            {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : 'N/A' }}
                                            @if($invoice->is_overdue)
                                                <span class="badge bg-danger ms-2">Overdue</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Payment Terms:</strong></td>
                                        <td>{{ $invoice->payment_terms_text }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Currency:</strong></td>
                                        <td><span class="badge bg-info">{{ $invoiceCurrency }}</span></td>
                                    </tr>
                                    @if($invoice->exchange_rate && $invoice->exchange_rate != 1)
                                    <tr>
                                        <td><strong>Exchange Rate:</strong></td>
                                        <td>1 {{ $invoiceCurrency }} = {{ number_format($invoice->exchange_rate, 6) }} {{ $functionalCurrency }}</td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">Supplier Information</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="150"><strong>Supplier:</strong></td>
                                        <td>{{ optional($invoice->supplier)->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Phone:</strong></td>
                                        <td>{{ optional($invoice->supplier)->phone ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td>{{ optional($invoice->supplier)->email ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Address:</strong></td>
                                        <td>{{ optional($invoice->supplier)->address ?? 'N/A' }}</td>
                                    </tr>
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
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-list-ul me-2"></i>Invoice Items
                        </h5>
                    </div>
                    <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                        <th>Item</th>
                                        <th>Code</th>
                                        <th>Description</th>
                                        <th>Unit</th>
                                        <th>Expiry Date</th>
                                        <th>Batch Number</th>
                                        <th class="text-end">Quantity</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-end">VAT Rate</th>
                                        <th class="text-end">VAT Amount</th>
                                <th class="text-end">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                                    @foreach($invoice->items as $item)
                                    @php
                                        if ($item->inventoryItem) {
                                            $itemName = $item->inventoryItem->name;
                                            $itemCode = $item->inventoryItem->code;
                                            $itemDescription = $item->description ?? 'N/A';
                                            $itemUnit = $item->inventoryItem->unit_of_measure ?? 'N/A';
                                        } else {
                                            $itemName = $item->description ?? 'N/A';
                                            $itemCode = 'N/A';
                                            $itemDescription = $item->description ?? 'N/A';
                                            $itemUnit = 'N/A';
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-success me-2">Inventory</span>
                                                <strong>{{ $itemName }}</strong>
                                            </div>
                                        </td>
                                        <td>{{ $itemCode }}</td>
                                        <td>{{ $itemDescription }}</td>
                                        <td>{{ $itemUnit }}</td>
                                        <td>
                                            @if($item->expiry_date)
                                                <span class="badge bg-info">{{ $item->expiry_date->format('d M Y') }}</span>
                                                @if($item->expiry_date < now())
                                                    <span class="badge bg-danger ms-1">Expired</span>
                                                @elseif($item->expiry_date < now()->addDays(30))
                                                    <span class="badge bg-warning ms-1">Expiring Soon</span>
                                                @endif
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->batch_number)
                                                <span class="badge bg-secondary">{{ $item->batch_number }}</span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ number_format($item->quantity, 2) }}</td>
                                        <td class="text-end">{{ $currencyDisplay }} {{ number_format($item->unit_cost, 2) }}</td>
                                        <td class="text-end">{{ number_format($item->vat_rate, 2) }}%</td>
                                        <td class="text-end">{{ $currencyDisplay }} {{ number_format($item->vat_amount, 2) }}</td>
                                        <td class="text-end"><strong>{{ $currencyDisplay }} {{ number_format($item->line_total, 2) }}</strong></td>
                            </tr>
                            @endforeach
                        </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes, Terms & Attachment + Invoice Summary -->
        <div class="row">
            <div class="col-md-8">
                @if($invoice->notes || $invoice->terms_conditions || $invoice->attachment)
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-note me-2"></i>Notes & Terms
                            </h5>
                            @if($invoice->attachment)
                                <a href="{{ asset('storage/' . $invoice->attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bx bx-paperclip me-1"></i> View Attachment
                                </a>
                            @endif
                        </div>
                        <div class="card-body">
                            @if($invoice->notes)
                                <div class="mb-3">
                                    <h6>Notes:</h6>
                                    <p class="mb-0">{{ $invoice->notes }}</p>
                                </div>
                            @endif
                            @if($invoice->terms_conditions)
                                <div>
                                    <h6>Terms & Conditions:</h6>
                                    <p class="mb-0">{{ $invoice->terms_conditions }}</p>
                                </div>
                            @endif
                            @if(!$invoice->notes && !$invoice->terms_conditions && $invoice->attachment)
                                <p class="mb-0 text-muted">An attachment has been uploaded for this invoice.</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-calculator me-2"></i>Invoice Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>{{ number_format($invoice->subtotal, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>VAT Amount:</span>
                            <span>{{ number_format($invoice->vat_amount, 2) }}</span>
                        </div>
                        @if($invoice->discount_amount > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span>Discount:</span>
                            <span>-{{ number_format($invoice->discount_amount, 2) }}</span>
                        </div>
                        @endif
                        @if($invoice->withholding_tax_amount > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span>Withholding Tax:</span>
                            <span>{{ number_format($invoice->withholding_tax_amount, 2) }}</span>
                        </div>
                        @endif

                        <!-- Early Payment Discount Information -->
                        @if($invoice->early_payment_discount_enabled)
                        <div class="d-flex justify-content-between mb-2">
                            <span>Early Payment Discount:</span>
                            <span class="text-success">-{{ $currencyDisplay }} {{ number_format($invoice->calculateEarlyPaymentDiscount(), 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Early Payment Terms:</span>
                            <span class="text-muted small">{{ $invoice->getEarlyPaymentDiscountText() }}</span>
                        </div>
                        @if($invoice->isEarlyPaymentDiscountValid())
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-success small">Early Payment Valid Until:</span>
                            <span class="text-success small">{{ $invoice->getEarlyPaymentDiscountExpiryDate() ? $invoice->getEarlyPaymentDiscountExpiryDate()->format('d M Y') : 'N/A' }}</span>
                        </div>
                        @else
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-danger small">Early Payment Expired:</span>
                            <span class="text-danger small">{{ $invoice->getEarlyPaymentDiscountExpiryDate() ? $invoice->getEarlyPaymentDiscountExpiryDate()->format('d M Y') : 'N/A' }}</span>
                        </div>
                        @endif
                        @endif

                        <!-- Late Payment Fees Information -->
                        @if($invoice->late_payment_fees_enabled && $invoice->isOverdue())
                        <div class="d-flex justify-content-between mb-2">
                            <span>Late Payment Fees:</span>
                            <span class="text-danger">+{{ $currencyDisplay }} {{ number_format($invoice->calculateLatePaymentFees(), 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Late Payment Terms:</span>
                            <span class="text-muted small">{{ $invoice->getLatePaymentFeesText() }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-danger small">Overdue Days:</span>
                            <span class="text-danger small">{{ $invoice->getOverdueDays() }} days</span>
                        </div>
                        @endif

                        <hr>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total Amount:</span>
                            <span>{{ $currencyDisplay }} {{ number_format($invoice->total_amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Paid Amount:</span>
                            <span>{{ $currencyDisplay }} {{ number_format($totalPaid ?? 0, 2) }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold text-primary">
                            <span>Balance Due:</span>
                            <span>{{ $currencyDisplay }} {{ number_format($balanceDue ?? max(0, ($invoice->total_amount ?? 0) - ($totalPaid ?? 0)), 2) }}</span>
                        </div>

                        @if(isset($unpaidInvoices) && $unpaidInvoices->count() > 0)
                        <hr>
                        <div class="d-flex justify-content-between fw-bold text-warning">
                            <span>Total Supplier Balance:</span>
                            <span>{{ $functionalCurrency }} {{ number_format($totalSupplierBalanceInTZS ?? 0, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 text-muted small">
                            <span>(Current Invoice + Previous Unpaid)</span>
                            <span>{{ $functionalCurrency }} {{ number_format($currentInvoiceBalanceInTZS ?? 0, 2) }} + {{ $functionalCurrency }} {{ number_format($totalUnpaidAmountInTZS ?? 0, 2) }}</span>
                        </div>
                        @endif

                        <!-- Final Amount with Early Payment Discount or Late Payment Fees -->
                        @if($invoice->early_payment_discount_enabled && $invoice->isEarlyPaymentDiscountValid() && $invoice->calculateEarlyPaymentDiscount() > 0)
                        <div class="d-flex justify-content-between fw-bold text-success">
                            <span>Amount Due with Early Payment Discount:</span>
                            <span>{{ $currencyDisplay }} {{ number_format($invoice->getAmountDueWithEarlyDiscount(), 2) }}</span>
                        </div>
                        @endif

                        @if($invoice->late_payment_fees_enabled && $invoice->isOverdue() && $invoice->calculateLatePaymentFees() > 0)
                        <div class="d-flex justify-content-between fw-bold text-danger">
                            <span>Amount Due with Late Payment Fees:</span>
                            <span>{{ $currencyDisplay }} {{ number_format($invoice->getAmountDueWithLateFees(), 2) }}</span>
                        </div>
                        @endif
                        
                        <!-- Payment Progress Bar -->
                        @php
                            $paymentPercentage = $invoice->total_amount > 0 ? ($totalPaid / $invoice->total_amount) * 100 : 0;
                            $paymentPercentage = round($paymentPercentage, 1);
                        @endphp
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small">Payment Progress</span>
                                <span class="text-muted small fw-bold">{{ $paymentPercentage }}%</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar 
                                    @if($paymentPercentage >= 100) bg-success
                                    @elseif($paymentPercentage >= 75) bg-info
                                    @elseif($paymentPercentage >= 50) bg-warning
                                    @else bg-danger
                                    @endif" 
                                    role="progressbar" 
                                    style="width: {{ $paymentPercentage }}%" 
                                    aria-valuenow="{{ $paymentPercentage }}" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">{{ $currencyDisplay }} {{ number_format($totalPaid ?? 0, 2) }}</small>
                                <small class="text-muted">{{ $currencyDisplay }} {{ number_format($invoice->total_amount, 2) }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="bx bx-credit-card me-2"></i>Payment History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th class="text-end">Amount</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($payments as $p)
                                    <tr>
                                        <td>{{ optional($p->date)->format('d M Y') ?? 'N/A' }}</td>
                                        <td class="text-end">{{ $currencyDisplay }} {{ number_format($p->amount, 2) }}</td>
                                        <td>{{ optional($p->bankAccount)->name ? 'Bank - ' . $p->bankAccount->name : 'Cash' }}</td>
                                        <td>{{ $p->reference ?? 'N/A' }}</td>
                                        <td>{{ $p->description ?? 'N/A' }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('purchases.purchase-invoices.payment.edit', [$invoice->encoded_id, $p->hash_id]) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>
                                                <a href="{{ route('purchases.purchase-invoices.payment.print', [$invoice->encoded_id, $p->hash_id]) }}" class="btn btn-sm btn-outline-info" title="Print" target="_blank"><i class="bx bx-printer"></i></a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Delete" onclick="deletePurchasePayment('{{ $invoice->encoded_id }}', '{{ $p->hash_id }}')"><i class="bx bx-trash"></i></button>
                                            </div>
                                        </td>
                            </tr>
                                    @empty
                            <tr>
                                        <td colspan="6" class="text-center text-muted">No payments found</td>
                            </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="table-info">
                                        <th>Total Paid</th>
                                        <th class="text-end">{{ $currencyDisplay }} {{ number_format($totalPaid ?? 0, 2) }}</th>
                                        <th colspan="3"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

        <!-- GL Transactions -->
        @if(optional($invoice->glTransactions)->count() > 0)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-book me-2"></i>General Ledger Transactions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Account</th>
                                        <th>Description</th>
                                        <th class="text-end">Debit</th>
                                        <th class="text-end">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->glTransactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->date ? $transaction->date->format('d M Y') : 'N/A' }}</td>
                                        <td>
                                            <strong>{{ $transaction->chartAccount->account_code }}</strong><br>
                                            <small class="text-muted">{{ $transaction->chartAccount->account_name }}</small>
                                        </td>
                                        <td>{{ $transaction->description }}</td>
                                        <td class="text-end">
                                            @if($transaction->nature === 'debit')
                                                {{ number_format($transaction->amount, 2) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($transaction->nature === 'credit')
                                                {{ number_format($transaction->amount, 2) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Invoice Actions -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-cog me-2"></i>Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-danger" onclick="deleteInvoice()">
                                <i class="bx bx-trash me-1"></i>Delete Invoice
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">

// Keyboard shortcut handler for reprocessing items (Ctrl+Shift+P)
document.addEventListener('keydown', function(e) {
    // Check for Ctrl+Shift+P (or Cmd+Shift+P on Mac)
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'P') {
        // Prevent default browser print dialog
        e.preventDefault();
        
        // Only trigger if the alert is visible
        const alertElement = document.getElementById('reprocess-alert');
        if (alertElement && alertElement.offsetParent !== null) {
            reprocessInvoiceItems();
        }
    }
});

function reprocessInvoiceItems() {
    Swal.fire({
        title: 'Process Invoice Items?',
        text: 'This will manually trigger the processing of all invoice items. This may take a few moments.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, Process Items',
        cancelButtonText: 'Cancel',
        showLoaderOnConfirm: true,
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Processing...',
                text: 'Items are being processed. Please wait...',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: '{{ route("purchases.purchase-invoices.reprocess-items", $invoice->encoded_id) }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: response.message || 'Items processing has been queued successfully.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Reload page after a short delay to allow job to process
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        });
                    } else {
                        Swal.fire(
                            'Error!',
                            response.message || 'An error occurred while processing items.',
                            'error'
                        );
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred while processing items.';
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    Swal.fire(
                        'Error!',
                        errorMessage,
                        'error'
                    );
                }
            });
        }
    });
}

function deleteInvoice() {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '#',
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire(
                            'Deleted!',
                            response.message,
                            'success'
                        ).then(() => {
                            window.location.href = '{{ route("purchases.purchase-invoices.index") }}';
                        });
                    } else {
                        Swal.fire(
                            'Error!',
                            response.message,
                            'error'
                        );
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred while deleting the invoice.';
                    
                    // Try to get the error message from the response
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    Swal.fire(
                        'Error!',
                        errorMessage,
                        'error'
                    );
                }
            });
        }
    });
}

function sendInvoiceEmail() {
    Swal.fire({
        title: 'Send Invoice Email',
        html: `
            <div class="text-start">
                <div class="mb-3">
                    <label for="email_subject" class="form-label">Subject</label>
                    <input type="text" id="email_subject" class="form-control" value="Invoice #{{ $invoice->invoice_number }} from {{ config('app.name') }}" placeholder="Email subject">
                </div>
                <div class="mb-3">
                    <label for="email_message" class="form-label">Message</label>
                    <textarea id="email_message" class="form-control" rows="4" placeholder="Email message">Please find attached invoice #{{ $invoice->invoice_number }} for your records.</textarea>
                </div>
                <div class="mb-3">
                    <label for="email_address" class="form-label">Email Address</label>
                    <input type="email" id="email_address" class="form-control" value="{{ optional($invoice->supplier)->email ?? '' }}" placeholder="Email address">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Send Email',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const subject = document.getElementById('email_subject').value;
            const message = document.getElementById('email_message').value;
            const email = document.getElementById('email_address').value;
            
            if (!email) {
                Swal.showValidationMessage('Email address is required');
                return false;
            }
            
            return { subject, message, email };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("purchases.purchase-invoices.send-email", $invoice->encoded_id) }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    subject: result.value.subject,
                    message: result.value.message,
                    email: result.value.email
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire(
                            'Sent!',
                            response.message,
                            'success'
                        );
                    } else {
                        Swal.fire(
                            'Error!',
                            response.message,
                            'error'
                        );
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred while sending the email.';
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    Swal.fire(
                        'Error!',
                        errorMessage,
                        'error'
                    );
                }
            });
        }
    });
}

function reversePayment(receiptId) {
    Swal.fire({
        title: 'Reverse Payment',
        text: 'Please provide a reason for reversing this payment:',
        input: 'text',
        inputPlaceholder: 'Enter reason for reversal...',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Reverse Payment',
        inputValidator: (value) => {
            if (!value) {
                return 'You need to provide a reason!'
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '#',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    receipt_id: receiptId,
                    reason: result.value
                },
                success: function(response) {
                    Swal.fire(
                        'Reversed!',
                        'Payment has been reversed successfully.',
                        'success'
                    ).then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    Swal.fire(
                        'Error!',
                        'An error occurred while reversing the payment.',
                        'error'
                    );
                }
            });
        }
    });
}

function deletePayment(paymentType, paymentId, encodedId) {
    Swal.fire({
        title: 'Delete Payment',
        text: 'Are you sure you want to delete this payment? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            let url;
            let data = {
                _token: '{{ csrf_token() }}'
            };
            
            if (paymentType === 'payment') {
                // Delete payment record
                url = '#';
            } else {
                // Delete receipt record
                url = '#';
                data.receipt_id = paymentId;
            }
            
            $.ajax({
                url: url,
                type: 'DELETE',
                data: data,
                success: function(response) {
                    if (response.success) {
                        Swal.fire(
                            'Deleted!',
                            'Payment has been deleted successfully.',
                            'success'
                        ).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire(
                            'Error!',
                            response.message || 'An error occurred while deleting the payment.',
                            'error'
                        );
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred while deleting the payment.';
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    Swal.fire(
                        'Error!',
                        errorMessage,
                        'error'
                    );
                }
            });
        }
    });
}

function deletePurchasePayment(encodedId, paymentId) {
    Swal.fire({
        title: 'Delete Payment',
        text: 'Are you sure you want to delete this payment? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ url('/purchases/purchase-invoices') }}/' + encodedId + '/payment/' + paymentId,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(resp){
                    if (resp.success) {
                        Swal.fire('Deleted!', resp.message, 'success').then(()=>{ location.reload(); });
                    } else {
                        Swal.fire('Error!', resp.message || 'Failed to delete payment', 'error');
                    }
                },
                error: function(xhr){
                    let msg = 'An error occurred while deleting the payment.';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire('Error!', msg, 'error');
                }
            });
        }
    });
}
</script>
@endpush
@endsection 