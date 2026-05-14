<?php

namespace App\Http\Controllers\Accounting\Reports;

use App\Http\Controllers\Controller;
use App\Models\BankReconciliation;
use App\Models\BankAccount;
use App\Models\BankReconciliationItem;
use App\Services\UnclearedItemsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class BankReconciliationReportController extends Controller
{
    /**
     * Display the bank reconciliation reports index (listing all reports).
     */
    public function reportsIndex()
    {
        if (!auth()->user()->can('view bank reconciliation report')) {
            abort(403, 'Unauthorized access to this report.');
        }
        
        return view('accounting.reports.bank-reconciliation.reports-index');
    }

    /**
     * Display the bank reconciliation report index.
     */
    public function index()
    {
        if (!auth()->user()->can('view bank reconciliation report')) {
            abort(403, 'Unauthorized access to this report.');
        }
        
        $user = Auth::user();

        // Get bank accounts for the current company
        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->orderBy('name')
            ->get();

        // Get recent reconciliations for statistics
        $recentReconciliations = BankReconciliation::with(['bankAccount', 'user'])
            ->whereHas('bankAccount.chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->orderBy('reconciliation_date', 'desc')
            ->limit(5)
            ->get();

        // Calculate statistics
        $stats = [
            'total_reconciliations' => BankReconciliation::whereHas('bankAccount.chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })->count(),
            'completed_reconciliations' => BankReconciliation::whereHas('bankAccount.chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })->where('status', 'completed')->count(),
            'pending_reconciliations' => BankReconciliation::whereHas('bankAccount.chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })->whereIn('status', ['draft', 'in_progress'])->count(),
        ];

        return view('accounting.reports.bank-reconciliation.index', compact('bankAccounts', 'recentReconciliations', 'stats'));
    }

    /**
     * Generate and display the bank reconciliation report.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:draft,in_progress,completed,cancelled',
            'export_format' => 'nullable|in:pdf,excel',
        ]);

        $user = Auth::user();
        $query = BankReconciliation::with([
            'bankAccount.chartAccount',
            'user',
            'branch',
            'reconciliationItems.glTransaction.chartAccount',
            'reconciliationItems.matchedWithItem',
            'reconciliationItems.reconciledBy'
        ])
        ->whereHas('bankAccount.chartAccount.accountClassGroup', function ($query) use ($user) {
            $query->where('company_id', $user->company_id);
        });

        // Apply filters
        if ($request->filled('bank_account_id')) {
            $query->where('bank_account_id', $request->bank_account_id);
        }

        if ($request->filled('start_date')) {
            $query->where('reconciliation_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('reconciliation_date', '<=', $request->end_date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reconciliations = $query->orderBy('reconciliation_date', 'desc')->get();

        // Get filter data for the view
        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->orderBy('name')
            ->get();

        $filters = $request->only(['bank_account_id', 'start_date', 'end_date', 'status']);

        // Calculate report statistics
        $reportStats = [
            'total_reconciliations' => $reconciliations->count(),
            'total_bank_balance' => $reconciliations->sum('bank_statement_balance'),
            'total_book_balance' => $reconciliations->sum('book_balance'),
            'total_difference' => $reconciliations->sum('difference'),
            'completed_count' => $reconciliations->where('status', 'completed')->count(),
            'pending_count' => $reconciliations->whereIn('status', ['draft', 'in_progress'])->count(),
        ];

        // If PDF export is requested
        if ($request->export_format === 'pdf') {
            return $this->exportToPdf($reconciliations, $filters, $reportStats, $user);
        }

        return view('accounting.reports.bank-reconciliation.generate', compact(
            'reconciliations',
            'bankAccounts',
            'filters',
            'reportStats'
        ));
    }

    /**
     * Export bank reconciliation report to PDF.
     */
    private function exportToPdf($reconciliations, $filters, $reportStats, $user)
    {
        $company = $user->company;
        $branch = $user->branch;

        // Prepare data for PDF
        $pdfData = [
            'reconciliations' => $reconciliations,
            'filters' => $filters,
            'reportStats' => $reportStats,
            'company' => $company,
            'branch' => $branch,
            'user' => $user,
            'generated_at' => now(),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('accounting.reports.bank-reconciliation.pdf', $pdfData);

        // Set PDF options
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOption('margin-top', 10);
        $pdf->setOption('margin-right', 10);
        $pdf->setOption('margin-bottom', 10);
        $pdf->setOption('margin-left', 10);

        // Generate filename
        $filename = 'bank_reconciliation_report_' . date('Y-m-d_H-i-s') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Show detailed reconciliation report for a specific reconciliation.
     */
    public function show(BankReconciliation $bankReconciliation)
    {
        $user = Auth::user();

        // Check if user has access to this reconciliation
        if ($bankReconciliation->bankAccount->chartAccount->accountClassGroup->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this reconciliation.');
        }

        $bankReconciliation->load([
            'bankAccount.chartAccount',
            'user',
            'branch',
            'reconciliationItems.glTransaction.chartAccount',
            'reconciliationItems.matchedWithItem',
            'reconciliationItems.reconciledBy'
        ]);

        // Get unreconciled items
        $unreconciledBankItems = $bankReconciliation->reconciliationItems()
            ->where('is_bank_statement_item', true)
            ->where('is_reconciled', false)
            ->get();

        $unreconciledBookItems = $bankReconciliation->reconciliationItems()
            ->where('is_book_entry', true)
            ->where('is_reconciled', false)
            ->get();

        $reconciledItems = $bankReconciliation->reconciliationItems()
            ->where('is_reconciled', true)
            ->with(['matchedWithItem', 'reconciledBy'])
            ->get();

        return view('accounting.reports.bank-reconciliation.show', compact(
            'bankReconciliation',
            'unreconciledBankItems',
            'unreconciledBookItems',
            'reconciledItems'
        ));
    }

    /**
     * Export specific reconciliation to PDF.
     */
    public function exportReconciliation(BankReconciliation $bankReconciliation, Request $request)
    {
        $user = Auth::user();

        // Check if user has access to this reconciliation
        if ($bankReconciliation->bankAccount->chartAccount->accountClassGroup->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this reconciliation.');
        }

        $bankReconciliation->load([
            'bankAccount.chartAccount',
            'user',
            'branch',
            'reconciliationItems.glTransaction.chartAccount',
            'reconciliationItems.matchedWithItem',
            'reconciliationItems.reconciledBy'
        ]);

        // Get items - match exactly with screen report logic
        // Get unreconciled items (not in balance)
        $unreconciledBankItems = $bankReconciliation->reconciliationItems()
            ->where('is_bank_statement_item', true)
            ->where('is_reconciled', false)
            ->orderBy('transaction_date', 'asc')
            ->get();

        $unreconciledBookItems = $bankReconciliation->reconciliationItems()
            ->where('is_book_entry', true)
            ->where('is_reconciled', false)
            ->orderBy('transaction_date', 'asc')
            ->get();

        // Get reconciled pairs - show only the book entry side for clarity (matching screen report)
        $reconciledItems = $bankReconciliation->reconciliationItems()
            ->where('is_reconciled', true)
            ->where('is_book_entry', true)  // Only book entries (matching screen report)
            ->with(['matchedWithItem', 'reconciledBy'])
            ->orderBy('reconciled_at', 'desc')
            ->get();  // No limit in PDF - show all reconciled items
        
        // Get uncleared items summary (DNC, UPC, Brought Forward) for PDF
        $unclearedItemsService = app(UnclearedItemsService::class);
        $unclearedItemsSummary = $unclearedItemsService->getUnclearedItemsSummary($bankReconciliation);
        
        // Get brought forward items
        $broughtForwardItems = $bankReconciliation->reconciliationItems()
            ->where('is_brought_forward', true)
            ->where('uncleared_status', 'UNCLEARED')
            ->with(['originReconciliation', 'broughtForwardFromItem'])
            ->orderBy('origin_date', 'asc')
            ->get();

        $company = $user->company;
        $branch = $user->branch;

        // Prepare data for PDF - match screen report data structure
        $pdfData = [
            'bankReconciliation' => $bankReconciliation,
            'unreconciledBankItems' => $unreconciledBankItems,
            'unreconciledBookItems' => $unreconciledBookItems,
            'reconciledItems' => $reconciledItems,
            'unclearedItemsSummary' => $unclearedItemsSummary,
            'broughtForwardItems' => $broughtForwardItems,
            'company' => $company,
            'branch' => $branch,
            'user' => $user,
            'generated_at' => now(),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('accounting.reports.bank-reconciliation.reconciliation-pdf', $pdfData);

        // Set PDF options
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOption('margin-top', 10);
        $pdf->setOption('margin-right', 10);
        $pdf->setOption('margin-bottom', 10);
        $pdf->setOption('margin-left', 10);

        // Generate filename
        $filename = 'bank_reconciliation_' . $bankReconciliation->bankAccount->name . '_' . $bankReconciliation->reconciliation_date->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Unreconciled Items Aging Report - Updated to match user specifications
     */
    public function unreconciledItemsAging(Request $request)
    {
        $user = Auth::user();

        // Get filters with defaults
        $bankAccountId = $request->get('bank_account_id');
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));

        // Build query for unreconciled items (both book entries and bank statement items)
        $query = BankReconciliationItem::whereHas('bankReconciliation.bankAccount.chartAccount.accountClassGroup', function ($q) use ($user) {
            $q->where('company_id', $user->company_id);
        })
        ->where('is_reconciled', false);

        if ($bankAccountId) {
            $query->whereHas('bankReconciliation', function ($q) use ($bankAccountId) {
                $q->where('bank_account_id', $bankAccountId);
            });
        }

        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        $items = $query->with([
            'bankReconciliation.bankAccount',
            'glTransaction',
            'reconciledBy'
        ])
        ->orderBy('transaction_date', 'asc')
        ->get();

        // Calculate aging and format data
        $reportItems = [];
        foreach ($items as $item) {
            $item->calculateAging();
            
            // Determine type (Deposit/Payment)
            $type = 'Payment';
            if ($item->is_book_entry && $item->nature === 'debit') {
                $type = 'Deposit';
            } elseif ($item->is_book_entry && $item->nature === 'credit') {
                $type = 'Payment';
            } elseif ($item->is_bank_statement_item && $item->nature === 'credit') {
                $type = 'Deposit';
            } elseif ($item->is_bank_statement_item && $item->nature === 'debit') {
                $type = 'Payment';
            }
            
            // Determine status
            // For book entries: Payments show "Payments Unpresented", Deposits show "Receipt Undeposited"
            // For bank statement items: Show "Not in Books"
            if ($item->is_bank_statement_item) {
                $status = 'Not in Books';
            } elseif ($item->is_book_entry) {
                if ($type === 'Payment') {
                    $status = 'Unpresented';
                } else {
                    $status = 'Undeposited';
                }
            } else {
                $status = 'Unpresented';
            }
            
            $reportItems[] = [
                'date' => $item->transaction_date,
                'description' => $item->description,
                'reference' => $item->reference ?? '-',
                'cash_book_amount' => $item->is_book_entry ? ($item->nature === 'debit' ? $item->amount : -$item->amount) : null,
                'bank_statement_amount' => $item->is_bank_statement_item ? ($item->nature === 'credit' ? $item->amount : -$item->amount) : null,
                'type' => $type,
                'aging_days' => $item->age_days ?? 0,
                'status' => $status,
                'item' => $item,
            ];
        }

        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->orderBy('name')
            ->get();

        $selectedBankAccount = $bankAccountId ? BankAccount::find($bankAccountId) : null;

        // Export to PDF if requested
        if ($request->get('export') === 'pdf') {
            $pdf = Pdf::loadView('accounting.reports.bank-reconciliation.unreconciled-items-aging-pdf', compact(
                'reportItems',
                'user',
                'bankAccounts',
                'selectedBankAccount',
                'startDate',
                'endDate'
            ));
            $pdf->setPaper('A4', 'landscape');
            return $pdf->download('unreconciled_items_aging_' . date('Y-m-d') . '.pdf');
        }

        return view('accounting.reports.bank-reconciliation.unreconciled-items-aging', compact(
            'reportItems',
            'bankAccounts',
            'selectedBankAccount',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Cleared Transactions Report
     * 
     * Shows ONLY items that were outstanding in the previous month's bank reconciliation
     * but have been cleared in the current month
     */
    public function clearedTransactions(Request $request)
    {
        $user = Auth::user();

        // Get filters with defaults
        $bankAccountId = $request->get('bank_account_id');
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));

        // Build query for items that:
        // 1. Were outstanding in the previous month (have origin_reconciliation_id or is_brought_forward = true)
        // 2. Have been cleared in the current month (uncleared_status = 'CLEARED' and clearing_month in current period)
        $query = BankReconciliationItem::whereHas('bankReconciliation.bankAccount.chartAccount.accountClassGroup', function ($q) use ($user) {
            $q->where('company_id', $user->company_id);
        })
        ->where('uncleared_status', 'CLEARED') // Only cleared items
        ->where('is_book_entry', true) // Only book entries (DNC/UPC items)
        ->where(function ($q) {
            // Items that were outstanding from previous month:
            // - Either have origin_reconciliation_id (pointing to previous reconciliation)
            // - Or is_brought_forward = true (carried forward from previous month)
            $q->whereNotNull('origin_reconciliation_id')
              ->orWhere('is_brought_forward', true);
        })
        ->whereNotNull('clearing_month') // Must have a clearing month
        ->whereNotNull('clearing_date'); // Must have a clearing date

        if ($bankAccountId) {
            $query->whereHas('bankReconciliation', function ($q) use ($bankAccountId) {
                $q->where('bank_account_id', $bankAccountId);
            });
        }

        // Filter by clearing month (when item was cleared)
        if ($startDate) {
            $query->where('clearing_month', '>=', date('Y-m-01', strtotime($startDate)));
        }

        if ($endDate) {
            $query->where('clearing_month', '<=', date('Y-m-t', strtotime($endDate)));
        }

        $items = $query->with([
            'bankReconciliation.bankAccount',
            'originReconciliation',
            'clearedBy',
            'glTransaction'
        ])
        ->orderBy('clearing_date', 'asc')
        ->get();

        // Format data for report
        $reportItems = [];
        foreach ($items as $item) {
            // Determine source
            $source = 'Cash Book';
            if ($item->is_bank_statement_item && !$item->is_book_entry) {
                $source = 'Bank';
            }
            
            // Get origin month (when item was outstanding)
            $originMonth = $item->origin_month 
                ? $item->origin_month->format('M Y') 
                : ($item->originReconciliation 
                    ? $item->originReconciliation->end_date->format('M Y') 
                    : 'N/A');
            
            $reportItems[] = [
                'date' => $item->transaction_date,
                'description' => $item->description,
                'reference' => $item->reference ?? '-',
                'amount' => $item->nature === 'debit' ? $item->amount : -$item->amount,
                'source' => $source,
                'item_type' => $item->item_type ?? 'N/A',
                'origin_month' => $originMonth,
                'cleared_date' => $item->clearing_date ? $item->clearing_date->format('Y-m-d') : null,
                'clearing_month' => $item->clearing_month ? $item->clearing_month->format('M Y') : null,
                'cleared_by' => $item->clearedBy ? $item->clearedBy->name : ($item->reconciledBy ? $item->reconciledBy->name : 'Auto'),
                'age_days' => $item->age_days ?? 0,
                'age_months' => $item->age_months ?? 0,
                'item' => $item,
            ];
        }

        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->orderBy('name')
            ->get();

        $selectedBankAccount = $bankAccountId ? BankAccount::find($bankAccountId) : null;

        // Export to PDF if requested
        if ($request->get('export') === 'pdf') {
            $pdf = Pdf::loadView('accounting.reports.bank-reconciliation.cleared-transactions-pdf', compact(
                'reportItems',
                'user',
                'bankAccounts',
                'selectedBankAccount',
                'startDate',
                'endDate'
            ));
            $pdf->setPaper('A4', 'landscape');
            return $pdf->download('cleared_transactions_' . date('Y-m-d') . '.pdf');
        }

        return view('accounting.reports.bank-reconciliation.cleared-transactions', compact(
            'reportItems',
            'bankAccounts',
            'selectedBankAccount',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Bank Reconciliation Adjustments - Auto Journal Entries
     */
    public function bankReconciliationAdjustments(Request $request)
    {
        $user = Auth::user();

        // Get filters with defaults
        $bankAccountId = $request->get('bank_account_id');
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));

        // Get reconciliations that have adjustments posted
        $query = BankReconciliation::whereHas('bankAccount.chartAccount.accountClassGroup', function ($q) use ($user) {
            $q->where('company_id', $user->company_id);
        })
        ->whereNotNull('adjustments_posted_at');

        if ($bankAccountId) {
            $query->where('bank_account_id', $bankAccountId);
        }

        if ($startDate) {
            $query->where('adjustments_posted_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('adjustments_posted_at', '<=', $endDate . ' 23:59:59');
        }

        $reconciliations = $query->with([
            'bankAccount',
            'adjustmentsPostedBy'
        ])
        ->orderBy('adjustments_posted_at', 'desc')
        ->get();

        // Get GL transactions for adjustments (bank charges, interest, etc.)
        $adjustmentTransactions = [];
        foreach ($reconciliations as $reconciliation) {
            // Get bank statement items that are adjustments (bank charges, interest)
            $bankItems = $reconciliation->reconciliationItems()
                ->where('is_bank_statement_item', true)
                ->where('is_book_entry', false)
                ->where(function($q) {
                    $q->where('description', 'like', '%bank charge%')
                      ->orWhere('description', 'like', '%bank fee%')
                      ->orWhere('description', 'like', '%interest%')
                      ->orWhere('description', 'like', '%service charge%');
                })
                ->with('glTransaction.chartAccount')
                ->get();

            foreach ($bankItems as $item) {
                if ($item->glTransaction) {
                    $glTransaction = $item->glTransaction;
                    
                    // Determine adjustment type
                    $type = 'Bank Charge';
                    if (stripos($item->description, 'interest') !== false) {
                        $type = 'Interest Income';
                    }
                    
                    // Get the paired transaction (debit/credit)
                    $pairedTransaction = \App\Models\GlTransaction::where('transaction_id', $glTransaction->transaction_id)
                        ->where('transaction_type', $glTransaction->transaction_type)
                        ->where('id', '!=', $glTransaction->id)
                        ->with('chartAccount')
                        ->first();
                    
                    $adjustmentTransactions[] = [
                        'date' => $reconciliation->adjustments_posted_at->format('Y-m-d'),
                        'journal_no' => 'AJ-' . $reconciliation->adjustments_posted_at->format('Y-m-d') . '-' . str_pad($item->id, 3, '0', STR_PAD_LEFT),
                        'type' => $type,
                        'debit_account' => $glTransaction->nature === 'debit' ? ($glTransaction->chartAccount->name ?? 'N/A') : ($pairedTransaction->chartAccount->name ?? 'N/A'),
                        'credit_account' => $glTransaction->nature === 'credit' ? ($glTransaction->chartAccount->name ?? 'N/A') : ($pairedTransaction->chartAccount->name ?? 'N/A'),
                        'amount' => $item->amount,
                        'description' => $item->description,
                        'posted_by' => $reconciliation->adjustmentsPostedBy ? $reconciliation->adjustmentsPostedBy->name : 'System',
                        'reconciliation' => $reconciliation,
                    ];
                }
            }
        }

        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->orderBy('name')
            ->get();

        $selectedBankAccount = $bankAccountId ? BankAccount::find($bankAccountId) : null;

        // Export to PDF if requested
        if ($request->get('export') === 'pdf') {
            $pdf = Pdf::loadView('accounting.reports.bank-reconciliation.adjustments-pdf', compact(
                'adjustmentTransactions',
                'user',
                'bankAccounts',
                'selectedBankAccount',
                'startDate',
                'endDate'
            ));
            $pdf->setPaper('A4', 'landscape');
            return $pdf->download('bank_reconciliation_adjustments_' . date('Y-m-d') . '.pdf');
        }

        return view('accounting.reports.bank-reconciliation.adjustments', compact(
            'adjustmentTransactions',
            'bankAccounts',
            'selectedBankAccount',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Bank Reconciliation Exception Report (items uncleared for >15 days)
     */
    public function exceptionReport(Request $request)
    {
        $user = Auth::user();

        // Get filters
        $bankAccountId = $request->get('bank_account_id');
        $severity = $request->get('severity'); // high, medium, low

        // Build query for uncleared items > 15 days
        $query = BankReconciliationItem::whereHas('bankReconciliation.bankAccount.chartAccount.accountClassGroup', function ($q) use ($user) {
            $q->where('company_id', $user->company_id);
        })
        ->where('is_reconciled', false)
        ->whereRaw('DATEDIFF(NOW(), transaction_date) > 15');

        if ($bankAccountId) {
            $query->whereHas('bankReconciliation', function ($q) use ($bankAccountId) {
                $q->where('bank_account_id', $bankAccountId);
            });
        }

        $items = $query->with([
            'bankReconciliation.bankAccount',
            'glTransaction'
        ])
        ->get();

        // Calculate aging and format exceptions
        $exceptions = [];
        foreach ($items as $item) {
            $item->calculateAging();
            
            // Determine issue type
            $issueType = 'Cash Book Only';
            if ($item->is_bank_statement_item && !$item->is_book_entry) {
                $issueType = 'Bank Statement Only';
            }
            
            // Determine severity
            $itemSeverity = 'Low';
            $suggestedAction = 'Review transaction';
            
            if ($item->age_days >= 90) {
                $itemSeverity = 'High';
                if ($item->is_book_entry && $item->nature === 'debit') {
                    $suggestedAction = 'Verify customer payment';
                } else {
                    $suggestedAction = 'Investigate missing transaction';
                }
            } elseif ($item->age_days >= 30) {
                $itemSeverity = 'Medium';
                $suggestedAction = 'Post adjustment or verify';
            }
            
            // Filter by severity if specified
            if ($severity && $itemSeverity !== ucfirst($severity)) {
                continue;
            }
            
            $exceptions[] = [
                'issue_type' => $issueType,
                'description' => $item->description,
                'transaction' => $item->reference ?? '-',
                'amount' => $item->nature === 'debit' ? $item->amount : -$item->amount,
                'detected_on' => now()->format('Y-m-d'),
                'severity' => $itemSeverity,
                'suggested_action' => $suggestedAction,
                'age_days' => $item->age_days,
                'item' => $item,
            ];
        }

        // Sort by severity and age
        usort($exceptions, function($a, $b) {
            $severityOrder = ['High' => 3, 'Medium' => 2, 'Low' => 1];
            if ($severityOrder[$a['severity']] !== $severityOrder[$b['severity']]) {
                return $severityOrder[$b['severity']] - $severityOrder[$a['severity']];
            }
            return $b['age_days'] - $a['age_days'];
        });

        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->orderBy('name')
            ->get();

        $selectedBankAccount = $bankAccountId ? BankAccount::find($bankAccountId) : null;

        // Export to PDF if requested
        if ($request->get('export') === 'pdf') {
            $pdf = Pdf::loadView('accounting.reports.bank-reconciliation.exception-pdf', compact(
                'exceptions',
                'user',
                'bankAccounts',
                'selectedBankAccount',
                'severity'
            ));
            $pdf->setPaper('A4', 'landscape');
            return $pdf->download('bank_reconciliation_exceptions_' . date('Y-m-d') . '.pdf');
        }

        return view('accounting.reports.bank-reconciliation.exception', compact(
            'exceptions',
            'bankAccounts',
            'selectedBankAccount',
            'severity'
        ));
    }

    /**
     * Reconciliation Approval & Audit Trail Report
     */
    public function approvalAuditTrail(Request $request)
    {
        $user = Auth::user();

        // Get filters
        $bankAccountId = $request->get('bank_account_id');
        $month = $request->get('month', date('Y-m'));
        $year = $request->get('year', date('Y'));

        // Get reconciliations for the specified month/year
        $query = BankReconciliation::whereHas('bankAccount.chartAccount.accountClassGroup', function ($q) use ($user) {
            $q->where('company_id', $user->company_id);
        })
        ->whereYear('reconciliation_date', $year)
        ->whereMonth('reconciliation_date', date('m', strtotime($month . '-01')));

        if ($bankAccountId) {
            $query->where('bank_account_id', $bankAccountId);
        }

        $reconciliations = $query->with([
            'bankAccount',
            'user',
            'approvalHistories.approvalLevel',
            'approvalHistories.approver',
            'submittedBy',
            'approvedBy'
        ])
        ->orderBy('reconciliation_date', 'desc')
        ->get();

        // Build audit trail for each reconciliation
        $auditTrails = [];
        foreach ($reconciliations as $reconciliation) {
            $trail = [];
            $step = 1;
            
            // Get approval histories
            $histories = $reconciliation->approvalHistories()->with(['approvalLevel', 'approver'])->orderBy('created_at', 'asc')->get();
            
            // Add reconciliation started
            $trail[] = [
                'step' => $step++,
                'action' => 'Reconciliation Started',
                'user' => $reconciliation->user ? $reconciliation->user->name : 'System',
                'timestamp' => $reconciliation->created_at,
                'ip_device' => '-',
                'notes' => '-',
            ];
            
            // Add items matched (if any)
            $matchedCount = $reconciliation->reconciliationItems()->where('is_reconciled', true)->count();
            if ($matchedCount > 0) {
                $firstMatch = $reconciliation->reconciliationItems()->where('is_reconciled', true)->orderBy('reconciled_at', 'asc')->first();
                $trail[] = [
                    'step' => $step++,
                    'action' => 'Items Matched',
                    'user' => $firstMatch->reconciledBy ? $firstMatch->reconciledBy->name : 'System',
                    'timestamp' => $firstMatch->reconciled_at,
                    'ip_device' => '-',
                    'notes' => $matchedCount . ' items matched',
                ];
            }
            
            // Add approval history entries
            foreach ($histories as $history) {
                $actionLabel = ucfirst($history->action);
                if ($history->action === 'submitted') {
                    $actionLabel = 'Submitted for Approval';
                } elseif ($history->action === 'approved') {
                    $actionLabel = 'Approved';
                } elseif ($history->action === 'rejected') {
                    $actionLabel = 'Rejected';
                } elseif ($history->action === 'reassigned') {
                    $actionLabel = 'Reassigned';
                }
                
                $trail[] = [
                    'step' => $step++,
                    'action' => $actionLabel,
                    'user' => $history->approver ? $history->approver->name : 'System',
                    'timestamp' => $history->created_at,
                    'ip_device' => '-',
                    'notes' => $history->comments ?? ($history->approvalLevel ? 'Level ' . $history->approvalLevel->approval_order : '-'),
                ];
            }
            
            // Add differences fixed (if adjustments were made)
            if ($reconciliation->adjustments_posted_at) {
                $trail[] = [
                    'step' => $step++,
                    'action' => 'Differences Fixed',
                    'user' => $reconciliation->adjustmentsPostedBy ? $reconciliation->adjustmentsPostedBy->name : 'System',
                    'timestamp' => $reconciliation->adjustments_posted_at,
                    'ip_device' => '-',
                    'notes' => 'Adjusted bank charges and other items',
                ];
            }
            
            // Add final approval/completion
            if ($reconciliation->status === 'completed' || $reconciliation->status === 'approved') {
                $trail[] = [
                    'step' => $step++,
                    'action' => $reconciliation->status === 'completed' ? 'Completed' : 'Approved',
                    'user' => $reconciliation->approvedBy ? $reconciliation->approvedBy->name : ($reconciliation->submittedBy ? $reconciliation->submittedBy->name : 'System'),
                    'timestamp' => $reconciliation->approved_at ?? $reconciliation->updated_at,
                    'ip_device' => '-',
                    'notes' => $reconciliation->difference == 0 ? 'Reconciliation balanced' : 'Reconciliation completed',
                ];
            }
            
            $auditTrails[] = [
                'reconciliation' => $reconciliation,
                'trail' => $trail,
            ];
        }

        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->orderBy('name')
            ->get();

        $selectedBankAccount = $bankAccountId ? BankAccount::find($bankAccountId) : null;

        // Export to PDF if requested
        if ($request->get('export') === 'pdf') {
            $pdf = Pdf::loadView('accounting.reports.bank-reconciliation.approval-audit-trail-pdf', compact(
                'auditTrails',
                'user',
                'bankAccounts',
                'selectedBankAccount',
                'month',
                'year'
            ));
            $pdf->setPaper('A4', 'landscape');
            return $pdf->download('bank_reconciliation_audit_trail_' . $month . '.pdf');
        }

        return view('accounting.reports.bank-reconciliation.approval-audit-trail', compact(
            'auditTrails',
            'bankAccounts',
            'selectedBankAccount',
            'month',
            'year'
        ));
    }

    /**
     * Full Bank Reconciliation Pack - Selection Page
     */
    public function fullReconciliationPackSelect(Request $request)
    {
        if (!auth()->user()->can('view bank reconciliation report')) {
            abort(403, 'Unauthorized access to this report.');
        }
        
        $user = Auth::user();

        // Get bank accounts for the current company
        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->orderBy('name')
            ->get();

        // Get recent reconciliations
        $reconciliations = BankReconciliation::with(['bankAccount', 'user'])
            ->whereHas('bankAccount.chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->orderBy('reconciliation_date', 'desc')
            ->limit(20)
            ->get();

        return view('accounting.reports.bank-reconciliation.full-pack-select', compact('bankAccounts', 'reconciliations'));
    }

    /**
     * Full Bank Reconciliation Pack (PDF bundle)
     */
    public function fullReconciliationPack(Request $request, $bankReconciliation = null)
    {
        $user = Auth::user();
        
        // Handle POST request with reconciliation_id
        if ($request->isMethod('post')) {
            $reconciliationId = $request->get('reconciliation_id');
            if ($reconciliationId) {
                $bankReconciliation = BankReconciliation::find($reconciliationId);
            } else {
                $bankAccountId = $request->get('bank_account_id');
                if (!$bankAccountId) {
                    return redirect()->route('accounting.reports.bank-reconciliation-report.full-pack-select')
                        ->withErrors(['error' => 'Please select a bank account or reconciliation.']);
                }
                
                $bankReconciliation = BankReconciliation::whereHas('bankAccount.chartAccount.accountClassGroup', function ($q) use ($user) {
                    $q->where('company_id', $user->company_id);
                })
                ->where('bank_account_id', $bankAccountId)
                ->orderBy('reconciliation_date', 'desc')
                ->first();
                
                if (!$bankReconciliation) {
                    return redirect()->route('accounting.reports.bank-reconciliation-report.full-pack-select')
                        ->withErrors(['error' => 'No reconciliation found for the selected bank account.']);
                }
            }
        }
        
        // Decode hash ID if provided in route parameter
        if ($bankReconciliation && is_string($bankReconciliation)) {
            $id = \App\Helpers\HashIdHelper::decode($bankReconciliation);
            if ($id) {
                $bankReconciliation = BankReconciliation::find($id);
            } else {
                $bankReconciliation = BankReconciliation::find($bankReconciliation);
            }
        }
        
        // If still no reconciliation, try to get from request
        if (!$bankReconciliation) {
            $bankAccountId = $request->get('bank_account_id');
            if (!$bankAccountId) {
                return redirect()->route('accounting.reports.bank-reconciliation-report.full-pack-select')
                    ->withErrors(['error' => 'Please select a bank account or reconciliation.']);
            }
            
            $bankReconciliation = BankReconciliation::whereHas('bankAccount.chartAccount.accountClassGroup', function ($q) use ($user) {
                $q->where('company_id', $user->company_id);
            })
            ->where('bank_account_id', $bankAccountId)
            ->orderBy('reconciliation_date', 'desc')
            ->first();
            
            if (!$bankReconciliation) {
                return redirect()->route('accounting.reports.bank-reconciliation-report.full-pack-select')
                    ->withErrors(['error' => 'No reconciliation found for the selected bank account.']);
            }
        }
        
        // Ensure we have a BankReconciliation model instance
        if (!$bankReconciliation instanceof BankReconciliation) {
            return redirect()->route('accounting.reports.bank-reconciliation-report.full-pack-select')
                ->withErrors(['error' => 'Invalid reconciliation selected.']);
        }
        
        // Check access
        if ($bankReconciliation->bankAccount->chartAccount->accountClassGroup->company_id !== $user->company_id) {
            abort(403, 'Unauthorized access to this reconciliation.');
        }
        
        // Load all relationships
        $bankReconciliation->load([
            'bankAccount.chartAccount',
            'user',
            'branch',
            'reconciliationItems.glTransaction.chartAccount',
            'reconciliationItems.matchedWithItem',
            'reconciliationItems.reconciledBy',
            'approvalHistories.approvalLevel',
            'approvalHistories.approver',
        ]);
        
        // Get all report data
        $unreconciledItems = $bankReconciliation->reconciliationItems()
            ->where('is_reconciled', false)
            ->orderBy('transaction_date', 'asc')
            ->get();
        
        foreach ($unreconciledItems as $item) {
            $item->calculateAging();
        }
        
        $clearedItems = $bankReconciliation->reconciliationItems()
            ->where('is_reconciled', true)
            ->with(['reconciledBy', 'matchedWithItem'])
            ->orderBy('reconciled_at', 'desc')
            ->get();
        
        // Get adjustments
        $adjustmentTransactions = [];
        if ($bankReconciliation->adjustments_posted_at) {
            $bankItems = $bankReconciliation->reconciliationItems()
                ->where('is_bank_statement_item', true)
                ->where('is_book_entry', false)
                ->where(function($q) {
                    $q->where('description', 'like', '%bank charge%')
                      ->orWhere('description', 'like', '%bank fee%')
                      ->orWhere('description', 'like', '%interest%')
                      ->orWhere('description', 'like', '%service charge%');
                })
                ->with('glTransaction.chartAccount')
                ->get();
            
            foreach ($bankItems as $item) {
                if ($item->glTransaction) {
                    $glTransaction = $item->glTransaction;
                    $type = 'Bank Charge';
                    if (stripos($item->description, 'interest') !== false) {
                        $type = 'Interest Income';
                    }
                    
                    $pairedTransaction = \App\Models\GlTransaction::where('transaction_id', $glTransaction->transaction_id)
                        ->where('transaction_type', $glTransaction->transaction_type)
                        ->where('id', '!=', $glTransaction->id)
                        ->with('chartAccount')
                        ->first();
                    
                    $adjustmentTransactions[] = [
                        'date' => $bankReconciliation->adjustments_posted_at->format('Y-m-d'),
                        'journal_no' => 'AJ-' . $bankReconciliation->adjustments_posted_at->format('Y-m-d') . '-' . str_pad($item->id, 3, '0', STR_PAD_LEFT),
                        'type' => $type,
                        'debit_account' => $glTransaction->nature === 'debit' ? ($glTransaction->chartAccount->name ?? 'N/A') : ($pairedTransaction->chartAccount->name ?? 'N/A'),
                        'credit_account' => $glTransaction->nature === 'credit' ? ($glTransaction->chartAccount->name ?? 'N/A') : ($pairedTransaction->chartAccount->name ?? 'N/A'),
                        'amount' => $item->amount,
                        'description' => $item->description,
                        'posted_by' => $bankReconciliation->adjustmentsPostedBy ? $bankReconciliation->adjustmentsPostedBy->name : 'System',
                    ];
                }
            }
        }
        
        // Get exceptions (>15 days)
        $exceptions = [];
        foreach ($unreconciledItems as $item) {
            // Ensure age_days is calculated
            if (!$item->age_days) {
                $item->calculateAging();
            }
            if ($item->age_days && $item->age_days > 15) {
                $issueType = $item->is_book_entry ? 'Cash Book Only' : 'Bank Statement Only';
                $severity = $item->age_days >= 90 ? 'High' : ($item->age_days >= 30 ? 'Medium' : 'Low');
                $suggestedAction = $item->age_days >= 90 ? 'Verify customer payment' : 'Post adjustment';
                
                $exceptions[] = [
                    'issue_type' => $issueType,
                    'description' => $item->description,
                    'transaction' => $item->reference ?? '-',
                    'amount' => $item->nature === 'debit' ? $item->amount : -$item->amount,
                    'detected_on' => now()->format('Y-m-d'),
                    'severity' => $severity,
                    'suggested_action' => $suggestedAction,
                ];
            }
        }
        
        // Get audit trail
        $histories = $bankReconciliation->approvalHistories()->with(['approvalLevel', 'approver'])->orderBy('created_at', 'asc')->get();
        $auditTrail = [];
        $step = 1;
        
        $auditTrail[] = [
            'step' => $step++,
            'action' => 'Reconciliation Started',
            'user' => $bankReconciliation->user ? $bankReconciliation->user->name : 'System',
            'timestamp' => $bankReconciliation->created_at,
            'ip_device' => '-',
            'notes' => '-',
        ];
        
        $matchedCount = $clearedItems->count();
        if ($matchedCount > 0) {
            $firstMatch = $clearedItems->last();
            $auditTrail[] = [
                'step' => $step++,
                'action' => 'Items Matched',
                'user' => $firstMatch->reconciledBy ? $firstMatch->reconciledBy->name : 'System',
                'timestamp' => $firstMatch->reconciled_at,
                'ip_device' => '-',
                'notes' => $matchedCount . ' items matched',
            ];
        }
        
        foreach ($histories as $history) {
            $actionLabel = ucfirst($history->action);
            if ($history->action === 'submitted') $actionLabel = 'Submitted for Approval';
            elseif ($history->action === 'approved') $actionLabel = 'Approved';
            elseif ($history->action === 'rejected') $actionLabel = 'Rejected';
            elseif ($history->action === 'reassigned') $actionLabel = 'Reassigned';
            
            $auditTrail[] = [
                'step' => $step++,
                'action' => $actionLabel,
                'user' => $history->approver ? $history->approver->name : 'System',
                'timestamp' => $history->created_at,
                'ip_device' => '-',
                'notes' => $history->comments ?? '-',
            ];
        }
        
        if ($bankReconciliation->adjustments_posted_at) {
            $auditTrail[] = [
                'step' => $step++,
                'action' => 'Differences Fixed',
                'user' => $bankReconciliation->adjustmentsPostedBy ? $bankReconciliation->adjustmentsPostedBy->name : 'System',
                'timestamp' => $bankReconciliation->adjustments_posted_at,
                'ip_device' => '-',
                'notes' => 'Adjusted bank charges',
            ];
        }
        
        if ($bankReconciliation->status === 'completed' || $bankReconciliation->status === 'approved') {
            $auditTrail[] = [
                'step' => $step++,
                'action' => $bankReconciliation->status === 'completed' ? 'Completed' : 'Approved',
                'user' => $bankReconciliation->approvedBy ? $bankReconciliation->approvedBy->name : 'System',
                'timestamp' => $bankReconciliation->approved_at ?? $bankReconciliation->updated_at,
                'ip_device' => '-',
                'notes' => $bankReconciliation->difference == 0 ? 'Reconciliation balanced' : 'Reconciliation completed',
            ];
        }
        
        try {
            // Prepare PDF data
            $company = $user->company;
            $branch = $user->branch;
            
            $pdfData = [
                'bankReconciliation' => $bankReconciliation,
                'unreconciledItems' => $unreconciledItems,
                'clearedItems' => $clearedItems,
                'adjustmentTransactions' => $adjustmentTransactions,
                'exceptions' => $exceptions,
                'auditTrail' => $auditTrail,
                'company' => $company,
                'branch' => $branch,
                'user' => $user,
                'generated_at' => now(),
            ];
            
            // Generate PDF
            $pdf = Pdf::loadView('accounting.reports.bank-reconciliation.full-pack-pdf', $pdfData);
            $pdf->setPaper('A4', 'landscape');
            
            // Sanitize filename
            $bankName = preg_replace('/[^A-Za-z0-9_-]/', '_', $bankReconciliation->bankAccount->name);
            $filename = 'bank_reconciliation_pack_' . $bankName . '_' . $bankReconciliation->reconciliation_date->format('Y-m-d') . '.pdf';
            
            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('Error generating full bank reconciliation pack PDF', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'reconciliation_id' => $bankReconciliation->id ?? null
            ]);
            
            return redirect()->route('accounting.reports.bank-reconciliation-report.full-pack-select')
                ->withErrors(['error' => 'Error generating PDF: ' . $e->getMessage()]);
        }
    }

    /**
     * Cleared Items from Previous Month Report
     */
    public function clearedItemsFromPreviousMonth(Request $request)
    {
        $user = Auth::user();

        // Get filters
        $bankAccountId = $request->get('bank_account_id');
        $clearingMonth = $request->get('clearing_month', date('Y-m'));

        // Build query for items cleared in the specified month
        $query = BankReconciliationItem::whereHas('bankReconciliation.bankAccount.chartAccount.accountClassGroup', function ($q) use ($user) {
            $q->where('company_id', $user->company_id);
        })
        ->where('uncleared_status', 'CLEARED')
        ->where('is_book_entry', true)
        ->whereNotNull('clearing_month')
        ->whereYear('clearing_month', date('Y', strtotime($clearingMonth . '-01')))
        ->whereMonth('clearing_month', date('m', strtotime($clearingMonth . '-01')));

        if ($bankAccountId) {
            $query->whereHas('bankReconciliation', function ($q) use ($bankAccountId) {
                $q->where('bank_account_id', $bankAccountId);
            });
        }

        $items = $query->with([
            'bankReconciliation.bankAccount',
            'originReconciliation',
            'clearedBy'
        ])
        ->orderBy('clearing_date', 'asc')
        ->get();

        // Calculate age at clearing
        foreach ($items as $item) {
            if ($item->origin_date && $item->clearing_date) {
                // Age at clearing: clearing_date minus origin_date
                $ageDays = $item->clearing_date->diffInDays($item->origin_date);
                $ageDays = round($ageDays); // Round to whole number
                
                $ageMonths = $item->clearing_date->diffInMonths($item->origin_date);
                $remainingDays = $item->clearing_date->copy()->subMonths($ageMonths)->diffInDays($item->origin_date);
                $ageMonths = $ageMonths + ($remainingDays / 30);
                $ageMonths = round($ageMonths, 2);
                
                $item->age_at_clearing_days = $ageDays;
                $item->age_at_clearing_months = $ageMonths;
            }
        }

        // Group by item type
        $dncItems = $items->where('item_type', 'DNC');
        $upcItems = $items->where('item_type', 'UPC');

        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->orderBy('name')
            ->get();

        // Export to PDF if requested
        if ($request->get('export') === 'pdf') {
            $pdf = Pdf::loadView('accounting.reports.bank-reconciliation.cleared-items-pdf', compact(
                'items',
                'dncItems',
                'upcItems',
                'user',
                'bankAccounts',
                'bankAccountId',
                'clearingMonth'
            ));
            $pdf->setPaper('A4', 'landscape');
            return $pdf->download('cleared_items_' . $clearingMonth . '.pdf');
        }

        return view('accounting.reports.bank-reconciliation.cleared-items', compact(
            'items',
            'dncItems',
            'upcItems',
            'bankAccounts',
            'bankAccountId',
            'clearingMonth'
        ));
    }

    /**
     * Reconciliation Summary Movement Report
     */
    public function reconciliationSummaryMovement(Request $request)
    {
        $user = Auth::user();

        // Get filters
        $bankAccountId = $request->get('bank_account_id');
        $startMonth = $request->get('start_month', date('Y-m', strtotime('-11 months')));
        $endMonth = $request->get('end_month', date('Y-m'));

        // Get all reconciliations in the period
        $query = BankReconciliation::whereHas('bankAccount.chartAccount.accountClassGroup', function ($q) use ($user) {
            $q->where('company_id', $user->company_id);
        })
        ->whereYear('end_date', '>=', date('Y', strtotime($startMonth . '-01')))
        ->whereMonth('end_date', '>=', date('m', strtotime($startMonth . '-01')))
        ->whereYear('end_date', '<=', date('Y', strtotime($endMonth . '-01')))
        ->whereMonth('end_date', '<=', date('m', strtotime($endMonth . '-01')))
        ->orderBy('end_date', 'asc');

        if ($bankAccountId) {
            $query->where('bank_account_id', $bankAccountId);
        }

        $reconciliations = $query->with(['reconciliationItems'])->get();

        // Process data by month
        $monthlyData = [];
        $unclearedItemsService = app(UnclearedItemsService::class);

        foreach ($reconciliations as $reconciliation) {
            $month = $reconciliation->end_date->format('Y-m');
            
            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = [
                    'month' => $reconciliation->end_date->format('M Y'),
                    'dnc' => [
                        'opening' => 0,
                        'cleared' => 0,
                        'new' => 0,
                        'closing' => 0,
                    ],
                    'upc' => [
                        'opening' => 0,
                        'cleared' => 0,
                        'new' => 0,
                        'closing' => 0,
                    ],
                ];
            }

            $summary = $unclearedItemsService->getUnclearedItemsSummary($reconciliation);
            
            // Get brought forward items (opening)
            $broughtForwardDNC = $reconciliation->reconciliationItems()
                ->where('item_type', 'DNC')
                ->where('is_brought_forward', true)
                ->where('uncleared_status', 'UNCLEARED')
                ->sum('amount');
            
            $broughtForwardUPC = $reconciliation->reconciliationItems()
                ->where('item_type', 'UPC')
                ->where('is_brought_forward', true)
                ->where('uncleared_status', 'UNCLEARED')
                ->sum('amount');

            // Get cleared items this month
            $clearedDNC = $reconciliation->reconciliationItems()
                ->where('item_type', 'DNC')
                ->where('uncleared_status', 'CLEARED')
                ->whereYear('clearing_month', $reconciliation->end_date->format('Y'))
                ->whereMonth('clearing_month', $reconciliation->end_date->format('m'))
                ->sum('amount');
            
            $clearedUPC = $reconciliation->reconciliationItems()
                ->where('item_type', 'UPC')
                ->where('uncleared_status', 'CLEARED')
                ->whereYear('clearing_month', $reconciliation->end_date->format('Y'))
                ->whereMonth('clearing_month', $reconciliation->end_date->format('m'))
                ->sum('amount');

            // Get new uncleared items (not brought forward)
            $newDNC = $reconciliation->reconciliationItems()
                ->where('item_type', 'DNC')
                ->where('uncleared_status', 'UNCLEARED')
                ->where('is_brought_forward', false)
                ->sum('amount');
            
            $newUPC = $reconciliation->reconciliationItems()
                ->where('item_type', 'UPC')
                ->where('uncleared_status', 'UNCLEARED')
                ->where('is_brought_forward', false)
                ->sum('amount');

            $monthlyData[$month]['dnc']['opening'] = $broughtForwardDNC;
            $monthlyData[$month]['dnc']['cleared'] = $clearedDNC;
            $monthlyData[$month]['dnc']['new'] = $newDNC;
            $monthlyData[$month]['dnc']['closing'] = $summary['dnc']['total_amount'] ?? 0;

            $monthlyData[$month]['upc']['opening'] = $broughtForwardUPC;
            $monthlyData[$month]['upc']['cleared'] = $clearedUPC;
            $monthlyData[$month]['upc']['new'] = $newUPC;
            $monthlyData[$month]['upc']['closing'] = $summary['upc']['total_amount'] ?? 0;
        }

        $bankAccounts = BankAccount::with('chartAccount')
            ->whereHas('chartAccount.accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->orderBy('name')
            ->get();

        // Export to PDF if requested
        if ($request->get('export') === 'pdf') {
            $pdf = Pdf::loadView('accounting.reports.bank-reconciliation.summary-movement-pdf', compact(
                'monthlyData',
                'user',
                'bankAccounts',
                'bankAccountId',
                'startMonth',
                'endMonth'
            ));
            $pdf->setPaper('A4', 'landscape');
            return $pdf->download('reconciliation_summary_movement_' . date('Y-m-d') . '.pdf');
        }

        return view('accounting.reports.bank-reconciliation.summary-movement', compact(
            'monthlyData',
            'bankAccounts',
            'bankAccountId',
            'startMonth',
            'endMonth'
        ));
    }
}
