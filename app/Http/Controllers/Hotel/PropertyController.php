<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\Hotel\Property;
use App\Models\Property\Lease;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view properties');
        
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id;

        $query = Property::with(['branch', 'company', 'createdBy'])
            ->where('company_id', $user->company_id)
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });

        // Stats
        $totalProperties = (clone $query)->count();
        $activeProperties = (clone $query)->where('status', 'active')->count();
        $maintenanceProperties = (clone $query)->where('status', 'maintenance')->count();
        $totalValue = (clone $query)->sum('current_value');

        if ($request->ajax()) {
            return DataTables::of($query)
                ->addColumn('property_name', function ($property) {
                    $description = $property->description ? '<br><small class="text-muted" style="font-size: 0.75rem;">' . Str::limit($property->description, 40) . '</small>' : '';
                    return '<div class="d-flex align-items-center">
                        <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-2">
                            <i class="bx bx-building text-primary"></i>
                        </div>
                        <div>
                            <strong class="d-block">' . e($property->name) . '</strong>' . $description . '
                        </div>
                    </div>';
                })
                ->addColumn('type_badge', function ($property) {
                    $typeIcons = [
                        'hotel' => '🏨',
                        'apartment' => '🏢',
                        'office' => '🏛️',
                        'retail' => '🏪',
                        'warehouse' => '🏭',
                        'residential' => '🏠'
                    ];
                    $icon = $typeIcons[$property->type] ?? '🏗️';
                    return '<span class="property-type-badge bg-light text-dark">' . $icon . ' ' . ucfirst($property->type) . '</span>';
                })
                ->addColumn('status_badge', function ($property) {
                    $badges = [
                        'active' => '<span class="badge bg-success-subtle text-success"><i class="bx bx-check-circle me-1"></i>Active</span>',
                        'inactive' => '<span class="badge bg-secondary-subtle text-secondary"><i class="bx bx-pause-circle me-1"></i>Inactive</span>',
                        'maintenance' => '<span class="badge bg-warning-subtle text-warning"><i class="bx bx-wrench me-1"></i>Maintenance</span>',
                        'sold' => '<span class="badge bg-info-subtle text-info"><i class="bx bx-dollar me-1"></i>Sold</span>'
                    ];
                    return $badges[$property->status] ?? '<span class="badge bg-secondary">' . ucfirst($property->status) . '</span>';
                })
                ->addColumn('location', function ($property) {
                    if ($property->city || $property->state) {
                        $location = '<div><i class="bx bx-map-pin text-muted me-1"></i><span>' . e($property->city) . ($property->state ? ', ' . e($property->state) : '') . '</span></div>';
                        if ($property->country) {
                            $location .= '<small class="text-muted">' . e($property->country) . '</small>';
                        }
                        return $location;
                    }
                    return '<span class="text-muted"><i class="bx bx-map text-muted me-1"></i>Not specified</span>';
                })
                ->addColumn('formatted_value', function ($property) {
                    if ($property->current_value) {
                        return '<strong class="text-success">TSh ' . number_format($property->current_value, 0) . '</strong>';
                    }
                    return '<span class="text-muted">Not set</span>';
                })
                ->addColumn('actions', function ($property) {
                    return '<div class="btn-group btn-group-sm" role="group">
                        <a href="' . route('properties.show', $property) . '" class="btn btn-outline-primary" title="View Details" data-bs-toggle="tooltip">
                            <i class="bx bx-show"></i>
                        </a>
                        <a href="' . route('properties.edit', $property) . '" class="btn btn-outline-secondary" title="Edit Property" data-bs-toggle="tooltip">
                            <i class="bx bx-edit"></i>
                        </a>
                        <form method="POST" action="' . route('properties.destroy', $property) . '" style="display: inline;" onsubmit="return confirm(\'Are you sure you want to delete ' . addslashes($property->name) . '? This action cannot be undone.\')">
                            ' . csrf_field() . '
                            ' . method_field('DELETE') . '
                            <button type="submit" class="btn btn-outline-danger" title="Delete Property" data-bs-toggle="tooltip">
                                <i class="bx bx-trash"></i>
                            </button>
                        </form>
                    </div>';
                })
                ->rawColumns(['property_name', 'type_badge', 'status_badge', 'location', 'formatted_value', 'actions'])
                ->order(function ($q) {
                    $q->orderBy('name');
                })
                ->toJson();
        }

        return view('property.properties.index', compact(
            'totalProperties',
            'activeProperties',
            'maintenanceProperties',
            'totalValue'
        ));
    }

    public function create()
    {
        $this->authorize('create property');
        
        return view('property.properties.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create property');
        
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:hotel,apartment,office,retail,warehouse,residential',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'purchase_price' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:active,inactive,maintenance,sold',
            'description' => 'nullable|string|max:1000',
            'purchase_date' => 'nullable|date',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string|in:parking,security,elevator,gym,pool,garden,balcony,terrace'
        ]);

        DB::beginTransaction();
        
        try {
            $amenities = $request->input('amenities', []);
            
            $branch_id = session('branch_id') ?? user()->branch_id ?? 1;

            $property = Property::create([
                'name' => $request->name,
                'type' => $request->type,
                'address' => $request->address ?? '',
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country,
                'postal_code' => $request->postal_code,
                'purchase_price' => $request->purchase_price ?? 0,
                'current_value' => $request->current_value ?? 0,
                'status' => $request->status,
                'description' => $request->description,
                'purchase_date' => $request->purchase_date,
                'contact_person' => $request->contact_person ?? '',
                'contact_phone' => $request->contact_phone ?? '',
                'contact_email' => $request->contact_email ?? '',
                'amenities' => $amenities,
                'branch_id' => $branch_id,
                'company_id' => Auth::user()->company_id,
                'created_by' => Auth::id()
            ]);

            DB::commit();
            
            return redirect()->route('properties.index')
                ->with('success', 'Property created successfully!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create property: ' . $e->getMessage());
        }
    }

    public function show(Property $property)
    {
        $this->authorize('view property details');
        
        $property->load(['branch', 'company', 'createdBy', 'rooms', 'bookings']);
        
        return view('property.properties.show', compact('property'));
    }

    public function edit(Property $property)
    {
        $this->authorize('edit property');
        
        return view('property.properties.edit', compact('property'));
    }

    public function update(Request $request, Property $property)
    {
        $this->authorize('edit property');
        
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:hotel,apartment,office,retail,warehouse,residential',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'purchase_price' => 'nullable|numeric|min:0',
            'current_value' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:active,inactive,maintenance,sold',
            'description' => 'nullable|string|max:1000',
            'purchase_date' => 'nullable|date',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string|in:parking,security,elevator,gym,pool,garden,balcony,terrace'
        ]);

        DB::beginTransaction();
        
        try {
            $amenities = $request->input('amenities', []);
            
            $property->update([
                'name' => $request->name,
                'type' => $request->type,
                'address' => $request->address ?? '',
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country,
                'postal_code' => $request->postal_code,
                'purchase_price' => $request->purchase_price ?? 0,
                'current_value' => $request->current_value ?? 0,
                'status' => $request->status,
                'description' => $request->description,
                'purchase_date' => $request->purchase_date,
                'contact_person' => $request->contact_person,
                'contact_phone' => $request->contact_phone,
                'contact_email' => $request->contact_email,
                'amenities' => $amenities
            ]);

            DB::commit();
            
            return redirect()->route('properties.show', $property)
                ->with('success', 'Property updated successfully!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update property: ' . $e->getMessage());
        }
    }

    public function destroy(Property $property)
    {
        $this->authorize('delete property');
        
        try {
            // Prevent deletion if property has rooms, bookings, or leases
            $hasRooms = $property->rooms()->exists();
            $hasBookings = method_exists($property, 'bookings') ? $property->bookings()->exists() : false;
            $hasLeases = Lease::where('property_id', $property->id)->exists();
            if ($hasRooms || $hasBookings || $hasLeases) {
                return redirect()->back()
                    ->with('error', 'Cannot delete property: it has related ' . ($hasRooms ? 'rooms' : ($hasBookings ? 'bookings' : 'leases')) . '.');
            }
            $property->delete();
            
            return redirect()->route('properties.index')
                ->with('success', 'Property deleted successfully!');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete property: ' . $e->getMessage());
        }
    }
}
