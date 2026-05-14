<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - {{ $invoice->invoice_number }}</title>
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
            border-top: 2px solid #198754;
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
            color: #198754;
        }

        .company-details {
            font-size: 10px;
        }

        .invoice-title {
            font-weight: bold;
            text-align: center;
            font-size: 18px;
            margin: 10px 0;
            color: #198754;
        }

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
            color: #198754;
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
            color: #198754;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-top: 15px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #cbd5e1;
            padding: 5px;
        }

        .items-table th {
            text-align: left;
            font-weight: bold;
            background-color: #198754;
            color: #fff;
        }

        .items-table td {
            background-color: #fff;
        }

        .items-table .text-right {
            text-align: right;
        }

        .items-table .text-center {
            text-align: center;
        }

        .footer {
            margin-top: 30px;
            font-size: 9px;
            text-align: center;
            color: #666;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 9px;
        }

        .status-draft {
            background-color: #6c757d;
            color: #fff;
        }

        .status-sent {
            background-color: #0dcaf0;
            color: #000;
        }

        .status-paid {
            background-color: #198754;
            color: #fff;
        }

        .status-cancelled {
            background-color: #dc3545;
            color: #fff;
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
        <div class="invoice-title">RENTAL INVOICE</div>

        <!-- Info Section -->
        <div class="info-section">
            <div class="bill-to">
                <strong>Bill To:</strong><br>
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

            <div class="invoice-box">
                <table>
                    <tr>
                        <td><strong>Invoice Number:</strong></td>
                        <td>{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td><strong>Date:</strong></td>
                        <td>{{ $invoice->invoice_date->format('M d, Y') }}</td>
                    </tr>
                    @if($invoice->due_date)
                    <tr>
                        <td><strong>Due Date:</strong></td>
                        <td>{{ $invoice->due_date->format('M d, Y') }}</td>
                    </tr>
                    @endif
                    @if($invoice->contract)
                    <tr>
                        <td><strong>Contract:</strong></td>
                        <td>{{ $invoice->contract->contract_number }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            <span class="status-badge status-{{ $invoice->status }}">
                                {{ strtoupper($invoice->status) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th>Type</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ $item->description }}
                        @if($item->notes)
                            <br><small><i>{{ $item->notes }}</i></small>
                        @endif
                    </td>
                    <td>
                        @php
                            $typeLabel = match($item->item_type) {
                                'equipment' => 'Equipment',
                                'damage_charge' => 'Damage',
                                'loss_charge' => 'Loss',
                                'service' => 'Service',
                                default => ucfirst($item->item_type)
                            };
                        @endphp
                        {{ $typeLabel }}
                    </td>
                    <td class="text-center">{{ number_format($item->quantity, 0) }}</td>
                    <td class="text-right">TZS {{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">TZS {{ number_format($item->line_total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                @if($invoice->rental_charges > 0)
                <tr>
                    <td colspan="5" class="text-right"><strong>Rental Charges:</strong></td>
                    <td class="text-right"><strong>TZS {{ number_format($invoice->rental_charges, 2) }}</strong></td>
                </tr>
                @endif
                @if($invoice->damage_charges > 0)
                <tr>
                    <td colspan="5" class="text-right"><strong>Damage Charges:</strong></td>
                    <td class="text-right"><strong>TZS {{ number_format($invoice->damage_charges, 2) }}</strong></td>
                </tr>
                @endif
                @if($invoice->loss_charges > 0)
                <tr>
                    <td colspan="5" class="text-right"><strong>Loss Charges:</strong></td>
                    <td class="text-right"><strong>TZS {{ number_format($invoice->loss_charges, 2) }}</strong></td>
                </tr>
                @endif
                <tr>
                    <td colspan="5" class="text-right"><strong>Subtotal:</strong></td>
                    <td class="text-right"><strong>TZS {{ number_format($invoice->subtotal, 2) }}</strong></td>
                </tr>
                @if($invoice->tax_amount > 0)
                <tr>
                    <td colspan="5" class="text-right"><strong>Tax:</strong></td>
                    <td class="text-right"><strong>TZS {{ number_format($invoice->tax_amount, 2) }}</strong></td>
                </tr>
                @endif
                @if($invoice->deposit_applied > 0)
                <tr>
                    <td colspan="5" class="text-right"><strong>Deposit Applied:</strong></td>
                    <td class="text-right"><strong class="text-success">-TZS {{ number_format($invoice->deposit_applied, 2) }}</strong></td>
                </tr>
                @endif
                <tr style="background-color: #f8f9fa; font-weight: bold;">
                    <td colspan="5" class="text-right"><strong>Total Amount:</strong></td>
                    <td class="text-right"><strong>TZS {{ number_format($invoice->total_amount, 2) }}</strong></td>
                </tr>
                @php
                    $balanceDue = $invoice->total_amount - ($invoice->deposit_applied ?? 0);
                @endphp
                @if($balanceDue > 0)
                <tr>
                    <td colspan="5" class="text-right"><strong>Balance Due:</strong></td>
                    <td class="text-right"><strong class="text-warning">TZS {{ number_format($balanceDue, 2) }}</strong></td>
                </tr>
                @endif
            </tfoot>
        </table>

        @if($invoice->notes)
        <div style="margin-top: 15px; font-size: 10px;">
            <strong>Notes:</strong><br>
            {{ $invoice->notes }}
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <hr>
            <p>This is a computer-generated invoice. No signature is required.</p>
            <p>Generated on: {{ now()->format('M d, Y h:i A') }}</p>
        </div>
    </div>
</body>
</html>
