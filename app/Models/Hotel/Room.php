<?php

namespace App\Models\Hotel;

use App\Traits\LogsActivity;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Helpers\HashIdHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'property_id',
        'room_number',
        'room_name',
        'room_type',
        'capacity',
        'rate_per_night',
        'rate_per_month',
        'status',
        'description',
        'amenities',
        'images',
        'size_sqm',
        'floor_number',
        'view_type',
        'has_balcony',
        'has_kitchen',
        'has_wifi',
        'has_ac',
        'has_tv',
        'branch_id',
        'company_id',
        'created_by'
    ];

    protected $casts = [
        'rate_per_night' => 'decimal:2',
        'rate_per_month' => 'decimal:2',
        'amenities' => 'array',
        'images' => 'array',
        'size_sqm' => 'decimal:2',
        'has_balcony' => 'boolean',
        'has_kitchen' => 'boolean',
        'has_wifi' => 'boolean',
        'has_ac' => 'boolean',
        'has_tv' => 'boolean'
    ];

    // Relationships
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    // Scopes
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('room_type', $type);
    }

    public function scopeByProperty($query, $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    // Accessors
    public function getFullRoomNameAttribute()
    {
        return $this->room_name ? "{$this->room_number} - {$this->room_name}" : $this->room_number;
    }

    public function getCurrentBookingAttribute()
    {
        return $this->bookings()
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->where('check_in', '<=', now())
            ->where('check_out', '>=', now())
            ->first();
    }

    public function getIsOccupiedAttribute()
    {
        // Room is occupied if there's a current booking (confirmed or checked-in) for today
        return $this->current_booking !== null;
    }

    public function getIsAvailableForBookingAttribute()
    {
        // Room is available for booking if:
        // 1. Room status is 'available'
        // 2. Room is not currently occupied (no active bookings)
        // 3. Room is not in maintenance or out of order
        return $this->status === 'available' && 
               !$this->is_occupied &&
               !in_array($this->status, ['maintenance', 'out_of_order']);
    }

    // Methods
    public function isAvailableForDates($checkIn, $checkOut)
    {
        // Use half-open interval logic: overlap if (existing.check_in < new.check_out) AND (existing.check_out > new.check_in)
        // Room is booked when status is: online_booking, confirmed, checked_in
        // Room is NOT booked when status is: pending
        return !$this->bookings()
            ->whereIn('status', ['confirmed', 'checked_in', 'online_booking']) // Exclude pending
            ->where(function ($q) use ($checkIn, $checkOut) {
                $q->where('check_in', '<', $checkOut)
                  ->where('check_out', '>', $checkIn);
            })
            ->exists();
    }

    public function getTotalRevenue($startDate = null, $endDate = null)
    {
        $query = $this->bookings()->where('status', '!=', 'cancelled');
        
        if ($startDate) {
            $query->where('check_in', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('check_out', '<=', $endDate);
        }
        
        return $query->sum('total_amount');
    }

    public function getOccupancyRate($startDate = null, $endDate = null)
    {
        $startDate = $startDate ?: now()->startOfMonth();
        $endDate = $endDate ?: now()->endOfMonth();
        
        $totalDays = $startDate->diffInDays($endDate) + 1;
        $occupiedDays = $this->bookings()
            ->where('status', '!=', 'cancelled')
            ->where('check_in', '<=', $endDate)
            ->where('check_out', '>=', $startDate)
            ->sum('nights');
            
        return $totalDays > 0 ? round(($occupiedDays / $totalDays) * 100, 2) : 0;
    }

    /**
     * Get the next available date for this room
     */
    public function getNextAvailableDateAttribute()
    {
        $nextBooking = $this->bookings()
            ->where('status', '!=', 'cancelled')
            ->where('check_out', '>', now())
            ->orderBy('check_out', 'asc')
            ->first();

        if ($nextBooking) {
            return $nextBooking->check_out->addDay();
        }

        return now();
    }

    /**
     * Get availability status with next available date
     */
    public function getAvailabilityStatusAttribute()
    {
        if ($this->is_occupied) {
            $currentBooking = $this->current_booking;
            $nextAvailable = $this->next_available_date;
            
            return [
                'status' => 'occupied',
                'current_guest' => $currentBooking ? $currentBooking->guest->first_name . ' ' . $currentBooking->guest->last_name : 'Unknown',
                'check_out_date' => $currentBooking ? $currentBooking->check_out->format('M d, Y') : null,
                'next_available' => $nextAvailable->format('M d, Y'),
                'days_until_available' => now()->diffInDays($nextAvailable, false)
            ];
        }

        return [
            'status' => 'available',
            'current_guest' => null,
            'check_out_date' => null,
            'next_available' => now()->format('M d, Y'),
            'days_until_available' => 0
        ];
    }

    /**
     * Get upcoming bookings for this room
     */
    public function getUpcomingBookingsAttribute()
    {
        return $this->bookings()
            ->where('status', '!=', 'cancelled')
            ->where('check_in', '>', now())
            ->orderBy('check_in', 'asc')
            ->get();
    }

    /**
     * Check if room is available for a specific date range
     * 
     * A room is considered BOOKED (not available) if ANY booking overlaps with the selected date range.
     * Overlap detection: A booking overlaps if:
     *   - booking.check_in < selected.end_date AND
     *   - booking.check_out > selected.start_date
     * 
     * Examples:
     * - Selected: Jan 10-15, Booking: Jan 8-12 → OVERLAP (booked)
     * - Selected: Jan 10-15, Booking: Jan 12-18 → OVERLAP (booked)
     * - Selected: Jan 10-15, Booking: Jan 5-20 → OVERLAP (booked)
     * - Selected: Jan 10-15, Booking: Jan 1-9 → NO OVERLAP (available)
     * - Selected: Jan 10-15, Booking: Jan 16-20 → NO OVERLAP (available)
     * 
     * @param Carbon $checkIn Start date of the date range to check
     * @param Carbon $checkOut End date of the date range to check
     * @param int|null $excludeBookingId Optional booking ID to exclude from check (for updates)
     * @return bool True if available, false if booked
     */
    public function isAvailableForDateRange($checkIn, $checkOut, $excludeBookingId = null)
    {
        $query = $this->bookings()
            ->where('status', '!=', 'cancelled')
            ->whereIn('status', ['confirmed', 'checked_in', 'pending'])
            ->where(function ($q) use ($checkIn, $checkOut) {
                // Overlap detection: booking overlaps if it starts before selected range ends 
                // AND ends after selected range starts
                $q->where('check_in', '<', $checkOut)
                  ->where('check_out', '>', $checkIn);
            });

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        // If any overlapping booking exists, room is NOT available (booked)
        return !$query->exists();
    }

    /**
     * Get the hash ID for this model.
     */
    public function getHashIdAttribute()
    {
        return HashIdHelper::encode($this->id);
    }

    /**
     * Resolve the model instance for route model binding using hash ID.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // If field is hash_id or null, decode the hash ID
        if ($field === 'hash_id' || $field === null) {
            $id = HashIdHelper::decode($value);
            if ($id !== null) {
                return $this->findOrFail($id);
            }
        }

        return parent::resolveRouteBinding($value, $field);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKey()
    {
        return HashIdHelper::encode($this->id);
    }

    /**
     * Get the route key name for the model.
     */
    public function getRouteKeyName()
    {
        return 'hash_id';
    }
}