<?php

namespace App\Http\Controllers\RentalEventEquipment;

use App\Http\Controllers\Controller;
use App\Models\GlTransaction;
use App\Models\RentalEventEquipment\AccountingSetting;
use App\Models\RentalEventEquipment\DecorationEquipmentLoss;
use App\Models\RentalEventEquipment\DecorationEquipmentLossItem;
use App\Models\RentalEventEquipment\DecorationJob;
use App\Models\RentalEventEquipment\Equipment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class DecorationEquipmentLossController extends Controller
{
    public function index()
    {
        return view('rental-event-equipment.decoration-losses.index');
    }

    /**
     * Get equipment options related to a specific decoration job for loss recording.
     */
    public function getJobEquipment(Request $request, int $jobId)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        try {
            // Equipment that has been issued for this job (via decoration equipment issues)
            $equipment = Equipment::where('company_id', $companyId)
                ->where(function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                        ->orWhereNull('branch_id');
                })
                ->whereIn('id', function ($q) use ($jobId) {
                    $q->select('equipment_id')
                        ->from('decoration_equipment_issue_items as dei')
                        ->join('decoration_equipment_issues as de', 'dei.issue_id', '=', 'de.id')
                        ->where('de.decoration_job_id', $jobId);
                })
                ->orderBy('name')
                ->get()
                ->map(function (Equipment $eq) {
                    return [
                        'id' => $eq->id,
                        'name' => $eq->name,
                        'code' => $eq->equipment_code,
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'equipment' => $equipment,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch equipment for decoration job in loss form', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'equipment' => [],
            ], 500);
        }
    }

    public function data(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $query = DecorationEquipmentLoss::forCompany($companyId)
            ->forBranch($branchId)
            ->with(['job.customer', 'equipment', 'responsibleEmployee']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('job_number', function ($loss) {
                return optional($loss->job)->job_number ?? '-';
            })
            ->addColumn('customer_name', function ($loss) {
                return optional(optional($loss->job)->customer)->name ?? '-';
            })
            ->addColumn('equipment_name', function ($loss) {
                return optional($loss->equipment)->name ?? 'N/A';
            })
            ->addColumn('loss_date_formatted', function ($loss) {
                return optional($loss->loss_date)->format('M d, Y') ?? '-';
            })
            ->addColumn('loss_type_badge', function ($loss) {
                $badgeClass = $loss->loss_type === 'employee' ? 'warning' : 'danger';
                $label = $loss->loss_type === 'employee' ? 'Employee Liability' : 'Business Expense';
                return '<span class="badge bg-' . $badgeClass . '">' . $label . '</span>';
            })
            ->addColumn('status_badge', function ($loss) {
                $badgeClass = match ($loss->status) {
                    'draft' => 'secondary',
                    'confirmed' => 'success',
                    'cancelled' => 'dark',
                    default => 'secondary',
                };
                return '<span class="badge bg-' . $badgeClass . '">' . ucfirst($loss->status) . '</span>';
            })
            ->addColumn('actions', function ($loss) {
                $encodedId = Hashids::encode($loss->id);
                return '<div class="text-center">
                    <a href="' . route('rental-event-equipment.decoration-losses.show', $encodedId) . '" class="btn btn-sm btn-outline-info" title="View">
                        <i class="bx bx-show"></i>
                    </a>
                </div>';
            })
            ->rawColumns(['loss_type_badge', 'status_badge', 'actions'])
            ->make(true);
    }

    public function create()
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

        $employees = User::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return view('rental-event-equipment.decoration-losses.create', compact('jobs', 'equipment', 'employees'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'decoration_job_id' => 'nullable|exists:decoration_jobs,id',
            'responsible_employee_id' => 'nullable|exists:users,id',
            'loss_type' => 'required|in:business,employee',
            'loss_date' => 'nullable|date',
            'reason' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.equipment_id' => 'required|exists:equipment,id',
            'items.*.quantity_lost' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
        ]);

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        // Filter out any items with non-positive quantities (safety)
        $items = collect($request->input('items', []))
            ->filter(function ($item) {
                return isset($item['equipment_id'], $item['quantity_lost'])
                    && (int) $item['quantity_lost'] > 0;
            });

        if ($items->isEmpty()) {
            return back()
                ->withInput()
                ->with('error', 'At least one equipment line with a positive quantity is required.');
        }

        DB::beginTransaction();
        try {
            $nextId = (DecorationEquipmentLoss::max('id') ?? 0) + 1;
            $lossNumber = 'LOSS-' . date('Y') . '-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

            // Use the first item as the header equipment for backward compatibility
            $firstItem = $items->first();
            $totalQuantity = $items->sum(function ($item) {
                return (int) $item['quantity_lost'];
            });

            $loss = DecorationEquipmentLoss::create([
                'loss_number' => $lossNumber,
                'decoration_job_id' => $request->decoration_job_id,
                'equipment_id' => $firstItem['equipment_id'],
                'responsible_employee_id' => $request->loss_type === 'employee' ? $request->responsible_employee_id : null,
                'loss_type' => $request->loss_type,
                'quantity_lost' => $totalQuantity,
                'loss_date' => $request->loss_date,
                'reason' => $request->reason,
                'status' => 'draft',
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'created_by' => Auth::id(),
            ]);

            foreach ($items as $item) {
                DecorationEquipmentLossItem::create([
                    'loss_id' => $loss->id,
                    'equipment_id' => $item['equipment_id'],
                    'quantity_lost' => $item['quantity_lost'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('rental-event-equipment.decoration-losses.show', $loss)
                ->with('success', 'Decoration equipment loss recorded as draft. You can confirm it after review.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to record loss: ' . $e->getMessage());
        }
    }

    public function show(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $loss = DecorationEquipmentLoss::forCompany($companyId)
            ->forBranch($branchId)
            ->with(['job.customer', 'equipment', 'responsibleEmployee', 'creator'])
            ->findOrFail($id);

        return view('rental-event-equipment.decoration-losses.show', compact('loss'));
    }

    public function confirm(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $loss = DecorationEquipmentLoss::forCompany($companyId)
            ->forBranch($branchId)
            ->with('equipment')
            ->findOrFail($id);

        if ($loss->status !== 'draft') {
            return redirect()->route('rental-event-equipment.decoration-losses.show', $loss)
                ->with('error', 'Only draft losses can be confirmed.');
        }

        DB::beginTransaction();
        try {
            // Stock quantities for lost items are already adjusted during returns.
            // This confirmation step is for internal tracking and accounting classification only.

            $loss->update([
                'status' => 'confirmed',
                'updated_by' => Auth::id(),
            ]);

            // Post GL only for business expense losses where we have costing info and settings
            if ($loss->loss_type === 'business') {
                $this->postGlForDecorationLoss($loss);
            } else {
                // For employee liability, classification is tracked here; GL can be handled via separate HR/AR flows later.
                GlTransaction::where('transaction_type', 'decoration_loss')
                    ->where('transaction_id', $loss->id)
                    ->delete();
            }

            DB::commit();

            return redirect()->route('rental-event-equipment.decoration-losses.show', $loss)
                ->with('success', 'Loss confirmed as ' . ($loss->loss_type === 'employee' ? 'Employee Liability' : 'Business Expense') . '.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('rental-event-equipment.decoration-losses.show', $loss)
                ->with('error', 'Failed to confirm loss: ' . $e->getMessage());
        }
    }

    /**
     * Post GL double-entry for a decoration equipment loss (business expense).
     *
     * Dr Loss on Equipment (expense)
     * Cr Rental & Event Equipment (asset)
     */
    protected function postGlForDecorationLoss(DecorationEquipmentLoss $loss): void
    {
        // Prefer line items; fall back to header equipment for older records
        $items = $loss->items()->with('equipment')->get();

        $amount = 0;
        $descriptionSuffix = '';

        if ($items->count() > 0) {
            foreach ($items as $item) {
                $equipment = $item->equipment;
                if (! $equipment || $item->quantity_lost <= 0) {
                    continue;
                }
                $unitCost = (float) ($equipment->replacement_cost ?? 0);
                if ($unitCost <= 0) {
                    continue;
                }
                $amount += $unitCost * (int) $item->quantity_lost;
            }

            if ($amount <= 0) {
                return;
            }

            $descriptionSuffix = $items->count() === 1
                ? $items->first()->equipment->name
                : 'Multiple items';
        } else {
            $equipment = $loss->equipment;
            if (! $equipment || $loss->quantity_lost <= 0) {
                return;
            }

            // Use replacement_cost as proxy for carrying amount
            $unitCost = (float) ($equipment->replacement_cost ?? 0);
            if ($unitCost <= 0) {
                return;
            }

            $amount = $unitCost * $loss->quantity_lost;
            $descriptionSuffix = $equipment->name;
        }

        $companyId = $loss->company_id;
        $branchId = $loss->branch_id;

        $settings = AccountingSetting::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })
            ->first();

        if (! $settings || ! $settings->loss_on_equipment_account_id || ! $settings->rental_equipment_account_id) {
            return;
        }

        // Clear existing GL transactions for this loss
        GlTransaction::where('transaction_type', 'decoration_loss')
            ->where('transaction_id', $loss->id)
            ->delete();

        $description = "Decoration equipment loss {$loss->loss_number}" . ($descriptionSuffix ? " - {$descriptionSuffix}" : '');
        $userId = Auth::id();
        $lossDate = $loss->loss_date ?? now();

        // Dr Loss on Equipment (expense)
        GlTransaction::create([
            'chart_account_id' => $settings->loss_on_equipment_account_id,
            'customer_id' => null,
            'supplier_id' => null,
            'amount' => $amount,
            'nature' => 'debit',
            'transaction_id' => $loss->id,
            'transaction_type' => 'decoration_loss',
            'date' => $lossDate,
            'description' => $description,
            'branch_id' => $branchId,
            'user_id' => $userId,
        ]);

        // Cr Rental & Event Equipment (asset)
        GlTransaction::create([
            'chart_account_id' => $settings->rental_equipment_account_id,
            'customer_id' => null,
            'supplier_id' => null,
            'amount' => $amount,
            'nature' => 'credit',
            'transaction_id' => $loss->id,
            'transaction_type' => 'decoration_loss',
            'date' => $lossDate,
            'description' => $description,
            'branch_id' => $branchId,
            'user_id' => $userId,
        ]);
    }
}

