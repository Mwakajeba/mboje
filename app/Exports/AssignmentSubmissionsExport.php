<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AssignmentSubmissionsExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $students;
    protected $existingSubmissions;
    protected $assignment;

    public function __construct($students, $existingSubmissions, $assignment)
    {
        $this->students = $students;
        $this->existingSubmissions = $existingSubmissions;
        $this->assignment = $assignment;
    }

    public function array(): array
    {
        $data = [];

        foreach ($this->students as $student) {
            $existingSubmission = $this->existingSubmissions->get($student->id);
            
            $data[] = [
                $student->admission_number,
                $student->first_name . ' ' . $student->last_name,
                $student->class ? $student->class->name : 'N/A',
                $student->stream ? $student->stream->name : 'N/A',
                $existingSubmission ? $existingSubmission->marks_obtained : '',
                $existingSubmission ? $existingSubmission->teacher_comments : '',
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'Admission Number',
            'Student Name',
            'Class',
            'Stream',
            'Marks Obtained',
            'Comments',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style header row
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ]);

        // Style data rows
        $lastRow = $sheet->getHighestRow();
        if ($lastRow > 1) {
            $sheet->getStyle('A2:F' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN
                    ]
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]);

            // Center align for Admission Number, Class, Stream, Marks
            $sheet->getStyle('A2:A' . $lastRow)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            $sheet->getStyle('C2:D' . $lastRow)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            $sheet->getStyle('E2:E' . $lastRow)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(40);

        return $sheet;
    }
}

