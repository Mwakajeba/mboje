<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PayrollVarianceExport implements FromArray, WithStyles, ShouldAutoSize, WithTitle
{
    protected $variances;
    protected $year;
    protected $currentMonth;
    protected $compareMonth;

    public function __construct($variances, $year, $currentMonth, $compareMonth)
    {
        $this->variances = $variances;
        $this->year = $year;
        $this->currentMonth = $currentMonth;
        $this->compareMonth = $compareMonth;
    }

    public function array(): array
    {
        $data = [];
        $currentLabel = \Carbon\Carbon::create($this->year, $this->currentMonth, 1)->format('F Y');
        $compareLabel = \Carbon\Carbon::create($this->year, $this->compareMonth, 1)->format('F Y');
        
        $data[] = ['Payroll Variance Report'];
        $data[] = ['Current Period: ' . $currentLabel . ' vs Compare Period: ' . $compareLabel];
        $data[] = [];

        $data[] = ['Metric', 'Current Period', 'Previous Period', 'Variance', 'Variance %'];

        foreach ($this->variances as $key => $variance) {
            $data[] = [
                ucwords(str_replace('_', ' ', $key)),
                number_format($variance['current'], 2),
                number_format($variance['compare'], 2),
                ($variance['variance'] >= 0 ? '+' : '') . number_format($variance['variance'], 2),
                ($variance['variance_percent'] >= 0 ? '+' : '') . number_format($variance['variance_percent'], 2) . '%',
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
            2 => ['font' => ['bold' => true]],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '17a2b8']],
            ],
        ];
    }

    public function title(): string
    {
        return 'Payroll Variance';
    }
}
