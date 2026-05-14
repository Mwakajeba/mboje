@extends('layouts.main')

@section('title', 'Sales Reports')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-bar-chart-alt-2'],
                ['label' => 'Sales Reports', 'url' => '#', 'icon' => 'bx bx-trending-up']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-trending-up me-2"></i>Sales Reports
                            </h4>
                        </div>
                        
                        <p class="text-muted mb-4">
                            Comprehensive sales analysis and reporting tools to track performance, 
                            analyze trends, and make data-driven business decisions.
                        </p>

                        <div class="row">
                            <!-- Sales Summary Report -->
                            @can('view sales reports')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-bar-chart fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Sales Summary</h5>
                                        <p class="card-text">High-level overview of total sales over time periods</p>
                                        <a href="{{ route('sales.reports.sales-summary') }}" class="btn btn-primary">
                                            <i class="bx bx-bar-chart me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Sales by Product Report -->
                            @can('view sales reports')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-package fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Sales by Product</h5>
                                        <p class="card-text">Product-wise sales volumes, revenue, and profitability</p>
                                        <a href="{{ route('sales.reports.sales-by-product') }}" class="btn btn-success">
                                            <i class="bx bx-package me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Sales by Customer Report -->
                            @can('view customer reports')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-user fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Sales by Customer</h5>
                                        <p class="card-text">Customer-wise revenue and contribution analysis</p>
                                        <a href="{{ route('sales.reports.sales-by-customer') }}" class="btn btn-info">
                                            <i class="bx bx-user me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Sales by Branch Report -->
                            @can('view branch performance')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-buildings fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Sales by Branch</h5>
                                        <p class="card-text">Branch/location sales performance comparison</p>
                                        <a href="{{ route('sales.reports.sales-by-branch') }}" class="btn btn-warning">
                                            <i class="bx bx-buildings me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Branch Profitability Report -->
                            @can('view branch performance')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-trending-up fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Branch Profitability</h5>
                                        <p class="card-text">Branch-level profitability after expenses</p>
                                        <a href="{{ route('sales.reports.branch-profitability') }}" class="btn btn-danger">
                                            <i class="bx bx-trending-up me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Sales Trend Report
                            @can('view sales reports')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-line-chart fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Sales Trend</h5>
                                        <p class="card-text">Sales trends and forecasting analysis</p>
                                        <a href="{{ route('sales.reports.sales-trend') }}" class="btn btn-primary">
                                            <i class="bx bx-line-chart me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan -->


                            <!-- Sales by Salesperson Report -->
                            @can('view staff performance')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-user-check fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Sales by Salesperson</h5>
                                        <p class="card-text">Sales staff performance evaluation</p>
                                        <a href="{{ route('sales.reports.sales-by-salesperson') }}" class="btn btn-info">
                                            <i class="bx bx-user-check me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Discount Effectiveness Report -->
                            @can('view sales reports')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-discount fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Discount Effectiveness</h5>
                                        <p class="card-text">Impact of discounts and promotions</p>
                                        <a href="{{ route('sales.reports.discount-effectiveness') }}" class="btn btn-warning">
                                            <i class="bx bx-discount me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Sales Return Report -->
                            @can('view sales reports')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-undo fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Sales Return</h5>
                                        <p class="card-text">Returned sales tracking and analysis</p>
                                        <a href="{{ route('sales.reports.sales-return') }}" class="btn btn-danger">
                                            <i class="bx bx-undo me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Profitability by Product Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-dollar-circle fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Profitability by Product</h5>
                                        <p class="card-text">Product and customer profitability analysis</p>
                                        <a href="{{ route('sales.reports.profitability-by-product') }}" class="btn btn-primary">
                                            <i class="bx bx-dollar-circle me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Receivables Aging Report -->
                            @can('view collection report')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-time-five fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Receivables Aging</h5>
                                        <p class="card-text">Outstanding invoices by aging buckets</p>
                                        <a href="{{ route('sales.reports.receivables-aging') }}" class="btn btn-success">
                                            <i class="bx bx-time-five me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Collection Efficiency Report -->
                            <!-- @can('view collection report')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-credit-card fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Collection Efficiency</h5>
                                        <p class="card-text">Collection speed and efficiency metrics</p>
                                        <a href="{{ route('sales.reports.collection-efficiency') }}" class="btn btn-info">
                                            <i class="bx bx-credit-card me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan -->

                            <!-- Invoice Register Report -->
                            @can('view sales reports')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-receipt fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Invoice Register</h5>
                                        <p class="card-text">All invoices issued in a period</p>
                                        <a href="{{ route('sales.reports.invoice-register') }}" class="btn btn-warning">
                                            <i class="bx bx-receipt me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Customer Statement Report -->
                            @can('view customer reports')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-file-blank fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Customer Statement</h5>
                                        <p class="card-text">Customer transaction summaries</p>
                                        <a href="{{ route('sales.reports.customer-statement') }}" class="btn btn-danger">
                                            <i class="bx bx-file-blank me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Paid Invoice Report -->
                            @can('view sales reports')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-check-circle fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Paid Invoice</h5>
                                        <p class="card-text">Fully paid invoices tracking</p>
                                        <a href="{{ route('sales.reports.paid-invoice') }}" class="btn btn-primary">
                                            <i class="bx bx-check-circle me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Credit Note Report -->
                            @can('view sales reports')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-credit-card-alt fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Credit Note</h5>
                                        <p class="card-text">All credit notes issued tracking</p>
                                        <a href="{{ route('sales.reports.credit-note') }}" class="btn btn-success">
                                            <i class="bx bx-credit-card-alt me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Tax Invoice Report -->
                            @can('view sales reports')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-receipt fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Tax Invoice</h5>
                                        <p class="card-text">VAT/tax reporting summaries</p>
                                        <a href="{{ route('sales.reports.tax-invoice') }}" class="btn btn-info">
                                            <i class="bx bx-receipt me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
