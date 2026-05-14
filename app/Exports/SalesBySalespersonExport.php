<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class SalesBySalespersonExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $salespersonSales;
    protected $dateFrom;
    protected $dateTo;
    protected $branch;
    protected $company;

    public function __construct($salespersonSales, $dateFrom, $dateTo, $branch, $company = null)
    {
        $this->salespersonSales = $salespersonSales;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->branch = $branch;
        $this->company = $company;
    }

    public function collection()
    {
        return $this->salespersonSales;
    }

    public function headings(): array
    {
        return [
            'Salesperson Name',
            'Total Sales (TZS)',
            'Amount Collected (TZS)',
            'Outstanding Amount (TZS)',
            'Collection %',
            'Invoice Count',
            'Customers Served',
            'Contribution %'
        ];
    }

    public function map($salesperson): array
    {
        $totalSales = $this->salespersonSales->sum('total_sales');
        $salesPercentage = $totalSales > 0 ? ($salesperson->total_sales / $totalSales) * 100 : 0;

        return [
            $salesperson->createdBy->name ?? 'Unknown',
            number_format($salesperson->total_sales, 2),
            number_format($salesperson->amount_collected ?? 0, 2),
            number_format($salesperson->outstanding_amount ?? 0, 2),
            number_format($salesperson->collection_percentage ?? 0, 2) . '%',
            $salesperson->invoice_count,
            $salesperson->customers_served,
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
        return 'Sales by Salesperson';
    }
}
