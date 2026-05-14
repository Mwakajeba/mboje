<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\Hotel\Room;
use App\Models\Hotel\Property;
use App\Models\Property\Lease;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view rooms');

        $branch_id = session('branch_id') ?? user()->branch_id ?? 1;

        if ($request->ajax()) {
        $rooms = Room::with(['property', 'bookings'])
                ->where('branch_id', $branch_id)
                ->select('rooms.*');

            $dataTable = DataTables::of($rooms)
                ->addColumn('room_info', function ($room) {
                    $info = '<strong>' . $room->room_number . '</strong>';
                    if ($room->room_name) {
                        $info .= '<br><small class="text-muted">' . $room->room_name . '</small>';
                    }
                    return $info;
                })
                ->addColumn('room_type_badge', function ($room) {
                    return '<span class="badge bg-light text-dark">' . ucfirst($room->room_type) . '</span>';
                })
                ->addColumn('status_info', function ($room) use ($request) {
                    $checkIn = $request->get('check_in');
                    $checkOut = $request->get('check_out');
                    if ($checkIn && $checkOut) {
                        $in = \Carbon\Carbon::parse($checkIn);
                        $out = \Carbon\Carbon::parse($checkOut);
                        $available = $room->isAvailableForDates($in, $out);
                        if ($available) {
                            return '<span class="badge bg-success">Available</span><br><small class="text-muted">Free for selected dates</small>';
                        }
                        $conflict = $room->bookings()
                            ->whereIn('status', ['pending','confirmed','checked_in'])
                            ->where(function($q) use ($in,$out){
                                $q->where('check_in','<',$out)->where('check_out','>',$in);
                            })
                            ->orderBy('check_in')
                            ->first();
                        $guest = $conflict? ($conflict->guest->first_name . ' ' . $conflict->guest->last_name) : 'Unknown';
                        $status = '<span class="badge bg-warning">Occupied</span><br>';
                        $status .= '<small class="text-muted">';
                        $status .= '<strong>Guest:</strong> ' . e($guest) . '<br>';
                        if ($conflict) {
                            $status .= '<strong>Range:</strong> ' . $conflict->check_in->format('M d, Y') . ' - ' . $conflict->check_out->format('M d, Y');
                        }
                        $status .= '</small>';
                        return $status;
                    }
                    $availability = $room->availability_status;
                    if ($availability['status'] === 'available') {
                        return '<span class="badge bg-success">Available Now</span><br><small class="text-muted">Ready for booking</small>';
                    }
                    $status = '<span class="badge bg-warning">Occupied</span><br>';
                    $status .= '<small class="text-muted">';
                    $status .= '<strong>Guest:</strong> ' . $availability['current_guest'] . '<br>';
                    $status .= '<strong>Check-out:</strong> ' . $availability['check_out_date'] . '<br>';
                    $status .= '<strong>Available:</strong> ' . $availability['next_available'];
                    if ($availability['days_until_available'] > 0) {
                        $roundedDays = is_numeric($availability['days_until_available']) ? round($availability['days_until_available']) : $availability['days_until_available'];
                        $status .= ' <span class="text-warning">(' . $roundedDays . ' days)</span>';
                    } else {
                        $status .= ' <span class="text-success">(Today)</span>';
                    }
                    $status .= '</small>';
                    return $status;
                })
                ->addColumn('rate_formatted', function ($room) {
                    return 'TSh ' . number_format($room->rate_per_night, 0);
                })
                ->addColumn('capacity_info', function ($room) {
                    return $room->capacity . ' guests';
                })
                ->addColumn('actions', function ($room) {
                    $actions = '<div class="btn-group" role="group">';
                    $actions .= '<a href="' . route('rooms.show', $room) . '" class="btn btn-sm btn-outline-primary" title="View"><i class="bx bx-show"></i></a>';
                    $actions .= '<a href="' . route('rooms.edit', $room) . '" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bx bx-edit"></i></a>';
                    $actions .= '<button type="button" class="btn btn-sm btn-outline-danger" title="Delete" onclick="deleteRoom(' . $room->id . ', \'' . $room->room_number . '\')"><i class="bx bx-trash"></i></button>';
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['room_info', 'room_type_badge', 'status_info', 'actions']);

            // Build summary counts (date-aware if filters provided; otherwise current occupancy)
            $checkIn = $request->get('check_in');
            $checkOut = $request->get('check_out');
            $totalRooms = Room::where('branch_id', Auth::user()->branch_id)->count();
            $maintenanceRooms = Room::where('branch_id', Auth::user()->branch_id)->where('status', 'maintenance')->count();
            $availableRoomsCount = 0;
            $occupiedRoomsCount = 0;
            $roomsInBranch = Room::where('branch_id', Auth::user()->branch_id)->get();
            if ($checkIn && $checkOut) {
                $in = \Carbon\Carbon::parse($checkIn);
                $out = \Carbon\Carbon::parse($checkOut);
                foreach ($roomsInBranch as $rm) {
                    if ($rm->status === 'maintenance') { continue; }
                    if ($rm->isAvailableForDates($in, $out)) { $availableRoomsCount++; } else { $occupiedRoomsCount++; }
                }
            } else {
                foreach ($roomsInBranch as $rm) {
                    if ($rm->status === 'maintenance') { continue; }
                    if ($rm->is_occupied) { $occupiedRoomsCount++; } else { $availableRoomsCount++; }
                }
            }

            return $dataTable
                ->with([
                    'summary' => [
                        'total' => $totalRooms,
                        'available' => $availableRoomsCount,
                        'occupied' => $occupiedRoomsCount,
                        'maintenance' => $maintenanceRooms,
                    ]
                ])
                ->make(true);
        }

        $totalRooms = Room::where('branch_id', Auth::user()->branch_id)->count();
        $maintenanceRooms = Room::where('branch_id', Auth::user()->branch_id)->where('status', 'maintenance')->count();
        // Compute availability based on current occupancy, not raw status
        $availableRooms = 0;
        $occupiedRooms = 0;
        $roomsNow = Room::where('branch_id', Auth::user()->branch_id)->get();
        foreach ($roomsNow as $rm) {
            if ($rm->status === 'maintenance') { continue; }
            if ($rm->is_occupied) { $occupiedRooms++; } else { $availableRooms++; }
        }

        return view('hotel.rooms.index', compact(
            'totalRooms',
            'availableRooms',
            'occupiedRooms',
            'maintenanceRooms'
        ));
    }

    public function create()
    {
        $this->authorize('create room');
        
        $properties = Property::orderBy('name')->get();
        
        return view('hotel.rooms.create', compact('properties'));
    }

    public function store(Request $request)
    {
        $this->authorize('create room');
        
        $request->validate([
            'room_number' => 'required|string|max:50|unique:rooms,room_number',
            'room_name' => 'nullable|string|max:255',
            'room_type' => 'required|string|in:single,double,twin,suite,deluxe',
            'capacity' => 'required|integer|min:1|max:10',
            'rate_per_night' => 'required|numeric|min:0',
            'rate_per_month' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:available,occupied,maintenance,out_of_order',
            'description' => 'nullable|string|max:1000',
            'size_sqm' => 'nullable|numeric|min:0',
            'floor_number' => 'nullable|string|max:50',
            'view_type' => 'nullable|string|in:city_view,ocean_view,garden_view,street_view',
            'property_id' => 'nullable|exists:properties,id',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string|in:wifi,ac,tv,minibar,balcony,kitchen,ocean_view,city_view,smoking',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,jpg,png,webp|max:5120', // 5MB max per image
        ]);

        DB::beginTransaction();
        
        try {
            $amenities = $request->input('amenities', []);
            
            $branch_id = session('branch_id') ?? user()->branch_id ?? 1;

            // Handle image uploads
            $imagePaths = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('rooms', 'public');
                    $imagePaths[] = asset('storage/' . $path);
                }
            }

            $room = Room::create([
                'room_number' => $request->room_number,
                'room_name' => $request->room_name,
                'room_type' => $request->room_type,
                'capacity' => $request->capacity,
                'rate_per_night' => $request->rate_per_night,
                'rate_per_month' => $request->rate_per_month ?? 0,
                'status' => $request->status,
                'description' => $request->description,
                'size_sqm' => $request->size_sqm,
                'floor_number' => $request->floor_number,
                'view_type' => $request->view_type,
                'property_id' => $request->property_id,
                'amenities' => $amenities,
                'images' => $imagePaths,
                'has_wifi' => in_array('wifi', $amenities),
                'has_ac' => in_array('ac', $amenities),
                'has_tv' => in_array('tv', $amenities),
                'has_balcony' => in_array('balcony', $amenities),
                'has_kitchen' => in_array('kitchen', $amenities),
                'branch_id' => $branch_id,
                'company_id' => Auth::user()->company_id,
                'created_by' => Auth::id()
            ]);

            DB::commit();
            
            return redirect()->route('rooms.index')
                ->with('success', 'Room created successfully!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create room: ' . $e->getMessage());
        }
    }

    public function show(Room $room)
    {
        $this->authorize('view room details');
        
        $room->load(['property', 'bookings.guest', 'createdBy']);
        
        return view('hotel.rooms.show', compact('room'));
    }

    public function edit(Room $room)
    {
        $this->authorize('edit room');
        
        $properties = Property::orderBy('name')->get();
        
        return view('hotel.rooms.edit', compact('room', 'properties'));
    }

    public function update(Request $request, Room $room)
    {
        $this->authorize('edit room');
        
        $request->validate([
            'room_number' => 'required|string|max:50|unique:rooms,room_number,' . $room->id,
            'room_name' => 'nullable|string|max:255',
            'room_type' => 'required|string|in:single,double,twin,suite,deluxe',
            'capacity' => 'required|integer|min:1|max:10',
            'rate_per_night' => 'required|numeric|min:0',
            'rate_per_month' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:available,occupied,maintenance,out_of_order',
            'description' => 'nullable|string|max:1000',
            'size_sqm' => 'nullable|numeric|min:0',
            'floor_number' => 'nullable|string|max:50',
            'view_type' => 'nullable|string|in:city_view,ocean_view,garden_view,street_view',
            'property_id' => 'nullable|exists:properties,id',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string|in:wifi,ac,tv,minibar,balcony,kitchen,ocean_view,city_view,smoking',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,jpg,png,webp|max:5120', // 5MB max per image
        ]);

        DB::beginTransaction();
        
        try {
            $amenities = $request->input('amenities', []);
            
            // Handle existing images
            $existingImages = [];
            if ($request->has('existing_images')) {
                $existingImages = json_decode($request->existing_images, true) ?? [];
            }
            
            // Handle removed images
            $removedImages = [];
            if ($request->has('removed_images')) {
                $removedImages = json_decode($request->removed_images, true) ?? [];
            }
            
            // Filter out removed images
            $currentImages = array_filter($existingImages, function($img) use ($removedImages) {
                return !in_array($img, $removedImages);
            });
            
            // Handle new image uploads
            $newImagePaths = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('rooms', 'public');
                    $newImagePaths[] = asset('storage/' . $path);
                }
            }
            
            // Combine existing and new images
            $allImages = array_merge(array_values($currentImages), $newImagePaths);
            
            $room->update([
                'room_number' => $request->room_number,
                'room_name' => $request->room_name,
                'room_type' => $request->room_type,
                'capacity' => $request->capacity,
                'rate_per_night' => $request->rate_per_night,
                'rate_per_month' => $request->rate_per_month ?? 0,
                'status' => $request->status,
                'description' => $request->description,
                'size_sqm' => $request->size_sqm,
                'floor_number' => $request->floor_number,
                'view_type' => $request->view_type,
                'property_id' => $request->property_id,
                'amenities' => $amenities,
                'images' => $allImages,
                'has_wifi' => in_array('wifi', $amenities),
                'has_ac' => in_array('ac', $amenities),
                'has_tv' => in_array('tv', $amenities),
                'has_balcony' => in_array('balcony', $amenities),
                'has_kitchen' => in_array('kitchen', $amenities)
            ]);

            DB::commit();
            
            return redirect()->route('rooms.show', $room)
                ->with('success', 'Room updated successfully!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update room: ' . $e->getMessage());
        }
    }

    public function destroy(Room $room)
    {
        $this->authorize('delete room');
        
        try {
            // Prevent deletion if room has any bookings or leases
            $hasBookings = $room->bookings()->exists();
            $hasLeases = Lease::where('room_id', $room->id)->exists();
            if ($hasBookings || $hasLeases) {
                return redirect()->back()
                    ->with('error', 'Cannot delete room: it is linked to existing ' . ($hasBookings ? 'bookings' : 'leases') . '.');
            }
            $room->delete();
            
            return redirect()->route('rooms.index')
                ->with('success', 'Room deleted successfully!');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete room: ' . $e->getMessage());
        }
    }
}
