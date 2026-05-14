<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ClassWiseRevenueCollectionExport implements FromView, WithTitle, WithEvents
{
    protected $revenueData;
    protected $academicYear;
    protected $class;
    protected $stream;
    protected $dateFrom;
    protected $dateTo;
    protected $period;
    protected $company;

    public function __construct($revenueData, $academicYear, $class, $stream, $dateFrom, $dateTo, $period, $company)
    {
        $this->revenueData = $revenueData;
        $this->academicYear = $academicYear;
        $this->class = $class;
        $this->stream = $stream;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->period = $period;
        $this->company = $company;
    }

    public function view(): View
    {
        return view('school.reports.exports.class-wise-revenue-collection-excel', [
            'revenueData' => $this->revenueData,
            'academicYear' => $this->academicYear,
            'class' => $this->class,
            'stream' => $this->stream,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'period' => $this->period,
            'company' => $this->company
        ]);
    }

    public function title(): string
    {
        return 'Class-Wise Revenue Collection';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(15);
                $sheet->getColumnDimension('B')->setWidth(15);
                $sheet->getColumnDimension('C')->setWidth(15);
                $sheet->getColumnDimension('D')->setWidth(15);
                $sheet->getColumnDimension('E')->setWidth(15);
                $sheet->getColumnDimension('F')->setWidth(15);
                $sheet->getColumnDimension('G')->setWidth(15);
                $sheet->getColumnDimension('H')->setWidth(15);
                $sheet->getColumnDimension('I')->setWidth(15);
                $sheet->getColumnDimension('J')->setWidth(15);

                // Style headers
                $sheet->getStyle('A1:J1')->applyFromArray([
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

