<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id;

        $suppliers = Supplier::query()
            ->where('company_id', $user->company_id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('purchases.index', compact('suppliers'));
    }
}
