@extends('layouts.main')

@section('title', 'Inventory Reports')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Inventory Reports', 'url' => '#', 'icon' => 'bx bx-calculator']
            ]" />
        <h6 class="mb-0 text-uppercase">INVENTORY REPORTS</h6>
        <hr />
    <!-- Reports Grid -->
    <div class="row">
        <!-- Stock on Hand Report -->
         @can('view stock on hand report')
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-primary position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-package fs-1 text-primary"></i>
                    </div>
                    <h5 class="card-title">Stock on Hand</h5>
                    <p class="card-text">Current quantity and value per item, location, and overall.</p>
                    <a href="{{ route('inventory.reports.stock-on-hand') }}" class="btn btn-primary">
                        <i class="bx bx-package me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>
        @endcan
        <!-- Stock Valuation Report -->
        <!-- <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-info position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-calculator fs-1 text-info"></i>
                    </div>
                    <h5 class="card-title">Stock Valuation</h5>
                    <p class="card-text">Item value by costing method (FIFO/Weighted Avg) with totals per category/location.</p>
                    <a href="{{ route('inventory.reports.stock-valuation') }}" class="btn btn-info">
                        <i class="bx bx-calculator me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div> -->

        <!-- Movement Register Report -->
        @can('view movement register report')
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-success position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-transfer fs-1 text-success"></i>
                    </div>
                    <h5 class="card-title">Movement Register</h5>
                    <p class="card-text">All movements (purchased, sold, transfer, adjustment, returns) with dates, refs, users.</p>
                    <a href="{{ route('inventory.reports.movement-register') }}" class="btn btn-success">
                        <i class="bx bx-transfer me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>
        @endcan
        <!-- Aging Stock Report -->
        <!-- <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-warning position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-time fs-1 text-warning"></i>
                    </div>
                    <h5 class="card-title">Aging Stock</h5>
                    <p class="card-text">Days since last movement; flag slow/obsolete items.</p>
                    <a href="{{ route('inventory.reports.aging-stock') }}" class="btn btn-warning">
                        <i class="bx bx-time me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div> -->

        <!-- Reorder Report -->
        @can('view reorder report')
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-danger position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-shopping-bag fs-1 text-danger"></i>
                    </div>
                    <h5 class="card-title">Reorder Report</h5>
                    <p class="card-text">Items below minimum/reorder levels with suggested PO quantities.</p>
                    <a href="{{ route('inventory.reports.reorder') }}" class="btn btn-danger">
                        <i class="bx bx-shopping-bag me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>
        @endcan
        <!-- Over/Understock Report -->
         @can('view over understock report')
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-secondary position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-trending-up fs-1 text-secondary"></i>
                    </div>
                    <h5 class="card-title">Over/Understock Report</h5>
                    <p class="card-text">Compare current vs min/max; highlight exceptions.</p>
                    <a href="{{ route('inventory.reports.over-understock') }}" class="btn btn-secondary">
                        <i class="bx bx-trending-up me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>
        @endcan
        <!-- Item Ledger Report -->
         @can('view item ledger report')
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-dark position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-book fs-1 text-dark"></i>
                    </div>
                    <h5 class="card-title">Item Ledger (Kardex)</h5>
                    <p class="card-text">Per‑item chronological movements with running balance and unit cost.</p>
                    <a href="{{ route('inventory.reports.item-ledger') }}" class="btn btn-dark">
                        <i class="bx bx-book me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>
        @endcan
        <!-- Cost Changes Report -->
        <!-- <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-primary position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-edit fs-1 text-primary"></i>
                    </div>
                    <h5 class="card-title">Cost Changes</h5>
                    <p class="card-text">History of average cost/FIFO layer changes with reasons.</p>
                    <a href="{{ route('inventory.reports.cost-changes') }}" class="btn btn-primary">
                        <i class="bx bx-edit me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div> -->

        <!-- Stock Take Variance Report -->
        <!-- <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-info position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-check-square fs-1 text-info"></i>
                    </div>
                    <h5 class="card-title">Stock Take Variance</h5>
                    <p class="card-text">Physical count vs system, differences in qty/value, approvals.</p>
                    <a href="{{ route('inventory.reports.stock-take-variance') }}" class="btn btn-info">
                        <i class="bx bx-check-square me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div> -->

        <!-- Location Bin Report -->
        <!-- <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-success position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-map fs-1 text-success"></i>
                    </div>
                    <h5 class="card-title">Location Bin Report</h5>
                    <p class="card-text">Per‑location/bin quantities; empty/overfull bins.</p>
                    <a href="{{ route('inventory.reports.location-bin') }}" class="btn btn-success">
                        <i class="bx bx-map me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div> -->

        <!-- Category Brand Mix Report -->
        <!-- <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-warning position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-pie-chart fs-1 text-warning"></i>
                    </div>
                    <h5 class="card-title">Category/Brand Mix</h5>
                    <p class="card-text">Inventory value and units by category/brand; contribution analysis.</p>
                    <a href="{{ route('inventory.reports.category-brand-mix') }}" class="btn btn-warning">
                        <i class="bx bx-pie-chart me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div> -->

        <!-- Inventory Value Summary Report -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-success position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-bar-chart fs-1 text-success"></i>
                    </div>
                    <h5 class="card-title">Inventory Value Summary</h5>
                    <p class="card-text">Summarizes total inventory value by item, category, or location.</p>
                    <a href="{{ route('inventory.reports.inventory-value-summary') }}" class="btn btn-success">
                        <i class="bx bx-bar-chart me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>

        <!-- Inventory Quantity Summary Report -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-primary position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-list-ul fs-1 text-primary"></i>
                    </div>
                    <h5 class="card-title">Inventory Quantity Summary</h5>
                    <p class="card-text">Provides total stock counts by item, category, or location without valuation.</p>
                    <a href="{{ route('inventory.reports.inventory-quantity-summary') }}" class="btn btn-primary">
                        <i class="bx bx-list-ul me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>

        <!-- Inventory Profit Margin Report -->
          @can('view profit margin report')
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-info position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-trending-up fs-1 text-info"></i>
                    </div>
                    <h5 class="card-title">Inventory Profit Margin</h5>
                    <p class="card-text">Measures gross profit and margin per item, category, or location.</p>
                    <a href="{{ route('inventory.reports.inventory-profit-margin') }}" class="btn btn-info">
                        <i class="bx bx-trending-up me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>
        @endcan

        <!-- Inventory Price List Report -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-warning position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-tag fs-1 text-warning"></i>
                    </div>
                    <h5 class="card-title">Inventory Price List</h5>
                    <p class="card-text">Lists current selling price, cost, and markup per item.</p>
                    <a href="{{ route('inventory.reports.inventory-price-list') }}" class="btn btn-warning">
                        <i class="bx bx-tag me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>

        <!-- Inventory Costing Calculation Worksheet -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-secondary position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-calculator fs-1 text-secondary"></i>
                    </div>
                    <h5 class="card-title">Inventory Costing Calculation Worksheet</h5>
                    <p class="card-text">Detailed calculation of item cost using selected valuation method.</p>
                    <a href="{{ route('inventory.reports.inventory-costing-worksheet') }}" class="btn btn-secondary">
                        <i class="bx bx-calculator me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>

        <!-- Inventory Quantity by Location Report -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-dark position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-map fs-1 text-dark"></i>
                    </div>
                    <h5 class="card-title">Inventory Quantity by Location</h5>
                    <p class="card-text">Shows stock distribution across multiple warehouses/branches.</p>
                    <a href="{{ route('inventory.reports.inventory-quantity-by-location') }}" class="btn btn-dark">
                        <i class="bx bx-map me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>

        <!-- Inventory Transfer Movement Report -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-primary position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-transfer fs-1 text-primary"></i>
                    </div>
                    <h5 class="card-title">Inventory Transfer Movement</h5>
                    <p class="card-text">Track stock transferred between locations with dates, quantities, and value.</p>
                    <a href="{{ route('inventory.reports.inventory-transfer-movement') }}" class="btn btn-primary">
                        <i class="bx bx-transfer me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>

        <!-- Inventory Aging Report -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-danger position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-time fs-1 text-danger"></i>
                    </div>
                    <h5 class="card-title">Inventory Aging Report</h5>
                    <p class="card-text">Identify how long stock items have been in inventory to detect slow-moving items.</p>
                    <a href="{{ route('inventory.reports.inventory-aging') }}" class="btn btn-danger">
                        <i class="bx bx-time me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>

        <!-- Category Performance Report -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-success position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-pie-chart fs-1 text-success"></i>
                    </div>
                    <h5 class="card-title">Category Performance Report</h5>
                    <p class="card-text">Analyze profitability by product category.</p>
                    <a href="{{ route('inventory.reports.category-performance') }}" class="btn btn-success">
                        <i class="bx bx-pie-chart me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>

        <!-- Inventory Count Reports Section -->
        <div class="col-12 mt-4 mb-3">
            <h5 class="text-uppercase text-primary"><i class="bx bx-clipboard-check me-2"></i>Inventory Count Reports</h5>
            <hr>
        </div>

        <!-- Full Inventory Count Report -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-primary position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-clipboard fs-1 text-primary"></i>
                    </div>
                    <h5 class="card-title">Full Inventory Count Report</h5>
                    <p class="card-text">Complete listing of all count sessions with details, status, and entries.</p>
                    <a href="{{ route('inventory.reports.full-inventory-count') }}" class="btn btn-primary">
                        <i class="bx bx-clipboard me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>

        <!-- Variance Summary Report -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-info position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-bar-chart-alt-2 fs-1 text-info"></i>
                    </div>
                    <h5 class="card-title">Variance Summary Report</h5>
                    <p class="card-text">Summary statistics of all variances: zero, positive, negative, and high-value.</p>
                    <a href="{{ route('inventory.reports.variance-summary') }}" class="btn btn-info">
                        <i class="bx bx-bar-chart-alt-2 me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>

        <!-- Variance Value Report -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-warning position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-dollar fs-1 text-warning"></i>
                    </div>
                    <h5 class="card-title">Variance Value Report</h5>
                    <p class="card-text">Detailed variance values sorted by amount, showing financial impact.</p>
                    <a href="{{ route('inventory.reports.variance-value') }}" class="btn btn-warning">
                        <i class="bx bx-dollar me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>

        <!-- High-Value Items Scorecard -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-danger position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-trophy fs-1 text-danger"></i>
                    </div>
                    <h5 class="card-title">High-Value Items Scorecard</h5>
                    <p class="card-text">Items with high-value variances grouped by item with detailed statistics.</p>
                    <a href="{{ route('inventory.reports.high-value-scorecard') }}" class="btn btn-danger">
                        <i class="bx bx-trophy me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>

        <!-- Expiry & Damaged Stock Report -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-secondary position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-error-circle fs-1 text-secondary"></i>
                    </div>
                    <h5 class="card-title">Expiry & Damaged Stock Report</h5>
                    <p class="card-text">Items with damaged, expired, or obsolete conditions with expiry dates.</p>
                    <a href="{{ route('inventory.reports.expiry-damaged-stock') }}" class="btn btn-secondary">
                        <i class="bx bx-error-circle me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>

        <!-- Cycle Count Performance Report -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-success position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-trending-up fs-1 text-success"></i>
                    </div>
                    <h5 class="card-title">Cycle Count Performance Report</h5>
                    <p class="card-text">Performance metrics for cycle counts: completion rate, accuracy, and variance statistics.</p>
                    <a href="{{ route('inventory.reports.cycle-count-performance') }}" class="btn btn-success">
                        <i class="bx bx-trending-up me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>

        <!-- Year-end Stock Valuation Report -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-dark position-relative">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bx bx-file-blank fs-1 text-dark"></i>
                    </div>
                    <h5 class="card-title">Year-end Stock Valuation Report</h5>
                    <p class="card-text">IPSAS/IFRS compliant year-end inventory valuation with location breakdown.</p>
                    <a href="{{ route('inventory.reports.year-end-stock-valuation') }}" class="btn btn-dark">
                        <i class="bx bx-file-blank me-1"></i> View Report
                    </a>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
@endsection
