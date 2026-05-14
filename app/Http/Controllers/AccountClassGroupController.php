<?php

namespace App\Http\Controllers;

use App\Models\AccountClass;
use App\Models\MainGroup;
use App\Models\AccountClassGroup;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Vinkla\Hashids\Facades\Hashids;

class AccountClassGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $user = auth()->user();
        $accountClassGroups = AccountClassGroup::with(['accountClass', 'mainGroup'])
            ->withCount('chartAccounts')
            ->where('company_id', $user->company_id)
            ->get();
        return view('account-class-groups.index', compact('accountClassGroups'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $user = auth()->user();
        $mainGroups = MainGroup::where('company_id', $user->company_id)
            ->where('status', true)
            ->with('accountClass')
            ->get();
        return view('account-class-groups.create', compact('mainGroups'));
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'main_group_id' => 'required|exists:main_groups,id',
            'group_code' => 'nullable|string|max:255|unique:account_class_groups,group_code',
            'name' => 'required|string|max:255|unique:account_class_groups,name',
        ]);

        $user = auth()->user();
        
        // Get the class_id from the selected main group
        $mainGroup = MainGroup::findOrFail($request->main_group_id);

        AccountClassGroup::create([
            'class_id' => $mainGroup->class_id,
            'main_group_id' => $request->main_group_id,
            'group_code' => $request->group_code,
            'name' => $request->name,
            'company_id' => $user->company_id,
        ]);

        return redirect()->route('accounting.account-class-groups.index')
            ->with('success', 'Account Class Group created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($encodedId)
    {
        // Decode the ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.account-class-groups.index')->withErrors(['Account Class Group not found.']);
        }

        $accountClassGroup = AccountClassGroup::findOrFail($decoded[0]);
        $user = auth()->user();

        // Ensure the account class group belongs to the current user's company
        if ($accountClassGroup->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this account class group.');
        }

        $accountClassGroup->load(['accountClass', 'mainGroup']);
        return view('account-class-groups.show', compact('accountClassGroup'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($encodedId)
    {
        // Decode the ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.account-class-groups.index')->withErrors(['Account Class Group not found.']);
        }

        $accountClassGroup = AccountClassGroup::findOrFail($decoded[0]);
        $user = auth()->user();

        // Ensure the account class group belongs to the current user's company
        if ($accountClassGroup->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this account class group.');
        }

        $mainGroups = MainGroup::where('company_id', $user->company_id)
            ->where('status', true)
            ->with('accountClass')
            ->get();
        return view('account-class-groups.edit', compact('accountClassGroup', 'mainGroups'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $encodedId)
    {
        // Decode account class group ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.account-class-groups.index')->withErrors(['Account Class Group not found.']);
        }

        $accountClassGroup = AccountClassGroup::findOrFail($decoded[0]);

        $request->validate([
            'main_group_id' => 'required|exists:main_groups,id',
            'group_code' => 'nullable|string|max:255|unique:account_class_groups,group_code,' . $accountClassGroup->id,
            'name' => 'required|string|max:255|unique:account_class_groups,name,' . $accountClassGroup->id,
        ]);

        // Get the class_id from the selected main group
        $mainGroup = MainGroup::findOrFail($request->main_group_id);

        $accountClassGroup->update([
            'class_id' => $mainGroup->class_id,
            'main_group_id' => $request->main_group_id,
            'group_code' => $request->group_code,
            'name' => $request->name,
        ]);

        return redirect()->route('accounting.account-class-groups.index')
            ->with('success', 'Account Class Group updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($encodedId)
    {
        // Decode the encoded ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.account-class-groups.index')->withErrors(['Account Class Group not found.']);
        }

        $accountClassGroup = AccountClassGroup::withCount('chartAccounts')->findOrFail($decoded[0]);
        $user = auth()->user();

        // Ensure the account class group belongs to the current user's company
        if ($accountClassGroup->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this account class group.');
        }

        // Check if account class group is being used
        if ($accountClassGroup->isInUse()) {
            return redirect()->route('accounting.account-class-groups.index')
                ->withErrors(['error' => 'Cannot delete Account Class Group "' . $accountClassGroup->name . '" because it is being used by ' . $accountClassGroup->chart_accounts_count . ' Chart Account(s).']);
        }

        $accountClassGroup->delete();

        return redirect()->route('accounting.account-class-groups.index')
            ->with('success', 'Account Class Group deleted successfully.');
    }
}
