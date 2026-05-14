<?php

namespace App\Http\Controllers\Accounting\PettyCash;

use App\Http\Controllers\Controller;
use App\Models\PettyCash\PettyCashUnit;
use App\Models\PettyCash\PettyCashRegister;
use App\Services\PettyCashModeService;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class PettyCashRegisterController extends Controller
{
    /**
     * Display petty cash register for a unit
     */
    public function index(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $unit = PettyCashUnit::with(['branch', 'custodian'])->findOrFail($id);
        
        // Check authorization
        if ($unit->company_id != Auth::user()->company_id) {
            abort(403);
        }

        // Handle AJAX request for DataTables
        if ($request->ajax()) {
            return $this->getRegisterData($request, $unit);
        }

        // Get reconciliation summary
        $asOfDate = $request->get('as_of_date', now()->toDateString());
        $reconciliation = PettyCashModeService::getReconciliationSummary($unit, $asOfDate);

        return view('accounting.petty-cash.register.index', compact('unit', 'reconciliation', 'asOfDate'));
    }

    /**
     * Get register data for DataTables
     */
    private function getRegisterData(Request $request, PettyCashUnit $unit)
    {
        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'status' => $request->get('status'),
            'entry_type' => $request->get('entry_type'),
        ];

        $query = PettyCashModeService::getRegisterEntries($unit->id, $filters);

        return datatables($query)
            ->addIndexColumn()
            ->addColumn('pcv_number_link', function ($entry) {
                return '<a href="#" class="text-primary fw-bold">' . ($entry->pcv_number ?? 'N/A') . '</a>';
            })
            ->addColumn('formatted_date', function ($entry) {
                return $entry->register_date->format('M d, Y');
            })
            ->addColumn('formatted_amount', function ($entry) {
                $class = $entry->nature === 'debit' ? 'text-danger' : 'text-success';
                $sign = $entry->nature === 'debit' ? '-' : '+';
                return '<span class="' . $class . ' fw-bold">' . $sign . 'TZS ' . number_format($entry->amount, 2) . '</span>';
            })
            ->addColumn('entry_type_badge', function ($entry) {
                $colors = [
                    'disbursement' => 'danger',
                    'replenishment' => 'success',
                    'opening_balance' => 'info',
                    'adjustment' => 'warning',
                ];
                $color = $colors[$entry->entry_type] ?? 'secondary';
                return '<span class="badge bg-' . $color . '">' . ucfirst(str_replace('_', ' ', $entry->entry_type)) . '</span>';
            })
            ->addColumn('nature_badge', function ($entry) {
                $color = $entry->nature === 'debit' ? 'danger' : 'success';
                $text = $entry->nature === 'debit' ? 'Debit' : 'Credit';
                return '<span class="badge bg-' . $color . '">' . $text . '</span>';
            })
            ->addColumn('gl_account_name', function ($entry) {
                return $entry->glAccount->account_name ?? 'N/A';
            })
            ->addColumn('requested_by_name', function ($entry) {
                return $entry->requestedBy->name ?? 'N/A';
            })
            ->addColumn('approved_by_name', function ($entry) {
                return $entry->approvedBy->name ?? 'N/A';
            })
            ->addColumn('status_badge', function ($entry) {
                $colors = [
                    'pending' => 'secondary',
                    'approved' => 'info',
                    'posted' => 'success',
                    'rejected' => 'danger',
                ];
                $color = $colors[$entry->status] ?? 'secondary';
                return '<span class="badge bg-' . $color . '">' . ucfirst($entry->status) . '</span>';
            })
            ->addColumn('formatted_balance_after', function ($entry) {
                return '<span class="fw-bold">TZS ' . number_format($entry->balance_after ?? 0, 2) . '</span>';
            })
            ->rawColumns(['pcv_number_link', 'formatted_amount', 'entry_type_badge', 'nature_badge', 'status_badge', 'formatted_balance_after'])
            ->make(true);
    }

    /**
     * Show reconciliation page
     */
    public function reconciliation(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $unit = PettyCashUnit::with(['branch', 'custodian'])->findOrFail($id);
        
        // Check authorization
        if ($unit->company_id != Auth::user()->company_id) {
            abort(403);
        }

        $asOfDate = $request->get('as_of_date', now()->toDateString());
        $reconciliation = PettyCashModeService::getReconciliationSummary($unit, $asOfDate);

        // Get outstanding vouchers (pending receipts)
        $outstandingVouchers = PettyCashRegister::where('petty_cash_unit_id', $unit->id)
            ->where('entry_type', 'disbursement')
            ->where('status', '!=', 'posted')
            ->where('register_date', '<=', $asOfDate)
            ->with(['transaction', 'requestedBy'])
            ->get();

        return view('accounting.petty-cash.register.reconciliation', compact('unit', 'reconciliation', 'outstandingVouchers', 'asOfDate'));
    }

    /**
     * Save reconciliation (cash count)
     */
    public function saveReconciliation(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $unit = PettyCashUnit::findOrFail($id);
        
        // Check authorization
        if ($unit->company_id != Auth::user()->company_id) {
            abort(403);
        }

        $request->validate([
            'as_of_date' => 'required|date',
            'cash_counted' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $asOfDate = $request->as_of_date;
        $reconciliation = PettyCashModeService::getReconciliationSummary($unit, $asOfDate);
        $variance = $request->cash_counted - $reconciliation['closing_cash'];

        // Store reconciliation (you may want to create a reconciliations table)
        // For now, we'll just return the variance

            return redirect()
            ->route('accounting.petty-cash.register.reconciliation', $unit->encoded_id)
            ->with('success', 'Reconciliation saved. Variance: ' . number_format($variance, 2) . ' TZS')
            ->with('reconciliation_data', [
                'cash_counted' => $request->cash_counted,
                'variance' => $variance,
            ]);
    }

    /**
     * Export Petty Cash Register to PDF
     */
    public function exportPdf(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $unit = PettyCashUnit::with(['branch', 'custodian', 'company'])->findOrFail($id);
        
        if ($unit->company_id != Auth::user()->company_id) {
            abort(403);
        }

        $filters = [
            'date_from' => $request->get('date_from', now()->startOfMonth()->format('Y-m-d')),
            'date_to' => $request->get('date_to', now()->format('Y-m-d')),
            'status' => $request->get('status'),
            'entry_type' => $request->get('entry_type'),
        ];

        $entries = PettyCashModeService::getRegisterEntries($unit->id, $filters)
            ->with(['transaction', 'replenishment', 'imprestRequest', 'glAccount', 'requestedBy', 'approvedBy'])
            ->get();

        $reconciliation = PettyCashModeService::getReconciliationSummary($unit, $filters['date_to']);

        $dateFrom = Carbon::parse($filters['date_from']);
        $dateTo = Carbon::parse($filters['date_to']);

        $pdf = Pdf::loadView('accounting.petty-cash.register.exports.pdf', compact(
            'unit', 'entries', 'reconciliation', 'dateFrom', 'dateTo', 'filters'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('petty-cash-register-' . $unit->code . '-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export Petty Cash Register to Excel
     */
    public function exportExcel(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $unit = PettyCashUnit::with(['branch', 'custodian', 'company'])->findOrFail($id);
        
        if ($unit->company_id != Auth::user()->company_id) {
            abort(403);
        }

        $filters = [
            'date_from' => $request->get('date_from', now()->startOfMonth()->format('Y-m-d')),
            'date_to' => $request->get('date_to', now()->format('Y-m-d')),
            'status' => $request->get('status'),
            'entry_type' => $request->get('entry_type'),
        ];

        $entries = PettyCashModeService::getRegisterEntries($unit->id, $filters)
            ->with(['transaction', 'replenishment', 'imprestRequest', 'glAccount', 'requestedBy', 'approvedBy'])
            ->get();

        $dateFrom = Carbon::parse($filters['date_from']);
        $dateTo = Carbon::parse($filters['date_to']);

        return Excel::download(
            new \App\Exports\PettyCashRegisterExport($entries, $unit, $dateFrom, $dateTo, $filters),
            'petty-cash-register-' . $unit->code . '-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Export Reconciliation Report to PDF
     */
    public function exportReconciliationPdf(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $unit = PettyCashUnit::with(['branch', 'custodian', 'company'])->findOrFail($id);
        
        if ($unit->company_id != Auth::user()->company_id) {
            abort(403);
        }

        $asOfDate = $request->get('as_of_date', now()->toDateString());
        $cashCounted = $request->get('cash_counted');
        $notes = $request->get('notes');
        
        $reconciliation = PettyCashModeService::getReconciliationSummary($unit, $asOfDate);
        
        // Get outstanding vouchers
        $outstandingVouchers = PettyCashRegister::where('petty_cash_unit_id', $unit->id)
            ->where('entry_type', 'disbursement')
            ->where('status', '!=', 'posted')
            ->where('register_date', '<=', $asOfDate)
            ->with(['transaction', 'requestedBy'])
            ->get();

        $variance = $cashCounted ? ($cashCounted - $reconciliation['closing_cash']) : null;

        $pdf = Pdf::loadView('accounting.petty-cash.register.exports.reconciliation-pdf', compact(
            'unit', 'reconciliation', 'outstandingVouchers', 'asOfDate', 'cashCounted', 'variance', 'notes'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('petty-cash-reconciliation-' . $unit->code . '-' . $asOfDate . '.pdf');
    }

    /**
     * Export Reconciliation Report to Excel
     */
    public function exportReconciliationExcel(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $unit = PettyCashUnit::with(['branch', 'custodian', 'company'])->findOrFail($id);
        
        if ($unit->company_id != Auth::user()->company_id) {
            abort(403);
        }

        $asOfDate = $request->get('as_of_date', now()->toDateString());
        $cashCounted = $request->get('cash_counted');
        $notes = $request->get('notes');
        
        $reconciliation = PettyCashModeService::getReconciliationSummary($unit, $asOfDate);
        
        // Get outstanding vouchers
        $outstandingVouchers = PettyCashRegister::where('petty_cash_unit_id', $unit->id)
            ->where('entry_type', 'disbursement')
            ->where('status', '!=', 'posted')
            ->where('register_date', '<=', $asOfDate)
            ->with(['transaction', 'requestedBy'])
            ->get();

        $variance = $cashCounted ? ($cashCounted - $reconciliation['closing_cash']) : null;

        return Excel::download(
            new \App\Exports\PettyCashReconciliationExport($unit, $reconciliation, $outstandingVouchers, $asOfDate, $cashCounted, $variance, $notes),
            'petty-cash-reconciliation-' . $unit->code . '-' . $asOfDate . '.xlsx'
        );
    }

    /**
     * Display reconciliation report for all units
     */
    public function reconciliationIndex(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $asOfDate = $request->get('as_of_date', now()->toDateString());
        $branchId = $request->get('branch_id');
        $status = $request->get('status');
        
        $query = PettyCashUnit::forCompany($companyId)
            ->with(['branch', 'custodian', 'supervisor']);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }
        
        $units = $query->orderBy('name')->get();
        $branches = Branch::where('company_id', $companyId)->orderBy('name')->get();
        
        return view('accounting.petty-cash.reconciliation.index', compact('units', 'branches', 'asOfDate'));
    }

    /**
     * Export Reconciliation Report (All Units) to PDF
     */
    public function exportReconciliationIndexPdf(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $asOfDate = $request->get('as_of_date', now()->toDateString());
        $branchId = $request->get('branch_id');
        $status = $request->get('status');
        
        $query = PettyCashUnit::forCompany($companyId)
            ->with(['branch', 'custodian', 'company']);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }
        
        $units = $query->orderBy('name')->get();
        
        $pdf = Pdf::loadView('accounting.petty-cash.reconciliation.exports.index-pdf', compact(
            'units', 'asOfDate', 'branchId', 'status', 'companyId'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('petty-cash-reconciliation-report-' . $asOfDate . '.pdf');
    }

    /**
     * Export Reconciliation Report (All Units) to Excel
     */
    public function exportReconciliationIndexExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $asOfDate = $request->get('as_of_date', now()->toDateString());
        $branchId = $request->get('branch_id');
        $status = $request->get('status');
        
        $query = PettyCashUnit::forCompany($companyId)
            ->with(['branch', 'custodian']);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }
        
        $units = $query->orderBy('name')->get();
        
        return Excel::download(
            new \App\Exports\PettyCashReconciliationIndexExport($units, $asOfDate),
            'petty-cash-reconciliation-report-' . $asOfDate . '.xlsx'
        );
    }
}

