<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class PayablesAgingExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $invoices;
    protected $asOfDate;
    protected $branch;
    protected $supplier;
    protected $company;

    public function __construct($invoices, $asOfDate, $branch = null, $supplier = null, $company = null)
    {
        $this->invoices = $invoices;
        $this->asOfDate = $asOfDate;
        $this->branch = $branch;
        $this->supplier = $supplier;
        $this->company = $company;
    }

    public function collection()
    {
        return $this->invoices;
    }

    public function headings(): array
    {
        return [
            'Supplier Name',
            'Invoice Number',
            'Invoice Date',
            'Due Date',
            'Outstanding Amount (TZS)',
            'Days Overdue',
            'Aging Bucket',
            'Status',
        ];
    }

    public function map($invoice): array
    {
        return [
            $invoice['supplier_name'],
            $invoice['invoice_number'],
            Carbon::parse($invoice['invoice_date'])->format('d-M-Y'),
            Carbon::parse($invoice['due_date'])->format('d-M-Y'),
            number_format($invoice['outstanding_amount'], 2),
            $invoice['days_overdue'],
            $invoice['aging_bucket'],
            ucfirst($invoice['status']),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '17a2b8'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Payables Aging';
    }
}

