<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class SalesByBranchExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $branchSales;
    protected $dateFrom;
    protected $dateTo;
    protected $company;

    public function __construct($branchSales, $dateFrom, $dateTo, $company = null)
    {
        $this->branchSales = $branchSales;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->company = $company;
    }

    public function collection()
    {
        return $this->branchSales;
    }

    public function headings(): array
    {
        return [
            'Branch Name',
            'Total Sales',
            'Invoice Count',
            'Customers Served',
            'Average Invoice Value',
            'Sales Percentage'
        ];
    }

    public function map($branch): array
    {
        $totalSales = $this->branchSales->sum('total_sales');
        $salesPercentage = $totalSales > 0 ? ($branch->total_sales / $totalSales) * 100 : 0;

        return [
            $branch->branch->name ?? 'Unknown',
            number_format($branch->total_sales, 2),
            $branch->invoice_count,
            $branch->customers_served,
            number_format($branch->avg_invoice_value, 2),
            number_format($salesPercentage, 2) . '%'
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
        return 'Sales by Branch';
    }
}
