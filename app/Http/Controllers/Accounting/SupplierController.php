<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Vinkla\Hashids\Facades\Hashids;

class SupplierController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $companyId = $user->company_id ?? null;

        $branchId = $user->branch_id ?? null;
        if ($companyId) {
            $suppliers = Supplier::with(['company', 'branch', 'createdBy'])
                ->byCompany($companyId)
                ->when($branchId, function($q) use ($branchId){ $q->where('branch_id', $branchId); })
                ->orderBy('name')
                ->get();
        } else {
            // If user doesn't have company_id, show by branch if present, else all suppliers
            $suppliers = Supplier::with(['company', 'branch', 'createdBy'])
                ->when($branchId, function($q) use ($branchId){ $q->where('branch_id', $branchId); })
                ->orderBy('name')
                ->get();
        }

        $stats = [
            'total' => $suppliers->count(),
            'active' => $suppliers->where('status', 'active')->count(),
            'inactive' => $suppliers->where('status', 'inactive')->count(),
            'blacklisted' => $suppliers->where('status', 'blacklisted')->count(),
        ];

        return view('accounting.suppliers.index', compact('suppliers', 'stats'));
    }

    public function create()
    {
        $user = auth()->user();
        $companyId = $user->company_id ?? null;

        $companies = Company::orderBy('name')->get();

        if ($companyId) {
            $branches = Branch::where('company_id', $companyId)->orderBy('name')->get();
        } else {
            $branches = Branch::orderBy('name')->get();
        }

        $regions = Region::orderBy('name')->get();
        $statusOptions = Supplier::getStatusOptions();

        return view('accounting.suppliers.create', compact('companies', 'branches', 'regions', 'statusOptions'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
            'region' => 'nullable|string|max:100',
            'company_registration_name' => 'nullable|string|max:255',
            'tin_number' => 'nullable|string|max:50',
            'vat_number' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:50',
            'account_name' => 'nullable|string|max:255',
            'products_or_services' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive,blacklisted',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user = auth()->user();
        $companyId = $user->company_id ?? Company::first()->id ?? 1;

        // Debug: Log the Business & Legal Information fields
        \Log::info('SupplierController::store - Business & Legal fields received:', [
            'company_registration_name' => $request->company_registration_name,
            'tin_number' => $request->tin_number,
            'vat_number' => $request->vat_number,
            'products_or_services' => $request->products_or_services
        ]);

        $supplier = Supplier::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'region' => $request->region,
            'company_registration_name' => $request->company_registration_name,
            'tin_number' => $request->tin_number,
            'vat_number' => $request->vat_number,
            'bank_name' => $request->bank_name,
            'bank_account_number' => $request->bank_account_number,
            'account_name' => $request->account_name,
            'products_or_services' => $request->products_or_services,
            'status' => $request->status,
            'company_id' => $companyId,
            'branch_id' => (Auth::user()->branch_id) ?? (session('branch_id') ?: null) ?? (function_exists('current_branch_id') ? current_branch_id() : null),
            'created_by' => auth()->id(),
        ]);

        // Debug: Log what was actually saved
        \Log::info('SupplierController::store - Business & Legal fields saved:', [
            'company_registration_name' => $supplier->company_registration_name,
            'tin_number' => $supplier->tin_number,
            'vat_number' => $supplier->vat_number,
            'products_or_services' => $supplier->products_or_services
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => 'Supplier created successfully',
                'supplier' => [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'phone' => $supplier->phone,
                ],
            ], 201);
        }

        return redirect()->route('accounting.suppliers.index')
            ->with('success', 'Supplier created successfully!');
    }

    public function show($encodedId)
    {
        // Decode the ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.suppliers.index')->withErrors(['Supplier not found.']);
        }

        $supplier = Supplier::findOrFail($decoded[0]);
        $supplier->load(['company', 'branch', 'createdBy', 'updatedBy']);

        return view('accounting.suppliers.show', compact('supplier'));
    }

    public function edit($encodedId)
    {
        // Decode the ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.suppliers.index')->withErrors(['Supplier not found.']);
        }

        $supplier = Supplier::findOrFail($decoded[0]);

        $user = auth()->user();
        $companyId = $user->company_id ?? null;

        $companies = Company::orderBy('name')->get();

        if ($companyId) {
            $branches = Branch::where('company_id', $companyId)->orderBy('name')->get();
        } else {
            $branches = Branch::orderBy('name')->get();
        }

        $regions = Region::orderBy('name')->get();
        $statusOptions = Supplier::getStatusOptions();

        return view('accounting.suppliers.edit', compact('supplier', 'companies', 'branches', 'regions', 'statusOptions'));
    }

    public function update(Request $request, $encodedId)
    {
        // Decode supplier ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.suppliers.index')->withErrors(['Supplier not found.']);
        }

        $supplier = Supplier::findOrFail($decoded[0]);

        // Debug: Log the Business & Legal Information fields
        \Log::info('SupplierController::update - Business & Legal fields received:', [
            'company_registration_name' => $request->company_registration_name,
            'tin_number' => $request->tin_number,
            'vat_number' => $request->vat_number,
            'products_or_services' => $request->products_or_services
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
            'region' => 'nullable|string|max:100',
            'company_registration_name' => 'nullable|string|max:255',
            'tin_number' => 'nullable|string|max:50',
            'vat_number' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:50',
            'account_name' => 'nullable|string|max:255',
            'products_or_services' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive,blacklisted',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $supplier->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'region' => $request->region,
            'company_registration_name' => $request->company_registration_name,
            'tin_number' => $request->tin_number,
            'vat_number' => $request->vat_number,
            'bank_name' => $request->bank_name,
            'bank_account_number' => $request->bank_account_number,
            'account_name' => $request->account_name,
            'products_or_services' => $request->products_or_services,
            'status' => $request->status,
            'branch_id' => $request->branch_id,
            'updated_by' => auth()->id(),
        ]);

        // Debug: Log what was actually updated
        \Log::info('SupplierController::update - Business & Legal fields updated:', [
            'company_registration_name' => $supplier->fresh()->company_registration_name,
            'tin_number' => $supplier->fresh()->tin_number,
            'vat_number' => $supplier->fresh()->vat_number,
            'products_or_services' => $supplier->fresh()->products_or_services
        ]);

        return redirect()->route('accounting.suppliers.index')
            ->with('success', 'Supplier updated successfully!');
    }

    public function destroy($encodedId)
    {
        // Decode the encoded ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.suppliers.index')->withErrors(['Supplier not found.']);
        }

        $supplier = Supplier::findOrFail($decoded[0]);
        $supplier->delete();

        return redirect()->route('accounting.suppliers.index')
            ->with('success', 'Supplier deleted successfully!');
    }

    public function changeStatus(Request $request, $encodedId)
    {
        // Decode the encoded ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.suppliers.index')->withErrors(['Supplier not found.']);
        }

        $supplier = Supplier::findOrFail($decoded[0]);

        $request->validate([
            'status' => 'required|in:active,inactive,blacklisted'
        ]);

        $supplier->update([
            'status' => $request->status,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()
            ->with('success', 'Supplier status updated successfully!');
    }
}