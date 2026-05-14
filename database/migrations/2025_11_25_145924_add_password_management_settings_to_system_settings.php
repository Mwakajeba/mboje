<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\SystemSetting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Initialize password management settings if they don't exist
        SystemSetting::initializeDefaults();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally remove password management settings
        $passwordSettingsKeys = [
            'password_history_count',
            'password_expiration_days',
            'password_expiration_warning_days',
            'password_enable_blacklist',
            'password_custom_blacklist',
            'password_require_strength_meter',
            'password_min_strength_score',
        ];

        foreach ($passwordSettingsKeys as $key) {
            SystemSetting::where('key', $key)->delete();
        }

        SystemSetting::clearCache();
    }
};
