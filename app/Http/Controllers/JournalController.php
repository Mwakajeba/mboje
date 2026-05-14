<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\ChartAccount;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\GlTransaction;
use App\Models\Sales\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PDF;

class JournalController extends Controller
{
    public function index()
    {
        // Permission: view journals
        abort_unless(auth()->user()->can('view journals') || auth()->user()->can('view journal details'), 403);

        // Load stats filtered by current branch (for initial card display)
        $user = Auth::user();
        $resolvedBranchId = session('branch_id') ?? ($user->branch_id ?? null);

        $query = Journal::with('items', 'user');
        if ($resolvedBranchId) {
            $query->where('branch_id', $resolvedBranchId);
        }
        $journals = $query->latest()->take(100)->get();

        return view('accounting.journals.index', compact('journals'));
    }

    /**
     * Get statistics for cards (AJAX endpoint)
     */
    public function statistics(Request $request)
    {
        // Permission: view journals statistics (use same as view journals)
        abort_unless(auth()->user()->can('view journals') || auth()->user()->can('view journal details'), 403);

        $user = Auth::user();
        $branchId = $request->get('branch_id');
        $resolvedBranchId = session('branch_id') ?? ($user->branch_id ?? null);

        $query = Journal::query();

        if ($branchId && $branchId !== 'all' && $branchId !== 'default') {
            $query->where('branch_id', $branchId);
        } elseif ($branchId !== 'all' && $resolvedBranchId) {
            $query->where('branch_id', $resolvedBranchId);
        }

        $journals = $query->get();

        return response()->json([
            'total_entries' => $journals->count(),
            'total_debit' => $journals->sum('debit_total'),
            'total_credit' => $journals->sum('credit_total'),
            'balance' => $journals->sum('balance'),
            'is_balanced' => $journals->sum('balance') == 0,
        ]);
    }

    /**
     * Data endpoint for Journals DataTable (AJAX)
     */
    public function data(Request $request)
    {
        // Permission: view journals list
        abort_unless(auth()->user()->can('view journals') || auth()->user()->can('view journal details'), 403);

        $query = Journal::with(['user', 'items'])->latest();

        // Branch filter: default to current session/user branch if set, unless "all" is explicitly requested
        $user = Auth::user();
        $branchId = $request->get('branch_id');
        $resolvedBranchId = session('branch_id') ?? ($user->branch_id ?? null);

        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        } elseif ($resolvedBranchId) {
            $query->where('branch_id', $resolvedBranchId);
        }

        // Global search
        if ($request->has('search') && !empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $totalRecords = Journal::count();
        $filteredRecords = $query->count();

        // Ordering – only allow ordering by reference (col 1) or date (col 2)
        if ($request->has('order') && !empty($request->order)) {
            $orderColumn = $request->order[0]['column'] ?? 1;
            $orderDir = $request->order[0]['dir'] ?? 'asc';

            if ($orderColumn == 1) {
                $query->orderBy('reference', $orderDir);
            } elseif ($orderColumn == 2) {
                $query->orderBy('date', $orderDir);
            } else {
                $query->orderBy('date', 'desc');
            }
        } else {
            $query->orderBy('date', 'desc');
        }

        $start = (int) ($request->start ?? 0);
        $length = (int) ($request->length ?? 10);
        $journals = $query->skip($start)->take($length)->get();

        $data = [];
        foreach ($journals as $index => $journal) {
            $rowNumber = $start + $index + 1;

            $balanceBadge = $journal->balance == 0
                ? '<span class="badge bg-success">Balanced</span>'
                : '<span class="badge bg-warning">TZS ' . number_format(abs($journal->balance), 2) . '</span>';

            // Determine overall status badge
            if ($journal->approved) {
                $statusBadge = '<span class="badge bg-success"><i class="bx bx-check-circle me-1"></i>Approved</span>';
            } elseif ($journal->isRejected()) {
                $statusBadge = '<span class="badge bg-danger"><i class="bx bx-x-circle me-1"></i>Rejected</span>';
            } else {
                $statusBadge = '<span class="badge bg-warning"><i class="bx bx-time me-1"></i>Pending Approval</span>';
            }

            $actions = '<div class="btn-group">';

            // View action
            if (auth()->user()->can('view journal details') || auth()->user()->can('view journals')) {
                $actions .= '<a href="' . route('accounting.journals.show', $journal) . '" class="btn btn-sm btn-primary" title="View Details">'
                    . '<i class="bx bx-show"></i></a>';
            }

            // Edit action
            if (auth()->user()->can('edit journal')) {
                $actions .= '<a href="' . route('accounting.journals.edit', $journal) . '" class="btn btn-sm btn-warning" title="Edit">'
                    . '<i class="bx bx-edit"></i></a>';
            }

            // Delete action
            if (auth()->user()->can('delete journal')) {
                $actions .= '<button type="button" class="btn btn-sm btn-danger" title="Delete" '
                    . 'onclick="confirmDelete(\'' . route('accounting.journals.destroy', $journal) . '\')">'
                    . '<i class="bx bx-trash"></i></button>';
            }

            $actions .= '</div>';

            $data[] = [
                'index' => $rowNumber,
                'reference' => e($journal->reference ?? 'N/A'),
                'date' => $journal->date ? $journal->date->format('M d, Y') : 'N/A',
                'debit' => 'TZS ' . number_format($journal->debit_total, 2),
                'credit' => 'TZS ' . number_format($journal->credit_total, 2),
                'balance_badge' => $balanceBadge,
                'status' => $statusBadge,
                'created_by' => e($journal->user->name ?? 'N/A'),
                'actions' => $actions,
            ];
        }

        return response()->json([
            'draw' => intval($request->draw ?? 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    public function create()
    {
        // Permission: create journal
        abort_unless(auth()->user()->can('create journal') || auth()->user()->can('create journal entries'), 403);

        $branches = Branch::all();
        $customers = Customer::all();
        $accounts = ChartAccount::all();
        return view('accounting.journals.create', compact('accounts', 'customers', 'branches'));
    }

    public function store(Request $request)
    {
        // Permission: create journal
        abort_unless(auth()->user()->can('create journal') || auth()->user()->can('create journal entries'), 403);

        try {
            $validated = $request->validate([
                'date' => 'required|date',
                'description' => 'nullable|string',
                'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048',
                'items' => 'required|array|min:2', // Must have both debit and credit
                'items.*.account_id' => 'required|exists:chart_accounts,id',
                'items.*.amount' => 'required|numeric|min:0.01',
                'items.*.nature' => 'required|in:debit,credit',
                'items.*.description' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }

        // Validate that debits equal credits
        $debitTotal = 0;
        $creditTotal = 0;
        foreach ($request->items as $item) {
            if ($item['nature'] === 'debit') {
                $debitTotal += $item['amount'];
            } else {
                $creditTotal += $item['amount'];
            }
        }

        if ($debitTotal !== $creditTotal) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debits must equal Credits',
                    'errors' => ['items' => ['Debits (' . number_format($debitTotal, 2) . ') must equal Credits (' . number_format($creditTotal, 2) . ')']]
                ], 422);
            }
            return redirect()->back()
                ->withErrors(['items' => 'Debits must equal Credits'])
                ->withInput();
        }

        try {
            DB::transaction(function () use ($request) {
                // Generate a unique reference
                $nextId = Journal::max('id') + 1;
                $reference = 'JRN-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

                // Resolve branch id from session or user
                $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);

                // Handle file upload
                $attachmentPath = null;
                if ($request->hasFile('attachment')) {
                    $file = $request->file('attachment');
                    $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $attachmentPath = $file->storeAs('journal-attachments', $fileName, 'public');
                }

                $journal = Journal::create([
                    'date' => $request->date,
                    'description' => $request->description,
                    'branch_id' => $branchId,
                    'user_id' => Auth::id(),
                    'reference_type' => 'Journal',
                    'reference' => $reference,
                    'attachment' => $attachmentPath,
                ]);

                foreach ($request->items as $item) {
                    $journal->items()->create([
                        'chart_account_id' => $item['account_id'],
                        'amount' => $item['amount'],
                        'nature' => $item['nature'],
                        'description' => $item['description'] ?? null,
                    ]);
                }

                // Initialize approval workflow (will create GL transactions if auto-approved)
                $journal->initializeApprovalWorkflow();

                // Log activity for journal creation
                if ($journal->approved) {
                    $journal->logActivity('post', "Posted Journal Entry {$journal->reference} to General Ledger", [
                        'Journal Reference' => $journal->reference,
                        'Date' => $journal->date->format('Y-m-d'),
                        'Total Amount' => number_format($journal->total, 2),
                        'Description' => $journal->description ?? 'N/A',
                        'Reference Type' => $journal->reference_type ?? 'N/A',
                        'Items Count' => count($request->items),
                        'Branch' => $journal->branch ? $journal->branch->name : 'N/A',
                        'Posted By' => Auth::user()->name,
                        'Posted At' => now()->format('Y-m-d H:i:s')
                    ]);
                } else {
                    $journal->logActivity('create', "Created Journal Entry {$journal->reference} - Pending Approval", [
                        'Journal Reference' => $journal->reference,
                        'Date' => $journal->date->format('Y-m-d'),
                        'Total Amount' => number_format($journal->total, 2),
                        'Description' => $journal->description ?? 'N/A',
                        'Items Count' => count($request->items),
                        'Status' => 'Pending Approval'
                    ]);
                }
            });
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            // Check if it's a reconciliation error
            if (str_contains($errorMessage, 'completed reconciliation period')) {
                $errorMessage = 'Cannot post: Journal entry chart account is in a completed reconciliation period';
            } else {
                $errorMessage = 'Failed to create journal entry: ' . $errorMessage;
            }

            // For AJAX requests, return JSON with SweetAlert-friendly format
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'swal' => true, // Flag to indicate this should be shown as SweetAlert
                    'icon' => 'error'
                ], 422);
            }

            return redirect()->back()
                ->with('error', $errorMessage)
                ->withInput();
        }

        // If AJAX request or reconciliation_id is present, return JSON or redirect to reconciliation
        if ($request->ajax() || $request->wantsJson()) {
            $redirectUrl = route('accounting.journals.index');

            // If reconciliation_id is present, redirect to bank reconciliation page
            if ($request->has('reconciliation_id') && $request->reconciliation_id) {
                $redirectUrl = route('accounting.bank-reconciliation.show', \App\Helpers\HashIdHelper::encode($request->reconciliation_id));
            }

            return response()->json([
                'success' => true,
                'message' => 'Journal entry created successfully.',
                'redirect_url' => $redirectUrl,
            ]);
        }

        // If reconciliation_id is present, redirect to bank reconciliation page
        if ($request->has('reconciliation_id') && $request->reconciliation_id) {
            return redirect()->route('accounting.bank-reconciliation.show', \App\Helpers\HashIdHelper::encode($request->reconciliation_id))
                ->with('success', 'Journal entry created successfully.');
        }

        return redirect()->route('accounting.journals.index')->with('success', 'Journal entry created.');
    }

    public function show(Journal $journal)
    {
        // Permission: view single journal
        abort_unless(auth()->user()->can('view journal details') || auth()->user()->can('view journals'), 403);

        $journal->load('items.chartAccount', 'approvals.approver', 'approvedBy', 'user');

        // Load approval settings to check if user can approve
        $settings = \App\Models\JournalEntryApprovalSetting::where('company_id', Auth::user()->company_id)->first();
        $currentApproval = $journal->currentApproval();
        $canApprove = false;

        // If journal is not approved and has no approvals, check if workflow should be initialized
        if (!$journal->approved && $journal->approvals->count() === 0 && $settings) {
            // Try to initialize approval workflow if it wasn't done during creation
            try {
                $journal->initializeApprovalWorkflow();
                $journal->refresh();
                $journal->load('approvals.approver');
                $currentApproval = $journal->currentApproval();
            } catch (\Exception $e) {
                \Log::error('Failed to initialize approval workflow', [
                    'journal_id' => $journal->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Debug: Log the values to help troubleshoot
        \Log::info('Journal Approval Debug', [
            'journal_id' => $journal->id,
            'journal_approved' => $journal->approved,
            'has_settings' => $settings ? true : false,
            'has_current_approval' => $currentApproval ? true : false,
            'current_approval_level' => $currentApproval ? $currentApproval->approval_level : null,
            'current_approval_status' => $currentApproval ? $currentApproval->status : null,
            'user_id' => Auth::user()->id,
            'approvals_count' => $journal->approvals->count(),
            'pending_approvals_count' => $journal->approvals->where('status', 'pending')->count(),
        ]);

        if ($settings && $currentApproval) {
            $canApprove = $settings->canUserApproveAtLevel(Auth::user(), $currentApproval->approval_level);

            // Debug: Log permission check
            \Log::info('Permission Check', [
                'user_id' => Auth::user()->id,
                'level' => $currentApproval->approval_level,
                'approval_type' => $settings->{"level{$currentApproval->approval_level}_approval_type"} ?? null,
                'approvers' => $settings->{"level{$currentApproval->approval_level}_approvers"} ?? null,
                'can_approve' => $canApprove,
            ]);
        }

        return view('accounting.journals.show', compact('journal', 'settings', 'currentApproval', 'canApprove'));
    }

    /**
     * Show approval page for journal entry
     */
    public function showApproval(Journal $journal)
    {
        $user = Auth::user();

        $settings = \App\Models\JournalEntryApprovalSetting::where('company_id', $user->company_id)->first();

        if (!$settings) {
            return redirect()->back()->withErrors(['error' => 'No approval settings configured.']);
        }

        $currentApproval = $journal->currentApproval();

        if (!$currentApproval) {
            return redirect()->back()->withErrors(['error' => 'No pending approval found for this journal entry.']);
        }

        // Check if current user can approve at this level
        if (!$settings->canUserApproveAtLevel($user, $currentApproval->approval_level)) {
            return redirect()->back()->withErrors(['error' => 'You do not have permission to approve this journal entry.']);
        }

        $journal->load('items.chartAccount', 'approvals.approver', 'user', 'branch');

        return view('accounting.journals.approval', compact('journal', 'currentApproval', 'settings'));
    }

    /**
     * Approve journal entry
     */
    public function approve(Request $request, Journal $journal)
    {
        $user = Auth::user();

        $settings = \App\Models\JournalEntryApprovalSetting::where('company_id', $user->company_id)->first();

        if (!$settings) {
            return redirect()->back()->withErrors(['error' => 'No approval settings configured.']);
        }

        $currentApproval = $journal->currentApproval();

        if (!$currentApproval) {
            return redirect()->back()->withErrors(['error' => 'No pending approval found for this journal entry.']);
        }

        // Check if current user can approve at this level
        if (!$settings->canUserApproveAtLevel($user, $currentApproval->approval_level)) {
            return redirect()->back()->withErrors(['error' => 'You do not have permission to approve this journal entry.']);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            // Update current approval
            $currentApproval->update([
                'status' => 'approved',
                'approver_id' => $user->id,
                'notes' => $validated['notes'] ?? null,
                'approved_at' => now(),
            ]);

            // Check if this was the last required approval
            if ($journal->isFullyApproved()) {
                // Mark journal as approved and create GL transactions
                $journal->update([
                    'approved' => true,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);

                // Create GL transactions
                $journal->createGlTransactions();

                // Log activity
                $journal->logActivity('approve', "Journal Entry {$journal->reference} Approved and Posted to GL", [
                    'Journal Reference' => $journal->reference,
                    'Approved By' => $user->name,
                    'Approved At' => now()->format('Y-m-d H:i:s'),
                ]);
            } else {
                // Log partial approval
                $journal->logActivity('approve', "Journal Entry {$journal->reference} - Level {$currentApproval->approval_level} Approved", [
                    'Journal Reference' => $journal->reference,
                    'Approval Level' => $currentApproval->approval_level,
                    'Approved By' => $user->name,
                    'Approved At' => now()->format('Y-m-d H:i:s'),
                ]);
            }

            DB::commit();

            return redirect()->route('accounting.journals.show', $journal)
                ->with('success', 'Journal entry approved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Journal Approval Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to approve journal entry: ' . $e->getMessage()]);
        }
    }

    /**
     * Reject journal entry
     */
    public function reject(Request $request, Journal $journal)
    {
        $user = Auth::user();

        $settings = \App\Models\JournalEntryApprovalSetting::where('company_id', $user->company_id)->first();

        if (!$settings) {
            return redirect()->back()->withErrors(['error' => 'No approval settings configured.']);
        }

        $currentApproval = $journal->currentApproval();

        if (!$currentApproval) {
            return redirect()->back()->withErrors(['error' => 'No pending approval found for this journal entry.']);
        }

        // Check if current user can approve at this level
        if (!$settings->canUserApproveAtLevel($user, $currentApproval->approval_level)) {
            return redirect()->back()->withErrors(['error' => 'You do not have permission to reject this journal entry.']);
        }

        $validated = $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            // Update current approval to rejected
            $currentApproval->update([
                'status' => 'rejected',
                'approver_id' => $user->id,
                'notes' => $validated['notes'],
                'rejected_at' => now(),
            ]);

            // Log rejection
            $journal->logActivity('reject', "Journal Entry {$journal->reference} Rejected", [
                'Journal Reference' => $journal->reference,
                'Rejection Reason' => $validated['notes'],
                'Rejected By' => $user->name,
                'Rejected At' => now()->format('Y-m-d H:i:s'),
            ]);

            DB::commit();

            return redirect()->route('accounting.journals.show', $journal)
                ->with('success', 'Journal entry rejected.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Journal Rejection Error: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Failed to reject journal entry: ' . $e->getMessage()]);
        }
    }

    public function edit(Journal $journal)
    {
        // Permission: edit journal
        abort_unless(auth()->user()->can('edit journal') || auth()->user()->can('edit journal entries'), 403);

        $accounts = ChartAccount::all();
        $journal->load('items');
        return view('accounting.journals.edit', compact('journal', 'accounts'));
    }

    public function update(Request $request, Journal $journal)
    {
        // Permission: edit journal
        abort_unless(auth()->user()->can('edit journal') || auth()->user()->can('edit journal entries'), 403);

        $request->validate([
            'date' => 'required|date',
            'description' => 'nullable|string',
            'items' => 'required|array|min:2',
            'items.*.account_id' => 'required|exists:chart_accounts,id',
            'items.*.amount' => 'required|numeric|min:0.01',
            'items.*.nature' => 'required|in:debit,credit',
            'items.*.description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $journal) {
            // Resolve branch id from session or user
            $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);

            $journal->update([
                'date' => $request->date,
                'description' => $request->description,
                'branch_id' => $branchId,
            ]);

            // Delete old GL transactions for this journal
            GlTransaction::where('transaction_id', $journal->id)
                ->where('transaction_type', 'journal')
                ->delete();

            $journal->items()->delete(); // Remove old items
            foreach ($request->items as $item) {
                $journal->items()->create([
                    'chart_account_id' => $item['account_id'],
                    'amount' => $item['amount'],
                    'nature' => $item['nature'],
                    'description' => $item['description'] ?? null,
                ]);

                // Create new GL transaction for each journal item
                GlTransaction::create([
                    'chart_account_id' => $item['account_id'],
                    'customer_id' => null,
                    'supplier_id' => null,
                    'amount' => $item['amount'],
                    'nature' => $item['nature'],
                    'transaction_id' => $journal->id,
                    'transaction_type' => 'journal',
                    'date' => $request->date,
                    'description' => $item['description'] ?? $request->description,
                    'branch_id' => $branchId,
                    'user_id' => Auth::id(),
                ]);
            }
        });

        return redirect()->route('accounting.journals.index')->with('success', 'Journal entry updated.');
    }

    public function destroy(Journal $journal)
    {
        // Permission: delete journal
        abort_unless(auth()->user()->can('delete journal') || auth()->user()->can('delete journal entries'), 403);

        DB::transaction(function () use ($journal) {
            if ($journal->reference_type === 'sales_invoice_payment') {
                $invoice = SalesInvoice::where('invoice_number', $journal->reference)->first();
                if ($invoice && (int) $invoice->company_id === (int) auth()->user()->company_id) {
                    $invoice->reverseCashDepositJournalPayment($journal);
                }
            }

            // Delete associated GL transactions
            GlTransaction::where('transaction_id', $journal->id)
                ->where('transaction_type', 'journal')
                ->delete();

            // Delete journal items
            $journal->items()->delete();

            // Delete the journal
            $journal->delete();
        });

        return redirect()->route('accounting.journals.index')->with('success', 'Journal entry deleted.');
    }

    public function exportPdf(Journal $journal)
    {
        $journal->load(['items.chartAccount', 'user.company', 'branch', 'approvedBy']);

        $pdf = PDF::loadView('accounting.journals.pdf', compact('journal'));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('journal-' . $journal->reference . '.pdf');
    }
}
