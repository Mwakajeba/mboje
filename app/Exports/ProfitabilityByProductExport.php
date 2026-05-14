<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class ProfitabilityByProductExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $productProfitability;
    protected $dateFrom;
    protected $dateTo;
    protected $branch;
    protected $company;

    public function __construct($productProfitability, $dateFrom, $dateTo, $branch, $company = null)
    {
        $this->productProfitability = $productProfitability;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->branch = $branch;
        $this->company = $company;
    }

    public function collection()
    {
        return $this->productProfitability;
    }

    public function headings(): array
    {
        return [
            'Product Name',
            'Quantity Sold',
            'Total Revenue',
            'Total COGS',
            'Gross Profit',
            'Margin Percentage',
            'Average Unit Price'
        ];
    }

    public function map($product): array
    {
        $marginPercentage = $product->total_revenue > 0 ? ($product->gross_profit / $product->total_revenue) * 100 : 0;

        return [
            $product->inventoryItem->name ?? 'Unknown Product',
            number_format($product->total_quantity, 0),
            number_format($product->total_revenue, 2),
            number_format($product->total_cogs, 2),
            number_format($product->gross_profit, 2),
            number_format($marginPercentage, 2) . '%',
            number_format($product->avg_unit_price, 2)
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
        return 'Profitability by Product';
    }
}
