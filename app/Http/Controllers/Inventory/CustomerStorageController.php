<?php

namespace App\Http\Controllers\Inventory;

use App\Helpers\SmsHelper;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Inventory\Category;
use App\Models\Inventory\CustomerStorageBalance;
use App\Models\Inventory\CustomerStorageReceipt;
use App\Models\Inventory\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class CustomerStorageController extends Controller
{
    private function currentBranchId(): int
    {
        $user = Auth::user();

        return (int) (session('branch_id') ?: $user->branch_id);
    }

    private function customersForCurrentBranch()
    {
        $user = Auth::user();
        $branchId = $this->currentBranchId();

        return Customer::where('company_id', $user->company_id)
            ->where('branch_id', $branchId)
            ->orderBy('name');
    }

    private function itemsForCurrentBranch()
    {
        return Item::forBranch($this->currentBranchId())
            ->active()
            ->orderBy('name');
    }

    public function index(Request $request)
    {
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
                'message' => 'Tawi halijachaguliwa. Chagua tawi kisha jaribu tena.',
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
                    ->unique()
                    ->values()
                    ->all();

                if ($branchIds === []) {
                    $branchIds = [$this->currentBranchId()];
                }

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

        return redirect()
            ->to(route('inventory.customer-storage.index'))
            ->with('success', 'Zao la mteja ' . $customer->name . ' limepokelewa kikamilifu (idadi: ' . (int) $validated['quantity'] . ').');
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
            ->addColumn('history_link', function ($row) {
                $url = route('inventory.customer-storage.history', ['customer_id' => $row->customer_id]);

                return '<a href="' . e($url) . '" class="btn btn-sm btn-outline-info" title="Historia ya uletaji">'
                    . '<i class="bx bx-history me-1"></i> Historia</a>';
            })
            ->rawColumns(['history_link'])
            ->make(true);
    }

    protected function historyDatatable(Request $request)
    {
        $user = Auth::user();
        $branchId = $this->currentBranchId();

        $query = CustomerStorageReceipt::query()
            ->with(['customer', 'item', 'createdByUser'])
            ->where('company_id', $user->company_id)
            ->where('branch_id', $branchId);

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        return DataTables::of($query)
            ->addColumn('customer_name', fn ($row) => $row->customer->name ?? '—')
            ->addColumn('item_name', fn ($row) => $row->item->name ?? '—')
            ->addColumn('item_code', fn ($row) => $row->item->code ?? '—')
            ->addColumn('quantity_display', fn ($row) => $this->formatQuantityWithUnit((float) $row->quantity, $row->item))
            ->addColumn('package_display', fn ($row) => $this->formatPackageCount((float) $row->quantity, $row->item))
            ->editColumn('received_date', fn ($row) => optional($row->received_date)->format('d/m/Y'))
            ->addColumn('recorded_by', fn ($row) => $row->createdByUser->name ?? '—')
            ->editColumn('created_at', fn ($row) => $row->created_at->format('d/m/Y H:i'))
            ->filterColumn('customer_name', function ($query, $keyword) {
                $query->whereHas('customer', fn ($q) => $q->where('name', 'like', '%' . $keyword . '%'));
            })
            ->filterColumn('item_name', function ($query, $keyword) {
                $query->whereHas('item', fn ($q) => $q->where('name', 'like', '%' . $keyword . '%'));
            })
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
