<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class PurchaseOrderRegisterExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $orders;
    protected $dateFrom;
    protected $dateTo;
    protected $branch;
    protected $supplier;
    protected $status;
    protected $company;

    public function __construct($orders, $dateFrom, $dateTo, $branch, $supplier, $status, $company = null)
    {
        $this->orders = $orders;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->branch = $branch;
        $this->supplier = $supplier;
        $this->status = $status;
        $this->company = $company;
    }

    public function collection()
    {
        return $this->orders;
    }

    public function headings(): array
    {
        return [
            'PO Number',
            'Order Date',
            'Supplier Name',
            'Branch',
            'Status',
            'Total Amount (TZS)'
        ];
    }

    public function map($order): array
    {
        return [
            $order->reference ?? ('PO-' . $order->id),
            $order->order_date ? $order->order_date->format('Y-m-d') : 'N/A',
            $order->supplier->name ?? 'Unknown',
            $order->branch->name ?? 'N/A',
            ucfirst($order->status),
            number_format((float)($order->total_amount ?? 0), 2)
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
        return 'Purchase Order Register';
    }
}

