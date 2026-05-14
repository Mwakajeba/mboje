<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class MonthlyAttendanceTrendExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnWidths, WithMapping, WithEvents
{
    protected $trendData;
    protected $academicYear;
    protected $class;
    protected $stream;
    protected $startDate;
    protected $endDate;
    protected $company;

    public function __construct($trendData, $academicYear, $class, $stream, $startDate, $endDate, $company)
    {
        $this->trendData = $trendData;
        $this->academicYear = $academicYear;
        $this->class = $class;
        $this->stream = $stream;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->company = $company;
    }

    public function collection()
    {
        return collect($this->trendData['monthly_data']);
    }

    public function headings(): array
    {
        return [
            'Month',
            'Total Sessions',
            'Unique Students',
            'Present',
            'Absent',
            'Late',
            'Sick',
            'Total Records',
            'Attendance Rate (%)'
        ];
    }

    public function map($month): array
    {
        return [
            $month['month_name'],
            $month['total_sessions'],
            $month['unique_students'],
            $month['total_present'],
            $month['total_absent'],
            $month['total_late'],
            $month['total_sick'],
            $month['total_records'],
            number_format($month['attendance_rate'], 2)
        ];
    }

    public function title(): string
    {
        return 'Monthly Attendance Trend';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,  // Month
            'B' => 15,  // Total Sessions
            'C' => 18,  // Unique Students
            'D' => 12,  // Present
            'E' => 12,  // Absent
            'F' => 12,  // Late
            'G' => 12,  // Sick
            'H' => 15,  // Total Records
            'I' => 20,  // Attendance Rate
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $dataRowCount = count($this->trendData['monthly_data']);

        // Header row style (row 7 after title rows)
        $headerRow = 7;
        $sheet->getStyle('A' . $headerRow . ':I' . $headerRow)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '333333'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Set header row height
        $sheet->getRowDimension($headerRow)->setRowHeight(25);

        // Data rows style (starting from row 8)
        $dataStartRow = $headerRow + 1;
        $dataEndRow = $dataStartRow + $dataRowCount - 1;
        $sheet->getStyle('A' . $dataStartRow . ':I' . $dataEndRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);


        // Add title and filter information at the top
        // Title (row 1)
        $sheet->setCellValue('A1', 'MONTHLY ATTENDANCE TREND ANALYSIS REPORT');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Company name (row 2)
        if ($this->company) {
            $sheet->setCellValue('A2', 'Company: ' . $this->company->name);
            $sheet->mergeCells('A2:I2');
            $sheet->getStyle('A2')->applyFromArray([
                'font' => ['size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        // Filters (rows 3-7)
        $sheet->setCellValue('A3', 'Academic Year: ' . ($this->academicYear ? $this->academicYear->year_name : 'All'));
        $sheet->setCellValue('A4', 'Class: ' . ($this->class ? $this->class->name : 'All'));
        $sheet->setCellValue('A5', 'Stream: ' . ($this->stream ? $this->stream->name : 'All'));
        $sheet->setCellValue('A6', 'Date Range: ' . $this->startDate->format('F d, Y') . ' to ' . $this->endDate->format('F d, Y'));
        $sheet->setCellValue('A7', 'Generated On: ' . date('F d, Y h:i A'));

        // Adjust row heights
        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->getRowDimension(2)->setRowHeight(20);

        return $sheet;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $dataRowCount = count($this->trendData['monthly_data']);
                $grandTotalRow = $dataRowCount + 8; // 7 header rows + data rows + 1 for grand total

                // Add grand total row
                $sheet->setCellValue('A' . $grandTotalRow, 'Grand Total');
                $sheet->setCellValue('B' . $grandTotalRow, $this->trendData['grand_totals']['total_sessions']);
                $sheet->setCellValue('C' . $grandTotalRow, $this->trendData['grand_totals']['total_students']);
                $sheet->setCellValue('D' . $grandTotalRow, $this->trendData['grand_totals']['total_present']);
                $sheet->setCellValue('E' . $grandTotalRow, $this->trendData['grand_totals']['total_absent']);
                $sheet->setCellValue('F' . $grandTotalRow, $this->trendData['grand_totals']['total_late']);
                $sheet->setCellValue('G' . $grandTotalRow, $this->trendData['grand_totals']['total_sick']);
                $sheet->setCellValue('H' . $grandTotalRow, $this->trendData['grand_totals']['total_records']);
                $sheet->setCellValue('I' . $grandTotalRow, number_format($this->trendData['grand_totals']['overall_attendance_rate'], 2));

                // Style grand total row
                $sheet->getStyle('A' . $grandTotalRow . ':I' . $grandTotalRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E9ECEF'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
            },
        ];
    }
}

