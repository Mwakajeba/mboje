<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Contingency;
use App\Models\Provision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;

class ContingencyController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($request->ajax()) {
            $query = Contingency::where('company_id', $user->company_id)
                ->with(['branch', 'provision'])
                ->select([
                    'id',
                    'contingency_number',
                    'contingency_type',
                    'title',
                    'probability',
                    'probability_percent',
                    'expected_amount',
                    'currency_code',
                    'status',
                    'resolution_outcome',
                    'resolution_date',
                    'branch_id',
                    'provision_id',
                    'created_at',
                ]);

            return datatables($query)
                ->addColumn('contingency_type_label', function (Contingency $c) {
                    return ucfirst($c->contingency_type);
                })
                ->addColumn('status_badge', function (Contingency $c) {
                    $badgeClass = match ($c->status) {
                        'open' => 'bg-info',
                        'resolved' => 'bg-success',
                        'cancelled' => 'bg-secondary',
                        default => 'bg-secondary',
                    };
                    return '<span class="badge ' . $badgeClass . '">' . ucfirst($c->status) . '</span>';
                })
                ->addColumn('formatted_expected_amount', function (Contingency $c) {
                    return $c->expected_amount !== null
                        ? number_format($c->expected_amount, 2) . ' ' . $c->currency_code
                        : '-';
                })
                ->addColumn('branch_name', function (Contingency $c) {
                    return $c->branch?->name ?? '-';
                })
                ->addColumn('linked_provision', function (Contingency $c) {
                    return $c->provision ? $c->provision->provision_number : '-';
                })
                ->addColumn('formatted_resolution_date', function (Contingency $c) {
                    return $c->resolution_date ? $c->resolution_date->format('Y-m-d') : '-';
                })
                ->addColumn('actions', function (Contingency $c) {
                    $encodedId = $c->encoded_id;
                    $url = route('accounting.contingencies.show', $encodedId);
                    return '<a href="' . $url . '" class="btn btn-sm btn-info" title="View"><i class="bx bx-show"></i></a>';
                })
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        }

        $base = Contingency::where('company_id', $user->company_id);

        $totalContingencies = (clone $base)->count();
        $totalLiabilities = (clone $base)->where('contingency_type', 'liability')->sum('expected_amount');
        $totalAssets = (clone $base)->where('contingency_type', 'asset')->sum('expected_amount');
        $openItems = (clone $base)->where('status', 'open')->count();

        return view('accounting.provisions.contingencies.index', compact(
            'totalContingencies',
            'totalLiabilities',
            'totalAssets',
            'openItems'
        ));
    }

    public function create()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $branches = Branch::where('company_id', $companyId)->orderBy('name')->get();
        $provisions = Provision::forCompany($companyId)
            ->orderBy('provision_number')
            ->get();

        return view('accounting.provisions.contingencies.create', compact('branches', 'provisions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contingency_type' => 'required|in:liability,asset',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'provision_id' => 'nullable|exists:provisions,id',
            'probability' => 'required|in:remote,possible,probable,virtually_certain',
            'probability_percent' => 'nullable|numeric|min:0|max:100',
            'currency_code' => 'required|string|size:3',
            'fx_rate_at_creation' => 'nullable|numeric|min:0.000001',
            'expected_amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:open,resolved,cancelled',
            'resolution_outcome' => 'nullable|in:no_outflow,outflow,inflow,other',
            'resolution_date' => 'nullable|date',
            'resolution_notes' => 'nullable|string|max:2000',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $user = Auth::user();
        $companyId = $user->company_id;

        $contingency = new Contingency();
        $contingency->contingency_number = $this->generateContingencyNumber($companyId);
        $contingency->fill($validated);
        $contingency->fx_rate_at_creation = $validated['fx_rate_at_creation'] ?? 1;
        $contingency->company_id = $companyId;
        $contingency->created_by = $user->id;
        $contingency->updated_by = $user->id;
        $contingency->save();

        return redirect()
            ->route('accounting.contingencies.show', $contingency->encoded_id)
            ->with('success', 'Contingent item saved for disclosure (no journal entries posted).');
    }

    public function show(string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $contingency = Contingency::with(['branch', 'company', 'provision'])->findOrFail($id);

        if ($contingency->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        return view('accounting.provisions.contingencies.show', compact('contingency'));
    }

    protected function generateContingencyNumber(int $companyId): string
    {
        $prefix = 'CTG';
        $year = date('Y');

        $last = Contingency::withTrashed()
            ->where('company_id', $companyId)
            ->where('contingency_number', 'like', $prefix . '-' . $year . '-%')
            ->orderBy('contingency_number', 'desc')
            ->first();

        $next = 1;
        if ($last) {
            $parts = explode('-', $last->contingency_number);
            if (count($parts) === 3 && is_numeric($parts[2])) {
                $next = (int) $parts[2] + 1;
            }
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $next);
    }
}


