<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class OtherIncomeCollectionExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $otherIncomeData;
    protected $dateFrom;
    protected $dateTo;
    protected $accountId;
    protected $classId;

    public function __construct($otherIncomeData, $dateFrom, $dateTo, $accountId, $classId)
    {
        $this->otherIncomeData = $otherIncomeData;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->accountId = $accountId;
        $this->classId = $classId;
    }

    public function collection()
    {
        return collect($this->otherIncomeData);
    }

    public function headings(): array
    {
        return [
            'Date',
            'Student Name',
            'Class',
            'Stream',
            'Category',
            'Description',
            'Amount (TZS)'
        ];
    }

    public function map($income): array
    {
        return [
            $income->transaction_date->format('Y-m-d'),
            $income->student ? $income->student->first_name . ' ' . $income->student->last_name : $income->other_party,
            $income->student && $income->student->class ? $income->student->class->name : '-',
            $income->student && $income->student->stream ? $income->student->stream->name : '-',
            $income->incomeAccount ? $income->incomeAccount->account_name : '-',
            $income->description,
            $income->amount
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style the header row
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '28A745'],
            ],
        ]);

        // Auto-size columns
        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Format amount column as currency
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('G2:G' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');

        return [];
    }

    public function title(): string
    {
        $title = 'Other Income Collection Report';

        $filters = [];
        if ($this->dateFrom && $this->dateTo) {
            $filters[] = $this->dateFrom . ' to ' . $this->dateTo;
        }
        if ($this->classId) {
            $class = \App\Models\Classe::find($this->classId);
            if ($class) {
                $filters[] = 'Class: ' . $class->name;
            }
        }
        if ($this->accountId) {
            $account = \App\Models\ChartAccount::find($this->accountId);
            if ($account) {
                $filters[] = 'Account: ' . $account->account_code . ' - ' . $account->account_name;
            }
        }

        if (!empty($filters)) {
            $title .= ' (' . implode(', ', $filters) . ')';
        }

        return $title;
    }
}