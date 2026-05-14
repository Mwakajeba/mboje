<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class GrnVarianceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $reportData;
    protected $dateFrom;
    protected $dateTo;
    protected $branch;
    protected $supplier;
    protected $status;
    protected $varianceStatus;
    protected $company;

    public function __construct($reportData, $dateFrom, $dateTo, $branch, $supplier, $status, $varianceStatus, $company = null)
    {
        $this->reportData = $reportData;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->branch = $branch;
        $this->supplier = $supplier;
        $this->status = $status;
        $this->varianceStatus = $varianceStatus;
        $this->company = $company;
    }

    public function collection()
    {
        return $this->reportData;
    }

    public function headings(): array
    {
        return [
            'GRN Number',
            'GRN Date',
            'PO Number',
            'Supplier Name',
            'Branch',
            'Item Code',
            'Item Name',
            'Received Quantity',
            'Invoiced Quantity',
            'Variance',
            'Variance %',
            'Status'
        ];
    }

    public function map($row): array
    {
        $statusLabel = 'Matched';
        if ($row['variance_status'] === 'not_invoiced') {
            $statusLabel = 'Not Invoiced';
        } elseif ($row['variance_status'] === 'under_invoiced') {
            $statusLabel = 'Under Invoiced';
        } elseif ($row['variance_status'] === 'over_invoiced') {
            $statusLabel = 'Over Invoiced';
        }

        return [
            $row['grn_number'],
            $row['grn_date'] ? Carbon::parse($row['grn_date'])->format('Y-m-d') : 'N/A',
            $row['po_number'],
            $row['supplier_name'],
            $row['branch_name'],
            $row['item_code'],
            $row['item_name'],
            number_format($row['received_quantity'], 2),
            number_format($row['invoiced_quantity'], 2),
            number_format($row['variance'], 2),
            number_format($row['variance_percentage'], 2) . '%',
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
        return 'GRN vs Invoice Variance';
    }
}

