<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryCountSheetsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $session;

    public function __construct($session)
    {
        $this->session = $session;
    }

    public function collection()
    {
        return $this->session->entries()->with('item')->get();
    }

    public function headings(): array
    {
        $headings = [
            'Item Code',
            'Item Name',
            'Unit of Measure',
            'Location/Bin',
        ];

        if (!$this->session->is_blind_count) {
            $headings[] = 'System Quantity';
        }

        $headings[] = 'Physical Quantity';
        $headings[] = 'Remarks';
        $headings[] = 'Condition';
        $headings[] = 'Lot/Batch Number';
        $headings[] = 'Expiry Date';

        return $headings;
    }

    public function map($entry): array
    {
        $row = [
            $entry->item->code ?? '',
            $entry->item->name ?? '',
            $entry->item->unit_of_measure ?? '',
            $entry->bin_location ?? '',
        ];

        if (!$this->session->is_blind_count) {
            $row[] = number_format($entry->system_quantity, 2);
        }

        $row[] = $entry->physical_quantity ? number_format($entry->physical_quantity, 2) : '';
        $row[] = $entry->remarks ?? '';
        $row[] = ucfirst($entry->condition ?? 'good');
        $row[] = $entry->lot_number ?? $entry->batch_number ?? '';
        $row[] = $entry->expiry_date ? $entry->expiry_date->format('Y-m-d') : '';

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']]],
        ];
    }

    public function title(): string
    {
        return 'Counting Sheets';
    }
}
