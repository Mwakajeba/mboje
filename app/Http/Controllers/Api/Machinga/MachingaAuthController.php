<?php

namespace App\Http\Controllers\Api\Machinga;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MachingaAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = find_user_by_phone($request->phone);

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Namba ya simu au nenosiri si sahihi.',
            ], 401);
        }

        if (($user->status ?? 'active') !== 'active' && ($user->is_active ?? true) === false) {
            return response()->json([
                'success' => false,
                'message' => 'Akaunti yako haipo hai. Wasiliana na msimamizi.',
            ], 403);
        }

        if (! user_can_view_wamachinga_purchases($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Huna ruhusa ya kutumia programu ya Wamachinga.',
            ], 403);
        }

        // Long-lived token; expires only when user logs out (Sanctum expiration = null).
        $token = $user->createToken('machinga-mobile', ['*'], null)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Umefanikiwa kuingia.',
            'data' => [
                'token' => $token,
                'user' => $this->formatUser($user),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $this->formatUser($user),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Umetoka kwenye mfumo.',
        ]);
    }

    public function branches(Request $request): JsonResponse
    {
        $user = $request->user();
        $ids = $user->permittedBranchIds();

        $branches = Branch::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Branch $b) => [
                'id' => $b->id,
                'name' => $b->name,
            ])
            ->values();

        return response()->json(['success' => true, 'data' => $branches]);
    }

    public function locations(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => ['required', 'integer'],
        ]);

        $user = $request->user();
        $branchId = (int) $request->query('branch_id');

        if (! in_array($branchId, $user->permittedBranchIds(), true)) {
            return response()->json([
                'success' => false,
                'message' => 'Tawi haliruhusiwi.',
            ], 403);
        }

        $locations = $user->locations()
            ->where('inventory_locations.branch_id', $branchId)
            ->select('inventory_locations.id', 'inventory_locations.name', 'inventory_locations.branch_id')
            ->orderBy('inventory_locations.name')
            ->get()
            ->map(fn ($loc) => [
                'id' => $loc->id,
                'name' => $loc->name,
                'branch_id' => (int) $loc->branch_id,
            ])
            ->values();

        return response()->json(['success' => true, 'data' => $locations]);
    }

    public function setContext(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'branch_id' => ['required', 'integer'],
            'location_id' => ['nullable', 'integer'],
        ]);

        $branchId = (int) $validated['branch_id'];
        if (! in_array($branchId, $user->permittedBranchIds(), true)) {
            return response()->json([
                'success' => false,
                'message' => 'Tawi haliruhusiwi.',
            ], 422);
        }

        $locationId = isset($validated['location_id']) ? (int) $validated['location_id'] : null;
        if ($locationId) {
            $valid = $user->locations()
                ->where('inventory_locations.branch_id', $branchId)
                ->where('inventory_locations.id', $locationId)
                ->exists();
            if (! $valid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Eneo haliruhusiwi.',
                ], 422);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Muktadha umehifadhiwa.',
            'data' => [
                'branch_id' => $branchId,
                'location_id' => $locationId,
            ],
        ]);
    }

    private function formatUser(User $user): array
    {
        $branchIds = $user->permittedBranchIds();
        $branches = Branch::query()
            ->whereIn('id', $branchIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Branch $b) => ['id' => $b->id, 'name' => $b->name])
            ->values()
            ->all();

        $locations = $user->locations()
            ->select('inventory_locations.id', 'inventory_locations.name', 'inventory_locations.branch_id')
            ->orderBy('inventory_locations.name')
            ->get()
            ->map(fn ($loc) => [
                'id' => $loc->id,
                'name' => $loc->name,
                'branch_id' => (int) $loc->branch_id,
            ])
            ->values()
            ->all();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'company_id' => $user->company_id,
            'branch_id' => $user->branch_id,
            'is_md' => $user->hasRole('md'),
            'can_record' => user_can_record_wamachinga_purchases($user),
            'can_enter_daily' => user_can_enter_daily_accounts($user),
            'can_view_suppliers' => $user->can('view suppliers') && ! $user->hasRole('md'),
            'branches' => $branches,
            'locations' => $locations,
        ];
    }
}
