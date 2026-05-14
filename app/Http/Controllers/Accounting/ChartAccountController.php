<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AccountClassGroup;
use App\Models\AccountClass;
use App\Models\CashFlowCategory;
use App\Models\EquityCategory;

class ChartAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $accountClassGroups = AccountClassGroup::with('accountClass')->get();
        $accountClasses = AccountClass::all();
        $cashFlowCategories = CashFlowCategory::all();
        $equityCategories = EquityCategory::all();
        
        // Build class ranges mapping
        $classRanges = [];
        foreach ($accountClasses as $class) {
            $classRanges[$class->id] = [
                'from' => $class->range_from,
                'to' => $class->range_to
            ];
        }
        
        return view('chart-accounts.create', compact('accountClassGroups', 'accountClasses', 'cashFlowCategories', 'equityCategories', 'classRanges'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $chartAccount = null;
        if ($id) {
            $chartAccount = \App\Models\ChartAccount::findOrFail($id);
        }
        $accountClassGroups = AccountClassGroup::with('accountClass')->get();
        $accountClasses = AccountClass::all();
        $cashFlowCategories = CashFlowCategory::all();
        $equityCategories = EquityCategory::all();
        
        // Build class ranges mapping
        $classRanges = [];
        foreach ($accountClasses as $class) {
            $classRanges[$class->id] = [
                'from' => $class->range_from,
                'to' => $class->range_to
            ];
        }
        
        return view('chart-accounts.edit', compact('chartAccount', 'accountClassGroups', 'accountClasses', 'cashFlowCategories', 'equityCategories', 'classRanges'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
