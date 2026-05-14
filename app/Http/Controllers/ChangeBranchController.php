<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Branch;
use App\Models\InventoryLocation;
use Illuminate\Support\Facades\DB;

class ChangeBranchController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        // Show ONLY branches assigned to the user via pivot
        $assignedBranchIds = method_exists($user, 'branches') ? $user->branches()->pluck('branches.id')->toArray() : [];
        
        // Get branches assigned to user, regardless of user's company_id (since it might be null)
        $branches = Branch::whereIn('id', $assignedBranchIds)->get();

        // Provide only locations assigned to the user; the blade will filter by selected branch
        $locations = $user->locations()->select('inventory_locations.*')->get();

        \Log::info('User branches', [
            'user_id' => $user->id, 
            'user_company_id' => $user->company_id,
            'assigned_branch_ids' => $assignedBranchIds,
            'branches_count' => $branches->count(),
            'branches' => $branches->pluck('name', 'id')->toArray()
        ]);
        return view('auth.change-branch', compact('branches', 'locations'));
    }

    public function change(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'branch_id' => 'nullable|integer',
            'location_id' => 'nullable|integer',
        ]);

        // Determine allowed branches for this user via pivot
        $assignedBranchIds = method_exists($user, 'branches')
            ? $user->branches()->pluck('branches.id')->toArray()
            : [];

        // Select branch: prefer requested if assigned; otherwise first assigned
        $requestedBranchId = (int) ($request->branch_id ?? 0);
        if ($requestedBranchId && in_array($requestedBranchId, $assignedBranchIds, true)) {
            $selectedBranchId = $requestedBranchId;
        } else {
            $selectedBranchId = (int) (reset($assignedBranchIds) ?: 0);
        }

        // Guard: if no assigned branch found, deny
        if (!$selectedBranchId) {
            return back()->with('error', 'No branch assigned to your account. Contact administrator.');
        }

        // Store selected branch in session for filtering (do not persist on users table)
        session(['branch_id' => $selectedBranchId]);

        // If a location is provided, ensure it is ASSIGNED to the user and matches the selected branch
        if ($request->filled('location_id')) {
            $locationId = (int) $request->location_id;
            $validLocationId = $user->locations()
                ->where('inventory_locations.branch_id', $selectedBranchId)
                ->where('inventory_locations.id', $locationId)
                ->value('inventory_locations.id');

            if ($validLocationId) {
                session(['location_id' => $validLocationId]);
            } else {
                // Fallback: pick any location in the selected branch
                $fallbackLocationId = \App\Models\InventoryLocation::where('branch_id', $selectedBranchId)
                    ->orderBy('name')
                    ->value('id');
                if ($fallbackLocationId) {
                    session(['location_id' => $fallbackLocationId]);
                } else {
                    // Clear any stale session location if truly none exist
                    session()->forget('location_id');
                }
            }
        } else {
            // No location selected: try assign a default location for the selected branch
            $defaultLocationId = $user->locations()
                ->where('inventory_locations.branch_id', $selectedBranchId)
                ->orderBy('inventory_locations.name')
                ->value('inventory_locations.id');

            if ($defaultLocationId) {
                session(['location_id' => $defaultLocationId]);
            } else {
                // Fallback: pick any location in the selected branch
                $fallbackLocationId = \App\Models\InventoryLocation::where('branch_id', $selectedBranchId)
                    ->orderBy('name')
                    ->value('id');
                if ($fallbackLocationId) {
                    session(['location_id' => $fallbackLocationId]);
                } else {
                    session()->forget('location_id');
                }
            }
        }
        // Handle AJAX vs normal request and ensure redirect target exists
        $target = \Route::has('dashboard') ? route('dashboard') : url('/');
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Branch changed successfully!',
                'redirect_url' => $target,
            ]);
        }
        return redirect()->intended($target)->with('success', 'Branch changed successfully!');
    }

    public function locations(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
        ]);
        $branchId = (int) $request->query('branch_id');

        $locations = $user->locations()
            ->where('inventory_locations.branch_id', $branchId)
            ->select('inventory_locations.id', 'inventory_locations.name', 'inventory_locations.branch_id')
            ->orderBy('inventory_locations.name')
            ->get();

        return response()->json($locations);
    }
}
