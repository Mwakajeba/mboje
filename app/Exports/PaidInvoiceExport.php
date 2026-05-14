<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class PaidInvoiceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $invoices;
    protected $dateFrom;
    protected $dateTo;
    protected $branch;
    protected $paymentStatus;
    protected $company;

    public function __construct($invoices, $dateFrom, $dateTo, $branch, $paymentStatus, $company = null)
    {
        $this->invoices = $invoices;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->branch = $branch;
        $this->paymentStatus = $paymentStatus;
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
            'Total Amount',
            'Paid Amount',
            'Outstanding Amount',
            'Payment Status'
        ];
    }

    public function map($invoice): array
    {
        $outstanding = $invoice->total_amount - $invoice->paid_amount;
        $isFullyPaid = $invoice->paid_amount >= $invoice->total_amount;
        $paymentStatus = $isFullyPaid ? 'Fully Paid' : 'Partially Paid';

        return [
            $invoice->invoice_number,
            $invoice->customer->name ?? 'Unknown',
            $invoice->invoice_date->format('Y-m-d'),
            number_format($invoice->total_amount, 2),
            number_format($invoice->paid_amount, 2),
            number_format($outstanding, 2),
            $paymentStatus
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
        return 'Payment Received Report';
    }
}
