<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view rooms'); // reuse permission until a tenant permission is defined

        if ($request->ajax()) {
            $tenants = Tenant::withCount(['leases'])
                ->where('company_id', Auth::user()->company_id)
                ->select('tenants.*');

            return DataTables::of($tenants)
                ->addColumn('tenant_number', fn($t) => $t->tenant_number ?? ('TENANT' . str_pad($t->id, 6, '0', STR_PAD_LEFT)))
                ->addColumn('name', fn($t) => $t->full_name)
                ->addColumn('contact', function ($t) {
                    $lines = [];
                    if ($t->phone) { $lines[] = e($t->phone); }
                    if ($t->email) { $lines[] = e($t->email); }
                    return implode('<br>', $lines);
                })
                ->addColumn('status_badge', function ($t) {
                    $map = [ 'active' => 'success', 'inactive' => 'secondary', 'blacklisted' => 'danger' ];
                    $color = $map[$t->status] ?? 'light';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($t->status ?? 'unknown') . '</span>';
                })
                ->addColumn('leases_count', fn($t) => $t->leases_count)
                ->addColumn('actions', function ($t) {
                    $actions = '<div class="btn-group" role="group">';
                    $actions .= '<a href="' . route('tenants.show', $t->id) . '" class="btn btn-sm btn-outline-primary" title="View"><i class="bx bx-show"></i></a>';
                    $actions .= '<a href="' . route('tenants.edit', $t->id) . '" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bx bx-edit"></i></a>';
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['contact', 'status_badge', 'actions'])
                ->make(true);
        }

        $totalTenants = Tenant::where('company_id', Auth::user()->company_id)->count();
        $activeTenants = Tenant::where('company_id', Auth::user()->company_id)->where('status', 'active')->count();
        $inactiveTenants = Tenant::where('company_id', Auth::user()->company_id)->where('status', 'inactive')->count();
        $rentingTenants = Tenant::where('company_id', Auth::user()->company_id)
            ->whereHas('leases', function ($q) {
                $q->where('status', 'active')
                  ->where('start_date', '<=', now())
                  ->where('end_date', '>=', now());
            })->count();

        return view('property.tenants.index', compact('totalTenants', 'activeTenants', 'inactiveTenants', 'rentingTenants'));
    }

    public function create()
    {
        $this->authorize('create room'); // reuse permission until tenant perms exist
        return view('property.tenants.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create room');

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:150',
            'phone' => 'required|string|max:50',
            'id_number' => 'nullable|string|max:100',
            'id_type' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'nationality' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'employer_name' => 'nullable|string|max:150',
            'employer_phone' => 'nullable|string|max:50',
            'monthly_income' => 'nullable|numeric|min:0',
            'emergency_contact_name' => 'nullable|string|max:150',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'emergency_contact_relationship' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive,blacklisted',
            'notes' => 'nullable|string',
        ]);

        $tenant = Tenant::create(array_merge($validated, [
            'tenant_number' => (new Tenant())->generateTenantNumber(),
            'company_id' => Auth::user()->company_id,
            'created_by' => Auth::id(),
        ]));

        return redirect()->route('tenants.index')->with('success', 'Tenant created successfully.');
    }
}


