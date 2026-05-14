<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PettyCashReconciliationExport implements FromArray, WithHeadings, WithStyles, WithTitle, WithColumnWidths, WithCustomStartCell
{
    protected $unit;
    protected $reconciliation;
    protected $outstandingVouchers;
    protected $asOfDate;
    protected $cashCounted;
    protected $variance;
    protected $notes;

    public function __construct($unit, $reconciliation, $outstandingVouchers, $asOfDate, $cashCounted = null, $variance = null, $notes = null)
    {
        $this->unit = $unit;
        $this->reconciliation = $reconciliation;
        $this->outstandingVouchers = $outstandingVouchers;
        $this->asOfDate = $asOfDate;
        $this->cashCounted = $cashCounted;
        $this->variance = $variance;
        $this->notes = $notes;
    }

    public function startCell(): string
    {
        return 'A1';
    }

    public function array(): array
    {
        $data = [];
        
        // Header information
        $data[] = ['Petty Cash Reconciliation Report'];
        $data[] = [];
        $data[] = ['Unit Name:', $this->unit->name];
        $data[] = ['Unit Code:', $this->unit->code];
        $data[] = ['As of Date:', $this->asOfDate];
        $data[] = ['Custodian:', $this->unit->custodian->name ?? 'N/A'];
        $data[] = [];
        
        // Reconciliation Summary
        $data[] = ['RECONCILIATION SUMMARY'];
        $data[] = ['Opening Balance', number_format($this->reconciliation['opening_balance'] ?? 0, 2)];
        $data[] = ['Total Disbursed', number_format($this->reconciliation['total_disbursed'] ?? 0, 2)];
        $data[] = ['Total Replenished', number_format($this->reconciliation['total_replenished'] ?? 0, 2)];
        $data[] = ['Closing Cash (Calculated)', number_format($this->reconciliation['closing_cash'] ?? 0, 2)];
        $data[] = ['System Balance', number_format($this->reconciliation['system_balance'] ?? 0, 2)];
        $data[] = [];
        
        // Cash Count
        if ($this->cashCounted !== null) {
            $data[] = ['CASH COUNT'];
            $data[] = ['Physical Cash Counted', number_format($this->cashCounted, 2)];
            $data[] = ['Calculated Balance', number_format($this->reconciliation['closing_cash'] ?? 0, 2)];
            $data[] = ['Variance', number_format($this->variance ?? 0, 2)];
            $data[] = [];
        }
        
        // Outstanding Vouchers
        if ($this->outstandingVouchers && $this->outstandingVouchers->count() > 0) {
            $data[] = ['OUTSTANDING VOUCHERS'];
            $data[] = ['PCV Number', 'Date', 'Description', 'Amount', 'Requested By', 'Status'];
            
            $totalOutstanding = 0;
            foreach ($this->outstandingVouchers as $voucher) {
                $totalOutstanding += $voucher->amount;
                $data[] = [
                    $voucher->pcv_number ?? 'N/A',
                    $voucher->register_date->format('Y-m-d'),
                    $voucher->description,
                    number_format($voucher->amount, 2),
                    $voucher->requestedBy->name ?? 'N/A',
                    ucfirst($voucher->status)
                ];
            }
            $data[] = ['Total Outstanding', '', '', number_format($totalOutstanding, 2), '', ''];
            $data[] = [];
        }
        
        // Notes
        if ($this->notes) {
            $data[] = ['NOTES'];
            $data[] = [$this->notes];
        }
        
        return $data;
    }

    public function headings(): array
    {
        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 20,
            'C' => 40,
            'D' => 15,
            'E' => 20,
            'F' => 15,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Merge and style title
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Find and style section headers dynamically
        $highestRow = $sheet->getHighestRow();
        for ($row = 1; $row <= $highestRow; $row++) {
            $cellValue = $sheet->getCell("A{$row}")->getValue();
            
            // Style section headers (all caps titles)
            if (is_string($cellValue) && strtoupper($cellValue) === $cellValue && strlen($cellValue) > 5) {
                $sheet->getStyle("A{$row}:F{$row}")->getFont()->setBold(true);
                $sheet->getStyle("A{$row}:F{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFE0E0E0');
            }
            
            // Style totals (rows containing "Total" or "Closing")
            if (is_string($cellValue) && (stripos($cellValue, 'Total') !== false || stripos($cellValue, 'Closing') !== false)) {
                $sheet->getStyle("A{$row}:F{$row}")->getFont()->setBold(true);
            }
        }
        
        return [];
    }

    public function title(): string
    {
        return 'Reconciliation Report';
    }
}

