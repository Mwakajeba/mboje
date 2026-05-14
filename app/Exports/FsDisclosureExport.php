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

class FsDisclosureExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $rows = [];
        $totalOpening = 0;
        $totalAdditions = 0;
        $totalDisposals = 0;
        $totalRevaluations = 0;
        $totalDepreciation = 0;
        $totalClosing = 0;

        foreach ($this->data as $item) {
            $rows[] = [
                $item['category'],
                $item['opening_balance'],
                $item['additions'],
                $item['disposals'],
                $item['revaluations'],
                $item['depreciation'],
                $item['closing_balance'],
            ];

            $totalOpening += $item['opening_balance'];
            $totalAdditions += $item['additions'];
            $totalDisposals += $item['disposals'];
            $totalRevaluations += $item['revaluations'];
            $totalDepreciation += $item['depreciation'];
            $totalClosing += $item['closing_balance'];
        }

        $rows[] = [
            'TOTAL',
            $totalOpening,
            $totalAdditions,
            $totalDisposals,
            $totalRevaluations,
            $totalDepreciation,
            $totalClosing,
        ];

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Category',
            'Opening Balance',
            'Additions',
            'Disposals',
            'Revaluations',
            'Depreciation',
            'Closing Balance'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '343A40']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $rowCount = count($this->data) + 2;
        
        $sheet->getStyle('A' . $rowCount . ':G' . $rowCount)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $sheet->getStyle('B2:G' . $rowCount)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        $sheet->getStyle('B2:G' . $rowCount)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle('A2:G' . ($rowCount - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        return [];
    }

    public function title(): string
    {
        return 'FS Disclosure';
    }
}
