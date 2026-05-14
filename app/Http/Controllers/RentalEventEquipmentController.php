<?php

namespace App\Http\Controllers;

use App\Models\RentalEventEquipment\CustomerDeposit;
use App\Models\RentalEventEquipment\DecorationEquipmentIssue;
use App\Models\RentalEventEquipment\DecorationEquipmentLoss;
use App\Models\RentalEventEquipment\DecorationEquipmentPlan;
use App\Models\RentalEventEquipment\DecorationEquipmentReturn;
use App\Models\RentalEventEquipment\DecorationInvoice;
use App\Models\RentalEventEquipment\DecorationJob;
use App\Models\RentalEventEquipment\Equipment;
use App\Models\RentalEventEquipment\RentalContract;
use App\Models\RentalEventEquipment\RentalDamageCharge;
use App\Models\RentalEventEquipment\RentalDispatch;
use App\Models\RentalEventEquipment\RentalInvoice;
use App\Models\RentalEventEquipment\RentalQuotation;
use App\Models\RentalEventEquipment\RentalReturn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RentalEventEquipmentController extends Controller
{
    /**
     * Display the Rental & Event Equipment dashboard.
     */
    public function index(): View
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        // Apply the same company/branch scoping convention used elsewhere in the module
        $branchScope = function ($query) use ($branchId) {
            return $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            });
        };

        $dashboardCounts = [
            // Rental business
            'equipment' => Equipment::where('company_id', $companyId)
                ->when($branchId, $branchScope)
                ->count(),
            'rental_quotations' => RentalQuotation::where('company_id', $companyId)
                ->when($branchId, $branchScope)
                ->count(),
            'rental_contracts' => RentalContract::where('company_id', $companyId)
                ->when($branchId, $branchScope)
                ->count(),
            'customer_deposits' => CustomerDeposit::where('company_id', $companyId)
                ->when($branchId, $branchScope)
                ->count(),
            'rental_dispatches' => RentalDispatch::where('company_id', $companyId)
                ->when($branchId, $branchScope)
                ->count(),
            'rental_returns' => RentalReturn::where('company_id', $companyId)
                ->when($branchId, $branchScope)
                ->count(),
            'damage_charges' => RentalDamageCharge::where('company_id', $companyId)
                ->when($branchId, $branchScope)
                ->count(),
            'rental_invoices' => RentalInvoice::where('company_id', $companyId)
                ->when($branchId, $branchScope)
                ->count(),

            // Decoration service
            'decoration_jobs' => DecorationJob::where('company_id', $companyId)
                ->when($branchId, $branchScope)
                ->count(),
            'decoration_plans' => DecorationEquipmentPlan::where('company_id', $companyId)
                ->when($branchId, $branchScope)
                ->count(),
            'decoration_issues' => DecorationEquipmentIssue::where('company_id', $companyId)
                ->when($branchId, $branchScope)
                ->count(),
            'decoration_returns' => DecorationEquipmentReturn::where('company_id', $companyId)
                ->when($branchId, $branchScope)
                ->count(),
            'decoration_losses' => DecorationEquipmentLoss::where('company_id', $companyId)
                ->when($branchId, $branchScope)
                ->count(),
            'decoration_invoices' => DecorationInvoice::where('company_id', $companyId)
                ->when($branchId, $branchScope)
                ->count(),
        ];

        return view('rental-event-equipment.index', compact('dashboardCounts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('rental-event-equipment.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'item_name' => 'required|string|max:255',
            'category' => 'required|in:rental_equipment,decoration_equipment',
            'quantity_owned' => 'required|integer|min:1',
            'replacement_cost' => 'required|numeric|min:0',
            'rental_rate' => 'nullable|numeric|min:0|required_if:category,rental_equipment',
            'status' => 'required|in:available,reserved,on_rent,in_event_use,under_repair,lost',
            'description' => 'nullable|string',
        ]);

        // TODO: Store equipment in database
        // For now, just redirect with success message
        return redirect()->route('rental-event-equipment.index')
            ->with('success', 'Equipment created successfully.');
    }
}
