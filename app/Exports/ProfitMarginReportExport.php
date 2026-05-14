<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProfitMarginReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $profitData;
    protected $dateFrom;
    protected $dateTo;

    public function __construct($profitData, $dateFrom, $dateTo)
    {
        $this->profitData = $profitData;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function collection()
    {
        return $this->profitData;
    }

    public function headings(): array
    {
        return [
            'Item Code',
            'Item Name',
            'Sold Quantity',
            'Sales Revenue',
            'Cost of Goods',
            'Gross Margin',
            'Gross Margin %',
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
            $item['sold_qty'],
            number_format($item['sales_revenue'], 2),
            number_format($item['cost_of_goods'], 2),
            number_format($item['gross_margin'], 2),
            number_format($item['gross_margin_percent'], 2) . '%',
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
