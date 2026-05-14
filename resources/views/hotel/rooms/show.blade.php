@extends('layouts.main')

@section('title', 'Room Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel & Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-building-house'],
            ['label' => 'Room Management', 'url' => route('rooms.index'), 'icon' => 'bx bx-bed'],
            ['label' => 'Room Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title">{{ $room->room_number }}{{ $room->room_name ? ' - ' . $room->room_name : '' }}</h4>
                                <p class="card-subtitle text-muted">{{ ucfirst($room->room_type) }} Room</p>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('rooms.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to Rooms
                                </a>
                                <a href="{{ route('rooms.edit', $room) }}" class="btn btn-primary">
                                    <i class="bx bx-edit me-1"></i> Edit Room
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Room Number</label>
                                            <p class="form-control-plaintext">{{ $room->room_number }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Room Type</label>
                                            <p class="form-control-plaintext">{{ ucfirst($room->room_type) }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Rate per Night</label>
                                            <p class="form-control-plaintext">TSh {{ number_format($room->rate_per_night) }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Capacity</label>
                                            <p class="form-control-plaintext">{{ $room->capacity }} guests</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Floor</label>
                                            <p class="form-control-plaintext">{{ $room->floor_number ?: 'Not specified' }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Status</label>
                                            @php
                                                $availability = $room->availability_status;
                                            @endphp
                                            
                                            @if($room->status === 'maintenance')
                                                <span class="badge bg-danger">Maintenance</span>
                                                <br><small class="text-muted">Under maintenance</small>
                                            @elseif($room->status === 'out_of_order')
                                                <span class="badge bg-secondary">Out of Order</span>
                                                <br><small class="text-muted">Not available</small>
                                            @elseif($availability['status'] === 'available')
                                                <span class="badge bg-success">Available Now</span>
                                                <br><small class="text-muted">Ready for booking</small>
                                            @else
                                                <span class="badge bg-warning">Occupied</span>
                                                <br>
                                                <small class="text-muted">
                                                    <strong>Guest:</strong> {{ $availability['current_guest'] }}<br>
                                                    <strong>Check-out:</strong> {{ $availability['check_out_date'] }}<br>
                                                    <strong>Available:</strong> {{ $availability['next_available'] }}
                                                    @if($availability['days_until_available'] > 0)
                                                        <span class="text-warning">({{ $availability['days_until_available'] }} days)</span>
                                                    @else
                                                        <span class="text-success">(Today)</span>
                                                    @endif
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                @if($room->description)
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Description</label>
                                        <p class="form-control-plaintext">{{ $room->description }}</p>
                                    </div>
                                @endif

                                @if($room->amenities && count($room->amenities) > 0)
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Amenities</label>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($room->amenities as $amenity)
                                                <span class="badge bg-light text-dark">{{ ucfirst(str_replace('_', ' ', $amenity)) }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Room Statistics</h6>
                                        <div class="mb-3">
                                            <small class="text-muted">Total Bookings</small>
                                            <h5 class="mb-0">{{ $room->bookings->count() }}</h5>
                                        </div>
                                        <div class="mb-3">
                                            <small class="text-muted">Revenue Generated</small>
                                            <h5 class="mb-0">TSh {{ number_format($room->getTotalRevenue()) }}</h5>
                                        </div>
                                        <div class="mb-3">
                                            <small class="text-muted">Occupancy Rate</small>
                                            <h5 class="mb-0">{{ $room->getOccupancyRate() }}%</h5>
                                        </div>
                                        <div class="mb-3">
                                            <small class="text-muted">Current Status</small>
                                            <h5 class="mb-0">
                                                @if($room->is_occupied)
                                                    <span class="text-warning">Occupied</span>
                                                @else
                                                    <span class="text-success">Available</span>
                                                @endif
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Availability Schedule -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Availability Schedule</h4>
                        <p class="card-subtitle text-muted">Current and upcoming bookings for this room</p>
                    </div>
                    <div class="card-body">
                        @php
                            $upcomingBookings = $room->upcoming_bookings;
                            $currentBooking = $room->current_booking;
                        @endphp
                        
                        @if($currentBooking || $upcomingBookings->count() > 0)
                            <div class="timeline">
                                @if($currentBooking)
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-warning"></div>
                                        <div class="timeline-content">
                                            <h6 class="timeline-title">Currently Occupied</h6>
                                            <p class="timeline-text">
                                                <strong>Guest:</strong> {{ $currentBooking->guest->first_name }} {{ $currentBooking->guest->last_name }}<br>
                                                <strong>Check-in:</strong> {{ $currentBooking->check_in->format('M d, Y') }}<br>
                                                <strong>Check-out:</strong> {{ $currentBooking->check_out->format('M d, Y') }}<br>
                                                <strong>Status:</strong> 
                                                @if($currentBooking->status === 'checked_in')
                                                    <span class="badge bg-primary">Checked In</span>
                                                @else
                                                    <span class="badge bg-success">Confirmed</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                @endif
                                
                                @foreach($upcomingBookings as $booking)
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-info"></div>
                                        <div class="timeline-content">
                                            <h6 class="timeline-title">Upcoming Booking</h6>
                                            <p class="timeline-text">
                                                <strong>Guest:</strong> {{ $booking->guest->first_name }} {{ $booking->guest->last_name }}<br>
                                                <strong>Check-in:</strong> {{ $booking->check_in->format('M d, Y') }}<br>
                                                <strong>Check-out:</strong> {{ $booking->check_out->format('M d, Y') }}<br>
                                                <strong>Status:</strong> <span class="badge bg-success">Confirmed</span>
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="bx bx-calendar-check font-size-48 mb-3 d-block"></i>
                                <h5>No upcoming bookings</h5>
                                <p>This room is available for booking.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Recent Bookings</h4>
                    </div>
                    <div class="card-body">
                        @if($room->bookings->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Booking #</th>
                                            <th>Guest</th>
                                            <th>Check-in</th>
                                            <th>Check-out</th>
                                            <th>Status</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($room->bookings->take(5) as $booking)
                                            <tr>
                                                <td>{{ $booking->booking_number }}</td>
                                                <td>{{ $booking->guest->first_name ?? 'N/A' }} {{ $booking->guest->last_name ?? '' }}</td>
                                                <td>{{ $booking->check_in->format('M d, Y') }}</td>
                                                <td>{{ $booking->check_out->format('M d, Y') }}</td>
                                                <td>
                                                    @switch($booking->status)
                                                        @case('confirmed')
                                                            <span class="badge bg-success">Confirmed</span>
                                                            @break
                                                        @case('checked_in')
                                                            <span class="badge bg-primary">Checked In</span>
                                                            @break
                                                        @case('checked_out')
                                                            <span class="badge bg-info">Checked Out</span>
                                                            @break
                                                        @case('cancelled')
                                                            <span class="badge bg-danger">Cancelled</span>
                                                            @break
                                                        @default
                                                            <span class="badge bg-secondary">{{ ucfirst($booking->status) }}</span>
                                                    @endswitch
                                                </td>
                                                <td>TSh {{ number_format($booking->total_amount) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="bx bx-calendar font-size-48 mb-3 d-block"></i>
                                <h5>No bookings found</h5>
                                <p>This room has not been booked yet.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 3px solid #fff;
    box-shadow: 0 0 0 3px #e9ecef;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #007bff;
}

.timeline-title {
    margin-bottom: 10px;
    color: #495057;
    font-weight: 600;
}

.timeline-text {
    margin-bottom: 0;
    color: #6c757d;
    line-height: 1.5;
}
</style>
@endpush
@endsection
