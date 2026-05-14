<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class CreditNoteExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $creditNotes;
    protected $dateFrom;
    protected $dateTo;
    protected $branch;
    protected $company;

    public function __construct($creditNotes, $dateFrom, $dateTo, $branch, $company = null)
    {
        $this->creditNotes = $creditNotes;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->branch = $branch;
        $this->company = $company;
    }

    public function collection()
    {
        return $this->creditNotes;
    }

    public function headings(): array
    {
        return [
            'Credit Note Number',
            'Customer Name',
            'Credit Note Date',
            'Status',
            'Total Amount',
            'Applied Amount',
            'Remaining Amount'
        ];
    }

    public function map($creditNote): array
    {
        return [
            $creditNote->credit_note_number,
            $creditNote->customer->name ?? 'Unknown',
            $creditNote->credit_note_date->format('Y-m-d'),
            ucfirst($creditNote->status),
            number_format($creditNote->total_amount, 2),
            number_format($creditNote->applied_amount, 2),
            number_format($creditNote->remaining_amount, 2)
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
        return 'Credit Note Report';
    }
}
