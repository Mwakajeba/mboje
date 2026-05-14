<?php

namespace App\Http\Controllers\RentalEventEquipment;

use App\Http\Controllers\Controller;
use App\Models\RentalEventEquipment\RentalReturn;
use App\Models\RentalEventEquipment\RentalReturnItem;
use App\Models\RentalEventEquipment\RentalDispatch;
use App\Models\RentalEventEquipment\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class RentalReturnController extends Controller
{
    public function index()
    {
        return view('rental-event-equipment.rental-returns.index');
    }

    public function data(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $query = RentalReturn::with(['customer', 'contract', 'dispatch'])
            ->where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            });

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('customer_name', function ($return) {
                return $return->customer->name ?? 'N/A';
            })
            ->addColumn('contract_number', function ($return) {
                return $return->contract->contract_number ?? 'N/A';
            })
            ->addColumn('dispatch_number', function ($return) {
                return $return->dispatch->dispatch_number ?? 'N/A';
            })
            ->addColumn('return_date_formatted', function ($return) {
                return $return->return_date->format('M d, Y');
            })
            ->addColumn('status_badge', function ($return) {
                $badgeClass = match($return->status) {
                    'draft' => 'secondary',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    default => 'secondary'
                };
                return '<span class="badge bg-' . $badgeClass . '">' . ucfirst($return->status) . '</span>';
            })
            ->addColumn('actions', function ($return) {
                $encodedId = Hashids::encode($return->id);
                return '<div class="text-center">
                    <a href="' . route('rental-event-equipment.rental-returns.show', $encodedId) . '" class="btn btn-sm btn-outline-info" title="View">
                        <i class="bx bx-show"></i>
                    </a>
                </div>';
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $dispatches = RentalDispatch::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->where('status', 'dispatched')
            ->with(['customer', 'contract', 'items.equipment'])
            ->orderBy('dispatch_date', 'desc')
            ->get()
            ->map(function ($dispatch) {
                return [
                    'id' => $dispatch->id,
                    'dispatch_number' => $dispatch->dispatch_number,
                    'customer_name' => $dispatch->customer->name ?? 'N/A',
                    'contract_number' => $dispatch->contract->contract_number ?? 'N/A',
                    'dispatch_date' => $dispatch->dispatch_date->format('M d, Y'),
                    'encoded_id' => Hashids::encode($dispatch->id),
                ];
            });

        return view('rental-event-equipment.rental-returns.create', compact('dispatches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'dispatch_id' => 'required|exists:rental_dispatches,id',
            'return_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.dispatch_item_id' => 'required|exists:rental_dispatch_items,id',
            'items.*.equipment_id' => 'required|exists:equipment,id',
            'items.*.quantity_returned' => 'required|integer|min:0',
            'items.*.condition' => 'required|in:good,damaged,lost',
            'items.*.condition_notes' => 'nullable|string',
        ]);
        
        // Filter out items with zero quantity
        $items = array_filter($request->items, function($item) {
            return isset($item['quantity_returned']) && $item['quantity_returned'] > 0;
        });
        
        if (empty($items)) {
            return back()->withInput()->with('error', 'At least one item must have a quantity greater than 0.');
        }

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $dispatch = RentalDispatch::with('items.equipment')->findOrFail($request->dispatch_id);

        DB::beginTransaction();
        try {
            $returnNumber = 'RET-' . date('Y') . '-' . str_pad((RentalReturn::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT);

            $return = RentalReturn::create([
                'return_number' => $returnNumber,
                'dispatch_id' => $request->dispatch_id,
                'contract_id' => $dispatch->contract_id,
                'customer_id' => $dispatch->customer_id,
                'return_date' => $request->return_date,
                'notes' => $request->notes,
                'status' => 'draft',
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'created_by' => Auth::id(),
            ]);

            // Group items by equipment_id and condition to handle bulk returns
            $equipmentTotals = [];
            foreach ($items as $item) {
                $key = $item['equipment_id'] . '_' . $item['condition'];
                if (!isset($equipmentTotals[$key])) {
                    $equipmentTotals[$key] = [
                        'dispatch_item_id' => $item['dispatch_item_id'],
                        'equipment_id' => $item['equipment_id'],
                        'condition' => $item['condition'],
                        'quantity_returned' => 0,
                        'condition_notes' => $item['condition_notes'] ?? null,
                    ];
                }
                $equipmentTotals[$key]['quantity_returned'] += $item['quantity_returned'];
            }
            
            // Create return items and update equipment
            foreach ($equipmentTotals as $item) {
                RentalReturnItem::create([
                    'return_id' => $return->id,
                    'dispatch_item_id' => $item['dispatch_item_id'],
                    'equipment_id' => $item['equipment_id'],
                    'quantity_returned' => $item['quantity_returned'],
                    'condition' => $item['condition'],
                    'condition_notes' => $item['condition_notes'],
                ]);
            }
            
            // Update equipment status and quantities
            $equipmentUpdates = [];
            foreach ($equipmentTotals as $item) {
                $equipmentId = $item['equipment_id'];
                if (!isset($equipmentUpdates[$equipmentId])) {
                    $equipmentUpdates[$equipmentId] = [
                        'good' => 0,
                        'damaged' => 0,
                        'lost' => 0,
                    ];
                }
                $equipmentUpdates[$equipmentId][$item['condition']] += $item['quantity_returned'];
            }
            
            foreach ($equipmentUpdates as $equipmentId => $totals) {
                $equipment = Equipment::find($equipmentId);
                if ($equipment) {
                    // Add good items back to available
                    if ($totals['good'] > 0) {
                        $equipment->quantity_available = ($equipment->quantity_available ?? 0) + $totals['good'];
                    }
                    
                    // Move damaged items to under repair
                    if ($totals['damaged'] > 0) {
                        // Equipment status will be under_repair if any damaged
                        $equipment->status = 'under_repair';
                    } elseif ($totals['good'] > 0 && $totals['lost'] == 0) {
                        // Only good items returned, set to available
                        $equipment->status = 'available';
                    }
                    
                    // Lost items reduce available quantity
                    if ($totals['lost'] > 0) {
                        $equipment->quantity_available = max(0, ($equipment->quantity_available ?? 0) - $totals['lost']);
                    }
                    
                    $equipment->save();
                }
            }

            // Update dispatch status if all items returned
            $totalDispatched = $dispatch->items->sum('quantity');
            $totalReturned = collect($equipmentTotals)->sum('quantity_returned');
            if ($totalReturned >= $totalDispatched) {
                $dispatch->update(['status' => 'returned']);
            }

            DB::commit();

            return redirect()->route('rental-event-equipment.rental-returns.index')
                ->with('success', 'Rental return recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to record return: ' . $e->getMessage());
        }
    }

    public function show(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $return = RentalReturn::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with(['customer', 'contract', 'dispatch', 'items.equipment', 'creator'])
            ->findOrFail($id);

        return view('rental-event-equipment.rental-returns.show', compact('return'));
    }

    public function destroy(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $return = RentalReturn::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->findOrFail($id);

        if ($return->status !== 'draft') {
            return redirect()->route('rental-event-equipment.rental-returns.index')
                ->with('error', 'Only draft returns can be deleted.');
        }

        $return->delete();

        return redirect()->route('rental-event-equipment.rental-returns.index')
            ->with('success', 'Rental return deleted successfully.');
    }
}
