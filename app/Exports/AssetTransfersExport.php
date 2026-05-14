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

class AssetTransfersExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
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

        foreach ($this->data as $transfer) {
            $rows[] = [
                $transfer['asset_code'],
                $transfer['from_location'],
                $transfer['to_location'],
                $transfer['transfer_date'],
                $transfer['approved_by'],
                $transfer['remarks'],
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Asset Code',
            'From Location',
            'To Location',
            'Transfer Date',
            'Approved By',
            'Remarks'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17A2B8']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $rowCount = count($this->data) + 1;
        
        $sheet->getStyle('A2:F' . $rowCount)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        return [];
    }

    public function title(): string
    {
        return 'Asset Transfers';
    }
}
