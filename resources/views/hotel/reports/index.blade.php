@extends('layouts.main')

@section('title', 'Hotel Reports')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel & Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-building-house'],
            ['label' => 'Hotel Reports', 'url' => '#', 'icon' => 'bx bx-file']
        ]" />

        <h6 class="mb-0 text-uppercase">HOTEL REPORTS</h6>
        <hr />

        <!-- Hotel Reports Operations -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-center">
                            <div><i class="bx bx-file me-1 font-22 text-primary"></i></div>
                            <h5 class="mb-0 text-primary">Hotel Reports & Analytics</h5>
                        </div>
                        <hr>
                        <div class="row">
                            {{-- Report: Daily Booking vs Collection --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-calendar-check fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Daily Booking vs Collection Report</h5>
                                        <p class="card-text">Bookings overlapping a date range with price per day, days selected, amount paid and due</p>
                                        <a href="{{ route('hotel.reports.daily-booking-vs-collection') }}" class="btn btn-success">
                                            <i class="bx bx-calendar-check me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #1: Daily Occupancy Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-bed fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Daily Occupancy Report</h5>
                                        <p class="card-text">See how many rooms are occupied today with occupancy rate and availability</p>
                                        <a href="{{ route('hotel.reports.daily-occupancy') }}" class="btn btn-primary">
                                            <i class="bx bx-bed me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #2: Daily Sales / Revenue Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-money fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Daily Sales / Revenue Report</h5>
                                        <p class="card-text">Track how much money was collected today with payment methods and details</p>
                                        <a href="{{ route('hotel.reports.daily-sales') }}" class="btn btn-success">
                                            <i class="bx bx-money me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #3: Monthly Revenue Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-trending-up fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Monthly Revenue Report</h5>
                                        <p class="card-text">Analyze hotel income performance per month with revenue breakdown</p>
                                        <a href="{{ route('hotel.reports.monthly-revenue') }}" class="btn btn-info">
                                            <i class="bx bx-trending-up me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #4: Booking Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-calendar-check fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Booking Report</h5>
                                        <p class="card-text">Track all reservations (walk-in & online) with booking status and details</p>
                                        <a href="{{ route('hotel.reports.booking') }}" class="btn btn-warning">
                                            <i class="bx bx-calendar-check me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #5: Check-In & Check-Out Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-danger position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-log-in fs-1 text-danger"></i>
                                        </div>
                                        <h5 class="card-title">Check-In & Check-Out Report</h5>
                                        <p class="card-text">Monitor guest movement with check-in/out times and stay duration</p>
                                        <a href="{{ route('hotel.reports.check-in-out') }}" class="btn btn-danger">
                                            <i class="bx bx-log-in me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #6: Room Status Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-secondary position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-home fs-1 text-secondary"></i>
                                        </div>
                                        <h5 class="card-title">Room Status Report</h5>
                                        <p class="card-text">Know room readiness at any time with current status and last updated</p>
                                        <a href="{{ route('hotel.reports.room-status') }}" class="btn btn-secondary">
                                            <i class="bx bx-home me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #7: Housekeeping Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-dark position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-broom fs-1 text-dark"></i>
                                        </div>
                                        <h5 class="card-title">Housekeeping Report</h5>
                                        <p class="card-text">Track cleaning performance with cleaning status and staff details</p>
                                        <a href="{{ route('hotel.reports.housekeeping') }}" class="btn btn-dark">
                                            <i class="bx bx-broom me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #8: Guest History Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-primary position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-user fs-1 text-primary"></i>
                                        </div>
                                        <h5 class="card-title">Guest History Report</h5>
                                        <p class="card-text">Understand guest behavior & loyalty with visit counts and spending</p>
                                        <a href="{{ route('hotel.reports.guest-history') }}" class="btn btn-primary">
                                            <i class="bx bx-user me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #9: Payment Method Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-success position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-credit-card fs-1 text-success"></i>
                                        </div>
                                        <h5 class="card-title">Payment Method Report</h5>
                                        <p class="card-text">Analyze payment trends by method (Cash, M-Pesa, Card, Bank Transfer)</p>
                                        <a href="{{ route('hotel.reports.payment-method') }}" class="btn btn-success">
                                            <i class="bx bx-credit-card me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #10: Staff Activity Report --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-info position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-user-check fs-1 text-info"></i>
                                        </div>
                                        <h5 class="card-title">Staff Activity Report</h5>
                                        <p class="card-text">Monitor staff performance & accountability with transaction details</p>
                                        <a href="{{ route('hotel.reports.staff-activity') }}" class="btn btn-info">
                                            <i class="bx bx-user-check me-1"></i> View Report
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Report #11: Profit & Loss Summary --}}
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card border-warning position-relative h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-calculator fs-1 text-warning"></i>
                                        </div>
                                        <h5 class="card-title">Profit & Loss Summary</h5>
                                        <p class="card-text">Know if the hotel is profitable with revenue, expenses, and net profit</p>
                                        <a href="{{ route('hotel.reports.profit-loss') }}" class="btn btn-warning">
                                            <i class="bx bx-calculator me-1"></i> View Report
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
</style>
@endpush
