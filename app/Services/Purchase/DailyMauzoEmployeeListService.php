<?php

namespace App\Services\Purchase;

use App\Models\Hr\Employee;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class DailyMauzoEmployeeListService
{
    public const SALES_PERSON_ROLE = 'sales person';

    /**
     * @return Collection<int, object{id: int, display_name: string, employee_number: ?string, phone: string, role_name: string, user_id: ?int}>
     */
    public function listWorkersForManagement(int $companyId, ?int $branchId): Collection
    {
        if (Schema::hasTable('hr_employees')) {
            return Employee::query()
                ->with(['user.roles'])
                ->active()
                ->forCompanyBranch($companyId, $branchId)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get()
                ->map(function (Employee $employee) {
                    $user = $employee->user;

                    return (object) [
                        'id' => $employee->id,
                        'display_name' => $employee->full_name,
                        'employee_number' => $employee->employee_number,
                        'phone' => $user->phone ?? '—',
                        'role_name' => $user?->roles->first()?->name ?? '—',
                        'user_id' => $user?->id,
                    ];
                });
        }

        return $this->usersFallbackQuery($companyId, $branchId)
            ->with('roles')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => (object) [
                'id' => $user->id,
                'display_name' => $user->name,
                'employee_number' => null,
                'phone' => $user->phone ?? '—',
                'role_name' => $user->roles->first()?->name ?? '—',
                'user_id' => $user->id,
            ]);
    }

    public function workerExistsForCompanyBranch(int $workerId, int $companyId, ?int $branchId): bool
    {
        return $this->employeeExistsForCompanyBranch($workerId, $companyId, $branchId);
    }

    /**
     * @return Collection<int, object{id: int, display_name: string, employee_number: ?string}>
     */
    public function listForCompanyBranch(int $companyId, ?int $branchId): Collection
    {
        if (Schema::hasTable('hr_employees')) {
            return Employee::query()
                ->active()
                ->forCompanyBranch($companyId, $branchId)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'middle_name', 'last_name', 'employee_number'])
                ->map(fn (Employee $e) => (object) [
                    'id' => $e->id,
                    'display_name' => $e->full_name,
                    'employee_number' => $e->employee_number,
                ]);
        }

        return $this->usersFallbackQuery($companyId, $branchId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $u) => (object) [
                'id' => $u->id,
                'display_name' => $u->name,
                'employee_number' => null,
            ]);
    }

    /**
     * Employees linked to users with the sales person role (for daily accounts report).
     *
     * @return Collection<int, object{id: int, display_name: string, employee_number: ?string}>
     */
    public function listSalesPersonsForCompanyBranch(int $companyId, ?int $branchId): Collection
    {
        if (Schema::hasTable('hr_employees')) {
            return Employee::query()
                ->active()
                ->forCompanyBranch($companyId, $branchId)
                ->whereNotNull('user_id')
                ->whereHas('user', fn ($q) => $q->role(self::SALES_PERSON_ROLE))
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'middle_name', 'last_name', 'employee_number'])
                ->map(fn (Employee $e) => (object) [
                    'id' => $e->id,
                    'display_name' => $e->full_name,
                    'employee_number' => $e->employee_number,
                ]);
        }

        return $this->usersFallbackQuery($companyId, $branchId)
            ->role(self::SALES_PERSON_ROLE)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $u) => (object) [
                'id' => $u->id,
                'display_name' => $u->name,
                'employee_number' => null,
            ]);
    }

    public function employeeExistsForCompanyBranch(int $employeeId, int $companyId, ?int $branchId): bool
    {
        if (Schema::hasTable('hr_employees')) {
            return Employee::query()
                ->active()
                ->forCompanyBranch($companyId, $branchId)
                ->whereKey($employeeId)
                ->exists();
        }

        return $this->usersFallbackQuery($companyId, $branchId)
            ->whereKey($employeeId)
            ->exists();
    }

    public function salesPersonExistsForCompanyBranch(int $employeeId, int $companyId, ?int $branchId): bool
    {
        if (Schema::hasTable('hr_employees')) {
            return Employee::query()
                ->active()
                ->forCompanyBranch($companyId, $branchId)
                ->whereNotNull('user_id')
                ->whereHas('user', fn ($q) => $q->role(self::SALES_PERSON_ROLE))
                ->whereKey($employeeId)
                ->exists();
        }

        return $this->usersFallbackQuery($companyId, $branchId)
            ->role(self::SALES_PERSON_ROLE)
            ->whereKey($employeeId)
            ->exists();
    }

    private function usersFallbackQuery(int $companyId, ?int $branchId)
    {
        $query = User::query()
            ->where('company_id', $companyId)
            ->where('status', 'active');

        if ($branchId && Schema::hasTable('branch_user')) {
            $query->where(function ($q) use ($branchId) {
                $q->whereHas('branches', fn ($bq) => $bq->where('branches.id', $branchId));
                if (Schema::hasColumn('users', 'branch_id')) {
                    $q->orWhere('branch_id', $branchId);
                }
            });
        }

        return $query;
    }
}
