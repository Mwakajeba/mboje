<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class SalesByProductExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $productSales;
    protected $dateFrom;
    protected $dateTo;
    protected $branch;
    protected $company;

    public function __construct($productSales, $dateFrom, $dateTo, $branch, $company = null)
    {
        $this->productSales = $productSales;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->branch = $branch;
        $this->company = $company;
    }

    public function collection()
    {
        return $this->productSales;
    }

    public function headings(): array
    {
        return [
            'Product',
            'Quantity Sold',
            'Sales Value (TZS)',
            'Cost (TZS)',
            'Gross Profit',
            'Profit Margin %',
            '% Contribution'
        ];
    }

    public function map($row): array
    {
        return [
            $row->inventoryItem->name ?? 'Unknown Product',
            number_format($row->total_quantity),
            number_format($row->total_revenue, 2),
            number_format($row->total_cogs, 2),
            number_format($row->gross_profit, 2),
            number_format($row->profit_margin_percentage, 2) . '%',
            number_format($row->contribution_percentage, 2) . '%'
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
        return 'Sales by Product Report';
    }
}
