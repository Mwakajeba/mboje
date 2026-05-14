<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountClassGroup;
use App\Models\MainGroup;

class AccountClassGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get main groups (using updated names from MainGroupSeeder)
        $nonCurrentAssets = MainGroup::where('name', 'Non Current Assets')->first();
        $currentAssets = MainGroup::where('name', 'Current Assets')->first();
        $nonCurrentLiabilities = MainGroup::where('name', 'Non-Current Liabilities')->first();
        $currentLiabilities = MainGroup::where('name', 'Current Liabilities')->first();
        $shareCapitalReserves = MainGroup::where('name', 'Share Capital & Reserves')->first();
        $operatingRevenueCategory = MainGroup::where('name', 'Operating Revenue Category')->first();
        $otherOperatingIncomeCategory = MainGroup::where('name', 'Other Operating Income Category')->first();
        $operatingExpensesCategory = MainGroup::where('name', 'Operating Expenses Category')->first();
        $financingCategory = MainGroup::where('name', 'Financing Category')->first();
        $investingCategory = MainGroup::where('name', 'Investing Category')->first();

        $company_id = 1; // Default company ID

        $accountClassGroups = [
            // CURRENT ASSETS Groups (main_group_id = 2)
            [
                'main_group_id' => $currentAssets->id,
                'class_id' => $currentAssets->class_id,
                'group_code' => 'CA',
                'name' => 'Trade & Other Receivables',
                'company_id' => $company_id,
            ],
            [
                'main_group_id' => $currentAssets->id,
                'class_id' => $currentAssets->class_id,
                'group_code' => 'CCE',
                'name' => 'Cash & Cash Equivalents (IAS 7)',
                'company_id' => $company_id,
            ],
            [
                'main_group_id' => $currentAssets->id,
                'class_id' => $currentAssets->class_id,
                'group_code' => 'I',
                'name' => 'Inventory (IAS 2)',
                'company_id' => $company_id,
            ],
            [
                'main_group_id' => $currentAssets->id,
                'class_id' => $currentAssets->class_id,
                'group_code' => 'POCA',
                'name' => 'Prepayments & Other Current Assets',
                'company_id' => $company_id,
            ],
            [
                'main_group_id' => $currentAssets->id,
                'class_id' => $currentAssets->class_id,
                'group_code' => 'NCA-HFS',
                'name' => 'Non-Current Assets Held for Sale',
                'company_id' => $company_id,
            ],

            // NON-CURRENT ASSETS Groups (main_group_id = 1)
            [
                'main_group_id' => $nonCurrentAssets->id,
                'class_id' => $nonCurrentAssets->class_id,
                'group_code' => 'NCA',
                'name' => 'Property, Plant & Equipment (PPE)',
                'company_id' => $company_id,
            ],
            [
                'main_group_id' => $nonCurrentAssets->id,
                'class_id' => $nonCurrentAssets->class_id,
                'group_code' => 'ADA & I',
                'name' => 'Accumulated Depreciation, Amortization & Impairment',
                'company_id' => $company_id,
            ],
            [
                'main_group_id' => $nonCurrentAssets->id,
                'class_id' => $nonCurrentAssets->class_id,
                'group_code' => 'RoUA',
                'name' => 'Right of Use Assets (IFRS 16)',
                'company_id' => $company_id,
            ],
            [
                'main_group_id' => $nonCurrentAssets->id,
                'class_id' => $nonCurrentAssets->class_id,
                'group_code' => 'IA',
                'name' => 'Intangible Assets (IAS 38)',
                'company_id' => $company_id,
            ],
            [
                'main_group_id' => $nonCurrentAssets->id,
                'class_id' => $nonCurrentAssets->class_id,
                'group_code' => 'ONCA',
                'name' => 'Other Non-Current Assets',
                'company_id' => $company_id,
            ],
            [
                'main_group_id' => $nonCurrentAssets->id,
                'class_id' => $nonCurrentAssets->class_id,
                'group_code' => 'IP',
                'name' => 'Investment Properties',
                'company_id' => $company_id,
            ],
            [
                'main_group_id' => $nonCurrentAssets->id,
                'class_id' => $nonCurrentAssets->class_id,
                'group_code' => 'FAI',
                'name' => 'Financial Assets Investment',
                'company_id' => $company_id,
            ],

            // NON-CURRENT LIABILITIES Groups (main_group_id = 3)
            [
                'main_group_id' => $nonCurrentLiabilities->id,
                'class_id' => $nonCurrentLiabilities->class_id,
                'group_code' => 'NCL',
                'name' => 'Borrowings',
                'company_id' => $company_id,
            ],
            [
                'main_group_id' => $nonCurrentLiabilities->id,
                'class_id' => $nonCurrentLiabilities->class_id,
                'group_code' => 'LL',
                'name' => 'Lease Liabilities (IFRS 16)',
                'company_id' => $company_id,
            ],
            [
                'main_group_id' => $nonCurrentLiabilities->id,
                'class_id' => $nonCurrentLiabilities->class_id,
                'group_code' => 'DL',
                'name' => 'Deferred Liabilities',
                'company_id' => $company_id,
            ],

            // CURRENT LIABILITIES Groups (main_group_id = 4)
            [
                'main_group_id' => $currentLiabilities->id,
                'class_id' => $currentLiabilities->class_id,
                'group_code' => 'CL',
                'name' => 'Trade & Other Payables',
                'company_id' => $company_id,
            ],
            [
                'main_group_id' => $currentLiabilities->id,
                'class_id' => $currentLiabilities->class_id,
                'group_code' => 'SB',
                'name' => 'Short-term Borrowings',
                'company_id' => $company_id,
            ],
            [
                'main_group_id' => $currentLiabilities->id,
                'class_id' => $currentLiabilities->class_id,
                'group_code' => 'P',
                'name' => 'Provisions (IAS 37)',
                'company_id' => $company_id,
            ],
            [
                'main_group_id' => $currentLiabilities->id,
                'class_id' => $currentLiabilities->class_id,
                'group_code' => 'OCL',
                'name' => 'Other Current Liabilities',
                'company_id' => $company_id,
            ],
            [
                'main_group_id' => $currentLiabilities->id,
                'class_id' => $currentLiabilities->class_id,
                'group_code' => 'LAA-HFS',
                'name' => 'Liabilities Associated with Assets Held for Sale',
                'company_id' => $company_id,
            ],

            // SHARE CAPITAL & RESERVES Groups (main_group_id = 5)
            [
                'main_group_id' => $shareCapitalReserves->id,
                'class_id' => $shareCapitalReserves->class_id,
                'group_code' => 'SC',
                'name' => 'Share Capital',
                'company_id' => $company_id,
            ],
            [
                'main_group_id' => $shareCapitalReserves->id,
                'class_id' => $shareCapitalReserves->class_id,
                'group_code' => 'RE',
                'name' => 'Reserves',
                'company_id' => $company_id,
            ],
            [
                'main_group_id' => $shareCapitalReserves->id,
                'class_id' => $shareCapitalReserves->class_id,
                'group_code' => 'DIV',
                'name' => 'Dividends',
                'company_id' => $company_id,
            ],

            // OPERATING REVENUE CATEGORY Groups (main_group_id = 6)
            [
                'main_group_id' => $operatingRevenueCategory->id,
                'class_id' => $operatingRevenueCategory->class_id,
                'group_code' => 'SR',
                'name' => 'Sales Revenue',
                'company_id' => $company_id,
            ],
            [
                'main_group_id' => $operatingRevenueCategory->id,
                'class_id' => $operatingRevenueCategory->class_id,
                'group_code' => 'COS',
                'name' => 'Cost of Sales',
                'company_id' => $company_id,
            ],

            // OTHER OPERATING INCOME CATEGORY Groups (main_group_id = 7)
            [
                'main_group_id' => $otherOperatingIncomeCategory->id,
                'class_id' => $otherOperatingIncomeCategory->class_id,
                'group_code' => 'OI',
                'name' => 'Other Income',
                'company_id' => $company_id,
            ],

            // INVESTING CATEGORY Groups (main_group_id = 15)
            [
                'main_group_id' => $investingCategory->id,
                'class_id' => $investingCategory->class_id,
                'group_code' => 'INVI',
                'name' => 'Investment Income',
                'company_id' => $company_id,
            ],

            // OPERATING EXPENSES CATEGORY Groups (main_group_id = 9)
            [
                'main_group_id' => $operatingExpensesCategory->id,
                'class_id' => $operatingExpensesCategory->class_id,
                'group_code' => 'S&D',
                'name' => 'Selling & Distribution',
                'company_id' => $company_id,
            ],
            [
                'main_group_id' => $operatingExpensesCategory->id,
                'class_id' => $operatingExpensesCategory->class_id,
                'group_code' => 'ADMIN',
                'name' => 'Administrative Expenses',
                'company_id' => $company_id,
            ],
            [
                'main_group_id' => $operatingExpensesCategory->id,
                'class_id' => $operatingExpensesCategory->class_id,
                'group_code' => 'DAI',
                'name' => 'Depreciation, Amortization & Impairment',
                'company_id' => $company_id,
            ],
            [
                'main_group_id' => $operatingExpensesCategory->id,
                'class_id' => $operatingExpensesCategory->class_id,
                'group_code' => 'TX',
                'name' => 'Taxation',
                'company_id' => $company_id,
            ],
            [
                'main_group_id' => $operatingExpensesCategory->id,
                'class_id' => $operatingExpensesCategory->class_id,
                'group_code' => 'EB',
                'name' => 'Employee Benefits',
                'company_id' => $company_id,
            ],

            // FINANCING CATEGORY Groups (main_group_id = 14)
            [
                'main_group_id' => $financingCategory->id,
                'class_id' => $financingCategory->class_id,
                'group_code' => 'FC',
                'name' => 'Finance Costs (IAS 23, IFRS 9)',
                'company_id' => $company_id,
            ],
        ];

        foreach ($accountClassGroups as $group) {
            // Use updateOrCreate so that rerunning the seeder is idempotent
            AccountClassGroup::updateOrCreate(
                [
                    'name' => $group['name'],
                    'company_id' => $company_id,
                ],
                $group
            );
        }
    }
}
