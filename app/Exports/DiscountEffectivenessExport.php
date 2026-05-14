<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class DiscountEffectivenessExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $discountData;
    protected $dateFrom;
    protected $dateTo;
    protected $branch;
    protected $company;

    public function __construct($discountData, $dateFrom, $dateTo, $branch, $company = null)
    {
        $this->discountData = $discountData;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->branch = $branch;
        $this->company = $company;
    }

    public function collection()
    {
        return $this->discountData;
    }

    public function headings(): array
    {
        return [
            'Invoice Number',
            'Customer Name',
            'Invoice Date',
            'Gross Sales',
            'Discount Amount',
            'Net Sales',
            'Discount Percentage'
        ];
    }

    public function map($item): array
    {
        return [
            $item['invoice_number'],
            $item['customer_name'],
            Carbon::parse($item['invoice_date'])->format('Y-m-d'),
            number_format($item['gross_sales'], 2),
            number_format($item['discount_amount'], 2),
            number_format($item['net_sales'], 2),
            number_format($item['discount_percentage'], 2) . '%'
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
        return 'Discount Effectiveness';
    }
}
