@extends('layouts.main')

@section('title', 'Rental & Event Equipment')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => '#', 'icon' => 'bx bx-package']
        ]" />
            <h6 class="mb-0 text-uppercase">RENTAL & EVENT EQUIPMENT</h6>
            <hr />


            <!-- Rental Business Operations -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-center">
                                <div><i class="bx bx-shopping-bag me-1 font-22 text-success"></i></div>
                                <h5 class="mb-0 text-success">Rental Business Operations</h5>
                            </div>
                            <hr />
                            <p class="text-muted small mb-3">Customer pays per item rented. Track items from store →
                                customer → back to store.</p>

                            <div class="row mb-4">
                              <!-- Equipment Master -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-primary position-relative">
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                            {{ $dashboardCounts['equipment'] ?? 0 }}
                                        </span>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-package fs-1 text-primary"></i>
                                            </div>
                                            <h6 class="card-title">Equipment Master</h6>
                                            <p class="card-text small">Register and manage all reusable equipment items
                                                (chairs, tables, tents, etc.)</p>
                                            <a href="{{ route('rental-event-equipment.equipment.index') }}"
                                                class="btn btn-primary btn-sm">
                                                <i class="bx bx-list-ul me-1"></i> Manage Equipment
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <!-- Rental Quotations -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-success position-relative">
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            {{ $dashboardCounts['rental_quotations'] ?? 0 }}
                                        </span>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-file-blank fs-1 text-success"></i>
                                            </div>
                                            <h6 class="card-title">Rental Quotations</h6>
                                            <p class="card-text small">Create quotations for customers. Check equipment
                                                availability without stock movement.</p>
                                            <a href="{{ route('rental-event-equipment.quotations.index') }}"
                                                class="btn btn-success btn-sm">
                                                <i class="bx bx-list-ul me-1"></i> Manage Quotations
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rental Contracts -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-success position-relative">
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            {{ $dashboardCounts['rental_contracts'] ?? 0 }}
                                        </span>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-file fs-1 text-success"></i>
                                            </div>
                                            <h6 class="card-title">Rental Contracts</h6>
                                            <p class="card-text small">Convert quotations to contracts. Items change from
                                                Available → Reserved status.</p>
                                            <a href="{{ route('rental-event-equipment.contracts.index') }}"
                                                class="btn btn-success btn-sm">
                                                <i class="bx bx-list-ul me-1"></i> Manage Contracts
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Customer Deposits -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-warning position-relative">
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning">
                                            {{ $dashboardCounts['customer_deposits'] ?? 0 }}
                                        </span>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-money fs-1 text-warning"></i>
                                            </div>
                                            <h6 class="card-title">Customer Deposits</h6>
                                            <p class="card-text small">Record customer deposits received before events.
                                                Deposits are refundable liabilities.</p>
                                            <a href="{{ route('rental-event-equipment.customer-deposits.index') }}"
                                                class="btn btn-warning btn-sm">
                                                <i class="bx bx-list-ul me-1"></i> Manage Deposits
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rental Dispatch -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-info position-relative">
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            {{ $dashboardCounts['rental_dispatches'] ?? 0 }}
                                        </span>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-send fs-1 text-info"></i>
                                            </div>
                                            <h6 class="card-title">Rental Dispatch</h6>
                                            <p class="card-text small">Issue items to customers. Status changes: Reserved →
                                                On Rent. Items tagged with customer and event.</p>
                                            <a href="{{ route('rental-event-equipment.rental-dispatches.index') }}"
                                                class="btn btn-info btn-sm">
                                                <i class="bx bx-list-ul me-1"></i> Manage Dispatches
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rental Returns -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-primary position-relative">
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                            {{ $dashboardCounts['rental_returns'] ?? 0 }}
                                        </span>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-undo fs-1 text-primary"></i>
                                            </div>
                                            <h6 class="card-title">Rental Returns</h6>
                                            <p class="card-text small">Record returned items. Good items → Available,
                                                Damaged → Under Repair, Lost → Removed from stock.</p>
                                            <a href="{{ route('rental-event-equipment.rental-returns.index') }}"
                                                class="btn btn-primary btn-sm">
                                                <i class="bx bx-list-ul me-1"></i> Manage Returns
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Damage & Loss Charges -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-danger position-relative">
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            {{ $dashboardCounts['damage_charges'] ?? 0 }}
                                        </span>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-error-circle fs-1 text-danger"></i>
                                            </div>
                                            <h6 class="card-title">Damage & Loss Charges</h6>
                                            <p class="card-text small">Calculate and charge customers for damaged or lost
                                                equipment. Generate recovery invoices.</p>
                                            <a href="{{ route('rental-event-equipment.damage-charges.index') }}"
                                                class="btn btn-danger btn-sm">
                                                <i class="bx bx-list-ul me-1"></i> Manage Charges
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rental Invoices -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-success position-relative">
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                            {{ $dashboardCounts['rental_invoices'] ?? 0 }}
                                        </span>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-receipt fs-1 text-success"></i>
                                            </div>
                                            <h6 class="card-title">Rental Invoices</h6>
                                            <p class="card-text small">Generate final invoices with rental charges,
                                                damage/loss charges, less deposit. Items appear on invoice.</p>
                                            <a href="{{ route('rental-event-equipment.rental-invoices.index') }}"
                                                class="btn btn-success btn-sm">
                                                <i class="bx bx-list-ul me-1"></i> Manage Invoices
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Decoration Service Business Operations -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-center">
                                <div><i class="bx bx-palette me-1 font-22 text-info"></i></div>
                                <h5 class="mb-0 text-info">Decoration Service Business Operations</h5>
                            </div>
                            <hr />
                            <p class="text-muted small mb-3">Customer pays for service package, not individual items.
                                Equipment used internally by decorators.</p>

                            <div class="row mb-4">
                                <!-- Decoration Jobs -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-info position-relative">
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            {{ $dashboardCounts['decoration_jobs'] ?? 0 }}
                                        </span>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-calendar-event fs-1 text-info"></i>
                                            </div>
                                            <h6 class="card-title">Decoration Jobs</h6>
                                            <p class="card-text small">Create event decoration jobs with customer, event
                                                date, theme, and agreed service price.</p>
                                            <a href="{{ route('rental-event-equipment.decoration-jobs.index') }}"
                                                class="btn btn-info btn-sm">
                                                <i class="bx bx-list-ul me-1"></i> Manage Jobs
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Equipment Planning -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-info position-relative">
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            {{ $dashboardCounts['decoration_plans'] ?? 0 }}
                                        </span>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-list-check fs-1 text-info"></i>
                                            </div>
                                            <h6 class="card-title">Equipment Planning</h6>
                                            <p class="card-text small">Plan and prepare list of equipment items needed
                                                internally for decoration service delivery.</p>
                                            <a href="{{ route('rental-event-equipment.decoration-equipment-plans.index') }}"
                                               class="btn btn-info btn-sm">
                                                <i class="bx bx-list-ul me-1"></i> Plan Equipment
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Equipment Issue to Decorators -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-warning position-relative">
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning">
                                            {{ $dashboardCounts['decoration_issues'] ?? 0 }}
                                        </span>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-user-check fs-1 text-warning"></i>
                                            </div>
                                            <h6 class="card-title">Issue to Decorators</h6>
                                            <p class="card-text small">Issue equipment to decorators. Status: Available → In
                                                Event Use. Items tagged with decorator and event.</p>
                                            <a href="{{ route('rental-event-equipment.decoration-equipment-issues.index') }}"
                                               class="btn btn-warning btn-sm">
                                                <i class="bx bx-list-ul me-1"></i> Manage Issues
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Decoration Returns -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-primary position-relative">
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                            {{ $dashboardCounts['decoration_returns'] ?? 0 }}
                                        </span>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-undo fs-1 text-primary"></i>
                                            </div>
                                            <h6 class="card-title">Decoration Returns</h6>
                                            <p class="card-text small">Record returned equipment. Good → Available, Damaged
                                                → Under Repair, Lost → Written Off.</p>
                                            <a href="{{ route('rental-event-equipment.decoration-equipment-returns.index') }}"
                                               class="btn btn-primary btn-sm">
                                                <i class="bx bx-list-ul me-1"></i> Manage Returns
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Loss Handling -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-danger position-relative">
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            {{ $dashboardCounts['decoration_losses'] ?? 0 }}
                                        </span>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-error fs-1 text-danger"></i>
                                            </div>
                                            <h6 class="card-title">Loss Handling</h6>
                                            <p class="card-text small">Handle equipment losses internally. Treat as business
                                                expense or employee liability based on policy.</p>
                                            <a href="{{ route('rental-event-equipment.decoration-losses.index') }}"
                                               class="btn btn-danger btn-sm">
                                                <i class="bx bx-list-ul me-1"></i> Manage Losses
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Service Invoices -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-info position-relative">
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info">
                                            {{ $dashboardCounts['decoration_invoices'] ?? 0 }}
                                        </span>
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-receipt fs-1 text-info"></i>
                                            </div>
                                            <h6 class="card-title">Service Invoices</h6>
                                            <p class="card-text small">Generate service invoices for decoration work. Items
                                                do NOT appear on invoice - only service description.</p>
                                            <a href="{{ route('rental-event-equipment.decoration-invoices.index') }}"
                                               class="btn btn-info btn-sm">
                                                <i class="bx bx-list-ul me-1"></i> Manage Invoices
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings & Configuration -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-center">
                                <div><i class="bx bx-cog me-1 font-22 text-secondary"></i></div>
                                <h5 class="mb-0 text-secondary">Settings & Configuration</h5>
                            </div>
                            <hr />

                            <div class="row mb-4">
                                <!-- Equipment Categories -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-secondary position-relative">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-category fs-1 text-secondary"></i>
                                            </div>
                                            <h6 class="card-title">Equipment Categories</h6>
                                            <p class="card-text small">Manage equipment categories and classifications</p>
                                            <a href="{{ route('rental-event-equipment.categories.index') }}"
                                                class="btn btn-secondary btn-sm">
                                                <i class="bx bx-list-ul me-1"></i> Manage Categories
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- People Tracking -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-secondary position-relative">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-group fs-1 text-secondary"></i>
                                            </div>
                                            <h6 class="card-title">People Tracking</h6>
                                            <p class="card-text small">Manage customers, employees/decorators, and
                                                storekeepers for equipment tracking</p>
                                            <button class="btn btn-secondary btn-sm" disabled>
                                                <i class="bx bx-list-ul me-1"></i> Manage People
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Accounting Settings -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-secondary position-relative">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-calculator fs-1 text-secondary"></i>
                                            </div>
                                            <h6 class="card-title">Accounting Settings</h6>
                                            <p class="card-text small">Configure GL accounts for rental income, service
                                                income, deposits, and expenses</p>
                                            <a href="{{ route('rental-event-equipment.accounting-settings.index') }}"
                                                class="btn btn-secondary btn-sm">
                                                <i class="bx bx-cog me-1"></i> Configure
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Approval Settings -->
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border-warning position-relative">
                                        <div class="card-body text-center">
                                            <div class="mb-3">
                                                <i class="bx bx-check-circle fs-1 text-warning"></i>
                                            </div>
                                            <h6 class="card-title">Approval Settings</h6>
                                            <p class="card-text small">Configure approval workflows for quotations,
                                                contracts, invoices, and other rental operations</p>
                                            <a href="{{ route('rental-event-equipment.approval-settings.index') }}"
                                                class="btn btn-warning btn-sm">
                                                <i class="bx bx-cog me-1"></i> Configure
                                            </a>
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

        .card-title {
            font-size: 0.9rem;
            font-weight: 600;
        }

        .card-text {
            font-size: 0.75rem;
            line-height: 1.2;
        }
    </style>
@endpush