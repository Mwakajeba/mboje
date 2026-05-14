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

class WcfRemittanceExport implements FromArray, WithStyles, ShouldAutoSize, WithTitle
{
    protected $reportData;
    protected $totalGross;
    protected $totalWCF;
    protected $year;
    protected $month;

    public function __construct($reportData, $totalGross, $totalWCF, $year, $month)
    {
        $this->reportData = $reportData;
        $this->totalGross = $totalGross;
        $this->totalWCF = $totalWCF;
        $this->year = $year;
        $this->month = $month;
    }

    public function array(): array
    {
        $data = [];
        
        $monthName = \Carbon\Carbon::create($this->year, $this->month, 1)->format('F Y');
        $data[] = ['WCF CONTRIBUTION SCHEDULE'];
        $data[] = ['Period: ' . $monthName];
        $data[] = [];

        $data[] = ['S/N', 'Employee No', 'Employee Name', 'Gross Salary', 'WCF Rate (%)', 'WCF Amount'];

        foreach ($this->reportData as $item) {
            $data[] = [
                $item['sn'],
                $item['employee_number'],
                $item['employee_name'],
                number_format($item['gross_salary'], 2),
                number_format($item['wcf_rate'], 2),
                number_format($item['wcf_amount'], 2),
            ];
        }

        $data[] = [
            '',
            '',
            'TOTAL',
            number_format($this->totalGross, 2),
            '',
            number_format($this->totalWCF, 2),
        ];

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->reportData) + 5;
        
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17a2b8']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER']],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17a2b8']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
            $lastRow => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '28a745']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
        ];
    }

    public function title(): string
    {
        return 'WCF Remittance';
    }
}
