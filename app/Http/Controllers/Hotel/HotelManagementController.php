<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\Hotel\Property;
use App\Models\Hotel\Room;
use App\Models\Hotel\Booking;
use App\Models\Hotel\Guest;
use App\Models\Hotel\GuestMessage;
use App\Models\Branch;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class HotelManagementController extends Controller
{
    public function index()
    {
        $this->authorize('view hotel management');
        
        // Get summary statistics
        $totalProperties = Property::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->byType('hotel')
            ->count();
            
        $totalRooms = Room::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->count();
            
        $availableRooms = Room::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->available()
            ->count();
            
        $totalBookings = Booking::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->where('check_in', '>=', now()->startOfMonth())
            ->where('check_in', '<=', now()->endOfMonth())
            ->count();
            
        $totalGuests = Guest::forCompany(current_company_id())
            ->count();
            
        $currentOccupancy = $this->calculateCurrentOccupancy();
        $monthlyRevenue = $this->calculateMonthlyRevenue();
        
        // Rooms Occupied - count of currently occupied rooms
        $roomsOccupied = Booking::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->whereDate('check_in', '<=', now())
            ->whereDate('check_out', '>=', now())
            ->distinct('room_id')
            ->count('room_id');
        
        // Today's Bookings - bookings starting today (value and count)
        $todaysBookingsValue = Booking::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->whereDate('check_in', now()->toDateString())
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');
        $todaysBookingsCount = Booking::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->whereDate('check_in', now()->toDateString())
            ->where('status', '!=', 'cancelled')
            ->count();

        // Online Bookings - count of bookings with online_booking status
        $onlineBookings = Booking::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->where('status', 'online_booking')
            ->count();

        // Guest Messages - count of unread messages
        // Use same branch detection pattern as BookingController
        $branchId = session('branch_id') ?? Auth::user()->branch_id ?? null;
        
        try {
            $unreadQuery = GuestMessage::where('is_read', false);
            $totalQuery = GuestMessage::query();
            
            if ($branchId) {
                $unreadQuery->where('branch_id', $branchId);
                $totalQuery->where('branch_id', $branchId);
            } else {
                $unreadQuery->whereNull('branch_id');
                $totalQuery->whereNull('branch_id');
            }
            
            $unreadMessages = $unreadQuery->count();
            $totalMessages = $totalQuery->count();
        } catch (\Exception $e) {
            // Table might not exist yet, set defaults
            $unreadMessages = 0;
            $totalMessages = 0;
        }

        return view('hotel.management.index', compact(
            'totalProperties',
            'totalRooms',
            'availableRooms',
            'totalBookings',
            'totalGuests',
            'currentOccupancy',
            'monthlyRevenue',
            'roomsOccupied',
            'todaysBookingsValue',
            'todaysBookingsCount',
            'onlineBookings',
            'unreadMessages',
            'totalMessages'
        ));
    }

    private function calculateCurrentOccupancy()
    {
        $totalRooms = Room::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->count();
            
        if ($totalRooms == 0) return 0;
        
        $occupiedRooms = Booking::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->where('check_in', '<=', now())
            ->where('check_out', '>=', now())
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->distinct('room_id')
            ->count('room_id');
            
        return round(($occupiedRooms / $totalRooms) * 100, 2);
    }

    private function calculateMonthlyRevenue()
    {
        return Booking::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->where('check_in', '>=', now()->startOfMonth())
            ->where('check_in', '<=', now()->endOfMonth())
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');
    }

    /**
     * Get room availability status for a date range
     */
    public function getRoomStatus(Request $request)
    {
        $this->authorize('view hotel management');

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        // Load ALL rooms (not just those with bookings) to show complete status
        $rooms = Room::with(['property', 'bookings' => function ($query) use ($startDate, $endDate) {
            // Load bookings that overlap with the selected date range
            // Overlap logic: booking.check_in < selected.end_date AND booking.check_out > selected.start_date
            $query->where('status', '!=', 'cancelled')
                ->whereIn('status', ['confirmed', 'checked_in', 'pending'])
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->where('check_in', '<', $endDate)
                      ->where('check_out', '>', $startDate);
                })
                ->with('guest');
        }])
        ->forBranch(current_branch_id())
        ->forCompany(current_company_id())
        ->orderBy('room_number')
        ->get();

        $roomStatus = $rooms->map(function ($room) use ($startDate, $endDate) {
            // Check if room is available for the selected date range
            // A room is booked if ANY booking overlaps with the selected date range
            $isAvailable = $room->isAvailableForDateRange($startDate, $endDate);
            
            // Get conflicting bookings that overlap with the selected date range
            // Overlap: booking.check_in < selected.end_date AND booking.check_out > selected.start_date
            $conflictingBookings = $room->bookings->filter(function ($booking) use ($startDate, $endDate) {
                // Check if booking overlaps with selected date range
                return $booking->check_in < $endDate && $booking->check_out > $startDate;
            });

            return [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'room_name' => $room->room_name,
                'full_name' => $room->full_room_name,
                'property_name' => $room->property->name ?? 'N/A',
                'status' => $room->status,
                'is_available' => $isAvailable,
                'conflicting_bookings' => $conflictingBookings->map(function ($booking) {
                    return [
                        'booking_number' => $booking->booking_number,
                        'guest_name' => $booking->guest ? $booking->guest->first_name . ' ' . $booking->guest->last_name : 'N/A',
                        'check_in' => $booking->check_in->format('Y-m-d'),
                        'check_out' => $booking->check_out->format('Y-m-d'),
                        'status' => $booking->status,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'rooms' => $roomStatus,
            'summary' => [
                'total' => $rooms->count(),
                'available' => $roomStatus->where('is_available', true)->count(),
                'booked' => $roomStatus->where('is_available', false)->count(),
            ],
        ]);
    }

    /**
     * Show hotel settings (e.g. Terms and Conditions for booking PDFs)
     */
    public function settings()
    {
        $this->authorize('view hotel management');

        $termsAndConditions = SystemSetting::getValue('hotel_terms_and_conditions', '');

        return view('hotel.management.settings', compact('termsAndConditions'));
    }

    /**
     * Update hotel settings (Terms and Conditions)
     */
    public function updateSettings(Request $request)
    {
        $this->authorize('view hotel management');

        $request->validate([
            'terms_and_conditions' => 'nullable|string|max:10000',
        ]);

        SystemSetting::setValue(
            'hotel_terms_and_conditions',
            $request->input('terms_and_conditions', ''),
            'string',
            'hotel',
            'Hotel Terms and Conditions',
            'Shown in the footer of booking PDF exports'
        );

        return redirect()->route('hotel.management.index')
            ->with('success', 'Hotel settings saved successfully. Terms and Conditions will appear on booking PDF exports.');
    }
}