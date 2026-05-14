@extends('layouts.main')

@section('title', 'Sales Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales Management', 'url' => '#', 'icon' => 'bx bx-shopping-bag']
        ]" />
        <h6 class="mb-0 text-uppercase">SALES MANAGEMENT</h6>
        <hr />

        <!-- Sales Statistics -->
        <div class="row">
            <div class="col-12 col-lg-8">
                <div class="card border-top border-0 border-4 border-primary">
                    <div class="card-body p-5">
                        <div class="card-title d-flex align-items-center">
                            <div><i class="bx bx-shopping-bag me-1 font-22 text-primary"></i></div>
                            <h5 class="mb-0 text-primary">Sales Statistics</h5>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card radius-10 bg-primary">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">Total Proformas</p>
                                                <h4 class="text-white">{{ \App\Models\Sales\SalesProforma::forBranch(auth()->user()->branch_id)->count() }}</h4>
                                            </div>
                                            <div class="ms-auto fs-1 text-white"><i class="bx bx-file-blank"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card radius-10 bg-success">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">Accepted Orders</p>
                                                <h4 class="text-white">{{ \App\Models\Sales\SalesOrder::forBranch(auth()->user()->branch_id)->where('status', 'approved')->count() }}</h4>
                                            </div>
                                            <div class="ms-auto fs-1 text-white"><i class="bx bx-check-circle"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card radius-10 bg-warning">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">Draft Items</p>
                                                <h4 class="text-white">{{ 
                                                    \App\Models\Sales\SalesProforma::forBranch(auth()->user()->branch_id)->where('status', 'draft')->count() +
                                                    \App\Models\Sales\SalesOrder::forBranch(auth()->user()->branch_id)->where('status', 'draft')->count() +
                                                    \App\Models\Sales\SalesInvoice::forBranch(auth()->user()->branch_id)->where('status', 'draft')->count() +
                                                    \App\Models\Sales\Delivery::forBranch(auth()->user()->branch_id)->where('status', 'draft')->count() +
                                                    \App\Models\Sales\CreditNote::forBranch(auth()->user()->branch_id)->where('status', 'draft')->count()
                                                }}</h4>
                                                <small class="text-white" style="font-size: 0.8rem; color: white !important;">
                                                    P: {{ \App\Models\Sales\SalesProforma::forBranch(auth()->user()->branch_id)->where('status', 'draft')->count() }} | 
                                                    O: {{ \App\Models\Sales\SalesOrder::forBranch(auth()->user()->branch_id)->where('status', 'draft')->count() }} | 
                                                    I: {{ \App\Models\Sales\SalesInvoice::forBranch(auth()->user()->branch_id)->where('status', 'draft')->count() }} | 
                                                    D: {{ \App\Models\Sales\Delivery::forBranch(auth()->user()->branch_id)->where('status', 'draft')->count() }} | 
                                                    C: {{ \App\Models\Sales\CreditNote::forBranch(auth()->user()->branch_id)->where('status', 'draft')->count() }}
                                                </small>
                                            </div>
                                            <div class="ms-auto fs-1 text-white"><i class="bx bx-edit"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card radius-10 bg-info">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">This Month</p>
                                                <h4 class="text-white">{{ \App\Models\Sales\SalesProforma::forBranch(auth()->user()->branch_id)->whereMonth('proforma_date', now()->month)->count() }}</h4>
                                            </div>
                                            <div class="ms-auto fs-1 text-white"><i class="bx bx-calendar"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card radius-10 bg-success">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">Total Invoices</p>
                                                <h4 class="text-white">{{ \App\Models\Sales\SalesInvoice::forBranch(auth()->user()->branch_id)->count() }}</h4>
                                            </div>
                                            <div class="ms-auto fs-1 text-white"><i class="bx bx-calendar"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card radius-10 bg-info">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">Paid Invoices</p>
                                                <h4 class="text-white">{{ \App\Models\Sales\SalesInvoice::forBranch(auth()->user()->branch_id)->where('status', 'paid')->count() }}</h4>
                                            </div>
                                            <div class="ms-auto fs-1 text-white"><i class="bx bx-calendar"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                             <div class="col-md-3">
                                <div class="card radius-10 bg-warning">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">Unpaid Invoices</p>
                                            <h4 class="text-white">{{ \App\Models\Sales\SalesInvoice::forBranch(auth()->user()->branch_id)->where('balance_due', '>', 0)->count() }}</h4>
                                            </div>
                                            <div class="ms-auto fs-1 text-white"><i class="bx bx-calendar"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                             <div class="col-md-3">
                                <div class="card radius-10 bg-primary">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">This Month</p>
                                            <h4 class="text-white">{{ \App\Models\Sales\SalesInvoice::forBranch(auth()->user()->branch_id)->whereMonth('created_at', now()->month)->count() }}</h4>
                                            </div>
                                            <div class="ms-auto fs-1 text-white"><i class="bx bx-calendar"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="card border-top border-0 border-4 border-success">
                    <div class="card-body p-5">
                        <div class="card-title d-flex align-items-center">
                            <div><i class="bx bx-trending-up me-1 font-22 text-success"></i></div>
                            <h5 class="mb-0 text-success">Sales Analytics</h5>
                        </div>
                        <hr>
                        <div class="d-grid gap-2">
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <h4 class="text-success mb-1">{{ \App\Models\Sales\SalesInvoice::forBranch(auth()->user()->branch_id)->whereMonth('created_at', now()->month)->count() }}</h4>
                                    <small class="text-muted">Invoices</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-primary mb-1">{{ \App\Models\Sales\SalesOrder::forBranch(auth()->user()->branch_id)->whereMonth('created_at', now()->month)->count() }}</h4>
                                    <small class="text-muted">Orders</small>
                                </div>
                            </div>
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <h4 class="text-success mb-1">{{ \App\Models\Sales\CashSale::forBranch(auth()->user()->branch_id)->whereMonth('created_at', now()->month)->count() }}</h4>
                                    <small class="text-muted">Cash Sales</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-info mb-1">{{ \App\Models\Sales\PosSale::forBranch(auth()->user()->branch_id)->whereMonth('created_at', now()->month)->count() }}</h4>
                                    <small class="text-muted">POS Sales</small>
                                </div>
                            </div>
                            <div class="text-center mb-3">
                                <h4 class="text-warning mb-1">{{ \App\Models\Sales\SalesProforma::forBranch(auth()->user()->branch_id)->whereMonth('created_at', now()->month)->count() }}</h4>
                                <small class="text-muted">Proformas This Month</small>
                            </div>
                            <hr>
                            @can('view sales reports')
                            <a href="{{ route('sales.reports.index') }}" class="btn btn-success">
                                <i class="bx bx-bar-chart me-1"></i> View Sales Reports
                            </a>
                            @endcan
                            @can('view customers')
                            <a href="{{ route('customers.index') }}" class="btn btn-outline-success">
                                <i class="bx bx-group me-1"></i> Customer Analytics
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Flow Modules -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-center">
                            <div><i class="bx bx-grid me-1 font-22 text-primary"></i></div>
                            <h5 class="mb-0 text-primary">Sales Flow Management</h5>
                        </div>
                        <hr>
                        <div class="row">
                            <!-- 1. Customer Master Data -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                            {{ \App\Models\Customer::forBranch(auth()->user()->branch_id)->count() }}
                                            <span class="visually-hidden">customers count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-group fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Customer Master Data</h5>
                                        <p class="card-text">Register and manage customer details, contacts, and credit terms.</p>
                                        @can('view customers')
                                        <a href="{{ route('customers.index') }}" class="btn btn-primary">
                                            <i class="bx bx-list-ul me-1"></i> Manage Customers
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Sales Quote (Proforma) -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            {{ \App\Models\Sales\SalesProforma::forBranch(auth()->user()->branch_id)->count() }}
                                            <span class="visually-hidden">proformas count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-file-blank fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Sales Quote (Proforma)</h5>
                                        <p class="card-text">Issue quotations with prices, terms, and validity for customer review.</p>
                                        @can('view sales proformas')
                                        <a href="{{ route('sales.proformas.index') }}" class="btn btn-success">
                                            <i class="bx bx-list-ul me-1"></i> Manage Proformas
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Sales Order -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                                            {{ \App\Models\Sales\SalesOrder::forBranch(auth()->user()->branch_id)->count() }}
                                            <span class="visually-hidden">orders count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-shopping-bag fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Sales Order</h5>
                                        <p class="card-text">Convert accepted quotes into binding orders with inventory and credit checks.</p>
                                        @can('view sales orders')
                                        <a href="{{ route('sales.orders.index') }}" class="btn btn-warning">
                                            <i class="bx bx-shopping-bag me-1"></i> Manage Orders
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>

                            <!-- 4. Delivery / Shipment -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            {{ \App\Models\Sales\Delivery::forBranch(auth()->user()->branch_id)->count() }}
                                            <span class="visually-hidden">deliveries count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-package fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Delivery / Shipment</h5>
                                        <p class="card-text">Track pick, pack, and delivery with stock updates and delivery notes.</p>
                                        @can('view deliveries')
                                        <a href="{{ route('sales.deliveries.index') }}" class="btn btn-info">
                                            <i class="bx bx-package me-1"></i> Manage Deliveries
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>

                            <!-- 5. Sales Invoice -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            {{ \App\Models\Sales\SalesInvoice::forBranch(auth()->user()->branch_id)->count() }}
                                            <span class="visually-hidden">invoices count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-receipt fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Sales Invoice</h5>
                                        <p class="card-text">Create invoices with taxes, post to AR, and recognize revenue.</p>
                                        @can('view sales invoices')
                                        <a href="{{ route('sales.invoices.index') }}" class="btn btn-danger">
                                            <i class="bx bx-receipt me-1"></i> Manage Invoices
                                        </a>
                                        @endcan
                                        @can('create sales invoices')
                                        <a href="{{ route('sales.opening-balances.index') }}" class="btn btn-outline-secondary ms-2">
                                            <i class="bx bx-book-open me-1"></i> Invoice Opening Balance
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>

                            <!-- 6. Credit Note -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                            {{ \App\Models\Sales\CreditNote::forBranch(auth()->user()->branch_id)->count() }}
                                            <span class="visually-hidden">credit notes count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-undo fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Credit Note</h5>
                                        <p class="card-text">Issue credit notes for returns, discounts, and corrections.</p>
                                        @can('view credit notes')
                                        <a href="{{ route('sales.credit-notes.index') }}" class="btn btn-primary">
                                            <i class="bx bx-list-ul me-1"></i> Manage Credit Notes
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>

                            <!-- 7. Cash Sales -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            {{ \App\Models\Sales\CashSale::forBranch(auth()->user()->branch_id)->count() }}
                                            <span class="visually-hidden">cash sales count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-dollar-circle fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Cash Sales</h5>
                                        <p class="card-text">Process immediate cash transactions with instant payment collection.</p>
                                        @can('view cash sales')
                                        <a href="{{ route('sales.cash-sales.index') }}" class="btn btn-success">
                                            <i class="bx bx-list-ul me-1"></i> Manage Cash Sales
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>

                            <!-- 8. POS Sales -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            {{ \App\Models\Sales\PosSale::forBranch(auth()->user()->branch_id)->count() }}
                                            <span class="visually-hidden">pos sales count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-credit-card fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">POS Sales</h5>
                                        <p class="card-text">Point of sale transactions with multiple payment methods.</p>
                                        <a href="{{ route('sales.pos-sales.index') }}" class="btn btn-info">
                                            <i class="bx bx-list-ul me-1"></i> Manage POS Sales
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 9. SALES POS LIST -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-dark position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark">
                                            {{ \App\Models\Sales\PosSale::forBranch(auth()->user()->branch_id)->count() }}
                                            <span class="visually-hidden">pos sales count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-list-ul fs-1 text-dark"></i>
                                        </div>
                                        <h5 class="card-title">SALES POS LIST</h5>
                                        <p class="card-text">View, edit, and manage all POS sales transactions with full control.</p>
                                        <a href="{{ route('sales.pos.list') }}" class="btn btn-dark">
                                            <i class="bx bx-list-ul me-1"></i> View POS List
                                        </a>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Proformas -->
        @php
        $recentProformas = \App\Models\Sales\SalesProforma::with('customer')
        ->forBranch(auth()->user()->branch_id)
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
        @endphp

        @if($recentProformas->count() > 0)
        <div class="card">
            <div class="card-body">
                <div class="card-title d-flex align-items-center justify-content-between">
                    <div>
                        <i class="bx bx-time-five me-1 font-22 text-primary"></i>
                        <h5 class="mb-0 text-primary">Recent Proformas</h5>
                    </div>
                    @can('view sales proformas')
                    <a href="{{ route('sales.proformas.index') }}" class="btn btn-primary btn-sm">
                        <i class="bx bx-list-ul me-1"></i> View All Proformas
                    </a>
                    @endcan
                </div>
                <hr>
                <div class="table-responsive">
                    <table id="recent-proformas-table" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Proforma #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentProformas as $index => $proforma)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    @can('view sales proformas')
                                    <a href="{{ route('sales.proformas.show', $proforma->encoded_id) }}" class="text-primary fw-bold">
                                        {{ $proforma->proforma_number }}
                                    </a>
                                    @else
                                    <span class="text-muted fw-bold">{{ $proforma->proforma_number }}</span>
                                    @endcan
                                </td>
                                <td>{{ $proforma->customer->name ?? 'N/A' }}</td>
                                <td>{{ $proforma->proforma_date->format('M d, Y') }}</td>
                                <td class="text-end">TZS {{ number_format($proforma->total_amount, 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $proforma->status_badge_class }}">
                                        {{ ucfirst($proforma->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        @can('view sales proformas')
                                        <a href="{{ route('sales.proformas.show', $proforma->encoded_id) }}" class="btn btn-sm btn-outline-primary" title="View">
                                            <i class="bx bx-show"></i>
                                        </a>
                                        @endcan
                                        @can('edit sales proforma')
                                        <a href="{{ route('sales.proformas.edit', $proforma->encoded_id) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @else
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="text-muted">
                    <i class="bx bx-file-blank fs-1"></i>
                    <h5 class="mt-3">No Proformas Found</h5>
                    <p class="mb-4">Get started by creating your first sales proforma</p>
                    @can('create sales proforma')
                    <a href="{{ route('sales.proformas.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i> Create Your First Proforma
                    </a>
                    @endcan
                </div>
            </div>
        </div>
        @endif
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

    .border-primary {
        border-color: #0d6efd !important;
    }

    .border-success {
        border-color: #198754 !important;
    }

    .border-warning {
        border-color: #ffc107 !important;
    }

    .border-info {
        border-color: #0dcaf0 !important;
    }

    .border-danger {
        border-color: #dc3545 !important;
    }

    .border-secondary {
        border-color: #6c757d !important;
    }

    .border-dark {
        border-color: #212529 !important;
    }

    .border-purple {
        border-color: #6f42c1 !important;
    }

    .text-purple {
        color: #6f42c1 !important;
    }

    .bg-purple {
        background-color: #6f42c1 !important;
    }

</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        // Initialize DataTable for recent proformas
        if ($('#recent-proformas-table').length) {
            $('#recent-proformas-table').DataTable({
                responsive: true
                , order: [
                    [3, 'desc']
                ], // Sort by date descending
                pageLength: 5
                , searching: false
                , lengthChange: false
                , info: false
                , language: {
                    paginate: {
                        first: "First"
                        , last: "Last"
                        , next: "Next"
                        , previous: "Previous"
                    }
                }
            });
        }
    });

</script>
@endpush
