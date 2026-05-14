@extends('layouts.main')

@section('title', __('app.dashboard'))

@php
use Vinkla\Hashids\Facades\Hashids;
@endphp

<style>
    .financial-section {
        margin-bottom: 20px;
    }

    .section-header {
        border-radius: 8px 8px 0 0 !important;
    }

    .section-content {
        border-radius: 0 0 8px 8px !important;
        border-top: none !important;
    }

    .account-row:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s ease;
    }

    .account-row a:hover {
        color: #007bff !important;
        text-decoration: underline !important;
    }

    .table-sm td {
        padding: 0.5rem;
        vertical-align: middle;
    }

    .section-title {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }

    .widgets-icons-2 {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #ededed;
        font-size: 27px;
    }
    
    .bg-gradient-primary {
        background: linear-gradient(45deg, #0d6efd, #0a58ca) !important;
    }
    
    .bg-gradient-success {
        background: linear-gradient(45deg, #198754, #146c43) !important;
    }
    
    .bg-gradient-info {
        background: linear-gradient(45deg, #0dcaf0, #0aa2c0) !important;
    }
    
    .bg-gradient-warning {
        background: linear-gradient(45deg, #ffc107, #ffb300) !important;
    }
    
    .bg-gradient-danger {
        background: linear-gradient(45deg, #dc3545, #bb2d3b) !important;
    }
    
    .bg-gradient-secondary {
        background: linear-gradient(45deg, #6c757d, #5c636a) !important;
    }
    
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .radius-10 {
        border-radius: 10px;
    }
    
    .border-start {
        border-left-width: 3px !important;
    }

    @media print {

        .btn,
        .overlay,
        .back-to-top,
        footer {
            display: none !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
        }

        .section-header {
            background: #333 !important;
            color: white !important;
        }
    }
</style>

@section('content')
@can('view dashboard')
<div class="page-wrapper">
    <div class="page-content">

        <div class="page-content">
        <!-- Welcome Section -->
        <div class="row">
            <div class="col-12">
                <div class="card border-top border-0 border-4 border-primary">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="card-title d-flex align-items-center">
                                    <div><i class="bx bx-home me-1 font-22 text-primary"></i></div>
                                    <!-- <h5 class="mb-0 text-primary">Welcome back, {{ auth()->user()->name }}
                                    </h5> -->
                                    <h5 class="mb-0 text-primary">Dashboard
                                    </h5>
                                </div>
                                <p class="mb-0 text-muted">Here's what's happening with your financial data today</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="d-flex gap-2 justify-content-end">
                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#bulkSmsModal">
                                        <i class="bx bx-envelope"></i> SMS
                                    </button>
                                    <a href="{{ route('customers.create') }}" class="btn btn-sm btn-primary">
                                        <i class="bx bx-user-plus"></i> Create Customer
                                    </a>
                                    <a href="{{ route('sales.invoices.create') }}" class="btn btn-sm btn-success">
                                        <i class="bx bx-money"></i> Create Invoice
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Branch Filter -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('dashboard') }}" class="d-flex align-items-center">
                            <div class="me-3">
                                <label for="branch_id" class="form-label mb-0"><strong>Filter Dashboard By Branch:</strong></label>
                            </div>
                            <div class="me-3">
                                <select name="branch_id" id="branch_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Branches</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ $selectedBranchId == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if($selectedBranchId)
                                <div class="me-3">
                                    <span class="badge bg-primary">
                                        Showing: {{ $branches->where('id', $selectedBranchId)->first()->name ?? 'Selected Branch' }}
                                    </span>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>


        <div class="row">
            @can('view inventory value card')
            <div class="col-xl-3 col-md-6">
                <a href="{{ route('inventory.reports.stock-valuation') }}" class="text-decoration-none">
                    <div class="card radius-10 border-start border-0 border-3 border-info">
                        <div class="card-body position-relative">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="mb-0 text-secondary">Total Value of Inventory</p>
                                    <h4 class="my-1 text-info" id="inventoryValue">TZS {{ number_format($totalInventoryValue ?? 0, 2) }}</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-info"><i class="bx bx-package align-middle"></i> Inventory items (<span id="inventoryItemsCount">{{ $totalInventoryItemsCount ?? 0 }}</span>)</span>
                                    </p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                    <i class='bx bx-package'></i>
                                </div>
                            </div>
                            <span class="stretched-link"></span>
                        </div>
                    </div>
                </a>
            </div>
            @endcan

            @can('view sales today card')
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Sales Today</p>
                                <h4 class="my-1 text-success" id="totalSalesTodayValue">TZS {{ number_format($totalSalesToday ?? 0, 2) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-cart align-middle"></i> Today's total sales (invoices, cash & POS)</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                <i class='bx bx-cart'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endcan

            @can('view net profit ytd card')
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Net Profit YTD</p>
                                <h4 class="my-1 text-primary">TZS {{ number_format($netProfitYtd ?? 0, 2) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-line-chart align-middle"></i> Year-to-date net profit</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class='bx bx-line-chart'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endcan

            @can('view total expenses today card')
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-danger">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Expenses Today</p>
                                <h4 class="my-1 text-danger" id="totalExpensesTodayValue">TZS {{ number_format($totalExpensesToday ?? 0, 2) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-danger"><i class="bx bx-wallet align-middle"></i> All payments/expenses today</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-danger text-white ms-auto">
                                <i class='bx bx-wallet'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan
        <!--end first row-->

        @can('view total outstanding invoices card')
        <div class="row mt-3">
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Outstanding Invoices</p>
                                <h4 class="my-1 text-warning" id="outstandingInvoicesAmountValue">TZS {{ number_format($outstandingInvoicesAmount ?? 0, 2) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-warning"><i class="bx bx-file align-middle"></i> Unpaid balance (<span id="outstandingInvoicesCountValue">{{ $outstandingInvoicesCount ?? 0 }}</span>)</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                <i class='bx bx-file'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endcan

            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Customers</p>
                                <h4 class="my-1 text-info" id="totalCustomersValue">{{ $totalCustomers ?? 0 }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-info"><i class="bx bx-user align-middle"></i> Active customers</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                <i class='bx bx-user'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- <div class="col-xl-3 col-md-6">
                <a href="{{ route('approvals.queue') }}" class="text-decoration-none">
                    <div class="card radius-10 border-start border-0 border-3 border-warning">
                        <div class="card-body position-relative">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="mb-0 text-secondary">Pending Approvals</p>
                                    <h4 class="my-1 text-warning">{{ $pendingApprovalsCount ?? 0 }}</h4>
                                    <p class="mb-0 font-13">
                                        <span class="text-warning"><i class="bx bx-check-shield align-middle"></i> Awaiting your review</span>
                                    </p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                    <i class='bx bx-check-shield'></i>
                                </div>
                            </div>
                            <span class="stretched-link"></span>
                        </div>
                    </div>
                </a>
            </div> -->

            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Cash Collected Today</p>
                                <h4 class="my-1 text-success" id="cashCollectedTodayValue">TZS {{ number_format($cashCollectedToday ?? 0, 2) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-money align-middle"></i> Total receipts today</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                <i class='bx bx-money'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @can('view revenue this month card')
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Revenue This Month</p>
                                <h4 class="my-1 text-primary" id="revenueThisMonthValue">TZS {{ number_format($revenueThisMonth ?? 0, 2) }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-trending-up align-middle"></i> Sales invoices this month</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class='bx bx-trending-up'></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan
        <!--end second row-->



        <!-- Charts Row: Sales Aging & Top 10 Items Sold -->
        <div class="row">
            <div class="col-5">
                <div class="card radius-10">
                    <div class="card-body">
                        <h5 class="mb-3">Sales Aging (Unpaid Invoices)</h5>
                        <canvas id="salesAgingChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-7">
                <div class="card radius-10">
                    <div class="card-body">
                        <h5 class="mb-3">Top 10 Items Sold (This Year)</h5>
                        <div id="topItemsChartWrapper" style="height: 220px; position: relative;">
                            <canvas id="topItemsChart" style="height: 220px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script nonce="{{ $cspNonce ?? '' }}">
        document.addEventListener('DOMContentLoaded', function() {
            // Load Inventory Summary
            fetch(`/dashboard/inventory-summary?branch_id=${encodeURIComponent(document.getElementById('branch_id')?.value || '')}`)
                .then(response => response.ok ? response.json() : Promise.reject('Network error'))
                .then(data => {
                    const inventoryValueEl = document.getElementById('inventoryValue');
                    if (inventoryValueEl) {
                        inventoryValueEl.textContent = 'TZS ' + Number(data.totalInventoryValue || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }
                    const inventoryItemsCountEl = document.getElementById('inventoryItemsCount');
                    if (inventoryItemsCountEl) {
                        inventoryItemsCountEl.textContent = Number(data.totalInventoryItemsCount || 0).toLocaleString();
                    }
                })
                .catch(err => console.error('Inventory summary error:', err));

            // Load Summary Cards
            fetch(`/dashboard/cards-summary?branch_id=${encodeURIComponent(document.getElementById('branch_id')?.value || '')}`)
                .then(response => response.json())
                .then(data => {
                    const totalSalesTodayEl = document.getElementById('totalSalesTodayValue');
                    if (totalSalesTodayEl) {
                        totalSalesTodayEl.textContent = 'TZS ' + Number(data.totalSalesToday || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }

                    const totalExpensesTodayEl = document.getElementById('totalExpensesTodayValue');
                    if (totalExpensesTodayEl) {
                        totalExpensesTodayEl.textContent = 'TZS ' + Number(data.totalExpensesToday || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }

                    const outstandingAmountEl = document.getElementById('outstandingInvoicesAmountValue');
                    if (outstandingAmountEl) {
                        outstandingAmountEl.textContent = 'TZS ' + Number(data.outstandingInvoicesAmount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }

                    const outstandingCountEl = document.getElementById('outstandingInvoicesCountValue');
                    if (outstandingCountEl) {
                        outstandingCountEl.textContent = Number(data.outstandingInvoicesCount || 0).toLocaleString();
                    }

                    const totalCustomersEl = document.getElementById('totalCustomersValue');
                    if (totalCustomersEl) {
                        totalCustomersEl.textContent = Number(data.totalCustomers || 0).toLocaleString();
                    }

                    const cashCollectedEl = document.getElementById('cashCollectedTodayValue');
                    if (cashCollectedEl) {
                        cashCollectedEl.textContent = 'TZS ' + Number(data.cashCollectedToday || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }

                    const revenueThisMonthEl = document.getElementById('revenueThisMonthValue');
                    if (revenueThisMonthEl) {
                        revenueThisMonthEl.textContent = 'TZS ' + Number(data.revenueThisMonth || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }
                })
                .catch(err => console.error('Summary cards error:', err));

            // Revenue Trend Chart
            const revenueChartEl = document.getElementById('revenueTrendChart');
            if (revenueChartEl) {
                fetch(`/dashboard/revenue-trend?branch_id=${encodeURIComponent(document.getElementById('branch_id')?.value || '')}`)
                    .then(response => response.json())
                    .then(data => {
                        const ctx = revenueChartEl.getContext('2d');
                        if (ctx && typeof Chart !== 'undefined') {
                            new Chart(ctx, {
                                type: 'line',
                                data: data,
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { display: false }
                                    },
                                    scales: {
                                        y: { beginAtZero: true }
                                    }
                                }
                            });
                        }
                    })
                    .catch(err => console.error('Revenue trend error:', err));
            }

            // Order Status Distribution Chart
            const orderStatusChartEl = document.getElementById('orderStatusChart');
            if (orderStatusChartEl) {
                fetch(`/dashboard/order-status?branch_id=${encodeURIComponent(document.getElementById('branch_id')?.value || '')}`)
                    .then(response => response.json())
                    .then(data => {
                        const ctx = orderStatusChartEl.getContext('2d');
                        if (ctx && typeof Chart !== 'undefined') {
                            new Chart(ctx, {
                                type: 'doughnut',
                                data: data,
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { position: 'bottom' }
                                    }
                                }
                            });
                        }
                    })
                    .catch(err => console.error('Order status error:', err));
            }

            // Top Products Chart
            const topProductsChartEl = document.getElementById('topProductsChart');
            if (topProductsChartEl) {
                fetch(`/dashboard/top-products?branch_id=${encodeURIComponent(document.getElementById('branch_id')?.value || '')}`)
                    .then(response => response.json())
                    .then(data => {
                        const ctx = topProductsChartEl.getContext('2d');
                        if (ctx && typeof Chart !== 'undefined') {
                            new Chart(ctx, {
                                type: 'bar',
                                data: data,
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    indexAxis: 'y',
                                    plugins: {
                                        legend: { display: false }
                                    },
                                    scales: {
                                        x: { beginAtZero: true }
                                    }
                                }
                            });
                        }
                    })
                    .catch(err => console.error('Top products error:', err));
            }

            // Top 10 Items Sold (This Year)
            const topItemsChartEl = document.getElementById('topItemsChart');
            if (topItemsChartEl) {
                fetch(`/dashboard/top-items-sold?branch_id=${encodeURIComponent(document.getElementById('branch_id')?.value || '')}`)
                    .then(response => response.ok ? response.json() : Promise.reject('Network error'))
                    .then(data => {
                        const canvas = topItemsChartEl;
                        const ctx = canvas.getContext('2d');
                        const items = (data && data.items) ? data.items : [];
                        const quantities = (data && data.quantities) ? data.quantities : [];
                        const isEmpty = items.length === 0 || quantities.length === 0 || quantities.every(q => q == 0);
                        if (isEmpty) {
                            canvas.style.display = 'none';
                            const fallback = document.createElement('div');
                            fallback.style.textAlign = 'center';
                            fallback.style.padding = '30px 0';
                            fallback.style.color = '#888';
                            fallback.innerHTML = '<b>No sales data available for this year.</b>';
                            canvas.parentNode.appendChild(fallback);
                            return;
                        }
                        if (ctx && typeof Chart !== 'undefined') {
                            new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: items,
                                    datasets: [{
                                        label: 'Quantity Sold',
                                        data: quantities,
                                        backgroundColor: '#27ae60'
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { display: false },
                                        title: {
                                            display: true,
                                            text: 'Top 10 Items Sold (This Year)'
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            title: { display: true, text: 'Qty' }
                                        },
                                        x: {
                                            title: { display: true, text: 'Item' },
                                            ticks: { autoSkip: true, maxTicksLimit: 10 }
                                        }
                                    }
                                }
                            });
                        }
                    })
                    .catch(err => {
                        const canvas = document.getElementById('topItemsChart');
                        if (canvas) {
                            canvas.style.display = 'none';
                            const fallback = document.createElement('div');
                            fallback.style.textAlign = 'center';
                            fallback.style.padding = '30px 0';
                            fallback.style.color = '#888';
                            fallback.innerHTML = '<b>Unable to load items data.</b>';
                            if (canvas.parentNode) {
                                canvas.parentNode.appendChild(fallback);
                            }
                        }
                        console.error('Top items chart error:', err);
                    });
            }

            // Sales Aging (Unpaid Invoices) Pie Chart using async endpoint
            (function() {
                fetch(`/dashboard/receivables-aging?branch_id=${encodeURIComponent(document.getElementById('branch_id')?.value || '')}`)
                    .then(response => response.ok ? response.json() : Promise.reject('Network error'))
                    .then(aging => {
                        const labels = ['Current', '1-30 Days', '31-60 Days', '>60 Days'];
                        const values = [
                            Number(aging.current || 0),
                            Number(aging.overdue_1_30 || 0),
                            Number(aging.overdue_31_60 || 0),
                            Number(aging.overdue_60_plus || 0)
                        ];

                        const canvas = document.getElementById('salesAgingChart');
                        const ctx = canvas.getContext('2d');
                        if (!values.length || values.every(v => v == 0)) {
                            canvas.style.display = 'none';
                            const fallback = document.createElement('div');
                            fallback.style.textAlign = 'center';
                            fallback.style.padding = '30px 0';
                            fallback.style.color = '#888';
                            fallback.innerHTML = '<b>No unpaid invoice aging data available.</b>';
                            ctx.canvas.parentNode.appendChild(fallback);
                            return;
                        }

                        new Chart(ctx, {
                            type: 'pie',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Unpaid Invoices',
                                    data: values,
                                    backgroundColor: ['#2ecc71', '#f1c40f', '#e67e22', '#e74c3c']
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: { display: true },
                                    title: { display: true, text: 'Sales Aging (Unpaid Invoices)' },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                                const value = context.parsed;
                                                const percent = total ? ((value / total) * 100).toFixed(1) : 0;
                                                return `${context.label}: TZS ${Number(value).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} (${percent}%)`;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    })
                    .catch(() => {
                        const canvas = document.getElementById('salesAgingChart');
                        if (!canvas) return;
                        canvas.style.display = 'none';
                        const fallback = document.createElement('div');
                        fallback.style.textAlign = 'center';
                        fallback.style.padding = '30px 0';
                        fallback.style.color = '#888';
                        fallback.innerHTML = '<b>Unable to load receivables aging.</b>';
                        canvas.parentNode.appendChild(fallback);
                    });
            })();

            // Gross Profit Trend (This Year)
            (function() {
                const canvas = document.getElementById('grossProfitTrendChart');
                if (!canvas) return;

                const branchVal = document.getElementById('branch_id')?.value || '';
                fetch(`/dashboard/gross-profit-trend?branch_id=${encodeURIComponent(branchVal)}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        const ctx = canvas.getContext('2d');
                        const months = data.months || ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                        const revenue = (data.revenue || []).map(v => Number(v) || 0);
                        const cogs = (data.cogs || []).map(v => Number(v) || 0);
                        const profit = (data.profit || []).map(v => Number(v) || 0);

                        const isEmpty = months.length === 0 ||
                            (revenue.every(v => v == 0) && cogs.every(v => v == 0) && profit.every(v => v == 0));
                        if (isEmpty) {
                            canvas.style.display = 'none';
                            const fallback = document.createElement('div');
                            fallback.style.textAlign = 'center';
                            fallback.style.padding = '40px 0';
                            fallback.style.color = '#888';
                            fallback.innerHTML = '<b>No gross profit data available for this year.</b>';
                            ctx.canvas.parentNode.appendChild(fallback);
                            return;
                        } else {
                            canvas.style.display = '';
                            const parent = ctx.canvas.parentNode;
                            const fallbacks = parent.querySelectorAll('div');
                            fallbacks.forEach(el => { if (el && el.innerText && el.innerText.includes('No data')) el.remove(); });
                        }

                        const minVal = Math.min(...revenue, ...cogs, ...profit);
                        const maxVal = Math.max(...revenue, ...cogs, ...profit);

                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: months,
                                datasets: [
                                    { 
                                        label: 'Revenue', 
                                        data: revenue, 
                                        borderColor: 'rgba(46, 204, 113, 1)',
                                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                                        fill: false,
                                        tension: 0.4
                                    },
                                    { 
                                        label: 'COGS', 
                                        data: cogs, 
                                        borderColor: 'rgba(231, 76, 60, 1)',
                                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                                        fill: false,
                                        tension: 0.4
                                    },
                                    { 
                                        label: 'Gross Profit', 
                                        data: profit, 
                                        borderColor: 'rgba(52, 152, 219, 1)',
                                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                                        fill: false,
                                        tension: 0.4
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: true, position: 'top' },
                                    title: { display: true, text: 'Gross Profit Trend (This Year)' },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                let label = context.dataset.label || '';
                                                let raw = context.parsed;
                                                let value = (raw && typeof raw === 'object') ? Number(raw.y ?? 0) : Number(raw ?? 0);
                                                if (!isFinite(value)) value = 0;
                                                return `${label}: TZS ${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: { title: { display: true, text: 'Month' } },
                                    y: {
                                        beginAtZero: false,
                                        suggestedMin: Math.min(0, minVal * 1.1),
                                        suggestedMax: Math.max(0, maxVal * 1.1),
                                        title: { display: true, text: 'Amount (TZS)' }
                                    }
                                }
                            }
                        });
                    })
                    .catch((err) => {
                        const canvas = document.getElementById('grossProfitTrendChart');
                        if (canvas) {
                            canvas.style.display = 'none';
                            const fallback = document.createElement('div');
                            fallback.style.textAlign = 'center';
                            fallback.style.padding = '40px 0';
                            fallback.style.color = '#888';
                            fallback.innerHTML = '<b>Unable to load gross profit trend data.</b>';
                            canvas.parentNode.appendChild(fallback);
                        }
                        console && console.error && console.error('gross-profit-trend fetch failed', err);
                    });
            })();

            // Revenue, Expenses, Net Profit (Monthly in Selected Year)
            let profitChartInstance = null;
            function loadProfitChartForYear(year) {
                const branchVal = document.getElementById('branch_id')?.value || '';
                return fetch(`/dashboard/profit-by-year?year=${encodeURIComponent(year)}&branch_id=${encodeURIComponent(branchVal)}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        const canvas = document.getElementById('monthlyCollectionsChart');
                        const ctx = canvas.getContext('2d');
                        const labels = data.labels || ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                        const revenue = (data.revenue || []).map(v => Number(v) || 0);
                        const expenses = (data.expenses || []).map(v => Number(v) || 0);
                        const profit = (data.profit || []).map(v => Number(v) || 0);

                        const isEmpty = labels.length === 0 ||
                            (revenue.every(v => v == 0) && expenses.every(v => v == 0) && profit.every(v => v == 0));
                        if (isEmpty) {
                            canvas.style.display = 'none';
                            const fallback = document.createElement('div');
                            fallback.style.textAlign = 'center';
                            fallback.style.padding = '40px 0';
                            fallback.style.color = '#888';
                            fallback.innerHTML = '<b>No data available for the selected year.</b>';
                            ctx.canvas.parentNode.appendChild(fallback);
                            return;
                        } else {
                            canvas.style.display = '';
                            const parent = ctx.canvas.parentNode;
                            const fallbacks = parent.querySelectorAll('div');
                            fallbacks.forEach(el => { if (el && el.innerText && el.innerText.includes('No data')) el.remove(); });
                        }

                        const minVal = Math.min(...revenue, ...expenses, ...profit);
                        const maxVal = Math.max(...revenue, ...expenses, ...profit);

                        if (profitChartInstance) {
                            profitChartInstance.destroy();
                            profitChartInstance = null;
                        }

                        profitChartInstance = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: labels,
                                datasets: [
                                    { label: 'Revenue', data: revenue, backgroundColor: 'rgba(46, 204, 113, 0.6)' },
                                    { label: 'Expenses', data: expenses, backgroundColor: 'rgba(231, 76, 60, 0.6)' },
                                    { label: 'Net Profit', data: profit, backgroundColor: 'rgba(52, 152, 219, 0.6)' }
                                ]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: { display: true },
                                    title: { display: true, text: `Revenue, Expenses and Net Profit (${year})` },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                let label = context.dataset.label || '';
                                                let raw = context.parsed;
                                                let value = (raw && typeof raw === 'object') ? Number(raw.y ?? 0) : Number(raw ?? 0);
                                                if (!isFinite(value)) value = 0;
                                                return `${label}: TZS ${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: { stacked: false, title: { display: true, text: 'Month' } },
                                    y: {
                                        stacked: false,
                                        beginAtZero: false,
                                        suggestedMin: Math.min(0, minVal * 1.1),
                                        suggestedMax: Math.max(0, maxVal * 1.1),
                                        title: { display: true, text: 'Amount (TZS)' }
                                    }
                                },
                                barThickness: 12
                            }
                        });
                    })
                    .catch((err) => {
                        const canvas = document.getElementById('monthlyCollectionsChart');
                        if (canvas) {
                            canvas.style.display = 'none';
                            const fallback = document.createElement('div');
                            fallback.style.textAlign = 'center';
                            fallback.style.padding = '40px 0';
                            fallback.style.color = '#888';
                            fallback.innerHTML = '<b>Unable to load monthly profit data.</b>';
                            canvas.parentNode.appendChild(fallback);
                        }
                        console && console.error && console.error('profit-by-year fetch failed', err);
                    });
            }

            const yearSelect = document.getElementById('profitYearSelect');
            if (yearSelect) {
                loadProfitChartForYear(yearSelect.value);
                yearSelect.addEventListener('change', function() {
                    loadProfitChartForYear(this.value);
                });
            }
        });

           // Removed yearly aggregate chart init to avoid double-initialization; monthly per-year loader below handles charting

            // Helper function to update change indicators
            function updateChangeIndicator(elementId, change) {
                const element = document.getElementById(elementId);
                if (!element) return;
                
                const icon = element.querySelector('i');
                const text = element.querySelector('small');
                
                if (change > 0) {
                    icon.className = 'bx bx-up-arrow-alt text-success me-1';
                    text.className = 'text-success';
                    text.textContent = '+' + change.toFixed(1) + '%';
                } else if (change < 0) {
                    icon.className = 'bx bx-down-arrow-alt text-danger me-1';
                    text.className = 'text-danger';
                    text.textContent = change.toFixed(1) + '%';
                } else {
                    icon.className = 'bx bx-minus text-muted me-1';
                    text.className = 'text-muted';
                    text.textContent = '0.0%';
                }
            }
        </script>
        <!--end row-->
        @can('view graphs')
        <!-- Balance Sheet Overview -->
        <div class="row">
            <div class="col-12 col-lg-8 d-lg-flex align-items-lg-stretch">
                <div class="card radius-10 w-100">
                    <div class="card-body">
                        <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                            <div class="card-header bg-white border-bottom-0">
                                <div class="d-flex align-items-center">
                                    <i class="bx bx-line-chart text-primary me-2 font-20"></i>
                                    <h6 class="mb-0 text-dark">Gross Profit Trend (This Year)</h6>
                                </div>
                            </div>
                            <div class="card-body pt-3 pb-2">
                                <canvas id="grossProfitTrendChart" height="120"></canvas>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                                <div class="card-header bg-white border-bottom-0 d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <i class="bx bx-bar-chart-alt-2 text-primary me-2 font-20"></i>
                                        <h6 class="mb-0 text-dark">Revenue, Expenses and Net Profit</h6>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <label for="profitYearSelect" class="me-2 mb-0 small text-muted">Year</label>
                                        <select id="profitYearSelect" class="form-select form-select-sm" style="width: auto;">
                                            @php $cy = date('Y'); @endphp
                                            @for ($y = $cy; $y >= $cy - 4; $y--)
                                                <option value="{{ $y }}" {{ $y == $cy ? 'selected' : '' }}>{{ $y }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                                <div class="card-body pt-3 pb-2">
                                    <canvas id="monthlyCollectionsChart" height="120"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4 d-lg-flex align-items-lg-stretch">
                <div class="card radius-10 w-100">
                    <div class="card-header bg-transparent">Account Class Balances</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Class</th>
                                        <th>Balance</th>
                                        <th>Accounts</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($balanceSheetData as $item)
                                    <tr>
                                        <td>
                                            <div>
                                                <strong>{{ $item['class_code'] }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $item['class_name'] }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $item['balance'] >= 0 ? 'bg-success' : 'bg-danger' }}">
                                                TZS {{ number_format($item['balance'], 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $item['account_count'] }}</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No account data available</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end row-->
        @endcan



        <!-- Recent Activities -->
        <div class="row row-cols-1 row-cols-lg-3">
            <div class="col">
                <div class="card radius-10">
                    <div class="card-header bg-transparent">
                        <h6 class="mb-0"><i class="bx bx-book-open me-2"></i>Recent Journals</h6>
                    </div>
                    <div class="card-body">
                        @forelse($recentJournals as $journal)
                        <div class="d-flex align-items-center mb-3">
                            <div class="widgets-icons bg-light-primary text-primary me-3">
                                <i class="bx bx-book"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ $journal->reference }}</h6>
                                <p class="mb-0 text-muted">{{ Str::limit($journal->description, 30) }}</p>
                                <small class="text-muted">{{ $journal->date ? $journal->date->format('M d, Y') : 'N/A' }}</small>
                            </div>
                        </div>
                        @empty
                        <p class="text-muted text-center">No recent journals</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10">
                    <div class="card-header bg-transparent">
                        <h6 class="mb-0"><i class="bx bx-money me-2"></i>Recent Payments</h6>
                    </div>
                    <div class="card-body">
                        @forelse($recentPayments as $payment)
                        <div class="d-flex align-items-center mb-3">
                            <div class="widgets-icons bg-light-success text-success me-3">
                                <i class="bx bx-money"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ $payment->reference }}</h6>
                                <p class="mb-0 text-muted">{{ Str::limit($payment->description, 30) }}</p>
                                <small class="text-muted">{{ $payment->date ? $payment->date->format('M d, Y') : 'N/A' }}</small>
                            </div>
                        </div>
                        @empty
                        <p class="text-muted text-center">No recent payments</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10">
                    <div class="card-header bg-transparent">
                        <h6 class="mb-0"><i class="bx bx-receipt me-2"></i>Recent Receipts</h6>
                    </div>
                    <div class="card-body">
                        @forelse($recentReceipts as $receipt)
                        <div class="d-flex align-items-center mb-3">
                            <div class="widgets-icons bg-light-success text-success me-3">
                                <i class="bx bx-receipt"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ $receipt->reference }}</h6>
                                <p class="mb-0 text-muted">{{ $receipt->description ?? 'N/A' }}</p>
                                <small class="text-muted">{{ $receipt->date ? $receipt->date->format('M d, Y') : 'N/A' }}</small>
                            </div>
                        </div>
                        @empty
                        <p class="text-muted text-center">No recent receipts</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Report Summary -->
        @can('view financial reports')
        <div class="row">
            <div class="col-12">
                <div class="card radius-10 border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="mb-0 text-dark"><i class="bx bx-bar-chart me-2"></i>FINANCIAL REPORT SUMMARY</h5>
                                <small class="text-muted">
                                    Comprehensive financial overview as of {{ date('d-m-Y') }}
                                    @php
                                        $currentBranchName = null;
                                        if (!empty($selectedBranchId)) {
                                            $currentBranchName = optional($branches->firstWhere('id', $selectedBranchId))->name;
                                        }
                                    @endphp
                                    — {{ $currentBranchName ? ('Branch: ' . $currentBranchName) : 'All Branches' }}
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <!-- Balance Sheet Section -->
                            <div class="col-md-6">
                                <div class="financial-section">
                                    <div class="section-header bg-light p-3 rounded-top">
                                        <h4 class="mb-0 text-dark"><i class="bx bx-balance me-2"></i>BALANCE SHEET</h4>
                                        <small class="text-muted">As of {{ date('d-m-Y') }} vs {{ $previousYearData['year'] }}</small>
                                    </div>

                                    <!-- Assets Section -->
                                    <div class="section-content border rounded-bottom">
                                        <div class="section-title bg-light p-2 border-bottom">
                                            <h6 class="mb-0 text-dark"><i class="bx bx-trending-up me-1"></i>ASSETS</h6>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover mb-0" id="assets-table">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Account</th>
                                                        <th class="text-end">Current Year</th>
                                                        <th class="text-end">Previous Year</th>
                                                        <th class="text-end">Change</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php $sumAsset = 0; $sumAssetPrev = 0; @endphp
                                                    @foreach($financialReportData['chartAccountsAssets'] as $mainGroupName => $mainGroup)
                                                    @php 
                                                        $prevMainGroup = $previousYearData['chartAccountsAssets'][$mainGroupName] ?? null;
                                                        $prevMainGroupTotal = $prevMainGroup['total'] ?? 0;
                                                        $currentMainGroupTotal = $mainGroup['total'] ?? 0;
                                                    @endphp
                                                    @if($currentMainGroupTotal != 0 || $prevMainGroupTotal != 0)
                                                    @php 
                                                        $mainGroupId = 'asset-' . Str::slug($mainGroupName); 
                                                        $mainGroupChange = $currentMainGroupTotal - $prevMainGroupTotal;
                                                    @endphp
                                                    <tr class="table-primary parent-row clickable" data-bs-toggle="collapse" data-bs-target=".{{ $mainGroupId }}" aria-expanded="true">
                                                        <td class="fw-bold text-dark">
                                                            <i class="bx bx-chevron-down me-1 transition-icon"></i>
                                                            <i class="bx bx-folder me-1"></i>{{ $mainGroupName }}
                                                        </td>
                                                        <td class="text-end fw-bold text-dark">{{ number_format($currentMainGroupTotal, 2) }}</td>
                                                        <td class="text-end fw-bold text-dark">{{ number_format($prevMainGroupTotal, 2) }}</td>
                                                        <td class="text-end fw-bold text-dark">
                                                            {{ $mainGroupChange >= 0 ? '+' : '' }}{{ number_format($mainGroupChange, 2) }}
                                                        </td>
                                                    </tr>
                                                    @if(isset($mainGroup['fslis']))
                                                        @foreach($mainGroup['fslis'] as $fsliName => $fsli)
                                                        @php 
                                                            $prevFsli = $prevMainGroup['fslis'][$fsliName] ?? null;
                                                            $prevFsliTotal = $prevFsli['total'] ?? 0;
                                                            $currentFsliTotal = $fsli['total'] ?? 0;
                                                        @endphp
                                                        @if($currentFsliTotal != 0 || $prevFsliTotal != 0)
                                                        @php 
                                                            $fsliId = 'fsli-asset-' . Str::slug($fsliName); 
                                                            $fsliChange = $currentFsliTotal - $prevFsliTotal;
                                                        @endphp
                                                        <tr class="table-light collapse show {{ $mainGroupId }} fsli-row clickable" data-bs-toggle="collapse" data-bs-target=".{{ $fsliId }}" aria-expanded="false">
                                                            <td class="ps-4 fw-medium text-dark">
                                                                <i class="bx bx-chevron-right me-1 transition-icon"></i>
                                                                {{ $fsliName }}
                                                            </td>
                                                            <td class="text-end fw-medium text-dark">{{ number_format($currentFsliTotal, 2) }}</td>
                                                            <td class="text-end fw-medium text-dark">{{ number_format($prevFsliTotal, 2) }}</td>
                                                            <td class="text-end fw-medium text-dark">
                                                                {{ $fsliChange >= 0 ? '+' : '' }}{{ number_format($fsliChange, 2) }}
                                                            </td>
                                                        </tr>
                                                        @if(isset($fsli['accounts']))
                                                            @foreach($fsli['accounts'] as $chartAccountAsset)
                                                                @include('partials.dashboard-account-row', [
                                                                    'account' => $chartAccountAsset,
                                                                    'mainGroupName' => $mainGroupName,
                                                                    'fsliName' => $fsliName,
                                                                    'fsliId' => $fsliId,
                                                                    'previousYearData' => $previousYearData['chartAccountsAssets'],
                                                                    'depth' => 0
                                                                ])
                                                            @endforeach
                                                        @endif
                                                        @endif
                                                        @endforeach
                                                    @endif
                                                    @endif
                                                    @endforeach
                                                    @php 
                                                        $sumAsset = collect($financialReportData['chartAccountsAssets'])->sum('total');
                                                        $sumAssetPrev = collect($previousYearData['chartAccountsAssets'])->sum('total');
                                                    @endphp
                                                    <tr class="table-secondary fw-bold">
                                                        <td>TOTAL ASSETS</td>
                                                        <td class="text-end">{{ number_format($sumAsset,2) }}</td>
                                                        <td class="text-end">{{ number_format($sumAssetPrev,2) }}</td>
                                                        <td class="text-end">
                                                            @php $assetChange = $sumAsset - $sumAssetPrev; @endphp
                                                                {{ $assetChange >= 0 ? '+' : '' }}{{ number_format($assetChange,2) }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Equity Section -->
                                        <div class="section-title bg-light p-2 border-bottom mt-3">
                                            <h6 class="mb-0 text-dark"><i class="bx bx-user me-1"></i>EQUITY</h6>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0 table-hover" id="equity-table">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Account</th>
                                                        <th class="text-end">Current Year</th>
                                                        <th class="text-end">Previous Year</th>
                                                        <th class="text-end">Change</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php $sumEquity = 0; $sumEquityPrev = 0; @endphp
                                                    @foreach($financialReportData['chartAccountsEquitys'] as $mainGroupName => $mainGroup)
                                                    @php 
                                                        $prevMainGroup = $previousYearData['chartAccountsEquitys'][$mainGroupName] ?? null;
                                                        $prevMainGroupTotal = $prevMainGroup['total'] ?? 0;
                                                        $currentMainGroupTotal = $mainGroup['total'] ?? 0;
                                                    @endphp
                                                    @if($currentMainGroupTotal != 0 || $prevMainGroupTotal != 0)
                                                    @php 
                                                        $mainGroupId = 'equity-' . Str::slug($mainGroupName); 
                                                        $mainGroupChange = $currentMainGroupTotal - $prevMainGroupTotal;
                                                    @endphp
                                                    <tr class="table-primary parent-row clickable" data-bs-toggle="collapse" data-bs-target=".{{ $mainGroupId }}" aria-expanded="true">
                                                        <td class="fw-bold text-dark">
                                                            <i class="bx bx-chevron-down me-1 transition-icon"></i>
                                                            <i class="bx bx-folder me-1"></i>{{ $mainGroupName }}
                                                        </td>
                                                        <td class="text-end fw-bold text-dark">{{ number_format($currentMainGroupTotal, 2) }}</td>
                                                        <td class="text-end fw-bold text-dark">{{ number_format($prevMainGroupTotal, 2) }}</td>
                                                        <td class="text-end fw-bold text-dark">
                                                            {{ $mainGroupChange >= 0 ? '+' : '' }}{{ number_format($mainGroupChange, 2) }}
                                                        </td>
                                                    </tr>
                                                    @if(isset($mainGroup['fslis']))
                                                        @foreach($mainGroup['fslis'] as $fsliName => $fsli)
                                                        @php 
                                                            $prevFsli = $prevMainGroup['fslis'][$fsliName] ?? null;
                                                            $prevFsliTotal = $prevFsli['total'] ?? 0;
                                                            $currentFsliTotal = $fsli['total'] ?? 0;
                                                        @endphp
                                                        @if($currentFsliTotal != 0 || $prevFsliTotal != 0)
                                                        @php 
                                                            $fsliId = 'fsli-equity-' . Str::slug($fsliName); 
                                                            $fsliChange = $currentFsliTotal - $prevFsliTotal;
                                                        @endphp
                                                        <tr class="table-light collapse show {{ $mainGroupId }} fsli-row clickable" data-bs-toggle="collapse" data-bs-target=".{{ $fsliId }}" aria-expanded="false">
                                                            <td class="ps-4 fw-medium text-dark">
                                                                <i class="bx bx-chevron-right me-1 transition-icon"></i>
                                                                {{ $fsliName }}
                                                            </td>
                                                            <td class="text-end fw-medium text-dark">{{ number_format($currentFsliTotal, 2) }}</td>
                                                            <td class="text-end fw-medium text-dark">{{ number_format($prevFsliTotal, 2) }}</td>
                                                            <td class="text-end fw-medium text-dark">
                                                                {{ $fsliChange >= 0 ? '+' : '' }}{{ number_format($fsliChange, 2) }}
                                                            </td>
                                                        </tr>
                                                        @if(isset($fsli['accounts']))
                                                            @foreach($fsli['accounts'] as $chartAccountEquity)
                                                                @include('partials.dashboard-account-row', [
                                                                    'account' => $chartAccountEquity,
                                                                    'mainGroupName' => $mainGroupName,
                                                                    'fsliName' => $fsliName,
                                                                    'fsliId' => $fsliId,
                                                                    'previousYearData' => $previousYearData['chartAccountsEquitys'],
                                                                    'depth' => 0
                                                                ])
                                                            @endforeach
                                                        @endif
                                                        @endif
                                                        @endforeach
                                                    @endif
                                                    @endif
                                                    @endforeach
                                                    @php 
                                                        $sumEquity = collect($financialReportData['chartAccountsEquitys'])->sum('total');
                                                        $sumEquityPrev = collect($previousYearData['chartAccountsEquitys'])->sum('total');
                                                    @endphp
                                                    <tr class="table-info">
                                                        <td>Profit And Loss (YTD)</td>
                                                        <td class="text-end fw-bold">{{ number_format($cumulativeProfitLoss ?? 0,2) }}</td>
                                                        <td class="text-end text-dark">{{ number_format($previousYearData['profitLoss'],2) }}</td>
                                                        <td class="text-end">
                                                            @php $profitChange = ($cumulativeProfitLoss ?? 0) - $previousYearData['profitLoss']; @endphp
                                                                {{ $profitChange >= 0 ? '+' : '' }}{{ number_format($profitChange,2) }}
                                                        </td>
                                                    </tr>
                                                    <tr class="table-secondary fw-bold">
                                                        <td>TOTAL EQUITY</td>
                                                        <td class="text-end">{{ number_format($sumEquity + ($cumulativeProfitLoss ?? 0),2) }}</td>
                                                        <td class="text-end">{{ number_format($sumEquityPrev + $previousYearData['profitLoss'],2) }}</td>
                                                        <td class="text-end">
                                                            @php $equityChange = ($sumEquity + ($cumulativeProfitLoss ?? 0)) - ($sumEquityPrev + $previousYearData['profitLoss']); @endphp
                                                                {{ $equityChange >= 0 ? '+' : '' }}{{ number_format($equityChange,2) }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Liabilities Section -->
                                        <div class="section-title bg-light p-2 border-bottom mt-3">
                                            <h6 class="mb-0 text-dark"><i class="bx bx-trending-down me-1"></i>LIABILITIES</h6>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0 table-hover" id="liabilities-table">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Account</th>
                                                        <th class="text-end">Current Year</th>
                                                        <th class="text-end">Previous Year</th>
                                                        <th class="text-end">Change</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($financialReportData['chartAccountsLiabilities'] as $mainGroupName => $mainGroup)
                                                    @php 
                                                        $prevMainGroup = $previousYearData['chartAccountsLiabilities'][$mainGroupName] ?? null;
                                                        $prevMainGroupTotal = $prevMainGroup['total'] ?? 0;
                                                        $currentMainGroupTotal = $mainGroup['total'] ?? 0;
                                                    @endphp
                                                    @if($currentMainGroupTotal != 0 || $prevMainGroupTotal != 0)
                                                    @php 
                                                        $mainGroupId = 'liability-' . Str::slug($mainGroupName); 
                                                        $mainGroupChange = $currentMainGroupTotal - $prevMainGroupTotal;
                                                    @endphp
                                                    <tr class="table-primary parent-row clickable" data-bs-toggle="collapse" data-bs-target=".{{ $mainGroupId }}" aria-expanded="true">
                                                        <td class="fw-bold text-dark">
                                                            <i class="bx bx-chevron-down me-1 transition-icon"></i>
                                                            <i class="bx bx-folder me-1"></i>{{ $mainGroupName }}
                                                        </td>
                                                        <td class="text-end fw-bold text-dark">{{ number_format($currentMainGroupTotal, 2) }}</td>
                                                        <td class="text-end fw-bold text-dark">{{ number_format($prevMainGroupTotal, 2) }}</td>
                                                        <td class="text-end fw-bold text-dark">
                                                            {{ $mainGroupChange >= 0 ? '+' : '' }}{{ number_format($mainGroupChange, 2) }}
                                                        </td>
                                                    </tr>
                                                    @if(isset($mainGroup['fslis']))
                                                        @foreach($mainGroup['fslis'] as $fsliName => $fsli)
                                                        @php 
                                                            $prevFsli = $prevMainGroup['fslis'][$fsliName] ?? null;
                                                            $prevFsliTotal = $prevFsli['total'] ?? 0;
                                                            $currentFsliTotal = $fsli['total'] ?? 0;
                                                        @endphp
                                                        @if($currentFsliTotal != 0 || $prevFsliTotal != 0)
                                                        @php 
                                                            $fsliId = 'fsli-liability-' . Str::slug($fsliName); 
                                                            $fsliChange = $currentFsliTotal - $prevFsliTotal;
                                                        @endphp
                                                        <tr class="table-light collapse show {{ $mainGroupId }} fsli-row clickable" data-bs-toggle="collapse" data-bs-target=".{{ $fsliId }}" aria-expanded="false">
                                                            <td class="ps-4 fw-medium text-dark">
                                                                <i class="bx bx-chevron-right me-1 transition-icon"></i>
                                                                {{ $fsliName }}
                                                            </td>
                                                            <td class="text-end fw-medium text-dark">{{ number_format($currentFsliTotal, 2) }}</td>
                                                            <td class="text-end fw-medium text-dark">{{ number_format($prevFsliTotal, 2) }}</td>
                                                            <td class="text-end fw-medium text-dark">
                                                                {{ $fsliChange >= 0 ? '+' : '' }}{{ number_format($fsliChange, 2) }}
                                                            </td>
                                                        </tr>
                                                        @if(isset($fsli['accounts']))
                                                            @foreach($fsli['accounts'] as $chartAccountLiability)
                                                                @include('partials.dashboard-account-row', [
                                                                    'account' => $chartAccountLiability,
                                                                    'mainGroupName' => $mainGroupName,
                                                                    'fsliName' => $fsliName,
                                                                    'fsliId' => $fsliId,
                                                                    'previousYearData' => $previousYearData['chartAccountsLiabilities'],
                                                                    'depth' => 0
                                                                ])
                                                            @endforeach
                                                        @endif
                                                        @endif
                                                        @endforeach
                                                    @endif
                                                    @endif
                                                    @endforeach
                                                    @php 
                                                        $sumLiability = collect($financialReportData['chartAccountsLiabilities'])->sum('total');
                                                        $sumLiabilityPrev = collect($previousYearData['chartAccountsLiabilities'])->sum('total');
                                                    @endphp
                                                    <tr class="fw-bold">
                                                        <td>TOTAL LIABILITIES</td>
                                                        <td class="text-end">{{ number_format($sumLiability,2) }}</td>
                                                        <td class="text-end">{{ number_format($sumLiabilityPrev, 2) }}</td>
                                                        <td class="text-end">
                                                            @php $liabilityChange = $sumLiability - $sumLiabilityPrev; @endphp
                                                                {{ $liabilityChange >= 0 ? '+' : '' }}{{ number_format(abs($liabilityChange),2) }}
                                                        </td>
                                                    </tr>
                                                    <tr class="table-secondary fw-bold">
                                                        <td>TOTAL EQUITY & LIABILITY</td>
                                                        <td class="text-end">{{ number_format($sumLiability + $sumEquity + ($cumulativeProfitLoss ?? 0),2) }}</td>
                                                        <td class="text-end">{{ number_format($sumLiabilityPrev + $sumEquityPrev + $previousYearData['profitLoss'],2) }}</td>
                                                        <td class="text-end">
                                                            @php $totalChange = ($sumLiability + $sumEquity + ($cumulativeProfitLoss ?? 0)) - ($sumLiabilityPrev + $sumEquityPrev + $previousYearData['profitLoss']); @endphp
                                                                {{ $totalChange >= 0 ? '+' : '' }}{{ number_format($totalChange,2) }}

                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Profit & Loss Section -->
                            <div class="col-md-6">
                                <div class="financial-section">
                                    <div class="section-header bg-light p-3 rounded-top">
                                        <h4 class="mb-0 text-dark"><i class="bx bx-line-chart me-2"></i>PROFIT & LOSS STATEMENT</h4>
                                        <small class="text-muted">From 01-01-{{date('Y')}} to {{ date('d-m-Y') }} vs {{ $previousYearData['year'] }}</small>
                                    </div>

                                    <div class="section-content border rounded-bottom">
                                        <!-- Revenue Section -->
                                        <div class="section-title bg-light p-2 border-bottom">
                                            <h6 class="mb-0 text-dark"><i class="bx bx-trending-up me-1"></i>INCOME</h6>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead class="table-secondary">
                                                    <tr>
                                                        <th>Account</th>
                                                        <th class="text-end">Current Year</th>
                                                        <th class="text-end">Previous Year</th>
                                                        <th class="text-end">Change</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php 
                                                        $sumRevenue = collect($financialReportData['chartAccountsRevenues'])->sum('total');
                                                        $sumRevenuePrev = collect($previousYearData['chartAccountsRevenues'])->sum('total');
                                                    @endphp
                                                    @foreach($financialReportData['chartAccountsRevenues'] as $mainGroupName => $mainGroup)
                                                    @php 
                                                        $prevMainGroup = $previousYearData['chartAccountsRevenues'][$mainGroupName] ?? null;
                                                        $prevMainGroupTotal = $prevMainGroup['total'] ?? 0;
                                                        $currentMainGroupTotal = $mainGroup['total'] ?? 0;
                                                    @endphp
                                                    @if($currentMainGroupTotal != 0 || $prevMainGroupTotal != 0)
                                                    @php 
                                                        $mainGroupId = 'income-' . Str::slug($mainGroupName); 
                                                        $mainGroupChange = $currentMainGroupTotal - $prevMainGroupTotal;
                                                    @endphp
                                                    <tr class="table-primary parent-row clickable" data-bs-toggle="collapse" data-bs-target=".{{ $mainGroupId }}" aria-expanded="true">
                                                        <td class="fw-bold text-dark">
                                                            <i class="bx bx-chevron-down me-1 transition-icon"></i>
                                                            <i class="bx bx-folder me-1"></i>{{ $mainGroupName }}
                                                        </td>
                                                        <td class="text-end fw-bold text-dark">{{ number_format($currentMainGroupTotal, 2) }}</td>
                                                        <td class="text-end fw-bold text-dark">{{ number_format($prevMainGroupTotal, 2) }}</td>
                                                        <td class="text-end fw-bold text-dark">
                                                            {{ $mainGroupChange >= 0 ? '+' : '' }}{{ number_format($mainGroupChange, 2) }}
                                                        </td>
                                                    </tr>
                                                    @foreach($mainGroup['fslis'] as $fsliName => $fsli)
                                                        @php 
                                                            $prevFsli = $prevMainGroup['fslis'][$fsliName] ?? null;
                                                            $prevFsliTotal = $prevFsli['total'] ?? 0;
                                                        $currentFsliTotal = $fsli['total'] ?? 0;
                                                    @endphp
                                                    @if($currentFsliTotal != 0 || $prevFsliTotal != 0)
                                                        @php 
                                                            $fsliId = 'fsli-income-' . Str::slug($fsliName); 
                                                            $fsliChange = $currentFsliTotal - $prevFsliTotal;
                                                        @endphp
                                                        <tr class="table-light collapse show {{ $mainGroupId }} fsli-row clickable" data-bs-toggle="collapse" data-bs-target=".{{ $fsliId }}" aria-expanded="false">
                                                            <td class="ps-4 fw-medium text-dark">
                                                                <i class="bx bx-chevron-right me-1 transition-icon"></i>
                                                                {{ $fsliName }}
                                                            </td>
                                                            <td class="text-end fw-medium text-dark">{{ number_format($currentFsliTotal, 2) }}</td>
                                                            <td class="text-end fw-medium text-dark">{{ number_format($prevFsliTotal, 2) }}</td>
                                                            <td class="text-end fw-medium text-dark">
                                                                {{ $fsliChange >= 0 ? '+' : '' }}{{ number_format($fsliChange, 2) }}
                                                            </td>
                                                        </tr>
                                                        @if(isset($fsli['accounts']))
                                                            @foreach($fsli['accounts'] as $chartAccountRevenue)
                                                                @include('partials.dashboard-account-row', [
                                                                    'account' => $chartAccountRevenue,
                                                                    'mainGroupName' => $mainGroupName,
                                                                    'fsliName' => $fsliName,
                                                                    'fsliId' => $fsliId,
                                                                    'previousYearData' => $previousYearData['chartAccountsRevenues'],
                                                                    'depth' => 0
                                                                ])
                                                            @endforeach
                                                        @endif
                                                        @endif
                                                        @endforeach
                                                    @endif
                                                    @endforeach
                                                    <tr class="fw-bold">
                                                        <td>TOTAL INCOME</td>
                                                        <td class="text-end">{{ number_format($sumRevenue,2) }}</td>
                                                        <td class="text-end">{{ number_format($sumRevenuePrev,2) }}</td>
                                                        <td class="text-end">
                                                            @php $revenueChange = $sumRevenue - $sumRevenuePrev; @endphp

                                                                {{ $revenueChange >= 0 ? '+' : '' }}{{ number_format($revenueChange,2) }}

                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Expenses Section -->
                                        <div class="section-title bg-light p-2 border-bottom mt-3">
                                            <h6 class="mb-0 text-dark"><i class="bx bx-trending-down me-1"></i>EXPENSES</h6>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Account</th>
                                                        <th class="text-end">Current Year</th>
                                                        <th class="text-end">Previous Year</th>
                                                        <th class="text-end">Change</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php 
                                                        $sumExpense = collect($financialReportData['chartAccountsExpense'])->sum('total');
                                                        $sumExpensePrev = collect($previousYearData['chartAccountsExpense'])->sum('total');
                                                    @endphp
                                                    @foreach($financialReportData['chartAccountsExpense'] as $mainGroupName => $mainGroup)
                                                    @php 
                                                        $prevMainGroup = $previousYearData['chartAccountsExpense'][$mainGroupName] ?? null;
                                                        $prevMainGroupTotal = $prevMainGroup['total'] ?? 0;
                                                        $currentMainGroupTotal = $mainGroup['total'] ?? 0;
                                                    @endphp
                                                    @if($currentMainGroupTotal != 0 || $prevMainGroupTotal != 0)
                                                    @php 
                                                        $mainGroupId = 'expense-' . Str::slug($mainGroupName); 
                                                        $mainGroupChange = $currentMainGroupTotal - $prevMainGroupTotal;
                                                    @endphp
                                                    <tr class="table-primary parent-row clickable" data-bs-toggle="collapse" data-bs-target=".{{ $mainGroupId }}" aria-expanded="true">
                                                        <td class="fw-bold text-dark">
                                                            <i class="bx bx-chevron-down me-1 transition-icon"></i>
                                                            <i class="bx bx-folder me-1"></i>{{ $mainGroupName }}
                                                        </td>
                                                        <td class="text-end fw-bold text-dark">{{ number_format($currentMainGroupTotal, 2) }}</td>
                                                        <td class="text-end fw-bold text-dark">{{ number_format($prevMainGroupTotal, 2) }}</td>
                                                        <td class="text-end fw-bold text-dark">
                                                            {{ $mainGroupChange >= 0 ? '+' : '' }}{{ number_format($mainGroupChange, 2) }}
                                                        </td>
                                                    </tr>
                                                    @foreach($mainGroup['fslis'] as $fsliName => $fsli)
                                                        @php 
                                                            $prevFsli = $prevMainGroup['fslis'][$fsliName] ?? null;
                                                            $prevFsliTotal = $prevFsli['total'] ?? 0;
                                                        $currentFsliTotal = $fsli['total'] ?? 0;
                                                    @endphp
                                                    @if($currentFsliTotal != 0 || $prevFsliTotal != 0)
                                                        @php 
                                                            $fsliId = 'fsli-expense-' . Str::slug($fsliName); 
                                                            $fsliChange = $currentFsliTotal - $prevFsliTotal;
                                                        @endphp
                                                        <tr class="table-light collapse show {{ $mainGroupId }} fsli-row clickable" data-bs-toggle="collapse" data-bs-target=".{{ $fsliId }}" aria-expanded="false">
                                                            <td class="ps-4 fw-medium text-dark">
                                                                <i class="bx bx-chevron-right me-1 transition-icon"></i>
                                                                {{ $fsliName }}
                                                            </td>
                                                            <td class="text-end fw-medium text-dark">{{ number_format($currentFsliTotal, 2) }}</td>
                                                            <td class="text-end fw-medium text-dark">{{ number_format($prevFsliTotal, 2) }}</td>
                                                            <td class="text-end fw-medium text-dark">
                                                                {{ $fsliChange >= 0 ? '+' : '' }}{{ number_format($fsliChange, 2) }}
                                                            </td>
                                                        </tr>
                                                        @if(isset($fsli['accounts']))
                                                            @foreach($fsli['accounts'] as $chartAccountExpense)
                                                                @include('partials.dashboard-account-row', [
                                                                    'account' => $chartAccountExpense,
                                                                    'mainGroupName' => $mainGroupName,
                                                                    'fsliName' => $fsliName,
                                                                    'fsliId' => $fsliId,
                                                                    'parentClasses' => $mainGroupId,
                                                                    'previousYearData' => $previousYearData['chartAccountsExpense'],
                                                                    'depth' => 0
                                                                ])
                                                            @endforeach
                                                        @endif
                                                        @endif
                                                        @endforeach
                                                    @endif
                                                    @endforeach
                                                    <tr class="table-secondary fw-bold">
                                                        <td>TOTAL EXPENSES</td>
                                                        <td class="text-end">{{ number_format($sumExpense,2) }}</td>
                                                        <td class="text-end">{{ number_format($sumExpensePrev,2) }}</td>
                                                        <td class="text-end">
                                                            @php $expenseChange = $sumExpense - $sumExpensePrev; @endphp

                                                                {{ $expenseChange >= 0 ? '+' : '' }}{{ number_format($expenseChange,2) }}

                                                        </td>
                                                    </tr>
                                                    <tr class="table-secondary">
                                                        <td>NET PROFIT/LOSS</td>
                                                        <td class="text-end">{{ number_format($sumRevenue - $sumExpense,2) }}</td>
                                                        <td class="text-end">{{ number_format($sumRevenuePrev - $sumExpensePrev,2) }}</td>
                                                        <td class="text-end">
                                                            @php $netProfitChange = ($sumRevenue - $sumExpense) - ($sumRevenuePrev - $sumExpensePrev); @endphp

                                                                {{ $netProfitChange >= 0 ? '+' : '' }}{{ number_format($netProfitChange,2) }}

                                                        </td>
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
        @endcan
        <!-- Bulk SMS Modal -->
        <div class="modal fade" id="bulkSmsModal" tabindex="-1" aria-labelledby="bulkSmsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkSmsModalLabel">
                            <i class="bx bx-envelope me-2"></i>Send Bulk SMS
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="bulkSmsForm" action="{{ route('sms.bulk') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="branch_id" class="form-label">Select Branch</label>
                                <select class="form-select" id="branch_id" name="branch_id" required>
                                    <option value="all">All Branches</option>
                                    @foreach(App\Models\Branch::all() as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="message_title" class="form-label fw-bold">Message Title</label>
                                <select class="form-select" id="message_title" name="message_title" required>
                                    <option value="">Select a title...</option>
                                    <option value="Payment Reminder">Payment Reminder</option>
                                    <option value="Custom">Custom Title</option>
                                </select>
                                <div class="form-text">Choose a title for this SMS batch or select Custom to enter your own.</div>
                            </div>
                            <div class="mb-3">
                                <label for="bulk_message_content" class="form-label">Message Content</label>
                                <textarea class="form-control" id="bulk_message_content" name="bulk_message_content" rows="4" maxlength="500" required></textarea>
                                <div class="form-text"><span id="bulk_character_count">0</span>/500 characters</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bx bx-x me-1"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-primary" id="sendBulkSmsBtn">
                                <i class="bx bx-send me-1"></i>Send Bulk SMS
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script nonce="{{ $cspNonce ?? '' }}">
        // Character counter for bulk SMS
        function updateBulkCharacterCount() {
            const bulkMessageContent = document.getElementById('bulk_message_content');
            const bulkCharacterCount = document.getElementById('bulk_character_count');
            const count = bulkMessageContent.value.length;
            bulkCharacterCount.textContent = count;
            if (count > 500) {
                bulkCharacterCount.style.color = 'red';
            } else if (count > 450) {
                bulkCharacterCount.style.color = 'orange';
            } else {
                bulkCharacterCount.style.color = 'green';
            }
        }
        document.getElementById('bulk_message_content').addEventListener('input', updateBulkCharacterCount);

        // Toggle message content visibility for Payment Reminder
        function toggleBulkMessageField() {
            const titleSelect = document.getElementById('message_title');
            const content = document.getElementById('bulk_message_content');
            const contentWrapper = content.closest('.mb-3');
            if (!titleSelect || !content || !contentWrapper) return;
            if (titleSelect.value === 'Payment Reminder') {
                // Prefill a default template, hide field, keep it enabled for submission
                if (!content.value || content.getAttribute('data-autofilled') !== 'yes') {
                    content.value = 'Dear Customer, this is a friendly reminder to clear your outstanding balance. Please make your payment at your earliest convenience. Thank you.';
                    content.setAttribute('data-autofilled', 'yes');
                }
                content.readOnly = true;
                content.required = false;
                contentWrapper.style.display = 'none';
                updateBulkCharacterCount();
            } else {
                content.readOnly = false;
                content.required = true;
                contentWrapper.style.display = '';
                // If previously auto-filled, allow user to edit/customize
                if (content.getAttribute('data-autofilled') === 'yes' && titleSelect.value === 'Custom') {
                    content.value = '';
                    content.removeAttribute('data-autofilled');
                }
                updateBulkCharacterCount();
            }
        }
        document.getElementById('message_title').addEventListener('change', toggleBulkMessageField);
        // Initialize on modal open (in case default is Payment Reminder)
        document.addEventListener('DOMContentLoaded', toggleBulkMessageField);

        // Bulk SMS form submission
        const bulkSmsForm = document.getElementById('bulkSmsForm');
        bulkSmsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const sendBtn = document.getElementById('sendBulkSmsBtn');
            const originalText = sendBtn.innerHTML;
            const modal = document.getElementById('bulkSmsModal');
            const formElements = modal.querySelectorAll('input, textarea, select, button');
            const closeBtn = modal.querySelector('.btn-close');
            const modalBody = modal.querySelector('.modal-body');
            // Capture form data BEFORE disabling inputs to avoid losing values
            const formData = new FormData(this);
            // Show loading state and disable all form elements
            sendBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Sending...';
            sendBtn.disabled = true;
            formElements.forEach(element => { element.disabled = true; });
            if (closeBtn) closeBtn.disabled = true;
            modalBody.style.opacity = '0.7';
            // Submit the form via AJAX
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                let responseMsg = '';
                if (typeof data.response === 'string') {
                    try {
                        const parsed = JSON.parse(data.response);
                        responseMsg = parsed.message || data.message || '';
                    } catch (e) {
                        responseMsg = data.response || data.message || '';
                    }
                } else if (typeof data.response === 'object' && data.response !== null) {
                    responseMsg = data.response.message || data.message || '';
                } else {
                    responseMsg = data.message || '';
                }
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Bulk SMS Sent!',
                        html: `<div><b>${responseMsg}</b></div>`,
                        confirmButtonColor: '#28a745',
                        timer: 3000,
                        timerProgressBar: true,
                        showConfirmButton: true
                    });
                    bulkSmsForm.reset();
                    updateBulkCharacterCount();
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    modalInstance.hide();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed to Send Bulk SMS',
                        text: responseMsg || 'Unknown error occurred',
                        confirmButtonColor: '#dc3545',
                        footer: 'Please try again or contact support if the problem persists.'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Connection Error',
                    text: 'Failed to send bulk SMS due to connection issues.',
                    confirmButtonColor: '#dc3545',
                    footer: 'Please check your internet connection and try again.'
                });
            })
            .finally(() => {
                sendBtn.innerHTML = originalText;
                sendBtn.disabled = false;
                formElements.forEach(element => { element.disabled = false; });
                if (closeBtn) closeBtn.disabled = false;
                modalBody.style.opacity = '1';
            });
        });
        </script>
        
        <style>
            .clickable {
                cursor: pointer;
            }
            .transition-icon {
                transition: transform 0.3s ease;
                display: inline-block;
            }
            [aria-expanded="true"] .transition-icon {
                transform: rotate(90deg);
            }
            /* Critical for Bootstrap collapse with table rows */
            tr.collapse.show {
                display: table-row !important;
            }
            .parent-account.clickable {
                background-color: rgba(0,0,0,0.02);
            }
            .parent-account.clickable:hover {
                background-color: rgba(0,0,0,0.05);
            }
        </style>

        <script nonce="{{ $cspNonce ?? '' }}">
            document.addEventListener('DOMContentLoaded', function() {
                // Recursive collapse logic
                const triggers = document.querySelectorAll('[data-bs-toggle="collapse"]');
                
                triggers.forEach(trigger => {
                    trigger.addEventListener('click', function(e) {
                        // Small delay to let Bootstrap update the aria-expanded attribute
                        setTimeout(() => {
                            const isExpanded = this.getAttribute('aria-expanded') === 'true';
                            
                            // If we are COLLAPSING this row, we want to hide all descendants
                            if (!isExpanded) {
                                const targetSelector = this.getAttribute('data-bs-target');
                                if (!targetSelector) return;
                                
                                const targets = document.querySelectorAll(targetSelector);
                                targets.forEach(target => {
                                    // If the child is also a parent/trigger, collapse it
                                    if (target.hasAttribute('data-bs-toggle')) {
                                        // If it's currently expanded, collapse it by clicking or using API
                                        if (target.getAttribute('aria-expanded') === 'true') {
                                            const bsCollapse = bootstrap.Collapse.getInstance(target) || new bootstrap.Collapse(target);
                                            bsCollapse.hide();
                                        }
                                    }
                                    
                                    // Also hide any nested targets this child might have
                                    const nestedTargetSelector = target.getAttribute('data-bs-target');
                                    if (nestedTargetSelector) {
                                        const nestedTargets = document.querySelectorAll(nestedTargetSelector);
                                        nestedTargets.forEach(nt => {
                                            const bsCollapse = bootstrap.Collapse.getInstance(nt) || new bootstrap.Collapse(nt);
                                            bsCollapse.hide();
                                        });
                                    }
                                });
                            }
                        }, 50);
                    });
                });
            });
        </script>
        @endpush
    </div>
</div>
@endcan
<!--end page wrapper -->
<!--start overlay-->
<div class="overlay toggle-icon"></div>
<!--end overlay-->
<!--Start Back To Top Button--> <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
<!--End Back To Top Button-->
<footer class="page-footer">
    <p class="mb-0">Copyright © 2021. All right reserved.</p>
</footer>
@endsection