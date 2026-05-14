<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\Purchase\OpeningBalance;
use App\Models\Payment;
use App\Models\Purchase\CashPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class SupplierStatementController extends Controller
{
    public function index()
    {
        $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
        $suppliers = Supplier::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get();
        
        return view('purchases.reports.supplier-statement.index', compact('suppliers'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
        $supplier = Supplier::findOrFail($request->supplier_id);
        
        // Get opening balance
        $openingBalance = OpeningBalance::where('supplier_id', $supplier->id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->first();
        
        $openingAmount = $openingBalance ? (float)$openingBalance->amount : 0;
        $openingPaid = $openingBalance ? (float)$openingBalance->paid_amount : 0;
        $openingBalanceDue = $openingAmount - $openingPaid;

        // Get all transactions for the supplier within date range
        $transactions = collect();

        // 1. Opening Balance (if within date range)
        if ($openingBalance && $openingBalance->opening_date >= $request->date_from && $openingBalance->opening_date <= $request->date_to) {
            $transactions->push([
                'date' => $openingBalance->opening_date,
                'type' => 'Opening Balance',
                'reference' => 'OB-' . $openingBalance->id,
                'description' => 'Opening Balance',
                'debit' => $openingAmount,
                'credit' => 0,
                'balance' => 0, // Will be calculated
                'sort_date' => $openingBalance->opening_date->format('Y-m-d H:i:s'),
            ]);
        }

        // 2. Purchase Invoices
        $invoices = PurchaseInvoice::where('supplier_id', $supplier->id)
            ->whereBetween('invoice_date', [$request->date_from, $request->date_to])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('invoice_date')
            ->get();

        foreach ($invoices as $invoice) {
            $transactions->push([
                'date' => $invoice->invoice_date,
                'type' => 'Purchase Invoice',
                'reference' => $invoice->invoice_number,
                'description' => 'Purchase Invoice',
                'debit' => (float)$invoice->total_amount,
                'credit' => 0,
                'balance' => 0, // Will be calculated
                'sort_date' => $invoice->invoice_date->format('Y-m-d H:i:s'),
            ]);
        }

        // 3. Cash Purchases
        $cashPurchases = CashPurchase::where('supplier_id', $supplier->id)
            ->whereBetween('purchase_date', [$request->date_from, $request->date_to])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('purchase_date')
            ->get();

        foreach ($cashPurchases as $purchase) {
            $transactions->push([
                'date' => $purchase->purchase_date,
                'type' => 'Cash Purchase',
                'reference' => 'CP-' . $purchase->id,
                'description' => 'Cash Purchase',
                'debit' => (float)$purchase->total_amount,
                'credit' => 0,
                'balance' => 0, // Will be calculated
                'sort_date' => $purchase->purchase_date->format('Y-m-d H:i:s'),
            ]);
        }

        // 4. Payments
        $payments = Payment::where('supplier_id', $supplier->id)
            ->whereIn('reference_type', ['purchase_invoice', 'cash_purchase'])
            ->whereBetween('date', [$request->date_from, $request->date_to])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('date')
            ->get();

        foreach ($payments as $payment) {
            $transactions->push([
                'date' => $payment->date,
                'type' => 'Payment',
                'reference' => $payment->reference,
                'description' => 'Payment - ' . $payment->description,
                'debit' => 0,
                'credit' => (float)$payment->amount,
                'balance' => 0, // Will be calculated
                'sort_date' => $payment->date->format('Y-m-d H:i:s'),
            ]);
        }

        // Sort transactions by date
        $transactions = $transactions->sortBy('sort_date');

        // Calculate running balance
        $runningBalance = $openingBalanceDue;
        $transactions = $transactions->map(function ($transaction) use (&$runningBalance) {
            $runningBalance += $transaction['debit'] - $transaction['credit'];
            $transaction['balance'] = $runningBalance;
            return $transaction;
        });

        // Calculate summary
        $totalDebits = $transactions->sum('debit');
        $totalCredits = $transactions->sum('credit');
        $finalBalance = $openingBalanceDue + $totalDebits - $totalCredits;

        return view('purchases.reports.supplier-statement.statement', compact(
            'supplier',
            'transactions',
            'openingBalanceDue',
            'totalDebits',
            'totalCredits',
            'finalBalance'
        ))->with([
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
        $supplier = Supplier::findOrFail($request->supplier_id);
        
        // Get opening balance
        $openingBalance = OpeningBalance::where('supplier_id', $supplier->id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->first();
        
        $openingAmount = $openingBalance ? (float)$openingBalance->amount : 0;
        $openingPaid = $openingBalance ? (float)$openingBalance->paid_amount : 0;
        $openingBalanceDue = $openingAmount - $openingPaid;

        // Get all transactions for the supplier within date range
        $transactions = collect();

        // 1. Opening Balance (if within date range)
        if ($openingBalance && $openingBalance->opening_date >= $request->date_from && $openingBalance->opening_date <= $request->date_to) {
            $transactions->push([
                'date' => $openingBalance->opening_date,
                'type' => 'Opening Balance',
                'reference' => 'OB-' . $openingBalance->id,
                'description' => 'Opening Balance',
                'debit' => $openingAmount,
                'credit' => 0,
                'balance' => 0,
                'sort_date' => $openingBalance->opening_date->format('Y-m-d H:i:s'),
            ]);
        }

        // 2. Purchase Invoices
        $invoices = PurchaseInvoice::where('supplier_id', $supplier->id)
            ->whereBetween('invoice_date', [$request->date_from, $request->date_to])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('invoice_date')
            ->get();

        foreach ($invoices as $invoice) {
            $transactions->push([
                'date' => $invoice->invoice_date,
                'type' => 'Purchase Invoice',
                'reference' => $invoice->invoice_number,
                'description' => 'Purchase Invoice',
                'debit' => (float)$invoice->total_amount,
                'credit' => 0,
                'balance' => 0,
                'sort_date' => $invoice->invoice_date->format('Y-m-d H:i:s'),
            ]);
        }

        // 3. Cash Purchases
        $cashPurchases = CashPurchase::where('supplier_id', $supplier->id)
            ->whereBetween('purchase_date', [$request->date_from, $request->date_to])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('purchase_date')
            ->get();

        foreach ($cashPurchases as $purchase) {
            $transactions->push([
                'date' => $purchase->purchase_date,
                'type' => 'Cash Purchase',
                'reference' => 'CP-' . $purchase->id,
                'description' => 'Cash Purchase',
                'debit' => (float)$purchase->total_amount,
                'credit' => 0,
                'balance' => 0,
                'sort_date' => $purchase->purchase_date->format('Y-m-d H:i:s'),
            ]);
        }

        // 4. Payments
        $payments = Payment::where('supplier_id', $supplier->id)
            ->whereIn('reference_type', ['purchase_invoice', 'cash_purchase'])
            ->whereBetween('date', [$request->date_from, $request->date_to])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('date')
            ->get();

        foreach ($payments as $payment) {
            $transactions->push([
                'date' => $payment->date,
                'type' => 'Payment',
                'reference' => $payment->reference,
                'description' => 'Payment - ' . $payment->description,
                'debit' => 0,
                'credit' => (float)$payment->amount,
                'balance' => 0,
                'sort_date' => $payment->date->format('Y-m-d H:i:s'),
            ]);
        }

        // Sort transactions by date
        $transactions = $transactions->sortBy('sort_date');

        // Calculate running balance
        $runningBalance = $openingBalanceDue;
        $transactions = $transactions->map(function ($transaction) use (&$runningBalance) {
            $runningBalance += $transaction['debit'] - $transaction['credit'];
            $transaction['balance'] = $runningBalance;
            return $transaction;
        });

        // Calculate summary
        $totalDebits = $transactions->sum('debit');
        $totalCredits = $transactions->sum('credit');
        $finalBalance = $openingBalanceDue + $totalDebits - $totalCredits;

        $data = compact(
            'supplier',
            'transactions',
            'openingBalanceDue',
            'totalDebits',
            'totalCredits',
            'finalBalance'
        );
        $data['dateFrom'] = $request->date_from;
        $data['dateTo'] = $request->date_to;
        
        $pdf = Pdf::loadView('purchases.reports.supplier-statement.pdf', $data);

        $filename = 'Supplier_Statement_' . $supplier->name . '_' . $request->date_from . '_to_' . $request->date_to . '.pdf';
        return $pdf->download($filename);
    }

    public function exportExcel(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
        $supplier = Supplier::findOrFail($request->supplier_id);
        
        // Get opening balance
        $openingBalance = OpeningBalance::where('supplier_id', $supplier->id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->first();
        
        $openingAmount = $openingBalance ? (float)$openingBalance->amount : 0;
        $openingPaid = $openingBalance ? (float)$openingBalance->paid_amount : 0;
        $openingBalanceDue = $openingAmount - $openingPaid;

        // Get all transactions for the supplier within date range
        $transactions = collect();

        // 1. Opening Balance (if within date range)
        if ($openingBalance && $openingBalance->opening_date >= $request->date_from && $openingBalance->opening_date <= $request->date_to) {
            $transactions->push([
                'date' => $openingBalance->opening_date,
                'type' => 'Opening Balance',
                'reference' => 'OB-' . $openingBalance->id,
                'description' => 'Opening Balance',
                'debit' => $openingAmount,
                'credit' => 0,
                'balance' => 0,
                'sort_date' => $openingBalance->opening_date->format('Y-m-d H:i:s'),
            ]);
        }

        // 2. Purchase Invoices
        $invoices = PurchaseInvoice::where('supplier_id', $supplier->id)
            ->whereBetween('invoice_date', [$request->date_from, $request->date_to])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('invoice_date')
            ->get();

        foreach ($invoices as $invoice) {
            $transactions->push([
                'date' => $invoice->invoice_date,
                'type' => 'Purchase Invoice',
                'reference' => $invoice->invoice_number,
                'description' => 'Purchase Invoice',
                'debit' => (float)$invoice->total_amount,
                'credit' => 0,
                'balance' => 0,
                'sort_date' => $invoice->invoice_date->format('Y-m-d H:i:s'),
            ]);
        }

        // 3. Cash Purchases
        $cashPurchases = CashPurchase::where('supplier_id', $supplier->id)
            ->whereBetween('purchase_date', [$request->date_from, $request->date_to])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('purchase_date')
            ->get();

        foreach ($cashPurchases as $purchase) {
            $transactions->push([
                'date' => $purchase->purchase_date,
                'type' => 'Cash Purchase',
                'reference' => 'CP-' . $purchase->id,
                'description' => 'Cash Purchase',
                'debit' => (float)$purchase->total_amount,
                'credit' => 0,
                'balance' => 0,
                'sort_date' => $purchase->purchase_date->format('Y-m-d H:i:s'),
            ]);
        }

        // 4. Payments
        $payments = Payment::where('supplier_id', $supplier->id)
            ->whereIn('reference_type', ['purchase_invoice', 'cash_purchase'])
            ->whereBetween('date', [$request->date_from, $request->date_to])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('date')
            ->get();

        foreach ($payments as $payment) {
            $transactions->push([
                'date' => $payment->date,
                'type' => 'Payment',
                'reference' => $payment->reference,
                'description' => 'Payment - ' . $payment->description,
                'debit' => 0,
                'credit' => (float)$payment->amount,
                'balance' => 0,
                'sort_date' => $payment->date->format('Y-m-d H:i:s'),
            ]);
        }

        // Sort transactions by date
        $transactions = $transactions->sortBy('sort_date');

        // Calculate running balance
        $runningBalance = $openingBalanceDue;
        $transactions = $transactions->map(function ($transaction) use (&$runningBalance) {
            $runningBalance += $transaction['debit'] - $transaction['credit'];
            $transaction['balance'] = $runningBalance;
            return $transaction;
        });

        // Calculate summary
        $totalDebits = $transactions->sum('debit');
        $totalCredits = $transactions->sum('credit');
        $finalBalance = $openingBalanceDue + $totalDebits - $totalCredits;

        $filename = 'Supplier_Statement_' . $supplier->name . '_' . $request->date_from . '_to_' . $request->date_to . '.xlsx';
        
        return Excel::download(new \App\Exports\SupplierStatementExport(
            $supplier,
            $transactions,
            $openingBalanceDue,
            $totalDebits,
            $totalCredits,
            $finalBalance,
            $request->date_from,
            $request->date_to
        ), $filename);
    }
}
