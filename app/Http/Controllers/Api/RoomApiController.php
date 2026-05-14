<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotel\Room;
use App\Helpers\HashIdHelper;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RoomApiController extends Controller
{
    /**
     * Get all rooms (for public website)
     * GET /api/rooms
     */
    public function index(Request $request)
    {
        $query = Room::where('status', '!=', 'maintenance')
            ->where('status', '!=', 'out_of_order');

        // Filter by availability if check-in/check-out dates provided
        $checkIn = $request->get('check_in');
        $checkOut = $request->get('check_out');
        
        if ($checkIn && $checkOut) {
            // Get rooms that are not booked during this period
            $query->whereDoesntHave('bookings', function ($q) use ($checkIn, $checkOut) {
                $q->whereIn('status', ['confirmed', 'checked_in', 'pending'])
                    ->where(function ($query) use ($checkIn, $checkOut) {
                        // Overlap detection: booking overlaps if it starts before selected range ends 
                        // AND ends after selected range starts
                        $query->where('check_in', '<', $checkOut)
                              ->where('check_out', '>', $checkIn);
                    });
            });
            
            // Also filter out rooms that are in maintenance or out of order
            $query->where('status', 'available');
        }

        // Get pagination parameters
        $perPage = $request->get('per_page', 12);
        $page = $request->get('page', 1);
        
        $rooms = $query->paginate($perPage, ['*'], 'page', $page);

        $roomsData = $rooms->getCollection()->map(function ($room) use ($checkIn, $checkOut) {
            // Check if room is booked today
            $today = Carbon::today()->toDateString();
            $isBookedToday = $room->bookings()
                ->whereIn('status', ['confirmed', 'checked_in', 'pending'])
                ->where('check_in', '<=', $today)
                ->where('check_out', '>=', $today)
                ->exists();

            // Check if room is booked for the search dates (if provided)
            $isBookedForDates = false;
            if ($checkIn && $checkOut) {
                $isBookedForDates = $room->bookings()
                    ->whereIn('status', ['confirmed', 'checked_in', 'pending'])
                    ->where(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in', '<', $checkOut)
                          ->where('check_out', '>', $checkIn);
                    })
                    ->exists();
            }

            // Determine actual status
            $actualStatus = $room->status;
            if ($room->status === 'available') {
                if ($isBookedToday || $isBookedForDates) {
                    $actualStatus = 'booked';
                }
            }

            return [
                'id' => $room->id,
                'hashid' => $room->hash_id,
                'name' => $room->room_name ?? $room->room_number,
                'room_number' => $room->room_number,
                'type' => $room->room_type,
                'price' => (float) $room->rate_per_night,
                'max_adults' => (int) $room->capacity,
                'max_children' => (int) ($room->max_children ?? 0),
                'status' => $this->mapStatus($actualStatus),
                'description' => $room->description,
                'amenities' => $this->getAmenities($room),
                'images' => $this->getImages($room),
                'created_at' => $room->created_at?->toISOString(),
                'updated_at' => $room->updated_at?->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $roomsData,
            'pagination' => [
                'current_page' => $rooms->currentPage(),
                'last_page' => $rooms->lastPage(),
                'per_page' => $rooms->perPage(),
                'total' => $rooms->total(),
                'from' => $rooms->firstItem(),
                'to' => $rooms->lastItem(),
            ],
        ]);

        return response()->json([
            'success' => true,
            'data' => $rooms,
        ]);
    }

    /**
     * Get single room details
     * GET /api/rooms/{id} - Supports both hashid and numeric ID
     */
    public function show($id)
    {
        // Try to decode hashid first
        $decodedId = HashIdHelper::decode($id);
        
        if ($decodedId !== null) {
            // It's a hashid, use decoded ID
            $room = Room::findOrFail($decodedId);
        } else {
            // Try as numeric ID (for backward compatibility)
            if (is_numeric($id)) {
                $room = Room::findOrFail($id);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Room not found',
                ], 404);
            }
        }

        $roomData = [
            'id' => $room->id,
            'hashid' => $room->hash_id,
            'name' => $room->room_name ?? $room->room_number,
            'room_number' => $room->room_number,
            'type' => $room->room_type,
            'price' => (float) $room->rate_per_night,
            'max_adults' => (int) $room->capacity,
            'max_children' => (int) ($room->max_children ?? 0),
            'status' => $this->mapStatus($room->status),
            'description' => $room->description,
            'amenities' => $this->getAmenities($room),
            'images' => $this->getImages($room),
            'created_at' => $room->created_at?->toISOString(),
            'updated_at' => $room->updated_at?->toISOString(),
        ];

        return response()->json([
            'success' => true,
            'data' => $roomData,
        ]);
    }

    /**
     * Map Laravel room status to API status
     */
    private function mapStatus($status)
    {
        $statusMap = [
            'available' => 'available',
            'occupied' => 'booked',
            'maintenance' => 'maintenance',
            'out_of_order' => 'out_of_order',
        ];

        return $statusMap[$status] ?? 'available';
    }

    /**
     * Get amenities array from room model
     */
    private function getAmenities($room)
    {
        $amenities = [];
        
        if (isset($room->has_wifi) && $room->has_wifi) {
            $amenities[] = 'Free WiFi';
        }
        if (isset($room->has_ac) && $room->has_ac) {
            $amenities[] = 'Air Conditioning';
        }
        if (isset($room->has_tv) && $room->has_tv) {
            $amenities[] = 'Smart TV';
        }
        if (isset($room->has_balcony) && $room->has_balcony) {
            $amenities[] = 'Balcony';
        }
        if (isset($room->has_kitchen) && $room->has_kitchen) {
            $amenities[] = 'Kitchen';
        }
        
        // Also check amenities array if it exists
        if (is_array($room->amenities)) {
            foreach ($room->amenities as $amenity) {
                $amenityMap = [
                    'wifi' => 'Free WiFi',
                    'ac' => 'Air Conditioning',
                    'tv' => 'Smart TV',
                    'minibar' => 'Mini Bar',
                    'balcony' => 'Balcony',
                    'kitchen' => 'Kitchen',
                    'ocean_view' => 'Ocean View',
                    'city_view' => 'City View',
                ];
                
                if (isset($amenityMap[$amenity]) && !in_array($amenityMap[$amenity], $amenities)) {
                    $amenities[] = $amenityMap[$amenity];
                }
            }
        }
        
        return array_unique($amenities);
    }

    /**
     * Get images array from room model
     */
    private function getImages($room)
    {
        // If images is already an array, return it
        if (isset($room->images) && is_array($room->images)) {
            return $room->images;
        }
        
        // If images is stored as JSON string, decode it
        if (isset($room->images) && is_string($room->images)) {
            $decoded = json_decode($room->images, true);
            if (is_array($decoded) && count($decoded) > 0) {
                return $decoded;
            }
        }
        
        // Return placeholder image if no images
        return ['https://via.placeholder.com/800x600?text=Room+Image'];
    }
}
