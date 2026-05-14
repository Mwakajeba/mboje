<?php

namespace App\Http\Controllers;

use App\Models\MainGroup;
use App\Models\AccountClass;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Vinkla\Hashids\Facades\Hashids;

class MainGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $user = auth()->user();
        $mainGroups = MainGroup::with('accountClass')
            ->withCount('accountClassGroups')
            ->where('company_id', $user->company_id)
            ->get();
        return view('main-groups.index', compact('mainGroups'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $accountClasses = AccountClass::all(); // Account classes are global
        return view('main-groups.create', compact('accountClasses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'class_id' => 'required|exists:account_class,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);

        $user = auth()->user();

        MainGroup::create([
            'class_id' => $request->class_id,
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->has('status') ? $request->status : true,
            'company_id' => $user->company_id,
        ]);

        return redirect()->route('accounting.main-groups.index')
            ->with('success', 'Main Group created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($encodedId)
    {
        // Decode the ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.main-groups.index')->withErrors(['Main Group not found.']);
        }

        $mainGroup = MainGroup::findOrFail($decoded[0]);
        $user = auth()->user();

        // Ensure the main group belongs to the current user's company
        if ($mainGroup->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this main group.');
        }

        $mainGroup->load('accountClass');
        return view('main-groups.show', compact('mainGroup'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($encodedId)
    {
        // Decode the ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.main-groups.index')->withErrors(['Main Group not found.']);
        }

        $mainGroup = MainGroup::findOrFail($decoded[0]);
        $user = auth()->user();

        // Ensure the main group belongs to the current user's company
        if ($mainGroup->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this main group.');
        }

        $accountClasses = AccountClass::all(); // Account classes are global
        return view('main-groups.edit', compact('mainGroup', 'accountClasses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $encodedId)
    {
        // Decode main group ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.main-groups.index')->withErrors(['Main Group not found.']);
        }

        $mainGroup = MainGroup::findOrFail($decoded[0]);

        $request->validate([
            'class_id' => 'required|exists:account_class,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);

        $mainGroup->update([
            'class_id' => $request->class_id,
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->has('status') ? $request->status : true,
        ]);

        return redirect()->route('accounting.main-groups.index')
            ->with('success', 'Main Group updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($encodedId)
    {
        // Decode the encoded ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.main-groups.index')->withErrors(['Main Group not found.']);
        }

        $mainGroup = MainGroup::withCount('accountClassGroups')->findOrFail($decoded[0]);
        $user = auth()->user();

        // Ensure the main group belongs to the current user's company
        if ($mainGroup->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this main group.');
        }

        // Check if main group is being used
        if ($mainGroup->isInUse()) {
            return redirect()->route('accounting.main-groups.index')
                ->withErrors(['error' => 'Cannot delete Main Group "' . $mainGroup->name . '" because it is being used by ' . $mainGroup->account_class_groups_count . ' Account Class Group(s).']);
        }

        $mainGroup->delete();

        return redirect()->route('accounting.main-groups.index')
            ->with('success', 'Main Group deleted successfully.');
    }
}
