<?php

namespace App\Http\Controllers\Api\Machinga;

use App\Http\Controllers\Controller;
use App\Models\Purchase\SupplierAdvance;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MachingaHubController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = (int) $user->company_id;
        $branchId = $this->resolveBranchId($request, $user);
        $isMd = $user->hasRole('md');

        $modules = [];

        if (! $isMd && $user->can('view suppliers')) {
            $modules[] = [
                'key' => 'suppliers',
                'title' => 'Wakusanyaji - Wamachinga',
                'subtitle' => 'Orodha ya wakusanyaji: jina na namba ya simu.',
                'icon' => 'group',
                'count' => Supplier::query()
                    ->where('company_id', $companyId)
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->count(),
            ];
        }

        if (user_can_view_wamachinga_purchases($user)) {
            $advanceCount = SupplierAdvance::query()
                ->where('company_id', $companyId)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->count();

            $modules[] = [
                'key' => 'supplier_advances',
                'title' => 'Malipo ya Awali - Wamachinga',
                'subtitle' => 'Rekodi malipo, matumizi na salio',
                'icon' => 'wallet',
                'count' => $advanceCount,
            ];

            $modules[] = [
                'key' => 'hesabu_machinga',
                'title' => 'Hesabu za Machinga',
                'subtitle' => 'Ripoti ya msambazaji kwa tarehe',
                'icon' => 'file',
                'count' => null,
            ];

            $modules[] = [
                'key' => 'daily_accounts',
                'title' => 'Hesabu za Kila Siku (Wafanyakazi)',
                'subtitle' => 'Mauzo, matumizi, manunuzi, stoo',
                'icon' => 'calendar',
                'count' => $advanceCount,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'title' => 'Usimamizi wa Mtiririko wa Ununuzi',
                'modules' => $modules,
            ],
        ]);
    }

    private function resolveBranchId(Request $request, $user): ?int
    {
        $branchId = $request->header('X-Branch-Id') ?? session('branch_id') ?? $user->branch_id;

        return $branchId ? (int) $branchId : null;
    }
}
