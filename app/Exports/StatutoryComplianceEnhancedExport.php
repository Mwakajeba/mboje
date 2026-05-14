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

class StatutoryComplianceEnhancedExport implements FromArray, WithStyles, ShouldAutoSize, WithTitle
{
    protected $complianceData;
    protected $totalViolations;
    protected $totalWarnings;
    protected $totalCompliant;
    protected $year;
    protected $month;

    public function __construct($complianceData, $totalViolations, $totalWarnings, $totalCompliant, $year, $month)
    {
        $this->complianceData = $complianceData;
        $this->totalViolations = $totalViolations;
        $this->totalWarnings = $totalWarnings;
        $this->totalCompliant = $totalCompliant;
        $this->year = $year;
        $this->month = $month;
    }

    public function array(): array
    {
        $data = [];
        
        $monthName = \Carbon\Carbon::create($this->year, $this->month, 1)->format('F Y');
        $data[] = ['Statutory Compliance Report (Enhanced)'];
        $data[] = ['Period: ' . $monthName];
        $data[] = [];
        $data[] = ['Summary'];
        $data[] = ['Total Compliant', $this->totalCompliant];
        $data[] = ['Total Violations', $this->totalViolations];
        $data[] = ['Total Warnings', $this->totalWarnings];
        $data[] = [];

        $data[] = ['Employee', 'Department', 'Compliance Status', 'Issues'];

        foreach ($this->complianceData as $item) {
            $issues = count($item['compliance']['violations']) + count($item['compliance']['warnings']);
            $status = $item['compliance']['is_compliant'] ? 'Compliant' : 'Non-Compliant';
            
            $data[] = [
                $item['employee']->full_name ?? ($item['employee']->first_name . ' ' . $item['employee']->last_name),
                $item['employee']->department ? $item['employee']->department->name : 'N/A',
                $status,
                $issues,
            ];
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ffc107']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER']],
            4 => ['font' => ['bold' => true, 'size' => 12]],
            9 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ffc107']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
        ];
    }

    public function title(): string
    {
        return 'Compliance Enhanced';
    }
}
