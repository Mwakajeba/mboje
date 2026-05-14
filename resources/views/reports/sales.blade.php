@extends('layouts.main')

@section('title', 'Sales Reports')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Sales Reports', 'url' => '#', 'icon' => 'bx bx-shopping-bag']
        ]" />
        <h6 class="mb-0 text-uppercase">SALES REPORTS</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Sales Reports</h4>

                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bx bx-check-circle me-2"></i>
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if(isset($errors) && $errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bx bx-error-circle me-2"></i>
                                Please fix the following errors:
                                <ul class="mb-0 mt-2">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <div class="row">
                            <!-- Proforma Summary Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-file-blank fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Proforma Summary Report</h5>
                                        <p class="card-text">Generate comprehensive proforma reports with status analysis and performance metrics.</p>
                                        <a href="#" class="btn btn-primary" onclick="alert('Proforma Summary Report coming soon!')">
                                            <i class="bx bx-file me-1"></i> Generate Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Sales Performance Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-trending-up fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Sales Performance Report</h5>
                                        <p class="card-text">Analyze sales performance trends, growth rates, and key performance indicators.</p>
                                        <a href="#" class="btn btn-success" onclick="alert('Sales Performance Report coming soon!')">
                                            <i class="bx bx-file me-1"></i> Generate Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Customer Sales Analysis -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-group fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Customer Sales Analysis</h5>
                                        <p class="card-text">Analyze customer buying patterns, top customers, and customer segmentation.</p>
                                        <a href="#" class="btn btn-info" onclick="alert('Customer Sales Analysis coming soon!')">
                                            <i class="bx bx-file me-1"></i> Generate Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Revenue Analysis Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-money fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Revenue Analysis Report</h5>
                                        <p class="card-text">Track revenue streams, analyze profitability, and identify growth opportunities.</p>
                                        <a href="#" class="btn btn-warning" onclick="alert('Revenue Analysis Report coming soon!')">
                                            <i class="bx bx-file me-1"></i> Generate Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Sales by Period Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-calendar fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Sales by Period Report</h5>
                                        <p class="card-text">Generate monthly, quarterly, and annual sales reports with period comparisons.</p>
                                        <a href="#" class="btn btn-danger" onclick="alert('Sales by Period Report coming soon!')">
                                            <i class="bx bx-file me-1"></i> Generate Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Product Sales Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-secondary">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-package fs-1 text-secondary"></i>
                                        </div>
                                        <h5 class="card-title">Product Sales Report</h5>
                                        <p class="card-text">Analyze product performance, best sellers, and inventory turnover rates.</p>
                                        <a href="#" class="btn btn-secondary" onclick="alert('Product Sales Report coming soon!')">
                                            <i class="bx bx-file me-1"></i> Generate Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Sales Commission Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-purple">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-user-check fs-1 text-purple"></i>
                                        </div>
                                        <h5 class="card-title">Sales Commission Report</h5>
                                        <p class="card-text">Calculate and track sales commissions for sales representatives and teams.</p>
                                        <a href="#" class="btn btn-purple" onclick="alert('Sales Commission Report coming soon!')">
                                            <i class="bx bx-file me-1"></i> Generate Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Sales Forecast Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-teal">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-line-chart fs-1 text-teal"></i>
                                        </div>
                                        <h5 class="card-title">Sales Forecast Report</h5>
                                        <p class="card-text">Generate sales forecasts based on historical data and market trends.</p>
                                        <a href="#" class="btn btn-teal" onclick="alert('Sales Forecast Report coming soon!')">
                                            <i class="bx bx-file me-1"></i> Generate Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Sales Pipeline Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-indigo">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-funnel fs-1 text-indigo"></i>
                                        </div>
                                        <h5 class="card-title">Sales Pipeline Report</h5>
                                        <p class="card-text">Track sales opportunities through the pipeline stages and conversion rates.</p>
                                        <a href="#" class="btn btn-indigo" onclick="alert('Sales Pipeline Report coming soon!')">
                                            <i class="bx bx-file me-1"></i> Generate Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Sales Territory Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-orange">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-map fs-1 text-orange"></i>
                                        </div>
                                        <h5 class="card-title">Sales Territory Report</h5>
                                        <p class="card-text">Analyze sales performance by geographic territories and regions.</p>
                                        <a href="#" class="btn btn-orange" onclick="alert('Sales Territory Report coming soon!')">
                                            <i class="bx bx-file me-1"></i> Generate Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Sales Comparison Report -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-pink">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-bar-chart-alt-2 fs-1 text-pink"></i>
                                        </div>
                                        <h5 class="card-title">Sales Comparison Report</h5>
                                        <p class="card-text">Compare sales performance across different periods, products, or regions.</p>
                                        <a href="#" class="btn btn-pink" onclick="alert('Sales Comparison Report coming soon!')">
                                            <i class="bx bx-file me-1"></i> Generate Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Sales Dashboard -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-dark">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-dashboard fs-1 text-dark"></i>
                                        </div>
                                        <h5 class="card-title">Sales Dashboard</h5>
                                        <p class="card-text">Interactive dashboard with real-time sales metrics and key performance indicators.</p>
                                        <a href="{{ route('sales.index') }}" class="btn btn-dark">
                                            <i class="bx bx-show me-1"></i> View Dashboard
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Currency Reports -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-dollar-circle fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Currency Reports</h5>
                                        <p class="card-text">Multi-currency sales summary, comparisons, and exchange rate analysis.</p>
                                        <a href="{{ route('reports.currency.index') }}" class="btn btn-primary">
                                            <i class="bx bx-right-arrow-alt me-1"></i> Open Currency Reports
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .fs-1 {
        font-size: 3rem !important;
    }

    .border-primary { border-color: #0d6efd !important; }
    .border-success { border-color: #198754 !important; }
    .border-info { border-color: #0dcaf0 !important; }
    .border-warning { border-color: #ffc107 !important; }
    .border-danger { border-color: #dc3545 !important; }
    .border-secondary { border-color: #6c757d !important; }
    .border-purple { border-color: #6f42c1 !important; }
    .border-teal { border-color: #20c997 !important; }
    .border-indigo { border-color: #6610f2 !important; }
    .border-orange { border-color: #fd7e14 !important; }
    .border-pink { border-color: #e83e8c !important; }
    .border-dark { border-color: #212529 !important; }

    .text-purple { color: #6f42c1 !important; }
    .text-teal { color: #20c997 !important; }
    .text-indigo { color: #6610f2 !important; }
    .text-orange { color: #fd7e14 !important; }
    .text-pink { color: #e83e8c !important; }

    .bg-purple { background-color: #6f42c1 !important; }
    .bg-teal { background-color: #20c997 !important; }
    .bg-indigo { background-color: #6610f2 !important; }
    .bg-orange { background-color: #fd7e14 !important; }
    .bg-pink { background-color: #e83e8c !important; }

    .btn-purple { background-color: #6f42c1; border-color: #6f42c1; color: white; }
    .btn-teal { background-color: #20c997; border-color: #20c997; color: white; }
    .btn-indigo { background-color: #6610f2; border-color: #6610f2; color: white; }
    .btn-orange { background-color: #fd7e14; border-color: #fd7e14; color: white; }
    .btn-pink { background-color: #e83e8c; border-color: #e83e8c; color: white; }

    .btn-purple:hover { background-color: #5a32a3; border-color: #5a32a3; color: white; }
    .btn-teal:hover { background-color: #1a9f7a; border-color: #1a9f7a; color: white; }
    .btn-indigo:hover { background-color: #520dc2; border-color: #520dc2; color: white; }
    .btn-orange:hover { background-color: #e8690b; border-color: #e8690b; color: white; }
    .btn-pink:hover { background-color: #d63384; border-color: #d63384; color: white; }
</style>
@endpush 