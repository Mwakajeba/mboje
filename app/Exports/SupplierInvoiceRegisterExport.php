<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class SupplierInvoiceRegisterExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $invoices;
    protected $dateFrom;
    protected $dateTo;
    protected $branch;
    protected $supplier;
    protected $status;
    protected $company;

    public function __construct($invoices, Carbon $dateFrom, Carbon $dateTo, $branch, $supplier, $status, $company = null)
    {
        $this->invoices = $invoices;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->branch = $branch;
        $this->supplier = $supplier;
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
            'Supplier Name',
            'Branch',
            'Invoice Date',
            'Due Date',
            'Subtotal',
            'VAT Amount',
            'Discount Amount',
            'Total Amount',
            'Paid Amount',
            'Outstanding Amount',
            'Status',
            'Currency'
        ];
    }

    public function map($invoice): array
    {
        return [
            $invoice->invoice_number,
            $invoice->supplier->name ?? 'Unknown',
            $invoice->branch->name ?? 'N/A',
            $invoice->invoice_date->format('Y-m-d'),
            $invoice->due_date ? $invoice->due_date->format('Y-m-d') : 'N/A',
            number_format($invoice->subtotal, 2),
            number_format($invoice->vat_amount, 2),
            number_format($invoice->discount_amount, 2),
            number_format($invoice->total_amount, 2),
            number_format($invoice->total_paid, 2),
            number_format($invoice->outstanding_amount, 2),
            ucfirst($invoice->status),
            $invoice->currency ?? 'TZS'
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
        return 'Supplier Invoice Register';
    }
}

