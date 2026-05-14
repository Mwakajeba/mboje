<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\FxRate;
use App\Models\Currency;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class FxRateController extends Controller
{
    protected $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    /**
     * Display a listing of FX rates.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get currencies for filter dropdown
        $currencies = Currency::where('company_id', $user->company_id)
            ->active()
            ->orderBy('currency_code')
            ->get();

        // Get supported currencies as fallback
        $supportedCurrencies = $this->exchangeRateService->getSupportedCurrencies();

        return view('accounting.fx-rates.index', compact('currencies', 'supportedCurrencies'));
    }

    /**
     * DataTables AJAX endpoint for FX rates.
     */
    public function data(Request $request)
    {
        $user = Auth::user();
        
        $query = FxRate::with('creator')
            ->where('company_id', $user->company_id)
            ->select('fx_rates.*');

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                // Apply filters from request
                if ($request->filled('source')) {
                    $query->where('source', $request->source);
                }

                if ($request->filled('is_locked')) {
                    $query->where('is_locked', $request->is_locked);
                }

                // Global search
                if ($request->filled('search.value')) {
                    $searchValue = $request->input('search.value');
                    $query->where(function($q) use ($searchValue) {
                        $q->where('from_currency', 'like', "%{$searchValue}%")
                          ->orWhere('to_currency', 'like', "%{$searchValue}%")
                          ->orWhere('source', 'like', "%{$searchValue}%")
                          ->orWhereHas('creator', function($creatorQuery) use ($searchValue) {
                              $creatorQuery->where('name', 'like', "%{$searchValue}%");
                          });
                    });
                }
            })
            ->addColumn('formatted_date', function ($rate) {
                return '<span class="badge bg-light text-dark">' . $rate->rate_date->format('M d, Y') . '</span>';
            })
            ->addColumn('from_currency_badge', function ($rate) {
                return '<span class="badge bg-info">' . $rate->from_currency . '</span>';
            })
            ->addColumn('to_currency_badge', function ($rate) {
                return '<span class="badge bg-success">' . $rate->to_currency . '</span>';
            })
            ->addColumn('formatted_spot_rate', function ($rate) {
                return '<span class="fw-bold">' . number_format($rate->spot_rate, 6) . '</span>';
            })
            ->addColumn('formatted_month_end_rate', function ($rate) {
                if ($rate->month_end_rate) {
                    return '<span class="fw-bold text-primary">' . number_format($rate->month_end_rate, 6) . '</span>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('formatted_average_rate', function ($rate) {
                if ($rate->average_rate) {
                    return '<span class="fw-bold text-secondary">' . number_format($rate->average_rate, 6) . '</span>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('source_badge', function ($rate) {
                $badgeClass = match($rate->source) {
                    'manual' => 'primary',
                    'api' => 'success',
                    'import' => 'warning',
                    default => 'secondary'
                };
                return '<span class="badge bg-'.$badgeClass.'">' . ucfirst($rate->source) . '</span>';
            })
            ->addColumn('status_badge', function ($rate) {
                if ($rate->is_locked) {
                    return '<span class="badge bg-danger"><i class="bx bx-lock me-1"></i> Locked</span>';
                } else {
                    return '<span class="badge bg-success"><i class="bx bx-lock-open me-1"></i> Unlocked</span>';
                }
            })
            ->addColumn('creator_name', function ($rate) {
                return $rate->creator->name ?? 'N/A';
            })
            ->addColumn('actions', function ($rate) {
                $actions = '<div class="btn-group">';
                
                if (!$rate->is_locked) {
                    $actions .= '<a href="'.route('accounting.fx-rates.edit', $rate->id).'" class="btn btn-sm btn-warning" title="Edit"><i class="bx bx-edit"></i></a>';
                }
                
                if ($rate->is_locked) {
                    $actions .= '<form action="'.route('accounting.fx-rates.unlock', $rate->id).'" method="POST" class="d-inline unlock-form" data-rate-id="'.$rate->id.'" data-from-currency="'.$rate->from_currency.'" data-to-currency="'.$rate->to_currency.'">';
                    $actions .= csrf_field();
                    $actions .= '<button type="button" class="btn btn-sm btn-success unlock-btn" title="Unlock"><i class="bx bx-lock-open"></i></button>';
                    $actions .= '</form>';
                } else {
                    $actions .= '<form action="'.route('accounting.fx-rates.lock', $rate->id).'" method="POST" class="d-inline lock-form" data-rate-id="'.$rate->id.'" data-from-currency="'.$rate->from_currency.'" data-to-currency="'.$rate->to_currency.'">';
                    $actions .= csrf_field();
                    $actions .= '<button type="button" class="btn btn-sm btn-danger lock-btn" title="Lock"><i class="bx bx-lock"></i></button>';
                    $actions .= '</form>';
                }
                
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['formatted_date', 'from_currency_badge', 'to_currency_badge', 'formatted_spot_rate', 'formatted_month_end_rate', 'formatted_average_rate', 'source_badge', 'status_badge', 'actions'])
            ->orderColumn('rate_date', 'rate_date $1')
            ->orderColumn('from_currency', 'from_currency $1')
            ->orderColumn('to_currency', 'to_currency $1')
            ->make(true);
    }

    /**
     * Show the form for creating a new FX rate.
     */
    public function create()
    {
        $user = Auth::user();
        
        // Get currencies
        $currencies = Currency::where('company_id', $user->company_id)
            ->active()
            ->orderBy('currency_code')
            ->get();

        // Get supported currencies as fallback
        $supportedCurrencies = $this->exchangeRateService->getSupportedCurrencies();

        // Get functional currency
        $functionalCurrency = $user->company->functional_currency ?? 'TZS';

        return view('accounting.fx-rates.create', compact('currencies', 'supportedCurrencies', 'functionalCurrency'));
    }

    /**
     * Store a newly created FX rate.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'rate_date' => 'required|date',
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3',
            'spot_rate' => 'required|numeric|min:0.000001',
            'month_end_rate' => 'nullable|numeric|min:0.000001',
            'average_rate' => 'nullable|numeric|min:0.000001',
            'source' => 'required|in:manual,api,import',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Check if currencies are different
        if ($validated['from_currency'] === $validated['to_currency']) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['to_currency' => 'From currency and To currency must be different.']);
        }

        try {
            DB::beginTransaction();

            $fxRate = $this->exchangeRateService->storeFxRate(
                $validated['from_currency'],
                $validated['to_currency'],
                $validated['rate_date'],
                $validated['spot_rate'],
                $validated['month_end_rate'] ?? null,
                $validated['average_rate'] ?? null,
                $validated['source'],
                $user->company_id,
                $user->id
            );

            DB::commit();

            return redirect()->route('accounting.fx-rates.index')
                ->with('success', 'FX rate created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating FX rate: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create FX rate. ' . $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified FX rate.
     */
    public function edit($id)
    {
        $user = Auth::user();
        
        $fxRate = FxRate::where('company_id', $user->company_id)
            ->findOrFail($id);

        // Check if rate is locked
        if ($fxRate->is_locked) {
            return redirect()->route('accounting.fx-rates.index')
                ->withErrors(['error' => 'Cannot edit locked FX rate.']);
        }

        // Get currencies
        $currencies = Currency::where('company_id', $user->company_id)
            ->active()
            ->orderBy('currency_code')
            ->get();

        // Get supported currencies as fallback
        $supportedCurrencies = $this->exchangeRateService->getSupportedCurrencies();

        return view('accounting.fx-rates.edit', compact('fxRate', 'currencies', 'supportedCurrencies'));
    }

    /**
     * Update the specified FX rate.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        $fxRate = FxRate::where('company_id', $user->company_id)
            ->findOrFail($id);

        // Check if rate is locked
        if ($fxRate->is_locked) {
            return redirect()->route('accounting.fx-rates.index')
                ->withErrors(['error' => 'Cannot update locked FX rate.']);
        }

        $validated = $request->validate([
            'rate_date' => 'required|date',
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3',
            'spot_rate' => 'required|numeric|min:0.000001',
            'month_end_rate' => 'nullable|numeric|min:0.000001',
            'average_rate' => 'nullable|numeric|min:0.000001',
            'source' => 'required|in:manual,api,import',
        ]);

        // Check if currencies are different
        if ($validated['from_currency'] === $validated['to_currency']) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['to_currency' => 'From currency and To currency must be different.']);
        }

        try {
            DB::beginTransaction();

            // Delete old rate if date or currency pair changed
            if ($fxRate->rate_date != $validated['rate_date'] || 
                $fxRate->from_currency != $validated['from_currency'] || 
                $fxRate->to_currency != $validated['to_currency']) {
                
                $fxRate->delete();
                
                // Create new rate
                $fxRate = $this->exchangeRateService->storeFxRate(
                    $validated['from_currency'],
                    $validated['to_currency'],
                    $validated['rate_date'],
                    $validated['spot_rate'],
                    $validated['month_end_rate'] ?? null,
                    $validated['average_rate'] ?? null,
                    $validated['source'],
                    $user->company_id,
                    $user->id
                );
            } else {
                // Update existing rate
                $fxRate->update([
                    'spot_rate' => $validated['spot_rate'],
                    'month_end_rate' => $validated['month_end_rate'],
                    'average_rate' => $validated['average_rate'],
                    'source' => $validated['source'],
                ]);
            }

            DB::commit();

            return redirect()->route('accounting.fx-rates.index')
                ->with('success', 'FX rate updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating FX rate: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update FX rate. ' . $e->getMessage()]);
        }
    }

    /**
     * Lock the specified FX rate.
     */
    public function lock($id)
    {
        $user = Auth::user();

        $fxRate = FxRate::where('company_id', $user->company_id)
            ->findOrFail($id);

        try {
            $fxRate->lock();
            
            // Log activity
            $fxRate->logActivity('lock', "Locked FX Exchange Rate {$fxRate->from_currency}/{$fxRate->to_currency} for " . ($fxRate->rate_date ? $fxRate->rate_date->format('Y-m-d') : 'N/A'), [
                'Currency Pair' => "{$fxRate->from_currency}/{$fxRate->to_currency}",
                'Rate Date' => $fxRate->rate_date ? $fxRate->rate_date->format('Y-m-d') : 'N/A',
                'Spot Rate' => number_format($fxRate->spot_rate, 6),
                'Month-End Rate' => $fxRate->month_end_rate ? number_format($fxRate->month_end_rate, 6) : 'N/A',
                'Average Rate' => $fxRate->average_rate ? number_format($fxRate->average_rate, 6) : 'N/A',
                'Locked By' => Auth::user()->name,
                'Locked At' => now()->format('Y-m-d H:i:s')
            ]);

            return redirect()->route('accounting.fx-rates.index')
                ->with('success', 'FX rate locked successfully.');

        } catch (\Exception $e) {
            Log::error('Error locking FX rate: ' . $e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Failed to lock FX rate. ' . $e->getMessage()]);
        }
    }

    /**
     * Unlock the specified FX rate.
     */
    public function unlock($id)
    {
        $user = Auth::user();

        $fxRate = FxRate::where('company_id', $user->company_id)
            ->findOrFail($id);

        try {
            $fxRate->unlock();
            
            // Log activity
            $fxRate->logActivity('unlock', "Unlocked FX Exchange Rate {$fxRate->from_currency}/{$fxRate->to_currency} for " . ($fxRate->rate_date ? $fxRate->rate_date->format('Y-m-d') : 'N/A'), [
                'Currency Pair' => "{$fxRate->from_currency}/{$fxRate->to_currency}",
                'Rate Date' => $fxRate->rate_date ? $fxRate->rate_date->format('Y-m-d') : 'N/A',
                'Spot Rate' => number_format($fxRate->spot_rate, 6),
                'Month-End Rate' => $fxRate->month_end_rate ? number_format($fxRate->month_end_rate, 6) : 'N/A',
                'Average Rate' => $fxRate->average_rate ? number_format($fxRate->average_rate, 6) : 'N/A',
                'Unlocked By' => Auth::user()->name,
                'Unlocked At' => now()->format('Y-m-d H:i:s')
            ]);

            return redirect()->route('accounting.fx-rates.index')
                ->with('success', 'FX rate unlocked successfully.');

        } catch (\Exception $e) {
            Log::error('Error unlocking FX rate: ' . $e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Failed to unlock FX rate. ' . $e->getMessage()]);
        }
    }

    /**
     * Show import form for bulk import of FX rates.
     */
    public function import()
    {
        $user = Auth::user();
        
        // Get currencies
        $currencies = Currency::where('company_id', $user->company_id)
            ->active()
            ->orderBy('currency_code')
            ->get();

        // Get supported currencies as fallback
        $supportedCurrencies = $this->exchangeRateService->getSupportedCurrencies();

        return view('accounting.fx-rates.import', compact('currencies', 'supportedCurrencies'));
    }

    /**
     * Download sample CSV template for FX rates import.
     */
    public function downloadSample()
    {
        $user = Auth::user();
        $functionalCurrency = $user->company->functional_currency ?? 'TZS';
        
        // Get all supported currencies from API
        $supportedCurrencies = $this->exchangeRateService->getSupportedCurrencies(true);
        
        // Create sample data for all currencies (excluding functional currency)
        $sampleData = [];
        $baseRates = [
            'USD' => 2500.00,
            'EUR' => 2700.00,
            'GBP' => 3150.00,
            'KES' => 18.50,
            'UGX' => 0.65,
            'RWF' => 2.00,
            'BIF' => 0.85,
            'CDF' => 0.90,
            'ZAR' => 135.00,
            'CNY' => 350.00,
            'JPY' => 16.50,
            'AUD' => 1650.00,
            'CAD' => 1850.00,
            'CHF' => 2800.00,
            'INR' => 30.00,
            'SGD' => 1850.00,
            'AED' => 680.00,
            'SAR' => 665.00,
            'NGN' => 1.65,
            'EGP' => 80.00,
            'MAD' => 250.00,
            'ETB' => 45.00,
            'GHS' => 200.00,
            'XOF' => 4.10,
            'XAF' => 4.10,
        ];
        
        $sources = ['manual', 'api', 'import'];
        $sourceIndex = 0;
        
        foreach ($supportedCurrencies as $currencyCode => $currencyName) {
            // Skip functional currency (from_currency and to_currency must be different)
            if ($currencyCode === $functionalCurrency) {
                continue;
            }
            
            // Use base rate if available, otherwise generate a reasonable rate
            $baseRate = $baseRates[$currencyCode] ?? 1000.00;
            $spotRate = number_format($baseRate, 6, '.', '');
            
            $sampleData[] = [
                'rate_date' => date('Y-m-d'),
                'from_currency' => $currencyCode,
                'to_currency' => $functionalCurrency,
                'spot_rate' => $spotRate,
                'month_end_rate' => $spotRate,
                'average_rate' => $spotRate,
                'source' => $sources[$sourceIndex % count($sources)],
            ];
            
            $sourceIndex++;
        }
        
        // If no currencies were found, use fallback sample data
        if (empty($sampleData)) {
            $sampleData = [
                [
                    'rate_date' => date('Y-m-d'),
                    'from_currency' => 'USD',
                    'to_currency' => $functionalCurrency,
                    'spot_rate' => '2500.000000',
                    'month_end_rate' => '2500.000000',
                    'average_rate' => '2500.000000',
                    'source' => 'manual',
                ],
                [
                    'rate_date' => date('Y-m-d'),
                    'from_currency' => 'EUR',
                    'to_currency' => $functionalCurrency,
                    'spot_rate' => '2700.000000',
                    'month_end_rate' => '2700.000000',
                    'average_rate' => '2700.000000',
                    'source' => 'api',
                ],
                [
                    'rate_date' => date('Y-m-d'),
                    'from_currency' => 'GBP',
                    'to_currency' => $functionalCurrency,
                    'spot_rate' => '3150.000000',
                    'month_end_rate' => '',
                    'average_rate' => '',
                    'source' => 'import',
                ],
            ];
        }

        // Generate CSV content
        $filename = 'fx_rates_import_sample_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($sampleData) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Write header row
            fputcsv($file, [
                'rate_date',
                'from_currency',
                'to_currency',
                'spot_rate',
                'month_end_rate',
                'average_rate',
                'source'
            ]);
            
            // Write sample data rows
            foreach ($sampleData as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Process bulk import of FX rates from CSV/Excel.
     */
    public function processImport(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'file' => 'required|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        try {
            DB::beginTransaction();

            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            
            $data = [];
            
            // Handle CSV files
            if (in_array(strtolower($extension), ['csv', 'txt'])) {
                $handle = fopen($file->getRealPath(), 'r');
                
                // Skip BOM if present
                $firstLine = fgets($handle);
                if (substr($firstLine, 0, 3) === "\xEF\xBB\xBF") {
                    $firstLine = substr($firstLine, 3);
                }
                
                // Parse header row
                $headers = str_getcsv($firstLine);
                $headers = array_map('trim', $headers);
                
                // Read data rows
                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) < 4) continue; // Skip incomplete rows
                    $data[] = array_combine($headers, array_pad($row, count($headers), ''));
                }
                
                fclose($handle);
            } else {
                // Handle Excel files
                $data = Excel::toArray([], $file)[0]; // Get first sheet
                
                if (empty($data)) {
                    throw new \Exception('Excel file is empty or invalid.');
                }
                
                // Get headers from first row
                $headers = array_map('trim', array_map('strtolower', $data[0]));
                
                // Convert array rows to associative arrays
                $processedData = [];
                for ($i = 1; $i < count($data); $i++) {
                    if (count($data[$i]) < 4) continue; // Skip incomplete rows
                    $processedData[] = array_combine($headers, array_pad($data[$i], count($headers), ''));
                }
                $data = $processedData;
            }

            if (empty($data)) {
                throw new \Exception('No data found in the file. Please check the file format.');
            }

            $imported = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];

            foreach ($data as $index => $row) {
                $rowNumber = $index + 2; // +2 because: +1 for header, +1 for 0-index
                
                try {
                    // Normalize column names (case-insensitive)
                    $normalizedRow = [];
                    foreach ($row as $key => $value) {
                        $normalizedKey = strtolower(trim($key));
                        $normalizedRow[$normalizedKey] = trim($value);
                    }

                    // Extract and validate required fields
                    $rateDate = $normalizedRow['rate_date'] ?? null;
                    $fromCurrency = strtoupper(trim($normalizedRow['from_currency'] ?? ''));
                    $toCurrency = strtoupper(trim($normalizedRow['to_currency'] ?? ''));
                    $spotRate = $normalizedRow['spot_rate'] ?? null;
                    $monthEndRate = !empty($normalizedRow['month_end_rate']) ? $normalizedRow['month_end_rate'] : null;
                    $averageRate = !empty($normalizedRow['average_rate']) ? $normalizedRow['average_rate'] : null;
                    $source = strtolower(trim($normalizedRow['source'] ?? 'import'));

                    // Validate required fields
                    if (empty($rateDate)) {
                        $errors[] = "Row {$rowNumber}: rate_date is required";
                        $skipped++;
                        continue;
                    }

                    if (empty($fromCurrency) || strlen($fromCurrency) !== 3) {
                        $errors[] = "Row {$rowNumber}: from_currency must be a valid 3-letter currency code";
                        $skipped++;
                        continue;
                    }

                    if (empty($toCurrency) || strlen($toCurrency) !== 3) {
                        $errors[] = "Row {$rowNumber}: to_currency must be a valid 3-letter currency code";
                        $skipped++;
                        continue;
                    }

                    if ($fromCurrency === $toCurrency) {
                        $errors[] = "Row {$rowNumber}: from_currency and to_currency must be different";
                        $skipped++;
                        continue;
                    }

                    if (empty($spotRate) || !is_numeric($spotRate) || $spotRate <= 0) {
                        $errors[] = "Row {$rowNumber}: spot_rate must be a positive number";
                        $skipped++;
                        continue;
                    }

                    // Validate date format
                    try {
                        $rateDateObj = \Carbon\Carbon::parse($rateDate);
                        $rateDate = $rateDateObj->format('Y-m-d');
                    } catch (\Exception $e) {
                        $errors[] = "Row {$rowNumber}: Invalid date format for rate_date: {$rateDate}";
                        $skipped++;
                        continue;
                    }

                    // Validate optional fields
                    if (!empty($monthEndRate) && (!is_numeric($monthEndRate) || $monthEndRate <= 0)) {
                        $errors[] = "Row {$rowNumber}: month_end_rate must be a positive number if provided";
                        $skipped++;
                        continue;
                    }

                    if (!empty($averageRate) && (!is_numeric($averageRate) || $averageRate <= 0)) {
                        $errors[] = "Row {$rowNumber}: average_rate must be a positive number if provided";
                        $skipped++;
                        continue;
                    }

                    // Validate source
                    if (!in_array($source, ['manual', 'api', 'import'])) {
                        $source = 'import'; // Default to 'import' if invalid
                    }

                    // Check if rate already exists
                    $existing = \App\Models\FxRate::where('from_currency', $fromCurrency)
                        ->where('to_currency', $toCurrency)
                        ->where('rate_date', $rateDate)
                        ->where('company_id', $user->company_id)
                        ->first();

                    $isUpdate = $existing !== null;

                    // Store or update the rate
                    $fxRate = $this->exchangeRateService->storeFxRate(
                        $fromCurrency,
                        $toCurrency,
                        $rateDate,
                        (float) $spotRate,
                        !empty($monthEndRate) ? (float) $monthEndRate : null,
                        !empty($averageRate) ? (float) $averageRate : null,
                        $source,
                        $user->company_id,
                        $user->id
                    );

                    if ($isUpdate) {
                        $updated++;
                    } else {
                        $imported++;
                    }

                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                    $skipped++;
                    Log::error('FX Rate Import Error', [
                        'row' => $rowNumber,
                        'data' => $row,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            // Build success message
            $message = "Import completed: {$imported} new rate(s) imported";
            if ($updated > 0) {
                $message .= ", {$updated} rate(s) updated";
            }
            if ($skipped > 0) {
                $message .= ", {$skipped} row(s) skipped";
            }

            if (!empty($errors)) {
                $message .= ". " . count($errors) . " error(s) occurred.";
                return redirect()->route('accounting.fx-rates.import')
                    ->with('warning', $message)
                    ->with('import_errors', $errors);
            }

            return redirect()->route('accounting.fx-rates.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FX Rate Import Error: ' . $e->getMessage());

            return redirect()->route('accounting.fx-rates.import')
                ->with('error', 'Failed to import FX rates: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * API endpoint to get rate for date/currency pair.
     */
    public function getRate(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3',
            'date' => 'nullable|date',
            'rate_type' => 'nullable|in:spot,month_end,average',
        ]);

        $fromCurrency = $request->from_currency;
        $toCurrency = $request->to_currency;
        $date = $request->date ?? now()->toDateString();
        $rateType = $request->rate_type ?? 'spot';

        try {
            $rate = null;

            switch ($rateType) {
                case 'spot':
                    $rate = $this->exchangeRateService->getSpotRate($fromCurrency, $toCurrency, $date, $user->company_id);
                    break;
                case 'month_end':
                    $dateObj = \Carbon\Carbon::parse($date);
                    $rate = $this->exchangeRateService->getMonthEndRate(
                        $fromCurrency, 
                        $toCurrency, 
                        $dateObj->year, 
                        $dateObj->month, 
                        $user->company_id
                    );
                    break;
                case 'average':
                    // For average, we need start and end dates
                    $startDate = \Carbon\Carbon::parse($date)->startOfMonth()->toDateString();
                    $endDate = \Carbon\Carbon::parse($date)->endOfMonth()->toDateString();
                    $rate = $this->exchangeRateService->getAverageRate(
                        $fromCurrency, 
                        $toCurrency, 
                        $startDate, 
                        $endDate, 
                        $user->company_id
                    );
                    break;
            }

            // Determine source of the rate
            $source = 'FX RATES MANAGEMENT';
            if ($rateType === 'spot') {
                $fxRate = FxRate::getSpotRate($fromCurrency, $toCurrency, $date, $user->company_id);
                if ($fxRate) {
                    $source = 'FX RATES MANAGEMENT';
                } else {
                    $source = 'API/Fallback';
                }
            }
            
            return response()->json([
                'success' => true,
                'rate' => $rate,
                'source' => $source,
                'data' => [
                    'from_currency' => $fromCurrency,
                    'to_currency' => $toCurrency,
                    'date' => $date,
                    'rate_type' => $rateType,
                    'rate' => $rate,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}

