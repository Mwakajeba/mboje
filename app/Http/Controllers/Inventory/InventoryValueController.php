<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Item;
use App\Services\InventoryValueService;
use Illuminate\Http\Request;

class InventoryValueController extends Controller
{
    public function __construct(
        private readonly InventoryValueService $valueService
    ) {}

    /**
     * Choose a location to view inventory value detail.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Item::class);

        $companyId = (int) auth()->user()->company_id;
        $branchId = session('branch_id') ? (int) session('branch_id') : (auth()->user()->branch_id ? (int) auth()->user()->branch_id : null);

        $locations = $this->valueService->locationsForCompany($companyId, $branchId);

        if ($locations->count() === 1 && ! $request->boolean('pick')) {
            return redirect()->route('inventory.value.show', $locations->first()->id);
        }

        $loginLocationId = session('location_id') ? (int) session('location_id') : null;
        if ($loginLocationId && $locations->contains('id', $loginLocationId) && ! $request->boolean('pick')) {
            return redirect()->route('inventory.value.show', $loginLocationId);
        }

        $summaries = $this->valueService->buildLocationSummaries($locations, $companyId);
        $currency = $this->valueService->functionalCurrency();

        return view('inventory.value.index', compact('summaries', 'currency', 'branchId'));
    }

    /**
     * Items at a location with stock, cost, and selling value.
     */
    public function show(int $locationId)
    {
        $this->authorize('viewAny', Item::class);

        $companyId = (int) auth()->user()->company_id;
        $branchId = session('branch_id') ? (int) session('branch_id') : (auth()->user()->branch_id ? (int) auth()->user()->branch_id : null);

        $detail = $this->valueService->buildLocationDetail($locationId, $companyId);

        if ($branchId && $detail['location']->branch_id && (int) $detail['location']->branch_id !== $branchId) {
            abort(404);
        }

        $currency = $this->valueService->functionalCurrency();

        return view('inventory.value.show', [
            'location' => $detail['location'],
            'rows' => $detail['rows'],
            'totals' => $detail['totals'],
            'currency' => $currency,
        ]);
    }
}
