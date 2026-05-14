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

class NssfRemittanceExport implements FromArray, WithStyles, ShouldAutoSize, WithTitle
{
    protected $reportData;
    protected $totalEmployee;
    protected $totalEmployer;
    protected $year;
    protected $month;

    public function __construct($reportData, $totalEmployee, $totalEmployer, $year, $month)
    {
        $this->reportData = $reportData;
        $this->totalEmployee = $totalEmployee;
        $this->totalEmployer = $totalEmployer;
        $this->year = $year;
        $this->month = $month;
    }

    public function array(): array
    {
        $data = [];
        
        $monthName = \Carbon\Carbon::create($this->year, $this->month, 1)->format('F Y');
        $data[] = ['NSSF CONTRIBUTION SCHEDULE'];
        $data[] = ['Period: ' . $monthName];
        $data[] = [];

        $data[] = ['S/N', 'Employee No', 'Employee Name', 'NSSF Number', 'Pensionable Salary', 'Employee Contribution', 'Employer Contribution', 'Total'];

        foreach ($this->reportData as $item) {
            $data[] = [
                $item['sn'],
                $item['employee_number'],
                $item['employee_name'],
                $item['nssf_number'],
                number_format($item['pensionable_salary'], 2),
                number_format($item['employee_contribution'], 2),
                number_format($item['employer_contribution'], 2),
                number_format($item['total_contribution'], 2),
            ];
        }

        $data[] = [
            '',
            '',
            'TOTAL',
            '',
            '',
            number_format($this->totalEmployee, 2),
            number_format($this->totalEmployer, 2),
            number_format($this->totalEmployee + $this->totalEmployer, 2),
        ];

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->reportData) + 5;
        
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '007bff']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER']],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '007bff']],
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
        return 'NSSF Remittance';
    }
}
