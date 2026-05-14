<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class NhifRemittanceExport implements FromArray, WithStyles, ShouldAutoSize, WithTitle
{
    protected $reportData;
    protected $totalNHIF;
    protected $year;
    protected $month;

    public function __construct($reportData, $totalNHIF, $year, $month)
    {
        $this->reportData = $reportData;
        $this->totalNHIF = $totalNHIF;
        $this->year = $year;
        $this->month = $month;
    }

    public function array(): array
    {
        $data = [];
        
        $monthName = \Carbon\Carbon::create($this->year, $this->month, 1)->format('F Y');
        $data[] = ['NHIF CONTRIBUTION SCHEDULE'];
        $data[] = ['Period: ' . $monthName];
        $data[] = [];

        $data[] = ['S/N', 'Employee No', 'Employee Name', 'NHIF Number', 'Salary Band', 'NHIF Amount'];

        foreach ($this->reportData as $item) {
            $data[] = [
                $item['sn'],
                $item['employee_number'],
                $item['employee_name'],
                $item['nhif_number'],
                number_format($item['salary_band'], 2),
                number_format($item['nhif_amount'], 2),
            ];
        }

        $data[] = [
            '',
            '',
            'TOTAL',
            '',
            '',
            number_format($this->totalNHIF, 2),
        ];

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->reportData) + 5;
        
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '28a745']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER']],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '28a745']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
            $lastRow => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ffc107']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
        ];
    }

    public function title(): string
    {
        return 'NHIF Remittance';
    }
}
