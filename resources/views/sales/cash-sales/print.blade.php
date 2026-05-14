<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Sale Receipt - {{ $cashSale->sale_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: #fff;
            padding: 20px;
        }
        
        .receipt-container {
            max-width: 400px;
            margin: 0 auto;
            border: 2px solid #333;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .company-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .company-tagline {
            font-size: 10px;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        
        .document-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .sale-number {
            font-size: 14px;
            font-weight: bold;
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .content {
            padding: 20px;
        }
        
        .info-section {
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            border-bottom: 1px dotted #ddd;
            padding-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            color: #666;
            min-width: 100px;
        }
        
        .info-value {
            text-align: right;
            font-weight: 500;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .items-table th {
            background: #f8f9fa;
            padding: 8px 5px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            border-bottom: 2px solid #ddd;
        }
        
        .items-table td {
            padding: 8px 5px;
            border-bottom: 1px solid #eee;
            font-size: 11px;
        }
        
        .item-name {
            font-weight: bold;
        }
        
        .item-code {
            font-size: 9px;
            color: #666;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .amounts-section {
            margin-top: 20px;
            border-top: 2px solid #333;
            padding-top: 15px;
        }
        
        .amount-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 12px;
        }
        
        .total-row {
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 10px;
            font-weight: bold;
            font-size: 14px;
        }
        
        .footer {
            background: #f8f9fa;
            padding: 15px 20px;
            text-align: center;
            border-top: 1px solid #ddd;
        }
        
        .footer p {
            margin-bottom: 5px;
            font-size: 10px;
            color: #666;
        }
        
        .thank-you {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .payment-status {
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .qr-section {
            text-align: center;
            margin: 15px 0;
        }
        
        .qr-placeholder {
            width: 80px;
            height: 80px;
            border: 2px dashed #ccc;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            color: #999;
        }
        
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            .receipt-container {
                border: none;
                max-width: none;
            }
        }
        
        .no-print {
            display: none;
        }
        
        @media screen {
            .print-button {
                position: fixed;
                top: 20px;
                right: 20px;
                background: #007bff;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 5px;
                cursor: pointer;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">Print Receipt</button>
    
    <div class="receipt-container">
        <!-- Header -->
        <div class="header">
            <div class="company-name">{{ $cashSale->company->name ?? 'COMPANY NAME' }}</div>
            <div class="company-tagline">Professional Business Solutions</div>
            <div class="document-title">CASH SALE RECEIPT</div>
            <div class="sale-number">{{ $cashSale->sale_number }}</div>
        </div>
        
        <!-- Content -->
        <div class="content">
            <!-- Customer Information -->
            <div class="info-section">
                <div class="info-row">
                    <span class="info-label">Customer:</span>
                    <span class="info-value">{{ $cashSale->customer->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <span class="info-value">{{ $cashSale->customer->phone ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date:</span>
                    <span class="info-value">{{ $cashSale->sale_date->format('M d, Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Time:</span>
                    <span class="info-value">{{ $cashSale->created_at->format('H:i') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment:</span>
                    <span class="info-value">{{ ucfirst($cashSale->payment_method) }}</span>
                </div>
                @if($cashSale->bankAccount)
                <div class="info-row">
                    <span class="info-label">Bank:</span>
                    <span class="info-value">{{ $cashSale->bankAccount->name }}</span>
                </div>
                @endif
                @if($cashSale->cashDeposit)
                <div class="info-row">
                    <span class="info-label">Account:</span>
                    <span class="info-value">{{ $cashSale->cashDeposit->type->name ?? 'Customer Balance' }}</span>
                </div>
                @endif
            </div>
            
            <!-- Items Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Price</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cashSale->items as $item)
                    <tr>
                        <td>
                            <div class="item-name">{{ $item->item_name }}</div>
                            <div class="item-code">{{ $item->item_code }}</div>
                            @if($item->notes)
                            <div class="item-code">{{ $item->notes }}</div>
                            @endif
                        </td>
                        <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                        <td class="text-right">TZS {{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">TZS {{ number_format($item->line_total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            <!-- Amounts Summary -->
            <div class="amounts-section">
                <div class="amount-row">
                    <span>Subtotal:</span>
                    <span>TZS {{ number_format($cashSale->subtotal, 2) }}</span>
                </div>
                @if($cashSale->vat_amount > 0)
                <div class="amount-row">
                    <span>VAT ({{ $cashSale->vat_rate }}%):</span>
                    <span>TZS {{ number_format($cashSale->vat_amount, 2) }}</span>
                </div>
                @endif
                @if($cashSale->discount_amount > 0)
                <div class="amount-row">
                    <span>Discount:</span>
                    <span>-TZS {{ number_format($cashSale->discount_amount, 2) }}</span>
                </div>
                @endif
                @if($cashSale->withholding_tax_amount > 0)
                <div class="amount-row">
                    <span>Withholding Tax:</span>
                    <span>TZS {{ number_format($cashSale->withholding_tax_amount, 2) }}</span>
                </div>
                @endif
                <div class="amount-row total-row">
                    <span>TOTAL:</span>
                    <span>TZS {{ number_format($cashSale->total_amount, 2) }}</span>
                </div>
                <div class="amount-row">
                    <span>Paid Amount:</span>
                    <span>TZS {{ number_format($cashSale->paid_amount, 2) }}</span>
                </div>
            </div>
            
            <!-- QR Code Section -->
            <div class="qr-section">
                <div class="qr-placeholder">
                    QR Code<br>Placeholder
                </div>
                <div class="payment-status">PAID</div>
            </div>
            
            <!-- Notes -->
            @if($cashSale->notes)
            <div class="info-section">
                <div class="info-row">
                    <span class="info-label">Notes:</span>
                    <span class="info-value">{{ $cashSale->notes }}</span>
                </div>
            </div>
            @endif
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="thank-you">Thank you for your business!</div>
            <p>Generated on {{ now()->format('M d, Y H:i:s') }}</p>
            <p>Branch: {{ $cashSale->branch->name ?? 'N/A' }}</p>
            <p>Cashier: {{ $cashSale->createdBy->name ?? 'N/A' }}</p>
            <p>For any queries, please contact us</p>
        </div>
    </div>

    <script nonce="{{ $cspNonce ?? '' }}">
        // Auto print when page loads
        window.onload = function() {
            // Small delay to ensure everything is loaded
            setTimeout(function() {
                window.print();
            }, 500);
        }
        
        // Print button functionality
        function printReceipt() {
            window.print();
        }
    </script>
</body>
</html> 