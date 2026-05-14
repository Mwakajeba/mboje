<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice/Delivery note - {{ $invoice->invoice_number }}</title>
    <style>
        /* Email-safe styles */
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #000000;
            background-color: #FFFFFF;
            margin: 0;
            padding: 20px;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #FFFFFF;
        }
        .header {
            text-align: center;
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
            padding-bottom: 5px;
        }
        .company-name {
            color: #b22222;
            font-size: 15px;
            font-weight: bold;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .company-logo {
            display: inline-block;
            vertical-align: middle;
            margin-right: 8px;
        }
        .company-logo img {
            width: 35px;
            height: 35px;
        }
        .company-details {
            font-size: 8px;
            line-height: 1.3;
            margin-top: 2px;
        }
        .mobile-money {
            font-size: 9px;
            margin-top: 5px;
        }
        .invoice-title {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            margin: 8px 0;
            text-transform: uppercase;
        }
        .invoice-details {
            font-size: 9px;
            margin-bottom: 8px;
        }
        .bill-to {
            margin-bottom: 10px;
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
        .balance-section {
            font-size: 9px;
            margin-top: 8px;
        }
        .balance-row {
            display: flex;
            justify-content: space-between;
        }
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
        .payment-history {
            margin-top: 10px;
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
        .early-payment-section {
            margin-top: 10px;
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
        .amount-due-section {
            margin-top: 10px;
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
        .custom-message {
            background-color: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        @if($customMessage)
        <div class="custom-message">
            <p style="margin: 0;">{{ $customMessage }}</p>
        </div>
        @endif

        <div style="text-align: center; margin: 15px 0; padding: 12px; background-color: #e7f3ff; border: 2px solid #007bff; border-radius: 5px;">
            <a href="{{ route('sales.invoices.export-pdf', $invoice->encoded_id) }}?download=1" 
               style="display: inline-block; background-color: #007bff; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 11px;">
                📥 Download PDF Invoice
            </a>
        </div>

        <div class="header">
            <div class="company-info">
                <div class="company-logo">
                    @if($invoice->company->logo && file_exists(public_path('storage/' . $invoice->company->logo)))
                    <img src="{{ asset('storage/' . $invoice->company->logo) }}" alt="Logo" style="width: 35px; height: 35px;">
                    @endif
                </div>
                <h1 class="company-name">{{ $invoice->company->name ?? 'SMARTACCOUNTING' }}</h1>
                <div class="company-details">
                    <div><strong>P.O. Box:</strong> {{ $invoice->company->address ?? 'P.O.BOX 00000, City, Country' }}</div>
                    <div><strong>Phone:</strong> {{ $invoice->company->phone ?? '+255 000 000 000' }}</div>
                    <div><strong>Email:</strong> {{ $invoice->company->email ?? 'company@email.com' }}</div>
                </div>
                <div class="mobile-money">
                    <div style="font-weight: bold; margin-bottom: 3px; font-size: 8px;">Please pay through one of the following methods:</div>
                    @if($bankAccounts && $bankAccounts->count() > 0)
                    <div style="font-size: 8px;">
                        @foreach($bankAccounts as $account)
                        <div style="margin-bottom: 2px;">
                            <strong>{{ strtoupper($account->name) }}:</strong> {{ $account->account_number }}
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div style="font-size: 8px;">No payment methods available</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="invoice-title">Tax Invoice/Delivery note</div>

        <div class="invoice-details">
            <div class="bill-to">
                <div style="font-weight: bold; margin-bottom: 2px;">Bill To:</div>
                <div>{{ $invoice->customer->name ?? 'Customer Name' }}</div>
                <div style="margin-top: 5px; font-weight: bold;">User name:</div>
                <div>{{ $invoice->createdBy->name ?? 'User Name' }}</div>
            </div>
            <div class="invoice-info">
                <table style="width: 100%; border-collapse: collapse; font-size: 8px;">
                    <tr>
                        <td style="padding: 2px; border: 1px solid #000; font-weight: bold; width: 20%;">Invoice No:</td>
                        <td style="padding: 2px; border: 1px solid #000; width: 30%;">{{ $invoice->invoice_number }}</td>
                        <td style="padding: 2px; border: 1px solid #000; font-weight: bold; width: 20%;">Date:</td>
                        <td style="padding: 2px; border: 1px solid #000; width: 30%;">{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                    </tr>
                    @if($invoice->salesOrder)
                    <tr>
                        <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">History:</td>
                        <td style="padding: 2px; border: 1px solid #000;" colspan="3">
                            Order: {{ $invoice->salesOrder->order_number }}
                            @if($invoice->salesOrder->proforma)
                                | Proforma: {{ $invoice->salesOrder->proforma->proforma_number }}
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
                    <td>{{ $item->inventoryItem->name ?? $item->item_name ?? $item->description }}</td>
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
        // Calculate old invoices balance
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
        // Get payment history
        $payments = $invoice->payments()->with(['user', 'bankAccount', 'cashDeposit.type'])->get();
        $receipts = $invoice->receipts()->with(['user', 'bankAccount'])->get();

        $allPayments = collect();

        foreach($payments as $payment) {
            $allPayments->push([
                'type' => 'payment',
                'date' => $payment->date,
                'amount' => $payment->amount,
                'description' => $payment->description,
                'bank_account' => $payment->bankAccount,
                'cash_deposit' => $payment->cashDeposit,
            ]);
        }

        foreach($receipts as $receipt) {
            $allPayments->push([
                'type' => 'receipt',
                'date' => $receipt->date,
                'amount' => $receipt->amount,
                'description' => $receipt->description,
                'bank_account' => $receipt->bankAccount,
            ]);
        }

        $allPayments = $allPayments->sortBy('date');
        @endphp

        @if($allPayments->count() > 0)
        <div class="payment-history">
            <h3>PAYMENT HISTORY</h3>
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
                            @if(isset($payment['bank_account']) && $payment['bank_account'])
                                {{ $payment['bank_account']->name }}
                            @elseif(isset($payment['cash_deposit']) && $payment['cash_deposit'])
                                {{ $payment['cash_deposit']->type->name ?? 'Cash Deposit' }}
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
            <h3>EARLY PAYMENT DISCOUNT</h3>
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

        @if($invoice->paid_amount > 0)
        <div class="amount-due-section">
            <h3>AMOUNT DUE</h3>
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
            <div>Payable within 30 days after which 10% interest will be applicable per month</div>
        </div>

        <div style="text-align: center; margin: 20px 0; padding: 15px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">
            <a href="{{ route('sales.invoices.export-pdf', $invoice->encoded_id) }}?download=1" 
               style="display: inline-block; background-color: #007bff; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 12px;">
                📄 Download PDF Invoice
            </a>
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
    </div>
</body>
</html>
