@extends('layouts.main')

@section('title', 'Statement of Changes in Equity')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Statement of Changes in Equity', 'url' => '#', 'icon' => 'bx bx-trending-up']
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
                        <form id="equityForm" method="GET" action="{{ route('accounting.reports.changes-equity') }}">
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

                                <!-- Branch -->
                                <div class="col-md-6 col-lg-3 mb-3">
                                    <label for="branch_id" class="form-label">Branch</label>
                                    <select class="form-select" id="branch_id" name="branch_id">
                                        @if(($branches->count() ?? 0) > 1)
                                            <option value="all" {{ ($branchId ?? 'all') === 'all' ? 'selected' : '' }}>All Branches</option>
                                        @endif
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" {{ ($branchId ?? '') == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Equity Category Filter (Optional) -->
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

                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-search me-1"></i>Generate Report
                                    </button>
                                    <a href="{{ route('accounting.reports.changes-equity') }}" class="btn btn-secondary ms-2">
                                        <i class="bx bx-refresh me-1"></i>Reset
                                    </a>
                                </div>
                            </div>
                        </form>

                        <hr class="my-4">

                        <!-- IFRS Format Statement -->
                        @if(isset($equityStatementData))
                            <!-- Summary Cards -->
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body">
                                            <h6 class="mb-1">Opening Total Equity</h6>
                                            <h4 class="mb-0">{{ number_format($equityStatementData['total_opening'], 2) }}</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <h6 class="mb-1">Total Movement</h6>
                                            <h4 class="mb-0">{{ number_format($equityStatementData['total_movement'], 2) }}</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-info text-white">
                                        <div class="card-body">
                                            <h6 class="mb-1">Closing Total Equity</h6>
                                            <h4 class="mb-0">{{ number_format($equityStatementData['total_closing'], 2) }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- IFRS Columnar Format -->
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">
                                        <i class="bx bx-file-find me-2"></i>Statement of Changes in Equity (IAS 1) - Columnar Format
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-bordered mb-0">
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
                                                <tr>
                                                    <td colspan="{{ count($equityStatementData['equity_components']) + 2 }}" class="py-1"></td>
                                                </tr>

                                                <!-- Changes Header -->
                                                <tr class="table-info">
                                                    <td colspan="{{ count($equityStatementData['equity_components']) + 2 }}" class="fw-bold">
                                                        <strong>Changes in equity for the year:</strong>
                                                    </td>
                                                </tr>

                                                <!-- Collect all line items -->
                                                @php
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
                                                        <tr>
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
                                                        <td>Total comprehensive income for the year</td>
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
                                                    <tr>
                                                        <td colspan="{{ count($equityStatementData['equity_components']) + 2 }}" class="py-1"></td>
                                                    </tr>
                                                    <tr class="table-warning">
                                                        <td colspan="{{ count($equityStatementData['equity_components']) + 2 }}">
                                                            <strong>Transactions with owners:</strong>
                                                        </td>
                                                    </tr>
                                                    @foreach($transactionsWithOwnersItems as $item)
                                                        <tr>
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
                                                <tr>
                                                    <td colspan="{{ count($equityStatementData['equity_components']) + 2 }}" class="py-1"></td>
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

                        @else
                            <div class="text-center py-5">
                                <i class="bx bx-trending-up fs-1 text-muted mb-3"></i>
                                <h5 class="text-muted">No Equity Data Found</h5>
                                <p class="text-muted">No equity transactions found for the selected period and filters.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
function exportReport(type) {
    const form = document.getElementById('equityForm');
    const exportTypeInput = document.createElement('input');
    exportTypeInput.type = 'hidden';
    exportTypeInput.name = 'export_type';
    exportTypeInput.value = type;
    form.appendChild(exportTypeInput);
    
    form.action = "{{ route('accounting.reports.changes-equity.export') }}";
    form.submit();
    
    // Remove the input after submission
    form.removeChild(exportTypeInput);
    form.action = "{{ route('accounting.reports.changes-equity') }}";
}
</script>
@endsection
