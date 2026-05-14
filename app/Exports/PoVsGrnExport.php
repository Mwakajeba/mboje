<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class PoVsGrnExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $reportData;
    protected $dateFrom;
    protected $dateTo;
    protected $branch;
    protected $supplier;
    protected $status;
    protected $fulfillmentStatus;
    protected $company;

    public function __construct($reportData, $dateFrom, $dateTo, $branch, $supplier, $status, $fulfillmentStatus, $company = null)
    {
        $this->reportData = $reportData;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->branch = $branch;
        $this->supplier = $supplier;
        $this->status = $status;
        $this->fulfillmentStatus = $fulfillmentStatus;
        $this->company = $company;
    }

    public function collection()
    {
        return $this->reportData;
    }

    public function headings(): array
    {
        return [
            'PO Number',
            'PO Date',
            'Supplier Name',
            'Branch',
            'Item Code',
            'Item Name',
            'Ordered Quantity',
            'Received Quantity',
            'Variance',
            'Fulfillment %',
            'Status'
        ];
    }

    public function map($row): array
    {
        $statusLabel = 'Not Received';
        if ($row['fulfillment_status'] === 'fully_received') {
            $statusLabel = 'Fully Received';
        } elseif ($row['fulfillment_status'] === 'partially_received') {
            $statusLabel = 'Partially Received';
        }

        return [
            $row['po_number'],
            $row['po_date'] ? Carbon::parse($row['po_date'])->format('Y-m-d') : 'N/A',
            $row['supplier_name'],
            $row['branch_name'],
            $row['item_code'],
            $row['item_name'],
            number_format($row['ordered_quantity'], 2),
            number_format($row['received_quantity'], 2),
            number_format($row['variance'], 2),
            number_format($row['fulfillment_percentage'], 2) . '%',
            $statusLabel
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
        return 'PO vs GRN Report';
    }
}

