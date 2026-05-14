<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReorderReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $reorderItems;

    public function __construct($reorderItems)
    {
        $this->reorderItems = $reorderItems;
    }

    public function collection()
    {
        return $this->reorderItems;
    }

    public function headings(): array
    {
        return [
            'Item Code',
            'Item Name',
            'Category',
            'Current Stock',
            'Reorder Level',
            'Suggested Quantity',
            'Unit of Measure',
            'Cost Price',
            'Unit Price',
        ];
    }

    public function map($item): array
    {
        return [
            $item['item_code'],
            $item['item_name'],
            $item['category'],
            $item['current_stock'],
            $item['reorder_level'],
            $item['suggested_qty'],
            $item['unit_of_measure'],
            number_format($item['cost_price'], 2),
            number_format($item['unit_price'], 2),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
