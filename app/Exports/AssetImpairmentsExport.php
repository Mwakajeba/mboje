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

class AssetImpairmentsExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
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

        foreach ($this->data as $imp) {
            $rows[] = [
                $imp['asset_code'],
                $imp['cgu'],
                $imp['carrying_amount_before'],
                $imp['recoverable_amount'],
                $imp['impairment_loss'],
                $imp['reversal'],
                $imp['carrying_amount_after'],
            ];
        }

        $rows[] = [
            'TOTAL',
            '',
            '',
            '',
            $this->summary['total_loss'],
            $this->summary['total_reversals'],
            $this->summary['total_carrying_after'],
        ];

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Asset Code',
            'CGU',
            'Carrying Amount Before',
            'Recoverable Amount',
            'Impairment Loss',
            'Reversal',
            'Carrying Amount After'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC107']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $rowCount = count($this->data) + 2;
        
        $sheet->getStyle('A' . $rowCount . ':G' . $rowCount)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $sheet->getStyle('C2:G' . $rowCount)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        $sheet->getStyle('C2:G' . $rowCount)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle('A2:G' . ($rowCount - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        return [];
    }

    public function title(): string
    {
        return 'Impairments';
    }
}
