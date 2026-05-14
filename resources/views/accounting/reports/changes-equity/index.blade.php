@extends('layouts.main')

@section('title', 'Changes in Equity Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Changes in Equity Report', 'url' => '#', 'icon' => 'bx bx-trending-up']
        ]" />
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-trending-up me-2"></i>Statement of Changes in Equity (IAS 1)</h5>
                                <small class="text-muted">IFRS-compliant columnar format showing movements in equity components</small>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-success" onclick="generateReport()">
                                    <i class="bx bx-refresh me-1"></i> Generate Report
                                </button>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-danger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bx bx-download me-1"></i> Export
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="exportReport('pdf')">
                                            <i class="bx bx-file-pdf me-2"></i> Export PDF
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="exportReport('excel')">
                                            <i class="bx bx-file me-2"></i> Export Excel
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filters Section -->
                        <form id="changesEquityForm" method="GET" action="{{ route('accounting.reports.changes-equity') }}">
                            <div class="row">
                                <!-- From Date -->
                                <div class="col-md-6 col-lg-3 mb-3">
                                    <label for="from_date" class="form-label">From Date</label>
                                    <input type="date" class="form-control" id="from_date" name="from_date" 
                                           value="{{ $fromDate }}" required>
                                </div>

                                <!-- To Date -->
                                <div class="col-md-6 col-lg-3 mb-3">
                                    <label for="to_date" class="form-label">To Date</label>
                                    <input type="date" class="form-control" id="to_date" name="to_date" 
                                           value="{{ $toDate }}" required>
                                </div>

                                <!-- Branch (Admin Only) -->
                                @if($user->hasRole('admin'))
                                <div class="col-md-6 col-lg-3 mb-3">
                                    <label for="branch_id" class="form-label">Branch</label>
                                    <select class="form-select" id="branch_id" name="branch_id">
                                        <option value="all" {{ $branchId === 'all' ? 'selected' : '' }}>All Branches</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif

                                <!-- Equity Category -->
                                <div class="col-md-6 col-lg-3 mb-3">
                                    <label for="equity_category_id" class="form-label">Category Filter (Optional)</label>
                                    <select class="form-select" id="equity_category_id" name="equity_category_id">
                                        <option value="">All Categories (IFRS Format)</option>
                                        @foreach($equityCategories as $category)
                                            <option value="{{ $category->id }}" {{ ($equityCategoryId ?? '') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }} (Legacy)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </form>

                        <hr class="my-4">

                        <!-- Summary Cards -->
                        @php
                            $openingBalance = $equityStatementData['total_opening'] ?? ($legacyChangesEquityData['opening_balance'] ?? 0);
                            $netChange = $equityStatementData['total_movement'] ?? ($legacyChangesEquityData['overall_total'] ?? 0);
                            $closingBalance = $equityStatementData['total_closing'] ?? ($legacyChangesEquityData['closing_balance'] ?? 0);
                            $numCategories = isset($equityStatementData) ? count($equityStatementData['equity_components']) : (count($legacyChangesEquityData['grouped_data'] ?? []));
                        @endphp

                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h4 class="mb-1">{{ number_format($openingBalance, 2) }}</h4>
                                                <small>Opening Total Equity</small>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <i class="bx bx-dollar-circle fs-1"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h4 class="mb-1">{{ number_format($netChange, 2) }}</h4>
                                                <small>Total Movement</small>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <i class="bx bx-trending-up fs-1"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h4 class="mb-1">{{ number_format($closingBalance, 2) }}</h4>
                                                <small>Closing Total Equity</small>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <i class="bx bx-calculator fs-1"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h4 class="mb-1">{{ isset($equityStatementData) ? 'IFRS' : 'LEGACY' }}</h4>
                                                <small>Report Format</small>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <i class="bx bx-file fs-1"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Report Content (IFRS Format) -->
                        @if(isset($equityStatementData))
                            <!-- IFRS Columnar Format -->
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">
                                        <i class="bx bx-file-find me-2"></i>Statement of Changes in Equity (IAS 1) - Columnar Format
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-bordered mb-0 table-sm">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="min-width: 200px;"></th>
                                                    @foreach($equityStatementData['equity_components'] as $component)
                                                        <th class="text-end">{{ $component['name'] }}</th>
                                                    @endforeach
                                                    <th class="text-end bg-primary text-white">Total Equity</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Opening Balance -->
                                                <tr class="fw-bold table-secondary">
                                                    <td>Balance at {{ \Carbon\Carbon::parse($fromDate)->format('F d, Y') }}</td>
                                                    @foreach($equityStatementData['equity_components'] as $component)
                                                        <td class="text-end">
                                                            {{ number_format($equityStatementData['opening_balances'][$component['key']] ?? 0, 2) }}
                                                        </td>
                                                    @endforeach
                                                    <td class="text-end fw-bold bg-light">
                                                        {{ number_format($equityStatementData['total_opening'], 2) }}
                                                    </td>
                                                </tr>

                                                <!-- Spacer -->
                                                <tr style="height: 10px;">
                                                    <td colspan="{{ count($equityStatementData['equity_components']) + 2 }}" style="border: none;"></td>
                                                </tr>

                                                <!-- Changes Header -->
                                                <tr class="table-info">
                                                    <td colspan="{{ count($equityStatementData['equity_components']) + 2 }}" class="fw-bold">
                                                        Changes in equity for the year:
                                                    </td>
                                                </tr>

                                                @php
                                                    // Collect all line items
                                                    $allLineItems = [];
                                                    foreach ($equityStatementData['equity_components'] as $component) {
                                                        $movements = $equityStatementData['movements'][$component['key']];
                                                        foreach ($movements['line_items'] as $item) {
                                                            if (!isset($allLineItems[$item['name']])) {
                                                                $allLineItems[$item['name']] = [
                                                                    'name' => $item['name'],
                                                                    'category' => $item['category'],
                                                                    'amounts' => []
                                                                ];
                                                            }
                                                            $allLineItems[$item['name']]['amounts'][$component['key']] = $item['amount'];
                                                        }
                                                    }
                                                    
                                                    // Group by category
                                                    $comprehensiveIncomeItems = array_filter($allLineItems, fn($item) => $item['category'] === 'comprehensive_income');
                                                    $transactionsWithOwnersItems = array_filter($allLineItems, fn($item) => $item['category'] === 'transactions_with_owners');
                                                @endphp

                                                <!-- Comprehensive Income Items -->
                                                @if(count($comprehensiveIncomeItems) > 0)
                                                    @foreach($comprehensiveIncomeItems as $item)
                                                        <tr class="line-item">
                                                            <td class="ps-3">{{ $item['name'] }}</td>
                                                            @php $rowTotal = 0; @endphp
                                                            @foreach($equityStatementData['equity_components'] as $component)
                                                                @php
                                                                    $amount = $item['amounts'][$component['key']] ?? 0;
                                                                    $rowTotal += $amount;
                                                                @endphp
                                                                <td class="text-end">
                                                                    @if(abs($amount) > 0.01)
                                                                        <span class="{{ $amount < 0 ? 'text-danger' : '' }}">
                                                                            {{ number_format($amount, 2) }}
                                                                        </span>
                                                                    @else
                                                                        --
                                                                    @endif
                                                                </td>
                                                            @endforeach
                                                            <td class="text-end bg-light">
                                                                <span class="{{ $rowTotal < 0 ? 'text-danger' : '' }}">
                                                                    {{ number_format($rowTotal, 2) }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach

                                                    <!-- Total Comprehensive Income -->
                                                    <tr class="fw-bold table-primary">
                                                        <td>Total comprehensive income</td>
                                                        @php $totalCompIncome = 0; @endphp
                                                        @foreach($equityStatementData['equity_components'] as $component)
                                                            @php
                                                                $total = 0;
                                                                foreach ($comprehensiveIncomeItems as $item) {
                                                                    $total += $item['amounts'][$component['key']] ?? 0;
                                                                }
                                                                $totalCompIncome += $total;
                                                            @endphp
                                                            <td class="text-end">{{ number_format($total, 2) }}</td>
                                                        @endforeach
                                                        <td class="text-end fw-bold bg-light">{{ number_format($totalCompIncome, 2) }}</td>
                                                    </tr>
                                                @endif

                                                <!-- Transactions with Owners -->
                                                @if(count($transactionsWithOwnersItems) > 0)
                                                    <tr style="height: 10px;">
                                                        <td colspan="{{ count($equityStatementData['equity_components']) + 2 }}" style="border: none;"></td>
                                                    </tr>
                                                    <tr class="table-warning">
                                                        <td colspan="{{ count($equityStatementData['equity_components']) + 2 }}" class="fw-bold">
                                                            Transactions with owners:
                                                        </td>
                                                    </tr>
                                                    @foreach($transactionsWithOwnersItems as $item)
                                                        <tr class="line-item">
                                                            <td class="ps-3">{{ $item['name'] }}</td>
                                                            @php $rowTotal = 0; @endphp
                                                            @foreach($equityStatementData['equity_components'] as $component)
                                                                @php
                                                                    $amount = $item['amounts'][$component['key']] ?? 0;
                                                                    $rowTotal += $amount;
                                                                @endphp
                                                                <td class="text-end">
                                                                    @if(abs($amount) > 0.01)
                                                                        <span class="{{ $amount < 0 ? 'text-danger' : '' }}">
                                                                            {{ number_format($amount, 2) }}
                                                                        </span>
                                                                    @else
                                                                        --
                                                                    @endif
                                                                </td>
                                                            @endforeach
                                                            <td class="text-end bg-light">
                                                                <span class="{{ $rowTotal < 0 ? 'text-danger' : '' }}">
                                                                    {{ number_format($rowTotal, 2) }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach

                                                    <!-- Total Transactions with Owners -->
                                                    <tr class="fw-bold table-warning">
                                                        <td>Total transactions with owners</td>
                                                        @php $totalTxnOwners = 0; @endphp
                                                        @foreach($equityStatementData['equity_components'] as $component)
                                                            @php
                                                                $total = 0;
                                                                foreach ($transactionsWithOwnersItems as $item) {
                                                                    $total += $item['amounts'][$component['key']] ?? 0;
                                                                }
                                                                $totalTxnOwners += $total;
                                                            @endphp
                                                            <td class="text-end">{{ number_format($total, 2) }}</td>
                                                        @endforeach
                                                        <td class="text-end fw-bold bg-light">{{ number_format($totalTxnOwners, 2) }}</td>
                                                    </tr>
                                                @endif

                                                <!-- Closing Balance -->
                                                <tr style="height: 10px;">
                                                    <td colspan="{{ count($equityStatementData['equity_components']) + 2 }}" style="border: none;"></td>
                                                </tr>
                                                <tr class="fw-bold table-success">
                                                    <td>Balance at {{ \Carbon\Carbon::parse($toDate)->format('F d, Y') }}</td>
                                                    @foreach($equityStatementData['equity_components'] as $component)
                                                        <td class="text-end">
                                                            {{ number_format($equityStatementData['closing_balances'][$component['key']] ?? 0, 2) }}
                                                        </td>
                                                    @endforeach
                                                    <td class="text-end fw-bold bg-light">
                                                        {{ number_format($equityStatementData['total_closing'], 2) }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes -->
                            @if(isset($equityStatementData['notes']) && count($equityStatementData['notes']) > 0)
                                <div class="card mt-3 border-secondary">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0">Notes to the Statement:</h6>
                                    </div>
                                    <div class="card-body">
                                        <ol class="small">
                                            @foreach($equityStatementData['notes'] as $note)
                                                <li>{{ $note }}</li>
                                            @endforeach
                                        </ol>
                                    </div>
                                </div>
                            @endif

                        @elseif(isset($legacyChangesEquityData) && count($legacyChangesEquityData['grouped_data'] ?? []) > 0)
                            <!-- Legacy Format Display (for category filter) -->
                            @foreach($legacyChangesEquityData['grouped_data'] as $categoryName => $transactions)
                                <div class="card border-primary mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">
                                                <i class="bx bx-category me-2"></i>{{ $categoryName }}
                                            </h6>
                                            <div>
                                                <span class="badge bg-light text-dark">
                                                    Net Change: {{ number_format($legacyChangesEquityData['category_totals'][$categoryName]['net_change'], 2) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Account</th>
                                                        <th>Description</th>
                                                        <th class="text-center">Nature</th>
                                                        <th class="text-end">Amount</th>
                                                        <th class="text-end">Impact</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($transactions as $transaction)
                                                        <tr>
                                                            <td>{{ \Carbon\Carbon::parse($transaction['date'])->format('M d, Y') }}</td>
                                                            <td>
                                                                <div>
                                                                    <strong>{{ $transaction['account_name'] }}</strong>
                                                                </div>
                                                                <small class="text-muted">{{ $transaction['account_code'] }}</small>
                                                            </td>
                                                            <td>{{ $transaction['description'] ?: 'No description' }}</td>
                                                            <td class="text-center">
                                                                <span class="badge {{ $transaction['nature'] === 'credit' ? 'bg-success' : 'bg-danger' }}">
                                                                    {{ ucfirst($transaction['nature']) }}
                                                                </span>
                                                            </td>
                                                            <td class="text-end">{{ number_format($transaction['amount'], 2) }}</td>
                                                            <td class="text-end">
                                                                <span class="fw-bold {{ $transaction['impact'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                                    {{ $transaction['impact'] >= 0 ? '+' : '' }}{{ number_format($transaction['impact'], 2) }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-primary">
                                                        <td colspan="4"><strong>Total for {{ $categoryName }}</strong></td>
                                                        <td class="text-end">
                                                            <strong>
                                                                {{ number_format($legacyChangesEquityData['category_totals'][$categoryName]['credit_total'], 2) }} /
                                                                {{ number_format($legacyChangesEquityData['category_totals'][$categoryName]['debit_total'], 2) }}
                                                            </strong>
                                                        </td>
                                                        <td class="text-end">
                                                            <strong class="{{ $legacyChangesEquityData['category_totals'][$categoryName]['net_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                                {{ $legacyChangesEquityData['category_totals'][$categoryName]['net_change'] >= 0 ? '+' : '' }}{{ number_format($legacyChangesEquityData['category_totals'][$categoryName]['net_change'], 2) }}
                                                            </strong>
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-5">
                                <i class="bx bx-info-circle fs-1 text-muted mb-3"></i>
                                <h5 class="text-muted">No Equity Data Found</h5>
                                <p class="text-muted">No equity transactions found for the selected period and filters.</p>
                                <p class="small text-muted">Please ensure your GL transactions have proper equity categories assigned.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
function generateReport() {
    document.getElementById('changesEquityForm').submit();
}

function exportReport(type) {
    const form = document.getElementById('changesEquityForm');
    const formData = new FormData(form);
    formData.append('export_type', type);
    
    // Create a temporary form for export
    const exportForm = document.createElement('form');
    exportForm.method = 'POST';
    exportForm.action = '{{ route("accounting.reports.changes-equity.export") }}';
    
    
    // Add CSRF token
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';
    exportForm.appendChild(csrfToken);
    
    // Add form data
    for (let [key, value] of formData.entries()) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        exportForm.appendChild(input);
    }
    
    document.body.appendChild(exportForm);
    exportForm.submit();
    document.body.removeChild(exportForm);
}

// Auto-submit form when filters change
document.addEventListener('DOMContentLoaded', function() {
    const filterInputs = document.querySelectorAll('#changesEquityForm select, #changesEquityForm input[type="date"]');
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            generateReport();
        });
    });
});
</script>
@endsection 