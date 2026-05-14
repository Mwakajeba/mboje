<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt - {{ $invoice->invoice_number }}</title>
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
            border-top: 2px solid #ffc107;
            margin: 8px 0;
        }

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
            color: #ffc107;
        }

        .company-details {
            font-size: 10px;
        }

        .receipt-title {
            font-weight: bold;
            text-align: center;
            font-size: 18px;
            margin: 10px 0;
            color: #ffc107;
        }

        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .customer-info {
            width: 48%;
            font-size: 10px;
        }

        .customer-info strong {
            color: #ffc107;
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
            color: #ffc107;
        }

        .payment-details {
            margin-top: 15px;
            font-size: 10px;
        }

        .payment-details table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .payment-details th,
        .payment-details td {
            border: 1px solid #cbd5e1;
            padding: 5px;
        }

        .payment-details th {
            text-align: left;
            font-weight: bold;
            background-color: #ffc107;
            color: #000;
        }

        .payment-details td {
            background-color: #fff;
        }

        .payment-details .text-right {
            text-align: right;
        }

        .footer {
            margin-top: 30px;
            font-size: 9px;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="logo-section text-center">
            @if($company && $company->logo)
                <img src="{{ public_path('storage/' . $company->logo) }}" alt="Company Logo" class="company-logo">
            @endif
            <div class="company-name">{{ $company->name ?? 'Company Name' }}</div>
            @if($branch)
                <div class="company-details">{{ $branch->name ?? '' }}</div>
            @endif
            @if($company && $company->address)
                <div class="company-details">{{ $company->address }}</div>
            @endif
            @if($company && $company->phone)
                <div class="company-details">Tel: {{ $company->phone }}</div>
            @endif
            @if($company && $company->email)
                <div class="company-details">Email: {{ $company->email }}</div>
            @endif
        </div>

        <hr>

        <!-- Title -->
        <div class="receipt-title">PAYMENT RECEIPT</div>

        <!-- Info Section -->
        <div class="info-section">
            <div class="customer-info">
                <strong>Received From:</strong><br>
                {{ $invoice->customer->name ?? 'N/A' }}<br>
                @if($invoice->customer && $invoice->customer->phone)
                    Phone: {{ $invoice->customer->phone }}<br>
                @endif
                @if($invoice->customer && $invoice->customer->email)
                    Email: {{ $invoice->customer->email }}<br>
                @endif
                @if($invoice->customer && $invoice->customer->company_address)
                    {{ $invoice->customer->company_address }}
                @endif
            </div>

            <div class="receipt-box">
                <table>
                    <tr>
                        <td><strong>Receipt For:</strong></td>
                        <td>Invoice #{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td><strong>Date:</strong></td>
                        <td>{{ now()->format('M d, Y') }}</td>
                    </tr>
                    @if($invoice->contract)
                    <tr>
                        <td><strong>Contract:</strong></td>
                        <td>{{ $invoice->contract->contract_number }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Payment Details -->
        <div class="payment-details">
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Payment for Invoice #{{ $invoice->invoice_number }}</strong></td>
                        <td class="text-right"><strong>TZS {{ number_format($invoice->deposit_applied ?? 0, 2) }}</strong></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr style="background-color: #f8f9fa; font-weight: bold;">
                        <td><strong>Total Amount Received:</strong></td>
                        <td class="text-right"><strong>TZS {{ number_format($invoice->deposit_applied ?? 0, 2) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if($invoice->notes)
        <div style="margin-top: 15px; font-size: 10px;">
            <strong>Notes:</strong><br>
            {{ $invoice->notes }}
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <hr>
            <p>This is a computer-generated receipt. No signature is required.</p>
            <p>Generated on: {{ now()->format('M d, Y h:i A') }}</p>
        </div>
    </div>
</body>
</html>
