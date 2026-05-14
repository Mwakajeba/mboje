# Laravel API Setup for Hotel Portal

This document explains how to set up the Laravel API endpoints so that rooms created in your Laravel system will appear on the Next.js website.

## Step 1: Create API Controller for Rooms

Create a new API controller: `app/Http/Controllers/Api/RoomApiController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotel\Room;
use Illuminate\Http\Request;

class RoomApiController extends Controller
{
    /**
     * Get all rooms (for public website)
     */
    public function index(Request $request)
    {
        $query = Room::where('status', '!=', 'maintenance')
            ->where('status', '!=', 'out_of_order');

        // Filter by availability if check-in/check-out dates provided
        if ($request->has('check_in') && $request->has('check_out')) {
            $checkIn = $request->check_in;
            $checkOut = $request->check_out;
            
            // Get rooms that are not booked during this period
            $query->whereDoesntHave('bookings', function ($q) use ($checkIn, $checkOut) {
                $q->where(function ($query) use ($checkIn, $checkOut) {
                    $query->whereBetween('check_in', [$checkIn, $checkOut])
                        ->orWhereBetween('check_out', [$checkIn, $checkOut])
                        ->orWhere(function ($q) use ($checkIn, $checkOut) {
                            $q->where('check_in', '<=', $checkIn)
                              ->where('check_out', '>=', $checkOut);
                        });
                })->where('status', '!=', 'cancelled');
            });
        }

        $rooms = $query->get()->map(function ($room) {
            return [
                'id' => $room->id,
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
        });

        return response()->json([
            'success' => true,
            'data' => $rooms,
        ]);
    }

    /**
     * Get single room details
     */
    public function show($id)
    {
        $room = Room::findOrFail($id);

        $roomData = [
            'id' => $room->id,
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
        
        if ($room->has_wifi) $amenities[] = 'Free WiFi';
        if ($room->has_ac) $amenities[] = 'Air Conditioning';
        if ($room->has_tv) $amenities[] = 'Smart TV';
        if ($room->has_balcony) $amenities[] = 'Balcony';
        if ($room->has_kitchen) $amenities[] = 'Kitchen';
        
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
        // If you have an images relationship or column, return it here
        // For now, return empty array or default image
        if ($room->images && is_array($room->images)) {
            return $room->images;
        }
        
        // Return default placeholder or empty
        return [];
    }
}
```

## Step 2: Add API Routes

Add these routes to `routes/api.php`:

```php
// Public Room API (no authentication required)
Route::prefix('rooms')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\RoomApiController::class, 'index']);
    Route::get('/{id}', [App\Http\Controllers\Api\RoomApiController::class, 'show']);
});
```

## Step 3: Enable CORS (if needed)

If you get CORS errors, make sure CORS is enabled in `config/cors.php`:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_methods' => ['*'],
'allowed_origins' => ['http://localhost:3000'], // Add your Next.js URL
'allowed_origins_patterns' => [],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => true,
```

## Step 4: Update Room Model (if needed)

Make sure your Room model has the necessary relationships and attributes. If you need to add image support:

```php
// In your Room migration, add:
$table->json('images')->nullable();

// In your Room model:
protected $casts = [
    'amenities' => 'array',
    'images' => 'array',
];
```

## Step 5: Test the API

1. Create a room in your Laravel system at `http://127.0.0.1:8000/rooms/create`
2. Test the API endpoint: `http://127.0.0.1:8000/api/rooms`
3. The room should appear in the JSON response
4. Refresh your Next.js website - the room should appear automatically

## Notes

- Rooms with status "maintenance" or "out_of_order" will not appear on the public website
- The API automatically filters out booked rooms when check-in/check-out dates are provided
- Make sure your room creation form includes all necessary fields (name, type, price, capacity, etc.)
