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
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-store'],
            ['label' => 'Sales Invoices', 'url' => route('sales.invoices.index'), 'icon' => 'bx bx-receipt'],
            ['label' => 'Invoice Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Invoice Details</h4>
                    <div class="page-title-right">
                        @php
                            $isPaid = ($invoice->status === 'paid');
                        @endphp
                        @can('record sales payment')
                        <a href="{{ $isPaid ? '#' : route('sales.invoices.payment-form', $invoice->encoded_id) }}" class="btn btn-success me-1 {{ $isPaid ? 'disabled' : '' }}" {{ $isPaid ? 'aria-disabled=true tabindex=-1' : '' }}>
                            <i class="bx bx-money me-1"></i>Record Payment
                        </a>
                        @endcan
                        @if($invoice->late_payment_fees_enabled && $invoice->isOverdue() && $invoice->calculateLatePaymentFees() > 0 && !$invoice->hasLatePaymentFeesApplied())
                        <form method="POST" action="{{ route('sales.invoices.apply-late-fees', $invoice->encoded_id) }}" id="apply-late-fees-form" style="display: inline;">
                            @csrf
                            <button type="button" class="btn btn-warning me-1" id="apply-late-fees-btn">
                                <i class="bx bx-time me-1"></i>Apply Late Fees
                            </button>
                        </form>
                        @endif
                        @can('edit sales invoices')
                        <a href="{{ $isPaid ? '#' : route('sales.invoices.edit', $invoice->encoded_id) }}" class="btn btn-primary me-1 {{ $isPaid ? 'disabled' : '' }}" {{ $isPaid ? 'aria-disabled=true tabindex=-1' : '' }}>
                            <i class="bx bx-edit me-1"></i>Edit Invoice
                        </a>
                        @endcan
                        @if($invoice->customer->email)
                        <button type="button" class="btn btn-info me-1" onclick="sendInvoiceEmail()">
                            <i class="bx bx-envelope me-1"></i>Send Email
                        </button>
                        @endif
                        <a href="{{ route('sales.invoices.export-pdf', $invoice->encoded_id) }}" class="btn btn-info me-1" target="_blank">
                            <i class="bx bx-download me-1"></i>Export PDF
                        </a>
                        <div class="btn-group me-1">
                            <button type="button" class="btn btn-danger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bx bx-printer me-1"></i>Print Invoice
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('sales.invoices.print', $invoice->encoded_id) }}?size=a4" target="_blank"><i class="bx bx-file me-2"></i>A4</a></li>
                                <li><a class="dropdown-item" href="{{ route('sales.invoices.print', $invoice->encoded_id) }}?size=a5" target="_blank"><i class="bx bx-file me-2"></i>A5</a></li>
                            </ul>
                        </div>
                        <form method="POST" action="{{ route('sales.invoices.pos-print', $invoice->encoded_id) }}" class="d-inline" onsubmit="return confirm('Send this invoice to the POS printer?')">
                            @csrf
                            <button type="submit" class="btn btn-dark me-1">
                                <i class="bx bx-receipt me-1"></i>POS Print
                            </button>
                        </form>
                        <div class="btn-group me-1">
                            <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bx bx-copy me-1"></i>Copy To
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('sales.proformas.create-from-invoice', $invoice->encoded_id) }}"><i class="bx bx-file me-2"></i>Sales Quote</a></li>
                                <li><a class="dropdown-item" href="{{ route('sales.orders.create-from-invoice', $invoice->encoded_id) }}"><i class="bx bx-cart me-2"></i>Sales Order</a></li>
                                <li><a class="dropdown-item" href="{{ route('sales.deliveries.create-from-invoice', $invoice->encoded_id) }}"><i class="bx bx-truck me-2"></i>Delivery Note</a></li>
                                <li><a class="dropdown-item" href="{{ route('sales.credit-notes.create-from-invoice', $invoice->encoded_id) }}"><i class="bx bx-undo me-2"></i>Credit Note</a></li>
                            </ul>
                        </div>
                        <a href="{{ route('sales.invoices.index') }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back to Invoices
                        </a>
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
                                    @if($invoice->salesOrder)
                                    <tr>
                                        <td><strong>From Order:</strong></td>
                                        <td>
                                            <a href="{{ route('sales.orders.show', $invoice->salesOrder->encoded_id ?? \Vinkla\Hashids\Facades\Hashids::encode($invoice->salesOrder->id)) }}" class="text-primary fw-semibold">
                                                {{ $invoice->salesOrder->order_number }}
                                            </a>
                                            @if($invoice->salesOrder->proforma)
                                                <br><small class="text-muted">From Proforma: 
                                                    <a href="{{ route('sales.proformas.show', $invoice->salesOrder->proforma->encoded_id ?? \Vinkla\Hashids\Facades\Hashids::encode($invoice->salesOrder->proforma->id)) }}" class="text-primary fw-semibold">
                                                        {{ $invoice->salesOrder->proforma->proforma_number }}
                                                    </a>
                                                </small>
                                            @endif
                                        </td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>
                                            {!! $invoice->status_badge !!}
                                            @if($invoice->gl_posted ?? false)
                                                <span class="badge bg-success ms-2">GL Posted</span>
                                            @elseif($invoice->status === 'sent' || $invoice->status === 'paid')
                                                <span class="badge bg-warning text-dark ms-2" title="Invoice is approved but not yet posted to GL. This may be due to a locked period or configuration issue.">
                                                    Not Posted to GL
                                                </span>
                                            @endif
                                        </td>
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
                                    @if($invoice->reference_no)
                                    <tr>
                                        <td><strong>Reference No.:</strong></td>
                                        <td>{{ $invoice->reference_no }}</td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">Customer Information</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="150"><strong>Customer:</strong></td>
                                        <td>{{ $invoice->customer->name }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Phone:</strong></td>
                                        <td>{{ $invoice->customer->phone }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td>{{ $invoice->customer->email ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Address:</strong></td>
                                        <td>{{ $invoice->customer->address ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Credit Limit:</strong></td>
                                        <td>{{ $functionalCurrency }} {{ number_format($invoice->customer->credit_limit ?? 0, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Currency:</strong></td>
                                        <td><span class="badge bg-info">{{ $invoiceCurrency }}</span></td>
                                    </tr>
                                    @if($invoice->exchange_rate && $invoice->exchange_rate != 1)
                                    <tr>
                                        <td><strong>Exchange Rate:</strong></td>
                                        <td>1 {{ $invoiceCurrency }} = {{ number_format($invoice->exchange_rate, 6) }} {{ \App\Models\SystemSetting::getValue('functional_currency', auth()->user()->company->functional_currency ?? 'TZS') }}</td>
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
                                    <tr>
                                        <td>
                                            <strong>{{ $item->item_name }}</strong>
                                            @if($item->notes)
                                                <br><small class="text-muted">{{ $item->notes }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $item->item_code }}</td>
                                        <td>{{ $item->description ?? 'N/A' }}</td>
                                        <td>{{ $item->unit_of_measure ?? 'N/A' }}</td>
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
                                        <td class="text-end">{{ $currencyDisplay }} {{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-end">{{ $item->vat_rate }}%</td>
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

        <!-- Invoice Summary -->
        <div class="row">
            <div class="col-md-8">
                @if($invoice->notes || $invoice->terms_conditions || $invoice->attachment)
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-note me-2"></i>Notes & Terms
                            </h5>
                            @if($invoice->attachment)
                                <a href="{{ asset('storage/' . $invoice->attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bx bx-link-external me-1"></i>View Attachment
                                </a>
                            @endif
                        </div>
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
                            <p class="text-muted mb-0"><small>An attachment has been uploaded for this invoice.</small></p>
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
                            <span>{{ $currencyDisplay }} {{ number_format($invoice->subtotal, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>VAT Amount:</span>
                            <span>{{ $currencyDisplay }} {{ number_format($invoice->vat_amount, 2) }}</span>
                        </div>
                        @if(($creditNotesApplied ?? 0) > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span>Credit Notes Applied:</span>
                            <span class="text-success">-{{ $currencyDisplay }} {{ number_format($creditNotesApplied, 2) }}</span>
                        </div>
                        @endif
                        {{-- Previous unpaid invoices for this customer --}}
                        @if($unpaidInvoices->count() > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span>Previous Unpaid Invoices:</span>
                            <span class="text-warning">{{ $functionalCurrency }} {{ number_format($totalUnpaidAmountInTZS ?? 0, 2) }}</span>
                        </div>
                        @endif
                        @if($invoice->discount_amount > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span>Discount:</span>
                            <span>-{{ $currencyDisplay }} {{ number_format($invoice->discount_amount, 2) }}</span>
                        </div>
                        @endif
                        @if($invoice->withholding_tax_amount > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span>Withholding Tax:</span>
                            <span>{{ $currencyDisplay }} {{ number_format($invoice->withholding_tax_amount, 2) }}</span>
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
                            <span class="text-success small">{{ $invoice->getEarlyPaymentDiscountExpiryDate()->format('d M Y') }}</span>
                        </div>
                        @else
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-danger small">Early Payment Expired:</span>
                            <span class="text-danger small">{{ $invoice->getEarlyPaymentDiscountExpiryDate()->format('d M Y') }}</span>
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
                            <span>{{ $currencyDisplay }} {{ number_format($invoice->paid_amount, 2) }}</span>
                        </div>
                        @if(($creditNotesApplied ?? 0) > 0)
                        @php $effectiveBalance = max($invoice->balance_due - $creditNotesApplied, 0); @endphp
                        <div class="alert alert-info py-2 px-3 mb-2">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold">Effective Balance after Credits:</span>
                                <span class="fw-semibold">{{ $currencyDisplay }} {{ number_format($effectiveBalance, 2) }}</span>
                            </div>
                        </div>
                        @endif
                        <hr>
                        <div class="d-flex justify-content-between fw-bold text-primary">
                            <span>Balance Due:</span>
                            <span>{{ $currencyDisplay }} {{ number_format($invoice->balance_due, 2) }}</span>
                        </div>

                        @if($unpaidInvoices->count() > 0)
                        <hr>
                        <div class="d-flex justify-content-between fw-bold text-warning">
                            <span>Total Customer Balance:</span>
                            <span>{{ $functionalCurrency }} {{ number_format($totalCustomerBalanceInTZS ?? 0, 2) }}</span>
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
                            $paymentPercentage = $invoice->total_amount > 0 ? ($invoice->paid_amount / $invoice->total_amount) * 100 : 0;
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
                                <small class="text-muted">{{ $currencyDisplay }} {{ number_format($invoice->paid_amount, 2) }}</small>
                                <small class="text-muted">{{ $currencyDisplay }} {{ number_format($invoice->total_amount, 2) }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- GL Transactions -->
        @if($invoice->glTransactions->count() > 0)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-book me-2"></i>General Ledger Transactions
                        </h5>
                        <small class="text-muted">All amounts are displayed in functional currency ({{ $functionalCurrency }})</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Account</th>
                                        <th>Description</th>
                                        <th class="text-end">Debit ({{ $functionalCurrency }})</th>
                                        <th class="text-end">Credit ({{ $functionalCurrency }})</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->glTransactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->date->format('d M Y') }}</td>
                                        <td>
                                            <strong>{{ $transaction->chartAccount->account_code }}</strong><br>
                                            <small class="text-muted">{{ $transaction->chartAccount->account_name }}</small>
                                        </td>
                                        <td>{{ $transaction->description }}</td>
                                        <td class="text-end">
                                            @if($transaction->nature === 'debit')
                                                {{ $functionalCurrency }} {{ number_format($transaction->amount, 2) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($transaction->nature === 'credit')
                                                {{ $functionalCurrency }} {{ number_format($transaction->amount, 2) }}
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

        <!-- Payment History -->
        @canany(['view payment history', 'view sales invoices'])
        @php
            $receiptsCount = $invoice->receipts()->count();
            $paymentsCount = $invoice->payments()->count();
            $journalPaymentsCount = $invoice->cashDepositPaymentJournals()->count();
            $hasPayments = $receiptsCount > 0 || $paymentsCount > 0 || $journalPaymentsCount > 0;
        @endphp
        @if($hasPayments)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-history me-2"></i>Payment History
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Payment Method</th>
                                        <th>Description</th>
                                        <th>Recorded By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        // Get both payments and receipts for this invoice
                                        // Use the receipts() method which handles both reference=id and reference_number=invoice_number
                                        $payments = $invoice->payments()->with(['user', 'bankAccount', 'cashDeposit.type'])->get();
                                        $receipts = $invoice->receipts()->with(['user', 'bankAccount'])->get();
                                        $cashDepositJournals = $invoice->relationLoaded('cashDepositPaymentJournals')
                                            ? $invoice->cashDepositPaymentJournals
                                            : $invoice->cashDepositPaymentJournals()->with(['user', 'items'])->get();

                                        // Combine and sort by date
                                        $allPayments = collect();

                                        // Add payments
                                        foreach($payments as $payment) {
                                            $allPayments->push([
                                                'type' => 'payment',
                                                'data' => $payment,
                                                'date' => $payment->date,
                                                'amount' => $payment->amount,
                                                'description' => $payment->description,
                                                'user' => $payment->user,
                                                'bank_account_id' => $payment->bank_account_id,
                                                'cash_deposit_id' => $payment->cash_deposit_id,
                                                'bank_account' => $payment->bankAccount,
                                                'approved' => $payment->approved,
                                                'id' => $payment->id,
                                                'encoded_id' => $payment->hash_id
                                            ]);
                                        }

                                        // Add receipts
                                        foreach($receipts as $receipt) {
                                            $allPayments->push([
                                                'type' => 'receipt',
                                                'data' => $receipt,
                                                'date' => $receipt->date,
                                                'amount' => $receipt->amount,
                                                'description' => $receipt->description,
                                                'user' => $receipt->user,
                                                'bank_account_id' => $receipt->bank_account_id,
                                                'cash_deposit_id' => null,
                                                'bank_account' => $receipt->bankAccount,
                                                'approved' => $receipt->approved,
                                                'id' => $receipt->id,
                                                'encoded_id' => $receipt->encoded_id
                                            ]);
                                        }

                                        foreach ($cashDepositJournals as $cdJournal) {
                                            $jAmount = (float) $cdJournal->items->where('nature', 'debit')->sum('amount');
                                            if ($jAmount <= 0) {
                                                continue;
                                            }
                                            // Journal `approved` is for accounting workflow, not "reversed payment"; always allow invoice actions.
                                            $allPayments->push([
                                                'type' => 'journal_cash_deposit',
                                                'data' => $cdJournal,
                                                'date' => $cdJournal->date,
                                                'amount' => $jAmount,
                                                'description' => $cdJournal->description,
                                                'user' => $cdJournal->user,
                                                'bank_account_id' => null,
                                                'cash_deposit_id' => null,
                                                'bank_account' => null,
                                                'approved' => true,
                                                'id' => $cdJournal->id,
                                                'encoded_id' => null,
                                            ]);
                                        }

                                        $allPayments = $allPayments->sortByDesc('date');
                                    @endphp

                                    @foreach($allPayments as $payment)
                                    <tr>
                                        <td>{{ $payment['date']->format('M d, Y') }}</td>
                                        <td class="text-end">{{ $currencyDisplay }} {{ number_format($payment['amount'], 2) }}</td>
                                        <td>
                                            @if($payment['type'] === 'journal_cash_deposit')
                                                <span class="badge bg-info">Cash Deposit</span>
                                            @elseif($payment['type'] === 'payment')
                                                @if($payment['cash_deposit_id'])
                                                    <span class="badge bg-info">Cash Deposit (voucher)</span>
                                                @else
                                                    <span class="badge bg-secondary">Payment voucher</span>
                                                @endif
                                            @else
                                                @if($payment['bank_account_id'])
                                                    <span class="badge bg-primary">{{ $payment['bank_account']->name ?? 'Bank' }}</span>
                                                @else
                                                    <span class="badge bg-success">Cash</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td>{{ $payment['description'] ?? 'Payment for Invoice #' . $invoice->invoice_number }}</td>
                                        <td>{{ $payment['user']?->name ?? '—' }}</td>
                                        <td>
                                            @php
                                                $isJournalCashDeposit = $payment['type'] === 'journal_cash_deposit';
                                            @endphp
                                            <div class="btn-group" role="group">
                                                @if($isJournalCashDeposit)
                                                <a href="{{ route('accounting.journals.show', $payment['data']) }}"
                                                   class="btn btn-sm btn-outline-secondary me-1" title="View journal entry" target="_blank">
                                                    <i class="bx bx-show me-1"></i>Journal
                                                </a>
                                                <a href="{{ route('accounting.journals.edit', $payment['data']) }}"
                                                   class="btn btn-sm btn-primary me-1" title="Edit journal entry">
                                                    <i class="bx bx-edit me-1"></i>Edit
                                                </a>
                                                @elseif($payment['type'] === 'payment')
                                                <a href="{{ route('sales.invoices.payment.edit', $payment['encoded_id']) }}"
                                                   class="btn btn-sm btn-primary me-1" title="Edit Payment">
                                                    <i class="bx bx-edit me-1"></i>Edit
                                                </a>
                                                @elseif($payment['type'] === 'receipt')
                                                <a href="{{ route('sales.invoices.print-receipt', $payment['encoded_id']) }}"
                                                   class="btn btn-sm btn-secondary me-1" title="Print Receipt" target="_blank">
                                                    <i class="bx bx-printer me-1"></i>Print
                                                </a>
                                                <a href="{{ route('sales.invoices.receipt.edit', $payment['encoded_id']) }}"
                                                   class="btn btn-sm btn-primary me-1" title="Edit Receipt">
                                                    <i class="bx bx-edit me-1"></i>Edit
                                                </a>
                                                @endif
                                                @if($payment['approved'])
                                                <button type="button" class="btn btn-sm btn-danger"
                                                        onclick="deletePayment('{{ $payment['type'] }}', {{ $payment['id'] }}, '{{ $payment['encoded_id'] ?? '' }}')"
                                                        title="Delete Payment">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                                @else
                                                <span class="text-muted small ms-1" title="This entry was reversed or voided; it cannot be deleted again.">Reversed</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-info">
                                        <th colspan="1">Total Payments:</th>
                                        <th class="text-end">{{ $currencyDisplay }} {{ number_format($invoice->paid_amount, 2) }}</th>
                                        <th colspan="4"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @endcan

        <!-- Credit Notes Section -->
        @if($invoice->creditNotes->count() > 0)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-undo me-2"></i>Related Credit Notes
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Credit Note #</th>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Amount</th>
                                        <th>Applied Amount</th>
                                        <th>Remaining</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->creditNotes as $creditNote)
                                    <tr>
                                        <td>
                                            <a href="{{ route('sales.credit-notes.show', $creditNote->encoded_id) }}" 
                                               class="text-primary fw-bold">
                                                {{ $creditNote->credit_note_number }}
                                            </a>
                                        </td>
                                        <td>{{ $creditNote->credit_note_date->format('M d, Y') }}</td>
                                        <td>
                                            <span class="badge bg-info">
                                                {{ ucfirst(str_replace('_', ' ', $creditNote->type)) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($creditNote->status === 'draft')
                                                <span class="badge bg-secondary">Draft</span>
                                            @elseif($creditNote->status === 'issued')
                                                <span class="badge bg-warning">Issued</span>
                                            @elseif($creditNote->status === 'applied')
                                                <span class="badge bg-success">Applied</span>
                                            @elseif($creditNote->status === 'cancelled')
                                                <span class="badge bg-danger">Cancelled</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($creditNote->status) }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ $currencyDisplay }} {{ number_format($creditNote->total_amount, 2) }}</td>
                                        <td class="text-end">{{ $currencyDisplay }} {{ number_format($creditNote->applied_amount, 2) }}</td>
                                        <td class="text-end">{{ $currencyDisplay }} {{ number_format($creditNote->remaining_amount, 2) }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('sales.credit-notes.show', $creditNote->encoded_id) }}" 
                                                   class="btn btn-sm btn-primary" title="View Credit Note">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                @if($creditNote->status === 'draft')
                                                <a href="{{ route('sales.credit-notes.edit', $creditNote->encoded_id) }}" 
                                                   class="btn btn-sm btn-warning" title="Edit Credit Note">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                @endif
                                                @if($creditNote->status === 'issued' && $creditNote->remaining_amount > 0)
                                                <a href="{{ route('sales.credit-notes.apply', $creditNote->encoded_id) }}" 
                                                   class="btn btn-sm btn-success" title="Apply Credit Note">
                                                    <i class="bx bx-check"></i>
                                                </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-info">
                                        <th colspan="4">Total Credit Notes:</th>
                                        <th class="text-end">{{ $currencyDisplay }} {{ number_format($invoice->creditNotes->sum('total_amount'), 2) }}</th>
                                        <th class="text-end">{{ $currencyDisplay }} {{ number_format($invoice->creditNotes->sum('applied_amount'), 2) }}</th>
                                        <th class="text-end">{{ $currencyDisplay }} {{ number_format($invoice->creditNotes->sum('remaining_amount'), 2) }}</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
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
                url: '{{ route("sales.invoices.index") }}/{{ $invoice->encoded_id }}',
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
                            window.location.href = '{{ route("sales.invoices.index") }}';
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
                    <input type="email" id="email_address" class="form-control" value="{{ $invoice->customer->email }}" placeholder="Email address">
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
                url: '{{ route("sales.invoices.send-email", $invoice->encoded_id) }}',
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
                url: '{{ route("sales.invoices.reverse-payment", $invoice->encoded_id) }}',
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
                url = '{{ route("sales.invoices.delete-invoice-payment", ":encodedId") }}'.replace(':encodedId', encodedId);
            } else if (paymentType === 'journal_cash_deposit') {
                url = '{{ url("/sales/invoices/" . $invoice->encoded_id . "/journal-payment") }}/' + paymentId;
            } else {
                // Delete receipt record
                url = '{{ route("sales.invoices.delete-payment", $invoice->encoded_id) }}';
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

// Apply Late Fees Confirmation with SweetAlert
$(document).ready(function() {
    $('#apply-late-fees-btn').on('click', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Apply Late Payment Fees?',
            text: 'Are you sure you want to apply late payment fees? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Apply Fees',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $('#apply-late-fees-form').submit();
            }
        });
    });
});
</script>
@endpush
@endsection
