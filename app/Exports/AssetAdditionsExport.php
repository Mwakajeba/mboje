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

class AssetAdditionsExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
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

        foreach ($this->data as $addition) {
            $rows[] = [
                $addition['asset_code'],
                $addition['asset_name'],
                $addition['category'],
                $addition['invoice_no'],
                $addition['vendor'],
                $addition['purchase_date'],
                $addition['capitalized_date'],
                $addition['amount'],
                $addition['approved_by'],
            ];
        }

        $rows[] = [
            'TOTAL',
            '',
            '',
            '',
            '',
            '',
            '',
            $this->summary['total_amount'],
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
            'Invoice No',
            'Vendor',
            'Purchase Date',
            'Capitalized Date',
            'Amount',
            'Approved By'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '28A745']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $rowCount = count($this->data) + 2;
        
        $sheet->getStyle('A' . $rowCount . ':I' . $rowCount)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $sheet->getStyle('H2:H' . $rowCount)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        $sheet->getStyle('H2:H' . $rowCount)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle('A2:I' . ($rowCount - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        return [];
    }

    public function title(): string
    {
        return 'Asset Additions';
    }
}
