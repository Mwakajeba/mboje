<?php

namespace App\Http\Controllers;

use App\Models\CashCollateralType;
use App\Models\ChartAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class CashCollateralTypeController extends Controller
{
    /**
     * Display a listing of cash collateral types
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getDataTable();
        }

        // Get stats for dashboard cards
        $totalTypes = CashCollateralType::count();
        $activeTypes = CashCollateralType::where('is_active', 1)->count();
        $inactiveTypes = CashCollateralType::where('is_active', 0)->count();
        
        // Get chart accounts for the create modal
        $chartAccounts = ChartAccount::orderBy('account_code')->get();

        return view('cash_collateral_types.index', compact('totalTypes', 'activeTypes', 'inactiveTypes', 'chartAccounts'));
    }

    /**
     * Get DataTable for cash collateral types
     */
    private function getDataTable()
    {
        $query = CashCollateralType::with(['chartAccount'])
            ->select('cash_collateral_types.*');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('chart_account_name', function ($row) {
                return $row->chartAccount ? $row->chartAccount->account_name : '—';
            })
            ->addColumn('chart_account_code', function ($row) {
                return $row->chartAccount ? $row->chartAccount->account_code : '—';
            })
            ->addColumn('status', function ($row) {
                return $row->is_active ? 
                    '<span class="badge bg-success">Hai</span>' : 
                    '<span class="badge bg-secondary">Haifanyi kazi</span>';
            })
            ->addColumn('action', function ($row) {
                $editBtn = '<button type="button" 
                               class="btn btn-sm btn-outline-warning me-1 edit-btn" 
                               data-id="' . $row->id . '"
                               data-name="' . htmlspecialchars($row->name) . '"
                               data-chart-account-id="' . $row->chart_account_id . '"
                               data-description="' . htmlspecialchars($row->description ?? '') . '"
                               data-active="' . $row->is_active . '"
                               title="Badili">
                               <i class="bx bx-edit"></i> Badili
                           </button>';
                
                $deleteBtn = '<button type="button" 
                                 class="btn btn-sm btn-outline-danger delete-btn" 
                                 data-id="' . $row->id . '" 
                                 data-name="' . htmlspecialchars($row->name) . '" 
                                 title="Futa">
                                 <i class="bx bx-trash"></i> Futa
                             </button>';

                return $editBtn . $deleteBtn;
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new cash collateral type
     */
    public function create()
    {
        $chartAccounts = ChartAccount::orderBy('account_code')->get();
        return view('cash_collateral_types.create', compact('chartAccounts'));
    }

    /**
     * Store a newly created cash collateral type
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:cash_collateral_types,name',
            'chart_account_id' => 'required|exists:chart_accounts,id',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean'
        ]);

        $cashCollateralType = CashCollateralType::create([
            'name' => $request->name,
            'chart_account_id' => $request->chart_account_id,
            'description' => $request->description,
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Akaunti ya mkopo imesajiliwa kikamilifu.',
                'data' => $cashCollateralType->load('chartAccount')
            ]);
        }

        return redirect()->route('cash_collateral_types.index')
            ->with('success', 'Akaunti ya mkopo imesajiliwa kikamilifu.');
    }

    /**
     * Display the specified cash collateral type
     */
    public function show($id)
    {
        $cashCollateralType = CashCollateralType::with(['chartAccount'])->findOrFail($id);
        return view('cash_collateral_types.show', compact('cashCollateralType'));
    }

    /**
     * Show the form for editing the specified cash collateral type
     */
    public function edit($id)
    {
        $cashCollateralType = CashCollateralType::findOrFail($id);
        $chartAccounts = ChartAccount::orderBy('account_code')->get();
        
        return view('cash_collateral_types.edit', compact('cashCollateralType', 'chartAccounts'));
    }

    /**
     * Update the specified cash collateral type
     */
    public function update(Request $request, $id)
    {
        $cashCollateralType = CashCollateralType::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:cash_collateral_types,name,' . $id,
            'chart_account_id' => 'required|exists:chart_accounts,id',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean'
        ]);

        $cashCollateralType->update([
            'name' => $request->name,
            'chart_account_id' => $request->chart_account_id,
            'description' => $request->description,
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Akaunti ya mkopo imesasishwa kikamilifu.',
                'data' => $cashCollateralType->load('chartAccount')
            ]);
        }

        return redirect()->route('cash_collateral_types.index')
            ->with('success', 'Akaunti ya mkopo imesasishwa kikamilifu.');
    }

    /**
     * Remove the specified cash collateral type
     */
    public function destroy($id)
    {
        try {
            $cashCollateralType = CashCollateralType::findOrFail($id);
            $cashCollateralType->delete();

            return response()->json([
                'success' => true,
                'message' => 'Akaunti ya mkopo imefutwa kikamilifu.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Imeshindwa kufuta akaunti ya mkopo: ' . $e->getMessage()
            ], 500);
        }
    }
}