<?php

namespace App\Http\Controllers\RentalEventEquipment;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\GlTransaction;
use App\Models\RentalEventEquipment\AccountingSetting;
use App\Models\RentalEventEquipment\DecorationInvoice;
use App\Models\RentalEventEquipment\DecorationJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class DecorationInvoiceController extends Controller
{
    public function index()
    {
        return view('rental-event-equipment.decoration-invoices.index');
    }

    public function data(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $query = DecorationInvoice::with(['customer', 'job'])
            ->where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            });

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('customer_name', function ($invoice) {
                return $invoice->customer->name ?? 'N/A';
            })
            ->addColumn('job_number', function ($invoice) {
                return optional($invoice->job)->job_number ?? '-';
            })
            ->addColumn('invoice_date_formatted', function ($invoice) {
                return $invoice->invoice_date ? $invoice->invoice_date->format('M d, Y') : '-';
            })
            ->addColumn('total_amount_formatted', function ($invoice) {
                return 'TZS ' . number_format($invoice->total_amount, 2);
            })
            ->addColumn('status_badge', function ($invoice) {
                $badgeClass = match ($invoice->status) {
                    'draft' => 'secondary',
                    'sent' => 'info',
                    'paid' => 'success',
                    'cancelled' => 'danger',
                    default => 'secondary',
                };
                return '<span class="badge bg-' . $badgeClass . '">' . ucfirst($invoice->status) . '</span>';
            })
            ->addColumn('actions', function ($invoice) {
                $encodedId = Hashids::encode($invoice->id);
                $actions = '<div class="btn-group" role="group">';
                $actions .= '<a href="' . route('rental-event-equipment.decoration-invoices.show', $encodedId) . '" class="btn btn-sm btn-outline-info" title="View">
                        <i class="bx bx-show"></i>
                    </a>';
                if ($invoice->status === 'draft') {
                    $actions .= '<a href="' . route('rental-event-equipment.decoration-invoices.edit', $encodedId) . '" class="btn btn-sm btn-outline-warning" title="Edit">
                            <i class="bx bx-edit"></i>
                        </a>';
                }
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    public function create(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $jobId = $request->get('job_id');
        $selectedJob = null;

        if ($jobId) {
            $decoded = Hashids::decode($jobId);
            $id = $decoded[0] ?? $jobId;
            $selectedJob = DecorationJob::forCompany($companyId)
                ->forBranch($branchId)
                ->with('customer')
                ->find($id);
        }

        $customers = Customer::where('company_id', $companyId)
            ->when($branchId, function ($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $jobs = DecorationJob::forCompany($companyId)
            ->forBranch($branchId)
            ->with('customer')
            ->orderBy('job_number', 'desc')
            ->get();

        return view('rental-event-equipment.decoration-invoices.create', compact('customers', 'jobs', 'selectedJob'));
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $request->validate([
            'decoration_job_id' => 'nullable|exists:decoration_jobs,id',
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'reference' => 'nullable|string|max:255',
            'service_description' => 'nullable|string',
            'service_amount' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $nextId = (DecorationInvoice::max('id') ?? 0) + 1;
            $invoiceNumber = 'DEC-INV-' . date('Y') . '-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

            $serviceAmount = (float) $request->service_amount;
            $taxAmount = (float) ($request->tax_amount ?? 0);
            $totalAmount = $serviceAmount + $taxAmount;

            $invoice = DecorationInvoice::create([
                'invoice_number' => $invoiceNumber,
                'decoration_job_id' => $request->decoration_job_id,
                'customer_id' => $request->customer_id,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date,
                'reference' => $request->reference,
                'service_description' => $request->service_description,
                'service_amount' => $serviceAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'notes' => $request->notes,
                'status' => 'draft',
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'created_by' => Auth::id(),
            ]);
            
            // Create GL double entry for this invoice (Dr AR, Cr Decoration Service Income)
            $this->postGlForDecorationInvoice($invoice);

            DB::commit();

            return redirect()->route('rental-event-equipment.decoration-invoices.show', $invoice)
                ->with('success', 'Decoration service invoice created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create service invoice: ' . $e->getMessage());
        }
    }

    public function show(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $invoice = DecorationInvoice::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })
            ->with(['customer', 'job', 'company', 'branch', 'creator'])
            ->findOrFail($id);

        return view('rental-event-equipment.decoration-invoices.show', compact('invoice'));
    }

    public function edit(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $invoice = DecorationInvoice::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })
            ->with(['job', 'customer'])
            ->findOrFail($id);

        if ($invoice->status !== 'draft') {
            return redirect()->route('rental-event-equipment.decoration-invoices.show', $encodedId)
                ->with('error', 'Only draft invoices can be edited.');
        }

        $customers = Customer::where('company_id', $companyId)
            ->when($branchId, function ($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $jobs = DecorationJob::forCompany($companyId)
            ->forBranch($branchId)
            ->with('customer')
            ->orderBy('job_number', 'desc')
            ->get();

        return view('rental-event-equipment.decoration-invoices.edit', compact('invoice', 'customers', 'jobs'));
    }

    public function update(Request $request, string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $invoice = DecorationInvoice::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })
            ->findOrFail($id);

        if ($invoice->status !== 'draft') {
            return redirect()->route('rental-event-equipment.decoration-invoices.show', $encodedId)
                ->with('error', 'Only draft invoices can be edited.');
        }

        $request->validate([
            'decoration_job_id' => 'nullable|exists:decoration_jobs,id',
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'reference' => 'nullable|string|max:255',
            'service_description' => 'nullable|string',
            'service_amount' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $serviceAmount = (float) $request->service_amount;
        $taxAmount = (float) ($request->tax_amount ?? 0);
        $totalAmount = $serviceAmount + $taxAmount;

        $invoice->update([
            'decoration_job_id' => $request->decoration_job_id,
            'customer_id' => $request->customer_id,
            'invoice_date' => $request->invoice_date,
            'due_date' => $request->due_date,
            'reference' => $request->reference,
            'service_description' => $request->service_description,
            'service_amount' => $serviceAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'notes' => $request->notes,
            'updated_by' => Auth::id(),
        ]);

        // Refresh GL entries to reflect updated amount
        $this->postGlForDecorationInvoice($invoice);

        return redirect()->route('rental-event-equipment.decoration-invoices.show', $encodedId)
            ->with('success', 'Decoration service invoice updated successfully.');
    }

    public function destroy(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $invoice = DecorationInvoice::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })
            ->findOrFail($id);

        if ($invoice->status !== 'draft') {
            return redirect()->route('rental-event-equipment.decoration-invoices.index')
                ->with('error', 'Only draft invoices can be deleted.');
        }

        // Remove any GL transactions linked to this invoice
        GlTransaction::where('transaction_type', 'decoration_invoice')
            ->where('transaction_id', $invoice->id)
            ->delete();

        $invoice->delete();

        return redirect()->route('rental-event-equipment.decoration-invoices.index')
            ->with('success', 'Decoration service invoice deleted successfully.');
    }

    /**
     * Post or refresh GL double-entry for a decoration invoice.
     *
     * Dr Accounts Receivable
     * Cr Decoration Service Income
     */
    protected function postGlForDecorationInvoice(DecorationInvoice $invoice): void
    {
        // Only post GL for positive amounts
        if ($invoice->total_amount <= 0) {
            GlTransaction::where('transaction_type', 'decoration_invoice')
                ->where('transaction_id', $invoice->id)
                ->delete();
            return;
        }

        $companyId = $invoice->company_id;
        $branchId = $invoice->branch_id;

        // Get accounting settings for this company / branch
        $settings = AccountingSetting::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })
            ->first();

        if (! $settings || ! $settings->accounts_receivable_account_id || ! $settings->service_income_account_id) {
            // If settings are incomplete, skip GL posting to avoid bad data
            return;
        }

        // Clear any existing GL entries for this invoice
        GlTransaction::where('transaction_type', 'decoration_invoice')
            ->where('transaction_id', $invoice->id)
            ->delete();

        $description = "Decoration service invoice {$invoice->invoice_number}";
        $userId = Auth::id();

        // Debit Accounts Receivable (customer owes us)
        GlTransaction::create([
            'chart_account_id' => $settings->accounts_receivable_account_id,
            'customer_id' => $invoice->customer_id,
            'supplier_id' => null,
            'amount' => $invoice->total_amount,
            'nature' => 'debit',
            'transaction_id' => $invoice->id,
            'transaction_type' => 'decoration_invoice',
            'date' => $invoice->invoice_date,
            'description' => $description,
            'branch_id' => $branchId,
            'user_id' => $userId,
        ]);

        // Credit Decoration Service Income
        GlTransaction::create([
            'chart_account_id' => $settings->service_income_account_id,
            'customer_id' => $invoice->customer_id,
            'supplier_id' => null,
            'amount' => $invoice->total_amount,
            'nature' => 'credit',
            'transaction_id' => $invoice->id,
            'transaction_type' => 'decoration_invoice',
            'date' => $invoice->invoice_date,
            'description' => $description,
            'branch_id' => $branchId,
            'user_id' => $userId,
        ]);
    }
}

