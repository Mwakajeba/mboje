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

class PayrollCostAnalysisExport implements FromArray, WithStyles, ShouldAutoSize, WithTitle
{
    protected $costBreakdown;
    protected $year;
    protected $month;

    public function __construct($costBreakdown, $year, $month)
    {
        $this->costBreakdown = $costBreakdown;
        $this->year = $year;
        $this->month = $month;
    }

    public function array(): array
    {
        $data = [];
        
        $monthName = \Carbon\Carbon::create($this->year, $this->month, 1)->format('F Y');
        $data[] = ['Payroll Cost Analysis Report'];
        $data[] = ['Period: ' . $monthName];
        $data[] = [];

        $data[] = ['Cost Category', 'Amount (TZS)'];

        $data[] = ['Basic Salary', number_format($this->costBreakdown['basic_salary'], 2)];
        $data[] = ['Allowances', number_format($this->costBreakdown['allowances'], 2)];
        $data[] = ['Overtime', number_format($this->costBreakdown['overtime'], 2)];
        $data[] = ['Gross Salary', number_format($this->costBreakdown['gross_salary'], 2)];
        $data[] = [];
        $data[] = ['Statutory Deductions', number_format($this->costBreakdown['statutory_deductions'], 2)];
        $data[] = ['Other Deductions', number_format($this->costBreakdown['other_deductions'], 2)];
        $data[] = ['Net Pay', number_format($this->costBreakdown['net_pay'], 2)];
        $data[] = [];
        $data[] = ['Employer Contributions', number_format($this->costBreakdown['employer_contributions'], 2)];
        $data[] = [];
        $data[] = ['Total Cost to Company', number_format($this->costBreakdown['total_cost'], 2)];

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
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
            16 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ffc107']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
        ];
    }

    public function title(): string
    {
        return 'Cost Analysis';
    }
}
