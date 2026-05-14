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

class BookTaxReconciliationExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $reconciliationData;
    protected $asOfDate;
    protected $taxRate;

    public function __construct($reconciliationData, $asOfDate, $taxRate = 30)
    {
        $this->reconciliationData = $reconciliationData;
        $this->asOfDate = $asOfDate;
        $this->taxRate = $taxRate;
    }

    public function array(): array
    {
        $rows = [];
        $taxRateDecimal = floatval($this->taxRate) / 100;
        
        $totals = [
            'book_nbv' => 0,
            'tax_wdv' => 0,
            'temporary_difference' => 0,
            'deferred_tax' => 0
        ];

        foreach ($this->reconciliationData as $item) {
            $bookCarryingAmount = floatval($item['book_nbv']);
            $taxBase = floatval($item['tax_wdv']);
            $temporaryDifference = $bookCarryingAmount - $taxBase;
            $deferredTax = $temporaryDifference * $taxRateDecimal;
            
            $deferredTaxLabel = $deferredTax >= 0 ? ' (DTL)' : ' (DTA)';
            
            $rows[] = [
                $item['asset']->code ?? 'N/A',
                $item['asset']->name ?? 'N/A',
                $bookCarryingAmount,
                $taxBase,
                $temporaryDifference,
                $this->taxRate . '%',
                abs($deferredTax) . $deferredTaxLabel,
            ];

            $totals['book_nbv'] += $bookCarryingAmount;
            $totals['tax_wdv'] += $taxBase;
            $totals['temporary_difference'] += $temporaryDifference;
            $totals['deferred_tax'] += $deferredTax;
        }

        // Add totals row
        $deferredTaxTotalLabel = $totals['deferred_tax'] >= 0 ? ' (DTL)' : ' (DTA)';
        
        $rows[] = [
            'Total',
            '',
            $totals['book_nbv'],
            $totals['tax_wdv'],
            $totals['temporary_difference'],
            $this->taxRate . '%',
            abs($totals['deferred_tax']) . $deferredTaxTotalLabel,
        ];

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Asset Code',
            'Asset Name',
            'Book Carrying Amount',
            'Tax Base',
            'Temporary Difference',
            'Deferred Tax Rate',
            'Deferred Tax Asset/Liability'
        ];
    }

    public function title(): string
    {
        return 'Book vs Tax ' . date('Y-m-d', strtotime($this->asOfDate));
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

        // Find the totals row
        $rowCount = count($this->array()) + 1; // +1 for heading row
        
        // Style totals row
        $sheet->getStyle('A' . $rowCount . ':G' . $rowCount)->applyFromArray([
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

        // Format currency columns (C to E)
        $sheet->getStyle('C2:E' . $rowCount)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        // Right align numeric columns
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
