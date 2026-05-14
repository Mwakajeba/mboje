@extends('layouts.main')

@section('title', 'Hotel Management')

@push('styles')
<style>
    .module-card {
        position: relative;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .module-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .count-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 14px;
        color: white;
        z-index: 10;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel & Property Management', 'url' => '#', 'icon' => 'bx bx-building-house'],
            ['label' => 'Hotel Management', 'url' => '#', 'icon' => 'bx bx-hotel']
        ]" />

        <h6 class="mb-0 text-uppercase">HOTEL MANAGEMENT</h6>
        <hr />

        <!-- Summary Cards -->
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded bg-primary bg-gradient">
                                    <div class="avatar-title text-white">
                                        <i class="bx bx-building font-size-24"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-uppercase fw-medium text-muted mb-0">Total Properties</p>
                                <h4 class="mb-0">{{ number_format($totalProperties) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded bg-success bg-gradient">
                                    <div class="avatar-title text-white">
                                        <i class="bx bx-bed font-size-24"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-uppercase fw-medium text-muted mb-0">Total Rooms</p>
                                <h4 class="mb-0">{{ number_format($totalRooms) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded bg-info bg-gradient">
                                    <div class="avatar-title text-white">
                                        <i class="bx bx-check-circle font-size-24"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-uppercase fw-medium text-muted mb-0">Available Rooms</p>
                                <h4 class="mb-0">{{ number_format($availableRooms) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded bg-warning bg-gradient">
                                    <div class="avatar-title text-white">
                                        <i class="bx bx-trending-up font-size-24"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-uppercase fw-medium text-muted mb-0">Occupancy Rate</p>
                                <h4 class="mb-0">{{ number_format($currentOccupancy, 1) }}%</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Rooms Occupied -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded bg-gradient-warning bg-gradient">
                                    <div class="avatar-title text-white">
                                        <i class="bx bx-bed font-size-24"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-uppercase fw-medium text-muted mb-0">Rooms Occupied</p>
                                <h4 class="mb-0">{{ number_format($roomsOccupied ?? 0) }} / {{ number_format($totalRooms) }}</h4>
                                <p class="text-muted mb-0 small">Currently occupied</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Today's Bookings -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded bg-gradient-lush bg-gradient">
                                    <div class="avatar-title text-white">
                                        <i class="bx bx-book-content font-size-24"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-uppercase fw-medium text-muted mb-0">Today's Bookings</p>
                                <h4 class="mb-0">TZS {{ number_format($todaysBookingsValue ?? 0, 2) }}</h4>
                                <p class="text-muted mb-0 small">Value ({{ $todaysBookingsCount ?? 0 }})</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Management Cards -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Hotel Management Dashboard</h4>

                        <div class="row">
                            <!-- Room Management -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card module-card h-100">
                                    <div class="count-badge bg-primary">{{ number_format($totalRooms) }}</div>
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-bed text-primary" style="font-size: 3rem;"></i>
                                        </div>
                                        <h5 class="card-title">Room Management</h5>
                                        <p class="card-text">Manage rooms, rates, and availability</p>
                                        <a href="{{ route('rooms.index') }}" class="btn btn-primary">
                                            <i class="bx bx-group me-1"></i>Manage Rooms
                                        </a>
                                        <div class="mt-2">
                                            <a href="{{ route('properties.create') }}" class="btn btn-outline-primary btn-sm">
                                                <i class="bx bx-building-house me-1"></i>Create Property
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Booking Management -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card module-card h-100">
                                    <div class="count-badge bg-success">{{ number_format($totalBookings) }}</div>
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-calendar-check text-success" style="font-size: 3rem;"></i>
                                        </div>
                                        <h5 class="card-title">Booking Management</h5>
                                        <p class="card-text">Handle reservations and check-ins</p>
                                        <a href="{{ route('bookings.index') }}" class="btn btn-success">
                                            <i class="bx bx-calendar me-1"></i>Manage Bookings
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Guest Management -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card module-card h-100">
                                    <div class="count-badge bg-info">{{ number_format($totalGuests) }}</div>
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-user text-info" style="font-size: 3rem;"></i>
                                        </div>
                                        <h5 class="card-title">Guest Management</h5>
                                        <p class="card-text">Manage guest information and history</p>
                                        <a href="{{ route('guests.index') }}" class="btn btn-info">
                                            <i class="bx bx-user me-1"></i>Manage Guests
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Hotel Expenses -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card module-card h-100">
                                    <div class="count-badge bg-warning">-</div>
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-wallet text-warning" style="font-size: 3rem;"></i>
                                        </div>
                                        <h5 class="card-title">Hotel Expenses</h5>
                                        <p class="card-text">Record general and room-specific expenses</p>
                                        <a href="{{ route('hotel.expenses.index') }}" class="btn btn-warning">
                                            <i class="bx bx-wallet me-1"></i>Manage Expenses
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Online Bookings -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card module-card h-100">
                                    <div class="count-badge" style="background-color: #6f42c1;">{{ number_format($onlineBookings ?? 0) }}</div>
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-globe" style="font-size: 3rem; color: #6f42c1;"></i>
                                        </div>
                                        <h5 class="card-title">Online Bookings</h5>
                                        <p class="card-text">View and manage bookings from web portal</p>
                                        <a href="{{ route('bookings.index') }}?status=online_booking" class="btn" style="background-color: #6f42c1; color: white; border-color: #6f42c1;">
                                            <i class="bx bx-globe me-1"></i>View Online Bookings
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Booked Room Status -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card module-card h-100">
                                    <div class="count-badge bg-danger">-</div>
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-calendar-check text-danger" style="font-size: 3rem;"></i>
                                        </div>
                                        <h5 class="card-title">Booked Room Status</h5>
                                        <p class="card-text">View room availability and booking status for date ranges</p>
                                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#roomStatusModal">
                                            <i class="bx bx-calendar-check me-1"></i>View Room Status
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Guest Messages -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card module-card h-100">
                                    <div class="count-badge bg-danger">{{ number_format($unreadMessages ?? 0) }}</div>
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-mail text-danger" style="font-size: 3rem;"></i>
                                        </div>
                                        <h5 class="card-title">Guest Messages</h5>
                                        <p class="card-text">View and respond to messages from guests</p>
                                        <a href="{{ route('hotel.guest-messages.index') }}" class="btn btn-danger">
                                            <i class="bx bx-mail me-1"></i>View Messages
                                            @if($unreadMessages > 0)
                                                <span class="badge bg-white text-danger ms-1">{{ $unreadMessages }}</span>
                                            @endif
                                        </a>
                                        <div class="mt-2">
                                            <small class="text-muted">Total: {{ number_format($totalMessages ?? 0) }} messages</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Settings -->
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card module-card h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="bx bx-cog" style="font-size: 3rem; color: #6c757d;"></i>
                                        </div>
                                        <h5 class="card-title">Settings</h5>
                                        <p class="card-text">Terms and Conditions for booking PDF exports</p>
                                        <a href="{{ route('hotel.settings') }}" class="btn btn-outline-secondary">
                                            <i class="bx bx-cog me-1"></i>Hotel Settings
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Monthly Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <h4 class="text-primary">{{ number_format($totalBookings) }}</h4>
                                <p class="text-muted mb-0">Bookings This Month</p>
                            </div>
                            <div class="col-6">
                                <h4 class="text-success">{{ number_format($totalGuests) }}</h4>
                                <p class="text-muted mb-0">Total Guests</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Revenue Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-12">
                                <h4 class="text-success">{{ number_format($monthlyRevenue, 2) }} TZS</h4>
                                <p class="text-muted mb-0">Monthly Revenue</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Room Status Modal -->
<div class="modal fade" id="roomStatusModal" tabindex="-1" aria-labelledby="roomStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roomStatusModalLabel">
                    <i class="bx bx-calendar-check me-2"></i>Booked Room Status
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Date Range Filter -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" value="{{ date('Y-m-d', strtotime('+7 days')) }}">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" class="btn btn-primary w-100" id="checkRoomStatus">
                            <i class="bx bx-search me-1"></i>Check Status
                        </button>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4" id="summaryCards" style="display: none;">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h3 id="totalRoomsCount">0</h3>
                                <p class="mb-0">Total Rooms</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3 id="availableRoomsCount">0</h3>
                                <p class="mb-0">Available Rooms</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <h3 id="bookedRoomsCount">0</h3>
                                <p class="mb-0">Booked Rooms</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading Spinner -->
                <div class="text-center" id="loadingSpinner" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <!-- Room Status Table -->
                <div class="table-responsive" id="roomStatusTable" style="display: none;">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Room Number</th>
                                <th>Room Name</th>
                                <th>Property</th>
                                <th>Status</th>
                                <th>Availability</th>
                                <th>Conflicting Bookings</th>
                            </tr>
                        </thead>
                        <tbody id="roomStatusTableBody">
                            <!-- Data will be populated here -->
                        </tbody>
                    </table>
                </div>

                <!-- No Data Message -->
                <div class="alert alert-info text-center" id="noDataMessage" style="display: none;">
                    <i class="bx bx-info-circle me-2"></i>Please select a date range and click "Check Status" to view room availability.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Check room status on button click
    $('#checkRoomStatus').on('click', function() {
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();

        if (!startDate || !endDate) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Please select both start and end dates.'
            });
            return;
        }

        if (startDate > endDate) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Start date must be before or equal to end date.'
            });
            return;
        }

        // Show loading spinner
        $('#loadingSpinner').show();
        $('#roomStatusTable').hide();
        $('#summaryCards').hide();
        $('#noDataMessage').hide();

        // Make AJAX request
        $.ajax({
            url: '{{ route("hotel.management.room-status") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                start_date: startDate,
                end_date: endDate
            },
            success: function(response) {
                $('#loadingSpinner').hide();

                if (response.success) {
                    // Update summary cards
                    $('#totalRoomsCount').text(response.summary.total);
                    $('#availableRoomsCount').text(response.summary.available);
                    $('#bookedRoomsCount').text(response.summary.booked);
                    $('#summaryCards').show();

                    // Populate table
                    let tableBody = '';
                    response.rooms.forEach(function(room) {
                        const statusBadge = room.is_available 
                            ? '<span class="badge bg-success">Available</span>'
                            : '<span class="badge bg-danger">Booked</span>';
                        
                        const roomStatusBadge = room.status === 'available' 
                            ? '<span class="badge bg-info">Available</span>'
                            : room.status === 'occupied'
                            ? '<span class="badge bg-warning">Occupied</span>'
                            : '<span class="badge bg-secondary">' + room.status + '</span>';

                        let conflictsHtml = '';
                        if (room.conflicting_bookings.length > 0) {
                            conflictsHtml = '<ul class="list-unstyled mb-0">';
                            room.conflicting_bookings.forEach(function(booking) {
                                conflictsHtml += '<li class="mb-1">';
                                conflictsHtml += '<strong>' + booking.booking_number + '</strong><br>';
                                conflictsHtml += '<small>Guest: ' + booking.guest_name + '</small><br>';
                                conflictsHtml += '<small>Dates: ' + booking.check_in + ' to ' + booking.check_out + '</small>';
                                conflictsHtml += '</li>';
                            });
                            conflictsHtml += '</ul>';
                        } else {
                            conflictsHtml = '<span class="text-muted">No conflicts</span>';
                        }

                        tableBody += '<tr>';
                        tableBody += '<td>' + room.room_number + '</td>';
                        tableBody += '<td>' + (room.room_name || 'N/A') + '</td>';
                        tableBody += '<td>' + room.property_name + '</td>';
                        tableBody += '<td>' + roomStatusBadge + '</td>';
                        tableBody += '<td>' + statusBadge + '</td>';
                        tableBody += '<td>' + conflictsHtml + '</td>';
                        tableBody += '</tr>';
                    });

                    $('#roomStatusTableBody').html(tableBody);
                    $('#roomStatusTable').show();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to fetch room status.'
                    });
                }
            },
            error: function(xhr) {
                $('#loadingSpinner').hide();
                let errorMessage = 'Failed to fetch room status.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage
                });
            }
        });
    });

    // Check status on modal show if dates are set
    $('#roomStatusModal').on('shown.bs.modal', function() {
        $('#noDataMessage').show();
    });
});
</script>
@endpush
