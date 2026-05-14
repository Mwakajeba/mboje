<?php

namespace App\Http\Controllers\RentalEventEquipment;

use App\Http\Controllers\Controller;
use App\Models\RentalEventEquipment\EquipmentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Hashids\Hashids;
use Yajra\DataTables\Facades\DataTables;

class EquipmentCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('rental-event-equipment.categories.index');
    }

    /**
     * Get categories data for DataTables.
     */
    public function data(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $query = EquipmentCategory::query()
            ->where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            });

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('created_at_formatted', function ($category) {
                return $category->created_at->format('M d, Y');
            })
            ->addColumn('equipment_count', function ($category) {
                $count = $category->equipment()->count();
                $category->equipment_count = $count; // Add to category object for actions
                return $count;
            })
            ->addColumn('actions', function ($category) {
                return view('rental-event-equipment.categories.partials.actions', compact('category'))->render();
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('rental-event-equipment.categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'categories' => 'required|array|min:1',
            'categories.*.name' => 'required|string|max:255',
            'categories.*.description' => 'nullable|string'
        ]);

        // Check for duplicate names within the submitted categories
        $names = array_column($request->categories, 'name');
        if (count($names) !== count(array_unique($names))) {
            return back()->withErrors(['categories' => 'Duplicate category names are not allowed.'])->withInput();
        }

        // Check for existing categories with the same names
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;
        
        $existingCategories = EquipmentCategory::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->whereIn('name', $names)
            ->pluck('name')
            ->toArray();
            
        if (!empty($existingCategories)) {
            return back()->withErrors(['categories' => 'The following category names already exist: ' . implode(', ', $existingCategories)])->withInput();
        }

        $createdCount = 0;
        foreach ($request->categories as $categoryData) {
            if (!empty(trim($categoryData['name']))) {
                EquipmentCategory::create([
                    'name' => trim($categoryData['name']),
                    'description' => trim($categoryData['description'] ?? ''),
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                ]);
                $createdCount++;
            }
        }

        return redirect()->route('rental-event-equipment.categories.index')
            ->with('success', $createdCount . ' categor' . ($createdCount > 1 ? 'ies' : 'y') . ' created successfully.');
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
        
        $category = EquipmentCategory::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with('equipment')
            ->findOrFail($id);

        return view('rental-event-equipment.categories.show', compact('category'));
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
        
        $category = EquipmentCategory::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->findOrFail($id);

        return view('rental-event-equipment.categories.edit', compact('category'));
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
            'description' => 'nullable|string'
        ]);

        // Check for unique name within company/branch
        $existing = EquipmentCategory::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->where('name', $request->name)
            ->where('id', '!=', $id)
            ->exists();
            
        if ($existing) {
            return back()->withErrors(['name' => 'The category name already exists.'])->withInput();
        }

        $category = EquipmentCategory::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->findOrFail($id);

        $category->update($request->only(['name', 'description']));

        return redirect()->route('rental-event-equipment.categories.index')
            ->with('success', 'Category updated successfully.');
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
        
        $category = EquipmentCategory::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->findOrFail($id);

        // Check if category has any equipment assigned
        if ($category->equipment()->count() > 0) {
            return redirect()->route('rental-event-equipment.categories.index')
                ->with('error', 'Cannot delete category "' . $category->name . '" because it has equipment assigned to it. Please remove all equipment assignments first.');
        }

        $category->delete();

        return redirect()->route('rental-event-equipment.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    /**
     * Check if a category name already exists.
     */
    public function checkName(Request $request)
    {
        $name = $request->query('name');
        $excludeId = $request->query('exclude_id');

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;
        
        $query = EquipmentCategory::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->where('name', $name);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $exists = $query->exists();

        return response()->json(['exists' => $exists]);
    }
}
