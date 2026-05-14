@extends('layouts.main')

@section('title','Purchases Reports')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => '#', 'icon' => 'bx bx-bar-chart']
        ]" />

        <h6 class="mb-0 text-uppercase">Purchases Reports</h6>
        <hr />

        <div class="row g-3">
            {{-- Report #1: Purchase Requisition Report --}}
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-primary position-relative">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-clipboard fs-1 text-primary"></i>
                        </div>
                        <h5 class="card-title">Purchase Requisition Report</h5>
                        <p class="card-text">Item-level requisition details with estimated values</p>
                        <a href="{{ route('purchases.reports.purchase-requisition') }}" class="btn btn-primary">
                            <i class="bx bx-clipboard me-1"></i> View Report
                        </a>
                    </div>
                </div>
            </div>

            {{-- Report #2: Purchase Order Register --}}
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-primary position-relative">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-file-blank fs-1 text-primary"></i>
                        </div>
                        <h5 class="card-title">Purchase Order Register</h5>
                        <p class="card-text">PO list with status, totals, and expected delivery</p>
                        <a href="{{ route('purchases.reports.purchase-order-register') }}" class="btn btn-primary">
                            <i class="bx bx-file-blank me-1"></i> View Report
                        </a>
                    </div>
                </div>
            </div>

            {{-- Report #3: PO vs GRN Report --}}
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-success position-relative">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-transfer fs-1 text-success"></i>
                        </div>
                        <h5 class="card-title">PO vs GRN (Fulfillment)</h5>
                        <p class="card-text">Ordered vs received quantities with fulfillment %</p>
                        <a href="{{ route('purchases.reports.po-vs-grn') }}" class="btn btn-success">
                            <i class="bx bx-transfer me-1"></i> View Report
                        </a>
                    </div>
                </div>
            </div>

            {{-- Report #4: GRN Variance Report --}}
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-warning position-relative">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-error fs-1 text-warning"></i>
                        </div>
                        <h5 class="card-title">GRN vs Invoice Variance</h5>
                        <p class="card-text">Received vs invoiced quantities with variance</p>
                        <a href="{{ route('purchases.reports.grn-variance') }}" class="btn btn-warning">
                            <i class="bx bx-error me-1"></i> View Report
                        </a>
                    </div>
                </div>
            </div>

            {{-- Report #5: Supplier Invoice Register --}}
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-danger position-relative">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-receipt fs-1 text-danger"></i>
                        </div>
                        <h5 class="card-title">Supplier Invoice Register</h5>
                        <p class="card-text">All invoices with net, tax, gross amounts and due dates</p>
                        <a href="{{ route('purchases.reports.invoice-register') }}" class="btn btn-danger">
                            <i class="bx bx-receipt me-1"></i> View Report
                        </a>
                    </div>
                </div>
            </div>

            {{-- Report #6: Supplier Statement Report --}}
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-info position-relative">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-file fs-1 text-info"></i>
                        </div>
                        <h5 class="card-title">Supplier Statement</h5>
                        <p class="card-text">Running balances with debit/credit transactions</p>
                        <a href="{{ route('purchases.reports.supplier-statement') }}" class="btn btn-info">
                            <i class="bx bx-file me-1"></i> View Report
                        </a>
                    </div>
                </div>
            </div>

            {{-- Report #7: Payables Aging Report --}}
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-secondary position-relative">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-time-five fs-1 text-secondary"></i>
                        </div>
                        <h5 class="card-title">Payables Aging</h5>
                        <p class="card-text">Aging buckets: 0-30, 31-60, 61-90, 90+ days</p>
                        <a href="{{ route('purchases.reports.payables-aging') }}" class="btn btn-secondary">
                            <i class="bx bx-time-five me-1"></i> View Report
                        </a>
                    </div>
                </div>
            </div>

            {{-- Report #8: Outstanding Supplier Invoices --}}
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-dark position-relative">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-hourglass fs-1 text-dark"></i>
                        </div>
                        <h5 class="card-title">Outstanding Invoices</h5>
                        <p class="card-text">Unpaid invoices with outstanding balances</p>
                        <a href="{{ route('purchases.reports.outstanding-invoices') }}" class="btn btn-dark">
                            <i class="bx bx-hourglass me-1"></i> View Report
                        </a>
                    </div>
                </div>
            </div>

            {{-- Report #9: Paid Supplier Invoice Report --}}
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-success position-relative">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-check-circle fs-1 text-success"></i>
                        </div>
                        <h5 class="card-title">Paid Invoices</h5>
                        <p class="card-text">Fully paid invoices with payment details</p>
                        <a href="{{ route('purchases.reports.paid-invoices') }}" class="btn btn-success">
                            <i class="bx bx-check-circle me-1"></i> View Report
                        </a>
                    </div>
                </div>
            </div>

            {{-- Report #10: Supplier Credit Note Report --}}
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-primary position-relative">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-undo fs-1 text-primary"></i>
                        </div>
                        <h5 class="card-title">Supplier Credit Note</h5>
                        <p class="card-text">Credit notes with net, tax, and gross amounts</p>
                        <a href="{{ route('purchases.reports.supplier-credit-note') }}" class="btn btn-primary">
                            <i class="bx bx-undo me-1"></i> View Report
                        </a>
                    </div>
                </div>
            </div>

            {{-- Report #11: Supplier Invoice Variance Report (PO vs Invoice) --}}
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-warning position-relative">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-git-compare fs-1 text-warning"></i>
                        </div>
                        <h5 class="card-title">PO vs Invoice Variance</h5>
                        <p class="card-text">Ordered/received vs invoiced value variances</p>
                        <a href="{{ route('purchases.reports.po-invoice-variance') }}" class="btn btn-warning">
                            <i class="bx bx-git-compare me-1"></i> View Report
                        </a>
                    </div>
                </div>
            </div>

            {{-- Report #12: Purchase Returns Report --}}
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-primary position-relative">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-undo fs-1 text-primary"></i>
                        </div>
                        <h5 class="card-title">Purchase Returns</h5>
                        <p class="card-text">Returned items with quantities and reasons</p>
                        <a href="{{ route('purchases.reports.purchase-returns') }}" class="btn btn-primary">
                            <i class="bx bx-undo me-1"></i> View Report
                        </a>
                    </div>
                </div>
            </div>

            {{-- Report #13: Purchase Analysis by Supplier --}}
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-primary position-relative">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-user fs-1 text-primary"></i>
                        </div>
                        <h5 class="card-title">Purchase Analysis by Supplier</h5>
                        <p class="card-text">Total value, PO count, avg order value, contribution %</p>
                        <a href="{{ route('purchases.reports.purchase-by-supplier') }}" class="btn btn-primary">
                            <i class="bx bx-user me-1"></i> View Report
                        </a>
                    </div>
                </div>
            </div>

            {{-- Report #14: Purchase Analysis by Item/Category --}}
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-primary position-relative">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-basket fs-1 text-primary"></i>
                        </div>
                        <h5 class="card-title">Purchase Analysis by Item</h5>
                        <p class="card-text">Total qty, total value, avg unit cost by category</p>
                        <a href="{{ route('purchases.reports.purchase-by-item') }}" class="btn btn-primary">
                            <i class="bx bx-basket me-1"></i> View Report
                        </a>
                    </div>
                </div>
            </div>

            {{-- Report #15: Purchase Forecast Report --}}
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-info position-relative">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-line-chart fs-1 text-info"></i>
                        </div>
                        <h5 class="card-title">Purchase Forecast</h5>
                        <p class="card-text">Monthly usage, current stock, forecast qty, suggested purchase</p>
                        <a href="{{ route('purchases.reports.purchase-forecast') }}" class="btn btn-info">
                            <i class="bx bx-line-chart me-1"></i> View Report
                        </a>
                    </div>
                </div>
            </div>

            {{-- Report #16: Supplier Invoice Tax Report --}}
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-success position-relative">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-receipt fs-1 text-success"></i>
                        </div>
                        <h5 class="card-title">Supplier Invoice Tax</h5>
                        <p class="card-text">Taxable amount, tax rate, input tax by period</p>
                        <a href="{{ route('purchases.reports.supplier-tax') }}" class="btn btn-success">
                            <i class="bx bx-receipt me-1"></i> View Report
                        </a>
                    </div>
                </div>
            </div>

            {{-- Report #17: Supplier Payment Schedule Report --}}
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-dark position-relative">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-calendar-event fs-1 text-dark"></i>
                        </div>
                        <h5 class="card-title">Supplier Payment Schedule</h5>
                        <p class="card-text">Outstanding amounts by due date with priority</p>
                        <a href="{{ route('purchases.reports.payment-schedule') }}" class="btn btn-dark">
                            <i class="bx bx-calendar-event me-1"></i> View Report
                        </a>
                    </div>
                </div>
            </div>

            {{-- Report #18: Three-Way Matching Exception Report --}}
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-warning position-relative">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-error fs-1 text-warning"></i>
                        </div>
                        <h5 class="card-title">Three-Way Matching Exception</h5>
                        <p class="card-text">PO vs GRN vs Invoice mismatches and exceptions</p>
                        <a href="{{ route('purchases.reports.three-way-matching-exception') }}" class="btn btn-warning">
                            <i class="bx bx-error me-1"></i> View Report
                        </a>
                    </div>
                </div>
            </div>

            {{-- Report #19: Supplier Performance Report --}}
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-primary position-relative">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-trophy fs-1 text-primary"></i>
                        </div>
                        <h5 class="card-title">Supplier Performance</h5>
                        <p class="card-text">On-time delivery %, average delivery time, total orders</p>
                        <a href="{{ route('purchases.reports.supplier-performance') }}" class="btn btn-primary">
                            <i class="bx bx-trophy me-1"></i> View Report
                        </a>
                    </div>
                </div>
            </div>

            {{-- Report #20: Purchase Price Variance (PPV) Report --}}
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-primary position-relative">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-trending-up fs-1 text-primary"></i>
                        </div>
                        <h5 class="card-title">Purchase Price Variance (PPV)</h5>
                        <p class="card-text">Price variance between PO and Invoice</p>
                        <a href="{{ route('purchases.reports.purchase-price-variance') }}" class="btn btn-primary">
                            <i class="bx bx-trending-up me-1"></i> View Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


