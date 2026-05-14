<?php

namespace App\Http\Controllers\RentalEventEquipment;

use App\Http\Controllers\Controller;
use App\Models\RentalEventEquipment\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class EquipmentStatusController extends Controller
{
    /**
     * Display equipment status tracking page.
     */
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        // Get status counts
        $statusCounts = Equipment::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->selectRaw('status, COUNT(*) as count, COALESCE(SUM(quantity_available), 0) as total_available, COALESCE(SUM(quantity_owned), 0) as total_owned')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        // Get selected status filter
        $selectedStatus = $request->get('status');

        return view('rental-event-equipment.status.index', compact('statusCounts', 'selectedStatus'));
    }

    /**
     * Get equipment data for DataTables with status breakdown.
     */
    public function data(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $query = Equipment::with(['category'])
            ->where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            });

        // Apply status filter if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && !empty($request->search['value'])) {
                    $searchValue = $request->search['value'];

                    $query->where(function ($q) use ($searchValue) {
                        $q->where('name', 'LIKE', '%' . $searchValue . '%')
                          ->orWhere('equipment_code', 'LIKE', '%' . $searchValue . '%')
                          ->orWhere('description', 'LIKE', '%' . $searchValue . '%');
                    });
                }
            })
            ->addIndexColumn()
            ->addColumn('category_name', function ($equipment) {
                return $equipment->category->name ?? 'N/A';
            })
            ->addColumn('status_breakdown', function ($equipment) use ($companyId, $branchId) {
                // Calculate quantities by status for this equipment
                $owned = $equipment->quantity_owned ?? 0;
                $available = $equipment->quantity_available ?? 0;
                
                // Calculate on_rent quantity from dispatched items (most accurate - these are definitely out)
                $onRentQty = \App\Models\RentalEventEquipment\RentalDispatchItem::whereHas('dispatch', function($q) use ($companyId, $branchId) {
                    $q->where('company_id', $companyId)
                      ->where(function($query) use ($branchId) {
                          $query->where('branch_id', $branchId)
                                ->orWhereNull('branch_id');
                      })
                      ->where('status', 'dispatched');
                })
                ->where('equipment_id', $equipment->id)
                ->sum('quantity');
                
                // Calculate reserved quantity from active contracts (items in contracts but not yet dispatched)
                // This is approximate - we sum contract items, but some may already be dispatched
                $totalContractQty = \App\Models\RentalEventEquipment\RentalContractItem::whereHas('contract', function($q) use ($companyId, $branchId) {
                    $q->where('company_id', $companyId)
                      ->where(function($query) use ($branchId) {
                          $query->where('branch_id', $branchId)
                                ->orWhereNull('branch_id');
                      })
                      ->where('status', 'active');
                })
                ->where('equipment_id', $equipment->id)
                ->sum('quantity');
                
                // Reserved = contract items minus dispatched items (approximate)
                $reservedQty = max(0, $totalContractQty - $onRentQty);
                
                // Calculate under_repair quantity from return items marked as damaged
                $underRepairQty = \App\Models\RentalEventEquipment\RentalReturnItem::whereHas('return', function($q) use ($companyId, $branchId) {
                    $q->where('company_id', $companyId)
                      ->where(function($query) use ($branchId) {
                          $query->where('branch_id', $branchId)
                                ->orWhereNull('branch_id');
                      })
                      ->where('status', 'completed');
                })
                ->where('equipment_id', $equipment->id)
                ->where('condition', 'damaged')
                ->sum('quantity_returned');
                
                // Calculate lost quantity (if equipment status is lost)
                $lostQty = 0;
                if ($equipment->status === 'lost') {
                    // Lost items are not available, reserved, on rent, or under repair
                    $lostQty = max(0, $owned - $available - $reservedQty - $onRentQty - $underRepairQty);
                }
                
                // Calculate in_event_use (if status is in_event_use)
                $inEventUseQty = 0;
                if ($equipment->status === 'in_event_use') {
                    $inEventUseQty = max(0, $owned - $available - $reservedQty - $onRentQty - $underRepairQty - $lostQty);
                }
                
                // Calculate other/unaccounted quantity
                $accountedQty = $available + $reservedQty + $onRentQty + $underRepairQty + $lostQty + $inEventUseQty;
                $otherQty = max(0, $owned - $accountedQty);
                
                // Build status breakdown HTML - show all statuses that have quantities
                $breakdown = '<div class="status-breakdown">';
                $breakdown .= '<div class="row g-2">';
                
                // Available
                if ($available > 0) {
                    $breakdown .= '<div class="col-12 mb-1"><span class="badge bg-success">Available: ' . $available . '</span></div>';
                }
                
                // Reserved
                if ($reservedQty > 0) {
                    $breakdown .= '<div class="col-12 mb-1"><span class="badge bg-warning">Reserved: ' . $reservedQty . '</span></div>';
                }
                
                // On Rent
                if ($onRentQty > 0) {
                    $breakdown .= '<div class="col-12 mb-1"><span class="badge bg-info">On Rent: ' . $onRentQty . '</span></div>';
                }
                
                // In Event Use
                if ($inEventUseQty > 0) {
                    $breakdown .= '<div class="col-12 mb-1"><span class="badge bg-primary">In Event Use: ' . $inEventUseQty . '</span></div>';
                }
                
                // Under Repair
                if ($underRepairQty > 0) {
                    $breakdown .= '<div class="col-12 mb-1"><span class="badge bg-danger">Under Repair: ' . $underRepairQty . '</span></div>';
                }
                
                // Lost
                if ($lostQty > 0) {
                    $breakdown .= '<div class="col-12 mb-1"><span class="badge bg-dark">Lost: ' . $lostQty . '</span></div>';
                }
                
                // Other/Unknown (only show if significant)
                if ($otherQty > 0 && $otherQty < $owned) {
                    $breakdown .= '<div class="col-12 mb-1"><span class="badge bg-secondary">Other: ' . $otherQty . '</span></div>';
                }
                
                $breakdown .= '</div>';
                $breakdown .= '<div class="mt-2"><small class="text-muted fw-bold">Total Owned: ' . $owned . '</small></div>';
                $breakdown .= '</div>';
                
                return $breakdown;
            })
            ->addColumn('actions', function ($equipment) {
                return view('rental-event-equipment.status.partials.actions', compact('equipment'))->render();
            })
            ->rawColumns(['status_breakdown', 'actions'])
            ->make(true);
    }
}
