<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Shares\Shareholder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class ShareholderController extends Controller
{
    /**
     * Display a listing of shareholders.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        if ($request->ajax()) {
            return $this->getData($request, $companyId);
        }
        
        return view('accounting.share-capital.shareholders.index');
    }
    
    /**
     * Get shareholders data for DataTables
     */
    private function getData(Request $request, $companyId)
    {
        $query = Shareholder::where('company_id', $companyId)
            ->select('shareholders.*') // Explicitly select all columns including id
            ->with(['company', 'creator']);
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->filled('is_related_party')) {
            $query->where('is_related_party', $request->is_related_party);
        }
        
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('code_link', function ($shareholder) {
                $id = $shareholder->getKey(); // Use getKey() to get the actual primary key value
                $code = $shareholder->code ?? ($id ? 'SH-' . $id : 'SH-');

                if (! $id) {
                    return e($code);
                }

                $encodedId = Hashids::encode($id);

                return '<a href="' . route('accounting.share-capital.shareholders.show', ['encodedId' => $encodedId]) . '" class="text-primary fw-bold">' . e($code) . '</a>';
            })
            ->addColumn('type_badge', function ($shareholder) {
                $badgeClass = match($shareholder->type) {
                    'individual' => 'bg-primary',
                    'corporate' => 'bg-info',
                    'government' => 'bg-warning',
                    'employee' => 'bg-success',
                    'related_party' => 'bg-danger',
                    default => 'bg-secondary',
                };
                return '<span class="badge ' . $badgeClass . '">' . ucfirst(str_replace('_', ' ', $shareholder->type)) . '</span>';
            })
            ->addColumn('related_party_badge', function ($shareholder) {
                if ($shareholder->is_related_party) {
                    return '<span class="badge bg-danger">Related Party</span>';
                }
                return '<span class="badge bg-secondary">Independent</span>';
            })
            ->addColumn('status_badge', function ($shareholder) {
                $badgeClass = $shareholder->is_active ? 'bg-success' : 'bg-secondary';
                return '<span class="badge ' . $badgeClass . '">' . ($shareholder->is_active ? 'Active' : 'Inactive') . '</span>';
            })
            ->addColumn('actions', function ($shareholder) {
                $actions = '<div class="d-flex gap-1">';
                
                // Get the primary key value using getKey() which respects the model's primaryKey setting
                $id = $shareholder->getKey();

                if ($id) {
                    $encodedId = Hashids::encode($id);
                    $safeEncodedId = e($encodedId);
                    $safeName = htmlspecialchars($shareholder->name ?? 'Unknown', ENT_QUOTES);

                    $actions .= '<a href="' . route('accounting.share-capital.shareholders.show', ['encodedId' => $encodedId]) . '" class="btn btn-sm btn-info" title="View"><i class="bx bx-show"></i></a>';
                    $actions .= '<a href="' . route('accounting.share-capital.shareholders.edit', ['encodedId' => $encodedId]) . '" class="btn btn-sm btn-primary" title="Edit"><i class="bx bx-edit"></i></a>';
                    $actions .= '<button type="button" class="btn btn-sm btn-danger delete-shareholder-btn" data-encoded-id="' . $safeEncodedId . '" data-name="' . $safeName . '" title="Delete"><i class="bx bx-trash"></i></button>';
                } else {
                    $actions .= '<button type="button" class="btn btn-sm btn-secondary" disabled title="ID missing"><i class="bx bx-block"></i></button>';
                }

                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['code_link', 'type_badge', 'related_party_badge', 'status_badge', 'actions'])
            ->make(true);
    }
    
    /**
     * Show the form for creating a new shareholder.
     */
    public function create()
    {
        return view('accounting.share-capital.shareholders.create');
    }
    
    /**
     * Store a newly created shareholder.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|in:individual,corporate,government,employee,related_party,other',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'is_related_party' => 'boolean',
            'related_party_notes' => 'nullable|string',
        ]);
        
        $validated['company_id'] = $user->company_id;
        $validated['created_by'] = $user->id;
        $validated['is_active'] = true;
        
        Shareholder::create($validated);
        
        return redirect()->route('accounting.share-capital.shareholders.index')
            ->with('success', 'Shareholder created successfully.');
    }
    
    /**
     * Display the specified shareholder.
     */
    public function show($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $shareholder = Shareholder::with(['company', 'creator', 'updater', 'shareHoldings.shareClass'])
            ->findOrFail($id);
        
        // Calculate statistics
        $totalShares = $shareholder->total_shares_held;
        $totalHoldings = $shareholder->shareHoldings()->where('status', 'active')->count();
        
        return view('accounting.share-capital.shareholders.show', compact(
            'shareholder',
            'totalShares',
            'totalHoldings'
        ));
    }
    
    /**
     * Show the form for editing the specified shareholder.
     */
    public function edit($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $shareholder = Shareholder::findOrFail($id);
        
        return view('accounting.share-capital.shareholders.edit', compact('shareholder'));
    }
    
    /**
     * Update the specified shareholder.
     */
    public function update(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $shareholder = Shareholder::findOrFail($id);
        $user = Auth::user();
        
        $validated = $request->validate([
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|in:individual,corporate,government,employee,related_party,other',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'is_related_party' => 'boolean',
            'related_party_notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        
        $validated['updated_by'] = $user->id;
        
        $shareholder->update($validated);
        
        return redirect()->route('accounting.share-capital.shareholders.show', $encodedId)
            ->with('success', 'Shareholder updated successfully.');
    }
    
    /**
     * Remove the specified shareholder from storage.
     */
    public function destroy($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Shareholder not found.'], 404);
            }
            abort(404);
        }
        
        $shareholder = Shareholder::findOrFail($id);
        
        // Check if shareholder has active holdings
        $activeHoldings = $shareholder->shareHoldings()->where('status', 'active')->count();
        if ($activeHoldings > 0) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete shareholder with active share holdings. Please transfer or cancel all holdings first.'
                ], 422);
            }
            return redirect()->route('accounting.share-capital.shareholders.index')
                ->with('error', 'Cannot delete shareholder with active share holdings.');
        }
        
        $shareholder->delete();
        
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Shareholder deleted successfully.'
            ]);
        }
        
        return redirect()->route('accounting.share-capital.shareholders.index')
            ->with('success', 'Shareholder deleted successfully.');
    }
}

