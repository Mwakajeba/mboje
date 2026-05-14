@extends('layouts.main')

@section('title', 'Income Statement Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Income Statement Report', 'url' => '#', 'icon' => 'bx bx-line-chart']
        ]" />
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-line-chart me-2"></i>Income Statement Report
                            </h4>
                        </div>

                        <!-- Filters -->
                        <form id="incomeStatementForm" method="GET" action="{{ route('accounting.reports.income-statement') }}" class="row g-3 mb-4">
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
                                <select class="form-select" id="reporting_type" name="reporting_type" required>
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
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="bx bx-search me-1"></i>Filter
                                </button>
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
                        </form>

                        <!-- Export Options -->
                        @if(isset($incomeStatementData))
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
                        @if(isset($incomeStatementData))
                        @php
                            $comparatives = $incomeStatementData['comparative'] ?? [];
                            $comparativesCount = is_array($comparatives) ? count($comparatives) : 0;
                            
                            // Calculate summary stats
                            $totalRevenue = $incomeStatementData['data']['total_revenue'] ?? 0;
                            $totalExpenses = abs($incomeStatementData['data']['total_expenses'] ?? 0);
                            $netIncome = $incomeStatementData['data']['profit_loss'] ?? 0;
                        @endphp

                        <!-- Summary Cards -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Total Revenue</h5>
                                        <h3 class="mb-0">{{ number_format($totalRevenue, 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border border-danger">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-danger">Total Expenses</h5>
                                        <h3 class="mb-0">{{ number_format($totalExpenses, 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border {{ $netIncome >= 0 ? 'border-success' : 'border-danger' }}">
                                    <div class="card-body text-center">
                                        <h5 class="card-title {{ $netIncome >= 0 ? 'text-success' : 'text-danger' }}">Net Income</h5>
                                        <h3 class="mb-0 {{ $netIncome >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($netIncome, 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Account</th>
                                        <th class="text-end">Current Period</th>
                                        @if($comparativesCount)
                                            @foreach($comparatives as $label => $comp)
                                                <th class="text-end">{{ $label }}</th>
                                            @endforeach
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Revenue Section -->
                                    <tr class="table-primary">
                                        <td colspan="{{ 1 + $comparativesCount + 1 }}" class="fw-bold text-dark">
                                            <i class="bx bx-trending-up me-1"></i>REVENUE
                                        </td>
                                    </tr>
                                    @php
                                        $revenueTotalCurrent = 0;
                                        $compRevenueTotals = [];
                                    @endphp
                                    @foreach($incomeStatementData['data']['revenues'] as $mainGroupName => $mainGroup)
                                        @if(isset($mainGroup['total']) && $mainGroup['total'] != 0)
                                            <tr class="table-secondary">
                                                <td colspan="{{ 1 + $comparativesCount + 1 }}" class="fw-bold text-dark ps-3">
                                                    <i class="bx bx-folder me-1"></i>{{ $mainGroupName }}
                                                </td>
                                            </tr>
                                            @if(isset($mainGroup['fslis']))
                                                @foreach($mainGroup['fslis'] as $fsliName => $fsli)
                                                    @if(isset($fsli['total']) && $fsli['total'] != 0)
                                                        <tr class="table-light">
                                                            <td colspan="{{ 1 + $comparativesCount + 1 }}" class="ps-4 fw-medium text-dark">
                                                                {{ $fsliName }}
                                                            </td>
                                                        </tr>
                                                        @if(isset($fsli['accounts']))
                                                            @foreach($fsli['accounts'] as $account)
                                                                @if($account['sum'] != 0)
                                                                    @php
                                                                        $revenueTotalCurrent += $account['sum'];
                                                                        $rowComps = [];
                                                                        foreach ($comparatives as $label => $cdata) {
                                                                            $prevMainGroup = $cdata['revenues'][$mainGroupName] ?? [];
                                                                            $prevFslis = $prevMainGroup['fslis'] ?? [];
                                                                            $prevFsli = $prevFslis[$fsliName] ?? [];
                                                                            $prevAccounts = $prevFsli['accounts'] ?? [];
                                                                            $prev = collect($prevAccounts)->firstWhere('account_id', $account['account_id'])['sum'] ?? 0;
                                                                            $rowComps[$label] = $prev;
                                                                            $compRevenueTotals[$label] = ($compRevenueTotals[$label] ?? 0) + $prev;
                                                                        }
                                                                    @endphp
                                                                    <tr>
                                                                        <td class="ps-5">
                                                                            @if($account['account_code'] ?? '')
                                                                                <span class="text-muted small">{{ $account['account_code'] }} - </span>
                                                                            @endif
                                                                            {{ $account['account'] }}
                                                                        </td>
                                                                        <td class="text-end">{{ number_format($account['sum'], 2) }}</td>
                                                                        @if($comparativesCount)
                                                                            @foreach($comparatives as $label => $ignored)
                                                                                <td class="text-end">{{ number_format($rowComps[$label] ?? 0, 2) }}</td>
                                                                            @endforeach
                                                                        @endif
                                                                    </tr>
                                                                @endif
                                                            @endforeach
                                                        @endif
                                                        <tr class="table-light">
                                                            <td class="ps-4 fw-medium text-dark">Total {{ $fsliName }}</td>
                                                            <td class="text-end fw-medium">{{ number_format($fsli['total'] ?? 0, 2) }}</td>
                                                            @if($comparativesCount)
                                                                @foreach($comparatives as $label => $ignored)
                                                                    @php
                                                                        $prevMainGroup = $comparatives[$label]['revenues'][$mainGroupName] ?? [];
                                                                        $prevFslis = $prevMainGroup['fslis'] ?? [];
                                                                        $prevFsli = $prevFslis[$fsliName] ?? [];
                                                                        $fsliTotal = $prevFsli['total'] ?? 0;
                                                                    @endphp
                                                                    <td class="text-end fw-medium">{{ number_format($fsliTotal, 2) }}</td>
                                                                @endforeach
                                                            @endif
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            @endif
                                            <tr class="table-secondary">
                                                <td class="ps-3 fw-bold">Total {{ $mainGroupName }}</td>
                                                <td class="text-end fw-bold">{{ number_format($mainGroup['total'] ?? 0, 2) }}</td>
                                                @if($comparativesCount)
                                                    @foreach($comparatives as $label => $ignored)
                                                        @php
                                                            $prevMainGroup = $comparatives[$label]['revenues'][$mainGroupName] ?? [];
                                                            $mainGroupTotal = $prevMainGroup['total'] ?? 0;
                                                        @endphp
                                                        <td class="text-end fw-bold">{{ number_format($mainGroupTotal, 2) }}</td>
                                                    @endforeach
                                                @endif
                                            </tr>
                                        @endif
                                    @endforeach
                                    <tr class="table-primary">
                                        <td class="fw-bold">Total Revenue</td>
                                        <td class="text-end fw-bold">{{ number_format($revenueTotalCurrent, 2) }}</td>
                                        @if($comparativesCount)
                                            @foreach($comparatives as $label => $ignored)
                                                <td class="text-end fw-bold">{{ number_format($compRevenueTotals[$label] ?? 0, 2) }}</td>
                                            @endforeach
                                        @endif
                                    </tr>

                                    <!-- Expense Section -->
                                    <tr class="table-danger">
                                        <td colspan="{{ 1 + $comparativesCount + 1 }}" class="fw-bold text-dark">
                                            <i class="bx bx-trending-down me-1"></i>EXPENSES
                                        </td>
                                    </tr>
                                    @php
                                        $expenseTotalCurrent = 0;
                                        $compExpenseTotals = [];
                                    @endphp
                                    @foreach($incomeStatementData['data']['expenses'] as $mainGroupName => $mainGroup)
                                        @if(isset($mainGroup['total']) && $mainGroup['total'] != 0)
                                            <tr class="table-secondary">
                                                <td colspan="{{ 1 + $comparativesCount + 1 }}" class="fw-bold text-dark ps-3">
                                                    <i class="bx bx-folder me-1"></i>{{ $mainGroupName }}
                                                </td>
                                            </tr>
                                            @if(isset($mainGroup['fslis']))
                                                @foreach($mainGroup['fslis'] as $fsliName => $fsli)
                                                    @if(isset($fsli['total']) && $fsli['total'] != 0)
                                                        <tr class="table-light">
                                                            <td colspan="{{ 1 + $comparativesCount + 1 }}" class="ps-4 fw-medium text-dark">
                                                                {{ $fsliName }}
                                                            </td>
                                                        </tr>
                                                        @if(isset($fsli['accounts']))
                                                            @foreach($fsli['accounts'] as $account)
                                                                @if($account['sum'] != 0)
                                                                    @php
                                                                        $expenseTotalCurrent += abs($account['sum']);
                                                                        $rowComps = [];
                                                                        foreach ($comparatives as $label => $cdata) {
                                                                            $prevMainGroup = $cdata['expenses'][$mainGroupName] ?? [];
                                                                            $prevFslis = $prevMainGroup['fslis'] ?? [];
                                                                            $prevFsli = $prevFslis[$fsliName] ?? [];
                                                                            $prevAccounts = $prevFsli['accounts'] ?? [];
                                                                            $prev = abs(collect($prevAccounts)->firstWhere('account_id', $account['account_id'])['sum'] ?? 0);
                                                                            $rowComps[$label] = $prev;
                                                                            $compExpenseTotals[$label] = ($compExpenseTotals[$label] ?? 0) + $prev;
                                                                        }
                                                                    @endphp
                                                                    <tr>
                                                                        <td class="ps-5">
                                                                            @if($account['account_code'] ?? '')
                                                                                <span class="text-muted small">{{ $account['account_code'] }} - </span>
                                                                            @endif
                                                                            {{ $account['account'] }}
                                                                        </td>
                                                                        <td class="text-end">{{ number_format(abs($account['sum']), 2) }}</td>
                                                                        @if($comparativesCount)
                                                                            @foreach($comparatives as $label => $ignored)
                                                                                <td class="text-end">{{ number_format($rowComps[$label] ?? 0, 2) }}</td>
                                                                            @endforeach
                                                                        @endif
                                                                    </tr>
                                                                @endif
                                                            @endforeach
                                                        @endif
                                                        <tr class="table-light">
                                                            <td class="ps-4 fw-medium text-dark">Total {{ $fsliName }}</td>
                                                            <td class="text-end fw-medium">{{ number_format(abs($fsli['total'] ?? 0), 2) }}</td>
                                                            @if($comparativesCount)
                                                                @foreach($comparatives as $label => $ignored)
                                                                    @php
                                                                        $prevMainGroup = $comparatives[$label]['expenses'][$mainGroupName] ?? [];
                                                                        $prevFslis = $prevMainGroup['fslis'] ?? [];
                                                                        $prevFsli = $prevFslis[$fsliName] ?? [];
                                                                        $fsliTotal = abs($prevFsli['total'] ?? 0);
                                                                    @endphp
                                                                    <td class="text-end fw-medium">{{ number_format($fsliTotal, 2) }}</td>
                                                                @endforeach
                                                            @endif
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            @endif
                                            <tr class="table-secondary">
                                                <td class="ps-3 fw-bold">Total {{ $mainGroupName }}</td>
                                                <td class="text-end fw-bold">{{ number_format(abs($mainGroup['total'] ?? 0), 2) }}</td>
                                                @if($comparativesCount)
                                                    @foreach($comparatives as $label => $ignored)
                                                        @php
                                                            $prevMainGroup = $comparatives[$label]['expenses'][$mainGroupName] ?? [];
                                                            $mainGroupTotal = abs($prevMainGroup['total'] ?? 0);
                                                        @endphp
                                                        <td class="text-end fw-bold">{{ number_format($mainGroupTotal, 2) }}</td>
                                                    @endforeach
                                                @endif
                                            </tr>
                                        @endif
                                    @endforeach
                                    <tr class="table-danger">
                                        <td class="fw-bold">Total Expenses</td>
                                        <td class="text-end fw-bold">{{ number_format($expenseTotalCurrent, 2) }}</td>
                                        @if($comparativesCount)
                                            @foreach($comparatives as $label => $ignored)
                                                <td class="text-end fw-bold">{{ number_format($compExpenseTotals[$label] ?? 0, 2) }}</td>
                                            @endforeach
                                        @endif
                                    </tr>

                                    <!-- Net Income -->
                                    <tr class="table-{{ $netIncome >= 0 ? 'success' : 'danger' }}">
                                        <td class="fw-bold">Net Income</td>
                                        @php $netCurrent = $revenueTotalCurrent - $expenseTotalCurrent; @endphp
                                        <td class="text-end fw-bold {{ $netCurrent >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($netCurrent, 2) }}
                                        </td>
                                        @if($comparativesCount)
                                            @foreach($comparatives as $label => $ignored)
                                                @php $netComp = ($compRevenueTotals[$label] ?? 0) - ($compExpenseTotals[$label] ?? 0); @endphp
                                                <td class="text-end fw-bold {{ $netComp >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ number_format($netComp, 2) }}
                                                </td>
                                            @endforeach
                                        @endif
                                    </tr>
                                </tbody>
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
    document.getElementById('incomeStatementForm').submit();
}

function exportReport(type) {
    const form = document.getElementById('incomeStatementForm');
    const formData = new FormData(form);
    formData.append('export_type', type);
    
    const url = '{{ route("accounting.reports.income-statement.export") }}?' + new URLSearchParams(formData);
    
    // Show loading state
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
@endsection
