@extends('layouts.main')

@section('title', 'Analytics Dashboard')

@push('styles')
<style>
    :root {
        --analytics-bg: #f8f9fa;
        --analytics-card-bg: #ffffff;
        --analytics-text: #1a1a1a;
        --analytics-text-muted: #6c757d;
        --analytics-border: #e0e0e0;
        --analytics-primary: #0078d4;
        --analytics-success: #00b294;
        --analytics-danger: #e81123;
        --analytics-warning: #ffaa44;
        --analytics-shadow: rgba(0, 0, 0, 0.08);
        --analytics-shadow-hover: rgba(0, 0, 0, 0.12);
    }

    :root[data-theme="dark"] {
        --analytics-bg: #1a1a1a;
        --analytics-card-bg: #2d2d2d;
        --analytics-text: #ffffff;
        --analytics-text-muted: #b0b0b0;
        --analytics-border: #404040;
        --analytics-shadow: rgba(0, 0, 0, 0.3);
        --analytics-shadow-hover: rgba(0, 0, 0, 0.4);
    }

    body.analytics-dark {
        background-color: var(--analytics-bg);
        color: var(--analytics-text);
    }

    .analytics-dashboard {
        background-color: var(--analytics-bg);
        min-height: 100vh;
        padding: 20px;
        overflow: visible !important;
        height: auto !important;
        max-height: none !important;
    }
    
    .page-content {
        overflow: visible !important;
        height: auto !important;
    }
    
    .page-wrapper {
        overflow: visible !important;
        height: auto !important;
    }

    .analytics-card {
        background: linear-gradient(135deg, var(--analytics-card-bg) 0%, var(--analytics-card-bg) 100%);
        border: none;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px var(--analytics-shadow), 0 1px 3px var(--analytics-shadow);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .analytics-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--analytics-primary), var(--analytics-primary));
        opacity: 0;
        transition: opacity 0.3s;
    }

    .analytics-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px var(--analytics-shadow-hover), 0 4px 8px var(--analytics-shadow);
    }

    .analytics-card:hover::before {
        opacity: 1;
    }

    .kpi-card {
        text-align: left;
        cursor: pointer;
        position: relative;
        padding: 16px 18px;
        background: linear-gradient(135deg, var(--analytics-card-bg) 0%, var(--analytics-card-bg) 100%);
        border-left: 4px solid var(--analytics-primary);
        min-height: auto;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .kpi-card:nth-child(1) { border-left-color: #0078d4; }
    .kpi-card:nth-child(2) { border-left-color: #00b294; }
    .kpi-card:nth-child(3) { border-left-color: #ffaa44; }
    .kpi-card:nth-child(4) { border-left-color: #e81123; }
    .kpi-card:nth-child(5) { border-left-color: #8764b8; }
    .kpi-card:nth-child(6) { border-left-color: #00bcf2; }
    .kpi-card:nth-child(7) { border-left-color: #ff5757; }
    .kpi-card:nth-child(8) { border-left-color: #50c878; }

    .kpi-card .kpi-label {
        font-size: 0.7rem;
        color: var(--analytics-text-muted);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
        line-height: 1.2;
    }

    .kpi-card .kpi-label::before {
        content: '';
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: currentColor;
        opacity: 0.6;
    }

    .kpi-card .kpi-value {
        font-size: clamp(1.5rem, 4vw, 2.2rem);
        font-weight: 700;
        color: var(--analytics-text);
        margin-bottom: 8px;
        line-height: 1.1;
        background: linear-gradient(135deg, var(--analytics-text), var(--analytics-text-muted));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        word-break: break-word;
        overflow-wrap: break-word;
        hyphens: auto;
    }

    .kpi-card .kpi-change {
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 4px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 16px;
        background: rgba(0, 120, 212, 0.1);
        width: fit-content;
        margin-top: 4px;
        line-height: 1.3;
    }

    .kpi-card .kpi-change.positive {
        color: #00b294;
        background: rgba(0, 178, 148, 0.1);
    }

    .kpi-card .kpi-change.negative {
        color: #e81123;
        background: rgba(232, 17, 35, 0.1);
    }

    .kpi-card .kpi-prev {
        font-size: 0.7rem;
        color: var(--analytics-text-muted);
        margin-top: 4px;
        font-weight: 500;
        line-height: 1.3;
    }

    .analytics-header {
        background: linear-gradient(135deg, var(--analytics-card-bg) 0%, var(--analytics-card-bg) 100%);
        border: none;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
        box-shadow: 0 2px 8px var(--analytics-shadow), 0 1px 3px var(--analytics-shadow);
        position: relative;
        overflow: hidden;
    }

    .analytics-header::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #0078d4, #00b294, #ffaa44, #e81123);
    }

    .analytics-header .logo-section {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .analytics-header .logo-section img {
        max-height: 50px;
    }

    .analytics-header .filters-section {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }

    .analytics-header select,
    .analytics-header input {
        background: var(--analytics-card-bg);
        border: 2px solid var(--analytics-border);
        color: var(--analytics-text);
        padding: 10px 14px;
        border-radius: 8px;
        font-size: 0.875rem;
        transition: all 0.2s;
    }

    .analytics-header select:focus,
    .analytics-header input:focus {
        outline: none;
        border-color: var(--analytics-primary);
        box-shadow: 0 0 0 3px rgba(0, 120, 212, 0.1);
    }

    .analytics-header button {
        padding: 10px 20px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .analytics-header .btn-primary {
        background: linear-gradient(135deg, #0078d4 0%, #005a9e 100%);
        color: white;
    }

    .analytics-header .btn-primary:hover {
        background: linear-gradient(135deg, #005a9e 0%, #0078d4 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 120, 212, 0.3);
    }

    .analytics-header .btn-export {
        background: linear-gradient(135deg, #00b294 0%, #008a75 100%);
        color: white;
    }

    .analytics-header .btn-export:hover {
        background: linear-gradient(135deg, #008a75 0%, #00b294 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 178, 148, 0.3);
    }

    .analytics-header .btn-theme {
        background: linear-gradient(135deg, #ffaa44 0%, #ff8800 100%);
        color: #000;
    }

    .analytics-header .btn-theme:hover {
        background: linear-gradient(135deg, #ff8800 0%, #ffaa44 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(255, 170, 68, 0.3);
    }

    .chart-container {
        background: linear-gradient(135deg, var(--analytics-card-bg) 0%, var(--analytics-card-bg) 100%);
        border: none;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        position: relative;
        box-shadow: 0 2px 8px var(--analytics-shadow), 0 1px 3px var(--analytics-shadow);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
    }

    .chart-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #0078d4, #00b294, #ffaa44);
        opacity: 0.8;
    }

    .chart-container:hover {
        box-shadow: 0 8px 24px var(--analytics-shadow-hover), 0 4px 8px var(--analytics-shadow);
        transform: translateY(-2px);
    }

    .chart-container .chart-title {
        font-size: 1.125rem;
        font-weight: 700;
        margin-bottom: 20px;
        color: var(--analytics-text);
        display: flex;
        align-items: center;
        gap: 10px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--analytics-border);
    }

    .chart-container .chart-title::before {
        content: '';
        width: 4px;
        height: 20px;
        background: linear-gradient(180deg, #0078d4, #00b294);
        border-radius: 2px;
    }

    .chart-wrapper {
        position: relative;
        height: 320px;
        padding: 10px;
    }

    .chart-wrapper.chart-large {
        height: 420px;
    }

    .key-metrics-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .key-metrics-table th,
    .key-metrics-table td {
        padding: 16px;
        text-align: left;
        border-bottom: 1px solid var(--analytics-border);
    }

    .key-metrics-table th {
        background: linear-gradient(135deg, var(--analytics-primary) 0%, #005a9e 100%);
        color: white;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .key-metrics-table th:first-child {
        border-radius: 8px 0 0 0;
    }

    .key-metrics-table th:last-child {
        border-radius: 0 8px 0 0;
    }

    .key-metrics-table tbody tr {
        transition: background-color 0.2s;
    }

    .key-metrics-table tbody tr:hover {
        background: rgba(0, 120, 212, 0.05);
    }

    .key-metrics-table tbody tr:nth-child(even) {
        background: rgba(0, 0, 0, 0.02);
    }

    body[data-theme="dark"] .key-metrics-table tbody tr:nth-child(even) {
        background: rgba(255, 255, 255, 0.02);
    }

    .key-metrics-table td {
        color: var(--analytics-text);
        font-weight: 500;
    }

    .trend-up {
        color: var(--analytics-success);
    }

    .trend-down {
        color: var(--analytics-danger);
    }

    .loading-spinner {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 200px;
        color: var(--analytics-text-muted);
    }

    .drill-down-panel {
        background: var(--analytics-card-bg);
        border: 1px solid var(--analytics-border);
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        display: none;
    }

    .drill-down-panel.active {
        display: block;
    }

    .drill-down-panel .back-btn {
        margin-bottom: 15px;
        padding: 6px 12px;
        background: var(--analytics-bg);
        border: 1px solid var(--analytics-border);
        color: var(--analytics-text);
        border-radius: 4px;
        cursor: pointer;
    }

    /* Responsive styles for KPI cards */
    @media (max-width: 1200px) {
        .kpi-card {
            padding: 14px 16px;
        }
        
        .kpi-card .kpi-value {
            font-size: clamp(1.3rem, 3.5vw, 2rem);
        }
    }

    @media (max-width: 992px) {
        .kpi-card {
            padding: 12px 14px;
        }
        
        .kpi-card .kpi-value {
            font-size: clamp(1.2rem, 3vw, 1.8rem);
        }
        
        .kpi-card .kpi-label {
            font-size: 0.65rem;
            margin-bottom: 6px;
        }
        
        .kpi-card .kpi-change {
            font-size: 0.7rem;
            padding: 3px 8px;
        }
    }

    @media (max-width: 768px) {
        .analytics-header {
            flex-direction: column;
            align-items: stretch;
        }

        .analytics-header .filters-section {
            flex-direction: column;
        }

        .kpi-card {
            padding: 12px 14px;
        }
        
        .kpi-card .kpi-value {
            font-size: clamp(1.1rem, 4vw, 1.6rem);
            margin-bottom: 6px;
        }
        
        .kpi-card .kpi-label {
            font-size: 0.65rem;
            margin-bottom: 6px;
        }
        
        .kpi-card .kpi-change {
            font-size: 0.65rem;
            padding: 3px 8px;
        }
        
        .kpi-card .kpi-prev {
            font-size: 0.65rem;
            margin-top: 3px;
        }
    }

    @media (max-width: 576px) {
        .kpi-card {
            padding: 10px 12px;
        }
        
        .kpi-card .kpi-value {
            font-size: clamp(1rem, 5vw, 1.4rem);
        }
        
        .kpi-card .kpi-label {
            font-size: 0.6rem;
            margin-bottom: 4px;
        }
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="analytics-dashboard" id="analyticsDashboard">
            <!-- Header with Filters -->
            <div class="analytics-header">
                <div class="logo-section">
                    @if($company && $company->logo)
                        <img src="{{ asset('storage/' . $company->logo) }}" alt="{{ $company->name }}">
                    @endif
                    <div>
                        <h4 class="mb-0" style="color: var(--analytics-text);">Executive Analytics Dashboard</h4>
                        <small style="color: var(--analytics-text-muted);">Real-time Financial Performance</small>
                    </div>
                </div>
                <div class="filters-section">
                    <select id="periodSelect" class="form-select">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="month" selected>Monthly</option>
                        <option value="quarter">Quarterly</option>
                        <option value="year">Yearly</option>
                        <option value="custom">Custom</option>
                    </select>
                    <input type="date" id="startDate" class="form-control" style="display: none;">
                    <input type="date" id="endDate" class="form-control" style="display: none;">
                    <select id="monthSelect" class="form-select" style="display: none;">
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ now()->month == $i ? 'selected' : '' }}>{{ now()->copy()->month($i)->format('F') }}</option>
                        @endfor
                    </select>
                    <select id="quarterSelect" class="form-select" style="display: none;">
                        @php
                            $currentQuarter = ceil(now()->month / 3);
                        @endphp
                        <option value="1" {{ $currentQuarter == 1 ? 'selected' : '' }}>Q1</option>
                        <option value="2" {{ $currentQuarter == 2 ? 'selected' : '' }}>Q2</option>
                        <option value="3" {{ $currentQuarter == 3 ? 'selected' : '' }}>Q3</option>
                        <option value="4" {{ $currentQuarter == 4 ? 'selected' : '' }}>Q4</option>
                    </select>
                    <select id="yearSelect" class="form-select">
                        @for($y = now()->year - 5; $y <= now()->year; $y++)
                            <option value="{{ $y }}" {{ now()->year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                    <button class="btn-primary" onclick="generateDashboard()">Generate Dashboard</button>
                    <button class="btn-export" onclick="exportToPDFServer()">Export to PDF</button>
                    <button class="btn-theme" onclick="toggleTheme()">🌓</button>
                </div>
            </div>

            <!-- Loading State -->
            <div id="loadingSpinner" class="loading-spinner">
                <div>
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading dashboard data...</p>
                </div>
            </div>

            <!-- KPI Cards Row 1 -->
            <div class="row g-3" id="kpiRow1" style="display: none;">
                <div class="col-md-3 col-sm-6">
                    <div class="analytics-card kpi-card" onclick="drillDown('revenue', 'product')">
                        <div class="kpi-label">Revenue</div>
                        <div class="kpi-value" id="kpiRevenue">-</div>
                        <div class="kpi-change" id="kpiRevenueChange">
                            <span class="trend-indicator">-</span>
                            <span>-</span>
                        </div>
                        <div class="kpi-prev" id="kpiRevenuePrev">Prev: -</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="analytics-card kpi-card" onclick="drillDown('expenses', 'category')">
                        <div class="kpi-label">Expenses</div>
                        <div class="kpi-value" id="kpiExpenses">-</div>
                        <div class="kpi-change" id="kpiExpensesChange">
                            <span class="trend-indicator">-</span>
                            <span>-</span>
                        </div>
                        <div class="kpi-prev" id="kpiExpensesPrev">Prev: -</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="analytics-card kpi-card">
                        <div class="kpi-label">Net Profit</div>
                        <div class="kpi-value" id="kpiNetProfit">-</div>
                        <div class="kpi-change" id="kpiNetProfitChange">
                            <span class="trend-indicator">-</span>
                            <span>-</span>
                        </div>
                        <div class="kpi-prev" id="kpiNetProfitPrev">Prev: -</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="analytics-card kpi-card">
                        <div class="kpi-label">Cash</div>
                        <div class="kpi-value" id="kpiCash">-</div>
                        <div class="kpi-change" id="kpiCashChange">
                            <span class="trend-indicator">-</span>
                            <span>-</span>
                        </div>
                        <div class="kpi-prev" id="kpiCashPrev">Prev: -</div>
                    </div>
                </div>
            </div>

            <!-- KPI Cards Row 2 -->
            <div class="row g-3" id="kpiRow2" style="display: none;">
                <div class="col-md-3 col-sm-6">
                    <div class="analytics-card kpi-card">
                        <div class="kpi-label">Gross Profit Margin</div>
                        <div class="kpi-value" id="kpiGrossProfitMargin">-</div>
                        <div class="kpi-change" id="kpiGrossProfitMarginChange">
                            <span class="trend-indicator">-</span>
                            <span>-</span>
                        </div>
                        <div class="kpi-prev" id="kpiGrossProfitMarginPrev">Prev: -</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="analytics-card kpi-card">
                        <div class="kpi-label">Net Profit Margin</div>
                        <div class="kpi-value" id="kpiNetProfitMargin">-</div>
                        <div class="kpi-change" id="kpiNetProfitMarginChange">
                            <span class="trend-indicator">-</span>
                            <span>-</span>
                        </div>
                        <div class="kpi-prev" id="kpiNetProfitMarginPrev">Prev: -</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="analytics-card kpi-card">
                        <div class="kpi-label">Expense Ratio</div>
                        <div class="kpi-value" id="kpiExpenseRatio">-</div>
                        <div class="kpi-change" id="kpiExpenseRatioChange">
                            <span class="trend-indicator">-</span>
                            <span>-</span>
                        </div>
                        <div class="kpi-prev" id="kpiExpenseRatioPrev">Prev: -</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="analytics-card kpi-card">
                        <div class="kpi-label">Current Ratio</div>
                        <div class="kpi-value" id="kpiCurrentRatio">-</div>
                        <div class="kpi-change" id="kpiCurrentRatioChange">
                            <span class="trend-indicator">-</span>
                            <span>-</span>
                        </div>
                        <div class="kpi-prev" id="kpiCurrentRatioPrev">Prev: -</div>
                    </div>
                </div>
            </div>

            <!-- Charts Row 1 -->
            <div class="row g-3" id="chartsRow1" style="display: none;">
                <div class="col-md-8">
                    <div class="chart-container">
                        <div class="chart-title">Revenue vs Expenses vs Profit</div>
                        <div id="chartErrorMsg" style="display: none; padding: 20px; text-align: center; color: #dc3545; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;">
                            <strong>Chart Library Error:</strong> Chart.js failed to load. Please check your internet connection or contact support.
                        </div>
                        <div class="chart-wrapper chart-large">
                            <canvas id="revenueTrendChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="chart-container">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="chart-title" id="expenseChartTitle">Expense Composition by Category</div>
                            <button id="expenseBackBtn" class="btn btn-sm btn-outline-secondary" style="display: none;">
                                <i class="bx bx-arrow-back me-1"></i>Back
                            </button>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="expenseCompositionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row 2 -->
            <div class="row g-3" id="chartsRow2" style="display: none;">
                <div class="col-md-6">
                    <div class="chart-container">
                        <div class="chart-title">Top 5 Customers</div>
                        <div class="chart-wrapper">
                            <canvas id="topCustomersChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-container">
                        <div class="chart-title">Cash Flow Movement</div>
                        <div class="chart-wrapper">
                            <canvas id="cashFlowChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row 3 - Top Products -->
            <div class="row g-3" id="chartsRow3" style="display: none;">
                <div class="col-md-12">
                    <div class="chart-container">
                        <div class="chart-title">Top 5 Products</div>
                        <div class="chart-wrapper">
                            <canvas id="topProductsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Key Metrics Table -->
            <div class="row g-3" id="keyMetricsRow" style="display: none;">
                <div class="col-12">
                    <div class="chart-container">
                        <div class="chart-title">Key Metrics</div>
                        <table class="key-metrics-table">
                            <thead>
                                <tr>
                                    <th>Metric</th>
                                    <th>Current</th>
                                    <th>Previous</th>
                                    <th>Change</th>
                                </tr>
                            </thead>
                            <tbody id="keyMetricsBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Drill-down Panel -->
            <div class="drill-down-panel" id="drillDownPanel">
                <button class="back-btn" onclick="closeDrillDown()">← Back</button>
                <div class="chart-title" id="drillDownTitle"></div>
                <div class="chart-wrapper chart-large">
                    <canvas id="drillDownChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" 
        onerror="console.error('Failed to load Chart.js from CDN'); 
                 document.getElementById('chartErrorMsg')?.removeAttribute('style');"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
    let charts = {};
    let currentData = null;
    let isDarkTheme = true;

    // Check if Chart.js is loaded
    function checkChartJsLoaded() {
        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded! Please check CDN connection.');
            const errorMsg = document.getElementById('chartErrorMsg');
            if (errorMsg) {
                errorMsg.style.display = 'block';
            }
            return false;
        }
        return true;
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for Chart.js to load
        if (!checkChartJsLoaded()) {
            // Retry after 1 second
            setTimeout(function() {
                if (!checkChartJsLoaded()) {
                    return;
                }
                initializeDashboard();
            }, 1000);
        } else {
            initializeDashboard();
        }
    });

    function initializeDashboard() {
        // Load saved theme preference
        const savedTheme = localStorage.getItem('analyticsTheme');
        if (savedTheme) {
            isDarkTheme = savedTheme === 'dark';
        }
        applyTheme();
        updateDateInputs();
        generateDashboard();
        
        // Watch for period changes
        const periodSelect = document.getElementById('periodSelect');
        if (periodSelect) {
            periodSelect.addEventListener('change', updateDateInputs);
        }
    }

    function updateDateInputs() {
        const period = document.getElementById('periodSelect').value;
        const startDate = document.getElementById('startDate');
        const endDate = document.getElementById('endDate');
        const monthSelect = document.getElementById('monthSelect');
        const quarterSelect = document.getElementById('quarterSelect');
        
        if (period === 'custom') {
            startDate.style.display = 'inline-block';
            endDate.style.display = 'inline-block';
            monthSelect.style.display = 'none';
            quarterSelect.style.display = 'none';
            
            if (!startDate.value) {
                startDate.value = new Date().toISOString().split('T')[0];
            }
            if (!endDate.value) {
                endDate.value = new Date().toISOString().split('T')[0];
            }
        } else {
            startDate.style.display = 'none';
            endDate.style.display = 'none';
            
            if (period === 'month') {
                monthSelect.style.display = 'inline-block';
                quarterSelect.style.display = 'none';
            } else if (period === 'quarter') {
                monthSelect.style.display = 'none';
                if (quarterSelect) {
                    quarterSelect.style.display = 'inline-block';
                }
            } else {
                monthSelect.style.display = 'none';
                quarterSelect.style.display = 'none';
            }
        }
    }

    function generateDashboard() {
        const period = document.getElementById('periodSelect').value;
        const year = document.getElementById('yearSelect').value;
        const month = document.getElementById('monthSelect')?.value;
        const quarter = document.getElementById('quarterSelect')?.value;
        let startDate = null;
        let endDate = null;

        if (period === 'custom') {
            startDate = document.getElementById('startDate').value;
            endDate = document.getElementById('endDate').value;
        }

        // Show loading
        document.getElementById('loadingSpinner').style.display = 'flex';
        document.getElementById('kpiRow1').style.display = 'none';
        document.getElementById('kpiRow2').style.display = 'none';
        document.getElementById('chartsRow1').style.display = 'none';
        document.getElementById('chartsRow2').style.display = 'none';
        document.getElementById('keyMetricsRow').style.display = 'none';

        // Build URL
        let url = '{{ route("analytics.dashboard-data") }}?period=' + period + '&year=' + year;
        if (month) url += '&month=' + month;
        if (quarter) url += '&quarter=' + quarter;
        if (startDate) url += '&start_date=' + startDate;
        if (endDate) url += '&end_date=' + endDate;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentData = data;
                renderDashboard(data);
            } else {
                alert('Error loading dashboard data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading dashboard data');
        })
        .finally(() => {
            document.getElementById('loadingSpinner').style.display = 'none';
        });
    }

    function renderDashboard(data) {
        console.log('Dashboard Data Received:', data);
        console.log('Top Customers Data:', data.charts?.top_customers);
        
        // Render KPIs
        renderKPIs(data.kpis);
        
        // Render Charts
        console.log('Revenue Trend Data:', data.charts?.revenue_trend);
        renderRevenueTrendChart(data.charts?.revenue_trend || []);
        renderExpenseCompositionChart(data.charts?.expense_composition || []);
        renderTopCustomersChart(data.charts?.top_customers || []);
        renderCashFlowChart(data.charts?.cash_flow_movement || {});
        renderTopProductsChart(data.charts?.top_products || []);
        
        // Render Key Metrics Table
        renderKeyMetricsTable(data.kpis);
        
        // Show all sections
        document.getElementById('kpiRow1').style.display = 'flex';
        document.getElementById('kpiRow2').style.display = 'flex';
        document.getElementById('chartsRow1').style.display = 'flex';
        document.getElementById('chartsRow2').style.display = 'flex';
        document.getElementById('keyMetricsRow').style.display = 'block';
    }

    function renderKPIs(kpis) {
        // Revenue
        updateKPI('kpiRevenue', kpis.revenue?.current ?? 0, kpis.revenue?.change_percent ?? 0, kpis.revenue?.previous ?? 0, kpis.revenue?.trend ?? 'neutral');
        
        // Expenses
        updateKPI('kpiExpenses', kpis.expenses?.current ?? 0, kpis.expenses?.change_percent ?? 0, kpis.expenses?.previous ?? 0, kpis.expenses?.trend ?? 'neutral');
        
        // Net Profit
        updateKPI('kpiNetProfit', kpis.net_profit?.current ?? 0, kpis.net_profit?.change_percent ?? 0, kpis.net_profit?.previous ?? 0, kpis.net_profit?.trend ?? 'neutral');
        
        // Cash Flow
        updateKPI('kpiCash', kpis.cash_flow?.current ?? 0, kpis.cash_flow?.change_percent ?? 0, kpis.cash_flow?.previous ?? 0, kpis.cash_flow?.trend ?? 'neutral');
        
        // Gross Profit Margin
        updateKPI('kpiGrossProfitMargin', kpis.gross_profit_margin?.current ?? 0, kpis.gross_profit_margin?.change_percent ?? 0, kpis.gross_profit_margin?.previous ?? 0, kpis.gross_profit_margin?.trend ?? 'neutral', '%');
        
        // Net Profit Margin
        updateKPI('kpiNetProfitMargin', kpis.net_profit_margin?.current ?? 0, kpis.net_profit_margin?.change_percent ?? 0, kpis.net_profit_margin?.previous ?? 0, kpis.net_profit_margin?.trend ?? 'neutral', '%');
        
        // Expense Ratio
        updateKPI('kpiExpenseRatio', kpis.expense_ratio?.current ?? 0, kpis.expense_ratio?.change_percent ?? 0, kpis.expense_ratio?.previous ?? 0, kpis.expense_ratio?.trend ?? 'neutral', '%');
        
        // Current Ratio (format as number, not currency)
        updateKPI('kpiCurrentRatio', kpis.current_ratio?.current ?? 0, kpis.current_ratio?.change_percent ?? 0, kpis.current_ratio?.previous ?? 0, kpis.current_ratio?.trend ?? 'neutral', 'ratio');
    }

    function updateKPI(elementPrefix, current, changePercent, previous, trend, suffix = '') {
        const valueEl = document.getElementById(elementPrefix);
        const changeEl = document.getElementById(elementPrefix + 'Change');
        const prevEl = document.getElementById(elementPrefix + 'Prev');
        
        if (suffix === '%') {
            valueEl.textContent = formatNumber(current, 1) + suffix;
        } else if (suffix === 'ratio') {
            valueEl.textContent = formatNumber(current, 2);
        } else {
            valueEl.textContent = formatCurrency(current);
        }
        
        const changeAbs = Math.abs(changePercent);
        const trendIcon = trend === 'up' ? '▲' : trend === 'down' ? '▼' : '-';
        const changeClass = trend === 'up' ? 'positive' : trend === 'down' ? 'negative' : '';
        
        changeEl.className = 'kpi-change ' + changeClass;
        changeEl.innerHTML = `<span>${trendIcon}</span><span>${formatNumber(changeAbs, 1)}%</span>`;
        
        if (previous !== null && previous !== undefined) {
            if (suffix === '%') {
                prevEl.textContent = 'Prev: ' + formatNumber(previous, 1) + suffix;
            } else if (suffix === 'ratio') {
                prevEl.textContent = 'Prev: ' + formatNumber(previous, 2);
            } else {
                prevEl.textContent = 'Prev: ' + formatCurrency(previous);
            }
        } else {
            prevEl.textContent = '';
        }
    }

    function renderRevenueTrendChart(data) {
        // Check if Chart.js is available
        if (typeof Chart === 'undefined') {
            console.error('Chart.js library is not loaded. Cannot render chart.');
            return;
        }

        const ctx = document.getElementById('revenueTrendChart');
        if (!ctx) {
            console.error('Revenue trend chart canvas not found');
            return;
        }
        
        if (charts.revenueTrend) {
            charts.revenueTrend.destroy();
        }
        
        // Handle empty or invalid data
        if (!data || !Array.isArray(data) || data.length === 0) {
            console.warn('No revenue trend data available', data);
            // Create empty chart with message
            charts.revenueTrend = new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['No Data'],
                    datasets: [{
                        label: 'Revenue',
                        data: [0],
                        borderColor: 'rgb(0, 123, 255)',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    }, {
                        label: 'Expenses',
                        data: [0],
                        borderColor: 'rgb(220, 53, 69)',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    }, {
                        label: 'Profit',
                        data: [0],
                        borderColor: 'rgb(40, 167, 69)',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'No data available for the selected period',
                            color: isDarkTheme ? '#ffffff' : '#333333'
                        },
                        legend: {
                            labels: {
                                color: isDarkTheme ? '#ffffff' : '#333333'
                            }
                        }
                    }
                }
            });
            return;
        }
        
        const labels = data.map(d => {
            try {
                return new Date(d.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            } catch (e) {
                return d.date || 'Unknown';
            }
        });
        const revenue = data.map(d => parseFloat(d.revenue || 0));
        const expenses = data.map(d => parseFloat(d.expenses || 0));
        const profit = data.map(d => parseFloat(d.profit || 0));
        
        console.log('Rendering Revenue Trend Chart:', {
            labels: labels.length,
            revenue: revenue.length,
            expenses: expenses.length,
            profit: profit.length
        });
        
        charts.revenueTrend = new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Revenue',
                        data: revenue,
                        borderColor: '#0078d4',
                        backgroundColor: 'rgba(0, 120, 212, 0.15)',
                        fill: true,
                        tension: 0.5,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#0078d4',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Expenses',
                        data: expenses,
                        borderColor: '#e81123',
                        backgroundColor: 'rgba(232, 17, 35, 0.15)',
                        fill: true,
                        tension: 0.5,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#e81123',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Profit',
                        data: profit,
                        borderColor: '#00b294',
                        backgroundColor: 'rgba(0, 178, 148, 0.15)',
                        fill: true,
                        tension: 0.5,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#00b294',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: isDarkTheme ? '#ffffff' : '#333333',
                            font: {
                                size: 12,
                                weight: '600'
                            },
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: isDarkTheme ? 'rgba(45, 45, 45, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                        titleColor: isDarkTheme ? '#ffffff' : '#333333',
                        bodyColor: isDarkTheme ? '#ffffff' : '#333333',
                        borderColor: isDarkTheme ? '#404040' : '#e0e0e0',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + formatCurrency(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            color: isDarkTheme ? '#ffffff' : '#6c757d',
                            font: {
                                size: 11,
                                weight: '500'
                            },
                            callback: function(value) {
                                return formatCurrency(value);
                            },
                            padding: 8
                        },
                        grid: {
                            color: isDarkTheme ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.08)',
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: {
                            color: isDarkTheme ? '#ffffff' : '#6c757d',
                            font: {
                                size: 11,
                                weight: '500'
                            },
                            padding: 8
                        },
                        grid: {
                            color: isDarkTheme ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.08)',
                            drawBorder: false
                        }
                    }
                }
            }
        });
    }

    // Expense Composition Chart - Drill-down state
    let expenseChartState = {
        level: 1, // 1 = categories, 2 = subcategories
        currentCategory: null,
        fullData: null
    };

    function renderExpenseCompositionChart(data) {
        if (typeof Chart === 'undefined') {
            console.error('Chart.js library is not loaded. Cannot render chart.');
            return;
        }
        
        const ctxEl = document.getElementById('expenseCompositionChart');
        if (!ctxEl) {
            console.error('Expense composition chart canvas not found');
            return;
        }
        
        // Store full data for drill-down
        expenseChartState.fullData = data;
        expenseChartState.level = 1;
        expenseChartState.currentCategory = null;
        
        // Reset UI
        document.getElementById('expenseChartTitle').textContent = 'Expense Composition by Category';
        document.getElementById('expenseBackBtn').style.display = 'none';
        
        renderExpenseChartLevel1(data);
    }

    function renderExpenseChartLevel1(data) {
        const ctxEl = document.getElementById('expenseCompositionChart');
        const ctx = ctxEl.getContext('2d');
        
        if (charts.expenseComposition) {
            charts.expenseComposition.destroy();
        }
        
        // Update UI - reset header and hide back button
        document.getElementById('expenseChartTitle').textContent = 'Expense Composition by Category';
        document.getElementById('expenseBackBtn').style.display = 'none';
        
        if (!data || data.length === 0) {
            charts.expenseComposition = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['No Data'],
                    datasets: [{
                        data: [1],
                        backgroundColor: ['rgba(128, 128, 128, 0.3)']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    }
                }
            });
            return;
        }
        
        const labels = data.map(d => d.category || 'Other');
        const amounts = data.map(d => d.amount || 0);
        const total = amounts.reduce((a, b) => a + b, 0);
        
        // Generate colors with conditional formatting (orange if > 60%)
        const colors = amounts.map((amount, index) => {
            const percentage = total > 0 ? (amount / total) * 100 : 0;
            if (percentage > 60) {
                return 'rgba(255, 140, 0, 0.85)'; // Orange warning
            }
            return generateColors(data.length)[index];
        });
        
        charts.expenseComposition = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: amounts,
                    backgroundColor: colors,
                    borderWidth: 3,
                    borderColor: isDarkTheme ? '#2d2d2d' : '#ffffff',
                    hoverBorderWidth: 4,
                    hoverBorderColor: isDarkTheme ? '#ffffff' : '#0078d4'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 1000,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: isDarkTheme ? '#ffffff' : '#333333',
                            padding: 12,
                            font: {
                                size: 11,
                                weight: '500'
                            },
                            generateLabels: function(chart) {
                                const data = chart.data;
                                const labelColor = isDarkTheme ? '#ffffff' : '#333333';
                                if (data.labels.length && data.datasets.length) {
                                    return data.labels.map((label, i) => {
                                        const value = data.datasets[0].data[i];
                                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                        return {
                                            text: `${label} (${percentage}%)`,
                                            fillStyle: data.datasets[0].backgroundColor[i],
                                            strokeStyle: data.datasets[0].borderColor,
                                            lineWidth: data.datasets[0].borderWidth,
                                            textColor: labelColor,
                                            fontColor: labelColor,
                                            hidden: false,
                                            index: i
                                        };
                                    });
                                }
                                return [];
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: isDarkTheme ? 'rgba(45, 45, 45, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                        titleColor: isDarkTheme ? '#ffffff' : '#333333',
                        bodyColor: isDarkTheme ? '#ffffff' : '#333333',
                        borderColor: isDarkTheme ? '#404040' : '#e0e0e0',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = formatCurrency(context.parsed);
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                onClick: function(event, elements) {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        const category = data[index];
                        if (category && category.subcategories && category.subcategories.length > 0) {
                            expenseChartState.level = 2;
                            expenseChartState.currentCategory = category;
                            renderExpenseChartLevel2(category);
                        }
                    }
                },
                onHover: function(event, elements) {
                    event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
                }
            }
        });
    }

    function renderExpenseChartLevel2(category) {
        const ctxEl = document.getElementById('expenseCompositionChart');
        const ctx = ctxEl.getContext('2d');
        
        if (charts.expenseComposition) {
            charts.expenseComposition.destroy();
        }
        
        // Update UI
        document.getElementById('expenseChartTitle').textContent = `${category.category} – Breakdown`;
        document.getElementById('expenseBackBtn').style.display = 'block';
        
        const subcategories = category.subcategories || [];
        const labels = subcategories.map(s => s.subcategory || 'Other');
        const amounts = subcategories.map(s => s.amount || 0);
        const total = amounts.reduce((a, b) => a + b, 0);
        
        // Use consistent color family for subcategories
        const baseColor = generateColors(1)[0];
        const colors = generateColors(subcategories.length);
        
        charts.expenseComposition = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: amounts,
                    backgroundColor: colors,
                    borderWidth: 3,
                    borderColor: isDarkTheme ? '#2d2d2d' : '#ffffff',
                    hoverBorderWidth: 4,
                    hoverBorderColor: isDarkTheme ? '#ffffff' : '#0078d4'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 800,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: isDarkTheme ? '#ffffff' : '#333333',
                            padding: 12,
                            font: {
                                size: 11,
                                weight: '500'
                            },
                            generateLabels: function(chart) {
                                const data = chart.data;
                                const labelColor = isDarkTheme ? '#ffffff' : '#333333';
                                if (data.labels.length && data.datasets.length) {
                                    return data.labels.map((label, i) => {
                                        const value = data.datasets[0].data[i];
                                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                        return {
                                            text: `${label} (${percentage}%)`,
                                            fillStyle: data.datasets[0].backgroundColor[i],
                                            strokeStyle: data.datasets[0].borderColor,
                                            lineWidth: data.datasets[0].borderWidth,
                                            textColor: labelColor,
                                            fontColor: labelColor,
                                            hidden: false,
                                            index: i
                                        };
                                    });
                                }
                                return [];
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: isDarkTheme ? 'rgba(45, 45, 45, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                        titleColor: isDarkTheme ? '#ffffff' : '#333333',
                        bodyColor: isDarkTheme ? '#ffffff' : '#333333',
                        borderColor: isDarkTheme ? '#404040' : '#e0e0e0',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = formatCurrency(context.parsed);
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                onHover: function(event, elements) {
                    event.native.target.style.cursor = 'default';
                }
            }
        });
    }

    // Back button handler
    document.addEventListener('DOMContentLoaded', function() {
        const backBtn = document.getElementById('expenseBackBtn');
        if (backBtn) {
            backBtn.addEventListener('click', function() {
                if (expenseChartState.fullData) {
                    expenseChartState.level = 1;
                    expenseChartState.currentCategory = null;
                    renderExpenseChartLevel1(expenseChartState.fullData);
                }
            });
        }
    });

    function renderTopCustomersChart(data) {
        if (typeof Chart === 'undefined') {
            console.error('Chart.js library is not loaded. Cannot render chart.');
            return;
        }
        
        console.log('Rendering Top Customers Chart with data:', data);
        const ctxEl = document.getElementById('topCustomersChart');
        if (!ctxEl) {
            console.error('Top customers chart canvas not found');
            return;
        }
        
        const ctx = ctxEl.getContext('2d');
        
        if (charts.topCustomers) {
            charts.topCustomers.destroy();
        }
        
        // Handle empty data
        if (!data || data.length === 0) {
            console.warn('No customer data available for Top Customers chart');
            charts.topCustomers = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['No Data'],
                    datasets: [{
                        label: 'Revenue',
                        data: [0],
                        backgroundColor: 'rgba(128, 128, 128, 0.5)',
                        borderColor: 'rgb(128, 128, 128)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    }
                }
            });
            return;
        }
        
        const labels = data.map(d => d.customer || 'Unknown');
        const revenues = data.map(d => d.revenue || 0);
        
        charts.topCustomers = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenue',
                    data: revenues,
                    backgroundColor: 'rgba(0, 120, 212, 0.85)',
                    borderColor: '#0078d4',
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: isDarkTheme ? 'rgba(45, 45, 45, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                        titleColor: isDarkTheme ? '#ffffff' : '#333333',
                        bodyColor: isDarkTheme ? '#ffffff' : '#333333',
                        borderColor: isDarkTheme ? '#404040' : '#e0e0e0',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return formatCurrency(context.parsed.x);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            color: isDarkTheme ? '#ffffff' : '#6c757d',
                            font: {
                                size: 11,
                                weight: '500'
                            },
                            callback: function(value) {
                                return formatCurrency(value);
                            },
                            padding: 8
                        },
                        grid: {
                            color: isDarkTheme ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.08)',
                            drawBorder: false
                        }
                    },
                    y: {
                        ticks: {
                            color: isDarkTheme ? '#ffffff' : '#6c757d',
                            font: {
                                size: 11,
                                weight: '500'
                            },
                            padding: 8
                        },
                        grid: {
                            color: isDarkTheme ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.08)',
                            drawBorder: false
                        }
                    }
                }
            }
        });
    }

    function renderTopProductsChart(data) {
        console.log('Rendering Top Products Chart with data:', data);
        
        if (typeof Chart === 'undefined') {
            console.error('Chart.js library is not loaded. Cannot render chart.');
            return;
        }
        
        const ctxEl = document.getElementById('topProductsChart');
        if (!ctxEl) {
            console.error('Top products chart canvas not found');
            return;
        }
        
        const ctx = ctxEl.getContext('2d');
        
        if (charts.topProducts) {
            charts.topProducts.destroy();
        }
        
        // Show the chart row first
        const chartRow = document.getElementById('chartsRow3');
        if (chartRow) {
            chartRow.style.display = 'flex';
        }
        
        // Handle empty data
        if (!data || data.length === 0) {
            console.warn('No product data available for Top Products chart');
            charts.topProducts = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['No Data'],
                    datasets: [{
                        label: 'Revenue',
                        data: [0],
                        backgroundColor: 'rgba(128, 128, 128, 0.5)',
                        borderColor: 'rgb(128, 128, 128)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    }
                }
            });
            return;
        }
        
        const labels = data.map(d => d.product || d.name || 'Unknown');
        const revenues = data.map(d => d.revenue || 0);
        
        charts.topProducts = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenue',
                    data: revenues,
                    backgroundColor: 'rgba(0, 178, 148, 0.85)',
                    borderColor: '#00b294',
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: isDarkTheme ? 'rgba(45, 45, 45, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                        titleColor: isDarkTheme ? '#ffffff' : '#333333',
                        bodyColor: isDarkTheme ? '#ffffff' : '#333333',
                        borderColor: isDarkTheme ? '#404040' : '#e0e0e0',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return formatCurrency(context.parsed.x);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            color: isDarkTheme ? '#ffffff' : '#6c757d',
                            font: {
                                size: 11,
                                weight: '500'
                            },
                            callback: function(value) {
                                return formatCurrency(value);
                            },
                            padding: 8
                        },
                        grid: {
                            color: isDarkTheme ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.08)',
                            drawBorder: false
                        }
                    },
                    y: {
                        ticks: {
                            color: isDarkTheme ? '#ffffff' : '#6c757d',
                            font: {
                                size: 11,
                                weight: '500'
                            },
                            padding: 8
                        },
                        grid: {
                            color: isDarkTheme ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.08)',
                            drawBorder: false
                        }
                    }
                }
            }
        });
        
        console.log('Top Products chart rendered successfully');
    }

    function renderCashFlowChart(data) {
        if (typeof Chart === 'undefined') {
            console.error('Chart.js library is not loaded. Cannot render chart.');
            return;
        }
        
        const ctxEl = document.getElementById('cashFlowChart');
        if (!ctxEl) {
            console.error('Cash flow chart canvas not found');
            return;
        }
        
        const ctx = ctxEl.getContext('2d');
        
        if (charts.cashFlow) {
            charts.cashFlow.destroy();
        }
        
        charts.cashFlow = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Beginning', 'Inflows', 'Outflows', 'Ending'],
                datasets: [{
                    label: 'Cash Flow',
                    data: [data.beginning, data.inflows, -data.outflows, data.ending],
                    backgroundColor: [
                        'rgba(0, 120, 212, 0.85)',
                        'rgba(0, 178, 148, 0.85)',
                        'rgba(232, 17, 35, 0.85)',
                        'rgba(0, 120, 212, 0.85)'
                    ],
                    borderColor: [
                        '#0078d4',
                        '#00b294',
                        '#e81123',
                        '#0078d4'
                    ],
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: isDarkTheme ? 'rgba(45, 45, 45, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                        titleColor: isDarkTheme ? '#ffffff' : '#333333',
                        bodyColor: isDarkTheme ? '#ffffff' : '#333333',
                        borderColor: isDarkTheme ? '#404040' : '#e0e0e0',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return formatCurrency(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            color: isDarkTheme ? '#ffffff' : '#6c757d',
                            font: {
                                size: 11,
                                weight: '500'
                            },
                            callback: function(value) {
                                return formatCurrency(value);
                            },
                            padding: 8
                        },
                        grid: {
                            color: isDarkTheme ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.08)',
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: {
                            color: isDarkTheme ? '#ffffff' : '#6c757d',
                            font: {
                                size: 11,
                                weight: '500'
                            },
                            padding: 8
                        },
                        grid: {
                            color: isDarkTheme ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.08)',
                            drawBorder: false
                        }
                    }
                }
            }
        });
    }

    function renderKeyMetricsTable(kpis) {
        const tbody = document.getElementById('keyMetricsBody');
        tbody.innerHTML = '';
        
        const metrics = [
            { key: 'revenue', label: 'Revenue' },
            { key: 'gross_profit', label: 'Gross Profit' },
            { key: 'net_profit', label: 'Net Profit' },
            { key: 'expenses', label: 'Expenses' },
            { key: 'gross_profit_margin', label: 'Gross Profit Margin', suffix: '%' },
            { key: 'net_profit_margin', label: 'Net Profit Margin', suffix: '%' }
        ];
        
        metrics.forEach(metric => {
            const kpi = kpis[metric.key];
            if (!kpi) return;
            
            const row = document.createElement('tr');
            const current = metric.suffix === '%' ? formatNumber(kpi.current ?? 0, 1) + '%' : formatCurrency(kpi.current ?? 0);
            const previous = kpi.previous !== null && kpi.previous !== undefined 
                ? (metric.suffix === '%' ? formatNumber(kpi.previous, 1) + '%' : formatCurrency(kpi.previous))
                : '-';
            const change = formatNumber(Math.abs(kpi.change_percent ?? 0), 1) + '%';
            const trendClass = kpi.trend === 'up' ? 'trend-up' : kpi.trend === 'down' ? 'trend-down' : '';
            const trendIcon = kpi.trend === 'up' ? '▲' : kpi.trend === 'down' ? '▼' : '-';
            const arrowColor = kpi.trend === 'up' ? '#28a745' : (kpi.trend === 'down' ? '#dc3545' : '#666');
            
            row.innerHTML = `
                <td>${metric.label}</td>
                <td>${current}</td>
                <td>${previous}</td>
                <td class="${trendClass}">
                    <span style="color: ${arrowColor};">${trendIcon}</span> ${change}
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    function drillDown(metric, groupBy) {
        if (!currentData) return;
        
        const url = '{{ route("analytics.drill-down") }}?metric=' + metric + '&group_by=' + groupBy + 
                   '&start_date=' + currentData.start_date + '&end_date=' + currentData.end_date;
        
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('drillDownPanel').classList.add('active');
                document.getElementById('drillDownTitle').textContent = metric.charAt(0).toUpperCase() + metric.slice(1) + ' by ' + groupBy.charAt(0).toUpperCase() + groupBy.slice(1);
                
                const ctx = document.getElementById('drillDownChart').getContext('2d');
                if (charts.drillDown) {
                    charts.drillDown.destroy();
                }
                
                const labels = data.data.map(d => d[groupBy] || d.customer || d.product || d.category);
                const values = data.data.map(d => d.revenue || d.amount);
                
                charts.drillDown = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: metric,
                            data: values,
                            backgroundColor: 'rgba(0, 123, 255, 0.8)',
                            borderColor: 'rgb(0, 123, 255)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return formatCurrency(context.parsed.y);
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: isDarkTheme ? '#ffffff' : '#333333',
                                    callback: function(value) {
                                        return formatCurrency(value);
                                    }
                                },
                                grid: {
                                    color: isDarkTheme ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                                }
                            },
                            x: {
                                ticks: {
                                    color: isDarkTheme ? '#ffffff' : '#333333'
                                },
                                grid: {
                                    color: isDarkTheme ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                                }
                            }
                        }
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading drill-down data');
        });
    }

    function closeDrillDown() {
        document.getElementById('drillDownPanel').classList.remove('active');
        if (charts.drillDown) {
            charts.drillDown.destroy();
            charts.drillDown = null;
        }
    }

    function toggleTheme() {
        isDarkTheme = !isDarkTheme;
        document.documentElement.setAttribute('data-theme', isDarkTheme ? 'dark' : 'light');
        document.body.classList.toggle('analytics-dark', isDarkTheme);
        localStorage.setItem('analyticsTheme', isDarkTheme ? 'dark' : 'light');
        applyTheme();
        // Re-render all charts with new theme
        if (currentData) {
            renderDashboard(currentData);
        }
    }

    function applyTheme() {
        // Apply theme to document root
        if (isDarkTheme) {
            document.documentElement.setAttribute('data-theme', 'dark');
            document.body.classList.add('analytics-dark');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
            document.body.classList.remove('analytics-dark');
        }
    }

    function exportToPDFServer() {
        // Get current filter values
        const period = document.getElementById('periodSelect').value;
        const month = document.getElementById('monthSelect')?.value || '';
        const quarter = document.getElementById('quarterSelect')?.value || '';
        const year = document.getElementById('yearSelect')?.value || new Date().getFullYear();
        const startDate = document.getElementById('startDate')?.value || '';
        const endDate = document.getElementById('endDate')?.value || '';
        const branchId = '{{ session('branch_id') ?? Auth::user()->branch_id ?? '' }}';
        
        // Build query string
        const params = new URLSearchParams({
            period: period,
            year: year,
        });
        
        if (month) params.append('month', month);
        if (quarter) params.append('quarter', quarter);
        if (startDate) params.append('start_date', startDate);
        if (endDate) params.append('end_date', endDate);
        if (branchId) params.append('branch_id', branchId);
        
        // Open server-side PDF export
        window.location.href = '{{ route("analytics.export-pdf") }}?' + params.toString();
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('en-TZ', {
            style: 'currency',
            currency: 'TZS',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value);
    }

    function formatNumber(value, decimals = 0) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(value);
    }

    function generateColors(count) {
        // Power BI-inspired color palette
        const colors = [
            'rgba(0, 120, 212, 0.85)',      // Microsoft Blue
            'rgba(0, 178, 148, 0.85)',      // Teal Green
            'rgba(255, 170, 68, 0.85)',    // Orange
            'rgba(232, 17, 35, 0.85)',      // Red
            'rgba(135, 100, 184, 0.85)',    // Purple
            'rgba(0, 188, 242, 0.85)',      // Cyan
            'rgba(255, 87, 87, 0.85)',     // Pink Red
            'rgba(80, 200, 120, 0.85)',    // Light Green
            'rgba(255, 140, 0, 0.85)',     // Dark Orange
            'rgba(106, 168, 79, 0.85)',    // Olive Green
            'rgba(184, 46, 184, 0.85)',    // Magenta
            'rgba(0, 183, 195, 0.85)'      // Aqua
        ];
        return colors.slice(0, count);
    }
</script>
@endpush