<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AccrualScheduleExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $schedule;
    protected $amortisationSchedule;

    public function __construct($schedule, $amortisationSchedule)
    {
        $this->schedule = $schedule;
        $this->amortisationSchedule = $amortisationSchedule;
    }

    public function collection()
    {
        return collect($this->amortisationSchedule)->map(function($period) {
            $journal = $this->schedule->journals->where('period', $period['period'])->first();
            return (object) [
                'period' => $period['period'],
                'start_date' => $period['period_start_date']->format('Y-m-d'),
                'end_date' => $period['period_end_date']->format('Y-m-d'),
                'days' => $period['days_in_period'],
                'amount' => $period['amortisation_amount'],
                'status' => $journal ? $journal->status : 'pending',
                'journal_number' => $journal && $journal->journal ? $journal->journal->journal_number : 'N/A',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Period',
            'Start Date',
            'End Date',
            'Days',
            'Amount (' . $this->schedule->currency_code . ')',
            'Status',
            'Journal Number'
        ];
    }

    public function map($row): array
    {
        return [
            $row->period,
            $row->start_date,
            $row->end_date,
            $row->days,
            number_format($row->amount, 2),
            ucfirst($row->status),
            $row->journal_number,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '6f42c1']], 'font' => ['color' => ['rgb' => 'FFFFFF']]],
        ];
    }

    public function title(): string
    {
        return 'Amortisation Schedule';
    }
}
