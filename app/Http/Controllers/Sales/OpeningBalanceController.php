<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\OpeningBalance;
use App\Models\Sales\SalesInvoice;
use App\Models\Customer;
use App\Models\GlTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class OpeningBalanceController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
        $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
            
            $query = OpeningBalance::with('customer')
            ->when($branchId, fn($q)=>$q->where('branch_id', $branchId))
                ->where('company_id', Auth::user()->company_id);

            return DataTables::of($query)
                ->addColumn('customer_name', function ($balance) {
                    return $balance->customer->name ?? 'N/A';
                })
                ->addColumn('opening_date', function ($balance) {
                    return format_date($balance->opening_date, 'Y-m-d');
                })
                ->addColumn('amount_formatted', function ($balance) {
                    return number_format($balance->amount, 2);
                })
                ->addColumn('paid_amount_formatted', function ($balance) {
                    return '<span class="text-success">' . number_format($balance->paid_amount, 2) . '</span>';
                })
                ->addColumn('balance_due_formatted', function ($balance) {
                    return '<span class="text-primary">' . number_format($balance->balance_due, 2) . '</span>';
                })
                ->addColumn('status_badge', function ($balance) {
                    $badgeClass = match($balance->status) {
                        'posted' => 'bg-info',
                        'draft' => 'bg-warning',
                        'closed' => 'bg-success',
                        default => 'bg-secondary'
                    };
                    return '<span class="badge ' . $badgeClass . '">' . ucfirst($balance->status) . '</span>';
                })
                ->addColumn('actions', function ($balance) {
                    $hashid = Hashids::encode($balance->id);
                    $actions = '<div class="btn-group" role="group">';
                    
                    // View button
                    $actions .= '<a href="' . route('sales.opening-balances.show', $hashid) . '" class="btn btn-sm btn-outline-info" title="View">
                        <i class="bx bx-show"></i>
                    </a>';
                    
                    // Edit and Delete buttons only if no payments
                    if ($balance->paid_amount == 0) {
                        $actions .= '<a href="' . route('sales.opening-balances.edit', $hashid) . '" class="btn btn-sm btn-outline-warning" title="Edit">
                            <i class="bx bx-edit"></i>
                        </a>';
                        $actions .= '<button type="button" class="btn btn-sm btn-outline-danger delete-opening-balance" 
                                data-url="' . route('sales.opening-balances.destroy', $hashid) . '" 
                                data-customer="' . ($balance->customer->name ?? 'N/A') . '" 
                                data-amount="' . number_format($balance->amount, 2) . '" 
                                title="Delete">
                            <i class="bx bx-trash"></i>
                        </button>';
                    } else {
                        $actions .= '<span class="text-muted" title="Cannot edit/delete - has payments">
                            <i class="bx bx-lock"></i>
                        </span>';
                    }
                    
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['paid_amount_formatted', 'balance_due_formatted', 'status_badge', 'actions'])
                ->make(true);
        }

        return view('sales.opening-balances.index');
    }

    public function create()
    {
        $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
        if (!$branchId) {
            return redirect()->back()->withErrors(['error' => 'Please select a branch before creating opening balance.']);
        }
        $customers = Customer::forBranch($branchId)->get();
        return view('sales.opening-balances.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
        Log::info('sales.opening_balance.store.start', [
            'user_id' => Auth::id(),
            'branch_id' => $branchId,
            'payload' => $request->only(['customer_id','opening_date','currency','exchange_rate','amount','reference'])
        ]);
        if (!$branchId) {
            Log::warning('sales.opening_balance.store.no_branch', [
                'user_id' => Auth::id()
            ]);
            return back()->withInput()->withErrors(['error' => 'Please select a branch before creating opening balance.']);
        }

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'opening_date' => 'required|date',
            'currency' => 'nullable|string|max:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        Log::info('sales.opening_balance.store.validated');
        DB::beginTransaction();
        try {
            $companyId = Auth::user()->company_id;
            $userId = Auth::id();
            
            // Get functional currency
            $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', Auth::user()->company->functional_currency ?? 'TZS');
            $currency = $request->currency ?? $functionalCurrency;
            
            // Get exchange rate using FxTransactionRateService
            $fxTransactionRateService = app(\App\Services\FxTransactionRateService::class);
            $userProvidedRate = $request->filled('exchange_rate') ? (float) $request->exchange_rate : null;
            $rateResult = $fxTransactionRateService->getTransactionRate(
                $currency,
                $functionalCurrency,
                $request->opening_date,
                $companyId,
                $userProvidedRate
            );
            $rate = $rateResult['rate'];
            $fxRateUsed = $rate; // Store the rate used for fx_rate_used field
            
            $amount = (float) $request->amount;

            $opening = OpeningBalance::create([
                'customer_id' => $request->customer_id,
                'branch_id' => $branchId,
                'company_id' => $companyId,
                'opening_date' => $request->opening_date,
                'currency' => $currency,
                'exchange_rate' => $rate,
                'fx_rate_used' => $fxRateUsed,
                'amount' => $amount,
                'paid_amount' => 0,
                'balance_due' => $amount,
                'status' => 'posted',
                'reference' => $request->reference,
                'notes' => $request->notes,
                'created_by' => $userId,
            ]);
            Log::info('sales.opening_balance.created', [
                'opening_balance_id' => $opening->id,
                'customer_id' => $opening->customer_id,
                'amount' => $opening->amount
            ]);
            
            // Create synthetic invoice
            $invoice = SalesInvoice::create([
                'customer_id' => $request->customer_id,
                'invoice_date' => $request->opening_date,
                'due_date' => $request->opening_date,
                'status' => 'sent',
                'payment_terms' => 'immediate',
                'payment_days' => 0,
                'subtotal' => $amount,
                'vat_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $amount,
                'paid_amount' => 0,
                'balance_due' => $amount,
                'currency' => $currency,
                'exchange_rate' => $rate,
                'fx_rate_used' => $fxRateUsed,
                'notes' => 'Opening Balance',
                'terms_conditions' => null,
                'branch_id' => $branchId,
                'company_id' => $companyId,
                'created_by' => $userId,
            ]);

            $opening->update(['sales_invoice_id' => $invoice->id]);
            Log::info('sales.opening_balance.invoice_created', [
                'opening_balance_id' => $opening->id,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'total_amount' => $invoice->total_amount
            ]);

            // Post GL: Dr AR, Cr Opening AR Equity
            $receivableAccountId = $invoice->getReceivableAccountId();
            // Resolve Opening Balance Equity account with multiple fallbacks
            $openingEquityAccountId = (int) (\App\Models\SystemSetting::where('key','inventory_default_opening_balance_account')->value('value') ?? 0);
            if (!$openingEquityAccountId) {
                $openingEquityAccountId = (int) (\App\Models\SystemSetting::where('key','ar_opening_balance_account_id')->value('value') ?? 0);
            }
            if (!$openingEquityAccountId) {
                // Fallback to Retained Earnings or a safe equity account id if configured
                $openingEquityAccountId = (int) (\App\Models\SystemSetting::where('key','retained_earnings_account_id')->value('value') ?? 0);
            }
            Log::info('sales.opening_balance.gl_accounts_resolved', [
                'receivable_account_id' => $receivableAccountId,
                'opening_equity_account_id' => $openingEquityAccountId
            ]);
            if (!$openingEquityAccountId) {
                Log::warning('sales.opening_balance.missing_equity_account', [
                    'opening_balance_id' => $opening->id
                ]);
                DB::rollBack();
                return back()->withInput()->withErrors(['error' => 'Opening Balance Equity account is not configured. Set inventory_default_opening_balance_account (preferred) or ar_opening_balance_account_id or retained_earnings_account_id in Settings.']);
            }

            GlTransaction::create([
                'chart_account_id' => $receivableAccountId,
                'customer_id' => $request->customer_id,
                'amount' => $amount,
                'nature' => 'debit',
                'transaction_id' => $invoice->id,
                'transaction_type' => 'sales_invoice',
                'date' => $request->opening_date,
                'description' => 'Opening Balance AR',
                'branch_id' => $branchId,
                'user_id' => $userId,
            ]);

            GlTransaction::create([
                'chart_account_id' => $openingEquityAccountId,
                'customer_id' => $request->customer_id,
                'amount' => $amount,
                'nature' => 'credit',
                'transaction_id' => $invoice->id,
                'transaction_type' => 'sales_invoice',
                'date' => $request->opening_date,
                'description' => 'Opening Balance Offset',
                'branch_id' => $branchId,
                'user_id' => $userId,
            ]);

            DB::commit();
            Log::info('sales.opening_balance.store.success', [
                'opening_balance_id' => $opening->id,
                'invoice_id' => $invoice->id
            ]);
            $encodedId = \Vinkla\Hashids\Facades\Hashids::encode($opening->id);
            return redirect()->route('sales.opening-balances.show', $encodedId)->with('success', 'Opening balance posted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('sales.opening_balance.store.failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withInput()->withErrors(['error' => 'Failed to create opening balance: ' . $e->getMessage()]);
        }
    }

    public function show($encodedId)
    {
        try {
            $decoded = Hashids::decode($encodedId);
            $id = $decoded[0] ?? null;
            
            if (!$id) {
                \Log::warning('Invalid opening balance ID provided', [
                    'encoded_id' => $encodedId,
                    'decoded' => $decoded,
                    'user_id' => auth()->id()
                ]);
                return redirect()->route('sales.opening-balances.index')
                    ->with('error', 'Invalid opening balance ID: ' . $encodedId);
            }
            
            $balance = OpeningBalance::with(['customer','invoice'])->findOrFail($id);
            return view('sales.opening-balances.show', compact('balance'));
        } catch (\Exception $e) {
            \Log::error('Error accessing opening balance', [
                'encoded_id' => $encodedId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            return redirect()->route('sales.opening-balances.index')
                ->with('error', 'Error accessing opening balance: ' . $e->getMessage());
        }
    }

    public function edit($encodedId)
    {
        try {
            $decoded = Hashids::decode($encodedId);
            $id = $decoded[0] ?? null;
            
            if (!$id) {
                \Log::warning('Invalid opening balance ID provided for edit', [
                    'encoded_id' => $encodedId,
                    'decoded' => $decoded,
                    'user_id' => auth()->id()
                ]);
                return redirect()->route('sales.opening-balances.index')
                    ->with('error', 'Invalid opening balance ID: ' . $encodedId);
            }
        } catch (\Exception $e) {
            \Log::error('Error decoding opening balance ID for edit', [
                'encoded_id' => $encodedId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            return redirect()->route('sales.opening-balances.index')
                ->with('error', 'Error accessing opening balance: ' . $e->getMessage());
        }

        $balance = OpeningBalance::with(['customer', 'invoice'])->findOrFail($id);
        
        // Check if opening balance has payments
        if ($balance->paid_amount > 0) {
            return redirect()->route('sales.opening-balances.index')
                ->with('error', 'Cannot edit opening balance that has payments.');
        }

        $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
        if (!$branchId) {
            return redirect()->back()->withErrors(['error' => 'Please select a branch before editing opening balance.']);
        }
        
        $customers = Customer::forBranch($branchId)->get();
        return view('sales.opening-balances.edit', compact('balance', 'customers'));
    }

    public function update(Request $request, $encodedId)
    {
        try {
            $decoded = Hashids::decode($encodedId);
            $id = $decoded[0] ?? null;
            
            if (!$id) {
                \Log::warning('Invalid opening balance ID provided for update', [
                    'encoded_id' => $encodedId,
                    'decoded' => $decoded,
                    'user_id' => auth()->id()
                ]);
                return redirect()->route('sales.opening-balances.index')
                    ->with('error', 'Invalid opening balance ID: ' . $encodedId);
            }
        } catch (\Exception $e) {
            \Log::error('Error decoding opening balance ID for update', [
                'encoded_id' => $encodedId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            return redirect()->route('sales.opening-balances.index')
                ->with('error', 'Error accessing opening balance: ' . $e->getMessage());
        }

        $balance = OpeningBalance::with(['customer', 'invoice'])->findOrFail($id);
        
        // Check if opening balance has payments
        if ($balance->paid_amount > 0) {
            return redirect()->route('sales.opening-balances.index')
                ->with('error', 'Cannot edit opening balance that has payments.');
        }

        $branchId = session('branch_id') ?? (Auth::user()->branch_id ?? null);
        if (!$branchId) {
            return redirect()->back()->withErrors(['error' => 'Please select a branch before updating opening balance.']);
        }

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'opening_date' => 'required|date',
            'currency' => 'nullable|string|max:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        Log::info('sales.opening_balance.update.start', [
            'opening_balance_id' => $balance->id,
            'user_id' => Auth::id(),
            'branch_id' => $branchId,
            'payload' => $request->only(['customer_id','opening_date','currency','exchange_rate','amount','reference'])
        ]);

        DB::beginTransaction();
        try {
            $companyId = Auth::user()->company_id;
            $userId = Auth::id();
            $currency = $request->currency ?: 'TZS';
            $rate = $request->exchange_rate ?: 1.000000;
            $amount = (float) $request->amount;

            // Update opening balance
            $balance->update([
                'customer_id' => $request->customer_id,
                'opening_date' => $request->opening_date,
                'currency' => $currency,
                'exchange_rate' => $rate,
                'amount' => $amount,
                'balance_due' => $amount, // Reset balance due since no payments
                'reference' => $request->reference,
                'notes' => $request->notes,
                'updated_by' => $userId,
            ]);

            // Update the linked invoice
            if ($balance->invoice) {
                $balance->invoice->update([
                    'customer_id' => $request->customer_id,
                    'invoice_date' => $request->opening_date,
                    'due_date' => $request->opening_date,
                    'subtotal' => $amount,
                    'total_amount' => $amount,
                    'balance_due' => $amount,
                    'currency' => $currency,
                    'exchange_rate' => $rate,
                    'notes' => 'Opening Balance',
                ]);
            }

            // Delete existing GL transactions
            GlTransaction::where('transaction_type', 'sales_invoice')
                ->where('transaction_id', $balance->invoice->id)
                ->delete();

            // Create new GL transactions
            $receivableAccountId = $balance->invoice->getReceivableAccountId();
            $openingEquityAccountId = (int) (\App\Models\SystemSetting::where('key','inventory_default_opening_balance_account')->value('value') ?? 0);
            if (!$openingEquityAccountId) {
                $openingEquityAccountId = (int) (\App\Models\SystemSetting::where('key','ar_opening_balance_account_id')->value('value') ?? 0);
            }
            if (!$openingEquityAccountId) {
                $openingEquityAccountId = (int) (\App\Models\SystemSetting::where('key','retained_earnings_account_id')->value('value') ?? 0);
            }

            if (!$openingEquityAccountId) {
                DB::rollBack();
                return back()->withInput()->withErrors(['error' => 'Opening Balance Equity account is not configured.']);
            }

            GlTransaction::create([
                'chart_account_id' => $receivableAccountId,
                'customer_id' => $request->customer_id,
                'amount' => $amount,
                'nature' => 'debit',
                'transaction_id' => $balance->invoice->id,
                'transaction_type' => 'sales_invoice',
                'date' => $request->opening_date,
                'description' => 'Opening Balance AR',
                'branch_id' => $branchId,
                'user_id' => $userId,
            ]);

            GlTransaction::create([
                'chart_account_id' => $openingEquityAccountId,
                'customer_id' => $request->customer_id,
                'amount' => $amount,
                'nature' => 'credit',
                'transaction_id' => $balance->invoice->id,
                'transaction_type' => 'sales_invoice',
                'date' => $request->opening_date,
                'description' => 'Opening Balance Offset',
                'branch_id' => $branchId,
                'user_id' => $userId,
            ]);

            DB::commit();
            Log::info('sales.opening_balance.update.success', [
                'opening_balance_id' => $balance->id,
                'invoice_id' => $balance->invoice->id
            ]);

            return redirect()->route('sales.opening-balances.show', $encodedId)->with('success', 'Opening balance updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('sales.opening_balance.update.failed', [
                'opening_balance_id' => $balance->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withInput()->withErrors(['error' => 'Failed to update opening balance: ' . $e->getMessage()]);
        }
    }

    public function destroy($encodedId)
    {
        try {
            $decoded = Hashids::decode($encodedId);
            $id = $decoded[0] ?? null;
            
            if (!$id) {
                \Log::warning('Invalid opening balance ID provided for delete', [
                    'encoded_id' => $encodedId,
                    'decoded' => $decoded,
                    'user_id' => auth()->id()
                ]);
                return response()->json(['success' => false, 'message' => 'Invalid opening balance ID: ' . $encodedId], 400);
            }
        } catch (\Exception $e) {
            \Log::error('Error decoding opening balance ID for delete', [
                'encoded_id' => $encodedId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            return response()->json(['success' => false, 'message' => 'Error accessing opening balance: ' . $e->getMessage()], 400);
        }

        $balance = OpeningBalance::with(['customer', 'invoice'])->findOrFail($id);
        
        // Check if opening balance has payments
        if ($balance->paid_amount > 0) {
            return response()->json(['success' => false, 'message' => 'Cannot delete opening balance that has payments.'], 422);
        }

        Log::info('sales.opening_balance.destroy.start', [
            'opening_balance_id' => $balance->id,
            'user_id' => Auth::id()
        ]);

        DB::beginTransaction();
        try {
            // Delete GL transactions
            if ($balance->invoice) {
                GlTransaction::where('transaction_type', 'sales_invoice')
                    ->where('transaction_id', $balance->invoice->id)
                    ->delete();
            }

            // Delete the linked invoice
            if ($balance->invoice) {
                $balance->invoice->delete();
            }

            // Delete the opening balance
            $balance->delete();

            DB::commit();
            Log::info('sales.opening_balance.destroy.success', [
                'opening_balance_id' => $balance->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Opening balance deleted successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('sales.opening_balance.destroy.failed', [
                'opening_balance_id' => $balance->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to delete opening balance: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show the import form
     */
    public function import()
    {
        return view('sales.opening-balances.import');
    }

    /**
     * Download the import template
     */
    public function downloadTemplate()
    {
        $filename = 'opening_balances_template.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'customer_name',
                'opening_date',
                'amount',
                'currency',
                'exchange_rate',
                'reference',
                'notes'
            ]);

            // Get all customers for the current company
            $customers = Customer::where('company_id', auth()->user()->company_id)
                ->orderBy('name')
                ->get();

            // Add sample data for each customer
            $sampleAmounts = [25000.00, 50000.00, 75000.00, 100000.00, 150000.00];
            $sampleDates = ['2024-01-01', '2024-01-15', '2024-02-01', '2024-02-15', '2024-03-01'];
            $sampleReferences = ['OB-2024-001', 'OB-2024-002', 'OB-2024-003', 'OB-2024-004', 'OB-2024-005'];
            $sampleNotes = [
                'Opening balance',
                'Initial customer balance',
                'Starting balance',
                'Opening balance for customer',
                'Initial outstanding balance'
            ];

            foreach ($customers as $index => $customer) {
                $amount = $sampleAmounts[$index % count($sampleAmounts)];
                $date = $sampleDates[$index % count($sampleDates)];
                $reference = $sampleReferences[$index % count($sampleReferences)];
                $note = $sampleNotes[$index % count($sampleNotes)];

                fputcsv($file, [
                    $customer->name,
                    $date,
                    number_format($amount, 2),
                    'TZS',
                    '1.00',
                    $reference,
                    $note . ' - ' . $customer->name
                ]);
            }

            // If no customers exist, add a few sample rows
            if ($customers->isEmpty()) {
                fputcsv($file, [
                    'Sample Customer 1',
                    '2024-01-01',
                    '50000.00',
                    'TZS',
                    '1.00',
                    'OB-2024-001',
                    'Opening balance for Sample Customer 1'
                ]);

                fputcsv($file, [
                    'Sample Customer 2',
                    '2024-01-01',
                    '75000.00',
                    'TZS',
                    '1.00',
                    'OB-2024-002',
                    'Opening balance for Sample Customer 2'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Process the bulk import
     */
    public function processImport(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('import_file');
        $path = $file->getRealPath();
        
        $data = array_map('str_getcsv', file($path));
        $header = array_shift($data); // Remove header row
        
        $imported = 0;
        $errors = [];
        $lineNumber = 1;
        $invoiceCounter = 0; // Counter for unique invoice numbers

        // Start database transaction
        \DB::beginTransaction();

        try {
            foreach ($data as $row) {
                $lineNumber++;
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                try {
                    // Map CSV columns to array
                    $rowData = array_combine($header, $row);
                    
                    // Validate required fields
                    if (empty($rowData['customer_name']) || empty($rowData['opening_date']) || empty($rowData['amount'])) {
                        $errors[] = "Line {$lineNumber}: Missing required fields (customer_name, opening_date, amount)";
                        continue;
                    }

                    // Find customer by name
                    $customer = Customer::where('company_id', auth()->user()->company_id)
                        ->where('name', 'like', '%' . trim($rowData['customer_name']) . '%')
                        ->first();

                    if (!$customer) {
                        $errors[] = "Line {$lineNumber}: Customer '{$rowData['customer_name']}' not found";
                        continue;
                    }

                    // Validate date
                    try {
                        $openingDate = \Carbon\Carbon::createFromFormat('Y-m-d', $rowData['opening_date']);
                    } catch (\Exception $e) {
                        $errors[] = "Line {$lineNumber}: Invalid date format. Use YYYY-MM-DD";
                        continue;
                    }

                    // Validate amount
                    $amount = floatval($rowData['amount']);
                    if ($amount <= 0) {
                        $errors[] = "Line {$lineNumber}: Amount must be greater than 0";
                        continue;
                    }

                    // Determine branch ID
                    $branchId = session('branch_id') ?? auth()->user()->branch_id;
                    if (!$branchId) {
                        // If no branch is set, use the first available branch for the user
                        $userBranches = auth()->user()->branches;
                        $branchId = $userBranches->first()?->id;
                    }
                    
                    if (!$branchId) {
                        $errors[] = "Line {$lineNumber}: No branch available for user";
                        continue;
                    }

                    // Check for duplicate opening balance
                    $existingOpeningBalance = OpeningBalance::where('customer_id', $customer->id)
                        ->where('opening_date', $openingDate)
                        ->where('amount', $amount)
                        ->where('company_id', auth()->user()->company_id)
                        ->where('branch_id', $branchId)
                        ->first();

                    if ($existingOpeningBalance) {
                        $errors[] = "Line {$lineNumber}: Opening balance already exists for {$customer->name} on {$openingDate} with amount {$amount}";
                        continue;
                    }

                    // Create opening balance
                    $openingBalance = OpeningBalance::create([
                        'customer_id' => $customer->id,
                        'opening_date' => $openingDate,
                        'amount' => $amount,
                        'paid_amount' => 0.00,
                        'balance_due' => $amount,
                        'currency' => $rowData['currency'] ?? 'TZS',
                        'exchange_rate' => floatval($rowData['exchange_rate'] ?? 1.00),
                        'reference' => $rowData['reference'] ?? null,
                        'notes' => $rowData['notes'] ?? null,
                        'status' => 'posted',
                        'company_id' => auth()->user()->company_id,
                        'branch_id' => $branchId,
                        'created_by' => auth()->id(),
                    ]);

                    // Create synthetic sales invoice
                    $invoiceCounter++;
                    $invoiceNumber = $this->generateInvoiceNumber($invoiceCounter);
                    $salesInvoice = SalesInvoice::create([
                        'invoice_number' => $invoiceNumber,
                        'customer_id' => $customer->id,
                        'invoice_date' => $openingDate,
                        'due_date' => $openingDate,
                        'subtotal' => $amount,
                        'vat_amount' => 0,
                        'discount_amount' => 0,
                        'total_amount' => $amount,
                        'balance_due' => $amount,
                        'currency' => $rowData['currency'] ?? 'TZS',
                        'exchange_rate' => floatval($rowData['exchange_rate'] ?? 1.00),
                        'status' => 'sent',
                        'payment_terms' => 'immediate',
                        'notes' => 'Opening Balance: ' . ($rowData['notes'] ?? ''),
                        'reference' => $rowData['reference'] ?? null,
                        'company_id' => auth()->user()->company_id,
                        'branch_id' => $branchId,
                        'user_id' => auth()->id(),
                        'created_by' => auth()->id(),
                        'early_payment_discount_enabled' => 0,
                        'early_payment_days' => 0,
                        'late_payment_fees_enabled' => 0,
                    ]);

                    // Link opening balance to invoice
                    $openingBalance->update(['sales_invoice_id' => $salesInvoice->id]);

                    // Post GL transactions
                    $this->postGlTransactions($openingBalance, $salesInvoice);

                    $imported++;

                } catch (\Exception $e) {
                    $errors[] = "Line {$lineNumber}: " . $e->getMessage();
                    // If we have critical errors, rollback the transaction
                    if (count($errors) > 10) { // Allow some errors but not too many
                        throw new \Exception("Too many errors encountered. Import aborted.");
                    }
                }
            }

        // Check if we should commit or rollback
        if (!empty($errors) && $imported === 0) {
            // No successful imports and we have errors - rollback
            \DB::rollback();
            return redirect()->route('sales.opening-balances.import')
                ->with('error', 'Import failed. No opening balances were imported due to errors.')
                ->with('import_errors', $errors);
        } elseif (!empty($errors) && $imported > 0) {
            // Some imports succeeded but we have errors - commit what we have
            \DB::commit();
            $message = "Successfully imported {$imported} opening balance(s)";
            $message .= ". " . count($errors) . " error(s) occurred.";
            return redirect()->route('sales.opening-balances.index')
                ->with('success', $message)
                ->with('import_errors', $errors);
        } else {
            // All imports succeeded - commit
            \DB::commit();
            $message = "Successfully imported {$imported} opening balance(s)";
            return redirect()->route('sales.opening-balances.index')
                ->with('success', $message);
        }

        } catch (\Exception $e) {
            // Rollback transaction on any critical error
            \DB::rollback();
            return redirect()->route('sales.opening-balances.import')
                ->with('error', 'Import failed: ' . $e->getMessage())
                ->with('import_errors', $errors);
        }
    }

    /**
     * Post GL transactions for opening balance
     */
    private function postGlTransactions($openingBalance, $salesInvoice)
    {
        $amount = $openingBalance->amount;
        $branchId = $openingBalance->branch_id;
        $userId = auth()->id();
        $openingDate = $openingBalance->opening_date;

        // Get receivable account ID
        $receivableAccountId = (int) (\App\Models\SystemSetting::where('key','inventory_default_receivable_account')->value('value') ?? 0);
        if (!$receivableAccountId) {
            $receivableAccountId = (int) (\App\Models\SystemSetting::where('key','ar_opening_balance_account_id')->value('value') ?? 0);
        }
        if (!$receivableAccountId) {
            $receivableAccountId = (int) (\App\Models\SystemSetting::where('key','accounts_receivable_account_id')->value('value') ?? 0);
        }

        // Get opening equity account ID
        $openingEquityAccountId = (int) (\App\Models\SystemSetting::where('key','inventory_default_opening_balance_account')->value('value') ?? 0);
        if (!$openingEquityAccountId) {
            $openingEquityAccountId = (int) (\App\Models\SystemSetting::where('key','ar_opening_balance_account_id')->value('value') ?? 0);
        }
        if (!$openingEquityAccountId) {
            $openingEquityAccountId = (int) (\App\Models\SystemSetting::where('key','retained_earnings_account_id')->value('value') ?? 0);
        }

        if (!$receivableAccountId || !$openingEquityAccountId) {
            throw new \Exception('Required GL accounts are not configured. Please set accounts_receivable_account_id and opening balance equity account in Settings.');
        }

        // Create GL transactions
        \App\Models\GlTransaction::create([
            'chart_account_id' => $receivableAccountId,
            'customer_id' => $openingBalance->customer_id,
            'amount' => $amount,
            'nature' => 'debit',
            'transaction_id' => $salesInvoice->id,
            'transaction_type' => 'sales_invoice',
            'date' => $openingDate,
            'description' => 'Opening Balance AR',
            'branch_id' => $branchId,
            'user_id' => $userId,
        ]);

        \App\Models\GlTransaction::create([
            'chart_account_id' => $openingEquityAccountId,
            'customer_id' => $openingBalance->customer_id,
            'amount' => $amount,
            'nature' => 'credit',
            'transaction_id' => $salesInvoice->id,
            'transaction_type' => 'sales_invoice',
            'date' => $openingDate,
            'description' => 'Opening Balance Offset',
            'branch_id' => $branchId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Generate invoice number
     */
    private function generateInvoiceNumber($counter = null)
    {
        // Generate a unique invoice number for opening balance invoices
        $prefix = 'OB-INV-';
        $date = date('Ymd');
        
        if ($counter !== null) {
            // Get the highest existing invoice number for today
            $lastInvoice = SalesInvoice::where('invoice_number', 'like', $prefix . $date . '%')
                ->orderBy('invoice_number', 'desc')
                ->first();
            
            $startNumber = 1;
            if ($lastInvoice) {
                // Extract the sequence number and start from the next available number
                $lastNumber = (int) substr($lastInvoice->invoice_number, -6);
                $startNumber = $lastNumber + 1;
            }
            
            // Use the provided counter offset from the start number
            $nextNumber = str_pad($startNumber + $counter - 1, 6, '0', STR_PAD_LEFT);
        } else {
            // Get the last invoice number for today
            $lastInvoice = SalesInvoice::where('invoice_number', 'like', $prefix . $date . '%')
                ->orderBy('invoice_number', 'desc')
                ->first();
            
            if ($lastInvoice) {
                // Extract the sequence number and increment it
                $lastNumber = (int) substr($lastInvoice->invoice_number, -6);
                $nextNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
            } else {
                // First invoice of the day
                $nextNumber = '000001';
            }
        }
        
        return $prefix . $date . $nextNumber;
    }
}


