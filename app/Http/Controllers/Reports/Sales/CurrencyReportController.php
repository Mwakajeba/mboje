<?php

namespace App\Http\Controllers\Reports\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\CashSale;
use App\Models\Sales\PosSale;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CurrencyReportController extends Controller
{
    protected $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    /**
     * Display currency report index
     */
    public function index()
    {
        $currencies = $this->exchangeRateService->getSupportedCurrencies();
        
        return view('reports.sales_reports.currency.index', compact('currencies'));
    }

    /**
     * Generate currency summary report
     */
    public function summary(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'currency' => 'nullable|string|max:3',
            'report_type' => 'required|in:all,invoices,cash_sales,pos_sales',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $currency = $request->currency;
        $reportType = $request->report_type;

        $data = $this->generateSummaryData($startDate, $endDate, $currency, $reportType);

        return view('reports.sales_reports.currency.summary', compact('data', 'startDate', 'endDate', 'currency', 'reportType'));
    }

    /**
     * Generate currency comparison report
     */
    public function comparison(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'currencies' => 'required|array|min:2',
            'currencies.*' => 'string|max:3',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $currencies = $request->currencies;

        $data = $this->generateComparisonData($startDate, $endDate, $currencies);

        return view('reports.sales_reports.currency.comparison', compact('data', 'startDate', 'endDate', 'currencies'));
    }

    /**
     * Generate exchange rate analysis report
     */
    public function exchangeRateAnalysis(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'from_currency' => 'required|string|max:3',
            'to_currency' => 'required|string|max:3',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $fromCurrency = $request->from_currency;
        $toCurrency = $request->to_currency;

        $data = $this->generateExchangeRateAnalysis($startDate, $endDate, $fromCurrency, $toCurrency);

        return view('reports.sales_reports.currency.exchange_rate_analysis', compact('data', 'startDate', 'endDate', 'fromCurrency', 'toCurrency'));
    }

    /**
     * Export currency report to PDF
     */
    public function exportPdf(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:summary,comparison,exchange_rate_analysis',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $reportType = $request->report_type;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        switch ($reportType) {
            case 'summary':
                $data = $this->generateSummaryData($startDate, $endDate, $request->currency, $request->report_type ?? 'all');
                return view('reports.sales_reports.currency.pdf.summary', compact('data', 'startDate', 'endDate'));
            
            case 'comparison':
                $data = $this->generateComparisonData($startDate, $endDate, $request->currencies);
                return view('reports.sales_reports.currency.pdf.comparison', compact('data', 'startDate', 'endDate'));
            
            case 'exchange_rate_analysis':
                $data = $this->generateExchangeRateAnalysis($startDate, $endDate, $request->from_currency, $request->to_currency);
                return view('reports.sales_reports.currency.pdf.exchange_rate_analysis', compact('data', 'startDate', 'endDate'));
        }
    }

    /**
     * Generate summary data
     */
    private function generateSummaryData($startDate, $endDate, $currency = null, $reportType = 'all')
    {
        $query = DB::table('sales_invoices')
            ->select(
                'currency',
                DB::raw('COUNT(*) as total_transactions'),
                DB::raw('SUM(total_amount) as total_amount'),
                DB::raw('SUM(subtotal) as total_subtotal'),
                DB::raw('SUM(vat_amount) as total_vat'),
                DB::raw('AVG(exchange_rate) as avg_exchange_rate'),
                DB::raw('MIN(exchange_rate) as min_exchange_rate'),
                DB::raw('MAX(exchange_rate) as max_exchange_rate')
            )
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled');

        if ($currency) {
            $query->where('currency', $currency);
        }

        $invoices = $query->groupBy('currency')->get();

        // Add cash sales data
        $cashSalesQuery = DB::table('cash_sales')
            ->select(
                'currency',
                DB::raw('COUNT(*) as total_transactions'),
                DB::raw('SUM(total_amount) as total_amount'),
                DB::raw('SUM(subtotal) as total_subtotal'),
                DB::raw('SUM(vat_amount) as total_vat'),
                DB::raw('AVG(exchange_rate) as avg_exchange_rate'),
                DB::raw('MIN(exchange_rate) as min_exchange_rate'),
                DB::raw('MAX(exchange_rate) as max_exchange_rate')
            )
            ->whereBetween('sale_date', [$startDate, $endDate]);

        if ($currency) {
            $cashSalesQuery->where('currency', $currency);
        }

        $cashSales = $cashSalesQuery->groupBy('currency')->get();

        // Add POS sales data
        $posSalesQuery = DB::table('pos_sales')
            ->select(
                'currency',
                DB::raw('COUNT(*) as total_transactions'),
                DB::raw('SUM(total_amount) as total_amount'),
                DB::raw('SUM(subtotal) as total_subtotal'),
                DB::raw('SUM(vat_amount) as total_vat'),
                DB::raw('AVG(exchange_rate) as avg_exchange_rate'),
                DB::raw('MIN(exchange_rate) as min_exchange_rate'),
                DB::raw('MAX(exchange_rate) as max_exchange_rate')
            )
            ->whereBetween('sale_date', [$startDate, $endDate]);

        if ($currency) {
            $posSalesQuery->where('currency', $currency);
        }

        $posSales = $posSalesQuery->groupBy('currency')->get();

        // Combine and aggregate data
        $combinedData = [];
        
        foreach ($invoices as $invoice) {
            $combinedData[$invoice->currency]['invoices'] = $invoice;
        }
        
        foreach ($cashSales as $cashSale) {
            if (!isset($combinedData[$cashSale->currency])) {
                $combinedData[$cashSale->currency] = [];
            }
            $combinedData[$cashSale->currency]['cash_sales'] = $cashSale;
        }
        
        foreach ($posSales as $posSale) {
            if (!isset($combinedData[$posSale->currency])) {
                $combinedData[$posSale->currency] = [];
            }
            $combinedData[$posSale->currency]['pos_sales'] = $posSale;
        }

        return $combinedData;
    }

    /**
     * Generate comparison data
     */
    private function generateComparisonData($startDate, $endDate, $currencies)
    {
        $data = [];
        
        foreach ($currencies as $currency) {
            $data[$currency] = [
                'invoices' => $this->getCurrencyData('sales_invoices', $startDate, $endDate, $currency),
                'cash_sales' => $this->getCurrencyData('cash_sales', $startDate, $endDate, $currency),
                'pos_sales' => $this->getCurrencyData('pos_sales', $startDate, $endDate, $currency),
            ];
        }

        return $data;
    }

    /**
     * Get currency data for a specific table
     */
    private function getCurrencyData($table, $startDate, $endDate, $currency)
    {
        $dateField = $table === 'sales_invoices' ? 'invoice_date' : 'sale_date';
        
        return DB::table($table)
            ->select(
                DB::raw('DATE(' . $dateField . ') as date'),
                DB::raw('COUNT(*) as transactions'),
                DB::raw('SUM(total_amount) as total_amount'),
                DB::raw('AVG(exchange_rate) as avg_rate')
            )
            ->whereBetween($dateField, [$startDate, $endDate])
            ->where('currency', $currency)
            ->groupBy(DB::raw('DATE(' . $dateField . ')'))
            ->orderBy('date')
            ->get();
    }

    /**
     * Generate exchange rate analysis
     */
    private function generateExchangeRateAnalysis($startDate, $endDate, $fromCurrency, $toCurrency)
    {
        // Get exchange rate history
        $history = $this->exchangeRateService->getExchangeRateHistory($fromCurrency, $toCurrency, 30);
        
        // Get sales data with exchange rates
        $salesData = DB::table('sales_invoices')
            ->select(
                'invoice_date',
                'total_amount',
                'exchange_rate',
                DB::raw('total_amount * exchange_rate as tzs_equivalent')
            )
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->where('currency', $fromCurrency)
            ->orderBy('invoice_date')
            ->get();

        return [
            'history' => $history,
            'sales_data' => $salesData,
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
        ];
    }
}
