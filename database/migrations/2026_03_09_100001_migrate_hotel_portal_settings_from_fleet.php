<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fleet_system_settings')) {
            return;
        }

        $hotelKeys = [
            'enable_booking_portal',
            'require_admin_approval',
            'online_booking_expiry_hours',
            'booking_availability_hours',
            'enable_dynamic_pricing',
            'weekend_rate_multiplier',
            'enable_promo_codes',
            'enable_captcha',
            'portal_notification_email',
            'portal_notification_sms',
            'enable_email_verification',
            'portal_tax_rate',
            'portal_terms_conditions',
        ];

        $rows = DB::table('fleet_system_settings')
            ->whereIn('setting_key', $hotelKeys)
            ->get();

        foreach ($rows as $row) {
            DB::table('hotel_portal_settings')->updateOrInsert(
                [
                    'company_id' => $row->company_id,
                    'setting_key' => $row->setting_key,
                ],
                [
                    'setting_value' => $row->setting_value,
                    'updated_by' => $row->updated_by ?? null,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // No rollback - data stays in hotel_portal_settings
    }
};
