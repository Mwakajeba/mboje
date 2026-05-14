<?php

namespace App\Http\Controllers\RentalEventEquipment;

use App\Http\Controllers\Controller;
use App\Models\RentalEventEquipment\Equipment;
use App\Models\RentalEventEquipment\EquipmentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Hashids\Hashids;
use Yajra\DataTables\Facades\DataTables;

class EquipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        // Get filter options
        $categories = EquipmentCategory::where('company_id', $companyId)
            ->where(function ($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
            })
            ->orderBy('name')
            ->get();

        // Get selected filter values for form repopulation
        $selectedCategory = $request->get('category_id');
        $selectedStatus = $request->get('status');

        return view('rental-event-equipment.equipment.index', compact(
            'categories',
            'selectedCategory',
            'selectedStatus'
        ));
    }

    /**
     * Get equipment data for DataTables.
     */
    public function data(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $query = Equipment::with(['category'])
            ->where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            });

        // Apply filters if provided
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && !empty($request->search['value'])) {
                    $searchValue = $request->search['value'];

                    $query->where(function ($q) use ($searchValue) {
                        $q->where('name', 'LIKE', '%' . $searchValue . '%')
                          ->orWhere('equipment_code', 'LIKE', '%' . $searchValue . '%')
                          ->orWhere('description', 'LIKE', '%' . $searchValue . '%');
                    });
                }
            })
            ->addIndexColumn()
            ->addColumn('category_name', function ($equipment) {
                return $equipment->category->name ?? 'N/A';
            })
            ->addColumn('status_badge', function ($equipment) {
                $status = $equipment->status ?? 'available';
                $badgeClass = match($status) {
                    'available' => 'success',
                    'reserved' => 'warning',
                    'on_rent' => 'info',
                    'in_event_use' => 'primary',
                    'under_repair' => 'danger',
                    'lost' => 'dark',
                    default => 'secondary'
                };
                return '<span class="badge bg-' . $badgeClass . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
            })
            ->addColumn('status_breakdown', function ($equipment) use ($companyId, $branchId) {
                // Calculate quantities by status for this equipment
                $owned = $equipment->quantity_owned ?? 0;
                $available = $equipment->quantity_available ?? 0;
                
                // Calculate on_rent quantity from dispatched items
                $onRentQty = \App\Models\RentalEventEquipment\RentalDispatchItem::whereHas('dispatch', function($q) use ($companyId, $branchId) {
                    $q->where('company_id', $companyId)
                      ->where(function($query) use ($branchId) {
                          $query->where('branch_id', $branchId)
                                ->orWhereNull('branch_id');
                      })
                      ->where('status', 'dispatched');
                })
                ->where('equipment_id', $equipment->id)
                ->sum('quantity');
                
                // Calculate reserved quantity from active contracts
                $totalContractQty = \App\Models\RentalEventEquipment\RentalContractItem::whereHas('contract', function($q) use ($companyId, $branchId) {
                    $q->where('company_id', $companyId)
                      ->where(function($query) use ($branchId) {
                          $query->where('branch_id', $branchId)
                                ->orWhereNull('branch_id');
                      })
                      ->where('status', 'active');
                })
                ->where('equipment_id', $equipment->id)
                ->sum('quantity');
                
                // Reserved = contract items minus dispatched items
                $reservedQty = max(0, $totalContractQty - $onRentQty);
                
                // Calculate under_repair quantity from return items marked as damaged
                $underRepairQty = \App\Models\RentalEventEquipment\RentalReturnItem::whereHas('return', function($q) use ($companyId, $branchId) {
                    $q->where('company_id', $companyId)
                      ->where(function($query) use ($branchId) {
                          $query->where('branch_id', $branchId)
                                ->orWhereNull('branch_id');
                      })
                      ->where('status', 'completed');
                })
                ->where('equipment_id', $equipment->id)
                ->where('condition', 'damaged')
                ->sum('quantity_returned');
                
                // Calculate lost quantity
                $lostQty = 0;
                if ($equipment->status === 'lost') {
                    $lostQty = max(0, $owned - $available - $reservedQty - $onRentQty - $underRepairQty);
                }
                
                // Calculate in_event_use
                $inEventUseQty = 0;
                if ($equipment->status === 'in_event_use') {
                    $inEventUseQty = max(0, $owned - $available - $reservedQty - $onRentQty - $underRepairQty - $lostQty);
                }
                
                // Calculate other/unaccounted quantity
                $accountedQty = $available + $reservedQty + $onRentQty + $underRepairQty + $lostQty + $inEventUseQty;
                $otherQty = max(0, $owned - $accountedQty);
                
                // Build status breakdown HTML
                $breakdown = '<div class="status-breakdown">';
                $breakdown .= '<div class="row g-2">';
                
                // Available
                if ($available > 0) {
                    $breakdown .= '<div class="col-12 mb-1"><span class="badge bg-success">Available: ' . $available . '</span></div>';
                }
                
                // Reserved
                if ($reservedQty > 0) {
                    $breakdown .= '<div class="col-12 mb-1"><span class="badge bg-warning">Reserved: ' . $reservedQty . '</span></div>';
                }
                
                // On Rent
                if ($onRentQty > 0) {
                    $breakdown .= '<div class="col-12 mb-1"><span class="badge bg-info">On Rent: ' . $onRentQty . '</span></div>';
                }
                
                // In Event Use
                if ($inEventUseQty > 0) {
                    $breakdown .= '<div class="col-12 mb-1"><span class="badge bg-primary">In Event Use: ' . $inEventUseQty . '</span></div>';
                }
                
                // Under Repair
                if ($underRepairQty > 0) {
                    $breakdown .= '<div class="col-12 mb-1"><span class="badge bg-danger">Under Repair: ' . $underRepairQty . '</span></div>';
                }
                
                // Lost
                if ($lostQty > 0) {
                    $breakdown .= '<div class="col-12 mb-1"><span class="badge bg-dark">Lost: ' . $lostQty . '</span></div>';
                }
                
                // Other/Unknown
                if ($otherQty > 0 && $otherQty < $owned) {
                    $breakdown .= '<div class="col-12 mb-1"><span class="badge bg-secondary">Other: ' . $otherQty . '</span></div>';
                }
                
                $breakdown .= '</div>';
                $breakdown .= '<div class="mt-2"><small class="text-muted fw-bold">Total Owned: ' . $owned . '</small></div>';
                $breakdown .= '</div>';
                
                return $breakdown;
            })
            ->addColumn('cost_info', function ($equipment) {
                $info = '<div><div class="fw-bold">Replacement: ' . number_format($equipment->replacement_cost, 2) . '</div>';
                if ($equipment->rental_rate) {
                    $info .= '<small class="text-muted">Rental: ' . number_format($equipment->rental_rate, 2) . '</small></div>';
                } else {
                    $info .= '<small class="text-muted">No rental rate</small></div>';
                }
                return $info;
            })
            ->addColumn('actions', function ($equipment) {
                return view('rental-event-equipment.equipment.partials.actions', compact('equipment'))->render();
            })
            ->rawColumns(['status_badge', 'status_breakdown', 'cost_info', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $categories = EquipmentCategory::where('company_id', $companyId)
            ->where(function ($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('rental-event-equipment.equipment.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:equipment_categories,id',
            'description' => 'nullable|string',
            'quantity_owned' => 'required|integer|min:0',
            'replacement_cost' => 'required|numeric|min:0',
            'rental_rate' => 'nullable|numeric|min:0',
            'status' => 'required|in:available,reserved,on_rent,in_event_use,under_repair,lost',
            'location' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        // Validate category belongs to company/branch
        $category = EquipmentCategory::where('id', $request->category_id)
            ->where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->first();

        if (!$category) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['category_id' => 'The selected category is invalid.']);
        }

        // Generate equipment code if not provided
        $equipmentCode = $request->equipment_code;
        if (empty($equipmentCode)) {
            $equipmentCode = 'EQ-' . strtoupper(substr(md5(time() . $request->name), 0, 8));
        }

        // Check if equipment code is unique
        $existing = Equipment::where('equipment_code', $equipmentCode)->exists();
        if ($existing) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['equipment_code' => 'The equipment code already exists.']);
        }

        $equipment = Equipment::create([
            'equipment_code' => $equipmentCode,
            'name' => $request->name,
            'category_id' => $request->category_id,
            'description' => $request->description,
            'quantity_owned' => $request->quantity_owned,
            'quantity_available' => $request->quantity_owned, // Initially all are available
            'replacement_cost' => $request->replacement_cost,
            'rental_rate' => $request->rental_rate,
            'status' => $request->status,
            'location' => $request->location,
            'serial_number' => $request->serial_number,
            'purchase_date' => $request->purchase_date,
            'manufacturer' => $request->manufacturer,
            'model' => $request->model,
            'notes' => $request->notes,
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('rental-event-equipment.equipment.index')
            ->with('success', 'Equipment created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $encodedId)
    {
        $hashids = new Hashids();
        $id = $hashids->decode($encodedId)[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $equipment = Equipment::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with(['category', 'creator', 'updater'])
            ->findOrFail($id);

        return view('rental-event-equipment.equipment.show', compact('equipment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $encodedId)
    {
        $hashids = new Hashids();
        $id = $hashids->decode($encodedId)[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $equipment = Equipment::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->findOrFail($id);

        $categories = EquipmentCategory::where('company_id', $companyId)
            ->where(function ($query) use ($branchId) {
                $query->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('rental-event-equipment.equipment.edit', compact('equipment', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $encodedId)
    {
        $hashids = new Hashids();
        $id = $hashids->decode($encodedId)[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:equipment_categories,id',
            'description' => 'nullable|string',
            'quantity_owned' => 'required|integer|min:0',
            'replacement_cost' => 'required|numeric|min:0',
            'rental_rate' => 'nullable|numeric|min:0',
            'status' => 'required|in:available,reserved,on_rent,in_event_use,under_repair,lost',
            'location' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $equipment = Equipment::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->findOrFail($id);

        // Validate category belongs to company/branch
        $category = EquipmentCategory::where('id', $request->category_id)
            ->where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->first();

        if (!$category) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['category_id' => 'The selected category is invalid.']);
        }

        // Check equipment code uniqueness if changed
        if ($request->equipment_code && $request->equipment_code !== $equipment->equipment_code) {
            $existing = Equipment::where('equipment_code', $request->equipment_code)
                ->where('id', '!=', $id)
                ->exists();
            if ($existing) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['equipment_code' => 'The equipment code already exists.']);
            }
        }

        // Update quantity_available if quantity_owned changed
        $quantityDiff = $request->quantity_owned - $equipment->quantity_owned;
        $newQuantityAvailable = max(0, $equipment->quantity_available + $quantityDiff);

        $equipment->update([
            'equipment_code' => $request->equipment_code ?? $equipment->equipment_code,
            'name' => $request->name,
            'category_id' => $request->category_id,
            'description' => $request->description,
            'quantity_owned' => $request->quantity_owned,
            'quantity_available' => $newQuantityAvailable,
            'replacement_cost' => $request->replacement_cost,
            'rental_rate' => $request->rental_rate,
            'status' => $request->status,
            'location' => $request->location,
            'serial_number' => $request->serial_number,
            'purchase_date' => $request->purchase_date,
            'manufacturer' => $request->manufacturer,
            'model' => $request->model,
            'notes' => $request->notes,
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('rental-event-equipment.equipment.index')
            ->with('success', 'Equipment updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $encodedId)
    {
        $hashids = new Hashids();
        $id = $hashids->decode($encodedId)[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $equipment = Equipment::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->findOrFail($id);

        $equipment->delete();

        return redirect()->route('rental-event-equipment.equipment.index')
            ->with('success', 'Equipment deleted successfully.');
    }
}
