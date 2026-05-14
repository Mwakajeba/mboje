<!DOCTYPE html>
<html>
<head>
    <title>Counting Sheets - {{ $session->session_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>INVENTORY COUNTING SHEETS</h2>
        <p><strong>Session:</strong> {{ $session->session_number }}</p>
        <p><strong>Location:</strong> {{ $session->location->name ?? 'N/A' }}</p>
        <p><strong>Date:</strong> {{ $session->snapshot_date ? $session->snapshot_date->format('M d, Y') : 'N/A' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item Code</th>
                <th>Item Name</th>
                <th>UOM</th>
                <th>Location/Bin</th>
                @if(!$session->is_blind_count)
                <th>System Qty</th>
                @endif
                <th>Physical Qty</th>
                <th>Remarks</th>
                <th>Condition</th>
                <th>Lot/Batch</th>
                <th>Expiry Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($session->entries as $entry)
            <tr>
                <td>{{ $entry->item->code ?? 'N/A' }}</td>
                <td>{{ $entry->item->name ?? 'N/A' }}</td>
                <td>{{ $entry->item->unit_of_measure ?? 'N/A' }}</td>
                <td>{{ $entry->bin_location ?? '-' }}</td>
                @if(!$session->is_blind_count)
                <td>{{ number_format($entry->system_quantity, 2) }}</td>
                @endif
                <td>{{ $entry->physical_quantity ? number_format($entry->physical_quantity, 2) : '' }}</td>
                <td>{{ $entry->remarks ?? '' }}</td>
                <td>{{ ucfirst($entry->condition ?? 'good') }}</td>
                <td>{{ $entry->lot_number ?? $entry->batch_number ?? '' }}</td>
                <td>{{ $entry->expiry_date ? $entry->expiry_date->format('Y-m-d') : '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px; font-size: 9px;">
        <p><strong>Instructions:</strong></p>
        <ul>
            <li>Count all items at the specified location</li>
            <li>Record physical quantity in the Physical Qty column</li>
            <li>Note any damaged, expired, or missing items in Remarks</li>
            <li>Record lot/batch numbers and expiry dates where applicable</li>
            <li>Return completed sheets to supervisor</li>
        </ul>
    </div>
</body>
</html>

