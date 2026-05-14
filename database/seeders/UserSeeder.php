<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Branch;
use App\Models\InventoryLocation;
use App\Models\Hr\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Check if any user already exists
        if (User::exists()) {
            $this->command->warn('Users already exist. Skipping seeding.');
            return;
        }
        // Get all branches
        $branches = Branch::all();

        if ($branches->isEmpty()) {
            $this->command->warn('No branches found. Seed branches first.');
            return;
        }

        // Seed one user per branch - first user as super-admin, others as admin
        foreach ($branches as $index => $branch) {
            // First user is super-admin, others are admin
            $role = $index === 0 ? 'super-admin' : 'admin';
            $name = $index === 0 ? 'Julius Mwakajeba (Super Admin)' : 'Julius Mwakajeba ' . $index;

            $user = User::create([
                'name' => $name,
                'phone' => '255655577803' . $index,
                'email' => 'admin' . $index . '@safco.com',
                'password' => Hash::make('12345'),
                // 'branch_id' => $branch->id,
                'company_id' => $branch->company_id,
                'role' => $role,
                'is_active' => 'yes',
                'sms_verification_code' => '654321',
                'sms_verified_at' => now(),
            ]);

            // Assign appropriate role using Spatie permissions
            $user->assignRole($role);

            // Create corresponding employee record - DISABLED
            // $this->createEmployeeForUser($user, $branch, $index);

            // Ensure super admin is attached to Main Branch in pivot table and assigned its locations
            if ($role === 'super-admin') {
                $mainBranch = Branch::where('name', 'Main Branch')->first();
                if ($mainBranch) {
                    // Attach without removing any existing pivot links if seeder runs again
                    $user->branches()->syncWithoutDetaching([$mainBranch->id]);

                    // Attach all locations under Main Branch to this user; set the first as default
                    $mainBranchLocations = InventoryLocation::where('branch_id', $mainBranch->id)->get();
                    if ($mainBranchLocations->isNotEmpty()) {
                        // Build sync array with is_default=false and branch_id
                        $syncData = [];
                        foreach ($mainBranchLocations as $location) {
                            $syncData[$location->id] = [
                                'is_default' => false,
                                'branch_id' => $location->branch_id
                            ];
                        }
                        $user->locations()->syncWithoutDetaching($syncData);

                        // Set the first location as default
                        $defaultLocationId = $mainBranchLocations->first()->id;
                        DB::table('location_user')
                            ->where('user_id', $user->id)
                            ->update(['is_default' => false]);
                        DB::table('location_user')
                            ->where('user_id', $user->id)
                            ->where('inventory_location_id', $defaultLocationId)
                            ->update(['is_default' => true]);
                    }
                }
            }
        }
    }

    /**
     * Create an employee record for a user
     *
     * @param User $user
     * @param Branch $branch
     * @param int $index
     * @return Employee
     */
    private function createEmployeeForUser(User $user, Branch $branch, int $index): Employee
    {
        // Generate employee number
        $lastEmployee = Employee::where('company_id', $user->company_id)
            ->where('employee_number', 'like', 'EMP%')
            ->orderBy('employee_number', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastEmployee) {
            $lastNumber = (int) str_replace('EMP', '', $lastEmployee->employee_number);
            $nextNumber = $lastNumber + 1;
        } else {
            // Start from index to avoid conflicts when multiple users are created
            $nextNumber = $index + 1;
        }

        $employeeNumber = 'EMP' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        // Parse user name to get first and last name
        $nameParts = explode(' ', $user->name);
        $firstName = $nameParts[0] ?? 'Julius';
        $lastName = end($nameParts) !== $firstName ? end($nameParts) : 'Mwakajeba';
        $middleName = count($nameParts) > 2 ? implode(' ', array_slice($nameParts, 1, -1)) : null;

        // Create employee record
        $employee = Employee::create([
            'company_id' => $user->company_id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'employee_number' => $employeeNumber,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'marital_status' => 'single',
            'country' => 'Tanzania',
            'region' => 'Dar es Salaam',
            'district' => 'Ilala',
            'current_physical_location' => 'Dar es Salaam, Tanzania',
            'email' => $user->email,
            'phone_number' => $user->phone,
            'basic_salary' => 1000000.00,
            'identity_document_type' => 'NIDA',
            'identity_number' => 'NIDA' . str_pad($index + 1, 10, '0', STR_PAD_LEFT),
            'employment_type' => 'full_time',
            'date_of_employment' => now()->subYears(2),
            'designation' => $index === 0 ? 'System Administrator' : 'Administrator',
            'status' => 'active',
            'include_in_payroll' => true,
            'has_nhif' => false,
            'has_pension' => false,
            'has_trade_union' => false,
            'has_wcf' => false,
            'has_heslb' => false,
            'has_sdl' => false,
        ]);

        $this->command->info("Created employee record for user: {$user->name} (Employee #: {$employeeNumber})");

        return $employee;
    }
}
