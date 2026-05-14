<?php

namespace App\Http\Controllers\RentalEventEquipment;

use App\Http\Controllers\Controller;
use App\Models\RentalEventEquipment\DecorationEquipmentPlan;
use App\Models\RentalEventEquipment\DecorationEquipmentPlanItem;
use App\Models\RentalEventEquipment\DecorationJob;
use App\Models\RentalEventEquipment\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class DecorationEquipmentPlanController extends Controller
{
    public function index()
    {
        return view('rental-event-equipment.decoration-equipment-plans.index');
    }

    public function data(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $query = DecorationEquipmentPlan::with(['job.customer'])
            ->forCompany($companyId)
            ->forBranch($branchId);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('job_number', function ($plan) {
                return optional($plan->job)->job_number ?? '-';
            })
            ->addColumn('customer_name', function ($plan) {
                return optional(optional($plan->job)->customer)->name ?? '-';
            })
            ->addColumn('status_badge', function ($plan) {
                $badgeClass = match ($plan->status) {
                    'draft' => 'secondary',
                    'finalized' => 'success',
                    'cancelled' => 'danger',
                    default => 'secondary',
                };
                return '<span class="badge bg-' . $badgeClass . '">' . ucfirst($plan->status) . '</span>';
            })
            ->addColumn('actions', function ($plan) {
                $encodedId = Hashids::encode($plan->id);
                return '<div class="text-center">
                    <a href="' . route('rental-event-equipment.decoration-equipment-plans.show', $encodedId) . '" class="btn btn-sm btn-outline-info" title="View">
                        <i class="bx bx-show"></i>
                    </a>
                    <a href="' . route('rental-event-equipment.decoration-equipment-plans.edit', $encodedId) . '" class="btn btn-sm btn-outline-primary" title="Edit">
                        <i class="bx bx-edit"></i>
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

        $jobs = DecorationJob::forCompany($companyId)
            ->forBranch($branchId)
            ->with('customer')
            ->orderBy('job_number', 'desc')
            ->get();

        $equipment = Equipment::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })
            ->orderBy('name')
            ->get();

        $selectedJobId = $request->get('job_id');
        $selectedJob = null;
        if ($selectedJobId) {
            $decoded = Hashids::decode($selectedJobId);
            $id = $decoded[0] ?? $selectedJobId;
            $selectedJob = DecorationJob::forCompany($companyId)
                ->forBranch($branchId)
                ->with('customer')
                ->find($id);
        }

        return view('rental-event-equipment.decoration-equipment-plans.create', compact('jobs', 'equipment', 'selectedJob'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'decoration_job_id' => 'required|exists:decoration_jobs,id',
            'status' => 'required|in:draft,finalized,cancelled',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.equipment_id' => 'required|exists:equipment,id',
            'items.*.quantity_planned' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
        ]);

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        DB::beginTransaction();
        try {
            $plan = DecorationEquipmentPlan::create([
                'decoration_job_id' => $request->decoration_job_id,
                'status' => $request->status,
                'notes' => $request->notes,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'created_by' => Auth::id(),
            ]);

            foreach ($request->items as $item) {
                DecorationEquipmentPlanItem::create([
                    'plan_id' => $plan->id,
                    'equipment_id' => $item['equipment_id'],
                    'quantity_planned' => $item['quantity_planned'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('rental-event-equipment.decoration-equipment-plans.show', $plan)
                ->with('success', 'Equipment plan created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create plan: ' . $e->getMessage());
        }
    }

    public function show(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $plan = DecorationEquipmentPlan::forCompany($companyId)
            ->forBranch($branchId)
            ->with(['job.customer', 'items.equipment', 'creator'])
            ->findOrFail($id);

        return view('rental-event-equipment.decoration-equipment-plans.show', compact('plan'));
    }

    public function edit(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $plan = DecorationEquipmentPlan::forCompany($companyId)
            ->forBranch($branchId)
            ->with(['items.equipment', 'job.customer'])
            ->findOrFail($id);

        if ($plan->status === 'cancelled') {
            return redirect()->route('rental-event-equipment.decoration-equipment-plans.show', $plan)
                ->with('error', 'Cancelled plans cannot be edited.');
        }

        $jobs = DecorationJob::forCompany($companyId)
            ->forBranch($branchId)
            ->with('customer')
            ->orderBy('job_number', 'desc')
            ->get();

        $equipment = Equipment::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })
            ->orderBy('name')
            ->get();

        return view('rental-event-equipment.decoration-equipment-plans.edit', compact('plan', 'jobs', 'equipment'));
    }

    public function update(Request $request, string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $plan = DecorationEquipmentPlan::forCompany($companyId)
            ->forBranch($branchId)
            ->with('items')
            ->findOrFail($id);

        if ($plan->status === 'cancelled') {
            return redirect()->route('rental-event-equipment.decoration-equipment-plans.show', $plan)
                ->with('error', 'Cancelled plans cannot be edited.');
        }

        $request->validate([
            'decoration_job_id' => 'required|exists:decoration_jobs,id',
            'status' => 'required|in:draft,finalized,cancelled',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.equipment_id' => 'required|exists:equipment,id',
            'items.*.quantity_planned' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $plan->update([
                'decoration_job_id' => $request->decoration_job_id,
                'status' => $request->status,
                'notes' => $request->notes,
                'updated_by' => Auth::id(),
            ]);

            // Replace items
            $plan->items()->delete();
            foreach ($request->items as $item) {
                DecorationEquipmentPlanItem::create([
                    'plan_id' => $plan->id,
                    'equipment_id' => $item['equipment_id'],
                    'quantity_planned' => $item['quantity_planned'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('rental-event-equipment.decoration-equipment-plans.show', $plan)
                ->with('success', 'Equipment plan updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update plan: ' . $e->getMessage());
        }
    }
}

