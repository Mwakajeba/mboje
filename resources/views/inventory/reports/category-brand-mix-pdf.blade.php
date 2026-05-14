<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Category Brand Mix Report</title>
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
    </style>
</head>
<body>
    <div class="header">
        <h1>Category Brand Mix Report</h1>
        <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th>Items Count</th>
                <th>Total Quantity</th>
                <th>Total Value</th>
                <th>Qty %</th>
                <th>Value %</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categoryMix as $categoryName => $category)
            <tr>
                <td>{{ $categoryName }}</td>
                <td>{{ $category['items_count'] }}</td>
                <td>{{ $category['total_qty'] }}</td>
                <td>{{ number_format($category['total_value'], 2) }}</td>
                <td>{{ number_format($category['qty_percentage'], 2) }}%</td>
                <td>{{ number_format($category['value_percentage'], 2) }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <h3>Summary</h3>
        <p><strong>Grand Total Quantity:</strong> {{ $grandTotalQty }}</p>
        <p><strong>Grand Total Value:</strong> {{ number_format($grandTotalValue, 2) }}</p>
    </div>

    <div class="footer">
        <p>Total Categories: {{ $categoryMix->count() }}</p>
    </div>
</body>
</html>
