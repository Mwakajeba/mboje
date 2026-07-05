<?php

namespace App\Services\Purchase;

use App\Models\Hr\Employee;
use App\Models\Role;
use App\Models\User;
use App\Services\PasswordService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DailyAccountsEmployeeService
{
    public const DEFAULT_PASSWORD = '12345';

    /**
     * @return array{user: User, employee: ?Employee}
     */
    public function create(User $actor, string $name, string $phone, int $roleId): array
    {
        $companyId = (int) $actor->company_id;
        $branchId = $this->resolveBranchId($actor);

        $role = Role::query()
            ->where('guard_name', 'web')
            ->whereKey($roleId)
            ->whereNotIn('name', ['super-admin', 'Super Admin'])
            ->first();

        if (! $role) {
            throw new \InvalidArgumentException('Jukumu ulilochagua si sahihi.');
        }

        $formattedPhone = $this->formatPhoneNumber($phone);
        $email = $this->generateUniqueEmail($companyId, $formattedPhone);

        return DB::transaction(function () use ($actor, $name, $formattedPhone, $email, $companyId, $branchId, $role) {
            $userData = [
                'name' => trim($name),
                'phone' => $formattedPhone,
                'email' => $email,
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'company_id' => $companyId,
                'status' => 'active',
                'is_active' => 'yes',
            ];

            if (Schema::hasColumn('users', 'branch_id')) {
                $userData['branch_id'] = $branchId;
            }

            $user = User::create($userData);

            (new PasswordService())->updatePassword($user, self::DEFAULT_PASSWORD);
            $user->assignRole($role);

            if (Schema::hasTable('branch_user')) {
                $user->branches()->sync([$branchId]);
            }

            $this->copyLocationsFromActor($actor, $user);

            $employee = $this->createEmployeeRecord($user, $companyId, $branchId);

            return [
                'user' => $user->fresh(['roles']),
                'employee' => $employee,
            ];
        });
    }

    private function createEmployeeRecord(User $user, int $companyId, int $branchId): ?Employee
    {
        if (! Schema::hasTable('hr_employees')) {
            return null;
        }

        $nameParts = preg_split('/\s+/', trim($user->name)) ?: [];
        $firstName = $nameParts[0] ?? $user->name;
        $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : $firstName;

        $data = [
            'company_id' => $companyId,
            'user_id' => $user->id,
            'employee_number' => $this->nextEmployeeNumber($companyId),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'status' => 'active',
        ];

        if (Schema::hasColumn('hr_employees', 'branch_id')) {
            $data['branch_id'] = $branchId;
        }

        if (Schema::hasColumn('hr_employees', 'phone_number')) {
            $data['phone_number'] = $user->phone;
        }

        if (Schema::hasColumn('hr_employees', 'email')) {
            $data['email'] = $user->email;
        }

        return Employee::create($data);
    }

    private function copyLocationsFromActor(User $actor, User $newUser): void
    {
        if (! Schema::hasTable('location_user')) {
            return;
        }

        $locations = $actor->locations()->get(['inventory_locations.id']);

        if ($locations->isEmpty()) {
            return;
        }

        $defaultLocationId = $actor->defaultLocation()->value('inventory_locations.id')
            ?? $locations->first()->id;

        $sync = [];
        foreach ($locations as $location) {
            $sync[$location->id] = [
                'is_default' => (int) $location->id === (int) $defaultLocationId,
            ];
        }

        $newUser->locations()->sync($sync);
    }

    private function nextEmployeeNumber(int $companyId): string
    {
        $last = Employee::query()
            ->where('company_id', $companyId)
            ->where('employee_number', 'like', 'EMP%')
            ->orderByDesc('employee_number')
            ->value('employee_number');

        $next = 1;
        if ($last && preg_match('/EMP(\d+)/', $last, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return 'EMP'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function generateUniqueEmail(int $companyId, string $phone): string
    {
        $base = 'mfanyakazi.'.$phone.'@mboje.local';
        $email = $base;
        $suffix = 1;

        while (User::query()->where('company_id', $companyId)->where('email', $email)->exists()) {
            $email = Str::before($base, '@').'.'.$suffix.'@'.Str::after($base, '@');
            $suffix++;
        }

        return $email;
    }

    private function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($phone, '+255')) {
            return substr($phone, 1);
        }

        if (str_starts_with($phone, '0')) {
            return '255'.substr($phone, 1);
        }

        if (str_starts_with($phone, '255')) {
            return $phone;
        }

        if (strlen($phone) === 9) {
            return '255'.$phone;
        }

        return $phone;
    }

    private function resolveBranchId(User $actor): int
    {
        $branchId = (int) (session('branch_id') ?? 0);

        if (! $branchId && Schema::hasColumn('users', 'branch_id') && $actor->branch_id) {
            $branchId = (int) $actor->branch_id;
        }

        if (! $branchId && Schema::hasTable('branch_user')) {
            $branchId = (int) ($actor->branches()->value('branches.id') ?? 0);
        }

        if (! $branchId) {
            throw new \InvalidArgumentException('Tawi halijachaguliwa. Chagua tawi kisha jaribu tena.');
        }

        return $branchId;
    }

    public function deactivate(int $workerId, int $companyId, ?int $branchId, User $actor): string
    {
        if (Schema::hasTable('hr_employees')) {
            $employee = Employee::query()
                ->with('user.roles')
                ->active()
                ->where('company_id', $companyId)
                ->when($branchId && Schema::hasColumn('hr_employees', 'branch_id'), fn ($q) => $q->where('branch_id', $branchId))
                ->whereKey($workerId)
                ->first();

            if (! $employee) {
                throw new \InvalidArgumentException('Mfanyakazi hajapatikana.');
            }

            $user = $employee->user;
            $label = $employee->full_name;
        } else {
            $user = User::query()
                ->with('roles')
                ->where('company_id', $companyId)
                ->where('status', 'active')
                ->whereKey($workerId)
                ->when($branchId && Schema::hasTable('branch_user'), function ($q) use ($branchId) {
                    $q->where(function ($inner) use ($branchId) {
                        $inner->whereHas('branches', fn ($bq) => $bq->where('branches.id', $branchId));
                        if (Schema::hasColumn('users', 'branch_id')) {
                            $inner->orWhere('branch_id', $branchId);
                        }
                    });
                })
                ->first();

            if (! $user) {
                throw new \InvalidArgumentException('Mfanyakazi hajapatikana.');
            }

            $employee = null;
            $label = $user->name;
        }

        if ($user && (int) $user->id === (int) $actor->id) {
            throw new \InvalidArgumentException('Huwezi kufuta akaunti yako mwenyewe.');
        }

        if ($user && $this->userHasProtectedRole($user)) {
            throw new \InvalidArgumentException('Huwezi kufuta mfanyakazi mwenye jukumu hili.');
        }

        DB::transaction(function () use ($employee, $user) {
            if ($employee) {
                $employee->update(['status' => 'inactive']);
            }

            if ($user) {
                $user->update([
                    'status' => 'inactive',
                    'is_active' => 'no',
                ]);
            }
        });

        return $label;
    }

    private function userHasProtectedRole(User $user): bool
    {
        return $user->hasRole('super-admin')
            || $user->hasRole('Super Admin')
            || $user->hasRole('md')
            || $user->hasRole('Md')
            || $user->hasRole('admin');
    }
}
