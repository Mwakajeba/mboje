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

class ExaminationResultsExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $examData;
    protected $request;

    public function __construct($examData, $request)
    {
        $this->examData = $examData;
        $this->request = $request;
    }

    public function array(): array
    {
        $data = [];

        // Add results
        foreach ($this->examData['results'] as $index => $result) {
            $row = [
                $index + 1, // Sequential serial number
                $result['student']->first_name . ' ' . $result['student']->last_name,
                $result['student']->stream ? $result['student']->stream->name : '-',
                ucfirst(substr($result['student']->gender, 0, 1)),
            ];

            // Add subject marks
            foreach ($this->examData['subjectMapping'] as $code => $subjectInfo) {
                $subjectId = $subjectInfo['id'];
                $row[] = $result['marks'][$subjectId] ?? '-';
            }

            // Add totals and grades
            $row[] = $result['total'];
            $row[] = $result['average'];
            $row[] = $result['grade'];
            $row[] = $result['position'];
            $row[] = $result['remark'];

            $data[] = $row;
        }

        // Add summary rows
        $data[] = ['']; // Empty row
        $data[] = ['OVERALL PERFORMANCE LEVELS SUMMARY']; // Section header
        $data[] = ['']; // Empty row

        // Gender-based summary
        if (!empty($this->examData['results'])) {
            $girlsGrades = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0];
            $boysGrades = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0];
            $totalGirls = 0;
            $totalBoys = 0;

            foreach ($this->examData['results'] as $result) {
                $grade = $result['grade'] ?? 'N/A';
                $gender = strtolower($result['student']->gender ?? '');

                // Handle different gender representations
                $isFemale = in_array($gender, ['f', 'female', 'woman', 'girl']);
                $isMale = in_array($gender, ['m', 'male', 'man', 'boy']);

                if ($isFemale && isset($girlsGrades[$grade])) {
                    $girlsGrades[$grade]++;
                    $totalGirls++;
                } elseif ($isMale && isset($boysGrades[$grade])) {
                    $boysGrades[$grade]++;
                    $totalBoys++;
                }
            }

            $totalGrades = [
                'A' => $girlsGrades['A'] + $boysGrades['A'],
                'B' => $girlsGrades['B'] + $boysGrades['B'],
                'C' => $girlsGrades['C'] + $boysGrades['C'],
                'D' => $girlsGrades['D'] + $boysGrades['D'],
                'E' => $girlsGrades['E'] + $boysGrades['E']
            ];
            $grandTotal = $totalGirls + $totalBoys;

            // Header row
            $data[] = ['GENDER', 'A', 'B', 'C', 'D', 'E', 'TOTAL'];

            // Girls row
            $data[] = ['GIRLS', $girlsGrades['A'], $girlsGrades['B'], $girlsGrades['C'], $girlsGrades['D'], $girlsGrades['E'], $totalGirls];

            // Boys row
            $data[] = ['BOYS', $boysGrades['A'], $boysGrades['B'], $boysGrades['C'], $boysGrades['D'], $boysGrades['E'], $totalBoys];

            // Total row
            $data[] = ['TOTAL', $totalGrades['A'], $totalGrades['B'], $totalGrades['C'], $totalGrades['D'], $totalGrades['E'], $grandTotal];
        }

        $data[] = ['']; // Empty row
        $data[] = ['']; // Empty row

        // Subject Performance Analysis
        if (isset($this->examData['subjectPerformance']['by_stream']) && $this->examData['subjectPerformance']['by_stream']) {
            $data[] = ['SUBJECTS PERFORMANCE ANALYSIS'];
            $data[] = ['']; // Empty row

            foreach ($this->examData['subjectPerformance']['streams'] as $streamId => $streamData) {
                $data[] = ['STREAM: ' . ($streamData['stream']->name ?? 'Unknown Stream')];
                $data[] = ['']; // Empty row

                // Header for subject analysis
                $data[] = ['Subject Name', 'Teacher\'s Name', 'A', 'B', 'C', 'D', 'E', 'Total', 'GPA', 'Competency Level'];

                foreach ($streamData['subjects'] as $subjectId => $performance) {
                    $teacherInfo = $performance['teacher'];
                    if (isset($performance['teacher_stream']) && $performance['teacher_stream']) {
                        $teacherInfo .= ' (' . $performance['teacher_stream'] . ')';
                    }

                    $row = [
                        $performance['subject']->name,
                        $teacherInfo,
                        $performance['gradeCounts']['A'] ?? 0,
                        $performance['gradeCounts']['B'] ?? 0,
                        $performance['gradeCounts']['C'] ?? 0,
                        $performance['gradeCounts']['D'] ?? 0,
                        $performance['gradeCounts']['E'] ?? 0,
                        $performance['total'],
                        number_format($performance['gpa'], 1),
                        $performance['competencyLevel']
                    ];
                    $data[] = $row;
                }

                $data[] = ['']; // Empty row after each stream
            }
        }

        $data[] = ['']; // Empty row
        $data[] = ['OVERALL PERFORMANCE LEVELS SUMMARY']; // Section header
        $data[] = ['']; // Empty row

        return $data;
    }

    public function headings(): array
    {
        $headings = [
            '#',
            'NAME',
            'STR',
            'SEX',
        ];

        // Add subject headings
        foreach ($this->examData['subjectMapping'] as $code => $subjectInfo) {
            $headings[] = $code;
        }

        // Add remaining headings
        $headings[] = 'TOTAL';
        $headings[] = 'AVR';
        $headings[] = 'GRD';
        $headings[] = 'POS';
        $headings[] = 'REMARK';

        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        // Set column widths for better readability
        $sheet->getColumnDimension('A')->setWidth(5);  // Position
        $sheet->getColumnDimension('B')->setWidth(25); // Name
        $sheet->getColumnDimension('C')->setWidth(10); // Stream
        $sheet->getColumnDimension('D')->setWidth(5);  // Sex

        // Set subject column widths
        $subjectCount = count($this->examData['subjectMapping']);
        $colIndex = 4; // Start from column E (index 4)
        foreach ($this->examData['subjectMapping'] as $code => $subjectInfo) {
            $sheet->getColumnDimension(chr(65 + $colIndex))->setWidth(8);
            $colIndex++;
        }

        // Set remaining column widths
        $sheet->getColumnDimension(chr(65 + $colIndex))->setWidth(10);     // TOTAL
        $sheet->getColumnDimension(chr(65 + $colIndex + 1))->setWidth(8);  // AVR
        $sheet->getColumnDimension(chr(65 + $colIndex + 2))->setWidth(6);  // GRD
        $sheet->getColumnDimension(chr(65 + $colIndex + 3))->setWidth(6);  // POS
        $sheet->getColumnDimension(chr(65 + $colIndex + 4))->setWidth(15); // REMARK

        // Style the main header row
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '17A2B8'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
            ],
        ]);

        // Find section boundaries
        $dataStartRow = 2;
        $overallSummaryRow = 0;
        $subjectsAnalysisRow = 0;

        for ($row = 1; $row <= $lastRow; $row++) {
            $cellValue = $sheet->getCell('A' . $row)->getValue();
            if ($cellValue === 'OVERALL PERFORMANCE LEVELS SUMMARY') {
                $overallSummaryRow = $row;
            } elseif ($cellValue === 'SUBJECTS PERFORMANCE ANALYSIS') {
                $subjectsAnalysisRow = $row;
                break;
            }
        }

        // Style data rows
        if ($overallSummaryRow > 0) {
            $dataEndRow = $overallSummaryRow - 2;

            // Alternate row colors for data
            for ($row = $dataStartRow; $row <= $dataEndRow; $row++) {
                if ($row % 2 == 0) {
                    $sheet->getStyle('A' . $row . ':' . $lastColumn . $row)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F8F9FA'],
                        ],
                    ]);
                }

                // Add borders to data rows
                $sheet->getStyle('A' . $row . ':' . $lastColumn . $row)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'DEE2E6'],
                        ],
                    ],
                ]);
            }
        }

        // Style section headers
        $sectionHeaders = [];
        if ($overallSummaryRow > 0) $sectionHeaders[] = $overallSummaryRow;
        if ($subjectsAnalysisRow > 0) $sectionHeaders[] = $subjectsAnalysisRow;

        foreach ($sectionHeaders as $headerRow) {
            $sheet->getStyle('A' . $headerRow . ':' . $lastColumn . $headerRow)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 16,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '17A2B8'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '17A2B8'],
                    ],
                ],
            ]);

            // Merge cells for section headers
            $sheet->mergeCells('A' . $headerRow . ':' . $lastColumn . $headerRow);
        }

        // Style stream headers
        for ($row = 1; $row <= $lastRow; $row++) {
            $cellValue = $sheet->getCell('A' . $row)->getValue();
            if (strpos($cellValue, 'STREAM: ') === 0) {
                $sheet->getStyle('A' . $row . ':' . $lastColumn . $row)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '28A745'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '28A745'],
                        ],
                    ],
                ]);
                $sheet->mergeCells('A' . $row . ':' . $lastColumn . $row);
            }
        }

        // Style summary tables (gender distribution and subject analysis)
        $summaryTableRows = [];

        // Find gender distribution table
        if ($overallSummaryRow > 0) {
            $genderHeaderRow = $overallSummaryRow + 2; // Skip empty row and header
            $genderDataStart = $genderHeaderRow + 1;
            $genderDataEnd = $genderDataStart + 2; // 3 rows: header, girls, boys

            $summaryTableRows = array_merge($summaryTableRows, range($genderHeaderRow, $genderDataEnd + 1)); // +1 for total row
        }

        // Find subject analysis tables
        if ($subjectsAnalysisRow > 0) {
            for ($row = $subjectsAnalysisRow + 1; $row <= $lastRow; $row++) {
                $cellValue = $sheet->getCell('A' . $row)->getValue();
                if (!empty($cellValue) && !in_array($cellValue, ['', 'Subject Name', 'STREAM:'])) {
                    $summaryTableRows[] = $row;
                }
            }
        }

        // Apply borders to summary table rows
        foreach ($summaryTableRows as $row) {
            $sheet->getStyle('A' . $row . ':' . $lastColumn . $row)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'DEE2E6'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        // Style summary table headers
        $summaryHeaders = [];
        if ($overallSummaryRow > 0) {
            $summaryHeaders[] = $overallSummaryRow + 2; // Gender distribution header
        }
        if ($subjectsAnalysisRow > 0) {
            // Find subject analysis headers
            for ($row = $subjectsAnalysisRow + 1; $row <= $lastRow; $row++) {
                $cellValue = $sheet->getCell('A' . $row)->getValue();
                if ($cellValue === 'Subject Name') {
                    $summaryHeaders[] = $row;
                }
            }
        }

        foreach ($summaryHeaders as $headerRow) {
            $sheet->getStyle('A' . $headerRow . ':' . $lastColumn . $headerRow)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '6C757D'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '6C757D'],
                    ],
                ],
            ]);
        }

        // Center align numeric columns
        $centerColumns = ['A', 'C', 'D']; // Position, Stream, Sex
        $colIndex = 4; // Start from column E
        foreach ($this->examData['subjectMapping'] as $code => $subjectInfo) {
            $centerColumns[] = chr(65 + $colIndex);
            $colIndex++;
        }
        // Add TOTAL, AVR, GRD, POS columns
        $centerColumns[] = chr(65 + $colIndex);
        $centerColumns[] = chr(65 + $colIndex + 1);
        $centerColumns[] = chr(65 + $colIndex + 2);
        $centerColumns[] = chr(65 + $colIndex + 3);

        foreach ($centerColumns as $col) {
            $sheet->getStyle($col . '1:' . $col . $lastRow)->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);
        }

        // Set row heights for better readability
        for ($row = 1; $row <= $lastRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(20);
        }

        // Make section headers taller
        if ($overallSummaryRow > 0) {
            $sheet->getRowDimension($overallSummaryRow)->setRowHeight(30);
        }
        if ($subjectsAnalysisRow > 0) {
            $sheet->getRowDimension($subjectsAnalysisRow)->setRowHeight(30);
        }

        return [];
    }
}