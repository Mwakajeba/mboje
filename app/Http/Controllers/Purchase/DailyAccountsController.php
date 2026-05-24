<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Purchase\DailyManunuziLine;
use App\Models\Purchase\DailyManunuziRecord;
use App\Models\Purchase\DailyMatumiziLine;
use App\Models\Purchase\DailyMatumiziRecord;
use App\Models\Purchase\DailyMauzoLine;
use App\Models\Purchase\DailyMauzoRecord;
use App\Models\Purchase\DailyStooLine;
use App\Models\Purchase\DailyStooRecord;
use App\Models\User;
use App\Services\Purchase\DailyAccountsReportDeletionService;
use App\Services\Purchase\DailyAccountsReportLineService;
use App\Services\Purchase\DailyAccountsReportNotificationService;
use App\Services\Purchase\DailyAccountsReportService;
use App\Services\Purchase\DailyMauzoEmployeeListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DailyAccountsController extends Controller
{
    public function __construct(
        private readonly DailyMauzoEmployeeListService $mauzoEmployeeList,
        private readonly DailyAccountsReportService $dailyAccountsReport,
        private readonly DailyAccountsReportNotificationService $reportNotification,
        private readonly DailyAccountsReportDeletionService $reportDeletion,
        private readonly DailyAccountsReportLineService $reportLineService
    ) {}

    public function index()
    {
        abort_unless(user_can_view_wamachinga_purchases(), 403);

        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id;

        $employees = $this->mauzoEmployeeList->listForCompanyBranch(
            (int) $user->company_id,
            $branchId ? (int) $branchId : null
        );

        return view('purchases.daily-accounts.index', compact('employees'));
    }

    public function storeMauzo(Request $request)
    {
        return $this->storeDailyEmployeeEntry(
            $request,
            DailyMauzoRecord::class,
            DailyMauzoLine::class,
            'daily_mauzo_record_id',
            'mauzo',
            'Mauzo/Mapato yamehifadhiwa'
        );
    }

    public function storeMatumizi(Request $request)
    {
        return $this->storeDailyEmployeeEntry(
            $request,
            DailyMatumiziRecord::class,
            DailyMatumiziLine::class,
            'daily_matumizi_record_id',
            'matumizi',
            'Matumizi yamehifadhiwa'
        );
    }

    public function storeManunuzi(Request $request)
    {
        return $this->storeDailyEmployeeEntry(
            $request,
            DailyManunuziRecord::class,
            DailyManunuziLine::class,
            'daily_manunuzi_record_id',
            'manunuzi',
            'Manunuzi yamehifadhiwa'
        );
    }

    public function storeStoo(Request $request)
    {
        return $this->storeDailyEmployeeEntry(
            $request,
            DailyStooRecord::class,
            DailyStooLine::class,
            'daily_stoo_record_id',
            'stoo',
            'Taarifa za stoo zimehifadhiwa',
            'thamani',
            ['bidhaa' => ['required', 'string', 'max:255']],
            amountIsText: true
        );
    }

    public function matumiziManunuzi()
    {
        abort_unless(user_can_enter_daily_accounts(), 403);

        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id;

        $employees = $this->mauzoEmployeeList->listForCompanyBranch(
            (int) $user->company_id,
            $branchId ? (int) $branchId : null
        );

        return view('purchases.daily-accounts.matumizi-manunuzi', compact('employees'));
    }

    public function report()
    {
        abort_unless(user_can_view_wamachinga_purchases(), 403);

        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id;

        $employees = $this->mauzoEmployeeList->listForCompanyBranch(
            (int) $user->company_id,
            $branchId ? (int) $branchId : null
        );

        return view('purchases.daily-accounts.report', compact('employees'));
    }

    public function reportShow(Request $request)
    {
        abort_unless(user_can_view_wamachinga_purchases(), 403);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
            'entry_date' => ['required', 'date'],
        ], [
            'employee_id.required' => 'Chagua mfanyakazi.',
            'entry_date.required' => 'Chagua tarehe.',
            'entry_date.date' => 'Tarehe si sahihi.',
        ]);

        if (! $this->mauzoEmployeeList->employeeExistsForCompanyBranch(
            (int) $validated['employee_id'],
            $companyId,
            $branchId ? (int) $branchId : null
        )) {
            abort(404);
        }

        $employeeName = $this->resolveEmployeeDisplayName((int) $validated['employee_id']);
        $report = $this->dailyAccountsReport->build(
            $companyId,
            $branchId ? (int) $branchId : null,
            (int) $validated['employee_id'],
            $validated['entry_date']
        );

        $canManage = $this->userIsAdmin();
        $employees = $canManage
            ? $this->mauzoEmployeeList->listForCompanyBranch($companyId, $branchId ? (int) $branchId : null)
            : collect();

        return view('purchases.daily-accounts.report-show', array_merge($report, [
            'employee_name' => $employeeName,
            'employee_id' => (int) $validated['employee_id'],
            'can_manage' => $canManage,
            'can_delete' => $canManage,
            'employees' => $employees,
        ]));
    }

    public function updateReportLine(Request $request, string $type, int $line)
    {
        abort_unless($this->userIsAdmin(), 403);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $amountField = $this->reportLineService->amountField($type);

        $amountLabel = $type === 'stoo' ? 'Thamani/Idadi' : 'Kiasi';

        $rules = [
            'employee_id' => ['required', 'integer'],
            'entry_date' => ['required', 'date'],
            'maelezo' => ['required', 'string', 'max:2000'],
            $amountField => $type === 'stoo'
                ? ['required', 'string', 'max:255']
                : ['required', 'numeric', 'min:0'],
        ];

        if ($type === 'stoo') {
            $rules['bidhaa'] = ['required', 'string', 'max:255'];
        }

        $messages = [
            'employee_id.required' => 'Chagua mfanyakazi.',
            'entry_date.required' => 'Chagua tarehe.',
            'entry_date.date' => 'Tarehe si sahihi.',
            'maelezo.required' => 'Maelezo yanahitajika.',
            'bidhaa.required' => 'Jina la bidhaa linahitajika.',
            $amountField.'.required' => $amountLabel.' inahitajika.',
        ];

        if ($type !== 'stoo') {
            $messages[$amountField.'.numeric'] = $amountLabel.' lazima iwe nambari.';
            $messages[$amountField.'.min'] = $amountLabel.' haiwezi kuwa hasi.';
        }

        $validated = $request->validate($rules, $messages);

        if (! $this->mauzoEmployeeList->employeeExistsForCompanyBranch(
            (int) $validated['employee_id'],
            $companyId,
            $branchId ? (int) $branchId : null
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Mfanyakaji aliyechaguliwa si sahihi.',
                'errors' => ['employee_id' => ['Mfanyakaji aliyechaguliwa si sahihi.']],
            ], 422);
        }

        try {
            $this->reportLineService->updateLine(
                $type,
                $line,
                $companyId,
                $branchId ? (int) $branchId : null,
                $validated
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            abort(404);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rekodi imesasishwa.',
            'redirect' => route('purchases.daily-accounts.report.show', [
                'employee_id' => (int) $validated['employee_id'],
                'entry_date' => $validated['entry_date'],
            ]),
        ]);
    }

    public function destroyReportLine(string $type, int $line)
    {
        abort_unless($this->userIsAdmin(), 403);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        try {
            $this->reportDeletion->deleteLine($type, $line, $companyId, $branchId ? (int) $branchId : null);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            abort(404);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Rekodi imefutwa.']);
    }

    public function destroyReportSection(Request $request, string $type)
    {
        abort_unless($this->userIsAdmin(), 403);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $validated = $this->validateReportScope($request, $companyId, $branchId);

        try {
            $this->reportDeletion->deleteSectionForDate(
                $type,
                $companyId,
                $branchId ? (int) $branchId : null,
                (int) $validated['employee_id'],
                $validated['entry_date']
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Rekodi zote za sehemu hii zimefutwa.']);
    }

    public function destroyReportAll(Request $request)
    {
        abort_unless($this->userIsAdmin(), 403);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $validated = $this->validateReportScope($request, $companyId, $branchId);

        $this->reportDeletion->deleteAllForDate(
            $companyId,
            $branchId ? (int) $branchId : null,
            (int) $validated['employee_id'],
            $validated['entry_date']
        );

        return response()->json(['success' => true, 'message' => 'Rekodi zote za siku hii zimefutwa.']);
    }

    public function reportSendNotification(Request $request)
    {
        abort_unless(user_can_view_wamachinga_purchases(), 403);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
            'entry_date' => ['required', 'date'],
        ], [
            'employee_id.required' => 'Chagua mfanyakazi.',
            'entry_date.required' => 'Chagua tarehe.',
        ]);

        if (! $this->mauzoEmployeeList->employeeExistsForCompanyBranch(
            (int) $validated['employee_id'],
            $companyId,
            $branchId ? (int) $branchId : null
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Mfanyakaji aliyechaguliwa si sahihi.',
            ], 422);
        }

        $employeeName = $this->resolveEmployeeDisplayName((int) $validated['employee_id']);

        $result = $this->reportNotification->send(
            $companyId,
            $branchId ? (int) $branchId : null,
            (int) $validated['employee_id'],
            $validated['entry_date'],
            $employeeName
        );

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    /**
     * @param  class-string  $recordModel
     * @param  class-string  $lineModel
     */
    private function storeDailyEmployeeEntry(
        Request $request,
        string $recordModel,
        string $lineModel,
        string $lineRecordForeignKey,
        string $entryTypeKey,
        string $successPrefix,
        string $amountField = 'kiasi',
        array $extraRecordRules = [],
        bool $amountIsText = false
    ): JsonResponse|\Illuminate\Http\RedirectResponse {
        abort_unless(user_can_enter_daily_accounts(), 403);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $typeLabel = match ($entryTypeKey) {
            'mauzo' => 'mauzo/mapato',
            'matumizi' => 'matumizi',
            'manunuzi' => 'manunuzi',
            'stoo' => 'stoo',
            default => 'mauzo/mapato',
        };

        $amountLabel = $amountIsText ? 'Thamani/Idadi' : 'Kiasi';

        $amountLineRules = $amountIsText
            ? ['required', 'string', 'max:255']
            : ['required', 'numeric', 'min:0'];

        $rules = array_merge([
            'employee_id' => ['required', 'integer'],
            'entry_date' => ['required', 'date'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.maelezo' => ['required', 'string', 'max:2000'],
            'lines.*.'.$amountField => $amountLineRules,
        ], $extraRecordRules);

        $messages = [
            'employee_id.required' => 'Chagua mfanyakazi.',
            'entry_date.required' => 'Tarehe inahitajika.',
            'entry_date.date' => 'Tarehe si sahihi.',
            'lines.required' => 'Ongeza angalau mstari mmoja wa '.$typeLabel.'.',
            'lines.min' => 'Ongeza angalau mstari mmoja wa '.$typeLabel.'.',
            'lines.*.maelezo.required' => 'Maelezo yanahitajika kwa kila mstari.',
            'lines.*.'.$amountField.'.required' => $amountLabel.' inahitajika kwa kila mstari.',
            'bidhaa.required' => 'Jina la bidhaa linahitajika.',
            'bidhaa.max' => 'Jina la bidhaa ni refu sana.',
        ];

        if (! $amountIsText) {
            $messages['lines.*.'.$amountField.'.numeric'] = $amountLabel.' lazima iwe nambari.';
            $messages['lines.*.'.$amountField.'.min'] = $amountLabel.' haiwezi kuwa hasi.';
        }

        $validated = $request->validate($rules, $messages);

        if (! $this->mauzoEmployeeList->employeeExistsForCompanyBranch(
            (int) $validated['employee_id'],
            $companyId,
            $branchId ? (int) $branchId : null
        )) {
            return response()->json([
                'message' => 'Mfanyakaji aliyechaguliwa si sahihi.',
                'errors' => ['employee_id' => ['Mfanyakaji aliyechaguliwa si sahihi.']],
            ], 422);
        }

        $employeeName = $this->resolveEmployeeDisplayName((int) $validated['employee_id']);

        $lines = array_values(array_filter($validated['lines'], function ($line) use ($amountField, $amountIsText) {
            $hasMaelezo = trim((string) ($line['maelezo'] ?? '')) !== '';
            $hasAmount = $amountIsText
                ? trim((string) ($line[$amountField] ?? '')) !== ''
                : (float) ($line[$amountField] ?? 0) > 0;

            return $hasMaelezo || $hasAmount;
        }));

        if ($lines === []) {
            return response()->json([
                'message' => 'Ongeza angalau mstari mmoja wa '.$typeLabel.'.',
                'errors' => ['lines' => ['Ongeza angalau mstari mmoja wa '.$typeLabel.'.']],
            ], 422);
        }

        $record = DB::transaction(function () use ($validated, $companyId, $branchId, $user, $lines, $recordModel, $lineModel, $lineRecordForeignKey, $amountField, $extraRecordRules) {
            $recordData = [
                'company_id' => $companyId,
                'branch_id' => $branchId ? (int) $branchId : null,
                'employee_id' => (int) $validated['employee_id'],
                'entry_date' => $validated['entry_date'],
                'user_id' => $user->id,
            ];

            foreach (array_keys($extraRecordRules) as $field) {
                $recordData[$field] = $validated[$field];
            }

            $record = $recordModel::create($recordData);

            foreach ($lines as $index => $line) {
                $lineModel::create([
                    $lineRecordForeignKey => $record->id,
                    'maelezo' => $line['maelezo'],
                    $amountField => $line[$amountField],
                    'sort_order' => $index,
                ]);
            }

            return $record->load('lines');
        });

        $total = $amountIsText ? null : (float) $record->lines->sum($amountField);

        $successMessage = $successPrefix.' kwa '.$employeeName;
        if (! empty($validated['bidhaa'])) {
            $successMessage .= ' — '.$validated['bidhaa'];
        }
        $successMessage .= '.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $successMessage,
                'record_id' => $record->id,
                'total' => $total,
            ]);
        }

        return redirect()
            ->route('purchases.daily-accounts.index')
            ->with('success', $successMessage);
    }

    /**
     * @return array{employee_id: int, entry_date: string}
     */
    private function validateReportScope(Request $request, int $companyId, ?int $branchId): array
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
            'entry_date' => ['required', 'date'],
        ], [
            'employee_id.required' => 'Mfanyakaji haapatikani.',
            'entry_date.required' => 'Tarehe haipo.',
        ]);

        if (! $this->mauzoEmployeeList->employeeExistsForCompanyBranch(
            (int) $validated['employee_id'],
            $companyId,
            $branchId ? (int) $branchId : null
        )) {
            abort(404);
        }

        return $validated;
    }

    private function userIsAdmin(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('admin')
            || $user->hasRole('super-admin')
            || $user->hasRole('Super Admin');
    }

    private function resolveEmployeeDisplayName(int $employeeId): string
    {
        if (Schema::hasTable('hr_employees')) {
            $employee = Employee::query()->find($employeeId);

            return $employee?->full_name ?? 'Mfanyakaji';
        }

        return User::query()->find($employeeId)?->name ?? 'Mfanyakaji';
    }
}
