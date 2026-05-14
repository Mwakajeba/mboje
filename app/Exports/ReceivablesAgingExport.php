<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class ReceivablesAgingExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $agingData;
    protected $asOfDate;
    protected $branch;
    protected $company;

    public function __construct($agingData, $asOfDate, $branch, $company = null)
    {
        $this->agingData = $agingData;
        $this->asOfDate = $asOfDate;
        $this->branch = $branch;
        $this->company = $company;
    }

    public function collection()
    {
        return $this->agingData;
    }

    public function headings(): array
    {
        return [
            'Customer Name',
            'Invoice Number',
            'Invoice Date',
            'Total Amount',
            'Paid Amount',
            'Outstanding Amount',
            'Days Outstanding',
            'Aging Bucket'
        ];
    }

    public function map($item): array
    {
        return [
            $item['customer_name'],
            $item['invoice_number'],
            Carbon::parse($item['invoice_date'])->format('Y-m-d'),
            number_format($item['total_amount'], 2),
            number_format($item['paid_amount'], 2),
            number_format($item['outstanding_amount'], 2),
            $item['days_outstanding'],
            $item['aging_bucket']
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
        return 'Receivables Aging';
    }
}
