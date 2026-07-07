<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Purchase\DriverTrip;
use App\Models\Purchase\DriverTripMapatoLine;
use App\Models\Purchase\DriverTripMapatoRecord;
use App\Models\Purchase\DriverTripMatumiziLine;
use App\Models\Purchase\DriverTripMatumiziRecord;
use App\Services\Purchase\DriverTripNotificationService;
use App\Services\Purchase\DriverTripReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DriverTripController extends Controller
{
    public function __construct(
        private readonly DriverTripReportService $reportService,
        private readonly DriverTripNotificationService $notificationService
    ) {}

    public function index(Request $request)
    {
        abort_unless(user_can_view_wamachinga_purchases(), 403);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        if ($request->ajax()) {
            return $this->tripsDatatable($companyId, $branchId ? (int) $branchId : null);
        }

        return view('purchases.driver-trips.index');
    }

    public function store(Request $request)
    {
        abort_unless(user_can_enter_daily_accounts(), 403);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $validated = $request->validate($this->tripValidationRules(), $this->tripValidationMessages());

        $trip = DriverTrip::create([
            'company_id' => $companyId,
            'branch_id' => $branchId ? (int) $branchId : null,
            'trip_name' => $validated['trip_name'],
            'driver_name' => $validated['driver_name'],
            'vehicle_info' => $validated['vehicle_info'] ?? null,
            'trip_price' => $validated['trip_price'],
            'trip_date' => $validated['trip_date'],
            'status' => $validated['status'] ?? DriverTrip::STATUS_ACTIVE,
            'user_id' => $user->id,
        ]);

        $this->notificationService->sendTripCreated($trip, $user->name);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Safari imesajiliwa kikamilifu.']);
        }

        return redirect()
            ->route('purchases.driver-trips.index')
            ->with('success', 'Safari imesajiliwa kikamilifu.');
    }

    public function update(Request $request, int $trip)
    {
        abort_unless(user_can_enter_daily_accounts(), 403);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $driverTrip = $this->findTripForScope($trip, $companyId, $branchId ? (int) $branchId : null);

        $validated = $request->validate($this->tripValidationRules(), $this->tripValidationMessages());

        $driverTrip->update([
            'trip_name' => $validated['trip_name'],
            'driver_name' => $validated['driver_name'],
            'vehicle_info' => $validated['vehicle_info'] ?? null,
            'trip_price' => $validated['trip_price'],
            'trip_date' => $validated['trip_date'],
            'status' => $validated['status'] ?? DriverTrip::STATUS_ACTIVE,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Safari imesasishwa kikamilifu.']);
        }

        return redirect()
            ->route('purchases.driver-trips.index')
            ->with('success', 'Safari imesasishwa kikamilifu.');
    }

    public function destroy(int $trip)
    {
        abort_unless(user_can_enter_daily_accounts(), 403);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $driverTrip = $this->findTripForScope($trip, $companyId, $branchId ? (int) $branchId : null);

        $this->notificationService->sendTripDeleted($driverTrip, $user->name);

        DB::transaction(function () use ($driverTrip) {
            $driverTrip->delete();
        });

        return response()->json(['message' => 'Safari imefutwa kikamilifu.']);
    }

    public function storeMapato(Request $request)
    {
        return $this->storeTripEntry(
            $request,
            DriverTripMapatoRecord::class,
            DriverTripMapatoLine::class,
            'driver_trip_mapato_record_id',
            'mapato',
            'Mapato yamehifadhiwa'
        );
    }

    public function storeMatumizi(Request $request)
    {
        return $this->storeTripEntry(
            $request,
            DriverTripMatumiziRecord::class,
            DriverTripMatumiziLine::class,
            'driver_trip_matumizi_record_id',
            'matumizi',
            'Matumizi yamehifadhiwa'
        );
    }

    public function report(int $trip)
    {
        abort_unless(user_can_view_wamachinga_purchases(), 403);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $driverTrip = $this->findTripForScope($trip, $companyId, $branchId ? (int) $branchId : null);
        $report = $this->reportService->build($driverTrip);

        return view('purchases.driver-trips.report', $report);
    }

    protected function tripsDatatable(int $companyId, ?int $branchId)
    {
        $query = DriverTrip::query()
            ->forCompanyBranch($companyId, $branchId)
            ->orderByDesc('trip_date')
            ->orderByDesc('id');

        return DataTables::of($query)
            ->setRowClass(function ($row) {
                return ($row->status ?? DriverTrip::STATUS_ACTIVE) === DriverTrip::STATUS_COMPLETED
                    ? 'trip-completed'
                    : '';
            })
            ->addColumn('trip_date_display', fn ($row) => $row->trip_date?->format('d/m/Y') ?? '—')
            ->addColumn('trip_price_display', fn ($row) => number_format((float) $row->trip_price, 2))
            ->addColumn('status_display', function ($row) {
                $status = $row->status ?? DriverTrip::STATUS_ACTIVE;
                $label = DriverTrip::statusOptions()[$status] ?? $status;

                if ($status === DriverTrip::STATUS_COMPLETED) {
                    return '<span class="badge bg-success">Imekwisha</span>';
                }

                return '<span class="badge bg-primary">Hai</span>';
            })
            ->addColumn('vehicle_info_short', function ($row) {
                $info = trim((string) $row->vehicle_info);

                return $info === '' ? '—' : (strlen($info) > 40 ? substr($info, 0, 40) . '…' : $info);
            })
            ->addColumn('actions', function ($row) {
                $tripName = e($row->trip_name);
                $tripDate = $row->trip_date?->format('Y-m-d') ?? date('Y-m-d');
                $canEnter = user_can_enter_daily_accounts();

                $html = '<div class="d-flex flex-wrap gap-1 justify-content-center">';

                if ($canEnter) {
                    $html .= '<button type="button" class="btn btn-sm btn-outline-success btn-trip-mapato"'
                        . ' data-trip-id="' . (int) $row->id . '"'
                        . ' data-trip-name="' . $tripName . '"'
                        . ' data-trip-date="' . e($tripDate) . '"'
                        . ' title="Ingiza mapato">'
                        . '<i class="bx bx-wallet me-1"></i> Mapato</button>';

                    $html .= '<button type="button" class="btn btn-sm btn-outline-warning btn-trip-matumizi"'
                        . ' data-trip-id="' . (int) $row->id . '"'
                        . ' data-trip-name="' . $tripName . '"'
                        . ' data-trip-date="' . e($tripDate) . '"'
                        . ' title="Ingiza matumizi">'
                        . '<i class="bx bx-receipt me-1"></i> Matumizi</button>';
                }

                $html .= '<a href="' . e(route('purchases.driver-trips.report', $row->id)) . '"'
                    . ' class="btn btn-sm btn-outline-primary" title="Ripoti">'
                    . '<i class="bx bx-file me-1"></i> Ripoti</a>';

                if ($canEnter) {
                    $vehicleInfo = e($row->vehicle_info ?? '');
                    $html .= '<button type="button" class="btn btn-sm btn-outline-secondary btn-trip-edit"'
                        . ' data-trip-id="' . (int) $row->id . '"'
                        . ' data-trip-name="' . $tripName . '"'
                        . ' data-driver-name="' . e($row->driver_name) . '"'
                        . ' data-vehicle-info="' . $vehicleInfo . '"'
                        . ' data-trip-price="' . e((string) $row->trip_price) . '"'
                        . ' data-trip-date="' . e($tripDate) . '"'
                        . ' data-trip-status="' . e($row->status ?? DriverTrip::STATUS_ACTIVE) . '"'
                        . ' title="Badili safari">'
                        . '<i class="bx bx-edit"></i></button>';

                    $html .= '<button type="button" class="btn btn-sm btn-outline-danger btn-trip-delete"'
                        . ' data-trip-id="' . (int) $row->id . '"'
                        . ' data-trip-name="' . $tripName . '"'
                        . ' title="Futa safari">'
                        . '<i class="bx bx-trash"></i></button>';
                }

                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['actions', 'status_display'])
            ->make(true);
    }

    /**
     * @param  class-string  $recordModel
     * @param  class-string  $lineModel
     */
    private function storeTripEntry(
        Request $request,
        string $recordModel,
        string $lineModel,
        string $lineRecordForeignKey,
        string $entryTypeKey,
        string $successPrefix
    ): JsonResponse {
        abort_unless(user_can_enter_daily_accounts(), 403);

        $user = Auth::user();
        $companyId = (int) $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        $typeLabel = $entryTypeKey === 'matumizi' ? 'matumizi' : 'mapato';

        $validated = $request->validate([
            'driver_trip_id' => ['required', 'integer'],
            'entry_date' => ['required', 'date'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.maelezo' => ['required', 'string', 'max:2000'],
            'lines.*.kiasi' => ['required', 'numeric', 'min:0'],
        ], [
            'driver_trip_id.required' => 'Safari haijachaguliwa.',
            'entry_date.required' => 'Tarehe inahitajika.',
            'lines.required' => 'Ongeza angalau mstari mmoja wa ' . $typeLabel . '.',
            'lines.*.maelezo.required' => 'Maelezo yanahitajika kwa kila mstari.',
            'lines.*.kiasi.required' => 'Kiasi kinahitajika kwa kila mstari.',
        ]);

        $trip = $this->findTripForScope(
            (int) $validated['driver_trip_id'],
            $companyId,
            $branchId ? (int) $branchId : null
        );

        $lines = array_values(array_filter($validated['lines'], function ($line) {
            $hasMaelezo = trim((string) ($line['maelezo'] ?? '')) !== '';
            $hasAmount = (float) ($line['kiasi'] ?? 0) > 0;

            return $hasMaelezo || $hasAmount;
        }));

        if ($lines === []) {
            return response()->json([
                'message' => 'Ongeza angalau mstari mmoja wa ' . $typeLabel . '.',
                'errors' => ['lines' => ['Ongeza angalau mstari mmoja wa ' . $typeLabel . '.']],
            ], 422);
        }

        $record = DB::transaction(function () use ($validated, $companyId, $branchId, $user, $lines, $trip, $recordModel, $lineModel, $lineRecordForeignKey) {
            $record = $recordModel::create([
                'company_id' => $companyId,
                'branch_id' => $branchId ? (int) $branchId : null,
                'driver_trip_id' => $trip->id,
                'entry_date' => $validated['entry_date'],
                'user_id' => $user->id,
            ]);

            foreach ($lines as $index => $line) {
                $lineModel::create([
                    $lineRecordForeignKey => $record->id,
                    'maelezo' => $line['maelezo'],
                    'kiasi' => $line['kiasi'],
                    'sort_order' => $index,
                ]);
            }

            return $record->load('lines');
        });

        $total = (float) $record->lines->sum('kiasi');

        $linePayload = collect($lines)->map(fn ($line) => [
            'maelezo' => $line['maelezo'],
            'kiasi' => $line['kiasi'],
        ])->all();

        if ($entryTypeKey === 'mapato') {
            $this->notificationService->sendMapatoEntered(
                $trip,
                $linePayload,
                $total,
                $validated['entry_date'],
                $user->name
            );
        } else {
            $this->notificationService->sendMatumiziEntered(
                $trip,
                $linePayload,
                $total,
                $validated['entry_date'],
                $user->name
            );
        }

        return response()->json([
            'message' => $successPrefix . ' kwa safari ' . $trip->trip_name . ' (jumla ' . number_format($total, 2) . ').',
            'record_id' => $record->id,
            'total' => $total,
        ]);
    }

    private function findTripForScope(int $tripId, int $companyId, ?int $branchId): DriverTrip
    {
        $query = DriverTrip::query()
            ->where('company_id', $companyId)
            ->where('id', $tripId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function tripValidationRules(): array
    {
        return [
            'trip_name' => ['required', 'string', 'max:255'],
            'driver_name' => ['required', 'string', 'max:255'],
            'vehicle_info' => ['nullable', 'string', 'max:2000'],
            'trip_price' => ['required', 'numeric', 'min:0'],
            'trip_date' => ['required', 'date'],
            'status' => ['nullable', 'in:' . DriverTrip::STATUS_ACTIVE . ',' . DriverTrip::STATUS_COMPLETED],
        ];
    }

    /** @return array<string, string> */
    private function tripValidationMessages(): array
    {
        return [
            'trip_name.required' => 'Jina la safari linahitajika.',
            'driver_name.required' => 'Jina la dereva linahitajika.',
            'trip_price.required' => 'Bei ya safari inahitajika.',
            'trip_date.required' => 'Tarehe ya safari inahitajika.',
        ];
    }
}
