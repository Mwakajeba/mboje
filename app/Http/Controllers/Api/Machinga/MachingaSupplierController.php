<?php

namespace App\Http\Controllers\Api\Machinga;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MachingaSupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasRole('md') || ! $user->can('view suppliers')) {
            return response()->json([
                'success' => false,
                'message' => 'Huna ruhusa.',
            ], 403);
        }

        $companyId = (int) $user->company_id;
        $branchId = $this->branchId($request, $user);

        $suppliers = Supplier::query()
            ->where('company_id', $companyId)
            ->visibleInBranch($branchId)
            ->orderBy('name')
            ->get(['id', 'name', 'phone'])
            ->map(fn (Supplier $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'phone' => $s->phone,
            ])
            ->values();

        return response()->json(['success' => true, 'data' => $suppliers]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasRole('md') || ! $user->can('create supplier')) {
            return response()->json([
                'success' => false,
                'message' => 'Huna ruhusa ya kuongeza mkusanyaji.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
        ], [
            'name.required' => 'Jina linahitajika.',
            'phone.required' => 'Namba ya simu inahitajika.',
        ]);

        $companyId = (int) $user->company_id;
        $branchId = $this->branchId($request, $user);

        $supplier = Supplier::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'status' => Supplier::STATUS_ACTIVE,
            'company_id' => $companyId,
            'branch_id' => Supplier::resolveBranchIdForUser($branchId),
            'created_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mkusanyaji ameongezwa.',
            'data' => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'phone' => $supplier->phone,
            ],
        ], 201);
    }

    private function branchId(Request $request, $user): ?int
    {
        $id = $request->header('X-Branch-Id') ?? session('branch_id') ?? $user->branch_id;

        return $id ? (int) $id : null;
    }
}
