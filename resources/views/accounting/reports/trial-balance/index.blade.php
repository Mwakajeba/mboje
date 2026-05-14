@extends('layouts.main')

@section('title', 'Trial Balance Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Trial Balance Report', 'url' => '#', 'icon' => 'bx bx-calculator']
        ]" />
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-calculator me-2"></i>Trial Balance Report
                            </h4>
                        </div>

                        <!-- Filters -->
                        <form id="trialBalanceForm" method="GET" action="{{ route('accounting.reports.trial-balance') }}" class="row g-3 mb-4">
                            <div class="col-md-2">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}" required>
                            </div>

                            <div class="col-md-2">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}" required>
                            </div>

                            <div class="col-md-2">
                                <label for="reporting_type" class="form-label">Reporting Type</label>
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
                                <label for="layout" class="form-label">Layout</label>
                                <select class="form-select" id="layout" name="layout">
                                    <option value="single_column" {{ $layout === 'single_column' ? 'selected' : '' }}>Single Column</option>
                                    <option value="double_column" {{ $layout === 'double_column' ? 'selected' : '' }}>Double Column</option>
                                    <option value="multi_column" {{ $layout === 'multi_column' ? 'selected' : '' }}>Multiple Columns</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="level_of_detail" class="form-label">Level of Detail</label>
                                <select class="form-select" id="level_of_detail" name="level_of_detail">
                                    <option value="summary" {{ $levelOfDetail === 'summary' ? 'selected' : '' }}>Summary</option>
                                    <option value="detailed" {{ $levelOfDetail === 'detailed' ? 'selected' : '' }}>Detailed</option>
                                </select>
                            </div>

                            <!-- Comparative Period (optional) -->
                            <div class="col-12">
                                <div class="card border border-info">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <h6 class="card-title mb-0 text-info">
                                                <i class="bx bx-calendar me-2"></i>Comparative Periods (optional)
                                            </h6>
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addComparative()">
                                                <i class="bx bx-plus"></i> Add Comparative
                                            </button>
                                        </div>
                                        <div id="comparatives_container">
                                            @if(!empty($comparativeColumns))
                                                @foreach($comparativeColumns as $idx => $col)
                                                    <div class="row g-2 align-items-end mb-2 comparative-row">
                                                        <div class="col-md-3">
                                                            <label class="form-label">Name</label>
                                                            <input type="text" class="form-control" name="comparative_columns[{{ $idx }}][name]" value="{{ $col['name'] ?? ('Comparative '.($idx+1)) }}" placeholder="e.g. Previous Period">
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label">Start Date</label>
                                                            <input type="date" class="form-control" name="comparative_columns[{{ $idx }}][start_date]" value="{{ $col['start_date'] ?? '' }}">
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label">End Date</label>
                                                            <input type="date" class="form-control" name="comparative_columns[{{ $idx }}][end_date]" value="{{ $col['end_date'] ?? '' }}">
                                                        </div>
                                                        <div class="col-md-3 text-end">
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
                                <a href="{{ route('accounting.reports.trial-balance') }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-reset me-1"></i> Reset
                                </a>
                            </div>
                        </form>

                        <!-- Export Options -->
                        @if(isset($trialBalanceData))
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-danger" onclick="exportReport('pdf')">
                                        <i class="bx bx-file-pdf me-1"></i>Export PDF
                                    </button>
                                    <button type="button" class="btn btn-success" onclick="exportReport('excel')">
                                        <i class="bx bx-file me-1"></i>Export Excel
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Report Results -->
                        @if(isset($trialBalanceData))
                        @php
                            $comparatives = $trialBalanceData['comparative'] ?? [];
                            $comparativesCount = is_array($comparatives) ? count($comparatives) : 0;
                            
                            // Calculate summary stats
                            $totalDebit = 0;
                            $totalCredit = 0;
                            $totalOpening = 0;
                            $totalChange = 0;
                            $totalClosing = 0;
                            
                            foreach ($trialBalanceData['data'] as $class => $accounts) {
                                foreach ($accounts as $account) {
                                    if ($layout === 'multi_column') {
                                        $openingDr = property_exists($account, 'opening_debit') ? floatval($account->opening_debit) : 0;
                                        $openingCr = property_exists($account, 'opening_credit') ? floatval($account->opening_credit) : 0;
                                        $changeDr = property_exists($account, 'change_debit') ? floatval($account->change_debit) : 0;
                                        $changeCr = property_exists($account, 'change_credit') ? floatval($account->change_credit) : 0;
                                        $closingDr = property_exists($account, 'closing_debit') ? floatval($account->closing_debit) : 0;
                                        $closingCr = property_exists($account, 'closing_credit') ? floatval($account->closing_credit) : 0;
                                        
                                        $totalOpening += ($openingDr - $openingCr);
                                        $totalChange += ($changeDr - $changeCr);
                                        $totalClosing += ($closingDr - $closingCr);
                                    } else {
                                        $sumVal = isset($account->sum) ? floatval($account->sum) : 0;
                                        if ($sumVal != 0) {
                                            if ($layout === 'double_column') {
                                                $isCredit = isset($account->nature) ? ($account->nature === 'credit') : ($sumVal < 0);
                                                $currentDebit = $isCredit ? 0 : abs($sumVal);
                                                $currentCredit = $isCredit ? abs($sumVal) : 0;
                                                $totalDebit += $currentDebit;
                                                $totalCredit += $currentCredit;
                                            } else {
                                                if ($sumVal < 0) {
                                                    $totalCredit += abs($sumVal);
                                                } else {
                                                    $totalDebit += $sumVal;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            
                            $netBalance = $totalDebit - $totalCredit;
                            if ($layout === 'multi_column') {
                                $netBalance = $totalClosing;
                            }
                        @endphp

                        <!-- Summary Cards -->
                        @if($layout === 'multi_column')
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">Opening Balance</h5>
                                        <h3 class="mb-0">{{ number_format($totalOpening, 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Current Change</h5>
                                        <h3 class="mb-0">{{ number_format($totalChange, 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Closing Balance</h5>
                                        <h3 class="mb-0">{{ number_format($totalClosing, 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border {{ $netBalance == 0 ? 'border-success' : 'border-danger' }}">
                                    <div class="card-body text-center">
                                        <h5 class="card-title {{ $netBalance == 0 ? 'text-success' : 'text-danger' }}">Net Balance</h5>
                                        <h3 class="mb-0 {{ $netBalance == 0 ? 'text-success' : 'text-danger' }}">{{ number_format($netBalance, 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">Total Debit</h5>
                                        <h3 class="mb-0">{{ number_format($totalDebit, 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Total Credit</h5>
                                        <h3 class="mb-0">{{ number_format($totalCredit, 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border {{ $netBalance == 0 ? 'border-success' : 'border-danger' }}">
                                    <div class="card-body text-center">
                                        <h5 class="card-title {{ $netBalance == 0 ? 'text-success' : 'text-danger' }}">Net Balance</h5>
                                        <h3 class="mb-0 {{ $netBalance == 0 ? 'text-success' : 'text-danger' }}">{{ number_format($netBalance, 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                @if($layout === 'double_column')
                                    <thead class="table-light">
                                        <tr>
                                            <th>Account Name</th>
                                            <th>Account Code</th>
                                            <th class="text-end">Debit</th>
                                            <th class="text-end">Credit</th>
                                            @if($comparativesCount)
                                                @foreach($comparatives as $label => $comp)
                                                    <th class="text-end">Debit ({{ $label }})</th>
                                                    <th class="text-end">Credit ({{ $label }})</th>
                                                @endforeach
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $totalDebit = 0;
                                            $totalCredit = 0;
                                            $compTotals = [];
                                        @endphp
                                        @foreach($trialBalanceData['data'] as $class => $accounts)
                                            @foreach($accounts as $account)
                                                @if(floatval($account->sum) != 0)
                                                    @php
                                                        $currentDebit = $account->nature === 'debit' ? abs(floatval($account->sum)) : 0;
                                                        $currentCredit = $account->nature === 'credit' ? abs(floatval($account->sum)) : 0;
                                                        $totalDebit += $currentDebit;
                                                        $totalCredit += $currentCredit;
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $account->account }}</td>
                                                        <td class="account-code">{{ $account->account_code }}</td>
                                                        <td class="text-end">{{ $currentDebit ? number_format($currentDebit, 2) : '-' }}</td>
                                                        <td class="text-end">{{ $currentCredit ? number_format($currentCredit, 2) : '-' }}</td>
                                                        @if($comparativesCount)
                                                            @foreach($comparatives as $label => $compData)
                                                                @php
                                                                    $compAccount = collect($compData[$class] ?? [])->first(function($a) use ($account) {
                                                                        return isset($a->account_code) && $a->account_code == $account->account_code;
                                                                    });
                                                                    $compDebit = $compAccount && $compAccount->nature === 'debit' ? abs(floatval($compAccount->sum)) : 0;
                                                                    $compCredit = $compAccount && $compAccount->nature === 'credit' ? abs(floatval($compAccount->sum)) : 0;
                                                                    $compTotals[$label]['debit'] = ($compTotals[$label]['debit'] ?? 0) + $compDebit;
                                                                    $compTotals[$label]['credit'] = ($compTotals[$label]['credit'] ?? 0) + $compCredit;
                                                                @endphp
                                                                <td class="text-end">{{ $compDebit ? number_format($compDebit, 2) : '-' }}</td>
                                                                <td class="text-end">{{ $compCredit ? number_format($compCredit, 2) : '-' }}</td>
                                                            @endforeach
                                                        @endif
                                                    </tr>
                                                @endif
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="2" class="text-end">TOTAL</th>
                                            <th class="text-end">{{ number_format($totalDebit, 2) }}</th>
                                            <th class="text-end">{{ number_format($totalCredit, 2) }}</th>
                                            @if($comparativesCount)
                                                @foreach($comparatives as $label => $ignored)
                                                    <th class="text-end">{{ number_format($compTotals[$label]['debit'] ?? 0, 2) }}</th>
                                                    <th class="text-end">{{ number_format($compTotals[$label]['credit'] ?? 0, 2) }}</th>
                                                @endforeach
                                            @endif
                                        </tr>
                                        <tr>
                                            <th colspan="{{ 2 + ($comparativesCount ? ($comparativesCount*2) : 0) }}" class="text-end">Net Balance (Debit - Credit)</th>
                                            <th class="text-end {{ ($totalDebit - $totalCredit) == 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($totalDebit - $totalCredit, 2) }}
                                            </th>
                                        </tr>
                                    </tfoot>
                                @elseif($layout === 'single_column')
                                    <thead class="table-light">
                                        <tr>
                                            <th>Account Name</th>
                                            <th>Account Code</th>
                                            <th class="text-end">Balance</th>
                                            @if($comparativesCount)
                                                @foreach($comparatives as $label => $comp)
                                                    <th class="text-end">Balance ({{ $label }})</th>
                                                @endforeach
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $totalDebit = 0;
                                            $totalCredit = 0;
                                            $compTotals = [];
                                        @endphp
                                        @foreach($trialBalanceData['data'] as $class => $accounts)
                                            @foreach($accounts as $account)
                                                @if(floatval($account->sum) != 0)
                                                    @php
                                                        $sumVal = floatval($account->sum);
                                                        if ($account->nature === 'credit') {
                                                            $totalCredit += abs($sumVal);
                                                        } else {
                                                            $totalDebit += abs($sumVal);
                                                        }
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $account->account }}</td>
                                                        <td class="account-code">{{ $account->account_code }}</td>
                                                        <td class="text-end">
                                                            @if($account->nature === 'credit')
                                                                ({{ number_format(abs(floatval($account->sum)), 2) }})
                                                            @else
                                                                {{ number_format(abs(floatval($account->sum)), 2) }}
                                                            @endif
                                                        </td>
                                                        @if($comparativesCount)
                                                            @foreach($comparatives as $label => $compData)
                                                                @php
                                                                    $compAccount = collect($compData[$class] ?? [])->first(function($a) use ($account) {
                                                                        return isset($a->account_code) && $a->account_code == $account->account_code;
                                                                    });
                                                                    $compVal = 0;
                                                                    if ($compAccount) {
                                                                        if ($compAccount->nature === 'credit') {
                                                                            $compVal = -1 * abs(floatval($compAccount->sum));
                                                                        } else {
                                                                            $compVal = abs(floatval($compAccount->sum));
                                                                        }
                                                                    }
                                                                    $compTotals[$label] = ($compTotals[$label] ?? 0) + $compVal;
                                                                @endphp
                                                                <td class="text-end">{{ $compVal < 0 ? '('.number_format(abs($compVal), 2).')' : number_format($compVal, 2) }}</td>
                                                            @endforeach
                                                        @endif
                                                    </tr>
                                                @endif
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="2" class="text-end">Net Balance (Debit - Credit)</th>
                                            <th class="text-end {{ ($totalDebit - $totalCredit) == 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($totalDebit - $totalCredit, 2) }}
                                            </th>
                                            @if($comparativesCount)
                                                @foreach($comparatives as $label => $ignored)
                                                    @php $tv = $compTotals[$label] ?? 0; @endphp
                                                    <th class="text-end {{ $tv == 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ $tv < 0 ? '('.number_format(abs($tv), 2).')' : number_format($tv, 2) }}
                                                    </th>
                                                @endforeach
                                            @endif
                                        </tr>
                                    </tfoot>
                                @else
                                    <thead class="table-light">
                                        <tr>
                                            <th>Account Name</th>
                                            <th>Account Code</th>
                                            <th class="text-end">Opening Balance</th>
                                            <th class="text-end">Current Year Change</th>
                                            <th class="text-end">Closing Balance</th>
                                            <th class="text-end">Difference</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $totalOpening = 0;
                                            $totalChange = 0;
                                            $totalClosing = 0;
                                            $totalDiff = 0;
                                        @endphp
                                        @foreach($trialBalanceData['data'] as $class => $accounts)
                                            @foreach($accounts as $account)
                                                @php
                                                    $openingDr = property_exists($account, 'opening_debit') ? floatval($account->opening_debit) : 0;
                                                    $openingCr = property_exists($account, 'opening_credit') ? floatval($account->opening_credit) : 0;
                                                    $changeDr  = property_exists($account, 'change_debit') ? floatval($account->change_debit) : 0;
                                                    $changeCr  = property_exists($account, 'change_credit') ? floatval($account->change_credit) : 0;
                                                    $closingDr = property_exists($account, 'closing_debit') ? floatval($account->closing_debit) : 0;
                                                    $closingCr = property_exists($account, 'closing_credit') ? floatval($account->closing_credit) : 0;
                                        
                                                    // Calculate net balances (Debit - Credit), credits will be negative
                                                    $openingBalance = $openingDr - $openingCr;
                                                    $changeBalance = $changeDr - $changeCr;
                                                    $closingBalance = $closingDr - $closingCr;
                                                    $difference = $closingBalance;
                                        
                                                    $totalOpening += $openingBalance;
                                                    $totalChange += $changeBalance;
                                                    $totalClosing += $closingBalance;
                                                    $totalDiff += $difference;
                                                @endphp
                                                @if($openingBalance != 0 || $changeBalance != 0 || $closingBalance != 0)
                                                    <tr>
                                                        <td>{{ $account->account }}</td>
                                                        <td class="account-code">{{ $account->account_code }}</td>
                                                        <td class="text-end">{{ $openingBalance != 0 ? number_format($openingBalance, 2) : '-' }}</td>
                                                        <td class="text-end">{{ $changeBalance != 0 ? number_format($changeBalance, 2) : '-' }}</td>
                                                        <td class="text-end">{{ $closingBalance != 0 ? number_format($closingBalance, 2) : '-' }}</td>
                                                        <td class="text-end">{{ $difference != 0 ? number_format($difference, 2) : '-' }}</td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="2" class="text-end">TOTAL</th>
                                            <th class="text-end">{{ number_format($totalOpening, 2) }}</th>
                                            <th class="text-end">{{ number_format($totalChange, 2) }}</th>
                                            <th class="text-end">{{ number_format($totalClosing, 2) }}</th>
                                            <th class="text-end">{{ number_format($totalDiff, 2) }}</th>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
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
    const form = document.getElementById('trialBalanceForm');
    form.submit();
}

function exportReport(type) {
    const form = document.getElementById('trialBalanceForm');
    const formData = new FormData(form);
    formData.append('export_type', type);
    
    const url = '{{ route("accounting.reports.trial-balance.export") }}?' + new URLSearchParams(formData);
    
    // Show simple loading message
    const loadingMsg = document.createElement('div');
    loadingMsg.innerHTML = 'Generating ' + type.toUpperCase() + ' report...';
    loadingMsg.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#333;color:white;padding:20px;border-radius:5px;z-index:9999;';
    document.body.appendChild(loadingMsg);
    
    // Download the file
    window.location.href = url;

    // Remove loading message after a short delay
    setTimeout(() => {
        if (loadingMsg.parentNode) {
            loadingMsg.parentNode.removeChild(loadingMsg);
        }
    }, 2000);
}

function addComparative(){
    const container = document.getElementById('comparatives_container');
    const idx = container.querySelectorAll('.comparative-row').length;
    const row = document.createElement('div');
    row.className = 'row g-2 align-items-end mb-2 comparative-row';
    row.innerHTML = `
        <div class="col-md-3">
            <label class="form-label">Name</label>
            <input type="text" class="form-control" name="comparative_columns[${idx}][name]" placeholder="e.g. Previous Period">
        </div>
        <div class="col-md-3">
            <label class="form-label">Start Date</label>
            <input type="date" class="form-control" name="comparative_columns[${idx}][start_date]">
        </div>
        <div class="col-md-3">
            <label class="form-label">End Date</label>
            <input type="date" class="form-control" name="comparative_columns[${idx}][end_date]">
        </div>
        <div class="col-md-3 text-end">
            <button type="button" class="btn btn-outline-danger" onclick="this.closest('.comparative-row').remove()">
                <i class="bx bx-trash"></i> Remove
            </button>
        </div>
    `;
    container.appendChild(row);
}
</script>

<style>
.account-code {
    font-family: monospace;
    font-size: 0.9em;
    color: #666;
}
</style>
@endsection
