<?php

namespace Database\Seeders;

use App\Models\AccountClass;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AccountGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accountGroups = [
            // Assets (Class ID: 1)
            ['id' => 1, 'name' => 'Current Assets', 'class_id' => 1, 'group_code' => 'CA'],
            ['id' => 2, 'name' => 'Non Current Assets', 'class_id' => 1, 'group_code' => 'NCA'],
            
            // Liabilities (Class ID: 2)
            ['id' => 5, 'name' => 'Current Liabilities', 'class_id' => 2, 'group_code' => 'CL'],
            ['id' => 6, 'name' => 'Non Current Liabilities', 'class_id' => 2, 'group_code' => 'NCL'],
            
            // Equity (Class ID: 5)
            ['id' => 7, 'name' => 'Share Capital', 'class_id' => 5, 'group_code' => 'SC'],
            ['id' => 8, 'name' => 'Retained Earnings', 'class_id' => 5, 'group_code' => 'RE'],
            ['id' => 9, 'name' => 'Other Equity', 'class_id' => 5, 'group_code' => 'OEQ'],
            
            // Revenue (Class ID: 3)
            ['id' => 10, 'name' => 'Sales Revenue', 'class_id' => 3, 'group_code' => 'SR'],
            ['id' => 11, 'name' => 'Other Income', 'class_id' => 3, 'group_code' => 'OI'],
            ['id' => 12, 'name' => 'Interest Income', 'class_id' => 3, 'group_code' => 'II'],
            
            // Expenses (Class ID: 4)
            ['id' => 13, 'name' => 'Cost of Goods Sold', 'class_id' => 4, 'group_code' => 'COGS'],
            ['id' => 14, 'name' => 'Operating Expenses', 'class_id' => 4, 'group_code' => 'OPEX'],
            ['id' => 15, 'name' => 'Administrative Expenses', 'class_id' => 4, 'group_code' => 'ADMIN'],
            ['id' => 16, 'name' => 'Financial Expenses', 'class_id' => 4, 'group_code' => 'FIN'],
        ];

        foreach ($accountGroups as $group) {
            DB::table('account_class_groups')->updateOrInsert(
                ['id' => $group['id']],
                [
                    'class_id' => $group['class_id'],
                    'company_id' => 1, // Default company_id for seeding
                    'group_code' => $group['group_code'],
                    'name' => $group['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
