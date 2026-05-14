<?php

namespace App\Channels;

use App\Helpers\SmsHelper;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        Log::info('SMS Channel called', [
            'user_id' => $notifiable->id ?? null,
            'user_name' => $notifiable->name ?? 'N/A',
            'user_phone' => $notifiable->phone ?? 'N/A',
            'notification' => get_class($notification),
        ]);
        
        // Check if notification has toSms method
        if (!method_exists($notification, 'toSms')) {
            Log::warning('SMS notification skipped: Notification does not have toSms method', [
                'user_id' => $notifiable->id ?? null,
                'notification' => get_class($notification),
            ]);
            return;
        }

        // Get phone number from notifiable
        $phone = $notifiable->phone ?? null;

        if (!$phone) {
            Log::info('SMS notification skipped: User has no phone number', [
                'user_id' => $notifiable->id ?? null,
                'user_name' => $notifiable->name ?? 'N/A',
                'notification' => get_class($notification),
            ]);
            return;
        }

        // Get message from notification
        $message = $notification->toSms($notifiable);

        if (empty($message)) {
            Log::warning('SMS notification skipped: Empty message', [
                'user_id' => $notifiable->id ?? null,
                'notification' => get_class($notification),
            ]);
            return;
        }

        try {
            // Format phone number (ensure it's in international format)
            $formattedPhone = function_exists('normalize_phone_number') 
                ? normalize_phone_number($phone) 
                : $this->formatPhoneNumber($phone);

            // Send SMS using SmsHelper
            $response = SmsHelper::send($formattedPhone, $message);

            Log::info('SMS notification sent', [
                'user_id' => $notifiable->id ?? null,
                'phone' => $formattedPhone,
                'notification' => get_class($notification),
            ]);
        } catch (\Exception $e) {
            Log::error('SMS notification failed', [
                'user_id' => $notifiable->id ?? null,
                'user_name' => $notifiable->name ?? 'N/A',
                'phone' => $phone,
                'formatted_phone' => $formattedPhone ?? 'N/A',
                'notification' => get_class($notification),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Re-throw to ensure it's logged but don't break the notification flow
        }
    }

    /**
     * Format phone number to international format (fallback if helper not available)
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Remove leading +
        $phone = ltrim($phone, '+');
        
        // If starts with 0, replace with 255 (Tanzania country code)
        if (str_starts_with($phone, '0')) {
            return '255' . substr($phone, 1);
        }
        
        // If doesn't start with 255, add it
        if (!str_starts_with($phone, '255')) {
            return '255' . $phone;
        }
        
        return $phone;
    }
}

