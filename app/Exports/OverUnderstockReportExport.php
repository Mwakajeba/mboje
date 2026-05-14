<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OverUnderstockReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $stockAnalysis;

    public function __construct($stockAnalysis)
    {
        $this->stockAnalysis = $stockAnalysis;
    }

    public function collection()
    {
        return $this->stockAnalysis;
    }

    public function headings(): array
    {
        return [
            'Item Code',
            'Item Name',
            'Category',
            'Current Stock',
            'Minimum Stock',
            'Maximum Stock',
            'Status',
            'Variance',
            'Value',
            'Unit of Measure',
            'Cost Price',
        ];
    }

    public function map($item): array
    {
        return [
            $item['item_code'],
            $item['item_name'],
            $item['category'],
            $item['current_stock'],
            $item['minimum_stock'],
            $item['maximum_stock'],
            ucfirst($item['status']),
            $item['variance'],
            number_format($item['value'], 2),
            $item['unit_of_measure'],
            number_format($item['cost_price'], 2),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
