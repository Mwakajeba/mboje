<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property\Lease;
use App\Models\Hotel\Property;
use App\Models\Hotel\Room;
use App\Models\Property\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class LeaseController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view rooms'); // reuse permission or create a specific one later

        if ($request->ajax()) {
            $leases = Lease::with(['property', 'room', 'tenant'])
                ->where('branch_id', Auth::user()->branch_id)
                ->select('leases.*');

            return DataTables::of($leases)
                ->addColumn('lease_number', fn($l) => $l->lease_number ?? ('#' . $l->id))
                ->addColumn('rental_unit', function ($l) {
                    if ($l->room) {
                        return ($l->property->name ?? 'Property') . ' - Room ' . ($l->room->room_number ?? 'N/A');
                    }
                    return $l->property->name ?? 'N/A';
                })
                ->addColumn('tenant_name', fn($l) => $l->tenant->name ?? 'N/A')
                ->addColumn('period', function ($l) {
                    $start = $l->start_date ? $l->start_date->format('M d, Y') : 'N/A';
                    $end = $l->end_date ? $l->end_date->format('M d, Y') : 'N/A';
                    return "$start - $end";
                })
                ->addColumn('monthly_rent_formatted', fn($l) => 'TSh ' . number_format($l->monthly_rent ?? 0, 0))
                ->addColumn('status_badge', function ($l) {
                    $map = [
                        'active' => 'success',
                        'expired' => 'danger',
                        'terminated' => 'secondary',
                        'renewed' => 'info'
                    ];
                    $color = $map[$l->status] ?? 'light';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($l->status ?? 'unknown') . '</span>';
                })
                ->addColumn('payment_status_badge', function ($l) {
                    $map = [
                        'current' => 'success',
                        'due' => 'warning',
                        'overdue' => 'danger',
                    ];
                    $color = $map[$l->payment_status] ?? 'light';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($l->payment_status ?? 'unknown') . '</span>';
                })
                ->addColumn('actions', function ($l) {
                    $actions = '<div class="btn-group" role="group">';
                    $actions .= '<a href="' . route('leases.show', $l->id) . '" class="btn btn-sm btn-outline-primary" title="View"><i class="bx bx-show"></i></a>';
                    $actions .= '<a href="' . route('leases.edit', $l->id) . '" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bx bx-edit"></i></a>';
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['status_badge', 'payment_status_badge', 'actions'])
                ->make(true);
        }

        $totalLeases = Lease::where('branch_id', Auth::user()->branch_id)->count();
        $activeLeases = Lease::where('branch_id', Auth::user()->branch_id)->where('status', 'active')->count();
        $expiredLeases = Lease::where('branch_id', Auth::user()->branch_id)->where('status', 'expired')->count();
        $overdueLeases = Lease::where('branch_id', Auth::user()->branch_id)->where('payment_status', 'overdue')->count();

        return view('property.leases.index', compact('totalLeases', 'activeLeases', 'expiredLeases', 'overdueLeases'));
    }

    public function create()
    {
        $this->authorize('create room'); // reuse until dedicated permission exists

        $properties = Property::orderBy('name')->get();
        $rooms = Room::orderBy('room_number')->get();
        $tenants = Tenant::orderBy('first_name')->get();

        return view('property.leases.create', compact('properties', 'rooms', 'tenants'));
    }

    public function store(Request $request)
    {
        $this->authorize('create room');

        $request->validate([
            'property_id' => 'required|exists:properties,id',
            'room_id' => 'nullable|exists:rooms,id',
            'tenant_id' => 'required|exists:tenants,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'monthly_rent' => 'required|numeric|min:0',
            'security_deposit' => 'nullable|numeric|min:0',
            'rent_due_day' => 'required|integer|min:1|max:31',
            'late_fee_amount' => 'nullable|numeric|min:0',
            'late_fee_grace_days' => 'nullable|integer|min:0',
            'terms_conditions' => 'nullable|string',
            'special_conditions' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $lease = Lease::create([
            'lease_number' => (new Lease())->generateLeaseNumber(),
            'property_id' => $request->property_id,
            'room_id' => $request->room_id,
            'tenant_id' => $request->tenant_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'monthly_rent' => $request->monthly_rent,
            'security_deposit' => $request->security_deposit ?? 0,
            'paid_deposit' => 0,
            'deposit_balance' => ($request->security_deposit ?? 0),
            'late_fee_amount' => $request->late_fee_amount ?? 0,
            'late_fee_grace_days' => $request->late_fee_grace_days ?? 0,
            'rent_due_day' => $request->rent_due_day,
            'status' => 'active',
            'payment_status' => 'current',
            'terms_conditions' => $request->terms_conditions,
            'special_conditions' => $request->special_conditions,
            'notes' => $request->notes,
            'branch_id' => Auth::user()->branch_id,
            'company_id' => Auth::user()->company_id,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('leases.index')->with('success', 'Lease created successfully.');
    }

    public function destroy(Lease $lease)
    {
        $this->authorize('delete room'); // reuse permission
        // Block deletion if there are dependent financial records (placeholder; expand when integrated)
        $hasPayments = method_exists($lease, 'payments') ? $lease->payments()->exists() : false;
        $hasInvoices = method_exists($lease, 'invoices') ? $lease->invoices()->exists() : false;
        if ($hasPayments || $hasInvoices) {
            return redirect()->back()->with('error', 'Cannot delete lease: it has related financial records.');
        }
        $lease->delete();
        return redirect()->route('leases.index')->with('success', 'Lease deleted successfully.');
    }
}


