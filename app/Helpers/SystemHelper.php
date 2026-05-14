<?php

if (!function_exists('setting')) {
    /**
     * Get a system setting value
     */
    function setting($key, $default = null)
    {
        return \App\Services\SystemSettingService::get($key, $default);
    }
}

if (!function_exists('app_setting')) {
    /**
     * Get application setting
     */
    function app_setting($key, $default = null)
    {
        return \App\Services\SystemSettingService::get($key, $default);
    }
}


if (!function_exists('is_maintenance_mode')) {
    /**
     * Check if maintenance mode is enabled
     */
    function is_maintenance_mode()
    {
        return \App\Services\SystemSettingService::isMaintenanceMode();
    }
}

if (!function_exists('get_maintenance_message')) {
    /**
     * Get maintenance message
     */
    function get_maintenance_message()
    {
        return \App\Services\SystemSettingService::getMaintenanceMessage();
    }
}

if (!function_exists('get_default_vat_type')) {
    /**
     * Get default VAT type from system settings
     */
    function get_default_vat_type()
    {
        return setting('inventory_default_vat_type', 'inclusive');
    }
}

if (!function_exists('get_default_vat_rate')) {
    /**
     * Get default VAT rate from system settings
     */
    function get_default_vat_rate()
    {
        return (float) setting('inventory_default_vat_rate', 18.00);
    }
}

if (!function_exists('format_currency')) {
    /**
     * Format currency based on system settings
     */
    function format_currency($amount, $currency = null)
    {
        $currency = $currency ?: setting('currency', 'TZS');
        $symbol = setting('currency_symbol', 'TSh');
        
        return $symbol . number_format($amount, 2);
    }
}

if (!function_exists('format_date')) {
    /**
     * Format date based on system settings
     */
    function format_date($date, $format = null)
    {
        $format = $format ?: setting('date_format', 'Y-m-d');
        
        if ($date instanceof \Carbon\Carbon) {
            return $date->format($format);
        }
        
        return \Carbon\Carbon::parse($date)->format($format);
    }
}

if (!function_exists('format_datetime')) {
    /**
     * Format datetime based on system settings
     */
    function format_datetime($datetime, $dateFormat = null, $timeFormat = null)
    {
        $dateFormat = $dateFormat ?: setting('date_format', 'Y-m-d');
        $timeFormat = $timeFormat ?: setting('time_format', 'H:i:s');
        $format = $dateFormat . ' ' . $timeFormat;
        
        if ($datetime instanceof \Carbon\Carbon) {
            return $datetime->format($format);
        }
        
        return \Carbon\Carbon::parse($datetime)->format($format);
    }
}

if (!function_exists('get_phrase')) {
    /**
     * Get phrase translation
     */
    function get_phrase($phrase, $default = null)
    {
        return $default ?? $phrase;
    }
}

if (!function_exists('format_date')) {
    /**
     * Format date in local timezone
     */
    function format_date($date, $format = 'd M Y')
    {
        if (!$date) {
            return 'N/A';
        }
        
        if (is_string($date)) {
            $date = \Carbon\Carbon::parse($date);
        }
        
        return $date->setTimezone(config('app.timezone'))->format($format);
    }
}

if (!function_exists('format_datetime')) {
    /**
     * Format datetime in local timezone
     */
    function format_datetime($date, $format = 'd M Y H:i:s')
    {
        if (!$date) {
            return 'N/A';
        }
        
        if (is_string($date)) {
            $date = \Carbon\Carbon::parse($date);
        }
        
        return $date->setTimezone(config('app.timezone'))->format($format);
    }
}