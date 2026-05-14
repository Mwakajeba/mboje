<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class GLReconciliationExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $data;
    protected $summary;
    protected $asOfDate;

    public function __construct($data, $summary, $asOfDate)
    {
        $this->data = $data;
        $this->summary = $summary;
        $this->asOfDate = $asOfDate;
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->data as $account) {
            $rows[] = [
                $account['gl_account_code'],
                $account['gl_account_name'],
                $account['account_type'],
                $account['gl_balance'],
                $account['subledger_balance'],
                $account['difference'],
                abs($account['difference']) < 0.01 ? 'Reconciled' : 'Variance'
            ];
        }

        // Add totals row
        $rows[] = [
            'TOTAL',
            '',
            '',
            $this->summary['total_gl'],
            $this->summary['total_subledger'],
            $this->summary['total_variance'],
            abs($this->summary['total_variance']) < 0.01 ? 'Reconciled' : 'Variance'
        ];

        return $rows;
    }

    public function headings(): array
    {
        return [
            'GL Account Code',
            'GL Account Name',
            'Account Type',
            'GL Balance',
            'Subledger Balance',
            'Difference',
            'Status'
        ];
    }

    public function title(): string
    {
        return 'GL Reconciliation ' . date('Y-m-d', strtotime($this->asOfDate));
    }

    public function styles(Worksheet $sheet)
    {
        // Style header row
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '28A745']
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

        // Set font color for header to white
        $sheet->getStyle('A1:G1')->getFont()->getColor()->setRGB('FFFFFF');

        // Find the totals row
        $rowCount = count($this->array()) + 2;
        
        // Style totals row
        $sheet->getStyle('A' . $rowCount . ':G' . $rowCount)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFF2CC']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ]);

        // Format currency columns (D, E, F)
        $sheet->getStyle('D2:F' . $rowCount)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        // Right align currency columns
        $sheet->getStyle('D2:F' . $rowCount)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Center align status column
        $sheet->getStyle('G2:G' . $rowCount)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Add borders to all data cells
        $sheet->getStyle('A2:G' . $rowCount)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ]
        ]);

        // Highlight variance rows in red
        for ($row = 2; $row < $rowCount; $row++) {
            $cellValue = $sheet->getCell('G' . $row)->getValue();
            if ($cellValue === 'Variance') {
                $sheet->getStyle('F' . $row)->getFont()->getColor()->setRGB('DC3545');
                $sheet->getStyle('F' . $row)->getFont()->setBold(true);
            }
        }

        return $sheet;
    }
}
