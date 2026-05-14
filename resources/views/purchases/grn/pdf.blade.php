<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Goods Receipt Note - {{ $grn->grn_number ?? ('GRN-' . str_pad($grn->id, 6, '0', STR_PAD_LEFT)) }}</title>
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

        /* GRN title */
        .grn-title {
            font-weight: bold;
            text-align: center;
            font-size: 18px;
            margin: 10px 0;
            color: #1e40af;
        }

        /* Purchase Order info */
        .po-info {
            background-color: #fef3c7;
            border: 2px solid #fbbf24;
            padding: 8px;
            margin: 10px 0;
            text-align: center;
            border-radius: 3px;
            font-size: 10px;
        }

        .po-info strong {
            color: #92400e;
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

        .grn-box {
            width: 48%;
            text-align: right;
        }

        .grn-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-left: auto;
        }

        .grn-box td {
            border: 1px solid #cbd5e1;
            padding: 4px;
        }

        .grn-box td:nth-child(even) {
            text-align: right;
        }

        .grn-box strong {
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

        .status-received {
            background-color: #3b82f6;
            color: #fff;
        }

        .status-quality_checked {
            background-color: #fbbf24;
            color: #000;
        }

        .status-approved {
            background-color: #10b981;
            color: #fff;
        }

        .status-rejected {
            background-color: #ef4444;
            color: #fff;
        }

        /* Quality check status */
        .quality-status {
            margin-top: 10px;
            padding: 8px;
            background-color: #f0f9ff;
            border: 1px solid #3b82f6;
            border-radius: 3px;
            font-size: 10px;
        }

        .quality-status strong {
            color: #1e40af;
        }

    </style>

</head>
<body>
    <div class="container">

        {{-- Header --}}
        <div class="text-left">
            @php
            $company = $company ?? ($grn->company ?? ($grn->branch->company ?? null));
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

        <div class="grn-title">GOODS RECEIPT NOTE</div>
        <hr>

        @if($grn->purchaseOrder)
        <div class="po-info">
            <strong>Purchase Order:</strong> {{ $grn->purchaseOrder->order_number ?? ('PO-' . str_pad($grn->purchaseOrder->id, 6, '0', STR_PAD_LEFT)) }}
            @if($grn->purchaseOrder->order_date)
            | Date: {{ $grn->purchaseOrder->order_date->format('d F Y') }}
            @endif
        </div>
        @else
        <div class="po-info">
            <strong>Standalone GRN</strong> (not created from a Purchase Order)
        </div>
        @endif

        {{-- Received From + GRN Info --}}
        <div class="info-section">
            <div class="received-from" style="float: left; width: 48%;">
                <strong>Received from :</strong><br>
                @if($grn->purchaseOrder && $grn->purchaseOrder->supplier)
                <strong>{{ $grn->purchaseOrder->supplier->name ?? 'N/A' }}</strong><br>
                @if($grn->purchaseOrder->supplier->phone)
                {{ $grn->purchaseOrder->supplier->phone }}<br>
                @endif
                @if($grn->purchaseOrder->supplier->email)
                {{ $grn->purchaseOrder->supplier->email }}<br>
                @endif
                @if($grn->purchaseOrder->supplier->address)
                {{ $grn->purchaseOrder->supplier->address }}<br>
                @endif
                @else
                <strong>N/A</strong><br>
                @endif
                <br>
                <strong>Received By:</strong><br>
                @php
                $receiver = $grn->receivedByUser ?? null;
                $receiverRole = $receiver && method_exists($receiver, 'roles') ? $receiver->roles->first() : null;
                @endphp
                @if($receiver)
                {{ $receiver->name }}
                @if($receiverRole)
                ({{ $receiverRole->name }})
                @endif
                @else
                N/A
                @endif
            </div>

            <div class="grn-box" style="text-align: right; float: left; width: 48%;">
                <table style="margin-top: 8px;">
                    <tr>
                        <td><strong>GRN no:</strong></td>
                        <td>{{ $grn->grn_number ?? ('GRN-' . str_pad($grn->id, 6, '0', STR_PAD_LEFT)) }}</td>
                        <td><strong>Date :</strong></td>
                        <td>{{ $grn->receipt_date->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            <span class="status-badge status-{{ str_replace(' ', '_', strtolower($grn->status ?? 'draft')) }}">
                                {{ strtoupper(str_replace('_', ' ', $grn->status ?? 'DRAFT')) }}
                            </span>
                        </td>
                        <td><strong>Currency:</strong></td>
                        <td>TZS</td>
                    </tr>
                    @if($grn->branch)
                    <tr>
                        <td><strong>Branch:</strong></td>
                        <td colspan="3">{{ $grn->branch->name ?? 'N/A' }}</td>
                    </tr>
                    @endif
                    @if($grn->warehouse)
                    <tr>
                        <td><strong>Warehouse:</strong></td>
                        <td colspan="3">{{ $grn->warehouse->name ?? 'N/A' }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Time:</strong></td>
                        <td colspan="3">{{ $grn->created_at->format('H:i:s') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div style="clear: both; margin-bottom: 10px;"></div>

        @if($grn->notes)
        <div class="notes" style="clear: both; margin-bottom: 10px;">
            <strong>Notes:</strong><br>
            {{ $grn->notes }}
        </div>
        @endif

        @if($grn->quality_check_status && $grn->quality_check_status !== 'pending')
        <div class="quality-status">
            <strong>Quality Check Status:</strong> {{ strtoupper(str_replace('_', ' ', $grn->quality_check_status)) }}
            @if($grn->qualityCheckedByUser)
            | <strong>Checked By:</strong> {{ $grn->qualityCheckedByUser->name }}
            @endif
            @if($grn->quality_check_date)
            | <strong>Date:</strong> {{ $grn->quality_check_date->format('d F Y H:i') }}
            @endif
        </div>
        @endif

        {{-- Items --}}
        @php
        $hasExpiryDates = $grn->items->contains(function($item) {
        return isset($item->expiry_date) && $item->expiry_date !== null;
        });
        @endphp
        <table class="items-table">
            <thead>
                <tr>
                    <th>Qty Ordered</th>
                    <th>Qty Received</th>
                    <th>Name</th>
                    @if($hasExpiryDates)
                    <th>Exp date</th>
                    @endif
                    <th>Unit Cost</th>
                    <th>UOM</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($grn->items as $item)
                <tr>
                    <td class="text-center">{{ number_format($item->quantity_ordered, 2) }}</td>
                    <td class="text-center">{{ number_format($item->quantity_received, 2) }}</td>
                    <td>
                        <strong>{{ $item->inventoryItem->name ?? 'N/A' }}</strong>
                        @if($item->inventoryItem && $item->inventoryItem->code)
                        <br><small style="color: #666;">Code: {{ $item->inventoryItem->code }}</small>
                        @endif
                    </td>
                    @if($hasExpiryDates)
                    <td class="text-center">
                        {{ isset($item->expiry_date) && $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date)->format('m/Y') : '' }}
                    </td>
                    @endif
                    <td class="text-right">{{ number_format($item->unit_cost, 2) }}</td>
                    <td class="text-center">{{ $item->inventoryItem->unit_of_measure ?? '-' }}</td>
                    <td class="text-right">{{ number_format($item->total_cost, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $hasExpiryDates ? 7 : 6 }}" class="text-center" style="padding: 20px;">
                        <div style="color: #666;">No items found</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Totals --}}
        @php
        // Count columns: Qty Ordered, Qty Received, Name, (Exp date if exists), Unit Cost, UOM, Amount
        $colspan = $hasExpiryDates ? 6 : 5;
        @endphp
        <table class="totals-table">
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Total Quantity Received: </td>
                <td>{{ number_format($grn->total_quantity ?? $grn->items->sum('quantity_received'), 2) }}</td>
            </tr>
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;"><strong>GRAND TOTAL: </strong></td>
                <td><strong>{{ number_format($grn->total_amount, 2) }}</strong></td>
            </tr>
        </table>

        @if(method_exists($grn, 'getAmountInWords'))
        <div style="margin-top:5px;font-style:italic;">
            <strong>{{ ucwords($grn->getAmountInWords()) }}</strong>
        </div>
        @endif

        {{-- Footer --}}
        <hr>
        <div class="footer">
            <div style="margin-bottom: 10px;">Thank you for your business!</div>

            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Received By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        {{ $grn->receivedByUser->name ?? 'N/A' }}
                    </div>
                </div>
                
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Quality Checked By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        @if($grn->qualityCheckedByUser)
                        {{ $grn->qualityCheckedByUser->name }}
                        @else
                        <span style="color: #999;">Pending</span>
                        @endif
                    </div>
                </div>
                
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Approved By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        <span style="color: #999;">{{ ucfirst($grn->status) }}</span>
                    </div>
                </div>
            </div>

            <ol style="margin-top: 15px; padding-left: 20px;">
                <li>This GRN confirms receipt of goods as specified above</li>
                <li>All items have been inspected and verified</li>
                <li>Please retain this document for your records</li>
                @if($grn->purchaseOrder)
                <li>This GRN is linked to Purchase Order: {{ $grn->purchaseOrder->order_number ?? 'N/A' }}</li>
                @endif
            </ol>

            <div class="text-center" style="font-size:9px; margin-top: 20px;">
                GRN No: {{ $grn->grn_number ?? ('GRN-' . str_pad($grn->id, 6, '0', STR_PAD_LEFT)) }} <br>
                Page 1 of 1
            </div>
        </div>

    </div>

</body>
</html>
