<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PettyCashRegisterExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $entries;
    protected $unit;
    protected $dateFrom;
    protected $dateTo;
    protected $filters;

    public function __construct($entries, $unit, $dateFrom, $dateTo, $filters)
    {
        $this->entries = $entries;
        $this->unit = $unit;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->filters = $filters;
    }

    public function collection()
    {
        return $this->entries;
    }

    public function headings(): array
    {
        return [
            'PCV Number',
            'Date',
            'Entry Type',
            'Description',
            'Amount (TZS)',
            'Nature',
            'GL Account',
            'Requested By',
            'Approved By',
            'Status',
            'Balance After (TZS)'
        ];
    }

    public function map($entry): array
    {
        $sign = $entry->nature === 'debit' ? '-' : '+';
        
        return [
            $entry->pcv_number ?? 'N/A',
            $entry->register_date->format('Y-m-d'),
            ucfirst(str_replace('_', ' ', $entry->entry_type)),
            $entry->description,
            $sign . number_format($entry->amount, 2),
            ucfirst($entry->nature),
            $entry->glAccount->account_name ?? 'N/A',
            $entry->requestedBy->name ?? 'N/A',
            $entry->approvedBy->name ?? 'N/A',
            ucfirst($entry->status),
            number_format($entry->balance_after ?? 0, 2)
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
        return 'Petty Cash Register';
    }
}


