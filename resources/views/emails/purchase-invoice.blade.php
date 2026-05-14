<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Invoice - {{ $invoice->invoice_number }}</title>
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
        .supplier-info {
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
            <a href="{{ route('purchases.purchase-invoices.export-pdf', $invoice->encoded_id) }}?download=1" 
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
            </div>
        </div>

        <div class="invoice-title">Purchase Invoice</div>

        <div class="invoice-details">
            <div class="supplier-info">
                <div style="font-weight: bold; margin-bottom: 2px;">Supplier:</div>
                <div>{{ $invoice->supplier->name ?? 'Supplier Name' }}</div>
                @if($invoice->supplier && $invoice->supplier->address)
                <div style="margin-top: 3px;">{{ $invoice->supplier->address }}</div>
                @endif
            </div>
            <div class="invoice-info">
                <table style="width: 100%; border-collapse: collapse; font-size: 8px;">
                    <tr>
                        <td style="padding: 2px; border: 1px solid #000; font-weight: bold; width: 20%;">Invoice No:</td>
                        <td style="padding: 2px; border: 1px solid #000; width: 30%;">{{ $invoice->invoice_number }}</td>
                        <td style="padding: 2px; border: 1px solid #000; font-weight: bold; width: 20%;">Date:</td>
                        <td style="padding: 2px; border: 1px solid #000; width: 30%;">{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                    </tr>
                    @if($invoice->due_date)
                    <tr>
                        <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">Due Date:</td>
                        <td style="padding: 2px; border: 1px solid #000;">{{ $invoice->due_date->format('d/m/Y') }}</td>
                        <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">Currency:</td>
                        <td style="padding: 2px; border: 1px solid #000;">{{ $invoice->currency ?? 'TZS' }}</td>
                    </tr>
                    @else
                    <tr>
                        <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">Currency:</td>
                        <td style="padding: 2px; border: 1px solid #000;" colspan="3">{{ $invoice->currency ?? 'TZS' }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">TIN:</td>
                        <td style="padding: 2px; border: 1px solid #000;">{{ $invoice->company->tin ?? 'N/A' }}</td>
                        <td style="padding: 2px; border: 1px solid #000; font-weight: bold;">VRN:</td>
                        <td style="padding: 2px; border: 1px solid #000;">{{ $invoice->company->vrn ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 8%;">Qty</th>
                    <th style="width: 50%;">Description</th>
                    <th style="width: 15%;">Unit Price</th>
                    <th style="width: 12%;">VAT</th>
                    <th style="width: 15%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                    <td>
                        @if($item->inventoryItem)
                            {{ $item->inventoryItem->name }}
                        @elseif($item->asset)
                            {{ $item->asset_name ?? $item->asset->name ?? 'Asset' }}
                        @else
                            {{ $item->description ?? 'Item' }}
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($item->unit_cost, 2) }}</td>
                    <td class="text-right">{{ number_format($item->vat_amount, 2) }}</td>
                    <td class="text-right">{{ number_format($item->line_total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            <div class="summary-row">
                <span>Subtotal:</span>
                <span>{{ number_format($invoice->subtotal, 2) }}</span>
            </div>
            @if($invoice->discount_amount > 0)
            <div class="summary-row">
                <span>Discount:</span>
                <span>{{ number_format($invoice->discount_amount, 2) }}</span>
            </div>
            @endif
            <div class="summary-row">
                <span>VAT:</span>
                <span>{{ number_format($invoice->vat_amount, 2) }}</span>
            </div>
            <div class="summary-row total">
                <span>Total Amount:</span>
                <span>{{ number_format($invoice->total_amount, 2) }}</span>
            </div>
        </div>

        @if($invoice->notes)
        <div style="margin-top: 10px; font-size: 8px;">
            <strong>Notes:</strong>
            <div style="margin-top: 3px;">{{ $invoice->notes }}</div>
        </div>
        @endif

        <div style="text-align: center; margin: 20px 0; padding: 15px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">
            <a href="{{ route('purchases.purchase-invoices.export-pdf', $invoice->encoded_id) }}?download=1" 
               style="display: inline-block; background-color: #007bff; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 12px;">
                📄 Download PDF Invoice
            </a>
        </div>

        <div style="margin-top: 10px; font-size: 8px; text-align: center;">
            <div>Invoice No: {{ $invoice->invoice_number }}</div>
            <div>Page 1 of 1</div>
        </div>
    </div>
</body>
</html>

