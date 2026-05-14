<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CountingTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithEvents
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        // Skip first row if it's headers
        return array_slice($this->data, 1);
    }

    public function headings(): array
    {
        // Return first row as headings
        return $this->data[0] ?? [];
    }

    public function styles(Worksheet $sheet)
    {
        // Style header row
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0E0E0']
            ],
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

        return $sheet;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $workbook = $sheet->getParent();

                // Create a hidden sheet for dropdown data
                $dropdownSheet = $workbook->createSheet();
                $dropdownSheet->setTitle('DropdownData');

                // Add condition options
                $conditions = ['good', 'damaged', 'expired', 'obsolete', 'missing'];
                $dropdownSheet->setCellValue('A1', 'Condition Options');
                foreach ($conditions as $index => $condition) {
                    $dropdownSheet->setCellValue('A' . ($index + 2), $condition);
                }

                // Hide the dropdown data sheet
                $dropdownSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

                // Get the highest row in the main sheet
                $highestRow = $sheet->getHighestRow();

                // Apply data validation to Condition column (Column D)
                // Columns: A=Item Code, B=Item Name, C=Physical Quantity, D=Condition
                $validation = $sheet->getDataValidation('D2:D' . $highestRow);
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true); // Allow blank (optional field)
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setErrorTitle('Invalid Condition');
                $validation->setError('Please select a valid condition from the dropdown: good, damaged, expired, obsolete, or missing.');
                $validation->setPromptTitle('Select Condition');
                $validation->setPrompt('Please select a condition from the dropdown list.');
                $validation->setFormula1('DropdownData!$A$2:$A$' . (count($conditions) + 1));

                // Format date column (Expiry Date - Column G)
                $sheet->getStyle('G2:G' . $highestRow)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
            }
        ];
    }
}

