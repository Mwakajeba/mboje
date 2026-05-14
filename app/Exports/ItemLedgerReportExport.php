<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ItemLedgerReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $ledgerEntries;
    protected $item;

    public function __construct($ledgerEntries, $item)
    {
        $this->ledgerEntries = $ledgerEntries;
        $this->item = $item;
    }

    public function collection()
    {
        return $this->ledgerEntries;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Reference',
            'Type',
            'In Qty',
            'Out Qty',
            'Unit Cost',
            'Running Qty',
            'Running Value',
            'Avg Unit Cost',
        ];
    }

    public function map($entry): array
    {
        return [
            $entry['date'],
            $entry['reference'],
            $entry['type'],
            number_format($entry['in_qty'], 2),
            number_format($entry['out_qty'], 2),
            number_format($entry['unit_cost'], 2),
            number_format($entry['running_qty'], 2),
            number_format($entry['running_value'], 2),
            number_format($entry['avg_unit_cost'], 2),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
