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

class YearToDateSummaryExport implements FromArray, WithStyles, ShouldAutoSize, WithTitle
{
    protected $ytdTotals;
    protected $monthlyBreakdown;
    protected $year;

    public function __construct($ytdTotals, $monthlyBreakdown, $year)
    {
        $this->ytdTotals = $ytdTotals;
        $this->monthlyBreakdown = $monthlyBreakdown;
        $this->year = $year;
    }

    public function array(): array
    {
        $data = [];
        
        $data[] = ['Year-to-Date Summary Report - ' . $this->year];
        $data[] = [];

        $data[] = ['YTD Totals'];
        $data[] = ['Total Payrolls', $this->ytdTotals['total_payrolls']];
        $data[] = ['Total Employees', $this->ytdTotals['total_employees']];
        $data[] = ['Total Gross Salary', number_format($this->ytdTotals['total_gross_salary'], 2)];
        $data[] = ['Total Deductions', number_format($this->ytdTotals['total_deductions'], 2)];
        $data[] = ['Total Net Pay', number_format($this->ytdTotals['total_net_pay'], 2)];
        $data[] = [];

        $data[] = ['Monthly Breakdown'];
        $data[] = ['Month', 'Payrolls', 'Employees', 'Gross Salary', 'Net Pay'];

        foreach ($this->monthlyBreakdown as $month) {
            $data[] = [
                $month['month'],
                $month['payroll_count'],
                $month['employee_count'],
                number_format($month['gross_salary'], 2),
                number_format($month['net_pay'], 2),
            ];
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17a2b8']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            3 => ['font' => ['bold' => true, 'size' => 12]],
            10 => ['font' => ['bold' => true, 'size' => 12]],
            11 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17a2b8']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
        ];
    }

    public function title(): string
    {
        return 'YTD Summary';
    }
}
