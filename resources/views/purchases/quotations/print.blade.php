<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Quotation - {{ $quotation->reference }}</title>
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

        /* Quotation title */
        .quotation-title {
            font-weight: bold;
            text-align: center;
            font-size: 18px;
            margin: 10px 0;
            color: #1e40af;
        }

        /* RFQ Notice */
        .rfq-notice {
            background-color: #fef3c7;
            border: 2px solid #fbbf24;
            padding: 10px;
            margin: 10px 0;
            text-align: center;
            border-radius: 3px;
        }

        .rfq-notice strong {
            color: #92400e;
            font-size: 12px;
        }

        /* Info section */
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .quote-from {
            width: 48%;
            font-size: 10px;
        }

        .quote-from strong {
            color: #1e40af;
        }

        .quotation-box {
            width: 48%;
            text-align: right;
        }

        .quotation-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-left: auto;
        }

        .quotation-box td {
            border: 1px solid #cbd5e1;
            padding: 4px;
        }

        .quotation-box td:nth-child(even) {
            text-align: right;
        }

        .quotation-box strong {
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

        .status-sent {
            background-color: #3b82f6;
            color: #fff;
        }

        .status-approved {
            background-color: #10b981;
            color: #fff;
        }

        .status-rejected {
            background-color: #ef4444;
            color: #fff;
        }

        .status-expired {
            background-color: #fbbf24;
            color: #000;
        }

        /* Valid Until box */
        .valid-until {
            background-color: #dbeafe;
            border: 2px solid #1e40af;
            padding: 10px;
            margin: 10px 0;
            text-align: center;
            border-radius: 3px;
        }

        .valid-until strong {
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
            $company = $company ?? ($quotation->branch->company ?? null);
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

     

        <div class="quotation-title">
            @if($quotation->is_request_for_quotation)
            REQUEST FOR QUOTATION (RFQ)
            @else
            PURCHASE QUOTATION
            @endif
        </div>
        <hr>

        @if($quotation->is_request_for_quotation)
        <div class="rfq-notice">
            <strong>📋 REQUEST FOR QUOTATION</strong><br>
            This is a request for quotation. Please provide your pricing for the items listed below.
        </div>
        @endif

        {{-- Quote From + Quotation Info --}}
        <div class="info-section">
            <div class="quote-from" style="float: left; width: 48%;">
                <strong>Quote from :</strong><br>
                <strong>{{ $quotation->supplier->name ?? 'N/A' }}</strong><br>
                @if($quotation->supplier && $quotation->supplier->phone)
                {{ $quotation->supplier->phone }}<br>
                @endif
                @if($quotation->supplier && $quotation->supplier->email)
                {{ $quotation->supplier->email }}<br>
                @endif
                @if($quotation->supplier && $quotation->supplier->address)
                {{ $quotation->supplier->address }}<br>
                @endif
                @if($quotation->supplier && $quotation->supplier->region)
                Region: {{ $quotation->supplier->region }}<br>
                @endif
                <br>
                <strong>Created By:</strong><br>
                @php
                $creator = $quotation->user ?? null;
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

            <div class="quotation-box" style="text-align: right; float: left; width: 48%;">
                <table style="margin-top: 8px;">
                    <tr>
                        <td><strong>Quotation no:</strong></td>
                        <td>{{ $quotation->reference }}</td>
                        <td><strong>Date :</strong></td>
                        <td>{{ $quotation->start_date->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Due Date:</strong></td>
                        <td colspan="3">{{ $quotation->due_date->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            <span class="status-badge status-{{ $quotation->status ?? 'draft' }}">
                                {{ strtoupper($quotation->status ?? 'DRAFT') }}
                            </span>
                        </td>
                        <td><strong>Currency:</strong></td>
                        <td>TZS</td>
                    </tr>
                    @if($quotation->supplier && $quotation->supplier->tin_number)
                    <tr>
                        <td><strong>TIN:</strong></td>
                        <td>{{ $quotation->supplier->tin_number }}</td>
                        <td><strong>VRN:</strong></td>
                        <td>{{ $quotation->supplier->vat_number ?? 'N/A' }}</td>
                    </tr>
                    @endif
                    @if($quotation->branch)
                    <tr>
                        <td><strong>Branch:</strong></td>
                        <td colspan="3">{{ $quotation->branch->name ?? 'N/A' }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Time:</strong></td>
                        <td colspan="3">{{ $quotation->created_at->format('H:i:s') }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div style="clear: both; margin-bottom: 10px;"></div>

        @if($quotation->notes)
        <div class="notes" style="clear: both; margin-bottom: 10px;">
            <strong>Notes:</strong><br>
            {{ $quotation->notes }}
        </div>
        @endif

        {{-- Items --}}
        @php
        $hasExpiryDates = $quotation->quotationItems->contains(function($item) {
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
                    @if(!$quotation->is_request_for_quotation)
                    <th>Unit price</th>
                    <th>UOM</th>
                    <th>Tax</th>
                    <th>Amount</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($quotation->quotationItems as $item)
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
                    @if(!$quotation->is_request_for_quotation)
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-center">{{ $item->unit_of_measure ?? ($item->item->unit_of_measure ?? '-') }}</td>
                    <td class="text-right">{{ number_format($item->tax_amount, 2) }}</td>
                    <td class="text-right">{{ number_format($item->total_amount, 2) }}</td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $quotation->is_request_for_quotation ? ($hasExpiryDates ? 3 : 2) : ($hasExpiryDates ? 7 : 6) }}" class="text-center" style="padding: 20px;">
                        <div style="color: #666;">No items found</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if(!$quotation->is_request_for_quotation)
        {{-- Totals --}}
        @php
        $subtotal = $quotation->subtotal ?? $quotation->quotationItems->sum('total_amount');
        $vatAmount = $quotation->vat_amount ?? $quotation->quotationItems->sum('tax_amount');
        $vatRate = 0;
        if ($vatAmount > 0 && $subtotal > 0) {
        $vatRate = ($vatAmount / ($subtotal - $vatAmount)) * 100;
        }
        // Count columns: Qty, Name, (Exp date if exists), Unit price, UOM, Tax, Amount
        $colspan = $hasExpiryDates ? 6 : 5;
        @endphp
        <table class="totals-table">
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Sub Total: </td>
                <td>{{ number_format($subtotal, 2) }}</td>
            </tr>
            @if($vatAmount > 0)
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Tax {{ number_format($vatRate, 1) }}%: </td>
                <td>{{ number_format($vatAmount, 2) }}</td>
            </tr>
            @endif
            @if($quotation->discount_amount > 0)
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;">Total Discount:</td>
                <td>{{ number_format($quotation->discount_amount ?? 0, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;"><strong>GRAND TOTAL: </strong></td>
                <td><strong>{{ number_format($quotation->total_amount, 2) }}</strong></td>
            </tr>
        </table>

        @if(method_exists($quotation, 'getAmountInWords'))
        <div style="margin-top:5px;font-style:italic;">
            <strong>{{ ucwords($quotation->getAmountInWords()) }}</strong>
        </div>
        @endif
        @endif

        @if($quotation->due_date)
        <div class="valid-until">
            <strong>Valid Until: {{ $quotation->due_date->format('d F Y') }}</strong>
        </div>
        @endif

        {{-- Footer --}}
        <hr>
        <div class="footer">
            <div style="margin-bottom: 10px;">Thank you for your business!</div>
            @if($quotation->terms_conditions)
            <div style="margin-bottom: 10px;"><strong>Terms and Conditions:</strong><br>{{ $quotation->terms_conditions }}</div>
            @endif

            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Prepared By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        {{ $quotation->user->name ?? 'N/A' }}
                    </div>
                </div>
                
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Approved By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        <span style="color: #999;">{{ ucfirst($quotation->status) }}</span>
                    </div>
                </div>
                
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Received By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        {{ $quotation->supplier->name ?? 'Supplier' }}
                    </div>
                </div>
            </div>

            <ol style="margin-top: 15px; padding-left: 20px;">
                <li>This quotation is valid until the due date specified above</li>
                <li>Please review all terms and conditions before acceptance</li>
                <li>Prices are subject to change after the validity period</li>
                @if($quotation->is_request_for_quotation)
                <li>Please provide your pricing for the items listed above</li>
                @endif
            </ol>

            <strong>{{ $quotation->supplier->name ?? 'Supplier' }}</strong>

            <div class="text-center" style="font-size:9px; margin-top: 20px;">
                Quotation No: {{ $quotation->reference }} <br>
                Page 1 of 1
            </div>
        </div>

    </div>

</body>
</html>
