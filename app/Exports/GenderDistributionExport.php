<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Http\Request;

class GenderDistributionExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $genderData;
    protected $request;

    public function __construct($genderData, Request $request)
    {
        $this->genderData = $genderData;
        $this->request = $request;
    }

    public function collection()
    {
        $data = collect();

        // Add summary row
        $data->push([
            'Summary',
            '',
            'Male Students',
            'Female Students',
            'Total Students',
            'Male Ratio'
        ]);

        $data->push([
            'Grand Total',
            '',
            $this->genderData['grandTotal']['male'],
            $this->genderData['grandTotal']['female'],
            $this->genderData['grandTotal']['total'],
            $this->genderData['grandTotal']['total'] > 0 ?
                round(($this->genderData['grandTotal']['male'] / $this->genderData['grandTotal']['total']) * 100, 1) . '%' :
                '0%'
        ]);

        $data->push(['', '', '', '', '', '']); // Empty row

        // Add header row
        $data->push([
            'Class Level',
            'Stream',
            'Male Students',
            'Female Students',
            'Total Students'
        ]);

        // Add data rows
        foreach ($this->genderData['groupedData'] as $className => $streams) {
            foreach ($streams as $streamName => $streamData) {
                $data->push([
                    $className,
                    $streamName,
                    $streamData['male'],
                    $streamData['female'],
                    $streamData['total']
                ]);
            }

            // Add class total
            $data->push([
                $className . ' Total',
                '',
                $this->genderData['classTotals'][$className]['male'],
                $this->genderData['classTotals'][$className]['female'],
                $this->genderData['classTotals'][$className]['total']
            ]);
        }

        // Add grand total again at the end
        $data->push(['', '', '', '', '', '']); // Empty row
        $data->push([
            'GRAND TOTAL',
            '',
            $this->genderData['grandTotal']['male'],
            $this->genderData['grandTotal']['female'],
            $this->genderData['grandTotal']['total'],
            ''
        ]);

        return $data;
    }

    public function headings(): array
    {
        return [
            'Gender Distribution Report',
            '',
            '',
            '',
            '',
            ''
        ];
    }

    public function map($row): array
    {
        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        // Style the header
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => '17A2B8'],
            ],
            'alignment' => [
                'horizontal' => 'center',
            ],
        ]);

        // Merge header cells
        $sheet->mergeCells('A1:F1');

        // Style summary section
        $sheet->getStyle('A2:F2')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => 'F8F9FA'],
            ],
        ]);

        $sheet->getStyle('A3:F3')->applyFromArray([
            'font' => ['bold' => true],
        ]);

        // Find the data header row (should be around row 6)
        $dataHeaderRow = 6;
        $sheet->getStyle("A{$dataHeaderRow}:F{$dataHeaderRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => '17A2B8'],
            ],
        ]);

        // Style total rows
        $lastRow = $sheet->getHighestRow();
        for ($row = 1; $row <= $lastRow; $row++) {
            $cellValue = $sheet->getCell("A{$row}")->getValue();
            if (strpos($cellValue, 'Total') !== false || strpos($cellValue, 'GRAND TOTAL') !== false) {
                $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => 'F8F9FA'],
                    ],
                ]);
            }
        }

        // Auto-size columns
        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Center numeric columns
        $sheet->getStyle('C:F')->getAlignment()->setHorizontal('center');

        return [];
    }

    public function title(): string
    {
        return 'Gender Distribution Report';
    }
}