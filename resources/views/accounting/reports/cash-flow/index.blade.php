@extends('layouts.main')

@section('title', 'Cash Flow Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Cash Flow Report', 'url' => '#', 'icon' => 'bx bx-money']
        ]" />
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-money me-2"></i>Cash Flow Report</h5>
                                <small class="text-muted">Track cash flows from operating, investing, and financing activities</small>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary" onclick="exportReport('pdf')">
                                    <i class="bx bx-file-pdf me-1"></i>Export PDF
                                </button>
                                <button type="button" class="btn btn-outline-success" onclick="exportReport('excel')">
                                    <i class="bx bx-file me-1"></i>Export Excel
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filters Section -->
                        <form id="cashFlowForm" method="GET" action="{{ route('accounting.reports.cash-flow') }}">
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

                                <!-- Branch (Assigned) -->
                                <div class="col-md-6 col-lg-3 mb-3">
                                    <label for="branch_id" class="form-label">Branch</label>
                                    <select class="form-select" id="branch_id" name="branch_id">
                                        @if(($branches->count() ?? 0) > 1)
                                            <option value="all" {{ $branchId === 'all' ? 'selected' : '' }}>All Branches</option>
                                        @endif
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Method Selector (IFRS) -->
                                <div class="col-md-6 col-lg-3 mb-3">
                                    <label for="method" class="form-label">
                                        Method (IAS 7)
                                        <i class="bx bx-info-circle text-info" data-bs-toggle="tooltip" title="Direct method shows actual cash flows. Indirect method reconciles profit to cash."></i>
                                    </label>
                                    <select class="form-select" id="method" name="method">
                                        <option value="direct" {{ ($method ?? 'direct') === 'direct' ? 'selected' : '' }}>Direct Method</option>
                                        <option value="indirect" {{ ($method ?? 'direct') === 'indirect' ? 'selected' : '' }}>Indirect Method</option>
                                    </select>
                                </div>

                                <!-- Cash Flow Category (Optional Filter) -->
                                <div class="col-md-6 col-lg-3 mb-3">
                                    <label for="cash_flow_category_id" class="form-label">Category Filter (Optional)</label>
                                    <select class="form-select" id="cash_flow_category_id" name="cash_flow_category_id">
                                        <option value="">All Categories (IFRS Format)</option>
                                        @foreach($cashFlowCategories as $category)
                                            <option value="{{ $category->id }}" {{ ($cashFlowCategoryId ?? '') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }} (Legacy)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Comparative Periods Section -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="card bg-light mb-3">
                                        <div class="card-body">
                                            <h6 class="card-title mb-3">
                                                <i class="bx bx-calendar-event me-2"></i>Comparative Periods (Optional)
                                            </h6>
                                            <div id="comparativePeriods">
                                                <!-- Comparative periods will be added here -->
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addComparativePeriod()">
                                                <i class="bx bx-plus me-1"></i>Add Comparative Period
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-search me-1"></i>Generate Report
                                    </button>
                                    <a href="{{ route('accounting.reports.cash-flow') }}" class="btn btn-secondary ms-2">
                                        <i class="bx bx-refresh me-1"></i>Reset
                                    </a>
                                </div>
                            </div>
                        </form>

                        <br>

                        <!-- Summary Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h4 class="mb-1">{{ number_format($cashFlowData['opening_cash'] ?? $cashFlowData['opening_balance'] ?? 0, 2) }}</h4>
                                                <small>Opening Cash Balance</small>
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
                                                <h4 class="mb-1">{{ number_format($cashFlowData['net_cash_flow'] ?? $cashFlowData['overall_total'] ?? 0, 2) }}</h4>
                                                <small>Net Cash Flow</small>
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
                                                <h4 class="mb-1">{{ number_format($cashFlowData['closing_cash'] ?? $cashFlowData['closing_balance'] ?? 0, 2) }}</h4>
                                                <small>Closing Cash Balance</small>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <i class="bx bx-wallet fs-1"></i>
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
                                                <h4 class="mb-1">{{ isset($cashFlowData['method']) ? strtoupper($cashFlowData['method']) : 'LEGACY' }}</h4>
                                                <small>Report Method</small>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <i class="bx bx-file fs-1"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cash Flow Data (IFRS Format) -->
                        @if(isset($cashFlowData['cash_flows']))
                            <!-- IFRS Format Display -->
                            <div class="card border-info mb-4">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">
                                        <i class="bx bx-file-find me-2"></i>Statement of Cash Flows (IAS 7) - {{ ucfirst($cashFlowData['method']) }} Method
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <!-- Operating Activities -->
                                    <div class="mb-4">
                                        <h6 class="text-primary fw-bold">CASH FLOWS FROM OPERATING ACTIVITIES</h6>
                                        <table class="table table-sm">
                                            @foreach($cashFlowData['cash_flows']['operating']['line_items'] as $item)
                                                @if(isset($item['is_header']) && $item['is_header'])
                                                    <tr>
                                                        <td colspan="2" class="fw-bold ps-3">{{ $item['name'] }}</td>
                                                    </tr>
                                                @else
                                                    <tr class="{{ isset($item['is_subtotal']) && $item['is_subtotal'] ? 'table-light fw-bold' : '' }}">
                                                        <td class="ps-{{ $item['level'] * 2 }}">{{ $item['name'] }}</td>
                                                        <td class="text-end" style="width: 150px;">
                                                            @if($item['amount'] !== null)
                                                                <span class="{{ ($item['amount'] ?? 0) < 0 ? 'text-danger' : '' }}">
                                                                    {{ number_format($item['amount'], 2) }}
                                                                </span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                            <tr class="table-primary">
                                                <td class="fw-bold">Net cash from operating activities</td>
                                                <td class="text-end fw-bold">
                                                    <span class="{{ $cashFlowData['cash_flows']['operating']['net'] < 0 ? 'text-danger' : 'text-success' }}">
                                                        {{ number_format($cashFlowData['cash_flows']['operating']['net'], 2) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>

                                    <!-- Investing Activities -->
                                    <div class="mb-4">
                                        <h6 class="text-primary fw-bold">CASH FLOWS FROM INVESTING ACTIVITIES</h6>
                                        <table class="table table-sm">
                                            @foreach($cashFlowData['cash_flows']['investing']['line_items'] as $item)
                                                @if(abs($item['amount'] ?? 0) > 0.01)
                                                    <tr>
                                                        <td class="ps-2">{{ $item['name'] }}</td>
                                                        <td class="text-end" style="width: 150px;">
                                                            <span class="{{ ($item['amount'] ?? 0) < 0 ? 'text-danger' : '' }}">
                                                                {{ number_format($item['amount'], 2) }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                            <tr class="table-primary">
                                                <td class="fw-bold">Net cash from investing activities</td>
                                                <td class="text-end fw-bold">
                                                    <span class="{{ $cashFlowData['cash_flows']['investing']['net'] < 0 ? 'text-danger' : 'text-success' }}">
                                                        {{ number_format($cashFlowData['cash_flows']['investing']['net'], 2) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>

                                    <!-- Financing Activities -->
                                    <div class="mb-4">
                                        <h6 class="text-primary fw-bold">CASH FLOWS FROM FINANCING ACTIVITIES</h6>
                                        <table class="table table-sm">
                                            @foreach($cashFlowData['cash_flows']['financing']['line_items'] as $item)
                                                @if(abs($item['amount'] ?? 0) > 0.01)
                                                    <tr>
                                                        <td class="ps-2">{{ $item['name'] }}</td>
                                                        <td class="text-end" style="width: 150px;">
                                                            <span class="{{ ($item['amount'] ?? 0) < 0 ? 'text-danger' : '' }}">
                                                                {{ number_format($item['amount'], 2) }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                            <tr class="table-primary">
                                                <td class="fw-bold">Net cash from financing activities</td>
                                                <td class="text-end fw-bold">
                                                    <span class="{{ $cashFlowData['cash_flows']['financing']['net'] < 0 ? 'text-danger' : 'text-success' }}">
                                                        {{ number_format($cashFlowData['cash_flows']['financing']['net'], 2) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>

                                    <!-- Summary -->
                                    <div class="mt-4 pt-3 border-top">
                                        <table class="table table-sm">
                                            <tr class="table-info">
                                                <td class="fw-bold">NET INCREASE/(DECREASE) IN CASH AND CASH EQUIVALENTS</td>
                                                <td class="text-end fw-bold" style="width: 150px;">
                                                    <span class="{{ $cashFlowData['net_cash_flow'] < 0 ? 'text-danger' : 'text-success' }}">
                                                        {{ number_format($cashFlowData['net_cash_flow'], 2) }}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Cash and cash equivalents at beginning of period</td>
                                                <td class="text-end">{{ number_format($cashFlowData['opening_cash'], 2) }}</td>
                                            </tr>
                                            <tr class="table-success">
                                                <td class="fw-bold">Cash and cash equivalents at end of period</td>
                                                <td class="text-end fw-bold">{{ number_format($cashFlowData['closing_cash'], 2) }}</td>
                                            </tr>
                                        </table>
                                    </div>

                                    <!-- Notes -->
                                    @if(isset($cashFlowData['notes']) && count($cashFlowData['notes']) > 0)
                                        <div class="mt-4 pt-3 border-top">
                                            <h6 class="text-muted mb-3">NOTES:</h6>
                                            <ol class="small text-muted">
                                                @foreach($cashFlowData['notes'] as $note)
                                                    <li>{{ $note }}</li>
                                                @endforeach
                                            </ol>
                                        </div>
                                    @endif
                                </div>
                            </div>

                        @elseif(isset($legacyCashFlowData) && count($legacyCashFlowData['grouped_data'] ?? []) > 0)
                            <!-- Legacy Format Display (for category filter) -->
                            @foreach($legacyCashFlowData['grouped_data'] as $categoryName => $transactions)
                                <div class="card border-primary mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">
                                                <i class="bx bx-category me-2"></i>{{ $categoryName }}
                                            </h6>
                                            <div>
                                                <span class="badge bg-light text-dark">
                                                    Net Flow: {{ number_format($legacyCashFlowData['category_totals'][$categoryName]['net_change'], 2) }}
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
                                                                {{ number_format($legacyCashFlowData['category_totals'][$categoryName]['credit_total'], 2) }} /
                                                                {{ number_format($legacyCashFlowData['category_totals'][$categoryName]['debit_total'], 2) }}
                                                            </strong>
                                                        </td>
                                                        <td class="text-end">
                                                            <strong class="{{ $legacyCashFlowData['category_totals'][$categoryName]['net_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                                {{ $legacyCashFlowData['category_totals'][$categoryName]['net_change'] >= 0 ? '+' : '' }}{{ number_format($legacyCashFlowData['category_totals'][$categoryName]['net_change'], 2) }}
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
                                <i class="bx bx-money fs-1 text-muted mb-3"></i>
                                <h5 class="text-muted">No Cash Flow Data Found</h5>
                                <p class="text-muted">No cash flow transactions found for the selected period and filters.</p>
                                <p class="small text-muted">Please ensure your GL transactions have proper transaction types and cash flow categories assigned.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
let comparativePeriodCount = 0;

function exportReport(type) {
    const form = document.getElementById('cashFlowForm');
    const exportTypeInput = document.createElement('input');
    exportTypeInput.type = 'hidden';
    exportTypeInput.name = 'export_type';
    exportTypeInput.value = type;
    form.appendChild(exportTypeInput);
    
    form.action = "{{ route('accounting.reports.cash-flow.export') }}";
    form.submit();
    
    // Remove the input after submission
    form.removeChild(exportTypeInput);
    form.action = "{{ route('accounting.reports.cash-flow') }}";
}

function addComparativePeriod() {
    const container = document.getElementById('comparativePeriods');
    const periodDiv = document.createElement('div');
    periodDiv.className = 'row mb-3 comparative-period';
    periodDiv.id = `period-${comparativePeriodCount}`;
    
    periodDiv.innerHTML = `
        <div class="col-md-3">
            <label class="form-label">Period Name</label>
            <input type="text" class="form-control" name="comparative_periods[${comparativePeriodCount}][name]" 
                   placeholder="e.g., Prior Year" value="Period ${comparativePeriodCount + 1}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Start Date</label>
            <input type="date" class="form-control" name="comparative_periods[${comparativePeriodCount}][start_date]" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">End Date</label>
            <input type="date" class="form-control" name="comparative_periods[${comparativePeriodCount}][end_date]" required>
        </div>
        <div class="col-md-1 d-flex align-items-end">
            <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeComparativePeriod(${comparativePeriodCount})">
                <i class="bx bx-trash"></i>
            </button>
        </div>
    `;
    
    container.appendChild(periodDiv);
    comparativePeriodCount++;
}

function removeComparativePeriod(id) {
    const period = document.getElementById(`period-${id}`);
    if (period) {
        period.remove();
    }
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection 