<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class FeeWaiversDiscountsExport implements FromView, WithTitle, WithEvents
{
    protected $waiversDiscountsData;
    protected $academicYear;
    protected $class;
    protected $stream;
    protected $dateFrom;
    protected $dateTo;
    protected $discountType;
    protected $period;
    protected $company;

    public function __construct($waiversDiscountsData, $academicYear, $class, $stream, $dateFrom, $dateTo, $discountType, $period, $company)
    {
        $this->waiversDiscountsData = $waiversDiscountsData;
        $this->academicYear = $academicYear;
        $this->class = $class;
        $this->stream = $stream;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->discountType = $discountType;
        $this->period = $period;
        $this->company = $company;
    }

    public function view(): View
    {
        return view('school.reports.exports.fee-waivers-discounts-excel', [
            'waiversDiscountsData' => $this->waiversDiscountsData,
            'academicYear' => $this->academicYear,
            'class' => $this->class,
            'stream' => $this->stream,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'discountType' => $this->discountType,
            'period' => $this->period,
            'company' => $this->company
        ]);
    }

    public function title(): string
    {
        return 'Fee Waivers & Discounts';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;

                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(15);
                $sheet->getColumnDimension('B')->setWidth(15);
                $sheet->getColumnDimension('C')->setWidth(20);
                $sheet->getColumnDimension('D')->setWidth(15);
                $sheet->getColumnDimension('E')->setWidth(10);
                $sheet->getColumnDimension('F')->setWidth(12);
                $sheet->getColumnDimension('G')->setWidth(12);
                $sheet->getColumnDimension('H')->setWidth(12);
                $sheet->getColumnDimension('I')->setWidth(12);
                $sheet->getColumnDimension('J')->setWidth(12);
                $sheet->getColumnDimension('K')->setWidth(12);
                $sheet->getColumnDimension('L')->setWidth(12);

                // Style headers
                $sheet->getStyle('A1:L1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '333333'],
                    ],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => 'FFC107'],
                    ],
                ]);
            },
        ];
    }
}

