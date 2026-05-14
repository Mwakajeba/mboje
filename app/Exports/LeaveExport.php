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

class LeaveExport implements FromArray, WithStyles, ShouldAutoSize, WithTitle
{
    protected $leaveData;
    protected $totalDaysTaken;
    protected $totalPaidDays;
    protected $totalUnpaidDays;
    protected $year;
    protected $month;

    public function __construct($leaveData, $totalDaysTaken, $totalPaidDays, $totalUnpaidDays, $year, $month)
    {
        $this->leaveData = $leaveData;
        $this->totalDaysTaken = $totalDaysTaken;
        $this->totalPaidDays = $totalPaidDays;
        $this->totalUnpaidDays = $totalUnpaidDays;
        $this->year = $year;
        $this->month = $month;
    }

    public function array(): array
    {
        $data = [];
        
        $monthName = \Carbon\Carbon::create($this->year, $this->month, 1)->format('F Y');
        $data[] = ['Leave Report (Payroll Dependency)'];
        $data[] = ['Period: ' . $monthName];
        $data[] = [];

        $data[] = ['Employee', 'Department', 'Leave Type', 'Days Taken', 'Paid Days', 'Unpaid Days', 'Balance'];

        foreach ($this->leaveData as $item) {
            $data[] = [
                $item['employee_name'],
                $item['department'],
                $item['leave_type'],
                number_format($item['days_taken'], 2),
                number_format($item['paid_days'], 2),
                number_format($item['unpaid_days'], 2),
                isset($item['balance']) ? number_format($item['balance'], 2) : 'N/A',
            ];
        }

        $data[] = [
            'Total',
            '',
            '',
            number_format($this->totalDaysTaken, 2),
            number_format($this->totalPaidDays, 2),
            number_format($this->totalUnpaidDays, 2),
            '',
        ];

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->leaveData) + 5;
        
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '20c997']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER']],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '20c997']],
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
        return 'Leave Report';
    }
}
