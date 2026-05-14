<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Voucher - {{ $paymentVoucher->reference }}</title>
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

        /* Voucher title */
        .voucher-title {
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

        .pay-to {
            width: 48%;
            font-size: 10px;
        }

        .pay-to strong {
            color: #1e40af;
        }

        .voucher-box {
            width: 48%;
            text-align: right;
        }

        .voucher-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-left: auto;
        }

        .voucher-box td {
            border: 1px solid #cbd5e1;
            padding: 4px;
        }

        .voucher-box td:nth-child(even) {
            text-align: right;
        }

        .voucher-box strong {
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

    </style>

</head>
<body>
    <div class="container">

        {{-- Header --}}
        <div class="text-left">
            @php
            $company = $paymentVoucher->user->company ?? null;
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


        <div class="voucher-title">PAYMENT VOUCHER</div>
        <hr>
        {{-- Pay To + Voucher Info --}}
        <div class="info-section">
            <div class="pay-to" style="float: left; width: 48%;">
                <strong>Pay to :</strong><br>
                @if($paymentVoucher->payee_type === 'customer' && $paymentVoucher->customer)
                <strong>{{ $paymentVoucher->customer->name }}</strong><br>
                @if($paymentVoucher->customer->phone)
                {{ $paymentVoucher->customer->phone }}<br>
                @endif
                @if($paymentVoucher->customer->email)
                {{ $paymentVoucher->customer->email }}<br>
                @endif
                @if($paymentVoucher->customer->address)
                {{ $paymentVoucher->customer->address }}<br>
                @endif
                @elseif($paymentVoucher->payee_type === 'supplier' && $paymentVoucher->supplier)
                <strong>{{ $paymentVoucher->supplier->name }}</strong><br>
                @if($paymentVoucher->supplier->phone)
                {{ $paymentVoucher->supplier->phone }}<br>
                @endif
                @if($paymentVoucher->supplier->email)
                {{ $paymentVoucher->supplier->email }}<br>
                @endif
                @if($paymentVoucher->supplier->address)
                {{ $paymentVoucher->supplier->address }}<br>
                @endif
                @elseif($paymentVoucher->payee_type === 'employee' && $paymentVoucher->employee)
                <strong>{{ $paymentVoucher->employee->full_name }}</strong><br>
                @if($paymentVoucher->employee->employee_number)
                Employee No: {{ $paymentVoucher->employee->employee_number }}<br>
                @endif
                @elseif($paymentVoucher->payee_type === 'other')
                <strong>{{ $paymentVoucher->payee_name ?? 'N/A' }}</strong><br>
                @else
                <strong>N/A</strong><br>
                @endif
                <br>
                <strong>Created By:</strong><br>
                @php
                $creator = $paymentVoucher->user ?? null;
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

            <div class="voucher-box" style="text-align: right; float: left; width: 48%;">
                <table style="margin-top: 8px;">
                    <tr>
                        <td><strong>Voucher no:</strong></td>
                        <td>{{ $paymentVoucher->reference }}</td>
                        <td><strong>Date :</strong></td>
                        <td>{{ $paymentVoucher->date ? $paymentVoucher->date->format('d F Y') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Currency:</strong></td>
                        <td>{{ $paymentVoucher->currency ?? 'TZS' }}</td>
                        <td><strong>Ex Rate:</strong></td>
                        <td>{{ number_format($paymentVoucher->exchange_rate ?? 1, 2) }}</td>
                    </tr>
                    @if($paymentVoucher->branch)
                    <tr>
                        <td><strong>Branch:</strong></td>
                        <td colspan="3">{{ $paymentVoucher->branch->name ?? 'N/A' }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Time:</strong></td>
                        <td colspan="3">{{ $paymentVoucher->created_at->format('H:i:s') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        @if($paymentVoucher->description)
        <div class="notes" style="clear: both;">
            <strong>Description:</strong><br>
            {{ $paymentVoucher->description }}
        </div>
        @endif

        {{-- Items --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Account</th>
                    <th>Account Code</th>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($paymentVoucher->paymentItems as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->chartAccount->account_name ?? 'N/A' }}</td>
                    <td class="text-center">{{ $item->chartAccount->account_code ?? 'N/A' }}</td>
                    <td>{{ $item->description ?: '-' }}</td>
                    <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center">No payment items found</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Totals --}}
        @php
        $colspan = 4;
        @endphp
        <table class="totals-table">
            <tr>
                <td colspan="{{ $colspan }}" style="text-align: right;"><strong>GRAND TOTAL: </strong></td>
                <td><strong>{{ number_format($paymentVoucher->amount, 2) }}</strong></td>
            </tr>
        </table>

        @if(method_exists($paymentVoucher, 'getAmountInWords'))
        <div style="margin-top:5px;font-style:italic;">
            <strong>{{ ucwords($paymentVoucher->getAmountInWords()) }}</strong>
        </div>
        @endif

        @if($paymentVoucher->notes)
        <div style="margin-top:10px; font-size: 10px;">
            <strong>Notes:</strong><br>
            {{ $paymentVoucher->notes }}
        </div>
        @endif

        {{-- Footer --}}
        <hr>
        <div class="footer">
            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Prepared By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        {{ $paymentVoucher->user->name ?? 'N/A' }}
                    </div>
                </div>
                
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Approved By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        @if($paymentVoucher->approved && $paymentVoucher->approvedBy)
                        {{ $paymentVoucher->approvedBy->name }}
                        @elseif($paymentVoucher->approved)
                        {{ $paymentVoucher->user->name ?? 'N/A' }}
                        @else
                        <span style="color: #999;">Pending Approval</span>
                        @endif
                    </div>
                </div>
                
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Received By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        @if($paymentVoucher->payee_type === 'customer' && $paymentVoucher->customer)
                        {{ $paymentVoucher->customer->name }}
                        @elseif($paymentVoucher->payee_type === 'supplier' && $paymentVoucher->supplier)
                        {{ $paymentVoucher->supplier->name }}
                        @elseif($paymentVoucher->payee_type === 'employee' && $paymentVoucher->employee)
                        {{ $paymentVoucher->employee->full_name }}
                        @elseif($paymentVoucher->payee_type === 'other')
                        {{ $paymentVoucher->payee_name ?? 'N/A' }}
                        @else
                        <span style="color: #999;">N/A</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="text-center" style="font-size:9px; margin-top: 20px;">
                Payment Voucher No: {{ $paymentVoucher->reference }} <br>
                Page 1 of 1
            </div>
        </div>

    </div>

</body>
</html>
