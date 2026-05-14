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

class EmployeePayrollHistoryExport implements FromArray, WithStyles, ShouldAutoSize, WithTitle
{
    protected $employee;
    protected $payrollHistory;
    protected $ytdTotals;
    protected $year;

    public function __construct($employee, $payrollHistory, $ytdTotals, $year)
    {
        $this->employee = $employee;
        $this->payrollHistory = $payrollHistory;
        $this->ytdTotals = $ytdTotals;
        $this->year = $year;
    }

    public function array(): array
    {
        $data = [];
        
        $data[] = ['Employee Payroll History Report'];
        $data[] = ['Employee: ' . ($this->employee->full_name ?? $this->employee->first_name . ' ' . $this->employee->last_name)];
        $data[] = ['Employee Number: ' . $this->employee->employee_number];
        $data[] = ['Year: ' . $this->year];
        $data[] = [];

        $data[] = ['Period', 'Gross Salary', 'Total Deductions', 'Net Salary', 'PAYE', 'NHIF', 'Pension'];

        foreach ($this->payrollHistory as $ph) {
            $data[] = [
                \Carbon\Carbon::create($ph->payroll->year, $ph->payroll->month, 1)->format('F Y'),
                number_format($ph->gross_salary, 2),
                number_format($ph->total_deductions, 2),
                number_format($ph->net_salary, 2),
                number_format($ph->paye, 2),
                number_format($ph->insurance, 2),
                number_format($ph->pension, 2),
            ];
        }

        $data[] = [];
        $data[] = [
            'YTD Totals',
            number_format($this->ytdTotals['gross_salary'], 2),
            number_format($this->ytdTotals['total_deductions'], 2),
            number_format($this->ytdTotals['net_salary'], 2),
            number_format($this->ytdTotals['paye'], 2),
            number_format($this->ytdTotals['nhif'], 2),
            number_format($this->ytdTotals['pension'], 2),
        ];

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->payrollHistory) + 8;
        
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '007bff']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true]],
            6 => [
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
        return 'Employee History';
    }
}
