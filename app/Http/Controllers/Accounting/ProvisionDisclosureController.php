<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\ProvisionDisclosureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProvisionDisclosureController extends Controller
{
    protected ProvisionDisclosureService $disclosureService;

    public function __construct(ProvisionDisclosureService $disclosureService)
    {
        $this->disclosureService = $disclosureService;
    }

    /**
     * Display disclosure report
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        // Default to current year
        $periodStart = $request->get('period_start', now()->startOfYear()->format('Y-m-d'));
        $periodEnd = $request->get('period_end', now()->format('Y-m-d'));

        $disclosure = $this->disclosureService->generateDisclosure($companyId, $periodStart, $periodEnd);

        return view('accounting.provisions.disclosure', compact('disclosure', 'periodStart', 'periodEnd'));
    }

    /**
     * Export disclosure as JSON
     */
    public function exportJson(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $periodStart = $request->get('period_start', now()->startOfYear()->format('Y-m-d'));
        $periodEnd = $request->get('period_end', now()->format('Y-m-d'));

        $data = $this->disclosureService->exportToArray($companyId, $periodStart, $periodEnd);

        return response()->json($data);
    }

    /**
     * Export disclosure as Excel (placeholder - requires Laravel Excel package)
     */
    public function exportExcel(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $periodStart = $request->get('period_start', now()->startOfYear()->format('Y-m-d'));
        $periodEnd = $request->get('period_end', now()->format('Y-m-d'));

        $data = $this->disclosureService->exportToArray($companyId, $periodStart, $periodEnd);

        // TODO: Implement Excel export using Maatwebsite/Laravel-Excel or similar
        return response()->json([
            'message' => 'Excel export not yet implemented. Use JSON export or PDF.',
            'data' => $data,
        ]);
    }
}

