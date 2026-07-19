<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Inventory\Category;
use App\Models\Inventory\PermanentStorageBalance;
use App\Models\Inventory\PermanentStorageGharama;
use App\Models\Inventory\PermanentStorageMalipo;
use App\Models\Inventory\PermanentStorageMapato;
use App\Models\Inventory\PermanentStorageReceipt;
use App\Models\Inventory\PermanentStorageSale;
use App\Models\Inventory\PermanentStorageWithdrawal;
use App\Models\Inventory\Item;
use App\Models\InventoryLocation;
use App\Services\Inventory\PermanentStorageCustomerSmsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class PermanentStorageController extends Controller
{
    /**
     * Align branch/location session with inventory items (ItemController).
     */
    private function ensureInventorySession(): void
    {
        if (! session('location_id')) {
            $user = Auth::user();
            $defaultLocation = $user->defaultLocation()->first();

            if ($defaultLocation) {
                session([
                    'location_id' => $defaultLocation->id,
                    'branch_id' => $defaultLocation->branch_id,
                ]);
            } else {
                $firstLocation = $user->locations()->first();
                if ($firstLocation) {
                    session([
                        'location_id' => $firstLocation->id,
                        'branch_id' => $firstLocation->branch_id,
                    ]);
                }
            }
        } elseif (! session('branch_id') && session('location_id')) {
            $branchId = InventoryLocation::where('id', session('location_id'))->value('branch_id');
            if ($branchId) {
                session(['branch_id' => $branchId]);
            }
        }
    }

    private function currentBranchId(): ?int
    {
        $this->ensureInventorySession();

        if (session('branch_id')) {
            return (int) session('branch_id');
        }

        $user = Auth::user();

        return $user->branch_id ? (int) $user->branch_id : null;
    }

    private function customersForCurrentBranch()
    {
        $user = Auth::user();
        $branchId = $this->currentBranchId();

        $query = Customer::where('company_id', $user->company_id);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->orderBy('name');
    }

    /**
     * Same visibility rules as inventory/items list (ItemController@index).
     */
    private function itemsForCurrentBranch()
    {
        $user = Auth::user();
        $sessionBranchId = session('branch_id') ? (int) session('branch_id') : null;

        return Item::query()
            ->where('company_id', $user->company_id)
            ->visibleInSessionBranch($sessionBranchId)
            ->orderBy('name');
    }

    public function index(Request $request)
    {
        $this->ensureInventorySession();

        $user = Auth::user();
        $branchId = $this->currentBranchId();

        if ($request->has('draw')) {
            return $this->balancesDatatable($request);
        }

        $customers = $this->customersForCurrentBranch()
            ->get(['id', 'name', 'phone']);

        $items = $this->itemsForCurrentBranch()
            ->get(['id', 'name', 'code', 'unit_of_measure']);

        $unitOptions = Item::unitOfMeasureOptions();

        $categories = Category::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $assignableBranches = Branch::where('company_id', $user->company_id)
            ->whereIn('id', $user->permittedBranchIds())
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        $balanceSummary = $this->buildBalanceSummary(
            (int) $user->company_id,
            $branchId ? (int) $branchId : null
        );

        $listStatus = $request->query('status') === PermanentStorageBalance::STATUS_INACTIVE
            ? PermanentStorageBalance::STATUS_INACTIVE
            : PermanentStorageBalance::STATUS_ACTIVE;

        $statusCounts = $this->balanceStatusCounts(
            (int) $user->company_id,
            $branchId ? (int) $branchId : null,
            PermanentStorageBalance::class
        );

        return view('inventory.permanent-storage.index', compact(
            'customers',
            'items',
            'unitOptions',
            'categories',
            'assignableBranches',
            'branchId',
            'balanceSummary',
            'listStatus',
            'statusCounts'
        ));
    }

    /**
     * @return array<int, array{
     *     item_name: string,
     *     item_code: string,
     *     total_quantity: float,
     *     unit: string,
     *     package_count: float|null,
     *     package_name: string,
     *     quantity_display: string,
     *     package_display: string,
     *     summary_display: string
     * }>
     */
    private function buildBalanceSummary(int $companyId, ?int $branchId): array
    {
        if (! Schema::hasTable('permanent_storage_balances')) {
            return [];
        }

        $balances = PermanentStorageBalance::query()
            ->with('item')
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('status', PermanentStorageBalance::STATUS_ACTIVE)
            ->where('quantity_on_hand', '>', 0)
            ->get();

        if ($balances->isEmpty()) {
            return [];
        }

        return $balances
            ->groupBy('inventory_item_id')
            ->map(function ($rows) {
                /** @var PermanentStorageBalance $first */
                $first = $rows->first();
                $item = $first->item;
                $totalQty = (float) $rows->sum('quantity_on_hand');
                $unit = trim((string) ($item->unit_of_measure ?? ''));
                $packageQuantity = (float) ($item->package_quantity ?? 0);
                $packageName = trim((string) ($item->package_name ?? ''));

                $quantityDisplay = $this->formatQuantityWithUnit($totalQty, $item);
                $packageCount = null;
                $packageDisplay = '—';

                if ($packageQuantity > 0 && $packageName !== '') {
                    $packageCount = $totalQty / $packageQuantity;
                    $packageDisplay = $this->formatStorageNumber($packageCount).' '.$packageName;
                }

                $summaryDisplay = $packageDisplay !== '—'
                    ? $packageDisplay.' ('.$quantityDisplay.')'
                    : $quantityDisplay;

                return [
                    'item_name' => $item->name ?? '—',
                    'item_code' => $item->code ?? '',
                    'total_quantity' => $totalQty,
                    'unit' => $unit,
                    'package_count' => $packageCount,
                    'package_name' => $packageName,
                    'quantity_display' => $quantityDisplay,
                    'package_display' => $packageDisplay,
                    'summary_display' => $summaryDisplay,
                ];
            })
            ->sortBy('item_name')
            ->values()
            ->all();
    }

    public function quickStoreCustomer(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = $this->currentBranchId();

        if (! $branchId) {
            return response()->json([
                'success' => false,
                'message' => 'Tawi halijachaguliwa. Chagua tawi au eneo la hesabu kisha jaribu tena.',
            ], 422);
        }

        if ($request->filled('phone') && function_exists('normalize_phone_number')) {
            $request->merge(['phone' => normalize_phone_number($request->input('phone'))]);
        } else {
            $request->merge(['phone' => $request->input('phone') ?: null]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => [
                'nullable',
                'string',
                Rule::when($request->filled('phone'), ['size:12', 'regex:/^[0-9]+$/']),
            ],
            'description' => 'nullable|string|max:1000',
            'id_type' => ['nullable', Rule::in(array_keys(Customer::idTypeOptions()))],
            'id_number' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:50',
            'account_name' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,suspended',
            'send_welcome_sms' => 'nullable|boolean',
        ], [
            'name.required' => 'Jina la mteja linahitajika.',
            'phone.size' => 'Namba ya simu iwe na tarakimu 12 (mfano: 255712345678).',
            'phone.regex' => 'Namba ya simu iwe na tarakimu tu.',
            'status.required' => 'Hali ya mteja inahitajika.',
        ]);

        $idType = ! empty($validated['id_type']) ? strtolower((string) $validated['id_type']) : null;

        $customer = DB::transaction(function () use ($validated, $idType, $branchId, $companyId) {
            $customer = Customer::create([
                'customerNo' => Customer::nextCustomerNo(),
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'description' => $validated['description'] ?? null,
                'id_type' => $idType,
                'id_number' => $validated['id_number'] ?? null,
                'bank_name' => $validated['bank_name'] ?? null,
                'bank_account_number' => $validated['bank_account_number'] ?? null,
                'account_name' => $validated['account_name'] ?? null,
                'status' => $validated['status'],
                'branch_id' => $branchId,
                'company_id' => $companyId,
            ]);

            $customer->ensureLoanAccount();

            return $customer;
        });

        return response()->json([
            'success' => true,
            'message' => 'Mteja amesajiliwa kikamilifu.',
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
            ],
        ], 201);
    }

    public function quickStoreItem(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $allowedBranchIds = $user->permittedBranchIds();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:inventory_items,code',
            'category_id' => 'required|exists:inventory_categories,id',
            'unit_of_measure' => ['required', Rule::in(array_keys(Item::unitOfMeasureOptions()))],
            'package_name' => 'nullable|string|max:255',
            'package_quantity' => 'nullable|numeric|min:0',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => ['integer', Rule::in($allowedBranchIds)],
        ], [
            'name.required' => 'Jina la bidhaa linahitajika.',
            'code.required' => 'Nambari ya bidhaa inahitajika.',
            'code.unique' => 'Nambari ya bidhaa tayari imetumika.',
            'category_id.required' => 'Kategoria inahitajika.',
            'unit_of_measure.required' => 'Kipimo kinahitajika.',
        ]);

        Category::where('company_id', $companyId)
            ->where('id', $validated['category_id'])
            ->firstOrFail();

        $item = DB::transaction(function () use ($request, $validated, $companyId) {
            $item = Item::create([
                'company_id' => $companyId,
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'code' => $validated['code'],
                'item_type' => 'product',
                'unit_of_measure' => $validated['unit_of_measure'],
                'package_name' => $validated['package_name'] ?? null,
                'package_quantity' => $validated['package_quantity'] ?? null,
                'cost_price' => 0,
                'unit_price' => 0,
                'minimum_stock' => 0,
                'maximum_stock' => 0,
                'reorder_level' => 0,
                'is_active' => true,
                'track_stock' => true,
                'track_expiry' => false,
                'has_opening_balance' => false,
                'opening_balance_quantity' => 0,
                'opening_balance_value' => 0,
            ]);

            if (Schema::hasTable('inventory_items_branches')) {
                $branchIds = collect($request->input('branch_ids', []))
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                // Empty = visible in all branches (same as inventory items form).
                $item->visibilityBranches()->sync($branchIds);
            }

            return $item;
        });

        return response()->json([
            'success' => true,
            'message' => 'Zao limesajiliwa kikamilifu.',
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
            ],
        ], 201);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $branchId = $this->currentBranchId();
        $companyId = $user->company_id;

        if (! $branchId) {
            return back()
                ->withInput()
                ->withErrors(['customer_id' => 'Tawi halijachaguliwa. Chagua tawi au eneo la hesabu kisha jaribu tena.']);
        }

        $validated = $request->validate([
            'customer_id' => 'required|integer',
            'inventory_item_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'received_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ], [
            'customer_id.required' => 'Chagua mteja.',
            'inventory_item_id.required' => 'Chagua zao.',
            'quantity.required' => 'Weka idadi.',
            'quantity.min' => 'Idadi iwe angalau 1.',
            'received_date.required' => 'Weka tarehe aliyoleta zao.',
        ]);

        $customer = Customer::where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->where('id', $validated['customer_id'])
            ->first();

        if (! $customer) {
            return back()
                ->withInput()
                ->withErrors(['customer_id' => 'Mteja hajapatikana katika tawi hili. Chagua mteja mwingine au ongeza mteja mpya.']);
        }

        $item = $this->itemsForCurrentBranch()
            ->where('id', $validated['inventory_item_id'])
            ->first();

        if (! $item) {
            return back()
                ->withInput()
                ->withErrors(['inventory_item_id' => 'Zao halipatikani katika tawi hili. Chagua zao lingine au ongeza zao jipya.']);
        }

        if (! Schema::hasTable('permanent_storage_receipts') || ! Schema::hasTable('permanent_storage_balances')) {
            return back()
                ->withInput()
                ->withErrors(['quantity' => 'Jedwali la uhifadhi wa mazao wa kudumu halijasanidiwa. Wasiliana na msimamizi wa mfumo kuendesha migrations.']);
        }

        try {
            DB::transaction(function () use ($user, $branchId, $customer, $item, $validated) {
                PermanentStorageReceipt::create([
                    'company_id' => $user->company_id,
                    'branch_id' => $branchId,
                    'customer_id' => $customer->id,
                    'inventory_item_id' => $item->id,
                    'quantity' => $validated['quantity'],
                    'received_date' => $validated['received_date'],
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $user->id,
                ]);

                $balance = PermanentStorageBalance::firstOrNew([
                    'company_id' => $user->company_id,
                    'branch_id' => $branchId,
                    'customer_id' => $customer->id,
                    'inventory_item_id' => $item->id,
                ]);

                $balance->quantity_on_hand = (float) ($balance->quantity_on_hand ?? 0) + (float) $validated['quantity'];
                $balance->status = PermanentStorageBalance::STATUS_ACTIVE;
                $balance->save();
            });
        } catch (\Throwable $e) {
            \Log::error('Permanent storage store failed: ' . $e->getMessage(), [
                'customer_id' => $validated['customer_id'],
                'inventory_item_id' => $validated['inventory_item_id'],
                'branch_id' => $branchId,
            ]);

            return back()
                ->withInput()
                ->withErrors(['quantity' => 'Imeshindikana kuhifadhi zao. Jaribu tena au wasiliana na msimamizi wa mfumo.']);
        }

        return redirect()
            ->to(route('inventory.permanent-storage.index'))
            ->with('success', 'Zao la mteja ' . $customer->name . ' limepokelewa kikamilifu (idadi: ' . (int) $validated['quantity'] . ').');
    }

    public function withdraw(Request $request)
    {
        $user = Auth::user();
        $branchId = $this->currentBranchId();
        $companyId = $user->company_id;

        $validated = $request->validate([
            'balance_id' => 'required|integer',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => ['required', Rule::in(array_keys(PermanentStorageWithdrawal::reasonOptions()))],
            'price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ], [
            'balance_id.required' => 'Salio halijapatikana.',
            'quantity.required' => 'Weka idadi ya kutoa.',
            'quantity.min' => 'Idadi iwe angalau 1.',
            'reason.required' => 'Chagua sababu ya kutoa zao.',
            'price.min' => 'Bei haiwezi kuwa hasi.',
        ]);

        if ($validated['reason'] === 'kuuza') {
            $request->validate([
                'price' => 'required|numeric|min:0.01',
            ], [
                'price.required' => 'Weka bei kwa kila kilo.',
                'price.min' => 'Bei iwe zaidi ya 0.',
            ]);
            $validated['price'] = (float) $request->input('price');
        }

        if (! Schema::hasTable('permanent_storage_withdrawals')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jedwali la utoaji wa zao halijasanidiwa. Endesha migrations.',
                ], 422);
            }

            return back()->withErrors(['quantity' => 'Jedwali la utoaji wa zao halijasanidiwa. Endesha migrations.']);
        }

        $balance = PermanentStorageBalance::query()
            ->with(['customer', 'item'])
            ->where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->where('id', $validated['balance_id'])
            ->first();

        if (! $balance) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Salio halipatikani katika tawi hili.',
                ], 422);
            }

            return back()->withErrors(['quantity' => 'Salio halipatikani katika tawi hili.']);
        }

        if ($validated['reason'] === 'kuuza' && ! Schema::hasTable('permanent_storage_sales')) {
            $migrationMessage = 'Jedwali la mauzo ya zao halijasanidiwa. Endesha migrations.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $migrationMessage], 422);
            }

            return back()->withErrors(['price' => $migrationMessage]);
        }

        $withdrawQty = (float) $validated['quantity'];
        $onHand = (float) $balance->quantity_on_hand;

        if ($withdrawQty > $onHand) {
            $message = 'Idadi ya kutoa haiwezi kuzidi salio lililopo (' . $this->formatStorageNumber($onHand) . ').';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->withErrors(['quantity' => $message]);
        }

        try {
            DB::transaction(function () use ($user, $branchId, $balance, $validated, $withdrawQty, $onHand) {
                $withdrawal = PermanentStorageWithdrawal::create([
                    'company_id' => $user->company_id,
                    'branch_id' => $branchId,
                    'customer_id' => $balance->customer_id,
                    'inventory_item_id' => $balance->inventory_item_id,
                    'quantity' => $withdrawQty,
                    'reason' => $validated['reason'],
                    'notes' => $validated['notes'] ?? null,
                    'withdrawn_date' => now()->toDateString(),
                    'created_by' => $user->id,
                ]);

                if ($validated['reason'] === 'kuuza' && Schema::hasTable('permanent_storage_sales')) {
                    $price = (float) $validated['price'];
                    $total = round($withdrawQty * $price, 2);

                    PermanentStorageSale::create([
                        'company_id' => $user->company_id,
                        'branch_id' => $branchId,
                        'customer_id' => $balance->customer_id,
                        'inventory_item_id' => $balance->inventory_item_id,
                        'quantity' => $withdrawQty,
                        'price' => $price,
                        'total' => $total,
                        'withdrawal_id' => $withdrawal->id,
                        'created_by' => $user->id,
                    ]);
                }

                $balance->quantity_on_hand = $onHand - $withdrawQty;
                $balance->save();
            });
        } catch (\Throwable $e) {
            \Log::error('Permanent storage withdraw failed: ' . $e->getMessage(), [
                'balance_id' => $validated['balance_id'],
                'branch_id' => $branchId,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Imeshindikana kutoa zao. Jaribu tena.',
                ], 500);
            }

            return back()->withErrors(['quantity' => 'Imeshindikana kutoa zao. Jaribu tena.']);
        }

        $customerName = $balance->customer->name ?? 'mteja';
        $message = 'Zao la ' . $customerName . ' limetolewa kikamilifu (idadi: ' . $this->formatStorageNumber($withdrawQty) . ').';

        if ($validated['reason'] === 'kuuza') {
            $total = round($withdrawQty * (float) $validated['price'], 2);
            $message = 'Mauzo ya zao la ' . $customerName . ' yamerekodiwa (idadi: '
                . $this->formatStorageNumber($withdrawQty) . ', jumla: ' . number_format($total, 2) . ').';
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()
            ->to(route('inventory.permanent-storage.index'))
            ->with('success', $message);
    }

    public function storeMapato(Request $request)
    {
        return $this->storeFinanceEntry($request, 'mapato');
    }

    public function storeGharama(Request $request)
    {
        return $this->storeFinanceEntry($request, 'gharama');
    }

    public function storeMalipo(Request $request)
    {
        return $this->storeFinanceEntry($request, 'malipo');
    }

    public function taarifa(Request $request)
    {
        $this->ensureInventorySession();

        $user = Auth::user();
        $branchId = $this->currentBranchId();
        $companyId = (int) $user->company_id;

        $validated = $request->validate([
            'balance_id' => 'required|integer',
        ]);

        $balance = PermanentStorageBalance::query()
            ->with(['customer', 'item'])
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereKey($validated['balance_id'])
            ->firstOrFail();

        $customerId = (int) $balance->customer_id;
        $itemId = (int) $balance->inventory_item_id;

        $receipts = Schema::hasTable('permanent_storage_receipts')
            ? PermanentStorageReceipt::query()
                ->where('company_id', $companyId)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('customer_id', $customerId)
                ->where('inventory_item_id', $itemId)
                ->orderBy('received_date')
                ->orderBy('id')
                ->get()
            : collect();

        $withdrawals = Schema::hasTable('permanent_storage_withdrawals')
            ? PermanentStorageWithdrawal::query()
                ->where('company_id', $companyId)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('customer_id', $customerId)
                ->where('inventory_item_id', $itemId)
                ->orderBy('withdrawn_date')
                ->orderBy('id')
                ->get()
            : collect();

        $stockLines = collect();
        foreach ($receipts as $row) {
            $stockLines->push([
                'date' => $row->received_date,
                'type' => 'in',
                'type_label' => 'Uletaji',
                'description' => $row->notes ?: 'Pokea zao',
                'quantity' => (float) $row->quantity,
                'sort' => $row->received_date->format('Y-m-d').'-A-'.$row->id,
            ]);
        }
        foreach ($withdrawals as $row) {
            $reason = PermanentStorageWithdrawal::reasonOptions()[$row->reason] ?? $row->reason;
            $stockLines->push([
                'date' => $row->withdrawn_date,
                'type' => 'out',
                'type_label' => 'Utoaji',
                'description' => $reason.($row->notes ? ' — '.$row->notes : ''),
                'quantity' => (float) $row->quantity,
                'sort' => $row->withdrawn_date->format('Y-m-d').'-B-'.$row->id,
            ]);
        }
        $stockLines = $stockLines->sortBy('sort')->values();

        $mapatoEntries = Schema::hasTable('permanent_storage_mapato')
            ? PermanentStorageMapato::query()
                ->where('company_id', $companyId)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('customer_id', $customerId)
                ->where('inventory_item_id', $itemId)
                ->orderBy('entry_date')
                ->orderBy('id')
                ->get()
            : collect();

        $salesEntries = Schema::hasTable('permanent_storage_sales')
            ? PermanentStorageSale::query()
                ->where('company_id', $companyId)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('customer_id', $customerId)
                ->where('inventory_item_id', $itemId)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get()
            : collect();

        $mapatoLines = collect();
        foreach ($mapatoEntries as $row) {
            $mapatoLines->push([
                'id' => (int) $row->id,
                'date' => $row->entry_date,
                'sababu' => $row->sababu,
                'kiasi' => (float) $row->kiasi,
                'source' => 'mapato',
            ]);
        }
        foreach ($salesEntries as $row) {
            $saleDate = $row->created_at
                ? \Carbon\Carbon::parse($row->created_at)->toDateString()
                : now()->toDateString();

            $mapatoLines->push([
                'id' => (int) $row->id,
                'date' => $saleDate,
                'sababu' => 'Mauzo ya zao (idadi '.$this->formatStorageNumber((float) $row->quantity).')',
                'kiasi' => (float) $row->total,
                'source' => 'mauzo',
            ]);
        }
        $mapatoLines = $mapatoLines->sortBy('date')->values();

        $gharamaLines = Schema::hasTable('permanent_storage_gharama')
            ? PermanentStorageGharama::query()
                ->where('company_id', $companyId)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('customer_id', $customerId)
                ->where('inventory_item_id', $itemId)
                ->orderBy('entry_date')
                ->orderBy('id')
                ->get()
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'date' => $row->entry_date,
                    'sababu' => $row->sababu,
                    'kiasi' => (float) $row->kiasi,
                    'source' => 'gharama',
                ])
                ->values()
            : collect();

        $malipoLines = Schema::hasTable('permanent_storage_malipo')
            ? PermanentStorageMalipo::query()
                ->where('company_id', $companyId)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('customer_id', $customerId)
                ->where('inventory_item_id', $itemId)
                ->orderBy('entry_date')
                ->orderBy('id')
                ->get()
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'date' => $row->entry_date,
                    'sababu' => $row->sababu,
                    'kiasi' => (float) $row->kiasi,
                    'source' => 'malipo',
                ])
                ->values()
            : collect();

        $mapatoTotal = (float) $mapatoLines->sum('kiasi');
        $gharamaTotal = (float) $gharamaLines->sum('kiasi');
        $malipoTotal = (float) $malipoLines->sum('kiasi');
        $fedhaBalance = round($mapatoTotal - $gharamaTotal - $malipoTotal, 2);

        return view('inventory.permanent-storage.taarifa', [
            'balance' => $balance,
            'customer' => $balance->customer,
            'item' => $balance->item,
            'stock_quantity' => (float) $balance->quantity_on_hand,
            'stock_quantity_display' => $this->formatQuantityWithUnit((float) $balance->quantity_on_hand, $balance->item),
            'stock_package_display' => $this->formatPackageCount((float) $balance->quantity_on_hand, $balance->item),
            'stockLines' => $stockLines,
            'mapatoLines' => $mapatoLines,
            'gharamaLines' => $gharamaLines,
            'malipoLines' => $malipoLines,
            'mapatoTotal' => $mapatoTotal,
            'gharamaTotal' => $gharamaTotal,
            'malipoTotal' => $malipoTotal,
            'fedhaBalance' => $fedhaBalance,
            'can_delete' => user_can_delete_permanent_storage_taarifa(),
        ]);
    }

    public function destroyTaarifaLine(Request $request)
    {
        abort_unless(user_can_delete_permanent_storage_taarifa(), 403);

        $this->ensureInventorySession();

        $user = Auth::user();
        $branchId = $this->currentBranchId();
        $companyId = (int) $user->company_id;

        $validated = $request->validate([
            'balance_id' => 'required|integer',
            'source' => 'required|in:mapato,mauzo,gharama,malipo',
            'line_id' => 'required|integer',
        ]);

        $balance = PermanentStorageBalance::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereKey($validated['balance_id'])
            ->firstOrFail();

        $customerId = (int) $balance->customer_id;
        $itemId = (int) $balance->inventory_item_id;
        $source = $validated['source'];
        $lineId = (int) $validated['line_id'];

        $query = match ($source) {
            'mapato' => PermanentStorageMapato::query(),
            'mauzo' => PermanentStorageSale::query(),
            'gharama' => PermanentStorageGharama::query(),
            'malipo' => PermanentStorageMalipo::query(),
        };

        $line = $query
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('customer_id', $customerId)
            ->where('inventory_item_id', $itemId)
            ->whereKey($lineId)
            ->firstOrFail();

        $line->delete();

        $labels = [
            'mapato' => 'Mapato',
            'mauzo' => 'Mauzo',
            'gharama' => 'Gharama',
            'malipo' => 'Malipo',
        ];

        return response()->json([
            'success' => true,
            'message' => ($labels[$source] ?? 'Rekodi').' imefutwa.',
        ]);
    }

    public function report()
    {
        $this->ensureInventorySession();

        $data = $this->buildCustomerReportData(
            (int) Auth::user()->company_id,
            $this->currentBranchId()
        );

        return view('inventory.permanent-storage.report', $data);
    }

    public function exportReportPdf()
    {
        $this->ensureInventorySession();

        $user = Auth::user();
        $data = $this->buildCustomerReportData(
            (int) $user->company_id,
            $this->currentBranchId()
        );

        $data['company'] = function_exists('current_company')
            ? current_company()
            : $user->company;
        $data['generatedAt'] = now();

        $filename = 'Ripoti_Stoo_ya_Kudumu_'.now()->format('Y-m-d_H-i-s').'.pdf';

        $pdf = Pdf::loadView('inventory.permanent-storage.report-pdf', $data)
            ->setPaper('A4', 'portrait');

        return $pdf->download($filename);
    }

    /**
     * @return array{
     *   customers: \Illuminate\Support\Collection,
     *   stock_summary: array,
     *   total_quantity: float,
     *   total_quantity_display: string,
     *   total_salio: float,
     *   total_mapato: float,
     *   total_gharama: float,
     *   total_malipo: float
     * }
     */
    private function buildCustomerReportData(int $companyId, ?int $branchId): array
    {
        $stockSummary = $this->buildBalanceSummary($companyId, $branchId);
        $totalQuantity = (float) collect($stockSummary)->sum('total_quantity');
        $totalQuantityDisplay = collect($stockSummary)
            ->pluck('summary_display')
            ->filter()
            ->implode(', ');

        if ($totalQuantityDisplay === '') {
            $totalQuantityDisplay = $this->formatStorageNumber($totalQuantity);
        }

        $balances = Schema::hasTable('permanent_storage_balances')
            ? PermanentStorageBalance::query()
                ->with(['customer', 'item'])
                ->where('company_id', $companyId)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->get()
            : collect();

        $mapatoByCustomer = $this->sumFinanceByCustomer('permanent_storage_mapato', 'kiasi', $companyId, $branchId);
        $salesByCustomer = $this->sumFinanceByCustomer('permanent_storage_sales', 'total', $companyId, $branchId);
        $gharamaByCustomer = $this->sumFinanceByCustomer('permanent_storage_gharama', 'kiasi', $companyId, $branchId);
        $malipoByCustomer = $this->sumFinanceByCustomer('permanent_storage_malipo', 'kiasi', $companyId, $branchId);

        $customerIds = collect()
            ->merge($balances->pluck('customer_id'))
            ->merge(array_keys($mapatoByCustomer))
            ->merge(array_keys($salesByCustomer))
            ->merge(array_keys($gharamaByCustomer))
            ->merge(array_keys($malipoByCustomer))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->filter()
            ->values();

        $customersById = Customer::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $customerIds)
            ->get(['id', 'name', 'phone'])
            ->keyBy('id');

        $customers = $customerIds->map(function (int $customerId) use (
            $balances,
            $customersById,
            $mapatoByCustomer,
            $salesByCustomer,
            $gharamaByCustomer,
            $malipoByCustomer
        ) {
            $customer = $customersById->get($customerId);
            $rows = $balances->where('customer_id', $customerId);
            $quantity = (float) $rows->sum('quantity_on_hand');

            $mapato = (float) ($mapatoByCustomer[$customerId] ?? 0)
                + (float) ($salesByCustomer[$customerId] ?? 0);
            $gharama = (float) ($gharamaByCustomer[$customerId] ?? 0);
            $malipo = (float) ($malipoByCustomer[$customerId] ?? 0);
            $salio = round($mapato - $gharama - $malipo, 2);

            $quantityParts = $rows
                ->groupBy('inventory_item_id')
                ->map(function ($itemRows) {
                    $item = $itemRows->first()->item;
                    $qty = (float) $itemRows->sum('quantity_on_hand');
                    if ($qty <= 0) {
                        return null;
                    }

                    $quantityDisplay = $this->formatQuantityWithUnit($qty, $item);
                    $packageDisplay = $this->formatPackageCount($qty, $item);

                    if ($packageDisplay !== '—') {
                        return $packageDisplay.' ('.$quantityDisplay.')';
                    }

                    return $quantityDisplay;
                })
                ->filter()
                ->values();

            $quantityDisplay = $quantityParts->isNotEmpty()
                ? $quantityParts->implode(', ')
                : $this->formatStorageNumber($quantity);

            return [
                'customer_id' => $customerId,
                'name' => $customer->name ?? '—',
                'phone' => $customer->phone ?? '',
                'quantity' => $quantity,
                'quantity_display' => $quantityDisplay,
                'mapato' => $mapato,
                'gharama' => $gharama,
                'malipo' => $malipo,
                'salio' => $salio,
            ];
        })
            ->filter(fn ($row) => $row['quantity'] > 0 || abs($row['salio']) > 0.00001)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $totalMapato = (float) $customers->sum('mapato');
        $totalGharama = (float) $customers->sum('gharama');
        $totalMalipo = (float) $customers->sum('malipo');
        $totalSalio = round($totalMapato - $totalGharama - $totalMalipo, 2);

        return [
            'customers' => $customers,
            'stock_summary' => $stockSummary,
            'total_quantity' => $totalQuantity,
            'total_quantity_display' => $totalQuantityDisplay,
            'total_salio' => $totalSalio,
            'total_mapato' => $totalMapato,
            'total_gharama' => $totalGharama,
            'total_malipo' => $totalMalipo,
        ];
    }

    /**
     * @return array<int, float>
     */
    private function sumFinanceByCustomer(string $table, string $amountColumn, int $companyId, ?int $branchId): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $allowed = ['kiasi', 'total'];
        if (! in_array($amountColumn, $allowed, true)) {
            return [];
        }

        return DB::table($table)
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->groupBy('customer_id')
            ->selectRaw("customer_id, SUM({$amountColumn}) as total")
            ->pluck('total', 'customer_id')
            ->map(fn ($value) => (float) $value)
            ->all();
    }

    /**
     * @param  'mapato'|'gharama'|'malipo'  $type
     */
    private function storeFinanceEntry(Request $request, string $type)
    {
        $user = Auth::user();
        $branchId = $this->currentBranchId();
        $companyId = (int) $user->company_id;

        if (! $branchId) {
            return response()->json([
                'success' => false,
                'message' => 'Tawi halijachaguliwa. Chagua tawi kisha jaribu tena.',
            ], 422);
        }

        $table = match ($type) {
            'mapato' => 'permanent_storage_mapato',
            'gharama' => 'permanent_storage_gharama',
            'malipo' => 'permanent_storage_malipo',
            default => null,
        };

        if (! $table || ! Schema::hasTable($table)) {
            return response()->json([
                'success' => false,
                'message' => 'Jedwali la '.$type.' halijasanidiwa. Endesha migrations.',
            ], 422);
        }

        $validated = $request->validate([
            'balance_id' => 'required|integer',
            'sababu' => 'required|string|max:500',
            'kiasi' => 'required|numeric|min:0.01',
            'entry_date' => 'required|date',
        ], [
            'balance_id.required' => 'Salio halijapatikana.',
            'sababu.required' => 'Sababu inahitajika.',
            'kiasi.required' => 'Kiasi kinahitajika.',
            'kiasi.min' => 'Kiasi kiwe zaidi ya sifuri.',
            'entry_date.required' => 'Tarehe inahitajika.',
        ]);

        $balance = PermanentStorageBalance::query()
            ->with('customer')
            ->where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->whereKey($validated['balance_id'])
            ->first();

        if (! $balance) {
            return response()->json([
                'success' => false,
                'message' => 'Salio halipatikani katika tawi hili.',
            ], 422);
        }

        $payload = [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'customer_id' => $balance->customer_id,
            'inventory_item_id' => $balance->inventory_item_id,
            'sababu' => trim($validated['sababu']),
            'kiasi' => round((float) $validated['kiasi'], 2),
            'entry_date' => $validated['entry_date'],
            'created_by' => $user->id,
        ];

        try {
            match ($type) {
                'mapato' => PermanentStorageMapato::create($payload),
                'gharama' => PermanentStorageGharama::create($payload),
                'malipo' => PermanentStorageMalipo::create($payload),
            };
        } catch (\Throwable $e) {
            \Log::error('Permanent storage '.$type.' store failed: '.$e->getMessage(), [
                'balance_id' => $validated['balance_id'],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Imeshindikana kuhifadhi. Jaribu tena.',
            ], 500);
        }

        if (in_array($type, ['gharama', 'malipo'], true) && $balance->customer) {
            $sms = app(PermanentStorageCustomerSmsService::class);
            if ($type === 'gharama') {
                $sms->sendGharama($balance->customer, $payload['sababu'], $payload['kiasi'], $payload['entry_date']);
            } else {
                $sms->sendMalipo($balance->customer, $payload['sababu'], $payload['kiasi'], $payload['entry_date']);
            }
        }

        $labels = [
            'mapato' => 'Mapato yamehifadhiwa.',
            'gharama' => 'Gharama imehifadhiwa.',
            'malipo' => 'Malipo yamehifadhiwa.',
        ];

        return response()->json([
            'success' => true,
            'message' => $labels[$type] ?? 'Imehifadhiwa.',
        ]);
    }

    public function history(Request $request)
    {
        $user = Auth::user();
        $branchId = $this->currentBranchId();

        if ($request->has('draw')) {
            return $this->historyDatatable($request);
        }

        $customerId = $request->query('customer_id');
        $customer = null;
        if ($customerId) {
            $customer = Customer::where('company_id', $user->company_id)
                ->where('branch_id', $branchId)
                ->find($customerId);
        }

        $customers = $this->customersForCurrentBranch()
            ->get(['id', 'name']);

        return view('inventory.permanent-storage.history', compact('customers', 'customer', 'customerId'));
    }

    protected function balancesDatatable(Request $request)
    {
        $user = Auth::user();
        $branchId = $this->currentBranchId();
        $listStatus = $request->query('status') === PermanentStorageBalance::STATUS_INACTIVE
            ? PermanentStorageBalance::STATUS_INACTIVE
            : PermanentStorageBalance::STATUS_ACTIVE;
        $showingInactive = $listStatus === PermanentStorageBalance::STATUS_INACTIVE;

        $query = PermanentStorageBalance::query()
            ->with(['customer', 'item'])
            ->where('company_id', $user->company_id)
            ->where('branch_id', $branchId)
            ->where('status', $listStatus);

        if (! $showingInactive) {
            $query->where('quantity_on_hand', '>', 0);
        }

        return DataTables::of($query)
            ->addColumn('customer_name', fn ($row) => $row->customer->name ?? '—')
            ->addColumn('customer_phone', fn ($row) => $row->customer->phone ?? '—')
            ->addColumn('item_name', fn ($row) => $row->item->name ?? '—')
            ->addColumn('item_code', fn ($row) => $row->item->code ?? '—')
            ->addColumn('status_label', fn ($row) => e($row->statusLabel()))
            ->addColumn('quantity_display', fn ($row) => $this->formatQuantityWithUnit((float) $row->quantity_on_hand, $row->item))
            ->addColumn('package_display', fn ($row) => $this->formatPackageCount((float) $row->quantity_on_hand, $row->item))
            ->filterColumn('customer_name', function ($query, $keyword) {
                $query->whereHas('customer', fn ($q) => $q->where('name', 'like', '%' . $keyword . '%'));
            })
            ->filterColumn('item_name', function ($query, $keyword) {
                $query->whereHas('item', fn ($q) => $q->where('name', 'like', '%' . $keyword . '%'));
            })
            ->addColumn('actions', function ($row) use ($showingInactive) {
                $taarifaUrl = route('inventory.permanent-storage.taarifa', ['balance_id' => $row->id]);
                $customerName = e($row->customer->name ?? '—');
                $itemName = e($row->item->name ?? '—');
                $onHand = (float) $row->quantity_on_hand;
                $unit = e($row->item->unit_of_measure ?? '');
                $balanceId = (int) $row->id;

                $html = '<div class="d-flex flex-wrap gap-1 justify-content-center">';

                if ($showingInactive) {
                    $html .= '<button type="button" class="btn btn-sm btn-success btn-change-storage-status"'
                        . ' data-balance-id="' . $balanceId . '"'
                        . ' data-status="active"'
                        . ' title="Rudisha Inaendelea">'
                        . '<i class="bx bx-revision me-1"></i> Inaendelea</button>';
                    $html .= '<a href="' . e($taarifaUrl) . '" class="btn btn-sm btn-outline-dark" title="Taarifa">'
                        . '<i class="bx bx-file me-1"></i> Taarifa</a>';
                } else {
                    $html .= '<button type="button" class="btn btn-sm btn-outline-warning btn-withdraw-permanent-storage"'
                        . ' data-balance-id="' . $balanceId . '"'
                        . ' data-customer-name="' . $customerName . '"'
                        . ' data-item-name="' . $itemName . '"'
                        . ' data-quantity-on-hand="' . $onHand . '"'
                        . ' data-unit="' . $unit . '"'
                        . ' title="Toa Zao">'
                        . '<i class="bx bx-export me-1"></i> Toa</button>';

                    $html .= '<button type="button" class="btn btn-sm btn-outline-success btn-permanent-mapato"'
                        . ' data-balance-id="' . $balanceId . '"'
                        . ' data-customer-name="' . $customerName . '"'
                        . ' data-item-name="' . $itemName . '"'
                        . ' title="Ingiza Mapato">'
                        . '<i class="bx bx-wallet me-1"></i> Mapato</button>';

                    $html .= '<button type="button" class="btn btn-sm btn-outline-danger btn-permanent-gharama"'
                        . ' data-balance-id="' . $balanceId . '"'
                        . ' data-customer-name="' . $customerName . '"'
                        . ' data-item-name="' . $itemName . '"'
                        . ' title="Ingiza Gharama">'
                        . '<i class="bx bx-receipt me-1"></i> Gharama</button>';

                    $html .= '<button type="button" class="btn btn-sm btn-outline-primary btn-permanent-malipo"'
                        . ' data-balance-id="' . $balanceId . '"'
                        . ' data-customer-name="' . $customerName . '"'
                        . ' data-item-name="' . $itemName . '"'
                        . ' title="Ingiza Malipo">'
                        . '<i class="bx bx-money me-1"></i> Malipo</button>';

                    $html .= '<a href="' . e($taarifaUrl) . '" class="btn btn-sm btn-outline-dark" title="Taarifa">'
                        . '<i class="bx bx-file me-1"></i> Taarifa</a>';

                    $html .= '<button type="button" class="btn btn-sm btn-outline-secondary btn-change-storage-status"'
                        . ' data-balance-id="' . $balanceId . '"'
                        . ' data-status="inactive"'
                        . ' title="Weka Imeisha">'
                        . '<i class="bx bx-check-circle me-1"></i> Imeisha</button>';
                }

                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['actions', 'status_label'])
            ->make(true);
    }

    public function updateStatus(Request $request)
    {
        $this->ensureInventorySession();

        $user = Auth::user();
        $branchId = $this->currentBranchId();
        $companyId = (int) $user->company_id;

        $validated = $request->validate([
            'balance_id' => 'required|integer',
            'status' => 'required|in:active,inactive',
        ]);

        $balance = PermanentStorageBalance::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereKey($validated['balance_id'])
            ->firstOrFail();

        $balance->status = $validated['status'];
        $balance->save();

        $label = PermanentStorageBalance::statusOptions()[$balance->status] ?? $balance->status;

        return response()->json([
            'success' => true,
            'message' => 'Hali imebadilishwa kuwa: '.$label.'.',
            'status' => $balance->status,
            'status_label' => $label,
        ]);
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @return array{active: int, inactive: int}
     */
    private function balanceStatusCounts(int $companyId, ?int $branchId, string $model): array
    {
        if (! Schema::hasTable((new $model)->getTable())) {
            return ['active' => 0, 'inactive' => 0];
        }

        $base = $model::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        return [
            'active' => (int) (clone $base)->where('status', 'active')->where('quantity_on_hand', '>', 0)->count(),
            'inactive' => (int) (clone $base)->where('status', 'inactive')->count(),
        ];
    }

    protected function historyDatatable(Request $request)
    {
        $user = Auth::user();
        $branchId = $this->currentBranchId();
        $companyId = $user->company_id;
        $customerId = $request->input('customer_id');

        $receipts = DB::table('permanent_storage_receipts as r')
            ->join('customers as c', 'c.id', '=', 'r.customer_id')
            ->join('inventory_items as i', 'i.id', '=', 'r.inventory_item_id')
            ->leftJoin('users as u', 'u.id', '=', 'r.created_by')
            ->where('r.company_id', $companyId)
            ->where('r.branch_id', $branchId)
            ->when($customerId, fn ($q) => $q->where('r.customer_id', $customerId))
            ->select([
                DB::raw("'in' as movement_type"),
                'r.received_date as transaction_date',
                'c.name as customer_name',
                'i.name as item_name',
                'i.unit_of_measure',
                'i.package_name',
                'i.package_quantity',
                'r.quantity',
                DB::raw('NULL as reason'),
                'r.notes',
                'u.name as recorded_by',
                'r.created_at',
            ]);

        $union = $receipts;

        if (Schema::hasTable('permanent_storage_withdrawals')) {
            $withdrawals = DB::table('permanent_storage_withdrawals as w')
                ->join('customers as c', 'c.id', '=', 'w.customer_id')
                ->join('inventory_items as i', 'i.id', '=', 'w.inventory_item_id')
                ->leftJoin('users as u', 'u.id', '=', 'w.created_by')
                ->where('w.company_id', $companyId)
                ->where('w.branch_id', $branchId)
                ->when($customerId, fn ($q) => $q->where('w.customer_id', $customerId))
                ->select([
                    DB::raw("'out' as movement_type"),
                    'w.withdrawn_date as transaction_date',
                    'c.name as customer_name',
                    'i.name as item_name',
                    'i.unit_of_measure',
                    'i.package_name',
                    'i.package_quantity',
                    'w.quantity',
                    'w.reason',
                    'w.notes',
                    'u.name as recorded_by',
                    'w.created_at',
                ]);

            $union = $receipts->unionAll($withdrawals);
        }

        $query = DB::query()->fromSub($union, 'storage_history');

        return DataTables::of($query)
            ->addColumn('type_badge', function ($row) {
                if ($row->movement_type === 'out') {
                    return '<span class="badge bg-danger">Utoaji</span>';
                }

                return '<span class="badge bg-success">Uletaji</span>';
            })
            ->editColumn('transaction_date', fn ($row) => $row->transaction_date
                ? \Carbon\Carbon::parse($row->transaction_date)->format('d/m/Y')
                : '—')
            ->addColumn('quantity_display', function ($row) {
                $item = new Item([
                    'unit_of_measure' => $row->unit_of_measure,
                    'package_name' => $row->package_name,
                    'package_quantity' => $row->package_quantity,
                ]);
                $display = $this->formatQuantityWithUnit((float) $row->quantity, $item);

                if ($row->movement_type === 'out') {
                    return '<span class="text-danger fw-semibold">-' . e($display) . '</span>';
                }

                return '<span class="text-success">+' . e($display) . '</span>';
            })
            ->addColumn('package_display', function ($row) {
                $item = new Item([
                    'package_name' => $row->package_name,
                    'package_quantity' => $row->package_quantity,
                ]);

                return $this->formatPackageCount((float) $row->quantity, $item);
            })
            ->addColumn('reason_display', function ($row) {
                if ($row->movement_type !== 'out' || ! $row->reason) {
                    return '—';
                }

                return e(PermanentStorageWithdrawal::reasonOptions()[$row->reason] ?? $row->reason);
            })
            ->editColumn('created_at', fn ($row) => $row->created_at
                ? \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i')
                : '—')
            ->setRowClass(function ($row) {
                return $row->movement_type === 'out' ? 'table-danger' : '';
            })
            ->rawColumns(['type_badge', 'quantity_display'])
            ->make(true);
    }

    protected function formatQuantityWithUnit(float $quantity, ?Item $item): string
    {
        $unit = $item?->unit_of_measure;
        $formattedQty = $this->formatStorageNumber($quantity);

        return $unit ? $formattedQty . ' ' . $unit : $formattedQty;
    }

    protected function formatPackageCount(float $quantity, ?Item $item): string
    {
        if (! $item) {
            return '—';
        }

        $packageQuantity = (float) ($item->package_quantity ?? 0);
        $packageName = trim((string) ($item->package_name ?? ''));

        if ($packageQuantity <= 0 || $packageName === '') {
            return '—';
        }

        $count = $quantity / $packageQuantity;

        return $this->formatStorageNumber($count) . ' ' . $packageName;
    }

    protected function formatStorageNumber(float $value): string
    {
        if (fmod($value, 1.0) === 0.0) {
            return number_format($value, 0, '.', ',');
        }

        return rtrim(rtrim(number_format($value, 2, '.', ','), '0'), '.');
    }
}
