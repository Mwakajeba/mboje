<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class DetailedFeeCollectionExport implements FromView, WithTitle, WithEvents
{
    protected $feeCollectionData;
    protected $academicYearId;
    protected $classId;

    public function __construct($feeCollectionData, $academicYearId, $classId)
    {
        $this->feeCollectionData = $feeCollectionData;
        $this->academicYearId = $academicYearId;
        $this->classId = $classId;
    }

    public function view(): View
    {
        return view('school.reports.exports.detailed-fee-collection-excel', [
            'feeCollectionData' => $this->feeCollectionData,
            'academicYearId' => $this->academicYearId,
            'classId' => $this->classId
        ]);
    }

    public function title(): string
    {
        return 'Detailed Fee Collection Report';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(20);
                $sheet->getColumnDimension('B')->setWidth(20);
                $sheet->getColumnDimension('C')->setWidth(15);
                $sheet->getColumnDimension('D')->setWidth(15);
                $sheet->getColumnDimension('E')->setWidth(15);
                $sheet->getColumnDimension('F')->setWidth(15);

                // Style headers
                $sheet->getStyle('A1:F1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => '17A2B8'],
                    ],
                ]);

                // Style totals
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle('A' . $lastRow . ':F' . $lastRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => 'F8F9FA'],
                    ],
                ]);
            },
        ];
    }
}