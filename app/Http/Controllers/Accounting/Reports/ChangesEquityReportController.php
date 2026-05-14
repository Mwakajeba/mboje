<?php

namespace App\Http\Controllers\Accounting\Reports;

use App\Http\Controllers\Controller;
use App\Services\FinancialReports\EquityStatementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ChangesEquityReportController extends Controller
{
    protected $equityStatementService;
    
    public function __construct(EquityStatementService $equityStatementService)
    {
        $this->equityStatementService = $equityStatementService;
    }
    
    public function index(Request $request)
    {
        if (!auth()->user()->can('view changes in equity report')) {
            abort(403, 'Unauthorized access to this report.');
        }
        
        $user = Auth::user();
        $company = $user->company;
        
        // Get branches visible to the user: only assigned branches
        $branches = $user->branches()
            ->where('branches.company_id', $company->id)
            ->select('branches.id', 'branches.name')
            ->get();

        // Set default values
        $fromDate = $request->get('from_date', now()->startOfYear()->format('Y-m-d'));
        $toDate = $request->get('to_date', now()->format('Y-m-d'));
        $branchParam = $request->get('branch_id');
        $branchId = ($branches->count() > 1 && $branchParam === 'all') ? 'all' : ($branchParam ?: ($branches->first()->id ?? null));
        $equityCategoryId = $request->get('equity_category_id', '');

        // Get equity categories
        $equityCategories = DB::table('equity_categories')->get();
        
        // Get comparative periods
        $comparativePeriods = $request->get('comparative_periods', []);

        // Get equity statement data using new service
        $equityStatementData = $this->equityStatementService->getEquityStatement(
            $fromDate,
            $toDate,
            $branchId,
            $comparativePeriods
        );
        
        // Get old format data for backward compatibility (if category filter is used)
        $legacyChangesEquityData = null;
        if ($equityCategoryId) {
            $legacyChangesEquityData = $this->getChangesEquityData($fromDate, $toDate, $branchId, $equityCategoryId);
        }

        return view('accounting.reports.changes-equity.index', compact(
            'equityStatementData',
            'legacyChangesEquityData',
            'branches',
            'fromDate',
            'toDate',
            'branchId',
            'equityCategoryId',
            'equityCategories',
            'comparativePeriods',
            'user'
        ));
    }

    private function getChangesEquityData($fromDate, $toDate, $branchId, $equityCategoryId)
    {
        $user = Auth::user();
        $company = $user->company;

        // Build the base query for equity accounts
        $query = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->leftJoin('equity_categories', 'chart_accounts.equity_category_id', '=', 'equity_categories.id')
            ->where('account_class_groups.company_id', $company->id)
            ->where('chart_accounts.has_equity', true)
            ->whereBetween('gl_transactions.date', [$fromDate, $toDate]);

        // Add branch filter if specified
        if ($branchId && $branchId != 'all') {
            $query->where('gl_transactions.branch_id', $branchId);
        }

        // Add equity category filter if specified
        if ($equityCategoryId) {
            $query->where('chart_accounts.equity_category_id', $equityCategoryId);
        }

        // Get the data
        $equityTransactions = $query->select(
            'chart_accounts.id as account_id',
            'chart_accounts.account_name',
            'chart_accounts.account_code',
            'equity_categories.name as equity_category_name',
            'equity_categories.description as equity_category_description',
            'gl_transactions.date',
            'gl_transactions.nature',
            'gl_transactions.amount',
            'gl_transactions.description as transaction_description',
            'gl_transactions.transaction_type',
            'gl_transactions.transaction_id'
        )
        ->orderBy('gl_transactions.date')
        ->orderBy('equity_categories.name')
        ->orderBy('chart_accounts.account_name')
        ->get();

        // Group data by equity category
        $groupedData = [];
        $categoryTotals = [];
        $overallTotal = 0;

        foreach ($equityTransactions as $transaction) {
            $categoryName = $transaction->equity_category_name ?? 'Uncategorized';
            
            if (!isset($groupedData[$categoryName])) {
                $groupedData[$categoryName] = [];
                $categoryTotals[$categoryName] = [
                    'debit_total' => 0,
                    'credit_total' => 0,
                    'net_change' => 0
                ];
            }

            // Calculate the impact (credit increases equity, debit decreases equity)
            $impact = $transaction->nature === 'credit' ? $transaction->amount : -$transaction->amount;
            
            $groupedData[$categoryName][] = [
                'date' => $transaction->date,
                'account_name' => $transaction->account_name,
                'account_code' => $transaction->account_code,
                'description' => $transaction->transaction_description,
                'nature' => $transaction->nature,
                'amount' => $transaction->amount,
                'impact' => $impact,
                'transaction_type' => $transaction->transaction_type
            ];

            // Update category totals
            if ($transaction->nature === 'debit') {
                $categoryTotals[$categoryName]['debit_total'] += $transaction->amount;
            } else {
                $categoryTotals[$categoryName]['credit_total'] += $transaction->amount;
            }
            $categoryTotals[$categoryName]['net_change'] += $impact;
            $overallTotal += $impact;
        }

        // Get opening balance (equity balance before the report period)
        $openingBalance = $this->getOpeningEquityBalance($fromDate, $branchId, $equityCategoryId);

        return [
            'grouped_data' => $groupedData,
            'category_totals' => $categoryTotals,
            'overall_total' => $overallTotal,
            'opening_balance' => $openingBalance,
            'closing_balance' => $openingBalance + $overallTotal,
            'filters' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'branch_id' => $branchId,
                'equity_category_id' => $equityCategoryId
            ]
        ];
    }

    private function getOpeningEquityBalance($fromDate, $branchId, $equityCategoryId)
    {
        $user = Auth::user();
        $company = $user->company;

        $query = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->where('account_class_groups.company_id', $company->id)
            ->where('chart_accounts.has_equity', true)
            ->where('gl_transactions.date', '<', $fromDate);

        // Add branch filter if specified
        if ($branchId && $branchId != 'all') {
            $query->where('gl_transactions.branch_id', $branchId);
        }

        // Add equity category filter if specified
        if ($equityCategoryId) {
            $query->where('chart_accounts.equity_category_id', $equityCategoryId);
        }

        $result = $query->select(
            DB::raw('SUM(CASE WHEN gl_transactions.nature = "credit" THEN gl_transactions.amount ELSE 0 END) as credit_total'),
            DB::raw('SUM(CASE WHEN gl_transactions.nature = "debit" THEN gl_transactions.amount ELSE 0 END) as debit_total')
        )->first();

        return ($result->credit_total ?? 0) - ($result->debit_total ?? 0);
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        $company = $user->company;

        // Get filter parameters
        $fromDate = $request->get('from_date', now()->startOfYear()->format('Y-m-d'));
        $toDate = $request->get('to_date', now()->format('Y-m-d'));
        $branchId = $request->get('branch_id', $user->branch_id);
        $equityCategoryId = $request->get('equity_category_id', '');
        $exportType = $request->get('export_type', 'pdf');
        $comparativePeriods = $request->get('comparative_periods', []);

        // Get equity statement data using new service
        $equityStatementData = $this->equityStatementService->getEquityStatement(
            $fromDate,
            $toDate,
            $branchId,
            $comparativePeriods
        );
        
        // Get branches for header
        $branches = $user->branches()
            ->where('branches.company_id', $company->id)
            ->select('branches.id', 'branches.name')
            ->get();

        if ($exportType === 'excel') {
            return $this->exportExcelIFRS($equityStatementData, $company, $fromDate, $toDate, $branches, $branchId);
        } else {
            return $this->exportPdfIFRS($equityStatementData, $company, $fromDate, $toDate, $branches, $branchId);
        }
    }

    private function exportPdf($changesEquityData, $company, $fromDate, $toDate)
    {
        $user = Auth::user();
        
        // Get branches for header
        $branches = [];
        if ($user->hasRole('admin')) {
            $branches = DB::table('branches')
                ->where('company_id', $company->id)
                ->select('id', 'name')
                ->get();
        }
        
        // Generate PDF
        $pdf = \PDF::loadView('accounting.reports.changes-equity.pdf', compact(
            'changesEquityData', 
            'company', 
            'branches',
            'fromDate',
            'toDate'
        ));
        $pdf->setPaper('A4', 'portrait');
        
        $filename = 'changes_in_equity_' . $fromDate . '_to_' . $toDate . '.pdf';
        return $pdf->download($filename);
    }

    private function exportExcel($changesEquityData, $company, $fromDate, $toDate)
    {
        $spreadsheet = new Spreadsheet();
        
        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator($company->name ?? 'SmartFinance')
            ->setLastModifiedBy($company->name ?? 'SmartFinance')
            ->setTitle('Changes in Equity Report')
            ->setSubject('Changes in Equity from ' . Carbon::parse($fromDate)->format('F d, Y') . ' to ' . Carbon::parse($toDate)->format('F d, Y'))
            ->setDescription('Changes in Equity Report generated on ' . now()->format('F d, Y \a\t g:i A'));

        // Create worksheet
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle('Changes in Equity');

        // Set headers
        $worksheet->setCellValue('A1', $company->name ?? 'SmartFinance');
        $worksheet->setCellValue('A2', 'CHANGES IN EQUITY');
        $worksheet->setCellValue('A3', 'From ' . Carbon::parse($fromDate)->format('F d, Y') . ' to ' . Carbon::parse($toDate)->format('F d, Y'));
        $worksheet->setCellValue('A4', 'Generated: ' . now()->format('F d, Y \a\t g:i A'));

        // Set column headers
        $row = 6;
        $worksheet->setCellValue('A' . $row, 'Date');
        $worksheet->setCellValue('B' . $row, 'Account');
        $worksheet->setCellValue('C' . $row, 'Description');
        $worksheet->setCellValue('D' . $row, 'Nature');
        $worksheet->setCellValue('E' . $row, 'Amount');
        $worksheet->setCellValue('F' . $row, 'Impact');
        $worksheet->setCellValue('G' . $row, 'Category');

        // Style headers
        $worksheet->getStyle('A' . $row . ':G' . $row)->getFont()->setBold(true);
        $worksheet->getStyle('A' . $row . ':G' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');

        $row++;

        // Add data by category
        foreach ($changesEquityData['grouped_data'] as $categoryName => $transactions) {
            // Add category header
            $worksheet->setCellValue('A' . $row, $categoryName);
            $worksheet->getStyle('A' . $row)->getFont()->setBold(true);
            $worksheet->getStyle('A' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('28A745');
            $worksheet->getStyle('A' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
            $row++;

            // Add transactions
            foreach ($transactions as $transaction) {
                $worksheet->setCellValue('A' . $row, Carbon::parse($transaction['date'])->format('M d, Y'));
                $worksheet->setCellValue('B' . $row, $transaction['account_name'] . ' (' . $transaction['account_code'] . ')');
                $worksheet->setCellValue('C' . $row, $transaction['description']);
                $worksheet->setCellValue('D' . $row, ucfirst($transaction['nature']));
                $worksheet->setCellValue('E' . $row, $transaction['amount']);
                $worksheet->setCellValue('F' . $row, $transaction['impact']);
                $worksheet->setCellValue('G' . $row, $categoryName);

                // Format numbers
                $worksheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $worksheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

                // Color code impact
                if ($transaction['impact'] > 0) {
                    $worksheet->getStyle('F' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_GREEN));
                } else {
                    $worksheet->getStyle('F' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED));
                }

                $row++;
            }

            // Add category total
            $worksheet->setCellValue('A' . $row, 'Total for ' . $categoryName);
            $worksheet->getStyle('A' . $row)->getFont()->setBold(true);
            $worksheet->setCellValue('F' . $row, $changesEquityData['category_totals'][$categoryName]['net_change']);
            $worksheet->getStyle('F' . $row)->getFont()->setBold(true);
            $worksheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
            $row++; // Add spacing
        }

        // Add summary section
        $row++;
        $worksheet->setCellValue('A' . $row, 'SUMMARY');
        $worksheet->getStyle('A' . $row)->getFont()->setBold(true);
        $worksheet->getStyle('A' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('007BFF');
        $worksheet->getStyle('A' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
        $row++;

        $worksheet->setCellValue('A' . $row, 'Opening Balance');
        $worksheet->setCellValue('B' . $row, $changesEquityData['opening_balance']);
        $worksheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $row++;

        $worksheet->setCellValue('A' . $row, 'Net Change');
        $worksheet->setCellValue('B' . $row, $changesEquityData['overall_total']);
        $worksheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $row++;

        $worksheet->setCellValue('A' . $row, 'Closing Balance');
        $worksheet->setCellValue('B' . $row, $changesEquityData['closing_balance']);
        $worksheet->getStyle('B' . $row)->getFont()->setBold(true);
        $worksheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $worksheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create Excel file
        $writer = new Xlsx($spreadsheet);
        $filename = 'changes_in_equity_' . $fromDate . '_to_' . $toDate . '.xlsx';
        
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
    
    /**
     * Export statement of changes in equity in IFRS format to PDF
     */
    private function exportPdfIFRS($equityStatementData, $company, $fromDate, $toDate, $branches, $branchId)
    {
        $user = Auth::user();
        
        // Resolve branch name for display
        $branchName = 'All Branches';
        if ($branchId && $branchId !== 'all') {
            $branch = $branches->firstWhere('id', $branchId);
            $branchName = $branch->name ?? 'Unknown Branch';
        } elseif (($branches->count() ?? 0) <= 1 && $branchId === 'all') {
            // If only one assigned branch, show its name instead of All
            $branchName = optional($branches->first())->name ?? 'All Branches';
        }
        
        // Generate PDF
        $pdf = \PDF::loadView('accounting.reports.changes-equity.pdf-ifrs', compact(
            'equityStatementData', 
            'company', 
            'branches',
            'branchName',
            'fromDate',
            'toDate'
        ));
        $pdf->setPaper('A4', 'landscape'); // Landscape for columnar format
        
        $filename = 'statement_of_changes_in_equity_' . $fromDate . '_to_' . $toDate . '.pdf';
        return $pdf->download($filename);
    }
    
    /**
     * Export statement of changes in equity in IFRS format to Excel
     */
    private function exportExcelIFRS($equityStatementData, $company, $fromDate, $toDate, $branches, $branchId)
    {
        $spreadsheet = new Spreadsheet();
        
        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator($company->name ?? 'SmartAccounting')
            ->setLastModifiedBy($company->name ?? 'SmartAccounting')
            ->setTitle('Statement of Changes in Equity')
            ->setSubject('Statement of Changes in Equity from ' . Carbon::parse($fromDate)->format('F d, Y') . ' to ' . Carbon::parse($toDate)->format('F d, Y'))
            ->setDescription('IFRS-compliant Statement of Changes in Equity generated on ' . now()->format('F d, Y \a\t g:i A'));

        // Create worksheet
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle('Changes in Equity');

        $components = $equityStatementData['equity_components'];
        $numComponents = count($components);
        
        // Set headers
        $row = 1;
        $worksheet->setCellValue('A' . $row, $company->name ?? 'SmartAccounting');
        $lastCol = chr(65 + $numComponents); // A + number of components
        $worksheet->mergeCells('A' . $row . ':' . $lastCol . $row);
        $worksheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(16);
        $worksheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        $row++;
        $worksheet->setCellValue('A' . $row, 'STATEMENT OF CHANGES IN EQUITY');
        $worksheet->mergeCells('A' . $row . ':' . $lastCol . $row);
        $worksheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $worksheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        $row++;
        $worksheet->setCellValue('A' . $row, 'For the year ended ' . Carbon::parse($toDate)->format('F d, Y'));
        $worksheet->mergeCells('A' . $row . ':' . $lastCol . $row);
        $worksheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        $row += 2;
        
        // Column headers
        $worksheet->setCellValue('A' . $row, '');
        $col = 'B';
        foreach ($components as $component) {
            $worksheet->setCellValue($col . $row, $component['name']);
            $worksheet->getStyle($col . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $col++;
        }
        $worksheet->setCellValue($col . $row, 'Total');
        $worksheet->getStyle('A' . $row . ':' . $col . $row)->getFont()->setBold(true);
        $worksheet->getStyle('A' . $row . ':' . $col . $row)->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        $row++;
        
        // Opening balances
        $worksheet->setCellValue('A' . $row, 'Balance at ' . Carbon::parse($fromDate)->format('F d, Y'));
        $worksheet->getStyle('A' . $row)->getFont()->setBold(true);
        $col = 'B';
        foreach ($components as $component) {
            $amount = $equityStatementData['opening_balances'][$component['key']] ?? 0;
            $worksheet->setCellValue($col . $row, $amount);
            $worksheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $worksheet->getStyle($col . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $col++;
        }
        $worksheet->setCellValue($col . $row, $equityStatementData['total_opening']);
        $worksheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $worksheet->getStyle($col . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        
        $row += 2;
        
        // Comprehensive Income Section
        $worksheet->setCellValue('A' . $row, 'Changes in equity for the year:');
        $worksheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        
        // Get unique line items across all components
        $allLineItems = [];
        foreach ($components as $component) {
            $movements = $equityStatementData['movements'][$component['key']];
            foreach ($movements['line_items'] as $item) {
                if (!isset($allLineItems[$item['name']])) {
                    $allLineItems[$item['name']] = [
                        'name' => $item['name'],
                        'category' => $item['category'],
                        'amounts' => []
                    ];
                }
                $allLineItems[$item['name']]['amounts'][$component['key']] = $item['amount'];
            }
        }
        
        // Group by category
        $comprehensiveIncomeItems = array_filter($allLineItems, function($item) {
            return $item['category'] === 'comprehensive_income';
        });
        
        $transactionsWithOwnersItems = array_filter($allLineItems, function($item) {
            return $item['category'] === 'transactions_with_owners';
        });
        
        $otherItems = array_filter($allLineItems, function($item) {
            return $item['category'] === 'other';
        });
        
        // Output comprehensive income items
        if (!empty($comprehensiveIncomeItems)) {
            foreach ($comprehensiveIncomeItems as $item) {
                $worksheet->setCellValue('A' . $row, '  ' . $item['name']);
                $col = 'B';
                $rowTotal = 0;
                foreach ($components as $component) {
                    $amount = $item['amounts'][$component['key']] ?? 0;
                    if (abs($amount) > 0.01) {
                        $worksheet->setCellValue($col . $row, $amount);
                        $worksheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                    } else {
                        $worksheet->setCellValue($col . $row, '--');
                    }
                    $worksheet->getStyle($col . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    $rowTotal += $amount;
                    $col++;
                }
                $worksheet->setCellValue($col . $row, $rowTotal);
                $worksheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $worksheet->getStyle($col . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $row++;
            }
            
            // Subtotal for comprehensive income
            $worksheet->setCellValue('A' . $row, 'Total comprehensive income');
            $worksheet->getStyle('A' . $row)->getFont()->setBold(true);
            $col = 'B';
            $totalCompIncome = 0;
            foreach ($components as $component) {
                $total = 0;
                foreach ($comprehensiveIncomeItems as $item) {
                    $total += $item['amounts'][$component['key']] ?? 0;
                }
                $worksheet->setCellValue($col . $row, $total);
                $worksheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $worksheet->getStyle($col . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $worksheet->getStyle('A' . $row . ':' . $col . $row)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $totalCompIncome += $total;
                $col++;
            }
            $worksheet->setCellValue($col . $row, $totalCompIncome);
            $worksheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $worksheet->getStyle($col . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $worksheet->getStyle('A' . $row . ':' . $col . $row)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $row += 2;
        }
        
        // Transactions with owners
        if (!empty($transactionsWithOwnersItems)) {
            $worksheet->setCellValue('A' . $row, 'Transactions with owners:');
            $worksheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
            
            foreach ($transactionsWithOwnersItems as $item) {
                $worksheet->setCellValue('A' . $row, '  ' . $item['name']);
                $col = 'B';
                $rowTotal = 0;
                foreach ($components as $component) {
                    $amount = $item['amounts'][$component['key']] ?? 0;
                    if (abs($amount) > 0.01) {
                        $worksheet->setCellValue($col . $row, $amount);
                        $worksheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                    } else {
                        $worksheet->setCellValue($col . $row, '--');
                    }
                    $worksheet->getStyle($col . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    $rowTotal += $amount;
                    $col++;
                }
                $worksheet->setCellValue($col . $row, $rowTotal);
                $worksheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $worksheet->getStyle($col . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $row++;
            }
            
            // Subtotal for transactions with owners
            $worksheet->setCellValue('A' . $row, 'Total transactions with owners');
            $worksheet->getStyle('A' . $row)->getFont()->setBold(true);
            $col = 'B';
            $totalTxnOwners = 0;
            foreach ($components as $component) {
                $total = 0;
                foreach ($transactionsWithOwnersItems as $item) {
                    $total += $item['amounts'][$component['key']] ?? 0;
                }
                $worksheet->setCellValue($col . $row, $total);
                $worksheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $worksheet->getStyle($col . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $worksheet->getStyle('A' . $row . ':' . $col . $row)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $totalTxnOwners += $total;
                $col++;
            }
            $worksheet->setCellValue($col . $row, $totalTxnOwners);
            $worksheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $worksheet->getStyle($col . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $worksheet->getStyle('A' . $row . ':' . $col . $row)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $row += 2;
        }
        
        // Closing balances
        $worksheet->setCellValue('A' . $row, 'Balance at ' . Carbon::parse($toDate)->format('F d, Y'));
        $worksheet->getStyle('A' . $row)->getFont()->setBold(true);
        $col = 'B';
        foreach ($components as $component) {
            $amount = $equityStatementData['closing_balances'][$component['key']] ?? 0;
            $worksheet->setCellValue($col . $row, $amount);
            $worksheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $worksheet->getStyle($col . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $worksheet->getStyle($col . $row)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE);
            $worksheet->getStyle($col . $row)->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE);
            $col++;
        }
        $worksheet->setCellValue($col . $row, $equityStatementData['total_closing']);
        $worksheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $worksheet->getStyle($col . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $worksheet->getStyle($col . $row)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE);
        $worksheet->getStyle($col . $row)->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE);
        
        // Auto-size columns
        $worksheet->getColumnDimension('A')->setWidth(40);
        for ($i = 1; $i <= $numComponents + 1; $i++) {
            $worksheet->getColumnDimension(chr(65 + $i))->setWidth(18);
        }

        // Create Excel file
        $writer = new Xlsx($spreadsheet);
        $filename = 'statement_of_changes_in_equity_' . $fromDate . '_to_' . $toDate . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
}
