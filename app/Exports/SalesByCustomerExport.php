<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class SalesByCustomerExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $customerSales;
    protected $dateFrom;
    protected $dateTo;
    protected $branch;
    protected $company;

    public function __construct($customerSales, $dateFrom, $dateTo, $branch, $company = null)
    {
        $this->customerSales = $customerSales;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->branch = $branch;
        $this->company = $company;
    }

    public function collection()
    {
        return $this->customerSales;
    }

    public function headings(): array
    {
        return [
            '#',
            'Customer Name',
            'Phone',
            'Total Sales (TZS)',
            'Total Cost (TZS)',
            'Gross Profit (TZS)',
            'Invoice Count',
            'Avg Invoice Value (TZS)',
            'Outstanding Balance (TZS)',
            'Contribution %',
            'First Invoice Date',
            'Last Invoice Date',
            'Status'
        ];
    }

    public function map($customer): array
    {
        static $index = 0;
        $index++;
        
        $totalSales = $this->customerSales->sum('total_sales');
        $salesPercentage = $totalSales > 0 ? ($customer->total_sales / $totalSales) * 100 : 0;
        $statusClass = $salesPercentage >= 10 ? 'High Value' : ($salesPercentage >= 5 ? 'Medium Value' : 'Low Value');

        return [
            $index,
            $customer->customer->name ?? 'Unknown',
            $customer->customer_phone ?? 'N/A',
            number_format($customer->total_sales, 2),
            number_format($customer->total_cost ?? 0, 2),
            number_format($customer->gross_profit ?? 0, 2),
            $customer->invoice_count,
            number_format($customer->avg_invoice_value, 2),
            number_format($customer->outstanding_balance ?? 0, 2),
            number_format($salesPercentage, 2) . '%',
            $customer->first_invoice_date ? Carbon::parse($customer->first_invoice_date)->format('M d, Y') : 'N/A',
            $customer->last_invoice_date ? Carbon::parse($customer->last_invoice_date)->format('M d, Y') : 'N/A',
            $statusClass
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
        return 'Sales by Customer';
    }
}
