<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Profit Margin Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .summary {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .company-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .company-logo {
            margin-right: 20px;
        }
        .company-details h1 {
            margin: 0 0 10px 0;
            font-size: 20px;
        }
        .company-details p {
            margin: 2px 0;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <div class="company-logo">
                @if($company->logo)
                    <img src="{{ asset('storage/' . $company->logo) }}" alt="Logo" style="max-height: 60px;">
                @endif
            </div>
            <div class="company-details">
                <h1>{{ $company->name }}</h1>
                <p>{{ $company->address }}</p>
                <p>{{ $company->phone }}</p>
                <p>{{ $company->email }}</p>
            </div>
        </div>
        <h1>Profit Margin Report</h1>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item Code</th>
                <th>Item Name</th>
                <th>Sold Qty</th>
                <th>Sales Revenue</th>
                <th>Cost of Goods</th>
                <th>Gross Margin</th>
                <th>Margin %</th>
            </tr>
        </thead>
        <tbody>
            @foreach($profitData as $item)
            <tr>
                <td>{{ $item['item']->code }}</td>
                <td>{{ $item['item']->name }}</td>
                <td>{{ $item['sold_qty'] }}</td>
                <td>{{ number_format($item['sales_revenue'], 2) }}</td>
                <td>{{ number_format($item['cost_of_goods'], 2) }}</td>
                <td>{{ number_format($item['gross_margin'], 2) }}</td>
                <td>{{ number_format($item['gross_margin_percent'], 2) }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- <div class="summary">
        <h3>Summary</h3>
        <p><strong>Total Items Sold:</strong> {{ $profitData->count() }}</p>
        <p><strong>Total Sales Revenue:</strong> {{ number_format($profitData->sum('sales_revenue'), 2) }}</p>
        <p><strong>Total Cost of Goods:</strong> {{ number_format($profitData->sum('cost_of_goods'), 2) }}</p>
        <p><strong>Total Gross Margin:</strong> {{ number_format($profitData->sum('gross_margin'), 2) }}</p>
    </div> --}}

    <div class="footer">
        <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
        <p>Report by: {{ auth()->user()->name }}</p>   
        <p>Report Period: {{ $dateFrom->format('Y-m-d') }} to {{ $dateTo->format('Y-m-d') }}</p>
    </div>
</body>
</html>
