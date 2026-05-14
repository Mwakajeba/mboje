<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class SupplierStatementExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $supplier;
    protected $transactions;
    protected $dateFrom;
    protected $dateTo;
    protected $branch;
    protected $openingBalance;
    protected $totalInvoices;
    protected $totalPayments;
    protected $totalDebitNotes;
    protected $closingBalance;
    protected $company;

    public function __construct($supplier, $transactions, Carbon $dateFrom, Carbon $dateTo, $branch, $openingBalance, $totalInvoices, $totalPayments, $totalDebitNotes, $closingBalance, $company = null)
    {
        $this->supplier = $supplier;
        $this->transactions = $transactions;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->branch = $branch;
        $this->openingBalance = $openingBalance;
        $this->totalInvoices = $totalInvoices;
        $this->totalPayments = $totalPayments;
        $this->totalDebitNotes = $totalDebitNotes;
        $this->closingBalance = $closingBalance;
        $this->company = $company;
    }

    public function collection()
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Description',
            'Invoiced',
            'Payments',
            'Balance'
        ];
    }

    public function map($transaction): array
    {
        $invoiced = '';
        $payments = '';
        
        if ($transaction->type == 'invoice') {
            $invoiced = number_format($transaction->amount, 2);
        } elseif ($transaction->type == 'payment') {
            $payments = number_format($transaction->amount, 2);
        } elseif ($transaction->type == 'debit_note') {
            $payments = number_format($transaction->amount, 2); // Debit notes reduce balance like payments
        }
        
        return [
            $transaction->date->format('Y-m-d'),
            $transaction->description,
            $invoiced,
            $payments,
            number_format($transaction->balance ?? 0, 2)
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
        return 'Supplier Statement - ' . $this->supplier->name;
    }
}

