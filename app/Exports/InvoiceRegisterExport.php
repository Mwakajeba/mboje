<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class InvoiceRegisterExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $invoices;
    protected $dateFrom;
    protected $dateTo;
    protected $branch;
    protected $status;
    protected $company;

    public function __construct($invoices, $dateFrom, $dateTo, $branch, $status, $company = null)
    {
        $this->invoices = $invoices;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->branch = $branch;
        $this->status = $status;
        $this->company = $company;
    }

    public function collection()
    {
        return $this->invoices;
    }

    public function headings(): array
    {
        return [
            'Invoice Number',
            'Customer Name',
            'Invoice Date',
            'Status',
            'Total Amount',
            'Paid Amount',
            'Outstanding Amount',
            'Created By'
        ];
    }

    public function map($invoice): array
    {
        $outstanding = $invoice->total_amount - $invoice->paid_amount;

        return [
            $invoice->invoice_number,
            $invoice->customer->name ?? 'Unknown',
            $invoice->invoice_date->format('Y-m-d'),
            ucfirst($invoice->status),
            number_format($invoice->total_amount, 2),
            number_format($invoice->paid_amount, 2),
            number_format($outstanding, 2),
            $invoice->createdBy->name ?? 'Unknown'
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
        return 'Invoice Register';
    }
}
