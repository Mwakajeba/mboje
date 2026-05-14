<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Receipt - {{ $posSale->sale_number }}</title>
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            margin: 0;
            padding: 0;
            background: white;
            color: black;
            font-weight: bold;
        }
        
        .receipt {
            width: 320px;
            max-width: 320px;
            margin: 0 auto;
            padding: 10px;
            background: white;
            overflow: hidden;
            word-wrap: break-word;
        }
        
        /* Ensure text doesn't overflow */
        .item-name, .company-name, .branch-name {
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
        }
        
        /* Header section */
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 12px;
            margin-bottom: 12px;
        }
        
        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .receipt-title {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .branch-name {
            font-size: 11px;
            margin-bottom: 4px;
        }
        
        /* Receipt information */
        .receipt-info {
            margin-bottom: 12px;
            font-size: 10px;
            padding: 0 3px;
        }
        
        .receipt-info div {
            margin-bottom: 3px;
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
        }
        
        .receipt-info .label {
            font-weight: bold;
        }
        
        /* Items section */
        .items {
            margin-bottom: 12px;
            padding: 0 3px;
        }
        
        .item {
            margin-bottom: 8px;
            border-bottom: 1px dotted #ccc;
            padding-bottom: 5px;
            padding-top: 3px;
        }
        
        .item-name {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 3px;
            word-wrap: break-word;
            padding: 0 2px;
        }
        
        .item-details {
            font-size: 9px;
            color: #666;
            margin-bottom: 3px;
            padding: 0 2px;
        }
        
        .item-expiry {
            font-size: 8px;
            color: #888;
            margin-bottom: 2px;
            padding: 0 2px;
        }
        
        .item-expiry.expired {
            color: #dc3545;
            font-weight: bold;
        }
        
        .item-expiry.expiring-soon {
            color: #ffc107;
            font-weight: bold;
        }
        
        .item-total {
            text-align: right;
            font-weight: bold;
            font-size: 10px;
            padding: 0 2px;
        }
        
        /* Totals section */
        .totals {
            border-top: 1px dashed #000;
            padding-top: 12px;
            margin-top: 12px;
            font-size: 10px;
            padding: 0 3px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            padding: 2px 0;
        }
        
        .total-row.final {
            font-weight: bold;
            font-size: 14px;
            border-top: 1px solid #000;
            padding-top: 6px;
            margin-top: 6px;
        }
        
        /* Payment information */
        .payment-info {
            margin-top: 12px;
            border-top: 1px dashed #000;
            padding-top: 12px;
            font-size: 10px;
            padding: 0 3px;
        }
        
        .payment-info div {
            margin-bottom: 3px;
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            margin-top: 18px;
            font-size: 9px;
            color: #666;
            border-top: 1px dashed #000;
            padding-top: 12px;
            padding: 0 3px;
        }
        
        .footer div {
            margin-bottom: 4px;
            padding: 2px 0;
        }
        
        /* Notes section */
        .notes {
            margin-top: 12px;
            border-top: 1px dashed #000;
            padding-top: 12px;
            font-size: 10px;
            padding: 0 3px;
        }
        
        .notes .label {
            font-weight: bold;
            margin-bottom: 4px;
            padding: 2px 0;
        }
        
        .notes div:last-child {
            padding: 2px 0;
        }
        
        /* Print media query – align behavior with Sales Invoice receipt */
        @media print {
            @page { margin: 0; }
            body { margin: 6mm; }
            .no-print { display: none; }
        }
        
        /* Utility classes */
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-bold {
            font-weight: bold;
        }
        
        .text-uppercase {
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <div class="company-name">{{ $posSale->company->name ?? 'COMPANY NAME' }}</div>
            <div class="receipt-title">POS RECEIPT</div>
            <div class="branch-name">{{ $posSale->branch->name ?? 'BRANCH NAME' }}</div>
            <div class= "branch-phone">Phone: {{ $posSale->branch->phone ?? 'N/A' }}</div>
        </div>

        <!-- Receipt Information -->
        <div class="receipt-info">
            <div>
                <span class="label">Receipt No:</span>
                <span>{{ $posSale->sale_number }}</span>
            </div>
            <div>
                <span class="label">Date:</span>
                <span>{{ $posSale->sale_date ? $posSale->sale_date->format('d/m/Y H:i') : 'N/A' }}</span>
            </div>
            <div>
                <span class="label">Customer:</span>
                <span>{{ $posSale->customer_name ?? 'Walk-in Customer' }}</span>
            </div>
            <div>
                <span class="label">Operator:</span>
                <span>{{ $posSale->operator->name ?? 'N/A' }}</span>
            </div>
        </div>

        <!-- Items -->
        <div class="items">
            @foreach($posSale->items as $item)
            <div class="item">
                <div class="item-name">{{ $item->inventoryItem->name ?? $item->item_name ?? 'N/A' }}</div>
                <div class="item-details">
                    {{-- {{ $item->quantity }} x {{ number_format($item->unit_price, 2) }} TZS --}}
                    @if($item->vat_amount > 0)
                        | VAT: {{ number_format($item->vat_amount, 2) }} TZS
                    @endif
                </div>
                @if($item->expiry_date)
                @php
                    $daysUntilExpiry = now()->diffInDays($item->expiry_date, false);
                    $expiryClass = '';
                    if ($daysUntilExpiry < 0) {
                        $expiryClass = 'expired';
                    } elseif ($daysUntilExpiry <= 30) {
                        $expiryClass = 'expiring-soon';
                    }
                @endphp
                <div class="item-expiry {{ $expiryClass }}">
                    <span class="label">Expiry:</span>
                    <span>{{ $item->expiry_date->format('d/m/Y') }}</span>
                    @if($item->batch_number)
                        | Batch: {{ $item->batch_number }}
                    @endif
                    @if($daysUntilExpiry < 0)
                        <span> (EXPIRED)</span>
                    @elseif($daysUntilExpiry <= 30)
                        <span> ({{ $daysUntilExpiry }} days)</span>
                    @endif
                </div>
                @endif
                <div class="item-total">
                    {{ number_format($item->line_total, 2) }} TZS
                </div>
            </div>
            @endforeach
        </div>

        <!-- Totals -->
        <div class="totals">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>{{ number_format($posSale->subtotal, 2) }} TZS</span>
            </div>
            @if($posSale->vat_amount > 0)
            <div class="total-row">
                <span>VAT:</span>
                <span>{{ number_format($posSale->vat_amount, 2) }} TZS</span>
            </div>
            @endif
            @if($posSale->discount_amount > 0)
            <div class="total-row">
                <span>Discount:</span>
                <span>-{{ number_format($posSale->discount_amount, 2) }} TZS</span>
            </div>
            @endif
            @if($posSale->withholding_tax_amount > 0)
            <div class="total-row">
                <span>WHT:</span>
                <span>{{ number_format($posSale->withholding_tax_amount, 2) }} TZS</span>
            </div>
            @endif
            <div class="total-row final">
                <span>TOTAL:</span>
                <span>{{ number_format($posSale->total_amount, 2) }} TZS</span>
            </div>
        </div>

        <!-- Payment Information -->
        <div class="payment-info">
            <div>
                <span class="label">Payment Method:</span>
                <span>{{ ucfirst(str_replace('_', ' ', $posSale->payment_method)) }}</span>
            </div>
            @if($posSale->bank_account)
            <div>
                <span class="label">Bank Account:</span>
                <span>{{ $posSale->bank_account->name ?? 'N/A' }}</span>
            </div>
            @endif
            <div>
                <span class="label">Amount Paid:</span>
                <span class="text-bold">{{ number_format($posSale->total_amount, 2) }} TZS</span>
            </div>
            @if($posSale->currency && $posSale->currency !== 'TZS')
            <div>
                <span class="label">Currency:</span>
                <span>{{ $posSale->currency }}</span>
            </div>
            @endif
        </div>

        <!-- Notes -->
        @if($posSale->notes)
        <div class="notes">
            <div class="label">Notes:</div>
            <div>{{ $posSale->notes }}</div>
        </div>
        @endif

        <!-- Footer -->
        {{-- <div class="footer">
            <div>Thank you for your purchase!</div>
            <div>Please keep this receipt for your records</div>
            <div>Printed: {{ now()->format('d/m/Y H:i:s') }}</div>
        </div> --}}
    </div>

    <script nonce="{{ $cspNonce ?? '' }}">
        // Auto print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 250);
        };
    </script>
</body>
</html>
