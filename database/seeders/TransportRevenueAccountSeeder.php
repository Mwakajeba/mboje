<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransportRevenueAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find Revenue class
        $revenueClass = \App\Models\AccountClass::where('name', 'revenue')->first();
        if (!$revenueClass) {
            return;
        }
        
        // Find existing Revenue group (company 1)
        $revenueGroup = \App\Models\AccountClassGroup::where('name', 'Revenue')
            ->where('company_id', 1)
            ->first();
            
        if (!$revenueGroup) {
            return;
        }
        
        // Find or create Transport Revenue account
        $transportRevenueAccount = \App\Models\ChartAccount::firstOrCreate([
            'account_name' => 'Transport Revenue',
            'account_code' => '4205',
            'account_class_group_id' => $revenueGroup->id
        ]);
        
        // Add system setting for transport revenue account
        \App\Models\SystemSetting::updateOrCreate([
            'key' => 'inventory_default_transport_revenue_account'
        ], [
            'value' => $transportRevenueAccount->id,
            'label' => 'Transport Revenue Account',
            'description' => 'Default account for transport revenue',
            'type' => 'select',
            'group' => 'inventory'
        ]);
    }
}
