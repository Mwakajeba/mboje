<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Purchase Quotation #{{ $quotation->reference }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #28a745;
            padding-bottom: 20px;
        }
        .quotation-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .quotation-details h3 {
            margin-top: 0;
            color: #28a745;
        }
        .supplier-info {
            margin-bottom: 20px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .items-table th {
            background-color: #f2f2f2;
        }
        .totals {
            text-align: right;
            margin-top: 20px;
        }
        .total-row {
            font-weight: bold;
            font-size: 1.1em;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 0.9em;
        }
        .rfq-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            text-align: center;
        }
        .company-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>PURCHASE QUOTATION</h1>
        <h2>#{{ $quotation->reference }}</h2>
        @if($quotation->is_request_for_quotation)
            <h3 style="color: #ffc107;">REQUEST FOR QUOTATION (RFQ)</h3>
        @endif
    </div>

    <div class="company-info">
        <h3>From:</h3>
        <p><strong>{{ $quotation->branch->company->name ?? config('app.name') }}</strong></p>
        <p>{{ $quotation->branch->name ?? '' }}</p>
        <p>{{ $quotation->branch->address ?? '' }}</p>
        <p>Phone: {{ $quotation->branch->phone ?? '' }}</p>
        <p>Email: {{ $quotation->branch->email ?? '' }}</p>
    </div>

    <div class="quotation-details">
        <h3>Quotation Details</h3>
        <p><strong>Date:</strong> {{ $quotation->start_date->format('M d, Y') }}</p>
        <p><strong>Due Date:</strong> {{ $quotation->due_date->format('M d, Y') }}</p>
        <p><strong>Status:</strong> {{ ucfirst($quotation->status) }}</p>
        @if($quotation->is_request_for_quotation)
            <p><strong>Type:</strong> Request for Quotation (RFQ)</p>
        @else
            <p><strong>Type:</strong> Purchase Quotation</p>
        @endif
    </div>

    <div class="supplier-info">
        <h3>To:</h3>
        <p><strong>{{ $quotation->supplier->name }}</strong></p>
        @if($quotation->supplier->email)
            <p>Email: {{ $quotation->supplier->email }}</p>
        @endif
        @if($quotation->supplier->phone)
            <p>Phone: {{ $quotation->supplier->phone }}</p>
        @endif
        @if($quotation->supplier->address)
            <p>Address: {{ $quotation->supplier->address }}</p>
        @endif
        @if($quotation->supplier->region)
            <p>Region: {{ $quotation->supplier->region }}</p>
        @endif
    </div>

    @if($quotation->is_request_for_quotation)
        <div class="rfq-notice">
            <h3>📋 Request for Quotation</h3>
            <p>This is a request for quotation. Please provide your best pricing for the items listed below.</p>
        </div>
    @endif

    <table class="items-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Quantity</th>
                @if(!$quotation->is_request_for_quotation)
                    <th>Unit Price</th>
                    <th>VAT</th>
                    <th>Total</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->quotationItems as $item)
            <tr>
                <td>
                    <strong>{{ $item->item->name }}</strong><br>
                    <small class="text-muted">{{ $item->item->code }}</small>
                </td>
                <td>{{ number_format($item->quantity, 2) }} {{ $item->item->unit_of_measure ?? 'units' }}</td>
                @if(!$quotation->is_request_for_quotation)
                    <td>TZS {{ number_format($item->unit_price, 2) }}</td>
                    <td>
                        @if($item->vat_type === 'no_vat')
                            No VAT
                        @elseif($item->vat_type === 'inclusive')
                            Inclusive ({{ $item->vat_rate }}%)
                        @else
                            Exclusive ({{ $item->vat_rate }}%)
                        @endif
                        <br><small>TZS {{ number_format($item->tax_amount, 2) }}</small>
                    </td>
                    <td>TZS {{ number_format($item->total_amount, 2) }}</td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>

    @if(!$quotation->is_request_for_quotation)
        <div class="totals">
            <p><strong>Total Amount:</strong> TZS {{ number_format($quotation->total_amount, 2) }}</p>
        </div>
    @endif

    @if($quotation->notes)
        <div style="margin-top: 20px;">
            <h3>Notes:</h3>
            <p>{{ $quotation->notes }}</p>
        </div>
    @endif

    @if($quotation->terms_conditions)
        <div style="margin-top: 20px;">
            <h3>Terms & Conditions:</h3>
            <p>{{ $quotation->terms_conditions }}</p>
        </div>
    @endif

    <div class="footer">
        <p>Thank you for your consideration!</p>
        <p>{{ config('app.name') }}</p>
        <p style="margin-top: 20px;">
            <a href="{{ route('purchases.quotations.show', $quotation->id) }}" 
               style="background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
                📄 View Quotation
            </a>
        </p>
    </div>
</body>
</html> 