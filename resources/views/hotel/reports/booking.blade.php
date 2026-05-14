@extends('layouts.main')

@section('title', 'Booking Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel Reports', 'url' => route('hotel.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Booking Report', 'url' => '#', 'icon' => 'bx bx-calendar-check']
        ]" />

        <h6 class="mb-0 text-uppercase">BOOKING REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('hotel.reports.booking') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date To</label>
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

        <!-- Export Buttons -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-end gap-2">
                    <form method="POST" action="{{ route('hotel.reports.booking.export-pdf') }}" style="display: inline;">
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

        <!-- Report Data -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Guest Name</th>
                                <th>Room Type</th>
                                <th>Check-in Date</th>
                                <th>Check-out Date</th>
                                <th>Nights</th>
                                <th>Booking Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bookings as $booking)
                                <tr>
                                    <td>{{ $booking->booking_number }}</td>
                                    <td>{{ $booking->guest->first_name }} {{ $booking->guest->last_name }}</td>
                                    <td>{{ $booking->room->room_type ?? 'N/A' }}</td>
                                    <td>{{ $booking->check_in->format('M d, Y') }}</td>
                                    <td>{{ $booking->check_out->format('M d, Y') }}</td>
                                    <td class="text-end">{{ $booking->nights }}</td>
                                    <td>
                                        <span class="badge bg-{{ $booking->status == 'confirmed' ? 'success' : ($booking->status == 'checked_in' ? 'info' : ($booking->status == 'cancelled' ? 'danger' : 'warning')) }}">
                                            {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No bookings found for the selected date range.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
