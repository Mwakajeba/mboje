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

class OvertimeExport implements FromArray, WithStyles, ShouldAutoSize, WithTitle
{
    protected $overtimeData;
    protected $totalHours;
    protected $totalAmount;
    protected $year;
    protected $month;

    public function __construct($overtimeData, $totalHours, $totalAmount, $year, $month)
    {
        $this->overtimeData = $overtimeData;
        $this->totalHours = $totalHours;
        $this->totalAmount = $totalAmount;
        $this->year = $year;
        $this->month = $month;
    }

    public function array(): array
    {
        $data = [];
        
        $monthName = \Carbon\Carbon::create($this->year, $this->month, 1)->format('F Y');
        $data[] = ['Overtime Report (Labour Law & Cost Control)'];
        $data[] = ['Period: ' . $monthName];
        $data[] = [];

        $data[] = ['Employee', 'Department', 'Total Hours', 'Total Amount (TZS)', 'Rate Multiplier'];

        foreach ($this->overtimeData as $item) {
            $data[] = [
                $item['employee_name'],
                $item['department'],
                number_format($item['total_hours'], 2),
                number_format($item['total_amount'], 2),
                $item['rate_multiplier'],
            ];
        }

        $data[] = ['Total', '', number_format($this->totalHours, 2), number_format($this->totalAmount, 2), ''];

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->overtimeData) + 5;
        
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'fd7e14']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER']],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'fd7e14']],
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
        return 'Overtime';
    }
}
