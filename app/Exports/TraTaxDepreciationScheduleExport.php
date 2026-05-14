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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TraTaxDepreciationScheduleExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $scheduleData;
    protected $taxYear;

    public function __construct($scheduleData, $taxYear)
    {
        $this->scheduleData = $scheduleData;
        $this->taxYear = $taxYear;
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->scheduleData as $classData) {
            // Add tax class header
            $rows[] = [
                $classData['tax_class']->class_code . ' - ' . $classData['tax_class']->description,
                '', '', '', '', '', ''
            ];

            // Add data rows for each category in this class
            foreach ($classData['categories'] as $category) {
                $rows[] = [
                    $category['category']->name ?? 'N/A',
                    $classData['tax_class']->class_code,
                    $category['opening_wdv'],
                    $category['additions'],
                    $category['disposals'],
                    $category['tax_depreciation'],
                    $category['closing_wdv'],
                ];
            }

            // Add class totals row
            $rows[] = [
                'Class Total',
                '',
                $classData['total_opening_wdv'],
                $classData['total_additions'],
                $classData['total_disposals'],
                $classData['total_tax_depreciation'],
                $classData['total_closing_wdv'],
            ];

            // Add empty row for spacing between classes
            $rows[] = ['', '', '', '', '', '', ''];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Asset Category',
            'Tax Pool Class',
            'Opening Tax WDV',
            'Additions',
            'Disposals',
            'Tax Depreciation',
            'Closing Tax WDV'
        ];
    }

    public function title(): string
    {
        return 'TRA Tax Depreciation ' . $this->taxYear;
    }

    public function styles(Worksheet $sheet)
    {
        // Style header row
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
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
        $sheet->getStyle('A1:G1')->getFont()->getColor()->setRGB('FFFFFF');

        // Find and style tax class headers and total rows
        $rowCount = count($this->array()) + 2; // +2 for heading row and 1-based indexing
        
        for ($row = 2; $row <= $rowCount; $row++) {
            $cellValue = $sheet->getCell('A' . $row)->getValue();
            
            // Style tax class headers (rows with class code)
            if (strpos($cellValue, ' - ') !== false && $sheet->getCell('B' . $row)->getValue() == '') {
                $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9E2F3']
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN
                        ]
                    ]
                ]);
            }
            
            // Style total rows
            if ($cellValue === 'Class Total') {
                $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
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
            }
        }

        // Format currency columns (C to G)
        $sheet->getStyle('C2:G' . $rowCount)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        // Right align currency columns
        $sheet->getStyle('C2:G' . $rowCount)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Add borders to all data cells
        $sheet->getStyle('A2:G' . $rowCount)->applyFromArray([
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
