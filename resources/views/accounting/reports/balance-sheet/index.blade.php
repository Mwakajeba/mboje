@extends('layouts.main')

@section('title', 'Balance Sheet Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Balance Sheet Report', 'url' => '#', 'icon' => 'bx bx-balance']
        ]" />
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-balance me-2"></i>Balance Sheet Report
                            </h4>
                        </div>

                        <!-- Filters -->
                        <form id="balanceSheetForm" method="GET" action="{{ route('accounting.reports.balance-sheet') }}" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label for="as_of_date" class="form-label">As of Date</label>
                                <input type="date" class="form-control" id="as_of_date" name="as_of_date" value="{{ $asOfDate }}" required>
                            </div>

                            <div class="col-md-2">
                                <label for="reporting_type" class="form-label">Method</label>
                                <select class="form-select" id="reporting_type" name="reporting_type">
                                    <option value="accrual" {{ $reportingType === 'accrual' ? 'selected' : '' }}>Accrual Basis</option>
                                    <option value="cash" {{ $reportingType === 'cash' ? 'selected' : '' }}>Cash Basis</option>
                                </select>
                            </div>

                            <div class="col-md-2">
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

                            <div class="col-md-2">
                                <label for="report_type" class="form-label">Type</label>
                                <select class="form-select" id="report_type" name="report_type">
                                    <option value="summary" {{ $reportType === 'summary' ? 'selected' : '' }}>Summary</option>
                                    <option value="detailed" {{ $reportType === 'detailed' ? 'selected' : '' }}>Detailed</option>
                                </select>
                            </div>

                            <!-- Comparative Dates (optional) -->
                            <div class="col-12">
                                <div class="card border border-info">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <h6 class="card-title mb-0 text-info">
                                                <i class="bx bx-calendar me-2"></i>Comparative Dates (optional)
                                            </h6>
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addComparative()">
                                                <i class="bx bx-plus"></i> Add Comparative
                                            </button>
                                        </div>
                                        <div id="comparatives_container">
                                            @if(!empty($comparativeDates))
                                                @foreach($comparativeDates as $idx => $compDate)
                                                    <div class="row g-2 align-items-end mb-2 comparative-row">
                                                        <div class="col-md-4">
                                                            <label class="form-label">Name</label>
                                                            <input type="text" class="form-control" name="comparative_dates[{{ $idx }}][name]" value="{{ $compDate['name'] ?? ('Comparative '.($idx+1)) }}" placeholder="e.g. Previous Quarter">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">As of Date</label>
                                                            <input type="date" class="form-control" name="comparative_dates[{{ $idx }}][as_of_date]" value="{{ $compDate['as_of_date'] ?? '' }}" required>
                                                        </div>
                                                        <div class="col-md-2 text-end">
                                                            <button type="button" class="btn btn-outline-danger" onclick="this.closest('.comparative-row').remove()">
                                                                <i class="bx bx-trash"></i> Remove
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bx bx-search me-1"></i>Generate Report
                                </button>
                                @if(request()->has('as_of_date'))
                                <div class="btn-group">
                                    <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="bx bx-download me-1"></i>Export
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('accounting.reports.balance-sheet.export', array_merge(request()->all(), ['export_type' => 'pdf'])) }}">Export as PDF</a></li>
                                        <li><a class="dropdown-item" href="{{ route('accounting.reports.balance-sheet.export', array_merge(request()->all(), ['export_type' => 'excel'])) }}">Export as Excel</a></li>
                                    </ul>
                                </div>
                                @endif
                                <a href="{{ route('accounting.reports.balance-sheet') }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-reset me-1"></i> Reset
                                </a>
                            </div>
                        </form>

                        <!-- Report Display -->
                        @if(request()->has('as_of_date'))
                        <div class="row">
                            <div class="col-12">
                                <div class="card radius-10 border-0 shadow-sm">
                                    <div class="card-header bg-transparent border-0">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h5 class="mb-0 text-dark"><i class="bx bx-balance me-2"></i>BALANCE SHEET</h5>
                                                <small class="text-muted">
                                                    As of {{ \Carbon\Carbon::parse($asOfDate)->format('d-m-Y') }} vs {{ $previousYearData['year'] }}
                                                    @php
                                                        $currentBranchName = null;
                                                        if ($branchId && $branchId !== 'all') {
                                                            $currentBranchName = optional($branches->firstWhere('id', $branchId))->name;
                                                        }
                                                    @endphp
                                                    — {{ $currentBranchName ? ('Branch: ' . $currentBranchName) : 'All Branches' }}
                                                    — {{ ucfirst($reportingType) }} Basis
                                                    — {{ ucfirst($reportType) }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="row">
                                            <!-- Balance Sheet Section -->
                                            <div class="col-12">
                                                <div class="financial-section">
                                                    <div class="section-header bg-light p-3 rounded-top">
                                                        <h4 class="mb-0 text-dark"><i class="bx bx-balance me-2"></i>BALANCE SHEET</h4>
                                                        <small class="text-muted">As of {{ \Carbon\Carbon::parse($asOfDate)->format('d-m-Y') }} vs {{ $previousYearData['year'] }}</small>
                                                    </div>

                                                    <!-- Assets Section -->
                                                    <div class="section-content border rounded-bottom">
                                                        <div class="section-title bg-light p-2 border-bottom">
                                                            <h6 class="mb-0 text-dark"><i class="bx bx-trending-up me-1"></i>ASSETS</h6>
                                                        </div>
                                                        <div class="table-responsive">
                                                            <table class="table table-striped">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th>Account</th>
                                                                        <th class="text-end">Current ({{ \Carbon\Carbon::parse($asOfDate)->format('d-m-Y') }})</th>
                                                                        <th class="text-end">Previous Year ({{ $previousYearData['year'] }})</th>
                                                                        @foreach($comparativeData as $compName => $compInfo)
                                                                        <th class="text-end">{{ $compName }} ({{ \Carbon\Carbon::parse($compInfo['asOfDate'])->format('d-m-Y') }})</th>
                                                                        @endforeach
                                                                        <th class="text-end">Change</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @php $sumAsset = 0; $sumAssetPrev = 0; @endphp
                                                                    @foreach($financialReportData['chartAccountsAssets'] as $mainGroupName => $mainGroup)
                                                                    @if(isset($mainGroup['total']) && $mainGroup['total'] != 0)
                                                                    @php $colspan = 4 + count($comparativeData); @endphp
                                                                    <tr class="table-primary">
                                                                        <td colspan="{{ $colspan }}" class="fw-bold text-dark"><i class="bx bx-folder me-1"></i>{{ $mainGroupName }}</td>
                                                                    </tr>
                                                                    @if(isset($mainGroup['fslis']))
                                                                        @foreach($mainGroup['fslis'] as $fsliName => $fsli)
                                                                        @if(isset($fsli['total']) && $fsli['total'] != 0)
                                                                        @php $colspan = 4 + count($comparativeData); @endphp
                                                                        <tr class="table-light">
                                                                            <td colspan="{{ $colspan }}" class="ps-4 fw-medium text-dark">{{ $fsliName }}</td>
                                                                        </tr>
                                                                        @if($reportType === 'detailed' && isset($fsli['accounts']))
                                                                            @foreach($fsli['accounts'] as $chartAccountAsset)
                                                                            @if($chartAccountAsset['sum'] != 0)
                                                                            @php 
                                                                                $sumAsset += $chartAccountAsset['sum'] ?? 0;
                                                                                $prevYearMainGroup = $previousYearData['chartAccountsAssets'][$mainGroupName] ?? [];
                                                                                $prevYearFslis = $prevYearMainGroup['fslis'] ?? [];
                                                                                $prevYearFsli = $prevYearFslis[$fsliName] ?? [];
                                                                                $prevYearAccounts = $prevYearFsli['accounts'] ?? [];
                                                                                $prevYearAccount = collect($prevYearAccounts)->firstWhere('account_id', $chartAccountAsset['account_id']);
                                                                                $prevYearAmount = $prevYearAccount['sum'] ?? 0;
                                                                                $sumAssetPrev += $prevYearAmount;
                                                                                $change = ($chartAccountAsset['sum'] ?? 0) - $prevYearAmount;
                                                                                
                                                                                // Get comparative amounts
                                                                                $comparativeAmounts = [];
                                                                                foreach ($comparativeData as $compName => $compInfo) {
                                                                                    $compMainGroup = $compInfo['data']['chartAccountsAssets'][$mainGroupName] ?? [];
                                                                                    $compFslis = $compMainGroup['fslis'] ?? [];
                                                                                    $compFsli = $compFslis[$fsliName] ?? [];
                                                                                    $compAccounts = $compFsli['accounts'] ?? [];
                                                                                    $compAccount = collect($compAccounts)->firstWhere('account_id', $chartAccountAsset['account_id']);
                                                                                    $comparativeAmounts[$compName] = $compAccount['sum'] ?? 0;
                                                                                }
                                                                            @endphp
                                                                            <tr class="account-row">
                                                                                <td class="ps-5">
                                                                                    <a href="{{ route('accounting.transactions.doubleEntries', Hashids::encode($chartAccountAsset['account_id'])) }}"
                                                                                        class="text-decoration-none text-dark fw-medium">
                                                                                        <i class="bx bx-chevron-right me-1 text-dark"></i>
                                                                                        @if($chartAccountAsset['account_code'] ?? '')<span class="text-muted small">{{ $chartAccountAsset['account_code'] }} - </span>@endif
                                                                                        {{ $chartAccountAsset['account'] }}
                                                                                    </a>
                                                                                </td>
                                                                                <td class="text-end">
                                                                                    <a href="{{ route('accounting.transactions.doubleEntries', Hashids::encode($chartAccountAsset['account_id'])) }}"
                                                                                        class="text-decoration-none fw-bold text-dark">
                                                                                        {{ number_format($chartAccountAsset['sum'] ?? 0,2) }}
                                                                                    </a>
                                                                                </td>
                                                                                <td class="text-end text-dark">
                                                                                    {{ number_format($prevYearAmount,2) }}
                                                                                </td>
                                                                                @foreach($comparativeData as $compName => $compInfo)
                                                                                <td class="text-end text-dark">
                                                                                    {{ number_format($comparativeAmounts[$compName] ?? 0,2) }}
                                                                                </td>
                                                                                @endforeach
                                                                                <td class="text-end">
                                                                                        {{ $change >= 0 ? '+' : '' }}{{ number_format($change,2) }}
                                                                                </td>
                                                                            </tr>
                                                                            @endif
                                                                            @endforeach
                                                                        @else
                                                                            @php 
                                                                                // Summary mode - just show FSLI total
                                                                                $fsliTotal = $fsli['total'] ?? 0;
                                                                                $sumAsset += $fsliTotal;
                                                                                $prevYearMainGroup = $previousYearData['chartAccountsAssets'][$mainGroupName] ?? [];
                                                                                $prevYearFslis = $prevYearMainGroup['fslis'] ?? [];
                                                                                $prevYearFsli = $prevYearFslis[$fsliName] ?? [];
                                                                                $prevYearFsliTotal = $prevYearFsli['total'] ?? 0;
                                                                                $sumAssetPrev += $prevYearFsliTotal;
                                                                                $change = $fsliTotal - $prevYearFsliTotal;
                                                                                
                                                                                // Get comparative totals
                                                                                $comparativeTotals = [];
                                                                                foreach ($comparativeData as $compName => $compInfo) {
                                                                                    $compMainGroup = $compInfo['data']['chartAccountsAssets'][$mainGroupName] ?? [];
                                                                                    $compFslis = $compMainGroup['fslis'] ?? [];
                                                                                    $compFsli = $compFslis[$fsliName] ?? [];
                                                                                    $comparativeTotals[$compName] = $compFsli['total'] ?? 0;
                                                                                }
                                                                            @endphp
                                                                            <tr class="account-row">
                                                                                <td class="ps-5 fw-medium">{{ $fsliName }}</td>
                                                                                <td class="text-end fw-bold">{{ number_format($fsliTotal,2) }}</td>
                                                                                <td class="text-end">{{ number_format($prevYearFsliTotal,2) }}</td>
                                                                                @foreach($comparativeData as $compName => $compInfo)
                                                                                <td class="text-end">{{ number_format($comparativeTotals[$compName] ?? 0,2) }}</td>
                                                                                @endforeach
                                                                                <td class="text-end">{{ $change >= 0 ? '+' : '' }}{{ number_format($change,2) }}</td>
                                                                            </tr>
                                                                        @endif
                                                                        @endif
                                                                        @endforeach
                                                                    @endif
                                                                    @endif
                                                                    @endforeach
                                                                    @php 
                                                                        // Calculate comparative totals for assets
                                                                        $comparativeAssetTotals = [];
                                                                        foreach ($comparativeData as $compName => $compInfo) {
                                                                            $compTotal = 0;
                                                                            foreach ($compInfo['data']['chartAccountsAssets'] as $compMainGroup) {
                                                                                if (isset($compMainGroup['total'])) {
                                                                                    $compTotal += $compMainGroup['total'];
                                                                                }
                                                                            }
                                                                            $comparativeAssetTotals[$compName] = $compTotal;
                                                                        }
                                                                        $assetChange = $sumAsset - $sumAssetPrev;
                                                                    @endphp
                                                                    <tr class="table-secondary fw-bold">
                                                                        <td>TOTAL ASSETS</td>
                                                                        <td class="text-end">{{ number_format($sumAsset,2) }}</td>
                                                                        <td class="text-end">{{ number_format($sumAssetPrev,2) }}</td>
                                                                        @foreach($comparativeData as $compName => $compInfo)
                                                                        <td class="text-end">{{ number_format($comparativeAssetTotals[$compName] ?? 0,2) }}</td>
                                                                        @endforeach
                                                                        <td class="text-end">{{ $assetChange >= 0 ? '+' : '' }}{{ number_format($assetChange,2) }}</td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>

                                                        <!-- Equity Section -->
                                                        <div class="section-title bg-light p-2 border-bottom mt-3">
                                                            <h6 class="mb-0 text-dark"><i class="bx bx-user me-1"></i>EQUITY</h6>
                                                        </div>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm mb-0">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th>Account</th>
                                                                        <th class="text-end">Current ({{ \Carbon\Carbon::parse($asOfDate)->format('d-m-Y') }})</th>
                                                                        <th class="text-end">Previous Year ({{ $previousYearData['year'] }})</th>
                                                                        @foreach($comparativeData as $compName => $compInfo)
                                                                        <th class="text-end">{{ $compName }} ({{ \Carbon\Carbon::parse($compInfo['asOfDate'])->format('d-m-Y') }})</th>
                                                                        @endforeach
                                                                        <th class="text-end">Change</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @php $sumEquity = 0; $sumEquityPrev = 0; @endphp
                                                                    @foreach($financialReportData['chartAccountsEquitys'] as $mainGroupName => $mainGroup)
                                                                    @if(isset($mainGroup['total']) && $mainGroup['total'] != 0)
                                                                    @php $colspan = 4 + count($comparativeData); @endphp
                                                                    <tr class="table-primary">
                                                                        <td colspan="{{ $colspan }}" class="fw-bold text-dark"><i class="bx bx-folder me-1"></i>{{ $mainGroupName }}</td>
                                                                    </tr>
                                                                    @if(isset($mainGroup['fslis']))
                                                                        @foreach($mainGroup['fslis'] as $fsliName => $fsli)
                                                                        @if(isset($fsli['total']) && $fsli['total'] != 0)
                                                                        @php $colspan = 4 + count($comparativeData); @endphp
                                                                        <tr class="table-light">
                                                                            <td colspan="{{ $colspan }}" class="ps-4 fw-medium text-dark">{{ $fsliName }}</td>
                                                                        </tr>
                                                                        @if($reportType === 'detailed' && isset($fsli['accounts']))
                                                                            @foreach($fsli['accounts'] as $chartAccountEquity)
                                                                            @if($chartAccountEquity['sum'] != 0)
                                                                            @php 
                                                                                $sumEquity += ($chartAccountEquity['sum'] ?? 0);
                                                                                $prevYearMainGroup = $previousYearData['chartAccountsEquitys'][$mainGroupName] ?? [];
                                                                                $prevYearFslis = $prevYearMainGroup['fslis'] ?? [];
                                                                                $prevYearFsli = $prevYearFslis[$fsliName] ?? [];
                                                                                $prevYearAccounts = $prevYearFsli['accounts'] ?? [];
                                                                                $prevYearAccount = collect($prevYearAccounts)->firstWhere('account_id', $chartAccountEquity['account_id']);
                                                                                $prevYearAmount = ($prevYearAccount['sum'] ?? 0);
                                                                                $sumEquityPrev += $prevYearAmount;
                                                                                $change = ($chartAccountEquity['sum'] ?? 0) - $prevYearAmount;
                                                                                
                                                                                // Get comparative amounts
                                                                                $comparativeEquityAmounts = [];
                                                                                foreach ($comparativeData as $compName => $compInfo) {
                                                                                    $compMainGroup = $compInfo['data']['chartAccountsEquitys'][$mainGroupName] ?? [];
                                                                                    $compFslis = $compMainGroup['fslis'] ?? [];
                                                                                    $compFsli = $compFslis[$fsliName] ?? [];
                                                                                    $compAccounts = $compFsli['accounts'] ?? [];
                                                                                    $compAccount = collect($compAccounts)->firstWhere('account_id', $chartAccountEquity['account_id']);
                                                                                    $comparativeEquityAmounts[$compName] = $compAccount['sum'] ?? 0;
                                                                                }
                                                                            @endphp
                                                                            <tr class="account-row">
                                                                                <td class="ps-5">
                                                                                    <a href="{{ route('accounting.transactions.doubleEntries', Hashids::encode($chartAccountEquity['account_id'])) }}"
                                                                                        class="text-decoration-none text-dark fw-medium">
                                                                                        <i class="bx bx-chevron-right me-1 text-dark"></i>
                                                                                        @if($chartAccountEquity['account_code'] ?? '')<span class="text-muted small">{{ $chartAccountEquity['account_code'] }} - </span>@endif
                                                                                        {{ $chartAccountEquity['account'] }}
                                                                                    </a>
                                                                                </td>
                                                                                <td class="text-end">
                                                                                    <a href="{{ route('accounting.transactions.doubleEntries', Hashids::encode($chartAccountEquity['account_id'])) }}"
                                                                                        class="text-decoration-none fw-bold text-dark">
                                                                                        {{ number_format($chartAccountEquity['sum'] ?? 0,2) }}
                                                                                    </a>
                                                                                </td>
                                                                                <td class="text-end text-dark">
                                                                                    {{ number_format($prevYearAmount,2) }}
                                                                                </td>
                                                                                @foreach($comparativeData as $compName => $compInfo)
                                                                                <td class="text-end text-dark">
                                                                                    {{ number_format($comparativeEquityAmounts[$compName] ?? 0,2) }}
                                                                                </td>
                                                                                @endforeach
                                                                                <td class="text-end">
                                                                                        {{ $change >= 0 ? '+' : '' }}{{ number_format($change,2) }}
                                                                                </td>
                                                                            </tr>
                                                                            @endif
                                                                            @endforeach
                                                                        @else
                                                                            @php 
                                                                                $fsliTotal = $fsli['total'] ?? 0;
                                                                                $sumEquity += $fsliTotal;
                                                                                $prevYearMainGroup = $previousYearData['chartAccountsEquitys'][$mainGroupName] ?? [];
                                                                                $prevYearFslis = $prevYearMainGroup['fslis'] ?? [];
                                                                                $prevYearFsli = $prevYearFslis[$fsliName] ?? [];
                                                                                $prevYearFsliTotal = $prevYearFsli['total'] ?? 0;
                                                                                $sumEquityPrev += $prevYearFsliTotal;
                                                                                $change = $fsliTotal - $prevYearFsliTotal;
                                                                                
                                                                                // Get comparative totals
                                                                                $comparativeEquityTotals = [];
                                                                                foreach ($comparativeData as $compName => $compInfo) {
                                                                                    $compMainGroup = $compInfo['data']['chartAccountsEquitys'][$mainGroupName] ?? [];
                                                                                    $compFslis = $compMainGroup['fslis'] ?? [];
                                                                                    $compFsli = $compFslis[$fsliName] ?? [];
                                                                                    $comparativeEquityTotals[$compName] = $compFsli['total'] ?? 0;
                                                                                }
                                                                            @endphp
                                                                            <tr class="account-row">
                                                                                <td class="ps-5 fw-medium">{{ $fsliName }}</td>
                                                                                <td class="text-end fw-bold">{{ number_format($fsliTotal,2) }}</td>
                                                                                <td class="text-end">{{ number_format($prevYearFsliTotal,2) }}</td>
                                                                                @foreach($comparativeData as $compName => $compInfo)
                                                                                <td class="text-end">{{ number_format($comparativeEquityTotals[$compName] ?? 0,2) }}</td>
                                                                                @endforeach
                                                                                <td class="text-end">{{ $change >= 0 ? '+' : '' }}{{ number_format($change,2) }}</td>
                                                                            </tr>
                                                                        @endif
                                                                        @endif
                                                                        @endforeach
                                                                    @endif
                                                                    @endif
                                                                    @endforeach
                                                                    @php 
                                                                        // Calculate comparative profit/loss and equity totals
                                                                        $comparativeProfitLoss = [];
                                                                        $comparativeEquityTotals = [];
                                                                        foreach ($comparativeData as $compName => $compInfo) {
                                                                            $comparativeProfitLoss[$compName] = $compInfo['netProfitYtd'] ?? 0;
                                                                            $compEquityTotal = 0;
                                                                            foreach ($compInfo['data']['chartAccountsEquitys'] as $compMainGroup) {
                                                                                if (isset($compMainGroup['total'])) {
                                                                                    $compEquityTotal += $compMainGroup['total'];
                                                                                }
                                                                            }
                                                                            $comparativeEquityTotals[$compName] = $compEquityTotal + ($compInfo['netProfitYtd'] ?? 0);
                                                                        }
                                                                        $profitChange = ($netProfitYtd ?? 0) - $previousYearData['profitLoss'];
                                                                        $equityChange = ($sumEquity + ($netProfitYtd ?? 0)) - ($sumEquityPrev + $previousYearData['profitLoss']);
                                                                    @endphp
                                                                    <tr class="table-info">
                                                                        <td>Profit And Loss (YTD)</td>
                                                                        <td class="text-end fw-bold">{{ number_format($netProfitYtd ?? 0,2) }}</td>
                                                                        <td class="text-end text-dark">{{ number_format($previousYearData['profitLoss'],2) }}</td>
                                                                        @foreach($comparativeData as $compName => $compInfo)
                                                                        <td class="text-end text-dark">{{ number_format($comparativeProfitLoss[$compName] ?? 0,2) }}</td>
                                                                        @endforeach
                                                                        <td class="text-end">{{ $profitChange >= 0 ? '+' : '' }}{{ number_format($profitChange,2) }}</td>
                                                                    </tr>
                                                                    <tr class="table-secondary fw-bold">
                                                                        <td>TOTAL EQUITY</td>
                                                                        <td class="text-end">{{ number_format($sumEquity + ($netProfitYtd ?? 0),2) }}</td>
                                                                        <td class="text-end">{{ number_format($sumEquityPrev + $previousYearData['profitLoss'],2) }}</td>
                                                                        @foreach($comparativeData as $compName => $compInfo)
                                                                        <td class="text-end">{{ number_format($comparativeEquityTotals[$compName] ?? 0,2) }}</td>
                                                                        @endforeach
                                                                        <td class="text-end">{{ $equityChange >= 0 ? '+' : '' }}{{ number_format($equityChange,2) }}</td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>

                                                        <!-- Liabilities Section -->
                                                        <div class="section-title bg-light p-2 border-bottom mt-3">
                                                            <h6 class="mb-0 text-dark"><i class="bx bx-trending-down me-1"></i>LIABILITIES</h6>
                                                        </div>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm mb-0">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th>Account</th>
                                                                        <th class="text-end">Current ({{ \Carbon\Carbon::parse($asOfDate)->format('d-m-Y') }})</th>
                                                                        <th class="text-end">Previous Year ({{ $previousYearData['year'] }})</th>
                                                                        @foreach($comparativeData as $compName => $compInfo)
                                                                        <th class="text-end">{{ $compName }} ({{ \Carbon\Carbon::parse($compInfo['asOfDate'])->format('d-m-Y') }})</th>
                                                                        @endforeach
                                                                        <th class="text-end">Change</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @php $sumLiability = 0; $sumLiabilityPrev = 0; @endphp
                                                                    @foreach($financialReportData['chartAccountsLiabilities'] as $mainGroupName => $mainGroup)
                                                                    @if(isset($mainGroup['total']) && $mainGroup['total'] != 0)
                                                                    @php $colspan = 4 + count($comparativeData); @endphp
                                                                    <tr class="table-primary">
                                                                        <td colspan="{{ $colspan }}" class="fw-bold text-dark"><i class="bx bx-folder me-1"></i>{{ $mainGroupName }}</td>
                                                                    </tr>
                                                                    @if(isset($mainGroup['fslis']))
                                                                        @foreach($mainGroup['fslis'] as $fsliName => $fsli)
                                                                        @if(isset($fsli['total']) && $fsli['total'] != 0)
                                                                        @php $colspan = 4 + count($comparativeData); @endphp
                                                                        <tr class="table-light">
                                                                            <td colspan="{{ $colspan }}" class="ps-4 fw-medium text-dark">{{ $fsliName }}</td>
                                                                        </tr>
                                                                        @if($reportType === 'detailed' && isset($fsli['accounts']))
                                                                            @foreach($fsli['accounts'] as $chartAccountLiability)
                                                                            @if($chartAccountLiability['sum'] != 0)
                                                                            @php 
                                                                                $sumLiability += ($chartAccountLiability['sum'] ?? 0);
                                                                                $prevYearMainGroup = $previousYearData['chartAccountsLiabilities'][$mainGroupName] ?? [];
                                                                                $prevYearFslis = $prevYearMainGroup['fslis'] ?? [];
                                                                                $prevYearFsli = $prevYearFslis[$fsliName] ?? [];
                                                                                $prevYearAccounts = $prevYearFsli['accounts'] ?? [];
                                                                                $prevYearAccount = collect($prevYearAccounts)->firstWhere('account_id', $chartAccountLiability['account_id']);
                                                                                $prevYearAmount = ($prevYearAccount['sum'] ?? 0);
                                                                                $sumLiabilityPrev += $prevYearAmount;
                                                                                $change = ($chartAccountLiability['sum'] ?? 0) - $prevYearAmount;
                                                                                
                                                                                // Get comparative amounts
                                                                                $comparativeLiabilityAmounts = [];
                                                                                foreach ($comparativeData as $compName => $compInfo) {
                                                                                    $compMainGroup = $compInfo['data']['chartAccountsLiabilities'][$mainGroupName] ?? [];
                                                                                    $compFslis = $compMainGroup['fslis'] ?? [];
                                                                                    $compFsli = $compFslis[$fsliName] ?? [];
                                                                                    $compAccounts = $compFsli['accounts'] ?? [];
                                                                                    $compAccount = collect($compAccounts)->firstWhere('account_id', $chartAccountLiability['account_id']);
                                                                                    $comparativeLiabilityAmounts[$compName] = $compAccount['sum'] ?? 0;
                                                                                }
                                                                            @endphp
                                                                            <tr class="account-row">
                                                                                <td class="ps-5">
                                                                                    <a href="{{ route('accounting.transactions.doubleEntries', Hashids::encode($chartAccountLiability['account_id'])) }}"
                                                                                        class="text-decoration-none text-dark fw-medium">
                                                                                        <i class="bx bx-chevron-right me-1 text-dark"></i>
                                                                                        @if($chartAccountLiability['account_code'] ?? '')<span class="text-muted small">{{ $chartAccountLiability['account_code'] }} - </span>@endif
                                                                                        {{ $chartAccountLiability['account'] }}
                                                                                    </a>
                                                                                </td>
                                                                                <td class="text-end">
                                                                                    <a href="{{ route('accounting.transactions.doubleEntries', Hashids::encode($chartAccountLiability['account_id'])) }}"
                                                                                        class="text-decoration-none fw-bold text-dark">
                                                                                        {{ number_format($chartAccountLiability['sum'] ?? 0,2) }}
                                                                                    </a>
                                                                                </td>
                                                                                <td class="text-end text-dark">
                                                                                    {{ number_format($prevYearAmount,2) }}
                                                                                </td>
                                                                                @foreach($comparativeData as $compName => $compInfo)
                                                                                <td class="text-end text-dark">
                                                                                    {{ number_format($comparativeLiabilityAmounts[$compName] ?? 0,2) }}
                                                                                </td>
                                                                                @endforeach
                                                                                <td class="text-end">
                                                                                        {{ $change >= 0 ? '+' : '' }}{{ number_format($change,2) }}
                                                                                </td>
                                                                            </tr>
                                                                            @endif
                                                                            @endforeach
                                                                        @else
                                                                            @php 
                                                                                $fsliTotal = $fsli['total'] ?? 0;
                                                                                $sumLiability += $fsliTotal;
                                                                                $prevYearMainGroup = $previousYearData['chartAccountsLiabilities'][$mainGroupName] ?? [];
                                                                                $prevYearFslis = $prevYearMainGroup['fslis'] ?? [];
                                                                                $prevYearFsli = $prevYearFslis[$fsliName] ?? [];
                                                                                $prevYearFsliTotal = $prevYearFsli['total'] ?? 0;
                                                                                $sumLiabilityPrev += $prevYearFsliTotal;
                                                                                $change = $fsliTotal - $prevYearFsliTotal;
                                                                                
                                                                                // Get comparative totals
                                                                                $comparativeLiabilityTotals = [];
                                                                                foreach ($comparativeData as $compName => $compInfo) {
                                                                                    $compMainGroup = $compInfo['data']['chartAccountsLiabilities'][$mainGroupName] ?? [];
                                                                                    $compFslis = $compMainGroup['fslis'] ?? [];
                                                                                    $compFsli = $compFslis[$fsliName] ?? [];
                                                                                    $comparativeLiabilityTotals[$compName] = $compFsli['total'] ?? 0;
                                                                                }
                                                                            @endphp
                                                                            <tr class="account-row">
                                                                                <td class="ps-5 fw-medium">{{ $fsliName }}</td>
                                                                                <td class="text-end fw-bold">{{ number_format($fsliTotal,2) }}</td>
                                                                                <td class="text-end">{{ number_format($prevYearFsliTotal,2) }}</td>
                                                                                @foreach($comparativeData as $compName => $compInfo)
                                                                                <td class="text-end">{{ number_format($comparativeLiabilityTotals[$compName] ?? 0,2) }}</td>
                                                                                @endforeach
                                                                                <td class="text-end">{{ $change >= 0 ? '+' : '' }}{{ number_format($change,2) }}</td>
                                                                            </tr>
                                                                        @endif
                                                                        @endif
                                                                        @endforeach
                                                                    @endif
                                                                    @endif
                                                                    @endforeach
                                                                    @php 
                                                                        // Calculate comparative totals for liabilities
                                                                        $comparativeLiabilityTotals = [];
                                                                        foreach ($comparativeData as $compName => $compInfo) {
                                                                            $compTotal = 0;
                                                                            foreach ($compInfo['data']['chartAccountsLiabilities'] as $compMainGroup) {
                                                                                if (isset($compMainGroup['total'])) {
                                                                                    $compTotal += $compMainGroup['total'];
                                                                                }
                                                                            }
                                                                            $comparativeLiabilityTotals[$compName] = $compTotal;
                                                                        }
                                                                        $liabilityChange = $sumLiability - $sumLiabilityPrev;
                                                                        
                                                                        // Calculate comparative totals for equity & liability
                                                                        $comparativeEquityLiabilityTotals = [];
                                                                        foreach ($comparativeData as $compName => $compInfo) {
                                                                            $compLiabilityTotal = $comparativeLiabilityTotals[$compName] ?? 0;
                                                                            // Recalculate equity total for this comparative period
                                                                            $compEquityTotal = 0;
                                                                            foreach ($compInfo['data']['chartAccountsEquitys'] as $compMainGroup) {
                                                                                if (isset($compMainGroup['total'])) {
                                                                                    $compEquityTotal += $compMainGroup['total'];
                                                                                }
                                                                            }
                                                                            $compEquityTotal += ($compInfo['netProfitYtd'] ?? 0);
                                                                            $comparativeEquityLiabilityTotals[$compName] = $compLiabilityTotal + $compEquityTotal;
                                                                        }
                                                                        $totalChange = ($sumLiability + $sumEquity + ($netProfitYtd ?? 0)) - ($sumLiabilityPrev + $sumEquityPrev + $previousYearData['profitLoss']);
                                                                    @endphp
                                                                    <tr class="fw-bold">
                                                                        <td>TOTAL LIABILITIES</td>
                                                                        <td class="text-end">{{ number_format($sumLiability,2) }}</td>
                                                                        <td class="text-end">{{ number_format($sumLiabilityPrev,2) }}</td>
                                                                        @foreach($comparativeData as $compName => $compInfo)
                                                                        <td class="text-end">{{ number_format($comparativeLiabilityTotals[$compName] ?? 0,2) }}</td>
                                                                        @endforeach
                                                                        <td class="text-end">{{ $liabilityChange >= 0 ? '+' : '' }}{{ number_format($liabilityChange,2) }}</td>
                                                                    </tr>
                                                                    <tr class="table-secondary fw-bold">
                                                                        <td>TOTAL EQUITY & LIABILITY</td>
                                                                        <td class="text-end">{{ number_format($sumLiability + $sumEquity + ($netProfitYtd ?? 0),2) }}</td>
                                                                        <td class="text-end">{{ number_format($sumLiabilityPrev + $sumEquityPrev + $previousYearData['profitLoss'],2) }}</td>
                                                                        @foreach($comparativeData as $compName => $compInfo)
                                                                        <td class="text-end">{{ number_format($comparativeEquityLiabilityTotals[$compName] ?? 0,2) }}</td>
                                                                        @endforeach
                                                                        <td class="text-end">{{ $totalChange >= 0 ? '+' : '' }}{{ number_format($totalChange,2) }}</td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
function addComparative(){
    const container = document.getElementById('comparatives_container');
    const idx = container.querySelectorAll('.comparative-row').length;
    const row = document.createElement('div');
    row.className = 'row g-2 align-items-end mb-2 comparative-row';
    row.innerHTML = `
        <div class="col-md-4">
            <label class="form-label">Name</label>
            <input type="text" class="form-control" name="comparative_dates[${idx}][name]" placeholder="e.g. Previous Quarter">
        </div>
        <div class="col-md-6">
            <label class="form-label">As of Date</label>
            <input type="date" class="form-control" name="comparative_dates[${idx}][as_of_date]" required>
        </div>
        <div class="col-md-2 text-end">
            <button type="button" class="btn btn-outline-danger" onclick="this.closest('.comparative-row').remove()">
                <i class="bx bx-trash"></i> Remove
            </button>
        </div>
    `;
    container.appendChild(row);
}
</script>
@endsection

