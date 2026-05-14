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

class PayrollSummaryExport implements FromArray, WithStyles, ShouldAutoSize, WithTitle
{
    protected $summaryData;
    protected $totalEmployees;
    protected $totalGrossPay;
    protected $totalDeductions;
    protected $totalNetPay;
    protected $totalEmployerStatutory;
    protected $year;
    protected $startMonth;
    protected $endMonth;

    public function __construct($summaryData, $totalEmployees, $totalGrossPay, $totalDeductions, $totalNetPay, $totalEmployerStatutory, $year, $startMonth, $endMonth)
    {
        $this->summaryData = $summaryData;
        $this->totalEmployees = $totalEmployees;
        $this->totalGrossPay = $totalGrossPay;
        $this->totalDeductions = $totalDeductions;
        $this->totalNetPay = $totalNetPay;
        $this->totalEmployerStatutory = $totalEmployerStatutory;
        $this->year = $year;
        $this->startMonth = $startMonth;
        $this->endMonth = $endMonth;
    }

    public function array(): array
    {
        $data = [];
        
        $data[] = ['Payroll Summary Report (Executive & Audit Critical)'];
        $data[] = ['Year: ' . $this->year . ' | Period: ' . \Carbon\Carbon::create($this->year, $this->startMonth, 1)->format('F') . ' - ' . \Carbon\Carbon::create($this->year, $this->endMonth, 1)->format('F')];
        $data[] = [];

        $data[] = ['Period', 'Employees Paid', 'Gross Pay (TZS)', 'Deductions (TZS)', 'Net Pay (TZS)', 'Employer Statutory (TZS)', 'Status'];

        foreach ($this->summaryData as $item) {
            $data[] = [
                $item['period_label'],
                $item['employees_paid'],
                number_format($item['gross_pay'], 2),
                number_format($item['total_deductions'], 2),
                number_format($item['net_pay'], 2),
                number_format($item['employer_statutory'], 2),
                ucfirst($item['status']),
            ];
        }

        $data[] = [
            'Total',
            $this->totalEmployees,
            number_format($this->totalGrossPay, 2),
            number_format($this->totalDeductions, 2),
            number_format($this->totalNetPay, 2),
            number_format($this->totalEmployerStatutory, 2),
            '',
        ];

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->summaryData) + 5;
        
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6610f2']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER']],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6610f2']],
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
        return 'Payroll Summary';
    }
}
