<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\CountPeriod;
use App\Models\Inventory\CountSession;
use App\Models\Inventory\CountEntry;
use App\Models\Inventory\CountTeam;
use App\Models\Inventory\CountVariance;
use App\Models\Inventory\CountAdjustment;
use App\Models\Inventory\CountAdjustmentApproval;
use App\Models\Inventory\Item;
use App\Models\InventoryLocation;
use App\Models\Inventory\StockLevel;
use App\Models\Inventory\Movement;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\ChartAccount;
use App\Models\InventoryCostLayer;
use App\Models\SystemSetting;
use App\Services\InventoryCostService;
use App\Services\InventoryStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class InventoryCountController extends Controller
{
    /**
     * Display a listing of count periods and sessions
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        if ($request->ajax()) {
            // Check if requesting periods data
            if ($request->get('type') === 'periods') {
                $query = CountPeriod::with(['location', 'responsibleStaff', 'sessions'])
                    ->forCompany($companyId);

                return DataTables::of($query)
                    ->addIndexColumn()
                    ->addColumn('period_name_link', function ($period) {
                        return '<a href="' . route('inventory.counts.periods.show', $period->encoded_id) . '" class="text-primary fw-bold">' . $period->period_name . '</a>';
                    })
                    ->addColumn('count_type_formatted', function ($period) {
                        return ucfirst(str_replace('_', ' ', $period->count_type));
                    })
                    ->addColumn('count_start_date_formatted', function ($period) {
                        return $period->count_start_date->format('M d, Y');
                    })
                    ->addColumn('count_end_date_formatted', function ($period) {
                        return $period->count_end_date->format('M d, Y');
                    })
                    ->addColumn('location_name', function ($period) {
                        return $period->location ? $period->location->name : 'All Locations';
                    })
                    ->addColumn('sessions_count', function ($period) {
                        return '<span class="badge bg-info">' . $period->sessions->count() . '</span>';
                    })
                    ->addColumn('status_badge', function ($period) {
                        $colors = [
                            'draft' => 'secondary',
                            'in_progress' => 'warning',
                            'completed' => 'success',
                            'cancelled' => 'danger',
                        ];
                        $color = $colors[$period->status] ?? 'secondary';
                        return '<span class="badge bg-' . $color . '">' . ucfirst($period->status) . '</span>';
                    })
                    ->addColumn('actions', function ($period) {
                        $encodedId = $period->encoded_id;
                        $actions = '<div class="btn-group" role="group">';
                        $actions .= '<a href="' . route('inventory.counts.periods.show', $encodedId) . '" class="btn btn-sm btn-info" title="View"><i class="bx bx-show"></i></a>';
                        if ($period->status === 'draft') {
                            $actions .= '<a href="' . route('inventory.counts.sessions.create', $encodedId) . '" class="btn btn-sm btn-primary" title="Create Session"><i class="bx bx-plus"></i></a>';
                        }
                        $actions .= '</div>';
                        return $actions;
                    })
                    ->rawColumns(['period_name_link', 'sessions_count', 'status_badge', 'actions'])
                    ->make(true);
            }

            // Default: return sessions data
            $query = CountSession::with(['period', 'location', 'createdBy'])
                ->forCompany($companyId);

            // Filter by period_id if provided
            if ($request->has('period_id') && $request->period_id) {
                $query->where('count_period_id', $request->period_id);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('session_number_link', function ($session) {
                    return '<a href="' . route('inventory.counts.sessions.show', $session->encoded_id) . '" class="text-primary fw-bold">' . $session->session_number . '</a>';
                })
                ->addColumn('period_name', function ($session) {
                    return $session->period ? $session->period->period_name : 'N/A';
                })
                ->addColumn('location_name', function ($session) {
                    return $session->location ? $session->location->name : 'N/A';
                })
                ->addColumn('status_badge', function ($session) {
                    $colors = [
                        'draft' => 'secondary',
                        'frozen' => 'info',
                        'counting' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    ];
                    $color = $colors[$session->status] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($session->status) . '</span>';
                })
                ->addColumn('snapshot_date_formatted', function ($session) {
                    return $session->snapshot_date ? $session->snapshot_date->format('M d, Y H:i') : 'N/A';
                })
                ->addColumn('created_by_name', function ($session) {
                    return $session->createdBy ? $session->createdBy->name : 'N/A';
                })
                ->addColumn('actions', function ($session) {
                    $encodedId = $session->encoded_id;
                    $actions = '<div class="btn-group" role="group">';
                    $actions .= '<a href="' . route('inventory.counts.sessions.show', $encodedId) . '" class="btn btn-sm btn-info" title="View"><i class="bx bx-show"></i></a>';
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['session_number_link', 'status_badge', 'actions'])
                ->make(true);
        }

        // Get summary statistics
        $totalSessions = CountSession::forCompany($companyId)->count();
        $activeSessions = CountSession::forCompany($companyId)->whereIn('status', ['frozen', 'counting'])->count();
        $completedSessions = CountSession::forCompany($companyId)->where('status', 'completed')->count();
        $pendingAdjustments = CountAdjustment::whereHas('session', function($q) use ($companyId) {
            $q->forCompany($companyId);
        })->where('status', 'pending_approval')->count();

        $locations = InventoryLocation::where('company_id', $companyId)->orderBy('name')->get();

        return view('inventory.counts.index', compact(
            'totalSessions',
            'activeSessions',
            'completedSessions',
            'pendingAdjustments',
            'locations'
        ));
    }

    /**
     * Show the form for creating a new count period
     */
    public function createPeriod()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $branches = \App\Models\Branch::where('company_id', $companyId)->orderBy('name')->get();
        $locations = InventoryLocation::with('branch')->where('company_id', $companyId)->orderBy('name')->get();
        $users = \App\Models\User::where('company_id', $companyId)->orderBy('name')->get();

        return view('inventory.counts.periods.create', compact('branches', 'locations', 'users'));
    }

    /**
     * Store a newly created count period
     */
    public function storePeriod(Request $request)
    {
        $validated = $request->validate([
            'period_name' => 'required|string|max:255',
            'count_type' => 'required|in:cycle,year_end,ad_hoc',
            'frequency' => 'nullable|in:daily,weekly,monthly,quarterly,yearly,ad_hoc',
            'count_start_date' => 'required|date',
            'count_end_date' => 'required|date|after_or_equal:count_start_date',
            'inventory_location_id' => 'nullable|exists:inventory_locations,id',
            'responsible_staff_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $user = Auth::user();
        $validated['company_id'] = $user->company_id;
        $validated['branch_id'] = $request->branch_id ?? session('branch_id') ?? $user->branch_id;
        $validated['status'] = 'draft';

        $period = CountPeriod::create($validated);

        return redirect()->route('inventory.counts.periods.show', $period->encoded_id)
            ->with('success', 'Count period created successfully.');
    }

    /**
     * Show count period details
     */
    public function showPeriod($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $period = CountPeriod::with([
            'sessions.location',
            'sessions.createdBy',
            'location.branch',
            'responsibleStaff',
            'branch'
        ])->findOrFail($id);

        if ($period->company_id != Auth::user()->company_id) {
            abort(403);
        }

        return view('inventory.counts.periods.show', compact('period'));
    }

    /**
     * Create a new count session from a period
     */
    public function createSession(Request $request, $periodEncodedId)
    {
        $periodId = Hashids::decode($periodEncodedId)[0] ?? null;
        if (!$periodId) {
            abort(404);
        }

        $period = CountPeriod::findOrFail($periodId);
        if ($period->company_id != Auth::user()->company_id) {
            abort(403);
        }

        $locations = InventoryLocation::with('branch')->where('company_id', $period->company_id)->orderBy('name')->get();
        $users = \App\Models\User::where('company_id', $period->company_id)->orderBy('name')->get();

        return view('inventory.counts.sessions.create', compact('period', 'locations', 'users'));
    }

    /**
     * Store a newly created count session
     */
    public function storeSession(Request $request, $periodEncodedId)
    {
        $periodId = Hashids::decode($periodEncodedId)[0] ?? null;
        if (!$periodId) {
            abort(404);
        }

        $period = CountPeriod::findOrFail($periodId);
        if ($period->company_id != Auth::user()->company_id) {
            abort(403);
        }

        $validated = $request->validate([
            'inventory_location_id' => 'required|exists:inventory_locations,id',
            'is_blind_count' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Generate session number
            $sessionNumber = 'CNT-' . date('Ymd') . '-' . str_pad(CountSession::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

            $validated['count_period_id'] = $period->id;
            $validated['company_id'] = $period->company_id;
            $validated['session_number'] = $sessionNumber;
            $validated['snapshot_date'] = now();
            $validated['status'] = 'draft';
            $validated['created_by'] = Auth::id();
            $validated['is_blind_count'] = $request->has('is_blind_count');

            $session = CountSession::create($validated);

            // Generate counting sheets (entries) for all items at this location
            $this->generateCountingSheets($session);

            DB::commit();

            return redirect()->route('inventory.counts.sessions.show', $session->encoded_id)
                ->with('success', 'Count session created successfully. Counting sheets generated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create count session: ' . $e->getMessage());
        }
    }

    /**
     * Generate counting sheets for a session
     */
    private function generateCountingSheets(CountSession $session)
    {
        $locationId = $session->inventory_location_id;
        $companyId = $session->company_id;

        // Get all active items that track stock for this company
        $items = Item::where('company_id', $companyId)
            ->where('track_stock', true)
            ->where('is_active', true)
            ->get();

        foreach ($items as $item) {
            // Calculate system quantity from movements (actual stock at this location)
            $systemQuantity = $item->getStockAtLocation($locationId);
            
            // Get StockLevel record for bin location and as fallback for quantity
            $stockLevel = StockLevel::where('item_id', $item->id)
                ->where('inventory_location_id', $locationId)
                ->first();

            // Fallback: If no movements found or quantity is 0, use StockLevel quantity
            if ($systemQuantity == 0 && $stockLevel && $stockLevel->quantity > 0) {
                $systemQuantity = $stockLevel->quantity;
            }

            // When blind count is DISABLED: Include ALL items (even with 0 stock) for verification
            // When blind count is ENABLED: Only include items with actual stock > 0
            if (!$session->is_blind_count || $systemQuantity > 0) {
                // Get bin location if available (could be stored in StockLevel or elsewhere)
                $binLocation = null; // TODO: Add bin_location to StockLevel if needed

            // Create count entry
            CountEntry::create([
                'count_session_id' => $session->id,
                'item_id' => $item->id,
                'inventory_location_id' => $locationId,
                    'bin_location' => $binLocation,
                'system_quantity' => $systemQuantity,
                'status' => 'pending',
            ]);
            }
        }
    }

    /**
     * Show count session details
     */
    public function showSession($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $session = CountSession::with(['period', 'location', 'entries.item', 'teams.user', 'adjustments', 'approval.approver'])
            ->findOrFail($id);

        if ($session->company_id != Auth::user()->company_id) {
            abort(403);
        }

        // Recalculate system quantities for entries that show 0
        foreach ($session->entries as $entry) {
            if ($entry->system_quantity == 0 && $entry->item) {
                $locationId = $entry->inventory_location_id;
                $currentSystemQuantity = $entry->item->getStockAtLocation($locationId);
                
                // Fallback to StockLevel if movements return 0
                if ($currentSystemQuantity == 0) {
                    $stockLevel = StockLevel::where('item_id', $entry->item_id)
                        ->where('inventory_location_id', $locationId)
                        ->first();
                    if ($stockLevel && $stockLevel->quantity > 0) {
                        $currentSystemQuantity = $stockLevel->quantity;
                    }
                }
                
                // Update the entry's system_quantity for display if we found stock
                if ($currentSystemQuantity > 0) {
                    $entry->system_quantity = $currentSystemQuantity;
                }
            }
        }

        // Get summary statistics
        $totalEntries = $session->entries->count();
        $countedEntries = $session->entries->where('status', '!=', 'pending')->count();
        $variances = CountVariance::whereHas('entry', function($q) use ($id) {
            $q->where('count_session_id', $id);
        })->get();

        // Calculate variance statistics
        // Total variances = only non-zero variances (positive + negative), excluding zero variances
        $positiveVariances = $variances->where('variance_type', 'positive')->count();
        $negativeVariances = $variances->where('variance_type', 'negative')->count();
        $zeroVariances = $variances->where('variance_type', 'zero')->count();
        $totalVariances = $positiveVariances + $negativeVariances; // Only count non-zero variances
        $highValueVariances = $variances->where('is_high_value', true)->count();
        
        // Calculate variance values (variance_value is already stored as absolute)
        $totalVarianceValue = $variances->sum('variance_value');
        $totalPositiveValue = $variances->where('variance_type', 'positive')->sum('variance_value');
        $totalNegativeValue = $variances->where('variance_type', 'negative')->sum('variance_value');

        // Get adjustments for this session
        $adjustments = \App\Models\Inventory\CountAdjustment::where('count_session_id', $id)
            ->with(['item', 'approvals', 'journal'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('inventory.counts.sessions.show', compact(
            'session', 
            'totalEntries', 
            'countedEntries', 
            'variances', 
            'adjustments',
            'totalVariances',
            'positiveVariances',
            'negativeVariances',
            'zeroVariances',
            'highValueVariances',
            'totalVarianceValue',
            'totalPositiveValue',
            'totalNegativeValue'
        ));
    }

    /**
     * Freeze stock movements (soft freeze)
     */
    public function freezeSession($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $session = CountSession::findOrFail($id);
        if ($session->company_id != Auth::user()->company_id) {
            abort(403);
        }

        if ($session->status !== 'draft') {
            return back()->with('error', 'Only draft sessions can be frozen.');
        }

        try {
            DB::beginTransaction();

            // Update snapshot date to current time
            $session->snapshot_date = now();
            $session->status = 'frozen';
            $session->count_start_time = now();
            $session->save();

            // Update all entries with current system quantities
            foreach ($session->entries as $entry) {
                $stockLevel = StockLevel::where('item_id', $entry->item_id)
                    ->where('inventory_location_id', $session->inventory_location_id)
                    ->first();

                $systemQuantity = $stockLevel ? $stockLevel->quantity : 0;
                $entry->system_quantity = $systemQuantity;
                $entry->save();
            }

            DB::commit();

            return back()->with('success', 'Count session frozen. Stock movements can continue but will be marked as post-count transactions.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to freeze session: ' . $e->getMessage());
        }
    }

    /**
     * Start counting
     */
    public function startCounting($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $session = CountSession::findOrFail($id);
        if ($session->company_id != Auth::user()->company_id) {
            abort(403);
        }

        if ($session->status !== 'frozen') {
            return back()->with('error', 'Session must be frozen before counting can start.');
        }

        $session->status = 'counting';
        $session->save();

        return back()->with('success', 'Counting started.');
    }

    /**
     * Complete counting
     */
    public function completeCounting($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $session = CountSession::findOrFail($id);
        if ($session->company_id != Auth::user()->company_id) {
            abort(403);
        }

        if ($session->status !== 'counting') {
            return back()->with('error', 'Session must be in counting status.');
        }

        try {
            DB::beginTransaction();

            $session->status = 'completed';
            $session->count_end_time = now();
            $session->save();

            // Calculate variances for all entries
            $this->calculateVariances($session);

            // Detect batch/lot mismatches
            $this->detectBatchLotMismatches($session);

            // Create approval record
            \App\Models\Inventory\CountSessionApproval::updateOrCreate(
                ['count_session_id' => $session->id],
                [
                    'status' => 'pending',
                    'comments' => null,
                ]
            );

            DB::commit();

            return back()->with('success', 'Counting completed. Variances calculated. Awaiting approval.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to complete counting: ' . $e->getMessage());
        }
    }

    /**
     * Approve completed count session
     */
    public function approveCountSession(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $session = CountSession::with('approval')->findOrFail($id);
        if ($session->company_id != Auth::user()->company_id) {
            abort(403);
        }

        if (!$session->isPendingApproval()) {
            return back()->with('error', 'Session is not pending approval.');
        }

        $validated = $request->validate([
            'comments' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $approval = $session->approval;
            if (!$approval) {
                $approval = \App\Models\Inventory\CountSessionApproval::create([
                    'count_session_id' => $session->id,
                    'status' => 'approved',
                    'comments' => $validated['comments'] ?? null,
                    'approved_at' => now(),
                    'approver_id' => Auth::id(),
                ]);
            } else {
                $approval->approve($validated['comments'] ?? null);
            }

            DB::commit();

            return back()->with('success', 'Count session approved successfully. You can now create adjustments.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to approve: ' . $e->getMessage());
        }
    }

    /**
     * Reject completed count session
     */
    public function rejectCountSession(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $session = CountSession::with('approval')->findOrFail($id);
        if ($session->company_id != Auth::user()->company_id) {
            abort(403);
        }

        if (!$session->isPendingApproval()) {
            return back()->with('error', 'Session is not pending approval.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);

        try {
            DB::beginTransaction();

            $approval = $session->approval;
            if (!$approval) {
                $approval = \App\Models\Inventory\CountSessionApproval::create([
                    'count_session_id' => $session->id,
                    'status' => 'rejected',
                    'rejection_reason' => $validated['rejection_reason'],
                    'rejected_at' => now(),
                    'approver_id' => Auth::id(),
                ]);
            } else {
                $approval->reject($validated['rejection_reason']);
            }

            DB::commit();

            return back()->with('success', 'Count session rejected. You may need to recount.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to reject: ' . $e->getMessage());
        }
    }

    /**
     * Get unit cost from cost layers based on costing method (FIFO or AVCO)
     */
    private function getUnitCostFromCostLayers($itemId)
    {
        $costMethod = SystemSetting::where('key', 'inventory_cost_method')->value('value') ?? 'fifo';
        
        if ($costMethod === 'fifo') {
            // FIFO: Use the oldest cost layer's unit_cost
            $oldestLayer = InventoryCostLayer::where('item_id', $itemId)
                ->where('remaining_quantity', '>', 0)
                ->where('is_consumed', false)
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->first();
            
            if ($oldestLayer) {
                return $oldestLayer->unit_cost;
            }
        } else {
            // AVCO/Weighted Average: Calculate weighted average from all available cost layers
            $costLayers = InventoryCostLayer::where('item_id', $itemId)
                ->where('remaining_quantity', '>', 0)
                ->where('is_consumed', false)
                ->get();
            
            $totalQuantity = $costLayers->sum('remaining_quantity');
            $totalValue = $costLayers->sum(function ($layer) {
                return $layer->remaining_quantity * $layer->unit_cost;
            });
            
            if ($totalQuantity > 0) {
                return $totalValue / $totalQuantity;
            }
        }
        
        // Fallback to item's cost_price or average_cost if no cost layers found
        $item = Item::find($itemId);
        return $item->cost_price ?? $item->average_cost ?? 0;
    }

    /**
     * Calculate variances for a session
     */
    private function calculateVariances(CountSession $session)
    {
        foreach ($session->entries as $entry) {
            if ($entry->physical_quantity === null) {
                continue; // Skip entries without physical count
            }

            // Recalculate system quantity from movements to ensure accuracy
            $locationId = $entry->inventory_location_id;
            $systemQuantity = $entry->item->getStockAtLocation($locationId);
            
            // Fallback to StockLevel if movements return 0
            if ($systemQuantity == 0) {
                $stockLevel = StockLevel::where('item_id', $entry->item_id)
                    ->where('inventory_location_id', $locationId)
                    ->first();
                if ($stockLevel && $stockLevel->quantity > 0) {
                    $systemQuantity = $stockLevel->quantity;
                }
            }

            // Update entry's system_quantity if we recalculated it
            if ($entry->system_quantity != $systemQuantity) {
                $entry->system_quantity = $systemQuantity;
                $entry->save();
            }

            $varianceQty = $entry->physical_quantity - $systemQuantity;
            $variancePct = $systemQuantity > 0 
                ? ($varianceQty / $systemQuantity) * 100 
                : ($entry->physical_quantity > 0 ? 100 : 0); // If system is 0 but physical > 0, variance is 100%

            // Clamp variance percentage to fit into DECIMAL(5,2) column range (-999.99 to 999.99)
            $variancePct = round($variancePct, 2);
            $variancePct = max(min($variancePct, 999.99), -999.99);

            // Get unit cost from cost layers (FIFO or AVCO)
            $unitCost = $this->getUnitCostFromCostLayers($entry->item_id);
            $varianceValue = abs($varianceQty * $unitCost);

            $varianceType = 'zero';
            if ($varianceQty > 0) {
                $varianceType = 'positive';
            } elseif ($varianceQty < 0) {
                $varianceType = 'negative';
            }

            // Get variance thresholds from settings
            $varianceValueThreshold = (float) (SystemSetting::where('key', 'inventory_variance_value_threshold')->value('value') ?? 50000);
            $variancePercentageThreshold = (float) (SystemSetting::where('key', 'inventory_variance_percentage_threshold')->value('value') ?? 5);
            
            // Check if high value (using configurable thresholds)
            $isHighValue = $varianceValue >= $varianceValueThreshold || abs($variancePct) >= $variancePercentageThreshold;
            $requiresRecount = $isHighValue;

            CountVariance::updateOrCreate(
                ['count_entry_id' => $entry->id],
                [
                    'item_id' => $entry->item_id,
                    'system_quantity' => $systemQuantity, // Use recalculated value
                    'physical_quantity' => $entry->physical_quantity,
                    'variance_quantity' => $varianceQty,
                    'variance_percentage' => $variancePct,
                    'unit_cost' => $unitCost,
                    'variance_value' => $varianceValue,
                    'variance_type' => $varianceType,
                    'is_high_value' => $isHighValue,
                    'requires_recount' => $requiresRecount,
                    'recount_tolerance_percentage' => $variancePercentageThreshold,
                    'recount_tolerance_value' => $varianceValueThreshold,
                    'status' => $requiresRecount ? 'pending' : 'resolved',
                ]
            );
        }
    }

    /**
     * Export counting sheets to PDF
     */
    public function exportCountingSheetsPdf($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $session = CountSession::with(['entries.item', 'location'])->findOrFail($id);
        if ($session->company_id != Auth::user()->company_id) {
            abort(403);
        }

        $pdf = Pdf::loadView('inventory.counts.sessions.export.counting-sheets-pdf', compact('session'));
        return $pdf->download('counting-sheets-' . $session->session_number . '.pdf');
    }

    /**
     * Export counting sheets to Excel
     */
    public function exportCountingSheetsExcel($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $session = CountSession::with(['entries.item', 'location'])->findOrFail($id);
        if ($session->company_id != Auth::user()->company_id) {
            abort(403);
        }

        return Excel::download(new \App\Exports\InventoryCountSheetsExport($session), 'counting-sheets-' . $session->session_number . '.xlsx');
    }

    /**
     * Update physical quantity for an entry
     */
    public function updatePhysicalQuantity(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Invalid entry ID'], 404);
        }

        $entry = CountEntry::with('session')->findOrFail($id);
        if ($entry->session->company_id != Auth::user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (!in_array($entry->session->status, ['frozen', 'counting'])) {
            return response()->json(['success' => false, 'message' => 'Session is not in counting mode'], 400);
        }

        $validated = $request->validate([
            'physical_quantity' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $entry->physical_quantity = $validated['physical_quantity'];
            $entry->counted_by = Auth::id();
            $entry->counted_at = now();
            $entry->status = 'counted';
            $entry->save();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Physical quantity updated']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to update: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show entry details
     */
    public function showEntry($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $entry = CountEntry::with(['item', 'location', 'session', 'variance', 'countedBy', 'recountedBy', 'verifiedBy'])
            ->findOrFail($id);

        if ($entry->session->company_id != Auth::user()->company_id) {
            abort(403);
        }

        // Recalculate system quantity from movements (current actual stock at snapshot time)
        $locationId = $entry->inventory_location_id;
        $currentSystemQuantity = $entry->item->getStockAtLocation($locationId);
        
        // Fallback to StockLevel if movements return 0
        if ($currentSystemQuantity == 0) {
            $stockLevel = StockLevel::where('item_id', $entry->item_id)
                ->where('inventory_location_id', $locationId)
                ->first();
            if ($stockLevel && $stockLevel->quantity > 0) {
                $currentSystemQuantity = $stockLevel->quantity;
            }
        }
        
        // If stored system_quantity is 0 but we found stock, update it for display
        // This ensures we show the actual current stock, not 0
        if ($entry->system_quantity == 0 && $currentSystemQuantity > 0) {
            $entry->system_quantity = $currentSystemQuantity;
        } elseif ($currentSystemQuantity > 0) {
            // Always show the most current stock calculation
            $entry->system_quantity = $currentSystemQuantity;
        }

        return view('inventory.counts.entries.show', compact('entry'))->render();
    }

    /**
     * Request recount for an entry
     */
    public function requestRecount(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Invalid entry ID'], 404);
        }

        $entry = CountEntry::with('session')->findOrFail($id);
        if ($entry->session->company_id != Auth::user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'recount_quantity' => 'required|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $entry->recount_quantity = $validated['recount_quantity'];
            $entry->physical_quantity = $validated['recount_quantity']; // Update physical quantity with recount
            $entry->recounted_by = Auth::id();
            $entry->recounted_at = now();
            $entry->status = 'recounted';
            if ($validated['remarks']) {
                $entry->remarks = ($entry->remarks ? $entry->remarks . "\n" : '') . 'Recount: ' . $validated['remarks'];
            }
            $entry->save();

            // Update variance if exists
            if ($entry->variance) {
                $variance = $entry->variance;
                $varianceQty = $entry->physical_quantity - $entry->system_quantity;
                $variancePct = $entry->system_quantity > 0 
                    ? ($varianceQty / $entry->system_quantity) * 100 
                    : 0;

                // Clamp variance percentage to fit into DECIMAL(5,2) column range (-999.99 to 999.99)
                $variancePct = round($variancePct, 2);
                $variancePct = max(min($variancePct, 999.99), -999.99);
                // Get unit cost from cost layers (FIFO or AVCO)
                $unitCost = $this->getUnitCostFromCostLayers($entry->item_id);
                $varianceValue = abs($varianceQty * $unitCost);

                $varianceType = 'zero';
                if ($varianceQty > 0) {
                    $varianceType = 'positive';
                } elseif ($varianceQty < 0) {
                    $varianceType = 'negative';
                }

                // Get variance thresholds from settings
                $varianceValueThreshold = (float) (SystemSetting::where('key', 'inventory_variance_value_threshold')->value('value') ?? 50000);
                $variancePercentageThreshold = (float) (SystemSetting::where('key', 'inventory_variance_percentage_threshold')->value('value') ?? 5);

                $variance->update([
                    'physical_quantity' => $entry->physical_quantity,
                    'variance_quantity' => $varianceQty,
                    'variance_percentage' => $variancePct,
                    'variance_value' => $varianceValue,
                    'variance_type' => $varianceType,
                    'is_high_value' => $varianceValue >= $varianceValueThreshold || abs($variancePct) >= $variancePercentageThreshold,
                ]);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Recount recorded']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to record recount: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Verify an entry
     */
    public function verifyEntry(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Invalid entry ID'], 404);
        }

        $entry = CountEntry::with('session')->findOrFail($id);
        if ($entry->session->company_id != Auth::user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            DB::beginTransaction();

            $entry->verified_by = Auth::id();
            $entry->verified_at = now();
            $entry->status = 'verified';
            $entry->save();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Entry verified']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to verify: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show variance investigation page
     */
    public function showVariances($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $session = CountSession::with(['entries.item', 'location'])->findOrFail($id);
        if ($session->company_id != Auth::user()->company_id) {
            abort(403);
        }

        $variances = CountVariance::whereHas('entry', function($q) use ($id) {
            $q->where('count_session_id', $id);
        })->with(['entry.item', 'entry.location'])->get();

        return view('inventory.counts.sessions.variances', compact('session', 'variances'));
    }

    /**
     * Update variance investigation
     */
    public function updateVarianceInvestigation(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Invalid variance ID'], 404);
        }

        $variance = CountVariance::with('entry.session')->findOrFail($id);
        if ($variance->entry->session->company_id != Auth::user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'investigation_notes' => 'nullable|string',
            'status' => 'required|in:pending,investigating,resolved,approved',
        ]);

        try {
            $variance->update($validated);
            return response()->json(['success' => true, 'message' => 'Variance investigation updated']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show form to create adjustment from variance
     */
    public function createAdjustmentForm($varianceId)
    {
        $variance = CountVariance::with(['entry.session', 'entry.item', 'entry.location'])->findOrFail($varianceId);
        if ($variance->entry->session->company_id != Auth::user()->company_id) {
            abort(403);
        }

        // Check if session is approved
        if (!$variance->entry->session->isApproved()) {
            return back()->with('error', 'Count session must be approved before creating adjustments.');
        }

        return view('inventory.counts.adjustments.create', compact('variance'));
    }

    /**
     * Create adjustment from variance
     */
    public function createAdjustment(Request $request, $varianceId)
    {
        $variance = CountVariance::with(['entry.session', 'entry.item', 'entry.location'])->findOrFail($varianceId);
        if ($variance->entry->session->company_id != Auth::user()->company_id) {
            abort(403);
        }

        // Check if session is approved
        if (!$variance->entry->session->isApproved()) {
            return back()->with('error', 'Count session must be approved before creating adjustments.');
        }

        $validated = $request->validate([
            'reason_code' => 'required|in:wrong_posting,theft,damage,expired,unrecorded_issue,unrecorded_receipt',
            'reason_description' => 'required|string',
            'supervisor_comments' => 'nullable|string',
            'finance_comments' => 'nullable|string',
            'supporting_documents' => 'nullable|array',
            'supporting_documents.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // Handle file uploads
        $supportingDocs = [];
        if ($request->hasFile('supporting_documents')) {
            foreach ($request->file('supporting_documents') as $file) {
                $path = $file->store('inventory_count_adjustments', 'public');
                $supportingDocs[] = $path;
            }
        }

        try {
            DB::beginTransaction();

            // Generate adjustment number
            $adjustmentNumber = 'ADJ-' . date('Ymd') . '-' . str_pad(CountAdjustment::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

            $adjustment = CountAdjustment::create([
                'count_session_id' => $variance->entry->session->id,
                'variance_id' => $variance->id,
                'item_id' => $variance->item_id,
                'inventory_location_id' => $variance->entry->inventory_location_id,
                'adjustment_number' => $adjustmentNumber,
                'adjustment_quantity' => $variance->variance_quantity,
                'adjustment_value' => abs($variance->variance_value),
                'adjustment_type' => $variance->variance_type === 'positive' ? 'surplus' : 'shortage',
                'reason_code' => $validated['reason_code'],
                'reason_description' => $validated['reason_description'],
                'supervisor_comments' => $validated['supervisor_comments'] ?? null,
                'finance_comments' => $validated['finance_comments'] ?? null,
                'supporting_documents' => !empty($supportingDocs) ? $supportingDocs : null,
                'status' => 'pending_approval',
                'created_by' => Auth::id(),
            ]);

            // Create multi-level approval records using settings from inventory settings
            $approvalSettings = \App\Models\Inventory\CountSessionApprovalSetting::getForCompany($variance->entry->session->company_id);
            $approvalLevels = (int) ($approvalSettings->approval_levels ?? 1);

            // Only create approval records for configured levels
            for ($level = 1; $level <= $approvalLevels; $level++) {
                // Check if this level is configured (has approval type set)
                $approvalType = $approvalSettings->getApprovalTypeForLevel($level);
                if (empty($approvalType) && $level > 1) {
                    // Skip unconfigured levels beyond level 1
                    break;
                }

                $levelName = $approvalSettings->getLevelName($level);
                if (empty($levelName)) {
                    $levelName = "Level {$level}";
                }

                CountAdjustmentApproval::create([
                    'count_adjustment_id' => $adjustment->id,
                    'approval_level' => $level,
                    'level_name' => $levelName,
                    'status' => 'pending',
                ]);
            }

            DB::commit();

            return redirect()->route('inventory.counts.sessions.show', $variance->entry->session->encoded_id)
                ->with('success', 'Adjustment created successfully. Awaiting approval.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create adjustment: ' . $e->getMessage());
        }
    }

    /**
     * Bulk create adjustments from multiple variances
     */
    public function bulkCreateAdjustments(Request $request, $encodedId)
    {
        $sessionId = Hashids::decode($encodedId)[0] ?? null;
        if (!$sessionId) {
            abort(404);
        }

        $session = CountSession::findOrFail($sessionId);
        if ($session->company_id != Auth::user()->company_id) {
            abort(403);
        }

        // Check if session is approved
        if (!$session->isApproved()) {
            return back()->with('error', 'Count session must be approved before creating adjustments.');
        }

        $validated = $request->validate([
            'variance_ids' => 'required|array|min:1',
            'variance_ids.*' => 'required|exists:inventory_count_variances,id',
            'reason_code' => 'required|in:wrong_posting,theft,damage,expired,unrecorded_issue,unrecorded_receipt',
            'reason_description' => 'required|string',
            'supervisor_comments' => 'nullable|string',
            'finance_comments' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $variances = CountVariance::whereIn('id', $validated['variance_ids'])
                ->with(['entry.session', 'entry.item', 'entry.location'])
                ->get();

            // Verify all variances belong to the same session
            foreach ($variances as $variance) {
                if ($variance->entry->session->id != $sessionId) {
                    throw new \Exception('All variances must belong to the same count session.');
                }
                if ($variance->adjustment) {
                    throw new \Exception('One or more variances already have adjustments.');
                }
            }

            $createdAdjustments = [];
            $baseAdjustmentNumber = CountAdjustment::whereDate('created_at', today())->count();

            foreach ($variances as $index => $variance) {
                $adjustmentNumber = 'ADJ-' . date('Ymd') . '-' . str_pad($baseAdjustmentNumber + $index + 1, 4, '0', STR_PAD_LEFT);

                $adjustment = CountAdjustment::create([
                    'count_session_id' => $sessionId,
                    'variance_id' => $variance->id,
                    'item_id' => $variance->item_id,
                    'inventory_location_id' => $variance->entry->inventory_location_id,
                    'adjustment_number' => $adjustmentNumber,
                    'adjustment_quantity' => $variance->variance_quantity,
                    'adjustment_value' => abs($variance->variance_value),
                    'adjustment_type' => $variance->variance_type === 'positive' ? 'surplus' : 'shortage',
                    'reason_code' => $validated['reason_code'],
                    'reason_description' => $validated['reason_description'],
                    'supervisor_comments' => $validated['supervisor_comments'] ?? null,
                    'finance_comments' => $validated['finance_comments'] ?? null,
                    'status' => 'pending_approval',
                    'created_by' => Auth::id(),
                ]);

                // Create multi-level approval records using settings from inventory settings
                $approvalSettings = \App\Models\Inventory\CountSessionApprovalSetting::getForCompany($session->company_id);
                $approvalLevels = (int) ($approvalSettings->approval_levels ?? 1);

                // Only create approval records for configured levels
                for ($level = 1; $level <= $approvalLevels; $level++) {
                    // Check if this level is configured (has approval type set)
                    $approvalType = $approvalSettings->getApprovalTypeForLevel($level);
                    if (empty($approvalType) && $level > 1) {
                        // Skip unconfigured levels beyond level 1
                        break;
                    }

                    $levelName = $approvalSettings->getLevelName($level);
                    if (empty($levelName)) {
                        $levelName = "Level {$level}";
                    }

                    CountAdjustmentApproval::create([
                        'count_adjustment_id' => $adjustment->id,
                        'approval_level' => $level,
                        'level_name' => $levelName,
                        'status' => 'pending',
                    ]);
                }

                $createdAdjustments[] = $adjustment;
            }

            DB::commit();

            return redirect()->route('inventory.counts.sessions.show', $session->encoded_id)
                ->with('success', count($createdAdjustments) . ' adjustment(s) created successfully. Awaiting approval.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create adjustments: ' . $e->getMessage());
        }
    }

    /**
     * Bulk post adjustments to GL
     */
    public function bulkPostAdjustmentsToGL(Request $request, $encodedId)
    {
        $sessionId = Hashids::decode($encodedId)[0] ?? null;
        if (!$sessionId) {
            abort(404);
        }

        $session = CountSession::findOrFail($sessionId);
        if ($session->company_id != Auth::user()->company_id) {
            abort(403);
        }

        // Handle adjustment_ids - it might come as JSON string or array
        $adjustmentIds = $request->input('adjustment_ids');
        if (is_string($adjustmentIds)) {
            // Try to decode JSON string
            $decoded = json_decode($adjustmentIds, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $request->merge(['adjustment_ids' => $decoded]);
            } else {
                // If not JSON, try to split by comma or use as single value
                $request->merge(['adjustment_ids' => [$adjustmentIds]]);
            }
        }

        $validated = $request->validate([
            'adjustment_ids' => 'required|array|min:1',
            'adjustment_ids.*' => 'required|exists:inventory_count_adjustments,id',
        ]);

        try {
            DB::beginTransaction();

            $adjustments = CountAdjustment::whereIn('id', $validated['adjustment_ids'])
                ->with(['session', 'item', 'location', 'approvals'])
                ->get();

            // Verify all adjustments belong to the same session and are approved
            foreach ($adjustments as $adjustment) {
                if ($adjustment->count_session_id != $sessionId) {
                    throw new \Exception('All adjustments must belong to the same count session.');
                }
                if ($adjustment->status !== 'approved') {
                    throw new \Exception('All adjustments must be approved before posting to GL.');
                }
                if (!$adjustment->isFullyApproved()) {
                    throw new \Exception('All approval levels must be completed for all adjustments.');
                }
                if ($adjustment->journal_id) {
                    throw new \Exception('One or more adjustments have already been posted.');
                }
            }

            $user = Auth::user();
            $branchId = $session->period->branch_id ?? session('branch_id') ?? $user->branch_id;

            // Get inventory account from settings
            $inventoryAccountId = SystemSetting::where('key', 'inventory_default_inventory_account')->value('value');
            $inventoryAccount = $inventoryAccountId ? ChartAccount::find($inventoryAccountId) : null;

            if (!$inventoryAccount) {
                throw new \Exception('Inventory account not found. Please configure it in Inventory Settings.');
            }

            $lossAccountId = SystemSetting::where('key', 'inventory_loss_expense_account')->value('value');
            $gainAccountId = SystemSetting::where('key', 'inventory_gain_income_account')->value('value');
            
            $lossAccount = $lossAccountId ? ChartAccount::find($lossAccountId) : null;
            $gainAccount = $gainAccountId ? ChartAccount::find($gainAccountId) : null;
            
            if (!$lossAccount) {
                $lossAccount = ChartAccount::where('account_name', 'LIKE', '%inventory loss%')
                    ->orWhere('account_name', 'LIKE', '%stock loss%')
                    ->orWhere('account_name', 'LIKE', '%Inventory Loss Expense%')
                    ->first() ?? $inventoryAccount;
            }

            if (!$gainAccount) {
                $gainAccount = ChartAccount::where('account_name', 'LIKE', '%inventory gain%')
                    ->orWhere('account_name', 'LIKE', '%stock gain%')
                    ->orWhere('account_name', 'LIKE', '%Inventory Gain Income%')
                    ->first() ?? $inventoryAccount;
            }

            $costService = new InventoryCostService();
            $postedCount = 0;
            $errors = [];

            // Group adjustments by type for consolidated journal entry
            $shortages = [];
            $surpluses = [];
            $totalShortageValue = 0;
            $totalSurplusValue = 0;
            
            // Track items with both loss and gain to detect conflicts
            $itemsWithBothTypes = [];
            $itemAdjustments = [];
            
            // First pass: Check for items with both types
            foreach ($adjustments as $adjustment) {
                $itemKey = $adjustment->item_id . '_' . $adjustment->inventory_location_id;
                if (!isset($itemAdjustments[$itemKey])) {
                    $itemAdjustments[$itemKey] = [
                        'item_id' => $adjustment->item_id,
                        'location_id' => $adjustment->inventory_location_id,
                        'has_shortage' => false,
                        'has_surplus' => false,
                        'adjustments' => []
                    ];
                }
                
                if ($adjustment->adjustment_type === 'shortage') {
                    $itemAdjustments[$itemKey]['has_shortage'] = true;
                } else {
                    $itemAdjustments[$itemKey]['has_surplus'] = true;
                }
                
                $itemAdjustments[$itemKey]['adjustments'][] = [
                    'id' => $adjustment->id,
                    'number' => $adjustment->adjustment_number,
                    'type' => $adjustment->adjustment_type,
                    'quantity' => $adjustment->adjustment_quantity
                ];
                
                // Check if this item has both types
                if ($itemAdjustments[$itemKey]['has_shortage'] && $itemAdjustments[$itemKey]['has_surplus']) {
                    $itemsWithBothTypes[$itemKey] = $itemAdjustments[$itemKey];
                }
            }
            
            // Log items with both loss and gain
            if (!empty($itemsWithBothTypes)) {
                Log::warning('Bulk Post - Items with Both Loss and Gain', [
                    'items_count' => count($itemsWithBothTypes),
                    'items' => array_values($itemsWithBothTypes)
                ]);
            }
            
            // Second pass: Process adjustments
            foreach ($adjustments as $adjustment) {
                try {
                    $locationId = $adjustment->inventory_location_id;
                    $adjustmentQuantity = abs($adjustment->adjustment_quantity);
                    
                    // Skip adjustments with zero quantity
                    if ($adjustmentQuantity == 0) {
                        Log::warning('Bulk Post - Skipping adjustment with zero quantity', [
                            'adjustment_id' => $adjustment->id,
                            'adjustment_number' => $adjustment->adjustment_number,
                            'adjustment_value' => $adjustment->adjustment_value
                        ]);
                        $errors[] = "Adjustment {$adjustment->adjustment_number} has zero quantity and was skipped.";
                        continue;
                    }
                    
                    $unitCost = $adjustment->adjustment_value / $adjustmentQuantity;
                    $actualCost = $adjustment->adjustment_value;

                    // Update cost layers
                    if ($adjustment->adjustment_type === 'shortage') {
                        $costInfo = $costService->removeInventory(
                            $adjustment->item_id,
                            $adjustmentQuantity,
                            'adjustment_out',
                            $adjustment->adjustment_number,
                            now()->toDateString()
                        );
                        $actualCost = $costInfo['total_cost'];
                        $unitCost = $costInfo['average_unit_cost'] ?? ($adjustmentQuantity > 0 ? ($actualCost / $adjustmentQuantity) : 0);
                        $shortages[] = ['adjustment' => $adjustment, 'cost' => $actualCost, 'qty' => $adjustmentQuantity];
                        $totalShortageValue += $actualCost;
                        
                        Log::info('Bulk Post - Processing Shortage', [
                            'adjustment_id' => $adjustment->id,
                            'adjustment_number' => $adjustment->adjustment_number,
                            'quantity' => $adjustmentQuantity,
                            'actual_cost' => $actualCost,
                            'total_shortage_value' => $totalShortageValue
                        ]);
                    } else {
                        $costService->addInventory(
                            $adjustment->item_id,
                            $adjustmentQuantity,
                            $unitCost,
                            'adjustment_in',
                            $adjustment->adjustment_number,
                            now()->toDateString()
                        );
                        $surpluses[] = ['adjustment' => $adjustment, 'cost' => $actualCost, 'qty' => $adjustmentQuantity];
                        $totalSurplusValue += $actualCost;
                    }

                    // Get current stock balance before adjustment
                    $stockService = new InventoryStockService();
                    $balanceBefore = $stockService->getItemStockAtLocation($adjustment->item_id, $locationId);
                    
                    // Calculate balance after adjustment
                    $balanceAfter = $balanceBefore;
                    if ($adjustment->adjustment_type === 'shortage') {
                        $balanceAfter = $balanceBefore - $adjustmentQuantity;
                    } else {
                        $balanceAfter = $balanceBefore + $adjustmentQuantity;
                    }
                    
                    // Create movement - IMPORTANT: This creates the movement record that reduces inventory qty
                    $movementType = $adjustment->adjustment_type === 'shortage' ? 'adjustment_out' : 'adjustment_in';
                    $movement = Movement::create([
                        'branch_id' => $branchId,
                        'location_id' => $locationId,
                        'item_id' => $adjustment->item_id,
                        'user_id' => $user->id,
                        'movement_type' => $movementType,
                        'quantity' => $adjustmentQuantity,
                        'unit_cost' => $unitCost,
                        'total_cost' => $actualCost,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                        'reference_type' => 'inventory_count_adjustment',
                        'reference_id' => $adjustment->id,
                        'reference_number' => $adjustment->adjustment_number,
                        'reason' => $adjustment->reason_description,
                        'movement_date' => now(),
                    ]);
                    
                    Log::info('Bulk Post - Movement Created', [
                        'adjustment_id' => $adjustment->id,
                        'adjustment_type' => $adjustment->adjustment_type,
                        'movement_id' => $movement->id,
                        'movement_type' => $movementType,
                        'quantity' => $adjustmentQuantity
                    ]);

                    // Update stock level
                    $stockLevel = StockLevel::where('item_id', $adjustment->item_id)
                        ->where('inventory_location_id', $locationId)
                        ->first();

                    if ($stockLevel) {
                        if ($adjustment->adjustment_type === 'shortage') {
                            $stockLevel->quantity -= $adjustmentQuantity;
                        } else {
                            $stockLevel->quantity += $adjustmentQuantity;
                        }
                        $stockLevel->save();
                    } else {
                        if ($adjustment->adjustment_type === 'surplus') {
                            StockLevel::create([
                                'item_id' => $adjustment->item_id,
                                'inventory_location_id' => $locationId,
                                'quantity' => $adjustmentQuantity,
                            ]);
                        }
                    }

                    // Store for journal entry creation
                    $adjustment->movement_id = $movement->id;
                    $adjustment->save();

                } catch (\Exception $e) {
                    $errors[] = "Adjustment {$adjustment->adjustment_number}: " . $e->getMessage();
                }
            }

            // Create consolidated journal entry for all adjustments
            Log::info('Bulk Post - Summary Before Journal Creation', [
                'shortages_count' => count($shortages),
                'surpluses_count' => count($surpluses),
                'total_shortage_value' => $totalShortageValue,
                'total_surplus_value' => $totalSurplusValue,
                'total_adjustments' => $adjustments->count(),
                'items_with_both_types' => count($itemsWithBothTypes),
                'items_with_both_types_details' => !empty($itemsWithBothTypes) ? array_values($itemsWithBothTypes) : []
            ]);
            
            if (count($shortages) > 0 || count($surpluses) > 0) {
                $nextId = Journal::max('id') + 1;
                $reference = 'BULK-ADJ-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

                $journal = Journal::create([
                    'date' => now()->toDateString(),
                    'description' => 'Bulk Inventory Count Adjustments - ' . count($adjustments) . ' item(s)',
                    'branch_id' => $branchId,
                    'user_id' => $user->id,
                    'reference_type' => 'Inventory Count Adjustment (Bulk)',
                    'reference' => $reference,
                ]);
                
                Log::info('Bulk Post - Journal Created', [
                    'journal_id' => $journal->id,
                    'reference' => $reference
                ]);

                // Post shortages
                if ($totalShortageValue > 0) {
                    Log::info('Bulk Post - Creating Shortage Journal Items', [
                        'journal_id' => $journal->id,
                        'total_shortage_value' => $totalShortageValue,
                        'shortages_count' => count($shortages),
                        'loss_account_id' => $lossAccount->id,
                        'inventory_account_id' => $inventoryAccount->id
                    ]);
                    
                    JournalItem::create([
                        'journal_id' => $journal->id,
                        'chart_account_id' => $lossAccount->id,
                        'amount' => $totalShortageValue,
                        'nature' => 'debit',
                        'description' => 'Inventory Shortages - ' . count($shortages) . ' item(s)',
                    ]);

                    JournalItem::create([
                        'journal_id' => $journal->id,
                        'chart_account_id' => $inventoryAccount->id,
                        'amount' => $totalShortageValue,
                        'nature' => 'credit',
                        'description' => 'Inventory Shortages - ' . count($shortages) . ' item(s)',
                    ]);
                } else {
                    Log::warning('Bulk Post - No Shortage Value', [
                        'total_shortage_value' => $totalShortageValue,
                        'shortages_count' => count($shortages),
                        'adjustments_count' => $adjustments->count()
                    ]);
                }

                // Post surpluses
                if ($totalSurplusValue > 0) {
                    JournalItem::create([
                        'journal_id' => $journal->id,
                        'chart_account_id' => $inventoryAccount->id,
                        'amount' => $totalSurplusValue,
                        'nature' => 'debit',
                        'description' => 'Inventory Surpluses - ' . count($surpluses) . ' item(s)',
                    ]);

                    JournalItem::create([
                        'journal_id' => $journal->id,
                        'chart_account_id' => $gainAccount->id,
                        'amount' => $totalSurplusValue,
                        'nature' => 'credit',
                        'description' => 'Inventory Surpluses - ' . count($surpluses) . ' item(s)',
                    ]);
                }

                // Update all adjustments with journal reference
                foreach ($adjustments as $adjustment) {
                    $adjustment->status = 'posted';
                    $adjustment->posted_by = $user->id;
                    $adjustment->posted_at = now();
                    $adjustment->journal_id = $journal->id;
                    $adjustment->save();
                    $postedCount++;
                }

                // Reload journal with items before creating GL transactions
                $journal->load('items');
                
                // Initialize journal approval workflow
                $journal->initializeApprovalWorkflow();

                // Create GL transactions from journal items
                $journal->createGlTransactions();
            }

            DB::commit();

            $message = "Successfully posted {$postedCount} adjustment(s) to GL.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode('; ', $errors);
            }

            return redirect()->route('inventory.counts.sessions.show', $session->encoded_id)
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to post adjustments: ' . $e->getMessage());
        }
    }

    /**
     * Show adjustments for a count session
     */
    public function showAdjustments($encodedId)
    {
        $sessionId = Hashids::decode($encodedId)[0] ?? null;
        if (!$sessionId) {
            abort(404);
        }

        $session = CountSession::with(['period', 'location', 'approval.approver'])
            ->findOrFail($sessionId);

        if ($session->company_id != Auth::user()->company_id) {
            abort(403);
        }

        // Get adjustments for this session
        $adjustments = \App\Models\Inventory\CountAdjustment::where('count_session_id', $sessionId)
            ->with(['item', 'approvals', 'journal', 'createdBy', 'approvedBy', 'postedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get approval settings
        $approvalSettings = \App\Models\Inventory\CountSessionApprovalSetting::getForCompany($session->company_id);
        $user = Auth::user();

        // Check which adjustments the user can approve
        $adjustmentsWithPermission = [];
        $canBulkApprove = false;
        foreach ($adjustments as $adjustment) {
            if ($adjustment->status === 'pending_approval') {
                $currentLevel = $adjustment->getCurrentApprovalLevel();
                if ($currentLevel && $approvalSettings->canUserApproveAtLevel($user, $currentLevel)) {
                    $adjustmentsWithPermission[] = $adjustment->id;
                    $canBulkApprove = true;
                }
            }
        }

        // Calculate statistics
        $totalAdjustments = $adjustments->count();
        $pendingApproval = $adjustments->where('status', 'pending_approval')->count();
        $approved = $adjustments->where('status', 'approved')->count();
        $posted = $adjustments->where('status', 'posted')->count();
        $rejected = $adjustments->where('status', 'rejected')->count();
        
        $totalValue = $adjustments->sum('adjustment_value');
        $shortages = $adjustments->where('adjustment_type', 'shortage');
        $surpluses = $adjustments->where('adjustment_type', 'surplus');
        $totalShortageValue = $shortages->sum('adjustment_value');
        $totalSurplusValue = $surpluses->sum('adjustment_value');

        return view('inventory.counts.sessions.adjustments', compact(
            'session',
            'adjustments',
            'totalAdjustments',
            'pendingApproval',
            'approved',
            'posted',
            'rejected',
            'totalValue',
            'totalShortageValue',
            'totalSurplusValue',
            'approvalSettings',
            'canBulkApprove',
            'adjustmentsWithPermission'
        ));
    }

    /**
     * Show adjustment details
     */
    public function showAdjustment($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $adjustment = CountAdjustment::with(['session', 'variance', 'item', 'location', 'createdBy', 'approvedBy', 'postedBy', 'approvals.approver'])
            ->findOrFail($id);

        if ($adjustment->session->company_id != Auth::user()->company_id) {
            abort(403);
        }

        // Get current approval level
        $currentApprovalLevel = $adjustment->getCurrentApprovalLevel();
        $currentApproval = $currentApprovalLevel 
            ? $adjustment->approvals()->where('approval_level', $currentApprovalLevel)->first()
            : null;

        return view('inventory.counts.adjustments.show', compact('adjustment', 'currentApprovalLevel', 'currentApproval'));
    }

    /**
     * Approve adjustment (multi-level)
     */
    public function approveAdjustment(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $adjustment = CountAdjustment::with(['session', 'approvals'])->findOrFail($id);
        if ($adjustment->session->company_id != Auth::user()->company_id) {
            abort(403);
        }

        if ($adjustment->status !== 'pending_approval') {
            return back()->with('error', 'Adjustment is not pending approval.');
        }

        $validated = $request->validate([
            'comments' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Ensure session and approvals are loaded
            if (!$adjustment->relationLoaded('session')) {
                $adjustment->load('session');
            }
            if (!$adjustment->relationLoaded('approvals')) {
                $adjustment->load('approvals');
            }

            // Get approval settings
            $approvalSettings = \App\Models\Inventory\CountSessionApprovalSetting::getForCompany($adjustment->session->company_id);
            $configuredLevels = (int) ($approvalSettings->approval_levels ?? 1);
            $user = Auth::user();

            // Debug logging
            \Log::info('Approval Attempt', [
                'adjustment_id' => $adjustment->id,
                'adjustment_number' => $adjustment->adjustment_number,
                'status' => $adjustment->status,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'configured_levels' => $configuredLevels,
                'all_approvals' => $adjustment->approvals->map(function($a) {
                    return [
                        'level' => $a->approval_level,
                        'status' => $a->status,
                        'level_name' => $a->level_name
                    ];
                })->toArray()
            ]);

            // Get current approval level (only within configured levels)
            $currentLevel = $adjustment->getCurrentApprovalLevel();
            
            \Log::info('Current Approval Level', [
                'current_level' => $currentLevel,
                'pending_approvals_count' => $adjustment->approvals()->where('status', 'pending')->count(),
                'pending_within_configured' => $adjustment->approvals()->where('status', 'pending')->where('approval_level', '<=', $configuredLevels)->count()
            ]);

            if (!$currentLevel) {
                // Check if there are any pending approvals at all
                $allPending = $adjustment->approvals()->where('status', 'pending')->count();
                $pendingWithinConfigured = $adjustment->approvals()->where('status', 'pending')->where('approval_level', '<=', $configuredLevels)->count();
                
                \Log::warning('No Current Approval Level Found', [
                    'all_pending' => $allPending,
                    'pending_within_configured' => $pendingWithinConfigured,
                    'configured_levels' => $configuredLevels
                ]);
                
                if ($allPending > 0 && $pendingWithinConfigured == 0) {
                    return back()->with('error', "Found {$allPending} pending approval(s) but none within configured {$configuredLevels} level(s). Please check your approval settings.");
                }
                return back()->with('error', 'No pending approval level found.');
            }

            // Check if user can approve at this level based on settings
            $canApprove = $approvalSettings->canUserApproveAtLevel($user, $currentLevel);
            
            \Log::info('Permission Check', [
                'current_level' => $currentLevel,
                'can_approve' => $canApprove,
                'approval_type' => $approvalSettings->getApprovalTypeForLevel($currentLevel),
                'approvers' => $approvalSettings->getApproversForLevel($currentLevel)
            ]);
            
            if (!$canApprove) {
                return back()->with('error', 'You do not have permission to approve at level ' . $currentLevel . '. Please check your approval settings.');
            }

            // Approve current level - ensure we're getting the right one within configured levels
            $approval = $adjustment->approvals()
                ->where('approval_level', $currentLevel)
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                \Log::warning('Approval Record Not Found', [
                    'current_level' => $currentLevel,
                    'all_approvals_at_level' => $adjustment->approvals()->where('approval_level', $currentLevel)->get()->map(function($a) {
                        return ['id' => $a->id, 'status' => $a->status];
                    })->toArray()
                ]);
                return back()->with('error', 'Approval level ' . $currentLevel . ' not found or already processed.');
            }

            $approval->approve($validated['comments'] ?? null);
            $approval->approver_id = Auth::id();
            $approval->save();

            \Log::info('Approval Record Updated', [
                'approval_id' => $approval->id,
                'approval_level' => $approval->approval_level,
                'new_status' => $approval->status
            ]);

            // Reload approvals to get fresh data
            $adjustment->load('approvals');
            
            // Check if all approvals are complete
            $isFullyApproved = $adjustment->isFullyApproved();
            
            \Log::info('Fully Approved Check', [
                'is_fully_approved' => $isFullyApproved,
                'configured_levels' => $configuredLevels,
                'approved_count' => $adjustment->approvals()->where('approval_level', '<=', $configuredLevels)->where('status', 'approved')->count(),
                'total_relevant' => $adjustment->approvals()->where('approval_level', '<=', $configuredLevels)->count()
            ]);
            
            if ($isFullyApproved) {
            $adjustment->status = 'approved';
            $adjustment->approved_by = Auth::id();
            $adjustment->approved_at = now();
            $adjustment->save();
                
                \Log::info('Adjustment Fully Approved', [
                    'adjustment_id' => $adjustment->id,
                    'adjustment_number' => $adjustment->adjustment_number
                ]);
            }

            DB::commit();

            $message = $isFullyApproved 
                ? 'All approvals complete. Adjustment is fully approved and ready for GL posting.'
                : 'Approval level ' . $currentLevel . ' approved. Awaiting next level approval.';

            \Log::info('Approval Success', ['message' => $message]);
            
            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Approval Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'adjustment_id' => $adjustment->id ?? null
            ]);
            return back()->with('error', 'Failed to approve: ' . $e->getMessage());
        }
    }

    /**
     * Bulk approve adjustments
     */
    public function bulkApproveAdjustments(Request $request, $encodedId)
    {
        // Custom log file for debugging
        $logFile = storage_path('logs/bulk_approve_debug.log');
        $logMessage = function($msg) use ($logFile) {
            $timestamp = date('Y-m-d H:i:s');
            file_put_contents($logFile, "[{$timestamp}] {$msg}\n", FILE_APPEND);
        };
        
        // Log immediately at the start - use both Log and error_log
        $logMessage("=== BULK APPROVE START ===");
        $logMessage("Encoded ID: " . $encodedId);
        $logMessage("Request data: " . json_encode($request->all()));
        error_log("=== BULK APPROVE START ===");
        error_log("Encoded ID: " . $encodedId);
        error_log("Request data: " . json_encode($request->all()));
        
        Log::info('=== BULK APPROVE START ===', [
            'encoded_id' => $encodedId,
            'request_all' => $request->all(),
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name ?? 'Unknown'
        ]);

        $sessionId = Hashids::decode($encodedId)[0] ?? null;
        $logMessage("Decoded Session ID: " . ($sessionId ?? 'NULL'));
        error_log("Decoded Session ID: " . ($sessionId ?? 'NULL'));
        
        if (!$sessionId) {
            $logMessage("ERROR: Session ID is null after decoding");
            error_log("ERROR: Session ID is null after decoding");
            Log::error('Bulk Approve - Invalid encoded ID', ['encoded_id' => $encodedId]);
            abort(404);
        }

        $session = CountSession::findOrFail($sessionId);
        $logMessage("Session found: " . $session->id);
        error_log("Session found: " . $session->id);
        
        if ($session->company_id != Auth::user()->company_id) {
            $logMessage("ERROR: Company ID mismatch");
            error_log("ERROR: Company ID mismatch");
            abort(403);
        }

        try {
            // Handle adjustment_ids - it might come as JSON string or array
            $adjustmentIds = $request->input('adjustment_ids');
            if (is_string($adjustmentIds)) {
                // Try to decode JSON string
                $decoded = json_decode($adjustmentIds, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $request->merge(['adjustment_ids' => $decoded]);
                    $logMessage("Decoded JSON string to array: " . json_encode($decoded));
                } else {
                    // If not JSON, try to split by comma or use as single value
                    $logMessage("Failed to decode JSON, trying alternative parsing");
                    $request->merge(['adjustment_ids' => [$adjustmentIds]]);
                }
            }
            
            $validated = $request->validate([
                'adjustment_ids' => 'required|array|min:1',
                'adjustment_ids.*' => 'required|exists:inventory_count_adjustments,id',
                'comments' => 'nullable|string',
            ]);
            
            $logMessage("Validation passed. Adjustment IDs: " . json_encode($validated['adjustment_ids']));
            error_log("Validation passed. Adjustment IDs: " . json_encode($validated['adjustment_ids']));
        } catch (\Illuminate\Validation\ValidationException $e) {
            $logMessage("VALIDATION ERROR: " . json_encode($e->errors()));
            error_log("VALIDATION ERROR: " . json_encode($e->errors()));
            Log::error('Bulk Approve - Validation failed', ['errors' => $e->errors()]);
            throw $e;
        }

        Log::info('Bulk Approval Attempt', [
            'session_id' => $sessionId,
            'adjustment_ids' => $validated['adjustment_ids'],
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name
        ]);
        
        error_log("Starting bulk approval for " . count($validated['adjustment_ids']) . " adjustments");

        try {
            DB::beginTransaction();

            $adjustments = CountAdjustment::whereIn('id', $validated['adjustment_ids'])
                ->with(['session', 'approvals'])
                ->get();

            Log::info('Bulk Approval - Loaded Adjustments', [
                'count' => $adjustments->count(),
                'adjustment_numbers' => $adjustments->pluck('adjustment_number')->toArray()
            ]);

            // Verify all adjustments belong to the same session and are pending approval
            foreach ($adjustments as $adjustment) {
                if ($adjustment->count_session_id != $sessionId) {
                    throw new \Exception('All adjustments must belong to the same count session.');
                }
                if ($adjustment->status !== 'pending_approval') {
                    Log::warning('Bulk Approval - Adjustment not pending', [
                        'adjustment_id' => $adjustment->id,
                        'adjustment_number' => $adjustment->adjustment_number,
                        'status' => $adjustment->status
                    ]);
                    throw new \Exception('All adjustments must be pending approval.');
                }
            }

            // Get approval settings once for all adjustments
            $approvalSettings = \App\Models\Inventory\CountSessionApprovalSetting::getForCompany($session->company_id);
            $configuredLevels = (int) ($approvalSettings->approval_levels ?? 1);
            $user = Auth::user();

            Log::info('Bulk Approval - Settings', [
                'configured_levels' => $configuredLevels,
                'user_id' => $user->id
            ]);

            $approvedCount = 0;
            $fullyApprovedCount = 0;
            $errors = [];

            foreach ($adjustments as $adjustment) {
                try {
                    // Ensure session and approvals are loaded
                    if (!$adjustment->relationLoaded('session')) {
                        $adjustment->load('session');
                    }
                    if (!$adjustment->relationLoaded('approvals')) {
                        $adjustment->load('approvals');
                    }

                    Log::info('Bulk Approval - Processing Adjustment', [
                        'adjustment_id' => $adjustment->id,
                        'adjustment_number' => $adjustment->adjustment_number,
                        'status' => $adjustment->status,
                        'approvals_count' => $adjustment->approvals->count(),
                        'all_approvals' => $adjustment->approvals->map(function($a) {
                            return [
                                'level' => $a->approval_level,
                                'status' => $a->status,
                                'level_name' => $a->level_name
                            ];
                        })->toArray()
                    ]);

                    // Get current approval level (only within configured levels)
                    $currentLevel = $adjustment->getCurrentApprovalLevel();
                    
                    Log::info('Bulk Approval - Current Level', [
                        'adjustment_id' => $adjustment->id,
                        'current_level' => $currentLevel,
                        'pending_approvals_count' => $adjustment->approvals()->where('status', 'pending')->count(),
                        'pending_within_configured' => $adjustment->approvals()->where('status', 'pending')->where('approval_level', '<=', $configuredLevels)->count()
                    ]);

                    if (!$currentLevel) {
                        $allPending = $adjustment->approvals()->where('status', 'pending')->count();
                        $pendingWithinConfigured = $adjustment->approvals()->where('status', 'pending')->where('approval_level', '<=', $configuredLevels)->count();
                        
                        Log::warning('Bulk Approval - No Current Level Found', [
                            'adjustment_id' => $adjustment->id,
                            'adjustment_number' => $adjustment->adjustment_number,
                            'all_pending' => $allPending,
                            'pending_within_configured' => $pendingWithinConfigured,
                            'configured_levels' => $configuredLevels
                        ]);
                        
                        $errors[] = "Adjustment {$adjustment->adjustment_number}: No pending approval level found.";
                        continue;
                    }

                    // Check if user can approve at this level based on settings
                    $canApprove = $approvalSettings->canUserApproveAtLevel($user, $currentLevel);
                    
                    Log::info('Bulk Approval - Permission Check', [
                        'adjustment_id' => $adjustment->id,
                        'current_level' => $currentLevel,
                        'can_approve' => $canApprove,
                        'approval_type' => $approvalSettings->getApprovalTypeForLevel($currentLevel),
                        'approvers' => $approvalSettings->getApproversForLevel($currentLevel)
                    ]);
                    
                    if (!$canApprove) {
                        $errors[] = "Adjustment {$adjustment->adjustment_number}: You do not have permission to approve at level {$currentLevel}.";
                        continue;
                    }

                    // Approve current level - ensure we're getting the right one within configured levels
                    $approval = $adjustment->approvals()
                        ->where('approval_level', $currentLevel)
                        ->where('status', 'pending')
                        ->first();

                    if (!$approval) {
                        Log::warning('Bulk Approval - Approval Record Not Found', [
                            'adjustment_id' => $adjustment->id,
                            'current_level' => $currentLevel,
                            'all_approvals_at_level' => $adjustment->approvals()->where('approval_level', $currentLevel)->get()->map(function($a) {
                                return ['id' => $a->id, 'status' => $a->status];
                            })->toArray()
                        ]);
                        $errors[] = "Adjustment {$adjustment->adjustment_number}: Approval level {$currentLevel} not found or already processed.";
                        continue;
                    }

                    $logMessage("Before approve() call - Approval ID: {$approval->id}, Status: {$approval->status}");
                    error_log("Before approve() call - Approval ID: {$approval->id}, Status: {$approval->status}");
                    
                    $approval->approve($validated['comments'] ?? null);
                    $approval->approver_id = Auth::id();
                    
                    $logMessage("After approve() call - Status: {$approval->status}, Approver ID: {$approval->approver_id}");
                    error_log("After approve() call - Status: {$approval->status}, Approver ID: {$approval->approver_id}");
                    
                    $saved = $approval->save();
                    $logMessage("Approval save result: " . ($saved ? 'TRUE' : 'FALSE'));
                    $logMessage("Approval after save - ID: {$approval->id}, Status: {$approval->status}, Approver: {$approval->approver_id}");
                    error_log("Approval save result: " . ($saved ? 'TRUE' : 'FALSE'));
                    error_log("Approval after save - ID: {$approval->id}, Status: {$approval->status}, Approver: {$approval->approver_id}");
                    
                    // Verify the save by reloading from database
                    $reloadedApproval = \App\Models\Inventory\CountAdjustmentApproval::find($approval->id);
                    $logMessage("Reloaded approval from DB - Status: " . ($reloadedApproval->status ?? 'NULL') . ", Approver: " . ($reloadedApproval->approver_id ?? 'NULL'));
                    error_log("Reloaded approval from DB - Status: " . ($reloadedApproval->status ?? 'NULL') . ", Approver: " . ($reloadedApproval->approver_id ?? 'NULL'));

                    Log::info('Bulk Approval - Approval Record Updated', [
                        'adjustment_id' => $adjustment->id,
                        'approval_id' => $approval->id,
                        'approval_level' => $approval->approval_level,
                        'new_status' => $approval->status
                    ]);

                    $approvedCount++;

                    // Reload approvals to get fresh data
                    $adjustment->load('approvals');
                    
                    // Check if all approvals are complete
                    $isFullyApproved = $adjustment->isFullyApproved();
                    
                    Log::info('Bulk Approval - Fully Approved Check', [
                        'adjustment_id' => $adjustment->id,
                        'is_fully_approved' => $isFullyApproved,
                        'configured_levels' => $configuredLevels,
                        'approved_count' => $adjustment->approvals()->where('approval_level', '<=', $configuredLevels)->where('status', 'approved')->count(),
                        'total_relevant' => $adjustment->approvals()->where('approval_level', '<=', $configuredLevels)->count()
                    ]);
                    
                    if ($isFullyApproved) {
                        $logMessage("Adjustment {$adjustment->id} is fully approved. Updating status...");
                        error_log("Adjustment {$adjustment->id} is fully approved. Updating status...");
                        $adjustment->status = 'approved';
                        $adjustment->approved_by = Auth::id();
                        $adjustment->approved_at = now();
                        $saved = $adjustment->save();
                        $logMessage("Adjustment save result: " . ($saved ? 'TRUE' : 'FALSE'));
                        error_log("Adjustment save result: " . ($saved ? 'TRUE' : 'FALSE'));
                        
                        // Verify the save
                        $reloadedAdjustment = \App\Models\Inventory\CountAdjustment::find($adjustment->id);
                        $logMessage("Reloaded adjustment from DB - Status: " . ($reloadedAdjustment->status ?? 'NULL'));
                        error_log("Reloaded adjustment from DB - Status: " . ($reloadedAdjustment->status ?? 'NULL'));
                        
                        $fullyApprovedCount++;
                        $logMessage("Fully approved count incremented to: {$fullyApprovedCount}");
                        error_log("Fully approved count incremented to: {$fullyApprovedCount}");
                        
                        Log::info('Bulk Approval - Adjustment Fully Approved', [
                            'adjustment_id' => $adjustment->id,
                            'adjustment_number' => $adjustment->adjustment_number,
                            'saved' => $saved,
                            'reloaded_status' => $reloadedAdjustment->status ?? null
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Bulk Approval - Adjustment Error', [
                        'adjustment_id' => $adjustment->id ?? null,
                        'adjustment_number' => $adjustment->adjustment_number ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $errors[] = "Adjustment {$adjustment->adjustment_number}: " . $e->getMessage();
                }
            }

            $logMessage("=== BEFORE DB COMMIT ===");
            $logMessage("Approved count: {$approvedCount}");
            $logMessage("Fully approved count: {$fullyApprovedCount}");
            $logMessage("Errors count: " . count($errors));
            error_log("=== BEFORE DB COMMIT ===");
            error_log("Approved count: {$approvedCount}");
            error_log("Fully approved count: {$fullyApprovedCount}");
            error_log("Errors count: " . count($errors));
            
            DB::commit();
            $logMessage("=== AFTER DB COMMIT ===");
            error_log("=== AFTER DB COMMIT ===");

            Log::info('Bulk Approval - Success', [
                'approved_count' => $approvedCount,
                'fully_approved_count' => $fullyApprovedCount,
                'errors_count' => count($errors)
            ]);
            
            error_log("=== BULK APPROVE SUCCESS ===");

            $message = "Successfully approved {$approvedCount} adjustment(s).";
            if ($fullyApprovedCount > 0) {
                $message .= " {$fullyApprovedCount} adjustment(s) are now fully approved and ready for GL posting.";
            }
            if (!empty($errors)) {
                $message .= " Errors: " . implode('; ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $message .= " and " . (count($errors) - 5) . " more errors.";
                }
            }

            return redirect()->route('inventory.counts.sessions.adjustments', $session->encoded_id)
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $logMessage("=== BULK APPROVE EXCEPTION ===");
            $logMessage("Error: " . $e->getMessage());
            $logMessage("File: " . $e->getFile() . ":" . $e->getLine());
            $logMessage("Trace: " . $e->getTraceAsString());
            error_log("=== BULK APPROVE EXCEPTION ===");
            error_log("Error: " . $e->getMessage());
            error_log("File: " . $e->getFile() . ":" . $e->getLine());
            error_log("Trace: " . $e->getTraceAsString());
            
            Log::error('Bulk Approval - Failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'session_id' => $sessionId ?? null
            ]);
            
            error_log("=== END EXCEPTION ===");
            return back()->with('error', 'Failed to approve adjustments: ' . $e->getMessage());
        }
    }

    /**
     * Reject adjustment
     */
    public function rejectAdjustment(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $adjustment = CountAdjustment::with(['session', 'approvals'])->findOrFail($id);
        if ($adjustment->session->company_id != Auth::user()->company_id) {
            abort(403);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $currentLevel = $adjustment->getCurrentApprovalLevel();
            if (!$currentLevel) {
                return back()->with('error', 'No pending approval level found.');
            }

            $approval = $adjustment->approvals()
                ->where('approval_level', $currentLevel)
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                return back()->with('error', 'Approval level not found or already processed.');
            }

            $approval->reject($validated['rejection_reason']);
            $approval->approver_id = Auth::id();
            $approval->save();

            $adjustment->status = 'rejected';
            $adjustment->save();

            DB::commit();

            return back()->with('success', 'Adjustment rejected.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to reject: ' . $e->getMessage());
        }
    }

    /**
     * Post adjustment to GL
     */
    public function postAdjustmentToGL($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $adjustment = CountAdjustment::with(['session', 'item', 'location'])->findOrFail($id);
        if ($adjustment->session->company_id != Auth::user()->company_id) {
            abort(403);
        }

        if ($adjustment->status !== 'approved') {
            return back()->with('error', 'Adjustment must be fully approved before posting to GL.');
        }

        if (!$adjustment->isFullyApproved()) {
            return back()->with('error', 'All approval levels must be completed before posting to GL.');
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $branchId = $adjustment->session->period->branch_id ?? session('branch_id') ?? $user->branch_id;
            $locationId = $adjustment->inventory_location_id;

            // Get inventory account from settings
            $inventoryAccountId = SystemSetting::where('key', 'inventory_default_inventory_account')->value('value');
            $inventoryAccount = $inventoryAccountId ? ChartAccount::find($inventoryAccountId) : null;

            if (!$inventoryAccount) {
                throw new \Exception('Inventory account not found. Please configure it in Inventory Settings.');
            }

            // Get loss/gain accounts from settings (default accounts)
            $lossAccountId = SystemSetting::where('key', 'inventory_loss_expense_account')->value('value');
            $gainAccountId = SystemSetting::where('key', 'inventory_gain_income_account')->value('value');
            
            // ChartAccount doesn't have company_id, so we can directly find by ID
            $lossAccount = $lossAccountId ? ChartAccount::find($lossAccountId) : null;
            $gainAccount = $gainAccountId ? ChartAccount::find($gainAccountId) : null;
            
            // Fallback to search by name if not configured in settings
            if (!$lossAccount) {
                $lossAccount = ChartAccount::where('account_name', 'LIKE', '%inventory loss%')
                ->orWhere('account_name', 'LIKE', '%stock loss%')
                    ->orWhere('account_name', 'LIKE', '%Inventory Loss Expense%')
                ->first();
            }

            if (!$gainAccount) {
                $gainAccount = ChartAccount::where('account_name', 'LIKE', '%inventory gain%')
                ->orWhere('account_name', 'LIKE', '%stock gain%')
                    ->orWhere('account_name', 'LIKE', '%Inventory Gain Income%')
                ->first();
            }
            
            // Final fallback: use inventory account if loss/gain accounts not found
            if (!$lossAccount) {
                $lossAccount = $inventoryAccount;
            }
            
            if (!$gainAccount) {
                $gainAccount = $inventoryAccount;
            }

            // Initialize cost service for updating cost layers
            $costService = new InventoryCostService();
            $adjustmentQuantity = abs($adjustment->adjustment_quantity);
            $unitCost = $adjustment->adjustment_value / $adjustmentQuantity;
            $actualCost = $adjustment->adjustment_value;

            // Update cost layers based on adjustment type BEFORE creating journal entries
            // This ensures we use the actual cost from cost layers (FIFO/AVCO) for accurate valuation
            if ($adjustment->adjustment_type === 'shortage') {
                // Shortage: Remove from cost layers using FIFO/AVCO
                $costInfo = $costService->removeInventory(
                    $adjustment->item_id,
                    $adjustmentQuantity,
                    'adjustment_out',
                    $adjustment->adjustment_number,
                    now()->toDateString()
                );
                // Use actual cost from cost layers (FIFO/AVCO) for accurate valuation
                $actualCost = $costInfo['total_cost'];
                $unitCost = $costInfo['average_unit_cost'] ?? ($actualCost / $adjustmentQuantity);
            } else {
                // Surplus: Add to cost layers with the unit cost from variance
                $costService->addInventory(
                    $adjustment->item_id,
                    $adjustmentQuantity,
                    $unitCost,
                    'adjustment_in',
                    $adjustment->adjustment_number,
                    now()->toDateString()
                );
            }

            // Create journal entry
            $nextId = Journal::max('id') + 1;
            $reference = 'ADJ-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

            $journal = Journal::create([
                'date' => now()->toDateString(),
                'description' => 'Inventory Count Adjustment: ' . $adjustment->adjustment_number,
                'branch_id' => $branchId,
                'user_id' => $user->id,
                'reference_type' => 'Inventory Count Adjustment',
                'reference' => $reference,
            ]);

            if ($adjustment->adjustment_type === 'shortage') {
                // Shortage: Dr Loss, Cr Inventory (use actual cost from cost layers)
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $lossAccount->id ?? $inventoryAccount->id,
                    'amount' => $actualCost,
                    'nature' => 'debit',
                    'description' => 'Inventory Shortage - ' . $adjustment->item->name,
                ]);

                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $inventoryAccount->id,
                    'amount' => $actualCost,
                    'nature' => 'credit',
                    'description' => 'Inventory Shortage - ' . $adjustment->item->name,
                ]);
            } else {
                // Surplus: Dr Inventory, Cr Gain
                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $inventoryAccount->id,
                    'amount' => $actualCost,
                    'nature' => 'debit',
                    'description' => 'Inventory Surplus - ' . $adjustment->item->name,
                ]);

                JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $gainAccount->id ?? $inventoryAccount->id,
                    'amount' => $actualCost,
                    'nature' => 'credit',
                    'description' => 'Inventory Surplus - ' . $adjustment->item->name,
                ]);
            }

            $movementType = $adjustment->adjustment_type === 'shortage' ? 'adjustment_out' : 'adjustment_in';

            // Get current stock balance before adjustment
            $stockService = new InventoryStockService();
            $balanceBefore = $stockService->getItemStockAtLocation($adjustment->item_id, $locationId);
            
            // Calculate balance after adjustment
            $balanceAfter = $balanceBefore;
            if ($adjustment->adjustment_type === 'shortage') {
                $balanceAfter = $balanceBefore - $adjustmentQuantity;
            } else {
                $balanceAfter = $balanceBefore + $adjustmentQuantity;
            }
            
            // Create inventory movement
            $movement = Movement::create([
                'branch_id' => $branchId,
                'location_id' => $locationId,
                'item_id' => $adjustment->item_id,
                'user_id' => $user->id,
                'movement_type' => $movementType,
                'quantity' => $adjustmentQuantity,
                'unit_cost' => $unitCost,
                'total_cost' => $actualCost,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => 'inventory_count_adjustment',
                'reference_id' => $adjustment->id,
                'reference_number' => $adjustment->adjustment_number,
                'reason' => $adjustment->reason_description,
                'movement_date' => now(),
            ]);

            // Update stock level
            $stockLevel = StockLevel::where('item_id', $adjustment->item_id)
                ->where('inventory_location_id', $locationId)
                ->first();

            if ($stockLevel) {
                if ($adjustment->adjustment_type === 'shortage') {
                    $stockLevel->quantity -= $adjustmentQuantity;
                } else {
                    $stockLevel->quantity += $adjustmentQuantity;
                }
                $stockLevel->save();
            } else {
                // Create stock level if it doesn't exist (for surplus adjustments)
                if ($adjustment->adjustment_type === 'surplus') {
                    StockLevel::create([
                        'item_id' => $adjustment->item_id,
                        'inventory_location_id' => $locationId,
                        'quantity' => $adjustmentQuantity,
                    ]);
                }
            }

            // Update adjustment
            $adjustment->status = 'posted';
            $adjustment->posted_by = $user->id;
            $adjustment->posted_at = now();
            $adjustment->journal_id = $journal->id;
            $adjustment->movement_id = $movement->id;
            $adjustment->save();

            // Reload journal with items before creating GL transactions
            $journal->load('items');

            // Initialize journal approval workflow
            $journal->initializeApprovalWorkflow();

            // Create GL transactions from journal items
            $journal->createGlTransactions();

            DB::commit();

            return back()->with('success', 'Adjustment posted to GL successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to post to GL: ' . $e->getMessage());
        }
    }

    /**
     * Assign team members to count session
     */
    public function assignTeam(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $session = CountSession::findOrFail($id);
        if ($session->company_id != Auth::user()->company_id) {
            abort(403);
        }

        $validated = $request->validate([
            'teams' => 'required|array',
            'teams.*.user_id' => 'required|exists:users,id',
            'teams.*.role' => 'required|in:counter,supervisor,verifier',
            'teams.*.assigned_area' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            // Delete existing team assignments
            CountTeam::where('count_session_id', $session->id)->delete();

            // Create new team assignments
            $primarySupervisor = null;
            foreach ($validated['teams'] as $teamData) {
                CountTeam::create([
                    'count_session_id' => $session->id,
                    'user_id' => $teamData['user_id'],
                    'role' => $teamData['role'],
                    'assigned_area' => $teamData['assigned_area'] ?? null,
                    'assigned_at' => now(),
                    'assigned_by' => Auth::id(),
                ]);

                // Auto-assign first supervisor as session supervisor
                if ($teamData['role'] === 'supervisor' && !$primarySupervisor) {
                    $primarySupervisor = $teamData['user_id'];
                }
            }

            // Update session supervisor if a supervisor was assigned in team
            if ($primarySupervisor) {
                $session->supervisor_id = $primarySupervisor;
                $session->save();
            }

            DB::commit();

            return redirect()->route('inventory.counts.sessions.show', $session->encoded_id)
                ->with('success', 'Team members assigned successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to assign team: ' . $e->getMessage());
        }
    }

    /**
     * Show team assignment form
     */
    public function showTeamAssignment($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $session = CountSession::with(['teams.user', 'location'])->findOrFail($id);
        if ($session->company_id != Auth::user()->company_id) {
            abort(403);
        }

        // Get all users in the company
        $users = \App\Models\User::where('company_id', $session->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('inventory.counts.sessions.assign-team', compact('session', 'users'));
    }

    /**
     * Upload Excel for offline counting
     */
    public function uploadCountingExcel(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'Invalid session ID'], 404);
        }

        $session = CountSession::with('entries.item')->findOrFail($id);
        if ($session->company_id != Auth::user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (!in_array($session->status, ['frozen', 'counting'])) {
            return response()->json(['success' => false, 'message' => 'Session must be frozen or in counting status'], 400);
        }

        $validated = $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            DB::beginTransaction();

            $file = $request->file('excel_file');
            $data = Excel::toArray([], $file)[0]; // Get first sheet

            // Skip header row (row 0)
            $imported = 0;
            $errors = [];

            for ($i = 1; $i < count($data); $i++) {
                $row = $data[$i];
                
                // Expected format: Item Code, Item Name, Physical Quantity, Condition, Lot Number, Batch Number, Expiry Date, Remarks
                // Item Name (column 1) is for reference only, we skip it
                if (count($row) < 3) continue;

                $itemCode = trim($row[0] ?? '');
                // $itemName = $row[1] ?? ''; // Item Name - for reference only, not used
                $physicalQty = $row[2] ?? 0;
                $condition = $row[3] ?? 'good';
                $lotNumber = $row[4] ?? null;
                $batchNumber = $row[5] ?? null;
                $expiryDate = isset($row[6]) && !empty($row[6]) ? \Carbon\Carbon::parse($row[6]) : null;
                $remarks = $row[7] ?? null;

                if (empty($itemCode)) continue;

                // Find entry by item code
                $entry = $session->entries()
                    ->whereHas('item', function($q) use ($itemCode) {
                        $q->where('code', $itemCode);
                    })
                    ->first();

                if (!$entry) {
                    $errors[] = "Row " . ($i + 1) . ": Item code '{$itemCode}' not found in session";
                    continue;
                }

                // Update entry
                $entry->physical_quantity = $physicalQty;
                $entry->condition = $condition;
                $entry->lot_number = $lotNumber;
                $entry->batch_number = $batchNumber;
                $entry->expiry_date = $expiryDate;
                $entry->remarks = $remarks;
                $entry->counted_by = Auth::id();
                $entry->counted_at = now();
                $entry->status = 'counted';
                $entry->save();

                $imported++;
            }

            DB::commit();

            $message = "Successfully imported {$imported} entries.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode('; ', array_slice($errors, 0, 10));
                if (count($errors) > 10) {
                    $message .= " and " . (count($errors) - 10) . " more errors.";
                }
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'imported' => $imported,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to import: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download Excel template for counting
     */
    public function downloadCountingTemplate($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $session = CountSession::with(['entries.item', 'location'])->findOrFail($id);
        if ($session->company_id != Auth::user()->company_id) {
            abort(403);
        }

        $data = [];
        $data[] = ['Item Code', 'Item Name', 'Physical Quantity', 'Condition (good/damaged/expired/obsolete/missing)', 'Lot Number', 'Batch Number', 'Expiry Date (YYYY-MM-DD)', 'Remarks'];

        foreach ($session->entries as $entry) {
            $data[] = [
                $entry->item->code ?? '',
                $entry->item->name ?? '',
                $entry->physical_quantity ?? '',
                $entry->condition ?? 'good',
                $entry->lot_number ?? '',
                $entry->batch_number ?? '',
                $entry->expiry_date ? $entry->expiry_date->format('Y-m-d') : '',
                $entry->remarks ?? '',
            ];
        }

        return Excel::download(new \App\Exports\CountingTemplateExport($data), 'counting-template-' . $session->session_number . '.xlsx');
    }

    /**
     * Detect batch/lot mismatches
     */
    private function detectBatchLotMismatches(CountSession $session)
    {
        foreach ($session->entries as $entry) {
            if (!$entry->physical_quantity || !$entry->item) {
                continue;
            }

            // Get system lot/batch from latest movement or stock level
            $latestMovement = \App\Models\Inventory\Movement::where('item_id', $entry->item_id)
                ->where('location_id', $entry->inventory_location_id)
                ->where('movement_type', 'in')
                ->orderBy('movement_date', 'desc')
                ->first();

            $systemLot = $latestMovement->lot_number ?? null;
            $systemBatch = $latestMovement->batch_number ?? null;

            $physicalLot = $entry->lot_number;
            $physicalBatch = $entry->batch_number;

            $hasMismatch = false;
            $mismatchDetails = [];

            if ($systemLot && $physicalLot && $systemLot !== $physicalLot) {
                $hasMismatch = true;
                $mismatchDetails[] = "Lot Number: System='{$systemLot}', Physical='{$physicalLot}'";
            }

            if ($systemBatch && $physicalBatch && $systemBatch !== $physicalBatch) {
                $hasMismatch = true;
                $mismatchDetails[] = "Batch Number: System='{$systemBatch}', Physical='{$physicalBatch}'";
            }

            if ($hasMismatch && $entry->variance) {
                $entry->variance->update([
                    'investigation_notes' => ($entry->variance->investigation_notes ? $entry->variance->investigation_notes . "\n" : '') . 
                        "BATCH/LOT MISMATCH DETECTED: " . implode('; ', $mismatchDetails)
                ]);
            }
        }
    }
}
