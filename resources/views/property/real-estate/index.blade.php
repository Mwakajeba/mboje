@extends('layouts.main')

@section('title', 'Real Estate Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel & Property Management', 'url' => '#', 'icon' => 'bx bx-building-house'],
            ['label' => 'Real Estate', 'url' => '#', 'icon' => 'bx bx-building']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Real Estate Management Dashboard</h4>
                        <p class="card-subtitle text-muted">Manage your properties, leases, and tenants</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded bg-primary bg-gradient">
                                    <div class="avatar-title text-white">
                                        <i class="bx bx-building font-size-24"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-uppercase fw-medium text-muted mb-0">Total Properties</p>
                                <h4 class="mb-0">{{ number_format($totalProperties) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded bg-success bg-gradient">
                                    <div class="avatar-title text-white">
                                        <i class="bx bx-home font-size-24"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-uppercase fw-medium text-muted mb-0">Total Units</p>
                                <h4 class="mb-0">{{ number_format($totalUnits) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded bg-info bg-gradient">
                                    <div class="avatar-title text-white">
                                        <i class="bx bx-file font-size-24"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-uppercase fw-medium text-muted mb-0">Active Leases</p>
                                <h4 class="mb-0">{{ number_format($activeLeases) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded bg-warning bg-gradient">
                                    <div class="avatar-title text-white">
                                        <i class="bx bx-trending-up font-size-24"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-uppercase fw-medium text-muted mb-0">Occupancy Rate</p>
                                <h4 class="mb-0">{{ number_format($occupancyRate, 1) }}%</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Management Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-building text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="card-title">Property Management</h5>
                        <p class="text-muted">Manage properties and units</p>
                        <a href="{{ route('properties.index') }}" class="btn btn-primary">
                            <i class="bx bx-plus me-1"></i>Manage Properties
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-file text-success" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="card-title">Lease Management</h5>
                        <p class="text-muted">Handle lease agreements and renewals</p>
                        <a href="{{ route('leases.index') }}" class="btn btn-success">
                            <i class="bx bx-plus me-1"></i>Manage Leases
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-user text-info" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="card-title">Tenant Management</h5>
                        <p class="text-muted">Manage tenant information and history</p>
                        <a href="{{ route('tenants.index') }}" class="btn btn-info">
                            <i class="bx bx-plus me-1"></i>Manage Tenants
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-bar-chart text-warning" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="card-title">Property Reports</h5>
                        <p class="text-muted">Revenue, occupancy, and performance</p>
                        <a href="{{ route('property.reports.index') }}" class="btn btn-warning">
                            <i class="bx bx-chart me-1"></i>View Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats and Alerts -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Monthly Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <h4 class="text-primary">{{ number_format($totalTenants) }}</h4>
                                <p class="text-muted mb-0">Total Tenants</p>
                            </div>
                            <div class="col-6">
                                <h4 class="text-success">{{ number_format($monthlyRentIncome, 2) }} TZS</h4>
                                <p class="text-muted mb-0">Monthly Rent Income</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Lease Alerts</h5>
                    </div>
                    <div class="card-body">
                        @if($expiringLeases->count() > 0)
                            <div class="alert alert-warning">
                                <i class="bx bx-info-circle me-2"></i>
                                <strong>{{ $expiringLeases->count() }}</strong> lease(s) expiring within 30 days
                            </div>
                            <div class="mt-2">
                                @foreach($expiringLeases->take(3) as $lease)
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small>{{ $lease->property->name ?? 'Property' }} - {{ $lease->tenant->full_name }}</small>
                                        <small class="text-muted">{{ $lease->end_date->format('M d, Y') }}</small>
                                    </div>
                                @endforeach
                                @if($expiringLeases->count() > 3)
                                    <small class="text-muted">... and {{ $expiringLeases->count() - 3 }} more</small>
                                @endif
                            </div>
                        @else
                            <div class="text-center text-muted">
                                <i class="bx bx-check-circle text-success" style="font-size: 2rem;"></i>
                                <p class="mt-2 mb-0">No leases expiring soon</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
