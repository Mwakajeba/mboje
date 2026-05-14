<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class CashflowForecastExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $forecast;
    protected $summary;
    protected $company;

    public function __construct($forecast, $summary, $company = null)
    {
        $this->forecast = $forecast;
        $this->summary = $summary;
        $this->company = $company;
    }

    public function collection()
    {
        $data = collect();
        
        foreach ($this->summary as $date => $row) {
            $data->push([
                'date' => $date,
                'inflows' => $row['inflows'],
                'outflows' => $row['outflows'],
                'net' => $row['net'],
                'balance' => $row['balance'],
            ]);
        }
        
        return $data;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Inflows (TZS)',
            'Outflows (TZS)',
            'Net Cashflow (TZS)',
            'Closing Balance (TZS)',
        ];
    }

    public function map($row): array
    {
        return [
            Carbon::parse($row['date'])->format('d M Y'),
            number_format($row['inflows'], 2),
            number_format($row['outflows'], 2),
            number_format($row['net'], 2),
            number_format($row['balance'], 2),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']]],
        ];
    }

    public function title(): string
    {
        return 'Cashflow Forecast';
    }
}

