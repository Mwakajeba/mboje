<?php

namespace App\Http\Controllers\RentalEventEquipment;

use App\Http\Controllers\Controller;
use App\Models\RentalEventEquipment\RentalDamageCharge;
use App\Models\RentalEventEquipment\RentalDamageChargeItem;
use App\Models\RentalEventEquipment\RentalReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class RentalDamageChargeController extends Controller
{
    public function index()
    {
        return view('rental-event-equipment.damage-charges.index');
    }

    public function data(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $query = RentalDamageCharge::with(['customer', 'contract', 'return'])
            ->where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            });

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('customer_name', function ($charge) {
                return $charge->customer->name ?? 'N/A';
            })
            ->addColumn('contract_number', function ($charge) {
                return $charge->contract->contract_number ?? 'N/A';
            })
            ->addColumn('charge_date_formatted', function ($charge) {
                return $charge->charge_date->format('M d, Y');
            })
            ->addColumn('total_charges_formatted', function ($charge) {
                return 'TZS ' . number_format($charge->total_charges, 2);
            })
            ->addColumn('status_badge', function ($charge) {
                $badgeClass = match($charge->status) {
                    'draft' => 'secondary',
                    'invoiced' => 'success',
                    'cancelled' => 'danger',
                    default => 'secondary'
                };
                return '<span class="badge bg-' . $badgeClass . '">' . ucfirst($charge->status) . '</span>';
            })
            ->addColumn('actions', function ($charge) {
                $encodedId = Hashids::encode($charge->id);
                return '<div class="text-center">
                    <a href="' . route('rental-event-equipment.damage-charges.show', $encodedId) . '" class="btn btn-sm btn-outline-info" title="View">
                        <i class="bx bx-show"></i>
                    </a>
                </div>';
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    public function create(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $returnId = $request->get('return_id');
        
        // Check if table exists before querying
        $returns = collect([]);
        try {
            $returns = RentalReturn::where('company_id', $companyId)
                ->where(function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
                })
                ->where('status', 'completed')
                ->with(['customer', 'contract', 'dispatch', 'items.equipment'])
                ->orderBy('return_date', 'desc')
                ->get()
                ->map(function ($return) {
                    return [
                        'id' => $return->id,
                        'return_number' => $return->return_number,
                        'customer_name' => $return->customer->name ?? 'N/A',
                        'return_date' => $return->return_date->format('M d, Y'),
                        'encoded_id' => Hashids::encode($return->id),
                    ];
                });
        } catch (\Exception $e) {
            // Table doesn't exist yet - migrations not run
            \Log::warning('Rental returns table does not exist yet', ['error' => $e->getMessage()]);
        }

        $selectedReturn = null;
        if ($returnId) {
            try {
                $decoded = Hashids::decode($returnId);
                $id = $decoded[0] ?? null;
                if ($id) {
                    $selectedReturn = RentalReturn::with(['items.equipment', 'customer', 'contract', 'dispatch'])
                        ->find($id);
                }
            } catch (\Exception $e) {
                \Log::warning('Could not load selected return', ['error' => $e->getMessage()]);
            }
        }

        return view('rental-event-equipment.damage-charges.create', compact('returns', 'selectedReturn'));
    }

    public function getReturnItems(Request $request, $returnId)
    {
        try {
            $decoded = Hashids::decode($returnId);
            $id = $decoded[0] ?? null;
            
            if (!$id) {
                return response()->json(['error' => 'Invalid return ID'], 400);
            }

            $companyId = Auth::user()->company_id;
            $branchId = session('branch_id') ?: Auth::user()->branch_id;

            $return = RentalReturn::where('company_id', $companyId)
                ->where(function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
                })
                ->with(['items.equipment', 'customer', 'contract', 'dispatch'])
                ->find($id);
            
            if (!$return) {
                return response()->json(['error' => 'Return not found'], 404);
            }

            // Get only damaged or lost items
            $damagedLostItems = $return->items()
                ->whereIn('condition', ['damaged', 'lost'])
                ->with('equipment')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'return_item_id' => $item->id,
                        'equipment_id' => $item->equipment_id,
                        'equipment_name' => $item->equipment->name ?? 'N/A',
                        'equipment_code' => $item->equipment->equipment_code ?? 'N/A',
                        'quantity' => $item->quantity_returned,
                        'condition' => $item->condition,
                        'condition_notes' => $item->condition_notes ?? '',
                    ];
                });

            return response()->json([
                'success' => true,
                'return' => [
                    'id' => $return->id,
                    'return_number' => $return->return_number,
                    'customer_name' => $return->customer->name ?? 'N/A',
                    'return_date' => $return->return_date->format('M d, Y'),
                ],
                'items' => $damagedLostItems,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching return items', ['error' => $e->getMessage(), 'return_id' => $returnId]);
            return response()->json(['error' => 'Failed to fetch return items: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        // Decode return_id if it's encoded
        $returnId = $request->return_id;
        try {
            $decoded = Hashids::decode($returnId);
            if (!empty($decoded)) {
                $returnId = $decoded[0];
            }
        } catch (\Exception $e) {
            // If decode fails, use original value (might be numeric ID)
        }

        $request->validate([
            'return_id' => 'required',
            'charge_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.return_item_id' => 'nullable|exists:rental_return_items,id',
            'items.*.equipment_id' => 'required|exists:equipment,id',
            'items.*.charge_type' => 'required|in:damage,loss',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_charge' => 'required|numeric|min:0',
            'items.*.description' => 'nullable|string',
        ]);

        // Filter out items with zero quantity or unchecked
        $items = array_filter($request->items, function($item) {
            return isset($item['quantity']) && $item['quantity'] > 0 && 
                   isset($item['unit_charge']) && $item['unit_charge'] > 0;
        });

        if (empty($items)) {
            return back()->withInput()->with('error', 'At least one item must have a quantity and unit charge greater than 0.');
        }

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $return = RentalReturn::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with(['customer', 'contract', 'dispatch'])
            ->findOrFail($returnId);

        DB::beginTransaction();
        try {
            $chargeNumber = 'DAM-' . date('Y') . '-' . str_pad((RentalDamageCharge::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT);

            $totalDamageCharges = 0;
            $totalLossCharges = 0;

            foreach ($items as $item) {
                if ($item['charge_type'] === 'damage') {
                    $totalDamageCharges += ($item['quantity'] * $item['unit_charge']);
                } else {
                    $totalLossCharges += ($item['quantity'] * $item['unit_charge']);
                }
            }

            $charge = RentalDamageCharge::create([
                'charge_number' => $chargeNumber,
                'return_id' => $returnId,
                'dispatch_id' => $return->dispatch_id,
                'contract_id' => $return->contract_id,
                'customer_id' => $return->customer_id,
                'charge_date' => $request->charge_date,
                'total_damage_charges' => $totalDamageCharges,
                'total_loss_charges' => $totalLossCharges,
                'total_charges' => $totalDamageCharges + $totalLossCharges,
                'notes' => $request->notes,
                'status' => 'draft',
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'created_by' => Auth::id(),
            ]);

            foreach ($items as $item) {
                RentalDamageChargeItem::create([
                    'damage_charge_id' => $charge->id,
                    'return_item_id' => $item['return_item_id'] ?? null,
                    'equipment_id' => $item['equipment_id'],
                    'charge_type' => $item['charge_type'],
                    'quantity' => $item['quantity'],
                    'unit_charge' => $item['unit_charge'],
                    'total_charge' => $item['quantity'] * $item['unit_charge'],
                    'description' => $item['description'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('rental-event-equipment.damage-charges.index')
                ->with('success', 'Damage charges created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create damage charges: ' . $e->getMessage());
        }
    }

    public function show(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $charge = RentalDamageCharge::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with(['customer', 'contract', 'return', 'items.equipment', 'creator'])
            ->findOrFail($id);

        return view('rental-event-equipment.damage-charges.show', compact('charge'));
    }

    public function destroy(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $charge = RentalDamageCharge::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->findOrFail($id);

        if ($charge->status !== 'draft') {
            return redirect()->route('rental-event-equipment.damage-charges.index')
                ->with('error', 'Only draft charges can be deleted.');
        }

        $charge->delete();

        return redirect()->route('rental-event-equipment.damage-charges.index')
            ->with('success', 'Damage charge deleted successfully.');
    }
}
