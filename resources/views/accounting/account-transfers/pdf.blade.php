<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Transfer #{{ $transfer->transfer_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 10px;
            color: #333;
            font-size: 11px;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header-left {
            display: flex;
            align-items: center;
        }
        .logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
            margin-right: 12px;
        }
        .company-info {
            flex: 1;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 2px;
        }
        .document-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .header-right {
            text-align: right;
            font-size: 10px;
            color: #666;
        }
        .transfer-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 10px;
        }
        .info-section {
            background-color: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 8px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 6px;
            padding-bottom: 3px;
            border-bottom: 1px solid #0d6efd;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-value {
            font-size: 11px;
            margin-bottom: 8px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
            padding: 4px 8px;
            background-color: #f8f9fa;
            border-radius: 3px;
            border-left: 3px solid #0d6efd;
        }
        .info-row:last-child {
            margin-bottom: 0;
        }
        .info-row .info-label {
            flex: 0 0 40%;
            margin-bottom: 0;
            font-weight: bold;
            color: #495057;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .info-row .info-value {
            flex: 0 0 58%;
            margin-bottom: 0;
            text-align: right;
            font-size: 10px;
            font-weight: 500;
            color: #212529;
        }
        .amount-section {
            text-align: right;
            background-color: #f8f9fa;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .amount-label {
            font-size: 12px;
            font-weight: bold;
            color: #0d6efd;
        }
        .amount-value {
            font-size: 18px;
            font-weight: bold;
            color: #0d6efd;
        }
        .accounts-section {
            margin-bottom: 15px;
        }
        .accounts-section h3 {
            color: #0d6efd;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
            margin-bottom: 8px;
            font-size: 12px;
        }
        .account-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 10px;
        }
        .account-box.from {
            border-left: 4px solid #dc3545;
        }
        .account-box.to {
            border-left: 4px solid #198754;
        }
        .account-type {
            font-size: 9px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .account-name {
            font-size: 12px;
            font-weight: bold;
            color: #212529;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 9px;
        }
        th, td {
            padding: 6px;
            text-align: left;
            border: 1px solid #dee2e6;
        }
        th {
            background-color: #0d6efd;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-draft {
            background-color: #6c757d;
            color: white;
        }
        .status-submitted {
            background-color: #0dcaf0;
            color: white;
        }
        .status-approved {
            background-color: #198754;
            color: white;
        }
        .status-rejected {
            background-color: #dc3545;
            color: white;
        }
        .status-posted {
            background-color: #0d6efd;
            color: white;
        }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
            font-size: 9px;
            color: #666;
            text-align: center;
        }
        .signature-section {
            margin-top: 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .signature-box {
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
            text-align: center;
        }
        .signature-label {
            font-size: 10px;
            font-weight: bold;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="company-info">
                <div class="company-name">{{ $transfer->company->name ?? 'Company Name' }}</div>
                <div class="document-title">INTER-ACCOUNT TRANSFER</div>
            </div>
        </div>
        <div class="header-right">
            <div>Generated: {{ now()->format('d M Y, h:i A') }}</div>
            <div>Page 1 of 1</div>
        </div>
    </div>

    <div class="transfer-info">
        <div class="info-section">
            <div class="section-title">Transfer Information</div>
            <div class="info-row">
                <span class="info-label">Transfer Number:</span>
                <span class="info-value">{{ $transfer->transfer_number }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Transfer Date:</span>
                <span class="info-value">{{ $transfer->transfer_date->format('d M Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value">
                    <span class="status-badge status-{{ $transfer->status }}">{{ ucfirst($transfer->status) }}</span>
                </span>
            </div>
            @if($transfer->reference_number)
            <div class="info-row">
                <span class="info-label">Reference Number:</span>
                <span class="info-value">{{ $transfer->reference_number }}</span>
            </div>
            @endif
        </div>

        <div class="info-section">
            <div class="section-title">Amount Details</div>
            <div class="info-row">
                <span class="info-label">Transfer Amount:</span>
                <span class="info-value">{{ number_format($transfer->amount, 2) }} {{ $transfer->currency->currency_code ?? 'TZS' }}</span>
            </div>
            @if($transfer->charges > 0)
            <div class="info-row">
                <span class="info-label">Charges:</span>
                <span class="info-value">{{ number_format($transfer->charges, 2) }} {{ $transfer->currency->currency_code ?? 'TZS' }}</span>
            </div>
            @endif
            <div class="info-row">
                <span class="info-label">Total Amount:</span>
                <span class="info-value" style="font-weight: bold; color: #0d6efd;">
                    {{ number_format($transfer->amount + ($transfer->charges ?? 0), 2) }} {{ $transfer->currency->currency_code ?? 'TZS' }}
                </span>
            </div>
            @if($transfer->exchange_rate && $transfer->exchange_rate != 1)
            <div class="info-row">
                <span class="info-label">Exchange Rate:</span>
                <span class="info-value">{{ number_format($transfer->exchange_rate, 6) }}</span>
            </div>
            @endif
        </div>
    </div>

    <div class="accounts-section">
        <h3>Account Details</h3>
        <div class="account-box from">
            <div class="account-type">From Account ({{ ucfirst(str_replace('_', ' ', $transfer->from_account_type)) }})</div>
            <div class="account-name">
                @php
                    $fromAccount = $transfer->fromAccount;
                @endphp
                {{ $fromAccount ? ($fromAccount->name ?? 'N/A') : 'N/A' }}
            </div>
        </div>
        <div class="account-box to">
            <div class="account-type">To Account ({{ ucfirst(str_replace('_', ' ', $transfer->to_account_type)) }})</div>
            <div class="account-name">
                @php
                    $toAccount = $transfer->toAccount;
                @endphp
                {{ $toAccount ? ($toAccount->name ?? 'N/A') : 'N/A' }}
            </div>
        </div>
    </div>

    @if($transfer->description)
    <div class="info-section" style="margin-bottom: 15px;">
        <div class="section-title">Description</div>
        <div style="padding: 8px; font-size: 10px; line-height: 1.5;">
            {{ $transfer->description }}
        </div>
    </div>
    @endif

    @if($transfer->charges > 0 && $transfer->chargesAccount)
    <div class="info-section" style="margin-bottom: 15px;">
        <div class="section-title">Charges Information</div>
        <div class="info-row">
            <span class="info-label">Charges Amount:</span>
            <span class="info-value">{{ number_format($transfer->charges, 2) }} {{ $transfer->currency->currency_code ?? 'TZS' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Charges Account:</span>
            <span class="info-value">{{ $transfer->chargesAccount->account_code }} - {{ $transfer->chargesAccount->account_name }}</span>
        </div>
    </div>
    @endif

    @if($transfer->journal_id && $transfer->journal && $transfer->journal->items)
    <div class="accounts-section">
        <h3>General Ledger Entries</h3>
        <table>
            <thead>
                <tr>
                    <th>Account Code</th>
                    <th>Account Name</th>
                    <th class="text-center">Nature</th>
                    <th class="text-right">Amount</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transfer->journal->items as $item)
                <tr>
                    <td>{{ $item->chartAccount->account_code ?? 'N/A' }}</td>
                    <td>{{ $item->chartAccount->account_name ?? 'N/A' }}</td>
                    <td class="text-center">
                        @if($item->nature === 'debit')
                            <span style="color: #198754; font-weight: bold;">Debit</span>
                        @else
                            <span style="color: #dc3545; font-weight: bold;">Credit</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <strong>{{ number_format($item->amount, 2) }} {{ $transfer->currency->currency_code ?? 'TZS' }}</strong>
                    </td>
                    <td>{{ $item->description ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-right">Total:</th>
                    <th class="text-right">{{ number_format($transfer->journal->items->sum('amount'), 2) }} {{ $transfer->currency->currency_code ?? 'TZS' }}</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    <div class="info-section" style="margin-bottom: 15px;">
        <div class="section-title">Additional Information</div>
        <div class="info-row">
            <span class="info-label">Created By:</span>
            <span class="info-value">{{ $transfer->createdBy->name ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Created At:</span>
            <span class="info-value">{{ $transfer->created_at->format('d M Y, h:i A') }}</span>
        </div>
        @if($transfer->approved_by)
        <div class="info-row">
            <span class="info-label">Approved By:</span>
            <span class="info-value">{{ $transfer->approvedBy->name ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Approved At:</span>
            <span class="info-value">{{ $transfer->approved_at ? $transfer->approved_at->format('d M Y, h:i A') : 'N/A' }}</span>
        </div>
        @endif
        @if($transfer->approval_notes)
        <div class="info-row">
            <span class="info-label">Approval Notes:</span>
            <span class="info-value">{{ $transfer->approval_notes }}</span>
        </div>
        @endif
        @if($transfer->rejection_reason)
        <div class="info-row">
            <span class="info-label">Rejection Reason:</span>
            <span class="info-value" style="color: #dc3545;">{{ $transfer->rejection_reason }}</span>
        </div>
        @endif
        @if($transfer->branch)
        <div class="info-row">
            <span class="info-label">Branch:</span>
            <span class="info-value">{{ $transfer->branch->name ?? 'N/A' }}</span>
        </div>
        @endif
    </div>

    <div class="footer">
        <div>This is a computer-generated document. No signature is required.</div>
        <div style="margin-top: 5px;">Generated on {{ now()->format('d M Y, h:i A') }} by {{ Auth::user()->name ?? 'System' }}</div>
    </div>
</body>
</html>

