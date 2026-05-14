<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MarksSampleExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    protected $examTypeId;
    protected $classId;
    protected $streamId;
    protected $academicYearId;
    protected $companyId;
    protected $branchId;

    public function __construct($examTypeId, $classId = null, $streamId = null, $academicYearId = null, $companyId = null, $branchId = null)
    {
        $this->examTypeId = $examTypeId;
        $this->classId = $classId;
        $this->streamId = $streamId;
        $this->academicYearId = $academicYearId;
        $this->companyId = $companyId ?: session('company_id');
        $this->branchId = $branchId ?: (session('branch_id') ?: (Auth::check() ? Auth::user()->branch_id : null));
    }

    public function array(): array
    {
        $data = [];

        try {
            // Removed logging for compatibility

        // Get exam class assignments for the selected criteria
        $query = DB::table('exam_class_assignments')
            ->join('school_exam_types', 'exam_class_assignments.exam_type_id', '=', 'school_exam_types.id')
            ->join('classes', 'exam_class_assignments.class_id', '=', 'classes.id')
            ->leftJoin('streams', 'exam_class_assignments.stream_id', '=', 'streams.id')
            ->where('exam_class_assignments.exam_type_id', $this->examTypeId)
            ->where('exam_class_assignments.company_id', $this->companyId)
            ->where(function ($query) {
                $query->where('exam_class_assignments.branch_id', $this->branchId)
                      ->orWhereNull('exam_class_assignments.branch_id');
            });

        if ($this->classId && $this->classId !== 'all') {
            $query->where('exam_class_assignments.class_id', $this->classId);
        }

        if ($this->streamId) {
            $query->where('exam_class_assignments.stream_id', $this->streamId);
        }

        $assignments = $query->select(
            'exam_class_assignments.id as assignment_id',
            'exam_class_assignments.class_id',
            'exam_class_assignments.stream_id',
            'school_exam_types.name as exam_name',
            'classes.name as class_name',
            'streams.name as stream_name'
        )->get();

        // Check if class is selected
        if (!$this->classId || $this->classId === 'all') {
            $data[] = ['No class selected. Please select a class to download the marks sample.', '', '', ''];
            return $data;
        }

        // Get all students from the selected class
        $studentsQuery = DB::table('students')
            ->join('classes', 'students.class_id', '=', 'classes.id')
            ->leftJoin('streams', 'students.stream_id', '=', 'streams.id')
            ->where('students.status', 'active')
            ->where('students.class_id', $this->classId)
            ->where('students.company_id', $this->companyId)
            ->where(function ($query) {
                $query->where('students.branch_id', $this->branchId)
                      ->orWhereNull('students.branch_id');
            });

        if ($this->streamId) {
            $studentsQuery->where('students.stream_id', $this->streamId);
        }

        $allStudents = $studentsQuery->select(
                'students.id',
                'students.admission_number',
                'students.first_name',
                'students.last_name',
                'classes.name as class_name',
                'streams.name as stream_name',
                'students.class_id',
                'students.stream_id'
            )
            ->orderByRaw("CONCAT(students.first_name, ' ', students.last_name) ASC")
            ->get();

        if ($allStudents->isEmpty()) {
            $data[] = ['No students found in the selected class.', '', '', ''];
            return $data;
        }

        // Get class and stream info
        $classInfo = $allStudents->first();
        $className = $classInfo->class_name;
        $streamName = $classInfo->stream_name;

        // Get exam name if assignments exist
        $examName = 'Sample';
        if ($assignments->isNotEmpty()) {
            $examName = $assignments->first()->exam_name ?? 'Sample';
        }

        // Get exam registrations to check for absent students
        $assignmentIds = $assignments->pluck('assignment_id')->toArray();
        $studentIds = $allStudents->pluck('id')->toArray();
        
        // Create a map of student_id + subject_id to registration status (absent)
        $absentMap = [];
        if (!empty($assignmentIds) && !empty($studentIds)) {
            try {
                // Get academic year if not provided (use current academic year)
                $academicYearId = $this->academicYearId;
                if (!$academicYearId) {
                    $currentYear = DB::table('academic_years')
                        ->where('company_id', $this->companyId)
                        ->where('is_current', true)
                        ->where(function($query) {
                            if ($this->branchId) {
                                $query->where('branch_id', $this->branchId)
                                      ->orWhereNull('branch_id');
                            } else {
                                $query->whereNull('branch_id');
                            }
                        })
                        ->value('id');
                    $academicYearId = $currentYear;
                }
                
                $query = DB::table('school_exam_registrations')
                    ->join('exam_class_assignments', 'school_exam_registrations.exam_class_assignment_id', '=', 'exam_class_assignments.id')
                    ->whereIn('school_exam_registrations.exam_class_assignment_id', $assignmentIds)
                    ->whereIn('school_exam_registrations.student_id', $studentIds)
                    ->where('school_exam_registrations.status', 'absent')
                    ->where('school_exam_registrations.company_id', $this->companyId);
                
                // Add branch filter
                if ($this->branchId) {
                    $query->where(function($q) {
                        $q->where('school_exam_registrations.branch_id', $this->branchId)
                          ->orWhereNull('school_exam_registrations.branch_id');
                    });
                } else {
                    $query->whereNull('school_exam_registrations.branch_id');
                }
                
                // Add academic year filter if available
                if ($academicYearId) {
                    $query->where('school_exam_registrations.academic_year_id', $academicYearId);
                }
                
                $registrations = $query->select(
                    'school_exam_registrations.student_id',
                    'exam_class_assignments.subject_id'
                )->get();
                
                foreach ($registrations as $reg) {
                    $key = $reg->student_id . '_' . $reg->subject_id;
                    $absentMap[$key] = true;
                }
                
                \Log::info('MarksSampleExport - Absent registrations', [
                    'count' => count($registrations),
                    'absent_map_count' => count($absentMap),
                    'assignment_ids_count' => count($assignmentIds),
                    'student_ids_count' => count($studentIds),
                    'academic_year_id' => $academicYearId,
                    'sample_keys' => array_slice(array_keys($absentMap), 0, 5)
                ]);
            } catch (\Exception $e) {
                \Log::warning('Error fetching absent registrations: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Get subjects from exam class assignments (to match with registrations)
        $examSubjects = DB::table('exam_class_assignments')
            ->join('subjects', 'exam_class_assignments.subject_id', '=', 'subjects.id')
            ->where('exam_class_assignments.exam_type_id', $this->examTypeId)
            ->where('exam_class_assignments.class_id', $this->classId)
            ->where('exam_class_assignments.company_id', $this->companyId)
            ->where(function ($query) {
                $query->where('exam_class_assignments.branch_id', $this->branchId)
                      ->orWhereNull('exam_class_assignments.branch_id');
            });
        
        if ($this->streamId) {
            $examSubjects->where('exam_class_assignments.stream_id', $this->streamId);
        }
        
        $examSubjects = $examSubjects->select(
            'subjects.id',
            'subjects.name',
            'subjects.short_name',
            'exam_class_assignments.id as assignment_id'
        )->get();

        // Get subjects for this class through subject groups (for sorting)
        $allSubjects = DB::table('subjects')
            ->join('subject_subject_group', 'subjects.id', '=', 'subject_subject_group.subject_id')
            ->join('subject_groups', 'subject_subject_group.subject_group_id', '=', 'subject_groups.id')
            ->where('subject_groups.class_id', $this->classId)
            ->where('subject_groups.is_active', true)
            ->where('subject_groups.company_id', $this->companyId)
            ->where(function ($query) {
                if ($this->branchId) {
                    $query->where('subject_groups.branch_id', $this->branchId)
                          ->orWhereNull('subject_groups.branch_id');
                } else {
                    $query->whereNull('subject_groups.branch_id');
                }
            })
            ->select(
                'subjects.id',
                'subjects.name',
                'subjects.short_name',
                'subject_subject_group.sort_order'
            )
            ->get();

        // Get unique subjects, keeping the one with minimum sort_order
        $subjectGroups = $allSubjects->groupBy('id')->map(function ($group) {
            return $group->sortBy('sort_order')->first();
        })->values()->sortBy(function ($subject) {
            return [$subject->sort_order, $subject->name];
        })->values();

        // Use exam subjects if available, otherwise use subject groups
        if ($examSubjects->isNotEmpty()) {
            // Merge with sort order from subject groups
            $sampleSubjects = $examSubjects->map(function($examSubject) use ($subjectGroups) {
                $groupSubject = $subjectGroups->firstWhere('id', $examSubject->id);
                return (object)[
                    'id' => $examSubject->id,
                    'name' => $examSubject->name,
                    'short_name' => $examSubject->short_name,
                    'assignment_id' => $examSubject->assignment_id,
                    'sort_order' => $groupSubject ? $groupSubject->sort_order : 999
                ];
            })->sortBy(function ($subject) {
                return [$subject->sort_order, $subject->name];
            })->values();
        } else {
            // Fallback to subject groups
            $sampleSubjects = $subjectGroups;
        }

        // If no subjects found for this class, get first 6 subjects as fallback
        if ($sampleSubjects->isEmpty()) {
            $sampleSubjects = DB::table('subjects')
                ->where('company_id', $this->companyId)
                ->where(function ($query) {
                    $query->where('branch_id', $this->branchId)
                          ->orWhereNull('branch_id');
                })
                ->select('id', 'name', 'short_name')
                ->orderBy('name')
                ->limit(6)
                ->get();
        }

        // Add section header (make sure it has the same number of columns as data rows)
        $totalColumns = 4 + $sampleSubjects->count(); // 4 fixed columns + subjects
        $headerRow = [
            'Class: ' . $className . ($streamName ? ' - Stream: ' . $streamName : ''),
            'Exam: ' . $examName
        ];

        // Fill remaining columns with empty strings
        for ($i = 2; $i < $totalColumns; $i++) {
            $headerRow[] = '';
        }

        $data[] = $headerRow;

        // Add header row with subjects
        $headerRow = [
            'Admission Number',
            'Student Name',
            'Class',
            'Stream'
        ];

        // Add subject columns
        foreach ($sampleSubjects as $subject) {
            $headerRow[] = $subject->short_name ?: $subject->name;
        }

        $data[] = $headerRow;

        // Add student rows
        foreach ($allStudents as $student) {
            $studentName = trim($student->first_name . ' ' . $student->last_name);

            $studentRow = [
                $student->admission_number,
                $studentName,
                $student->class_name,
                $student->stream_name ?: ''
            ];

            // Add marks cells - check if student is absent for each subject
            foreach ($sampleSubjects as $subject) {
                $key = $student->id . '_' . $subject->id;
                if (isset($absentMap[$key])) {
                    $studentRow[] = 'Absent'; // Show "Absent" for absent students
                } else {
                $studentRow[] = ''; // Empty cell for mark input
                }
            }

            $data[] = $studentRow;
        }

        return $data;
        } catch (\Exception $e) {
            // Return a simple error message
            return [['Error generating export: ' . $e->getMessage(), '', '', '']];
        }
    }

    public function headings(): array
    {
        // This will be overridden by the data structure, but keeping for interface compliance
        return [];
    }

    public function title(): string
    {
        return 'Marks Entry Sample';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,  // Admission Number
            'B' => 25,  // Student Name
            'C' => 15,  // Class
            'D' => 15,  // Stream
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        
        // Find the header row (row with "Admission Number")
        $headerRow = 2;
        for ($row = 1; $row <= $highestRow; $row++) {
            $cellValue = $sheet->getCell('A' . $row)->getValue();
            if ($cellValue === 'Admission Number') {
                $headerRow = $row;
                break;
            }
        }
        
        $dataStartRow = $headerRow + 1;
        $dataEndRow = $highestRow;
        
        // Style header row
        $headerRange = 'A' . $headerRow . ':' . $highestColumn . $headerRow;
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'], // Blue header
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
        
        // Style data rows
        if ($dataEndRow >= $dataStartRow) {
            $dataRange = 'A' . $dataStartRow . ':' . $highestColumn . $dataEndRow;
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D9D9D9'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
            
            // Style mark columns (columns E onwards) - center align
            $markStartCol = 'E';
            $markRange = $markStartCol . $dataStartRow . ':' . $highestColumn . $dataEndRow;
            $sheet->getStyle($markRange)->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);
            
            // Alternate row colors for better readability (apply first)
            for ($row = $dataStartRow; $row <= $dataEndRow; $row++) {
                if (($row - $dataStartRow) % 2 === 0) {
                    $rowRange = 'A' . $row . ':' . $highestColumn . $row;
                    $sheet->getStyle($rowRange)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F8F9FA'], // Light gray
                        ],
                    ]);
                }
            }
            
            // Highlight "Absent" cells (apply after alternate colors to override)
            for ($row = $dataStartRow; $row <= $dataEndRow; $row++) {
                for ($col = 'E'; $col <= $highestColumn; $col++) {
                    $cellValue = $sheet->getCell($col . $row)->getValue();
                    if ($cellValue === 'Absent' || $cellValue === 'Absent') {
                        $sheet->getStyle($col . $row)->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => 'DC3545'], // Red text
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F8D7DA'], // Light red background
                            ],
                        ]);
                    }
                }
            }
        }
        
        // Set row height for header
        $sheet->getRowDimension($headerRow)->setRowHeight(25);
        
        // Auto-size columns for subject columns (E onwards)
        for ($col = 'E'; $col <= $highestColumn; $col++) {
            $sheet->getColumnDimension($col)->setWidth(12);
        }
        
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                try {
                    $sheet = $event->sheet->getDelegate();
                    $highestRow = $sheet->getHighestRow();
                    $highestColumn = $sheet->getHighestColumn();
                    
                    // Find the header row (row with "Admission Number")
                    $headerRow = 2;
                    for ($row = 1; $row <= $highestRow; $row++) {
                        $cellValue = $sheet->getCell('A' . $row)->getValue();
                        if ($cellValue === 'Admission Number') {
                            $headerRow = $row;
                            break;
                        }
                    }
                    
                    $dataStartRow = $headerRow + 1;
                    $dataEndRow = $highestRow;
                    
                    // Only create table if we have data and Table class is available
                    if ($dataEndRow >= $dataStartRow && class_exists(Table::class)) {
                        $tableRange = 'A' . $headerRow . ':' . $highestColumn . $dataEndRow;
                        
                        try {
                            // Create Excel table (this makes it a proper Excel table format)
                            $table = new Table($tableRange, 'MarksEntryTable');
                            $table->setShowHeaderRow(true);
                            $table->setShowTotalsRow(false);
                            
                            // Add table to worksheet
                            // Note: We don't set a specific table style as it may vary by PhpSpreadsheet version
                            // All our custom styling (colors, borders, etc.) is already applied in the styles() method
                            $sheet->addTable($table);
                        } catch (\Exception $tableException) {
                            // If table creation fails, log but don't break the export
                            // The export will still work with all the styling from styles() method
                            \Log::warning('Could not create Excel table: ' . $tableException->getMessage());
                        }
                    }
                } catch (\Exception $e) {
                    // If table creation fails, continue without table (styling is already applied)
                    \Log::warning('Failed to create Excel table in MarksSampleExport: ' . $e->getMessage());
                }
            },
        ];
    }
}