<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Order - {{ $order->order_number }}</title>
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

.text-center { text-align: center; }
.text-right { text-align: right; }

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

/* Title */
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
    <div class="text-center">
        @if($order->company && $order->company->logo)
            @php
                $logo = $order->company->logo; // e.g. "uploads/company/logo.png"
                $logoPath = public_path('storage/' . ltrim($logo, '/'));
                $logoBase64 = null;
                if (file_exists($logoPath)) {
                    $imageData = file_get_contents($logoPath);
                    $imageInfo = getimagesize($logoPath);
                    if ($imageInfo !== false) {
                        $mimeType = $imageInfo->mime ?? 'image/png';
                        $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                    }
                }
            @endphp
            @if($logoBase64)
                <div class="logo-section">
                    <img src="{{ $logoBase64 }}" alt="{{ $order->company->name . ' logo' }}" class="company-logo">
                </div>
            @endif
        @endif

        <div class="company-name">{{ $order->company->name }}</div>
        <div class="company-details">
            P.O. Box: {{ $order->company->address }} <br>
            Phone: {{ $order->company->phone }} <br>
            Email: {{ $order->company->email }}
        </div>
    </div>

    <hr>

    <div class="invoice-title">SALES ORDER</div>

    {{-- Bill To + Order Info --}}
    <div class="info-section">
        <div class="bill-to">
            <strong>Order to :</strong><br>
            <strong>{{ $order->customer->name }}</strong><br>
            @if($order->customer->phone)
                {{ $order->customer->phone }}<br>
            @endif
            @if($order->customer->email)
                {{ $order->customer->email }}<br>
            @endif
            @if($order->customer->address)
                {{ $order->customer->address }}<br>
            @endif
            <br>
            <strong>Created By:</strong><br>
            @php
                $creator = $order->createdBy ?? null;
            @endphp
            @if($creator)
                {{ $creator->name }}
            @else
                System
            @endif
        </div>

        <div class="invoice-box" style="text-align: right;">
            <table style="margin-top: 8px;">
                <tr>
                    <td><strong>Order no:</strong></td>
                    <td>{{ $order->order_number }}</td>
                    <td><strong>Order Date:</strong></td>
                    <td>{{ $order->order_date->format('d F Y') }}</td>
                </tr>
                <tr>
                    <td><strong>Expected Delivery:</strong></td>
                    <td>{{ $order->expected_delivery_date ? $order->expected_delivery_date->format('d F Y') : 'N/A' }}</td>
                    <td><strong>Payment Terms:</strong></td>
                    <td>{{ $order->payment_terms ? $order->payment_terms : 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>TIN:</strong></td>
                    <td>{{ $order->customer->tin_number ?? 'N/A' }}</td>
                    <td><strong>VRN:</strong></td>
                    <td>{{ $order->customer->vat_number ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Time:</strong></td>
                    <td colspan="3">{{ $order->created_at->format('H:i:s') }}</td>
                </tr>
            </table>
        </div>
    </div>

    @if($order->notes)
    <div class="notes">
        <strong>Notes:</strong><br>
        {{ $order->notes }}
    </div>
    @endif

    {{-- Items --}}
    <table class="items-table">
        <thead>
            <tr>
                <th>Qty</th>
                <th>Description</th>
                <th>Unit price</th>
                <th>UOM</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                <td>
                    {{ optional($item->inventoryItem)->name ?? $item->item_name }}
                    @if($item->inventoryItem && $item->inventoryItem->description)
                        <br><small>{{ $item->inventoryItem->description }}</small>
                    @endif
                </td>
                <td class="text-right">{{ number_format($item->unit_price,2) }}</td>
                <td class="text-center">{{ $item->inventoryItem->unit_of_measure ?? $item->unit_of_measure }}</td>
                <td class="text-right">{{ number_format($item->total,2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <table class="totals-table">
        <tr>
            <td colspan="3" style="text-align: right;">Sub Total: </td>
            <td>{{ number_format($order->subtotal,2) }}</td>
        </tr>
        @if($order->vat_amount > 0)
        <tr>
            <td colspan="3" style="text-align: right;">Tax {{ number_format($order->vat_rate, 1) }}%: </td>
            <td>{{ number_format($order->vat_amount,2) }}</td>
        </tr>
        @endif
        @if($order->discount_amount > 0)
        <tr>
            <td colspan="3" style="text-align: right;">Total Discount:</td>
            <td>{{ number_format($order->discount_amount ?? 0,2) }}</td>
        </tr>
        @endif
        <tr>
            <td colspan="3" style="text-align: right;"><strong>ORDER TOTAL: </strong></td>
            <td><strong>{{ number_format($order->total_amount,2) }}</strong></td>
        </tr>
    </table>

    {{-- Amount in Words --}}
    @if(method_exists($order, 'getAmountInWords'))
    <div style="margin-top:5px;font-style:italic;">
        <strong>{{ ucwords($order->getAmountInWords()) }}</strong>
    </div>
    @endif

    {{-- Footer --}}
    <hr>
    <div class="footer">
        @if($order->terms_conditions)
            <div><strong>Terms and Conditions:</strong><br>{{ $order->terms_conditions }}</div>
        @endif

        <div class="signature">
            <strong>Customer Signature:</strong> ________________________________
        </div>

        <strong>{{ $order->customer->name }}</strong>

        <div class="text-center" style="font-size:9px;">
            Order No: {{ $order->order_number }} <br>
            Page 1 of 1
        </div>
    </div>

</div>

</body>
</html>

