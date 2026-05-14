@extends('layouts.main')

@section('title', 'Bank Reconciliation Reports')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Bank Reconciliation Reports', 'url' => '#', 'icon' => 'bx bx-credit-card']
        ]" />
        
        <h6 class="mb-0 text-uppercase">BANK RECONCILIATION REPORTS</h6>
        <hr />

        <div class="row">
            <!-- Unreconciled Items Aging Report -->
            @can('view bank reconciliation report')
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-time fs-1 text-danger"></i>
                        </div>
                        <h5 class="card-title">Unreconciled Items Aging Report</h5>
                        <p class="card-text">View unreconciled items with aging analysis showing cash book and bank statement amounts.</p>
                        <a href="{{ route('accounting.reports.bank-reconciliation-report.unreconciled-items-aging') }}" class="btn btn-danger">
                            <i class="bx bx-file me-1"></i> Generate Report
                        </a>
                    </div>
                </div>
            </div>
            @endcan

            <!-- Cleared Transactions Report -->
            @can('view bank reconciliation report')
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-check-circle fs-1 text-success"></i>
                        </div>
                        <h5 class="card-title">Cleared Transactions Report</h5>
                        <p class="card-text">View all cleared transactions with source (Cash Book/Bank) and cleared by information.</p>
                        <a href="{{ route('accounting.reports.bank-reconciliation-report.cleared-transactions') }}" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Generate Report
                        </a>
                    </div>
                </div>
            </div>
            @endcan

            <!-- Bank Reconciliation Adjustments -->
            @can('view bank reconciliation report')
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-edit fs-1 text-warning"></i>
                        </div>
                        <h5 class="card-title">Bank Reconciliation Adjustments</h5>
                        <p class="card-text">View auto journal entries for bank charges, interest, and other adjustments.</p>
                        <a href="{{ route('accounting.reports.bank-reconciliation-report.adjustments') }}" class="btn btn-warning">
                            <i class="bx bx-file me-1"></i> Generate Report
                        </a>
                    </div>
                </div>
            </div>
            @endcan

            <!-- Exception Report -->
            @can('view bank reconciliation report')
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-error-circle fs-1 text-danger"></i>
                        </div>
                        <h5 class="card-title">Bank Reconciliation Exception Report</h5>
                        <p class="card-text">Identify items uncleared for more than 15 days with severity levels and suggested actions.</p>
                        <a href="{{ route('accounting.reports.bank-reconciliation-report.exception') }}" class="btn btn-danger">
                            <i class="bx bx-file me-1"></i> Generate Report
                        </a>
                    </div>
                </div>
            </div>
            @endcan

            <!-- Approval & Audit Trail Report -->
            @can('view bank reconciliation report')
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-history fs-1 text-info"></i>
                        </div>
                        <h5 class="card-title">Approval & Audit Trail Report</h5>
                        <p class="card-text">Track reconciliation approval workflow and audit trail with user actions and timestamps.</p>
                        <a href="{{ route('accounting.reports.bank-reconciliation-report.approval-audit-trail') }}" class="btn btn-info">
                            <i class="bx bx-file me-1"></i> Generate Report
                        </a>
                    </div>
                </div>
            </div>
            @endcan

            <!-- Full Bank Reconciliation Pack -->
            @can('view bank reconciliation report')
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bx bx-package fs-1 text-primary"></i>
                        </div>
                        <h5 class="card-title">Full Bank Reconciliation Pack</h5>
                        <p class="card-text">Complete PDF bundle combining all reconciliation reports for auditors.</p>
                        <a href="{{ route('accounting.reports.bank-reconciliation-report.full-pack') }}" class="btn btn-primary">
                            <i class="bx bx-download me-1"></i> Download Pack
                        </a>
                    </div>
                </div>
            </div>
            @endcan

        </div>
    </div>
</div>
@endsection

