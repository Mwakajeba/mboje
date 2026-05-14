<?php

namespace App\Http\Controllers\RentalEventEquipment;

use App\Http\Controllers\Controller;
use App\Models\RentalEventEquipment\RentalDispatch;
use App\Models\RentalEventEquipment\RentalDispatchItem;
use App\Models\RentalEventEquipment\RentalContract;
use App\Models\RentalEventEquipment\Equipment;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class RentalDispatchController extends Controller
{
    public function index()
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        // Get dispatches for alerts
        $dispatches = RentalDispatch::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })
            ->where('status', 'dispatched')
            ->whereNotNull('expected_return_date')
            ->with(['customer', 'contract'])
            ->get();

        $today = now();
        $overdueDispatches = [];
        $dueSoonDispatches = [];

        foreach ($dispatches as $dispatch) {
            // Calculate integer days remaining
            $daysUntilReturn = $today->diffInDays($dispatch->expected_return_date, false);

            if ($daysUntilReturn < 0) {
                // Overdue
                $overdueDispatches[] = [
                    'dispatch' => $dispatch,
                    'days_overdue' => abs($daysUntilReturn)
                ];
            } elseif ($daysUntilReturn <= 3 && $daysUntilReturn >= 0) {
                // Due soon (within 3 days)
                $dueSoonDispatches[] = [
                    'dispatch' => $dispatch,
                    'days_remaining' => (int)$daysUntilReturn
                ];
            }
        }

        return view('rental-event-equipment.rental-dispatches.index', compact('overdueDispatches', 'dueSoonDispatches'));
    }

    public function data(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $query = RentalDispatch::with(['customer', 'contract'])
            ->where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            });

        $today = now();

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('customer_name', function ($dispatch) {
                return $dispatch->customer->name ?? 'N/A';
            })
            ->addColumn('contract_number', function ($dispatch) {
                return $dispatch->contract->contract_number ?? 'N/A';
            })
            ->addColumn('dispatch_date_formatted', function ($dispatch) {
                return $dispatch->dispatch_date->format('M d, Y');
            })
            ->addColumn('expected_return_date_formatted', function ($dispatch) use ($today) {
                if (!$dispatch->expected_return_date) {
                    return '<span class="text-muted">Not set</span>';
                }

                $daysUntilReturn = (int)$today->diffInDays($dispatch->expected_return_date, false);
                $formattedDate = $dispatch->expected_return_date->format('M d, Y');

                if ($daysUntilReturn < 0) {
                    // Overdue
                    $daysOverdue = abs($daysUntilReturn);
                    return '<span class="text-danger fw-bold">' . $formattedDate . '</span><br><small class="text-danger">' . $daysOverdue . ' day(s) overdue</small>';
                } elseif ($daysUntilReturn <= 3) {
                    // Due soon
                    return '<span class="text-warning fw-bold">' . $formattedDate . '</span><br><small class="text-warning">' . $daysUntilReturn . ' day(s) remaining</small>';
                } else {
                    // Normal
                    return '<span>' . $formattedDate . '</span><br><small class="text-muted">' . $daysUntilReturn . ' day(s) remaining</small>';
                }
            })
            ->addColumn('status_badge', function ($dispatch) {
                $badgeClass = match ($dispatch->status) {
                    'draft' => 'secondary',
                    'dispatched' => 'success',
                    'returned' => 'info',
                    'cancelled' => 'danger',
                    default => 'secondary'
                };
                return '<span class="badge bg-' . $badgeClass . '">' . ucfirst($dispatch->status) . '</span>';
            })
            ->addColumn('actions', function ($dispatch) {
                return view('rental-event-equipment.rental-dispatches.partials.actions', compact('dispatch'))->render();
            })
            ->rawColumns(['status_badge', 'actions', 'expected_return_date_formatted'])
            ->make(true);
    }

    public function create(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $contracts = RentalContract::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })
            ->where('status', 'active') // Only show approved contracts
            ->with(['customer', 'items.equipment'])
            ->orderBy('contract_number', 'desc')
            ->get();

        $selectedContractId = $request->get('contract_id');
        $selectedContract = null;

        // If contract_id is provided, fetch the contract to get event details
        if ($selectedContractId) {
            $selectedContract = RentalContract::where('company_id', $companyId)
                ->where(function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                        ->orWhereNull('branch_id');
                })
                ->where('status', 'active')
                ->find($selectedContractId);
        }

        return view('rental-event-equipment.rental-dispatches.create', compact('contracts', 'selectedContractId', 'selectedContract'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'contract_id' => 'required|exists:rental_contracts,id',
                'dispatch_date' => 'required|date',
                'expected_return_date' => 'nullable|date|after_or_equal:dispatch_date',
                'event_location' => 'nullable|string|max:255',
                'event_date' => 'nullable|date',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.equipment_id' => 'required|exists:equipment,id',
                'items.*.quantity' => 'required|integer|min:1',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $contract = RentalContract::with('items.equipment')->findOrFail($request->contract_id);

        // Validate items belong to contract and quantities are available
        foreach ($request->items as $item) {
            $contractItem = $contract->items->firstWhere('equipment_id', $item['equipment_id']);
            if (!$contractItem) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected equipment does not belong to this contract.',
                        'errors' => ['items' => ['Selected equipment does not belong to this contract.']]
                    ], 422);
                }
                return back()->withErrors(['items' => 'Selected equipment does not belong to this contract.'])->withInput();
            }
            if ($item['quantity'] > $contractItem->quantity) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Quantity cannot exceed contract quantity.',
                        'errors' => ['items' => ['Quantity cannot exceed contract quantity.']]
                    ], 422);
                }
                return back()->withErrors(['items' => 'Quantity cannot exceed contract quantity.'])->withInput();
            }
            $equipment = Equipment::find($item['equipment_id']);

            // Check if equipment status allows dispatch
            $blockedStatuses = ['lost', 'disposed', 'cancelled'];
            if (in_array($equipment->status, $blockedStatuses)) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => "Equipment '{$equipment->name}' cannot be dispatched. Current status: " . ucfirst(str_replace('_', ' ', $equipment->status)),
                        'errors' => ['items' => ["Equipment '{$equipment->name}' cannot be dispatched. Current status: " . ucfirst(str_replace('_', ' ', $equipment->status))]]
                    ], 422);
                }
                return back()->withErrors(['items' => "Equipment '{$equipment->name}' cannot be dispatched. Current status: " . ucfirst(str_replace('_', ' ', $equipment->status))])->withInput();
            }
        }

        $dispatchNumber = 'DISP-' . date('Y') . '-' . str_pad((RentalDispatch::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT);

        DB::beginTransaction();
        try {
            $dispatch = RentalDispatch::create([
                'dispatch_number' => $dispatchNumber,
                'contract_id' => $request->contract_id,
                'customer_id' => $contract->customer_id,
                'dispatch_date' => $request->dispatch_date,
                'expected_return_date' => $request->expected_return_date,
                'event_location' => $request->event_location,
                'event_date' => $request->event_date,
                'notes' => $request->notes,
                'status' => 'draft',
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'created_by' => Auth::id(),
            ]);

            foreach ($request->items as $item) {
                RentalDispatchItem::create([
                    'dispatch_id' => $dispatch->id,
                    'equipment_id' => $item['equipment_id'],
                    'quantity' => $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Rental dispatch created successfully.',
                    'redirect' => route('rental-event-equipment.rental-dispatches.index')
                ]);
            }

            return redirect()->route('rental-event-equipment.rental-dispatches.index')
                ->with('success', 'Rental dispatch created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create dispatch: ' . $e->getMessage()
                ], 422);
            }

            return back()->withInput()->with('error', 'Failed to create dispatch: ' . $e->getMessage());
        }
    }

    public function show(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $dispatch = RentalDispatch::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })
            ->with(['customer', 'contract', 'items.equipment.category', 'creator'])
            ->findOrFail($id);

        return view('rental-event-equipment.rental-dispatches.show', compact('dispatch'));
    }

    public function confirmDispatch(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $dispatch = RentalDispatch::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })
            ->with('items.equipment')
            ->findOrFail($id);

        if ($dispatch->status !== 'draft') {
            return redirect()->route('rental-event-equipment.rental-dispatches.show', $dispatch)
                ->with('error', 'Only draft dispatches can be confirmed.');
        }

        DB::beginTransaction();
        try {
            // Update equipment status and quantity when confirming dispatch
            foreach ($dispatch->items as $item) {
                $equipment = Equipment::find($item->equipment_id);
                if ($equipment) {
                    // Decrement quantity_available (regardless of current status)
                    // This ensures we only dispatch available items, not damaged ones
                    $equipment->quantity_available = max(0, $equipment->quantity_available - $item->quantity);

                    // Update status to 'on_rent' if equipment has available quantity
                    // If all items are now on rent, status should be 'on_rent'
                    // If some are still available/reserved, we might want to keep a mixed status
                    // For simplicity, if quantity_available > 0, we could keep status as is, but for tracking, set to 'on_rent'
                    // Actually, let's set status to 'on_rent' to indicate items are out
                    if ($equipment->quantity_available >= 0) {
                        // Only change status if it's not a blocked status
                        if (!in_array($equipment->status, ['lost', 'disposed', 'cancelled'])) {
                            $equipment->status = 'on_rent';
                        }
                    }
                    $equipment->save();
                }
            }

            $dispatch->update(['status' => 'dispatched']);

            DB::commit();

            return redirect()->route('rental-event-equipment.rental-dispatches.show', $dispatch)
                ->with('success', 'Dispatch confirmed. Equipment status changed to On Rent.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('rental-event-equipment.rental-dispatches.show', $dispatch)
                ->with('error', 'Failed to confirm dispatch: ' . $e->getMessage());
        }
    }

    public function destroy(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $dispatch = RentalDispatch::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })
            ->with('items.equipment')
            ->findOrFail($id);

        if ($dispatch->status !== 'draft') {
            return redirect()->route('rental-event-equipment.rental-dispatches.index')
                ->with('error', 'Only draft dispatches can be deleted.');
        }

        $dispatch->delete();

        return redirect()->route('rental-event-equipment.rental-dispatches.index')
            ->with('success', 'Rental dispatch deleted successfully.');
    }

    public function getDispatchItems($id)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        // Try to decode if it's an encoded ID
        $decoded = Hashids::decode($id);
        $dispatchId = $decoded[0] ?? $id;

        $dispatch = RentalDispatch::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })
            ->with(['items.equipment.category', 'customer', 'contract'])
            ->find($dispatchId);

        if (!$dispatch) {
            return response()->json([
                'error' => 'Dispatch not found'
            ], 404);
        }

        return response()->json([
            'dispatch' => [
                'id' => $dispatch->id,
                'dispatch_number' => $dispatch->dispatch_number,
                'customer_name' => $dispatch->customer->name ?? 'N/A',
            ],
            'items' => $dispatch->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'equipment_id' => $item->equipment_id,
                    'equipment_name' => $item->equipment->name ?? 'N/A',
                    'equipment_code' => $item->equipment->equipment_code ?? 'N/A',
                    'quantity' => $item->quantity,
                ];
            })
        ]);
    }
}
