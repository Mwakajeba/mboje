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

class DepreciationScheduleExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $data;
    protected $assetDetails;
    protected $summary;

    public function __construct($data, $assetDetails, $summary)
    {
        $this->data = $data;
        $this->assetDetails = $assetDetails;
        $this->summary = $summary;
    }

    public function array(): array
    {
        $rows = [];

        // Add asset details header
        $rows[] = ['DEPRECIATION SCHEDULE - FULL LIFE SCHEDULE'];
        $rows[] = ['Asset Code:', $this->assetDetails['code']];
        $rows[] = ['Asset Name:', $this->assetDetails['name']];
        $rows[] = ['Category:', $this->assetDetails['category']];
        $rows[] = ['Purchase Cost:', 'TZS ' . number_format($this->assetDetails['cost'], 2)];
        $rows[] = ['Salvage Value:', 'TZS ' . number_format($this->assetDetails['salvage_value'], 2)];
        $rows[] = ['Useful Life:', $this->assetDetails['useful_life'] . ' years'];
        $rows[] = ['Depreciation Method:', $this->assetDetails['depreciation_method']];
        $rows[] = []; // Empty row

        // Add schedule data
        foreach ($this->data as $index => $period) {
            $rows[] = [
                $index + 1,
                $period['date'],
                $period['opening_nbv'],
                $period['depreciation'],
                $period['revaluation'],
                $period['impairment'],
                $period['closing_nbv'],
            ];
        }

        // Add totals row
        $rows[] = [
            '',
            'TOTAL',
            '',
            $this->summary['total_depreciation'],
            $this->summary['total_revaluation'],
            $this->summary['total_impairment'],
            '',
        ];

        // Add summary
        $rows[] = []; // Empty row
        $rows[] = ['SUMMARY'];
        $rows[] = ['Total Depreciation:', 'TZS ' . number_format($this->summary['total_depreciation'], 2)];
        $rows[] = ['Current NBV:', 'TZS ' . number_format($this->summary['current_nbv'], 2)];
        $rows[] = ['Periods Shown:', $this->summary['period_count']];
        $rows[] = ['Remaining Life:', $this->summary['remaining_months'] . ' months'];

        return $rows;
    }

    public function headings(): array
    {
        return [
            ['Period', 'Date', 'Opening NBV', 'Depreciation', 'Revaluation Adj.', 'Impairment', 'Closing NBV']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
        ]);

        $sheet->mergeCells('A1:G1');

        $sheet->getStyle('A2:A8')->applyFromArray([
            'font' => ['bold' => true]
        ]);

        $headingRow = 10;
        
        $sheet->getStyle('A' . $headingRow . ':G' . $headingRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $dataRowCount = count($this->data);
        $totalsRow = $headingRow + $dataRowCount + 1;

        $sheet->getStyle('A' . $totalsRow . ':G' . $totalsRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $firstDataRow = $headingRow + 1;
        $lastDataRow = $totalsRow - 1;
        
        foreach (range('C', 'G') as $col) {
            $sheet->getStyle($col . $firstDataRow . ':' . $col . $lastDataRow)
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }

        foreach (['D', 'E', 'F'] as $col) {
            $sheet->getStyle($col . $totalsRow)
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }

        foreach (range('C', 'G') as $col) {
            $sheet->getStyle($col . $firstDataRow . ':' . $col . $totalsRow)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $sheet->getStyle('A' . $firstDataRow . ':G' . $lastDataRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $summaryRow = $totalsRow + 2;
        $sheet->getStyle('A' . $summaryRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12]
        ]);
        $sheet->mergeCells('A' . $summaryRow . ':B' . $summaryRow);

        $summaryDetailsStart = $summaryRow + 1;
        $summaryDetailsEnd = $summaryDetailsStart + 3;
        $sheet->getStyle('A' . $summaryDetailsStart . ':A' . $summaryDetailsEnd)->applyFromArray([
            'font' => ['bold' => true]
        ]);

        return [];
    }

    public function title(): string
    {
        return 'Depreciation Schedule';
    }
}
