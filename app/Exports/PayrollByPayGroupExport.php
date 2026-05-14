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

class PayrollByPayGroupExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $payGroupData;
    protected $totals;
    protected $year;
    protected $month;

    public function __construct($payGroupData, $totals, $year, $month)
    {
        $this->payGroupData = $payGroupData;
        $this->totals = $totals;
        $this->year = $year;
        $this->month = $month;
    }

    public function array(): array
    {
        $data = [];
        
        $monthName = \Carbon\Carbon::create($this->year, $this->month, 1)->format('F Y');
        $data[] = ['Payroll by Pay Group Report'];
        $data[] = ['Period: ' . $monthName];
        $data[] = [];

        $data[] = ['Pay Group', 'Employees', 'Gross Salary', 'Deductions', 'Net Pay', 'Avg. Salary'];

        foreach ($this->payGroupData as $group) {
            $data[] = [
                $group['pay_group']->pay_group_code . ' - ' . $group['pay_group']->pay_group_name,
                $group['employee_count'],
                number_format($group['total_gross'], 2),
                number_format($group['total_deductions'], 2),
                number_format($group['total_net'], 2),
                number_format($group['average_salary'], 2),
            ];
        }

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
        $lastRow = count($this->payGroupData) + 5;
        
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17a2b8']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
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
        return 'Payroll by Pay Group';
    }
}
