<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Purchase\CustomerAccountsReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;

class CustomerAccountsController extends Controller
{
    public function __construct(
        private readonly CustomerAccountsReportService $reportService
    ) {}

    public function index()
    {
        abort_unless(user_can_view_wamachinga_purchases(), 403);

        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id;

        $customers = Customer::query()
            ->where('company_id', $user->company_id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'customerNo']);

        return view('purchases.customer-accounts.index', compact('customers'));
    }

    public function show(Request $request, string $encodedCustomerId)
    {
        abort_unless(user_can_view_wamachinga_purchases(), 403);

        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
        ], [
            'entry_date.required' => 'Chagua tarehe.',
            'entry_date.date' => 'Tarehe si sahihi.',
        ]);

        $id = Hashids::decode($encodedCustomerId)[0] ?? null;

        if (! $id) {
            abort(404);
        }

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $customer = Customer::query()
            ->where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->findOrFail($id);

        $report = $this->reportService->build(
            $customer,
            $companyId,
            $branchId ? (int) $branchId : null,
            $validated['entry_date']
        );

        return view('purchases.customer-accounts.show', array_merge($report, [
            'customer' => $customer,
            'encodedCustomerId' => $encodedCustomerId,
            'entry_date' => $validated['entry_date'],
        ]));
    }
}
