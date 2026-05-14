<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class SalesSummaryExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $summaryData;
    protected $dateFrom;
    protected $dateTo;
    protected $branch;
    protected $customer;
    protected $groupBy;
    protected $company;

    public function __construct($summaryData, $dateFrom, $dateTo, $branch, $customer, $groupBy, $company = null)
    {
        $this->summaryData = $summaryData;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->branch = $branch;
        $this->customer = $customer;
        $this->groupBy = $groupBy;
        $this->company = $company;
    }

    public function collection()
    {
        return $this->summaryData;
    }

    public function headings(): array
    {
        $dateLabel = ucfirst($this->groupBy);
        return [
            $dateLabel,
            'No. of Invoices',
            'Quantity Sold',
            'Total Sales (TZS)',
            'Total Discounts',
            'Net Sales',
            '% Growth vs. Prev. Period',
            'Average Daily Sales (TZS)'
        ];
    }

    public function map($row): array
    {
        return [
            $row['period_label'],
            $row['invoice_count'],
            $row['total_quantity'],
            number_format($row['total_sales'], 2),
            number_format($row['total_discounts'], 2),
            number_format($row['net_sales'], 2),
            is_null($row['growth_vs_prev']) ? '—' : (($row['growth_vs_prev'] >= 0 ? '+' : '') . number_format($row['growth_vs_prev'], 1) . '%'),
            number_format($row['average_daily_sales'], 2)
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
        return 'Sales Summary Report';
    }
}
