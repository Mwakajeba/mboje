<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Hotel Expense - {{ $expense->reference_number }}</title>
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

        /* Expense title */
        .expense-title {
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

        .expense-box {
            width: 48%;
            text-align: right;
        }

        .expense-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-left: auto;
        }

        .expense-box td {
            border: 1px solid #cbd5e1;
            padding: 4px;
        }

        .expense-box td:nth-child(even) {
            text-align: right;
        }

        .expense-box strong {
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
            $company = $expense->user->company ?? null;
            @endphp
            @if($company && $company->logo)
            @php
            $logo = $company->logo;
            $logoPath = public_path('storage/' . ltrim($logo, '/'));

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

        <div class="expense-title">HOTEL EXPENSE</div>
        <hr>
        {{-- Expense Info --}}
        <div class="info-section">
            <div style="float: left; width: 48%; font-size: 10px;">
                <strong>Expense Type:</strong> Hotel Expense<br>
                <strong>Branch:</strong> {{ $expense->branch->name ?? 'N/A' }}<br>
                <strong>Bank Account:</strong> {{ $expense->bankAccount->name ?? 'N/A' }}<br>
                @if($expense->bankAccount && $expense->bankAccount->account_number)
                <strong>Account Number:</strong> {{ $expense->bankAccount->account_number }}<br>
                @endif
                <br>
                <strong>Created By:</strong><br>
                @php
                $creator = $expense->user ?? null;
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

            <div class="expense-box" style="text-align: right; float: left; width: 48%;">
                <table style="margin-top: 8px;">
                    <tr>
                        <td><strong>Expense no:</strong></td>
                        <td>{{ $expense->reference_number }}</td>
                        <td><strong>Date :</strong></td>
                        <td>{{ $expense->date ? $expense->date->format('d F Y') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td colspan="3">
                            @if($expense->approved)
                            <span style="color: green;">Approved</span>
                            @else
                            <span style="color: orange;">Pending</span>
                            @endif
                        </td>
                    </tr>
                    @if($expense->branch)
                    <tr>
                        <td><strong>Branch:</strong></td>
                        <td colspan="3">{{ $expense->branch->name ?? 'N/A' }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Time:</strong></td>
                        <td colspan="3">{{ $expense->created_at->format('H:i:s') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        @if($expense->description)
        <div class="notes" style="clear: both; margin-bottom: 10px;">
            <strong>Description:</strong><br>
            {{ $expense->description }}
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
                @forelse($expense->paymentItems as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->chartAccount->account_name ?? 'N/A' }}</td>
                    <td class="text-center">{{ $item->chartAccount->account_code ?? 'N/A' }}</td>
                    <td>{{ $item->description ?: '-' }}</td>
                    <td class="text-right">TSh {{ number_format($item->amount, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center">No expense items found</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Totals --}}
        <table class="totals-table">
            <tr>
                <td colspan="4" style="text-align: right;"><strong>GRAND TOTAL: </strong></td>
                <td><strong>TSh {{ number_format($expense->amount, 2) }}</strong></td>
            </tr>
        </table>

        {{-- Footer --}}
        <hr>
        <div class="footer">
            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Prepared By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        {{ $expense->user->name ?? 'N/A' }}
                    </div>
                </div>
                
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Approved By</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        @if($expense->approved && $expense->approvedBy)
                        {{ $expense->approvedBy->name }}
                        @elseif($expense->approved)
                        {{ $expense->user->name ?? 'N/A' }}
                        @else
                        <span style="color: #999;">Pending Approval</span>
                        @endif
                    </div>
                </div>
                
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div style="margin-top: 5px; font-size: 11px;"><strong>Branch Manager</strong></div>
                    <div style="margin-top: 2px; font-size: 10px;">
                        <span style="color: #999;">Signature</span>
                    </div>
                </div>
            </div>

            <div class="text-center" style="font-size:9px; margin-top: 20px;">
                Hotel Expense No: {{ $expense->reference_number }} <br>
                Page 1 of 1
            </div>
        </div>

    </div>

</body>
</html>
