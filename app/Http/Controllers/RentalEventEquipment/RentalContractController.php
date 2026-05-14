<?php

namespace App\Http\Controllers\RentalEventEquipment;

use App\Http\Controllers\Controller;
use App\Models\RentalEventEquipment\RentalContract;
use App\Models\RentalEventEquipment\RentalContractItem;
use App\Models\RentalEventEquipment\RentalQuotation;
use App\Models\RentalEventEquipment\RentalApproval;
use App\Models\RentalEventEquipment\Equipment;
use App\Models\Customer;
use App\Services\RentalEventEquipment\RentalApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class RentalContractController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return view('rental-event-equipment.contracts.index');
    }

    /**
     * Get contracts data for DataTables.
     */
    public function data(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $query = RentalContract::with(['customer', 'quotation'])
            ->where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->orderBy('created_at', 'desc'); // Sort by most recently created (newest first)

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('customer_name', function ($contract) {
                return $contract->customer->name ?? 'N/A';
            })
            ->addColumn('quotation_number', function ($contract) {
                return $contract->quotation->quotation_number ?? 'N/A';
            })
            ->addColumn('created_at_formatted', function ($contract) {
                return $contract->created_at->format('M d, Y H:i');
            })
            ->addColumn('contract_date_formatted', function ($contract) {
                return $contract->contract_date->format('M d, Y');
            })
            ->addColumn('event_date_formatted', function ($contract) {
                return $contract->event_date ? $contract->event_date->format('M d, Y') : 'N/A';
            })
            ->addColumn('status_badge', function ($contract) use ($companyId, $branchId) {
                $status = $contract->status ?? 'draft';
                
                // Check if contract has dispatches (items in rent)
                $hasDispatches = \App\Models\RentalEventEquipment\RentalDispatch::where('contract_id', $contract->id)
                    ->where('company_id', $companyId)
                    ->where(function ($q) use ($branchId) {
                        $q->where('branch_id', $branchId)
                          ->orWhereNull('branch_id');
                    })
                    ->where('status', 'dispatched')
                    ->exists();
                
                // Check if contract has returns (items returned)
                $hasReturns = \App\Models\RentalEventEquipment\RentalReturn::where('contract_id', $contract->id)
                    ->where('company_id', $companyId)
                    ->where(function ($q) use ($branchId) {
                        $q->where('branch_id', $branchId)
                          ->orWhereNull('branch_id');
                    })
                    ->where('status', 'completed')
                    ->exists();
                
                $badgeClass = match($status) {
                    'draft' => 'secondary',
                    'pending_approval' => 'warning',
                    'active' => 'success',
                    'approved' => 'success',
                    'completed' => 'info',
                    'cancelled' => 'danger',
                    'rejected' => 'danger',
                    default => 'secondary'
                };
                $statusLabel = match($status) {
                    'pending_approval' => 'Pending Approval',
                    default => ucfirst($status)
                };
                
                $badges = '<span class="badge bg-' . $badgeClass . '">' . $statusLabel . '</span>';
                
                // Add additional status badges
                if ($hasDispatches) {
                    $badges .= ' <span class="badge bg-info">In Rent</span>';
                }
                if ($hasReturns) {
                    $badges .= ' <span class="badge bg-success">Returned</span>';
                }
                
                return $badges;
            })
            ->addColumn('total_amount_formatted', function ($contract) {
                return 'TZS ' . number_format($contract->total_amount, 2);
            })
            ->addColumn('actions', function ($contract) {
                return view('rental-event-equipment.contracts.partials.actions', compact('contract'))->render();
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new contract from a quotation.
     */
    public function create(Request $request, string $quotationId = null)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $quotation = null;
        // Check both route parameter and query parameter
        $quotationIdParam = $quotationId ?? $request->get('quotation_id');
        
        if ($quotationIdParam) {
            $decoded = Hashids::decode($quotationIdParam);
            $id = $decoded[0] ?? null;

            if ($id) {
                $quotation = RentalQuotation::where('company_id', $companyId)
                    ->where(function ($q) use ($branchId) {
                        $q->where('branch_id', $branchId)
                          ->orWhereNull('branch_id');
                    })
                    ->with(['customer', 'items.equipment.category'])
                    ->findOrFail($id);

                if ($quotation->status === 'converted') {
                    return redirect()->route('rental-event-equipment.quotations.index')
                        ->with('error', 'This quotation has already been converted to a contract.');
                }
            }
        }

        $customers = Customer::where('company_id', $companyId)
            ->when($branchId, function ($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $equipment = Equipment::with(['category'])
            ->where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->orderBy('name')
            ->get();

        return view('rental-event-equipment.contracts.create', compact('quotation', 'customers', 'equipment'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'quotation_id' => 'nullable|exists:rental_quotations,id',
            'customer_id' => 'required|exists:customers,id',
            'contract_date' => 'required|date',
            'event_date' => 'nullable|date',
            'rental_start_date' => 'nullable|date',
            'rental_end_date' => 'nullable|date|after_or_equal:rental_start_date',
            'event_location' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.equipment_id' => 'required|exists:equipment,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.rental_rate' => 'required|numeric|min:0',
            'items.*.rental_days' => 'required|integer|min:1',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
        ]);

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        // Generate contract number
        $contractNumber = 'RC-' . date('Y') . '-' . str_pad((RentalContract::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT);

        DB::beginTransaction();
        try {
            // Calculate totals
            $subtotal = 0;
            foreach ($request->items as $item) {
                $itemTotal = $item['quantity'] * $item['rental_rate'] * $item['rental_days'];
                $subtotal += $itemTotal;
            }

            $discountAmount = $request->discount_amount ?? 0;
            if ($request->discount_type === 'percentage' && $discountAmount > 0) {
                $discountAmount = ($subtotal * $discountAmount) / 100;
            }

            $totalAmount = $subtotal - $discountAmount;

            $contract = RentalContract::create([
                'contract_number' => $contractNumber,
                'quotation_id' => $request->quotation_id,
                'customer_id' => $request->customer_id,
                'contract_date' => $request->contract_date,
                'event_date' => $request->event_date,
                'rental_start_date' => $request->rental_start_date,
                'rental_end_date' => $request->rental_end_date,
                'event_location' => $request->event_location,
                'status' => 'draft',
                'notes' => $request->notes,
                'terms_conditions' => $request->terms_conditions,
                'subtotal' => $subtotal,
                'discount_type' => $request->discount_type,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'created_by' => Auth::id(),
            ]);

            // Create contract items and update equipment status
            foreach ($request->items as $item) {
                $itemTotal = $item['quantity'] * $item['rental_rate'] * $item['rental_days'];
                
                RentalContractItem::create([
                    'contract_id' => $contract->id,
                    'equipment_id' => $item['equipment_id'],
                    'quantity' => $item['quantity'],
                    'rental_rate' => $item['rental_rate'],
                    'rental_days' => $item['rental_days'],
                    'total_amount' => $itemTotal,
                    'notes' => $item['notes'] ?? null,
                ]);

                // Update equipment status from Available to Reserved
                $equipment = Equipment::find($item['equipment_id']);
                if ($equipment && $equipment->status === 'available') {
                    // Reserve the quantity (reduce available quantity)
                    $equipment->quantity_available = max(0, $equipment->quantity_available - $item['quantity']);
                    $equipment->status = 'reserved';
                    $equipment->save();
                }
            }

            // Update quotation status if it was converted
            if ($request->quotation_id) {
                $quotation = RentalQuotation::find($request->quotation_id);
                if ($quotation) {
                    $quotation->update(['status' => 'converted']);
                }
            }

            DB::commit();

            // Initialize approval workflow
            $approvalService = new RentalApprovalService();
            $approvalService->initializeApprovalWorkflow($contract);

            return redirect()->route('rental-event-equipment.contracts.index')
                ->with('success', 'Rental contract created successfully. Equipment status updated to Reserved.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create contract: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $contract = RentalContract::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with(['customer', 'quotation', 'items.equipment.category', 'creator'])
            ->findOrFail($id);

        // Check if contract has dispatches (items in rent) or returns
        $hasDispatches = \App\Models\RentalEventEquipment\RentalDispatch::where('contract_id', $contract->id)
            ->where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->where('status', 'dispatched')
            ->exists();

        $hasReturns = \App\Models\RentalEventEquipment\RentalReturn::where('contract_id', $contract->id)
            ->where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->where('status', 'completed')
            ->exists();

        $canConvertToDispatch = $contract->status === 'active' && !$hasDispatches && !$hasReturns;

        // Get approval information (only if table exists)
        $pendingApprovals = collect();
        $allApprovals = collect();
        try {
            $pendingApprovals = RentalApproval::where('approvable_type', RentalContract::class)
                ->where('approvable_id', $contract->id)
                ->where('status', RentalApproval::STATUS_PENDING)
                ->with('approver')
                ->get()
                ->groupBy('approval_level');

            $allApprovals = RentalApproval::where('approvable_type', RentalContract::class)
                ->where('approvable_id', $contract->id)
                ->with('approver')
                ->orderBy('approval_level')
                ->orderBy('created_at')
                ->get();
        } catch (\Exception $e) {
            // Table doesn't exist yet, skip approval data
        }

        // Check if current user can approve
        $canApprove = false;
        $userApprovalLevel = null;
        $user = Auth::user();
        
        // Super admin can always approve
        $isSuperAdmin = $user->hasRole('super-admin') || $user->hasRole('Super Admin') || ($user->is_admin ?? false);
        
        if ($contract->status === 'pending_approval' || $contract->status === 'draft') {
            if ($isSuperAdmin) {
                // Super admin can approve at any level
                $canApprove = true;
                // Get the first pending level or use level 1
                $userApprovalLevel = $pendingApprovals->keys()->first() ?? 1;
            } else {
                // Regular user - check if they're in the approval records
                foreach ($pendingApprovals as $level => $approvals) {
                    $userApproval = $approvals->where('approver_id', Auth::id())->first();
                    if ($userApproval && $userApproval->status === RentalApproval::STATUS_PENDING) {
                        $canApprove = true;
                        $userApprovalLevel = $level;
                        break;
                    }
                }
            }
        }
        
        // If super admin and status is draft but no approval workflow initialized, allow approval
        if ($isSuperAdmin && $contract->status === 'draft' && $pendingApprovals->isEmpty()) {
            $canApprove = true;
            $userApprovalLevel = 1;
        }

        return view('rental-event-equipment.contracts.show', compact('contract', 'pendingApprovals', 'allApprovals', 'canApprove', 'userApprovalLevel', 'canConvertToDispatch'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $contract = RentalContract::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with(['items.equipment.category'])
            ->findOrFail($id);

        if ($contract->status !== 'draft') {
            return redirect()->route('rental-event-equipment.contracts.show', $contract)
                ->with('error', 'Only draft contracts can be edited.');
        }

        $customers = Customer::where('company_id', $companyId)
            ->when($branchId, function ($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $equipment = Equipment::with(['category'])
            ->where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->orderBy('name')
            ->get();

        return view('rental-event-equipment.contracts.edit', compact('contract', 'customers', 'equipment'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $contract = RentalContract::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with('items')
            ->findOrFail($id);

        if ($contract->status !== 'draft') {
            return back()->with('error', 'Only draft contracts can be edited.');
        }

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'contract_date' => 'required|date',
            'event_date' => 'nullable|date',
            'rental_start_date' => 'nullable|date',
            'rental_end_date' => 'nullable|date|after_or_equal:rental_start_date',
            'event_location' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.equipment_id' => 'required|exists:equipment,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.rental_rate' => 'required|numeric|min:0',
            'items.*.rental_days' => 'required|integer|min:1',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Restore equipment quantities from old items
            foreach ($contract->items as $oldItem) {
                $equipment = Equipment::find($oldItem->equipment_id);
                if ($equipment && $equipment->status === 'reserved') {
                    $equipment->quantity_available += $oldItem->quantity;
                    if ($equipment->quantity_available >= $equipment->quantity_owned) {
                        $equipment->status = 'available';
                    }
                    $equipment->save();
                }
            }

            // Calculate totals
            $subtotal = 0;
            foreach ($request->items as $item) {
                $itemTotal = $item['quantity'] * $item['rental_rate'] * $item['rental_days'];
                $subtotal += $itemTotal;
            }

            $discountAmount = $request->discount_amount ?? 0;
            if ($request->discount_type === 'percentage' && $discountAmount > 0) {
                $discountAmount = ($subtotal * $discountAmount) / 100;
            }

            $totalAmount = $subtotal - $discountAmount;

            $contract->update([
                'customer_id' => $request->customer_id,
                'contract_date' => $request->contract_date,
                'event_date' => $request->event_date,
                'rental_start_date' => $request->rental_start_date,
                'rental_end_date' => $request->rental_end_date,
                'event_location' => $request->event_location,
                'notes' => $request->notes,
                'terms_conditions' => $request->terms_conditions,
                'subtotal' => $subtotal,
                'discount_type' => $request->discount_type,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
            ]);

            // Delete existing items
            $contract->items()->delete();

            // Create new contract items and update equipment status
            foreach ($request->items as $item) {
                $itemTotal = $item['quantity'] * $item['rental_rate'] * $item['rental_days'];
                
                RentalContractItem::create([
                    'contract_id' => $contract->id,
                    'equipment_id' => $item['equipment_id'],
                    'quantity' => $item['quantity'],
                    'rental_rate' => $item['rental_rate'],
                    'rental_days' => $item['rental_days'],
                    'total_amount' => $itemTotal,
                    'notes' => $item['notes'] ?? null,
                ]);

                // Update equipment status from Available to Reserved
                $equipment = Equipment::find($item['equipment_id']);
                if ($equipment && $equipment->status === 'available') {
                    // Reserve the quantity (reduce available quantity)
                    $equipment->quantity_available = max(0, $equipment->quantity_available - $item['quantity']);
                    $equipment->status = 'reserved';
                    $equipment->save();
                }
            }

            DB::commit();

            return redirect()->route('rental-event-equipment.contracts.index')
                ->with('success', 'Rental contract updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update contract: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $contract = RentalContract::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with('items')
            ->findOrFail($id);

        if ($contract->status !== 'draft') {
            return redirect()->route('rental-event-equipment.contracts.index')
                ->with('error', 'Only draft contracts can be deleted.');
        }

        DB::beginTransaction();
        try {
            // Restore equipment status and quantity
            foreach ($contract->items as $item) {
                $equipment = Equipment::find($item->equipment_id);
                if ($equipment && $equipment->status === 'reserved') {
                    $equipment->quantity_available += $item->quantity;
                    if ($equipment->quantity_available >= $equipment->quantity_owned) {
                        $equipment->status = 'available';
                    }
                    $equipment->save();
                }
            }

            // Restore quotation status if it was converted
            if ($contract->quotation_id) {
                $quotation = RentalQuotation::find($contract->quotation_id);
                if ($quotation && $quotation->status === 'converted') {
                    $quotation->update(['status' => 'approved']);
                }
            }

            $contract->delete();

            DB::commit();

            return redirect()->route('rental-event-equipment.contracts.index')
                ->with('success', 'Rental contract deleted successfully. Equipment status restored.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('rental-event-equipment.contracts.index')
                ->with('error', 'Failed to delete contract: ' . $e->getMessage());
        }
    }

    /**
     * Export contract to PDF
     */
    public function exportPdf(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        if (!$id) {
            abort(404);
        }

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $contract = RentalContract::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with(['customer', 'items.equipment.category', 'creator', 'company', 'branch', 'quotation'])
            ->findOrFail($id);

        $company = $contract->company;
        $branch = $contract->branch;

        $pdf = \PDF::loadView('rental-event-equipment.contracts.export-pdf', compact('contract', 'company', 'branch'));
        $filename = $contract->contract_number . '.pdf';
        return $pdf->download($filename);
    }
}
