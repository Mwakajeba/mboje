@extends('layouts.main')

@section('title', 'Daily Booking vs Collection Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel Reports', 'url' => route('hotel.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Daily Booking vs Collection Report', 'url' => '#', 'icon' => 'bx bx-calendar-check']
        ]" />

        <h6 class="mb-0 text-uppercase">DAILY BOOKING VS COLLECTION REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('hotel.reports.daily-booking-vs-collection') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary"><i class="bx bx-search me-1"></i> Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Export PDF -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-end gap-2">
                    <form method="POST" action="{{ route('hotel.reports.daily-booking-vs-collection.export-pdf') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from', $dateFrom) }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to', $dateTo) }}">
                        <button type="submit" class="btn btn-danger">
                            <i class="bx bx-file-blank me-1"></i> Export PDF
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Report title and date range -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-2"><strong>Bookings vs. Collections Report</strong></h5>
                <p class="mb-0 text-muted">
                    <strong>FROM</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}
                    <strong> TO</strong> {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
                </p>
                <div class="row text-center mt-3">
                    <div class="col-md-4">
                        <h5 class="text-muted mb-1">Amount Expected in Range</h5>
                        <h4 class="text-primary">TZS {{ number_format($totalExpectedInRange ?? 0, 2) }}</h4>
                        <small class="text-muted">Price × days in selected range</small>
                    </div>
                    <div class="col-md-4">
                        <h5 class="text-muted mb-1">Total Amount Paid</h5>
                        <h4 class="text-success">TZS {{ number_format($totalPaid, 2) }}</h4>
                    </div>
                    <div class="col-md-4">
                        <h5 class="text-muted mb-1">Total Due Amount</h5>
                        <h4 class="text-warning">TZS {{ number_format($totalDue, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Data -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th><strong>CUSTOMER</strong></th>
                                <th><strong>PROPERTY</strong></th>
                                <th class="text-end"><strong>PRICE PER DAY</strong></th>
                                <th class="text-center"><strong>BOOKED DAYS</strong></th>
                                <th class="text-center"><strong>DAYS SELECTED</strong></th>
                                <th class="text-end"><strong>AMOUNT EXPECTED IN RANGE</strong></th>
                                <th class="text-end"><strong>AMOUNT PAID</strong></th>
                                <th class="text-end"><strong>DUE AMOUNT</strong></th>
                                <th><strong>CHECK-IN</strong></th>
                                <th><strong>CHECK-OUT</strong></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportRows as $row)
                                <tr>
                                    <td>{{ $row['customer'] }}</td>
                                    <td>{{ $row['property'] }}</td>
                                    <td class="text-end">{{ number_format($row['price_per_day'], 2) }}</td>
                                    <td class="text-center">{{ $row['booked_days'] }}</td>
                                    <td class="text-center">{{ $row['days_selected'] }}</td>
                                    <td class="text-end">{{ number_format($row['amount_expected_in_range'] ?? 0, 2) }}</td>
                                    <td class="text-end">{{ number_format($row['amount_paid'], 2) }}</td>
                                    <td class="text-end">{{ number_format($row['due_amount'], 2) }}</td>
                                    <td>{{ $row['check_in'] }}</td>
                                    <td>{{ $row['check_out'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">No bookings overlap the selected date range.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5" class="text-end">TOTAL:</th>
                                <th class="text-end">TZS {{ number_format($totalExpectedInRange ?? 0, 2) }}</th>
                                <th class="text-end">TZS {{ number_format($totalPaid, 2) }}</th>
                                <th class="text-end">TZS {{ number_format($totalDue, 2) }}</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
