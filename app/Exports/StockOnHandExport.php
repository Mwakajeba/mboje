<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class StockOnHandExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $itemsWithStock;

    protected $totalQuantity;

    protected $totalValue;

    protected $systemCostMethod;

    protected $company;

    /** @var \Illuminate\Support\Collection Branches to show (columns = totals per branch, aggregated from all locations in that branch) */
    protected $branches;

    public function __construct($itemsWithStock, $totalQuantity, $totalValue, $systemCostMethod, $company = null, $branches = null)
    {
        $this->itemsWithStock = $itemsWithStock;
        $this->totalQuantity = $totalQuantity;
        $this->totalValue = $totalValue;
        $this->systemCostMethod = $systemCostMethod;
        $this->company = $company;
        // Use provided branches (export shows branch name, totals aggregated from all locations in branch)
        if ($branches !== null) {
            $this->branches = collect($branches);
        } else {
            $branchIds = collect();
            foreach ($itemsWithStock as $itemData) {
                foreach ($itemData['locations'] ?? [] as $loc) {
                    $bid = $loc['location']->branch_id ?? null;
                    if ($bid) {
                        $branchIds->push($bid);
                    }
                }
            }
            $this->branches = \App\Models\Branch::whereIn('id', $branchIds->unique()->filter()->values()->all())
                ->orderBy('name')
                ->get();
        }
    }

    public function collection()
    {
        return $this->itemsWithStock;
    }

    public function headings(): array
    {
        $headings = [
            'Item Code',
            'Item Name',
            'Category',
            'UOM',
            'Unit Cost',
            'Total Stock',
            'Total Value',
        ];

        foreach ($this->branches as $branch) {
            $name = $branch->name;
            $headings[] = 'branch_qty(' . $name . ')';
            $headings[] = 'branch(' . $name . ')_amount';
        }

        return $headings;
    }

    public function map($itemData): array
    {
        $row = [
            $itemData['item']->code,
            $itemData['item']->name,
            $itemData['item']->category->name ?? 'N/A',
            $itemData['item']->unit_of_measure,
            (float) $itemData['unit_cost'],
            (float) $itemData['total_stock'],
            (float) $itemData['total_value'],
        ];

        // Aggregate by branch: sum stock and value for all locations in each branch
        $byBranch = collect($itemData['locations'] ?? [])->groupBy(function ($loc) {
            return $loc['location']->branch_id ?? 0;
        })->map(function ($locationItems) {
            return [
                'stock' => $locationItems->sum('stock'),
                'value' => $locationItems->sum('value'),
            ];
        });

        foreach ($this->branches as $branch) {
            $totals = $byBranch->get($branch->id);
            if ($totals && ($totals['stock'] > 0 || $totals['value'] > 0)) {
                $row[] = (float) $totals['stock'];
                $row[] = (float) $totals['value'];
            } else {
                $row[] = null;
                $row[] = null;
            }
        }

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        $numBranches = $this->branches->count();
        $lastDataCol = 7 + $numBranches * 2; // 2 columns per branch: qty + amount
        $lastColLetter = Coordinate::stringFromColumnIndex($lastDataCol);
        $dataRowCount = $this->itemsWithStock->count();
        $highestRow = $dataRowCount + 1; // last data row (row 1 = header)

        // Header row
        $sheet->getStyle('A1:' . $lastColLetter . '1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
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
        $sheet->getStyle('A1:' . $lastColLetter . '1')->getFont()->getColor()->setRGB('FFFFFF');

        if ($dataRowCount >= 1) {
            // Number format: Unit Cost (E), Total Stock (F), Total Value (G) - columns 5,6,7
            $sheet->getStyle('E2:G' . $highestRow)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('E2:G' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Quantity and Amount columns per branch: 2 columns each (qty, amount)
            for ($i = 0; $i < $numBranches; $i++) {
                $qtyCol = 8 + $i * 2;
                $amtCol = 9 + $i * 2;
                $qtyLetter = Coordinate::stringFromColumnIndex($qtyCol);
                $amtLetter = Coordinate::stringFromColumnIndex($amtCol);
                $sheet->getStyle($qtyLetter . '2:' . $qtyLetter . $highestRow)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle($amtLetter . '2:' . $amtLetter . $highestRow)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle($qtyLetter . '2:' . $qtyLetter . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle($amtLetter . '2:' . $amtLetter . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            // Data area borders
            $sheet->getStyle('A2:' . $lastColLetter . $highestRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ]);
        }

        return $sheet;
    }

    public function title(): string
    {
        return 'Stock on Hand Report';
    }
}
