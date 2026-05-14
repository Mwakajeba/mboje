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

class PayeRemittanceExport implements FromArray, WithStyles, ShouldAutoSize, WithTitle
{
    protected $reportData;
    protected $totalTaxableIncome;
    protected $totalPAYE;
    protected $year;
    protected $month;

    public function __construct($reportData, $totalTaxableIncome, $totalPAYE, $year, $month)
    {
        $this->reportData = $reportData;
        $this->totalTaxableIncome = $totalTaxableIncome;
        $this->totalPAYE = $totalPAYE;
        $this->year = $year;
        $this->month = $month;
    }

    public function array(): array
    {
        $data = [];
        
        $monthName = \Carbon\Carbon::create($this->year, $this->month, 1)->format('F Y');
        $data[] = ['PAYE REMITTANCE SCHEDULE (TRA)'];
        $data[] = ['Period: ' . $monthName];
        $data[] = [];

        $data[] = ['S/N', 'Employee No', 'Employee Name', 'TIN', 'Gross Pay', 'Taxable Income', 'PAYE Amount'];

        foreach ($this->reportData as $item) {
            $data[] = [
                $item['sn'],
                $item['employee_number'],
                $item['employee_name'],
                $item['tin'],
                number_format($item['gross_pay'], 2),
                number_format($item['taxable_income'], 2),
                number_format($item['paye_amount'], 2),
            ];
        }

        $data[] = [
            '',
            '',
            'TOTAL',
            '',
            '',
            number_format($this->totalTaxableIncome, 2),
            number_format($this->totalPAYE, 2),
        ];

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->reportData) + 5;
        
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'dc3545']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER']],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'dc3545']],
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
        return 'PAYE Remittance';
    }
}
