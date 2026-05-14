@extends('layouts.main')

@section('title', 'Inventory Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Management', 'url' => '#', 'icon' => 'bx bx-package']
        ]" />
        <h6 class="mb-0 text-uppercase">INVENTORY MANAGEMENT</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

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
                            <!-- Categories Management -->
                            @can('manage inventory categories')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                            {{ $categoriesCount }}
                                            <span class="visually-hidden">categories count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-category fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Categories</h5>
                                        <p class="card-text">Manage inventory categories and product classifications.</p>
                                        <a href="{{ route('inventory.categories.index') }}" class="btn btn-primary">
                                            <i class="bx bx-list-ul me-1"></i> Manage Categories
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Items Management -->
                            @can('manage inventory items')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            {{ $itemsCount }}
                                            <span class="visually-hidden">items count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-box fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Inventory Items</h5>
                                        <p class="card-text">Manage products, services, and inventory items.</p>
                                        <a href="{{ route('inventory.items.index') }}" class="btn btn-success">
                                            <i class="bx bx-package me-1"></i> Manage Items
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Movements Management -->
                            @can('view inventory adjustments')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                                            {{ $movementsCount }}
                                            <span class="visually-hidden">movements count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-transfer fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Stock Movements</h5>
                                        <p class="card-text">Track stock movements, receipts, and adjustments.</p>
                                        <a href="{{ route('inventory.movements.index') }}" class="btn btn-warning">
                                            <i class="bx bx-transfer-alt me-1"></i> Manage Movements
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Adjustments Management -->
                            @can('manage inventory movements')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            {{ $adjustmentsCount ?? 0 }}
                                            <span class="visually-hidden">adjustments count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-adjust fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Stock Adjustments</h5>
                                        <p class="card-text">Make inventory adjustments and corrections.</p>
                                        @can('create inventory adjustments')
                                        <a href="{{ route('inventory.movements.create') }}?defaultMovementType=adjustment_in" class="btn btn-danger">
                                            <i class="bx bx-adjust me-1"></i> Make Adjustment
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Transfers Management -->
                            @can('view inventory transfer')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            {{ $transfersCount ?? 0 }}
                                            <span class="visually-hidden">transfers count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-transfer fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Branch Transfers</h5>
                                        <p class="card-text">Transfer items between branches.</p>
                                        <a href="{{ route('inventory.transfers.index') }}" class="btn btn-info">
                                            <i class="bx bx-list-ul me-1"></i> Manage Transfers
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Write-offs Management -->
                            @can('view inventory write-offs')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-dark position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark">
                                            {{ $writeOffsCount ?? 0 }}
                                            <span class="visually-hidden">write-offs count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-x-circle fs-1 text-dark"></i>
                                        </div>
                                        <h5 class="card-title">Write-offs &amp; Stock-outs</h5>
                                        <p class="card-text">Record damaged, expired, or internally consumed inventory.</p>
                                        <a href="{{ route('inventory.write-offs.index') }}" class="btn btn-dark">
                                            <i class="bx bx-list-ul me-1"></i> Manage Records
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Transfer Requests Management -->
                          

                            <!-- Locations Management -->
                            @can('manage inventory locations')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            {{ $locationsCount }}
                                            <span class="visually-hidden">locations count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-building fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Locations</h5>
                                        <p class="card-text">Manage warehouse locations and storage areas.</p>
                                        <a href="{{ route('settings.inventory.locations.index') }}" class="btn btn-info">
                                            <i class="bx bx-building me-1"></i> Manage Locations
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Opening Balance -->
                            @can('manage inventory opening balances')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-layer-plus fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Opening Balance</h5>
                                        <p class="card-text">Initialize starting stock by location. Applies to current login location.</p>
                                        <a href="{{ route('inventory.opening-balances.index') }}" class="btn btn-warning">
                                            <i class="bx bx-list-ul me-1"></i> View Opening Balances
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Inventory Count -->
                            @can('manage inventory locations')
                             <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-purple position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-purple">
                                            {{ $countSessionsCount ?? 0 }}
                                            <span class="visually-hidden">locations count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-message-square-dots fs-1 text-purple"></i>
                                        </div>
                                        <h5 class="card-title">Stock Taking/Count</h5>
                                        <p class="card-text">Take stock of inventory items and locations.</p>
                                        <a href="{{ route('inventory.counts.index') }}" class="btn btn-info">
                                            <i class="bx bx-building me-1"></i> Manage Locations
                                        </a>
                                    </div>
                                </div>
                            </div> 
                             @endcan 

                            <!-- Inventory Settings -->
                            @can('manage inventory settings')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-secondary position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary">
                                            1
                                            <span class="visually-hidden">settings</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-cog fs-1 text-secondary"></i>
                                        </div>
                                        <h5 class="card-title">Inventory Settings</h5>
                                        <p class="card-text">Configure inventory system settings and preferences.</p>
                                        <a href="{{ route('settings.inventory') }}" class="btn btn-secondary">
                                            <i class="bx bx-cog me-1"></i> Configure
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan
                        </div>

                        <!-- Recent Movements Overview -->
                        @if($recentMovements->count() > 0)
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="bx bx-time-five me-2"></i>Recent Stock Movements
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Item</th>
                                                        <th>Movement Type</th>
                                                        <th>Quantity</th>
                                                        <th>User</th>
                                                        <th>Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($recentMovements as $movement)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $movement->item->name }}</strong>
                                                            <br>
                                                            <small class="text-muted">{{ $movement->item->code }}</small>
                                                        </td>
                                                        <td>
                                                            @php
                                                                $typeClasses = [
                                                                    'opening_balance' => 'bg-primary',
                                                                    'opening_balance' => 'bg-primary',
                                                                    'transfer_in' => 'bg-success',
                                                                    'transfer_out' => 'bg-info',
                                                                    'sold' => 'bg-danger',
                                                                    'purchased' => 'bg-success',
                                                                    'adjustment_in' => 'bg-warning',
                                                                    'adjustment_out' => 'bg-secondary',
                                                                    'write_off' => ($movement->writeoff_type === 'stock_out' ? 'bg-warning text-dark' : 'bg-dark')
                                                                ];
                                                                $typeLabels = [
                                                                    'opening_balance' => 'Opening Balance',
                                                                    'transfer_in' => 'Transfer In',
                                                                    'transfer_out' => 'Transfer Out',
                                                                    'sold' => 'Sold',
                                                                    'purchased' => 'Purchased',
                                                                    'adjustment_in' => 'Adjustment In',
                                                                    'adjustment_out' => 'Adjustment Out',
                                                                    'write_off' => ($movement->writeoff_type === 'stock_out' ? 'Stock-out' : 'Write-off')
                                                                ];
                                                            @endphp
                                                            <span class="badge {{ $typeClasses[$movement->movement_type] ?? 'bg-secondary' }}">
                                                                {{ $typeLabels[$movement->movement_type] ?? ucfirst($movement->movement_type) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="fw-bold">{{ number_format($movement->quantity, 2) }}</span>
                                                            {{ $movement->item->unit_of_measure ?? 'units' }}
                                                        </td>
                                                        <td>{{ $movement->user->name }}</td>
                                                        <td>{{ $movement->created_at->format('M j, Y H:i') }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="text-center mt-3">
                                            <a href="{{ route('inventory.movements.index') }}" class="btn btn-outline-primary">
                                                <i class="bx bx-list-ul me-1"></i> View All Movements
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end page wrapper -->
<!--start overlay-->
<div class="overlay toggle-icon"></div>
<!--end overlay-->
<!--Start Back To Top Button--> <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
<!--End Back To Top Button-->
<footer class="page-footer">
    <p class="mb-0">Copyright © {{ date('Y') }}. All right reserved. -- By SAFCO FINTECH</p>
</footer>

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

    .badge {
        font-size: 0.75rem;
        padding: 0.5em 0.75em;
    }

    .fs-1 {
        font-size: 3rem !important;
    }

    /* Notification badge positioning */
    .position-relative .badge {
        z-index: 10;
        font-size: 0.7rem;
        min-width: 1.5rem;
        height: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid white;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    .border-primary { border-color: #0d6efd !important; }
    .border-success { border-color: #198754 !important; }
    .border-warning { border-color: #ffc107 !important; }
    .border-info { border-color: #0dcaf0 !important; }
    .border-danger { border-color: #dc3545 !important; }
    .border-secondary { border-color: #6c757d !important; }
    .border-purple { border-color: #6f42c1 !important; }
    
    .text-purple { color: #6f42c1 !important; }
    .bg-purple { background-color: #6f42c1 !important; }
    .btn-purple { 
        background-color: #6f42c1; 
        border-color: #6f42c1; 
        color: white; 
    }
    .btn-purple:hover { 
        background-color: #5a32a3; 
        border-color: #5a32a3; 
        color: white; 
    }
</style>
@endpush
