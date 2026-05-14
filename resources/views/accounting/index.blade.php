@extends('layouts.main')

@section('title', 'Accounting Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Management', 'url' => '#', 'icon' => 'bx bx-calculator']
        ]" />
        <h6 class="mb-0 text-uppercase">ACCOUNTING MANAGEMENT</h6>
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
                            <!-- Main Groups Management -->
                            @can('view account class groups')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-purple position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-purple">
                                            {{ $mainGroups }}
                                            <span class="visually-hidden">main groups count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-grid-alt fs-1 text-purple"></i>
                                        </div>
                                        <h5 class="card-title">Main Groups</h5>
                                        <p class="card-text">Manage main account groups and classifications.</p>
                                        <a href="{{ route('accounting.main-groups.index') }}" class="btn btn-purple">
                                            <i class="bx bx-list-ul me-1"></i> Manage Main Groups
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan
                            
                            <!-- FSLI Management -->
                            @can('view account class groups')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                            {{ $chartAccountFsli }}
                                            <span class="visually-hidden">chart account - fsli count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-category fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Charts of Account - Groups</h5>
                                        <p class="card-text">Manage financial statement line items and classifications.</p>
                                        <a href="{{ route('accounting.account-class-groups.index') }}" class="btn btn-primary">
                                            <i class="bx bx-list-ul me-1"></i> Manage Groups
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Chart of Accounts Management -->
                            @can('view chart accounts')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            {{ $chartAccounts }}
                                            <span class="visually-hidden">chart account count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-box fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Chart of Accounts</h5>
                                        <p class="card-text">Manage accounts, groups, and reporting structure.</p>
                                        <a href="{{ route('accounting.chart-accounts.index') }}" class="btn btn-success">
                                            <i class="bx bx-book me-1"></i> Manage Chart of Accounts
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Banks Management -->
                            @can('view bank accounts')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            {{ $banks }}
                                            <span class="visually-hidden">bank count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-map fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Bank Accounts</h5>
                                        <p class="card-text">Manage company bank accounts and mappings to GL.</p>
                                        <a href="{{ route('accounting.bank-accounts') }}" class="btn btn-info">
                                            <i class="bx bx-bank me-1"></i> Manage Bank Accounts
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Journals Management -->
                            @can('view journals')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                                            {{ $journals }}
                                            <span class="visually-hidden">journal count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-transfer fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Manual Journals</h5>
                                        <p class="card-text">Record manual journal entries and adjustments.</p>
                                        <a href="{{ route('accounting.journals.index') }}" class="btn btn-warning">
                                            <i class="bx bx-transfer-alt me-1"></i> Manage Journals
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Payment Voucher Management -->
                            @can('view payment vouchers')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            {{ $paymentVouchers ?? 0 }}
                                            <span class="visually-hidden">pv count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-adjust fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Payment Vouchers</h5>
                                        <p class="card-text">Create payment vouchers and post expenses.</p>
                                        @can('view payment vouchers')
                                        <a href="{{ route('accounting.payment-vouchers.index') }}" class="btn btn-danger">
                                            <i class="bx bx-receipt me-1"></i> Manage Payment Vouchers
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Receipt Voucher Management -->
                            @can('view receipt vouchers')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            {{ $receiptVouchers ?? 0 }}
                                            <span class="visually-hidden">receipt voucher count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-transfer fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Receipt Vouchers</h5>
                                        <p class="card-text">Record receipts and income vouchers.</p>
                                        <a href="{{ route('accounting.receipt-vouchers.index') }}" class="btn btn-info">
                                            <i class="bx bx-list-ul me-1"></i> Manage Receipt Vouchers
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Bank Reconciliation Management -->
                            @can('view bank reconciliation')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative">
                                    <div class="card-body text-center">
                                          <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            {{ $bankReconciliations ?? 0 }}
                                            <span class="visually-hidden">bank reconciliation count</span>
                                        </span>

                                        <div class="mb-3">
                                            <i class="bx bx-layer-plus fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Bank Reconciliation</h5>
                                        <p class="card-text">Reconcile bank statements with ledger balances.</p>
                                        <a href="{{ route('accounting.bank-reconciliation.index') }}" class="btn btn-warning">
                                            <i class="bx bx-list-ul me-1"></i> Manage Bank Reconciliation
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Budget Management -->
                            @can('view budgets')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-secondary position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary">
                                            {{$budgets}}
                                            <span class="visually-hidden">Budget</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-cog fs-1 text-secondary"></i>
                                        </div>
                                        <h5 class="card-title">Annual Budget</h5>
                                        <p class="card-text">Manage annual budgets and allocations.</p>
                                        <a href="{{ route('accounting.budgets.index') }}" class="btn btn-secondary">
                                            <i class="bx bx-cog me-1"></i> Manage Budgets
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Petty Cash Management -->
                             @can('view petty cash units')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            {{ $pettyCashUnits ?? 0 }}
                                            <span class="visually-hidden">petty cash units count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-wallet fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Petty Cash System</h5>
                                        <p class="card-text">Manage petty cash floats, expenses, replenishments, and custodians.</p>
                                        <a href="{{ route('accounting.petty-cash.units.index') }}" class="btn btn-info">
                                            <i class="bx bx-wallet me-1"></i> Manage Petty Cash
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Inter-Account Transfers -->
                             @can('view inter-account transfers')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                            {{ $accountTransfers ?? 0 }}
                                            <span class="visually-hidden">account transfers count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-transfer fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Inter-Account Transfers</h5>
                                        <p class="card-text">Transfer funds between bank accounts, cash accounts, and petty cash units.</p>
                                        <a href="{{ route('accounting.account-transfers.index') }}" class="btn btn-primary">
                                            <i class="bx bx-transfer me-1"></i> Manage Transfers
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Cashflow Forecasting -->
                             @can('view cashflow forecasts')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            {{ $cashflowForecasts ?? 0 }}
                                            <span class="visually-hidden">cashflow forecasts count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-trending-up fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Cashflow Forecasting</h5>
                                        <p class="card-text">Generate comprehensive cashflow forecasts with scenario analysis.</p>
                                        <a href="{{ route('accounting.cashflow-forecasts.index') }}" class="btn btn-success">
                                            <i class="bx bx-trending-up me-1"></i> Manage Forecasts
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan
                            <!-- FX Rates Management -->
                             @can('view fx rates')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                            {{ $fxRates ?? 0 }}
                                            <span class="visually-hidden">FX rates count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-dollar fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">FX Rates</h5>
                                        <p class="card-text">Manage foreign exchange rates for multi-currency transactions.</p>
                                        <a href="{{ route('accounting.fx-rates.index') }}" class="btn btn-primary">
                                            <i class="bx bx-dollar me-1"></i> Manage FX Rates
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- FX Revaluation Management -->
                             @can('view fx revaluation')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            {{ $fxRevaluations ?? 0 }}
                                            <span class="visually-hidden">FX revaluation count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-refresh fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">FX Revaluation</h5>
                                        <p class="card-text">Revalue foreign currency monetary items at month-end closing rates (IAS 21).</p>
                                        <a href="{{ route('accounting.fx-revaluation.index') }}" class="btn btn-success">
                                            <i class="bx bx-refresh me-1"></i> Manage Revaluation
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan
                            <!-- FX Settings Management -->
                             @can('view fx settings')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-cog fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">FX Settings</h5>
                                        <p class="card-text">Configure FX accounts, functional currency, and revaluation settings for IAS 21 compliance.</p>
                                        <a href="{{ route('accounting.fx-settings.index') }}" class="btn btn-warning">
                                            <i class="bx bx-cog me-1"></i> Configure Settings
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Accruals & Prepayments Management -->
                             @can('view accruals prepayments')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-purple position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-purple">
                                            {{ $accrualSchedules ?? 0 }}
                                            <span class="visually-hidden">accrual schedules count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-time-five fs-1 text-purple"></i>
                                        </div>
                                        <h5 class="card-title">Accruals & Prepayments</h5>
                                        <p class="card-text">Manage prepaid expenses, accrued expenses, deferred income, and accrued income with automated amortisation (IFRS compliant).</p>
                                        <a href="{{ route('accounting.accruals-prepayments.index') }}" class="btn btn-purple">
                                            <i class="bx bx-time-five me-1"></i> Manage Schedules
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- IAS 37 Provisions Management -->
                            @can('view provisions')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-secondary position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary">
                                            {{ $provisionsCount ?? 0 }}
                                            <span class="visually-hidden">provisions count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-shield-quarter fs-1 text-secondary"></i>
                                        </div>
                                        <h5 class="card-title">Provisions (IAS 37)</h5>
                                        <p class="card-text">
                                            Manage legal claims, warranties, onerous contracts, environmental and restructuring provisions
                                            with full IAS 37 recognition, remeasurement, utilisation, and unwinding logic.
                                        </p>
                                        <a href="{{ route('accounting.provisions.index') }}" class="btn btn-secondary">
                                            <i class="bx bx-shield-quarter me-1"></i> Manage Provisions
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <!-- Share Capital Management -->
                            @can('view accounting')
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative">
                                    <div class="card-body text-center">
                                        <!-- Count Badge -->
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                            {{ $shareCapitalShareholders ?? 0 }}
                                            <span class="visually-hidden">shareholders count</span>
                                        </span>
                                        <div class="mb-3">
                                            <i class="bx bx-group fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Share Capital Management</h5>
                                        <p class="card-text">
                                            Manage {{ $shareCapitalShareholders ?? 0 }} shareholders, {{ $shareCapitalShareClasses ?? 0 }} share classes,
                                            {{ $shareCapitalIssues ?? 0 }} posted share issues, and {{ $shareCapitalDividends ?? 0 }} dividends with full GL integration.
                                        </p>
                                        <a href="{{ route('accounting.share-capital.index') }}" class="btn btn-primary">
                                            <i class="bx bx-group me-1"></i> Manage Share Capital
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endcan

                        </div>

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
