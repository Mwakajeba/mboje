<?php

namespace App\Http\Controllers\RentalEventEquipment;

use App\Http\Controllers\Controller;
use App\Models\RentalEventEquipment\DecorationEquipmentIssue;
use App\Models\RentalEventEquipment\DecorationEquipmentReturn;
use App\Models\RentalEventEquipment\DecorationEquipmentReturnItem;
use App\Models\RentalEventEquipment\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class DecorationEquipmentReturnController extends Controller
{
    public function index()
    {
        return view('rental-event-equipment.decoration-equipment-returns.index');
    }

    public function data(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $query = DecorationEquipmentReturn::forCompany($companyId)
            ->forBranch($branchId)
            ->with(['issue.job.customer']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('issue_number', function ($return) {
                return $return->issue->issue_number ?? 'N/A';
            })
            ->addColumn('job_number', function ($return) {
                return $return->job->job_number ?? 'N/A';
            })
            ->addColumn('customer_name', function ($return) {
                return $return->job->customer->name ?? 'N/A';
            })
            ->addColumn('return_date_formatted', function ($return) {
                return $return->return_date ? $return->return_date->format('M d, Y') : '-';
            })
            ->addColumn('status_badge', function ($return) {
                $badgeClass = match ($return->status) {
                    'draft' => 'secondary',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    default => 'secondary',
                };
                return '<span class="badge bg-' . $badgeClass . '">' . ucfirst($return->status) . '</span>';
            })
            ->addColumn('actions', function ($return) {
                $encodedId = Hashids::encode($return->id);
                return '<div class="text-center">
                    <a href="' . route('rental-event-equipment.decoration-equipment-returns.show', $encodedId) . '" class="btn btn-sm btn-outline-info" title="View">
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

        $issues = DecorationEquipmentIssue::forCompany($companyId)
            ->forBranch($branchId)
            ->where('status', 'issued')
            ->with(['job.customer'])
            ->orderBy('issue_date', 'desc')
            ->get()
            ->map(function ($issue) {
                return [
                    'id' => $issue->id,
                    'issue_number' => $issue->issue_number,
                    'job_number' => $issue->job->job_number ?? 'N/A',
                    'customer_name' => $issue->job->customer->name ?? 'N/A',
                    'issue_date' => $issue->issue_date ? $issue->issue_date->format('M d, Y') : '-',
                    'encoded_id' => Hashids::encode($issue->id),
                ];
            });

        return view('rental-event-equipment.decoration-equipment-returns.create', compact('issues'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'issue_id' => 'required|exists:decoration_equipment_issues,id',
            'return_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.issue_item_id' => 'required|exists:decoration_equipment_issue_items,id',
            'items.*.equipment_id' => 'required|exists:equipment,id',
            'items.*.quantity_returned' => 'required|integer|min:0',
            'items.*.condition' => 'required|in:good,damaged,lost',
            'items.*.condition_notes' => 'nullable|string',
        ]);

        // Filter out zero-quantity lines
        $items = array_filter($request->items, function ($item) {
            return isset($item['quantity_returned']) && $item['quantity_returned'] > 0;
        });

        if (empty($items)) {
            return back()->withInput()->with('error', 'At least one item must have a quantity greater than 0.');
        }

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $issue = DecorationEquipmentIssue::forCompany($companyId)
            ->forBranch($branchId)
            ->with(['items'])
            ->findOrFail($request->issue_id);

        DB::beginTransaction();
        try {
            $nextId = (DecorationEquipmentReturn::max('id') ?? 0) + 1;
            $returnNumber = 'DER-' . date('Y') . '-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

            $return = DecorationEquipmentReturn::create([
                'return_number' => $returnNumber,
                'issue_id' => $issue->id,
                'decoration_job_id' => $issue->decoration_job_id,
                'return_date' => $request->return_date,
                'notes' => $request->notes,
                'status' => 'draft',
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'created_by' => Auth::id(),
            ]);

            // Aggregate per equipment & condition similar to rental returns
            $equipmentTotals = [];
            foreach ($items as $item) {
                $key = $item['equipment_id'] . '_' . $item['condition'];
                if (!isset($equipmentTotals[$key])) {
                    $equipmentTotals[$key] = [
                        'issue_item_id' => $item['issue_item_id'],
                        'equipment_id' => $item['equipment_id'],
                        'condition' => $item['condition'],
                        'quantity_returned' => 0,
                        'condition_notes' => $item['condition_notes'] ?? null,
                    ];
                }
                $equipmentTotals[$key]['quantity_returned'] += $item['quantity_returned'];
            }

            foreach ($equipmentTotals as $item) {
                DecorationEquipmentReturnItem::create([
                    'return_id' => $return->id,
                    'issue_item_id' => $item['issue_item_id'],
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
                    if ($totals['good'] > 0) {
                        $equipment->quantity_available = ($equipment->quantity_available ?? 0) + $totals['good'];
                    }

                    if ($totals['damaged'] > 0) {
                        $equipment->status = 'under_repair';
                    } elseif ($totals['good'] > 0 && $totals['lost'] == 0) {
                        $equipment->status = 'available';
                    }

                    if ($totals['lost'] > 0) {
                        $equipment->quantity_available = max(0, ($equipment->quantity_available ?? 0) - $totals['lost']);
                    }

                    $equipment->save();
                }
            }

            // Mark issue as returned if all quantities are accounted for
            $totalIssued = $issue->items->sum('quantity_issued');
            $totalReturned = collect($equipmentTotals)->sum('quantity_returned');
            if ($totalReturned >= $totalIssued) {
                $issue->update([
                    'status' => 'returned',
                    'updated_by' => Auth::id(),
                ]);
            }

            $return->update([
                'status' => 'completed',
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('rental-event-equipment.decoration-equipment-returns.index')
                ->with('success', 'Decoration equipment return recorded successfully.');
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

        $return = DecorationEquipmentReturn::forCompany($companyId)
            ->forBranch($branchId)
            ->with(['issue.job.customer', 'items.equipment', 'creator'])
            ->findOrFail($id);

        return view('rental-event-equipment.decoration-equipment-returns.show', compact('return'));
    }
}

