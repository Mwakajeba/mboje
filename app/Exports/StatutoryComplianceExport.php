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

class StatutoryComplianceExport implements FromArray, WithStyles, ShouldAutoSize, WithTitle
{
    protected $statutoryData;
    protected $totals;
    protected $year;
    protected $month;

    public function __construct($statutoryData, $totals, $year, $month)
    {
        $this->statutoryData = $statutoryData;
        $this->totals = $totals;
        $this->year = $year;
        $this->month = $month;
    }

    public function array(): array
    {
        $data = [];
        
        $monthName = \Carbon\Carbon::create($this->year, $this->month, 1)->format('F Y');
        $data[] = ['Statutory Compliance Report'];
        $data[] = ['Period: ' . $monthName];
        $data[] = [];

        $data[] = ['Statutory Item', 'Employee Contribution', 'Employer Contribution', 'Total', 'Compliance Rate'];

        foreach ($this->statutoryData as $item) {
            $total = $item['employee_total'] + $item['employer_total'];
            $data[] = [
                $item['name'],
                number_format($item['employee_total'], 2),
                number_format($item['employer_total'], 2),
                number_format($total, 2),
                number_format($item['compliance_rate'], 2) . '%',
            ];
        }

        $data[] = [
            'Total',
            number_format($this->totals['total_employee_contributions'], 2),
            number_format($this->totals['total_employer_contributions'], 2),
            number_format($this->totals['grand_total'], 2),
            '100.00%',
        ];

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->statutoryData) + 5;
        
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '28a745']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
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
        return 'Statutory Compliance';
    }
}
