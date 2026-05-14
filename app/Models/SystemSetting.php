<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_public'
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Get a setting value by key
     */
    public static function getValue($key, $default = null)
    {
        $cacheKey = "system_setting_{$key}";

        try {
            return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
                $setting = self::where('key', $key)->first();

                if (!$setting) {
                    return $default;
                }

                return self::castValue($setting->value, $setting->type);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // If cache driver fails (e.g., PDO driver not available), try direct database query
            if (str_contains($e->getMessage(), 'could not find driver')) {
                try {
                    $setting = self::where('key', $key)->first();
                    return $setting ? self::castValue($setting->value, $setting->type) : $default;
                } catch (\Exception $dbException) {
                    // If database also fails, return default
                    return $default;
                }
            }
            // For other database errors, return default
            return $default;
        } catch (\Exception $e) {
            // For any other cache errors, try direct database query
            try {
                $setting = self::where('key', $key)->first();
                return $setting ? self::castValue($setting->value, $setting->type) : $default;
            } catch (\Exception $dbException) {
                return $default;
            }
        }
    }

    /**
     * Set a setting value
     */
    public static function setValue($key, $value, $type = 'string', $group = 'general', $label = null, $description = null)
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'group' => $group,
                'label' => $label ?? ucwords(str_replace('_', ' ', $key)),
                'description' => $description
            ]
        );

        // Clear cache
        Cache::forget("system_setting_{$key}");

        return $setting;
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup($group)
    {
        return self::where('group', $group)->get();
    }

    /**
     * Get all settings as key-value pairs
     */
    public static function getAllAsArray()
    {
        return Cache::remember('all_system_settings', 3600, function () {
            $settings = self::all();
            $result = [];

            foreach ($settings as $setting) {
                $result[$setting->key] = self::castValue($setting->value, $setting->type);
            }

            return $result;
        });
    }

    /**
     * Cast value based on type
     */
    private static function castValue($value, $type)
    {
        if ($value === null) {
            return null;
        }

        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'json':
                return json_decode($value, true);
            case 'array':
                return explode(',', $value);
            default:
                return $value;
        }
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache()
    {
        Cache::forget('all_system_settings');

        $settings = self::all();
        foreach ($settings as $setting) {
            Cache::forget("system_setting_{$setting->key}");
        }
    }

    /**
     * Initialize default settings
     */
    public static function initializeDefaults()
    {
        $defaults = [
            // General Settings
            'app_name' => ['value' => 'SmartAccounting', 'type' => 'string', 'group' => 'general', 'label' => 'Application Name'],
            'app_url' => ['value' => config('app.url'), 'type' => 'string', 'group' => 'general', 'label' => 'Application URL'],
            'timezone' => ['value' => 'Africa/Dar_es_Salaam', 'type' => 'string', 'group' => 'general', 'label' => 'Timezone'],
            'locale' => ['value' => 'sw', 'type' => 'string', 'group' => 'general', 'label' => 'Default Language'],
            'date_format' => ['value' => 'Y-m-d', 'type' => 'string', 'group' => 'general', 'label' => 'Date Format'],
            'time_format' => ['value' => 'H:i:s', 'type' => 'string', 'group' => 'general', 'label' => 'Time Format'],
            'currency' => ['value' => 'TZS', 'type' => 'string', 'group' => 'general', 'label' => 'Default Currency'],
            'currency_symbol' => ['value' => 'TSh', 'type' => 'string', 'group' => 'general', 'label' => 'Currency Symbol'],

            // Email Settings
            'mail_driver' => ['value' => config('mail.default'), 'type' => 'string', 'group' => 'email', 'label' => 'Mail Driver'],
            'mail_host' => ['value' => config('mail.mailers.smtp.host'), 'type' => 'string', 'group' => 'email', 'label' => 'SMTP Host'],
            'mail_port' => ['value' => config('mail.mailers.smtp.port'), 'type' => 'integer', 'group' => 'email', 'label' => 'SMTP Port'],
            'mail_username' => ['value' => config('mail.mailers.smtp.username'), 'type' => 'string', 'group' => 'email', 'label' => 'SMTP Username'],
            'mail_password' => ['value' => config('mail.mailers.smtp.password'), 'type' => 'string', 'group' => 'email', 'label' => 'SMTP Password'],
            'mail_encryption' => ['value' => config('mail.mailers.smtp.encryption'), 'type' => 'string', 'group' => 'email', 'label' => 'SMTP Encryption'],
            'mail_from_address' => ['value' => config('mail.from.address'), 'type' => 'string', 'group' => 'email', 'label' => 'From Email Address'],
            'mail_from_name' => ['value' => config('mail.from.name'), 'type' => 'string', 'group' => 'email', 'label' => 'From Name'],

            // Security Settings
            'session_lifetime' => ['value' => config('session.lifetime'), 'type' => 'integer', 'group' => 'security', 'label' => 'Session Lifetime (minutes)'],
            'password_min_length' => ['value' => 8, 'type' => 'integer', 'group' => 'security', 'label' => 'Minimum Password Length'],
            'password_require_special' => ['value' => true, 'type' => 'boolean', 'group' => 'security', 'label' => 'Require Special Characters'],
            'password_require_numbers' => ['value' => true, 'type' => 'boolean', 'group' => 'security', 'label' => 'Require Numbers'],
            'password_require_uppercase' => ['value' => true, 'type' => 'boolean', 'group' => 'security', 'label' => 'Require Uppercase Letters'],
            'login_attempts_limit' => ['value' => 5, 'type' => 'integer', 'group' => 'security', 'label' => 'Login Attempts Limit'],
            'lockout_duration' => ['value' => 15, 'type' => 'integer', 'group' => 'security', 'label' => 'Lockout Duration (minutes)'],
            'two_factor_enabled' => ['value' => false, 'type' => 'boolean', 'group' => 'security', 'label' => 'Enable Two-Factor Authentication'],

            // Password Management Settings
            'password_history_count' => ['value' => 5, 'type' => 'integer', 'group' => 'security', 'label' => 'Password History Count', 'description' => 'Number of previous passwords to prevent reuse (0 = disabled)'],
            'password_expiration_days' => ['value' => 0, 'type' => 'integer', 'group' => 'security', 'label' => 'Password Expiration (days)', 'description' => 'Number of days before password expires (0 = never expires)'],
            'password_expiration_warning_days' => ['value' => 7, 'type' => 'integer', 'group' => 'security', 'label' => 'Password Expiration Warning (days)', 'description' => 'Show warning when password expires in this many days'],
            'password_enable_blacklist' => ['value' => true, 'type' => 'boolean', 'group' => 'security', 'label' => 'Enable Common Password Blacklist', 'description' => 'Block common/weak passwords'],
            'password_custom_blacklist' => ['value' => '', 'type' => 'string', 'group' => 'security', 'label' => 'Custom Password Blacklist', 'description' => 'Comma-separated list of passwords to block'],
            'password_require_strength_meter' => ['value' => true, 'type' => 'boolean', 'group' => 'security', 'label' => 'Show Password Strength Meter', 'description' => 'Display real-time password strength indicator'],
            'password_min_strength_score' => ['value' => 40, 'type' => 'integer', 'group' => 'security', 'label' => 'Minimum Password Strength Score', 'description' => 'Minimum strength score required (0-100)'],

            // HTTPS Enforcement Settings
            'security_force_https' => ['value' => false, 'type' => 'boolean', 'group' => 'security', 'label' => 'Force HTTPS Redirect', 'description' => 'Redirect all HTTP requests to HTTPS'],
            'security_enable_hsts' => ['value' => true, 'type' => 'boolean', 'group' => 'security', 'label' => 'Enable HSTS (HTTP Strict Transport Security)', 'description' => 'Enable Strict-Transport-Security header to force HTTPS connections'],
            'security_hsts_max_age' => ['value' => 31536000, 'type' => 'integer', 'group' => 'security', 'label' => 'HSTS Max Age (seconds)', 'description' => 'How long browsers should remember to use HTTPS (default: 1 year = 31536000)'],
            'security_hsts_include_subdomains' => ['value' => true, 'type' => 'boolean', 'group' => 'security', 'label' => 'HSTS Include Subdomains', 'description' => 'Apply HSTS to all subdomains'],
            'security_hsts_preload' => ['value' => false, 'type' => 'boolean', 'group' => 'security', 'label' => 'HSTS Preload', 'description' => 'Enable HSTS preload (only enable if you have submitted your domain to HSTS preload list)'],
            'security_enforce_secure_cookies' => ['value' => true, 'type' => 'boolean', 'group' => 'security', 'label' => 'Enforce Secure Cookies', 'description' => 'Only send cookies over HTTPS connections'],
            'security_content_security_policy' => ['value' => 'default', 'type' => 'string', 'group' => 'security', 'label' => 'Content Security Policy', 'description' => 'Use "default" for secure nonce-based CSP (recommended). Set to "disabled" to disable. Or provide a custom CSP string.'],
            'security_referrer_policy' => ['value' => 'strict-origin-when-cross-origin', 'type' => 'string', 'group' => 'security', 'label' => 'Referrer Policy', 'description' => 'Control how much referrer information is sent'],
            'security_permissions_policy' => ['value' => 'geolocation=(), microphone=(), camera=(self)', 'type' => 'string', 'group' => 'security', 'label' => 'Permissions Policy', 'description' => 'Control browser features and APIs (set to "disabled" to disable). Camera is allowed for QR scanning.'],

            // Rate Limiting Settings
            'rate_limit_login_attempts' => ['value' => 5, 'type' => 'integer', 'group' => 'security', 'label' => 'Login Rate Limit (attempts per phone)', 'description' => 'Maximum login attempts per phone number (brute force protection per account)'],
            'rate_limit_login_decay' => ['value' => 15, 'type' => 'integer', 'group' => 'security', 'label' => 'Login Rate Limit Window (minutes)', 'description' => 'Time window for login rate limiting'],
            'rate_limit_login_per_ip_attempts' => ['value' => 20, 'type' => 'integer', 'group' => 'security', 'label' => 'Login Rate Limit (attempts per IP)', 'description' => 'Maximum total login attempts per IP across all usernames (prevents credential stuffing)'],
            'rate_limit_password_reset_attempts' => ['value' => 3, 'type' => 'integer', 'group' => 'security', 'label' => 'Password Reset Rate Limit (attempts)', 'description' => 'Maximum password reset requests allowed per IP'],
            'rate_limit_password_reset_decay' => ['value' => 15, 'type' => 'integer', 'group' => 'security', 'label' => 'Password Reset Rate Limit Window (minutes)', 'description' => 'Time window for password reset rate limiting'],
            'rate_limit_otp_attempts' => ['value' => 5, 'type' => 'integer', 'group' => 'security', 'label' => 'OTP Rate Limit (attempts)', 'description' => 'Maximum OTP requests allowed per IP'],
            'rate_limit_otp_decay' => ['value' => 5, 'type' => 'integer', 'group' => 'security', 'label' => 'OTP Rate Limit Window (minutes)', 'description' => 'Time window for OTP rate limiting'],
            'rate_limit_api_auth_attempts' => ['value' => 60, 'type' => 'integer', 'group' => 'security', 'label' => 'API Rate Limit - Authenticated (requests)', 'description' => 'Maximum API requests per minute for authenticated users'],
            'rate_limit_api_auth_decay' => ['value' => 1, 'type' => 'integer', 'group' => 'security', 'label' => 'API Rate Limit - Authenticated Window (minutes)', 'description' => 'Time window for authenticated API rate limiting'],
            'rate_limit_api_unauth_attempts' => ['value' => 20, 'type' => 'integer', 'group' => 'security', 'label' => 'API Rate Limit - Unauthenticated (requests)', 'description' => 'Maximum API requests per minute for unauthenticated users'],
            'rate_limit_api_unauth_decay' => ['value' => 1, 'type' => 'integer', 'group' => 'security', 'label' => 'API Rate Limit - Unauthenticated Window (minutes)', 'description' => 'Time window for unauthenticated API rate limiting'],
            'rate_limit_global_attempts' => ['value' => 200, 'type' => 'integer', 'group' => 'security', 'label' => 'Global Rate Limit (requests)', 'description' => 'Maximum requests per minute per IP (DDoS protection)'],
            'rate_limit_global_decay' => ['value' => 1, 'type' => 'integer', 'group' => 'security', 'label' => 'Global Rate Limit Window (minutes)', 'description' => 'Time window for global rate limiting'],
            'rate_limit_registration_attempts' => ['value' => 3, 'type' => 'integer', 'group' => 'security', 'label' => 'Registration Rate Limit (attempts)', 'description' => 'Maximum registration attempts allowed per IP'],
            'rate_limit_registration_decay' => ['value' => 60, 'type' => 'integer', 'group' => 'security', 'label' => 'Registration Rate Limit Window (minutes)', 'description' => 'Time window for registration rate limiting'],
            'rate_limit_search_attempts' => ['value' => 30, 'type' => 'integer', 'group' => 'security', 'label' => 'Search Rate Limit (requests)', 'description' => 'Maximum search requests per minute'],
            'rate_limit_search_decay' => ['value' => 1, 'type' => 'integer', 'group' => 'security', 'label' => 'Search Rate Limit Window (minutes)', 'description' => 'Time window for search rate limiting'],
            'rate_limit_upload_attempts' => ['value' => 10, 'type' => 'integer', 'group' => 'security', 'label' => 'Upload Rate Limit (requests)', 'description' => 'Maximum file upload requests per minute'],
            'rate_limit_upload_decay' => ['value' => 1, 'type' => 'integer', 'group' => 'security', 'label' => 'Upload Rate Limit Window (minutes)', 'description' => 'Time window for upload rate limiting'],

            // Backup Settings
            'backup_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'backup', 'label' => 'Enable Automatic Backups'],
            'backup_frequency' => ['value' => 'daily', 'type' => 'string', 'group' => 'backup', 'label' => 'Backup Frequency'],
            'backup_retention_days' => ['value' => 30, 'type' => 'integer', 'group' => 'backup', 'label' => 'Backup Retention (days)'],
            'backup_include_files' => ['value' => true, 'type' => 'boolean', 'group' => 'backup', 'label' => 'Include Files in Backup'],
            'backup_compression' => ['value' => true, 'type' => 'boolean', 'group' => 'backup', 'label' => 'Compress Backups'],

            // Maintenance Settings
            'maintenance_mode' => ['value' => false, 'type' => 'boolean', 'group' => 'maintenance', 'label' => 'Maintenance Mode'],
            'maintenance_message' => ['value' => 'System is under maintenance. Please try again later.', 'type' => 'string', 'group' => 'maintenance', 'label' => 'Maintenance Message'],
            'debug_mode' => ['value' => config('app.debug'), 'type' => 'boolean', 'group' => 'maintenance', 'label' => 'Debug Mode'],
            'log_level' => ['value' => config('logging.default'), 'type' => 'string', 'group' => 'maintenance', 'label' => 'Log Level'],

            // Inventory Specific Settings
            'inventory_low_stock_threshold' => ['value' => 10, 'type' => 'integer', 'group' => 'inventory', 'label' => 'Low Stock Threshold'],
            'inventory_auto_reorder_point' => ['value' => 5, 'type' => 'integer', 'group' => 'inventory', 'label' => 'Auto Reorder Point'],
            'inventory_default_unit' => ['value' => 'pieces', 'type' => 'string', 'group' => 'inventory', 'label' => 'Default Unit'],
            'inventory_cost_method' => ['value' => 'fifo', 'type' => 'string', 'group' => 'inventory', 'label' => 'Cost Method'],
            'inventory_barcode_prefix' => ['value' => 'INV', 'type' => 'string', 'group' => 'inventory', 'label' => 'Barcode Prefix'],
            'inventory_enable_batch_tracking' => ['value' => true, 'type' => 'boolean', 'group' => 'inventory', 'label' => 'Enable Batch Tracking'],
            'inventory_enable_expiry_tracking' => ['value' => true, 'type' => 'boolean', 'group' => 'inventory', 'label' => 'Enable Expiry Tracking'],
            'inventory_enable_serial_tracking' => ['value' => false, 'type' => 'boolean', 'group' => 'inventory', 'label' => 'Enable Serial Tracking'],
            'inventory_default_location' => ['value' => 1, 'type' => 'integer', 'group' => 'inventory', 'label' => 'Default Location'],
            // Inventory defaults – these are fallback values if no SystemSetting rows exist yet.
            // They are aligned with the new ChartAccountSeeder IDs.
            'inventory_default_inventory_account' => ['value' => 185, 'type' => 'integer', 'group' => 'inventory', 'label' => 'Default Inventory Account (Asset)'],
            'inventory_default_sales_account' => ['value' => 53, 'type' => 'integer', 'group' => 'inventory', 'label' => 'Default Sales Account', 'description' => 'Account used for recording sales transactions'],
            'inventory_default_cost_account' => ['value' => 173, 'type' => 'integer', 'group' => 'inventory', 'label' => 'Default Cost Account (COGS)'],
            'inventory_default_opening_balance_account' => ['value' => 41, 'type' => 'integer', 'group' => 'inventory', 'label' => 'Default Opening Balance Account', 'description' => 'Account used for recording opening inventory balances'],
            'inventory_default_vat_account' => ['value' => 36, 'type' => 'integer', 'group' => 'inventory', 'label' => 'VAT Payable', 'description' => 'Account used for VAT liability tracking'],
            'inventory_default_withholding_tax_account' => ['value' => 37, 'type' => 'integer', 'group' => 'inventory', 'label' => 'Default Withholding Tax Account', 'description' => 'Account used for withholding tax liability tracking'],
            'inventory_default_withholding_tax_expense_account' => ['value' => null, 'type' => 'integer', 'group' => 'inventory', 'label' => 'Default Withholding Tax Expense Account (Expense)'],
            'inventory_default_purchase_payable_account' => ['value' => null, 'type' => 'integer', 'group' => 'inventory', 'label' => 'Default Purchase Payable Account (Liability)'],
            'inventory_default_discount_account' => ['value' => null, 'type' => 'integer', 'group' => 'inventory', 'label' => 'Default Discount Account (Expense)'],
            'inventory_default_discount_income_account' => ['value' => null, 'type' => 'integer', 'group' => 'inventory', 'label' => 'Default Discount Income Account (Revenue)'],
            'inventory_default_is_withholding_receivable' => ['value' => false, 'type' => 'boolean', 'group' => 'inventory', 'label' => 'Default Withholding Tax Type'],
            'inventory_default_early_payment_discount_account' => ['value' => null, 'type' => 'integer', 'group' => 'inventory', 'label' => 'Default Early Payment Discount Account (Expense)'],
            'inventory_default_late_payment_fees_account' => ['value' => null, 'type' => 'integer', 'group' => 'inventory', 'label' => 'Default Late Payment Fees Account (Revenue)'],
            'inventory_default_receivable_account' => ['value' => null, 'type' => 'integer', 'group' => 'inventory', 'label' => 'Default Accounts Receivable Account (Asset)'],
            'inventory_default_cash_account' => ['value' => 1, 'type' => 'integer', 'group' => 'inventory', 'label' => 'Default Cash Account (Cash on Hand)', 'description' => 'Account used for cash transactions (cash sales, cash purchases, receipts, payments)'],
            'inventory_default_invoice_due_days' => [
                'value' => 30,
                'type' => 'integer',
                'group' => 'inventory',
                'label' => 'Default Invoice Due Days',
                'description' => 'Number of days from invoice date to due date for sales invoices created from inventory (0 = due on invoice date)'
            ],

            // Debit Notes / Workflow
            'auto_approve_debit_notes' => ['value' => '0', 'type' => 'boolean', 'group' => 'purchases', 'label' => 'Auto-Approve Debit Notes'],

            // Hotel & Property Management Chart Accounts
            'hotel_room_revenue_account_id' => ['value' => 194, 'type' => 'integer', 'group' => 'hotel_property', 'label' => 'Hotel Room Revenue Account'],
            'hotel_service_revenue_account_id' => ['value' => 195, 'type' => 'integer', 'group' => 'hotel_property', 'label' => 'Hotel Service Revenue Account'],
            'hotel_food_beverage_account_id' => ['value' => 196, 'type' => 'integer', 'group' => 'hotel_property', 'label' => 'Hotel Food & Beverage Revenue Account'],
            'hotel_operating_expense_account_id' => ['value' => 201, 'type' => 'integer', 'group' => 'hotel_property', 'label' => 'Hotel Operating Expense Account'],
            'hotel_maintenance_expense_account_id' => ['value' => 202, 'type' => 'integer', 'group' => 'hotel_property', 'label' => 'Hotel Maintenance Expense Account'],
            'hotel_marketing_expense_account_id' => ['value' => 203, 'type' => 'integer', 'group' => 'hotel_property', 'label' => 'Hotel Marketing Expense Account'],
            'hotel_discount_expense_account_id' => ['value' => 172, 'type' => 'integer', 'group' => 'hotel_property', 'label' => 'Hotel Discount Expense Account'],

            'property_rental_income_account_id' => ['value' => 197, 'type' => 'integer', 'group' => 'hotel_property', 'label' => 'Property Rental Income Account'],
            'property_service_charge_account_id' => ['value' => 198, 'type' => 'integer', 'group' => 'hotel_property', 'label' => 'Property Service Charge Account'],
            'property_late_fee_account_id' => ['value' => 199, 'type' => 'integer', 'group' => 'hotel_property', 'label' => 'Property Late Fee Account'],
            'property_operating_expense_account_id' => ['value' => 205, 'type' => 'integer', 'group' => 'hotel_property', 'label' => 'Property Operating Expense Account'],
            'property_maintenance_expense_account_id' => ['value' => 206, 'type' => 'integer', 'group' => 'hotel_property', 'label' => 'Property Maintenance Expense Account'],
            'property_utilities_expense_account_id' => ['value' => 207, 'type' => 'integer', 'group' => 'hotel_property', 'label' => 'Property Utilities Expense Account'],
            'property_management_fee_account_id' => ['value' => 200, 'type' => 'integer', 'group' => 'hotel_property', 'label' => 'Property Management Fee Account'],

            'property_asset_account_id' => ['value' => 187, 'type' => 'integer', 'group' => 'hotel_property', 'label' => 'Property Asset Account'],
            'furniture_fixtures_account_id' => ['value' => 188, 'type' => 'integer', 'group' => 'hotel_property', 'label' => 'Furniture & Fixtures Account'],
            'accumulated_depreciation_account_id' => ['value' => 190, 'type' => 'integer', 'group' => 'hotel_property', 'label' => 'Accumulated Depreciation Account'],
            'security_deposit_liability_account_id' => ['value' => 193, 'type' => 'integer', 'group' => 'hotel_property', 'label' => 'Security Deposit Liability Account'],

            //Asset Management Settings
            'asset_default_asset_account' => ['value' => 600, 'type' => 'integer', 'group' => 'assets', 'label' => 'Default Asset Account (Asset)'],
            'asset_default_accumulated_depreciation_account' => ['value' => 601, 'type' => 'integer', 'group' => 'assets', 'label' => 'Default Accumulated Depreciation Account (Contra Asset)'],
            'asset_default_depreciation_expense_account' => ['value' => 73, 'type' => 'integer', 'group' => 'assets', 'label' => 'Default Depreciation Expense Account (Expense)'],
            'asset_default_loss_disposal_account' => ['value' => 748, 'type' => 'integer', 'group' => 'assets', 'label' => 'Default Asset Loss Disposal Account (Expense)'],
            'asset_default_gain_disposal_account' => ['value' => 747, 'type' => 'integer', 'group' => 'assets', 'label' => 'Default Asset Gain Disposal Account (Revenue)'],
            'asset_default_revaluation_gain_account' => ['value' => 602, 'type' => 'integer', 'group' => 'assets', 'label' => 'Default Asset Revaluation Gain Account (Equity)'],
            'asset_default_revaluation_loss_account' => ['value' => 637, 'type' => 'integer', 'group' => 'assets', 'label' => 'Default Asset Revaluation Loss Account (Expense)'],
            'asset_default_hfs_account' => ['value' => 723, 'type' => 'integer', 'group' => 'assets', 'label' => 'Default Held for Sale Account (Asset)'],
            'asset_default_impairment_loss_account' => ['value' => 603, 'type' => 'integer', 'group' => 'assets', 'label' => 'Default Impairment Loss Account (Expense)'],
            'asset_below_threshold_expense_account' => ['value' => 73, 'type' => 'integer', 'group' => 'assets', 'label' => 'Below Capitalization Threshold Expense Account', 'description' => 'Expense account for purchase lines below category capitalization threshold (when category has no expense account set)'],

            // Bank Reconciliation Chart Accounts (per company, set via settings UI)
            'bank_reconciliation_bank_fees_account_id' => ['value' => null, 'type' => 'integer', 'group' => 'bank_reconciliation', 'label' => 'Bank Fees Expense Account'],
            'bank_reconciliation_interest_income_account_id' => ['value' => null, 'type' => 'integer', 'group' => 'bank_reconciliation', 'label' => 'Interest Income Account'],
            'bank_reconciliation_other_expense_account_id' => ['value' => null, 'type' => 'integer', 'group' => 'bank_reconciliation', 'label' => 'Other Expense Account (for adjustments)'],
            'bank_reconciliation_other_income_account_id' => ['value' => null, 'type' => 'integer', 'group' => 'bank_reconciliation', 'label' => 'Other Income Account (for adjustments)'],

            // Document & Print Settings
            'document_page_size' => ['value' => 'A5', 'type' => 'string', 'group' => 'documents', 'label' => 'Default Page Size', 'description' => 'Global page size (A3, A4, A5, A6, Letter, Legal)'],
            'document_orientation' => ['value' => 'portrait', 'type' => 'string', 'group' => 'documents', 'label' => 'Default Orientation', 'description' => 'portrait or landscape'],
            'document_margin_top' => ['value' => '2.54cm', 'type' => 'string', 'group' => 'documents', 'label' => 'Top Margin', 'description' => 'CSS size value in cm, e.g., 1.0cm'],
            'document_margin_right' => ['value' => '2.54cm', 'type' => 'string', 'group' => 'documents', 'label' => 'Right Margin', 'description' => 'CSS size value in cm'],
            'document_margin_bottom' => ['value' => '2.54cm', 'type' => 'string', 'group' => 'documents', 'label' => 'Bottom Margin', 'description' => 'CSS size value in cm'],
            'document_margin_left' => ['value' => '2.54cm', 'type' => 'string', 'group' => 'documents', 'label' => 'Left Margin', 'description' => 'CSS size value in cm'],
            'document_font_family' => ['value' => 'DejaVu Sans', 'type' => 'string', 'group' => 'documents', 'label' => 'Font Family', 'description' => 'Use fonts supported by PDF (e.g., DejaVu Sans, Arial, Times New Roman)'],
            'document_base_font_size' => ['value' => 10, 'type' => 'integer', 'group' => 'documents', 'label' => 'Base Font Size (px)'],
            'document_line_height' => ['value' => 1.4, 'type' => 'string', 'group' => 'documents', 'label' => 'Line Height'],
            'document_text_color' => ['value' => '#000000', 'type' => 'string', 'group' => 'documents', 'label' => 'Text Color'],
            'document_background_color' => ['value' => '#FFFFFF', 'type' => 'string', 'group' => 'documents', 'label' => 'Background Color'],
            'document_header_color' => ['value' => '#000000', 'type' => 'string', 'group' => 'documents', 'label' => 'Header Text Color'],
            'document_accent_color' => ['value' => '#000000', 'type' => 'string', 'group' => 'documents', 'label' => 'Accent Color'],
            'document_table_header_bg' => ['value' => '#f2f2f2', 'type' => 'string', 'group' => 'documents', 'label' => 'Table Header Background'],
            'document_table_header_text' => ['value' => '#000000', 'type' => 'string', 'group' => 'documents', 'label' => 'Table Header Text Color'],

            // Subscription Settings
            'subscription_notification_days_30' => ['value' => 30, 'type' => 'integer', 'group' => 'subscription', 'label' => 'First Notification Days (30 days)', 'description' => 'Show notification when subscription expires in this many days or less'],
            'subscription_notification_days_20' => ['value' => 20, 'type' => 'integer', 'group' => 'subscription', 'label' => 'Second Notification Days (20 days)', 'description' => 'Show notification when subscription expires in this many days or less'],

            // Company Settings
            'functional_currency' => ['value' => 'TZS', 'type' => 'string', 'group' => 'company', 'label' => 'Functional Currency', 'description' => 'Company\'s functional currency (base currency for reporting)'],
            'fx_realized_gain_account_id' => ['value' => null, 'type' => 'integer', 'group' => 'company', 'label' => 'FX Realized Gain Account', 'description' => 'Chart of account for recording realized foreign exchange gains'],
            'fx_realized_loss_account_id' => ['value' => null, 'type' => 'integer', 'group' => 'company', 'label' => 'FX Realized Loss Account', 'description' => 'Chart of account for recording realized foreign exchange losses'],
            'fx_unrealized_gain_account_id' => ['value' => null, 'type' => 'integer', 'group' => 'company', 'label' => 'FX Unrealized Gain Account', 'description' => 'Chart of account for recording unrealized foreign exchange gains'],
            'fx_unrealized_loss_account_id' => ['value' => null, 'type' => 'integer', 'group' => 'company', 'label' => 'FX Unrealized Loss Account', 'description' => 'Chart of account for recording unrealized foreign exchange losses'],
            'fx_rate_override_threshold' => ['value' => '5', 'type' => 'string', 'group' => 'company', 'label' => 'FX Rate Override Threshold (%)', 'description' => 'Percentage threshold for rate override approval. If rate override exceeds this percentage, approval is required.'],
            'fx_revaluation_approval_required' => ['value' => false, 'type' => 'boolean', 'group' => 'company', 'label' => 'FX Revaluation Approval Required', 'description' => 'Require approval before posting FX revaluation journal entries'],

            // Sales Settings
            'sales_default_payment_terms' => ['value' => 'net_30', 'type' => 'string', 'group' => 'sales', 'label' => 'Default Payment Terms', 'description' => 'Default payment terms for sales invoices (immediate, net_15, net_30, net_45, net_60, custom)'],
            'sales_default_payment_days' => ['value' => 30, 'type' => 'integer', 'group' => 'sales', 'label' => 'Default Payment Days', 'description' => 'Default number of days for payment terms'],
            'sales_invoice_number_prefix' => ['value' => 'INV', 'type' => 'string', 'group' => 'sales', 'label' => 'Invoice Number Prefix', 'description' => 'Prefix for sales invoice numbers'],
            'sales_default_vat_type' => ['value' => 'inclusive', 'type' => 'string', 'group' => 'sales', 'label' => 'Default VAT Type', 'description' => 'Default VAT type for sales invoices (inclusive, exclusive)'],
            'sales_default_vat_rate' => ['value' => 18.00, 'type' => 'string', 'group' => 'sales', 'label' => 'Default VAT Rate (%)', 'description' => 'Default VAT rate percentage for sales invoices'],
            'sales_edit_time_limit_hours' => ['value' => 24, 'type' => 'integer', 'group' => 'sales', 'label' => 'Sales Edit Time Limit (Hours)', 'description' => 'Number of hours after which cash sales and POS sales cannot be edited (0 = no limit)'],
            'sales_auto_apply_discount' => ['value' => false, 'type' => 'boolean', 'group' => 'sales', 'label' => 'Auto Apply Discount', 'description' => 'Automatically apply discount to sales invoices'],
            'sales_enable_early_payment_discount' => ['value' => false, 'type' => 'boolean', 'group' => 'sales', 'label' => 'Enable Early Payment Discount', 'description' => 'Enable early payment discount feature for sales invoices'],
            'sales_enable_late_payment_fees' => ['value' => false, 'type' => 'boolean', 'group' => 'sales', 'label' => 'Enable Late Payment Fees', 'description' => 'Enable late payment fees feature for sales invoices'],
            'sales_require_approval' => ['value' => false, 'type' => 'boolean', 'group' => 'sales', 'label' => 'Require Invoice Approval', 'description' => 'Require approval before finalizing sales invoices'],
            'sales_allow_negative_stock' => ['value' => false, 'type' => 'boolean', 'group' => 'sales', 'label' => 'Allow Negative Stock', 'description' => 'Allow sales when stock is insufficient'],

        ];

        foreach ($defaults as $key => $config) {
            self::setValue(
                $key,
                $config['value'],
                $config['type'],
                $config['group'],
                $config['label'],
                $config['description'] ?? null
            );
        }
    }
}
