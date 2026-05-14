<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Method Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 15px; color: #333; }
        .header { margin-bottom: 20px; border-bottom: 3px solid #198754; padding-bottom: 15px; }
        .header-content {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 30px;
        }
        .logo-section {
            flex-shrink: 0;
            display: flex;
            align-items: center;
        }
        .company-logo {
            height: 100px;
            width: auto;
            max-width: 200px;
            object-fit: contain;
        }
        .title-section {
            text-align: center;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .header h1 { color: #198754; margin: 0; font-size: 24px; font-weight: bold; }
        .company-name { color: #333; margin: 5px 0; font-size: 16px; font-weight: 600; }
        .header .subtitle { color: #666; margin: 5px 0 0 0; font-size: 14px; }
        .report-info { margin-bottom: 15px; font-size: 11px; }
        .report-info table { width: 100%; }
        .report-info td { padding: 4px; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #ddd; padding: 6px; }
        table.data-table th { background-color: #198754; color: white; font-weight: bold; text-align: left; }
        table.data-table tfoot { background-color: #f8f9fa; font-weight: bold; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .summary-cards { display: flex; gap: 10px; margin-bottom: 20px; }
        .summary-card { flex: 1; padding: 10px; border: 1px solid #ddd; text-align: center; }
        .summary-card h4 { margin: 5px 0; font-size: 14px; }
        .summary-card p { margin: 0; font-size: 11px; color: #666; }
        .footer { position: fixed; bottom: 0; width: 100%; font-size: 9px; text-align: center; color: #666; border-top: 1px solid #ddd; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo-section">
                @if($company && $company->logo)
                    <img src="{{ public_path('storage/' . $company->logo) }}" alt="{{ $company->name }}" class="company-logo">
                @endif
            </div>
            <div class="title-section">
                <h1>Payment Method Report</h1>
                @if($company)
                    <div class="company-name">{{ $company->name }}</div>
                @endif
                @if($branch)
                    <div class="subtitle">{{ $branch->name }}</div>
                @endif
                <div class="subtitle">Generated on {{ $generatedAt->format('F d, Y \a\t g:i A') }}</div>
            </div>
        </div>
    </div>

    <div class="report-info">
        <table>
            <tr>
                <td><strong>Date Range:</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</td>
                <td class="text-end"><strong>Generated:</strong> {{ $generatedAt->format('d M Y H:i') }}</td>
            </tr>
        </table>
    </div>

    <div class="summary-cards">
        <div class="summary-card">
            <h4>TZS {{ number_format($cashTotal, 2) }}</h4>
            <p>Cash Total</p>
        </div>
        <div class="summary-card">
            <h4>TZS {{ number_format($mobileMoneyTotal, 2) }}</h4>
            <p>Mobile Money Total</p>
        </div>
        <div class="summary-card">
            <h4>TZS {{ number_format($cardTotal, 2) }}</h4>
            <p>Card Total</p>
        </div>
        <div class="summary-card">
            <h4>TZS {{ number_format($bankTransferTotal, 2) }}</h4>
            <p>Bank Transfer Total</p>
        </div>
        <div class="summary-card">
            <h4>TZS {{ number_format($grandTotal, 2) }}</h4>
            <p>Grand Total</p>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th class="text-end">Cash Total</th>
                <th class="text-end">Mobile Money Total</th>
                <th class="text-end">Card Total</th>
                <th class="text-end">Bank Transfer</th>
                <th class="text-end">Grand Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dailyData as $data)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($data['date'])->format('M d, Y') }}</td>
                    <td class="text-end">{{ number_format($data['cash'], 2) }}</td>
                    <td class="text-end">{{ number_format($data['mobile_money'], 2) }}</td>
                    <td class="text-end">{{ number_format($data['card'], 2) }}</td>
                    <td class="text-end">{{ number_format($data['bank_transfer'], 2) }}</td>
                    <td class="text-end"><strong>{{ number_format($data['total'], 2) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No payment data found for the selected date range.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th class="text-end">TOTAL:</th>
                <th class="text-end">{{ number_format($cashTotal, 2) }}</th>
                <th class="text-end">{{ number_format($mobileMoneyTotal, 2) }}</th>
                <th class="text-end">{{ number_format($cardTotal, 2) }}</th>
                <th class="text-end">{{ number_format($bankTransferTotal, 2) }}</th>
                <th class="text-end">{{ number_format($grandTotal, 2) }}</th>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Payment Method Report - Generated on {{ $generatedAt->format('d M Y H:i') }}</p>
    </div>
</body>
</html>
