<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
        }

        .container {
            width: 100%;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        hr {
            border: none;
            border-top: 2px solid #3b82f6;
            margin: 8px 0;
        }

        /* Header */
        .logo-section {
            margin-bottom: 10px;
        }

        .company-logo {
            max-height: 80px;
            max-width: 120px;
            object-fit: contain;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
        }

        .company-details {
            font-size: 10px;
        }

        /* Payment methods */
        .payment-methods {
            font-size: 10px;
            margin: 8px 0;
        }

        .payment-method-bar {
            background-color: #1e3a8a;
            color: #fff;
            padding: 8px;
            font-weight: bold;
            margin-top: 10px;
        }

        .payment-details {
            padding: 8px;
            background-color: #f8fafc;
        }

        .payment-details strong {
            color: #1e40af;
        }

        /* Invoice title */
        .invoice-title {
            font-weight: bold;
            text-align: center;
            font-size: 18px;
            margin: 10px 0;
            color: #1e40af;
        }

        /* Info section */
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .bill-to {
            width: 48%;
            font-size: 10px;
        }

        .bill-to strong {
            color: #1e40af;
        }

        .invoice-box {
            width: 48%;
            text-align: right;
        }

        .invoice-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-left: auto;
        }

        .invoice-box td {
            border: 1px solid #cbd5e1;
            padding: 4px;
        }

        .invoice-box td:nth-child(even) {
            text-align: right;
        }

        .invoice-box strong {
            color: #1e40af;
        }

        /* Items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 10px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #cbd5e1;
            padding: 5px;
        }

        .items-table th {
            text-align: center;
            font-weight: bold;
            background-color: #1e3a8a;
            color: #fff;
        }

        .items-table tbody tr:nth-child(even) {
            background-color: #dbeafe;
        }

        .items-table tbody tr:nth-child(odd) {
            background-color: #fff;
        }

        /* Totals */
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 10px;
        }

        .totals-table td {
            padding: 4px 5px;
            border: none;
        }

        .totals-table td:last-child {
            text-align: right;
            padding-right: 5px;
        }

        .totals-table tr:last-child td {
            background-color: #1e3a8a;
            color: #fff;
            font-weight: bold;
            padding: 8px 5px;
        }

        .totals-table tr:last-child td:last-child {
            background-color: #dbeafe;
            color: #1e3a8a;
            padding: 8px;
            border-radius: 3px;
        }

        /* Receipt History */
        .receipt-history {
            margin-top: 15px;
            font-size: 10px;
        }

        .receipt-history-title {
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 8px;
            font-size: 11px;
        }

        .receipt-history-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 5px;
        }

        .receipt-history-table th {
            background-color: #1e3a8a;
            color: #fff;
            padding: 5px;
            text-align: left;
            border: 1px solid #cbd5e1;
            font-weight: bold;
        }

        .receipt-history-table td {
            padding: 5px;
            border: 1px solid #cbd5e1;
        }

        .receipt-history-table tbody tr:nth-child(even) {
            background-color: #dbeafe;
        }

        .receipt-history-table tbody tr:nth-child(odd) {
            background-color: #fff;
        }

        .receipt-history-table .text-right {
            text-align: right;
        }

        .receipt-history-table .text-center {
            text-align: center;
        }

        /* Footer */
        .footer {
            margin-top: 20px;
            font-size: 10px;
        }

        .footer strong {
            color: #1e40af;
        }

        .signature {
            margin-top: 20px;
        }

        .footer hr {
            border-top: 1px solid #dbeafe;
            margin: 15px 0;
        }

    </style>

</head>
<body>
    <div class="container">

        {{-- Header --}}
        <div class="text-left">
            @if($invoice->company && $invoice->company->logo)
            @php
            // Logo is stored in storage/app/public (via "public" disk)
            $logo = $invoice->company->logo; // e.g. "uploads/companies/company_1_1768466462.png"
            $logoPath = public_path('storage/' . ltrim($logo, '/'));

            // Convert image to base64 for DomPDF compatibility
            $logoBase64 = null;
            if (file_exists($logoPath)) {
            $imageData = file_get_contents($logoPath);
            $imageInfo = getimagesize($logoPath);
            if ($imageInfo !== false) {
            $mimeType = $imageInfo['mime'];
            $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
            }
            }
            @endphp
            @if($logoBase64)
            <div class="logo-section" style="float: left; width: 45%;">
                <img src="{{ $logoBase64 }}" alt="{{ $invoice->company->name . ' logo' }}" class="company-logo">
            </div>
            @endif
            @endif
            <div style="float: right; width: 50%; text-align: left; margin-left: 15%;">
                <div class="company-name">{{ $invoice->company->name }}</div>
                <div class="company-details">
                    Address: {{ $invoice->company->address }} <br>
                    Phone: {{ $invoice->company->phone }} <br>
                    Email: {{ $invoice->company->email }}
                </div>
            </div>
        </div>
        <div style="clear: both;"></div>

        @if($bankAccounts && $bankAccounts->count() > 0)
        <div class="payment-method-bar" style="text-align: center;">
            <strong>PAYMENT METHOD :</strong>
        </div>
        <div class="payment-details">
            @foreach($bankAccounts as $account)
            <strong>{{ strtoupper($account->name ?? $account->bank_name ?? 'BANK') }}:</strong> {{ $account->account_number ?? 'N/A' }} &nbsp;&nbsp;
            @endforeach
        </div>
        @endif



        <div class="invoice-title">SALES INVOICE</div>
        <hr>
        {{-- Bill To + Invoice Info --}}
        <div class="info-section">
            <div class="bill-to" style="float: left; width: 48%;">
                <strong>Invoice to :</strong><br>
                <strong>{{ $invoice->customer->name ?? 'N/A' }}</strong><br>
                @if($invoice->customer && $invoice->customer->phone)
                {{ $invoice->customer->phone }}<br>
                @endif
                @if($invoice->customer && $invoice->customer->email)
                {{ $invoice->customer->email }}<br>
                @endif
                @if($invoice->customer && $invoice->customer->address)
                {{ $invoice->customer->address }}<br>
                @endif
                <br>
                <strong>Created By:</strong><br>
                @php
                $creator = $invoice->createdBy ?? null;
                $creatorRole = $creator && method_exists($creator, 'roles') ? $creator->roles->first() : null;
                @endphp
                @if($creator)
                {{ $creator->name }}
                @if($creatorRole)
                ({{ $creatorRole->name }})
                @endif
                @else
                System
                @endif
            </div>

            <div class="invoice-box" style="text-align: right; float: left; width: 48%;">
                <table style="margin-top: 8px;">
                    <tr>
                        <td><strong>Invoice no:</strong></td>
                        <td>{{ $invoice->invoice_number }}</td>
                        <td><strong>Date :</strong></td>
                        <td>{{ $invoice->invoice_date->format('d F Y') }}</td>
                    </tr>
                    @if($invoice->reference_no)
                    <tr>
                        <td><strong>Reference No.:</strong></td>
                        <td colspan="3">{{ $invoice->reference_no }}</td>
                    </tr>
                    @endif
                    @if($invoice->due_date)
                    <tr>
                        <td><strong>Due Date:</strong></td>
                        <td colspan="3">{{ $invoice->due_date->format('d F Y') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Currency:</strong></td>
                        <td>{{ $invoice->currency ?? 'TZS' }}</td>
                        <td><strong>Ex Rate:</strong></td>
                        <td>{{ number_format($invoice->exchange_rate ?? 1, 2) }}</td>
                    </tr>
                    @if($invoice->customer && $invoice->customer->tin_number)
                    <tr>
                        <td><strong>TIN:</strong></td>
                        <td>{{ $invoice->customer->tin_number }}</td>
                        <td><strong>VRN:</strong></td>
                        <td>{{ $invoice->customer->vat_number ?? 'N/A' }}</td>
                    </tr>
                    @endif
                    @if($invoice->branch)
                    <tr>
                        <td><strong>Branch:</strong></td>
                        <td colspan="3">{{ $invoice->branch->name ?? 'N/A' }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Time:</strong></td>
                        <td colspan="3">{{ $invoice->created_at->format('H:i:s') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div style="clear: both; margin-bottom: 10px;"></div>

        @if($invoice->notes)
        <div class="notes" style="clear: both; margin-bottom: 10px;">
            <strong>Description:</strong><br>
            {{ $invoice->notes }}
        </div>
        @endif
        {{-- Items --}}
        @php
        $hasExpiryDates = $invoice->items->contains(function($item) {
        return $item->expiry_date !== null;
        });
        @endphp
        <table class="items-table">
            <thead>
                <tr>
                    <th>Qty</th>
                    <th>Name</th>
                    @if($hasExpiryDates)
                    <th>Exp date</th>
                    @endif
                    <th>Unit price</th>
                    <th>UOM</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td>{{ optional($item->inventoryItem)->name ?? $item->item_name }}</td>
                    @if($hasExpiryDates)
                    <td class="text-center">
                        {{ $item->expiry_date ? $item->expiry_date->format('m/Y') : '' }}
                    </td>
                    @endif
                    <td class="text-right">{{ number_format($item->unit_price,2) }}</td>
                    <td class="text-center">{{ $item->unit_of_measure }}</td>
                    <td class="text-right">{{ number_format($item->line_total,2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        @php
        $vatRate = 0;
        if ($invoice->vat_amount > 0) {
        if (method_exists($invoice, 'getVatRate')) {
        $vatRate = $invoice->getVatRate();
        } elseif (isset($invoice->vat_rate) && $invoice->vat_rate > 0) {
        $vatRate = $invoice->vat_rate;
        } elseif ($invoice->subtotal > 0) {
        $vatRate = ($invoice->vat_amount / $invoice->subtotal) * 100;
        }
        }
        // Count columns: Qty, Name, (Exp date if exists), Unit price, UOM, Amount
        $colspan = $hasExpiryDates ? 5 : 4;
        @endphp
        <table class="totals-table">
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Sub Total: </td>
                <td>{{ number_format($invoice->subtotal,2) }}</td>
            </tr>
            @if($invoice->vat_amount > 0)
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Tax {{ number_format($vatRate, 1) }}%: </td>
                <td>{{ number_format($invoice->vat_amount,2) }}</td>
            </tr>
            @endif
            @if($invoice->discount_amount > 0)
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Total Discount:</td>
                <td>{{ number_format($invoice->discount_amount ?? 0,2) }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;"><strong>GRAND TOTAL: </strong></td>
                <td><strong>{{ number_format($invoice->total_amount,2) }}</strong></td>
            </tr>
        </table>

        @if(method_exists($invoice, 'getAmountInWords'))
        <div style="margin-top:5px;font-style:italic;">
            <strong>{{ ucwords($invoice->getAmountInWords()) }}</strong>
        </div>
        @endif

        {{-- Outstanding --}}
        @php
        $oldInvoicesBalance = \App\Models\Sales\SalesInvoice::where('customer_id', $invoice->customer_id)
        ->where('id', '!=', $invoice->id)
        ->selectRaw('SUM(total_amount - paid_amount) as old_balance')
        ->value('old_balance') ?? 0;
        $totalBalance = $invoice->balance_due + $oldInvoicesBalance;
        @endphp
        <div style="margin-top:10px;">
            Outstanding Today: {{ number_format($invoice->balance_due,2) }} <br>
            Old: {{ number_format($oldInvoicesBalance,2) }} <br>
            <strong>Total: {{ number_format($totalBalance,2) }}</strong>
        </div>

        {{-- Receipt History --}}
        @php
        // Use receipts loaded in controller, or load them if not provided
        $receipts = isset($receipts) ? $receipts->sortBy([['date', 'asc'], ['created_at', 'asc']]) : $invoice->receipts()->with(['bankAccount', 'user'])->orderBy('date', 'asc')->orderBy('created_at', 'asc')->get();
        @endphp
        @if($receipts->count() > 0)
        <div class="receipt-history">
            <div class="receipt-history-title">PAYMENT HISTORY</div>
            <table class="receipt-history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Receipt No</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Bank Account</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($receipts as $receipt)
                    <tr>
                        <td>{{ $receipt->date ? $receipt->date->format('d/m/Y') : ($receipt->created_at ? $receipt->created_at->format('d/m/Y') : 'N/A') }}</td>
                        <td>{{ $receipt->reference ?? 'N/A' }}</td>
                        <td class="text-right">{{ number_format($receipt->amount, 2) }}</td>
                        <td>{{ $receipt->payment_method ?? ($receipt->bankAccount ? 'Bank Transfer' : 'Cash') }}</td>
                        <td>{{ $receipt->bankAccount ? $receipt->bankAccount->name : 'N/A' }}</td>
                        <td>{{ $receipt->description ?? '-' }}</td>
                    </tr>
                    @endforeach
                    <tr style="background-color: #e0f2fe; font-weight: bold;">
                        <td colspan="2" style="text-align: right;"><strong>Total Paid:</strong></td>
                        <td class="text-right"><strong>{{ number_format($invoice->paid_amount, 2) }}</strong></td>
                        <td colspan="3"></td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif

        {{-- Footer --}}
        <hr>
        <div class="footer">
            <div style="margin-bottom: 10px;">Thank you for business with us!</div>
            <div><strong>Term and Conditions:</strong><br>Payment due within <b>{{ $invoice->payment_days ?? 30 }} days</b> of receiving this invoice.@if($invoice->late_payment_fees_enabled) There will be {{ number_format($invoice->late_payment_fees_rate ?? 10, 0) }}% interest charge per month on late invoice.@endif</div>

            <div class="signature">
                <strong>Signature:</strong> ________________________________
            </div>

            <ol>
                <li>Goods once sold will not be accepted back</li>
                <li>Kindly verify quantities, price and date of expiry</li>
                <li>Received the above goods in good order</li>
            </ol>

            <strong>{{ $invoice->customer->name }}</strong>

            <div class="text-center" style="font-size:9px;">
                Invoice No: {{ $invoice->invoice_number }} <br>
                Page 1 of 1
            </div>
        </div>

    </div>

</body>
</html>
