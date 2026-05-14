<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cash Purchase - {{ $purchase->id }}</title>
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

        /* Purchase title */
        .purchase-title {
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

        .purchase-from {
            width: 48%;
            font-size: 10px;
        }

        .purchase-from strong {
            color: #1e40af;
        }

        .purchase-box {
            width: 48%;
            text-align: right;
        }

        .purchase-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-left: auto;
        }

        .purchase-box td {
            border: 1px solid #cbd5e1;
            padding: 4px;
        }

        .purchase-box td:nth-child(even) {
            text-align: right;
        }

        .purchase-box strong {
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

        /* Payment method badge */
        .payment-method-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .payment-method-cash {
            background-color: #10b981;
            color: #fff;
        }

        .payment-method-bank {
            background-color: #3b82f6;
            color: #fff;
        }

    </style>

</head>
<body>
    <div class="container">

        {{-- Header --}}
        <div class="text-left">
            @php
            $company = $company ?? ($purchase->company ?? ($purchase->branch->company ?? null));
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

        @if(isset($bankAccounts) && $bankAccounts && $bankAccounts->count() > 0)
        <div class="payment-method-bar" style="text-align: center;">
            <strong>PAYMENT METHOD :</strong>
        </div>
        <div class="payment-details">
            @foreach($bankAccounts as $account)
            <strong>{{ strtoupper($account->name ?? $account->bank_name ?? 'BANK') }}:</strong> {{ $account->account_number ?? 'N/A' }} &nbsp;&nbsp;
            @endforeach
        </div>
        @endif

        <div class="purchase-title">CASH PURCHASE</div>
        <hr>
        {{-- Purchase From + Purchase Info --}}
        <div class="info-section">
            <div class="purchase-from" style="float: left; width: 48%;">
                <strong>Purchase from :</strong><br>
                <strong>{{ $purchase->supplier->name ?? 'N/A' }}</strong><br>
                @if($purchase->supplier && $purchase->supplier->phone)
                {{ $purchase->supplier->phone }}<br>
                @endif
                @if($purchase->supplier && $purchase->supplier->email)
                {{ $purchase->supplier->email }}<br>
                @endif
                @if($purchase->supplier && $purchase->supplier->address)
                {{ $purchase->supplier->address }}<br>
                @endif
                <br>
                <strong>Created By:</strong><br>
                @php
                $creator = $purchase->createdBy ?? null;
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

            <div class="purchase-box" style="text-align: right; float: left; width: 48%;">
                <table style="margin-top: 8px;">
                    <tr>
                        <td><strong>Purchase no:</strong></td>
                        <td>CP-{{ $purchase->purchase_date->format('Ymd') }}-{{ str_pad($purchase->id, 4, '0', STR_PAD_LEFT) }}</td>
                        <td><strong>Date :</strong></td>
                        <td>{{ $purchase->purchase_date->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Payment Method:</strong></td>
                        <td>
                            <span class="payment-method-badge payment-method-{{ $purchase->payment_method ?? 'cash' }}">
                                {{ strtoupper($purchase->payment_method ?? 'CASH') }}
                            </span>
                        </td>
                        <td><strong>Currency:</strong></td>
                        <td>{{ $purchase->currency ?? 'TZS' }}</td>
                    </tr>
                    @if($purchase->bankAccount)
                    <tr>
                        <td><strong>Bank Account:</strong></td>
                        <td colspan="3">{{ $purchase->bankAccount->name }} ({{ $purchase->bankAccount->account_number }})</td>
                    </tr>
                    @endif
                    @if($purchase->supplier && $purchase->supplier->tin_number)
                    <tr>
                        <td><strong>TIN:</strong></td>
                        <td>{{ $purchase->supplier->tin_number }}</td>
                        <td><strong>VRN:</strong></td>
                        <td>{{ $purchase->supplier->vat_number ?? 'N/A' }}</td>
                    </tr>
                    @endif
                    @if($purchase->branch)
                    <tr>
                        <td><strong>Branch:</strong></td>
                        <td colspan="3">{{ $purchase->branch->name ?? 'N/A' }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Time:</strong></td>
                        <td colspan="3">{{ $purchase->created_at->format('H:i:s') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div style="clear: both; margin-bottom: 10px;"></div>

        @if($purchase->notes)
        <div class="notes" style="clear: both; margin-bottom: 10px;">
            <strong>Notes:</strong><br>
            {{ $purchase->notes }}
        </div>
        @endif

        {{-- Items --}}
        @php
        $hasExpiryDates = $purchase->items->contains(function($item) {
        return isset($item->expiry_date) && $item->expiry_date !== null;
        });
        $hasBatchNumbers = $purchase->items->contains(function($item) {
        return isset($item->batch_number) && !empty($item->batch_number);
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
                    @if($hasBatchNumbers)
                    <th>Batch</th>
                    @endif
                    <th>Unit price</th>
                    <th>UOM</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchase->items as $item)
                <tr>
                    <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                    <td>
                        <strong>{{ optional($item->inventoryItem)->name ?? $item->description ?? 'N/A' }}</strong>
                        @if($item->inventoryItem && $item->inventoryItem->code)
                        <br><small style="color: #666;">Code: {{ $item->inventoryItem->code }}</small>
                        @endif
                    </td>
                    @if($hasExpiryDates)
                    <td class="text-center">
                        {{ $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date)->format('m/Y') : '' }}
                    </td>
                    @endif
                    @if($hasBatchNumbers)
                    <td class="text-center">
                        {{ $item->batch_number ?? '' }}
                    </td>
                    @endif
                    <td class="text-right">{{ number_format($item->unit_cost, 2) }}</td>
                    <td class="text-center">{{ optional($item->inventoryItem)->unit_of_measure ?? $item->unit_of_measure ?? '-' }}</td>
                    <td class="text-right">{{ number_format($item->line_total, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ ($hasExpiryDates ? 1 : 0) + ($hasBatchNumbers ? 1 : 0) + 5 }}" class="text-center" style="padding: 20px;">
                        <div style="color: #666;">No items found</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Totals --}}
        @php
        $vatRate = 0;
        if ($purchase->vat_amount > 0 && $purchase->subtotal > 0) {
        $vatRate = ($purchase->vat_amount / $purchase->subtotal) * 100;
        }
        // Count columns: Qty, Name, (Exp date if exists), (Batch if exists), Unit price, UOM, Amount
        $colspan = 4 + ($hasExpiryDates ? 1 : 0) + ($hasBatchNumbers ? 1 : 0);
        @endphp
        <table class="totals-table">
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Sub Total: </td>
                <td>{{ number_format($purchase->subtotal, 2) }}</td>
            </tr>
            @if($purchase->vat_amount > 0)
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Tax {{ number_format($vatRate, 1) }}%: </td>
                <td>{{ number_format($purchase->vat_amount, 2) }}</td>
            </tr>
            @endif
            @if($purchase->discount_amount > 0)
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Total Discount:</td>
                <td>{{ number_format($purchase->discount_amount ?? 0, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;"><strong>GRAND TOTAL: </strong></td>
                <td><strong>{{ number_format($purchase->total_amount, 2) }}</strong></td>
            </tr>
        </table>

        @if(method_exists($purchase, 'getAmountInWords'))
        <div style="margin-top:5px;font-style:italic;">
            <strong>{{ ucwords($purchase->getAmountInWords()) }}</strong>
        </div>
        @endif

        {{-- Footer --}}
        <hr>
        <div class="footer">
            <div style="margin-bottom: 10px;">Thank you for your supply!</div>

            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Prepared By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        {{ $purchase->createdBy->name ?? 'N/A' }}
                    </div>
                </div>
                
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Approved By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        <span style="color: #999;">Pending Approval</span>
                    </div>
                </div>
                
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Received By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        {{ $purchase->supplier->name ?? 'Supplier' }}
                    </div>
                </div>
            </div>

            <ol style="margin-top: 15px; padding-left: 20px;">
                <li>Please verify all items and quantities received before processing payment</li>
                <li>Payment has been made via {{ strtoupper($purchase->payment_method ?? 'cash') }} as indicated above</li>
                <li>Please retain this document for your accounting records</li>
                <li>Any discrepancies should be reported immediately to our accounts department</li>
            </ol>

            <strong>{{ $purchase->supplier->name ?? 'Supplier' }}</strong>

            <div class="text-center" style="font-size:9px; margin-top: 20px;">
                Cash Purchase No: CP-{{ $purchase->purchase_date->format('Ymd') }}-{{ str_pad($purchase->id, 4, '0', STR_PAD_LEFT) }} <br>
                Page 1 of 1
            </div>
        </div>

    </div>

</body>
</html>
