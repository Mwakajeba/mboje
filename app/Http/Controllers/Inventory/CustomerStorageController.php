<?php

namespace App\Http\Controllers\Inventory;

use App\Helpers\SmsHelper;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Inventory\Category;
use App\Models\Inventory\CustomerStorageBalance;
use App\Models\Inventory\CustomerStorageReceipt;
use App\Models\Inventory\CustomerStorageSale;
use App\Models\Inventory\CustomerStorageWithdrawal;
use App\Models\Inventory\Item;
use App\Models\InventoryLocation;
use App\Services\Inventory\CustomerStorageNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class CustomerStorageController extends Controller
{
    public function __construct(
        private readonly CustomerStorageNotificationService $notificationService
    ) {}

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

        return view('inventory.customer-storage.index', compact(
            'customers',
            'items',
            'unitOptions',
            'categories',
            'assignableBranches',
            'branchId'
        ));
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

        if ($request->boolean('send_welcome_sms')) {
            try {
                $this->sendCustomerWelcomeSms($customer);
            } catch (\Exception $e) {
                \Log::error('Failed to send welcome SMS (customer storage quick add): ' . $e->getMessage());
            }
        }

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

        if (! Schema::hasTable('customer_storage_receipts') || ! Schema::hasTable('customer_storage_balances')) {
            return back()
                ->withInput()
                ->withErrors(['quantity' => 'Jedwali la uhifadhi wa wateja halijasanidiwa. Wasiliana na msimamizi wa mfumo kuendesha migrations.']);
        }

        try {
            DB::transaction(function () use ($user, $branchId, $customer, $item, $validated) {
                CustomerStorageReceipt::create([
                    'company_id' => $user->company_id,
                    'branch_id' => $branchId,
                    'customer_id' => $customer->id,
                    'inventory_item_id' => $item->id,
                    'quantity' => $validated['quantity'],
                    'received_date' => $validated['received_date'],
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $user->id,
                ]);

                $balance = CustomerStorageBalance::firstOrNew([
                    'company_id' => $user->company_id,
                    'branch_id' => $branchId,
                    'customer_id' => $customer->id,
                    'inventory_item_id' => $item->id,
                ]);

                $balance->quantity_on_hand = (float) ($balance->quantity_on_hand ?? 0) + (float) $validated['quantity'];
                $balance->save();
            });
        } catch (\Throwable $e) {
            \Log::error('Customer storage store failed: ' . $e->getMessage(), [
                'customer_id' => $validated['customer_id'],
                'inventory_item_id' => $validated['inventory_item_id'],
                'branch_id' => $branchId,
            ]);

            return back()
                ->withInput()
                ->withErrors(['quantity' => 'Imeshindikana kuhifadhi zao. Jaribu tena au wasiliana na msimamizi wa mfumo.']);
        }

        $newBalance = (float) CustomerStorageBalance::query()
            ->where('company_id', $user->company_id)
            ->where('branch_id', $branchId)
            ->where('customer_id', $customer->id)
            ->where('inventory_item_id', $item->id)
            ->value('quantity_on_hand');

        $this->notificationService->sendReceiptStored(
            (int) $user->company_id,
            $customer->name,
            $item->name,
            (float) $validated['quantity'],
            $validated['received_date'],
            $newBalance,
            $validated['notes'] ?? null,
            $user->name
        );

        return redirect()
            ->to(route('inventory.customer-storage.index'))
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
            'reason' => ['required', Rule::in(array_keys(CustomerStorageWithdrawal::reasonOptions()))],
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

        if (! Schema::hasTable('customer_storage_withdrawals')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jedwali la utoaji wa zao halijasanidiwa. Endesha migrations.',
                ], 422);
            }

            return back()->withErrors(['quantity' => 'Jedwali la utoaji wa zao halijasanidiwa. Endesha migrations.']);
        }

        $balance = CustomerStorageBalance::query()
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

        if ($validated['reason'] === 'kuuza' && ! Schema::hasTable('customer_storage_sales')) {
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
                $withdrawal = CustomerStorageWithdrawal::create([
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

                if ($validated['reason'] === 'kuuza' && Schema::hasTable('customer_storage_sales')) {
                    $price = (float) $validated['price'];
                    $total = round($withdrawQty * $price, 2);

                    CustomerStorageSale::create([
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
            \Log::error('Customer storage withdraw failed: ' . $e->getMessage(), [
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

        $newBalance = $onHand - $withdrawQty;
        $withdrawPrice = $validated['reason'] === 'kuuza' ? (float) $validated['price'] : null;

        $this->notificationService->sendWithdrawal(
            (int) $user->company_id,
            $balance->customer->name ?? 'mteja',
            $balance->item->name ?? 'zao',
            $withdrawQty,
            $validated['reason'],
            $withdrawPrice,
            $newBalance,
            $validated['notes'] ?? null,
            $user->name
        );

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
            ->to(route('inventory.customer-storage.index'))
            ->with('success', $message);
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

        return view('inventory.customer-storage.history', compact('customers', 'customer', 'customerId'));
    }

    protected function balancesDatatable(Request $request)
    {
        $user = Auth::user();
        $branchId = $this->currentBranchId();

        $query = CustomerStorageBalance::query()
            ->with(['customer', 'item'])
            ->where('company_id', $user->company_id)
            ->where('branch_id', $branchId)
            ->where('quantity_on_hand', '>', 0);

        return DataTables::of($query)
            ->addColumn('customer_name', fn ($row) => $row->customer->name ?? '—')
            ->addColumn('customer_phone', fn ($row) => $row->customer->phone ?? '—')
            ->addColumn('item_name', fn ($row) => $row->item->name ?? '—')
            ->addColumn('item_code', fn ($row) => $row->item->code ?? '—')
            ->addColumn('quantity_display', fn ($row) => $this->formatQuantityWithUnit((float) $row->quantity_on_hand, $row->item))
            ->addColumn('package_display', fn ($row) => $this->formatPackageCount((float) $row->quantity_on_hand, $row->item))
            ->filterColumn('customer_name', function ($query, $keyword) {
                $query->whereHas('customer', fn ($q) => $q->where('name', 'like', '%' . $keyword . '%'));
            })
            ->filterColumn('item_name', function ($query, $keyword) {
                $query->whereHas('item', fn ($q) => $q->where('name', 'like', '%' . $keyword . '%'));
            })
            ->addColumn('actions', function ($row) {
                $historyUrl = route('inventory.customer-storage.history', ['customer_id' => $row->customer_id]);
                $customerName = e($row->customer->name ?? '—');
                $itemName = e($row->item->name ?? '—');
                $onHand = (float) $row->quantity_on_hand;
                $unit = e($row->item->unit_of_measure ?? '');

                $html = '<div class="d-flex flex-wrap gap-1 justify-content-center">';
                $html .= '<button type="button" class="btn btn-sm btn-outline-warning btn-withdraw-storage"'
                    . ' data-balance-id="' . (int) $row->id . '"'
                    . ' data-customer-name="' . $customerName . '"'
                    . ' data-item-name="' . $itemName . '"'
                    . ' data-quantity-on-hand="' . $onHand . '"'
                    . ' data-unit="' . $unit . '"'
                    . ' title="Toa Zao">'
                    . '<i class="bx bx-export me-1"></i> Toa</button>';
                $html .= '<a href="' . e($historyUrl) . '" class="btn btn-sm btn-outline-info" title="Historia">'
                    . '<i class="bx bx-history"></i></a>';
                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    protected function historyDatatable(Request $request)
    {
        $user = Auth::user();
        $branchId = $this->currentBranchId();
        $companyId = $user->company_id;
        $customerId = $request->input('customer_id');

        $receipts = DB::table('customer_storage_receipts as r')
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

        if (Schema::hasTable('customer_storage_withdrawals')) {
            $withdrawals = DB::table('customer_storage_withdrawals as w')
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

                return e(CustomerStorageWithdrawal::reasonOptions()[$row->reason] ?? $row->reason);
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

    protected function sendCustomerWelcomeSms(Customer $customer): void
    {
        if (! SmsHelper::isConfigured()) {
            throw new \Exception('SMS haijasanidiwa.');
        }

        $formattedPhone = function_exists('normalize_phone_number')
            ? normalize_phone_number($customer->phone)
            : $customer->phone;

        $message = "Karibu {$customer->name} katika Gala letu ya MBOJI MILLIS";

        $result = SmsHelper::send($formattedPhone, $message);

        if (! ($result['success'] ?? false)) {
            throw new \Exception($result['error'] ?? 'Imeshindikana kutuma SMS ya kukaribisha.');
        }
    }

}
