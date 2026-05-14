<?php

namespace App\Exports;

use App\Models\Assets\Asset;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class AssetRevaluationImportTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    protected int $companyId;

    protected ?int $branchId;

    public function __construct(int $companyId, ?int $branchId = null)
    {
        $this->companyId = $companyId;
        $this->branchId = $branchId;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $assets = Asset::where('company_id', $this->companyId)
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->where('status', 'active')
            ->orderBy('code')
            ->get();

        return $assets->map(function ($asset) {
            $carryingAmount = $asset->getCurrentCarryingAmount();
            $carryingAmount = $carryingAmount !== null ? round((float) $carryingAmount, 2) : null;

            return [
                'asset_code' => $asset->code,
                'fair_value' => $carryingAmount ?? $asset->purchase_cost ?? 0,
                'carrying_amount' => $carryingAmount ?? '',
                'useful_life_after' => '',
                'residual_value_after' => '',
            ];
        });
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'asset_code',
            'fair_value',
            'carrying_amount',
            'useful_life_after',
            'residual_value_after',
        ];
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(20);
                $sheet->getColumnDimension('B')->setWidth(15);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(20);
                $sheet->getColumnDimension('E')->setWidth(22);

                // Style the header row
                $headerStyle = [
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0D6EFD'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                ];

                $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

                // Add Instructions sheet
                $spreadsheet = $sheet->getParent();
                $instructionsSheet = $spreadsheet->createSheet();
                $instructionsSheet->setTitle('Instructions');

                $instructionsSheet->setCellValue('A1', 'Asset Revaluation Import Instructions');
                $instructionsSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                $instructions = [
                    'A3' => 'Template is pre-populated with your active assets and their current carrying amounts.',
                    'A4' => 'Required Fields:',
                    'A5' => '• asset_code - Pre-filled with asset codes (do not change)',
                    'A6' => '• fair_value - Update with the new fair market value for each asset. Defaults to current carrying amount.',
                    'A7' => '• carrying_amount - Pre-filled with current carrying amount (read-only reference)',
                    'A9' => 'Optional Fields:',
                    'A10' => '• useful_life_after - Remaining useful life in months (integer)',
                    'A11' => '• residual_value_after - Residual value after revaluation (numeric)',
                    'A13' => 'Important Notes:',
                    'A14' => '• Update the fair_value column with the new revalued amount for each asset',
                    'A15' => '• Remove rows for assets you do not want to revalue',
                    'A16' => '• Numeric fields should NOT contain commas, currency symbols, or spaces',
                    'A17' => '• Fill in Revaluation Date, Valuation Model, and Reason in the web form before uploading',
                    'A18' => '• All imported revaluations will be created as drafts for review before submission',
                ];

                foreach ($instructions as $cell => $text) {
                    $instructionsSheet->setCellValue($cell, $text);
                }

                $instructionsSheet->getColumnDimension('A')->setWidth(80);
                $instructionsSheet->getStyle('A3:A8')->getFont()->setBold(true);
                $instructionsSheet->getStyle('A9:A12')->getFont()->setBold(true);
                $instructionsSheet->getStyle('A13:A18')->getFont()->setBold(true);

                $spreadsheet->setActiveSheetIndex(0);
            },
        ];
    }
}
