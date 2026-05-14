<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class OtherIncomeReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $otherIncomeData;
    protected $company;
    protected $startDate;
    protected $endDate;
    protected $reportingType;
    protected $branchName;
    protected $generatedBy;

    public function __construct($otherIncomeData, $company, $startDate, $endDate, $reportingType, $branchName, $generatedBy)
    {
        $this->otherIncomeData = $otherIncomeData;
        $this->company = $company;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->reportingType = $reportingType;
        $this->branchName = $branchName;
        $this->generatedBy = $generatedBy;
    }

    public function collection()
    {
        return collect($this->otherIncomeData);
    }

    public function headings(): array
    {
        return [
            'Account Code',
            'Account Name',
            'Account Group',
            'Amount'
        ];
    }

    public function map($account): array
    {
        return [
            $account['account_code'],
            $account['account'],
            $account['group_name'],
            $account['sum']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Other Income Report';
    }
}
