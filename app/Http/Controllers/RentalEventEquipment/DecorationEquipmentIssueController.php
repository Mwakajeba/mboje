<?php

namespace App\Http\Controllers\RentalEventEquipment;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\RentalEventEquipment\DecorationEquipmentIssue;
use App\Models\RentalEventEquipment\DecorationEquipmentIssueItem;
use App\Models\RentalEventEquipment\DecorationJob;
use App\Models\RentalEventEquipment\Equipment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class DecorationEquipmentIssueController extends Controller
{
    public function index()
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        // Simple alerts for outstanding issues (not yet fully returned)
        $issues = DecorationEquipmentIssue::forCompany($companyId)
            ->forBranch($branchId)
            ->whereIn('status', ['issued'])
            ->with(['job.customer', 'decorator'])
            ->orderBy('issue_date', 'desc')
            ->get();

        return view('rental-event-equipment.decoration-equipment-issues.index', compact('issues'));
    }

    public function data(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $query = DecorationEquipmentIssue::with(['job.customer', 'decorator'])
            ->forCompany($companyId)
            ->forBranch($branchId);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('job_number', function ($issue) {
                return $issue->job->job_number ?? 'N/A';
            })
            ->addColumn('customer_name', function ($issue) {
                return $issue->job->customer->name ?? 'N/A';
            })
            ->addColumn('decorator_name', function ($issue) {
                return $issue->decorator->name ?? '-';
            })
            ->addColumn('issue_date_formatted', function ($issue) {
                return $issue->issue_date ? $issue->issue_date->format('M d, Y') : '-';
            })
            ->addColumn('status_badge', function ($issue) {
                $badgeClass = match ($issue->status) {
                    'draft' => 'secondary',
                    'issued' => 'primary',
                    'returned' => 'success',
                    'cancelled' => 'danger',
                    default => 'secondary'
                };
                return '<span class="badge bg-' . $badgeClass . '">' . ucfirst($issue->status) . '</span>';
            })
            ->addColumn('actions', function ($issue) {
                return view('rental-event-equipment.decoration-equipment-issues.partials.actions', compact('issue'))->render();
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

        // Use all equipment tagged as decoration/rental equipment; filter is left simple here
        $equipment = Equipment::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })
            ->orderBy('name')
            ->get();

        // Decorators: reuse users table, you can later filter by role
        $decorators = User::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return view('rental-event-equipment.decoration-equipment-issues.create', compact(
            'jobs',
            'selectedJob',
            'selectedJobId',
            'equipment',
            'decorators'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'decoration_job_id' => 'required|exists:decoration_jobs,id',
            'decorator_id' => 'nullable|exists:users,id',
            'issue_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.equipment_id' => 'required|exists:equipment,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        DB::beginTransaction();
        try {
            $nextId = (DecorationEquipmentIssue::max('id') ?? 0) + 1;
            $issueNumber = 'DEI-' . date('Y') . '-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

            $issue = DecorationEquipmentIssue::create([
                'issue_number' => $issueNumber,
                'decoration_job_id' => $request->decoration_job_id,
                'decorator_id' => $request->decorator_id,
                'issue_date' => $request->issue_date,
                'notes' => $request->notes,
                'status' => 'draft',
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'created_by' => Auth::id(),
            ]);

            foreach ($request->items as $item) {
                DecorationEquipmentIssueItem::create([
                    'issue_id' => $issue->id,
                    'equipment_id' => $item['equipment_id'],
                    'quantity_issued' => $item['quantity'],
                    'remarks' => $item['remarks'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('rental-event-equipment.decoration-equipment-issues.show', $issue)
                ->with('success', 'Decoration equipment issue created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create issue: ' . $e->getMessage());
        }
    }

    public function show(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $issue = DecorationEquipmentIssue::forCompany($companyId)
            ->forBranch($branchId)
            ->with(['job.customer', 'decorator', 'items.equipment'])
            ->findOrFail($id);

        return view('rental-event-equipment.decoration-equipment-issues.show', compact('issue'));
    }

    public function confirmIssue(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $issue = DecorationEquipmentIssue::forCompany($companyId)
            ->forBranch($branchId)
            ->with('items.equipment')
            ->findOrFail($id);

        if ($issue->status !== 'draft') {
            return redirect()->route('rental-event-equipment.decoration-equipment-issues.show', $issue)
                ->with('error', 'Only draft issues can be confirmed.');
        }

        DB::beginTransaction();
        try {
            foreach ($issue->items as $item) {
                $equipment = Equipment::find($item->equipment_id);
                if ($equipment) {
                    // Reduce available quantity
                    $equipment->quantity_available = max(0, ($equipment->quantity_available ?? 0) - $item->quantity_issued);

                    // Set status to in_event_use if not blocked
                    if (!in_array($equipment->status, ['lost', 'disposed', 'cancelled'])) {
                        $equipment->status = 'in_event_use';
                    }

                    $equipment->save();
                }
            }

            $issue->update([
                'status' => 'issued',
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('rental-event-equipment.decoration-equipment-issues.show', $issue)
                ->with('success', 'Issue confirmed. Equipment moved to In Event Use.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('rental-event-equipment.decoration-equipment-issues.show', $issue)
                ->with('error', 'Failed to confirm issue: ' . $e->getMessage());
        }
    }

    /**
     * Get issue items for use when recording returns (AJAX).
     */
    public function getIssueItems($id)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        // Allow both encoded and plain IDs
        $decoded = Hashids::decode($id);
        $issueId = $decoded[0] ?? $id;

        $issue = DecorationEquipmentIssue::forCompany($companyId)
            ->forBranch($branchId)
            ->with(['items.equipment', 'job.customer'])
            ->find($issueId);

        if (!$issue) {
            return response()->json([
                'error' => 'Issue not found',
            ], 404);
        }

        return response()->json([
            'issue' => [
                'id' => $issue->id,
                'issue_number' => $issue->issue_number,
                'job_number' => optional($issue->job)->job_number,
                'customer_name' => optional(optional($issue->job)->customer)->name,
                'issue_date' => optional($issue->issue_date)->format('M d, Y'),
            ],
            'items' => $issue->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'issue_item_id' => $item->id,
                    'equipment_id' => $item->equipment_id,
                    'equipment_name' => optional($item->equipment)->name ?? 'N/A',
                    'equipment_code' => optional($item->equipment)->equipment_code ?? 'N/A',
                    'quantity_issued' => $item->quantity_issued,
                ];
            }),
        ]);
    }
}

