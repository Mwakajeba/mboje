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
        // Initialize rate limiting settings if they don't exist
        // This ensures existing databases get the new settings
        SystemSetting::initializeDefaults();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally remove rate limiting settings
        // Note: This is optional as settings can remain without causing issues
        $rateLimitKeys = [
            'rate_limit_login_attempts',
            'rate_limit_login_decay',
            'rate_limit_login_per_ip_attempts',
            'rate_limit_password_reset_attempts',
            'rate_limit_password_reset_decay',
            'rate_limit_otp_attempts',
            'rate_limit_otp_decay',
            'rate_limit_api_auth_attempts',
            'rate_limit_api_auth_decay',
            'rate_limit_api_unauth_attempts',
            'rate_limit_api_unauth_decay',
            'rate_limit_global_attempts',
            'rate_limit_global_decay',
            'rate_limit_registration_attempts',
            'rate_limit_registration_decay',
            'rate_limit_search_attempts',
            'rate_limit_search_decay',
            'rate_limit_upload_attempts',
            'rate_limit_upload_decay',
        ];

        foreach ($rateLimitKeys as $key) {
            SystemSetting::where('key', $key)->delete();
        }

        SystemSetting::clearCache();
    }
};
