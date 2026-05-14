<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class SalesReturnExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $returnData;
    protected $dateFrom;
    protected $dateTo;
    protected $branch;
    protected $reason;
    protected $company;

    public function __construct($returnData, $dateFrom, $dateTo, $branch, $reason, $company = null)
    {
        $this->returnData = $returnData;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->branch = $branch;
        $this->reason = $reason;
        $this->company = $company;
    }

    public function collection()
    {
        return $this->returnData;
    }

    public function headings(): array
    {
        return [
            'Credit Note Number',
            'Original Invoice',
            'Customer Name',
            'Return Date',
            'Return Reason',
            'Item Name',
            'Item Code',
            'Quantity',
            'Unit Price',
            'Return Value',
            'Status'
        ];
    }

    public function map($return): array
    {
        $reasonText = '';
        switch($return->reason) {
            case 'damaged':
                $reasonText = 'Damaged';
                break;
            case 'defective':
                $reasonText = 'Defective';
                break;
            case 'wrong_item':
                $reasonText = 'Wrong Item';
                break;
            case 'customer_request':
                $reasonText = 'Customer Request';
                break;
            default:
                $reasonText = ucfirst(str_replace('_', ' ', $return->reason));
        }

        return [
            $return->credit_note_number,
            $return->original_invoice_number,
            $return->customer_name,
            Carbon::parse($return->return_date)->format('m/d/Y'),
            $reasonText,
            $return->item_name,
            $return->item_code,
            number_format($return->quantity, 0),
            number_format($return->unit_price, 0),
            number_format($return->return_value, 0),
            ucfirst($return->status)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Sales Return Report';
    }
}
