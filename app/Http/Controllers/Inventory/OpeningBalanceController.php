<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\GlTransaction;
use App\Models\Inventory\Item;
use App\Models\Inventory\Movement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Inventory\OpeningBalance;
use App\Models\SystemSetting;
use Yajra\DataTables\Facades\DataTables;
use App\Services\InventoryCostService;
use App\Services\InventoryStockService;

class OpeningBalanceController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $loginLocationId = session('location_id');
            $query = OpeningBalance::with(['item', 'item.category', 'location'])
                ->where('company_id', Auth::user()->company_id)
                ->when($loginLocationId, function ($q) use ($loginLocationId) {
                    $q->where('inventory_location_id', $loginLocationId);
                });

            return DataTables::of($query)
                ->addColumn('item_name', function ($ob) {
                    return $ob->item->name ?? 'N/A';
                })
                ->addColumn('item_code', function ($ob) {
                    return $ob->item->code ?? 'N/A';
                })
                ->addColumn('category', function ($ob) {
                    return $ob->item->category->name ?? 'N/A';
                })
                ->addColumn('location_name', function ($ob) {
                    return $ob->location->name ?? 'N/A';
                })
                ->editColumn('quantity', function ($ob) {
                    return number_format($ob->quantity, 2);
                })
                ->editColumn('unit_cost', function ($ob) {
                    return number_format($ob->unit_cost, 2);
                })
                ->editColumn('total_cost', function ($ob) {
                    return number_format($ob->total_cost, 2);
                })
                ->editColumn('opened_at', function ($ob) {
                    return optional($ob->opened_at)->format('Y-m-d');
                })
                ->make(true);
        }

        return view('inventory.opening_balances.index');
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        if (!($user->hasPermissionTo('create inventory adjustments') || $user->hasPermissionTo('manage inventory opening balances') || $user->hasPermissionTo('manage inventory movements'))) {
            abort(403, 'Unauthorized access.');
        }

        $loginLocationId = session('location_id');
        $itemsQuery = Item::where('company_id', $user->company_id);
        if ($loginLocationId) {
            $existingItemIds = OpeningBalance::where('company_id', $user->company_id)
                ->where('inventory_location_id', $loginLocationId)
                ->pluck('item_id');
            // Show all company items that do NOT yet have an opening balance for this location
            $itemsQuery->whereNotIn('id', $existingItemIds);
        }
        $items = $itemsQuery->orderBy('name')->get();
        if ($items->isEmpty()) {
            return redirect()->route('inventory.opening-balances.index')->with('info', 'Please create inventory items before adding opening balances.');
        }
        // Prefill unit cost with resolved cost (location → branch → default) when user selects an item
        Item::withResolvedPricesForContext($items);

        // Compute stock for current login location per item (for display)
        $locationStocks = [];
        if ($loginLocationId) {
            // Opening balances by base item id at this location
            $openingByItemId = OpeningBalance::where('company_id', $user->company_id)
                ->where('inventory_location_id', $loginLocationId)
                ->select('item_id', DB::raw('SUM(quantity) as qty'))
                ->groupBy('item_id')
                ->pluck('qty', 'item_id')
                ->toArray();

            // Live per-location items by code using stock service
            $stockService = new InventoryStockService();
            $perLocationItems = Item::where('company_id', $user->company_id)
                ->get(['id', 'code']);

            $liveByCode = [];
            foreach ($perLocationItems as $item) {
                $stock = $stockService->getItemStockAtLocation($item->id, $loginLocationId);
                $liveByCode[$item->code] = $stock;
            }

            foreach ($items as $it) {
                $locationStocks[$it->id] = isset($liveByCode[$it->code])
                    ? (float)$liveByCode[$it->code]
                    : (float)($openingByItemId[$it->id] ?? 0);
            }
        }

        return view('inventory.opening_balances.create', compact('items', 'locationStocks'));
    }

        public function store(Request $request)
    {
        if (!(
            auth()->user()->hasPermissionTo('manage inventory opening balances') ||
            auth()->user()->hasPermissionTo('create inventory adjustments') ||
            auth()->user()->hasPermissionTo('manage inventory movements')
        )) {
            abort(403, 'Unauthorized access.');
        }

        // Get login location and branch IDs
        $loginLocationId = session('location_id');
        $branchId = session('branch_id') ?? Auth::user()->branch_id;
        if (empty($loginLocationId)) {
            return back()->withErrors(['csv_file' => 'Please select a branch/location before posting opening balances.'])->withInput();
        }
        if (empty($branchId)) {
            return back()->withErrors(['csv_file' => 'Please select a branch before posting opening balances.'])->withInput();
        }

        // Default movement_date to today if not provided
        if (!$request->filled('movement_date')) {
            $request->merge(['movement_date' => now()->toDateString()]);
        }
        // Default movement_type to opening_balance if not provided
        if (!$request->filled('movement_type')) {
            $request->merge(['movement_type' => 'opening_balance']);
        }

        $request->validate([
            'movement_type' => 'required|in:opening_balance',
            'reference' => 'nullable|string|max:255',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string',
            'movement_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.expiry_date' => 'nullable|date|after_or_equal:today',
            'items.*.batch_number' => 'nullable|string|max:100',
            'opening' => 'nullable|boolean',
        ]);

        // Note: Expiry date is optional even for items that track expiry
        // Users can leave it blank if not applicable

        DB::transaction(function () use ($request, $loginLocationId, $branchId) {
            $costService = new InventoryCostService();
            $movements = [];

            foreach ($request->items as $itemData) {
                $item = Item::findOrFail($itemData['item_id']);

                // Verify item belongs to user's company
                if ($item->company_id !== Auth::user()->company_id) {
                    abort(403, 'Unauthorized access to item: ' . $item->name);
                }

                // Calculate total cost
                $totalCost = $itemData['quantity'] * $itemData['unit_cost'];

                // Get current stock using stock service
                $stockService = new InventoryStockService();
                $currentStock = $stockService->getItemTotalStock($item->id);
                $newStock = $currentStock + $itemData['quantity'];

                // Create movement record
                $movement = Movement::create([
                    'branch_id' => $branchId,
                    'location_id' => $loginLocationId,
                    'item_id' => $itemData['item_id'],
                    'user_id' => Auth::id(),
                    'movement_type' => 'opening_balance',
                    'quantity' => $itemData['quantity'],
                    'unit_cost' => $totalCost / $itemData['quantity'],
                    'total_cost' => $totalCost,
                    'reference' => $request->reference ?: ($request->boolean('opening') ? 'Opening Balance' : null),
                    'reason' => $request->reason ?? 'N/A',
                    'notes' => $request->notes,
                    'movement_date' => $request->movement_date,
                    'balance_before' => $currentStock,
                    'balance_after' => $newStock,
                ]);

                // Add expiry tracking if item tracks expiry and expiry date is provided
                if ($item->track_expiry && !empty($itemData['expiry_date'])) {
                    $expiryService = new \App\Services\ExpiryStockService();
                    $expiryService->addStock(
                        $item->id,
                        $loginLocationId,
                        $itemData['quantity'],
                        $totalCost / $itemData['quantity'],
                        $itemData['expiry_date'],
                        'opening_balance',
                        $movement->id,
                        $itemData['batch_number'] ?? null,
                        $request->reference ?: 'Opening Balance'
                    );

                    \Log::info('Opening Balance: Added expiry tracking', [
                        'item_id' => $item->id,
                        'expiry_date' => $itemData['expiry_date'],
                        'batch_number' => $itemData['batch_number'] ?? null,
                        'quantity' => $itemData['quantity']
                    ]);
                }

                // Create GL transactions
                    $inventoryAccountId = SystemSetting::where('key', 'inventory_default_inventory_account')->value('value');
                    $openingBalanceAccountId = SystemSetting::where('key', 'inventory_default_opening_balance_account')->value('value');

                    if ($inventoryAccountId && $openingBalanceAccountId) {
                        // DR Inventory (asset)
                        GlTransaction::create([
                            'chart_account_id' => $inventoryAccountId,
                            'amount' => $totalCost,
                            'nature' => 'debit',
                            'transaction_id' => $movement->id,
                            'transaction_type' => 'opening_balance',
                            'date' => $request->movement_date,
                            'description' => 'Opening balance - ' . ($item->name ?? 'Item'),
                        'branch_id' => $branchId,
                            'user_id' => Auth::id(),
                        ]);
                        // CR Opening Balance (equity)
                       GlTransaction::create([
                            'chart_account_id' => $openingBalanceAccountId,
                            'amount' => $totalCost,
                            'nature' => 'credit',
                            'transaction_id' => $movement->id,
                            'transaction_type' => 'opening_balance',
                            'date' => $request->movement_date,
                            'description' => 'Opening balance - ' . ($item->name ?? 'Item'),
                            'branch_id' => $branchId,
                            'user_id' => Auth::id(),
                        ]);
                    }


                // Persist independent opening balance record when posting opening balances
                if ($request->movement_type === 'opening_balance') {
                    OpeningBalance::create([
                        'company_id' => Auth::user()->company_id,
                        'branch_id' => $branchId,
                        'inventory_location_id' => $loginLocationId,
                        'item_id' => $item->id,
                        'quantity' => $itemData['quantity'],
                        'unit_cost' => $totalCost / $itemData['quantity'],
                        'total_cost' => $totalCost,
                        'reference' => $request->reference ?: 'Opening Balance',
                        'notes' => $request->notes,
                        'opened_at' => $request->movement_date,
                        'user_id' => Auth::id(),
                    ]);
                }

                // Create cost layer for FIFO/Weighted Average COGS calculation
                $costService->addInventory(
                    $item->id,
                    $itemData['quantity'],
                    $itemData['unit_cost'],
                    'opening_balance',
                    $request->reference ?: 'Opening Balance: ' . ($item->code ?? $item->name),
                    $request->movement_date
                );

                $movements[] = $movement;
            }
        });

        return redirect()->route('inventory.opening-balances.index')
            ->with('success', count($request->items) . ' stock opening balance(s) recorded successfully.');
    }

    public function import(Request $request)
    {
        $user = Auth::user();
        if (!($user->hasPermissionTo('manage inventory opening balances') || $user->hasPermissionTo('create inventory adjustments') || $user->hasPermissionTo('manage inventory movements'))) {
            abort(403, 'Unauthorized access.');
        }

        // Resolve branch and location for import context
        $loginLocationId = session('location_id');
        $branchId = session('branch_id') ?? $user->branch_id;

        Log::info('OpeningBalance import started', [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'branch_id' => $branchId,
            'location_id' => $loginLocationId,
        ]);

        // Require both branch and location
        if (empty($loginLocationId)) {
            Log::warning('OpeningBalance import aborted: missing login location', [
                'user_id' => $user->id
            ]);
            return back()->withErrors(['csv_file' => 'Please select a branch/location before importing opening balances.'])->withInput();
        }
        if (empty($branchId)) {
            Log::warning('OpeningBalance import aborted: missing branch id', [
                'user_id' => $user->id
            ]);
            return back()->withErrors(['csv_file' => 'Please select a branch before importing opening balances.'])->withInput();
        }

        // Default movement_date to today if not provided
        if (!$request->filled('movement_date')) {
            $request->merge(['movement_date' => now()->toDateString()]);
        }

        $request->validate([
            // Accept common CSV mime types in addition to file extension
            'csv_file' => 'required|file|mimes:csv,txt|mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel,application/octet-stream|max:4096',
            'movement_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $file = $request->file('csv_file');
        $rows = array_map('str_getcsv', file($file->getRealPath()));
        $header = array_map('trim', array_shift($rows));
        Log::info('OpeningBalance import header parsed', [
            'header' => $header,
            'row_count' => count($rows),
        ]);

        $required = ['item_code', 'quantity', 'unit_cost', 'has_expiry_date', 'expiry_date'];
        $missing = array_diff($required, $header);
        if (!empty($missing)) {
            Log::warning('OpeningBalance import missing columns', [
                'missing' => array_values($missing)
            ]);
            return back()->withErrors(['csv_file' => 'Missing required columns: ' . implode(', ', $missing)])->withInput();
        }

        $colIndex = array_flip($header);
        $imported = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $idx => $row) {
                // Skip column count validation since item_name is optional
                $code = trim($row[$colIndex['item_code']] ?? '');
                $qty = (float)($row[$colIndex['quantity']] ?? 0);
                $unitCost = (float)($row[$colIndex['unit_cost']] ?? 0);
                $hasExpiryDate = trim($row[$colIndex['has_expiry_date']] ?? '');
                $expiryDate = trim($row[$colIndex['expiry_date']] ?? '');

                Log::info('Processing row data', [
                    'row_number' => $idx + 2,
                    'item_code' => $code,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'has_expiry_date' => $hasExpiryDate,
                    'expiry_date' => $expiryDate,
                    'raw_row' => $row
                ]);

                // Validate individual fields
                if ($code === '') {
                    $errors[] = 'Row ' . ($idx + 2) . ': item_code is empty';
                    Log::warning('Row validation failed: empty item_code', ['row_number' => $idx + 2]);
                    continue;
                }
                if ($qty <= 0) {
                    $errors[] = 'Row ' . ($idx + 2) . ': quantity must be greater than 0';
                    Log::warning('Row validation failed: invalid quantity', [
                        'row_number' => $idx + 2,
                        'quantity' => $qty
                    ]);
                    continue;
                }
                if ($unitCost < 0) {
                    $errors[] = 'Row ' . ($idx + 2) . ': unit_cost cannot be negative';
                    Log::warning('Row validation failed: negative unit_cost', [
                        'row_number' => $idx + 2,
                        'unit_cost' => $unitCost
                    ]);
                    continue;
                }

                // Validate expiry date fields - accept both uppercase and lowercase
                $hasExpiryDateLower = strtolower($hasExpiryDate);
                if ($hasExpiryDateLower !== 'true' && $hasExpiryDateLower !== 'false') {
                    $errors[] = 'Row ' . ($idx + 2) . ': has_expiry_date must be "true" or "false" (case insensitive)';
                    Log::warning('Row validation failed: invalid has_expiry_date', [
                        'row_number' => $idx + 2,
                        'has_expiry_date' => $hasExpiryDate
                    ]);
                    continue;
                }

                $item = Item::where('company_id', $user->company_id)
                    ->where('code', $code)
                    ->first();

                if (!$item) {
                    Log::warning('Item not found', [
                        'row_number' => $idx + 2,
                        'item_code' => $code,
                        'company_id' => $user->company_id
                    ]);
                    $errors[] = 'Row ' . ($idx + 2) . ': item_code not found (' . $code . ')';
                    continue;
                }

                // Validate that has_expiry_date matches item's track_expiry setting
                $itemTrackExpiry = $item->track_expiry ? 'true' : 'false';
                if ($hasExpiryDateLower !== $itemTrackExpiry) {
                    $errors[] = 'Row ' . ($idx + 2) . ': has_expiry_date (' . $hasExpiryDate . ') does not match item\'s track_expiry setting (' . $itemTrackExpiry . ')';
                    Log::warning('Row validation failed: has_expiry_date mismatch', [
                        'row_number' => $idx + 2,
                        'has_expiry_date' => $hasExpiryDate,
                        'item_track_expiry' => $itemTrackExpiry
                    ]);
                    continue;
                }

                // Note: Expiry date is optional even for items that track expiry
                // Users can leave it blank if not applicable

                // Validate expiry date format if provided
                if (!empty($expiryDate)) {
                    try {
                        $parsedDate = \Carbon\Carbon::createFromFormat('Y-m-d', $expiryDate);
                        if ($parsedDate->format('Y-m-d') !== $expiryDate) {
                            throw new \Exception('Invalid date format');
                        }
                    } catch (\Exception $e) {
                        $errors[] = 'Row ' . ($idx + 2) . ': expiry_date must be in YYYY-MM-DD format';
                        Log::warning('Row validation failed: invalid expiry_date format', [
                            'row_number' => $idx + 2,
                            'expiry_date' => $expiryDate
                        ]);
                        continue;
                    }
                }

                $totalCost = $qty * $unitCost;
                // Create movement as opening_balance
                $movement = Movement::create([
                    'branch_id' => $branchId,
                    'location_id' => $loginLocationId,
                    'item_id' => $item->id,
                    'user_id' => $user->id,
                    'movement_type' => 'opening_balance',
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'reference' => $request->reference ?: 'Opening Balance',
                    'reason' => 'Opening Balance Import',
                    'notes' => 'Being the opening stock brought forward at the start of the financial period,
                                 representing initial quantities and values of items before any transactions occurred.',
                    'movement_date' => $request->movement_date,
                    'balance_before' => 0, // Opening balance starts from 0
                    'balance_after' => $qty, // Opening balance quantity
                ]);

                Log::info('OpeningBalance import movement created', [
                    'movement_id' => $movement->id,
                    'item_id' => $item->id,
                    'item_code' => $item->code,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                ]);

                // Add expiry tracking if item tracks expiry and expiry date is provided
                if ($item->track_expiry && !empty($expiryDate)) {
                    $expiryService = new \App\Services\ExpiryStockService();
                    $expiryService->addStock(
                        $item->id,
                        $loginLocationId,
                        $qty,
                        $unitCost,
                        $expiryDate,
                        'opening_balance',
                        $movement->id,
                        null, // No batch number for opening balance
                        $request->reference ?: 'Opening Balance'
                    );

                    \Log::info('Opening Balance: Added expiry tracking', [
                        'item_id' => $item->id,
                        'expiry_date' => $expiryDate,
                        'quantity' => $qty
                    ]);
                }
                // GL: DR Inventory, CR Opening Balance
                $inventoryAccountId = SystemSetting::where('key', 'inventory_default_inventory_account')->value('value');
                $openingBalanceAccountId = SystemSetting::where('key', 'inventory_default_opening_balance_account')->value('value');

                Log::info('Checking GL account settings', [
                    'inventory_account_id' => $inventoryAccountId,
                    'opening_balance_account_id' => $openingBalanceAccountId,
                    'row_number' => $idx + 2
                ]);

                if ($inventoryAccountId && $openingBalanceAccountId) {
                    GlTransaction::create([
                        'chart_account_id' => $inventoryAccountId,
                        'amount' => $totalCost,
                        'nature' => 'debit',
                        'transaction_id' => $movement->id,
                        'transaction_type' => 'opening_balance',
                        'date' => $request->movement_date,
                        'description' => 'Opening balance import - ' . $code,
                        'branch_id' => $branchId,
                        'user_id' => $user->id,
                    ]);
                    GlTransaction::create([
                        'chart_account_id' => $openingBalanceAccountId,
                        'amount' => $totalCost,
                        'nature' => 'credit',
                        'transaction_id' => $movement->id,
                        'transaction_type' => 'opening_balance',
                        'date' => $request->movement_date,
                        'description' => 'Opening balance import - ' . $code,
                        'branch_id' => $branchId,
                        'user_id' => $user->id,
                    ]);
                }

                // Opening balance record
                Log::info('Creating opening balance record', [
                    'row_number' => $idx + 2,
                    'company_id' => $user->company_id,
                    'branch_id' => $branchId,
                    'location_id' => $loginLocationId,
                    'item_id' => $item->id,
                    'item_code' => $item->code,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost
                ]);

                OpeningBalance::create([
                    'company_id' => $user->company_id,
                    'branch_id' => $branchId,
                    'inventory_location_id' => $loginLocationId,
                    'item_id' => $item->id,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'reference' => $request->reference ?: 'Opening Balance',
                    'notes' => $request->notes,
                    'opened_at' => $request->movement_date,
                    'user_id' => $user->id,
                ]);

                // Create cost layer for FIFO/Weighted Average COGS calculation
                $costService = new InventoryCostService();
                $costService->addInventory(
                    $item->id,
                    $qty,
                    $unitCost,
                    'opening_balance',
                    $request->reference ?: 'Opening Balance: ' . $item->code,
                    $request->movement_date
                );

                $imported++;
            }

            DB::commit();
            Log::info('OpeningBalance import committed', [
                'user_id' => $user->id,
                'imported_count' => $imported,
                'errors_count' => count($errors),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('OpeningBalance import failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['csv_file' => 'Import failed: ' . $e->getMessage()])->withInput();
        }

        if ($imported === 0) {
            Log::warning('OpeningBalance import completed with zero imports', [
                'errors_count' => count($errors)
            ]);
            return back()->withErrors(['csv_file' => 'No valid rows were imported. Please check your CSV content.'])->withInput();
        }

        $msg = "Opening balance import completed. Imported: {$imported}.";
        if (!empty($errors)) {
            $msg .= ' ' . count($errors) . ' row(s) skipped.';
        }

        return redirect()->route('inventory.opening-balances.index')->with('success', $msg)->with('ob_import_errors', $errors);
    }

    public function downloadTemplate()
    {
        $this->authorize('viewAny', Item::class);

        $filename = 'opening_balance_template.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $rows = [];
        // Header (item_name is optional - only for user reference)
        $rows[] = ['item_name','item_code', 'quantity', 'unit_cost', 'has_expiry_date', 'expiry_date'];

        // List only items without an opening balance at the current login location
        $user = Auth::user();
        $loginLocationId = session('location_id');
        $itemsQuery = Item::where('company_id', $user->company_id);
        if ($loginLocationId) {
            $existingItemIds = OpeningBalance::where('company_id', $user->company_id)
                ->where('inventory_location_id', $loginLocationId)
                ->pluck('item_id');
            $itemsQuery->whereNotIn('id', $existingItemIds);
        }
        $items = $itemsQuery->orderBy('code')->get(['name', 'code', 'cost_price', 'track_expiry']);

        foreach ($items as $item) {
            $rows[] = [
                $item->name,
                $item->code,
                '', // quantity left blank for user to fill
                (string) ($item->cost_price ?? '0'),
                $item->track_expiry ? 'true' : 'false', // has_expiry_date
                '', // expiry_date left blank for user to fill if needed
            ];
        }

        // Build CSV content
        $content = '';
        foreach ($rows as $r) {
            // Escape commas if needed
            $escaped = array_map(function ($v) {
                $v = (string) $v;
                return str_contains($v, ',') ? '"' . str_replace('"', '""', $v) . '"' : $v;
            }, $r);
            $content .= implode(',', $escaped) . "\n";
        }

        return response($content, 200, $headers);
    }
}
