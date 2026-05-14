<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Services\ExpiryStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpiryReportController extends Controller
{
    public function index()
    {
        $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
        return view('inventory.reports.expiry.index', compact('branchId'));
    }

    public function expiringSoon(Request $request)
    {
        $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
            'location_id' => 'nullable|exists:inventory_locations,id'
        ]);

        // Use global setting if no days specified
        $days = $request->days;
        if (!$days) {
            $days = \App\Models\SystemSetting::where('key', 'inventory_global_expiry_warning_days')->value('value') ?? 30;
        }

        $expiryService = new ExpiryStockService();
        $expiringItems = $expiryService->getExpiringStock(
            $days,
            $request->location_id
        );

        return view('inventory.reports.expiry.expiring-soon', [
            'expiringItems' => $expiringItems,
            'days' => $days,
            'locationId' => $request->location_id
        ]);
    }

    public function expired(Request $request)
    {
        $request->validate([
            'location_id' => 'nullable|exists:inventory_locations,id'
        ]);

        $expiryService = new ExpiryStockService();
        $expiredItems = $expiryService->getExpiredStock($request->location_id);

        return view('inventory.reports.expiry.expired', [
            'expiredItems' => $expiredItems,
            'locationId' => $request->location_id
        ]);
    }

    public function stockDetails(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:inventory_items,id',
            'location_id' => 'nullable|exists:inventory_locations,id'
        ]);

        $expiryService = new ExpiryStockService();
        $stockDetails = $expiryService->getAvailableStock(
            $request->item_id,
            $request->location_id
        );

        return response()->json($stockDetails);
    }
}
