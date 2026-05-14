<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AssetsByCategoryExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $data;
    protected $summary;

    public function __construct($data, $summary)
    {
        $this->data = $data;
        $this->summary = $summary;
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->data as $item) {
            $rows[] = [
                $item['category'],
                $item['total_cost'],
                $item['total_accumulated_depreciation'],
                $item['total_impairment'],
                $item['net_book_value'],
            ];
        }

        $rows[] = [
            'TOTAL',
            $this->summary['total_cost'],
            $this->summary['total_depreciation'],
            $this->summary['total_impairment'],
            $this->summary['total_nbv'],
        ];

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Category',
            'Total Cost',
            'Total Accumulated Depreciation',
            'Total Impairment',
            'Net Book Value'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D6EFD']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $rowCount = count($this->data) + 2;
        
        $sheet->getStyle('A' . $rowCount . ':E' . $rowCount)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $sheet->getStyle('B2:E' . $rowCount)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        $sheet->getStyle('B2:E' . $rowCount)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle('A2:E' . ($rowCount - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        return [];
    }

    public function title(): string
    {
        return 'Assets by Category';
    }
}
