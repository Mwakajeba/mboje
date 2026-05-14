<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class DepreciationExpenseExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $data;
    protected $summary;
    protected $fromDate;
    protected $toDate;

    public function __construct($data, $summary, $fromDate, $toDate)
    {
        $this->data = $data;
        $this->summary = $summary;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->data as $item) {
            $rows[] = [
                $item['asset_code'],
                $item['asset_name'],
                $item['category_name'],
                $item['cost'],
                $item['opening_nbv'],
                $item['depreciation_rate'] . '%',
                $item['period_depreciation'],
                $item['accumulated_depreciation'],
                $item['closing_nbv'],
            ];
        }

        // Add totals row
        $rows[] = [
            'TOTAL',
            '',
            '',
            $this->summary['total_cost'],
            $this->summary['total_opening_nbv'],
            '',
            $this->summary['total_depreciation'],
            $this->summary['total_accumulated'],
            $this->summary['total_closing_nbv'],
        ];

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Asset Code',
            'Asset Name',
            'Category',
            'Cost',
            'Opening NBV',
            'Depreciation Rate',
            'Depreciation This Period',
            'Accumulated Depreciation',
            'Closing NBV'
        ];
    }

    public function title(): string
    {
        return 'Depreciation Expense';
    }

    public function styles(Worksheet $sheet)
    {
        // Style header row
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFC107']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ]);

        // Set font color for header
        $sheet->getStyle('A1:I1')->getFont()->getColor()->setRGB('000000');

        // Find the totals row
        $rowCount = count($this->array()) + 2;
        
        // Style totals row
        $sheet->getStyle('A' . $rowCount . ':I' . $rowCount)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFF2CC']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ]);

        // Format currency columns (D, E, G, H, I)
        $sheet->getStyle('D2:E' . $rowCount)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('G2:I' . $rowCount)->getNumberFormat()->setFormatCode('#,##0.00');

        // Right align currency columns
        $sheet->getStyle('D2:E' . $rowCount)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('G2:I' . $rowCount)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Center align rate column
        $sheet->getStyle('F2:F' . $rowCount)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Add borders to all data cells
        $sheet->getStyle('A2:I' . $rowCount)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ]
        ]);

        // Highlight depreciation column
        $sheet->getStyle('G2:G' . ($rowCount - 1))->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'DC3545']]
        ]);

        return $sheet;
    }
}
