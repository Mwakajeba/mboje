<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class BranchProfitabilityExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $branchData;
    protected $dateFrom;
    protected $dateTo;
    protected $company;

    public function __construct($branchData, $dateFrom, $dateTo, $company = null)
    {
        $this->branchData = $branchData;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->company = $company;
    }

    public function collection()
    {
        return $this->branchData;
    }

    public function headings(): array
    {
        return [
            'Branch Name',
            'Total Revenue (TZS)',
            'Estimated Expenses (TZS)',
            'Net Profit (TZS)',
            'Net Margin %',
            'Status'
        ];
    }

    public function map($branch): array
    {
        $status = 'Loss Making';
        if ($branch->net_margin_percentage >= 20) {
            $status = 'Profitable';
        } elseif ($branch->net_margin_percentage >= 10) {
            $status = 'Marginal';
        }

        return [
            $branch->branch->name ?? 'Unknown Branch',
            number_format($branch->total_revenue, 2),
            number_format($branch->estimated_expenses, 2),
            number_format($branch->net_profit, 2),
            number_format($branch->net_margin_percentage, 1) . '%',
            $status
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Branch Profitability';
    }
}
