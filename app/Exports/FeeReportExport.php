<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class FeeReportExport implements FromView, WithTitle, WithEvents
{
    protected $feeData;
    protected $academicYearId;
    protected $classId;
    protected $streamId;
    protected $quarter;
    protected $status;

    public function __construct($feeData, $academicYearId, $classId, $streamId, $quarter, $status)
    {
        $this->feeData = $feeData;
        $this->academicYearId = $academicYearId;
        $this->classId = $classId;
        $this->streamId = $streamId;
        $this->quarter = $quarter;
        $this->status = $status;
    }

    public function view(): View
    {
        return view('school.reports.exports.fee-report-excel', [
            'feeData' => $this->feeData,
            'academicYearId' => $this->academicYearId,
            'classId' => $this->classId,
            'streamId' => $this->streamId,
            'quarter' => $this->quarter,
            'status' => $this->status
        ]);
    }

    public function title(): string
    {
        return 'Fee Payment Status Report';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(15);
                $sheet->getColumnDimension('B')->setWidth(20);
                $sheet->getColumnDimension('C')->setWidth(12);
                $sheet->getColumnDimension('D')->setWidth(12);
                $sheet->getColumnDimension('E')->setWidth(12);
                $sheet->getColumnDimension('F')->setWidth(15);
                $sheet->getColumnDimension('G')->setWidth(12);
                $sheet->getColumnDimension('H')->setWidth(12);
                $sheet->getColumnDimension('I')->setWidth(12);
                $sheet->getColumnDimension('J')->setWidth(12);
                $sheet->getColumnDimension('K')->setWidth(12);

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