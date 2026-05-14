@extends('layouts.main')

@section('title', 'New Booking')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel & Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-building-house'],
            ['label' => 'Booking Management', 'url' => route('bookings.index'), 'icon' => 'bx bx-calendar'],
            ['label' => 'New Booking', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />

        @if($rooms->count() == 0)
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bx bx-info-circle me-2"></i>
                <strong>No Available Rooms:</strong> There are currently no rooms available for booking. 
                Please check room status or contact the administrator.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Create New Booking</h4>
                        <p class="card-subtitle text-muted">Make a new hotel reservation</p>
                    </div>
                    <div class="card-body">
                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bx bx-error-circle me-2"></i>
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        <form method="POST" action="{{ route('bookings.store') }}">
                            @csrf
                            <!-- Dates first to drive availability logic -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Check-in Date <span class="text-danger">*</span></label>
                                        <input type="date" name="check_in" id="check_in" class="form-control @error('check_in') is-invalid @enderror {{ session('error') ? 'is-invalid' : '' }}" value="{{ old('check_in', date('Y-m-d')) }}">
                                        @error('check_in')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        @if(session('error'))
                                            <div class="invalid-feedback d-block">{{ session('error') }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Check-out Date <span class="text-danger">*</span></label>
                                        <input type="date" name="check_out" id="check_out" class="form-control @error('check_out') is-invalid @enderror {{ session('error') ? 'is-invalid' : '' }}" value="{{ old('check_out', date('Y-m-d', strtotime('+1 day'))) }}">
                                        @error('check_out')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        @if(session('error'))
                                            <div class="invalid-feedback d-block">{{ session('error') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <label class="form-label mb-0">Guest <span class="text-danger">*</span></label>
                                            @can('create guest')
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#quickGuestModal">
                                                    <i class="bx bx-user-plus me-1"></i>Create New Guest
                                                </button>
                                            @endcan
                                        </div>
                                        <select name="guest_id" id="guest_id" class="form-select select2-single @error('guest_id') is-invalid @enderror">
                                            <option value="">Select Guest</option>
                                            @foreach($guests as $guest)
                                                <option value="{{ $guest->id }}" {{ old('guest_id') == $guest->id ? 'selected' : '' }}>
                                                    {{ $guest->full_name }} ({{ $guest->guest_number }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('guest_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Room <span class="text-danger">*</span></label>
                                        <select name="room_id" id="room_id_select" class="form-select select2-single @error('room_id') is-invalid @enderror" disabled>
                                            <option value="">Select Room (choose dates first)</option>
                                        </select>
                                        @error('room_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted" id="availableRoomsHint">Pick Check-in and Check-out to load available rooms.</small>
                                    </div>
                                </div>
                            </div>

                            

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Adults <span class="text-danger">*</span></label>
                                        <input type="number" name="adults" class="form-control @error('adults') is-invalid @enderror" value="{{ old('adults', 1) }}" min="1" max="10">
                                        @error('adults')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted" id="room-capacity-info">Select a room to see capacity restrictions</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Children</label>
                                        <input type="number" name="children" class="form-control @error('children') is-invalid @enderror" value="{{ old('children', 0) }}" min="0" max="10">
                                        @error('children')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Status <span class="text-danger">*</span></label>
                                        <select name="status" class="form-select select2-single @error('status') is-invalid @enderror">
                                            <option value="">Select Status</option>
                                            <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="confirmed" {{ old('status', 'confirmed') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                            <option value="checked_in" {{ old('status') == 'checked_in' ? 'selected' : '' }}>Checked In</option>
                                        </select>
                                        @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Payment Status <span class="text-danger">*</span></label>
                                        <select name="payment_status" class="form-select select2-single @error('payment_status') is-invalid @enderror">
                                            <option value="">Select Payment Status</option>
                                            <option value="pending" {{ old('payment_status', 'pending') == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="partial" {{ old('payment_status') == 'partial' ? 'selected' : '' }}>Partial</option>
                                            <option value="paid" {{ old('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                        </select>
                                        @error('payment_status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Rate per Night (TSh) <span class="text-danger">*</span></label>
                                        <input type="number" name="room_rate" class="form-control @error('room_rate') is-invalid @enderror" value="{{ old('room_rate') }}" placeholder="0" min="0" step="0.01">
                                        @error('room_rate')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Discount (TSh)</label>
                                        <input type="number" name="discount_amount" class="form-control @error('discount_amount') is-invalid @enderror" value="{{ old('discount_amount', 0) }}" placeholder="0" min="0" step="0.01">
                                        @error('discount_amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>


                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Special Requests</label>
                                        <textarea name="special_requests" class="form-control @error('special_requests') is-invalid @enderror" rows="3" placeholder="Any special requests...">{{ old('special_requests') }}</textarea>
                                        @error('special_requests')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="Internal notes...">{{ old('notes') }}</textarea>
                                        @error('notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i> Create Booking
                                </button>
                                <a href="{{ route('bookings.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-x me-1"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@can('create guest')
<div class="modal fade" id="quickGuestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-user-plus me-1"></i>Create New Guest</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quick-guest-form">
                @csrf
                <div class="modal-body">
                    <div id="quick-guest-alert" class="alert alert-danger d-none"></div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="qg_first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" id="qg_first_name" name="first_name" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="qg_last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" id="qg_last_name" name="last_name" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="qg_email" class="form-label">Email</label>
                        <input type="email" id="qg_email" name="email" class="form-control">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="qg_phone" class="form-label">Phone</label>
                        <input type="text" id="qg_phone" name="phone" class="form-control">
                        <div class="invalid-feedback"></div>
                    </div>

                    <input type="hidden" name="status" value="active">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="quick-guest-submit-btn">
                        <i class="bx bx-save me-1"></i>Save Guest
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2 if available
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('.select2-single').select2();
    }
    // Show SweetAlert for overlap errors if present
    @if(session('error_overlap') && session('error'))
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Date Range Unavailable',
            text: @json(session('error')),
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#d33'
        });
    } else {
        alert(@json(session('error')));
    }
    @endif

    @if(!session('error_overlap') && session('error'))
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Action Failed',
            text: @json(session('error')),
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#d33'
        });
    } else {
        alert(@json(session('error')));
    }
    @endif
    const roomSelect = document.querySelector('select[name="room_id"]');
    const rateInput = document.querySelector('input[name="room_rate"]');
    const adultsInput = document.querySelector('input[name="adults"]');
    const childrenInput = document.querySelector('input[name="children"]');
    const capacityInfo = document.getElementById('room-capacity-info');
    const checkInInput = document.querySelector('input[name="check_in"]');
    const checkOutInput = document.querySelector('input[name="check_out"]');

    // When check-in changes: ensure check-out is not before check-in (optional min for check-out)
    if (checkInInput && checkOutInput) {
        checkInInput.addEventListener('change', function() {
            var checkIn = this.value;
            if (checkIn) {
                checkOutInput.min = checkIn;
                if (checkOutInput.value && checkOutInput.value < checkIn) {
                    var d = new Date(checkIn + 'T12:00:00');
                    d.setDate(d.getDate() + 1);
                    checkOutInput.value = d.toISOString().slice(0, 10);
                }
            } else {
                checkOutInput.removeAttribute('min');
            }
        });
        // Set initial min from check-in if both have values
        if (checkInInput.value) checkOutInput.min = checkInInput.value;
    }
    
    // Room data for auto-filling
    const roomData = {
        @foreach($rooms as $room)
        {{ $room->id }}: {
            rate: {{ $room->rate_per_night ?? 0 }},
            capacity: {{ $room->capacity ?? 1 }},
            type: '{{ $room->room_type }}'
        },
        @endforeach
    };
    
    
    // Auto-fill room information when room is selected
    // Use Select2 change event if Select2 is initialized, otherwise use native change
    $(roomSelect).on('change', function() {
        const roomId = this.value;
        
        if (roomId && roomData[roomId]) {
            const room = roomData[roomId];
            
            // Auto-fill rate per night
            rateInput.value = room.rate;
            
            // Set capacity restrictions
            const maxCapacity = room.capacity;
            adultsInput.max = maxCapacity;
            childrenInput.max = maxCapacity;
            
            // If current adults exceed capacity, adjust it
            if (parseInt(adultsInput.value) > maxCapacity) {
                adultsInput.value = maxCapacity;
            }
            
            // If current children exceed capacity, adjust it
            if (parseInt(childrenInput.value) > maxCapacity) {
                childrenInput.value = maxCapacity;
            }
            
            // For single rooms, restrict to 1 person
            if (room.type === 'single') {
                adultsInput.value = 1;
                adultsInput.max = 1;
                childrenInput.value = 0;
                childrenInput.max = 0;
                childrenInput.disabled = true;
                capacityInfo.textContent = 'Single room - Maximum 1 person only';
                capacityInfo.className = 'text-warning';
            } else {
                childrenInput.disabled = false;
                capacityInfo.textContent = `Room capacity: ${maxCapacity} guests maximum`;
                capacityInfo.className = 'text-info';
            }
            
            // Update total guests validation
            updateTotalGuestsValidation();
        } else {
            // Reset when no room selected
            rateInput.value = '';
            adultsInput.max = 10;
            childrenInput.max = 10;
            childrenInput.disabled = false;
            capacityInfo.textContent = 'Select a room to see capacity restrictions';
            capacityInfo.className = 'text-muted';
        }
    });

    // Live availability check via AJAX with SweetAlert feedback
    function checkAvailability() {
        const roomId = roomSelect.value;
        const checkIn = checkInInput.value;
        const checkOut = checkOutInput.value;
        if (!checkIn || !checkOut) return;
        // Load available rooms for the chosen range
        fetch(`{{ route('bookings.available-rooms') }}?check_in=${checkIn}&check_out=${checkOut}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(r => r.ok ? r.json() : Promise.reject()).then(data => {
            const hasRooms = Array.isArray(data.rooms) && data.rooms.length > 0;
            if (typeof $ !== 'undefined') {
                const $sel = $('#room_id_select');
                // Rebuild Select2 cleanly
                if ($sel.data('select2')) {
                    $sel.select2('destroy');
                }
                $sel.empty();
                $sel.append(new Option(hasRooms ? 'Select Room' : 'No rooms available for selected dates', ''));
                if (hasRooms) {
                    data.rooms.forEach(rm => {
                        $sel.append(new Option(rm.label, rm.id));
                    });
                    $sel.prop('disabled', false);
                    // Re-init select2
                    if ($.fn.select2) {
                        $sel.select2();
                    }
                    document.getElementById('availableRoomsHint').textContent = `${data.rooms.length} room(s) available in this range.`;
                } else {
                    $sel.prop('disabled', true);
                    if ($.fn.select2) {
                        $sel.select2();
                    }
                    document.getElementById('availableRoomsHint').textContent = 'No rooms available in this range.';
                }
            } else {
                // Fallback without jQuery
                const sel = document.getElementById('room_id_select');
                sel.innerHTML = '';
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = hasRooms ? 'Select Room' : 'No rooms available for selected dates';
                sel.appendChild(placeholder);
                if (hasRooms) {
                    data.rooms.forEach(rm => {
                        const opt = document.createElement('option');
                        opt.value = rm.id;
                        opt.textContent = rm.label;
                        sel.appendChild(opt);
                    });
                    sel.disabled = false;
                    document.getElementById('availableRoomsHint').textContent = `${data.rooms.length} room(s) available in this range.`;
                } else {
                    sel.disabled = true;
                    document.getElementById('availableRoomsHint').textContent = 'No rooms available in this range.';
                }
            }
        }).catch(() => {
            const sel = document.getElementById('room_id_select');
            sel.innerHTML = '<option value="">Unable to load rooms</option>';
            sel.disabled = true;
        });

        // If a room is already chosen, validate overlap for it specifically
        if (!roomId) return;
        fetch(`{{ route('bookings.check-availability') }}?room_id=${roomId}&check_in=${checkIn}&check_out=${checkOut}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.available) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Date Range Unavailable',
                        text: data.message || 'Room is not available for the selected dates.',
                        icon: 'warning',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#d33'
                    });
                } else {
                    alert(data.message || 'Room is not available for the selected dates.');
                }
            }
        }).catch(() => {});
    }

    checkInInput.addEventListener('change', checkAvailability);
    checkOutInput.addEventListener('change', checkAvailability);
    // If both dates are prefilled (validation back), run once
    if (checkInInput.value && checkOutInput.value) {
        checkAvailability();
    }
    
    // Validate total guests against room capacity
    function updateTotalGuestsValidation() {
        const roomId = roomSelect.value;
        if (roomId && roomData[roomId]) {
            const room = roomData[roomId];
            const adults = parseInt(adultsInput.value) || 0;
            const children = parseInt(childrenInput.value) || 0;
            const totalGuests = adults + children;
            
            if (totalGuests > room.capacity) {
                // Show warning or adjust values
                const excess = totalGuests - room.capacity;
                if (children > 0) {
                    childrenInput.value = Math.max(0, children - excess);
                } else {
                    adultsInput.value = Math.max(1, adults - excess);
                }
            }
        }
    }
    
    // Add event listeners for adults and children inputs
    adultsInput.addEventListener('input', updateTotalGuestsValidation);
    childrenInput.addEventListener('input', updateTotalGuestsValidation);
    
             // Initialize on page load if room is already selected
             if (roomSelect.value) {
                 $(roomSelect).trigger('change');
             }

            @can('create guest')
            const quickGuestModalEl = document.getElementById('quickGuestModal');
            const quickGuestForm = document.getElementById('quick-guest-form');
            const quickGuestSubmitBtn = document.getElementById('quick-guest-submit-btn');
            const quickGuestAlert = document.getElementById('quick-guest-alert');

            function clearQuickGuestValidation() {
                if (!quickGuestForm) return;
                quickGuestForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                quickGuestForm.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
                if (quickGuestAlert) {
                    quickGuestAlert.classList.add('d-none');
                    quickGuestAlert.textContent = '';
                }
            }

            if (quickGuestModalEl) {
                quickGuestModalEl.addEventListener('hidden.bs.modal', function() {
                    clearQuickGuestValidation();
                    quickGuestForm.reset();
                });
            }

            if (quickGuestForm) {
                quickGuestForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    clearQuickGuestValidation();

                    const formData = new FormData(quickGuestForm);
                    quickGuestSubmitBtn.disabled = true;
                    quickGuestSubmitBtn.innerHTML = '<i class="spinner-border spinner-border-sm me-1"></i>Saving...';

                    fetch('{{ route("guests.store") }}', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: formData
                    })
                    .then(async response => {
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            throw { status: response.status, data };
                        }
                        return data;
                    })
                    .then(data => {
                        if (!data.success || !data.guest) {
                            throw { status: 500, data: { message: 'Unexpected response from server.' } };
                        }

                        const guestSelect = $('#guest_id');
                        const optionLabel = `${data.guest.full_name} (${data.guest.guest_number})`;
                        const exists = guestSelect.find(`option[value="${data.guest.id}"]`).length > 0;
                        if (!exists) {
                            guestSelect.append(new Option(optionLabel, data.guest.id, true, true));
                        }
                        guestSelect.val(String(data.guest.id)).trigger('change');

                        const modalInstance = bootstrap.Modal.getInstance(quickGuestModalEl);
                        if (modalInstance) modalInstance.hide();

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: data.message || 'Guest created successfully.',
                                timer: 1800,
                                showConfirmButton: false
                            });
                        }
                    })
                    .catch(err => {
                        const status = err.status || 500;
                        const data = err.data || {};

                        if (status === 422 && data.errors) {
                            Object.keys(data.errors).forEach(field => {
                                const input = quickGuestForm.querySelector(`[name="${field}"]`);
                                if (input) {
                                    input.classList.add('is-invalid');
                                    const feedback = input.parentElement.querySelector('.invalid-feedback');
                                    if (feedback) {
                                        feedback.textContent = data.errors[field][0];
                                    }
                                }
                            });
                            return;
                        }

                        if (quickGuestAlert) {
                            quickGuestAlert.textContent = data.message || 'Failed to create guest. Please try again.';
                            quickGuestAlert.classList.remove('d-none');
                        }
                    })
                    .finally(() => {
                        quickGuestSubmitBtn.disabled = false;
                        quickGuestSubmitBtn.innerHTML = '<i class="bx bx-save me-1"></i>Save Guest';
                    });
                });
            }
            @endcan
         });
         </script>
         @endpush
@endsection
