<?php

namespace App\Exports;

use App\Models\AccountClassGroup;
use App\Models\MainGroup;
use App\Models\CashFlowCategory;
use App\Models\EquityCategory;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;

class ChartAccountTemplateExport implements WithHeadings, WithTitle, WithEvents
{
    public function title(): string
    {
        return 'Chart of Accounts Template';
    }

    public function headings(): array
    {
        return [
            'Account Code',
            'Account Name',
            'Main Group',
            'Account Class Group',
            'Type (parent/child)',
            'Parent Account Code (Required if Type is child)',
            'Has Cash Flow (Yes/No)',
            'Cash Flow Category (Optional)',
            'Has Equity (Yes/No)',
            'Equity Category (Optional)'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $workbook = $event->sheet->getDelegate()->getParent();
                $sheet = $event->sheet->getDelegate();
                $companyId = auth()->user()->company_id;

                // Add Sample Data
                $sheet->setCellValue('A2', '1000');
                $sheet->setCellValue('B2', 'Fixed Assets');
                $sheet->setCellValue('E2', 'parent');
                $sheet->setCellValue('G2', 'No');
                $sheet->setCellValue('I2', 'No');

                $sheet->setCellValue('A3', '1100');
                $sheet->setCellValue('B3', 'Motor Vehicles');
                $sheet->setCellValue('E3', 'child');
                $sheet->setCellValue('F3', '1000');
                $sheet->setCellValue('G3', 'No');
                $sheet->setCellValue('I3', 'No');

                // Create Excel Table
                $tableRange = 'A1:J3';
                $table = new Table($tableRange, 'ChartAccountImportTable');
                $table->setShowHeaderRow(true);
                $table->setShowTotalsRow(false);
                $table->setAllowFilter(true);
                
                $tableStyle = new TableStyle();
                $tableStyle->setTheme(TableStyle::TABLE_STYLE_MEDIUM2);
                $table->setStyle($tableStyle);
                
                $sheet->addTable($table);
                
                // Create a hidden sheet for data validation lists
                $validationSheetName = 'Lists';
                $validationSheet = new Worksheet($workbook, $validationSheetName);
                $workbook->addSheet($validationSheet);
                $validationSheet->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);

                // Populate validation lists
                $mainGroups = MainGroup::where('company_id', $companyId)->pluck('name')->toArray();
                $classGroups = AccountClassGroup::where('company_id', $companyId)->pluck('name')->toArray();
                $types = ['parent', 'child'];
                $yesNo = ['Yes', 'No'];
                $cashFlowCategories = CashFlowCategory::pluck('name')->toArray();
                $equityCategories = EquityCategory::pluck('name')->toArray();

                $this->writeListToSheet($validationSheet, 'A', $mainGroups);
                $this->writeListToSheet($validationSheet, 'B', $classGroups);
                $this->writeListToSheet($validationSheet, 'C', $types);
                $this->writeListToSheet($validationSheet, 'D', $yesNo);
                $this->writeListToSheet($validationSheet, 'E', $cashFlowCategories);
                $this->writeListToSheet($validationSheet, 'F', $equityCategories);

                // Define named ranges for the lists
                $workbook->addNamedRange(new \PhpOffice\PhpSpreadsheet\NamedRange('MainGroups', $validationSheet, '$A$1:$A$' . max(1, count($mainGroups))));
                $workbook->addNamedRange(new \PhpOffice\PhpSpreadsheet\NamedRange('ClassGroups', $validationSheet, '$B$1:$B$' . max(1, count($classGroups))));
                $workbook->addNamedRange(new \PhpOffice\PhpSpreadsheet\NamedRange('Types', $validationSheet, '$C$1:$C$' . count($types)));
                $workbook->addNamedRange(new \PhpOffice\PhpSpreadsheet\NamedRange('YesNo', $validationSheet, '$D$1:$D$' . count($yesNo)));
                $workbook->addNamedRange(new \PhpOffice\PhpSpreadsheet\NamedRange('CashFlowCats', $validationSheet, '$E$1:$E$' . max(1, count($cashFlowCategories))));
                $workbook->addNamedRange(new \PhpOffice\PhpSpreadsheet\NamedRange('EquityCats', $validationSheet, '$F$1:$F$' . max(1, count($equityCategories))));

                // Apply validations to the main sheet
                $this->applyValidation($sheet, 'C', 'MainGroups');
                $this->applyValidation($sheet, 'D', 'ClassGroups');
                $this->applyValidation($sheet, 'E', 'Types');
                $this->applyValidation($sheet, 'G', 'YesNo');
                $this->applyValidation($sheet, 'H', 'CashFlowCats');
                $this->applyValidation($sheet, 'I', 'YesNo');
                $this->applyValidation($sheet, 'J', 'EquityCats');

                // Style the header
                $sheet->getStyle('A1:J1')->getFont()->setBold(true);
                foreach (range('A', 'J') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Add instructions below the table
                $sheet->setCellValue('A5', 'INSTRUCTIONS:');
                $sheet->setCellValue('A6', '1. IMPORTANT: Delete rows 2-3 (sample data) before filling your data');
                $sheet->setCellValue('A7', '2. Start entering your chart of accounts data from row 2');
                $sheet->setCellValue('A8', '3. Use dropdown lists for Main Group, Account Class Group, Type, and Yes/No columns');
                $sheet->setCellValue('A9', '4. Parent Account Code is only required if Type is "child"');
                $sheet->setCellValue('A10', '5. Cash Flow/Equity Categories are optional and only used if the respective "Has" column is "Yes"');
                
                $instructionStyle = $sheet->getStyle('A5:A10');
                $instructionStyle->getFont()->setItalic(true);
                $instructionStyle->getFont()->getColor()->setRGB('666666');
            },
        ];
    }

    private function writeListToSheet(Worksheet $sheet, $column, array $items)
    {
        if (empty($items)) {
            $sheet->setCellValue($column . '1', 'No Data');
            return;
        }
        foreach ($items as $index => $item) {
            $sheet->setCellValue($column . ($index + 1), $item);
        }
    }

    private function applyValidation(Worksheet $sheet, $column, $namedRange)
    {
        for ($i = 2; $i <= 1000; $i++) {
            $validation = $sheet->getCell($column . $i)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(false);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1($namedRange);
        }
    }
}
