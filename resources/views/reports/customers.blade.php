@extends('layouts.main')

@section('title', 'Customer Reports')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-file'],
                ['label' => 'Customer Reports', 'url' => '#', 'icon' => 'bx bx-group']
            ]" />
            <h6 class="mb-0 text-uppercase">CUSTOMER REPORTS</h6>
            <hr />

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Customer Reports</h4>
                            <p class="text-muted">Customer reports functionality will be implemented here.</p>
                            
                            <div class="row">
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-primary">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-group fs-1 text-primary"></i>
                                            </div>
                                            <h5 class="card-title">Customer List Report</h5>
                                            <p class="card-text">Complete list of all customers with their details.</p>
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
                                                <i class="bx bx-user-check fs-1 text-success"></i>
                                            </div>
                                            <h5 class="card-title">Customer Activity Report</h5>
                                            <p class="card-text">Track customer activities and transaction history.</p>
                                            <button class="btn btn-success" disabled>
                                                <i class="bx bx-file me-1"></i> Coming Soon
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-info">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-trending-up fs-1 text-info"></i>
                                            </div>
                                            <h5 class="card-title">Customer Growth Report</h5>
                                            <p class="card-text">Analyze customer acquisition and growth trends.</p>
                                            <button class="btn btn-info" disabled>
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