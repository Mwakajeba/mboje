<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt Voucher - {{ $receiptVoucher->reference ?? 'RCP-' . $receiptVoucher->id }}</title>
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

        /* Receipt title */
        .receipt-title {
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

        .received-from {
            width: 48%;
            font-size: 10px;
        }

        .received-from strong {
            color: #1e40af;
        }

        .receipt-box {
            width: 48%;
            text-align: right;
        }

        .receipt-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-left: auto;
        }

        .receipt-box td {
            border: 1px solid #cbd5e1;
            padding: 4px;
        }

        .receipt-box td:nth-child(even) {
            text-align: right;
        }

        .receipt-box strong {
            color: #1e40af;
        }

        /* Items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 10px;
            clear: both;
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
            color: #1e40af;
            padding: 8px;
            border-radius: 3px;
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

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .signature-box {
            text-align: center;
            width: 30%;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 2px;
        }

        /* Invoice Summary */
        .invoice-summary {
            margin-top: 15px;
            padding: 10px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 3px;
        }

        .invoice-summary-title {
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 8px;
            font-size: 11px;
        }

        .invoice-summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 10px;
        }

    </style>

</head>
<body>
    <div class="container">

        {{-- Header --}}
        <div class="text-left">
            @php
            $company = $invoice->company ?? $receiptVoucher->user->company ?? null;
            @endphp
            @if($company && $company->logo)
            @php
            // Logo is stored in storage/app/public (via "public" disk)
            $logo = $company->logo; // e.g. "uploads/companies/company_1_1768466462.png"
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
                <img src="{{ $logoBase64 }}" alt="{{ $company->name . ' logo' }}" class="company-logo">
            </div>
            @endif
            @endif
            <div style="float: right; width: 50%; text-align: left; margin-left: 15%;">
                <div class="company-name">{{ $company->name ?? 'SmartFinance' }}</div>
                <div class="company-details">
                    @if($company && $company->address)
                    P.O Box: {{ $company->address }} <br>
                    @endif
                    @if($company && $company->phone)
                    Phone: {{ $company->phone }} <br>
                    @endif
                    @if($company && $company->email)
                    Email: {{ $company->email }}
                    @endif
                </div>
            </div>
        </div>
        <div style="clear: both;"></div>

        @if($receiptVoucher->bankAccount)
        <div class="payment-method-bar" style="text-align: center;">
            <strong>PAYMENT METHOD :</strong>
        </div>
        <div class="payment-details">
            <strong>{{ strtoupper($receiptVoucher->bankAccount->name ?? $receiptVoucher->bankAccount->bank_name ?? 'BANK') }}:</strong> {{ $receiptVoucher->bankAccount->account_number ?? 'N/A' }}
        </div>
        @endif

        <div class="receipt-title">RECEIPT VOUCHER</div>
        <hr>
        {{-- Received From + Receipt Info --}}
        <div class="info-section">
            <div class="received-from" style="float: left; width: 48%;">
                <strong>Received from :</strong><br>
                @if($receiptVoucher->payee_type === 'customer' && $receiptVoucher->customer)
                <strong>{{ $receiptVoucher->customer->name }}</strong><br>
                @if($receiptVoucher->customer->phone)
                {{ $receiptVoucher->customer->phone }}<br>
                @endif
                @if($receiptVoucher->customer->email)
                {{ $receiptVoucher->customer->email }}<br>
                @endif
                @if($receiptVoucher->customer->address)
                {{ $receiptVoucher->customer->address }}<br>
                @endif
                @elseif($receiptVoucher->payee_type === 'employee' && $receiptVoucher->employee)
                <strong>{{ $receiptVoucher->employee->full_name }}</strong><br>
                @if($receiptVoucher->employee->employee_number)
                Employee No: {{ $receiptVoucher->employee->employee_number }}<br>
                @endif
                @elseif($receiptVoucher->payee_type === 'other')
                <strong>{{ $receiptVoucher->payee_name ?? 'N/A' }}</strong><br>
                @elseif(isset($invoice) && $invoice->customer)
                <strong>{{ $invoice->customer->name ?? 'Walk-in Customer' }}</strong><br>
                @if($invoice->customer->phone)
                {{ $invoice->customer->phone }}<br>
                @endif
                @if($invoice->customer->email)
                {{ $invoice->customer->email }}<br>
                @endif
                @if($invoice->customer->address)
                {{ $invoice->customer->address }}<br>
                @endif
                @else
                <strong>{{ $receiptVoucher->payee_name ?? 'N/A' }}</strong><br>
                @endif
                <br>
                <strong>Created By:</strong><br>
                @php
                $creator = $receiptVoucher->user ?? null;
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

            <div class="receipt-box" style="text-align: right; float: left; width: 48%;">
                <table style="margin-top: 8px;">
                    <tr>
                        <td><strong>Receipt no:</strong></td>
                        <td>{{ $receiptVoucher->reference ?? 'RCP-' . $receiptVoucher->id }}</td>
                        <td><strong>Date :</strong></td>
                        <td>{{ $receiptVoucher->date ? $receiptVoucher->date->format('d F Y') : 'N/A' }}</td>
                    </tr>
                    @if(isset($invoice))
                    <tr>
                        <td><strong>Invoice no:</strong></td>
                        <td colspan="3">{{ $invoice->invoice_number }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Currency:</strong></td>
                        <td>{{ $receiptVoucher->currency ?? 'TZS' }}</td>
                        <td><strong>Ex Rate:</strong></td>
                        <td>{{ number_format($receiptVoucher->exchange_rate ?? 1, 2) }}</td>
                    </tr>
                    @if($receiptVoucher->branch)
                    <tr>
                        <td><strong>Branch:</strong></td>
                        <td colspan="3">{{ $receiptVoucher->branch->name ?? 'N/A' }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Time:</strong></td>
                        <td colspan="3">{{ $receiptVoucher->created_at->format('H:i:s') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div style="clear: both; margin-bottom: 10px;"></div>

        @if($receiptVoucher->description)
        <div class="notes" style="clear: both; margin-bottom: 10px;">
            <strong>Description:</strong><br>
            {{ $receiptVoucher->description }}
        </div>
        @endif

        {{-- Items --}}
        @if($receiptVoucher->receiptItems && $receiptVoucher->receiptItems->count() > 0)
        <table class="items-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Account</th>
                    <th>Account Code</th>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receiptVoucher->receiptItems as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->chartAccount->account_name ?? 'N/A' }}</td>
                    <td class="text-center">{{ $item->chartAccount->account_code ?? 'N/A' }}</td>
                    <td>{{ $item->description ?: '-' }}</td>
                    <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div style="margin-top: 10px; padding: 10px; text-align: center; color: #999; font-style: italic;">
            No receipt items found
        </div>
        @endif

        {{-- Totals --}}
        @php
        $colspan = 4;
        @endphp
        <table class="totals-table" style="margin-top: 10px;">
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;"><strong>GRAND TOTAL: </strong></td>
                <td><strong>{{ number_format($receiptVoucher->amount, 2) }}</strong></td>
            </tr>
        </table>

        @if(method_exists($receiptVoucher, 'getAmountInWords'))
        <div style="margin-top:5px;font-style:italic;">
            <strong>{{ ucwords($receiptVoucher->getAmountInWords()) }}</strong>
        </div>
        @endif

        @if(isset($invoice))
        <div class="invoice-summary">
            <div class="invoice-summary-title">INVOICE SUMMARY</div>
            <div class="invoice-summary-row">
                <span>Total Invoice:</span>
                <span><strong>{{ number_format($invoice->total_amount, 2) }}</strong></span>
            </div>
            <div class="invoice-summary-row">
                <span>Total Paid:</span>
                <span><strong>{{ number_format($invoice->paid_amount, 2) }}</strong></span>
            </div>
            <div class="invoice-summary-row">
                <span>Balance Due:</span>
                <span><strong>{{ number_format($invoice->balance_due, 2) }}</strong></span>
            </div>
        </div>
        @endif

        {{-- Footer --}}
        <hr>
        <div class="footer">
            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Prepared By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        {{ $receiptVoucher->user->name ?? 'N/A' }}
                    </div>
                </div>
                
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Approved By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        @if($receiptVoucher->approved && $receiptVoucher->approvedBy)
                        {{ $receiptVoucher->approvedBy->name }}
                        @elseif($receiptVoucher->approved)
                        {{ $receiptVoucher->user->name ?? 'N/A' }}
                        @else
                        <span style="color: #999;">Pending Approval</span>
                        @endif
                    </div>
                </div>
                
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Received By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        @if($receiptVoucher->payee_type === 'customer' && $receiptVoucher->customer)
                        {{ $receiptVoucher->customer->name }}
                        @elseif($receiptVoucher->payee_type === 'employee' && $receiptVoucher->employee)
                        {{ $receiptVoucher->employee->full_name }}
                        @elseif($receiptVoucher->payee_type === 'other')
                        {{ $receiptVoucher->payee_name ?? 'N/A' }}
                        @elseif(isset($invoice) && $invoice->customer)
                        {{ $invoice->customer->name ?? 'Walk-in Customer' }}
                        @else
                        {{ $receiptVoucher->payee_name ?? 'N/A' }}
                        @endif
                    </div>
                </div>
            </div>

            <div class="text-center" style="font-size:9px; margin-top: 20px;">
                Receipt Voucher No: {{ $receiptVoucher->reference ?? 'RCP-' . $receiptVoucher->id }} <br>
                Page 1 of 1
            </div>
        </div>

    </div>

</body>
</html>
