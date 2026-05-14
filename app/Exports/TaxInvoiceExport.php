<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class TaxInvoiceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $taxInvoices;
    protected $dateFrom;
    protected $dateTo;
    protected $branch;
    protected $taxType;
    protected $company;

    public function __construct($taxInvoices, $dateFrom, $dateTo, $branch, $taxType, $company = null)
    {
        $this->taxInvoices = $taxInvoices;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->branch = $branch;
        $this->taxType = $taxType;
        $this->company = $company;
    }

    public function collection()
    {
        return $this->taxInvoices;
    }

    public function headings(): array
    {
        return [
            'Invoice Number',
            'Customer Name',
            'Invoice Date',
            'Tax Type',
            'Tax Rate',
            'Subtotal',
            'VAT Amount',
            'WHT Amount',
            'Total Amount'
        ];
    }

    public function map($invoice): array
    {
        $hasVat = $invoice->vat_amount > 0;
        $hasWht = $invoice->withholding_tax_amount > 0;
        
        if ($hasVat && $hasWht) {
            $taxType = 'Both';
        } elseif ($hasVat) {
            $taxType = 'VAT';
        } elseif ($hasWht) {
            $taxType = 'WHT';
        } else {
            $taxType = 'None';
        }

        $taxRate = '';
        if ($hasVat && $hasWht) {
            $taxRate = 'V:' . number_format($invoice->vat_rate ?? 0, 1) . '% W:' . number_format($invoice->withholding_tax_rate ?? 0, 1) . '%';
        } elseif ($hasVat) {
            $taxRate = number_format($invoice->vat_rate ?? 0, 1) . '%';
        } elseif ($hasWht) {
            $taxRate = number_format($invoice->withholding_tax_rate ?? 0, 1) . '%';
        } else {
            $taxRate = '-';
        }

        return [
            $invoice->invoice_number,
            $invoice->customer->name ?? 'Unknown',
            Carbon::parse($invoice->invoice_date)->format('m/d/Y'),
            $taxType,
            $taxRate,
            number_format($invoice->subtotal, 0),
            number_format($invoice->vat_amount, 0),
            number_format($invoice->withholding_tax_amount, 0),
            number_format($invoice->total_amount, 0)
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
        return 'Tax Invoice Report';
    }
}
