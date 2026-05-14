<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class FeeAgingExport implements FromView, WithTitle, WithEvents
{
    protected $agingData;
    protected $academicYear;
    protected $class;
    protected $stream;
    protected $feeGroup;
    protected $asOfDate;
    protected $company;

    public function __construct($agingData, $academicYear, $class, $stream, $feeGroup, $asOfDate, $company)
    {
        $this->agingData = $agingData;
        $this->academicYear = $academicYear;
        $this->class = $class;
        $this->stream = $stream;
        $this->feeGroup = $feeGroup;
        $this->asOfDate = $asOfDate;
        $this->company = $company;
    }

    public function view(): View
    {
        return view('school.reports.exports.fee-aging-excel', [
            'agingData' => $this->agingData,
            'academicYear' => $this->academicYear,
            'class' => $this->class,
            'stream' => $this->stream,
            'feeGroup' => $this->feeGroup,
            'asOfDate' => $this->asOfDate,
            'company' => $this->company
        ]);
    }

    public function title(): string
    {
        return 'Fee Aging Report';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                // Set column widths (removed Hash ID and Admission # columns - now 11 columns)
                $sheet->getColumnDimension('A')->setWidth(15); // Invoice #
                $sheet->getColumnDimension('B')->setWidth(25); // Student
                $sheet->getColumnDimension('C')->setWidth(15); // Class
                $sheet->getColumnDimension('D')->setWidth(15); // Stream
                $sheet->getColumnDimension('E')->setWidth(15); // Issue Date
                $sheet->getColumnDimension('F')->setWidth(15); // Due Date
                $sheet->getColumnDimension('G')->setWidth(12); // Days Overdue
                $sheet->getColumnDimension('H')->setWidth(15); // Total Amount
                $sheet->getColumnDimension('I')->setWidth(15); // Paid
                $sheet->getColumnDimension('J')->setWidth(15); // Outstanding
                $sheet->getColumnDimension('K')->setWidth(15); // Aging

                // Style headers
                $sheet->getStyle('A1:K1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => '667EEA'],
                    ],
                ]);
            },
        ];
    }
}

