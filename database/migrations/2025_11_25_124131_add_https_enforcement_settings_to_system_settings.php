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
        // Initialize HTTPS enforcement settings if they don't exist
        // This ensures existing databases get the new settings
        SystemSetting::initializeDefaults();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally remove HTTPS enforcement settings
        // Note: This is optional as settings can remain without causing issues
        $httpsSettingsKeys = [
            'security_force_https',
            'security_enable_hsts',
            'security_hsts_max_age',
            'security_hsts_include_subdomains',
            'security_hsts_preload',
            'security_enforce_secure_cookies',
            'security_content_security_policy',
            'security_referrer_policy',
            'security_permissions_policy',
        ];

        foreach ($httpsSettingsKeys as $key) {
            SystemSetting::where('key', $key)->delete();
        }

        SystemSetting::clearCache();
    }
};
