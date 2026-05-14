<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PerformanceByClassExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $performanceData;
    protected $academicYear;
    protected $examType;
    protected $selectedClass;

    public function __construct($performanceData, $academicYear, $examType, $selectedClass)
    {
        $this->performanceData = $performanceData;
        $this->academicYear = $academicYear;
        $this->examType = $examType;
        $this->selectedClass = $selectedClass;
    }

    public function title(): string
    {
        return 'Performance by Class Report';
    }

    public function headings(): array
    {
        return [
            ['Performance by Class Report'],
            ['Academic Year: ' . ($this->academicYear ? $this->academicYear->year_name : 'All')],
            ['Exam Type: ' . ($this->examType ? $this->examType->name : 'All')],
            ['Class: ' . ($this->selectedClass ? $this->selectedClass->name : 'All')],
            ['Generated on: ' . now()->format('Y-m-d H:i:s')],
            [], // Empty row
            ['Class', 'Stream', 'Passed', 'Failed', 'Not Attempted', 'Total Students']
        ];
    }

    public function array(): array
    {
        $data = [];

        // Add performance data
        foreach ($this->performanceData['performance'] as $className => $streams) {
            foreach ($streams as $streamData) {
                $data[] = [
                    $className,
                    $streamData['stream']['name'],
                    $streamData['passed'],
                    $streamData['failed'],
                    $streamData['not_attempted'],
                    $streamData['total_students']
                ];
            }
        }

        // Add summary row
        if (!empty($this->performanceData['performance'])) {
            $data[] = []; // Empty row
            $data[] = [
                'GRAND TOTAL',
                '',
                $this->performanceData['grandTotal']['passed'],
                $this->performanceData['grandTotal']['failed'],
                $this->performanceData['grandTotal']['not_attempted'],
                $this->performanceData['grandTotal']['total_students']
            ];
            $data[] = [
                'PASS RATE',
                '',
                $this->performanceData['grandTotal']['pass_rate'] . '%',
                '',
                '',
                ''
            ];
        }

        // Add absent students section if any
        if (!empty($this->performanceData['absentStudents'])) {
            $data[] = []; // Empty row
            $data[] = ['ABSENT STUDENTS'];
            $data[] = ['Student Name', 'Class', 'Stream', 'Absent Subjects'];

            foreach ($this->performanceData['absentStudents'] as $absentStudent) {
                $absentSubjects = implode(', ', array_column($absentStudent['absent_subjects'], 'name'));
                $data[] = [
                    $absentStudent['student']->first_name . ' ' . $absentStudent['student']->last_name,
                    $absentStudent['student']->class->name ?? 'N/A',
                    $absentStudent['student']->stream->name ?? 'N/A',
                    $absentSubjects
                ];
            }
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        // Title styling
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Merge title cell
        $sheet->mergeCells('A1:F1');

        // Header styling
        $sheet->getStyle('A7:F7')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4CAF50'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Find the grand total row and style it
        $lastRow = $sheet->getHighestRow();
        for ($row = 8; $row <= $lastRow; $row++) {
            $cellValue = $sheet->getCell('A' . $row)->getValue();
            if ($cellValue === 'GRAND TOTAL') {
                $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '2196F3'],
                    ],
                ]);
                break;
            }
        }

        // Find the absent students header and style it
        for ($row = 8; $row <= $lastRow; $row++) {
            $cellValue = $sheet->getCell('A' . $row)->getValue();
            if ($cellValue === 'ABSENT STUDENTS') {
                $sheet->getStyle('A' . $row)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                    ],
                ]);
                // Style the absent students header row
                $sheet->getStyle('A' . ($row + 1) . ':D' . ($row + 1))->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FF9800'],
                    ],
                ]);
                break;
            }
        }

        // Add borders to all data
        $sheet->getStyle('A7:F' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        return [
            // Set column widths
            'A' => ['width' => 20],
            'B' => ['width' => 15],
            'C' => ['width' => 10],
            'D' => ['width' => 10],
            'E' => ['width' => 15],
            'F' => ['width' => 15],
        ];
    }
}