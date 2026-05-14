<?php

namespace App\Http\Controllers\Accounting\PettyCash;

use App\Http\Controllers\Controller;
use App\Models\PettyCash\PettyCashExpenseCategory;
use App\Models\ChartAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;

class PettyCashExpenseCategoryController extends Controller
{
    /**
     * Display a listing of expense categories
     */
    public function index()
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $categories = PettyCashExpenseCategory::forCompany($companyId)
            ->with('expenseAccount')
            ->orderBy('name')
            ->get();
        
        return view('accounting.petty-cash.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new category
     */
    public function create()
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        // Get expense accounts
        $expenseAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($companyId) {
            $q->where('company_id', $companyId)
              ->whereHas('accountClass', function($q2) {
                  $q2->where('name', 'LIKE', '%expense%');
              });
        })->orderBy('account_code')->get();
        
        return view('accounting.petty-cash.categories.create', compact('expenseAccounts'));
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'expense_account_id' => 'required|exists:chart_accounts,id',
            'description' => 'nullable|string',
        ]);
        
        try {
            $validated['company_id'] = Auth::user()->company_id;
            $validated['is_active'] = true;
            
            PettyCashExpenseCategory::create($validated);
            
            return redirect()->route('accounting.petty-cash.categories.index')
                ->with('success', 'Expense category created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create category: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified category
     */
    public function edit($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $category = PettyCashExpenseCategory::findOrFail($id);
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $expenseAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($companyId) {
            $q->where('company_id', $companyId)
              ->whereHas('accountClass', function($q2) {
                  $q2->where('name', 'LIKE', '%expense%');
              });
        })->orderBy('account_code')->get();
        
        return view('accounting.petty-cash.categories.edit', compact('category', 'expenseAccounts'));
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $category = PettyCashExpenseCategory::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'expense_account_id' => 'required|exists:chart_accounts,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        
        try {
            $category->update($validated);
            
            return redirect()->route('accounting.petty-cash.categories.index')
                ->with('success', 'Expense category updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update category: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified category
     */
    public function destroy($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $category = PettyCashExpenseCategory::findOrFail($id);
        
        if ($category->transactions()->count() > 0) {
            return back()->with('error', 'Cannot delete category with existing transactions.');
        }
        
        try {
            $category->delete();
            return redirect()->route('accounting.petty-cash.categories.index')
                ->with('success', 'Expense category deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete category: ' . $e->getMessage());
        }
    }
}

