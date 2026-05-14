<?php

namespace App\Http\Controllers\Accounting\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Branch;

class OtherIncomeReportController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()->can('view other income report')) {
            abort(403, 'Unauthorized access to this report.');
        }
        
        $user = Auth::user();
        $company = $user->company;
        
        // Get default dates if not provided
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        $reportingType = $request->get('reporting_type', 'accrual');
        $branchId = $request->get('branch_id', 'all');
        
        // Get user's assigned branches
        $branches = $user->branches()->get();
        
        $otherIncomeData = null;
        
        // Only fetch data if dates are provided
        if ($request->has('start_date') && $request->has('end_date')) {
            $otherIncomeData = $this->getOtherIncomeData($startDate, $endDate, $reportingType, $branchId, $company);
        }
        
        return view('accounting.reports.other-income.index', compact(
            'startDate', 
            'endDate', 
            'reportingType', 
            'branchId', 
            'branches', 
            'otherIncomeData'
        ));
    }
    
    private function getOtherIncomeData($startDate, $endDate, $reportingType, $branchId, $company)
    {
        // Get sales revenue account from settings to exclude it
        $salesRevenueAccountId = \App\Models\SystemSetting::where('key', 'inventory_default_sales_account')->value('value');
        
        // Build the base query for other income accounts (excluding sales revenue)
        $query = DB::table('gl_transactions')
            ->join('chart_accounts', 'gl_transactions.chart_account_id', '=', 'chart_accounts.id')
            ->join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $company->id)
            ->whereBetween('gl_transactions.date', [$startDate, $endDate])
            ->whereIn('account_class.name', ['income', 'revenue']);
        
        // Exclude sales revenue account if it's set in settings
        if ($salesRevenueAccountId && is_numeric($salesRevenueAccountId)) {
            $query->where('chart_accounts.id', '!=', $salesRevenueAccountId);
        }
        
        // Add branch filter
        $assignedBranchIds = Auth::user()->branches()->pluck('branches.id')->toArray();
        if ($branchId === 'all') {
            if (!empty($assignedBranchIds)) {
                $query->whereIn('gl_transactions.branch_id', $assignedBranchIds);
            }
        } elseif ($branchId) {
            $query->where('gl_transactions.branch_id', $branchId);
        } else {
            if (!empty($assignedBranchIds)) {
                $query->whereIn('gl_transactions.branch_id', $assignedBranchIds);
            }
        }
        
        // Select fields and calculate income
        $query->select(
            'chart_accounts.id as account_id',
            'chart_accounts.account_name as account',
            'chart_accounts.account_code',
            'account_class_groups.name as group_name',
            DB::raw("SUM(CASE WHEN gl_transactions.nature = 'credit' THEN gl_transactions.amount ELSE -gl_transactions.amount END) as sum")
        )
        ->groupBy('chart_accounts.id', 'chart_accounts.account_name', 'chart_accounts.account_code', 'account_class_groups.name')
        ->having('sum', '!=', 0)
        ->orderBy('account_class_groups.name')
        ->orderBy('chart_accounts.account_code');
        
        return $query->get()->map(function ($item) {
            return [
                'account_id' => $item->account_id,
                'account' => $item->account,
                'account_code' => $item->account_code,
                'group_name' => $item->group_name,
                'sum' => (float) $item->sum
            ];
        })->toArray();
    }
    
    public function export(Request $request)
    {
        $user = Auth::user();
        $company = $user->company;
        
        // Get filter parameters
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));
        $reportingType = $request->get('reporting_type', 'accrual');
        $branchId = $request->get('branch_id', 'all');
        $exportType = $request->get('export_type', 'pdf');
        
        // Get other income data
        $otherIncomeData = $this->getOtherIncomeData($startDate, $endDate, $reportingType, $branchId, $company);
        
        if ($exportType === 'pdf') {
            return $this->exportPdf($otherIncomeData, $company, $startDate, $endDate, $reportingType);
        } else {
            return $this->exportExcel($otherIncomeData, $company, $startDate, $endDate, $reportingType);
        }
    }
    
    private function exportPdf($otherIncomeData, $company, $startDate, $endDate, $reportingType)
    {
        $user = Auth::user();
        
        // Determine branch name for header
        $branchName = 'All Branches';
        if ($user->branch) {
            $branchName = $user->branch->name;
        }
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('accounting.reports.other-income.pdf', [
            'otherIncomeData' => $otherIncomeData,
            'company' => $company,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reportingType' => $reportingType,
            'branchName' => $branchName,
            'generatedBy' => $user->name,
            'generatedAt' => now()->format('Y-m-d H:i:s')
        ]);
        
        $filename = 'Other_Income_Report_' . $startDate . '_to_' . $endDate . '.pdf';
        
        return $pdf->download($filename);
    }
    
    private function exportExcel($otherIncomeData, $company, $startDate, $endDate, $reportingType)
    {
        $user = Auth::user();
        
        // Determine branch name for header
        $branchName = 'All Branches';
        if ($user->branch) {
            $branchName = $user->branch->name;
        }
        
        $filename = 'Other_Income_Report_' . $startDate . '_to_' . $endDate . '.xlsx';
        
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\OtherIncomeReportExport($otherIncomeData, $company, $startDate, $endDate, $reportingType, $branchName, $user->name),
            $filename
        );
    }
}