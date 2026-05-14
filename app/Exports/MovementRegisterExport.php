<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class MovementRegisterExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $movements;
    protected $movementTypes;
    protected $company;

    public function __construct($movements, $movementTypes, $company = null)
    {
        $this->movements = $movements;
        $this->movementTypes = $movementTypes;
        $this->company = $company;
    }

    public function collection()
    {
        return $this->movements;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Ref. No',
            'Movement Type',
            'In Qty',
            'Out Qty',
            'Balance Qty',
            'Item Code',
            'Item Name',
            'Location',
            'Entered By'
        ];
    }

    public function map($movement): array
    {
        $movementDate = $movement->movement_date ? 
            Carbon::parse($movement->movement_date)->format('Y-m-d') : 
            $movement->created_at->format('Y-m-d');

        $refNo = $movement->reference ?? ($movement->movement_type == 'opening_balance' ? 'Opening' : '-');
        
        $inQty = isset($movement->in_qty) && $movement->in_qty > 0 ? number_format($movement->in_qty, 2) : '–';
        $outQty = isset($movement->out_qty) && $movement->out_qty > 0 ? number_format($movement->out_qty, 2) : '–';
        $balanceQty = number_format($movement->balance_qty ?? 0, 2);

        $enteredBy = $movement->user->name ?? 'N/A';
        if ($movement->user && $movement->user->roles && $movement->user->roles->first()) {
            $enteredBy .= ' (' . $movement->user->roles->first()->name . ')';
        }

        return [
            $movementDate,
            $refNo,
            $this->movementTypes[$movement->movement_type] ?? ucfirst(str_replace('_', ' ', $movement->movement_type)),
            $inQty,
            $outQty,
            $balanceQty,
            $movement->item->code ?? 'N/A',
            $movement->item->name ?? 'N/A',
            $movement->location->name ?? 'N/A',
            $enteredBy
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
        return 'Movement Register Report';
    }
}
