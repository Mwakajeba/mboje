<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PayrollByDepartmentExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $departmentData;
    protected $totals;
    protected $year;
    protected $month;

    public function __construct($departmentData, $totals, $year, $month)
    {
        $this->departmentData = $departmentData;
        $this->totals = $totals;
        $this->year = $year;
        $this->month = $month;
    }

    public function array(): array
    {
        $data = [];
        
        // Add report title and period
        $monthName = \Carbon\Carbon::create($this->year, $this->month, 1)->format('F Y');
        $data[] = ['Payroll by Department Report'];
        $data[] = ['Period: ' . $monthName];
        $data[] = []; // Empty row

        // Add column headers
        $data[] = ['Department', 'Employees', 'Gross Salary', 'Deductions', 'Net Pay', 'Avg. Salary'];

        // Add department data
        foreach ($this->departmentData as $dept) {
            $data[] = [
                $dept['department']->name,
                $dept['employee_count'],
                number_format($dept['total_gross'], 2),
                number_format($dept['total_deductions'], 2),
                number_format($dept['total_net'], 2),
                number_format($dept['average_salary'], 2),
            ];
        }

        // Add totals row
        $avgSalary = $this->totals['total_employees'] > 0 
            ? $this->totals['total_net'] / $this->totals['total_employees'] 
            : 0;
        
        $data[] = [
            'Total',
            $this->totals['total_employees'],
            number_format($this->totals['total_gross'], 2),
            number_format($this->totals['total_deductions'], 2),
            number_format($this->totals['total_net'], 2),
            number_format($avgSalary, 2),
        ];

        return $data;
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->departmentData) + 5;
        
        return [
            // Title row
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17a2b8']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Period row
            2 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Header row
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17a2b8']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
            // Total row
            $lastRow => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '28a745']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
        ];
    }

    public function title(): string
    {
        return 'Payroll by Department';
    }
}
