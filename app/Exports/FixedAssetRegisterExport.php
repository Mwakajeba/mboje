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

class FixedAssetRegisterExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $data;
    protected $asOfDate;

    public function __construct($data, $asOfDate)
    {
        $this->data = $data;
        $this->asOfDate = $asOfDate;
    }

    public function array(): array
    {
        $rows = [];
        $totals = [
            'purchase_cost' => 0,
            'accumulated_depreciation' => 0,
            'impairment_amount' => 0,
            'carrying_amount' => 0
        ];

        foreach ($this->data as $asset) {
            $rows[] = [
                $asset['code'],
                $asset['name'],
                $asset['category'],
                $asset['location'],
                $asset['custodian'],
                $asset['serial_number'],
                $asset['purchase_date'],
                $asset['capitalization_date'],
                $asset['purchase_cost'],
                $asset['useful_life'],
                $asset['depreciation_method'],
                $asset['accumulated_depreciation'],
                $asset['impairment_amount'],
                $asset['carrying_amount'],
                $asset['status'],
            ];

            $totals['purchase_cost'] += $asset['purchase_cost'];
            $totals['accumulated_depreciation'] += $asset['accumulated_depreciation'];
            $totals['impairment_amount'] += $asset['impairment_amount'];
            $totals['carrying_amount'] += $asset['carrying_amount'];
        }

        // Add totals row
        $rows[] = [
            'TOTAL',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            $totals['purchase_cost'],
            '',
            '',
            $totals['accumulated_depreciation'],
            $totals['impairment_amount'],
            $totals['carrying_amount'],
            '',
        ];

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Asset Code',
            'Asset Name',
            'Category',
            'Location',
            'Custodian',
            'Serial No',
            'Purchase Date',
            'Capitalization Date',
            'Cost',
            'Useful Life (Yrs)',
            'Depreciation Method',
            'Accumulated Depreciation',
            'Accumulated Impairment',
            'Carrying Amount (NBV)',
            'Status'
        ];
    }

    public function title(): string
    {
        return 'Asset Register ' . date('Y-m-d', strtotime($this->asOfDate));
    }

    public function styles(Worksheet $sheet)
    {
        // Style header row
        $sheet->getStyle('A1:O1')->applyFromArray([
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
        $sheet->getStyle('A1:O1')->getFont()->getColor()->setRGB('FFFFFF');

        // Find the totals row
        $rowCount = count($this->array()) + 2; // +2 for heading row and 1-based indexing
        
        // Style totals row
        $sheet->getStyle('A' . $rowCount . ':O' . $rowCount)->applyFromArray([
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

        // Format currency columns (I, L, M, N)
        $sheet->getStyle('I2:I' . $rowCount)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('L2:N' . $rowCount)->getNumberFormat()->setFormatCode('#,##0.00');

        // Right align currency columns
        $sheet->getStyle('I2:I' . $rowCount)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('L2:N' . $rowCount)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Center align useful life and method columns
        $sheet->getStyle('J2:K' . $rowCount)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Add borders to all data cells
        $sheet->getStyle('A2:O' . $rowCount)->applyFromArray([
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
