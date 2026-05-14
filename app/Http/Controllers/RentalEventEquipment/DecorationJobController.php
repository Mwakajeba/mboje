<?php

namespace App\Http\Controllers\RentalEventEquipment;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\RentalEventEquipment\DecorationJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class DecorationJobController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return view('rental-event-equipment.decoration-jobs.index');
    }

    /**
     * Get decoration jobs data for DataTables.
     */
    public function data(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $query = DecorationJob::with(['customer'])
            ->forCompany($companyId)
            ->forBranch($branchId);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('customer_name', function ($job) {
                return $job->customer->name ?? 'N/A';
            })
            ->addColumn('event_date_formatted', function ($job) {
                return $job->event_date ? $job->event_date->format('M d, Y') : '-';
            })
            ->addColumn('status_badge', function ($job) {
                $status = $job->status ?? 'draft';
                $badgeClass = match ($status) {
                    'draft' => 'secondary',
                    'planned' => 'info',
                    'confirmed' => 'primary',
                    'in_progress' => 'warning',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    default => 'secondary',
                };

                $label = ucwords(str_replace('_', ' ', $status));

                return '<span class="badge bg-' . $badgeClass . '">' . $label . '</span>';
            })
            ->addColumn('agreed_price_formatted', function ($job) {
                return 'TZS ' . number_format($job->agreed_price, 2);
            })
            ->addColumn('actions', function ($job) {
                return view('rental-event-equipment.decoration-jobs.partials.actions', compact('job'))->render();
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $customers = Customer::where('company_id', $companyId)
            ->when($branchId, function ($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('rental-event-equipment.decoration-jobs.create', compact('customers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'event_date' => 'nullable|date',
            'event_location' => 'nullable|string|max:255',
            'event_theme' => 'nullable|string|max:255',
            'package_name' => 'nullable|string|max:255',
            'service_description' => 'nullable|string',
            'agreed_price' => 'required|numeric|min:0',
            'status' => 'required|in:draft,planned,confirmed,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        DB::beginTransaction();

        try {
            $nextId = (DecorationJob::max('id') ?? 0) + 1;
            $jobNumber = 'DJ-' . date('Y') . '-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

            DecorationJob::create([
                'job_number' => $jobNumber,
                'customer_id' => $request->customer_id,
                'event_date' => $request->event_date,
                'event_location' => $request->event_location,
                'event_theme' => $request->event_theme,
                'package_name' => $request->package_name,
                'service_description' => $request->service_description,
                'agreed_price' => $request->agreed_price,
                'status' => $request->status,
                'notes' => $request->notes,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('rental-event-equipment.decoration-jobs.index')
                ->with('success', 'Decoration job created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Failed to create decoration job: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $job = DecorationJob::forCompany($companyId)
            ->forBranch($branchId)
            ->with(['customer', 'creator'])
            ->findOrFail($id);

        return view('rental-event-equipment.decoration-jobs.show', compact('job'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $job = DecorationJob::forCompany($companyId)
            ->forBranch($branchId)
            ->findOrFail($id);

        $customers = Customer::where('company_id', $companyId)
            ->when($branchId, function ($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('rental-event-equipment.decoration-jobs.edit', compact('job', 'customers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $job = DecorationJob::forCompany($companyId)
            ->forBranch($branchId)
            ->findOrFail($id);

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'event_date' => 'nullable|date',
            'event_location' => 'nullable|string|max:255',
            'event_theme' => 'nullable|string|max:255',
            'package_name' => 'nullable|string|max:255',
            'service_description' => 'nullable|string',
            'agreed_price' => 'required|numeric|min:0',
            'status' => 'required|in:draft,planned,confirmed,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $job->update([
            'customer_id' => $request->customer_id,
            'event_date' => $request->event_date,
            'event_location' => $request->event_location,
            'event_theme' => $request->event_theme,
            'package_name' => $request->package_name,
            'service_description' => $request->service_description,
            'agreed_price' => $request->agreed_price,
            'status' => $request->status,
            'notes' => $request->notes,
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('rental-event-equipment.decoration-jobs.index')
            ->with('success', 'Decoration job updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $job = DecorationJob::forCompany($companyId)
            ->forBranch($branchId)
            ->findOrFail($id);

        $job->delete();

        return redirect()->route('rental-event-equipment.decoration-jobs.index')
            ->with('success', 'Decoration job deleted successfully.');
    }
}
