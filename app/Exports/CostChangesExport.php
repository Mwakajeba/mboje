<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class CostChangesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $costChanges;
    protected $company;

    public function __construct($costChanges, $company = null)
    {
        $this->costChanges = $costChanges;
        $this->company = $company;
    }

    public function collection()
    {
        return $this->costChanges;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Item Code',
            'Item Name',
            'Location',
            'Cost Method',
            'Change Type',
            'Quantity',
            'Remaining Quantity',
            'Unit Cost (TZS)',
            'Total Cost (TZS)',
            'Reason',
            'Reference',
            'User',
            'Status',
            'Notes'
        ];
    }

    public function map($change): array
    {
        $remainingQuantity = '';
        if ($change['type'] === 'layer' && isset($change['remaining_quantity'])) {
            $remainingQuantity = number_format($change['remaining_quantity'], 2);
        }

        $status = '';
        if ($change['type'] === 'layer') {
            $status = $change['is_consumed'] ? 'Consumed' : 'Active';
        } else {
            $status = 'Applied';
        }

        return [
            Carbon::parse($change['date'])->format('Y-m-d H:i'),
            $change['item']->code ?? 'N/A',
            $change['item']->name ?? 'N/A',
            $change['location']->name ?? 'N/A',
            $change['cost_method'],
            ucfirst(str_replace('_', ' ', $change['movement_type'])),
            number_format($change['quantity'], 2),
            $remainingQuantity,
            number_format($change['unit_cost'], 2),
            number_format($change['total_cost'], 2),
            $change['reason'],
            $change['reference'] ?? '-',
            $change['user']->name ?? 'System',
            $status,
            $change['notes'] ?? '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Cost Changes Report';
    }
}
