<?php

namespace App\Http\Controllers\RentalEventEquipment;

use App\Http\Controllers\Controller;
use App\Models\RentalEventEquipment\RentalQuotation;
use App\Models\RentalEventEquipment\RentalQuotationItem;
use App\Models\RentalEventEquipment\Equipment;
use App\Models\RentalEventEquipment\RentalApproval;
use App\Models\Customer;
use App\Services\RentalEventEquipment\RentalApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class RentalQuotationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return view('rental-event-equipment.quotations.index');
    }

    /**
     * Get quotations data for DataTables.
     */
    public function data(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $query = RentalQuotation::with(['customer'])
            ->where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            });

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('customer_name', function ($quotation) {
                return $quotation->customer->name ?? 'N/A';
            })
            ->addColumn('quotation_date_formatted', function ($quotation) {
                return $quotation->quotation_date->format('M d, Y');
            })
            ->addColumn('valid_until_formatted', function ($quotation) {
                return $quotation->valid_until->format('M d, Y');
            })
            ->addColumn('status_badge', function ($quotation) {
                $status = $quotation->status ?? 'draft';
                $badgeClass = match($status) {
                    'draft' => 'secondary',
                    'pending_approval' => 'warning',
                    'sent' => 'info',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'expired' => 'warning',
                    'converted' => 'primary',
                    default => 'secondary'
                };
                $statusLabel = match($status) {
                    'pending_approval' => 'Pending Approval',
                    default => ucfirst($status)
                };
                return '<span class="badge bg-' . $badgeClass . '">' . $statusLabel . '</span>';
            })
            ->addColumn('total_amount_formatted', function ($quotation) {
                return 'TZS ' . number_format($quotation->total_amount, 2);
            })
            ->addColumn('actions', function ($quotation) {
                return view('rental-event-equipment.quotations.partials.actions', compact('quotation'))->render();
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

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

        return view('rental-event-equipment.quotations.create', compact('customers', 'equipment'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'quotation_date' => 'required|date',
            'valid_until' => 'required|date|after:quotation_date',
            'event_date' => 'nullable|date',
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

        // Generate quotation number
        $quotationNumber = 'RQ-' . date('Y') . '-' . str_pad((RentalQuotation::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT);

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

            $quotation = RentalQuotation::create([
                'quotation_number' => $quotationNumber,
                'customer_id' => $request->customer_id,
                'quotation_date' => $request->quotation_date,
                'valid_until' => $request->valid_until,
                'event_date' => $request->event_date,
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

            // Create quotation items
            foreach ($request->items as $item) {
                $itemTotal = $item['quantity'] * $item['rental_rate'] * $item['rental_days'];
                RentalQuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'equipment_id' => $item['equipment_id'],
                    'quantity' => $item['quantity'],
                    'rental_rate' => $item['rental_rate'],
                    'rental_days' => $item['rental_days'],
                    'total_amount' => $itemTotal,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();

            // Initialize approval workflow
            $approvalService = new RentalApprovalService();
            $approvalService->initializeApprovalWorkflow($quotation);

            return redirect()->route('rental-event-equipment.quotations.index')
                ->with('success', 'Rental quotation created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create quotation: ' . $e->getMessage());
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

        $quotation = RentalQuotation::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with(['customer', 'items.equipment.category', 'creator'])
            ->findOrFail($id);

        // Get approval information (only if table exists)
        $pendingApprovals = collect();
        $allApprovals = collect();
        try {
            $pendingApprovals = RentalApproval::where('approvable_type', RentalQuotation::class)
                ->where('approvable_id', $quotation->id)
                ->where('status', RentalApproval::STATUS_PENDING)
                ->with('approver')
                ->get()
                ->groupBy('approval_level');

            $allApprovals = RentalApproval::where('approvable_type', RentalQuotation::class)
                ->where('approvable_id', $quotation->id)
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
        
        if ($quotation->status === 'pending_approval' || $quotation->status === 'draft') {
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
        if ($isSuperAdmin && $quotation->status === 'draft' && $pendingApprovals->isEmpty()) {
            $canApprove = true;
            $userApprovalLevel = 1;
        }

        return view('rental-event-equipment.quotations.show', compact('quotation', 'pendingApprovals', 'allApprovals', 'canApprove', 'userApprovalLevel'));
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

        $quotation = RentalQuotation::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with(['items.equipment'])
            ->findOrFail($id);

        if (!in_array($quotation->status, ['draft', 'rejected'])) {
            return redirect()->route('rental-event-equipment.quotations.show', $quotation)
                ->with('error', 'Only draft or rejected quotations can be edited. Rejected quotations can be edited to reapply for approval.');
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

        return view('rental-event-equipment.quotations.edit', compact('quotation', 'customers', 'equipment'));
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

        $quotation = RentalQuotation::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->findOrFail($id);

        if (!in_array($quotation->status, ['draft', 'rejected'])) {
            return back()->with('error', 'Only draft or rejected quotations can be edited.');
        }

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'quotation_date' => 'required|date',
            'valid_until' => 'required|date|after:quotation_date',
            'event_date' => 'nullable|date',
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

            $quotation->update([
                'customer_id' => $request->customer_id,
                'quotation_date' => $request->quotation_date,
                'valid_until' => $request->valid_until,
                'event_date' => $request->event_date,
                'event_location' => $request->event_location,
                'notes' => $request->notes,
                'terms_conditions' => $request->terms_conditions,
                'subtotal' => $subtotal,
                'discount_type' => $request->discount_type,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'updated_by' => Auth::id(),
            ]);

            // Delete existing items
            $quotation->items()->delete();

            // Create new quotation items
            foreach ($request->items as $item) {
                $itemTotal = $item['quantity'] * $item['rental_rate'] * $item['rental_days'];
                RentalQuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'equipment_id' => $item['equipment_id'],
                    'quantity' => $item['quantity'],
                    'rental_rate' => $item['rental_rate'],
                    'rental_days' => $item['rental_days'],
                    'total_amount' => $itemTotal,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();

            // If quotation was rejected, reinitialize approval workflow (reapply for approval)
            $wasRejected = $quotation->status === 'rejected';
            if ($wasRejected) {
                $approvalService = new RentalApprovalService();
                $approvalService->reinitializeApprovalWorkflow($quotation);
            }

            if ($wasRejected) {
                return redirect()->route('rental-event-equipment.quotations.show', $quotation)
                    ->with('success', 'Quotation updated and resubmitted for approval successfully.');
            }

            return redirect()->route('rental-event-equipment.quotations.index')
                ->with('success', 'Rental quotation updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update quotation: ' . $e->getMessage());
        }
    }

    /**
     * Submit draft quotation for approval (draft -> pending_approval).
     */
    public function submitForApproval(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $quotation = RentalQuotation::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->findOrFail($id);

        if ($quotation->status !== 'draft') {
            return redirect()->route('rental-event-equipment.quotations.show', $quotation)
                ->with('error', 'Only draft quotations can be submitted for approval.');
        }

        $approvalService = new RentalApprovalService();
        $approvalService->reinitializeApprovalWorkflow($quotation);

        return redirect()->route('rental-event-equipment.quotations.show', $quotation)
            ->with('success', 'Quotation has been submitted for approval.');
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

        $quotation = RentalQuotation::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->findOrFail($id);

        if (!in_array($quotation->status, ['draft', 'rejected'])) {
            return redirect()->route('rental-event-equipment.quotations.index')
                ->with('error', 'Only draft quotations can be deleted.');
        }

        if ($quotation->contracts()->count() > 0) {
            return redirect()->route('rental-event-equipment.quotations.index')
                ->with('error', 'Cannot delete quotation that has been converted to a contract.');
        }

        // Clear any approval records before delete
        try {
            RentalApproval::where('approvable_type', RentalQuotation::class)
                ->where('approvable_id', $quotation->id)
                ->delete();
        } catch (\Exception $e) {
            // Ignore if table or records don't exist
        }

        $quotation->delete();

        return redirect()->route('rental-event-equipment.quotations.index')
            ->with('success', 'Rental quotation deleted successfully.');
    }

    /**
     * Export quotation to PDF
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

        $quotation = RentalQuotation::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with(['customer', 'items.equipment.category', 'creator', 'company', 'branch'])
            ->findOrFail($id);

        $company = $quotation->company;
        $branch = $quotation->branch;

        $pdf = \PDF::loadView('rental-event-equipment.quotations.export-pdf', compact('quotation', 'company', 'branch'));
        $filename = $quotation->quotation_number . '.pdf';
        return $pdf->download($filename);
    }
}
