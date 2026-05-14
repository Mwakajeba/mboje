<?php

namespace App\Http\Controllers;

use App\Models\TransferRequest;
use App\Models\Inventory\Item;
use App\Models\InventoryLocation;
use App\Models\Inventory\Movement;
use App\Models\Inventory\OpeningBalance;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class TransferRequestController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = TransferRequest::with(['item', 'fromLocation', 'toLocation', 'requestedBy', 'approvedBy'])
                ->forCompany(Auth::user()->company_id);

            return DataTables::of($query)
                ->addColumn('status_badge', function ($request) {
                    return $request->status_badge;
                })
                ->addColumn('actions', function ($request) {
                    $actions = '';
                    
                    // View action
                    $actions .= '<a href="' . route('inventory.transfer-requests.show', $request->id) . '" class="btn btn-sm btn-outline-info" title="View">
                        <i class="bx bx-show"></i>
                    </a> ';

                    // Approve/Reject actions (only for pending requests)
                    if ($request->status === 'pending' && Auth::user()->can('approve transfer requests')) {
                        $actions .= '<button type="button" class="btn btn-sm btn-outline-success approve-btn" data-id="' . $request->id . '" title="Approve">
                            <i class="bx bx-check"></i>
                        </button> ';
                        
                        $actions .= '<button type="button" class="btn btn-sm btn-outline-danger reject-btn" data-id="' . $request->id . '" title="Reject">
                            <i class="bx bx-x"></i>
                        </button> ';
                    }

                    // Edit action (only for pending requests by the requester)
                    if ($request->status === 'pending' && $request->requested_by === Auth::id()) {
                        $actions .= '<a href="' . route('inventory.transfer-requests.edit', $request->id) . '" class="btn btn-sm btn-outline-primary" title="Edit">
                            <i class="bx bx-edit"></i>
                        </a> ';
                    }

                    return $actions;
                })
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        }

        return view('transfer-requests.index');
    }

    public function create()
    {
        $user = Auth::user();
        $loginLocationId = session('location_id');
        
        // Get items with stock at current login location
        $items = Item::where('company_id', $user->company_id)
            ->where('is_active', 1)
            ->where('track_stock', 1)
            ->get()
            ->filter(function ($item) use ($loginLocationId) {
                if (!$loginLocationId) return false;
                
                // Check stock levels
                $stockLevel = $item->stockLevels()->where('inventory_location_id', $loginLocationId)->sum('quantity');
                if ($stockLevel > 0) return true;
                
                // Check opening balances
                $openingBalance = OpeningBalance::where('item_id', $item->id)
                    ->where('inventory_location_id', $loginLocationId)
                    ->sum('quantity');
                
                return $openingBalance > 0;
            });

        // Build location-aware available stock map for modal display
        $locationStocks = [];
        foreach ($items as $item) {
            $locationStocks[$item->id] = $this->getAvailableStock($item->id, $loginLocationId);
        }

        // Get all locations for the company (excluding current login location)
        $locations = InventoryLocation::where('company_id', $user->company_id)
            ->where('id', '!=', $loginLocationId)
            ->with('branch')
            ->get();

        // Destination branches within company
        $branches = Branch::where('company_id', $user->company_id)->orderBy('name')->get();

        return view('transfer-requests.create', compact('items', 'locations', 'loginLocationId', 'locationStocks', 'branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'to_location_id' => 'required|exists:inventory_locations,id',
            'item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        $loginLocationId = session('location_id');
        
        if (!$loginLocationId) {
            return back()->withErrors(['error' => 'No location selected. Please select a location first.']);
        }

        // Verify item belongs to company
        $item = Item::where('id', $request->item_id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        // Check available stock at current location
        $availableStock = $this->getAvailableStock($item->id, $loginLocationId);
        
        if ($availableStock < $request->quantity) {
            return back()->withErrors(['quantity' => "Insufficient stock. Available: {$availableStock}"]);
        }

        // Get destination location
        $toLocation = InventoryLocation::where('id', $request->to_location_id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        // Generate reference
        $reference = 'TRQ-' . date('Ymd') . '-' . strtoupper(Str::random(4));

        DB::transaction(function () use ($request, $user, $loginLocationId, $toLocation, $item, $reference) {
            TransferRequest::create([
                'reference' => $reference,
                'company_id' => $user->company_id,
                'branch_id' => $toLocation->branch_id,
                'from_location_id' => $loginLocationId,
                'to_location_id' => $request->to_location_id,
                'item_id' => $request->item_id,
                'quantity' => $request->quantity,
                'unit_cost' => $item->cost_price ?? 0,
                'total_cost' => $request->quantity * ($item->cost_price ?? 0),
                'reason' => $request->reason,
                'notes' => $request->notes,
                'requested_by' => $user->id,
            ]);
        });

        return redirect()->route('inventory.transfer-requests.index')
            ->with('success', 'Transfer request created successfully.');
    }

    public function show(TransferRequest $transferRequest)
    {
        // Ensure user can view this request
        if ($transferRequest->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $transferRequest->load(['item', 'fromLocation', 'toLocation', 'requestedBy', 'approvedBy']);

        return view('transfer-requests.show', compact('transferRequest'));
    }

    public function edit(TransferRequest $transferRequest)
    {
        // Only allow editing if pending and requested by current user
        if ($transferRequest->status !== 'pending' || $transferRequest->requested_by !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $user = Auth::user();
        $loginLocationId = session('location_id');
        
        // Get items with stock at current login location
        $items = Item::where('company_id', $user->company_id)
            ->where('is_active', 1)
            ->where('track_stock', 1)
            ->get()
            ->filter(function ($item) use ($loginLocationId) {
                if (!$loginLocationId) return false;
                
                $stockLevel = $item->stockLevels()->where('inventory_location_id', $loginLocationId)->sum('quantity');
                if ($stockLevel > 0) return true;
                
                $openingBalance = OpeningBalance::where('item_id', $item->id)
                    ->where('inventory_location_id', $loginLocationId)
                    ->sum('quantity');
                
                return $openingBalance > 0;
            });

        // Get all locations for the company (excluding current login location)
        $locations = InventoryLocation::where('company_id', $user->company_id)
            ->where('id', '!=', $loginLocationId)
            ->with('branch')
            ->get();

        return view('transfer-requests.edit', compact('transferRequest', 'items', 'locations', 'loginLocationId'));
    }

    public function update(Request $request, TransferRequest $transferRequest)
    {
        // Only allow updating if pending and requested by current user
        if ($transferRequest->status !== 'pending' || $transferRequest->requested_by !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'to_location_id' => 'required|exists:inventory_locations,id',
            'item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        $loginLocationId = session('location_id');
        
        if (!$loginLocationId) {
            return back()->withErrors(['error' => 'No location selected. Please select a location first.']);
        }

        // Verify item belongs to company
        $item = Item::where('id', $request->item_id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        // Check available stock at current location
        $availableStock = $this->getAvailableStock($item->id, $loginLocationId);
        
        if ($availableStock < $request->quantity) {
            return back()->withErrors(['quantity' => "Insufficient stock. Available: {$availableStock}"]);
        }

        // Get destination location
        $toLocation = InventoryLocation::where('id', $request->to_location_id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        DB::transaction(function () use ($request, $transferRequest, $toLocation, $item) {
            $transferRequest->update([
                'branch_id' => $toLocation->branch_id,
                'to_location_id' => $request->to_location_id,
                'item_id' => $request->item_id,
                'quantity' => $request->quantity,
                'unit_cost' => $item->cost_price ?? 0,
                'total_cost' => $request->quantity * ($item->cost_price ?? 0),
                'reason' => $request->reason,
                'notes' => $request->notes,
            ]);
        });

        return redirect()->route('inventory.transfer-requests.index')
            ->with('success', 'Transfer request updated successfully.');
    }

    public function approve(Request $request, TransferRequest $transferRequest)
    {
        if (!$transferRequest->canBeApproved()) {
            return response()->json(['error' => 'Request cannot be approved.'], 400);
        }

        $request->validate([
            'approval_notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($request, $transferRequest) {
            // Approve the request
            $transferRequest->approve(Auth::id(), $request->approval_notes);

            // Create the actual transfer
            $this->createTransfer($transferRequest);
        });

        return response()->json(['success' => 'Transfer request approved and transfer created successfully.']);
    }

    public function reject(Request $request, TransferRequest $transferRequest)
    {
        if (!$transferRequest->canBeRejected()) {
            return response()->json(['error' => 'Request cannot be rejected.'], 400);
        }

        $request->validate([
            'approval_notes' => 'required|string|max:1000',
        ]);

        $transferRequest->reject(Auth::id(), $request->approval_notes);

        return response()->json(['success' => 'Transfer request rejected successfully.']);
    }

    private function getAvailableStock($itemId, $locationId)
    {
        // Check stock levels
        $stockLevel = DB::table('inventory_stock_levels')
            ->where('item_id', $itemId)
            ->where('inventory_location_id', $locationId)
            ->sum('quantity');

        if ($stockLevel > 0) {
            return $stockLevel;
        }

        // Check opening balances
        $openingBalance = OpeningBalance::where('item_id', $itemId)
            ->where('inventory_location_id', $locationId)
            ->sum('quantity');

        return $openingBalance;
    }

    private function createTransfer(TransferRequest $transferRequest)
    {
        // Generate transfer reference
        $transferReference = 'TRF-' . date('Ymd') . '-' . strtoupper(Str::random(4));

        // Create transfer out movement
        Movement::create([
            'branch_id' => $transferRequest->branch_id,
            'item_id' => $transferRequest->item_id,
            'user_id' => $transferRequest->approved_by,
            'movement_type' => 'transfer_out',
            'quantity' => $transferRequest->quantity,
            'unit_cost' => $transferRequest->unit_cost,
            'total_cost' => $transferRequest->total_cost,
            'reference' => $transferReference,
            'reason' => 'Transfer Request Approved: ' . $transferRequest->reference,
            'notes' => $transferRequest->notes,
            'movement_date' => now(),
            'balance_before' => 0,
            'balance_after' => -$transferRequest->quantity,
        ]);

        // Create transfer in movement
        Movement::create([
            'branch_id' => $transferRequest->branch_id,
            'item_id' => $transferRequest->item_id,
            'user_id' => $transferRequest->approved_by,
            'movement_type' => 'transfer_in',
            'quantity' => $transferRequest->quantity,
            'unit_cost' => $transferRequest->unit_cost,
            'total_cost' => $transferRequest->total_cost,
            'reference' => $transferReference,
            'reason' => 'Transfer Request Approved: ' . $transferRequest->reference,
            'notes' => $transferRequest->notes,
            'movement_date' => now(),
            'balance_before' => 0,
            'balance_after' => $transferRequest->quantity,
        ]);

        // Update stock levels (if using stock levels table)
        // This would need to be implemented based on your stock management system
    }
}