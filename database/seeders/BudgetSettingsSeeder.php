<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class BudgetSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Budget Check Enabled
        SystemSetting::updateOrCreate(
            ['key' => 'budget_check_enabled'],
            [
                'value' => '0',
                'type' => 'boolean',
                'group' => 'budget',
                'label' => 'Enable Budget Checking',
                'description' => 'Enable or disable budget checking for expenses. When enabled, the system will check if expenses exceed budget limits.',
                'is_public' => false,
            ]
        );

        // Budget Over Budget Percentage Allowed
        SystemSetting::updateOrCreate(
            ['key' => 'budget_over_budget_percentage'],
            [
                'value' => '10',
                'type' => 'integer',
                'group' => 'budget',
                'label' => 'Over Budget Percentage Allowed',
                'description' => 'Percentage over budget that is allowed before blocking expenses. For example, 10 means expenses can exceed budget by up to 10%.',
                'is_public' => false,
            ]
        );

        // Require Budget Allocation
        SystemSetting::updateOrCreate(
            ['key' => 'budget_require_allocation'],
            [
                'value' => '0',
                'type' => 'boolean',
                'group' => 'budget',
                'label' => 'Require Budget Allocation',
                'description' => 'When enabled, payment vouchers for accounts not included in the budget will be blocked. When disabled, they will be allowed with a warning.',
                'is_public' => false,
            ]
        );
    }
}
