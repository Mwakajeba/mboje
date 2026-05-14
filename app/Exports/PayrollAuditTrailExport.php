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

class PayrollAuditTrailExport implements FromArray, WithStyles, ShouldAutoSize, WithTitle
{
    protected $auditLogs;
    protected $dateFrom;
    protected $dateTo;

    public function __construct($auditLogs, $dateFrom, $dateTo)
    {
        $this->auditLogs = $auditLogs;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function array(): array
    {
        $data = [];
        
        $data[] = ['Payroll Audit Trail Report'];
        $data[] = ['Period: ' . $this->dateFrom . ' to ' . $this->dateTo];
        $data[] = [];

        $data[] = ['Date/Time', 'Payroll Reference', 'Action', 'User', 'Details'];

        foreach ($this->auditLogs as $log) {
            $data[] = [
                $log->created_at->format('Y-m-d H:i:s'),
                $log->payroll ? $log->payroll->reference : 'N/A',
                $log->action,
                $log->user ? $log->user->name : 'System',
                $log->description ?? '',
            ];
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6c757d']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER']],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6c757d']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
        ];
    }

    public function title(): string
    {
        return 'Audit Trail';
    }
}
