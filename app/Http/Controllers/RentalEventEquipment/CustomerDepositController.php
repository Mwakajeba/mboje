<?php

namespace App\Http\Controllers\RentalEventEquipment;

use App\Http\Controllers\Controller;
use App\Models\RentalEventEquipment\CustomerDeposit;
use App\Models\RentalEventEquipment\RentalContract;
use App\Models\RentalEventEquipment\AccountingSetting;
use App\Services\RentalEventEquipment\RentalApprovalService;
use App\Models\RentalEventEquipment\RentalApproval;
use App\Models\Customer;
use App\Models\BankAccount;
use App\Models\GlTransaction;
use App\Models\Journal;
use App\Models\JournalItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class CustomerDepositController extends Controller
{
    public function index()
    {
        return view('rental-event-equipment.customer-deposits.index');
    }

    public function data(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        // Get unique customers with deposits (including drafts)
        $customersQuery = \App\Models\Customer::whereHas('rentalCustomerDeposits', function ($q) use ($companyId, $branchId) {
            $q->where('company_id', $companyId)
              ->where(function ($query) use ($branchId) {
                  $query->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
              });
        })
        ->where('company_id', $companyId)
        ->when($branchId, function ($query) use ($branchId) {
            return $query->where('branch_id', $branchId);
            });

        return DataTables::of($customersQuery)
            ->addIndexColumn()
            ->addColumn('customer_name', function ($customer) use ($companyId, $branchId) {
                return $customer->name ?? 'N/A';
            })
            ->addColumn('customer_number', function ($customer) {
                return $customer->customerNo ?? 'N/A';
            })
            ->addColumn('total_deposited', function ($customer) use ($companyId, $branchId) {
                $total = CustomerDeposit::getTotalDepositsForCustomer($customer->id, $companyId, $branchId);
                return 'TZS ' . number_format($total, 2);
            })
            ->addColumn('total_used', function ($customer) use ($companyId, $branchId) {
                $total = CustomerDeposit::getTotalUsedDepositsForCustomer($customer->id, $companyId, $branchId);
                return 'TZS ' . number_format($total, 2);
            })
            ->addColumn('remaining_balance', function ($customer) use ($companyId, $branchId) {
                $deposited = CustomerDeposit::getTotalDepositsForCustomer($customer->id, $companyId, $branchId);
                $used = CustomerDeposit::getTotalUsedDepositsForCustomer($customer->id, $companyId, $branchId);
                $balance = $deposited - $used;
                $badgeClass = $balance > 0 ? 'success' : ($balance < 0 ? 'danger' : 'secondary');
                return '<span class="badge bg-' . $badgeClass . '">TZS ' . number_format($balance, 2) . '</span>';
            })
            ->addColumn('first_deposit_date', function ($customer) use ($companyId, $branchId) {
                $firstDeposit = CustomerDeposit::where('customer_id', $customer->id)
                    ->where('company_id', $companyId)
                    ->where(function ($q) use ($branchId) {
                        $q->where('branch_id', $branchId)
                          ->orWhereNull('branch_id');
                    })
                    ->orderBy('deposit_date', 'asc')
                    ->first();
                return $firstDeposit ? $firstDeposit->deposit_date->format('M d, Y') : 'N/A';
            })
            ->addColumn('last_deposit_date', function ($customer) use ($companyId, $branchId) {
                $lastDeposit = CustomerDeposit::where('customer_id', $customer->id)
                    ->where('company_id', $companyId)
                    ->where(function ($q) use ($branchId) {
                        $q->where('branch_id', $branchId)
                          ->orWhereNull('branch_id');
                    })
                    ->orderBy('deposit_date', 'desc')
                    ->first();
                return $lastDeposit ? $lastDeposit->deposit_date->format('M d, Y') : 'N/A';
            })
            ->addColumn('actions', function ($customer) {
                $encodedId = Hashids::encode($customer->id);
                $actions = '<div class="btn-group" role="group">';
                $actions .= '<a href="' . route('rental-event-equipment.customer-deposits.customer-detail', $encodedId) . '" class="btn btn-sm btn-outline-info" title="View Details">
                    <i class="bx bx-show"></i>
                </a>';
                $actions .= '<a href="' . route('rental-event-equipment.customer-deposits.export-customer-pdf', $encodedId) . '" class="btn btn-sm btn-outline-danger" title="Export PDF" target="_blank">
                    <i class="bx bx-file"></i>
                </a>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['remaining_balance', 'actions'])
            ->make(true);
    }

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

        $contracts = RentalContract::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with('customer')
            ->orderBy('contract_number', 'desc')
            ->get();

        $bankAccounts = BankAccount::whereHas('chartAccount.accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->where(function ($query) use ($branchId) {
                $query->where('is_all_branches', true);
                if ($branchId) {
                    $query->orWhere('branch_id', $branchId);
                }
            })
            ->with('chartAccount')
            ->orderBy('name')
            ->get();

        return view('rental-event-equipment.customer-deposits.create', compact('customers', 'contracts', 'bankAccounts'));
    }

    public function getContractsByCustomer($customerId)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $contracts = RentalContract::where('customer_id', $customerId)
            ->where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with('customer')
            ->orderBy('contract_number', 'desc')
            ->get(['id', 'contract_number', 'customer_id']);

        return response()->json($contracts);
    }

    public function store(Request $request)
    {
        $request->validate([
            'contract_id' => 'nullable|exists:rental_contracts,id',
            'customer_id' => 'required|exists:customers,id',
            'deposit_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:bank_transfer',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        DB::beginTransaction();
        try {
            $depositNumber = 'DEP-' . date('Y') . '-' . str_pad((CustomerDeposit::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT);

            // Handle attachment upload
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $attachmentPath = $file->store('customer-deposits', 'public');
            }

            $deposit = CustomerDeposit::create([
                'deposit_number' => $depositNumber,
                'contract_id' => $request->contract_id,
                'customer_id' => $request->customer_id,
                'deposit_date' => $request->deposit_date,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'bank_account_id' => $request->bank_account_id,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
                'attachment' => $attachmentPath,
                'status' => 'confirmed',
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'created_by' => Auth::id(),
            ]);

            // Initialize approval workflow
            try {
                $approvalService = app(RentalApprovalService::class);
                $approvalService->initializeApprovalWorkflow($deposit);
            } catch (\Exception $e) {
                // Log but don't fail the deposit creation
                \Log::warning('Failed to initialize approval workflow for deposit', [
                    'deposit_id' => $deposit->id,
                    'error' => $e->getMessage()
                ]);
            }

            // Only post to GL if status is NOT 'draft' (will be posted after approval)
            // GL transactions will be created when deposit is approved and status changes to 'confirmed'

            DB::commit();

            return redirect()->route('rental-event-equipment.customer-deposits.index')
                ->with('success', 'Customer deposit recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to record deposit: ' . $e->getMessage());
        }
    }

    public function show(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $deposit = CustomerDeposit::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with(['customer', 'contract', 'creator', 'bankAccount'])
            ->findOrFail($id);

        // Get deposit movement (invoices that used deposits from this contract)
        $depositMovements = collect();
        if ($deposit->contract_id) {
            $depositMovements = \App\Models\RentalEventEquipment\RentalInvoice::where('contract_id', $deposit->contract_id)
                ->where('deposit_applied', '>', 0)
                ->with(['customer', 'creator'])
                ->orderBy('invoice_date', 'desc')
                ->get();
        }

        // Get approval data
        $pendingApprovals = collect();
        $allApprovals = collect();
        $canApprove = false;
        $userApprovalLevel = null;

        try {
            $pendingApprovals = RentalApproval::where('approvable_type', CustomerDeposit::class)
                ->where('approvable_id', $deposit->id)
                ->where('status', RentalApproval::STATUS_PENDING)
                ->with('approver')
                ->get()
                ->groupBy('approval_level');

            $allApprovals = RentalApproval::where('approvable_type', CustomerDeposit::class)
                ->where('approvable_id', $deposit->id)
                ->with('approver')
                ->orderBy('approval_level')
                ->orderBy('created_at')
                ->get();

            // Check if current user can approve
            $user = Auth::user();
            $isSuperAdmin = $user->hasRole('super-admin') || $user->hasRole('Super Admin') || ($user->is_admin ?? false);

            if ($isSuperAdmin) {
                $canApprove = true;
                $userApprovalLevel = 1; // Default level for super admin
            } else {
                $approvalService = app(RentalApprovalService::class);
                foreach ($pendingApprovals as $level => $approvals) {
                    foreach ($approvals as $approval) {
                        if ($approvalService->canUserApprove($deposit, Auth::id(), $level)) {
                            $canApprove = true;
                            $userApprovalLevel = $level;
                            break 2;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Approval table might not exist, that's okay
            \Log::debug('Could not fetch approval data', ['error' => $e->getMessage()]);
        }

        return view('rental-event-equipment.customer-deposits.show', compact(
            'deposit',
            'pendingApprovals',
            'allApprovals',
            'canApprove',
            'userApprovalLevel',
            'depositMovements'
        ));
    }

    public function exportPdf(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        if (!$id) {
            abort(404);
        }

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $deposit = CustomerDeposit::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with(['customer', 'contract', 'bankAccount', 'company', 'branch'])
            ->findOrFail($id);

        // Get all deposit movements for this customer
        $movements = CustomerDeposit::getDepositMovementsForCustomer($deposit->customer_id, $companyId, $branchId);

        $company = $deposit->company;
        $branch = $deposit->branch;

        $pdf = \PDF::loadView('rental-event-equipment.customer-deposits.export-pdf', compact('deposit', 'company', 'branch', 'movements'));
        $filename = $deposit->deposit_number . '.pdf';
        return $pdf->download($filename);
    }

    public function edit(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $deposit = CustomerDeposit::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->findOrFail($id);

        // Allow editing confirmed deposits (since they're auto-confirmed now)
        // Only prevent editing if status is 'applied' or 'refunded'
        if (in_array($deposit->status, ['applied', 'refunded'])) {
            return redirect()->route('rental-event-equipment.customer-deposits.show', $deposit)
                ->with('error', 'Deposits with status "' . $deposit->status . '" cannot be edited.');
        }

        $customers = Customer::where('company_id', $companyId)
            ->when($branchId, function ($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $contracts = RentalContract::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with('customer')
            ->orderBy('contract_number', 'desc')
            ->get();

        $bankAccounts = BankAccount::whereHas('chartAccount.accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->where(function ($query) use ($branchId) {
                $query->where('is_all_branches', true);
                if ($branchId) {
                    $query->orWhere('branch_id', $branchId);
                }
            })
            ->with('chartAccount')
            ->orderBy('name')
            ->get();

        return view('rental-event-equipment.customer-deposits.edit', compact('deposit', 'customers', 'contracts', 'bankAccounts'));
    }

    public function update(Request $request, string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $deposit = CustomerDeposit::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->findOrFail($id);

        // Allow editing confirmed deposits (since they're auto-confirmed now)
        // Only prevent editing if status is 'applied' or 'refunded'
        if (in_array($deposit->status, ['applied', 'refunded'])) {
            return back()->with('error', 'Deposits with status "' . $deposit->status . '" cannot be edited.');
        }

        $request->validate([
            'contract_id' => 'nullable|exists:rental_contracts,id',
            'customer_id' => 'required|exists:customers,id',
            'deposit_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:bank_transfer',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);

        $updateData = $request->only([
            'contract_id',
            'customer_id',
            'deposit_date',
            'amount',
            'payment_method',
            'bank_account_id',
            'reference_number',
            'notes',
        ]);

        // Handle attachment upload
        if ($request->hasFile('attachment')) {
            // Delete old attachment if exists
            if ($deposit->attachment && \Storage::disk('public')->exists($deposit->attachment)) {
                \Storage::disk('public')->delete($deposit->attachment);
            }
            $file = $request->file('attachment');
            $attachmentPath = $file->store('customer-deposits', 'public');
            $updateData['attachment'] = $attachmentPath;
        }

        $deposit->update($updateData);

        return redirect()->route('rental-event-equipment.customer-deposits.index')
            ->with('success', 'Customer deposit updated successfully.');
    }

    public function destroy(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $deposit = CustomerDeposit::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->findOrFail($id);

        // Only allow deleting confirmed deposits (since they're auto-confirmed now)
        // Prevent deleting if status is 'applied' or 'refunded'
        if (in_array($deposit->status, ['applied', 'refunded'])) {
            return redirect()->route('rental-event-equipment.customer-deposits.index')
                ->with('error', 'Deposits with status "' . $deposit->status . '" cannot be deleted.');
        }

        $deposit->delete();

        return redirect()->route('rental-event-equipment.customer-deposits.index')
            ->with('success', 'Customer deposit deleted successfully.');
    }

    public function customerDetail(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $customerId = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $customer = Customer::where('company_id', $companyId)
            ->when($branchId, function ($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->findOrFail($customerId);

        // Get all deposits for this customer (including drafts)
        $deposits = CustomerDeposit::where('customer_id', $customerId)
            ->where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with(['contract', 'bankAccount', 'creator'])
            ->orderBy('deposit_date', 'desc')
            ->get();

        // Get all invoices that used deposits
        $invoices = \App\Models\RentalEventEquipment\RentalInvoice::where('customer_id', $customerId)
            ->where('deposit_applied', '>', 0)
            ->where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with(['contract', 'creator'])
            ->orderBy('invoice_date', 'desc')
            ->get();

        // Calculate totals
        $totalDeposited = CustomerDeposit::getTotalDepositsForCustomer($customerId, $companyId, $branchId);
        $totalUsed = CustomerDeposit::getTotalUsedDepositsForCustomer($customerId, $companyId, $branchId);
        $remainingBalance = $totalDeposited - $totalUsed;

        // Get all movements
        $movements = CustomerDeposit::getDepositMovementsForCustomer($customerId, $companyId, $branchId);

        return view('rental-event-equipment.customer-deposits.customer-detail', compact(
            'customer',
            'deposits',
            'invoices',
            'totalDeposited',
            'totalUsed',
            'remainingBalance',
            'movements'
        ));
    }

    public function exportCustomerPdf(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $customerId = $decoded[0] ?? null;

        if (!$customerId) {
            abort(404);
        }

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $customer = Customer::where('company_id', $companyId)
            ->when($branchId, function ($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->findOrFail($customerId);

        // Get all deposits (including drafts)
        $deposits = CustomerDeposit::where('customer_id', $customerId)
            ->where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with(['contract', 'bankAccount'])
            ->orderBy('deposit_date', 'asc')
            ->get();

        // Get all invoices that used deposits
        $invoices = \App\Models\RentalEventEquipment\RentalInvoice::where('customer_id', $customerId)
            ->where('deposit_applied', '>', 0)
            ->where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with(['contract'])
            ->orderBy('invoice_date', 'asc')
            ->get();

        // Calculate totals
        $totalDeposited = CustomerDeposit::getTotalDepositsForCustomer($customerId, $companyId, $branchId);
        $totalUsed = CustomerDeposit::getTotalUsedDepositsForCustomer($customerId, $companyId, $branchId);
        $remainingBalance = $totalDeposited - $totalUsed;

        // Get all movements
        $movements = CustomerDeposit::getDepositMovementsForCustomer($customerId, $companyId, $branchId);

        $company = $customer->company;
        $branch = $customer->branch;

        $pdf = \PDF::loadView('rental-event-equipment.customer-deposits.export-customer-pdf', compact(
            'customer',
            'deposits',
            'invoices',
            'totalDeposited',
            'totalUsed',
            'remainingBalance',
            'movements',
            'company',
            'branch'
        ));
        $filename = 'Customer_Deposit_Statement_' . $customer->customerNo . '.pdf';
        return $pdf->download($filename);
    }
}
