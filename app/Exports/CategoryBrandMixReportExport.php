<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CategoryBrandMixReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $categoryMix;
    protected $grandTotalQty;
    protected $grandTotalValue;

    public function __construct($categoryMix, $grandTotalQty, $grandTotalValue)
    {
        $this->categoryMix = $categoryMix;
        $this->grandTotalQty = $grandTotalQty;
        $this->grandTotalValue = $grandTotalValue;
    }

    public function collection()
    {
        return $this->categoryMix;
    }

    public function headings(): array
    {
        return [
            'Category',
            'Items Count',
            'Total Quantity',
            'Total Value',
            'Qty Percentage',
            'Value Percentage',
        ];
    }

    public function map($category): array
    {
        return [
            $category['category_name'],
            $category['items_count'],
            $category['total_qty'],
            number_format($category['total_value'], 2),
            number_format($category['qty_percentage'], 2) . '%',
            number_format($category['value_percentage'], 2) . '%',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
