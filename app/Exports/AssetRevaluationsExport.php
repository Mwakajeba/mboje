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

class AssetRevaluationsExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
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

        foreach ($this->data as $rev) {
            $rows[] = [
                $rev['asset_code'],
                $rev['asset_name'],
                $rev['old_carrying_amount'],
                $rev['revalued_amount'],
                $rev['surplus_deficit'],
                $rev['revaluation_reserve_movement'],
                $rev['valuer'],
                $rev['valuation_date'],
            ];
        }

        // Add totals row
        $rows[] = [
            'TOTAL',
            '',
            array_sum(array_column($this->data, 'old_carrying_amount')),
            array_sum(array_column($this->data, 'revalued_amount')),
            $this->summary['net_movement'],
            '',
            '',
            '',
        ];

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Asset Code',
            'Asset Name',
            'Old Carrying Amount',
            'Revalued Amount',
            'Surplus/(Deficit)',
            'Revaluation Reserve Movement',
            'Valuer',
            'Valuation Date'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D6EFD']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $rowCount = count($this->data) + 2;

        $sheet->getStyle('A' . $rowCount . ':H' . $rowCount)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $sheet->getStyle('C2:F' . $rowCount)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        $sheet->getStyle('C2:F' . $rowCount)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle('A2:H' . ($rowCount - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        return [];
    }

    public function title(): string
    {
        return 'Revaluations';
    }
}
