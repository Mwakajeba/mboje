<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MainGroup;
use App\Models\AccountClass;

class MainGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get account classes
        $assets = AccountClass::where('name', 'Assets')->first();
        $liabilities = AccountClass::where('name', 'Liabilities')->first();
        $equity = AccountClass::where('name', 'Equity')->first();
        $revenue = AccountClass::where('name', 'Revenue')->first();
        $expenses = AccountClass::where('name', 'Expenses')->first();

        $company_id = 1; // Default company ID

        $mainGroups = [
            // ASSETS Main Groups (class_id = 1)
            [
                'class_id' => $assets->id,
                'name' => 'Non Current Assets',
                'description' => 'This is the main Group of Assets that are expected to generate economic benefits to the entity for more than one accounting period',
                'status' => true,
                'company_id' => $company_id,
            ],
            [
                'class_id' => $assets->id,
                'name' => 'Current Assets',
                'description' => 'This is a group of assets that are expected to generate economic benefits to the entity within the next 12 months',
                'status' => true,
                'company_id' => $company_id,
            ],

            // LIABILITIES Main Groups (class_id = 2)
            [
                'class_id' => $liabilities->id,
                'name' => 'Non-Current Liabilities',
                'description' => 'These are long term obligations to the entity for which the economic benefits will flow from the entity for more that 12 months',
                'status' => true,
                'company_id' => $company_id,
            ],
            [
                'class_id' => $liabilities->id,
                'name' => 'Current Liabilities',
                'description' => 'These are short term obligations that are expected to be settled within the next 12 months',
                'status' => true,
                'company_id' => $company_id,
            ],

            // EQUITY Main Groups (class_id = 5)
            [
                'class_id' => $equity->id,
                'name' => 'Share Capital & Reserves',
                'description' => 'This is the main group for all capital and other reserves',
                'status' => true,
                'company_id' => $company_id,
            ],

            // REVENUE Main Groups (class_id = 3)
            [
                'class_id' => $revenue->id,
                'name' => 'Operating Revenue Category',
                'description' => 'This includes IFRS 15 revenue categories as per IFRS 18',
                'status' => true,
                'company_id' => $company_id,
            ],
            [
                'class_id' => $revenue->id,
                'name' => 'Other Operating Income Category',
                'description' => 'Other Income categories apart from the main core business income category but from Investing activities',
                'status' => true,
                'company_id' => $company_id,
            ],
            [
                'class_id' => $revenue->id,
                'name' => 'Investing Category',
                'description' => 'Income/expenses from financial investments not part of main business',
                'status' => true,
                'company_id' => $company_id,
            ],

            // EXPENSES Main Groups (class_id = 4)
            [
                'class_id' => $expenses->id,
                'name' => 'Operating Expenses Category',
                'description' => 'Operating Expenses for the business to keep its operations running as usual without interruptions',
                'status' => true,
                'company_id' => $company_id,
            ],
            [
                'class_id' => $expenses->id,
                'name' => 'Cost of Sales (COS)',
                'description' => 'Cost of Sales (COS) for direct costs related to generation of income',
                'status' => true,
                'company_id' => $company_id,
            ],
            [
                'class_id' => $expenses->id,
                'name' => 'Financing Category',
                'description' => 'costs/income from financing activities',
                'status' => true,
                'company_id' => $company_id,
            ],
        ];

        foreach ($mainGroups as $group) {
            MainGroup::create($group);
        }
    }
}