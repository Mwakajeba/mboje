<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add default VAT type setting to system_settings table
        \App\Models\SystemSetting::updateOrCreate(
            ['key' => 'inventory_default_vat_type'],
            [
                'value' => 'inclusive',
                'type' => 'string',
                'group' => 'inventory',
                'label' => 'Default VAT Type',
                'description' => 'Default VAT type for inventory items (inclusive, exclusive, or no_vat)',
                'is_public' => false
            ]
        );

        // Add default VAT rate setting if it doesn't exist
        \App\Models\SystemSetting::updateOrCreate(
            ['key' => 'inventory_default_vat_rate'],
            [
                'value' => '18.00',
                'type' => 'decimal',
                'group' => 'inventory',
                'label' => 'Default VAT Rate (%)',
                'description' => 'Default VAT rate percentage for inventory items',
                'is_public' => false
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \App\Models\SystemSetting::where('key', 'inventory_default_vat_type')->delete();
        \App\Models\SystemSetting::where('key', 'inventory_default_vat_rate')->delete();
    }
};