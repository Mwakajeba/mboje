<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class CollectionEfficiencyExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $collectionData;
    protected $dateFrom;
    protected $dateTo;
    protected $branch;
    protected $company;

    public function __construct($collectionData, $dateFrom, $dateTo, $branch, $company = null)
    {
        $this->collectionData = $collectionData;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->branch = $branch;
        $this->company = $company;
    }

    public function collection()
    {
        return $this->collectionData;
    }

    public function headings(): array
    {
        return [
            'Invoice Number',
            'Customer Name',
            'Invoice Date',
            'Total Amount',
            'Paid Amount',
            'Outstanding Amount',
            'Collection Rate',
            'Days Outstanding'
        ];
    }

    public function map($item): array
    {
        return [
            $item['invoice_number'],
            $item['customer_name'],
            Carbon::parse($item['invoice_date'])->format('Y-m-d'),
            number_format($item['total_amount'], 2),
            number_format($item['paid_amount'], 2),
            number_format($item['outstanding_amount'], 2),
            number_format($item['collection_rate'], 2) . '%',
            $item['days_outstanding'] >= 0 ? round($item['days_outstanding']) : 0
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
        return 'Collection Efficiency';
    }
}
