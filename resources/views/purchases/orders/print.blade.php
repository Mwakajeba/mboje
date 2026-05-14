<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Order - {{ $order->order_number }}</title>
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

        /* Order title */
        .order-title {
            font-weight: bold;
            text-align: center;
            font-size: 18px;
            margin: 10px 0;
            color: #1e40af;
        }

        /* Quotation info */
        .quotation-info {
            background-color: #fef3c7;
            border: 2px solid #fbbf24;
            padding: 8px;
            margin: 10px 0;
            text-align: center;
            border-radius: 3px;
            font-size: 10px;
        }

        .quotation-info strong {
            color: #92400e;
        }

        /* Info section */
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .order-from {
            width: 48%;
            font-size: 10px;
        }

        .order-from strong {
            color: #1e40af;
        }

        .order-box {
            width: 48%;
            text-align: right;
        }

        .order-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-left: auto;
        }

        .order-box td {
            border: 1px solid #cbd5e1;
            padding: 4px;
        }

        .order-box td:nth-child(even) {
            text-align: right;
        }

        .order-box strong {
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

        /* Status badge */
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-draft {
            background-color: #9ca3af;
            color: #fff;
        }

        .status-pending_approval {
            background-color: #fbbf24;
            color: #000;
        }

        .status-approved {
            background-color: #10b981;
            color: #fff;
        }

        .status-in_production {
            background-color: #3b82f6;
            color: #fff;
        }

        .status-ready_for_delivery {
            background-color: #8b5cf6;
            color: #fff;
        }

        .status-delivered {
            background-color: #10b981;
            color: #fff;
        }

        .status-cancelled {
            background-color: #ef4444;
            color: #fff;
        }

        .status-on_hold {
            background-color: #f59e0b;
            color: #000;
        }

        /* Expected delivery box */
        .expected-delivery {
            background-color: #dbeafe;
            border: 2px solid #1e40af;
            padding: 10px;
            margin: 10px 0;
            text-align: center;
            border-radius: 3px;
        }

        .expected-delivery strong {
            color: #1e40af;
            font-size: 12px;
        }

    </style>

</head>
<body>
    <div class="container">

        {{-- Header --}}
        <div class="text-left">
            @php
            $company = $company ?? ($order->company ?? ($order->branch->company ?? null));
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



        <div class="order-title">PURCHASE ORDER</div>
        <hr>

        @if($order->quotation)
        <div class="quotation-info">
            <strong>Converted from Quotation:</strong> {{ $order->quotation->reference ?? 'N/A' }}
        </div>
        @endif

        {{-- Order From + Order Info --}}
        <div class="info-section">
            <div class="order-from" style="float: left; width: 48%;">
                <strong>Order from :</strong><br>
                <strong>{{ $order->supplier->name ?? 'N/A' }}</strong><br>
                @if($order->supplier && $order->supplier->phone)
                {{ $order->supplier->phone }}<br>
                @endif
                @if($order->supplier && $order->supplier->email)
                {{ $order->supplier->email }}<br>
                @endif
                @if($order->supplier && $order->supplier->address)
                {{ $order->supplier->address }}<br>
                @endif
                @if($order->supplier && $order->supplier->region)
                Region: {{ $order->supplier->region }}<br>
                @endif
                <br>
                <strong>Created By:</strong><br>
                @php
                $creator = $order->createdBy ?? null;
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

            <div class="order-box" style="text-align: right; float: left; width: 48%;">
                <table style="margin-top: 8px;">
                    <tr>
                        <td><strong>Order no:</strong></td>
                        <td>{{ $order->order_number }}</td>
                        <td><strong>Date :</strong></td>
                        <td>{{ $order->order_date->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Expected Delivery:</strong></td>
                        <td colspan="3">{{ $order->expected_delivery_date->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            <span class="status-badge status-{{ str_replace(' ', '_', strtolower($order->status ?? 'draft')) }}">
                                {{ strtoupper(str_replace('_', ' ', $order->status ?? 'DRAFT')) }}
                            </span>
                        </td>
                        <td><strong>Currency:</strong></td>
                        <td>TZS</td>
                    </tr>
                    <tr>
                        <td><strong>Payment Terms:</strong></td>
                        <td>{{ ucfirst(str_replace('_', ' ', $order->payment_terms ?? 'immediate')) }}</td>
                        <td><strong>Payment Days:</strong></td>
                        <td>{{ $order->payment_days ?? '0' }}</td>
                    </tr>
                    @if($order->supplier && $order->supplier->tin_number)
                    <tr>
                        <td><strong>TIN:</strong></td>
                        <td>{{ $order->supplier->tin_number }}</td>
                        <td><strong>VRN:</strong></td>
                        <td>{{ $order->supplier->vat_number ?? 'N/A' }}</td>
                    </tr>
                    @endif
                    @if($order->branch)
                    <tr>
                        <td><strong>Branch:</strong></td>
                        <td colspan="3">{{ $order->branch->name ?? 'N/A' }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Time:</strong></td>
                        <td colspan="3">{{ $order->created_at->format('H:i:s') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div style="clear: both; margin-bottom: 10px;"></div>

        @if($order->notes)
        <div class="notes" style="clear: both; margin-bottom: 10px;">
            <strong>Notes:</strong><br>
            {{ $order->notes }}
        </div>
        @endif

        {{-- Items --}}
        @php
        $hasExpiryDates = $order->items->contains(function($item) {
        return isset($item->expiry_date) && $item->expiry_date !== null;
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
                    @if(!$order->hide_cost_price)
                    <th>Unit price</th>
                    @endif
                    <th>UOM</th>
                    @if(!$order->hide_cost_price)
                    <th>VAT Rate</th>
                    <th>Tax</th>
                    <th>Amount</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($order->items as $item)
                <tr>
                    <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                    <td>
                        <strong>{{ $item->item->name ?? $item->description ?? 'N/A' }}</strong>
                        @if($item->item && $item->item->code)
                        <br><small style="color: #666;">Code: {{ $item->item->code }}</small>
                        @endif
                    </td>
                    @if($hasExpiryDates)
                    <td class="text-center">
                        {{ isset($item->expiry_date) && $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date)->format('m/Y') : '' }}
                    </td>
                    @endif
                    @if(!$order->hide_cost_price)
                    <td class="text-right">{{ number_format($item->cost_price, 2) }}</td>
                    @endif
                    <td class="text-center">{{ $item->item->unit_of_measure ?? '-' }}</td>
                    @if(!$order->hide_cost_price)
                    <td class="text-center">
                        @if($item->vat_type === 'no_vat')
                        0.00%
                        @else
                        {{ number_format($item->vat_rate, 2) }}%
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($item->vat_amount, 2) }}</td>
                    <td class="text-right">{{ number_format($item->total_amount, 2) }}</td>
                    @endif
                </tr>
                @empty
                <tr>
                    @php
                        $cols = 3;
                        if($hasExpiryDates) $cols++;
                        if(!$order->hide_cost_price) $cols += 4;
                    @endphp
                    <td colspan="{{ $cols }}" class="text-center" style="padding: 20px;">
                        <div style="color: #666;">No items found</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Totals --}}
        @if(!$order->hide_cost_price)
        @php
        $vatRate = 0;
        if ($order->vat_amount > 0 && $order->subtotal > 0) {
        $vatRate = ($order->vat_amount / ($order->subtotal - $order->vat_amount)) * 100;
        }
        // Count columns: Qty, Name, (Exp date if exists), Unit price, UOM, VAT Rate, Tax, Amount
        $colspan = $hasExpiryDates ? 7 : 6;
        @endphp
        <table class="totals-table">
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Sub Total: </td>
                <td>{{ number_format($order->subtotal, 2) }}</td>
            </tr>
            @if($order->vat_amount > 0)
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Tax {{ number_format($vatRate, 1) }}%: </td>
                <td>{{ number_format($order->vat_amount, 2) }}</td>
            </tr>
            @endif
            @if($order->tax_amount > 0 && $order->tax_amount != $order->vat_amount)
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Additional Tax:</td>
                <td>{{ number_format($order->tax_amount, 2) }}</td>
            </tr>
            @endif
            @if($order->discount_amount > 0)
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Total Discount:</td>
                <td>{{ number_format($order->discount_amount ?? 0, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;"><strong>GRAND TOTAL: </strong></td>
                <td><strong>{{ number_format($order->total_amount, 2) }}</strong></td>
            </tr>
        </table>

        @if(method_exists($order, 'getAmountInWords'))
        <div style="margin-top:5px;font-style:italic;">
            <strong>{{ ucwords($order->getAmountInWords()) }}</strong>
        </div>
        @endif
        @endif

        @if($order->expected_delivery_date)
        <div class="expected-delivery">
            <strong>Expected Delivery Date: {{ $order->expected_delivery_date->format('d F Y') }}</strong>
        </div>
        @endif

        {{-- Footer --}}
        <hr>
        <div class="footer">
            <div style="margin-bottom: 10px;">Thank you for your business!</div>
            @if($order->terms_conditions)
            <div style="margin-bottom: 10px;"><strong>Terms and Conditions:</strong><br>{{ $order->terms_conditions }}</div>
            @endif

            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Prepared By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        {{ $order->createdBy->name ?? 'N/A' }}
                    </div>
                </div>
                
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Approved By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        @if($order->approvedBy)
                        {{ $order->approvedBy->name }}
                        @else
                        <span style="color: #999;">Pending Approval</span>
                        @endif
                    </div>
                </div>
                
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Received By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        {{ $order->supplier->name ?? 'Supplier' }}
                    </div>
                </div>
            </div>

            <ol style="margin-top: 15px; padding-left: 20px;">
                <li>This purchase order is subject to the terms and conditions specified above</li>
                <li>Please confirm receipt and expected delivery date</li>
                <li>Any changes to this order must be approved in writing</li>
                <li>Goods must be delivered in accordance with the specifications provided</li>
            </ol>

            <strong>{{ $order->supplier->name ?? 'Supplier' }}</strong>

            <div class="text-center" style="font-size:9px; margin-top: 20px;">
                Purchase Order No: {{ $order->order_number }} <br>
                Page 1 of 1
            </div>
        </div>

    </div>

</body>
</html>
