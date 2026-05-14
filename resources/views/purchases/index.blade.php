@extends('layouts.main')

@section('title', 'Purchase Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchase Management', 'url' => '#', 'icon' => 'bx bx-purchase-tag']
        ]" />
        <h6 class="mb-0 text-uppercase">PURCHASE MANAGEMENT</h6>
        <hr />

        <!-- Purchase Statistics -->
        <div class="row">
            <div class="col-12 col-lg-8">
                <div class="card border-top border-0 border-4 border-primary">
                    <div class="card-body p-5">
                        <div class="card-title d-flex align-items-center">
                            <div><i class="bx bx-purchase-tag me-1 font-22 text-primary"></i></div>
                            <h5 class="mb-0 text-primary">Purchase Statistics</h5>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card radius-10 bg-primary">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="">
                                                <p class="mb-1 text-white">Total Quotations</p>
                                                <h4 class="text-white">{{ \App\Models\Purchase\PurchaseQuotation::when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))->count() }}</h4>
                                                <small class="text-white-50">Branch: {{ auth()->user()->branch->name ?? 'N/A' }}</small>
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
                                                <p class="mb-1 text-white">Accepted</p>
                                                <h4 class="text-white">{{ \App\Models\Purchase\PurchaseQuotation::when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))->where('status', 'approved')->count() }}</h4>
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
                                                <p class="mb-1 text-white">Draft</p>
                                                <h4 class="text-white">{{ \App\Models\Purchase\PurchaseQuotation::when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))->where('status', 'draft')->count() }}</h4>
                                                <small class="text-white-50">Branch: {{ auth()->user()->branch->name ?? 'N/A' }}</small>
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
                                                <h4 class="text-white">{{ \App\Models\Purchase\PurchaseQuotation::when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))->whereMonth('start_date', now()->month)->whereYear('start_date', now()->year)->count() }}</h4>
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
                            <h5 class="mb-0 text-success">Purchase Analytics</h5>
                        </div>
                        <hr>
                        <div class="d-grid gap-2">
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <h4 class="text-success mb-1">{{ \App\Models\Bill::whereMonth('created_at', now()->month)->count() }}</h4>
                                    <small class="text-muted">Bills</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-primary mb-1">{{ \App\Models\Purchase\PurchaseOrder::when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))->whereMonth('order_date', now()->month)->whereYear('order_date', now()->year)->count() }}</h4>
                                    <small class="text-muted">Orders</small>
                                </div>
                            </div>
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    @php
                                        $cashPurchaseBranchId = session('branch_id') ?? auth()->user()->branch_id;
                                    @endphp
                                    <h4 class="text-success mb-1">{{ \App\Models\Purchase\CashPurchase::where('company_id', auth()->user()->company_id)->when($cashPurchaseBranchId, fn($q) => $q->where('branch_id', $cashPurchaseBranchId))->count() }}</h4>
                                    <small class="text-muted">Cash Purchases</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-info mb-1">{{ \App\Models\Purchase\GoodsReceipt::when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))->count() }}</h4>
                                    <small class="text-muted">Goods Receipts</small>
                                </div>
                            </div>
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <h4 class="text-warning mb-1">{{ \App\Models\Purchase\DebitNote::when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))->count() }}</h4>
                                    <small class="text-muted">Debit Notes</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-purple mb-1">{{ \App\Models\Bill::count() }}</h4>
                                    <small class="text-muted">Bills</small>
                                </div>
                            </div>
                            <div class="text-center mb-3">
                                <h4 class="text-warning mb-1">{{ \App\Models\Purchase\PurchaseQuotation::when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))->whereMonth('start_date', now()->month)->count() }}</h4>
                                <small class="text-muted">Quotations This Month</small>
                            </div>
                            <hr>
                            @can('view purchases')
                            <a href="{{ route('purchases.reports.index') }}" class="btn btn-primary">
                                <i class="bx bx-bar-chart me-1"></i> View Purchase Reports
                            </a>
                            @endcan
                            <a href="{{ route('accounting.bill-purchases') }}" class="btn btn-success">
                                <i class="bx bx-bar-chart me-1"></i> View Bill Reports
                            </a>
                            <a href="{{ route('accounting.suppliers.index') }}" class="btn btn-outline-success">
                                <i class="bx bx-group me-1"></i> Supplier Analytics
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Flow Modules -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-center">
                            <div><i class="bx bx-grid me-1 font-22 text-primary"></i></div>
                            <h5 class="mb-0 text-primary">Purchase Flow Management</h5>
                        </div>
                        <hr>
                        <div class="row">
                            <!-- 1. Supplier Master Data -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                            {{ \App\Models\Supplier::when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))->count() }}
                                            <span class="visually-hidden">suppliers count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-group fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Supplier Master Data</h5>
                                        <p class="card-text">Register and manage supplier details, contacts, and payment terms.</p>
                                        @can('view suppliers')
                                        <a href="{{ route('accounting.suppliers.index') }}" class="btn btn-primary">
                                            <i class="bx bx-list-ul me-1"></i> Manage Suppliers
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Purchase Requisitions -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-dark position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark">
                                            {{ \App\Models\Purchase\PurchaseRequisition::when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))->count() }}
                                            <span class="visually-hidden">requisitions count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-file fs-1 text-dark"></i>
                                        </div>
                                        <h5 class="card-title">Purchase Requisitions</h5>
                                        <p class="card-text">Create internal requests for inventory, fixed assets and intangible assets with approvals and budget checks.</p>
                                        <a href="{{ route('purchases.requisitions.index') }}" class="btn btn-dark">
                                            <i class="bx bx-file me-1"></i> Manage Requisitions
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Purchase Quotation -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            {{ \App\Models\Purchase\PurchaseQuotation::when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))->count() }}
                                            <span class="visually-hidden">quotations count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-file-blank fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Purchase Quotation</h5>
                                        <p class="card-text">Request quotations from suppliers with specifications and terms.</p>
                                        @can('view purchase quotations')
                                        <a href="{{ route('purchases.quotations.index') }}" class="btn btn-success">
                                            <i class="bx bx-list-ul me-1"></i> View Quotations
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>

                            <!-- 4. Purchase Order -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        @php
                                            $poBranchId = session('branch_id') ?? auth()->user()->branch_id;
                                        @endphp
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                                             {{ \App\Models\Purchase\PurchaseOrder::when($poBranchId, fn($q) => $q->where('branch_id', $poBranchId))->count() }}
                                            <span class="visually-hidden">orders count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-shopping-bag fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Purchase Order</h5>
                                        <p class="card-text">Convert accepted quotations into binding orders with inventory planning.</p>
                                        @can('view purchase orders')
                                        <a href="{{ route('purchases.orders.index') }}" class="btn btn-warning">
                                            <i class="bx bx-shopping-bag me-1"></i> Manage Orders
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>

                            <!-- 5. Goods Receipt -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            {{ \App\Models\Purchase\GoodsReceipt::when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))->count() }}
                                            <span class="visually-hidden">receipts count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-package fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Goods Receipt</h5>
                                        <p class="card-text">Receive and verify goods with quality checks and stock updates.</p>
                                        @can('view purchase orders')
                                        <a href="{{ route('purchases.grn.index') }}" class="btn btn-info">
                                            <i class="bx bx-package me-1"></i> Manage GRN
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>

                            <!-- 6. Purchase Invoice -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        @php
                                            $piBranchId = session('branch_id') ?? auth()->user()->branch_id;
                                        @endphp
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            {{ \App\Models\Purchase\PurchaseInvoice::when($piBranchId, fn($q) => $q->where('branch_id', $piBranchId))->count() }}
                                            <span class="visually-hidden">invoices count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-receipt fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Purchase Invoice</h5>
                                        <p class="card-text">Create invoices with taxes, post to AP, and manage payables.</p>
                                        @can('view purchase invoices')
                                        <a href="{{ route('purchases.purchase-invoices.index') }}" class="btn btn-danger">
                                            <i class="bx bx-receipt me-1"></i> Manage Invoices
                                        </a>
                                        @endcan
                                        @can('create purchase invoices')
                                        <a href="{{ route('purchases.opening-balances.index') }}" class="btn btn-outline-secondary ms-2">
                                            <i class="bx bx-book-open me-1"></i> Supplier Opening Balance
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>

                            <!-- 6. Cash Purchase -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            @php
                                                $branchId = session('branch_id') ?? auth()->user()->branch_id;
                                            @endphp
                                            {{ \App\Models\Purchase\CashPurchase::where('company_id', auth()->user()->company_id)->when($branchId, fn($q) => $q->where('branch_id', $branchId))->count() }}
                                            <span class="visually-hidden">cash purchases count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-dollar-circle fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Cash Purchase</h5>
                                        <p class="card-text">Process immediate cash purchases with instant payment settlement.</p>
                                        @can('view cash purchases')
                                        <a href="{{ route('purchases.cash-purchases.index') }}" class="btn btn-success">
                                            <i class="bx bx-list-ul me-1"></i> Manage Cash Purchases
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>

                            <!-- 7. Debit Notes -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-secondary position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary">
                                            {{ \App\Models\Purchase\DebitNote::when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))->count() }}
                                            <span class="visually-hidden">debit notes count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-minus-circle fs-1 text-secondary"></i>
                                        </div>
                                        <h5 class="card-title">Debit Notes</h5>
                                        <p class="card-text">Process returns, corrections, and adjustments for purchase transactions.</p>
                                        @can('view debit notes')
                                        <a href="{{ route('purchases.debit-notes.index') }}" class="btn btn-secondary">
                                            <i class="bx bx-minus-circle me-1"></i> Manage Debit Notes
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>

                            <!-- 8. Bill Management -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-purple position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-purple">
                                            {{ \App\Models\Bill::when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))->count() }}
                                            <span class="visually-hidden">bills count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-receipt fs-1 text-purple"></i>
                                        </div>
                                        <h5 class="card-title">Bill Management</h5>
                                        <p class="card-text">Manage supplier bills and accounts payable transactions.</p>
                                        <a href="{{ route('accounting.bill-purchases') }}" class="btn btn-purple">
                                            <i class="bx bx-list-ul me-1"></i> Manage Bills
                                        </a>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Quotations -->
        @php
            $recentQuotations = \App\Models\Purchase\PurchaseQuotation::with('supplier')
                ->when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        @endphp

        @if($recentQuotations->count() > 0)
            <div class="card">
                <div class="card-body">
                    <div class="card-title d-flex align-items-center justify-content-between">
                        <div>
                            <i class="bx bx-time-five me-1 font-22 text-primary"></i>
                            <h5 class="mb-0 text-primary">Recent Quotations</h5>
                        </div>
                        @can('view purchase quotations')
                        <a href="{{ route('purchases.quotations.index') }}" class="btn btn-primary btn-sm">
                            <i class="bx bx-list-ul me-1"></i> View All
                        </a>
                        @endcan
                    </div>
                    <hr>
                    <div class="table-responsive">
                        <table id="recent-quotations-table" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Quotation #</th>
                                    <th>Supplier</th>
                                    <th>Date</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentQuotations as $index => $quotation)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <span class="text-primary fw-bold">
                                                {{ $quotation->reference }}
                                            </span>
                                        </td>
                                        <td>{{ $quotation->supplier->name ?? 'N/A' }}</td>
                                        <td>{{ $quotation->start_date->format('M d, Y') }}</td>
                                        <td class="text-end">TZS {{ number_format($quotation->total_amount, 2) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $quotation->status === 'approved' ? 'success' : ($quotation->status === 'draft' ? 'warning' : 'secondary') }}">
                                                {{ ucfirst($quotation->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                @can('view purchase quotations')
                                                <a href="{{ route('purchases.quotations.show', $quotation->id) }}" class="btn btn-sm btn-outline-primary" title="View">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                @endcan
                                                @if($quotation->status === 'draft')
                                                @can('edit purchase quotations')
                                                <a href="{{ route('purchases.quotations.edit', $quotation->id) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                @endcan
                                                @endif
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
                        <h5 class="mt-3">No Quotations Found</h5>
                        <p class="mb-4">Get started by creating your first purchase quotation</p>
                        @can('create purchase quotations')
                        <a href="{{ route('purchases.quotations.create') }}" class="btn btn-primary">
                            <i class="bx bx-plus me-1"></i> Create Quotation
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

    .border-primary { border-color: #0d6efd !important; }
    .border-success { border-color: #198754 !important; }
    .border-warning { border-color: #ffc107 !important; }
    .border-info { border-color: #0dcaf0 !important; }
    .border-danger { border-color: #dc3545 !important; }
    .border-secondary { border-color: #6c757d !important; }
    .border-dark { border-color: #212529 !important; }
    .border-purple { border-color: #6f42c1 !important; }
    .text-purple { color: #6f42c1 !important; }
    .bg-purple { background-color: #6f42c1 !important; }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        // Initialize DataTable for recent quotations
        if ($('#recent-quotations-table').length) {
            $('#recent-quotations-table').DataTable({
                responsive: true,
                order: [[3, 'desc']], // Sort by date descending
                pageLength: 5,
                searching: false,
                lengthChange: false,
                info: false,
                language: {
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
        }
    });
</script>
@endpush
