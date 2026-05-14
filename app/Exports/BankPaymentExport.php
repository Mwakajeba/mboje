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

class BankPaymentExport implements FromArray, WithStyles, ShouldAutoSize, WithTitle
{
    protected $bankGroups;
    protected $dateFrom;
    protected $dateTo;

    public function __construct($bankGroups, $dateFrom, $dateTo)
    {
        $this->bankGroups = $bankGroups;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function array(): array
    {
        $data = [];
        
        $data[] = ['Bank Payment Report'];
        $data[] = ['Period: ' . $this->dateFrom . ' to ' . $this->dateTo];
        $data[] = [];

        foreach ($this->bankGroups as $group) {
            $bankName = $group['bank_account'] ? $group['bank_account']->name : 'No Bank Account';
            $accountNumber = $group['bank_account'] ? $group['bank_account']->account_number : 'N/A';
            
            $data[] = ['Bank: ' . $bankName . ' (' . $accountNumber . ')'];
            $data[] = ['Payroll Reference', 'Period', 'Employee Count', 'Net Pay (TZS)', 'Status'];
            
            foreach ($group['payrolls'] as $payroll) {
                $data[] = [
                    $payroll->reference,
                    \Carbon\Carbon::create($payroll->year, $payroll->month, 1)->format('F Y'),
                    $payroll->payrollEmployees->count(),
                    number_format($payroll->payrollEmployees->sum('net_salary'), 2),
                    ucfirst($payroll->status),
                ];
            }
            
            $data[] = ['Subtotal', '', $group['payroll_count'], number_format($group['total_amount'], 2), ''];
            $data[] = [];
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '007bff']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER']],
        ];
    }

    public function title(): string
    {
        return 'Bank Payments';
    }
}
