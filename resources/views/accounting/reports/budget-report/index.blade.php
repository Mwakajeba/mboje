@extends('layouts.main')

@section('title', 'Budget Report')

@push('styles')
<style>
    .achievement-badge {
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 4px;
        transition: all 0.3s ease;
    }
    
    .achievement-excellent {
        background-color: #155724 !important;
        color: white !important;
        box-shadow: 0 2px 4px rgba(21, 87, 36, 0.3);
    }
    
    .achievement-very-good {
        background-color: #28a745 !important;
        color: white !important;
        box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
    }
    
    .achievement-good {
        background-color: #5cb85c !important;
        color: white !important;
        box-shadow: 0 2px 4px rgba(92, 184, 92, 0.3);
    }
    
    .achievement-fair {
        background-color: #7cb342 !important;
        color: white !important;
        box-shadow: 0 2px 4px rgba(124, 179, 66, 0.3);
    }
    
    .achievement-average {
        background-color: #ffc107 !important;
        color: #000 !important;
        box-shadow: 0 2px 4px rgba(255, 193, 7, 0.4);
        font-weight: 700;
    }
    
    .achievement-poor {
        background-color: #ff9800 !important;
        color: white !important;
        box-shadow: 0 2px 4px rgba(255, 152, 0, 0.3);
    }
    
    .achievement-very-poor {
        background-color: #dc3545 !important;
        color: white !important;
        box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
    }
    
    .achievement-badge:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .border-primary { border-color: #0d6efd !important; }
    .border-success { border-color: #198754 !important; }
    .border-info { border-color: #0dcaf0 !important; }
    .border-warning { border-color: #ffc107 !important; }
    .border-danger { border-color: #dc3545 !important; }
    .border-secondary { border-color: #6c757d !important; }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Budget Report', 'url' => '#', 'icon' => 'bx bx-chart']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-chart me-2"></i>Budget vs Actual Report
                            </h4>
                        </div>

                        <!-- Filters -->
                        <form method="GET" action="{{ route('accounting.reports.budget-report') }}" id="budgetReportForm" class="row g-3 mb-4">
                            <div class="col-md-2">
                                <label class="form-label">Year</label>
                                <select class="form-select" id="year" name="year">
                                    @for($y = date('Y') + 2; $y >= 2020; $y--)
                                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Budget</label>
                                <select class="form-select" id="budget_id" name="budget_id">
                                    <option value="">All Budgets</option>
                                    @foreach($availableBudgets as $budget)
                                        <option value="{{ $budget->id }}" {{ $budgetId == $budget->id ? 'selected' : '' }}>
                                            {{ $budget->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Branch</label>
                                <select class="form-select" id="branch_id" name="branch_id">
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Account Class</label>
                                <select class="form-select" id="account_class_id" name="account_class_id">
                                    <option value="">All Classes</option>
                                    @foreach($accountClasses as $class)
                                        <option value="{{ $class->id }}" {{ $accountClassId == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Account Group</label>
                                <select class="form-select" id="account_group_id" name="account_group_id">
                                    <option value="">All Groups</option>
                                    @foreach($accountGroups as $group)
                                        <option value="{{ $group->id }}" {{ $accountGroupId == $group->id ? 'selected' : '' }}>
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">All</option>
                                    <option value="Revenue" {{ $category == 'Revenue' ? 'selected' : '' }}>Revenue</option>
                                    <option value="Expense" {{ $category == 'Expense' ? 'selected' : '' }}>Expense</option>
                                    <option value="Capital Expenditure" {{ $category == 'Capital Expenditure' ? 'selected' : '' }}>Capital</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Date Type</label>
                                <select class="form-select" id="date_type" name="date_type">
                                    <option value="custom" {{ $dateType == 'custom' ? 'selected' : '' }}>Custom Range</option>
                                    <option value="monthly" {{ $dateType == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    <option value="quarterly" {{ $dateType == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                    <option value="yearly" {{ $dateType == 'yearly' ? 'selected' : '' }}>Yearly</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" 
                                       value="{{ $dateFrom }}" {{ $dateType != 'custom' ? 'readonly' : '' }}>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" 
                                       value="{{ $dateTo }}" {{ $dateType != 'custom' ? 'readonly' : '' }}>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="bx bx-search me-1"></i>Filter
                                </button>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">Variance Filter</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="show_only_over_budget" 
                                           name="show_only_over_budget" value="1" {{ $showOnlyOverBudget ? 'checked' : '' }}>
                                    <label class="form-check-label" for="show_only_over_budget">
                                        Over Budget Only
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="show_only_under_budget" 
                                           name="show_only_under_budget" value="1" {{ $showOnlyUnderBudget ? 'checked' : '' }}>
                                    <label class="form-check-label" for="show_only_under_budget">
                                        Under Budget Only
                                    </label>
                                </div>
                            </div>
                        </form>

                        <!-- Export Options -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <a href="{{ route('accounting.reports.budget-report.export-pdf') }}?{{ http_build_query(request()->all()) }}" 
                                       class="btn btn-danger">
                                        <i class="bx bx-file-pdf me-1"></i>Export PDF
                                    </a>
                                    <a href="{{ route('accounting.reports.budget-report.export') }}?{{ http_build_query(request()->all()) }}" 
                                       class="btn btn-success">
                                        <i class="bx bx-file me-1"></i>Export Excel
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Summary Cards -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">Total Budgeted</h5>
                                        <h3 class="mb-0">{{ number_format($budgetData['summary']['total_budgeted'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Total Actual</h5>
                                        <h3 class="mb-0">{{ number_format($budgetData['summary']['total_actual'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border {{ $budgetData['summary']['total_variance'] >= 0 ? 'border-success' : 'border-danger' }}">
                                    <div class="card-body text-center">
                                        <h5 class="card-title {{ $budgetData['summary']['total_variance'] >= 0 ? 'text-success' : 'text-danger' }}">Total Variance</h5>
                                        <h3 class="mb-0 {{ $budgetData['summary']['total_variance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($budgetData['summary']['total_variance'], 2) }} TZS
                                        </h3>
                                        <small class="text-muted">{{ $budgetData['summary']['variance_percentage'] }}%</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-secondary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-secondary">Total Accounts</h5>
                                        <h3 class="mb-0">{{ $budgetData['summary']['total_accounts'] }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Under Budget</h5>
                                        <h3 class="mb-0">{{ $budgetData['summary']['under_budget_count'] }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border border-danger">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-danger">Over Budget</h5>
                                        <h3 class="mb-0">{{ $budgetData['summary']['over_budget_count'] }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">On Budget</h5>
                                        <h3 class="mb-0">{{ $budgetData['summary']['on_budget_count'] }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="budgetReportTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Account Code</th>
                                        <th>Account Name</th>
                                        <th>Account Class</th>
                                        <th>Account Group</th>
                                        <th>Category</th>
                                        <th class="text-end">Budgeted Amount</th>
                                        <th class="text-end">Actual Amount</th>
                                        <th class="text-end">Variance</th>
                                        <th class="text-end">Variance %</th>
                                        <th class="text-end">Achievement</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($budgetData['items'] as $item)
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary">{{ $item->account_code }}</span>
                                            </td>
                                            <td>{{ $item->account_name }}</td>
                                            <td>{{ $item->account_class }}</td>
                                            <td>{{ $item->account_group }}</td>
                                            <td>
                                                @if($item->category == 'Revenue')
                                                    <span class="badge bg-success">{{ $item->category }}</span>
                                                @elseif($item->category == 'Expense')
                                                    <span class="badge bg-danger">{{ $item->category }}</span>
                                                @else
                                                    <span class="badge bg-warning">{{ $item->category }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end fw-bold">
                                                {{ number_format($item->budgeted_amount, 2) }}
                                            </td>
                                            <td class="text-end">
                                                {{ number_format($item->actual_amount, 2) }}
                                            </td>
                                            <td class="text-end fw-bold {{ $item->variance >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($item->variance, 2) }}
                                            </td>
                                            <td class="text-end">
                                                <span class="badge {{ $item->variance >= 0 ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $item->variance_percentage }}%
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                @php
                                                    $achievement = $item->achievement_percentage ?? 0;
                                                    $achievementClass = '';
                                                    $achievementBgColor = '';
                                                    $isExpense = $item->category == 'Expense';
                                                    
                                                    // For expenses: less than 50% is good (spending less), more than 50% is bad (spending more)
                                                    // For revenue: more than 50% is good (earning more), less than 50% is bad (earning less)
                                                    if ($isExpense) {
                                                        // Expense logic: lower is better
                                                        if ($achievement < 50) {
                                                            // Good - spending less than budget
                                                            if ($achievement < 30) {
                                                                $achievementClass = 'achievement-excellent';
                                                                $achievementBgColor = '#155724';
                                                            } elseif ($achievement < 40) {
                                                                $achievementClass = 'achievement-very-good';
                                                                $achievementBgColor = '#28a745';
                                                            } else {
                                                                $achievementClass = 'achievement-good';
                                                                $achievementBgColor = '#5cb85c';
                                                            }
                                                        } elseif ($achievement >= 45 && $achievement < 55) {
                                                            $achievementClass = 'achievement-average';
                                                            $achievementBgColor = '#ffc107';
                                                        } else {
                                                            // Bad - spending more than budget
                                                            if ($achievement >= 90) {
                                                                $achievementClass = 'achievement-very-poor';
                                                                $achievementBgColor = '#dc3545';
                                                            } elseif ($achievement >= 75) {
                                                                $achievementClass = 'achievement-poor';
                                                                $achievementBgColor = '#ff9800';
                                                            } else {
                                                                $achievementClass = 'achievement-poor';
                                                                $achievementBgColor = '#ff9800';
                                                            }
                                                        }
                                                    } else {
                                                        // Revenue logic: higher is better (original logic)
                                                        if ($achievement >= 50) {
                                                            if ($achievement >= 90) {
                                                                $achievementClass = 'achievement-excellent';
                                                                $achievementBgColor = '#155724';
                                                            } elseif ($achievement >= 75) {
                                                                $achievementClass = 'achievement-very-good';
                                                                $achievementBgColor = '#28a745';
                                                            } elseif ($achievement >= 60) {
                                                                $achievementClass = 'achievement-good';
                                                                $achievementBgColor = '#5cb85c';
                                                            } else {
                                                                $achievementClass = 'achievement-fair';
                                                                $achievementBgColor = '#7cb342';
                                                            }
                                                        } elseif ($achievement >= 45 && $achievement < 55) {
                                                            $achievementClass = 'achievement-average';
                                                            $achievementBgColor = '#ffc107';
                                                        } else {
                                                            if ($achievement >= 30) {
                                                                $achievementClass = 'achievement-poor';
                                                                $achievementBgColor = '#ff9800';
                                                            } else {
                                                                $achievementClass = 'achievement-very-poor';
                                                                $achievementBgColor = '#dc3545';
                                                            }
                                                        }
                                                    }
                                                @endphp
                                                <span class="badge achievement-badge {{ $achievementClass }}" 
                                                      style="background-color: {{ $achievementBgColor }} !important; color: {{ $achievement >= 45 && $achievement < 55 ? '#000' : 'white' }};">
                                                    {{ number_format($achievement, 2) }}%
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center text-muted">No data available for the selected filters</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="5">TOTAL</th>
                                        <th class="text-end">{{ number_format($budgetData['summary']['total_budgeted'], 2) }}</th>
                                        <th class="text-end">{{ number_format($budgetData['summary']['total_actual'], 2) }}</th>
                                        <th class="text-end {{ $budgetData['summary']['total_variance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($budgetData['summary']['total_variance'], 2) }}
                                        </th>
                                        <th class="text-end">
                                            <span class="badge {{ $budgetData['summary']['total_variance'] >= 0 ? 'bg-success' : 'bg-danger' }}">
                                                {{ $budgetData['summary']['variance_percentage'] }}%
                                            </span>
                                        </th>
                                        <th class="text-end">
                                            @php
                                                $totalAchievement = $budgetData['summary']['total_budgeted'] > 0 
                                                    ? round(($budgetData['summary']['total_actual'] / $budgetData['summary']['total_budgeted']) * 100, 2)
                                                    : 0;
                                                $totalAchievementClass = '';
                                                $totalAchievementBgColor = '';
                                                
                                                if ($totalAchievement >= 50) {
                                                    if ($totalAchievement >= 90) {
                                                        $totalAchievementClass = 'achievement-excellent';
                                                        $totalAchievementBgColor = '#155724';
                                                    } elseif ($totalAchievement >= 75) {
                                                        $totalAchievementClass = 'achievement-very-good';
                                                        $totalAchievementBgColor = '#28a745';
                                                    } elseif ($totalAchievement >= 60) {
                                                        $totalAchievementClass = 'achievement-good';
                                                        $totalAchievementBgColor = '#5cb85c';
                                                    } else {
                                                        $totalAchievementClass = 'achievement-fair';
                                                        $totalAchievementBgColor = '#7cb342';
                                                    }
                                                } elseif ($totalAchievement >= 45 && $totalAchievement < 55) {
                                                    $totalAchievementClass = 'achievement-average';
                                                    $totalAchievementBgColor = '#ffc107';
                                                } else {
                                                    if ($totalAchievement >= 30) {
                                                        $totalAchievementClass = 'achievement-poor';
                                                        $totalAchievementBgColor = '#ff9800';
                                                    } else {
                                                        $totalAchievementClass = 'achievement-very-poor';
                                                        $totalAchievementBgColor = '#dc3545';
                                                    }
                                                }
                                            @endphp
                                            <span class="badge achievement-badge {{ $totalAchievementClass }}" 
                                                  style="background-color: {{ $totalAchievementBgColor }} !important; color: {{ $totalAchievement >= 45 && $totalAchievement < 55 ? '#000' : 'white' }};">
                                                {{ number_format($totalAchievement, 2) }}%
                                            </span>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Handle date type changes
    $('#date_type').on('change', function() {
        const dateType = $(this).val();
        const dateFromInput = $('#date_from');
        const dateToInput = $('#date_to');
        
        if (dateType === 'custom') {
            dateFromInput.prop('readonly', false);
            dateToInput.prop('readonly', false);
        } else {
            dateFromInput.prop('readonly', true);
            dateToInput.prop('readonly', true);
            $('#budgetReportForm').submit();
        }
    });
    
    // Auto-submit form when year or branch changes
    $('#year, #branch_id').on('change', function() {
        $('#budgetReportForm').submit();
    });
    
    // Initialize DataTable
    $('#budgetReportTable').DataTable({
        "pageLength": 25,
        "order": [[ 0, "asc" ]],
        "responsive": true,
        "autoWidth": false,
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries per page",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": "Showing 0 to 0 of 0 entries",
            "infoFiltered": "(filtered from _MAX_ total entries)",
            "emptyTable": "No budget data available",
            "zeroRecords": "No matching budget records found"
        },
        "columnDefs": [
            {
                "targets": [5, 6, 7, 8, 9],
                "className": "text-end"
            }
        ]
    });
});
</script>
@endpush

@endsection
