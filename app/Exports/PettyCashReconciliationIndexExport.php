<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Services\PettyCashModeService;

class PettyCashReconciliationIndexExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths
{
    protected $units;
    protected $asOfDate;

    public function __construct($units, $asOfDate)
    {
        $this->units = $units;
        $this->asOfDate = $asOfDate;
    }

    public function collection()
    {
        return $this->units;
    }

    public function map($unit): array
    {
        $reconciliation = PettyCashModeService::getReconciliationSummary($unit, $this->asOfDate);
        
        $outstanding = \App\Models\PettyCash\PettyCashRegister::where('petty_cash_unit_id', $unit->id)
            ->where('entry_type', 'disbursement')
            ->where('status', '!=', 'posted')
            ->where('register_date', '<=', $this->asOfDate)
            ->count();

        return [
            $unit->code,
            $unit->name,
            $unit->branch->name ?? 'N/A',
            $unit->custodian->name ?? 'N/A',
            number_format($reconciliation['opening_balance'], 2),
            number_format($reconciliation['total_disbursed'], 2),
            number_format($reconciliation['total_replenished'], 2),
            number_format($reconciliation['closing_cash'], 2),
            number_format($reconciliation['system_balance'], 2),
            number_format($reconciliation['variance'], 2),
            $outstanding,
            $unit->is_active ? 'Active' : 'Inactive',
        ];
    }

    public function headings(): array
    {
        return [
            'Unit Code',
            'Unit Name',
            'Branch',
            'Custodian',
            'Opening Balance',
            'Total Disbursed',
            'Total Replenished',
            'Closing Cash',
            'System Balance',
            'Variance',
            'Outstanding Vouchers',
            'Status',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 25,
            'C' => 20,
            'D' => 20,
            'E' => 18,
            'F' => 18,
            'G' => 18,
            'H' => 18,
            'I' => 18,
            'J' => 18,
            'K' => 20,
            'L' => 12,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header row
        $sheet->getStyle('A1:L1')->getFont()->setBold(true);
        $sheet->getStyle('A1:L1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');
        $sheet->getStyle('A1:L1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Auto-size columns
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Number formatting for currency columns
        $sheet->getStyle('E2:L' . ($sheet->getHighestRow()))->getNumberFormat()
            ->setFormatCode('#,##0.00');
        
        return [];
    }

    public function title(): string
    {
        return 'Reconciliation Report';
    }
}


