<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Shares\ShareClass;
use App\Models\ChartAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class ShareClassController extends Controller
{
    /**
     * Display a listing of share classes.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        if ($request->ajax()) {
            return $this->getData($request, $companyId);
        }
        
        return view('accounting.share-capital.share-classes.index');
    }
    
    /**
     * Get share classes data for DataTables
     */
    private function getData(Request $request, $companyId)
    {
        $query = ShareClass::where('company_id', $companyId)
            ->with(['company', 'creator']);
        
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('code_link', function ($shareClass) {
                return '<a href="' . route('accounting.share-capital.share-classes.show', $shareClass->encoded_id) . '" class="text-primary fw-bold">' . $shareClass->code . '</a>';
            })
            ->addColumn('formatted_par_value', function ($shareClass) {
                if (!$shareClass->has_par_value) {
                    return 'No Par Value';
                }
                return number_format($shareClass->par_value, 6) . ' ' . ($shareClass->currency_code ?? '');
            })
            ->addColumn('formatted_authorized_shares', function ($shareClass) {
                return $shareClass->authorized_shares ? number_format($shareClass->authorized_shares) : 'N/A';
            })
            ->addColumn('status_badge', function ($shareClass) {
                $badgeClass = $shareClass->is_active ? 'bg-success' : 'bg-secondary';
                return '<span class="badge ' . $badgeClass . '">' . ($shareClass->is_active ? 'Active' : 'Inactive') . '</span>';
            })
            ->addColumn('actions', function ($shareClass) {
                $actions = '<div class="d-flex gap-1">';
                $actions .= '<a href="' . route('accounting.share-capital.share-classes.show', $shareClass->encoded_id) . '" class="btn btn-sm btn-info" title="View"><i class="bx bx-show"></i></a>';
                $actions .= '<a href="' . route('accounting.share-capital.share-classes.edit', $shareClass->encoded_id) . '" class="btn btn-sm btn-primary" title="Edit"><i class="bx bx-edit"></i></a>';
                $actions .= '<button type="button" class="btn btn-sm btn-danger delete-share-class-btn" data-encoded-id="' . $shareClass->encoded_id . '" data-name="' . htmlspecialchars($shareClass->name, ENT_QUOTES) . '" title="Delete"><i class="bx bx-trash"></i></button>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['code_link', 'status_badge', 'actions'])
            ->make(true);
    }
    
    /**
     * Show the form for creating a new share class.
     */
    public function create()
    {
        return view('accounting.share-capital.share-classes.create');
    }
    
    /**
     * Store a newly created share class.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'has_par_value' => 'boolean',
            'par_value' => 'nullable|numeric|min:0',
            'currency_code' => 'nullable|string|size:3',
            'share_type' => 'required|in:ordinary,preference,other',
            'voting_rights' => 'required|in:full,limited,none',
            'dividend_policy' => 'required|in:discretionary,fixed,participating,none',
            'redeemable' => 'boolean',
            'convertible' => 'boolean',
            'cumulative' => 'boolean',
            'participating' => 'boolean',
            'classification' => 'required|in:equity,liability,compound',
            'authorized_shares' => 'nullable|integer|min:0',
            'authorized_value' => 'nullable|numeric|min:0',
        ]);
        
        $validated['company_id'] = $user->company_id;
        $validated['created_by'] = $user->id;
        $validated['is_active'] = true;
        
        ShareClass::create($validated);
        
        return redirect()->route('accounting.share-capital.share-classes.index')
            ->with('success', 'Share class created successfully.');
    }
    
    /**
     * Display the specified share class.
     */
    public function show($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $shareClass = ShareClass::with(['company', 'creator', 'updater'])
            ->findOrFail($id);
        
        // Get statistics
        $totalIssued = $shareClass->total_issued_shares;
        $totalOutstanding = $shareClass->total_outstanding_shares;
        $totalHoldings = $shareClass->shareHoldings()->where('status', 'active')->count();
        
        return view('accounting.share-capital.share-classes.show', compact(
            'shareClass',
            'totalIssued',
            'totalOutstanding',
            'totalHoldings'
        ));
    }
    
    /**
     * Show the form for editing the specified share class.
     */
    public function edit($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $shareClass = ShareClass::findOrFail($id);
        
        return view('accounting.share-capital.share-classes.edit', compact('shareClass'));
    }
    
    /**
     * Update the specified share class.
     */
    public function update(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $shareClass = ShareClass::findOrFail($id);
        $user = Auth::user();
        
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'has_par_value' => 'boolean',
            'par_value' => 'nullable|numeric|min:0',
            'currency_code' => 'nullable|string|size:3',
            'share_type' => 'required|in:ordinary,preference,other',
            'voting_rights' => 'required|in:full,limited,none',
            'dividend_policy' => 'required|in:discretionary,fixed,participating,none',
            'redeemable' => 'boolean',
            'convertible' => 'boolean',
            'cumulative' => 'boolean',
            'participating' => 'boolean',
            'classification' => 'required|in:equity,liability,compound',
            'authorized_shares' => 'nullable|integer|min:0',
            'authorized_value' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);
        
        $validated['updated_by'] = $user->id;
        
        $shareClass->update($validated);
        
        return redirect()->route('accounting.share-capital.share-classes.show', $encodedId)
            ->with('success', 'Share class updated successfully.');
    }
    
    /**
     * Remove the specified share class from storage.
     */
    public function destroy($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Share class not found.'], 404);
            }
            abort(404);
        }
        
        $shareClass = ShareClass::findOrFail($id);
        
        // Check if share class has active share issues
        $activeIssues = $shareClass->shareIssues()->where('status', '!=', 'cancelled')->count();
        if ($activeIssues > 0) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete share class with active share issues. Please cancel or complete all issues first.'
                ], 422);
            }
            return redirect()->route('accounting.share-capital.share-classes.index')
                ->with('error', 'Cannot delete share class with active share issues.');
        }
        
        // Check if share class has active holdings
        $activeHoldings = $shareClass->shareHoldings()->where('status', 'active')->count();
        if ($activeHoldings > 0) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete share class with active share holdings. Please transfer or cancel all holdings first.'
                ], 422);
            }
            return redirect()->route('accounting.share-capital.share-classes.index')
                ->with('error', 'Cannot delete share class with active share holdings.');
        }
        
        // Check if share class has corporate actions
        $corporateActions = $shareClass->corporateActions()->where('status', '!=', 'cancelled')->count();
        if ($corporateActions > 0) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete share class with corporate actions. Please cancel all corporate actions first.'
                ], 422);
            }
            return redirect()->route('accounting.share-capital.share-classes.index')
                ->with('error', 'Cannot delete share class with corporate actions.');
        }
        
        // Check if share class has dividends
        $dividends = $shareClass->dividends()->where('status', '!=', 'cancelled')->count();
        if ($dividends > 0) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete share class with dividend records. Please cancel all dividends first.'
                ], 422);
            }
            return redirect()->route('accounting.share-capital.share-classes.index')
                ->with('error', 'Cannot delete share class with dividend records.');
        }
        
        $shareClass->delete();
        
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Share class deleted successfully.'
            ]);
        }
        
        return redirect()->route('accounting.share-capital.share-classes.index')
            ->with('success', 'Share class deleted successfully.');
    }
}

