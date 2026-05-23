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
        private readonly DailyAccountsReportNotificationService $reportNotification
    ) {}

    public function index()
    {
        abort_unless(Auth::user()->can('view purchases'), 403);

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
            'Mauzo yamehifadhiwa'
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
            ['bidhaa' => ['required', 'string', 'max:255']]
        );
    }

    public function matumiziManunuzi()
    {
        abort_unless(Auth::user()->can('record purchase payment'), 403);

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
        abort_unless(Auth::user()->can('view purchases'), 403);

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
        abort_unless(Auth::user()->can('view purchases'), 403);

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

        return view('purchases.daily-accounts.report-show', array_merge($report, [
            'employee_name' => $employeeName,
            'employee_id' => (int) $validated['employee_id'],
        ]));
    }

    public function reportSendNotification(Request $request)
    {
        abort_unless(Auth::user()->can('view purchases'), 403);

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
        array $extraRecordRules = []
    ): JsonResponse|\Illuminate\Http\RedirectResponse {
        abort_unless(Auth::user()->can('record purchase payment'), 403);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $typeLabel = match ($entryTypeKey) {
            'matumizi' => 'matumizi',
            'manunuzi' => 'manunuzi',
            'stoo' => 'stoo',
            default => 'mauzo',
        };

        $amountLabel = $amountField === 'thamani' ? 'Thamani' : 'Kiasi';

        $rules = array_merge([
            'employee_id' => ['required', 'integer'],
            'entry_date' => ['required', 'date'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.maelezo' => ['required', 'string', 'max:2000'],
            'lines.*.'.$amountField => ['required', 'numeric', 'min:0'],
        ], $extraRecordRules);

        $messages = [
            'employee_id.required' => 'Chagua mfanyakazi.',
            'entry_date.required' => 'Tarehe inahitajika.',
            'entry_date.date' => 'Tarehe si sahihi.',
            'lines.required' => 'Ongeza angalau mstari mmoja wa '.$typeLabel.'.',
            'lines.min' => 'Ongeza angalau mstari mmoja wa '.$typeLabel.'.',
            'lines.*.maelezo.required' => 'Maelezo yanahitajika kwa kila mstari.',
            'lines.*.'.$amountField.'.required' => $amountLabel.' inahitajika kwa kila mstari.',
            'lines.*.'.$amountField.'.numeric' => $amountLabel.' lazima iwe nambari.',
            'lines.*.'.$amountField.'.min' => $amountLabel.' haiwezi kuwa hasi.',
            'bidhaa.required' => 'Jina la bidhaa linahitajika.',
            'bidhaa.max' => 'Jina la bidhaa ni refu sana.',
        ];

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

        $lines = array_values(array_filter($validated['lines'], function ($line) use ($amountField) {
            return trim((string) ($line['maelezo'] ?? '')) !== '' || (float) ($line[$amountField] ?? 0) > 0;
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

        $total = (float) $record->lines->sum($amountField);

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

    private function resolveEmployeeDisplayName(int $employeeId): string
    {
        if (Schema::hasTable('hr_employees')) {
            $employee = Employee::query()->find($employeeId);

            return $employee?->full_name ?? 'Mfanyakaji';
        }

        return User::query()->find($employeeId)?->name ?? 'Mfanyakaji';
    }
}
