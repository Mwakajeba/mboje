<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\Hotel\Guest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GuestController extends Controller
{
    public function index()
    {
        $this->authorize('view guests');
        
        $guests = Guest::with(['company', 'createdBy'])
            ->orderBy('first_name')
            ->paginate(20);

        $totalGuests = Guest::count();
        $activeGuests = Guest::where('status', 'active')->count();
        $newGuests = Guest::where('created_at', '>=', now()->subDays(30))->count();

        return view('hotel.guests.index', compact(
            'guests',
            'totalGuests',
            'activeGuests',
            'newGuests'
        ));
    }

    public function create()
    {
        $this->authorize('create guest');
        
        return view('hotel.guests.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create guest');
        
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:guests,email',
            'phone' => 'nullable|string|max:50',
            'id_number' => 'nullable|string|max:50',
            'id_type' => 'nullable|string|in:passport,national_id,driving_license,other',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|string|in:male,female,other',
            'nationality' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'special_requests' => 'nullable|string|max:1000',
            'status' => 'required|string|in:active,inactive,blacklisted',
            'notes' => 'nullable|string|max:1000'
        ]);

        DB::beginTransaction();
        
        try {
            // Generate guest number
            $guestNumber = 'G' . str_pad(Guest::count() + 1, 6, '0', STR_PAD_LEFT);
            
            $guest = Guest::create([
                'guest_number' => $guestNumber,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'id_number' => $request->id_number,
                'id_type' => $request->id_type,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'nationality' => $request->nationality,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country,
                'postal_code' => $request->postal_code,
                'emergency_contact_name' => $request->emergency_contact_name,
                'emergency_contact_phone' => $request->emergency_contact_phone,
                'special_requests' => $request->special_requests,
                'status' => $request->status,
                'notes' => $request->notes,
                'company_id' => Auth::user()->company_id,
                'created_by' => Auth::id()
            ]);

            DB::commit();

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Guest created successfully!',
                    'guest' => [
                        'id' => $guest->id,
                        'guest_number' => $guest->guest_number,
                        'full_name' => $guest->full_name,
                    ],
                ]);
            }

            return redirect()->route('guests.index')
                ->with('success', 'Guest created successfully!');
                
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create guest: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create guest: ' . $e->getMessage());
        }
    }

    public function show(Guest $guest)
    {
        $this->authorize('view guest details');
        
        $guest->load(['company', 'createdBy', 'bookings.room']);
        
        return view('hotel.guests.show', compact('guest'));
    }

    public function edit(Guest $guest)
    {
        $this->authorize('edit guest');
        
        return view('hotel.guests.edit', compact('guest'));
    }

    public function update(Request $request, Guest $guest)
    {
        $this->authorize('edit guest');
        
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:guests,email,' . $guest->id,
            'phone' => 'nullable|string|max:50',
            'id_number' => 'nullable|string|max:50',
            'id_type' => 'nullable|string|in:passport,national_id,driving_license,other',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|string|in:male,female,other',
            'nationality' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'special_requests' => 'nullable|string|max:1000',
            'status' => 'required|string|in:active,inactive,blacklisted',
            'notes' => 'nullable|string|max:1000'
        ]);

        DB::beginTransaction();
        
        try {
            $guest->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'id_number' => $request->id_number,
                'id_type' => $request->id_type,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'nationality' => $request->nationality,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country,
                'postal_code' => $request->postal_code,
                'emergency_contact_name' => $request->emergency_contact_name,
                'emergency_contact_phone' => $request->emergency_contact_phone,
                'special_requests' => $request->special_requests,
                'status' => $request->status,
                'notes' => $request->notes
            ]);

            DB::commit();
            
            return redirect()->route('guests.show', $guest)
                ->with('success', 'Guest updated successfully!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update guest: ' . $e->getMessage());
        }
    }

    public function destroy(Guest $guest)
    {
        $this->authorize('delete guest');
        
        try {
            // Prevent deletion if guest has any bookings
            if ($guest->bookings()->exists()) {
                return redirect()->back()
                    ->with('error', 'Cannot delete guest: the guest has related bookings.');
            }
            $guest->delete();
            
            return redirect()->route('guests.index')
                ->with('success', 'Guest deleted successfully!');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete guest: ' . $e->getMessage());
        }
    }
}
