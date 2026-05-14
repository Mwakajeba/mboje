<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class SystemSettingService
{
    /**
     * Get a setting value
     */
    public static function get($key, $default = null)
    {
        return SystemSetting::getValue($key, $default);
    }

    /**
     * Set a setting value
     */
    public static function set($key, $value, $type = 'string', $group = 'general', $label = null, $description = null)
    {
        return SystemSetting::setValue($key, $value, $type, $group, $label, $description);
    }

    /**
     * Get all settings as array
     */
    public static function all()
    {
        return SystemSetting::getAllAsArray();
    }

    /**
     * Get settings by group
     */
    public static function getByGroup($group)
    {
        return SystemSetting::getByGroup($group);
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache()
    {
        SystemSetting::clearCache();
    }

    /**
     * Get application configuration
     */
    public static function getAppConfig()
    {
        return [
            'name' => self::get('app_name', config('app.name')),
            'url' => self::get('app_url', config('app.url')),
            'timezone' => self::get('timezone', config('app.timezone')),
            'locale' => self::get('locale', config('app.locale')),
            'debug' => self::get('debug_mode', config('app.debug')),
        ];
    }

    /**
     * Get email configuration
     */
    public static function getEmailConfig()
    {
        return [
            'driver' => self::get('mail_driver', config('mail.default')),
            'host' => self::get('mail_host', config('mail.mailers.smtp.host')),
            'port' => self::get('mail_port', config('mail.mailers.smtp.port')),
            'username' => self::get('mail_username', config('mail.mailers.smtp.username')),
            'password' => self::get('mail_password', config('mail.mailers.smtp.password')),
            'encryption' => self::get('mail_encryption', config('mail.mailers.smtp.encryption')),
            'from_address' => self::get('mail_from_address', config('mail.from.address')),
            'from_name' => self::get('mail_from_name', config('mail.from.name')),
        ];
    }

    /**
     * Get security configuration
     */
    public static function getSecurityConfig()
    {
        return [
            'session_lifetime' => self::get('session_lifetime', config('session.lifetime')),
            'password_min_length' => self::get('password_min_length', 8),
            'password_require_special' => self::get('password_require_special', true),
            'password_require_numbers' => self::get('password_require_numbers', true),
            'password_require_uppercase' => self::get('password_require_uppercase', true),
            'login_attempts_limit' => self::get('login_attempts_limit', 5),
            'lockout_duration' => self::get('lockout_duration', 15),
            'two_factor_enabled' => self::get('two_factor_enabled', false),
        ];
    }

    /**
     * Get rate limiting configuration
     */
    public static function getRateLimitingConfig()
    {
        return [
            'login' => [
                'max_attempts' => self::get('rate_limit_login_attempts', config('rate-limiting.login.max_attempts', 5)),
                'decay_minutes' => self::get('rate_limit_login_decay', config('rate-limiting.login.decay_minutes', 15)),
                'max_attempts_per_ip' => self::get('rate_limit_login_per_ip_attempts', config('rate-limiting.login.max_attempts_per_ip', 20)),
            ],
            'password_reset' => [
                'max_attempts' => self::get('rate_limit_password_reset_attempts', config('rate-limiting.password_reset.max_attempts', 3)),
                'decay_minutes' => self::get('rate_limit_password_reset_decay', config('rate-limiting.password_reset.decay_minutes', 15)),
            ],
            'otp' => [
                'max_attempts' => self::get('rate_limit_otp_attempts', config('rate-limiting.otp.max_attempts', 5)),
                'decay_minutes' => self::get('rate_limit_otp_decay', config('rate-limiting.otp.decay_minutes', 5)),
            ],
            'api' => [
                'authenticated' => [
                    'max_attempts' => self::get('rate_limit_api_auth_attempts', config('rate-limiting.api.authenticated.max_attempts', 60)),
                    'decay_minutes' => self::get('rate_limit_api_auth_decay', config('rate-limiting.api.authenticated.decay_minutes', 1)),
                ],
                'unauthenticated' => [
                    'max_attempts' => self::get('rate_limit_api_unauth_attempts', config('rate-limiting.api.unauthenticated.max_attempts', 20)),
                    'decay_minutes' => self::get('rate_limit_api_unauth_decay', config('rate-limiting.api.unauthenticated.decay_minutes', 1)),
                ],
            ],
            'global' => [
                'max_attempts' => self::get('rate_limit_global_attempts', config('rate-limiting.global.max_attempts', 200)),
                'decay_minutes' => self::get('rate_limit_global_decay', config('rate-limiting.global.decay_minutes', 1)),
            ],
            'registration' => [
                'max_attempts' => self::get('rate_limit_registration_attempts', config('rate-limiting.registration.max_attempts', 3)),
                'decay_minutes' => self::get('rate_limit_registration_decay', config('rate-limiting.registration.decay_minutes', 60)),
            ],
            'search' => [
                'max_attempts' => self::get('rate_limit_search_attempts', config('rate-limiting.search.max_attempts', 30)),
                'decay_minutes' => self::get('rate_limit_search_decay', config('rate-limiting.search.decay_minutes', 1)),
            ],
            'upload' => [
                'max_attempts' => self::get('rate_limit_upload_attempts', config('rate-limiting.upload.max_attempts', 10)),
                'decay_minutes' => self::get('rate_limit_upload_decay', config('rate-limiting.upload.decay_minutes', 1)),
            ],
        ];
    }

    /**
     * Get backup configuration
     */
    public static function getBackupConfig()
    {
        return [
            'enabled' => self::get('backup_enabled', true),
            'frequency' => self::get('backup_frequency', 'daily'),
            'retention_days' => self::get('backup_retention_days', 30),
            'include_files' => self::get('backup_include_files', true),
            'compression' => self::get('backup_compression', true),
        ];
    }


    /**
     * Check if maintenance mode is enabled
     */
    public static function isMaintenanceMode()
    {
        return self::get('maintenance_mode', false);
    }

    /**
     * Get maintenance message
     */
    public static function getMaintenanceMessage()
    {
        return self::get('maintenance_message', 'System is under maintenance. Please try again later.');
    }

    /**
     * Apply settings to Laravel configuration
     */
    public static function applyToConfig()
    {
        $appConfig = self::getAppConfig();
        $emailConfig = self::getEmailConfig();

        // Apply app settings
        config(['app.name' => $appConfig['name']]);
        config(['app.url' => $appConfig['url']]);
        config(['app.timezone' => $appConfig['timezone']]);
        config(['app.locale' => $appConfig['locale']]);
        config(['app.debug' => $appConfig['debug']]);

        // Apply email settings
        config(['mail.default' => $emailConfig['driver']]);
        config(['mail.mailers.smtp.host' => $emailConfig['host']]);
        config(['mail.mailers.smtp.port' => $emailConfig['port']]);
        config(['mail.mailers.smtp.username' => $emailConfig['username']]);
        config(['mail.mailers.smtp.password' => $emailConfig['password']]);
        config(['mail.mailers.smtp.encryption' => $emailConfig['encryption']]);
        config(['mail.from.address' => $emailConfig['from_address']]);
        config(['mail.from.name' => $emailConfig['from_name']]);

        // Apply session settings
        $securityConfig = self::getSecurityConfig();
        config(['session.lifetime' => $securityConfig['session_lifetime']]);
    }

    /**
     * Validate email settings
     */
    public static function validateEmailSettings()
    {
        $emailConfig = self::getEmailConfig();
        
        $required = ['host', 'port', 'username', 'password'];
        $missing = [];
        
        foreach ($required as $field) {
            if (empty($emailConfig[$field])) {
                $missing[] = $field;
            }
        }
        
        return [
            'valid' => empty($missing),
            'missing' => $missing,
            'config' => $emailConfig
        ];
    }

    /**
     * Test email configuration
     */
    public static function testEmailConfig()
    {
        try {
            $emailConfig = self::getEmailConfig();
            
            // Create a test mailer
            $config = [
                'transport' => 'smtp',
                'host' => $emailConfig['host'],
                'port' => $emailConfig['port'],
                'encryption' => $emailConfig['encryption'],
                'username' => $emailConfig['username'],
                'password' => $emailConfig['password'],
                'timeout' => null,
                'local_domain' => env('MAIL_EHLO_DOMAIN'),
            ];

            $mailer = new \Illuminate\Mail\MailManager(app(), $config);
            
            // Try to send a test email
            $mailer->raw('Test email from SmartFinance system settings', function($message) use ($emailConfig) {
                $message->to($emailConfig['from_address'])
                        ->subject('SmartFinance Email Test');
            });

            return ['success' => true, 'message' => 'Email configuration is working correctly'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Email configuration failed: ' . $e->getMessage()];
        }
    }
} 