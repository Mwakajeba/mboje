<?php

namespace App\Http\Controllers\Api\Machinga;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Purchase\DailyAccountsController;
use App\Services\Purchase\DailyAccountsReportService;
use App\Services\Purchase\DailyMauzoEmployeeListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MachingaDailyAccountsController extends Controller
{
    public function __construct(
        private readonly DailyMauzoEmployeeListService $mauzoEmployeeList,
        private readonly DailyAccountsReportService $dailyAccountsReport
    ) {}

    public function hub(Request $request): JsonResponse
    {
        abort_unless(user_can_view_wamachinga_purchases($request->user()), 403);

        $user = $request->user();
        $canEnter = user_can_enter_daily_accounts($user);

        return response()->json([
            'success' => true,
            'data' => [
                'can_enter' => $canEnter,
                'actions' => array_values(array_filter([
                    $canEnter ? ['key' => 'mauzo', 'title' => 'Ingiza Mauzo/Mapato'] : null,
                    $canEnter ? ['key' => 'matumizi_manunuzi', 'title' => 'Ingiza Matumizi/Manunuzi'] : null,
                    $canEnter ? ['key' => 'stoo', 'title' => 'Ingiza Stoo'] : null,
                    ['key' => 'report', 'title' => 'Ona Repoti'],
                ])),
            ],
        ]);
    }

    public function reportEmployees(Request $request): JsonResponse
    {
        abort_unless(user_can_view_wamachinga_purchases($request->user()), 403);

        return response()->json([
            'success' => true,
            'data' => $this->mapEmployees(
                $request,
                fn ($companyId, $branchId) => $this->mauzoEmployeeList
                    ->listSalesPersonsForCompanyBranch($companyId, $branchId)
            ),
        ]);
    }

    public function entryEmployees(Request $request): JsonResponse
    {
        abort_unless(user_can_enter_daily_accounts($request->user()), 403);

        return response()->json([
            'success' => true,
            'data' => $this->mapEmployees(
                $request,
                fn ($companyId, $branchId) => $this->mauzoEmployeeList
                    ->listForCompanyBranch($companyId, $branchId)
            ),
        ]);
    }

    /**
     * @param  callable(int, ?int): \Illuminate\Support\Collection  $listFn
     */
    private function mapEmployees(Request $request, callable $listFn): \Illuminate\Support\Collection
    {
        $user = $request->user();
        $branchId = $this->branchId($request, $user);

        return $listFn((int) $user->company_id, $branchId)
            ->map(fn ($e) => [
                'id' => $e->id,
                'display_name' => $e->display_name,
                'employee_number' => $e->employee_number,
            ])
            ->values();
    }

    public function report(Request $request): JsonResponse
    {
        abort_unless(user_can_view_wamachinga_purchases($request->user()), 403);

        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
            'entry_date' => ['required', 'date'],
        ]);

        $user = $request->user();
        $companyId = (int) $user->company_id;
        $branchId = $this->branchId($request, $user);

        if (! $this->mauzoEmployeeList->salesPersonExistsForCompanyBranch(
            (int) $validated['employee_id'],
            $companyId,
            $branchId
        )) {
            return response()->json(['success' => false, 'message' => 'Mfanyakaji si sahihi.'], 422);
        }

        $report = $this->dailyAccountsReport->build(
            $companyId,
            $branchId,
            (int) $validated['employee_id'],
            $validated['entry_date']
        );

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function storeMauzo(Request $request): JsonResponse
    {
        return $this->forwardToWebController($request, 'storeMauzo');
    }

    public function storeMatumizi(Request $request): JsonResponse
    {
        return $this->forwardToWebController($request, 'storeMatumizi');
    }

    public function storeManunuzi(Request $request): JsonResponse
    {
        return $this->forwardToWebController($request, 'storeManunuzi');
    }

    public function storeStoo(Request $request): JsonResponse
    {
        return $this->forwardToWebController($request, 'storeStoo');
    }

    private function forwardToWebController(Request $request, string $method): JsonResponse
    {
        $controller = app(DailyAccountsController::class);
        $response = $controller->{$method}($request);

        if ($response instanceof JsonResponse) {
            return $response;
        }

        return response()->json([
            'success' => true,
            'message' => session('success') ?? 'Imehifadhiwa.',
        ]);
    }

    private function branchId(Request $request, $user): ?int
    {
        $id = $request->header('X-Branch-Id') ?? session('branch_id') ?? $user->branch_id;

        return $id ? (int) $id : null;
    }
}
