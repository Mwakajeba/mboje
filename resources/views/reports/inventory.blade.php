@extends('layouts.main')

@section('title', 'Inventory Reports')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-file'],
                ['label' => 'Inventory Reports', 'url' => '#', 'icon' => 'bx bx-package']
            ]" />
            <h6 class="mb-0 text-uppercase">INVENTORY REPORTS</h6>
            <hr />

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Inventory Reports</h4>
                            <p class="text-muted">Comprehensive inventory reports and analytics will be implemented here.</p>
                            
                            <div class="row">
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-primary">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-package fs-1 text-primary"></i>
                                            </div>
                                            <h5 class="card-title">Stock Summary Report</h5>
                                            <p class="card-text">Overview of current stock levels, values and inventory status.</p>
                                            <button class="btn btn-primary" disabled>
                                                <i class="bx bx-file me-1"></i> Coming Soon
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-success">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-trending-up fs-1 text-success"></i>
                                            </div>
                                            <h5 class="card-title">Stock Movement Report</h5>
                                            <p class="card-text">Track inventory movements, adjustments and transfers.</p>
                                            <button class="btn btn-success" disabled>
                                                <i class="bx bx-file me-1"></i> Coming Soon
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-warning">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-bar-chart fs-1 text-warning"></i>
                                            </div>
                                            <h5 class="card-title">Inventory Analytics Report</h5>
                                            <p class="card-text">Advanced inventory analytics, trends and cost analysis.</p>
                                            <button class="btn btn-warning" disabled>
                                                <i class="bx bx-file me-1"></i> Coming Soon
                                            </button>
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