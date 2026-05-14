<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        @php
            $docPageSize = isset($printSize) ? strtoupper($printSize) : \App\Models\SystemSetting::getValue('document_page_size', 'A5');
            $docOrientation = \App\Models\SystemSetting::getValue('document_orientation', 'portrait');
            $docMarginTop = \App\Models\SystemSetting::getValue('document_margin_top', '2.54cm');
            $docMarginRight = \App\Models\SystemSetting::getValue('document_margin_right', '2.54cm');
            $docMarginBottom = \App\Models\SystemSetting::getValue('document_margin_bottom', '2.54cm');
            $docMarginLeft = \App\Models\SystemSetting::getValue('document_margin_left', '2.54cm');
            $docFontFamily = \App\Models\SystemSetting::getValue('document_font_family', 'DejaVu Sans');
            $docFontSize = (int) (\App\Models\SystemSetting::getValue('document_base_font_size', 10));
            $docLineHeight = \App\Models\SystemSetting::getValue('document_line_height', '1.4');
            $docTextColor = \App\Models\SystemSetting::getValue('document_text_color', '#000000');
            $docBgColor = \App\Models\SystemSetting::getValue('document_background_color', '#FFFFFF');
            $docHeaderColor = \App\Models\SystemSetting::getValue('document_header_color', '#000000');
            $docAccentColor = \App\Models\SystemSetting::getValue('document_accent_color', '#b22222');
            $docTableHeaderBg = \App\Models\SystemSetting::getValue('document_table_header_bg', '#f2f2f2');
            $docTableHeaderText = \App\Models\SystemSetting::getValue('document_table_header_text', '#000000');
            $pageSizeCss = $docPageSize . ' ' . $docOrientation;
        @endphp
        @page {
            size: {{ $pageSizeCss }};
            margin: {{ $docMarginTop }} {{ $docMarginRight }} {{ $docMarginBottom }} {{ $docMarginLeft }};
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: '{{ $docFontFamily }}', sans-serif;
            font-size: {{ $docFontSize }}px;
            line-height: {{ $docLineHeight }};
            color: {{ $docTextColor }};
            background-color: {{ $docBgColor }};
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
                size: {{ $pageSizeCss }};
                margin: {{ $docMarginTop }} {{ $docMarginRight }} {{ $docMarginBottom }} {{ $docMarginLeft }};
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

        /* === HEADER (same layout as export PDF, no background color) === */
        .header {
            margin-bottom: 10px;
            padding: 0;
            background: none;
        }

        .header::after {
            content: "";
            display: table;
            clear: both;
        }

        .logo-section {
            float: left;
            width: 45%;
            margin-bottom: 10px;
        }

        .logo-section img {
            max-height: 80px;
            max-width: 120px;
            object-fit: contain;
        }

        .header-company-right {
            float: right;
            width: 50%;
            text-align: left;
            margin-left: 5%;
        }

        .company-name {
            color: #000;
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 4px 0;
        }

        .company-details {
            font-size: 10px;
            line-height: 1.4;
            color: #000;
        }

        .payment-prompt {
            font-size: 10px;
            margin: 10px 0 6px 0;
            font-weight: bold;
            color: #000;
            background: none;
        }

        .payment-details-list {
            font-size: 10px;
            color: #000;
            background: none;
        }

        .payment-details-list strong {
            color: #000;
        }

        /* === INVOICE TITLE === */
        .invoice-title {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            margin: 8px 0;
            text-transform: uppercase;
        }

        /* === INFO SECTION === */
        .invoice-details {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            margin-bottom: 8px;
        }

        .bill-to {
            flex: 1;
        }

        .invoice-info {
            flex: 1;
            text-align: right;
        }

        .invoice-info table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        .invoice-info td {
            border: 1px solid #000;
            padding: 2px;
        }

        .invoice-info td:first-child {
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
            background-color: {{ $docTableHeaderBg }};
            font-weight: bold;
            text-align: center;
            color: {{ $docTableHeaderText }};
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

        .amount-in-words {
            font-size: 8px;
            font-style: italic;
            margin-top: 3px;
        }

        /* === BALANCE SECTION === */
        .balance-section {
            font-size: 9px;
            margin-top: 8px;
        }

        .balance-row {
            display: flex;
            justify-content: space-between;
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

        .terms ol {
            margin: 2px 0 0 15px;
            padding: 0;
        }

        .terms li {
            margin-bottom: 2px;
        }

        .page-info {
            text-align: center;
            font-size: 8px;
            margin-top: 8px;
        }

        .expiry-date {
            font-size: 7px;
        }

        /* === PAYMENT HISTORY === */
        .payment-history {
            margin-top: 10px;
            page-break-inside: avoid;
        }

        .payment-history h3 {
            font-size: 11px;
            font-weight: bold;
            margin: 8px 0 5px 0;
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }

        .payment-history table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin-bottom: 10px;
        }

        .payment-history th,
        .payment-history td {
            border: 1px solid #000;
            padding: 3px;
            font-size: 8px;
        }

        .payment-history th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .payment-history tfoot tr {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        /* === EARLY PAYMENT DISCOUNT === */
        .early-payment-section {
            margin-top: 10px;
            page-break-inside: avoid;
        }

        .early-payment-section h3 {
            font-size: 11px;
            font-weight: bold;
            margin: 8px 0 5px 0;
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }

        .early-payment-section > div {
            background-color: #f8f9fa;
            border: 1px solid #000;
            padding: 8px;
            margin-bottom: 10px;
        }

        /* === LATE PAYMENT FEES === */
        .late-payment-section {
            margin-top: 10px;
            page-break-inside: avoid;
        }

        .late-payment-section h3 {
            font-size: 11px;
            font-weight: bold;
            margin: 8px 0 5px 0;
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }

        .late-payment-section > div {
            background-color: #f8d7da;
            border: 1px solid #000;
            padding: 8px;
            margin-bottom: 10px;
        }

        /* === AMOUNT DUE === */
        .amount-due-section {
            margin-top: 10px;
            page-break-inside: avoid;
        }

        .amount-due-section h3 {
            font-size: 11px;
            font-weight: bold;
            margin: 8px 0 5px 0;
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }

        .amount-due-section > div {
            background-color: #fff3cd;
            border: 1px solid #000;
            padding: 8px;
            margin-bottom: 10px;
        }

    </style>

</head>
<body>
    <div class="print-container">
        {{-- Header: same layout as export PDF, no background color --}}
        <div class="header">
            @if($invoice->company && $invoice->company->logo)
            <div class="logo-section">
                <img src="{{ asset('storage/' . ltrim($invoice->company->logo, '/')) }}" alt="{{ $invoice->company->name ?? 'Logo' }}">
            </div>
            @endif
            <div class="header-company-right">
                <div class="company-name">{{ $invoice->company->name ?? 'SMARTACCOUNTING' }}</div>
                <div class="company-details">
                    <strong>P.O. Box:</strong> {{ $invoice->company->address ?? 'P.O.BOX 00000, City, Country' }}<br>
                    <strong>Phone:</strong> {{ $invoice->company->phone ?? '+255 000 000 000' }}<br>
                    <strong>Email:</strong> {{ $invoice->company->email ?? 'company@email.com' }}
                </div>
            </div>
        </div>
        <div style="clear: both;"></div>
        @if($bankAccounts && $bankAccounts->count() > 0)
        <div class="payment-prompt">Please pay through one of the following methods:</div>
        <div class="payment-details-list">
            @foreach($bankAccounts as $account)
            <strong>{{ strtoupper($account->name ?? $account->bank_name ?? 'BANK') }}:</strong> {{ $account->account_number ?? 'N/A' }} &nbsp;&nbsp;
            @endforeach
        </div>
        @endif

    <div class="invoice-title">Sales Invoice</div>

    <div class="invoice-details">
        <div class="bill-to">
            <div class="field-label">Bill To:</div>
            <div class="field-value">{{ $invoice->customer->name ?? 'Customer Name' }}</div>
           </br>
            <div class="field-value"></div>
            <div class="field-label">User name:</div>
            <div class="field-value">{{ $invoice->createdBy->name ?? 'User Name' }}</div>
        </div>
        <div class="invoice-info">
            <table style="width: 100%; border-collapse: collapse; font-size: 8px;">
                <tr>
                    <td style="padding: 2px; border: 1px solid #000; font-weight: bold; width: 20%;">Invoice No:</td>
                    <td style="padding: 2px; border: 1px solid #000; width: 30%;">{{ $invoice->invoice_number }}</td>
                    <td style="padding: 2px; border: 1px solid #000; font-weight: bold; width: 20%;">Date:</td>
                    <td style="padding: 2px; border: 1px solid #000; width: 30%;">{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                </tr>
                @if($invoice->reference_no)
                <tr>
                    <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">Reference No.:</td>
                    <td style="padding: 2px; border: 1px solid #000;" colspan="3">{{ $invoice->reference_no }}</td>
                </tr>
                @endif
                @if($invoice->salesOrder)
                <tr>
                    <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">History:</td>
                    <td style="padding: 2px; border: 1px solid #000;" colspan="3">
                        Order: 
                        <a href="{{ route('sales.orders.show', \Vinkla\Hashids\Facades\Hashids::encode($invoice->salesOrder->id)) }}" style="color: #0d6efd; text-decoration: none;">
                            {{ $invoice->salesOrder->order_number }}
                        </a>
                        @if($invoice->salesOrder->proforma)
                            &nbsp;|&nbsp; Proforma: 
                            <a href="{{ route('sales.proformas.show', \Vinkla\Hashids\Facades\Hashids::encode($invoice->salesOrder->proforma->id)) }}" style="color: #0d6efd; text-decoration: none;">
                                {{ $invoice->salesOrder->proforma->proforma_number }}
                            </a>
                        @endif
                    </td>
                </tr>
                @endif
                <tr>
                    <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">Currency:</td>
                    <td style="padding: 2px; border: 1px solid #000;">{{ $invoice->currency ?? 'TZS' }}</td>
                    <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">Ex Rate:</td>
                    <td style="padding: 2px; border: 1px solid #000;">{{ number_format($invoice->exchange_rate ?? 1, 2) }}</td>
                </tr>
                <tr>
                    <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">TIN:</td>
                    <td style="padding: 2px; border: 1px solid #000;">{{ $invoice->company->tin ?? 'N/A' }}</td>
                    <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">VRN:</td>
                    <td style="padding: 2px; border: 1px solid #000;">{{ $invoice->company->vrn ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">Time:</td>
                    <td style="padding: 2px; border: 1px solid #000;" colspan="3">{{ $invoice->created_at->format('h:i:s A') }}</td>
                </tr>
            </table>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 8%;">Qty</th>
                <th style="width: 45%;">Description</th>
                <th style="width: 12%;">Ex date</th>
                <th style="width: 15%;">Unit price</th>
                <th style="width: 10%;">uom</th>
                <th style="width: 10%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td class="text-center">{{ number_format($item->quantity, 0) }}</td>
                <td>{{ $item->inventoryItem->name ?? $item->description }}</td>
                <td class="text-center">
                    @if($item->expiry_date)
                    <span class="expiry-date">{{ $item->expiry_date->format('m/Y') }}</span>
                    @endif
                </td>
                <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-center">{{ $item->inventoryItem->unit_of_measure ?? '' }}</td>
                <td class="text-right">{{ number_format($item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-row">
            <span>Amount without tax:</span>
            <span>{{ number_format($invoice->subtotal, 2) }}</span>
        </div>
        <div class="summary-row">
            <span>Total Tax:</span>
            <span>{{ number_format($invoice->vat_amount, 2) }}</span>
        </div>
        <div class="summary-row">
            <span>Total Discount:</span>
            <span>{{ number_format($invoice->discount_amount ?? 0, 2) }}</span>
        </div>
        <div class="summary-row total">
            <span>E &.O.E Total Amount:</span>
            <span>{{ number_format($invoice->total_amount, 2) }}</span>
        </div>
    </div>

    <div class="amount-in-words">
        <strong>{{ ucwords($invoice->getAmountInWords()) }}</strong>
    </div>

    @php
    // Calculate old invoices balance (all previous invoices total - paid)
    $oldInvoicesBalance = \App\Models\Sales\SalesInvoice::where('customer_id', $invoice->customer_id)
    ->where('id', '!=', $invoice->id)
    ->selectRaw('SUM(total_amount - paid_amount) as old_balance')
    ->value('old_balance') ?? 0;

    $totalBalance = $invoice->balance_due + $oldInvoicesBalance;
    @endphp

    <div class="balance-section">
        <div class="balance-row">
            <span>Outstanding Today:</span>
            <span>{{ number_format($invoice->balance_due, 2) }}</span>
        </div>
        <div class="balance-row">
            <span>Old:</span>
            <span>{{ number_format($oldInvoicesBalance, 2) }}</span>
        </div>
        <div class="balance-row">
            <span>Total:</span>
            <span>{{ number_format($totalBalance, 2) }}</span>
        </div>
    </div>

    @php
    // Get payment history for this invoice
    $payments = $invoice->payments()->with(['user', 'bankAccount', 'cashDeposit.type'])->get();
    $receipts = $invoice->receipts()->with(['user', 'bankAccount'])->get();

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
            'bank_account' => $receipt->bankAccount,
            'approved' => $receipt->approved,
            'id' => $receipt->id,
            'encoded_id' => $receipt->hash_id
        ]);
    }

    // Sort by date
    $allPayments = $allPayments->sortBy('date');
    @endphp

    @if($allPayments->count() > 0)
    <div class="payment-history">
        <h3 style="font-size: 11px; font-weight: bold; margin: 8px 0 5px 0; text-align: center; border-bottom: 1px solid #000; padding-bottom: 3px;">PAYMENT HISTORY</h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 8px; margin-bottom: 10px;">
            <thead>
                <tr style="background-color: #f5f5f5;">
                    <th style="border: 1px solid #000; padding: 3px; text-align: left; font-size: 8px;">Date</th>
                    <th style="border: 1px solid #000; padding: 3px; text-align: left; font-size: 8px;">Type</th>
                    <th style="border: 1px solid #000; padding: 3px; text-align: left; font-size: 8px;">Description</th>
                    <th style="border: 1px solid #000; padding: 3px; text-align: right; font-size: 8px;">Amount</th>
                    <th style="border: 1px solid #000; padding: 3px; text-align: left; font-size: 8px;">Method</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allPayments as $payment)
                <tr>
                    <td style="border: 1px solid #000; padding: 3px; font-size: 8px;">{{ $payment['date']->format('d/m/Y') }}</td>
                    <td style="border: 1px solid #000; padding: 3px; font-size: 8px; text-transform: uppercase;">{{ $payment['type'] }}</td>
                    <td style="border: 1px solid #000; padding: 3px; font-size: 8px;">{{ $payment['description'] ?? 'N/A' }}</td>
                    <td style="border: 1px solid #000; padding: 3px; font-size: 8px; text-align: right;">{{ number_format($payment['amount'], 2) }}</td>
                    <td style="border: 1px solid #000; padding: 3px; font-size: 8px;">
                        @if($payment['bank_account'])
                            {{ $payment['bank_account']->name }}
                        @elseif($payment['cash_deposit_id'] && isset($payment['data']->cashDeposit))
                            {{ $payment['data']->cashDeposit->type->name ?? 'Cash Deposit' }}
                        @else
                            Cash
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td colspan="3" style="border: 1px solid #000; padding: 3px; font-size: 8px; text-align: right;">Total Payments:</td>
                    <td style="border: 1px solid #000; padding: 3px; font-size: 8px; text-align: right;">{{ number_format($invoice->paid_amount, 2) }}</td>
                    <td style="border: 1px solid #000; padding: 3px; font-size: 8px;"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    @if($invoice->early_payment_discount_enabled && $invoice->isEarlyPaymentDiscountValid() && $invoice->calculateEarlyPaymentDiscount() > 0)
    <div class="early-payment-section">
        <h3 style="font-size: 11px; font-weight: bold; margin: 8px 0 5px 0; text-align: center; border-bottom: 1px solid #000; padding-bottom: 3px;">EARLY PAYMENT DISCOUNT</h3>
        <div style="background-color: #f8f9fa; border: 1px solid #000; padding: 8px; margin-bottom: 10px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span style="font-weight: bold;">Early Payment Discount:</span>
                <span style="font-weight: bold; color: #28a745;">{{ number_format($invoice->calculateEarlyPaymentDiscount(), 2) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span>Valid until:</span>
                <span>{{ $invoice->getEarlyPaymentDiscountExpiryDate()->format('d/m/Y') }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span>Discount rate:</span>
                <span>{{ $invoice->early_payment_discount_rate }}{{ $invoice->early_payment_discount_type === 'percentage' ? '%' : ' TZS' }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-weight: bold; border-top: 1px solid #000; padding-top: 5px; margin-top: 5px;">
                <span>Amount due with early payment discount:</span>
                <span style="color: #28a745; font-size: 12px;">{{ number_format($invoice->getAmountDueWithEarlyDiscount(), 2) }}</span>
            </div>
        </div>
    </div>
    @endif

    @if($invoice->late_payment_fees_enabled && $invoice->isOverdue() && $invoice->calculateLatePaymentFees() > 0)
    <div class="late-payment-section">
        <h3 style="font-size: 11px; font-weight: bold; margin: 8px 0 5px 0; text-align: center; border-bottom: 1px solid #000; padding-bottom: 3px;">LATE PAYMENT FEES</h3>
        <div style="background-color: #f8d7da; border: 1px solid #000; padding: 8px; margin-bottom: 10px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span style="font-weight: bold;">Late Payment Fees:</span>
                <span style="font-weight: bold; color: #dc3545;">+{{ number_format($invoice->calculateLatePaymentFees(), 2) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span>Late Payment Terms:</span>
                <span>{{ $invoice->getLatePaymentFeesText() }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span>Overdue Days:</span>
                <span style="color: #dc3545;">{{ $invoice->getOverdueDays() }} days</span>
            </div>
            @if($invoice->hasLatePaymentFeesApplied())
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 7px; color: #856404;">
                <span>Fees Applied:</span>
                <span>Yes ({{ $invoice->late_payment_fees_type === 'monthly' ? 'Monthly fees applied for current period' : 'One-time fee applied' }})</span>
            </div>
            @endif
            <div style="display: flex; justify-content: space-between; font-weight: bold; border-top: 1px solid #000; padding-top: 5px; margin-top: 5px;">
                <span>Amount due with late payment fees:</span>
                <span style="color: #dc3545; font-size: 12px;">{{ number_format($invoice->getAmountDueWithLateFees(), 2) }}</span>
            </div>
        </div>
    </div>
    @endif

    @if($invoice->paid_amount > 0)
    <div class="amount-due-section">
        <h3 style="font-size: 11px; font-weight: bold; margin: 8px 0 5px 0; text-align: center; border-bottom: 1px solid #000; padding-bottom: 3px;">AMOUNT DUE</h3>
        <div style="background-color: #fff3cd; border: 1px solid #000; padding: 8px; margin-bottom: 10px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span>Total Invoice Amount:</span>
                <span>{{ number_format($invoice->total_amount, 2) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span>Amount Paid:</span>
                <span style="color: #28a745;">{{ number_format($invoice->paid_amount, 2) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-weight: bold; border-top: 1px solid #000; padding-top: 5px; margin-top: 5px;">
                <span>Balance Due:</span>
                <span style="color: #dc3545; font-size: 12px;">{{ number_format($invoice->balance_due, 2) }}</span>
            </div>
        </div>
    </div>
    @endif

    <div class="payment-terms">
        <h6>PAYMENT TERMS</h6>
        @if($invoice->late_payment_fees_enabled)
            <div>{{ $invoice->getLatePaymentFeesText() }} - Payment due within {{ $invoice->payment_days ?? 30 }} days</div>
        @else
            <div>Payment due within {{ $invoice->payment_days ?? 30 }} days</div>
        @endif
    </div>

    <div class="footer">
        <div class="signature-line">
            <strong>Signature................................................</strong>
        </div>

        <div class="terms">
            <ol>
                <li>Goods once sold will not be accepted back</li>
                <li>Kindly verify quantities, price and date of expiry</li>
                <li>Received the above goods in good order</li>
            </ol>
        </div>

        <div style="margin-top: 10px;">
            <strong>{{ $invoice->customer->name ?? 'Customer Name' }}</strong>
        </div>

        <div class="page-info">
            <div>Invoice No: {{ $invoice->invoice_number }}</div>
            <div>Page 1 of 1</div>
        </div>
    </div>
    </div> <!-- Close print-container -->

    <script nonce="{{ $cspNonce ?? '' }}">
        // Auto-print when page loads
        window.onload = function() {
            window.print();
        }

    </script>
</body>
</html>
