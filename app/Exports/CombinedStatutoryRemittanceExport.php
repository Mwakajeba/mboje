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

class CombinedStatutoryRemittanceExport implements FromArray, WithStyles, ShouldAutoSize, WithTitle
{
    protected $statutoryData;
    protected $totalAmount;
    protected $year;
    protected $month;

    public function __construct($statutoryData, $totalAmount, $year, $month)
    {
        $this->statutoryData = $statutoryData;
        $this->totalAmount = $totalAmount;
        $this->year = $year;
        $this->month = $month;
    }

    public function array(): array
    {
        $data = [];
        
        $monthName = \Carbon\Carbon::create($this->year, $this->month, 1)->format('F Y');
        $data[] = ['COMBINED STATUTORY REMITTANCE CONTROL REPORT'];
        $data[] = ['Period: ' . $monthName];
        $data[] = ['One-page CFO & Auditor view. Confirms all statutory obligations.'];
        $data[] = [];

        $data[] = ['Statutory Obligation', 'Amount Payable (TZS)', 'Due Date', 'Paid Date', 'Control Number', 'Status'];

        foreach ($this->statutoryData as $item) {
            $data[] = [
                $item['statutory'],
                number_format($item['amount_payable'], 2),
                $item['due_date'],
                $item['paid_date'] ?? 'Not Paid',
                $item['control_number'],
                $item['status'],
            ];
        }

        $data[] = [
            'TOTAL',
            number_format($this->totalAmount, 2),
            '',
            '',
            '',
            '',
        ];

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->statutoryData) + 6;
        
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'dc3545']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER']],
            3 => ['font' => ['italic' => true, 'size' => 9], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER']],
            5 => [
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
        return 'Combined Statutory';
    }
}
