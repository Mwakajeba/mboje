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

class AssetMovementScheduleExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $data;
    protected $fromDate;
    protected $toDate;

    public function __construct($data, $fromDate, $toDate)
    {
        $this->data = $data;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function array(): array
    {
        $rows = [];
        $totals = [
            'opening_cost' => 0,
            'additions' => 0,
            'disposals' => 0,
            'transfers' => 0,
            'revaluation' => 0,
            'closing_cost' => 0,
            'opening_accum_dep' => 0,
            'depreciation_charge' => 0,
            'disposal_dep_removed' => 0,
            'impairment' => 0,
            'closing_accum_dep' => 0,
            'closing_nbv' => 0
        ];

        foreach ($this->data as $category) {
            $rows[] = [
                $category['category_name'],
                $category['opening_cost'],
                $category['additions'],
                $category['disposals'],
                $category['transfers'],
                $category['revaluation'],
                $category['closing_cost'],
                $category['opening_accum_dep'],
                $category['depreciation_charge'],
                $category['disposal_dep_removed'],
                $category['impairment'],
                $category['closing_accum_dep'],
                $category['closing_nbv'],
            ];

            foreach (array_keys($totals) as $key) {
                $totals[$key] += $category[$key];
            }
        }

        // Add totals row
        $rows[] = [
            'GRAND TOTAL',
            $totals['opening_cost'],
            $totals['additions'],
            $totals['disposals'],
            $totals['transfers'],
            $totals['revaluation'],
            $totals['closing_cost'],
            $totals['opening_accum_dep'],
            $totals['depreciation_charge'],
            $totals['disposal_dep_removed'],
            $totals['impairment'],
            $totals['closing_accum_dep'],
            $totals['closing_nbv'],
        ];

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Asset Category',
            'Opening Cost',
            'Additions',
            'Disposals',
            'Transfers',
            'Revaluation',
            'Closing Cost',
            'Opening Accum Dep',
            'Depreciation Charge',
            'Disposal Dep Removed',
            'Impairment',
            'Closing Accum Dep',
            'Closing NBV'
        ];
    }

    public function title(): string
    {
        return 'Movement Schedule';
    }

    public function styles(Worksheet $sheet)
    {
        // Style header row
        $sheet->getStyle('A1:M1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
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

        // Set font color for header to white
        $sheet->getStyle('A1:M1')->getFont()->getColor()->setRGB('FFFFFF');

        // Find the totals row
        $rowCount = count($this->array()) + 2;
        
        // Style totals row
        $sheet->getStyle('A' . $rowCount . ':M' . $rowCount)->applyFromArray([
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

        // Format currency columns (B to M)
        $sheet->getStyle('B2:M' . $rowCount)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        // Right align currency columns
        $sheet->getStyle('B2:M' . $rowCount)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Add borders to all data cells
        $sheet->getStyle('A2:M' . $rowCount)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ]
        ]);

        return $sheet;
    }
}
