<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Initialize all default settings - this will update existing values to defaults
        SystemSetting::initializeDefaults();
        
        // Seed hotel discount expense account setting if missing
        SystemSetting::setValue(
            'hotel_discount_expense_account_id',
            '172', // seeded Discount Expense in ChartAccountSeeder
            'integer',
            'hotel_property',
            'Hotel Discount Expense Account'
        );

        // Clear all caches to ensure fresh values are loaded
        SystemSetting::clearCache();

        $this->command->info('System settings initialized with default values successfully!');
    }
}
